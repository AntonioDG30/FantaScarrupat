<?php
  session_start();

  if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
  }


  global $conn;
  include 'connectionDB.php';

  $nome_fantasquadra = $_GET['nome_fantasquadra'];

  $sql = "UPDATE fantasquadra SET flag_attuale = NOT flag_attuale WHERE nome_fantasquadra = '$nome_fantasquadra'";
  $conn->query($sql);

  header("Location: ../gestisciPartecipanti.php");
  exit;
?>
