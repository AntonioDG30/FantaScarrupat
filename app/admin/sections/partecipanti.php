<?php
/**
 * app/admin/sections/partecipanti.php — Gestione fantasquadre
 */

// Fantasquadre esistenti
$squads = $db->query("
    SELECT nome_fantasquadra, fantallenatore, immagine_fantallenatore, scudetto, flag_attuale
      FROM fantasquadra
     ORDER BY flag_attuale DESC, nome_fantasquadra ASC
")->fetchAll(\PDO::FETCH_ASSOC);

// Statistiche
$n_attivi   = (int)$db->query("SELECT COUNT(*) FROM fantasquadra WHERE flag_attuale = 1")->fetchColumn();
$n_inattivi = (int)$db->query("SELECT COUNT(*) FROM fantasquadra WHERE flag_attuale = 0")->fetchColumn();
$n_rose_tot = (int)$db->query("SELECT COUNT(*) FROM rosa")->fetchColumn();

// Editing mode: ?edit=nome_fantasquadra
$edit_fq = isset($_GET['edit']) ? urldecode($_GET['edit']) : null;
$editing = null;
if ($edit_fq) {
    foreach ($squads as $s) {
        if ($s['nome_fantasquadra'] === $edit_fq) {
            $editing = $s;
            break;
        }
    }
}
?>

<div style="display:grid;grid-template-columns:1fr<?= $editing ? ' 360px' : '' ?>;gap:var(--sp-6);align-items:start" id="part-layout">

<!-- ── Tabella ────────────────────────────────────────────────── -->
<div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:var(--sp-3)">
    <h2 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:700;color:var(--c-text);margin:0">
      Partecipanti
    </h2>
    <div style="display:flex;gap:var(--sp-2)">
      <a href="<?= BASE_PATH ?>api/admin/partecipanti.php?action=export_csv"
         class="btn-sm btn-secondary">
        <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">download</span>
        Esporta CSV
      </a>
      <a href="?tab=partecipanti&new=1" class="btn-sm btn-primary">
        <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">add</span>
        Aggiungi
      </a>
    </div>
  </div>

  <div class="panel">
    <div class="panel__body--flush">
      <table class="adm-table" aria-label="Elenco partecipanti">
        <thead>
          <tr>
            <th style="width:48px">Foto</th>
            <th style="width:44px">Scud.</th>
            <th>Fantasquadra</th>
            <th>Fantallenatore</th>
            <th style="width:90px;text-align:center">Attuale</th>
            <th style="width:110px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($squads as $s):
            $foto_url = '';
            if (!empty($s['immagine_fantallenatore'])) {
                $foto_url = BASE_PATH . 'uploads/coaches/' . rawurlencode($s['immagine_fantallenatore']);
            }
            $scud_url = '';
            if (!empty($s['scudetto'])) {
                $scud_url = BASE_PATH . 'uploads/teams/' . rawurlencode($s['scudetto']);
            }
          ?>
          <tr>
            <td>
              <?php if ($foto_url): ?>
                <img src="<?= htmlspecialchars($foto_url) ?>"
                     alt=""
                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;
                            background:var(--c-bg-raised);display:block">
              <?php else: ?>
                <div style="width:36px;height:36px;border-radius:50%;background:oklch(from var(--c-accent) l c h / .15);
                            display:flex;align-items:center;justify-content:center;
                            font-family:var(--font-heading);font-weight:700;font-size:.875rem;color:var(--c-accent)">
                  <?= strtoupper(substr($s['fantallenatore'], 0, 2)) ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($scud_url): ?>
                <img src="<?= htmlspecialchars($scud_url) ?>"
                     alt="Scudetto <?= htmlspecialchars($s['nome_fantasquadra']) ?>"
                     style="width:32px;height:32px;object-fit:contain;display:block">
              <?php else: ?>
                <span style="color:var(--c-text-faint);font-size:.75rem">-</span>
              <?php endif; ?>
            </td>
            <td style="font-weight:500"><?= htmlspecialchars($s['nome_fantasquadra']) ?></td>
            <td style="color:var(--c-text-muted)"><?= htmlspecialchars($s['fantallenatore']) ?></td>
            <td style="text-align:center">
              <input type="checkbox"
                     class="flag-toggle"
                     data-fq="<?= htmlspecialchars($s['nome_fantasquadra']) ?>"
                     <?= $s['flag_attuale'] ? 'checked' : '' ?>
                     style="width:16px;height:16px;accent-color:var(--c-accent);cursor:pointer"
                     aria-label="Attiva/disattiva <?= htmlspecialchars($s['nome_fantasquadra']) ?>">
            </td>
            <td>
              <div style="display:flex;gap:var(--sp-2);align-items:center">
                <a href="?tab=partecipanti&edit=<?= urlencode($s['nome_fantasquadra']) ?>"
                   class="btn-sm btn-secondary"
                   style="font-size:.75rem"
                   aria-label="Modifica <?= htmlspecialchars($s['nome_fantasquadra']) ?>">
                  <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">edit</span>
                </a>
                <button type="button"
                        class="btn-sm btn-secondary rose-expand-btn"
                        data-fq="<?= htmlspecialchars($s['nome_fantasquadra']) ?>"
                        style="font-size:.75rem"
                        aria-label="Rose di <?= htmlspecialchars($s['nome_fantasquadra']) ?>">
                  <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">folder_special</span>
                </button>
              </div>
            </td>
          </tr>
          <!-- Rose expand row -->
          <tr class="rose-expand-row"
              id="rose-row-<?= htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '_', $s['nome_fantasquadra'])) ?>"
              style="display:none">
            <td colspan="6" style="padding:0">
              <div class="rose-content" style="padding:var(--sp-4) var(--sp-5);background:var(--c-bg-alt);border-top:1px solid var(--c-border)">
                <span style="color:var(--c-text-faint);font-size:.875rem">Caricamento…</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($squads)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--c-text-muted);padding:var(--sp-10)">Nessuna fantasquadra.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── Pannello edit / aggiungi ────────────────────────────────── -->
<?php if ($editing || isset($_GET['new'])): ?>
<div class="panel" style="align-self:start">
  <div class="panel__header" style="justify-content:space-between">
    <span class="panel__title">
      <?= $editing ? 'Modifica ' . htmlspecialchars($editing['nome_fantasquadra']) : 'Nuova fantasquadra' ?>
    </span>
    <a href="?tab=partecipanti" style="color:var(--c-text-faint);display:flex;align-items:center" aria-label="Chiudi">
      <span class="material-symbols-rounded" style="font-size:18px" aria-hidden="true">close</span>
    </a>
  </div>
  <div class="panel__body">
    <form method="post"
          action="<?= BASE_PATH ?>api/admin/partecipanti.php"
          enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
      <?php if ($editing): ?>
        <input type="hidden" name="nome_fantasquadra" value="<?= htmlspecialchars($editing['nome_fantasquadra']) ?>">
      <?php endif; ?>

      <?php if (!$editing): ?>
      <div class="adm-field">
        <label class="adm-label" for="p_nome">Nome fantasquadra</label>
        <input type="text" id="p_nome" name="nome_fantasquadra_new"
               class="adm-input" required maxlength="100" placeholder="Es. FC Pocholoco">
      </div>
      <?php else: ?>
      <div class="adm-field">
        <label class="adm-label">Nome fantasquadra</label>
        <div style="padding:var(--sp-3) var(--sp-4);background:var(--c-bg-alt);border:1px solid var(--c-border);border-radius:var(--r-md);color:var(--c-text-muted);font-size:.9375rem">
          <?= htmlspecialchars($editing['nome_fantasquadra']) ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="adm-field">
        <label class="adm-label" for="p_fant">Fantallenatore</label>
        <input type="text" id="p_fant" name="fantallenatore"
               class="adm-input" required maxlength="100"
               value="<?= htmlspecialchars($editing['fantallenatore'] ?? '') ?>"
               placeholder="Nome Cognome">
      </div>

      <div class="adm-field">
        <label class="adm-label" for="p_foto">
          Foto fantallenatore
          <?php if (!empty($editing['immagine_fantallenatore'])): ?>
            <span style="font-weight:400;color:var(--c-text-faint)">(lascia vuoto per mantenere)</span>
          <?php endif; ?>
        </label>
        <input type="file" id="p_foto" name="immagine_fantallenatore"
               accept="image/jpeg,image/png,image/webp,image/gif"
               class="adm-input">
      </div>

      <div class="adm-field">
        <label class="adm-label" for="p_scud">
          Scudetto fantasquadra
          <?php if (!empty($editing['scudetto'])): ?>
            <span style="font-weight:400;color:var(--c-text-faint)">(lascia vuoto per mantenere)</span>
          <?php endif; ?>
        </label>
        <?php if (!empty($editing['scudetto'])): ?>
          <div style="margin-bottom:var(--sp-2)">
            <img src="<?= BASE_PATH ?>uploads/teams/<?= rawurlencode($editing['scudetto']) ?>"
                 alt="Scudetto attuale"
                 style="width:48px;height:48px;object-fit:contain;border-radius:var(--r-sm);border:1px solid var(--c-border)">
          </div>
        <?php endif; ?>
        <input type="file" id="p_scud" name="scudetto"
               accept="image/jpeg,image/png,image/webp,image/gif"
               class="adm-input">
      </div>

      <div class="adm-field" style="margin-bottom:var(--sp-6)">
        <label style="display:flex;align-items:center;gap:var(--sp-3);cursor:pointer">
          <input type="checkbox" name="flag_attuale" value="1"
                 <?= ($editing['flag_attuale'] ?? 1) ? 'checked' : '' ?>
                 style="width:16px;height:16px;accent-color:var(--c-accent)">
          <span class="adm-label" style="margin:0">Partecipante stagione corrente</span>
        </label>
      </div>

      <button type="submit" class="btn-sm btn-primary" style="width:100%;justify-content:center">
        <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">save</span>
        <?= $editing ? 'Salva modifiche' : 'Aggiungi fantasquadra' ?>
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

</div><!-- /part-layout -->

<!-- Statistiche -->
<div class="stat-row" style="margin-top:var(--sp-6)">
  <div class="stat-block">
    <div class="stat-block__label">Partecipanti attivi</div>
    <div class="stat-block__value accent"><?= $n_attivi ?></div>
  </div>
  <div class="stat-block">
    <div class="stat-block__label">Partecipanti inattivi</div>
    <div class="stat-block__value"><?= $n_inattivi ?></div>
  </div>
  <div class="stat-block">
    <div class="stat-block__label">Rose totali (tutte le stagioni)</div>
    <div class="stat-block__value"><?= $n_rose_tot ?></div>
  </div>
</div>

<script>
/* ── Toggle flag_attuale ────────────────────────────────────────── */
document.querySelectorAll('.flag-toggle').forEach(function(cb) {
  cb.addEventListener('change', function() {
    var fq  = this.dataset.fq;
    var val = this.checked ? '1' : '0';
    var fd  = new FormData();
    fd.append('action', 'toggle_flag');
    fd.append('nome_fantasquadra', fq);
    fd.append('flag_attuale', val);
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fetch(window.BASE_PATH + 'api/admin/partecipanti.php', {
      method: 'POST', credentials: 'same-origin',
      headers: {'X-CSRF-Token': window.CSRF_TOKEN || ''},
      body: fd
    }).then(function(r) { return r.json(); })
    .catch(function() { cb.checked = !cb.checked; });
  });
});

/* ── Rose expand accordion ─────────────────────────────────────── */
document.querySelectorAll('.rose-expand-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var fq   = this.dataset.fq;
    var key  = fq.replace(/[^a-zA-Z0-9]/g, '_');
    var row  = document.getElementById('rose-row-' + key);
    if (!row) return;

    var isOpen = row.style.display !== 'none';
    if (isOpen) { row.style.display = 'none'; return; }

    var content = row.querySelector('.rose-content');
    content.innerHTML = '<span style="color:var(--c-text-faint);font-size:.875rem">Caricamento…</span>';
    row.style.display = '';

    fetch(window.BASE_PATH + 'api/admin/partecipante_rose.php?fq=' + encodeURIComponent(fq), {
      credentials: 'same-origin'
    }).then(function(r) { return r.json(); })
    .then(function(data) {
      if (!Array.isArray(data) || data.length === 0) {
        content.innerHTML = '<span style="color:var(--c-text-faint);font-size:.875rem">Nessuna rosa trovata.</span>';
        return;
      }
      var html = '<div style="display:flex;flex-wrap:wrap;gap:var(--sp-3)">';
      data.forEach(function(r) {
        html += '<div style="background:var(--c-bg-raised);border:1px solid var(--c-border);border-radius:var(--r-md);'
              + 'padding:var(--sp-3) var(--sp-4);min-width:140px">'
              + '<div style="font-weight:600;font-size:.875rem">' + escHtml(r.anno_label) + '</div>'
              + '<div style="font-size:.8rem;color:var(--c-text-muted);margin-top:2px">'
              + escHtml(r.modalita) + ' &middot; ' + r.num_giocatori + ' gioc.'
              + ' &middot; ' + r.crediti_totali + ' cred.'
              + '</div></div>';
      });
      html += '</div>';
      content.innerHTML = html;
    }).catch(function() {
      content.innerHTML = '<span style="color:var(--c-danger);font-size:.875rem">Errore caricamento.</span>';
    });
  });
});

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
