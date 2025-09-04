/**
 * Dual criteria intersection manager
 */
window.CriteriaAndManager = (function(){
  'use strict';

  let resultsA = []; let resultsB = []; let intersection = [];
  let currentCriteriaA = ''; let currentCriteriaB = '';
  let isExecuting = false;

  // === [NEW - 18 in Doppio] helpers ===
  const _selMap = {
    A: { criteria:'#criteriaSelectA', wrap:'#prevTeamWrapperA', select:'#prevTeamSelectA' },
    B: { criteria:'#criteriaSelectB', wrap:'#prevTeamWrapperB', select:'#prevTeamSelectB' }
  };

  async function _populatePrevTeams(selectEl){
    // carica solo se vuota o se devi ricaricare
    selectEl.disabled = true;
    try {
      const res = await fetch('api/criteria.php?action=list_prev_teams', { headers: { 'X-CSRF-Token': csrfToken } });
      const data = await res.json();
      selectEl.innerHTML = `<option value="">-- Seleziona fantasquadra --</option>`;
      if (data.success && Array.isArray(data.teams)){
        data.teams.forEach(n => selectEl.innerHTML += `<option value="${n}">${n}</option>`);
      }
    } catch(e){
      showMessage('Errore caricamento fantasquadre','danger');
    } finally {
      selectEl.disabled = false;
    }
  }

  async function _handleCriteriaChange(side){
    const cfg = _selMap[side];
    const crit = document.querySelector(cfg.criteria)?.value;
    const wrap = document.querySelector(cfg.wrap);
    const sel  = document.querySelector(cfg.select);
    if (!wrap || !sel) return;

    if (crit === '18'){
      wrap.style.display = 'flex';
      await _populatePrevTeams(sel);
    } else {
      wrap.style.display = 'none';
      sel.value = '';
    }
  }

  // Normalizza la chiave d'intersezione: preferisci SEMPRE 'codice_fantacalcio'
  function _keyOf(row){
    const k = row && (row.codice_fantacalcio ?? row.id ?? '');
    return String(k).trim();
  }



  async function runDualCriteria(){
    const criteriaA = document.getElementById('criteriaSelectA')?.value;
    const criteriaB = document.getElementById('criteriaSelectB')?.value;
    if (!criteriaA || !criteriaB){ showMessage('Seleziona entrambi i criteri','warning'); return; }
    if (criteriaA === criteriaB){ showMessage('Seleziona criteri diversi','warning'); return; }
    if (isExecuting) return;

    // [NEW] valida e prepara extra param per 18 (A/B)
    let extraA = '', extraB = '';
    if (criteriaA === '18'){
      const fsqA = (document.getElementById('prevTeamSelectA')?.value || '').trim();
      if (!fsqA){ showMessage('Seleziona una fantasquadra per il criterio A','warning'); return; }
      extraA = `&fantasquadra=${encodeURIComponent(fsqA)}`;
    }
    if (criteriaB === '18'){
      const fsqB = (document.getElementById('prevTeamSelectB')?.value || '').trim();
      if (!fsqB){ showMessage('Seleziona una fantasquadra per il criterio B','warning'); return; }
      extraB = `&fantasquadra=${encodeURIComponent(fsqB)}`;
    }

    isExecuting = true; showLoading('Esecuzione criteri in corso…');
    try {
      if (typeof csrfToken === 'undefined') throw new Error('CSRF Token non disponibile');
      const timeoutMs = 30000;

      // [NEW] costruisci le URL includendo gli extra (solo se 18)
      const urlA = `api/criteria.php?action=run&criteria=${encodeURIComponent(criteriaA)}${extraA}`;
      const urlB = `api/criteria.php?action=run&criteria=${encodeURIComponent(criteriaB)}${extraB}`;

      const [resA, resB] = await Promise.all([
        fetchWithTimeout(urlA, { headers: { 'X-CSRF-Token': csrfToken } }, timeoutMs),
        fetchWithTimeout(urlB, { headers: { 'X-CSRF-Token': csrfToken } }, timeoutMs)
      ]);
      const [dataA, dataB] = await Promise.all([resA.json(), resB.json()]);
      if (!dataA.success) throw new Error(dataA.error || 'Errore criterio A');
      if (!dataB.success) throw new Error(dataB.error || 'Errore criterio B');

      // risultati
      resultsA = Array.isArray(dataA.results) ? dataA.results : [];
      resultsB = Array.isArray(dataB.results) ? dataB.results : [];
      currentCriteriaA = criteriaA; currentCriteriaB = criteriaB;

      // indicizza B per codice_fantacalcio (fallback: id)
      const idxB = new Map(resultsB.map(r => [_keyOf(r), r]));

      // intersezione usando la stessa chiave
      intersection = resultsA
        .filter(a => idxB.has(_keyOf(a)))
        .map(a => {
          const b = idxB.get(_keyOf(a));
          // unione: mantieni i campi di 'a', completa con quelli di 'b' dove mancano
          const out = { ...b, ...a };
          return out;
        });

      // Pubblica risultati UI
      if (typeof currentResults !== 'undefined' && typeof originalResults !== 'undefined'){
        currentResults = [...intersection]; originalResults = [...intersection];
        if (typeof currentMode !== 'undefined'){ currentMode = 'dual'; }
      }
      if (typeof displayResults === 'function'){ displayResults(); }
      if (typeof showFilters === 'function'){ showFilters(); }
      const linksContainer = document.getElementById('dualResultLinks');
      if (linksContainer) linksContainer.style.display = 'block';
      document.getElementById('linkOnlyA').onclick = (e) => { e.preventDefault(); showOnlyA(); };
      document.getElementById('linkOnlyB').onclick = (e) => { e.preventDefault(); showOnlyB(); };
      showMessage(`Intersezione: ${intersection.length} giocatori (A: ${resultsA.length}, B: ${resultsB.length})`, 'success');
    } catch (e){
      console.error(e); showMessage(e.message || 'Errore nell\'esecuzione', 'danger');
    } finally {
      isExecuting = false; hideLoading();
    }
  }


  function showOnlyA(){
    if (!resultsA.length){ showMessage('Nessun risultato per A','warning'); return; }
    currentResults = [...resultsA]; originalResults = [...resultsA]; currentMode = 'single';
    if (typeof displayResults === 'function'){ displayResults(); }
    if (window.FiltersManager && originalResults){ window.FiltersManager.initialize(originalResults, typeof applyFiltersCallback === 'function' ? applyFiltersCallback : null); }
    showMessage(`Visualizzando solo criterio ${currentCriteriaA}: ${resultsA.length} giocatori`, 'info');
  }
  function showOnlyB(){
    if (!resultsB.length){ showMessage('Nessun risultato per B','warning'); return; }
    currentResults = [...resultsB]; originalResults = [...resultsB]; currentMode = 'single';
    if (typeof displayResults === 'function'){ displayResults(); }
    if (window.FiltersManager && originalResults){ window.FiltersManager.initialize(originalResults, typeof applyFiltersCallback === 'function' ? applyFiltersCallback : null); }
    showMessage(`Visualizzando solo criterio ${currentCriteriaB}: ${resultsB.length} giocatori`, 'info');
  }

  function fetchWithTimeout(url, options, timeout){
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    return fetch(url, { ...options, signal: controller.signal })
      .finally(() => clearTimeout(id));
  }

  // === [NEW - 18 in Doppio] wiring UI ===
  document.addEventListener('DOMContentLoaded', () => {
    ['A','B'].forEach(side => {
      const el = document.querySelector(_selMap[side].criteria);
      if (el) el.addEventListener('change', () => _handleCriteriaChange(side));
    });
  });


  return { runDualCriteria };
})();