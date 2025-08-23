# Mejora del Editor XML de Juegos y Máquinas

## Progreso (implementado)

- **Paginación del listado** (servidor)
  -[x] Parámetros `page` y `per_page` por `GET`.
  -[x] Controles de navegación: «Anterior» / «Siguiente».
  -[x] Formulario “Ir a” para saltar a una página concreta, preservando `per_page`.
  -[x] Opciones de tamaño de página: 10, 25, 50, 100.
  -[x] Se mantienen índices absolutos en editar/eliminar para no romper acciones.

- **Mejoras de UI en listado**
  -[x] Grid de juegos a **1 elemento por línea** (`.game-grid { grid-template-columns: 1fr; }`).
  -[x] Estilos para `.per-page-form`, `.pagination` y `.page-jump`.
  -[x] Sustitución de `color-mix()` por `rgba(...)` para compatibilidad.
  -[x] Visualización de ROMs en lista: cada atributo en su propia línea (HTML con `div` + CSS actualizado).

- **Correcciones**
  -[x] Cierre del bloque `if ($xml)` para evitar error "Unexpected EndOfFile" reportado por el linter.

- **Persistencia segura**
  -[x] Copia de seguridad `.bak` antes de escribir y rollback automático si falla `save()` (en edición y eliminación).

- **Herramientas de mantenimiento**
  -[x] Botón “Restaurar desde .bak” para recuperar el XML desde la copia de seguridad.
  -[x] Eliminación masiva por filtros (incluir/excluir) con simulación (dry-run) para contar coincidencias antes de borrar.
  -[x] Limpieza automática de nodos de texto vacíos al guardar en acciones de `editar`, `eliminar` y `eliminación masiva` (evita líneas en blanco entre `<game>`).
  -[x] Acción manual “Guardar / Compactar XML” que reescribe el fichero formateado y sin espacios sobrantes.
  -[x] Botón “Guardar / Compactar XML” visible tras una eliminación masiva exitosa (usa la bandera de sesión `pending_save`).
  -[x] Buscador por nombre/descripcion/categoría (servidor) vía `GET q`, integrado con paginación y "Mostrar N".

- **Soporte de máquinas y edición avanzada**
  -[x] Listado unificado de nodos `<game>` y `<machine>` con paginación.
  -[x] Visualización de campos específicos en máquinas: `year` y `manufacturer` en la tarjeta.
  -[x] Edición multi‑ROM para juegos y máquinas: añadir/eliminar/editar múltiples `<rom>` por entrada.
  -[x] Validación estricta de ROMs: `size` numérico, `crc` (8 hex), `md5` (32 hex), `sha1` (40 hex).
  -[x] Cálculo de hashes MD5/SHA1 desde fichero por cada ROM (frontend + backend).
  -[x] Eliminación individual disponible para juegos y máquinas.
  -[x] Eliminación masiva extendida para incluir `<machine>` además de `<game>`.

- **Filtros y criterios (actualizado)**
  -[x] Se eliminó el selector de “Regiones/países a excluir”. Ahora el filtrado masivo utiliza únicamente:
  - Regiones/países a incluir
  - Idiomas a excluir
  -[x] Mapeo de región “USA” incluye sinónimos: `USA`, `U.S.A.`, `UNITED STATES`, `AMERICA`.
  -[x] Validado conteo con `uploads/current.xml`: 749 coincidencias para “USA” usando el criterio anterior (botón y script en PowerShell devolvieron el mismo resultado).
  -[x] En eliminación masiva se buscan campos por tipo:
    - Juegos: `name`, `description`, `category`.
    - Máquinas: `name`, `description`, `year`, `manufacturer`.

Este documento propone mejoras alineadas con las Reglas globales de codificación y las necesidades del proyecto.

## Prioridades (alto impacto primero)

- **CSRF en formularios (PRIORITARIO)**
  - [ ] Incluir y verificar token CSRF en todos los formularios POST críticos (`edit`, `delete`, `bulk_delete`, `remove_xml`).
- **Validaciones de entrada (Servidor/Cliente)**
  - [ ] Sanitizar y validar `$_POST` (longitud, tipos, regex para `crc`, `md5`, `sha1`, tamaño numérico).
  - [ ] Responder con mensajes de error claros y no reveladores de detalles internos.

- **Gestión de grandes ficheros XML**
  - [x] Paginación en la lista de juegos para no renderizar miles de nodos a la vez.
  - [ ] Carga diferida (lazy) al navegar páginas.
- **Manejo robusto de errores**
  - [ ] Try/catch alrededor de operaciones DOM y de fichero con logs (no visibles al usuario en prod).
  - [ ] Mensajes de sesión diferenciados por tipo (info, warning, error) y autolimpieza.
- **Accesibilidad y UX del modal**
  - [ ] Focus management (enfocar el primer input al abrir y devolver foco al cierre).
- **Rendimiento front-end**
  - [ ] Delegación de eventos y uso de `documentFragment` al renderizar listas paginadas.
- **Filtros específicos MAME**
  - [ ] Añadir filtros opcionales: `driver status`, `cloneof`, `isbios`, etc., cuando el XML sea de tipo MAME.

## Código y arquitectura

- **Separación de responsabilidades**
  - [ ] Extraer la lógica de edición/eliminación a funciones PHP (por ejemplo, `editarEntrada()`, `eliminarEntrada()`) o a una clase `EditorXml`.
  - [ ] Crear un archivo `helpers.php` con utilidades (escape seguro, validación, manejo de archivos).
- **Estructura de vistas**
  - [ ] Partials PHP para cabecera, tarjeta de juego y modal (evita duplicación y mejora legibilidad).
- **Tipado y estilo**
  - [ ] Mantener `declare(strict_types=1);` y añadir anotaciones PHPDoc a funciones públicas.
  - [ ] Añadir un linter/formatter (PHP CS Fixer) y EditorConfig para consistencia.

## Seguridad

- **Límites de subida**
  - [ ] Verificar tamaño de archivo y MIME type además de la extensión.
- **Carpeta de uploads**
  - [ ] Proteger `uploads/` con `.htaccess` (si aplica) para evitar ejecución/descarga no deseada.
- **Tokens CSRF**
  - [x] Generación de token CSRF por sesión y helpers (`generarTokenCSRF()`, `campoCSRF()`, `verificarTokenCSRF()`).
  - [ ] (PRIORITARIO) Incluir el token en todos los formularios POST críticos (`edit`, `delete`, `bulk_delete`, `remove_xml`, etc.) y verificar en servidor.

## Funcionalidades nuevas

- **Búsqueda y filtrado**
  - [x] Filtro por nombre/descripcion/categoría en servidor (GET `q`) antes de paginar; preserva `q` en paginación y formularios.
  - [ ] Extender búsqueda a campos de ROM (p. ej., `rom/@name`) y hashes (CRC/MD5/SHA1).
  - [ ] Añadir “Idiomas a incluir” (multi-select) para consultas del tipo “Región AND Idioma(s)”.
- **Editar cabecera**
  - [ ] Formulario para actualizar `header` (`name`, `description`, `version`, `date`, `author`, `url`, `homepage`).
- **Alta de entradas**
  - [ ] Botón para crear un `game` nuevo (y soporte futuro para `machine` si aplica), con validaciones y multi‑ROM.
- **Exportar/descargar**
  - [ ] Botón para descargar el XML actual tras ediciones.

## Accesibilidad y diseño

- **Mensajes accesibles**
  - [ ] `role="alert"` para notificaciones y contraste adecuado.
- **Responsive**
  - [ ] Ajustes CSS para móviles (breakpoints en tarjetas y modal).

## Internacionalización

- **Soporte multi-idioma**
  - [ ] Centralizar textos en un archivo de idiomas (es/en) y detector por querystring o sesión.

## Pruebas

- **Pruebas automáticas**
  - [ ] Tests unitarios PHP para validadores y parser DOM.
  - [ ] Tests de integración para flujo de edición/eliminación con ficheros de muestra en `/test/`.

## DX (Developer Experience)

- **README detallado**
  - [ ] Instrucciones de instalación (XAMPP), estructura del proyecto y flujo de trabajo.
- **Datos de ejemplo pequeños**
  - [ ] XML de muestra pequeño en `/test/` para desarrollo rápido sin cargar grandes DATS.

## Roadmap sugerido

- [ ] Validaciones servidor/cliente + CSRF.
- [x] Paginación de lista de juegos (servidor + front).
- [ ] Refactor a funciones/clase `EditorXml` y helpers.
- [ ] Edición de cabecera y alta de juegos.
- [x] Backups automáticos y botón deshacer (restauración desde .bak).
- [x] Compactación del XML: limpieza de espacios automática y acción manual para reescritura limpia.
- [ ] Exportación.
- [x] Búsqueda básica por nombre/descripcion/categoría (servidor).
- [ ] Reloj: opción de mostrar segundos en UI y configuración explícita de zona horaria (PHP/JS).
- [ ] Accesibilidad avanzada e i18n.
- [ ] Filtros MAME específicos (driver status, cloneof, isbios...).
- [ ] Tests unitarios y de integración.
- [ ] Refactor de código y mejora de DX.
