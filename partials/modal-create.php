<?php
declare(strict_types=1);
?>
<div id="createModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="createModalTitle" style="display:none">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="createModalTitle">Crear nuevo XML</h3>
      <button type="button" class="close" aria-label="Cerrar" onclick="closeCreateModal()">×</button>
    </div>
    <div class="modal-body">
      <form id="create-xml-form" method="post">
        <input type="hidden" name="action" value="create_xml">
        <div class="form-row">
          <label for="cx_name">Nombre del catálogo</label>
          <input type="text" id="cx_name" name="name" required>
        </div>
        <div class="form-row">
          <label for="cx_description">Descripción</label>
          <textarea id="cx_description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-row">
          <label for="cx_version">Versión</label>
          <input type="text" id="cx_version" name="version" value="1.0" required>
        </div>
        <div class="form-row">
          <label for="cx_date">Fecha</label>
          <input type="date" id="cx_date" name="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
        </div>
        <div class="form-row">
          <label for="cx_author">Autor</label>
          <input type="text" id="cx_author" name="author" required>
        </div>
        <div class="form-row">
          <label for="cx_homepage">Título de la web (opcional)</label>
          <input type="text" id="cx_homepage" name="homepage" placeholder="Enlace">
        </div>
        <div class="form-row">
          <label for="cx_url">URL de la web (opcional)</label>
          <input type="url" id="cx_url" name="url" placeholder="https://ejemplo.com">
        </div>
        <div class="modal-actions">
          <button type="button" class="secondary" onclick="closeCreateModal()">Cancelar</button>
          <button type="submit">Crear XML</button>
        </div>
      </form>
    </div>
  </div>
</div>
