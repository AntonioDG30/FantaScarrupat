
<?php
// admin-sections/parametri.php
try {
    $stmt = $conn->prepare("
        SELECT p.*, 
               COUNT(pu.id_rosa) as utilizzi
        FROM parametri_rosa p
        LEFT JOIN parametri_utilizzati pu ON p.id_parametro = pu.id_parametro
        GROUP BY p.id_parametro
        ORDER BY p.numero_parametro ASC
    ");
    $stmt->execute();
    $parametri = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Errore nel recupero parametri: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Gestione Parametri</h1>
    <p class="section-subtitle">Configurazione parametri per l'analisi delle rose</p>
</div>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="tab-btn active" data-tab="lista-parametri">
        <span class="material-icons">list</span>
        Lista Parametri
    </button>
    <button class="tab-btn" data-tab="nuovo-parametro">
        <span class="material-icons">add_circle</span>
        Nuovo Parametro
    </button>
    <button class="tab-btn" data-tab="associazioni">
        <span class="material-icons">link</span>
        Associazioni Rose
    </button>
</div>

<!-- Lista Parametri -->
<div id="lista-parametri" class="tab-content active">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">tune</span>
                Parametri Configurati
            </h3>
        </div>
        
        <div class="admin-table">
            <table id="parametriTable" class="table">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Descrizione</th>
                        <th>Utilizzi</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($parametri as $p): ?>
                        <tr>
                            <td>
                                <span class="param-number"><?= $p['numero_parametro'] ?></span>
                            </td>
                            <td>
                                <div class="param-description">
                                    <?= htmlspecialchars($p['testo_parametro']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="usage-badge"><?= $p['utilizzi'] ?> rose</span>
                            </td>
                            <td>
                                <?php if ($p['flag_visibile'] == '1'): ?>
                                    <span class="badge badge-success">Attivo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nascosto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="php/cambiaFlagParametro.php?id_parametro=<?= $p['id_parametro'] ?>&tab=parametri" 
                                       class="action-btn toggle-visibility" 
                                       title="Cambia visibilità">
                                        <span class="material-icons">
                                            <?= $p['flag_visibile'] == '1' ? 'visibility_off' : 'visibility' ?>
                                        </span>
                                    </a>
                                    <button class="action-btn edit-param" 
                                            data-id="<?= $p['id_parametro'] ?>"
                                            title="Modifica">
                                        <span class="material-icons">edit</span>
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

<!-- Nuovo Parametro -->
<div id="nuovo-parametro" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">add_circle</span>
                Aggiungi Nuovo Parametro
            </h3>
        </div>
        
        <form action="php/insertParametri.php" method="POST" class="admin-form">
            <div class="form-group">
                <label class="form-label">Numero Parametro *</label>
                <input type="number" name="numeroParametro" class="form-control" min="1" required>
                <small class="form-text">Numero identificativo univoco</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Descrizione Parametro *</label>
                <textarea name="descParametro" class="form-control" rows="3" required 
                          placeholder="Descrivi il criterio di valutazione..."></textarea>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-modern btn-gradient-4">
                    <span class="material-icons">save</span>
                    Salva Parametro
                </button>
                <button type="reset" class="btn-secondary">
                    <span class="material-icons">refresh</span>
                    Reset
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Associazioni -->
<div id="associazioni" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">link</span>
                Associazioni Parametri-Rose
            </h3>
        </div>
        
        <?php
        try {
            $stmt = $conn->prepare("
                SELECT r.nome_fantasquadra, r.anno, r.id_rosa,
                       GROUP_CONCAT(p.numero_parametro ORDER BY p.numero_parametro) as parametri_associati
                FROM rosa r
                LEFT JOIN parametri_utilizzati pu ON r.id_rosa = pu.id_rosa
                LEFT JOIN parametri_rosa p ON pu.id_parametro = p.id_parametro
                GROUP BY r.id_rosa
                ORDER BY r.anno DESC, r.nome_fantasquadra ASC
            ");
            $stmt->execute();
            $associazioni = $stmt->fetchAll();
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Errore nel caricamento associazioni</div>";
            $associazioni = [];
        }
        ?>
        
        <div class="associations-grid">
            <?php foreach ($associazioni as $a): ?>
                <div class="association-card">
                    <div class="association-header">
                        <h5><?= htmlspecialchars($a['nome_fantasquadra']) ?></h5>
                        <span class="year-badge"><?= $a['anno'] ?></span>
                    </div>
                    <div class="association-params">
                        <?php if ($a['parametri_associati']): ?>
                            <?php foreach (explode(',', $a['parametri_associati']) as $param): ?>
                                <span class="param-tag"><?= $param ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="no-params">Nessun parametro associato</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.param-number {
    font-weight: 700;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.param-description {
    max-width: 300px;
    line-height: 1.4;
}

.usage-badge {
    background: var(--glass-bg);
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
}

.associations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.association-card {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.association-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--glass-shadow);
}

.association-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.association-header h5 {
    margin: 0;
    font-weight: 700;
    color: var(--text-primary);
}

.year-badge {
    background: var(--gradient-primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.875rem;
}

.association-params {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.param-tag {
    background: var(--success-bg);
    color: var(--success-color);
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid var(--success-color);
}

.no-params {
    color: var(--text-muted);
    font-style: italic;
}
</style>