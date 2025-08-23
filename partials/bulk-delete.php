<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
?>
<div class="bulk-delete">
    <h2>Eliminación masiva por filtros</h2>
    <?php 
        $bf = $_SESSION['bulk_filters'] ?? [
            'include'=>'','exclude'=>'','include_regions'=>[],'exclude_langs'=>[],
            'mame_filters'=>[],'mame_driver_status'=>'','mame_clone_filter'=>''
        ];
        $selRegions = is_array($bf['include_regions'] ?? null) ? $bf['include_regions'] : [];
        $selLangs = is_array($bf['exclude_langs'] ?? null) ? $bf['exclude_langs'] : [];
    ?>
    <form method="post" class="bulk-delete-form" id="bulk-delete-form">
        <?= campoCSRF() ?>
        <div class="fields">
            <label>Regiones/países a incluir</label>
            <div class="multi-select" data-ms-name="include_regions[]">
                <button type="button" class="ms-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="ms-label">Seleccionar regiones</span>
                    <span class="ms-caret">▾</span>
                </button>
                <div class="ms-panel" role="listbox">
                    <div class="ms-actions">
                        <button type="button" class="ms-all" data-action="all">Todos</button>
                        <button type="button" class="ms-none" data-action="none">Ninguno</button>
                    </div>
                    <div class="ms-options">
                        <?php 
                            $regions = ['Japon','Europa','USA','Asia','Australia','Escandinavia','Corea','China','Hong Kong','Taiwan','Rusia','España','Alemania','Francia','Italia','Paises Bajos','Portugal','Brasil','Mexico','Reino Unido','Norteamerica','Mundo/Internacional','PAL','NTSC'];
                            foreach ($regions as $r) {
                                $checked = in_array($r, $selRegions, true) ? 'checked' : '';
                                $id = 'msr_' . md5($r);
                                echo '<label class="ms-option" for="'.htmlspecialchars($id).'">'
                                    .'<input type="checkbox" id="'.htmlspecialchars($id).'" name="include_regions[]" value="'.htmlspecialchars($r).'" '.$checked.'>'
                                    .'<span>'.htmlspecialchars($r).'</span>'
                                    .'</label>';
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="fields">
            <label>Idiomas a excluir</label>
            <div class="multi-select" data-ms-name="exclude_langs[]">
                <button type="button" class="ms-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="ms-label">Seleccionar idiomas</span>
                    <span class="ms-caret">▾</span>
                </button>
                <div class="ms-panel" role="listbox">
                    <div class="ms-actions">
                        <button type="button" class="ms-all" data-action="all">Todos</button>
                        <button type="button" class="ms-none" data-action="none">Ninguno</button>
                    </div>
                    <div class="ms-options">
                        <?php 
                            $langs = [
                                'EN'=>'Inglés', 'JA'=>'Japonés', 'FR'=>'Francés', 'DE'=>'Alemán', 'ES'=>'Español', 'IT'=>'Italiano',
                                'NL'=>'Neerlandés', 'PT'=>'Portugués', 'SV'=>'Sueco', 'NO'=>'Noruego', 'DA'=>'Danés', 'FI'=>'Finés',
                                'ZH'=>'Chino', 'KO'=>'Coreano', 'PL'=>'Polaco', 'RU'=>'Ruso', 'CS'=>'Checo', 'HU'=>'Húngaro'
                            ];
                            foreach ($langs as $code=>$label) {
                                $checked = in_array($code, $selLangs, true) ? 'checked' : '';
                                $id = 'msl_' . $code;
                                echo '<label class="ms-option" for="'.htmlspecialchars($id).'">'
                                    .'<input type="checkbox" id="'.htmlspecialchars($id).'" name="exclude_langs[]" value="'.htmlspecialchars($code).'" '.$checked.'>'
                                    .'<span>'.htmlspecialchars($label.' ('.$code.')').'</span>'
                                    .'</label>';
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros específicos MAME -->
        <div class="fields mame-filters" style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
            <h3>Filtros específicos MAME (opcional)</h3>
            <div class="mame-row">
                <label>
                    <input type="checkbox" name="mame_filter_bios" value="1" <?= in_array('bios', $bf['mame_filters'] ?? [], true) ? 'checked' : '' ?>>
                    Excluir BIOS (isbios="yes")
                </label>
                <label>
                    <input type="checkbox" name="mame_filter_device" value="1" <?= in_array('device', $bf['mame_filters'] ?? [], true) ? 'checked' : '' ?>>
                    Excluir dispositivos (isdevice="yes")
                </label>
            </div>
            <div class="mame-row">
                <label>Estado del driver:</label>
                <select name="mame_driver_status">
                    <option value="">Cualquiera</option>
                    <option value="good" <?= ($bf['mame_driver_status'] ?? '') === 'good' ? 'selected' : '' ?>>Bueno</option>
                    <option value="imperfect" <?= ($bf['mame_driver_status'] ?? '') === 'imperfect' ? 'selected' : '' ?>>Imperfecto</option>
                    <option value="preliminary" <?= ($bf['mame_driver_status'] ?? '') === 'preliminary' ? 'selected' : '' ?>>Preliminar</option>
                </select>
            </div>
            <div class="mame-row">
                <label>Filtrar por cloneof:</label>
                <select name="mame_clone_filter">
                    <option value="">Cualquiera</option>
                    <option value="parent_only" <?= ($bf['mame_clone_filter'] ?? '') === 'parent_only' ? 'selected' : '' ?>>Solo padres (sin cloneof)</option>
                    <option value="clone_only" <?= ($bf['mame_clone_filter'] ?? '') === 'clone_only' ? 'selected' : '' ?>>Solo clones (con cloneof)</option>
                </select>
            </div>
            <p class="hint">Estos filtros solo se aplican a nodos &lt;machine&gt; con atributos MAME.</p>
        </div>
        
        <div class="actions">
            <button type="button" class="secondary" id="clear-filters">Limpiar filtros</button>
            <button type="submit" name="action" value="reset_filters" class="secondary">Restablecer filtros</button>
            <button type="submit" name="action" value="bulk_count" class="secondary">Contar coincidencias</button>
            <button type="submit" name="action" value="bulk_delete" class="danger" onclick="return confirm('¿Seguro que deseas eliminar los juegos que coincidan con estos filtros? Esta acción no se puede deshacer.');">Eliminar filtrados</button>
        </div>
        <p class="hint">Se buscará en nombre y descripción. Además:
            • en juegos (game): también en categoría.
            • en máquinas (machine): también en año y fabricante.
            Coincidencia insensible a mayúsculas/minúsculas.</p>
        <div id="count-result" class="sr-live" role="status" aria-live="polite" aria-atomic="true"></div>
    </form>
</div>
