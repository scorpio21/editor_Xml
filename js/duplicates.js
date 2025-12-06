// Lógica de gestión de duplicados
(function () {
    'use strict';

    var afterLoad = (window.AppUtils && window.AppUtils.afterLoad) ? window.AppUtils.afterLoad : function (cb) {
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', cb); } else { cb(); }
    };

    var currentGroups = [];

    afterLoad(function () {
        var detectBtn = document.getElementById('detect-duplicates-btn');
        var exportCsvBtn = document.getElementById('export-dup-csv-btn');
        var selectOriginalsBtn = document.getElementById('select-all-originals-btn');
        var selectSpanishBtn = document.getElementById('select-all-spanish-btn');
        var selectLatestRevBtn = document.getElementById('select-latest-rev-btn');
        var container = document.getElementById('duplicate-groups-container');
        var exportSection = document.getElementById('duplicate-export-section');
        var duplicatesForm = document.getElementById('duplicates-form');
        var noMsg = document.getElementById('no-duplicates-msg');
        var countText = document.getElementById('duplicates-count-text');

        if (!detectBtn || !container || !duplicatesForm) return;

        // Detectar duplicados
        detectBtn.addEventListener('click', async function () {
            detectBtn.disabled = true;
            detectBtn.textContent = 'Detectando...';

            try {
                var formData = new FormData();
                formData.append('action', 'detect_duplicates');
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                var res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData,
                    credentials: 'same-origin'
                });

                var data = await res.json();

                if (!data.ok) {
                    alert(data.message || 'Error al detectar duplicados.');
                    return;
                }

                currentGroups = data.groups || [];
                renderGroups(currentGroups);

                if (currentGroups.length > 0) {
                    if (exportCsvBtn) exportCsvBtn.disabled = false;
                    if (selectOriginalsBtn) selectOriginalsBtn.disabled = false;
                    if (selectSpanishBtn) selectSpanishBtn.disabled = false;
                    if (selectLatestRevBtn) selectLatestRevBtn.disabled = false;
                    if (noMsg) noMsg.style.display = 'none';
                } else {
                    if (noMsg) {
                        noMsg.textContent = 'No se encontraron duplicados.';
                        noMsg.style.display = 'block';
                    }
                }

            } catch (e) {
                console.error(e);
                alert('Error de red al detectar duplicados.');
            } finally {
                detectBtn.disabled = false;
                detectBtn.textContent = 'Detectar Duplicados';
            }
        });

        // Exportar CSV
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', function () {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;

                var actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'export_duplicates_csv';
                form.appendChild(actionInput);

                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('input[name="csrf_token"]').value;
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        }

        // Sugerir originales (sin idiomas específicos)
        if (selectOriginalsBtn) {
            selectOriginalsBtn.addEventListener('click', function () {
                currentGroups.forEach(function (group, gIdx) {
                    // Buscar entrada sin idiomas
                    var noLangIdx = -1;
                    for (var i = 0; i < group.entries.length; i++) {
                        if (!group.entries[i].languages || group.entries[i].languages.length === 0) {
                            noLangIdx = i;
                            break;
                        }
                    }
                    if (noLangIdx >= 0) {
                        var radio = document.querySelector('input[name="keep_group_' + gIdx + '"][value="' + noLangIdx + '"]');
                        if (radio) radio.checked = true;
                    }
                });
                updateSelection();
            });
        }

        // Sugerir versiones con español
        if (selectSpanishBtn) {
            selectSpanishBtn.addEventListener('click', function () {
                currentGroups.forEach(function (group, gIdx) {
                    // Buscar entrada con español
                    var spanishIdx = -1;
                    for (var i = 0; i < group.entries.length; i++) {
                        var langs = group.entries[i].languages || [];
                        if (langs.includes('Es')) {
                            spanishIdx = i;
                            break;
                        }
                    }
                    if (spanishIdx >= 0) {
                        var radio = document.querySelector('input[name="keep_group_' + gIdx + '"][value="' + spanishIdx + '"]');
                        if (radio) radio.checked = true;
                    } else {
                        // Si no hay español, seleccionar el primero
                        var radio = document.querySelector('input[name="keep_group_' + gIdx + '"][value="0"]');
                        if (radio) radio.checked = true;
                    }
                });
                updateSelection();
            });
        }

        // Sugerir última revisión
        if (selectLatestRevBtn) {
            selectLatestRevBtn.addEventListener('click', function () {
                currentGroups.forEach(function (group, gIdx) {
                    // Buscar la revisión más alta
                    var maxRev = -1;
                    var maxIdx = 0;
                    group.entries.forEach(function (entry, eIdx) {
                        var rev = entry.revision || 0;
                        if (rev > maxRev) {
                            maxRev = rev;
                            maxIdx = eIdx;
                        }
                    });
                    var radio = document.querySelector('input[name="keep_group_' + gIdx + '"][value="' + maxIdx + '"]');
                    if (radio) radio.checked = true;
                });
                updateSelection();
            });
        }

        // Renderizar grupos de duplicados
        function renderGroups(groups) {
            if (!container) return;
            container.innerHTML = '';

            if (groups.length === 0) {
                container.innerHTML = '<p class="hint">No se encontraron duplicados.</p>';
                if (exportSection) exportSection.style.display = 'none';
                return;
            }

            groups.forEach(function (group, gIdx) {
                var groupDiv = document.createElement('div');
                groupDiv.className = 'duplicate-group';

                var title = document.createElement('h4');
                title.innerHTML = escapeHtml(group.base) + ' <span class="dup-count">' + group.entries.length + ' duplicados</span>';
                groupDiv.appendChild(title);

                var itemsDiv = document.createElement('div');
                itemsDiv.className = 'duplicate-items';

                group.entries.forEach(function (entry, eIdx) {
                    var label = document.createElement('label');
                    label.className = 'duplicate-item';

                    var radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'keep_group_' + gIdx;
                    radio.value = String(eIdx);
                    radio.setAttribute('data-index', String(entry.index));

                    var details = document.createElement('div');
                    details.className = 'item-details';

                    var name = document.createElement('span');
                    name.className = 'item-name';
                    name.textContent = entry.name;
                    details.appendChild(name);

                    var meta = document.createElement('span');
                    meta.className = 'item-meta';
                    var badges = [];
                    if (entry.region) {
                        badges.push('<span class="badge">' + escapeHtml(entry.region) + '</span>');
                    }
                    if (entry.languages && entry.languages.length > 0) {
                        var hasSpanish = entry.languages.includes('Es');
                        badges.push('<span class="badge' + (hasSpanish ? ' spanish' : '') + '">Idiomas: ' + escapeHtml(entry.languages.join(',')) + '</span>');
                    }
                    if (entry.revision !== null) {
                        badges.push('<span class="badge revision">Rev ' + entry.revision + '</span>');
                    }
                    meta.innerHTML = badges.join(' ');
                    details.appendChild(meta);

                    label.appendChild(radio);
                    label.appendChild(details);
                    itemsDiv.appendChild(label);
                });

                groupDiv.appendChild(itemsDiv);
                container.appendChild(groupDiv);
            });

            // Event listener para cambios en radios
            container.addEventListener('change', function (e) {
                if (e.target.type === 'radio') {
                    updateSelection();
                }
            });

            updateSelection();
        }

        // Actualizar estado de selección y calcular índices a eliminar
        function updateSelection() {
            if (!exportSection || !countText) return;

            // Marcar visualmente items seleccionados
            var allItems = document.querySelectorAll('.duplicate-item');
            allItems.forEach(function (item) {
                var radio = item.querySelector('input[type="radio"]');
                if (radio && radio.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });

            // Calcular índices a eliminar
            var toDelete = [];
            currentGroups.forEach(function (group, gIdx) {
                var selected = document.querySelector('input[name="keep_group_' + gIdx + '"]:checked');
                if (!selected) return; // No hay selección en este grupo

                var keepIdx = parseInt(selected.value, 10);
                group.entries.forEach(function (entry, eIdx) {
                    if (eIdx !== keepIdx) {
                        toDelete.push(entry.index);
                    }
                });
            });

            // Eliminar todos los inputs anteriores delete_indices[]
            var oldInputs = duplicatesForm.querySelectorAll('input[name="delete_indices[]"]');
            oldInputs.forEach(function (inp) { inp.remove(); });

            // Crear nuevos inputs para cada índice
            toDelete.forEach(function (idx) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'delete_indices[]';
                inp.value = String(idx);
                duplicatesForm.appendChild(inp);
            });

            // Mostrar sección de exportación
            if (toDelete.length > 0) {
                exportSection.style.display = 'block';
                countText.textContent = 'Se eliminarán ' + toDelete.length + ' duplicados. Se mantendrán las versiones seleccionadas.';
            } else {
                exportSection.style.display = 'none';
            }
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    });
})();
