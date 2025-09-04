<?php
    declare(strict_types=1);

    // Protezione autenticazione e accesso admin
    require_once __DIR__ . '/auth/require_login.php';
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/find_userData.php';
    require_once __DIR__ . '/api/ruota.php';


    // Solo admin può accedere
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: ' . url('HomeParametri.php'));
        exit;
    }

    // Rigenera CSRF token se non esiste
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruota della Fortuna - FantaScarrupat Analyzer</title>
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
    <link rel="stylesheet" href="assets/css/sorteggio-ruota.css">
</head>
<body>
    <div class="main-container">
        <!-- Navbar uniforme -->
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-container">
                    <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
                        <span class="material-icons" style="font-size: 2rem;">casino</span>
                        Ruota della Fortuna
                    </a>
                    
                    <div class="navbar-nav">
                        <div class="nav-links">
                            <button class="theme-toggle" id="themeToggle" title="Cambia tema" aria-label="Cambia tema">
                                <span class="material-icons" id="themeIcon">dark_mode</span>
                            </button>
                            
                            <a href="<?= url('indexAdmin.php') ?>" class="nav-link">
                                <span class="material-icons">admin_panel_settings</span>
                                Admin Panel
                            </a>
                            
                            <a href="<?= url('HomeParametri.php') ?>" class="nav-link">
                                <span class="material-icons">home</span>
                                Home
                            </a>

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
                <!-- Hero Header -->
                <div class="hero-header fade-in-up">
                    <div class="hero-content">
                        <h1 class="hero-title">
                            <span class="material-icons" style="font-size: 3.5rem;">casino</span>
                            Ruota della Fortuna
                        </h1>
                        <p class="hero-subtitle">
                            Sistema di assegnazione parametri casuali per fantallenatori. Ogni parametro può essere assegnato una sola volta, ogni utente può riceverne al massimo 2.
                        </p>
                    </div>
                </div>
                
                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Setup Card -->
                    <div class="ruota-card fade-in-up">
                        <div class="card-header-enhanced">
                            <div class="card-icon-enhanced">
                                <span class="material-icons">settings</span>
                            </div>
                            <div>
                                <h2 class="card-title-enhanced">Setup Ruota</h2>
                                <p class="card-subtitle-enhanced">Seleziona i partecipanti per l'estrazione</p>
                            </div>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">group</span>
                                Utenti disponibili
                            </label>
                            
                            <div class="selection-controls">
                                <button type="button" id="selectAllBtn" class="btn-select-all">
                                    <span class="material-icons" style="font-size: 16px;">select_all</span>
                                    Seleziona tutti
                                </button>
                                <button type="button" id="clearAllBtn" class="btn-select-all">
                                    <span class="material-icons" style="font-size: 16px;">clear_all</span>
                                    Deseleziona tutti
                                </button>
                            </div>
                            
                            <div id="usersGrid" class="users-grid">
                                <!-- Popolato dinamicamente -->
                            </div>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label class="form-label-enhanced">
                                <span class="material-icons" style="font-size: 1.2rem;">people</span>
                                Partecipanti selezionati
                            </label>
                            <div id="selectedUsers" class="selected-users-container">
                                <em style="color: var(--text-muted);">Nessun partecipante selezionato</em>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3 flex-wrap">
                            <button id="startWheel" class="btn-enhanced btn-primary-enhanced" disabled>
                                <span class="material-icons">play_arrow</span>
                                Avvia Ruota
                            </button>
                            <button id="cancelCurrentSession" class="btn-enhanced btn-secondary-enhanced" disabled>
                                <span class="material-icons">cancel</span>
                                Annulla Sessione
                            </button>
                            <button id="resetAssignments" class="btn-enhanced btn-secondary-enhanced">
                                <span class="material-icons">refresh</span>
                                Reset Tutte
                            </button>
                        </div>
                    </div>
                    
                    <!-- Wheel Card -->
                    <div class="ruota-card fade-in-up">
                        <div class="card-header-enhanced">
                            <div class="card-icon-enhanced">
                                <span class="material-icons">casino</span>
                            </div>
                            <div>
                                <h2 class="card-title-enhanced">Ruota della Fortuna</h2>
                                <p class="card-subtitle-enhanced">Estrazione parametri in corso</p>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="wheel-container">
                                <div class="wheel-pointer">
                                    <span class="material-icons">keyboard_arrow_down</span>
                                </div>
                                <div id="wheel" class="wheel-container">
                                    <div class="wheel-center">
                                        <span class="material-icons" style="color: var(--primary-color);">stars</span>
                                    </div>
                                    <!-- Segmenti generati dinamicamente -->
                                </div>
                            </div>
                            
                            <button id="spinBtn" class="btn-enhanced btn-primary-enhanced" disabled>
                                <span class="material-icons">rotate_right</span>
                                Gira la Ruota
                            </button>
                            
                            <div id="wheelStatus" class="wheel-status">
                                <em>Seleziona i partecipanti per iniziare</em>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Results Section - NASCONDE i parametri -->
                <div class="results-section fade-in-up">
                    <div class="ruota-card">
                        <div class="card-header-enhanced">
                            <div class="card-icon-enhanced" style="background: linear-gradient(135deg, var(--success-color), #16a34a);">
                                <span class="material-icons">emoji_events</span>
                            </div>
                            <div>
                                <h2 class="card-title-enhanced">Risultati Estrazione</h2>
                                <p class="card-subtitle-enhanced">Utenti estratti (parametri assegnati segretamente)</p>
                            </div>
                        </div>
                        
                        <div id="results" class="results-grid">
                            <!-- Risultati popolati dinamicamente - SENZA dettagli parametri -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dialog Reset Conferma -->
        <div id="resetDialog" class="dialog-overlay">
            <div class="dialog-enhanced">
                <div class="dialog-header-enhanced">
                    <span class="material-icons" style="color: var(--error-color); font-size: 2rem;">warning</span>
                    <h3 class="dialog-title-enhanced">Reset Tutte le Assegnazioni</h3>
                </div>
                <div class="dialog-content-enhanced">
                    <p>Sei sicuro di voler eliminare <strong>tutte</strong> le assegnazioni parametri esistenti? Questa operazione non può essere annullata.</p>
                    <p style="margin-top: 1rem; color: var(--warning-color);">
                        <strong>Attenzione:</strong> Tutti i parametri assegnati (anche delle sessioni precedenti) verranno rimossi definitivamente.
                    </p>
                </div>
                <div class="dialog-actions-enhanced">
                    <button id="cancelReset" class="btn-enhanced btn-secondary-enhanced">
                        <span class="material-icons">close</span>
                        Annulla
                    </button>
                    <button id="confirmReset" class="btn-enhanced btn-primary-enhanced" style="background: linear-gradient(135deg, var(--error-color), #dc2626);">
                        <span class="material-icons">delete_forever</span>
                        Conferma Reset
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Dialog Annulla Sessione Corrente -->
        <div id="cancelSessionDialog" class="dialog-overlay">
            <div class="dialog-enhanced">
                <div class="dialog-header-enhanced">
                    <span class="material-icons" style="color: var(--warning-color); font-size: 2rem;">cancel</span>
                    <h3 class="dialog-title-enhanced">Annulla Sessione Corrente</h3>
                </div>
                <div class="dialog-content-enhanced">
                    <p>Sei sicuro di voler annullare la sessione corrente della ruota?</p>
                    <p style="margin-top: 1rem; color: var(--text-secondary);">
                        Verranno rimosse solo le assegnazioni di questa sessione, mantenendo quelle delle sessioni precedenti.
                    </p>
                </div>
                <div class="dialog-actions-enhanced">
                    <button id="cancelCancelSession" class="btn-enhanced btn-secondary-enhanced">
                        <span class="material-icons">close</span>
                        Annulla
                    </button>
                    <button id="confirmCancelSession" class="btn-enhanced btn-primary-enhanced" style="background: linear-gradient(135deg, var(--warning-color), #f59e0b);">
                        <span class="material-icons">cancel</span>
                        Conferma Annullamento
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Toast Container -->
        <div id="toastContainer" class="toast-container">
            <!-- Toast messages popolati dinamicamente -->
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/sorteggio-ruota.js"></script>
    
    <script>
        window.CURRENT_USER = {
            id_user: <?= (int)$u['id_user'] ?>,
            username: <?= json_encode($u['username']) ?>,
            nome_fantasquadra: <?= json_encode($u['nome_fantasquadra']) ?>,
            is_admin: <?= (int)$u['flag_admin'] ?> === 1,
            theme_preference: <?= json_encode($u['theme_preference'] ?? 'auto') ?>,
            avatar_url: <?= json_encode($u['avatar_url']) ?> // valore così com'è dal DB
        };

        // Configuration
        const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
        
        function url(path) {
            return '<?= getProjectBasePath() ?>' + path.replace(/^\/+/, '');
        }                
    </script>
    
    <!-- Mobile Navbar Script -->
    <script src="assets/js/mobile-navbar.js"></script>
    <script src="assets/js/session-monitor.js"></script>
</body>
</html>