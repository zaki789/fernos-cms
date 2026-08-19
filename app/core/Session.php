<?php
declare(strict_types=1);

namespace Fernosa\Core;

final class Session
{
    public static function start(array $config): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name((string)($config['session_name'] ?? 'fernosa_session'));
        session_set_cookie_params([
            'lifetime' => (int)($config['session_lifetime'] ?? 7200),
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        $now = time();
        $lastActivity = (int)($_SESSION['_last_activity'] ?? 0);
        $lifetime = (int)($config['session_lifetime'] ?? 7200);

        if ($lastActivity > 0 && ($now - $lastActivity) > $lifetime) {
            self::destroy();
            session_start();
        }

        $_SESSION['_last_activity'] = $now;

        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = $now;
        } elseif (($now - (int)$_SESSION['_created_at']) > 900) {
            session_regenerate_id(true);
            $_SESSION['_created_at'] = $now;
        }
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 2) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $result = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $result;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parameters['path'],
                $parameters['domain'],
                (bool)$parameters['secure'],
                (bool)$parameters['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
