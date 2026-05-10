// Lógica de modales: abrir/cerrar y gestión de filas ROM en formularios dentro de modales
(function(){
  'use strict';

  // Evitar que el navegador "abra/navegue" cuando el usuario suelta archivos desde el explorador.
  // Se aplica solo a drag&drop de ficheros (no a otros drags internos).

  var afterLoad = (window.AppUtils && window.AppUtils.afterLoad) ? window.AppUtils.afterLoad : function(cb){
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
  };

  // --- Accesibilidad: utilidades para modales (gestión de foco y ARIA) ---
  function getFocusableIn(modal) {
    if (!modal) return [];
    var selectors = [
      'a[href]', 'button:not([disabled])', 'input:not([disabled])', 'select:not([disabled])',
      'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])'
    ];
    var nodes = modal.querySelectorAll(selectors.join(','));
    return Array.prototype.filter.call(nodes, function(el){
      // visible y dentro del modal
      return el && typeof el.focus === 'function' && el.offsetParent !== null;
    });
  }
  function a11yOpenModal(modal) {
    if (!modal) return;
    // Recordar disparador para devolver el foco al cerrar
    modal.__lastTrigger = (document.activeElement && document.activeElement instanceof HTMLElement)
      ? document.activeElement : null;
    // ARIA
    if (!modal.getAttribute('role')) modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-hidden', 'false');
    // Mostrar
    modal.style.display = 'block';
    // Foco inicial
    var focusables = getFocusableIn(modal);
    if (focusables.length) {
      try { focusables[0].focus(); } catch (e) {}
    } else {
      // Garantizar foco para lectores de pantalla
      if (!modal.hasAttribute('tabindex')) modal.setAttribute('tabindex', '-1');
      try { modal.focus(); } catch (e) {}
    }
    // Trampa de foco con Tab dentro del modal
    modal.__trapHandler = function(e){
      if (e.key !== 'Tab') return;
      var list = getFocusableIn(modal);
      if (!list.length) { e.preventDefault(); return; }
      var first = list[0];
      var last = list[list.length - 1];
      var active = document.activeElement;
      if (e.shiftKey) {
        if (active === first || !modal.contains(active)) { e.preventDefault(); try { last.focus(); } catch(_){} }
      } else {
        if (active === last || !modal.contains(active)) { e.preventDefault(); try { first.focus(); } catch(_){} }
      }
    };
    modal.addEventListener('keydown', modal.__trapHandler);
  }
  function a11yCloseModal(modal) {
    if (!modal) return;
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    if (modal.__trapHandler) {
      modal.removeEventListener('keydown', modal.__trapHandler);
      modal.__trapHandler = null;
    }
    // Devolver foco al disparador
    if (modal.__lastTrigger && typeof modal.__lastTrigger.focus === 'function') {
      try { modal.__lastTrigger.focus(); } catch (e) {}
    }
  }

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
    if (modal) { a11yOpenModal(modal); }
  }
  function closeModal() {
    var m = document.getElementById('editModal');
    if (m) a11yCloseModal(m);
  }

  // --- Modal ayuda ---
  function openHelpModal() {
    var modal = document.getElementById('helpModal');
    if (!modal) return;
    a11yOpenModal(modal);
  }
  function closeHelpModal() {
    var modal = document.getElementById('helpModal');
    if (!modal) return;
    a11yCloseModal(modal);
  }

  // --- Modal crear XML ---
  function openCreateModal() {
    var modal = document.getElementById('createModal');
    if (!modal) return;
    a11yOpenModal(modal);
  }
  function closeCreateModal() {
    var modal = document.getElementById('createModal');
    if (!modal) return;
    a11yCloseModal(modal);
  }

  // --- Modal añadir juego ---
  function openAddModal() {
    var modal = document.getElementById('addGameModal');
    if (!modal) return;
    a11yOpenModal(modal);
  }
  function closeAddModal() {
    var modal = document.getElementById('addGameModal');
    if (!modal) return;
    a11yCloseModal(modal);
  }

  // --- Modal añadir juegos (lote drag&drop) ---
  function openAddBatchModal() {
    var modal = document.getElementById('addGameBatchModal');
    if (!modal) return;
    a11yOpenModal(modal);
  }
  function closeAddBatchModal() {
    var modal = document.getElementById('addGameBatchModal');
    if (!modal) return;
    a11yCloseModal(modal);
  }

  function isBatchModalOpen() {
    var modal = document.getElementById('addGameBatchModal');
    if (!modal) return false;
    var hidden = modal.getAttribute('aria-hidden');
    return hidden !== 'true';
  }

  // Bloqueo global (captura): evita que el navegador navegue/abra/descargue archivos
  // al soltarlos encima de la página.
  function bloquearDropDefault(e) {
    if (!e || !e.dataTransfer) return;
    if (!isBatchModalOpen()) return;
    var types = e.dataTransfer.types;
    var esFichero = false;
    try {
      if (types && typeof types.contains === 'function') {
        esFichero = types.contains('Files');
      } else if (types && typeof types.length === 'number') {
        for (var i = 0; i < types.length; i++) {
          if (types[i] === 'Files') { esFichero = true; break; }
        }
      }
    } catch (_) {
      esFichero = false;
    }
    if (!esFichero) {
      try {
        if (e.dataTransfer.items && e.dataTransfer.items.length) {
          esFichero = (e.dataTransfer.items[0].kind === 'file');
        } else if (e.dataTransfer.files && e.dataTransfer.files.length) {
          esFichero = true;
        }
      } catch (_) {
        esFichero = false;
      }
    }
    if (!esFichero) return;

    // Si el drop/drag cae dentro del dropzone, dejamos que el handler del dropzone
    // procese los ficheros (solo prevenimos la navegación por defecto del navegador).
    var dentroDropzone = false;
    try {
      var t = e.target;
      if (t && typeof t.closest === 'function') {
        dentroDropzone = !!t.closest('#ag-batch-dropzone');
      }
    } catch (_) {
      dentroDropzone = false;
    }

    e.preventDefault();
    if (!dentroDropzone) {
      try { e.stopPropagation(); } catch (_) {}
    }
    try { e.dataTransfer.dropEffect = 'copy'; } catch (_) {}
  }

  window.addEventListener('dragenter', bloquearDropDefault, true);
  window.addEventListener('dragover', bloquearDropDefault, true);
  window.addEventListener('drop', bloquearDropDefault, true);

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
    var addForm = document.getElementById('add-game-form');

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

    function nombreBaseArchivo(filename) {
      return String(filename || '').replace(/\.[^/.]+$/, '');
    }

    async function enviarJuegoDesdeArchivo(file) {
      if (!addForm) { throw new Error('Formulario no disponible'); }
      if (!window.Hashes || typeof window.Hashes.calcularDesdeFile !== 'function') {
        throw new Error('Módulo de hashes no disponible');
      }

      var csrfEl = addForm.querySelector('input[name="csrf_token"]');
      var csrf = csrfEl ? String(csrfEl.value || '') : '';
      if (!csrf) { throw new Error('Token CSRF ausente'); }

      var resHashes = await window.Hashes.calcularDesdeFile(file);

      var fd = new FormData();
      fd.append('action', 'add_game');
      fd.append('csrf_token', csrf);
      fd.append('game_name', nombreBaseArchivo(file.name));
      fd.append('description', nombreBaseArchivo(file.name));
      fd.append('category', '');
      fd.append('rom_name[]', file.name);
      fd.append('size[]', String(resHashes.size));
      fd.append('crc[]', String(resHashes.crc));
      fd.append('md5[]', String(resHashes.md5));
      fd.append('sha1[]', String(resHashes.sha1));

      var resp = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
        credentials: 'same-origin'
      });
      var data = null;
      try { data = await resp.json(); } catch (_) { data = null; }
      if (!resp.ok || !data || data.ok !== true) {
        var msg = (data && data.message) ? data.message : 'No se pudo añadir el juego.';
        throw new Error(msg);
      }
      return data;
    }

    async function procesarArchivosEnCola(files, dropzone) {
      if (!files || !files.length) return;
      var total = files.length;
      var ok = 0;
      var fail = 0;
      if (dropzone) {
        var sub = dropzone.querySelector('.dropzone-subtitle');
        if (sub) sub.textContent = 'Procesando ' + total + ' archivo(s)…';
      }
      for (var i = 0; i < files.length; i++) {
        var f = files[i];
        try {
          if (dropzone) {
            var sub2 = dropzone.querySelector('.dropzone-subtitle');
            if (sub2) sub2.textContent = 'Calculando y añadiendo (' + (i + 1) + '/' + total + '): ' + f.name;
          }
          await enviarJuegoDesdeArchivo(f);
          ok++;
        } catch (e) {
          console.error(e);
          fail++;
        }
      }
      if (dropzone) {
        var sub3 = dropzone.querySelector('.dropzone-subtitle');
        if (sub3) sub3.textContent = 'Completado. Añadidos: ' + ok + '. Fallos: ' + fail + '.';
      }
      if (fail > 0) {
        alert('Proceso terminado. Añadidos: ' + ok + '. Fallos: ' + fail + '. Revisa consola para detalles.');
      }

      // Si se añadieron juegos, recargar para verlos reflejados en la lista.
      // El formulario de este modal es para alta manual; el lote se hace vía AJAX.
      if (ok > 0 && fail === 0) {
        try { closeAddBatchModal(); } catch (_) {}
        setTimeout(function(){ window.location.reload(); }, 300);
      }
    }

    function normalizarListaArchivos(fileList) {
      if (!fileList) return [];
      try { return Array.prototype.slice.call(fileList); } catch (_) { return []; }
    }

    // El drag&drop en lote se gestiona en el modal addGameBatchModal

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

  // --- Módulo Añadir juegos (lote drag&drop) ---
  afterLoad(function(){
    var dropzone = document.getElementById('ag-batch-dropzone');
    var batchInput = document.getElementById('ag_batch_files');
    var batchForm = document.getElementById('add-game-batch-form');
    var catSelect = document.getElementById('ag_batch_category_select');

    function getCategoriaBatch() {
      if (catSelect) return String(catSelect.value || '').trim();
      return '';
    }

    function normalizarListaArchivos(fileList) {
      if (!fileList) return [];
      try { return Array.prototype.slice.call(fileList); } catch (_) { return []; }
    }

    function activarDropzoneUI(isOver) {
      if (!dropzone) return;
      if (isOver) dropzone.classList.add('dragover');
      else dropzone.classList.remove('dragover');
    }

    async function enviarJuegoDesdeArchivo(file) {
      if (!batchForm) { throw new Error('Formulario no disponible'); }
      if (!window.Hashes || typeof window.Hashes.calcularDesdeFile !== 'function') {
        throw new Error('Módulo de hashes no disponible');
      }

      var csrfEl = batchForm.querySelector('input[name="csrf_token"]');
      var csrf = csrfEl ? String(csrfEl.value || '') : '';
      if (!csrf) { throw new Error('Token CSRF ausente'); }

      var resHashes = await window.Hashes.calcularDesdeFile(file);

      var fd = new FormData();
      fd.append('action', 'add_game');
      fd.append('csrf_token', csrf);
      fd.append('game_name', String(file.name).replace(/\.[^/.]+$/, ''));
      fd.append('description', String(file.name).replace(/\.[^/.]+$/, ''));
      fd.append('category', getCategoriaBatch());
      fd.append('rom_name[]', file.name);
      fd.append('size[]', String(resHashes.size));
      fd.append('crc[]', String(resHashes.crc));
      fd.append('md5[]', String(resHashes.md5));
      fd.append('sha1[]', String(resHashes.sha1));

      var resp = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
        credentials: 'same-origin'
      });
      var data = null;
      try { data = await resp.json(); } catch (_) { data = null; }
      if (!resp.ok || !data || data.ok !== true) {
        var msg = (data && data.message) ? data.message : 'No se pudo añadir el juego.';
        throw new Error(msg);
      }
      return data;
    }

    async function procesarArchivosEnCola(files) {
      if (!files || !files.length) return;
      var total = files.length;
      var ok = 0;
      var fail = 0;
      if (dropzone) {
        var sub = dropzone.querySelector('.dropzone-subtitle');
        if (sub) sub.textContent = 'Procesando ' + total + ' archivo(s)…';
      }
      for (var i = 0; i < files.length; i++) {
        var f = files[i];
        try {
          if (dropzone) {
            var sub2 = dropzone.querySelector('.dropzone-subtitle');
            if (sub2) sub2.textContent = 'Calculando y añadiendo (' + (i + 1) + '/' + total + '): ' + f.name;
          }
          await enviarJuegoDesdeArchivo(f);
          ok++;
        } catch (e) {
          console.error(e);
          fail++;
        }
      }
      if (dropzone) {
        var sub3 = dropzone.querySelector('.dropzone-subtitle');
        if (sub3) sub3.textContent = 'Completado. Añadidos: ' + ok + '. Fallos: ' + fail + '.';
      }
      if (fail > 0) {
        alert('Proceso terminado. Añadidos: ' + ok + '. Fallos: ' + fail + '. Revisa consola para detalles.');
      }
      if (ok > 0 && fail === 0) {
        try { closeAddBatchModal(); } catch (_) {}
        setTimeout(function(){ window.location.reload(); }, 300);
      }
    }

    if (dropzone && batchInput) {
      dropzone.addEventListener('click', function(){
        try { batchInput.click(); } catch (_) {}
      });
      dropzone.addEventListener('keydown', function(e){
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          try { batchInput.click(); } catch (_) {}
        }
      });
      dropzone.addEventListener('dragover', function(e){
        e.preventDefault();
        if (e.dataTransfer) {
          try { e.dataTransfer.dropEffect = 'copy'; } catch (_) {}
        }
        activarDropzoneUI(true);
      });
      dropzone.addEventListener('dragleave', function(){
        activarDropzoneUI(false);
      });
      dropzone.addEventListener('drop', function(e){
        e.preventDefault();
        activarDropzoneUI(false);
        var files = normalizarListaArchivos(e.dataTransfer ? e.dataTransfer.files : null);
        if (dropzone) {
          var sub0 = dropzone.querySelector('.dropzone-subtitle');
          if (sub0) sub0.textContent = 'Recibidos: ' + (files ? files.length : 0) + ' archivo(s).';
        }
        procesarArchivosEnCola(files);
      });

      batchInput.addEventListener('change', function(){
        var files = normalizarListaArchivos(batchInput.files);
        procesarArchivosEnCola(files);
        try { batchInput.value = ''; } catch (_) {}
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
      editForm.addEventListener('submit', async function(ev){
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
        if (errors.length) { ev.preventDefault(); alert(errors.join('\n')); return; }

        // Envío AJAX y actualización en vivo
        ev.preventDefault();
        try {
          var formData = new FormData(editForm);
          var res = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
            credentials: 'same-origin'
          });
          var data = null;
          try { data = await res.json(); } catch (_) { data = null; }
          if (!res.ok || !data || data.ok !== true) {
            var msg = (data && data.message) ? data.message : 'No se pudo guardar la edición.';
            alert(msg);
            return;
          }
          // Actualizar tarjeta correspondiente
          actualizarTarjetaEditada(data);
          closeModal();
        } catch (e) {
          console.error(e);
          alert('Error de red al guardar los cambios.');
        }
      });
    }

    function escapeHtml(str){
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function actualizarTarjetaEditada(resp){
      var sel = '.game[data-absindex="' + String(resp.index) + '"][data-type="' + String(resp.node_type) + '"]';
      var card = document.querySelector(sel);
      if (!card) return;
      // Actualizar data-attrs
      card.setAttribute('data-name', resp.name || '');
      card.setAttribute('data-description', resp.description || '');
      if (resp.node_type === 'game') { card.setAttribute('data-category', resp.category || ''); }
      var roms = Array.isArray(resp.roms) ? resp.roms : [];
      card.setAttribute('data-roms', JSON.stringify(roms));
      var first = roms[0] || null;
      card.setAttribute('data-romname', first ? (first.name || '') : '');
      card.setAttribute('data-size', first ? (first.size || '') : '');
      card.setAttribute('data-crc', first ? (first.crc || '') : '');
      card.setAttribute('data-md5', first ? (first.md5 || '') : '');
      card.setAttribute('data-sha1', first ? (first.sha1 || '') : '');

      // Actualizar campos visibles
      actualizarLineaInfo(card, 'Nombre:', resp.name || '');
      actualizarLineaInfo(card, 'Descripción:', resp.description || '');
      if (card.getAttribute('data-type') === 'game') {
        actualizarLineaInfo(card, 'Categoría:', resp.category || '');
      } else {
        actualizarLineaInfo(card, 'Categoría:', '—');
      }
      renderizarRoms(card, roms);
    }

    function actualizarLineaInfo(card, etiqueta, valor){
      var infos = card.querySelectorAll('.game-info');
      infos.forEach(function(div){
        var s = div.querySelector('strong');
        if (!s) return;
        var label = (s.textContent || '').trim();
        if (label === etiqueta) {
          div.innerHTML = '<strong>' + escapeHtml(etiqueta) + '</strong> ' + escapeHtml(valor);
        }
      });
    }

    function renderizarRoms(card, roms){
      var romsBlock = card.querySelector('.game-roms');
      if (roms && roms.length) {
        var html = '<strong>ROMs:</strong><ul>' + roms.map(function(r){
          return '<li>'
            + '<div><strong>Nombre:</strong> ' + escapeHtml(r.name || '') + '</div>'
            + '<div><strong>Tamaño:</strong> ' + escapeHtml(r.size || '') + '</div>'
            + '<div><strong>CRC:</strong> ' + escapeHtml(r.crc || '') + '</div>'
            + '<div><strong>MD5:</strong> ' + escapeHtml(r.md5 || '') + '</div>'
            + '<div><strong>SHA1:</strong> ' + escapeHtml(r.sha1 || '') + '</div>'
            + '</li>';
        }).join('') + '</ul>';
        if (!romsBlock) {
          // Reemplazar el bloque "ROMs: N/A" si existe
          var noRoms = null;
          var infos = card.querySelectorAll('.game-info');
          infos.forEach(function(div){
            var s = div.querySelector('strong');
            if (s && (s.textContent || '').trim() === 'ROMs:') { noRoms = div; }
          });
          romsBlock = document.createElement('div');
          romsBlock.className = 'game-roms';
          if (noRoms && noRoms.parentNode) {
            noRoms.parentNode.replaceChild(romsBlock, noRoms);
          } else {
            card.appendChild(romsBlock);
          }
        }
        romsBlock.innerHTML = html;
      } else {
        // Mostrar N/A
        if (romsBlock && romsBlock.parentNode) {
          var na = document.createElement('div');
          na.className = 'game-info';
          na.innerHTML = '<strong>ROMs:</strong> N/A';
          romsBlock.parentNode.replaceChild(na, romsBlock);
        } else {
          actualizarLineaInfo(card, 'ROMs:', 'N/A');
        }
      }
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
  window.openAddBatchModal = openAddBatchModal;
  window.closeAddBatchModal = closeAddBatchModal;
})();
