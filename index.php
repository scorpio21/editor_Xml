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
    <h2>Cabecera del fichero</h2>
    <div class="game">
        <?php if (isset($xml->header)): ?>
            <div class="game-info"><strong>Nombre:</strong> <?= htmlspecialchars((string)($xml->header->name ?? '')) ?: 'N/A' ?></div>
            <div class="game-info"><strong>Descripción:</strong> <?= htmlspecialchars((string)($xml->header->description ?? '')) ?: 'N/A' ?></div>
            <div class="game-info"><strong>Versión:</strong> <?= htmlspecialchars((string)($xml->header->version ?? '')) ?: 'N/A' ?></div>
            <div class="game-info"><strong>Fecha:</strong> <?= htmlspecialchars((string)($xml->header->date ?? '')) ?: 'N/A' ?></div>
            <div class="game-info"><strong>Autor:</strong> <?= htmlspecialchars((string)($xml->header->author ?? '')) ?: 'N/A' ?></div>
            <div class="game-info"><strong>Web:</strong> 
                <?php if (!empty((string)($xml->header->url ?? ''))): ?>
                    <a href="<?= htmlspecialchars((string)($xml->header->url ?? '')) ?>" target="_blank"><?= htmlspecialchars((string)($xml->header->homepage ?? 'Enlace')) ?></a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="game-info">No hay información de cabecera disponible</div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/partials/bulk-delete.php'; ?>

    <?php include __DIR__ . '/partials/games-list.php'; ?>

<?php endif; ?>

<?php include __DIR__ . '/partials/modal-edit.php'; ?>

<?php include __DIR__ . '/partials/modal-help.php'; ?>

<script src="js/editor-xml.js"></script>

</body>
</html>