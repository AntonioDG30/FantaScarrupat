<?php
/**
 * app/profilo.php — Account e preferenze utente
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_layout.php';

$db      = getDB();
$user_id = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

// ── Leggi dati utente ─────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT username, nome_fantasquadra, theme_preference, created_at
      FROM users
     WHERE id_user = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_PATH . 'login.php');
    exit;
}

// ── POST: cambio password ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        $errors[] = 'Token di sicurezza non valido. Ricarica la pagina e riprova.';
    } else {
        if ($_POST['action'] === 'change_password') {
            $pw_current = $_POST['pw_current'] ?? '';
            $pw_new     = $_POST['pw_new']     ?? '';
            $pw_confirm = $_POST['pw_confirm'] ?? '';

            if ($pw_current === '' || $pw_new === '' || $pw_confirm === '') {
                $errors[] = 'Compila tutti i campi della password.';
            } elseif (strlen($pw_new) < 8) {
                $errors[] = 'La nuova password deve avere almeno 8 caratteri.';
            } elseif ($pw_new !== $pw_confirm) {
                $errors[] = 'La nuova password e la conferma non coincidono.';
            } else {
                $stmt2 = $db->prepare("SELECT password_hash FROM users WHERE id_user = ?");
                $stmt2->execute([$user_id]);
                $hash = $stmt2->fetchColumn();

                $valid = password_verify($pw_current, (string)$hash);
                if (!$valid && preg_match('/^[a-f0-9]{64}$/i', (string)$hash)) {
                    $valid = hash_equals($hash, hash('sha256', $pw_current));
                }

                if (!$valid) {
                    $errors[] = 'La password attuale non è corretta.';
                } elseif ($pw_current === $pw_new) {
                    $errors[] = 'La nuova password deve essere diversa da quella attuale.';
                } else {
                    $db->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?")
                       ->execute([password_hash($pw_new, PASSWORD_BCRYPT, ['cost' => 12]), $user_id]);
                    $success = 'Password aggiornata con successo.';
                }
            }
        }
    }
}

// Data registrazione leggibile
$created_at = '';
if (!empty($user['created_at'])) {
    $created_at = (new \DateTime($user['created_at']))->format('d/m/Y');
}

$theme_pref = $user['theme_preference'] ?? 'auto';

/* ── Parametri ruota della fortuna — DISATTIVATO (sistema parametri rimosso) ──
   Sezione commentata, non eliminata, su richiesta. Per riattivarla togliere i
   commenti qui e nel markup più in basso.

$stmt_par = $db->prepare("
    SELECT pr.numero_parametro, pr.testo_parametro, upa.created_at AS assegnato_il
      FROM user_parametro_assegnato upa
      JOIN parametri_rosa pr ON pr.id_parametro = upa.id_parametro
     WHERE upa.id_user = ?
     ORDER BY upa.created_at DESC
");
$stmt_par->execute([$user_id]);
$parametri_assegnati = $stmt_par->fetchAll(\PDO::FETCH_ASSOC);
*/

app_layout_start('Profilo', 'profilo');
?>

<!-- ── Hero profilo ───────────────────────────────────────────── -->
<div class="profile-hero">
  <div class="profile-hero__avatar" aria-hidden="true">
    <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 2))) ?>
  </div>
  <div class="profile-hero__info">
    <h1 class="profile-hero__name"><?= htmlspecialchars($user['username']) ?></h1>
    <div class="profile-hero__badges">
      <?php if ($_SESSION['flag_admin'] ?? false): ?>
        <span class="pill pill--gold">Amministratore</span>
      <?php else: ?>
        <span class="pill">Partecipante</span>
      <?php endif; ?>
      <?php if (!empty($user['nome_fantasquadra'])): ?>
        <span class="profile-hero__meta">
          <span class="material-symbols-rounded" aria-hidden="true">shield</span>
          <?= htmlspecialchars($user['nome_fantasquadra']) ?>
        </span>
      <?php endif; ?>
      <?php if ($created_at): ?>
        <span class="profile-hero__meta">
          <span class="material-symbols-rounded" aria-hidden="true">calendar_today</span>
          Membro dal <?= htmlspecialchars($created_at) ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── Griglia 2 colonne ──────────────────────────────────────── -->
<div class="grid-2 mb-6">

  <!-- Informazioni account -->
  <div class="card">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">account_circle</span>
      <div class="card__title">Account</div>
    </div>
    <div class="card__body">
      <div class="col" style="gap:var(--sp-4)">
        <div>
          <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--c-text-muted);margin-bottom:var(--sp-2)">Username</div>
          <div style="display:flex;gap:var(--sp-2);align-items:center">
            <div id="username-display"
                 style="flex:1;padding:var(--sp-3) var(--sp-4);background:var(--c-bg-alt);border:1px solid var(--c-border);border-radius:var(--r-md);font-size:.9375rem">
              <?= htmlspecialchars($user['username']) ?>
            </div>
            <button type="button" id="username-edit-btn"
                    class="btn btn-sm btn-outline"
                    style="flex-shrink:0;display:inline-flex;align-items:center;gap:var(--sp-1)"
                    aria-label="Modifica username">
              <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">edit</span>
              Modifica
            </button>
          </div>
          <!-- Edit form (hidden by default) -->
          <form id="username-form" style="display:none;margin-top:var(--sp-3)" autocomplete="off" novalidate>
            <div style="display:flex;gap:var(--sp-2);align-items:center">
              <input type="text" id="username-input"
                     value="<?= htmlspecialchars($user['username']) ?>"
                     maxlength="40" minlength="3"
                     pattern="[a-zA-Z0-9_\-\.]+"
                     required
                     style="flex:1;padding:var(--sp-3) var(--sp-4);background:var(--c-bg-alt);
                            border:1px solid var(--c-accent);border-radius:var(--r-md);
                            color:var(--c-text);font-size:.9375rem;outline:none;"
                     aria-label="Nuovo username">
              <button type="submit" id="username-save-btn"
                      class="btn btn-sm btn-primary"
                      style="flex-shrink:0;display:inline-flex;align-items:center;gap:var(--sp-1)">
                <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">check</span>
                Salva
              </button>
              <button type="button" id="username-cancel-btn"
                      class="btn btn-sm btn-outline"
                      style="flex-shrink:0">
                Annulla
              </button>
            </div>
            <div id="username-msg"
                 style="margin-top:var(--sp-2);font-size:.8125rem;min-height:1.2em"
                 aria-live="polite"></div>
          </form>
        </div>
        <div>
          <div style="font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--c-text-muted);margin-bottom:var(--sp-2)">Fantasquadra</div>
          <div style="padding:var(--sp-3) var(--sp-4);background:var(--c-bg-alt);border:1px solid var(--c-border);border-radius:var(--r-md);font-size:.9375rem">
            <?= htmlspecialchars($user['nome_fantasquadra'] ?? '-') ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sicurezza: cambio password -->
  <div class="card">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">lock</span>
      <div class="card__title">Sicurezza</div>
    </div>
    <div class="card__body">

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
          <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0" aria-hidden="true">error</span>
          <span><?= htmlspecialchars($errors[0]) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
          <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0" aria-hidden="true">check_circle</span>
          <span><?= htmlspecialchars($success) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" id="pw-form" autocomplete="off" novalidate>
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="col" style="gap:var(--sp-4)">
          <?php
          $pw_fields = [
              ['id' => 'pw_current', 'label' => 'Password attuale',       'ac' => 'current-password', 'hint' => ''],
              ['id' => 'pw_new',     'label' => 'Nuova password',          'ac' => 'new-password',     'hint' => 'Minimo 8 caratteri'],
              ['id' => 'pw_confirm', 'label' => 'Conferma nuova password', 'ac' => 'new-password',     'hint' => ''],
          ];
          foreach ($pw_fields as $f): ?>
          <div>
            <label for="<?= $f['id'] ?>"
                   style="display:block;font-size:.8125rem;font-weight:500;color:var(--c-text-muted);margin-bottom:var(--sp-2)">
              <?= $f['label'] ?>
              <?php if ($f['hint']): ?>
                <span style="font-weight:400;color:var(--c-text-dim)"> (<?= $f['hint'] ?>)</span>
              <?php endif; ?>
            </label>
            <div style="position:relative">
              <input type="password"
                     id="<?= $f['id'] ?>"
                     name="<?= $f['id'] ?>"
                     autocomplete="<?= $f['ac'] ?>"
                     required
                     <?= $f['id'] === 'pw_new' ? 'minlength="8"' : '' ?>
                     style="width:100%;padding:var(--sp-3) var(--sp-10) var(--sp-3) var(--sp-4);
                            background:var(--c-bg-alt);border:1px solid var(--c-border);
                            border-radius:var(--r-md);color:var(--c-text);font-size:.9375rem;
                            outline:none;transition:border-color var(--dur-fast) var(--ease-out),
                            box-shadow var(--dur-fast) var(--ease-out);"
                     onfocus="this.style.borderColor='var(--c-accent)';this.style.boxShadow='0 0 0 3px var(--c-accent-dim)'"
                     onblur="this.style.borderColor='';this.style.boxShadow=''">
              <button type="button" tabindex="-1"
                      onclick="togglePw('<?= $f['id'] ?>',this)"
                      style="position:absolute;right:var(--sp-3);top:50%;transform:translateY(-50%);
                             background:none;border:none;cursor:pointer;color:var(--c-text-dim);
                             display:flex;align-items:center;padding:var(--sp-1);"
                      aria-label="Mostra/nascondi password">
                <span class="material-symbols-rounded" style="font-size:20px" aria-hidden="true">visibility</span>
              </button>
            </div>
          </div>
          <?php endforeach; ?>

          <div style="padding-top:var(--sp-2)">
            <button type="submit" id="pw-submit"
                    class="btn btn-md btn-primary"
                    style="display:inline-flex;align-items:center;gap:var(--sp-2)">
              <span class="material-symbols-rounded" style="font-size:18px" aria-hidden="true">lock</span>
              Aggiorna password
            </button>
          </div>
        </div>
      </form>

    </div>
  </div>

</div>

<!-- ── Preferenze tema ─────────────────────────────────────────── -->
<div class="card mb-6">
  <div class="card__header">
    <span class="material-symbols-rounded td-muted" aria-hidden="true">palette</span>
    <div class="card__title">Tema interfaccia</div>
  </div>
  <div class="card__body">
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:var(--sp-3)"
         id="theme-options"
         role="radiogroup"
         aria-label="Scegli il tema">
      <?php foreach ([
          ['value' => 'dark',  'label' => 'Scuro',     'icon' => 'dark_mode',       'desc' => 'Per la sera'],
          ['value' => 'light', 'label' => 'Chiaro',    'icon' => 'light_mode',      'desc' => 'Pieno giorno'],
          ['value' => 'auto',  'label' => 'Automatico','icon' => 'brightness_auto', 'desc' => 'Segue il sistema'],
      ] as $opt): ?>
      <label class="quick-item"
             id="theme-label-<?= $opt['value'] ?>"
             style="flex-direction:column;align-items:flex-start;cursor:pointer;
                    border-color:<?= $theme_pref === $opt['value'] ? 'var(--c-accent)' : 'var(--c-border)' ?>;
                    background:<?= $theme_pref === $opt['value'] ? 'var(--c-accent-dim)' : 'var(--c-bg-raised)' ?>">
        <input type="radio"
               name="theme_pref"
               value="<?= $opt['value'] ?>"
               data-action="set-theme"
               <?= $theme_pref === $opt['value'] ? 'checked' : '' ?>
               style="position:absolute;opacity:0;pointer-events:none">
        <span class="material-symbols-rounded"
              style="font-size:24px;color:<?= $theme_pref === $opt['value'] ? 'var(--c-accent)' : 'var(--c-text-muted)' ?>"
              aria-hidden="true"><?= $opt['icon'] ?></span>
        <div style="margin-top:var(--sp-2)">
          <div class="quick-item__label"><?= $opt['label'] ?></div>
          <div class="quick-item__desc"><?= $opt['desc'] ?></div>
        </div>
      </label>
      <?php endforeach; ?>
    </div>

    <div id="theme-save-msg"
         style="margin-top:var(--sp-4);font-size:.8125rem;color:var(--c-success);
                opacity:0;transition:opacity var(--dur-fast) var(--ease-out);"
         aria-live="polite">
      Preferenza salvata.
    </div>
  </div>
</div>

<?php /* ── Parametri ruota della fortuna — SEZIONE COMMENTATA (non eliminata) ──────
<div class="card mb-6">
  <div class="card__header">
    <span class="material-symbols-rounded td-muted" aria-hidden="true">casino</span>
    <div>
      <div class="card__title">Parametri ruota della fortuna</div>
      <div class="card__sub">I criteri di analisi assegnati tramite la ruota</div>
    </div>
  </div>
  <div class="card__body">
    <?php if (empty($parametri_assegnati)): ?>
      <div style="text-align:center;padding:var(--sp-6) var(--sp-4);color:var(--c-text-muted)">
        <span class="material-symbols-rounded" style="font-size:36px;display:block;margin-bottom:var(--sp-3);opacity:.4" aria-hidden="true">casino</span>
        Nessun parametro assegnato. Gira la ruota della fortuna per scoprire i tuoi criteri!
      </div>
    <?php else: ?>
      <div class="col" style="gap:var(--sp-3)">
        <?php foreach ($parametri_assegnati as $par): ?>
          <div style="display:flex;align-items:center;gap:var(--sp-4);padding:var(--sp-3) var(--sp-4);
                      background:var(--c-bg-alt);border:1px solid var(--c-border);border-radius:var(--r-md)">
            <div style="flex-shrink:0;width:32px;height:32px;border-radius:50%;
                        background:oklch(from var(--c-accent) l c h / .12);
                        display:flex;align-items:center;justify-content:center;
                        font-family:var(--font-heading);font-weight:700;font-size:.875rem;color:var(--c-accent)">
              <?= (int)$par['numero_parametro'] ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:.9375rem;font-weight:500;color:var(--c-text)">
                <?= htmlspecialchars($par['testo_parametro']) ?>
              </div>
            </div>
            <?php if (!empty($par['assegnato_il'])): ?>
              <div style="flex-shrink:0;font-size:.75rem;color:var(--c-text-faint)">
                <?= (new \DateTime($par['assegnato_il']))->format('d/m/Y') ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
── fine sezione commentata ── */ ?>

<!-- ── Zona pericolosa ────────────────────────────────────────── -->
<div class="card" style="border-color:var(--c-danger-dim)">
  <div class="card__header" style="border-color:var(--c-danger-dim)">
    <span class="material-symbols-rounded" style="color:var(--c-danger)" aria-hidden="true">warning</span>
    <div>
      <div class="card__title" style="color:var(--c-danger)">Zona pericolosa</div>
      <div class="card__sub">Azioni irreversibili</div>
    </div>
  </div>
  <div class="card__body">
    <div class="row" style="justify-content:space-between;flex-wrap:wrap;gap:var(--sp-4)">
      <div>
        <div style="font-weight:600;font-size:.9375rem">Esci da tutti i dispositivi</div>
        <div class="td-muted" style="font-size:.8125rem;margin-top:2px">Termina tutte le sessioni attive. Dovrai effettuare di nuovo il login.</div>
      </div>
      <a href="<?= BASE_PATH ?>auth/logout.php?all=1"
         class="btn btn-sm btn-outline"
         onclick="return confirm('Esci da tutti i dispositivi?')">
        Esci ovunque
      </a>
    </div>
  </div>
</div>

<script>
/* ── Username edit ───────────────────────────────────────────── */
(function () {
  var display    = document.getElementById('username-display');
  var form       = document.getElementById('username-form');
  var input      = document.getElementById('username-input');
  var editBtn    = document.getElementById('username-edit-btn');
  var cancelBtn  = document.getElementById('username-cancel-btn');
  var saveBtn    = document.getElementById('username-save-btn');
  var msg        = document.getElementById('username-msg');

  if (!display || !form) return;

  editBtn.addEventListener('click', function () {
    form.style.display = 'block';
    editBtn.style.display = 'none';
    input.focus();
    input.select();
  });

  cancelBtn.addEventListener('click', function () {
    form.style.display = 'none';
    editBtn.style.display = '';
    msg.textContent = '';
    input.value = display.textContent.trim();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var newName = input.value.trim();
    if (!newName) return;

    saveBtn.disabled = true;
    msg.style.color = 'var(--c-text-muted)';
    msg.textContent = 'Salvataggio…';

    var fd = new FormData();
    fd.append('action', 'update_username');
    fd.append('username', newName);
    fd.append('csrf_token', window.CSRF_TOKEN || '');

    fetch(window.BASE_PATH + 'api/profilo.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: fd,
    }).then(function (r) { return r.json(); })
    .then(function (data) {
      if (data && data.success) {
        display.textContent = data.username;
        // Aggiorna anche il nome nell'header se presente
        var navUser = document.getElementById('nav-username');
        if (navUser) navUser.textContent = data.username;
        form.style.display = 'none';
        editBtn.style.display = '';
        msg.style.color = 'var(--c-success)';
        msg.textContent = 'Username aggiornato.';
        setTimeout(function () { msg.textContent = ''; }, 3000);
      } else {
        msg.style.color = 'var(--c-danger)';
        msg.textContent = (data && data.error) ? data.error : 'Errore sconosciuto.';
        saveBtn.disabled = false;
      }
    }).catch(function () {
      msg.style.color = 'var(--c-danger)';
      msg.textContent = 'Errore di rete.';
      saveBtn.disabled = false;
    });
  });
}());

/* ── Toggle show/hide password ───────────────────────────────── */
function togglePw(id, btn) {
  var input = document.getElementById(id);
  var icon  = btn.querySelector('.material-symbols-rounded');
  input.type = (input.type === 'password') ? 'text' : 'password';
  icon.textContent = (input.type === 'text') ? 'visibility_off' : 'visibility';
}

/* ── Prevent double-submit ───────────────────────────────────── */
document.getElementById('pw-form').addEventListener('submit', function () {
  var btn = document.getElementById('pw-submit');
  btn.disabled = true;
  btn.style.opacity = '.55';
});

/* ── Theme picker: visual update + save to DB ────────────────── */
(function () {
  var group    = document.getElementById('theme-options');
  var msg      = document.getElementById('theme-save-msg');
  var hideTimer;

  group.addEventListener('change', function (e) {
    var radio = e.target.closest('[data-action="set-theme"]');
    if (!radio) return;

    // Aggiorna stile dei label
    group.querySelectorAll('label').forEach(function (lbl) {
      var isActive = lbl.querySelector('input').value === radio.value;
      lbl.style.borderColor = isActive ? 'var(--c-accent)' : 'var(--c-border)';
      lbl.style.background  = isActive ? 'var(--c-accent-dim)' : 'var(--c-bg-raised)';
      var icon = lbl.querySelector('.material-symbols-rounded');
      if (icon) icon.style.color = isActive ? 'var(--c-accent)' : 'var(--c-text-muted)';
    });

    // Salva nel DB
    var fd = new FormData();
    fd.append('action', 'set_theme');
    fd.append('theme',  radio.value);
    fd.append('csrf_token', window.CSRF_TOKEN || '');

    fetch(window.BASE_PATH + 'api/profilo.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: fd,
    }).then(function (res) {
      return res.ok ? res.json() : Promise.reject();
    }).then(function (data) {
      if (data && data.success) {
        msg.style.opacity = '1';
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () { msg.style.opacity = '0'; }, 2500);
      }
    }).catch(function () {});
  });
}());
</script>

<?php app_layout_end(); ?>
