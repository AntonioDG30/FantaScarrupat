<?php
/**
 * config/archive.php — Archiviazione dei file caricati dall'admin.
 *
 * Salva una copia dei file importati (listone, rose, competizioni, formazioni)
 * in caricamenti_admin/<categoria>/ con un nome parlante, per tenerne memoria.
 * La cartella è protetta dall'accesso web: la .htaccess deny-all viene generata
 * qui lato server (così non dipende dall'upload FTP dei dotfile) e c'è anche una
 * regola nella .htaccess di root.
 */
declare(strict_types=1);

if (!function_exists('fs_stagione_slug')) {
    /** anno INT (2026) -> "2025-2026" */
    function fs_stagione_slug(int $anno): string
    {
        return ($anno - 1) . '-' . $anno;
    }
}

if (!function_exists('fs_slug')) {
    /** "Champions League" -> "champions-league" */
    function fs_slug(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        return trim($s, '-');
    }
}

if (!function_exists('fs_temp_dir')) {
    /**
     * Cartella temporanea DENTRO il progetto: sicura con l'open_basedir di
     * Altervista (sys_get_temp_dir/'/tmp' è spesso fuori dai path consentiti).
     * Sta sotto caricamenti_admin/ (già protetta da accesso web).
     */
    function fs_temp_dir(): string
    {
        $base = dirname(__DIR__) . '/caricamenti_admin';
        $dir  = $base . '/_tmp';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $ht = $base . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents(
                $ht,
                "Require all denied\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
            );
        }
        return is_dir($dir) ? $dir : sys_get_temp_dir();
    }
}

if (!function_exists('fs_archivia_caricamento')) {
    /**
     * Copia $sourcePath in caricamenti_admin/<categoria>/<filename>.
     * Best-effort: ritorna il path salvato oppure null. NON interrompe mai l'import.
     *
     * @param string $sourcePath percorso del file sorgente (es. tmp_name dell'upload)
     * @param string $categoria  'listone' | 'rose' | 'competizioni' | 'formazioni'
     * @param string $filename   nome parlante del file di destinazione
     */
    function fs_archivia_caricamento(string $sourcePath, string $categoria, string $filename): ?string
    {
        $categoria = preg_replace('/[^a-z0-9_-]/i', '', $categoria) ?? '';
        $allowed   = ['listone', 'rose', 'competizioni', 'formazioni'];
        if (!in_array($categoria, $allowed, true) || !is_file($sourcePath)) {
            return null;
        }

        $baseDir = dirname(__DIR__) . '/caricamenti_admin';
        $destDir = $baseDir . '/' . $categoria;

        if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            error_log('[FS archive] impossibile creare la cartella ' . $destDir);
            return null;
        }

        // Protezione web: .htaccess deny-all (creata lato server, non via FTP)
        $ht = $baseDir . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents(
                $ht,
                "# Archivio caricamenti admin — nessun accesso web\n"
                . "Require all denied\n"
                . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n"
            );
        }

        // Nome file sicuro
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'file';
        $dest     = $destDir . '/' . $filename;

        if (!@copy($sourcePath, $dest)) {
            error_log('[FS archive] copia fallita: ' . $sourcePath . ' -> ' . $dest);
            return null;
        }
        return $dest;
    }
}
