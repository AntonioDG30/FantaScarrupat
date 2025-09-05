<?php
// ================== php/getCompetitionMatches.php - CORRECTED ==================
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
    
    $competitionId = $_GET['id'] ?? '';
    if (!$competitionId || !is_numeric($competitionId)) {
        throw new Exception('ID competizione non valido');
    }
    
    // Info competizione
    $stmt = $conn->prepare("
        SELECT cd.nome_competizione, cd.anno
        FROM competizione_disputata cd
        WHERE cd.id_competizione_disputata = ?
    ");
    $stmt->execute([$competitionId]);
    $competizione = $stmt->fetch();
    
    if (!$competizione) {
        throw new Exception('Competizione non trovata');
    }
    
    // Partite della competizione - NOMI COLONNE CORRETTI
    $stmt = $conn->prepare("
        SELECT 
            pa.nome_fantasquadra_casa as squadra_casa,
            pa.nome_fantasquadra_trasferta as squadra_ospite, 
            pa.giornata,
            pa.tipologia,
            pa.girone,
            pa.gol_casa,
            pa.gol_trasferta,
            pa.punteggio_casa,
            pa.punteggio_trasferta,
            CONCAT(pa.gol_casa, ' - ', pa.gol_trasferta) as risultato
        FROM partita_avvessario pa
        WHERE pa.id_competizione_disputata = ?
        ORDER BY pa.giornata ASC, pa.tipologia ASC
    ");
    $stmt->execute([$competitionId]);
    $partite = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($competizione, ['partite' => $partite])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>