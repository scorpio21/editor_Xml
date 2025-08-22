<?php
declare(strict_types=1);

function asegurarCarpetaUploads(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function cargarXmlSiDisponible(string $xmlFile): ?SimpleXMLElement {
    if (isset($_SESSION['xml_uploaded']) && file_exists($xmlFile)) {
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            $_SESSION['error'] = 'Error al cargar el archivo XML. Formato incorrecto.';
            unset($_SESSION['xml_uploaded']);
            return null;
        }
        return $xml;
    }
    return null;
}

/**
 * Elimina nodos de texto que contengan solo espacios/saltos de línea
 * para evitar líneas en blanco entre elementos.
 */
function limpiarEspaciosEnBlancoDom(DOMDocument $dom): void {
    $xp = new DOMXPath($dom);
    // Selecciona todos los nodos de texto cuyo contenido normalizado sea vacío
    $nodes = $xp->query('//text()[normalize-space(.) = ""]');
    if (!$nodes) { return; }
    // Iterar de atrás hacia adelante para evitar invalidar la NodeList en vivo
    for ($i = $nodes->length - 1; $i >= 0; $i--) {
        $n = $nodes->item($i);
        if ($n && $n->parentNode) {
            $n->parentNode->removeChild($n);
        }
    }
}

function crearBackup(string $xmlFile): void {
    if (file_exists($xmlFile)) {
        @copy($xmlFile, $xmlFile . '.bak');
    }
}

function guardarDomConBackup(DOMDocument $dom, string $xmlFile): bool {
    $backup = $xmlFile . '.bak';
    if (file_exists($xmlFile)) {
        @copy($xmlFile, $backup);
    }
    $saved = @$dom->save($xmlFile);
    if ($saved === false) {
        if (file_exists($backup)) {
            @copy($backup, $xmlFile);
        }
        return false;
    }
    return true;
}
