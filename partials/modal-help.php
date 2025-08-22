<?php
declare(strict_types=1);
?>
<!-- Modal ayuda -->
<div class="modal" id="helpModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
  <div class="modal-content" role="document">
    <button type="button" class="close" aria-label="Cerrar" onclick="closeHelpModal()">&times;</button>
    <h3 id="helpTitle">Ayuda del Editor XML</h3>

    <nav aria-label="Índice de ayuda" style="margin-bottom:10px;">
      <ol>
        <li><a href="#h-requisitos">Requisitos</a></li>
        <li><a href="#h-cargar">Cargar un archivo</a></li>
        <li><a href="#h-listado">Explorar y paginar</a></li>
        <li><a href="#h-editar">Editar un juego</a></li>
        <li><a href="#h-eliminar">Eliminar un juego</a></li>
        <li><a href="#h-masivo">Eliminación masiva</a></li>
        <li><a href="#h-compactar">Guardar / Compactar XML</a></li>
        <li><a href="#h-restaurar">Restaurar desde .bak</a></li>
        <li><a href="#h-consejos">Consejos</a></li>
      </ol>
    </nav>

    <section id="h-requisitos">
      <h4>1) Requisitos</h4>
      <ul>
        <li>Archivo válido XML/DAT de tipo <code>datafile</code> (cabecera y juegos).</li>
        <li>No exceder el tamaño permitido por el servidor (usa paginación para colecciones grandes).</li>
      </ul>
    </section>

    <section id="h-cargar">
      <h4>2) Cargar un archivo</h4>
      <ol>
        <li>En <strong>Subir fichero XML o DAT</strong>, selecciona tu archivo.</li>
        <li>Pulsa <strong>Cargar XML/DAT</strong>.</li>
        <li>Si es válido, verás la <strong>cabecera del fichero</strong> y el listado paginado.</li>
      </ol>
    </section>

    <section id="h-listado">
      <h4>3) Explorar y paginar</h4>
      <ul>
        <li>Usa <strong>Anterior / Siguiente</strong> o el formulario <strong>Ir a</strong> para navegar páginas.</li>
        <li>Ajusta <strong>Elementos por página</strong> (10/25/50/100) según tu preferencia.</li>
      </ul>
    </section>

    <section id="h-editar">
      <h4>4) Editar un juego</h4>
      <ol>
        <li>Pulsa <strong>Editar</strong> en el juego.</li>
        <li>Modifica los campos necesarios y pulsa <strong>Guardar cambios</strong>.</li>
        <li>El XML se reescribe con formato limpio.</li>
      </ol>
    </section>

    <section id="h-eliminar">
      <h4>5) Eliminar un juego</h4>
      <ol>
        <li>Pulsa <strong>Eliminar</strong> en el juego.</li>
        <li>El cambio se guarda y se hace una copia <code>.bak</code> antes de escribir.</li>
      </ol>
    </section>

    <section id="h-masivo">
      <h4>6) Eliminación masiva</h4>
      <ol>
        <li>Define <em>Regiones/países a incluir</em> y <em>Idiomas a excluir</em>.</li>
        <li>Pulsa <strong>Contar coincidencias</strong> para previsualizar el impacto.</li>
        <li>Si estás conforme, pulsa <strong>Eliminar filtrados</strong>.</li>
      </ol>
      <p><small>Tras una eliminación masiva exitosa, aparecerá el botón <em>Guardar / Compactar XML</em>.</small></p>
    </section>

    <section id="h-compactar">
      <h4>7) Guardar / Compactar XML</h4>
      <ul>
        <li>Reescribe el fichero con indentación consistente y sin líneas en blanco entre elementos.</li>
        <li>Útil para dejar el archivo ordenado tras operaciones masivas.</li>
      </ul>
    </section>

    <section id="h-restaurar">
      <h4>8) Restaurar desde .bak</h4>
      <ul>
        <li>Usa el botón <strong>Restaurar desde .bak</strong> para volver al estado anterior si algo salió mal.</li>
      </ul>
    </section>

    <section id="h-consejos">
      <h4>9) Consejos</h4>
      <ul>
        <li>Usa la búsqueda del navegador (Ctrl/Cmd + F) en el listado.</li>
        <li>Para colecciones grandes, reduce elementos por página para una navegación fluida.</li>
        <li>Si ves líneas en blanco en el XML, usa <strong>Guardar / Compactar XML</strong>.</li>
      </ul>
    </section>
  </div>
</div>
