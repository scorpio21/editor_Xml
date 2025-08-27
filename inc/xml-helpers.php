<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

/**
 * Asegura que la carpeta de subidas exista.
 * Crea la carpeta recursivamente si no existe.
 */
function asegurarCarpetaUploads(string $dir): void {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

/**
 * Carga el XML actual si está disponible en disco y la sesión lo indica.
 * Devuelve SimpleXMLElement o null si no hay XML o si falla la carga.
 * Registra información y errores mediante el logger.
 */
function cargarXmlSiDisponible(string $xmlFile): ?SimpleXMLElement {
    if (isset($_SESSION['xml_uploaded']) && file_exists($xmlFile)) {
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlFile);
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            $errs = array_map(static function ($e) {
                return [ 'level' => $e->level, 'code' => $e->code, 'line' => $e->line, 'message' => trim((string)$e->message) ];
            }, is_array($errors) ? $errors : []);
            registrarError('xml-helpers.php:cargarXmlSiDisponible', 'Fallo al cargar XML: formato inválido', [ 'xmlFile' => $xmlFile, 'errors' => $errs ]);
            $_SESSION['error'] = 'Error al cargar el archivo XML. Formato incorrecto.';
            unset($_SESSION['xml_uploaded']);
            return null;
        }
        libxml_use_internal_errors($prev);
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

/**
 * Crea un archivo de copia de seguridad con extensión .bak si el XML existe.
 */
function crearBackup(string $xmlFile): void {
    if (file_exists($xmlFile)) {
        @copy($xmlFile, $xmlFile . '.bak');
    }
}

/**
 * Guarda el DOM en disco creando un backup previo si existía el archivo.
 * En caso de fallo, revierte desde el backup y devuelve false.
 */
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

/**
 * Divide una cadena en tokens alfanuméricos en mayúsculas (A-Z0-9).
 * Devuelve un array sin vacíos.
 */
function tokenizar(string $s): array {
    $tokens = preg_split('/[^A-Z0-9]+/', strtoupper($s));
    if (!is_array($tokens)) { return []; }
    $tokens = array_values(array_filter($tokens, static fn($t) => $t !== ''));
    return $tokens;
}

/**
 * Comprueba si algún término coincide con el haystack.
 * - Si el término tiene no-alfanuméricos, usa strpos sobre el haystack (en mayúsculas).
 * - Si es alfanumérico puro, exige coincidencia exacta de token.
 */
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

/**
 * Mapea selects de regiones a términos de inclusión y de idiomas a términos de exclusión.
 */
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
