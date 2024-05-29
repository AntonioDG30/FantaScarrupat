<!DOCTYPE html>
<html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FantaScarrupat - Admin</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="./img/favicon.png" type="image/x-icon">
    <!-- Custom styles -->
    <link rel="stylesheet" href="./css/style.min.css">
  </head>

  <body>
    <?php
      global $conn;
      include 'navbarAdmin.php';
      include 'php/connectionDB.php';
    ?>

    <!-- ! Main -->
    <?php
      global $total_views_current_month;
      global $unique_visitors_current_month;
      global $total_views_last_month;
      global $unique_visitors_last_month;
      global $total_views_percentage_change;
      global $unique_visitors_percentage_change;

      // Pagine totali visitate del mese attuale
      $sql_total_page_views_current_month = "SELECT SUM(views) AS total_page_views FROM page_views WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())";
      $result_total_page_views_current_month = $conn->query($sql_total_page_views_current_month);
      $row_total_page_views_current_month = $result_total_page_views_current_month->fetch_assoc();
      $total_page_views_current_month = $row_total_page_views_current_month['total_page_views'];

      // Pagine totali visitate del mese precedente
      $sql_total_page_views_last_month = "SELECT SUM(views) AS total_page_views FROM page_views WHERE MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
      $result_total_page_views_last_month = $conn->query($sql_total_page_views_last_month);
      $row_total_page_views_last_month = $result_total_page_views_last_month->fetch_assoc();
      $total_page_views_last_month = $row_total_page_views_last_month['total_page_views'];

      // Calcolo della percentuale di variazione delle pagine totali visitate
      if ($total_page_views_last_month != 0) {
        $total_page_views_percentage_change = (($total_page_views_current_month - $total_page_views_last_month) / $total_page_views_last_month) * 100;
      } else {
        if ($total_page_views_current_month != 0) {
          $total_page_views_percentage_change = 100; // Aumento del 100% se il mese precedente ha avuto 0 visite
        } else {
          $total_page_views_percentage_change = 0; // Nessuna variazione se entrambi i mesi hanno avuto 0 visite
        }
      }


      // Visitatori unici del mese attuale
      $sql_unique_visitors_current_month = "SELECT COUNT(DISTINCT ip_address) AS unique_visitors FROM sessions WHERE MONTH(start_time) = MONTH(CURDATE()) AND YEAR(start_time) = YEAR(CURDATE())";
      $result_unique_visitors_current_month = $conn->query($sql_unique_visitors_current_month);
      $row_unique_visitors_current_month = $result_unique_visitors_current_month->fetch_assoc();
      $unique_visitors_current_month = $row_unique_visitors_current_month['unique_visitors'];

      // Visitatori unici del mese precedente
      $sql_unique_visitors_last_month = "SELECT COUNT(DISTINCT ip_address) AS unique_visitors FROM sessions WHERE MONTH(start_time) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(start_time) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
      $result_unique_visitors_last_month = $conn->query($sql_unique_visitors_last_month);
      $row_unique_visitors_last_month = $result_unique_visitors_last_month->fetch_assoc();
      $unique_visitors_last_month = $row_unique_visitors_last_month['unique_visitors'];

      // Calcolo della percentuale di variazione dei visitatori unici
      if ($unique_visitors_last_month != 0) {
        $unique_visitors_percentage_change = (($unique_visitors_current_month - $unique_visitors_last_month) / $unique_visitors_last_month) * 100;
      } else {
        if ($unique_visitors_current_month != 0) {
          $unique_visitors_percentage_change = 100; // Aumento del 100% se il mese precedente ha avuto 0 visitatori unici
        } else {
          $unique_visitors_percentage_change = 0; // Nessuna variazione se entrambi i mesi hanno avuto 0 visitatori unici
        }
      }

    // Visitatori totali del mese attuale
    $sql_total_visit_current_month = "SELECT COUNT(ip_address) AS total_visit FROM sessions WHERE MONTH(start_time) = MONTH(CURDATE()) AND YEAR(start_time) = YEAR(CURDATE())";
    $result_total_visit_current_month = $conn->query($sql_total_visit_current_month);
    $row_total_visit_current_month = $result_total_visit_current_month->fetch_assoc();
    $total_visit_current_month = $row_total_visit_current_month['total_visit'];

    // Visitatori totali del mese precedente
    $sql_total_visit_last_month = "SELECT COUNT(ip_address) AS total_visit FROM sessions WHERE MONTH(start_time) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(start_time) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
    $result_total_visit_last_month = $conn->query($sql_total_visit_last_month);
    $row_total_visit_last_month = $result_total_visit_last_month->fetch_assoc();
    $total_visit_last_month = $row_total_visit_last_month['total_visit'];

    // Calcolo della percentuale di variazione dei visitatori totali
    if ($total_visit_last_month != 0) {
      $total_visit_percentage_change = (($total_visit_current_month - $total_visit_last_month) / $total_visit_last_month) * 100;
    } else {
      if ($total_visit_current_month != 0) {
        $total_visit_percentage_change = 100;
      } else {
        $total_visit_percentage_change = 0;
      }
    }


    // Creazione della tabella temporanea per il mese attuale
    $sql_create_temp_table_current_month = "
    CREATE TEMPORARY TABLE IF NOT EXISTS dates_table_current_month AS (
        SELECT CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY AS date_generated
        FROM
            (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
            CROSS JOIN
            (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
        WHERE CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY BETWEEN DATE_SUB(LAST_DAY(CURDATE()), INTERVAL DAY(LAST_DAY(CURDATE())) - 1 DAY) AND LAST_DAY(CURDATE())
    );";
    $conn->query($sql_create_temp_table_current_month);

    // Query per calcolare la media dei visitatori unici per il mese attuale
    $sql_media_visit_current_month = "
    SELECT AVG(unique_visitors) AS media_visit
    FROM (
        SELECT d.date_generated, COUNT(DISTINCT s.ip_address) AS unique_visitors
        FROM dates_table_current_month d
        LEFT JOIN sessions s ON DATE(s.start_time) = d.date_generated
        WHERE MONTH(d.date_generated) = MONTH(CURDATE()) AND YEAR(d.date_generated) = YEAR(CURDATE())
        GROUP BY d.date_generated
    ) AS daily_visitors_current_month;";

    $result_media_visit_current_month = $conn->query($sql_media_visit_current_month);
    $row_media_visit_current_month = $result_media_visit_current_month->fetch_assoc();
    $media_visit_current_month = $row_media_visit_current_month['media_visit'];

    // Creazione della tabella temporanea per il mese precedente
    $sql_create_temp_table_last_month = "
    CREATE TEMPORARY TABLE IF NOT EXISTS dates_table_last_month AS (
        SELECT CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY AS date_generated
        FROM
            (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a
            CROSS JOIN
            (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b
        WHERE CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY BETWEEN DATE_SUB(LAST_DAY(CURDATE() - INTERVAL 1 MONTH), INTERVAL DAY(LAST_DAY(CURDATE() - INTERVAL 1 MONTH)) - 1 DAY) AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)
    );";
    $conn->query($sql_create_temp_table_last_month);

    // Query per calcolare la media dei visitatori unici per il mese precedente
    $sql_media_visit_last_month = "
    SELECT AVG(unique_visitors) AS media_visit
    FROM (
        SELECT d.date_generated, COUNT(DISTINCT s.ip_address) AS unique_visitors
        FROM dates_table_last_month d
        LEFT JOIN sessions s ON DATE(s.start_time) = d.date_generated
        WHERE MONTH(d.date_generated) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(d.date_generated) = YEAR(CURDATE() - INTERVAL 1 MONTH)
        GROUP BY d.date_generated
    ) AS daily_visitors_last_month;";

    $result_media_visit_last_month = $conn->query($sql_media_visit_last_month);
    $row_media_visit_last_month = $result_media_visit_last_month->fetch_assoc();
    $media_visit_last_month = $row_media_visit_last_month['media_visit'];

    // Calcolo della percentuale di variazione dei media visitatori
    if ($media_visit_last_month != 0) {
      $media_visit_percentage_change = (($media_visit_current_month - $media_visit_last_month) / $media_visit_last_month) * 100;
    } else {
      if ($media_visit_current_month != 0) {
        $media_visit_percentage_change = 100;
      } else {
        $media_visit_percentage_change = 0;
      }
    }
    ?>
    <main class="main users chart-page" id="skip-target">
      <div class="container">
        <h2 class="main-title">Dashboard</h2>
        <div class="row stat-cards">
          <div class="col-md-6 col-xl-3">
            <article class="stat-cards-item">
              <?php
              if ($unique_visitors_percentage_change > 0) {
              ?>
              <div class="stat-cards-icon success">
                <?php
                } else if ($unique_visitors_percentage_change < 0) {
                ?>
                <div class="stat-cards-icon danger">
                  <?php
                  } else {
                  ?>
                  <div class="stat-cards-icon warning">
                    <?php
                    }
                    ?>
                    <i data-feather="bar-chart-2" aria-hidden="true"></i>
                  </div>
                  <div class="stat-cards-info">
                    <p class="stat-cards-info__num"><?php echo $unique_visitors_current_month?></p>
                    <p class="stat-cards-info__title">Visitatori Unici a <?PHP echo date('F');?></p></p>
                    <p class="stat-cards-info__progress">
                      <?php
                      if ($unique_visitors_percentage_change > 0) {
                      ?>
                      <span class="stat-cards-info__profit success">
                  <?php
                  } else if ($unique_visitors_percentage_change < 0) {
                  ?>
                    <span class="stat-cards-info__profit danger">
                  <?php
                  } else {
                  ?>
                    <span class="stat-cards-info__profit warning">
                  <?php
                  }
                  ?>
                      <i data-feather="trending-up" aria-hidden="true"></i>
                      <?php echo number_format($unique_visitors_percentage_change,2)?>%
                    </span>
                    rispetto a <?PHP echo date('F', strtotime('last month'));?>
                    </p>
                  </div>
            </article>
          </div>
          <div class="col-md-6 col-xl-3">
            <article class="stat-cards-item">
              <?php
                if ($total_visit_percentage_change > 0) {
              ?>
                <div class="stat-cards-icon success">
              <?php
                } else if ($total_visit_percentage_change < 0) {
              ?>
                <div class="stat-cards-icon danger">
              <?php
                } else {
              ?>
                <div class="stat-cards-icon warning">
              <?php
                }
              ?>
                  <i data-feather="bar-chart-2" aria-hidden="true"></i>
                </div>
              <div class="stat-cards-info">
                <p class="stat-cards-info__num"><?php echo $total_visit_current_month?></p>
                <p class="stat-cards-info__title">Visite totali a <?PHP echo date('F');?></p></p>
                <p class="stat-cards-info__progress">
                  <?php
                    if ($total_visit_percentage_change > 0) {
                  ?>
                    <span class="stat-cards-info__profit success">
                  <?php
                    } else if ($total_visit_percentage_change < 0) {
                  ?>
                    <span class="stat-cards-info__profit danger">
                  <?php
                    } else {
                  ?>
                    <span class="stat-cards-info__profit warning">
                  <?php
                    }
                  ?>
                      <i data-feather="trending-up" aria-hidden="true"></i>
                      <?php echo number_format($total_visit_percentage_change,2)?>%
                    </span>
                    rispetto a <?PHP echo date('F', strtotime('last month'));?>
                </p>
              </div>
            </article>
          </div>
          <div class="col-md-6 col-xl-3">
            <article class="stat-cards-item">
              <?php
              if ($total_page_views_percentage_change > 0) {
              ?>
              <div class="stat-cards-icon success">
                <?php
                } else if ($total_page_views_percentage_change < 0) {
                ?>
                <div class="stat-cards-icon danger">
                  <?php
                  } else {
                  ?>
                  <div class="stat-cards-icon warning">
                    <?php
                    }
                    ?>
                    <i data-feather="file" aria-hidden="true"></i>
                  </div>
                  <div class="stat-cards-info">
                    <p class="stat-cards-info__num"><?php echo $total_page_views_current_month?></p>
                    <p class="stat-cards-info__title">Pagine totali visitate a <?PHP echo date('F');?></p></p>
                    <p class="stat-cards-info__progress">
                      <?php
                      if ($total_page_views_percentage_change > 0) {
                      ?>
                      <span class="stat-cards-info__profit success">
                  <?php
                  } else if ($total_page_views_percentage_change < 0) {
                  ?>
                    <span class="stat-cards-info__profit danger">
                  <?php
                  } else {
                  ?>
                    <span class="stat-cards-info__profit warning">
                  <?php
                  }
                  ?>
                      <i data-feather="trending-up" aria-hidden="true"></i>
                      <?php echo number_format($total_page_views_percentage_change,2)?>%
                    </span>
                    rispetto a <?PHP echo date('F', strtotime('last month'));?>
                    </p>
                  </div>
            </article>
          </div>
          <div class="col-md-6 col-xl-3">
            <article class="stat-cards-item">
              <?php
              if ($media_visit_percentage_change > 0) {
              ?>
              <div class="stat-cards-icon success">
                <?php
                } else if ($media_visit_percentage_change < 0) {
                ?>
                <div class="stat-cards-icon danger">
                  <?php
                  } else {
                  ?>
                  <div class="stat-cards-icon warning">
                    <?php
                    }
                    ?>
                    <i data-feather="bar-chart-2" aria-hidden="true"></i>
                  </div>
                  <div class="stat-cards-info">
                    <p class="stat-cards-info__num"><?php echo number_format($media_visit_current_month,2)?></p>
                    <p class="stat-cards-info__title">Media visite giornaliere a <?PHP echo date('F');?></p></p>
                    <p class="stat-cards-info__progress">
                      <?php
                      if ($media_visit_percentage_change > 0) {
                      ?>
                      <span class="stat-cards-info__profit success">
                  <?php
                  } else if ($media_visit_percentage_change < 0) {
                  ?>
                    <span class="stat-cards-info__profit danger">
                  <?php
                  } else {
                  ?>
                    <span class="stat-cards-info__profit warning">
                  <?php
                  }
                  ?>
                      <i data-feather="trending-up" aria-hidden="true"></i>
                      <?php echo number_format($media_visit_percentage_change,2)?>%
                    </span>
                    rispetto a <?PHP echo date('F', strtotime('last month'));?>
                    </p>
                  </div>
            </article>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-9">
            <canvas id="visitorsChart" aria-label="Grafico visitatori giornalieri" role="img"></canvas>
          </div>
          <div class="col-lg-3">
            <article class="white-block">
              <div class="top-cat-title">
                <h3>Top 10 Pagine Visitate</h3>
              </div>
              <ul class="top-cat-list">
                <?php
                $query = "SELECT DISTINCT(page_url), SUM(views) AS views FROM page_views
                          WHERE MONTH(date) = MONTH(CURDATE()) GROUP BY page_url
                          ORDER BY SUM(views) DESC LIMIT 10";
                $result = $conn->query($query);
                if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                ?>
                <li>
                  <a href="<?php echo $row["page_url"]?>">
                    <div class="top-cat-list__title">
                      <?php
                        if (pathinfo(basename($row["page_url"]), PATHINFO_FILENAME) != null) {
                          echo  pathinfo(basename($row["page_url"]), PATHINFO_FILENAME);
                        } else {
                          echo "index";
                        }
                      ?>
                      <span><?php echo $row["views"]?></span>
                    </div>
                  </a>
                </li>
                <?php
                    }
                  }
                ?>
              </ul>
            </article>
            <div class="col-span-1 bg-white rounded-md dark:bg-darker">
              <!-- Card header -->
              <div class="p-4 border-b dark:border-primary">
                <h4 style="color: white;">
                  Utenti Attivi:
                  <span style="color: white;" id="usersCount">0</span>
                </h4>
              </div>
              <!-- Chart -->
              <div class="relative p-4">
                <canvas id="activeUsersChart"></canvas>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>
    <!-- ! Footer -->
    <footer class="footer">
      <div class="container footer--flex">
        <div class="footer-start">
          <p>
            2024 FantaScarrupat -
            <a href="http://fantascarrupat.altervista.org/" target="_blank" rel="noopener noreferrer">
              fantascarrupat.altervista.org
            </a>
          </p>
        </div>
      </div>
    </footer>

    <!-- Chart library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chart library -->
    <script src="./plugins/chart.min.js"></script>
    <!-- Icons library -->
    <script src="./plugins/feather.min.js"></script>
    <!-- Custom scripts -->
    <script src="js/script.js"></script>

    <script>
      // Ottieni il riferimento al canvas
      var ctx = document.getElementById('visitorsChart').getContext('2d');

      // Crea un array di etichette per gli ultimi 30 giorni
      var labels = [
        <?php
        $dates = [];
        for ($i = 29; $i >= 0; $i--) {
          $date = date('Y-m-d', strtotime("-$i days"));
          array_push($dates, $date);
        }
        echo '"' . implode('","', $dates) . '"';
        ?>
      ];

      // Crea un array di dati per il numero di visitatori giornalieri
      var data = [
        <?php
        date_default_timezone_set('Europe/Rome');
        $visitorCounts = [];
        foreach ($dates as $date) {
          // Esegui la query per ottenere il numero di visitatori per ciascun giorno
          $sql = "SELECT COUNT(DISTINCT ip_address) AS daily_visitors FROM sessions WHERE DATE(start_time) = '$date'";
          $result = $conn->query($sql);
          $row = $result->fetch_assoc();
          $dailyVisitors = $row['daily_visitors'];
          array_push($visitorCounts, $dailyVisitors);
        }
        echo implode(',', $visitorCounts);
        ?>
      ];

      // Crea il grafico utilizzando Chart.js
      var visitorsChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Visitatori giornalieri',
            data: data,
            backgroundColor: 'rgba(54, 162, 235, 0.2)', // Colore dell'area del grafico
            borderColor: 'rgba(54, 162, 235, 1)', // Colore della linea del grafico
            borderWidth: 1
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });





      // Ottieni il riferimento al canvas per il grafico degli utenti attivi
      var ctxActiveUsers = document.getElementById('activeUsersChart').getContext('2d');

      // Array per memorizzare i conteggi degli ultimi 10 avvenimenti
      var lastTenCounts = Array(10).fill(0);

      // Array per memorizzare i timestamp degli ultimi 10 avvenimenti
      var lastTenTimestamps = Array(10).fill(0);

      // Funzione per aggiornare il grafico degli utenti attivi
      function updateActiveUsersChart() {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
          if (this.readyState === 4 && this.status === 200) {
            var activeUsersData = JSON.parse(this.responseText);
            var activeUsersCount = activeUsersData.activeUsers;
            var timestamp = new Date().toLocaleTimeString(); // Timestamp attuale

            // Aggiorna gli array dei conteggi e dei timestamp
            lastTenCounts.push(activeUsersCount);
            lastTenCounts.shift(); // Rimuovi il primo elemento (il più vecchio)
            lastTenTimestamps.push(timestamp);
            lastTenTimestamps.shift(); // Rimuovi il primo elemento (il più vecchio)

            // Aggiorna il conteggio totale degli utenti attivi
            document.getElementById('usersCount').innerText = activeUsersCount;

            // Aggiorna il grafico
            activeUsersChart.data.labels = lastTenTimestamps;
            activeUsersChart.data.datasets[0].data = lastTenCounts;
            activeUsersChart.update();
          }
        };
        xhttp.open("GET", "php/getActiveUsers.php", true);
        xhttp.send();
      }

      // Crea il grafico degli utenti attivi utilizzando Chart.js
      var activeUsersChart = new Chart(ctxActiveUsers, {
        type: 'bar',
        data: {
          labels: lastTenTimestamps, // Utilizza i timestamp come etichette sull'asse x
          datasets: [{
            label: 'Utenti attivi',
            data: lastTenCounts,
            backgroundColor: 'rgba(255, 99, 132, 0.2)', // Colore dell'area del grafico
            borderColor: 'rgba(255, 99, 132, 1)', // Colore della linea del grafico
            borderWidth: 1
          }]
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });

      // Aggiorna il grafico degli utenti attivi ogni tot millisecondi (ad esempio ogni 5 secondi)
      setInterval(updateActiveUsersChart, 5000);
    </script>


  </body>

</html>
