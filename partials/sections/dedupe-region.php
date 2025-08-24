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
    <h3>Eliminar duplicados por región</h3>
    <p class="hint">Mantendrá solo una entrada por juego, conservando la versión de la región seleccionada (si existe). No afecta a máquinas.</p>

    <label for="prefer_region">Conservar región:</label>
    <select name="prefer_region" id="prefer_region" required>
      <?php foreach ($regionsAll as $r): ?>
        <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
      <?php endforeach; ?>
    </select>

    <div class="option-row">
      <label>
        <input type="checkbox" name="keep_europe" id="keep_europe" value="1">
        Conservar también Europa
      </label>
      <p class="hint">Si se marca, además de la región preferida se conservarán también las variantes de Europa.</p>
    </div>
  </div>

  <div class="actions">
    <button type="submit" name="action" value="dedupe_region_count" class="secondary">Contar duplicados</button>
    <button type="submit" name="action" value="dedupe_region_export_csv" class="secondary" id="btn-dedupe-export" disabled>Exportar duplicados (CSV)</button>
    <button type="submit" name="action" value="dedupe_region" class="danger" id="btn-dedupe" disabled onclick="return confirm('¿Eliminar duplicados y conservar solo la región seleccionada cuando exista?');">Eliminar duplicados</button>
  </div>

  <div id="dedupe-count-result" class="sr-live" role="status" aria-live="polite" aria-atomic="true"></div>
</form>
