<?php

global $conn;
require_once 'php/connectionDB.php';
require_once 'navbarAdmin.php';

// Validazione e sanitizzazione del parametro 'anno'
if (isset($_GET['anno']) && is_numeric($_GET['anno'])) {
  $anno = (int) $_GET['anno'];
} else {
  header("Location: ../inserisciRose.php?check=Anno non impostato");
  exit;
}

// Prepara e esegue la query per le rose di Fantasquadra
$stmt = $conn->prepare(
  "SELECT id_rosa, nome_fantasquadra
     FROM rosa
     WHERE anno = ?"
);
$stmt->bind_param("i", $anno);
$stmt->execute();
$rosa_result = $stmt->get_result();
$rose = $rosa_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Prepara e esegue la query per i parametri della rosa
$stmt2 = $conn->prepare(
  "SELECT id_parametro, numero_parametro, testo_parametro
     FROM parametri_rosa
     ORDER BY numero_parametro"
);
$stmt2->execute();
$parametri_result = $stmt2->get_result();
$parametri = $parametri_result->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FantaScarrupat - Admin</title>
  <link rel="shortcut icon" href="./img/favicon.png" type="image/x-icon">
  <link rel="stylesheet" href="./css/style.min.css">
</head>
<body>
<main class="main users chart-page" id="skip-target">
  <div class="container">
    <h2 class="main-title">Imposta Parametri Rose (Anno <?php echo htmlspecialchars($anno, ENT_QUOTES); ?>)</h2>
    <form class="sign-up-form form"
          action="php/impostaParametriDB.php"
          method="post"
          enctype="multipart/form-data"
          onsubmit="return validateForm()">

      <?php if (!empty($rose)): ?>
        <?php foreach ($rose as $index => $r): ?>
          <br><br>
          <p><p style="color:white"> <?php echo htmlspecialchars($r['nome_fantasquadra'], ENT_QUOTES); ?></p></legend>
          <br>

            <?php for ($i = 1; $i <= 2; $i++): ?>
              <select id="parametro_<?php echo $r['id_rosa'] . '_' . $i; ?>"
                      name="parametro[<?php echo $r['id_rosa']; ?>][<?php echo $i; ?>]"
                      class="form-select">
                <?php foreach ($parametri as $p): ?>
                  <option value="<?php echo htmlspecialchars($p['id_parametro'], ENT_QUOTES); ?>">
                    <?php echo $p['numero_parametro'] . '. ' . htmlspecialchars($p['testo_parametro'], ENT_QUOTES); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <br><br>
            <?php endfor; ?>

        <?php endforeach; ?>
      <?php else: ?>
        <p>Nessuna rosa trovata per l'anno selezionato.</p>
      <?php endif; ?>

      <?php if (isset($_GET['check']) && $_GET['check'] !== 'start'): ?>
        <p class="form-error"><?php echo htmlspecialchars($_GET['check'], ENT_QUOTES); ?></p>
      <?php endif; ?>

      <button class="form-btn primary-default-btn" type="submit">
        Inserisci
      </button>
    </form>
  </div>
</main>

<footer class="footer">
  <div class="container footer--flex">
    <div class="footer-start">
      <p>
        2024 FantaScarrupat -
        <a href="http://fantascarrupat.altervista.org/"
           target="_blank"
           rel="noopener noreferrer">
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
