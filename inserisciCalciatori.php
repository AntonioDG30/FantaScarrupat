<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegant Dashboard | Dashboard</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="./img/svg/logo.svg" type="image/x-icon">
    <!-- Custom styles -->
    <link rel="stylesheet" href="./css/style.min.css">
  </head>

  <body>
    <?php
    global $conn;
    include 'navbarAdmin.php';
    include 'php/connectionDB.php';
    ?>
    <main class="main users chart-page" id="skip-target">
      <div class="container">
        <h2 class="main-title">Inserisci Calciatori Reali</h2>
        <form class="sign-up-form form" action="php/insertGiocatori.php" method="post" enctype="multipart/form-data">
          <label class="form-label-wrapper">
            <p class="form-label">File calciatori.csv</p>
            <input class="form-input-file" type="file" id="fileCalciatori" name="fileCalciatori" required>
          </label>
          <?php
            if ($_GET['check'] != null && $_GET['check'] != 'start') {
          ?>
              <br>
              <p class="sign-up__check"><?php echo $_GET['check'] ?></p>
          <?php ;
            }
          ?>
          <br>
          <button class="form-btn primary-default-btn transparent-btn" type="submit">Inserisci</button>
        </form>
      </div>
    </main>
    <!-- ! Footer -->
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
