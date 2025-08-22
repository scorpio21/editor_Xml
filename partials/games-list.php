<?php
declare(strict_types=1);
?>
<h2>Lista de juegos (<?= count($xml->game) ?>)</h2>

<?php
    // Parámetros de paginación (GET)
    $total = count($xml->game);
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
    <?php $idx = 0; foreach ($xml->game as $game): ?>
        <?php if ($idx < $start) { $idx++; continue; } ?>
        <?php if ($idx > $end) { break; } ?>
        <div class="game" id="game-<?= $idx ?>"
             data-name="<?= htmlspecialchars((string)$game['name']) ?>"
             data-description="<?= htmlspecialchars((string)$game->description) ?>"
             data-category="<?= htmlspecialchars((string)$game->category) ?>"
             data-romname="<?= htmlspecialchars((string)($game->rom['name'] ?? '')) ?>"
             data-size="<?= htmlspecialchars((string)($game->rom['size'] ?? '')) ?>"
             data-crc="<?= htmlspecialchars((string)($game->rom['crc'] ?? '')) ?>"
             data-md5="<?= htmlspecialchars((string)($game->rom['md5'] ?? '')) ?>"
             data-sha1="<?= htmlspecialchars((string)($game->rom['sha1'] ?? '')) ?>">
            <div class="game-info"><strong>Nombre:</strong> <?= htmlspecialchars((string)$game['name']) ?></div>
            <div class="game-info"><strong>Descripción:</strong> <?= htmlspecialchars((string)$game->description) ?></div>
            <div class="game-info"><strong>Categoría:</strong> <?= htmlspecialchars((string)$game->category) ?></div>
            <div class="game-info"><strong>Rom Name:</strong> <?= htmlspecialchars((string)($game->rom['name'] ?? 'N/A')) ?></div>
            <div class="game-info"><strong>Tamaño:</strong> <?= htmlspecialchars((string)($game->rom['size'] ?? 'N/A')) ?></div>
            <div class="game-info"><strong>CRC:</strong> <?= htmlspecialchars((string)($game->rom['crc'] ?? 'N/A')) ?></div>
            <div class="game-info"><strong>MD5:</strong> <?= htmlspecialchars((string)($game->rom['md5'] ?? 'N/A')) ?></div>
            <div class="game-info"><strong>SHA1:</strong> <?= htmlspecialchars((string)($game->rom['sha1'] ?? 'N/A')) ?></div>

            <div class="game-actions">
                <button onclick="openEditModal(<?= $idx ?>)">Editar</button>

                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="index" value="<?= $idx ?>">
                    <button type="submit" onclick="return confirm('¿Eliminar este juego?')">Eliminar</button>
                </form>
            </div>
        </div>
    <?php $idx++; endforeach; ?>
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
