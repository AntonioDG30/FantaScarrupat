<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Elegant Dashboard | Dashboard</title>
  <!-- Favicon -->
  <link rel="shortcut icon" href="./img/svg/logo.svg" type="image/x-icon">
  <!-- Custom styles -->
  <link rel="stylesheet" href="./css/style.min.css">

  <style>
    .pagination {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      margin-top: 20px;
    }

    .pagination a,
    .pagination span {
      display: inline-block;
      padding: 10px 20px;
      margin: 5px;
      background-color: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      border: none;
      text-align: center;
    }

    .pagination a:hover {
      background-color: #0056b3;
    }

    .pagination a.active,
    .pagination span.current-page {
      background-color: #0056b3;
      pointer-events: none;
    }

    .pagination span.current-page {
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border-radius: 5px;
    }
  </style>
</head>

<body>
<?php
// Includi il file per la navbar
include 'navbarAdmin.php';
// Includi il file per la connessione al database
global $conn;
include 'php/connectionDB.php';

// Definisci il numero di risultati per pagina
$results_per_page = 25;

// Ottieni la pagina corrente, se non specificata imposta la prima pagina come default
$current_page = isset($_GET['page']) ? $_GET['page'] : 1;

// Calcola l'offset per la query SQL
$offset = ($current_page - 1) * $results_per_page;

// Esegui la query per ottenere i risultati paginati
$query = "SELECT * FROM giocatore ORDER BY ruolo DESC LIMIT $offset, $results_per_page";
$result = $conn->query($query);

// Calcola il numero totale di pagine
$query2 = "SELECT * FROM giocatore ORDER BY ruolo";
$result2 = $conn->query($query2);
$total_pages = ceil($result2->num_rows / $results_per_page);
?>

<main class="main users chart-page" id="skip-target">
  <div class="container">
    <h2 class="main-title">Visualizza Calciatori</h2>
    <div class="col-lg-12">
      <div class="users-table table-wrapper">
        <table class="posts-table">
          <thead>
          <tr class="users-table-info">
            <th>Ruolo</th>
            <th>Nome</th>
            <th>Squadra</th>
            <th>Codice Fantacalcio</th>
          </tr>
          </thead>
          <tbody id="table-body">
          <?php
          // Popola la tabella con i risultati ottenuti dalla query
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              ?>
              <tr>
                <td><?php echo $row["ruolo"]; ?></td>
                <td><?php echo $row["nome_giocatore"] ?></td>
                <td><?php echo $row["squadra_reale"] ?></td>
                <td><?php echo $row["codice_fantacalcio"] ?></td>
              </tr>
              <?php
            }
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Paginazione -->
  <div class="pagination">
    <a href="?page=1">Prima</a>
    <a href="?page=<?php echo max(($current_page - 1), 1); ?>">Precedente</a>
    <span class="current-page">Pagina <?php echo $current_page; ?> di <?php echo $total_pages; ?></span>
    <a href="?page=<?php echo min(($current_page + 1), $total_pages); ?>">Successiva</a>
    <a href="?page=<?php echo $total_pages; ?>">Ultima</a>
  </div>
</main>
</main>



  <!-- Footer -->
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

<!-- Script -->
<!-- Chart library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart library -->
<script src="./plugins/chart.min.js"></script>
<!-- Icons library -->
<script src="plugins/feather.min.js"></script>
<!-- Custom scripts -->
<script src="js/script.js"></script>
</body>

</html>
