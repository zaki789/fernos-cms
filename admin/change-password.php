<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'درخواست نامعتبر است.';
    } else {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['new_password_confirmation'] ?? '');
        $user = current_user();
        $statement = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $_SESSION['user_id']]);
        $record = $statement->fetch();

        if (!$user || !$record || !password_verify($currentPassword, $record['password_hash'])) {
            $error = 'رمز عبور فعلی نادرست است.';
        } elseif (strlen($newPassword) < 10) {
            $error = 'رمز عبور جدید باید حداقل ۱۰ نویسه باشد.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'تکرار رمز عبور با رمز جدید یکسان نیست.';
        } elseif ($currentPassword === $newPassword) {
            $error = 'رمز عبور جدید باید با رمز قبلی متفاوت باشد.';
        } else {
            $update = Database::connection()->prepare('UPDATE users SET password_hash = :hash, must_change_password = 0, updated_at = NOW() WHERE id = :id');
            $update->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $_SESSION['user_id']]);
            $_SESSION['must_change_password'] = false;
            redirect(APP_URL . '/admin/index.php');
        }
    }
}
?><!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>تغییر رمز | Fernosa</title><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css"></head>
<body><main class="form-card"><h1>تغییر رمز عبور</h1><p class="muted">برای ادامه کار، رمز عبور پیش‌فرض را تغییر دهید.</p><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>رمز عبور فعلی<input type="password" name="current_password" autocomplete="current-password" required></label><label>رمز عبور جدید<input type="password" name="new_password" minlength="10" autocomplete="new-password" required></label><label>تکرار رمز عبور جدید<input type="password" name="new_password_confirmation" minlength="10" autocomplete="new-password" required></label><button class="button" type="submit">ذخیره رمز جدید</button></form></main></body></html>
