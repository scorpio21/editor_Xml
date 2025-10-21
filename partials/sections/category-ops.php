<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/csrf-helper.php';
?>
<div class="category-ops">
  <p class="hint">Selecciona una o varias categorías para operar sobre ellas.</p>
  <?php 
    $catOptions = [
      'Add-Ons',
      'Applications',
      'Bonus Discs',
      'Coverdiscs',
      'Demos',
      'Preproduction'
    ];
    $sel = isset($_SESSION['category_filters']) && is_array($_SESSION['category_filters']) ? ($_SESSION['category_filters']['cats'] ?? []) : [];
  ?>

  <form method="post" class="category-form">
    <?= campoCSRF() ?>
    <fieldset class="category-fieldset">
      <legend>Categorías</legend>
      <div class="category-grid">
        <?php foreach ($catOptions as $c): $id = 'cat_' . md5($c); $checked = in_array($c, $sel, true) ? 'checked' : ''; ?>
          <label class="cat-option" for="<?= htmlspecialchars($id) ?>">
            <input type="checkbox" id="<?= htmlspecialchars($id) ?>" name="cats[]" value="<?= htmlspecialchars($c) ?>" <?= $checked ?>>
            <span><?= htmlspecialchars($c) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <div class="actions">
      <button type="submit" name="action" value="category_count" class="secondary">Contar coincidencias</button>
      <button type="submit" name="action" value="category_delete" class="danger" onclick="return confirm('¿Eliminar todas las entradas con las categorías seleccionadas? Esta acción no se puede deshacer.');">Eliminar por categoría</button>
      <button type="submit" name="action" value="category_export_xml">Exportar coincidencias a XML</button>
    </div>

    <p class="hint">Las categorías se comparan contra el nodo <category> de cada <game> o <machine>. Se usa coincidencia por prefijo, sin distinguir mayúsculas/minúsculas.</p>
  </form>
</div>
