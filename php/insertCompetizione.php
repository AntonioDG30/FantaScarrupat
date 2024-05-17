<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit;
}

// Importa connessione con il database
global $conn;
include 'connectionDB.php';

// Includi la libreria PhpSpreadsheet
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Funzione per gestire il caricamento del file calendario
function upload_file($file, $path) {
  $file_error = $file['error'];
  $file_tmp = $file['tmp_name'];
  $file_size = $file['size'];
  $file_name = $file['name'];
  $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
  $file_mime = mime_content_type($file_tmp);

  $allowed_exts = ['xlsx', 'xls'];
  $allowed_mimes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
  $max_size = 2 * 1024 * 1024; // 2MB

  if ($file_error === UPLOAD_ERR_OK) {
    if (in_array($file_ext, $allowed_exts) && in_array($file_mime, $allowed_mimes)) {
      if ($file_size <= $max_size) {
        $original_name = pathinfo($file_name, PATHINFO_FILENAME);
        $counter = 1;
        $nome_finale = $original_name . '.' . $file_ext;

        while (true) {
          if (!file_exists($path . $nome_finale)) {
            break;
          } else {
            $nome_finale = $original_name . '_' . $counter . '.' . $file_ext;
            $counter++;
          }
        }

        $file_dest = $path . $nome_finale;

        if (move_uploaded_file($file_tmp, $file_dest)) {
          return $file_dest;
        } else {
          header("Location: ../inserisciNuovaCompetizione.php?check=Errore durante lo spostamento del file caricato.");
          exit;
        }
      } else {
        header("Location: ../inserisciNuovaCompetizione.php?check=Il file caricato supera la dimensione massima consentita di 2MB.");
        exit;
      }
    } else {
      header("Location: ../inserisciNuovaCompetizione.php?check=Tipo di file non supportato. Sono permessi solo XLSX, XLS.");
      exit;
    }
  } else {
    switch ($file_error) {
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        $error_msg = "Il file caricato supera la dimensione massima consentita.";
        break;
      case UPLOAD_ERR_PARTIAL:
        $error_msg = "Il file è stato caricato solo parzialmente.";
        break;
      case UPLOAD_ERR_NO_FILE:
        $error_msg = "Nessun file è stato caricato.";
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $error_msg = "Manca una cartella temporanea.";
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $error_msg = "Impossibile scrivere il file sul disco.";
        break;
      case UPLOAD_ERR_EXTENSION:
        $error_msg = "Un'estensione PHP ha bloccato l'upload del file.";
        break;
      default:
        $error_msg = "Si è verificato un errore durante l'upload del file.";
        break;
    }
    header("Location: ../inserisciNuovaCompetizione.php?check=$error_msg");
    exit;
  }
}

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $competizione = $conn->real_escape_string($_POST['competizione']);
  $anno = $conn->real_escape_string($_POST['anno']);
  $file_calendario = $_FILES['fileClaendario'];

  if ($competizione == 'nuova_competizione') {
    $nome_competizione = $conn->real_escape_string($_POST['nomeCompetizione']);
    $tipologia = $conn->real_escape_string($_POST['tipologia']);

    // Inserisci la nuova competizione
    $sql_new_competizione = "INSERT INTO competizione (nome_competizione, tipologia) VALUES ('$nome_competizione', '$tipologia')";
    if ($conn->query($sql_new_competizione) === FALSE) {
      header("Location: ../inserisciNuovaCompetizione.php?check=Errore durante l'inserimento della nuova competizione: " . $conn->error);
      exit;
    }
  } else {
    $nome_competizione = $competizione;
  }

  // Carica il file calendario
  $file_path = upload_file($file_calendario, '../file/');

  // Query per inserire la competizione disputata
  $sql_competizione_disputata = "INSERT INTO competizione_disputata (nome_competizione, anno) VALUES ('$nome_competizione', '$anno')";
  if ($conn->query($sql_competizione_disputata) === FALSE) {
    header("Location: ../inserisciNuovaCompetizione.php?check=Errore durante l'inserimento della competizione disputata: " . $conn->error);
    exit;
  }
  $id_competizione_disputata = $conn->insert_id;

  // Recupera i nomi delle squadre dalla tabella fantasquadre
  $query = "SELECT nome_fantasquadra FROM fantasquadra";
  $result = $conn->query($query);
  $nomiSquadre = array();
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $nomiSquadre[] = $row['nome_fantasquadra'];
    }
  }

  // Recupera le tipologie di partite dalla tabella tipologia_partita
  $query = "SELECT tipologia FROM tipologia_partita";
  $result = $conn->query($query);
  $tipologia_partita = array();
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $tipologia_partita[] = $row['tipologia'];
    }
  }

  // Carica il file Excel
  $objPHPExcel = IOFactory::load($file_path);
  $sheet = $objPHPExcel->getActiveSheet();

  // Inizializziamo gli elementi delle partite globali
  $giornata = -1;
  $tipologia = 'Calendario';
  $girone = null;
  $sql = '';

  // Itera sulle righe del foglio di lavoro
  foreach ($sheet->getRowIterator() as $indice => $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false);

    $data = array();
    foreach ($cellIterator as $cell) {
      $data[] = $cell->getValue();
    }

    if (in_array($data[0], $tipologia_partita)) {
      $tipologia = $data[0];
    } else if (in_array($data[0], $nomiSquadre)) {
      estraiValori($data, 0);
    } else if (in_array($data[1], $nomiSquadre)) {
      estraiValori($data, 1);
    } else if (!empty($data[0]) && preg_match('/^\d/', $data[0])) {
      $giornata = $giornata + 2;
    } else {
      continue;
    }
  }

  echo "Competizione inserita correttamente.";
  header("Location: ../inserisciNuovaCompetizione.php?check=Competizione inserita correttamente.");
  exit;
}

function estraiValori($data, $i)
{
  global $giornata, $tipologia, $conn, $girone;

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
  $parts = explode('-', $data[$i]);
  $gol_casa = intval($parts[0]);
  $gol_trasferta = intval($parts[1]);
  $i = $i + 2;

  // Prepara e esegui la query SQL per l'inserimento dei dati
  if (!empty($girone)) {
    $query = "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta, giornata, tipologia, girone) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia', '$girone')";
  } else {
    $query = "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta, giornata, tipologia) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia')";
  }
  if ($conn->query($query) === FALSE) {
    echo "Errore durante l'inserimento della partita: " . $conn->error;
    exit;
  }

  if ($i == 7 && !empty($data[7])) {
    $girone = $data[$i];
    $i++;
  } else {
    $girone = null;
  }

  if (!empty($data[$i])) {
    $giornata = $giornata + 1;
    $nome_fantasquadra_casa = $data[$i];
    $i++;
    $punti_casa = $data[$i];
    $i++;
    $punti_trasferta = $data[$i];
    $i++;
    $nome_fantasquadra_trasferta = $data[$i];
    $i++;
    $parts = explode('-', $data[$i]);
    $gol_casa = intval($parts[0]);
    $gol_trasferta = intval($parts[1]);

    // Prepara e esegui la query SQL per l'inserimento dei dati
    if (!empty($girone)) {
      $query = "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta, giornata, tipologia, girone) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia', '$girone')";
    } else {
      $query = "INSERT INTO partita_avvessario (id_competizione_disputata, nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta, punteggio_casa, punteggio_trasferta, giornata, tipologia) VALUES ('AMMA FA', '$nome_fantasquadra_casa', '$nome_fantasquadra_trasferta', '$gol_casa', '$gol_trasferta', '$punti_casa', '$punti_trasferta', '$giornata', '$tipologia')";
    }
    if ($conn->query($query) === FALSE) {
      echo "Errore durante l'inserimento della partita: " . $conn->error;
      exit;
    }

    $giornata = $giornata - 1;
  }
}
?>
