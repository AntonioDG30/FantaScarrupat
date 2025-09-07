/**
 * Progress Manager - Gestisce il progress bar e il polling dello stato
 */
window.ProgressManager = (function(){
  'use strict';

  let isActive = false;
  let pollInterval = null;
  let overlay = null;
  let progressBar = null;
  let statusText = null;
  let logContainer = null;
  let cacheInfo = null;
  let etaDisplay = null;
  let detailsExpanded = false;

  const POLL_INTERVAL = 500; // ms
  const MAX_POLL_ATTEMPTS = 600; // 5 minuti massimo
  let pollAttempts = 0;

  function init(){
    createOverlay();
    bindEvents();
  }

  function createOverlay(){
    if(overlay) return;

    overlay = document.createElement('div');
    overlay.id = 'progressOverlay';
    overlay.className = 'progress-overlay';
    overlay.style.display = 'none';
    overlay.innerHTML = `
      <div class="progress-modal">
        <div class="progress-header">
          <h3>Caricamento dati in corso</h3>
          <div class="cache-status" id="cacheStatus"></div>
        </div>
        
        <div class="progress-content">
          <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar">
              <div class="progress-fill" id="progressFill"></div>
              <div class="progress-text" id="progressText">0%</div>
            </div>
          </div>
          
          <div class="progress-status">
            <div class="status-text" id="statusText">Inizializzazione...</div>
            <div class="eta-display" id="etaDisplay" style="display:none;"></div>
          </div>
          
          <div class="progress-stepper" id="progressStepper">
            <div class="step active">Scansione input</div>
            <div class="step">Lettura liste</div>
            <div class="step">Normalizzazione</div>
            <div class="step">Cache API</div>
            <div class="step">Costruzione indici</div>
            <div class="step">Pronto</div>
          </div>
          
          <div class="progress-log">
            <div class="log-header">
              <span>Log operazioni</span>
              <button type="button" class="btn-details" id="btnDetails">
                <span class="material-icons">expand_more</span>
                <span class="details-text">Dettagli</span>
              </button>
            </div>
            <div class="log-content" id="logContent" style="display:none;"></div>
          </div>
        </div>
        
        <div class="progress-actions">
          <button type="button" class="btn-cancel" id="btnCancelProgress">
            <span class="material-icons">close</span>
            Nascondi
          </button>
          <button type="button" class="btn-refresh" id="btnForceRefresh" style="display:none;">
            <span class="material-icons">refresh</span>
            Aggiorna adesso
          </button>
        </div>
      </div>
    `;

    // Stili CSS inline per semplicità
    const styles = `
      <style>
      .progress-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
      }
      .progress-modal{
        background: var(--card-bg);
        border-radius: 16px;
        padding: 20px;
        width: min(92vw, 640px);
        max-width: 640px;
        min-width: 0;             
        max-height: 90vh;         
        overflow: auto;
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--border-color);
      }

      .progress-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
      }
      .progress-header h3 {
        margin: 0;
        font-weight: 700;
        color: var(--text-primary);
      }
      .cache-status {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
      }
      .cache-status.valid {
        background: var(--success-bg);
        color: var(--success-color);
      }
      .cache-status.expired {
        background: var(--warning-bg);
        color: var(--warning-color);
      }
      .cache-status.empty {
        background: var(--border-color);
        color: var(--text-secondary);
      }
      .progress-bar-container {
        margin-bottom: 16px;
      }
      .progress-bar {
        height: 40px;
        background: var(--border-color);
        border-radius: 4px;
        position: relative;
        overflow: hidden;
      }
      .progress-fill {
        height: 100%;
        background: var(--gradient-primary);
        border-radius: 4px;
        transition: width 0.3s ease;
        width: 0%;
      }
      .progress-text {
        text-align: center;
        font-weight: 600;
        margin-top: 8px;
        color: var(--text-primary);
      }
      .progress-status {
        margin-bottom: 20px;
        text-align: center;
      }
      .status-text {
        font-size: 1rem;
        color: var(--text-secondary);
        margin-bottom: 4px;
      }
      .eta-display {
        font-size: 0.85rem;
        color: var(--text-muted);
      }
      .progress-stepper{
        display:flex;
        justify-content: space-between;
        padding:0 8px;
        margin-bottom:20px;
        flex-wrap: wrap;   /* evita compressione su mobile */
        gap:6px;
      }

      @media (max-width: 520px){
        .progress-bar{ height:24px; }
        .progress-header h3{ font-size:1rem; }
        .btn-details{ font-size:.8rem; }
        .progress-actions{ flex-wrap:wrap; justify-content:center; }
      }

      .step {
        flex: 1;
        text-align: center;
        padding: 8px 4px;
        font-size: 0.85rem;         /* un filo più grande */
        color: var(--text-secondary);/* più contrasto del muted */
        position: relative;
        font-weight: 600;            /* passi più leggibili */
      }
      .step.active {
        color: var(--primary-color);
        font-weight: 600;
      }
      .step.completed {
        color: var(--success-color);
      }
      .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -8px;
        width: 16px;
        height: 1px;
        background: var(--border-color);
        transform: translateY(-50%);
      }
      .progress-log {
        margin-bottom: 20px;
      }
      .log-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
      }
      .log-header span {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-secondary);
      }
      .btn-details {
        background: none;
        border: none;
        display: flex;
        align-items: center;
        gap: 4px;
        color: var(--primary-color);
        cursor: pointer;
        font-size: 0.85rem;
        padding: 4px 8px;
        border-radius: 6px;
        transition: var(--transition-fast);
      }
      .btn-details:hover {
        background: var(--hover-bg);
      }
      .log-content {
        max-height: 120px;
        overflow-y: auto;
        padding: 8px 0;
      }
      .progress-log .log-content {
        display: none;
        margin-top: 12px;
        max-height: 180px;
        overflow: auto;
        padding-right: 6px;
        background: var(--table-bg);       /* fondo dedicato */
        border: 1px solid var(--border-light);
        border-radius: 10px;
      }
      .log-entry {
        padding: 4px 0;
        font-size: 0.8rem;
        color: var(--text-primary);
        display: flex;
        gap: 8px;
      }
      .log-message  {
        color: var(--text-primary);
      }
      .log-time {
        color: var(--text-muted);
        font-family: monospace;
        min-width: 50px;
      }
      .progress-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
      }
      .progress-actions button {
        padding: 8px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        transition: var(--transition-fast);
      }
      .btn-cancel {
        background: var(--border-color);
        color: var(--text-secondary);
      }
      .btn-cancel:hover {
        background: var(--hover-bg);
      }
      .btn-refresh {
        background: var(--primary-color);
        color: white;
      }
      .btn-refresh:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
      }
      </style>
    `;
    
    document.head.insertAdjacentHTML('beforeend', styles);
    document.body.appendChild(overlay);

    // Riferimenti agli elementi
    progressBar = overlay.querySelector('#progressFill');
    statusText = overlay.querySelector('#statusText');
    logContainer = overlay.querySelector('#logContent');
    cacheInfo = overlay.querySelector('#cacheStatus');
    etaDisplay = overlay.querySelector('#etaDisplay');
  }

  function bindEvents(){
    if(!overlay) return;

    const btnDetails = overlay.querySelector('#btnDetails');
    const btnCancel = overlay.querySelector('#btnCancelProgress');
    const btnRefresh = overlay.querySelector('#btnForceRefresh');

    if(btnDetails){
      btnDetails.addEventListener('click', toggleDetails);
    }

    if(btnCancel){
      btnCancel.addEventListener('click', hide);
    }

    if(btnRefresh){
      btnRefresh.addEventListener('click', forceRefresh);
    }
  }

  function show(cacheStatus = 'unknown'){
    if(!overlay) init();
    
    isActive = true;
    pollAttempts = 0;
    overlay.style.display = 'flex';
    
    // Reset UI
    updateProgress(0, 1, 'Inizializzazione...', 0); // *** FORZA 0% ***
    updateCacheStatus(cacheStatus);
    clearLog();

    // Mostra animazione di entrata
    setTimeout(() => {
      if(overlay) overlay.style.opacity = '1';
    }, 50);
  }

  function hide(){
    if(!overlay) return;
    
    isActive = false;
    stopPolling();
    
    overlay.style.display = 'none';
    overlay.style.opacity = '0';
    
    // Reset dettagli
    detailsExpanded = false;
    const logContent = overlay.querySelector('#logContent');
    const btnDetails = overlay.querySelector('#btnDetails');
    const icon = btnDetails?.querySelector('.material-icons');
    
    if(logContent) logContent.style.display = 'none';
    if(icon) icon.textContent = 'expand_more';
  }

  function updateProgress(step, total, label, percent = null){
    if(!overlay) return;

    const calculatedPercent = percent !== null ? percent : (total > 0 ? Math.round((step / total) * 100) : 0);
    
    if(progressBar){
      progressBar.style.width = calculatedPercent + '%';
    }
    
    const progressText = overlay.querySelector('#progressText');
    if(progressText){
      progressText.textContent = calculatedPercent + '%';
    }
    
    if(statusText){
      statusText.textContent = label || 'In elaborazione...';
    }

    // Aggiorna stepper
    updateStepper(step, total);
  }

  function updateStepper(step, total){
    const steps = overlay?.querySelectorAll('.step');
    if(!steps || steps.length === 0) return;

    const progressPercent = total > 0 ? (step / total) * 100 : 0;
    
    steps.forEach((stepEl, index) => {
      const stepPercent = ((index + 1) / steps.length) * 100;
      
      stepEl.classList.remove('active', 'completed');
      
      if(progressPercent >= stepPercent){
        stepEl.classList.add('completed');
      } else if(progressPercent > (stepPercent - (100 / steps.length))){
        stepEl.classList.add('active');
      }
    });
  }

  function updateCacheStatus(status, age = null){
    if(!cacheInfo) return;

    // Rimuovi classi esistenti
    cacheInfo.className = 'cache-status';
    
    // Aggiungi classe specifica
    cacheInfo.classList.add(status);
    
    switch(status){
      case 'fresh':
      case 'valid':
        cacheInfo.textContent = age ? `Cache valida (${age})` : 'Cache valida';
        break;
      case 'rebuilding':
        cacheInfo.textContent = 'Rigenerazione in corso...';
        break;
      case 'stale':
      case 'expired':
        cacheInfo.textContent = 'Cache scaduta';
        break;
      case 'empty':
        cacheInfo.textContent = 'Nessuna cache';
        break;
      default:
        cacheInfo.textContent = 'Stato cache sconosciuto';
    }
  }

  function addLogEntry(message, timestamp = null){
    if(!logContainer) return;

    const time = timestamp ? new Date(timestamp * 1000).toLocaleTimeString() : new Date().toLocaleTimeString();
    const timeShort = time.substring(0, 5); // HH:MM

    const entry = document.createElement('div');
    entry.className = 'log-entry';
    entry.innerHTML = `
      <span class="log-time">${timeShort}</span>
      <span class="log-message">${message}</span>
    `;

    logContainer.appendChild(entry);

    // Mantieni solo le ultime 8 entries
    while(logContainer.children.length > 8){
      logContainer.removeChild(logContainer.firstChild);
    }

    // Auto-scroll se i dettagli sono espansi
    if(detailsExpanded && logContainer.scrollHeight > logContainer.clientHeight){
      logContainer.scrollTop = logContainer.scrollHeight;
    }
  }

  function clearLog(){
    if(logContainer){
      logContainer.innerHTML = '';
    }
  }

  function toggleDetails(){
    if(!overlay) return;

    detailsExpanded = !detailsExpanded;
    const logContent = overlay.querySelector('#logContent');
    const icon = overlay.querySelector('#btnDetails .material-icons');
    const text = overlay.querySelector('#btnDetails .details-text');

    if(logContent){
      logContent.style.display = detailsExpanded ? 'block' : 'none';
    }
    
    if(icon){
      icon.textContent = detailsExpanded ? 'expand_less' : 'expand_more';
    }
    
    if(text){
      text.textContent = detailsExpanded ? 'Nascondi' : 'Dettagli';
    }
  }

  function forceRefresh(){
    hide();
    // Riavvia il caricamento forzando il refresh
    if(typeof loadData === 'function'){
      loadData(true); // force refresh
    }
  }

  function startPolling(){
    if(pollInterval) stopPolling();
    
    pollInterval = setInterval(pollStatus, POLL_INTERVAL);
    // Primo poll immediato
    pollStatus();
  }

  function stopPolling(){
    if(pollInterval){
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  async function pollStatus(){
    if(!isActive){
      stopPolling();
      return;
    }

    pollAttempts++;
    
    if(pollAttempts > MAX_POLL_ATTEMPTS){
      handleError('Timeout: operazione troppo lunga');
      return;
    }

    try {
      const progressUrl = new URL('status/progress.php', window.location.href);
      const response = await fetch(progressUrl, {
        cache: 'no-cache',
        headers: { 'Cache-Control': 'no-cache' }
      });


      if(!response.ok){
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();

      if(data.error){
        handleError(data.error);
        return;
      }

      const ci = data.cache_info;
      if (ci && ci.status) {
        updateCacheStatus(ci.status, ci.age_formatted || null);
      }

      // Aggiorna UI
      updateProgress(data.step, data.total, data.label, data.percent);

      // Aggiorna log
      if(data.log && Array.isArray(data.log)){
        // Aggiungi solo nuovi messaggi (semplificato)
        data.log.forEach(entry => {
          addLogEntry(entry.label, entry.ts);
        });
      }

      // Check completamento
      if(data.completed || data.percent >= 100){
        handleCompletion(data.cache_info);
      }

    } catch(error) {
      console.warn('Progress poll error:', error);
      ProgressManager.addLogEntry(`Errore polling: ${error.message || error}`);
      // Non fermare per errori di rete sporadici, ma limita i tentativi
      if(pollAttempts > 10 && pollAttempts % 10 === 0){
        addLogEntry(`Errore di rete (tentativo ${pollAttempts})`);
      }
    }
  }

  function handleCompletion(cacheInfo){    
    // Mostra stato completato
    updateProgress(100, 100, 'Operazione completata', 100);
    
    if (cacheInfo && cacheInfo.built_at) {
      updateCacheStatus('valid', cacheInfo.age_formatted || cacheInfo.age);
    }
    
    // Chiama il callback ma NON nascondere
    if(typeof window.onLoadDataComplete === 'function'){
      window.onLoadDataComplete();
    }
  }

  // *** AGGIUNGI METODO PER FORZARE COMPLETAMENTO DALL'ESTERNO ***
  function forceComplete(){
    isActive = false;
    updateProgress(100, 100, 'Completato!', 100);
    
    // Nascondi dopo un breve delay
    setTimeout(() => {
      hide();
    }, 1000);
  }

  function handleError(message){
    stopPolling();
    isActive = false;
    
    updateProgress(0, 1, `Errore: ${message}`, 0);
    addLogEntry(`ERRORE: ${message}`);
    
    // Mostra pulsante per aggiornamento forzato
    const btnRefresh = overlay?.querySelector('#btnForceRefresh');
    if(btnRefresh){
      btnRefresh.style.display = 'flex';
    }
    
    // Callback di errore se definito
    if(typeof window.onLoadDataError === 'function'){
      window.onLoadDataError(message);
    }
  }

  

  // API pubblica
  return {
    show,
    hide,
    updateProgress,
    updateCacheStatus, 
    addLogEntry,
    forceComplete,  
    isActive: () => isActive,
    showNonAdminBlock: function() {
      if (!overlay) init();
      
      isActive = true;
      overlay.style.display = 'flex';
      
      // Modifica contenuto per non-admin
      const modal = overlay.querySelector('.progress-modal');
      modal.innerHTML = `
        <div style="text-align: center; padding: 40px;">
          <span class="material-icons" style="font-size: 64px; color: var(--warning-color); margin-bottom: 24px;">info</span>
          <h2 style="margin-bottom: 16px; color: var(--text-primary);">Cache assente</h2>
          <p style="margin-bottom: 24px; color: var(--text-secondary);">
            I dati della cache non sono disponibili.<br>
            Contatta l'amministratore per rigenerare la cache.
          </p>
          <button onclick="location.reload()" style="
            padding: 12px 24px; background: var(--primary-color); 
            color: white; border: none; border-radius: 8px; cursor: pointer;
          ">
            Aggiorna pagina
          </button>
        </div>
      `;
      
      // Rimuovi possibilità di chiudere
      overlay.style.pointerEvents = 'auto';
    }
  };
})();

// Inizializza quando il DOM è pronto
if(document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', () => ProgressManager.init?.());
} else {
  ProgressManager.init?.();
}