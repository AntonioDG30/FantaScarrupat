<?php
/**
 * public/galleria.php — Galleria fotografica
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

$db = getDB();

$foto = $db->query("
    SELECT id_immagine, percorso, didascalia, uploaded_at
    FROM immagine
    WHERE is_visibile = 1
    ORDER BY uploaded_at DESC, id_immagine DESC
")->fetchAll();

layout_start('Galleria', 'galleria', [
    'description' => 'La galleria fotografica di FantaScarrupat: aste, cerimonie di premiazione e momenti della lega.',
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => 'Galleria']],
]);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Galleria">
  <div class="container">
    <p class="hero__eyebrow">Momenti</p>
    <h1 class="hero__title">Galleria</h1>
    <p class="hero__subtitle" style="max-width:45ch">
      Aste, premiazioni, raduni. La memoria fotografica della lega,
      dai primi anni ad oggi.
    </p>
    <div class="hero__stats">
      <div>
        <div class="hero__stat-value"><?= count($foto) ?></div>
        <div class="hero__stat-label">Foto</div>
      </div>
    </div>
  </div>
</section>

<!-- ── Griglia foto ───────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">

    <?php if (empty($foto)): ?>
    <div style="text-align:center;padding:var(--sp-16) 0;color:var(--c-text-muted)">
      <span class="material-symbols-rounded"
            style="font-size:48px;display:block;margin-bottom:var(--sp-4);opacity:0.4">
        photo_library
      </span>
      <p>Nessuna foto disponibile al momento.</p>
    </div>
    <?php else: ?>

    <!-- Lightbox: apre immagine a schermo intero -->
    <div id="lightbox"
         role="dialog"
         aria-modal="true"
         aria-label="Visualizzatore immagine"
         style="display:none;position:fixed;inset:0;z-index:200;
                background:oklch(0% 0 0 / 0.92);
                flex-direction:column;align-items:center;justify-content:center;
                padding:var(--sp-6)">
      <button onclick="closeLightbox()"
              aria-label="Chiudi"
              style="position:absolute;top:var(--sp-5);right:var(--sp-5);
                     background:oklch(100% 0 0 / 0.12);border:none;
                     color:oklch(95% 0.005 250);border-radius:50%;
                     width:44px;height:44px;cursor:pointer;
                     display:flex;align-items:center;justify-content:center;
                     font-size:1.25rem;transition:background var(--dur-fast) var(--ease-out)"
              onmouseover="this.style.background='oklch(100% 0 0 / 0.22)'"
              onmouseout="this.style.background='oklch(100% 0 0 / 0.12)'">
        <span class="material-symbols-rounded">close</span>
      </button>
      <img id="lightbox-img" src="" alt=""
           style="max-width:90vw;max-height:80vh;object-fit:contain;
                  border-radius:var(--r-lg)">
      <p id="lightbox-caption"
         style="margin-top:var(--sp-4);color:oklch(75% 0.010 250);
                font-size:0.9375rem;text-align:center;max-width:60ch"></p>
    </div>

    <div class="galleria-grid" role="list">
      <?php foreach ($foto as $f):
        $url = htmlspecialchars(GALLERY_URL . $f['percorso']);
        $cap = htmlspecialchars($f['didascalia'] ?? '');
      ?>
      <figure class="galleria-item" role="listitem">
        <button onclick="openLightbox(<?= htmlspecialchars(json_encode($url)) ?>, <?= htmlspecialchars(json_encode($cap)) ?>)"
                style="width:100%;background:none;border:none;padding:0;cursor:zoom-in"
                aria-label="Ingrandisci: <?= $cap ?>">
          <img src="<?= $url ?>"
               alt="<?= $cap ?>"
               loading="lazy"
               style="width:100%;height:auto;display:block"
               onerror="this.closest('.galleria-item').style.display='none'">
        </button>
        <?php if ($f['didascalia']): ?>
        <figcaption style="padding:var(--sp-3) var(--sp-4);
                           font-size:0.8125rem;color:var(--c-text-muted);
                           line-height:1.4">
          <?= $cap ?>
        </figcaption>
        <?php endif; ?>
      </figure>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

  </div>
</section>

<script>
function openLightbox(url, caption) {
  var lb  = document.getElementById('lightbox');
  var img = document.getElementById('lightbox-img');
  var cap = document.getElementById('lightbox-caption');
  img.src   = url;
  img.alt   = caption;
  cap.textContent = caption;
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').style.display = 'none';
  document.getElementById('lightbox-img').src = '';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeLightbox();
});
document.getElementById('lightbox').addEventListener('click', function(e) {
  if (e.target === this) closeLightbox();
});
</script>

<?php layout_end(); ?>
