;(function () {
  const STORAGE_KEY = 'komera:theme';
  const DEFAULT_THEME = 'theme-ocean';
  const root = document.documentElement;
  const apply = (name) => {
    const cls = Array.from(root.classList).filter((c) => c.startsWith('theme-'));
    cls.forEach((c) => root.classList.remove(c));
    root.classList.add(name);
    try { localStorage.setItem(STORAGE_KEY, name); } catch (e) {}
    const ev = new CustomEvent('theme:change', { detail: { theme: name } });
    window.dispatchEvent(ev);
  };
  const get = () => {
    try { return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME; } catch (e) { return DEFAULT_THEME; }
  };
  const init = () => {
    apply(get());
    document.querySelectorAll('[data-theme-option]').forEach((el) => {
      el.addEventListener('click', () => {
        const name = el.getAttribute('data-theme-option');
        if (name) apply(name);
        const parent = el.closest('.theme-switcher');
        if (parent) parent.classList.remove('open');
      });
    });
    document.querySelectorAll('[data-theme-trigger]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const wrap = btn.closest('.theme-switcher');
        if (wrap) wrap.classList.toggle('open');
      });
    });
    document.addEventListener('click', (e) => {
      const t = e.target;
      document.querySelectorAll('.theme-switcher.open').forEach((el) => {
        if (!el.contains(t)) el.classList.remove('open');
      });
    });
  };
  window.ThemeManager = { setTheme: apply, getTheme: get };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

