<?php
/**
 * public/storia.php — Storia della lega
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Numero stagioni per l'eyebrow dell'hero
$n_stagioni = (int)($db->query("
    SELECT COUNT(DISTINCT anno) FROM competizione_disputata
")->fetchColumn() ?? 0);

// ── Cronologia dinamica (da DB — admin può modificare in dashboard) ────────────
$tl_rows = $db->query("
    SELECT anno, etichetta, testo, icona
    FROM storia_timeline
    WHERE visibile = 1
    ORDER BY anno ASC, ordine ASC
")->fetchAll();
$tl_grouped = [];
foreach ($tl_rows as $row) {
    $tl_grouped[(int)$row['anno']][] = $row;
}

layout_start('Storia', 'storia', [
    'description' => 'La storia di FantaScarrupat: come è nata la lega, le stagioni, i personaggi.',
    'og_type'    => 'article',
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => 'Storia']],
]);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Storia">
  <div class="container">
    <p class="hero__eyebrow">Dal 2016 · <?= $n_stagioni ?> stagioni</p>
    <h1 class="hero__title">La storia</h1>
    <p class="hero__subtitle" style="max-width:52ch">
      Nel lontano 2016, da un nucleo di sei giovani impavidi, sorse la Lega FantaScarrupat.
      Riviviamo insieme i momenti principali.
    </p>
  </div>
</section>

<!-- ── Testo narrativo ────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container-narrow">
    <article style="line-height:1.8;font-size:1.0625rem">

      <p style="margin-bottom:var(--sp-6)">
        Nel lontano 2016, da un nucleo di sei giovani impavidi, dalla mente fervida di passione
        calcistica, sorse un'idea destinata a plasmare il loro destino e a instaurare un legame
        indissolubile. Giovani ardimentosi, con età compresa tra i 15 e i 16 anni, affrontavano
        insieme i banchi della scuola, una piccola istituzione chiamata "ITI &amp; LS F. Giordani"
        a Caserta. Ma non era solo l'istruzione a unirli: era il calcio, una fiamma che ardeva
        nei loro cuori, che li spinse oltre il semplice ruolo di studenti.
      </p>

      <p style="margin-bottom:var(--sp-6)">
        Fu su un mezzo di trasporto, un modesto autobus della ditta "Castaldo", che il destino
        tessé la trama della loro epopea. L'autobus, grigio e anonimo agli occhi dei più, divenne
        il loro santuario, il palcoscenico delle loro gesta e dei loro battibecchi. Era lì che
        nacque la Lega FantaScarrupat, una congrega di amici uniti da un'unica passione, un rifugio
        sicuro e caloroso che li accolse durante gli anni burrascosi delle superiori.
      </p>

      <p style="margin-bottom:0">
        Con il passare del tempo, la Lega si espanse, accogliendo nuovi adepti e salutando vecchi
        amici che intrapresero strade diverse. Ma l'essenza rimase intatta, intessuta di momenti
        indelebili e legami indissolubili. Ricordi di scherzi e litigi, di vittorie esaltanti e
        sconfitte amare, si fusero insieme, forgiando l'anima stessa della Lega.
      </p>

    </article>
  </div>
</section>

<!-- ── Cronologia ─────────────────────────────────────────────────────────────── -->
<?php if (!empty($tl_grouped)): ?>
<section class="section section-alt" aria-label="Cronologia">
  <div class="container-narrow">

    <div style="margin-bottom:var(--sp-10)">
      <p style="font-family:var(--font-heading);font-size:0.6rem;font-weight:700;
                letter-spacing:0.15em;text-transform:uppercase;
                color:var(--c-text-faint);margin-bottom:var(--sp-3)">
        Lega · Dal 2016
      </p>
      <h2 style="font-family:var(--font-display);font-size:clamp(1.75rem,4vw,2.25rem);
                 font-weight:600;font-style:italic;margin:0">
        Cronologia
      </h2>
    </div>

    <div class="st-tl">
      <?php foreach ($tl_grouped as $anno => $events): ?>
      <div class="st-tl__group">

        <div class="st-tl__year-row">
          <span class="st-tl__year"><?= (int)$anno ?></span>
        </div>

        <div class="st-tl__events">
          <?php foreach ($events as $ev): ?>
          <div class="st-tl__event">
            <div class="st-tl__event-icon-wrap" aria-hidden="true">
              <span class="material-symbols-rounded st-tl__event-icon"><?= htmlspecialchars($ev['icona']) ?></span>
            </div>
            <div class="st-tl__event-body">
              <div class="st-tl__event-label"><?= htmlspecialchars($ev['etichetta']) ?></div>
              <p class="st-tl__event-text"><?= nl2br(htmlspecialchars($ev['testo'])) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- ── CTA ────────────────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container" style="text-align:center;max-width:580px">
    <h2 style="margin-bottom:var(--sp-4)">L'archivio completo</h2>
    <p style="margin:0 auto var(--sp-8);color:var(--c-text-muted)">
      Tutte le edizioni di tutte le competizioni, con risultati e classifiche.
    </p>
    <div style="display:flex;gap:var(--sp-4);justify-content:center;flex-wrap:wrap">
      <a href="<?= BASE_PATH ?>public/albo-doro.php" class="btn btn-primary btn-pill">
        <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">emoji_events</span>
        Albo d'Oro
      </a>
      <a href="<?= BASE_PATH ?>public/partecipanti.php" class="btn btn-outline btn-pill">
        <span class="material-symbols-rounded" style="font-size:16px">groups</span>
        Partecipanti
      </a>
    </div>
  </div>
</section>

<?php layout_end(); ?>
