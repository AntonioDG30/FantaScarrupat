<?php
// Includi il file di connessione al database
global $conn;
include 'connectionDB.php';

// Apro il file CSV in modalità lettura
$csv_file = fopen('../file/Lista.csv', 'r');

// Controllo se il file è stato aperto correttamente
if (!$csv_file) {
  die("Impossibile aprire il file CSV");
}

// Itero sulle righe del file CSV
while (($row = fgetcsv($csv_file, 1000, ",")) !== FALSE) {
  // Prendo solo le colonne desiderate (1, 3, 4 e 10)
  $codice_fantacalcio = addslashes($row[0]);
  $nome_giocatore = addslashes($row[2]);
  $ruolo = addslashes($row[3]);
  $squadra_reale = addslashes($row[9]); // la colonna 10

  // Query SQL per verificare se esiste già una riga con le stesse colonne nella tabella giocatore
  $check_query = "SELECT * FROM giocatore WHERE codice_fantacalcio = '$codice_fantacalcio' AND nome_giocatore = '$nome_giocatore' AND ruolo = '$ruolo' AND squadra_reale = '$squadra_reale'";
  $result = $conn->query($check_query);

  // Se non esiste una riga con le stesse colonne, genero il comando SQL di inserimento
  if ($result->num_rows == 0) {
    $sql_command = "INSERT INTO giocatore (codice_fantacalcio, nome_giocatore, ruolo, squadra_reale) VALUES ('$codice_fantacalcio', '$nome_giocatore', '$ruolo', '$squadra_reale');\n";
    $stmt = $conn->prepare($sql_command);
    $stmt->execute();
  }
}

// Chiudo i file
fclose($csv_file);

// Chiudo la connessione al database
$conn->close();

echo "Database giocatori correttamente aggiornato";
?>
