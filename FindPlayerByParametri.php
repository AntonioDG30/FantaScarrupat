<?php
declare(strict_types=1);

// **PROTEZIONE AUTENTICAZIONE**
require_once __DIR__ . '/auth/require_login.php';


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/find_userData.php';


// Log attività ricerca
function logSearchActivity($pdo, $user_id, $criteria_id = null) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_activities'");
        if ($stmt->rowCount() > 0) {
            $description = $criteria_id ? "Ricerca criterio $criteria_id" : "Nuova ricerca avviata";
            $stmt = $pdo->prepare("INSERT INTO user_activities (id_user, activity_type, description) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, 'search', $description]);
        }
        
        // Incrementa contatore ricerche utente
        $stmt = $pdo->prepare("UPDATE users SET total_searches = COALESCE(total_searches, 0) + 1 WHERE id_user = ?");
        $stmt->execute([$user_id]);
        
    } catch (PDOException $e) {
        error_log("Search activity log error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FantaScarrupat Analyzer - Sistema di Analisi</title>
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <!-- Bootstrap 5 (grid/utilities) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.2.0/css/scroller.bootstrap5.min.css">

  <!-- noUiSlider -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css">

  <!-- Theme & Page CSS -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/FindPlayerByParametri.css">
  
</head>
<body>
  <script>
    window.CURRENT_USER = {
      id_user: <?= (int)$u['id_user'] ?>,
      username: <?= json_encode($u['username']) ?>,
      nome_fantasquadra: <?= json_encode($u['nome_fantasquadra']) ?>,
      is_admin: <?= (int)$u['flag_admin'] ?> === 1,
      theme_preference: <?= json_encode($u['theme_preference'] ?? 'auto') ?>,
      avatar_url: <?= json_encode($u['avatar_url']) ?> // valore così com'è dal DB
    };
  </script>

<div class="main-container">
  <!-- **NAVBAR RESPONSIVA MIGLIORATA** -->
  <nav class="navbar">
    <div class="container-fluid">
      <div class="navbar-container">
        <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
        FindPlayerByParametri
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
                  <button class="btn-cache-action force-refresh" id="btnForceRefresh" title="Forza aggiornamento (ignora cache)">
                  <span class="material-icons" style="font-size: 16px;">refresh</span>
                  <span>Aggiorna</span>
                  </button>
                </div>
              <?php endif; ?>
                
            </div>
        
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


  <!-- Controls bar -->
  <div class="controls-bar slide-up">
    <div class="controls-tabs">
      <button class="control-tab active"><span class="material-icons">search</span> Singolo</button>
      <button class="control-tab"><span class="material-icons">join_inner</span> Doppio</button>
      <button class="control-tab"><span class="material-icons">tune</span> Filtri</button>
    </div>

    <div class="control-panel active" id="panel-single-wrapper">
      <div class="control-panel-inner">
        <div class="panel-title">CRITERIO</div>
        <div class="panel-row">
          <div class="criteria-select-wrapper">
            <select class="form-select criteria-select" id="criteriaSelect" disabled>
              <option value="">Seleziona un criterio</option>
            </select>
          </div>
          <div class="criteria-select-wrapper" id="prevTeamWrapper" style="display:none; gap:8px; align-items:center;">
            <label for="prevTeamSelect" class="form-label" style="margin:0;">Fantasquadra</label>
            <select id="prevTeamSelect" class="criteria-select" disabled>
              <option value="">Seleziona fantasquadra</option>
            </select>
          </div>
          <button class="btn-modern btn-gradient" id="btnRun" disabled>
            <span class="material-icons">search</span> Esegui
          </button>
        </div>
      </div>
    </div>

    <div class="control-panel" id="panel-dual-wrapper">
      <div class="control-panel-inner">
        <div class="panel-title">CRITERI</div>
        <div class="panel-row dual">
          <div class="criteria-group">
            <select class="criteria-select" id="criteriaSelectA" disabled>
              <option value="">Seleziona criterio A</option>
            </select>
          </div>
          <div id="prevTeamWrapperA" class="criteria-select-wrapper" style="display:none; gap:8px; align-items:center; margin-top:6px;">
            <label for="prevTeamSelectA" class="form-label" style="margin:0;">Fantasquadra</label>
            <select id="prevTeamSelectA" class="criteria-select" disabled>
              <option value="">Seleziona fantasquadra</option>
            </select>
          </div>
          <div class="criteria-separator"><span class="material-icons">join_inner</span></div>
          <div class="criteria-group">
            <select class="criteria-select" id="criteriaSelectB" disabled>
              <option value="">Seleziona criterio B</option>
            </select>
          </div>
          <div id="prevTeamWrapperB" class="criteria-select-wrapper" style="display:none; gap:8px; align-items:center; margin-top:6px;">
            <label for="prevTeamSelectB" class="form-label" style="margin:0;">Fantasquadra</label>
            <select id="prevTeamSelectB" class="form-select criteria-select" disabled>
              <option value="">Seleziona fantasquadra</option>
            </select>
          </div>
          <button class="btn-modern btn-gradient" id="btnRunDual" disabled>
            <span class="material-icons">play_arrow</span> Esegui entrambi (AND)
          </button>
        </div>
        <div class="dual-result-links" id="dualResultLinks" style="display:none">
          <a href="#" class="quick-link" id="linkOnlyA">Solo A</a> •
          <a href="#" class="quick-link" id="linkOnlyB">Solo B</a>
        </div>
      </div>
    </div>

    <div class="control-panel" id="panel-filters-wrapper">
      <div class="control-panel-inner">
        <div class="panel-title">FILTRI</div>
        <div class="filters-grid" id="filtersCard" style="display:none">
          <div class="filter-group">
            <label class="form-label">Ruolo</label>
            <div id="ruoloFilters" class="filter-chips">
              <div class="filter-chip" data-value="P">P</div>
              <div class="filter-chip" data-value="D">D</div>
              <div class="filter-chip" data-value="C">C</div>
              <div class="filter-chip" data-value="A">A</div>
            </div>
          </div>
          <div class="filter-group">
            <label class="form-label" for="squadraFilter">Squadra</label>
            <select id="squadraFilter" class="form-select"></select>
          </div>
          <div class="filter-group">
            <label class="form-label" for="nazionalitaFilter">Nazionalità</label>
            <select id="nazionalitaFilter" class="form-select"></select>
          </div>
          <div class="filter-group">
            <label class="form-label">Quotazione</label>
            <div class="slider-container">
              <div id="quotazioneRange"></div>
              <div class="slider-labels">
                <span>Min: <strong id="quotazioneMin">-</strong></span>
                <span>Max: <strong id="quotazioneMax">-</strong></span>
              </div>
            </div>
          </div>
          <div class="filter-group">
            <label class="form-label">Età</label>
            <div class="slider-container">
              <div id="etaRange"></div>
              <div class="slider-labels">
                <span>Min: <strong id="etaMin">-</strong></span>
                <span>Max: <strong id="etaMax">-</strong></span>
              </div>
            </div>
          </div>
          <div class="filter-group">
            <label class="form-label">&nbsp;</label>
            <button class="btn-modern btn-outline" id="btnResetFilters">
              <span class="material-icons">restart_alt</span> Reset filtri
            </button>
          </div>
          <div class="filter-group" style="grid-column:1 / -1;">
            <div id="activeFiltersDisplay" class="active-filters">
              <span class="text-muted">Nessun filtro attivo</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Results -->
  <div class="results-card fade-in" id="resultsCard" style="display:none">
    <div class="results-header">
      <div class="results-info">
        <div class="results-count" id="resultsCount">0 giocatori trovati</div>
      </div>
      <div class="results-toolbar">
        <button class="btn-export" onclick="exportData('csv')" title="Esporta in CSV">
          <span class="material-icons">download</span> CSV
        </button>
        <button class="btn-export" onclick="exportData('xlsx')" title="Esporta in Excel">
          <span class="material-icons">download</span> Excel
        </button>
        <button class="btn-export" onclick="exportData('pdf')" title="Esporta in PDF">
          <span class="material-icons">download</span> PDF
        </button>
      </div>
    </div>
    <div class="table-container">
      <div id="emptyState" class="empty-state" style="display:none">
        <div class="empty-state-content">
          <span class="material-icons">search_off</span>
          <h3>Nessun risultato trovato</h3>
          <p>Prova a modificare i criteri di ricerca o i filtri applicati.</p>
        </div>
      </div>
      <table id="resultsTable" class="table table-sm">
        <thead></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display:none">
  <div class="loading-content">
    <div class="loading-spinner"></div>
    <div class="loading-text">Caricamento in corso…</div>
  </div>
</div>

<!-- jQuery + fallback chain -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script>if(!window.jQuery){document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\\/script>')}</script>
<script>if(!window.jQuery){document.write('<script src="/assets/vendor/jquery-3.7.1.min.js"><\\/script>')}</script>

<!-- Bootstrap bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables core + extensions -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.2.0/js/dataTables.scroller.min.js"></script>

<!-- noUiSlider -->
<script src="https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js"></script>

<!-- App scripts -->
<script src="assets/js/theme.js"></script>
<script src="assets/js/filters.js"></script>
<script src="assets/js/criteria-and.js"></script>
<script src="assets/js/progress.js"></script>
<script src="assets/js/findPlayerByParametri.js"></script>

<script>
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