<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use Fernosa\Core\Csrf;
use Fernosa\Services\VisitService;

VisitService::track('page', 3);

$error = null;
$success = null;
$values = [
    'name' => '',
    'phone' => '',
    'email' => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::enforce($_POST['_token'] ?? null);

    foreach (array_keys($values) as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $honeypot = trim((string)($_POST['website'] ?? ''));
    $lastContact = (int)($_SESSION['_last_contact_at'] ?? 0);

    if ($honeypot !== '') {
        $error = 'درخواست نامعتبر است.';
    } elseif ((time() - $lastContact) < 30) {
        $error = 'برای ارسال پیام بعدی کمی فاصله زمانی لازم است.';
    } elseif (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 190) {
        $error = 'نام را به‌صورت صحیح وارد کنید.';
    } elseif ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'نشانی ایمیل معتبر نیست.';
    } elseif (mb_strlen($values['message']) < 10 || mb_strlen($values['message']) > 3000) {
        $error = 'متن پیام باید بین ۱۰ تا ۳۰۰۰ نویسه باشد.';
    } else {
        $statement = db()->prepare(
            'INSERT INTO contact_messages
                (name, phone, email, subject, message, status, ip_address, created_at)
             VALUES
                (:name, :phone, :email, :subject, :message, "new", :ip, NOW())'
        );
        $statement->execute([
            'name' => $values['name'],
            'phone' => mb_substr($values['phone'], 0, 50),
            'email' => mb_substr($values['email'], 0, 190),
            'subject' => mb_substr($values['subject'], 0, 255),
            'message' => $values['message'],
            'ip' => client_ip(),
        ]);

        $_SESSION['_last_contact_at'] = time();
        $success = 'پیام شما با موفقیت ثبت شد.';
        $values = array_fill_keys(array_keys($values), '');
        Csrf::rotate();
    }
}

$metaTitle = 'تماس با فرنوسا';
$metaDescription = 'اطلاعات تماس، ساعات کاری و فرم ارتباط با فرنوسا';
$currentPage = 'contact';

require BASE_PATH . '/partials/public-header.php';
?>
<section class="item-page-section public-section">
    <div class="site-container contact-layout">
        <article class="contact-info-card">
            <p class="public-eyebrow">ارتباط با ما</p>
            <h1>با فرنوسا در تماس باشید</h1>
            <p>برای پرسش درباره منو، رزرو و دریافت مسیر پیام بفرستید یا مستقیماً تماس بگیرید.</p>

            <div class="contact-info-list">
                <a href="tel:<?= e(preg_replace('/\D+/', '', (string)setting('phone', ''))) ?>">
                    <i class="fa-solid fa-phone"></i>
                    <span><small>تلفن</small><?= e(setting('phone', '02100000000')) ?></span>
                </a>
                <a href="mailto:<?= e(setting('email', 'hello@fernosa.ir')) ?>">
                    <i class="fa-solid fa-envelope"></i>
                    <span><small>ایمیل</small><?= e(setting('email', 'hello@fernosa.ir')) ?></span>
                </a>
                <div>
                    <i class="fa-solid fa-location-dot"></i>
                    <span><small>نشانی</small><?= e(setting('address', 'نشانی مجموعه فرنوسا')) ?></span>
                </div>
            </div>
        </article>

        <section class="contact-form-card">
            <h2>ارسال پیام</h2>

            <?php if ($error): ?>
                <div class="public-alert error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="public-alert success"><?= e($success) ?></div>
            <?php endif; ?>

            <form method="post" class="contact-form">
                <?= csrf_field() ?>
                <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off">

                <div class="contact-fields">
                    <label>
                        <span>نام و نام خانوادگی</span>
                        <input type="text" name="name" value="<?= e($values['name']) ?>" required maxlength="190">
                    </label>
                    <label>
                        <span>شماره تماس</span>
                        <input type="tel" name="phone" value="<?= e($values['phone']) ?>" maxlength="50" dir="ltr">
                    </label>
                    <label>
                        <span>ایمیل</span>
                        <input type="email" name="email" value="<?= e($values['email']) ?>" maxlength="190" dir="ltr">
                    </label>
                    <label>
                        <span>موضوع</span>
                        <input type="text" name="subject" value="<?= e($values['subject']) ?>" maxlength="255">
                    </label>
                    <label class="full">
                        <span>متن پیام</span>
                        <textarea name="message" rows="6" required maxlength="3000"><?= e($values['message']) ?></textarea>
                    </label>
                </div>

                <button class="primary-public-button" type="submit">
                    ثبت پیام
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
            </form>
        </section>
    </div>
</section>
<?php require BASE_PATH . '/partials/public-footer.php'; ?>
