<?php
  session_start();

  if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../Admin.php?tab=dashboard");
    exit;
  }

  global $conn;
  include 'connectionDB.php';

  $id_parametro = $_GET['id_parametro'];

  $sql = "UPDATE parametri_rosa SET flag_visibile = NOT flag_visibile WHERE id_parametro = '$id_parametro'";
  $conn->query($sql);

  header("Location: ../Admin.php?tab=parametri");
  exit;
?>
