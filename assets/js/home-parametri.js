// Inizializza theme manager
document.addEventListener('DOMContentLoaded', () => {
    if (typeof ThemeManager !== 'undefined') {
        const tm = new ThemeManager();
        tm.init();
    }
    
    // Intersection Observer per animazioni
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, index * 100);
            }
        });
    }, observerOptions);
    
    // Osserva tutti gli elementi con animazione
    document.querySelectorAll('.fade-in-up').forEach(el => {
        observer.observe(el);
    });
    
    // Mostra immediatamente i primi elementi
    setTimeout(() => {
        const firstElements = document.querySelectorAll('.fade-in-up');
        firstElements.forEach((el, index) => {
            if (index < 2) {
                el.classList.add('visible');
            }
        });
    }, 100);
    
    // Animazione contatori
    function animateCounter(element, target) {
        const startValue = 0;
        const increment = target / 60;
        let currentValue = startValue;
        
        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= target) {
                element.textContent = target;
                clearInterval(counter);
            } else {
                element.textContent = Math.floor(currentValue);
            }
        }, 16);
    }
    
    // Avvia contatori quando entrano in vista
    const counters = document.querySelectorAll('.stat-value[data-count]');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                entry.target.classList.add('animated');
                const target = parseInt(entry.target.getAttribute('data-count'));
                animateCounter(entry.target, target);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => counterObserver.observe(counter));
});