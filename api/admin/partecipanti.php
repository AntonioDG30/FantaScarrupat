<?php
/**
 * api/admin/partecipanti.php — CRUD fantasquadre
 *
 * POST actions:
 *   toggle_flag      — toggling flag_attuale (AJAX)
 *   update           — aggiorna fantallenatore + foto (form POST → redirect)
 *   add              — nuova fantasquadra (form POST → redirect)
 */

declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

// ── GET: export CSV partecipanti ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action !== 'export_csv') { http_response_code(405); exit; }

    $rows = $db->query("
        SELECT nome_fantasquadra, fantallenatore,
               CASE WHEN flag_attuale = 1 THEN 'Sì' ELSE 'No' END AS attuale
          FROM fantasquadra
         ORDER BY flag_attuale DESC, nome_fantasquadra ASC
    ")->fetchAll(\PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="partecipanti.csv"');
    header('Cache-Control: no-store');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Fantasquadra', 'Fantallenatore', 'Stagione Attuale']);
    foreach ($rows as $r) {
        fputcsv($out, [csv_safe($r['nome_fantasquadra']), csv_safe($r['fantallenatore']), $r['attuale']]);
    }
    fclose($out);
    exit;
}

$action = $_POST['action'] ?? '';

// ── toggle_flag (AJAX) ────────────────────────────────────────────────
if ($action === 'toggle_flag') {
    if (!$csrf_ok) { http_response_code(403); echo json_encode(['error' => 'CSRF']); exit; }
    $fq  = trim($_POST['nome_fantasquadra'] ?? '');
    $val = (int)($_POST['flag_attuale'] ?? 0);
    if ($fq === '') { http_response_code(400); echo json_encode(['error' => 'Missing param']); exit; }
    $db->prepare("UPDATE fantasquadra SET flag_attuale = ? WHERE nome_fantasquadra = ?")
       ->execute([$val ? 1 : 0, $fq]);
    echo json_encode(['success' => true]);
    exit;
}

// Le azioni seguenti fanno redirect → non JSON
header('Content-Type: text/html; charset=utf-8', true); // override header da _auth

if (!$csrf_ok) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Token di sicurezza non valido.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti');
    exit;
}

// ── add (nuova fantasquadra) ──────────────────────────────────────────
if ($action === 'add') {
    $nome  = trim($_POST['nome_fantasquadra_new'] ?? '');
    $fant  = trim($_POST['fantallenatore']         ?? '');
    $flag  = isset($_POST['flag_attuale']) ? 1 : 0;

    if ($nome === '' || $fant === '') {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Nome e fantallenatore obbligatori.'];
        header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti&new=1');
        exit;
    }

    // Foto upload
    $foto_file = '';
    if (!empty($_FILES['immagine_fantallenatore']['name'])) {
        $foto_file = handle_foto_upload($_FILES['immagine_fantallenatore']);
        if ($foto_file === false) {
            $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore upload foto.'];
            header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti&new=1');
            exit;
        }
    }

    // Scudetto upload
    $scud_file = '';
    if (!empty($_FILES['scudetto']['name'])) {
        $scud_file = handle_scudetto_upload($_FILES['scudetto']);
        if ($scud_file === false) {
            $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore upload scudetto.'];
            header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti&new=1');
            exit;
        }
    }

    try {
        $db->prepare("
            INSERT INTO fantasquadra (nome_fantasquadra, fantallenatore, immagine_fantallenatore, flag_attuale, scudetto)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$nome, $fant, $foto_file, $flag, $scud_file]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Fantasquadra aggiunta: ' . $nome];
    } catch (\PDOException $e) {
        error_log('[FS partecipanti add] ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore DB. Nome già esistente?'];
    }
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti');
    exit;
}

// ── update ────────────────────────────────────────────────────────────
if ($action === 'update') {
    $fq   = trim($_POST['nome_fantasquadra'] ?? '');
    $fant = trim($_POST['fantallenatore']     ?? '');
    $flag = isset($_POST['flag_attuale']) ? 1 : 0;

    if ($fq === '' || $fant === '') {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Dati mancanti.'];
        header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti');
        exit;
    }

    // Foto upload opzionale
    $foto_sql    = '';
    $foto_params = [];
    if (!empty($_FILES['immagine_fantallenatore']['name'])) {
        $new_foto = handle_foto_upload($_FILES['immagine_fantallenatore']);
        if ($new_foto === false) {
            $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore upload foto.'];
            header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti&edit=' . urlencode($fq));
            exit;
        }
        $foto_sql    = ', immagine_fantallenatore = ?';
        $foto_params = [$new_foto];
    }

    // Scudetto upload opzionale
    $scud_sql    = '';
    $scud_params = [];
    if (!empty($_FILES['scudetto']['name'])) {
        $new_scud = handle_scudetto_upload($_FILES['scudetto']);
        if ($new_scud === false) {
            $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore upload scudetto.'];
            header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti&edit=' . urlencode($fq));
            exit;
        }
        $scud_sql    = ', scudetto = ?';
        $scud_params = [$new_scud];
    }

    $db->prepare("
        UPDATE fantasquadra
           SET fantallenatore = ?, flag_attuale = ? $foto_sql $scud_sql
         WHERE nome_fantasquadra = ?
    ")->execute(array_merge([$fant, $flag], $foto_params, $scud_params, [$fq]));

    $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => $fq . ' aggiornato.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=partecipanti');
    exit;
}

// ── Helper: foto upload ───────────────────────────────────────────────
function handle_foto_upload(array $file): string|false
{
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 3 * 1024 * 1024; // 3 MB

    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $max_size)        return false;

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true))  return false;

    $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext      = $ext_map[$mime];
    $filename = uniqid('coach_', true) . '.' . $ext;
    $dest     = __DIR__ . '/../../uploads/coaches/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return $filename;
}

// ── Helper: scudetto upload ───────────────────────────────────────────
function handle_scudetto_upload(array $file): string|false
{
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2 MB

    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $max_size)        return false;

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed, true))  return false;

    $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext      = $ext_map[$mime];
    $filename = uniqid('scud_', true) . '.' . $ext;
    $dir      = __DIR__ . '/../../uploads/teams/';

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $dest = $dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return $filename;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
