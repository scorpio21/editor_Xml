<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/csrf-helper.php';
// Formulario para eliminar duplicados por región
// Se usa en la pestaña 5 cuando hay XML cargado.

$regionsAll = ['Japon','Europa','USA','Asia','Australia','Escandinavia','Corea','China','Hong Kong','Taiwan','Rusia','España','Alemania','Francia','Italia','Paises Bajos','Portugal','Brasil','Mexico','Reino Unido','Norteamerica','Mundo/Internacional','PAL','NTSC'];
?>
<form method="post" class="dedupe-form" id="dedupe-region-form">
  <?= campoCSRF() ?>
  <div class="fields mame-filters">
    <h3><?= htmlspecialchars(t('dedupe.h3')) ?></h3>
    <p class="hint"><?= htmlspecialchars(t('dedupe.hint')) ?></p>

    <label for="prefer_region"><?= htmlspecialchars(t('dedupe.label.keep_region')) ?></label>
    <select name="prefer_region" id="prefer_region" required>
      <?php foreach ($regionsAll as $r): ?>
        <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
      <?php endforeach; ?>
    </select>

    <div class="option-row">
      <label>
        <input type="checkbox" name="keep_europe" id="keep_europe" value="1">
        <?= htmlspecialchars(t('dedupe.checkbox.keep_europe')) ?>
      </label>
      <p class="hint"><?= htmlspecialchars(t('dedupe.checkbox.keep_europe_hint')) ?></p>
    </div>
  </div>

  <div class="actions">
    <button type="submit" name="action" value="dedupe_region_count" class="secondary"><?= htmlspecialchars(t('dedupe.btn.count')) ?></button>
    <button type="submit" name="action" value="dedupe_region_export_csv" class="secondary" id="btn-dedupe-export" disabled><?= htmlspecialchars(t('dedupe.btn.export_csv')) ?></button>
    <button type="submit" name="action" value="dedupe_region" class="danger" id="btn-dedupe" disabled onclick="return confirm('<?= htmlspecialchars(t('dedupe.confirm.remove'), ENT_QUOTES) ?>');"><?= htmlspecialchars(t('dedupe.btn.remove')) ?></button>
  </div>

  <div id="dedupe-count-result" class="sr-live" role="status" aria-live="polite" aria-atomic="true"></div>
</form>
