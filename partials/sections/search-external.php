<?php
declare(strict_types=1);
?>
<div id="search-external" class="search-external" aria-labelledby="tab-btn-6">
  <p class="hint">Busca juegos en webs externas por nombre o por hash (MD5/SHA1/CRC). Se abrirán enlaces de búsqueda en nuevas pestañas.</p>

  <form id="search-external-form" class="search-external-form" onsubmit="return false" novalidate>
    <fieldset>
      <legend>Datos de búsqueda</legend>
      <div class="form-row">
        <label for="se-name">Nombre del juego</label>
        <input type="text" id="se-name" name="name" placeholder="Ej.: Super Mario World">
      </div>
      <div class="form-row">
        <label for="se-md5">MD5</label>
        <input type="text" id="se-md5" name="md5" maxlength="32" placeholder="32 hex (opcional)">
      </div>
      <div class="form-row">
        <label for="se-sha1">SHA1</label>
        <input type="text" id="se-sha1" name="sha1" maxlength="40" placeholder="40 hex (opcional)">
      </div>
      <div class="form-row">
        <label for="se-crc">CRC</label>
        <input type="text" id="se-crc" name="crc" maxlength="8" placeholder="8 hex (opcional)">
      </div>
    </fieldset>

    <div class="actions">
      <button type="button" id="se-build-links" class="primary">Generar enlaces</button>
      <button type="button" id="se-open-all" class="secondary" disabled>Abrir todas</button>
      <span id="se-errors" class="form-errors" aria-live="polite"></span>
    </div>
  </form>

  <div class="results" id="se-results" hidden>
    <h4>Enlaces de búsqueda</h4>
    <ul id="se-links" class="links-list"></ul>
    <p class="hint">Consejo: algunos sitios no soportan búsqueda por hash directa; por eso usamos búsquedas "site:".</p>
  </div>
</div>
