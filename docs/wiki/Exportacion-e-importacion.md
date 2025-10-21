# Exportación e importación

## Exportación

- Exporta los resultados del filtrado actual a un XML.
- Compactación del DOM para minimizar tamaño.

### Exportación por categorías (pestaña "Categorías")

- Selecciona una o varias categorías y usa:
  - "Contar coincidencias": previsualiza cuántas entradas coinciden.
  - "Eliminar por categoría": borra del XML cargado todas las coincidencias.
  - "Exportar coincidencias a XML": descarga un nuevo fichero solo con coincidencias.
- La lista de categorías se obtiene dinámicamente de los nodos `<category>` del fichero cargado.
- Controles UX: "Seleccionar todo", "Ninguno" e "Invertir".

#### Nombre del fichero exportado

- Patrón: `<Plataforma> - Datfile (N) (YYYY-MM-DD HH-mm-ss).ext` donde `ext` conserva la extensión original (`.xml` o `.dat`).
- Ejemplo: `Microsoft - Xbox - Datfile (2204) (2025-10-21 14-15-51).dat`.

#### Cabecera del fichero exportado (`<header>…</header>`)

- Orden de campos: `name`, `description`, `version`, `date`, y después el resto (`author`, `homepage`, `url`).
- `name`: plataforma base (p. ej., `Microsoft - Xbox`).
- `description`: `<Plataforma> - Datfile (N) (YYYY-MM-DD HH-mm-ss)`.
- `version` y `date`: fecha/hora actual de la exportación.

### Editor de descripción del header

- En la pestaña "Categorías" hay un bloque para editar `header/description` del fichero cargado.
- Se muestra una sugerencia basada en el nombre del fichero, el conteo actual y la fecha.
- Al guardar, se actualiza la descripción en el XML con backup automático.

## Importación (si aplica)

- Paso a paso para importar XML al sistema.
- Validación de esquema y manejo de errores.

## Backups

- Antes de operaciones destructivas, se generan backups `.bak`.
- Ubicación y política de retención sugerida.
