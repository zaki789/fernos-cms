<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e(setting('site_title', 'Fernosa')) ?></title><link rel="stylesheet" href="/fernosa-cms/assets/css/admin.css"></head><body><main class="form-card"><h1><?= e(setting('site_name', 'Fernosa')) ?></h1><p><?= e(setting('footer_text', 'به‌زودی منوی دیجیتال فرنوسا در دسترس است.')) ?></p><a class="button" href="/fernosa-cms/admin/login.php">ورود مدیریت</a></main></body></html>
