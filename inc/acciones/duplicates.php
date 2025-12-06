<?php
declare(strict_types=1);

// Módulo: detección y gestión de duplicados
// Requisitos previos: require de common.php, xml-helpers.php y variables $xmlFile, $xml

/**
 * Normaliza el nombre de un juego/máquina para detectar duplicados.
 * Extrae el nombre base eliminando región, idiomas, revisiones, etc.
 * 
 * @param string $nombreCompleto Nombre completo del juego
 * @return array ['base' => nombre legible, 'key' => clave normalizada, 'region' => ..., 'languages' => [...], 'revision' => ...]
 */
function normalizarNombreParaDuplicados(string $nombreCompleto): array
{
    $original = trim($nombreCompleto);
    $trabajando = $original;

    // Extraer región
    $region = null;
    if (preg_match('/\((?:Europe|USA|Japan|World|France|Germany|Spain|Italy|Australia|Asia|Brazil|Korea|China|Canada|Netherlands|Sweden|Denmark|Norway|Finland|Russia|Poland|UK|Ireland|Scotland|Wales)\)/iu', $trabajando, $m)) {
        $region = trim($m[0], '()');
    }

    // Extraer idiomas (formato: (En,Fr,De,Es) o (En) o similar)
    $languages = [];
    if (preg_match('/\(([A-Z][a-z](?:,[A-Z][a-z])*)\)/u', $trabajando, $m)) {
        $languages = array_map('trim', explode(',', $m[1]));
    }

    // Extraer revisión (Rev 1, Rev 2, etc.)
    $revision = null;
    if (preg_match('/\(Rev\s*(\d+)\)/iu', $trabajando, $m)) {
        $revision = (int) $m[1];
    }

    // Eliminar todo lo que está entre paréntesis al final
    $nombreBase = preg_replace('/\s*\([^)]*\)\s*$/u', '', $trabajando);
    while (preg_match('/\s*\([^)]*\)\s*$/u', $nombreBase)) {
        $nombreBase = preg_replace('/\s*\([^)]*\)\s*$/u', '', $nombreBase);
    }
    $nombreBase = trim($nombreBase);

    // Generar clave normalizada (sin espacios, sin puntuación, mayúsculas)
    $clave = strtoupper($nombreBase);
    $clave = preg_replace('/[^A-Z0-9]/u', '', $clave);

    return [
        'base' => $nombreBase,
        'key' => $clave,
        'region' => $region,
        'languages' => $languages,
        'revision' => $revision
    ];
}

/**
 * Detecta grupos de duplicados en el XML cargado.
 * Agrupa por nombre base normalizado.
 * 
 * @param SimpleXMLElement $xml XML cargado
 * @return array Array de grupos de duplicados
 */
function detectarGruposDuplicados(SimpleXMLElement $xml): array
{
    $children = $xml->xpath('/datafile/*[self::game or self::machine]') ?: [];

    $grupos = [];
    $index = 0;

    foreach ($children as $node) {
        $type = $node->getName() === 'machine' ? 'machine' : 'game';
        $nombreCompleto = (string) ($node['name'] ?? '');

        if ($nombreCompleto === '') {
            $index++;
            continue;
        }

        $info = normalizarNombreParaDuplicados($nombreCompleto);
        $key = $info['key'];

        if (!isset($grupos[$key])) {
            $grupos[$key] = [
                'base' => $info['base'],
                'key' => $key,
                'entries' => []
            ];
        }

        $grupos[$key]['entries'][] = [
            'index' => $index,
            'type' => $type,
            'name' => $nombreCompleto,
            'region' => $info['region'],
            'languages' => $info['languages'],
            'revision' => $info['revision'],
            'description' => (string) ($node->description ?? ''),
            'category' => $type === 'game' ? (string) ($node->category ?? '') : null
        ];

        $index++;
    }

    // Filtrar solo grupos con más de una entrada (duplicados reales)
    $duplicados = array_values(array_filter($grupos, static function ($grupo) {
        return count($grupo['entries']) > 1;
    }));

    return $duplicados;
}

// Acción: detectar duplicados
if ($action === 'detect_duplicates') {
    requireValidCsrf();

    if (!isset($xml) || !($xml instanceof SimpleXMLElement)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => false, 'message' => 'No hay XML cargado.']);
        exit;
    }

    $grupos = detectarGruposDuplicados($xml);

    // Respuesta AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => true,
            'count' => count($grupos),
            'groups' => $grupos
        ]);
        exit;
    }

    $_SESSION['message'] = sprintf('Se encontraron %d grupos de duplicados.', count($grupos));
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Acción: exportar duplicados a CSV
if ($action === 'export_duplicates_csv') {
    requireValidCsrf();

    if (!isset($xml) || !($xml instanceof SimpleXMLElement)) {
        $_SESSION['error'] = 'No hay XML cargado.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $grupos = detectarGruposDuplicados($xml);

    if (count($grupos) === 0) {
        $_SESSION['message'] = 'No se encontraron duplicados para exportar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $base = 'duplicados';
    if (isset($_SESSION['original_filename'])) {
        $origNoExt = preg_replace('/\.[^.]+$/', '', (string) $_SESSION['original_filename']) ?? 'current';
        $base = preg_replace('/[\\\\\\/:*?"<>|]/', '_', $origNoExt);
    }
    $dateStr = date('Ymd_His');
    $filename = sprintf('%s_duplicados_%s.csv', $base, $dateStr);

    // Limpiar buffer de salida
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    // BOM UTF-8 para Excel
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');

    // Cabecera
    fputcsv($out, ['grupo', 'nombre_completo', 'region', 'idiomas', 'revision', 'tipo', 'descripcion']);

    foreach ($grupos as $grupo) {
        $grupoNombre = $grupo['base'];
        foreach ($grupo['entries'] as $entry) {
            fputcsv($out, [
                $grupoNombre,
                $entry['name'],
                $entry['region'] ?? '',
                implode(',', $entry['languages']),
                $entry['revision'] !== null ? 'Rev ' . $entry['revision'] : '',
                $entry['type'],
                $entry['description']
            ]);
        }
    }

    fclose($out);
    exit;
}

// Acción: generar XML sin duplicados seleccionados
if ($action === 'export_xml_without_duplicates') {
    requireValidCsrf();

    if (!isset($xml) || !($xml instanceof SimpleXMLElement)) {
        $_SESSION['error'] = 'No hay XML cargado.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Recibir índices a eliminar desde POST
    $toDelete = isset($_POST['delete_indices']) && is_array($_POST['delete_indices'])
        ? array_map('intval', $_POST['delete_indices'])
        : [];

    if (count($toDelete) === 0) {
        $_SESSION['error'] = 'No se seleccionó ningún duplicado para eliminar.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Cargar todas las entradas
    $children = $xml->xpath('/datafile/*[self::game or self::machine]') ?: [];

    // Construir DOM de salida
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $datafile = $dom->createElement('datafile');

    // Primero contar cuántos se mantendrán para actualizar la cabecera
    $kept = 0;
    foreach ($children as $idx => $node) {
        if (!in_array($idx, $toDelete, true)) {
            $kept++;
        }
    }

    // Copiar y actualizar cabecera
    $newDateStr = date('Y-m-d H-i-s');
    $newVersionDate = date('Y-m-d H:i:s');

    // Intentar extraer el nombre base del archivo original (ej: "Microsoft - Xbox")
    // Se asume formato: "Nombre Base - Datfile (Num) (Date).xml" o similar
    $nombreBaseArchivo = 'Datfile';
    if (isset($_SESSION['original_filename'])) {
        $origName = (string) $_SESSION['original_filename'];
        // Buscar patrón " - Datfile ("
        $pos = strpos($origName, ' - Datfile (');
        if ($pos !== false) {
            $nombreBaseArchivo = substr($origName, 0, $pos);
        } else {
            // Si no sigue el patrón exacto, intentamos limpiar la extensión y lo que parezca fecha/número al final
            $nombreBaseArchivo = preg_replace('/\s-\sDatfile.*$/', '', $origName);
            $nombreBaseArchivo = preg_replace('/\s\(\d+\).*$/', '', $nombreBaseArchivo);
            $nombreBaseArchivo = str_replace('.xml', '', $nombreBaseArchivo);
        }
    }

    $newFilename = sprintf('%s - Datfile (%d) (%s).xml', $nombreBaseArchivo, $kept, $newDateStr);

    if (isset($xml->header)) {
        $header = $dom->createElement('header');

        // Name
        if (isset($xml->header->name)) {
            $safeVal = htmlspecialchars((string) $xml->header->name, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $header->appendChild($dom->createElement('name', $safeVal));
        }

        // Description: debe coincidir con el nombre nuevo del archivo
        $header->appendChild($dom->createElement('description', $newFilename));

        // Categoría (si existía)
        if (isset($xml->header->category)) {
            $safeVal = htmlspecialchars((string) $xml->header->category, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $header->appendChild($dom->createElement('category', $safeVal));
        }

        // Version y Date
        $header->appendChild($dom->createElement('version', $newVersionDate));
        $header->appendChild($dom->createElement('date', $newVersionDate));

        // Copiar author, homepage, url, comment, clrmamepro, romcenter
        foreach (['author', 'homepage', 'url', 'comment', 'clrmamepro', 'romcenter'] as $f) {
            if (isset($xml->header->{$f}) && (string) $xml->header->{$f} !== '') {
                $safeVal = htmlspecialchars((string) $xml->header->{$f}, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $header->appendChild($dom->createElement($f, $safeVal));
            }
        }

        $datafile->appendChild($header);
    }

    // Añadir entradas que NO están en la lista de eliminación
    $kept = 0; // Reiniciar contador para el bucle (aunque ya sabemos cuantos son)
    foreach ($children as $idx => $node) {
        if (in_array($idx, $toDelete, true)) {
            continue; // Saltar los marcados para eliminar
        }

        $type = $node->getName() === 'machine' ? 'machine' : 'game';
        $gameNode = $dom->createElement($type);
        $gameNode->setAttribute('name', (string) ($node['name'] ?? ''));

        // Atributos extra de game/machine
        foreach (['sourcefile', 'cloneof', 'romof', 'sampleof', 'isbios'] as $attr) {
            if (isset($node[$attr])) {
                $gameNode->setAttribute($attr, (string) $node[$attr]);
            }
        }

        if ($type === 'game') {
            if (isset($node->description)) {
                $safeDesc = htmlspecialchars((string) $node->description, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $gameNode->appendChild($dom->createElement('description', $safeDesc));
            }
            if (isset($node->category) && (string) $node->category !== '') {
                $safeCat = htmlspecialchars((string) $node->category, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $gameNode->appendChild($dom->createElement('category', $safeCat));
            }
            // Otros campos comunes
            foreach (['year', 'manufacturer', 'publisher', 'genre'] as $tag) {
                if (isset($node->{$tag})) {
                    $safeVal = htmlspecialchars((string) $node->{$tag}, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                    $gameNode->appendChild($dom->createElement($tag, $safeVal));
                }
            }
        } else {
            // Lógica para 'machine'
            if (isset($node->description)) {
                $safeDesc = htmlspecialchars((string) $node->description, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $gameNode->appendChild($dom->createElement('description', $safeDesc));
            }
            if (isset($node->year)) {
                $safeYear = htmlspecialchars((string) $node->year, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $gameNode->appendChild($dom->createElement('year', $safeYear));
            }
            if (isset($node->manufacturer)) {
                $safeMan = htmlspecialchars((string) $node->manufacturer, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $gameNode->appendChild($dom->createElement('manufacturer', $safeMan));
            }
        }

        // Copiar ROMs, Disks, Samples
        if (isset($node->rom)) {
            foreach ($node->rom as $rom) {
                $romEl = $dom->createElement('rom');
                foreach ($rom->attributes() as $k => $v) {
                    $romEl->setAttribute($k, (string) $v);
                }
                $gameNode->appendChild($romEl);
            }
        }
        // Copiar Disk
        if (isset($node->disk)) {
            foreach ($node->disk as $disk) {
                $diskEl = $dom->createElement('disk');
                foreach ($disk->attributes() as $k => $v) {
                    $diskEl->setAttribute($k, (string) $v);
                }
                $gameNode->appendChild($diskEl);
            }
        }

        $datafile->appendChild($gameNode);
        $kept++;
    }

    $dom->appendChild($datafile);
    $dom->normalizeDocument();
    EditorXml::limpiarEspaciosEnBlancoDom($dom);

    // Nombre de archivo final (ya calculado arriba)
    $filename = $newFilename;

    // Limpiar buffer de salida
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/xml; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo $dom->saveXML();
    exit;
}
