<?php
  // Parametri di connessione al database
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "my_fantascarrupat";

  // Connessione al database
  $conn = new mysqli($servername, $username, $password, $dbname);

  // Verifica della connessione
  if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
  }
?>
