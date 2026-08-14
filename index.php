<?php
/**
 * Root index.php — Serve la homepage pubblica direttamente sull'URL radice "/".
 *
 * Niente redirect verso /public/index.php: per SEO e branding la homepage vive
 * sull'URL canonico "/". I percorsi interni di public/index.php restano corretti
 * perché usano __DIR__ (relativo al file) e BASE_PATH (assoluto dalla root).
 */
declare(strict_types=1);

require __DIR__ . '/public/index.php';
