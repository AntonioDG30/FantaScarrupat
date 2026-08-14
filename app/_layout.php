<?php
/**
 * app/_layout.php — Layout area privata
 *
 * Uso:
 *   require_once __DIR__ . '/../auth/require_login.php';
 *   require_once __DIR__ . '/_layout.php';
 *   app_layout_start('Titolo pagina', 'nav-key-attivo');
 *   // ...contenuto pagina...
 *   app_layout_end();
 *
 * $active — chiave del link attivo:
 *   'dashboard' | 'hall-of-fame' | 'profilo'
 *   'admin-dashboard' | 'admin-giocatori' | 'admin-partecipanti'
 *   'admin-rose' | 'admin-competizioni' | 'admin-gallery'
 *   'admin-sorteggio'
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

$_APP_USER = [
    'username'     => $_SESSION['username']          ?? 'Utente',
    'is_admin'     => (bool)($_SESSION['flag_admin'] ?? false),
    'fantasquadra' => $_SESSION['nome_fantasquadra'] ?? '',
    'initials'     => strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)),
];

$_APP_NAV = [
    'hall-of-fame' => ['url' => BASE_PATH . 'app/hall-of-fame.php', 'label' => 'Hall of Fame'],
    'profilo'      => ['url' => BASE_PATH . 'app/profilo.php',      'label' => 'Profilo'],
];

$_APP_ADMIN_NAV = [
    'admin-dashboard'    => ['url' => BASE_PATH . 'app/admin/index.php',              'label' => 'Panoramica',    'icon' => 'monitoring'],
    'admin-giocatori'    => ['url' => BASE_PATH . 'app/admin/giocatori.php',           'label' => 'Giocatori',     'icon' => 'sports_soccer'],
    'admin-partecipanti' => ['url' => BASE_PATH . 'app/admin/partecipanti.php',        'label' => 'Partecipanti',  'icon' => 'shield_person'],
    'admin-rose'         => ['url' => BASE_PATH . 'app/admin/rose.php',                'label' => 'Rose',          'icon' => 'list_alt'],
    'admin-competizioni' => ['url' => BASE_PATH . 'app/admin/competizioni.php',        'label' => 'Competizioni',  'icon' => 'emoji_events'],
    'admin-gallery'      => ['url' => BASE_PATH . 'app/admin/gallery.php',             'label' => 'Galleria',      'icon' => 'photo_library'],
    'admin-sorteggio'    => ['url' => BASE_PATH . 'app/sorteggio.php',                 'label' => 'Ruota della Fortuna', 'icon' => 'casino'],
];

/**
 * Apre il documento HTML dell'area privata con la topnav.
 */
function app_layout_start(string $title, string $active_nav = 'dashboard'): void
{
    global $_APP_USER, $_APP_NAV, $_APP_ADMIN_NAV;

    $bp         = defined('BASE_PATH') ? BASE_PATH : '/';
    $full_title = htmlspecialchars($title) . ' · FantaScarrupat';
    $csrf_token = $_SESSION['csrf_token'] ?? '';

    $anno    = defined('CURRENT_ANNO') ? (int)CURRENT_ANNO : (int)date('Y');
    $stagione = ($anno - 1) . '/' . substr((string)$anno, 2, 2);

    $is_admin_page = str_starts_with($active_nav, 'admin-');
    ?>
<!DOCTYPE html>
<html lang="it" data-theme="dark" data-bs-theme="dark" data-zone="app">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $full_title ?></title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= $bp ?>assets/img/favicon.png">

  <!-- Font self-hosted (GDPR: nessuna richiesta a Google) -->
  <link rel="stylesheet" href="<?= $bp ?>assets/css/material-symbols.css">

  <!-- Design system -->
  <link rel="stylesheet" href="<?= $bp ?>assets/css/main.css?v=<?= ASSET_VER ?>">
  <link rel="stylesheet" href="<?= $bp ?>assets/css/app.css?v=<?= ASSET_VER ?>">

  <!-- Bootstrap 5.3 (self-hosted, usato nelle sezioni admin) -->
  <link rel="stylesheet" href="<?= $bp ?>assets/vendor/bootstrap/bootstrap.min.css">

  <!-- Theme pre-apply (evita FOUC) -->
  <script src="<?= $bp ?>assets/js/theme.js"></script>
</head>
<body>

<!-- ── Topnav privata ─────────────────────────────────────────── -->
<nav class="priv-nav" role="navigation" aria-label="Navigazione area riservata">
  <div class="priv-nav__inner">

    <!-- Brand / home -->
    <a class="priv-nav__brand" href="<?= $bp ?>app/dashboard.php" aria-label="FantaScàrrupat, home">
      <span class="priv-nav__monogram" aria-hidden="true">FS</span>
      <span class="priv-nav__brand-name">FantaScàrrupat</span>
    </a>

    <!-- Navigazione principale -->
    <div class="priv-nav__links" id="priv-nav-links" role="menubar">
      <?php foreach ($_APP_NAV as $key => $item): ?>
        <a href="<?= htmlspecialchars($item['url']) ?>"
           class="priv-nav__link<?= $active_nav === $key ? ' is-active' : '' ?>"
           role="menuitem"
           <?= $active_nav === $key ? 'aria-current="page"' : '' ?>>
          <?= htmlspecialchars($item['label']) ?>
        </a>
      <?php endforeach; ?>

      <?php if ($_APP_USER['is_admin']): ?>
      <!-- Solo mobile: Admin e (se sono in area admin) le sue sottosezioni nella tendina -->
      <div class="priv-nav__drawer-admin">
        <div class="priv-nav__drawer-label">Amministrazione</div>
        <a href="<?= $bp ?>app/admin/index.php"
           class="priv-nav__link priv-nav__drawer-link<?= $is_admin_page ? ' is-active' : '' ?>">
          <span class="material-symbols-rounded" aria-hidden="true">admin_panel_settings</span>
          Admin
        </a>
        <?php if ($is_admin_page): foreach ($_APP_ADMIN_NAV as $akey => $aitem): ?>
          <a href="<?= htmlspecialchars($aitem['url']) ?>"
             class="priv-nav__link priv-nav__drawer-link priv-nav__drawer-link--sub<?= $active_nav === $akey ? ' is-active' : '' ?>"
             <?= $active_nav === $akey ? 'aria-current="page"' : '' ?>>
            <span class="material-symbols-rounded" aria-hidden="true"><?= htmlspecialchars($aitem['icon']) ?></span>
            <?= htmlspecialchars($aitem['label']) ?>
          </a>
        <?php endforeach; endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Destra: admin btn + user chip + logout + hamburger -->
    <div class="priv-nav__user">

      <?php if ($_APP_USER['is_admin']): ?>
      <a href="<?= $bp ?>app/admin/index.php"
         class="priv-nav__admin-btn<?= $is_admin_page ? ' is-active' : '' ?>"
         aria-label="Vai all'area admin">
        <span class="material-symbols-rounded" aria-hidden="true">admin_panel_settings</span>
        Admin
      </a>
      <?php endif; ?>

      <a href="<?= $bp ?>app/profilo.php"
         class="priv-nav__chip"
         aria-label="Profilo di <?= htmlspecialchars($_APP_USER['username']) ?>">
        <span class="priv-nav__avatar-small" aria-hidden="true">
          <?= htmlspecialchars($_APP_USER['initials']) ?>
        </span>
        <span class="priv-nav__username"><?= htmlspecialchars($_APP_USER['username']) ?></span>
      </a>

      <a href="<?= $bp ?>auth/logout.php"
         class="priv-nav__logout"
         title="Esci"
         aria-label="Esci dall'area riservata">
        <span class="material-symbols-rounded" aria-hidden="true">logout</span>
      </a>

      <!-- Hamburger mobile -->
      <button class="priv-nav__hamburger"
              id="priv-nav-toggle"
              aria-label="Apri menu"
              aria-expanded="false"
              aria-controls="priv-nav-links">
        <span class="material-symbols-rounded" aria-hidden="true">menu</span>
      </button>

    </div>
  </div>
</nav>

<?php if ($is_admin_page): ?>
<!-- ── Admin sub-nav ──────────────────────────────────────────── -->
<nav class="admin-subnav" aria-label="Navigazione admin">
  <div class="admin-subnav__inner">
    <?php foreach ($_APP_ADMIN_NAV as $key => $item): ?>
      <a href="<?= htmlspecialchars($item['url']) ?>"
         class="admin-subnav__link<?= $active_nav === $key ? ' is-active' : '' ?>"
         <?= $active_nav === $key ? 'aria-current="page"' : '' ?>>
        <span class="material-symbols-rounded" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
        <?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>

<!-- ── Contenuto pagina ───────────────────────────────────────── -->
<main class="priv-main" id="main-content" role="main">
<?php
    // Dati JS globali
    echo '<script>';
    echo 'window.BASE_PATH='    . json_encode($bp, JSON_HEX_TAG) . ';';
    echo 'window.CURRENT_USER=' . json_encode([
        'username'     => $_APP_USER['username'],
        'is_admin'     => $_APP_USER['is_admin'],
        'fantasquadra' => $_APP_USER['fantasquadra'],
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) . ';';
    echo 'window.CSRF_TOKEN='   . json_encode($csrf_token, JSON_HEX_TAG) . ';';
    echo '</script>';
}

/**
 * Chiude main, aggiunge script, chiude html.
 */
function app_layout_end(): void
{
    $bp = defined('BASE_PATH') ? BASE_PATH : '/';
    ?>
</main>

<!-- Bootstrap JS (self-hosted) -->
<script src="<?= $bp ?>assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>

<!-- Session monitor -->
<script src="<?= $bp ?>assets/js/session-monitor.js"></script>

<!-- Nav mobile toggle -->
<script>
(function () {
  var toggle = document.getElementById('priv-nav-toggle');
  var links  = document.getElementById('priv-nav-links');
  if (!toggle || !links) return;

  toggle.addEventListener('click', function () {
    var open = links.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && links.classList.contains('is-open')) {
      links.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });

  // Click fuori chiude il menu
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.priv-nav') && links.classList.contains('is-open')) {
      links.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
}());
</script>

</body>
</html>
    <?php
}
