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
      <h2 class="main-title">Gestisci Competizioni</h2>
      <div class="col-lg-12">
        <div class="users-table table-wrapper">
          <table class="posts-table">
            <thead>
            <tr class="users-table-info">
              <th>Nome Competizione</th>
              <th colspan="2">Fantasquadra Vincente</th>
              <th>Fantallenatore Vincente</th>
              <th>Stagione</th>
            </tr>
            </thead>
            <tbody>
            <?php
              $query = "SELECT * FROM competizione_disputata, fantasquadra WHERE nome_fantasquadra = vincitore ORDER BY anno DESC";
              $result = $conn->query($query);
              if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>
            <tr>
              <td>
                <?php echo $row["nome_competizione"]?>
              </td>
              <td>
                <img style="width: 80px; height: 80px;" src="img/fanta/<?php echo $row["scudetto"]?>" alt="logo">
              </td>
              <td>
                <?php echo $row["nome_fantasquadra"]?>
              </td>
              <td>
                  <?php echo $row["fantaallenatore"]?>
              </td>
              <td>
                <?php echo $row["anno"]?>
              </td>
            </tr>
            <?php
                }
              }
            ?>
            </tbody>
          </table>
        </div>
      </div>
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
  </div>
</div>

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
