<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
  header("Location: ../Admin.php?tab=dashboard");
  exit;
}

// Include the database connection file
global $conn;
include 'connectionDB.php';

// Check if a file has been uploaded
if (isset($_FILES['fileCalciatori'])) {
  $file_error = $_FILES['fileCalciatori']['error'];

  // Check if there are no errors in the file upload
  if ($file_error === UPLOAD_ERR_OK) {
    $fileCalciatori = $_FILES['fileCalciatori']['tmp_name'];

    // Open the CSV file in read mode
    $handle = fopen($fileCalciatori, 'r');

    // Check if the file has been opened correctly
    if (!$handle) {
      header("Location: ../Admin.php?tab=calciatori&check=Impossibile aprire il file CSV.");
      exit;
    }

    // Check for BOM and remove it if present
    $first_line = fgets($handle);
    if (substr($first_line, 0, 3) == "\xEF\xBB\xBF") {
      $first_line = substr($first_line, 3);
    }

    // Rewind the file pointer to the beginning of the file
    fseek($handle, 0);

    // Read the file line by line
    $first_row = true;
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
      // Skip the first line if it contains the BOM
      if ($first_row && substr($row[0], 0, 3) == "\xEF\xBB\xBF") {
        $row[0] = substr($row[0], 3);
      }
      $first_row = false;

      // Check that the necessary columns exist
      if (!isset($row[0]) || !isset($row[2]) || !isset($row[3]) || !isset($row[9])) {
        fclose($handle);
        header("Location: ../Admin.php?tab=calciatori&check=Il file CSV non contiene le colonne richieste.");
        exit;
      }

      // Get only the desired columns (1, 3, 4, and 10)
      $codice_fantacalcio = trim($conn->real_escape_string($row[0]));
      $nome_giocatore = trim($conn->real_escape_string($row[2]));
      $ruolo = trim($conn->real_escape_string($row[3]));
      $squadra_reale = trim($conn->real_escape_string($row[9])); // the 10th column

      // Ensure codice_fantacalcio is an integer
      if (!is_numeric($codice_fantacalcio)) {
        fclose($handle);
        header("Location: ../Admin.php?tab=calciatori&check=Valore non valido per codice_fantacalcio: $codice_fantacalcio");
        exit;
      }

      // SQL query to check if a row with the same columns already exists in the giocatore table
      $check_query = "SELECT * FROM giocatore WHERE codice_fantacalcio = '$codice_fantacalcio' AND nome_giocatore = '$nome_giocatore' AND ruolo = '$ruolo' AND squadra_reale = '$squadra_reale'";
      $result = $conn->query($check_query);

      // If there is no row with the same columns, generate the SQL insert command
      if ($result->num_rows == 0) {
        $sql_command = "INSERT INTO giocatore (codice_fantacalcio, nome_giocatore, ruolo, squadra_reale) VALUES ('$codice_fantacalcio', '$nome_giocatore', '$ruolo', '$squadra_reale')";
        $stmt = $conn->prepare($sql_command);
        if (!$stmt) {
          fclose($handle);
          header("Location: ../Admin.php?tab=calciatori&check=Errore nella preparazione della query: " . $conn->error);
          exit;
        }
        $stmt->execute();
      }
    }

    // Close the file
    fclose($handle);
    header("Location: ../Admin.php?tab=calciatori&check=Calciatori importati con successo");
    exit;
  } else {
    // Handle upload errors
    switch ($file_error) {
      case UPLOAD_ERR_INI_SIZE:
        $error_message = "Il file caricato supera la dimensione massima consentita.";
        break;
      case UPLOAD_ERR_FORM_SIZE:
        $error_message = "Il file caricato supera la dimensione massima consentita nel form HTML.";
        break;
      case UPLOAD_ERR_PARTIAL:
        $error_message = "Il file è stato caricato solo parzialmente.";
        break;
      case UPLOAD_ERR_NO_FILE:
        $error_message = "Nessun file è stato caricato.";
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $error_message = "Manca una cartella temporanea.";
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $error_message = "Impossibile scrivere il file sul disco.";
        break;
      case UPLOAD_ERR_EXTENSION:
        $error_message = "Un'estensione PHP ha bloccato l'upload del file.";
        break;
      default:
        $error_message = "Si è verificato un errore durante l'upload del file.";
        break;
    }
    header("Location: ../Admin.php?tab=calciatori&check=$error_message");
    exit;
  }
}
?>
