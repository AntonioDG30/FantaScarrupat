<?php
/**
 * public/contattaci.php — Pagina contatti
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../config/database.php';

layout_start('Contattaci', 'contattaci', [
    'description' => 'FantaScarrupat è una lega privata. Per entrare in contatto con i gestori.',
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => 'Contattaci']],
]);
?>

<!-- ── Hero ──────────────────────────────────────────────────────────────────── -->
<section class="hero" aria-label="Contatti">
  <div class="container">
    <p class="hero__eyebrow">Lega privata</p>
    <h1 class="hero__title">Contattaci</h1>
    <p class="hero__subtitle" style="max-width:50ch">
      Se necessiti di maggiori informazioni o hai delle curiosità, contattaci!
    </p>
  </div>
</section>

<!-- ── Contenuto ─────────────────────────────────────────────────────────────── -->
<section class="section">
  <div class="container">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-16);align-items:start"
         class="contact-layout">

      <!-- ── Sinistra: informazioni ── -->
      <div>
        <h2 style="margin-bottom:var(--sp-5)">Chi siamo</h2>
        <p style="margin-bottom:var(--sp-5)">
          FantaScarrupat è una lega di fantacalcio privata attiva dal 2016,
          composta da <?= (int)getDB()->query("SELECT COUNT(*) FROM fantasquadra WHERE flag_attuale = 1")->fetchColumn() ?>
          partecipanti.
        </p>
        <p style="margin-bottom:var(--sp-5)">
          Questo sito è lo spazio ufficiale della lega: archivio storico,
          schede delle squadre, galleria fotografica e area riservata
          per i partecipanti.
        </p>
        <p style="color:var(--c-text-muted)">
          Se sei un partecipante e hai perso le credenziali di accesso,
          contatta direttamente l'amministratore della lega.
        </p>

        <div style="margin-top:var(--sp-10);
                    padding:var(--sp-6);
                    background:var(--c-bg-alt);
                    border-radius:var(--r-xl)">
          <div style="display:flex;align-items:center;gap:var(--sp-3);
                      margin-bottom:var(--sp-4)">
            <span class="material-symbols-rounded"
                  style="font-size:20px;color:var(--c-accent);font-variation-settings:'FILL' 1">
              lock
            </span>
            <strong>Sei già un partecipante?</strong>
          </div>
          <p style="color:var(--c-text-muted);margin-bottom:var(--sp-5);font-size:0.9375rem">
            Accedi all'area riservata per gestire la tua rosa e consultare
            le classifiche della stagione in corso.
          </p>
          <a href="<?= BASE_PATH ?>login.php" class="btn btn-primary btn-sm btn-pill">
            <span class="material-symbols-rounded"
                  style="font-size:15px;font-variation-settings:'FILL' 1">lock</span>
            Accedi
          </a>
        </div>
      </div>

      <!-- ── Destra: form ── -->
      <div>
        <h2 style="margin-bottom:var(--sp-6)">Scrivi un messaggio</h2>

        <!-- Nota informativa -->
        <div class="alert alert-success" style="margin-bottom:var(--sp-6)">
          <span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0">info</span>
          <span style="font-size:0.875rem">
            Non distribuiamo credenziali né accogliamo nuovi partecipanti tramite questo form.
            Per qualsiasi richiesta legata all'accesso, contatta direttamente l'amministratore.
          </span>
        </div>

        <form method="post"
              action="#"
              onsubmit="handleContactForm(event)"
              novalidate>

          <div style="display:flex;flex-direction:column;gap:var(--sp-5)">

            <div class="form-group">
              <label for="cf-nome" class="form-label">Nome</label>
              <input id="cf-nome"
                     name="nome"
                     type="text"
                     class="form-control"
                     placeholder="Il tuo nome"
                     autocomplete="name"
                     required>
            </div>

            <div class="form-group">
              <label for="cf-email" class="form-label">Email</label>
              <input id="cf-email"
                     name="email"
                     type="email"
                     class="form-control"
                     placeholder="la.tua@email.it"
                     autocomplete="email"
                     required>
            </div>

            <div class="form-group">
              <label for="cf-oggetto" class="form-label">Oggetto</label>
              <select id="cf-oggetto" name="oggetto" class="form-control form-select" required>
                <option value="">Seleziona...</option>
                <option value="info">Informazioni generali</option>
                <option value="accesso">Problemi di accesso</option>
                <option value="altro">Altro</option>
              </select>
            </div>

            <div class="form-group">
              <label for="cf-messaggio" class="form-label">Messaggio</label>
              <textarea id="cf-messaggio"
                        name="messaggio"
                        class="form-control"
                        rows="5"
                        placeholder="Scrivi qui il tuo messaggio..."
                        required
                        style="resize:vertical"></textarea>
            </div>

            <div>
              <button type="submit" class="btn btn-primary btn-md btn-pill">
                <span class="material-symbols-rounded" style="font-size:16px">send</span>
                Invia messaggio
              </button>
              <p style="font-size:0.8125rem;color:var(--c-text-muted);margin-top:var(--sp-3)">
                Inviando il messaggio dichiari di aver letto l'<a href="<?= BASE_PATH ?>public/privacy.php">Informativa Privacy</a>.
              </p>
            </div>

          </div><!-- /form fields -->

        </form>

        <!-- Feedback di invio (nascosto inizialmente) -->
        <div id="contact-feedback"
             style="display:none;margin-top:var(--sp-6)"
             role="alert">
        </div>

      </div><!-- /form column -->

    </div><!-- /grid -->

  </div>
</section>

<style>
@media (max-width: 720px) {
  .contact-layout { grid-template-columns: 1fr !important; }
}
</style>

<script>
function handleContactForm(e) {
  e.preventDefault();
  var form     = e.target;
  var feedback = document.getElementById('contact-feedback');
  var nome     = form.nome.value.trim();
  var email    = form.email.value.trim();
  var oggetto  = form.oggetto.value;
  var msg      = form.messaggio.value.trim();

  if (!nome || !email || !oggetto || !msg) {
    feedback.style.display = 'block';
    feedback.className = 'alert alert-danger';
    feedback.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0">error</span>'
                       + '<span>Compila tutti i campi prima di inviare.</span>';
    return;
  }

  // Simulazione invio (senza backend configurato)
  form.querySelector('button[type=submit]').disabled = true;
  feedback.style.display = 'block';
  feedback.className = 'alert alert-success';
  feedback.innerHTML = '<span class="material-symbols-rounded" style="font-size:18px;flex-shrink:0">check_circle</span>'
                     + '<span>Messaggio ricevuto. Risponderemo il prima possibile.</span>';
  form.reset();
}
</script>

<?php layout_end(); ?>
