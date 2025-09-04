<?php
declare(strict_types=1);

/**
 * Cosa faccio: configuro la pagina profilo con protezione autenticazione.
 * Perché: voglio permettere agli utenti di gestire i propri dati e preferenze.
 * Dipendenze: require_login, config, userData per profilo corrente.
 */
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/find_userData.php';
require_once __DIR__ . '/src/Utils/Repository.php';

/**
 * Cosa faccio: rigenero CSRF token se non esiste per sicurezza form.
 * Perché: voglio proteggere tutti i form da attacchi CSRF.
 */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success_message = '';
$error_message = '';

/**
 * Cosa faccio: gestisco submit dei form di aggiornamento profilo.
 * Input: POST con action specifica (update_profile o change_password)
 * Output: aggiornamento dati + messaggio successo/errore
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /**
     * Cosa faccio: verifico CSRF prima di processare qualsiasi form.
     * Perché: voglio garantire che il submit venga dalla mia pagina.
     */
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Token di sicurezza non valido.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            /**
             * Cosa faccio: gestisco aggiornamento USERNAME e preferenze.
             * Nota: nome_fantasquadra NON è editabile da questa sezione.
             * Validazione: controllo lunghezza, caratteri e unicità username.
             */
            $new_username = trim($_POST['username'] ?? '');
            $theme_preference = $_POST['theme_preference'] ?? 'auto';
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            
            if (empty($new_username)) {
                $error_message = 'Il username è obbligatorio.';
            } elseif (strlen($new_username) < 3) {
                $error_message = 'Il username deve essere di almeno 3 caratteri.';
            } elseif (strlen($new_username) > 50) {
                $error_message = 'Il username non può superare i 50 caratteri.';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $new_username)) {
                $error_message = 'Il username può contenere solo lettere, numeri, underscore e trattini.';
            } else {
                /**
                 * Cosa faccio: valido tema e aggiorno database.
                 * Logica: controllo unicità username, creo colonne mancanti, aggiorno record.
                 */
                if (!in_array($theme_preference, ['light', 'dark', 'auto'])) {
                    $theme_preference = 'auto';
                }
                
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
                    
                    /**
                     * Cosa faccio: verifico unicità username se diverso da quello attuale.
                     * Perché: voglio evitare duplicati username nel sistema.
                     */
                    if ($new_username !== $_SESSION['username']) {
                        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
                        $stmt->execute([$new_username, $_SESSION['user_id']]);
                        if ($stmt->fetch()) {
                            $error_message = 'Il username è già utilizzato da un altro utente.';
                        }
                    }
                    
                    if (empty($error_message)) {
                        /**
                         * Cosa faccio: creo colonne preferenze se non esistono.
                         * Perché: supporto legacy database che potrebbero non averle.
                         */
                        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'theme_preference'");
                        if ($stmt->rowCount() == 0) {
                            $pdo->exec("ALTER TABLE users ADD COLUMN theme_preference VARCHAR(20) DEFAULT 'auto'");
                        }
                        
                        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_notifications'");
                        if ($stmt->rowCount() == 0) {
                            $pdo->exec("ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) DEFAULT 1");
                        }
                        
                        /**
                         * Cosa faccio: aggiorno username e preferenze utente.
                         * Output: record aggiornato + timestamp last_activity.
                         */
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, theme_preference = ?, email_notifications = ?, last_activity = NOW() WHERE id_user = ?");
                        $stmt->execute([$new_username, $theme_preference, $email_notifications, $_SESSION['user_id']]);
                        
                        // Aggiorno sessione con nuovo username
                        $_SESSION['username'] = $new_username;
                        
                        // Log attività aggiornamento
                        logUserActivity($pdo, $_SESSION['user_id'], 'profile_update', 'Profilo aggiornato');
                        
                        $success_message = 'Profilo aggiornato con successo!';
                    }
                    
                } catch (PDOException $e) {
                    error_log("Profile update database error: " . $e->getMessage());
                    $error_message = 'Errore nell\'aggiornamento del profilo.';
                }
            }
            
        } elseif ($action === 'change_password') {
            /**
             * Cosa faccio: gestisco cambio password con validazione robusta.
             * Logica: verifico password attuale, valido nuova, aggiorno con bcrypt.
             * Sicurezza: supporto legacy SHA256 + upgrade automatico.
             */
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = 'Tutti i campi password sono obbligatori.';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'La nuova password e la conferma non corrispondono.';
            } elseif (strlen($new_password) < 8) {
                $error_message = 'La nuova password deve essere di almeno 8 caratteri.';
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
                    
                    /**
                     * Cosa faccio: verifico password attuale con supporto multi-hash.
                     * Logica: provo bcrypt, poi fallback SHA256 per legacy.
                     */
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id_user = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        $password_valid = false;
                        
                        // Verifico con password_verify prima
                        if (password_verify($current_password, $user['password_hash'])) {
                            $password_valid = true;
                        } else {
                            // Fallback per hash SHA-256 legacy
                            if (preg_match('/^[a-f0-9]{64}$/i', $user['password_hash'])) {
                                $sha256_hash = hash('sha256', $current_password);
                                if (hash_equals($user['password_hash'], $sha256_hash)) {
                                    $password_valid = true;
                                }
                            }
                        }
                        
                        if ($password_valid) {
                            /**
                             * Cosa faccio: aggiorno password con hash bcrypt sicuro.
                             * Sicurezza: rigenero session_id e CSRF token dopo cambio.
                             */
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ?, last_activity = NOW() WHERE id_user = ?");
                            $update_stmt->execute([$new_hash, $_SESSION['user_id']]);
                            
                            // Rigenero ID sessione per sicurezza
                            session_regenerate_id(true);
                            $_SESSION['last_regenerate'] = time();
                            
                            // Rigenero CSRF token
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            
                            // Log attività cambio password
                            logUserActivity($pdo, $_SESSION['user_id'], 'password_change', 'Password cambiata');
                            
                            $success_message = 'Password cambiata con successo!';
                        } else {
                            $error_message = 'La password attuale non è corretta.';
                        }
                    } else {
                        $error_message = 'Utente non trovato.';
                    }
                    
                } catch (PDOException $e) {
                    error_log("Password change database error: " . $e->getMessage());
                    $error_message = 'Errore nel cambio password.';
                }
            }
        }
    }
}

/**
 * Cosa faccio: loggo attività utente se tabella disponibile.
 * Input: pdo, user_id, activity_type, description
 * Output: inserimento record in user_activities (se esiste)
 */
function logUserActivity($pdo, $user_id, $activity_type, $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_activities'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("INSERT INTO user_activities (id_user, activity_type, description) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $activity_type, $description]);
        }
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Cosa faccio: carico dati reali utente e statistiche dal database.
 * Output: user_info con dati aggiornati + user_stats calcolate + parametri ruota.
 * Fallback: dati di esempio se query DB falliscono.
 */
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
    
    // Inizializzo repository con debug abilitato in development
    $debug = defined('DEBUG') && DEBUG === true;
    $repository = new Repository($pdo, $debug);
    
    /**
     * Cosa faccio: carico informazioni utente complete dal database.
     * Input: user_id dalla sessione
     * Output: array con tutti i campi profilo + preferenze + timestamps
     */
    $stmt = $pdo->prepare("
        SELECT 
            username, 
            flag_admin, 
            nome_fantasquadra, 
            created_at, 
            last_activity, 
            last_login,
            theme_preference, 
            email_notifications, 
            total_searches 
        FROM users 
        WHERE id_user = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch();
    
    if (!$user_info) {
        // Fallback se utente non trovato
        $user_info = [
            'username' => $_SESSION['username'],
            'nome_fantasquadra' => $_SESSION['nome_fantasquadra'] ?? '',
            'flag_admin' => $_SESSION['is_admin'] ?? false,
            'created_at' => null,
            'last_activity' => null,
            'last_login' => null,
            'theme_preference' => 'auto',
            'email_notifications' => 1,
            'total_searches' => 0
        ];
    }
    
    /**
     * Cosa faccio: calcolo statistiche utente avanzate usando Repository.
     * Output: array con metriche aggregate (giorni, ricerche, export, etc.)
     */
    $user_stats = $repository->getUserStats($_SESSION['user_id']);
    
    // Carico timestamp ultima attività export
    $last_export = $repository->getLastExportActivity($_SESSION['user_id']);
    $user_stats['last_export'] = $last_export ? date('d/m/Y H:i', strtotime($last_export)) : 'Mai';
    
    /**
     * Cosa faccio: calcolo metriche aggiuntive se utente ha fantasquadra.
     * Input: nome_fantasquadra per query Hall of Fame
     * Output: record personale, peggior risultato, media punti
     */
    if ($user_info['nome_fantasquadra']) {
        $hall_stats = $repository->getHallOfFameStats($user_info['nome_fantasquadra']);
        
        $user_stats['personal_record'] = (float)($hall_stats['max_points'] ?? 0);
        $user_stats['worst_performance'] = (float)($hall_stats['min_points'] ?? 0);
        $user_stats['consistency_score'] = $hall_stats['total_matches'] > 0 ? 
            round((float)($hall_stats['avg_points_per_match'] ?? 0), 2) : 0;
    } else {
        $user_stats['personal_record'] = 0;
        $user_stats['worst_performance'] = 0;
        $user_stats['consistency_score'] = 0;
    }
    
    /**
     * Cosa faccio: calcolo frequenza attività e sessioni basate su timestamps.
     * Logica: giorni attivi / giorni registrazione * 100 per percentuale.
     */
    if ($user_info['created_at'] && $user_info['last_activity']) {
        $days_registered = (new DateTime($user_info['created_at']))->diff(new DateTime())->days;
        $days_active = $user_stats['active_days'];

        $user_stats['activity_frequency'] = $days_registered > 0 ? 
            round(($days_active / $days_registered) * 100) : 0;
        
        /**
         * Cosa faccio: conto sessioni reali basate su log login.
         * Input: user_activities con activity_type = 'login'
         * Output: totale sessioni + sessioni ultimi 30 giorni
         */
        $sessionRow = null;
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) AS sessions_total,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS sessions_30d
                FROM user_activities
                WHERE id_user = ? AND activity_type = 'login'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $sessionRow = $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Sessions count error: ' . $e->getMessage());
            $sessionRow = ['sessions_total' => 0, 'sessions_30d' => 0];
        }

        $user_stats['sessions_total'] = (int)($sessionRow['sessions_total'] ?? 0);
        $user_stats['sessions_30d']   = (int)($sessionRow['sessions_30d'] ?? 0);
    } else {
        $user_stats['sessions_total'] = 1;
        $user_stats['activity_frequency'] = 0;
    }
    
    /**
     * Cosa faccio: stimo utilizzo dati basato su ricerche e partite.
     * Algoritmo: 0.1MB per ricerca + 0.05MB per partita visualizzata.
     */
    $base_usage = $user_stats['total_searches'] * 0.1;
    $match_usage = $user_stats['total_matches_played'] * 0.05;
    $user_stats['estimated_data_usage_mb'] = round($base_usage + $match_usage, 1);

    /**
     * Cosa faccio: carico parametri assegnati dalla ruota della fortuna.
     * Input: user_parametro_assegnato con join su parametri_rosa
     * Output: array con parametri e timestamp assegnazione
     */
    $user_parametri_assegnati = [];
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_parametro_assegnato'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT p.numero_parametro, p.testo_parametro, a.created_at
                FROM user_parametro_assegnato a
                JOIN parametri_rosa p ON p.id_parametro = a.id_parametro
                WHERE a.id_user = :id_user
                ORDER BY a.created_at ASC
            ");
            $stmt->execute(['id_user' => $_SESSION['user_id']]);
            $user_parametri_assegnati = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Profile parametri assegnati error: " . $e->getMessage());
        $user_parametri_assegnati = [];
    }
    
} catch (PDOException $e) {
    error_log("User info load error: " . $e->getMessage());
    
    /**
     * Cosa faccio: fornisco fallback completo in caso errore database.
     * Perché: voglio evitare pagina bianca, meglio dati di esempio.
     */
    $user_info = [
        'username' => $_SESSION['username'],
        'nome_fantasquadra' => $_SESSION['nome_fantasquadra'] ?? '',
        'flag_admin' => $_SESSION['is_admin'] ?? false,
        'created_at' => null,
        'last_activity' => null,
        'last_login' => null,
        'theme_preference' => 'auto',
        'email_notifications' => 1,
        'total_searches' => 0
    ];
    
    $user_stats = [
        'total_searches' => 0,
        'days_registered' => 0,
        'active_days' => 0,
        'favorite_competition_type' => 'N/A',
        'most_played_season' => 'N/A',
        'total_matches_played' => 0,
        'last_export' => 'N/A',
        'personal_record' => 0,
        'worst_performance' => 0,
        'consistency_score' => 0,
        'sessions_total' => 1,
        'activity_frequency' => 0,
        'estimated_data_usage_mb' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo - FantaScarrupat Analyzer</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Theme & Page CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>
    <script>
        window.CURRENT_USER = {
            id_user: <?= (int)$u['id_user'] ?>,
            username: <?= json_encode($u['username']) ?>,
            nome_fantasquadra: <?= json_encode($u['nome_fantasquadra']) ?>,
            is_admin: <?= (int)$u['flag_admin'] ?> === 1,
            theme_preference: <?= json_encode($u['theme_preference'] ?? 'auto') ?>,
            avatar_url: <?= json_encode($u['avatar_url']) ?>
        };
    </script>

    <div class="main-container">
        <!-- Navbar Responsiva -->
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-container">
                    <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
                        Profilo Utente
                    </a>
                    
                    <div class="navbar-nav">
                        <div class="nav-links">
                            <button class="theme-toggle" id="themeToggle" title="Cambia tema" aria-label="Cambia tema">
                                <span class="material-icons" id="themeIcon">dark_mode</span>
                            </button>

                            <div class="user-section">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                        <?php if (!empty($_SESSION['nome_fantasquadra'])): ?>
                                            <div class="user-team"><?= htmlspecialchars($_SESSION['nome_fantasquadra']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="profile-container">
            <!-- Enhanced Profile Hero con dati reali -->
            <div class="profile-hero animate-slide-in">
                <div class="hero-content">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-4 mb-3">
                                <div class="profile-avatar-large">
                                    <?= strtoupper(substr($user_info['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h1 class="hero-title">
                                        Ciao, <?= htmlspecialchars($user_info['username']) ?>!
                                    </h1>
                                    <p class="hero-subtitle">
                                        <?php if ($user_info['flag_admin']): ?>
                                            Amministratore di Sistema
                                        <?php else: ?>
                                            Analista Fantacalcio
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- Hero Stats con dati reali calcolati -->
                            <div class="hero-stats">
                                <div class="hero-stat">
                                    <span class="hero-stat-value">
                                        <?= $user_stats['days_registered'] ?>
                                    </span>
                                    <div class="hero-stat-label">Giorni da cui sei registrato</div>
                                </div>
                                <div class="hero-stat">
                                    <span class="hero-stat-value"><?= $user_stats['total_searches'] ?></span>
                                    <div class="hero-stat-label">Ricerche Totali</div>
                                </div>
                                <div class="hero-stat">
                                    <span class="hero-stat-value"><?= $user_stats['estimated_data_usage_mb'] ?>MB</span>
                                    <div class="hero-stat-label">Consumo Dati Stimato per Ricerca Parametri</div>
                                </div>
                                <div class="hero-stat">
                                    <span class="hero-stat-value"><?= $user_stats['sessions_total'] ?></span>
                                    <div class="hero-stat-label">Sessioni Totali</div>
                                </div>
                                <div class="hero-stat">
                                    <span class="hero-stat-value"><?= $user_stats['activity_frequency'] ?>%</span>
                                    <div class="hero-stat-label">Frequenza Attività</div>
                                </div>
                                <div class="hero-stat">
                                    <span class="hero-stat-value"><?= $user_stats['total_matches_played'] ?></span>
                                    <div class="hero-stat-label">Partite Disputate dal 2024/2025</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Messaggi -->
            <?php if ($success_message): ?>
                <div class="alert-enhanced alert-success-enhanced animate-slide-in">
                    <span class="material-icons" style="font-size: 1.5rem;">check_circle</span>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert-enhanced alert-danger-enhanced animate-slide-in">
                    <span class="material-icons" style="font-size: 1.5rem;">error</span>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Overview con dati reali -->
            <div class="stats-grid animate-slide-in">
                <div class="stat-card-enhanced">
                    <span class="stat-card-value"><?= htmlspecialchars($user_info['nome_fantasquadra']) ?></span>
                    <div class="stat-card-label">Nome FantaSquadra</div>
                </div>
                <?php if ($user_stats['personal_record'] > 0): ?>
                    <div class="stat-card-enhanced">
                        <span class="stat-card-value"><?= number_format($user_stats['personal_record'], 1) ?></span>
                        <div class="stat-card-label">Record Punti Personale</div>
                    </div>
                    <div class="stat-card-enhanced">
                        <span class="stat-card-value"><?= number_format($user_stats['worst_performance'], 1) ?></span>
                        <div class="stat-card-label">Peggior Risultato Personale</div>
                    </div>
                    <div class="stat-card-enhanced">
                        <span class="stat-card-value"><?= number_format($user_stats['consistency_score'], 1) ?></span>
                        <div class="stat-card-label">Media Punti a Partita</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Account Information -->
                <div class="profile-card animate-slide-in">
                    <div class="card-header-enhanced">
                        <div class="card-icon-enhanced">
                            <span class="material-icons">person</span>
                        </div>
                        <div>
                            <h2 class="card-title-enhanced">Informazioni Account</h2>
                            <p class="card-subtitle-enhanced">Gestisci i tuoi dati personali</p>
                        </div>
                    </div>
                    
                    <form method="POST" action="" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group-enhanced">
                            <label for="username" class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">badge</span>
                                Username
                            </label>
                            <input type="text" class="form-control-enhanced" id="username" name="username" 
                                   value="<?= htmlspecialchars($user_info['username']) ?>" 
                                   maxlength="50" required>
                            <div class="form-text-enhanced">Il tuo username unico per accedere al sistema</div>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">verified_user</span>
                                Stato Account
                            </label>
                            <div>
                                <span class="user-badge-enhanced">
                                    <span class="material-icons" style="font-size: 1.1rem;">
                                        <?= $user_info['flag_admin'] ? 'admin_panel_settings' : 'person' ?>
                                    </span>
                                    <?= $user_info['flag_admin'] ? 'Amministratore' : 'Utente Standard' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label for="nome_fantasquadra_display" class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">sports_soccer</span>
                                Nome Fantasquadra
                            </label>
                            <input type="text" class="form-control-enhanced" id="nome_fantasquadra_display" 
                                   value="<?= htmlspecialchars($user_info['nome_fantasquadra'] ?? 'Nessuna fantasquadra') ?>" 
                                   disabled>
                            <div class="form-text-enhanced form-text-disabled">
                                Il nome fantasquadra non può essere modificato da questa sezione
                            </div>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label for="theme_preference" class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">palette</span>
                                Tema Preferito
                            </label>
                            <select class="form-select-enhanced" id="theme_preference" name="theme_preference">
                                <option value="auto" <?= ($user_info['theme_preference'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>Automatico (Sistema)</option>
                                <option value="light" <?= ($user_info['theme_preference'] ?? 'auto') === 'light' ? 'selected' : '' ?>>Chiaro</option>
                                <option value="dark" <?= ($user_info['theme_preference'] ?? 'auto') === 'dark' ? 'selected' : '' ?>>Scuro</option>
                            </select>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">notifications</span>
                                Notifiche Email
                            </label>
                            <div class="d-flex align-items-center gap-3">
                                <label class="toggle-enhanced">
                                    <input type="checkbox" name="email_notifications" <?= ($user_info['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                                    <span class="toggle-slider-enhanced"></span>
                                </label>
                                <span class="form-text-enhanced">Ricevi notifiche via email per aggiornamenti importanti</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-enhanced btn-primary-enhanced w-100">
                            <span class="material-icons" style="font-size: 1.2rem;">save</span>
                            Salva Modifiche
                        </button>
                    </form>
                </div>
                
                <!-- Security Section -->
                <div class="profile-card animate-slide-in">
                    <div class="card-header-enhanced">
                        <div class="card-icon-enhanced">
                            <span class="material-icons">security</span>
                        </div>
                        <div>
                            <h2 class="card-title-enhanced">Sicurezza Account</h2>
                            <p class="card-subtitle-enhanced">Proteggi il tuo account</p>
                        </div>
                    </div>
                    
                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group-enhanced">
                            <label for="current_password" class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">lock</span>
                                Password Attuale
                            </label>
                            <input type="password" class="form-control-enhanced" id="current_password" name="current_password" 
                                   required autocomplete="current-password">
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label for="new_password" class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">lock_open</span>
                                Nuova Password
                            </label>
                            <input type="password" class="form-control-enhanced" id="new_password" name="new_password" 
                                   required autocomplete="new-password" minlength="8">
                            <div class="password-strength-enhanced" id="passwordStrength">
                                <div class="password-strength-fill-enhanced" id="passwordStrengthFill"></div>
                            </div>
                            <div class="form-text-enhanced">Minimo 8 caratteri. Usa lettere, numeri e simboli per maggiore sicurezza.</div>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label for="confirm_password" class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">lock_outline</span>
                                Conferma Password
                            </label>
                            <input type="password" class="form-control-enhanced" id="confirm_password" name="confirm_password" 
                                   required autocomplete="new-password" minlength="8">
                        </div>
                        
                        <div class="d-grid gap-3">
                            <button type="submit" class="btn-enhanced btn-primary-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">shield</span>
                                Cambia Password
                            </button>
                            <button type="button" class="btn-enhanced btn-secondary-enhanced" onclick="resetPasswordForm()">
                                <span class="material-icons" style="font-size: 1.2rem;">clear</span>
                                Annulla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Info Summary -->
            <?php if ($user_info['created_at'] || $user_info['last_activity']): ?>
                <div class="profile-card animate-slide-in">
                    <div class="card-header-enhanced">
                        <div class="card-icon-enhanced">
                            <span class="material-icons">info</span>
                        </div>
                        <div>
                            <h3 class="card-title-enhanced" style="font-size: 1.5rem;">Riepilogo Account</h3>
                            <p class="card-subtitle-enhanced">Informazioni cronologiche dal database</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <?php if ($user_info['created_at']): ?>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                    <div class="h5 mb-1 text-primary">
                                        <?= date('d/m/Y', strtotime($user_info['created_at'])) ?>
                                    </div>
                                    <small style="color: #64748b;">Registrazione</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user_info['last_activity']): ?>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                    <div class="h5 mb-1 text-success">
                                        <?= date('d/m/Y H:i', strtotime($user_info['last_activity'])) ?>
                                    </div>
                                    <small style="color: #64748b;">Ultima Attività</small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user_info['last_login']): ?>
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                    <div class="h5 mb-1 text-warning">
                                        <?= date('d/m/Y H:i', strtotime($user_info['last_login'])) ?>
                                    </div>
                                    <small style="color: #64748b;">Ultimo Login</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Sezione Parametri Assegnati -->
            <?php if (!empty($user_parametri_assegnati)): ?>
                <div class="profile-card animate-slide-in" style="margin-top: 3rem;">
                    <div class="card-header-enhanced">
                        <div class="card-icon-enhanced" style="background: linear-gradient(135deg, #ff9800, #f57c00);">
                            <span class="material-icons">casino</span>
                        </div>
                        <div>
                            <h3 class="card-title-enhanced" style="font-size: 1.5rem;">Parametri Ruota della Fortuna</h3>
                            <p class="card-subtitle-enhanced">I tuoi parametri estratti dalla ruota</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="margin: 0;">
                        <table class="table table-hover" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(245, 124, 0, 0.05)); color: #ff9800; font-weight: 700; text-align: center;">
                                        <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">tag</span>
                                        Numero Parametro
                                    </th>
                                    <th style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(245, 124, 0, 0.05)); color: #ff9800; font-weight: 700;">
                                        <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">description</span>
                                        Testo Parametro
                                    </th>
                                    <th style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(245, 124, 0, 0.05)); color: #ff9800; font-weight: 700; text-align: center;">
                                        <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">schedule</span>
                                        Data Assegnazione
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_parametri_assegnati as $parametro): ?>
                                <tr style="transition: all 0.3s ease;">
                                    <td style="text-align: center; font-weight: 700; color: #ff9800; font-size: 1.1rem;">
                                        #<?= htmlspecialchars((string)$parametro['numero_parametro']) ?>
                                    </td>
                                    <td style="color: var(--text-primary); font-weight: 500;">
                                        <?= htmlspecialchars($parametro['testo_parametro']) ?>
                                    </td>
                                    <td style="text-align: center; color: var(--text-secondary); font-size: 0.9rem;">
                                        <?= date('d/m/Y H:i', strtotime($parametro['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="padding: 1.5rem 2rem; background: linear-gradient(135deg, rgba(255, 152, 0, 0.05), rgba(245, 124, 0, 0.02)); border-top: 1px solid var(--border-light); display: flex; align-items: center; gap: 12px; border-radius: 0 0 20px 20px;">
                        <span class="material-icons" style="color: #ff9800;">info</span>
                        <div style="color: var(--text-secondary); font-size: 0.9rem;">
                            <strong><?= count($user_parametri_assegnati) ?></strong> parametro<?= count($user_parametri_assegnati) !== 1 ? 'i' : '' ?> assegnato<?= count($user_parametri_assegnati) !== 1 ? 'i' : '' ?> tramite la Ruota della Fortuna
                            <?php if (count($user_parametri_assegnati) < 2): ?>
                                <span style="color: #4caf50; font-weight: 600;"> • Puoi ancora riceverne <?= 2 - count($user_parametri_assegnati) ?></span>
                            <?php else: ?>
                                <span style="color: #ff9800; font-weight: 600;"> • Limite massimo raggiunto</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/profile.js"></script>
    <script src="assets/js/mobile-navbar.js"></script>
    <script src="assets/js/session-monitor.js"></script>
</body>
</html>