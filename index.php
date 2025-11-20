<?php
declare(strict_types=1);
session_start();

$xmlFile = __DIR__ . '/uploads/current.xml';

require_once __DIR__ . '/inc/xml-helpers.php';
require_once __DIR__ . '/inc/i18n.php';
asegurarCarpetaUploads(__DIR__ . '/uploads');

// Inicializar i18n (idioma desde ?lang=es|en y sesión)
i18n_init();

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
<html lang="<?= htmlspecialchars(lang()) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars(t('app.title')) ?></title>
    <link rel="stylesheet" href="css/editor-xml.css">
    <?php if ($uiTabs): ?>
      <link rel="stylesheet" href="css/tabs.css">
      <link rel="stylesheet" href="css/search-external.css">
    <?php endif; ?>
</head>
<body>

<div class="app-header">
    <h2><?= htmlspecialchars(t('header.title')) ?></h2>
    <div class="app-meta" aria-label="Fecha y hora actuales">
        <span data-clock data-format="DD/MM/YYYY HH:mm" data-initial="<?= htmlspecialchars(date('c')) ?>">
            <?= htmlspecialchars(date('d/m/Y H:i')) ?>
        </span>
    </div>
    <div class="lang-select" id="lang-dropdown" data-current="<?= htmlspecialchars(lang()) ?>">
        <button type="button" id="lang-dd-trigger" class="ls-trigger" aria-haspopup="listbox" aria-expanded="false" aria-label="<?= htmlspecialchars(t('lang.label')) ?>">
            <img src="img/flags/<?= lang()==='en' ? 'gb' : 'es' ?>.svg" alt="" width="20" height="14" aria-hidden="true">
            <span class="ls-text"><?= htmlspecialchars(lang()==='en' ? t('lang.en') : t('lang.es')) ?></span>
            <span class="ls-caret" aria-hidden="true">▾</span>
        </button>
        <ul class="ls-panel" id="lang-dd-panel" role="listbox" aria-label="<?= htmlspecialchars(t('lang.label')) ?>">
            <li role="option" tabindex="0" data-lang="es" aria-selected="<?= lang()==='es' ? 'true' : 'false' ?>">
                <img src="img/flags/es.svg" alt="" width="20" height="14" aria-hidden="true">
                <span><?= htmlspecialchars(t('lang.es')) ?></span>
            </li>
            <li role="option" tabindex="0" data-lang="en" aria-selected="<?= lang()==='en' ? 'true' : 'false' ?>">
                <img src="img/flags/gb.svg" alt="" width="20" height="14" aria-hidden="true">
                <span><?= htmlspecialchars(t('lang.en')) ?></span>
            </li>
        </ul>
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
    <div role="tablist" aria-label="Secciones del editor" aria-orientation="horizontal">
      <button role="tab" id="tab-btn-1" aria-selected="true" aria-controls="tab-panel-1">
        <img class="tab-ico" src="img/ico-home.svg" alt="" aria-hidden="true"><span class="tab-text"><?= htmlspecialchars(t('tabs.welcome')) ?></span>
      </button>
      <button role="tab" id="tab-btn-2" aria-selected="false" aria-controls="tab-panel-2">
        <img class="tab-ico" src="img/ico-upload.svg" alt="" aria-hidden="true"><span class="tab-text"><?= htmlspecialchars(t('tabs.upload_search')) ?></span>
      </button>
      <?php if (!$isMame): ?>
      <button role="tab" id="tab-btn-3" aria-selected="false" aria-controls="tab-panel-3">
        <img class="tab-ico" src="img/ico-bulk.svg" alt="" aria-hidden="true"><span class="tab-text"><?= htmlspecialchars(t('tabs.bulk_delete')) ?></span>
      </button>
      <?php endif; ?>
      <?php if ($isMame): ?>
      <button role="tab" id="tab-btn-4" aria-selected="false" aria-controls="tab-panel-4">
        <img class="tab-ico" src="img/ico-mame.svg" alt="" aria-hidden="true"><span class="tab-text"><?= htmlspecialchars(t('tabs.mame_search')) ?></span>
      </button>
      <?php endif; ?>
      <button role="tab" id="tab-btn-5" aria-selected="false" aria-controls="tab-panel-5">
        <img class="tab-ico" src="img/ico-dedupe.svg" alt="" aria-hidden="true"><span class="tab-text"><?= htmlspecialchars(t('tabs.dedupe_region')) ?></span>
      </button>
      <button role="tab" id="tab-btn-6" aria-selected="false" aria-controls="tab-panel-6">
        <img class="tab-ico" src="img/ico-home.svg" alt="" aria-hidden="true"><span class="tab-text"><?= htmlspecialchars(t('tabs.search_external')) ?></span>
      </button>
      <button role="tab" id="tab-btn-7" aria-selected="false" aria-controls="tab-panel-7">
        <img class="tab-ico" src="img/ico-bulk.svg" alt="" aria-hidden="true"><span class="tab-text">Categorías</span>
      </button>
      <button role="tab" id="tab-btn-8" aria-selected="false" aria-controls="tab-panel-8">
        <img class="tab-ico" src="img/ico-bulk.svg" alt="" aria-hidden="true"><span class="tab-text">Regiones</span>
      </button>
    </div>

    <section role="tabpanel" id="tab-panel-1" aria-labelledby="tab-btn-1">
      <h2><?= htmlspecialchars(t('welcome.h2')) ?></h2>
      <p><?= htmlspecialchars(t('welcome.p1')) ?></p>
      <p><?= htmlspecialchars(t('welcome.p2')) ?></p>
      <p>
        <button type="button" class="secondary" onclick="openHelpModal()"><?= htmlspecialchars(t('welcome.help_btn')) ?></button>
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
        <p class="hint"><?= htmlspecialchars(t('hint.load_first')) ?></p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($isMame): ?>
    <section role="tabpanel" id="tab-panel-4" aria-labelledby="tab-btn-4" hidden>
      <h3><?= htmlspecialchars(t('mame.h3')) ?></h3>
      <p class="hint"><?= htmlspecialchars(t('mame.hint')) ?></p>
      <form method="get" class="search-form">
        <label for="q_mame"><?= htmlspecialchars(t('mame.search_label')) ?></label>
        <input id="q_mame" name="q" type="text" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="<?= htmlspecialchars(t('mame.input_placeholder')) ?>">
        <div class="search-options">
          <label><input type="checkbox" name="q_in_roms" value="1" <?= !empty($_GET['q_in_roms']) && $_GET['q_in_roms'] === '1' ? 'checked' : '' ?>> <?= htmlspecialchars(t('mame.chk_roms')) ?></label>
          <label><input type="checkbox" name="q_in_hashes" value="1" <?= !empty($_GET['q_in_hashes']) && $_GET['q_in_hashes'] === '1' ? 'checked' : '' ?>> <?= htmlspecialchars(t('mame.chk_hashes')) ?></label>
        </div>
        <button type="submit"><?= htmlspecialchars(t('mame.submit')) ?></button>
      </form>
      <p class="hint"><?= htmlspecialchars(t('mame.results_hint')) ?></p>
    </section>
    <?php endif; ?>

    <section role="tabpanel" id="tab-panel-5" aria-labelledby="tab-btn-5" hidden>
      <h3><?= htmlspecialchars(t('tabs.dedupe_region')) ?></h3>
      <?php if ($xml): ?>
        <?php include __DIR__ . '/partials/sections/dedupe-region.php'; ?>
      <?php else: ?>
        <p class="hint"><?= htmlspecialchars(t('hint.load_first_tool')) ?></p>
      <?php endif; ?>
    </section>

    <section role="tabpanel" id="tab-panel-6" aria-labelledby="tab-btn-6" hidden>
      <h3><?= htmlspecialchars(t('search_external.h3')) ?></h3>
      <?php include __DIR__ . '/partials/sections/search-external.php'; ?>
    </section>

    <section role="tabpanel" id="tab-panel-7" aria-labelledby="tab-btn-7" hidden>
      <h3>Categorías</h3>
      <?php if ($xml): ?>
        <?php include __DIR__ . '/partials/sections/category-ops.php'; ?>
      <?php else: ?>
        <p class="hint">Primero carga un XML para usar esta herramienta.</p>
      <?php endif; ?>
    </section>

    <section role="tabpanel" id="tab-panel-8" aria-labelledby="tab-btn-8" hidden>
      <h3>Regiones</h3>
      <?php if ($xml): ?>
        <?php include __DIR__ . '/partials/sections/region-export.php'; ?>
      <?php else: ?>
        <p class="hint">Primero carga un XML para usar esta herramienta.</p>
      <?php endif; ?>
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
<script src="js/lang-selector.js"></script>

</body>
</html>