/**
 * FindPlayerByParametri - Sistema semplificato con nuovo cache manager
 */

// Stato applicazione
let currentResults = [];
let originalResults = [];
let currentCriteria = '';
let currentMode = 'single';

// Criteri e mapping colonne
const criteriaColumns = {
  '1':['data_nascita'],'2':['data_nascita'],'3':[],'4':['presenze_totali'],'5':['nazionalita_effettiva'],'6':['nazionalita_effettiva'],
  '7':['nazionalita_effettiva'],'8':[],'9':[],'10':['GA_scorsa_squadra'],'11':['gol_scorsa_stagione'],'12':['assist_scorsa_stagione'],
  '13':['gol_scorsa_stagione'],'14':['presenze_scorsa_stagione'],'15':['media_voto'],'16':[],'17':[],'18':[], 
  '19':['ultima_stagione','anni_assenza'],'20':['Numero_SV'],'21':['ammonizioni_scorsa_stagione'],'22':['squadra_scorsa','squadra_attuale'],
  '23':['autogol_scorsa_stagione'],'24':['presenze_scorsa_stagione'],'25':['rigori_sbagliati'],'26':['presenze_scorsa_stagione'],
  '27':[],'28':['gol_scorsa_stagione','assist_scorsa_stagione','gol_plus_assist'],'29':['cartellini_per_presenza','rapporto'],
  '30':[],'31':['ruolo_classic_scorsa','ruolo_classic_attuale'],'32':[],'34':['gol_scorsa_stagione'] 
};

const criteriaList = {
  '1':'Under 23 (al 1° luglio)','2':'Over 32 (al 1° luglio)',
  '3':'Prima stagione in Serie A','4':'Più di 200 presenze in Serie A',
  '5':'Giocatori sudamericani','6':'Giocatori africani','7':'Europei non italiani',
  '8':'Squadre neopromosse (dinamico)','9':'Squadre 10°—17° scorsa stagione',
  '10':'Portieri squadre con GA ≥ 50','11':'Difensori con almeno 1 gol',
  '12':'Centrocampisti con almeno 3 assist','13':'Attaccanti con massimo 5 gol',
  '14':'Meno di 10 presenze','15':'Media voto < 6','16':'Quotazione ≤ 6','17':'Quotazione ≤ 3',
  '18':'Rosa dell\'anno scorso', '19':'Ritorno in Serie A',"20":"Almeno 5 partite '6*' (S.V.)",
  '21':'Più di 7 ammonizioni', '22':'Cambiato squadra vs stagione precedente',
  '23':'Almeno 1 autogol (scorsa stagione)',
  '24':'Almeno 34 presenze','25':'Almeno un rigore sbagliato',
  '26':'Zero ammonizioni/espulsioni e ≥6 presenze','27':'Presenti in tutte le ultime 3 stagioni',
  '28':'Gol+Assist ≥ 5','29':'Rapporto (gialli+rossi)/presenze sopra soglia',
  '30':'Rigoristi designati','31':'Cambio ruolo ufficiale vs scorsa stagione','32':'Esordienti assoluti in Serie A',
  '34':'>10 gol nella scorsa stagione' 
};

// ===== UTILITIES =====
function showMessage(msg, type = 'info') {
  if (window.cacheManager) {
    window.cacheManager.showMessage(msg, type);
  } else {
    console.log(`[${type}] ${msg}`);
  }
}

function showLoading(text = 'Caricamento in corso…') {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) {
    overlay.style.display = 'block';
    const loadingText = overlay.querySelector('.loading-text');
    if (loadingText) {
      loadingText.textContent = text;
    }
  }
}

function hideLoading() {
  const overlay = document.getElementById('loadingOverlay');
  if (overlay) {
    overlay.style.display = 'none';
  }
}

function getCsrfToken() {
  return window.csrfToken || 
         document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
         '';
}

// ===== INIZIALIZZAZIONE =====
async function initializeApp() {
  try {
    // Usa il nuovo cache manager
    const success = await window.cacheManager.initialize();
    
    if (!success) {
      // Cache manager ha già gestito l'errore con overlay/banner
      // Ma dobbiamo abilitare le select dei criteri che hanno dati hardcoded
      enableCriteriaSelects();
      return;
    }
    
    // Cache OK - abilita tutto il sistema
    enableAllControls();
    showMessage('FindPlayerByParametri pronto', 'success');
    
  } catch (error) {
    console.error('FindPlayerByParametri initialization error:', error);
    showMessage('Errore di inizializzazione: ' + error.message, 'danger');
    // Anche in caso di errore, abilita almeno le select criteri
    enableCriteriaSelects();
  }
}

// ===== FUNZIONI DI ABILITAZIONE =====
function enableCriteriaSelects() {
  // Abilita specificamente le select dei criteri (hanno dati hardcoded)
  const criteriaSelects = ['criteriaSelect', 'criteriaSelectA', 'criteriaSelectB'];
  
  criteriaSelects.forEach(id => {
    const select = document.getElementById(id);
    if (select) {
      select.disabled = false;
      select.style.opacity = '1';
    }
  });
  
  console.log('Criteria selects enabled');
}

function enableAllControls() {
  // Abilita tutti i controlli dell'app
  const allControls = document.querySelectorAll(`
    .controls-bar select,
    .controls-bar button, 
    .controls-bar input,
    .criteria-selector select,
    .criteria-selector button,
    .criteria-selector input,
    .squad-section select,
    .squad-section button,
    .squad-section input,
    .results-card button,
    .results-card select,
    .results-card input,
    .filters-grid select,
    .filters-grid button,
    .filters-grid input
  `);
  
  allControls.forEach(el => {
    el.disabled = false;
    el.style.opacity = '1';
  });
  
  console.log('All app controls enabled');
}

// ===== GESTIONE TABS =====
function initializeTabs() {
  const tabs = document.querySelectorAll('.control-tab');
  const panels = [
    document.getElementById('panel-single-wrapper'),
    document.getElementById('panel-dual-wrapper'),
    document.getElementById('panel-filters-wrapper')
  ];
  
  tabs.forEach((tab, i) => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      tabs.forEach(t => t.classList.remove('active')); 
      tab.classList.add('active');
      panels.forEach(p => p.classList.remove('active')); 
      panels[i]?.classList.add('active');
    });
  });
}

// ===== POPOLAMENTO SELECT =====
function populateSelects() {
  ['criteriaSelect','criteriaSelectA','criteriaSelectB'].forEach((id, idx) => {
    const el = document.getElementById(id);
    if (!el) return;
    
    const placeholder = idx === 0 ? 'Seleziona un criterio' : 
                       (idx === 1 ? 'Seleziona criterio A' : 'Seleziona criterio B');
    
    el.innerHTML = `<option value="">${placeholder}</option>`;
    Object.entries(criteriaList).forEach(([k,v]) => {
      el.innerHTML += `<option value="${k}">${k}. ${v}</option>`;
    });
  });
}

// ===== GESTIONE CRITERIO 18 =====
function initializeCriteriaHandlers() {
  const criteriaSelect = document.getElementById('criteriaSelect');
  if (criteriaSelect) {
    criteriaSelect.addEventListener('change', async (e) => {
      const v = e.target.value;
      const wrap = document.getElementById('prevTeamWrapper');
      const sel = document.getElementById('prevTeamSelect');
      
      if (v === '18') {
        wrap.style.display = 'flex';
        sel.disabled = true;
        
        try {
          const res = await fetch('api/criteria.php?action=list_prev_teams', { 
            headers: {'X-CSRF-Token': getCsrfToken()} 
          });
          const data = await res.json();
          
          sel.innerHTML = `<option value="">Seleziona fantasquadra</option>`;
          if (data.success && Array.isArray(data.teams)) {
            data.teams.forEach(n => {
              sel.innerHTML += `<option value="${n}">${n}</option>`;
            });
          }
          sel.disabled = false;
        } catch(e) {
          showMessage('Errore caricamento fantasquadre','danger');
        }
      } else {
        wrap.style.display = 'none';
      }
    });
  }
}

// ===== ESECUZIONE CRITERI =====
async function runCriteria() {
  const criteriaId = document.getElementById('criteriaSelect').value;
  if (!criteriaId) { 
    showMessage('Seleziona un criterio','warning'); 
    return; 
  }

  // Validazione aggiuntiva per criterio 18
  let extra = '';
  if (criteriaId === '18') {
    const fsq = (document.getElementById('prevTeamSelect')?.value || '').trim();
    if (!fsq) { 
      showMessage('Seleziona una fantasquadra','warning'); 
      return; 
    }
    extra = `&fantasquadra=${encodeURIComponent(fsq)}`;
  }

  currentMode = 'single'; 
  currentCriteria = criteriaId; 
  
  showMessage('Esecuzione criterio in corso...', 'info');
  
  try {
    const res = await fetch(`api/criteria.php?action=run&criteria=${criteriaId}${extra}`, { 
      headers: {'X-CSRF-Token': getCsrfToken()} 
    });
    
    const data = await res.json();
    
    if (data.success) {
      currentResults = Array.isArray(data.results) ? data.results : [];
      originalResults = [...currentResults];
      displayResults(); 
      showFilters();
      showMessage(`Trovati ${currentResults.length} giocatori`,'success');
      
      // Log attività ricerca
      logSearchActivityToServer(criteriaId);
    } else { 
      showMessage(data.error || "Errore nell'esecuzione",'danger'); 
    }
  } catch(e) { 
    showMessage('Errore di rete','danger'); 
  }
}

// ===== LOG ATTIVITÀ =====
async function logSearchActivityToServer(criteriaId) {
  try {
    await fetch('api/log_activity.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken()
      },
      body: JSON.stringify({
        activity_type: 'search',
        description: `Ricerca criterio ${criteriaId}`
      })
    });
  } catch (e) {
    console.warn('Could not log activity:', e);
  }
}

// ===== FILTRI =====
function showFilters() {
  const card = document.getElementById('filtersCard');
  if (card) {
    card.style.display = 'grid';
    if (window.FiltersManager && originalResults.length) {
      window.FiltersManager.initialize(originalResults, applyFiltersCallback);
    }
  }
}

function applyFiltersCallback(filtered) {
  currentResults = filtered; 
  displayResults();
}

// ===== VISUALIZZAZIONE RISULTATI =====
function buildColumns() {
  const base = [
    { data:'nome_completo', title:'NOME COMPLETO' },
    { data:'ruolo_classic', title:'RUOLO CLASSIC' },
    { data:'squadra', title:'SQUADRA' },
    { data:'quota_attuale_classic', title:'QUOTA ATTUALE CLASSIC' }
  ];
  
  let extra = [];
  if (currentMode === 'single') { 
    extra = criteriaColumns[currentCriteria] || []; 
  }
  
  return base.concat(extra.map(k => ({ 
    data: k, 
    title: k.replaceAll('_',' ').toUpperCase() 
  })));
}

// DataTableManager semplificato
const DataTableManager = (() => {
  let dt = null;
  let sig = '';

  const buildSig = (cols) => JSON.stringify((cols||[]).map(c => c.data));

  function ensureTableElement() {
    const container = document.querySelector('#resultsCard .table-container') || document.body;
    let table = document.getElementById('resultsTable');
    if (!table) {
      table = document.createElement('table');
      table.id = 'resultsTable'; 
      table.className = 'table table-sm';
      container.appendChild(table);
    }
    return table;
  }

  function hardDestroy() {
    try {
      if ($.fn && $.fn.dataTable && $.fn.dataTable.isDataTable('#resultsTable')) {
        $('#resultsTable').DataTable().clear().destroy(true);
      }
    } catch(e) { /* noop */ }
    dt = null; 
    sig = '';
  }

  function rebuild(columns, data) {
    try {
      hardDestroy();
      const table = ensureTableElement();
      table.innerHTML = '';
      
      const thead = document.createElement('thead'); 
      const tr = document.createElement('tr');
      columns.forEach(c => { 
        const th = document.createElement('th'); 
        th.textContent = c.title; 
        tr.appendChild(th); 
      });
      thead.appendChild(tr); 
      table.appendChild(thead); 
      table.appendChild(document.createElement('tbody'));
      
      const orderIdx = Math.max(0, columns.findIndex(c => c.data === 'quota_attuale_classic'));
      
      dt = $('#resultsTable').DataTable({
        destroy: true, 
        data: [], 
        columns: columns,
        responsive: true, 
        deferRender: true, 
        pageLength: 25,
        lengthMenu: [25,50,100,250,500], 
        scrollY: '60vh', 
        scrollCollapse: true, 
        scroller: true,
        order: [[orderIdx,'desc']],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
          {extend:'colvis', text:'Mostra/Nascondi Colonne', className:'btn btn-outline-secondary btn-sm'}
        ]
      });
      
      sig = buildSig(columns);
      updateData(data || []);
    } catch (e) {
      console.error('DataTable rebuild error:', e);
    }
  }

  function updateData(data) {
    if (!dt) return;
    try { 
      dt.clear(); 
      if (data && data.length) dt.rows.add(data); 
      dt.draw(false); 
    } catch(e) { 
      console.warn('[DT] updateData error', e); 
    }
  }

  function ensure(columns, data) {
    const newSig = buildSig(columns);
    if (dt && $.fn && $.fn.dataTable && $.fn.dataTable.isDataTable('#resultsTable') && newSig === sig) {
      updateData(data);
    } else {
      rebuild(columns, data);
    }
  }

  function destroyIfAny() { 
    hardDestroy(); 
  }

  return { ensure, destroyIfAny, get: () => dt };
})();

function displayResults() {
  const resultsCard = document.getElementById('resultsCard');
  const emptyState = document.getElementById('emptyState');
  const resultsCount = document.getElementById('resultsCount');
  
  if (!resultsCard || !emptyState || !resultsCount) { 
    console.warn('[UI] Elementi risultati mancanti'); 
    return; 
  }

  resultsCard.style.display = 'block';
  resultsCount.textContent = `${currentResults.length} giocatori trovati`;

  if (!currentResults.length) {
    emptyState.style.display = 'block'; 
    const tbl = document.getElementById('resultsTable'); 
    if (tbl) tbl.style.display = 'none';
    DataTableManager.destroyIfAny(); 
    return;
  }

  emptyState.style.display = 'none'; 
  const tbl = document.getElementById('resultsTable'); 
  if (tbl) tbl.style.display = 'table';

  const cols = buildColumns();
  const data = currentResults.map(row => { 
    const o = {}; 
    cols.forEach(c => o[c.data] = row?.[c.data] ?? ''); 
    return o; 
  });
  
  DataTableManager.ensure(cols, data);
}

// ===== EXPORT =====
async function exportData(format) {
  if (!currentResults || !currentResults.length) {
    showMessage('Nessun risultato da esportare','warning'); 
    return;
  }
  
  try {
    const criteriaId = (currentMode === 'single' ? currentCriteria : '');
    const res = await fetch('api/criteria.php?action=export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken()
      },
      body: JSON.stringify({
        criteria: criteriaId,
        format: format,
        results: currentResults   
      })
    });

    if (!res.ok) {
      const t = await res.text();
      throw new Error(t || res.statusText);
    }

    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `criterio_${criteriaId || 'AND'}_${Date.now()}.${format}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    showMessage('Export completato','success');
    
    // Log attività export
    logSearchActivityToServer(`export_${format}`);
  } catch(e) {
    showMessage('Errore export: '+(e.message||e),'danger');
  }
}

// ===== EVENT BINDINGS =====
function bindEvents() {
  const btnRun = document.getElementById('btnRun');
  if (btnRun) {
    btnRun.addEventListener('click', runCriteria);
  }
  
  const btnRunDual = document.getElementById('btnRunDual');
  if (btnRunDual) {
    btnRunDual.addEventListener('click', () => {
      if (window.CriteriaAndManager?.runDualCriteria) {
        window.CriteriaAndManager.runDualCriteria();
      }
    });
  }
  
  // Cache controls (solo per admin)
  const btnCacheInfo = document.getElementById('btnCacheInfo');
  if (btnCacheInfo) {
    btnCacheInfo.addEventListener('click', () => window.cacheManager.getCacheInfo());
  }
  
  const btnForceRefresh = document.getElementById('btnForceRefresh');
  if (btnForceRefresh) {
    btnForceRefresh.addEventListener('click', () => window.cacheManager.rebuildCache());
  }
}

// ===== INIZIALIZZAZIONE DOM =====
document.addEventListener('DOMContentLoaded', () => {
  // IMPORTANTE: Inizializza theme SUBITO e con collegamento bottone
  initializeTheme();

  // Inizializza componenti UI
  initializeTabs();
  populateSelects();
  initializeCriteriaHandlers();
  bindEvents();

  // DOPO aver inizializzato l'UI, avvia il cache manager
  initializeApp();
});

// Nuova funzione per gestire theme
function initializeTheme() {
  if (window.ThemeManager) {
    const themeManager = new ThemeManager(); 
    themeManager.init();
    console.log('ThemeManager initialized');
  } else {
    console.warn('ThemeManager not found');
    // Fallback manuale
    initializeThemeFallback();
  }
  
  // Collega sempre il bottone theme toggle
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
    console.log('Theme toggle button bound');
  }
}

function initializeThemeFallback() {
  // Gestione manuale se ThemeManager non è disponibile
  const savedTheme = localStorage.getItem('theme') || 'auto';
  applyTheme(savedTheme);
}

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  applyTheme(newTheme);
  localStorage.setItem('theme', newTheme);
  
  // Aggiorna icona
  const icon = document.getElementById('themeIcon');
  if (icon) {
    icon.textContent = newTheme === 'dark' ? 'light_mode' : 'dark_mode';
  }
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  console.log('Theme applied:', theme);
}

// Export global functions
window.exportData = exportData;