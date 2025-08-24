<?php
declare(strict_types=1);

// Configuración básica de aplicación y logging
// Regla: mantenerlo simple y en español

// Entorno de ejecución: 'production' o 'development'
$__env = getenv('APP_ENV');
if (!is_string($__env) || $__env === '') { $__env = 'production'; }
if (!defined('APP_ENV')) { define('APP_ENV', $__env); }

// Directorio de logs: /logs al lado del proyecto
$__logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($__logDir)) {
    @mkdir($__logDir, 0777, true);
}

// Ruta del archivo de log principal
$__logPath = $__logDir . DIRECTORY_SEPARATOR . 'app.log';
if (!defined('LOG_PATH')) { define('LOG_PATH', $__logPath); }

// Nivel mínimo de log: INFO, ADVERTENCIA, ERROR
$__level = getenv('LOG_LEVEL_MIN');
if (!is_string($__level) || $__level === '') { $__level = 'INFO'; }
if (!defined('LOG_LEVEL_MIN')) { define('LOG_LEVEL_MIN', strtoupper($__level)); }
