<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Core;

use FantacalcioAnalyzer\Utils\Logger;
use FantacalcioAnalyzer\Utils\NormalizerTrait;

/**
 * Fallback Wikipedia scraper per dati Serie A
 */
class SerieAWikiFetcher
{
    use NormalizerTrait;
    
    private const BASE_EN = "https://en.wikipedia.org/wiki/";
    
    private Logger $logger;
    
    public function __construct()
    {
        $this->logger = new Logger("SerieAWikiFetcher");
    }
    
    /**
     * Scarica una pagina Wikipedia e parsea le tabelle HTML
     */
    private function readHtmlTables(string $url): array
    {
        $this->logger->info("[Wiki] Fetching: $url");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTPHEADER => [
                "User-Agent: Mozilla/5.0 (compatible; FantacalcioAnalyzer/1.0; +http://example.com/bot)"
            ]
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP $httpCode");
        }
        
        return $this->parseHtmlTables($html);
    }
    
    /**
     * Parsea le tabelle HTML dalla pagina
     */
    private function parseHtmlTables(string $html): array
    {
        $tables = [];
        
        // Usa DOMDocument per parsing robusto
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        
        $tableDoms = $xpath->query('//table');
        foreach ($tableDoms as $table) {
            $rows = [];
            $trs = $xpath->query('.//tr', $table);
            
            foreach ($trs as $tr) {
                $row = [];
                $cells = $xpath->query('.//td|.//th', $tr);
                foreach ($cells as $cell) {
                    $text = trim($cell->textContent);
                    // Pulisce note Wikipedia [1], [2], etc.
                    $text = preg_replace('/\[\d+\]/', '', $text);
                    $text = preg_replace('/\s+/', ' ', trim($text));
                    $row[] = $text;
                }
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
            
            if (!empty($rows)) {
                $tables[] = $rows;
            }
        }
        
        $this->logger->info("[Wiki] Found " . count($tables) . " tables");
        return $tables;
    }
    
    /**
     * Ottiene la lista delle squadre partecipanti per una stagione
     */
    public function getCurrentTeams(string $season): array
    {
        $seasonWiki = $this->seasonStrToWiki($season);
        $url = self::BASE_EN . "{$seasonWiki}_Serie_A";
        
        $this->logger->info("[Wiki] Fetching teams for season $season -> $seasonWiki");
        
        try {
            $tables = $this->readHtmlTables($url);
        } catch (\Exception $e) {
            $this->logger->error("[Wiki] Error fetching teams page: " . $e->getMessage());
            return [];
        }
        
        // Cerca la tabella con le squadre (ha colonne Team, Stadium/Ground/Location)
        foreach ($tables as $table) {
            if (empty($table)) continue;
            
            $header = array_map('strtolower', array_map('trim', $table[0]));
            
            // Deve avere almeno 'team' e una colonna relativa allo stadio
            $hasTeam = in_array('team', $header) || in_array('club', $header);
            $hasStadium = in_array('stadium', $header) || in_array('ground', $header) || in_array('location', $header);
            
            if ($hasTeam && $hasStadium) {
                $teamCol = array_search('team', $header);
                if ($teamCol === false) {
                    $teamCol = array_search('club', $header);
                }
                
                if ($teamCol === false) continue;
                
                $teams = [];
                for ($i = 1; $i < count($table); $i++) {
                    if (!isset($table[$i][$teamCol])) continue;
                    
                    $team = $table[$i][$teamCol];
                    $team = preg_replace('/\[.*?\]/', '', $team); // Rimuove note
                    $team = preg_replace('/\(.*?\)/', '', $team); // Rimuove parentesi
                    $team = trim($team);
                    
                    if (strlen($team) > 1 && !in_array($team, $teams)) {
                        $teams[] = $team;
                    }
                }
                
                if (count($teams) >= 18) { // Serie A dovrebbe avere 20 squadre, minimo 18
                    $this->logger->info("[Wiki] Found teams table with " . count($teams) . " teams");
                    $this->logger->info("[Wiki] Teams: " . implode(', ', $teams));
                    return $teams;
                }
            }
        }
        
        // Fallback: prova a estrarre dalla classifica
        $this->logger->info("[Wiki] Teams table not found, trying league table fallback");
        $leagueTable = $this->getLeagueTable($season);
        if (!empty($leagueTable)) {
            $teams = array_column($leagueTable, 'Team');
            $this->logger->info("[Wiki] Extracted from league table: " . implode(', ', $teams));
            return $teams;
        }
        
        $this->logger->warning("[Wiki] No teams found for season $season");
        return [];
    }
    
    /**
     * Ottiene la classifica finale di una stagione
     */
    public function getLeagueTable(string $season): array
    {
        $seasonWiki = $this->seasonStrToWiki($season);
        $url = self::BASE_EN . "{$seasonWiki}_Serie_A";
        
        $this->logger->info("[Wiki] Fetching league table for season $season");
        
        try {
            $tables = $this->readHtmlTables($url);
        } catch (\Exception $e) {
            $this->logger->error("[Wiki] Error fetching league table: " . $e->getMessage());
            return [];
        }
        
        // Cerca la tabella della classifica finale
        $candidates = [];
        foreach ($tables as $table) {
            if (empty($table)) continue;
            
            $header = array_map('strtolower', array_map('trim', $table[0]));
            
            // La classifica deve avere posizione e squadra
            $hasPos = false;
            $hasTeam = false;
            $hasGames = false; // Indicator of league table
            
            foreach ($header as $col) {
                if (in_array($col, ['pos', 'position', '#'])) $hasPos = true;
                if (in_array($col, ['team', 'club'])) $hasTeam = true;
                if (in_array($col, ['pld', 'played', 'games', 'mp', 'matches played'])) $hasGames = true;
            }
            
            if ($hasPos && $hasTeam && $hasGames) {
                $candidates[] = $table;
            }
        }
        
        if (empty($candidates)) {
            $this->logger->warning("[Wiki] No league table candidates found");
            return [];
        }
        
        // Processo ogni candidato
        foreach ($candidates as $table) {
            $normalized = $this->normalizeLeagueTable($table);
            if ($normalized !== null && count($normalized) >= 18) {
                $this->logger->info("[Wiki] League table found with " . count($normalized) . " teams");
                foreach ($normalized as $row) {
                    $ga = isset($row['GA']) ? " (GA: {$row['GA']})" : "";
                    $this->logger->info("[Wiki] {$row['Pos']}. {$row['Team']}{$ga}");
                }
                return $normalized;
            }
        }
        
        $this->logger->warning("[Wiki] No valid league table found for season $season");
        return [];
    }
    
    /**
     * Normalizza una tabella classifica in formato standard
     */
    private function normalizeLeagueTable(array $table): ?array
    {
        if (empty($table) || count($table) < 2) {
            return null;
        }
        
        $header = array_map('strtolower', array_map('trim', $table[0]));
        $mapping = [];
        
        // Mappa le colonne
        foreach ($header as $idx => $col) {
            if (in_array($col, ['pos', 'position', '#'])) {
                $mapping['Pos'] = $idx;
            } elseif (in_array($col, ['team', 'club'])) {
                $mapping['Team'] = $idx;
            } elseif (in_array($col, ['ga', 'gf', 'goals against', 'against'])) {
                $mapping['GA'] = $idx;
            }
        }
        
        if (!isset($mapping['Pos']) || !isset($mapping['Team'])) {
            return null;
        }
        
        $result = [];
        for ($i = 1; $i < count($table); $i++) {
            $row = [];
            
            // Posizione
            if (!isset($table[$i][$mapping['Pos']])) continue;
            $posStr = trim($table[$i][$mapping['Pos']]);
            $pos = (int)preg_replace('/\D/', '', $posStr); // Estrae solo numeri
            if ($pos <= 0 || $pos > 20) continue; // Posizione valida Serie A
            
            $row['Pos'] = $pos;
            
            // Squadra
            if (!isset($table[$i][$mapping['Team']])) continue;
            $team = $table[$i][$mapping['Team']];
            $team = preg_replace('/\[.*?\]|\(.*?\)/', '', $team); // Pulisce note e parentesi
            $team = trim($team);
            if (empty($team)) continue;
            
            $row['Team'] = $team;
            
            // Gol Against (opzionale)
            if (isset($mapping['GA']) && isset($table[$i][$mapping['GA']])) {
                $gaStr = trim($table[$i][$mapping['GA']]);
                $ga = (int)preg_replace('/\D/', '', $gaStr);
                if ($ga > 0) {
                    $row['GA'] = $ga;
                }
            }
            
            $result[] = $row;
        }
        
        // Ordina per posizione
        usort($result, fn($a, $b) => $a['Pos'] <=> $b['Pos']);
        
        return $result;
    }
    
    /**
     * Calcola le liste dinamiche usando Wikipedia come fallback
     */
    public function computeDynamicLists(string $currentSeason, string $lastSeason): array
    {
        $this->logger->info("[Wiki] Computing dynamic lists for $currentSeason vs $lastSeason");
        
        // Ottiene squadre per entrambe le stagioni
        $currentTeams = $this->getCurrentTeams($currentSeason);
        $lastTeams = $this->getCurrentTeams($lastSeason);
        
        $neopromosse = [];
        if (!empty($currentTeams) && !empty($lastTeams)) {
            $lastTeamsSet = array_flip($lastTeams);
            foreach ($currentTeams as $team) {
                if (!isset($lastTeamsSet[$team])) {
                    $neopromosse[] = $team;
                }
            }
        }
        
        $this->logger->info("[Wiki] Neopromosse: " . implode(', ', $neopromosse));
        
        // Ottiene classifica finale stagione precedente
        $leagueTable = $this->getLeagueTable($lastSeason);
        
        $squadreMedia = [];
        $squadre50GA = [];
        
        if (!empty($leagueTable)) {
            foreach ($leagueTable as $row) {
                // Squadre posizioni 10-17
                if ($row['Pos'] >= 10 && $row['Pos'] <= 17) {
                    $squadreMedia[] = $row['Team'];
                }
                
                // Squadre con 50+ gol subiti
                if (isset($row['GA']) && $row['GA'] >= 50) {
                    $squadre50GA[] = $row['Team'];
                }
            }
        }
        
        $this->logger->info("[Wiki] Squadre media (10-17): " . implode(', ', $squadreMedia));
        $this->logger->info("[Wiki] Squadre 50+ GA: " . implode(', ', $squadre50GA));
        
        return [$neopromosse, $squadreMedia, $squadre50GA];
    }
    
    /**
     * Test di connessione Wikipedia
     */
    public function testConnection(): bool
    {
        try {
            $url = self::BASE_EN . "2024–25_Serie_A";
            $this->logger->info("[Wiki] Testing connection to $url");
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_NOBODY => true, // HEAD request
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $success = ($httpCode === 200);
            $this->logger->info("[Wiki] Connection test: " . ($success ? 'SUCCESS' : "FAILED ($httpCode)"));
            return $success;
            
        } catch (\Exception $e) {
            $this->logger->error("[Wiki] Connection test failed: " . $e->getMessage());
            return false;
        }
    }
}