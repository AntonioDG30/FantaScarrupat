/**
 * theme.js — FantaScarrupat
 *
 * Dark / light theme toggle.
 *
 * Public zone: always dark, toggle is disabled.
 * App zone:    dark by default, user can switch; preference stored in localStorage.
 *
 * Usage: include on every page. Reads data-zone="public"|"app" from <html>.
 * Sets data-theme="light"|"dark" on <html>.
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'fs-theme';
  const DARK  = 'dark';
  const LIGHT = 'light';
  const AUTO  = 'auto';

  /** The zone is stamped on <html data-zone="..."> by each _layout.php */
  function getZone() {
    return document.documentElement.dataset.zone || 'public';
  }

  /** Resolve the effective theme string for the current zone */
  function resolveTheme() {
    if (getZone() === 'public') return DARK;

    const stored = localStorage.getItem(STORAGE_KEY) || AUTO;

    if (stored === DARK  ) return DARK;
    if (stored === LIGHT ) return LIGHT;

    // AUTO: follow system preference; default to dark for the app zone
    return window.matchMedia('(prefers-color-scheme: light)').matches ? LIGHT : DARK;
  }

  /** Apply theme to <html> */
  function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    // Update toggle button icon(s) if present
    document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
      el.textContent = theme === DARK ? 'light_mode' : 'dark_mode';
    });
    document.querySelectorAll('[data-theme-label]').forEach(function (el) {
      el.textContent = theme === DARK ? 'Tema chiaro' : 'Tema scuro';
    });
  }

  /** Toggle between dark and light, persist choice */
  function toggle() {
    if (getZone() === 'public') return; // no-op for public zone
    const current = resolveTheme();
    const next    = current === DARK ? LIGHT : DARK;
    localStorage.setItem(STORAGE_KEY, next);
    applyTheme(next);
  }

  /** Set theme explicitly and persist */
  function setTheme(theme) {
    if (getZone() === 'public') return;
    if (theme === DARK || theme === LIGHT || theme === AUTO) {
      if (theme === AUTO) {
        localStorage.removeItem(STORAGE_KEY);
      } else {
        localStorage.setItem(STORAGE_KEY, theme);
      }
      applyTheme(resolveTheme());
    }
  }

  // ── Wire up toggle buttons on DOMContentLoaded ──────────────
  document.addEventListener('DOMContentLoaded', function () {

    // Any element with [data-action="toggle-theme"]
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-action="toggle-theme"]');
      if (btn) { toggle(); }
    });

    // Theme selector radio/select (data-action="set-theme" data-theme-value="dark|light|auto")
    document.addEventListener('change', function (e) {
      const el = e.target.closest('[data-action="set-theme"]');
      if (el) { setTheme(el.value || el.dataset.themeValue); }
    });
  });

  // ── React to system preference changes (for AUTO mode) ──────
  window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', function () {
    if (!localStorage.getItem(STORAGE_KEY)) {
      applyTheme(resolveTheme());
    }
  });

  // ── Initial application (runs immediately, before DOMContentLoaded) ──
  applyTheme(resolveTheme());

  // ── Public API ───────────────────────────────────────────────
  window.FSTheme = { toggle: toggle, set: setTheme, resolve: resolveTheme };

}());
