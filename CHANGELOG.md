# Changelog

Todas las modificaciones notables de este proyecto se documentarán en este archivo.

## 2025-08-29

### Security — 2025-08-29

- Saneado automático de `<!DOCTYPE>` en cargas de XML para mantener mitigaciones XXE sin bloquear al usuario.
  - `inc/xml-helpers.php:cargarXmlSiDisponible`: detecta y elimina `DOCTYPE` (con o sin subset interno) antes de parsear.
  - Carga protegida con `LIBXML_NONET` y persistencia del XML saneado mediante `guardarDomConBackup()`.
  - Registro de advertencia en logs y aviso en UI solo una vez por sesión.
  - Documentado en `partials/modal-help.php` (sección "Primeros pasos").

### Docs — 2025-08-29

- `README.md`: nueva sección "Seguridad XML (XXE)" que documenta:
  - Saneado automático de DOCTYPE y persistencia del XML saneado.
  - Escapado de entidades no declaradas tras retirar DTD.
  - Uso de `LIBXML_NONET` y flags seguros en DOM (`resolveExternals=false`, `substituteEntities=false`, `validateOnParse=false`).

### UX — 2025-08-29

- Mensaje de aviso convertido en "flash message" accesible:
  - `partials/header-file.php`: imprime `$_SESSION['message']` y hace `unset()` tras render.
  - Accesibilidad: `role="status"` y `aria-live="polite"`.

## 2025-08-27

### Added — 2025-08-27

- Botón y endpoint para **exportar resultados filtrados a XML** sin duplicados.
  - Formulario con preservación de filtros actuales (`q`, `q_in_roms`, `q_in_hashes`) y token CSRF.
  - Generación de un nuevo `datafile` con cabecera original y solo entradas `<game>`/`<machine>` filtradas.
- Nueva pestaña "Buscar juego": formulario para construir enlaces de búsqueda en webs externas (myrient, vimm) por nombre y por hash (MD5/SHA1/CRC) usando búsquedas `site:`.
- Fase 2 (Buscar juego): **Comprobar Archive** con enlace directo
  - Nuevo endpoint `search_archive` (POST, AJAX+CSRF) en `inc/acciones/search_external.php` que consulta `advancedsearch` de Archive.org
  - Botón "Comprobar Archive" y bloque de resultado con "Encontrado/No encontrado" y enlace a `details/{identifier}`

### Fixed — 2025-08-27

- Corrección de warnings de `DOMDocument::createElement()` por caracteres especiales (p. ej. `&`, `"`, `<`):
  - Se escapa el contenido textual con `htmlspecialchars(..., ENT_XML1 | ENT_COMPAT, 'UTF-8')` en `inc/acciones/crud.php` (acción `export_filtered_xml`).
- Saneado del nombre del archivo exportado para compatibilidad con Windows.

### Changed — 2025-08-27

- UI: La pestaña y panel "Filtros MAME (opcional)" ahora solo se muestran cuando el XML contiene nodos `<machine>` (detección de tipo MAME).
  - Referencias: `index.php` (variable `$isMame` y render condicional) y `partials/bulk-delete.php` (bloque condicional con nota accesible cuando no aplica).
- UI (MAME): Cuando el fichero es MAME, la **eliminación masiva se deshabilita** y no se muestra la pestaña correspondiente; la pestaña de MAME se convierte en **solo buscador** (nombre/ROM/hash).
  - Referencias: `index.php` (oculta tab/panel de eliminación masiva en MAME; renombra y ajusta panel MAME a “MAME (buscar)”), `partials/games-list.php` (oculta botones “Eliminar” si `$isMame`).
- Logging: `inc/xml-helpers.php` ahora registra
  - resultado de creación de backup (`crearBackup`, éxito/fracaso),
  - intento de backup previo en guardado (`guardarDomConBackup`, con advertencia si falla),
  - y bytes escritos al guardar exitosamente.
  - Rotación básica y niveles configurables en `inc/logger.php` y `inc/config.php` (`LOG_PATH`, `LOG_LEVEL_MIN`).
  - Refactor: centralización de helpers XML mediante `inc/EditorXml.php` (métodos estáticos).
    - Acciones actualizadas para usar `EditorXml`: `inc/acciones/crud.php`, `inc/acciones/bulk.php`, `inc/acciones/dedupe.php`.
    - Compatibilidad: `inc/xml-helpers.php` mantiene la implementación y se invoca desde `EditorXml` para evitar duplicación. Tests existentes siguen apuntando a helpers globales.

Notas:

- Flujo protegido con CSRF y finalizado con `exit` tras envío de cabeceras y contenido.
- "Compactar XML" sobrescribe el archivo actual y crea `.bak`. Para obtener un fichero nuevo, usa "Descargar XML".
- Cierra #25.

### Security — 2025-08-27

- Mitigación XXE aplicada en todas las cargas de XML.
  - `SimpleXML`: `LIBXML_NONET` y rechazo proactivo de `<!DOCTYPE>` en `inc/xml-helpers.php` (`cargarXmlSiDisponible`).
  - `DOMDocument::loadXML`: `LIBXML_NONET` y desactivadas `resolveExternals`, `substituteEntities`, `validateOnParse` en `inc/acciones/crud.php`, `inc/acciones/dedupe.php`, `inc/acciones/bulk.php`.
- Tests: añadido `test/xxe_security_test.php` (verifica rechazo de DOCTYPE y no expansión de entidades externas).
- Documentación: `MEJORAS.md` actualizado con sección de Seguridad XML (XXE).

## 2025-08-25

### Added — 2025-08-25

- Interfaz por pestañas accesible (ARIA + teclado) como modo por defecto.
- Iconos SVG en pestañas: `img/ico-home.svg`, `ico-upload.svg`, `ico-bulk.svg`, `ico-mame.svg`, `ico-dedupe.svg`.
- Persistencia de pestaña activa y posición de scroll por panel (sessionStorage).
- Parciales reutilizables: `partials/sections/mame-filters.php` y `partials/sections/dedupe-region.php`.

### Changed — 2025-08-25

- `index.php`: la UI por defecto pasa a ser la de pestañas. La UI clásica queda accesible con `?ui=classic`.
- Estilos en `css/tabs.css` para estado activo y alineado de iconos.

### Breaking Change — 2025-08-25

- La navegación por defecto cambia a la UI por pestañas. La antigua UI se mantiene únicamente con `?ui=classic`.

### Debug — 2025-08-25

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
