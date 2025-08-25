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
</head>

<body>

<?php
global $conn;
include 'php/contVisual.php';

// Classe per parsare il file LaTeX
class LaTeXParser {
  private $content;
  private $chapters = [];

  public function __construct($filePath) {
    if (file_exists($filePath)) {
      $this->content = file_get_contents($filePath);
      $this->parse();
    } else {
      throw new Exception("File LaTeX non trovato: " . $filePath);
    }
  }

  private function parse() {
    // Rimuovi commenti LaTeX
    $this->content = preg_replace('/(?<!\\\\)%.*$/m', '', $this->content);

    // Rimuovo tutti i \label{…} e il loro contenuto
    $this->content = preg_replace('/\\\\label\{[^}]*\}/', '', $this->content);

    // Rimuovo tutti i \caption{…}
    $this->content = preg_replace('/\\\\caption\{[^}]*\}/', '', $this->content);


    // Pattern per trovare i capitoli (chapter*)
    $pattern = '/\\\\chapter\{([^}]+)\}(.*?)(?=\\\\chapter|$)/s';
    preg_match_all($pattern, $this->content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
      $chapterTitle = $this->cleanLatex($match[1]);
      $chapterContent = $match[2];

      // Trova le sezioni (section*) che sono gli articoli
      $subsections = [];
      $subPattern = '/\\\\section\{([^}]+)\}(.*?)(?=\\\\section|\\\\chapter|$)/s';
      preg_match_all($subPattern, $chapterContent, $subMatches, PREG_SET_ORDER);

      foreach ($subMatches as $subMatch) {
        $subsectionTitle = $this->cleanLatex($subMatch[1]);
        $subsectionContent = $subMatch[2];

        // Cerca eventuali subsection* dentro ogni section*
        $subsubPattern = '/\\\\subsection\{([^}]+)\}(.*?)(?=\\\\subsection|\\\\section|\\\\chapter|$)/s';
        if (preg_match_all($subsubPattern, $subsectionContent, $subsubMatches, PREG_SET_ORDER) && count($subsubMatches) > 0) {
          // Se ci sono subsection*, crea un contenuto combinato
          $combinedContent = '';
          // Prima parte del contenuto prima della prima subsection
          $introContent = substr($subsectionContent, 0, strpos($subsectionContent, '\subsection*'));
          if (trim($introContent)) {
            $combinedContent .= $this->parseContent($introContent);
          }

          foreach ($subsubMatches as $subsubMatch) {
            $subsubTitle = $this->cleanLatex($subsubMatch[1]);
            $subsubContent = $this->parseContent($subsubMatch[2]);
            $combinedContent .= '<h5>' . $subsubTitle . '</h5>' . $subsubContent;
          }
          $subsectionContent = $combinedContent;
        } else {
          // Altrimenti, parsa normalmente il contenuto
          $subsectionContent = $this->parseContent($subsectionContent);
        }

        $subsections[] = [
          'title' => $subsectionTitle,
          'content' => $subsectionContent
        ];
      }

      // Se non ci sono sezioni, prendi tutto il contenuto del capitolo
      if (empty($subsections)) {
        $subsections[] = [
          'title' => 'Contenuto',
          'content' => $this->parseContent($chapterContent)
        ];
      }

      $this->chapters[] = [
        'title' => $chapterTitle,
        'subsections' => $subsections
      ];
    }
  }

  private function parseContent($content) {
    // Rimuovi spazi extra
    $content = trim($content);

    // Converti liste itemize in HTML
    $content = preg_replace_callback('/\\\\begin\{itemize\}(.*?)\\\\end\{itemize\}/s', function($matches) {
      $items = preg_split('/\\\\item/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
      $html = '<ul>';
      foreach ($items as $item) {
        $item = $this->cleanLatex(trim($item));
        if (!empty($item)) {
          $html .= '<li>' . $item . '</li>';
        }
      }
      $html .= '</ul>';
      return $html;
    }, $content);

    // Converti liste enumerate in HTML
    $content = preg_replace_callback('/\\\\begin\{enumerate\}(.*?)\\\\end\{enumerate\}/s', function($matches) {
      $items = preg_split('/\\\\item/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
      $html = '<ol>';
      foreach ($items as $item) {
        $item = $this->cleanLatex(trim($item));
        if (!empty($item)) {
          $html .= '<li>' . $item . '</li>';
        }
      }
      $html .= '</ol>';
      return $html;
    }, $content);

    $content = preg_replace_callback(
      '/\\\\begin\{(?:tabular|longtable)\}\{[^}]*\}(.*?)\\\\end\{(?:tabular|longtable)\}/s',
      function($matches) {
        // 1) estraggo il corpo
        $body = $matches[1];

        // 2) elimino caption, hline e le directives di longtable
        $body = preg_replace([
          '/\\\\caption\{[^}]*\}/',
          '/\\\\hline/',
          '/\\\\end(firsthead|head|foot|lastfoot)/',
          '/\\\\multicolumn\{[^}]*\}\{[^}]*\}\{[^}]*\}/'
        ], '', $body);

        // 3) split sulle righe LaTeX (\\)
        $rows = preg_split('/\\\\\\\\\s*/', trim($body), -1, PREG_SPLIT_NO_EMPTY);

        // 4) filtro eventuali righe ancora “vuote”
        $rows = array_filter($rows, function ($r) {
          return trim($r) !== '';
        });


        // 5) cerco la riga header (la prima che contiene \textbf)
        $headerIdx = null;
        foreach ($rows as $i => $r) {
          if (strpos($r, '\\textbf') !== false) {
            $headerIdx = $i;
            break;
          }
        }

        // 6) se non trovo \textbf, prendo la prima riga; altrimenti la uso e butto via tutto fino a quella
        if ($headerIdx === null) {
          $headerRow = array_shift($rows);
        } else {
          $headerRow = $rows[$headerIdx];
          // ricreo $rows partendo dopo l’header
          $rows = array_slice($rows, $headerIdx + 1);
        }

        // 7) costruisco la tabella
        $html  = '<table class="table table-striped table-bordered">';
        // header
        $html .= '<thead><tr style="background: #1363C6">';
        foreach (array_map('trim', explode('&', $headerRow)) as $cell) {
          // estraggo il testo da \textbf{…}, se c’è
          if (preg_match('/\\\\textbf\{(.+?)\}/', $cell, $m)) {
            $th = htmlspecialchars($m[1]);
          } else {
            $th = htmlspecialchars(preg_replace('/\\\\[a-zA-Z]+\{(.+?)\}/', '$1', $cell));
          }
          $html .= "<th>{$th}</th>";
        }
        $html .= '</tr></thead>';

        // body
        $html .= '<tbody>';
        foreach ($rows as $row) {
          $html .= '<tr>';
          foreach (array_map('trim', explode('&', $row)) as $cell) {
            // rimuovo eventuali \textbf residue senza fare altri <strong>
            $cell = preg_replace('/\\\\textbf\{(.+?)\}/', '$1', $cell);
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
          }
          $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $html = preg_replace('/\\\\\\\\/', '', $html);

        return $html;
      },
      $content
    );



    // Gestisci i paragrafi
    $paragraphs = preg_split('/\n\s*\n/', $content);
    $html = '';
    foreach ($paragraphs as $paragraph) {
      $paragraph = $this->cleanLatex(trim($paragraph));
      if (!empty($paragraph)) {
        $html .= '<p>' . $paragraph . '</p>';
      }
    }

    return $html;
  }

  private function cleanLatex($text) {

    // Rimuovo i \\ (ma solo in questa fase di “pulizia”)
    $text = preg_replace('/\\\\\\\\/', '', $text);


    // Rimuovi comandi LaTeX comuni
    $text = str_replace(['\\textbf{', '\\textit{', '\\emph{', '}'], ['<strong>', '<em>', '<em>', '</strong></em>'], $text);
    $text = preg_replace('/\\\\[a-zA-Z]+\{([^}]*)\}/', '$1', $text);
    $text = preg_replace('/\\\\[a-zA-Z]+/', '', $text);

    // Gestisci caratteri speciali LaTeX
    $text = str_replace(['\\%', '\\_', '\\&', '\\#', '\\$'], ['%', '_', '&', '#', '$'], $text);
    $text = str_replace(['``', "''", '---', '--'], ['"', '"', '—', '–'], $text);

    // Rimuovi spazi multipli
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
  }

  public function getChapters() {
    return $this->chapters;
  }
}

// Carica e parsa il file LaTeX
try {
  $parser = new LaTeXParser('file/Regolamento.tex');
  $chapters = $parser->getChapters();
} catch (Exception $e) {
  $chapters = [];
  $error = $e->getMessage();
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

<?php include 'navbar.html'; ?>

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

  <?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
      Errore nel caricamento del regolamento: <?php echo htmlspecialchars($error); ?>
    </div>
  <?php elseif (!empty($chapters)): ?>

    <div class="row">
      <div class="col-lg-4">
        <div class="nav nav-pills faq-nav" id="faq-caps" role="tablist" aria-orientation="vertical">
          <?php foreach ($chapters as $index => $chapter): ?>
            <a href="#cap<?php echo $index + 1; ?>"
               class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>"
               data-toggle="pill"
               role="tab"
               aria-controls="cap<?php echo $index + 1; ?>"
               aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
              <?php echo htmlspecialchars($chapter['title']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="tab-content" id="faq-tab-content">
          <?php foreach ($chapters as $chapterIndex => $chapter): ?>
            <div class="tab-pane <?php echo $chapterIndex === 0 ? 'show active' : ''; ?>"
                 id="cap<?php echo $chapterIndex + 1; ?>"
                 role="tabpanel"
                 aria-labelledby="cap<?php echo $chapterIndex + 1; ?>">
              <div class="accordion" id="accordion-cap-<?php echo $chapterIndex + 1; ?>">

                <?php foreach ($chapter['subsections'] as $subIndex => $subsection): ?>
                  <div class="card">
                    <div class="card-header" id="accordion-cap-<?php echo $chapterIndex + 1; ?>-heading-<?php echo $subIndex + 1; ?>">
                      <button class="btn btn-link" type="button"
                              data-toggle="collapse"
                              data-target="#accordion-cap-<?php echo $chapterIndex + 1; ?>-content-<?php echo $subIndex + 1; ?>"
                              aria-expanded="<?php echo $subIndex === 0 ? 'true' : 'false'; ?>"
                              aria-controls="accordion-cap-<?php echo $chapterIndex + 1; ?>-content-<?php echo $subIndex + 1; ?>">
                        <?php echo htmlspecialchars($subsection['title']); ?>
                      </button>
                    </div>
                    <div class="collapse <?php echo $subIndex === 0 ? 'show' : ''; ?>"
                         id="accordion-cap-<?php echo $chapterIndex + 1; ?>-content-<?php echo $subIndex + 1; ?>"
                         aria-labelledby="accordion-cap-<?php echo $chapterIndex + 1; ?>-heading-<?php echo $subIndex + 1; ?>"
                         data-parent="#accordion-cap-<?php echo $chapterIndex + 1; ?>">
                      <div class="card-body">
                        <?php echo $subsection['content']; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="alert alert-warning" role="alert">
      Errore nella generazione del regolamento, contatta l'amministratore.
    </div>
  <?php endif; ?>

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
            <td><a href="file/Contratto_Scambi_Giocatori.docx" download="Contratto_Scambi_Giocatori" class="btn btn-primary">Download</a></td>
          </tr>
          <tr>
            <th><img class="img-fluid-table" src="img/pdf.png"></th>
            <td>Modulo Richiesta Modifica Nome e/o Logo:</td>
            <td><a href="file/Richiesta_Cambio_Nome_Logo.docx" download="Richiesta_Cambio_Nome_Logo" class="btn btn-primary">Download</a></td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
<!-- Regolamento End -->
<br>

<?php include 'footer.html'; ?>

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
