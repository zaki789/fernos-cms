<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setting(string $key, mixed $default = ''): mixed
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            $rows = Database::connection()->query('SELECT setting_key, setting_value FROM settings WHERE deleted_at IS NULL')->fetchAll();
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable) {
            return $default;
        }
    }
    return $settings[$key] ?? $default;
}

function old_input(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}
