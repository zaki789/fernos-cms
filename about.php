<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Fernosa\Services\VisitService;

VisitService::track('page', 2);

$statement = db()->prepare(
    'SELECT title, content, image, seo_title, meta_description
     FROM pages
     WHERE slug = :slug AND status = "published" AND deleted_at IS NULL
     LIMIT 1'
);
$statement->execute(['slug' => 'about']);
$page = $statement->fetch();

$metaTitle = $page['seo_title'] ?? 'درباره فرنوسا';
$metaDescription = $page['meta_description'] ?? 'داستان و فلسفه کافه فرنوسا';
$currentPage = 'about';

require BASE_PATH . '/partials/public-header.php';
?>
<section class="item-page-section public-section">
    <div class="site-container about-grid">
        <div class="about-art">
            <img src="<?= e(asset('images/brand/about.svg')) ?>" alt="درباره فرنوسا">
        </div>
        <article class="about-copy">
            <p class="public-eyebrow">درباره مجموعه</p>
            <h1><?= e($page['title'] ?? 'درباره فرنوسا') ?></h1>
            <div class="page-rich-content">
                <?php if ($page): ?>
                    <p><?= nl2br(e(trim(strip_tags((string)$page['content'])))) ?></p>
                <?php else: ?>
                    <p>اطلاعات این صفحه در دیتابیس موجود نیست.</p>
                <?php endif; ?>
            </div>
            <div class="about-features">
                <span><i class="fa-solid fa-mug-hot"></i> قهوه تازه</span>
                <span><i class="fa-solid fa-ice-cream"></i> جلاتوی دست‌ساز</span>
                <span><i class="fa-solid fa-heart"></i> سرو دقیق</span>
            </div>
        </article>
    </div>
</section>
<?php require BASE_PATH . '/partials/public-footer.php'; ?>
