<?php
/**
 * app/admin/lib/formazione_xlsx.php — Parser dei file XLSX "Formazioni".
 *
 * Parsing puro PHP (ZipArchive + SimpleXML), nessun Composer.
 * Restituisce una struttura con titolo, competizione, giornata e l'elenco dei
 * match; per ogni match i due lati (casa/trasferta) con titolari e panchina,
 * voto base (nero) e fantavoto (verde/grigio). Il COLORE è dato semantico:
 *   - verde  (FF008000) = il fantavoto conta (titolare in campo o subentrato)
 *   - grigio (FFD3D3D3) = panchinaro non subentrato (voto reale ma non conteggiato)
 *
 * Layout: titolo in riga 1; per ogni match una riga header (casa in col A,
 * risultato "g-g" in col F, trasferta in col G), riga modulo, 11 titolari,
 * riga "Panchina", panchinari, "Modificatore difesa/Capitano", "TOTALE: x,yy".
 * I due lati possono DESINCRONIZZARSI (numero panchinari diverso): per questo
 * vengono letti in modo indipendente, lato sinistro (A,B,D,E) e destro (G,H,J,K).
 */
declare(strict_types=1);

if (!defined('FS_COL_VERDE'))  define('FS_COL_VERDE',  'rgb:FF008000'); // fantavoto che conta
if (!defined('FS_COL_GRIGIO')) define('FS_COL_GRIGIO', 'rgb:FFD3D3D3'); // panchina non subentrato

/**
 * Ruoli ammessi nella colonna ruolo (classic + mantra).
 */
function fs_form_ruoli_validi(): array
{
    return ['P','D','C','A','Por','Dc','Dd','Ds','B','E','M','T','W','Pc'];
}

/**
 * Converte un valore cella in float, gestendo '-' (nessun voto) e la virgola.
 */
function fs_form_num($v): ?float
{
    $v = strtolower(trim((string)$v));
    if ($v === '') return null;
    // token "senza voto" (nessun voto in pagella)
    if (in_array($v, ['-', '–', '—', 'sv', 's.v.', 'ng', 'n.g.', 'n.e.'], true)) return null;
    // tieni solo cifre, punto, virgola e segno (gestisce es. "6*" voto d'ufficio → 6)
    $v = str_replace(',', '.', $v);
    $v = preg_replace('/[^0-9.\-]/', '', $v) ?? '';
    if ($v === '' || $v === '-' || $v === '.') return null;
    return is_numeric($v) ? (float)$v : null;
}

/**
 * Legge l'XLSX e ritorna la griglia: [rownum => [colLetter => ['v'=>string,'color'=>string]]].
 * Il colore è quello del font della cella (risolto via styles.xml).
 */
function fs_form_xlsx_grid(string $path): array
{
    if (!class_exists('ZipArchive')) return [];
    $zip = new \ZipArchive();
    if ($zip->open($path) !== true) return [];

    // styles: indice xf -> colore font
    $xfColor = [];
    if ($st = $zip->getFromName('xl/styles.xml')) {
        $sx = @simplexml_load_string($st, 'SimpleXMLElement', LIBXML_NONET);
        if ($sx) {
            $fontColor = [];
            $i = 0;
            foreach ($sx->fonts->font as $f) {
                $c = 'auto';
                if (isset($f->color)) {
                    $a = $f->color->attributes();
                    if (isset($a['rgb']))     $c = 'rgb:' . (string)$a['rgb'];
                    elseif (isset($a['theme'])) $c = 'theme:' . (string)$a['theme'];
                }
                $fontColor[$i++] = $c;
            }
            $j = 0;
            foreach ($sx->cellXfs->xf as $xf) {
                $a = $xf->attributes();
                $xfColor[$j++] = $fontColor[isset($a['fontId']) ? (int)$a['fontId'] : 0] ?? 'auto';
            }
        }
    }

    // shared strings (testo concatenato dei run)
    $shared = [];
    if ($ss = $zip->getFromName('xl/sharedStrings.xml')) {
        $sx = @simplexml_load_string($ss, 'SimpleXMLElement', LIBXML_NONET);
        if ($sx) {
            foreach ($sx->si as $si) {
                if (isset($si->t)) {
                    $shared[] = (string)$si->t;
                } else {
                    $t = '';
                    if (isset($si->r)) foreach ($si->r as $r) $t .= (string)($r->t ?? '');
                    $shared[] = $t;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheetXml) return [];
    $sheet = @simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NONET);
    if (!$sheet || !isset($sheet->sheetData)) return [];

    $grid = [];
    foreach ($sheet->sheetData->row as $row) {
        $rn = (int)$row['r'];
        foreach ($row->c as $c) {
            $col  = preg_replace('/[0-9]/', '', (string)($c['r'] ?? ''));
            $type = (string)($c['t'] ?? '');
            $s    = isset($c['s']) ? (int)$c['s'] : -1;
            $v    = (string)($c->v ?? '');
            if ($type === 's') {
                $val = $shared[(int)$v] ?? '';
            } elseif ($type === 'inlineStr') {
                $val = (string)($c->is->t ?? '');
            } else {
                $val = $v;
            }
            $grid[$rn][$col] = [
                'v'     => $val,
                'color' => $s >= 0 ? ($xfColor[$s] ?? 'auto') : 'auto',
            ];
        }
    }
    return $grid;
}

/**
 * Parsa un lato del match (sinistro o destro) leggendo le colonne indicate.
 * @return array ['modulo','mod_difesa','mod_capitano','totale','inserita','giocatori'=>[...]]
 */
function fs_form_parse_side(array $grid, int $R, int $next, string $cRole, string $cName, string $cVoto, string $cFanta): array
{
    $ruoli   = fs_form_ruoli_validi();
    $modulo  = trim((string)($grid[$R + 1][$cRole]['v'] ?? '')) ?: null;
    $players = [];
    $modDif = null; $modCap = null; $totale = null; $inserita = null;
    $section = 'tit'; $ord = 0;

    for ($rr = $R + 2; $rr < $next; $rr++) {
        $label = trim((string)($grid[$rr][$cRole]['v'] ?? ''));
        $name  = trim((string)($grid[$rr][$cName]['v'] ?? ''));
        if ($label === '' && $name === '') continue;

        if (stripos($label, 'Panchina') !== false) { $section = 'pan'; continue; }

        if (stripos($label, 'Modificatore') !== false) {
            $val = fs_form_num($grid[$rr][$cFanta]['v'] ?? '');
            if (stripos($label, 'difesa') !== false)        $modDif = $val;
            elseif (stripos($label, 'Capitano') !== false)  $modCap = $val;
            continue;
        }
        if (stripos($label, 'TOTALE') !== false) {
            if (preg_match('/([\d.,]+)/', $label, $mm)) $totale = (float)str_replace(',', '.', $mm[1]);
            continue;
        }
        if (stripos($label, 'Inserita') !== false) { $inserita = $label; continue; }

        if (in_array($label, $ruoli, true)) {
            $col = $grid[$rr][$cFanta]['color'] ?? 'auto';
            $players[] = [
                'ruolo'     => $label,
                'nome'      => $name,
                'voto_base' => fs_form_num($grid[$rr][$cVoto]['v'] ?? ''),
                'fantavoto' => fs_form_num($grid[$rr][$cFanta]['v'] ?? ''),
                'titolare'  => ($section === 'tit'),
                'conta'     => ($col === FS_COL_VERDE),
                'ordine'    => $ord++,
            ];
        }
    }

    return [
        'modulo'       => $modulo,
        'mod_difesa'   => $modDif,
        'mod_capitano' => $modCap,
        'totale'       => $totale,
        'inserita'     => $inserita,
        'giocatori'    => $players,
    ];
}

/**
 * Parsa l'intero file formazioni.
 * @return array|null ['titolo','competizione','giornata',
 *                      'matches'=>[ ['casa','trasf','gol_casa','gol_trasf','casa_data','trasf_data'], ... ]]
 */
function fs_form_parse(string $path): ?array
{
    $grid = fs_form_xlsx_grid($path);
    if (!$grid) return null;

    $title = trim((string)($grid[1]['A']['v'] ?? ''));
    $comp = ''; $giornata = 0;
    if (preg_match('/Formazioni\s+(.+?)\s*-\s*Giornata\s*(\d+)/i', $title, $m)) {
        $comp     = trim($m[1]);
        $giornata = (int)$m[2];
    }

    $maxR = max(array_keys($grid));

    // righe header: col F = "g-g"
    $headers = [];
    for ($r = 1; $r <= $maxR; $r++) {
        $f = trim((string)($grid[$r]['F']['v'] ?? ''));
        if (preg_match('/^\d+\s*-\s*\d+$/', $f)) $headers[] = $r;
    }

    $matches = [];
    foreach ($headers as $idx => $R) {
        $next  = $headers[$idx + 1] ?? ($maxR + 1);
        $casa  = trim((string)($grid[$R]['A']['v'] ?? ''));
        $trasf = trim((string)($grid[$R]['G']['v'] ?? ''));
        $res   = str_replace(' ', '', trim((string)($grid[$R]['F']['v'] ?? '')));
        $parts = explode('-', $res);
        $gc    = (int)($parts[0] ?? 0);
        $gt    = (int)($parts[1] ?? 0);
        if ($casa === '' || $trasf === '') continue;

        $matches[] = [
            'casa'       => $casa,
            'trasf'      => $trasf,
            'gol_casa'   => $gc,
            'gol_trasf'  => $gt,
            'casa_data'  => fs_form_parse_side($grid, $R, $next, 'A', 'B', 'D', 'E'),
            'trasf_data' => fs_form_parse_side($grid, $R, $next, 'G', 'H', 'J', 'K'),
        ];
    }

    return [
        'titolo'       => $title,
        'competizione' => $comp,
        'giornata'     => $giornata,
        'matches'      => $matches,
    ];
}
