<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
}
// Connessione al database e query per ottenere il numero di utenti attivi
global $conn;
include 'connectionDB.php';

date_default_timezone_set('Europe/Rome');
$current_time_minus_one_minute = date("Y-m-d H:i:s", strtotime('-1 minute'));
$sql = "SELECT COUNT(*) AS activeUsers FROM sessions WHERE last_activity > '$current_time_minus_one_minute'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// Restituisci i dati in formato JSON
echo json_encode(['activeUsers' => $row['activeUsers']]);
?>

