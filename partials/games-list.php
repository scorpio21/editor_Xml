<?php
declare(strict_types=1);
?>
<?php $entries = $xml->xpath('/datafile/*[self::game or self::machine]') ?: []; ?>
<h2>Lista de juegos/máquinas (<?= count($entries) ?>)</h2>

<?php
    // Parámetros de paginación (GET)
    $total = count($entries);
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    if (!in_array($perPage, [10, 25, 50, 100], true)) { $perPage = 10; }
    $pages = max(1, (int)ceil($total / $perPage));
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    if ($page > $pages) { $page = $pages; }
    $start = ($page - 1) * $perPage;
    $end = min($total - 1, $start + $perPage - 1);
?>

<form method="get" class="per-page-form">
    <label for="per_page">Mostrar</label>
    <select id="per_page" name="per_page" onchange="this.form.submit()">
        <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
        <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
        <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
        <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
    </select>
    <noscript><button type="submit">Aplicar</button></noscript>
    <?php if ($page > 1): ?><input type="hidden" name="page" value="<?= $page ?>"><?php endif; ?>
</form>
<div class="list-meta">Mostrando <?= $total > 0 ? ($start + 1) : 0 ?>–<?= $total > 0 ? ($end + 1) : 0 ?> de <?= $total ?></div>
<div class="game-grid">
    <?php $idx = 0; $gameIdx = 0; $machineIdx = 0; foreach ($entries as $entry): ?>
        <?php if ($idx < $start) { $idx++; if ($entry->getName()==='game') { $gameIdx++; } continue; } ?>
        <?php if ($idx > $end) { break; } ?>
        <?php $isMachine = ($entry->getName() === 'machine'); ?>
        <?php $firstRom = $entry->rom[0] ?? null; ?>
        <?php
            // Construir JSON de ROMs para data-roms
            $romArr = [];
            foreach ($entry->rom as $r) {
                $romArr[] = [
                    'name' => (string)$r['name'],
                    'size' => (string)$r['size'],
                    'crc'  => (string)$r['crc'],
                    'md5'  => (string)$r['md5'],
                    'sha1' => (string)$r['sha1'],
                ];
            }
            $romsJson = htmlspecialchars(json_encode($romArr, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        ?>
        <div class="game" id="<?= $isMachine ? 'machine-'.$machineIdx : 'game-'.$gameIdx ?>"
             data-name="<?= htmlspecialchars((string)$entry['name']) ?>"
             data-description="<?= htmlspecialchars((string)$entry->description) ?>"
             data-category="<?= $isMachine ? '' : htmlspecialchars((string)$entry->category) ?>"
             data-type="<?= $isMachine ? 'machine' : 'game' ?>"
             data-roms='<?= $romsJson ?>'
             data-romname="<?= $firstRom ? htmlspecialchars((string)$firstRom['name']) : '' ?>"
             data-size="<?= $firstRom ? htmlspecialchars((string)$firstRom['size']) : '' ?>"
             data-crc="<?= $firstRom ? htmlspecialchars((string)$firstRom['crc']) : '' ?>"
             data-md5="<?= $firstRom ? htmlspecialchars((string)$firstRom['md5']) : '' ?>"
             data-sha1="<?= $firstRom ? htmlspecialchars((string)$firstRom['sha1']) : '' ?>">
            <div class="game-info"><strong>Tipo:</strong> <?= $isMachine ? 'machine' : 'game' ?></div>
            <div class="game-info"><strong>Nombre:</strong> <?= htmlspecialchars((string)$entry['name']) ?></div>
            <div class="game-info"><strong>Descripción:</strong> <?= htmlspecialchars((string)$entry->description) ?></div>
            <?php if ($isMachine): ?>
                <div class="game-info"><strong>Año:</strong> <?= htmlspecialchars((string)$entry->year) ?></div>
                <div class="game-info"><strong>Fabricante:</strong> <?= htmlspecialchars((string)$entry->manufacturer) ?></div>
            <?php endif; ?>
            <div class="game-info"><strong>Categoría:</strong> <?= $isMachine ? '—' : htmlspecialchars((string)$entry->category) ?></div>
            <?php if (count($entry->rom) > 0): ?>
                <div class="game-roms">
                    <strong>ROMs:</strong>
                    <ul>
                        <?php foreach ($entry->rom as $rom): ?>
                            <li>
                                <div><strong>Nombre:</strong> <?= htmlspecialchars((string)$rom['name']) ?></div>
                                <div><strong>Tamaño:</strong> <?= htmlspecialchars((string)$rom['size']) ?></div>
                                <div><strong>CRC:</strong> <?= htmlspecialchars((string)$rom['crc']) ?></div>
                                <div><strong>MD5:</strong> <?= htmlspecialchars((string)$rom['md5']) ?></div>
                                <div><strong>SHA1:</strong> <?= htmlspecialchars((string)$rom['sha1']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="game-info"><strong>ROMs:</strong> N/A</div>
            <?php endif; ?>

            <div class="game-actions">
                <?php if (!$isMachine): ?>
                    <button onclick="openEditModal(<?= $gameIdx ?>)">Editar</button>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="index" value="<?= $gameIdx ?>">
                        <button type="submit" onclick="return confirm('¿Eliminar este juego?')">Eliminar</button>
                    </form>
                <?php else: ?>
                    <button onclick="openEditModalMachine(<?= $machineIdx ?>)">Editar</button>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="node_type" value="machine">
                        <input type="hidden" name="index" value="<?= $machineIdx ?>">
                        <button type="submit" onclick="return confirm('¿Eliminar esta máquina?')">Eliminar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php $idx++; if ($isMachine) { $machineIdx++; } else { $gameIdx++; } endforeach; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a class="page-link" href="?page=<?= $page - 1 ?>&per_page=<?= $perPage ?>">&laquo; Anterior</a>
    <?php endif; ?>
    <span class="page-info">Página <?= $page ?> de <?= $pages ?></span>
    <form method="get" class="page-jump">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <label for="goto">Ir a</label>
        <input id="goto" name="page" type="number" min="1" max="<?= $pages ?>" value="<?= $page ?>">
        <button type="submit">Ir</button>
    </form>
    <?php if ($page < $pages): ?>
        <a class="page-link" href="?page=<?= $page + 1 ?>&per_page=<?= $perPage ?>">Siguiente &raquo;</a>
    <?php endif; ?>
</div>
