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
include 'php/connectionDB.php';

// Verifica la connessione
if ($conn->connect_error) {
  die("Connessione fallita: " . $conn->connect_error);
}
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
        <h1 class="display-6 mb-5">Foto Gallery</h1>
      </div>
    </div>
    <div class="tab-class text-center">
      <ul class="nav nav-pills d-inline-flex justify-content-center mb-5">
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill active" data-bs-toggle="pill" href="#tab-1">
            <span class="text-dark" style="width: 150px;">All</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex py-2 mx-3 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-2">
            <span class="text-dark" style="width: 150px;">USA</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-3">
            <span class="text-dark" style="width: 150px;">Canada</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-4">
            <span class="text-dark" style="width: 150px;">Europe</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-5">
            <span class="text-dark" style="width: 150px;">China</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-6">
            <span class="text-dark" style="width: 150px;">Singapore</span>
          </a>
        </li>
      </ul>
      <div class="tab-content">
        <div id="tab-1" class="tab-pane fade show p-0 active">
          <div class="row g-4">
            <div class="col-xl-8">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="destination-img">
                    <img class="img-fluid rounded w-100" src="img/destination-1.jpg" alt="">
                    <div class="destination-overlay p-4">
                      <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                      <h4 class="text-white mb-2 mt-3">New York City</h4>
                      <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                    </div>
                    <div class="search-icon">
                      <a href="img/destination-1.jpg" data-lightbox="destination-1"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="destination-img">
                    <img class="img-fluid rounded w-100" src="img/destination-2.jpg" alt="">
                    <div class="destination-overlay p-4">
                      <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                      <h4 class="text-white mb-2 mt-3">Las vegas</h4>
                      <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                    </div>
                    <div class="search-icon">
                      <a href="img/destination-2.jpg" data-lightbox="destination-2"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="destination-img">
                    <img class="img-fluid rounded w-100" src="img/destination-7.jpg" alt="">
                    <div class="destination-overlay p-4">
                      <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                      <h4 class="text-white mb-2 mt-3">Los angelas</h4>
                      <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                    </div>
                    <div class="search-icon">
                      <a href="img/destination-7.jpg" data-lightbox="destination-7"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="destination-img">
                    <img class="img-fluid rounded w-100" src="img/destination-8.jpg" alt="">
                    <div class="destination-overlay p-4">
                      <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                      <h4 class="text-white mb-2 mt-3">Los angelas</h4>
                      <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                    </div>
                    <div class="search-icon">
                      <a href="img/destination-8.jpg" data-lightbox="destination-8"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-4">
              <div class="destination-img h-100">
                <img class="img-fluid rounded w-100 h-100" src="img/destination-9.jpg" style="object-fit: cover; min-height: 300px;" alt="">
                <div class="destination-overlay p-4">
                  <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                  <h4 class="text-white mb-2 mt-3">San francisco</h4>
                  <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                </div>
                <div class="search-icon">
                  <a href="img/destination-9.jpg" data-lightbox="destination-4"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="destination-img">
                <img class="img-fluid rounded w-100" src="img/destination-4.jpg" alt="">
                <div class="destination-overlay p-4">
                  <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                  <h4 class="text-white mb-2 mt-3">Los angelas</h4>
                  <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                </div>
                <div class="search-icon">
                  <a href="img/destination-4.jpg" data-lightbox="destination-4"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="destination-img">
                <img class="img-fluid rounded w-100" src="img/destination-5.jpg" alt="">
                <div class="destination-overlay p-4">
                  <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                  <h4 class="text-white mb-2 mt-3">Los angelas</h4>
                  <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                </div>
                <div class="search-icon">
                  <a href="img/destination-5.jpg" data-lightbox="destination-5"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="destination-img">
                <img class="img-fluid rounded w-100" src="img/destination-6.jpg" alt="">
                <div class="destination-overlay p-4">
                  <a href="#" class="btn btn-primary text-white rounded-pill border py-2 px-3">20 Photos</a>
                  <h4 class="text-white mb-2 mt-3">Los angelas</h4>
                  <a href="#" class="btn-hover text-white">View All Place <i class="fa fa-arrow-right ms-2"></i></a>
                </div>
                <div class="search-icon">
                  <a href="img/destination-6.jpg" data-lightbox="destination-6"><i class="fa fa-plus-square fa-1x btn btn-light btn-lg-square text-primary"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div id="tab-2" class="tab-pane fade show p-0">

          <div class="albo">
            <?php
            $query = "SELECT C.anno, F.nome_fantasquadra, F.scudetto, F.fantaallenatore FROM competizione_disputata as C,
            fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Serie A' ORDER BY C.anno";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
            ?>
            <div class="box">
              <div class="elemento testo"><?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?></div>
              <div class="elemento immagine"><img src="img/fanta/<?PHP echo$row["scudetto"]?>" alt="Immagine"></div>
              <div class="elemento testo"><?PHP echo $row["nome_fantasquadra"]?></div>
              <div class="elemento testo"><?PHP echo $row["fantaallenatore"]?></div>
            </div>
            <?php
            }}
            ?>
          </div>

        </div>
        <div id="tab-3" class="tab-pane fade show p-0">
          <div class="albo">
            <?php
            $query = "SELECT C.anno, F.nome_fantasquadra, F.scudetto, F.fantaallenatore FROM competizione_disputata as C,
            fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Champions League' ORDER BY C.anno";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="box">
                  <div class="elemento testo"><?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?></div>
                  <div class="elemento immagine"><img src="img/fanta/<?PHP echo$row["scudetto"]?>" alt="Immagine"></div>
                  <div class="elemento testo"><?PHP echo $row["nome_fantasquadra"]?></div>
                  <div class="elemento testo"><?PHP echo $row["fantaallenatore"]?></div>
                </div>
                <?php
              }}
            ?>
          </div>
        </div>
        <div id="tab-4" class="tab-pane fade show p-0">
          <div class="albo">
            <?php
            $query = "SELECT C.anno, F.nome_fantasquadra, F.scudetto, F.fantaallenatore FROM competizione_disputata as C,
            fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Coppa Italia' ORDER BY C.anno";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="box">
                  <div class="elemento testo"><?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?></div>
                  <div class="elemento immagine"><img src="img/fanta/<?PHP echo$row["scudetto"]?>" alt="Immagine"></div>
                  <div class="elemento testo"><?PHP echo $row["nome_fantasquadra"]?></div>
                  <div class="elemento testo"><?PHP echo $row["fantaallenatore"]?></div>
                </div>
                <?php
              }}
            ?>
          </div>
        </div>
        <div id="tab-5" class="tab-pane fade show p-0">
          <div class="albo">
            <?php
            $query = "SELECT C.anno, F.nome_fantasquadra, F.scudetto, F.fantaallenatore FROM competizione_disputata as C,
            fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Battle Royale' ORDER BY C.anno";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="box">
                  <div class="elemento testo"><?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?></div>
                  <div class="elemento immagine"><img src="img/fanta/<?PHP echo$row["scudetto"]?>" alt="Immagine"></div>
                  <div class="elemento testo"><?PHP echo $row["nome_fantasquadra"]?></div>
                  <div class="elemento testo"><?PHP echo $row["fantaallenatore"]?></div>
                </div>
                <?php
              }}
            ?>
          </div>
        </div>
        <div id="tab-6" class="tab-pane fade show p-0">
          <div class="albo">
            <?php
            $query = "SELECT C.anno, F.nome_fantasquadra, F.scudetto, F.fantaallenatore FROM competizione_disputata as C,
            fantasquadra AS F WHERE F.nome_fantasquadra = C.vincitore AND nome_competizione = 'Formula 1' ORDER BY C.anno";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                ?>
                <div class="box">
                  <div class="elemento testo"><?PHP echo $row["anno"]-1 ?>/<?PHP echo $row["anno"]?></div>
                  <div class="elemento immagine"><img src="img/fanta/<?PHP echo$row["scudetto"]?>" alt="Immagine"></div>
                  <div class="elemento testo"><?PHP echo $row["nome_fantasquadra"]?></div>
                  <div class="elemento testo"><?PHP echo $row["fantaallenatore"]?></div>
                </div>
                <?php
              }}
            ?>
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
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const galleryLinks = document.querySelectorAll(".gallery-link");
    const overlay = document.querySelector(".overlay");
    const overlayImage = document.querySelector(".overlay-image");

    galleryLinks.forEach(function(link) {
      link.addEventListener("click", function(event) {
        event.preventDefault();
        overlayImage.src = this.querySelector("img").src;
        overlay.style.display = "flex";
      });
    });

    overlay.addEventListener("click", function(event) {
      if (event.target === overlay) {
        overlay.style.display = "none";
      }
    });
  });

</script>

<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>

</html>
