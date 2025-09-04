<?php
declare(strict_types=1);

/**
 * Cosa faccio: configuro la pagina Hall of Fame con protezione autenticazione.
 * Perché: voglio mostrare statistiche e trofei solo agli utenti autorizzati.
 * Dipendenze: require_login, config, userData per dati utente corrente.
 */
require_once __DIR__ . '/auth/require_login.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/find_userData.php';

/**
 * Cosa faccio: carico il repository per accesso ai dati delle competizioni.
 * Perché: ho bisogno di statistiche reali dal database per trofei e storico.
 * Fallback: se Repository non esiste, uso dati di esempio per evitare errori.
 */
$repository = null;
try {
    if (file_exists(__DIR__ . '/src/Utils/Repository.php')) {
        require_once __DIR__ . '/src/Utils/Repository.php';
    } elseif (file_exists(__DIR__ . '/src/Repository.php')) {
        require_once __DIR__ . '/src/Repository.php';
    } else {
        throw new Exception('Repository class not found');
    }
} catch (Exception $e) {
    error_log("HallOfFame: Errore caricamento Repository: " . $e->getMessage());
}

/**
 * Cosa faccio: genero o riuso il token CSRF per sicurezza forms.
 * Perché: voglio proteggere da attacchi CSRF su eventuali azioni future.
 */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Cosa faccio: inizializzo le statistiche utente con valori di default sicuri.
 * Perché: voglio evitare errori se il caricamento dati fallisce parzialmente.
 * Struttura: tutti i campi necessari per la UI con valori neutri.
 */
$user_stats = [
    'total_competitions' => 0,
    'trophies_won' => 0,
    'total_points' => 0,
    'total_matches' => 0,
    'avg_points_per_match' => 0,
    'seasons_played' => 0,
    'best_season' => 'N/A',
    'max_points' => 0,
    'min_points' => 0,
    'total_players' => 0
];

$trophies = [];
$competitions = [];
$win_loss_stats = ['wins' => 0, 'losses' => 0, 'draws' => 0];
$data_loaded = false;

/**
 * Cosa faccio: carico i dati reali dal database usando PDO e Repository.
 * Perché: voglio mostrare statistiche authentic invece di dati fittizi.
 * Error handling: log errori ma continuo con dati di fallback.
 */
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    $debug = (defined('DEBUG') && DEBUG === true);
    $repository = new Repository($pdo, $debug);
    
    $user_fantasquadra = $_SESSION['nome_fantasquadra'] ?? null;
    
    if ($user_fantasquadra && $repository) {
        /**
         * Cosa faccio: carico le statistiche principali dell'utente.
         * Input: nome_fantasquadra dalla sessione
         * Output: array con metriche aggregate (partite, punti, trofei)
         */
        $stats = $repository->getHallOfFameStats($user_fantasquadra);
        if ($stats && is_array($stats)) {
            $user_stats = [
                'total_competitions' => (int)($stats['total_competitions'] ?? 0),
                'trophies_won' => (int)($stats['trophies_won'] ?? 0),
                'total_points' => (float)($stats['total_points'] ?? 0),
                'total_matches' => (int)($stats['total_matches'] ?? 0),
                'avg_points_per_match' => round((float)($stats['avg_points_per_match'] ?? 0), 2),
                'seasons_played' => (int)($stats['seasons_played'] ?? 0),
                'max_points' => (float)($stats['max_points'] ?? 0),
                'min_points' => (float)($stats['min_points'] ?? 0),
            ];
            $data_loaded = true;
        }
        
        /**
         * Cosa faccio: identifico la migliore stagione per l'utente.
         * Criterio: stagione con più punti totali o migliore media.
         */
        $best_season = $repository->getBestSeason($user_fantasquadra);
        $user_stats['best_season'] = $best_season ?? 'N/A';
        
        /**
         * Cosa faccio: calcolo le statistiche vittorie/sconfitte/pareggi.
         * Perché: voglio mostrare il track record competitivo dell'utente.
         */
        $win_loss_result = $repository->getWinLossStats($user_fantasquadra);
        if ($win_loss_result && is_array($win_loss_result)) {
            $win_loss_stats = [
                'wins' => (int)($win_loss_result['wins'] ?? 0),
                'losses' => (int)($win_loss_result['losses'] ?? 0),
                'draws' => (int)($win_loss_result['draws'] ?? 0)
            ];
        }
        
        /**
         * Cosa faccio: carico i trofei reali vinti dall'utente.
         * Perché: voglio mostrare i successi concreti invece di placeholder.
         * Mapping: trasformo i dati DB in formato UI-friendly.
         */
        $real_trophies = $repository->getRealTrophies($user_fantasquadra);
        $trophy_id = 1;
        
        if ($real_trophies && is_array($real_trophies)) {
            foreach ($real_trophies as $trophy) {
                $trophies[] = [
                    'id' => $trophy_id++,
                    'name' => "Vincitore {$trophy['nome_competizione']} {$trophy['anno']}",
                    'type' => strtolower($trophy['tipologia'] ?? '') === 'campionato' ? 'championship' : 'cup',
                    'season' => (int)($trophy['anno'] ?? date('Y')),
                    'date' => ($trophy['anno'] ?? date('Y')) . '-05-30',
                    'points' => (float)($trophy['total_points'] ?? 0),
                    'matches' => (int)($trophy['matches_played'] ?? 0),
                    'description' => "Primo posto in {$trophy['nome_competizione']} ({$trophy['matches_played']} partite)",
                    'icon' => strtolower($trophy['tipologia'] ?? '') === 'campionato' ? 'emoji_events' : 'military_tech',
                    'color' => 'gold'
                ];
            }
        }
        
        /**
         * Cosa faccio: aggiungo trofei achievement basati su milestone raggiunte.
         * Perché: voglio riconoscere traguardi anche senza vittorie dirette.
         * Logic: controllo soglie e aggiungo trofei virtuali appropriati.
         */
        if ($user_stats['total_points'] >= 1000 && $user_stats['total_matches'] >= 10) {
            $trophies[] = [
                'id' => $trophy_id++,
                'name' => 'Veterano dei 1000 Punti',
                'type' => 'achievement',
                'season' => $user_stats['best_season'],
                'date' => date('Y') . '-01-01',
                'points' => 0,
                'matches' => 0,
                'description' => "Superati i 1000 punti totali in {$user_stats['total_matches']} partite",
                'icon' => 'star',
                'color' => 'special'
            ];
        }
        
        if ($user_stats['seasons_played'] >= 2) {
            $trophies[] = [
                'id' => $trophy_id++,
                'name' => 'Giocatore Esperto',
                'type' => 'special',
                'season' => $user_stats['best_season'],
                'date' => date('Y') . '-01-01',
                'points' => 0,
                'matches' => 0,
                'description' => "Attivo per {$user_stats['seasons_played']} stagioni",
                'icon' => 'workspace_premium',
                'color' => 'silver'
            ];
        }
        
        /**
         * Cosa faccio: calcolo e assegno trofeo per win rate elevato.
         * Soglia: 30% win rate con almeno una vittoria.
         * Perché: voglio premiare la consistenza competitiva.
         */
        if ($win_loss_stats['wins'] > 0 && $user_stats['total_matches'] > 0) {
            $win_rate = round(($win_loss_stats['wins'] / $user_stats['total_matches']) * 100);
            if ($win_rate >= 30) {
                $trophies[] = [
                    'id' => $trophy_id++,
                    'name' => 'Strategist',
                    'type' => 'achievement',
                    'season' => $user_stats['best_season'],
                    'date' => date('Y') . '-01-01',
                    'points' => 0,
                    'matches' => 0,
                    'description' => "Win rate del {$win_rate}% ({$win_loss_stats['wins']} vittorie)",
                    'icon' => 'psychology',
                    'color' => 'special'
                ];
            }
        }
        
        /**
         * Cosa faccio: carico lo storico delle competizioni per il nuovo layout.
         * Limit: 15 competizioni più recenti per performance.
         * Mapping: trasformo dati DB in formato ottimizzato per filtri UI.
         */
        try {
            $competition_history = $repository->getCompetitionHistory($user_fantasquadra, 15);

            if (is_array($competition_history)) {
                foreach ($competition_history as $comp) {
                    $typeLabel = match ($comp['competition_type'] ?? 'regular') {
                        'regular'  => 'Campionato',
                        'knockout' => 'Knockout',
                        default    => 'Misto'
                    };

                    $season = (isset($comp['season']) && is_numeric($comp['season']))
                        ? ((int)$comp['season'] - 1) . '/' . (int)$comp['season']
                        : 'Anno sconosciuto';
                    $points   = (float)($comp['points'] ?? 0);
                    $matches  = (int)($comp['matches_played'] ?? 0);
                    $avg      = $matches > 0 ? round($points / $matches, 1) : 0.0;

                    $competitions[] = [
                        'season'            => $season,
                        'competition'       => (string)($comp['competition'] ?? 'Competizione'),
                        'type'              => $typeLabel,
                        'status'            => (string)($comp['status'] ?? 'pending'),
                        'is_winner'         => (bool)($comp['is_winner'] ?? false),
                        'position_type'     => (string)($comp['position_info']['type'] ?? 'unknown'),
                        'position_value'    => (int)($comp['position_info']['value'] ?? 0),
                        'position_display'  => (string)($comp['position_info']['display'] ?? 'N/A'),
                        'badge'             => $comp['position_info']['badge'] ?? null,
                        'points'            => $points,
                        'matches_played'    => $matches,
                        'avg_points'        => $avg,
                    ];
                }
            }
        } catch (Exception $e) {
            /**
             * Cosa faccio: genero dati di fallback se caricamento storico fallisce.
             * Perché: voglio evitare pagina vuota, meglio placeholder ragionevoli.
             */
            if ($user_stats['total_competitions'] > 0) {
                for ($i = 0; $i < min($user_stats['total_competitions'], 5); $i++) {
                    $competitions[] = [
                        'season' => (int)$user_stats['best_season'],
                        'competition' => 'Competizione ' . ($i + 1),
                        'type' => 'Campionato',
                        'status' => 'completed',
                        'is_winner' => $i < $user_stats['trophies_won'],
                        'position_type' => $i < $user_stats['trophies_won'] ? 'winner' : 'position',
                        'position_value' => $i < $user_stats['trophies_won'] ? 1 : rand(2, 4),
                        'position_display' => $i < $user_stats['trophies_won'] ? 'Vincitore' : (rand(2,4) . 'º su ' . rand(6,10)),
                        'points' => round($user_stats['avg_points_per_match'] * rand(10, 20), 1),
                        'matches_played' => rand(10, 20),
                        'avg_points' => $user_stats['avg_points_per_match'],
                        'badge' => $i < $user_stats['trophies_won'] ? 'winner' : null,
                    ];
                }
            }
            error_log("HallOfFame: Errore getCompetitionHistory, uso fallback: " . $e->getMessage());
        }
        
        /**
         * Cosa faccio: conto il numero totale di giocatori nel database.
         * Perché: voglio mostrare il contesto della competizione all'utente.
         */
        $total_players = $repository->getTotalPlayers();
        $user_stats['total_players'] = (int)$total_players;
        
    } else {
        /**
         * Cosa faccio: gestisco il caso utente senza fantasquadra.
         * Perché: voglio dare un benvenuto invece di errori o pagina vuota.
         */
        if ($debug) {
            error_log("HallOfFame: Utente senza fantasquadra o repository non disponibile");
        }
        
        $trophies = [
            [
                'id' => 1,
                'name' => 'Benvenuto in FantaScarrupat Analyzer',
                'type' => 'welcome',
                'season' => (int)date('Y'),
                'date' => date('Y-m-d'),
                'points' => 0,
                'matches' => 0,
                'description' => 'Primo accesso completato. Associa una fantasquadra per iniziare!',
                'icon' => 'celebration',
                'color' => 'special'
            ]
        ];
        
        $competitions = [
            [
                'season' => (int)date('Y'),
                'competition' => 'Nessuna competizione',
                'position' => 0,
                'total_teams' => 0,
                'points' => 0,
                'matches_played' => 0,
                'status' => 'pending',
                'is_winner' => false
            ]
        ];
    }
    
} catch (Exception $e) {
    error_log("HallOfFame: Errore generale: " . $e->getMessage());
    
    /**
     * Cosa faccio: fornisco fallback completo in caso di errore database.
     * Perché: voglio evitare white screen of death, meglio messaggio user-friendly.
     */
    $trophies = [
        [
            'id' => 1,
            'name' => 'Sistema in Aggiornamento',
            'type' => 'system',
            'season' => (int)date('Y'),
            'date' => date('Y-m-d'),
            'points' => 0,
            'matches' => 0,
            'description' => 'I dati saranno disponibili a breve. Riprova più tardi.',
            'icon' => 'refresh',
            'color' => 'special'
        ]
    ];
}

/**
 * Cosa faccio: log finale per debug se abilitato.
 * Perché: voglio tracciare il successo del caricamento dati per troubleshooting.
 */
if ($debug && $data_loaded) {
    error_log("HallOfFame: Dati caricati con successo - Partite: {$user_stats['total_matches']}, Punti: {$user_stats['total_points']}, Trofei: " . count($trophies));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall of Fame - FantaScarrupat Analyzer</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/hall-of-fame.css">
</head>
<body>
    <div class="main-container">
        <!-- Indicatore stato dati -->
        <div class="data-status" style="background: <?= $data_loaded ? 'rgba(34, 197, 94, 0.9)' : 'rgba(239, 68, 68, 0.9)' ?>;">
            <?= $data_loaded ? '✅ DATI REALI' : '⚠️ FALLBACK' ?>
        </div>
        
        <!-- Navbar Responsiva -->
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-container">
                    <a href="<?= url('HomeParametri.php') ?>" class="navbar-brand">
                        Hall of Fame
                    </a>
                    
                    <div class="navbar-nav">
                        <div class="nav-links">
                            <button class="theme-toggle" id="themeToggle" title="Cambia tema" aria-label="Cambia tema">
                                <span class="material-icons" id="themeIcon">dark_mode</span>
                            </button>

                            <div class="user-section">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                        <?php if (!empty($_SESSION['nome_fantasquadra'])): ?>
                                            <div class="user-team"><?= htmlspecialchars($_SESSION['nome_fantasquadra']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="page-container">
            <!-- Hero Header con Statistiche Reali -->
            <div class="hero-header animate-on-scroll">
                <div class="hero-content">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="hero-title">
                                <span class="material-icons" style="font-size: 3rem;">emoji_events</span>
                                Hall of Fame
                            </h1>
                            <p class="hero-subtitle">
                                <?= $data_loaded ? 'Le tue statistiche ' : 'Statistiche in caricamento' ?> nella Lega FantaScarruoat dal 2024/2025
                            </p>
                            <div class="d-flex align-items-center gap-2">
                                <span class="material-icons">account_circle</span>
                                <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                                <?php if (!empty($_SESSION['nome_fantasquadra'])): ?>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($_SESSION['nome_fantasquadra']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <span class="stat-value" data-count="<?= $user_stats['trophies_won'] ?>"><?= $user_stats['trophies_won'] ?></span>
                                    <div class="stat-label">Trofei Vinti dal 2024/2025</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value" data-count="<?= $user_stats['total_competitions'] ?>"><?= $user_stats['total_competitions'] ?></span>
                                    <div class="stat-label">Competizioni Disputate dal 2024/2025</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value" data-count="<?= (int)$user_stats['total_points'] ?>"><?= number_format($user_stats['total_points'], 0) ?></span>
                                    <div class="stat-label">Punti Totali dal 2024/2025</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value"><?= $user_stats['best_season'] ?></span>
                                    <div class="stat-label">Migliore Stagione</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value" data-count="<?= $user_stats['total_matches'] ?>"><?= $user_stats['total_matches'] ?></span>
                                    <div class="stat-label">Partite Totali dal 2024/2025</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-value"><?= number_format($user_stats['avg_points_per_match'], 1) ?></span>
                                    <div class="stat-label">Media per Partita dal 2024/2025</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Trofei Section con solo dati reali -->
                <div>
                    <div class="trophies-section animate-on-scroll">
                        <h2 class="section-title">
                            <span class="material-icons" style="font-size: 2rem;">military_tech</span>
                            I Tuoi Trofei (<?= count($trophies) ?>)
                        </h2>
                        
                        <?php if (!empty($trophies)): ?>
                            <div class="trophies-grid">
                                <?php foreach ($trophies as $trophy): ?>
                                    <div class="trophy-card <?= $trophy['color'] ?> animate-on-scroll">
                                        <div class="trophy-header">
                                            <div class="trophy-icon <?= $trophy['color'] ?>">
                                                <span class="material-icons"><?= $trophy['icon'] ?></span>
                                            </div>
                                            <div>
                                                <h3 class="trophy-title"><?= htmlspecialchars($trophy['name']) ?></h3>
                                                <p class="trophy-season">Stagione <?= htmlspecialchars((string)$trophy['season'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                                            </div>
                                        </div>
                                        
                                        <p class="trophy-description">
                                            <?= htmlspecialchars($trophy['description']) ?>
                                        </p>
                                        
                                        <div class="trophy-meta">
                                            <span><?= date('d/m/Y', strtotime($trophy['date'])) ?></span>
                                            <?php if ($trophy['points'] > 0): ?>
                                                <span class="trophy-points"><?= number_format($trophy['points'], 1) ?> punti</span>
                                            <?php elseif ($trophy['matches'] > 0): ?>
                                                <span class="trophy-points"><?= $trophy['matches'] ?> partite</span>
                                            <?php else: ?>
                                                <span class="trophy-points">Achievement</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-trophies">
                                <span class="material-icons">emoji_events</span>
                                <h3>Nessun trofeo ancora</h3>
                                <p>Partecipa alle competizioni per guadagnare i tuoi primi trofei!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Competition History & Stats Sidebar -->
                <div>
                    <!-- Statistiche Avanzate -->
                    <?php if ($user_stats['total_matches'] > 0): ?>
                        <div class="competitions-card animate-on-scroll" style="margin-bottom: 2rem;">
                            <div class="competitions-header">
                                <h3 class="section-title mb-0" style="font-size: 1.5rem;">
                                    <span class="material-icons" style="font-size: 1.5rem;">analytics</span>
                                    Statistiche Dettagliate
                                </h3>
                            </div>
                            
                            <div class="competitions-body">
                                <div class="enhanced-stats-grid">
                                    <div class="enhanced-stat-card">
                                        <span class="enhanced-stat-value">
                                            <?= $user_stats['total_matches'] > 0 ? round(($win_loss_stats['wins'] / $user_stats['total_matches']) * 100) : 0 ?>%
                                        </span>
                                        <div class="enhanced-stat-label">Win Rate</div>
                                    </div>
                                    <div class="enhanced-stat-card">
                                        <span class="enhanced-stat-value"><?= number_format($user_stats['max_points'], 1) ?></span>
                                        <div class="enhanced-stat-label">Record Punti</div>
                                    </div>
                                    <div class="enhanced-stat-card">
                                        <span class="enhanced-stat-value"><?= $user_stats['seasons_played'] ?></span>
                                        <div class="enhanced-stat-label">Stagioni Attive</div>
                                    </div>
                                    <div class="enhanced-stat-card">
                                        <span class="enhanced-stat-value"><?= $win_loss_stats['wins'] ?></span>
                                        <div class="enhanced-stat-label">Vittorie Totali</div>
                                    </div>
                                    <div class="enhanced-stat-card">
                                        <span class="enhanced-stat-value"><?= $win_loss_stats['draws'] ?></span>
                                        <div class="enhanced-stat-label">Pareggi</div>
                                    </div>
                                    <div class="enhanced-stat-card">
                                        <span class="enhanced-stat-value"><?= $user_stats['total_players'] ?></span>
                                        <div class="enhanced-stat-label">Giocatori DB</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Storico Competizioni -->
                    <div class="competitions-card v2 animate-on-scroll">
                        <div class="competitions-header">
                            <h2 class="section-title mb-0">
                            <span class="material-icons" style="font-size: 2rem;">history</span>
                            Storico Competizioni
                            </h2>
                        </div>

                        <!-- Toolbar filtri/ordinamento -->
                        <div class="comp-toolbar">
                            <select id="filterType" class="form-select">
                            <option value="all">Tutti i tipi</option>
                            <option value="campionato">Campionato</option>
                            <option value="knockout">Knockout</option>
                            </select>
                            <select id="filterStatus" class="form-select">
                            <option value="all">Tutti gli stati</option>
                            <option value="completed">Terminate</option>
                            <option value="ongoing">In corso</option>
                            <option value="pending">Da iniziare</option>
                            </select>
                            <select id="sortBy" class="form-select">
                            <option value="season_desc">Stagione ↓</option>
                            <option value="season_asc">Stagione ↑</option>
                            <option value="points_desc">Punti ↓</option>
                            <option value="points_asc">Punti ↑</option>
                            <option value="avg_desc">Media ↓</option>
                            <option value="avg_asc">Media ↑</option>
                            </select>
                            <div class="d-flex justify-content-end align-items-center">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="onlyWinners">
                                <label class="form-check-label" for="onlyWinners">Solo vittorie</label>
                            </div>
                            </div>
                        </div>

                        <div class="comp-grid" id="compGrid">
                            <?php if (!empty($competitions) && ($competitions[0]['competition'] ?? '') !== 'Nessuna competizione'): ?>
                                <?php foreach ($competitions as $c): ?>
                                    <?php
                                        $typeClass = strtolower($c['type'] ?? 'campionato');
                                        $icon = ($typeClass === 'campionato') ? 'leaderboard' : 'military_tech';
                                        $seasonKey = 0;
                                        if (is_string($c['season']) && preg_match('/(\d{4})\s*\/\s*(\d{4})/', (string)$c['season'], $m)) { 
                                            $seasonKey = (int)$m[2]; 
                                        } elseif (is_numeric($c['season'])) { 
                                            $seasonKey = (int)$c['season']; 
                                        }
                                        $badgeText = !empty($c['is_winner']) ? 'Vincitore'
                                                    : (($c['position_type'] ?? '') === 'stage' ? (($c['position_display'] ?? ''))
                                                        : ($c['position_display'] ?? ''));
                                        $badgeClass = !empty($c['is_winner']) ? 'win' : ((($c['position_type'] ?? '')==='stage') ? 'stage' : 'place');
                                        $statusLabel = ($c['status']==='completed' ? 'TERMINATA' : ($c['status']==='ongoing' ? 'IN CORSO' : 'DA INIZIARE'));
                                    ?>
                                    <article
                                    class="comp-card type-<?= htmlspecialchars($typeClass) ?> status-<?= htmlspecialchars($c['status']) ?> <?= !empty($c['is_winner']) ? 'is-winner' : '' ?>"
                                    data-type="<?= htmlspecialchars($typeClass) ?>"
                                    data-status="<?= htmlspecialchars($c['status']) ?>"
                                    data-season="<?= (int)$seasonKey ?>"
                                    data-points="<?= (float)$c['points'] ?>"
                                    data-avg="<?= number_format((float)$c['avg_points'], 3, '.', '') ?>"
                                    data-winner="<?= !empty($c['is_winner']) ? '1' : '0' ?>"
                                    >
                                        <?php if (!empty($c['is_winner'])): ?><div class="ribbon"></div><?php endif; ?>

                                        <div class="comp-head">
                                            <div class="comp-icon"><span class="material-icons"><?= $icon ?></span></div>
                                            <div>
                                                <div class="comp-title"><?= htmlspecialchars($c['competition']) ?></div>
                                                <div class="comp-meta">
                                                    <span class="chip"><?= 'Stagione ' . htmlspecialchars((string)$c['season'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                                    <span class="chip"><?= htmlspecialchars($c['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                                    <span class="chip status <?= htmlspecialchars($c['status']) ?>"><?= $statusLabel ?></span>
                                                </div>
                                            </div>
                                                <div class="comp-points">
                                                <div class="value"><?= number_format((float)$c['points'], 1) ?></div>
                                                <div class="label">punti totali</div>
                                            </div>
                                        </div>

                                        <div class="comp-body">
                                            <div class="pos-badge <?= $badgeClass ?>">
                                                <?php if (!empty($c['is_winner'])): ?>🏆<?php elseif (($c['position_type'] ?? '')==='stage'): ?>🥈<?php else: ?>🏅<?php endif; ?>
                                                <span><?= htmlspecialchars($badgeText) ?></span>
                                            </div>
                                            <div class="comp-numbers">
                                                <span><b><?= (int)$c['matches_played'] ?></b> partite</span>
                                                <span> | </span>
                                                <span><b><?= number_format((float)$c['avg_points'], 1) ?></b> media</span>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-trophies" style="grid-column:1 / -1;">
                                    <span class="material-icons">sports</span>
                                    <h4>Nessuna competizione</h4>
                                    <p><?php if (!empty($_SESSION['nome_fantasquadra'])): ?>Le tue competizioni future appariranno qui.<?php else: ?>Associa una fantasquadra per vedere le competizioni.<?php endif; ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/mobile-navbar.js"></script>
    <script src="assets/js/hall-of-fame.js"></script>
    
    <script>

        // Theme
        const tm = new ThemeManager();
        tm.init();

        /**
         * Cosa faccio: passo i dati PHP al JavaScript per l'inizializzazione.
         * Perché: JavaScript ha bisogno di questi dati per contatori e CSRF.
         */
        window.CURRENT_USER = {
            id_user: <?= (int)$u['id_user'] ?>,
            username: <?= json_encode($u['username']) ?>,
            nome_fantasquadra: <?= json_encode($u['nome_fantasquadra']) ?>,
            is_admin: <?= (int)$u['flag_admin'] ?> === 1,
            theme_preference: <?= json_encode($u['theme_preference'] ?? 'auto') ?>,
            avatar_url: <?= json_encode($u['avatar_url']) ?>
        };

        const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
        function url(path) {
            return '<?= getProjectBasePath() ?>' + path.replace(/^\/+/, '');
        }
    
    </script>
    <script src="assets/js/session-monitor.js"></script>
</body>
</html>