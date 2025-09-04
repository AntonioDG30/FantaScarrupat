<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: ../Admin.php?tab=dashboard");
  exit;
}

// Includi il file di connessione al database
global $conn;
include 'connectionDB.php';

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nome_fantasquadra = $conn->real_escape_string($_POST['nomeFantaSquadra']);
  $nome_fantallenatore = $conn->real_escape_string($_POST['nomeFantallenatore']);

  // Funzione per gestire il caricamento delle immagini
  function upload_image($file, $path, $db_field) {
    global $conn;

    $file_error = $file['error'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_name = $file['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $file_mime = mime_content_type($file_tmp);

    // Definisci i tipi di file permessi e la dimensione massima
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
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
            return $nome_finale;
          } else {
            header("Location: ../Admin.php?tab=partecipanti&check=Errore durante lo spostamento del file caricato.");
            exit;
          }
        } else {
          header("Location: ../Admin.php?tab=partecipanti&check=Il file caricato supera la dimensione massima consentita di 2MB.");
          exit;
        }
      } else {
        header("Location: ../Admin.php?tab=partecipanti&check=Tipo di file non supportato. Sono permessi solo JPG, JPEG, PNG, e GIF.");
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
          header("Location: ../Admin.php?tab=partecipanti&check=" . urlencode($error_msg));
      exit;
    }
  }

  // Carica scudettoFantaSquadra
  $scudetto_nome = upload_image($_FILES['scudettoFantaSquadra'], '../img/scudetti/', 'scudetto');

  // Carica fotoFantallenatore
  $foto_nome = upload_image($_FILES['fotoFantallenatore'], '../img/partecipanti/', 'foto');

  // Query per inserire i dati nel database
  $sql_insert = "INSERT INTO fantasquadra (nome_fantasquadra, scudetto, fantallenatore, immagine_fantallenatore, flag_attuale)
                   VALUES ('$nome_fantasquadra', '$scudetto_nome', '$nome_fantallenatore', '$foto_nome', 1)";

  // Esegui la query
  if ($conn->query($sql_insert) === TRUE) {
    header("Location: ../Admin.php?tab=partecipanti&check=Partecipante inserito con successo");
    exit;
  } else {
    header("Location: ../Admin.php?tab=partecipanti&check=Errore durante l\'inserimento dei dati nel database: " . $conn->error);
    exit;
  }
}
?>
