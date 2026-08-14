<?php
/**
 * public/regolamento.php — Regolamento della lega
 * Struttura: sidebar titoli + accordion articoli, parser LaTeX integrato.
 *
 * Il .tex numera i Titoli in numeri romani e gli articoli di seguito da 1 a 61
 * su tutto il documento. Il parser rispetta quella numerazione, così un rinvio
 * letto sul sito porta allo stesso articolo che si legge sul PDF.
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

// Stagione mostrata nell'occhiello. Va aggiornata a mano una volta l'anno.
const REG_STAGIONE = '2026/27';

// ── Parser LaTeX ──────────────────────────────────────────────────────────────
class LaTeXParser
{
    /** Titoli presenti nel .tex ma da non pubblicare sul sito. */
    private const TITOLI_NASCOSTI = ['cap:firme'];

    private string $raw      = '';
    private array  $chapters = [];
    private array  $refs     = [];   // label => info sulla partizione che la contiene
    private array  $capitoli = [];   // label di ciascun titolo, nell'ordine del file
    private array  $blocks   = [];   // HTML già pronto, messo da parte durante il parsing

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File LaTeX non trovato: $filePath");
        }
        $this->raw = file_get_contents($filePath);
        $this->mapLabels();
        $this->parse();
    }

    /**
     * Prima passata: associa ogni \label al numero della partizione che la
     * contiene. Serve a stampare i rinvii come numeri e non come "art:4.5".
     * Va fatta sul sorgente intatto, prima che le label vengano rimosse.
     */
    private function mapLabels(): void
    {
        preg_match_all(
            '/\\\\(chapter|section|subsection|label)\{([^}]*)\}/',
            $this->raw, $tokens, PREG_SET_ORDER
        );

        $cap = 0; $art = 0; $sub = 0;
        $corrente = null;

        foreach ($tokens as $t) {
            switch ($t[1]) {
                case 'chapter':
                    $cap++; $sub = 0;
                    $corrente = ['tipo' => 'cap', 'num' => self::romano($cap),
                                 'cap'  => $cap - 1, 'art' => null];
                    $this->capitoli[] = null;   // la riempie la \label che segue
                    break;
                case 'section':
                    $art++; $sub = 0;
                    $corrente = ['tipo' => 'art', 'num' => (string) $art,
                                 'cap'  => $cap - 1, 'art' => $art];
                    break;
                case 'subsection':
                    $sub++;
                    $corrente = ['tipo' => 'art', 'num' => $art . '.' . $sub,
                                 'cap'  => $cap - 1, 'art' => $art];
                    break;
                case 'label':
                    if ($corrente === null) break;
                    $this->refs[$t[2]] = $corrente;
                    $ultimo = count($this->capitoli) - 1;
                    if ($corrente['tipo'] === 'cap' && $ultimo >= 0
                        && $this->capitoli[$ultimo] === null) {
                        $this->capitoli[$ultimo] = $t[2];
                    }
                    break;
            }
        }
    }

    public static function romano(int $n): string
    {
        $mappa = ['X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $out = '';
        foreach ($mappa as $segno => $val) {
            while ($n >= $val) { $out .= $segno; $n -= $val; }
        }
        return $out;
    }

    private function parse(): void
    {
        $c = $this->raw;

        // Commenti, label e caption non servono più
        $c = preg_replace('/(?<!\\\\)%.*$/m', '', $c);
        $c = preg_replace('/\\\\label\{[^}]*\}/', '', $c);
        $c = preg_replace('/\\\\caption\{[^}]*\}/', '', $c);

        // Involucri dei float: senza questi il testo si porta dietro
        // "table" e "[!ht]" come se fossero parole
        $c = preg_replace('/\\\\begin\{(?:table|figure|center)\}(\[[^\]]*\])?/', '', $c);
        $c = preg_replace('/\\\\end\{(?:table|figure|center)\}/', '', $c);
        $c = preg_replace('/\\\\(?:centering|noindent|clearpage|newpage|smallskip|medskip|bigskip)\b/', '', $c);
        $c = preg_replace('/\\\\(?:vspace|hspace)\*?\{[^}]*\}/', '', $c);

        // Titoli
        preg_match_all(
            '/\\\\chapter\{([^}]+)\}(.*?)(?=\\\\chapter|$)/s',
            $c, $matches, PREG_SET_ORDER
        );

        $numArticolo = 0;

        foreach ($matches as $ci => $m) {
            $body = $m[2];

            // Titolo escluso dalla pubblicazione: lo salto, ma faccio comunque
            // avanzare il contatore degli articoli così la numerazione resta
            // quella del PDF
            if (in_array($this->capitoli[$ci] ?? '', self::TITOLI_NASCOSTI, true)) {
                $numArticolo += preg_match_all('/\\\\section\{/', $body);
                continue;
            }

            $title    = $this->cleanLatex($m[1]);
            $sections = [];

            // Articoli
            preg_match_all(
                '/\\\\section\{([^}]+)\}(.*?)(?=\\\\section|\\\\chapter|$)/s',
                $body, $subs, PREG_SET_ORDER
            );

            foreach ($subs as $s) {
                $numArticolo++;
                $sTitle   = $this->cleanLatex($s[1]);
                $sContent = $s[2];
                $numSub   = 0;

                // Sotto-articoli
                if (preg_match_all(
                    '/\\\\subsection\{([^}]+)\}(.*?)(?=\\\\subsection|\\\\section|\\\\chapter|$)/s',
                    $sContent, $ss, PREG_SET_ORDER
                ) && count($ss) > 0) {
                    $combined = '';
                    $introEnd = strpos($sContent, '\subsection');
                    if ($introEnd !== false) {
                        $intro = substr($sContent, 0, $introEnd);
                        if (trim($intro)) $combined .= $this->parseContent($intro);
                    }
                    foreach ($ss as $sub) {
                        $numSub++;
                        $combined .= '<h5 class="reg-subsection" id="art-'
                            . $numArticolo . '-' . $numSub . '">'
                            . '<span class="reg-subsection__num">Art. '
                            . $numArticolo . '.' . $numSub . '</span> '
                            . htmlspecialchars($this->cleanLatex($sub[1]))
                            . '</h5>'
                            . $this->parseContent($sub[2]);
                    }
                    $sContent = $combined;
                } else {
                    $sContent = $this->parseContent($sContent);
                }

                $sections[] = [
                    'num'     => $numArticolo,
                    'title'   => $sTitle,
                    'content' => $sContent,
                ];
            }

            if (empty($sections)) {
                $sections[] = ['num' => null, 'title' => 'Contenuto',
                               'content' => $this->parseContent($body)];
            }

            $this->chapters[] = [
                'num'      => self::romano($ci + 1),
                'title'    => $title,
                'sections' => $sections,
            ];
        }
    }

    /** Mette da parte un blocco HTML già pronto, perché cleanLatex non lo tocchi. */
    private function stash(string $html): string
    {
        $this->blocks[] = $html;
        return "\x02BLOCCO" . (count($this->blocks) - 1) . "\x03";
    }

    private function restore(string $text): string
    {
        return preg_replace_callback('/\x02BLOCCO(\d+)\x03/', function ($m) {
            return $this->blocks[(int) $m[1]] ?? '';
        }, $text);
    }

    private function parseContent(string $content): string
    {
        $content = trim($content);

        // Elenchi puntati e numerati
        foreach (['itemize' => 'ul', 'enumerate' => 'ol'] as $env => $tag) {
            $content = preg_replace_callback(
                '/\\\\begin\{' . $env . '\}(.*?)\\\\end\{' . $env . '\}/s',
                function ($m) use ($tag) {
                    $items = preg_split('/\\\\item\b/', $m[1], -1, PREG_SPLIT_NO_EMPTY);
                    $html  = '<' . $tag . ' class="reg-list">';
                    foreach ($items as $item) {
                        $t = $this->cleanLatex(trim($item));
                        if ($t) $html .= '<li>' . $t . '</li>';
                    }
                    return $this->stash($html . '</' . $tag . '>');
                },
                $content
            );
        }

        // Tabelle. La specifica di colonna può contenere graffe annidate,
        // come in p{4.5cm}, quindi il pattern ne ammette un livello.
        $content = preg_replace_callback(
            '/\\\\begin\{(?:tabular|longtable)\}\s*(?:\[[^\]]*\])?\s*'
            . '\{(?:[^{}]|\{[^{}]*\})*\}(.*?)\\\\end\{(?:tabular|longtable)\}/s',
            function ($m) {
                $body = preg_replace([
                    '/\\\\caption\{[^}]*\}/',
                    '/\\\\hline/',
                    '/\\\\end(?:firsthead|head|foot|lastfoot)/',
                    '/\\\\multicolumn\{[^}]*\}\{[^}]*\}\{[^}]*\}/',
                ], '', $m[1]);

                $rows = array_values(array_filter(
                    preg_split('/\\\\\\\\\s*/', trim($body), -1, PREG_SPLIT_NO_EMPTY),
                    fn($r) => trim($r) !== ''
                ));
                if (!$rows) return '';

                $headerIdx = null;
                foreach ($rows as $i => $r) {
                    if (str_contains($r, '\textbf')) { $headerIdx = $i; break; }
                }
                if ($headerIdx === null) {
                    $headerRow = array_shift($rows);
                } else {
                    $headerRow = $rows[$headerIdx];
                    $rows = array_slice($rows, $headerIdx + 1);
                }

                $html = '<div class="reg-table-wrap"><table class="reg-table"><thead><tr>';
                foreach (array_map('trim', explode('&', $headerRow)) as $cell) {
                    $cell = preg_replace('/\\\\textbf\{(.+?)\}/', '$1', $cell);
                    $html .= '<th>' . $this->cleanLatex($cell) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                foreach ($rows as $row) {
                    $html .= '<tr>';
                    foreach (array_map('trim', explode('&', $row)) as $cell) {
                        $cell = preg_replace('/\\\\textbf\{(.+?)\}/', '$1', $cell);
                        $html .= '<td>' . $this->cleanLatex($cell) . '</td>';
                    }
                    $html .= '</tr>';
                }
                return $this->stash($html . '</tbody></table></div>');
            },
            $content
        );

        // Capoversi
        $html = '';
        foreach (preg_split('/\n\s*\n/', $content) as $p) {
            $p = $this->cleanLatex(trim($p));
            if ($p === '') continue;
            $html .= preg_match('/^\x02BLOCCO\d+\x03$/', $p)
                ? $this->restore($p)
                : '<p>' . $this->restore($p) . '</p>';
        }
        return $html;
    }

    private function cleanLatex(string $text): string
    {
        $text = preg_replace('/\\\\\\\\/', '', $text);

        // Rinvii: diventano il numero dell'articolo, cliccabile
        $text = preg_replace_callback('/\\\\ref\{([^}]*)\}/', function ($m) {
            $k = $m[1];
            if (!isset($this->refs[$k])) return htmlspecialchars($k);
            $r = $this->refs[$k];
            $href = $r['tipo'] === 'cap' ? '#tit-' . $r['num'] : '#art-' . $r['num'];
            return '<a class="reg-ref" href="' . $href . '"'
                 . ' data-cap="' . $r['cap'] . '"'
                 . ($r['art'] !== null ? ' data-art="' . $r['art'] . '"' : '')
                 . '>' . $r['num'] . '</a>';
        }, $text);

        // Grassetto e corsivo
        $text = preg_replace('/\\\\textbf\{([^}]*)\}/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\\\\(?:textit|emph)\{([^}]*)\}/', '<em>$1</em>', $text);
        // Comandi generici con argomento, poi senza
        $text = preg_replace('/\\\\[a-zA-Z]+\{([^}]*)\}/', '$1', $text);
        $text = preg_replace('/\\\\[a-zA-Z]+\*?/', '', $text);
        // Caratteri speciali
        $text = str_replace(['\\%', '\\_', '\\&', '\\#', '\\$'], ['%', '_', '&', '#', '$'], $text);
        $text = str_replace(['``', "''", '---', '--'],
                            ["\u{201c}", "\u{201d}", "\u{2014}", "\u{2013}"], $text);
        // Pulizia
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    public function getChapters(): array { return $this->chapters; }
}

// ── Carica LaTeX ──────────────────────────────────────────────────────────────
$chapters = [];
$latex_error = null;
try {
    $parser   = new LaTeXParser(__DIR__ . '/file/regolamento.tex');
    $chapters = $parser->getChapters();
} catch (\Throwable $e) {
    $latex_error = $e->getMessage();
}

// ── Parametri rosa dal DB ─────────────────────────────────────────────────────
$db = getDB();
$parametri = $db->query("
    SELECT numero_parametro, testo_parametro
    FROM parametri_rosa
    WHERE flag_visibile = 1
    ORDER BY numero_parametro ASC
")->fetchAll();

$chapter_count  = count($chapters);
// Solo gli articoli veri: il capitolo delle firme non ne ha e finirebbe nel conto
$articoli_tot   = array_sum(array_map(
    fn($c) => count(array_filter($c['sections'], fn($s) => $s['num'] !== null)),
    $chapters
));

layout_start('Regolamento', 'regolamento', [
    'description' => 'Il regolamento ufficiale di FantaScarrupat: struttura, votazioni, calcolo punteggi, mercato e competizioni.',
    'og_type'    => 'article',
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => 'Regolamento']],
]);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Regolamento">
  <div class="container">
    <p class="hero__eyebrow">Stagione <?= htmlspecialchars(REG_STAGIONE) ?></p>
    <h1 class="hero__title">Regolamento</h1>
    <p class="hero__subtitle" style="max-width:50ch">
      Tutto ciò che governa la lega: struttura, votazioni, calcolo punteggi,
      mercato, infortuni, competizioni. In vigore dalla fondazione, aggiornato ogni stagione.
    </p>
    <?php if ($chapter_count > 0): ?>
    <div class="hero__stats">
      <div>
        <div class="hero__stat-value"><?= $chapter_count ?></div>
        <div class="hero__stat-label">Titoli</div>
      </div>
      <div>
        <div class="hero__stat-value"><?= $articoli_tot ?></div>
        <div class="hero__stat-label">Articoli</div>
      </div>
      <?php if (!empty($parametri)): ?>
      <div>
        <div class="hero__stat-value"><?= count($parametri) ?></div>
        <div class="hero__stat-label">Parametri</div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Corpo ─────────────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">

<?php if ($latex_error): ?>
    <div style="padding:var(--sp-8);border:1px solid oklch(55% 0.18 28 / 0.35);
                border-radius:var(--r-xl);color:oklch(72% 0.15 28);
                background:oklch(55% 0.18 28 / 0.06)">
      <strong>Errore nel caricamento del regolamento.</strong>
      Il file LaTeX non è raggiungibile. Usa il download PDF qui sotto.
    </div>

<?php elseif (!empty($chapters)): ?>

    <div class="reg-layout">

      <!-- ── Sidebar titoli ── -->
      <aside class="reg-sidebar" aria-label="Titoli">
        <div class="reg-sidebar__inner">
          <div class="reg-sidebar__heading">Titoli</div>
          <nav class="reg-sidebar__nav" role="tablist" aria-orientation="vertical">
            <?php foreach ($chapters as $ci => $ch): ?>
            <button
              class="reg-chap-btn<?= $ci === 0 ? ' is-active' : '' ?>"
              role="tab"
              aria-selected="<?= $ci === 0 ? 'true' : 'false' ?>"
              aria-controls="reg-panel-<?= $ci ?>"
              id="reg-tab-<?= $ci ?>"
              data-chapter="<?= $ci ?>">
              <span class="reg-chap-btn__num"><?= htmlspecialchars($ch['num']) ?></span>
              <span class="reg-chap-btn__label"><?= htmlspecialchars($ch['title']) ?></span>
            </button>
            <?php endforeach; ?>
          </nav>

          <!-- Download PDF -->
          <div class="reg-sidebar__download">
              <a href="<?= BASE_PATH ?>public/file/Regolamento_Ufficiale.pdf"
                 download="Regolamento_FantaScarrupat"
                 class="reg-download-btn"
                 style="margin-bottom: 8px; display: inline-block;">
                <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">picture_as_pdf</span>
                Scarica PDF Regolamento
              </a>
              <a href="<?= BASE_PATH ?>public/file/Richiesta_Cambio_Nome_Logo.docx"
                 download="Richiesta_Cambio_Nome_Logo"
                 class="reg-download-btn">
                <span class="material-symbols-rounded" style="font-size:16px;font-variation-settings:'FILL' 1">picture_as_pdf</span>
                Scarica Word modello cambio nome e logo
              </a>
          </div>
        </div>
      </aside>

      <!-- ── Pannelli titolo ── -->
      <div class="reg-content">
        <?php foreach ($chapters as $ci => $ch): ?>
        <div
          class="reg-panel<?= $ci === 0 ? ' is-active' : '' ?>"
          id="reg-panel-<?= $ci ?>"
          role="tabpanel"
          aria-labelledby="reg-tab-<?= $ci ?>">

          <!-- Titolo -->
          <header class="reg-panel__header" id="tit-<?= htmlspecialchars($ch['num']) ?>">
            <span class="reg-panel__num">Titolo <?= htmlspecialchars($ch['num']) ?></span>
            <h2 class="reg-panel__title"><?= htmlspecialchars($ch['title']) ?></h2>
            <p class="reg-panel__meta">
              <?= count($ch['sections']) ?> articol<?= count($ch['sections']) === 1 ? 'o' : 'i' ?>
            </p>
          </header>

          <!-- Articoli accordion -->
          <div class="reg-accordion" id="reg-acc-<?= $ci ?>">
            <?php foreach ($ch['sections'] as $si => $sec): ?>
            <div class="reg-acc-item"
                 id="<?= $sec['num'] !== null ? 'art-' . $sec['num'] : 'reg-acc-' . $ci . '-' . $si ?>"
                 data-art="<?= $sec['num'] ?>">
              <button
                class="reg-acc-trigger<?= ($ci === 0 && $si === 0) ? ' is-open' : '' ?>"
                aria-expanded="<?= ($ci === 0 && $si === 0) ? 'true' : 'false' ?>"
                aria-controls="reg-acc-body-<?= $ci ?>-<?= $si ?>">
                <span class="reg-acc-trigger__label">
                  <?php if ($sec['num'] !== null): ?>
                  <span class="reg-acc-trigger__art">Art. <?= $sec['num'] ?></span>
                  <?php endif; ?>
                  <?= htmlspecialchars($sec['title']) ?>
                </span>
                <span class="reg-acc-trigger__icon material-symbols-rounded">expand_more</span>
              </button>
              <div
                class="reg-acc-body<?= ($ci === 0 && $si === 0) ? ' is-open' : '' ?>"
                id="reg-acc-body-<?= $ci ?>-<?= $si ?>"
                role="region">
                <div class="reg-acc-body__inner">
                  <?= $sec['content'] ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

        </div><!-- /panel -->
        <?php endforeach; ?>
      </div><!-- /content -->

    </div><!-- /reg-layout -->

<?php else: ?>
    <div style="text-align:center;padding:var(--sp-16) 0;color:var(--c-text-muted)">
      <span class="material-symbols-rounded"
            style="font-size:48px;display:block;margin-bottom:var(--sp-4);opacity:0.4">
        description
      </span>
      <p>Il regolamento non è ancora disponibile.</p>
    </div>
<?php endif; ?>

  </div><!-- /container -->
</section>

<!-- ── Script tabs + accordion + rinvii ──────────────────────────────────────── -->
<script>
(function () {
  var tabs   = document.querySelectorAll('.reg-chap-btn');
  var panels = document.querySelectorAll('.reg-panel');

  function apriTitolo(idx) {
    tabs.forEach(function (b) {
      var attivo = parseInt(b.dataset.chapter, 10) === idx;
      b.classList.toggle('is-active', attivo);
      b.setAttribute('aria-selected', attivo ? 'true' : 'false');
    });
    panels.forEach(function (p) { p.classList.remove('is-active'); });
    var panel = document.getElementById('reg-panel-' + idx);
    if (panel) panel.classList.add('is-active');
    return panel;
  }

  function apriArticolo(item) {
    if (!item) return;
    var btn = item.querySelector('.reg-acc-trigger');
    if (!btn) return;
    var panel = item.closest('.reg-panel');
    if (panel) {
      panel.querySelectorAll('.reg-acc-trigger.is-open').forEach(function (b) {
        b.classList.remove('is-open');
        b.setAttribute('aria-expanded', 'false');
        var body = document.getElementById(b.getAttribute('aria-controls'));
        if (body) body.classList.remove('is-open');
      });
    }
    btn.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    var body = document.getElementById(btn.getAttribute('aria-controls'));
    if (body) body.classList.add('is-open');
  }

  // ── Tab dei titoli ──────────────────────────────────────────────────────────
  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var idx   = parseInt(this.dataset.chapter, 10);
      var panel = apriTitolo(idx);
      if (window.innerWidth < 840 && panel) {
        setTimeout(function () {
          panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 60);
      }
    });
  });

  // ── Accordion degli articoli ────────────────────────────────────────────────
  document.querySelectorAll('.reg-acc-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var isOpen = this.classList.contains('is-open');
      var panel  = this.closest('.reg-panel');
      if (panel) {
        panel.querySelectorAll('.reg-acc-trigger.is-open').forEach(function (b) {
          b.classList.remove('is-open');
          b.setAttribute('aria-expanded', 'false');
          var body = document.getElementById(b.getAttribute('aria-controls'));
          if (body) body.classList.remove('is-open');
        });
      }
      if (!isOpen) {
        this.classList.add('is-open');
        this.setAttribute('aria-expanded', 'true');
        var body = document.getElementById(this.getAttribute('aria-controls'));
        if (body) body.classList.add('is-open');
      }
    });
  });

  // ── Rinvii interni: apre il titolo giusto, apre l'articolo, ci porta ────────
  function vaiA(hash) {
    if (!hash) return false;
    var target = document.querySelector(hash);
    if (!target) return false;
    var panel = target.closest('.reg-panel');
    if (panel) {
      var idx = parseInt(panel.id.replace('reg-panel-', ''), 10);
      apriTitolo(idx);
    }
    var item = target.classList.contains('reg-acc-item')
             ? target
             : target.closest('.reg-acc-item');
    if (item) apriArticolo(item);
    setTimeout(function () {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 80);
    return true;
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a.reg-ref') : null;
    if (!a) return;
    var hash = a.getAttribute('href');
    if (hash && hash.charAt(0) === '#' && vaiA(hash)) {
      e.preventDefault();
      history.replaceState(null, '', hash);
    }
  });

  if (window.location.hash) vaiA(window.location.hash);
}());
</script>

<?php layout_end(); ?>
