<?php
/**
 * login.php — Accesso area riservata FantaScarrupat
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/login_rate_limit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(fs_session_cookie_params());
    session_start();
}

// Già loggato: vai alla dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'app/dashboard.php', true, 302);
    exit;
}

// ── CSRF token ────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Rate limiting (sessione-based, 5 tentativi → 15 min blocco) ───────────────
$rate_locked     = false;
$rate_remaining  = 0;
if (!empty($_SESSION['_login_lock_until'])) {
    $left = (int)$_SESSION['_login_lock_until'] - time();
    if ($left > 0) {
        $rate_locked    = true;
        $rate_remaining = (int)ceil($left / 60);
    } else {
        unset($_SESSION['_login_lock_until'], $_SESSION['_login_fails']);
    }
}

// Flash messages da redirect precedenti
$error = '';
$flash = '';
if (!empty($_SESSION['flash_login_error'])) {
    $error = (string)$_SESSION['flash_login_error'];
    unset($_SESSION['flash_login_error']);
}
if (!empty($_SESSION['flash_login_info'])) {
    $flash = (string)$_SESSION['flash_login_info'];
    unset($_SESSION['flash_login_info']);
}

// Messaggio da querystring reason
if ($error === '' && !empty($_GET['reason'])) {
    $flash = match ((string)$_GET['reason']) {
        'idle'            => 'Sessione scaduta per inattività.',
        'session_expired' => 'La tua sessione è scaduta (limite di 8 ore). Accedi nuovamente.',
        'session_invalid' => 'Sessione non valida. Per sicurezza, accedi nuovamente.',
        default           => '',
    };
}

// ── Gestione POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$rate_locked) {

    // Verifica CSRF
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Token di sicurezza non valido. Ricarica la pagina e riprova.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Inserisci username e password.';
        } else {
            $db      = getDB();
            $ip_hash = lrl_ip_hash();
            $lrl     = lrl_check($db, $ip_hash);

            if ($lrl['blocked']) {
                $rate_locked    = true;
                $rate_remaining = $lrl['minutes'];
                $error = 'Troppi tentativi falliti da questa rete. Riprova tra circa '
                       . $lrl['minutes'] . ' minuti.';
            } else {
            try {
                $stmt = $db->prepare(
                    "SELECT id_user, username, password_hash, flag_admin, nome_fantasquadra
                     FROM users WHERE username = ? LIMIT 1"
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                $ok = false;
                if ($user !== false) {
                    if (password_verify($password, $user['password_hash'])) {
                        $ok = true;
                    } elseif (preg_match('/^[a-f0-9]{64}$/i', $user['password_hash'])
                              && hash_equals($user['password_hash'], hash('sha256', $password))) {
                        // Upgrade hash SHA-256 legacy → bcrypt
                        $db->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?")
                           ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id_user']]);
                        $ok = true;
                    }
                }

                if ($ok) {
                    // Reset rate limit (sessione + DB per questo IP)
                    unset($_SESSION['_login_fails'], $_SESSION['_login_lock_until']);
                    lrl_clear($db, $ip_hash);

                    // Rigenera session ID prima di scrivere dati sensibili
                    session_regenerate_id(true);

                    // Aggiorna DB
                    $db->prepare("UPDATE users SET last_login = NOW(), last_activity = NOW() WHERE id_user = ?")
                       ->execute([(int)$user['id_user']]);

                    // Bootstrap sessione
                    $now = time();
                    $_SESSION['user_id']           = (int)$user['id_user'];
                    $_SESSION['username']          = $user['username'];
                    $_SESSION['flag_admin']        = (int)$user['flag_admin'];
                    $_SESSION['nome_fantasquadra'] = $user['nome_fantasquadra'] ?? '';
                    $_SESSION['login_time']        = $now;
                    $_SESSION['last_activity']     = $now;
                    $_SESSION['last_regenerate']   = $now;
                    $_SESSION['ua_hash']           = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
                    $_SESSION['csrf_token']        = bin2hex(random_bytes(32));

                    // Redirect
                    $dest = (string)($_SESSION['redirect_after_login'] ?? '');
                    unset($_SESSION['redirect_after_login']);
                    if ($dest === ''
                        || $dest[0] !== '/'
                        || (strlen($dest) > 1 && $dest[1] === '/')) {
                        $dest = BASE_PATH . 'app/dashboard.php';
                    }
                    header('Location: ' . $dest, true, 302);
                    exit;

                } else {
                    lrl_record_failure($db, $ip_hash, $username);
                    $_SESSION['_login_fails'] = ((int)($_SESSION['_login_fails'] ?? 0)) + 1;
                    if ((int)$_SESSION['_login_fails'] >= 5) {
                        $_SESSION['_login_lock_until'] = time() + 900;
                        $error = 'Troppi tentativi falliti. Riprova tra 15 minuti.';
                    } else {
                        $error = 'Username o password non corretti.';
                    }
                }

            } catch (\PDOException $e) {
                error_log('[FS login] ' . $e->getMessage());
                $error = 'Errore di sistema. Riprova tra qualche minuto.';
            }
            } // chiude il ramo "IP non bloccato dal rate limit"
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it" data-theme="dark" data-zone="app">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Accedi · FantaScarrupat</title>
  <meta name="robots" content="noindex, nofollow">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= BASE_PATH ?>assets/img/favicon.png">

  <!-- Font self-hosted (GDPR: nessuna richiesta a Google) -->
  <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/material-symbols.css">

  <!-- Design system (contiene anche il font import self-hosted) -->
  <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/main.css">

  <!-- Theme applicato subito per evitare flash -->
  <script src="<?= BASE_PATH ?>assets/js/theme.js"></script>

  <style>
    /* ── Pagina login — layout centrato su sfondo dark ──────────── */
    html, body {
      height: 100%;
      margin: 0;
      background: var(--c-bg);
      color: var(--c-text);
      font-family: var(--font-body);
    }

    .lp-wrap {
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: var(--sp-6);
    }

    .lp-card {
      width: 100%;
      max-width: 420px;
    }

    /* Wordmark */
    .lp-brand {
      text-align: center;
      margin-bottom: var(--sp-10);
    }
    .lp-brand__name {
      display: block;
      font-family: var(--font-heading);
      font-size: 2.25rem;
      font-weight: 900;
      letter-spacing: -0.02em;
      color: var(--c-text);
      margin-bottom: var(--sp-2);
      line-height: 1;
    }
    .lp-brand__name em {
      font-style: normal;
      color: var(--c-accent);
    }
    .lp-brand__sub {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--c-text-muted);
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    /* Form */
    .lp-form {
      display: flex;
      flex-direction: column;
      gap: var(--sp-5);
    }

    .lp-field {
      display: flex;
      flex-direction: column;
      gap: var(--sp-2);
    }
    .lp-field label {
      font-size: 0.8125rem;
      font-weight: 600;
      color: var(--c-text-muted);
      letter-spacing: 0.03em;
    }
    .lp-field input {
      background: var(--c-bg-card);
      border: 1px solid var(--c-border);
      border-radius: var(--r-md);
      padding: 10px 14px;
      font-size: 0.9375rem;
      font-family: var(--font-body);
      color: var(--c-text);
      width: 100%;
      box-sizing: border-box;
      outline: none;
      transition: border-color 180ms var(--ease-out),
                  box-shadow   180ms var(--ease-out);
    }
    .lp-field input:focus {
      border-color: var(--c-accent);
      box-shadow: 0 0 0 3px oklch(78% 0.120 215 / 0.15);
    }
    .lp-field input::placeholder { color: var(--c-text-faint); }
    .lp-field input:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Password toggle */
    .lp-pw {
      position: relative;
    }
    .lp-pw input { padding-right: 44px; }
    .lp-pw-btn {
      position: absolute;
      right: 0; top: 0; bottom: 0;
      width: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
      border: none;
      color: var(--c-text-faint);
      cursor: pointer;
      transition: color 150ms;
      border-radius: 0 var(--r-md) var(--r-md) 0;
    }
    .lp-pw-btn:hover { color: var(--c-text-muted); }
    .lp-pw-btn .material-symbols-rounded { font-size: 18px; }

    /* Alert */
    .lp-alert {
      display: flex;
      gap: var(--sp-3);
      align-items: flex-start;
      padding: var(--sp-3) var(--sp-4);
      border-radius: var(--r-md);
      font-size: 0.875rem;
      line-height: 1.45;
    }
    .lp-alert--error {
      background: oklch(72% 0.170 25 / 0.12);
      border: 1px solid oklch(72% 0.170 25 / 0.25);
      color: var(--c-danger);
    }
    .lp-alert--info {
      background: oklch(78% 0.120 215 / 0.10);
      border: 1px solid oklch(78% 0.120 215 / 0.22);
      color: var(--c-accent);
    }
    .lp-alert .material-symbols-rounded {
      font-size: 18px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    /* Submit */
    .lp-submit {
      width: 100%;
      padding: 11px 20px;
      background: var(--c-accent);
      color: oklch(99% 0.005 255);
      border: none;
      border-radius: var(--r-md);
      font-family: var(--font-body);
      font-size: 0.9375rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: var(--sp-2);
      transition: opacity 150ms;
      margin-top: var(--sp-2);
    }
    .lp-submit:hover:not(:disabled) { opacity: 0.88; }
    .lp-submit:disabled { opacity: 0.40; cursor: not-allowed; }
    .lp-submit .material-symbols-rounded { font-size: 18px; }

    /* Link torna al sito */
    .lp-back {
      display: block;
      text-align: center;
      margin-top: var(--sp-8);
      font-size: 0.8125rem;
      color: var(--c-text-muted);
      text-decoration: none;
      transition: color 150ms;
    }
    .lp-back:hover { color: var(--c-text); }

    @media (max-width: 480px) {
      .lp-wrap {
        align-items: flex-start;
        padding-top: clamp(3rem, 12vw, 5rem);
      }
    }
  </style>
</head>
<body>

<div class="lp-wrap">
  <div class="lp-card">

    <!-- Wordmark -->
    <div class="lp-brand">
      <span class="lp-brand__name">Fanta<em>Scarrupat</em></span>
      <span class="lp-brand__sub">Area riservata</span>
    </div>

    <!-- Alert rate limit -->
    <?php if ($rate_locked): ?>
    <div class="lp-alert lp-alert--error" style="margin-bottom:var(--sp-5)">
      <span class="material-symbols-rounded" style="font-variation-settings:'FILL' 1">lock</span>
      <span>
        Troppi tentativi falliti. Attendi ancora
        <strong><?= $rate_remaining ?> minut<?= $rate_remaining === 1 ? 'o' : 'i' ?></strong>.
      </span>
    </div>
    <?php elseif ($error !== ''): ?>
    <div class="lp-alert lp-alert--error" style="margin-bottom:var(--sp-5)">
      <span class="material-symbols-rounded" style="font-variation-settings:'FILL' 1">error</span>
      <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php elseif ($flash !== ''): ?>
    <div class="lp-alert lp-alert--info" style="margin-bottom:var(--sp-5)">
      <span class="material-symbols-rounded">info</span>
      <span><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="post"
          action="<?= htmlspecialchars(BASE_PATH . 'login.php', ENT_QUOTES, 'UTF-8') ?>"
          class="lp-form"
          autocomplete="on"
          novalidate>
      <input type="hidden" name="csrf_token"
             value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="lp-field">
        <label for="lf-username">Username</label>
        <input id="lf-username"
               name="username"
               type="text"
               autocomplete="username"
               autofocus
               required
               <?= $rate_locked ? 'disabled' : '' ?>
               value="<?= htmlspecialchars((string)($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="lp-field">
        <label for="lf-password">Password</label>
        <div class="lp-pw">
          <input id="lf-password"
                 name="password"
                 type="password"
                 autocomplete="current-password"
                 required
                 <?= $rate_locked ? 'disabled' : '' ?>>
          <button type="button"
                  class="lp-pw-btn"
                  onclick="lpTogglePw()"
                  aria-label="Mostra o nascondi password">
            <span class="material-symbols-rounded" id="lp-pw-icon">visibility</span>
          </button>
        </div>
      </div>

      <button type="submit"
              class="lp-submit"
              id="lp-submit"
              <?= $rate_locked ? 'disabled' : '' ?>>
        <span class="material-symbols-rounded"
              style="font-variation-settings:'FILL' 1">login</span>
        Accedi
      </button>
    </form>

    <a href="<?= htmlspecialchars(BASE_PATH . 'public/index.php', ENT_QUOTES, 'UTF-8') ?>"
       class="lp-back">
      ← Torna al sito pubblico
    </a>

  </div>
</div>

<script>
function lpTogglePw() {
  var inp  = document.getElementById('lf-password');
  var icon = document.getElementById('lp-pw-icon');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    inp.type = 'password';
    icon.textContent = 'visibility';
  }
}

// Disabilita submit durante l'invio per evitare doppio click
document.querySelector('.lp-form').addEventListener('submit', function () {
  var btn = document.getElementById('lp-submit');
  if (btn) btn.disabled = true;
});
</script>

</body>
</html>
