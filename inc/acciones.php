<?php
declare(strict_types=1);

// Shim de compatibilidad: delega toda la lógica al nuevo enrutador modular y termina.
require_once __DIR__ . '/router-acciones.php';
return;

// Este archivo procesa todas las acciones POST y subidas.
// Debe ejecutarse al inicio del request. Redirige y exit() cuando corresponde.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// CSRF: helpers y verificación centralizada
require_once __DIR__ . '/csrf-helper.php';
require_once __DIR__ . '/logger.php';
/**
 * Requiere un token CSRF válido o redirige con error.
 */
function requireValidCsrf(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !verificarTokenCSRF($token)) {
        registrarAdvertencia('acciones.php:requireValidCsrf', 'Token CSRF inválido o ausente', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'action' => $_POST['action'] ?? null,
        ]);
        // Si es una petición AJAX, devolver JSON estandarizado
        if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Sesión no válida o token CSRF incorrecto.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['error'] = 'Sesión no válida o token CSRF incorrecto.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// (migrado) Acción add_game movida más abajo, después de cargar $xml

// Guardar/Compactar XML manualmente
if (isset($_POST['action']) && $_POST['action'] === 'compact_xml') {
    requireValidCsrf();
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

// Descargar/exportar el XML actual
if (isset($_POST['action']) && $_POST['action'] === 'download_xml') {
    requireValidCsrf();
    if (file_exists($xmlFile)) {
        $filesize = @filesize($xmlFile);
        // Calcular conteo actual (games + machines)
        $countNow = 0;
        $sx = @simplexml_load_file($xmlFile);
        if ($sx instanceof SimpleXMLElement) {
            $games = $sx->xpath('/datafile/game');
            $machines = $sx->xpath('/datafile/machine');
            $countNow = (is_array($games) ? count($games) : 0) + (is_array($machines) ? count($machines) : 0);
        }
        // Determinar base del nombre original (si existe)
        $orig = (string)($_SESSION['original_filename'] ?? 'current.xml');
        $origNoExt = preg_replace('/\.[^.]+$/', '', $orig) ?? 'current';
        $base = $origNoExt;
        // Intentar extraer patrón "Nombre (numero) (fecha)"
        if (preg_match('/^(.*)\s\(\d+\)\s\([^)]*\)$/', $origNoExt, $m)) {
            $base = trim($m[1]);
        }
        // Sanear base para nombre de archivo en Windows
        $base = preg_replace('/[\\\\\/:\*\?\"<>\|]/', ' ', $base);
        $dateStr = date('Y-m-d H-i-s');
        $filename = sprintf('%s (%d) (%s).xml', $base !== '' ? $base : 'datafile', $countNow, $dateStr);
        // Cabeceras para descarga
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        if ($filesize !== false) {
            header('Content-Length: ' . (string)$filesize);
        }
        // Enviar contenido y terminar
        @readfile($xmlFile);
        exit;
    } else {
        $_SESSION['error'] = 'No hay XML disponible para descargar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Crear nuevo XML desde cero
if (isset($_POST['action']) && $_POST['action'] === 'create_xml') {
    requireValidCsrf();
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
    requireValidCsrf();
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

// Subida de fichero (no depende de action) — proteger con CSRF
if (isset($_FILES['xmlFile']) && isset($_FILES['xmlFile']['error']) && $_FILES['xmlFile']['error'] === UPLOAD_ERR_OK) {
    requireValidCsrf();
    $fileExtension = pathinfo($_FILES['xmlFile']['name'], PATHINFO_EXTENSION);
    if (in_array(strtolower($fileExtension), ['xml', 'dat'], true)) {
        // Guardar nombre original para futuras exportaciones
        $_SESSION['original_filename'] = (string)$_FILES['xmlFile']['name'];
        move_uploaded_file($_FILES['xmlFile']['tmp_name'], $xmlFile);
        $_SESSION['xml_uploaded'] = true;
        $_SESSION['message'] = 'Archivo cargado correctamente.';

    } else {
        $_SESSION['error'] = 'Solo se permiten archivos XML o DAT.';
    }
}

// Restaurar desde copia de seguridad .bak
if (isset($_POST['action']) && $_POST['action'] === 'restore_backup') {
    requireValidCsrf();
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
    requireValidCsrf();
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
        $key = mb_strtoupper(trim((string)$r), 'UTF-8');
        if (isset($regionMap[$key])) { foreach ($regionMap[$key] as $pat) { $includeTerms[] = $pat; } }
    }
    $langMap = [
        'EN' => ['EN'], 'JA' => ['JA'], 'FR' => ['FR'], 'DE' => ['DE'], 'ES' => ['ES'], 'IT' => ['IT'],
        'NL' => ['NL'], 'PT' => ['PT'], 'SV' => ['SV'], 'NO' => ['NO'], 'DA' => ['DA'], 'FI' => ['FI'],
        'ZH' => ['ZH'], 'KO' => ['KO'], 'PL' => ['PL'], 'RU' => ['RU'], 'CS' => ['CS'], 'HU' => ['HU']
    ];
    foreach ($excludeLangs as $l) {
        $key = mb_strtoupper(trim((string)$l), 'UTF-8');
        if (isset($langMap[$key])) { foreach ($langMap[$key] as $pat) { $excludeTerms[] = $pat; } }
    }
}

// Simulación: contar coincidencias
if (isset($_POST['action']) && $_POST['action'] === 'bulk_count' && $xml) {
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
        // Respuesta uniforme si es AJAX
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
    requireValidCsrf();
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
    requireValidCsrf();
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
    requireValidCsrf();
    
    require_once __DIR__ . '/mame-filters.php';
    
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
    // Mapear regiones a incluir e idiomas a excluir (sin regiones a excluir)
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

    // Los filtros ya están guardados en sesión arriba

    // Formateo limpio del XML al guardar
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
        // Mostrar botón para guardar/compactar explícitamente a petición del usuario
        $_SESSION['pending_save'] = true;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// === Dedupe por región: conteo previo ===
if (isset($_POST['action']) && $_POST['action'] === 'dedupe_region_count' && $xml) {
    requireValidCsrf();
    $prefer = trim((string)($_POST['prefer_region'] ?? ''));
    $keepEU = isset($_POST['keep_europe']) && (string)$_POST['keep_europe'] === '1';
    if ($prefer === '') {
        // Respuesta uniforme si es AJAX
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

    // Cargar DOM y preparar XPath
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    // Mapear términos de la región preferida (+Europa opcional)
    $includeTerms = [];
    $excludeTerms = [];
    $regionsPref = [$prefer];
    if ($keepEU && mb_strtoupper($prefer, 'UTF-8') !== 'EUROPA') { $regionsPref[] = 'Europa'; }
    mapearRegionesIdiomas($regionsPref, [], $includeTerms, $excludeTerms);

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
            $tokens = tokenizar($hay);
            $pref = anyTermMatch($tokens, $hay, $includeTerms);
            $isPreferred[$idx] = $pref;
            if ($pref) { $hasPreferred = true; }
        }
        if (!$hasPreferred) {
            // No hay preferida en este grupo: no contamos nada (no deduplicar)
            continue;
        }
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
if (isset($_POST['action']) && $_POST['action'] === 'dedupe_region' && $xml) {
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
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    // Mapear términos de la región preferida (+Europa opcional)
    $includeTerms = [];
    $excludeTerms = [];
    $regionsPref = [$prefer];
    if ($keepEU && mb_strtoupper($prefer, 'UTF-8') !== 'EUROPA') { $regionsPref[] = 'Europa'; }
    mapearRegionesIdiomas($regionsPref, [], $includeTerms, $excludeTerms);

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

    crearBackup($xmlFile);

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
            $tokens = tokenizar($hay);
            $pref = anyTermMatch($tokens, $hay, $includeTerms);
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
    limpiarEspaciosEnBlancoDom($dom);
    if (!guardarDomConBackup($dom, $xmlFile)) {
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
if (isset($_POST['action']) && $_POST['action'] === 'dedupe_region_export_csv' && $xml) {
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
    $dom->loadXML($xml->asXML());
    $xpath = new DOMXPath($dom);
    $games = $xpath->query('/datafile/game');

    // Términos para preferencia de región (+Europa opcional)
    $includeTerms = [];
    $excludeTerms = [];
    $regionsPref = [$prefer];
    if ($keepEU && mb_strtoupper($prefer, 'UTF-8') !== 'EUROPA') { $regionsPref[] = 'Europa'; }
    mapearRegionesIdiomas($regionsPref, [], $includeTerms, $excludeTerms);

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
            $tokens = tokenizar($hay);
            $pref = anyTermMatch($tokens, $hay, $includeTerms);
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

// Eliminar archivo XML actual
if (isset($_POST['action']) && $_POST['action'] === 'remove_xml') {
    requireValidCsrf();
    if (file_exists($xmlFile)) { unlink($xmlFile); }
    unset($_SESSION['xml_uploaded']);
    $_SESSION['message'] = 'Archivo eliminado correctamente.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
