<?php
declare(strict_types=1);

// Helpers comunes para módulos de acciones
require_once __DIR__ . '/../csrf-helper.php';
require_once __DIR__ . '/../logger.php';
require_once __DIR__ . '/../xml-helpers.php';

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
