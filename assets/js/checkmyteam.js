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

// ===== BOOTSTRAP & DATA LOADING =====
async function initializeApp() {
  try {
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
    
  } catch (error) {
    console.error('Initialization error:', error);
    
    if (window.ProgressManager) {
      ProgressManager.hide();
    } else {
      hideLoading();
    }
    
    showMessage('Errore di inizializzazione: ' + error.message, 'danger');
  }
}

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
  
  // Cache controls
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

// ===== CACHE FUNCTIONS =====
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

// Make removeSelectedCriteria globally accessible
window.removeSelectedCriteria = removeSelectedCriteria;
