<?php
/**
 * Script para comprimir archivos XML grandes antes de subirlos
 * Uso: compress_xml.php?file=ruta/al/archivo.xml
 */

// Configuración
$maxSizeKB = 15000; // 15MB máximo para archivos comprimidos

if (!isset($_GET['file'])) {
    die('Error: No se especificó archivo. Usa: compress_xml.php?file=ruta/al/archivo.xml');
}

$filePath = $_GET['file'];
if (!file_exists($filePath)) {
    die('Error: El archivo no existe: ' . htmlspecialchars($filePath));
}

$fileSize = filesize($filePath);
$fileSizeKB = $fileSize / 1024;

echo "<h2>Compresión de XML</h2>";
echo "<p>Archivo original: <strong>" . htmlspecialchars(basename($filePath)) . "</strong></p>";
echo "<p>Tamaño original: <strong>" . number_format($fileSizeKB, 2) . " KB</strong></p>";

if ($fileSizeKB <= $maxSizeKB) {
    echo "<p style='color: green;'>✅ El archivo ya está por debajo del límite de " . number_format($maxSizeKB/1024, 1) . "MB</p>";
    echo "<p>Puedes subirlo directamente.</p>";
    exit;
}

// Intentar comprimir con gzencode
$xmlContent = file_get_contents($filePath);
if ($xmlContent === false) {
    die('Error: No se pudo leer el archivo.');
}

$compressed = gzencode($xmlContent, 9);
if ($compressed === false) {
    die('Error: No se pudo comprimir el archivo.');
}

$compressedSizeKB = strlen($compressed) / 1024;
$ratio = ($fileSizeKB - $compressedSizeKB) / $fileSizeKB * 100;

echo "<p>Tamaño comprimido: <strong>" . number_format($compressedSizeKB, 2) . " KB</strong></p>";
echo "<p>Reducción: <strong>" . number_format($ratio, 1) . "%</strong></p>";

if ($compressedSizeKB > $maxSizeKB) {
    echo "<p style='color: orange;'>⚠️ El archivo comprimido sigue siendo demasiado grande.</p>";
    echo "<p>Considera dividir el XML en partes más pequeñas.</p>";
    exit;
}

// Guardar archivo comprimido
$compressedPath = $filePath . '.gz';
if (file_put_contents($compressedPath, $compressed)) {
    echo "<p style='color: green;'>✅ Archivo comprimido guardado: <strong>" . htmlspecialchars(basename($compressedPath)) . "</strong></p>";
    echo "<p>Ahora puedes subir este archivo .gz y el sistema lo descomprimirá automáticamente.</p>";
    
    // Enlace para descargar
    echo "<p><a href='" . htmlspecialchars(basename($compressedPath)) . "' download>📥 Descargar archivo comprimido</a></p>";
} else {
    echo "<p style='color: red;'>❌ Error al guardar el archivo comprimido.</p>";
}
?>
