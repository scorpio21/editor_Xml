# Editor XML de Juegos y Máquinas

[![Estado](https://img.shields.io/badge/estado-activo-success.svg)](./) [![PHP](https://img.shields.io/badge/PHP-8%2B-777bb3.svg)](https://www.php.net/) [![Licencia](https://img.shields.io/badge/licencia-MIT-blue.svg)](./LICENSE) [![Issues](https://img.shields.io/github/issues/scorpio21/editor_Xml.svg)](https://github.com/scorpio21/editor_Xml/issues) [![Último commit](https://img.shields.io/github/last-commit/scorpio21/editor_Xml.svg)](https://github.com/scorpio21/editor_Xml/commits) [![Stars](https://img.shields.io/github/stars/scorpio21/editor_Xml.svg?style=social)](https://github.com/scorpio21/editor_Xml/stargazers)

Aplicación web en PHP para visualizar, editar y mantener ficheros XML/DAT de catálogos de juegos y máquinas (formatos tipo `datafile`, compatibles con No-Intro y MAME). Optimizada para XAMPP en Windows, compatible con cualquier servidor web con PHP 8+ y extensión DOM.

Actualizado: 2025-08-24 — ver `CHANGELOG.md` (Fase 1: índices absolutos en borrado/edición y logging más discreto).

## Tabla de contenidos

- [Características clave](#características-clave)
- [Características](#características)
- [Por qué / Para quién](#por-qué--para-quién)
- [Pila tecnológica](#pila-tecnológica)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Uso](#uso)
- [Inicio rápido](#inicio-rápido)
- [Capturas](#capturas)
- [Notas técnicas](#notas-técnicas)
- [Buenas prácticas seguidas](#buenas-prácticas-seguidas)
- [Seguridad (pendiente/mejorable)](#seguridad-pendientemejorable)
- [Limitaciones actuales](#limitaciones-actuales)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [Contribuir](#contribuir)
- [Reportar un problema / Solicitar mejora](#reportar-un-problema--solicitar-mejora)
- [Versionado](#versionado)
- [Soporte](#soporte)
- [Licencia](#licencia)

## Características clave

- 🚀 Edición rápida de juegos y máquinas (soporte multi‑ROM)
- 🔍 Búsqueda y filtros (incluye eliminación masiva con dry‑run)
- 🧰 Mantenimiento seguro: backups automáticos y restauración desde `.bak`
- 🧹 Compactación y limpieza automática del XML al guardar
- 📄 Paginación en servidor para DATS grandes
- 🔐 Protección CSRF en todas las acciones POST

## Características

- **Subida de XML/DAT**: carga el fichero y lo deja disponible como `uploads/current.xml`.
- **Cabecera del fichero**: muestra nombre, descripción, versión, fecha, autor y enlaces.
- **Listado con paginación**: render unificado de entradas `<game>` y `<machine>` a 1 por línea, con controles Anterior/Siguiente y “Ir a”.
- **Edición de juego/máquina (multi‑ROM)**: modal para actualizar nombre, descripción, categoría (solo `game`) y múltiples ROMs con `size`, `crc`, `md5`, `sha1`. Incluye botón para calcular hashes MD5/SHA1 desde fichero por cada ROM.
- **Campos específicos de máquinas**: visualización de `year` y `manufacturer` en la lista.
- **Eliminación individual**: borrado de un juego o una máquina concreta.
- **Eliminación masiva con filtros (juegos y máquinas)**:
  - Regiones/países a incluir.
  - Idiomas a excluir.
  - Búsqueda por: `name`, `description`, `category` (en `game`) y `year`, `manufacturer` (en `machine`).
  - Botón “Contar coincidencias” (AJAX) para previsualizar impacto.
- **Búsqueda rápida (servidor)**: cuadro de búsqueda por nombre/descr./categoría antes de paginar; preserva el término en la navegación.
- **Visualización clara de ROMs**: cada atributo (`name`, `size`, `crc`, `md5`, `sha1`) en su propia línea para mejor legibilidad.
- **Backups automáticos**: antes de guardar, se crea `uploads/current.xml.bak` y se revierte si falla la escritura.
- **Restaurar desde .bak**: botón para recuperar el XML previo.
- **Compactar XML**: limpieza de nodos de texto vacíos y guardado con indentación consistente.
- **Ayuda integrada**: botón “Ayuda” con modal explicativo paso a paso.

## Por qué / Para quién

- Personas que gestionan grandes catálogos XML/DAT (No-Intro, Redump, MAME).
- Coleccionistas que necesitan filtrar, editar y limpiar entradas con múltiples ROMs.
- Administradores que requieren backups, restauración y compactación segura del XML.

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
│  ├─ csrf-helper.php      # Helpers de CSRF: generar/verificar token y campo oculto
│  └─ xml-helpers.php      # Helpers: asegurarCarpetaUploads, guardar con backup, limpiar espacios DOM
├─ partials/
│  ├─ header-file.php      # Cabecera de archivo actual y acciones relacionadas
│  ├─ games-list.php       # Render de la lista unificada de juegos y máquinas (paginada)
│  ├─ bulk-delete.php      # Formulario y controles de eliminación masiva (juegos y máquinas)
│  ├─ modal-edit.php       # Modal para editar juego/máquina con soporte multi-ROM
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
2) **Explorar**: usa la paginación y “Ir a” para navegar (verás juegos y máquinas).
3) **Editar**: pulsa “Editar” en un juego o una máquina, modifica y guarda.
   - Puedes añadir, eliminar o modificar múltiples ROMs por entrada.
   - Valida `size`, `crc` (8 hex), `md5` (32 hex) y `sha1` (40 hex). Puedes calcular hashes desde fichero.
   - En `machine` no aplica `category`.
4) **Eliminar**: usa “Eliminar” en un juego o una máquina, o la **Eliminación masiva** con filtros.
5) **Contar coincidencias**: en masivo, usa el botón “Contar coincidencias” para ver el impacto antes de borrar.
6) **Guardar / Compactar XML**: tras una eliminación masiva, pulsa el botón para reescritura limpia del XML.
7) **Restaurar**: si lo necesitas, “Restaurar desde .bak”.
8) **Ayuda**: botón “Ayuda” (arriba) con guía paso a paso.
9) **Buscar**: utiliza el cuadro de búsqueda para filtrar por nombre/descr./categoría. El término se mantiene al paginar y cambiar "Mostrar N".

## Inicio rápido

1. Abre `http://localhost/editor_Xml/` en tu navegador.
2. Sube un archivo `.xml` o `.dat` (se guardará como `uploads/current.xml`).
3. Edita entradas desde la lista o el modal; usa “Contar coincidencias” antes de una eliminación masiva.
4. Guarda/Compacta y, si es necesario, restaura desde `.bak`.

## Notas técnicas

- El guardado usa `DOMDocument` con `preserveWhiteSpace = false`, `formatOutput = true` y una limpieza de nodos de texto vacíos.
- Antes de escribir, se hace copia de seguridad `.bak` y, si falla el guardado, se revierte.
- La edición multi-ROM reemplaza todos los nodos `<rom>` del elemento editado por los nuevos valores validados.
- La eliminación masiva soporta un conteo previo por AJAX y contempla nodos `<game>` y `<machine>`.
- Reloj en UI: elementos con `data-clock` muestran la hora actual del navegador, actualizada cada minuto. “Última modificación” en cabecera usa `filemtime` del XML y la zona horaria de PHP.

## Buenas prácticas seguidas

- Código y textos en **español**.
- CSS en `css/`, JS en `js/`, sin estilos ni scripts embebidos.
- Manejo básico de errores mediante mensajes de sesión.
- Evitamos duplicación de lógica con helpers en `inc/xml-helpers.php`.

## Seguridad (pendiente/mejorable)

- Validaciones más estrictas de entrada (tipos y formatos de `crc`, `md5`, `sha1`).
- CSRF: implementado en todos los formularios POST críticos y verificado en servidor. Ver detalle en [`CHANGELOG.md`](./CHANGELOG.md).
- Protección de la carpeta `uploads/` (si aplica en tu entorno) con `.htaccess`.

## Limitaciones actuales

- Rendimiento: los DATS muy grandes pueden tardar en procesarse en equipos modestos.
- Validaciones avanzadas de entrada: pueden ampliarse (más mensajes y reglas específicas).
- i18n: actualmente interfaz en español; multi‑idioma pendiente.
- Pruebas automáticas: unitarias e integración aún por completar.

## Roadmap

Revisa [`MEJORAS.md`](./MEJORAS.md) para el roadmap detallado, mejoras planificadas y progreso reciente.

- Milestone v1.0 (issues priorizados): [ver en GitHub](https://github.com/scorpio21/editor_Xml/issues?q=is%3Aissue+milestone%3A%22v1.0%22)
- Milestone v1.1 (siguientes iteraciones): [ver en GitHub](https://github.com/scorpio21/editor_Xml/issues?q=is%3Aissue+milestone%3A%22v1.1%22)
- Project "Editor XML Roadmap" (tablero): [Project 5](https://github.com/users/scorpio21/projects/5)

## Changelog

Consulta el historial de cambios en [`CHANGELOG.md`](./CHANGELOG.md).

## Contribuir

1. Crea un fork y una rama feature: `feature/mi-mejora`.
2. Sigue el estilo del proyecto (PHP 8+, funciones en español, CSS/JS separados).
3. Envía un PR con una descripción clara.

## Reportar un problema / Solicitar mejora

- Abre un issue desde GitHub: [Elegir plantilla](https://github.com/scorpio21/editor_Xml/issues/new/choose)
- Si no usas plantilla: [Nuevo issue](https://github.com/scorpio21/editor_Xml/issues/new)

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
