<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
require_once __DIR__ . '/../inc/i18n.php';
?>
<!-- Modal editar -->
<div class="modal" id="editModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="editTitle" aria-describedby="editRomsLegend">
    <div class="modal-content">
        <button type="button" class="close" aria-label="<?= htmlspecialchars(t('common.close')) ?>" onclick="closeModal()">&times;</button>
        <h3 id="editTitle"><?= htmlspecialchars(t('edit.title')) ?></h3>
        <form method="post" id="edit-game-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="index" id="editIndex">
            <input type="hidden" name="node_type" id="editNodeType">
            <?= campoCSRF() ?>

            <label><?= htmlspecialchars(t('edit.name')) ?></label>
            <input type="text" name="game_name" id="editGameName" required>

            <label><?= htmlspecialchars(t('edit.description')) ?></label>
            <textarea name="description" id="editDescription" rows="3" required></textarea>

            <label><?= htmlspecialchars(t('edit.category')) ?></label>
            <input type="text" name="category" id="editCategory" placeholder="<?= htmlspecialchars(t('edit.category_ph')) ?>">

            <fieldset>
                <legend id="editRomsLegend"><?= htmlspecialchars(t('edit.roms_legend')) ?></legend>
                <div id="edit-roms-container" class="roms-container">
                    <!-- filas dinámicas -->
                </div>
                <button type="button" id="edit_add_rom_btn" class="secondary"><?= htmlspecialchars(t('edit.add_rom')) ?></button>
                <p class="hint"><?= htmlspecialchars(t('edit.hashes_hint')) ?></p>
            </fieldset>

            <div class="modal-actions">
                <button type="submit"><?= htmlspecialchars(t('common.save')) ?></button>
            </div>
        </form>
    </div>
    </div>
