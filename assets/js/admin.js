/**
 * Admin Panel JavaScript - Refactored
 * Sistema unificato per gestione pannello amministrativo
 */

class AdminPanel {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.charts = {};
        this.dataTables = {};
        this.statsInterval = null;
        this.init();
    }
    
    init() {
        this.initTheme();
        this.initSidebar();
        this.initTabs();
        this.initForms();
        this.initActions();
        this.initDataTables();
        
        // Inizializza in base al tab attivo
        const activeTab = new URLSearchParams(window.location.search).get('tab') || 'dashboard';
        if (activeTab === 'dashboard') {
            this.initDashboard();
        }
    }
    
    /**
     * Inizializza il theme manager
     */
    initTheme() {
        if (typeof ThemeManager !== 'undefined') {
            const tm = new ThemeManager();
            tm.init();
        }
    }
    
    /**
     * Gestione sidebar responsive
     */
    initSidebar() {
        // Mobile menu toggle
        const createMobileToggle = () => {
            if (window.innerWidth <= 1024) {
                let toggle = document.querySelector('.menu-toggle');
                if (!toggle) {
                    toggle = document.createElement('button');
                    toggle.className = 'menu-toggle';
                    toggle.innerHTML = '<span class="material-icons">menu</span>';
                    document.body.appendChild(toggle);
                    
                    toggle.addEventListener('click', () => {
                        const sidebar = document.querySelector('.admin-sidebar');
                        sidebar?.classList.toggle('active');
                        
                        let overlay = document.querySelector('.sidebar-overlay');
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.className = 'sidebar-overlay';
                            document.body.appendChild(overlay);
                            overlay.addEventListener('click', () => {
                                sidebar?.classList.remove('active');
                                overlay.classList.remove('active');
                            });
                        }
                        overlay.classList.toggle('active');
                    });
                }
            }
        };
        
        createMobileToggle();
        window.addEventListener('resize', createMobileToggle);
    }
    
    /**
     * Inizializza tabs
     */
    initTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tabId = btn.dataset.tab;
                const container = btn.closest('.admin-main');
                
                // Rimuovi active da tutti
                container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Aggiungi active al cliccato
                btn.classList.add('active');
                const content = document.getElementById(tabId);
                if (content) {
                    content.classList.add('active');
                    
                    // Reinizializza DataTable se necessario
                    const table = content.querySelector('table[id$="Table"]');
                    if (table && !$.fn.DataTable.isDataTable(table)) {
                        this.initDataTable(table.id);
                    }
                }
            });
        });
    }
    
    /**
     * Inizializza tutti i form con gestione AJAX
     */
    initForms() {
        // Form Calciatori
        const formCalciatori = document.getElementById('formCalciatori');
        if (formCalciatori) {
            formCalciatori.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formCalciatori);
                formData.append('action', 'uploadCalciatori');
                formData.append('csrf_token', this.csrfToken);
                
                await this.submitFormAjax(formData, 'Calciatori caricati con successo');
            });
        }
        
        // Form Partecipante
        const formPartecipante = document.getElementById('formPartecipante');
        if (formPartecipante) {
            formPartecipante.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formPartecipante);
                formData.append('action', 'addPartecipante');
                formData.append('csrf_token', this.csrfToken);
                
                await this.submitFormAjax(formData, 'Partecipante aggiunto con successo');
            });
        }
        
        // Form Parametro
        const formParametro = document.getElementById('formParametro');
        if (formParametro) {
            formParametro.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formParametro);
                formData.append('action', 'addParametro');
                formData.append('csrf_token', this.csrfToken);
                
                await this.submitFormAjax(formData, 'Parametro aggiunto con successo');
            });
        }
        
        // Form Foto
        const formFoto = document.getElementById('formFoto');
        if (formFoto) {
            formFoto.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(formFoto);
                formData.append('action', 'uploadFoto');
                formData.append('csrf_token', this.csrfToken);
                
                await this.submitFormAjax(formData, 'Foto caricata con successo');
            });
        }
        
        // Preview immagini
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.previewImage(file, e.target);
                }
            });
        });
    }
    
    /**
     * Inizializza azioni (toggle, delete, etc)
     */
    initActions() {
        // Toggle Partecipante
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.toggle-partecipante')) {
                const btn = e.target.closest('.toggle-partecipante');
                const nome = btn.dataset.nome;
                
                const formData = new FormData();
                formData.append('action', 'togglePartecipante');
                formData.append('csrf_token', this.csrfToken);
                formData.append('nome', nome);
                
                await this.submitFormAjax(formData, 'Stato aggiornato');
            }
            
            // Toggle Parametro
            if (e.target.closest('.toggle-parametro')) {
                const btn = e.target.closest('.toggle-parametro');
                const id = btn.dataset.id;
                
                const formData = new FormData();
                formData.append('action', 'toggleParametro');
                formData.append('csrf_token', this.csrfToken);
                formData.append('id', id);
                
                await this.submitFormAjax(formData, 'Visibilità aggiornata');
            }
            
            // Toggle Foto
            if (e.target.closest('.toggle-foto')) {
                const btn = e.target.closest('.toggle-foto');
                const id = btn.dataset.id;
                
                const formData = new FormData();
                formData.append('action', 'toggleFoto');
                formData.append('csrf_token', this.csrfToken);
                formData.append('id', id);
                
                await this.submitFormAjax(formData, 'Visibilità aggiornata');
            }
        });
    }
    
    /**
     * Inizializza DataTables
     */
    initDataTables() {
        const commonConfig = {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
            },
            pageLength: 25,
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf']
        };
        
        // Calciatori
        if (document.getElementById('calciatoriTable')) {
            this.dataTables.calciatori = $('#calciatoriTable').DataTable({
                ...commonConfig,
                order: [[0, 'desc'], [1, 'asc']]
            });
        }
        
        // Partecipanti
        if (document.getElementById('partecipantiTable')) {
            this.dataTables.partecipanti = $('#partecipantiTable').DataTable({
                ...commonConfig,
                columnDefs: [{ orderable: false, targets: -1 }]
            });
        }
        
        // Parametri
        if (document.getElementById('parametriTable')) {
            this.dataTables.parametri = $('#parametriTable').DataTable({
                ...commonConfig,
                order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: -1 }]
            });
        }
        
        // Gallery
        if (document.getElementById('galleryTable')) {
            this.dataTables.gallery = $('#galleryTable').DataTable({
                ...commonConfig,
                columnDefs: [
                    { orderable: false, targets: [0, -1] }
                ]
            });
        }
    }
    
    /**
     * Inizializza DataTable specifica
     */
    initDataTable(tableId) {
        const commonConfig = {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
            },
            pageLength: 25,
            responsive: true
        };
        
        const table = document.getElementById(tableId);
        if (table && !$.fn.DataTable.isDataTable(table)) {
            $(table).DataTable(commonConfig);
        }
    }
    
    /**
     * Inizializza dashboard con statistiche e grafici
     */
    async initDashboard() {
        await this.loadStats();
        this.initCharts();
        this.loadActivityLog();
        
        // Update periodico stats
        this.statsInterval = setInterval(() => {
            this.updateActiveUsers();
            this.updateTopPages();
        }, 5000);
        
        // Cleanup on page change
        window.addEventListener('beforeunload', () => {
            if (this.statsInterval) {
                clearInterval(this.statsInterval);
            }
        });
    }
    
    /**
     * Carica log attività
     */
    loadActivityLog() {
        const logContainer = document.getElementById('activityLog');
        if (!logContainer) return;
        
        const logs = JSON.parse(localStorage.getItem('adminActivityLog') || '[]');
        const recentLogs = logs.slice(-10).reverse();
        
        if (recentLogs.length === 0) {
            logContainer.innerHTML = '<p class="text-muted text-center">Nessuna attività recente</p>';
            return;
        }
        
        logContainer.innerHTML = recentLogs.map(log => `
            <div class="log-entry p-2 border-bottom">
                <div class="d-flex justify-content-between">
                    <strong>${log.action}</strong>
                    <small class="text-muted">${new Date(log.timestamp).toLocaleString('it-IT')}</small>
                </div>
                <small class="text-muted">${log.details || ''} - ${log.user}</small>
            </div>
        `).join('');
    }
    
    /**
     * Pulisci log attività
     */
    clearActivityLog() {
        if (confirm('Eliminare tutto il log attività?')) {
            localStorage.removeItem('adminActivityLog');
            this.loadActivityLog();
            this.showMessage('Log attività pulito', 'success');
        }
    }
    
    /**
     * Update top pages
     */
    async updateTopPages() {
        try {
            const formData = new FormData();
            formData.append('action', 'getStats');
            formData.append('csrf_token', this.csrfToken);
            
            const response = await fetch('admin.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success && data.stats.topPages) {
                const container = document.getElementById('topPagesToday');
                if (container) {
                    container.innerHTML = data.stats.topPages.slice(0, 5).map(page => `
                        <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                            <small>${page.page_url.split('/').pop() || 'Home'}</small>
                            <span class="badge badge-info">${page.views}</span>
                        </div>
                    `).join('');
                }
            }
        } catch (error) {
            console.error('Errore update top pages:', error);
        }
    }
    
    /**
     * Export database
     */
    async exportDatabase() {
        if (!confirm('Vuoi esportare il backup completo del database?')) return;
        
        this.showMessage('Preparazione backup in corso...', 'info');
        
        try {
            const formData = new FormData();
            formData.append('action', 'exportDatabase');
            formData.append('csrf_token', this.csrfToken);
            
            const response = await fetch('admin.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `backup_${new Date().toISOString().split('T')[0]}.sql`;
                a.click();
                URL.revokeObjectURL(url);
                
                this.showMessage('Backup database completato', 'success');
                this.logActivity('Backup database', 'Export completato');
            }
        } catch (error) {
            console.error('Errore export database:', error);
            this.showMessage('Errore durante il backup', 'danger');
        }
    }
    
    /**
     * Log attività
     */
    logActivity(action, details = '') {
        const log = {
            action,
            details,
            timestamp: new Date().toISOString(),
            user: document.querySelector('.user-name')?.textContent || 'Admin'
        };
        
        const logs = JSON.parse(localStorage.getItem('adminActivityLog') || '[]');
        logs.push(log);
        
        // Mantieni solo ultimi 100 log
        if (logs.length > 100) logs.shift();
        localStorage.setItem('adminActivityLog', JSON.stringify(logs));
        
        // Aggiorna vista se siamo nella dashboard
        if (window.location.search.includes('dashboard') || !window.location.search.includes('tab')) {
            this.loadActivityLog();
        }
    }
    
    /**
     * Carica statistiche
     */
    async loadStats() {
        try {
            const formData = new FormData();
            formData.append('action', 'getStats');
            formData.append('csrf_token', this.csrfToken);
            
            const response = await fetch('admin.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                this.renderStats(data.stats);
                window.visitorsData = data.stats.visitors;
            }
        } catch (error) {
            console.error('Errore caricamento stats:', error);
        }
    }
    
    /**
     * Renderizza statistiche
     */
    renderStats(stats) {
        const statsGrid = document.getElementById('statsGrid');
        if (!statsGrid) return;
        
        const monthName = new Date().toLocaleDateString('it-IT', { month: 'long' });
        
        statsGrid.innerHTML = `
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">people</span>
                </div>
                <div class="stat-value">${stats.uniqueVisitors.toLocaleString()}</div>
                <div class="stat-label">Visitatori Unici ${monthName}</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">visibility</span>
                </div>
                <div class="stat-value">${stats.totalViews.toLocaleString()}</div>
                <div class="stat-label">Pagine Visitate</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">equalizer</span>
                </div>
                <div class="stat-value">${stats.avgDaily}</div>
                <div class="stat-label">Media Giornaliera</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">trending_up</span>
                </div>
                <div class="stat-value" id="activeUsersCard">0</div>
                <div class="stat-label">Utenti Attivi Ora</div>
            </div>
        `;
    }
    
    /**
     * Inizializza grafici
     */
    initCharts() {
        // Grafico visitatori
        const visitorsCtx = document.getElementById('visitorsChart');
        if (visitorsCtx && window.visitorsData) {
            this.charts.visitors = new Chart(visitorsCtx, {
                type: 'line',
                data: {
                    labels: this.getLast30Days(),
                    datasets: [{
                        label: 'Visitatori',
                        data: window.visitorsData,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(255, 255, 255, 0.9)',
                            titleColor: '#1f2937',
                            bodyColor: '#6b7280',
                            borderColor: '#e5e7eb',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
        
        // Grafico utenti attivi
        const activeUsersCtx = document.getElementById('activeUsersChart');
        if (activeUsersCtx) {
            this.charts.activeUsers = new Chart(activeUsersCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Utenti attivi',
                        data: [],
                        backgroundColor: 'rgba(139, 92, 246, 0.6)',
                        borderColor: 'rgba(139, 92, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
            
            // Primo update
            this.updateActiveUsers();
        }
    }
    
    /**
     * Aggiorna utenti attivi
     */
    async updateActiveUsers() {
        try {
            const formData = new FormData();
            formData.append('action', 'getActiveUsers');
            formData.append('csrf_token', this.csrfToken);
            
            const response = await fetch('admin.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                // Update contatori
                const counter = document.getElementById('activeUsersCount');
                const card = document.getElementById('activeUsersCard');
                if (counter) counter.textContent = data.activeUsers;
                if (card) card.textContent = data.activeUsers;
                
                // Update grafico
                if (this.charts.activeUsers) {
                    const chart = this.charts.activeUsers;
                    const time = new Date().toLocaleTimeString('it-IT', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    chart.data.labels.push(time);
                    chart.data.datasets[0].data.push(data.activeUsers);
                    
                    // Mantieni solo ultimi 10 valori
                    if (chart.data.labels.length > 10) {
                        chart.data.labels.shift();
                        chart.data.datasets[0].data.shift();
                    }
                    
                    chart.update();
                }
            }
        } catch (error) {
            console.error('Errore update utenti attivi:', error);
        }
    }
    
    /**
     * Submit form con AJAX
     */
    async submitFormAjax(formData, successMessage = 'Operazione completata') {
        const submitBtn = event.submitter;
        const originalText = submitBtn.innerHTML;
        
        try {
            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Caricamento...';
            
            const response = await fetch('admin.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage(data.message || successMessage, 'success');
                
                // Reset form se necessario
                const form = submitBtn.closest('form');
                if (form) form.reset();
                
                // Reload DataTable se nella stessa pagina
                setTimeout(() => {
                    const activeTab = document.querySelector('.tab-content.active');
                    const table = activeTab?.querySelector('table[id$="Table"]');
                    if (table) {
                        location.reload();
                    }
                }, 1500);
            } else {
                this.showMessage(data.message || 'Errore durante l\'operazione', 'danger');
            }
        } catch (error) {
            console.error('Errore:', error);
            this.showMessage('Errore di rete', 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
    
    /**
     * Preview immagine
     */
    previewImage(file, input) {
        const reader = new FileReader();
        reader.onload = (e) => {
            let preview = input.parentElement.querySelector('.img-preview-container');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'img-preview-container mt-3';
                input.parentElement.appendChild(preview);
            }
            preview.innerHTML = `
                <img src="${e.target.result}" class="img-fluid rounded" 
                     style="max-width: 200px; max-height: 200px; border: 2px solid var(--border-color);">
            `;
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * Ottieni ultimi 30 giorni per grafici
     */
    getLast30Days() {
        const days = [];
        const today = new Date();
        
        for (let i = 29; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(today.getDate() - i);
            days.push(date.toLocaleDateString('it-IT', {
                day: '2-digit',
                month: '2-digit'
            }));
        }
        
        return days;
    }
    
    /**
     * Mostra messaggio toast
     */
    showMessage(message, type = 'info') {
        // Rimuovi messaggi esistenti
        document.querySelectorAll('.admin-toast').forEach(t => t.remove());
        
        const toast = document.createElement('div');
        toast.className = `admin-toast alert alert-${type} alert-dismissible fade show`;
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            min-width: 300px;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        `;
        
        const icons = {
            success: 'check_circle',
            danger: 'error',
            warning: 'warning',
            info: 'info'
        };
        
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <span class="material-icons me-2">${icons[type] || icons.info}</span>
                <span class="flex-grow-1">${message}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Auto rimozione dopo 5 secondi
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 150);
        }, 5000);
    }
}

// ===== FUNZIONALITÀ EXTRA =====
class AdminFeatures {
    constructor(adminPanel) {
        this.admin = adminPanel;
        this.initExtraFeatures();
    }
    
    initExtraFeatures() {
        this.initSearchFilters();
        this.initBulkOperations();
        this.initExportButtons();
        this.initKeyboardShortcuts();
        this.initActivityLog();
    }
    
    /**
     * Ricerca globale e filtri
     */
    initSearchFilters() {
        // Aggiungi barra di ricerca globale
        const searchBar = `
            <div class="global-search" style="position: fixed; top: 80px; right: 20px; z-index: 100;">
                <input type="text" id="globalSearch" class="form-control" 
                       placeholder="Cerca..." style="width: 250px; background: var(--card-bg);">
            </div>
        `;
        
        if (document.querySelector('.admin-main')) {
            document.querySelector('.admin-main').insertAdjacentHTML('afterbegin', searchBar);
            
            document.getElementById('globalSearch')?.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                this.performGlobalSearch(query);
            });
        }
    }
    
    performGlobalSearch(query) {
        if (query.length < 2) return;
        
        // Cerca nelle tabelle visibili
        document.querySelectorAll('.admin-table tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }
    
    /**
     * Operazioni bulk
     */
    initBulkOperations() {
        // Aggiungi checkbox alle tabelle
        document.querySelectorAll('.admin-table table').forEach(table => {
            const thead = table.querySelector('thead tr');
            const tbody = table.querySelector('tbody');
            
            if (thead && tbody) {
                // Aggiungi checkbox header
                const th = document.createElement('th');
                th.innerHTML = '<input type="checkbox" class="bulk-select-all">';
                thead.insertBefore(th, thead.firstChild);
                
                // Aggiungi checkbox a ogni riga
                tbody.querySelectorAll('tr').forEach(row => {
                    const td = document.createElement('td');
                    td.innerHTML = '<input type="checkbox" class="bulk-select-item">';
                    row.insertBefore(td, row.firstChild);
                });
            }
        });
        
        // Gestisci select all
        document.querySelectorAll('.bulk-select-all').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const table = e.target.closest('table');
                table.querySelectorAll('.bulk-select-item').forEach(item => {
                    item.checked = e.target.checked;
                });
                this.updateBulkActions();
            });
        });
        
        // Gestisci singoli checkbox
        document.querySelectorAll('.bulk-select-item').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateBulkActions());
        });
    }
    
    updateBulkActions() {
        const selected = document.querySelectorAll('.bulk-select-item:checked').length;
        
        if (selected > 0) {
            if (!document.querySelector('.bulk-actions-bar')) {
                const bar = `
                    <div class="bulk-actions-bar" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); 
                         background: var(--card-bg); padding: 1rem 2rem; border-radius: var(--radius-xl); 
                         box-shadow: var(--shadow-xl); display: flex; gap: 1rem; align-items: center; z-index: 1000;">
                        <span><strong id="bulkCount">${selected}</strong> elementi selezionati</span>
                        <button class="btn-primary btn-sm" onclick="adminPanel.features.bulkExport()">
                            <span class="material-icons">download</span> Esporta
                        </button>
                        <button class="btn-danger btn-sm" onclick="adminPanel.features.bulkDelete()">
                            <span class="material-icons">delete</span> Elimina
                        </button>
                        <button class="btn-secondary btn-sm" onclick="adminPanel.features.clearSelection()">
                            <span class="material-icons">clear</span> Annulla
                        </button>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', bar);
            } else {
                document.getElementById('bulkCount').textContent = selected;
            }
        } else {
            document.querySelector('.bulk-actions-bar')?.remove();
        }
    }
    
    clearSelection() {
        document.querySelectorAll('.bulk-select-item, .bulk-select-all').forEach(cb => cb.checked = false);
        this.updateBulkActions();
    }
    
    bulkExport() {
        const selected = document.querySelectorAll('.bulk-select-item:checked');
        const data = [];
        
        selected.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const cells = Array.from(row.cells).slice(1).map(cell => cell.textContent);
            data.push(cells);
        });
        
        this.downloadCSV(data, 'export.csv');
        this.admin.showMessage('Dati esportati con successo', 'success');
    }
    
    bulkDelete() {
        if (confirm(`Eliminare ${document.querySelectorAll('.bulk-select-item:checked').length} elementi?`)) {
            this.admin.showMessage('Elementi eliminati', 'success');
            this.clearSelection();
            setTimeout(() => location.reload(), 1000);
        }
    }
    
    /**
     * Bottoni export
     */
    initExportButtons() {
        document.querySelectorAll('.card-header').forEach(header => {
            const table = header.parentElement.querySelector('table');
            if (table && !header.querySelector('.export-btn')) {
                const btn = document.createElement('button');
                btn.className = 'btn-secondary btn-sm export-btn';
                btn.innerHTML = '<span class="material-icons">download</span> Export';
                btn.onclick = () => this.exportTable(table);
                
                const titleEl = header.querySelector('.card-title');
                if (titleEl) {
                    header.style.display = 'flex';
                    header.style.justifyContent = 'space-between';
                    header.appendChild(btn);
                }
            }
        });
    }
    
    exportTable(table) {
        const data = [];
        
        // Headers
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
        data.push(headers);
        
        // Rows
        table.querySelectorAll('tbody tr').forEach(row => {
            const cells = Array.from(row.cells).map(cell => cell.textContent);
            data.push(cells);
        });
        
        this.downloadCSV(data, 'table-export.csv');
    }
    
    downloadCSV(data, filename) {
        const csv = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }
    
    /**
     * Keyboard shortcuts
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S = Save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const activeForm = document.querySelector('.tab-content.active form');
                if (activeForm) {
                    const submitBtn = activeForm.querySelector('button[type="submit"]');
                    submitBtn?.click();
                }
            }
            
            // Ctrl/Cmd + F = Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('globalSearch')?.focus();
            }
            
            // ESC = Clear selection
            if (e.key === 'Escape') {
                this.clearSelection();
            }
            
            // Alt + 1-7 = Navigate tabs
            if (e.altKey && e.key >= '1' && e.key <= '7') {
                e.preventDefault();
                const menuItems = document.querySelectorAll('.menu-item');
                const index = parseInt(e.key) - 1;
                if (menuItems[index]) {
                    menuItems[index].click();
                }
            }
        });
    }
    
    /**
     * Activity Log
     */
    initActivityLog() {
        // Intercetta tutte le azioni
        const originalSubmit = this.admin.submitFormAjax.bind(this.admin);
        this.admin.submitFormAjax = async (...args) => {
            const result = await originalSubmit(...args);
            this.logActivity('Form inviato', args[1]);
            return result;
        };
    }
    
    logActivity(action, details) {
        const log = {
            action,
            details,
            timestamp: new Date().toISOString(),
            user: document.querySelector('.user-name')?.textContent || 'Admin'
        };
        
        // Salva in localStorage
        const logs = JSON.parse(localStorage.getItem('adminActivityLog') || '[]');
        logs.push(log);
        
        // Mantieni solo ultimi 100 log
        if (logs.length > 100) logs.shift();
        localStorage.setItem('adminActivityLog', JSON.stringify(logs));
        
        console.log('Activity logged:', log);
    }
}

// Inizializza quando DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
    window.adminPanel.features = new AdminFeatures(window.adminPanel);
});

// CSS per animazioni toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    .img-preview-container {
        margin-top: 1rem;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
        border-width: 0.2em;
    }
`;
document.head.appendChild(style);