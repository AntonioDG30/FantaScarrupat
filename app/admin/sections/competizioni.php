<?php
/**
 * app/admin/sections/competizioni.php — Gestione competizioni ed edizioni
 */

// Tipi competizione (per capire se partite avvessario o solitarie)
$solitaria_tipi = ['Battle Royale', 'Formula Uno', 'Highlander', 'Uno vs Tutti'];

// Tutte le edizioni con partite count
$edizioni = $db->query("
    SELECT cd.id_competizione_disputata, cd.nome_competizione, cd.anno, cd.vincitore,
           c.tipologia,
           (SELECT COUNT(*) FROM partita_avvessario pa WHERE pa.id_competizione_disputata = cd.id_competizione_disputata) AS n_avv,
           (SELECT COUNT(*) FROM partita_solitaria  ps WHERE ps.id_competizione_disputata = cd.id_competizione_disputata) AS n_sol
      FROM competizione_disputata cd
      JOIN competizione c ON c.nome_competizione = cd.nome_competizione
     ORDER BY cd.anno DESC, cd.id_competizione_disputata DESC
")->fetchAll(\PDO::FETCH_ASSOC);

// Competizioni disponibili (per form nuova edizione)
$comp_list = $db->query("SELECT nome_competizione, tipologia FROM competizione ORDER BY nome_competizione ASC")
              ->fetchAll(\PDO::FETCH_ASSOC);

// Fantasquadre per i form partita
$fq_list = $db->query("SELECT nome_fantasquadra FROM fantasquadra ORDER BY nome_fantasquadra ASC")
             ->fetchAll(\PDO::FETCH_COLUMN);

// Tipologie partita (per avvessario)
$tip_partita = $db->query("SELECT tipologia FROM tipologia_partita ORDER BY tipologia ASC")
               ->fetchAll(\PDO::FETCH_COLUMN);

// Tipologie competizione (per creare un nuovo TIPO base)
$tip_comp = $db->query("SELECT tipologia FROM tipologia_competizione ORDER BY tipologia ASC")
            ->fetchAll(\PDO::FETCH_COLUMN);

// ID edizione aperta (da URL)
$open_id = (int)($_GET['open'] ?? 0);

// Avvisi dettagliati dall'ultimo import formazioni (match non agganciati)
$form_avvisi = $_SESSION['import_formazioni_avvisi'] ?? [];
unset($_SESSION['import_formazioni_avvisi']);
?>

<?php if (!empty($form_avvisi)): ?>
<div class="panel" style="margin-bottom:var(--sp-5);border-color:var(--c-warning,#d39e00)">
  <details>
    <summary style="cursor:pointer;padding:var(--sp-4) var(--sp-5);font-size:.875rem;font-weight:600;color:var(--c-text-muted);list-style:none">
      <span class="material-symbols-rounded" style="font-size:16px;vertical-align:-3px" aria-hidden="true">warning</span>
      Avvisi ultimo import formazioni (<?= count($form_avvisi) ?>)
    </summary>
    <ul style="margin:0;padding:var(--sp-3) var(--sp-6) var(--sp-5);font-size:.8125rem;color:var(--c-text-muted);line-height:1.6">
      <?php foreach ($form_avvisi as $a): ?>
        <li><?= htmlspecialchars($a) ?></li>
      <?php endforeach; ?>
    </ul>
  </details>
</div>
<?php endif; ?>

<!-- ── Form nuova competizione (TIPO base) ────────────────────── -->
<div class="panel" style="margin-bottom:var(--sp-4)">
  <details <?= isset($_GET['new_comp']) ? 'open' : '' ?>>
    <summary style="display:flex;align-items:center;gap:var(--sp-3);cursor:pointer;padding:var(--sp-4) var(--sp-5);list-style:none">
      <span class="material-symbols-rounded" style="font-size:18px;color:var(--c-accent)" aria-hidden="true">emoji_events</span>
      <span style="font-weight:600;color:var(--c-text)">Nuova competizione (tipo)</span>
      <span style="font-size:.8rem;color:var(--c-text-faint);font-weight:400">— definisce nome e formato, poi crei le edizioni</span>
    </summary>
    <div style="padding:var(--sp-5);border-top:1px solid var(--c-border)">
      <p style="font-size:.8125rem;color:var(--c-text-muted);margin:0 0 var(--sp-4)">
        Crea un nuovo tipo di competizione (es. una nuova coppa). La <strong>tipologia</strong> decide come viene
        mostrata nel sito pubblico: <em>A Calendario</em> = campionato, <em>A Gruppi</em> = gironi + tabellone,
        <em>Eliminazione Diretta</em>/<em>Mista</em> = tabellone, le altre = classifica a punti.
      </p>
      <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php"
            style="display:flex;gap:var(--sp-4);flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action"   value="add_competizione">
        <input type="hidden" name="redirect" value="?tab=competizioni">

        <div class="adm-field" style="margin:0;flex:1;min-width:220px">
          <label class="adm-label" for="newcomp_nome">Nome competizione</label>
          <input type="text" id="newcomp_nome" name="nome_competizione" class="adm-input"
                 maxlength="100" required placeholder="Es. Supercoppa">
        </div>

        <div class="adm-field" style="margin:0;min-width:200px">
          <label class="adm-label" for="newcomp_tip">Tipologia (formato)</label>
          <select id="newcomp_tip" name="tipologia" class="adm-input" required>
            <option value="">Scegli formato…</option>
            <?php foreach ($tip_comp as $tc): ?>
              <option value="<?= htmlspecialchars($tc) ?>"><?= htmlspecialchars($tc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="padding-bottom:1px">
          <button type="submit" class="btn-sm btn-primary">
            <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">save</span>
            Crea competizione
          </button>
        </div>
      </form>
    </div>
  </details>
</div>

<!-- ── Form nuova edizione ────────────────────────────────────── -->
<div class="panel" style="margin-bottom:var(--sp-6)">
  <details <?= isset($_GET['new_ed']) ? 'open' : '' ?>>
    <summary style="display:flex;align-items:center;gap:var(--sp-3);cursor:pointer;padding:var(--sp-4) var(--sp-5);list-style:none">
      <span class="material-symbols-rounded" style="font-size:18px;color:var(--c-accent)" aria-hidden="true">add_circle</span>
      <span style="font-weight:600;color:var(--c-text)">Nuova edizione competizione</span>
    </summary>
    <div style="padding:var(--sp-5);border-top:1px solid var(--c-border)">
      <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php"
            style="display:flex;gap:var(--sp-4);flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="add_edizione">

        <div class="adm-field" style="margin:0;flex:1;min-width:200px">
          <label class="adm-label" for="comp_nome">Competizione</label>
          <select id="comp_nome" name="nome_competizione" class="adm-input" required>
            <option value="">Scegli competizione…</option>
            <?php foreach ($comp_list as $c): ?>
              <option value="<?= htmlspecialchars($c['nome_competizione']) ?>">
                <?= htmlspecialchars($c['nome_competizione']) ?>
                <span style="color:var(--c-text-faint)">(<?= htmlspecialchars($c['tipologia']) ?>)</span>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="adm-field" style="margin:0;min-width:130px">
          <label class="adm-label" for="comp_anno">Anno</label>
          <input type="number" id="comp_anno" name="anno"
                 class="adm-input" min="2016" max="<?= (int)($anno_max ?? CURRENT_ANNO) + 1 ?>"
                 value="<?= (int)($anno_max ?? CURRENT_ANNO) ?>" required>
        </div>

        <div class="adm-field" style="margin:0;min-width:200px">
          <label class="adm-label" for="comp_vincitore">Vincitore <span style="color:var(--c-text-faint)">(opzionale)</span></label>
          <select id="comp_vincitore" name="vincitore" class="adm-input">
            <option value="">Nessuno / In corso</option>
            <?php foreach ($fq_list as $fq): ?>
              <option value="<?= htmlspecialchars($fq) ?>"><?= htmlspecialchars($fq) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="padding-bottom:1px">
          <button type="submit" class="btn-sm btn-primary">
            <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">save</span>
            Crea edizione
          </button>
        </div>
      </form>
    </div>
  </details>
</div>

<!-- ── Lista edizioni ─────────────────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-4)">
  <h2 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:700;color:var(--c-text);margin:0">
    Edizioni (<?= count($edizioni) ?>)
  </h2>
</div>

<?php if (empty($edizioni)): ?>
<div style="text-align:center;padding:var(--sp-12);color:var(--c-text-muted)">
  Nessuna edizione registrata.
</div>
<?php else: ?>

<?php foreach ($edizioni as $ed):
    $ed_id     = (int)$ed['id_competizione_disputata'];
    $is_sol    = in_array($ed['tipologia'], $solitaria_tipi, true);
    $n_partite = $is_sol ? (int)$ed['n_sol'] : (int)$ed['n_avv'];
    $is_open   = ($open_id === $ed_id);

    // Partite di questa edizione (solo se aperta)
    $partite_ed   = [];
    $n_formazioni = 0;
    if ($is_open && !$is_sol) {
        $pa = $db->prepare("
            SELECT id_partita, nome_fantasquadra_casa, nome_fantasquadra_trasferta,
                   gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta,
                   giornata, tipologia, girone
              FROM partita_avvessario
             WHERE id_competizione_disputata = ?
             ORDER BY giornata ASC, girone ASC, id_partita ASC
        ");
        $pa->execute([$ed_id]);
        $partite_ed = $pa->fetchAll(\PDO::FETCH_ASSOC);

        $nf = $db->prepare("SELECT COUNT(*) FROM formazione WHERE id_competizione_disputata = ?");
        $nf->execute([$ed_id]);
        $n_formazioni = (int)$nf->fetchColumn();
    } elseif ($is_open && $is_sol) {
        $ps = $db->prepare("
            SELECT id_partita_solitaria, nome_fantasquadra, punteggio, giornata
              FROM partita_solitaria
             WHERE id_competizione_disputata = ?
             ORDER BY giornata ASC, punteggio DESC
        ");
        $ps->execute([$ed_id]);
        $partite_ed = $ps->fetchAll(\PDO::FETCH_ASSOC);
    }
?>

<div class="panel" style="margin-bottom:var(--sp-4)">
  <!-- Intestazione edizione -->
  <div class="comp-ed-head" style="display:flex;align-items:center;gap:var(--sp-4);padding:var(--sp-4) var(--sp-5);border-bottom:1px solid var(--c-border);flex-wrap:wrap">
    <div style="flex:1;min-width:0">
      <span style="font-weight:600;color:var(--c-text)"><?= htmlspecialchars($ed['nome_competizione']) ?></span>
      <span style="color:var(--c-text-muted);font-size:.875rem;margin-left:var(--sp-3)"><?= (int)$ed['anno'] ?></span>
      <?php if ($ed['vincitore']): ?>
        <span style="margin-left:var(--sp-3);color:var(--c-gold);font-size:.875rem">
          <span class="material-symbols-rounded" style="font-size:13px;vertical-align:-2px;font-variation-settings:'FILL' 1" aria-hidden="true">emoji_events</span>
          <?= htmlspecialchars($ed['vincitore']) ?>
        </span>
      <?php endif; ?>
    </div>
    <div class="comp-ed-actions" style="display:flex;align-items:center;gap:var(--sp-3)">
      <span style="font-size:.8rem;color:var(--c-text-faint)"><?= $n_partite ?> partite</span>

      <!-- Vincitore: modifica deliberata (nascosta in un dettaglio + conferma) -->
      <details class="comp-win">
        <summary class="comp-win__toggle">
          <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">emoji_events</span>
          Vincitore
          <span class="material-symbols-rounded comp-win__chev" aria-hidden="true">expand_more</span>
        </summary>
        <div class="comp-win__body">
          <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php"
                class="comp-win__form"
                onsubmit="return confirm('Impostare il vincitore selezionato per questa competizione? Sovrascrive il vincitore attuale.');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="action" value="set_vincitore">
            <input type="hidden" name="id_cd"   value="<?= $ed_id ?>">
            <select name="vincitore" class="adm-input" style="padding:var(--sp-2) var(--sp-3);font-size:.8125rem;min-width:160px">
              <option value="">Nessun vincitore</option>
              <?php foreach ($fq_list as $fq): ?>
                <option value="<?= htmlspecialchars($fq) ?>" <?= $ed['vincitore'] === $fq ? 'selected' : '' ?>>
                  <?= htmlspecialchars($fq) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-sm btn-primary" style="font-size:.75rem">
              <span class="material-symbols-rounded" style="font-size:13px" aria-hidden="true">save</span>
              Salva
            </button>
          </form>
          <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php"
                onsubmit="return confirm('Calcolare e impostare automaticamente il vincitore dai risultati registrati?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="action"  value="calc_vincitore">
            <input type="hidden" name="id_cd"   value="<?= $ed_id ?>">
            <input type="hidden" name="redirect" value="?tab=competizioni&open=<?= $ed_id ?>">
            <button type="submit" class="btn-sm btn-secondary" style="font-size:.75rem"
                    title="Calcola vincitore dai risultati registrati">
              <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">calculate</span>
              Calcola
            </button>
          </form>
        </div>
      </details>

      <!-- Toggle partite -->
      <a href="?tab=competizioni<?= $is_open ? '' : '&open=' . $ed_id ?>"
         class="btn-sm btn-secondary" style="font-size:.75rem">
        <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">
          <?= $is_open ? 'expand_less' : 'expand_more' ?>
        </span>
        <?= $is_open ? 'Chiudi' : 'Partite' ?>
      </a>

      <!-- Elimina intera edizione (con tutte le partite e formazioni) -->
      <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php" style="margin:0"
            onsubmit="return confirm('Eliminare DEFINITIVAMENTE questa edizione con tutte le sue partite e formazioni collegate? L\'operazione è irreversibile.');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action" value="delete_edizione">
        <input type="hidden" name="id_cd"  value="<?= $ed_id ?>">
        <button type="submit" class="btn-sm" style="font-size:.75rem;color:var(--c-danger);border-color:var(--c-danger);background:transparent"
                title="Elimina l'intera edizione">
          <span class="material-symbols-rounded" style="font-size:14px" aria-hidden="true">delete_forever</span>
          Elimina
        </button>
      </form>
    </div>
  </div>

  <?php if ($is_open): ?>
  <div style="padding:var(--sp-5)">

    <!-- Importa competizione: calendario + (opzionale) formazioni in un colpo solo -->
    <details style="margin-bottom:var(--sp-5)">
      <summary style="display:flex;align-items:center;gap:var(--sp-2);cursor:pointer;font-size:.875rem;font-weight:600;color:var(--c-text-muted);list-style:none">
        <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">upload_file</span>
        Importa competizione (calendario<?php if (!$is_sol): ?> + formazioni<?php endif; ?>)
      </summary>
      <div style="margin-top:var(--sp-4);padding:var(--sp-5);border:1px solid var(--c-border);border-radius:var(--r-lg)">
        <p style="font-size:.8125rem;color:var(--c-text-muted);margin:0 0 var(--sp-4)">
          Carica il <strong>calendario XLSX</strong><?php if (!$is_sol): ?> e, se vuoi, lo <strong>ZIP delle formazioni</strong> (tutte le giornate)<?php endif; ?>.
          Il sistema importa tutto in un unico passaggio e <strong>calcola subito vincitore, supplementari e rigori</strong>.
        </p>
        <form method="post"
              data-proc-unified
              data-cal-url="<?= BASE_PATH ?>app/admin/import_competizione.php"
              data-form-url="<?= BASE_PATH ?>app/admin/import_formazioni.php"
              data-proc-title="Importazione competizione"
              enctype="multipart/form-data"
              style="display:flex;gap:var(--sp-4);flex-wrap:wrap;align-items:flex-end">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
          <input type="hidden" name="id_cd"      value="<?= $ed_id ?>">

          <div class="adm-field" style="margin:0;flex:1;min-width:220px">
            <span class="adm-label">File calendario <span style="font-weight:400;color:var(--c-text-faint)">(XLSX)</span></span>
            <label class="adm-file">
              <input type="file" name="file_calendario" accept=".xlsx" class="adm-file__input" required>
              <span class="adm-file__icon material-symbols-rounded" aria-hidden="true">upload_file</span>
              <span class="adm-file__text">Scegli o trascina il file</span>
              <span class="adm-file__hint">XLSX</span>
            </label>
          </div>

          <div class="adm-field" style="margin:0;flex:1;min-width:220px">
            <span class="adm-label">Secondo file <span style="font-weight:400;color:var(--c-text-faint)">(opzionale, es. NBA)</span></span>
            <label class="adm-file">
              <input type="file" name="file_calendario2" accept=".xlsx" class="adm-file__input">
              <span class="adm-file__icon material-symbols-rounded" aria-hidden="true">upload_file</span>
              <span class="adm-file__text">Nessun file</span>
              <span class="adm-file__hint">XLSX</span>
            </label>
          </div>

          <?php if ($n_partite > 0): ?>
          <div class="adm-field" style="margin:0;flex-basis:100%;background:rgba(220,53,69,.07);border:1px solid var(--c-danger);border-radius:var(--r-lg);padding:var(--sp-3) var(--sp-4)">
            <label style="display:flex;align-items:flex-start;gap:var(--sp-2);cursor:pointer">
              <input type="checkbox" name="sovrascrivi_cal" value="1" required
                     style="width:16px;height:16px;margin-top:2px;accent-color:var(--c-danger);flex-shrink:0">
              <span style="font-size:.8125rem;color:var(--c-text);line-height:1.45">
                <strong style="color:var(--c-danger)">Sostituisci il calendario esistente.</strong>
                Verranno <strong>eliminate le <?= $n_partite ?> partite</strong> già presenti per
                «<?= htmlspecialchars($ed['nome_competizione']) ?> <?= (int)$ed['anno'] ?>» e reinserite quelle del file.
              </span>
            </label>
          </div>
          <?php endif; ?>

          <?php if (!$is_sol): ?>
          <!-- Formazioni opzionali -->
          <div class="adm-field" style="margin:0;flex-basis:100%;border-top:1px dashed var(--c-border);padding-top:var(--sp-4)">
            <label style="display:flex;align-items:center;gap:var(--sp-2);cursor:pointer">
              <input type="checkbox" name="incl_formazioni" value="1" checked
                     style="width:15px;height:15px;accent-color:var(--c-accent)"
                     onchange="var b=this.closest('form').querySelector('.unif-form-box'); if(b) b.style.display=this.checked?'':'none';">
              <span class="adm-label" style="margin:0;font-size:.8125rem">Carica anche le <strong>formazioni</strong> (servono per calcolare supplementari e rigori)</span>
            </label>
          </div>
          <div class="unif-form-box adm-field" style="margin:0;flex-basis:100%">
            <span class="adm-label">File formazioni <span style="font-weight:400;color:var(--c-text-faint)">(ZIP tutte le giornate, o singolo XLSX)</span></span>
            <label class="adm-file">
              <input type="file" name="file_formazioni" accept=".zip,.xlsx" class="adm-file__input">
              <span class="adm-file__icon material-symbols-rounded" aria-hidden="true">folder_zip</span>
              <span class="adm-file__text">Scegli o trascina lo ZIP (o un XLSX)</span>
              <span class="adm-file__hint">ZIP / XLSX</span>
            </label>
            <?php if ($n_formazioni > 0): ?>
            <label style="display:flex;align-items:flex-start;gap:var(--sp-2);cursor:pointer;margin-top:var(--sp-3)">
              <input type="checkbox" name="sovrascrivi_form" value="1"
                     style="width:15px;height:15px;margin-top:2px;accent-color:var(--c-danger);flex-shrink:0">
              <span style="font-size:.8125rem;color:var(--c-text-muted);line-height:1.45">
                Sovrascrivi le <strong><?= $n_formazioni ?> formazioni</strong> già presenti (quelle coincidenti vengono sostituite).
              </span>
            </label>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <div style="flex-basis:100%;padding-bottom:1px">
            <button type="submit" class="btn-sm btn-primary">
              <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">upload</span>
              Importa competizione
            </button>
          </div>
        </form>
      </div>
    </details>

    <?php if (!$is_sol): ?>

    <!-- Partite avvessario -->
    <?php if (!empty($partite_ed)): ?>
    <div class="panel" style="margin-bottom:var(--sp-5)">
      <div class="panel__body--flush">
        <table class="adm-table" style="font-size:.875rem">
          <thead>
            <tr>
              <th style="width:40px">G</th>
              <th>Casa</th>
              <th style="width:50px;text-align:center">Gol</th>
              <th style="text-align:center;width:50px"></th>
              <th style="width:50px;text-align:center">Gol</th>
              <th>Trasferta</th>
              <th style="width:70px">Tipo</th>
              <th style="width:55px">Girone</th>
              <th style="width:40px"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($partite_ed as $pt): ?>
            <tr>
              <td style="color:var(--c-text-faint)"><?= (int)$pt['giornata'] ?></td>
              <td style="font-weight:500"><?= htmlspecialchars($pt['nome_fantasquadra_casa']) ?></td>
              <td style="text-align:center;font-family:var(--font-heading);font-weight:700;font-size:1.1rem">
                <?= (int)$pt['gol_casa'] ?>
              </td>
              <td style="text-align:center;color:var(--c-text-faint);font-size:.8rem">vs</td>
              <td style="text-align:center;font-family:var(--font-heading);font-weight:700;font-size:1.1rem">
                <?= (int)$pt['gol_trasferta'] ?>
              </td>
              <td style="font-weight:500"><?= htmlspecialchars($pt['nome_fantasquadra_trasferta']) ?></td>
              <td style="font-size:.8rem;color:var(--c-text-muted)"><?= htmlspecialchars($pt['tipologia']) ?></td>
              <td style="font-size:.8rem;color:var(--c-text-muted)"><?= htmlspecialchars($pt['girone'] ?? '') ?></td>
              <td>
                <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php" style="margin:0">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                  <input type="hidden" name="action"      value="delete_avv">
                  <input type="hidden" name="id_partita"  value="<?= (int)$pt['id_partita'] ?>">
                  <input type="hidden" name="redirect"    value="?tab=competizioni&open=<?= $ed_id ?>">
                  <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--c-danger);padding:2px 4px" title="Elimina">
                    <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">delete</span>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Aggiungi partita avvessario -->
    <div style="border:1px solid var(--c-border);border-radius:var(--r-lg);padding:var(--sp-5)">
      <p style="font-size:.8125rem;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.06em;margin:0 0 var(--sp-4)">Aggiungi partita</p>
      <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php"
            class="adm-form-grid"
            style="display:grid;grid-template-columns:1fr auto 1fr auto;gap:var(--sp-3);align-items:end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action"  value="add_avv">
        <input type="hidden" name="id_cd"   value="<?= $ed_id ?>">
        <input type="hidden" name="redirect" value="?tab=competizioni&open=<?= $ed_id ?>">

        <div>
          <label class="adm-label">Casa</label>
          <select name="casa" class="adm-input" required>
            <?php foreach ($fq_list as $fq): ?>
              <option value="<?= htmlspecialchars($fq) ?>"><?= htmlspecialchars($fq) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Gol + punteggi compatti -->
        <div style="display:flex;flex-direction:column;gap:var(--sp-2)">
          <label class="adm-label" style="text-align:center">Gol</label>
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <input type="number" name="gol_casa" class="adm-input" min="0" max="99" value="0" required
                   style="width:56px;text-align:center">
            <span style="color:var(--c-text-faint);font-size:.875rem">-</span>
            <input type="number" name="gol_trasferta" class="adm-input" min="0" max="99" value="0" required
                   style="width:56px;text-align:center">
          </div>
          <div style="display:flex;align-items:center;gap:var(--sp-2)">
            <input type="number" name="punteggio_casa" class="adm-input" step="0.5" min="0" max="999" value="0"
                   style="width:56px;text-align:center;font-size:.8rem" placeholder="Pt">
            <span style="color:var(--c-text-faint);font-size:.75rem">-</span>
            <input type="number" name="punteggio_trasferta" class="adm-input" step="0.5" min="0" max="999" value="0"
                   style="width:56px;text-align:center;font-size:.8rem" placeholder="Pt">
          </div>
        </div>

        <div>
          <label class="adm-label">Trasferta</label>
          <select name="trasferta" class="adm-input" required>
            <?php foreach ($fq_list as $fq): ?>
              <option value="<?= htmlspecialchars($fq) ?>"><?= htmlspecialchars($fq) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex;flex-direction:column;gap:var(--sp-2)">
          <div>
            <label class="adm-label">Giornata</label>
            <input type="number" name="giornata" class="adm-input" min="1" max="99" value="1" required style="width:64px">
          </div>
          <div>
            <label class="adm-label">Tipo</label>
            <select name="tipologia" class="adm-input" style="font-size:.8rem">
              <?php foreach ($tip_partita as $tp): ?>
                <option value="<?= htmlspecialchars($tp) ?>"><?= htmlspecialchars($tp) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="adm-label">Girone <span style="color:var(--c-text-faint)">(opt.)</span></label>
            <input type="text" name="girone" class="adm-input" maxlength="20" style="font-size:.8rem" placeholder="A/B/…">
          </div>
          <button type="submit" class="btn-sm btn-primary" style="margin-top:var(--sp-1)">
            <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">add</span>
            Aggiungi
          </button>
        </div>
      </form>
    </div>

    <?php else: ?>
    <!-- Partite solitarie -->
    <?php if (!empty($partite_ed)): ?>
    <div class="panel" style="margin-bottom:var(--sp-5)">
      <div class="panel__body--flush">
        <table class="adm-table" style="font-size:.875rem">
          <thead>
            <tr>
              <th style="width:40px">G</th>
              <th>Squadra</th>
              <th style="text-align:right;width:90px">Punteggio</th>
              <th style="width:40px"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($partite_ed as $pt): ?>
            <tr>
              <td style="color:var(--c-text-faint)"><?= (int)$pt['giornata'] ?></td>
              <td style="font-weight:500"><?= htmlspecialchars($pt['nome_fantasquadra']) ?></td>
              <td style="text-align:right;font-family:var(--font-heading);font-weight:700;font-size:1.1rem">
                <?= number_format((float)$pt['punteggio'], 1) ?>
              </td>
              <td>
                <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php" style="margin:0">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                  <input type="hidden" name="action"      value="delete_sol">
                  <input type="hidden" name="id_partita"  value="<?= (int)$pt['id_partita_solitaria'] ?>">
                  <input type="hidden" name="redirect"    value="?tab=competizioni&open=<?= $ed_id ?>">
                  <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--c-danger);padding:2px 4px" title="Elimina">
                    <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">delete</span>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Aggiungi partita solitaria -->
    <div style="border:1px solid var(--c-border);border-radius:var(--r-lg);padding:var(--sp-5)">
      <p style="font-size:.8125rem;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.06em;margin:0 0 var(--sp-4)">Aggiungi risultato</p>
      <form method="post" action="<?= BASE_PATH ?>api/admin/competizioni.php"
            style="display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <input type="hidden" name="action"   value="add_sol">
        <input type="hidden" name="id_cd"    value="<?= $ed_id ?>">
        <input type="hidden" name="redirect" value="?tab=competizioni&open=<?= $ed_id ?>">

        <div class="adm-field" style="margin:0;flex:1;min-width:180px">
          <label class="adm-label">Squadra</label>
          <select name="nome_fantasquadra" class="adm-input" required>
            <?php foreach ($fq_list as $fq): ?>
              <option value="<?= htmlspecialchars($fq) ?>"><?= htmlspecialchars($fq) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="adm-field" style="margin:0;min-width:100px">
          <label class="adm-label">Punteggio</label>
          <input type="number" name="punteggio" class="adm-input" step="0.5" min="0" max="999" value="0" required>
        </div>
        <div class="adm-field" style="margin:0;min-width:80px">
          <label class="adm-label">Giornata</label>
          <input type="number" name="giornata" class="adm-input" min="1" max="99" value="1" required>
        </div>
        <div style="padding-bottom:1px">
          <button type="submit" class="btn-sm btn-primary">
            <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">add</span>
            Aggiungi
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>
</div>

<?php endforeach; ?>
<?php endif; ?>
