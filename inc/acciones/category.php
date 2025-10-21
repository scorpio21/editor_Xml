<?php
declare(strict_types=1);

// Acciones por categoría: category_count, category_delete, category_export_xml

if (!function_exists('requireValidCsrf')) {
    require_once __DIR__ . '/../csrf-helper.php';
}
require_once __DIR__ . '/../EditorXml.php';

$action = $_POST['action'] ?? '';

function normalizar(string $s): string {
    // Mayúsculas, recorte y normalización simple de separadores/':'
    $s = strtoupper(trim($s));
    // Colapsar espacios múltiples
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    // Quitar dos puntos finales opcionales ("GAMES:" -> "GAMES")
    $s = rtrim($s, ": \t\r\n");
    return $s;
}

function categoriaCoincide(?string $cat, array $seleccionadas): bool {
    if ($cat === null) { return false; }
    $catN = normalizar($cat);
    foreach ($seleccionadas as $pref) {
        $p = normalizar($pref);
        if ($p === '') { continue; }
        // Coincidencia exacta tras normalización (ignora mayúsculas, espacios y ':' finales)
        if ($catN === $p) { return true; }
    }
    return false;
}

// Sólo procesar si tenemos XML cargado cuando se requiera
if (in_array($action, ['category_count','category_delete','category_export_xml'], true)) {
    requireValidCsrf();

    // Validar cats
    $cats = isset($_POST['cats']) && is_array($_POST['cats']) ? array_values(array_filter($_POST['cats'], 'is_string')) : [];
    if (count($cats) === 0) {
        $_SESSION['error'] = 'Debes seleccionar al menos una categoría.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $_SESSION['category_filters'] = [ 'cats' => $cats ];

    // Cargar XML si no está disponible por algún motivo
    if (!isset($xml) || !($xml instanceof SimpleXMLElement)) {
        $root = __DIR__ . '/..';
        $xmlFileLocal = $root . '/../uploads/current.xml';
        $xml = EditorXml::cargarXmlSiDisponible($xmlFileLocal);
        if (!$xml) {
            $_SESSION['error'] = 'No hay XML cargado.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    // Preparar DOM y XPath
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->resolveExternals = false; // Seguridad XXE
    $dom->substituteEntities = false;
    $dom->validateOnParse = false;
    $dom->loadXML($xml->asXML(), LIBXML_NONET);
    $xp = new DOMXPath($dom);

    if ($action === 'category_count') {
        $total = 0;
        foreach (['game','machine'] as $type) {
            $nodes = $xp->query('/datafile/' . $type);
            if (!$nodes) { continue; }
            foreach ($nodes as $n) {
                if (!($n instanceof DOMElement)) { continue; }
                $cNode = $xp->query('./category', $n)->item(0);
                $cat = $cNode ? (string)$cNode->nodeValue : null;
                if (categoriaCoincide($cat, $cats)) { $total++; }
            }
        }
        $_SESSION['message'] = 'Coincidencias por categoría: ' . $total . '. (Simulación: no se ha eliminado nada)';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'category_delete') {
        if (!isset($xmlFile)) {
            $xmlFile = __DIR__ . '/../..' . '/uploads/current.xml';
        }
        EditorXml::crearBackup($xmlFile);

        $eliminados = 0;
        foreach (['game','machine'] as $type) {
            $nodes = $xp->query('/datafile/' . $type);
            if (!$nodes) { continue; }
            // Recorremos en orden inverso para eliminar sin problemas
            for ($i = $nodes->length - 1; $i >= 0; $i--) {
                $el = $nodes->item($i);
                if (!($el instanceof DOMElement)) { continue; }
                $cNode = $xp->query('./category', $el)->item(0);
                $cat = $cNode ? (string)$cNode->nodeValue : null;
                if (categoriaCoincide($cat, $cats)) {
                    $el->parentNode?->removeChild($el);
                    $eliminados++;
                }
            }
        }
        // Guardar con limpieza
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->normalizeDocument();
        EditorXml::limpiarEspaciosEnBlancoDom($dom);
        if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
            $_SESSION['error'] = 'No se pudo guardar tras eliminar por categoría. Se revirtió al respaldo.';
        } else {
            $_SESSION['message'] = 'Eliminación por categoría completada. Registros eliminados: ' . $eliminados . '.';
            $_SESSION['pending_save'] = true;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'category_export_xml') {
        // Crear nuevo DOM con raíz datafile
        $newDom = new DOMDocument('1.0', 'UTF-8');
        $newDom->preserveWhiteSpace = false;
        $newDom->formatOutput = true;
        $root = $newDom->createElement('datafile');
        $newDom->appendChild($root);

        $count = 0;
        foreach (['game','machine'] as $type) {
            $nodes = $xp->query('/datafile/' . $type);
            if (!$nodes) { continue; }
            foreach ($nodes as $el) {
                if (!($el instanceof DOMElement)) { continue; }
                $cNode = $xp->query('./category', $el)->item(0);
                $cat = $cNode ? (string)$cNode->nodeValue : null;
                if (categoriaCoincide($cat, $cats)) {
                    $imported = $newDom->importNode($el, true);
                    $root->appendChild($imported);
                    $count++;
                }
            }
        }

        // Si hay nombre original de subida, usarlo tal cual (sin añadir sufijos)
        $orig = (string)($_SESSION['original_filename'] ?? '');
        if ($orig !== '') {
            // Saneado mínimo por seguridad en cabecera; conservar extensión
            $origSanitized = preg_replace('/[\\\/:\*\?\"<>\|]/', ' ', $orig);
            $origSanitized = trim((string)$origSanitized);
            $filename = ($origSanitized !== '' ? $origSanitized : 'datafile.xml');
        } else {
            // Construir nombre: preferir header/name, si es 'datafile' usar basename
            $base = '';
            if (isset($xml) && $xml instanceof SimpleXMLElement) {
                $hdr = $xml->xpath('/datafile/header/name');
                if (is_array($hdr) && isset($hdr[0])) {
                    $base = trim((string)$hdr[0]);
                    if (strtoupper($base) === 'DATAFILE') { $base = ''; }
                }
            }
            if ($base === '' && isset($xmlFile) && is_string($xmlFile) && $xmlFile !== '') {
                $bn = basename($xmlFile);
                $base = preg_replace('/\.[^.]+$/', '', $bn) ?? '';
            }
            // Sanear base y formar con conteo y fecha
            $base = preg_replace('/[\\\/:\*\?\"<>\|]/', ' ', (string)$base);
            $base = trim((string)$base);
            $dateStr = date('Y-m-d H-i-s');
            $filename = sprintf('%s (%d) (%s).xml', ($base !== '' ? $base : 'datafile'), $count, $dateStr);
        }
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $newDom->saveXML();
        exit;
    }
}
