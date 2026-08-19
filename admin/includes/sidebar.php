<?php
declare(strict_types=1);

$navigation = [
    ['key' => 'dashboard', 'label' => 'داشبورد', 'icon' => 'fa-solid fa-table-cells-large', 'href' => url('admin/index.php'), 'ready' => true],
    ['key' => 'items', 'label' => 'آیتم‌های منو', 'icon' => 'fa-solid fa-burger', 'href' => '#latest-items', 'ready' => false],
    ['key' => 'categories', 'label' => 'دسته‌بندی‌ها', 'icon' => 'fa-solid fa-layer-group', 'href' => '#category-summary', 'ready' => false],
    ['key' => 'media', 'label' => 'رسانه و گالری', 'icon' => 'fa-regular fa-images', 'href' => '#', 'ready' => false],
    ['key' => 'pages', 'label' => 'صفحات', 'icon' => 'fa-regular fa-file-lines', 'href' => '#', 'ready' => false],
    ['key' => 'qr', 'label' => 'شعب و QR', 'icon' => 'fa-solid fa-qrcode', 'href' => '#', 'ready' => false],
    ['key' => 'appearance', 'label' => 'ظاهر سایت', 'icon' => 'fa-solid fa-palette', 'href' => '#', 'ready' => false],
    ['key' => 'settings', 'label' => 'تنظیمات', 'icon' => 'fa-solid fa-sliders', 'href' => '#', 'ready' => false],
];
?>
<aside class="admin-sidebar" data-sidebar>
    <div class="sidebar-header">
        <a class="sidebar-brand" href="<?= e(url('admin/index.php')) ?>">
            <span>F</span>
            <div class="sidebar-brand-copy">
                <b>Fernosa</b>
                <small>Menu Manager</small>
            </div>
        </a>
        <button class="sidebar-collapse" type="button" data-sidebar-collapse aria-label="جمع‌کردن منو">
            <i class="fa-solid fa-angles-right"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <p class="sidebar-label">مدیریت</p>
        <?php foreach ($navigation as $item): ?>
            <a
                class="sidebar-link <?= $currentSection === $item['key'] ? 'active' : '' ?> <?= !$item['ready'] ? 'stage-next' : '' ?>"
                href="<?= e($item['href']) ?>"
                <?= !$item['ready'] && $item['href'] === '#' ? 'aria-disabled="true"' : '' ?>
            >
                <i class="<?= e($item['icon']) ?>"></i>
                <span><?= e($item['label']) ?></span>
                <?php if (!$item['ready']): ?>
                    <small>بعدی</small>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-status">
            <span class="status-dot"></span>
            <div>
                <b>سامانه فعال است</b>
                <small>نسخه 1.0 مرحله اول</small>
            </div>
        </div>

        <form method="post" action="<?= e(url('admin/logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="logout-button" type="submit">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>خروج امن</span>
            </button>
        </form>
    </div>
</aside>
<div class="sidebar-overlay" data-sidebar-overlay></div>
