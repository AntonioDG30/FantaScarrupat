/**
 * Enhanced Admin Panel JavaScript
 * Versione 2.0 - Con correzioni dashboard e nuove funzionalità
 */

class EnhancedAdminPanel {
    constructor() {
        this.currentTab = null;
        this.charts = {};
        this.dataTables = {};
        this.notifications = [];
        this.autoRefreshInterval = null;
        this.dashboardChartInitialized = false;
        
        this.init();
    }
    
    init() {
        this.initTheme();
        this.initSidebar();
        this.initDataTables();
        this.initDashboardCharts();
        this.initForms();
        this.initActions();
        this.initTabs();
        this.initNotifications();
        this.initAutoRefresh();
        this.initSearchAndFilters();
        this.addTabToForms();
        this.fixChartSizing();
        
        // console.log('Enhanced Admin Panel v2.0 initialized');
    }
    
    /**
     * FIX: Risolve il problema di sizing dei chart
     */
    fixChartSizing() {
        // Osserva i cambiamenti di dimensione
        if (typeof ResizeObserver !== 'undefined') {
            const resizeObserver = new ResizeObserver(entries => {
                entries.forEach(entry => {
                    const chartId = entry.target.querySelector('canvas')?.id;
                    if (chartId && this.charts[chartId]) {
                        setTimeout(() => {
                            this.charts[chartId].resize();
                        }, 100);
                    }
                });
            });

            // Osserva tutti i container dei chart
            document.querySelectorAll('.chart-container').forEach(container => {
                resizeObserver.observe(container);
            });
        }

        // Fix dimensioni iniziali
        setTimeout(() => {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }, 500);
    }
    
    /**
     * Inizializza i grafici della dashboard con fix per i dati
     */
    initDashboardCharts() {
        if (this.dashboardChartInitialized) return;
        
        // Chart visitatori
        const visitorsCtx = document.getElementById('visitorsChart');
        if (visitorsCtx) {
            const dashboardData = window.dashboardData || {};
            const visitorsData = dashboardData.visitorsData || [];
            const visitorsLabels = dashboardData.visitorsLabels || this.getLast30Days();
            
            // Assicura che i dati abbiano la lunghezza corretta
            while (visitorsData.length < visitorsLabels.length) {
                visitorsData.unshift(0);
            }
            
            this.charts.visitorsChart = new Chart(visitorsCtx, {
                type: 'line',
                data: {
                    labels: visitorsLabels,
                    datasets: [{
                        label: 'Visitatori giornalieri',
                        data: visitorsData,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 8
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
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#6b7280',
                            borderColor: '#e5e7eb',
                            borderWidth: 1,
                            cornerRadius: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }
        
        // Chart utenti attivi in tempo reale
        const activeUsersCtx = document.getElementById('activeUsersChart');
        if (activeUsersCtx) {
            this.charts.activeUsersChart = new Chart(activeUsersCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Utenti Attivi', 'Utenti Inattivi'],
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(229, 231, 235, 0.3)'
                        ],
                        borderColor: [
                            'rgba(16, 185, 129, 1)',
                            'rgba(229, 231, 235, 0.5)'
                        ],
                        borderWidth: 2,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed;
                                }
                            }
                        }
                    }
                }
            });
            
            // Avvia aggiornamento real-time
            this.startActiveUsersTracking();
        }
        
        this.dashboardChartInitialized = true;
    }
    
    /**
     * Tracking utenti attivi in tempo reale
     */
    startActiveUsersTracking() {
        const updateActiveUsers = async () => {
            try {
                const response = await fetch('php/getActiveUsers.php');
                const data = await response.json();
                
                if (data.activeUsers !== undefined) {
                    const activeUsers = data.activeUsers;
                    const inactiveUsers = Math.max(0, 10 - activeUsers); // Stima basata su max 10 utenti
                    
                    if (this.charts.activeUsersChart) {
                        this.charts.activeUsersChart.data.datasets[0].data = [activeUsers, inactiveUsers];
                        this.charts.activeUsersChart.update('none');
                    }
                    
                    // Aggiorna contatore
                    const counter = document.getElementById('activeUsersCount');
                    if (counter) {
                        counter.textContent = activeUsers;
                        
                        // Effetto pulse se ci sono utenti attivi
                        if (activeUsers > 0) {
                            counter.parentElement.classList.add('pulse-glow');
                        } else {
                            counter.parentElement.classList.remove('pulse-glow');
                        }
                    }
                    
                    // Notifica se ci sono molti utenti attivi
                    if (activeUsers >= 5 && !this.hasNotification('high-activity')) {
                        this.showNotification({
                            id: 'high-activity',
                            title: 'Alta attività!',
                            message: `${activeUsers} utenti attivi contemporaneamente`,
                            type: 'info',
                            duration: 8000
                        });
                    }
                }
            } catch (error) {
                console.error('Errore aggiornamento utenti attivi:', error);
            }
        };
        
        // Aggiorna ogni 5 secondi
        updateActiveUsers(); // Prima chiamata immediata
        setInterval(updateActiveUsers, 5000);
    }
    
    /**
     * Aggiunge campo tab nascosto a tutti i form
     */
    addTabToForms() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'dashboard';
        
        document.querySelectorAll('form').forEach(form => {
            // Controlla se ha già il campo tab
            if (!form.querySelector('input[name="tab"]')) {
                const tabField = document.createElement('input');
                tabField.type = 'hidden';
                tabField.name = 'tab';
                tabField.value = currentTab;
                form.appendChild(tabField);
            }
        });
        
        // Aggiorna link per mantenere tab
        document.querySelectorAll('a[href*="cambiaFlag"], a[href*="php/"]').forEach(link => {
            if (link.href.includes('cambiaFlag') || link.href.includes('.php')) {
                const url = new URL(link.href);
                if (!url.searchParams.has('tab')) {
                    url.searchParams.set('tab', currentTab);
                    link.href = url.toString();
                }
            }
        });
    }
    
    /**
     * Sistema notifiche avanzato
     */
    initNotifications() {
        // Crea container notifiche se non esiste
        if (!document.getElementById('notifications-container')) {
            const container = document.createElement('div');
            container.id = 'notifications-container';
            container.className = 'notifications-container';
            document.body.appendChild(container);
        }
        
        // Verifica notifiche da URL
        const urlParams = new URLSearchParams(window.location.search);
        const checkParam = urlParams.get('check');
        if (checkParam) {
            this.showNotification({
                title: 'Operazione completata',
                message: checkParam,
                type: 'success',
                duration: 5000
            });
        }
    }
    
    showNotification({ id, title, message, type = 'info', duration = 5000, actions = [] }) {
        const container = document.getElementById('notifications-container');
        if (!container) return;
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} notification-enter`;
        if (id) notification.setAttribute('data-id', id);
        
        const icons = {
            success: 'check_circle',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };
        
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <span class="material-icons">${icons[type]}</span>
                </div>
                <div class="notification-text">
                    <div class="notification-title">${title}</div>
                    <div class="notification-message">${message}</div>
                </div>
                ${actions.length > 0 ? `
                    <div class="notification-actions">
                        ${actions.map(action => `
                            <button class="notification-btn" onclick="${action.onClick}">
                                ${action.text}
                            </button>
                        `).join('')}
                    </div>
                ` : ''}
                <button class="notification-close" onclick="this.closest('.notification').remove()">
                    <span class="material-icons">close</span>
                </button>
            </div>
            <div class="notification-progress"></div>
        `;
        
        container.appendChild(notification);
        this.notifications.push({ id, element: notification });
        
        // Animazione entrata
        setTimeout(() => {
            notification.classList.remove('notification-enter');
            notification.classList.add('notification-show');
        }, 100);
        
        // Progress bar
        if (duration > 0) {
            const progressBar = notification.querySelector('.notification-progress');
            progressBar.style.animationDuration = `${duration}ms`;
            progressBar.classList.add('notification-progress-active');
            
            // Auto remove
            setTimeout(() => {
                this.removeNotification(notification);
            }, duration);
        }
        
        return notification;
    }
    
    removeNotification(notification) {
        notification.classList.add('notification-exit');
        setTimeout(() => {
            notification.remove();
            this.notifications = this.notifications.filter(n => n.element !== notification);
        }, 300);
    }
    
    hasNotification(id) {
        return this.notifications.some(n => n.id === id);
    }
    
    /**
     * Auto-refresh per statistiche dashboard
     */
    initAutoRefresh() {
        // Auto-refresh ogni 30 secondi per la dashboard
        if (window.location.search.includes('tab=dashboard') || !window.location.search.includes('tab=')) {
            this.autoRefreshInterval = setInterval(() => {
                this.refreshDashboardStats();
            }, 30000);
        }
    }
    
    async refreshDashboardStats() {
        try {
            // Aggiorna solo i dati statistici senza ricaricare la pagina
            const response = await fetch(`Admin.php?tab=dashboard&ajax=1`);
            if (response.ok) {
                console.log('Dashboard stats refreshed');
            }
        } catch (error) {
            console.error('Errore refresh dashboard:', error);
        }
    }
    
    /**
     * Ricerca e filtri avanzati
     */
    initSearchAndFilters() {
        // Aggiungi search box per tabelle
        document.querySelectorAll('.admin-table').forEach((table, index) => {
            const tableId = table.querySelector('table')?.id;
            if (tableId && this.dataTables[tableId.replace('Table', '')]) {
                this.addAdvancedSearch(tableId, this.dataTables[tableId.replace('Table', '')]);
            }
        });
    }
    
    addAdvancedSearch(tableId, dataTable) {
        const tableContainer = document.getElementById(tableId)?.closest('.admin-card');
        if (!tableContainer) return;
        
        const searchContainer = document.createElement('div');
        searchContainer.className = 'table-search-container';
        searchContainer.innerHTML = `
            <div class="search-filters">
                <div class="search-input-group">
                    <span class="material-icons">search</span>
                    <input type="text" class="search-input" placeholder="Cerca in tabella...">
                </div>
                <div class="filter-buttons">
                    <button type="button" class="btn-filter" data-action="export-csv">
                        <span class="material-icons">file_download</span>
                        CSV
                    </button>
                    <button type="button" class="btn-filter" data-action="refresh">
                        <span class="material-icons">refresh</span>
                        Aggiorna
                    </button>
                </div>
            </div>
        `;
        
        const cardHeader = tableContainer.querySelector('.card-header');
        if (cardHeader) {
            cardHeader.appendChild(searchContainer);
        }
        
        // Event listeners
        const searchInput = searchContainer.querySelector('.search-input');
        searchInput.addEventListener('input', (e) => {
            dataTable.search(e.target.value).draw();
        });
        
        // Export CSV
        searchContainer.querySelector('[data-action="export-csv"]').addEventListener('click', () => {
            this.exportTableToCSV(tableId);
        });
        
        // Refresh
        searchContainer.querySelector('[data-action="refresh"]').addEventListener('click', () => {
            location.reload();
        });
    }
    
    /**
     * Export tabella to CSV
     */
    exportTableToCSV(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = Array.from(table.querySelectorAll('tr'));
        const csvContent = rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            return cells.map(cell => {
                // Pulisci il contenuto delle celle
                const text = cell.textContent || '';
                return `"${text.replace(/"/g, '""')}"`;
            }).join(',');
        }).join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${tableId}_export_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        
        this.showNotification({
            title: 'Export completato',
            message: 'Tabella esportata in CSV',
            type: 'success'
        });
    }
    
    /**
     * Miglioramenti DataTables esistenti
     */
    initDataTables() {
        const commonConfig = {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
            },
            pageLength: 25,
            responsive: true,
            dom: '<"top"f>rt<"bottom"lp><"clear">',
            drawCallback: function() {
                // Applica stili moderni dopo ogni ridraw
                this.api().rows().every(function() {
                    $(this.node()).addClass('modern-row');
                });
            }
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
        
        // Tabella Partecipanti con gestione righe nascoste
        if (document.getElementById('partecipantiTable')) {
            const hiddenRows = [];
            $('#partecipantiTable tbody tr').each(function() {
                if ($(this).attr('id') && $(this).attr('id').startsWith('rose-row-')) {
                    hiddenRows.push({
                        element: $(this).detach(),
                        prevRow: $(this).prev()
                    });
                }
            });
            
            this.dataTables.partecipanti = $('#partecipantiTable').DataTable({
                ...commonConfig,
                columnDefs: [
                    { orderable: false, targets: [4, 5] }
                ]
            });
            
            hiddenRows.forEach(item => {
                item.prevRow.after(item.element);
            });
        }
        
        // Altre tabelle...
        ['competizioni', 'gallery', 'parametri'].forEach(type => {
            const tableEl = document.getElementById(`${type}Table`);
            if (tableEl) {
                this.dataTables[type] = $(`#${type}Table`).DataTable(commonConfig);
            }
        });
    }
    
    /**
     * Altri metodi esistenti mantenuti e migliorati...
     */
    initTheme() {
        if (typeof ThemeManager !== 'undefined') {
            const tm = new ThemeManager();
            tm.init();
        }
    }
    
    initSidebar() {
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<span class="material-icons">menu</span>';
        
        if (window.innerWidth <= 1024) {
            document.body.appendChild(menuToggle);
            
            menuToggle.addEventListener('click', () => {
                const sidebar = document.querySelector('.admin-sidebar');
                sidebar.classList.toggle('active');
                
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
        document.querySelectorAll('.menu-item').forEach(item => {
            if (item.href && item.href.includes(currentPath)) {
                item.classList.add('active');
            }
        });
    }
    
    initForms() {
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    this.previewImage(file, e.target);
                }
            });
        });
        
        document.querySelectorAll('.admin-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showNotification({
                        title: 'Errore validazione',
                        message: 'Compila tutti i campi richiesti',
                        type: 'error'
                    });
                }
            });
        });
    }
    
    initActions() {
        document.querySelectorAll('.toggle-visibility').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleToggleVisibility(btn);
            });
        });
        
        document.querySelectorAll('.delete-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleDelete(btn);
            });
        });
    }
    
    async handleToggleVisibility(btn) {
        const type = btn.dataset.type;
        const id = btn.dataset.id;
        
        try {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-icons spinning">refresh</span>';
            
            const response = await fetch(btn.href);
            if (response.ok) {
                this.showNotification({
                    title: 'Successo',
                    message: 'Visibilità aggiornata',
                    type: 'success'
                });
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error('Errore server');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile aggiornare la visibilità',
                type: 'error'
            });
            btn.disabled = false;
            btn.innerHTML = '<span class="material-icons">visibility</span>';
        }
    }
    
    initTabs() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                tabButtons.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                btn.classList.add('active');
                const tabId = btn.dataset.tab;
                const tabContent = document.getElementById(tabId);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
            });
        });
    }
    
    previewImage(file, input) {
        const reader = new FileReader();
        reader.onload = (e) => {
            let preview = input.parentElement.querySelector('.img-preview-container');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'img-preview-container';
                input.parentElement.appendChild(preview);
            }
            preview.innerHTML = `
                <div class="img-preview-wrapper">
                    <img src="${e.target.result}" class="img-preview-large" />
                    <div class="img-preview-info">
                        <span>${file.name}</span>
                        <span>${(file.size / 1024).toFixed(1)} KB</span>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
    
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
    
    // Cleanup al destroy
    destroy() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
        
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        
        Object.values(this.dataTables).forEach(table => {
            if (table && typeof table.destroy === 'function') {
                table.destroy();
            }
        });
    }
}

// Stili per notifiche e miglioramenti
const enhancedStyles = `
<style>
.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}

.notification {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 0;
    min-width: 300px;
    max-width: 400px;
    box-shadow: var(--glass-shadow);
    pointer-events: all;
    position: relative;
    overflow: hidden;
}

.notification-enter {
    opacity: 0;
    transform: translateX(100%);
}

.notification-show {
    opacity: 1;
    transform: translateX(0);
    transition: all 0.3s ease;
}

.notification-exit {
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.notification-content {
    display: flex;
    align-items: center;
    padding: 16px;
    gap: 12px;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-success .notification-icon {
    background: var(--gradient-emerald-cyan);
    color: white;
}

.notification-error .notification-icon {
    background: var(--gradient-orange-pink);
    color: white;
}

.notification-info .notification-icon {
    background: var(--gradient-cyan-blue);
    color: white;
}

.notification-text {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.notification-message {
    font-size: 0.9rem;
    color: var(--text-secondary);
    line-height: 1.4;
}

.notification-close {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.notification-close:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
}

.notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: var(--gradient-primary);
    width: 0;
}

.notification-progress-active {
    animation: notificationProgress linear forwards;
}

@keyframes notificationProgress {
    from { width: 0; }
    to { width: 100%; }
}

.table-search-container {
    margin-top: 1rem;
}

.search-filters {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.search-input-group {
    display: flex;
    align-items: center;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 8px 12px;
    flex: 1;
    min-width: 200px;
}

.search-input-group .material-icons {
    color: var(--text-muted);
    margin-right: 8px;
}

.search-input {
    background: transparent;
    border: none;
    outline: none;
    color: var(--text-primary);
    flex: 1;
}

.filter-buttons {
    display: flex;
    gap: 8px;
}

.btn-filter {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.btn-filter:hover {
    background: var(--hover-bg);
    color: var(--text-primary);
    border-color: var(--primary-color);
}

.img-preview-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--glass-bg);
    border-radius: 12px;
    margin-top: 1rem;
}

.img-preview-large {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid var(--border-color);
}

.img-preview-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.modern-row {
    transition: all 0.2s ease;
}

.modern-row:hover {
    background: var(--glass-bg) !important;
}

@media (max-width: 768px) {
    .notifications-container {
        left: 20px;
        right: 20px;
    }
    
    .notification {
        min-width: auto;
        max-width: none;
    }
    
    .search-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-group {
        min-width: auto;
    }
}
</style>
`;

// Inietta stili
document.head.insertAdjacentHTML('beforeend', enhancedStyles);

// Inizializza quando DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    // Distruggi istanza precedente se esiste
    if (window.adminPanel && typeof window.adminPanel.destroy === 'function') {
        window.adminPanel.destroy();
    }
    
    const enhancedAdminPanel = new EnhancedAdminPanel();
    window.adminPanel = enhancedAdminPanel;
    
    // console.log('Enhanced Admin Panel loaded successfully');
});

// Cleanup su unload
window.addEventListener('beforeunload', () => {
    if (window.adminPanel && typeof window.adminPanel.destroy === 'function') {
        window.adminPanel.destroy();
    }
});