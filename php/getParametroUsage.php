<?php
// ================== php/getParametroUsage.php (#3) ==================
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
    
    $parametroId = $_GET['id'] ?? '';
    if (!$parametroId || !is_numeric($parametroId)) {
        throw new Exception('ID parametro non valido');
    }
    
    // Info parametro
    $stmt = $conn->prepare("
        SELECT numero_parametro, testo_parametro 
        FROM parametri_rosa 
        WHERE id_parametro = ?
    ");
    $stmt->execute([$parametroId]);
    $parametro = $stmt->fetch();
    
    if (!$parametro) {
        throw new Exception('Parametro non trovato');
    }
    
    // Rose che utilizzano il parametro
    $stmt = $conn->prepare("
        SELECT r.nome_fantasquadra, r.anno,
               COUNT(dr.id_giocatore) as num_giocatori,
               SUM(dr.crediti_pagati) as crediti_totali
        FROM parametri_utilizzati pu
        JOIN rosa r ON pu.id_rosa = r.id_rosa
        LEFT JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
        WHERE pu.id_parametro = ?
        GROUP BY r.id_rosa
        ORDER BY r.anno DESC, r.nome_fantasquadra
    ");
    $stmt->execute([$parametroId]);
    $rose = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($parametro, ['rose' => $rose])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>