<?php
// Includi la libreria PHPExcel
require 'PHPExcel/Classes/PHPExcel.php';

// Carica il file Excel
$objPHPExcel = PHPExcel_IOFactory::load('./file/Calendario_Serie-A.xlsx');

// Seleziona il foglio di lavoro (presumo che il foglio di lavoro sia il primo, puoi modificare di conseguenza se necessario)
$sheet = $objPHPExcel->getActiveSheet();

// Inizializza una stringa per memorizzare la query SQL
$sql = '';

// Itera sulle righe del foglio di lavoro
foreach ($sheet->getRowIterator() as $row) {
  // Ottieni i valori delle celle nella riga corrente
  $cellIterator = $row->getCellIterator();
  $cellIterator->setIterateOnlyExistingCells(false); // Considera tutte le celle, anche se vuote

  // Inizializza un array per memorizzare i dati della partita
  $data = array();

  // Itera sulle celle della riga corrente
  foreach ($cellIterator as $cell) {
    // Aggiungi il valore della cella all'array dei dati
    $data[] = $cell->getValue();
  }

  // Estrai i dati relativi alla partita
  $fantasquadra_casa = $data[0];
  $gol_casa = (int)$data[1];
  $punti_casa = (float)$data[2];
  $fantasquadra_trasferta = $data[3];
  $gol_trasferta = (int)$data[4];
  $punti_trasferta = (float)$data[5];
  $giornata = (int)$data[6];

  // Prepara la query SQL per l'inserimento dei dati e aggiungila alla stringa SQL
  $sql .= "INSERT INTO partita_avvessario (nome_fantasquadra_casa, gol_casa, punti_casa, nome_fantasquadra_trasferta, gol_trasferta, punti_trasferta, giornata) VALUES ('$fantasquadra_casa', $gol_casa, $punti_casa, '$fantasquadra_trasferta', $gol_trasferta, $punti_trasferta, $giornata);\n";
}

// Scrivi la stringa SQL in un file .sql
$file = 'query_insert_partite.sql';
file_put_contents($file, $sql);

echo "Query SQL salvata correttamente nel file $file";
?>
