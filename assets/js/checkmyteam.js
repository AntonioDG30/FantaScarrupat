/**
 * CheckMyTeam - Sistema semplificato con nuovo cache manager e persistenza dati
 * Versione aggiornata con salvataggio automatico in localStorage
 */

// Configurazioni rosa
const SQUAD_CONFIG = {
  'P': { count: 3, name: 'Portieri', icon: 'sports_soccer', color: '#FF9800' },
  'D': { count: 8, name: 'Difensori', icon: 'shield', color: '#4CAF50' },
  'C': { count: 8, name: 'Centrocampisti', icon: 'directions_run', color: '#2196F3' },
  'A': { count: 6, name: 'Attaccanti', icon: 'flash_on', color: '#F44336' }
};

// Chiavi per localStorage
const STORAGE_KEYS = {
  selectedCriteria: 'checkmyteam_selected_criteria',
  selectedPlayers: 'checkmyteam_selected_players',
  criteriaSets: 'checkmyteam_criteria_sets',
  lastSaved: 'checkmyteam_last_saved'
};

// Stato dell'applicazione
let appState = {
  playersData: null,
  playersByRole: { P: [], D: [], C: [], A: [] },
  criteriaList: {},
  selectedCriteria: new Map(),
  criteriaSets: {},
  selectedPlayers: {},
  playerSelects: {},
  isReady: false,
  persistenceEnabled: true // Flag per controllare il salvataggio automatico
};

// ===== UTILITIES =====
function showMessage(msg, type = 'info') {
  if (window.cacheManager) {
    window.cacheManager.showMessage(msg, type);
  } else {
    console.log(`[${type}] ${msg}`);
  }
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

// ===== PERSISTENZA DATI =====

/**
 * Verifica se localStorage è disponibile
 */
function isLocalStorageAvailable() {
  try {
    const test = '__localStorage_test__';
    localStorage.setItem(test, test);
    localStorage.removeItem(test);
    return true;
  } catch (e) {
    console.warn('localStorage non disponibile:', e);
    return false;
  }
}

/**
 * Salva i dati correnti in localStorage
 */
function saveToLocalStorage() {
  if (!appState.persistenceEnabled || !isLocalStorageAvailable()) return;
  
  try {
    // Converti Map in oggetto per selectedCriteria
    const selectedCriteriaObj = {};
    appState.selectedCriteria.forEach((value, key) => {
      selectedCriteriaObj[key] = value;
    });
    
    // Converti Set in array per criteriaSets
    const criteriaSetsObj = {};
    Object.keys(appState.criteriaSets).forEach(key => {
      if (appState.criteriaSets[key] instanceof Set) {
        criteriaSetsObj[key] = Array.from(appState.criteriaSets[key]);
      } else {
        criteriaSetsObj[key] = appState.criteriaSets[key];
      }
    });
    
    const dataToSave = {
      selectedCriteria: selectedCriteriaObj,
      selectedPlayers: appState.selectedPlayers,
      criteriaSets: criteriaSetsObj,
      timestamp: new Date().toISOString(),
      version: '1.0'
    };
    
    localStorage.setItem(STORAGE_KEYS.selectedCriteria, JSON.stringify(selectedCriteriaObj));
    localStorage.setItem(STORAGE_KEYS.selectedPlayers, JSON.stringify(appState.selectedPlayers));
    localStorage.setItem(STORAGE_KEYS.criteriaSets, JSON.stringify(criteriaSetsObj));
    localStorage.setItem(STORAGE_KEYS.lastSaved, dataToSave.timestamp);
    
    console.log('Dati salvati in localStorage:', {
      criteriaCount: Object.keys(selectedCriteriaObj).length,
      playersCount: Object.keys(appState.selectedPlayers).length,
      criteriaSetsCount: Object.keys(criteriaSetsObj).length
    });
    
    // Aggiorna indicatore di salvataggio
    updatePersistenceIndicator(true);
    
  } catch (e) {
    console.error('Errore salvataggio localStorage:', e);
    updatePersistenceIndicator(false);
  }
}

/**
 * Carica i dati da localStorage
 */
function loadFromLocalStorage() {
  if (!isLocalStorageAvailable()) return false;
  
  try {
    const selectedCriteriaData = localStorage.getItem(STORAGE_KEYS.selectedCriteria);
    const selectedPlayersData = localStorage.getItem(STORAGE_KEYS.selectedPlayers);
    const criteriaSetsData = localStorage.getItem(STORAGE_KEYS.criteriaSets);
    const lastSaved = localStorage.getItem(STORAGE_KEYS.lastSaved);
    
    if (!selectedCriteriaData && !selectedPlayersData) {
      console.log('Nessun dato salvato trovato in localStorage');
      return false;
    }
    
    // Ripristina selectedCriteria (da oggetto a Map)
    if (selectedCriteriaData) {
      const selectedCriteriaObj = JSON.parse(selectedCriteriaData);
      appState.selectedCriteria = new Map();
      Object.keys(selectedCriteriaObj).forEach(key => {
        appState.selectedCriteria.set(key, selectedCriteriaObj[key]);
      });
    }
    
    // Ripristina selectedPlayers
    if (selectedPlayersData) {
      appState.selectedPlayers = JSON.parse(selectedPlayersData);
    }
    
    // Ripristina criteriaSets (da array a Set)
    if (criteriaSetsData) {
      const criteriaSetsObj = JSON.parse(criteriaSetsData);
      appState.criteriaSets = {};
      Object.keys(criteriaSetsObj).forEach(key => {
        if (Array.isArray(criteriaSetsObj[key])) {
          appState.criteriaSets[key] = new Set(criteriaSetsObj[key]);
        } else {
          appState.criteriaSets[key] = criteriaSetsObj[key];
        }
      });
    }
    
    console.log('Dati caricati da localStorage:', {
      criteriaCount: appState.selectedCriteria.size,
      playersCount: Object.keys(appState.selectedPlayers).length,
      criteriaSetsCount: Object.keys(appState.criteriaSets).length,
      lastSaved: lastSaved
    });
    
    if (lastSaved) {
      const savedDate = new Date(lastSaved);
      showMessage(`Dati ripristinati dall'ultima sessione (${savedDate.toLocaleDateString()} ${savedDate.toLocaleTimeString()})`, 'info');
    }
    
    return true;
    
  } catch (e) {
    console.error('Errore caricamento localStorage:', e);
    showMessage('Errore nel ripristino dei dati salvati', 'warning');
    return false;
  }
}

/**
 * Cancella tutti i dati da localStorage
 */
function clearLocalStorage() {
  if (!isLocalStorageAvailable()) return;
  
  try {
    Object.values(STORAGE_KEYS).forEach(key => {
      localStorage.removeItem(key);
    });
    console.log('localStorage pulito');
  } catch (e) {
    console.error('Errore pulizia localStorage:', e);
  }
}

/**
 * Aggiorna l'indicatore di persistenza
 */
function updatePersistenceIndicator(success = true) {
  const indicator = document.getElementById('squadPersistenceIndicator');
  if (!indicator) return;
  
  const icon = indicator.querySelector('.material-icons');
  const text = indicator.querySelector('.indicator-text');
  
  if (success) {
    icon.textContent = 'save';
    if (text) text.textContent = 'Le tue selezioni vengono salvate automaticamente';
    indicator.style.color = 'var(--success-color, #059669)';
    indicator.style.borderColor = 'var(--success-color, #059669)';
    indicator.style.backgroundColor = 'var(--success-bg, rgba(16, 185, 129, 0.1))';
  } else {
    icon.textContent = 'save_off';
    if (text) text.textContent = 'Errore nel salvataggio automatico';
    indicator.style.color = 'var(--warning-color, #d97706)';
    indicator.style.borderColor = 'var(--warning-color, #d97706)';
    indicator.style.backgroundColor = 'var(--warning-bg, rgba(245, 158, 11, 0.1))';
  }
}

// ===== RESET FUNCTIONALITY =====

/**
 * Reset completo dell'applicazione
 */
function resetApplication() {
  // Disabilita temporaneamente il salvataggio automatico
  appState.persistenceEnabled = false;
  
  try {
    // Pulisci stato in memoria
    appState.selectedCriteria.clear();
    appState.criteriaSets = {};
    appState.selectedPlayers = {};
    
    // Pulisci localStorage
    clearLocalStorage();
    
    // Reset interfaccia
    resetInterface();
    
    // Riabilita salvataggio automatico
    appState.persistenceEnabled = true;
    
    showMessage('Reset completato con successo', 'success');
    
  } catch (e) {
    console.error('Errore durante il reset:', e);
    showMessage('Errore durante il reset: ' + e.message, 'danger');
    
    // Riabilita salvataggio automatico anche in caso di errore
    appState.persistenceEnabled = true;
  }
}

/**
 * Reset solo della rosa (mantiene i criteri)
 */
function clearSquadOnly() {
  // Disabilita temporaneamente il salvataggio automatico
  appState.persistenceEnabled = false;
  
  try {
    // Pulisci solo i giocatori selezionati
    appState.selectedPlayers = {};
    
    // Salva lo stato aggiornato
    appState.persistenceEnabled = true;
    saveToLocalStorage();
    
    // Reset interfaccia della rosa
    resetSquadInterface();
    
    showMessage('Rosa svuotata con successo', 'success');
    
  } catch (e) {
    console.error('Errore durante lo svuotamento della rosa:', e);
    showMessage('Errore durante lo svuotamento della rosa: ' + e.message, 'danger');
    
    // Riabilita salvataggio automatico anche in caso di errore
    appState.persistenceEnabled = true;
  }
}

/**
 * Reset dell'interfaccia
 */
function resetInterface() {
  // Reset criteri selezionati
  document.getElementById('selectedCriteriaContainer').style.display = 'none';
  document.getElementById('selectedCriteriaList').innerHTML = '';
  
  // Reset selettore criteri
  const criteriaSelect = document.getElementById('criteriaSelect');
  if (criteriaSelect) {
    criteriaSelect.value = '';
  }
  
  // Reset rosa
  resetSquadInterface();
}

/**
 * Reset solo dell'interfaccia della rosa
 */
function resetSquadInterface() {
  // Reset TomSelect instances
  Object.values(appState.playerSelects).forEach(tomSelect => {
    if (tomSelect && tomSelect.clear) {
      tomSelect.clear();
    }
  });
  
  // Reset slot visuals
  document.querySelectorAll('.player-slot').forEach(slot => {
    slot.classList.remove('has-selection');
    const criteriaDiv = slot.querySelector('.player-criteria');
    if (criteriaDiv) {
      criteriaDiv.textContent = 'Seleziona dei criteri per vedere la valutazione';
      criteriaDiv.className = 'player-criteria no-matches';
    }
  });
  
  // Reset native selects (fallback)
  document.querySelectorAll('.player-select').forEach(select => {
    select.value = '';
  });
}

// ===== RIPRISTINO STATO =====

/**
 * Ripristina l'interfaccia dopo il caricamento dei dati
 */
async function restoreInterface() {
  if (!appState.isReady) return;
  
  try {
    // Ripristina criteri selezionati
    if (appState.selectedCriteria.size > 0) {
      // Carica i set di criteri mancanti
      for (const [criteriaId] of appState.selectedCriteria) {
        if (!appState.criteriaSets[criteriaId]) {
          try {
            await loadCriteriaSet(criteriaId);
          } catch (e) {
            console.warn(`Impossibile caricare criterio ${criteriaId}:`, e);
          }
        }
      }
      
      // Renderizza i criteri
      renderSelectedCriteria();
      updateAllPlayerMatches();
      debouncedUpdateSquadCounts();
    }
    
    // Ripristina giocatori selezionati con retry logic
    if (Object.keys(appState.selectedPlayers).length > 0) {
      // Aspetta un momento per assicurarsi che TomSelect sia completamente inizializzato
      setTimeout(() => {
        Object.entries(appState.selectedPlayers).forEach(([slotId, playerId]) => {
          restorePlayerSelection(slotId, playerId);
        });
      }, 100);
    }
    
    console.log('Interfaccia ripristinata con successo');
    updatePersistenceIndicator(true);
    
  } catch (e) {
    console.error('Errore ripristino interfaccia:', e);
    showMessage('Errore nel ripristino dell\'interfaccia', 'warning');
  }
}

/**
 * Ripristina la selezione di un singolo giocatore con gestione robusta
 */
function restorePlayerSelection(slotId, playerId) {
  const tomSelect = appState.playerSelects[slotId];
  const selectElement = document.getElementById(`select-${slotId}`);
  
  // Converti playerId in stringa per consistenza
  const playerIdStr = String(playerId);
  
  if (tomSelect && tomSelect.setValue) {
    // Verifica che l'opzione esista in TomSelect
    const hasOption = tomSelect.options.hasOwnProperty(playerIdStr);
    
    if (hasOption) {
      tomSelect.setValue(playerIdStr, true); // true = silent, no trigger events
      console.log(`TomSelect restored for ${slotId}: ${playerIdStr}`);
    } else {
      console.warn(`Option ${playerIdStr} not found in TomSelect for ${slotId}`);
      
      // Prova a trovare l'opzione nel DOM e aggiungerla se necessario
      if (selectElement) {
        const option = selectElement.querySelector(`option[value="${playerIdStr}"]`);
        if (option) {
          // L'opzione esiste nel DOM ma non in TomSelect, forza refresh
          tomSelect.sync();
          setTimeout(() => {
            if (tomSelect.options.hasOwnProperty(playerIdStr)) {
              tomSelect.setValue(playerIdStr, true);
              console.log(`TomSelect restored after sync for ${slotId}: ${playerIdStr}`);
            }
          }, 50);
        }
      }
    }
  } else if (selectElement) {
    // Fallback per select native
    selectElement.value = playerIdStr;
    console.log(`Native select restored for ${slotId}: ${playerIdStr}`);
  }
  
  // Aggiorna stato visuale sempre
  const slot = document.querySelector(`#select-${slotId}`)?.closest('.player-slot');
  if (slot) {
    slot.classList.add('has-selection');
  }
  
  // Aggiorna criteri del giocatore
  updatePlayerCriteria(slotId, playerIdStr);
}

// ===== INIZIALIZZAZIONE =====
async function initializeApp() {
  try {
    // Usa il nuovo cache manager
    const success = await window.cacheManager.initialize();
    
    if (!success) {
      // Cache manager ha già gestito l'errore con overlay/banner
      return;
    }
    
    // Cache OK - carica i dati per CheckMyTeam
    await loadCheckMyTeamData();
    await buildInterface();
    
    // Carica dati salvati DOPO che l'interfaccia è pronta
    const hasStoredData = loadFromLocalStorage();
    
    appState.isReady = true;
    
    // Ripristina interfaccia se ci sono dati salvati
    if (hasStoredData) {
      await restoreInterface();
    }
    
    showMessage('CheckMyTeam pronto', 'success');
    
  } catch (error) {
    console.error('CheckMyTeam initialization error:', error);
    showMessage('Errore di inizializzazione: ' + error.message, 'danger');
  }
}

// ===== CARICAMENTO DATI SPECIFICI =====
async function loadCheckMyTeamData() {
  try {
    // Verifica che i dati di sessione siano disponibili (caricati dal cache manager)
    if (!window._SESSION_DATA_LOADED) {
      throw new Error('Dati sessione non disponibili. Cache non caricata correttamente.');
    }
    
    // USA L'ENDPOINT BOOTSTRAP CORRETTO invece di data.php?action=status
    const response = await fetch('api/checkteam.php?action=bootstrap', {
      headers: { 'X-CSRF-Token': getCsrfToken() }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'Errore caricamento dati CheckMyTeam');
    }
    
    // I dati vengono già normalizzati dall'endpoint bootstrap
    const players = data.players || [];
    const playersByRole = data.playersByRole || { P: [], D: [], C: [], A: [] };
    const criteriaList = data.criteriaList || {};
    
    // Popola lo stato
    appState.playersData = players;
    appState.playersByRole = playersByRole;
    appState.criteriaList = criteriaList;
    
    console.log('CheckMyTeam data loaded from bootstrap:', {
      players: appState.playersData.length,
      criteriaCount: Object.keys(appState.criteriaList).length,
      byRole: Object.keys(appState.playersByRole).map(r => `${r}: ${appState.playersByRole[r].length}`).join(', ')
    });
    
    // Valida che abbiamo ricevuto dati validi
    if (appState.playersData.length === 0) {
      throw new Error('Nessun giocatore ricevuto dall\'endpoint bootstrap');
    }
    
    // Verifica che tutti i ruoli abbiano giocatori
    const roleCheck = Object.keys(appState.playersByRole).map(role => ({
      role,
      count: appState.playersByRole[role].length
    }));
    
    console.log('Players by role:', roleCheck);
    
    if (roleCheck.every(r => r.count === 0)) {
      throw new Error('Nessun giocatore trovato per nessun ruolo');
    }
    
  } catch (error) {
    console.error('LoadCheckMyTeamData error:', error);
    throw error;
  }
}

// ===== FUNZIONI DI SUPPORTO =====
function groupPlayersByRole(players) {
  const byRole = {'P': [], 'D': [], 'C': [], 'A': []};
  
  if (!Array.isArray(players)) {
    console.warn('Players data is not an array:', players);
    return byRole;
  }
  
  console.log('Grouping players by role. Total players:', players.length);
  console.log('Sample player data:', players.slice(0, 3));
  
  players.forEach((player, index) => {
    // Gestisci diversi formati possibili per il ruolo
    const role = player.ruolo_classic || 
                 player.ruolo || 
                 player.R || 
                 '';
    
    // Normalizza il nome completo
    const nomeCompleto = player.nome_completo || 
                        player.nome || 
                        player.Nome || 
                        `Giocatore ${index}`;
    
    // Normalizza la squadra
    const squadra = player.squadra || 
                   player.squadra_attuale || 
                   player.Squadra || 
                   'Squadra sconosciuta';
    
    // Normalizza l'ID
    const playerId = player.id || 
                    player.codice_fantacalcio || 
                    player.Id || 
                    index;
    
    if (byRole[role]) {
      byRole[role].push({
        id: playerId,
        nome_completo: nomeCompleto,
        squadra: squadra,
        ruolo_classic: role,
        // Mantieni anche i dati originali per sicurezza
        _original: player
      });
    } else {
      console.warn(`Unknown role: "${role}" for player:`, player);
    }
  });
  
  // Log finale per debug
  Object.keys(byRole).forEach(role => {
    console.log(`Role ${role}: ${byRole[role].length} players`);
  });
  
  return byRole;
}

function getHardcodedCriteriaList() {
  return {
    '1': { name: 'Under 23 (al 1° luglio)', description: 'Giocatori nati dopo il 1° luglio 2002' },
    '2': { name: 'Over 32 (al 1° luglio)', description: 'Giocatori nati prima del 1° luglio 1993' },
    '3': { name: 'Prima stagione in Serie A', description: 'Giocatori senza presenze storiche' },
    '4': { name: 'Più di 200 presenze in Serie A', description: 'Giocatori esperti' },
    '5': { name: 'Giocatori sudamericani', description: 'Nazionalità sudamericane' },
    '6': { name: 'Giocatori africani', description: 'Nazionalità africane' },
    '7': { name: 'Europei non italiani', description: 'Nazionalità europee escl. Italia' },
    '8': { name: 'Squadre neopromosse', description: 'Squadre promosse quest\'anno' },
    '9': { name: 'Squadre 10°—17° scorsa stagione', description: 'Squadre di media classifica' },
    '10': { name: 'Portieri squadre con GA ≥ 50', description: 'Portieri squadre difese deboli' },
    '11': { name: 'Difensori con almeno 1 gol', description: 'Difensori che segnano' },
    '12': { name: 'Centrocampisti con almeno 3 assist', description: 'Centrocampisti creativi' },
    '13': { name: 'Attaccanti con massimo 5 gol', description: 'Attaccanti poco prolifici' },
    '14': { name: 'Meno di 10 presenze', description: 'Giocatori poco utilizzati' },
    '15': { name: 'Media voto < 6', description: 'Giocatori con rendimento basso' },
    '16': { name: 'Quotazione ≤ 6', description: 'Giocatori economici' },
    '17': { name: 'Quotazione ≤ 3', description: 'Giocatori molto economici' },
    '18': { name: 'Rosa stagione precedente', description: 'Giocatori della rosa dell\'anno scorso' },
    '19': { name: 'Ritorno in Serie A', description: 'Giocatori che tornano dopo assenza' },
    '20': { name: 'Almeno 5 partite \'6*\' (S.V.)', description: 'Giocatori spesso senza voto' },
    '21': { name: 'Più di 7 ammonizioni', description: 'Giocatori indisciplinati' },
    '22': { name: 'Cambiato squadra', description: 'Nuovi acquisti' },
    '23': { name: 'Almeno 1 autogol', description: 'Giocatori sfortunati' },
    '24': { name: 'Almeno 34 presenze', description: 'Giocatori sempre utilizzati' },
    '25': { name: 'Almeno un rigore sbagliato', description: 'Rigoristi imprecisi' },
    '26': { name: 'Zero ammonizioni/espulsioni', description: 'Giocatori disciplinati' },
    '27': { name: 'Presenti ultime 3 stagioni', description: 'Giocatori costanti' },
    '28': { name: 'Gol+Assist ≥ 5', description: 'Giocatori offensivi produttivi' },
    '29': { name: 'Alto rapporto cartellini/presenze', description: 'Giocatori indisciplinati' },
    '30': { name: 'Rigoristi designati', description: 'Tiratori di rigori' },
    '31': { name: 'Cambio ruolo ufficiale', description: 'Giocatori che cambiano posizione' },
    '32': { name: 'Esordienti assoluti', description: 'Prima volta in Serie A' }
  };
}

// ===== COSTRUZIONE INTERFACCIA =====
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
    if (!container) return;
    
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
      
      // Inizializza Tom Select se disponibile
      try {
        if (window.TomSelect) {
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
          
          tomSelect.on('change', (value) => {
            handlePlayerSelection(slotId, value);
          });
          
          appState.playerSelects[slotId] = tomSelect;
        } else {
          // Fallback to native select
          selectElement.addEventListener('change', (e) => {
            handlePlayerSelection(slotId, e.target.value);
          });
        }
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
  const btnAdd = document.getElementById('btnAddCriteria');
  if (btnAdd) {
    btnAdd.addEventListener('click', addSelectedCriteria);
  }
  
  // Reset buttons
  const btnResetAll = document.getElementById('btnResetAll');
  if (btnResetAll) {
    btnResetAll.addEventListener('click', showResetModal);
  }
  
  const btnClearSquad = document.getElementById('btnClearSquad');
  if (btnClearSquad) {
    btnClearSquad.addEventListener('click', () => {
      if (confirm('Sei sicuro di voler svuotare tutta la rosa?')) {
        clearSquadOnly();
      }
    });
  }
  
  const btnConfirmReset = document.getElementById('btnConfirmReset');
  if (btnConfirmReset) {
    btnConfirmReset.addEventListener('click', () => {
      resetApplication();
      
      // Chiudi modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
      if (modal) modal.hide();
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

function showResetModal() {
  const modal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
  modal.show();
}

const debouncedUpdateSquadCounts = debounce(updateSquadCounts, 200);
const debouncedSaveToLocalStorage = debounce(saveToLocalStorage, 1000);

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
  
  select.value = '';
  renderSelectedCriteria();
  
  // Carica set criterio
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
  
  // Salva automaticamente
  debouncedSaveToLocalStorage();
}

function renderSelectedCriteria() {
  const container = document.getElementById('selectedCriteriaContainer');
  const list = document.getElementById('selectedCriteriaList');
  
  if (!container || !list) return;
  
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
  
  // Salva automaticamente
  debouncedSaveToLocalStorage();
}

async function loadCriteriaSet(criteriaId) {
  try {
    // Nel sistema originale, CheckMyTeam usava gli stessi endpoint di criteria.php
    // Non aveva endpoint separati in checkteam.php
    const response = await fetch(`api/criteria.php?action=run&criteria=${criteriaId}`, {
      headers: { 'X-CSRF-Token': getCsrfToken() }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error || 'Errore valutazione criterio');
    }
    
    // Estrai gli ID dei giocatori dai risultati
    const playerIds = data.results.map(player => {
      return parseInt(player.id || player.codice_fantacalcio || 0);
    }).filter(id => id > 0);
    
    appState.criteriaSets[criteriaId] = new Set(playerIds);
    
    console.log(`Criteria ${criteriaId} loaded: ${playerIds.length} players`);
    
    // Salva automaticamente
    debouncedSaveToLocalStorage();
    
  } catch (error) {
    console.error(`Error loading criteria ${criteriaId}:`, error);
    throw error;
  }
}

function updateSquadCounts() {
  const selectedPlayerIds = new Set(Object.values(appState.selectedPlayers));
  
  appState.selectedCriteria.forEach((info, criteriaId) => {
    const countElement = document.getElementById(`squad-count-${criteriaId}`);
    const criteriaSet = appState.criteriaSets[criteriaId];
    
    if (criteriaSet && countElement) {
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
  const slot = document.querySelector(`#select-${slotId}`)?.closest('.player-slot');
  if (slot) {
    if (playerId) {
      slot.classList.add('has-selection');
    } else {
      slot.classList.remove('has-selection');
    }
  }
  
  updatePlayerCriteria(slotId, playerId);
  debouncedUpdateSquadCounts();
  
  // Salva automaticamente
  debouncedSaveToLocalStorage();
}

function updatePlayerCriteria(slotId, playerId) {
  const criteriaDiv = document.getElementById(`criteria-${slotId}`);
  if (!criteriaDiv) return;
  
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
  
  Object.keys(appState.playerSelects).forEach(slotId => {
    if (!appState.selectedPlayers[slotId]) {
      updatePlayerCriteria(slotId, null);
    }
  });
}

// ===== UTILITIES =====
function getCsrfToken() {
  return window.csrfToken || 
         document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
         '';
}

// Make functions globally accessible
window.removeSelectedCriteria = removeSelectedCriteria;

// ===== INIZIALIZZAZIONE =====
document.addEventListener('DOMContentLoaded', () => {
  // IMPORTANTE: Inizializza theme SUBITO
  initializeTheme();
  
  // Inizializza app
  initializeApp();
});

// Aggiungi le stesse funzioni theme di sopra
function initializeTheme() {
  if (window.ThemeManager) {
    const themeManager = new ThemeManager(); 
    themeManager.init();
    console.log('ThemeManager initialized');
  } else {
    console.warn('ThemeManager not found');
    initializeThemeFallback();
  }
  
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
    console.log('Theme toggle button bound');
  }
}

function initializeThemeFallback() {
  const savedTheme = localStorage.getItem('theme') || 'auto';
  applyTheme(savedTheme);
}

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  applyTheme(newTheme);
  localStorage.setItem('theme', newTheme);
  
  const icon = document.getElementById('themeIcon');
  if (icon) {
    icon.textContent = newTheme === 'dark' ? 'light_mode' : 'dark_mode';
  }
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  console.log('Theme applied:', theme);
}