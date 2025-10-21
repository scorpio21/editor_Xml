<?php
declare(strict_types=1);
require_once __DIR__ . '/../../inc/csrf-helper.php';
?>
<div class="category-ops">
  <p class="hint">Selecciona una o varias categorías para operar sobre ellas.</p>
  <?php 
    // Mostrar el nombre del fichero (header/name) o, en su defecto, el nombre original subido
    $fileNameDisplay = '';
    if (isset($xml) && $xml instanceof SimpleXMLElement) {
      $hdrName = $xml->xpath('/datafile/header/name');
      if (is_array($hdrName) && isset($hdrName[0])) {
        $fileNameDisplay = trim((string)$hdrName[0]);
      }
    }
    if ($fileNameDisplay === '' && isset($_SESSION['original_filename'])) {
      $of = (string)$_SESSION['original_filename'];
      $fileNameDisplay = preg_replace('/\.[^.]+$/', '', $of) ?? '';
    }
  ?>
  <?php if ($fileNameDisplay !== ''): ?>
    <p class="hint"><strong>Archivo:</strong> <?= htmlspecialchars($fileNameDisplay) ?></p>
  <?php endif; ?>
  <?php 
    // Construir lista de categorías únicas a partir del XML cargado
    $catOptions = [];
    if (isset($xml) && $xml instanceof SimpleXMLElement) {
      $dom = new DOMDocument();
      $dom->preserveWhiteSpace = false;
      $dom->resolveExternals = false; // Seguridad XXE
      $dom->substituteEntities = false;
      $dom->validateOnParse = false;
      $dom->loadXML($xml->asXML(), LIBXML_NONET);
      $xp = new DOMXPath($dom);
      $nodes = $xp->query('/datafile/*[self::game or self::machine]/category');
      $seen = [];
      if ($nodes) {
        foreach ($nodes as $n) {
          if (!($n instanceof DOMElement)) { continue; }
          $val = trim((string)$n->nodeValue);
          if ($val === '') { continue; }
          // Normalizar para unicidad: mayúsculas, colapsar espacios, quitar ':' final
          $norm = strtoupper(preg_replace('/\s+/', ' ', $val) ?? $val);
          $norm = rtrim($norm, ": \t\r\n");
          if ($norm === '') { continue; }
          if (!isset($seen[$norm])) {
            // Mostrar sin ':' final para consistencia visual
            $disp = rtrim($val, ": \t\r\n");
            $seen[$norm] = $disp;
          }
        }
      }
      if (!empty($seen)) {
        // Ordenar alfabéticamente por display
        $catOptions = array_values($seen);
        natcasesort($catOptions);
        $catOptions = array_values($catOptions);
      }
    }
    $sel = isset($_SESSION['category_filters']) && is_array($_SESSION['category_filters']) ? ($_SESSION['category_filters']['cats'] ?? []) : [];
  ?>

  <form method="post" class="category-form">
    <?= campoCSRF() ?>
    <fieldset class="category-fieldset">
      <legend>Categorías</legend>
      <div class="category-grid">
        <?php if (empty($catOptions)): ?>
          <p class="hint">No se detectaron categorías en el archivo cargado.</p>
        <?php else: ?>
        <?php foreach ($catOptions as $c): $id = 'cat_' . md5($c); $checked = in_array($c, $sel, true) ? 'checked' : ''; ?>
          <label class="cat-option" for="<?= htmlspecialchars($id) ?>">
            <input type="checkbox" id="<?= htmlspecialchars($id) ?>" name="cats[]" value="<?= htmlspecialchars($c) ?>" <?= $checked ?>>
            <span><?= htmlspecialchars($c) ?></span>
          </label>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </fieldset>

    <div class="actions">
      <button type="submit" name="action" value="category_count" class="secondary">Contar coincidencias</button>
      <button type="submit" name="action" value="category_delete" class="danger" onclick="return confirm('¿Eliminar todas las entradas con las categorías seleccionadas? Esta acción no se puede deshacer.');">Eliminar por categoría</button>
      <button type="submit" name="action" value="category_export_xml">Exportar coincidencias a XML</button>
    </div>

    <p class="hint">Las categorías se comparan contra el nodo <category> de cada <game> o <machine>. Se usa coincidencia exacta (ignorando mayúsculas, espacios y dos puntos finales).</p>
  </form>
</div>
