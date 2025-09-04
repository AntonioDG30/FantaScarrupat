<?php
    // **API ENDPOINTS INTERNI**
    if (isset($_GET['api'])) {
        ini_set('display_errors', '0'); 
        header('Content-Type: application/json; charset=utf-8'); 

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
            
            $api = $_GET['api'];
            
            // API 1: GET /api/utenti
            if ($api === 'utenti' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $stmt = $pdo->query("SELECT id_user, username FROM users ORDER BY username");
                $users = $stmt->fetchAll();
                echo json_encode($users);
                exit;
            }
            
            // API 2: GET /api/parametri-disponibili  
            if ($api === 'parametri-disponibili' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $stmt = $pdo->query("
                    SELECT p.id_parametro, p.numero_parametro, p.testo_parametro
                    FROM parametri_rosa p
                    LEFT JOIN user_parametro_assegnato a ON a.id_parametro = p.id_parametro
                    WHERE p.flag_visibile = '1' AND a.id_parametro IS NULL
                    ORDER BY p.numero_parametro
                ");
                $params = $stmt->fetchAll();
                echo json_encode($params);
                exit;
            }
            
            // API 3: POST /api/spin
            if ($api === 'spin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                // Leggi input UNA sola volta e prima di usare le variabili
                $input_raw = file_get_contents('php://input');
                $input = json_decode($input_raw, true) ?? [];

                // Valorizza PRIMA session_start e POI session_id
                $session_start = $input['session_start'] ?? null;
                $session_id = $session_start ?: '';

                // Verifica CSRF
                if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'CSRF token non valido']);
                    exit;
                }

                $id_user = (int)($input['id_user'] ?? 0);
                if ($id_user <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID utente non valido']);
                    exit;
                }
                
                // Lock applicativo per evitare race condition
                $pdo->query("SELECT GET_LOCK('ruota_fortuna', 10)");
                
                try {
                    // Verifica limite 2 parametri per utente
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_parametro_assegnato WHERE id_user = ?");
                    $stmt->execute([$id_user]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count >= 2) {
                        echo json_encode(['error' => 'Utente ha già raggiunto il limite di 2 parametri']);
                        exit;
                    }
                    
                    // Estrai parametro casuale disponibile
                    $stmt = $pdo->query("
                        SELECT p.id_parametro, p.numero_parametro, p.testo_parametro
                        FROM parametri_rosa p
                        LEFT JOIN user_parametro_assegnato a ON a.id_parametro = p.id_parametro
                        WHERE p.flag_visibile = '1' AND a.id_parametro IS NULL
                        ORDER BY RAND() 
                        LIMIT 1
                    ");
                    $parametro = $stmt->fetch();
                    
                    if (!$parametro) {
                        echo json_encode(['error' => 'Nessun parametro disponibile']);
                        exit;
                    }
                    
                    // Tentativo di assegnazione con INSERT IGNORE per sicurezza
                    $attempts = 0;
                    $success = false;
                    
                    while ($attempts < 5 && !$success) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT IGNORE INTO user_parametro_assegnato (id_user, id_parametro, session_id)
                                VALUES (?, ?, ?)
                            ");
                            $result = $stmt->execute([$id_user, $parametro['id_parametro'], $session_id]);

                            
                            if ($stmt->rowCount() > 0) {
                                $success = true;
                                // Risposta SENZA dettagli del parametro (segreto)
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'Parametro assegnato con successo',
                                    'session_start' => $session_start // Passa indietro per tracking
                                ]);
                            } else {
                                // Parametro già assegnato, riprova con altro
                                $attempts++;
                                $stmt = $pdo->query("
                                    SELECT p.id_parametro, p.numero_parametro, p.testo_parametro
                                    FROM parametri_rosa p
                                    LEFT JOIN user_parametro_assegnato a ON a.id_parametro = p.id_parametro
                                    WHERE p.flag_visibile = '1' AND a.id_parametro IS NULL
                                    ORDER BY RAND() 
                                    LIMIT 1
                                ");
                                $parametro = $stmt->fetch();
                                if (!$parametro) break;
                            }
                        } catch (PDOException $e) {
                            $attempts++;
                        }
                    }
                    
                    if (!$success) {
                        echo json_encode(['error' => 'Impossibile assegnare parametro dopo diversi tentativi']);
                    }
                    
                } finally {
                    $pdo->query("SELECT RELEASE_LOCK('ruota_fortuna')");
                }
                exit;
            }
            
            // API 4: POST /api/reset-assegnazioni
            if ($api === 'reset-assegnazioni' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Verifica CSRF
                if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'CSRF token non valido']);
                    exit;
                }
                
                if (($input['conferma'] ?? false) === true) {
                    $pdo->exec("DELETE FROM user_parametro_assegnato");
                    echo json_encode(['success' => true, 'message' => 'Assegnazioni resettate']);
                } else {
                    echo json_encode(['error' => 'Conferma necessaria']);
                }
                exit;
            }
            
            // API 5: GET /api/check-all-full (controlla se tutti i selezionati hanno già 2 parametri)
            if ($api === 'check-all-full' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $user_ids = $input['user_ids'] ?? [];
                
                if (empty($user_ids)) {
                    echo json_encode(['all_full' => false]);
                    exit;
                }
                
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT u.id_user, u.username, COUNT(a.id) as parametri_count
                    FROM users u
                    LEFT JOIN user_parametro_assegnato a ON a.id_user = u.id_user
                    WHERE u.id_user IN ($placeholders)
                    GROUP BY u.id_user
                    HAVING parametri_count < 2
                ");
                $stmt->execute($user_ids);
                $users_with_space = $stmt->fetchAll();
                
                echo json_encode(['all_full' => count($users_with_space) === 0]);
                exit;
            }
            
            // API 6: POST /api/cancel-current-session - cancella solo la sessione corrente
            if ($api === 'cancel-current-session' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);

                // CSRF
                if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'CSRF token non valido']);
                    exit;
                }

                if (($input['conferma'] ?? false) !== true) {
                    echo json_encode(['error' => 'Conferma necessaria']);
                    exit;
                }

                $session_id = $input['session_id'] ?? '';
                if ($session_id === '') {
                    echo json_encode(['error' => 'Sessione non valida']);
                    exit;
                }

                // Lock per evitare race con spin
                $pdo->query("SELECT GET_LOCK('ruota_fortuna', 10)");
                try {
                    $stmt = $pdo->prepare("DELETE FROM user_parametro_assegnato WHERE session_id = ?");
                    $stmt->execute([$session_id]);
                    $deletedRows = $stmt->rowCount();

                    echo json_encode([
                        'success' => true,
                        'message' => "Sessione annullata: {$deletedRows} assegnazioni rimosse",
                        'deleted_count' => $deletedRows
                    ]);
                } finally {
                    $pdo->query("SELECT RELEASE_LOCK('ruota_fortuna')");
                }
                exit;
            }

            
            // API 7: GET /api/users-with-params-count
            if ($api === 'users-with-params-count' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $stmt = $pdo->query("
                    SELECT u.id_user, u.username, COUNT(a.id) as params_count
                    FROM users u
                    LEFT JOIN user_parametro_assegnato a ON a.id_user = u.id_user
                    GROUP BY u.id_user, u.username
                    ORDER BY u.username
                ");
                $users = $stmt->fetchAll();
                echo json_encode($users);
                exit;
            }
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint non trovato']);
            exit;
            
        } catch (PDOException $e) {
            error_log("Ruota Fortuna API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Errore database']);
            exit;
        }
    }

    // Creazione tabella se non esiste
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

        // Crea tabella base (senza affidarsi alla colonna nuova)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_parametro_assegnato` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `id_user` INT NOT NULL,
            `id_parametro` INT NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_parametro_unico` (`id_parametro`),
            KEY `idx_user` (`id_user`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");

        // AGGIUNGI session_id se manca (modo compatibile)
        $col = $pdo->query("SHOW COLUMNS FROM `user_parametro_assegnato` LIKE 'session_id'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE `user_parametro_assegnato` ADD COLUMN `session_id` VARCHAR(64) NOT NULL DEFAULT '' AFTER `id_parametro`");
        }

        // AGGIUNGI indice se manca
        $idx = $pdo->query("SHOW INDEX FROM `user_parametro_assegnato` WHERE Key_name = 'idx_session'")->fetch();
        if (!$idx) {
            $pdo->exec("CREATE INDEX `idx_session` ON `user_parametro_assegnato` (`session_id`)");
        }

    } catch (PDOException $e) {
        error_log("Ruota Fortuna DB Setup Error: " . $e->getMessage());
    }
?>