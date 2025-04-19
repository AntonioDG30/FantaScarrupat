<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>FantaScarrupat</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="" name="keywords">
  <meta content="" name="description">

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
  <link href="css/ClassificaCompetizione.css" rel="stylesheet">
  <link href="css/TabelloneTorneo.css" rel="stylesheet">
  <link href="css/CalendarioPartite.css" rel="stylesheet">
</head>

<body>

<?php
  global $conn;
  global $id_competizione;
  global $tipologia_competizione;
  global $anno;
  $id_competizione = $_GET['id_competizione'];;
  include 'php/contVisual.php';

  $query1 = "SELECT * FROM competizione_disputata WHERE id_competizione_disputata = $id_competizione";
  $result1 = $conn->query($query1);
  if ($result1->num_rows > 0) {
    while ($row1 = $result1->fetch_assoc()) {
      $nome_competizione = $row1['nome_competizione'];
      $anno = $row1['anno'];
      $vincitore = $row1['vincitore'];
    }

    $query2 = "SELECT tipologia FROM competizione
                  WHERE nome_competizione IN (SELECT nome_competizione FROM competizione_disputata
                                                WHERE nome_competizione = '$nome_competizione')";
    $result2 = $conn->query($query2);
    if ($result2->num_rows > 0) {
      while ($row2 = $result2->fetch_assoc()) {
        $tipologia_competizione = $row2["tipologia"];
      }
    }
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
            <li class="breadcrumb-item"><a class="text-white" href="alboDOro.php">Albo D'Oro</a></li>
            <li class="breadcrumb-item text-white active" aria-current="page">Dettagli Competizione</li>
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

<!-- Albo D'Oro Start -->
<div class="container-fluid destination py-5">
  <div class="container py-5">
    <div class="mx-auto text-center mb-5" style="max-width: 900px;">
      <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
        <h1 class="display-6 mb-5"><?php echo $nome_competizione, " ", $anno-1, "/", $anno; ?></h1>
      </div>
    </div>
    <div class="tab-class text-center">
      <ul class="nav nav-pills d-inline-flex justify-content-center mb-5">
        <?php
          if ($tipologia_competizione == "A Calendario") {
        ?>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill active" data-bs-toggle="pill" href="#Classifica">
            <span class="text-dark" style="width: 150px;">Classifica</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex py-2 mx-3 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#Calendario">
            <span class="text-dark" style="width: 150px;">Calendario</span>
          </a>
        </li>
        <?php
          } else if ($tipologia_competizione == "A Gruppi" || $tipologia_competizione == "Mista") {
        ?>
        <li class="nav-item">
          <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill active" data-bs-toggle="pill" href="#Classifica">
            <span class="text-dark" style="width: 150px;">Classifica</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex py-2 mx-3 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#Tabellone">
            <span class="text-dark" style="width: 150px;">Tabellone</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="d-flex py-2 mx-3 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#Calendario">
            <span class="text-dark" style="width: 150px;">Calendario</span>
          </a>
        </li>
        <?php
          }
        ?>
      </ul>
      <div class="tab-content">
        <div id="Classifica" class="tab-pane fade show p-0 active">
          <?php
          if ($tipologia_competizione == "A Calendario" || $tipologia_competizione == "Mista") {
            $sql = "SELECT nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta,
    punteggio_casa, punteggio_trasferta, tipologia FROM partita_avvessario WHERE id_competizione_disputata = $id_competizione AND tipologia = 'Calendario' ORDER BY id_partita;";
            generaClassifica();
          } else if ($tipologia_competizione == "A Gruppi") {
            $sql = "SELECT DISTINCT girone from partita_avvessario WHERE id_competizione_disputata = $id_competizione AND girone IS NOT NULL";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $girone = $row["girone"];
                $sql = "SELECT * FROM partita_avvessario WHERE id_competizione_disputata = $id_competizione AND girone = '$girone'";
                generaClassifica();
              }
            }
          }

          function generaClassifica() {
          global $conn, $sql, $girone;
          $result = $conn->query($sql);

          $classifica = array();

          if ($result->num_rows > 0) {
            // Loop attraverso ogni partita
            while($row = $result->fetch_assoc()) {
              $casa = $row['nome_fantasquadra_casa'];
              $trasferta = $row['nome_fantasquadra_trasferta'];
              $gol_casa = $row['gol_casa'];
              $gol_trasferta = $row['gol_trasferta'];
              $punteggio_casa = $row['punteggio_casa'];
              $punteggio_trasferta = $row['punteggio_trasferta'];

              // Casa
              if (!isset($classifica[$casa])) {
                $classifica[$casa] = [
                  'punti' => 0,
                  'punteggio_totale' => 0,
                  'gol_fatti' => 0,
                  'gol_subiti' => 0,
                  'vittorie' => 0,
                  'sconfitte' => 0,
                  'pareggi' => 0
                ];
              }
              $classifica[$casa]['punteggio_totale'] += $punteggio_casa;
              $classifica[$casa]['gol_fatti'] += $gol_casa;
              $classifica[$casa]['gol_subiti'] += $gol_trasferta;
              if ($gol_casa > $gol_trasferta) {
                $classifica[$casa]['vittorie']++;
                $classifica[$casa]['punti'] += 3;
              } elseif ($gol_trasferta > $gol_casa) {
                $classifica[$casa]['sconfitte']++;
              } else {
                $classifica[$casa]['pareggi']++;
                $classifica[$casa]['punti'] += 1;
              }

              // Trasferta
              if (!isset($classifica[$trasferta])) {
                $classifica[$trasferta] = [
                  'punti' => 0,
                  'punteggio_totale' => 0,
                  'gol_fatti' => 0,
                  'gol_subiti' => 0,
                  'vittorie' => 0,
                  'sconfitte' => 0,
                  'pareggi' => 0
                ];
              }
              $classifica[$trasferta]['punteggio_totale'] += $punteggio_trasferta;
              $classifica[$trasferta]['gol_fatti'] += $gol_trasferta;
              $classifica[$trasferta]['gol_subiti'] += $gol_casa;
              if ($gol_trasferta > $gol_casa) {
                $classifica[$trasferta]['vittorie']++;
                $classifica[$trasferta]['punti'] += 3;
              } elseif ($gol_casa > $gol_trasferta) {
                $classifica[$trasferta]['sconfitte']++;
              } else {
                $classifica[$trasferta]['pareggi']++;
                $classifica[$trasferta]['punti'] += 1;
              }
            }

            // Ordina la classifica in ordine decrescente di punti totali
            uasort($classifica, function($a, $b) {
              return $b['punti'] - $a['punti'];
            });
          }
          ?>
          <?php
          global $tipologia_competizione;
          if ($tipologia_competizione == "A Gruppi") {
            ?>
            <h3>Girone <?php echo $girone ?> </h3>
            <?php
          }
          global $anno;
          ?>
          <div class="row">
            <div class="col-md-12">
              <div class="table-responsive">
                <table class="classifica-table">
                  <thead>
                  <tr>
                    <th>Posizione</th>
                    <th colspan="2">FantaSquadra</th>
                    <th>Vittorie</th>
                    <th>Pareggi</th>
                    <th>Sconfitte</th>
                    <th>Gol Fatti</th>
                    <th>Gol Subiti</th>
                    <th>Punti</th>
                    <th>Punteggio Totale</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php
                  $posizione = 1;
                  foreach ($classifica as $squadra => $stats) {
                  ?>
                  <tr>
                    <td><?php echo $posizione; ?></td>
                    <?php
                      $sql = "SELECT scudetto FROM fantasquadra WHERE nome_fantasquadra = '$squadra'";
                      $result = $conn->query($sql);
                      if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                    ?>
                      <td><img class="img-fluid-logo" src="img/scudetti/<?php echo $row["scudetto"]?>"></td>
                    <?php
                        }
                      }
                    ?>
                    <td>
                      <a href="dettagliRose.php?nome_fantasquadra=<?php echo urlencode($squadra);?>&anno=<?php echo $anno; ?>">
                        <?php echo $squadra; ?>
                      </a>
                    </td>
                    <td><?php echo $stats['vittorie']; ?></td>
                    <td><?php echo $stats['pareggi']; ?></td>
                    <td><?php echo $stats['sconfitte']; ?></td>
                    <td><?php echo $stats['gol_fatti']; ?></td>
                    <td><?php echo $stats['gol_subiti']; ?></td>
                    <td style="color: #1363C6"><?php echo $stats['punti']; ?></td>
                    <td><?php echo $stats['punteggio_totale']; ?></td>
                  </tr>
                    <?php
                    $posizione++;
                  }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
            <?php
          }
          ?>
        </div>
        <div id="Tabellone" class="tab-pane fade show p-0">
          <div class="bracket">
            <?php
            if ($tipologia_competizione == "A Gruppi" || $tipologia_competizione == "Mista") {
              $query = "SELECT * FROM partita_avvessario WHERE id_competizione_disputata = $id_competizione ORDER BY id_partita, tipologia;";
              $result = $conn->query($query);

              // Array per memorizzare le partite suddivise per tipologia
              $matches_by_type = array();

              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $match = array(
                    'id_partita' => $row['id_partita'],
                    'tipologia' => $row['tipologia'],
                    'nome_fantasquadra_casa' => $row['nome_fantasquadra_casa'],
                    'nome_fantasquadra_trasferta' => $row['nome_fantasquadra_trasferta'],
                    'gol_casa' => $row['gol_casa'],
                    'gol_trasferta' => $row['gol_trasferta']
                  );

                  // Filtra solo le tipologie di interesse
                  if (in_array($row['tipologia'], ['Quarti', 'Semifinali', 'Finale'])) {
                    // Identificare le partite di andata e ritorno
                    $key = $row['nome_fantasquadra_casa'] . '-' . $row['nome_fantasquadra_trasferta'];
                    $reverse_key = $row['nome_fantasquadra_trasferta'] . '-' . $row['nome_fantasquadra_casa'];

                    if (!isset($matches_by_type[$row['tipologia']][$key]) && !isset($matches_by_type[$row['tipologia']][$reverse_key])) {
                      $matches_by_type[$row['tipologia']][$key] = array(
                        'nome_fantasquadra_casa' => $row['nome_fantasquadra_casa'],
                        'nome_fantasquadra_trasferta' => $row['nome_fantasquadra_trasferta'],
                        'gol_casa' => 0,
                        'gol_trasferta' => 0
                      );
                    }

                    if (isset($matches_by_type[$row['tipologia']][$key])) {
                      $matches_by_type[$row['tipologia']][$key]['gol_casa'] += $row['gol_casa'];
                      $matches_by_type[$row['tipologia']][$key]['gol_trasferta'] += $row['gol_trasferta'];
                    } else {
                      $matches_by_type[$row['tipologia']][$reverse_key]['gol_casa'] += $row['gol_trasferta'];
                      $matches_by_type[$row['tipologia']][$reverse_key]['gol_trasferta'] += $row['gol_casa'];
                    }
                  }
                }
              } else {
                echo 'Nessuna partita trovata.';
              }
              ?>
              <div class="bracket">
                <?php foreach ($matches_by_type as $type => $matches) { ?>
                  <section class="round <?php echo strtolower(str_replace(' ', '', $type)); ?>">
                    <div class="winners">
                      <div class="matchups">
                        <?php foreach ($matches as $match) { ?>
                          <div class="matchup">
                            <div class="participants">
                              <div class="participant<?php if ($match['gol_casa'] > $match['gol_trasferta']) echo ' winner'; ?>">
                                <?php
                                $squadra = $match['nome_fantasquadra_casa'];
                                $sql = "SELECT scudetto FROM fantasquadra WHERE nome_fantasquadra = '$squadra'";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                  while($row = $result->fetch_assoc()) {
                                    ?>
                                    <span><img class="logo-tabellone" src="img/scudetti/<?php echo $row["scudetto"]?>"></span>
                                    <?php
                                  }
                                }
                                ?>
                                <span><?php echo $match['nome_fantasquadra_casa']; ?></span>
                                <span><?php echo $match['gol_casa']; ?></span>
                              </div>
                              <div class="participant<?php if ($match['gol_casa'] < $match['gol_trasferta']) echo ' winner'; ?>">
                                <?php
                                $squadra = $match['nome_fantasquadra_trasferta'];
                                $sql = "SELECT scudetto FROM fantasquadra WHERE nome_fantasquadra = '$squadra'";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0) {
                                  while($row = $result->fetch_assoc()) {
                                    ?>
                                    <span><img class="logo-tabellone" src="img/scudetti/<?php echo $row["scudetto"]?>"></span>
                                    <?php
                                  }
                                }
                                ?>
                                <span><?php echo $match['nome_fantasquadra_trasferta']; ?></span>
                                <span><?php echo $match['gol_trasferta']; ?></span>
                              </div>
                            </div>
                          </div>
                        <?php } ?>
                      </div>
                      <?php if ($type != 'Finale') { ?>
                        <div class="connector">
                          <div class="merger"></div>
                          <div class="line"></div>
                        </div>
                      <?php } ?>
                    </div>
                  </section>
                <?php } ?>
              </div>
              <?php
            }
            ?>
          </div>
        </div>
        <div id="Calendario" class="tab-pane fade show p-0">
          <div class="calendar">
            <?php
            $query = "SELECT * FROM partita_avvessario WHERE id_competizione_disputata = $id_competizione ORDER BY giornata, id_partita;";
            $result = $conn->query($query);
            $matches_by_day = array();
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $match = array(
                  'id_partita' => $row['id_partita'],
                  'giornata' => $row['giornata'],
                  'nome_fantasquadra_casa' => $row['nome_fantasquadra_casa'],
                  'nome_fantasquadra_trasferta' => $row['nome_fantasquadra_trasferta'],
                  'gol_casa' => $row['gol_casa'],
                  'gol_trasferta' => $row['gol_trasferta']
                );
                if (!isset($matches_by_day[$row['giornata']])) {
                  $matches_by_day[$row['giornata']] = array();
                }
                $matches_by_day[$row['giornata']][] = $match;
              }
            } else {
              echo 'Nessuna partita trovata.';
            }
            ?>
            <?php foreach ($matches_by_day as $giornata => $matches) { ?>
              <div class="giornata">
                <div class="giornata-header">
                  Giornata <?php echo $giornata; ?>
                </div>
                <div class="partite">
                  <?php foreach ($matches as $match) { ?>
                    <div class="partita">
                      <div class="team-name"><a href="dettagliRose.php?nome_fantasquadra=<?php echo urlencode($match['nome_fantasquadra_casa']);?>&anno=<?php echo $anno; ?>"><?php echo $match['nome_fantasquadra_casa']; ?></a></div>
                      <div class="match-info"><?php echo $match['gol_casa']; ?> - <?php echo $match['gol_trasferta']; ?></div>
                      <div class="team-name-trasf"><a href="dettagliRose.php?nome_fantasquadra=<?php echo urlencode($match['nome_fantasquadra_trasferta']);?>&anno=<?php echo $anno; ?>"><?php echo $match['nome_fantasquadra_trasferta']; ?></a></div>
                    </div>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
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
        var competizione = this.getAttribute('data-competizione');
        var anno = this.getAttribute('data-anno');
        if (anno < 2023) {
          swal("Dati non disponibili!", "Mi dispiace, ma per le competizioni antecedenti " +
            "la stagione 2023/2024 non sono disponibili i dettagli!");
        } else {
           window.location.href = "dettagliCompetizione.php?competizione=" + competizione + "&anno=" + anno;
        }
      });
    });
  });
</script>

<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>
</html>

