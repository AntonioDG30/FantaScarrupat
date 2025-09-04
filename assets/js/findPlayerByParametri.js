
// Funzione url() per compatibilità
function url(path) {
    const basePath = '<?= getProjectBasePath() ?>';
    return basePath + path.replace(/^\/+/, '');
}

let currentResults = [];
let originalResults = [];
let currentCriteria = '';
let currentMode = 'single'; // 'single' | 'dual'

// Criteri -> colonne extra (mantenuto come prima)
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
  '8':'Squadre neopromosse (dinamico)','9':'Squadre 10°–17° scorsa stagione',
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

// UI helpers (mantenuti come prima)
function showLoading(text='Caricamento in corso…'){ 
  // Se c'è ProgressManager attivo, non mostrare il loading classico
  if (window.ProgressManager && ProgressManager.isActive && ProgressManager.isActive()) {
    return;
  }
  const o=document.getElementById('loadingOverlay'); 
  o.style.display='block'; 
  o.querySelector('.loading-text').textContent=text; 
}

function hideLoading(){ 
  document.getElementById('loadingOverlay').style.display='none'; 
}

function showMessage(msg,type='info'){
  const el=document.createElement('div'); 
  el.className='toast-msg '+type; 
  el.textContent=msg;
  document.body.appendChild(el); 
  requestAnimationFrame(()=>el.classList.add('show'));
  setTimeout(()=>{ 
    el.classList.remove('show'); 
    setTimeout(()=>el.remove(),300); 
  },2600);
}

// ===== VERSIONE SEMPLIFICATA: UI sempre sincronizzata con backend =====
let currentPollingInterval = null;

async function loadData(forceRefresh = false) {
  try {
    const statusCheck = await fetch('status/progress.php', {
      cache: 'no-cache',
      headers: { 'Cache-Control': 'no-cache' }
    });
    
    if (statusCheck.ok) {
      const statusData = await statusCheck.json();
      if (statusData.operation_active) {
        // console.log('Joining existing operation');
        
        if (window.ProgressManager) {
          ProgressManager.show(statusData.cache_state);
        }
        
        disableInterface();
        startContinuousPolling();
        
        let message = 'Operazione in corso';
        if (statusData.job_info && !statusData.job_info.started_by_me) {
          message += ' (avviata da altro utente)';
        }
        showMessage(message, 'info');
        return;
      }
    }

    // Clear progress state prima di iniziare
    try {
      const clearUrl = new URL('api/data.php', window.location.href);
      clearUrl.searchParams.set('action', 'clear_progress');
      await fetch(clearUrl, {
        headers: { 'X-CSRF-Token': csrfToken }
      });
    } catch (e) {
      console.warn('Could not clear previous progress:', e);
    }
    
    // Mostra progress e disabilita UI
    if (window.ProgressManager) {
      ProgressManager.show('checking');
    } else {
      showLoading('Avvio operazione...');
    }
    
    disableInterface();
    startContinuousPolling();
    
    await new Promise(resolve => setTimeout(resolve, 200));
    
    // Avvia load operation
    const url = new URL('api/data.php', window.location.href);
    url.searchParams.set('action', 'load');
    
    const headers = { 'X-CSRF-Token': csrfToken };
    if (forceRefresh) {
      headers['X-Force-Refresh'] = '1';
    }
    
    const response = await fetch(url, { headers });
    const data = await response.json();
    
    if (data.success) {
      // console.log('Load initiated, polling will handle completion');
    } else {
      stopContinuousPolling();
      hideLoadingIndicators();
      enableInterface();
      showMessage(data.error || 'Errore nel caricamento', 'danger');
    }
    
  } catch (e) {
    stopContinuousPolling();
    hideLoadingIndicators();
    enableInterface();
    showMessage('Errore di rete: ' + e.message, 'danger');
    console.error('LoadData error:', e);
  }
}

function stopContinuousPolling() {
  if (currentPollingInterval) {
    clearInterval(currentPollingInterval);
    currentPollingInterval = null;
  }
}

function updateUIFromBackendState(data) {
  window.ProgressManager.updateProgress(
    data.step || 0, 
    data.total || 1, 
    data.label || 'In elaborazione...', 
    data.percent || 0
  );
  
  // Mostra info multi-utente
  if (data.job_info) {
    let statusText = data.label;
    if (!data.job_info.started_by_me) {
      statusText += ` (avviato da altro utente)`;
    }
    if (data.job_info.participants_count > 1) {
      statusText += ` [${data.job_info.participants_count} utenti collegati]`;
    }
    
    window.ProgressManager.updateProgress(
      data.step || 0, 
      data.total || 1, 
      statusText, 
      data.percent || 0
    );
  }

  // Aggiorna progress manager SOLO se attivo
  if (window.ProgressManager && window.ProgressManager.isActive()) {
    window.ProgressManager.updateProgress(
      data.step || 0, 
      data.total || 1, 
      data.label || 'In elaborazione...', 
      data.percent || 0
    );
    
    // Aggiorna cache status se disponibile
    if (data.cache_state) {
      const statusMap = {
        'fresh': 'valid',
        'stale': 'expired', 
        'rebuilding': 'rebuilding',
        'empty': 'empty'
      };
      window.ProgressManager.updateCacheStatus(
        statusMap[data.cache_state] || data.cache_state,
        data.cache_info?.age_formatted
      );
    }
    
    // Aggiorna log se disponibile
    if (data.log && Array.isArray(data.log)) {
      data.log.forEach(entry => {
        if (typeof entry === 'object' && entry.label) {
          window.ProgressManager.addLogEntry(entry.label, entry.ts);
        }
      });
    }
  } else {
    // Fallback per loading semplice
    const loadingText = document.querySelector('.loading-text');
    if (loadingText) {
      loadingText.textContent = data.label || 'Caricamento in corso...';
    }
  }
}

function handleOperationSuccess(data) {
  // console.log('Operation completed successfully');

  let successMessage = 'Dati caricati con successo';
  if (data.job_info && !data.job_info.started_by_me) {
    successMessage += ' (completato da altro utente)';
  }
  if (data.cache_final_info) {
    successMessage += ` (age: ${data.cache_final_info.age})`;
  }
  
  // Gestione corretta del completamento
  if (window.ProgressManager && window.ProgressManager.isActive()) {
    window.ProgressManager.forceComplete();
  } else {
    hideLoadingIndicators();
  }
  
  // Marca dati come caricati
  markDataAsLoaded();
  enableInterface();
  
  // Messaggio di successo
  const cacheMsg = data.cache_final_info ? 
    ` (age: ${data.cache_final_info.age})` : 
    '';
  showMessage('Dati caricati con successo' + cacheMsg, 'success');
}

function startContinuousPolling() {
  stopContinuousPolling();
  
  let pollAttempts = 0;
  const maxAttempts = 600;
  
  currentPollingInterval = setInterval(async () => {
    pollAttempts++;
    
    if (pollAttempts > maxAttempts) {
      stopContinuousPolling();
      handleOperationError('Timeout: operazione troppo lunga');
      return;
    }
    
    try {
      const progressUrl = new URL('status/progress.php', window.location.href);
      const response = await fetch(progressUrl, {
        cache: 'no-cache',
        headers: { 
          'Cache-Control': 'no-cache',
          'Pragma': 'no-cache'
        }
      });
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }
      
      const data = await response.json();
      
      // Aggiorna UI solo se i dati sono coerenti
      if (data.step !== undefined && data.total !== undefined) {
        updateUIFromBackendState(data);
      }
      
      // Check completamento più rigoroso
      if (data.completed === true && data.operation_active === false) {
        stopContinuousPolling();
        
        if (data.error) {
          handleOperationError(data.error);
        } else {
          handleOperationSuccess(data);
        }
      }
      
    } catch (error) {
      console.warn('Polling error:', error);
    }
  }, 500);
}

function handleOperationError(errorMessage) {
  if (window.ProgressManager) {
    ProgressManager.updateProgress(0, 1, `Errore: ${errorMessage}`, 0);
    
    // Mostra pulsante per retry
    const btnRefresh = document.querySelector('#btnForceRefresh');
    if (btnRefresh) {
      btnRefresh.style.display = 'flex';
    }
  }
  
  hideLoadingIndicators();
  enableInterface();
  showMessage('Errore: ' + errorMessage, 'danger');
}

function disableInterface() {
  const controls = [
    'criteriaSelect', 'criteriaSelectA', 'criteriaSelectB', 
    'btnRun', 'btnRunDual', 'btnReload'
  ];
  
  controls.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.disabled = true;
      el.style.opacity = '0.5';
    }
  });
  
  const cacheButtons = document.querySelectorAll('.btn-cache-action');
  cacheButtons.forEach(btn => {
    btn.disabled = true;
    btn.style.opacity = '0.5';
  });
}

function enableInterface() {
  const controls = [
    'criteriaSelect', 'criteriaSelectA', 'criteriaSelectB', 
    'btnRun', 'btnRunDual', 'btnReload'
  ];
  
  controls.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.disabled = false;
      el.style.opacity = '1';
    }
  });
  
  const cacheButtons = document.querySelectorAll('.btn-cache-action');
  cacheButtons.forEach(btn => {
    btn.disabled = false;
    btn.style.opacity = '1';
  });
}

function markDataAsLoaded() {
  const badge = document.getElementById('statusBadge');
  if (badge) {
    badge.classList.remove('not-loaded'); 
    badge.classList.add('loaded');
    badge.innerHTML = '<span class="material-icons">check_circle</span><span>Dati caricati</span>';
  }
}

function hideLoadingIndicators() {
  if (window.ProgressManager) {
    ProgressManager.hide();
  } else {
    hideLoading();
  }
}

// Refresh forzato semplificato
async function forceRefreshData() {
  const confirm_msg = 'Aggiornare i dati ignorando la cache?\n\n' +
                     'Questa operazione può richiedere alcuni minuti.';
  
  if (confirm(confirm_msg)) {
    await loadData(true);
  }
}

// Funzione info cache
async function getCacheInfo() {
  try {
    const response = await fetch('api/data.php?action=cache_info', {
      headers: { 'X-CSRF-Token': csrfToken }
    });
    const data = await response.json();
    
    if (data.success) {
      const info = data.cache_info;
      let message = `📋 Stato Cache\n\n`;
      
      if (info.status === 'exists') {
        message += `✅ Cache presente\n`;
        message += `📅 Creata: ${info.built_at_formatted}\n`;
        message += `⏰ Età: ${info.age_formatted}\n`;
        message += `📄 TTL: 24 ore\n`;
        message += `✔️ Valida: ${data.is_valid ? 'Sì' : 'No (scaduta)'}`;
        
        if (!data.is_valid) {
          message += '\n\n💡 La cache è scaduta. Usa "Aggiorna" per rigenerare.';
        }
      } else {
        message += `❌ Nessuna cache presente\n`;
        message += `💡 I dati verranno caricati completamente al prossimo refresh.`;
      }
      
      alert(message);
    } else {
      showMessage('Errore nel recupero info cache', 'warning');
    }
  } catch (e) {
    showMessage('Errore di rete', 'warning');
  }
}

// Resto del codice esistente per tabs, criteri, ecc.
document.addEventListener('DOMContentLoaded', () => {
  // Tabs handler
  const tabs = document.querySelectorAll('.control-tab');
  const panels = [
    document.getElementById('panel-single-wrapper'),
    document.getElementById('panel-dual-wrapper'),
    document.getElementById('panel-filters-wrapper')
  ];
  tabs.forEach((tab, i) => {
    tab.addEventListener('click', (e)=>{
      e.preventDefault();
      tabs.forEach(t=>t.classList.remove('active')); 
      tab.classList.add('active');
      panels.forEach(p=>p.classList.remove('active')); 
      panels[i]?.classList.add('active');
    });
  });

  // Theme
  const tm = new ThemeManager(); 
  tm.init();

  // Popola select
  populateSelects();

  // Event listeners
  const on = (id, ev, fn) => { 
    const el=document.getElementById(id); 
    if(el) el.addEventListener(ev, fn); 
  };
  
  on('btnReload','click', () => loadData(false));
  on('btnRun','click',runCriteria);
  on('btnRunDual','click', () => window.CriteriaAndManager?.runDualCriteria?.());
  
  // Controlli cache
  on('btnForceRefresh', 'click', forceRefreshData);
  on('btnCacheInfo', 'click', getCacheInfo);

  // Autoload
  loadData();
});

function populateSelects(){
  ['criteriaSelect','criteriaSelectA','criteriaSelectB'].forEach((id, idx)=>{
    const el=document.getElementById(id);
    if(!el) return;
    const placeholder = idx===0 ? 'Seleziona un criterio' : (idx===1 ? 'Seleziona criterio A' : 'Seleziona criterio B');
    el.innerHTML = `<option value="">${placeholder}</option>`;
    Object.entries(criteriaList).forEach(([k,v])=> el.innerHTML += `<option value="${k}">${k}. ${v}</option>`);
  });
}

document.getElementById('criteriaSelect').addEventListener('change', async (e) => {
  const v = e.target.value;
  const wrap = document.getElementById('prevTeamWrapper');
  const sel  = document.getElementById('prevTeamSelect');
  if (v === '18'){
    wrap.style.display = 'flex';
    sel.disabled = true;
    try{
      const res = await fetch('api/criteria.php?action=list_prev_teams', { headers: {'X-CSRF-Token': csrfToken} });
      const data = await res.json();
      sel.innerHTML = `<option value="">Seleziona fantasquadra</option>`;
      if (data.success && Array.isArray(data.teams)){
        data.teams.forEach(n => sel.innerHTML += `<option value="${n}">${n}</option>`);
      }
      sel.disabled = false;
    } catch(e){
      showMessage('Errore caricamento fantasquadre','danger');
    }
  } else {
    wrap.style.display = 'none';
  }
});

// Funzione con logging attività
async function runCriteria(){
  const criteriaId = document.getElementById('criteriaSelect').value;
  if(!criteriaId){ showMessage('Seleziona un criterio','warning'); return; }

  // Validazione aggiuntiva per criterio 18
  let extra = '';
  if (criteriaId === '18'){
    const fsq = (document.getElementById('prevTeamSelect')?.value || '').trim();
    if (!fsq){ showMessage('Seleziona una fantasquadra','warning'); return; }
    extra = `&fantasquadra=${encodeURIComponent(fsq)}`;
  }

  currentMode='single'; currentCriteria=criteriaId; showLoading('Esecuzione criterio…');
  try{
    const res = await fetch(`api/criteria.php?action=run&criteria=${criteriaId}${extra}`, { headers:{'X-CSRF-Token':csrfToken} });
    const data = await res.json();
    if(data.success){
      currentResults = Array.isArray(data.results) ? data.results : [];
      originalResults = [...currentResults];
      displayResults(); showFilters();
      showMessage(`Trovati ${currentResults.length} giocatori`,'success');
      
      // Log attività ricerca
      logSearchActivityToServer(criteriaId);
    } else { showMessage(data.error || "Errore nell'esecuzione",'danger'); }
  } catch(e){ showMessage('Errore di rete','danger'); }
  finally{ hideLoading(); }
}

// Funzione per log attività al server
async function logSearchActivityToServer(criteriaId) {
  try {
    await fetch('api/log_activity.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
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

function showFilters(){
  const card=document.getElementById('filtersCard');
  card.style.display='grid';
  if(window.FiltersManager && originalResults.length){
    window.FiltersManager.initialize(originalResults, applyFiltersCallback);
  }
}

function applyFiltersCallback(filtered){
  currentResults = filtered; displayResults();
}

// Costruzione colonne
function buildColumns(){
  const base = [
    { data:'nome_completo', title:'NOME COMPLETO' },
    { data:'ruolo_classic', title:'RUOLO CLASSIC' },
    { data:'squadra', title:'SQUADRA' },
    { data:'quota_attuale_classic', title:'QUOTA ATTUALE CLASSIC' }
  ];
  let extra = [];
  if(currentMode==='single'){ extra = criteriaColumns[currentCriteria] || []; }
  return base.concat(extra.map(k => ({ data:k, title:k.replaceAll('_',' ').toUpperCase() })));
}

// DataTableManager: unico punto di gestione per evitare reinit
const DataTableManager = (() => {
  let dt = null;
  let sig = '';
  let inFlight = false;
  let queued = null;

  const buildSig = (cols) => JSON.stringify((cols||[]).map(c => c.data));

  function ensureTableElement(){
    const container = document.querySelector('#resultsCard .table-container') || document.body;
    let table = document.getElementById('resultsTable');
    if(!table){
      table = document.createElement('table');
      table.id='resultsTable'; table.className='table table-sm';
      container.appendChild(table);
    }
    return table;
  }

  function hardDestroy(){
    try{
      if ($.fn && $.fn.dataTable && $.fn.dataTable.isDataTable('#resultsTable')){
        $('#resultsTable').DataTable().clear().destroy(true);
      }
    }catch(e){/* noop */}
    dt=null; sig='';
  }

  function rebuild(columns, data){
    if(inFlight){ queued={columns,data}; return; }
    inFlight = true;
    try{
      hardDestroy();
      const table = ensureTableElement();
      table.innerHTML = '';
      const thead=document.createElement('thead'); const tr=document.createElement('tr');
      columns.forEach(c=>{ const th=document.createElement('th'); th.textContent=c.title; tr.appendChild(th); });
      thead.appendChild(tr); table.appendChild(thead); table.appendChild(document.createElement('tbody'));
      const orderIdx=Math.max(0, columns.findIndex(c=>c.data==='quota_attuale_classic'));
      dt = $('#resultsTable').DataTable({
        destroy:true, data:[], columns:columns,
        responsive:true, deferRender:true, pageLength:25,
        lengthMenu:[25,50,100,250,500], scrollY:'60vh', scrollCollapse:true, scroller:true,
        order:[[orderIdx,'desc']],
        dom:"<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons:[ {extend:'colvis', text:'Mostra/Nascondi Colonne', className:'btn btn-outline-secondary btn-sm'} ],
        initComplete:function(){
          const btn=document.getElementById('btnToggleColumns');
          if(btn){ btn.onclick = () => $('.buttons-colvis').trigger('click'); }
        }
      });
      sig = buildSig(columns);
      updateData(data||[]);
    } finally {
      inFlight=false;
      if(queued){ const q=queued; queued=null; ensure(q.columns, q.data); }
    }
  }

  function updateData(data){
    if(!dt){ return; }
    try{ dt.clear(); if(data && data.length) dt.rows.add(data); dt.draw(false); }
    catch(e){ console.warn('[DT] updateData error', e); }
  }

  function ensure(columns, data){
    const newSig = buildSig(columns);
    if(dt && $.fn && $.fn.dataTable && $.fn.dataTable.isDataTable('#resultsTable') && newSig===sig){
      updateData(data);
    } else {
      rebuild(columns, data);
    }
  }

  function destroyIfAny(){ hardDestroy(); }

  return { ensure, destroyIfAny, get:()=>dt };
})();

function displayResults(){
  const resultsCard=document.getElementById('resultsCard');
  const emptyState=document.getElementById('emptyState');
  const resultsCount=document.getElementById('resultsCount');
  if(!resultsCard || !emptyState || !resultsCount){ console.warn('[UI] Elementi risultati mancanti'); return; }

  resultsCard.style.display='block';
  resultsCount.textContent = `${currentResults.length} giocatori trovati`;
  const badge=document.getElementById('badgeCount'); if(badge){ badge.style.display = currentResults.length ? 'inline-block' : 'none'; }

  if(!currentResults.length){
    emptyState.style.display='block'; const tbl=document.getElementById('resultsTable'); if(tbl) tbl.style.display='none';
    DataTableManager.destroyIfAny(); return;
  }

  emptyState.style.display='none'; const tbl=document.getElementById('resultsTable'); if(tbl) tbl.style.display='table';

  const cols = buildColumns();
  const data = currentResults.map(row => { const o={}; cols.forEach(c => o[c.data] = row?.[c.data] ?? ''); return o; });
  DataTableManager.ensure(cols, data);
}

async function exportData(format){
  if(!currentResults || !currentResults.length){
    showMessage('Nessun risultato da esportare','warning'); 
    return;
  }
  try{
    const criteriaId = (currentMode==='single' ? currentCriteria : '');
    const res = await fetch('api/criteria.php?action=export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify({
        criteria: criteriaId,
        format: format,
        results: currentResults   
      })
    });

    if(!res.ok){
      const t = await res.text();
      throw new Error(t || res.statusText);
    }

    const blob = await res.blob();
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = `criterio_${criteriaId || 'AND'}_${Date.now()}.${format}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
    showMessage('Export completato','success');
    
    // Log attività export
    logSearchActivityToServer(`export_${format}`);
  }catch(e){
    showMessage('Errore export: '+(e.message||e),'danger');
  }
}