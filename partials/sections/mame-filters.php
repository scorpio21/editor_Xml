<?php
declare(strict_types=1);
// Filtros específicos MAME (parcial reutilizable)
// Puede usarse dentro de un form de eliminación masiva o como vista informativa en la pestaña 4.

$bf = $_SESSION['bulk_filters'] ?? [
    'include'=>'','exclude'=>'','include_regions'=>[],'exclude_langs'=>[],
    'mame_filters'=>[],'mame_driver_status'=>'','mame_clone_filter'=>''
];
?>
<div class="fields mame-filters">
  <h3>Filtros específicos MAME (opcional)</h3>
  <div class="mame-row">
    <label>
      <input type="checkbox" name="mame_filter_bios" value="1" <?= in_array('bios', $bf['mame_filters'] ?? [], true) ? 'checked' : '' ?>>
      Excluir BIOS (isbios="yes")
    </label>
    <label>
      <input type="checkbox" name="mame_filter_device" value="1" <?= in_array('device', $bf['mame_filters'] ?? [], true) ? 'checked' : '' ?>>
      Excluir dispositivos (isdevice="yes")
    </label>
  </div>
  <div class="mame-row">
    <label>Estado del driver:</label>
    <select name="mame_driver_status">
      <option value="">Cualquiera</option>
      <option value="good" <?= ($bf['mame_driver_status'] ?? '') === 'good' ? 'selected' : '' ?>>Bueno</option>
      <option value="imperfect" <?= ($bf['mame_driver_status'] ?? '') === 'imperfect' ? 'selected' : '' ?>>Imperfecto</option>
      <option value="preliminary" <?= ($bf['mame_driver_status'] ?? '') === 'preliminary' ? 'selected' : '' ?>>Preliminar</option>
    </select>
  </div>
  <div class="mame-row">
    <label>Filtrar por cloneof:</label>
    <select name="mame_clone_filter">
      <option value="">Cualquiera</option>
      <option value="parent_only" <?= ($bf['mame_clone_filter'] ?? '') === 'parent_only' ? 'selected' : '' ?>>Solo padres (sin cloneof)</option>
      <option value="clone_only" <?= ($bf['mame_clone_filter'] ?? '') === 'clone_only' ? 'selected' : '' ?>>Solo clones (con cloneof)</option>
    </select>
  </div>
  <p class="hint">Estos filtros solo se aplican a nodos &lt;machine&gt; con atributos MAME.</p>
</div>
