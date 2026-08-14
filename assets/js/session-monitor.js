/**
 * session-monitor.js — FantaScarrupat
 *
 * Monitora la validità della sessione e reindirizza al login alla scadenza.
 * Polling su api/auth.php?action=check ogni POLL_INTERVAL ms.
 * Mostra un toast quando rimangono meno di WARN_THRESHOLD secondi.
 */
(function () {
  'use strict';

  var POLL_INTERVAL  = 120000;  // 2 minuti
  var WARN_THRESHOLD = 120;     // avvisa sotto i 2 minuti
  var _toast = null;

  function basePath() {
    return (window.BASE_PATH != null ? window.BASE_PATH : '/');
  }

  function showToast() {
    if (_toast) return;
    _toast = document.createElement('div');
    _toast.setAttribute('role', 'alert');
    _toast.setAttribute('aria-live', 'assertive');
    _toast.style.cssText = [
      'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
      'padding:14px 20px',
      'background:var(--c-bg-card,#1F2937)',
      'border:1px solid var(--c-border-strong,rgba(255,255,255,.16))',
      'border-radius:10px',
      'font-size:0.875rem',
      'color:var(--c-text,#F9FAFB)',
      'box-shadow:0 4px 16px rgba(0,0,0,.4)',
      'display:flex', 'align-items:center', 'gap:10px',
      'max-width:320px',
    ].join(';');
    _toast.innerHTML =
      '<span class="material-symbols-rounded" ' +
      'style="font-size:18px;color:var(--c-gold,#FBBF24);font-variation-settings:\'FILL\' 1">' +
      'timer</span>' +
      '<span>Sessione in scadenza. Salva il lavoro.</span>';
    document.body.appendChild(_toast);
  }

  function hideToast() {
    if (_toast) { _toast.remove(); _toast = null; }
  }

  function redirectToLogin() {
    hideToast();
    window.location.href = basePath() + 'login.php?reason=idle';
  }

  function poll() {
    fetch(basePath() + 'api/auth.php?action=check', {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
    })
      .then(function (res) {
        if (!res.ok) { redirectToLogin(); return null; }
        return res.json();
      })
      .then(function (data) {
        if (!data) return;
        if (!data.valid) { redirectToLogin(); return; }
        var minLeft = Math.min(
          data.idle_left     != null ? data.idle_left     : 99999,
          data.absolute_left != null ? data.absolute_left : 99999
        );
        if (minLeft <= WARN_THRESHOLD) {
          showToast();
        } else {
          hideToast();
        }
      })
      .catch(function () { /* errore di rete: non reindirizzare, riprova al prossimo poll */ });
  }

  // Avvia polling dopo 30 s dalla prima apertura, poi ogni POLL_INTERVAL
  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
      poll();
      setInterval(poll, POLL_INTERVAL);
    }, 30000);
  });
}());
