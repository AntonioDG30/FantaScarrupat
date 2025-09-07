// Configurazioni rosa
const SQUAD_CONFIG = {
  'P': { count: 3, name: 'Portieri', icon: 'sports_soccer', color: '#FF9800' },
  'D': { count: 8, name: 'Difensori', icon: 'shield', color: '#4CAF50' },
  'C': { count: 8, name: 'Centrocampisti', icon: 'directions_run', color: '#2196F3' },
  'A': { count: 6, name: 'Attaccanti', icon: 'flash_on', color: '#F44336' }
};

// Stato dell'applicazione
let appState = {
  playersData: null,
  playersByRole: { P: [], D: [], C: [], A: [] },
  criteriaList: {},
  selectedCriteria: new Map(), // Map<criteriaId, {name, description}>
  criteriaSets: {}, // { criteriaId: Set<playerId> }
  selectedPlayers: {}, // { slotId: playerId }
  playerSelects: {}, // { slotId: TomSelect instance }
  isLoading: false
};

// ===== UTILITIES =====
function url(path) {
  const basePath = '<?= getProjectBasePath() ?>';
  return basePath + path.replace(/^\/+/, '');
}

function showMessage(msg, type = 'info') {
  const el = document.createElement('div');
  el.className = 'toast-msg ' + type;
  el.textContent = msg;
  document.body.appendChild(el);
  requestAnimationFrame(() => el.classList.add('show'));
  setTimeout(() => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 300);
  }, 3000);
}

function showLoading(text = 'Caricamento in corso…') {
  if (window.ProgressManager && ProgressManager.isActive && ProgressManager.isActive()) {
    return;
  }
  const overlay = document.getElementById('loadingOverlay');
  overlay.style.display = 'block';
  overlay.querySelector('.loading-text').textContent = text;
}

function hideLoading() {
  document.getElementById('loadingOverlay').style.display = 'none';
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// ===== NUOVA LOGICA ADMIN/NON-ADMIN per CheckMyTeam =====
async function initializeApp() {
  try {
    // Verifica status cache e tipo utente
    const statusResponse = await fetch('api/data.php?action=cache_status', {
      headers: { 'X-CSRF-Token': csrfToken }
    });
    
    if (!statusResponse.ok) {
      throw new Error(`HTTP ${statusResponse.status}: ${statusResponse.statusText}`);
    }
    
    const statusData = await statusResponse.json();
    
    if (!statusData.success) {
      throw new Error('Errore verifica cache: ' + (statusData.error || 'Sconosciuto'));
    }
    
    const isAdmin = statusData.is_admin;
    const cacheStatus = statusData.cache_status;
    
    if (!isAdmin) {
      // NON-ADMIN: controlla solo esistenza cache
      if (!cacheStatus.exists) {
        showCacheMissingOverlay();
        return;
      } else {
        // Cache presente: carica normalmente
        await loadDataForNonAdmin();
        await buildInterface();
        markDataAsLoaded();
        showMessage('Applicazione pronta', 'success');
        return;
      }
    } else {
      // ADMIN: mostra banner informativo e procedi normalmente
      showAdminCacheBanner(cacheStatus);
      
      // Logica originale per admin
      if (window.ProgressManager) {
        ProgressManager.show('empty');
      } else {
        showLoading('Inizializzazione...');
      }

      await loadData();
      await buildInterface();
      
      if (window.ProgressManager) {
        ProgressManager.forceComplete();
      } else {
        hideLoading();
      }
      
      markDataAsLoaded();
      showMessage('Applicazione pronta', 'success');
    }
    
  } catch (error) {
    console.error('Initialization error:', error);
    
    if (window.ProgressManager) {
      ProgressManager.hide();
    } else {
      hideLoading();
    }
    
    if (error.message === 'cache_missing_non_admin') {
      showCacheMissingOverlay();
    } else {
      showMessage('Errore di inizializzazione: ' + error.message, 'danger');
    }
  }
}

// Nuove funzioni per gestione admin/non-admin
async function loadDataForNonAdmin() {
  try {
    // Carica direttamente dalla cache senza controlli TTL
    const response = await fetch('api/checkteam.php?action=bootstrap', {
      headers: { 'X-CSRF-Token': csrfToken }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
      if (data.error === 'cache_missing_non_admin') {
        throw new Error('cache_missing_non_admin');
      } else {
        throw new Error(data.error || 'Errore nel caricamento dati');
      }
    }
    
    // Popola lo stato
    appState.playersData = data.players;
    appState.playersByRole = data.playersByRole;
    appState.criteriaList = data.criteriaList;
    
  } catch (error) {
    console.error('LoadDataForNonAdmin error:', error);
    throw error;
  }
}

function showCacheMissingOverlay() {
  if (window.ProgressManager) {
    ProgressManager.showNonAdminBlock();
  } else {
    // Fallback overlay semplice
    const overlay = document.createElement('div');
    overlay.id = 'cacheMissingOverlay';
    overlay.style.cssText = `
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.8); z-index: 10000;
      display: flex; align-items: center; justify-content: center;
      font-family: var(--font-family, -apple-system, BlinkMacSystemFont, sans-serif);
    `;
    overlay.innerHTML = `
      <div style="
        background: var(--card-bg, white); 
        padding: 40px; 
        border-radius: 16px; 
        text-align: center; 
        max-width: 400px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      ">
        <span class="material-icons" style="
          font-size: 64px; 
          color: var(--warning-color, #f59e0b); 
          margin-bottom: 24px; 
          display: block;
        ">info</span>
        <h2 style="
          margin: 0 0 16px 0; 
          color: var(--text-primary, #1a1a1a);
          font-size: 1.5rem;
          font-weight: 700;
        ">Cache assente</h2>
        <p style="
          margin: 0 0 24px 0; 
          color: var(--text-secondary, #6b7280);
          line-height: 1.5;
        ">I dati della cache non sono disponibili.<br>Contatta l'amministratore per rigenerare la cache.</p>
        <button onclick="location.reload()" style="
          padding: 12px 24px; 
          background: var(--primary-color, #3b82f6); 
          color: white; 
          border: none; 
          border-radius: 8px; 
          cursor: pointer;
          font-weight: 600;
          transition: all 0.2s ease;
        " onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
          Aggiorna pagina
        </button>
      </div>
    `;
    document.body.appendChild(overlay);
    
    // Blocca scroll della pagina
    document.body.style.overflow = 'hidden';
  }
}

function showAdminCacheBanner(cacheStatus) {
  // Controlla se banner già mostrato/dismisso in questa sessione
  if (sessionStorage.getItem('admin_cache_banner_dismissed') === 'true') {
    return;
  }
  
  // Rimuovi banner esistente se presente
  const existingBanner = document.getElementById('adminCacheBanner');
  if (existingBanner) {
    existingBanner.remove();
  }
  
  const banner = document.createElement('div');
  banner.id = 'adminCacheBanner';
  banner.style.cssText = `
    position: fixed; 
    top: 80px; 
    left: 0; 
    right: 0; 
    z-index: 999;
    background: var(--info-bg, rgba(59, 130, 246, 0.1)); 
    border-bottom: 1px solid var(--info-color, #3b82f6);
    padding: 12px 24px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-family: var(--font-family, -apple-system, BlinkMacSystemFont, sans-serif);
  `;
  
  let content = `<div style="display: flex; align-items: center; gap: 12px;">`;
  content += `<span class="material-icons" style="color: var(--info-color, #3b82f6);">info</span>`;
  
  if (cacheStatus.exists) {
    content += `<span style="color: var(--text-primary, #1a1a1a); font-weight: 500;">`;
    content += `Cache presente (età: ${cacheStatus.age_formatted || 'sconosciuta'})`;
    content += `</span>`;
    
    if (cacheStatus.suggestion) {
      content += `<span style="
        margin-left: 16px; 
        padding: 4px 12px; 
        background: var(--warning-bg, rgba(245, 158, 11, 0.1)); 
        color: var(--warning-color, #d97706);
        border-radius: 6px; 
        font-size: 0.85rem;
        font-weight: 600;
        border: 1px solid var(--warning-color, #d97706);
      ">${cacheStatus.suggestion}</span>`;
    }
  } else {
    content += `<span style="color: var(--text-primary, #1a1a1a); font-weight: 500;">`;
    content += `Cache assente - usa i comandi navbar per rigenerare`;
    content += `</span>`;
  }
  
  content += `</div>`;
  content += `<button onclick="dismissAdminBanner()" style="
    background: none; 
    border: none; 
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
  " onmouseover="this.style.background='var(--hover-bg, rgba(0,0,0,0.05))'" onmouseout="this.style.background='none'">`;
  content += `<span class="material-icons">close</span></button>`;
  
  banner.innerHTML = content;
  document.body.appendChild(banner);
  
  // Auto-dismiss dopo 10 secondi se non c'è suggestion
  if (!cacheStatus.suggestion) {
    setTimeout(() => {
      if (document.getElementById('adminCacheBanner')) {
        dismissAdminBanner();
      }
    }, 10000);
  }
}

function dismissAdminBanner() {
  const banner = document.getElementById('adminCacheBanner');
  if (banner) {
    banner.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    banner.style.opacity = '0';
    banner.style.transform = 'translateY(-100%)';
    
    setTimeout(() => {
      banner.remove();
    }, 300);
    
    sessionStorage.setItem('admin_cache_banner_dismissed', 'true');
  }
}

// ===== DATA LOADING (logica originale per admin) =====
async function loadData() {
  const response = await fetch('api/checkteam.php?action=bootstrap', {
    headers: { 'X-CSRF-Token': csrfToken }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }
  
  const data = await response.json();
  
  if (!data.success) {
    throw new Error(data.error || 'Errore nel caricamento dati');
  }
  
  // Popola lo stato
  appState.playersData = data.players;
  appState.playersByRole = data.playersByRole;
  appState.criteriaList = data.criteriaList;

  /** 
    console.log('Data loaded:', {
      players: appState.playersData.length,
      criteriaCount: Object.keys(appState.criteriaList).length,
      byRole: Object.keys(appState.playersByRole).map(r => `${r}: ${appState.playersByRole[r].length}`).join(', ')
    });
  */
}

// ===== INTERFACE BUILDING =====
async function buildInterface() {
  buildCriteriaSelector();
  buildSquadSelectors();
  bindEvents();
}

function buildCriteriaSelector() {
  const select = document.getElementById('criteriaSelect');
  
  if (!appState.criteriaList || Object.keys(appState.criteriaList).length === 0) {
    showMessage('Nessun criterio disponibile', 'warning');
    return;
  }
  
  // Popola la select
  select.innerHTML = '<option value="">Seleziona un criterio</option>';
  Object.entries(appState.criteriaList).forEach(([id, info]) => {
    const option = document.createElement('option');
    option.value = id;
    option.textContent = `${id}. ${info.name}`;
    select.appendChild(option);
  });
  
  select.disabled = false;
  document.getElementById('btnAddCriteria').disabled = false;
}

function buildSquadSelectors() {
  Object.entries(SQUAD_CONFIG).forEach(([role, config]) => {
    const containerMap = {
      'P': 'portieri-slots',
      'D': 'difensori-slots', 
      'C': 'centrocampisti-slots',
      'A': 'attaccanti-slots'
    };
    
    const container = document.getElementById(containerMap[role]);
    container.innerHTML = '';
    
    for (let i = 0; i < config.count; i++) {
      const slotId = `${role}-${i}`;
      const slot = document.createElement('div');
      slot.className = 'player-slot';
      
      slot.innerHTML = `
        <select id="select-${slotId}" class="player-select">
          <option value="">Seleziona ${config.name}</option>
        </select>
        <div class="player-criteria" id="criteria-${slotId}">—</div>
      `;
      
      container.appendChild(slot);
    }
  });
  
  // Inizializza Tom Select
  initializePlayerSelects();
}

function initializePlayerSelects() {
  Object.entries(SQUAD_CONFIG).forEach(([role, config]) => {
    const players = appState.playersByRole[role] || [];
    
    for (let i = 0; i < config.count; i++) {
      const slotId = `${role}-${i}`;
      const selectId = `select-${slotId}`;
      const selectElement = document.getElementById(selectId);
      
      if (!selectElement) continue;
      
      // Popola options
      players.forEach(player => {
        const option = document.createElement('option');
        option.value = player.id;
        option.textContent = `${player.nome_completo} — ${player.squadra}`;
        selectElement.appendChild(option);
      });
      
      // Inizializza Tom Select
      try {
        const tomSelect = new TomSelect(selectElement, {
          placeholder: `Seleziona ${config.name}`,
          searchField: ['text'],
          sortField: { field: 'text', direction: 'asc' },
          dropdownParent: 'body',
          render: {
            option: function(data, escape) {
              return `<div class="option">${escape(data.text)}</div>`;
            }
          }
        });
        
        // Event handler
        tomSelect.on('change', (value) => {
          handlePlayerSelection(slotId, value);
        });
        
        appState.playerSelects[slotId] = tomSelect;
      } catch (error) {
        console.error(`Error initializing select for ${slotId}:`, error);
        
        // Fallback to native select
        selectElement.addEventListener('change', (e) => {
          handlePlayerSelection(slotId, e.target.value);
        });
      }
    }
  });
}

// ===== EVENT HANDLERS =====
function bindEvents() {
  // Add criteria button
  document.getElementById('btnAddCriteria').addEventListener('click', addSelectedCriteria);
  
  // Cache controls (solo per admin)
  document.getElementById('btnCacheInfo')?.addEventListener('click', getCacheInfo);
  document.getElementById('btnForceRefresh')?.addEventListener('click', forceRefreshData);
}

const debouncedUpdateSquadCounts = debounce(updateSquadCounts, 200);

async function addSelectedCriteria() {
  const select = document.getElementById('criteriaSelect');
  const criteriaId = select.value;
  
  if (!criteriaId) {
    showMessage('Seleziona un criterio', 'warning');
    return;
  }
  
  if (appState.selectedCriteria.has(criteriaId)) {
    showMessage('Criterio già selezionato', 'warning');
    return;
  }
  
  const criteriaInfo = appState.criteriaList[criteriaId];
  appState.selectedCriteria.set(criteriaId, criteriaInfo);
  
  // Reset select
  select.value = '';
  
  // Update UI
  renderSelectedCriteria();
  
  // Load criteria set
  if (!appState.criteriaSets[criteriaId]) {
    try {
      await loadCriteriaSet(criteriaId);
    } catch (error) {
      console.error(`Error loading criteria ${criteriaId}:`, error);
      showMessage(`Errore caricamento criterio ${criteriaId}`, 'warning');
    }
  }
  
  debouncedUpdateSquadCounts();
  updateAllPlayerMatches();
}

function renderSelectedCriteria() {
  const container = document.getElementById('selectedCriteriaContainer');
  const list = document.getElementById('selectedCriteriaList');
  
  if (appState.selectedCriteria.size === 0) {
    container.style.display = 'none';
    return;
  }
  
  container.style.display = 'block';
  list.innerHTML = '';
  
  appState.selectedCriteria.forEach((info, criteriaId) => {
    const item = document.createElement('div');
    item.className = 'selected-criteria-item';
    item.dataset.criteriaId = criteriaId;
    
    item.innerHTML = `
      <div class="criteria-info">
        <div class="criteria-name">${criteriaId}. ${info.name}</div>
        <div class="criteria-squad-count loading" id="squad-count-${criteriaId}">
          <span class="material-icons" style="font-size: 14px;">hourglass_empty</span>
          Calcolo in corso...
        </div>
      </div>
      <button class="btn-remove-criteria" onclick="removeSelectedCriteria('${criteriaId}')" title="Rimuovi criterio">
        <span class="material-icons">close</span>
      </button>
    `;
    
    list.appendChild(item);
  });
}

function removeSelectedCriteria(criteriaId) {
  appState.selectedCriteria.delete(criteriaId);
  renderSelectedCriteria();
  updateAllPlayerMatches();
  showMessage('Criterio rimosso', 'info');
}

async function loadCriteriaSet(criteriaId) {
  const response = await fetch('api/checkteam.php?action=evaluate_criteria', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({ criteriaId })
  });
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  
  const data = await response.json();
  
  if (!data.success) {
    throw new Error(data.error || 'Errore valutazione criterio');
  }
  
  appState.criteriaSets[criteriaId] = new Set(data.playerIds);
  
  //console.log(`Criteria ${criteriaId} loaded: ${data.playerIds.length} players`);
}

function updateSquadCounts() {
  const selectedPlayerIds = new Set(Object.values(appState.selectedPlayers));
  
  appState.selectedCriteria.forEach((info, criteriaId) => {
    const countElement = document.getElementById(`squad-count-${criteriaId}`);
    const criteriaSet = appState.criteriaSets[criteriaId];
    
    if (criteriaSet && countElement) {
      // Conta quanti giocatori della rosa rispettano questo criterio
      let count = 0;
      selectedPlayerIds.forEach(playerId => {
        if (criteriaSet.has(parseInt(playerId))) {
          count++;
        }
      });
      
      countElement.innerHTML = `<span class="material-icons" style="font-size: 14px;">check_circle</span>${count} giocatori della tua rosa rispettano questo criterio`;
      countElement.className = 'criteria-squad-count';
    }
  });
}

function handlePlayerSelection(slotId, playerId) {
  // Check for duplicates
  if (playerId && Object.values(appState.selectedPlayers).includes(playerId)) {
    const existingSlot = Object.keys(appState.selectedPlayers).find(k => appState.selectedPlayers[k] === playerId);
    showMessage(`Giocatore già selezionato in ${existingSlot}`, 'warning');
    
    // Reset this selection
    const tomSelect = appState.playerSelects[slotId];
    if (tomSelect) {
      tomSelect.clear();
    }
    return;
  }
  
  // Update state
  if (playerId) {
    appState.selectedPlayers[slotId] = playerId;
  } else {
    delete appState.selectedPlayers[slotId];
  }
  
  // Update slot visual state
  const slot = document.querySelector(`#select-${slotId}`).closest('.player-slot');
  if (playerId) {
    slot.classList.add('has-selection');
  } else {
    slot.classList.remove('has-selection');
  }
  
  // Update player criteria display
  updatePlayerCriteria(slotId, playerId);
  
  // Update squad counts
  debouncedUpdateSquadCounts();
}

function updatePlayerCriteria(slotId, playerId) {
  const criteriaDiv = document.getElementById(`criteria-${slotId}`);
  
  if (!playerId || appState.selectedCriteria.size === 0) {
    criteriaDiv.textContent = appState.selectedCriteria.size === 0 ? 
      'Seleziona dei criteri per vedere la valutazione' : 
      'Non rispetta nessun criterio tra i selezionati';
    criteriaDiv.className = 'player-criteria no-matches';
    return;
  }
  
  const matches = [];
  appState.selectedCriteria.forEach((info, criteriaId) => {
    const criteriaSet = appState.criteriaSets[criteriaId];
    if (criteriaSet && criteriaSet.has(parseInt(playerId))) {
      matches.push(criteriaId);
    }
  });
  
  if (matches.length === 0) {
    criteriaDiv.textContent = 'Non rispetta nessun criterio tra i selezionati';
    criteriaDiv.className = 'player-criteria no-matches';
  } else {
    criteriaDiv.innerHTML = `
      Questo giocatore rispetta questi criteri:
      <div class="criteria-matches">
        ${matches.map(id => `<span class="criteria-match-tag">${id}</span>`).join('')}
      </div>
    `;
    criteriaDiv.className = 'player-criteria has-matches';
  }
}

function updateAllPlayerMatches() {
  Object.keys(appState.selectedPlayers).forEach(slotId => {
    updatePlayerCriteria(slotId, appState.selectedPlayers[slotId]);
  });
  
  // Anche per slot vuoti per aggiornare il messaggio
  Object.keys(appState.playerSelects).forEach(slotId => {
    if (!appState.selectedPlayers[slotId]) {
      updatePlayerCriteria(slotId, null);
    }
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

// ===== CACHE FUNCTIONS (solo per admin) =====
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
        message += `📄 TTL: ${Math.round(data.ttl_seconds/3600)} ore\n`;
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

async function forceRefreshData() {
  if (confirm('Aggiornare i dati ignorando la cache?\n\nQuesta operazione può richiedere alcuni minuti.')) {
    location.reload();
  }
}

// Make functions globally accessible
window.removeSelectedCriteria = removeSelectedCriteria;
window.dismissAdminBanner = dismissAdminBanner;