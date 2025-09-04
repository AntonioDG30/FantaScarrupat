/**
 * Monitor sessione client-side con avvisi
 * VERSIONE CORRETTA: Heartbeat non resetta il timer
 */
class SessionMonitor {
    constructor() {
        this.idleWarningTime = 12 * 60 * 1000;  // Avviso a 12 minuti
        this.idleTimeout = 15 * 60 * 1000;       // Timeout a 15 minuti
        this.heartbeatInterval = 30 * 1000;      // Heartbeat ogni 30 secondi
        this.lastActivity = Date.now();
        this.warningShown = false;
        this.sessionExpired = false;
        this.checkInterval = null;
    }
    
    init() {
        // console.log('[SessionMonitor] Inizializzazione...');
        
        // Traccia attività utente REALE (non heartbeat)
        ['mousedown', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, () => this.onUserActivity(), true);
        });
        
        // Heartbeat periodico (solo verifica, non resetta)
        this.heartbeatInterval = setInterval(() => this.sendHeartbeat(), this.heartbeatInterval);
        
        // Check idle timeout
        this.checkInterval = setInterval(() => this.checkIdleTimeout(), 5000);
        
        // console.log('[SessionMonitor] Inizializzato con successo');
    }
    
    onUserActivity() {
        // Solo le azioni reali dell'utente resettano il timer
        const now = Date.now();
        
        // Se sono passati almeno 5 secondi dall'ultima attività registrata
        if (now - this.lastActivity > 5000) {
            this.lastActivity = now;
            this.warningShown = false;
            this.hideWarning();
            
            // Informa il server dell'attività reale
            this.updateServerActivity();
            
            // console.log('[SessionMonitor] Attività utente registrata');
        }
    }
    
    async updateServerActivity() {
        try {
            const response = await fetch(url('api/auth.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ action: 'update_activity' })
            });
            
            const data = await response.json();
            // console.log('[SessionMonitor] Attività aggiornata sul server:', data);
            
        } catch (error) {
            console.error('[SessionMonitor] Errore aggiornamento attività:', error);
        }
    }
    
    checkIdleTimeout() {
        if (this.sessionExpired) return;
        
        const now = Date.now();
        const idleTime = now - this.lastActivity;
        
        // console.log(`[SessionMonitor] Idle time: ${Math.floor(idleTime/1000)}s`);
        
        if (idleTime > this.idleTimeout) {
            // Timeout raggiunto
            // console.log('[SessionMonitor] TIMEOUT raggiunto! Eseguo logout...');
            this.handleTimeout();
        } else if (idleTime > this.idleWarningTime && !this.warningShown) {
            // Mostra avviso
            this.showWarning();
        }
    }
    
    showWarning() {
        this.warningShown = true;
        const remaining = Math.ceil((this.idleTimeout - (Date.now() - this.lastActivity)) / 60000);
        
        // console.log(`[SessionMonitor] Mostro avviso: ${remaining} minuti rimanenti`);
        
        // Rimuovi warning esistente
        this.hideWarning();
        
        const warning = document.createElement('div');
        warning.id = 'session-warning';
        warning.className = 'session-warning';
        warning.innerHTML = `
            <div class="warning-content">
                <span class="material-icons">warning</span>
                <span>La sessione scadrà tra ${remaining} minuti per inattività</span>
                <button class="btn-continue">Continua</button>
            </div>
        `;
        document.body.appendChild(warning);
        
        // Gestisci click su "Continua"
        const btnContinue = warning.querySelector('.btn-continue');
        if (btnContinue) {
            btnContinue.addEventListener('click', () => {
                // console.log('[SessionMonitor] Utente ha cliccato Continua');
                this.onUserActivity(); // Resetta timer
            });
        }
        
        // Aggiungi stili se non esistono
        if (!document.getElementById('session-warning-styles')) {
            const style = document.createElement('style');
            style.id = 'session-warning-styles';
            style.textContent = `
                .session-warning {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    background: linear-gradient(135deg, #f59e0b, #ea580c);
                    color: white;
                    padding: 16px 20px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(245, 158, 11, 0.3);
                    animation: slideIn 0.3s ease;
                    max-width: 400px;
                }
                
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                .warning-content {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                
                .warning-content .material-icons {
                    font-size: 24px;
                }
                
                .warning-content button {
                    margin-left: auto;
                    padding: 6px 16px;
                    background: white;
                    color: #ea580c;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s;
                }
                
                .warning-content button:hover {
                    transform: scale(1.05);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    hideWarning() {
        const warning = document.getElementById('session-warning');
        if (warning) {
            warning.remove();
        }
    }
    
    async sendHeartbeat() {
        if (this.sessionExpired) return;
        
        try {
            const response = await fetch(url('api/auth.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ action: 'heartbeat' })
            });
            
            const data = await response.json();
            
            /** 
                console.log('[SessionMonitor] Heartbeat response:', {
                    valid: data.data?.session_valid,
                    remaining: data.data?.remaining_idle
                });
            */
            
            if (!data.success || !data.data?.session_valid) {
                // console.log('[SessionMonitor] Sessione non valida dal server, eseguo logout');
                this.handleTimeout();
            } else {
                // Mostra tempo rimanente nella console
                const remainingSec = data.data?.remaining_idle || 0;
                if (remainingSec < 180) { // Meno di 3 minuti
                    console.warn(`[SessionMonitor] ⚠️ Sessione scade tra ${Math.floor(remainingSec/60)} minuti`);
                }
            }
        } catch (error) {
            console.error('[SessionMonitor] Heartbeat failed:', error);
        }
    }
    
    async handleTimeout() {
        if (this.sessionExpired) return;
        this.sessionExpired = true;
        
        // console.log('[SessionMonitor] Esecuzione timeout handler...');
        
        // Ferma tutti gli intervalli
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
        
        // Nascondi warning
        this.hideWarning();
        
        // Invia logout al server
        try {
            await fetch(url('api/auth.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ action: 'logout' })
            });
        } catch (error) {
            console.error('[SessionMonitor] Errore durante logout:', error);
        }
        
        // Redirect a login con messaggio
        // console.log('[SessionMonitor] Redirect a login...');
        window.location.href = url('login.php?expired=1');
    }
    
    destroy() {
        // console.log('[SessionMonitor] Distruzione monitor...');
        
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
        }
        
        this.hideWarning();
    }
}

// Inizializza su tutte le pagine protette
let sessionMonitor = null;

document.addEventListener('DOMContentLoaded', () => {
    // Verifica che siamo in una pagina protetta (con csrfToken definito)
    if (typeof csrfToken !== 'undefined') {
        sessionMonitor = new SessionMonitor();
        sessionMonitor.init();
        
        // Esporta globalmente per debug
        window.sessionMonitor = sessionMonitor;
    }
});