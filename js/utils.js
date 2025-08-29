// Utilidades comunes de la app (en español)
(function(){
  'use strict';
  // Ejecuta un callback cuando el DOM está listo
  function afterLoad(cb) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', cb);
    } else {
      cb();
    }
  }
  // Parseo estándar de respuestas JSON del backend { ok, message, ... }
  function parseAjaxJson(text) {
    var fallback = { ok: false, message: 'Operación completada.' };
    try {
      var data = JSON.parse(text);
      if (data && typeof data === 'object') {
        if (typeof data.ok !== 'boolean') { data.ok = true; }
        if (!data.message) { data.message = fallback.message; }
        return data;
      }
    } catch (e) { /* no JSON */ }
    return fallback;
  }
  // Exponer en window (simple y compatible)
  window.AppUtils = {
    afterLoad: afterLoad,
    parseAjaxJson: parseAjaxJson
  };
  
  // Auto-ocultado de mensajes flash accesibles
  // Oculta suavemente el bloque .flash-message tras unos segundos sin interferir con lectores de pantalla
  afterLoad(function(){
    try {
      var flash = document.querySelector('.flash-message');
      if (!flash) return;
      // Permitir desactivar el auto-hide con data-persist="1" si fuera necesario en el futuro
      if (flash.getAttribute('data-persist') === '1') return;
      var DURACION_MS = 6000; // 6s visibles
      setTimeout(function(){
        // Transición suave de opacidad y luego retirada del DOM
        flash.style.transition = 'opacity .3s ease';
        flash.style.opacity = '0';
        setTimeout(function(){
          if (flash && flash.parentNode) {
            flash.parentNode.removeChild(flash);
          }
        }, 350);
      }, DURACION_MS);
    } catch(_) { /* no-op */ }
  });
  // Diagnóstico opcional de assets y estructura de UI
  function hasDebugFlag(){
    try {
      var p = new URLSearchParams(window.location.search);
      return p.get('debug') === 'assets';
    } catch(_) { return false; }
  }

  function logAssetsAndUI(){
    if (!hasDebugFlag()) return;
    var seps = '\n------------------------------------------';
    console.group('%cDiagnóstico de assets y UI','color:#2563eb;font-weight:bold');
    try {
      // Scripts
      var scripts = Array.prototype.slice.call(document.scripts).map(function(s){ return s.src || '(inline)'; });
      console.log('Scripts (%d):%s\n%s', scripts.length, seps, scripts.join('\n'));
      // Estilos (links rel=stylesheet)
      var links = Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"]')).map(function(l){ return l.href; });
      console.log('Estilos (%d):%s\n%s', links.length, seps, links.join('\n'));
      // Paneles de pestañas si existen
      var tablist = document.querySelector('[role="tablist"]');
      if (tablist) {
        var tabs = Array.prototype.slice.call(tablist.querySelectorAll('[role="tab"]')).map(function(t){
          return (t.id||'(sin-id)')+ ' sel=' + t.getAttribute('aria-selected');
        });
        var panels = Array.prototype.slice.call(document.querySelectorAll('[role="tabpanel"]')).map(function(p){
          return (p.id||'(sin-id)')+ (p.hasAttribute('hidden')?' hidden':' visible');
        });
        console.log('Tabs:%s\n%s', seps, tabs.join('\n'));
        console.log('Panels:%s\n%s', seps, panels.join('\n'));
      } else {
        console.log('No se detectó tablist (UI clásica o no inicializada)');
      }
    } catch(err){
      console.warn('Diagnóstico de assets falló:', err);
    }
    console.groupEnd();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', logAssetsAndUI);
  } else {
    logAssetsAndUI();
  }
})();
