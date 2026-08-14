<?php
/**
 * app/admin/_admin_init.php — Shared admin page init.
 *
 * Deve essere incluso DOPO:
 *   require_once require_login.php
 *   require_once require_admin.php
 *   require_once config/database.php
 *   require_once app/_layout.php
 *
 * Imposta: $db, $flash, $anno_max
 * Definisce: rb_class()
 */

declare(strict_types=1);

$db = getDB();

// ── Stagioni ─────────────────────────────────────────────────────────
// Assicura la tabella (lazy migration: funziona anche su Altervista senza
// migrazione manuale) e calcola l'anno massimo selezionabile nei menu:
// è il più recente fra la stagione corrente e le stagioni create a mano.
if (!isset($anno_max)) {
    try {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS stagione (
                anno INT NOT NULL PRIMARY KEY,
                creata_il TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        $max_stagione = (int)$db->query("SELECT COALESCE(MAX(anno), 0) FROM stagione")->fetchColumn();
    } catch (\PDOException $e) {
        error_log('[FS stagione] init: ' . $e->getMessage());
        $max_stagione = 0;
    }
    $anno_max = max((int)CURRENT_ANNO, $max_stagione);
}

if (!function_exists('rb_class')) {
    function rb_class(string $ruolo): string
    {
        return match ($ruolo) {
            'P', 'Por'                  => 'rb-P',
            'D', 'Dc', 'Dd', 'Ds', 'B' => 'rb-D',
            'C', 'E', 'M', 'T', 'W'    => 'rb-C',
            'A', 'Pc'                   => 'rb-A',
            default                     => 'rb-D',
        };
    }
}

// Flash message
$flash = null;
if (!empty($_SESSION['admin_flash'])) {
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
}
