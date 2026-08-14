<?php
/**
 * api/admin/gallery.php — Upload e gestione immagini galleria
 *
 * POST actions (JSON response):
 *   upload              — carica file multipli
 *   toggle_visibilita   — cambia is_visibile
 *   update_didascalia   — salva testo didascalia
 *   delete              — elimina immagine da DB e disco
 */

declare(strict_types=1);
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!$csrf_ok) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF mismatch']);
    exit;
}

$action       = $_POST['action'] ?? '';
$upload_dir   = __DIR__ . '/../../uploads/gallery/';
$max_size     = 5 * 1024 * 1024; // 5 MB
$allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
$ext_map      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// ── upload ────────────────────────────────────────────────────────────
if ($action === 'upload') {
    if (empty($_FILES['files'])) {
        echo json_encode(['error' => 'Nessun file ricevuto.']);
        exit;
    }

    $didascalia = trim($_POST['didascalia'] ?? '');
    $saved      = 0;
    $errors     = [];

    // Normalizza struttura $_FILES per upload multiplo
    $files = [];
    foreach ($_FILES['files'] as $key => $values) {
        foreach ($values as $i => $v) {
            $files[$i][$key] = $v;
        }
    }

    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Errore upload: codice ' . $file['error'];
            continue;
        }
        if ($file['size'] > $max_size) {
            $errors[] = $file['name'] . ': file troppo grande (max 5 MB).';
            continue;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowed_mime, true)) {
            $errors[] = $file['name'] . ': formato non supportato.';
            continue;
        }

        $ext      = $ext_map[$mime];
        $filename = uniqid('img_', true) . '.' . $ext;
        $dest     = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $errors[] = $file['name'] . ': impossibile salvare il file.';
            continue;
        }

        try {
            $db->prepare("
                INSERT INTO immagine (percorso, didascalia, is_visibile, uploaded_at)
                VALUES (?, ?, 1, NOW())
            ")->execute([$filename, $didascalia ?: null]);
            $saved++;
        } catch (\PDOException $e) {
            error_log('[FS gallery upload] ' . $e->getMessage());
            @unlink($dest);
            $errors[] = $file['name'] . ': errore DB.';
        }
    }

    if ($saved > 0) {
        echo json_encode([
            'success' => true,
            'saved'   => $saved,
            'errors'  => $errors,
        ]);
    } else {
        http_response_code(422);
        echo json_encode(['error' => implode(' ', $errors) ?: 'Nessun file salvato.']);
    }
    exit;
}

// ── toggle_visibilita ─────────────────────────────────────────────────
if ($action === 'toggle_visibilita') {
    $id  = (int)($_POST['id_immagine'] ?? 0);
    $vis = (int)($_POST['is_visibile'] ?? 0);
    if ($id === 0) { echo json_encode(['error' => 'ID mancante']); exit; }

    $db->prepare("UPDATE immagine SET is_visibile = ? WHERE id_immagine = ?")
       ->execute([$vis ? 1 : 0, $id]);
    echo json_encode(['success' => true]);
    exit;
}

// ── update_didascalia ─────────────────────────────────────────────────
if ($action === 'update_didascalia') {
    $id  = (int)($_POST['id_immagine'] ?? 0);
    $did = trim($_POST['didascalia']   ?? '');
    if ($id === 0) { echo json_encode(['error' => 'ID mancante']); exit; }

    $db->prepare("UPDATE immagine SET didascalia = ? WHERE id_immagine = ?")
       ->execute([$did ?: null, $id]);
    echo json_encode(['success' => true]);
    exit;
}

// ── delete ────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id_immagine'] ?? 0);
    if ($id === 0) { echo json_encode(['error' => 'ID mancante']); exit; }

    // Recupera percorso prima di eliminare
    $stmt = $db->prepare("SELECT percorso FROM immagine WHERE id_immagine = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$row) { echo json_encode(['error' => 'Immagine non trovata']); exit; }

    $db->prepare("DELETE FROM immagine WHERE id_immagine = ?")
       ->execute([$id]);

    // Elimina file fisico (silenzioso se già mancante)
    $filepath = $upload_dir . $row['percorso'];
    if (is_file($filepath)) {
        @unlink($filepath);
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Azione non riconosciuta.']);
