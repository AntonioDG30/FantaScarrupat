<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Recupera i dati del form
  $name = htmlspecialchars(trim($_POST['name']));
  $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
  $subject = htmlspecialchars(trim($_POST['subject']));
  $message = htmlspecialchars(trim($_POST['message']));

  // Imposta l'email del destinatario
  $to = "antonio.digi30@hotmail.com";

  // Imposta l'oggetto dell'email
  $email_subject = "Nuovo messaggio da: $name";

  // Costruisci il corpo dell'email
  $email_body = "Hai ricevuto un nuovo messaggio dal modulo di contatto.\n\n".
    "Nome: $name\n".
    "Email: $email\n\n".
    "Oggetto: $subject\n\n".
    "Messaggio:\n$message\n";

  // Intestazioni email
  $headers = 'From: "' . $name . '" <' . $email . '>';

  // Invia l'email
  if (mail($to, $email_subject, $email_body, $headers)) {
    // Reindirizza a una pagina di ringraziamento o mostra un messaggio di successo
    header("Location: ../contattaci.php?check=Messaggio inviato con successo!");
  } else {
    // Mostra un messaggio di errore
    header("Location: ../contattaci.php?check=Errore nell'invio del messaggio. Riprova più tardi.");
  }
} else {
  // Mostra un messaggio di errore se il metodo non è POST
  header("Location: ../contattaci.php?check=Metodo di richiesta non valido.");
}
?>
