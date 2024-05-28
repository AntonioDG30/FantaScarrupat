<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FantaScarrupat - Admin</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="./img/favicon.png" type="image/x-icon">
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
    <h2 class="main-title">Gestisci Partecipanti</h2>
    <div class="col-lg-12">
      <div class="users-table table-wrapper">
        <table class="posts-table">
          <thead>
          <tr class="users-table-info">
            <th>Nome e Cognome</th>
            <th>Fantasquadra</th>
            <th>Logo</th>
            <th>Stato</th>
            <th>Rose</th>
          </tr>
          </thead>
          <tbody id="table-body">
          <?php
          $query = "SELECT * FROM fantasquadra ORDER BY flag_attuale DESC";
          $result = $conn->query($query);
          if ($result->num_rows > 0) {
            $index = 1; // Indice iniziale
            while($row = $result->fetch_assoc()) {
              $hiddenRowId = "hidden-row-" . $index; // Genera un ID univoco per l'elemento nascosto
              $nome_fantasquadra = $row["nome_fantasquadra"];
              ?>
              <tr>
                <td><?php echo $row["fantallenatore"]?></td>
                <td><?php echo $nome_fantasquadra?></td>
                <td><img style="width: 80px; height: 80px;" src="img/scudetti/<?php echo $row["scudetto"]?>" alt="logo"></td>
                <td>
                  <?php
                  if ($row["flag_attuale"]) {
                    ?>
                    <div class="table-cell">
                      <span class="badge-success">Attuale</span>
                    </div>
                    <?php
                  } else {
                    ?>
                    <div class="table-cell">
                      <span class="badge-trashed">Passato</span>
                    </div>
                    <?php
                  }
                  ?>
                </td>
                <td>
                  <span class="p-relative">
                        <button class="dropdown-btn transparent-btn" type="button" title="More info">
                          <div class="sr-only">More info</div>
                          <i data-feather="more-horizontal" aria-hidden="true"></i>
                        </button>
                        <ul class="users-item-dropdown dropdown">
                          <li><a href="php/cambiaFlagPartecipante.php?nome_fantasquadra=<?php echo urlencode($nome_fantasquadra); ?>">Cambia Stato</a></li>
                          <li><a href="#" class="toggle-icon" data-row="<?php echo $hiddenRowId ?>">Rose</a></li>
                        </ul>
                      </span>

                </td>
              </tr>
              </tr>
              <!-- Codice HTML per riga nascosta -->
              <tr id="<?php echo $hiddenRowId ?>" class="hidden-row">
                <?php
                /*$query = "SELECT G.nome_giocatore, G.ruolo, G.squadra_reale, R.crediti_pagati, R.anno  FROM giocatore AS G, rosa AS R WHERE R.nome_fantasquadra = '$nome_fantasquadra' AND R.id_giocatore = G.id_giocatore ORDER BY anno DESC";*/
                $query2 = "SELECT DISTINCT anno FROM rosa WHERE nome_fantasquadra = '$nome_fantasquadra' ORDER BY anno DESC";
                $result2 = $conn->query($query2);
                if ($result2->num_rows > 0) {
                  ?>
                    <td colspan="5">
                  <?php
                    while($row2 = $result2->fetch_assoc()) {
                      ?>
                      <a href="visualizzaRose.php?team=<?php echo $nome_fantasquadra?>&year=<?php echo $row2["anno"]?>">
                        <?php echo $row2["anno"]-1, '/',$row2["anno"] ?>
                      </a>
                      <?php
                    }
                      ?>
                    </td>
                    <?php

                } else {
                  ?>
                  <td colspan="5">Nessuna rosa associata a questa squadra</td>
                  <?php
                }
                ?>
              </tr>
              <?php
              $index++; // Incrementa l'indice
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

<script>
  document.addEventListener("DOMContentLoaded", function() {
    var extendLinks = document.querySelectorAll(".toggle-icon");

    extendLinks.forEach(function(link) {
      link.addEventListener("click", function(event) {
        event.preventDefault(); // Evita il comportamento predefinito dell'ancora
        var rowId = link.dataset.row;
        var hiddenRow = document.getElementById(rowId);

        if (hiddenRow.classList.contains("hidden-row")) {
          hiddenRow.classList.remove("hidden-row");
          link.textContent = "Chiudi Rose";
        } else {
          hiddenRow.classList.add("hidden-row");
          link.textContent = "Rose";
        }
      });
    });
  });


</script>







</body>

</html>
