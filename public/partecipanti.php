<?php
/**
 * public/partecipanti.php — Tutte le fantasquadre
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

// Tutte le fantasquadra con conteggio trofei e rosa più recente
$squadre = $db->query("
    SELECT
        f.nome_fantasquadra,
        f.fantallenatore,
        f.immagine_fantallenatore,
        f.scudetto,
        f.flag_attuale,
        COUNT(DISTINCT cd.id_competizione_disputata) AS trofei,
        GROUP_CONCAT(
            DISTINCT cd.nome_competizione
            ORDER BY cd.anno DESC
            SEPARATOR '||'
        ) AS competizioni_vinte,
        MAX(r.anno) AS ultimo_anno_rosa
    FROM fantasquadra f
    LEFT JOIN competizione_disputata cd ON cd.vincitore = f.nome_fantasquadra
    LEFT JOIN rosa r ON r.nome_fantasquadra = f.nome_fantasquadra
    GROUP BY f.nome_fantasquadra, f.fantallenatore, f.immagine_fantallenatore,
             f.scudetto, f.flag_attuale
    ORDER BY f.flag_attuale DESC, trofei DESC, f.nome_fantasquadra
")->fetchAll();

layout_start('Partecipanti', 'partecipanti', [
    'description' => 'Le fantasquadre della lega FantaScarrupat: partecipanti attuali e storici.',
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => 'Partecipanti']],
]);

$attuali = array_filter($squadre, fn($s) => (bool)$s['flag_attuale']);
$storici  = array_filter($squadre, fn($s) => !(bool)$s['flag_attuale']);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Partecipanti">
  <div class="container">
    <p class="hero__eyebrow">La lega</p>
    <h1 class="hero__title">Partecipanti</h1>
    <p class="hero__subtitle" style="max-width:45ch">
      Conosci i giocatori attuali e passati, dai veterani ai nuovi arrivati,
      che si sfidano ogni anno per la gloria finale.
    </p>
  </div>
</section>


<!-- ── Partecipanti attuali ───────────────────────────────────────────────────── -->
<section class="section" aria-labelledby="attuali-title">
  <div class="container">

    <div class="section-heading">
      <h2 id="attuali-title">Partecipanti attivi</h2>
      <p>Le <?= count($attuali) ?> squadre che compongono la lega nella stagione corrente.</p>
    </div>

    <div class="partecipanti-grid">
      <?php foreach ($attuali as $s):
        $coach_url = img_url(COACHES_URL, $s['immagine_fantallenatore'] ?? '');
        $badge_url = img_url(TEAMS_URL,   $s['scudetto'] ?? '');
        $initials  = strtoupper(mb_substr($s['nome_fantasquadra'], 0, 2));
        $ha_rosa   = $s['ultimo_anno_rosa'] !== null;
      ?>
      <div id="<?= htmlspecialchars(urlencode($s['nome_fantasquadra'])) ?>">
        <div class="partecipante-card">

          <!-- ── Area foto ── -->
          <div class="partecipante-card__photo-wrap">

            <!-- Placeholder iniziali (sempre presente dietro) -->
            <div class="partecipante-card__photo-ph" aria-hidden="true">
              <?= htmlspecialchars($initials) ?>
            </div>

            <!-- Foto coach (assoluta sopra il placeholder) -->
            <?php if ($coach_url !== ''): ?>
            <img src="<?= $coach_url ?>"
                 alt="<?= htmlspecialchars($s['fantallenatore']) ?>"
                 class="partecipante-card__photo"
                 loading="lazy"
                 onerror="this.style.display='none'">
            <?php endif; ?>

            <!-- Badge scudetto squadra in basso a destra -->
            <?php if ($badge_url !== ''): ?>
            <img src="<?= $badge_url ?>"
                 alt="Stemma <?= htmlspecialchars($s['nome_fantasquadra']) ?>"
                 class="partecipante-card__badge"
                 loading="lazy"
                 onerror="this.style.display='none'">
            <?php endif; ?>

          </div><!-- /photo-wrap -->

          <!-- ── Corpo testuale ── -->
          <div class="partecipante-card__body">

            <div class="partecipante-card__nome">
              <?= htmlspecialchars($s['nome_fantasquadra']) ?>
            </div>
            <div class="partecipante-card__squadra">
              <?= htmlspecialchars($s['fantallenatore']) ?>
            </div>

            <div class="partecipante-card__trofei">
              <?php if ((int)$s['trofei'] > 0): ?>
                <span class="material-symbols-rounded"
                      style="font-size:13px;font-variation-settings:'FILL' 1;color:var(--c-gold)">
                  emoji_events
                </span>
                <?= $s['trofei'] ?> <?= (int)$s['trofei'] === 1 ? 'trofeo' : 'trofei' ?>
              <?php else: ?>
                <span style="color:var(--c-text-faint)">Nessun trofeo</span>
              <?php endif; ?>
            </div>

            <?php if ($ha_rosa): ?>
            <a href="<?= BASE_PATH ?>public/dettagli-rosa.php?squadra=<?= urlencode($s['nome_fantasquadra']) ?>&anno=<?= $s['ultimo_anno_rosa'] ?>"
               class="btn btn-outline btn-sm"
               style="margin-top:var(--sp-4);align-self:flex-start">
              Rosa <?= anno_to_stagione((int)$s['ultimo_anno_rosa']) ?>
              <span class="material-symbols-rounded" style="font-size:14px">arrow_forward</span>
            </a>
            <?php endif; ?>

          </div><!-- /body -->

        </div><!-- /partecipante-card -->
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>


<!-- ── Partecipanti storici ───────────────────────────────────────────────────── -->
<?php if (!empty($storici)): ?>
<section class="section section-alt" aria-labelledby="storici-title">
  <div class="container">

    <div class="section-heading">
      <h2 id="storici-title">Ex partecipanti</h2>
      <p>Squadre che hanno fatto parte della lega nelle stagioni precedenti.</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:var(--sp-4)">
      <?php foreach ($storici as $s):
        $badge_url = img_url(TEAMS_URL, $s['scudetto'] ?? '');
        $initials  = strtoupper(mb_substr($s['nome_fantasquadra'], 0, 2));
      ?>
      <div id="<?= htmlspecialchars(urlencode($s['nome_fantasquadra'])) ?>"
           style="display:flex;align-items:center;gap:var(--sp-4);
                  padding:var(--sp-4) var(--sp-5);
                  background:var(--c-bg-raised);
                  border:1px solid var(--c-border);
                  border-radius:var(--r-xl)">

        <!-- Logo squadra (o iniziali come fallback) -->
        <div style="width:52px;height:52px;flex-shrink:0;position:relative">
          <?php if ($badge_url !== ''): ?>
            <img src="<?= $badge_url ?>"
                 alt="Stemma <?= htmlspecialchars($s['nome_fantasquadra']) ?>"
                 style="width:100%;height:100%;object-fit:contain;padding:5px;
                        border-radius:var(--r-md);
                        background:var(--c-bg-inset);
                        border:1px solid var(--c-border)"
                 loading="lazy"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div style="display:none;width:52px;height:52px;border-radius:50%;
                        align-items:center;justify-content:center;
                        background:oklch(from var(--c-text-muted) l c h / 0.10);
                        font-family:var(--font-heading);font-size:1rem;
                        font-weight:700;color:var(--c-text-muted)">
              <?= htmlspecialchars($initials) ?>
            </div>
          <?php else: ?>
            <div style="display:flex;width:52px;height:52px;border-radius:50%;
                        align-items:center;justify-content:center;
                        background:oklch(from var(--c-text-muted) l c h / 0.10);
                        font-family:var(--font-heading);font-size:1rem;
                        font-weight:700;color:var(--c-text-muted)">
              <?= htmlspecialchars($initials) ?>
            </div>
          <?php endif; ?>
        </div>

        <div style="min-width:0">
          <div style="font-family:var(--font-display);font-weight:600;font-style:italic;
                      font-size:1.05rem;color:var(--c-text);margin-bottom:2px;
                      letter-spacing:-0.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($s['nome_fantasquadra']) ?>
          </div>
          <div style="font-size:0.8125rem;color:var(--c-text-muted)">
            <?= htmlspecialchars($s['fantallenatore']) ?>
          </div>
          <?php if ((int)$s['trofei'] > 0): ?>
          <div style="font-size:0.75rem;color:var(--c-gold);margin-top:3px;
                      display:flex;align-items:center;gap:4px">
            <span class="material-symbols-rounded"
                  style="font-size:12px;font-variation-settings:'FILL' 1">emoji_events</span>
            <?= $s['trofei'] ?> <?= (int)$s['trofei'] === 1 ? 'trofeo' : 'trofei' ?>
          </div>
          <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>
<?php endif; ?>

<?php layout_end(); ?>
