/**
 * sorteggio.js — Sorteggio asta FantaScarrupat
 *
 * Chiama api/ruota.php per ottenere l'ordine casuale,
 * poi rivela i partecipanti uno per uno con animazione.
 */
(function () {
  'use strict';

  var _lista      = [];   // nomi ordinati (nascosti)
  var _rivelati   = 0;    // quanti sono già visibili

  var elList      = document.getElementById('sorteggio-list');
  var elEmpty     = document.getElementById('sorteggio-empty');
  var elMsg       = document.getElementById('sorteggio-msg');
  var btnSorteggia = document.getElementById('btn-sorteggia');
  var btnRivela    = document.getElementById('btn-rivela');
  var btnRivelaTutti = document.getElementById('btn-rivela-tutti');
  var btnIcon     = document.getElementById('btn-icon');
  var btnLabel    = document.getElementById('btn-label');

  /* ── Helpers ─────────────────────────────────────────────────── */

  function setMsg(text) {
    elMsg.textContent = text;
  }

  function enableRivela(on) {
    var btns = [btnRivela, btnRivelaTutti];
    btns.forEach(function (b) {
      b.disabled = !on;
      b.style.opacity   = on ? '1'              : '.4';
      b.style.color     = on ? 'var(--c-text)'  : '';
      b.style.borderColor = on ? 'var(--c-border-strong)' : '';
      b.style.cursor    = on ? 'pointer'        : 'default';
    });
  }

  /* ── Costruisce la lista (nascosta) ─────────────────────────── */
  function buildList(nomi) {
    // Rimuove il placeholder
    if (elEmpty) elEmpty.remove();

    // Rimuove le card precedenti
    elList.querySelectorAll('.sorteggio-item').forEach(function (el) { el.remove(); });

    nomi.forEach(function (nome, i) {
      var item = document.createElement('div');
      item.className  = 'sorteggio-item';
      item.dataset.i  = i;
      item.setAttribute('role', 'listitem');
      item.innerHTML =
        '<div class="sorteggio-item__num">' + (i + 1) + '</div>' +
        '<div class="sorteggio-item__name" style="opacity:0;filter:blur(6px);transition:opacity .35s,filter .35s">' +
          escHtml(nome) +
        '</div>';
      elList.appendChild(item);
    });
  }

  /* ── Rivela un singolo item ─────────────────────────────────── */
  function revelaOne() {
    if (_rivelati >= _lista.length) return;

    var items = elList.querySelectorAll('.sorteggio-item');
    var item  = items[_rivelati];
    if (!item) return;

    item.classList.add('revealed');

    var nameEl = item.querySelector('.sorteggio-item__name');
    // Piccolo frame per attivare la transizione CSS
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        nameEl.style.opacity = '1';
        nameEl.style.filter  = 'none';
      });
    });

    _rivelati++;
    setMsg(_rivelati + ' di ' + _lista.length + ' rivelati');

    if (_rivelati >= _lista.length) {
      enableRivela(false);
      setMsg('Ordine completo. Premi Sorteggia per un nuovo estrazione.');
      btnLabel.textContent = 'Nuovo sorteggio';
      btnIcon.textContent  = 'refresh';
    }
  }

  /* ── Rivela tutti con stagger ───────────────────────────────── */
  function revelaTutti() {
    if (_rivelati >= _lista.length) return;
    var items = elList.querySelectorAll('.sorteggio-item');
    var delay = 0;
    for (var i = _rivelati; i < _lista.length; i++) {
      (function (idx) {
        setTimeout(function () {
          var item   = items[idx];
          var nameEl = item.querySelector('.sorteggio-item__name');
          item.classList.add('revealed');
          requestAnimationFrame(function () {
            requestAnimationFrame(function () {
              nameEl.style.opacity = '1';
              nameEl.style.filter  = 'none';
            });
          });
        }, delay);
      }(i));
      delay += 120;
    }
    _rivelati = _lista.length;
    setTimeout(function () {
      enableRivela(false);
      setMsg('Ordine completo. Premi Sorteggia per una nuova estrazione.');
      btnLabel.textContent = 'Nuovo sorteggio';
      btnIcon.textContent  = 'refresh';
    }, delay);
  }

  /* ── Sorteggio via API ──────────────────────────────────────── */
  function sorteggia() {
    btnSorteggia.disabled = true;
    btnLabel.textContent  = 'Sorteggio...';
    btnIcon.textContent   = 'hourglass_top';
    setMsg('');
    enableRivela(false);

    var fd = new FormData();
    fd.append('action',     'shuffle');
    fd.append('csrf_token', window.CSRF_TOKEN || '');

    fetch((window.BASE_PATH || '/') + 'api/ruota.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: fd,
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        if (!data || !Array.isArray(data.partecipanti)) throw new Error('Risposta non valida');

        _lista    = data.partecipanti;
        _rivelati = 0;

        buildList(_lista);
        enableRivela(true);
        setMsg('Sorteggio completato. ' + _lista.length + ' partecipanti.');
        btnLabel.textContent = 'Sorteggia';
        btnIcon.textContent  = 'shuffle';
        btnSorteggia.disabled = false;
      })
      .catch(function (err) {
        setMsg('Errore durante il sorteggio. Riprova.');
        btnLabel.textContent  = 'Sorteggia';
        btnIcon.textContent   = 'shuffle';
        btnSorteggia.disabled = false;
      });
  }

  /* ── HTML escape ────────────────────────────────────────────── */
  function escHtml(str) {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ── Event listeners ────────────────────────────────────────── */
  btnSorteggia.addEventListener('click', sorteggia);
  btnRivela.addEventListener('click', revelaOne);
  btnRivelaTutti.addEventListener('click', revelaTutti);

  /* ── Keyboard: Space/Enter su Rivela prossimo ───────────────── */
  btnRivela.addEventListener('keydown', function (e) {
    if ((e.key === ' ' || e.key === 'Enter') && !btnRivela.disabled) {
      e.preventDefault();
      revelaOne();
    }
  });

}());
