<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
if (!empty($_SESSION['user_id'])) { redirect(APP_URL . '/admin/index.php'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'درخواست نامعتبر است.';
    } else {
        $identity = trim((string) ($_POST['identity'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE (username = :identity OR email = :identity) AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['identity' => $identity]);
        $user = $statement->fetch();
        $locked = $user && $user['locked_until'] && strtotime($user['locked_until']) > time();
        if ($locked || !$user || !password_verify($password, $user['password_hash'])) {
            if ($user && !$locked) {
                $attempts = (int) $user['failed_attempts'] + 1;
                $lock = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
                $update = Database::connection()->prepare('UPDATE users SET failed_attempts = :attempts, locked_until = :locked WHERE id = :id');
                $update->execute(['attempts' => min($attempts, 5), 'locked' => $lock, 'id' => $user['id']]);
            }
            $error = 'نام کاربری یا رمز عبور نادرست است.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['must_change_password'] = (bool) $user['must_change_password'];
            $update = Database::connection()->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id');
            $update->execute(['id' => $user['id']]);
            redirect(APP_URL . '/admin/index.php');
        }
    }
}
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ورود مدیران | Fernosa</title><link rel="stylesheet" href="/fernosa-cms/assets/css/admin.css"></head><body><main class="form-card"><h1>ورود به Fernosa</h1><p class="muted">مدیریت منوی دیجیتال فرنوسا</p><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>نام کاربری یا ایمیل<input name="identity" autocomplete="username" required></label><label>رمز عبور<input type="password" name="password" autocomplete="current-password" required></label><label><input type="checkbox" name="remember" value="1" style="width:auto"> مرا به خاطر بسپار</label><button class="button" type="submit">ورود امن</button></form></main></body></html>
