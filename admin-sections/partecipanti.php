<?php
// admin-sections/partecipanti.php - PULITO
try {
    $stmt = $conn->prepare("
        SELECT f.*, 
               COUNT(r.id_rosa) as num_rose,
               MAX(r.anno) as ultimo_anno
        FROM fantasquadra f
        LEFT JOIN rosa r ON f.nome_fantasquadra = r.nome_fantasquadra
        GROUP BY f.nome_fantasquadra
        ORDER BY f.flag_attuale DESC, f.nome_fantasquadra ASC
    ");
    $stmt->execute();
    $partecipanti = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Errore nel recupero partecipanti: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Gestione Partecipanti</h1>
    <p class="section-subtitle">Amministra fantasquadre e fantallenatori</p>
</div>

<?php if (isset($_GET['check'])): ?>
    <div class="alert alert-success">
        <span class="material-icons">check_circle</span>
        <?= htmlspecialchars($_GET['check']) ?>
    </div>
<?php endif; ?>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="tab-btn active" data-tab="lista-partecipanti">
        <span class="material-icons">list</span>
        Lista Partecipanti
    </button>
    <button class="tab-btn" data-tab="nuovo-partecipante">
        <span class="material-icons">person_add</span>
        Nuovo Partecipante
    </button>
    <button class="tab-btn" data-tab="statistiche-partecipanti">
        <span class="material-icons">analytics</span>
        Statistiche
    </button>
</div>

<!-- Lista Partecipanti -->
<div id="lista-partecipanti" class="tab-content active">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">groups</span>
                Partecipanti Registrati
            </h3>
            <div class="btn-group">
                <button class="btn-modern btn-gradient-1" onclick="exportData('csv', 'partecipanti')">
                    <span class="material-icons">file_download</span>
                    Esporta CSV
                </button>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="admin-table">
                <table id="partecipantiTable" class="table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Fantasquadra</th>
                            <th>Fantallenatore</th>
                            <th>Rose</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partecipanti as $p): ?>
                            <tr>
                                <td>
                                    <div class="participant-avatar">
                                        <?php if ($p['immagine_fantallenatore']): ?>
                                            <img src="img/partecipanti/<?= htmlspecialchars($p['immagine_fantallenatore']) ?>" 
                                                 class="img-preview" alt="<?= htmlspecialchars($p['fantallenatore']) ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <span class="material-icons">person</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="team-info">
                                        <div class="team-name"><?= htmlspecialchars($p['nome_fantasquadra']) ?></div>
                                        <?php if ($p['scudetto']): ?>
                                            <img src="img/scudetti/<?= htmlspecialchars($p['scudetto']) ?>" 
                                                 class="team-badge" alt="Scudetto" title="Scudetto">
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="coach-name"><?= htmlspecialchars($p['fantallenatore']) ?></span>
                                </td>
                                <td>
                                    <div class="rose-stats">
                                        <span class="rose-count"><?= $p['num_rose'] ?> rose</span>
                                        <?php if ($p['ultimo_anno']): ?>
                                            <small class="last-year">Ultimo: <?= $p['ultimo_anno'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($p['flag_attuale'] == '1'): ?>
                                        <span class="badge badge-success">Attivo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inattivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="php/cambiaFlagPartecipante.php?nome_fantasquadra=<?= urlencode($p['nome_fantasquadra']) ?>&tab=partecipanti" 
                                           class="action-btn toggle-visibility" 
                                           title="Cambia stato">
                                            <span class="material-icons">
                                                <?= $p['flag_attuale'] == '1' ? 'visibility_off' : 'visibility' ?>
                                            </span>
                                        </a>
                                        <button class="action-btn show-rose" 
                                                data-row="rose-row-<?= htmlspecialchars($p['nome_fantasquadra']) ?>"
                                                title="Mostra rose">
                                            <span class="material-icons">list</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Rose Details Row (Initially Hidden) -->
                            <tr id="rose-row-<?= htmlspecialchars($p['nome_fantasquadra']) ?>" style="display: none;">
                                <td colspan="6">
                                    <div class="rose-details">
                                        <?php
                                        try {
                                            $stmt_rose = $conn->prepare("
                                                SELECT r.anno, COUNT(dr.id_giocatore) as num_giocatori,
                                                       SUM(dr.crediti_pagati) as crediti_totali
                                                FROM rosa r
                                                LEFT JOIN dettagli_rosa dr ON r.id_rosa = dr.id_rosa
                                                WHERE r.nome_fantasquadra = ?
                                                GROUP BY r.anno
                                                ORDER BY r.anno DESC
                                            ");
                                            $stmt_rose->execute([$p['nome_fantasquadra']]);
                                            $rose = $stmt_rose->fetchAll();
                                            
                                            if ($rose): ?>
                                                <h5>Rose per <?= htmlspecialchars($p['nome_fantasquadra']) ?></h5>
                                                <div class="rose-grid">
                                                    <?php foreach ($rose as $rosa): ?>
                                                        <div class="rose-card">
                                                            <h6>Anno <?= $rosa['anno'] ?></h6>
                                                            <div class="rose-stats">
                                                                <span><?= $rosa['num_giocatori'] ?> giocatori</span>
                                                                <span><?= number_format($rosa['crediti_totali']) ?> crediti</span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p>Nessuna rosa trovata</p>
                                            <?php endif;
                                        } catch (Exception $e) {
                                            echo "<p>Errore nel caricamento rose</p>";
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Nuovo Partecipante -->
<div id="nuovo-partecipante" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">person_add</span>
                Aggiungi Nuovo Partecipante
            </h3>
        </div>
        
        <form action="php/insertPartecipanti.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label class="form-label">Nome Fantasquadra *</label>
                <input type="text" name="nomeFantaSquadra" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nome Fantallenatore *</label>
                <input type="text" name="nomeFantallenatore" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Scudetto Fantasquadra</label>
                <div class="form-file">
                    <input type="file" name="scudettoFantaSquadra" accept="image/*">
                    <div class="form-file-label">
                        <span class="material-icons">image</span>
                        <span>Scegli file scudetto...</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Foto Fantallenatore</label>
                <div class="form-file">
                    <input type="file" name="fotoFantallenatore" accept="image/*">
                    <div class="form-file-label">
                        <span class="material-icons">person</span>
                        <span>Scegli foto fantallenatore...</span>
                    </div>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-modern btn-gradient-2">
                    <span class="material-icons">save</span>
                    Salva Partecipante
                </button>
                <button type="reset" class="btn-secondary">
                    <span class="material-icons">refresh</span>
                    Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Statistiche -->
<div id="statistiche-partecipanti" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">analytics</span>
                Statistiche Partecipanti
            </h3>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">groups</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= count(array_filter($partecipanti, fn($p) => $p['flag_attuale'] == '1')) ?></div>
                    <div class="stat-label">Partecipanti Attivi</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">pause_circle</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= count(array_filter($partecipanti, fn($p) => $p['flag_attuale'] != '1')) ?></div>
                    <div class="stat-label">Partecipanti Inattivi</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="material-icons">list_alt</span>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= array_sum(array_column($partecipanti, 'num_rose')) ?></div>
                    <div class="stat-label">Rose Totali</div>
                </div>
            </div>
        </div>
    </div>
</div>