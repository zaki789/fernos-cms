<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
require_password_changed();
$pdo = Database::connection();
$stats = [];
$queries = ['items' => 'SELECT COUNT(*) FROM menu_items WHERE deleted_at IS NULL', 'categories' => 'SELECT COUNT(*) FROM categories WHERE deleted_at IS NULL', 'available' => 'SELECT COUNT(*) FROM menu_items WHERE deleted_at IS NULL AND is_available = 1', 'unavailable' => 'SELECT COUNT(*) FROM menu_items WHERE deleted_at IS NULL AND is_available = 0', 'special' => 'SELECT COUNT(*) FROM menu_items WHERE deleted_at IS NULL AND is_special = 1', 'visits' => 'SELECT COUNT(*) FROM visits WHERE visited_on = CURDATE()', 'scans' => 'SELECT COALESCE(SUM(scan_count), 0) FROM qr_codes WHERE deleted_at IS NULL'];
foreach ($queries as $key => $query) { $stats[$key] = (int) $pdo->query($query)->fetchColumn(); }
$latest = $pdo->query('SELECT title_fa, price, created_at FROM menu_items WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5')->fetchAll();
$pageTitle = 'داشبورد';
require __DIR__ . '/includes/header.php';
?><h1 class="page-title">نمای کلی فرنوسا</h1><div class="cards"><div class="card"><div class="stat-label">آیتم‌های منو</div><div class="stat-value"><?= e($stats['items']) ?></div></div><div class="card"><div class="stat-label">دسته‌بندی‌ها</div><div class="stat-value"><?= e($stats['categories']) ?></div></div><div class="card"><div class="stat-label">آیتم‌های فعال</div><div class="stat-value"><?= e($stats['available']) ?></div></div><div class="card"><div class="stat-label">ناموجود</div><div class="stat-value"><?= e($stats['unavailable']) ?></div></div><div class="card"><div class="stat-label">ویژه</div><div class="stat-value"><?= e($stats['special']) ?></div></div><div class="card"><div class="stat-label">بازدید امروز</div><div class="stat-value"><?= e($stats['visits']) ?></div></div><div class="card"><div class="stat-label">اسکن QR</div><div class="stat-value"><?= e($stats['scans']) ?></div></div></div><section class="card panel"><h2>آخرین آیتم‌های اضافه‌شده</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>نام</th><th>قیمت</th><th>تاریخ</th></tr></thead><tbody><?php foreach ($latest as $item): ?><tr><td><?= e($item['title_fa']) ?></td><td><?= e(number_format((float) $item['price'])) ?> ریال</td><td><?= e($item['created_at']) ?></td></tr><?php endforeach; ?><?php if (!$latest): ?><tr><td colspan="3" class="muted">هنوز آیتمی ثبت نشده است.</td></tr><?php endif; ?></tbody></table></div></section><?php require __DIR__ . '/includes/footer.php';
