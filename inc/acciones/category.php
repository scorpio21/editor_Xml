<?php
declare(strict_types=1);

// Acciones por categoría: category_count, category_delete, category_export_xml

if (!function_exists('requireValidCsrf')) {
    require_once __DIR__ . '/../csrf-helper.php';
}

// Declarar la acción lo antes posible para uso por bloques superiores
$action = $_POST['action'] ?? '';

// --- set_header_description ---
if ($action === 'set_header_description') {
    requireValidCsrf();
    if (!isset($xml) || !($xml instanceof SimpleXMLElement)) {
        $_SESSION['error'] = 'No hay XML cargado.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $desc = isset($_POST['new_description']) ? (string)$_POST['new_description'] : '';
    $desc = trim($desc);
    // Preparar DOM
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->resolveExternals = false; // Seguridad XXE
    $dom->substituteEntities = false;
    $dom->validateOnParse = false;
    $dom->loadXML($xml->asXML(), LIBXML_NONET);
    $xp = new DOMXPath($dom);
    // Localizar/crear <header>
    $headerNode = $xp->query('/datafile/header')->item(0);
    if (!($headerNode instanceof DOMElement)) {
        $root = $xp->query('/datafile')->item(0);
        if ($root instanceof DOMElement) {
            $headerNode = $dom->createElement('header');
            if ($root->firstChild) { $root->insertBefore($headerNode, $root->firstChild); }
            else { $root->appendChild($headerNode); }
        }
    }
    if ($headerNode instanceof DOMElement) {
        // Localizar/crear <description>
        $descNode = $xp->query('./description', $headerNode)->item(0);
        if (!($descNode instanceof DOMElement)) {
            $descNode = $dom->createElement('description');
            $headerNode->appendChild($descNode);
        }
        // Establecer texto (como nodo de texto)
        while ($descNode->firstChild) { $descNode->removeChild($descNode->firstChild); }
        $descNode->appendChild($dom->createTextNode($desc));
    }
    // Guardar con backup
    if (!isset($xmlFile)) { $xmlFile = __DIR__ . '/../..' . '/uploads/current.xml'; }
    EditorXml::crearBackup($xmlFile);
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->normalizeDocument();
    EditorXml::limpiarEspaciosEnBlancoDom($dom);
    if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
        $_SESSION['error'] = 'No se pudo guardar la descripción en el XML. Se revirtió al respaldo.';
    } else {
        $_SESSION['message'] = 'Descripción del fichero actualizada correctamente.';
        $_SESSION['pending_save'] = true;
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Sanear componentes de nombre de archivo para Windows sin usar regex complejas
function sanearNombreWindows(string $s): string {
    $s = trim($s);
    $s = str_replace(["\\","/",":","*","?","\"","<",">","|"], ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    return trim($s);
}
require_once __DIR__ . '/../EditorXml.php';

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

        // Si hay nombre original de subida y no es genérico, usarlo tal cual (sin añadir sufijos)
        $orig = (string)($_SESSION['original_filename'] ?? '');
        $filename = '';
        if ($orig !== '') {
            $origSanitized = sanearNombreWindows((string)$orig);
            $origUpper = strtoupper($origSanitized);
            if ($origSanitized !== '' && $origUpper !== 'DATAFILE.XML' && $origUpper !== 'CURRENT.XML') {
                $filename = $origSanitized;
            }
        }
        if ($filename === '') {
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
            $base = sanearNombreWindows((string)$base);
            $dateStr = date('Y-m-d H-i-s');
            $filename = sprintf('%s (%d) (%s).xml', ($base !== '' ? $base : 'datafile'), $count, $dateStr);
        }
        // Preparar contenido antes de enviar cabeceras y limpiar buffers
        // (evita "cosas raras" al principio del archivo debidas a salidas previas)
        // Construiremos el header del XML exportado más abajo y luego generamos el contenido final.
        // Preparar <header> en el XML exportado
        // Determinar nombre base de plataforma para header/name (sin sufijos de datfile/discs ni (n)(fecha))
        $platformBase = '';
        // 1) Preferir header/name del XML si existe y no es 'datafile'
        if (isset($xml) && $xml instanceof SimpleXMLElement) {
            $hdr = $xml->xpath('/datafile/header/name');
            if (is_array($hdr) && isset($hdr[0])) {
                $candidate = trim((string)$hdr[0]);
                if (strtoupper($candidate) !== 'DATAFILE') { $platformBase = $candidate; }
            }
        }
        // 2) Si aún vacío, derivar de original_filename (sin ext)
        if ($platformBase === '' && $orig !== '') {
            $origNoExt = preg_replace('/\.[^.]+$/', '', $orig) ?? '';
            $platformBase = trim((string)$origNoExt);
        }
        // 3) Si aún vacío, derivar del basename del archivo XML
        if ($platformBase === '' && isset($xmlFile) && is_string($xmlFile) && $xmlFile !== '') {
            $bn = basename($xmlFile);
            $platformBase = preg_replace('/\.[^.]+$/', '', $bn) ?? '';
        }
        // Limpiar sufijos de tipo " - Datfile ..." o " - Discs ..." y/o " (n) (fecha)"
        if ($platformBase !== '') {
            // Quitar (n) (fecha)
            if (preg_match('/^(.*)\s\(\d+\)\s\([^)]*\)$/', $platformBase, $m)) {
                $platformBase = trim((string)$m[1]);
            }
            // Quitar " - Datfile ..." o " - Discs ..."
            if (preg_match('/^(.*?)\s-\s(?:Datfile|Discs)\b.*$/i', $platformBase, $m2)) {
                $platformBase = trim((string)$m2[1]);
            }
        }
        $platformBase = trim($platformBase);
        $dateNow = date('Y-m-d H-i-s');
        $descText = sprintf('%s - Datfile (%d) (%s)', ($platformBase !== '' ? $platformBase : 'datafile'), $count, $dateNow);
        // Alinear nombre de archivo con la descripción y conservar extensión original (.dat/.xml)
        $ext = 'xml';
        if (!empty($orig)) {
            $e = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (in_array($e, ['xml','dat'], true)) { $ext = $e; }
        } elseif (isset($xmlFile) && is_string($xmlFile)) {
            $e = strtolower(pathinfo($xmlFile, PATHINFO_EXTENSION));
            if (in_array($e, ['xml','dat'], true)) { $ext = $e; }
        }
        $finalBase = ($platformBase !== '' ? $platformBase : 'datafile');
        $filename = sprintf('%s - Datfile (%d) (%s).%s', $finalBase, $count, $dateNow, $ext);

        // Clonar header original si existe, pero sobrescribir name/description
        $srcHeaderNode = null;
        if (isset($xml) && $xml instanceof SimpleXMLElement) {
            $h = $xp->query('/datafile/header');
            if ($h && $h->length > 0) { $srcHeaderNode = $h->item(0); }
        }
        $newHeader = $newDom->createElement('header');
        // Recoger nodos preservados (excluir name/description/version/date para evitar duplicados)
        $preserved = [];
        if ($srcHeaderNode instanceof DOMElement) {
            foreach ($srcHeaderNode->childNodes as $child) {
                if (!($child instanceof DOMElement)) { continue; }
                $tag = strtolower($child->tagName);
                if (in_array($tag, ['name','description','version','date'], true)) { continue; }
                $preserved[] = $newDom->importNode($child, true);
            }
        }
        // Establecer orden: name, description, version, date, luego preservados
        $newHeader->appendChild($newDom->createElement('name', $platformBase !== '' ? $platformBase : 'datafile'));
        $newHeader->appendChild($newDom->createElement('description', $descText));
        $newHeader->appendChild($newDom->createElement('version', $dateNow));
        $newHeader->appendChild($newDom->createElement('date', $dateNow));
       
        foreach ($preserved as $node) { $newHeader->appendChild($node); }
        // Insertar header al inicio
        if ($root->firstChild) {
            $root->insertBefore($newHeader, $root->firstChild);
        } else {
            $root->appendChild($newHeader);
        }

        $xmlContent = $newDom->saveXML();
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
        } else {
            @ob_clean();
        }
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        if (is_string($xmlContent)) {
            header('Content-Length: ' . (string)strlen($xmlContent));
            echo $xmlContent;
        } else {
            echo $newDom->saveXML();
        }
        exit;
    }
}
