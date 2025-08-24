<?php
declare(strict_types=1);

// Logger simple en español con rotación básica
// Requiere inc/config.php para LOG_PATH y LOG_LEVEL_MIN
require_once __DIR__ . '/config.php';

/**
 * Escribe una línea en el log si el nivel es >= nivel mínimo configurado.
 */
function escribirLog(string $nivel, string $contexto, string $mensaje, array $extra = []): void {
    static $niveles = [ 'INFO' => 1, 'ADVERTENCIA' => 2, 'ERROR' => 3 ];
    $nivel = strtoupper($nivel);
    $min = $niveles[LOG_LEVEL_MIN] ?? 1;
    $act = $niveles[$nivel] ?? 1;
    if ($act < $min) { return; }

    $ts = date('Y-m-d H:i:s');
    $linea = $ts . ' [' . $nivel . '] ' . $contexto . ' :: ' . sanitizarMensaje($mensaje);
    if (!empty($extra)) {
        $json = json_encode(sanitizarExtra($extra), JSON_UNESCAPED_UNICODE);
        if (is_string($json)) { $linea .= ' ' . $json; }
    }
    $linea .= PHP_EOL;

    // Rotación simple si el archivo supera ~2 MB
    $maxBytes = 2 * 1024 * 1024;
    if (file_exists(LOG_PATH) && filesize(LOG_PATH) > $maxBytes) {
        @rename(LOG_PATH, LOG_PATH . '.1');
    }

    @file_put_contents(LOG_PATH, $linea, FILE_APPEND | LOCK_EX);
}

/** Niveles convenientes */
function registrarInfo(string $contexto, string $mensaje, array $extra = []): void {
    escribirLog('INFO', $contexto, $mensaje, $extra);
}
function registrarAdvertencia(string $contexto, string $mensaje, array $extra = []): void {
    escribirLog('ADVERTENCIA', $contexto, $mensaje, $extra);
}
function registrarError(string $contexto, string $mensaje, array $extra = []): void {
    escribirLog('ERROR', $contexto, $mensaje, $extra);
}

/**
 * Sanitiza el mensaje para evitar saltos de línea y datos sensibles.
 */
function sanitizarMensaje(string $mensaje): string {
    $mensaje = str_replace(["\r", "\n"], ' ', $mensaje);
    return trim($mensaje);
}

/**
 * Sanitiza datos extra: recorta hashes, tamaños grandes y elimina binarios.
 */
function sanitizarExtra(array $extra): array {
    $san = [];
    foreach ($extra as $k => $v) {
        if (is_string($v)) {
            // Enmascarar posibles hashes largos
            if (preg_match('/^[0-9a-fA-F]{32,64}$/', $v)) {
                $san[$k] = substr($v, 0, 10) . '…';
            } else {
                $san[$k] = mb_substr($v, 0, 200);
            }
        } elseif (is_numeric($v) || is_bool($v) || $v === null) {
            $san[$k] = $v;
        } elseif (is_array($v)) {
            $san[$k] = sanitizarExtra($v);
        } else {
            $san[$k] = '[objeto]';
        }
    }
    return $san;
}
