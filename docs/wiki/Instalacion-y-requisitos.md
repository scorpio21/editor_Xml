# Instalación y requisitos

## Requisitos

- PHP 8.0+ con extensión DOM habilitada.
- Servidor web local (XAMPP recomendado en Windows).
- Permisos de lectura/escritura en la carpeta del proyecto para manejar backups `.bak`.

## Puesta en marcha

1. Clona el repositorio en tu servidor web local.
2. Coloca el proyecto en el directorio público (por ejemplo, `htdocs/` en XAMPP).
3. Accede a `http://localhost/editor_Xml/index.php`.

## Configuración

- Archivo `inc/config.php` para ajustes básicos.
- Verifica que `upload_max_filesize` y `post_max_size` en `php.ini` admitan el tamaño de tus XML.

## Estructura del proyecto

- `css/`: estilos (p. ej., `editor-xml.css`, `tabs.css`, `search-external.css`).
- `js/`: scripts (p. ej., `bulk.js`, `dedupe.js`, `search-external.js`).
- `inc/`: lógica PHP (`EditorXml.php`, `acciones.php`, `acciones/*`).
- `partials/`: vistas parciales (pestañas y secciones).
- `img/`: recursos gráficos.
- `test/`: pruebas.
