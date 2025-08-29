<?php
declare(strict_types=1);

// Pruebas de seguridad XXE para cargas XML
// Ejecutar con: php test/xxe_security_test.php

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

function crearArchivoTemporal(string $contenido): string {
    $tmp = tempnam(sys_get_temp_dir(), 'xmlxxe_');
    file_put_contents($tmp, $contenido);
    return $tmp;
}

// XML malicioso con entidad externa que intenta acceder a red
$malicious = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE datafile [
  <!ELEMENT datafile ANY>
  <!ENTITY xxe SYSTEM "http://127.0.0.1:9/should-not-fetch">  
]>
<datafile><header><name>&xxe;</name></header></datafile>
XML;

// 1) Verificar que cargarXmlSiDisponible() sanea DOCTYPE, carga con éxito y persiste sin DOCTYPE
$_SESSION['xml_uploaded'] = true;
$path = crearArchivoTemporal($malicious);
$xml = cargarXmlSiDisponible($path);
assertTrue($xml instanceof SimpleXMLElement, 'cargarXmlSiDisponible() sanea DOCTYPE y devuelve SimpleXMLElement');
// El archivo en disco debe haber sido persistido sin DOCTYPE
$persisted = @file_get_contents($path) ?: '';
assertTrue(stripos($persisted, '<!DOCTYPE') === false, 'El archivo persistido no contiene DOCTYPE tras saneado');

// 2) Verificar que DOMDocument con banderas seguras no resuelve entidad externa (sin acceso a red)
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->resolveExternals = false;
$dom->substituteEntities = false;
$dom->validateOnParse = false;
$loaded = @$dom->loadXML($malicious, LIBXML_NONET);
assertTrue($loaded === true, 'DOMDocument::loadXML() carga, pero no debe resolver entidades externas');
// Extraer el texto de header/name y comprobar que la entidad no se expandió (queda vacío o literal sin resolución)
$xp = new DOMXPath($dom);
$n = $xp->query('/datafile/header/name')->item(0);
$text = $n ? (string)$n->textContent : '';
assertTrue($text === '' || strpos($text, '&xxe;') !== false, 'Entidad externa no expandida (sin acceso a red)');

// Resultado
echo "\nResultados: OK={$tests['ok']} FAIL={$tests['fail']}\n";
exit($tests['fail'] > 0 ? 1 : 0);
