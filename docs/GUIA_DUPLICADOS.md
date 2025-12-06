# Guía de Uso: Gestión de Duplicados

## Descripción

La pestaña "Duplicados" permite detectar y eliminar juegos duplicados de forma inteligente, manteniendo solo la versión que prefieras de cada juego.

## ¿Cómo Funciona la Detección?

El sistema agrupa como duplicados los juegos que tienen **el mismo nombre base**, ignorando:

- **Región**: `(Europe)`, `(USA)`, `(Japan)`, `(World)`, etc.
- **Idiomas**: `(En,Fr,De,Es)`, `(Fr,De)`, `(En)`, etc.
- **Revisiones**: `(Rev 1)`, `(Rev 2)`, etc.

### Ejemplos de Detección

#### Caso 1: Variantes de idiomas

```
Original: Aeon Flux (Europe)
Duplicado: Aeon Flux (Europe) (En,Fr,De,Es)
→ Se agrupan como duplicados del mismo juego "Aeon Flux"
```

#### Caso 2: Versiones regionales

```
Original: Alter Echo (Europe)
Duplicado: Alter Echo (Europe) (Fr,De)
→ Se agrupan como duplicados del mismo juego "Alter Echo"
```

#### Caso 3: Revisiones

```
Original: Elder Scrolls III, The - Morrowind - Game of the Year Edition (Europe) (En,Fr,De)
Duplicado: Elder Scrolls III, The - Morrowind - Game of the Year Edition (Europe) (En,Fr,De) (Rev 1)
→ Se agrupan como duplicados del mismo juego "Elder Scrolls III, The - Morrowind"
```

## Paso a Paso

### 1. Cargar tu archivo XML/DAT

- Ve a la pestaña "Subir y Buscar"
- Selecciona tu archivo `.xml` o `.dat`
- Haz clic en "Cargar XML/DAT"

### 2. Acceder a la pestaña "Duplicados"

- Haz clic en la pestaña "Duplicados"

### 3. Detectar duplicados

- Haz clic en el botón **"Detectar Duplicados"**
- El sistema analizará todo el archivo y mostrará los grupos encontrados

### 4. Revisar los grupos de duplicados

Cada grupo muestra:

- **Nombre base** del juego
- **Cantidad de duplicados** encontrados
- **Lista de todas las variantes** con:
  - Nombre completo
  - Región (si aplica)
  - Idiomas disponibles (con badge especial si incluye español)
  - Revisión (si aplica)

### 5. Seleccionar qué versión mantener

Tienes dos opciones:

#### Opción A: Selección Manual

- En cada grupo, marca con el **radio button** la versión que quieres **MANTENER**
- Las demás versiones del grupo serán eliminadas

#### Opción B: Sugerencias Automáticas

Usa los botones de sugerencias para seleccionar automáticamente:

- **"Sugerir Originales"**: Selecciona versiones sin idiomas específicos (las "originales")
  - Útil si prefieres tener versiones internacionales en lugar de versiones regionales
  
- **"Sugerir Español"**: Selecciona versiones que incluyen español (Es)
  - Perfecto para usuarios hispanohablantes
  
- **"Sugerir Última Rev"**: Selecciona la revisión más alta en cada grupo
  - Recomendado para tener siempre las versiones más actualizadas

### 6. Exportar lista (Opcional)

- Haz clic en **"Exportar a CSV"**
- Descarga un archivo CSV con todos los duplicados encontrados
- Ábrelo en Excel para revisar antes de eliminar

### 7. Generar XML sin duplicados

- Una vez hayas seleccionado las versiones a mantener
- Verás un mensaje: "Se eliminarán X duplicados. Se mantendrán las versiones seleccionadas."
- Haz clic en **"Generar XML sin Duplicados Seleccionados"**
- Se descargará un nuevo archivo XML con solo las versiones que seleccionaste

## Casos de Uso Prácticos

### Caso 1: Mantener solo versiones con español

```
1. Detectar Duplicados
2. Clic en "Sugerir Español"
3. Revisar selección
4. Generar XML sin Duplicados
→ Resultado: Solo juegos en español o con español incluido
```

### Caso 2: Mantener versiones originales

```
1. Detectar Duplicados
2. Clic en "Sugerir Originales"
3. Revisar selección
4. Generar XML sin Duplicados
→ Resultado: Versiones internacionales sin idiomas específicos
```

### Caso 3: Mantener últimas revisiones

```
1. Detectar Duplicados
2. Clic en "Sugerir Última Rev"
3. Revisar selección
4. Generar XML sin Duplicados
→ Resultado: Solo revisiones (Rev 1, Rev 2, etc.) más altas
```

### Caso 4: Selección mixta personalizada

```
1. Detectar Duplicados
2. En grupo A: seleccionar versión con español
3. En grupo B: seleccionar versión original
4. En grupo C: seleccionar versión Rev 1
5. Generar XML sin Duplicados
→ Resultado: Mezcla personalizada según tus preferencias
```

## Consejos

✅ **Exporta a CSV primero** para revisar qué se va a eliminar
✅ **Haz backup** de tu archivo original antes de procesar
✅ **Usa sugerencias** para ahorrar tiempo en catálogos grandes
✅ **Combina estrategias**: usa sugerencias y luego ajusta manualmente
✅ El archivo original **NO se modifica**, siempre se genera uno nuevo

⚠️ **Recuerda**: Solo puedes mantener UNA versión por grupo
⚠️ Si no seleccionas ninguna opción en un grupo, ese grupo no se procesará

## Formato del CSV Exportado

```csv
grupo,nombre_completo,region,idiomas,revision,tipo,descripcion
Aeon Flux,Aeon Flux (Europe),Europe,,,game,Action game based on the series
Aeon Flux,Aeon Flux (Europe) (En,Fr,De,Es),Europe,"En,Fr,De,Es",,game,Action game based on the series
```

## Compatibilidad

- ✅ Funciona con archivos `.xml` y `.dat`
- ✅ Compatible con formatos No-Intro, Redump
- ✅ Soporta tanto `<game>` como `<machine>`
- ✅ Protegido con CSRF
- ✅ Preserva toda la información de ROM (CRC, MD5, SHA1)

## Preguntas Frecuentes

**P: ¿Se modifica mi archivo original?**
R: No. Siempre se genera un archivo nuevo. Tu XML original permanece intacto.

**P: ¿Puedo mantener más de una versión de un juego?**
R: No, en cada grupo debes elegir solo una versión para mantener. Las demás se eliminan.

**P: ¿Qué pasa si no selecciono nada en un grupo?**
R: Ese grupo no se procesará y todas sus versiones permanecerán en el archivo.

**P: ¿Puedo deshacer después de generar el XML?**
R: El archivo generado es independiente. Simplemente carga tu XML original de nuevo si quieres empezar de cero.

**P: ¿Funciona con archivos grandes?**
R: Sí, pero puede tardar unos segundos en detectar en archivos muy grandes (1000+ juegos).

**P: ¿Se pierden datos de las ROMs?**
R: No. Toda la información de ROM (nombre, size, crc, md5, sha1) se preserva en las versiones que mantienes.

## Soporte

Si encuentras problemas o tienes sugerencias:

- Abre un issue en GitHub
- Revisa la documentación principal en README.md
- Consulta el CHANGELOG.md para ver actualizaciones
