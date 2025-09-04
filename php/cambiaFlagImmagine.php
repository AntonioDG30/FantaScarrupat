<?php
  session_start();

  if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../Admin.php?tab=dashboard");
    exit;
  }

  global $conn;
  include 'connectionDB.php';

  $id_immagine = $_GET['id_immagine'];

  $sql = "UPDATE immagine SET flag_visibile = NOT flag_visibile WHERE id_immagine = '$id_immagine'";
  $conn->query($sql);

  header("Location: ../Admin.php?tab=gallery");
  exit;
?>