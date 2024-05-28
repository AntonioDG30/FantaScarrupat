<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FantaScarrupat</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">


    <!-- Favicon -->
    <link href="img/favicon.png" rel="icon">

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
                    <h1><span class="text-color1">Fanta</span><span class="text-color2">Scarrupat</span></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center justify-content-lg-start mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Partecipanti</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-lg-6 align-self-end text-center text-lg-end">
                    <img class="img-fluid" src="img/player_mezzo_busto.png" alt="" style="max-height: 300px;">
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->


    <!-- Partecipanti Start -->
    <div class="container-xxl py-5">
      <div class="container">
        <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
          <div class="d-inline-block rounded-pill bg-secondary text-ball py-1 px-3 mb-3">Lega FantaScarrupat</div>
          <h1 class="display-6 mb-5">Partecipanti Attuali</h1>
        </div>
        <div class="row g-4">
          <?php
          $query = "SELECT * FROM fantasquadra WHERE flag_attuale = 1";
          $result = $conn->query($query);
          if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              $nome_squadra = $row['nome_fantasquadra'];
              ?>
              <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                <div class="team-item position-relative rounded overflow-hidden">
                  <div class="overflow-hidden">
                    <img class="img-fluid-personal" src="img/partecipanti/<?php echo $row["immagine_fantallenatore"]?>" alt=" Errore Recupero Foto Fantallenatore">
                  </div>
                  <div class="team-text bg-light text-center p-4">
                    <h5><?php echo $row['fantallenatore']; ?></h5>
                    <p class="text-primary"><?php echo $nome_squadra; ?></p>
                    <div class="team-social text-center">
                      <?php
                      $query2 = "SELECT COUNT(id_competizione_disputata) AS count FROM competizione_disputata WHERE vincitore = '$nome_squadra'";
                      $result2 = $conn->query($query2);
                      if ($result2->num_rows > 0) {
                        while($row2 = $result2->fetch_assoc()) {
                          $count_vittorie = $row2['count'];
                          ?>
                          <p class="text-dark">Competizioni Vinte: <?php echo $count_vittorie; ?></p>
                          <?php
                        }
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php
            }
          }
          ?>
        </div>
      </div>
    </div>

    <div class="container-xxl py-5">
      <div class="container">
        <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
          <h1 class="display-6 mb-5">Partecipanti Passati</h1>
        </div>
        <div class="row g-4">
          <?php
            $query = "SELECT * FROM fantasquadra WHERE flag_attuale = 0";
            $result = $conn->query($query);
              if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                  $nome_squadra = $row['nome_fantasquadra'];
          ?>
                  <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="team-item position-relative rounded overflow-hidden">
                      <div class="overflow-hidden">
                        <img class="img-fluid-personal" src="img/partecipanti/<?php echo $row["immagine_fantallenatore"]?>" alt=" Errore Recupero Foto Fantallenatore">
                      </div>
                      <div class="team-text bg-light text-center p-4">
                        <h5><?php echo $row['fantallenatore']; ?></h5>
                        <p class="text-primary"><?php echo $nome_squadra; ?></p>
                        <div class="team-social text-center">
                          <?php
                            $query2 = "SELECT COUNT(id_competizione_disputata) AS count FROM competizione_disputata WHERE vincitore = '$nome_squadra'";
                            $result2 = $conn->query($query2);
                            if ($result2->num_rows > 0) {
                              while($row2 = $result2->fetch_assoc()) {
                                $count_vittorie = $row2['count'];
                          ?>
                                <p class="text-dark">Competizioni vinte: <?php echo $count_vittorie; ?></p>
                          <?php
                              }
                            }
                          ?>
                        </div>
                      </div>
                    </div>
                  </div>
          <?php
                }
              }
          ?>
        </div>
      </div>
    </div>
    <!-- Partecipanti End -->


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

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>
