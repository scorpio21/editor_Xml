// Acciones de eliminación masiva (conteo y ejecución) para la sección Bulk
(function(){
  'use strict';
  var afterLoad = (window.AppUtils && window.AppUtils.afterLoad) ? window.AppUtils.afterLoad : function(cb){
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
  };
  var parse = (window.AppUtils && window.AppUtils.parseAjaxJson) ? window.AppUtils.parseAjaxJson : function(t){ try{return JSON.parse(t);}catch(e){return {ok:false,message:'Operación completada.'};} };

  afterLoad(function(){
    function dbgEnabled(){ try { return (new URLSearchParams(window.location.search)).get('debug') === 'assets'; } catch(_) { return false; } }
    function dbgLog(){ if (!dbgEnabled()) return; try { console.log.apply(console, arguments); } catch(_) {} }
    dbgLog('[bulk] init');
    // Contar coincidencias
    var countingAjax = false;
    function handleCount(ev) {
      var countBtn = ev.target && ev.target.closest('button[name="action"][value="bulk_count"]');
      if (!countBtn) return;
      dbgLog('[bulk] click count');
      var form = document.getElementById('bulk-delete-form');
      if (!form) return;
      ev.preventDefault();
      ev.stopPropagation();
      if (typeof ev.stopImmediatePropagation === 'function') ev.stopImmediatePropagation();
      var live = document.getElementById('count-result');
      if (live) { live.textContent = 'Calculando…'; }
      var fd = new FormData(form);
      fd.set('action', 'bulk_count');
      fd.set('ajax', '1');
      var csrfInput = form.querySelector('input[name="csrf_token"]');
      if (csrfInput) { fd.set('csrf_token', csrfInput.value); }
      countingAjax = true;
      dbgLog('[bulk] fetch POST inc/acciones.php action=bulk_count');
      fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.text().then(function (t) { return { status: r.status, text: t }; }); })
        .then(function (resp) {
          dbgLog('[bulk] resp status=%d len=%d', resp.status, resp.text.length);
          var data = parse(resp.text) || { ok:true, message: 'Operación completada.' };
          var msg = data.message || 'Operación completada.';
          if (data && data.ok === false) { msg = 'Error: ' + msg; }
          dbgLog('[bulk] parsed ok=%s message=%s', String(data && data.ok !== false), msg);
          if (live) { live.textContent = msg; } else { alert(msg); }
        })
        .catch(function(){ if (live) { live.textContent = 'No se pudo completar el conteo.'; } })
        .finally(function(){ countingAjax = false; });
    }
    document.addEventListener('click', handleCount);
    var directBtn = document.querySelector('button[name="action"][value="bulk_count"]');
    if (directBtn) { directBtn.addEventListener('click', handleCount); }

    // Ejecutar eliminación masiva
    function handleBulkDelete(ev) {
      var btn = ev.target && ev.target.closest('button[name="action"][value="bulk_delete"]');
      if (!btn) return;
      dbgLog('[bulk] click delete');
      var form = document.getElementById('bulk-delete-form');
      if (!form) return;
      if (!window.confirm('¿Seguro que deseas eliminar los juegos que coincidan con estos filtros? Esta acción no se puede deshacer.')) { return; }
      ev.preventDefault();
      ev.stopPropagation();
      if (typeof ev.stopImmediatePropagation === 'function') ev.stopImmediatePropagation();
      var live = document.getElementById('count-result');
      if (live) { live.textContent = 'Eliminando…'; }
      var fd = new FormData(form);
      fd.set('action', 'bulk_delete');
      fd.set('ajax', '1');
      var csrfInput = form.querySelector('input[name="csrf_token"]');
      if (csrfInput) { fd.set('csrf_token', csrfInput.value); }
      dbgLog('[bulk] fetch POST inc/acciones.php action=bulk_delete');
      fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.text().then(function (t) { return { status: r.status, text: t }; }); })
        .then(function (resp) {
          dbgLog('[bulk] resp status=%d len=%d', resp.status, resp.text.length);
          var data = parse(resp.text) || { ok:true, message: 'Operación completada.' };
          var msg = data.message || 'Operación completada.';
          if (data && data.ok === false) { msg = 'Error: ' + msg; }
          dbgLog('[bulk] parsed ok=%s message=%s', String(data && data.ok !== false), msg);
          if (live) { live.textContent = msg; } else { alert(msg); }
          var btnDedupe = document.getElementById('btn-dedupe');
          var btnExport = document.getElementById('btn-dedupe-export');
          if (btnDedupe) { btnDedupe.disabled = true; }
          if (btnExport) { btnExport.disabled = true; }
        })
        .catch(function(){ if (live) { live.textContent = 'No se pudo completar la eliminación masiva.'; } });
    }
    document.addEventListener('click', handleBulkDelete);
    var btnBulkDelete = document.querySelector('button[name="action"][value="bulk_delete"]');
    if (btnBulkDelete) { btnBulkDelete.addEventListener('click', handleBulkDelete); }
  });
})();
