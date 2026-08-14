<?php
/**
 * config/database.php — PDO singleton
 *
 * Uso: $pdo = getDB();
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Restituisce la connessione PDO condivisa.
 * In caso di errore risponde 503 e interrompe l'esecuzione.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (\PDOException $e) {
        error_log('[FantaScarrupat] DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        exit('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Errore</title></head>'
           . '<body style="font-family:sans-serif;padding:2rem">'
           . '<h1>Servizio temporaneamente non disponibile.</h1>'
           . '<p>Riprovare tra qualche minuto.</p>'
           . '</body></html>');
    }

    return $pdo;
}

/**
 * Converte un anno intero in stringa stagione leggibile.
 * Es. 2025 → "2024/25"
 */
function anno_to_stagione(int $anno): string
{
    return ($anno - 1) . '/' . substr((string)$anno, 2, 2);
}

/**
 * URL immagine con fallback a placeholder SVG inline se il file non esiste.
 * Restituisce una stringa URL o 'data:...' SVG.
 */
function img_url(string $baseUrl, string $filename): string
{
    if ($filename === '' || $filename === 'NonDisponibile.png') {
        return ''; // Indica al template di mostrare l'avatar testuale
    }
    return htmlspecialchars($baseUrl . $filename, ENT_QUOTES, 'UTF-8');
}

/**
 * Neutralizza la "CSV/formula injection": se un campo inizia con un carattere
 * che Excel/LibreOffice interpreterebbero come formula (= + - @ TAB CR),
 * vi antepone un apostrofo. Da usare su tutti i campi testuali esportati in CSV.
 */
function csv_safe(string|int|float|null $value): string
{
    $s = (string)$value;
    if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $s;
    }
    return $s;
}
