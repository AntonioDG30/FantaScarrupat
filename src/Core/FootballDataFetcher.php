<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Core;

use FantacalcioAnalyzer\Utils\Logger;
use FantacalcioAnalyzer\Utils\NormalizerTrait;

/**
 * Fetch dinamico da Football-Data.org (API)
 */
class FootballDataFetcher
{
    use NormalizerTrait;
    
    // Endpoint base dell'API e codice competizione (SA = Serie A)
    private const BASE = "https://api.football-data.org/v4";
    private const COMP = "SA";
    
    private string $token;
    private Logger $logger;

    // --- RATE LIMIT / RETRY ---
    /** Numero max tentativi complessivi per richiesta (inclusi 429). */
    private int $maxRetries = 12; // era 3

    /** Intervallo minimo tra richieste (secondi) per rispettare il rate limit. */
    private float $minIntervalSec = 6.0;

    /** Timestamp dell’ultima richiesta andata a buon fine (per pacing). */
    private float $lastReqTs = 0.0;

    // --- CACHE IN-MEMORIA (per singola esecuzione) ---
    /** @var array<int, array> seasonStartYear => team-name[] */
    private array $cacheTeams = [];
    /** @var array<int, array> seasonStartYear => standings rows */
    private array $cacheStandings = [];
    /** @var array<int, array> teamId => squad[] */
    private array $cacheSquad = [];
    
    public function __construct(string $token)
    {
        $this->token = trim($token);
        $this->logger = new Logger("FootballDataFetcher");
    }

    /** Pacing tra richieste per rispettare il rate limit. */
    private function throttle(): void
    {
        if ($this->lastReqTs <= 0) return;
        $elapsed = microtime(true) - $this->lastReqTs;
        if ($elapsed < $this->minIntervalSec) {
            $sleepUs = (int)(($this->minIntervalSec - $elapsed) * 1_000_000);
            if ($sleepUs > 0) usleep($sleepUs);
        }
    }
    
    /**
     * Wrapper GET con gestione rate limit
     */
    private function get(string $path, array $params = []): array
    {
        if (empty($this->token)) {
            throw new \Exception("API token non configurato");
        }
        
        $url = self::BASE . $path;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $tries = 0;
        
        // loop “resiliente” ai 429 (con reset contatore su 429 dopo attesa)
        while (true) {
            $tries++;

            // pacing tra richieste
            $this->throttle();

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,                 // leggiamo anche gli header
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    "User-Agent: FantacalcioAnalyzer/1.0",
                    "X-Auth-Token: {$this->token}",
                    "Accept: application/json",
                    "Accept-Encoding: identity"
                ]
            ]);
            
            $raw = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hdrSize  = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $wait = min(10, $tries * 2);
                $this->logger->warning("[FD] cURL error: $error. Retry fra {$wait}s (try $tries/{$this->maxRetries})");
                if ($tries >= $this->maxRetries) {
                    throw new \Exception("cURL error: $error");
                }
                sleep($wait);
                continue;
            }

            $rawHeaders = substr((string)$raw, 0, $hdrSize);
            $response   = substr((string)$raw, $hdrSize);

            // mappa headers case-insensitive
            $headers = [];
            foreach (preg_split("/\r\n/", (string)$rawHeaders) as $line) {
                $p = strpos($line, ':');
                if ($p !== false) {
                    $k = strtolower(trim(substr($line, 0, $p)));
                    $v = trim(substr($line, $p + 1));
                    if ($k !== '') $headers[$k] = $v;
                }
            }
            
            if ($httpCode === 429) {
                // 1) prova Retry-After (secondi o data assoluta)
                $retryAfter = 0;
                if (isset($headers['retry-after'])) {
                    $ra = $headers['retry-after'];
                    if (ctype_digit($ra)) {
                        $retryAfter = (int)$ra;
                    } else {
                        $ts = strtotime($ra);
                        if ($ts !== false) $retryAfter = max(1, $ts - time());
                    }
                }
                // 2) parsing del body “You reached your request limit. Wait N seconds.”
                if ($retryAfter === 0 && preg_match('/Wait\s+(\d+)\s+seconds/i', (string)$response, $m)) {
                    $retryAfter = (int)$m[1];
                }
                // 3) fallback: backoff esponenziale prudente
                if ($retryAfter === 0) {
                    $retryAfter = min(60, (int)pow(2, min($tries, 6)));
                }

                // Adatta dinamicamente l’intervallo minimo
                $this->minIntervalSec = max($this->minIntervalSec, max(6.0, $retryAfter / 2.0));

                $this->logger->warning("[FD] Rate limit 429. Attendo {$retryAfter}s (try {$tries}/{$this->maxRetries})");
                if ($tries >= $this->maxRetries) {
                    // Evita errore definitivo: attende e resetta il contatore
                    sleep($retryAfter);
                    $tries = 0;
                } else {
                    sleep($retryAfter);
                }
                continue;
            }
            
            if ($httpCode >= 400) {
                // 4xx/5xx non-429: ritenta qualche volta, poi errore reale
                $wait = min(15, 2 + ($tries * 2));
                $this->logger->warning("[FD] HTTP $httpCode. Retry fra {$wait}s (try {$tries}/{$this->maxRetries}) - URL=$url");
                if ($tries >= $this->maxRetries) {
                    throw new \Exception("HTTP $httpCode: $response");
                }
                sleep($wait);
                continue;
            }

            // Successo → aggiorna timestamp per pacing
            $this->lastReqTs = microtime(true);

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response");
            }
            
            return $data ?: [];
        }
    }
    
    /**
     * Restituisce la lista delle squadre per la stagione
     */
    public function getCurrentTeams(int $seasonStartYear): array
    {
        // CACHE HIT?
        if (isset($this->cacheTeams[$seasonStartYear])) {
            $this->logger->info("[FD] Teams cache HIT for season $seasonStartYear");
            return $this->cacheTeams[$seasonStartYear];
        }

        try {
            $this->logger->info("[FD] Fetching teams for season $seasonStartYear");
            $data = $this->get("/competitions/" . self::COMP . "/teams", ['season' => $seasonStartYear]);
            
            $teams = [];
            foreach ($data['teams'] ?? [] as $team) {
                $name = trim($team['name'] ?? '');
                if (!empty($name)) {
                    $teams[] = $name;
                }
            }

            // cache
            $this->cacheTeams[$seasonStartYear] = $teams;
            
            $this->logger->info("[FD] Found " . count($teams) . " teams: " . implode(', ', $teams));
            return $teams;
            
        } catch (\Exception $e) {
            $this->logger->error("[FD] Error fetching teams $seasonStartYear: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Restituisce la classifica finale con posizioni, nomi squadre e gol subiti
     */
    public function getLeagueTable(int $seasonStartYear): array
    {
        // CACHE HIT?
        if (isset($this->cacheStandings[$seasonStartYear])) {
            $this->logger->info("[FD] League table cache HIT for season $seasonStartYear");
            return $this->cacheStandings[$seasonStartYear];
        }

        try {
            $this->logger->info("[FD] Fetching league table for season $seasonStartYear");
            $data = $this->get("/competitions/" . self::COMP . "/standings", ['season' => $seasonStartYear]);
            
            $standings = $data['standings'] ?? [];
            
            // Cerca la classifica "TOTAL"
            $table = null;
            foreach ($standings as $s) {
                if (strtoupper($s['type'] ?? '') === 'TOTAL') {
                    $table = $s['table'] ?? [];
                    break;
                }
            }
            
            if (!$table) {
                $this->logger->warning("[FD] No TOTAL standings found");
                $this->cacheStandings[$seasonStartYear] = [];
                return [];
            }
            
            $rows = [];
            foreach ($table as $item) {
                $pos = $item['position'] ?? null;
                $teamName = trim($item['team']['name'] ?? '');
                // Alcune risposte usano 'against' invece di 'goalsAgainst'
                $ga = $item['goalsAgainst'] ?? ($item['against'] ?? null);
                
                if ($teamName && $pos !== null && is_numeric($pos)) {
                    $rows[] = [
                        'Pos' => (int)$pos,
                        'Team' => $teamName,
                        'GA' => is_numeric($ga) ? (int)$ga : null
                    ];
                }
            }
            
            // Ordina per posizione
            usort($rows, fn($a, $b) => $a['Pos'] <=> $b['Pos']);

            // cache
            $this->cacheStandings[$seasonStartYear] = $rows;
            
            $this->logger->info("[FD] League table: " . count($rows) . " teams");
            foreach ($rows as $row) {
                $this->logger->info("[FD] {$row['Pos']}. {$row['Team']} (GA: {$row['GA']})");
            }
            
            return $rows;
            
        } catch (\Exception $e) {
            $this->logger->error("[FD] Error fetching league table $seasonStartYear: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcola le liste dinamiche: neopromosse, media classifica, 50+ GA
     */
    public function computeDynamicLists(string $currentSeason, string $lastSeason): array
    {
        $this->logger->info("[FD] Computing dynamic lists for $currentSeason vs $lastSeason");
        
        // Converte stagioni in anni di inizio
        $curY = $this->seasonStartYear($currentSeason);
        $lastY = $this->seasonStartYear($lastSeason);
        
        $this->logger->info("[FD] Season years: current=$curY, last=$lastY");
        
        // Fetch teams for both seasons
        $currentTeams = $this->getCurrentTeams($curY);
        $lastTeams = $this->getCurrentTeams($lastY);
        
        if (empty($currentTeams) || empty($lastTeams)) {
            throw new \Exception("Could not fetch teams for both seasons");
        }
        
        // Neopromosse: teams in current season but not in last season
        $lastTeamsSet = array_flip($lastTeams);
        $neopromosse = [];
        foreach ($currentTeams as $team) {
            if (!isset($lastTeamsSet[$team])) {
                $neopromosse[] = $team;
            }
        }
        
        $this->logger->info("[FD] Neopromosse (raw): " . implode(', ', $neopromosse));
        
        // Fetch league table for last season (usa cache se già chiamato)
        $leagueTable = $this->getLeagueTable($lastY);
        
        if (empty($leagueTable)) {
            throw new \Exception("Could not fetch league table for $lastY");
        }
        
        // Squadre posizioni 10-17
        $squadreMedia = [];
        foreach ($leagueTable as $row) {
            if ($row['Pos'] >= 10 && $row['Pos'] <= 17) {
                $squadreMedia[] = $row['Team'];
            }
        }
        
        $this->logger->info("[FD] Squadre media (10-17): " . implode(', ', $squadreMedia));
        
        // Squadre con 50+ gol subiti
        $squadre50GA = [];
        foreach ($leagueTable as $row) {
            if ($row['GA'] !== null && $row['GA'] >= 50) {
                $squadre50GA[] = $row['Team'];
            }
        }
        
        $this->logger->info("[FD] Squadre 50+ GA: " . implode(', ', $squadre50GA));
        
        return [$neopromosse, $squadreMedia, $squadre50GA];
    }
    
    /**
     * Restituisce la struttura completa teams per debug
     */
    public function getCurrentTeamsFull(int $seasonStartYear): array
    {
        try {
            // se vuoi, potresti usare la cache già caricata con getCurrentTeams()
            $data = $this->get("/competitions/" . self::COMP . "/teams", ['season' => $seasonStartYear]);
            return $data['teams'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error("[FD] Error fetching full teams $seasonStartYear: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottiene la rosa di una squadra specifica
     */
    public function getTeamSquad(int $teamId): array
    {
        // CACHE HIT?
        if (isset($this->cacheSquad[$teamId])) {
            $this->logger->info("[FD] Squad cache HIT for team $teamId");
            return $this->cacheSquad[$teamId];
        }

        try {
            $this->logger->info("[FD] Fetching squad for team $teamId");
            $data = $this->get("/teams/$teamId");
            $squad = $data['squad'] ?? [];
            // cache
            $this->cacheSquad[$teamId] = $squad;
            return $squad;
        } catch (\Exception $e) {
            $this->logger->error("[FD] Error fetching squad for team $teamId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Test di connessione API
     */
    public function testConnection(): bool
    {
        try {
            $this->logger->info("[FD] Testing API connection");
            $data = $this->get("/competitions/" . self::COMP);
            $name = $data['name'] ?? '';
            $this->logger->info("[FD] API test successful: $name");
            return !empty($name);
        } catch (\Exception $e) {
            $this->logger->error("[FD] API test failed: " . $e->getMessage());
            return false;
        }
    }
}
