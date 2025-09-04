<?php
// Backend per elaborare l'inserimento dei parametri selezionati

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: ../Admin.php?tab=dashboard");
  exit;
}
global $conn;
require_once 'connectionDB.php';

// Controllo del metodo di richiesta
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../Admin.php?tab=parametri&check=Metodo non consentito");
  exit;
}

// Recupera e valida i parametri inviati
// Expected: $_POST['parametro'][id_rosa] = array(1 => id_parametro1, 2 => id_parametro2)
if (empty($_POST['parametro']) || !is_array($_POST['parametro'])) {
  header("Location: ../Admin.php?tab=parametri&check=Dati parametri mancanti");
  exit;
}

$parametriSele = $_POST['parametro'];

// Inizio transazione per coerenza
$conn->begin_transaction();
try {
  // Prepariamo la cancellazione dei parametri esistenti per queste rose
  $roseIds = array_map('intval', array_keys($parametriSele));
  $in  = str_repeat('?,', count($roseIds) - 1) . '?';
  $delStmt = $conn->prepare("DELETE FROM parametri_utilizzati WHERE id_rosa IN ($in)");
  $delStmt->bind_param(str_repeat('i', count($roseIds)), ...$roseIds);
  $delStmt->execute();
  $delStmt->close();

  // Prepariamo l'INSERT con IGNORE per evitare doppioni fortuiti
  $insStmt = $conn->prepare(
    "INSERT INTO parametri_utilizzati (id_rosa, id_parametro) VALUES (?, ?)"
  );

  // Cicliamo su ogni rosa e i suoi parametri
  foreach ($parametriSele as $idRosa => $listaParam) {
    $idRosa = (int) $idRosa;
    if (!is_array($listaParam)) continue;

    foreach ($listaParam as $pos => $idParam) {
      $idParam = (int) $idParam;
      if ($idParam <= 0) continue;

      $insStmt->bind_param('ii', $idRosa, $idParam);
      $insStmt->execute();
    }
  }
  $insStmt->close();

  // Commit
  $conn->commit();

  header("Location: ../Admin.php?tab=rose&check=Parametri salvati con successo");
  exit;

} catch (Exception $e) {
  $conn->rollback();
  error_log('Errore parametri_utilizzati: ' . $e->getMessage());
  header("Location: ../Admin.php?tab=parametri&check=Errore durante il salvataggio parametri");
  exit;
}
