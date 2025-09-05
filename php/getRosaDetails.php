<?php
// ================== php/getRosaDetails.php (#2) ==================
declare(strict_types=1);
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $rosaId = $_GET['id'] ?? '';
    if (!$rosaId || !is_numeric($rosaId)) {
        throw new Exception('ID rosa non valido');
    }
    
    // Informazioni rosa
    $stmt = $conn->prepare("
        SELECT r.nome_fantasquadra, r.anno,
               SUM(dr.crediti_pagati) as crediti_totali
        FROM rosa r
        LEFT JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
        WHERE r.id_rosa = ?
        GROUP BY r.id_rosa
    ");
    $stmt->execute([$rosaId]);
    $rosaInfo = $stmt->fetch();
    
    if (!$rosaInfo) {
        throw new Exception('Rosa non trovata');
    }
    
    // Giocatori della rosa
    $stmt = $conn->prepare("
        SELECT g.nome_giocatore, g.ruolo, g.squadra_reale, dr.crediti_pagati
        FROM dettagli_rosa dr
        JOIN giocatore g ON dr.id_giocatore = g.id_giocatore
        WHERE dr.id_rosa = ?
        ORDER BY g.ruolo, dr.crediti_pagati DESC
    ");
    $stmt->execute([$rosaId]);
    $giocatori = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($rosaInfo, ['giocatori' => $giocatori])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>