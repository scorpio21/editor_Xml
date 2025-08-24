<?php
declare(strict_types=1);

// Helpers comunes para módulos de acciones
require_once __DIR__ . '/../csrf-helper.php';
require_once __DIR__ . '/../logger.php';

/**
 * Requiere un token CSRF válido. Si es AJAX (ajax=1) responde JSON; si no, redirige.
 */
if (!function_exists('requireValidCsrf')) {
    function requireValidCsrf(): void {
        $token = (string)($_POST['csrf_token'] ?? '');
        if ($token === '' || !verificarTokenCSRF($token)) {
            registrarAdvertencia('acciones:requireValidCsrf', 'Token CSRF inválido o ausente', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'action' => $_POST['action'] ?? null,
            ]);
            if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => 'Sesión no válida o token CSRF incorrecto.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['error'] = 'Sesión no válida o token CSRF incorrecto.';
            header('Location: ' . ($_SERVER['PHP_SELF'] ?? '/'));
            exit;
        }
    }
}

/** Tokenizar texto en mayúsculas por caracteres A-Z0-9 */
if (!function_exists('tokenizar')) {
    function tokenizar(string $s): array {
        $tokens = preg_split('/[^A-Z0-9]+/', $s);
        if (!is_array($tokens)) { return []; }
        $tokens = array_values(array_filter($tokens, static fn($t) => $t !== ''));
        return $tokens;
    }
}

/**
 * ¿Algún término coincide? Si el término contiene caracteres no alfanuméricos,
 * usamos strpos; si es alfanumérico puro, exigimos coincidencia de token completa
 * para evitar falsos positivos (ej.: ES dentro de GAMES).
 */
if (!function_exists('anyTermMatch')) {
    function anyTermMatch(array $tokens, string $haystackUpper, array $terms): bool {
        foreach ($terms as $t) {
            $t = strtoupper((string)$t);
            if ($t === '') { continue; }
            if (preg_match('/[^A-Z0-9]/', $t)) {
                if (strpos($haystackUpper, $t) !== false) { return true; }
            } else {
                if (in_array($t, $tokens, true)) { return true; }
            }
        }
        return false;
    }
}

/** Mapear regiones a términos a incluir e idiomas a excluir a patrones cortos */
if (!function_exists('mapearRegionesIdiomas')) {
    function mapearRegionesIdiomas(array $includeRegions, array $excludeLangs, array &$includeTerms, array &$excludeTerms): void {
        $regionMap = [
            'JAPON' => ['JAPAN'],
            'EUROPA' => ['EUROPE'],
            'USA' => ['USA', 'U.S.A.', 'UNITED STATES', 'AMERICA'],
            'ASIA' => ['ASIA'],
            'AUSTRALIA' => ['AUSTRALIA'],
            'ESCANDINAVIA' => ['SCANDINAVIA'],
            'COREA' => ['KOREA'],
            'CHINA' => ['CHINA'],
            'HONG KONG' => ['HONG KONG'],
            'TAIWAN' => ['TAIWAN'],
            'RUSIA' => ['RUSSIA'],
            'ESPAÑA' => ['SPAIN'],
            'ALEMANIA' => ['GERMANY'],
            'FRANCIA' => ['FRANCE'],
            'ITALIA' => ['ITALY'],
            'PAISES BAJOS' => ['NETHERLANDS'],
            'PORTUGAL' => ['PORTUGAL'],
            'BRASIL' => ['BRAZIL','BRAZILIAN'],
            'MEXICO' => ['MEXICO','MEXICAN'],
            'REINO UNIDO' => ['UNITED KINGDOM','UK','ENGLAND','BRITAIN','BRITISH'],
            'NORTEAMERICA' => ['NORTH AMERICA','NA'],
            'MUNDO/INTERNACIONAL' => ['WORLD','INTERNATIONAL'],
            'PAL' => ['PAL'],
            'NTSC' => ['NTSC']
        ];
        foreach ($includeRegions as $r) {
            $key = mb_strtoupper(trim((string)$r), 'UTF-8');
            if (isset($regionMap[$key])) { foreach ($regionMap[$key] as $pat) { $includeTerms[] = $pat; } }
        }
        $langMap = [
            'EN' => ['EN'], 'JA' => ['JA'], 'FR' => ['FR'], 'DE' => ['DE'], 'ES' => ['ES'], 'IT' => ['IT'],
            'NL' => ['NL'], 'PT' => ['PT'], 'SV' => ['SV'], 'NO' => ['NO'], 'DA' => ['DA'], 'FI' => ['FI'],
            'ZH' => ['ZH'], 'KO' => ['KO'], 'PL' => ['PL'], 'RU' => ['RU'], 'CS' => ['CS'], 'HU' => ['HU']
        ];
        foreach ($excludeLangs as $l) {
            $key = mb_strtoupper(trim((string)$l), 'UTF-8');
            if (isset($langMap[$key])) { foreach ($langMap[$key] as $pat) { $excludeTerms[] = $pat; } }
        }
    }
}
