<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/csrf-helper.php';
require_once __DIR__ . '/../../inc/i18n.php';
?>
<div id="search-external" class="search-external" aria-labelledby="tab-btn-6"
     data-archive-found="<?= htmlspecialchars(t('js.archive.found')) ?>"
     data-archive-not-found="<?= htmlspecialchars(t('js.archive.not_found')) ?>"
     data-archive-view="<?= htmlspecialchars(t('js.archive.view')) ?>"
     data-se-md5-invalid="<?= htmlspecialchars(t('js.se.md5_invalid')) ?>"
     data-se-sha1-invalid="<?= htmlspecialchars(t('js.se.sha1_invalid')) ?>"
     data-se-crc-invalid="<?= htmlspecialchars(t('js.se.crc_invalid')) ?>"
     data-se-require-one="<?= htmlspecialchars(t('js.se.require_one')) ?>"
     data-se-no-links="<?= htmlspecialchars(t('js.se.no_links')) ?>"
     data-se-invalid-response="<?= htmlspecialchars(t('js.se.invalid_response')) ?>"
     data-se-no-response="<?= htmlspecialchars(t('js.se.no_response')) ?>"
     data-se-already-running="<?= htmlspecialchars(t('js.se.already_running')) ?>"
     data-se-wait-seconds="<?= htmlspecialchars(t('js.se.wait_seconds')) ?>"
     data-se-checking="<?= htmlspecialchars(t('js.se.checking')) ?>"
     data-se-too-many="<?= htmlspecialchars(t('js.se.too_many')) ?>"
     data-se-could-not-query="<?= htmlspecialchars(t('js.se.could_not_query')) ?>"
     data-se-could-not-query-org="<?= htmlspecialchars(t('js.se.could_not_query_org')) ?>">
  <p class="hint"><?= htmlspecialchars(t('search_external.hint')) ?></p>

  <form id="search-external-form" class="search-external-form" onsubmit="return false" novalidate>
    <fieldset>
      <legend><?= htmlspecialchars(t('search_external.legend')) ?></legend>
      <div class="form-row">
        <label for="se-name"><?= htmlspecialchars(t('search_external.name_label')) ?></label>
        <input type="text" id="se-name" name="name" placeholder="<?= htmlspecialchars(t('search_external.name_placeholder')) ?>">
      </div>
      <div class="form-row">
        <label for="se-md5"><?= htmlspecialchars(t('search_external.md5_label')) ?></label>
        <input type="text" id="se-md5" name="md5" maxlength="32" placeholder="<?= htmlspecialchars(t('search_external.md5_placeholder')) ?>">
      </div>
      <div class="form-row">
        <label for="se-sha1"><?= htmlspecialchars(t('search_external.sha1_label')) ?></label>
        <input type="text" id="se-sha1" name="sha1" maxlength="40" placeholder="<?= htmlspecialchars(t('search_external.sha1_placeholder')) ?>">
      </div>
      <div class="form-row">
        <label for="se-crc"><?= htmlspecialchars(t('search_external.crc_label')) ?></label>
        <input type="text" id="se-crc" name="crc" maxlength="8" placeholder="<?= htmlspecialchars(t('search_external.crc_placeholder')) ?>">
      </div>
    </fieldset>

    <div class="actions">
      <button type="button" id="se-build-links" class="primary"><?= htmlspecialchars(t('search_external.btn_build')) ?></button>
      <button type="button" id="se-open-all" class="secondary" disabled><?= htmlspecialchars(t('search_external.btn_open_all')) ?></button>
      <button type="button" id="se-check-archive" class="secondary"><?= htmlspecialchars(t('search_external.btn_check_archive')) ?></button>
      <input type="hidden" name="csrf_token" id="se-csrf" value="<?= htmlspecialchars(obtenerTokenCSRF(), ENT_QUOTES) ?>">
      <span id="se-errors" class="form-errors" aria-live="polite"></span>
    </div>
  </form>

  <div class="results" id="se-results" hidden>
    <h4><?= htmlspecialchars(t('search_external.links_title')) ?></h4>
    <ul id="se-links" class="links-list"></ul>
    <p class="hint"><?= htmlspecialchars(t('search_external.links_hint')) ?></p>
  </div>

  <div class="results" id="se-archive-check" hidden>
    <h4><?= htmlspecialchars(t('search_external.archive_title')) ?></h4>
    <div id="se-archive-status" class="archive-status" aria-live="polite"></div>
  </div>
</div>
