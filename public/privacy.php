<?php
/**
 * public/privacy.php — Informativa sulla privacy (GDPR / Reg. UE 2016/679)
 */

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';

layout_start('Privacy & Cookie', 'privacy', [
    'description' => 'Informativa sul trattamento dei dati personali e sui cookie di FantaScarrupat.',
    'breadcrumb' => [['name' => 'Home', 'url' => 'public/index.php'], ['name' => 'Privacy & Cookie']],
]);

// ── Dati del Titolare (da personalizzare) ─────────────────────────────────────
$TITOLARE_NOME  = 'Antonio Di Giorgio, referente della Lega FantaScarrupat';
$TITOLARE_EMAIL = 'antonio.digi30@hotmail.com';
$ULTIMO_AGG     = '30 maggio 2026';
?>

<section class="hero" aria-label="Privacy">
  <div class="container">
    <p class="hero__eyebrow">Documento legale</p>
    <h1 class="hero__title">Informativa Privacy &amp; Cookie</h1>
    <p class="hero__subtitle" style="max-width:60ch">
      Come trattiamo i dati personali nel rispetto del Regolamento (UE) 2016/679 (GDPR)
      e del D.lgs. 196/2003 e s.m.i. (Codice Privacy).
    </p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:820px">

    <article class="privacy-doc" style="line-height:1.7;color:var(--c-text)">

      <p style="color:var(--c-text-muted)">Ultimo aggiornamento: <strong><?= htmlspecialchars($ULTIMO_AGG) ?></strong></p>

      <h2>1. Titolare del trattamento</h2>
      <p>
        Il Titolare del trattamento dei dati è <strong><?= htmlspecialchars($TITOLARE_NOME) ?></strong>,
        contattabile all'indirizzo email <strong><?= htmlspecialchars($TITOLARE_EMAIL) ?></strong>.
        Antonio Di Giorgio è il referente indicato per i contatti, ma ogni richiesta viene
        valutata e gestita <strong>collegialmente dall'intera lega</strong>.
        FantaScarrupat è una lega di fantacalcio <strong>privata</strong>, senza scopo di lucro,
        riservata ai partecipanti.
      </p>

      <h2>2. Quali dati trattiamo</h2>
      <ul>
        <li><strong>Dati dell'account</strong> (per i partecipanti registrati): username,
            password (conservata esclusivamente in forma di <em>hash</em> crittografico, mai in chiaro),
            nome della fantasquadra, preferenza del tema grafico, date di ultimo accesso e attività.</li>
        <li><strong>Dati pubblicati nelle pagine pubbliche</strong>: nome del fantallenatore,
            eventuale foto/immagine e nome della squadra, risultati e statistiche di gioco.</li>
        <li><strong>Dati tecnici</strong>: per finalità di sicurezza, i tentativi di accesso falliti
            vengono registrati temporaneamente associandoli a un <strong>hash</strong> dell'indirizzo IP
            (l'indirizzo IP in chiaro <em>non</em> viene conservato).</li>
      </ul>
      <p>Il sito <strong>non</strong> effettua profilazione, non usa cookie di marketing e non
         impiega servizi di analytics o pubblicità di terze parti.</p>

      <h2>3. Finalità e base giuridica</h2>
      <ul>
        <li><strong>Gestione dell'area riservata e autenticazione</strong>. Base giuridica:
            esecuzione di un rapporto/servizio richiesto dall'interessato e legittimo interesse
            alla sicurezza (art. 6.1.b e 6.1.f GDPR).</li>
        <li><strong>Pubblicazione di nome, foto e risultati dei partecipanti</strong>. Base giuridica:
            <strong>consenso</strong> dell'interessato (art. 6.1.a GDPR), revocabile in qualsiasi momento.</li>
        <li><strong>Sicurezza del sito</strong> (es. limitazione dei tentativi di accesso). Base giuridica:
            legittimo interesse del Titolare (art. 6.1.f GDPR).</li>
      </ul>

      <h2>4. Natura del conferimento</h2>
      <p>Il conferimento dei dati di account è necessario per poter accedere all'area riservata:
         in mancanza non sarà possibile utilizzare i relativi servizi. La pubblicazione di foto e
         nome è facoltativa e subordinata al consenso.</p>

      <h2>5. Tempi di conservazione</h2>
      <ul>
        <li>Dati dell'account: per tutta la durata della partecipazione alla lega; vengono
            cancellati su richiesta dell'interessato o al termine della partecipazione.</li>
        <li>Registro dei tentativi di accesso (hash IP): cancellato automaticamente entro
            <strong>24 ore</strong>.</li>
      </ul>

      <h2>6. Destinatari e luogo del trattamento</h2>
      <p>
        I dati sono trattati dal Titolare e non vengono comunicati a terzi né diffusi al di fuori
        del sito, fatta eccezione per il fornitore di <strong>hosting</strong> del sito, che agisce in
        qualità di Responsabile del trattamento per la sola conservazione tecnica dei dati.
        Tutte le risorse del sito (font, librerie grafiche) sono ospitate localmente: di conseguenza
        <strong>non vengono trasferiti dati a fornitori terzi</strong> (es. Google) durante la navigazione.
      </p>

      <h2>7. Cookie e tecnologie simili</h2>
      <p>
        Il sito utilizza esclusivamente <strong>cookie tecnici</strong>, necessari al funzionamento
        e alla sicurezza:
      </p>
      <ul>
        <li><code>PHPSESSID</code>: cookie di sessione, indispensabile per l'autenticazione
            all'area riservata. Si elimina alla chiusura del browser.</li>
        <li>Una preferenza del tema grafico (chiaro/scuro) salvata localmente nel browser
            (<em>localStorage</em>), che non viene trasmessa al server.</li>
      </ul>
      <p>
        Trattandosi unicamente di strumenti tecnici/strettamente necessari, ai sensi della
        normativa vigente e delle Linee guida del Garante, <strong>non è richiesto alcun consenso
        preventivo</strong> e non viene mostrato alcun banner. Il sito non installa cookie di
        profilazione o di terze parti.
      </p>

      <h2>8. Diritti dell'interessato</h2>
      <p>In qualità di interessato puoi esercitare in ogni momento i diritti previsti dagli
         artt. 15-22 del GDPR:</p>
      <ul>
        <li>accesso ai tuoi dati;</li>
        <li>rettifica dei dati inesatti;</li>
        <li>cancellazione (&ldquo;diritto all'oblio&rdquo;);</li>
        <li>limitazione del trattamento;</li>
        <li>portabilità dei dati;</li>
        <li>opposizione al trattamento;</li>
        <li>revoca del consenso prestato, senza pregiudicare la liceità del trattamento precedente.</li>
      </ul>
      <p>
        Per esercitare tali diritti è sufficiente scrivere a
        <strong><?= htmlspecialchars($TITOLARE_EMAIL) ?></strong>.
        Hai inoltre il diritto di proporre reclamo all'Autorità di controllo competente
        (<strong>Garante per la protezione dei dati personali</strong>, www.garanteprivacy.it).
      </p>

      <h2>9. Foto e nomi dei partecipanti</h2>
      <p>
        La pubblicazione di foto e nome dei partecipanti avviene solo previo consenso dell'interessato.
        È possibile richiederne in qualsiasi momento la rimozione scrivendo al contatto sopra indicato.
      </p>

      <h2>10. Modifiche all'informativa</h2>
      <p>
        Il Titolare si riserva di aggiornare la presente informativa. Le modifiche saranno pubblicate
        su questa pagina con l'indicazione della data di ultimo aggiornamento.
      </p>

    </article>

  </div>
</section>

<?php layout_end(); ?>
