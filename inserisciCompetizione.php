<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FantaScarrupat - Admin</title>
  <link rel="shortcut icon" href="./img/favicon.png" type="image/x-icon">
  <link rel="stylesheet" href="./css/style.min.css">

  <script>
    function mostraNuovaCompetizione() {
      var competizioneSelezionata = document.getElementById("competizione").value;
      var nuovaCompetizioneDiv = document.getElementById("nuova_competizione");
      var NbaCompetizioneDiv = document.getElementById("nba_competizione");

      if (competizioneSelezionata === "nuova_competizione") {
        nuovaCompetizioneDiv.style.display = "block";
      } else if (competizioneSelezionata === "NBA") {
        NbaCompetizioneDiv.style.display = "block";
      } else {
        nuovaCompetizioneDiv.style.display = "none";
      }
    }

    function validateForm() {
      console.log("Form submitted");
      return true;
    }
  </script>
</head>
<body>
<?php
global $conn;
include 'navbarAdmin.php';
include 'php/connectionDB.php';

$competizioni_query = "SELECT nome_competizione FROM competizione";
$competizioni_result = $conn->query($competizioni_query);

$tipologie_query = "SELECT tipologia FROM tipologia_competizione";
$tipologie_result = $conn->query($tipologie_query);

$anno_query = "SELECT DISTINCT anno FROM competizione_disputata ORDER BY anno DESC";
$anno_result = $conn->query($anno_query);
?>
<main class="main users chart-page" id="skip-target">
  <div class="container">
    <h2 class="main-title">Inserisci Nuova Competizione</h2>
    <form class="sign-up-form form" action="php/insertCompetizione.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
      <label class="form-label-wrapper">
        <p class="form-label">Competizione</p>
        <select id="competizione" name="competizione" onchange="mostraNuovaCompetizione()">
          <?php
          if ($competizioni_result->num_rows > 0) {
            while($row = $competizioni_result->fetch_assoc()) {
              echo "<option value='" . $row["nome_competizione"] . "'>" . $row["nome_competizione"] . "</option>";
            }
          }
          ?>
          <option value="nuova_competizione">Nuova Competizione</option>
        </select>
        <br>
      </label>
      <div id="nuova_competizione" style="display:none;">
        <label class="form-label-wrapper">
          <p class="form-label">Nome Competizione</p>
          <input class="form-input" type="text" name="nomeCompetizione" placeholder="Inserisci Nome Competizione">
        </label>
        <label class="form-label-wrapper">
          <p class="form-label">Competizione</p>
          <select id="tipologia" name="tipologia">
            <?php
            if ($tipologie_result->num_rows > 0) {
              while($row = $tipologie_result->fetch_assoc()) {
                echo "<option value='" . $row["tipologia"] . "'>" . $row["tipologia"] . "</option>";
              }
            }
            ?>
          </select>
          <br>
        </label>
      </div>
      <label class="form-label-wrapper">
        <p class="form-label">File Calendario</p>
        <input class="form-input-file" type="file" id="fileClaendario" name="fileClaendario" required>
      </label>
      <div id="nba_competizione" style="display:none;">
      	<br>
        <label class="form-label-wrapper">
          <p class="form-label">File Calendario 2</p>
          <input class="form-input-file" type="file" id="fileClaendario2" name="fileClaendario2" required>
        </label>
      </div>
      <?php
      if (isset($_GET['check']) && $_GET['check'] != 'start') {
        ?>
        <br>
        <p class="sign-up__check"><?php echo $_GET['check'] ?></p>
        <?php ;
      }
      ?>
      <br>
      <label class="form-label-wrapper">
        <p class="form-label">Anno Competizione</p>
        <select id="anno" name="anno">
          <?php
          if ($anno_result->num_rows > 0) {
            while($row = $anno_result->fetch_assoc()) {
              ?>
              <option value="<?php echo $row["anno"]+1 ?>"><?php echo $row["anno"], '/', $row["anno"]+1 ?></option>
              <?php
            }
          }
          ?>
        </select>
        <br>
      </label>
      <button class="form-btn primary-default-btn transparent-btn" type="submit">Inserisci</button>
    </form>
  </div>
</main>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="./plugins/chart.min.js"></script>
<script src="plugins/feather.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
