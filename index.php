<!doctype html>
<html class="no-js" lang="">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>
  <link rel="stylesheet" href="css/style.css">
  <meta name="description" content="">

  <meta property="og:title" content="">
  <meta property="og:type" content="">
  <meta property="og:url" content="">
  <meta property="og:image" content="">
  <meta property="og:image:alt" content="">

  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/icon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="icon.png">

  <link rel="manifest" href="site.webmanifest">
  <meta name="theme-color" content="#fafafa">
</head>

<body>

  <!-- Add your site or application content here -->
  <p>Hello world! This is HTML5 Boilerplate.</p>
  <script src="js/app.js"></script>

  <?php
  // Connessione al database
  $servername = "localhost";
  $username = "root"; // Modifica con il tuo username
  $password = ""; // Modifica con la tua password
  $dbname = "my_fantaScarrupat";

  // Connessione
  $conn = new mysqli($servername, $username, $password, $dbname);

  // Controllo della connessione
  if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
  }

  // Query per ottenere il nome della squadra con id_squadra uguale a 1
  $sql = "SELECT nome_fantasquadra FROM fantasquadra";
  $result = $conn->query($sql);

  // Controllo se la query ha restituito dei risultati
  if ($result->num_rows > 0) {
    // Output dei dati
    while($row = $result->fetch_assoc()) {
      echo "<p>Nome Squadra: " . $row["nome_squadra"] . "</p>";
    }
  } else {
    echo "Nessun risultato trovato";
  }

  // Chiusura della connessione
  $conn->close();
  ?>

</body>

</html>
