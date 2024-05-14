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
if (isset($_FILES['fileRose'])) {
  $file_error = $_FILES['fileRose']['error'];

  // Verifica se non ci sono errori nell'upload del file
  if ($file_error === UPLOAD_ERR_OK) {
    $fileRose = $_FILES['fileRose']['tmp_name'];

    // Apro il file CSV in modalità lettura
    $handle = fopen($fileRose, 'r');

    // Controllo se il file è stato aperto correttamente
    if (!$handle) {
      header("Location: ../inserisciRose.php?check=Impossibile aprire il file CSV");
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
    $anno = date("Y");

    // Query per inserire i giocatori nella tabella rosa
    $sql_insert = "INSERT INTO rosa (nome_fantasquadra, id_giocatore, crediti_pagati, anno) VALUES ";

    // Array per memorizzare i giocatori già inseriti per evitare duplicati
    $giocatori_inseriti = array();

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
            $sql_select = "SELECT * FROM rosa WHERE nome_fantasquadra = '$nome_fantasquadra' AND id_giocatore = '$id_giocatore' AND crediti_pagati = $crediti_pagati AND anno = $anno";
            $result = $conn->query($sql_select);
            if ($result && $result->num_rows > 0) {
              // La riga è già presente, quindi passiamo alla riga successiva
              continue;
            }

            // Aggiungi il giocatore alla lista dei giocatori inseriti
            $giocatori_inseriti[$codice_fantacalcio] = $id_giocatore;

            // Aggiungi la query SQL per l'inserimento del giocatore
            $sql_insert .= "('$nome_fantasquadra', $id_giocatore, $crediti_pagati, $anno), ";
          }
        }
      }
    }

    // Chiudo il file CSV
    fclose($handle);

    // Rimuovi l'ultima virgola e spazio dalla query SQL
    $sql_insert = rtrim($sql_insert, ", ");

    // Esegui la query per inserire i giocatori nella tabella rosa
    if (!empty($giocatori_inseriti)) {
      if ($conn->query($sql_insert) === TRUE) {
        header("Location: ../visualizzaRose.php");
      } else {
        header("Location: ../inserisciRose.php?Errore durante l'inserimento dei giocatori nella tabella rosa.");
      }
    } else {
      header("Location: ../visualizzaRose.php");
    }

  } else {
    // Gestione degli errori di upload
    switch ($file_error) {
      case UPLOAD_ERR_INI_SIZE:
        header("Location: ../inserisciRose.php?check=Il file caricato supera la dimensione massima consentita.");
        exit;
      case UPLOAD_ERR_FORM_SIZE:
        header("Location: ../inserisciRose.php?check=Il file caricato supera la dimensione massima consentita nel form HTML.");
        exit;
      case UPLOAD_ERR_PARTIAL:
        header("Location: ../inserisciRose.php?check=Il file è stato caricato solo parzialmente.");
        exit;
      case UPLOAD_ERR_NO_FILE:
        header("Location: ../inserisciRose.php?check=Nessun file è stato caricato.");
        exit;
      case UPLOAD_ERR_NO_TMP_DIR:
        header("Location: ../inserisciRose.php?check=Manca una cartella temporanea.");
        exit;
      case UPLOAD_ERR_CANT_WRITE:
        header("Location: ../inserisciRose.php?check=Impossibile scrivere il file sul disco.");
        exit;
      case UPLOAD_ERR_EXTENSION:
        header("Location: ../inserisciRose.php?check=Un'estensione PHP ha bloccato l'upload del file.");
        exit;
      default:
        header("Location: ../inserisciRose.php?check=Si è verificato un errore durante l'upload del file.");
        exit;
    }
  }
}
?>
