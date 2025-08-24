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

    function selectTab(idx, focus){
      // Guardar scroll del panel actual antes de cambiar
      if (currentIdx >= 0 && panels[currentIdx]) {
        try { sessionStorage.setItem(scrollKey(currentIdx), String(panels[currentIdx].scrollTop)); } catch (_) {}
      }
      tabs.forEach(function(tab, i){
        var selected = (i === idx);
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
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
    }

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
    // si hay alguna marcada en HTML, úsala
    var htmlSelectedIdx = tabs.findIndex(function(t){ return t.getAttribute('aria-selected') === 'true'; });
    if (htmlSelectedIdx >= 0) { initialIdx = htmlSelectedIdx; }
    try {
      var saved = sessionStorage.getItem(storageKey);
      if (saved != null) {
        var n = parseInt(saved, 10);
        if (!isNaN(n) && n >= 0 && n < tabs.length) { initialIdx = n; }
      }
    } catch (_) {}
    currentIdx = initialIdx;
    selectTab(initialIdx, false);
    dbgLog('[tabs] init -> initialIdx=%d id=%s', initialIdx, tabs[initialIdx] && tabs[initialIdx].id);
  }

  afterLoad(function(){
    var root = document.getElementById('app-tabs');
    if (root) initTabs(root);
  });
})();
