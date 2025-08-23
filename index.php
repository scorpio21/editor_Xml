<?php
declare(strict_types=1);
session_start();

$xmlFile = __DIR__ . '/uploads/current.xml';

require_once __DIR__ . '/inc/xml-helpers.php';
asegurarCarpetaUploads(__DIR__ . '/uploads');

// Procesar acciones (pueden redirigir y terminar la petición)
require_once __DIR__ . '/inc/acciones.php';

// Cargar XML para render
$xml = cargarXmlSiDisponible($xmlFile);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editor XML Juegos</title>
    <link rel="stylesheet" href="css/editor-xml.css">
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

<?php include __DIR__ . '/partials/header-file.php'; ?>

<?php if ($xml): ?>
    <?php include __DIR__ . '/partials/bulk-delete.php'; ?>

    <?php include __DIR__ . '/partials/games-list.php'; ?>

<?php endif; ?>

<?php include __DIR__ . '/partials/modal-edit.php'; ?>

<?php include __DIR__ . '/partials/modal-help.php'; ?>

<?php include __DIR__ . '/partials/modal-create.php'; ?>

<?php include __DIR__ . '/partials/modal-add-game.php'; ?>

<script src="js/hashes.js"></script>
<script src="js/editor-xml.js"></script>

</body>
</html>