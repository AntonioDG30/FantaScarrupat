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
    <link href="img/icon.svg" rel="stylesheet">

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
    <div class="container-fluid pt-5 bg-primary hero-header mb-5">
        <div class="container pt-5">
            <div class="row g-5 pt-5">
                <div class="col-lg-6 align-self-center text-center text-lg-start mb-lg-5">
                    <h1 class="display-4 text-white mb-4 animated slideInRight">
                      <span class="text-color1">Fanta</span><span class="text-color2">Scarrupat</span>
                    </h1>
                    <p class="text-white mb-4 animated slideInRight">Benvenuti nel mondo straordinario di Fantascarrupat,
                      dove la passione per il calcio si trasforma in una vera e propria leggenda! Esplora la nostra storia,
                      scopri quali sono state le sfide più affinchenti e chi è il campione in carica!</p>
                    <a href="partecipanti.php" class="btn btn-light py-sm-3 px-sm-5 rounded-pill me-3 animated slideInRight">Partecipanti</a>
                    <a href="contattaci.php" class="btn btn-outline-light py-sm-3 px-sm-5 rounded-pill animated slideInRight">Contattaci</a>
                </div>
                <div class="col-lg-6 align-self-end text-center text-lg-end">
                    <img class="img-fluid" src="img/player.png" alt="">
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


    <!-- About Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s" align="center">
                        <img src="img/logo.png" height="100%" width="100%">
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <h1 class="mb-4">La Nostra Storia</h1>
                    <p class="mb-4">Nel lontano 2016, da un nucleo di sei giovani impavidi, dalla mente fervida di passione calcistica,
                      sorse un'idea destinata a plasmare il loro destino e a cementare un legame indissolubile.
                      Giovani ardimentosi, con età compresa tra i 15 e i 16 anni, affrontavano insieme i banchi della scuola,
                      una piccola istituzione chiamata "ITI & LS F. Giordani" a Caserta.
                      Ma non era solo l'istruzione a unirli; era il calcio, una fiamma che ardeva nei loro cuori,
                      che li spinse oltre il semplice compito di studenti.</p>
                    <p class="mb-4">Fu su un mezzo di trasporto, un modesto autobus della ditta "Castaldo", che
                      il destino tessé la trama della loro epopea. L'autobus, grigio e anonimo agli occhi dei più,
                      divenne il loro santuario, il palcoscenico delle loro gesta e dei loro battibecchi. Era lì che
                      nacque la Lega FantaScarrupat, una congrega di amici uniti da un'unica passione, un rifugio sicuro
                      e caloroso che li accolse durante gli anni burrascosi delle superiori.</p>

                  <div class="d-flex align-items-center text-black mb-3">
                    <div class="btn-sm-square bg-blu text-primary rounded-circle me-3">
                      <i class="fa fa-check" style="color: white;"></i>
                    </div>
                    <span><strong>Passione</strong>: La nostra lega è alimentata dalla pura passione per il calcio</span>
                  </div>
                  <div class="d-flex align-items-center text-black mb-3">
                    <div class="btn-sm-square bg-blu text-primary rounded-circle me-3">
                      <i class="fa fa-check" style="color: white;"></i>
                    </div>
                    <span><strong>Amicizia</strong>: È il collante che tiene insieme la Lega Fantascarrupat</span>
                  </div>
                  <div class="d-flex align-items-center text-black mb-3">
                    <div class="btn-sm-square bg-blu text-primary rounded-circle me-3">
                      <i class="fa fa-check" style="color: white;"></i>
                    </div>
                    <span><strong>Innovazione</strong>: Siamo sempre in cerca di nuove idee e soluzioni</span>
                  </div>
                  <div class="row g-4 pt-3">
                    <div class="col-sm-6">
                      <div class="d-flex rounded p-3" style="background: rgba(256, 256, 256, 0.1);">
                        <i class="fa fa-users fa-3x text-black"></i>
                        <div class="ms-3">
                          <h2 class="text-black mb-0" data-toggle="counter-up">
                            <?php
                              $query = "SELECT COUNT(nome_fantasquadra) FROM fantasquadra";
                              $result = $conn->query($query);
                              if ($result) {
                                $row = $result->fetch_assoc();
                                $count = $row['COUNT(nome_fantasquadra)'];
                                echo $count;
                              }
                            ?>
                          </h2>
                          <p class="text-black mb-0">Fantallenatori storici</p>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-6">
                      <div class="d-flex rounded p-3" style="background: rgba(256, 256, 256, 0.1);">
                        <i class="fa fa-check fa-3x text-black"></i>
                        <div class="ms-3">
                          <h2 class="text-black mb-0" data-toggle="counter-up">
                            <?php
                              $query = "SELECT COUNT(id_competizione_disputata) FROM competizione_disputata";
                              $result = $conn->query($query);
                              if ($result) {
                                $row = $result->fetch_assoc();
                                $count = $row['COUNT(id_competizione_disputata)'];
                                echo $count;
                              }
                            ?>
                          </h2>
                          <p class="text-black mb-0">Competizioni disputate</p>
                        </div>
                      </div>
                    </div>
                    <a class="btn btn-primary rounded-pill px-4 me-3" href="">Leggi di più</a>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->


    <!-- Service Start -->
    <div class="container-fluid bg-light mt-5 py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-5 wow fadeIn" data-wow-delay="0.1s">
                    <h1 class="mb-4">I Nostri Servizi</h1>
                    <p class="mb-4">Esplora la nostra sezione Servizi, il cuore del nostro sito di fantacalcio.
                      Troverai il regolamento ufficiale, l'albo d'oro dei campioni, i partecipanti e una
                      fotogallery emozionante. Benvenuto nel mondo del fantacalcio, dove ogni dettaglio
                      conta per una stagione di gioco indimenticabile!
                    </p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="row g-4">
                                <div class="col-12 wow fadeIn" data-wow-delay="0.1s">
                                    <div class="service-item d-flex flex-column justify-content-center text-center rounded">
                                        <div class="service-icon btn-square">
                                          <img src="img/regolamento.png" width="40%" height="50%">
                                        </div>
                                        <h5 class="mb-3">Regolamento</h5>
                                        <p> Il fondamento del nostro gioco. Qui troverai tutte le regole ufficiali
                                          che governano la nostra lega, garantendo una competizione equa
                                          e appassionante per tutti i partecipant</p>
                                        <a class="btn px-3 mt-auto mx-auto" href="">Approfondisci</a>
                                    </div>
                                </div>
                                <div class="col-12 wow fadeIn" data-wow-delay="0.5s">
                                    <div class="service-item d-flex flex-column justify-content-center text-center rounded">
                                        <div class="service-icon btn-square">
                                          <img src="img/partecipanti.png" width="70%" height="70%">
                                        </div>
                                        <h5 class="mb-3">Partecipanti</h5>
                                        <p>La comunità che dà vita al gioco. Conosci i giocatori attuali e passati,
                                          dai veterani ai nuovi arrivati, che si sfidano ogni anno per la gloria finale.</p>
                                        <a class="btn px-3 mt-auto mx-auto" href="partecipanti.php">Approfondisci</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 pt-md-4">
                            <div class="row g-4">
                                <div class="col-12 wow fadeIn" data-wow-delay="0.3s">
                                    <div class="service-item d-flex flex-column justify-content-center text-center rounded">
                                        <div class="service-icon btn-square">
                                          <img src="img/albodoro.png" width="50%" height="50%">
                                        </div>
                                        <h5 class="mb-3">Albo D'Oro</h5>
                                        <p>Una vetrina dei campioni. Scopri i vincitori delle passate stagioni,
                                          i loro trionfi e le loro sfide più affincenti che li hanno portati alla gloria
                                          eterna.</p>
                                        <a class="btn px-3 mt-auto mx-auto" href="">Approfondisci</a>
                                    </div>
                                </div>
                                <div class="col-12 wow fadeIn" data-wow-delay="0.7s">
                                    <div class="service-item d-flex flex-column justify-content-center text-center rounded">
                                        <div class="service-icon btn-square">
                                          <img src="img/galleria.png" width="50%" height="40%">
                                        </div>
                                        <h5 class="mb-3">Foto Gallery</h5>
                                        <p> Immagini che raccontano storie. Rivivi i momenti più emozionanti
                                          e indimenticabili del nostro fantacalcio attraverso una collezione esclusiva
                                          di fotografie che catturano l'essenza della competizione e
                                          della passione per il calcio.</p>
                                        <a class="btn px-3 mt-auto mx-auto" href="">Approfondisci</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Service End -->


    <!-- FAQs Start -->
    <!--
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="mx-auto text-center wow fadeIn" data-wow-delay="0.1s" style="max-width: 500px;">
                <div class="btn btn-sm border rounded-pill text-primary px-3 mb-3">Popular FAQs</div>
                <h1 class="mb-4">Frequently Asked Questions</h1>
            </div>
            <div class="row">
                <div class="col-lg-6">
                  <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <img src="img/logo.png">
                  </div>
                </div>
                <div class="col-lg-6">
                    <div class="accordion" id="accordionFAQ2">
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.5s">
                          <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              Will you maintain my site for me?
                            </button>
                          </h2>
                          <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                               data-bs-parent="#accordionFAQ2">
                            <div class="accordion-body">
                              Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                            </div>
                          </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.5s">
                          <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              Will you maintain my site for me?
                            </button>
                          </h2>
                          <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                               data-bs-parent="#accordionFAQ2">
                            <div class="accordion-body">
                              Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                            </div>
                          </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.5s">
                          <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              Will you maintain my site for me?
                            </button>
                          </h2>
                          <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                               data-bs-parent="#accordionFAQ2">
                            <div class="accordion-body">
                              Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                            </div>
                          </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.5s">
                          <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              Will you maintain my site for me?
                            </button>
                          </h2>
                          <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                               data-bs-parent="#accordionFAQ2">
                            <div class="accordion-body">
                              Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                            </div>
                          </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.5s">
                          <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              Will you maintain my site for me?
                            </button>
                          </h2>
                          <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                               data-bs-parent="#accordionFAQ2">
                            <div class="accordion-body">
                              Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                            </div>
                          </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.5s">
                          <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                              Will you maintain my site for me?
                            </button>
                          </h2>
                          <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive"
                               data-bs-parent="#accordionFAQ2">
                            <div class="accordion-body">
                              Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                            </div>
                          </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.6s">
                            <h2 class="accordion-header" id="headingSix">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                    I’m on a strict budget. Do you have any low cost options?
                                </button>
                            </h2>
                            <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix"
                                data-bs-parent="#accordionFAQ2">
                                <div class="accordion-body">
                                    Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.7s">
                            <h2 class="accordion-header" id="headingSeven">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                    Will you maintain my site for me?
                                </button>
                            </h2>
                            <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven"
                                data-bs-parent="#accordionFAQ2">
                                <div class="accordion-body">
                                    Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item wow fadeIn" data-wow-delay="0.8s">
                            <h2 class="accordion-header" id="headingEight">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                    I’m on a strict budget. Do you have any low cost options?
                                </button>
                            </h2>
                            <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight"
                                data-bs-parent="#accordionFAQ2">
                                <div class="accordion-body">
                                    Dolor nonumy tempor elitr et rebum ipsum sit duo duo. Diam sed sed magna et magna diam aliquyam amet dolore ipsum erat duo. Sit rebum magna duo labore no diam.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    -->

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
    <?php
    $conn->close();
    ?>
</body>

</html>
