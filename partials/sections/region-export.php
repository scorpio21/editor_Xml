<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/csrf-helper.php';
?>
<div class="region-export">
  <h3>Exportar por región</h3>
  <p class="hint">Selecciona una o varias regiones (por nombre/descr./categoría) para contar o exportar a un nuevo XML.</p>
  <?php
    $regionsAll = ['Japon','Europa','USA','Asia','Australia','Escandinavia','Corea','China','Hong Kong','Taiwan','Rusia','España','Alemania','Francia','Italia','Paises Bajos','Portugal','Brasil','Mexico','Reino Unido','Norteamerica','Mundo/Internacional','PAL','NTSC'];
  ?>
  <form method="post" class="region-export-form">
    <?= campoCSRF() ?>
    <label for="region_list"><strong>Regiones a incluir</strong></label>
    <select id="region_list" name="include_regions[]" multiple size="8">
      <?php foreach ($regionsAll as $r): ?>
        <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
      <?php endforeach; ?>
    </select>

    <div class="actions">
      <button type="submit" name="action" value="export_region_count" class="secondary">Contar coincidencias</button>
      <button type="submit" name="action" value="export_region_xml">Exportar por región (XML)</button>
      <button type="submit" name="action" value="export_region_csv" class="secondary">Exportar por región (CSV)</button>
    </div>

    <p class="hint">El conteo y la exportación usan el mismo criterio de regiones que la eliminación masiva y la herramienta de deduplicados.</p>
  </form>
</div>
