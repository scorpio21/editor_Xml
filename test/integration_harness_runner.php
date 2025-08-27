<?php
declare(strict_types=1);

// Runner de integración: ejecuta una acción contra el enrutador usando un XML en ruta temporal
// Uso (ejemplos):
//   set ACTION=compact_xml& set XML_PATH=C:\\Temp\\current.xml & D:\\xampp\\php\\php.exe test\\integration_harness_runner.php
//   $env:ACTION="download_xml"; $env:XML_PATH="C:\\Temp\\current.xml"; D:\\xampp\\php\\php.exe test\\integration_harness_runner.php > downloaded.xml

// Preparar sesión
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$action = getenv('ACTION') ?: '';
$xmlPath = getenv('XML_PATH') ?: '';
$extraJson = getenv('EXTRA_JSON') ?: '';
if ($action === '' || $xmlPath === '') {
    fwrite(STDERR, "Faltan variables de entorno ACTION y/o XML_PATH\n");
    exit(2);
}

// Preparar entorno mínimo HTTP simulado
$_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] ?? '/index.php';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/inc/acciones.php';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Asegurar carpeta para el XML temporal
$dir = dirname($xmlPath);
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }

// Señalar que hay XML cargado cuando corresponda
$_SESSION['xml_uploaded'] = file_exists($xmlPath);

// Generar token CSRF
require_once __DIR__ . '/../inc/csrf-helper.php';
$token = generarTokenCSRF();

// Construir POST base
$_POST = [
    'action' => $action,
    'csrf_token' => $token,
];

// Mezclar EXTRA_JSON si se proporciona (por ejemplo, campos para add/edit/create)
if ($extraJson !== '') {
    $decoded = json_decode($extraJson, true);
    if (is_array($decoded)) {
        foreach ($decoded as $k => $v) { $_POST[$k] = $v; }
    }
}

// Valores por defecto para create_xml si no se proporcionaron
if ($action === 'create_xml') {
    $_POST += [
        'name' => 'Datafile de Prueba',
        'description' => 'Generado por integración',
        'version' => '1.0',
        'date' => date('Y-m-d'),
        'author' => 'editor_Xml',
        'homepage' => '',
        'url' => '',
    ];
}

// Inyectar $xmlFile para que el router use la ruta temporal
$xmlFile = $xmlPath;

// Para acciones que requieren XML previo, crear uno básico si no existe
if (!file_exists($xmlFile) && in_array($action, ['compact_xml','download_xml','add_game','edit','delete','bulk_count','bulk_delete','dedupe_region','dedupe_region_count','dedupe_region_export_csv'], true)) {
    file_put_contents($xmlFile, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<datafile><header><name>Test</name></header></datafile>\n");
    $_SESSION['xml_uploaded'] = true;
}

// Ejecutar router (esto puede terminar con header()+exit)
require_once __DIR__ . '/../inc/router-acciones.php';

// Si la acción no hizo exit, salir con código 0
exit(0);
