// Reloj en vivo: actualiza elementos con [data-clock] con segundos y zona horaria configurable
(function(){
  'use strict';
  function afterLoad(cb){
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', cb);
    } else { cb(); }
  }
  afterLoad(function initLiveClock(){
    var TZ_KEY = 'app.timezone';
    function getSavedTz(){
      try { return localStorage.getItem(TZ_KEY) || 'system'; } catch(e){ return 'system'; }
    }
    function saveTz(tz){
      try { localStorage.setItem(TZ_KEY, tz); } catch(e){}
    }
    function formatNow(){
      var tz = getSavedTz();
      var opts = { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false };
      var dtf;
      try {
        dtf = new Intl.DateTimeFormat(undefined, tz !== 'system' ? Object.assign({ timeZone: tz }, opts) : opts);
      } catch(e) {
        dtf = new Intl.DateTimeFormat(undefined, opts);
      }
      // Formatear a YYYY-MM-DD HH:MM:SS desde partes para consistencia
      var parts = dtf.formatToParts(new Date());
      var map = {};
      parts.forEach(function(p){ map[p.type] = p.value; });
      var y = map.year, m = map.month, d = map.day;
      var hh = map.hour, mm = map.minute, ss = map.second;
      return y + '-' + m + '-' + d + ' ' + hh + ':' + mm + ':' + ss;
    }
    function applyTzSelects(){
      var current = getSavedTz();
      var selects = document.querySelectorAll('[data-timezone-select]');
      Array.prototype.forEach.call(selects, function(s){
        // Seleccionar opción si existe
        if (Array.prototype.some.call(s.options, function(o){ return o.value === current; })) {
          s.value = current;
        }
        s.addEventListener('change', function(){
          saveTz(s.value || 'system');
        });
      });
    }
    function tick(){
      var text = formatNow();
      var nodes = document.querySelectorAll('[data-clock]');
      Array.prototype.forEach.call(nodes, function(n){ n.textContent = text; });
    }
    // Inicializar selects y arrancar reloj por segundo
    applyTzSelects();
    tick();
    setInterval(tick, 1000);
  });
})();
