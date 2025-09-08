/**
 * Sistema di gestione cache condiviso
 * Utilizzato sia da CheckMyTeam che da FindPlayerByParametri
 */

class CacheManager {
    constructor() {
        this.isAdmin = window.CURRENT_USER?.is_admin || false;
        this.bannerDismissed = false;
    }

    /**
     * Inizializza il sistema verificando lo stato della cache
     */
    async initialize() {
        try {
            const status = await this.checkCacheStatus();
            
            if (!status.exists) {
                this.showCacheMissingState();
                return false;
            }
            
            if (status.status === 'corrupted') {
                this.showCacheCorruptedState();
                return false;
            }
            
            // Cache valida - mostra banner informativo per admin
            if (this.isAdmin && !this.bannerDismissed) {
                this.showAdminInfoBanner(status);
            }
            
            // Carica dati dalla cache
            await this.loadFromCache();
            
            return true;
            
        } catch (error) {
            console.error('Cache initialization error:', error);
            this.showErrorState('Errore di connessione. Ricaricare la pagina.');
            return false;
        }
    }

    /**
     * Verifica stato della cache
     */
    async checkCacheStatus() {
        const response = await fetch('api/data.php?action=cache_status', {
            headers: { 'X-CSRF-Token': this.getCsrfToken() }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Cache status check failed');
        }
        
        return data.cache_status;
    }

    /**
     * Carica dati dalla cache
     */
    async loadFromCache() {
        const response = await fetch('api/data.php?action=load_from_cache', {
            headers: { 'X-CSRF-Token': this.getCsrfToken() }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Cache load failed');
        }
        
        // Marca i dati come caricati
        this.markDataAsLoaded();
        
        // Imposta flag globale per CheckMyTeam
        window._SESSION_DATA_LOADED = true;
        
        // console.log('Cache loaded successfully:', data.stats);
        return data;
    }

    /**
     * Rigenera cache (solo admin)
     */
    async rebuildCache() {
        if (!this.isAdmin) {
            this.showMessage('Accesso negato: solo amministratori', 'danger');
            return;
        }
        
        // Conferma azione
        if (!confirm('Rigenerare la cache?\n\nL\'operazione può richiedere alcuni minuti e ricaricherà la pagina.')) {
            return;
        }
        
        // console.log('Starting cache rebuild...');
        
        // Mostra loading
        this.showMessage('Rigenerazione cache in corso...', 'info');
        this.disableInterface();
        
        try {
            // console.log('Calling rebuild API...');
            
            const response = await fetch('api/data.php?action=rebuild_cache', {
                method: 'POST',
                headers: { 
                    'X-CSRF-Token': this.getCsrfToken(),
                    'Content-Type': 'application/json'
                }
            });
            
            // console.log('API Response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error response:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const data = await response.json();
            // console.log('API Response data:', data);
            
            if (data.success) {
                this.showMessage('Cache rigenerata con successo! Ricaricamento pagina...', 'success');
                
                // Ricarica la pagina per applicare i nuovi dati
                setTimeout(() => {
                    // console.log('Reloading page...');
                    location.reload();
                }, 2000);
                
            } else {
                console.error('Rebuild failed:', data);
                
                if (data.backup_restored) {
                    this.showMessage(data.message || 'Cache ripristinata dal backup', 'warning');
                } else {
                    this.showMessage(data.message || 'Errore durante la rigenerazione', 'danger');
                }
                this.enableInterface();
            }
            
        } catch (error) {
            console.error('Cache rebuild error:', error);
            this.showMessage('Errore durante la rigenerazione: ' + error.message, 'danger');
            this.enableInterface();
        }
    }

    /**
     * Mostra stato cache mancante
     */
    showCacheMissingState() {
        const message = this.isAdmin 
            ? 'Cache assente. Utilizzare i comandi della navbar per rigenerare.'
            : 'Dati non disponibili. Contattare l\'amministratore.';
            
        this.showPersistentOverlay('info', 'Cache Assente', message, this.isAdmin);
        this.disableInterface();
    }

    /**
     * Mostra stato cache corrotta
     */
    showCacheCorruptedState() {
        const message = this.isAdmin
            ? 'Cache corrotta. Rigenerazione necessaria.'
            : 'Errore nei dati. Contattare l\'amministratore.';
            
        this.showPersistentOverlay('warning', 'Cache Corrotta', message, this.isAdmin);
        this.disableInterface();
    }

    /**
     * Mostra stato di errore generico
     */
    showErrorState(message) {
        this.showPersistentOverlay('error', 'Errore', message, false);
        this.disableInterface();
    }

    /**
     * Banner informativo per admin
     */
    showAdminInfoBanner(status) {
        if (sessionStorage.getItem('admin_cache_banner_dismissed') === 'true') {
            return;
        }
        
        const existingBanner = document.getElementById('adminCacheBanner');
        if (existingBanner) {
            existingBanner.remove();
        }
        
        const banner = document.createElement('div');
        banner.id = 'adminCacheBanner';
        banner.className = 'admin-cache-banner';
        
        let statusText = `Cache presente (età: ${status.age_formatted || 'sconosciuta'})`;
        if (status.suggestion) {
            statusText += ` - ${status.suggestion}`;
        }
        
        banner.innerHTML = `
            <div class="banner-content">
                <span class="material-icons">info</span>
                <span class="banner-text">${statusText}</span>
                <button class="banner-close" onclick="cacheManager.dismissAdminBanner()">
                    <span class="material-icons">close</span>
                </button>
            </div>
        `;
        
        document.body.appendChild(banner);
        
        // Auto-dismiss dopo 10 secondi se non c'è suggestion importante
        if (!status.suggestion) {
            setTimeout(() => {
                if (document.getElementById('adminCacheBanner')) {
                    this.dismissAdminBanner();
                }
            }, 10000);
        }
    }

    /**
     * Chiude banner admin
     */
    dismissAdminBanner() {
        const banner = document.getElementById('adminCacheBanner');
        if (banner) {
            banner.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            banner.style.opacity = '0';
            banner.style.transform = 'translateY(-100%)';
            
            setTimeout(() => banner.remove(), 300);
            sessionStorage.setItem('admin_cache_banner_dismissed', 'true');
            this.bannerDismissed = true;
        }
    }

    /**
     * Overlay persistente per stati critici
     */
    showPersistentOverlay(type, title, message, showAction = false) {
        const overlay = document.createElement('div');
        overlay.id = 'persistentOverlay';
        overlay.className = 'persistent-overlay';
        
        const iconMap = {
            'info': 'info',
            'warning': 'warning', 
            'error': 'error'
        };
        
        const colorMap = {
            'info': '#3b82f6',
            'warning': '#f59e0b',
            'error': '#ef4444'
        };
        
        overlay.innerHTML = `
            <div class="overlay-content">
                <span class="material-icons overlay-icon" style="color: ${colorMap[type]}">${iconMap[type]}</span>
                <h2 class="overlay-title">${title}</h2>
                <p class="overlay-message">${message}</p>
                <div class="overlay-actions">
                    ${showAction ? `
                        <button class="btn-action btn-primary" onclick="cacheManager.rebuildCache()">
                            <span class="material-icons">refresh</span>
                            Rigenera Cache
                        </button>
                    ` : ''}
                    <button class="btn-action btn-secondary" onclick="location.reload()">
                        <span class="material-icons">refresh</span>
                        Ricarica Pagina
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
    }

    /**
     * Marca i dati come caricati
     */
    markDataAsLoaded() {
        const badge = document.getElementById('statusBadge');
        if (badge) {
            badge.classList.remove('not-loaded');
            badge.classList.add('loaded');
            badge.innerHTML = '<span class="material-icons">check_circle</span><span>Dati caricati</span>';
        }
    }

    /**
     * Disabilita interfaccia
     */
    disableInterface() {
        // Disabilita solo i controlli della pagina principale, NON la navbar
        const appControls = document.querySelectorAll(`
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
        
        appControls.forEach(el => {
            el.disabled = true;
            el.style.opacity = '0.5';
        });
        
        // console.log('App interface disabled, navbar controls remain active');
    }

    /**
     * Disabilita interfaccia ma mantiene attivi i controlli admin (stesso comportamento di disableInterface)
     */
    disableInterfaceExceptAdmin() {
        // Per compatibilità, usa la stessa logica di disableInterface
        this.disableInterface();
    }

    /**
     * Abilita interfaccia
     */
    enableInterface() {
        // Re-abilita tutti i controlli dell'app
        const appControls = document.querySelectorAll(`
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
        
        appControls.forEach(el => {
            el.disabled = false;
            el.style.opacity = '1';
        });
        
        // console.log('App interface enabled');
    }

    /**
     * Mostra messaggio toast
     */
    showMessage(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast-message toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Ottiene token CSRF
     */
    getCsrfToken() {
        const token = window.csrfToken || 
                     window.csrfToken ||
                     document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     '';
        
        if (!token) {
            console.warn('CSRF token not found! This may cause API calls to fail.');
        }
        
        return token;
    }

    /**
     * Informazioni cache (per admin)
     */
    async getCacheInfo() {
        if (!this.isAdmin) return;
        
        try {
            const response = await fetch('api/data.php?action=cache_info', {
                headers: { 'X-CSRF-Token': this.getCsrfToken() }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const info = data.cache_info;
                let message = `📋 Stato Cache\n\n`;
                
                if (info.exists) {
                    message += `✅ Cache presente\n`;
                    message += `📅 Creata: ${info.built_at_formatted}\n`;
                    message += `⏰ Età: ${info.age_formatted}\n`;
                    message += `📊 Giocatori: ${info.build_stats?.total_players || '?'}\n`;
                    message += `🌐 API Teams: ${info.build_stats?.api_teams || '?'}`;
                } else {
                    message += `❌ Nessuna cache presente`;
                }
                
                alert(message);
            } else {
                this.showMessage('Errore nel recupero info cache', 'warning');
            }
        } catch (e) {
            this.showMessage('Errore di rete', 'warning');
        }
    }
}

// Istanza globale
window.cacheManager = new CacheManager();