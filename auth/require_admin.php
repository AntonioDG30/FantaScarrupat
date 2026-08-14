<?php
/**
 * auth/require_admin.php — Protezione sezioni admin
 *
 * Da includere dopo require_login.php nelle pagine di /app/admin/:
 *   require_once __DIR__ . '/../../auth/require_login.php';
 *   require_once __DIR__ . '/../../auth/require_admin.php';
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/config.php';
}

// require_login.php deve essere già stato incluso e la sessione verificata
if (empty($_SESSION['user_id'])) {
    $bp = defined('BASE_PATH') ? BASE_PATH : '/';
    header('Location: ' . $bp . 'login.php', true, 302);
    exit;
}

if (empty($_SESSION['flag_admin'])) {
    $bp = defined('BASE_PATH') ? BASE_PATH : '/';
    header('Location: ' . $bp . 'app/dashboard.php', true, 302);
    exit;
}
