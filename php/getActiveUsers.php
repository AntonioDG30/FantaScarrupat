<?php
    session_start();

    // Controllo autenticazione e admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized', 'activeUsers' => 0]);
        exit;
    }

    // Connessione al database
    require_once 'connectionDB.php';

    date_default_timezone_set('Europe/Rome');
    $current_time_minus_one_minute = date("Y-m-d H:i:s", strtotime('-1 minute'));

    $sql = "SELECT COUNT(*) AS activeUsers FROM sessions WHERE last_activity > '$current_time_minus_one_minute'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    // Restituisci i dati in formato JSON
    header('Content-Type: application/json');
    echo json_encode(['activeUsers' => $row['activeUsers'] ?? 0]);
?>