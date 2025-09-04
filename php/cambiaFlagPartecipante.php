<?php
  session_start();

  if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../Admin.php?tab=dashboard");
    exit;
  }

  global $conn;
  include 'connectionDB.php';

  $nome_fantasquadra = $_GET['nome_fantasquadra'];

  $sql = "UPDATE fantasquadra SET flag_attuale = NOT flag_attuale WHERE nome_fantasquadra = '$nome_fantasquadra'";
  $conn->query($sql);

  header("Location: ../Admin.php?tab=partecipanti");
  exit;
?>