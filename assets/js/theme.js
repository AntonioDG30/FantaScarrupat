/**
 * Theme Manager (Light/Dark)
 */
class ThemeManager{
  constructor(){
    this.storageKey = 'fantacalcio-theme';
    this.toggleButton = null;
    this.themeIcon = null;
    this.currentTheme = null;
    this.mediaQuery = null;
  }
  init(){
    this.toggleButton = document.getElementById('themeToggle');
    this.themeIcon = document.getElementById('themeIcon');
    if (!this.toggleButton || !this.themeIcon) return;
    this.setupMediaQuery();
    this.loadInitialTheme();
    this.toggleButton.addEventListener('click', () => this.toggle());
    this.toggleButton.addEventListener('keydown', e => { if (e.key==='Enter' || e.key===' ') { e.preventDefault(); this.toggle(); } });
    setTimeout(() => document.body.classList.add('theme-transition'), 100);
  }
  setupMediaQuery(){
    try{
      this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      if (this.mediaQuery.addEventListener) this.mediaQuery.addEventListener('change', e => this.systemChanged(e));
      else if (this.mediaQuery.addListener) this.mediaQuery.addListener(e => this.systemChanged(e));
    }catch{}
  }
  loadInitialTheme(){
    const saved = localStorage.getItem(this.storageKey);
    if (saved === 'light' || saved === 'dark'){ this.setTheme(saved); return; }
    this.setTheme(this.mediaQuery && this.mediaQuery.matches ? 'dark' : 'light');
  }
  toggle(){ this.setTheme(this.currentTheme === 'light' ? 'dark' : 'light'); this.save(); this.animate(); }
  setTheme(theme){
    document.documentElement.setAttribute('data-theme', theme);
    this.currentTheme = theme;
    this.themeIcon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    const title = theme === 'dark' ? 'Attiva tema chiaro' : 'Attiva tema scuro';
    this.toggleButton.title = title; this.toggleButton.setAttribute('aria-label', title);
  }
  save(){ try{ localStorage.setItem(this.storageKey, this.currentTheme); }catch{} }
  systemChanged(e){ if (!localStorage.getItem(this.storageKey)) this.setTheme(e.matches ? 'dark' : 'light'); }
  animate(){ try{ this.toggleButton.style.transform='scale(.95)'; setTimeout(()=>this.toggleButton.style.transform='',150);}catch{} }
}
