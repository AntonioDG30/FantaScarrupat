<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: ../index.php");
  exit;
}

// Includi il file di connessione al database
global $conn;
include 'connectionDB.php';

// Verifica se il form è stato inviato
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nome_immagine = $conn->real_escape_string($_POST['nomeFoto']);
  $descrizione_immagine = $conn->real_escape_string($_POST['descFoto']);
  $flag_visibile = '1'; // Imposta il flag visibile a '1' per default

  // Verifica se è stato caricato un file
  if (isset($_FILES['fileImg'])) {
    $file_error = $_FILES['fileImg']['error'];
    $file_tmp = $_FILES['fileImg']['tmp_name'];
    $file_size = $_FILES['fileImg']['size'];
    $file_name = $_FILES['fileImg']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $file_mime = mime_content_type($file_tmp);

    // Definisci i tipi di file permessi e la dimensione massima
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // Verifica se non ci sono errori nell'upload del file
    if ($file_error === UPLOAD_ERR_OK) {
      // Controlla l'estensione del file
      if (in_array($file_ext, $allowed_exts) && in_array($file_mime, $allowed_mimes)) {
        // Controlla la dimensione del file
        if ($file_size <= $max_size) {
          // Verifica se esiste un'immagine con lo stesso nome nel database
          $original_name = $nome_immagine;
          $counter = 1;
          $nome_finale = $nome_immagine . '.' . $file_ext;

          while (true) {
            $sql_check = "SELECT * FROM immagine WHERE nome_immagine = '$nome_finale'";
            $result = $conn->query($sql_check);
            if ($result->num_rows > 0) {
              // Incrementa il contatore e aggiorna il nome dell'immagine
              $nome_finale = $original_name . '_' . $counter . '.' . $file_ext;
              $counter++;
            } else {
              break;
            }
          }

          // Costruisci il percorso di destinazione del file
          $file_dest = "../img/fotoGallery/" . $nome_finale;

          // Sposta il file caricato nella destinazione finale
          if (move_uploaded_file($file_tmp, $file_dest)) {
            // Query per inserire i dati nel database
            $sql_insert = "INSERT INTO immagine (nome_immagine, descrizione_immagine, flag_visibile) VALUES ('$nome_finale', '$descrizione_immagine', '$flag_visibile')";

            // Esegui la query
            if ($conn->query($sql_insert) === TRUE) {
              header("Location: ../gestisciGallery.php");
              exit;
            } else {
              header("Location: ../inserisciImmagini.php?check=Errore durante l'inserimento dei dati nel database: " . $conn->error);
              exit;
            }
          } else {
            header("Location: ../inserisciImmagini.php?check=Errore durante lo spostamento del file caricato.");
            exit;
          }
        } else {
          header("Location: ../inserisciImmagini.php?check=Il file caricato supera la dimensione massima consentita di 2MB.");
          exit;
        }
      } else {
        header("Location: ../inserisciImmagini.php?check=Tipo di file non supportato. Sono permessi solo JPG, JPEG, PNG, e GIF.");
        exit;
      }
    } else {
      // Gestione degli errori di upload
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
      header("Location: ../inserisciImmagini.php?check=$error_msg");
      exit;
    }
  }
}
?>
