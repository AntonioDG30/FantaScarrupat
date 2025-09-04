<?php
// admin-sections/competizioni.php  
try {
    $stmt = $conn->prepare("
        SELECT cd.*, c.tipologia,
               COUNT(pa.id_partita) as num_partite
        FROM competizione_disputata cd
        LEFT JOIN competizione c ON cd.nome_competizione = c.nome_competizione
        LEFT JOIN partita_avvessario pa ON cd.id_competizione_disputata = pa.id_competizione_disputata
        GROUP BY cd.id_competizione_disputata
        ORDER BY cd.anno DESC, cd.nome_competizione ASC
    ");
    $stmt->execute();
    $competizioni = $stmt->fetchAll();
    
    // Lista competizioni base
    $stmt = $conn->prepare("SELECT DISTINCT nome_competizione FROM competizione ORDER BY nome_competizione");
    $stmt->execute();
    $competizioni_base = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $error = "Errore nel recupero competizioni: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Gestione Competizioni</h1>
    <p class="section-subtitle">Amministra le competizioni e tornei</p>
</div>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="tab-btn active" data-tab="lista-competizioni">
        <span class="material-icons">emoji_events</span>
        Lista Competizioni
    </button>
    <button class="tab-btn" data-tab="nuova-competizione">
        <span class="material-icons">add_circle</span>
        Nuova Competizione
    </button>
    <button class="tab-btn" data-tab="hall-of-fame">
        <span class="material-icons">stars</span>
        Hall of Fame
    </button>
</div>

<!-- Lista Competizioni -->
<div id="lista-competizioni" class="tab-content active">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">emoji_events</span>
                Competizioni Disputate
            </h3>
        </div>
        
        <div class="admin-table">
            <table id="competizioniTable" class="table">
                <thead>
                    <tr>
                        <th>Competizione</th>
                        <th>Tipologia</th>
                        <th>Anno</th>
                        <th>Vincitore</th>
                        <th>Partite</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competizioni as $c): ?>
                        <tr>
                            <td>
                                <div class="competition-name">
                                    <?= htmlspecialchars($c['nome_competizione']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($c['tipologia']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="year-badge"><?= $c['anno'] ?></span>
                            </td>
                            <td>
                                <?php if ($c['vincitore']): ?>
                                    <div class="winner-info">
                                        <span class="material-icons trophy-icon">emoji_events</span>
                                        <?= htmlspecialchars($c['vincitore']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">In corso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="matches-count"><?= $c['num_partite'] ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="action-btn view-matches" 
                                            data-competition-id="<?= $c['id_competizione_disputata'] ?>"
                                            title="Vedi partite">
                                        <span class="material-icons">list</span>
                                    </button>
                                    <button class="action-btn view-stats" 
                                            data-competition-id="<?= $c['id_competizione_disputata'] ?>"
                                            title="Statistiche">
                                        <span class="material-icons">analytics</span>
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

<!-- Nuova Competizione -->
<div id="nuova-competizione" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">add_circle</span>
                Carica Nuova Competizione
            </h3>
        </div>
        
        <form action="php/insertCompetizione.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label class="form-label">Competizione *</label>
                <select name="competizione" class="form-select" id="competizione" required>
                    <option value="">Seleziona competizione...</option>
                    <?php foreach ($competizioni_base as $comp): ?>
                        <option value="<?= htmlspecialchars($comp) ?>">
                            <?= htmlspecialchars($comp) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="nuova_competizione">+ Nuova Competizione</option>
                </select>
            </div>
            
            <!-- Campi per nuova competizione (nascosti di default) -->
            <div id="nuova_competizione" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Nome Nuova Competizione *</label>
                    <input type="text" name="nomeCompetizione" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipologia *</label>
                    <select name="tipologia" class="form-select">
                        <option value="">Seleziona tipologia...</option>
                        <option value="A Calendario">A Calendario</option>
                        <option value="A Gruppi">A Gruppi</option>
                        <option value="Eliminazione Diretta">Eliminazione Diretta</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Anno *</label>
                <input type="number" name="anno" class="form-control" 
                       min="2020" max="2030" value="<?= date('Y') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">File Calendario *</label>
                <div class="form-file">
                    <input type="file" name="fileClaendario" accept=".xlsx,.xls" required>
                    <div class="form-file-label">
                        <span class="material-icons">upload_file</span>
                        <span>Scegli file calendario Excel...</span>
                    </div>
                </div>
            </div>
            
            <!-- Campo per NBA (nascosto di default) -->
            <div id="nba_competizione" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Secondo File Calendario NBA</label>
                    <div class="form-file">
                        <input type="file" name="fileClaendario2" accept=".xlsx,.xls">
                        <div class="form-file-label">
                            <span class="material-icons">upload_file</span>
                            <span>Scegli secondo file per NBA...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-modern btn-gradient-1">
                    <span class="material-icons">upload</span>
                    Carica Competizione
                </button>
                <button type="reset" class="btn-secondary">
                    <span class="material-icons">refresh</span>
                    Reset
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hall of Fame -->
<div id="hall-of-fame" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">stars</span>
                Hall of Fame
            </h3>
        </div>
        
        <?php
        try {
            // Top vincitori
            $stmt = $conn->prepare("
                SELECT vincitore, COUNT(*) as vittorie,
                       GROUP_CONCAT(CONCAT(nome_competizione, ' (', anno, ')') ORDER BY anno DESC SEPARATOR ', ') as competizioni_vinte
                FROM competizione_disputata 
                WHERE vincitore IS NOT NULL
                GROUP BY vincitore 
                ORDER BY vittorie DESC, vincitore ASC
            ");
            $stmt->execute();
            $hall_of_fame = $stmt->fetchAll();
        } catch (Exception $e) {
            $hall_of_fame = [];
        }
        ?>
        
        <div class="hall-of-fame-grid">
            <?php foreach ($hall_of_fame as $index => $winner): ?>
                <div class="fame-card rank-<?= $index + 1 ?>">
                    <div class="fame-rank">
                        <?php if ($index == 0): ?>
                            <span class="material-icons gold">emoji_events</span>
                        <?php elseif ($index == 1): ?>
                            <span class="material-icons silver">emoji_events</span>
                        <?php elseif ($index == 2): ?>
                            <span class="material-icons bronze">emoji_events</span>
                        <?php else: ?>
                            <span class="rank-number"><?= $index + 1 ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="fame-info">
                        <h4><?= htmlspecialchars($winner['vincitore']) ?></h4>
                        <div class="fame-stats">
                            <span class="victories"><?= $winner['vittorie'] ?> vittorie</span>
                        </div>
                        <div class="fame-competitions">
                            <?= htmlspecialchars($winner['competizioni_vinte']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.competition-name {
    font-weight: 600;
    color: var(--text-primary);
}

.winner-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.trophy-icon {
    color: #fbbf24;
    font-size: 1.2rem !important;
}

.matches-count {
    font-weight: 600;
    color: var(--primary-color);
}

.hall-of-fame-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.fame-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.fame-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    opacity: 0.8;
}

.fame-card.rank-1::before { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.fame-card.rank-2::before { background: linear-gradient(90deg, #9ca3af, #6b7280); }
.fame-card.rank-3::before { background: linear-gradient(90deg, #cd7c2f, #92400e); }

.fame-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--glass-shadow);
}

.fame-rank {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    flex-shrink: 0;
}

.fame-rank .gold { color: #fbbf24; font-size: 3rem !important; }
.fame-rank .silver { color: #9ca3af; font-size: 2.5rem !important; }
.fame-rank .bronze { color: #cd7c2f; font-size: 2rem !important; }

.rank-number {
    font-size: 2rem;
    font-weight: 900;
    color: var(--primary-color);
}

.fame-info h4 {
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    color: var(--text-primary);
}

.victories {
    background: var(--gradient-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.875rem;
}

.fame-competitions {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-top: 0.5rem;
    line-height: 1.4;
}
</style>