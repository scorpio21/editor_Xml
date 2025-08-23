function openEditModal(index) {
  var el = document.getElementById('game-' + index);
  if (!el) return;
  populateEditModal(el, index, 'game');
}

function openEditModalMachine(index) {
  var el = document.getElementById('machine-' + index);
  if (!el) return;
  populateEditModal(el, index, 'machine');
}

function populateEditModal(entryEl, index, type) {
  var name = entryEl.getAttribute('data-name') || '';
  var description = entryEl.getAttribute('data-description') || '';
  var category = entryEl.getAttribute('data-category') || '';
  var romsJson = entryEl.getAttribute('data-roms') || '[]';
  var roms = [];
  try { roms = JSON.parse(romsJson); } catch (e) { roms = []; }

  var form = document.getElementById('edit-game-form');
  if (!form) return;
  document.getElementById('editIndex').value = String(index);
  document.getElementById('editNodeType').value = type;
  document.getElementById('editGameName').value = name;
  document.getElementById('editDescription').value = description;
  document.getElementById('editCategory').value = category;

  var container = document.getElementById('edit-roms-container');
  if (!container) return;
  container.innerHTML = '';
  if (!Array.isArray(roms) || roms.length === 0) {
    container.appendChild(crearFilaEdicion());
  } else {
    roms.forEach(function (r) {
      container.appendChild(crearFilaEdicion(r));
    });
  }

  updateRemoveButtonsEdit();

  var modal = document.getElementById('editModal');
  if (modal) {
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
  }
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}

// Modal de ayuda
var lastHelpTrigger = null; // recordamos quién abrió el modal para devolver el foco
function openHelpModal() {
  var modal = document.getElementById('helpModal');
  if (modal) {
    // Recordar el elemento activo (botón/enlace que abrió el modal)
    lastHelpTrigger = (document.activeElement && document.activeElement instanceof HTMLElement) ? document.activeElement : null;
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    // Enfocar el primer enlace del índice para accesibilidad
    var firstLink = modal.querySelector('nav a, nav button, a, button');
    if (firstLink && typeof firstLink.focus === 'function') {
      firstLink.focus();
    }
  }
}

function closeHelpModal() {
  var modal = document.getElementById('helpModal');
  if (modal) {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    // Devolver foco al disparador si existe
    if (lastHelpTrigger && typeof lastHelpTrigger.focus === 'function') {
      try { lastHelpTrigger.focus(); } catch (e) {}
    }
  }
}

// Modal de creación de XML
function openCreateModal() {
  var modal = document.getElementById('createModal');
  if (modal) {
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    // Enfocar primer campo
    var first = modal.querySelector('input, textarea, select, button');
    if (first && typeof first.focus === 'function') first.focus();
  }
}

function closeCreateModal() {
  var modal = document.getElementById('createModal');
  if (modal) {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }
}

// Modal Añadir juego
function openAddModal() {
  var modal = document.getElementById('addGameModal');
  if (modal) {
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    var first = modal.querySelector('input, textarea, select, button');
    if (first && typeof first.focus === 'function') first.focus();
  }
}

function closeAddModal() {
  var modal = document.getElementById('addGameModal');
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
    var create = document.getElementById('createModal');
    if (event.target === create) {
      closeCreateModal();
    }
    var add = document.getElementById('addGameModal');
    if (event.target === add) {
      closeAddModal();
    }
  });

  // Cerrar modal con la tecla ESC
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
      closeHelpModal();
      closeCreateModal();
      closeAddModal();
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
  }, 250);

  // Añadir/eliminar filas de ROM y calcular hashes por fila
  var romsContainer = document.getElementById('roms-container');
  var addRomBtn = document.getElementById('ag_add_rom_btn');

  function updateRemoveButtons() {
    if (!romsContainer) return;
    var rows = romsContainer.querySelectorAll('.rom-row');
    var show = rows.length > 1;
    rows.forEach(function(row){
      var btn = row.querySelector('.ag_remove_btn');
      if (btn) btn.style.display = show ? 'inline-block' : 'none';
    });
  }

  function crearRomRow() {
    var idx = romsContainer.querySelectorAll('.rom-row').length;
    var row = document.createElement('div');
    row.className = 'rom-row';
    row.dataset.index = String(idx);
    row.innerHTML = `
      <div class="rom-file">
        <input type="file" class="ag_file" accept="*/*">
        <button type="button" class="ag_calc_btn secondary">Calcular hashes</button>
      </div>
      <div class="rom-fields">
        <input type="text" class="ag_rom" name="rom_name[]" placeholder="Nombre de ROM" required>
        <input type="text" class="ag_size" name="size[]" placeholder="Tamaño (bytes)" required>
        <input type="text" class="ag_crc" name="crc[]" placeholder="CRC32 (8 hex)" required>
        <input type="text" class="ag_md5" name="md5[]" placeholder="MD5 (32 hex)" required>
        <input type="text" class="ag_sha1" name="sha1[]" placeholder="SHA1 (40 hex)" required>
        <button type="button" class="ag_remove_btn danger" aria-label="Eliminar esta ROM">Eliminar ROM</button>
      </div>`;
    return row;
  }

  if (addRomBtn && romsContainer) {
    addRomBtn.addEventListener('click', function(){
      var row = crearRomRow();
      romsContainer.appendChild(row);
      updateRemoveButtons();
    });
  }

  if (romsContainer) {
    romsContainer.addEventListener('click', async function(e){
      var target = e.target;
      if (!(target instanceof Element)) return;
      // Calcular hashes de una fila
      if (target.classList.contains('ag_calc_btn')) {
        var row = target.closest('.rom-row');
        if (!row) return;
        var fileInput = row.querySelector('.ag_file');
        if (!fileInput || !fileInput.files || !fileInput.files[0]) { alert('Selecciona un archivo primero.'); return; }
        var file = fileInput.files[0];
        try {
          target.disabled = true;
          target.textContent = 'Calculando…';
          var res = await window.Hashes.calcularDesdeFile(file);
          var romName = row.querySelector('.ag_rom');
          var size = row.querySelector('.ag_size');
          var crc = row.querySelector('.ag_crc');
          var md5 = row.querySelector('.ag_md5');
          var sha1 = row.querySelector('.ag_sha1');
          if (romName && !romName.value) romName.value = file.name;
          var gameNameInput = document.getElementById('ag_name');
          if (gameNameInput && !gameNameInput.value) { gameNameInput.value = file.name.replace(/\.[^/.]+$/, ''); }
          if (size) size.value = res.size;
          if (crc) crc.value = res.crc;
          if (md5) md5.value = res.md5;
          if (sha1) sha1.value = res.sha1;
        } catch (err) {
          console.error(err);
          alert('No se pudieron calcular los hashes.');
        } finally {
          target.disabled = false;
          target.textContent = 'Calcular hashes';
        }
      }
      // Eliminar fila
      if (target.classList.contains('ag_remove_btn')) {
        var rowDel = target.closest('.rom-row');
        if (rowDel && romsContainer.children.length > 1) {
          romsContainer.removeChild(rowDel);
        }
        updateRemoveButtons();
      }
    });
  }

  // Estado inicial
  updateRemoveButtons();

  // Validación en submit del formulario Añadir juego (todas las ROMs)
  var addForm = document.getElementById('add-game-form');
  if (addForm) {
    addForm.addEventListener('submit', function(ev){
      var errors = [];
      if (!romsContainer) return;
      var rows = romsContainer.querySelectorAll('.rom-row');
      if (!rows.length) { errors.push('Debes añadir al menos una ROM.'); }
      rows.forEach(function(row){
        var size = (row.querySelector('.ag_size') || {}).value || '';
        var crc = (row.querySelector('.ag_crc') || {}).value || '';
        var md5 = (row.querySelector('.ag_md5') || {}).value || '';
        var sha1 = (row.querySelector('.ag_sha1') || {}).value || '';
        // Normalizar
        if (row.querySelector('.ag_crc')) row.querySelector('.ag_crc').value = crc.toUpperCase();
        if (row.querySelector('.ag_md5')) row.querySelector('.ag_md5').value = md5.toLowerCase();
        if (row.querySelector('.ag_sha1')) row.querySelector('.ag_sha1').value = sha1.toLowerCase();
        // Validar
        if (!/^\d+$/.test(size)) errors.push('El tamaño debe ser un número entero en bytes.');
        if (!/^[0-9A-Fa-f]{8}$/.test(crc)) errors.push('CRC32 debe tener 8 hex.');
        if (!/^[0-9a-fA-F]{32}$/.test(md5)) errors.push('MD5 debe tener 32 hex.');
        if (!/^[0-9a-fA-F]{40}$/.test(sha1)) errors.push('SHA1 debe tener 40 hex.');
      });
      if (errors.length) { ev.preventDefault(); alert(errors.join('\n')); }
    });
  }
});

// Edición: crear fila de ROM
function crearFilaEdicion(data) {
  var row = document.createElement('div');
  row.className = 'rom-row';
  row.innerHTML = `
    <div class="rom-file">
      <input type="file" class="eg_file" accept="*/*">
      <button type="button" class="eg_calc_btn secondary">Calcular hashes</button>
    </div>
    <div class="rom-fields">
      <input type="text" class="eg_rom" name="rom_name[]" placeholder="Nombre de ROM" required>
      <input type="text" class="eg_size" name="size[]" placeholder="Tamaño (bytes)" required>
      <input type="text" class="eg_crc" name="crc[]" placeholder="CRC32 (8 hex)" required>
      <input type="text" class="eg_md5" name="md5[]" placeholder="MD5 (32 hex)" required>
      <input type="text" class="eg_sha1" name="sha1[]" placeholder="SHA1 (40 hex)" required>
      <button type="button" class="eg_remove_btn danger" aria-label="Eliminar esta ROM">Eliminar ROM</button>
    </div>`;
  if (data) {
    var r = row.querySelector('.eg_rom'); if (r) r.value = data.name || '';
    var s = row.querySelector('.eg_size'); if (s) s.value = data.size || '';
    var c = row.querySelector('.eg_crc'); if (c) c.value = data.crc || '';
    var m = row.querySelector('.eg_md5'); if (m) m.value = data.md5 || '';
    var h = row.querySelector('.eg_sha1'); if (h) h.value = data.sha1 || '';
  }
  return row;
}

function updateRemoveButtonsEdit() {
  var container = document.getElementById('edit-roms-container');
  if (!container) return;
  var rows = container.querySelectorAll('.rom-row');
  var show = rows.length > 1;
  rows.forEach(function (row) {
    var btn = row.querySelector('.eg_remove_btn');
    if (btn) btn.style.display = show ? 'inline-block' : 'none';
  });
}

afterLoad(function initEditModal() {
  var container = document.getElementById('edit-roms-container');
  var addBtn = document.getElementById('edit_add_rom_btn');
  if (addBtn && container) {
    addBtn.addEventListener('click', function(){
      container.appendChild(crearFilaEdicion());
      updateRemoveButtonsEdit();
    });
  }
  if (container) {
    container.addEventListener('click', async function(e){
      var target = e.target;
      if (!(target instanceof Element)) return;
      if (target.classList.contains('eg_calc_btn')) {
        var row = target.closest('.rom-row');
        if (!row) return;
        var fileInput = row.querySelector('.eg_file');
        if (!fileInput || !fileInput.files || !fileInput.files[0]) { alert('Selecciona un archivo primero.'); return; }
        var file = fileInput.files[0];
        try {
          target.disabled = true;
          target.textContent = 'Calculando…';
          var res = await window.Hashes.calcularDesdeFile(file);
          var romName = row.querySelector('.eg_rom');
          var size = row.querySelector('.eg_size');
          var crc = row.querySelector('.eg_crc');
          var md5 = row.querySelector('.eg_md5');
          var sha1 = row.querySelector('.eg_sha1');
          if (romName && !romName.value) romName.value = file.name;
          if (size) size.value = res.size;
          if (crc) crc.value = res.crc;
          if (md5) md5.value = res.md5;
          if (sha1) sha1.value = res.sha1;
        } catch (err) {
          console.error(err);
          alert('No se pudieron calcular los hashes.');
        } finally {
          target.disabled = false;
          target.textContent = 'Calcular hashes';
        }
      }
      if (target.classList.contains('eg_remove_btn')) {
        var rowDel = target.closest('.rom-row');
        if (rowDel && container.children.length > 1) {
          container.removeChild(rowDel);
        }
        updateRemoveButtonsEdit();
      }
    });
  }

  var editForm = document.getElementById('edit-game-form');
  if (editForm) {
    editForm.addEventListener('submit', function(ev){
      var errors = [];
      var rows = container ? container.querySelectorAll('.rom-row') : [];
      if (!rows || !rows.length) { errors.push('Debes mantener al menos una ROM.'); }
      Array.prototype.forEach.call(rows, function(row){
        var size = (row.querySelector('.eg_size') || {}).value || '';
        var crc = (row.querySelector('.eg_crc') || {}).value || '';
        var md5 = (row.querySelector('.eg_md5') || {}).value || '';
        var sha1 = (row.querySelector('.eg_sha1') || {}).value || '';
        // Normalizar
        crc = crc.toUpperCase(); md5 = md5.toLowerCase(); sha1 = sha1.toLowerCase();
        if (row.querySelector('.eg_crc')) row.querySelector('.eg_crc').value = crc;
        if (row.querySelector('.eg_md5')) row.querySelector('.eg_md5').value = md5;
        if (row.querySelector('.eg_sha1')) row.querySelector('.eg_sha1').value = sha1;
        // Validar
        if (!/^\d+$/.test(size)) errors.push('El tamaño debe ser un número entero en bytes.');
        if (!/^[0-9A-Fa-f]{8}$/.test(crc)) errors.push('CRC32 debe tener 8 hex.');
        if (!/^[0-9a-fA-F]{32}$/.test(md5)) errors.push('MD5 debe tener 32 hex.');
        if (!/^[0-9a-fA-F]{40}$/.test(sha1)) errors.push('SHA1 debe tener 40 hex.');
      });
      if (errors.length) { ev.preventDefault(); alert(errors.join('\n')); }
    });
  }
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
    // Asegurar que el token CSRF esté incluido
    var csrfInput = form.querySelector('input[name="csrf_token"]');
    if (csrfInput) {
      fd.set('csrf_token', csrfInput.value);
    }
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

  // === Dedupe por región: conteo y activación del botón ===
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
    // Asegurar token CSRF
    var csrfInput = form.querySelector('input[name="csrf_token"]');
    if (csrfInput) { fd.set('csrf_token', csrfInput.value); }
    dedupeAjax = true;
    fetch('./inc/acciones.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text().then(function (t) { return { status: r.status, text: t }; }); })
      .then(function (resp) {
        var msg = 'Operación completada.';
        var duplicates = 0;
        try {
          var data = JSON.parse(resp.text);
          if (data && typeof data === 'object') {
            if (data.message) msg = data.message;
            if (typeof data.duplicates === 'number') duplicates = data.duplicates;
          }
        } catch (e) {
          if (resp.text) { msg = resp.text; }
        }
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

  // Al cambiar la región, resetear estado del botón y resultado
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

  // Al cambiar el checkbox "Conservar también Europa", reiniciar estado
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

  // Colapsar/expandir sección Eliminación masiva con persistencia
  var bulkToggle = document.querySelector('.bulk-delete .toggle-bulk');
  try {
    var key = 'bulkDeleteCollapsed'; // global por ahora (un único bloque)
    var collapsed = localStorage.getItem(key) === '1';
    function applyState(isCollapsed) {
      if (!bulkToggle) return;
      var container = bulkToggle.closest('.bulk-delete');
      if (!container) return;
      container.classList.toggle('collapsed', !!isCollapsed);
      bulkToggle.setAttribute('aria-expanded', String(!isCollapsed));
      bulkToggle.textContent = isCollapsed ? 'Mostrar sección' : 'Ocultar sección';
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
