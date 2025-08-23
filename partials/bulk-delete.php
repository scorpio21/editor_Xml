<?php
declare(strict_types=1);
?>
<div class="bulk-delete">
    <h2>Eliminación masiva por filtros</h2>
    <?php 
        $bf = $_SESSION['bulk_filters'] ?? ['include'=>'','exclude'=>'','include_regions'=>[],'exclude_langs'=>[]];
        $selRegions = is_array($bf['include_regions'] ?? null) ? $bf['include_regions'] : [];
        $selLangs = is_array($bf['exclude_langs'] ?? null) ? $bf['exclude_langs'] : [];
    ?>
    <form method="post" class="bulk-delete-form" id="bulk-delete-form">
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
