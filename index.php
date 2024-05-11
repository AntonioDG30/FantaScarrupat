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
    <div id="cookie-consent-popup" style="display: none;">
      <p>Questo sito utilizza i cookie per garantire una migliore esperienza di navigazione. Clicca su Accetta per accettare l'utilizzo dei cookie.</p>
      <button id="accept-cookie-btn">Accetta</button>
    </div>
    <?php
      global $conn;
      global $active_users;
      global $total_views;

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
                    <a href="contact.html" class="btn btn-outline-light py-sm-3 px-sm-5 rounded-pill animated slideInRight">Contattaci</a>
                </div>
                <div class="col-lg-6 align-self-end text-center text-lg-end">
                    <img class="img-fluid" src="img/player.png" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->


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
                    <a class="btn btn-primary rounded-pill px-4 me-3" href="storia.php">Leggi di più</a>
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
                                        <a class="btn px-3 mt-auto mx-auto" href="fotoGallery.php">Approfondisci</a>
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
      // Funzione per impostare un cookie di consenso
      function setCookie(name, value, days) {
        var expires = "";
        if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
          expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
      }

      // Funzione per mostrare il popup di accettazione dei cookie
      function showCookieConsentPopup() {
        var cookiePopup = document.getElementById('cookie-consent-popup');
        cookiePopup.style.display = 'block';
      }

      // Funzione per nascondere il popup di accettazione dei cookie
      function hideCookieConsentPopup() {
        var cookiePopup = document.getElementById('cookie-consent-popup');
        cookiePopup.style.display = 'none';
      }

      // Verifica se è già stato dato il consenso ai cookie
      var consentGiven = document.cookie.indexOf('consent_cookie=true') !== -1;

      // Se il consenso non è stato già dato, mostra il popup di accettazione dei cookie
      if (!consentGiven) {
        showCookieConsentPopup();
      }

      // Gestione del click sul pulsante di accettazione dei cookie
      var acceptCookieBtn = document.getElementById('accept-cookie-btn');
      acceptCookieBtn.addEventListener('click', function() {
        // Imposta il cookie di consenso con una durata di 365 giorni
        setCookie('consent_cookie', 'true', 365);
        // Nascondi il popup di accettazione dei cookie
        hideCookieConsentPopup();
      });
    </script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <?php
    $conn->close();
    ?>
</body>

</html>
