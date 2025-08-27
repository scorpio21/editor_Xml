<?php
declare(strict_types=1);

// Módulo: acciones CRUD y utilitarias del XML (crear, editar, eliminar, descargar, restaurar, compactar, subir, reset filtros)
// Requisitos previos: require de common.php, xml-helpers.php y variables $xmlFile, $xml

// Subida de fichero (no depende de action) — proteger con CSRF
if (isset($_FILES['xmlFile']) && isset($_FILES['xmlFile']['error']) && $_FILES['xmlFile']['error'] === UPLOAD_ERR_OK) {
    requireValidCsrf();
    $fileExtension = pathinfo($_FILES['xmlFile']['name'], PATHINFO_EXTENSION);
    if (in_array(strtolower($fileExtension), ['xml', 'dat'], true)) {
        // Guardar nombre original para futuras exportaciones
        $_SESSION['original_filename'] = (string)$_FILES['xmlFile']['name'];
        if (!@move_uploaded_file($_FILES['xmlFile']['tmp_name'], $xmlFile)) {
            registrarError('crud.php:upload', 'No se pudo mover el archivo subido al destino.', [
                'dest' => $xmlFile,
                'size' => $_FILES['xmlFile']['size'] ?? null,
                'name' => $_FILES['xmlFile']['name'] ?? null,
            ]);
            $_SESSION['error'] = 'No se pudo mover el archivo subido.';
        } else {
            $_SESSION['xml_uploaded'] = true;
            $_SESSION['message'] = 'Archivo cargado correctamente.';
        }

    } else {
        $_SESSION['error'] = 'Solo se permiten archivos XML o DAT.';
    }
}

if (!isset($_POST['action'])) {
    return; // no hay más que hacer
}

$action = (string)$_POST['action'];

// Guardar/Compactar XML manualmente
if ($action === 'compact_xml') {
    requireValidCsrf();
    if (file_exists($xmlFile)) {
        require_once __DIR__ . '/../xml-helpers.php';
        $raw = @file_get_contents($xmlFile);
        if ($raw === false) {
            registrarError('crud.php:compact_xml', 'No se pudo leer el XML para compactar.', [ 'file' => $xmlFile ]);
            $_SESSION['error'] = 'No se pudo leer el XML para compactar.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        if (@$dom->loadXML($raw) === false) {
            registrarError('crud.php:compact_xml', 'XML inválido. Falló loadXML al compactar.', []);
            $_SESSION['error'] = 'El XML no es válido y no se pudo compactar.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $dom->formatOutput = true;
        $dom->normalizeDocument();
        EditorXml::limpiarEspaciosEnBlancoDom($dom);
        if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
            registrarError('crud.php:compact_xml', 'Fallo al guardar XML compactado. Revertido al respaldo.', [ 'file' => $xmlFile ]);
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

// Descargar/exportar el XML actual
if ($action === 'download_xml') {
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
        if (@readfile($xmlFile) === false) {
            registrarError('crud.php:download_xml', 'Fallo al enviar el fichero para descarga.', [ 'file' => $xmlFile ]);
        }
        exit;
    } else {
        $_SESSION['error'] = 'No hay XML disponible para descargar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Exportar resultados filtrados (sin duplicados) a un nuevo XML en memoria
if ($action === 'export_filtered_xml') {
    requireValidCsrf();
    if (!file_exists($xmlFile)) {
        $_SESSION['error'] = 'No hay XML disponible para exportar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    // Parámetros de filtrado (simulan los GET del listado)
    $q = isset($_POST['q']) ? trim((string)$_POST['q']) : '';
    $qInRoms = isset($_POST['q_in_roms']) && (string)$_POST['q_in_roms'] === '1';
    $qInHashes = isset($_POST['q_in_hashes']) && (string)$_POST['q_in_hashes'] === '1';

    $sx = @simplexml_load_file($xmlFile);
    if (!($sx instanceof SimpleXMLElement)) {
        registrarError('crud.php:export_filtered_xml', 'Fallo al cargar XML para exportar filtrado.', [ 'file' => $xmlFile ]);
        $_SESSION['error'] = 'No se pudo cargar el XML para exportar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Recopilar entradas game/machine en orden
    $children = $sx->xpath('/datafile/*[self::game or self::machine]') ?: [];
    $entries = [];
    foreach ($children as $node) {
        $entries[] = [ 'el' => $node, 'type' => $node->getName() === 'machine' ? 'machine' : 'game' ];
    }

    if ($q !== '') {
        $qUpper = mb_strtoupper($q, 'UTF-8');
        $terms = array_values(array_filter(preg_split('/\s+/', $q)));
        $hasSpace = mb_strpos($qUpper, ' ', 0, 'UTF-8') !== false;
        $qHash = strtoupper(str_replace([' ', '-', '_'], '', $q));
        $entries = array_values(array_filter($entries, static function($item) use ($terms, $qUpper, $hasSpace, $qInRoms, $qInHashes, $qHash) {
            $e = $item['el'];
            $name = (string)($e['name'] ?? '');
            $hayName = mb_strtoupper($name, 'UTF-8');
            $matchBase = false;
            if ($hasSpace) {
                if (mb_strpos($hayName, $qUpper, 0, 'UTF-8') !== false) { $matchBase = true; }
                if (!$matchBase) {
                    $all = true;
                    foreach ($terms as $t) {
                        $t = mb_strtoupper((string)$t, 'UTF-8');
                        if ($t === '' || mb_strpos($hayName, $t, 0, 'UTF-8') === false) { $all = false; break; }
                    }
                    $matchBase = $all;
                }
            } else {
                $all = true;
                foreach ($terms as $t) {
                    $t = mb_strtoupper((string)$t, 'UTF-8');
                    if ($t === '' || mb_strpos($hayName, $t, 0, 'UTF-8') === false) { $all = false; break; }
                }
                $matchBase = $all;
            }

            $matchRoms = false;
            if ($qInRoms && isset($e->rom)) {
                foreach ($e->rom as $rom) {
                    $romName = (string)($rom['name'] ?? '');
                    $hayRom = mb_strtoupper($romName, 'UTF-8');
                    if ($hasSpace) {
                        if (mb_strpos($hayRom, $qUpper, 0, 'UTF-8') !== false) { $matchRoms = true; break; }
                        $all = true;
                        foreach ($terms as $t) {
                            $t = mb_strtoupper((string)$t, 'UTF-8');
                            if ($t === '' || mb_strpos($hayRom, $t, 0, 'UTF-8') === false) { $all = false; break; }
                        }
                        if ($all) { $matchRoms = true; break; }
                    } else {
                        $all = true;
                        foreach ($terms as $t) {
                            $t = mb_strtoupper((string)$t, 'UTF-8');
                            if ($t === '' || mb_strpos($hayRom, $t, 0, 'UTF-8') === false) { $all = false; break; }
                        }
                        if ($all) { $matchRoms = true; break; }
                    }
                }
            }

            $matchHashes = false;
            if ($qInHashes && isset($e->rom)) {
                foreach ($e->rom as $rom) {
                    $crc  = strtoupper((string)($rom['crc'] ?? ''));
                    $md5  = strtoupper((string)($rom['md5'] ?? ''));
                    $sha1 = strtoupper((string)($rom['sha1'] ?? ''));
                    $hay = str_replace([' ', '-', '_'], '', $crc . $md5 . $sha1);
                    if ($qHash !== '' && strpos($hay, $qHash) !== false) { $matchHashes = true; break; }
                }
            }

            return $matchBase || $matchRoms || $matchHashes;
        }));
        // Deduplicar por (tipo + nombre)
        $seen = [];
        $entries = array_values(array_filter($entries, static function($item) use (&$seen) {
            $node = $item['el'];
            $type = $item['type'];
            $name = (string)($node['name'] ?? '');
            $key = $type . '|' . mb_strtolower($name, 'UTF-8');
            if (isset($seen[$key])) { return false; }
            $seen[$key] = true;
            return true;
        }));
    }

    // Construir DOM de salida
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $datafile = $dom->createElement('datafile');

    // Copiar cabecera si existe
    if (isset($sx->header)) {
        $header = $dom->createElement('header');
        $fields = ['name','description','version','date','author','homepage','url'];
        foreach ($fields as $f) {
            if (isset($sx->header->{$f}) && (string)$sx->header->{$f} !== '') {
                // Escapar contenido textual para XML
                $safeVal = htmlspecialchars((string)$sx->header->{$f}, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $header->appendChild($dom->createElement($f, $safeVal));
            }
        }
        $datafile->appendChild($header);
    }

    // Añadir entradas filtradas
    foreach ($entries as $it) {
        $e = $it['el'];
        $type = $it['type'];
        $node = $dom->createElement($type);
        $node->setAttribute('name', (string)($e['name'] ?? ''));
        if ($type === 'game') {
            if (isset($e->description)) {
                $safeDesc = htmlspecialchars((string)$e->description, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $node->appendChild($dom->createElement('description', $safeDesc));
            }
            if (isset($e->category) && (string)$e->category !== '') {
                $safeCat = htmlspecialchars((string)$e->category, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $node->appendChild($dom->createElement('category', $safeCat));
            }
        } else { // machine
            if (isset($e->description)) {
                $safeDesc = htmlspecialchars((string)$e->description, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $node->appendChild($dom->createElement('description', $safeDesc));
            }
            if (isset($e->year) && (string)$e->year !== '') {
                $safeYear = htmlspecialchars((string)$e->year, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $node->appendChild($dom->createElement('year', $safeYear));
            }
            if (isset($e->manufacturer) && (string)$e->manufacturer !== '') {
                $safeMan = htmlspecialchars((string)$e->manufacturer, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $node->appendChild($dom->createElement('manufacturer', $safeMan));
            }
        }
        if (isset($e->rom)) {
            foreach ($e->rom as $rom) {
                $romEl = $dom->createElement('rom');
                foreach (['name','size','crc','md5','sha1'] as $attr) {
                    if (isset($rom[$attr]) && (string)$rom[$attr] !== '') {
                        $romEl->setAttribute($attr, (string)$rom[$attr]);
                    }
                }
                $node->appendChild($romEl);
            }
        }
        $datafile->appendChild($node);
    }

    $dom->appendChild($datafile);
    $dom->normalizeDocument();
    EditorXml::limpiarEspaciosEnBlancoDom($dom);

    // Nombre de archivo amigable
    $base = 'filtered';
    if (isset($_SESSION['original_filename'])) {
        $origNoExt = preg_replace('/\.[^.]+$/', '', (string)$_SESSION['original_filename']) ?? 'current';
        $base = preg_replace('/[\\\/:\*\?\"<>\|]/', ' ', (string)$origNoExt);
    }
    $dateStr = date('Y-m-d H-i-s');
    $filename = sprintf('%s (filtered) (%d) (%s).xml', $base !== '' ? $base : 'datafile', count($entries), $dateStr);

    header('Content-Type: application/xml; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo $dom->saveXML();
    exit;
}

// Crear nuevo XML desde cero
if ($action === 'create_xml') {
    requireValidCsrf();
    require_once __DIR__ . '/../xml-helpers.php';
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
    EditorXml::limpiarEspaciosEnBlancoDom($dom);
    if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
        registrarError('crud.php:create_xml', 'No se pudo crear/guardar el XML.', [ 'file' => $xmlFile ]);
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
if ($action === 'reset_filters') {
    requireValidCsrf();
    unset($_SESSION['bulk_filters']);
    $_SESSION['message'] = 'Filtros restablecidos.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Restaurar desde copia de seguridad .bak
if ($action === 'restore_backup') {
    requireValidCsrf();
    $backupFile = $xmlFile . '.bak';
    if (file_exists($backupFile)) {
        if (@copy($backupFile, $xmlFile)) {
            $_SESSION['xml_uploaded'] = true;
            $_SESSION['message'] = 'Restaurado correctamente desde la copia de seguridad (.bak).';
        } else {
            registrarError('crud.php:restore_backup', 'No se pudo restaurar desde la copia de seguridad.', [
                'backup' => $backupFile,
                'dest' => $xmlFile,
            ]);
            $_SESSION['error'] = 'No se pudo restaurar desde la copia de seguridad.';
        }
    } else {
        $_SESSION['error'] = 'No existe copia de seguridad disponible.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Añadir juego
if ($action === 'add_game' && isset($xml) && $xml instanceof SimpleXMLElement) {
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
    if (@$dom->loadXML($xml->asXML()) === false) {
        registrarError('crud.php:add_game', 'Falló loadXML al preparar DOM para añadir juego.', []);
        $_SESSION['error'] = 'No se pudo cargar el XML en memoria.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $xpath = new DOMXPath($dom);

    $df = $xpath->query('/datafile')->item(0);
    if (!($df instanceof DOMElement)) {
        $_SESSION['error'] = 'Estructura XML inválida: falta datafile.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    EditorXml::crearBackup($xmlFile);

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
    EditorXml::limpiarEspaciosEnBlancoDom($dom);
    if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
        registrarError('crud.php:add_game', 'No se pudo guardar el nuevo juego. Revertido al respaldo.', [ 'file' => $xmlFile ]);
        $_SESSION['error'] = 'No se pudo guardar el nuevo juego. Se revirtió al respaldo.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $_SESSION['message'] = 'Juego añadido correctamente.';
    $_SESSION['pending_save'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Guardar edición
if ($action === 'edit' && isset($xml) && $xml instanceof SimpleXMLElement) {
    requireValidCsrf();
    $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
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
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Faltan campos obligatorios (nombre o descripción).']);
            exit;
        }
        $_SESSION['error'] = 'Faltan campos obligatorios (nombre o descripción).';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $n = min(count($romNames), count($sizes), count($crcs), count($md5s), count($sha1s));
    if ($n === 0) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'message' => 'Debes mantener al menos una ROM.']);
            exit;
        }
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
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => 'Faltan campos obligatorios en alguna ROM.']);
                exit;
            }
            $_SESSION['error'] = 'Faltan campos obligatorios en alguna ROM.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^\d+$/', $rsize)) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => 'Tamaño inválido en una ROM (entero en bytes).']);
                exit;
            }
            $_SESSION['error'] = 'Tamaño inválido en una ROM (entero en bytes).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9A-F]{8}$/', $rcrc)) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => 'CRC32 inválido en una ROM (8 hex).']);
                exit;
            }
            $_SESSION['error'] = 'CRC32 inválido en una ROM (8 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9a-f]{32}$/', $rmd5)) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => 'MD5 inválido en una ROM (32 hex).']);
                exit;
            }
            $_SESSION['error'] = 'MD5 inválido en una ROM (32 hex).';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!preg_match('/^[0-9a-f]{40}$/', $rsha1)) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => 'SHA1 inválido en una ROM (40 hex).']);
                exit;
            }
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
            EditorXml::crearBackup($xmlFile);
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
            EditorXml::limpiarEspaciosEnBlancoDom($dom);
            if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
                registrarError('crud.php:edit', 'No se pudo guardar el XML tras editar. Revertido al respaldo.', [ 'file' => $xmlFile ]);
                if ($isAjax) {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode(['ok' => false, 'message' => 'No se pudo guardar el XML. Se revirtió al respaldo.']);
                    exit;
                }
                $_SESSION['error'] = 'No se pudo guardar el XML. Se revirtió al respaldo.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'ok' => true,
                    'node_type' => $nodeType,
                    'index' => $index,
                    'name' => $newName,
                    'description' => $newDesc,
                    'category' => $nodeType === 'game' ? $newCat : null,
                    'roms' => array_map(function($r){ return ['name'=>$r[0], 'size'=>$r[1], 'crc'=>$r[2], 'md5'=>$r[3], 'sha1'=>$r[4]]; }, $roms),
                ]);
                exit;
            }
            $_SESSION['message'] = 'Entrada actualizada correctamente.';
        }
    }
    if (!$isAjax) {
        header('Location: ' . $_SERVER['PHP_SELF']);
    } else {
        registrarAdvertencia('crud.php:edit', 'No se pudo localizar el nodo a editar.', [ 'index' => $index, 'type' => $nodeType ]);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'message' => 'No se pudo localizar el nodo a editar.']);
    }
    exit;
}

// Eliminar entrada (game o machine)
if ($action === 'delete' && isset($xml) && $xml instanceof SimpleXMLElement) {
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
        EditorXml::crearBackup($xmlFile);
        $toRemove = $nodes->item($index);
        if ($toRemove instanceof DOMElement) {
            $deletedName = $toRemove->getAttribute('name');
            $toRemove->parentNode->removeChild($toRemove);
        }
        // Formateo limpio del XML al guardar
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->normalizeDocument();
        EditorXml::limpiarEspaciosEnBlancoDom($dom);
        if (!EditorXml::guardarDomConBackup($dom, $xmlFile)) {
            registrarError('crud.php:delete', 'No se pudo guardar el XML tras eliminar. Revertido al respaldo.', [ 'file' => $xmlFile ]);
            $_SESSION['error'] = 'No se pudo guardar el XML tras eliminar. Se revirtió al respaldo.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $extra = [ 'node_type' => $nodeType, 'index' => $index ];
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            $extra['name'] = isset($deletedName) ? $deletedName : null;
        }
        registrarInfo('crud.php:delete', 'Elemento eliminado', $extra);
        $_SESSION['message'] = ($nodeType === 'machine') ? 'Máquina eliminada correctamente.' : 'Juego eliminado correctamente.';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Eliminar archivo XML actual
if ($action === 'remove_xml') {
    requireValidCsrf();
    if (file_exists($xmlFile)) {
        if (!@unlink($xmlFile)) {
            registrarError('crud.php:remove_xml', 'No se pudo eliminar el archivo XML actual.', [ 'file' => $xmlFile ]);
            $_SESSION['error'] = 'No se pudo eliminar el archivo XML.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    unset($_SESSION['xml_uploaded']);
    $_SESSION['message'] = 'Archivo eliminado correctamente.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
