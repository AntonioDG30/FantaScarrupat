<?php
/**
 * public/_layout.php — Layout sito pubblico
 *
 * Uso:
 *   require_once __DIR__ . '/_layout.php';
 *   layout_start('Titolo pagina', 'nav-key-attivo');
 *   // ...contenuto pagina...
 *   layout_end();
 *
 * $active: chiave del link di navigazione attivo
 *   'home' | 'partecipanti' | 'albo-doro' | 'competizioni'
 *   | 'rosa' | 'galleria' | 'storia' | 'regolamento' | 'contattaci'
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$_NAV_LINKS = [
    'storia'        => ['url' => BASE_PATH . 'public/storia.php',        'label' => 'Storia'],
    'partecipanti'  => ['url' => BASE_PATH . 'public/partecipanti.php',  'label' => 'Partecipanti'],
    'albo-doro'     => ['url' => BASE_PATH . 'public/albo-doro.php',     'label' => "Albo d'Oro"],
    'galleria'      => ['url' => BASE_PATH . 'public/galleria.php',      'label' => 'Galleria'],
    'regolamento'   => ['url' => BASE_PATH . 'public/regolamento.php',   'label' => 'Regolamento'],
    'contattaci'    => ['url' => BASE_PATH . 'public/contattaci.php',    'label' => 'Contattaci'],
];

/**
 * Apre il documento HTML fino al tag <main>.
 *
 * @param string $title      Titolo della pagina (senza suffisso)
 * @param string $active_nav Chiave del link attivo nella navbar
 * @param array  $options    Opzioni extra: 'description', 'og_image', 'body_class'
 */
function layout_start(string $title, string $active_nav = '', array $options = []): void
{
    global $_NAV_LINKS;

    $bp         = defined('BASE_PATH') ? BASE_PATH : '/';
    $site_name  = 'FantaScarrupat';

    // Normalizza titolo/descrizione (alcune pagine passano testo già con entità HTML)
    $clean_title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES, 'UTF-8'));
    $clean_desc  = trim(html_entity_decode(strip_tags(
        (string)($options['description'] ?? "La lega di fantacalcio privata FantaScarrupat, nata nel 2016: storia, albo d'oro, partecipanti, regolamento, classifiche e galleria fotografica.")
    ), ENT_QUOTES, 'UTF-8'));

    $full_title = $clean_title === '' ? $site_name : ($clean_title . ' · ' . $site_name);
    $canonical  = $options['canonical'] ?? fs_canonical();
    if (!preg_match('#^https?://#i', (string)$canonical)) $canonical = fs_url((string)$canonical);
    $og_image_default = empty($options['og_image']);
    $og_image   = fs_url($options['og_image'] ?? 'assets/img/og-default.png');
    $og_type    = $options['og_type'] ?? 'website';
    $robots     = $options['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $login_url  = $bp . 'login.php';
    $home_url   = fs_url(); // homepage canonica sulla radice "/"

    // ── JSON-LD: grafo strutturato (Organization → WebSite → WebPage [+breadcrumb] [+nodi pagina]) ──
    $org = [
        '@type' => 'SportsOrganization',
        '@id'   => fs_url() . '#organization',
        'name'  => 'FantaScarrupat',
        'alternateName' => 'Fanta Scàrrupat',
        'url'   => $home_url,
        'logo'  => ['@type' => 'ImageObject', 'url' => fs_url('assets/img/logo.png')],
        'image' => fs_url('assets/img/logo.png'),
        'foundingDate' => '2016',
        'sport' => 'Fantacalcio',
        'description' => 'Lega privata di fantacalcio fondata nel 2016, con campionato, coppe, gironi e tornei a eliminazione.',
        'email' => 'antonio.digi30@hotmail.com',
    ];
    $website = [
        '@type' => 'WebSite',
        '@id'   => fs_url() . '#website',
        'url'   => $home_url,
        'name'  => 'FantaScarrupat',
        'description' => $clean_desc,
        'inLanguage' => 'it-IT',
        'publisher' => ['@id' => fs_url() . '#organization'],
    ];
    $webpage = [
        '@type' => 'WebPage',
        '@id'   => $canonical . '#webpage',
        'url'   => $canonical,
        'name'  => $full_title,
        'description' => $clean_desc,
        'isPartOf'  => ['@id' => fs_url() . '#website'],
        'about'     => ['@id' => fs_url() . '#organization'],
        'inLanguage'=> 'it-IT',
        'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $og_image],
    ];
    $graph = [$org, $website];
    if (!empty($options['breadcrumb']) && is_array($options['breadcrumb'])) {
        $items = [];
        foreach (array_values($options['breadcrumb']) as $i => $bc) {
            $li = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => (string)$bc['name']];
            if (!empty($bc['url'])) $li['item'] = fs_url($bc['url'] === 'public/index.php' ? '' : $bc['url']);
            $items[] = $li;
        }
        $webpage['breadcrumb'] = ['@id' => $canonical . '#breadcrumb'];
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $items];
    }
    $graph[] = $webpage;
    if (!empty($options['jsonld']) && is_array($options['jsonld'])) {
        foreach ($options['jsonld'] as $node) { if (is_array($node)) $graph[] = $node; }
    }
    $jsonld_str = json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
    );
    ?>
<!DOCTYPE html>
<html lang="it" data-theme="dark" data-zone="public">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($full_title) ?></title>
  <meta name="description" content="<?= htmlspecialchars($clean_desc) ?>">
  <meta name="robots" content="<?= htmlspecialchars($robots) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
  <meta name="author" content="FantaScarrupat">
  <meta name="theme-color" content="#14183E">

  <!-- Open Graph (Facebook, WhatsApp, Telegram, LinkedIn…) -->
  <meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
  <meta property="og:site_name" content="FantaScarrupat">
  <meta property="og:locale" content="it_IT">
  <meta property="og:title" content="<?= htmlspecialchars($full_title) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($clean_desc) ?>">
  <meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
  <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
  <meta property="og:image:alt" content="FantaScarrupat — lega di fantacalcio dal 2016">
<?php if ($og_image_default): ?>
  <meta property="og:image:width" content="1731">
  <meta property="og:image:height" content="909">
  <meta property="og:image:type" content="image/png">
<?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($full_title) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($clean_desc) ?>">
  <meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

  <!-- Icone & manifest -->
  <link rel="icon" type="image/png" href="<?= $bp ?>assets/img/favicon.png">
  <link rel="apple-touch-icon" href="<?= $bp ?>assets/img/logo.png">
  <link rel="manifest" href="<?= $bp ?>site.webmanifest">

  <!-- Bootstrap 5.3 (self-hosted) -->
  <link rel="stylesheet" href="<?= $bp ?>assets/vendor/bootstrap/bootstrap.min.css">

  <!-- Font self-hosted (GDPR: nessuna richiesta a Google) -->
  <link rel="stylesheet" href="<?= $bp ?>assets/css/material-symbols.css">
  <link rel="stylesheet" href="<?= $bp ?>assets/css/fonts-serif.css">

  <!-- Design system -->
  <link rel="stylesheet" href="<?= $bp ?>assets/css/main.css?v=<?= ASSET_VER ?>">
  <link rel="stylesheet" href="<?= $bp ?>assets/css/public.css?v=<?= ASSET_VER ?>">

  <!-- Theme (applicato subito, evita flash) -->
  <script src="<?= $bp ?>assets/js/theme.js"></script>

  <!-- Dati strutturati (Schema.org) per SEO e motori generativi (GEO) -->
  <script type="application/ld+json"><?= $jsonld_str ?></script>
</head>
<body<?= isset($options['body_class']) ? ' class="' . htmlspecialchars($options['body_class']) . '"' : '' ?>>

<nav class="fs-navbar" role="navigation" aria-label="Navigazione principale">
  <div class="container">

    <!-- Brand -->
    <a href="<?= $bp ?>" class="fs-navbar__brand">
      Fanta<em>Scarrupat</em>
    </a>

    <!-- Toggle mobile -->
    <button class="fs-navbar__toggle"
            aria-label="Apri menu"
            aria-expanded="false"
            aria-controls="navbar-nav"
            id="navbar-toggle">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Links -->
    <nav id="navbar-nav" class="fs-navbar__nav" aria-label="Links">
      <?php foreach ($_NAV_LINKS as $key => $link): ?>
        <a href="<?= htmlspecialchars($link['url']) ?>"
           class="fs-navbar__link<?= $active_nav === $key ? ' active' : '' ?>"
           <?= $active_nav === $key ? 'aria-current="page"' : '' ?>>
          <?= htmlspecialchars($link['label']) ?>
        </a>
      <?php endforeach; ?>
      <!-- Accedi: visibile solo nella tendina mobile -->
      <a href="<?= $login_url ?>" class="fs-navbar__link fs-navbar__nav-login">Accedi</a>
    </nav>

    <!-- Accedi -->
    <div class="fs-navbar__actions">
      <a href="<?= $login_url ?>"
         class="btn btn-primary btn-sm btn-pill">
        <span class="material-symbols-rounded" style="font-size:15px;font-variation-settings:'FILL' 1">lock</span>
        Accedi
      </a>
    </div>

  </div>
</nav>

<main id="main-content">
    <?php
}

/**
 * Chiude main, aggiunge footer e script, chiude </html>.
 */
function layout_end(): void
{
    $bp = defined('BASE_PATH') ? BASE_PATH : '/';
    $year = date('Y');
    ?>
</main>

<footer class="fs-footer" role="contentinfo">
  <div class="container">
    <div class="fs-footer__grid">

      <!-- Brand column -->
      <div>
        <div class="fs-footer__brand">FantaScarrupat</div>
        <p class="fs-footer__tagline">
          Benvenuti nel mondo straordinario di Fantascarrupat, dove la passione per il
          calcio si trasforma in una vera e propria leggenda! Esplora la nostra storia,
          scopri quali sono state le sfide più affascinanti e chi è il campione in carica!
        </p>
      </div>

      <!-- Nav column -->
      <div>
        <div class="fs-footer__heading">Sezioni</div>
        <nav class="fs-footer__links" aria-label="Footer navigation">
          <a href="<?= $bp ?>public/storia.php">Storia</a>
          <a href="<?= $bp ?>public/partecipanti.php">Partecipanti</a>
          <a href="<?= $bp ?>public/albo-doro.php">Albo d'Oro</a>
          <a href="<?= $bp ?>public/galleria.php">Galleria</a>
        </nav>
      </div>

      <!-- More column -->
      <div>
        <div class="fs-footer__heading">Altro</div>
        <nav class="fs-footer__links" aria-label="Footer navigation secondario">
          <a href="<?= $bp ?>public/regolamento.php">Regolamento</a>
          <a href="<?= $bp ?>public/contattaci.php">Contattaci</a>
          <a href="<?= $bp ?>public/privacy.php">Privacy &amp; Cookie</a>
          <a href="<?= $bp ?>login.php">Area riservata</a>
        </nav>
      </div>

    </div>

    <div class="fs-footer__bottom">
      <span>© 2016-<?= $year ?> FantaScarrupat. Tutti i diritti riservati.</span>
    </div>
  </div>
</footer>

<!-- Bootstrap JS (self-hosted) -->
<script src="<?= $bp ?>assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>

<!-- Navbar toggle script -->
<script>
(function () {
  var btn = document.getElementById('navbar-toggle');
  var nav = document.getElementById('navbar-nav');
  if (!btn || !nav) return;
  btn.addEventListener('click', function () {
    var open = nav.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  // Chiudi al click fuori
  document.addEventListener('click', function (e) {
    if (!btn.contains(e.target) && !nav.contains(e.target)) {
      nav.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
    }
  });
}());
</script>

</body>
</html>
    <?php
}
