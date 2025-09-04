<?php
  declare(strict_types=1);

  // Protezione autenticazione e accesso admin
  require_once __DIR__ . '/../auth/require_login.php';
  require_once __DIR__ . '/../config/config.php';
  require_once __DIR__ . '/../config/find_userData.php';

  // Solo admin può accedere
  if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
      header('Location: ' . url('index.php'));
      exit;
  }

  // Rigenera CSRF token se non esiste
  if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }

  global $conn;
  include 'connectionDB.php';

  $nome_fantasquadra = $_GET['nome_fantasquadra'];

  $sql = "UPDATE fantasquadra SET flag_attuale = NOT flag_attuale WHERE nome_fantasquadra = '$nome_fantasquadra'";
  $conn->query($sql);

  header("Location: ../gestisciPartecipanti.php");
  exit;
?>
