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
                    <h1><span class="text-color1">Fanta</span><span class="text-color2">Scarrupat</span></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center justify-content-lg-start mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="#">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="#">Pages</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">About Us</li>
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


    <!-- Full Screen Search Start -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background: rgba(20, 24, 62, 0.7);">
                <div class="modal-header border-0">
                    <button type="button" class="btn btn-square bg-white btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center">
                    <div class="input-group" style="max-width: 600px;">
                        <input type="text" class="form-control bg-transparent border-light p-3"
                            placeholder="Type search keyword">
                        <button class="btn btn-light px-4"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Full Screen Search End -->


    <!-- Partecipanti Start -->
    <div class="container-fluid guide py-5">
      <div class="container py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 900px;">
          <h5 class="section-title px-3">Lega FantaScarrupat</h5>
          <h1 class="mb-0">Partecipanti Attuali</h1>
        </div>
        <div class="row g-4">
          <?php
            $query = "SELECT * FROM fantasquadra WHERE flag_attuale = 1";
            $result = $conn->query($query);
            if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
          ?>
          <div class="col-md-6 col-lg-3">
            <div class="guide-item">
              <div class="guide-img">
                <div class="guide-img-efects">
                  <img src="img/guide-1.jpg" class="img-fluid w-100 rounded-top" alt="Image">
                </div>
              </div>
              <div class="guide-title text-center rounded-bottom p-4">
                <div class="guide-title-inner">
                  <h4 class="mt-3"><?php echo $row['fantaallenatore']; ?></h4>
                  <p class="mb-0"><?php echo $row['nome_fantasquadra']; ?></p>
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

      <div class="container py-5">
        <div class="mx-auto text-center mb-5" style="max-width: 900px;">
          <h5 class="section-title px-3">Lega FantaScarrupat</h5>
          <h1 class="mb-0">Partecipanti Passati</h1>
        </div>
        <div class="row g-4">
          <?php
          $query = "SELECT * FROM fantasquadra WHERE flag_attuale = 0";
          $result = $conn->query($query);
          if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
              ?>
              <div class="col-md-6 col-lg-3">
                <div class="guide-item">
                  <div class="guide-img">
                    <div class="guide-img-efects">
                      <img src="img/guide-1.jpg" class="img-fluid w-100 rounded-top" alt="Image">
                    </div>
                  </div>
                  <div class="guide-title text-center rounded-bottom p-4">
                    <div class="guide-title-inner">
                      <h4 class="mt-3"><?php echo $row['fantaallenatore']; ?></h4>
                      <p class="mb-0"><?php echo $row['nome_fantasquadra']; ?></p>
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
