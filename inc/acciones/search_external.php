<?php
declare(strict_types=1);

// Acción: search_archive (AJAX)
// Devuelve JSON con el primer resultado de Archive.org o "No encontrado".

require_once __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'search_archive') {
        // Exigir CSRF válido
        requireValidCsrf();

        header('Content-Type: application/json; charset=utf-8');

        // Entradas
        $name = trim((string)($_POST['name'] ?? ''));
        $md5  = trim((string)($_POST['md5'] ?? ''));
        $sha1 = trim((string)($_POST['sha1'] ?? ''));
        $crc  = trim((string)($_POST['crc'] ?? ''));

        // Construir query (simple, sin campos específicos si no están soportados)
        $terms = [];
        if ($name !== '') { $terms[] = $name; }
        if ($md5  !== '') { $terms[] = $md5;  }
        if ($sha1 !== '') { $terms[] = $sha1; }
        if ($crc  !== '') { $terms[] = $crc;  }

        if (empty($terms)) {
            echo json_encode(['ok' => false, 'message' => 'Introduce al menos un dato para buscar.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Usamos advancedsearch con una consulta OR entre términos de texto
        // Documentación: https://archive.org/advancedsearch.php
        // Nota: algunos campos hash pueden no estar indexados como tales; el objetivo es localizar por texto general.
        $q = implode(' OR ', array_map(static function(string $t){
            // Escapar comillas dobles y envolver en comillas para buscar literal
            $t = str_replace('"', '"', $t);
            return '"' . $t . '"';
        }, $terms));

        $params = [
            'q' => $q,
            'fl[]' => ['identifier', 'title'],
            'sort[]' => ['downloads desc'],
            'rows' => 1,
            'page' => 1,
            'output' => 'json'
        ];

        // Construir URL
        $base = 'https://archive.org/advancedsearch.php';
        // Como fl[] y sort[] son arrays, construimos la query manualmente
        $query = http_build_query([
            'q' => $params['q'], 'rows' => $params['rows'], 'page' => $params['page'], 'output' => 'json'
        ], '', '&', PHP_QUERY_RFC3986);
        // Añadir múltiples fl[] y sort[]
        foreach ($params['fl[]'] as $fl) { $query .= '&' . rawurlencode('fl[]') . '=' . rawurlencode($fl); }
        foreach ($params['sort[]'] as $so) { $query .= '&' . rawurlencode('sort[]') . '=' . rawurlencode($so); }
        $url = $base . '?' . $query;

        // Realizar petición con timeout
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: editor_Xml/1.0 (+https://github.com/scorpio21/editor_Xml)'
                ],
                'timeout' => 6
            ]
        ]);

        try {
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp === false) {
                echo json_encode(['ok' => false, 'message' => 'No se pudo consultar Archive.org.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $data = json_decode($resp, true);
            if (!is_array($data) || !isset($data['response']['docs']) || !is_array($data['response']['docs'])) {
                echo json_encode(['ok' => false, 'message' => 'Respuesta no válida de Archive.org.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $docs = $data['response']['docs'];
            if (count($docs) < 1) {
                echo json_encode(['ok' => true, 'found' => false, 'message' => 'No encontrado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $doc = $docs[0];
            $identifier = (string)($doc['identifier'] ?? '');
            $title = (string)($doc['title'] ?? $identifier);
            if ($identifier === '') {
                echo json_encode(['ok' => true, 'found' => false, 'message' => 'No encontrado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $link = 'https://archive.org/details/' . rawurlencode($identifier);
            echo json_encode([
                'ok' => true,
                'found' => true,
                'identifier' => $identifier,
                'title' => $title,
                'link' => $link,
                'message' => 'Encontrado'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            registrarError('acciones:search_archive', 'Excepción consultando Archive', [
                'error' => $e->getMessage()
            ]);
            echo json_encode(['ok' => false, 'message' => 'Error consultando Archive.org.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
