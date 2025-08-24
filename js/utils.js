// Utilidades comunes en la app (en español)
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
})();
