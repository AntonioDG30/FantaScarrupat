/**
 * Admin Panel JavaScript
 * Gestisce tutte le funzionalità del pannello amministrativo
 */

class AdminPanel {
    constructor() {
        this.currentTab = null;
        this.charts = {};
        this.dataTables = {};
        this.init();
    }
    
    init() {
        this.initTheme();
        this.initSidebar();
        this.initDataTables();
        this.initCharts();
        this.initForms();
        this.initActions();
        this.initTabs();
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
     * Gestisce la sidebar responsive
     */
    initSidebar() {
        // Toggle sidebar mobile
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<span class="material-icons">menu</span>';
        
        if (window.innerWidth <= 1024) {
            document.body.appendChild(menuToggle);
            
            menuToggle.addEventListener('click', () => {
                const sidebar = document.querySelector('.admin-sidebar');
                sidebar.classList.toggle('active');
                
                // Overlay per chiudere sidebar su mobile
                let overlay = document.querySelector('.sidebar-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'sidebar-overlay';
                    document.body.appendChild(overlay);
                }
                overlay.classList.toggle('active');
                
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            });
        }
        
        // Evidenzia menu attivo
        const currentPath = window.location.search;
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            if (item.href && item.href.includes(currentPath)) {
                item.classList.add('active');
            }
        });
    }
    
    /**
     * Inizializza DataTables
     */
    initDataTables() {
        // Configurazione comune per tutte le tabelle
        const commonConfig = {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
            },
            pageLength: 25,
            responsive: true
        };
        
        // Tabella Calciatori
        if (document.getElementById('calciatoriTable')) {
            this.dataTables.calciatori = $('#calciatoriTable').DataTable({
                ...commonConfig,
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: -1 }
                ]
            });
        }
        
        // Tabella Partecipanti - GESTIONE SPECIALE per righe nascoste
        if (document.getElementById('partecipantiTable')) {
            // Prima rimuovi temporaneamente le righe nascoste dal DOM
            const hiddenRows = [];
            $('#partecipantiTable tbody tr').each(function() {
                if ($(this).attr('id') && $(this).attr('id').startsWith('rose-row-')) {
                    hiddenRows.push({
                        element: $(this).detach(),
                        prevRow: $(this).prev()
                    });
                }
            });
            
            // Inizializza DataTable senza le righe nascoste
            this.dataTables.partecipanti = $('#partecipantiTable').DataTable({
                ...commonConfig,
                columnDefs: [
                    { orderable: false, targets: [4, 5] }
                ]
            });
            
            // Reinserisci le righe nascoste dopo l'inizializzazione
            hiddenRows.forEach(item => {
                item.prevRow.after(item.element);
            });
        }
        
        // Tabella Competizioni
        if (document.getElementById('competizioniTable')) {
            this.dataTables.competizioni = $('#competizioniTable').DataTable({
                ...commonConfig,
                order: [[4, 'desc']]
            });
        }
        
        // Tabella Gallery
        if (document.getElementById('galleryTable')) {
            this.dataTables.gallery = $('#galleryTable').DataTable({
                ...commonConfig,
                columnDefs: [
                    { orderable: false, targets: [0, -1] }
                ]
            });
        }
        
        // Tabella Parametri
        if (document.getElementById('parametriTable')) {
            this.dataTables.parametri = $('#parametriTable').DataTable({
                ...commonConfig,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: -1 }
                ]
            });
        }
    }
    
    /**
     * Inizializza i grafici della dashboard
     */
    initCharts() {
        // Grafico visitatori
        const visitorsCtx = document.getElementById('visitorsChart');
        if (visitorsCtx) {
            this.charts.visitors = new Chart(visitorsCtx, {
                type: 'line',
                data: {
                    labels: this.getLast30Days(),
                    datasets: [{
                        label: 'Visitatori giornalieri',
                        data: window.visitorsData || [],
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
                        legend: {
                            display: false
                        },
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
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
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
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Update periodico utenti attivi
            this.updateActiveUsers();
            setInterval(() => this.updateActiveUsers(), 5000);
        }
    }
    
    /**
     * Inizializza la gestione form
     */
    initForms() {
        // Preview immagini
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.previewImage(file, e.target);
                }
            });
        });
        
        // Validazione form
        document.querySelectorAll('.admin-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showMessage('Compila tutti i campi richiesti', 'danger');
                }
            });
        });
        
        // Form competizioni dinamico
        const competizioneSelect = document.getElementById('competizione');
        if (competizioneSelect) {
            competizioneSelect.addEventListener('change', (e) => {
                const nuovaCompDiv = document.getElementById('nuova_competizione');
                const nbaDiv = document.getElementById('nba_competizione');
                
                if (e.target.value === 'nuova_competizione') {
                    nuovaCompDiv.style.display = 'block';
                    nbaDiv.style.display = 'none';
                } else if (e.target.value === 'NBA') {
                    nuovaCompDiv.style.display = 'none';
                    nbaDiv.style.display = 'block';
                } else {
                    nuovaCompDiv.style.display = 'none';
                    nbaDiv.style.display = 'none';
                }
            });
        }
    }
    
    /**
     * Inizializza azioni (toggle visibilità, elimina, etc)
     */
    initActions() {
        // Toggle visibilità 
        document.querySelectorAll('.toggle-visibility').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const type = btn.dataset.type;
                const id = btn.dataset.id;
                this.toggleVisibility(type, id);
            });
        });
        
        // Elimina
        document.querySelectorAll('.delete-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm('Sei sicuro di voler eliminare questo elemento?')) {
                    const type = btn.dataset.type;
                    const id = btn.dataset.id;
                    this.deleteItem(type, id);
                }
            });
        });
        
        // Mostra rose partecipante - FIX per DataTables
        document.querySelectorAll('.show-rose').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const rowId = btn.dataset.row;
                const row = document.getElementById(rowId);
                
                if (row) {
                    // Toggle visibilità della riga
                    const isVisible = row.style.display !== 'none';
                    row.style.display = isVisible ? 'none' : 'table-row';
                    
                    // Aggiorna testo bottone
                    btn.textContent = isVisible ? 'Mostra rose' : 'Nascondi rose';
                    
                    // Se DataTable è attivo, aggiorna il redraw
                    if (this.dataTables.partecipanti) {
                        // Forza un redraw della tabella dopo il toggle
                        setTimeout(() => {
                            this.dataTables.partecipanti.draw(false);
                        }, 100);
                    }
                }
            });
        });
    }
    
    /**
     * Inizializza tabs
     */
    initTabs() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Rimuovi active da tutti
                tabButtons.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Aggiungi active al cliccato
                btn.classList.add('active');
                const tabId = btn.dataset.tab;
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
            });
        });
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
                preview.className = 'img-preview-container';
                input.parentElement.appendChild(preview);
            }
            preview.innerHTML = `<img src="${e.target.result}" class="img-preview-large" />`;
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * Valida form
     */
    validateForm(form) {
        let isValid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        return isValid;
    }
    
    /**
     * Toggle visibilità elemento
     */
    async toggleVisibility(type, id) {
        try {
            const response = await fetch(`php/cambiaFlag${type}.php?id_${type.toLowerCase()}=${id}`, {
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            });
            
            if (response.ok) {
                this.showMessage('Visibilità aggiornata con successo', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showMessage('Errore durante l\'aggiornamento', 'danger');
            }
        } catch (error) {
            console.error('Errore:', error);
            this.showMessage('Errore di rete', 'danger');
        }
    }
    
    /**
     * Elimina elemento
     */
    async deleteItem(type, id) {
        try {
            const response = await fetch(`php/delete${type}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ id: id })
            });
            
            if (response.ok) {
                this.showMessage('Elemento eliminato con successo', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showMessage('Errore durante l\'eliminazione', 'danger');
            }
        } catch (error) {
            console.error('Errore:', error);
            this.showMessage('Errore di rete', 'danger');
        }
    }
    
    /**
     * Aggiorna utenti attivi
     */
    async updateActiveUsers() {
        try {
            const response = await fetch('php/getActiveUsers.php');
            const data = await response.json();
            
            if (this.charts.activeUsers && data.activeUsers) {
                const chart = this.charts.activeUsers;
                const timestamp = new Date().toLocaleTimeString('it-IT', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Aggiungi nuovo dato
                chart.data.labels.push(timestamp);
                chart.data.datasets[0].data.push(data.activeUsers);
                
                // Mantieni solo gli ultimi 10 valori
                if (chart.data.labels.length > 10) {
                    chart.data.labels.shift();
                    chart.data.datasets[0].data.shift();
                }
                
                chart.update();
                
                // Aggiorna contatore
                const counter = document.getElementById('activeUsersCount');
                if (counter) {
                    counter.textContent = data.activeUsers;
                }
            }
        } catch (error) {
            console.error('Errore aggiornamento utenti attivi:', error);
        }
    }
    
    /**
     * Ottieni ultimi 30 giorni
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
     * Mostra messaggio
     */
    showMessage(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <span class="material-icons">${this.getAlertIcon(type)}</span>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const container = document.querySelector('.admin-main');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Rimuovi automaticamente dopo 5 secondi
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
    
    /**
     * Ottieni icona per tipo di alert
     */
    getAlertIcon(type) {
        const icons = {
            success: 'check_circle',
            danger: 'error',
            warning: 'warning',
            info: 'info'
        };
        return icons[type] || icons.info;
    }
}

// Inizializza quando DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    const adminPanel = new AdminPanel();
    window.adminPanel = adminPanel;
    
    // Export data functions
    window.exportData = (format, type) => {
        const table = adminPanel.dataTables[type];
        if (!table) return;
        
        const data = table.data().toArray();
        
        if (format === 'csv') {
            exportToCSV(data, `${type}_${Date.now()}.csv`);
        } else if (format === 'xlsx') {
            exportToExcel(data, `${type}_${Date.now()}.xlsx`);
        }
    };
    
    // CSV export
    window.exportToCSV = (data, filename) => {
        const csv = data.map(row => row.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    };
});

// Aggiungi overlay per sidebar mobile
const style = document.createElement('style');
style.textContent = `
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
    
    .img-preview-large {
        max-width: 200px;
        max-height: 200px;
        border-radius: var(--radius-lg);
        border: 2px solid var(--border-color);
    }
    
    .is-invalid {
        border-color: var(--error-color) !important;
    }
    
    .btn-close {
        background: transparent;
        border: none;
        font-size: 1.5rem;
        line-height: 1;
        color: currentColor;
        opacity: 0.5;
        margin-left: auto;
        cursor: pointer;
    }
    
    .btn-close:hover {
        opacity: 1;
    }
    
    /* Fix per righe nascoste in DataTables */
    #partecipantiTable tbody tr[id^="rose-row-"] {
        background: var(--surface-secondary) !important;
    }
    
    #partecipantiTable tbody tr[id^="rose-row-"] td {
        padding: 0 !important;
    }
    
    #partecipantiTable tbody tr[id^="rose-row-"] .bg-light {
        background: var(--surface-secondary) !important;
        border: 1px solid var(--border-color);
    }
`;
document.head.appendChild(style);