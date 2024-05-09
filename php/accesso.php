<?php
  global $conn;
  include 'connectionDB.php';

  // Funzione per autenticare l'utente
  function authenticateUser($email, $password, $conn) {
    // Cripta la password con SHA-256
    $encrypted_password = hash('sha256', $password);

    // Query per selezionare l'utente dal database
    $query = "SELECT * FROM admin WHERE email = '$email' AND password = '$encrypted_password'";
    $result = $conn->query($query);

    // Se trova un utente corrispondente, crea la sessione e reindirizza alla pagina admin
    if ($result->num_rows > 0) {
      session_start();
      $_SESSION['user'] = $result->fetch_assoc();
      header("Location: ../admin.php");
      exit();
    } else {
      // Se le credenziali sono errate, mostra un messaggio di errore
      header("Location: ../login.php?check=true");
    }
  }

  // Controlla se il modulo di login è stato inviato
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prendi i dati inviati dal modulo di login
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Autentica l'utente
    authenticateUser($email, $password, $conn);
  }
?>

