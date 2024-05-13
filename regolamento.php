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
            <li class="breadcrumb-item text-white active" aria-current="page">Regolamento</li>
          </ol>
        </nav>
      </div>
      <div class="col-lg-5 align-self-end text-center text-lg-end">
        <img class="img-fluid" src="img/player_mezzo_busto.png" alt="" style="max-height: 320px;">
      </div>
    </div>
  </div>
</div>
<!-- Hero End -->

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
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-1-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-1-content-1">
                  Generalità
                </button>
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
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-1-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-1-content-2">
                  Consiglio di Lega
                </button>
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
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-1-content-3"
                        aria-expanded="false" aria-controls="accordion-cap-1-content-3">
                  Modifiche al Regolamento e Votazioni
                </button>
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
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-1-content-4"
                        aria-expanded="false" aria-controls="accordion-cap-1-content-4">
                  Votazioni d'Emergenza
                </button>
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
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-2-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-2-content-1">
                  La Modalità di Gioco
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-2-content-1" aria-labelledby="accordion-cap-2-heading-1" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>
                    Il gioco si basa sul regolamento Classic del Fantacalcio.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-2-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-2-content-2">
                  La Rosa
                </button>
              </div>
              <div class="collapse" id="accordion-cap-2-content-2" aria-labelledby="accordion-cap-2-heading-2" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>
                    Ogni squadra dovrà costruire, in fase d’asta, una rosa di 25 giocatori così divisi: 3 portieri, 8 difensori, 8 centrocampisti e 6 attaccanti.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-3">
                  <button class="btn btn-link" type="button"
                          data-toggle="collapse" data-target="#accordion-cap-2-content-3"
                          aria-expanded="false" aria-controls="accordion-cap-2-content-3">
                    L'Asta Iniziale
                  </button>
              </div>
              <div class="collapse" id="accordion-cap-2-content-3" aria-labelledby="accordion-cap-2-heading-3" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>
                    L’asta iniziale è divisa in due momenti: la prima “regole e votazioni” la seconda invece “creazione delle rose”.<br>
                    La prima come dice il nome stesso è la fase in cui tutti i fantallenatori della Lega discutono le varie regole e provvedono ad eseguire tutte le votazioni, e quindi alla stesura del nuovo regolamento.<br>
                    Nella seconda fase si procede alla creazione di tutte le rose. Ogni squadra avrà a disposizione 1000 crediti per creare la propria rosa. L’asta sarà organizzata secondo l’ordine dei ruoli e potrà avvenire tramite due metodi:<br>
                    <ul>
                      <li>Scorrimento alfabetico dei cognomi</li>
                      <li>Chiamata Random tramite software “FantaAsta”</li>
                    </ul>
                    La scelta di uno dei due metodi è oggetto di votazione durante la prima fase delle votazioni e verrà mantenuta anche per le successive due aste.<br>
                    Ogni giocatore della lista ha valore iniziale 1. Alla fine dello scorrimento della lista, le squadre che non hanno completato il reparto procederanno con l’asta seguendo il metodo della “chiamata diretta” del giocatore desiderato. Alla fine dell’asta di ogni reparto ogni squadra potrà decidere di correggere eventuali errori svincolando i giocatori non desiderati, riottenendo lo stesso prezzo precedentemente pagato e sostituendolo con il processo delle “chiamate dirette”. Prima di partecipare a codeste aste va specificato il giocatore che si intende svincolare il quale già verrà considerato come tale, di conseguenza se un fantallenatore dovesse ripensarci dovrà riacquistare il giocatore con una nuova asta, in assenza di partecipanti a codesta riacquisterà il giocatore al prezzo pagato nell’asta precedente dello stesso giocatore. <br>
                    La fonte per i ruoli è “listone di Fantacalcio.it”.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-4">
                  <button class="btn btn-link" type="button"
                          data-toggle="collapse" data-target="#accordion-cap-2-content-4"
                          aria-expanded="false" aria-controls="accordion-cap-2-content-4">
                    Schieramento della Formazione
                  </button>
              </div>
              <div class="collapse" id="accordion-cap-2-content-4" aria-labelledby="accordion-cap-2-heading-4" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>
                    La formazione potrà essere schierata entro 15 minuti prima dell’inizio della prima gara della serie A e prevede lo schieramento di 11 giocatori secondo i seguenti moduli:
                    <ul>
                      <li>343</li>
                      <li>352</li>
                      <li>433</li>
                      <li>442</li>
                      <li>451</li>
                      <li>541</li>
                      <li>532</li>
                    </ul>
                    La panchina è formata da 11 giocatori organizzati nel seguente modo:
                    <ul>
                      <li>2 Portiere</li>
                      <li>3 Difensori</li>
                      <li>4 Centrocampisti</li>
                      <li>5 Attaccanti</li>
                    </ul>
                    Nel caso non si consegni la formazione il sistema ripescherà l’ultima formazione schierata, se disponibile.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-2-heading-5">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-2-content-5"
                        aria-expanded="false" aria-controls="accordion-cap-2-content-5">
                  Sostituzioni
                </button>
              </div>
              <div class="collapse" id="accordion-cap-2-content-5" aria-labelledby="accordion-cap-2-heading-5" data-parent="#accordion-cap-2">
                <div class="card-body">
                  <p>
                    L’ordine in cui si schierano i giocatori in panchina gioca un ruolo fondamentale per il funzionamento delle sostituzioni. Se un giocatore non dovesse scendere in campo o comunque dovesse essere considerato “s.v.” si procederà alla sostituzione con il giocatore di pari ruolo schierato in panchina. Se tale giocatore non fosse presente in panchina il sistema assegnerà un voto d’ufficio pari a 0 (zero). <br>
                    Sono consentite un numero di 5 sostituzioni.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>


        <div class="tab-pane" id="cap3" role="tabpanel" aria-labelledby="cap3">
          <div class="accordion" id="accordion-cap-3">

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-1">
                  La Redazione
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-3-content-1" aria-labelledby="accordion-cap-3-heading-1" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    La redazione adottata per le pagelle è quella di “Fantacalcio.it” e come fonte per i bonus/malus è “Fantacalcio”.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-2">
                  Soglie Gol, Fasce e Intorni
                </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-2" aria-labelledby="accordion-cap-3-heading-2" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    La Lega FantaScarrupat stabilisce a 66 punti la soglia per “segnare” il primo goal. <br>
                    I goal successivi al primo si ottengono al raggiungimento di ulteriori 4 punti rispetto a quelli previsti dalla soglia precedente, esempio:
                    <ul>
                      <li>66 punti -> 1 goal</li>
                      <li>70 punti -> 2 goal</li>
                      <li>74 punti -> 3 goal</li>
                      <li>78 punti -> 4 goal</li>
                    </ul>
                    Inoltre la Lega FantaScarrupat stabilisce di non voler utilizzare alcun intorno, né tra fasce né interne ad esse, e stabilisce di non assegnare nessun “goal extra” o “autogoal” anche nel caso in cui la differenza tra i due sfidanti con punteggio nella stessa fascia sia massima (cioè 3.5 punti).
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-3">
                  <button class="btn btn-link" type="button"
                          data-toggle="collapse" data-target="#accordion-cap-3-content-3"
                          aria-expanded="false" aria-controls="accordion-cap-3-content-3">
                    Bonus e Malus
                  </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-3" aria-labelledby="accordion-cap-3-heading-3" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    I bonus/malus che saranno sommati o sottratti dalle pagelle sono:
                    <ul>
                      <li>•	+3 per ogni rete segnata</li>
                      <li>+1 per ogni assist effettuato</li>
                      <li>+3 per ogni rigore parato</li>
                      <li>+1 per il portiere che rimane imbattuto</li>
                      <li>-2 per ogni autogol</li>
                      <li>-1 per ogni rete subita</li>
                      <li>-3 per ogni rigore sbagliato</li>
                      <li>-0,5 per ogni ammonizione ricevuta</li>
                      <li>-1 per ogni espulsione ricevuta</li>
                      <li>+1 per ogni gol vittoria</li>
                      <li>+0,5 per ogni gol pareggio</li>
                    </ul>
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-4">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-4"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-4">
                  Fair Play
                </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-4" aria-labelledby="accordion-cap-3-heading-4" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    Il Fair Play è un bonus di 1 punto che viene assegnato alla squadra in sede di calcolo della giornata che non subisce negli 11 titolari (e/o eventuali sostituti) alcuna ammonizione o espulsione. <br>
                    Tale bonus è sede di votazione di ogni asta iniziale.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-5">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-5"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-5">
                  Modificatore Difesa
                </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-5" aria-labelledby="accordion-cap-3-heading-5" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    Il modificatore della difesa è un bonus/malus che si calcola solo se il portiere e almeno 4 difensori portano punteggio alla squadra. Si considerano i voti in pagella, al netto dei bonus/malus, del portiere e dei tre migliori difensori, calcolando la media aritmetica di questi quattro valori. Questa media viene convertita in punti bonus secondo il seguente criterio:
                    <ul>
                      <li>Media < 5: -3 punti</li>
                      <li>5 < Media < 5.25: -2 punti</li>
                      <li>5,25 < Media < 5,5: -1 punto</li>
                      <li>5,5 < Media < 5,75: -0.5 punti</li>
                      <li>5,75 < Media < 6: 0 punti</li>
                      <li>6 < Media < 6.25: +0.5 punti</li>
                      <li>6.25 < Media < 6.5: +1 punto</li>
                      <li>6.5 < Media < 6.75: +2 punti</li>
                      <li>6.75 < Media < 7: +3 punti</li>
                      <li>7 < Media < 7.25: +4 punti</li>
                      <li>7.25 < Media < 7.5: +5 punti</li>
                      <li>Media > 7.5: +6 punti</li>
                    </ul>
                    Tale bonus è oggetto di votazione di ogni asta iniziale.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-6">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-6"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-6">
                  Capitano
                </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-6" aria-labelledby="accordion-cap-3-heading-6" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    Il bonus Capitano è un bonus che si assegna in base al voto al netto dei bonus/malus che prende il giocatore che il Fantallenatore designa come capitano. <br>
                    Nel momento dell’inserimento di ogni formazione il fantallenatore dovrà designare il proprio capitano e il suo vice ed esso può essere modificato ogni giornata. <br>
                    Il bonus assegnato segue tale criterio:
                    <ul>
                      <li>Voto ≤ 4.5: -1 punto</li>
                      <li>Voto = 5: -0.5 punti</li>
                      <li>Voto = 5.5: 0 punti</li>
                      <li>Voto = 6: 0.5 punti</li>
                      <li>Voto = 6.5: 1 punto</li>
                      <li>Voto = 7: 1.5 punti</li>
                      <li>Voto ≥ 7.5: 2 punti</li>
                    </ul>
                    Al capitano però non verranno raddoppiati né i bonus né i malus. <br>
                    Tale bonus è oggetto di votazione di ogni asta iniziale.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-7">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-7"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-7">
                  La funzione "Switch"
                </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-7" aria-labelledby="accordion-cap-3-heading-7" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    "Switch" è una funzione di gioco che permette di assicurare la titolarità di un determinato calciatore inserito in formazione. La funzione “switch” prevede che qualora un giocatore, nella sua partita reale, non inizi dal primo minuto esso venga sostituito, nella formazione schierata su Leghe Fantacalcio, dal panchinaro preselezionato. In sostanza, si tratta di una inversione di due calciatori tra campo e panchina condizionata ad un evento certo, la non titolarità reale del calciatore schierato in formazione. <br>
                    Tale funzione è oggetto di votazione di ogni asta iniziale.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-3-heading-8">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-3-content-8"
                        aria-expanded="false" aria-controls="accordion-cap-3-content-8">
                  Partite sospese, rinviate o anticipate
                </button>
              </div>
              <div class="collapse" id="accordion-cap-3-content-8" aria-labelledby="accordion-cap-3-heading-8" data-parent="#accordion-cap-3">
                <div class="card-body">
                  <p>
                    In caso di partite sospese, rinviate o anticipate di un periodo maggiore di 7 giorni rispetto all’orario ufficiale della giornata (15.00 della domenica o 20.45 del mercoledì) sarà assegnato un voto d’ufficio pari a 6 a tutti i giocatori della gara suddetta. Davanti a casi eccezionali riguardanti squalificati o infortunati si seguiranno le indicazioni di Fantacalcio.it. Se le partite rinviate oltre i 7 giorni dovessero essere almeno 3 si aspetterà il recupero delle stesse.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>


        <div class="tab-pane" id="cap4" role="tabpanel" aria-labelledby="cap4">
          <div class="accordion" id="accordion-cap-4">

            <div class="card">
              <div class="card-header" id="accordion-cap-4-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-4-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-4-content-1">
                  Prima Asta di Riparazione (Opzionale)
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-4-content-1" aria-labelledby="accordion-cap-4-heading-1" data-parent="#accordion-cap-4">
                <div class="card-body">
                  <p>
                    Qualora l’asta iniziale dovesse avvenire prima del termine del calciomercato estivo, i fantallenatori dovranno incontrarsi al termine di esso per eseguire la prima asta di riparazione. Essa avrà lo scopo di sistemare le varie formazioni a seguito degli ultimi giorni di mercato. <br>
                    Ogni squadra partirà con i crediti rimasti dell’asta iniziale ai quali si aggiungeranno i crediti totali recuperati dai vari svincoli. L’asta sarà organizzata secondo l’ordine dei ruoli e potrà avvenire tramite due metodi:
                    <ul>
                      <li>Scorrimento alfabetico dei cognomi</li>
                      <li>Chiamata Random tramite software “FantaAsta”</li>
                    </ul>
                    La scelta di uno dei due metodi è oggetto di votazione durante l’asta iniziale. <br>
                    Prima di partecipare ad un’asta va specificato il giocatore che si intende svincolare, il quale già verrà considerato come tale, di conseguenza se un fantallenatore dovesse ripensarci dovrà riacquistare il giocatore con una nuova asta. In assenza di partecipanti a codesta riacquisterà il giocatore al prezzo pagato nell’asta precedente dello stesso giocatore. <br>
                    Prima dell’inizio di codesta asta vanno sistemati le formazioni secondo le indicazioni del paragrafo “Le Priorità” (vedi 4.4) qualora non fossero ancora state attuate.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-4-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-4-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-4-content-2">
                  Seconda Asta di Riparazione (Obbligatoria)
                </button>
              </div>
              <div class="collapse" id="accordion-cap-4-content-2" aria-labelledby="accordion-cap-4-heading-2" data-parent="#accordion-cap-4">
                <div class="card-body">
                  <p>
                    La seconda asta di riparazione avviene al termine del mercato invernale, essa avrà lo scopo di sistemare le varie formazioni a seguito degli ultimi giorni di mercato.<br>
                    Ogni squadra partirà con i crediti rimasti dell’asta iniziale/prima asta di riparazione ai quali si aggiungeranno i crediti totali recuperati dai vari svincoli. L’asta sarà organizzata secondo l’ordine dei ruoli e potrà avvenire tramite due metodi:
                    <ul>
                      <li>Scorrimento alfabetico dei cognomi</li>
                      <li>Chiamata Random tramite software “FantaAsta”</li>
                    </ul>
                    La scelta di uno dei due metodi è oggetto di votazione durante l’asta iniziale. <br>
                    Prima di partecipare ad un’asta va specificato il giocatore che si intende svincolare il quale già verrà considerato come tale, di conseguenza se un fantallenatore dovesse ripensarci dovrà riacquistare il giocatore con una nuova asta. In assenza di partecipanti a codesta riacquisterà il giocatore al prezzo pagato nell’asta precedente dello stesso giocatore.<br>
                    Prima dell’inizio di codesta asta vanno sistemati le formazioni secondo le indicazioni del paragrafo “Le Priorità” (vedi 4.4) e della “Sessioni Di Scambi” (vedi 4.3) qualora non fossero ancora state attuate.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-4-heading-3">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-4-content-3"
                        aria-expanded="false" aria-controls="accordion-cap-4-content-3">
                  Sessione di Scambi
                </button>
              </div>
              <div class="collapse" id="accordion-cap-4-content-3" aria-labelledby="accordion-cap-4-heading-3" data-parent="#accordion-cap-4">
                <div class="card-body">
                  <p>
                    Durante la prima fase della stagione i fantallenatori possono accordarsi per un eventuale scambio di giocatori. Questi scambi devono risultare sempre di pari valore, eventuali illeciti potrebbero portare ad una “votazione d’emergenza” (vedi 1.4) e a diverse penalità.
                    Gli scambi devono avvenire tra giocatori dello stesso ruolo.
                    Lo scambio riguarderà anche il prezzo del giocatore, il quale seguirà il calciatore a cui fa riferimento nella nuova fantasquadra.
                    Uno scambio è considerato valido solo quando viene comunicato da ambo i fantallenatori sulla Chat WhatsApp del fantacalcio e previa verifica dell’admin di Lega.
                    La comunicazione sulla Chat WhatsApp avverrà tramite la seguente procedura:
                    <ul>
                      <li>Uno dei due fantallenatori compilerà il file WORD “Comunicazione scambi” disponibile sia in chat che nella sezione documenti della Lega</li>
                      <li>Il file compilato verrà inoltrato nella chat</li>
                      <li>L’altro fantallenatore partecipante allo scambio confermerà lo scambio</li>
                      <li>L’admin ufficializzerà lo scambio</li>
                    </ul>
                    Lo scambio diventerà effettivo al termine della seconda asta di riparazione e non saranno più permessi a partire dall’avvio della prima giornata del campionato di “Serie A” successiva alla seconda asta di riparazione.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-4-heading-4">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-4-content-4"
                        aria-expanded="false" aria-controls="accordion-cap-4-content-4">
                  Priorità
                </button>
              </div>
              <div class="collapse" id="accordion-cap-4-content-4" aria-labelledby="accordion-cap-4-heading-4" data-parent="#accordion-cap-4">
                <div class="card-body">
                  <p>
                    Le priorità sono un sistema di salvaguardia per il fantacalcio e per il fantallenatore, esse vanno ad operare nelle situazioni in cui un calciatore tesserato per una delle fantasquadre presenti nella Lega dovesse abbandonare la Serie A.<br>
                    Un fantallenatore che dovesse ritrovarsi in questa situazione può richiedere l’ausilio delle priorità, egli quindi avrà la possibilità di sostituire il suo calciatore indisponibile con uno presente nella lista svincolati di pari ruolo e con quotazione pari o minore a quella del proprio calciatore (al momento del trasferimento) e verrà pagato allo stesso prezzo di quello che andrà a sostituire.<br>
                    Il giocatore, che il fantallenatore andrà ad ingaggiare, deve essere registrato nella lista degli svincolati dal giorno della data dell’ultima asta eseguita.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>



        <div class="tab-pane" id="cap5" role="tabpanel" aria-labelledby="cap5">
          <div class="accordion" id="accordion-cap-5">

            <div class="card">
              <div class="card-header" id="accordion-cap-5-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-5-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-5-content-1">
                  Generalità
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-5-content-1" aria-labelledby="accordion-cap-5-heading-1" data-parent="#accordion-cap-5">
                <div class="card-body">
                  <p>
                    Un infortunio è quando un giocatore risulta essere completamente inutilizzabile e quindi non convocabile per un certo numero di partite per un problema di natura fisica o mentale del giocatore stesso. <br>
                    Un’indisponibilità è quando un giocatore risulta essere completamente inutilizzabile e quindi non convocabile per un certo numero di partite per una decisione di tipo tattico, societario o qualsiasi altro (esempio: giocatore fuori rosa).
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-5-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-5-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-5-content-2">
                  Infortuni Inferiori ai 4 Mesi
                </button>
              </div>
              <div class="collapse" id="accordion-cap-5-content-2" aria-labelledby="accordion-cap-5-heading-2" data-parent="#accordion-cap-5">
                <div class="card-body">
                  <p>
                    Un fantallenatore che si ritrova nella situazione in cui il suo giocatore ha subito un infortunio per una durata complessiva (data infortunio – data prima convocazione) inferiore ai 4 mesi dovrà aspettare il rientro del calciatore senza poter usufruire di nessun aiuto.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-5-heading-3">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-5-content-3"
                        aria-expanded="false" aria-controls="accordion-cap-5-content-3">
                  Infortuni Superiori ai 4 Mesi
                </button>
              </div>
              <div class="collapse" id="accordion-cap-5-content-3" aria-labelledby="accordion-cap-5-heading-3" data-parent="#accordion-cap-5">
                <div class="card-body">
                  <p>
                    Un fantallenatore che si ritrova nella situazione in cui il suo giocatore subisca un infortunio per una durata complessiva (data infortunio – data prima convocazione), superiore o uguale ai 4 mesi o addirittura considerato con la dicitura “stagione finita” può richiedere la sua sostituzione dalla lista svincolati. <br>
                    Il giocatore deve essere registrato nella lista degli svincolati dal giorno della data dell’ultima asta eseguita e deve avere quotazione pari o FANTASCARRUPAT 17 minore a quella del giocatore infortunato, al momento proprio dell’infortunio. <br>
                    Questo sistema di sostituzioni è utilizzabile fino al 31 Marzo (intesa come data in cui avviene l’infortunio), dal 1° aprile nessun giocatore potrà più essere sostituito qualora anche l’infortunio sia superiore ai 4 mesi o addirittura considerato con la dicitura “stagione finita”.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-5-heading-4">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-5-content-4"
                        aria-expanded="false" aria-controls="accordion-cap-5-content-4">
                  Giocatori Indisponibili
                </button>
              </div>
              <div class="collapse" id="accordion-cap-5-content-4" aria-labelledby="accordion-cap-5-heading-4" data-parent="#accordion-cap-5">
                <div class="card-body">
                  <p>
                    Un fantallenatore che si ritrova nella situazione in cui il suo giocatore risulti indisponibile sarà costretto ad aspettare il rientro del calciatore senza poter usufruire di nessun aiuto.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-5-heading-5">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-5-content-5"
                        aria-expanded="false" aria-controls="accordion-cap-5-content-5">
                  Giocatori Positivi al Sars-Cov-2
                </button>
              </div>
              <div class="collapse" id="accordion-cap-5-content-5" aria-labelledby="accordion-cap-5-heading-5" data-parent="#accordion-cap-5">
                <div class="card-body">
                  <p>
                    Un giocatore che risulterà positivo al “SARS-CoV-2” o comunemente conosciuto con i nomi di “COVID-19” e “Coronavirus” verrà considerato come un infortunato qualunque e di conseguenza rispetterà le regole della “Gestione Infortuni E Indisponibilità” (vedi 5).
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>



        <div class="tab-pane" id="cap6" role="tabpanel" aria-labelledby="cap6">
          <div class="accordion" id="accordion-cap-6">

            <div class="card">
              <div class="card-header" id="accordion-cap-6-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-6-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-6-content-1">
                  Interruzione Campionato Serie A
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-6-content-1" aria-labelledby="accordion-cap-6-heading-1" data-parent="#accordion-cap-6">
                <div class="card-body">
                  <p>
                    In caso di sospensione del campionato di “Serie A” (vedi stagione 2019/2020) si attenderà l’eventuale ripresa delle gare per completare le competizioni. Se la ripresa prevedesse un campionato compresso o addirittura i playoff/playout o qualsiasi altra modalità differente a quella originale per il completamento della stagione, il nostro gioco si interromperà all’ultima giornata completa. Le competizioni a calendario saranno considerate valide e saranno distribuiti premi (con ricalcolo dei premi in funzione delle giornate) solo se si completano il 60% delle giornate totali. Le coppe saranno considerate valide solo in caso di completamento delle stesse. <br>
                    Questa regola non ha validità nel caso in cui l’interruzione del campionato fosse dovuta per permettere lo svolgimento di un’altra competizione di ordine superiore (vedi stagione 2022/2023), ad esempio:
                    <ul>
                      <li>Mondiale per nazionali</li>
                      <li>Competizioni continentali per nazionali</li>
                      <li>Olimpiadi</li>
                    </ul>
                    In questi casi si attenderà sempre la ripresa del campionato cosi come da calendario. <br>
                    Questo è uno dei casi in cui si può richiedere una “Votazione D’Emergenza” (vedi 1.4) per decidere eventualmente l’inserimento di nuove competizioni e modifiche delle attuali.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>



        <div class="tab-pane" id="cap7" role="tabpanel" aria-labelledby="cap7">
          <div class="accordion" id="accordion-cap-7">

            <div class="card">
              <div class="card-header" id="accordion-cap-7-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-7-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-7-content-1">
                  Serie A
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-7-content-1" aria-labelledby="accordion-cap-7-heading-1" data-parent="#accordion-cap-7">
                <div class="card-body">
                  <p>
                    A questa competizione partecipano le 8 squadre. Si tratta di un campionato con girone asimmetrico, il calendario verrà redatto in sede di Asta Iniziale in modo completamente randomico tramite l’apposito software di “Fantacalcio.it”. <br>
                    I punti saranno assegnati in questo modo:
                    <ul>
                      <li>3 Punti ad ogni vittoria</li>
                      <li>1 Punto ad ogni pareggio</li>
                      <li>0 Punti per ogni sconfitta</li>
                    </ul>
                    Le prime 3 classificate vengono dichiarate vincitrici ed andranno “a premio”. In caso di arrivo a pari punti varranno, nell’ordine, i seguenti criteri:
                    <ol>
                      <li>Somma punti totali</li>
                      <li>Classifica avulsa</li>
                      <li>Differenza reti</li>
                      <li>Gol fatti</li>
                      <li>Gol subiti</li>
                    </ol>
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-7-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-7-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-7-content-2">
                  Champions League
                </button>
              </div>
              <div class="collapse" id="accordion-cap-7-content-2" aria-labelledby="accordion-cap-7-heading-2" data-parent="#accordion-cap-7">
                <div class="card-body">
                  <p>
                    A questa competizione partecipano le 8 squadre. Essa segue la formula dei gironi dove le migliori due di ogni girone si sfidano in partite ad eliminazione diretta. <br>
                    Nella prima fase detta “GIRONI” le squadre verranno divise in gruppi da 4 e dovranno sfidarsi tra loro in 6 diversi match il cui calendario verrà redatto in sede di Asta Iniziale in modo completamente randomico tramite l’apposito software di “Fantacalcio.it”.
                    I punti saranno assegnati in questo modo:
                  <ul>
                    <li>3 Punti ad ogni vittoria</li>
                    <li>1 Punto ad ogni pareggio</li>
                    <li>0 Punti per ogni sconfitta</li>
                  </ul>
                  In caso di arrivo a pari punti varranno, nell’ordine, i seguenti criteri:
                  <ol>
                    <li>Somma punti totali</li>
                    <li>Classifica avulsa</li>
                    <li>Differenza reti</li>
                    <li>Gol fatti</li>
                    <li>Gol subiti</li>
                  </ol>
                  Le prime 2 classificate accederanno alla seconda fase detta “ELIMINAZIONE DIRETTA”. <br>
                  Qui le prime classificate sfideranno le seconde del girone opposto in due sfide (andata e ritorno), a differenza della finale che è a partita secca, la vincitrice verrà stabilita dai seguenti criteri:
                  <ul>
                    <li>Risultato totale sui due incontri</li>
                    <li>Supplementari e Rigori</li>
                    <li>Somma punti totali</li>
                    <li>Gol trasferta</li>
                  </ul>
                  Il criterio “Supplementari e Rigori” rispetta le regole del server “Fantacalcio.it” qui sotto riportato:<br><br>
                  SUPPLEMENTARI:<br>
                  Criterio: per ogni squadra il numero di reti realizzate nel supplementare è dato da una tabella di conversione che valuta la media aritmetica delle fantamedie dei migliori quattro panchinari, portieri esclusi, non subentrati in campo. Qualora dovessero mancare quattro panchinari a voto si aggiungeranno tanti 5,5 d’ufficio quanti sono i calciatori mancanti a raggiungere le quattro unità previste. <br>
                  Tabella di conversione:
                  <ul>
                    <li>La Fantamedia da 0 a 6,49 corrisponde a 0 Gol</li>
                    <li>La Fantamedia da 6,5 a 6,99 corrisponde a 1 Gol</li>
                    <li>La Fantamedia da 7 a 7,49 corrisponde a 2 Gol</li>
                    <li>Ogni 0,5 punti in più si assegna un gol</li>
                  </ul>
                  RIGORI:<br>
                  Criterio: se, dopo i supplementari, si è ancora in parità, si va ai calci di rigore. Il calciatore realizza un calcio di rigore quando il suo voto netto (quindi privo di bonus e malus di qualsiasi tipo) è maggiore/uguale a 6. Lo sbaglia se è inferiore a 6. Ogni squadra tirerà 5 calci di rigore, in caso di ulteriore parità si andrà avanti ad oltranza con delle serie da un rigore a testa che, come nella realtà, si interrompono quando una delle squadre andrà avanti nel punteggio.<br>
                  Ordinamento dei rigoristi: va stabilito dal fantallenatore solo indirettamente, poiché sarà indissolubilmente legato allo schieramento della formazione. Nelle leghe Classic l'ordine dei tiratori è:
                  <ul>
                    <li>attaccanti schierati nella formazione titolare se con voto valido (in ordine di come li avete schierati)</li>
                    <li>centrocampisti schierati nella formazione titolare se con voto valido (in ordine di come li avete schierati)</li>
                    <li>difensori schierati nella formazione titolare se con voto valido (in ordine di come li avete schierati)</li>
                    <li>portiere schierato nella formazione titolare se con voto valido</li>
                    <li>riserve subentrate con voto valido (secondo ordine di panchina)</li>
                  </ul>
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>


        <div class="tab-pane" id="cap8" role="tabpanel" aria-labelledby="cap8">
          <div class="accordion" id="accordion-cap-8">

            <div class="card">
              <div class="card-header" id="accordion-cap-8-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-8-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-8-content-1">
                  Sito FantaScarrupat
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-8-content-1" aria-labelledby="accordion-cap-8-heading-1" data-parent="#accordion-cap-8">
                <div class="card-body">
                  <p>
                    La Lega FantaScarrupat dalla stagione 2022/2023 dispone di un sito web personale, il cui link è: “www.fantascarrupat.altervista.org”. <br>
                    Esso sarà il centro dell’ecosistema della Lega, il quale, infatti, permetterà a tutti i partecipanti e non di accedere alle informazioni fondamentali della stessa come il regolamento ecc. ma avrà anche lo scopo di rendere sempre accessibile lo storico delle passate stagioni attraverso l’albo storico e il palmares.
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-8-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-8-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-8-content-2">
                  Responsabilità
                </button>
              </div>
              <div class="collapse" id="accordion-cap-8-content-2" aria-labelledby="accordion-cap-8-heading-2" data-parent="#accordion-cap-8">
                <div class="card-body">
                  <p>
                    Il sito sarà curato sotto ogni dettaglio dall’admin della Lega stessa ma tutto ciò che verrà pubblicato in esso è responsabilità di tutti i membri della Lega FantaScarrupat, di conseguenza quest’ultimi dovranno verificare che ciò che venga pubblicato rispetti il vero e sono tenuti a richiedere modifiche o proporre novità qualora lo ritenessero necessario.                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-8-heading-3">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-8-content-3"
                        aria-expanded="false" aria-controls="accordion-cap-8-content-3">
                  FantaSquadre: Nomi e Loghi
                </button>
              </div>
              <div class="collapse" id="accordion-cap-8-content-3" aria-labelledby="accordion-cap-8-heading-3" data-parent="#accordion-cap-8">
                <div class="card-body">
                  <p>
                    Ogni Fantallenatore nel momento in cui entra a far parte delle Lega FantaScarrupat deve inventare un nome e realizzare un logo per la propria Fantasquadra, esso deve essere originale e soprattutto personale.<br>
                    I nomi e soprattutto i loghi devono essere obbligatoriamente frutto dell’immaginazione personale del Fantallenatore e quindi non possono essere né copiati né ispirati ad altri già esistenti, questa regola è resa necessaria per il rispetto delle normative sul Copyright
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-8-heading-4">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-8-content-4"
                        aria-expanded="false" aria-controls="accordion-cap-8-content-4">
                  Modifiche FantaSquadre
                </button>
              </div>
              <div class="collapse" id="accordion-cap-8-content-4" aria-labelledby="accordion-cap-8-heading-4" data-parent="#accordion-cap-8">
                <div class="card-body">
                  <p>
                    Un Fantallenatore una volta deciso il nome e il logo per la propria Fantasquadra non potrà più modificarlo senza aver avuto l’autorizzazione del Consiglio di Lega.<br>
                    La modifica è possibile solo per motivi di assoluta urgenza o necessità, la modifica per fini estetici (sempre se accettata dal Consiglio di Lega) è permessa solo ad inizio stagione e, se accettata, non si ripotrà richiedere per le successive 2 stagioni.
                    <ol>
                      <li>Il Fantallenatore che intende modificare il proprio logo o nome dovrà compilare il file WORD “Richiesta cambi nome e logo”.</li>
                      <li>A quel punto l’admin verificherà la corretta compilazione della richiesta e controllerà se le modifiche proposte rispettano tutte le regole.</li>
                      <li>Successivamente aprirà una votazione per verificare che il 50%+1 dei partecipanti autorizzi la modifica.</li>
                      <li>Qualora ottenesse l’autorizzazione l’admin ufficializzerà la modifica e provvederà all’attuazione della stessa.</li>
                    </ol>
                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-8-heading-5">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-8-content-5"
                        aria-expanded="false" aria-controls="accordion-cap-8-content-5">
                  Penalità
                </button>
              </div>
              <div class="collapse" id="accordion-cap-8-content-5" aria-labelledby="accordion-cap-8-heading-5" data-parent="#accordion-cap-8">
                <div class="card-body">
                  <p>
                    Come detto nei paragrafi precedenti la modifica di nomi o loghi dovrà essere richiesta e poi accettata prima di essere applicata. <br>
                    Qualora un Fantallenatore dovesse procedere a modificare il nome o logo della propria Fantasquadra senza autorizzazione sarà prima chiamato a ripristinare nome e logo con quelli permessi, qualora la situazione dovesse continuare o addirittura ripetersi il Fantallenatore sarà penalizzato con un supplemento di euro 5.00 della sua quota di iscrizione.
                  </p>
                </div>
              </div>
            </div>

          </div>
        </div>


        <div class="tab-pane" id="cap9" role="tabpanel" aria-labelledby="cap9">
          <div class="accordion" id="accordion-cap-9">

            <div class="card">
              <div class="card-header" id="accordion-cap-9-heading-1">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-9-content-1"
                        aria-expanded="false" aria-controls="accordion-cap-9-content-1">
                  Quote
                </button>
              </div>
              <div class="collapse show" id="accordion-cap-9-content-1" aria-labelledby="accordion-cap-9-heading-1" data-parent="#accordion-cap-9">
                <div class="card-body">
                  <p>
                    La quota di partecipazione alla Lega è di 150 euro a stagione da versare al tesoriere. Il pagamento sarà suddiviso in due rate da 75 euro ciascuna, la prima con scadenza nella data della “Seconda Asta di Riparazione”, la seconda invece con scadenza nell’ultima giornata di Serie A, ove gli otto fantallenatori si incontreranno in un bar ed i vincitori offriranno un aperitivo agli sconfitti. All’atto del versamento sarà rilasciata una ricevuta da conservare come copia di avvenuto pagamento, essa è l’unica prova che attesta il reale versamento delle quote.                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-9-heading-2">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-9-content-2"
                        aria-expanded="false" aria-controls="accordion-cap-9-content-2">
                  Penalità
                </button>
              </div>
              <div class="collapse" id="accordion-cap-9-content-2" aria-labelledby="accordion-cap-9-heading-2" data-parent="#accordion-cap-9">
                <div class="card-body">
                  <p>
                    È prevista una penalità di valore pari al numero di giornate giocate qualora non si consegni in tempo una delle due rate del versamento.                  </p>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="accordion-cap-9-heading-3">
                <button class="btn btn-link" type="button"
                        data-toggle="collapse" data-target="#accordion-cap-9-content-3"
                        aria-expanded="false" aria-controls="accordion-cap-9-content-3">
                  Premi
                </button>
              </div>
              <div class="collapse" id="accordion-cap-9-content-3" aria-labelledby="accordion-cap-9-heading-3" data-parent="#accordion-cap-9">
                <div class="card-body">
                  <p>
                    Le quote determinano un totale di 880 euro suddivisi nei seguenti premi:
                    <ul>
                    <li>Serie A:</li>
                    <ul>
                      <li>1° Posto 350€</li>
                      <li>2° Posto 275€</li>
                      <li>3° Posto 200€</li>
                    </ul>
                    <li>Champions League:</li>
                    <ul>
                      <li>Vincitore coppa 200€</li>
                    </ul>
                    <li>Miglior Punteggio (Totale 175€)</li>
                    <ul>
                      <li>Miglior Punteggio di ogni Giornata 5€</li>
                    </ul>
                  </ul>
                  </p>
                </div>
              </div>
            </div>


          </div>
        </div>



      </div>
    </div>
  </div>

  <br>

  <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
    <h1 class="display-6 mb-5">Download File</h1>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="table-responsive">
        <table class="table">
          <thead class="thead-primary">
          <tr>
            <th>Type</th>
            <th>Documento</th>
            <th>Azione</th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <th><img class="img-fluid-table" src="img/pdf.png"></th>
            <td>Regolamento Ufficiale</td>
            <td><a href="file/Regolamento_Ufficiale.pdf" download="Regolamento_Ufficiale" class="btn btn-primary">Download</a></td>
          </tr>
          <tr>
            <th><img class="img-fluid-table" src="img/pdf.png"></th>
            <td>Modulo Scambio Giocatori</td>
            <td><a href="file/Regolamento_Ufficiale.pdf" download="Regolamento_Ufficiale" class="btn btn-primary">Download</a></td>
          </tr>
          <tr>
            <th><img class="img-fluid-table" src="img/pdf.png"></th>
            <td>Modulo Richiesta Modifica Nome e/o Logo:</td>
            <td><a href="file/Regolamento_Ufficiale.pdf" download="Regolamento_Ufficiale" class="btn btn-primary">Download</a></td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>




</div>

<!-- Regolamento End -->
<br>


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
<script>
  $('.upload').on('click touch', function(e) {

    e.preventDefault();

    var self = $(this);

    self.addClass('loading');
    setTimeout(function() {
      self.removeClass('loading');
    }, 4200)

  });
</script>


<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>

</html>
