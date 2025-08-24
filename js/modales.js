// Lógica de modales: abrir/cerrar y gestión de filas ROM en formularios dentro de modales
(function(){
  'use strict';

  var afterLoad = (window.AppUtils && window.AppUtils.afterLoad) ? window.AppUtils.afterLoad : function(cb){
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
  };

  // --- Modal editar ---
  function openEditModal(index) {
    var el = document.getElementById('game-' + index);
    if (!el) return;
    var absIdx = el.getAttribute('data-absindex');
    var idxToUse = (absIdx != null && absIdx !== '') ? parseInt(absIdx, 10) : index;
    populateEditModal(el, idxToUse, 'game');
  }
  function openEditModalMachine(index) {
    var el = document.getElementById('machine-' + index);
    if (!el) return;
    var absIdx = el.getAttribute('data-absindex');
    var idxToUse = (absIdx != null && absIdx !== '') ? parseInt(absIdx, 10) : index;
    populateEditModal(el, idxToUse, 'machine');
  }
  function crearFilaEdicion(data) {
    var row = document.createElement('div');
    row.className = 'rom-row';
    row.innerHTML = "\n    <div class=\"rom-file\">\n      <input type=\"file\" class=\"eg_file\" accept=\"*/*\">\n      <button type=\"button\" class=\"eg_calc_btn secondary\">Calcular hashes</button>\n    </div>\n    <div class=\"rom-fields\">\n      <input type=\"text\" class=\"eg_rom\" name=\"rom_name[]\" placeholder=\"Nombre de ROM\" required>\n      <input type=\"text\" class=\"eg_size\" name=\"size[]\" placeholder=\"Tamaño (bytes)\" required>\n      <input type=\"text\" class=\"eg_crc\" name=\"crc[]\" placeholder=\"CRC32 (8 hex)\" required>\n      <input type=\"text\" class=\"eg_md5\" name=\"md5[]\" placeholder=\"MD5 (32 hex)\" required>\n      <input type=\"text\" class=\"eg_sha1\" name=\"sha1[]\" placeholder=\"SHA1 (40 hex)\" required>\n      <button type=\"button\" class=\"eg_remove_btn danger\" aria-label=\"Eliminar esta ROM\">Eliminar ROM</button>\n    </div>";
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
      roms.forEach(function (r) { container.appendChild(crearFilaEdicion(r)); });
    }
    updateRemoveButtonsEdit();

    var modal = document.getElementById('editModal');
    if (modal) { modal.style.display = 'block'; modal.setAttribute('aria-hidden', 'false'); }
  }
  function closeModal() {
    var m = document.getElementById('editModal');
    if (m) m.style.display = 'none';
  }

  // --- Modal ayuda ---
  var lastHelpTrigger = null;
  function openHelpModal() {
    var modal = document.getElementById('helpModal');
    if (!modal) return;
    lastHelpTrigger = (document.activeElement && document.activeElement instanceof HTMLElement) ? document.activeElement : null;
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    var firstLink = modal.querySelector('nav a, nav button, a, button');
    if (firstLink && typeof firstLink.focus === 'function') firstLink.focus();
  }
  function closeHelpModal() {
    var modal = document.getElementById('helpModal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    if (lastHelpTrigger && typeof lastHelpTrigger.focus === 'function') {
      try { lastHelpTrigger.focus(); } catch (e) {}
    }
  }

  // --- Modal crear XML ---
  function openCreateModal() {
    var modal = document.getElementById('createModal');
    if (!modal) return;
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    var first = modal.querySelector('input, textarea, select, button');
    if (first && typeof first.focus === 'function') first.focus();
  }
  function closeCreateModal() {
    var modal = document.getElementById('createModal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }

  // --- Modal añadir juego ---
  function openAddModal() {
    var modal = document.getElementById('addGameModal');
    if (!modal) return;
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    var first = modal.querySelector('input, textarea, select, button');
    if (first && typeof first.focus === 'function') first.focus();
  }
  function closeAddModal() {
    var modal = document.getElementById('addGameModal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }

  // Eventos globales para cerrar modales con clic fuera o ESC
  afterLoad(function(){
    window.addEventListener('click', function (event) {
      var modal = document.getElementById('editModal');
      if (event.target === modal) closeModal();
      var help = document.getElementById('helpModal');
      if (event.target === help) closeHelpModal();
      var create = document.getElementById('createModal');
      if (event.target === create) closeCreateModal();
      var add = document.getElementById('addGameModal');
      if (event.target === add) closeAddModal();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeModal();
        closeHelpModal();
        closeCreateModal();
        closeAddModal();
      }
    });
  });

  // --- Módulo Añadir juego: filas ROM y hashes ---
  afterLoad(function(){
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
      var idx = romsContainer ? romsContainer.querySelectorAll('.rom-row').length : 0;
      var row = document.createElement('div');
      row.className = 'rom-row';
      row.dataset.index = String(idx);
      row.innerHTML = "\n      <div class=\"rom-file\">\n        <input type=\"file\" class=\"ag_file\" accept=\"*/*\">\n        <button type=\"button\" class=\"ag_calc_btn secondary\">Calcular hashes</button>\n      </div>\n      <div class=\"rom-fields\">\n        <input type=\"text\" class=\"ag_rom\" name=\"rom_name[]\" placeholder=\"Nombre de ROM\" required>\n        <input type=\"text\" class=\"ag_size\" name=\"size[]\" placeholder=\"Tamaño (bytes)\" required>\n        <input type=\"text\" class=\"ag_crc\" name=\"crc[]\" placeholder=\"CRC32 (8 hex)\" required>\n        <input type=\"text\" class=\"ag_md5\" name=\"md5[]\" placeholder=\"MD5 (32 hex)\" required>\n        <input type=\"text\" class=\"ag_sha1\" name=\"sha1[]\" placeholder=\"SHA1 (40 hex)\" required>\n        <button type=\"button\" class=\"ag_remove_btn danger\" aria-label=\"Eliminar esta ROM\">Eliminar ROM</button>\n      </div>";
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
        if (target.classList.contains('ag_remove_btn')) {
          var rowDel = target.closest('.rom-row');
          if (rowDel && romsContainer.children.length > 1) {
            romsContainer.removeChild(rowDel);
          }
          updateRemoveButtons();
        }
      });
    }
    updateRemoveButtons();
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
          if (row.querySelector('.ag_crc')) row.querySelector('.ag_crc').value = crc.toUpperCase();
          if (row.querySelector('.ag_md5')) row.querySelector('.ag_md5').value = md5.toLowerCase();
          if (row.querySelector('.ag_sha1')) row.querySelector('.ag_sha1').value = sha1.toLowerCase();
          if (!/^\d+$/.test(size)) errors.push('El tamaño debe ser un número entero en bytes.');
          if (!/^[0-9A-Fa-f]{8}$/.test(crc.toUpperCase())) errors.push('CRC32 debe tener 8 hex.');
          if (!/^[0-9a-fA-F]{32}$/.test(md5)) errors.push('MD5 debe tener 32 hex.');
          if (!/^[0-9a-fA-F]{40}$/.test(sha1)) errors.push('SHA1 debe tener 40 hex.');
        });
        if (errors.length) { ev.preventDefault(); alert(errors.join('\n')); }
      });
    }
  });

  // --- Módulo Editar: filas ROM, hashes y validación ---
  afterLoad(function(){
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
          crc = crc.toUpperCase(); md5 = md5.toLowerCase(); sha1 = sha1.toLowerCase();
          if (row.querySelector('.eg_crc')) row.querySelector('.eg_crc').value = crc;
          if (row.querySelector('.eg_md5')) row.querySelector('.eg_md5').value = md5;
          if (row.querySelector('.eg_sha1')) row.querySelector('.eg_sha1').value = sha1;
          if (!/^\d+$/.test(size)) errors.push('El tamaño debe ser un número entero en bytes.');
          if (!/^[0-9A-Fa-f]{8}$/.test(crc)) errors.push('CRC32 debe tener 8 hex.');
          if (!/^[0-9a-fA-F]{32}$/.test(md5)) errors.push('MD5 debe tener 32 hex.');
          if (!/^[0-9a-fA-F]{40}$/.test(sha1)) errors.push('SHA1 debe tener 40 hex.');
        });
        if (errors.length) { ev.preventDefault(); alert(errors.join('\n')); }
      });
    }
  });

  // Exponer funciones en window para botones en HTML (si se usan atributos onclick)
  window.openEditModal = openEditModal;
  window.openEditModalMachine = openEditModalMachine;
  window.openHelpModal = openHelpModal;
  window.closeHelpModal = closeHelpModal;
  window.openCreateModal = openCreateModal;
  window.closeCreateModal = closeCreateModal;
  window.openAddModal = openAddModal;
  window.closeAddModal = closeAddModal;
})();
