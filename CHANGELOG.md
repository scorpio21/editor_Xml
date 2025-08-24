# Changelog

Todas las modificaciones notables de este proyecto se documentarán en este archivo.

## 2025-08-24

// Fase 1

- Corrección de borrado bajo filtros/paginación usando índice absoluto por tipo.
- Modal de edición ahora usa `data-absindex` para enviar el índice correcto al servidor.
- Logging de borrado simplificado y condicionado por entorno (`APP_ENV`).
- Añadido `.gitignore` para excluir `uploads/` y `logs/` del repositorio.

Notas:

- Ver detalles técnicos en `README.md` y commits asociados.

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
