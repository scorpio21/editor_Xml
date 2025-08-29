<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
?>
<div class="bulk-delete">
    <h2><?= htmlspecialchars(t('bulk.h2')) ?></h2>
    <button type="button" class="toggle-bulk" aria-expanded="true" aria-controls="bulk-delete-form" data-text-hide="<?= htmlspecialchars(t('bulk.toggle')) ?>" data-text-show="<?= htmlspecialchars(t('bulk.show')) ?>"><?= htmlspecialchars(t('bulk.toggle')) ?></button>
    <?php 
        $bf = $_SESSION['bulk_filters'] ?? [
            'include'=>'','exclude'=>'','include_regions'=>[],'exclude_langs'=>[],
            'mame_filters'=>[],'mame_driver_status'=>'','mame_clone_filter'=>''
        ];
        $selRegions = is_array($bf['include_regions'] ?? null) ? $bf['include_regions'] : [];
        $selLangs = is_array($bf['exclude_langs'] ?? null) ? $bf['exclude_langs'] : [];
        // Lista de regiones reutilizable
        $regionsAll = ['Japon','Europa','USA','Asia','Australia','Escandinavia','Corea','China','Hong Kong','Taiwan','Rusia','España','Alemania','Francia','Italia','Paises Bajos','Portugal','Brasil','Mexico','Reino Unido','Norteamerica','Mundo/Internacional','PAL','NTSC'];
    ?>
    <form method="post" class="bulk-delete-form" id="bulk-delete-form">
        <?= campoCSRF() ?>
        <div class="fields">
            <label><?= htmlspecialchars(t('bulk.include_regions_label')) ?></label>
            <div class="multi-select" data-ms-name="include_regions[]" data-selected-suffix="<?= htmlspecialchars(t('js.selected_suffix')) ?>">
                <button type="button" class="ms-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="ms-label"><?= htmlspecialchars(t('bulk.select_regions')) ?></span>
                    <span class="ms-caret">▾</span>
                </button>
                <div class="ms-panel" role="listbox">
                    <div class="ms-actions">
                        <button type="button" class="ms-all" data-action="all"><?= htmlspecialchars(t('bulk.all')) ?></button>
                        <button type="button" class="ms-none" data-action="none"><?= htmlspecialchars(t('bulk.none')) ?></button>
                    </div>
                    <div class="ms-options">
                        <?php 
                            foreach ($regionsAll as $r) {
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
            <label><?= htmlspecialchars(t('bulk.exclude_langs_label')) ?></label>
            <div class="multi-select" data-ms-name="exclude_langs[]" data-selected-suffix="<?= htmlspecialchars(t('js.selected_suffix')) ?>">
                <button type="button" class="ms-trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="ms-label"><?= htmlspecialchars(t('bulk.select_langs')) ?></span>
                    <span class="ms-caret">▾</span>
                </button>
                <div class="ms-panel" role="listbox">
                    <div class="ms-actions">
                        <button type="button" class="ms-all" data-action="all"><?= htmlspecialchars(t('bulk.all')) ?></button>
                        <button type="button" class="ms-none" data-action="none"><?= htmlspecialchars(t('bulk.none')) ?></button>
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
            <button type="button" class="secondary" id="clear-filters"><?= htmlspecialchars(t('bulk.clear_filters')) ?></button>
            <button type="submit" name="action" value="reset_filters" class="secondary"><?= htmlspecialchars(t('bulk.reset_filters')) ?></button>
            <button type="submit" name="action" value="bulk_count" class="secondary"><?= htmlspecialchars(t('bulk.count_matches')) ?></button>
            <button type="submit" name="action" value="bulk_delete" class="danger" onclick="return confirm('<?= htmlspecialchars(t('bulk.confirm_delete'), ENT_QUOTES) ?>');"><?= htmlspecialchars(t('bulk.delete_filtered')) ?></button>
        </div>
        <p class="hint"><?= htmlspecialchars(t('bulk.hint.lines')) ?></p>
        <div id="count-result" class="sr-live" role="status" aria-live="polite" aria-atomic="true"></div>
    </form>

    <div class="hint" aria-live="polite"><?= htmlspecialchars(t('bulk.moved_hint')) ?></div>
</div>
