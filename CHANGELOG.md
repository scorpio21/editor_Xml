# Changelog

Todas las modificaciones notables de este proyecto se documentarán en este archivo.

## 2025-08-27

### Added

- Botón y endpoint para **exportar resultados filtrados a XML** sin duplicados.
  - Formulario con preservación de filtros actuales (`q`, `q_in_roms`, `q_in_hashes`) y token CSRF.
  - Generación de un nuevo `datafile` con cabecera original y solo entradas `<game>`/`<machine>` filtradas.
- Nueva pestaña "Buscar juego": formulario para construir enlaces de búsqueda en webs externas (myrient, vimm) por nombre y por hash (MD5/SHA1/CRC) usando búsquedas `site:`.
- Fase 2 (Buscar juego): **Comprobar Archive** con enlace directo
  - Nuevo endpoint `search_archive` (POST, AJAX+CSRF) en `inc/acciones/search_external.php` que consulta `advancedsearch` de Archive.org
  - Botón "Comprobar Archive" y bloque de resultado con "Encontrado/No encontrado" y enlace a `details/{identifier}`

### Fixed

- Corrección de warnings de `DOMDocument::createElement()` por caracteres especiales (p. ej. `&`, `"`, `<`):
  - Se escapa el contenido textual con `htmlspecialchars(..., ENT_XML1 | ENT_COMPAT, 'UTF-8')` en `inc/acciones/crud.php` (acción `export_filtered_xml`).
- Saneado del nombre del archivo exportado para compatibilidad con Windows.

Notas:

- Flujo protegido con CSRF y finalizado con `exit` tras envío de cabeceras y contenido.
- Cierra #25.

## 2025-08-25

### Added

- Interfaz por pestañas accesible (ARIA + teclado) como modo por defecto.
- Iconos SVG en pestañas: `img/ico-home.svg`, `ico-upload.svg`, `ico-bulk.svg`, `ico-mame.svg`, `ico-dedupe.svg`.
- Persistencia de pestaña activa y posición de scroll por panel (sessionStorage).
- Parciales reutilizables: `partials/sections/mame-filters.php` y `partials/sections/dedupe-region.php`.

### Changed

- `index.php`: la UI por defecto pasa a ser la de pestañas. La UI clásica queda accesible con `?ui=classic`.
- Estilos en `css/tabs.css` para estado activo y alineado de iconos.

### Breaking Change

- La navegación por defecto cambia a la UI por pestañas. La antigua UI se mantiene únicamente con `?ui=classic`.

### Debug

- Instrumentación opcional con `?debug=assets` para diagnóstico de carga de assets y cambios de pestañas.
- Archivos: `js/utils.js`, `js/tabs.js`, `js/bulk.js`, `js/dedupe.js`.
- No afecta al comportamiento en producción.

## 2025-08-24

// Fase 1

- Corrección de borrado bajo filtros/paginación usando índice absoluto por tipo.
- Modal de edición ahora usa `data-absindex` para enviar el índice correcto al servidor.
- Logging de borrado simplificado y condicionado por entorno (`APP_ENV`).
- Añadido `.gitignore` para excluir `uploads/` y `logs/` del repositorio.

Notas:

- Ver detalles técnicos en `README.md` y commits asociados.

// Fase 2 (UX)

- Edición por AJAX con actualización en vivo de tarjetas en el listado (sin recargar).
- `inc/acciones/crud.php`: respuestas JSON para acción `edit` (éxito y validaciones).
- `js/modales.js`: intercepta envío, hace `fetch`, actualiza `.game[data-absindex][data-type]` (nombre, descripción, categoría y ROMs).
- Se mantiene redirección para envíos no-AJAX.

Notas:

- Cierra #17.

## 2025-08-23

- Paginación del listado (servidor)
- Backups automáticos y restauración desde .bak
- Compactación manual del XML (limpieza y formateo)
- Búsqueda por nombre/descripcion/categoría (servidor) con preservación del término al paginar
- Edición multi‑ROM y eliminación individual
- Eliminación masiva con filtros (incluye conteo previo por AJAX)
- Cálculo de hashes MD5/SHA1 desde fichero por ROM
- Protección CSRF aplicada a todas las acciones POST críticas

Notas:

- Consulta Issues y Pull Requests para el detalle de cambios. Cierra #15.
