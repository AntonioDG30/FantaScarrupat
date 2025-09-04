// mobile-navbar.js - Sistema di navigazione mobile responsivo completo
// Versione: 2.0 - Navbar completa con gestione admin e responsive design

class MobileNavbar {
    constructor() {
        this.mobileToggle = null;
        this.mobileMenu = null;
        this.desktopNav = null;
        this.userDropdown = null;
        this.dropdownContent = null;
        this.isMenuOpen = false;
        this.isDropdownOpen = false;

        // Configurazione
        this.config = {
            breakpoint: 1024, // px - sotto questa larghezza attiva mobile
            animationDuration: 300,
            closeOnScroll: true,
            closeOnClickOutside: true,
            adminPages: ['findplayerbyparametri', 'checkmyteam'] // pagine con controlli admin
        };

        // Cache elementi
        this.elements = {
            statusBadge: null,
            cacheInfo: null,
            forceRefresh: null,
            themeToggle: null,
            userInfo: null
        };
    }

    ensureNavbarContainer() {
        if (!this.elements.navbar) {
            const nav = document.createElement('nav');
            nav.className = 'navbar';
            document.body.prepend(nav);
            this.elements.navbar = nav;
        }
    }

    preserveOriginalAdminControls() {
        // crea (se serve) un contenitore nascosto dove spostare gli elementi ORIGINALI
        const stashId = '_adminControlStash';
        let stash = document.getElementById(stashId);
        if (!stash) {
            stash = document.createElement('div');
            stash.id = stashId;
            stash.style.cssText = 'display:none !important;';
            document.body.appendChild(stash);
        }

        ['statusBadge', 'btnCacheInfo', 'btnForceRefresh'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== stash) {
            stash.appendChild(el); // sposta il nodo originale nel DOM (ma nascosto)
            }
        });
    }


    init() {
        this.cacheElements();              // leggi riferimenti correnti
        this.ensureNavbarContainer();      // assicurati che .navbar esista
        this.preserveOriginalAdminControls(); // sposta i 3 controlli nel “nascondiglio”
        this.createNavbarStructure();      // ora puoi ricostruire la navbar
        this.setupEventListeners();
        this.handleResize();
        this.updateUserProfile();
        // console.log('MobileNavbar v2.0 inizializzato');
    }



    cacheElements() {
        this.elements = {
            statusBadge: document.getElementById('statusBadge'),
            cacheInfo: document.getElementById('btnCacheInfo'),
            forceRefresh: document.getElementById('btnForceRefresh'),
            themeToggle: document.getElementById('themeToggle'),
            userInfo: document.querySelector('.user-info'),
            navbar: document.querySelector('.navbar')
        };
    }

    createNavbarStructure() {
        if (!this.elements.navbar) return;

        // Rimuovi navbar esistente e ricostruisci
        const navbar = this.elements.navbar;
        navbar.innerHTML = '';
        navbar.className = 'navbar modern-navbar';


        // Container principale
        const container = document.createElement('div');
        container.className = 'navbar-container';

        // Sezione sinistra - Brand e nome pagina
        const leftSection = document.createElement('div');
        leftSection.className = 'navbar-left';
        
        const brand = document.createElement('a');                  
        brand.className = 'navbar-brand';
        brand.href = this.getHomeUrl();                             
        brand.innerHTML = `
        <span class="material-icons brand-icon">analytics</span>
        <span class="brand-text">${this.getPageTitle()}</span>
        `;
        leftSection.appendChild(brand);

        // Sezione centrale - Controlli admin (solo desktop e pagine specifiche)
        const centerSection = document.createElement('div');
        centerSection.className = 'navbar-center';
        
        if (this.shouldShowAdminControls()) {
            this.createAdminControls(centerSection);
        }

        // Sezione destra - User controls
        const rightSection = document.createElement('div');
        rightSection.className = 'navbar-right';
        
        this.createUserControls(rightSection);
        this.createMobileToggle(rightSection);

        // Assembla navbar
        container.appendChild(leftSection);
        container.appendChild(centerSection);
        container.appendChild(rightSection);
        navbar.appendChild(container);

        // Crea menu mobile
        this.createMobileMenu(navbar);

        // Inietta stili
        this.injectStyles();
    }

    getHomeUrl() {
        return typeof url === 'function' ? url('HomeParametri.php') : 'HomeParametri.php';
    }

    getPageTitle() {
        const path = window.location.pathname.toLowerCase();
        if (path.includes('findplayerbyparametri')) return 'Find Player';
        if (path.includes('checkmyteam')) return 'Check My Team';
        if (path.includes('homeparametri')) return 'Home';
        if (path.includes('profile')) return 'Profilo Utente';
        if (path.includes('halloffame')) return 'Hall of Fame';
        if (path.includes('sorteggioruota')) return 'Ruota della Fortuna';
        
        // Fallback da title
        const title = document.title;
        if (title.includes('FindPlayer')) return 'Find Player';
        if (title.includes('CheckMyTeam')) return 'Check My Team';
        
        return 'FantaScarrupat Analyzer';
    }

    shouldShowAdminControls() {
        if (!this.isAdminUser()) return false;
        
        const path = window.location.pathname.toLowerCase();
        return this.config.adminPages.some(page => path.includes(page));
    }

    createAdminControls(container) {
        const adminControls = document.createElement('div');
        adminControls.className = 'admin-controls';

        const statusWrapper = document.createElement('div');
        statusWrapper.className = 'status-wrapper';

        const cacheControls = document.createElement('div');
        cacheControls.className = 'cache-controls';

        // Prendo i riferimenti ORIGINALI (catturati in cacheElements())
        const originalStatus  = this.elements.statusBadge || document.getElementById('statusBadge');
        const originalCache   = this.elements.cacheInfo   || document.getElementById('btnCacheInfo');
        const originalRefresh = this.elements.forceRefresh|| document.getElementById('btnForceRefresh');

        // STATUS
        if (originalStatus) {
            // riusa l’originale
            originalStatus.id = 'statusBadge';
            originalStatus.classList.add('status-badge');
            statusWrapper.appendChild(originalStatus);
        } else {
            // fallback (se la pagina non lo aveva)
            const statusBadge = document.createElement('div');
            statusBadge.className = 'status-badge not-loaded';
            statusBadge.id = 'statusBadge';
            statusBadge.innerHTML = `
            <span class="material-icons status-icon">pending</span>
            <span class="status-text">Dati non caricati</span>
            `;
            statusWrapper.appendChild(statusBadge);
        }

        // CACHE INFO
        if (originalCache) {
            originalCache.id = 'btnCacheInfo';
            originalCache.classList.add('btn-admin-control','cache-info');
            cacheControls.appendChild(originalCache);
        } else {
            const cacheInfoBtn = document.createElement('button');
            cacheInfoBtn.className = 'btn-admin-control cache-info';
            cacheInfoBtn.id = 'btnCacheInfo';
            cacheInfoBtn.title = 'Informazioni cache';
            cacheInfoBtn.innerHTML = `
            <span class="material-icons">info</span>
            <span class="btn-text">Cache</span>
            `;
            cacheControls.appendChild(cacheInfoBtn);
        }

        // FORCE REFRESH
        if (originalRefresh) {
            originalRefresh.id = 'btnForceRefresh';
            originalRefresh.classList.add('btn-admin-control','refresh');
            cacheControls.appendChild(originalRefresh);
        } else {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'btn-admin-control refresh';
            refreshBtn.id = 'btnForceRefresh';
            refreshBtn.title = 'Forza aggiornamento';
            refreshBtn.innerHTML = `
            <span class="material-icons">refresh</span>
            <span class="btn-text">Aggiorna</span>
            `;
            cacheControls.appendChild(refreshBtn);
        }

        adminControls.appendChild(statusWrapper);
        adminControls.appendChild(cacheControls);
        container.appendChild(adminControls);
    }


    createUserControls(container) {
        const userControls = document.createElement('div');
        userControls.className = 'user-controls desktop-only';

        // Profilo utente
        const userProfile = document.createElement('div');
        userProfile.className = 'user-profile';
        userProfile.innerHTML = this.getUserProfileHTML();

        userProfile.tabIndex = 0;
        userProfile.role = 'button';
        userProfile.addEventListener('click', () => {
        window.location.href = this.getProfileUrl();
        });
        userProfile.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            window.location.href = this.getProfileUrl();
        }
        });
        
        // Theme toggle
        const themeToggle = document.createElement('button');
        themeToggle.className = 'theme-toggle';
        themeToggle.id = 'navThemeToggle';
        themeToggle.title = 'Cambia tema';
        themeToggle.innerHTML = `<span class="material-icons" id="navThemeIcon">dark_mode</span>`;

        // Logout
        const logoutBtn = document.createElement('a');
        logoutBtn.className = 'logout-btn';
        logoutBtn.title = 'Esci';
        logoutBtn.href = this.getLogoutUrl();
        logoutBtn.innerHTML = `<span class="material-icons">logout</span>`;
        logoutBtn.addEventListener('click', (e) => this.handleLogout(e));


        userControls.appendChild(userProfile);
        userControls.appendChild(themeToggle);
        userControls.appendChild(logoutBtn);
        container.appendChild(userControls);
    }

    createMobileToggle(container) {
        const mobileToggle = document.createElement('button');
        mobileToggle.className = 'mobile-toggle mobile-only';
        mobileToggle.innerHTML = `<span class="material-icons">menu</span>`;
        mobileToggle.setAttribute('aria-label', 'Menu mobile');
        
        this.mobileToggle = mobileToggle;
        container.appendChild(mobileToggle);
    }

    createMobileMenu(navbar) {
        const mobileMenu = document.createElement('div');
        mobileMenu.className = 'mobile-menu';

        // Sezione profilo
        const profileSection = document.createElement('div');
        profileSection.className = 'mobile-profile';
        profileSection.innerHTML = this.getUserProfileHTML(true);

        // Lista azioni
        const actionsList = document.createElement('div');
        actionsList.className = 'mobile-actions';

        // Profilo
        const profileAction = this.createMobileAction('person', 'Il mio profilo', this.getProfileUrl());
        actionsList.appendChild(profileAction);

        // Tema
        const themeAction = this.createMobileAction('dark_mode', 'Cambia tema', '#', (e) => this.handleThemeToggle(e));
        actionsList.appendChild(themeAction);

        // Admin controls se necessari
        if (this.shouldShowAdminControls()) {
            const divider = document.createElement('div');
            divider.className = 'mobile-divider';
            actionsList.appendChild(divider);

            const cacheAction = this.createMobileAction('info', 'Informazioni cache', '#', (e) => this.handleCacheInfo(e));
            const refreshAction = this.createMobileAction('refresh', 'Aggiorna dati', '#', (e) => this.handleForceRefresh(e));
            
            actionsList.appendChild(cacheAction);
            actionsList.appendChild(refreshAction);
        }

        // Logout
        const logoutAction = this.createMobileAction('logout', 'Esci', this.getLogoutUrl(), (e) => this.handleLogout(e));
        logoutAction.classList.add('logout-action');
        actionsList.appendChild(logoutAction);

        mobileMenu.appendChild(profileSection);
        mobileMenu.appendChild(actionsList);
        navbar.appendChild(mobileMenu);

        this.mobileMenu = mobileMenu;
    }

    createMobileAction(icon, text, href, onClick) {
        const action = document.createElement('a');
        action.href = href;
        action.className = 'mobile-action';
        action.innerHTML = `
            <span class="material-icons action-icon">${icon}</span>
            <span class="action-text">${text}</span>
        `;
        
        if (onClick) {
            action.addEventListener('click', onClick);
        } else if (href !== '#') {
            action.addEventListener('click', () => this.closeMobileMenu());
        }

        return action;
    }

    getUserProfileHTML(isMobile = false) {
        const user = this.getUserData();
        const avatarClass = isMobile ? 'user-avatar-mobile' : 'user-avatar';
        const detailsClass = isMobile ? 'user-details-mobile' : 'user-details';
        const avatarHtml = user.avatar_url
            ? `<img src="img/partecipanti/${user.avatar_url}" alt="" class="${avatarClass}" />`
            : `<div class="${avatarClass}">${(user.name || 'U').charAt(0).toUpperCase()}</div>`;

        return `
            ${avatarHtml}
            <div class="${detailsClass}">
            <div class="user-name">${user.name}</div>
            ${user.team ? `<div class="user-team">${user.team}</div>` : ''}
            </div>
        `;
    }

    getUserData() {
        const userNameEl = document.querySelector('.user-name');
        const userTeamEl = document.querySelector('.user-team');

        let name = userNameEl ? userNameEl.textContent.trim() : 'Utente';
        let team = userTeamEl ? userTeamEl.textContent.trim() : '';

        if (window.CURRENT_USER) {
            name = window.CURRENT_USER.username || name;
            team = window.CURRENT_USER.nome_fantasquadra || team;
        }

        return {
            name,
            team,
            avatar_url: (window.CURRENT_USER && window.CURRENT_USER.avatar_url) ? window.CURRENT_USER.avatar_url : ''
        };
    }

    syncAdminControls() {
        // Sincronizza con elementi originali
        const originalStatus = this.elements.statusBadge;
        const originalCache = this.elements.cacheInfo;
        const originalRefresh = this.elements.forceRefresh;

        const navStatus = document.getElementById('navStatusBadge');
        const navCache = document.getElementById('navCacheInfo');
        const navRefresh = document.getElementById('navForceRefresh');

        // Sincronizza status
        if (originalStatus && navStatus) {
            const updateStatus = () => {
                navStatus.className = originalStatus.className;
                navStatus.querySelector('.status-text').textContent = 
                    originalStatus.querySelector('span:last-child')?.textContent || 'Dati non caricati';
            };
            
            updateStatus();
            new MutationObserver(updateStatus).observe(originalStatus, {
                attributes: true,
                childList: true,
                subtree: true
            });
        }

        // Sincronizza eventi
        if (navCache && originalCache) {
            navCache.addEventListener('click', () => originalCache.click());
        }
        
        if (navRefresh && originalRefresh) {
            navRefresh.addEventListener('click', () => originalRefresh.click());
        }
    }

    setupEventListeners() {
        // Mobile menu toggle
        if (this.mobileToggle) {
            this.mobileToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleMobileMenu();
            });
        }

        // Theme toggle
        const themeBtn = document.getElementById('navThemeToggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', (e) => this.handleThemeToggle(e));
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.mobile-menu') && !e.target.closest('.mobile-toggle')) {
                this.closeMobileMenu();
            }
        });

        // Resize handler
        window.addEventListener('resize', () => this.handleResize());

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeMobileMenu();
        });

        // Close on scroll (mobile)
        let scrollTimer;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                if (window.innerWidth < this.config.breakpoint) {
                    this.closeMobileMenu();
                }
            }, 150);
        });
    }

    updateUserProfile() {
        // Aggiorna profilo se cambia
        const profileElements = document.querySelectorAll('.user-profile, .mobile-profile');
        profileElements.forEach(el => {
            el.innerHTML = this.getUserProfileHTML(el.classList.contains('mobile-profile'));
        });
    }

    // Menu methods
    toggleMobileMenu() {
        this.isMenuOpen ? this.closeMobileMenu() : this.openMobileMenu();
    }

    openMobileMenu() {
        if (!this.mobileMenu) return;
        
        this.mobileMenu.classList.add('show');
        this.mobileToggle.querySelector('.material-icons').textContent = 'close';
        this.isMenuOpen = true;
        
        document.body.style.overflow = 'hidden';
        
        // Focus primo elemento
        const firstAction = this.mobileMenu.querySelector('.mobile-action');
        if (firstAction) setTimeout(() => firstAction.focus(), 100);
    }

    closeMobileMenu() {
        if (!this.mobileMenu || !this.isMenuOpen) return;
        
        this.mobileMenu.classList.remove('show');
        this.mobileToggle.querySelector('.material-icons').textContent = 'menu';
        this.isMenuOpen = false;
        document.body.style.overflow = '';
    }

    // Event handlers
    handleThemeToggle(e) {
        e.preventDefault();
        
        // Trova e clicca il toggle originale
        const originalToggle = this.elements.themeToggle || 
                              document.querySelector('[data-theme-toggle]') ||
                              document.querySelector('.theme-toggle');
        
        if (originalToggle && originalToggle.click) {
            originalToggle.click();
            this.syncThemeIcon();
            return;
        }
        
        // Fallback
        this.toggleThemeFallback();
        this.closeMobileMenu();
    }

    handleCacheInfo(e) {
        e.preventDefault();
        const originalBtn = this.elements.cacheInfo;
        if (originalBtn) originalBtn.click();
        this.closeMobileMenu();
    }

    handleForceRefresh(e) {
        e.preventDefault();
        const originalBtn = this.elements.forceRefresh;
        if (originalBtn) originalBtn.click();
        this.closeMobileMenu();
    }

    handleLogout(e) {
        if (e) e.preventDefault();
        if (!confirm('Sei sicuro di voler uscire?')) return;

        const logoutUrl = this.getLogoutUrl(); // es. HomeParametri.php?action=logout&token=...
        this.closeMobileMenu();
        if (logoutUrl) {
            window.location.href = logoutUrl;   // <-- esci davvero
        }
    }


    syncThemeIcon() {
        const navIcon = document.getElementById('navThemeIcon');
        const originalIcon = this.elements.themeToggle?.querySelector('.material-icons');
        
        if (navIcon && originalIcon) {
            navIcon.textContent = originalIcon.textContent;
        }
    }

    toggleThemeFallback() {
        const html = document.documentElement;
        const current = html.getAttribute('data-theme') || 
                       (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';
        
        html.setAttribute('data-theme', next);
        try { localStorage.setItem('theme', next); } catch (_) {}
        
        // Aggiorna icone
        const icons = document.querySelectorAll('#navThemeIcon, #themeIcon');
        icons.forEach(icon => {
            icon.textContent = next === 'dark' ? 'light_mode' : 'dark_mode';
        });
    }

    handleResize() {
        if (window.innerWidth >= this.config.breakpoint) {
            this.closeMobileMenu();
            document.body.style.overflow = '';
        }
    }

    // Utility methods
    isAdminUser() {
        // Controlla più fonti per determinare se è admin
        const meta = document.querySelector('meta[name="is-admin"]');
        if (meta && (meta.content === '1' || meta.content === 'true')) return true;
        
        if (document.body.dataset.isAdmin === '1') return true;
        if (window.CURRENT_USER && window.CURRENT_USER.is_admin) return true;
        
        // Fallback: presenza di elementi admin
        return !!(this.elements.cacheInfo || this.elements.forceRefresh);
    }

    getProfileUrl() {
        return typeof url === 'function' ? url('profile.php') : 'profile.php';
    }

    getLogoutUrl() {
        const token = this.getCsrfToken();
        const baseUrl = typeof url === 'function' ? url('HomeParametri.php') : 'HomeParametri.php';
        return `${baseUrl}?action=logout&token=${encodeURIComponent(token)}`;
    }

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content || '';
        
        const input = document.querySelector('input[name="csrf_token"]');
        if (input) return input.value || '';
        
        if (typeof csrfToken !== 'undefined') return csrfToken;
        
        return '';
    }

    injectStyles() {
        if (document.getElementById('modern-navbar-styles')) return;

        const style = document.createElement('style');
        style.id = 'modern-navbar-styles';
        style.textContent = `
            /* Modern Navbar Styles */
            .modern-navbar {
                background: var(--card-bg, rgba(255, 255, 255, 0.95));
                backdrop-filter: blur(12px);
                border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
                position: sticky;
                top: 0;
                z-index: 1000;
                transition: all 0.3s ease;
            }

            .navbar-container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 12px 24px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
            }

            /* Brand Section */
            .navbar-left .navbar-brand {
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 700;
                font-size: 1.25rem;
                color: var(--text-primary, #1a1a1a);
                text-decoration: none;
            }

            .brand-icon {
                color: var(--primary-color, #3b82f6);
                font-size: 28px !important;
            }

            /* Admin Controls */
            .navbar-center {
                display: flex;
                align-items: center;
                gap: 20px;
                flex: 1;
                justify-content: center;
                max-width: 600px;
            }

            .admin-controls {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 8px 16px;
                background: var(--bg-secondary, rgba(0, 0, 0, 0.03));
                border-radius: 12px;
                border: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
            }

            .status-badge {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 0.875rem;
                font-weight: 600;
                transition: all 0.3s ease;
            }

            .status-badge.loaded {
                background: var(--success-bg, rgba(16, 185, 129, 0.1));
                color: var(--success-color, #059669);
            }

            .status-badge.not-loaded {
                background: var(--warning-bg, rgba(245, 158, 11, 0.1));
                color: var(--warning-color, #d97706);
            }

            .cache-controls {
                display: flex;
                gap: 8px;
            }

            .btn-admin-control {
                display: flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: var(--card-bg, white);
                border: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                border-radius: 8px;
                font-size: 0.875rem;
                color: var(--text-secondary, #6b7280);
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .btn-admin-control:hover {
                background: var(--hover-bg, rgba(0, 0, 0, 0.05));
                color: var(--text-primary, #1a1a1a);
                transform: translateY(-1px);
            }

            .btn-admin-control.refresh:hover {
                color: var(--primary-color, #3b82f6);
                border-color: var(--primary-color, #3b82f6);
            }

            /* User Controls */
            .navbar-right {
                display: flex;
                align-items: center;
                gap: 16px;
            }

            .user-controls {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .user-profile {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 6px 12px;
                border-radius: 12px;
                background: var(--bg-secondary, rgba(0, 0, 0, 0.03));
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .user-profile:hover {
                background: var(--hover-bg, rgba(0, 0, 0, 0.05));
            }

            .user-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: var(--primary-color, #3b82f6);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 0.875rem;
            }

            .user-details {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .user-name {
                font-weight: 600;
                font-size: 0.875rem;
                color: var(--text-primary, #1a1a1a);
                line-height: 1;
            }

            .user-team {
                font-size: 0.75rem;
                color: var(--text-secondary, #6b7280);
                line-height: 1;
            }

            .theme-toggle, .logout-btn {
                width: 40px;
                height: 40px;
                border: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                background: var(--card-bg, white);
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                color: var(--text-secondary, #6b7280);
            }

            .theme-toggle:hover, .logout-btn:hover {
                background: var(--hover-bg, rgba(0, 0, 0, 0.05));
                color: var(--text-primary, #1a1a1a);
                transform: translateY(-1px);
            }

            .logout-btn:hover {
                color: var(--error-color, #ef4444);
                border-color: var(--error-color, #ef4444);
            }

            /* Mobile Toggle */
            .mobile-toggle {
                width: 44px;
                height: 44px;
                border: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                background: var(--card-bg, white);
                border-radius: 12px;
                display: none;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                color: var(--text-primary, #1a1a1a);
            }

            .mobile-toggle:hover {
                background: var(--hover-bg, rgba(0, 0, 0, 0.05));
                transform: scale(1.05);
            }

            /* Mobile Menu */
            .mobile-menu {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--card-bg, white);
                border: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                border-top: none;
                border-radius: 0 0 16px 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                padding: 20px;
                z-index: 999;
                display: none;
                max-height: 70vh;
                overflow-y: auto;
            }

            .mobile-menu.show {
                display: block;
                animation: slideDown 0.3s ease-out;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .mobile-profile {
                padding: 16px;
                border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                margin-bottom: 16px;
            }

            .mobile-profile .user-avatar-mobile {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: var(--primary-color, #3b82f6);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 1.25rem;
                margin-bottom: 12px;
            }

            .user-details-mobile .user-name {
                font-weight: 700;
                font-size: 1.125rem;
                color: var(--text-primary, #1a1a1a);
                margin-bottom: 4px;
            }

            .user-details-mobile .user-team {
                font-size: 0.875rem;
                color: var(--text-secondary, #6b7280);
            }

            .mobile-actions {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .mobile-action {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                border-radius: 12px;
                text-decoration: none;
                color: var(--text-primary, #1a1a1a);
                transition: all 0.2s ease;
                font-weight: 500;
            }

            .mobile-action:hover {
                background: var(--hover-bg, rgba(0, 0, 0, 0.05));
                color: var(--text-primary, #1a1a1a);
                text-decoration: none;
            }

            .mobile-action.logout-action {
                color: var(--error-color, #ef4444);
                border-top: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
                margin-top: 8px;
                padding-top: 16px;
            }

            .mobile-action.logout-action:hover {
                background: var(--error-bg, rgba(239, 68, 68, 0.1));
            }

            .mobile-divider {
                height: 1px;
                background: var(--border-color, rgba(0, 0, 0, 0.1));
                margin: 8px 0;
            }

            /* Responsive */
            @media (max-width: 1024px) {
                .navbar-center {
                    display: none;
                }
                
                .user-controls.desktop-only {
                    display: none;
                }
                
                .mobile-toggle {
                    display: flex;
                }
                
                .navbar-container {
                    padding: 12px 16px;
                }
                
                .brand-text {
                    font-size: 1.125rem;
                }
            }

            @media (max-width: 768px) {
                .navbar-container {
                    padding: 10px 12px;
                    gap: 16px;
                }
                
                .brand-text {
                    display: none;
                }
                
                .mobile-menu {
                    padding: 16px;
                    border-radius: 0;
                    border-left: none;
                    border-right: none;
                }
            }

            /* Dark mode support */
            [data-theme="dark"] .modern-navbar {
                background: var(--card-bg-dark, rgba(17, 24, 39, 0.95));
                border-bottom-color: var(--border-color-dark, rgba(255, 255, 255, 0.1));
            }

            [data-theme="dark"] .admin-controls {
                background: var(--bg-secondary-dark, rgba(255, 255, 255, 0.05));
                border-color: var(--border-color-dark, rgba(255, 255, 255, 0.1));
            }

            [data-theme="dark"] .btn-admin-control {
                background: var(--card-bg-dark, #1f2937);
                border-color: var(--border-color-dark, rgba(255, 255, 255, 0.1));
                color: var(--text-secondary-dark, #9ca3af);
            }

            [data-theme="dark"] .btn-admin-control:hover {
                background: var(--hover-bg-dark, rgba(255, 255, 255, 0.1));
                color: var(--text-primary-dark, #f9fafb);
            }
        `;
        
        document.head.appendChild(style);
    }

    // Public methods
    destroy() {
        if (this.mobileMenu) this.mobileMenu.remove();
        document.body.style.overflow = '';
        const style = document.getElementById('modern-navbar-styles');
        if (style) style.remove();
        // console.log('MobileNavbar distrutto');
    }

    refresh() {
        this.updateUserProfile();
        this.syncAdminControls();
    }

    closeAll() {
        this.closeMobileMenu();
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    if (window.mobileNavbar) {
        console.warn('MobileNavbar già inizializzato, distruggendo istanza precedente');
        window.mobileNavbar.destroy();
    }
    
    const mobileNavbar = new MobileNavbar();
    mobileNavbar.init();
    window.mobileNavbar = mobileNavbar;
});

// Export per module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MobileNavbar;
}