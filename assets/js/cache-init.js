/**
 * Inizializzazione del sistema di cache
 * Deve essere caricato dopo cache-manager.js
 */

// Assicura che il cache manager sia disponibile
if (!window.cacheManager) {
    console.error('CacheManager not found! Make sure cache-manager.js is loaded first.');
}

// Funzione di inizializzazione
function initializeCacheSystem() {
    // console.log('Initializing cache system...');
    
    // Collega i bottoni admin se presenti
    bindAdminControls();
    
    // Inizializza il cache manager
    if (window.cacheManager) {
        // console.log('Starting cache manager initialization...');
        window.cacheManager.initialize().then(success => {
            // console.log('Cache manager initialization result:', success);
        }).catch(error => {
            console.error('Cache manager initialization failed:', error);
        });
    }
}

// Collega i controlli admin
function bindAdminControls() {
    // Bottone info cache
    const btnCacheInfo = document.getElementById('btnCacheInfo');
    if (btnCacheInfo) {
        // console.log('Binding cache info button...');
        btnCacheInfo.addEventListener('click', (e) => {
            e.preventDefault();
            // console.log('Cache info button clicked');
            if (window.cacheManager) {
                window.cacheManager.getCacheInfo();
            } else {
                console.error('CacheManager not available');
            }
        });
    }
    
    // Bottone rigenera cache
    const btnForceRefresh = document.getElementById('btnForceRefresh');
    if (btnForceRefresh) {
        // console.log('Binding force refresh button...');
        btnForceRefresh.addEventListener('click', (e) => {
            e.preventDefault();
            // console.log('Force refresh button clicked');
            if (window.cacheManager) {
                window.cacheManager.rebuildCache();
            } else {
                console.error('CacheManager not available');
                alert('Sistema cache non disponibile. Ricaricare la pagina.');
            }
        });
        
        // Debug: aggiungi classe per verificare che il bottone sia trovato
        btnForceRefresh.setAttribute('data-cache-bound', 'true');
        // console.log('Force refresh button bound successfully');
    } else {
        // console.log('Force refresh button not found (user might not be admin)');
    }
}

// Test funzione per verificare che tutto funzioni
function testCacheSystem() {
    // console.log('=== CACHE SYSTEM TEST ===');
    // console.log('CacheManager available:', !!window.cacheManager);
    // console.log('User is admin:', window.CURRENT_USER?.is_admin);
    // console.log('CSRF Token available:', !!window.csrfToken);
    
    const btnForceRefresh = document.getElementById('btnForceRefresh');
    // console.log('Force refresh button found:', !!btnForceRefresh);
    // console.log('Force refresh button bound:', btnForceRefresh?.getAttribute('data-cache-bound'));
    // console.log('Force refresh button disabled:', btnForceRefresh?.disabled);
    
    const btnCacheInfo = document.getElementById('btnCacheInfo');
    // console.log('Cache info button found:', !!btnCacheInfo);
    // console.log('Cache info button disabled:', btnCacheInfo?.disabled);
    
    // Test chiamata API
    if (window.cacheManager) {
        // console.log('Testing cache status API...');
        window.cacheManager.checkCacheStatus().then(status => {
            // console.log('Cache status:', status);
        }).catch(error => {
            console.error('Cache status error:', error);
        });
    }
    
    // console.log('========================');
}

// Inizializzazione quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCacheSystem);
} else {
    // DOM già caricato
    initializeCacheSystem();
}

// Funzione globale per test
window.testCacheSystem = testCacheSystem;

// Auto-test dopo 1 secondo (per debug)
setTimeout(() => {
    if (window.CURRENT_USER?.is_admin) {
        testCacheSystem();
    }
}, 1000);