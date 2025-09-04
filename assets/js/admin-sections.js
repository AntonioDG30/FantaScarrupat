/**
 * Admin Sections JavaScript
 * Script separato per funzionalità specifiche delle sezioni admin
 */

class AdminSectionsManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.initImageModals();
        this.initRoleChart();
        this.initYearFilter();
        this.initGalleryViewToggle();
        this.initFormDynamics();
        console.log('Admin Sections Manager initialized');
    }
    
    /**
     * Modal per vista completa immagini gallery
     */
    initImageModals() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-full')) {
                const btn = e.target.closest('.view-full');
                const imgName = btn.dataset.img;
                this.showImageModal(imgName);
            }
        });
    }
    
    showImageModal(imgName) {
        // Rimuovi modal esistente se presente
        const existingModal = document.querySelector('.image-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="image-modal-content">
                <img src="img/fotoGallery/${imgName}" alt="Immagine gallery" loading="lazy">
                <button class="image-modal-close" title="Chiudi">
                    <span class="material-icons">close</span>
                </button>
            </div>
        `;
        
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
        
        // Animazione entrata
        setTimeout(() => {
            modal.style.opacity = '1';
        }, 10);
        
        // Event listeners per chiusura
        const closeBtn = modal.querySelector('.image-modal-close');
        closeBtn.addEventListener('click', () => this.closeImageModal(modal));
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeImageModal(modal);
            }
        });
        
        // Chiusura con ESC
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                this.closeImageModal(modal);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }
    
    closeImageModal(modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 300);
    }
    
    /**
     * Chart distribuzione ruoli calciatori
     */
    initRoleChart() {
        const ctx = document.getElementById('rolesChart');
        if (!ctx || typeof Chart === 'undefined') return;
        
        // Cerca dati dai meta tag o variabili globali
        let rolesData = {};
        
        // Prova a trovare i dati dalle righe della tabella calciatori
        const calciatoriTable = document.getElementById('calciatoriTable');
        if (calciatoriTable) {
            const rows = calciatoriTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const badgeRole = row.querySelector('.badge-role');
                if (badgeRole) {
                    const role = badgeRole.textContent.trim();
                    rolesData[role] = (rolesData[role] || 0) + 1;
                }
            });
        }
        
        if (Object.keys(rolesData).length === 0) return;
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(rolesData),
                datasets: [{
                    data: Object.values(rolesData),
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(59, 130, 246, 0.8)', 
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderWidth: 3,
                    borderColor: 'var(--card-bg)',
                    hoverBorderWidth: 4
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
                            padding: 20,
                            font: {
                                size: 14,
                                weight: '600'
                            },
                            color: 'var(--text-primary)'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'var(--card-bg)',
                        titleColor: 'var(--text-primary)',
                        bodyColor: 'var(--text-secondary)',
                        borderColor: 'var(--border-color)',
                        borderWidth: 1,
                        cornerRadius: 12,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.parsed / total) * 100);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                    easing: 'easeOutCubic'
                }
            }
        });
    }
    
    /**
     * Filtro per anno nelle rose
     */
    initYearFilter() {
        const yearFilter = document.getElementById('yearFilter');
        if (!yearFilter) return;
        
        yearFilter.addEventListener('change', function() {
            const selectedYear = this.value;
            const roseTable = document.getElementById('roseTable');
            if (!roseTable) return;
            
            // Se esiste DataTable, usa la sua API
            if (window.adminPanel && window.adminPanel.dataTables && window.adminPanel.dataTables.rose) {
                const table = window.adminPanel.dataTables.rose;
                if (selectedYear) {
                    table.column(2).search(selectedYear).draw();
                } else {
                    table.column(2).search('').draw();
                }
            } else {
                // Fallback: filtraggio manuale
                const rows = roseTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const yearCell = row.cells[2];
                    if (yearCell) {
                        const yearText = yearCell.textContent.trim();
                        if (!selectedYear || yearText.includes(selectedYear)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            }
        });
    }
    
    /**
     * Toggle vista gallery (grid/list)
     */
    initGalleryViewToggle() {
        const viewButtons = document.querySelectorAll('.btn-view');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                
                // Update active button
                viewButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Update gallery view
                const galleryContainer = document.querySelector('.gallery-container');
                if (galleryContainer) {
                    if (view === 'grid') {
                        galleryContainer.classList.remove('list-view');
                        galleryContainer.classList.add('grid-view');
                    } else {
                        galleryContainer.classList.remove('grid-view');
                        galleryContainer.classList.add('list-view');
                    }
                }
            });
        });
    }
    
    /**
     * Dinamiche form (competizioni, ecc.)
     */
    initFormDynamics() {
        // Form competizioni: mostra/nascondi campi
        const competizioneSelect = document.getElementById('competizione');
        if (competizioneSelect) {
            competizioneSelect.addEventListener('change', (e) => {
                const nuovaCompDiv = document.getElementById('nuova_competizione');
                const nbaDiv = document.getElementById('nba_competizione');
                
                // Nascondi tutto prima
                if (nuovaCompDiv) nuovaCompDiv.style.display = 'none';
                if (nbaDiv) nbaDiv.style.display = 'none';
                
                const value = e.target.value;
                if (value === 'nuova_competizione' && nuovaCompDiv) {
                    nuovaCompDiv.style.display = 'block';
                } else if (value === 'NBA' && nbaDiv) {
                    nbaDiv.style.display = 'block';
                }
            });
        }
        
        // Validazione file upload in tempo reale
        document.querySelectorAll('input[type="file"]').forEach(fileInput => {
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                const maxSize = 2 * 1024 * 1024; // 2MB
                const allowedTypes = {
                    'fileImg': ['image/jpeg', 'image/png', 'image/gif'],
                    'fileCalciatori': ['text/csv'],
                    'fileRose': ['text/csv'],
                    'fileClaendario': ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']
                };
                
                const inputName = e.target.name;
                const allowed = allowedTypes[inputName] || [];
                
                // Controllo dimensione
                if (file.size > maxSize) {
                    this.showFileError(e.target, `File troppo grande. Massimo 2MB`);
                    e.target.value = '';
                    return;
                }
                
                // Controllo tipo
                if (allowed.length > 0 && !allowed.includes(file.type)) {
                    this.showFileError(e.target, `Tipo file non supportato`);
                    e.target.value = '';
                    return;
                }
                
                // Rimuovi errori se tutto ok
                this.clearFileError(e.target);
                
                // Aggiorna label con nome file
                this.updateFileLabel(e.target, file);
            });
        });
    }
    
    showFileError(input, message) {
        this.clearFileError(input);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'file-error';
        errorDiv.style.cssText = 'color: var(--error-color); font-size: 0.875rem; margin-top: 0.25rem; font-weight: 500;';
        errorDiv.innerHTML = `<span class="material-icons" style="font-size: 1rem; vertical-align: middle; margin-right: 0.25rem;">error</span>${message}`;
        
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.appendChild(errorDiv);
        }
        
        input.classList.add('error');
    }
    
    clearFileError(input) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            const errorDiv = formGroup.querySelector('.file-error');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
        input.classList.remove('error');
    }
    
    updateFileLabel(input, file) {
        const label = input.closest('.form-file')?.querySelector('.form-file-label span:last-child');
        if (label) {
            const size = (file.size / 1024).toFixed(1);
            label.textContent = `${file.name} (${size} KB)`;
        }
    }
    
    /**
     * Utility: Format file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Gestione spesa per anno chart (rose)
function initSpesaAnnoChart() {
    const ctx = document.getElementById('spesaAnnoChart');
    if (!ctx || typeof Chart === 'undefined') return;
    
    // Calcola dati dalla tabella rose esistente
    const roseTable = document.getElementById('roseTable');
    if (!roseTable) return;
    
    const data = {};
    const rows = roseTable.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const cells = row.cells;
        if (cells.length >= 5) {
            const anno = cells[2].textContent.trim();
            const crediti = parseFloat(cells[4].textContent.replace(/[^\d.-]/g, '')) || 0;
            
            if (!data[anno]) {
                data[anno] = { total: 0, count: 0 };
            }
            data[anno].total += crediti;
            data[anno].count += 1;
        }
    });
    
    const labels = Object.keys(data).sort();
    const avgSpending = labels.map(anno => Math.round(data[anno].total / data[anno].count));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Spesa Media',
                data: avgSpending,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
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
                    backgroundColor: 'var(--card-bg)',
                    titleColor: 'var(--text-primary)',
                    bodyColor: 'var(--text-secondary)',
                    borderColor: 'var(--border-color)',
                    borderWidth: 1,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(context) {
                            return `Spesa Media: ${context.parsed.y.toLocaleString()} crediti`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'var(--text-secondary)',
                        font: { weight: '500' },
                        callback: function(value) {
                            return value.toLocaleString() + ' cr';
                        }
                    },
                    grid: {
                        color: 'var(--border-light)',
                        borderDash: [5, 5]
                    }
                },
                x: {
                    ticks: {
                        color: 'var(--text-secondary)',
                        font: { weight: '600' }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutCubic'
            }
        }
    });
}

// Inizializza quando DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    const adminSections = new AdminSectionsManager();
    window.adminSections = adminSections;
    
    // Inizializza chart specifici dopo un delay per assicurare che tutto sia caricato
    setTimeout(() => {
        adminSections.initRoleChart();
        initSpesaAnnoChart();
    }, 500);
});

// Export per utilizzo esterno
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminSectionsManager;
}