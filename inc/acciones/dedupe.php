<?php
declare(strict_types=1);

// Módulo: acciones de deduplicación por región (conteo, ejecución y exportación CSV)
// Requisitos previos: require de common.php, xml-helpers.php y variables $xmlFile, $xml

if (!isset($_POST['action'])) {
    return; // nada que hacer
}

$action = (string)$_POST['action'];

// === Dedupe por región: conteo previo ===
if ($action === 'dedupe_region_count' && isset($xml) && $xml instanceof SimpleXMLElement) {
    requireValidCsrf();

    $prefer = trim((string)($_POST['prefer_region'] ?? ''));
    $keepEU = isset($_POST['keep_europe']) && (string)$_POST['keep_europe'] === '1';

    if ($prefer === '') {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Debes seleccionar una región a conservar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'Debes seleccionar una región a conservar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    // Seguridad XXE
    $dom->resolveExternals = false;
    $dom->substituteEntities = false;
    $dom->validateOnParse = false;
    $dom->loadXML($xml->asXML(), LIBXML_NONET);
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    // Mapear términos de la región preferida (+Europa opcional)
    $includeTerms = [];
    $excludeTerms = [];
    $regionsPref = [$prefer];
    if ($keepEU && mb_strtoupper($prefer, 'UTF-8') !== 'EUROPA') { $regionsPref[] = 'Europa'; }
    EditorXml::mapearRegionesIdiomas($regionsPref, [], $includeTerms, $excludeTerms);

    // Agrupar por nombre base (eliminando paréntesis)
    $groups = [];
    for ($i = 0; $i < $games->length; $i++) {
        $g = $games->item($i);
        if (!($g instanceof DOMElement)) { continue; }
        $name = (string)$g->getAttribute('name');
        $base = trim((string)preg_replace('/\s*\([^)]*\)\s*/', ' ', $name));
        if ($base === '') { $base = $name; }
        $groups[$base] = $groups[$base] ?? [];
        $groups[$base][] = $g;
    }

    $toRemove = 0;
    foreach ($groups as $base => $items) {
        if (count($items) <= 1) { continue; }
        // Ver si existe al menos una variante de la región preferida
        $hasPreferred = false;
        $isPreferred = [];
        foreach ($items as $idx => $el) {
            $hay = strtoupper((string)$el->getAttribute('name'));
            $d = $xpath->query('./description', $el)->item(0);
            if ($d) { $hay .= ' ' . strtoupper((string)$d->nodeValue); }
            $tokens = EditorXml::tokenizar($hay);
            $pref = EditorXml::anyTermMatch($tokens, $hay, $includeTerms);
            $isPreferred[$idx] = $pref;
            if ($pref) { $hasPreferred = true; }
        }
        if (!$hasPreferred) { continue; }
        // Contar solo las NO preferidas
        foreach ($items as $idx => $el) {
            if (!$isPreferred[$idx]) { $toRemove++; }
        }
    }

    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'duplicates' => $toRemove,
            'message' => $toRemove > 0
                ? ("Se pueden eliminar " . $toRemove . " duplicados. Pulsa 'Eliminar duplicados' para continuar.")
                : 'No se encontraron duplicados para la región seleccionada.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['message'] = ($toRemove > 0)
        ? ('Se pueden eliminar ' . $toRemove . ' duplicados. Pulsa "Eliminar duplicados" para continuar.')
        : 'No se encontraron duplicados para la región seleccionada.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// === Dedupe por región: ejecución ===
if ($action === 'dedupe_region' && isset($xml) && $xml instanceof SimpleXMLElement) {
    requireValidCsrf();

    $prefer = trim((string)($_POST['prefer_region'] ?? ''));
    $keepEU = isset($_POST['keep_europe']) && (string)$_POST['keep_europe'] === '1';

    if ($prefer === '') {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Debes seleccionar una región a conservar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'Debes seleccionar una región a conservar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    // Seguridad XXE
    $dom->resolveExternals = false;
    $dom->substituteEntities = false;
    $dom->validateOnParse = false;
    $dom->loadXML($xml->asXML(), LIBXML_NONET);
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    $includeTerms = [];
    $excludeTerms = [];
    $regionsPref = [$prefer];
    if ($keepEU && mb_strtoupper($prefer, 'UTF-8') !== 'EUROPA') { $regionsPref[] = 'Europa'; }
    EditorXml::mapearRegionesIdiomas($regionsPref, [], $includeTerms, $excludeTerms);

    // Construir grupos por nombre base
    $groups = [];
    for ($i = 0; $i < $games->length; $i++) {
        $g = $games->item($i);
        if (!($g instanceof DOMElement)) { continue; }
        $name = (string)$g->getAttribute('name');
        $base = trim((string)preg_replace('/\s*\([^)]*\)\s*/', ' ', $name));
        if ($base === '') { $base = $name; }
        $groups[$base] = $groups[$base] ?? [];
        $groups[$base][] = $g;
    }

    EditorXml::crearBackup($xmlFile);

    $deleted = 0;
    foreach ($groups as $base => $items) {
        if (count($items) <= 1) { continue; }
        // Marcar preferidos y comprobar si existe alguno
        $hasPreferred = false;
        $isPreferred = [];
        foreach ($items as $idx => $el) {
            $hay = strtoupper((string)$el->getAttribute('name'));
            $d = $xpath->query('./description', $el)->item(0);
            if ($d) { $hay .= ' ' . strtoupper((string)$d->nodeValue); }
            $tokens = EditorXml::tokenizar($hay);
            $pref = EditorXml::anyTermMatch($tokens, $hay, $includeTerms);
            $isPreferred[$idx] = $pref;
            if ($pref) { $hasPreferred = true; }
        }
        if (!$hasPreferred) { continue; }
        // Eliminar solo los NO preferidos
        foreach ($items as $idx => $el) {
            if ($isPreferred[$idx]) { continue; }
            if ($el->parentNode) { $el->parentNode->removeChild($el); $deleted++; }
        }
    }

    // Guardar bonito
    $dom->formatOutput = true;
    $dom->normalizeDocument();
    EditorXml::limpiarEspaciosEnBlancoDom($dom);
    if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'No se pudo guardar el XML tras eliminar duplicados. Se revirtió al respaldo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'No se pudo guardar el XML tras eliminar duplicados. Se revirtió al respaldo.';
    } else {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'deleted' => $deleted,
                'pending_save' => true,
                'message' => 'Eliminación de duplicados completada. Registros eliminados: ' . $deleted . '.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['message'] = 'Eliminación de duplicados completada. Registros eliminados: ' . $deleted . '.';
        $_SESSION['pending_save'] = true;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// === Dedupe por región: exportar CSV de duplicados ===
if ($action === 'dedupe_region_export_csv' && isset($xml) && $xml instanceof SimpleXMLElement) {
    requireValidCsrf();

    $prefer = trim((string)($_POST['prefer_region'] ?? ''));
    $keepEU = isset($_POST['keep_europe']) && (string)$_POST['keep_europe'] === '1';

    if ($prefer === '') {
        $_SESSION['error'] = 'Debes seleccionar una región a conservar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    // Seguridad XXE
    $dom->resolveExternals = false;
    $dom->substituteEntities = false;
    $dom->validateOnParse = false;
    $dom->loadXML($xml->asXML(), LIBXML_NONET);
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    // Términos para preferencia de región (+Europa opcional)
    $includeTerms = [];
    $excludeTerms = [];
    $regionsPref = [$prefer];
    if ($keepEU && mb_strtoupper($prefer, 'UTF-8') !== 'EUROPA') { $regionsPref[] = 'Europa'; }
    EditorXml::mapearRegionesIdiomas($regionsPref, [], $includeTerms, $excludeTerms);

    // Agrupar por nombre base y determinar duplicados
    $groups = [];
    for ($i = 0; $i < $games->length; $i++) {
        $g = $games->item($i);
        if (!($g instanceof DOMElement)) { continue; }
        $name = (string)$g->getAttribute('name');
        $base = trim((string)preg_replace('/\s*\([^)]*\)\s*/', ' ', $name));
        if ($base === '') { $base = $name; }
        $groups[$base] = $groups[$base] ?? [];
        $groups[$base][] = $g;
    }

    $toExport = [];
    foreach ($groups as $base => $items) {
        if (count($items) <= 1) { continue; }
        // Marcar preferidos y comprobar si existe alguno
        $hasPreferred = false;
        $isPreferred = [];
        foreach ($items as $idx => $el) {
            $hay = strtoupper((string)$el->getAttribute('name'));
            $d = $xpath->query('./description', $el)->item(0);
            if ($d) { $hay .= ' ' . strtoupper((string)$d->nodeValue); }
            $tokens = EditorXml::tokenizar($hay);
            $pref = EditorXml::anyTermMatch($tokens, $hay, $includeTerms);
            $isPreferred[$idx] = $pref;
            if ($pref) { $hasPreferred = true; }
        }
        if (!$hasPreferred) { continue; }
        // Exportar solo los NO preferidos
        foreach ($items as $idx => $el) {
            if ($isPreferred[$idx]) { continue; }
            $toExport[] = [ 'nombre' => (string)$el->getAttribute('name') ];
        }
    }

    // Preparar descarga CSV
    $filename = 'duplicados_' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $prefer) . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // BOM para compatibilidad con Excel (UTF-8)
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    // Encabezado
    fputcsv($out, ['nombre']);
    foreach ($toExport as $row) {
        fputcsv($out, [$row['nombre']]);
    }
    fclose($out);
    exit;
}
