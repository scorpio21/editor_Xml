<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

function asegurarCarpetaUploads(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function cargarXmlSiDisponible(string $xmlFile): ?SimpleXMLElement {
    if (isset($_SESSION['xml_uploaded']) && file_exists($xmlFile)) {
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            registrarError('xml-helpers.php:cargarXmlSiDisponible', 'Fallo al cargar XML: formato inválido', [ 'xmlFile' => $xmlFile ]);
            $_SESSION['error'] = 'Error al cargar el archivo XML. Formato incorrecto.';
            unset($_SESSION['xml_uploaded']);
            return null;
        }
        registrarInfo('xml-helpers.php:cargarXmlSiDisponible', 'XML cargado correctamente', [ 'xmlFile' => $xmlFile ]);
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
        registrarInfo('xml-helpers.php:guardarDomConBackup', 'Creando copia de seguridad previa', [ 'xmlFile' => $xmlFile ]);
        @copy($xmlFile, $backup);
    }
    registrarInfo('xml-helpers.php:guardarDomConBackup', 'Guardando DOM en disco', [ 'xmlFile' => $xmlFile ]);
    $saved = @$dom->save($xmlFile);
    if ($saved === false) {
        registrarError('xml-helpers.php:guardarDomConBackup', 'Fallo al guardar XML. Revirtiendo al backup', [ 'xmlFile' => $xmlFile ]);
        if (file_exists($backup)) {
            @copy($backup, $xmlFile);
        }
        return false;
    }
    registrarInfo('xml-helpers.php:guardarDomConBackup', 'XML guardado correctamente', [ 'xmlFile' => $xmlFile ]);
    return true;
}

