<?php
// Definir la raíz del proyecto
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/supermercado');
define('BASE_URL', '/supermercado');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'supermercado_db');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Cambiar a true si usas HTTPS
    'httponly' => true
]);

// Zona horaria
date_default_timezone_set('America/Bogota');
?>