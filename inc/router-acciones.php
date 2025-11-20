<?php
declare(strict_types=1);

// Enrutador de acciones POST. Incluye módulos especializados.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Asegurar rutas y XML
$root = __DIR__ . '/..';
if (!isset($xmlFile)) {
    $xmlFile = $root . '/uploads/current.xml';
}
require_once $root . '/inc/xml-helpers.php';
require_once __DIR__ . '/EditorXml.php';
EditorXml::asegurarCarpetaUploads($root . '/uploads');

// Determinar acciones migradas
$handledActions = [
    // bulk
    'bulk_count',
    'bulk_delete',
    // dedupe
    'dedupe_region_count',
    'dedupe_region',
    'dedupe_region_export_csv',
    // crud/utilitarias
    'compact_xml',
    'download_xml',
    'export_region_xml',
    'export_region_csv',
    'create_xml',
    'reset_filters',
    'restore_backup',
    'add_game',
    'edit',
    'delete',
    'remove_xml',
];

// Ya no es necesario fallback para subida: lo maneja crud.php con CSRF

// Si hay action y NO es de las migradas, de momento no hacemos nada aquí
// (cuando se retiren completamente las acciones legacy, este bloque podrá eliminarse)

// A partir de aquí, solo para acciones migradas o sin acción: cargar comunes y módulos
require_once __DIR__ . '/acciones/common.php';

// Cargar XML si está disponible para los módulos que lo necesitan
$xml = EditorXml::cargarXmlSiDisponible($xmlFile);

// Incluir módulos de acciones migradas. Cada módulo verifica $_POST['action'] y hace exit cuando corresponde.
require_once __DIR__ . '/acciones/bulk.php';
require_once __DIR__ . '/acciones/dedupe.php';
require_once __DIR__ . '/acciones/crud.php';
require_once __DIR__ . '/acciones/search_external.php';
require_once __DIR__ . '/acciones/category.php';

// Si ninguna acción coincidió, el flujo continúa y la página puede renderizar normalmente.
