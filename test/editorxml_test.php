<?php
declare(strict_types=1);

// Pruebas mínimas para inc/EditorXml.php
// Ejecutar con: php test/editorxml_test.php

require_once __DIR__ . '/../inc/EditorXml.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$tests = [ 'ok' => 0, 'fail' => 0 ];

function assertTrue(bool $cond, string $msg) {
    global $tests;
    if ($cond) { echo "[OK] $msg\n"; $tests['ok']++; }
    else { echo "[FAIL] $msg\n"; $tests['fail']++; }
}

function assertEquals($a, $b, string $msg) {
    assertTrue($a === $b, $msg . " (esperado=" . var_export($b, true) . ", obtenido=" . var_export($a, true) . ")");
}

// Utils para archivos temporales
function crearArchivoTemporal(string $contenido, string $suffix = ''): string {
    $tmp = tempnam(sys_get_temp_dir(), 'xmltest_');
    if ($suffix !== '') {
        // Renombrar para poder controlar la extensión si se requiere
        $nuevo = $tmp . $suffix;
        @unlink($nuevo);
        rename($tmp, $nuevo);
        $tmp = $nuevo;
    }
    file_put_contents($tmp, $contenido);
    return $tmp;
}

function crearDirectorioTemporal(): string {
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xmltest_dir_' . bin2hex(random_bytes(4));
    mkdir($base, 0777, true);
    return $base;
}

// --- Prueba: crearBackup crea .bak y preserva contenido ---
$contenidoOriginal = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><root><n>1</n></root>\n";
$ruta = crearArchivoTemporal($contenidoOriginal, '.xml');
EditorXml::crearBackup($ruta);
assertTrue(file_exists($ruta . '.bak'), 'crearBackup() crea archivo .bak cuando existe el XML');
assertEquals(file_get_contents($ruta . '.bak'), $contenidoOriginal, 'crearBackup() copia el contenido original al .bak');

// --- Prueba: limpiarEspaciosEnBlancoDom elimina textos vacíos ---
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = true; // simular blancos existentes
$dom->formatOutput = false;
$xmlConBlancos = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root>\n   <a> x </a>\n   \n   <b/>\n</root>\n";
$dom->loadXML($xmlConBlancos);
EditorXml::limpiarEspaciosEnBlancoDom($dom);
$xpath = new DOMXPath($dom);
$emptyTextNodes = $xpath->query('//text()[normalize-space(.)=""]');
assertEquals($emptyTextNodes->length, 0, 'limpiarEspaciosEnBlancoDom() elimina nodos de texto vacíos');

// --- Prueba: guardarDomConBackup guarda y crea .bak ---
$dir = crearDirectorioTemporal();
$dest = $dir . DIRECTORY_SEPARATOR . 'current.xml';
file_put_contents($dest, "<root/>\n"); // Forzar creación de backup
$dom2 = new DOMDocument('1.0', 'UTF-8');
$dom2->formatOutput = true;
$root2 = $dom2->createElement('root');
$child = $dom2->createElement('child', 'valor');
$root2->appendChild($child);
$dom2->appendChild($root2);
$res = EditorXml::guardarDomConBackup($dom2, $dest);
assertTrue($res === true, 'guardarDomConBackup() devuelve true en guardado correcto');
assertTrue(file_exists($dest . '.bak'), 'guardarDomConBackup() crea archivo .bak cuando existía current.xml');
$contenidoGuardado = file_get_contents($dest);
assertTrue(strpos($contenidoGuardado, '<child>valor</child>') !== false, 'guardarDomConBackup() escribe el contenido esperado');

// Resultado
global $tests;
echo "\nResultados: OK={$tests['ok']} FAIL={$tests['fail']}\n";
exit($tests['fail'] > 0 ? 1 : 0);
