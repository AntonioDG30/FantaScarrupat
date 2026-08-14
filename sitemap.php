<?php
/**
 * sitemap.php — Sitemap XML dinamica per FantaScarrupat.
 * Servita anche come /sitemap.xml (rewrite in .htaccess).
 * Include le pagine pubbliche statiche + tutte le edizioni di competizione + le rose attuali.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex'); // la sitemap in sé non va indicizzata

$urls = [];
$add = function (string $rel, string $priority, string $changefreq, ?string $lastmod = null) use (&$urls): void {
    $urls[] = ['loc' => fs_url($rel), 'priority' => $priority, 'changefreq' => $changefreq, 'lastmod' => $lastmod];
};

$today = date('Y-m-d');

// ── Pagine pubbliche statiche ──────────────────────────────────────────────
$add('',                       '1.0', 'weekly',  $today); // homepage = URL radice "/"
$add('public/storia.php',      '0.7', 'yearly');
$add('public/albo-doro.php',   '0.8', 'monthly', $today);
$add('public/partecipanti.php','0.8', 'monthly');
$add('public/galleria.php',    '0.6', 'monthly');
$add('public/regolamento.php', '0.7', 'yearly');
$add('public/contattaci.php',  '0.4', 'yearly');
$add('public/privacy.php',     '0.3', 'yearly');

// ── Competizioni disputate (dettaglio) ─────────────────────────────────────
try {
    $db = getDB();
    $comp = $db->query("
        SELECT id_competizione_disputata, anno
          FROM competizione_disputata
         ORDER BY anno DESC, id_competizione_disputata DESC
    ")->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($comp as $c) {
        // priorità più alta per le stagioni con dati (>= 2024)
        $prio = ((int)$c['anno'] >= 2024) ? '0.7' : '0.4';
        $add('public/dettagli-competizione.php?id=' . (int)$c['id_competizione_disputata'], $prio, 'monthly');
    }

    // ── Rose delle fantasquadre attuali (per anno disponibile) ──────────────
    $rose = $db->query("
        SELECT DISTINCT r.nome_fantasquadra, r.anno
          FROM rosa r
          JOIN fantasquadra f ON f.nome_fantasquadra = r.nome_fantasquadra
         WHERE r.anno >= 2024
         ORDER BY r.anno DESC
    ")->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($rose as $rr) {
        $q = http_build_query(['squadra' => $rr['nome_fantasquadra'], 'anno' => (int)$rr['anno']]);
        $add('public/dettagli-rosa.php?' . $q, '0.5', 'monthly');
    }
} catch (\Throwable $e) {
    // In caso di problemi col DB, restituiamo comunque le pagine statiche.
    error_log('[FS sitemap] ' . $e->getMessage());
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
    if (!empty($u['lastmod']))    echo '    <lastmod>' . $u['lastmod'] . '</lastmod>' . "\n";
    if (!empty($u['changefreq'])) echo '    <changefreq>' . $u['changefreq'] . '</changefreq>' . "\n";
    if (!empty($u['priority']))   echo '    <priority>' . $u['priority'] . '</priority>' . "\n";
    echo '  </url>' . "\n";
}
echo '</urlset>' . "\n";
