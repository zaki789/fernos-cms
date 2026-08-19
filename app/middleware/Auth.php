<?php
declare(strict_types=1);

function require_admin(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect(APP_URL . '/admin/login.php');
    }
}

function require_password_changed(): void
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['must_change_password'])) {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (!str_ends_with((string) $currentPath, '/admin/change-password.php')) {
            redirect(APP_URL . '/admin/change-password.php');
        }
    }
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $statement = Database::connection()->prepare('SELECT id, username, email, full_name FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $_SESSION['user_id']]);
        $user = $statement->fetch() ?: null;
    }
    return $user;
}
