<?php
  session_start();

  if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
  }


  global $conn;
  include 'connectionDB.php';

  $id_parametro = $_GET['id_parametro'];

  $sql = "UPDATE parametri_rosa SET flag_visibile = NOT flag_visibile WHERE id_parametro = '$id_parametro'";
  $conn->query($sql);

  header("Location: ../gestisciParametri.php");
  exit;
?>
