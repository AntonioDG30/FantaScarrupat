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

  $id_parametro = $_GET['id_parametro'];

  $sql = "UPDATE parametri_rosa SET flag_visibile = NOT flag_visibile WHERE id_parametro = '$id_parametro'";
  $conn->query($sql);

  header("Location: ../gestisciParametri.php");
  exit;
?>
