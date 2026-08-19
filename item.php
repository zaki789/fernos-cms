<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Fernosa\Services\VisitService;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    $item = null;
} else {
    $statement = db()->prepare(
        'SELECT m.*, c.name_fa AS category_name
         FROM menu_items m
         INNER JOIN categories c ON c.id = m.category_id
         WHERE m.id = :id AND m.status = "active" AND m.deleted_at IS NULL
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $item = $statement->fetch();

    if ($item) {
        db()->prepare('UPDATE menu_items SET view_count = view_count + 1 WHERE id = :id')->execute(['id' => $id]);
        VisitService::track('item', (int)$id);
    } else {
        http_response_code(404);
    }
}

$metaTitle = $item ? ($item['seo_title'] ?: $item['name_fa'] . ' | فرنوسا') : 'آیتم پیدا نشد | فرنوسا';
$metaDescription = $item ? ($item['seo_description'] ?: $item['short_description']) : 'آیتم موردنظر در منوی فرنوسا پیدا نشد.';
$currentPage = 'menu';

require BASE_PATH . '/partials/public-header.php';
?>
<section class="item-page-section public-section">
    <div class="site-container">
        <?php if (!$item): ?>
            <div class="not-found-card">
                <span><i class="fa-solid fa-mug-hot"></i></span>
                <h1>آیتم موردنظر پیدا نشد</h1>
                <p>ممکن است این آیتم غیرفعال شده یا نشانی آن تغییر کرده باشد.</p>
                <a class="primary-public-button" href="<?= e(url('menu.php')) ?>">بازگشت به منو</a>
            </div>
        <?php else: ?>
            <a class="item-back-link" href="<?= e(url('menu.php?category=' . $item['category_id'])) ?>">
                <i class="fa-solid fa-arrow-right"></i>
                بازگشت به منو
            </a>

            <article class="item-page-card">
                <div class="item-page-image">
                    <img src="<?= e(url($item['main_image'])) ?>" alt="<?= e($item['name_fa']) ?>">
                    <span class="availability-badge <?= $item['availability'] === 'available' ? 'available' : 'unavailable' ?>">
                        <?= $item['availability'] === 'available' ? 'موجود' : 'ناموجود' ?>
                    </span>
                </div>

                <div class="item-page-copy">
                    <p class="public-eyebrow"><?= e($item['category_name']) ?></p>
                    <h1><?= e($item['name_fa']) ?></h1>
                    <span class="item-english-name"><?= e($item['name_en']) ?></span>
                    <p class="item-description"><?= e($item['full_description']) ?></p>

                    <div class="item-page-price">
                        <strong><?= format_price($item['price']) ?></strong>
                        <?php if ($item['old_price']): ?>
                            <del><?= format_price($item['old_price']) ?></del>
                        <?php endif; ?>
                    </div>

                    <div class="item-detail-grid">
                        <div>
                            <span><i class="fa-solid fa-list-check"></i> مواد تشکیل‌دهنده</span>
                            <p><?= e($item['ingredients_text'] ?: 'ثبت نشده') ?></p>
                        </div>
                        <div>
                            <span><i class="fa-solid fa-fire-flame-curved"></i> کالری</span>
                            <p><?= $item['calories'] ? number_format((int)$item['calories']) . ' کیلوکالری' : 'ثبت نشده' ?></p>
                        </div>
                        <div>
                            <span><i class="fa-solid fa-triangle-exclamation"></i> حساسیت‌ها</span>
                            <p><?= e($item['allergens_text'] ?: 'موردی ثبت نشده') ?></p>
                        </div>
                    </div>

                    <div class="item-page-actions">
                        <button
                            class="secondary-public-button"
                            type="button"
                            data-share-url="<?= e(url('item.php?id=' . $item['id'])) ?>"
                            data-share-title="<?= e($item['name_fa']) ?>"
                        >
                            <i class="fa-solid fa-share-nodes"></i>
                            اشتراک‌گذاری
                        </button>
                        <a class="primary-public-button" href="<?= e(url('menu.php')) ?>">
                            مشاهده همه منو
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                </div>
            </article>
        <?php endif; ?>
    </div>
</section>
<?php require BASE_PATH . '/partials/public-footer.php'; ?>
