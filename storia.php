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
                            <li class="breadcrumb-item text-white active" aria-current="page">La Nostra Storia</li>
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


    <!-- About Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="about-img">
                        <img class="img-fluid" src="img/logo.png">
                    </div>
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

                    <p class="mb-4">Con il passare del tempo, la Lega si espanse, accogliendo nuovi adepti e
                      salutando vecchi amici che intrapresero strade diverse. Ma l'essenza rimase intatta,
                      intessuta di momenti indelebili e legami indissolubili. Ricordi di scherzi e litigi,
                      di vittorie esaltanti e sconfitte amare, si fusero insieme, forgiando l'anima stessa della Lega.
                      Riviviamo insieme i momenti principali:</p>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->


    <!-- Timeline Start -->
    <div class="timeline">
      <div class="year">
        <div class="inner">
          <span>2016</span>
        </div>
      </div>

      <ul class="days">
        <li class="day">
          <div class="events">
            <p>Nel cuore di sei amici nasce la Lega FantaScarrupat, un'avventura che inizia tra i
              corridoi della scuola e si evolve nella passione per il calcio. La
              prima stagione segna l'inizio di un'epopea indimenticabile, fatta di sfide e di amicizia.
            </p>
            <div class="date">Stagione 2016/2017</div>
          </div>
        </li>

        <li class="day">
          <div class="events">
            <p>Espansione da sei a otto partecipanti e la prima rivoluzione dei Fantallenatori,
              la Lega attraversò il suo primo periodo di trasformazione.</p>
            <div class="date">Stagione 2017/2018</div>
          </div>
        </li>

        <li class="day">
          <div class="events">
            <p>Introduzione del regolamento scritto e la rigorosa formalizzazione di ogni evento, competizione e
              innovazione della Lega, si stabilì un nuovo standard di ordine e chiarezza,
              elevando l'organizzazione della Lega a nuove vette di professionalità e precisione.</p>
            <div class="date">Stagione 2020/2021</div>
          </div>
        </li>

        <li class="day">
          <div class="events">
            <p>La seconda rivoluzione dei Fantallenatori, che vide il clamoroso avvicendamento di due
              partecipanti, scosse le fondamenta della Lega, portando con sé un'aria di rinnovamento e di sfida.</p>
            <div class="date">Stagione 2021/2022</div>
          </div>
        </li>

        <li class="day">
          <div class="events">
            <p>Con la nascita ed il debutto del nuovo sito web, la Lega trovò un
              fulcro digitale per la sua gestione e accoglienza. Questo nuovo strumento
              non solo rappresentava un trampolino di lancio verso l'era moderna, ma divenne
              anche il centro nevralgico per il controllo e la coordinazione di ogni aspetto
              della Lega, segnando così un passo epocale nella sua evoluzione tecnologica.
            </p>
            <div class="date">Stagione 2022/2023</div>
          </div>
        </li>


        <li class="day">
          <div class="events">
            <p>La terza piccola rivoluzione dei Fantallenatori,
              caratterizzata dalla sostituzione di un singolo partecipante,
              aggiunse un nuovo tassello al mosaico dell'evoluzione della Lega,
              confermando la sua capacità di adattarsi e rinnovarsi continuamente
              nel suo percorso verso la grandezza.
            </p>
            <div class="date">Stagione 2023/2024</div>
          </div>
        </li>


        <li class="day">
          <div class="events">
            <p>
              L'evoluzione del sito web è stata un viaggio straordinario,
              trasformandolo da una semplice piattaforma statica a un portale dinamico,
              moderno e sempre aggiornato. Questa metamorfosi ha reso il sito non solo uno
              strumento pratico per la gestione della lega, ma anche un
              archivio prezioso di ricordi e stagioni passate. Ogni dettaglio è stato
              curato con precisione, tenendo conto delle mutevoli stagioni e delle esigenze
              dei Fantallenatori, garantendo così un'esperienza online coinvolgente e ricca di emozioni.
            </p>
            <div class="date">Stagione 2024/2025</div>
          </div>
        </li>

      <div class="year year--end">
        <div class="inner">
          <span>
            <?php
              if(date("n") > 8)
              {
                $anno = date("Y") + 1;
              } else
              {
                $anno = date("Y");
              }
              echo $anno;
            ?>
          </span>
        </div>
      </div>
    </div>
    <!-- Timeline End -->


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
