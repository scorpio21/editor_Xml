// Acciones de deduplicación por región (conteo y ejecución)
(function(){
  'use strict';
  var afterLoad = (window.AppUtils && window.AppUtils.afterLoad) ? window.AppUtils.afterLoad : function(cb){
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
  };
  var parse = (window.AppUtils && window.AppUtils.parseAjaxJson) ? window.AppUtils.parseAjaxJson : function(t){ try{return JSON.parse(t);}catch(e){return {ok:false,message:'Operación completada.'};} };

  afterLoad(function(){
    var dedupeAjax = false;
    function handleDedupeCount(ev) {
      var countBtn = ev.target && ev.target.closest('form#dedupe-form button[name="action"][value="dedupe_region_count"]');
      if (!countBtn) return;
      var form = document.getElementById('dedupe-form');
      if (!form) return;
      ev.preventDefault();
      ev.stopPropagation();
      if (typeof ev.stopImmediatePropagation === 'function') ev.stopImmediatePropagation();
      var live = document.getElementById('dedupe-count-result');
      if (live) { live.textContent = 'Calculando…'; }
      var btnDedupe = document.getElementById('btn-dedupe');
      if (btnDedupe) { btnDedupe.disabled = true; }
      var btnExport = document.getElementById('btn-dedupe-export');
      if (btnExport) { btnExport.disabled = true; }
      var fd = new FormData(form);
      fd.set('action', 'dedupe_region_count');
      fd.set('ajax', '1');
      var csrfInput = form.querySelector('input[name="csrf_token"]');
      if (csrfInput) { fd.set('csrf_token', csrfInput.value); }
      dedupeAjax = true;
      fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.text().then(function (t) { return { status: r.status, text: t }; }); })
        .then(function (resp) {
          var data = parse(resp.text) || { ok:true, message: 'Operación completada.' };
          var msg = data.message || 'Operación completada.';
          var duplicates = (typeof data.duplicates === 'number') ? data.duplicates : 0;
          if (data && data.ok === false) { msg = 'Error: ' + msg; }
          if (live) { live.textContent = msg; }
          var has = duplicates > 0;
          if (btnDedupe) { btnDedupe.disabled = !has; }
          if (btnExport) { btnExport.disabled = !has; }
        })
        .catch(function(){
          if (live) { live.textContent = 'No se pudo completar el conteo de duplicados.'; }
          if (btnDedupe) { btnDedupe.disabled = true; }
          if (btnExport) { btnExport.disabled = true; }
        })
        .finally(function(){ dedupeAjax = false; });
    }
    document.addEventListener('click', handleDedupeCount);

    // Cambios de inputs que invalidan resultado
    var pref = document.getElementById('prefer_region');
    if (pref) {
      pref.addEventListener('change', function(){
        var live = document.getElementById('dedupe-count-result');
        if (live) { live.textContent = ''; }
        var btnD = document.getElementById('btn-dedupe');
        if (btnD) { btnD.disabled = true; }
        var btnE = document.getElementById('btn-dedupe-export');
        if (btnE) { btnE.disabled = true; }
      });
    }
    var keepEU = document.getElementById('keep_europe');
    if (keepEU) {
      keepEU.addEventListener('change', function(){
        var live = document.getElementById('dedupe-count-result');
        if (live) { live.textContent = ''; }
        var btnD = document.getElementById('btn-dedupe');
        if (btnD) { btnD.disabled = true; }
        var btnE = document.getElementById('btn-dedupe-export');
        if (btnE) { btnE.disabled = true; }
      });
    }

    // Ejecutar dedupe
    function handleDedupeRun(ev) {
      var btn = ev.target && ev.target.closest('button[name="action"][value="dedupe_region"]');
      if (!btn) return;
      var form = document.getElementById('dedupe-form');
      if (!form) return;
      if (!window.confirm('¿Eliminar duplicados y conservar solo la región seleccionada cuando exista?')) { return; }
      ev.preventDefault();
      ev.stopPropagation();
      if (typeof ev.stopImmediatePropagation === 'function') ev.stopImmediatePropagation();
      var live = document.getElementById('dedupe-count-result');
      if (live) { live.textContent = 'Eliminando duplicados…'; }
      var fd = new FormData(form);
      fd.set('action', 'dedupe_region');
      fd.set('ajax', '1');
      var csrfInput = form.querySelector('input[name="csrf_token"]');
      if (csrfInput) { fd.set('csrf_token', csrfInput.value); }
      fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.text().then(function (t) { return { status: r.status, text: t }; }); })
        .then(function (resp) {
          var data = parse(resp.text) || { ok:true, message: 'Operación completada.' };
          var msg = data.message || 'Operación completada.';
          if (data && data.ok === false) { msg = 'Error: ' + msg; }
          if (live) { live.textContent = msg; } else { alert(msg); }
          var btnDedupe = document.getElementById('btn-dedupe');
          var btnExport = document.getElementById('btn-dedupe-export');
          if (btnDedupe) { btnDedupe.disabled = true; }
          if (btnExport) { btnExport.disabled = true; }
        })
        .catch(function(){ if (live) { live.textContent = 'No se pudo completar la eliminación de duplicados.'; } });
    }
    document.addEventListener('click', handleDedupeRun);
    var btnDedupeRun = document.querySelector('button[name="action"][value="dedupe_region"]');
    if (btnDedupeRun) { btnDedupeRun.addEventListener('click', handleDedupeRun); }
  });
})();
