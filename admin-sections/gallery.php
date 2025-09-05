<?php
// admin-sections/gallery.php - FIXED VERSION
try {
    $stmt = $conn->prepare("SELECT * FROM immagine ORDER BY id_immagine DESC");
    $stmt->execute();
    $immagini = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Errore nel recupero immagini: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Gestione Gallery</h1>
    <p class="section-subtitle">Amministra le immagini della gallery</p>
</div>

<!-- Tabs -->
<div class="admin-tabs">
    <button class="tab-btn active" data-tab="gallery-grid">
        <span class="material-icons">photo_library</span>
        Gallery Immagini
    </button>
    <button class="tab-btn" data-tab="carica-immagine">
        <span class="material-icons">add_photo_alternate</span>
        Carica Immagine
    </button>
    <button class="tab-btn" data-tab="gallery-tabella">
        <span class="material-icons">table_rows</span>
        Vista Tabella
    </button>
</div>

<!-- Gallery Grid -->
<div id="gallery-grid" class="tab-content active">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">photo_library</span>
                Immagini Gallery
            </h3>
            <div class="view-controls">
                <button class="btn-view active" data-view="grid" title="Vista griglia">
                    <span class="material-icons">grid_view</span>
                </button>
                <button class="btn-view" data-view="list" title="Vista elenco">
                    <span class="material-icons">view_list</span>
                </button>
            </div>
        </div>
        
        <div class="gallery-container grid-view">
            <!-- Vista Griglia -->
            <div class="gallery-grid-view">
                <?php foreach ($immagini as $img): ?>
                    <div class="gallery-item <?= $img['flag_visibile'] == '1' ? 'visible' : 'hidden' ?>">
                        <div class="gallery-item-image">
                            <img src="img/fotoGallery/<?= htmlspecialchars($img['nome_immagine']) ?>" 
                                 alt="<?= htmlspecialchars($img['descrizione_immagine']) ?>"
                                 loading="lazy">
                            <div class="gallery-item-overlay">
                                <div class="gallery-item-actions">
                                    <button class="action-btn view-full" 
                                            data-img="<?= htmlspecialchars($img['nome_immagine']) ?>"
                                            title="Vista completa">
                                        <span class="material-icons">fullscreen</span>
                                    </button>
                                    <a href="php/cambiaFlagImmagine.php?id_immagine=<?= $img['id_immagine'] ?>&tab=gallery" 
                                       class="action-btn toggle-visibility" 
                                       title="Cambia visibilità">
                                        <span class="material-icons">
                                            <?= $img['flag_visibile'] == '1' ? 'visibility_off' : 'visibility' ?>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="gallery-item-info">
                            <h5><?= htmlspecialchars($img['nome_immagine']) ?></h5>
                            <p><?= htmlspecialchars($img['descrizione_immagine']) ?></p>
                            <div class="gallery-item-status">
                                <?php if ($img['flag_visibile'] == '1'): ?>
                                    <span class="badge badge-success">Visibile</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nascosta</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Vista Lista -->
            <div class="gallery-list-view">
                <?php foreach ($immagini as $img): ?>
                    <div class="gallery-list-item <?= $img['flag_visibile'] == '1' ? 'visible' : 'hidden' ?>">
                        <div class="gallery-list-item-image">
                            <img src="img/fotoGallery/<?= htmlspecialchars($img['nome_immagine']) ?>" 
                                 alt="<?= htmlspecialchars($img['descrizione_immagine']) ?>"
                                 loading="lazy">
                        </div>
                        <div class="gallery-list-item-content">
                            <div class="gallery-list-item-title">
                                <?= htmlspecialchars($img['nome_immagine']) ?>
                            </div>
                            <div class="gallery-list-item-description">
                                <?= htmlspecialchars($img['descrizione_immagine']) ?>
                            </div>
                            <div class="gallery-list-item-meta">
                                <?php if ($img['flag_visibile'] == '1'): ?>
                                    <span class="badge badge-success">Visibile</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nascosta</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="gallery-list-item-actions">
                            <button class="action-btn view-full" 
                                    data-img="<?= htmlspecialchars($img['nome_immagine']) ?>"
                                    title="Vista completa">
                                <span class="material-icons">fullscreen</span>
                            </button>
                            <a href="php/cambiaFlagImmagine.php?id_immagine=<?= $img['id_immagine'] ?>&tab=gallery" 
                               class="action-btn toggle-visibility" 
                               title="Cambia visibilità">
                                <span class="material-icons">
                                    <?= $img['flag_visibile'] == '1' ? 'visibility_off' : 'visibility' ?>
                                </span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Carica Immagine -->
<div id="carica-immagine" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">add_photo_alternate</span>
                Carica Nuova Immagine
            </h3>
        </div>
        
        <form action="php/insertFoto.php" method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label class="form-label">Nome Immagine *</label>
                <input type="text" name="nomeFoto" class="form-control" required>
                <small class="form-text">Nome che apparirà nella gallery</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Descrizione</label>
                <textarea name="descFoto" class="form-control" rows="3" 
                          placeholder="Descrizione dell'immagine..."></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">File Immagine *</label>
                <div class="form-file">
                    <input type="file" name="fileImg" accept="image/*" required>
                    <div class="form-file-label">
                        <span class="material-icons">image</span>
                        <span>Scegli immagine...</span>
                    </div>
                </div>
                <small class="form-text">Formati supportati: JPG, PNG, GIF - Max 2MB</small>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-modern btn-gradient-1">
                    <span class="material-icons">cloud_upload</span>
                    Carica Immagine
                </button>
                <button type="reset" class="btn-secondary">
                    <span class="material-icons">refresh</span>
                    Reset
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Vista Tabella - FIXED WIDTH -->
<div id="gallery-tabella" class="tab-content">
    <div class="admin-card" style="width: 100% !important;">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">table_rows</span>
                Vista Tabella Gallery
            </h3>
        </div>
        
        <div class="admin-table" style="width: 100% !important;">
            <table id="galleryTable" class="table" style="width: 100% !important;">
                <thead>
                    <tr>
                        <th style="width: 80px;">Preview</th>
                        <th style="width: 200px;">Nome</th>
                        <th>Descrizione</th>
                        <th style="width: 120px;">Stato</th>
                        <th style="width: 120px;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($immagini as $img): ?>
                        <tr>
                            <td>
                                <img src="img/fotoGallery/<?= htmlspecialchars($img['nome_immagine']) ?>" 
                                     class="img-preview" alt="" loading="lazy"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                            </td>
                            <td>
                                <div class="table-cell-content">
                                    <?= htmlspecialchars($img['nome_immagine']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="description-text" style="max-width: none; white-space: normal;">
                                    <?= htmlspecialchars($img['descrizione_immagine']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($img['flag_visibile'] == '1'): ?>
                                    <span class="badge badge-success">Visibile</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nascosta</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="php/cambiaFlagImmagine.php?id_immagine=<?= $img['id_immagine'] ?>&tab=gallery" 
                                       class="action-btn toggle-visibility" 
                                       title="Cambia visibilità">
                                        <span class="material-icons">
                                            <?= $img['flag_visibile'] == '1' ? 'visibility_off' : 'visibility' ?>
                                        </span>
                                    </a>
                                    <button class="action-btn view-full" 
                                            data-img="<?= htmlspecialchars($img['nome_immagine']) ?>"
                                            title="Vista completa">
                                        <span class="material-icons">fullscreen</span>
                                    </button>
                                    <button class="action-btn edit-img" 
                                            data-id="<?= $img['id_immagine'] ?>"
                                            title="Modifica">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <button class="action-btn delete-img" 
                                            data-id="<?= $img['id_immagine'] ?>"
                                            title="Elimina">
                                        <span class="material-icons">delete</span>
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