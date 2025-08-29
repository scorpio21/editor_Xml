// Inicialización de widgets multi-selección y acciones relacionadas
(function(){
  'use strict';
  var afterLoad = (window.AppUtils && window.AppUtils.afterLoad) ? window.AppUtils.afterLoad : function(cb){
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
  };

  afterLoad(function initMultiSelects(){
    var widgets = document.querySelectorAll('.multi-select');
    Array.prototype.forEach.call(widgets, function (w) {
      var trigger = w.querySelector('.ms-trigger');
      var panel = w.querySelector('.ms-panel');
      var label = w.querySelector('.ms-label');
      var checks = w.querySelectorAll('input[type="checkbox"]');
      var btnAll = w.querySelector('.ms-all');
      var btnNone = w.querySelector('.ms-none');
      var placeholder = label && label.textContent ? label.textContent : 'Select';
      if (label && !label.getAttribute('data-placeholder')) {
        label.setAttribute('data-placeholder', placeholder);
      }
      if (!trigger || !panel) {
        return; // marcado incompleto
      }

      function updateLabel() {
        var selected = Array.prototype.filter.call(checks, function (c) { return c.checked; });
        if (selected.length === 0) {
          label.textContent = placeholder;
        } else if (selected.length === checks.length) {
          var allTxt = (btnAll && btnAll.textContent) ? btnAll.textContent : 'All';
          label.textContent = allTxt + ' (' + selected.length + ')';
        } else if (selected.length <= 2) {
          var names = selected.map(function (c) { return c.nextElementSibling ? c.nextElementSibling.textContent : c.value; });
          label.textContent = names.join(', ');
        } else {
          var suf = w.getAttribute('data-selected-suffix') || w.dataset.selectedSuffix || 'selected';
          label.textContent = selected.length + ' ' + suf;
        }
      }

      // Estado inicial
      updateLabel();

      // Abrir/cerrar
      function toggle(open) {
        var isOpen = panel.classList.contains('open');
        var next = typeof open === 'boolean' ? open : !isOpen;
        panel.classList.toggle('open', next);
        trigger.setAttribute('aria-expanded', String(next));
      }

      trigger.addEventListener('click', function (e) {
        e.stopPropagation();
        toggle();
      });

      // Cerrar al hacer click fuera
      document.addEventListener('click', function (e) {
        if (!w.contains(e.target)) toggle(false);
      });

      // Acciones
      if (btnAll) btnAll.addEventListener('click', function () {
        Array.prototype.forEach.call(checks, function (c) { c.checked = true; });
        updateLabel();
      });
      if (btnNone) btnNone.addEventListener('click', function () {
        Array.prototype.forEach.call(checks, function (c) { c.checked = false; });
        updateLabel();
      });

      // Cambio de checks
      Array.prototype.forEach.call(checks, function (c) {
        c.addEventListener('change', updateLabel);
      });

      // Accesibilidad básica: cerrar con ESC
      w.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') toggle(false);
      });
    });

    // Botón Limpiar filtros (delegación global)
    document.addEventListener('click', function (ev) {
      var btn = ev.target && ev.target.closest('#clear-filters');
      if (!btn) return;
      ev.preventDefault();
      ev.stopPropagation();
      Array.prototype.forEach.call(document.querySelectorAll('.multi-select'), function (w) {
        var checks = w.querySelectorAll('input[type="checkbox"]');
        Array.prototype.forEach.call(checks, function (c) { c.checked = false; });
        var label = w.querySelector('.ms-label');
        if (label) {
          var ph = label.getAttribute('data-placeholder') || label.textContent || 'Select';
          label.textContent = ph;
        }
        var trigger = w.querySelector('.ms-trigger');
        var panel = w.querySelector('.ms-panel');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
        if (panel) panel.classList.remove('open');
      });
    });

    // Colapsar/expandir sección Eliminación masiva con persistencia
    var bulkToggle = document.querySelector('.bulk-delete .toggle-bulk');
    try {
      var key = 'bulkDeleteCollapsed';
      var collapsed = localStorage.getItem(key) === '1';
      function applyState(isCollapsed) {
        if (!bulkToggle) return;
        var container = bulkToggle.closest('.bulk-delete');
        if (!container) return;
        container.classList.toggle('collapsed', !!isCollapsed);
        bulkToggle.setAttribute('aria-expanded', String(!isCollapsed));
        var showTxt = bulkToggle.getAttribute('data-text-show') || 'Show section';
        var hideTxt = bulkToggle.getAttribute('data-text-hide') || 'Hide section';
        bulkToggle.textContent = isCollapsed ? showTxt : hideTxt;
      }
      applyState(collapsed);
      if (bulkToggle) {
        bulkToggle.addEventListener('click', function(){
          collapsed = !collapsed;
          localStorage.setItem(key, collapsed ? '1' : '0');
          applyState(collapsed);
        });
      }
    } catch (e) { /* localStorage no disponible */ }
  });
})();
