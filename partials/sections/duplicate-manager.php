<?php
// Interfaz de gestión de duplicados
// Requiere que $xml esté disponible
?>

<div class="duplicate-manager">
    <p class="hint">
        Detecta juegos duplicados agrupados por nombre base (ignorando región, idiomas y revisiones).
        Selecciona cuál mantener en cada grupo y genera un nuevo XML sin los duplicados marcados.
    </p>

    <form id="duplicates-form" method="post" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarTokenCsrf()) ?>">
        <input type="hidden" name="action" value="export_xml_without_duplicates">
        <!-- Los delete_indices[] se crearán dinámicamente en JS -->

        <div class="duplicate-actions">
            <button type="button" id="detect-duplicates-btn" class="primary">
                Detectar Duplicados
            </button>
            <button type="button" id="export-dup-csv-btn" class="secondary" disabled>
                Exportar a CSV
            </button>
            <button type="button" id="select-all-originals-btn" class="secondary" disabled
                title="Selecciona automáticamente versiones sin idiomas específicos">
                Sugerir Originales
            </button>
            <button type="button" id="select-all-spanish-btn" class="secondary" disabled
                title="Selecciona automáticamente versiones con español">
                Sugerir Español
            </button>
            <button type="button" id="select-latest-rev-btn" class="secondary" disabled
                title="Selecciona automáticamente la revisión más alta">
                Sugerir Última Rev
            </button>
        </div>

        <div id="duplicate-groups-container" style="margin-top: 1.5rem;">
            <p class="hint" id="no-duplicates-msg">
                Haz clic en "Detectar Duplicados" para comenzar.
            </p>
        </div>

        <div id="duplicate-export-section" style="margin-top: 1.5rem; display: none;">
            <p class="hint">
                <strong id="duplicates-count-text"></strong>
            </p>
            <button type="submit" class="primary" id="export-xml-btn" style="margin-right: 1rem;">
                Generar XML sin Duplicados (Descargar)
            </button>
            <button type="submit" class="danger" id="delete-from-current-btn" name="delete_current"
                onclick="return confirm('¿Estás seguro? Esta acción eliminará los duplicados directamente del archivo cargado en el servidor. Se recomienda exportar una copia de seguridad primero.');">
                Eliminar Seleccionados del Fichero Actual
            </button>
        </div>
    </form>
</div>

<style>
    .duplicate-manager {
        max-width: 1200px;
        margin: 0 auto;
    }

    .duplicate-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .duplicate-group {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .duplicate-group h4 {
        margin: 0 0 0.75rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dup-count {
        background: #007bff;
        color: white;
        padding: 0.15rem 0.5rem;
        border-radius: 3px;
        font-size: 0.85rem;
        font-weight: normal;
    }

    .duplicate-items {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .duplicate-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem;
        background: white;
        border: 2px solid #e0e0e0;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .duplicate-item:hover {
        border-color: #007bff;
        background: #f0f8ff;
    }

    .duplicate-item input[type="radio"] {
        margin-top: 0.25rem;
        cursor: pointer;
    }

    .duplicate-item input[type="radio"]:checked+.item-details {
        font-weight: 600;
    }

    .duplicate-item.selected {
        border-color: #28a745;
        background: #f0fff4;
    }

    .item-details {
        flex: 1;
    }

    .item-name {
        display: block;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .item-meta {
        display: block;
        font-size: 0.85rem;
        color: #666;
    }

    .item-meta .badge {
        display: inline-block;
        background: #6c757d;
        color: white;
        padding: 0.1rem 0.4rem;
        border-radius: 3px;
        margin-right: 0.25rem;
        font-size: 0.75rem;
    }

    .item-meta .badge.spanish {
        background: #28a745;
    }

    .item-meta .badge.revision {
        background: #ffc107;
        color: #000;
    }

    /* Estilos para botones */
    button.primary,
    button.secondary,
    button.danger {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background-color 0.2s;
    }

    button.primary {
        background-color: #007bff;
        color: white;
    }

    button.primary:hover {
        background-color: #0056b3;
    }

    button.secondary {
        background-color: #6c757d;
        color: white;
    }

    button.secondary:hover {
        background-color: #545b62;
    }

    button.danger {
        background-color: #d9534f;
        color: white;
    }

    button.danger:hover {
        background-color: #c9302c;
    }

    button:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
</style>