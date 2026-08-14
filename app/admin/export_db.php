<?php
/**
 * app/admin/export_db.php — Esporta dump SQL del database
 *
 * GET ?csrf_token=... — restituisce il dump come download .sql
 */

declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// L'export è una mutazione "sensibile": solo POST + CSRF nel body (mai in querystring)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Metodo non consentito.';
    exit;
}
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
    http_response_code(403);
    echo 'Token di sicurezza non valido. Torna al pannello admin.';
    exit;
}

$db       = getDB();
$database = DB_NAME;

try {
    $tables = $db->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    error_log('[FS export_db] ' . $e->getMessage());
    http_response_code(500);
    echo 'Errore durante l\'esportazione del database. Riprova più tardi.';
    exit;
}

ob_start();

echo "-- FantaScarrupat Database Dump\n";
echo "-- Data: " . date('Y-m-d H:i:s') . "\n";
echo "-- Database: `{$database}`\n\n";
echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    echo "-- --------------------------------------------------------\n";
    echo "-- Tabella: `{$table}`\n\n";

    // CREATE TABLE
    try {
        $row    = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
        $keys   = array_keys($row);
        $create = $row[$keys[1]]; // second column = Create Table
        echo "DROP TABLE IF EXISTS `{$table}`;\n";
        echo $create . ";\n\n";
    } catch (\PDOException $e) {
        echo "-- ERRORE SHOW CREATE TABLE `{$table}`: " . $e->getMessage() . "\n\n";
        continue;
    }

    // Data
    try {
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            foreach (array_chunk($rows, 200) as $chunk) {
                $valGroups = [];
                foreach ($chunk as $r) {
                    $rowVals = [];
                    foreach ($r as $v) {
                        $rowVals[] = $v === null
                            ? 'NULL'
                            : "'" . addslashes((string)$v) . "'";
                    }
                    $valGroups[] = '(' . implode(', ', $rowVals) . ')';
                }
                echo "INSERT INTO `{$table}` ({$cols}) VALUES\n"
                   . implode(",\n", $valGroups) . ";\n";
            }
            echo "\n";
        }
    } catch (\PDOException $e) {
        echo "-- ERRORE SELECT `{$table}`: " . $e->getMessage() . "\n\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";

$sql      = ob_get_clean();
$filename = 'fantascarrupat_backup_' . date('Y-m-d_His') . '.sql';

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($sql));
header('Cache-Control: no-store');
header('Pragma: public');

echo $sql;
exit;
