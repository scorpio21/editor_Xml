<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
require_once __DIR__ . '/../inc/i18n.php';
?>
<div id="createModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="createModalTitle" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="createModalTitle"><?= htmlspecialchars(t('create_xml.title')) ?></h3>
      <button type="button" class="close" aria-label="<?= htmlspecialchars(t('common.close')) ?>" onclick="closeCreateModal()">×</button>
    </div>
    <div class="modal-body">
        <form method="post" id="create-xml-form">
            <input type="hidden" name="action" value="create_xml">
            <?= campoCSRF() ?>
        <div class="form-row">
          <label for="cx_name"><?= htmlspecialchars(t('create_xml.name')) ?></label>
          <input type="text" id="cx_name" name="name" required>
        </div>
        <div class="form-row">
          <label for="cx_description"><?= htmlspecialchars(t('create_xml.description')) ?></label>
          <textarea id="cx_description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-row">
          <label for="cx_version"><?= htmlspecialchars(t('create_xml.version')) ?></label>
          <input type="text" id="cx_version" name="version" value="1.0" required>
        </div>
        <div class="form-row">
          <label for="cx_date"><?= htmlspecialchars(t('create_xml.date')) ?></label>
          <input type="date" id="cx_date" name="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
        </div>
        <div class="form-row">
          <label for="cx_author"><?= htmlspecialchars(t('create_xml.author')) ?></label>
          <input type="text" id="cx_author" name="author" required>
        </div>
        <div class="form-row">
          <label for="cx_homepage"><?= htmlspecialchars(t('create_xml.homepage_title')) ?></label>
          <input type="text" id="cx_homepage" name="homepage" placeholder="<?= htmlspecialchars(t('create_xml.homepage_ph')) ?>">
        </div>
        <div class="form-row">
          <label for="cx_url"><?= htmlspecialchars(t('create_xml.url')) ?></label>
          <input type="url" id="cx_url" name="url" placeholder="<?= htmlspecialchars(t('create_xml.url_ph')) ?>">
        </div>
        <div class="modal-actions">
          <button type="button" class="secondary" onclick="closeCreateModal()">&larr; <?= htmlspecialchars(t('common.cancel')) ?></button>
          <button type="submit"><?= htmlspecialchars(t('create_xml.submit')) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
