<?php
// admin-sections/gallery.php
try {
    $stmt = $conn->prepare("
        SELECT * FROM immagine 
        ORDER BY id_immagine DESC
    ");
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
                <button class="btn-view active" data-view="grid">
                    <span class="material-icons">grid_view</span>
                </button>
                <button class="btn-view" data-view="list">
                    <span class="material-icons">view_list</span>
                </button>
            </div>
        </div>
        
        <div class="gallery-container">
            <div class="gallery-grid-view">
                <?php foreach ($immagini as $img): ?>
                    <div class="gallery-item <?= $img['flag_visibile'] == '1' ? 'visible' : 'hidden' ?>">
                        <div class="gallery-item-image">
                            <img src="img/fotoGallery/<?= htmlspecialchars($img['nome_immagine']) ?>" 
                                 alt="<?= htmlspecialchars($img['descrizione_immagine']) ?>">
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

<!-- Vista Tabella -->
<div id="gallery-tabella" class="tab-content">
    <div class="admin-card">
        <div class="card-header">
            <h3 class="card-title">
                <span class="material-icons">table_rows</span>
                Vista Tabella Gallery
            </h3>
        </div>
        
        <div class="admin-table">
            <table id="galleryTable" class="table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($immagini as $img): ?>
                        <tr>
                            <td>
                                <img src="img/fotoGallery/<?= htmlspecialchars($img['nome_immagine']) ?>" 
                                     class="img-preview" alt="">
                            </td>
                            <td><?= htmlspecialchars($img['nome_immagine']) ?></td>
                            <td>
                                <div class="description-text">
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
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.view-controls {
    display: flex;
    gap: 0.5rem;
}

.btn-view {
    width: 40px;
    height: 40px;
    border: 1px solid var(--border-color);
    background: var(--card-bg);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-view.active,
.btn-view:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.gallery-grid-view {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    padding: 2rem 0;
}

.gallery-item {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s ease;
    position: relative;
}

.gallery-item.hidden {
    opacity: 0.6;
    filter: grayscale(50%);
}

.gallery-item:hover {
    transform: translateY(-8px);
    box-shadow: var(--glass-shadow);
}

.gallery-item-image {
    position: relative;
    aspect-ratio: 16/12;
    overflow: hidden;
}

.gallery-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.gallery-item:hover .gallery-item-image img {
    transform: scale(1.05);
}

.gallery-item-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.gallery-item:hover .gallery-item-overlay {
    opacity: 1;
}

.gallery-item-actions {
    display: flex;
    gap: 1rem;
}

.gallery-item-actions .action-btn {
    width: 50px;
    height: 50px;
    background: white;
    color: var(--text-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.gallery-item-actions .action-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.gallery-item-info {
    padding: 1.5rem;
}

.gallery-item-info h5 {
    margin: 0 0 0.5rem 0;
    font-weight: 700;
    color: var(--text-primary);
}

.gallery-item-info p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0 0 1rem 0;
    line-height: 1.4;
}

.gallery-item-status {
    display: flex;
    justify-content: flex-end;
}

.description-text {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Modal per vista completa immagine */
.image-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.image-modal-content {
    max-width: 90vw;
    max-height: 90vh;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
}

.image-modal img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.image-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.image-modal-close:hover {
    background: rgba(0, 0, 0, 0.9);
}
</style>

<script>
// Modal per vista completa immagine
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-full')) {
            const btn = e.target.closest('.view-full');
            const imgName = btn.dataset.img;
            showImageModal(imgName);
        }
    });
    
    function showImageModal(imgName) {
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
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.remove();
            }
        });
    }
});
</script>