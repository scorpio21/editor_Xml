<?php
declare(strict_types=1);

/**
 * Helper para generar campos CSRF en formularios
 * Debe incluirse en todos los formularios que envían acciones POST
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Genera un token CSRF único para la sesión
 * @return string Token CSRF
 */
function generarTokenCSRF(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica si un token CSRF es válido
 * @param string $token Token a verificar
 * @return bool true si es válido
 */
function verificarTokenCSRF(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera un campo hidden con el token CSRF para formularios
 */
function campoCSRF(): string {
    $token = generarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
}

/**
 * Obtiene el token CSRF para uso en JavaScript/AJAX
 */
function obtenerTokenCSRF(): string {
    return generarTokenCSRF();
}
