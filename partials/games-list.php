<?php
declare(strict_types=1);
?>
<?php
    // Construir lista completa con índice absoluto por tipo en una sola pasada
    $___timing = (isset($_GET['debug']) && $_GET['debug'] === 'timing');
    if ($___timing) { @require_once __DIR__ . '/../inc/logger.php'; }
    $___t0 = $___timing ? microtime(true) : 0.0;
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
    $___tBuild = $___timing ? microtime(true) : 0.0;

    // Filtro de búsqueda por nombre/descripcion/categoría (GET q) y extensiones a ROMs/hashes
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $qInRoms = isset($_GET['q_in_roms']) && $_GET['q_in_roms'] === '1';
    $qInHashes = isset($_GET['q_in_hashes']) && $_GET['q_in_hashes'] === '1';
    $entries = $all;
    if ($q !== '') {
        $qUpper = mb_strtoupper($q, 'UTF-8');
        $terms = array_values(array_filter(preg_split('/\s+/', $q)));
        $termsUpper = array_map(static function($t){ return mb_strtoupper((string)$t, 'UTF-8'); }, $terms);
        $hasSpace = mb_strpos($qUpper, ' ', 0, 'UTF-8') !== false;
        // Normalización para hashes (quitar separadores comunes)
        $qHash = strtoupper(str_replace([' ', '-', '_'], '', $q));
        $entries = array_values(array_filter($all, static function($item) use ($termsUpper, $qUpper, $hasSpace, $qInRoms, $qInHashes, $qHash) {
            $e = $item['el'];
            // Coincidencia base por nombre
            $name = (string)($e['name'] ?? '');
            $hayName = mb_strtoupper($name, 'UTF-8');
            $matchBase = false;
            if ($hasSpace) {
                if (mb_strpos($hayName, $qUpper, 0, 'UTF-8') !== false) { $matchBase = true; }
                if (!$matchBase) {
                    $allOk = true;
                    foreach ($termsUpper as $t) {
                        if ($t === '' || mb_strpos($hayName, $t, 0, 'UTF-8') === false) { $allOk = false; break; }
                    }
                    $matchBase = $allOk;
                }
            } else {
                $allOk = true;
                foreach ($termsUpper as $t) {
                    if ($t === '' || mb_strpos($hayName, $t, 0, 'UTF-8') === false) { $allOk = false; break; }
                }
                $matchBase = $allOk;
            }

            // Coincidencia en ROMs por nombre
            $matchRoms = false;
            if (!$matchBase && $qInRoms && isset($e->rom)) {
                foreach ($e->rom as $rom) {
                    $romName = (string)($rom['name'] ?? '');
                    $hayRom = mb_strtoupper($romName, 'UTF-8');
                    if ($hasSpace) {
                        if (mb_strpos($hayRom, $qUpper, 0, 'UTF-8') !== false) { $matchRoms = true; break; }
                        $allOk = true;
                        foreach ($termsUpper as $t) {
                            if ($t === '' || mb_strpos($hayRom, $t, 0, 'UTF-8') === false) { $allOk = false; break; }
                        }
                        if ($allOk) { $matchRoms = true; break; }
                    } else {
                        $allOk = true;
                        foreach ($termsUpper as $t) {
                            if ($t === '' || mb_strpos($hayRom, $t, 0, 'UTF-8') === false) { $allOk = false; break; }
                        }
                        if ($allOk) { $matchRoms = true; break; }
                    }
                }
            }

            // Coincidencia en hashes: CRC/MD5/SHA1 (subcadena normalizada)
            $matchHashes = false;
            if (!$matchBase && !$matchRoms && $qInHashes && isset($e->rom)) {
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
        // Deduplicar por clave tipo+nombre (case-insensitive)
        $seen = [];
        $entries = array_values(array_filter($entries, static function($item) use (&$seen) {
            $node = $item['el'];
            $type = $item['type'];
            $name = (string)($node['name'] ?? '');
            $key = $type . '|' . mb_strtolower($name, 'UTF-8');
            if (isset($seen[$key])) { return false; }
            $seen[$key] = true;
            return true;
        }));
    }
    $___tFilter = $___timing ? microtime(true) : 0.0;
?>
<h2><?= htmlspecialchars(t('games_list.h2')) ?> (<?= count($entries) ?>)</h2>

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
    // Precalcular contadores por tipo antes de la página actual para ids consistentes
    $preGame = 0; $preMachine = 0;
    if ($start > 0 && $total > 0) {
        $limit = min($start, $total);
        for ($i = 0; $i < $limit; $i++) {
            if (($entries[$i]['type'] ?? '') === 'machine') { $preMachine++; }
            else { $preGame++; }
        }
    }
    // Cortar a los elementos de la página
    $pageEntries = array_slice($entries, $start, $perPage);
    $___tPage = $___timing ? microtime(true) : 0.0;
?>

<!-- Buscador -->
<form method="get" class="search-form">
    <label for="q"><?= htmlspecialchars(t('search.label')) ?></label>
    <input id="q" name="q" type="text" value="<?= htmlspecialchars($q) ?>" placeholder="<?= htmlspecialchars(t('search.placeholder')) ?>">
    <div class="search-options">
        <label><input type="checkbox" name="q_in_roms" value="1" <?= $qInRoms ? 'checked' : '' ?>> <?= htmlspecialchars(t('search.in_roms')) ?></label>
        <label><input type="checkbox" name="q_in_hashes" value="1" <?= $qInHashes ? 'checked' : '' ?>> <?= htmlspecialchars(t('search.in_hashes')) ?></label>
    </div>

<?php if ($___timing):
    $___tEnd = microtime(true);
    $durBuildMs = (int)round(($___tBuild - $___t0) * 1000);
    $durFilterMs = (int)round(($___tFilter - $___tBuild) * 1000);
    $durPageMs = (int)round(($___tPage - $___tFilter) * 1000);
    $durTotalMs = (int)round(($___tEnd - $___t0) * 1000);
    @registrarInfo('games-list', 'timing render', [
        'total_ms' => $durTotalMs,
        'build_ms' => $durBuildMs,
        'filter_ms' => $durFilterMs,
        'paginate_ms' => $durPageMs,
        'total_items' => count($all),
        'filtered' => count($entries),
        'page' => $page,
        'per_page' => $perPage,
    ]);
endif; ?>
    <?php if ($perPage !== 10): ?><input type="hidden" name="per_page" value="<?= $perPage ?>"><?php endif; ?>
    <button type="submit"><?= htmlspecialchars(t('search.button')) ?></button>
</form>

<!-- Exportar resultados filtrados a XML -->
<form method="post" class="inline-form">
    <input type="hidden" name="action" value="export_filtered_xml">
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
    <?php if ($qInRoms): ?><input type="hidden" name="q_in_roms" value="1"><?php endif; ?>
    <?php if ($qInHashes): ?><input type="hidden" name="q_in_hashes" value="1"><?php endif; ?>
    <?= campoCSRF() ?>
    <button type="submit" class="secondary"><?= htmlspecialchars(t('export_xml.button')) ?></button>
    <small class="hint"><?= htmlspecialchars(t('export_xml.hint')) ?></small>
    </form>

<form method="get" class="per-page-form">
    <label for="per_page"><?= htmlspecialchars(t('per_page.label')) ?></label>
    <select id="per_page" name="per_page" onchange="this.form.submit()">
        <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
        <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
        <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
        <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
    </select>
    <noscript><button type="submit"><?= htmlspecialchars(t('per_page.apply')) ?></button></noscript>
    <?php if ($page > 1): ?><input type="hidden" name="page" value="<?= $page ?>"><?php endif; ?>
    <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
    <?php if ($qInRoms): ?><input type="hidden" name="q_in_roms" value="1"><?php endif; ?>
    <?php if ($qInHashes): ?><input type="hidden" name="q_in_hashes" value="1"><?php endif; ?>
</form>

<!-- Cabecera del fichero: debajo del buscador -->
<h2><?= htmlspecialchars(t('header_file.h2')) ?></h2>
<div class="game">
    <?php if (isset($xml->header)): ?>
        <div class="game-info"><strong><?= htmlspecialchars(t('header.name')) ?></strong> <?= htmlspecialchars((string)($xml->header->name ?? '')) ?: htmlspecialchars(t('header.na')) ?></div>
        <div class="game-info"><strong><?= htmlspecialchars(t('header.description')) ?></strong> <?= htmlspecialchars((string)($xml->header->description ?? '')) ?: htmlspecialchars(t('header.na')) ?></div>
        <div class="game-info"><strong><?= htmlspecialchars(t('header.version')) ?></strong> <?= htmlspecialchars((string)($xml->header->version ?? '')) ?: htmlspecialchars(t('header.na')) ?></div>
        <div class="game-info"><strong><?= htmlspecialchars(t('header.date')) ?></strong> <?= htmlspecialchars((string)($xml->header->date ?? '')) ?: htmlspecialchars(t('header.na')) ?></div>
        <div class="game-info"><strong><?= htmlspecialchars(t('header.author')) ?></strong> <?= htmlspecialchars((string)($xml->header->author ?? '')) ?: htmlspecialchars(t('header.na')) ?></div>
        <div class="game-info"><strong><?= htmlspecialchars(t('header.web')) ?></strong>
            <?php if (!empty((string)($xml->header->url ?? ''))): ?>
                <a href="<?= htmlspecialchars((string)($xml->header->url ?? '')) ?>" target="_blank">&<?= 'nbsp;' ?><?= htmlspecialchars((string)($xml->header->homepage ?? t('header.link'))) ?></a>
            <?php else: ?>
                <?= htmlspecialchars(t('header.na')) ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="game-info"><?= htmlspecialchars(t('header.none')) ?></div>
    <?php endif; ?>
</div>

<div class="list-meta"><?= htmlspecialchars(t('list.showing.prefix')) ?> <?= $total > 0 ? ($start + 1) : 0 ?>–<?= $total > 0 ? ($end + 1) : 0 ?> <?= htmlspecialchars(t('list.of')) ?> <?= $total ?></div>
<div class="game-grid">
    <?php $gameIdx = $preGame; $machineIdx = $preMachine; foreach ($pageEntries as $entry): ?>
        <?php $node = $entry['el']; $isMachine = ($entry['type'] === 'machine'); ?>
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
            <div class="game-info"><strong><?= htmlspecialchars(t('item.type')) ?></strong> <?= $isMachine ? htmlspecialchars(t('item.type.machine')) : htmlspecialchars(t('item.type.game')) ?></div>
            <div class="game-info"><strong><?= htmlspecialchars(t('item.name')) ?></strong> <?= htmlspecialchars((string)$node['name']) ?></div>
            <div class="game-info"><strong><?= htmlspecialchars(t('item.description')) ?></strong> <?= htmlspecialchars((string)$node->description) ?></div>
            <?php if ($isMachine): ?>
                <div class="game-info"><strong><?= htmlspecialchars(t('item.year')) ?></strong> <?= htmlspecialchars((string)$node->year) ?></div>
                <div class="game-info"><strong><?= htmlspecialchars(t('item.manufacturer')) ?></strong> <?= htmlspecialchars((string)$node->manufacturer) ?></div>
            <?php endif; ?>
            <div class="game-info"><strong><?= htmlspecialchars(t('item.category')) ?></strong> <?= $isMachine ? '—' : htmlspecialchars((string)$node->category) ?></div>
            <?php if (count($node->rom) > 0): ?>
                <div class="game-roms">
                    <strong><?= htmlspecialchars(t('roms.title')) ?></strong>
                    <ul>
                        <?php foreach ($node->rom as $rom): ?>
                            <li>
                                <div><strong><?= htmlspecialchars(t('roms.name')) ?></strong> <?= htmlspecialchars((string)$rom['name']) ?></div>
                                <div><strong><?= htmlspecialchars(t('roms.size')) ?></strong> <?= htmlspecialchars((string)$rom['size']) ?></div>
                                <div><strong><?= htmlspecialchars(t('roms.crc')) ?></strong> <?= htmlspecialchars((string)$rom['crc']) ?></div>
                                <div><strong><?= htmlspecialchars(t('roms.md5')) ?></strong> <?= htmlspecialchars((string)$rom['md5']) ?></div>
                                <div><strong><?= htmlspecialchars(t('roms.sha1')) ?></strong> <?= htmlspecialchars((string)$rom['sha1']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="game-info"><strong><?= htmlspecialchars(t('roms.title')) ?></strong> <?= htmlspecialchars(t('roms.na')) ?></div>
            <?php endif; ?>

            <div class="game-actions">
                <?php if (!$isMachine): ?>
                    <button onclick="openEditModal(<?= $gameIdx ?>)"><?= htmlspecialchars(t('actions.edit')) ?></button>
                    <?php if (empty($isMame)): ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="index" value="<?= $absIndex ?>">
                        <?= campoCSRF() ?>
                        <button type="submit" onclick="return confirm('<?= htmlspecialchars(t('confirm.delete.game'), ENT_QUOTES) ?>')"><?= htmlspecialchars(t('actions.delete')) ?></button>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                    <button onclick="openEditModalMachine(<?= $machineIdx ?>)"><?= htmlspecialchars(t('actions.edit')) ?></button>
                    <?php if (empty($isMame)): ?>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="node_type" value="machine">
                        <input type="hidden" name="index" value="<?= $absIndex ?>">
                        <?= campoCSRF() ?>
                        <button type="submit" onclick="return confirm('<?= htmlspecialchars(t('confirm.delete.machine'), ENT_QUOTES) ?>')"><?= htmlspecialchars(t('actions.delete')) ?></button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php if ($isMachine) { $machineIdx++; } else { $gameIdx++; } endforeach; ?>
</div>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a class="page-link" href="?page=<?= $page - 1 ?>&per_page=<?= $perPage ?><?= $q !== '' ? '&q='.urlencode($q) : '' ?>">&laquo; <?= htmlspecialchars(t('pagination.prev')) ?></a>
    <?php endif; ?>
    <span class="page-info"><?= htmlspecialchars(t('pagination.page')) ?> <?= $page ?> <?= htmlspecialchars(t('pagination.of')) ?> <?= $pages ?></span>
    <form method="get" class="page-jump">
        <input type="hidden" name="per_page" value="<?= $perPage ?>">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
        <label for="goto"><?= htmlspecialchars(t('pagination.goto.label')) ?></label>
        <input id="goto" name="page" type="number" min="1" max="<?= $pages ?>" value="<?= $page ?>">
        <button type="submit"><?= htmlspecialchars(t('pagination.goto.button')) ?></button>
    </form>
    <?php if ($page < $pages): ?>
        <a class="page-link" href="?page=<?= $page + 1 ?>&per_page=<?= $perPage ?><?= $q !== '' ? '&q='.urlencode($q) : '' ?>"><?= htmlspecialchars(t('pagination.next')) ?> &raquo;</a>
    <?php endif; ?>
</div>
