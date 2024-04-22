<?php
// Connessione al database
global $conn;
include 'connectionDB.php';

// Verifica la connessione
if ($conn->connect_error) {
  die("Connessione fallita: " . $conn->connect_error);
}

// Query per recuperare i nomi delle squadre dalla tabella fantasquadre
$query = "SELECT nome_fantasquadra FROM fantasquadra";

// Esegui la query
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

// Apri il file CSV
$file = fopen("../file/fantascarrupat-rosters.csv", "r");

// Anno attuale
$anno = date("Y");

// Query per inserire i giocatori nella tabella rosa
$sql_insert = "INSERT INTO rosa (nome_fantasquadra, id_giocatore, crediti_pagati, anno) VALUES ";

// Array per memorizzare i giocatori già inseriti per evitare duplicati
$giocatori_inseriti = array();

// Leggi il file CSV riga per riga
while (($data = fgetcsv($file, 0, ",")) !== FALSE) {
  // Verifica che ci siano abbastanza valori nella riga
  if (count($data) >= 3 && in_array($data[0], $nomiSquadre)) {
    $nome_fantasquadra = $data[0];
    $codice_fantacalcio = $data[1];
    $crediti_pagati = $data[2];

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

// Chiudi il file CSV
fclose($file);

// Rimuovi l'ultima virgola e spazio dalla query SQL
$sql_insert = rtrim($sql_insert, ", ");

// Esegui la query per inserire i giocatori nella tabella rosa
if (!empty($giocatori_inseriti)) {
  if ($conn->query($sql_insert) === TRUE) {
    echo "Giocatori inseriti correttamente nella tabella rosa.";
  } else {
    echo "Errore durante l'inserimento dei giocatori nella tabella rosa: " . $conn->error;
  }
} else {
  echo "Nessun giocatore da inserire nella tabella rosa.";
}

// Chiudi la connessione al database
$conn->close();
?>
