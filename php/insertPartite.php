<?php
  // Importa connessione con il database
  global $conn;
  include 'connectionDB.php';

  // Includi la libreria PhpSpreadsheet
  require '../vendor/autoload.php';

  use PhpOffice\PhpSpreadsheet\IOFactory;


  // Query per recuperare i nomi delle squadre dalla tabella fantasquadre
  $query = "SELECT nome_fantasquadra FROM fantasquadra";

  // Esegui la query
  $result = $conn->query($query);

  // Inizializza un array per memorizzare i nomi delle squadre
  $nomiSquadre = array();

  // Verifica se sono presenti risultati
  if ($result->num_rows > 0) {
    // Itera sui risultati e aggiungi i nomi delle squadre all'array
    while ($row = $result->fetch_assoc()) {
      $nomiSquadre[] = $row['nome_fantasquadra'];
    }
  }

  // Carica il file Excel
  $objPHPExcel = IOFactory::load('../file/Calendario_Serie-A.xlsx');

  // Seleziona il foglio di lavoro (presumo che il foglio di lavoro sia il primo, puoi modificare di conseguenza se necessario)
  $sheet = $objPHPExcel->getActiveSheet();

// Inizializza una stringa per memorizzare la query SQL
  $sql = '';

  // Itera sulle righe del foglio di lavoro
  foreach ($sheet->getRowIterator() as $indice => $row) {
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


    if (in_array($data[0], $nomiSquadre)) {
      // Estrai i dati relativi alla partita
      $fantasquadra_casa = $data[0];
      echo $fantasquadra_casa, "\n";
      $gol_casa = (int)$data[1];
      echo $gol_casa, "\n";
      $punti_casa = (float)$data[2];
      echo $punti_casa, "\n";
      $fantasquadra_trasferta = $data[3];
      echo $fantasquadra_trasferta, "\n";
      $gol_trasferta = $data[4];
      echo $gol_trasferta, "\n";
      $PROVA = $data[6];
      echo $PROVA, "\n";
    } else {
      continue;
    }

    // Prepara la query SQL per l'inserimento dei dati e aggiungila alla stringa SQL
    //$sql .= "INSERT INTO partita_avvessario (nome_fantasquadra_casa, gol_casa, punti_casa, nome_fantasquadra_trasferta, gol_trasferta, punti_trasferta, giornata) VALUES ('$fantasquadra_casa', $gol_casa, $punti_casa, '$fantasquadra_trasferta', $gol_trasferta, $punti_trasferta, $giornata);\n";
  }

  // Scrivi la stringa SQL in un file .sql
  //$file = 'query_insert_partite.sql';
  //file_put_contents($file, $sql);

  //echo "Query SQL salvata correttamente nel file $file";
?>

