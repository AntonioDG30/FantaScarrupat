/**
 * Filters Manager - client-only
 */
window.FiltersManager = (function(){
  'use strict';

  let originalData = [];
  let filteredData = [];
  let applyCallback = null;
  let quotazioneSlider = null;
  let etaSlider = null;
  let isInitialized = false;

  const filters = {
    ruolo: [],
    squadra: '',
    quotazione: { min: 1, max: 50 },
    eta: { min: 18, max: 40 },
    nazionalita: ''
  };

  function initialize(data, callback){
    if (!Array.isArray(data) || !data.length){ return; }
    originalData = [...data];
    filteredData = [...data];
    applyCallback = callback;
    if (!isInitialized){
      setupUI();
      setupEventListeners();
      isInitialized = true;
    }
    populateDropdowns();
    resetFilters();
  }

  function setupUI(){
    setupRuoloChips();
    setupQuotazioneSlider();
    setupEtaSlider();
    const resetBtn = document.getElementById('btnResetFilters');
    if (resetBtn){ resetBtn.addEventListener('click', resetFilters); }
  }

  function setupRuoloChips(){
    const chips = document.querySelectorAll('#ruoloFilters .filter-chip');
    chips.forEach(chip => {
      chip.addEventListener('click', function(){
        const value = this.dataset.value;
        this.classList.toggle('active');
        if (this.classList.contains('active')){
          if (!filters.ruolo.includes(value)) filters.ruolo.push(value);
        } else {
          filters.ruolo = filters.ruolo.filter(r => r !== value);
        }
        applyFiltersDebounced();
      });
    });
  }

  function setupQuotazioneSlider(){
    const el = document.getElementById('quotazioneRange');
    if (!el) return;

    // compute range
    const quotazioni = originalData
      .map(item => parseFloat(item.quota_attuale_classic ?? item.quotazione ?? 0))
      .filter(v => !isNaN(v) && v > 0);
    const min = quotazioni.length ? Math.max(1, Math.floor(Math.min(...quotazioni))) : 1;
    const max = quotazioni.length ? Math.min(50, Math.ceil(Math.max(...quotazioni))) : 50;
    filters.quotazione = { min, max };

    if (el.noUiSlider){ el.noUiSlider.destroy(); }
    noUiSlider.create(el, {
      start: [min, max], connect: true, range: { min, max },
      tooltips: [true, true],
      format: { to: v => Math.round(v), from: v => Number(v) }
    });
    const minLabel = document.getElementById('quotazioneMin');
    const maxLabel = document.getElementById('quotazioneMax');
    if (minLabel) minLabel.textContent = min;
    if (maxLabel) maxLabel.textContent = max;
    el.noUiSlider.on('update', values => {
      filters.quotazione.min = parseInt(values[0]); filters.quotazione.max = parseInt(values[1]);
      applyFiltersDebounced();
    });
  }

  function setupEtaSlider(){
    const el = document.getElementById('etaRange');
    if (!el) return;
    const today = new Date();
    const ages = originalData.map(it => {
      if (!it.data_nascita) return null;
      const d = new Date(it.data_nascita);
      if (isNaN(d)) return null;
      const a = today.getFullYear() - d.getFullYear();
      return (a>0 && a<100) ? a : null;
    }).filter(Boolean);
    const min = ages.length ? Math.max(16, Math.min(...ages)) : 16;
    const max = ages.length ? Math.min(45, Math.max(...ages)) : 45;
    filters.eta = { min, max };

    if (el.noUiSlider){ el.noUiSlider.destroy(); }
    noUiSlider.create(el, {
      start: [min, max], connect: true, range: { min, max },
      tooltips: [true, true],
      format: { to: v => Math.round(v), from: v => Number(v) }
    });
    const minLabel = document.getElementById('etaMin');
    const maxLabel = document.getElementById('etaMax');
    if (minLabel) minLabel.textContent = min;
    if (maxLabel) maxLabel.textContent = max;
    el.noUiSlider.on('update', values => {
      filters.eta.min = parseInt(values[0]); filters.eta.max = parseInt(values[1]);
      applyFiltersDebounced();
    });
  }

  function populateDropdowns(){
    const squadre = [...new Set(originalData.map(i => i.squadra).filter(Boolean))].sort();
    const squadraSelect = document.getElementById('squadraFilter');
    if (squadraSelect){
      squadraSelect.innerHTML = '<option value="">Tutte le squadre</option>';
      squadre.forEach(s => squadraSelect.innerHTML += `<option value="${s}">${s}</option>`);
    }
    const naz = [...new Set(originalData.map(i => (i.nazionalita_effettiva || i.nazionalita || '')).filter(Boolean).map(n => n.split(';')[0].trim()))].sort();
    const nazSelect = document.getElementById('nazionalitaFilter');
    if (nazSelect){
      nazSelect.innerHTML = '<option value="">Tutte le nazionalità</option>';
      naz.forEach(n => nazSelect.innerHTML += `<option value="${n}">${n}</option>`);
    }
  }

  function setupEventListeners(){
    const squadra = document.getElementById('squadraFilter');
    if (squadra){ squadra.addEventListener('change', function(){ filters.squadra = this.value; applyFiltersDebounced(); }); }
    const naz = document.getElementById('nazionalitaFilter');
    if (naz){ naz.addEventListener('change', function(){ filters.nazionalita = this.value; applyFiltersDebounced(); }); }
  }

  const applyFiltersDebounced = debounce(applyFilters, 120);

  function applyFilters(){
    filteredData = originalData.filter(item => {
      // ruolo
      if (filters.ruolo.length && !filters.ruolo.includes(item.ruolo_classic)) return false;
      // squadra
      if (filters.squadra && item.squadra !== filters.squadra) return false;
      // quotazione
      const q = parseFloat(item.quota_attuale_classic ?? item.quotazione ?? 0);
      if (q && (q < filters.quotazione.min || q > filters.quotazione.max)) return false;
      // età
      if (item.data_nascita){
        const d = new Date(item.data_nascita);
        if (!isNaN(d)){
          const age = (new Date()).getFullYear() - d.getFullYear();
          if (age < filters.eta.min || age > filters.eta.max) return false;
        }
      }
      // nazionalità
      if (filters.nazionalita){
        const nz = (item.nazionalita_effettiva || item.nazionalita || '').toLowerCase();
        if (!nz.includes(filters.nazionalita.toLowerCase())) return false;
      }
      return true;
    });
    updateActiveFiltersDisplay();
    if (applyCallback) applyCallback(filteredData);
  }

  function updateActiveFiltersDisplay(){
    const display = document.getElementById('activeFiltersDisplay');
    if (!display) return;
    const active = [];
    if (filters.ruolo.length) active.push(`Ruolo: ${filters.ruolo.join(', ')}`);
    if (filters.squadra) active.push(`Squadra: ${filters.squadra}`);
    active.push(`Quotazione: ${filters.quotazione.min}–${filters.quotazione.max}`);
    active.push(`Età: ${filters.eta.min}–${filters.eta.max}`);
    if (filters.nazionalita) active.push(`Nazionalità: ${filters.nazionalita}`);
    display.innerHTML = active.length ? active.map(t => `<span class="badge bg-secondary">${t}</span>`).join(' ') : '<span class="text-muted">Nessun filtro attivo</span>';
  }

  function resetFilters(){
    filters.ruolo = [];
    filters.squadra = '';
    filters.nazionalita = '';
    setupQuotazioneSlider(); setupEtaSlider(); // reset ai range calcolati
    document.querySelectorAll('#ruoloFilters .filter-chip').forEach(ch => ch.classList.remove('active'));
    const squadra = document.getElementById('squadraFilter'); if (squadra) squadra.value = '';
    const naz = document.getElementById('nazionalitaFilter'); if (naz) naz.value = '';
    applyFilters();
  }

  // utils
  function debounce(fn, wait){
    let t; return function(...args){ clearTimeout(t); t = setTimeout(() => fn.apply(this,args), wait); };
  }

  return { initialize };
})();