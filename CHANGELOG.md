# Changelog

Todas las modificaciones notables de este proyecto se documentarán en este archivo.

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
