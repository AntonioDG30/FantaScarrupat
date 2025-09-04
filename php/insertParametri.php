<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: ../Admin.php?tab=dashboard");
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
    header("Location: ../Admin.php?tab=parametri&check=Parametro inserito con successo");
    exit;
  } else {
    header("Location: ../Admin.php?tab=parametri&check=Errore durante l'inserimento dei dati nel database: " . $conn->error);
    exit;
  }
}
?>
