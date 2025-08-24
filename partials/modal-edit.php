<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/csrf-helper.php';
?>
<!-- Modal editar -->
<div class="modal" id="editModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="editTitle" aria-describedby="editRomsLegend">
    <div class="modal-content">
        <button type="button" class="close" aria-label="Cerrar" onclick="closeModal()">&times;</button>
        <h3 id="editTitle">Editar entrada</h3>
        <form method="post" id="edit-game-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="index" id="editIndex">
            <input type="hidden" name="node_type" id="editNodeType">
            <?= campoCSRF() ?>

            <label>Nombre:</label>
            <input type="text" name="game_name" id="editGameName" required>

            <label>Descripción:</label>
            <textarea name="description" id="editDescription" rows="3" required></textarea>

            <label>Categoría (solo game):</label>
            <input type="text" name="category" id="editCategory" placeholder="(para 'machine' se ignora)">

            <fieldset>
                <legend id="editRomsLegend">ROMs</legend>
                <div id="edit-roms-container" class="roms-container">
                    <!-- filas dinámicas -->
                </div>
                <button type="button" id="edit_add_rom_btn" class="secondary">Añadir ROM</button>
                <p class="hint">Puedes añadir, eliminar y recalcular hashes por ROM. Para calcular hashes, selecciona el archivo correspondiente.</p>
            </fieldset>

            <div class="modal-actions">
                <button type="submit">Guardar cambios</button>
            </div>
        </form>
    </div>
    </div>
