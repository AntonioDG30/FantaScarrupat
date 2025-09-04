<?php
  declare(strict_types=1);

    // Protezione autenticazione e accesso admin
    require_once __DIR__ . '/auth/require_login.php';
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/find_userData.php';
    require_once __DIR__ . '/api/ruota.php';


    // Solo admin può accedere
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: ' . url('index.php'));
        exit;
    }

    // Rigenera CSRF token se non esiste
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
?>
<div class="layer"></div>
<!-- ! Body -->
<a class="skip-link sr-only" href="#skip-target">Skip to content</a>
<div class="page-flex">
  <!-- ! Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-start">
      <div class="sidebar-head">
        <a href="dashboardAdmin.php" class="logo-wrapper" title="Home">
          <span class="sr-only">Home</span>
          <span class="icon logo" aria-hidden="true">
            <img src="img/logo.png" height="50" width="50">
          </span>
          <div class="logo-text">
            <span class="logo-title">Dashboard</span>
            <span class="logo-subtitle">FantaScarrupat</span>
          </div>

        </a>
        <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
          <span class="sr-only">Toggle menu</span>
          <span class="icon menu-toggle" aria-hidden="true">
          </span>
        </button>
      </div>
      <div class="sidebar-body">
        <ul class="sidebar-body-menu">
          <li>
            <a  href="dashboardAdmin.php"><span class="icon home" aria-hidden="true"></span>Dashboard</a>
          </li>
          <li>
            <a class="show-cat-btn" href="##">
              <span class="icon document" aria-hidden="true"></span>Competizioni
              <span class="category__btn transparent-btn" title="Open list">
                            <span class="sr-only">Open list</span>
                            <span class="icon arrow-down" aria-hidden="true"></span>
                        </span>
            </a>
            <ul class="cat-sub-menu">
              <li>
                <a href="gestisciCompetizioni.php">Gestisci Competizioni</a>
              </li>
              <li>
                <a href="inserisciCompetizione.php?check=start">Inserisci Competizione</a>
              </li>
            </ul>
          </li>
          <li>
            <a class="show-cat-btn" href="##">
              <span class="icon folder" aria-hidden="true"></span>Rose
              <span class="category__btn transparent-btn" title="Open list">
                            <span class="sr-only">Open list</span>
                            <span class="icon arrow-down" aria-hidden="true"></span>
                        </span>
            </a>
            <ul class="cat-sub-menu">
              <li>
                <a href="visualizzaCalciatori.php?page=1">Visualizza Calciatori</a>
              </li>
              <li>
                <a href="inserisciCalciatori.php?check=start">Inserisci Calciatori</a>
              </li>
              <li>
                <a href="gestisciParametri.php">Gestisci Parametri</a>
              </li>
              <li>
                <a href="inserisciParametri.php?check=start">Inserisci Parametri</a>
              </li>
              <li>
                <a href="visualizzaRose.php">Visualizza Rose</a>
              </li>
              <li>
                <a href="inserisciRose.php?check=start">Inserisci Rose</a>
              </li>
            </ul>
          </li>
          <li>
            <a class="show-cat-btn" href="##">
              <span class="icon folder" aria-hidden="true"></span>Partecipanti
              <span class="category__btn transparent-btn" title="Open list">
                            <span class="sr-only">Open list</span>
                            <span class="icon arrow-down" aria-hidden="true"></span>
                        </span>
            </a>
            <ul class="cat-sub-menu">
              <li>
                <a href="gestisciPartecipanti.php">Gestisci Partecipanti</a>
              </li>
              <li>
                <a href="inserisciPartecipanti.php?check=start">Inserisci Partecipante</a>
              </li>
            </ul>
          </li>
          <li>
            <a class="show-cat-btn" href="##">
              <span class="icon folder" aria-hidden="true"></span>Foto Gallery
              <span class="category__btn transparent-btn" title="Open list">
                            <span class="sr-only">Open list</span>
                            <span class="icon arrow-down" aria-hidden="true"></span>
                        </span>
            </a>
            <ul class="cat-sub-menu">
              <li>
                <a href="gestisciGallery.php">Gestisci Gallery</a>
              </li>
              <li>
                <a href="inserisciFoto.php?check=start">Inserisci Foto</a>
              </li>
            </ul>
          </li>
          <span class="system-menu__title">system</span>
          <li>
            <a href="php/esportaDatabase.php">
              <span class="icon folder" aria-hidden="true"></span>
              Esporta Database
            </a>
          </li>
          <li>
            <a class="theme-switcher" href="#">
              <span class="icon folder" aria-hidden="true"></span>
              Tema Dash
            </a>
          </li>
          <li>
            <a href="php/logout.php">
              <span class="icon folder" aria-hidden="true"></span>
              Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </aside>
  <div class="main-wrapper">
    <!-- ! Main nav -->
    <nav class="main-nav--bg">
      <div class="container main-nav">
        <div class="main-nav-start">
        </div>
        <div class="main-nav-start">
          <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
            <span class="sr-only">Toggle menu</span>
            <span class="icon menu-toggle" aria-hidden="true">
          </span>
          </button>
        </div>
      </div>
    </nav>
