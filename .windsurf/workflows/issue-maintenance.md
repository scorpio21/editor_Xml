---
description: Mantenimiento de issues con GitHub CLI (listar, cerrar, comentar, etiquetar, milestones)
---

# Mantenimiento de issues con GitHub CLI

Este workflow guía tareas comunes de mantenimiento de issues en `scorpio21/editor_Xml` usando GitHub CLI (`gh`). Requiere autenticación previa (`gh auth login`).

1. Verificar autenticación y remoto
   - Comprobar sesión y protocolo Git
   
   ```powershell
   gh auth status
   git remote -v
   ```

2. Listar issues
   - Abiertos por defecto y cerrados si se necesita
   
   ```powershell
   # Abiertos
   gh issue list -R scorpio21/editor_Xml -s open
   # Cerrados
   gh issue list -R scorpio21/editor_Xml -s closed
   ```

3. Cerrar un issue con comentario de referencia
   - Sustituye `NUM` y el texto entre comillas por el mensaje que quieras
   
   ```powershell
   gh issue close <NUM> -R scorpio21/editor_Xml -c "Cerrado por commit <SHA> (ver CHANGELOG 2025-08-29)."
   ```

4. Comentar en un issue (sin cerrarlo)
   
   ```powershell
   gh issue comment <NUM> -R scorpio21/editor_Xml -b "Mensaje de seguimiento o referencia de commit."
   ```

5. Crear etiquetas (labels) comunes
   - Ejecutar una vez por etiqueta. Si ya existen, `gh` lo indicará.
   
   ```powershell
   gh label create "security" -R scorpio21/editor_Xml -c "#ef4444" -d "Cambios de seguridad"
   gh label create "docs"     -R scorpio21/editor_Xml -c "#3b82f6" -d "Documentación"
   gh label create "ux"       -R scorpio21/editor_Xml -c "#22c55e" -d "Experiencia de usuario"
   gh label create "bug"      -R scorpio21/editor_Xml -c "#d97706" -d "Corrección de errores"
   gh label create "enhancement" -R scorpio21/editor_Xml -c "#a855f7" -d "Mejoras"
   ```

6. Etiquetar issues existentes
   - Añadir o quitar etiquetas según convenga
   
   ```powershell
   # Añadir
   gh issue edit <NUM> -R scorpio21/editor_Xml --add-label "docs,ux"
   # Quitar
   gh issue edit <NUM> -R scorpio21/editor_Xml --remove-label "bug"
   ```

7. Crear milestone y asignarlo a issues
   
   ```powershell
   # Crear milestone (ejemplo: versión o fecha)
   gh milestone create -R scorpio21/editor_Xml "2025-09"
   # Obtener ID o listar milestones
   gh milestone list -R scorpio21/editor_Xml
   # Asignar milestone a un issue
   gh issue edit <NUM> -R scorpio21/editor_Xml --milestone "2025-09"
   ```

8. Cerrar varios issues por filtro (opcional)
   - Usa el filtro `--search` para listar y cierra los relevantes manualmente
   
   ```powershell
   # Buscar issues con texto "flash message" abiertos
   gh issue list -R scorpio21/editor_Xml -s open --search "flash message"
   # Cerrar uno concreto
   gh issue close <NUM> -R scorpio21/editor_Xml -c "Resuelto en <SHA>"
   ```

9. Verificar resultado
   
   ```powershell
   gh issue view <NUM> -R scorpio21/editor_Xml
   gh issue list -R scorpio21/editor_Xml -s closed
   ```

## Notas

- Sustituye `NUM` por el número de issue y `SHA` por el commit.
- Mantén tokens fuera del repo. `gh` usa el keyring de Windows de forma segura.
- Protocolo configurado: HTTPS (se puede cambiar con `gh config set -h github.com git_protocol https`).
