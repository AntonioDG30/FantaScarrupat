<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FantaScarrupat - Admin</title>
    <!-- Favicon -->

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
                <th>Numero Parametro</th>
                <th>Descrizione Parametro</th>
                <th>Visibilità</th>
                <th>Action</th>
              </tr>
              </thead>
              <tbody id="table-body">
              <?php
              $query = "SELECT * FROM parametri_rosa ORDER BY flag_visibile DESC";
              $result = $conn->query($query);
              if ($result->num_rows > 0) {
                $index = 1; // Indice iniziale
                while ($row = $result->fetch_assoc()) {
                  $id_parametro = $row["id_parametro"];
                  ?>
                  <tr>
                    <td><?php echo $row["numero_parametro"]; ?></td>
                    <td><?php echo $row["testo_parametro"] ?></td>
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
                      <a href="php/cambiaFlagParametro.php?id_parametro=<?php echo $id_parametro; ?>">
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
