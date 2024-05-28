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
    <link href="css/ClassificaCompetizione.css" rel="stylesheet">
</head>

<body>

    <?php
      global $conn;
      global $nome_fantasquadra;
      global $anno;
      $nome_fantasquadra = $_GET['nome_fantasquadra'];
      $anno = $_GET['anno'];
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
                    <img class="img-fluid" src="img/hero-img.png" alt="" style="max-height: 300px;">
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->

    <!-- Dettagli Rosa Start -->
    <div class="container-fluid destination py-5">
      <div class="container py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 900px;">
          <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 700px;">
            <h1 class="display-6 mb-5">Rosa <?php echo $nome_fantasquadra, " del ", $anno?></h1>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="table-responsive">
              <table class="classifica-table">
                <thead>
                <tr>
                  <th>Ruolo</th>
                  <th>Nome Giocatore</th>
                  <th>Squadra Reale</th>
                  <th>Crediti Spesi</th>
                </tr>
                </thead>
                <tbody>
                <?php
                  $query = "SELECT R.nome_fantasquadra, R.crediti_pagati, G.nome_giocatore, G.squadra_reale, G.ruolo
                                  FROM rosa AS R, giocatore AS G
                                  WHERE R.id_giocatore = G.id_giocatore
                                  AND R.nome_fantasquadra = '$nome_fantasquadra'
                                  AND R.anno = '$anno'
                                  ORDER BY G.ruolo DESC";
                  $result = $conn->query($query);

                  $matches_by_day = array();

                  if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                ?>
                      <tr>
                        <td><?php echo $row['ruolo']; ?></td>
                        <td><?php echo $row['nome_giocatore']; ?></td>
                        <td><?php echo $row['squadra_reale']; ?></td>
                        <td><?php echo $row['crediti_pagati']; ?></td>
                      </tr>
                <?php
                    }
                  } else {
                    echo "Nessun giocatore trovato per questa rosa";
                  }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Dettagli Rosa End -->


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
