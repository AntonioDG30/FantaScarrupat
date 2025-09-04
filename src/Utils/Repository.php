<?php
declare(strict_types=1);

/**
 * Repository per accesso unificato ai dati reali del database
 * VERSIONE CORRETTA con query semplificate e migliore gestione delle competizioni
 */
class Repository {
    private PDO $pdo;
    private bool $debug;
    
    public function __construct(PDO $pdo, bool $debug = false) {
        $this->pdo = $pdo;
        $this->debug = $debug;
        
        // Log di inizializzazione
        if ($this->debug) {
            error_log("[REPOSITORY] Inizializzato con debug attivo");
        }
    }
    
    /**
     * Log query per debug con informazioni dettagliate
     */
    private function logQuery(string $sql, array $params, float $execution_time, int $rows, string $context = ''): void {
        if (!$this->debug) return;
        
        error_log(sprintf(
            "[REPOSITORY%s] Query: %s | Params: %s | Time: %.3fs | Rows: %d",
            $context ? " - $context" : '',
            str_replace(["\n", "\t", "  "], [" ", " ", " "], trim($sql)),
            json_encode($params),
            $execution_time,
            $rows
        ));
    }
    
    /**
     * Esegue query con logging automatico e gestione errori migliorata
     */
    private function executeQuery(string $sql, array $params = [], string $context = ''): array {
        $start = microtime(true);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            $execution_time = microtime(true) - $start;
            $this->logQuery($sql, $params, $execution_time, count($result), $context);
            
            return $result;
        } catch (PDOException $e) {
            $execution_time = microtime(true) - $start;
            error_log("[REPOSITORY ERROR - $context] " . $e->getMessage() . " | SQL: " . trim($sql) . " | Params: " . json_encode($params) . " | Time: {$execution_time}s");
            throw $e;
        }
    }
    
    /**
     * Test connessione database e struttura
     */
    public function testDatabaseConnection(): array {
        $results = [
            'connection' => false,
            'tables' => [],
            'sample_data' => []
        ];
        
        try {
            // Test connessione base
            $version = $this->executeQuery("SELECT VERSION() as version", [], "connection_test");
            $results['connection'] = true;
            
            if ($this->debug) {
                error_log("[REPOSITORY] Database connesso: " . ($version[0]['version'] ?? 'unknown'));
            }
            
            // Verifica tabelle esistenti
            $tables = $this->executeQuery("SHOW TABLES", [], "tables_check");
            $results['tables'] = array_column($tables, array_values($tables[0])[0]);
            
            // Test dati campione
            if (in_array('partita_avvessario', $results['tables'])) {
                $sample = $this->executeQuery("SELECT COUNT(*) as count FROM partita_avvessario", [], "sample_data");
                $results['sample_data']['partita_avvessario'] = $sample[0]['count'] ?? 0;
            }
            
            if (in_array('competizione_disputata', $results['tables'])) {
                $sample = $this->executeQuery("SELECT COUNT(*) as count FROM competizione_disputata", [], "sample_data");
                $results['sample_data']['competizione_disputata'] = $sample[0]['count'] ?? 0;
            }
            
            if (in_array('fantasquadra', $results['tables'])) {
                $sample = $this->executeQuery("SELECT nome_fantasquadra FROM fantasquadra LIMIT 3", [], "sample_fantasquadra");
                $results['sample_data']['fantasquadre'] = array_column($sample, 'nome_fantasquadra');
            }
            
        } catch (Exception $e) {
            error_log("[REPOSITORY] Test database fallito: " . $e->getMessage());
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Statistiche Hall of Fame per una fantasquadra - VERSIONE MIGLIORATA
     */
    public function getHallOfFameStats(string $nome_fantasquadra): array {
        if ($this->debug) {
            error_log("[REPOSITORY] getHallOfFameStats chiamato per: '$nome_fantasquadra'");
        }
        
        // Prima verifica se esistono dati per questa fantasquadra
        try {
            $check_sql = "SELECT COUNT(*) as count FROM partita_avvessario WHERE nome_fantasquadra_casa = ? OR nome_fantasquadra_trasferta = ?";
            $check_result = $this->executeQuery($check_sql, [$nome_fantasquadra, $nome_fantasquadra], "fantasy_team_check");
            
            $match_count = (int)($check_result[0]['count'] ?? 0);
            
            if ($this->debug) {
                error_log("[REPOSITORY] Trovate $match_count partite per '$nome_fantasquadra'");
            }
            
            if ($match_count === 0) {
                if ($this->debug) {
                    error_log("[REPOSITORY] Nessuna partita trovata per '$nome_fantasquadra', restituisco dati vuoti");
                }
                return $this->getEmptyStats();
            }
            
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore check fantasquadra: " . $e->getMessage());
            return $this->getEmptyStats();
        }
        
        // Query semplificata per statistiche base
        try {
            $simple_sql = "
                SELECT 
                    COUNT(pa.id_partita) as total_matches,
                    SUM(CASE 
                        WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                        WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                        ELSE 0 
                    END) as total_points,
                    AVG(CASE 
                        WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                        WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                    END) as avg_points_per_match,
                    MAX(CASE 
                        WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                        WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                    END) as max_points,
                    MIN(CASE 
                        WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                        WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                    END) as min_points
                FROM partita_avvessario pa
                WHERE pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?
            ";
            
            $params = array_fill(0, 10, $nome_fantasquadra);
            $simple_result = $this->executeQuery($simple_sql, $params, "simple_stats");
            
            if (empty($simple_result) || !$simple_result[0]) {
                if ($this->debug) {
                    error_log("[REPOSITORY] Query semplice non ha restituito risultati");
                }
                return $this->getEmptyStats();
            }
            
            $base_stats = $simple_result[0];
            
            // Query separata per competizioni usando SOLO competizione_disputata
            $competitions_sql = "
                SELECT 
                    COUNT(DISTINCT cd.id_competizione_disputata) as total_competitions,
                    COUNT(CASE WHEN cd.vincitore = ? THEN 1 END) as trophies_won,
                    COUNT(DISTINCT cd.anno) as seasons_played
                FROM competizione_disputata cd
                WHERE EXISTS (
                    SELECT 1 FROM partita_avvessario pa 
                    WHERE pa.id_competizione_disputata = cd.id_competizione_disputata 
                    AND (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
                )
            ";
            
            $comp_result = $this->executeQuery($competitions_sql, [$nome_fantasquadra, $nome_fantasquadra, $nome_fantasquadra], "competitions_stats");
            $comp_stats = $comp_result[0] ?? [];
            
            // Combina i risultati
            $final_stats = [
                'total_competitions' => (int)($comp_stats['total_competitions'] ?? 0),
                'trophies_won' => (int)($comp_stats['trophies_won'] ?? 0),
                'total_points' => (float)($base_stats['total_points'] ?? 0),
                'total_matches' => (int)($base_stats['total_matches'] ?? 0),
                'avg_points_per_match' => round((float)($base_stats['avg_points_per_match'] ?? 0), 2),
                'max_points' => (float)($base_stats['max_points'] ?? 0),
                'min_points' => (float)($base_stats['min_points'] ?? 0),
                'seasons_played' => (int)($comp_stats['seasons_played'] ?? 0)
            ];
            
            if ($this->debug) {
                error_log("[REPOSITORY] Statistiche finali: " . json_encode($final_stats));
            }
            
            return $final_stats;
            
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore calcolo statistiche: " . $e->getMessage());
            return $this->getEmptyStats();
        }
    }
    
    /**
     * Restituisce statistiche vuote
     */
    private function getEmptyStats(): array {
        return [
            'total_competitions' => 0,
            'trophies_won' => 0,
            'total_points' => 0,
            'total_matches' => 0,
            'avg_points_per_match' => 0,
            'max_points' => 0,
            'min_points' => 0,
            'seasons_played' => 0
        ];
    }
    
    /**
     * Migliore stagione basata su punti reali
     */
    public function getBestSeason(string $nome_fantasquadra): ?int {
        try {
            $sql = "
                SELECT 
                    cd.anno,
                    SUM(CASE 
                        WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                        WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                        ELSE 0 
                    END) as season_points
                FROM competizione_disputata cd
                JOIN partita_avvessario pa ON cd.id_competizione_disputata = pa.id_competizione_disputata
                WHERE (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
                GROUP BY cd.anno
                ORDER BY season_points DESC
                LIMIT 1
            ";
            
            $result = $this->executeQuery($sql, [$nome_fantasquadra, $nome_fantasquadra, $nome_fantasquadra, $nome_fantasquadra], "best_season");
            
            return !empty($result) ? (int)$result[0]['anno'] : null;
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getBestSeason: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trofei reali vinti (solo vittorie effettive dalla tabella competizione_disputata)
     */
    public function getRealTrophies(string $nome_fantasquadra): array {
        try {
            // Query semplificata usando solo competizione_disputata
            $sql = "
                SELECT 
                    cd.anno,
                    cd.nome_competizione,
                    'Campionato' as tipologia,
                    SUM(CASE 
                        WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                        WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                        ELSE 0 
                    END) as total_points,
                    COUNT(pa.id_partita) as matches_played
                FROM competizione_disputata cd
                JOIN partita_avvessario pa ON cd.id_competizione_disputata = pa.id_competizione_disputata
                WHERE cd.vincitore = ? 
                AND (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
                GROUP BY cd.id_competizione_disputata, cd.anno, cd.nome_competizione
                ORDER BY cd.anno DESC, cd.nome_competizione
            ";
            
            $params = array_fill(0, 5, $nome_fantasquadra);
            return $this->executeQuery($sql, $params, "real_trophies");
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getRealTrophies: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Storico competizioni COMPLETAMENTE NUOVO - usa partita_avvessario, partita_solitaria e competizione_disputata
     */
    public function getCompetitionHistory(string $nome_fantasquadra, int $limit = 20): array {
        try {
            if ($this->debug) {
                error_log("[REPOSITORY] getCompetitionHistory NUOVO per: '$nome_fantasquadra'");
            }
            
            // Step 1: Trova tutte le competizioni disputate dall'utente
            $competitions_sql = "
                SELECT DISTINCT
                    cd.id_competizione_disputata,
                    cd.nome_competizione,
                    cd.anno,
                    cd.vincitore
                FROM competizione_disputata cd
                WHERE EXISTS (
                    SELECT 1 FROM partita_avvessario pa 
                    WHERE pa.id_competizione_disputata = cd.id_competizione_disputata 
                    AND (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
                ) OR EXISTS (
                    SELECT 1 FROM partita_solitaria ps 
                    WHERE ps.id_competizione_disputata = cd.id_competizione_disputata 
                    AND ps.nome_fantasquadra = ?
                )
                ORDER BY cd.anno DESC, cd.nome_competizione
                LIMIT ?
            ";
            
            $competitions = $this->executeQuery($competitions_sql, [
                $nome_fantasquadra, $nome_fantasquadra, $nome_fantasquadra, $limit
            ], "find_competitions");
            
            if ($this->debug) {
                error_log("[REPOSITORY] Trovate " . count($competitions) . " competizioni totali");
            }
            
            $result = [];
            
            foreach ($competitions as $comp) {
                $competition_id = $comp['id_competizione_disputata'];
                $competition_name = $comp['nome_competizione'] ?: ('Competizione ' . $comp['anno']);
                $is_winner = ($comp['vincitore'] === $nome_fantasquadra);
                
                // Step 2: Analizza le partite di questa competizione per determinare il tipo
                $matches_analysis_sql = "
                    SELECT 
                        pa.tipologia,
                        pa.giornata,
                        COUNT(*) as match_count,
                        SUM(CASE 
                            WHEN pa.nome_fantasquadra_casa = ? THEN pa.punteggio_casa 
                            WHEN pa.nome_fantasquadra_trasferta = ? THEN pa.punteggio_trasferta 
                        END) as total_points
                    FROM partita_avvessario pa
                    WHERE pa.id_competizione_disputata = ?
                    AND (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?)
                    GROUP BY pa.tipologia, pa.giornata
                    ORDER BY 
                        CASE pa.tipologia 
                            WHEN 'finale' THEN 1
                            WHEN 'semifinale' THEN 2  
                            WHEN 'quarti' THEN 3
                            WHEN 'ottavi' THEN 4
                            ELSE 5
                        END,
                        pa.giornata DESC
                ";
                
                $matches = $this->executeQuery($matches_analysis_sql, [
                    $nome_fantasquadra, $nome_fantasquadra, $competition_id, $nome_fantasquadra, $nome_fantasquadra
                ], "analyze_matches");
                
                // Step 3: Controlla anche partite solitarie
                $solo_matches_sql = "
                    SELECT 
                        COUNT(*) as solo_count,
                        SUM(ps.punteggio) as solo_points
                    FROM partita_solitaria ps
                    WHERE ps.id_competizione_disputata = ? AND ps.nome_fantasquadra = ?
                ";
                
                $solo_matches = $this->executeQuery($solo_matches_sql, [$competition_id, $nome_fantasquadra], "solo_matches");
                $solo_count = (int)($solo_matches[0]['solo_count'] ?? 0);
                $solo_points = (float)($solo_matches[0]['solo_points'] ?? 0);
                
                // Step 4: Determina il tipo di competizione e la posizione
                $competition_type = 'regular'; // regular, knockout, mixed
                $position_info = ['type' => 'unknown', 'value' => 0, 'display' => 'N/A'];
                $total_points = $solo_points;
                $total_matches = $solo_count;
                
                // Analizza le tipologie delle partite
                $has_knockout = false;
                $last_stage = '';
                
                foreach ($matches as $match_group) {
                    $tipologia = strtolower($match_group['tipologia'] ?? '');
                    $total_points += (float)($match_group['total_points'] ?? 0);
                    $total_matches += (int)($match_group['match_count'] ?? 0);
                    
                    if (in_array($tipologia, ['finale', 'semifinali', 'quarti', 'ottavi', 'fase a gironi'])) {
                        $has_knockout = true;
                        if (empty($last_stage) || $this->getStageOrder($tipologia) < $this->getStageOrder($last_stage)) {
                            $last_stage = $tipologia;
                        }
                    }
                }
                
                if ($has_knockout) {
                    // Competizione con eliminazione diretta
                    $competition_type = 'knockout';
                    
                    if ($is_winner) {
                        $position_info = [
                            'type' => 'winner',
                            'value' => 1,
                            'display' => 'Vincitore',
                            'badge' => 'winner'
                        ];
                    } else {
                        // Determinare a che fase si è fermato
                        $stage_display = $this->getStageDisplay($last_stage);
                        $position_info = [
                            'type' => 'stage',
                            'value' => $this->getStageOrder($last_stage),
                            'display' => $stage_display,
                            'badge' => null
                        ];
                    }
                } else {
                    // Competizione a classifica
                    $competition_type = 'regular';
                    
                    if ($is_winner) {
                        $position_info = [
                            'type' => 'winner',
                            'value' => 1,
                            'display' => '1° posto',
                            'badge' => 'winner'
                        ];
                    } else {

                        if($this->checkTipoLeague($competition_id)) {
                             $position = $this->calculateLeaguePositionSolo($competition_id, $nome_fantasquadra);
                        } else {
                             $position = $this->calculateLeaguePositionH2H($competition_id, $nome_fantasquadra);
                        }

                        $total_teams = $this->countTotalTeams($competition_id);
                        
                        $position_info = [
                            'type' => 'position',
                            'value' => $position,
                            'display' => $position . '° su ' . $total_teams,
                            'badge' => null
                        ];
                    }
                }
                
                $result[] = [
                    'season' => (int)$comp['anno'],
                    'competition' => $competition_name,
                    'competition_type' => $competition_type,
                    'position_info' => $position_info,
                    'points' => $total_points,
                    'matches_played' => $total_matches,
                    'is_winner' => $is_winner,
                    'status' => ($comp['anno'] >= date('Y') + 1) ? 'ongoing' : 'completed'
                ];
            }
            
            if ($this->debug) {
                error_log("[REPOSITORY] Storico competizioni preparato: " . count($result) . " entries");
                foreach ($result as $i => $comp) {
                    error_log("[REPOSITORY] {$comp['competition']} ({$comp['season']}) - {$comp['position_info']['display']} - {$comp['points']} punti");
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getCompetitionHistory NUOVO: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ordine delle fasi per competizioni knockout (più basso = più avanzato)
     */
    private function getStageOrder(string $stage): int {
        $orders = [
            'finale' => 1,
            'semifinali' => 2,
            'quarti' => 3,
            'ottavi' => 4,
            'fase a gironi' => 5
        ];
        return $orders[strtolower($stage)] ?? 10;
    }
    
    /**
     * Display friendly per le fasi
     */
    private function getStageDisplay(string $stage): string {
        $displays = [
            'finale' => 'Eliminato in Finale',
            'semifinali' => 'Eliminato in Semifinale',
            'quarti' => 'Eliminato ai Quarti',
            'ottavi' => 'Eliminato agli Ottavi',
            'fase a gironi' => 'Eliminato nella fase a gironi'
        ];
        return $displays[strtolower($stage)] ?? 'Eliminato';
    }

    
    /**
     * Ritorna TRUE se la competizione è di tipo "Battle Royale", "Formula Uno",
     * "Highlander" o "Uno vs Tutti" , altrimenti FALSE.
     */
    private function checkTipoLeague(int $competition_id): bool
    {
        try {
            $sql = "
                SELECT 1
                FROM competizione_disputata cd
                INNER JOIN competizione c
                    ON c.nome_competizione = cd.nome_competizione
                LEFT JOIN tipologia_competizione tc
                    ON tc.tipologia = c.tipologia
                WHERE cd.id_competizione_disputata = ?
                AND (
                        c.tipologia IN ('Battle Royale','Formula Uno','Highlander','Uno vs Tutti')
                    OR tc.tipologia IN ('Battle Royale','Formula Uno','Highlander','Uno vs Tutti')
                )
                AND (
                        EXISTS (
                            SELECT 1
                            FROM partita_avvessario pa
                            WHERE pa.id_competizione_disputata = cd.id_competizione_disputata
                        )
                        OR EXISTS (
                            SELECT 1
                            FROM partita_solitaria ps
                            WHERE ps.id_competizione_disputata = cd.id_competizione_disputata
                        )
                )
                LIMIT 1
            ";

            $rows = $this->executeQuery($sql, [$competition_id], "check_tipo_league");
            return !empty($rows);
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore checkTipoLeague: " . $e->getMessage());
            return false;
        }
    }



    /**
     * Calcola posizione in classifica per competizioni testa a testa
     */
    private function calculateLeaguePositionH2H(int $competition_id, string $nome_fantasquadra): int {
        try {
            // 3-1-0 su risultati (gol_casa/gol_trasferta) + tie-break: diff reti, fantapunti
            $ranking_sql = "
                SELECT team_name, points, goal_diff, total_fantapunti,
                    ROW_NUMBER() OVER (
                        ORDER BY points DESC, goal_diff DESC, total_fantapunti DESC, team_name ASC
                    ) AS position
                FROM (
                    SELECT team_name,
                        SUM(pts) AS points,
                        SUM(gd)  AS goal_diff,
                        SUM(fantapunti) AS total_fantapunti
                    FROM (
                        SELECT 
                            pa.nome_fantasquadra_casa AS team_name,
                            CASE 
                                WHEN pa.gol_casa > pa.gol_trasferta THEN 3
                                WHEN pa.gol_casa = pa.gol_trasferta THEN 1
                                ELSE 0
                            END AS pts,
                            (pa.gol_casa - pa.gol_trasferta) AS gd,
                            pa.punteggio_casa AS fantapunti
                        FROM partita_avvessario pa
                        WHERE pa.id_competizione_disputata = ?

                        UNION ALL

                        SELECT 
                            pa.nome_fantasquadra_trasferta AS team_name,
                            CASE 
                                WHEN pa.gol_trasferta > pa.gol_casa THEN 3
                                WHEN pa.gol_trasferta = pa.gol_casa THEN 1
                                ELSE 0
                            END AS pts,
                            (pa.gol_trasferta - pa.gol_casa) AS gd,
                            pa.punteggio_trasferta AS fantapunti
                        FROM partita_avvessario pa
                        WHERE pa.id_competizione_disputata = ?
                    ) AS x
                    GROUP BY team_name
                ) AS agg
                ORDER BY points DESC, goal_diff DESC, total_fantapunti DESC, team_name ASC
            ";

            $rows = $this->executeQuery($ranking_sql, [$competition_id, $competition_id], "league_ranking_h2h");

            foreach ($rows as $r) {
                if ($r['team_name'] === $nome_fantasquadra) {
                    return (int)$r['position'];
                }
            }

            return 1; // fallback se non trovata
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore calculateLeaguePositionH2H: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Calcola posizione in classifica per competizioni solitarie
     */
    private function calculateLeaguePositionSolo(int $competition_id, string $nome_fantasquadra): int {
        try {
            // Classifica per fantapunti totali (tie-break: partite giocate, poi nome squadra)
            $ranking_sql = "
                SELECT team_name, total_fantapunti, matches_played,
                    ROW_NUMBER() OVER (
                        ORDER BY total_fantapunti DESC, matches_played DESC, team_name ASC
                    ) AS position
                FROM (
                    SELECT 
                        ps.nome_fantasquadra AS team_name,
                        SUM(ps.punteggio)    AS total_fantapunti,
                        COUNT(*)             AS matches_played
                    FROM partita_solitaria ps
                    WHERE ps.id_competizione_disputata = ?
                    GROUP BY ps.nome_fantasquadra
                ) AS agg
                ORDER BY total_fantapunti DESC, matches_played DESC, team_name ASC
            ";

            $rows = $this->executeQuery($ranking_sql, [$competition_id], "league_ranking_solo");

            foreach ($rows as $r) {
                if ($r['team_name'] === $nome_fantasquadra) {
                    return (int)$r['position'];
                }
            }

            return 1; // fallback se non trovata
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore calculateLeaguePositionSolo: " . $e->getMessage());
            return 1;
        }
    }


    
    /**
     * Calcola posizione in classifica per competizioni regular
     */
    private function calculateLeaguePosition(int $competition_id, string $nome_fantasquadra): int {
        try {
            // Query per classifica generale
            $ranking_sql = "
                SELECT team_name, team_points,
                       ROW_NUMBER() OVER (ORDER BY team_points DESC) as position
                FROM (
                    SELECT pa.nome_fantasquadra_casa as team_name,
                           SUM(pa.punteggio_casa) as team_points
                    FROM partita_avvessario pa
                    WHERE pa.id_competizione_disputata = ?
                    GROUP BY pa.nome_fantasquadra_casa
                    
                    UNION ALL
                    
                    SELECT pa.nome_fantasquadra_trasferta as team_name,
                           SUM(pa.punteggio_trasferta) as team_points
                    FROM partita_avvessario pa
                    WHERE pa.id_competizione_disputata = ?
                    GROUP BY pa.nome_fantasquadra_trasferta
                    
                    UNION ALL
                    
                    SELECT ps.nome_fantasquadra as team_name,
                           SUM(ps.punteggio) as team_points
                    FROM partita_solitaria ps
                    WHERE ps.id_competizione_disputata = ?
                    GROUP BY ps.nome_fantasquadra
                ) all_teams
                GROUP BY team_name
                ORDER BY SUM(team_points) DESC
            ";
            
            $ranking = $this->executeQuery($ranking_sql, [$competition_id, $competition_id, $competition_id], "league_ranking");
            
            foreach ($ranking as $index => $team) {
                if ($team['team_name'] === $nome_fantasquadra) {
                    return $index + 1;
                }
            }
            
            return 1; // Fallback
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore calculateLeaguePosition: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Conta squadre totali nella competizione
     */
    private function countTotalTeams(int $competition_id): int {
        try {
            $count_sql = "
                SELECT COUNT(DISTINCT team_name) as total
                FROM (
                    SELECT nome_fantasquadra_casa as team_name FROM partita_avvessario WHERE id_competizione_disputata = ?
                    UNION
                    SELECT nome_fantasquadra_trasferta as team_name FROM partita_avvessario WHERE id_competizione_disputata = ?
                    UNION
                    SELECT nome_fantasquadra as team_name FROM partita_solitaria WHERE id_competizione_disputata = ?
                ) all_teams
                WHERE team_name IS NOT NULL AND team_name != ''
            ";
            
            $result = $this->executeQuery($count_sql, [$competition_id, $competition_id, $competition_id], "count_teams");
            return max(2, (int)($result[0]['total'] ?? 2));
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore countTotalTeams: " . $e->getMessage());
            return 2;
        }
    }
    
    /**
     * Statistiche utente reali dal database
     */
    public function getUserStats(int $user_id): array {
        try {
            // Statistiche base utente
            $user_sql = "
                SELECT 
                    username, 
                    nome_fantasquadra, 
                    created_at, 
                    last_activity,
                    total_searches,
                    COALESCE(DATEDIFF(NOW(), created_at), 0) as days_registered,
                    COALESCE(DATEDIFF(IFNULL(last_activity, NOW()), created_at), 0) as active_days
                FROM users 
                WHERE id_user = ?
            ";
            
            $user_data = $this->executeQuery($user_sql, [$user_id], "user_stats")[0] ?? null;
            
            if (!$user_data) {
                if ($this->debug) {
                    error_log("[REPOSITORY] Utente $user_id non trovato");
                }
                return $this->getEmptyUserStats();
            }
            
            if ($this->debug) {
                error_log("[REPOSITORY] Dati utente trovati: " . json_encode($user_data));
            }
            
            $base_stats = [
                'total_searches' => (int)($user_data['total_searches'] ?? 0),
                'days_registered' => (int)($user_data['days_registered'] ?? 0),
                'active_days' => (int)($user_data['active_days'] ?? 0),
                'favorite_competition_type' => 'N/A',
                'most_played_season' => 'N/A',
                'total_matches_played' => 0
            ];
            
            // Solo se ha una fantasquadra, calcola statistiche aggiuntive
            if (!empty($user_data['nome_fantasquadra'])) {
                $nome_fantasquadra = $user_data['nome_fantasquadra'];
                
                // Partite totali
                $base_stats['total_matches_played'] = $this->getTotalMatchesForUser($nome_fantasquadra);
                
                // Stagione più attiva
                try {
                    $season_sql = "
                        SELECT cd.anno, COUNT(*) as matches
                        FROM competizione_disputata cd
                        JOIN partita_avvessario pa ON cd.id_competizione_disputata = pa.id_competizione_disputata
                        WHERE pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?
                        GROUP BY cd.anno
                        ORDER BY matches DESC
                        LIMIT 1
                    ";
                    
                    $season_result = $this->executeQuery($season_sql, [$nome_fantasquadra, $nome_fantasquadra], "most_played_season");
                    if (!empty($season_result)) {
                        $base_stats['most_played_season'] = $season_result[0]['anno'];
                    }
                } catch (Exception $e) {
                    if ($this->debug) {
                        error_log("[REPOSITORY] Errore stagione più attiva: " . $e->getMessage());
                    }
                }
                
                // Competizione preferita (semplificata)
                $base_stats['favorite_competition_type'] = 'Campionato'; // Default
            }
            
            if ($this->debug) {
                error_log("[REPOSITORY] Statistiche utente finali: " . json_encode($base_stats));
            }
            
            return $base_stats;
            
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getUserStats: " . $e->getMessage());
            return $this->getEmptyUserStats();
        }
    }
    
    /**
     * Restituisce statistiche utente vuote
     */
    private function getEmptyUserStats(): array {
        return [
            'total_searches' => 0,
            'days_registered' => 0,
            'active_days' => 0,
            'favorite_competition_type' => 'N/A',
            'most_played_season' => 'N/A',
            'total_matches_played' => 0
        ];
    }
    
    /**
     * Conteggio totale partite per utente
     */
    private function getTotalMatchesForUser(string $nome_fantasquadra): int {
        try {
            $sql = "
                SELECT COUNT(*) as total
                FROM partita_avvessario
                WHERE nome_fantasquadra_casa = ? OR nome_fantasquadra_trasferta = ?
            ";
            
            $result = $this->executeQuery($sql, [$nome_fantasquadra, $nome_fantasquadra], "total_matches");
            return (int)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getTotalMatchesForUser: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Conteggio totale giocatori nel database
     */
    public function getTotalPlayers(): int {
        try {
            $result = $this->executeQuery("SELECT COUNT(*) as total FROM giocatore", [], "total_players");
            return (int)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getTotalPlayers: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Ultima attività export da user_activities (se tabella esiste)
     */
    public function getLastExportActivity(int $user_id): ?string {
        try {
            // Verifica se tabella esiste
            $check_sql = "SHOW TABLES LIKE 'user_activities'";
            $table_exists = $this->executeQuery($check_sql, [], "check_user_activities");
            
            if (empty($table_exists)) {
                if ($this->debug) {
                    error_log("[REPOSITORY] Tabella user_activities non esiste");
                }
                return null;
            }
            
            $sql = "
                SELECT created_at 
                FROM user_activities 
                WHERE id_user = ? AND activity_type LIKE '%export%' 
                ORDER BY created_at DESC 
                LIMIT 1
            ";
            
            $result = $this->executeQuery($sql, [$user_id], "last_export_activity");
            return $result[0]['created_at'] ?? null;
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getLastExportActivity: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Statistiche vittorie/sconfitte per fantasquadra
     */
    public function getWinLossStats(string $nome_fantasquadra): array {
        try {
            $sql = "
                SELECT 
                    SUM(CASE 
                        WHEN (pa.nome_fantasquadra_casa = ? AND pa.punteggio_casa > pa.punteggio_trasferta) OR
                             (pa.nome_fantasquadra_trasferta = ? AND pa.punteggio_trasferta > pa.punteggio_casa) 
                        THEN 1 ELSE 0 
                    END) as wins,
                    SUM(CASE 
                        WHEN (pa.nome_fantasquadra_casa = ? AND pa.punteggio_casa < pa.punteggio_trasferta) OR
                             (pa.nome_fantasquadra_trasferta = ? AND pa.punteggio_trasferta < pa.punteggio_casa) 
                        THEN 1 ELSE 0 
                    END) as losses,
                    SUM(CASE 
                        WHEN (pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?) AND 
                             pa.punteggio_casa = pa.punteggio_trasferta 
                        THEN 1 ELSE 0 
                    END) as draws
                FROM partita_avvessario pa
                WHERE pa.nome_fantasquadra_casa = ? OR pa.nome_fantasquadra_trasferta = ?
            ";
            
            $params = array_fill(0, 8, $nome_fantasquadra);
            $result = $this->executeQuery($sql, $params, "win_loss_stats");
            
            return $result[0] ?? ['wins' => 0, 'losses' => 0, 'draws' => 0];
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getWinLossStats: " . $e->getMessage());
            return ['wins' => 0, 'losses' => 0, 'draws' => 0];
        }
    }
    
    /**
     * Lista tutte le fantasquadre disponibili per debug
     */
    public function getAllFantasyTeams(): array {
        try {
            $sql = "
                SELECT DISTINCT nome_fantasquadra_casa as nome
                FROM partita_avvessario 
                WHERE nome_fantasquadra_casa IS NOT NULL AND nome_fantasquadra_casa != ''
                UNION
                SELECT DISTINCT nome_fantasquadra_trasferta as nome
                FROM partita_avvessario 
                WHERE nome_fantasquadra_trasferta IS NOT NULL AND nome_fantasquadra_trasferta != ''
                ORDER BY nome
            ";
            
            $result = $this->executeQuery($sql, [], "all_fantasy_teams");
            return array_column($result, 'nome');
        } catch (Exception $e) {
            error_log("[REPOSITORY] Errore getAllFantasyTeams: " . $e->getMessage());
            return [];
        }
    }
}