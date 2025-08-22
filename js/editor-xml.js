function openEditModal(index) {
  // Obtener el elemento del juego
  var gameElement = document.getElementById('game-' + index);

  // Obtener los valores de los atributos de datos
  var gameName = gameElement.getAttribute('data-name');
  var description = gameElement.getAttribute('data-description');
  var category = gameElement.getAttribute('data-category');
  var romName = gameElement.getAttribute('data-romname');
  var size = gameElement.getAttribute('data-size');
  var crc = gameElement.getAttribute('data-crc');
  var md5 = gameElement.getAttribute('data-md5');
  var sha1 = gameElement.getAttribute('data-sha1');

  // Llenar el formulario con los valores
  document.getElementById('editIndex').value = index;
  document.getElementById('editGameName').value = gameName || '';
  document.getElementById('editDescription').value = description || '';
  document.getElementById('editCategory').value = category || '';
  document.getElementById('editRomName').value = romName || '';
  document.getElementById('editSize').value = size || '';
  document.getElementById('editCrc').value = crc || '';
  document.getElementById('editMd5').value = md5 || '';
  document.getElementById('editSha1').value = sha1 || '';

  // Mostrar el modal
  document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}

// Modal de ayuda
function openHelpModal() {
  var modal = document.getElementById('helpModal');
  if (modal) {
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
  }
}

function closeHelpModal() {
  var modal = document.getElementById('helpModal');
  if (modal) {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }
}

// Cerrar modal al hacer clic fuera del contenido
afterLoad(function () {
  window.addEventListener('click', function (event) {
    var modal = document.getElementById('editModal');
    if (event.target === modal) {
      closeModal();
    }
    var help = document.getElementById('helpModal');
    if (event.target === help) {
      closeHelpModal();
    }
  });

  // Cerrar modal con la tecla ESC
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
      closeHelpModal();
    }
  });

  // Reintento tardío por si el DOM aún no estaba listo totalmente
  setTimeout(function () {
    var w2 = document.querySelectorAll('.multi-select');
    if (w2.length && !document.querySelector('.multi-select .ms-panel.open') && !document.querySelector('.multi-select .ms-panel').dataset._inited) {
      // Marcar como inicializado para evitar bucles
      Array.prototype.forEach.call(w2, function (w) {
        var p = w.querySelector('.ms-panel');
        if (p) p.dataset._inited = '1';
      });
    }
  }, 50);
});

// Utilidad para ejecutar cuando el DOM esté listo
function afterLoad(cb) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', cb);
  } else {
    cb();
  }
}

// Inicializar dropdowns multi-selección
afterLoad(function initMultiSelects() {
  var widgets = document.querySelectorAll('.multi-select');
  Array.prototype.forEach.call(widgets, function (w) {
    var trigger = w.querySelector('.ms-trigger');
    var panel = w.querySelector('.ms-panel');
    var label = w.querySelector('.ms-label');
    var checks = w.querySelectorAll('input[type="checkbox"]');
    var btnAll = w.querySelector('.ms-all');
    var btnNone = w.querySelector('.ms-none');
    var placeholder = label && label.textContent ? label.textContent : 'Seleccionar';
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
        label.textContent = 'Todos (' + selected.length + ')';
      } else if (selected.length <= 2) {
        var names = selected.map(function (c) { return c.nextElementSibling ? c.nextElementSibling.textContent : c.value; });
        label.textContent = names.join(', ');
      } else {
        label.textContent = selected.length + ' seleccionados';
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
      if (typeof console !== 'undefined' && console.debug) {
        console.debug('multi-select toggle', { open: next });
      }
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

  // Botón Limpiar filtros (delegación global para máxima robustez)
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
        var ph = label.getAttribute('data-placeholder') || 'Seleccionar';
        label.textContent = ph;
      }
      var trigger = w.querySelector('.ms-trigger');
      var panel = w.querySelector('.ms-panel');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
      if (panel) panel.classList.remove('open');
    });
  });

  // AJAX accesible para Contar coincidencias (sin recarga)
  var countingAjax = false;
  function handleCount(ev) {
    var countBtn = ev.target && ev.target.closest('button[name="action"][value="bulk_count"]');
    if (!countBtn) return;
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
    countingAjax = true;
    fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text().then(function (t) { return { status: r.status, text: t }; }); })
      .then(function (resp) {
        var msg = 'Operación completada.';
        try {
          var data = JSON.parse(resp.text);
          if (data && data.message) msg = data.message;
        } catch (e) {
          // Si no es JSON, mostramos el texto recibido si viene algo útil
          if (resp.text) { msg = resp.text; }
        }
        if (live) { live.textContent = msg; } else { alert(msg); }
      })
      .catch(function (e) {
        if (live) { live.textContent = 'No se pudo completar el conteo.'; }
      })
      .finally(function(){ countingAjax = false; });
  }
  // Interceptar tanto por delegación como directo para máxima compatibilidad
  document.addEventListener('click', handleCount);
  var directBtn = document.querySelector('button[name="action"][value="bulk_count"]');
  if (directBtn) { directBtn.addEventListener('click', handleCount); }

  // Evitar submit del formulario si estamos haciendo conteo por AJAX
  var formBD = document.getElementById('bulk-delete-form');
  if (formBD) {
    formBD.addEventListener('submit', function(e){
      if (countingAjax) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  }
});

// Reloj en vivo: actualiza elementos con [data-clock]
afterLoad(function initLiveClock(){
  function pad(n){ return (n < 10 ? '0' : '') + n; }
  function format(dt){
    return dt.getFullYear() + '-' + pad(dt.getMonth()+1) + '-' + pad(dt.getDate()) + ' ' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
  }
  function tick(){
    var nodes = document.querySelectorAll('[data-clock]');
    var now = new Date();
    var text = format(now);
    Array.prototype.forEach.call(nodes, function(n){ n.textContent = text; });
  }
  // Primera actualización alineada al minuto
  var now = new Date();
  var msToNextMinute = (60 - now.getSeconds()) * 1000 - now.getMilliseconds();
  setTimeout(function(){
    tick();
    setInterval(tick, 60*1000);
  }, Math.max(0, msToNextMinute));
});
