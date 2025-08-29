// Selector de idioma ES/EN preservando la URL actual
(function () {
  'use strict';

  function updateLangParam(url, newLang) {
    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set('lang', newLang);
      return u.toString();
    } catch (e) {
      const hasQuery = url.indexOf('?') !== -1;
      if (hasQuery) {
        if (/([?&])lang=([^&#]*)/.test(url)) {
          return url.replace(/([?&])lang=([^&#]*)/, `$1lang=${encodeURIComponent(newLang)}`);
        }
        return url + `&lang=${encodeURIComponent(newLang)}`;
      }
      return url + `?lang=${encodeURIComponent(newLang)}`;
    }
  }

  function goToLang(lang) {
    const normalized = lang === 'en' ? 'en' : 'es';
    const next = updateLangParam(window.location.href, normalized);
    window.location.href = next;
  }

  function initNativeSelectFallback() {
    const sel = document.getElementById('lang-select');
    if (!sel) return;
    sel.addEventListener('change', function () {
      goToLang(sel.value);
    });
  }

  function initCustomDropdown() {
    const root = document.getElementById('lang-dropdown');
    const trigger = document.getElementById('lang-dd-trigger');
    const panel = document.getElementById('lang-dd-panel');
    if (!root || !trigger || !panel) return;

    function open() {
      trigger.setAttribute('aria-expanded', 'true');
      panel.classList.add('open');
      // Igualar ancho al del trigger para aspecto de <select>
      try { panel.style.minWidth = trigger.getBoundingClientRect().width + 'px'; } catch (_) {}
      // Enfocar la opción seleccionada
      const current = root.getAttribute('data-current') || 'es';
      const opt = panel.querySelector(`[data-lang="${current}"]`);
      (opt || panel.querySelector('[role="option"]'))?.focus();
      document.addEventListener('click', onDocClick, { capture: true });
      document.addEventListener('keydown', onKeyDown);
    }

    function close() {
      trigger.setAttribute('aria-expanded', 'false');
      panel.classList.remove('open');
      document.removeEventListener('click', onDocClick, { capture: true });
      document.removeEventListener('keydown', onKeyDown);
    }

    function toggle() {
      const isOpen = trigger.getAttribute('aria-expanded') === 'true';
      isOpen ? close() : open();
    }

    function onDocClick(e) {
      if (!root.contains(e.target)) close();
    }

    function onKeyDown(e) {
      if (e.key === 'Escape') {
        close();
        trigger.focus();
      }
    }

    trigger.addEventListener('click', toggle);
    // Abrir con teclado: Enter, Space, ArrowDown
    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
        e.preventDefault();
        if (trigger.getAttribute('aria-expanded') !== 'true') {
          open();
        } else {
          // Si ya está abierto y se pulsa ArrowDown, mover foco a primera opción
          if (e.key === 'ArrowDown') {
            const first = panel.querySelector('li[role="option"]');
            first?.focus();
          }
        }
      }
    });

    panel.addEventListener('click', function (e) {
      const li = e.target.closest('li[role="option"]');
      if (!li) return;
      const lang = li.getAttribute('data-lang');
      goToLang(lang);
    });

    panel.addEventListener('keydown', function (e) {
      const options = Array.from(panel.querySelectorAll('li[role="option"]'));
      const currentIndex = options.indexOf(document.activeElement);
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        const next = options[Math.min(options.length - 1, currentIndex + 1)] || options[0];
        next.focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = options[Math.max(0, currentIndex - 1)] || options[options.length - 1];
        prev.focus();
      } else if (e.key === 'Home') {
        e.preventDefault();
        options[0]?.focus();
      } else if (e.key === 'End') {
        e.preventDefault();
        options[options.length - 1]?.focus();
      } else if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const li = document.activeElement.closest('li[role="option"]');
        if (li) {
          goToLang(li.getAttribute('data-lang'));
        }
      }
    });
  }

  function init() {
    initNativeSelectFallback();
    initCustomDropdown();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
