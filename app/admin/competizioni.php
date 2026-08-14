<?php
/**
 * app/admin/competizioni.php — Gestione competizioni
 */

declare(strict_types=1);

require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../auth/require_admin.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../_layout.php';
require_once __DIR__ . '/_admin_init.php';

app_layout_start('Admin · Competizioni', 'admin-competizioni');
?>

<!-- Flash message -->
<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type']) ?>" role="alert">
  <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0;margin-top:1px" aria-hidden="true">
    <?= $flash['type'] === 'success' ? 'check_circle' : ($flash['type'] === 'error' ? 'error' : 'info') ?>
  </span>
  <span><?= htmlspecialchars($flash['msg']) ?></span>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/sections/competizioni.php'; ?>

<?php require __DIR__ . '/_proc_banner.php'; ?>

<?php app_layout_end(); ?>
