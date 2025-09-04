<?php
declare(strict_types=1);

// Imposta il fuso orario dell’app
date_default_timezone_set('Europe/Rome');
// opzionale ma utile se il php.ini è “vuoto”
ini_set('date.timezone', 'Europe/Rome');


/**
 * Configurazione principale del sistema di analisi per Fantacalcio.
 * VERSIONE OTTIMIZZATA con cache API pre-calcolate
 */

// Database configuration (Altervista MySQL)
define('DB_HOST', 'localhost');
define('DB_NAME', 'my_fantascarrupat');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// API Configuration
define('FOOTBALL_DATA_API_TOKEN', 'da3c29e703a545fbb1d792bc5e0dfa0c');
define('FD_DELAY_SEC', 1.1);

// Paths
define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('CACHE_PATH', BASE_PATH . '/.cache');

// UI Configuration
define('USE_REACT_MUI', true);

// Season Configuration
define('CURRENT_SEASON', '2025-26');
define('LAST_SEASON', '2024-25');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600);

// Logging
define('LOG_LEVEL', 'INFO');
define('LOG_FILE', BASE_PATH . '/logs/app.log');

// Continenti mapping
define('CONTINENTI', [
    'sudamericani' => [
        'Argentina','Bolivia','Brasile','Cile','Colombia','Ecuador', 'Isola di Pasqua (Rapa Nui)',
        'Isole Galápagos', 'Guyana','Paraguay','Perù','Suriname','Uruguay','Venezuela',
        'Guyana Francese', 'Isole Falkland (Malvine)', 'Georgia del Sud e Isole Sandwich Australi'
    ],

    'africani' => [
        'Algeria','Angola','Benin','Botswana','Burkina Faso','Burundi','Capo Verde',
        'Camerun','Repubblica Centrafricana','Ciad','Comore','Repubblica Democratica del Congo',
        'Repubblica del Congo',"Costa d'Avorio",'Gibuti','Egitto','Guinea Equatoriale','Eritrea',
        'Eswatini','Etiopia','Gabon','Gambia','Ghana','Guinea','Guinea-Bissau','Kenya','Lesotho',
        'Liberia','Libia','Madagascar','Malawi','Mali','Mauritania','Mauritius','Marocco',
        'Mozambico','Namibia','Niger','Nigeria','Ruanda','São Tomé e Príncipe','Senegal',
        'Seychelles','Sierra Leone','Somalia','Sudafrica','Sudan','Sudan del Sud','Tanzania',
        'Togo','Tunisia','Uganda','Zambia','Zimbabwe', 'Sahara Occidentale', 'Mayotte','Riunione',
        "Sant'Elena, Ascensione e Tristan da Cunha", 'Territorio Britannico dell’Oceano Indiano',
        'Ceuta','Melilla', 'Isole Canarie', 'Madeira', 'Isole Selvagge (Selvagens)',
        'Isole Chafarinas', 'Peñón de Alhucemas', 'Peñón de Vélez de la Gomera',
        'Îles Éparses dell\'Oceano Indiano (Bassas da India, Europa, Juan de Nova, Gloriose, Tromelin)',
        'Isole Crozet', 'Isole Kerguelen', 'Isole Saint-Paul e Amsterdam', 'Isole Principe Edoardo'
    ],

    'europei' => [
        'Albania','Andorra','Armenia','Austria','Azerbaigian','Belgio','Bielorussia',
        'Bosnia ed Erzegovina','Bulgaria','Cipro','Croazia','Danimarca','Estonia','Finlandia',
        'Francia','Georgia','Germania','Grecia','Irlanda','Islanda','Italia','Kazakhstan','Kosovo',
        'Lettonia','Liechtenstein','Lituania','Lussemburgo','Macedonia del Nord','Malta','Moldova',
        'Monaco','Montenegro','Norvegia','Paesi Bassi','Polonia','Portogallo','Regno Unito',
        'Repubblica Ceca','Romania','Russia','San Marino','Serbia','Slovacchia','Slovenia',
        'Spagna','Svezia','Svizzera','Turchia','Ucraina','Ungheria','Vaticano',
        'Cipro', 'Turchia', 'Russia', 'Armenia', 'Azerbaigian', 'Georgia', 'Kazakhstan',
        'Gibilterra', 'Isole Faroe', 'Åland', 'Jersey','Guernsey', 'Isola di Man', 'Svalbard e Jan Mayen',
        'Inghilterra', 'Scozia', 'Galles', 'Irlanda del Nord', 'Azzorre', 'Alderney', 'Sark',
        'Herm', 'Isole Orcadi', 'Isole Shetland', 'Isola di Wight', 'Isole Scilly'
    ],
]);

// Criterion detail columns mapping 
define('CRITERION_DETAIL_COLS', [
    '1'  => ['eta','data_nascita'],
    '2'  => ['eta','data_nascita'],
    '3'  => [],
    '4'  => ['presenze_totali'],
    '5'  => ['nazionalita_effettiva'], // SOLO nazionalità effettiva
    '6'  => ['nazionalita_effettiva'], // SOLO nazionalità effettiva
    '7'  => ['nazionalita_effettiva'], // SOLO nazionalità effettiva
    '8'  => [],
    '9'  => [],
    '10' => ['GA_scorsa_squadra'],
    '11' => ['gol_scorsa_stagione'],
    '12' => ['assist_scorsa_stagione'],
    '13' => ['gol_scorsa_stagione'],
    '14' => ['presenze_scorsa_stagione'],
    '15' => ['media_voto'],
    '16' => [],
    '17' => [],
    '18' => ['gol_scorsa_stagione'],
    '19' => ['ultima_stagione', 'anni_assenza'],
    '20' => ['Numero_SV'],
    '21' => ['ammonizioni_scorsa_stagione'],
    '22' => ['squadra_scorsa','squadra_attuale'],
    '23' => ['autogol_scorsa_stagione'],
    '24' => ['presenze_scorsa_stagione'],
    '25' => ['rigori_sbagliati'],
    '26' => ['presenze_scorsa_stagione'],
    '27' => [],
    '28' => ['gol_scorsa_stagione','assist_scorsa_stagione'],
    '29' => ['cartellini_per_presenza'],
    '30' => ['rigorista_designato','rigorista_match_reason'],
    '31' => ['ruolo_classic_scorsa', 'ruolo_classic_attuale'],
    '32' => []
]);

// **NUOVE CONFIGURAZIONI PER CACHE API**
// Timeout per chiamate API (secondi)
define('API_TIMEOUT', 30);

// Numero massimo di retry per chiamate API
define('API_MAX_RETRIES', 3);

// Delay tra chiamate API (microsecondi)
define('API_DELAY_MICROSECONDS', 500000); // 0.5 secondi

// Flag per abilitare/disabilitare cache API
define('ENABLE_API_CACHE', true);

// Durata cache API in sessione (secondi)
define('API_CACHE_DURATION', 3600); // 1 ora

// Squadre hardcoded per fallback rigoristi (stagione 2024-25)
define('RIGORISTI_FALLBACK', [
    'Inter' => ['Çalhanoglu'],
    'Milan' => ['Pulisic'],
    'Juventus' => ['Vlahovic'],
    'Napoli' => ['Kvaratskhelia'],
    'Roma' => ['Dybala'],
    'Lazio' => ['Immobile'],
    'Atalanta' => ['Lookman'],
    'Fiorentina' => ['Kean'],
    'Bologna' => ['Orsolini'],
    'Torino' => ['Zapata'],
    'Udinese' => ['Thauvin'],
    'Sassuolo' => ['Berardi'],
    'Empoli' => ['Colombo'],
    'Verona' => ['Tengstedt'],
    'Cagliari' => ['Piccoli'],
    'Lecce' => ['Krstovic'],
    'Parma' => ['Man'],
    'Como' => ['Cutrone'],
    'Venezia' => ['Pohjanpalo'],
    'Monza' => ['Djuric']
]);

// **CONFIGURAZIONI DI PERFORMANCE**
// Abilita debug delle performance
define('PERFORMANCE_DEBUG', false);

// === DEBUG E PERFORMANCE ===
define('DEBUG', $_ENV['DEBUG'] ?? false); // true per abilitare debug query
define('CACHE_ENABLED', $_ENV['CACHE_ENABLED'] ?? true);
define('QUERY_LOG_ENABLED', $_ENV['QUERY_LOG_ENABLED'] ?? DEBUG);
define('PERFORMANCE_MONITORING', $_ENV['PERFORMANCE_MONITORING'] ?? false);

// === PAGINAZIONE ===
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// === EXPORT SETTINGS ===
define('EXPORT_MAX_ROWS', 5000);
define('EXPORT_TIMEOUT_SECONDS', 60);

// Limite memoria PHP (se necessario aumentare)
// ini_set('memory_limit', '256M');

// Timeout di esecuzione (se necessario aumentare)  
// ini_set('max_execution_time', 300); // 5 minuti

/**
 * Funzioni di utilità per la configurazione
 */

/**
 * Controlla se le cache API sono abilitate
 */
function isApiCacheEnabled(): bool 
{
    return defined('ENABLE_API_CACHE') && ENABLE_API_CACHE === true;
}

/**
 * Ottiene il timeout per le chiamate API
 */
function getApiTimeout(): int
{
    return defined('API_TIMEOUT') ? API_TIMEOUT : 30;
}

/**
 * Ottiene i rigoristi di fallback per una squadra
 */
function getFallbackRigoristi(string $team): array
{
    $fallback = defined('RIGORISTI_FALLBACK') ? RIGORISTI_FALLBACK : [];
    return $fallback[$team] ?? [];
}

/**
 * Log delle performance se abilitato
 */
function logPerformance(string $operation, float $startTime): void
{
    if (defined('PERFORMANCE_DEBUG') && PERFORMANCE_DEBUG) {
        $duration = microtime(true) - $startTime;
        error_log("[PERFORMANCE] $operation: " . round($duration * 1000, 2) . "ms");
    }
}

if (!defined('CACHE_TTL_SECONDS')) {
    define('CACHE_TTL_SECONDS', 3600 * 24); // 24 ore
}

/**
 * Ottiene il percorso base del progetto per costruire URL relativi
 */
if (!function_exists('getProjectBasePath')) {
    function getProjectBasePath(): string 
    {
        // Determina il percorso base in base alla struttura del progetto
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Estrae il percorso della directory base
        $basePath = dirname($scriptPath);
        
        // Normalizza il percorso (rimuove doppi slash, etc.)
        $basePath = rtrim($basePath, '/');
        
        // Se siamo nella root, restituisce una stringa vuota
        if ($basePath === '.' || $basePath === '') {
            return '';
        }
        
        // Altrimenti restituisce il percorso con slash finale
        return $basePath . '/';
    }
}

/**
 * Costruisce un URL relativo al progetto
 */
if (!function_exists('url')) {
    function url(string $path = ''): string 
    {
        $basePath = getProjectBasePath();
        $path = ltrim($path, '/');
        
        return $basePath . $path;
    }
}

/**
 * Verifica se una richiesta è AJAX
 */
if (!function_exists('isAjaxRequest')) {
    function isAjaxRequest(): bool 
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

/**
 * Sanitizza una stringa per l'output HTML
 */
if (!function_exists('e')) {
    function e(string $value): string 
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

/**
 * Ottiene un valore dall'array con fallback
 */
if (!function_exists('array_get')) {
    function array_get(array $array, string $key, mixed $default = null): mixed 
    {
        return $array[$key] ?? $default;
    }
}

/**
 * Log di debug condizionale
 */
if (!function_exists('debug_log')) {
    function debug_log(string $message, array $context = []): void 
    {
        if (defined('DEBUG') && DEBUG) {
            $contextStr = empty($context) ? '' : ' ' . json_encode($context);
            error_log("[DEBUG] $message$contextStr");
        }
    }
}


// === SESSIONI SICURE ===
define('SESSION_IDLE_TTL', 900);        // 15 minuti di inattività
define('SESSION_ABSOLUTE_TTL', 28800);   // 8 ore di durata massima
define('SESSION_REGEN_INTERVAL', 300);  // 5 minuti per rigenerazione ID
define('SESSION_STRICT_UA', true);      // Verifica user-agent
define('SESSION_SECURE_COOKIE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

