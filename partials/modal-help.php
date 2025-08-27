<?php
declare(strict_types=1);
?>
<!-- Modal ayuda -->
<div class="modal" id="helpModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
  <div class="modal-content" role="document">
    <button type="button" class="close" aria-label="Cerrar" onclick="closeHelpModal()">&times;</button>
    <h3 id="helpTitle">Ayuda del Editor XML</h3>
    <nav aria-label="Índice de ayuda">
      <ol>
        <li><a href="#h-requisitos">Requisitos</a></li>
        <li><a href="#h-primeros-pasos">Primeros pasos</a></li>
        <li><a href="#h-listado">Listado y paginación</a></li>
        <li><a href="#h-editar">Edición de entradas</a></li>
        <li><a href="#h-eliminar">Eliminación individual</a></li>
        <li><a href="#h-masivo">Eliminación masiva</a></li>
        <li><a href="#h-busqueda">Búsqueda</a></li>
        <li><a href="#h-compactar">Guardar / Compactar XML</a></li>
        <li><a href="#h-restaurar">Restaurar desde .bak</a></li>
        <li><a href="#h-consejos">Consejos y atajos</a></li>
        <li><a href="#h-soporte">Soporte</a></li>
      </ol>
    </nav>

    <section id="h-requisitos">
      <h4>1) Requisitos</h4>
      <ul>
        <li>Archivo XML/DAT válido de tipo <code>datafile</code> (con cabecera y entradas).</li>
        <li>PHP 8+ y extensión DOM activada en el servidor.</li>
        <li>Para colecciones grandes, usa la paginación para mejorar el rendimiento.</li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-primeros-pasos">
      <h4>2) Primeros pasos</h4>
      <ol>
        <li>Ve a <strong>Subir fichero XML o DAT</strong> y selecciona tu archivo.</li>
        <li>Pulsa <strong>Cargar XML/DAT</strong>.</li>
        <li>Si es válido, se mostrará la <strong>cabecera</strong> y el <strong>listado paginado</strong>.</li>
      </ol>
      <p><small>Consejo: tras cambios masivos, usa <em>Guardar / Compactar XML</em> para mantener el archivo limpio.</small></p>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-listado">
      <h4>3) Listado y paginación</h4>
      <ul>
        <li>Navega con <strong>Anterior / Siguiente</strong> o el formulario <strong>Ir a</strong>.</li>
        <li>Configura <strong>Elementos por página</strong> (10/25/50/100) según tu preferencia.</li>
        <li>La vista unifica <code>&lt;game&gt;</code> y <code>&lt;machine&gt;</code> para una navegación consistente.</li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-editar">
      <h4>4) Edición de entradas</h4>
      <ol>
        <li>Pulsa <strong>Editar</strong> en la tarjeta de la entrada.</li>
        <li>Actualiza los campos y confirma con <strong>Guardar cambios</strong>.</li>
        <li><strong>Multi‑ROM</strong>: puedes añadir, eliminar o modificar varias ROMs en la misma entrada.</li>
        <li>Validaciones: <code>size</code> numérico, <code>crc</code> (8 hex), <code>md5</code> (32 hex), <code>sha1</code> (40 hex). Puedes calcular MD5/SHA1 desde fichero.</li>
        <li>En <code>machine</code> se muestran además <strong>year</strong> y <strong>manufacturer</strong>. El campo <code>category</code> aplica solo a <code>game</code>.</li>
      </ol>
      <p><small>Al guardar, el XML se reescribe con formato consistente e indentación adecuada.</small></p>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-eliminar">
      <h4>5) Eliminación individual</h4>
      <ol>
        <li>Pulsa <strong>Eliminar</strong> en la entrada (juego o máquina) y confirma la acción.</li>
        <li>Se crea automáticamente una copia <code>.bak</code> antes de escribir cambios.</li>
      </ol>
      <p><small><strong>Nota:</strong> en ficheros <strong>MAME</strong> la eliminación individual está <strong>deshabilitada</strong>. Solo se permite buscar y editar.</small></p>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-masivo">
      <h4>6) Eliminación masiva</h4>
      <ol>
        <li>Define <em>Regiones/países a incluir</em> y <em>Idiomas a excluir</em>.</li>
        <li>La búsqueda contempla <strong>juegos y máquinas</strong>:
          <ul>
            <li>Juegos: <code>name</code>, <code>description</code>, <code>category</code>.</li>
            <li>Máquinas: <code>name</code>, <code>description</code>, <code>year</code>, <code>manufacturer</code>.</li>
          </ul>
        </li>
        <li>Usa <strong>Contar coincidencias</strong> para previsualizar el impacto (no borra nada).</li>
        <li>Si estás conforme, pulsa <strong>Eliminar filtrados</strong>.</li>
      </ol>
      <p><small><strong>Importante:</strong> la eliminación masiva <strong>no está disponible</strong> para ficheros <strong>MAME</strong>.</small></p>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-busqueda">
      <h4>7) Búsqueda</h4>
      <ul>
        <li><strong>Índice</strong>:
          <ul>
            <li><em>Búsqueda general</em>: filtra por <strong>nombre</strong>, <strong>descripción</strong> y <strong>categoría</strong> antes de paginar. El término se conserva al navegar entre páginas o cambiar "Mostrar N".</li>
            <li><em>MAME (buscar)</em>: buscador específico por <strong>nombre</strong>, <strong>ROM</strong> y <strong>hash</strong>. No hay eliminación masiva ni botones de eliminar.</li>
          </ul>
        </li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-compactar">
      <h4>8) Guardar / Compactar XML</h4>
      <ul>
        <li>Reescribe el fichero con indentación consistente y sin líneas en blanco entre elementos.</li>
        <li>Recomendado tras operaciones masivas para mantener el XML ordenado.</li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-restaurar">
      <h4>9) Restaurar desde .bak</h4>
      <ul>
        <li>Usa el botón <strong>Restaurar desde .bak</strong> para volver al estado anterior si algo no salió como esperabas.</li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-consejos">
      <h4>10) Consejos y atajos</h4>
      <ul>
        <li>Para colecciones grandes, reduce los elementos por página para una navegación fluida.</li>
        <li>Si detectas espacios o líneas en blanco, ejecuta <strong>Guardar / Compactar XML</strong>.</li>
        <li>Si la sesión expira o el navegador bloquea cookies, vuelve a cargar la página y reintenta.</li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>

    <section id="h-soporte">
      <h4>11) Soporte</h4>
      <ul>
        <li>Consulta el <strong>README</strong> y el <strong>CHANGELOG</strong> para detalles técnicos y novedades.</li>
        <li>¿Dudas o problemas? Abre un Issue: <a href="https://github.com/scorpio21/editor_Xml/issues/new/choose" target="_blank" rel="noopener">Crear issue en GitHub</a>.</li>
      </ul>
      <p><a href="#helpTitle">Volver al índice</a></p>
    </section>
  </div>
</div>
