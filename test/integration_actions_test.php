<?php
declare(strict_types=1);

// Prueba de integración E2E usando el harness integration_harness_runner.php
// Flujo: create_xml -> add_game -> compact_xml -> download_xml

$php = 'D:\\xampp\\php\\php.exe';
$project = realpath(__DIR__ . '/..');
$harness = $project . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'integration_harness_runner.php';
$baseDir = $project . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'itests_e2e';
$xmlPath = $baseDir . DIRECTORY_SEPARATOR . 'current.xml';
$downloadPath = $baseDir . DIRECTORY_SEPARATOR . 'downloaded.xml';

@mkdir($baseDir, 0777, true);
@unlink($xmlPath);
@unlink($xmlPath . '.bak');
@unlink($downloadPath);

function run(string $php, string $script, array $env = [], ?string $stdoutFile = null): int {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => $stdoutFile ? ['file', $stdoutFile, 'w'] : ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $cmd = sprintf('"%s" %s', $php, escapeshellarg($script));
    // Nota: pasar solo $env. Mezclar $_ENV/$_SERVER puede introducir valores no escalares y provocar warnings.
    $proc = proc_open($cmd, $descriptors, $pipes, dirname($script), $env);
    if (!is_resource($proc)) { throw new RuntimeException('No se pudo iniciar proceso'); }
    fclose($pipes[0]);
    if (!$stdoutFile) { stream_get_contents($pipes[1]); fclose($pipes[1]); }
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($proc);
    if ($code !== 0 && $err !== '') {
        fwrite(STDERR, "ERR: $err\n");
    }
    return (int)$code;
}

function assertTrue(bool $cond, string $msg): void { if (!$cond) { throw new RuntimeException($msg); } }

// 1) create_xml
$env = [
    'ACTION' => 'create_xml',
    'XML_PATH' => $xmlPath,
];
$code = run($php, $harness, $env);
assertTrue($code === 0, 'create_xml falló');
assertTrue(file_exists($xmlPath), 'No se creó el XML');

// 2) add_game
$extra = json_encode([
    'game_name' => 'Pac-Man',
    'description' => 'Arcade clásico',
    'category' => 'Arcade',
    'rom_name' => ['pacman.rom'],
    'size' => ['16384'],
    'crc' => ['0123ABCD'],
    'md5' => ['0123456789abcdef0123456789abcdef'],
    'sha1' => ['0123456789abcdef0123456789abcdef01234567'],
], JSON_UNESCAPED_UNICODE);
$env = [
    'ACTION' => 'add_game',
    'XML_PATH' => $xmlPath,
    'EXTRA_JSON' => $extra,
];
$code = run($php, $harness, $env);
assertTrue($code === 0, 'add_game falló');

// 3) compact_xml
$env = [
    'ACTION' => 'compact_xml',
    'XML_PATH' => $xmlPath,
];
$code = run($php, $harness, $env);
assertTrue($code === 0, 'compact_xml falló');
assertTrue(file_exists($xmlPath . '.bak'), 'No se generó .bak');

// 4) download_xml -> a fichero
$env = [
    'ACTION' => 'download_xml',
    'XML_PATH' => $xmlPath,
];
$code = run($php, $harness, $env, $downloadPath);
assertTrue($code === 0, 'download_xml falló');
assertTrue(file_exists($downloadPath), 'No se generó downloaded.xml');

// Validar contenido descargado
$xml = @simplexml_load_file($downloadPath);
assertTrue($xml instanceof SimpleXMLElement, 'XML descargado inválido');
$games = $xml->xpath('/datafile/game');
$machines = $xml->xpath('/datafile/machine');
$count = (is_array($games) ? count($games) : 0) + (is_array($machines) ? count($machines) : 0);
assertTrue($count >= 1, 'El XML no contiene entradas de juego/máquina');

echo "OK: integración E2E completada.\n";
