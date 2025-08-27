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

### Actualización 2025-08-27 — Refactor Fase 2 (helpers MAME y limpieza de acciones)

- Centralización: se consolidan funciones helper específicas de MAME en `inc/mame-filters.php`:
  - `aplicarFiltrosMame()`
  - `sanitizarTexto()`
  - `procesarFiltrosMame()`
  - `obtenerTextoParaBusqueda()` (soporta `DOMElement` y `SimpleXMLElement`)
- Limpieza: `inc/acciones.php` se reduce a un shim que delega en `inc/router-acciones.php`, eliminando código muerto y duplicado.
- Sin cambios funcionales: solo reorganización para mejorar mantenibilidad y futuras pruebas unitarias.
- Documentación: `README.md` actualizado para reflejar la nueva estructura y el módulo `inc/mame-filters.php`.
