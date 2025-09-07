<?php
declare(strict_types=1);

// Avvio la sessione il prima possibile
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/path_helper.php';

/**
 * Forzo logout solo su GET con ?expired=1,
 * poi ripulisco l'URL e mostro il messaggio tramite flash in sessione.
 */
$isForceLogout = ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['expired']) && $_GET['expired'] === '1');

if ($isForceLogout) {
    // Distruggo l'eventuale sessione esistente
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();

    // Nuova sessione pulita per flash + CSRF
    session_start();

    // Messaggio
    if (isset($_GET['reason']) && $_GET['reason'] === 'session_max') {
        $_SESSION['flash_error'] = 'La tua sessione è scaduta dopo 2 ore. Effettua nuovamente il login.';
    } else {
        $_SESSION['flash_error'] = 'La tua sessione è scaduta per inattività. Effettua nuovamente il login.';
    }

    // Redirect alla stessa pagina senza querystring
    $cleanPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    header('Location: ' . $cleanPath);
    exit;
}

// Se l'utente è già loggato, vado alla home
if (isset($_SESSION['user_id'])) {
    redirect('HomeParametri.php');
    exit;
}

// Messaggi
$error_message = '';
$success_message = '';

// Flash error da eventuale redirect precedente
if (isset($_SESSION['flash_error'])) {
    $error_message = (string)$_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Gestione error=session_invalid (opzionale ripulire URL, qui lo mostro direttamente)
if (isset($_GET['error']) && $_GET['error'] === 'session_invalid') {
    $extra = 'Sessione non valida. Per sicurezza, effettua nuovamente il login.';
    $error_message = $error_message ? ($error_message . ' ' . $extra) : $extra;
}

/**
 * CSRF token
 */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Submit del form login
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error_message = 'Token di sicurezza non valido.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error_message = 'Username e password sono obbligatori.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );

                $stmt = $pdo->prepare("SELECT id_user, username, password_hash, flag_admin, nome_fantasquadra FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user) {
                    $password_valid = false;

                    if (password_verify($password, $user['password_hash'])) {
                        $password_valid = true;
                    } else {
                        // Fallback SHA-256 legacy
                        if (preg_match('/^[a-f0-9]{64}$/i', $user['password_hash'])) {
                            $sha256_hash = hash('sha256', $password);
                            if (hash_equals($user['password_hash'], $sha256_hash)) {
                                $password_valid = true;
                                // Upgrade a bcrypt/argon2
                                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                                $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?");
                                $update_stmt->execute([$new_hash, $user['id_user']]);
                            }
                        }
                    }

                    if ($password_valid) {
                        // Rigenero ID sessione
                        session_regenerate_id(true);

                        // Aggiorno timestamp utente
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_activity = NOW() WHERE id_user = ?");
                        $stmt->execute([$user['id_user']]);

                        // Variabili di sessione
                        $now = time();
                        $_SESSION['user_id'] = (int)$user['id_user'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['is_admin'] = (bool)$user['flag_admin'];
                        $_SESSION['nome_fantasquadra'] = $user['nome_fantasquadra'];

                        // Metadati sicurezza
                        $_SESSION['login_time'] = $now;
                        $_SESSION['last_activity'] = $now;
                        $_SESSION['last_regenerate'] = $now;
                        $_SESSION['ua_hash'] = md5($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

                        // Log attività (se tabella esiste)
                        try {
                            $check = $pdo->query("SHOW TABLES LIKE 'user_activities'");
                            if ($check && $check->rowCount() > 0) {
                                $ins = $pdo->prepare("INSERT INTO user_activities (id_user, activity_type, description) VALUES (?, 'login', 'Login effettuato')");
                                $ins->execute([$user['id_user']]);
                            }
                        } catch (PDOException $e) {
                            error_log("Activity log error: " . $e->getMessage());
                        }

                        // Redirect post-login
                        $redirect_url = $_SESSION['redirect_after_login'] ?? url('HomeParametri.php');
                        unset($_SESSION['redirect_after_login']);
                        header("Location: $redirect_url");
                        exit;
                    } else {
                        $error_message = 'Username o password non corretti.';
                    }
                } else {
                    $error_message = 'Username o password non corretti.';
                }
            } catch (PDOException $e) {
                error_log("Login database error: " . $e->getMessage());
                $error_message = 'Errore di sistema. Riprova più tardi.';
            }
        }
    }
}

/**
 * Statistiche per brand section
 */
$system_stats = [
    'total_users' => 0,
    'total_searches_today' => 0,
    'held_competitions' => 0,
    'total_players' => 0
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Utenti totali
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $system_stats['total_users'] = (int)$stmt->fetchColumn();

    // Ricerche oggi (se tabella esiste)
    $check = $pdo->query("SHOW TABLES LIKE 'user_activities'");
    if ($check && $check->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activities WHERE activity_type = 'search' AND DATE(created_at) = CURDATE()");
        $stmt->execute();
        $system_stats['total_searches_today'] = (int)$stmt->fetchColumn();
    }

    // Competizioni disputate
    $stmt = $pdo->query("SELECT COUNT(*) FROM competizione_disputata");
    $system_stats['held_competitions'] = (int)$stmt->fetchColumn();

    // Giocatori (DISTINCT su codice_fantacalcio)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT codice_fantacalcio) AS total_players FROM giocatore");
    $system_stats['total_players'] = (int)$stmt->fetchColumn();

} catch (PDOException $e) {
    error_log("Login stats error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FantaScarrupat Analyzer</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Theme & Page CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Theme Toggle -->
        <button class="theme-toggle" id="themeToggle" title="Cambia tema" aria-label="Cambia tema">
            <span class="material-icons" id="themeIcon">dark_mode</span>
        </button>

        <div class="login-content">
            <!-- Brand Section -->
            <div class="brand-section fade-in">
                <div class="brand-logo floating">
                    <span>⚽</span>
                    <span>FantaScarrupat Analyzer</span>
                </div>
                <div class="brand-tagline">
                    Il sistema più avanzato per l'analisi fantacalcio.<br>
                    Oltre 30 criteri di ricerca, filtri intelligenti e statistiche dettagliate.
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-value" data-count="<?= (int)$system_stats['total_users'] ?>"><?= (int)$system_stats['total_users'] ?></span>
                        <div class="stat-label">Utenti</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value" data-count="<?= (int)$system_stats['total_searches_today'] ?>"><?= (int)$system_stats['total_searches_today'] ?></span>
                        <div class="stat-label">Ricerche Oggi</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value" data-count="<?= (int)$system_stats['held_competitions'] ?>"><?= (int)$system_stats['held_competitions'] ?></span>
                        <div class="stat-label">Competizioni Disputate</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value" data-count="<?= (int)$system_stats['total_players'] ?>"><?= (int)$system_stats['total_players'] ?></span>
                        <div class="stat-label">Giocatori</div>
                    </div>
                </div>
            </div>

            <!-- Login Form -->
            <div class="login-form-section slide-up">
                <div class="form-header">
                    <h1 class="form-title">Bentornato!</h1>
                    <p class="form-subtitle">Accedi al tuo account per continuare</p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <span class="material-icons" style="font-size: 1.2rem;">error</span>
                        <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <span class="material-icons" style="font-size: 1.2rem;">check_circle</span>
                        <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), ENT_QUOTES, 'UTF-8') ?>" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <div class="form-group">
                        <label for="username" class="form-label">
                            <span class="material-icons" style="font-size: 1.1rem;">person</span>
                            Username
                        </label>
                        <input type="text"
                               class="form-control"
                               id="username"
                               name="username"
                               placeholder="Inserisci il tuo username"
                               required
                               autofocus
                               autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <span class="material-icons" style="font-size: 1.1rem;">lock</span>
                            Password
                        </label>
                        <div class="password-field">
                            <input type="password"
                                   class="form-control"
                                   id="password"
                                   name="password"
                                   placeholder="Inserisci la tua password"
                                   required
                                   autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <span class="material-icons" id="passwordToggleIcon">visibility</span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="material-icons" id="loginIcon">login</span>
                        Accedi
                    </button>

                    <a href="<?= url('index.php') ?>" class="btn-back-to-site">
                        <span class="material-icons">arrow_back</span>
                        Torna al sito
                    </a>

                </form>

                <div class="form-footer">
                    <p>Accedendo accetti i nostri termini di servizio e la privacy policy.</p>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
