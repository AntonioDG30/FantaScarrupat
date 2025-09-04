/**
 * Script specifico per la pagina profilo utente.
 * Gestisco animazioni, validazione form e interazioni avanzate.
 */

/**
 * Cosa faccio: inizializzo tutti i sistemi del profilo quando DOM è pronto.
 * Perché: voglio garantire che elementi e dipendenze siano caricate prima di partire.
 * Sequenza: theme → animazioni → form validation → eventi.
 */
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
    setupScrollAnimations();
    setupFormValidation();
    setupPasswordStrength();
    bindInteractionEvents();
    
    // console.log('✨ Profile page caricato con DATI REALI dal database!');
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
 * Cosa faccio: configuro Intersection Observer per animazioni scroll.
 * Target: elementi con classe 'animate-slide-in'
 * Output: animazioni triggered quando entrano nel viewport
 */
function setupScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, index * 150);
            }
        });
    }, observerOptions);
    
    // Osservo tutti gli elementi con animazione
    document.querySelectorAll('.animate-slide-in').forEach(el => {
        observer.observe(el);
    });
    
    // Mostro immediatamente i primi elementi
    setTimeout(() => {
        const firstElements = document.querySelectorAll('.animate-slide-in');
        firstElements.forEach((el, index) => {
            if (index < 2) {
                el.classList.add('visible');
            }
        });
    }, 100);
}

/**
 * Cosa faccio: configuro validazione real-time per i form.
 * Target: form di aggiornamento profilo e cambio password
 * Output: feedback visivo immediato e validazione avanzata
 */
function setupFormValidation() {
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');
    
    if (profileForm) {
        setupProfileFormValidation(profileForm);
    }
    
    if (passwordForm) {
        setupPasswordFormValidation(passwordForm);
    }
}

/**
 * Cosa faccio: valido il form profilo con regole specifiche.
 * Input: profileForm (DOM element)
 * Output: validazione real-time su username e campi richiesti
 */
function setupProfileFormValidation(form) {
    const usernameField = form.querySelector('#username');
    
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            validateUsername(this);
        });
        
        usernameField.addEventListener('blur', function() {
            validateUsername(this, true);
        });
    }
    
    /**
     * Cosa faccio: valido il campo username con regole specifiche.
     * Input: field (DOM), showError (boolean per mostrare errori)
     * Output: styling di validazione e messaggi di errore
     */
    function validateUsername(field, showError = false) {
        const value = field.value.trim();
        const isValid = value.length >= 3 && value.length <= 50 && /^[a-zA-Z0-9_-]+$/.test(value);
        
        applyFieldValidation(field, isValid, showError ? getUsernameErrorMessage(value) : '');
    }
    
    /**
     * Cosa faccio: genero messaggio errore specifico per username.
     * Input: value (string)
     * Output: messaggio errore appropriato o stringa vuota se valido
     */
    function getUsernameErrorMessage(value) {
        if (value.length < 3) return 'Username deve essere di almeno 3 caratteri';
        if (value.length > 50) return 'Username non può superare i 50 caratteri';
        if (!/^[a-zA-Z0-9_-]+$/.test(value)) return 'Username può contenere solo lettere, numeri, underscore e trattini';
        return '';
    }
}

/**
 * Cosa faccio: configuro validazione avanzata per form password.
 * Input: passwordForm (DOM element)
 * Output: validazione real-time con controllo match e forza
 */
function setupPasswordFormValidation(form) {
    const currentPassword = form.querySelector('#current_password');
    const newPassword = form.querySelector('#new_password');
    const confirmPassword = form.querySelector('#confirm_password');
    
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            validateNewPassword(this);
            if (confirmPassword && confirmPassword.value) {
                validatePasswordMatch(confirmPassword);
            }
        });
    }
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            validatePasswordMatch(this);
        });
    }
    
    /**
     * Cosa faccio: valido la nuova password con criteri di sicurezza.
     * Input: field (DOM element)
     * Output: styling validazione + aggiornamento barra forza
     */
    function validateNewPassword(field) {
        const password = field.value;
        const isValid = password.length >= 8;
        
        applyFieldValidation(field, isValid, isValid ? '' : 'Password deve essere di almeno 8 caratteri');
        updatePasswordStrength(password);
    }
    
    /**
     * Cosa faccio: valido che le password coincidano.
     * Input: confirmField (DOM element)
     * Output: styling validazione e custom validity message
     */
    function validatePasswordMatch(confirmField) {
        const newPassword = document.getElementById('new_password');
        const isMatch = newPassword && confirmField.value === newPassword.value;
        
        if (confirmField.value && !isMatch) {
            confirmField.setCustomValidity('Le password non corrispondono');
            applyFieldValidation(confirmField, false, 'Le password non corrispondono');
        } else {
            confirmField.setCustomValidity('');
            applyFieldValidation(confirmField, isMatch || !confirmField.value);
        }
    }
}

/**
 * Cosa faccio: applico styling di validazione a un campo form.
 * Input: field (DOM), isValid (boolean), errorMessage (string)
 * Output: border color e messaggio errore aggiornati
 */
function applyFieldValidation(field, isValid, errorMessage = '') {
    if (!field.value) {
        // Campo vuoto: stato neutro
        field.style.borderColor = '';
        clearFieldError(field);
        return;
    }
    
    if (isValid) {
        field.style.borderColor = '#22c55e';
        clearFieldError(field);
    } else {
        field.style.borderColor = '#ef4444';
        if (errorMessage) {
            showFieldError(field, errorMessage);
        }
    }
}

/**
 * Cosa faccio: mostro messaggio di errore sotto un campo.
 * Input: field (DOM), message (string)
 * Output: elemento errore creato o aggiornato
 */
function showFieldError(field, message) {
    let errorElement = field.parentNode.querySelector('.field-error');
    
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.style.cssText = 'color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem;';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
}

/**
 * Cosa faccio: rimuovo messaggio di errore da un campo.
 * Input: field (DOM element)
 * Output: elemento errore rimosso se presente
 */
function clearFieldError(field) {
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

/**
 * Cosa faccio: configuro indicatore forza password dinamico.
 * Target: campo new_password e barra di forza
 * Output: visualizzazione forza password in tempo reale
 */
function setupPasswordStrength() {
    const strengthFill = document.getElementById('passwordStrengthFill');
    if (!strengthFill) return;
    
    /**
     * Cosa faccio: aggiorno visualmente la forza della password.
     * Input: password (string)
     * Output: barra colorata e classe CSS appropriate
     * Algoritmo: calcolo basato su lunghezza, caratteri speciali, numeri, maiuscole
     */
    window.updatePasswordStrength = function(password) {
        let strength = 0;
        
        // Calcolo punteggio forza password
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        // Rimuovo classi precedenti
        strengthFill.className = 'password-strength-fill-enhanced';
        
        // Applico classe appropriata
        const strengthClasses = ['', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
        if (strength > 0 && strength <= 4) {
            strengthFill.classList.add(strengthClasses[strength]);
        }
    };
}

/**
 * Cosa faccio: collego eventi per interazioni UI avanzate.
 * Target: bottoni, hover effects, animazioni parametri ruota
 * Output: handlers attivi per tutte le interazioni
 */
function bindInteractionEvents() {
    // Gestisco hover sulle statistiche per feedback visivo
    const statCards = document.querySelectorAll('.stat-card-enhanced, .hero-stat');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // Animo la sezione parametri se presente
    setTimeout(() => {
        animateParametersSection();
    }, 1000);
}

/**
 * Cosa faccio: animo la sezione parametri ruota della fortuna.
 * Perché: voglio evidenziare questa sezione speciale con effetti
 * Output: background e border animati per attirare attenzione
 */
function animateParametersSection() {
    const parametriSection = document.querySelector('.profile-card:last-of-type');
    if (parametriSection && parametriSection.querySelector('.material-icons[style*="casino"]')) {
        // È la sezione parametri, aggiungo effetto speciale
        parametriSection.style.background = 'linear-gradient(135deg, rgba(255, 152, 0, 0.05), var(--card-bg))';
        parametriSection.style.border = '1px solid rgba(255, 152, 0, 0.2)';
        parametriSection.style.boxShadow = '0 8px 25px rgba(255, 152, 0, 0.1), var(--shadow-lg)';
    }
}

/**
 * Cosa faccio: resetto il form password ai valori iniziali.
 * Input: chiamata da bottone Annulla
 * Output: form svuotato e validazioni resettate
 */
function resetPasswordForm() {
    const passwordForm = document.getElementById('passwordForm');
    if (!passwordForm) return;
    
    passwordForm.reset();
    
    // Reset validazioni custom
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword) {
        confirmPassword.setCustomValidity('');
    }
    
    // Reset barra forza password
    const strengthFill = document.getElementById('passwordStrengthFill');
    if (strengthFill) {
        strengthFill.className = 'password-strength-fill-enhanced';
    }
    
    // Reset stili validazione
    const fields = passwordForm.querySelectorAll('.form-control-enhanced');
    fields.forEach(field => {
        field.style.borderColor = '';
        clearFieldError(field);
    });
}

// Espongo funzioni globali richieste dall'HTML
window.resetPasswordForm = resetPasswordForm;