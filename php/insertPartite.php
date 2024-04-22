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

// Query per recuperare le tipologie di partite dalla tabella tipologia_partita
$query = "SELECT tipologia FROM tipologia_partita";

// Esegui la query
$result = $conn->query($query);

// Inizializza un array per memorizzare i nomi delle squadre
$tipologia_partita = array();

// Verifica se sono presenti risultati
if ($result->num_rows > 0) {
  // Itera sui risultati e aggiungi i nomi delle squadre all'array
  while ($row = $result->fetch_assoc()) {
    $tipologia_partita[] = $row['tipologia'];
  }
}

// Carica il file Excel
$objPHPExcel = IOFactory::load('../file/Calendario_Serie-A.xlsx');

// Seleziona il foglio di lavoro (presumo che il foglio di lavoro sia il primo, puoi modificare di conseguenza se necessario)
$sheet = $objPHPExcel->getActiveSheet();

// Inizializziamo gli elementi delle partite globali
$giornata = -1;
$tipologia = 'Calendario';
$girone = null;

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

  if (in_array($data[0], $tipologia_partita)) {
    $tipologia = $data[0];
  } else if (in_array($data[0], $nomiSquadre)) {
    // Richiamiamo la funzione per estrarre i valori dal file
    estraiValori($data, 0, $sql);
  } else if (in_array($data[1], $nomiSquadre)) {
    // Richiamiamo la funzione per estrarre i valori dal file
    estraiValori($data, 1, $sql);
  } else if (!empty($data[0]) && preg_match('/^\d/', $data[0])) {
    // Aggiorniamo la giornata
    $giornata = $giornata + 2;
  } else {
    continue;
  }
}

// Scrivi la stringa SQL in un file .sql
$file = 'query_insert_partite.sql';
file_put_contents($file, $sql);

echo "Query SQL salvata correttamente nel file $file";

function estraiValori($data, $i)
{
  // Estrai i dati relativi alla partita
  global $giornata, $tipologia, $sql, $girone;

  if ($i == 1 && !empty($data[0])) {
    $girone = $data[0];
  } else {
    $girone = null;
  }

  $nome_fantasquadra_casa = $data[$i];
  $i++;
  $punti_casa = $data[$i];
  $i++;
  $punti_trasferta = $data[$i];
  $i++;
  $nome_fantasquadra_trasferta = $data[$i];
  $i++;
  //Salviamo il risultato in due variabili diverse
  $parts = explode('-', $data[$i]);
  $gol_casa = intval($parts[0]);
  $gol_trasferta = intval($parts[1]);
  $i = $i + 2;

  // Prepara la query SQL per l'inserimento dei dati e aggiungila alla stringa SQL
  if (!empty($girone)) {
    $sql .= "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punti_casa, punti_trasferta, giornata, tipologia, girone) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia', '$girone');\n";
  } else {
    $sql .= "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punti_casa, punti_trasferta, giornata, tipologia) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia');\n";
  }
  if ($i == 7 && !empty($data[7])) {
    $girone = $data[$i];
    $i++;
  } else {
    $girone = null;
  }

  if (!empty($data[$i])) {
    // Aggiorniamo la giornata
    $giornata = $giornata + 1;

    // Estrai i dati relativi alla partita
    $nome_fantasquadra_casa = $data[$i];
    $i++;
    $punti_casa = $data[$i];
    $i++;
    $punti_trasferta = $data[$i];
    $i++;
    $nome_fantasquadra_trasferta = $data[$i];
    $i++;
    //Salviamo il risultato in due variabili diverse
    $parts = explode('-', $data[$i]);
    $gol_casa = intval($parts[0]);
    $gol_trasferta = intval($parts[1]);

    // Prepara la query SQL per l'inserimento dei dati e aggiungila alla stringa SQL
    if (!empty($girone)) {
      $sql .= "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punti_casa, punti_trasferta, giornata, tipologia, girone) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia', '$girone');\n";
    } else {
      $sql .= "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punti_casa, punti_trasferta, giornata, tipologia) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia');\n";
    }

    // Aggiorniamo la giornata
    $giornata = $giornata - 1;
  }
}
?>
