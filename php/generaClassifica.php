<?php
// Script da modificare ancora, manca il riconoscimento dell'anno e della competizione
// più controllo gironi e fasi finali


// Connessione al database
global $conn;
include 'connectionDB.php';

// Verifica la connessione
if ($conn->connect_error) {
  die("Connessione fallita: " . $conn->connect_error);
}

// Query per selezionare tutte le partite disputate
$sql = "SELECT nome_fantasquadra_casa, nome_fantasquadra_trasferta, gol_casa, gol_trasferta,
       punteggio_casa, punteggio_trasferta, tipologia, girone FROM partita_avvessario";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // Inizializza un array associativo per tenere traccia delle statistiche di ogni squadra
  $classifica = array();

  // Loop attraverso ogni partita
  while($row = $result->fetch_assoc()) {
    $casa = $row['nome_fantasquadra_casa'];
    $trasferta = $row['nome_fantasquadra_trasferta'];
    $gol_casa = $row['gol_casa'];
    $gol_trasferta = $row['gol_trasferta'];
    $punteggio_casa = $row['punteggio_casa'];
    $punteggio_trasferta = $row['punteggio_trasferta'];

    // Aggiorna le statistiche delle squadre basandosi sui risultati della partita

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
      $classifica[$casa]['punti'] +=3;
    } elseif ($gol_trasferta > $gol_casa) {
      $classifica[$casa]['sconfitte']++;
    } else {
      $classifica[$casa]['pareggi']++;
      $classifica[$casa]['punti'] +=1;
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
      $classifica[$trasferta]['punti'] +=3;
    } elseif ($gol_casa > $gol_trasferta) {
      $classifica[$trasferta]['sconfitte']++;
    } else {
      $classifica[$trasferta]['pareggi']++;
      $classifica[$trasferta]['punti'] +=1;
    }
  }

  // Ordina la classifica in ordine decrescente di punti totali
  arsort($classifica);

  // Stampa la classifica finale
  echo "Classifica Finale:\n";
  $posizione = 1;
  foreach ($classifica as $squadra => $stats) {
    echo "$posizione. $squadra - Punti: {$stats['punti']}, Punteggio Totale: {$stats['punteggio_totale']}, Gol Fatti: {$stats['gol_fatti']}, Gol Subiti: {$stats['gol_subiti']}, Vittorie: {$stats['vittorie']}, Sconfitte: {$stats['sconfitte']}, Pareggi: {$stats['pareggi']}\n";
    $posizione++;
  }
} else {
  echo "Nessuna partita trovata.";
}

// Chiudi la connessione al database
$conn->close();
?>
