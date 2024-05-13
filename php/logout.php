<?php
  // Inizia o ripristina la sessione
  session_start();

  // Distruggi tutte le variabili di sessione
  session_unset();

  // Distruggi la sessione
  session_destroy();

  // Reindirizza alla pagina index.php
  header("Location: ../index.php");
  exit; // Assicura che lo script termini qui
?>
