<?php
declare(strict_types=1);
?>
<?php
    // Construir lista completa con índice absoluto por tipo en una sola pasada
    $children = $xml->xpath('/datafile/*[self::game or self::machine]') ?: [];
    $all = [];
    $absGame = 0; $absMachine = 0;
    foreach ($children as $node) {
        $isMachine = ($node->getName() === 'machine');
        $all[] = [
            'el' => $node,
            'type' => $isMachine ? 'machine' : 'game',
            'absIndex' => $isMachine ? $absMachine++ : $absGame++,
        ];
    }

    // Filtro de búsqueda por nombre/descripcion/categoría (GET q) y extensiones a ROMs/hashes
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $qInRoms = isset($_GET['q_in_roms']) && $_GET['q_in_roms'] === '1';
    $qInHashes = isset($_GET['q_in_hashes']) && $_GET['q_in_hashes'] === '1';
    $entries = $all;
    if ($q !== '') {
        $qUpper = mb_strtoupper($q, 'UTF-8');
        $terms = array_values(array_filter(preg_split('/\s+/', $q)));
        $hasSpace = mb_strpos($qUpper, ' ', 0, 'UTF-8') !== false;
        // Normalización para hashes (quitar separadores comunes)
        $qHash = strtoupper(str_replace([' ', '-', '_'], '', $q));
        $entries = array_values(array_filter($all, static function($item) use ($terms, $qUpper, $hasSpace, $qInRoms, $qInHashes, $qHash) {
            $e = $item['el'];
            // Coincidencia base por nombre
            $name = (string)($e['name'] ?? '');
            $hayName = mb_strtoupper($name, 'UTF-8');
            $matchBase = false;
            if ($hasSpace) {
                if (mb_strpos($hayName, $qUpper, 0, 'UTF-8') !== false) { $matchBase = true; }
                if (!$matchBase) {
                    $all = true;
                    foreach ($terms as $t) {
                        $t = mb_strtoupper((string)$t, 'UTF-8');
                        if ($t === '' || mb_strpos($hayName, $t, 0, 'UTF-8') === false) { $all = false; break; }
                    }
                    $matchBase = $all;
                }
            } else {
                $all = true;
                foreach ($terms as $t) {
                    $t = mb_strtoupper((string)$t, 'UTF-8');
                    if ($t === '' || mb_strpos($hayName, $t, 0, 'UTF-8') === false) { $all = false; break; }
                }
                $matchBase = $all;
            }

            // Coincidencia en ROMs por nombre
            $matchRoms = false;
            if ($qInRoms && isset($e->rom)) {
                foreach ($e->rom as $rom) {
                    $romName = (string)($rom['name'] ?? '');
                    $hayRom = mb_strtoupper($romName, 'UTF-8');
                    if ($hasSpace) {
                        if (mb_strpos($hayRom, $qUpper, 0, 'UTF-8') !== false) { $matchRoms = true; break; }
                        $all = true;
                        foreach ($terms as $t) {
                            $t = mb_strtoupper((string)$t, 'UTF-8');
                            if ($t === '' || mb_strpos($hayRom, $t, 0, 'UTF-8') === false) { $all = false; break; }
                        }
                        if ($all) { $matchRoms = true; break; }
                    } else {
                        $all = true;
                        foreach ($terms as $t) {
                            $t = mb_strtoupper((string)$t, 'UTF-8');
                            if ($t === '' || mb_strpos($hayRom, $t, 0, 'UTF-8') === false) { $all = false; break; }
                        }
                        if ($all) { $matchRoms = true; break; }
                    }
                }
            }

            // Coincidencia en hashes: CRC/MD5/SHA1 (subcadena normalizada)
            $matchHashes = false;
            if ($qInHashes && isset($e->rom)) {
                foreach ($e->rom as $rom) {
                    $crc  = strtoupper((string)($rom['crc'] ?? ''));
                    $md5  = strtoupper((string)($rom['md5'] ?? ''));
                    $sha1 = strtoupper((string)($rom['sha1'] ?? ''));
                    $hay = str_replace([' ', '-', '_'], '', $crc . $md5 . $sha1);
                    if ($qHash !== '' && strpos($hay, $qHash) !== false) { $matchHashes = true; break; }
                }
            }

            return $matchBase || $matchRoms || $matchHashes;
        }));
    }
?>
<?php
    // Mapear índices absolutos por tipo (game/machine) para operaciones que requieren índice del XML completo
    $allGames = $xml->xpath('/datafile/game') ?: [];
    $allMachines = $xml->xpath('/datafile/machine') ?: [];
    $mapGame = [];
    foreach ($allGames as $i => $g) { $mapGame[spl_object_id($g)] = $i; }
    $mapMachine = [];
    foreach ($allMachines as $i => $m) { $mapMachine[spl_object_id($m)] = $i; }
?>
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

<!-- Buscador -->
<form method="get" class="search-form">
    <label for="q">Buscar</label>
    <input id="q" name="q" type="text" value="<?= htmlspecialchars($q) ?>" placeholder="Nombre (juego/máquina), ROM o hash">
    <div class="search-options">
        <label><input type="checkbox" name="q_in_roms" value="1" <?= $qInRoms ? 'checked' : '' ?>> Buscar en ROMs</label>
        <label><input type="checkbox" name="q_in_hashes" value="1" <?= $qInHashes ? 'checked' : '' ?>> Buscar en hashes (CRC/MD5/SHA1)</label>
    </div>
    <?php if ($perPage !== 10): ?><input type="hidden" name="per_page" value="<?= $perPage ?>"><?php endif; ?>
    <button type="submit">Buscar</button>
</form>

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
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
    <?php if ($qInRoms): ?><input type="hidden" name="q_in_roms" value="1"><?php endif; ?>
    <?php if ($qInHashes): ?><input type="hidden" name="q_in_hashes" value="1"><?php endif; ?>
</form>

<!-- Cabecera del fichero: debajo del buscador -->
<h2>Cabecera del fichero</h2>
<div class="game">
    <?php if (isset($xml->header)): ?>
        <div class="game-info"><strong>Nombre:</strong> <?= htmlspecialchars((string)($xml->header->name ?? '')) ?: 'N/A' ?></div>
        <div class="game-info"><strong>Descripción:</strong> <?= htmlspecialchars((string)($xml->header->description ?? '')) ?: 'N/A' ?></div>
        <div class="game-info"><strong>Versión:</strong> <?= htmlspecialchars((string)($xml->header->version ?? '')) ?: 'N/A' ?></div>
        <div class="game-info"><strong>Fecha:</strong> <?= htmlspecialchars((string)($xml->header->date ?? '')) ?: 'N/A' ?></div>
        <div class="game-info"><strong>Autor:</strong> <?= htmlspecialchars((string)($xml->header->author ?? '')) ?: 'N/A' ?></div>
        <div class="game-info"><strong>Web:</strong>
            <?php if (!empty((string)($xml->header->url ?? ''))): ?>
                <a href="<?= htmlspecialchars((string)($xml->header->url ?? '')) ?>" target="_blank"><?= htmlspecialchars((string)($xml->header->homepage ?? 'Enlace')) ?></a>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="game-info">No hay información de cabecera disponible</div>
    <?php endif; ?>
</div>

<div class="list-meta">Mostrando <?= $total > 0 ? ($start + 1) : 0 ?>–<?= $total > 0 ? ($end + 1) : 0 ?> de <?= $total ?></div>
<div class="game-grid">
    <?php $idx = 0; $gameIdx = 0; $machineIdx = 0; foreach ($entries as $entry): ?>
        <?php $node = $entry['el']; $isMachine = ($entry['type'] === 'machine'); ?>
        <?php if ($idx < $start) { $idx++; if (!$isMachine) { $gameIdx++; } else { $machineIdx++; } continue; } ?>
        <?php if ($idx > $end) { break; } ?>
        <?php $firstRom = $node->rom[0] ?? null; ?>
        <?php
            // Construir JSON de ROMs para data-roms
            $romArr = [];
            foreach ($node->rom as $r) {
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
        <?php $absIndex = (int)$entry['absIndex']; ?>
        <div class="game" id="<?= $isMachine ? 'machine-'.$machineIdx : 'game-'.$gameIdx ?>"
             data-name="<?= htmlspecialchars((string)$node['name']) ?>"
             data-description="<?= htmlspecialchars((string)$node->description) ?>"
             data-category="<?= $isMachine ? '' : htmlspecialchars((string)$node->category) ?>"
             data-type="<?= $isMachine ? 'machine' : 'game' ?>"
             data-absindex="<?= $absIndex ?>"
             data-roms='<?= $romsJson ?>'
             data-romname="<?= $firstRom ? htmlspecialchars((string)$firstRom['name']) : '' ?>"
             data-size="<?= $firstRom ? htmlspecialchars((string)$firstRom['size']) : '' ?>"
             data-crc="<?= $firstRom ? htmlspecialchars((string)$firstRom['crc']) : '' ?>"
             data-md5="<?= $firstRom ? htmlspecialchars((string)$firstRom['md5']) : '' ?>"
             data-sha1="<?= $firstRom ? htmlspecialchars((string)$firstRom['sha1']) : '' ?>">
            <div class="game-info"><strong>Tipo:</strong> <?= $isMachine ? 'machine' : 'game' ?></div>
            <div class="game-info"><strong>Nombre:</strong> <?= htmlspecialchars((string)$node['name']) ?></div>
            <div class="game-info"><strong>Descripción:</strong> <?= htmlspecialchars((string)$node->description) ?></div>
            <?php if ($isMachine): ?>
                <div class="game-info"><strong>Año:</strong> <?= htmlspecialchars((string)$node->year) ?></div>
                <div class="game-info"><strong>Fabricante:</strong> <?= htmlspecialchars((string)$node->manufacturer) ?></div>
            <?php endif; ?>
            <div class="game-info"><strong>Categoría:</strong> <?= $isMachine ? '—' : htmlspecialchars((string)$node->category) ?></div>
            <?php if (count($node->rom) > 0): ?>
                <div class="game-roms">
                    <strong>ROMs:</strong>
                    <ul>
                        <?php foreach ($node->rom as $rom): ?>
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
                        <input type="hidden" name="index" value="<?= $absIndex ?>">
                        <?= campoCSRF() ?>
                        <button type="submit" onclick="return confirm('¿Eliminar este juego?')">Eliminar</button>
                    </form>
                <?php else: ?>
                    <button onclick="openEditModalMachine(<?= $machineIdx ?>)">Editar</button>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="node_type" value="machine">
                        <input type="hidden" name="index" value="<?= $absIndex ?>">
                        <?= campoCSRF() ?>
                        <button type="submit" onclick="return confirm('¿Eliminar esta máquina?')">Eliminar</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php $idx++; if ($isMachine) { $machineIdx++; } else { $gameIdx++; } endforeach; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a class="page-link" href="?page=<?= $page - 1 ?>&per_page=<?= $perPage ?><?= $q !== '' ? '&q='.urlencode($q) : '' ?>">&laquo; Anterior</a>
    <?php endif; ?>
    <span class="page-info">Página <?= $page ?> de <?= $pages ?></span>
    <form method="get" class="page-jump">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
        <label for="goto">Ir a</label>
        <input id="goto" name="page" type="number" min="1" max="<?= $pages ?>" value="<?= $page ?>">
        <button type="submit">Ir</button>
    </form>
    <?php if ($page < $pages): ?>
        <a class="page-link" href="?page=<?= $page + 1 ?>&per_page=<?= $perPage ?><?= $q !== '' ? '&q='.urlencode($q) : '' ?>">Siguiente &raquo;</a>
    <?php endif; ?>
</div>
