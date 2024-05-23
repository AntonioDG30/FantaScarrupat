<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>AI.Tech - Artificial Intelligence HTML Template</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="" name="keywords">
  <meta content="" name="description">

  <!-- Favicon -->
  <link href="img/favicon.ico" rel="icon">

  <!-- Google Web Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Ubuntu:wght@500;700&display=swap"
        rel="stylesheet">

  <!-- Icon Font Stylesheet -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">


  <!-- Libraries Stylesheet -->
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

  <!-- Customized Bootstrap Stylesheet -->
  <link href="css/bootstrap.min.css" rel="stylesheet">

  <!-- Template Stylesheet -->
  <link href="css/style.css" rel="stylesheet">
</head>

<body>

<?php
    global $conn;
    include 'php/contVisual.php';
?>
<!-- Spinner Start -->
<div id="spinner"
     class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
  <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
    <span class="sr-only">Loading...</span>
  </div>
</div>
<!-- Spinner End -->

<?php
include 'navbar.html';
?>


<!-- Hero Start -->
<div class="container-fluid pt-5 bg-primary hero-header">
  <div class="container pt-5">
    <div class="row g-5 pt-5">
      <div class="col-lg-6 align-self-center text-center text-lg-start mb-lg-5">
        <h1 class="display-4 text-white mb-4 animated slideInRight">
          <span class="text-color1">Fanta</span><span class="text-color2">Scarrupat</span>
        </h1>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb justify-content-center justify-content-lg-start mb-0">
            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
            <li class="breadcrumb-item text-white active" aria-current="page">Foto Gallery</li>
          </ol>
        </nav>
      </div>
      <div class="col-lg-6 align-self-end text-center text-lg-end">
        <img class="img-fluid" src="img/hero-img.png" alt="" style="max-height: 300px;">
      </div>
    </div>
  </div>
</div>
<!-- Hero End -->

<!-- Albo D'Oro Start -->
<div class="container-fluid destination py-5">
  <div class="container py-5">
    <div class="mx-auto text-center mb-5" style="max-width: 900px;">
      <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
        <h1 class="display-6 mb-5">Albo D'Oro</h1>
      </div>
    </div>
    <div class="tab-class text-center">
      <ul class="nav nav-pills d-inline-flex justify-content-center mb-5">
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill active" data-bs-toggle="pill" href="#SerieA">
            <span class="text-dark" style="width: 150px;">SerieA</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex py-2 mx-3 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#ChampionsLeague">
            <span class="text-dark" style="width: 150px;">Champions League</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#CoppaItalia">
            <span class="text-dark" style="width: 150px;">Coppa Italia</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#BattleRoyale">
            <span class="text-dark" style="width: 150px;">Battle Royale</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#Formula1">
            <span class="text-dark" style="width: 150px;">Formula 1</span>
          </a>
        </li>
      </ul>
      <div class="tab-content">
        <div id="SerieA" class="tab-pane fade show p-0 active">
          <div class="row">
            <?php
            $query = "SELECT C.id_competizione_disputata, C.anno, F.nome_fantasquadra, F.scudetto, F.fantallenatore, C.nome_competizione FROM competizione_disputata as C,
              fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Serie A' ORDER BY C.anno DESC";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="col-lg-3 col-md-6">
                  <a href="#" class="dettagli-link" data-competizione="<?php echo $row["id_competizione_disputata"]?>" data-anno="<?php echo $row["anno"]; ?>">
                    <div class="single-unique-product">
                      <div class="descAnno">
                        <h4>
                          <?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?>
                        </h4>
                      </div>
                      <img class="img-fluid" src="img/scudetti/<?PHP echo$row["scudetto"]?>" alt="">
                      <div class="descVin">
                        <h4>
                          <?PHP echo $row["nome_fantasquadra"]?>
                        </h4>
                        <h6>
                          <?PHP echo $row["fantallenatore"]?>
                        </h6>
                      </div>
                    </div>
                  </a>
                </div>

                <?php
              }
            }
            ?>
          </div>

          <br>
          <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
            <h1 class="display-6 mb-5">Palmares</h1>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table">
                  <thead class="thead-primary">
                  <tr>
                    <th colspan="2">FantaSquadra</th>
                    <th>Allenatore</th>
                    <th>Vittorie</th>
                    <th>Estendi</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $query = "SELECT COUNT(id_competizione_disputata) as vittorie, F.nome_fantasquadra, F.scudetto, F.fantallenatore,
                          GROUP_CONCAT(DISTINCT CONCAT(' ', (C.anno) - 1), '/', C.anno) AS anni_vittoria
                          FROM competizione_disputata as C, fantasquadra AS F
                          WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Serie A'
                          GROUP BY F.nome_fantasquadra ORDER BY vittorie DESC";
                  $result = $conn->query($query);
                  if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                      ?>
                      <tr>
                        <th><img class="img-fluid-logo" src="img/scudetti/<?php echo $row["scudetto"]?>"></th>
                        <td><?php echo $row["nome_fantasquadra"]?></td>
                        <td><?php echo $row["fantallenatore"]?></td>
                        <td><?php echo $row["vittorie"]?></td>
                        <td><span class="toggle-icon">+</span></td>
                      </tr>
                      <tr class="hidden-row">
                        <td colspan="5">
                          <?php echo $row["anni_vittoria"]?>
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
        </div>
        <div id="ChampionsLeague" class="tab-pane fade show p-0">
          <div class="row">
            <?php
            $query = "SELECT C.id_competizione_disputata, C.anno, F.nome_fantasquadra, F.scudetto, F.fantallenatore, C.nome_competizione FROM competizione_disputata as C,
              fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Champions League' ORDER BY C.anno DESC";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="col-lg-3 col-md-6">
                  <a href="#" class="dettagli-link" data-competizione="<?php echo $row["id_competizione_disputata"]?>" data-anno="<?php echo $row["anno"]; ?>">
                    <div class="single-unique-product">
                      <div class="descAnno">
                        <h4>
                          <?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?>
                        </h4>
                      </div>
                      <img class="img-fluid" src="img/scudetti/<?PHP echo$row["scudetto"]?>" alt="">
                      <div class="descVin">
                        <h4>
                          <?PHP echo $row["nome_fantasquadra"]?>
                        </h4>
                        <h6>
                          <?PHP echo $row["fantallenatore"]?>
                        </h6>
                      </div>
                    </div>
                  </a>
                </div>
                <?php
              }
            }
            ?>
          </div>
          <br>
          <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
            <h1 class="display-6 mb-5">Palmares</h1>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table">
                  <thead class="thead-primary">
                  <tr>
                    <th colspan="2">FantaSquadra</th>
                    <th>Allenatore</th>
                    <th>Vittorie</th>
                    <th>Estendi</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $query = "SELECT COUNT(id_competizione_disputata) as vittorie, F.nome_fantasquadra, F.scudetto, F.fantallenatore,
                          GROUP_CONCAT(DISTINCT CONCAT(' ', (C.anno) - 1), '/', C.anno) AS anni_vittoria
                          FROM competizione_disputata as C, fantasquadra AS F
                          WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Champions League'
                          GROUP BY F.nome_fantasquadra ORDER BY vittorie DESC";
                  $result = $conn->query($query);
                  if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                      ?>
                      <tr>
                        <th><img class="img-fluid-logo" src="img/scudetti/<?php echo $row["scudetto"]?>"></th>
                        <td><?php echo $row["nome_fantasquadra"]?></td>
                        <td><?php echo $row["fantallenatore"]?></td>
                        <td><?php echo $row["vittorie"]?></td>
                        <td><span class="toggle-icon">+</span></td>
                      </tr>
                      <tr class="hidden-row">
                        <td colspan="5">
                          <?php echo $row["anni_vittoria"]?>
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

        </div>
        <div id="CoppaItalia" class="tab-pane fade show p-0">
          <div class="row">
            <?php
            $query = "SELECT C.id_competizione_disputata, C.anno, F.nome_fantasquadra, F.scudetto, F.fantallenatore, C.nome_competizione FROM competizione_disputata as C,
              fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Coppa Italia' ORDER BY C.anno DESC";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="col-lg-3 col-md-6">
                  <a href="#" class="dettagli-link" data-competizione="<?php echo $row["id_competizione_disputata"]?>" data-anno="<?php echo $row["anno"]; ?>">
                    <div class="single-unique-product">
                      <div class="descAnno">
                        <h4>
                          <?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?>
                        </h4>
                      </div>
                      <img class="img-fluid" src="img/scudetti/<?PHP echo$row["scudetto"]?>" alt="">
                      <div class="descVin">
                        <h4>
                          <?PHP echo $row["nome_fantasquadra"]?>
                        </h4>
                        <h6>
                          <?PHP echo $row["fantallenatore"]?>
                        </h6>
                      </div>
                    </div>
                  </a>
                </div>
                <?php
              }
            }
            ?>
          </div>
          <br>
          <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
            <h1 class="display-6 mb-5">Palmares</h1>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table">
                  <thead class="thead-primary">
                  <tr>
                    <th colspan="2">FantaSquadra</th>
                    <th>Allenatore</th>
                    <th>Vittorie</th>
                    <th>Estendi</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $query = "SELECT COUNT(id_competizione_disputata) as vittorie, F.nome_fantasquadra, F.scudetto, F.fantallenatore,
                          GROUP_CONCAT(DISTINCT CONCAT(' ', (C.anno) - 1), '/', C.anno) AS anni_vittoria
                          FROM competizione_disputata as C, fantasquadra AS F
                          WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Coppa Italia'
                          GROUP BY F.nome_fantasquadra ORDER BY vittorie DESC";
                  $result = $conn->query($query);
                  if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                      ?>
                      <tr>
                        <th><img class="img-fluid-logo" src="img/scudetti/<?php echo $row["scudetto"]?>"></th>
                        <td><?php echo $row["nome_fantasquadra"]?></td>
                        <td><?php echo $row["fantallenatore"]?></td>
                        <td><?php echo $row["vittorie"]?></td>
                        <td><span class="toggle-icon">+</span></td>
                      </tr>
                      <tr class="hidden-row">
                        <td colspan="5">
                          <?php echo $row["anni_vittoria"]?>
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
        </div>
        <div id="BattleRoyale" class="tab-pane fade show p-0">
          <div class="row">
            <?php
              $query = "SELECT C.id_competizione_disputata, C.anno, F.nome_fantasquadra, F.scudetto, F.fantallenatore, C.nome_competizione FROM competizione_disputata as C,
              fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Battle Royale' ORDER BY C.anno DESC";
              $result = $conn->query($query);
              if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
            ?>
            <div class="col-lg-3 col-md-6">
              <a href="#" class="dettagli-link" data-competizione="<?php echo $row["id_competizione_disputata"]?>" data-anno="<?php echo $row["anno"]; ?>">
                <div class="single-unique-product">
                  <div class="descAnno">
                    <h4>
                      <?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?>
                    </h4>
                  </div>
                  <img class="img-fluid" src="img/scudetti/<?PHP echo$row["scudetto"]?>" alt="">
                  <div class="descVin">
                    <h4>
                      <?PHP echo $row["nome_fantasquadra"]?>
                    </h4>
                    <h6>
                      <?PHP echo $row["fantallenatore"]?>
                    </h6>
                  </div>
                </div>
              </a>
            </div>
            <?php
                 }
              }
            ?>
          </div>

          <br>
          <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
            <h1 class="display-6 mb-5">Palmares</h1>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table">
                  <thead class="thead-primary">
                  <tr>
                    <th colspan="2">FantaSquadra</th>
                    <th>Allenatore</th>
                    <th>Vittorie</th>
                    <th>Estendi</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $query = "SELECT COUNT(id_competizione_disputata) as vittorie, F.nome_fantasquadra, F.scudetto, F.fantallenatore,
                          GROUP_CONCAT(DISTINCT CONCAT(' ', (C.anno) - 1), '/', C.anno) AS anni_vittoria
                          FROM competizione_disputata as C, fantasquadra AS F
                          WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Battle Royale'
                          GROUP BY F.nome_fantasquadra ORDER BY vittorie DESC";
                  $result = $conn->query($query);
                  if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                      ?>
                      <tr>
                        <th><img class="img-fluid-logo" src="img/scudetti/<?php echo $row["scudetto"]?>"></th>
                        <td><?php echo $row["nome_fantasquadra"]?></td>
                        <td><?php echo $row["fantallenatore"]?></td>
                        <td><?php echo $row["vittorie"]?></td>
                        <td><span class="toggle-icon">+</span></td>
                      </tr>
                      <tr class="hidden-row">
                        <td colspan="5">
                          <?php echo $row["anni_vittoria"]?>
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
        </div>
        <div id="Formula1" class="tab-pane fade show p-0">
          <div class="row">
            <?php
            $query = "SELECT C.id_competizione_disputata, C.anno, F.nome_fantasquadra, F.scudetto, F.fantallenatore, C.nome_competizione FROM competizione_disputata as C,
              fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Formula 1' ORDER BY C.anno DESC";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="col-lg-3 col-md-6">
                  <a href="#" class="dettagli-link" data-competizione="<?php echo $row["id_competizione_disputata"]?>" data-anno="<?php echo $row["anno"]; ?>">
                    <div class="single-unique-product">
                      <div class="descAnno">
                        <h4>
                          <?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?>
                        </h4>
                      </div>
                      <img class="img-fluid" src="img/scudetti/<?PHP echo$row["scudetto"]?>" alt="">
                      <div class="descVin">
                        <h4>
                          <?PHP echo $row["nome_fantasquadra"]?>
                        </h4>
                        <h6>
                          <?PHP echo $row["fantallenatore"]?>
                        </h6>
                      </div>
                    </div>
                  </a>
                </div>
                <?php
              }
            }
            ?>
          </div>
          <br>
          <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
            <h1 class="display-6 mb-5">Palmares</h1>
          </div>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="table">
                  <thead class="thead-primary">
                  <tr>
                    <th colspan="2">FantaSquadra</th>
                    <th>Allenatore</th>
                    <th>Vittorie</th>
                    <th>Estendi</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $query = "SELECT COUNT(id_competizione_disputata) as vittorie, F.nome_fantasquadra, F.scudetto, F.fantallenatore,
                          GROUP_CONCAT(DISTINCT CONCAT(' ', (C.anno) - 1), '/', C.anno) AS anni_vittoria
                          FROM competizione_disputata as C, fantasquadra AS F
                          WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Formula 1'
                          GROUP BY F.nome_fantasquadra ORDER BY vittorie DESC";
                  $result = $conn->query($query);
                  if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                      ?>
                      <tr>
                        <th><img class="img-fluid-logo" src="img/scudetti/<?php echo $row["scudetto"]?>"></th>
                        <td><?php echo $row["nome_fantasquadra"]?></td>
                        <td><?php echo $row["fantallenatore"]?></td>
                        <td><?php echo $row["vittorie"]?></td>
                        <td><span class="toggle-icon">+</span></td>
                      </tr>
                      <tr class="hidden-row">
                        <td colspan="5">
                          <?php echo $row["anni_vittoria"]?>
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
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Albo D'Oro End -->


<?php
include 'footer.html';
?>


<!-- Back to Top -->
<a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top pt-2"><i class="bi bi-arrow-up"></i></a>

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/wow/wow.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/counterup/counterup.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    var toggles = document.querySelectorAll(".toggle-icon");
    toggles.forEach(function(toggle) {
      var icon = toggle;
      var hiddenRow = toggle.closest("tr").nextElementSibling;

      toggle.addEventListener("click", function() {
        hiddenRow.classList.toggle("hidden-row");
        icon.classList.toggle("rotate");
      });
    });
  });
</script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
    var dettagliLinks = document.querySelectorAll('.dettagli-link');

    dettagliLinks.forEach(function(link) {
      link.addEventListener('click', function(event) {
        event.preventDefault();

        var id_competizione = this.getAttribute('data-competizione');
        var anno = this.getAttribute('data-anno');
        if (anno < 2023) {
          swal("Dati non disponibili!", "Mi dispiace, ma per le competizioni antecedenti " +
            "la stagione 2023/2024 non sono disponibili i dettagli!");
        } else {
           window.location.href = "dettagliCompetizione.php?id_competizione=" + id_competizione;
        }
      });
    });
  });
</script>


<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>

</html>
