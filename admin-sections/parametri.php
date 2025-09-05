<?php
// admin-sections/parametri.php - ENHANCED VERSION
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
    
    // Lista tutti i parametri per il modal di editing
    $stmt = $conn->prepare("SELECT * FROM parametri_rosa ORDER BY numero_parametro ASC");
    $stmt->execute();
    $tuttiParametri = $stmt->fetchAll();
    
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
                                            data-numero="<?= $p['numero_parametro'] ?>"
                                            data-testo="<?= htmlspecialchars($p['testo_parametro']) ?>"
                                            title="Modifica parametro">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <button class="action-btn view-usage" 
                                            data-id="<?= $p['id_parametro'] ?>"
                                            title="Vedi utilizzi">
                                        <span class="material-icons">analytics</span>
                                    </button>
                                    <?php if ($p['utilizzi'] == 0): ?>
                                    <button class="action-btn delete-param" 
                                            data-id="<?= $p['id_parametro'] ?>"
                                            title="Elimina parametro">
                                        <span class="material-icons">delete</span>
                                    </button>
                                    <?php endif; ?>
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

<!-- Associazioni - ENHANCED VERSION -->
<div id="associazioni" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">link</span>
                Associazioni Parametri-Rose
            </h3>
            <div class="btn-group">
                <button class="btn-modern btn-gradient-2" onclick="window.adminPanel.refreshAssociations()">
                    <span class="material-icons">refresh</span>
                    Aggiorna
                </button>
            </div>
        </div>
        
        <?php
        try {
            $stmt = $conn->prepare("
                SELECT r.nome_fantasquadra, r.anno, r.id_rosa,
                       GROUP_CONCAT(p.numero_parametro ORDER BY p.numero_parametro) as parametri_associati,
                       GROUP_CONCAT(p.id_parametro ORDER BY p.numero_parametro) as parametri_ids
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
                    <div class="association-actions">
                        <button class="association-edit-btn" 
                                data-rosa-id="<?= $a['id_rosa'] ?>"
                                data-rosa-name="<?= htmlspecialchars($a['nome_fantasquadra']) ?>"
                                data-rosa-year="<?= $a['anno'] ?>"
                                data-current-params="<?= htmlspecialchars($a['parametri_ids'] ?? '') ?>"
                                title="Modifica associazioni">
                            <span class="material-icons">edit</span>
                        </button>
                    </div>
                    
                    <div class="association-header">
                        <h5><?= htmlspecialchars($a['nome_fantasquadra']) ?></h5>
                        <span class="year-badge"><?= $a['anno'] ?></span>
                    </div>
                    <div class="association-params">
                        <?php if ($a['parametri_associati']): ?>
                            <?php foreach (explode(',', $a['parametri_associati']) as $param): ?>
                                <span class="param-tag"><?= trim($param) ?></span>
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

<!-- Modal per Editing Associazioni -->
<div id="associationModal" class="association-modal" style="display: none;">
    <div class="association-modal-content">
        <div class="association-modal-header">
            <h3 class="association-modal-title">Modifica Associazioni Parametri</h3>
            <button class="association-modal-close" onclick="window.adminPanel.closeAssociationModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        
        <form id="associationForm" class="association-form">
            <input type="hidden" id="rosaId" name="rosaId">
            
            <div class="association-form-group">
                <div class="association-form-label">Rosa:</div>
                <div id="rosaInfo" class="rosa-info"></div>
            </div>
            
            <div class="association-form-group">
                <div class="association-form-label">Parametri Disponibili:</div>
                <div class="association-checkboxes" id="parametriCheckboxes">
                    <?php foreach ($tuttiParametri as $param): ?>
                        <div class="association-checkbox-item">
                            <input type="checkbox" 
                                   id="param_<?= $param['id_parametro'] ?>" 
                                   name="parametri[]" 
                                   value="<?= $param['id_parametro'] ?>">
                            <label for="param_<?= $param['id_parametro'] ?>" class="association-checkbox-label">
                                <strong><?= $param['numero_parametro'] ?></strong> - <?= htmlspecialchars($param['testo_parametro']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="association-form-actions">
                <button type="button" class="btn-secondary" onclick="window.adminPanel.closeAssociationModal()">
                    <span class="material-icons">close</span>
                    Annulla
                </button>
                <button type="submit" class="btn-modern btn-gradient-1">
                    <span class="material-icons">save</span>
                    Salva Associazioni
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal per Editing Parametro -->
<div id="editParametroModal" class="association-modal" style="display: none;">
    <div class="association-modal-content">
        <div class="association-modal-header">
            <h3 class="association-modal-title">Modifica Parametro</h3>
            <button class="association-modal-close" onclick="window.adminPanel.closeEditParametroModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        
        <form id="editParametroForm" class="association-form">
            <input type="hidden" id="editParametroId" name="parametroId">
            
            <div class="association-form-group">
                <label class="association-form-label">Numero Parametro *</label>
                <input type="number" id="editNumeroParametro" name="numeroParametro" 
                       class="form-control" min="1" required>
            </div>
            
            <div class="association-form-group">
                <label class="association-form-label">Descrizione *</label>
                <textarea id="editTestoParametro" name="testoParametro" 
                          class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="association-form-actions">
                <button type="button" class="btn-secondary" onclick="window.adminPanel.closeEditParametroModal()">
                    <span class="material-icons">close</span>
                    Annulla
                </button>
                <button type="submit" class="btn-modern btn-gradient-1">
                    <span class="material-icons">save</span>
                    Salva Modifiche
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Passa i dati dei parametri al JavaScript per le funzionalità avanzate
window.parametriData = <?= json_encode($tuttiParametri) ?>;
window.associazioniData = <?= json_encode($associazioni) ?>;
</script>