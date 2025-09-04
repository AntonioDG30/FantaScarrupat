<?php
declare(strict_types=1);

namespace FantacalcioAnalyzer\Utils;

/**
 * Classe per il caricamento dati CSV/Excel.
 * Equivalente alla gestione pandas nel Python.
 */
class DataLoader
{
    use NormalizerTrait;
    
    private Logger $logger;
    
    public function __construct()
    {
        $this->logger = new Logger("DataLoader");
    }
    
    /**
     * Legge un file CSV e ritorna array di array associativi
     */
    public function readCsv(string $path, bool $header = true, array $columns = []): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: $path");
        }
        
        $data = $this->safeReadCsv($path);
        
        if (empty($data)) {
            return [];
        }
        
        // Se header è true, usa la prima riga come nomi colonne
        if ($header && count($data) > 0) {
            $headerRow = array_shift($data);
            
            // Se sono specificati nomi colonne custom, usali
            if (!empty($columns)) {
                $headerRow = array_slice($columns, 0, count($headerRow));
            }
            
            // Converti in array associativo
            $result = [];
            foreach ($data as $row) {
                $assoc = [];
                foreach ($headerRow as $idx => $colName) {
                    $assoc[$colName] = $row[$idx] ?? null;
                }
                $result[] = $assoc;
            }
            
            return $result;
        }
        
        // Se non c'è header ma ci sono colonne specificate
        if (!empty($columns)) {
            $result = [];
            foreach ($data as $row) {
                $assoc = [];
                foreach ($columns as $idx => $colName) {
                    $assoc[$colName] = $row[$idx] ?? null;
                }
                $result[] = $assoc;
            }
            return $result;
        }
        
        return $data;
    }
    
    /**
     * Legge un file Excel (usando SimpleXLSX o parsing XML diretto)
     * Per semplicità su Altervista, converte prima in CSV se possibile
     */
    public function readExcel(string $path, string $sheet = '', int $skiprows = 0): array
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: $path");
        }
        
        // Su Altervista potremmo non avere librerie Excel native
        // Fallback: richiedi conversione manuale in CSV o usa servizio esterno
        $this->logger->warning("Excel reading requires manual conversion to CSV on Altervista");
        
        // Prova a cercare un CSV con lo stesso nome
        $csvPath = str_replace('.xlsx', '.csv', $path);
        if (file_exists($csvPath)) {
            $data = $this->readCsv($csvPath, false);
            
            // Skip rows
            for ($i = 0; $i < $skiprows; $i++) {
                array_shift($data);
            }
            
            return $data;
        }
        
        return [];
    }
    
    /**
     * Trova file con pattern glob
     */
    public function glob(string $pattern): array
    {
        return glob($pattern) ?: [];
    }
    
    /**
     * Converte stringa data in DateTimeImmutable
     */
    public function parseDate(string $dateStr, array $formats = []): ?\DateTimeImmutable
    {
        if (empty($formats)) {
            $formats = ['d/m/Y H:i:s', 'd/m/Y', 'Y-m-d'];
        }
        
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date;
            }
        }
        
        return null;
    }
    
    /**
     * Filtra array di array per condizione
     */
    public function filterRows(array $data, callable $condition): array
    {
        return array_filter($data, $condition);
    }
    
    /**
     * Ordina array di array per colonna
     */
    public function sortBy(array &$data, string $column, bool $ascending = true): void
    {
        usort($data, function($a, $b) use ($column, $ascending) {
            $valA = $a[$column] ?? null;
            $valB = $b[$column] ?? null;
            
            if ($valA === $valB) return 0;
            
            $result = ($valA < $valB) ? -1 : 1;
            return $ascending ? $result : -$result;
        });
    }
    
    /**
     * Merge di due array di array su una colonna chiave (simile a pandas merge)
     */
    public function merge(array $left, array $right, string $on, string $how = 'inner'): array
    {
        $rightIndex = [];
        foreach ($right as $row) {
            $key = $row[$on] ?? null;
            if ($key !== null) {
                $rightIndex[$key] = $row;
            }
        }
        
        $result = [];
        
        if ($how === 'inner' || $how === 'left') {
            foreach ($left as $leftRow) {
                $key = $leftRow[$on] ?? null;
                if ($key !== null && isset($rightIndex[$key])) {
                    // Inner join: merge rows
                    $result[] = array_merge($leftRow, $rightIndex[$key]);
                } elseif ($how === 'left') {
                    // Left join: keep left row even if no match
                    $result[] = $leftRow;
                }
            }
        }
        
        if ($how === 'right') {
            $leftIndex = [];
            foreach ($left as $row) {
                $key = $row[$on] ?? null;
                if ($key !== null) {
                    $leftIndex[$key] = $row;
                }
            }
            
            foreach ($right as $rightRow) {
                $key = $rightRow[$on] ?? null;
                if ($key !== null && isset($leftIndex[$key])) {
                    $result[] = array_merge($leftIndex[$key], $rightRow);
                } else {
                    $result[] = $rightRow;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Group by su una colonna e aggrega
     */
    public function groupBy(array $data, string $column, callable $aggregator): array
    {
        $groups = [];
        
        foreach ($data as $row) {
            $key = $row[$column] ?? 'NULL';
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $row;
        }
        
        $result = [];
        foreach ($groups as $key => $group) {
            $result[$key] = $aggregator($group);
        }
        
        return $result;
    }
}