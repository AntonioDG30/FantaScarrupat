<?php
declare(strict_types=1);

// Protezione autenticazione e verifica admin
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/path_helper.php';

// Verifica permessi admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    redirect('HomeParametri.php');
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Connessione database con PDO per migliori performance
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("Errore connessione database: " . $e->getMessage());
}

// Tab attiva con validazione
$validTabs = ['dashboard', 'calciatori', 'partecipanti', 'rose', 'parametri', 'competizioni', 'gallery'];
$activeTab = $_GET['tab'] ?? 'dashboard';
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'dashboard';
}

// Check per richieste AJAX (per auto-refresh dashboard)
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'timestamp' => time()]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="it" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - FantaScarrupat Analyzer</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <meta name="is-admin" content="1">
    <meta name="theme-color" content="#3b82f6">
    <meta name="description" content="Pannello amministrativo FantaScarrupat - Gestione completa del sistema">
    
    <!-- Preload critical fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/modern-enhancements.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Enhanced Navbar -->
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-container">
                    <!-- Left Section -->
                    <div class="navbar-left">
                        <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
                            <span class="material-icons brand-icon">admin_panel_settings</span>
                            <span class="brand-text">Admin Panel</span>
                        </a>
                    </div>
                    
                    <!-- Right Section -->
                    <div class="navbar-right">
                        <div class="nav-links">
                            <a href="<?= url('HomeParametri.php') ?>" class="nav-link" title="Torna al sito">
                                <span class="material-icons">home</span>
                                <span class="link-text">Home</span>
                            </a>
                            
                            <button class="theme-toggle" id="themeToggle" title="Cambia tema" aria-label="Cambia tema">
                                <span class="material-icons" id="themeIcon">dark_mode</span>
                            </button>
                            
                            <div class="user-section">
                                <div class="user-info" title="Profilo amministratore">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                        <div class="user-role">Amministratore</div>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="<?= url('HomeParametri.php') ?>?action=logout&token=<?= $_SESSION['csrf_token'] ?>" 
                               class="nav-link logout-link"
                               onclick="return confirm('Sei sicuro di voler uscire?')"
                               title="Esci dal pannello admin">
                                <span class="material-icons">logout</span>
                                <span class="link-text">Esci</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Enhanced Sidebar -->
            <div class="admin-sidebar">
                <div class="sidebar-menu">
                    <!-- Dashboard -->
                    <a href="?tab=dashboard" class="menu-item <?= $activeTab === 'dashboard' ? 'active' : '' ?>" 
                       title="Dashboard principale">
                        <span class="material-icons">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                    
                    <div class="menu-section">Gestione Dati</div>
                    
                    <a href="?tab=calciatori" class="menu-item <?= $activeTab === 'calciatori' ? 'active' : '' ?>"
                       title="Gestisci database calciatori">
                        <span class="material-icons">sports_soccer</span>
                        <span>Calciatori</span>
                    </a>
                    
                    <a href="?tab=partecipanti" class="menu-item <?= $activeTab === 'partecipanti' ? 'active' : '' ?>"
                       title="Gestisci partecipanti e fantasquadre">
                        <span class="material-icons">groups</span>
                        <span>Partecipanti</span>
                    </a>
                    
                    <a href="?tab=rose" class="menu-item <?= $activeTab === 'rose' ? 'active' : '' ?>"
                       title="Gestisci rose delle fantasquadre">
                        <span class="material-icons">list_alt</span>
                        <span>Rose</span>
                    </a>
                    
                    <a href="?tab=parametri" class="menu-item <?= $activeTab === 'parametri' ? 'active' : '' ?>"
                       title="Configura parametri di analisi">
                        <span class="material-icons">tune</span>
                        <span>Parametri</span>
                    </a>
                    
                    <div class="menu-section">Contenuti</div>
                    
                    <a href="?tab=competizioni" class="menu-item <?= $activeTab === 'competizioni' ? 'active' : '' ?>"
                       title="Gestisci competizioni e tornei">
                        <span class="material-icons">emoji_events</span>
                        <span>Competizioni</span>
                    </a>
                    
                    <a href="?tab=gallery" class="menu-item <?= $activeTab === 'gallery' ? 'active' : '' ?>"
                       title="Gestisci gallery immagini">
                        <span class="material-icons">photo_library</span>
                        <span>Gallery</span>
                    </a>
                </div>

            </div>

            <!-- Tab Content Area -->
            <div class="admin-main">
                <?php
                // Loading animation
                echo '<div id="loading-overlay" class="loading-overlay" style="display: none;">
                        <div class="loading-content">
                            <div class="loading-spinner"></div>
                            <p>Caricamento in corso...</p>
                        </div>
                      </div>';
                
                // Include della sezione attiva
                $sectionFile = __DIR__ . "/admin-sections/{$activeTab}.php";
                if (file_exists($sectionFile)) {
                    include $sectionFile;
                } else {
                    // Fallback se il file non esiste
                    echo '<div class="admin-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <span class="material-icons">error</span>
                                    Sezione non trovata
                                </h3>
                            </div>
                            <div class="alert alert-warning">
                                <span class="material-icons">warning</span>
                                La sezione richiesta non è disponibile. 
                                <a href="?tab=dashboard" class="btn btn-primary">Torna alla Dashboard</a>
                            </div>
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="notifications-container" class="notifications-container"></div>

    <!-- Scripts -->
    <!-- jQuery (required for DataTables) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- Custom Scripts -->
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/session-monitor.js"></script>
    <script src="assets/js/admin-enhanced.js"></script>
    
    <!-- Global Configuration -->
    <script>
        // Configurazione globale
        const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
        const currentTab = '<?= htmlspecialchars($activeTab) ?>';
        const isAdmin = true;
        
        function url(path) {
            return '<?= getProjectBasePath() ?>' + path.replace(/^\/+/, '');
        }
        
        // Error handling globale
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            if (window.adminPanel) {
                window.adminPanel.showNotification({
                    title: 'Errore JavaScript',
                    message: 'Si è verificato un errore. Ricarica la pagina se il problema persiste.',
                    type: 'error',
                    duration: 8000
                });
            }
        });
        

        
        // Performance monitoring
        window.addEventListener('load', function() {
            if (window.performance) {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                console.log(`Admin Panel loaded in ${loadTime}ms`);
            }
        });
    </script>
    
    <!-- Structured Data per SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "FantaScarrupat Admin Panel",
        "description": "Pannello amministrativo per la gestione del sistema FantaScarrupat",
        "applicationCategory": "BusinessApplication",
        "operatingSystem": "Any"
    }
    </script>
</body>
</html>