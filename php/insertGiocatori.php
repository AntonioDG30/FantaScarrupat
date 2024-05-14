<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}

// Includi il file di connessione al database
global $conn;
include 'connectionDB.php';

// Verifica se è stato inviato un file
if (isset($_FILES['fileCalciatori'])) {
  $file_error = $_FILES['fileCalciatori']['error'];

  // Verifica se non ci sono errori nell'upload del file
  if ($file_error === UPLOAD_ERR_OK) {
    $fileCalciatori = $_FILES['fileCalciatori']['tmp_name'];

    // Apro il file CSV in modalità lettura
    $handle = fopen($fileCalciatori, 'r');

    // Controllo se il file è stato aperto correttamente
    if (!$handle) {
      header("Location: ../inserisciCalciatori.php?check=Impossibile aprire il file CSV");
      exit;
    }

    // Itero sulle righe del file CSV
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
      // Verifica che le colonne necessarie esistano
      if (!isset($row[0]) || !isset($row[2]) || !isset($row[3]) || !isset($row[9])) {
        fclose($handle);
        header("Location: ../inserisciCalciatori.php?check=Il file CSV non contiene le colonne richieste");
        exit;
      }

      // Prendo solo le colonne desiderate (1, 3, 4 e 10)
      $codice_fantacalcio = $conn->real_escape_string($row[0]);
      $nome_giocatore = $conn->real_escape_string($row[2]);
      $ruolo = $conn->real_escape_string($row[3]);
      $squadra_reale = $conn->real_escape_string($row[9]); // la colonna 10

      // Query SQL per verificare se esiste già una riga con le stesse colonne nella tabella giocatore
      $check_query = "SELECT * FROM giocatore WHERE codice_fantacalcio = '$codice_fantacalcio' AND nome_giocatore = '$nome_giocatore' AND ruolo = '$ruolo' AND squadra_reale = '$squadra_reale'";
      $result = $conn->query($check_query);

      // Se non esiste una riga con le stesse colonne, genero il comando SQL di inserimento
      if ($result->num_rows == 0) {
        $sql_command = "INSERT INTO giocatore (codice_fantacalcio, nome_giocatore, ruolo, squadra_reale) VALUES ('$codice_fantacalcio', '$nome_giocatore', '$ruolo', '$squadra_reale');";
        $stmt = $conn->prepare($sql_command);
        $stmt->execute();
      }
    }

    // Chiudo il file
    fclose($handle);

    header("Location: ../visualizzaCalciatori.php?page=1");
    exit;
  } else {
    // Gestione degli errori di upload
    switch ($file_error) {
      case UPLOAD_ERR_INI_SIZE:
        header("Location: ../inserisciCalciatori.php?check=Il file caricato supera la dimensione massima consentita.");
        exit;
      case UPLOAD_ERR_FORM_SIZE:
        header("Location: ../inserisciCalciatori.php?check=Il file caricato supera la dimensione massima consentita nel form HTML.");
        exit;
      case UPLOAD_ERR_PARTIAL:
        header("Location: ../inserisciCalciatori.php?check=Il file è stato caricato solo parzialmente.");
        exit;
      case UPLOAD_ERR_NO_FILE:
        header("Location: ../inserisciCalciatori.php?check=Nessun file è stato caricato.");
        exit;
      case UPLOAD_ERR_NO_TMP_DIR:
        header("Location: ../inserisciCalciatori.php?check=Manca una cartella temporanea.");
        exit;
      case UPLOAD_ERR_CANT_WRITE:
        header("Location: ../inserisciCalciatori.php?check=Impossibile scrivere il file sul disco.");
        exit;
      case UPLOAD_ERR_EXTENSION:
        header("Location: ../inserisciCalciatori.php?check=Un'estensione PHP ha bloccato l'upload del file.");
        exit;
      default:
        header("Location: ../inserisciCalciatori.php?check=Si è verificato un errore durante l'upload del file.");
        exit;
    }
  }
}
?>
