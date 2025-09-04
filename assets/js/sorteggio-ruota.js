// State
let allUsers = [];
let selectedUsers = [];
let wheelParticipants = [];
let isSpinning = false;
let currentRotation = 0;
let currentSessionStart = null; // Per tracking sessione corrente

// DOM Elements
const usersGrid = document.getElementById('usersGrid');
const selectedUsersContainer = document.getElementById('selectedUsers');
const selectAllBtn = document.getElementById('selectAllBtn');
const clearAllBtn = document.getElementById('clearAllBtn');
const startWheelBtn = document.getElementById('startWheel');
const cancelCurrentSessionBtn = document.getElementById('cancelCurrentSession');
const spinBtn = document.getElementById('spinBtn');
const wheel = document.getElementById('wheel');
const wheelStatus = document.getElementById('wheelStatus');
const results = document.getElementById('results');
const resetDialog = document.getElementById('resetDialog');
const cancelSessionDialog = document.getElementById('cancelSessionDialog');
const resetAssignmentsBtn = document.getElementById('resetAssignments');
const confirmResetBtn = document.getElementById('confirmReset');
const cancelResetBtn = document.getElementById('cancelReset');
const confirmCancelSessionBtn = document.getElementById('confirmCancelSession');
const cancelCancelSessionBtn = document.getElementById('cancelCancelSession');
const toastContainer = document.getElementById('toastContainer');

const defaultWheelStatusHTML = '<em>Seleziona i partecipanti per iniziare</em>';

function resetWheelGraphics() {
    // svuota la ruota e resetta lo stato
    wheel.innerHTML = `
        <div class="wheel-center">
            <span class="material-icons" style="color: var(--primary-color);">stars</span>
        </div>
    `;
    currentRotation = 0;
    isSpinning = false;
    spinBtn.disabled = true;
    wheelStatus.innerHTML = defaultWheelStatusHTML;
}

async function refreshAllUI({ clearResults = true, toastMessage = null } = {}) {
    // ricarica utenti e badge (0/2, 1/2, 2/2)
    await loadUsers();

    // reset selezioni e ruota
    clearAllUsers(true);
    wheelParticipants = [];
    currentSessionStart = null;
    resetWheelGraphics();

    // riabilita interazioni e ripristina i bottoni
    usersGrid.style.pointerEvents = 'auto';
    usersGrid.style.opacity = '1';
    startWheelBtn.disabled = true; // finché non selezioni almeno 2
    clearAllBtn.disabled = true;
    cancelCurrentSessionBtn.disabled = true;

    // abilita/disable "Seleziona tutti" in base a quanti utenti sono disponibili (<2 parametri)
    const availableUsers = allUsers.filter(u => parseInt(u.params_count) < 2);
    selectAllBtn.disabled = availableUsers.length === 0;

    if (clearResults) {
        results.innerHTML = '';
    }

    if (toastMessage) {
        showToast(toastMessage, 'success');
    }
}


// API Functions
async function apiCall(endpoint, options = {}) {
    const currentFile = window.location.pathname.split('/').pop() || 'SorteggioRuota.php';
    const res = await fetch(url(`${currentFile}?api=${endpoint}`), {
        headers: { 'Content-Type': 'application/json' },
        ...options
    });
    const text = await res.text();
    if (!res.ok) throw new Error(`HTTP ${res.status}: ${text.slice(0,200)}`);
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error('Risposta non-JSON dall’API:', text);
        throw new Error('Risposta non valida dal server (non-JSON)');
    }
}


// Toast Functions - uniformi al tema
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-enhanced ${type}`;
    toast.innerHTML = `
        <span class="material-icons">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'warning'}</span>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    // Show animation
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Load Users e render grid - con controllo parametri
async function loadUsers() {
    try {
        allUsers = await apiCall('users-with-params-count');
        renderUsersGrid();
    } catch (error) {
        console.error('Errore caricamento utenti:', error);
        showToast('Errore nel caricamento degli utenti', 'error');
    }
}

// Render users grid con controllo parametri esistenti
function renderUsersGrid() {
    usersGrid.innerHTML = '';
    
    allUsers.forEach(user => {
        const userItem = document.createElement('div');
        const isDisabled = parseInt(user.params_count) >= 2;
        
        userItem.className = `user-item ${isDisabled ? 'disabled' : ''}`;
        userItem.dataset.userId = user.id_user;
        userItem.innerHTML = `
            <div class="user-item-avatar ${isDisabled ? 'disabled' : ''}">
                ${user.username.charAt(0).toUpperCase()}
            </div>
            <span>${user.username}</span>
            ${isDisabled ? 
                '<span class="user-params-badge">2/2</span>' : 
                user.params_count > 0 ? 
                    `<span class="user-params-badge partial">${user.params_count}/2</span>` : 
                    ''
            }
        `;
        
        if (!isDisabled) {
            userItem.addEventListener('click', () => toggleUserSelection(user));
        } else {
            userItem.title = 'Utente ha già raggiunto il limite di 2 parametri';
        }
        
        usersGrid.appendChild(userItem);
    });
}

// Toggle user selection
function toggleUserSelection(user) {
    const existingIndex = selectedUsers.findIndex(u => u.id_user === user.id_user);
    
    if (existingIndex >= 0) {
        // Rimuovi selezione
        selectedUsers.splice(existingIndex, 1);
    } else {
        // Aggiungi selezione
        selectedUsers.push(user);
    }
    
    updateUsersGridDisplay();
    updateSelectedUsersDisplay();
}

// Aggiorna visualizzazione grid utenti
function updateUsersGridDisplay() {
    const userItems = usersGrid.querySelectorAll('.user-item');
    userItems.forEach(item => {
        const userId = parseInt(item.dataset.userId);
        const isSelected = selectedUsers.some(u => u.id_user === userId);
        item.classList.toggle('selected', isSelected);
    });
}

// Update Selected Users Display con X per rimozione
function updateSelectedUsersDisplay() {
    selectedUsersContainer.innerHTML = '';
    
    if (selectedUsers.length === 0) {
        selectedUsersContainer.innerHTML = '<em style="color: var(--text-muted);">Nessun partecipante selezionato</em>';
    } else {
        selectedUsers.forEach(user => {
            const chip = document.createElement('span');
            chip.className = 'user-chip';
            chip.innerHTML = `
                <span class="material-icons" style="font-size: 16px;">person</span>
                ${user.username}
                <span class="user-chip-remove" data-user-id="${user.id_user}">×</span>
            `;
            
            // Event listener per rimozione
            const removeBtn = chip.querySelector('.user-chip-remove');
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeUserSelection(user.id_user);
            });
            
            selectedUsersContainer.appendChild(chip);
        });
    }
    
    // Aggiorna stato bottoni - considera utenti disponibili
    const availableUsers = allUsers.filter(user => parseInt(user.params_count) < 2);
    startWheelBtn.disabled = selectedUsers.length < 2;
    selectAllBtn.disabled = selectedUsers.length === availableUsers.length || availableUsers.length === 0;
    clearAllBtn.disabled = selectedUsers.length === 0;
}

// Rimuovi utente selezionato
function removeUserSelection(userId) {
    selectedUsers = selectedUsers.filter(u => u.id_user !== userId);
    updateUsersGridDisplay();
    updateSelectedUsersDisplay();
}

// Seleziona tutti gli utenti
function selectAllUsers() {
    selectedUsers = allUsers.filter(u => parseInt(u.params_count) < 2);
    updateUsersGridDisplay();
    updateSelectedUsersDisplay();
    showToast('Tutti gli utenti disponibili selezionati', 'success');
}


// Deseleziona tutti gli utenti
function clearAllUsers(silent = false) {
    selectedUsers = [];
    updateUsersGridDisplay();
    updateSelectedUsersDisplay();
    if (!silent) {
        showToast('Selezione cancellata', 'success');
    }
}


// Check if all selected users have 2 parameters
async function checkAllUsersFullParams() {
    if (selectedUsers.length === 0) return false;
    
    try {
        const userIds = selectedUsers.map(u => u.id_user);
        const result = await apiCall('check-all-full', {
            method: 'POST',
            body: JSON.stringify({ user_ids: userIds })
        });
        
        return result.all_full;
    } catch (error) {
        console.error('Errore controllo parametri pieni:', error);
        return false;
    }
}

// Generate Wheel Colors
function getWheelColors(count) {
    const colors = [
        '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b',
        '#22c55e', '#ef4444', '#06b6d4', '#84cc16',
        '#f97316', '#a855f7', '#14b8a6', '#f43f5e',
        '#6366f1', '#10b981', '#f59e0b', '#8b5cf6'
    ];
    
    const result = [];
    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }
    return result;
}

function degToRad(deg) 
{ 
    return (deg - 90) * Math.PI / 180; 
}

function polar(cx, cy, r, deg) {
    const a = degToRad(deg);
    return { x: cx + r * Math.cos(a), y: cy + r * Math.sin(a) };
}

function describeDonutSlice(cx, cy, rOuter, rInner, startDeg, endDeg) {
    const largeArc = (endDeg - startDeg) > 180 ? 1 : 0;
    const p1 = polar(cx, cy, rOuter, startDeg);
    const p2 = polar(cx, cy, rOuter, endDeg);
    const p3 = polar(cx, cy, rInner, endDeg);
    const p4 = polar(cx, cy, rInner, startDeg);
    return `M ${p1.x} ${p1.y} A ${rOuter} ${rOuter} 0 ${largeArc} 1 ${p2.x} ${p2.y} L ${p3.x} ${p3.y} A ${rInner} ${rInner} 0 ${largeArc} 0 ${p4.x} ${p4.y} Z`;
}

// spezza in 1–2 righe (priorità sullo spazio, fallback hard split)
function wrapLabel(text, maxPerLine = 10) {
    if (text.length <= maxPerLine) return [text];
    const words = text.split(/\s+/);
    if (words.length > 1) {
        let line1 = '', line2 = '';
        for (const w of words) {
        if ((line1 + ' ' + w).trim().length <= maxPerLine) {
            line1 = (line1 ? line1 + ' ' : '') + w;
        } else {
            line2 = (line2 ? line2 + ' ' : '') + w;
        }
        }
        return [line1, line2].filter(Boolean);
    }
    // nessuno spazio: hard split
    return [text.slice(0, maxPerLine), text.slice(maxPerLine)];
}

const ANGLE_EPS = 0.001; // in gradi: minuscolo ma evita l’arco a 360° esatto

// Create Wheel - SENZA SPAZI NERI, segmenti perfetti
function createWheel() {
    wheel.innerHTML = '';

    if (!wheelParticipants || wheelParticipants.length === 0) {
        wheelStatus.innerHTML = '<em>Nessun partecipante attivo</em>';
        return;
    }

    const size = 520;
    const cx = size / 2, cy = cx;
    const rOuter = size * 0.46;
    const rInner = size * 0.16;
    const segmentAngle = 360 / wheelParticipants.length;
    const colors = getWheelColors(wheelParticipants.length);

    // font base: meno partecipanti => font più grande
    const baseFont = Math.max(10, Math.min(16, 18 - Math.ceil(wheelParticipants.length / 2)));
    const labelRadius = rOuter + 36; // più distanti => più leggibili

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', 'wheel-svg');
    svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
    svg.setAttribute('overflow', 'visible');
    svg.style.overflow = 'visible';


    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('id', 'wheelGroup');
    g.setAttribute('class', 'wheel-group');

    wheelParticipants.forEach((p, i) => {
        const n = wheelParticipants.length;
        const start = i * segmentAngle;
        // Evita che l'ultimo spicchio termini a 360° esatti
        let end = (i + 1) * segmentAngle;
        if (i === n - 1) end = 360 - ANGLE_EPS;

        const mid = start + (end - start) / 2;

        // 1) spicchio
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'slice');
        path.setAttribute('d', describeDonutSlice(cx, cy, rOuter, rInner, start, end));
        path.setAttribute('fill', colors[i % colors.length]);
        g.appendChild(path);

        // 2) leader line
        const pStart = polar(cx, cy, rOuter - 6, mid);
        const pEnd   = polar(cx, cy, rOuter + 22, mid);
        const leader = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        leader.setAttribute('class', 'leader');
        leader.setAttribute('x1', pStart.x); leader.setAttribute('y1', pStart.y);
        leader.setAttribute('x2', pEnd.x);   leader.setAttribute('y2', pEnd.y);
        g.appendChild(leader);

        // 3) etichetta esterna con wrap 1–2 righe
        const out = polar(cx, cy, labelRadius, mid);
        const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        label.setAttribute('class', 'label');
        label.setAttribute('x', out.x);
        label.setAttribute('y', out.y);

        const deg = (mid % 360 + 360) % 360;
        const anchor = (deg > 90 && deg < 270) ? 'end' : 'start';
        label.setAttribute('text-anchor', anchor);
        label.setAttribute('font-size', String(baseFont));

        const lines = wrapLabel(p.username, Math.max(8, Math.floor(baseFont * 0.8)));
        lines.forEach((ln, j) => {
        const tspan = document.createElementNS('http://www.w3.org/2000/svg', 'tspan');
        tspan.setAttribute('x', out.x);
        tspan.setAttribute('dy', j === 0 ? 0 : baseFont * 1.15);
        tspan.textContent = ln;
        label.appendChild(tspan);
        });

        // tooltip col nome completo
        const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
        title.textContent = p.username;
        label.appendChild(title);

        g.appendChild(label);
    });

    // mozzo
    const hub = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    hub.setAttribute('cx', cx); hub.setAttribute('cy', cy);
    hub.setAttribute('r', rInner - 6);
    hub.setAttribute('fill', 'var(--card-bg)');
    hub.setAttribute('stroke', 'var(--primary-color)');
    hub.setAttribute('stroke-width', '4');

    svg.appendChild(g);
    svg.appendChild(hub);
    wheel.appendChild(svg);

    wheelStatus.innerHTML = `<strong>${wheelParticipants.length}</strong> partecipanti attivi nella ruota`;
}



// Start Wheel - con controllo e rimozione utenti con 2 parametri
async function startWheel() {
    if (selectedUsers.length < 2) {
        showToast('Seleziona almeno 2 utenti per iniziare', 'warning');
        return;
    }
    
    // Ricarica dati utenti per controllo aggiornato
    await loadUsers();
    
    // Rimuovi utenti con 2 parametri dalla selezione corrente
    const validUsers = selectedUsers.filter(user => {
        const updatedUser = allUsers.find(u => u.id_user === user.id_user);
        return updatedUser && parseInt(updatedUser.params_count) < 2;
    });
    
    const removedCount = selectedUsers.length - validUsers.length;
    if (removedCount > 0) {
        selectedUsers = validUsers;
        updateUsersGridDisplay();
        updateSelectedUsersDisplay();
        showToast(`${removedCount} utenti con 2 parametri rimossi dalla selezione`, 'warning');
    }
    
    if (validUsers.length < 2) {
        showToast('Non ci sono abbastanza utenti disponibili per iniziare la ruota', 'error');
        return;
    }
    
    // Check finale
    const allFull = await checkAllUsersFullParams();
    if (allFull) {
        showToast('Tutti gli utenti selezionati hanno già 2 parametri', 'warning');
        return;
    }
    
    currentSessionStart = new Date().toISOString();
    wheelParticipants = [...validUsers];
    createWheel();
    
    spinBtn.disabled = false;
    startWheelBtn.disabled = true;
    cancelCurrentSessionBtn.disabled = false;
    usersGrid.style.pointerEvents = 'none';
    usersGrid.style.opacity = '0.6';
    selectAllBtn.disabled = true;
    clearAllBtn.disabled = true;
    
    showToast('Ruota avviata! Premi "Gira la Ruota" per la prima estrazione.', 'success');
}

// Spin Wheel - con controllo aggiornato parametri  
async function spinWheel() {
    if (isSpinning || wheelParticipants.length === 0) return;

    const wheelGroup = document.getElementById('wheelGroup');
    if (!wheelGroup) {
        showToast('Ruota non inizializzata', 'error');
        return;
    }

    isSpinning = true;
    spinBtn.disabled = true;
    spinBtn.classList.add('loading-state');
    spinBtn.innerHTML = '<div class="loading-spinner"></div> Girando...';
    wheelStatus.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;gap:.5rem;"><div class="loading-spinner"></div> Estrazione in corso...</div>';

    // Rotazione “casuale” come prima
    const spins = 5 + Math.random() * 5;
    const finalAngle = Math.random() * 360;
    currentRotation += spins * 360 + finalAngle;

    // Applica la rotazione al gruppo SVG
    wheelGroup.style.transform = `rotate(${currentRotation}deg)`;

    setTimeout(async () => {
        const segmentAngle = 360 / wheelParticipants.length;
        // Angolo normalizzato rispetto al puntatore a ore 12
        const normalizedAngle = (360 - (currentRotation % 360)) % 360;
        const winnerIndex = Math.floor(normalizedAngle / segmentAngle);
        const winner = wheelParticipants[winnerIndex];

        try {
        const response = await apiCall('spin', {
            method: 'POST',
            body: JSON.stringify({
            id_user: winner.id_user,
            session_start: currentSessionStart,
            csrf_token: csrfToken
            })
        });

        if (response.error) {
            showToast(response.error, 'error');
        } else if (response.success) {
            showToast(`${winner.username} è stato estratto e ha ricevuto un parametro segreto!`, 'success');

            addResult(winner);
            wheelParticipants = wheelParticipants.filter(p => p.id_user !== winner.id_user);

            // Ricarica contatori utenti e refresh griglia selezione
            await loadUsers();
            updateUsersGridDisplay();
            updateSelectedUsersDisplay();

            if (wheelParticipants.length > 0) {
            createWheel();
            wheelStatus.innerHTML = `Estrazione completata! Pronto per la prossima (${wheelParticipants.length} rimasti)`;
            } else {
            wheelStatus.innerHTML = '<strong style="color: var(--success-color);">Tutte le estrazioni completate!</strong>';
            showToast('Tutte le estrazioni completate! Parametri assegnati segretamente.', 'success');
            resetUIAfterCompletion();
            }
        }
        } catch (error) {
        console.error('Errore spin API:', error);
        showToast('Errore durante l\'estrazione', 'error');
        }

        isSpinning = false;
        spinBtn.disabled = wheelParticipants.length === 0;
        spinBtn.classList.remove('loading-state');
        spinBtn.innerHTML = '<span class="material-icons">rotate_right</span> Gira la Ruota';
        clearAllUsers(true); 
    }, 4000);
}


// Reset UI after completion
function resetUIAfterCompletion() {
        clearAllUsers(true);

    usersGrid.style.pointerEvents = 'auto';
    usersGrid.style.opacity = '1';
    startWheelBtn.disabled = false;
    selectAllBtn.disabled = selectedUsers.length === allUsers.length;
    clearAllBtn.disabled = selectedUsers.length === 0;
    currentSessionStart = null;
    cancelCurrentSessionBtn.disabled = true;
}

// Annulla sessione corrente - SOLO utenti della ruota attuale
async function cancelCurrentSession() {
    if (!currentSessionStart) {
        showToast('Nessuna sessione attiva da annullare', 'warning');
        return;
    }
    cancelSessionDialog.classList.add('show');
}


async function confirmCancelCurrentSession() {
    try {
        const response = await apiCall('cancel-current-session', {
        method: 'POST',
        body: JSON.stringify({
            conferma: true,
            session_id: currentSessionStart,   // ← passa l’ID della sessione corrente
            csrf_token: csrfToken
        })
        });

        if (response.success) {
            // rimuovi SOLO i risultati della sessione corrente
            [...document.querySelectorAll('.result-card')].forEach(card => {
                if (card.dataset.sessionId === currentSessionStart) {
                    card.remove();
                }
            });

            await refreshAllUI({
                clearResults: false, // mantieni eventuali risultati di sessioni precedenti
                toastMessage: `Sessione annullata (${response.deleted_count} assegnazioni rimosse)`
            });
        } else {
        showToast(response.error || 'Errore durante l\'annullamento', 'error');
        }
    } catch (error) {
        console.error('Errore cancel session:', error);
        showToast('Errore durante l\'annullamento', 'error');
    }
    cancelSessionDialog.classList.remove('show');
}


// Add Result to UI - SENZA mostrare il parametro (segreto)
function addResult(winner) {
    const resultCard = document.createElement('div');
    resultCard.className = 'result-card';
    resultCard.dataset.sessionId = currentSessionStart;
    resultCard.innerHTML = `
        <div class="result-header">
            <div class="result-avatar">
                ${winner.username.charAt(0).toUpperCase()}
            </div>
            <div class="result-info">
                <h5>${winner.username}</h5>
                <div class="result-status">✅ Parametro assegnato segretamente</div>
            </div>
        </div>
        <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; font-style: italic; margin-top: 0.5rem;">
            Estratto alle ${new Date().toLocaleTimeString()}
        </div>
    `;
    
    results.appendChild(resultCard);
    
    // Animazione entrata
    setTimeout(() => {
        resultCard.style.transform = 'translateY(0)';
        resultCard.style.opacity = '1';
    }, 100);
}

// Reset Assignments (tutte)
async function resetAssignments() {
    resetDialog.classList.add('show');
}

async function confirmReset() {
    try {
        const response = await apiCall('reset-assegnazioni', {
            method: 'POST',
            body: JSON.stringify({
                conferma: true,
                csrf_token: csrfToken
            })
        });
        
        if (response.success) {
            await refreshAllUI({
                clearResults: true,              // svuota tutta la griglia risultati
                toastMessage: 'Reset eseguito'   // messaggio conferma
            });
        } else {
            showToast(response.error || 'Errore durante il reset', 'error');
        }
    } catch (error) {
        console.error('Errore reset:', error);
        showToast('Errore durante il reset', 'error');
    }
    
    resetDialog.classList.remove('show');
}

// Event Listeners - AGGIORNATI per nuovo sistema
function setupEventListeners() {
    // Bottoni selezione
    selectAllBtn.addEventListener('click', selectAllUsers);
    clearAllBtn.addEventListener('click', clearAllUsers);
    
    // Bottoni ruota
    startWheelBtn.addEventListener('click', startWheel);
    spinBtn.addEventListener('click', spinWheel);
    cancelCurrentSessionBtn.addEventListener('click', cancelCurrentSession);
    resetAssignmentsBtn.addEventListener('click', resetAssignments);
    
    // Dialog reset tutte
    confirmResetBtn.addEventListener('click', confirmReset);
    cancelResetBtn.addEventListener('click', () => resetDialog.classList.remove('show'));
    
    // Dialog cancel sessione
    confirmCancelSessionBtn.addEventListener('click', confirmCancelCurrentSession);
    cancelCancelSessionBtn.addEventListener('click', () => cancelSessionDialog.classList.remove('show'));
    
    // Close dialogs on overlay click
    resetDialog.addEventListener('click', (e) => {
        if (e.target === resetDialog) {
            resetDialog.classList.remove('show');
        }
    });
    
    cancelSessionDialog.addEventListener('click', (e) => {
        if (e.target === cancelSessionDialog) {
            cancelSessionDialog.classList.remove('show');
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    setupEventListeners();
    
    // Theme management
    if (typeof ThemeManager !== 'undefined') {
        const tm = new ThemeManager();
        tm.init();
    }
    
    // Intersection Observer per animazioni uniformi
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, index * 150);
            }
        });
    }, observerOptions);
    
    // Osserva tutti gli elementi con animazione
    document.querySelectorAll('.fade-in-up').forEach(el => {
        observer.observe(el);
    });
    
    // console.log('🎰 Ruota della Fortuna caricata con TUTTI i miglioramenti richiesti!');
    // console.log('✓ Ruota migliorata per 6-12 partecipanti');
    // console.log('✓ Sistema selezione click-based');
    // console.log('✓ Bottoni Seleziona/Deseleziona tutti');
    // console.log('✓ Annullamento sessione corrente');
});