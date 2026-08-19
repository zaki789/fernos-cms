<?php
declare(strict_types=1);

namespace Fernosa\Core;

use PDO;
use Throwable;

final class Auth
{
    private const SESSION_USER_ID = 'auth_user_id';
    private const REMEMBER_COOKIE = 'fernosa_remember';

    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        if (Session::get(self::SESSION_USER_ID)) {
            return self::user() !== null;
        }

        return self::loginFromRememberCookie();
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_USER_ID);
        return $id ? (int)$id : null;
    }

    public static function user(): ?array
    {
        $userId = self::id();

        if (!$userId) {
            return null;
        }

        if (self::$cachedUser !== null && (int)self::$cachedUser['id'] === $userId) {
            return self::$cachedUser;
        }

        $statement = db()->prepare(
            'SELECT u.*, r.name AS role_name, r.title AS role_title
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.status = "active" AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();

        if (!$user) {
            self::logout();
            return null;
        }

        self::$cachedUser = $user;
        return $user;
    }

    public static function attempt(string $identifier, string $password, bool $remember, string $ip): array
    {
        $identifier = trim(mb_strtolower($identifier));
        self::cleanupLoginAttempts();

        $security = config('security');
        $maxAttempts = (int)($security['login_max_attempts'] ?? 5);
        $lockMinutes = (int)($security['login_lock_minutes'] ?? 15);

        $lockMinutes = max(1, min(1440, $lockMinutes));
        $attemptStatement = db()->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip_address = :ip
               AND identifier = :identifier
               AND is_successful = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$lockMinutes} MINUTE)"
        );
        $attemptStatement->execute([
            'ip' => $ip,
            'identifier' => $identifier,
        ]);
        $attemptCount = (int)$attemptStatement->fetchColumn();

        if ($attemptCount >= $maxAttempts) {
            return [
                'success' => false,
                'message' => "تعداد تلاش‌های ورود بیش از حد مجاز است. {$lockMinutes} دقیقه بعد دوباره تلاش کنید.",
            ];
        }

        $userStatement = db()->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE (LOWER(u.username) = :identifier OR LOWER(u.email) = :identifier)
               AND u.status = "active"
               AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $userStatement->execute(['identifier' => $identifier]);
        $user = $userStatement->fetch();

        $valid = $user && password_verify($password, (string)$user['password']);

        self::recordLoginAttempt($identifier, $ip, $valid);

        if (!$valid) {
            return [
                'success' => false,
                'message' => 'نام کاربری، ایمیل یا رمز عبور صحیح نیست.',
            ];
        }

        if (password_needs_rehash((string)$user['password'], PASSWORD_DEFAULT)) {
            $rehash = db()->prepare('UPDATE users SET password = :password WHERE id = :id');
            $rehash->execute([
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }

        session_regenerate_id(true);
        Session::put(self::SESSION_USER_ID, (int)$user['id']);
        Session::put('_created_at', time());
        self::$cachedUser = $user;

        db()->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id')
            ->execute(['ip' => $ip, 'id' => $user['id']]);

        self::logActivity((int)$user['id'], 'login', 'ورود موفق مدیر به پنل');

        if ($remember) {
            self::createRememberToken((int)$user['id']);
        }

        Csrf::rotate();

        return ['success' => true, 'message' => 'ورود با موفقیت انجام شد.'];
    }

    public static function requireAdmin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'برای مشاهده پنل مدیریت وارد حساب خود شوید.');
            redirect('admin/login.php');
        }

        // ورود موفق باید کاربر را وارد داشبورد کند.
        // تغییر رمز اولیه به‌صورت یادآوری در داشبورد نمایش داده می‌شود
        // تا کاربر در چرخهٔ Redirect گیر نکند و بتواند سایر بخش‌های پنل را ببیند.
    }

    public static function logout(): void
    {
        $userId = self::id();

        if ($userId) {
            self::deleteCurrentRememberToken();
            self::logActivity($userId, 'logout', 'خروج مدیر از پنل');
        }

        self::$cachedUser = null;
        Session::forget(self::SESSION_USER_ID);
        Session::destroy();
        self::clearRememberCookie();
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $statement = db()->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $hash = $statement->fetchColumn();

        if (!is_string($hash) || !password_verify($currentPassword, $hash)) {
            return ['success' => false, 'message' => 'رمز عبور فعلی صحیح نیست.'];
        }

        if (strlen($newPassword) < 10
            || !preg_match('/[A-Z]/', $newPassword)
            || !preg_match('/[a-z]/', $newPassword)
            || !preg_match('/\d/', $newPassword)
            || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            return [
                'success' => false,
                'message' => 'رمز جدید باید حداقل ۱۰ نویسه و شامل حرف بزرگ، حرف کوچک، عدد و نماد باشد.',
            ];
        }

        db()->prepare(
            'UPDATE users SET password = :password, must_change_password = 0, password_changed_at = NOW() WHERE id = :id'
        )->execute([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]);

        db()->prepare('DELETE FROM user_remember_tokens WHERE user_id = :user_id')
            ->execute(['user_id' => $userId]);

        self::logActivity($userId, 'password_changed', 'رمز عبور مدیر تغییر کرد');
        self::$cachedUser = null;
        Csrf::rotate();

        return ['success' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد.'];
    }

    private static function recordLoginAttempt(string $identifier, string $ip, bool $successful): void
    {
        try {
            db()->prepare(
                'INSERT INTO login_attempts (identifier, ip_address, user_agent, is_successful, attempted_at)
                 VALUES (:identifier, :ip, :user_agent, :successful, NOW())'
            )->execute([
                'identifier' => $identifier,
                'ip' => $ip,
                'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
                'successful' => $successful ? 1 : 0,
            ]);
        } catch (Throwable) {
            // شکست ثبت لاگ نباید ورود را متوقف کند.
        }
    }

    private static function cleanupLoginAttempts(): void
    {
        try {
            db()->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
        } catch (Throwable) {
            // پاک‌سازی دوره‌ای اختیاری است.
        }
    }

    private static function createRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $validatorHash = hash('sha256', $validator);
        $expiresAt = time() + (int)(config('security.remember_lifetime') ?? 2592000);

        db()->prepare(
            'INSERT INTO user_remember_tokens (user_id, selector, validator_hash, expires_at, created_at)
             VALUES (:user_id, :selector, :validator_hash, FROM_UNIXTIME(:expires_at), NOW())'
        )->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'validator_hash' => $validatorHash,
            'expires_at' => $expiresAt,
        ]);

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        setcookie(self::REMEMBER_COOKIE, $selector . ':' . $validator, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function loginFromRememberCookie(): bool
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';

        if (!is_string($cookie) || !str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);

        if (!preg_match('/^[a-f0-9]{18}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
            self::clearRememberCookie();
            return false;
        }

        $statement = db()->prepare(
            'SELECT t.id AS token_id, t.user_id, t.validator_hash
             FROM user_remember_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.selector = :selector
               AND t.expires_at > NOW()
               AND u.status = "active"
               AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['selector' => $selector]);
        $token = $statement->fetch();

        if (!$token || !hash_equals((string)$token['validator_hash'], hash('sha256', $validator))) {
            self::clearRememberCookie();
            return false;
        }

        session_regenerate_id(true);
        Session::put(self::SESSION_USER_ID, (int)$token['user_id']);
        db()->prepare('UPDATE user_remember_tokens SET last_used_at = NOW() WHERE id = :id')
            ->execute(['id' => $token['token_id']]);

        return self::user() !== null;
    }

    private static function deleteCurrentRememberToken(): void
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';

        if (!is_string($cookie) || !str_contains($cookie, ':')) {
            return;
        }

        [$selector] = explode(':', $cookie, 2);
        db()->prepare('DELETE FROM user_remember_tokens WHERE selector = :selector')
            ->execute(['selector' => $selector]);
    }

    private static function clearRememberCookie(): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }

    private static function logActivity(int $userId, string $action, string $description): void
    {
        try {
            db()->prepare(
                'INSERT INTO activity_logs (user_id, action, entity_type, description, ip_address, user_agent, created_at)
                 VALUES (:user_id, :action, "auth", :description, :ip, :user_agent, NOW())'
            )->execute([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip' => client_ip(),
                'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            ]);
        } catch (Throwable) {
            // لاگ فعالیت نباید عملیات اصلی را متوقف کند.
        }
    }
}
