<?php
// ================== php/getCompetitionStats.php - CORRECTED ==================
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
    
    // Statistiche partite - CORRECTED: usa i nomi colonne giusti
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as totale_partite,
            COUNT(CASE WHEN gol_casa IS NOT NULL AND gol_trasferta IS NOT NULL THEN 1 END) as partite_giocate,
            COUNT(CASE WHEN gol_casa IS NULL OR gol_trasferta IS NULL THEN 1 END) as partite_da_giocare,
            AVG(punteggio_casa + punteggio_trasferta) as media_punteggi,
            MAX(punteggio_casa) as punteggio_max_casa,
            MAX(punteggio_trasferta) as punteggio_max_trasferta,
            COUNT(DISTINCT nome_fantasquadra_casa) + COUNT(DISTINCT nome_fantasquadra_trasferta) as squadre_partecipanti
        FROM partita_avvessario
        WHERE id_competizione_disputata = ?
    ");
    $stmt->execute([$competitionId]);
    $statsPartite = $stmt->fetch();
    
    // Statistiche aggiuntive
    $stmt = $conn->prepare("
        SELECT 
            nome_fantasquadra_casa as squadra,
            COUNT(*) as partite_casa,
            SUM(CASE WHEN gol_casa > gol_trasferta THEN 1 ELSE 0 END) as vittorie_casa,
            AVG(punteggio_casa) as media_punteggio_casa
        FROM partita_avvessario
        WHERE id_competizione_disputata = ?
        GROUP BY nome_fantasquadra_casa
        UNION ALL
        SELECT 
            nome_fantasquadra_trasferta as squadra,
            COUNT(*) as partite_trasferta,
            SUM(CASE WHEN gol_trasferta > gol_casa THEN 1 ELSE 0 END) as vittorie_trasferta,
            AVG(punteggio_trasferta) as media_punteggio_trasferta
        FROM partita_avvessario
        WHERE id_competizione_disputata = ?
        GROUP BY nome_fantasquadra_trasferta
        ORDER BY squadra
    ");
    $stmt->execute([$competitionId, $competitionId]);
    $squadreStats = $stmt->fetchAll();
    
    $stats = [
        'Totale partite' => $statsPartite['totale_partite'] ?? 0,
        'Partite giocate' => $statsPartite['partite_giocate'] ?? 0,
        'Partite da giocare' => $statsPartite['partite_da_giocare'] ?? 0,
        'Media punteggi per partita' => round($statsPartite['media_punteggi'] ?? 0, 2),
        'Punteggio massimo casa' => round($statsPartite['punteggio_max_casa'] ?? 0, 2),
        'Punteggio massimo trasferta' => round($statsPartite['punteggio_max_trasferta'] ?? 0, 2),
        'Squadre partecipanti' => count(array_unique(array_column($squadreStats, 'squadra'))),
        'Vincitore' => $competizione['vincitore'] ?: 'Non ancora determinato'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => array_merge($competizione, [
            'stats' => $stats,
            'squadre_dettagli' => $squadreStats
        ])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>