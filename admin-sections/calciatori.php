<?php
// admin-sections/calciatori.php
try {
    $stmt = $conn->prepare("
        SELECT g.*, COUNT(dr.id_rosa) as utilizzi,
               AVG(dr.crediti_pagati) as media_crediti
        FROM giocatore g
        LEFT JOIN dettagli_rosa dr ON g.id_giocatore = dr.id_giocatore
        GROUP BY g.id_giocatore
        ORDER BY g.nome_giocatore ASC
    ");
    $stmt->execute();
    $calciatori = $stmt->fetchAll();
    
    // Statistiche ruoli
    $stmt = $conn->prepare("SELECT ruolo, COUNT(*) as count FROM giocatore GROUP BY ruolo");
    $stmt->execute();
    $statsRuoli = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    $error = "Errore nel recupero calciatori: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Gestione Calciatori</h1>
    <p class="section-subtitle">Database completo dei giocatori</p>
</div>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="tab-btn active" data-tab="lista-calciatori">
        <span class="material-icons">list</span>
        Lista Calciatori
    </button>
    <button class="tab-btn" data-tab="importa-calciatori">
        <span class="material-icons">file_upload</span>
        Importa CSV
    </button>
    <button class="tab-btn" data-tab="statistiche-calciatori">
        <span class="material-icons">analytics</span>
        Statistiche
    </button>
</div>

<!-- Lista Calciatori -->
<div id="lista-calciatori" class="tab-content active">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">sports_soccer</span>
                Database Calciatori
            </h3>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="admin-table">
                <table id="calciatoriTable" class="table">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Nome</th>
                            <th>Ruolo</th>
                            <th>Squadra</th>
                            <th>Utilizzi</th>
                            <th>Media Crediti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calciatori as $c): ?>
                            <tr>
                                <td><code><?= $c['codice_fantacalcio'] ?></code></td>
                                <td>
                                    <div class="player-name">
                                        <?= htmlspecialchars($c['nome_giocatore']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-role badge-<?= strtolower($c['ruolo']) ?>">
                                        <?= htmlspecialchars($c['ruolo']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($c['squadra_reale']) ?></td>
                                <td>
                                    <span class="usage-count"><?= $c['utilizzi'] ?></span>
                                </td>
                                <td>
                                    <?php if ($c['media_crediti']): ?>
                                        <span class="credits-avg"><?= number_format($c['media_crediti'], 1) ?> cr</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Importa Calciatori -->
<div id="importa-calciatori" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">file_upload</span>
                Importa Calciatori da CSV
            </h3>
        </div>
        
        <div class="alert alert-info">
            <span class="material-icons">info</span>
            Il file CSV deve contenere le colonne: Codice Fantacalcio, Nome, Ruolo, Squadra Reale
        </div>
        
        <form action="php/insertGiocatori.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label class="form-label">File CSV Calciatori *</label>
                <div class="form-file">
                    <input type="file" name="fileCalciatori" accept=".csv" required>
                    <div class="form-file-label">
                        <span class="material-icons">upload_file</span>
                        <span>Scegli file CSV...</span>
                    </div>
                </div>
                <small class="form-text">Formato supportato: CSV con separatore virgola</small>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-modern btn-gradient-3">
                    <span class="material-icons">upload</span>
                    Importa Calciatori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Statistiche -->
<div id="statistiche-calciatori" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">analytics</span>
                Statistiche Database
            </h3>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">sports_soccer</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= count($calciatori) ?></div>
                    <div class="stat-label">Calciatori Totali</div>
                </div>
            </div>
            
            <?php foreach ($statsRuoli as $ruolo => $count): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="material-icons">person</span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $count ?></div>
                        <div class="stat-label"><?= htmlspecialchars($ruolo) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Chart distribuzione ruoli -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div class="card-header">
                <h4 class="card-title">Distribuzione per Ruolo</h4>
            </div>
            <div class="chart-container" style="height: 300px;">
                <canvas id="rolesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
.badge-role {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge-p { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.badge-d { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
.badge-c { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.badge-a { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }

.player-name {
    font-weight: 600;
}

.usage-count {
    font-weight: 600;
    color: var(--primary-color);
}

.credits-avg {
    font-weight: 600;
    color: var(--success-color);
}
</style>

<script>
// Chart per distribuzione ruoli
document.addEventListener('DOMContentLoaded', function() {
    const rolesData = <?= json_encode($statsRuoli) ?>;
    const ctx = document.getElementById('rolesChart');
    
    if (ctx && Object.keys(rolesData).length > 0) {
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
});
</script>