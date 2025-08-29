// Componente de pestañas accesibles (ARIA)
(function(){
  'use strict';

  function afterLoad(cb){
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
  }

  function initTabs(root){
    if (!root) return;
    var tablist = root.querySelector('[role="tablist"]');
    if (!tablist) return;

    var tabs = Array.prototype.slice.call(tablist.querySelectorAll('[role="tab"]'));
    var panels = tabs.map(function(tab){ return document.getElementById(tab.getAttribute('aria-controls')); });
    var storageKey = 'tabs-active-' + (root.id || 'root');
    function scrollKey(i){ return storageKey + ':scroll:' + i; }

    var currentIdx = -1;
    function dbgEnabled(){
      try { return (new URLSearchParams(window.location.search)).get('debug') === 'assets'; } catch(_) { return false; }
    }
    function dbgLog(){ if (!dbgEnabled()) return; try { console.log.apply(console, arguments); } catch(_) {} }

    function getTabIdxFromUrl(){
      try {
        var usp = new URLSearchParams(window.location.search);
        var val = usp.get('tab');
        if (!val) return -1;
        // 1) si es un id de tab
        var byId = document.getElementById(val);
        if (byId) {
          var idxId = tabs.indexOf(byId);
          if (idxId >= 0) return idxId;
        }
        // 2) si es id de panel, resolver via aria-labelledby
        var panel = document.getElementById(val);
        if (panel) {
          var labelId = panel.getAttribute('aria-labelledby');
          if (labelId) {
            var tabEl = document.getElementById(labelId);
            if (tabEl) {
              var idxPanel = tabs.indexOf(tabEl);
              if (idxPanel >= 0) return idxPanel;
            }
          }
        }
        // 3) numérico: 1-based o 0-based
        var n = parseInt(val, 10);
        if (!isNaN(n)) {
          if (n >= 1 && n <= tabs.length) return n - 1;
          if (n >= 0 && n < tabs.length) return n;
        }
      } catch(_) {}
      return -1;
    }

    function setUrlTabParam(idx, push){
      try {
        var usp = new URLSearchParams(window.location.search);
        var tabId = tabs[idx] && tabs[idx].id ? tabs[idx].id : String(idx);
        usp.set('tab', tabId);
        var newUrl = window.location.pathname + '?' + usp.toString() + window.location.hash;
        if (push) { history.pushState({tabIdx: idx}, '', newUrl); }
        else { history.replaceState({tabIdx: idx}, '', newUrl); }
      } catch(_) {}
    }

    function selectTab(idx, focus){
      // Guardar scroll del panel actual antes de cambiar
      if (currentIdx >= 0 && panels[currentIdx]) {
        try { sessionStorage.setItem(scrollKey(currentIdx), String(panels[currentIdx].scrollTop)); } catch (_) {}
      }
      tabs.forEach(function(tab, i){
        var selected = (i === idx);
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        // Roving tabindex: solo la pestaña activa es tabulable
        tab.setAttribute('tabindex', selected ? '0' : '-1');
        var panel = panels[i];
        if (panel) {
          if (selected) { panel.removeAttribute('hidden'); }
          else { panel.setAttribute('hidden', ''); }
        }
      });
      try { sessionStorage.setItem(storageKey, String(idx)); } catch (_) {}
      currentIdx = idx;
      dbgLog('[tabs] selectTab -> idx=%d id=%s panel=%s focus=%s', idx, tabs[idx] && tabs[idx].id, panels[idx] && panels[idx].id, !!focus);
      if (focus && tabs[idx]) tabs[idx].focus();
      // Restaurar scroll del panel seleccionado
      var targetPanel = panels[idx];
      if (targetPanel) {
        try {
          var savedTop = sessionStorage.getItem(scrollKey(idx));
          if (savedTop != null) {
            var y = parseInt(savedTop, 10);
            if (!isNaN(y)) { targetPanel.scrollTop = y; }
          }
        } catch (_) {}
      }
      // Sincronizar URL (no crear entradas extra al restaurar)
      setUrlTabParam(idx, false);
    }

    // ARIA: orientación por defecto horizontal si falta
    if (!tablist.hasAttribute('aria-orientation')) {
      tablist.setAttribute('aria-orientation', 'horizontal');
    }

    // Inicializar roving tabindex según estado marcado en HTML
    var htmlSelectedIdxInit = tabs.findIndex(function(t){ return t.getAttribute('aria-selected') === 'true'; });
    tabs.forEach(function(tab, i){ tab.setAttribute('tabindex', (i === htmlSelectedIdxInit || (htmlSelectedIdxInit < 0 && i === 0)) ? '0' : '-1'); });

    // Activar con click/enter/space
    tabs.forEach(function(tab, i){
      tab.addEventListener('click', function(){ dbgLog('[tabs] click -> idx=%d id=%s', i, tab.id); selectTab(i, false); });
      tab.addEventListener('keydown', function(e){
        switch (e.key) {
          case 'Enter':
          case ' ': e.preventDefault(); dbgLog('[tabs] key %s -> idx=%d', e.key, i); selectTab(i, false); break;
          case 'ArrowRight': e.preventDefault(); dbgLog('[tabs] key %s -> next from %d', e.key, i); selectTab((i+1)%tabs.length, true); break;
          case 'ArrowLeft': e.preventDefault(); dbgLog('[tabs] key %s -> prev from %d', e.key, i); selectTab((i-1+tabs.length)%tabs.length, true); break;
          case 'Home': e.preventDefault(); dbgLog('[tabs] key Home'); selectTab(0, true); break;
          case 'End': e.preventDefault(); dbgLog('[tabs] key End'); selectTab(tabs.length-1, true); break;
        }
      });
    });

    // Soporte para botones data-goto-tab (dentro del root de pestañas)
    root.addEventListener('click', function(e){
      var btn = e.target.closest('[data-goto-tab]');
      if (!btn) return;
      var tabSelector = btn.getAttribute('data-goto-tab');
      var targetSelector = btn.getAttribute('data-goto-target');
      if (!tabSelector) return;
      var tabEl = root.querySelector(tabSelector);
      if (!tabEl) return;
      var idx = tabs.indexOf(tabEl);
      if (idx < 0) return;
      dbgLog('[tabs] data-goto-tab -> selector=%s idx=%d', tabSelector, idx);
      selectTab(idx, true);
      if (targetSelector) {
        var target = panels[idx] && panels[idx].querySelector(targetSelector);
        if (target) {
          try { target.scrollIntoView({behavior:'smooth', block:'start'}); } catch (_) { target.scrollIntoView(); }
          if (typeof target.focus === 'function') { try { target.focus(); } catch (_) {} }
        }
      }
    });

    // Selección inicial respetando sessionStorage
    var initialIdx = 0;
    // 1) URL (?tab=...)
    var urlIdx = getTabIdxFromUrl();
    if (urlIdx >= 0) { initialIdx = urlIdx; }
    // 2) HTML marcado
    if (urlIdx < 0) {
      var htmlSelectedIdx = tabs.findIndex(function(t){ return t.getAttribute('aria-selected') === 'true'; });
      if (htmlSelectedIdx >= 0) { initialIdx = htmlSelectedIdx; }
    }
    // 3) SessionStorage
    try {
      if (urlIdx < 0) {
        var saved = sessionStorage.getItem(storageKey);
        if (saved != null) {
          var n = parseInt(saved, 10);
          if (!isNaN(n) && n >= 0 && n < tabs.length) { initialIdx = n; }
        }
      }
    } catch (_) {}
    currentIdx = initialIdx;
    selectTab(initialIdx, false);
    dbgLog('[tabs] init -> initialIdx=%d id=%s', initialIdx, tabs[initialIdx] && tabs[initialIdx].id);

    // Responder a navegación del historial (back/forward)
    window.addEventListener('popstate', function(){
      var pIdx = getTabIdxFromUrl();
      if (pIdx >= 0 && pIdx !== currentIdx) {
        dbgLog('[tabs] popstate -> idx=%d', pIdx);
        selectTab(pIdx, false);
      }
    });
  }

  afterLoad(function(){
    var root = document.getElementById('app-tabs');
    if (root) initTabs(root);
  });
})();
