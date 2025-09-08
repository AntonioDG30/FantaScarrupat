/**
 * Enhanced Admin Panel JavaScript - Versione Completa
 * Gestisce tutte le funzionalità del pannello amministrativo
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
        this.initGalleryFeatures();
        this.initCompetitionFeatures();
        this.initParametersFeatures();
        this.initParticipantsFeatures();
        this.initRoseFeatures();
        this.initCalciatoriFeatures();
        
        // console.log('Enhanced Admin Panel v2.0 initialized');
    }
    
    /**
     * Inizializza theme manager
     */
    initTheme() {
        if (typeof ThemeManager !== 'undefined') {
            const tm = new ThemeManager();
            tm.init();
        }
    }
    
    /**
     * FIX: Risolve il problema di sizing dei chart
     */
    fixChartSizing() {
        // Osserva i cambiamenti di dimensione
        if (typeof ResizeObserver !== 'undefined') {
            const resizeObserver = new ResizeObserver(entries => {
                entries.forEach(entry => {
                    const chartCanvas = entry.target.querySelector('canvas');
                    if (chartCanvas && chartCanvas.id) {
                        // FIX: Usa Chart.getChart() invece di Chart.instances
                        const chartInstance = Chart.getChart(chartCanvas);
                        if (chartInstance && typeof chartInstance.resize === 'function') {
                            setTimeout(() => {
                                chartInstance.resize();
                            }, 100);
                        }
                    }
                });
            });

            // Osserva tutti i container dei chart
            document.querySelectorAll('.chart-container').forEach(container => {
                resizeObserver.observe(container);
            });
        }

        // FIX: Resize dei chart esistenti usando Chart.getChart()
        setTimeout(() => {
            // Trova tutti i canvas e prova a fare resize dei chart
            document.querySelectorAll('canvas').forEach(canvas => {
                const chart = Chart.getChart(canvas);
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
            
            // Backup: usa anche il nostro registro se esistente
            if (this.charts) {
                Object.values(this.charts).forEach(chart => {
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }
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
        
        // Chart distribuzione ruoli (per calciatori)
        this.initRolesChart();
        
        // Chart spesa per anno (per rose)
        this.initSpesaAnnoChart();
        
        this.dashboardChartInitialized = true;
    }
    
    /**
     * Chart distribuzione ruoli
     */
    initRolesChart() {
        const rolesCtx = document.getElementById('rolesChart');
        if (rolesCtx && window.rolesData) {
            // Distruggi chart esistente se presente
            const existingChart = Chart.getChart(rolesCtx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            // Crea nuovo chart e registralo
            this.charts.rolesChart = new Chart(rolesCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(window.rolesData),
                    datasets: [{
                        data: Object.values(window.rolesData),
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(59, 130, 246, 0.8)', 
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Chart spesa per anno
     */
    initSpesaAnnoChart() {
        const spesaCtx = document.getElementById('spesaAnnoChart');
        if (spesaCtx && window.spesaAnnoData) {
            // Distruggi chart esistente se presente
            const existingChart = Chart.getChart(spesaCtx);
            if (existingChart) {
                existingChart.destroy();
            }
            
            // Crea nuovo chart e registralo
            this.charts.spesaAnnoChart = new Chart(spesaCtx, {
                type: 'bar',
                data: {
                    labels: window.spesaAnnoData.labels || [],
                    datasets: [{
                        label: 'Spesa Media',
                        data: window.spesaAnnoData.data || [],
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        borderRadius: 8
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
                                callback: function(value) {
                                    return value.toLocaleString() + ' cr';
                                }
                            }
                        }
                    }
                }
            });
        }
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
                    const inactiveUsers = Math.max(0, 10 - activeUsers);
                    
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
        updateActiveUsers();
        setInterval(updateActiveUsers, 5000);
    }
    
    /**
     * Aggiunge campo tab nascosto a tutti i form
     */
    addTabToForms() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'dashboard';
        
        document.querySelectorAll('form').forEach(form => {
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
        if (window.location.search.includes('tab=dashboard') || !window.location.search.includes('tab=')) {
            this.autoRefreshInterval = setInterval(() => {
                this.refreshDashboardStats();
            }, 30000);
        }
    }
    
    async refreshDashboardStats() {
        try {
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
        document.querySelectorAll('.admin-table').forEach((table, index) => {
            const tableEl = table.querySelector('table');
            const tableId = tableEl?.id;
            if (tableId) {
                const tableName = tableId.replace('Table', '');
                if (this.dataTables[tableName]) {
                    this.addAdvancedSearch(tableId, this.dataTables[tableName]);
                }
            }
        });
    }
    
    addAdvancedSearch(tableId, dataTable) {
        const tableContainer = document.getElementById(tableId)?.closest('.admin-card');
        if (!tableContainer) return;
        
        const cardHeader = tableContainer.querySelector('.card-header');
        if (!cardHeader || cardHeader.querySelector('.table-search-container')) return;
        
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
        
        cardHeader.appendChild(searchContainer);
        
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
     * Inizializza DataTables
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
                // FIX: Gestione sicura delle righe
                try {
                    this.api().rows().every(function() {
                        const node = this.node();
                        if (node) {
                            $(node).addClass('modern-row');
                        }
                    });
                } catch (e) {
                    console.warn('Errore drawCallback DataTable:', e);
                }
            }
        };
        
        // Tabella Partecipanti con gestione migliorata righe nascoste
        if (document.getElementById('partecipantiTable')) {
            const hiddenRows = [];
            
            // FIX: Gestione sicura delle righe nascoste
            try {
                $('#partecipantiTable tbody tr').each(function() {
                    const id = $(this).attr('id');
                    if (id && id.startsWith('rose-row-')) {
                        const prevRow = $(this).prev();
                        if (prevRow.length > 0) {
                            hiddenRows.push({
                                element: $(this).detach(),
                                prevRow: prevRow
                            });
                        }
                    }
                });
                
                this.dataTables.partecipanti = $('#partecipantiTable').DataTable({
                    ...commonConfig,
                    columnDefs: [
                        { orderable: false, targets: [4, 5] }
                    ]
                });
                
                // Reinserisci le righe nascoste
                hiddenRows.forEach(item => {
                    if (item.prevRow && item.element) {
                        item.prevRow.after(item.element);
                    }
                });
                
            } catch (e) {
                console.error('Errore inizializzazione DataTable partecipanti:', e);
            }
        }
        
        // Altre tabelle con gestione errori
        ['competizioni', 'gallery', 'parametri', 'rose', 'calciatori'].forEach(type => {
            const tableEl = document.getElementById(`${type}Table`);
            if (tableEl) {
                try {
                    this.dataTables[type] = $(`#${type}Table`).DataTable({
                        ...commonConfig,
                        ...(type === 'calciatori' && {
                            order: [[1, 'asc']],
                            columnDefs: [{ orderable: false, targets: -1 }]
                        })
                    });
                } catch (e) {
                    console.error(`Errore inizializzazione DataTable ${type}:`, e);
                }
            }
        });
    }
    
    /**
     * Gestione sidebar responsiva
     */
    initSidebar() {
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<span class="material-icons">menu</span>';
        menuToggle.style.display = 'none';
        
        if (window.innerWidth <= 1024) {
            menuToggle.style.display = 'flex';
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
    
    /**
     * Gestione form migliorata
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
                    this.showNotification({
                        title: 'Errore validazione',
                        message: 'Compila tutti i campi richiesti',
                        type: 'error'
                    });
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
                    if (nuovaCompDiv) nuovaCompDiv.style.display = 'block';
                    if (nbaDiv) nbaDiv.style.display = 'none';
                } else if (e.target.value === 'NBA') {
                    if (nuovaCompDiv) nuovaCompDiv.style.display = 'none';
                    if (nbaDiv) nbaDiv.style.display = 'block';
                } else {
                    if (nuovaCompDiv) nuovaCompDiv.style.display = 'none';
                    if (nbaDiv) nbaDiv.style.display = 'none';
                }
            });
        }
    }
    
    /**
     * Azioni (toggle, delete, etc.)
     */
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
        
        // Mostra rose partecipante
        document.querySelectorAll('.show-rose').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const rowId = btn.dataset.row;
                const row = document.getElementById(rowId);
                
                if (row) {
                    const isVisible = row.style.display !== 'none';
                    row.style.display = isVisible ? 'none' : 'table-row';
                    btn.textContent = isVisible ? 'Mostra rose' : 'Nascondi rose';
                    
                    if (this.dataTables.partecipanti) {
                        setTimeout(() => {
                            this.dataTables.partecipanti.draw(false);
                        }, 100);
                    }
                }
            });
        });
    }
    
    async handleToggleVisibility(btn) {
        const type = btn.dataset.type;
        const id = btn.dataset.id;
        
        try {
            btn.disabled = true;
            const originalHTML = btn.innerHTML;
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
            btn.innerHTML = originalHTML;
        }
    }
    
    /**
     * Gestione tabs
     */
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
    
    /**
     * Funzionalità specifiche Gallery
     */
    initGalleryFeatures() {
        // Modal per vista completa immagine
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-full')) {
                const btn = e.target.closest('.view-full');
                const imgName = btn.dataset.img;
                this.showImageModal(imgName);
            }
        });
        
        // Toggle vista gallery
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.btn-view').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const view = btn.dataset.view;
                const galleryGrid = document.querySelector('.gallery-grid-view');
                if (galleryGrid) {
                    if (view === 'list') {
                        galleryGrid.style.display = 'none';
                        // Mostra vista tabella
                    } else {
                        galleryGrid.style.display = 'grid';
                    }
                }
            });
        });
    }
    
    showImageModal(imgName) {
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="image-modal-content">
                <img src="img/fotoGallery/${imgName}" alt="">
                <button class="image-modal-close">
                    <span class="material-icons">close</span>
                </button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close handlers
        modal.querySelector('.image-modal-close').addEventListener('click', () => {
            modal.remove();
        });
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        document.addEventListener('keydown', function keyHandler(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', keyHandler);
            }
        });
    }
    
    /**
     * Funzionalità specifiche Competizioni
     */
    initCompetitionFeatures() {
        // View matches e stats
        document.querySelectorAll('.view-matches, .view-stats').forEach(btn => {
            btn.addEventListener('click', () => {
                const competitionId = btn.dataset.competitionId;
                if (btn.classList.contains('view-matches')) {
                    this.showCompetitionMatches(competitionId);
                } else {
                    this.showCompetitionStats(competitionId);
                }
            });
        });
    }
    
    showCompetitionMatches(competitionId) {
        this.showNotification({
            title: 'Partite competizione',
            message: `Caricamento partite per competizione ${competitionId}`,
            type: 'info'
        });
    }
    
    showCompetitionStats(competitionId) {
        this.showNotification({
            title: 'Statistiche competizione',
            message: `Caricamento statistiche per competizione ${competitionId}`,
            type: 'info'
        });
    }
    
    /**
     * Funzionalità specifiche Parametri
     */
    initParametersFeatures() {
        // Edit parametri
        document.querySelectorAll('.edit-param').forEach(btn => {
            btn.addEventListener('click', () => {
                const paramId = btn.dataset.id;
                this.editParameter(paramId);
            });
        });
    }
    
    editParameter(paramId) {
        this.showNotification({
            title: 'Modifica parametro',
            message: `Funzionalità di modifica per parametro ${paramId} in sviluppo`,
            type: 'info'
        });
    }
    
    /**
     * Funzionalità specifiche Partecipanti
     */
    initParticipantsFeatures() {
        // Già gestito in initActions per show-rose
    }
    
    /**
     * Funzionalità specifiche Rose
     */
    initRoseFeatures() {
        // View details e export rosa
        document.querySelectorAll('.view-details, .export-rosa').forEach(btn => {
            btn.addEventListener('click', () => {
                const rosaId = btn.dataset.rosaId;
                if (btn.classList.contains('view-details')) {
                    this.viewRosaDetails(rosaId);
                } else {
                    this.exportRosa(rosaId);
                }
            });
        });
        
        // Filtro per anno
        const yearFilter = document.getElementById('yearFilter');
        if (yearFilter && this.dataTables.rose) {
            yearFilter.addEventListener('change', function() {
                const selectedYear = this.value;
                if (selectedYear) {
                    window.adminPanel.dataTables.rose.column(2).search(selectedYear).draw();
                } else {
                    window.adminPanel.dataTables.rose.column(2).search('').draw();
                }
            });
        }
    }
    
    viewRosaDetails(rosaId) {
        this.showNotification({
            title: 'Dettagli rosa',
            message: `Caricamento dettagli per rosa ${rosaId}`,
            type: 'info'
        });
    }
    
    exportRosa(rosaId) {
        this.showNotification({
            title: 'Export rosa',
            message: `Export rosa ${rosaId} completato`,
            type: 'success'
        });
    }
    
    /**
     * Funzionalità specifiche Calciatori
     */
    initCalciatoriFeatures() {
        // Funzionalità specifiche per la gestione calciatori
        // (al momento non ce ne sono di specifiche)
    }
    
    /**
     * Utility methods
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
        
        // FIX: Distruggi chart usando Chart.getChart() invece di Chart.instances
        document.querySelectorAll('canvas').forEach(canvas => {
            const chart = Chart.getChart(canvas);
            if (chart && typeof chart.destroy === 'function') {
                try {
                    chart.destroy();
                } catch (e) {
                    console.warn('Errore distruzione chart:', e);
                }
            }
        });
        
        // Pulisci anche il nostro registro se esiste
        if (this.charts) {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    try {
                        chart.destroy();
                    } catch (e) {
                        console.warn('Errore distruzione chart dal registro:', e);
                    }
                }
            });
            this.charts = {};
        }
        
        // Distruggi DataTables
        Object.values(this.dataTables).forEach(table => {
            if (table && typeof table.destroy === 'function') {
                try {
                    table.destroy();
                } catch (e) {
                    console.warn('Errore distruzione DataTable:', e);
                }
            }
        });
    }
}

// Export functions
window.exportData = (format, type) => {
    if (window.adminPanel && window.adminPanel.dataTables[type]) {
        const table = window.adminPanel.dataTables[type];
        const data = table.data().toArray();
        
        if (format === 'csv') {
            window.adminPanel.exportTableToCSV(`${type}Table`);
        }
    }
};

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

// Estende la classe EnhancedAdminPanel esistente
Object.assign(EnhancedAdminPanel.prototype, {

    /**
     * PRIORITÀ #4: Associazioni Parametri-Rose - Implementazione completa
     */
    initParametriAssociazioni() {
        // console.log('Inizializzazione gestione associazioni parametri...');
        
        // Gestione click sui bottoni di modifica associazione
        document.addEventListener('click', (e) => {
            if (e.target.closest('.association-edit-btn')) {
                const btn = e.target.closest('.association-edit-btn');
                this.openAssociationModal(btn);
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        // Gestione submit form associazioni
        const associationForm = document.getElementById('associationForm');
        if (associationForm) {
            associationForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveAssociations();
            });
        }
        
        // Gestione modal close
        document.addEventListener('click', (e) => {
            if (e.target.closest('.association-modal-close')) {
                this.closeAssociationModal();
            }
        });
        
        // Chiusura con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAssociationModal();
                this.closeEditParametroModal();
                this.closeDetailsModal();
            }
        });
    },

    openAssociationModal(btn) {
        const modal = document.getElementById('associationModal');
        if (!modal) {
            console.error('Modal associazioni non trovato');
            return;
        }
        
        const rosaId = btn.dataset.rosaId;
        const rosaName = btn.dataset.rosaName;
        const rosaYear = btn.dataset.rosaYear;
        const currentParams = btn.dataset.currentParams;
        
        // console.log('Apertura modal per rosa:', { rosaId, rosaName, rosaYear, currentParams });
        
        // Imposta informazioni rosa
        document.getElementById('rosaId').value = rosaId;
        document.getElementById('rosaInfo').innerHTML = `
            <div class="rosa-info-display">
                <strong>${rosaName}</strong>
                <span class="year-badge">${rosaYear}</span>
            </div>
        `;
        
        // Reset tutti i checkbox
        const checkboxes = document.querySelectorAll('#parametriCheckboxes input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Seleziona parametri attuali
        if (currentParams && currentParams.trim() !== '') {
            const currentParamIds = currentParams.split(',').map(id => id.trim());
            currentParamIds.forEach(id => {
                const checkbox = document.getElementById(`param_${id}`);
                if (checkbox) {
                    checkbox.checked = true;
                    // console.log('Selezionato parametro:', id);
                }
            });
        }
        
        // Mostra modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus sul primo checkbox per accessibilità
        const firstCheckbox = document.querySelector('#parametriCheckboxes input[type="checkbox"]');
        if (firstCheckbox) {
            setTimeout(() => firstCheckbox.focus(), 100);
        }
    },

    closeAssociationModal() {
        const modal = document.getElementById('associationModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    async saveAssociations() {
        const form = document.getElementById('associationForm');
        const formData = new FormData(form);
        
        // Mostra loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons spinning">refresh</span> Salvataggio...';
        
        try {
            // console.log('Invio dati associazioni...', Object.fromEntries(formData));
            
            const response = await fetch('php/updateAssociazioni.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });
            
            const result = await response.json();
            // console.log('Risposta server:', result);
            
            if (result.success) {
                this.showNotification({
                    title: 'Successo',
                    message: 'Associazioni aggiornate con successo',
                    type: 'success'
                });
                
                this.closeAssociationModal();
                
                // Ricarica la sezione associazioni
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                throw new Error(result.message || 'Errore nell\'aggiornamento');
            }
        } catch (error) {
            console.error('Errore salvataggio associazioni:', error);
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile aggiornare le associazioni: ' + error.message,
                type: 'error'
            });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    },

    /**
     * #1: Partecipanti - Mostra Rose
     */
    initParticipantsShowRose() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.show-rose')) {
                const btn = e.target.closest('.show-rose');
                const rowId = btn.dataset.row;
                this.toggleRoseRow(rowId, btn);
                e.preventDefault();
            }
        });
    },

    async toggleRoseRow(rowId, btn) {
        const row = document.getElementById(rowId);
        if (!row) {
            // Carica dinamicamente le rose se la riga non esiste
            await this.loadParticipantRose(btn);
            return;
        }
        
        const isVisible = row.style.display !== 'none';
        row.style.display = isVisible ? 'none' : 'table-row';
        
        // FIX: Controllo che l'icona esista prima di modificarla
        const icon = btn.querySelector('.material-icons');
        if (icon) {
            icon.textContent = isVisible ? 'expand_more' : 'expand_less';
        }
        
        // FIX: Aggiorna il title solo se il button ha la proprietà title
        if (btn.hasAttribute('title')) {
            btn.title = isVisible ? 'Mostra rose' : 'Nascondi rose';
        }
        
        // Aggiorna DataTable se presente
        if (this.dataTables.partecipanti) {
            setTimeout(() => {
                this.dataTables.partecipanti.draw(false);
            }, 100);
        }
    },

    async loadParticipantRose(btn) {
        const participantName = btn.dataset.participant || btn.closest('tr').querySelector('.team-name')?.textContent;
        if (!participantName) return;
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch(`php/getParticipantRose.php?participant=${encodeURIComponent(participantName)}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const result = await response.json();
            
            if (result.success) {
                this.insertRoseRow(btn, result.data);
            } else {
                throw new Error(result.message || 'Errore nel caricamento rose');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile caricare le rose: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    insertRoseRow(btn, roseData) {
        const currentRow = btn.closest('tr');
        const rowId = `rose-row-${roseData.participant.replace(/\s+/g, '-')}`;
        
        // Rimuovi riga esistente se presente
        const existingRow = document.getElementById(rowId);
        if (existingRow) existingRow.remove();
        
        // Crea nuova riga
        const newRow = document.createElement('tr');
        newRow.id = rowId;
        newRow.innerHTML = `
            <td colspan="6">
                <div class="rose-details">
                    <h5>Rose di ${roseData.participant}</h5>
                    <div class="rose-grid">
                        ${roseData.rose.map(rosa => `
                            <div class="rose-card">
                                <h6>Anno ${rosa.anno}</h6>
                                <div class="rose-stats">
                                    <span>${rosa.num_giocatori || 0} giocatori</span>
                                    <span>${rosa.crediti_totali ? rosa.crediti_totali.toLocaleString() : '0'} crediti</span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </td>
        `;
        
        // Inserisci dopo la riga corrente
        currentRow.insertAdjacentElement('afterend', newRow);
        
        // Aggiorna il pulsante - FIX: Assicura che l'icona esista
        btn.dataset.row = rowId;
        let icon = btn.querySelector('.material-icons');
        if (!icon) {
            // Crea l'icona se non esiste
            icon = document.createElement('span');
            icon.className = 'material-icons';
            btn.appendChild(icon);
        }
        icon.textContent = 'expand_less';
        btn.title = 'Nascondi rose';
    },

    /**
     * #2: Rose - Vedi Dettagli ed Export
     */
    initRoseActions() {
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.view-details')) {
                const btn = e.target.closest('.view-details');
                await this.handleViewRosaDetails(btn);
            }
            
            if (e.target.closest('.export-rosa')) {
                const btn = e.target.closest('.export-rosa');
                await this.handleExportRosa(btn);
            }
        });
    },

    async handleViewRosaDetails(btn) {
        const rosaId = btn.dataset.rosaId;
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch(`php/getRosaDetails.php?id=${rosaId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const result = await response.json();
            
            if (result.success) {
                this.showRosaDetailsModal(result.data);
            } else {
                throw new Error(result.message || 'Errore nel caricamento dettagli');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile caricare i dettagli: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    showRosaDetailsModal(data) {
        const modal = this.createModal(
            `Dettagli Rosa - ${data.nome_fantasquadra} (${data.anno})`,
            this.formatRosaDetails(data)
        );
        document.body.appendChild(modal);
        modal.style.display = 'flex';
    },

    formatRosaDetails(data) {
        if (!data || !data.giocatori) return '<p>Nessun dettaglio disponibile</p>';
        
        let html = `
            <div class="rosa-details-content">
                <div class="rosa-summary">
                    <h4>${data.nome_fantasquadra} - Anno ${data.anno}</h4>
                    <div class="rosa-stats">
                        <div class="stat-item">
                            <span class="stat-label">Giocatori:</span>
                            <span class="stat-value">${data.giocatori.length}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Crediti totali:</span>
                            <span class="stat-value">${data.crediti_totali.toLocaleString()} cr</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Media crediti:</span>
                            <span class="stat-value">${(data.crediti_totali / data.giocatori.length).toFixed(1)} cr</span>
                        </div>
                    </div>
                </div>
                <div class="rosa-players">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Giocatore</th>
                                <th>Ruolo</th>
                                <th>Squadra</th>
                                <th>Crediti</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.giocatori.forEach(g => {
            html += `
                <tr>
                    <td><strong>${g.nome_giocatore}</strong></td>
                    <td><span class="badge badge-role badge-${g.ruolo.toLowerCase()}">${g.ruolo}</span></td>
                    <td>${g.squadra_reale}</td>
                    <td><strong>${g.crediti_pagati} cr</strong></td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        return html;
    },

    async handleExportRosa(btn) {
        const rosaId = btn.dataset.rosaId;
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch(`php/exportRosa.php?id=${rosaId}&format=csv`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `rosa_${rosaId}_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                this.showNotification({
                    title: 'Export completato',
                    message: 'Rosa esportata con successo',
                    type: 'success'
                });
            } else {
                throw new Error('Errore nell\'export');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile esportare la rosa: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    /**
     * #3: Parametri - Modifica, Vedi Utilizzi, Elimina
     */
    initParametersActions() {
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.edit-param')) {
                const btn = e.target.closest('.edit-param');
                this.openEditParametroModal(btn);
            }
            
            if (e.target.closest('.view-usage')) {
                const btn = e.target.closest('.view-usage');
                await this.handleViewParametroUsage(btn);
            }
            
            if (e.target.closest('.delete-param')) {
                const btn = e.target.closest('.delete-param');
                await this.handleDeleteParametro(btn);
            }
        });
    },

    openEditParametroModal(btn) {
        const modal = document.getElementById('editParametroModal');
        if (!modal) {
            console.error('Modal edit parametro non trovato');
            return;
        }
        
        const parametroId = btn.dataset.id;
        const numero = btn.dataset.numero;
        const testo = btn.dataset.testo;
        
        document.getElementById('editParametroId').value = parametroId;
        document.getElementById('editNumeroParametro').value = numero;
        document.getElementById('editTestoParametro').value = testo;
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus sul campo numero
        setTimeout(() => {
            document.getElementById('editNumeroParametro').focus();
        }, 100);
    },

    closeEditParametroModal() {
        const modal = document.getElementById('editParametroModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    },

    async saveParametroEdit() {
        const form = document.getElementById('editParametroForm');
        const formData = new FormData(form);
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-icons spinning">refresh</span> Salvataggio...';
        
        try {
            const response = await fetch('php/updateParametro.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification({
                    title: 'Successo',
                    message: 'Parametro aggiornato con successo',
                    type: 'success'
                });
                
                this.closeEditParametroModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(result.message || 'Errore nell\'aggiornamento');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile aggiornare il parametro: ' + error.message,
                type: 'error'
            });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    },

    async handleViewParametroUsage(btn) {
        const parametroId = btn.dataset.id;
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch(`php/getParametroUsage.php?id=${parametroId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const result = await response.json();
            
            if (result.success) {
                this.showParametroUsageModal(result.data);
            } else {
                throw new Error(result.message || 'Errore nel caricamento utilizzi');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile caricare gli utilizzi: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    showParametroUsageModal(data) {
        const modal = this.createModal(
            `Utilizzi Parametro ${data.numero_parametro} - ${data.testo_parametro}`,
            this.formatParametroUsage(data)
        );
        document.body.appendChild(modal);
        modal.style.display = 'flex';
    },

    formatParametroUsage(data) {
        if (!data || !data.rose || data.rose.length === 0) {
            return '<p>Parametro non utilizzato in nessuna rosa</p>';
        }
        
        let html = `
            <div class="usage-content">
                <div class="usage-summary">
                    <p><strong>Utilizzi totali:</strong> ${data.rose.length} rose</p>
                </div>
                <div class="usage-list">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fantasquadra</th>
                                <th>Anno</th>
                                <th>Giocatori</th>
                                <th>Crediti</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        data.rose.forEach(r => {
            html += `
                <tr>
                    <td><strong>${r.nome_fantasquadra}</strong></td>
                    <td>${r.anno}</td>
                    <td>${r.num_giocatori || 'N/A'}</td>
                    <td>${r.crediti_totali ? r.crediti_totali.toLocaleString() + ' cr' : 'N/A'}</td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        return html;
    },

    async handleDeleteParametro(btn) {
        const parametroId = btn.dataset.id;
        const numero = btn.dataset.numero || 'questo parametro';
        
        if (!confirm(`Sei sicuro di voler eliminare il parametro ${numero}?\n\nQuesta azione non può essere annullata e eliminerà anche tutte le associazioni esistenti.`)) {
            return;
        }
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch('php/deleteParametro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id: parametroId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification({
                    title: 'Parametro eliminato',
                    message: 'Il parametro è stato eliminato con successo',
                    type: 'success'
                });
                
                setTimeout(() => location.reload(), 1000);
            } else {
                throw new Error(result.message || 'Errore nell\'eliminazione');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile eliminare il parametro: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    /**
     * #5: Competizioni - Vedi Partite e Statistiche
     */
    initCompetitionsActions() {
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.view-matches')) {
                const btn = e.target.closest('.view-matches');
                await this.handleViewMatches(btn);
            }
            
            if (e.target.closest('.view-stats')) {
                const btn = e.target.closest('.view-stats');
                await this.handleViewCompetitionStats(btn);
            }
        });
    },

    async handleViewMatches(btn) {
        const competitionId = btn.dataset.competitionId;
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch(`php/getCompetitionMatches.php?id=${competitionId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const result = await response.json();
            
            if (result.success) {
                this.showCompetitionMatchesModal(result.data);
            } else {
                throw new Error(result.message || 'Errore nel caricamento partite');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile caricare le partite: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    showCompetitionMatchesModal(data) {
        const modal = this.createModal(
            `Partite - ${data.competizione} (${data.anno})`,
            this.formatCompetitionMatches(data)
        );
        document.body.appendChild(modal);
        modal.style.display = 'flex';
    },

    formatCompetitionMatches(data) {
        if (!data || !data.partite || data.partite.length === 0) {
            return '<p>Nessuna partita trovata per questa competizione</p>';
        }
        
        let html = `
            <div class="matches-content">
                <div class="matches-summary">
                    <p><strong>Totale partite:</strong> ${data.partite.length}</p>
                </div>
                <div class="matches-list">
        `;
        
        data.partite.forEach(p => {
            // FIX: Gestisce campi che potrebbero essere null/undefined
            const squadraCasa = p.squadra_casa || 'TBD';
            const squadraOspite = p.squadra_ospite || 'TBD';
            const risultato = p.risultato || `${p.gol_casa || 0} - ${p.gol_trasferta || 0}`;
            const giornata = p.giornata || 'N/A';
            const tipologia = p.tipologia || '';
            const girone = p.girone ? ` (${p.girone})` : '';
            
            html += `
                <div class="match-item">
                    <div class="match-header">
                        <div class="match-teams">
                            <strong>${squadraCasa}</strong> vs <strong>${squadraOspite}</strong>
                        </div>
                        <div class="match-meta">
                            <span>Giornata ${giornata}</span>
                            ${tipologia ? `<span>${tipologia}${girone}</span>` : ''}
                        </div>
                    </div>
                    <div class="match-result">
                        <strong>${risultato}</strong>
                    </div>
                    <div class="match-scores">
                        <small>Punteggi: ${p.punteggio_casa || 0} - ${p.punteggio_trasferta || 0}</small>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
        
        return html;
    },

    async handleViewCompetitionStats(btn) {
        const competitionId = btn.dataset.competitionId;
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch(`php/getCompetitionStats.php?id=${competitionId}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const result = await response.json();
            
            if (result.success) {
                this.showCompetitionStatsModal(result.data);
            } else {
                throw new Error(result.message || 'Errore nel caricamento statistiche');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile caricare le statistiche: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    showCompetitionStatsModal(data) {
        const modal = this.createModal(
            `Statistiche - ${data.competizione} (${data.anno})`,
            this.formatCompetitionStats(data)
        );
        document.body.appendChild(modal);
        modal.style.display = 'flex';
    },

    formatCompetitionStats(data) {
        if (!data) return '<p>Nessuna statistica disponibile</p>';
        
        let html = `
            <div class="stats-content">
                <div class="stats-summary">
                    <h4>${data.nome_competizione || 'Competizione'} - Anno ${data.anno || 'N/A'}</h4>
                </div>
                <div class="stats-grid">
        `;
        
        if (data.stats && typeof data.stats === 'object') {
            Object.entries(data.stats).forEach(([key, value]) => {
                // FIX: Formatta i valori in modo sicuro
                let displayValue = value;
                if (typeof value === 'number') {
                    displayValue = key.toLowerCase().includes('media') ? 
                        value.toFixed(2) : 
                        value.toLocaleString();
                } else if (value === null || value === undefined) {
                    displayValue = 'N/A';
                }
                
                html += `
                    <div class="stat-item">
                        <div class="stat-label">${key}</div>
                        <div class="stat-value">${displayValue}</div>
                    </div>
                `;
            });
        }
        
        html += `
                </div>
            </div>
        `;
        
        return html;
    },

    /**
     * #6: Gallery - Modifica ed Elimina
     */
    initGalleryActions() {
        document.addEventListener('click', async (e) => {
            if (e.target.closest('.edit-img')) {
                const btn = e.target.closest('.edit-img');
                await this.handleEditImmagine(btn);
            }
            
            if (e.target.closest('.delete-img')) {
                const btn = e.target.closest('.delete-img');
                await this.handleDeleteImmagine(btn);
            }
        });
    },

    async handleEditImmagine(btn) {
        const imgId = btn.dataset.id;
        
        // Per ora usa prompt semplici, potresti migliorare con un modal
        const currentRow = btn.closest('tr');
        const currentName = currentRow?.cells[1]?.textContent.trim() || '';
        const currentDesc = currentRow?.cells[2]?.textContent.trim() || '';
        
        const newName = prompt('Nuovo nome per l\'immagine:', currentName);
        if (newName === null) return; // Annullato
        
        const newDesc = prompt('Nuova descrizione:', currentDesc);
        if (newDesc === null) return; // Annullato
        
        if (newName === currentName && newDesc === currentDesc) {
            this.showNotification({
                title: 'Nessuna modifica',
                message: 'Non hai apportato modifiche',
                type: 'info'
            });
            return;
        }
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch('php/updateImmagine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 
                    id: imgId, 
                    name: newName.trim(), 
                    description: newDesc.trim() 
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification({
                    title: 'Immagine aggiornata',
                    message: 'L\'immagine è stata aggiornata con successo',
                    type: 'success'
                });
                
                // Aggiorna le celle nella tabella
                if (currentRow) {
                    currentRow.cells[1].textContent = newName;
                    currentRow.cells[2].querySelector('.description-text').textContent = newDesc;
                }
            } else {
                throw new Error(result.message || 'Errore nell\'aggiornamento');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile aggiornare l\'immagine: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async handleDeleteImmagine(btn) {
        const imgId = btn.dataset.id;
        const currentRow = btn.closest('tr');
        const imgName = currentRow?.cells[1]?.textContent.trim() || 'questa immagine';
        
        if (!confirm(`Sei sicuro di voler eliminare "${imgName}"?\n\nQuesta azione non può essere annullata e rimuoverà definitivamente l'immagine dal server.`)) {
            return;
        }
        
        this.setButtonLoading(btn, true);
        
        try {
            const response = await fetch('php/deleteImmagine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id: imgId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification({
                    title: 'Immagine eliminata',
                    message: 'L\'immagine è stata eliminata con successo',
                    type: 'success'
                });
                
                // Rimuovi la riga dalla tabella
                if (currentRow) {
                    currentRow.remove();
                }
                
                // Aggiorna DataTable se presente
                if (this.dataTables.gallery) {
                    this.dataTables.gallery.draw();
                }
            } else {
                throw new Error(result.message || 'Errore nell\'eliminazione');
            }
        } catch (error) {
            this.showNotification({
                title: 'Errore',
                message: 'Impossibile eliminare l\'immagine: ' + error.message,
                type: 'error'
            });
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    /**
     * Utility Methods
     */
    setButtonLoading(btn, isLoading) {
        if (!btn) return; // FIX: Controlla che il button esista
        
        if (isLoading) {
            btn.classList.add('loading');
            btn.disabled = true;
            const icon = btn.querySelector('.material-icons');
            if (icon) {
                btn.dataset.originalIcon = icon.textContent;
                icon.textContent = 'refresh';
                icon.classList.add('spinning');
            }
        } else {
            btn.classList.remove('loading');
            btn.disabled = false;
            const icon = btn.querySelector('.material-icons');
            if (icon && btn.dataset.originalIcon) {
                icon.textContent = btn.dataset.originalIcon;
                icon.classList.remove('spinning');
                delete btn.dataset.originalIcon;
            }
        }
    },

    createModal(title, content) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" onclick="this.closest('.modal-overlay').remove(); document.body.style.overflow = '';">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
            </div>
        `;
        
        modal.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        `;
        
        const modalContent = modal.querySelector('.modal-content');
        modalContent.style.cssText = `
            background: var(--card-bg);
            border-radius: 20px;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
        `;
        
        const modalBody = modal.querySelector('.modal-body');
        modalBody.style.cssText = `
            padding: 2rem;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        `;
        
        document.body.style.overflow = 'hidden';
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
                document.body.style.overflow = '';
            }
        });
        
        return modal;
    },

    closeDetailsModal() {
        const modal = document.querySelector('.modal-overlay');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    }
});

// Inizializza tutte le nuove funzionalità quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    // Aspetta che l'admin panel sia inizializzato
    const initializeNewFeatures = () => {
        if (window.adminPanel) {
            window.adminPanel.initParametriAssociazioni();
            window.adminPanel.initParticipantsShowRose();
            window.adminPanel.initRoseActions();
            window.adminPanel.initParametersActions();
            window.adminPanel.initCompetitionsActions();
            window.adminPanel.initGalleryActions();
            
            // Aggiungi gestione submit per edit parametro se esiste il form
            const editParametroForm = document.getElementById('editParametroForm');
            if (editParametroForm) {
                editParametroForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    window.adminPanel.saveParametroEdit();
                });
            }
            
            // console.log('Tutte le funzionalità admin sono state inizializzate');
        } else {
            setTimeout(initializeNewFeatures, 100);
        }
    };
    
    initializeNewFeatures();
});