<?php
/**
 * api/admin/competizioni.php — CRUD edizioni e partite
 *
 * POST actions (form → redirect):
 *   add_edizione   — crea competizione_disputata
 *   set_vincitore  — aggiorna vincitore di un'edizione
 *   add_avv        — aggiunge partita_avvessario
 *   add_sol        — aggiunge partita_solitaria
 *   delete_avv     — elimina partita_avvessario
 *   delete_sol     — elimina partita_solitaria
 */

declare(strict_types=1);
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../app/admin/lib/tiebreak.php';

// Questo endpoint fa solo redirect, non restituisce JSON
header('Content-Type: text/html; charset=utf-8', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit('Method not allowed');
}

if (!$csrf_ok) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Token di sicurezza non valido.'];
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=competizioni');
    exit;
}

$action   = $_POST['action']   ?? '';
$redirect = $_POST['redirect'] ?? 'app/admin/index.php?tab=competizioni';

// Sanity check redirect: deve essere relativo e non protocol-relative
if (str_starts_with($redirect, '//') || preg_match('#^[a-z]+://#i', $redirect)) {
    $redirect = 'app/admin/index.php?tab=competizioni';
}
// Se non inizia con '?' o '/' prependi BASE_PATH + app/admin
$dest = str_starts_with($redirect, '?')
    ? BASE_PATH . 'app/admin/index.php' . $redirect
    : BASE_PATH . ltrim($redirect, '/');

// ── add_edizione ──────────────────────────────────────────────────────
if ($action === 'add_edizione') {
    $nome     = trim($_POST['nome_competizione'] ?? '');
    $anno     = (int)($_POST['anno'] ?? 0);
    $vincitore = trim($_POST['vincitore'] ?? '') ?: null;

    if ($nome === '' || $anno < 2016 || $anno > (int)date('Y') + 2) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Dati non validi.'];
        header('Location: ' . $dest); exit;
    }

    try {
        $db->prepare("
            INSERT INTO competizione_disputata (nome_competizione, anno, vincitore)
            VALUES (?, ?, ?)
        ")->execute([$nome, $anno, $vincitore]);
        $new_id = (int)$db->lastInsertId();
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Edizione creata: ' . $nome . ' ' . $anno];
        header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=competizioni&open=' . $new_id);
    } catch (\PDOException $e) {
        error_log('[FS competizioni add_edizione] ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore durante il salvataggio. Riprova.'];
        header('Location: ' . $dest);
    }
    exit;
}

// ── add_competizione (nuovo TIPO base nella tabella `competizione`) ───
if ($action === 'add_competizione') {
    $nome      = trim($_POST['nome_competizione'] ?? '');
    $tipologia = trim($_POST['tipologia'] ?? '');

    if ($nome === '' || $tipologia === '') {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Nome e tipologia sono obbligatori.'];
        header('Location: ' . $dest); exit;
    }
    // La tipologia deve esistere nel set ammesso
    $chk = $db->prepare("SELECT 1 FROM tipologia_competizione WHERE tipologia = ?");
    $chk->execute([$tipologia]);
    if (!$chk->fetchColumn()) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Tipologia non valida.'];
        header('Location: ' . $dest); exit;
    }

    try {
        $db->prepare("INSERT INTO competizione (nome_competizione, tipologia) VALUES (?, ?)")
           ->execute([$nome, $tipologia]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Competizione creata: ' . $nome . ' (' . $tipologia . ')'];
    } catch (\PDOException $e) {
        $dup = ($e->getCode() === '23000');
        error_log('[FS competizioni add_competizione] ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' =>
            $dup ? 'Esiste già una competizione con questo nome.' : 'Errore durante il salvataggio. Riprova.'];
    }
    header('Location: ' . $dest); exit;
}

// ── set_vincitore ─────────────────────────────────────────────────────
if ($action === 'set_vincitore') {
    $id_cd     = (int)($_POST['id_cd'] ?? 0);
    $vincitore = trim($_POST['vincitore'] ?? '') ?: null;

    if ($id_cd === 0) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'ID non valido.'];
        header('Location: ' . $dest); exit;
    }

    $db->prepare("UPDATE competizione_disputata SET vincitore = ? WHERE id_competizione_disputata = ?")
       ->execute([$vincitore, $id_cd]);

    $_SESSION['admin_flash'] = [
        'type' => 'success',
        'msg'  => $vincitore ? 'Vincitore impostato: ' . $vincitore : 'Vincitore rimosso.',
    ];
    header('Location: ' . $dest);
    exit;
}

// ── add_avv (partita_avvessario) ──────────────────────────────────────
if ($action === 'add_avv') {
    $id_cd      = (int)($_POST['id_cd']             ?? 0);
    $casa       = trim($_POST['casa']               ?? '');
    $trasferta  = trim($_POST['trasferta']           ?? '');
    $gol_c      = (int)($_POST['gol_casa']           ?? 0);
    $gol_t      = (int)($_POST['gol_trasferta']      ?? 0);
    $pt_c       = (float)($_POST['punteggio_casa']   ?? 0.0);
    $pt_t       = (float)($_POST['punteggio_trasferta'] ?? 0.0);
    $giornata   = (int)($_POST['giornata']           ?? 1);
    $tipologia  = trim($_POST['tipologia']           ?? 'Calendario');
    $girone     = trim($_POST['girone']              ?? '') ?: null;

    if ($id_cd === 0 || $casa === '' || $trasferta === '' || $casa === $trasferta) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Dati partita non validi (casa = trasferta?).'];
        header('Location: ' . $dest); exit;
    }

    try {
        $db->prepare("
            INSERT INTO partita_avvessario
                (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta,
                 gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta,
                 giornata, tipologia, girone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$id_cd, $casa, $trasferta, $gol_c, $gol_t, $pt_c, $pt_t, $giornata, $tipologia, $girone]);
        // Mantiene coerenti i flag supplementari/rigori sulle sfide a eliminazione
        fs_aggiorna_flag_eliminatorie($db, $id_cd);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Partita aggiunta.'];
    } catch (\PDOException $e) {
        error_log('[FS competizioni add_avv] ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore DB.'];
    }
    header('Location: ' . $dest);
    exit;
}

// ── add_sol (partita_solitaria) ───────────────────────────────────────
if ($action === 'add_sol') {
    $id_cd    = (int)($_POST['id_cd']             ?? 0);
    $fq       = trim($_POST['nome_fantasquadra']  ?? '');
    $punteggio = (float)($_POST['punteggio']      ?? 0.0);
    $giornata = (int)($_POST['giornata']           ?? 1);

    if ($id_cd === 0 || $fq === '') {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Dati non validi.'];
        header('Location: ' . $dest); exit;
    }

    try {
        $db->prepare("
            INSERT INTO partita_solitaria (id_competizione_disputata, nome_fantasquadra, punteggio, giornata)
            VALUES (?, ?, ?, ?)
        ")->execute([$id_cd, $fq, $punteggio, $giornata]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Risultato aggiunto.'];
    } catch (\PDOException $e) {
        error_log('[FS competizioni add_sol] ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore DB.'];
    }
    header('Location: ' . $dest);
    exit;
}

// ── delete_avv ────────────────────────────────────────────────────────
if ($action === 'delete_avv') {
    $id = (int)($_POST['id_partita'] ?? 0);
    if ($id > 0) {
        // Recupera l'edizione prima della cancellazione, per ricalcolare i flag tie-break
        $cd = $db->prepare("SELECT id_competizione_disputata FROM partita_avvessario WHERE id_partita = ?");
        $cd->execute([$id]);
        $id_cd_del = (int)$cd->fetchColumn();
        $db->prepare("DELETE FROM partita_avvessario WHERE id_partita = ?")
           ->execute([$id]);
        if ($id_cd_del) fs_aggiorna_flag_eliminatorie($db, $id_cd_del);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Partita eliminata.'];
    }
    header('Location: ' . $dest);
    exit;
}

// ── delete_sol ────────────────────────────────────────────────────────
if ($action === 'delete_sol') {
    $id = (int)($_POST['id_partita'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM partita_solitaria WHERE id_partita_solitaria = ?")
           ->execute([$id]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Risultato eliminato.'];
    }
    header('Location: ' . $dest);
    exit;
}

// ── delete_edizione (intera competizione_disputata + cascata) ─────────
if ($action === 'delete_edizione') {
    $id_cd = (int)($_POST['id_cd'] ?? 0);
    if ($id_cd === 0) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'ID edizione non valido.'];
        header('Location: ' . $dest); exit;
    }
    try {
        // FK ON DELETE CASCADE: partita_avvessario, partita_solitaria,
        // formazione (→ formazione_giocatore) vengono rimosse automaticamente.
        $st = $db->prepare("DELETE FROM competizione_disputata WHERE id_competizione_disputata = ?");
        $st->execute([$id_cd]);
        $_SESSION['admin_flash'] = $st->rowCount()
            ? ['type' => 'success', 'msg' => 'Edizione eliminata con tutte le partite e formazioni collegate.']
            : ['type' => 'error',   'msg' => 'Edizione non trovata.'];
    } catch (\PDOException $e) {
        error_log('[FS competizioni delete_edizione] ' . $e->getMessage());
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Errore durante l\'eliminazione.'];
    }
    // L'edizione non esiste più: torna alla lista, non alla scheda aperta.
    header('Location: ' . BASE_PATH . 'app/admin/index.php?tab=competizioni'); exit;
}

// ── calc_vincitore ────────────────────────────────────────────────────
if ($action === 'calc_vincitore') {
    $id_cd = (int)($_POST['id_cd'] ?? 0);
    if ($id_cd === 0) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'ID competizione non valido.'];
        header('Location: ' . $dest); exit;
    }

    $res = fs_calcola_vincitore($db, $id_cd);
    if ($res['vincitore'] !== null) {
        $db->prepare("UPDATE competizione_disputata SET vincitore = ? WHERE id_competizione_disputata = ?")
           ->execute([$res['vincitore'], $id_cd]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => fs_descrivi_esito_vincitore($res)];
    } else {
        $_SESSION['admin_flash'] = ['type' => 'error', 'msg' => fs_descrivi_esito_vincitore($res)];
    }
    header('Location: ' . $dest); exit;
}

$_SESSION['admin_flash'] = ['type' => 'error', 'msg' => 'Azione non riconosciuta.'];
header('Location: ' . $dest);
exit;
