<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit;
}

// Includi il file di connessione al database
global $conn;
include 'connectionDB.php';

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $numeroParametro = $conn->real_escape_string($_POST['numeroParametro']);
  $descParametro = $conn->real_escape_string($_POST['descParametro']);

  // Query per inserire i dati nel database
  $sql_insert = "INSERT INTO parametri_rosa (numero_parametro, testo_parametro, flag_visibile)
                                    VALUES ('$numeroParametro', '$descParametro', '1')";

  // Esegui la query
  if ($conn->query($sql_insert) === TRUE) {
    header("Location: ../gestisciParametri.php");
    exit;
  } else {
    header("Location: ../inserisciParametro.php?check=Errore durante l'inserimento dei dati nel database: " . $conn->error);
    exit;
  }
}
?>
