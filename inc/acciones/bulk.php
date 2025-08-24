<?php
declare(strict_types=1);

// Módulo: acciones de eliminación masiva (conteo y ejecución)
// Requisitos previos: require de common.php, xml-helpers.php y variables $xmlFile, $xml

if (!isset($_POST['action'])) {
    return; // nada que hacer
}

$action = (string)$_POST['action'];

// --- bulk_count ---
if ($action === 'bulk_count' && isset($xml) && $xml instanceof SimpleXMLElement) {
    requireValidCsrf();

    $include = isset($_POST['include']) ? trim((string)$_POST['include']) : '';
    $exclude = isset($_POST['exclude']) ? trim((string)$_POST['exclude']) : '';
    $includeRegions = isset($_POST['include_regions']) && is_array($_POST['include_regions']) ? $_POST['include_regions'] : [];
    $excludeLangs = isset($_POST['exclude_langs']) && is_array($_POST['exclude_langs']) ? $_POST['exclude_langs'] : [];

    // Construir términos combinados
    $includeTerms = [];
    if ($include !== '') {
        $includeTerms = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', strtoupper($include)))));
    }
    $excludeTerms = $exclude !== '' ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', strtoupper($exclude))))) : [];
    mapearRegionesIdiomas($includeRegions, $excludeLangs, $includeTerms, $excludeTerms);

    if (count($includeTerms) === 0) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Debes seleccionar al menos una región o indicar algún término a incluir.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'Debes seleccionar al menos una región o indicar algún término a incluir.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');
    $machines = $xpath->query('/datafile/machine');

    $matches = 0;
    // Contar juegos
    for ($i = 0; $i < $games->length; $i++) {
        $g = $games->item($i);
        if (!($g instanceof DOMElement)) { continue; }
        $name = (string)($g->getAttribute('name') ?? '');
        $desc = '';
        $cat = '';
        $dNode = $xpath->query('./description', $g)->item(0);
        if ($dNode) { $desc = (string)$dNode->nodeValue; }
        $cNode = $xpath->query('./category', $g)->item(0);
        if ($cNode) { $cat = (string)$cNode->nodeValue; }
        $haystackUpper = strtoupper($name.' '.$desc.' '.$cat);
        $tokens = tokenizar($haystackUpper);

        $matchInclude = anyTermMatch($tokens, $haystackUpper, $includeTerms);
        if (!$matchInclude) { continue; }
        $matchExclude = anyTermMatch($tokens, $haystackUpper, $excludeTerms);
        if ($matchExclude) { continue; }
        $matches++;
    }
    // Contar máquinas
    for ($i = 0; $i < $machines->length; $i++) {
        $m = $machines->item($i);
        if (!($m instanceof DOMElement)) { continue; }
        $name = (string)($m->getAttribute('name') ?? '');
        $desc = '';
        $year = '';
        $manu = '';
        $dNode = $xpath->query('./description', $m)->item(0);
        if ($dNode) { $desc = (string)$dNode->nodeValue; }
        $yNode = $xpath->query('./year', $m)->item(0);
        if ($yNode) { $year = (string)$yNode->nodeValue; }
        $manNode = $xpath->query('./manufacturer', $m)->item(0);
        if ($manNode) { $manu = (string)$manNode->nodeValue; }
        $haystackUpper = strtoupper($name.' '.$desc.' '.$year.' '.$manu);
        $tokens = tokenizar($haystackUpper);

        $matchInclude = anyTermMatch($tokens, $haystackUpper, $includeTerms);
        if (!$matchInclude) { continue; }
        $matchExclude = anyTermMatch($tokens, $haystackUpper, $excludeTerms);
        if ($matchExclude) { continue; }
        $matches++;
    }

    $_SESSION['bulk_filters'] = [
        'include' => $include,
        'exclude' => $exclude,
        'include_regions' => $includeRegions,
        'exclude_langs' => $excludeLangs,
    ];

    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'matches' => $matches,
            'message' => "Coincidencias encontradas: {$matches}. (Simulación: no se ha eliminado nada)",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['message'] = "Coincidencias encontradas: {$matches}. (Simulación: no se ha eliminado nada)";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// --- bulk_delete ---
if ($action === 'bulk_delete' && isset($xml) && $xml instanceof SimpleXMLElement) {
    requireValidCsrf();

    require_once __DIR__ . '/../mame-filters.php';

    $include = sanitizarTexto($_POST['include'] ?? '');
    $exclude = sanitizarTexto($_POST['exclude'] ?? '');
    $includeRegions = isset($_POST['include_regions']) && is_array($_POST['include_regions']) ? $_POST['include_regions'] : [];
    $excludeLangs = isset($_POST['exclude_langs']) && is_array($_POST['exclude_langs']) ? $_POST['exclude_langs'] : [];

    // Procesar filtros MAME
    $mameFilters = procesarFiltrosMame();

    // Guardar filtros en sesión
    $_SESSION['bulk_filters'] = [
        'include' => $include,
        'exclude' => $exclude,
        'include_regions' => $includeRegions,
        'exclude_langs' => $excludeLangs
    ] + $mameFilters;

    // Construir términos combinados
    $includeTerms = [];
    if ($include !== '') {
        $includeTerms = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', strtoupper($include)))));
    }
    $excludeTerms = $exclude !== '' ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', strtoupper($exclude))))) : [];

    mapearRegionesIdiomas($includeRegions, $excludeLangs, $includeTerms, $excludeTerms);

    if (count($includeTerms) === 0) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Debes seleccionar al menos una región o indicar algún término a incluir.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'Debes seleccionar al menos una región o indicar algún término a incluir.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');
    $machines = $xpath->query('/datafile/machine');

    crearBackup($xmlFile);

    $deleted = 0;
    $allFilters = $_SESSION['bulk_filters'];

    foreach ($games as $g) {
        $haystack = obtenerTextoParaBusqueda($g, 'game');
        $haystackUpper = strtoupper($haystack);
        $tokens = tokenizar($haystackUpper);
        if (!anyTermMatch($tokens, $haystackUpper, $includeTerms)) { continue; }
        if (anyTermMatch($tokens, $haystackUpper, $excludeTerms)) { continue; }
        $g->parentNode->removeChild($g);
        $deleted++;
    }

    foreach ($machines as $m) {
        $haystack = obtenerTextoParaBusqueda($m, 'machine');
        $haystackUpper = strtoupper($haystack);
        $tokens = tokenizar($haystackUpper);
        if (!anyTermMatch($tokens, $haystackUpper, $includeTerms)) { continue; }
        if (anyTermMatch($tokens, $haystackUpper, $excludeTerms)) { continue; }
        // Aplicar filtros MAME específicos
        if (!aplicarFiltrosMame($m, $allFilters)) { continue; }
        $m->parentNode->removeChild($m);
        $deleted++;
    }

    // Guardar
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->normalizeDocument();
    limpiarEspaciosEnBlancoDom($dom);

    if (!guardarDomConBackup($dom, $xmlFile)) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'No se pudo guardar tras la eliminación masiva. Se revirtió al respaldo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'No se pudo guardar tras la eliminación masiva. Se revirtió al respaldo.';
    } else {
        if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'deleted' => $deleted,
                'pending_save' => true,
                'message' => 'Eliminación masiva completada. Registros eliminados: ' . $deleted . '.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['message'] = 'Eliminación masiva completada. Registros eliminados: ' . $deleted . '.';
        $_SESSION['pending_save'] = true;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
