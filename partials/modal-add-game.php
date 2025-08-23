<?php
declare(strict_types=1);
?>
<div id="addGameModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addGameTitle" style="display:none">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="addGameTitle">Añadir juego</h3>
      <button type="button" class="close" aria-label="Cerrar" onclick="closeAddModal()">×</button>
    </div>
    <div class="modal-body">
      <form id="add-game-form" method="post">
        <input type="hidden" name="action" value="add_game">
        <div class="form-row">
          <label>ROMs del juego</label>
          <div id="roms-container">
            <div class="rom-row" data-index="0">
              <div class="rom-file">
                <input type="file" class="ag_file" accept="*/*">
                <button type="button" class="ag_calc_btn secondary">Calcular hashes</button>
              </div>
              <div class="rom-fields">
                <input type="text" class="ag_rom" name="rom_name[]" placeholder="Nombre de ROM" required>
                <input type="text" class="ag_size" name="size[]" placeholder="Tamaño (bytes)" required>
                <input type="text" class="ag_crc" name="crc[]" placeholder="CRC32 (8 hex)" required>
                <input type="text" class="ag_md5" name="md5[]" placeholder="MD5 (32 hex)" required>
                <input type="text" class="ag_sha1" name="sha1[]" placeholder="SHA1 (40 hex)" required>
                <button type="button" class="ag_remove_btn danger" aria-label="Eliminar esta ROM">Eliminar ROM</button>
              </div>
            </div>
          </div>
          <button type="button" id="ag_add_rom_btn" class="secondary">Añadir ROM</button>
        </div>
        <div class="form-row">
          <label for="ag_name">Nombre del juego</label>
          <input type="text" id="ag_name" name="game_name" required>
        </div>
        <div class="form-row">
          <label for="ag_desc">Descripción</label>
          <textarea id="ag_desc" name="description" rows="3" required></textarea>
        </div>
        <div class="form-row">
          <label for="ag_cat">Categoría</label>
          <input type="text" id="ag_cat" name="category" placeholder="Opcional">
        </div>
        <hr>
        <div class="modal-actions">
          <button type="button" class="secondary" onclick="closeAddModal()">Cancelar</button>
          <button type="submit">Añadir</button>
        </div>
      </form>
      <p class="hint"><small>Puedes añadir múltiples ROMs. En cada fila, selecciona archivo y pulsa "Calcular hashes" para completar los campos.</small></p>
    </div>
  </div>
</div>
