<?php
declare(strict_types=1);

// **PROTEZIONE AUTENTICAZIONE**
require_once __DIR__ . '/auth/require_login.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/find_userData.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CheckMyTeam - Valutazione Rosa su Criteri</title>
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Tom Select (ricerca nelle select) -->
  <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">

  <!-- Theme & Page CSS -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/checkmyteam.css">
  <link rel="stylesheet" href="assets/css/cache-system.css">

</head>
<body>
<div class="main-container">
  <!-- **NAVBAR IDENTICA A FINDPLAYERBYPARAMETRI** -->
  <nav class="navbar">
    <div class="container-fluid">
      <div class="navbar-container">
        <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
          CheckMyTeam
        </a>
        
        <div class="navbar-nav">
          <div class="nav-links">            
            <div class="header-controls">
              <div class="status-badge not-loaded" id="statusBadge">
                <span class="material-icons">pending</span>
                <span>Dati non caricati</span>
              </div>

              <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <div class="cache-controls">
                  <button class="btn-cache-action" id="btnCacheInfo" title="Informazioni cache">
                    <span class="material-icons" style="font-size: 16px;">info</span>
                    <span>Cache</span>
                  </button>
                  <button class="btn-cache-action force-refresh" id="btnForceRefresh" title="Forza aggiornamento">
                    <span class="material-icons" style="font-size: 16px;">refresh</span>
                    <span>Aggiorna</span>
                  </button>
                </div>
              <?php endif; ?>
            </div>
            
            <button class="theme-toggle" id="themeToggle" title="Cambia tema">
              <span class="material-icons" id="themeIcon">dark_mode</span>
            </button>

            <div class="user-section">
              <div class="user-dropdown">
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
                <div class="dropdown-content">
                  <a href="<?= url('profile.php') ?>" class="dropdown-item">
                    <span class="material-icons">person</span>
                    Profilo
                  </a>
                  <a href="<?= url('auth/logout.php') ?>" class="dropdown-item logout">
                    <span class="material-icons">logout</span>
                    Logout
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile toggle -->
        <button class="mobile-toggle" id="mobileToggle">
          <span class="material-icons">menu</span>
        </button>

        <!-- Mobile menu -->
        <div class="mobile-menu" id="mobileMenu">
          <div class="mobile-nav-links">
            <a href="<?= url('FindPlayerByParametri.php') ?>" class="nav-link">
              <span class="material-icons">search</span>
              Trova Giocatori
            </a>
          </div>
          <div class="mobile-user-section">
            <a href="<?= url('profile.php') ?>" class="nav-link">
              <span class="material-icons">person</span>
              Profilo
            </a>
            <a href="<?= url('auth/logout.php') ?>" class="nav-link">
              <span class="material-icons">logout</span>
              Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- **SELETTORE CRITERI CON SELECT** -->
  <div class="criteria-selector fade-in">
    <div class="criteria-header">
      <h2 class="criteria-title">
        <span class="material-icons">rule</span>
        Seleziona Criteri di Valutazione
      </h2>
    </div>
    <div class="criteria-body">
      <div class="criteria-select-row">
        <div class="criteria-select-wrapper">
          <select class="criteria-select" id="criteriaSelect" disabled>
            <option value="">Seleziona un criterio</option>
          </select>
        </div>
        <button class="btn-add-criteria" id="btnAddCriteria" disabled>
          <span class="material-icons">add</span>
          Aggiungi
        </button>
      </div>
      
      <div class="selected-criteria" id="selectedCriteriaContainer" style="display: none;">
        <h4 style="margin-bottom: 16px; color: var(--text-primary);">Criteri Selezionati:</h4>
        <div class="selected-criteria-list" id="selectedCriteriaList">
          <!-- Populated by JS -->
        </div>
      </div>
    </div>
  </div>

  <!-- **ROSA 25 SELECT IN VERTICALE** -->
  <div class="squad-section fade-in" id="squadSection">
    <div class="squad-header">
      <h2 class="criteria-title">
        <span class="material-icons">groups</span>
        Componi la tua Rosa (25 giocatori)
      </h2>
    </div>
    <div class="squad-body">
      <!-- Portieri -->
      <div class="role-group role-P">
        <div class="role-header">
          <span class="material-icons">sports_soccer</span>
          Portieri (3)
        </div>
        <div class="role-slots" id="portieri-slots">
          <!-- 3 select vertical populated by JS -->
        </div>
      </div>

      <!-- Difensori -->
      <div class="role-group role-D">
        <div class="role-header">
          <span class="material-icons">shield</span>
          Difensori (8)
        </div>
        <div class="role-slots" id="difensori-slots">
          <!-- 8 select vertical populated by JS -->
        </div>
      </div>

      <!-- Centrocampisti -->
      <div class="role-group role-C">
        <div class="role-header">
          <span class="material-icons">directions_run</span>
          Centrocampisti (8)
        </div>
        <div class="role-slots" id="centrocampisti-slots">
          <!-- 8 select vertical populated by JS -->
        </div>
      </div>

      <!-- Attaccanti -->
      <div class="role-group role-A">
        <div class="role-header">
          <span class="material-icons">flash_on</span>
          Attaccanti (6)
        </div>
        <div class="role-slots" id="attaccanti-slots">
          <!-- 6 select vertical populated by JS -->
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<!-- App scripts -->
<script src="assets/js/theme.js"></script>
<script src="assets/js/cache-manager.js"></script>
<script src="assets/js/cache-init.js"></script>
<script src="assets/js/checkmyteam.js"></script>

<script>
  window.CURRENT_USER = {
    id_user: <?= (int)$u['id_user'] ?>,
    username: <?= json_encode($u['username']) ?>,
    nome_fantasquadra: <?= json_encode($u['nome_fantasquadra']) ?>,
    is_admin: <?= (int)$u['flag_admin'] ?> === 1,
    theme_preference: <?= json_encode($u['theme_preference'] ?? 'auto') ?>,
    avatar_url: <?= json_encode($u['avatar_url']) ?>
  };


  // ===== GLOBALS =====
  window.csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
  const csrfToken = window.csrfToken; // Compatibility

  function url(path) {
    return '<?= getProjectBasePath() ?>' + path.replace(/^\/+/, '');
  }
</script>
<script src="assets/js/mobile-navbar.js"></script>
<script src="assets/js/session-monitor.js"></script>

</body>
</html>