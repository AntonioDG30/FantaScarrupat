<?php
/**
 * app/admin/lib/formazione_import.php — Import delle formazioni nel DB.
 *
 * La logica vive qui come funzione, così è testabile a parte (l'handler HTTP è
 * un guscio sottile). Aggancia ogni match alla partita del calendario tramite
 * (id_competizione_disputata, giornata, casa, trasferta) per ricavarne la
 * tipologia, e salva nelle tabelle `formazione` / `formazione_giocatore`.
 */
declare(strict_types=1);

require_once __DIR__ . '/formazione_xlsx.php';

/**
 * Estrae i file .xlsx da uno zip in file temporanei.
 * @return array<string,string> [nomeInterno => percorsoTmp]
 */
function fs_estrai_xlsx_da_zip(string $zipPath): array
{
    $out = [];
    $zip = new \ZipArchive();
    if ($zip->open($zipPath) !== true) return $out;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!preg_match('/\.xlsx$/i', $name)) continue;
        $data = $zip->getFromIndex($i);
        if ($data === false) continue;
        $base = function_exists('fs_temp_dir') ? fs_temp_dir() : sys_get_temp_dir();
        $tmp = tempnam($base, 'fsform');   // ZipArchive apre per contenuto: niente estensione
        if ($tmp !== false && file_put_contents($tmp, $data) !== false) {
            $out[$name] = $tmp;
        }
    }
    $zip->close();
    // ordine naturale: prima "..._3_giornata.xlsx" poi "..._3_giornata (1).xlsx"
    uksort($out, 'strnatcasecmp');
    return $out;
}

/**
 * Salva una singola formazione (una squadra in una partita) + i suoi giocatori.
 */
function fs_salva_formazione(
    \PDO $db, int $id_cd, int $giornata, string $tipologia,
    string $squadra, array $sideData
): void {
    $ins = preg_replace('/^Inserita via app il\s*/i', '', (string)($sideData['inserita'] ?? '')) ?: null;

    $stmt = $db->prepare("
        INSERT INTO formazione
            (id_competizione_disputata, giornata, tipologia, nome_fantasquadra,
             modulo, modificatore_difesa, modificatore_capitano, totale, inserita_il)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $id_cd, $giornata, $tipologia, $squadra,
        $sideData['modulo'] ?? null,
        $sideData['mod_difesa'] ?? null,
        $sideData['mod_capitano'] ?? null,
        $sideData['totale'] ?? null,
        $ins,
    ]);
    $id_formazione = (int)$db->lastInsertId();

    // INSERT multi-riga: una sola query per tutti i giocatori della formazione
    // (riduce di ~22x le query → import leggero e veloce anche su hosting lento).
    $players = $sideData['giocatori'];
    if ($players) {
        $rows = [];
        $vals = [];
        foreach ($players as $p) {
            $rows[] = '(?, ?, ?, NULL, ?, ?, ?, ?, ?)';
            array_push($vals,
                $id_formazione, $p['ruolo'], $p['nome'], (int)$p['ordine'],
                $p['titolare'] ? 1 : 0, $p['conta'] ? 1 : 0, $p['voto_base'], $p['fantavoto']
            );
        }
        $db->prepare(
            "INSERT INTO formazione_giocatore
                (id_formazione, ruolo, nome_giocatore, codice_fantacalcio,
                 ordine, titolare, conta, voto_base, fantavoto)
             VALUES " . implode(', ', $rows)
        )->execute($vals);
    }
}

/**
 * Importa tutte le formazioni dai file xlsx indicati nell'edizione $id_cd.
 * Da chiamare dentro una transazione gestita dal chiamante.
 *
 * @param array<string,string> $xlsxPaths [nome => percorso]
 * @param bool $sovrascrivi se true, sostituisce le formazioni già presenti con la
 *                          stessa chiave naturale; se false, quelle esistenti
 *                          restano e si importano solo le nuove.
 * @return array ['inserite','sostituite','gia_presenti','saltate','giocatori','avvisi']
 */
function fs_importa_formazioni(\PDO $db, int $id_cd, array $xlsxPaths, bool $sovrascrivi): array
{
    // mappa nome canonico fantasquadra (case-insensitive)
    $canon = [];
    foreach ($db->query("SELECT nome_fantasquadra FROM fantasquadra")->fetchAll(\PDO::FETCH_COLUMN) as $n) {
        $canon[mb_strtolower($n)] = $n;
    }

    $findPartita = $db->prepare("
        SELECT tipologia FROM partita_avvessario
         WHERE id_competizione_disputata = ? AND giornata = ?
           AND nome_fantasquadra_casa = ? AND nome_fantasquadra_trasferta = ?
         LIMIT 1
    ");
    $findForm = $db->prepare("
        SELECT id_formazione FROM formazione
         WHERE id_competizione_disputata = ? AND giornata = ? AND tipologia = ? AND nome_fantasquadra = ?
    ");
    $delForm = $db->prepare("DELETE FROM formazione WHERE id_formazione = ?");

    $stats = ['inserite' => 0, 'sostituite' => 0, 'gia_presenti' => 0, 'saltate' => 0, 'giocatori' => 0, 'avvisi' => []];

    foreach ($xlsxPaths as $nome => $path) {
        $parsed = fs_form_parse($path);
        if (!$parsed || empty($parsed['matches'])) {
            $stats['avvisi'][] = "File non leggibile o senza match: $nome";
            continue;
        }
        $giornata = (int)$parsed['giornata'];

        foreach ($parsed['matches'] as $mt) {
            $casa  = $canon[mb_strtolower($mt['casa'])]  ?? null;
            $trasf = $canon[mb_strtolower($mt['trasf'])] ?? null;
            if ($casa === null || $trasf === null) {
                $stats['saltate']++;
                $stats['avvisi'][] = "Squadra non riconosciuta in «{$parsed['titolo']}»: {$mt['casa']} / {$mt['trasf']}";
                continue;
            }

            $findPartita->execute([$id_cd, $giornata, $casa, $trasf]);
            $tipologia = $findPartita->fetchColumn();
            if ($tipologia === false) {
                // Fallback: stessa coppia con casa/trasferta invertite nel calendario
                $findPartita->execute([$id_cd, $giornata, $trasf, $casa]);
                $tipologia = $findPartita->fetchColumn();
            }
            if ($tipologia === false) {
                $stats['saltate']++;
                $stats['avvisi'][] = "Partita non trovata nel calendario: G{$giornata} {$casa} - {$trasf} ({$parsed['competizione']})";
                continue;
            }
            $tipologia = (string)$tipologia;

            // Salva entrambi i lati; sostituzione per-chiave (no cancellazioni collaterali)
            foreach ([[$casa, $mt['casa_data']], [$trasf, $mt['trasf_data']]] as [$squadra, $sideData]) {
                $findForm->execute([$id_cd, $giornata, $tipologia, $squadra]);
                $existing = $findForm->fetchColumn();

                if ($existing !== false) {
                    if (!$sovrascrivi) { $stats['gia_presenti']++; continue; }
                    $delForm->execute([(int)$existing]);
                    fs_salva_formazione($db, $id_cd, $giornata, $tipologia, $squadra, $sideData);
                    $stats['sostituite']++;
                } else {
                    fs_salva_formazione($db, $id_cd, $giornata, $tipologia, $squadra, $sideData);
                    $stats['inserite']++;
                }
                $stats['giocatori'] += count($sideData['giocatori']);
            }
        }
    }

    return $stats;
}
