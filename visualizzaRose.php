<?php

// Includi il file per la connessione al database
global $conn;
include 'php/connectionDB.php';

// Definisci i parametri di default per la squadra e l'anno
$current_team = isset($_GET['team']) ? $_GET['team'] : '';
$current_year = isset($_GET['year']) ? $_GET['year'] : date("Y");

// Ottieni le squadre e gli anni disponibili
$teams_query = "SELECT DISTINCT nome_fantasquadra FROM rosa";
$years_query = "SELECT DISTINCT anno FROM rosa";
$teams_result = $conn->query($teams_query);
$years_result = $conn->query($years_query);

// Popola gli array con i nomi delle squadre e gli anni
$teams = array();
$years = array();

if ($teams_result->num_rows > 0) {
  while ($row = $teams_result->fetch_assoc()) {
    $teams[] = $row['nome_fantasquadra'];
  }
}

if ($years_result->num_rows > 0) {
  while ($row = $years_result->fetch_assoc()) {
    $years[] = $row['anno'];
  }
}

// Esegui la query per ottenere i giocatori della squadra selezionata per l'anno selezionato
$query = "SELECT R.nome_fantasquadra, R.crediti_pagati, G.nome_giocatore, G.squadra_reale, G.ruolo
          FROM rosa AS R, giocatore AS G
          WHERE R.id_giocatore = G.id_giocatore
          AND R.nome_fantasquadra = '$current_team'
          AND R.anno = '$current_year'
          ORDER BY G.ruolo DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Elegant Dashboard | Visualizza Rose</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="./img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="./css/style.min.css">
</head>

<body>
<?php
// Includi il file per la navbar
include 'navbarAdmin.php';
?>

<main class="main users chart-page" id="skip-target">
  <div class="container">
    <h2 class="main-title">Visualizza Rose</h2>
    <div class="select-container">
      <form action="" method="GET">
        <label for="team">Seleziona squadra:</label>
        <select name="team" id="team">
          <?php
          foreach ($teams as $team) {
            echo "<option value='$team'" . ($team == $current_team ? " selected" : "") . ">$team</option>";
          }
          ?>
        </select>

        <label for="year">Seleziona anno:</label>
        <select name="year" id="year">
          <?php
          foreach ($years as $year) {
            echo "<option value='$year'" . ($year == $current_year ? " selected" : "") . ">$year</option>";
          }
          ?>
        </select>

        <button type="submit">Filtra</button>
      </form>
    </div>

    <div class="team-year-header">
      <?php
      if ($current_team && $current_year) {
        echo htmlspecialchars($current_team) . " " . htmlspecialchars($current_year)-1 . "/" . htmlspecialchars($current_year);
      } else {
        echo "Seleziona una squadra e un anno";
      }
      ?>
    </div>

    <div class="col-lg-12">
      <div class="users-table table-wrapper">
        <table class="posts-table">
          <thead>
          <tr class="users-table-info">
            <th>Ruolo</th>
            <th>Nome</th>
            <th>Squadra</th>
            <th>Crediti Pagati</th>
          </tr>
          </thead>
          <tbody id="table-body">
          <?php
          // Popola la tabella con i risultati ottenuti dalla query
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              ?>
              <tr>
                <td><?php echo $row["ruolo"]; ?></td>
                <td><?php echo $row["nome_giocatore"] ?></td>
                <td><?php echo $row["squadra_reale"] ?></td>
                <td><?php echo $row["crediti_pagati"] ?></td>
              </tr>
              <?php
            }
          } else {
            echo "<tr><td colspan='4'>Nessun giocatore trovato per questa squadra e anno.</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- Footer -->
<footer class="footer">
  <div class="container footer--flex">
    <div class="footer-start">
      <p>
        2024 FantaScarrupat -
        <a href="http://fantascarrupat.altervista.org/" target="_blank" rel="noopener noreferrer">
          fantascarrupat.altervista.org
        </a>
      </p>
    </div>
  </div>
</footer>

<!-- Script -->
<!-- Chart library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart library -->
<script src="./plugins/chart.min.js"></script>
<!-- Icons library -->
<script src="plugins/feather.min.js"></script>
<!-- Custom scripts -->
<script src="js/script.js"></script>
</body>

</html>
