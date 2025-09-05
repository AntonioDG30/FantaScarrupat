<?php
// ================== php/getCompetitionStats.php (#5) ==================
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
        SELECT cd.nome_competizione, cd.anno, cd.vincitore
        FROM competizione_disputata cd
        WHERE cd.id_competizione_disputata = ?
    ");
    $stmt->execute([$competitionId]);
    $competizione = $stmt->fetch();
    
    if (!$competizione) {
        throw new Exception('Competizione non trovata');
    }
    
    // Statistiche partite
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as totale_partite,
            COUNT(CASE WHEN risultato IS NOT NULL AND risultato != '' THEN 1 END) as partite_giocate,
            COUNT(CASE WHEN risultato IS NULL OR risultato = '' THEN 1 END) as partite_da_giocare
        FROM partita_avvessario
        WHERE id_competizione_disputata = ?
    ");
    $stmt->execute([$competitionId]);
    $statsPartite = $stmt->fetch();
    
    $stats = [
        'Totale partite' => $statsPartite['totale_partite'],
        'Partite giocate' => $statsPartite['partite_giocate'],
        'Partite da giocare' => $statsPartite['partite_da_giocare'],
        'Vincitore' => $competizione['vincitore'] ?: 'Non ancora determinato'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($competizione, ['stats' => $stats])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>