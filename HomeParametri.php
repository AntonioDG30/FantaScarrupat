<?php
declare(strict_types=1);

// Protezione autenticazione
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/find_userData.php';

// Gestione logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Verifica CSRF per logout
    if (isset($_GET['token']) && hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        session_destroy();
        redirect('login.php');
    }
}

// Rigenera CSRF token se non esiste
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Statistiche utente REALI dal database
$user_stats = [
    'last_login' => null,
    'total_searches' => 0,
    'favorite_criteria' => 'N/A',
    'most_used_filter' => 'N/A',
    'recent_activity' => []
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
    
    // Statistiche utente corrente
    $stmt = $pdo->prepare("
        SELECT 
            last_login,
            total_searches,
            created_at
        FROM users 
        WHERE id_user = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    if ($user_data) {
        $user_stats['last_login'] = $user_data['last_login'];
        $user_stats['total_searches'] = (int)($user_data['total_searches'] ?? 0);
    }
    
    // Attività recenti (se esiste la tabella)
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_activities'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("
            SELECT description 
            FROM user_activities 
            WHERE id_user = ? 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $activities = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $user_stats['recent_activity'] = $activities ?: [
            'Prima visita alla piattaforma',
            'Account creato con successo',
            'Benvenuto in FantaScarrupat Analyzer!'
        ];
    } else {
        // Fallback se tabella non esiste
        $user_stats['recent_activity'] = [
            'Prima visita alla piattaforma',
            'Account creato con successo',
            'Benvenuto in FantaScarrupat Analyzer!'
        ];
    }
    
    // Statistiche globali sistema
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_competitions FROM competizione");
    $total_competitions = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS all_criteria FROM parametri_rosa;");
    $all_criteria = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT codice_fantacalcio) AS total_players FROM giocatore;");
    $total_players = $stmt->fetchColumn();
    
    $system_stats = [
        'total_users' => (int)$total_users,
        'total_competitions' => (int)$total_competitions,
        'all_criteria' => (int)$all_criteria,
        'total_players' => (int)$total_players
    ];
    
} catch (PDOException $e) {
    error_log("HomeParametri database error: " . $e->getMessage());
    // Fallback con dati di esempio in caso di errore DB
    $system_stats = [
        'total_users' => 1,
        'total_competitions' => 5,
        'all_criteria' => 34,
        'total_players' => 500
    ];
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - FantaScarrupat Analyzer</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/home-parametri.css">
    
</head>
<body>
    <div class="main-container">
        <!-- Navbar Responsiva -->
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-container">
                    <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
                    HomeParametri
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
        
        <!-- Page Content -->
        <div class="container-fluid">
            <div class="page-content">
                <!-- Welcome Section -->
                <div class="welcome-section fade-in-up">
                    <h1 class="welcome-title">
                        Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?>!
                    </h1>
                    <p class="welcome-subtitle">
                        Il tuo centro di controllo per l'analisi fantacalcio più avanzata. 
                        Esplora i moduli disponibili e porta la tua strategia al livello successivo.
                    </p>
                    
                    <div class="welcome-stats">
                        <div class="stat-item">
                            <span class="stat-value" data-count="<?= $user_stats['total_searches'] ?>"><?= $user_stats['total_searches'] ?></span>
                            <div class="stat-label">Tue Ricerche</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" data-count="<?= $system_stats['all_criteria'] ?>"><?= $system_stats['all_criteria'] ?></span>
                            <div class="stat-label">Criteri Disponibili</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" data-count="<?= $system_stats['total_players'] ?>"><?= $system_stats['total_players'] ?></span>
                            <div class="stat-label">Giocatori Database</div>
                        </div>
                    </div>
                </div>
                
                <!-- Modules Grid - RIMOSSI Market Analysis e Profilo Utente -->
                <div class="modules-grid">
                    <!-- FindPlayerByParametri -->
                    <a href="<?= url('FindPlayerByParametri.php') ?>" class="module-card pulse-glow fade-in-up">
                        <div class="module-icon-container">
                            <span class="material-icons module-icon">search</span>
                        </div>
                        <div class="module-title">FindPlayerByParametri</div>
                        <div class="module-description">
                            Sistema avanzato di ricerca giocatori con <?= $system_stats['all_criteria'] ?> criteri multipli, filtri dinamici personalizzati e analisi dettagliate per ottimizzare la tua strategia fantacalcio.
                        </div>
                        <div class="module-footer">
                            <div class="module-status status-available">Disponibile</div>
                            <span class="material-icons module-arrow">arrow_forward</span>
                        </div>
                    </a>

                    <!-- CheckMyTeam -->
                    <a href="<?= url('CheckMyTeam.php') ?>" class="module-card pulse-glow fade-in-up">
                        <div class="module-icon-container">
                            <span class="material-icons module-icon">groups</span>
                        </div>
                        <div class="module-title">CheckMyTeam</div>
                        <div class="module-description">
                            Analizza la tua rosa attuale, ottimizza le formazioni settimanali e ricevi consigli strategici personalizzati per massimizzare i punti fantacalcio.
                        </div>
                        <div class="module-footer">
                            <div class="module-status status-available">Disponibile</div>
                            <span class="material-icons module-arrow">arrow_forward</span>
                        </div>
                    </a>
                    

                    <!-- Hall of Fame -->
                    <a href="<?= url('HallOfFame.php') ?>" class="module-card fade-in-up">
                        <div class="module-icon-container">
                            <span class="material-icons module-icon">emoji_events</span>
                        </div>
                        <div class="module-title">Hall of Fame</div>
                        <div class="module-description">
                            La tua sala trofei personale con tutti i successi, le competizioni disputate e le statistiche storiche del tuo percorso nel fantacalcio.
                        </div>
                        <div class="module-footer">
                            <div class="module-status status-available">Disponibile</div>
                            <span class="material-icons module-arrow">arrow_forward</span>
                        </div>
                    </a>

                    <!-- Admin Panel (Solo per Admin) -->
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <a href="<?= url('dashboardAdmin.php') ?>" class="module-card admin-only fade-in-up">
                        <div class="module-icon-container admin-icon-container">
                            <span class="material-icons module-icon">admin_panel_settings</span>
                        </div>
                        <div class="module-title">Pannello Admin</div>
                        <div class="module-description">
                            Area riservata agli amministratori per la gestione del sistema, monitoraggio utenti, configurazione database e controllo delle performance.
                        </div>
                        <div class="module-footer">
                            <div class="module-status status-admin-only">Solo Admin</div>
                            <span class="material-icons module-arrow">arrow_forward</span>
                        </div>
                    </a>
                    <a href="<?= url('SorteggioRuota.php') ?>" class="module-card admin-only fade-in-up">
                        <div class="module-icon-container admin-icon-container">
                            <span class="material-icons module-icon">admin_panel_settings</span>
                        </div>
                        <div class="module-title">Ruota della Fortuna</div>
                        <div class="module-description">
                            Area riservata agli amministratori gestione e utilizzo della Ruota della Fortuna a fin di sorteggiare l'ordine dei fantallenatori.
                        </div>
                        <div class="module-footer">
                            <div class="module-status status-admin-only">Solo Admin</div>
                            <span class="material-icons module-arrow">arrow_forward</span>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activity Section -->
                <div class="activity-section fade-in-up">
                    <div class="activity-header">
                        <div class="activity-icon">
                            <span class="material-icons">history</span>
                        </div>
                        <div>
                            <h3 class="activity-title">Attività Recente</h3>
                        </div>
                    </div>
                    
                    <ul class="activity-list">
                        <?php foreach ($user_stats['recent_activity'] as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-bullet"></div>
                            <div class="activity-text"><?= htmlspecialchars($activity) ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/home-parametri.js"></script>
    <script>
        window.CURRENT_USER = {
            id_user: <?= (int)$u['id_user'] ?>,
            username: <?= json_encode($u['username']) ?>,
            nome_fantasquadra: <?= json_encode($u['nome_fantasquadra']) ?>,
            is_admin: <?= (int)$u['flag_admin'] ?> === 1,
            theme_preference: <?= json_encode($u['theme_preference'] ?? 'auto') ?>,
            avatar_url: <?= json_encode($u['avatar_url']) ?> // valore così com'è dal DB
        };


        // CSRF token globale per navbar mobile
        const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
        
        // Funzione url() per compatibilità
        function url(path) {
            const basePath = '<?= getProjectBasePath() ?>';
            return basePath + path.replace(/^\/+/, '');
        }
    </script>
    
    <!-- Mobile Navbar Script -->
    <script src="assets/js/mobile-navbar.js"></script>
    <script src="assets/js/session-monitor.js"></script>
</body>
</html>