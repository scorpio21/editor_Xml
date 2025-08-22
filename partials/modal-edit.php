<?php
declare(strict_types=1);
?>
<!-- Modal editar -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Editar juego</h3>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="index" id="editIndex">
            
            <label>Nombre del juego:</label>
            <input type="text" name="game_name" id="editGameName">
            
            <label>Descripción:</label>
            <textarea name="description" id="editDescription" rows="3"></textarea>
            
            <label>Categoría:</label>
            <input type="text" name="category" id="editCategory">
            
            <label>Rom Name:</label>
            <input type="text" name="rom_name" id="editRomName">
            
            <label>Tamaño:</label>
            <input type="text" name="size" id="editSize">
            
            <label>CRC:</label>
            <input type="text" name="crc" id="editCrc">
            
            <label>MD5:</label>
            <input type="text" name="md5" id="editMd5">
            
            <label>SHA1:</label>
            <input type="text" name="sha1" id="editSha1">
            
            <br><br>
            <button type="submit">Guardar cambios</button>
        </form>
    </div>
</div>
