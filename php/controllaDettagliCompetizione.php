<?php
// Verifica se il parametro 'competizione' e 'anno' sono stati passati nella query string
if(isset($_GET['competizione']) && isset($_GET['anno'])) {
  // Connessione al database
  $conn = new mysqli('host', 'username', 'password', 'nome_database');

  // Verifica della connessione
  if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
  }

  $competizione = $_GET['competizione'];
  $anno = $_GET['anno'];

  // Esegui la query per verificare se ci sono corrispondenze tra competizioni_disputate e partite_disputate
  $query = "SELECT id_competizione_disputata FROM partita_avvessario WHERE id_competizione_disputata IN (SELECT id_competizione_disputata FROM competizione_disputata as c WHERE nome_competizione = '$competizione' AND anno = '$anno'";
  $result = $conn->query($query);

  if ($result->num_rows > 0) {
    echo "successo";
  } else {
    echo "errore";
  }

  $conn->close();
} else {
  echo "Parametri mancanti";
}
?>

