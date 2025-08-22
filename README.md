# Editor XML de Juegos

[![Estado](https://img.shields.io/badge/estado-activo-success.svg)](./) [![PHP](https://img.shields.io/badge/PHP-8%2B-777bb3.svg)](https://www.php.net/) [![Licencia](https://img.shields.io/badge/licencia-MIT-blue.svg)](./LICENSE)

Aplicación web en PHP para visualizar, editar y mantener ficheros XML/DAT de catálogos de juegos (formato `datafile`). Optimizada para XAMPP en Windows, compatible con cualquier servidor web con PHP 8+ y extensión DOM.

## Tabla de contenidos

- [Características](#características)
- [Pila tecnológica](#pila-tecnológica)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Uso](#uso)
- [Capturas](#capturas)
- [Notas técnicas](#notas-técnicas)
- [Buenas prácticas seguidas](#buenas-prácticas-seguidas)
- [Seguridad (pendiente/mejorable)](#seguridad-pendientemejorable)
- [Roadmap](#roadmap)
- [Contribuir](#contribuir)
- [Versionado](#versionado)
- [Soporte](#soporte)
- [Licencia](#licencia)

## Características

- **Subida de XML/DAT**: carga el fichero y lo deja disponible como `uploads/current.xml`.
- **Cabecera del fichero**: muestra nombre, descripción, versión, fecha, autor y enlaces.
- **Listado con paginación**: render de juegos a 1 por línea, con controles Anterior/Siguiente y “Ir a”.
- **Edición de juegos**: modal para actualizar nombre, descripción, categoría y atributos del `rom` (size, crc, md5, sha1).
- **Eliminación individual**: borrado de un juego concreto.
- **Eliminación masiva con filtros**:
  - Regiones/países a incluir.
  - Idiomas a excluir.
  - Botón “Contar coincidencias” (AJAX) para previsualizar impacto.
- **Backups automáticos**: antes de guardar, se crea `uploads/current.xml.bak` y se revierte si falla la escritura.
- **Restaurar desde .bak**: botón para recuperar el XML previo.
- **Compactar XML**: limpieza de nodos de texto vacíos y guardado con indentación consistente.
- **Ayuda integrada**: botón “Ayuda” con modal explicativo paso a paso.

## Pila tecnológica

- PHP 8+
- Extensión DOM de PHP (para manipulación XML)
- HTML5 + CSS3 (estilos en `css/`)
- JavaScript (vanilla, en `js/`)
- XAMPP/Apache (entorno recomendado en Windows)

## Requisitos

- PHP 8.0 o superior.
- Servidor web (XAMPP recomendado). Probado en Windows.

## Estructura del proyecto

```text
editor_Xml/
├─ css/
│  └─ editor-xml.css
├─ js/
│  └─ editor-xml.js
├─ inc/
│  ├─ acciones.php         # Procesa todas las acciones POST (edit, delete, bulk_delete, compact_xml, etc.)
│  └─ xml-helpers.php      # Helpers: asegurarCarpetaUploads, guardar con backup, limpiar espacios DOM
├─ partials/
│  ├─ header-file.php      # Cabecera de archivo actual y acciones relacionadas
│  ├─ games-list.php       # Render de la lista de juegos (paginada)
│  ├─ bulk-delete.php      # Formulario y controles de eliminación masiva
│  ├─ modal-edit.php       # Modal para editar juego
│  └─ modal-help.php       # Modal de ayuda (uso de la app)
├─ uploads/
│  ├─ current.xml          # Fichero XML activo (se crea tras subir)
│  └─ current.xml.bak      # Copia de seguridad
├─ index.php               # Punto de entrada (UI)
├─ MEJORAS.md              # Roadmap y registro de mejoras
└─ README.md               # Este archivo
```

## Instalación

1. Copia la carpeta `editor_Xml` a `xampp/htdocs/`.
2. Asegúrate de que la carpeta `uploads/` existe (se crea automáticamente si falta).
3. Abre en el navegador: `http://localhost/editor_Xml/`.

## Uso

1) **Subir archivo**: selecciona un `.xml` o `.dat` y pulsa “Cargar XML/DAT”.
2) **Explorar**: usa la paginación y “Ir a” para navegar.
3) **Editar**: pulsa “Editar”, modifica y guarda.
4) **Eliminar**: usa “Eliminar” en un juego o la **Eliminación masiva** con filtros.
5) **Contar coincidencias**: en masivo, usa el botón “Contar coincidencias” para ver el impacto antes de borrar.
6) **Guardar / Compactar XML**: tras una eliminación masiva, pulsa el botón para reescritura limpia del XML.
7) **Restaurar**: si lo necesitas, “Restaurar desde .bak”.
8) **Ayuda**: botón “Ayuda” (arriba) con guía paso a paso.

## Notas técnicas

- El guardado usa `DOMDocument` con `preserveWhiteSpace = false`, `formatOutput = true` y una limpieza de nodos de texto vacíos para evitar líneas en blanco entre elementos.
- Antes de escribir, se hace copia de seguridad `.bak` y, si falla el guardado, se revierte.
- La eliminación masiva soporta un conteo previo por AJAX para mayor seguridad.

## Buenas prácticas seguidas

- Código y textos en **español**.
- CSS en `css/`, JS en `js/`, sin estilos ni scripts embebidos.
- Manejo básico de errores mediante mensajes de sesión.
- Evitamos duplicación de lógica con helpers en `inc/xml-helpers.php`.

## Seguridad (pendiente/mejorable)

- Validaciones más estrictas de entrada (tipos y formatos de `crc`, `md5`, `sha1`).
- Tokens CSRF en formularios POST.
- Protección de la carpeta `uploads/` (si aplica en tu entorno) con `.htaccess`.

## Roadmap

Revisa [`MEJORAS.md`](./MEJORAS.md) para el roadmap detallado, mejoras planificadas y progreso reciente.

## Contribuir

1. Crea un fork y una rama feature: `feature/mi-mejora`.
2. Sigue el estilo del proyecto (PHP 8+, funciones en español, CSS/JS separados).
3. Envía un PR con una descripción clara.

## Licencia

Este proyecto está licenciado bajo los términos de la **MIT License**. Consulta el archivo [`LICENSE`](./LICENSE) para más información.

## Versionado

Se sigue un esquema inspirado en [SemVer](https://semver.org/lang/es/): `MAJOR.MINOR.PATCH`.

- Cambios incompatibles: incremento de `MAJOR`.
- Funcionalidad retrocompatible: incremento de `MINOR`.
- Corrección de errores: incremento de `PATCH`.

Se recomienda usar tags en Git para marcar versiones estables.

## Soporte

- Abre un issue en GitHub describiendo claramente el problema o la propuesta.
- Incluye pasos de reproducción, capturas y, si aplica, fragmentos de XML (sin datos sensibles).

## Capturas

Puedes añadir capturas en la carpeta `img/` y referenciarlas aquí. Ejemplos:

![Pantalla principal](img/captura-pantalla-principal.png)
![Modal de ayuda](img/captura-modal-ayuda.png)
