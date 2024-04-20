<?php
// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "my_fantascarrupat";

// Connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica della connessione
if ($conn->connect_error) {
  die("Connessione fallita: " . $conn->connect_error);
}

// Apro il file CSV in modalità lettura
$csv_file = fopen('file/lista.csv', 'r');

// Controllo se il file è stato aperto correttamente
if (!$csv_file) {
  die("Impossibile aprire il file CSV");
}

// Apro il file SQL in modalità scrittura
$sql_file = fopen('output.sql', 'w');

// Controllo se il file è stato aperto correttamente
if (!$sql_file) {
  die("Impossibile creare il file SQL");
}

// Itero sulle righe del file CSV
while (($row = fgetcsv($csv_file, 1000, ",")) !== FALSE) {
  // Prendo solo le colonne desiderate (1, 3, 4 e 10)
  $id_giocatore = addslashes($row[0]);
  $nome_giocatore = addslashes($row[2]);
  $ruolo = addslashes($row[3]);
  $squadra = addslashes($row[9]); // la colonna 10

  // Query SQL per verificare se esiste già una riga con le stesse colonne nella tabella giocatore
  $check_query = "SELECT * FROM giocatore WHERE id_giocatore = '$id_giocatore' AND nome_giocatore = '$nome_giocatore' AND ruolo = '$ruolo' AND squadra = '$squadra'";
  $result = $conn->query($check_query);

  // Se non esiste una riga con le stesse colonne, genero il comando SQL di inserimento
  if ($result->num_rows == 0) {
    $sql_command = "INSERT INTO giocatore (id_giocatore, nome_giocatore, ruolo, squadra) VALUES ('$id_giocatore', '$nome_giocatore', '$ruolo', '$squadra');\n";

    // Scrivo il comando nel file SQL
    fwrite($sql_file, $sql_command);
  }
}

// Chiudo i file
fclose($csv_file);
fclose($sql_file);

// Chiudo la connessione al database
$conn->close();

echo "File SQL generato con successo!";
?>
