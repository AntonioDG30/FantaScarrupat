<?php
/**
 * app/admin/index.php — Panoramica admin + redirect hub per ?tab= legacy.
 *
 * Compatibilità: le API rimandano ancora a ?tab=X — questo file
 * le reindirizza verso le pagine separate admin/X.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/database.php';

// ── POST: crea una nuova stagione ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_stagione') {
    $db = getDB();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(403);
        exit('Token di sicurezza non valido.');
    }
    $anno = (int)($_POST['anno'] ?? 0);
    if ($anno < 2016 || $anno > (int)date('Y') + 3) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Anno stagione non valido.'];
    } else {
        try {
            $db->exec(
                "CREATE TABLE IF NOT EXISTS stagione (
                    anno INT NOT NULL PRIMARY KEY,
                    creata_il TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $st = $db->prepare("INSERT IGNORE INTO stagione (anno) VALUES (?)");
            $st->execute([$anno]);
            $_SESSION['admin_flash'] = $st->rowCount()
                ? ['type' => 'success', 'msg' => 'Stagione ' . anno_to_stagione($anno) . ' creata.']
                : ['type' => 'info',    'msg' => 'La stagione ' . anno_to_stagione($anno) . ' esisteva già.'];
        } catch (\PDOException $e) {
            error_log('[FS stagione] add: ' . $e->getMessage());
            $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore durante la creazione della stagione.'];
        }
    }
    header('Location: ' . BASE_PATH . 'app/admin/index.php', true, 302);
    exit;
}

// ── Redirect hub — compatibilità con ?tab= legacy ─────────────────────
$_tab_map = [
    'giocatori'    => 'giocatori.php',
    'partecipanti' => 'partecipanti.php',
    'rose'         => 'rose.php',
    'competizioni' => 'competizioni.php',
    'gallery'      => 'gallery.php',
];

if (isset($_GET['tab']) && array_key_exists($_GET['tab'], $_tab_map)) {
    $extra = $_GET;
    unset($extra['tab']);
    $redir = BASE_PATH . 'app/admin/' . $_tab_map[$_GET['tab']];
    if (!empty($extra)) {
        $redir .= '?' . http_build_query($extra);
    }
    header('Location: ' . $redir, true, 302);
    exit;
}

// ── Panoramica ────────────────────────────────────────────────────────
require_once __DIR__ . '/../_layout.php';
require_once __DIR__ . '/_admin_init.php';

$stagione = anno_to_stagione(CURRENT_ANNO);

// Stat globali
$n_giocatori    = (int)$db->query("SELECT COUNT(*) FROM giocatore")->fetchColumn();
$n_partecipanti = (int)$db->query("SELECT COUNT(*) FROM fantasquadra WHERE flag_attuale = 1")->fetchColumn();
$n_rose         = (int)$db->query("SELECT COUNT(*) FROM rosa WHERE anno = " . (int)CURRENT_ANNO)->fetchColumn();
$n_competizioni = (int)$db->query("SELECT COUNT(*) FROM competizione_disputata WHERE anno = " . (int)CURRENT_ANNO)->fetchColumn();

// Ultime competizioni
$comp_recenti = $db->query("
    SELECT nome_competizione, vincitore, anno
      FROM competizione_disputata
     ORDER BY anno DESC, id_competizione_disputata DESC
     LIMIT 5
")->fetchAll(\PDO::FETCH_ASSOC);

app_layout_start('Admin · Panoramica', 'admin-dashboard');
?>

<!-- Flash message -->
<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" role="alert">
  <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0;margin-top:1px" aria-hidden="true">
    <?= $flash['type'] === 'success' ? 'check_circle' : ($flash['type'] === 'error' ? 'error' : 'info') ?>
  </span>
  <span><?= htmlspecialchars($flash['msg']) ?></span>
</div>
<?php endif; ?>

<!-- Page head -->
<div class="page-head">
  <div>
    <h1 style="font-size:clamp(1.5rem,3vw,2rem)">Panoramica</h1>
    <div class="page-head__sub">Stagione <?= htmlspecialchars($stagione) ?></div>
  </div>
</div>

<!-- Stat grid -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:var(--sp-8)">
  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Giocatori</div>
        <div class="stat__value stat__value--accent"><?= $n_giocatori ?></div>
        <div class="stat__delta">in archivio</div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Partecipanti attivi</div>
        <div class="stat__value"><?= $n_partecipanti ?></div>
        <div class="stat__delta">stagione corrente</div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Rose <?= htmlspecialchars($stagione) ?></div>
        <div class="stat__value"><?= $n_rose ?></div>
        <div class="stat__delta">registrate</div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card__body">
      <div class="stat">
        <div class="stat__label">Competizioni <?= htmlspecialchars($stagione) ?></div>
        <div class="stat__value stat__value--gold"><?= $n_competizioni ?></div>
        <div class="stat__delta">questo anno</div>
      </div>
    </div>
  </div>
</div>

<!-- Grid: link rapidi + ultime competizioni -->
<div class="grid-2" style="align-items:start">

  <!-- Accesso rapido sezioni -->
  <div class="card">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">bolt</span>
      <div class="card__title">Sezioni</div>
    </div>
    <div class="card__body" style="padding:var(--sp-4)">
      <div class="quick-grid">
        <?php
        $sections = [
            ['url' => BASE_PATH . 'app/admin/giocatori.php',    'icon' => 'sports_soccer',   'label' => 'Giocatori',    'desc' => 'Importa e gestisci'],
            ['url' => BASE_PATH . 'app/admin/partecipanti.php', 'icon' => 'shield_person',   'label' => 'Partecipanti', 'desc' => 'Fantasquadre'],
            ['url' => BASE_PATH . 'app/admin/rose.php',         'icon' => 'list_alt',        'label' => 'Rose',         'desc' => 'Per stagione'],
            ['url' => BASE_PATH . 'app/admin/competizioni.php', 'icon' => 'emoji_events',    'label' => 'Competizioni', 'desc' => 'Calendario e risultati'],
            ['url' => BASE_PATH . 'app/admin/gallery.php',      'icon' => 'photo_library',   'label' => 'Galleria',     'desc' => 'Foto della lega'],
            ['url' => BASE_PATH . 'app/sorteggio.php',          'icon' => 'casino',          'label' => 'Ruota della Fortuna', 'desc' => 'Ordine asta'],
        ];
        foreach ($sections as $s): ?>
        <a href="<?= htmlspecialchars($s['url']) ?>" class="quick-item">
          <span class="material-symbols-rounded" aria-hidden="true"><?= $s['icon'] ?></span>
          <div>
            <div class="quick-item__label"><?= $s['label'] ?></div>
            <div class="quick-item__desc"><?= $s['desc'] ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Ultime competizioni -->
  <div class="card">
    <div class="card__header">
      <span class="material-symbols-rounded td-muted" aria-hidden="true">emoji_events</span>
      <div class="card__title">Ultime competizioni</div>
    </div>
    <?php if (empty($comp_recenti)): ?>
      <div class="empty" style="padding:var(--sp-8)">
        <span class="material-symbols-rounded" aria-hidden="true">hourglass_empty</span>
        <div>Nessuna competizione registrata.</div>
      </div>
    <?php else: ?>
      <div class="activity-feed card__body--flush">
        <?php foreach ($comp_recenti as $c): ?>
        <div class="activity-item">
          <div class="activity-item__icon">
            <span class="material-symbols-rounded"
                  style="font-variation-settings:'FILL' <?= $c['vincitore'] ? '1' : '0' ?>"
                  aria-hidden="true">emoji_events</span>
          </div>
          <div class="activity-item__content">
            <div class="activity-item__text"><strong><?= htmlspecialchars($c['nome_competizione']) ?></strong></div>
            <div class="activity-item__time">
              <?= (int)$c['anno'] ?>
              <?php if ($c['vincitore']): ?>
                &middot; <?= htmlspecialchars($c['vincitore']) ?>
              <?php else: ?>
                &middot; <span style="color:var(--c-text-dim)">Vincitore non assegnato</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Stagioni -->
<?php
$anni_disponibili = $db->query("
    SELECT anno FROM (
        SELECT anno FROM stagione
        UNION SELECT DISTINCT anno FROM giocatore_stagione
        UNION SELECT DISTINCT anno FROM rosa
        UNION SELECT DISTINCT anno FROM competizione_disputata
    ) t
    WHERE anno IS NOT NULL
    ORDER BY anno DESC
")->fetchAll(\PDO::FETCH_COLUMN);
$prossima = $anno_max + 1;
?>
<div class="card" style="margin-top:var(--sp-6)">
  <div class="card__header">
    <span class="material-symbols-rounded td-muted" aria-hidden="true">event</span>
    <div class="card__title">Stagioni</div>
  </div>
  <div class="card__body">
    <p class="td-muted" style="font-size:.875rem;margin:0 0 var(--sp-4);max-width:62ch">
      Stagione corrente: <strong style="color:var(--c-text)"><?= htmlspecialchars($stagione) ?></strong>.
      Crea una nuova stagione per renderla selezionabile nei menu di import giocatori, rose e competizioni.
    </p>
    <form method="post" action="<?= BASE_PATH ?>app/admin/index.php"
          style="display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end;margin-bottom:var(--sp-5)">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <input type="hidden" name="action" value="add_stagione">
      <div class="adm-field" style="margin:0">
        <label class="adm-label" for="nuova_stagione">Nuova stagione (anno)</label>
        <input type="number" id="nuova_stagione" name="anno" class="adm-input"
               min="2016" max="<?= (int)date('Y') + 3 ?>" value="<?= $prossima ?>" style="width:150px">
        <div style="font-size:.75rem;color:var(--c-text-faint);margin-top:4px">
          <?= $prossima ?> = stagione <?= htmlspecialchars(anno_to_stagione($prossima)) ?>
        </div>
      </div>
      <button type="submit" class="btn-sm btn-primary">
        <span class="material-symbols-rounded" style="font-size:16px" aria-hidden="true">add</span>
        Crea stagione
      </button>
    </form>
    <?php if (!empty($anni_disponibili)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:var(--sp-2)">
      <?php foreach ($anni_disponibili as $a): ?>
        <span class="pill<?= (int)$a === (int)CURRENT_ANNO ? ' pill--accent' : '' ?>">
          <?= htmlspecialchars(anno_to_stagione((int)$a)) ?>
        </span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Strumenti -->
<div class="card" style="margin-top:var(--sp-6)">
  <div class="card__header">
    <span class="material-symbols-rounded td-muted" aria-hidden="true">build</span>
    <div class="card__title">Strumenti</div>
  </div>
  <div class="card__body">
    <form method="post" action="<?= BASE_PATH ?>app/admin/export_db.php" style="display:inline">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <button type="submit" class="btn-sm btn-secondary">
        <span class="material-symbols-rounded" style="font-size:15px" aria-hidden="true">download</span>
        Esporta database
      </button>
    </form>
  </div>
</div>

<style>
@media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr !important; } }
</style>

<?php app_layout_end(); ?>
