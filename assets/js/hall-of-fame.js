/**
 * Script specifico per Hall of Fame.
 * Gestisco animazioni, contatori e filtri per lo storico competizioni.
 */

/**
 * Configurazione globale per Hall of Fame.
 * Definisco qui i parametri principali della pagina.
 */
const HallOfFameConfig = {
    animationDuration: 600,
    counterSpeed: 30,
    scrollThreshold: 0.1,
    maxCounterSteps: 50
};

/**
 * Cosa faccio: inizializzo tutti i sistemi della Hall of Fame quando la pagina è pronta.
 * Perché: voglio garantire che DOM e dipendenze siano caricate prima di partire.
 * Sequenza: theme → animazioni → contatori → filtri → eventi.
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    setupScrollAnimations(); 
    initializeCounters();
    setupCompetitionFilters();
    bindEventHandlers();
    
    // console.log('✅ Hall of Fame inizializzata correttamente');
});

/**
 * Cosa faccio: inizializzo il theme manager se disponibile.
 * Perché: voglio mantenere coerenza di tema con il resto dell'app.
 * Fallback: se ThemeManager non esiste, continuo senza errori.
 */
function initializeTheme() {
    try {
        if (window.ThemeManager) {
            new ThemeManager().init();
        }
    } catch (error) {
        console.warn('ThemeManager non disponibile:', error);
    }
}

/**
 * Cosa faccio: configuro l'Intersection Observer per le animazioni di scroll.
 * Input: elementi con classe 'animate-on-scroll'
 * Output: animazioni triggered quando entrano nel viewport
 * Nota: uso threshold e rootMargin per timing ottimale.
 */
function setupScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    if (!('IntersectionObserver' in window)) {
        // Fallback per browser senza supporto
        animatedElements.forEach(el => el.classList.add('visible'));
        return;
    }
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Animo una sola volta
            }
        });
    }, {
        threshold: HallOfFameConfig.scrollThreshold,
        rootMargin: '0px 0px -50px 0px'
    });
    
    animatedElements.forEach(el => observer.observe(el));
    
    // Mostro immediatamente il primo elemento
    if (animatedElements[0]) {
        animatedElements[0].classList.add('visible');
    }
}

/**
 * Cosa faccio: inizializzo i contatori animati per le statistiche.
 * Perché: voglio rendere i numeri più engaging con animazione progressiva.
 * Target: elementi con attributo data-count che contiene il valore finale.
 */
function initializeCounters() {
    const counters = document.querySelectorAll('.stat-value[data-count]');
    
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.animated) {
                entry.target.dataset.animated = 'true';
                const targetValue = parseInt(entry.target.getAttribute('data-count') || '0', 10);
                animateCounter(entry.target, targetValue);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => counterObserver.observe(counter));
}

/**
 * Cosa faccio: animo un singolo contatore da 0 al valore target.
 * Input: element (DOM), target (number)
 * Output: animazione numerica progressiva
 * Algoritmo: incremento lineare con setInterval.
 */
function animateCounter(element, target) {
    if (target === 0) {
        element.textContent = '0';
        return;
    }
    
    let current = 0;
    const increment = Math.max(1, Math.floor(target / HallOfFameConfig.maxCounterSteps));
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target.toString();
            clearInterval(timer);
        } else {
            element.textContent = current.toString();
        }
    }, HallOfFameConfig.counterSpeed);
}

/**
 * Cosa faccio: configuro il sistema di filtri per lo storico competizioni.
 * Perché: voglio permettere all'utente di filtrare e ordinare i risultati.
 * Stato: salvo in localStorage per persistenza tra sessioni.
 */
function setupCompetitionFilters() {
    const grid = document.getElementById('compGrid');
    if (!grid) return;

    const filterControls = {
        type: document.getElementById('filterType'),
        status: document.getElementById('filterStatus'), 
        sort: document.getElementById('sortBy'),
        onlyWinners: document.getElementById('onlyWinners')
    };
    
    // Ripristino stato salvato
    restoreFilterState(filterControls);
    
    // Applico filtri iniziali
    applyFilters(grid, filterControls);
    
    // Bind eventi per aggiornamenti real-time
    Object.values(filterControls).forEach(control => {
        if (control) {
            const eventType = control.type === 'checkbox' ? 'change' : 'change';
            control.addEventListener(eventType, () => {
                applyFilters(grid, filterControls);
                saveFilterState(filterControls);
            });
        }
    });
}

/**
 * Cosa faccio: applico i filtri selezionati alle card delle competizioni.
 * Input: grid (DOM), filterControls (Object)
 * Output: card visibili/nascoste + riordinate secondo criteri
 * Algoritmo: filter → sort → DOM manipulation.
 */
function applyFilters(grid, controls) {
    const cards = [...grid.children].filter(card => card.matches('.comp-card'));
    const filters = {
        type: controls.type?.value || 'all',
        status: controls.status?.value || 'all', 
        onlyWinners: controls.onlyWinners?.checked || false,
        sort: controls.sort?.value || 'season_desc'
    };
    
    let visibleCards = cards.filter(card => {
        const cardType = card.dataset.type;
        const cardStatus = card.dataset.status; 
        const isWinner = card.dataset.winner === '1';
        
        return (filters.type === 'all' || cardType === filters.type) &&
               (filters.status === 'all' || cardStatus === filters.status) &&
               (!filters.onlyWinners || isWinner);
    });
    
    // Ordino le card visibili
    sortCards(visibleCards, filters.sort);
    
    // Aggiorno DOM
    cards.forEach(card => {
        card.classList.toggle('is-hidden', !visibleCards.includes(card));
    });
    
    // Riordino nel DOM
    visibleCards.forEach(card => grid.appendChild(card));
}

/**
 * Cosa faccio: ordino le card secondo il criterio selezionato.
 * Input: cards (Array), sortType (string)
 * Output: array ordinato (modifica in place)
 * Criteri: season, points, avg (asc/desc).
 */
function sortCards(cards, sortType) {
    cards.sort((a, b) => {
        const aData = {
            season: +a.dataset.season,
            points: +a.dataset.points, 
            avg: +a.dataset.avg
        };
        const bData = {
            season: +b.dataset.season,
            points: +b.dataset.points,
            avg: +b.dataset.avg
        };
        
        switch (sortType) {
            case 'season_asc': return aData.season - bData.season;
            case 'season_desc': return bData.season - aData.season;
            case 'points_asc': return aData.points - bData.points;
            case 'points_desc': return bData.points - aData.points;
            case 'avg_asc': return aData.avg - bData.avg;
            case 'avg_desc': return bData.avg - aData.avg;
            default: return bData.season - aData.season;
        }
    });
}

/**
 * Cosa faccio: salvo lo stato corrente dei filtri in localStorage.
 * Perché: voglio mantenere le preferenze dell'utente tra sessioni.
 * Key: 'hof_comp_filters_v2' per evitare conflitti con altre pagine.
 */
function saveFilterState(controls) {
    const state = {
        type: controls.type?.value,
        status: controls.status?.value,
        sort: controls.sort?.value, 
        onlyWinners: controls.onlyWinners?.checked
    };
    
    try {
        localStorage.setItem('hof_comp_filters_v2', JSON.stringify(state));
    } catch (error) {
        console.warn('Impossibile salvare stato filtri:', error);
    }
}

/**
 * Cosa faccio: ripristino lo stato dei filtri salvato in localStorage.
 * Perché: voglio ricreare l'esperienza precedente dell'utente.
 * Fallback: se non trovo stato salvato, uso valori di default.
 */
function restoreFilterState(controls) {
    try {
        const saved = JSON.parse(localStorage.getItem('hof_comp_filters_v2') || '{}');
        
        if (saved.type && controls.type) controls.type.value = saved.type;
        if (saved.status && controls.status) controls.status.value = saved.status;
        if (saved.sort && controls.sort) controls.sort.value = saved.sort;
        if (typeof saved.onlyWinners === 'boolean' && controls.onlyWinners) {
            controls.onlyWinners.checked = saved.onlyWinners;
        }
    } catch (error) {
        console.warn('Impossibile ripristinare stato filtri:', error);
    }
}

/**
 * Cosa faccio: collego eventi aggiuntivi per interazioni UI.
 * Target: bottoni, link, elementi interattivi specifici della pagina.
 * Nota: uso event delegation dove possibile per performance.
 */
function bindEventHandlers() {
    // Gestisco click su trofei per possibili azioni future
    document.addEventListener('click', (event) => {
        const trophyCard = event.target.closest('.trophy-card');
        if (trophyCard) {
            handleTrophyClick(trophyCard, event);
        }
    });
    
    // Gestisco hover su statistiche per tooltip future
    const statCards = document.querySelectorAll('.stat-card, .enhanced-stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', handleStatHover);
        card.addEventListener('mouseleave', handleStatLeave);
    });
}

/**
 * Cosa faccio: gestisco il click su una card trofeo.
 * Input: trophyCard (Element), event (Event)
 * Output: azione specifica per tipo trofeo (placeholder per future features)
 * Nota: attualmente solo logging, ma preparato per espansioni.
 */
function handleTrophyClick(trophyCard, event) {
    const trophyName = trophyCard.querySelector('.trophy-title')?.textContent;
    const trophyType = [...trophyCard.classList].find(cls => 
        ['gold', 'silver', 'special'].includes(cls)
    );
    
    // console.log(`Trofeo cliccato: ${trophyName} (${trophyType})`);
    
    // Qui potrei aggiungere modal con dettagli, condivisione, etc.
}

/**
 * Cosa faccio: gestisco l'hover sulle statistiche.
 * Perché: preparo per tooltip o dettagli aggiuntivi.
 * Attualmente: solo feedback visivo, ma estendibile.
 */
function handleStatHover(event) {
    const card = event.currentTarget;
    card.style.transform = 'translateY(-2px) scale(1.02)';
}

function handleStatLeave(event) {
    const card = event.currentTarget;
    card.style.transform = '';
}

/**
 * Cosa faccio: fornisco utility per debug e sviluppo.
 * Export: funzioni principali per testing o estensioni future.
 */
window.HallOfFameUtils = {
    animateCounter,
    applyFilters,
    saveFilterState,
    restoreFilterState
};