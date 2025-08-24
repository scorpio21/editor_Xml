<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
?>
<div class="top-actions">
    <button type="button" class="secondary" onclick="openHelpModal()">Ayuda</button>
    <button type="button" onclick="openCreateModal()">Crear XML</button>
  </div>
<?php if (isset($_SESSION['xml_uploaded']) && file_exists($xmlFile)): ?>
    <div class="current-file">
        <h3>Archivo actual: current.xml</h3>
        <?php $mod = @filemtime($xmlFile); ?>
        <div class="file-meta" aria-label="Información del archivo actual">
            <span class="badge">Cargado</span>
            <span>Última modificación: <?= $mod ? htmlspecialchars(date('Y-m-d H:i', (int)$mod)) : 'N/D' ?></span>
        </div>
        <div class="meta" aria-label="Fecha y hora actuales">
            Ahora: <span data-clock data-initial="<?= htmlspecialchars(date('c')) ?>"><?= htmlspecialchars(date('Y-m-d H:i:s')) ?></span>
            <label class="sr-only" for="timezone-select-loaded">Zona horaria</label>
            <select id="timezone-select-loaded" data-timezone-select>
                <option value="system">Sistema</option>
                <option value="UTC">UTC</option>
                <option value="Europe/Madrid">Europe/Madrid</option>
                <option value="America/New_York">America/New_York</option>
                <option value="America/Los_Angeles">America/Los_Angeles</option>
                <option value="Asia/Tokyo">Asia/Tokyo</option>
            </select>
        </div>
        <div class="file-actions">
            <button type="button" onclick="openAddModal()">Añadir juego</button>
        </div>
        <form method="post" class="inline-form">
            <?= campoCSRF() ?>
            <button type="submit" name="action" value="download_xml" class="secondary">Descargar XML</button>
        </form>
        <form method="post">
            <input type="hidden" name="action" value="remove_xml">
            <?= campoCSRF() ?>
            <button type="submit" class="remove-btn">Eliminar archivo actual</button>
        </form>
        <?php if (!empty($_SESSION['pending_save'])): ?>
        <form method="post" class="inline-form">
            <?= campoCSRF() ?>
            <button type="submit" name="action" value="compact_xml" class="secondary">Guardar / Compactar XML</button>
        </form>
        <?php else: ?>
        <p class="hint"><small>No hay cambios pendientes.</small></p>
        <?php endif; ?>
        <?php if (file_exists($xmlFile . '.bak')): ?>
        <form method="post" class="inline-form">
            <?= campoCSRF() ?>
            <button type="submit" name="action" value="restore_backup" class="secondary">Restaurar desde .bak</button>
        </form>
        <p class="derecha">By scorpio</p>
      <?php endif; ?>
    </div>
<?php else: ?>
    <div class="upload-form">
        <h3>Subir fichero XML o DAT</h3>
        <form method="post" enctype="multipart/form-data">
            <?= campoCSRF() ?>
            <div class="file-row">
                <input type="file" name="xmlFile" accept=".xml,.dat" required>
                <button type="submit">Cargar XML/DAT</button>
            </div>
        </form>
        <div class="or">
            <span>o</span>
        </div>
        <div class="create-new">
            <button type="button" onclick="openCreateModal()">Crear un XML nuevo</button>
        </div>
        <div class="meta" aria-label="Fecha y hora actuales">
            Ahora: <span data-clock data-initial="<?= htmlspecialchars(date('c')) ?>"><?= htmlspecialchars(date('Y-m-d H:i:s')) ?></span>
            <label class="sr-only" for="timezone-select-empty">Zona horaria</label>
            <select id="timezone-select-empty" data-timezone-select>
                <option value="system">Sistema</option>
                <option value="UTC">UTC</option>
                <option value="Europe/Madrid">Europe/Madrid</option>
                <option value="America/New_York">America/New_York</option>
                <option value="America/Los_Angeles">America/Los_Angeles</option>
                <option value="Asia/Tokyo">Asia/Tokyo</option>
            </select>
        </div>
        <p class="hint"><small>Sube un archivo XML o DAT con la estructura de juegos para comenzar.</small></p>
    </div>
<?php endif; ?>

