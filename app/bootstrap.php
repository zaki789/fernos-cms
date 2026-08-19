<?php
declare(strict_types=1);

$configFile = __DIR__ . '/config/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI !== 'cli') {
        header('Location: /install.php');
    }
    exit;
}
require_once $configFile;
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/middleware/Csrf.php';
require_once __DIR__ . '/middleware/Auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('fernosa_session');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
