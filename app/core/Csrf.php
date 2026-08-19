<?php
declare(strict_types=1);

namespace Fernosa\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);

        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(?string $token): bool
    {
        $stored = Session::get(self::SESSION_KEY);
        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }

    public static function enforce(?string $token): void
    {
        if (!self::validate($token)) {
            http_response_code(419);
            exit('درخواست منقضی یا نامعتبر است. صفحه را تازه‌سازی و دوباره تلاش کنید.');
        }
    }

    public static function rotate(): void
    {
        Session::put(self::SESSION_KEY, bin2hex(random_bytes(32)));
    }
}
