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

<!-- Regolamento Start -->
<div class="container">
  <br><br>
  <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
    <h1 class="display-6 mb-5">Regolamento Lega</h1>
  </div>
  <div class="row">
    <div class="col-lg-4">
      <div class="nav nav-pills faq-nav" id="faq-caps" role="tablist" aria-orientation="vertical">
        <a href="#cap1" class="nav-link active" data-toggle="pill" role="tab" aria-controls="" aria-selected="true">
          La Lega FantaScarrupat
        </a>
        <a href="#cap2" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap2" aria-selected="false">
          Il Gioco
        </a>
        <a href="#cap3" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap3" aria-selected="false">
          Modalità di Calcolo
        </a>
        <a href="#cap4" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap4" aria-selected="false">
          Mercato
        </a>
        <a href="#cap5" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap5" aria-selected="false">
         Gestione Infortuni e Indisponibilità
        </a>
        <a href="#cap6" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap6" aria-selected="false">
           Interruzione del Campionato di Serie A
        </a>
        <a href="#cap7" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap7" aria-selected="false">
          Competizioni
        </a>
        <a href="#cap8" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap8" aria-selected="false">
          Sito Web
        </a>
        <a href="#cap9" class="nav-link" data-toggle="pill" role="tab" aria-controls="cap9" aria-selected="false">
          Quote, Penalità e Premi
        </a>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="tab-content" id="faq-tab-content">
        <div class="tab-pane show active" id="cap1" role="tabpanel" aria-labelledby="cap1">
          <div class="accordion" id="accordion-cap-1">
            <div class="card">
              <div class="card-header" id="accordion-cap-1-heading-1">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-1-content-1" aria-expanded="false" aria-controls="accordion-cap-1-content-1">Generalità</button>
                </h5>
              </div>
              <div class="collapse show" id="accordion-cap-1-content-1" aria-labelledby="accordion-cap-1-heading-1" data-parent="#accordion-cap-1">
                <div class="card-body">
                  <p>
                    La Lega FantaScarrupat è un fantacalcio democratico fondato sull’amicizia e lo stare insieme. La sovranità appartiene a tutte le squadre partecipanti. Ogni decisione a partire dalla fase di fondazione e di stesura di questo regolamento sarà presa sempre in maniera democratica. Per la gestione del gioco ci si affida ai server di “Fantacalcio.it”.
                  </p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-1-heading-2">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-1-content-2" aria-expanded="false" aria-controls="accordion-cap-1-content-2">Consiglio di Lega</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-1-content-2" aria-labelledby="accordion-cap-1-heading-2" data-parent="#accordion-cap-1">
                <div class="card-body">
                  <p>
                    Ad ogni fantallenatore appartenente alla Lega è riservato un posto nel “Consiglio di Lega”. Il Consiglio di Lega ha il compito di prendere le decisioni definitive all’inizio della stagione e durante la stessa, organizzare date e sedi delle aste e risolvere situazioni spinose che si dovessero presentare nel corso della stagione.<br>
                    Il Consiglio di Lega è composto, per la stagione 2023/2024, da 8 membri ed è presieduto dall’admin della Lega: Antonio Di Giorgio.<br>
                    Ogni membro del Consiglio, incluso il presidente, ha stesso potere di voto e pari diritti e doveri.<br>
                  </p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-1-heading-3">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-1-content-3" aria-expanded="false" aria-controls="accordion-cap-1-content-3">Modifiche al Regolamento e Votazioni</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-1-content-3" aria-labelledby="accordion-cap-1-heading-3" data-parent="#accordion-cap-1">
                <div class="card-body">
                  <p>
                    Le modifiche al regolamento possono essere eseguite solo prima dell’inizio di ogni stagione. Per le votazioni che avranno sede nel corso della stagione, la modifica, se approvata, varrà dalla stagione successiva alla votazione.<br>
                    Le votazioni possono aver luogo sia in presenza che tramite software di messaggistica.<br>
                    Una votazione, per essere considerata valida, necessita che ogni membro esprima la propria preferenza.<br>
                    Un testo di modifica di codesto regolamento è approvato quando almeno il 50% + 1 dei votanti approva la modifica, in caso contrario o di parità la modifica non viene approvata e resta in vigore la precedente regola.
                  </p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-1-heading-4">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-1-content-4" aria-expanded="false" aria-controls="accordion-cap-1-content-4">Votazioni d'Emergenza</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-1-content-4" aria-labelledby="accordion-cap-1-heading-4" data-parent="#accordion-cap-1">
                <div class="card-body">
                  <p>
                    Nel corso di una stagione possono risultare necessarie votazioni che hanno effetto immediato sul regolamento (e quindi in vigore dal momento dell’approvazione e non dalla stagione successiva).<br>
                    Questo tipo di votazione può avvenire per motivi di vario genere ed è strettamente legata alla salvaguardia, trasparenza e sicurezza della Lega e della stagione stessa.<br>
                    Queste votazioni, a differenza delle votazioni di ordinaria amministrazione, necessitano per l’approvazione di un voto ad unanimità (il 100% dei votanti deve essere favorevole alla modifica).<br>
                    Il presidente del Consiglio della Lega è tenuto a chiamare una “Votazione d’emergenza” ove veda una situazione spinosa che vada ad alterare il corso della stagione, esempi:
                    <ul>
                      <li>Fantallenatore che non consegna più la formazione;</li>
                      <li>Fantallenatori che si accordano a danno di altri;</li>
                    </ul>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane" id="cap2" role="tabpanel" aria-labelledby="cap2">
          <div class="accordion" id="accordion-cap-2">
            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-1">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-2-content-1" aria-expanded="false" aria-controls="accordion-cap-2-content-1">Does anybody else feel jealous and aroused and worried?</button>
                </h5>
              </div>
              <div class="collapse show" id="accordion-cap-2-content-1" aria-labelledby="accordion-cap-2-heading-1" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>Kif, I have mated with a woman. Inform the men. This is the worst part. The calm before the battle. Bender, being God isn't easy. If you do too much, people get dependent on you, and if you do nothing, they lose hope. You have to use a light touch. Like a safecracker, or a pickpocket.</p>
                  <p><strong>Example: </strong>There's no part of that sentence I didn't like! You, a bobsleder!? That I'd like to see!</p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-2">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-2-content-2" aria-expanded="false" aria-controls="accordion-cap-2-content-2">This opera's as lousy as it is brilliant?</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-2-content-2" aria-labelledby="accordion-cap-2-heading-2" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>Your lyrics lack subtlety. You can't just have your characters announce how they feel. That makes me feel angry! It's okay, Bender. I like cooking too. Interesting. No, wait, the other thing: tedious.</p>
                  <p><strong>Example: </strong>Of all the friends I've had… you're the first. But I know you in the future. I cleaned your poop. Then we'll go with that data file!</p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-3">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-2-content-3" aria-expanded="false" aria-controls="accordion-cap-2-content-3">Who are you, my warranty?!</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-2-content-3" aria-labelledby="accordion-cap-2-heading-3" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>Oh, I think we should just stay friends. I'll tell them you went down prying the wedding ring off his cold, dead finger. Aww, it's true. I've been hiding it for so long. Say it in Russian! Then throw her in the laundry room, which will hereafter be referred to as "the brig".</p>
                  <p><strong>Example: </strong> We're rescuing ya. Robot 1-X, save my friends! And Zoidberg! <em>Then we'll go with that data file!</em> Okay, I like a challenge.</p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-4">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-2-content-4" aria-expanded="false" aria-controls="accordion-cap-2-content-4">I haven't felt much of anything since my guinea pig died?</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-2-content-4" aria-labelledby="accordion-cap-2-heading-4" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>And I'm his friend Jesus. Oh right. I forgot about the battle. OK, if everyone's finished being stupid. We'll need to have a look inside you with this camera. I'm just glad my fat, ugly mama isn't alive to see this day.</p>
                  <p><strong>Example: </strong> Isn't it true that you have been paid for your testimony? Quite possible.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="tab-pane" id="cap3" role="tabpanel" aria-labelledby="cap3">
          <div class="accordion" id="accordion-cap-3">
            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-1">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-3-content-1" aria-expanded="false" aria-controls="accordion-cap-3-content-1">Michelle, I don't regret this, but I both rue and lament it?</button>
                </h5>
              </div>
              <div class="collapse show" id="accordion-cap-3-content-1" aria-labelledby="accordion-cap-3-heading-1" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>Look, last night was a mistake. We'll need to have a look inside you with this camera. Good news, everyone! There's a report on TV with some very bad news! You know, I was God once. You lived before you met me?!</p>
                  <p><strong>Example: </strong>I'm Santa Claus! Pansy. That's a popular name today. Little "e", big "B"?</p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-2">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-3-content-2" aria-expanded="false" aria-controls="accordion-cap-3-content-2">Why am I sticky and naked?</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-3-content-2" aria-labelledby="accordion-cap-3-heading-2" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>Did I miss something fun? Humans dating robots is sick. You people wonder why I'm still single? It's 'cause all the fine robot sisters are dating humans! Kids don't turn rotten just from watching TV.</p>
                  <p><strong>Example: </strong>I usually try to keep my sadness pent up inside where it can fester quietly as a mental illness.</p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-3">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-3-content-3" aria-expanded="false" aria-controls="accordion-cap-3-content-3">Is that a cooking show?</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-3-content-3" aria-labelledby="accordion-cap-3-heading-3" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>OK, this has gotta stop. I'm going to remind Fry of his humanity the way only a woman can. You seem malnourished. Are you suffering from intestinal parasites? Check it out, y'all. Everyone who was invited is here. I am Singing Wind, Chief of the Martians.</p>
                  <p><strong>Example: </strong>Man, I'm sore all over. I feel like I just went ten rounds with mighty Thor.</p>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-4">
                <h5>
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordion-cap-3-content-4" aria-expanded="false" aria-controls="accordion-cap-3-content-4">You are the last hope of the universe?</button>
                </h5>
              </div>
              <div class="collapse" id="accordion-cap-3-content-4" aria-labelledby="accordion-cap-3-heading-4" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>I don't want to be rescued. I videotape every customer that comes in here, so that I may blackmail them later. Ah, computer dating. It's like pimping, but you rarely have to use the phrase "upside your head."</p>
                  <p><strong>Example: </strong>Tell them I hate them.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Regolamento End -->


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
<script src="https://unpkg.com/bootstrap@4.1.3/dist/js/bootstrap.min.js"></script>


<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>

</html>
