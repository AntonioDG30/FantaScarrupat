<?php
require '../vendor/autoload.php'; // Utilizza Composer (raccomandato)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Recupera i dati del form
  $name = htmlspecialchars(trim($_POST['name']));
  $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
  $subject = htmlspecialchars(trim($_POST['subject']));
  $message = htmlspecialchars(trim($_POST['message']));

  // Imposta l'email del destinatario
  $to = "fantascarrupat@gmail.com";

  // Imposta l'oggetto dell'email
  $email_subject = "Nuovo messaggio da: $name";

  // Costruisci il corpo dell'email
  $email_body = "Hai ricevuto un nuovo messaggio dal modulo di contatto.\n\n".
    "Nome: $name\n".
    "Email: $email\n\n".
    "Oggetto: $subject\n\n".
    "Messaggio:\n$message\n";

  // Configurazione SendGrid
  $sendgrid_api_key = '';
  $email = new \SendGrid\Mail\Mail();
  $email->setFrom("fantascarrupat@gmail.com", $name); // Mittente impostato come stesso indirizzo
  $email->setSubject($email_subject);
  $email->addTo($to);
  $email->addContent("text/plain", $email_body);

  $sendgrid = new \SendGrid($sendgrid_api_key);

  try {
    $response = $sendgrid->send($email);
    if ($response->statusCode() == 202) {
      // Reindirizza a una pagina di ringraziamento o mostra un messaggio di successo
      header("Location: ../contattaci.php?check=Messaggio inviato con successo!");
    } else {
      // Mostra un messaggio di errore
      header("Location: ../contattaci.php?check=Errore nell'invio del messaggio. Riprova più tardi.");
    }
  } catch (Exception $e) {
    // Mostra un messaggio di errore con dettagli sull'eccezione
    $error_message = $e->getMessage();
    echo "Errore durante l'invio dell'email: $error_message";
  }
} else {
  // Mostra un messaggio di errore se il metodo non è POST
  header("Location: ../contattaci.php?check=Metodo di richiesta non valido.");
}
?>
