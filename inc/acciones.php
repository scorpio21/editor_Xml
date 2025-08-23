<?php
declare(strict_types=1);

// Este archivo procesa todas las acciones POST y subidas.
// Debe ejecutarse al inicio del request. Redirige y exit() cuando corresponde.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// (migrado) Acción add_game movida más abajo, después de cargar $xml

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

// Crear nuevo XML desde cero
if (isset($_POST['action']) && $_POST['action'] === 'create_xml') {
    require_once __DIR__ . '/xml-helpers.php';
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $version = trim((string)($_POST['version'] ?? '1.0'));
    $date = trim((string)($_POST['date'] ?? date('Y-m-d')));
    $author = trim((string)($_POST['author'] ?? ''));
    $homepage = trim((string)($_POST['homepage'] ?? ''));
    $url = trim((string)($_POST['url'] ?? ''));

    if ($name === '' || $description === '' || $version === '' || $date === '' || $author === '') {
        $_SESSION['error'] = 'Rellena todos los campos obligatorios (nombre, descripción, versión, fecha y autor).';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    $datafile = $dom->createElement('datafile');
    $header = $dom->createElement('header');
    $header->appendChild($dom->createElement('name', $name));
    $header->appendChild($dom->createElement('description', $description));
    $header->appendChild($dom->createElement('version', $version));
    $header->appendChild($dom->createElement('date', $date));
    $header->appendChild($dom->createElement('author', $author));
    if ($homepage !== '') { $header->appendChild($dom->createElement('homepage', $homepage)); }
    if ($url !== '') { $header->appendChild($dom->createElement('url', $url)); }
    $datafile->appendChild($header);
    $dom->appendChild($datafile);

    // Limpieza de espacios y guardado con backup
    $dom->normalizeDocument();
    limpiarEspaciosEnBlancoDom($dom);
    if (!guardarDomConBackup($dom, $xmlFile)) {
        $_SESSION['error'] = 'No se pudo crear/guardar el XML.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $_SESSION['xml_uploaded'] = true;
    unset($_SESSION['pending_save']);
    $_SESSION['message'] = 'XML creado correctamente.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
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

// Añadir juego (después de cargar $xml)
if (isset($_POST['action']) && $_POST['action'] === 'add_game' && $xml) {
    $gameName = trim((string)($_POST['game_name'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? ''));
    $cat = trim((string)($_POST['category'] ?? ''));
    // Campos de ROM como arrays
    $romNames = isset($_POST['rom_name']) ? (array)$_POST['rom_name'] : [];
    $sizes = isset($_POST['size']) ? (array)$_POST['size'] : [];
    $crcs = isset($_POST['crc']) ? (array)$_POST['crc'] : [];
    $md5s = isset($_POST['md5']) ? (array)$_POST['md5'] : [];
    $sha1s = isset($_POST['sha1']) ? (array)$_POST['sha1'] : [];

    if ($gameName === '' || $desc === '') {
        $_SESSION['error'] = 'Faltan campos obligatorios del juego (nombre o descripción).';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $n = min(count($romNames), count($sizes), count($crcs), count($md5s), count($sha1s));
    if ($n === 0) {
        $_SESSION['error'] = 'Debes añadir al menos una ROM.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Normalización y validación básica por ROM
    $roms = [];
    for ($i = 0; $i < $n; $i++) {
        $rname = trim((string)$romNames[$i]);
        $rsize = trim((string)$sizes[$i]);
        $rcrc = strtoupper(trim((string)$crcs[$i]));
        $rmd5 = strtolower(trim((string)$md5s[$i]));
        $rsha1 = strtolower(trim((string)$sha1s[$i]));
        if ($rname === '' || $rsize === '' || $rcrc === '' || $rmd5 === '' || $rsha1 === '') {
            $_SESSION['error'] = 'Faltan campos obligatorios en alguna ROM.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^\d+$/', $rsize)) {
            $_SESSION['error'] = 'Tamaño inválido en una ROM (debe ser entero en bytes).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9A-F]{8}$/', $rcrc)) {
            $_SESSION['error'] = 'CRC32 inválido en una ROM (8 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9a-f]{32}$/', $rmd5)) {
            $_SESSION['error'] = 'MD5 inválido en una ROM (32 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9a-f]{40}$/', $rsha1)) {
            $_SESSION['error'] = 'SHA1 inválido en una ROM (40 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $roms[] = [$rname, $rsize, $rcrc, $rmd5, $rsha1];
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);

    $df = $xpath->query('/datafile')->item(0);
    if (!($df instanceof DOMElement)) {
        $_SESSION['error'] = 'Estructura XML inválida: falta datafile.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    crearBackup($xmlFile);

    // Detectar tipo predominante en el XML actual: machine vs game
    $hasMachine = $xpath->query('/datafile/machine')->length > 0;
    $hasGame = $xpath->query('/datafile/game')->length > 0;
    $nodeName = ($hasMachine && !$hasGame) ? 'machine' : 'game';

    $entry = $dom->createElement($nodeName);
    $entry->setAttribute('name', $gameName);
    $entry->appendChild($dom->createElement('description', $desc));
    // Solo añadir category para <game> para mantener compatibilidad con dats tipo MAME
    if ($nodeName === 'game' && $cat !== '') { $entry->appendChild($dom->createElement('category', $cat)); }

    foreach ($roms as [$rname, $rsize, $rcrc, $rmd5, $rsha1]) {
        $rom = $dom->createElement('rom');
        $rom->setAttribute('name', $rname);
        $rom->setAttribute('size', $rsize);
        $rom->setAttribute('crc', $rcrc);
        $rom->setAttribute('md5', $rmd5);
        $rom->setAttribute('sha1', $rsha1);
        $entry->appendChild($rom);
    }

    $df->appendChild($entry);

    // Formatear y limpiar
    $dom->formatOutput = true;
    $dom->normalizeDocument();
    limpiarEspaciosEnBlancoDom($dom);
    if (!guardarDomConBackup($dom, $xmlFile)) {
        $_SESSION['error'] = 'No se pudo guardar el nuevo juego. Se revirtió al respaldo.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $_SESSION['message'] = 'Juego añadido correctamente.';
    $_SESSION['pending_save'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
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
    $nodeType = (string)($_POST['node_type'] ?? 'game');
    $nodeType = ($nodeType === 'machine') ? 'machine' : 'game';
    $newName = trim((string)($_POST['game_name'] ?? ''));
    $newDesc = trim((string)($_POST['description'] ?? ''));
    $newCat  = trim((string)($_POST['category'] ?? ''));

    // ROMs como arrays
    $romNames = isset($_POST['rom_name']) ? (array)$_POST['rom_name'] : [];
    $sizes = isset($_POST['size']) ? (array)$_POST['size'] : [];
    $crcs = isset($_POST['crc']) ? (array)$_POST['crc'] : [];
    $md5s = isset($_POST['md5']) ? (array)$_POST['md5'] : [];
    $sha1s = isset($_POST['sha1']) ? (array)$_POST['sha1'] : [];

    if ($newName === '' || $newDesc === '') {
        $_SESSION['error'] = 'Faltan campos obligatorios (nombre o descripción).';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $n = min(count($romNames), count($sizes), count($crcs), count($md5s), count($sha1s));
    if ($n === 0) {
        $_SESSION['error'] = 'Debes mantener al menos una ROM.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $roms = [];
    for ($i = 0; $i < $n; $i++) {
        $rname = trim((string)$romNames[$i]);
        $rsize = trim((string)$sizes[$i]);
        $rcrc = strtoupper(trim((string)$crcs[$i]));
        $rmd5 = strtolower(trim((string)$md5s[$i]));
        $rsha1 = strtolower(trim((string)$sha1s[$i]));
        if ($rname === '' || $rsize === '' || $rcrc === '' || $rmd5 === '' || $rsha1 === '') {
            $_SESSION['error'] = 'Faltan campos obligatorios en alguna ROM.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^\d+$/', $rsize)) {
            $_SESSION['error'] = 'Tamaño inválido en una ROM (entero en bytes).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9A-F]{8}$/', $rcrc)) {
            $_SESSION['error'] = 'CRC32 inválido en una ROM (8 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9a-f]{32}$/', $rmd5)) {
            $_SESSION['error'] = 'MD5 inválido en una ROM (32 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9a-f]{40}$/', $rsha1)) {
            $_SESSION['error'] = 'SHA1 inválido en una ROM (40 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $roms[] = [$rname, $rsize, $rcrc, $rmd5, $rsha1];
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('/datafile/' . $nodeType);
    if ($index >= 0 && $nodes && $index < $nodes->length) {
        $toEdit = $nodes->item($index);
        if ($toEdit instanceof DOMElement) {
            crearBackup($xmlFile);
            // Actualizar atributos y campos
            $toEdit->setAttribute('name', $newName);
            $descNode = $xpath->query('./description', $toEdit)->item(0);
            if ($descNode) { $descNode->nodeValue = $newDesc; }
            else { $toEdit->appendChild($dom->createElement('description', $newDesc)); }

            if ($nodeType === 'game') {
                $catNode = $xpath->query('./category', $toEdit)->item(0);
                if ($catNode) { $catNode->nodeValue = $newCat; }
                else if ($newCat !== '') { $toEdit->appendChild($dom->createElement('category', $newCat)); }
            } else {
                // Asegurar que no queden categorías en machines
                $catNode = $xpath->query('./category', $toEdit)->item(0);
                if ($catNode && $catNode->parentNode) { $catNode->parentNode->removeChild($catNode); }
            }

            // Reemplazar todas las ROMs
            $existingRoms = $xpath->query('./rom', $toEdit);
            if ($existingRoms && $existingRoms->length) {
                // eliminar desde el final para evitar problemas de índice
                for ($i = $existingRoms->length - 1; $i >= 0; $i--) {
                    $n = $existingRoms->item($i);
                    if ($n && $n->parentNode) { $n->parentNode->removeChild($n); }
                }
            }
            foreach ($roms as [$rname, $rsize, $rcrc, $rmd5, $rsha1]) {
                $romEl = $dom->createElement('rom');
                $romEl->setAttribute('name', $rname);
                $romEl->setAttribute('size', $rsize);
                $romEl->setAttribute('crc', $rcrc);
                $romEl->setAttribute('md5', $rmd5);
                $romEl->setAttribute('sha1', $rsha1);
                $toEdit->appendChild($romEl);
            }

            // Guardar
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->normalizeDocument();
            limpiarEspaciosEnBlancoDom($dom);
            if (!guardarDomConBackup($dom, $xmlFile)) {
                $_SESSION['error'] = 'No se pudo guardar el XML. Se revirtió al respaldo.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            $_SESSION['message'] = 'Entrada actualizada correctamente.';
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Eliminar entrada (game o machine)
if (isset($_POST['action']) && $_POST['action'] === 'delete' && $xml) {
    $index = (int)($_POST['index'] ?? -1);
    $nodeType = (string)($_POST['node_type'] ?? 'game');
    $nodeType = ($nodeType === 'machine') ? 'machine' : 'game';
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('/datafile/' . $nodeType);
    if ($nodes && $index >= 0 && $index < $nodes->length) {
        crearBackup($xmlFile);
        $toRemove = $nodes->item($index);
        if ($toRemove instanceof DOMElement) {
            $toRemove->parentNode->removeChild($toRemove);
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
        $_SESSION['message'] = ($nodeType === 'machine') ? 'Máquina eliminada correctamente.' : 'Juego eliminado correctamente.';
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
    $machines = $xpath->query('/datafile/machine');

    crearBackup($xmlFile);

    $deleted = 0;
    // Eliminar juegos
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
        if (!anyTermMatch($tokens, $haystackUpper, $includeTerms)) { continue; }
        if (anyTermMatch($tokens, $haystackUpper, $excludeTerms)) { continue; }
        $g->parentNode->removeChild($g);
        $deleted++;
    }
    // Eliminar máquinas
    for ($i = $machines->length - 1; $i >= 0; $i--) {
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
        if (!anyTermMatch($tokens, $haystackUpper, $includeTerms)) { continue; }
        if (anyTermMatch($tokens, $haystackUpper, $excludeTerms)) { continue; }
        $m->parentNode->removeChild($m);
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
