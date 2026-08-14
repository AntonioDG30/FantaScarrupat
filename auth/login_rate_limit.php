<?php
/**
 * auth/login_rate_limit.php — Rate limiting persistente dei tentativi di login.
 *
 * Complementa il blocco basato su sessione di login.php: poiché il contatore di
 * sessione è aggirabile scartando il cookie, qui registriamo i tentativi falliti
 * su DB, indicizzati per HASH dell'IP (pseudonimizzato → GDPR-friendly, nessun IP
 * in chiaro conservato).
 *
 * Tutte le funzioni degradano in modo silenzioso se la tabella `login_attempts`
 * non esiste (es. schema non ancora aggiornato in produzione): in quel caso resta
 * attivo il solo limiter di sessione e il login continua a funzionare.
 */

declare(strict_types=1);

const LRL_WINDOW_SECONDS = 900;  // finestra di osservazione: 15 minuti
const LRL_MAX_PER_IP     = 10;   // tentativi falliti per IP oltre i quali si blocca

/** Indirizzo IP del client (best effort, considera un eventuale proxy fidato). */
function lrl_client_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '');
}

/** Hash dell'IP con sale applicativo: l'IP in chiaro non viene mai memorizzato. */
function lrl_ip_hash(): string
{
    return hash('sha256', 'fs_lrl|' . lrl_client_ip());
}

/**
 * Verifica se l'IP corrente ha superato la soglia di tentativi nella finestra.
 *
 * @return array{blocked:bool, minutes:int}
 */
function lrl_check(PDO $db, string $ipHash): array
{
    try {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM login_attempts
              WHERE ip_hash = ?
                AND attempted_at > (NOW() - INTERVAL " . LRL_WINDOW_SECONDS . " SECOND)"
        );
        $stmt->execute([$ipHash]);
        if ((int)$stmt->fetchColumn() >= LRL_MAX_PER_IP) {
            return ['blocked' => true, 'minutes' => (int)ceil(LRL_WINDOW_SECONDS / 60)];
        }
    } catch (\PDOException $e) {
        error_log('[FS lrl_check] ' . $e->getMessage());
    }
    return ['blocked' => false, 'minutes' => 0];
}

/** Registra un tentativo fallito e ripulisce le righe più vecchie di un giorno. */
function lrl_record_failure(PDO $db, string $ipHash, string $username): void
{
    try {
        $db->prepare(
            "INSERT INTO login_attempts (ip_hash, username, attempted_at) VALUES (?, ?, NOW())"
        )->execute([$ipHash, substr($username, 0, 60)]);

        // Pulizia opportunistica (evita la crescita illimitata della tabella)
        $db->query("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
    } catch (\PDOException $e) {
        error_log('[FS lrl_record] ' . $e->getMessage());
    }
}

/** Azzera i tentativi dell'IP dopo un login riuscito. */
function lrl_clear(PDO $db, string $ipHash): void
{
    try {
        $db->prepare("DELETE FROM login_attempts WHERE ip_hash = ?")->execute([$ipHash]);
    } catch (\PDOException $e) {
        error_log('[FS lrl_clear] ' . $e->getMessage());
    }
}
