<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Fernosa\Services\VisitService;

$qrCodeId = null;
$tableLabel = null;
$qrToken = trim((string)($_GET['qr'] ?? ''));

if ($qrToken !== '') {
    $statement = db()->prepare(
        'SELECT q.id, t.title AS table_title
         FROM qr_codes q
         LEFT JOIN `tables` t ON t.id = q.table_id
         WHERE q.token = :token AND q.status = "active" AND q.deleted_at IS NULL
         LIMIT 1'
    );
    $statement->execute(['token' => $qrToken]);
    $qr = $statement->fetch();

    if ($qr) {
        $qrCodeId = (int)$qr['id'];
        $tableLabel = $qr['table_title'];
    }
}

VisitService::track('menu', null, $qrCodeId);

$categories = db()->query(
    'SELECT id, name_fa, name_en, icon, color
     FROM categories
     WHERE status = "active" AND deleted_at IS NULL
     ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$initialCategory = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$metaTitle = 'منوی دیجیتال فرنوسا';
$metaDescription = 'منوی کامل قهوه، نوشیدنی، جلاتو، دسر و صبحانه فرنوسا';
$currentPage = 'menu';

require BASE_PATH . '/partials/public-header.php';
?>
<section class="menu-hero">
    <div class="site-container menu-hero-inner">
        <div>
            <p class="public-eyebrow">FERNOSA DIGITAL MENU</p>
            <h1>منوی فرنوسا</h1>
            <p>جستجو کنید، دسته‌بندی را انتخاب کنید و جزئیات هر آیتم را بدون خروج از صفحه ببینید.</p>
        </div>
        <div class="menu-hero-meta">
            <?php if ($tableLabel): ?>
                <span><i class="fa-solid fa-chair"></i> <?= e($tableLabel) ?></span>
            <?php endif; ?>
            <span><i class="fa-regular fa-clock"></i> به‌روزرسانی لحظه‌ای موجودی</span>
        </div>
    </div>
</section>

<section class="menu-toolbar-section">
    <div class="site-container">
        <div class="menu-search-row">
            <label class="menu-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" data-menu-search placeholder="جستجو در نام یا توضیحات..." autocomplete="off">
                <button type="button" data-search-clear aria-label="پاک‌کردن جستجو">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </label>

            <label class="available-switch">
                <input type="checkbox" data-available-only>
                <span class="switch-ui"></span>
                فقط آیتم‌های موجود
            </label>
        </div>

        <div class="category-tabs" data-category-tabs>
            <button class="category-tab <?= $initialCategory === 0 ? 'active' : '' ?>" type="button" data-category-id="0">
                <i class="fa-solid fa-border-all"></i>
                همه
            </button>
            <?php foreach ($categories as $category): ?>
                <button
                    class="category-tab <?= $initialCategory === (int)$category['id'] ? 'active' : '' ?>"
                    type="button"
                    data-category-id="<?= (int)$category['id'] ?>"
                    style="--tab-color: <?= e($category['color'] ?: '#988C75') ?>"
                >
                    <i class="<?= e($category['icon'] ?: 'fa-solid fa-utensils') ?>"></i>
                    <?= e($category['name_fa']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="menu-content public-section">
    <div class="site-container">
        <div class="menu-results-heading">
            <div>
                <p class="public-eyebrow">انتخاب‌های تازه</p>
                <h2 data-results-title>همه آیتم‌ها</h2>
            </div>
            <span data-results-count>در حال دریافت...</span>
        </div>

        <div
            class="menu-grid"
            data-menu-grid
            data-api-url="<?= e(url('api/menu.php')) ?>"
            data-item-api-url="<?= e(url('api/item.php')) ?>"
            data-initial-category="<?= (int)$initialCategory ?>"
        >
            <?php for ($i = 0; $i < 6; $i++): ?>
                <article class="menu-card skeleton-card" aria-hidden="true">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-content">
                        <span class="skeleton skeleton-line wide"></span>
                        <span class="skeleton skeleton-line"></span>
                        <span class="skeleton skeleton-line short"></span>
                    </div>
                </article>
            <?php endfor; ?>
        </div>

        <div class="menu-empty" data-menu-empty hidden>
            <span><i class="fa-solid fa-mug-hot"></i></span>
            <h3>آیتمی پیدا نشد</h3>
            <p>عبارت جستجو یا فیلتر انتخابی را تغییر دهید.</p>
        </div>
    </div>
</section>

<div class="item-modal" data-item-modal aria-hidden="true">
    <button class="modal-backdrop" type="button" data-modal-close aria-label="بستن"></button>
    <section class="item-modal-panel" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
        <button class="modal-close" type="button" data-modal-close aria-label="بستن جزئیات">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="item-modal-content" data-item-modal-content>
            <div class="modal-loading">
                <span class="spinner"></span>
                در حال دریافت جزئیات...
            </div>
        </div>
    </section>
</div>
<?php require BASE_PATH . '/partials/public-footer.php'; ?>
