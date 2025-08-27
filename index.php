<?php
declare(strict_types=1);
session_start();

$xmlFile = __DIR__ . '/uploads/current.xml';

require_once __DIR__ . '/inc/xml-helpers.php';
asegurarCarpetaUploads(__DIR__ . '/uploads');

// Procesar acciones (pueden redirigir y terminar la petición)
require_once __DIR__ . '/inc/router-acciones.php';

// Cargar XML para render
$xml = cargarXmlSiDisponible($xmlFile);
// Modo UI por pestañas por defecto. Para ver la UI clásica, usar ?ui=classic
$uiTabs = !isset($_GET['ui']) || $_GET['ui'] !== 'classic';
// Detectar si el XML cargado parece ser de tipo MAME (presencia de <machine>)
$isMame = false;
if ($xml) {
    $machines = $xml->xpath('/datafile/machine');
    $isMame = is_array($machines) && count($machines) > 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor XML Juegos</title>
    <link rel="stylesheet" href="css/editor-xml.css">
    <?php if ($uiTabs): ?>
      <link rel="stylesheet" href="css/tabs.css">
      <link rel="stylesheet" href="css/search-external.css">
    <?php endif; ?>
</head>
<body>

<div class="app-header">
    <h2>Editor de Catálogo de Juegos XML</h2>
    <div class="app-meta" aria-label="Fecha y hora actuales">
        <span data-clock data-format="DD/MM/YYYY HH:mm" data-initial="<?= htmlspecialchars(date('c')) ?>">
            <?= htmlspecialchars(date('d/m/Y H:i')) ?>
        </span>
    </div>
    </div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="error">
        <?= $_SESSION['error'] ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="notification">
        <?= $_SESSION['message'] ?>
        <?php unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?php if ($uiTabs): ?>
  <div class="tabs" id="app-tabs">
    <div role="tablist" aria-label="Secciones del editor">
      <button role="tab" id="tab-btn-1" aria-selected="true" aria-controls="tab-panel-1">
        <img class="tab-ico" src="img/ico-home.svg" alt="" aria-hidden="true"><span class="tab-text">Bienvenida</span>
      </button>
      <button role="tab" id="tab-btn-2" aria-selected="false" aria-controls="tab-panel-2">
        <img class="tab-ico" src="img/ico-upload.svg" alt="" aria-hidden="true"><span class="tab-text">Cargar y buscar</span>
      </button>
      <?php if (!$isMame): ?>
      <button role="tab" id="tab-btn-3" aria-selected="false" aria-controls="tab-panel-3">
        <img class="tab-ico" src="img/ico-bulk.svg" alt="" aria-hidden="true"><span class="tab-text">Eliminación masiva</span>
      </button>
      <?php endif; ?>
      <?php if ($isMame): ?>
      <button role="tab" id="tab-btn-4" aria-selected="false" aria-controls="tab-panel-4">
        <img class="tab-ico" src="img/ico-mame.svg" alt="" aria-hidden="true"><span class="tab-text">MAME (buscar)</span>
      </button>
      <?php endif; ?>
      <button role="tab" id="tab-btn-5" aria-selected="false" aria-controls="tab-panel-5">
        <img class="tab-ico" src="img/ico-dedupe.svg" alt="" aria-hidden="true"><span class="tab-text">Eliminar duplicados</span>
      </button>
      <button role="tab" id="tab-btn-6" aria-selected="false" aria-controls="tab-panel-6">
        <img class="tab-ico" src="img/ico-home.svg" alt="" aria-hidden="true"><span class="tab-text">Buscar juego</span>
      </button>
    </div>

    <section role="tabpanel" id="tab-panel-1" aria-labelledby="tab-btn-1">
      <h2>Bienvenido al editor de XML/DAT</h2>
      <p>Esta herramienta te permite cargar, explorar, editar y mantener tu catálogo XML/DAT de juegos y máquinas.</p>
      <p>Usa las pestañas para navegar entre secciones. Puedes abrir la ayuda en cualquier momento.</p>
      <p>
        <button type="button" class="secondary" onclick="openHelpModal()">Ayuda</button>
      </p>
    </section>

    <section role="tabpanel" id="tab-panel-2" aria-labelledby="tab-btn-2" hidden>
      <?php include __DIR__ . '/partials/header-file.php'; ?>
      <?php if ($xml): ?>
        <?php include __DIR__ . '/partials/games-list.php'; ?>
      <?php endif; ?>
    </section>

    <?php if (!$isMame): ?>
    <section role="tabpanel" id="tab-panel-3" aria-labelledby="tab-btn-3" hidden>
      <?php if ($xml): ?>
        <?php include __DIR__ . '/partials/bulk-delete.php'; ?>
      <?php else: ?>
        <p class="hint">Primero carga un fichero XML/DAT en la pestaña "Cargar y buscar".</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($isMame): ?>
    <section role="tabpanel" id="tab-panel-4" aria-labelledby="tab-btn-4" hidden>
      <h3>Búsqueda en ficheros MAME</h3>
      <p class="hint">Para ficheros MAME, esta sección permite buscar máquinas por nombre, ROM o hash. La eliminación masiva está deshabilitada.</p>
      <form method="get" class="search-form">
        <label for="q_mame">Buscar</label>
        <input id="q_mame" name="q" type="text" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Nombre de máquina, ROM o hash">
        <div class="search-options">
          <label><input type="checkbox" name="q_in_roms" value="1" <?= !empty($_GET['q_in_roms']) && $_GET['q_in_roms'] === '1' ? 'checked' : '' ?>> Buscar en ROMs</label>
          <label><input type="checkbox" name="q_in_hashes" value="1" <?= !empty($_GET['q_in_hashes']) && $_GET['q_in_hashes'] === '1' ? 'checked' : '' ?>> Buscar en hashes (CRC/MD5/SHA1)</label>
        </div>
        <button type="submit">Buscar</button>
      </form>
      <p class="hint">Los resultados se muestran en la lista principal bajo el buscador.</p>
    </section>
    <?php endif; ?>

    <section role="tabpanel" id="tab-panel-5" aria-labelledby="tab-btn-5" hidden>
      <h3>Eliminar duplicados por región</h3>
      <?php if ($xml): ?>
        <?php include __DIR__ . '/partials/sections/dedupe-region.php'; ?>
      <?php else: ?>
        <p class="hint">Primero carga un fichero XML/DAT en la pestaña "Cargar y buscar" para usar esta herramienta.</p>
      <?php endif; ?>
    </section>

    <section role="tabpanel" id="tab-panel-6" aria-labelledby="tab-btn-6" hidden>
      <h3>Buscar juego en webs externas</h3>
      <?php include __DIR__ . '/partials/sections/search-external.php'; ?>
    </section>
  </div>
<?php else: ?>
  <?php include __DIR__ . '/partials/header-file.php'; ?>

  <?php if ($xml): ?>
      <?php include __DIR__ . '/partials/bulk-delete.php'; ?>

      <?php include __DIR__ . '/partials/games-list.php'; ?>

  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/partials/modal-edit.php'; ?>

<?php include __DIR__ . '/partials/modal-help.php'; ?>

<?php include __DIR__ . '/partials/modal-create.php'; ?>

<?php include __DIR__ . '/partials/modal-add-game.php'; ?>

<script src="js/hashes.js"></script>
<script src="js/utils.js"></script>
<script src="js/reloj.js"></script>
<script src="js/multiselect.js"></script>
<?php if ($uiTabs): ?>
<script src="js/tabs.js"></script>
<?php endif; ?>
<script src="js/modales.js"></script>
<script src="js/bulk.js"></script>
<script src="js/dedupe.js"></script>
<script src="js/search-external.js"></script>

</body>
</html>