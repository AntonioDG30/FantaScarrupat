<?php
/**
 * app/admin/sections/gallery.php — Gestione galleria foto
 */

// Paginazione
$page     = max(1, (int)($_GET['gpage'] ?? 1));
$per_page = 24;

$total = (int)$db->query("SELECT COUNT(*) FROM immagine")->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

$immagini = $db->prepare("
    SELECT id_immagine, percorso, didascalia, is_visibile, uploaded_at
      FROM immagine
     ORDER BY uploaded_at DESC, id_immagine DESC
     LIMIT ? OFFSET ?
");
$immagini->execute([$per_page, $offset]);
$immagini = $immagini->fetchAll(\PDO::FETCH_ASSOC);

$gallery_url = BASE_PATH . 'uploads/gallery/';
?>

<!-- ── Upload ─────────────────────────────────────────────────── -->
<div class="panel" style="margin-bottom:var(--sp-6)">
  <div class="panel__header">
    <span class="panel__title">
      <span class="material-symbols-rounded" style="font-size:15px;vertical-align:-3px;margin-right:var(--sp-2)" aria-hidden="true">upload</span>
      Carica foto
    </span>
    <span style="font-size:.75rem;color:var(--c-text-faint)">JPEG · PNG · WebP · max 5 MB · fino a 10 file</span>
  </div>
  <div class="panel__body">
    <form method="post"
          action="<?= BASE_PATH ?>api/admin/gallery.php"
          enctype="multipart/form-data"
          id="gallery-upload-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="action" value="upload">

      <!-- Dropzone -->
      <div id="dropzone"
           style="border:2px dashed var(--c-border-strong);border-radius:var(--r-lg);
                  padding:var(--sp-10) var(--sp-8);text-align:center;cursor:pointer;
                  transition:border-color .2s,background-color .2s;position:relative"
           role="button"
           tabindex="0"
           aria-label="Zona upload foto, clicca o trascina file">
        <span class="material-symbols-rounded"
              style="font-size:40px;color:var(--c-text-faint);display:block;margin-bottom:var(--sp-3)"
              aria-hidden="true">cloud_upload</span>
        <p style="color:var(--c-text-muted);margin:0 0 var(--sp-2);font-size:.9375rem">
          Trascina le foto qui o <span style="color:var(--c-accent);font-weight:600">sfoglia</span>
        </p>
        <p style="color:var(--c-text-faint);margin:0;font-size:.8rem">JPEG, PNG, WebP · max 5 MB per file</p>
        <input type="file" id="gallery-files" name="files[]"
               multiple accept="image/jpeg,image/png,image/webp"
               style="position:absolute;inset:0;opacity:0;cursor:pointer"
               aria-hidden="true">
      </div>

      <!-- Preview miniature selezionate -->
      <div id="file-preview"
           style="display:flex;flex-wrap:wrap;gap:var(--sp-3);margin-top:var(--sp-4)"></div>

      <div style="display:flex;align-items:center;gap:var(--sp-4);margin-top:var(--sp-5);flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
          <label class="adm-label" for="gallery-did">Didascalia comune <span style="color:var(--c-text-faint)">(opzionale)</span></label>
          <input type="text" id="gallery-did" name="didascalia"
                 class="adm-input" maxlength="255" placeholder="Es. Estate 2024">
        </div>
        <div style="padding-bottom:1px">
          <button type="submit" id="btn-upload" class="btn-sm btn-primary" disabled>
            <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">upload</span>
            Carica <span id="upload-count"></span>
          </button>
        </div>
      </div>

      <!-- Progress bar -->
      <div id="upload-progress"
           style="display:none;margin-top:var(--sp-4)">
        <div style="height:4px;background:var(--c-border);border-radius:var(--r-pill);overflow:hidden">
          <div id="progress-bar"
               style="height:100%;width:0%;background:var(--c-accent);transition:width .3s"></div>
        </div>
        <p id="progress-msg" style="font-size:.8125rem;color:var(--c-text-muted);margin-top:var(--sp-2)"></p>
      </div>
    </form>
  </div>
</div>

<!-- ── Griglia foto ────────────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-4)">
  <h2 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:700;color:var(--c-text);margin:0">
    Galleria
    <span style="font-size:.9rem;font-weight:400;color:var(--c-text-muted);margin-left:var(--sp-2)"><?= $total ?> foto</span>
  </h2>
</div>

<?php if (empty($immagini)): ?>
<div style="text-align:center;padding:var(--sp-16);color:var(--c-text-muted)">
  <span class="material-symbols-rounded" style="font-size:48px;display:block;margin-bottom:var(--sp-4);opacity:.3" aria-hidden="true">photo_library</span>
  Nessuna foto ancora. Carica le prime immagini.
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--sp-4)"
     id="gallery-grid">
  <?php foreach ($immagini as $img):
      $src = htmlspecialchars($gallery_url . rawurlencode($img['percorso']));
      $visible = (bool)$img['is_visibile'];
  ?>
  <div class="gallery-card"
       data-id="<?= (int)$img['id_immagine'] ?>"
       style="background:var(--c-bg-card);border:1px solid var(--c-border);border-radius:var(--r-lg);
              overflow:hidden;position:relative;<?= !$visible ? 'opacity:.5' : '' ?>">

    <!-- Foto -->
    <div style="aspect-ratio:4/3;overflow:hidden;background:var(--c-bg-alt)">
      <img src="<?= $src ?>"
           alt="<?= htmlspecialchars($img['didascalia'] ?? '') ?>"
           loading="lazy"
           style="width:100%;height:100%;object-fit:cover;display:block">
    </div>

    <!-- Overlay azioni -->
    <div style="position:absolute;top:var(--sp-2);right:var(--sp-2);display:flex;gap:var(--sp-1)">
      <!-- Toggle visibilità -->
      <button class="btn-toggle-vis"
              data-id="<?= (int)$img['id_immagine'] ?>"
              data-visible="<?= $visible ? '1' : '0' ?>"
              style="width:28px;height:28px;border-radius:var(--r-md);border:none;cursor:pointer;
                     background:oklch(18% .015 255 / .8);backdrop-filter:blur(4px);
                     color:<?= $visible ? 'var(--c-success)' : 'var(--c-text-faint)' ?>;
                     display:flex;align-items:center;justify-content:center;"
              title="<?= $visible ? 'Nascondi' : 'Mostra' ?>">
        <span class="material-symbols-rounded"
              style="font-size:15px;font-variation-settings:'FILL' 1"
              aria-hidden="true"><?= $visible ? 'visibility' : 'visibility_off' ?></span>
      </button>

      <!-- Elimina -->
      <button class="btn-delete-img"
              data-id="<?= (int)$img['id_immagine'] ?>"
              data-name="<?= htmlspecialchars($img['percorso']) ?>"
              style="width:28px;height:28px;border-radius:var(--r-md);border:none;cursor:pointer;
                     background:oklch(18% .015 255 / .8);backdrop-filter:blur(4px);
                     color:var(--c-danger);
                     display:flex;align-items:center;justify-content:center;"
              title="Elimina foto">
        <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">delete</span>
      </button>
    </div>

    <!-- Didascalia editabile -->
    <div style="padding:var(--sp-3) var(--sp-3) var(--sp-3)">
      <input type="text"
             class="didascalia-input adm-input"
             data-id="<?= (int)$img['id_immagine'] ?>"
             value="<?= htmlspecialchars($img['didascalia'] ?? '') ?>"
             placeholder="Aggiungi didascalia..."
             maxlength="255"
             style="font-size:.8rem;padding:var(--sp-2) var(--sp-3)">
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Paginazione -->
<?php if ($pages > 1): ?>
<div style="display:flex;gap:var(--sp-2);margin-top:var(--sp-6);justify-content:center;flex-wrap:wrap">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a href="?tab=gallery&gpage=<?= $p ?>"
       class="btn-sm <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>"
       <?= $p === $page ? 'aria-current="page"' : '' ?>>
      <?= $p ?>
    </a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
(function() {
  'use strict';

  var csrf = window.CSRF_TOKEN || '';
  var api  = window.BASE_PATH + 'api/admin/gallery.php';

  /* ── Dropzone styling ──────────────────────────────────────── */
  var dz      = document.getElementById('dropzone');
  var fileIn  = document.getElementById('gallery-files');
  var preview = document.getElementById('file-preview');
  var btnUp   = document.getElementById('btn-upload');
  var countEl = document.getElementById('upload-count');

  ['dragover','dragleave','drop'].forEach(function(evt) {
    dz.addEventListener(evt, function(e) { e.preventDefault(); });
  });
  dz.addEventListener('dragover',  function() { dz.style.borderColor='var(--c-accent)'; dz.style.backgroundColor='oklch(from var(--c-accent) l c h / .06)'; });
  dz.addEventListener('dragleave', function() { dz.style.borderColor=''; dz.style.backgroundColor=''; });
  dz.addEventListener('drop', function(e) {
    dz.style.borderColor=''; dz.style.backgroundColor='';
    fileIn.files = e.dataTransfer.files;
    updatePreview();
  });
  fileIn.addEventListener('change', updatePreview);

  function updatePreview() {
    preview.innerHTML = '';
    var files = fileIn.files;
    if (!files || !files.length) { btnUp.disabled = true; return; }
    Array.from(files).forEach(function(f) {
      var div = document.createElement('div');
      div.style.cssText = 'width:80px;text-align:center;font-size:.7rem;color:var(--c-text-muted)';
      var url = URL.createObjectURL(f);
      div.innerHTML = '<img src="' + url + '" style="width:80px;height:60px;object-fit:cover;border-radius:6px;display:block;margin-bottom:4px"><span>' + escHtml(f.name.substring(0,14)) + '</span>';
      preview.appendChild(div);
    });
    btnUp.disabled = false;
    countEl.textContent = '(' + files.length + ')';
  }

  /* ── AJAX upload ───────────────────────────────────────────── */
  var form    = document.getElementById('gallery-upload-form');
  var progWrap = document.getElementById('upload-progress');
  var progBar  = document.getElementById('progress-bar');
  var progMsg  = document.getElementById('progress-msg');

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!fileIn.files.length) return;

    var fd = new FormData(form);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', api);
    xhr.setRequestHeader('X-CSRF-Token', csrf);

    progWrap.style.display = 'block';
    btnUp.disabled = true;

    xhr.upload.addEventListener('progress', function(ev) {
      if (!ev.lengthComputable) return;
      var pct = Math.round(ev.loaded / ev.total * 100);
      progBar.style.width = pct + '%';
      progMsg.textContent = 'Caricamento ' + pct + '%...';
    });

    xhr.addEventListener('load', function() {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          progMsg.textContent = 'Caricamento completato. Aggiornamento pagina...';
          setTimeout(function() { window.location.reload(); }, 800);
        } else {
          progMsg.textContent = 'Errore: ' + (resp.error || 'sconosciuto');
          progBar.style.background = 'var(--c-danger)';
          btnUp.disabled = false;
        }
      } catch(ex) {
        progMsg.textContent = 'Risposta non valida.';
        btnUp.disabled = false;
      }
    });

    xhr.addEventListener('error', function() {
      progMsg.textContent = 'Errore di rete.';
      btnUp.disabled = false;
    });

    xhr.send(fd);
  });

  /* ── Toggle visibilità ─────────────────────────────────────── */
  document.querySelectorAll('.btn-toggle-vis').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id  = this.dataset.id;
      var cur = this.dataset.visible === '1';
      var fd  = new FormData();
      fd.append('action',       'toggle_visibilita');
      fd.append('id_immagine',  id);
      fd.append('is_visibile',  cur ? '0' : '1');
      fd.append('csrf_token',   csrf);
      fetch(api, {method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':csrf},body:fd})
        .then(function(r){return r.json();})
        .then(function(resp) {
          if (!resp.success) return;
          var newVis = !cur;
          btn.dataset.visible = newVis ? '1' : '0';
          btn.style.color     = newVis ? 'var(--c-success)' : 'var(--c-text-faint)';
          btn.title           = newVis ? 'Nascondi' : 'Mostra';
          btn.querySelector('.material-symbols-rounded').textContent = newVis ? 'visibility' : 'visibility_off';
          var card = btn.closest('.gallery-card');
          card.style.opacity = newVis ? '1' : '0.5';
        });
    });
  });

  /* ── Didascalia (debounce save) ────────────────────────────── */
  var didTimers = {};
  document.querySelectorAll('.didascalia-input').forEach(function(inp) {
    inp.addEventListener('input', function() {
      var id  = this.dataset.id;
      var val = this.value;
      clearTimeout(didTimers[id]);
      didTimers[id] = setTimeout(function() {
        var fd = new FormData();
        fd.append('action',      'update_didascalia');
        fd.append('id_immagine', id);
        fd.append('didascalia',  val);
        fd.append('csrf_token',  csrf);
        fetch(api, {method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':csrf},body:fd})
          .catch(function(){});
      }, 800);
    });
  });

  /* ── Elimina ───────────────────────────────────────────────── */
  document.querySelectorAll('.btn-delete-img').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var id   = this.dataset.id;
      var name = this.dataset.name;
      if (!confirm('Eliminare "' + name + '"? L\'operazione è irreversibile.')) return;
      var fd = new FormData();
      fd.append('action',      'delete');
      fd.append('id_immagine', id);
      fd.append('csrf_token',  csrf);
      var card = btn.closest('.gallery-card');
      fetch(api, {method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':csrf},body:fd})
        .then(function(r){return r.json();})
        .then(function(resp) {
          if (resp.success) {
            card.style.transition = 'opacity .25s,transform .25s';
            card.style.opacity    = '0';
            card.style.transform  = 'scale(.95)';
            setTimeout(function() { card.remove(); }, 260);
          }
        });
    });
  });

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
}());
</script>
