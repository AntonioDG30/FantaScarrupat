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

  $database = "my_fantascarrupat";

  // Ottieni la struttura del database
  $sql_structure = "SHOW TABLES";
  $result_structure = $conn->query($sql_structure);

  // Inizializza lo script SQL con le prime tre righe
  $sql_script = "DROP SCHEMA IF EXISTS $database;\nCREATE SCHEMA $database;\nUSE $database;\n";

  // Cicla attraverso le tabelle e ottieni la struttura e i dati
  while ($row = $result_structure->fetch_assoc()) {
    $table_name = $row['Tables_in_' . $database];

    // Ottieni la struttura della tabella
    $sql_structure_table = "SHOW CREATE TABLE $table_name";
    $result_structure_table = $conn->query($sql_structure_table);
    $row_structure_table = $result_structure_table->fetch_assoc();
    $create_table_query = $row_structure_table["Create Table"];

    // Aggiungi la query di creazione tabella allo script SQL
    $sql_script .= "\n\n" . $create_table_query . ";\n\n";

    // Ottieni i dati della tabella
    $sql_data_table = "SELECT * FROM $table_name";
    $result_data_table = $conn->query($sql_data_table);

    // Cicla attraverso i risultati e aggiungi le query di inserimento allo script SQL
    while ($row_data_table = $result_data_table->fetch_assoc()) {
      $column_names = implode(", ", array_keys($row_data_table));
      $column_values = implode("', '", array_values($row_data_table));
      $sql_script .= "INSERT INTO $table_name ($column_names) VALUES ('$column_values');\n";
    }
  }

  // Chiudi la connessione al database
  $conn->close();

  // Scrivi lo script SQL su un file temporaneo
  $file = 'database_backup.sql';
  file_put_contents($file, $sql_script);

  // Imposta le intestazioni per forzare il download dello script come file
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . basename($file) . '"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($file));
  readfile($file);

  // Elimina il file temporaneo
  unlink($file);

  // Reindirizza l'utente alla pagina precedente
  header("Location: {$_SERVER['HTTP_REFERER']}");
  exit();
?>
