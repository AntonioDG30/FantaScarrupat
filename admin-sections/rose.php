<?php
// admin-sections/rose.php - PULITO
try {
    $stmt = $conn->prepare("
        SELECT r.*, f.fantallenatore,
               COUNT(dr.id_giocatore) as num_giocatori,
               SUM(dr.crediti_pagati) as crediti_totali,
               AVG(dr.crediti_pagati) as media_crediti
        FROM rosa r
        LEFT JOIN fantasquadra f ON r.nome_fantasquadra = f.nome_fantasquadra
        LEFT JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
        GROUP BY r.id_rosa
        ORDER BY r.anno DESC, r.nome_fantasquadra ASC
    ");
    $stmt->execute();
    $rose = $stmt->fetchAll();
    
    // Anni disponibili per filtro
    $stmt = $conn->prepare("SELECT DISTINCT anno FROM rosa ORDER BY anno DESC");
    $stmt->execute();
    $anni = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $error = "Errore nel recupero rose: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Gestione Rose</h1>
    <p class="section-subtitle">Database delle rose per ogni stagione</p>
</div>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="tab-btn active" data-tab="lista-rose">
        <span class="material-icons">list_alt</span>
        Lista Rose
    </button>
    <button class="tab-btn" data-tab="importa-rose">
        <span class="material-icons">file_upload</span>
        Importa Rose
    </button>
    <button class="tab-btn" data-tab="statistiche-rose">
        <span class="material-icons">analytics</span>
        Statistiche
    </button>
</div>

<!-- Lista Rose -->
<div id="lista-rose" class="tab-content active">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">list_alt</span>
                Rose per Stagione
            </h3>
            <div class="filter-controls">
                <select id="yearFilter" class="form-select">
                    <option value="">Tutti gli anni</option>
                    <?php foreach ($anni as $anno): ?>
                        <option value="<?= $anno ?>"><?= $anno ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="admin-table">
            <table id="roseTable" class="table">
                <thead>
                    <tr>
                        <th>Fantasquadra</th>
                        <th>Fantallenatore</th>
                        <th>Anno</th>
                        <th>Giocatori</th>
                        <th>Crediti Totali</th>
                        <th>Media Crediti</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rose as $r): ?>
                        <tr>
                            <td>
                                <div class="team-name"><?= htmlspecialchars($r['nome_fantasquadra']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($r['fantallenatore']) ?></td>
                            <td>
                                <span class="year-badge"><?= $r['anno'] ?></span>
                            </td>
                            <td>
                                <span class="players-count"><?= $r['num_giocatori'] ?></span>
                            </td>
                            <td>
                                <span class="credits-total"><?= number_format($r['crediti_totali']) ?> cr</span>
                            </td>
                            <td>
                                <span class="credits-avg"><?= number_format($r['media_crediti'], 1) ?> cr</span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="action-btn view-details" 
                                            data-rosa-id="<?= $r['id_rosa'] ?>"
                                            title="Vedi dettagli">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-btn export-rosa" 
                                            data-rosa-id="<?= $r['id_rosa'] ?>"
                                            title="Esporta rosa">
                                        <span class="material-icons">file_download</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Importa Rose -->
<div id="importa-rose" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">file_upload</span>
                Importa Rose da CSV
            </h3>
        </div>
        
        <div class="alert alert-info">
            <span class="material-icons">info</span>
            Il CSV deve contenere: Nome Fantasquadra, Codice Fantacalcio, Crediti Pagati
        </div>
        
        <form action="php/insertRose.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label class="form-label">File CSV Rose *</label>
                <div class="form-file">
                    <input type="file" name="fileRose" accept=".csv" required>
                    <div class="form-file-label">
                        <span class="material-icons">upload_file</span>
                        <span>Scegli file CSV...</span>
                    </div>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-modern btn-gradient-2">
                    <span class="material-icons">upload</span>
                    Importa Rose
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Statistiche -->
<div id="statistiche-rose" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">analytics</span>
                Statistiche Rose
            </h3>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">list_alt</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= count($rose) ?></div>
                    <div class="stat-label">Rose Totali</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">groups</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= array_sum(array_column($rose, 'num_giocatori')) ?></div>
                    <div class="stat-label">Giocatori Totali</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">payments</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format(array_sum(array_column($rose, 'crediti_totali'))) ?></div>
                    <div class="stat-label">Crediti Spesi</div>
                </div>
            </div>
        </div>
        
        <!-- Chart spesa media per anno -->
        <div class="admin-card" style="margin-top: 2rem;">
            <div class="card-header">
                <h4 class="card-title">Spesa Media per Anno</h4>
            </div>
            <div class="chart-container" style="height: 300px;">
                <canvas id="spesaAnnoChart"></canvas>
            </div>
        </div>
    </div>
</div>