<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: ../Admin.php?tab=dashboard");
  exit;
}

// Includi il file di connessione al database
global $conn;
include 'connectionDB.php';

// Verifica se è stato inviato un file
if (isset($_FILES['fileRose'])) {
  $file_error = $_FILES['fileRose']['error'];

  // Verifica se non ci sono errori nell'upload del file
  if ($file_error === UPLOAD_ERR_OK) {
    $fileRose = $_FILES['fileRose']['tmp_name'];

    // Apro il file CSV in modalità lettura
    $handle = fopen($fileRose, 'r');

    // Controllo se il file è stato aperto correttamente
    if (!$handle) {
      header("Location: ../Admin.php?tab=rose&check=Impossibile aprire il file CSV");
      exit;
    }

    // Query per recuperare i nomi delle squadre dalla tabella fantasquadre
    $query = "SELECT nome_fantasquadra FROM fantasquadra";
    $result = $conn->query($query);

    // Inizializza un array per memorizzare i nomi delle squadre
    $nomiSquadre = array();

    // Verifica se sono presenti risultati
    if ($result->num_rows > 0) {
      // Itera sui risultati e aggiungi i nomi delle squadre all'array
      while ($row = $result->fetch_assoc()) {
        $nomiSquadre[] = $row['nome_fantasquadra'];
      }
    }

    // Anno attuale
    $anno = '2026'; //date("Y");

    // Query per inserire i giocatori nella tabella rosa
    $sql_insert1 = "INSERT INTO rosa (nome_fantasquadra, anno) VALUES ";
    $sql_insert2 = "INSERT INTO dettagli_rosa (id_rosa, id_giocatore, crediti_pagati) VALUES ";

    // Array per memorizzare i giocatori già inseriti per evitare duplicati
    $giocatori_inseriti = array();

    // Array per tracciare i nomi già inseriti
    $processedSquadre = [];

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      // Verifica che ci siano almeno 3 colonne e che il nome sia uno di quelli consentiti
      if (count($data) >= 3 && in_array($data[0], $nomiSquadre)) {
        $nome = $conn->real_escape_string($data[0]);

        // Se non è vuoto e non è già stato processato
        if (!empty($nome) && !isset($processedSquadre[$nome])) {
          // Segnalo che l’ho già inserito
          $processedSquadre[$nome] = true;

          // Aggiungo la riga all’INSERT
          $sql_insert1 .= "('$nome', $anno), ";
        }
      }
    }


    $sql_insert1 = rtrim($sql_insert1, ", ");

    if ($conn->query($sql_insert1) === False) {
      header("Location: ../inserisciRose.php?Errore durante l'inserimento delle rose nell'entita ''rose''.");
    } else {

      // Riavvolgi il puntatore all’inizio del file
      rewind($handle);

      // Leggi il file CSV riga per riga
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Verifica che ci siano abbastanza valori nella riga e che il nome_fantasquadra sia valido
        if (count($data) >= 3 && in_array($data[0], $nomiSquadre)) {
          $nome_fantasquadra = $conn->real_escape_string($data[0]);
          $codice_fantacalcio = $conn->real_escape_string($data[1]);
          $crediti_pagati = $conn->real_escape_string($data[2]);

          // Verifica che i valori non siano vuoti
          if (!empty($nome_fantasquadra) && !empty($codice_fantacalcio) && !empty($crediti_pagati)) {
            // Ottieni l'id_giocatore più grande associato al codice_fantacalcio dalla tabella giocatore
            $sql_select = "SELECT MAX(id_giocatore) AS id_giocatore FROM giocatore WHERE codice_fantacalcio = '$codice_fantacalcio'";
            $result = $conn->query($sql_select);
            if ($result && $result->num_rows > 0) {
              $row = $result->fetch_assoc();
              $id_giocatore = $row['id_giocatore'];

              // Query per verificare se la riga è già presente nel database
              $sql_select = "SELECT * FROM rosa AS R, dettagli_rosa AS DR WHERE R.id_rosa = DR.id_rosa AND R.nome_fantasquadra = '$nome_fantasquadra' AND DR.id_giocatore = '$id_giocatore' AND DR.crediti_pagati = $crediti_pagati AND R.anno = $anno";
              $result = $conn->query($sql_select);
              if ($result && $result->num_rows > 0) {
                // La riga è già presente, quindi passiamo alla riga successiva
                continue;
              }

              // Aggiungi il giocatore alla lista dei giocatori inseriti
              $giocatori_inseriti[$codice_fantacalcio] = $id_giocatore;

              $sql_select = "SELECT MAX(id_rosa) AS id_rosa FROM rosa WHERE nome_fantasquadra = '$nome_fantasquadra'";
              $result = $conn->query($sql_select);
              if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $id_rosa = $row['id_rosa'];


                $sql_insert2 .= "($id_rosa, $id_giocatore, $crediti_pagati), ";
              }
            }
          }
        }
      }

      // Chiudo il file CSV
      fclose($handle);

      // Rimuovi l'ultima virgola e spazio dalla query SQL
      $sql_insert2 = rtrim($sql_insert2, ", ");

      print ($sql_insert2);

      // Esegui la query per inserire i giocatori nella tabella rosa
      if (!empty($giocatori_inseriti)) {
        if ($conn->query($sql_insert2) === TRUE) {
          header("Location: ../impostaParametri.php?check=start&anno=" . $anno);
        } else {
          header("Location: ../Admin.php?tab=rose&check=Errore durante l'inserimento dei giocatori nella tabella rosa.");
        }
      } else {
        header("Location: ../Admin.php?tab=rose&anno_param=" . $anno . "&check=Rose importate con successo");
      }
    }



  } else {
    // Gestione degli errori di upload
    switch ($file_error) {
      case UPLOAD_ERR_INI_SIZE:
        header("Location: ../Admin.php?tab=rose&check=Il file caricato supera la dimensione massima consentita.");
        exit;
      case UPLOAD_ERR_FORM_SIZE:
        header("Location: ../Admin.php?tab=rose&check=Il file caricato supera la dimensione massima consentita nel form HTML.");
        exit;
      case UPLOAD_ERR_PARTIAL:
        header("Location: ../Admin.php?tab=rose&check=Il file è stato caricato solo parzialmente.");
        exit;
      case UPLOAD_ERR_NO_FILE:
        header("Location: ../Admin.php?tab=rose&check=Nessun file è stato caricato.");
        exit;
      case UPLOAD_ERR_NO_TMP_DIR:
        header("Location: ../Admin.php?tab=rose&check=Manca una cartella temporanea.");
        exit;
      case UPLOAD_ERR_CANT_WRITE:
        header("Location: ../Admin.php?tab=rose&check=Impossibile scrivere il file sul disco.");
        exit;
      case UPLOAD_ERR_EXTENSION:
        header("Location: ../Admin.php?tab=rose&check=Un'estensione PHP ha bloccato l'upload del file.");
        exit;
      default:
        header("Location: ../Admin.php?tab=rose&check=Si è verificato un errore durante l'upload del file.");
        exit;
    }
  }
}
?>
