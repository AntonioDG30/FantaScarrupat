<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Utils;

/**
 * Trait per funzioni di normalizzazione.
 * Equivalente alle funzioni helper globali nel Python.
 */
trait NormalizerTrait
{
    /**
     * Converte una stringa stagione 'YYYY-YY' nella forma usata dalle pagine Wikipedia,
     * che impiegano il separatore 'en dash' (–).
     * 
     * Esempio:
     *     '2024-25' -> '2024–25'
     */
    private function seasonStrToWiki(string $season): string
    {
        [$a, $b] = explode('-', $season);
        return "{$a}–{$b}"; // en dash Unicode character
    }

    /**
     * Estrae l'anno di inizio stagione da una stringa 'YYYY-YY'.
     * 
     * Serve per chiamate API che richiedono l'anno iniziale come intero.
     * Esempio:
     *     '2025-26' -> 2025
     */
    private function seasonStartYear(string $season): int
    {
        [$a, ] = explode('-', $season);
        return (int)$a;
    }

    /**
     * Normalizza una stringa di nazionalità per confronti robusti.
     * 
     * Pipeline di normalizzazione:
     * - se input vuoto -> stringa vuota;
     * - rimozione diaccitazioni (NFKD) e caratteri combinanti;
     * - trim + minuscolo;
     * - uniformazione degli apostrofi/accents vari;
     * - rimozione degli apostrofi;
     * - rimozione di spazi/punteggiatura comuni (., spazio, -, _, /);
     * - compressione di ripetizioni anomale (>=3) dello stesso carattere (es. 'maroccco' -> 'marocco');
     * - mappatura alias EN->IT e sinonimi frequenti (es. 'netherlands' -> 'paesibassi').
     * 
     * Questo produce una "chiave" confrontabile in modo coerente tra fonti diverse.
     */
    private function normalizeCountryForMatch(string $s): string
    {
        if (empty($s)) {
            return "";
        }
        
        // 1) rimuove accenti/diacritici mantenendo lettere base
        $s = \Normalizer::normalize($s, \Normalizer::FORM_KD);
        $s = preg_replace('/[\x{0300}-\x{036f}]/u', '', $s);
        
        // 2) normalizza spazi/case
        $s = trim(strtolower($s));
        
        // 3) uniforma varie forme di apostrofo
        $s = str_replace(["'", "'", "ʼ", "´", "`"], "'", $s);
        
        // 4) rimuove apostrofi residui
        $s = str_replace("'", "", $s);
        
        // 5) elimina separatori/punteggiatura/whitespace comuni per un match più solido
        $s = preg_replace('/[.\s\-_\/]/', '', $s);
        
        // 6) comprime ripetizioni eccessive (>=3) dello stesso carattere
        $s = preg_replace('/(.)\1{2,}/', '$1$1', $s);
        
        // Tabella alias per ricondurre le varianti (soprattutto inglesi) alle forme italiane normalizzate.
        $aliases = [
            "france" => "francia",
            "germany" => "germania",
            "spain" => "spagna",
            "italy" => "italia",
            "portugal" => "portogallo",
            "belgium" => "belgio",
            "switzerland" => "svizzera",
            "austria" => "austria",
            "england" => "inghilterra",
            "scotland" => "scozia",
            "wales" => "galles",
            "netherlands" => "paesibassi",
            "holland" => "paesibassi",
            "olanda" => "paesibassi",
            "czechrepublic" => "repubblicaceca",
            "czechia" => "repubblicaceca",
            "bosniaandherzegovina" => "bosniaederzegovina",
            "northmacedonia" => "macedoniadelnord",
            "republicofireland" => "irlanda",
            "unitedstates" => "statiuniti",
            "usa" => "statiuniti",
            "southkorea" => "coreadelsud",
            "guadeloupe" => "guadalupe",
            "frenchguiana" => "guyanafrancese",
            "cotedivoire" => "costadavorio",
            "ivorycoast" => "costadavorio",
            "capeverde" => "capoverde",
            "capeverdeislands" => "capoverde",
            "drcongo" => "repubblicademocraticadelcongo",
            "democraticrepublicofthecongo" => "repubblicademocraticadelcongo",
            "morocco" => "marocco",
            "cameroon" => "camerun",
            "senegal" => "senegal",
            "gambia" => "gambia",
            "algeria" => "algeria",
            "angola" => "angola",
            "ghana" => "ghana",
            "nigeria" => "nigeria",
            "kosovo" => "kosovo",
            "serbia" => "serbia",
            "croatia" => "croazia",
            "greece" => "grecia",
            "turkey" => "turchia",
            "poland" => "polonia",
            "romania" => "romania",
            "albania" => "albania",
            "argentina" => "argentina",
            "brazil" => "brasile",
            "uruguay" => "uruguay",
            "chile" => "cile",
            "paraguay" => "paraguay",
            "peru" => "peru",
            "ecuador" => "ecuador",
            "venezuela" => "venezuela",
            "bolivia" => "bolivia",
            "colombia" => "colombia",
        ];
        
        return $aliases[$s] ?? $s;
    }

    /**
     * Converte una stagione in formato "YYYY-YY" in una chiave ordinabile (YYYY, YY)
     * Utile per ordinare liste di stagioni in senso cronologico con sorted(key=_year_key)
     */
    private function yearKey(string $y): array
    {
        [$a, $b] = explode('-', $y);
        return [(int)$a, (int)$b];
    }

    /**
     * Restituisce il riferimento temporale del 1° luglio dell'anno di inizio stagione
     * Esempio: "2025-26" -> date(2025, 7, 1). Serve per calcolo età al via del campionato
     */
    private function startOfSeasonReference(string $currentSeason): \DateTimeImmutable
    {
        [$a, ] = explode('-', $currentSeason);
        return new \DateTimeImmutable("{$a}-07-01");
    }

    /**
     * Legge un CSV gestendo in modo tollerante le codifiche.
     * Tenta prima 'utf-8-sig' (gestisce BOM), poi ricade a 'utf-8'.
     * 
     * Parametri:
     *     path: percorso del file CSV.
     *     options: array di opzioni (delimiter, enclosure, escape)
     * 
     * Ritorna:
     *     Array con i dati del CSV.
     */
    private function safeReadCsv(string $path, array $options = []): array
    {
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $escape = $options['escape'] ?? '\\';
        
        $content = file_get_contents($path);
        
        // Remove BOM if present
        $bom = "\xef\xbb\xbf";
        if (substr($content, 0, 3) === $bom) {
            $content = substr($content, 3);
        }
        
        $lines = explode("\n", $content);
        $data = [];
        
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                $data[] = str_getcsv($line, $delimiter, $enclosure, $escape);
            }
        }
        
        return $data;
    }

    /**
     * Restituisce una chiave nazione normalizzata e robusta ai sinonimi EN↔IT.
     */
    private function normalizeCountryKey(string $s): string
    {
        if (empty($s)) {
            return "";
        }
        
        $key = $this->normalizeCountryForMatch($s);
        
        $aliases = [
            "netherlands" => "paesibassi",
            "holland" => "paesibassi",
            "cotedivoire" => "costadavorio",
            "ivorycoast" => "costadavorio",
            "bosniaandherzegovina" => "bosniaederzegovina",
            "czechrepublic" => "repubblicaceca",
            "czechia" => "repubblicaceca",
            "drcongo" => "repubblicademocraticadelcongo",
            "democraticrepublicofthecongo" => "repubblicademocraticadelcongo",
            "capeverde" => "capoverde",
            "unitedstates" => "statiuniti",
            "usa" => "statiuniti",
            "southkorea" => "coreadelsud",
            "northmacedonia" => "macedoniadelnord",
            "republicofireland" => "irlanda",
            "england" => "inghilterra",
            "wales" => "galles",
            "scotland" => "scozia",
        ];
        
        return $aliases[$key] ?? $key;
    }

    /**
     * Restituisce una forma breve e coerente del nome squadra
     */
    private function normalizeTeam(string $s): string
    {
        if (empty($s)) {
            return "";
        }
        
        // rimuove eventuali note tra parentesi/quadre
        $s = preg_replace('/\[.*?\]|\(.*?\)/', '', $s);
        $s = str_replace('  ', ' ', trim($s));
        
        $mapping = [
            "Inter Milan" => "Inter",
            "Internazionale" => "Inter",
            "FC Internazionale Milano" => "Inter",
            "FC Internazionale" => "Inter",
            "AC Milan" => "Milan",
            "A.C. Milan" => "Milan",
            "AC Milan SpA" => "Milan",
            "SS Lazio" => "Lazio",
            "S.S. Lazio" => "Lazio",
            "SS Lazio Spa" => "Lazio",
            "AS Roma" => "Roma",
            "A.S. Roma" => "Roma",
            "AS Roma Spa" => "Roma",
            "Udinese Calcio" => "Udinese",
            "US Lecce" => "Lecce",
            "U.S. Lecce" => "Lecce",
            "Sassuolo Calcio" => "Sassuolo",
            "U.S. Sassuolo Calcio" => "Sassuolo",
            "US Sassuolo" => "Sassuolo",
            "Torino FC" => "Torino",
            "Torino F.C." => "Torino",
            "AC Monza" => "Monza",
            "A.C. Monza" => "Monza",
            "Genoa CFC" => "Genoa",
            "Genoa C.F.C." => "Genoa",
            "Bologna FC 1909" => "Bologna",
            "Bologna F.C. 1909" => "Bologna",
            "Cagliari Calcio" => "Cagliari",
            "Cagliari Calcio S.p.A." => "Cagliari",
            "Hellas Verona" => "Verona",
            "Hellas Verona FC" => "Verona",
            "Frosinone Calcio" => "Frosinone",
            "Como 1907" => "Como",
            "Atalanta BC" => "Atalanta",
            "Atalanta Bergamasca Calcio" => "Atalanta",
            "Empoli FC" => "Empoli",
            "Empoli F.C." => "Empoli",
            "ACF Fiorentina" => "Fiorentina",
            "Juventus FC" => "Juventus",
            "Juventus" => "Juventus",
            "SSC Napoli" => "Napoli",
            "S.S.C. Napoli" => "Napoli",
            "US Salernitana 1919" => "Salernitana",
            "U.S. Salernitana 1919" => "Salernitana",
            "US Cremonese" => "Cremonese",
            "U.S. Cremonese" => "Cremonese",
            "Parma Calcio 1913" => "Parma",
            "Venezia FC" => "Venezia",
            "Brescia Calcio" => "Brescia",
            "SPAL 2013" => "SPAL",
            "SPAL" => "SPAL",
            "US Sassuolo Calcio" => "Sassuolo",
            "AC Pisa 1909" => "Pisa",
            "A.C. Pisa 1909" => "Pisa",
            "FC Crotone" => "Crotone",
            "F.C. Crotone" => "Crotone",
            "FC Internazionale Milano" => "Inter",
        ];
        
        return $mapping[$s] ?? $s;
    }

    /**
     * Applica _normalize_team ad ogni elemento della lista e ritorna la lista normalizzata.
     */
    private function normalizeTeamList(array $teams): array
    {
        return array_map([$this, 'normalizeTeam'], $teams);
    }

    /**
     * Normalizza un nome qualsiasi in una chiave testuale robusta per confronti
     */
    private function normalizeName(string $s): string
    {
        if (empty($s)) {
            return "";
        }
        
        $s = trim($s);
        $s = \Normalizer::normalize($s, \Normalizer::FORM_KD);
        $s = preg_replace('/[\x{0300}-\x{036f}]/u', '', $s);
        $s = strtolower($s);
        $s = preg_replace("/[^a-z'\s]/", " ", $s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        
        return $s;
    }

    /**
     * Estrae il cognome da una chiave normalizzata (ultima parola)
     */
    private function surnameFromFull(string $fullNorm): string
    {
        if (empty($fullNorm)) {
            return "";
        }
        
        $parts = explode(' ', trim($fullNorm));
        return !empty($parts) ? end($parts) : "";
    }

    /**
     * Heuristics per filtrare stringhe che sembrano davvero nomi/cognomi
     */
    private function isProbableHumanName(string $s): bool
    {
        if (empty($s)) {
            return false;
        }
        
        $s = trim($s);
        $len = strlen($s);
        
        if ($len < 2 || $len > 40) {
            return false;
        }
        
        if (preg_match('/\d/', $s)) {
            return false;
        }
        
        $low = strtolower($s);
        $blacklist = [
            "rigori", "calci piazzati", "classifica", "titolari", "panchina",
            "campioncino", "logo", "badge", "svg", "image", "avatar"
        ];
        
        foreach ($blacklist as $tok) {
            if (strpos($low, $tok) !== false) {
                return false;
            }
        }
        
        if (!strpos($s, ' ') && !strpos($s, '-') && !strpos($s, "'") && !strpos($s, "'")) {
            return $len >= 3;
        }
        
        return true;
    }

    /**
     * Pulisce un'etichetta testuale per far emergere il nome proprio
     */
    private function cleanLabelName(string $s): string
    {
        if (empty($s)) {
            return "";
        }
        
        $x = trim($s);
        
        // rimuove prefissi redazionali (campioncino/capitano/mvp)
        $x = preg_replace('/^(campioncino|capitano|mvp)\s+/i', '', $x);
        $x = preg_replace('/\bcalci\s+piazzati\b/i', '', $x);
        $x = preg_replace('/\brigori\b/i', '', $x);
        
        // normalizza forme come "Zapata D." -> "Zapata"
        $x = preg_replace('/\b([A-ZÀ-ÖØ-Ý][A-Za-zÀ-ÖØ-öø-ÿ\'\-]+)\s+[A-Z]\.(?=\s|$)/', '$1', $x);
        
        // toglie parentesi finali e spazi multipli
        $x = preg_replace('/\s*\(.*?\)\s*$/', '', $x);
        $x = preg_replace('/\s{2,}/', ' ', trim($x));
        
        return $x;
    }

    /**
     * Estrae una stringa stagione "YYYY-YY" dal nome file usando due pattern accettati
     */
    private function extractYearFromFilename(string $filename): ?string
    {
        if (preg_match('/(\d{4})_(\d{2})/', $filename, $matches)) {
            return "{$matches[1]}-{$matches[2]}";
        }
        
        if (preg_match('/(\d{4})-(\d{2})/', $filename, $matches)) {
            return "{$matches[1]}-{$matches[2]}";
        }
        
        return null;
    }
}