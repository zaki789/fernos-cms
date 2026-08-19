<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Fernosa\Services\VisitService;

VisitService::track('home');

$categories = db()->query(
    'SELECT id, name_fa, name_en, slug, description, image, icon, color
     FROM categories
     WHERE status = "active" AND deleted_at IS NULL
     ORDER BY sort_order ASC, id ASC
     LIMIT 6'
)->fetchAll();

$featuredItems = db()->query(
    'SELECT m.id, m.name_fa, m.name_en, m.slug, m.short_description, m.price, m.old_price,
            m.main_image, m.availability, c.name_fa AS category_name
     FROM menu_items m
     INNER JOIN categories c ON c.id = m.category_id
     WHERE m.status = "active" AND m.is_featured = 1 AND m.deleted_at IS NULL
     ORDER BY m.sort_order ASC, m.id DESC
     LIMIT 4'
)->fetchAll();

$specialOffer = db()->query(
    'SELECT o.title, o.description, o.offer_price, o.ends_at,
            m.id, m.name_fa, m.name_en, m.main_image, m.price
     FROM special_offers o
     INNER JOIN menu_items m ON m.id = o.item_id
     WHERE o.status = "active"
       AND o.deleted_at IS NULL
       AND NOW() BETWEEN o.starts_at AND o.ends_at
     ORDER BY o.sort_order ASC, o.id DESC
     LIMIT 1'
)->fetch();

$metaTitle = setting('site_title', 'فرنوسا | کافه و جلاتو');
$metaDescription = setting('tagline', 'طعم اصیل، لحظه‌های ماندگار');
$currentPage = 'home';

require BASE_PATH . '/partials/public-header.php';
?>
<section class="hero-section">
    <div class="hero-orb hero-orb-one"></div>
    <div class="hero-orb hero-orb-two"></div>

    <div class="site-container hero-grid">
        <div class="hero-copy">
            <p class="public-eyebrow">FERNOSA CAFÉ & GELATO</p>
            <h1>طعم‌هایی که برای <em>ماندن در خاطر</em> ساخته شده‌اند.</h1>
            <p>
                قهوه تازه، جلاتوی دست‌ساز و دسرهای روزانه در فضایی گرم و آرام.
                منوی دیجیتال فرنوسا را سریع و بدون نیاز به نصب برنامه ببینید.
            </p>
            <div class="hero-actions">
                <a class="primary-public-button" href="<?= e(url('menu.php')) ?>">
                    مشاهده منوی کامل
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <a class="secondary-public-button" href="tel:<?= e(preg_replace('/\D+/', '', (string)setting('phone', ''))) ?>">
                    <i class="fa-solid fa-phone"></i>
                    تماس با فرنوسا
                </a>
            </div>
            <div class="hero-trust">
                <div><strong><?= number_format(count($categories)) ?>+</strong><span>دسته‌بندی</span></div>
                <div><strong><?= number_format(count($featuredItems)) ?></strong><span>پیشنهاد منتخب</span></div>
                <div><strong>روزانه</strong><span>مواد اولیه تازه</span></div>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-image-shell">
                <img src="<?= e(asset('images/brand/hero.svg')) ?>" alt="منوی کافه و جلاتو فرنوسا">
                <span class="floating-note note-one"><i class="fa-solid fa-mug-hot"></i> قهوه تازه</span>
                <span class="floating-note note-two"><i class="fa-solid fa-ice-cream"></i> جلاتوی دست‌ساز</span>
            </div>
        </div>
    </div>

    <a class="scroll-indicator" href="#categories" aria-label="رفتن به دسته‌بندی‌ها">
        <span></span>
        پایین بروید
    </a>
</section>

<section class="category-section public-section" id="categories">
    <div class="site-container">
        <div class="section-heading-public">
            <div>
                <p class="public-eyebrow">دسته‌بندی‌ها</p>
                <h2>انتخاب شما از کجا شروع می‌شود؟</h2>
            </div>
            <a href="<?= e(url('menu.php')) ?>">مشاهده همه <i class="fa-solid fa-arrow-left"></i></a>
        </div>

        <div class="category-card-grid">
            <?php foreach ($categories as $category): ?>
                <a class="category-card" href="<?= e(url('menu.php?category=' . $category['id'])) ?>" style="--category-color: <?= e($category['color'] ?: '#988C75') ?>">
                    <div class="category-card-image">
                        <img loading="lazy" src="<?= e(url($category['image'])) ?>" alt="<?= e($category['name_fa']) ?>">
                    </div>
                    <div>
                        <span class="category-icon"><i class="<?= e($category['icon'] ?: 'fa-solid fa-utensils') ?>"></i></span>
                        <h3><?= e($category['name_fa']) ?></h3>
                        <small><?= e($category['name_en']) ?></small>
                        <p><?= e($category['description']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="featured-section public-section">
    <div class="site-container">
        <div class="section-heading-public">
            <div>
                <p class="public-eyebrow">پیشنهاد فرنوسا</p>
                <h2>محبوب‌های این روزها</h2>
            </div>
        </div>

        <div class="featured-grid">
            <?php foreach ($featuredItems as $item): ?>
                <article class="featured-card">
                    <a class="featured-image" href="<?= e(url('item.php?id=' . $item['id'])) ?>">
                        <img loading="lazy" src="<?= e(url($item['main_image'])) ?>" alt="<?= e($item['name_fa']) ?>">
                        <span><?= e($item['category_name']) ?></span>
                    </a>
                    <div class="featured-card-body">
                        <div>
                            <h3><?= e($item['name_fa']) ?></h3>
                            <small><?= e($item['name_en']) ?></small>
                        </div>
                        <p><?= e($item['short_description']) ?></p>
                        <div class="featured-card-bottom">
                            <div class="price-block">
                                <strong><?= format_price($item['price']) ?></strong>
                                <?php if ($item['old_price']): ?>
                                    <del><?= format_price($item['old_price']) ?></del>
                                <?php endif; ?>
                            </div>
                            <a href="<?= e(url('item.php?id=' . $item['id'])) ?>" aria-label="جزئیات <?= e($item['name_fa']) ?>">
                                <i class="fa-solid fa-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($specialOffer): ?>
<section class="offer-section public-section">
    <div class="site-container">
        <div class="offer-card">
            <div class="offer-copy">
                <p class="public-eyebrow">پیشنهاد محدود</p>
                <h2><?= e($specialOffer['title']) ?></h2>
                <p><?= e($specialOffer['description']) ?></p>
                <div class="offer-price">
                    <strong><?= format_price($specialOffer['offer_price']) ?></strong>
                    <del><?= format_price($specialOffer['price']) ?></del>
                </div>
                <a class="primary-public-button" href="<?= e(url('item.php?id=' . $specialOffer['id'])) ?>">
                    مشاهده جزئیات
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>
            <div class="offer-image">
                <img loading="lazy" src="<?= e(url($specialOffer['main_image'])) ?>" alt="<?= e($specialOffer['name_fa']) ?>">
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="about-section public-section" id="about">
    <div class="site-container about-grid">
        <div class="about-art">
            <img loading="lazy" src="<?= e(asset('images/brand/about.svg')) ?>" alt="فضای فرنوسا">
        </div>
        <div class="about-copy">
            <p class="public-eyebrow">درباره فرنوسا</p>
            <h2>یک منو نیست؛ دعوتی برای مکث و لذت‌بردن است.</h2>
            <p>
                فرنوسا با تمرکز بر کیفیت مواد اولیه، سرو دقیق و فضایی صمیمی شکل گرفته است.
                هر آیتم منو با دستور مشخص و استاندارد ثابت آماده می‌شود تا تجربه‌ای قابل اعتماد داشته باشید.
            </p>
            <div class="about-features">
                <span><i class="fa-solid fa-seedling"></i> مواد اولیه منتخب</span>
                <span><i class="fa-solid fa-award"></i> دستورهای استاندارد</span>
                <span><i class="fa-solid fa-heart"></i> آماده‌سازی با دقت</span>
            </div>
        </div>
    </div>
</section>
<?php require BASE_PATH . '/partials/public-footer.php'; ?>
