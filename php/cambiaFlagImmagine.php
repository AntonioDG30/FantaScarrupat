<?php
  session_start();

  if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
  }


  global $conn;
  include 'connectionDB.php';

  $id_immagine = $_GET['id_immagine'];

  $sql = "UPDATE immagine SET flag_visibile = NOT flag_visibile WHERE id_immagine = '$id_immagine'";
  $conn->query($sql);

  header("Location: ../gestisciGallery.php");
  exit;
?>
