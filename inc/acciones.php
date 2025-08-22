<?php
declare(strict_types=1);

// Este archivo procesa todas las acciones POST y subidas.
// Debe ejecutarse al inicio del request. Redirige y exit() cuando corresponde.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Guardar/Compactar XML manualmente
if (isset($_POST['action']) && $_POST['action'] === 'compact_xml') {
    if (file_exists($xmlFile)) {
        require_once __DIR__ . '/xml-helpers.php';
        $raw = @file_get_contents($xmlFile);
        if ($raw === false) {
            $_SESSION['error'] = 'No se pudo leer el XML para compactar.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $dom = new DOMDocument();
        // Configuración para limpieza
        $dom->preserveWhiteSpace = false;
        if (@$dom->loadXML($raw) === false) {
            $_SESSION['error'] = 'El XML no es válido y no se pudo compactar.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $dom->formatOutput = true;
        $dom->normalizeDocument();
        limpiarEspaciosEnBlancoDom($dom);
        if (!guardarDomConBackup($dom, $xmlFile)) {
            $_SESSION['error'] = 'No se pudo guardar el XML compactado. Se revirtió al respaldo.';
        } else {
            $_SESSION['message'] = 'XML guardado y compactado correctamente.';
            unset($_SESSION['pending_save']);
            $_SESSION['xml_uploaded'] = true;
        }
    } else {
        $_SESSION['error'] = 'No hay XML cargado para compactar.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Soporte para ejecución directa (AJAX): definir rutas y helpers si no vienen de index.php
if (!isset($xmlFile)) {
    $root = dirname(__DIR__);
    $xmlFile = $root . '/uploads/current.xml';
    require_once $root . '/inc/xml-helpers.php';
    asegurarCarpetaUploads($root . '/uploads');
}

// Restablecer filtros de sesión
if (isset($_POST['action']) && $_POST['action'] === 'reset_filters') {
    unset($_SESSION['bulk_filters']);
    $_SESSION['message'] = 'Filtros restablecidos.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Helper: tokenizar texto en mayúsculas por caracteres A-Z0-9
function tokenizar(string $s): array {
    $tokens = preg_split('/[^A-Z0-9]+/', $s);
    if (!is_array($tokens)) { return []; }
    $tokens = array_values(array_filter($tokens, static fn($t) => $t !== ''));
    return $tokens;
}

// Helper: ¿algún término coincide? Si el término contiene caracteres no alfanuméricos, usamos strpos sobre el haystack;
// si es alfanumérico puro, exigimos coincidencia de token completa para evitar falsos positivos (ej.: ES dentro de GAMES)
function anyTermMatch(array $tokens, string $haystackUpper, array $terms): bool {
    foreach ($terms as $t) {
        $t = strtoupper((string)$t);
        if ($t === '') { continue; }
        if (preg_match('/[^A-Z0-9]/', $t)) {
            if (strpos($haystackUpper, $t) !== false) { return true; }
        } else {
            if (in_array($t, $tokens, true)) { return true; }
        }
    }
    return false;
}

// Subida de fichero (no depende de action)
if (isset($_FILES['xmlFile']) && isset($_FILES['xmlFile']['error']) && $_FILES['xmlFile']['error'] === UPLOAD_ERR_OK) {
    $fileExtension = pathinfo($_FILES['xmlFile']['name'], PATHINFO_EXTENSION);
    if (in_array(strtolower($fileExtension), ['xml', 'dat'], true)) {
        move_uploaded_file($_FILES['xmlFile']['tmp_name'], $xmlFile);
        $_SESSION['xml_uploaded'] = true;
        $_SESSION['message'] = 'Archivo cargado correctamente.';
    } else {
        $_SESSION['error'] = 'Solo se permiten archivos XML o DAT.';
    }
}

// Restaurar desde copia de seguridad .bak
if (isset($_POST['action']) && $_POST['action'] === 'restore_backup') {
    $backupFile = $xmlFile . '.bak';
    if (file_exists($backupFile)) {
        if (@copy($backupFile, $xmlFile)) {
            $_SESSION['xml_uploaded'] = true;
            $_SESSION['message'] = 'Restaurado correctamente desde la copia de seguridad (.bak).';
        } else {
            $_SESSION['error'] = 'No se pudo restaurar desde la copia de seguridad.';
        }
    } else {
        $_SESSION['error'] = 'No existe copia de seguridad disponible.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Cargar XML si está disponible para las acciones que lo requieren
$xml = null;
if (isset($_SESSION['xml_uploaded']) && file_exists($xmlFile)) {
    $xml = simplexml_load_file($xmlFile);
}

// Helper: construir términos desde selects (regiones a incluir e idiomas a excluir)
function mapearRegionesIdiomas(array $includeRegions, array $excludeLangs, array &$includeTerms, array &$excludeTerms): void {
    $regionMap = [
        'JAPON' => ['JAPAN'],
        'EUROPA' => ['EUROPE'],
        'USA' => ['USA', 'U.S.A.', 'UNITED STATES', 'AMERICA'],
        'ASIA' => ['ASIA'],
        'AUSTRALIA' => ['AUSTRALIA'],
        'ESCANDINAVIA' => ['SCANDINAVIA'],
        'COREA' => ['KOREA'],
        'CHINA' => ['CHINA'],
        'HONG KONG' => ['HONG KONG'],
        'TAIWAN' => ['TAIWAN'],
        'RUSIA' => ['RUSSIA'],
        'ESPAÑA' => ['SPAIN'],
        'ALEMANIA' => ['GERMANY'],
        'FRANCIA' => ['FRANCE'],
        'ITALIA' => ['ITALY'],
        'PAISES BAJOS' => ['NETHERLANDS'],
        'PORTUGAL' => ['PORTUGAL'],
        'BRASIL' => ['BRAZIL','BRAZILIAN'],
        'MEXICO' => ['MEXICO','MEXICAN'],
        'REINO UNIDO' => ['UNITED KINGDOM','UK','ENGLAND','BRITAIN','BRITISH'],
        'NORTEAMERICA' => ['NORTH AMERICA','NA'],
        'MUNDO/INTERNACIONAL' => ['WORLD','INTERNATIONAL'],
        'PAL' => ['PAL'],
        'NTSC' => ['NTSC']
    ];
    foreach ($includeRegions as $r) {
        $key = strtoupper(trim((string)$r));
        if (isset($regionMap[$key])) { foreach ($regionMap[$key] as $pat) { $includeTerms[] = $pat; } }
    }
    $langMap = [
        'EN' => ['EN'], 'JA' => ['JA'], 'FR' => ['FR'], 'DE' => ['DE'], 'ES' => ['ES'], 'IT' => ['IT'],
        'NL' => ['NL'], 'PT' => ['PT'], 'SV' => ['SV'], 'NO' => ['NO'], 'DA' => ['DA'], 'FI' => ['FI'],
        'ZH' => ['ZH'], 'KO' => ['KO'], 'PL' => ['PL'], 'RU' => ['RU'], 'CS' => ['CS'], 'HU' => ['HU']
    ];
    foreach ($excludeLangs as $l) {
        $key = strtoupper(trim((string)$l));
        if (isset($langMap[$key])) { foreach ($langMap[$key] as $pat) { $excludeTerms[] = $pat; } }
    }
}

// Simulación: contar coincidencias
if (isset($_POST['action']) && $_POST['action'] === 'bulk_count' && $xml) {
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
        $_SESSION['error'] = 'Debes seleccionar al menos una región o indicar algún término a incluir.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    $matches = 0;
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

    $_SESSION["bulk_filters"] = [
        'include' => $include,
        'exclude' => $exclude,
        'include_regions' => $includeRegions,
        'exclude_langs' => $excludeLangs,
    ];

    // Si es AJAX, responder con JSON accesible y no redirigir
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

// Guardar edición
if (isset($_POST['action']) && $_POST['action'] === 'edit' && $xml) {
    $index = (int)($_POST['index'] ?? -1);
    $dom = new DOMDocument();
    // Evitar nodos de texto de solo espacios entre elementos
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');
    if ($index >= 0 && $index < $games->length) {
        $gameToEdit = $games->item($index);
        if ($gameToEdit instanceof DOMElement) {
            crearBackup($xmlFile);
            $gameToEdit->setAttribute('name', (string)($_POST['game_name'] ?? ''));
            $description = $xpath->query('./description', $gameToEdit)->item(0);
            if ($description) { $description->nodeValue = (string)($_POST['description'] ?? ''); }
            else { $gameToEdit->appendChild($dom->createElement('description', (string)($_POST['description'] ?? ''))); }
            $category = $xpath->query('./category', $gameToEdit)->item(0);
            if ($category) { $category->nodeValue = (string)($_POST['category'] ?? ''); }
            else { $gameToEdit->appendChild($dom->createElement('category', (string)($_POST['category'] ?? ''))); }
            $rom = $xpath->query('./rom', $gameToEdit)->item(0);
            if ($rom instanceof DOMElement) {
                $rom->setAttribute('name', (string)($_POST['rom_name'] ?? ''));
                $rom->setAttribute('size', (string)($_POST['size'] ?? ''));
                $rom->setAttribute('crc', (string)($_POST['crc'] ?? ''));
                $rom->setAttribute('md5', (string)($_POST['md5'] ?? ''));
                $rom->setAttribute('sha1', (string)($_POST['sha1'] ?? ''));
            } else {
                $newRom = $dom->createElement('rom');
                $newRom->setAttribute('name', (string)($_POST['rom_name'] ?? ''));
                $newRom->setAttribute('size', (string)($_POST['size'] ?? ''));
                $newRom->setAttribute('crc', (string)($_POST['crc'] ?? ''));
                $newRom->setAttribute('md5', (string)($_POST['md5'] ?? ''));
                $newRom->setAttribute('sha1', (string)($_POST['sha1'] ?? ''));
                $gameToEdit->appendChild($newRom);
            }
            // Formateo limpio del XML al guardar
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->normalizeDocument();
            limpiarEspaciosEnBlancoDom($dom);
            if (!guardarDomConBackup($dom, $xmlFile)) {
                $_SESSION['error'] = 'No se pudo guardar el XML. Se revirtió al respaldo.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            $_SESSION['message'] = 'Juego actualizado correctamente.';
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Eliminar juego
if (isset($_POST['action']) && $_POST['action'] === 'delete' && $xml) {
    $index = (int)($_POST['index'] ?? -1);
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');
    if ($index >= 0 && $index < $games->length) {
        crearBackup($xmlFile);
        $gameToRemove = $games->item($index);
        if ($gameToRemove instanceof DOMElement) {
            $gameToRemove->parentNode->removeChild($gameToRemove);
        }
        // Formateo limpio del XML al guardar
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->normalizeDocument();
        limpiarEspaciosEnBlancoDom($dom);
        if (!guardarDomConBackup($dom, $xmlFile)) {
            $_SESSION['error'] = 'No se pudo guardar el XML tras eliminar. Se revirtió al respaldo.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $_SESSION['message'] = 'Juego eliminado correctamente.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Eliminación masiva
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && $xml) {
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
    // Mapear regiones a incluir e idiomas a excluir (sin regiones a excluir)
    mapearRegionesIdiomas($includeRegions, $excludeLangs, $includeTerms, $excludeTerms);
    if (count($includeTerms) === 0) {
        $_SESSION['error'] = 'Debes seleccionar al menos una región o indicar algún término a incluir.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument();
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    crearBackup($xmlFile);

    $deleted = 0;
    for ($i = $games->length - 1; $i >= 0; $i--) {
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
        $g->parentNode->removeChild($g);
        $deleted++;
    }

    $_SESSION['bulk_filters'] = [
        'include' => $include,
        'exclude' => $exclude,
        'include_regions' => $includeRegions,
        'exclude_langs' => $excludeLangs,
    ];

    // Formateo limpio del XML al guardar
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->normalizeDocument();
    limpiarEspaciosEnBlancoDom($dom);
    if (!guardarDomConBackup($dom, $xmlFile)) {
        $_SESSION['error'] = 'No se pudo guardar tras la eliminación masiva. Se revirtió al respaldo.';
    } else {
        $_SESSION['message'] = 'Eliminación masiva completada. Registros eliminados: ' . $deleted . '.';
        // Mostrar botón para guardar/compactar explícitamente a petición del usuario
        $_SESSION['pending_save'] = true;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Eliminar archivo XML actual
if (isset($_POST['action']) && $_POST['action'] === 'remove_xml') {
    if (file_exists($xmlFile)) { unlink($xmlFile); }
    unset($_SESSION['xml_uploaded']);
    $_SESSION['message'] = 'Archivo eliminado correctamente.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
