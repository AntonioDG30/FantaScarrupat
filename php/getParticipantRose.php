<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accesso negato']);
    exit;
}

header('Content-Type: application/json');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $participant = $_GET['participant'] ?? '';
    if (!$participant) {
        throw new Exception('Parametro participant richiesto');
    }
    
    $stmt = $conn->prepare("
        SELECT r.anno, r.id_rosa,
               COUNT(dr.id_giocatore) as num_giocatori,
               SUM(dr.crediti_pagati) as crediti_totali
        FROM rosa r
        LEFT JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
        WHERE r.nome_fantasquadra = ?
        GROUP BY r.id_rosa
        ORDER BY r.anno DESC
    ");
    $stmt->execute([$participant]);
    $rose = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'participant' => $participant,
            'rose' => $rose
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>