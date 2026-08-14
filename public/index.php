<?php
/**
 * public/index.php — Homepage
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

/* ── Stats globali ──────────────────────────────────────────────────────── */
$stats = $db->query("
    SELECT
        COUNT(DISTINCT anno)                                        AS stagioni,
        COUNT(*)                                                    AS trofei_assegnati,
        (SELECT COUNT(*) FROM fantasquadra WHERE flag_attuale = 1) AS partecipanti_attuali,
        (SELECT COUNT(*) FROM fantasquadra)                        AS fantallenatori_totali
    FROM competizione_disputata
")->fetch();

/* ── Campione ultima stagione ───────────────────────────────────────────── */
$ultimi = $db->query("
    SELECT cd.anno, cd.nome_competizione, cd.vincitore, f.scudetto
    FROM competizione_disputata cd
    LEFT JOIN fantasquadra f ON f.nome_fantasquadra = cd.vincitore
    WHERE cd.anno = (SELECT MAX(anno) FROM competizione_disputata)
      AND cd.vincitore IS NOT NULL
    ORDER BY CASE cd.nome_competizione WHEN 'Serie A' THEN 1 ELSE 2 END
")->fetchAll();

$anno_recente = !empty($ultimi) ? (int)$ultimi[0]['anno'] : LAST_ANNO;

$campione = null;
foreach ($ultimi as $v) {
    if ($v['nome_competizione'] === 'Serie A') { $campione = $v; break; }
}

$totale_partite = (int)$db->query(
    "SELECT COUNT(*) FROM partita_avvessario"
)->fetchColumn();

/* ── Classifica trofei (partecipanti attuali) ──────────────────────────── */
$classifica_trofei = $db->query("
    SELECT f.nome_fantasquadra, f.scudetto,
           COUNT(cd.id_competizione_disputata) AS trofei
    FROM fantasquadra f
    LEFT JOIN competizione_disputata cd ON cd.vincitore = f.nome_fantasquadra
    WHERE f.flag_attuale = 1
    GROUP BY f.nome_fantasquadra, f.scudetto
    ORDER BY trofei DESC, f.nome_fantasquadra ASC
")->fetchAll();

/* ── Timeline campioni Serie A ──────────────────────────────────────────── */
$timeline_serie_a = $db->query("
    SELECT cd.anno, cd.vincitore, f.scudetto
    FROM competizione_disputata cd
    LEFT JOIN fantasquadra f ON f.nome_fantasquadra = cd.vincitore
    WHERE cd.nome_competizione = 'Serie A' AND cd.vincitore IS NOT NULL
    ORDER BY cd.anno ASC
")->fetchAll();

$tl_last = !empty($timeline_serie_a)
           ? (int)$timeline_serie_a[count($timeline_serie_a) - 1]['anno']
           : $anno_recente;

layout_start('La lega di fantacalcio privata dal 2016', 'home', [
    'description' => "FantaScarrupat: la lega di fantacalcio privata tra amici, attiva dal 2016. Storia, albo d'oro dei vincitori, partecipanti, regolamento, classifiche, tabelloni a eliminazione e galleria fotografica.",
    'canonical'   => fs_url(), // la homepage è canonica sull'URL radice "/"
]);
?>

<!-- ── COPERTINA ─────────────────────────────────────────────────────────── -->
<section class="hs-cover" data-year="2016">
  <div class="container hs-cover__inner">

    <!-- Testo: titolo + fatti + CTA -->
    <div class="hs-cover__main">
      <p class="hs-cover__eyebrow">Lega privata · dal 2016</p>
      <h1 class="hs-cover__title">Fanta<em>Scarrupat</em></h1>
      <div class="hs-cover__rule" aria-hidden="true"></div>
      <p class="hs-cover__facts">
        <span><strong><?= (int)$stats['stagioni'] ?></strong> stagioni</span>
        <span class="hs-cover__sep" aria-hidden="true">·</span>
        <span><strong><?= (int)$stats['trofei_assegnati'] ?></strong> trofei</span>
        <span class="hs-cover__sep" aria-hidden="true">·</span>
        <span><strong><?= $totale_partite ?></strong> partite</span>
        <span class="hs-cover__sep" aria-hidden="true">·</span>
        <span><strong><?= (int)$stats['partecipanti_attuali'] ?></strong> squadre</span>
      </p>
      <div class="hs-cover__ctas">
        <a href="<?= BASE_PATH ?>public/albo-doro.php"
           class="btn btn-gold btn-lg btn-pill">Albo d'Oro</a>
        <a href="<?= BASE_PATH ?>public/partecipanti.php"
           class="btn btn-outline-warm btn-lg btn-pill">I Partecipanti</a>
      </div>
    </div>

    <!-- Campione: testo puro, niente box né bordi -->
    <?php if ($campione):
      $logo_url = img_url(TEAMS_URL, $campione['scudetto'] ?? '');
    ?>
    <div class="hs-cover__champion">
      <p class="hs-cover__champ-label">Campioni <?= htmlspecialchars(anno_to_stagione($anno_recente)) ?></p>
      <?php if ($logo_url !== ''): ?>
      <img class="hs-cover__champ-logo"
           src="<?= htmlspecialchars($logo_url) ?>"
           alt=""
           loading="eager"
           onerror="this.style.display='none'">
      <?php endif; ?>
      <p class="hs-cover__champ-name"><?= htmlspecialchars($campione['vincitore']) ?></p>
    </div>
    <?php endif; ?>

  </div>
</section>


<!-- ── STORIA ────────────────────────────────────────────────────────────── -->
<section class="section section-alt" aria-labelledby="hs-storia-title">
  <div class="container">
    <div class="hs-storia__layout">

      <!-- Logo / immagine -->
      <div class="hs-storia__media" aria-hidden="true">
        <img src="<?= BASE_PATH ?>assets/img/logo.png"
             alt="" loading="lazy">
      </div>

      <!-- Contenuto -->
      <div class="hs-storia__content">
        <p class="hs-eyebrow">La Nostra Storia</p>
        <h2 id="hs-storia-title" class="hs-arch-heading">
          Dal 2016, su un autobus a Caserta.
        </h2>
        <p class="hs-storia__text">
          Nel lontano 2016, da un nucleo di sei giovani impavidi, dalla mente fervida di
          passione calcistica, sorse un'idea destinata a plasmare il loro destino e a
          instaurare un legame indissolubile. Giovani ardimentosi, con età compresa tra i
          15 e i 16 anni, affrontavano insieme i banchi della scuola, una piccola
          istituzione chiamata "ITI &amp; LS F. Giordani" a Caserta. Ma non era solo
          l'istruzione a unirli; era il calcio, una fiamma che ardeva nei loro cuori,
          che li spinse oltre il semplice ruolo di studenti.
        </p>
        <p class="hs-storia__text" style="margin-top:var(--sp-4)">
          Fu su un mezzo di trasporto, un modesto autobus della ditta "Castaldo", che il
          destino tessé la trama della loro epopea. L'autobus, grigio e anonimo agli occhi
          dei più, divenne il loro santuario, il palcoscenico delle loro gesta e dei loro
          battibecchi. Era lì che nacque la Lega FantaScarrupat, una congrega di amici
          uniti da un'unica passione, un rifugio sicuro e caloroso che li accolse durante
          gli anni burrascosi delle superiori.
        </p>

        <ul class="hs-storia__values">
          <li><strong>Passione</strong>: la nostra lega è alimentata dalla pura passione per il calcio</li>
          <li><strong>Amicizia</strong>: è il collante che tiene insieme la Lega FantaScarrupat</li>
          <li><strong>Innovazione</strong>: siamo sempre in cerca di nuove idee e soluzioni</li>
        </ul>

        <div class="hs-storia__stats">
          <div class="hs-storia__stat">
            <span class="hs-storia__stat-n"><?= (int)$stats['stagioni'] ?></span>
            <span class="hs-storia__stat-l">Stagioni</span>
          </div>
          <div class="hs-storia__stat">
            <span class="hs-storia__stat-n"><?= (int)$stats['fantallenatori_totali'] ?></span>
            <span class="hs-storia__stat-l">Fantallenatori storici</span>
          </div>
          <div class="hs-storia__stat">
            <span class="hs-storia__stat-n"><?= (int)$stats['trofei_assegnati'] ?></span>
            <span class="hs-storia__stat-l">Trofei assegnati</span>
          </div>
        </div>

        <a href="<?= BASE_PATH ?>public/storia.php"
           class="btn btn-outline-warm btn-pill">Leggi di più</a>
      </div>

    </div>
  </div>
</section>


<!-- ── ARCHIVIO ──────────────────────────────────────────────────────────── -->
<section class="section section-alt" aria-labelledby="arch-title">
  <div class="container">
    <div class="hs-archivio__grid">

      <!-- Albo d'Oro Serie A: lista stagioni → vincitore -->
      <div>
        <p class="hs-eyebrow">Serie A</p>
        <h2 id="arch-title" class="hs-arch-heading">Albo d'Oro</h2>
        <div class="hs-winners" role="list">
          <?php foreach (array_reverse($timeline_serie_a) as $t):
            $is_cur  = ((int)$t['anno'] === $tl_last);
            $w_logo  = img_url(TEAMS_URL, $t['scudetto'] ?? '');
          ?>
          <div class="hs-winner-row<?= $is_cur ? ' hs-winner-row--current' : '' ?>"
               role="listitem">
            <span class="hs-winner__year">
              <?= htmlspecialchars(anno_to_stagione((int)$t['anno'])) ?>
            </span>
            <?php if ($w_logo !== ''): ?>
            <img class="hs-winner__logo"
                 src="<?= htmlspecialchars($w_logo) ?>"
                 alt="" loading="lazy"
                 onerror="this.style.visibility='hidden'">
            <?php else: ?>
            <span class="hs-winner__logo-ph" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="hs-winner__name"><?= htmlspecialchars($t['vincitore']) ?></span>
            <?php if ($is_cur): ?>
            <span class="hs-winner__badge">in carica</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Classifica trofei: partecipanti attuali per vittorie totali -->
      <div>
        <p class="hs-eyebrow">Tutti i trofei</p>
        <h2 class="hs-arch-heading" aria-label="Classifica trofei">Chi ha vinto</h2>
        <?php if (!empty($classifica_trofei)):
          $max_tr  = (int)$classifica_trofei[0]['trofei'];
          $prev_tr = null;
          $dr      = 0;
        ?>
        <ol class="hs-arch-standings" role="list">
          <?php foreach ($classifica_trofei as $sq):
            $tr      = (int)$sq['trofei'];
            if ($tr !== $prev_tr) $dr++;
            $is_lead = ($tr === $max_tr && $max_tr > 0);
            $is_zero = ($tr === 0);
            $l_url   = img_url(TEAMS_URL, $sq['scudetto'] ?? '');
            $cls     = 'hs-arch-standing';
            if ($is_lead) $cls .= ' hs-arch-standing--lead';
            if ($is_zero) $cls .= ' hs-arch-standing--zero';
            $prev_tr = $tr;
          ?>
          <li class="<?= $cls ?>">
            <span class="hs-arch-standing__rank"><?= $dr ?></span>
            <?php if ($l_url !== ''): ?>
            <img class="hs-arch-standing__logo"
                 src="<?= htmlspecialchars($l_url) ?>"
                 alt="" loading="lazy"
                 onerror="this.style.visibility='hidden'">
            <?php else: ?>
            <span class="hs-arch-standing__logo hs-arch-standing__logo--init" aria-hidden="true">
              <?= mb_strtoupper(mb_substr($sq['nome_fantasquadra'], 0, 2)) ?>
            </span>
            <?php endif; ?>
            <span class="hs-arch-standing__name">
              <?= htmlspecialchars($sq['nome_fantasquadra']) ?>
            </span>
            <span class="hs-arch-standing__count"><?= $tr ?></span>
          </li>
          <?php endforeach; ?>
        </ol>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>



<!-- ── ESPLORA / SERVIZI ──────────────────────────────────────────────────── -->
<section class="section section-alt" aria-labelledby="services-title">
  <div class="container">
    <div class="hs-services__outer">

      <!-- Intro — colonna sinistra su desktop -->
      <div class="hs-services__intro">
        <p class="hs-eyebrow">Esplora</p>
        <h2 id="services-title" class="hs-arch-heading">
          I Nostri<br>Servizi
        </h2>
        <p class="hs-services__body">
          Esplora la nostra sezione Servizi, il cuore del nostro sito di fantacalcio.
          Troverai il regolamento ufficiale, l'albo d'oro dei campioni, i partecipanti
          e una fotogallery emozionante. Benvenuto nel mondo del fantacalcio, dove ogni
          dettaglio conta per una stagione di gioco indimenticabile!
        </p>
      </div>

      <!-- Card grid — colonna destra su desktop, 2×2 staggered -->
      <div class="hs-services__grid">

        <!-- Colonna sinistra card -->
        <div class="hs-services__col">
          <a href="<?= BASE_PATH ?>public/albo-doro.php" class="hs-service-card">
            <div class="hs-service-card__icon">
              <img src="<?= BASE_PATH ?>assets/img/albodoro.png" alt="" loading="lazy">
            </div>
            <h3 class="hs-service-card__title">Albo d'Oro</h3>
            <p class="hs-service-card__desc">Una vetrina dei campioni. Scopri i vincitori delle passate stagioni, i loro trionfi e le loro sfide più affincenti che li hanno portati alla gloria eterna.</p>
            <span class="hs-service-card__cta">Approfondisci</span>
          </a>
          <a href="<?= BASE_PATH ?>public/partecipanti.php" class="hs-service-card">
            <div class="hs-service-card__icon">
              <img src="<?= BASE_PATH ?>assets/img/partecipanti.png" alt="" loading="lazy">
            </div>
            <h3 class="hs-service-card__title">Partecipanti</h3>
            <p class="hs-service-card__desc">La comunità che dà vita al gioco. Conosci i giocatori attuali e passati, dai veterani ai nuovi arrivati, che si sfidano ogni anno per la gloria finale.</p>
            <span class="hs-service-card__cta">Approfondisci</span>
          </a>
        </div>

        <!-- Colonna destra card — leggero offset su tablet+ -->
        <div class="hs-services__col hs-services__col--offset">
          <a href="<?= BASE_PATH ?>public/storia.php" class="hs-service-card">
            <div class="hs-service-card__icon">
              <img src="<?= BASE_PATH ?>assets/img/regolamento.png" alt="" loading="lazy">
            </div>
            <h3 class="hs-service-card__title">Storia</h3>
            <p class="hs-service-card__desc">Le origini della lega nel 2016: dall'autobus Castaldo a oggi, stagione per stagione.</p>
            <span class="hs-service-card__cta">Approfondisci</span>
          </a>
          <a href="<?= BASE_PATH ?>public/galleria.php" class="hs-service-card">
            <div class="hs-service-card__icon">
              <img src="<?= BASE_PATH ?>assets/img/galleria.png" alt="" loading="lazy">
            </div>
            <h3 class="hs-service-card__title">Foto Gallery</h3>
            <p class="hs-service-card__desc">Immagini che raccontano storie. Rivivi i momenti più emozionanti e indimenticabili del nostro fantacalcio attraverso una collezione esclusiva di fotografie che catturano l'essenza della competizione e della passione per il calcio.</p>
            <span class="hs-service-card__cta">Approfondisci</span>
          </a>
        </div>

      </div><!-- /hs-services__grid -->
    </div><!-- /hs-services__outer -->
  </div>
</section>

<?php layout_end(); ?>
