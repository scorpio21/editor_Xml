<?php
declare(strict_types=1);

// Internacionalización básica (es/en) para textos de la UI

/**
 * Inicializa el idioma desde ?lang=es|en y lo guarda en sesión.
 */
function i18n_init(): void {
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        if ($lang === 'es' || $lang === 'en') {
            $_SESSION['lang'] = $lang;
        }
    }
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'es';
    }
}

/**
 * Devuelve el idioma activo (es|en)
 */
function lang(): string {
    return $_SESSION['lang'] ?? 'es';
}

/**
 * Traduce una clave a partir del idioma activo.
 */
function t(string $key): string {
    static $I18N = null;
    if ($I18N === null) {
        $I18N = [
            'es' => [
                'app.title' => 'Editor XML Juegos',
                'header.title' => 'Editor de Catálogo de Juegos XML',
                'lang.label' => 'Idioma',
                'lang.es' => 'Español',
                'lang.en' => 'Inglés',
                'lang.change' => 'Cambiar',

                'tabs.welcome' => 'Bienvenida',
                'tabs.upload_search' => 'Cargar y buscar',
                'tabs.bulk_delete' => 'Eliminación masiva',
                'tabs.mame_search' => 'MAME (buscar)',
                'tabs.dedupe_region' => 'Eliminar duplicados',
                'tabs.search_external' => 'Buscar juego',

                'welcome.h2' => 'Bienvenido al editor de XML/DAT',
                'welcome.p1' => 'Esta herramienta te permite cargar, explorar, editar y mantener tu catálogo XML/DAT de juegos y máquinas.',
                'welcome.p2' => 'Usa las pestañas para navegar entre secciones. Puedes abrir la ayuda en cualquier momento.',
                'welcome.help_btn' => 'Ayuda',

                'hint.load_first' => 'Primero carga un fichero XML/DAT en la pestaña "Cargar y buscar".',
                'hint.load_first_tool' => 'Primero carga un fichero XML/DAT en la pestaña "Cargar y buscar" para usar esta herramienta.',

                'mame.h3' => 'Búsqueda en ficheros MAME',
                'mame.hint' => 'Para ficheros MAME, esta sección permite buscar máquinas por nombre, ROM o hash. La eliminación masiva está deshabilitada.',
                'mame.search_label' => 'Buscar',
                'mame.input_placeholder' => 'Nombre de máquina, ROM o hash',
                'mame.chk_roms' => 'Buscar en ROMs',
                'mame.chk_hashes' => 'Buscar en hashes (CRC/MD5/SHA1)',
                'mame.submit' => 'Buscar',
                'mame.results_hint' => 'Los resultados se muestran en la lista principal bajo el buscador.',

                'search_external.h3' => 'Buscar juego en webs externas',
                'search_external.hint' => 'Busca juegos en webs externas por nombre o por hash (MD5/SHA1/CRC). Se abrirán enlaces de búsqueda en nuevas pestañas.',
                'search_external.legend' => 'Datos de búsqueda',
                'search_external.name_label' => 'Nombre del juego',
                'search_external.name_placeholder' => 'Ej.: Super Mario World',
                'search_external.md5_label' => 'MD5',
                'search_external.md5_placeholder' => '32 hex (opcional)',
                'search_external.sha1_label' => 'SHA1',
                'search_external.sha1_placeholder' => '40 hex (opcional)',
                'search_external.crc_label' => 'CRC',
                'search_external.crc_placeholder' => '8 hex (opcional)',
                'search_external.btn_build' => 'Generar enlaces',
                'search_external.btn_open_all' => 'Abrir todas',
                'search_external.btn_check_archive' => 'Comprobar Archive',
                'search_external.links_title' => 'Enlaces de búsqueda',
                'search_external.links_hint' => 'Consejo: algunos sitios no soportan búsqueda por hash directa; por eso usamos búsquedas "site:".',
                'search_external.archive_title' => 'Archive.org',
            ],
            'en' => [
                'app.title' => 'XML Games Editor',
                'header.title' => 'XML Games Catalog Editor',
                'lang.label' => 'Language',
                'lang.es' => 'Spanish',
                'lang.en' => 'English',
                'lang.change' => 'Change',

                'tabs.welcome' => 'Welcome',
                'tabs.upload_search' => 'Load and search',
                'tabs.bulk_delete' => 'Bulk delete',
                'tabs.mame_search' => 'MAME (search)',
                'tabs.dedupe_region' => 'Remove duplicates',
                'tabs.search_external' => 'Search game',

                'welcome.h2' => 'Welcome to the XML/DAT editor',
                'welcome.p1' => 'This tool lets you load, explore, edit and maintain your XML/DAT catalog of games and machines.',
                'welcome.p2' => 'Use the tabs to navigate across sections. You can open the help at any time.',
                'welcome.help_btn' => 'Help',

                'hint.load_first' => 'First load an XML/DAT file in the "Load and search" tab.',
                'hint.load_first_tool' => 'First load an XML/DAT file in the "Load and search" tab to use this tool.',

                'mame.h3' => 'Search in MAME files',
                'mame.hint' => 'For MAME files, this section allows searching machines by name, ROM or hash. Bulk delete is disabled.',
                'mame.search_label' => 'Search',
                'mame.input_placeholder' => 'Machine name, ROM or hash',
                'mame.chk_roms' => 'Search in ROMs',
                'mame.chk_hashes' => 'Search in hashes (CRC/MD5/SHA1)',
                'mame.submit' => 'Search',
                'mame.results_hint' => 'Results are shown in the main list below the search box.',

                'search_external.h3' => 'Search game on external websites',
                'search_external.hint' => 'Search games on external websites by name or by hash (MD5/SHA1/CRC). Links will open in new tabs.',
                'search_external.legend' => 'Search data',
                'search_external.name_label' => 'Game name',
                'search_external.name_placeholder' => 'E.g.: Super Mario World',
                'search_external.md5_label' => 'MD5',
                'search_external.md5_placeholder' => '32 hex (optional)',
                'search_external.sha1_label' => 'SHA1',
                'search_external.sha1_placeholder' => '40 hex (optional)',
                'search_external.crc_label' => 'CRC',
                'search_external.crc_placeholder' => '8 hex (optional)',
                'search_external.btn_build' => 'Generate links',
                'search_external.btn_open_all' => 'Open all',
                'search_external.btn_check_archive' => 'Check Archive',
                'search_external.links_title' => 'Search links',
                'search_external.links_hint' => 'Tip: some sites don\'t support direct hash search; that\'s why we use "site:" searches.',
                'search_external.archive_title' => 'Archive.org',
            ],
        ];
    }
    return $I18N[lang()][$key] ?? $key;
}
