<?php
  global $conn;
  include 'php/connectionDB.php';

  // Ottieni l'indirizzo IP del visitatore
  $ip_address = $_SERVER['REMOTE_ADDR'];

  // Ottieni l'URL della pagina corrente
  $page_url = $_SERVER['REQUEST_URI'];

  // Ottieni l'orario corrente
  $current_time = date("Y-m-d H:i:s");

  // Verifica se esiste una sessione attiva per questo IP
  $sql = "SELECT * FROM sessions WHERE ip_address = '$ip_address'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    // Se esiste già una sessione per questo IP, aggiorna l'orario dell'ultima attività
    $sql = "UPDATE sessions SET last_activity = '$current_time' WHERE ip_address = '$ip_address'";
    $conn->query($sql);
  } else {
    // Se non esiste una sessione per questo IP, crea una nuova sessione
    $sql = "INSERT INTO sessions (ip_address, start_time, last_activity) VALUES ('$ip_address', '$current_time', '$current_time')";
    $conn->query($sql);
  }

  // Tracciamento delle visite alle pagine specifiche
  $sql = "SELECT * FROM page_views WHERE date = CURDATE() AND page_url = '$page_url'";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    // Se esiste già una visita per questa pagina oggi, aggiorna il contatore
    $sql = "UPDATE page_views SET views = views + 1 WHERE date = CURDATE() AND page_url = '$page_url'";
    $conn->query($sql);
  } else {
    // Se questa è la prima visita per questa pagina oggi, inserisci un nuovo record
    $sql = "INSERT INTO page_views (date, page_url, views) VALUES (CURDATE(), '$page_url', 1)";
    $conn->query($sql);
  }

  // Tracciamento dei visitatori unici
  $sql = "SELECT * FROM daily_views WHERE date = CURDATE() AND ip_address = '$ip_address'";
  $result = $conn->query($sql);

  if ($result->num_rows == 0) {
    // Se questo IP non ha ancora visitato oggi, registra un nuovo visitatore unico
    $sql = "INSERT INTO daily_views (date, ip_address) VALUES (CURDATE(), '$ip_address')";
    $conn->query($sql);
  }

?>
