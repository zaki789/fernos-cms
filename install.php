<?php
declare(strict_types=1);
require_once __DIR__ . '/app/helpers/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('fernosa_installer');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$installerToken = $_SESSION['installer_csrf'] ??= bin2hex(random_bytes(32));

$message = '';
$error = '';

function run_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('فایل SQL پیدا نشد.');
    }

    $statement = '';
    $inString = false;
    $length = strlen($sql);
    for ($index = 0; $index < $length; $index++) {
        $character = $sql[$index];
        $statement .= $character;
        if ($character === "'" && ($index === 0 || $sql[$index - 1] !== '\\')) {
            $inString = !$inString;
        }
        if ($character === ';' && !$inString) {
            $query = trim(substr($statement, 0, -1));
            $statement = '';
            if ($query !== '' && !preg_match('/^(CREATE DATABASE|USE)\\b/i', $query)) {
                $pdo->exec($query);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($installerToken, (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'درخواست نصب نامعتبر است.';
    } else {
    $host = trim((string) ($_POST['db_host'] ?? '127.0.0.1'));
    $name = trim((string) ($_POST['db_name'] ?? 'fernosa'));
    $user = trim((string) ($_POST['db_user'] ?? 'root'));
    $pass = (string) ($_POST['db_pass'] ?? '');
    $adminPassword = (string) ($_POST['admin_password'] ?? 'Fernosa@2026');
    try {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('نام دیتابیس فقط می‌تواند شامل حروف لاتین، عدد و زیرخط باشد.');
        }
        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $safeName = str_replace('`', '', $name);
        $pdo->exec('USE `' . $safeName . '`');
        run_sql_file($pdo, __DIR__ . '/database/fernosa.sql');
        run_sql_file($pdo, __DIR__ . '/database/seed.sql');
        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $statement = $pdo->prepare('INSERT INTO users (role_id, username, email, password_hash, full_name, must_change_password) VALUES (1, :username, :email, :hash, :name, 1) ON DUPLICATE KEY UPDATE role_id = 1, password_hash = VALUES(password_hash), full_name = VALUES(full_name), must_change_password = 1, deleted_at = NULL');
        $statement->execute(['username' => 'admin', 'email' => 'admin@fernosa.ir', 'hash' => $hash, 'name' => 'مدیر فرنوسا']);
        $appUrl = rtrim(trim((string) ($_POST['app_url'] ?? 'http://localhost/fernosa-cms')), '/');
        $config = "<?php\ndeclare(strict_types=1);\n\nconst DB_HOST = " . var_export($host, true) . ";\nconst DB_NAME = " . var_export($name, true) . ";\nconst DB_USER = " . var_export($user, true) . ";\nconst DB_PASS = " . var_export($pass, true) . ";\nconst APP_NAME = 'Fernosa';\nconst APP_URL = " . var_export($appUrl, true) . ";\nconst UPLOAD_MAX_BYTES = 5242880;\n";
        if (file_put_contents(__DIR__ . '/app/config/config.php', $config) === false) {
            throw new RuntimeException('امکان ذخیره تنظیمات وجود ندارد.');
        }
        $message = 'نصب با موفقیت انجام شد. فایل install.php را حذف کنید و وارد پنل شوید.';
    } catch (Throwable $exception) {
        $error = 'نصب انجام نشد: ' . $exception->getMessage();
    }
    }
}
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>نصب Fernosa</title><link rel="stylesheet" href="assets/css/admin.css"></head><body><main class="form-card"><h1>نصب Fernosa</h1><p class="muted">اطلاعات اتصال MySQL و آدرس اجرای پروژه را وارد کنید.</p><?php if ($message): ?><div class="badge"><?= e($message) ?></div><p><a class="button" href="admin/login.php">ورود به پنل</a></p><?php endif; ?><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e($installerToken) ?>"><label>آدرس پروژه<input name="app_url" value="<?= old_input('app_url', 'http://localhost/fernosa-cms') ?>" required></label><label>هاست دیتابیس<input name="db_host" value="<?= old_input('db_host', '127.0.0.1') ?>" required></label><label>نام دیتابیس<input name="db_name" value="<?= old_input('db_name', 'fernosa') ?>" required></label><label>نام کاربری<input name="db_user" value="<?= old_input('db_user', 'root') ?>" required></label><label>رمز دیتابیس<input type="password" name="db_pass"></label><label>رمز مدیر<input type="password" name="admin_password" value="Fernosa@2026" minlength="10" required></label><button class="button" type="submit">شروع نصب</button></form></main></body></html>
