<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
require_once __DIR__ . '/../inc/i18n.php';
?>
<div id="addGameModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addGameTitle" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="addGameTitle"><?= htmlspecialchars(t('add_game.title')) ?></h3>
      <button type="button" class="close" aria-label="<?= htmlspecialchars(t('common.close')) ?>" onclick="closeAddModal()">×</button>
    </div>
    <div class="modal-body">
      <form method="post" id="add-game-form">
        <input type="hidden" name="action" value="add_game">
        <?= campoCSRF() ?>
        <div class="form-row">
          <label><?= htmlspecialchars(t('add_game.roms_label')) ?></label>
          <div id="roms-container">
            <div class="rom-row" data-index="0">
              <div class="rom-file">
                <input type="file" class="ag_file" accept="*/*">
                <button type="button" class="ag_calc_btn secondary"><?= htmlspecialchars(t('add_game.calc_hashes')) ?></button>
              </div>
              <div class="rom-fields">
                <input type="text" class="ag_rom" name="rom_name[]" placeholder="<?= htmlspecialchars(t('add_game.placeholders.rom_name')) ?>" required>
                <input type="text" class="ag_size" name="size[]" placeholder="<?= htmlspecialchars(t('add_game.placeholders.size')) ?>" required>
                <input type="text" class="ag_crc" name="crc[]" placeholder="<?= htmlspecialchars(t('add_game.placeholders.crc')) ?>" required>
                <input type="text" class="ag_md5" name="md5[]" placeholder="<?= htmlspecialchars(t('add_game.placeholders.md5')) ?>" required>
                <input type="text" class="ag_sha1" name="sha1[]" placeholder="<?= htmlspecialchars(t('add_game.placeholders.sha1')) ?>" required>
                <button type="button" class="ag_remove_btn danger" aria-label="<?= htmlspecialchars(t('add_game.remove_rom_aria')) ?>"><?= htmlspecialchars(t('add_game.remove_rom')) ?></button>
              </div>
            </div>
          </div>
          <button type="button" id="ag_add_rom_btn" class="secondary"><?= htmlspecialchars(t('add_game.add_rom')) ?></button>
        </div>
        <div class="form-row">
          <label for="ag_name"><?= htmlspecialchars(t('add_game.name_label')) ?></label>
          <input type="text" id="ag_name" name="game_name" required>
        </div>
        <div class="form-row">
          <label for="ag_desc"><?= htmlspecialchars(t('add_game.desc_label')) ?></label>
          <textarea id="ag_desc" name="description" rows="3" required></textarea>
        </div>
        <div class="form-row">
          <label for="ag_cat"><?= htmlspecialchars(t('add_game.category_label')) ?></label>
          <input type="text" id="ag_cat" name="category" placeholder="<?= htmlspecialchars(t('add_game.optional')) ?>">
        </div>
        <hr>
        <div class="modal-actions">
          <button type="button" class="secondary" onclick="closeAddModal()">&larr; <?= htmlspecialchars(t('common.cancel')) ?></button>
          <button type="submit"><?= htmlspecialchars(t('common.add')) ?></button>
        </div>
      </form>
      <p class="hint"><small><?= htmlspecialchars(t('add_game.hint')) ?></small></p>
    </div>
  </div>
</div>
