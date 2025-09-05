<?php
// ================== php/exportRosa.php (#2) ==================
declare(strict_types=1);
require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../config/config.php';

try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $rosaId = $_GET['id'] ?? '';
    $format = $_GET['format'] ?? 'csv';
    
    if (!$rosaId || !is_numeric($rosaId)) {
        throw new Exception('ID rosa non valido');
    }
    
    // Dati rosa
    $stmt = $conn->prepare("
        SELECT r.nome_fantasquadra, r.anno,
               g.nome_giocatore, g.ruolo, g.squadra_reale, dr.crediti_pagati
        FROM rosa r
        JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
        JOIN giocatore g ON dr.id_giocatore = g.id_giocatore
        WHERE r.id_rosa = ?
        ORDER BY g.ruolo, dr.crediti_pagati DESC
    ");
    $stmt->execute([$rosaId]);
    $data = $stmt->fetchAll();
    
    if (empty($data)) {
        throw new Exception('Rosa non trovata o vuota');
    }
    
    $filename = "rosa_{$data[0]['nome_fantasquadra']}_{$data[0]['anno']}.csv";
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header CSV
    fputcsv($output, ['Giocatore', 'Ruolo', 'Squadra', 'Crediti']);
    
    // Dati
    foreach ($data as $row) {
        fputcsv($output, [
            $row['nome_giocatore'],
            $row['ruolo'],
            $row['squadra_reale'],
            $row['crediti_pagati']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>