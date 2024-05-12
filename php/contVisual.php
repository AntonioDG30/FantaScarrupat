<?php
global $conn;
include 'php/connectionDB.php';

// Ottieni l'indirizzo IP del visitatore
$ip_address = $_SERVER['REMOTE_ADDR'];

// Verifica il consenso degli utenti
$consent_given = isset($_COOKIE['consent_cookie']) && $_COOKIE['consent_cookie'] === 'true';

if ($consent_given) {
  // Ottieni l'URL della pagina corrente
  $page_url = $_SERVER['REQUEST_URI'];

  // Ottieni l'orario corrente
  date_default_timezone_set('Europe/Rome');
  $current_time = date("Y-m-d H:i:s");

  // Anonimizza l'indirizzo IP
  $anon_ip_address = anonymize_ip($ip_address);

  // Tempo massimo di inattività (in secondi)
  $max_inactive_time = 1800; // 30 minuti

// Verifica se esiste una sessione attiva per questo IP
  $sql = "SELECT * FROM sessions WHERE ip_address = '$anon_ip_address' ORDER BY last_activity DESC LIMIT 1";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $session_row = $result->fetch_assoc();
    $id_sessions = $session_row['id_sessions'];
    $last_activity_time = strtotime($session_row['last_activity']);

    // Verifica se la sessione è stata inattiva per più del tempo massimo consentito
    if (time() - $last_activity_time > $max_inactive_time) {
      // Crea una nuova sessione
      $sql = "INSERT INTO sessions (ip_address, start_time, last_activity) VALUES ('$anon_ip_address', '$current_time', '$current_time')";
      $conn->query($sql);
    } else {
      // Aggiorna l'orario dell'ultima attività della sessione attuale
      $sql = "UPDATE sessions SET last_activity = '$current_time' WHERE ip_address = '$anon_ip_address' AND id_sessions = '$id_sessions'";
      $conn->query($sql);
    }
  } else {
    // Se non esiste una sessione per questo IP, crea una nuova sessione
    $sql = "INSERT INTO sessions (ip_address, start_time, last_activity) VALUES ('$anon_ip_address', '$current_time', '$current_time')";
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
} else {
  // Se il consenso non è stato dato, non tracciare le attività dell'utente
}

// Funzione per anonimizzare l'indirizzo IP
function anonymize_ip($ip) {
  // Applica l'hash crittografico SHA-256 all'indirizzo IP
  $hashed_ip = hash('sha256', $ip);
  return $hashed_ip;
}
?>
