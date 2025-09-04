<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Core;

use FantacalcioAnalyzer\Utils\Logger;
use FantacalcioAnalyzer\Utils\DataLoader;
use FantacalcioAnalyzer\Utils\NormalizerTrait;
use FantacalcioAnalyzer\Utils\Progress;

/**
 * Classe che centralizza caricamento file, normalizzazioni, indici dinamici e query (criteri)
 * VERSIONE OTTIMIZZATA: Tutte le operazioni API vengono eseguite durante loadData() e cachate
 */
class FantacalcioAnalyzer
{
    use NormalizerTrait;
    
    public string $basePath;
    public string $dataPath;
    public string $listaPath;
    public string $statistichePath;
    public string $quotazioniPath;
    public string $valutazioniPath;
    
    public ?array $listaCorrente = null;
    public array $statistiche = [];
    public array $quotazioni = [];
    public array $valutazioni = [];
    
    public ?string $currentYear = null;
    public ?string $lastYear = null;
    
    // CACHE PRE-CALCOLATE (popolate durante loadData)
    public array $apiSquadIndex = [];
    public array $effectiveNationalities = []; // id -> nazionalità effettiva
    public array $rigoristiMap = [];
    public array $rigoristiRawMap = [];
    public array $teamGaLast = [];
    
    public array $continenti;
    public array $criterionDetailCols;
    
    public array $neopromosse = [];
    public array $squadreMediaClassifica = [];
    public array $squadre50GolSubiti = [];
    
    public FootballDataFetcher $api;
    public SerieAWikiFetcher $wiki;
    public DataLoader $dataLoader;
    public Logger $logger;

    public function getListaCorrente(): ?array { return $this->listaCorrente; }
    public function getStatistiche(): array { return $this->statistiche; }
    public function getQuotazioni(): array { return $this->quotazioni; }
    public function getValutazioni(): array { return $this->valutazioni; }
    public function getCurrentYear(): ?string { return $this->currentYear; }
    public function getLastYear(): ?string { return $this->lastYear; }
    public function getNeopromosse(): array { return $this->neopromosse; }
    public function getSquadreMediaClassifica(): array { return $this->squadreMediaClassifica; }
    public function getSquadre50GolSubiti(): array { return $this->squadre50GolSubiti; }

    public function restoreFromSession(array $sessionData): void
    {
        $this->listaCorrente = $sessionData['lista_corrente'] ?? null;
        $this->statistiche = $sessionData['statistiche'] ?? [];
        $this->quotazioni = $sessionData['quotazioni'] ?? [];
        $this->valutazioni = $sessionData['valutazioni'] ?? [];
        $this->currentYear = $sessionData['current_year'] ?? null;
        $this->lastYear = $sessionData['last_year'] ?? null;
        $this->neopromosse = $sessionData['neopromosse'] ?? [];
        $this->squadreMediaClassifica = $sessionData['squadre_media'] ?? [];
        $this->squadre50GolSubiti = $sessionData['squadre_50ga'] ?? [];
        $this->teamGaLast = $sessionData['team_ga_last'] ?? [];
        
        // RIPRISTINA LE CACHE OTTIMIZZATE
        $this->apiSquadIndex = $sessionData['api_squad_index'] ?? [];
        $this->effectiveNationalities = $sessionData['effective_nationalities'] ?? [];
        $this->rigoristiMap = $sessionData['rigoristi_map'] ?? [];
        $this->rigoristiRawMap = $sessionData['rigoristi_raw_map'] ?? [];
        
        $this->logger->info("Dati ripristinati da sessione (include cache API):");
        $this->logger->info("  Lista corrente: " . count($this->listaCorrente ?? []));
        $this->logger->info("  API squad index: " . count($this->apiSquadIndex));
        $this->logger->info("  Nazionalità effettive: " . count($this->effectiveNationalities));
        $this->logger->info("  Rigoristi: " . count($this->rigoristiMap));
    }
        
    public function __construct(string $basePath = '')
    {
        if (empty($basePath)) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        }
        
        if (!is_dir($basePath)) {
            $basePath = realpath($basePath) ?: getcwd();
        }
        
        $this->basePath = $basePath;
        $this->dataPath = $this->basePath . '/data';
        
        $this->listaPath = $this->dataPath . '/lista';
        $this->statistichePath = $this->dataPath . '/statistiche';
        $this->quotazioniPath = $this->dataPath . '/quotazioni';
        $this->valutazioniPath = $this->dataPath . '/valutazioni';
        
        $this->continenti = defined('CONTINENTI') ? CONTINENTI : [];
        $this->criterionDetailCols = defined('CRITERION_DETAIL_COLS') ? CRITERION_DETAIL_COLS : [];
        
        $token = defined('FOOTBALL_DATA_API_TOKEN') ? FOOTBALL_DATA_API_TOKEN : '';
        $this->api = new FootballDataFetcher($token);
        $this->wiki = new SerieAWikiFetcher();
        $this->dataLoader = new DataLoader();
        $this->logger = new Logger("FantacalcioAnalyzer");
    }
    
    /**
     * Carica tutti i dataset richiesti e prepara le liste dinamiche
     * VERSIONE OTTIMIZZATA: Include pre-calcolo di tutte le cache API + Progress tracking
     */
    public function loadData(): void
    { 
        $this->logger->info("Percorso base: {$this->basePath}");
        $this->logger->info("Percorso dati: {$this->dataPath}");
        
        if (!is_dir($this->dataPath)) {
            throw new \Exception("Cartella dati non trovata: {$this->dataPath}");
        }

        // === STEP 1: Lista Corrente ===
        Progress::update(3, 'Caricamento lista giocatori...');
        $this->loadListaCorrente();
        $this->logger->info("✅ Lista corrente caricata: " . count($this->listaCorrente ?? []) . " giocatori");

        // === STEP 2: Determinazione Anno ===
        Progress::update(4, 'Determinazione stagioni...');
        $this->determineCurrentYear();
        $this->logger->info("✅ Stagioni: corrente={$this->currentYear}, ultima={$this->lastYear}");

        // === STEP 3: Statistiche ===
        Progress::update(5, 'Caricamento statistiche...');
        $this->loadStatistiche();
        $this->logger->info("✅ Statistiche caricate per " . count($this->statistiche) . " stagioni");

        // === STEP 4: Quotazioni ===
        Progress::update(6, 'Caricamento quotazioni...');
        $this->loadQuotazioni();
        $this->logger->info("✅ Quotazioni caricate per " . count($this->quotazioni) . " stagioni");

        // === STEP 5: Valutazioni ===
        Progress::update(7, 'Caricamento valutazioni...');
        $this->loadValutazioni();
        $playerCount = count($this->valutazioni[$this->lastYear] ?? []);
        $this->logger->info("✅ Valutazioni caricate per $playerCount giocatori");

        // === STEP 6: Liste Dinamiche ===
        Progress::update(8, 'Calcolo liste dinamiche (API/Wikipedia)...');
        $this->fetchDynamicLists();
        $this->logger->info("✅ Liste dinamiche calcolate");

        // === STEP 7: Pre-calcolo Cache API ===
        Progress::update(9, 'Pre-calcolo cache API (rose, nazionalità, rigoristi)...');
        $this->logger->info("=== INIZIO PRE-CALCOLO CACHE API ===");
        $this->prewarmApiCaches();
        $this->logger->info("=== FINE PRE-CALCOLO CACHE API ===");
        
        // === COMPLETAMENTO ===
        Progress::addLogEntry("Cache API completate: " . count($this->apiSquadIndex) . " rose, " . 
                             count($this->effectiveNationalities) . " nazionalità, " . 
                             count($this->rigoristiMap) . " rigoristi");
        
        $this->logger->info("✅ Dati caricati con successo (include cache API)");
        $this->logger->info("  Anno corrente: {$this->currentYear}");
        $this->logger->info("  Ultimo anno con dati: {$this->lastYear}");
        $this->logger->info("  Giocatori in lista: " . count($this->listaCorrente ?? []));
        $this->logger->info("  Anni di statistiche: " . implode(', ', array_keys($this->statistiche)));
        $this->logger->info("  Rose API indicizzate: " . count($this->apiSquadIndex));
        $this->logger->info("  Nazionalità effettive: " . count($this->effectiveNationalities));
        $this->logger->info("  Rigoristi: " . count($this->rigoristiMap));
    }
    
    /**
     * PRE-CALCOLA TUTTE LE CACHE API DURANTE IL CARICAMENTO (con progress)
     */
    private function prewarmApiCaches(): void
    {
        $startTime = microtime(true);
        
        // 1. Indicizza rose API
        Progress::addLogEntry("Costruzione indice rose API...");
        $this->buildApiSquadIndex();
        Progress::addLogEntry("Rose API indicizzate: " . count($this->apiSquadIndex) . " squadre");
        
        // 2. Calcola nazionalità effettive
        Progress::addLogEntry("Risoluzione nazionalità effettive...");
        $this->computeEffectiveNationalities();
        Progress::addLogEntry("Nazionalità risolte: " . count($this->effectiveNationalities) . " giocatori");
        
        // 3. Scarica rigoristi  
        Progress::addLogEntry("Download rigoristi da fantacalcio.it...");
        $this->fetchRigoristi();
        Progress::addLogEntry("Rigoristi trovati per " . count($this->rigoristiMap) . " squadre");
        
        $duration = microtime(true) - $startTime;
        $this->logger->info("Cache API pre-calcolate in " . round($duration, 2) . "s");
        Progress::addLogEntry("Cache API completate in " . round($duration, 1) . "s");
    }
    
    /**
     * Carica il file CSV della lista giocatori e applica normalizzazioni di base
     */
    public function loadListaCorrente(): void
    {
        $pattern = $this->listaPath . '/*.csv';
        $csvFiles = $this->dataLoader->glob($pattern);
        $this->logger->info("[Lista] Ricerca: $pattern -> " . count($csvFiles) . " file");
        
        if (empty($csvFiles)) {
            throw new \Exception("Nessun file CSV trovato in {$this->listaPath}");
        }
        
        $csvFile = $csvFiles[0];
        foreach ($csvFiles as $file) {
            if (stripos(basename($file), 'ListaFantaAsta') !== false) {
                $csvFile = $file;
                break;
            }
        }
        
        $this->logger->info("[Lista] Carico: " . basename($csvFile));
        
        $rawData = $this->safeReadCsv($csvFile);
        
        if (empty($rawData)) {
            throw new \Exception("CSV lista corrente vuoto o non leggibile");
        }
        
        $numColumns = count($rawData[0]);
        $this->logger->info("[Lista] Colonne rilevate nel CSV: $numColumns");
        
        $baseColumns = [
            'id', 'cognome', 'nome_completo', 'ruolo_classic', 'ruolo_mantra',
            'quota_iniziale_classic', 'quota_attuale_classic', 'quota_iniziale_mantra',
            'quota_attuale_mantra', 'squadra', 'fvm_classic', 'fvm_mantra',
            'piede', 'nazionalita', 'data_nascita'
        ];
        
        while (count($baseColumns) < $numColumns) {
            $extraIndex = count($baseColumns) - 14;
            $baseColumns[] = "extra_col_$extraIndex";
        }
        
        if ($numColumns >= 3) {
            $transferredIndex = $numColumns - 3;
            $baseColumns[$transferredIndex] = 'trasferito';
        }
        
        $this->logger->info("[Lista] Colonne mappate: " . implode(', ', $baseColumns));
        
        $this->listaCorrente = $this->dataLoader->readCsv($csvFile, false, $baseColumns);
        
        if (empty($this->listaCorrente)) {
            throw new \Exception("CSV lista corrente vuoto dopo il caricamento");
        }
        
        foreach ($this->listaCorrente as &$row) {
            if (isset($row['data_nascita']) && !empty($row['data_nascita'])) {
                $date = $this->dataLoader->parseDate($row['data_nascita']);
                $row['data_nascita'] = $date ? $date->format('Y-m-d') : null;
            }
            
            if (isset($row['id'])) {
                $row['id'] = is_numeric($row['id']) ? (int)$row['id'] : null;
            }
            
            if (isset($row['squadra'])) {
                $row['squadra_norm'] = $this->normalizeTeam($row['squadra']);
            }
            
            if (isset($row['trasferito'])) {
                $row['trasferito'] = is_numeric($row['trasferito']) ? (int)$row['trasferito'] : 0;
            }
        }
        unset($row);
        
        $originalCount = count($this->listaCorrente);
        $this->filterTransferredPlayers();
        $filteredCount = count($this->listaCorrente);
        $excludedCount = $originalCount - $filteredCount;
        
        $this->logger->info("[Lista] Giocatori totali: $originalCount");
        $this->logger->info("[Lista] Giocatori esclusi (trasferiti): $excludedCount");
        $this->logger->info("[Lista] Giocatori rimanenti: $filteredCount");
    }
    
    public function filterTransferredPlayers(): void
    {
        if (empty($this->listaCorrente)) {
            return;
        }
        
        $originalCount = count($this->listaCorrente);
        
        $this->listaCorrente = array_filter($this->listaCorrente, function($row) {
            $trasferito = $row['trasferito'] ?? 0;
            return $trasferito != 1;
        });
        
        $this->listaCorrente = array_values($this->listaCorrente);
        
        $filteredCount = count($this->listaCorrente);
        $excludedCount = $originalCount - $filteredCount;
        
        $this->logger->info("[Filtro] Esclusi $excludedCount giocatori trasferiti");
    }
    
    public function determineCurrentYear(): void
    {
        if (defined('CURRENT_SEASON') && defined('LAST_SEASON')) {
            $this->currentYear = CURRENT_SEASON;
            $this->lastYear = LAST_SEASON;
            $this->logger->info("[Anni] Usando configurazione esplicita: current={$this->currentYear}, last={$this->lastYear}");
            return;
        }
        
        $allFiles = array_merge(
            $this->dataLoader->glob($this->statistichePath . '/*.csv'),
            $this->dataLoader->glob($this->statistichePath . '/*.xlsx')
        );
        
        $years = [];
        foreach ($allFiles as $file) {
            $y = $this->extractYearFromFilename(basename($file));
            if ($y) {
                $years[] = $y;
            }
        }
        
        if (empty($years)) {
            $this->currentYear = '2025-26';
            $this->lastYear = '2024-25';
            $this->logger->warning("Nessun anno dedotto dai file. Uso default {$this->currentYear} / {$this->lastYear}");
            return;
        }
        
        $years = array_unique($years);
        usort($years, function($a, $b) {
            return $this->yearKey($a) <=> $this->yearKey($b);
        });
        
        $this->lastYear = end($years);
        
        [$a, $b] = explode('-', $this->lastYear);
        $nextA = (int)$a + 1;
        $nextB = (int)$b + 1;
        $this->currentYear = $nextA . '-' . str_pad((string)$nextB, 2, '0', STR_PAD_LEFT);
        
        $this->logger->info("[Anni] Dedotto da file: last={$this->lastYear}, current={$this->currentYear}");
    }
    
    public function loadStatistiche(): void
    {
        $csvFiles = $this->dataLoader->glob($this->statistichePath . '/*.csv');
        $xlsxFiles = $this->dataLoader->glob($this->statistichePath . '/*.xlsx');
        
        $this->logger->info("[Statistiche] CSV trovati: " . count($csvFiles));
        $this->logger->info("[Statistiche] XLSX trovati: " . count($xlsxFiles));
        
        foreach ($csvFiles as $file) {
            $year = $this->extractYearFromFilename(basename($file));
            if (!$year) continue;
            
            try {
                $data = $this->dataLoader->readCsv($file, false);
                
                if (empty($data)) continue;
                
                if (!is_numeric($data[0][0] ?? '')) {
                    array_shift($data);
                }
                
                $mapped = [];
                foreach ($data as $row) {
                    if (count($row) >= 17) {
                        $playerId = is_numeric($row[0]) ? (int)$row[0] : null;
                        if ($playerId !== null) {
                            $mapped[] = [
                                'id' => $playerId,
                                'ruolo' => $row[1] ?? '',
                                'ruolo_mantra' => $row[2] ?? '',
                                'nome' => $row[3] ?? '',
                                'squadra' => $row[4] ?? '',
                                'presenze' => is_numeric($row[5]) ? (int)$row[5] : 0,
                                'media_voto' => is_numeric($row[6]) ? (float)$row[6] : 0,
                                'fanta_media' => is_numeric($row[7]) ? (float)$row[7] : 0,
                                'gol_fatti' => is_numeric($row[8]) ? (int)$row[8] : 0,
                                'gol_subiti' => is_numeric($row[9]) ? (int)$row[9] : 0,
                                'rigori_parati' => is_numeric($row[10]) ? (int)$row[10] : 0,
                                'rigori_calciati' => is_numeric($row[11]) ? (int)$row[11] : 0,
                                'rigori_segnati' => is_numeric($row[12]) ? (int)$row[12] : 0,
                                'rigori_sbagliati' => is_numeric($row[13]) ? (int)$row[13] : 0,
                                'assist' => is_numeric($row[14]) ? (int)$row[14] : 0,
                                'ammonizioni' => is_numeric($row[15]) ? (int)$row[15] : 0,
                                'espulsioni' => is_numeric($row[16]) ? (int)$row[16] : 0,
                                'Au' => isset($row[17]) && is_numeric($row[17]) ? (int)$row[17] : 0
                            ];
                        }
                    }
                }
                
                $this->statistiche[$year] = $mapped;
                $this->logger->info("[Statistiche] $year: " . count($mapped) . " righe");
                
            } catch (\Exception $e) {
                $this->logger->error("[Statistiche] Errore $file: " . $e->getMessage());
            }
        }
        
        if (empty($this->statistiche) && !empty($xlsxFiles)) {
            $this->logger->warning("[Statistiche] Trovati solo file XLSX. Convertire in CSV per usarli.");
            foreach ($xlsxFiles as $file) {
                $this->logger->warning("  - " . basename($file));
            }
        }
        
        $this->logger->info("[Statistiche] Anni caricati: " . count($this->statistiche));
    }
    
    public function loadQuotazioni(): void
    {
        $csvFiles = $this->dataLoader->glob($this->quotazioniPath . '/*.csv');
        $xlsxFiles = $this->dataLoader->glob($this->quotazioniPath . '/*.xlsx');
        
        $this->logger->info("[Quotazioni] CSV trovati: " . count($csvFiles));
        $this->logger->info("[Quotazioni] XLSX trovati: " . count($xlsxFiles));
        
        foreach ($csvFiles as $file) {
            $year = $this->extractYearFromFilename(basename($file));
            if (!$year) continue;
            
            try {
                $data = $this->dataLoader->readCsv($file, false);
                
                if (empty($data)) continue;
                
                if (!is_numeric($data[0][0] ?? '')) {
                    array_shift($data);
                }
                
                $mapped = [];
                foreach ($data as $row) {
                    if (count($row) >= 8) {
                        $id = is_numeric($row[0]) ? (int)$row[0] : null;
                        if ($id !== null) {
                            $mapped[] = [
                                'id' => $id,
                                'ruolo' => $row[1] ?? '',
                                'ruolo_mantra' => $row[2] ?? '',
                                'nome' => $row[3] ?? '',
                                'squadra' => $row[4] ?? '',
                                'quota_attuale' => is_numeric($row[5]) ? (float)$row[5] : 0,
                                'quota_iniziale' => is_numeric($row[6]) ? (float)$row[6] : 0,
                                'differenza' => is_numeric($row[7]) ? (float)$row[7] : 0,
                            ];
                        }
                    }
                }
                
                $this->quotazioni[$year] = $mapped;
                $this->logger->info("[Quotazioni] $year: " . count($mapped) . " righe");
                
            } catch (\Exception $e) {
                $this->logger->error("[Quotazioni] Errore $file: " . $e->getMessage());
            }
        }
        
        $this->logger->info("[Quotazioni] Anni caricati: " . count($this->quotazioni));
    }
    
    public function loadValutazioni(): void
    {
        if (!$this->lastYear) {
            $this->logger->warning("[Valutazioni] last_year non determinato. Skip.");
            return;
        }
        
        $candidates = [
            $this->valutazioniPath . '/' . $this->lastYear,
            $this->valutazioniPath . '/' . str_replace('-', '_', $this->lastYear),
        ];
        
        $votiPath = null;
        foreach ($candidates as $path) {
            if (is_dir($path)) {
                $votiPath = $path;
                break;
            }
        }
        
        if (!$votiPath) {
            $this->logger->warning("[Valutazioni] cartella non trovata per {$this->lastYear}");
            return;
        }
        
        $files = $this->dataLoader->glob($votiPath . '/*.csv');
        $this->logger->info("[Valutazioni] Path=$votiPath  File trovati=" . count($files));
        
        if (empty($files)) {
            return;
        }
        
        $playerVotes = [];
        
        foreach ($files as $file) {
            $filename = basename($file);
            
            if (preg_match('/Giornata[\s_-]*([0-9]{1,2})/i', $filename, $matches)) {
                $giornata = (int)$matches[1];
                
                try {
                    $data = $this->dataLoader->readCsv($file, false);
                    
                    foreach ($data as $row) {
                        if (count($row) >= 4) {
                            $playerId = is_numeric($row[0]) ? (int)$row[0] : null;
                            $vote = $row[3] ?? '';
                            
                            if ($playerId !== null && !empty($vote)) {
                                if (!isset($playerVotes[$playerId])) {
                                    $playerVotes[$playerId] = [];
                                }
                                $playerVotes[$playerId][$giornata] = trim((string)$vote);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->warning("[Valutazioni] errore su $filename: " . $e->getMessage());
                }
            }
        }
        
        $this->valutazioni[$this->lastYear] = $playerVotes;
        $this->logger->info("[Valutazioni] Caricati voti per " . count($playerVotes) . " giocatori");
    }
    
    public function fetchDynamicLists(): void
    {
        $this->logger->info("[Dinamiche] === INIZIO CALCOLO LISTE DINAMICHE ===");
        $this->logger->info("[Dinamiche] Current season: {$this->currentYear}");
        $this->logger->info("[Dinamiche] Last season: {$this->lastYear}");
        
        $neo = $media = $ga50 = [];
        $teamGaMap = [];
        
        $apiSuccess = false;
        try {
            $this->logger->info("[Dinamiche] Tentativo API Football-Data...");
            
            if (!$this->api->testConnection()) {
                throw new \Exception("API connection test failed");
            }
            
            [$neo, $media, $ga50] = $this->api->computeDynamicLists($this->currentYear, $this->lastYear);
            
            if (!empty($neo) || !empty($media) || !empty($ga50)) {
                $apiSuccess = true;
                $this->logger->info("[Dinamiche] API SUCCESS - Neopromosse: " . count($neo) . ", Media: " . count($media) . ", GA50: " . count($ga50));
                
                $lastY = $this->seasonStartYear($this->lastYear);
                $apiLeagueTable = $this->api->getLeagueTable($lastY);
                foreach ($apiLeagueTable as $row) {
                    $teamNorm = $this->normalizeTeam($row['Team'] ?? '');
                    if (isset($this->apiSquadIndex[$teamNorm]) && !empty($this->apiSquadIndex[$teamNorm])) {
                        continue;
                    }
                    if ($teamNorm && isset($row['GA']) && is_numeric($row['GA'])) {
                        $teamGaMap[$teamNorm] = (int)$row['GA'];
                    }
                }
            } else {
                $this->logger->warning("[Dinamiche] API returned empty results");
            }
        } catch (\Exception $e) {
            $this->logger->error("[Dinamiche] API FAILED: " . $e->getMessage());
        }
        
        if (!$apiSuccess) {
            try {
                $this->logger->info("[Dinamiche] Fallback Wikipedia...");
                
                if (!$this->wiki->testConnection()) {
                    throw new \Exception("Wikipedia connection test failed");
                }
                
                [$wneo, $wmedia, $wga50] = $this->wiki->computeDynamicLists($this->currentYear, $this->lastYear);
                
                $neo = $wneo ?? [];
                $media = $wmedia ?? [];
                $ga50 = $wga50 ?? [];
                
                $this->logger->info("[Dinamiche] WIKI SUCCESS - Neopromosse: " . count($neo) . ", Media: " . count($media) . ", GA50: " . count($ga50));
                
                $wikiLeagueTable = $this->wiki->getLeagueTable($this->lastYear);
                foreach ($wikiLeagueTable as $row) {
                    $teamNorm = $this->normalizeTeam($row['Team'] ?? '');
                    if ($teamNorm && isset($row['GA']) && is_numeric($row['GA'])) {
                        $teamGaMap[$teamNorm] = (int)$row['GA'];
                    }
                }
                
            } catch (\Exception $e) {
                $this->logger->error("[Dinamiche] WIKI FALLBACK FAILED: " . $e->getMessage());
            }
        }
        
        if (empty($neo) && empty($media) && empty($ga50)) {
            $this->logger->warning("[Dinamiche] Using hardcoded defaults for 2024-25 season");
            
            $neo = ['Parma', 'Como', 'Venezia'];
            $media = ['Torino', 'Udinese', 'Empoli', 'Parma', 'Lecce', 'Cagliari', 'Venezia', 'Monza'];
            $ga50 = ['Sassuolo', 'Salernitana', 'Frosinone'];
            
            $teamGaMap = [
                'Sassuolo' => 63,
                'Salernitana' => 72,
                'Frosinone' => 65,
                'Empoli' => 52,
                'Cagliari' => 55,
                'Lecce' => 51
            ];
        }
        
        $this->neopromosse = $this->normalizeTeamList($neo);
        $this->squadreMediaClassifica = $this->normalizeTeamList($media);
        $this->squadre50GolSubiti = $this->normalizeTeamList($ga50);
        
        $this->teamGaLast = [];
        foreach ($teamGaMap as $team => $ga) {
            $teamNorm = $this->normalizeTeam($team);
            if ($teamNorm) {
                $this->teamGaLast[$teamNorm] = $ga;
            }
        }
        
        $this->logger->info("[Dinamiche] === RISULTATI FINALI ===");
        $this->logger->info("[Dinamiche] Neopromosse (" . count($this->neopromosse) . "): " . implode(', ', $this->neopromosse));
        $this->logger->info("[Dinamiche] Media classifica (" . count($this->squadreMediaClassifica) . "): " . implode(', ', $this->squadreMediaClassifica));
        $this->logger->info("[Dinamiche] 50+ GA (" . count($this->squadre50GolSubiti) . "): " . implode(', ', $this->squadre50GolSubiti));
        $this->logger->info("[Dinamiche] Team GA mapping (" . count($this->teamGaLast) . "): " . json_encode($this->teamGaLast));
        
        if (empty($this->neopromosse) && empty($this->squadreMediaClassifica) && empty($this->squadre50GolSubiti)) {
            $this->logger->error("[Dinamiche] ERRORE CRITICO: Tutte le liste sono vuote!");
            throw new \Exception("Impossibile calcolare le liste dinamiche");
        }
    }
    
    /**
     * Costruisce l'indice delle rose API (una sola volta)
     */
    private function buildApiSquadIndex(): void
    {
        if (!empty($this->apiSquadIndex)) {
            return;
        }
        
        try {
            $currentSeasonYear = $this->seasonStartYear($this->currentYear);
        } catch (\Exception $e) {
            $this->logger->warning("[API Rose] Anno corrente non valido: " . $e->getMessage());
            return;
        }
        
        $this->logger->info("[API Rose] Costruzione indice per stagione $currentSeasonYear");
        
        $teamsData = $this->api->getCurrentTeamsFull($currentSeasonYear);
        if (empty($teamsData)) {
            $this->logger->warning("[API Rose] Nessuna squadra trovata per la stagione $currentSeasonYear");
            return;
        }
        
        $processedTeams = 0;
        $totalPlayers = 0;
        
        foreach ($teamsData as $team) {
            $teamName = $team['name'] ?? '';
            $teamId = $team['id'] ?? null;
            
            if (!$teamName || !$teamId) {
                continue;
            }
            
            $teamNorm = $this->normalizeTeam($teamName);
            if (!$teamNorm) {
                continue;
            }
            
            try {
                $squad = $this->api->getTeamSquad($teamId);
                $players = [];
                
                foreach ($squad as $player) {
                    $playerName = $player['name'] ?? $player['fullName'] ?? $player['shortName'] ?? '';
                    if (!$playerName) continue;
                    
                    $playerNameNorm = $this->normalizeName($playerName);
                    if (!$playerNameNorm) continue;
                    
                    $nationality = $player['nationality'] ?? $player['countryOfBirth'] ?? '';
                    $dob = $player['dateOfBirth'] ?? '';
                    
                    $players[$playerNameNorm] = [
                        'nat_raw' => $nationality,
                        'nat_norm' => $this->normalizeCountryKey($nationality),
                        'dob' => $dob
                    ];
                }
                
                if (!empty($players)) {
                    $this->apiSquadIndex[$teamNorm] = $players;
                    $processedTeams++;
                    $totalPlayers += count($players);
                }
                
                usleep(500000);
                
            } catch (\Exception $e) {
                $this->logger->warning("[API Rose] Errore per squadra $teamNorm: " . $e->getMessage());
                continue;
            }
        }
        
        $this->logger->info("[API Rose] Indice completato: $processedTeams squadre, $totalPlayers giocatori");
    }
    
    /**
     * Calcola le nazionalità effettive per tutti i giocatori (una sola volta)
     */
    private function computeEffectiveNationalities(): void
    {
        if (!empty($this->effectiveNationalities)) {
            return;
        }
        
        if (empty($this->listaCorrente)) {
            return;
        }
        
        $this->logger->info("[Nazionalità] Calcolo nazionalità effettive per " . count($this->listaCorrente) . " giocatori");
        
        foreach ($this->listaCorrente as $row) {
            $id = $row['id'] ?? null;
            if ($id === null) continue;
            
            $effective = $this->resolveEffectiveNationality($row);
            if ($effective) {
                $this->effectiveNationalities[(int)$id] = $effective;
            }
        }
        
        $this->logger->info("[Nazionalità] Risolte " . count($this->effectiveNationalities) . " nazionalità effettive");
    }
    
    /**
     * Risolve la nazionalità effettiva per un singolo giocatore
     */
    private function resolveEffectiveNationality(array $row): ?string
    {
        $nationality = $row['nazionalita'] ?? '';
        if (empty($nationality)) {
            return null;
        }
        
        $nationalities = array_map('trim', explode(';', $nationality));
        if (count($nationalities) <= 1) {
            return $nationalities[0] ?? null;
        }
        
        try {
            $teamCol = isset($row['squadra_norm']) ? 'squadra_norm' : 'squadra';
            $team = $this->normalizeTeam($row[$teamCol] ?? '');
            
            if (!$team || !isset($this->apiSquadIndex[$team])) {
                return $nationalities[0];
            }
            
            $fullName = $this->normalizeName($row['nome_completo'] ?? $row['nome'] ?? '');
            if (!$fullName) {
                return $nationalities[0];
            }
            
            $squad = $this->apiSquadIndex[$team] ?? [];
            $entry = $squad[$fullName] ?? null;
            
            if (!$entry) {
                $surname = $this->surnameFromFull($fullName);
                $candidates = [];
                foreach ($squad as $playerName => $playerData) {
                    if (str_ends_with($playerName, " " . $surname) || 
                        explode(' ', $playerName)[count(explode(' ', $playerName))-1] === $surname) {
                        $candidates[] = $playerData;
                    }
                }
                if (count($candidates) === 1) {
                    $entry = $candidates[0];
                }
            }
            
            if (!$entry) {
                return $nationalities[0];
            }
            
            $apiNatNorm = $this->normalizeCountryKey($entry['nat_raw'] ?? '');
            if (!$apiNatNorm) {
                return $nationalities[0];
            }
            
            foreach ($nationalities as $nat) {
                if ($this->normalizeCountryKey($nat) === $apiNatNorm) {
                    return $nat;
                }
            }
            
            return $nationalities[0];
            
        } catch (\Exception $e) {
            $this->logger->warning("Errore risoluzione nazionalità: " . $e->getMessage());
            return $nationalities[0];
        }
    }
    
    /**
     * Scarica rigoristi da fantacalcio.it tramite scraping HTML
     */
    private function fetchRigoristi(): void
    {
        if (!empty($this->rigoristiMap)) {
            return;
        }

        $url = 'https://www.fantacalcio.it/rigoristi-serie-a';
        $this->logger->info("[Rigoristi] Scraping da $url");

        // Raccogli le squadre note (normalizzate) dalla lista corrente
        $teamCol = isset($this->listaCorrente[0]['squadra_norm']) ? 'squadra_norm' : 'squadra';
        $knownTeams = [];
        foreach ($this->listaCorrente as $row) {
            $t = $this->normalizeTeam($row[$teamCol] ?? '');
            if ($t) { $knownTeams[$t] = true; }
        }
        $knownTeams = array_keys($knownTeams);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari',
            ]);
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                throw new \Exception("cURL: $err");
            }
            if ($httpCode !== 200 || empty($html)) {
                throw new \Exception("HTTP=$httpCode, html vuoto=" . (int)empty($html));
            }

            // DOM parse
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();
            $xp = new \DOMXPath($dom);

            // Helper per cercare un contenitore che contenga il nome squadra e la sezione "Rigori"
            $findTeamContainer = function (string $team) use ($xp): ?\DOMNode {
                $teamQ = preg_quote($team, '/');
                // Cerca un nodo che abbia "team" nel testo e risalendo un po' contenga "Rigori"
                $nodes = $xp->query("//*[contains(translate(normalize-space(text()),
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZÁÉÍÓÚÄËÏÖÜÀÈÌÒÙ',
                    'abcdefghijklmnopqrstuvwxyzáéíóúäëïöüàèìòù'), 
                    '".strtolower($team)."')]");
                foreach ($nodes as $n) {
                    // risali qualche antenato alla ricerca di "Rigori"
                    $anc = $n;
                    for ($k = 0; $k < 5 && $anc; $k++, $anc = $anc->parentNode) {
                        $txt = strtolower(trim($anc->textContent ?? ''));
                        if ($txt && strpos($txt, 'rigori') !== false) {
                            return $anc;
                        }
                    }
                }
                return null;
            };

            // Dato il container squadra, estrai il testo della sezione tra "Rigori" e "Calci piazzati"
            $sliceRigoriOnly = function (\DOMNode $container): string {
                $raw = strtolower($container->textContent ?? '');
                if ($raw === '') return '';
                // prendi la fetta dalla parola "rigori" fino a prima di "calci piazzati"/"punizioni"
                if (!preg_match('/rigori(.+?)(calci\s+piazzati|punizioni|$)/is', $raw, $m)) {
                    return '';
                }
                return trim($m[1]);
            };

            $this->rigoristiMap = [];
            $this->rigoristiRawMap = [];

            foreach ($knownTeams as $team) {
                $cont = $findTeamContainer($team);
                if (!$cont) { continue; }

                $rigoriText = $sliceRigoriOnly($cont);
                if ($rigoriText === '') { continue; }

                // Spezza in possibili nomi e pulisci
                $candidates = preg_split('/[,;]|(?:\d+[\.\)])\s*|\s+e\s+|\s{2,}/u', $rigoriText) ?: [];
                $ordered = [];
                foreach ($candidates as $chunk) {
                    $name = $this->cleanPlayerName($chunk);
                    if ($this->isProbablePlayerName($name)) {
                        $ordered[] = $name;
                    }
                }
                $ordered = array_values(array_unique(array_filter($ordered)));

                if (!empty($ordered)) {
                    // *** TOP1 ONLY ***
                    $top1Raw = $ordered[0];
                    $this->rigoristiRawMap[$team] = [$top1Raw];
                    $this->rigoristiMap[$team]    = [ $this->normalizeName($top1Raw) ];
                    $this->logger->info("[Rigoristi][TOP1] $team: $top1Raw");
                }
            }

            // Se nulla è stato trovato, usa fallback
            if (empty($this->rigoristiMap)) {
                $this->logger->warning("[Rigoristi] Scraping vuoto, uso fallback.");
                $this->useRigoristiFallback();
            }
        } catch (\Throwable $e) {
            $this->logger->error("[Rigoristi] Errore scraping: " . $e->getMessage());
            $this->useRigoristiFallback();
        }
    }

    
    /**
     * Parsing HTML per rigoristi con DOMDocument
     */
    private function parseRigoristiHtml(string $html): void
    {
        $knownTeams = [];
        if (!empty($this->listaCorrente)) {
            foreach ($this->listaCorrente as $row) {
                $team = $this->normalizeTeam($row['squadra'] ?? '');
                if ($team) $knownTeams[$team] = true;
            }
        }
        
        // Prova prima un parsing semplice con regex
        $pattern = '/<h3[^>]*>([^<]+)<\/h3>.*?Rigori:?\s*([^<]+)</si';
        if (preg_match_all($pattern, $html, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $teamName = $this->normalizeTeam($matches[1][$i]);
                if (isset($knownTeams[$teamName])) {
                    $rigoristiText = $matches[2][$i];
                    $rigoristi = [];
                    
                    // Estrai nomi dal testo
                    $names = preg_split('/[,;]|\se\s/', $rigoristiText);
                    foreach ($names as $name) {
                        $cleanName = $this->cleanPlayerName($name);
                        if ($this->isProbablePlayerName($cleanName)) {
                            $rigoristi[] = $cleanName;
                            if (count($rigoristi) >= 2) break;
                        }
                    }
                    
                    if (!empty($rigoristi)) {
                        $this->rigoristiRawMap[$teamName] = $rigoristi;
                        $this->rigoristiMap[$teamName] = array_map([$this, 'normalizeName'], $rigoristi);
                        $this->logger->info("[Rigoristi] $teamName: " . implode(', ', $rigoristi));
                    }
                }
            }
        }
        
        // Se non trova niente con regex, prova con DOMDocument
        if (empty($this->rigoristiMap)) {
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new \DOMXPath($dom);
            
            // ... resto del codice DOMDocument esistente ...
        }
        
        if (empty($this->rigoristiMap)) {
            throw new \Exception("Nessun rigorista trovato nel parsing");
        }
        
        $this->logger->info("[Rigoristi] Scraping completato: " . count($this->rigoristiMap) . " squadre");
    }
    
    /**
     * Trova rigoristi per una specifica squadra nell'HTML
     */
    private function findRigoristiForTeam(\DOMXPath $xpath, string $teamNorm): array
    {
        $teamVariants = [
            $teamNorm,
            ucfirst(strtolower($teamNorm)),
            strtoupper($teamNorm),
            $teamNorm . ' FC',
            'FC ' . $teamNorm,
            $teamNorm . ' Calcio'
        ];
        
        foreach ($teamVariants as $variant) {
            $teamNodes = $xpath->query("//text()[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '" . strtolower($variant) . "')]");
            
            foreach ($teamNodes as $teamNode) {
                $container = $teamNode->parentNode;
                if (!$container) continue;
                
                for ($level = 0; $level < 5; $level++) {
                    if (!$container) break;
                    
                    $rigoristi = $this->extractRigoristiFromContainer($xpath, $container);
                    if (!empty($rigoristi)) {
                        return array_slice($rigoristi, 0, 3);
                    }
                    
                    $container = $container->parentNode;
                }
            }
        }
        
        return [];
    }
    
    /**
     * Estrae rigoristi da un contenitore HTML
     */
    private function extractRigoristiFromContainer(\DOMXPath $xpath, \DOMNode $container): array
    {
        $rigoristi = [];
        
        $rigoriNodes = $xpath->query(".//text()[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'rigori')]", $container);
        
        if ($rigoriNodes->length === 0) {
            return [];
        }
        
        $allText = '';
        $walker = $xpath->query(".//text()", $container);
        foreach ($walker as $textNode) {
            $allText .= ' ' . $textNode->nodeValue;
        }
        
        if (preg_match('/rigori[^a-zA-Z]*([^.]+?)(?:calci piazzati|punizioni|$)/i', $allText, $matches)) {
            $rigoriText = $matches[1];
            
            $names = preg_split('/[,;]|\d+\.|\s+e\s+/', $rigoriText);
            
            foreach ($names as $name) {
                $cleanName = $this->cleanPlayerName($name);
                if ($this->isProbablePlayerName($cleanName)) {
                    $rigoristi[] = $cleanName;
                    if (count($rigoristi) >= 3) break;
                }
            }
        }
        
        return $rigoristi;
    }
    
    /**
     * Pulisce il nome del giocatore estratto dall'HTML
     */
    private function cleanPlayerName(string $name): string
    {
        $name = trim($name);
        
        $name = preg_replace('/^\d+\.\s*/', '', $name);
        $name = preg_replace('/\(.*?\)/', '', $name);
        $name = preg_replace('/\b(rigori|rigorista|designato|ufficiale)\b/i', '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        return $name;
    }
    
    /**
     * Verifica se un testo sembra essere un nome di giocatore
     */
    private function isProbablePlayerName(string $text): bool
    {
        $text = trim($text);
        $len = strlen($text);
        
        if ($len < 2 || $len > 40) {
            return false;
        }
        
        if (preg_match('/\d/', $text)) {
            return false;
        }
        
        $blacklist = ['rigori', 'calci', 'piazzati', 'classifica', 'partite', 'punizioni', 'corner'];
        $textLower = strtolower($text);
        foreach ($blacklist as $word) {
            if (strpos($textLower, $word) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Fallback con dati hardcoded se lo scraping fallisce
     */
    private function useRigoristiFallback(): void
    {
        // (Esempio indicativo: sostituisci i nomi col tuo set aggiornato se serve)
        $fallback = [
            'Inter'      => ['Calhanoglu'],
            'Milan'      => ['Pulisic'],
            'Juventus'   => ['Vlahovic'],
            'Napoli'     => ['Kvaratskhelia'],
            'Roma'       => ['Dybala'],
            'Lazio'      => ['Zaccagni'],
            'Atalanta'   => ['De Ketelaere'],
            'Fiorentina' => ['Kean'],
            'Bologna'    => ['Orsolini'],
            'Torino'     => ['Sanabria'],
            'Udinese'    => ['Thauvin'],
            'Empoli'     => ['Esposito'],
            'Verona'     => ['Tengstedt'],
            'Cagliari'   => ['Viola'],
            'Lecce'      => ['Krstovic'],
            'Parma'      => ['Hernani'],
            'Como'       => ['Cutrone'],
            'Venezia'    => ['Pohjanpalo'],
            'Monza'      => ['Colpani'],
            'Genoa'      => ['Retegui'],
            'Sassuolo'   => ['Pinamonti'],
            'Brescia'    => ['(esempio)'],
            'SPAL'       => ['(esempio)'],
            // ... aggiungi/aggiorna solo se presenti in Serie A corrente
        ];

        $this->rigoristiMap = [];
        $this->rigoristiRawMap = [];

        foreach ($fallback as $team => $list) {
            if (empty($list)) { continue; }
            $top1Raw = $list[0];
            $teamNorm = $this->normalizeTeam($team);
            $this->rigoristiRawMap[$teamNorm] = [$top1Raw];
            $this->rigoristiMap[$teamNorm]    = [ $this->normalizeName($top1Raw) ];
        }

        $this->logger->info("[Rigoristi] Usato fallback TOP1 per " . count($this->rigoristiMap) . " squadre");
    }

    
    public function getLoadedDataSummary(): array
    {
        return [
            'current_year' => $this->currentYear,
            'last_year' => $this->lastYear,
            'players_count' => count($this->listaCorrente ?? []),
            'statistics_years' => array_keys($this->statistiche),
            'quotazioni_years' => array_keys($this->quotazioni),
            'valutazioni_years' => array_keys($this->valutazioni),
            'neopromosse' => $this->neopromosse,
            'squadre_media' => $this->squadreMediaClassifica,
            'squadre_50ga' => $this->squadre50GolSubiti,
            'team_ga_last' => $this->teamGaLast,
            'team_ga_count' => count($this->teamGaLast),
            'dynamic_lists_ok' => !empty($this->neopromosse) || !empty($this->squadreMediaClassifica) || !empty($this->squadre50GolSubiti)
        ];
    }
    
    public function getSessionData(): array
    {
        return [
            'lista_corrente' => $this->listaCorrente,
            'statistiche' => $this->statistiche,
            'quotazioni' => $this->quotazioni,
            'valutazioni' => $this->valutazioni,
            'current_year' => $this->currentYear,
            'last_year' => $this->lastYear,
            'neopromosse' => $this->neopromosse,
            'squadre_media' => $this->squadreMediaClassifica,
            'squadre_50ga' => $this->squadre50GolSubiti,
            'team_ga_last' => $this->teamGaLast,
            'api_squad_index' => $this->apiSquadIndex,
            'effective_nationalities' => $this->effectiveNationalities,
            'rigoristi_map' => $this->rigoristiMap,
            'rigoristi_raw_map' => $this->rigoristiRawMap
        ];
    }
    
    public function runCriteria(string $criteriaId): array
    {
        $methodMap = [
            '1' => 'findUnder23',
            '2' => 'findOver32',
            '3' => 'findPrimaStagione',
            '4' => 'find200Presenze',
            '5' => 'findSudamericani',
            '6' => 'findAfricani',
            '7' => 'findEuropeiNonItaliani',
            '8' => 'findNeopromosse',
            '9' => 'findSquadreMediaBassa',
            '10' => 'findPortieri50Gol',
            '11' => 'findDifensoriGol',
            '12' => 'findCentrocampistiAssist',
            '13' => 'findAttaccantiMax5Gol',
            '14' => 'findPochePresenze',
            '15' => 'findMediaVotoBassa',
            '16' => 'findQuotazioneBassa',
            '17' => 'findQuotazioneMinima',
            '18' => 'findRosaStagionePrecedente',
            '19' => 'findRitornoSerieA',
            '20' => 'findSenzaVoto',
            '21' => 'findMolteAmmonizioni',
            '22' => 'findCambiatoSquadra',
            '23' => 'findAutogolAlmeno1',
            '24' => 'find34Presenze',
            '25' => 'findRigoriSbagliati',
            '26' => 'findFairPlay',
            '27' => 'findPresentiUltime3',
            '28' => 'findGolAssistPlus5',
            '29' => 'findCartelliniRatio',
            '30' => 'findRigoristiDesignati',
            '31' => 'findCambioRuoloUfficiale',
            '32' => 'findEsordientiAssoluti',
            '34' => 'findTroppiGol',
        ];
        
        if (!isset($methodMap[$criteriaId])) {
            throw new \Exception("Criterio non valido: $criteriaId");
        }
        
        $method = $methodMap[$criteriaId];
        if (!method_exists($this, $method)) {
            throw new \Exception("Metodo non implementato: $method");
        }
        
        return $this->$method();
    }
    
    // === CRITERI OTTIMIZZATI ===
    
    public function findUnder23(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $ref = $this->startOfSeasonReference($this->currentYear);
        $cutoff = $ref->modify('-23 years');
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($cutoff) {
            if (empty($row['data_nascita'])) return false;
            try {
                $birth = new \DateTimeImmutable($row['data_nascita']);
                return $birth >= $cutoff;
            } catch (\Exception $e) {
                return false;
            }
        });
        
        $result = [];
        foreach ($filtered as $row) {
            if ($row['data_nascita']) {
                $birth = new \DateTimeImmutable($row['data_nascita']);
                $age = $ref->diff($birth)->y;
                $row['eta'] = $age;
            }
            $result[] = $row;
        }
        
        return $result;
    }
    
    public function findOver32(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $ref = $this->startOfSeasonReference($this->currentYear);
        $cutoff = $ref->modify('-32 years');
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($cutoff) {
            if (empty($row['data_nascita'])) return false;
            try {
                $birth = new \DateTimeImmutable($row['data_nascita']);
                return $birth <= $cutoff;
            } catch (\Exception $e) {
                return false;
            }
        });
        
        $result = [];
        foreach ($filtered as $row) {
            if ($row['data_nascita']) {
                $birth = new \DateTimeImmutable($row['data_nascita']);
                $age = $ref->diff($birth)->y;
                $row['eta'] = $age;
            }
            $result[] = $row;
        }
        
        return $result;
    }
    
    public function findPrimaStagione(): array
    {
        // Se non c'è la lista corrente, ritorna vuoto
        if (empty($this->listaCorrente)) {
            return [];
        }

        // Insieme degli id presenti nella lista corrente (drop dei non numerici)
        $currentIdsSet = [];
        foreach ($this->listaCorrente as $row) {
            if (isset($row['id']) && $row['id'] !== '' && is_numeric($row['id'])) {
                $currentIdsSet[(int)$row['id']] = true;
            }
        }

        // Insieme degli id storici: tutte le stagioni nelle statistiche tranne l'anno corrente
        $historicalIdsSet = [];
        foreach ($this->statistiche as $year => $rows) {
            if ($year === $this->currentYear) {
                continue;
            }
            foreach ($rows as $r) {
                if (isset($r['id']) && $r['id'] !== '' && is_numeric($r['id'])) {
                    $historicalIdsSet[(int)$r['id']] = true;
                }
            }
        }

        // Differenza insiemistica: id presenti ora ma mai visti prima nelle statistiche
        $currentIds = array_keys($currentIdsSet);
        $historicalIds = array_keys($historicalIdsSet);
        $newIds = array_diff($currentIds, $historicalIds);

        // Filtra e restituisce le righe della lista corrente corrispondenti a $newIds
        $result = [];
        if (!empty($newIds)) {
            $newIdsLookup = array_fill_keys($newIds, true);
            foreach ($this->listaCorrente as $row) {
                if (isset($row['id']) && is_numeric($row['id']) && isset($newIdsLookup[(int)$row['id']])) {
                    $result[] = $row;
                }
            }
        }

        return $result;
    }



    
    public function find200Presenze(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $totals = [];
        foreach ($this->statistiche as $year => $data) {
            foreach ($data as $row) {
                if ($row['id'] !== null && $row['presenze'] > 0) {
                    $id = $row['id'];
                    $totals[$id] = ($totals[$id] ?? 0) + $row['presenze'];
                }
            }
        }
        
        $ids200 = [];
        foreach ($totals as $id => $tot) {
            if ($tot > 200) {
                $ids200[$id] = true;
            }
        }
        
        $result = array_filter($this->listaCorrente, function($row) use ($ids200) {
            return isset($ids200[$row['id']]);
        });
        
        $result = array_values($result);
        foreach ($result as &$row) {
            $row['presenze_totali'] = $totals[$row['id']] ?? 0;
        }
        
        return $result;
    }
    
    public function findSudamericani(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) {
            $id = (int)($row['id'] ?? 0);
            $effectiveNat = $this->effectiveNationalities[$id] ?? '';
            return $this->matchEffectiveNationalityInContinent($effectiveNat, 'sudamericani');
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            $id = (int)($row['id'] ?? 0);
            unset($row['nazionalita']);
            $row['nazionalita_effettiva'] = $this->effectiveNationalities[$id] ?? 'Non determinata';
        }
        unset($row);
        
        return $result;
    }
    
    public function findAfricani(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) {
            $id = (int)($row['id'] ?? 0);
            $effectiveNat = $this->effectiveNationalities[$id] ?? '';
            return $this->matchEffectiveNationalityInContinent($effectiveNat, 'africani');
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            $id = (int)($row['id'] ?? 0);
            unset($row['nazionalita']);
            $row['nazionalita_effettiva'] = $this->effectiveNationalities[$id] ?? 'Non determinata';
        }
        unset($row);
        
        return $result;
    }
    
    public function findEuropeiNonItaliani(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) {
            $id = (int)($row['id'] ?? 0);
            $effectiveNat = $this->effectiveNationalities[$id] ?? '';
            
            $isEuropean = $this->matchEffectiveNationalityInContinent($effectiveNat, 'europei');
            $isItalian = $this->isItalianEffectiveNationality($effectiveNat);
            
            return $isEuropean && !$isItalian;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            $id = (int)($row['id'] ?? 0);
            unset($row['nazionalita']);
            $row['nazionalita_effettiva'] = $this->effectiveNationalities[$id] ?? 'Non determinata';
        }
        unset($row);
        
        return $result;
    }
    
    public function findNeopromosse(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $teams = array_flip($this->neopromosse);
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($teams) {
            $team = $row['squadra_norm'] ?? $this->normalizeTeam($row['squadra'] ?? '');
            return isset($teams[$team]);
        });
        
        return array_values($filtered);
    }
    
    public function findSquadreMediaBassa(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $teams = array_flip($this->squadreMediaClassifica);
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($teams) {
            $team = $row['squadra_norm'] ?? $this->normalizeTeam($row['squadra'] ?? '');
            return isset($teams[$team]);
        });
        
        return array_values($filtered);
    }
    
    public function findPortieri50Gol(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $teams = array_flip($this->squadre50GolSubiti);
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($teams) {
            $team = $row['squadra_norm'] ?? $this->normalizeTeam($row['squadra'] ?? '');
            return $row['ruolo_classic'] === 'P' && isset($teams[$team]);
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            $teamNorm = $row['squadra_norm'] ?? $this->normalizeTeam($row['squadra'] ?? '');
            $row['GA_scorsa_squadra'] = $this->teamGaLast[$teamNorm] ?? 0;
        }
        
        return $result;
    }
    
    public function findDifensoriGol(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if ($row['ruolo_classic'] !== 'D' || !isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['gol_fatti'] >= 1;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['gol_scorsa_stagione'] = $statsMap[$row['id']]['gol_fatti'];
            }
        }
        
        return $result;
    }
    
    public function findCentrocampistiAssist(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if ($row['ruolo_classic'] !== 'C' || !isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['assist'] >= 3;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['assist_scorsa_stagione'] = $statsMap[$row['id']]['assist'];
            }
        }
        
        return $result;
    }
    
    public function findAttaccantiMax5Gol(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if ($row['ruolo_classic'] !== 'A' || !isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['gol_fatti'] <= 5 && $stats['presenze'] >= 1;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['gol_scorsa_stagione'] = $statsMap[$row['id']]['gol_fatti'];
            }
        }
        
        return $result;
    }
    
    public function findPochePresenze(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $this->logger->info("[Poche presenze] Giocatori con statistiche anno scorso: " . count($statsMap));
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['presenze'] < 10;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            $row['presenze_scorsa_stagione'] = isset($statsMap[$row['id']]) ? 
                $statsMap[$row['id']]['presenze'] : 0;
        }
        
        $this->logger->info("[Poche presenze] Risultati: " . count($result));
        
        return $result;
    }
    
    public function findMediaVotoBassa(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['media_voto'] < 6 && $stats['media_voto'] > 0;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['media_voto'] = $statsMap[$row['id']]['media_voto'];
            }
        }
        
        return $result;
    }
    
    public function findQuotazioneBassa(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) {
            $quota = (float)($row['quota_attuale_classic'] ?? 0);
            return $quota > 0 && $quota <= 6;
        });
        
        return array_values($filtered);
    }
    
    public function findQuotazioneMinima(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) {
            $quota = (float)($row['quota_attuale_classic'] ?? 0);
            return $quota > 0 && $quota <= 3;
        });
        
        return array_values($filtered);
    }
    
    public function findTroppiGol(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['gol_fatti'] > 10;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['gol_scorsa_stagione'] = $statsMap[$row['id']]['gol_fatti'];
            }
        }
        
        usort($result, function($a, $b) {
            $quotaA = (float)($a['quota_attuale_classic'] ?? 0);
            $quotaB = (float)($b['quota_attuale_classic'] ?? 0);
            return $quotaB <=> $quotaA;
        });
        
        return $result;
    }
    
    public function findRitornoSerieA(): array
    {
        if (empty($this->listaCorrente) || count($this->statistiche) < 3) {
            $this->logger->warning("[Ritorno Serie A] Servono almeno 3 stagioni di dati");
            return [];
        }
        
        $yearsSorted = array_keys($this->statistiche);
        usort($yearsSorted, function($a, $b) {
            return $this->yearKey($a) <=> $this->yearKey($b);
        });
        
        $lastTwo = array_slice($yearsSorted, -2);
        $pastPlayers = [];
        $recentPlayers = [];
        $lastSeen = [];
        
        foreach ($this->statistiche as $year => $data) {
            foreach ($data as $row) {
                if ($row['id'] !== null && $row['presenze'] > 0) {
                    $lastSeen[$row['id']] = $year;
                    
                    if (in_array($year, $lastTwo)) {
                        $recentPlayers[$row['id']] = true;
                    } else {
                        $pastPlayers[$row['id']] = true;
                    }
                }
            }
        }
        
        $returned = [];
        foreach ($this->listaCorrente as $row) {
            if ($row['id'] !== null && 
                isset($pastPlayers[$row['id']]) && 
                !isset($recentPlayers[$row['id']])) {
                $returned[] = $row['id'];
            }
        }
        
        $result = array_filter($this->listaCorrente, function($row) use ($returned) {
            return in_array($row['id'], $returned);
        });
        
        $result = array_values($result);
        
        foreach ($result as &$row) {
            $row['ultima_stagione'] = $lastSeen[$row['id']] ?? '';
            if ($row['ultima_stagione']) {
                $lastYear = $this->seasonStartYear($row['ultima_stagione']);
                $curYear = $this->seasonStartYear($this->currentYear);
                $row['anni_assenza'] = $curYear - $lastYear - 1;
            }
        }
        
        return $result;
    }
    
    public function findSenzaVoto(int $soglia = 5): array
    {
        // Se manca la lista corrente o i voti della scorsa stagione, ritorna vuoto
        if (empty($this->listaCorrente) || empty($this->lastYear) || !isset($this->valutazioni[$this->lastYear])) {
            return [];
        }

        // Conta, per ciascun id, quante volte compare "6*"
        $playersNoVote = [];
        foreach ($this->valutazioni[$this->lastYear] as $pid => $votesByGiornata) {
            $countNoVote = 0;
            foreach ($votesByGiornata as $v) {
                if (trim((string)$v) === '6*') {
                    $countNoVote++;
                }
            }
            if ($countNoVote >= $soglia && is_numeric($pid)) {
                $playersNoVote[(int)$pid] = true;
            }
        }

        // Filtra la lista corrente per gli id così identificati
        $result = [];
        if (!empty($playersNoVote)) {
            foreach ($this->listaCorrente as $row) {
                if (isset($row['id']) && is_numeric($row['id']) && isset($playersNoVote[(int)$row['id']])) {
                    $result[] = $row;
                }
            }
        }

        return $result;
    }

    
    public function findMolteAmmonizioni(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['ammonizioni'] > 7;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['ammonizioni_scorsa_stagione'] = $statsMap[$row['id']]['ammonizioni'];
            }
        }
        
        return $result;
    }
    
    public function findCambiatoSquadra(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $lastTeams = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null && $row['presenze'] >= 0) {
                $lastTeams[$row['id']] = $this->normalizeTeam($row['squadra']);
            }
        }
        
        $this->logger->info("[Cambiato squadra] Giocatori con squadra anno scorso: " . count($lastTeams));
        
        $result = [];
        foreach ($this->listaCorrente as $row) {
            if ($row['id'] !== null && isset($lastTeams[$row['id']])) {
                $curTeam = $row['squadra_norm'] ?? $this->normalizeTeam($row['squadra'] ?? '');
                $prevTeam = $lastTeams[$row['id']];
                
                if ($curTeam && $prevTeam && $curTeam !== $prevTeam) {
                    $row['squadra_scorsa'] = $prevTeam;
                    $row['squadra_attuale'] = $curTeam;
                    $result[] = $row;
                }
            }
        }
        
        $this->logger->info("[Cambiato squadra] Risultati: " . count($result));
        
        return $result;
    }
    
    public function findAutogolAlmeno1(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return isset($stats['Au']) && $stats['Au'] >= 1;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['autogol_scorsa_stagione'] = $statsMap[$row['id']]['Au'] ?? 0;
            }
        }
        
        if (empty($result)) {
            $this->logger->warning("[Autogol] Colonna Au non trovata o nessun autogol");
        }
        
        return $result;
    }
    
    public function find34Presenze(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['presenze'] >= 34;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['presenze_scorsa_stagione'] = $statsMap[$row['id']]['presenze'];
            }
        }
        
        return $result;
    }
    
    public function findRigoriSbagliati(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['rigori_sbagliati'] >= 1;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $row['rigori_sbagliati'] = $statsMap[$row['id']]['rigori_sbagliati'];
            }
        }
        
        return $result;
    }
    
    public function findFairPlay(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return $stats['ammonizioni'] == 0 && $stats['espulsioni'] == 0 && $stats['presenze'] >= 6;
        });
        
        return array_values($filtered);
    }
    
    public function findPresentiUltime3(): array
    {
        if (empty($this->listaCorrente) || count($this->statistiche) < 3) {
            $this->logger->warning("[Presenti ultime 3] Servono almeno 3 stagioni di dati");
            return [];
        }
        
        $yearsSorted = array_keys($this->statistiche);
        usort($yearsSorted, function($a, $b) {
            return $this->yearKey($a) <=> $this->yearKey($b);
        });
        
        $lastThree = array_slice($yearsSorted, -3);
        $sets = [];
        
        foreach ($lastThree as $year) {
            $yearIds = [];
            foreach ($this->statistiche[$year] as $row) {
                if ($row['presenze'] > 0) {
                    $yearIds[] = $row['id'];
                }
            }
            $sets[] = $yearIds;
        }
        
        if (count($sets) < 3) {
            return [];
        }
        
        $common = array_intersect(...$sets);
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($common) {
            return in_array($row['id'], $common);
        });
        
        return array_values($filtered);
    }
    
    public function findGolAssistPlus5(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $statsMap = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $statsMap[$row['id']] = $row;
            }
        }
        
        $filtered = array_filter($this->listaCorrente, function($row) use ($statsMap) {
            if (!isset($statsMap[$row['id']])) {
                return false;
            }
            $stats = $statsMap[$row['id']];
            return ($stats['gol_fatti'] + $stats['assist']) >= 5;
        });
        
        $result = array_values($filtered);
        foreach ($result as &$row) {
            if (isset($statsMap[$row['id']])) {
                $stats = $statsMap[$row['id']];
                $row['gol_scorsa_stagione'] = $stats['gol_fatti'];
                $row['assist_scorsa_stagione'] = $stats['assist'];
                $row['gol_plus_assist'] = $stats['gol_fatti'] + $stats['assist'];
            }
        }
        
        return $result;
    }
    
    public function findCartelliniRatio(?float $soglia = null, int $presenzeMin = 10): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $ratios = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null && $row['presenze'] > 0) {
                $cartellini = $row['ammonizioni'] + $row['espulsioni'];
                $ratio = $cartellini / $row['presenze'];
                $ratios[] = ['id' => $row['id'], 'ratio' => $ratio, 'presenze' => $row['presenze']];
            }
        }
        
        if ($soglia === null) {
            $pool = array_filter($ratios, fn($r) => $r['presenze'] >= $presenzeMin);
            if (empty($pool)) $pool = $ratios;
            
            $values = array_column($pool, 'ratio');
            if (!empty($values)) {
                sort($values);
                $percentile75 = $values[(int)(count($values) * 0.75)] ?? 0.25;
                $soglia = max(0.25, round($percentile75, 2));
            } else {
                $soglia = 0.25;
            }
        }
        
        $this->logger->info("[CartelliniRatio] Soglia: $soglia (presenze_min=$presenzeMin)");
        
        $ids = [];
        $ratioMap = [];
        foreach ($ratios as $r) {
            if ($r['ratio'] >= $soglia) {
                $ids[$r['id']] = true;
                $ratioMap[$r['id']] = $r['ratio'];
            }
        }
        
        $result = array_filter($this->listaCorrente, function($row) use ($ids) {
            return isset($ids[$row['id']]);
        });
        
        $result = array_values($result);
        
        foreach ($result as &$row) {
            if (isset($ratioMap[$row['id']])) {
                $row['cartellini_per_presenza'] = $ratioMap[$row['id']];
                $row['rapporto'] = round($ratioMap[$row['id']], 3);
            }
        }
        
        return $result;
    }
    
    /**
     * Criterio 30 — Rigoristi designati (TOP1 per squadra).
     * Regole di match:
     *   - se il TOP1 è un nome completo → match esatto sul nome normalizzato
     *   - se il TOP1 è un cognome → match SOLO se quel cognome è univoco nella rosa della squadra
     */
    public function findRigoristiDesignati(): array
    {
        if (empty($this->listaCorrente)) {
            return [];
        }

        // Assicurati di avere la mappa rigoristi popolata
        if (empty($this->rigoristiMap) && empty($this->rigoristiRawMap)) {
            $this->logger->warning("[Rigoristi] Mappa vuota, provo a caricarla...");
            $this->fetchRigoristi();
            if (empty($this->rigoristiMap) && empty($this->rigoristiRawMap)) {
                $this->logger->error("[Rigoristi] Impossibile recuperare rigoristi");
                return [];
            }
        }

        $teamCol = isset($this->listaCorrente[0]['squadra_norm']) ? 'squadra_norm' : 'squadra';

        // Precalcolo: frequenze cognomi per squadra
        $surnameCountByTeam = [];
        $playersByTeam = [];
        foreach ($this->listaCorrente as $row) {
            $team = $this->normalizeTeam($row[$teamCol] ?? '');
            if (!$team) continue;

            $full = $this->normalizeName($row['nome_completo'] ?? ($row['nome'] ?? ''));
            if ($full === '') continue;

            $sur  = $this->surnameFromFull($full);

            // Gestione cognomi composti con particelle
            $parts = preg_split('/\s+/', $full);
            $surnameComposite = $sur;
            if (count($parts) >= 2) {
                $penult = $parts[count($parts) - 2];
                $particles = ['de','di','da','dal','dalla','dalla','del','della','delle','dei','degli','van','von','la','le','du','mac','mc','al'];
                if (in_array($penult, $particles, true)) {
                    $surnameComposite = $penult . ' ' . $sur;
                }
            }

            $playersByTeam[$team][] = [
                'id' => $row['id'],
                'full' => $full,
                'surname' => $sur,
                'surnameComposite' => $surnameComposite,
            ];

            // Conta sia semplice che composto (servirà per l'univocità)
            $surnameCountByTeam[$team][$sur] = ($surnameCountByTeam[$team][$sur] ?? 0) + 1;
            if ($surnameComposite !== $sur) {
                $surnameCountByTeam[$team][$surnameComposite] = ($surnameCountByTeam[$team][$surnameComposite] ?? 0) + 1;
            }
        }

        // Determina il designato per ogni squadra cercando il PRIMO match che appare nel testo grezzo
        $designatoByTeam = []; // team_norm => ['full' => fullNameNorm, 'reason' => string]

        // Usa prima rigoristiRawMap (contiene il testo "sporco" da cui possiamo estrarre il primo nome reale),
        // altrimenti ripiega sul rigoristiMap (nome già “pulito”).
        $teamsWithData = array_unique(array_merge(array_keys($this->rigoristiRawMap), array_keys($this->rigoristiMap)));

        foreach ($teamsWithData as $team) {
            $teamNorm = $this->normalizeTeam($team);
            if (!$teamNorm || empty($playersByTeam[$teamNorm])) continue;

            // 1) Testo grezzo dove cercare (può contenere più nomi in sequenza)
            $raw = '';
            if (!empty($this->rigoristiRawMap[$teamNorm][0])) {
                $raw = (string)$this->rigoristiRawMap[$teamNorm][0];
            } elseif (!empty($this->rigoristiMap[$teamNorm][0])) {
                // fallback: se abbiamo un singolo nome già pulito
                $raw = (string)$this->rigoristiMap[$teamNorm][0];
            } else {
                continue;
            }

            // Normalizza il testo grezzo per la ricerca con “word boundary”
            $rawNorm = ' ' . $this->normalizeName($raw) . ' ';

            $best = null; // ['full' => ..., 'pos' => int, 'reason' => ...]
            foreach ($playersByTeam[$teamNorm] as $p) {
                $full = ' ' . $p['full'] . ' ';
                $sur  = ' ' . $p['surname'] . ' ';
                $surC = ' ' . $p['surnameComposite'] . ' ';

                // Match 1: nome completo
                $posFull = mb_strpos($rawNorm, $full);
                if ($posFull !== false) {
                    if ($best === null || $posFull < $best['pos']) {
                        $best = ['full' => trim($p['full']), 'pos' => $posFull, 'reason' => 'top1-fullname'];
                    }
                    continue; // full name ha priorità
                }

                // Match 2: cognome composto univoco
                $cntC = $surnameCountByTeam[$teamNorm][$p['surnameComposite']] ?? 0;
                if ($p['surnameComposite'] !== $p['surname'] && $cntC === 1) {
                    $posC = mb_strpos($rawNorm, $surC);
                    if ($posC !== false) {
                        if ($best === null || $posC < $best['pos']) {
                            $best = ['full' => trim($p['full']), 'pos' => $posC, 'reason' => 'top1-surname-composite-unique'];
                        }
                        continue;
                    }
                }

                // Match 3: cognome semplice univoco
                $cnt = $surnameCountByTeam[$teamNorm][$p['surname']] ?? 0;
                if ($cnt === 1) {
                    $posS = mb_strpos($rawNorm, $sur);
                    if ($posS !== false) {
                        if ($best === null || $posS < $best['pos']) {
                            $best = ['full' => trim($p['full']), 'pos' => $posS, 'reason' => 'top1-surname-unique'];
                        }
                    }
                }
            }

            if ($best !== null) {
                $designatoByTeam[$teamNorm] = ['full' => $best['full'], 'reason' => $best['reason']];
                $this->logger->info("[Rigoristi][MATCH] $teamNorm → {$best['full']} ({$best['reason']})");
            } else {
                // Se proprio non trovo nulla, ma ho un singolo nome “pulito”, prova match diretto
                if (!empty($this->rigoristiMap[$teamNorm][0])) {
                    $designatoByTeam[$teamNorm] = ['full' => $this->rigoristiMap[$teamNorm][0], 'reason' => 'direct-top1'];
                    $this->logger->info("[Rigoristi][FALLBACK] $teamNorm → {$this->rigoristiMap[$teamNorm][0]} (direct-top1)");
                }
            }
        }

        // Ora filtra la lista corrente sui designati trovati
        $result = [];
        foreach ($this->listaCorrente as $row) {
            $team = $this->normalizeTeam($row[$teamCol] ?? '');
            if (!$team || empty($designatoByTeam[$team])) continue;

            $playerFull = $this->normalizeName($row['nome_completo'] ?? ($row['nome'] ?? ''));
            if ($playerFull === $designatoByTeam[$team]['full']) {
                $out = $row;
                $out['rigorista_designato'] = 'SI';
                $out['rigorista_match_reason'] = $designatoByTeam[$team]['reason'];
                $result[] = $out;
            }
        }

        // Ordina (quota desc se disponibile, altrimenti per nome)
        if (!empty($result)) {
            if (isset($result[0]['quota_attuale_classic'])) {
                usort($result, function ($a, $b) {
                    return (float)($b['quota_attuale_classic'] ?? 0) <=> (float)($a['quota_attuale_classic'] ?? 0);
                });
            } elseif (isset($result[0]['nome_completo'])) {
                usort($result, fn($a, $b) => strcmp($a['nome_completo'] ?? '', $b['nome_completo'] ?? ''));
            }
        }

        return $result;
    }


    
    public function findCambioRuoloUfficiale(): array
    {
        if (empty($this->listaCorrente) || !isset($this->statistiche[$this->lastYear])) {
            return [];
        }
        
        $lastRoles = [];
        foreach ($this->statistiche[$this->lastYear] as $row) {
            if ($row['id'] !== null) {
                $lastRoles[$row['id']] = $row['ruolo'];
            }
        }
        
        $result = [];
        foreach ($this->listaCorrente as $row) {
            if ($row['id'] !== null && isset($lastRoles[$row['id']])) {
                $curRole = $row['ruolo_classic'];
                $prevRole = $lastRoles[$row['id']];
                
                if ($curRole !== $prevRole && $curRole && $prevRole) {
                    $row['ruolo_classic_attuale'] = $curRole;
                    $row['ruolo_classic_scorsa'] = $prevRole;
                    $result[] = $row;
                }
            }
        }
        
        return array_values($result);
    }
    
    public function findEsordientiAssoluti(): array
    {
        if (empty($this->listaCorrente) || empty($this->statistiche)) {
            return [];
        }
        
        $idsConPresenze = [];
        
        foreach ($this->statistiche as $year => $data) {
            [$startYear, ] = explode('-', $year);
            if ((int)$startYear < 2015) continue;
            
            foreach ($data as $row) {
                if ($row['id'] !== null && $row['presenze'] > 0) {
                    $idsConPresenze[$row['id']] = true;
                }
            }
        }
        
        $result = array_filter($this->listaCorrente, function($row) use ($idsConPresenze) {
            return $row['id'] !== null && !isset($idsConPresenze[$row['id']]);
        });
        
        return array_values($result);
    }
    
    // Metodi di utilità
    private function matchEffectiveNationalityInContinent(string $effectiveNationality, string $continent): bool
    {
        if (empty($effectiveNationality) || !isset($this->continenti[$continent])) {
            return false;
        }
        
        $normalizedPlayerNat = $this->normalizeCountryForMatch($effectiveNationality);
        $targetCountries = $this->continenti[$continent];
        
        foreach ($targetCountries as $country) {
            $normalizedCountry = $this->normalizeCountryForMatch($country);
            if ($normalizedPlayerNat === $normalizedCountry) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isItalianEffectiveNationality(string $effectiveNationality): bool
    {
        if (empty($effectiveNationality)) {
            return false;
        }
        
        $normalized = $this->normalizeCountryForMatch($effectiveNationality);
        return $normalized === $this->normalizeCountryForMatch('Italia');
    }
}