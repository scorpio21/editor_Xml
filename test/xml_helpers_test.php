<?php
declare(strict_types=1);

// Pruebas mínimas para inc/xml-helpers.php
// Ejecutar con: php test/xml_helpers_test.php

// Asegurar carga de helpers
require_once __DIR__ . '/../inc/xml-helpers.php';

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

// Utilidades para archivos temporales
function crearArchivoTemporal(string $contenido): string {
    $tmp = tempnam(sys_get_temp_dir(), 'xmltest_');
    file_put_contents($tmp, $contenido);
    return $tmp;
}

function crearDirectorioTemporal(): string {
    $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xmltest_dir_' . bin2hex(random_bytes(4));
    mkdir($base, 0777, true);
    return $base;
}

// --- Prueba: cargarXmlSiDisponible con XML válido ---
$_SESSION['xml_uploaded'] = true;
$xmlValido = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><datafile><header><name>Test</name></header></datafile>";
$pathValido = crearArchivoTemporal($xmlValido);
$xml = cargarXmlSiDisponible($pathValido);
assertTrue($xml instanceof SimpleXMLElement, 'cargarXmlSiDisponible() devuelve SimpleXMLElement con XML válido');

// --- Prueba: cargarXmlSiDisponible con XML inválido ---
$_SESSION['xml_uploaded'] = true;
$xmlInvalido = "<datafile><header><name>Sin cerrar"; // faltan etiquetas de cierre
$pathInvalido = crearArchivoTemporal($xmlInvalido);
$xml2 = cargarXmlSiDisponible($pathInvalido);
assertEquals($xml2, null, 'cargarXmlSiDisponible() devuelve null con XML inválido');
assertTrue(!isset($_SESSION['xml_uploaded']), 'cargarXmlSiDisponible() desmarca xml_uploaded tras error');
assertTrue(isset($_SESSION['error']) && is_string($_SESSION['error']), 'cargarXmlSiDisponible() establece mensaje de error en sesión');
unset($_SESSION['error']);

// --- Prueba: guardarDomConBackup crea .bak y guarda ---
$dir = crearDirectorioTemporal();
$dest = $dir . DIRECTORY_SEPARATOR . 'current.xml';
// Escribir un archivo inicial para forzar la creación de backup
file_put_contents($dest, "<root/>\n");
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;
$root = $dom->createElement('root');
$dom->appendChild($root);
$res = guardarDomConBackup($dom, $dest);
assertTrue($res === true, 'guardarDomConBackup() devuelve true en guardado correcto');
assertTrue(file_exists($dest . '.bak'), 'guardarDomConBackup() crea archivo .bak cuando existía current.xml');

// Resultado
global $tests;
echo "\nResultados: OK={$tests['ok']} FAIL={$tests['fail']}\n";
exit($tests['fail'] > 0 ? 1 : 0);
