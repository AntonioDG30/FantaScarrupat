<?php
session_start();

if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit;
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
            <img src="img/menu.png">
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
                <a href="inserisciCompetizione.php">Inserisci Competizione</a>
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
                <a href="inserisciCalciatori.php">Inserisci Calciatori</a>
              </li>
              <li>
                <a href="inserisciRose.php">Inserisci Rose</a>
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
                <a href="inserisciPartecipanti.php">Inserisci Partecipante</a>
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
                <a href="inserisciFoto.php">Inserisci Foto</a>
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
        <div class="main-nav-end">
          <button class="sidebar-toggle transparent-btn" title="Menu" type="button">
            <span class="sr-only">Toggle menu</span>
            <span class="icon menu-toggle--gray" aria-hidden="true">
              <img src="img/menu.png">
            </span>
          </button>
          <button class="theme-switcher gray-circle-btn" type="button" title="Switch theme">
            <span class="sr-only">Switch theme</span>
            <i class="sun-icon" data-feather="sun" aria-hidden="true"></i>
            <i class="moon-icon" data-feather="moon" aria-hidden="true"></i>
          </button>
          <div class="nav-user-wrapper">
            <button href="profilo.php" class="nav-user-btn dropdown-btn" title="My profile" type="button">
              <span class="sr-only">My profile</span>
              <span class="nav-user-img">
                <a href="profilo.php">
                  <picture>
                    <source srcset="./img/admin.jpeg" type="image/webp">
                    <img src="./img/admin.jpeg" alt="User name">
                  </picture>
                </a>
              </span>
            </button>
          </div>
        </div>
      </div>
    </nav>
