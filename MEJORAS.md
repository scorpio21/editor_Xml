# Mejora del Editor XML de Juegos y Máquinas

Este documento ahora actúa como índice hacia los Issues y el Project. El plan y las tareas viven en GitHub para evitar duplicidad y mantener una única fuente de verdad.

## Seguimiento del trabajo

- Milestone v1.0: [github.com/scorpio21/editor_Xml/milestone/1](https://github.com/scorpio21/editor_Xml/milestone/1)
- Milestone v1.1: [github.com/scorpio21/editor_Xml/milestone/2](https://github.com/scorpio21/editor_Xml/milestone/2)
- Project Roadmap: [github.com/users/scorpio21/projects/5](https://github.com/users/scorpio21/projects/5)
- Issues de mejora (label enhancement): [ver lista](https://github.com/scorpio21/editor_Xml/issues?q=is%3Aissue+label%3Aenhancement)

Para el historial de cambios, consulta `CHANGELOG.md`. El detalle de tareas y planificación está en GitHub (Issues/Milestones/Project).

## Actualización 2025-08-23

- Documentación actualizada (`README.md`, `CHANGELOG.md`).
- Se consolidan en docs las funcionalidades implementadas: filtros de eliminación masiva con conteo previo (AJAX), cálculo de hashes MD5/SHA1 desde fichero y protección CSRF.
- Referencia: ver `CHANGELOG.md` para el detalle. Cierra #15.

## Actualización 2025-08-24

- Fase 1 completada: corrección de borrado con índices absolutos y ajuste del modal de edición (`data-absindex`).
- Logging de borrado simplificado y condicionado por `APP_ENV`.
- Añadido `.gitignore` para excluir `/uploads` y `/logs`.
- Ver `CHANGELOG.md` para el detalle y commits asociados.

## Actualización 2025-08-25

- UI por pestañas accesible pasa a ser el modo por defecto (`index.php`).
- Añadidos iconos SVG para pestañas (`img/ico-*.svg`).
- Persistencia de pestaña activa y scroll por panel (sessionStorage) en `js/tabs.js`.
- Parciales reutilizables: `partials/sections/mame-filters.php` y `partials/sections/dedupe-region.php`.

Siguientes líneas de trabajo sugeridas:

- Accesibilidad avanzada: revisión con lector de pantalla, roles/atributos, `:focus-visible` y contraste AA.
- Tests de teclado: flechas, Home/End, Enter/Espacio, restauración de foco.
- Deep-link opcional `?tab=<n>` y restauración entre sesiones (localStorage) si se requiere.
- Tema oscuro y variables de color (`--accent-*`).
- E2E de navegación por pestañas y flujos de eliminación masiva/duplicados.
-. Proteger el diagnóstico `?debug=assets` por entorno (`APP_ENV=development`) o bandera de build.

## Actualización 2025-08-27

- Nueva funcionalidad: botón y endpoint para **exportar resultados filtrados a XML** sin duplicados.
- Robustez: se **escapan caracteres especiales** al construir elementos XML con `DOMDocument` (`ENT_XML1`), evitando warnings por entidades no terminadas.
- Nombre de archivo: saneado para Windows; patrón de exportación `Nombre (filtered) (N) (YYYY-MM-DD HH-mm-ss).xml`.
- Seguridad: mantiene verificación **CSRF** en el flujo de exportación y finaliza con `exit` tras enviar cabeceras/archivo.
- Documentación: actualizado `README.md` (uso y características) y `CHANGELOG.md` (historial).
- Estado: cierra issue de exportación filtrada y errores XML.

## Guía rápida: harness de integración y E2E

El repositorio incluye un harness para ejecutar acciones del backend sin pasar por la UI y un script E2E mínimo.

- Harness: `test/integration_harness_runner.php`
- E2E: `test/integration_actions_test.php`

Comandos (PowerShell, Windows/XAMPP):

```powershell
# Ejecutar E2E completo (create -> add -> compact -> download)
D:\xampp\php\php.exe test\integration_actions_test.php

# Usar el harness manualmente en un XML aislado
$env:ACTION="create_xml"; $env:XML_PATH="d:\xampp\htdocs\editor_Xml\uploads\itests\current.xml"; \
  D:\xampp\php\php.exe test\integration_harness_runner.php

$env:ACTION="add_game"; $env:XML_PATH="d:\xampp\htdocs\editor_Xml\uploads\itests\current.xml"; \
$env:EXTRA_JSON='{"game_name":"Pac-Man","description":"Arcade clásico","category":"Arcade","rom_name":["pacman.rom"],"size":["16384"],"crc":["0123ABCD"],"md5":["0123456789abcdef0123456789abcdef"],"sha1":["0123456789abcdef0123456789abcdef01234567"]}'; \
  D:\xampp\php\php.exe test\integration_harness_runner.php

$env:ACTION="compact_xml"; $env:XML_PATH="d:\xampp\htdocs\editor_Xml\uploads\itests\current.xml"; \
  D:\xampp\php\php.exe test\integration_harness_runner.php

$env:ACTION="download_xml"; $env:XML_PATH="d:\\xampp\\htdocs\\editor_Xml\\uploads\\itests\\current.xml"; \
  D:\\xampp\\php\\php.exe test\\integration_harness_runner.php > uploads\\itests\\downloaded.xml

# Editar una entrada (índice 0, tipo game)
$env:ACTION="edit"; $env:XML_PATH="d:\\xampp\\htdocs\\editor_Xml\\uploads\\itests\\current.xml"; \
$env:EXTRA_JSON='{"index":0,"node_type":"game","game_name":"Pac-Man Plus","description":"Versión mejorada","category":"Arcade","rom_name":["pacmanp.rom"],"size":["32768"],"crc":["89ABCDEF"],"md5":["abcdef0123456789abcdef0123456789"],"sha1":["abcdef0123456789abcdef0123456789abcdef0123"]}'; \
  D:\\xampp\\php\\php.exe test\\integration_harness_runner.php

# Eliminar una entrada (índice 0, tipo game)
$env:ACTION="delete"; $env:XML_PATH="d:\\xampp\\htdocs\\editor_Xml\\uploads\\itests\\current.xml"; \
$env:EXTRA_JSON='{"index":0,"node_type":"game"}'; \
  D:\\xampp\\php\\php.exe test\\integration_harness_runner.php
```

Notas:

- Usar rutas bajo `uploads/itests*/` para evitar tocar `uploads/current.xml` real.
- `EXTRA_JSON` permite inyectar parámetros para acciones que lo requieran (`add_game`, `edit`, `create_xml`).

### Actualización 2025-08-27 — Refactor Fase 2 (helpers MAME y limpieza de acciones)

- Centralización: se consolidan funciones helper específicas de MAME en `inc/mame-filters.php`:
  - `aplicarFiltrosMame()`
  - `sanitizarTexto()`
  - `procesarFiltrosMame()`
  - `obtenerTextoParaBusqueda()` (soporta `DOMElement` y `SimpleXMLElement`)
- Limpieza: `inc/acciones.php` se reduce a un shim que delega en `inc/router-acciones.php`, eliminando código muerto y duplicado.
- Sin cambios funcionales: solo reorganización para mejorar mantenibilidad y futuras pruebas unitarias.
- Documentación: `README.md` actualizado para reflejar la nueva estructura y el módulo `inc/mame-filters.php`.

### Actualización 2025-08-27 — Fase 3 (cerrada)

- Centralización de carga XML: `inc/router-acciones.php` ahora usa `cargarXmlSiDisponible()` de `inc/xml-helpers.php`.
- Eliminada duplicación de helpers (`tokenizar`, `anyTermMatch`, `mapearRegionesIdiomas`) en `inc/acciones/common.php`; se reutilizan los de `inc/xml-helpers.php`.
- Robustez: `cargarXmlSiDisponible()` captura y registra errores de libxml; añadidos docblocks en helpers.
- Logging: `crearBackup()` y `guardarDomConBackup()` registran éxito/fracaso de copia y bytes escritos al guardar.
- Tests mínimos: `test/xml_helpers_test.php` creado y ejecutado con resultados OK.

Checklist de cierre F3:

- [x] Centralización carga XML en router
- [x] Eliminar helpers duplicados en `common.php`
- [x] Manejo de errores y docblocks en helpers
- [x] Logging de backups/guardado con bytes
- [x] Tests mínimos y documentación actualizada

### Actualización 2025-08-27 — i18n básico y Búsqueda externa (Fase 2)

- i18n: selector de idioma por banderas en la cabecera (`img/flags/es.svg`, `img/flags/gb.svg`) con estilos mínimos en `css/editor-xml.css`.
- Documentación y ayuda: actualizado `README.md` (Estructura) y `partials/modal-help.php` (secciones de Exportar XML, Búsqueda externa y selector de idioma).
- Búsqueda externa Fase 2: añade botón "Comprobar Archive" que consulta la API de Archive.org para ofrecer enlace directo cuando hay coincidencias.
  - Sin scraping, protegido con CSRF, y sin exponer claves.
  - Se generan enlaces para myrient, vimm y archive.org por nombre y hashes (MD5/SHA1/CRC).

### Actualización 2025-08-27 — Seguridad XML (XXE)

- Endurecimiento completo de cargas XML para mitigar XXE:
  - `SimpleXML`: `simplexml_load_file(..., 'SimpleXMLElement', LIBXML_NONET)` en `inc/xml-helpers.php` y `inc/acciones/crud.php` (descarga/exportación).
  - `DOMDocument::loadXML()`: deshabilitar `resolveExternals`, `substituteEntities`, `validateOnParse` y usar `LIBXML_NONET` en `inc/acciones/crud.php`, `inc/acciones/dedupe.php` y `inc/acciones/bulk.php`.
- Pruebas: añadido `test/xxe_security_test.php` con XML malicioso (DOCTYPE + entidad externa). Verifica que se bloquea el acceso de red y la expansión de entidades.
- Notas: se mantiene el manejo de errores con `libxml_use_internal_errors(true)` en helpers. No hay cambios de lógica de negocio.
