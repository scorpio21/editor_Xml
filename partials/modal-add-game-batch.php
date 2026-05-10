<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
require_once __DIR__ . '/../inc/i18n.php';
?>
<div id="addGameBatchModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addGameBatchTitle" aria-hidden="true">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="addGameBatchTitle">Añadir juegos (drag&drop)</h3>
      <button type="button" class="close" aria-label="<?= htmlspecialchars(t('common.close')) ?>" onclick="closeAddBatchModal()">×</button>
    </div>
    <div class="modal-body">
      <form method="post" id="add-game-batch-form">
        <input type="hidden" name="action" value="add_game">
        <?= campoCSRF() ?>
        <?php
          // Construir lista de categorías únicas a partir del XML actual
          $catOptions = [];
          $xmlFile = __DIR__ . '/../uploads/current.xml';
          if (file_exists($xmlFile)) {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->resolveExternals = false;
            $dom->substituteEntities = false;
            $dom->validateOnParse = false;
            if (@$dom->load($xmlFile, LIBXML_NONET)) {
              $xp = new DOMXPath($dom);
              $nodes = $xp->query('/datafile/*[self::game or self::machine]/category');
              $seen = [];
              if ($nodes) {
                foreach ($nodes as $n) {
                  if (!($n instanceof DOMElement)) { continue; }
                  $val = trim((string)$n->nodeValue);
                  if ($val === '') { continue; }
                  $norm = strtoupper(preg_replace('/\s+/', ' ', $val) ?? $val);
                  $norm = rtrim($norm, ": \t\r\n");
                  if ($norm === '') { continue; }
                  if (!isset($seen[$norm])) {
                    $disp = rtrim($val, ": \t\r\n");
                    $seen[$norm] = $disp;
                  }
                }
              }
              if (!empty($seen)) {
                $catOptions = array_values($seen);
                natcasesort($catOptions);
                $catOptions = array_values($catOptions);
              }
            }
          }
          // Categorías por defecto para XML nuevos o vacíos
          $defaultCategories = ['Applications', 'Games', 'Preproduction'];
          
          // Agregar categorías por defecto si no existen
          foreach ($defaultCategories as $defCat) {
            $hasCat = false;
            foreach ($catOptions as $c) {
              if (strcasecmp($c, $defCat) === 0) { $hasCat = true; break; }
            }
            if (!$hasCat) { $catOptions[] = $defCat; }
          }
          
          // Ordenar alfabéticamente todas las categorías
          natcasesort($catOptions);
          $catOptions = array_values($catOptions);
        ?>
        <div class="form-row">
          <label for="ag_batch_category_select">Categoría</label>
          <select id="ag_batch_category_select" name="category">
            <option value="">(Sin categoría)</option>
            <?php foreach ($catOptions as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <label>Arrastra aquí tus juegos comprimidos</label>
          <div id="ag-batch-dropzone" class="dropzone" role="button" tabindex="0" aria-label="Arrastra y suelta archivos comprimidos para añadir juegos">
            <div class="dropzone-title">Soltar archivos aquí</div>
            <div class="dropzone-subtitle">Se añadirá 1 juego por archivo, calculando hashes automáticamente.</div>
          </div>
          <input type="file" id="ag_batch_files" class="visually-hidden" multiple accept="*/*">
        </div>
        <div class="modal-actions">
          <button type="button" class="secondary" onclick="closeAddBatchModal()">&larr; <?= htmlspecialchars(t('common.cancel')) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
