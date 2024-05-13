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
        <h2 class="main-title">Gestisci Gallery</h2>
        <div class="col-lg-12">
          <div class="users-table table-wrapper">
            <table class="posts-table">
              <thead>
              <tr class="users-table-info">
                <th>Nome</th>
                <th>Descrizione</th>
                <th>Visibilità</th>
                <th>Action</th>
              </tr>
              </thead>
              <tbody id="table-body">
              <?php
              $query = "SELECT * FROM immagine ORDER BY flag_visibile DESC";
              $result = $conn->query($query);
              if ($result->num_rows > 0) {
                $index = 1; // Indice iniziale
                while ($row = $result->fetch_assoc()) {
                  $id_immagine = $row["id_immagine"];
                  ?>
                  <tr>
                    <td><?php echo $row["nome_immagine"]; ?></td>
                    <td><?php echo $row["descrizione_immagine"] ?></td>
                    <td>
                      <?php
                      if ($row["flag_visibile"]) {
                        ?>
                        <div class="table-cell">
                          <span class="badge-success">Visibile</span>
                        </div>
                        <?php
                      } else {
                        ?>
                        <div class="table-cell">
                          <span class="badge-trashed">Non Visibile</span>
                        </div>
                        <?php
                      }
                      ?>
                    </td>
                    <td>
                      <a href="php/cambiaFlagImmagine.php?id_immagine=<?php echo $id_immagine; ?>">
                        Cambia Visibilità
                      </a>
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
