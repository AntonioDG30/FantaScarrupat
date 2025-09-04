/**
 * Script specifico per la pagina di login.
 * Gestisco animazioni, contatori statistiche e validazione form.
 */

/**
 * Cosa faccio: inizializzo tutti i sistemi del login quando DOM è pronto.
 * Perché: voglio garantire che elementi e dipendenze siano caricate prima di partire.
 * Sequenza: theme → animazioni → contatori → form → eventi.
 */
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
    setupAnimatedCounters();
    setupFormHandling();
    setupKeyboardHandlers();
    setupFormValidation();
    focusUsernameField();
    
    // console.log('🔐 Login page caricata con successo!');
});

/**
 * Cosa faccio: inizializzo il theme manager se disponibile.
 * Perché: voglio mantenere coerenza di tema con resto dell'app.
 */
function initializeTheme() {
    try {
        if (typeof ThemeManager !== 'undefined') {
            const tm = new ThemeManager();
            tm.init();
        }
    } catch (error) {
        console.warn('ThemeManager non disponibile:', error);
    }
}

/**
 * Cosa faccio: configuro i contatori animati per le statistiche sistema.
 * Target: elementi con attributo data-count
 * Output: animazione progressiva dei numeri da 0 al valore finale
 * Timing: delay di 800ms per permettere il caricamento visivo.
 */
function setupAnimatedCounters() {
    /**
     * Cosa faccio: animo un singolo contatore con incremento progressivo.
     * Input: element (DOM), target (number)
     * Output: aggiornamento textContent ogni 30ms
     * Algoritmo: incremento calcolato per completare in ~1.5 secondi.
     */
    function animateCounter(element, target) {
        const startValue = 0;
        const increment = target / 50; // 50 step per animazione fluida
        let currentValue = startValue;
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= target) {
                element.textContent = target;
                clearInterval(counter);
            } else {
                element.textContent = Math.floor(currentValue);
            }
        }, 30);
    }
    
    // Avvio contatori dopo breve delay per caricamento pagina
    setTimeout(() => {
        const counters = document.querySelectorAll('.stat-value[data-count]');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            if (target > 0) {
                counter.textContent = 0;
                animateCounter(counter, target);
            }
        });
    }, 800);
}

/**
 * Cosa faccio: gestisco il submit del form e stati loading.
 * Perché: voglio feedback visivo durante l'autenticazione.
 * Output: bottone disabilitato e spinner attivo durante submit.
 */
function setupFormHandling() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginIcon = document.getElementById('loginIcon');
    
    if (!loginForm || !loginBtn) return;
    
    // Variabile per prevenire submit multipli
    let isSubmitting = false;
    
    loginForm.addEventListener('submit', function(e) {
        // Prevengo submit multipli
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
        
        // Attivo stato loading
        loginBtn.classList.add('loading');
        loginBtn.disabled = true;
        if (loginIcon) {
            loginIcon.textContent = 'hourglass_empty';
        }
        
        // Fallback per rimuovere loading se non redirect
        setTimeout(() => {
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
            if (loginIcon) {
                loginIcon.textContent = 'login';
            }
            isSubmitting = false;
        }, 10000);
    });
}

/**
 * Cosa faccio: gestisco i tasti Enter per submit e visibilità password.
 * Perché: voglio UX ottimizzata per navigazione da tastiera.
 * Target: form fields e toggle password.
 */
function setupKeyboardHandlers() {
    // Gestisco Enter sui campi form
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const form = document.getElementById('loginForm');
            const activeElement = document.activeElement;
            
            if (activeElement && activeElement.form === form) {
                e.preventDefault();
                form.submit();
            }
        }
    });
}

/**
 * Cosa faccio: configuro la validazione real-time del form.
 * Target: campi username e password per feedback immediato
 * Output: stili e messaggi di validazione dinamici.
 */
function setupFormValidation() {
    const usernameField = document.getElementById('username');
    const passwordField = document.getElementById('password');
    
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            validateField(this, this.value.length >= 3);
        });
    }
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            validateField(this, this.value.length >= 6);
        });
    }
    
    /**
     * Cosa faccio: applico stili di validazione a un campo form.
     * Input: field (DOM), isValid (boolean)
     * Output: border color e icone di feedback
     */
    function validateField(field, isValid) {
        if (field.value === '') {
            // Campo vuoto: stato neutro
            field.style.borderColor = '';
            return;
        }
        
        if (isValid) {
            field.style.borderColor = '#22c55e';
        } else {
            field.style.borderColor = '#ef4444';
        }
    }
}

/**
 * Cosa faccio: focus automatico sul campo username dopo caricamento.
 * Perché: miglioro UX evitando click aggiuntivo per iniziare login.
 * Timing: 500ms delay per permettere animazioni complete.
 */
function focusUsernameField() {
    setTimeout(() => {
        const usernameField = document.getElementById('username');
        if (usernameField) {
            usernameField.focus();
        }
    }, 500);
}

/**
 * Cosa faccio: gestisco il toggle della visibilità password.
 * Input: click sul bottone eye icon
 * Output: campo password type=text/password + icona aggiornata
 */
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (!passwordField || !toggleIcon) return;
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.textContent = 'visibility_off';
    } else {
        passwordField.type = 'password';
        toggleIcon.textContent = 'visibility';
    }
}

// Espongo funzioni globali richieste dall'HTML
window.togglePassword = togglePassword;