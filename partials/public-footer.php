</main>
<footer class="site-footer" id="contact">
    <div class="site-container footer-grid">
        <div class="footer-about">
            <a class="public-brand footer-brand" href="<?= e(url()) ?>">
                <span class="public-brand-mark">F</span>
                <div>
                    <b><?= e(setting('logo_text', 'Fernosa')) ?></b>
                    <small>Café & Gelato</small>
                </div>
            </a>
            <p><?= e(setting('tagline', 'طعم اصیل، لحظه‌های ماندگار')) ?></p>
        </div>

        <div>
            <h3>دسترسی سریع</h3>
            <a href="<?= e(url('menu.php')) ?>">منوی دیجیتال</a>
            <a href="<?= e(url('about.php')) ?>">درباره فرنوسا</a>
            <a href="<?= e(url('admin/login.php')) ?>">ورود مدیریت</a>
        </div>

        <div>
            <h3>ارتباط با ما</h3>
            <a href="tel:<?= e(preg_replace('/\D+/', '', (string)setting('phone', ''))) ?>">
                <?= e(setting('phone', '02100000000')) ?>
            </a>
            <a href="mailto:<?= e(setting('email', 'hello@fernosa.ir')) ?>">
                <?= e(setting('email', 'hello@fernosa.ir')) ?>
            </a>
            <span><?= e(setting('address', 'نشانی مجموعه فرنوسا')) ?></span>
        </div>

        <div>
            <h3>ساعات کاری</h3>
            <?php foreach ((array)setting('working_hours', []) as $day => $hours): ?>
                <span><?= e($day) ?>: <?= e($hours) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="site-container footer-bottom">
        <span>© <?= e(date('Y')) ?> <?= e(setting('footer_text', 'تمام حقوق برای فرنوسا محفوظ است.')) ?></span>
        <span>طراحی اختصاصی با PHP و MySQL</span>
    </div>
</footer>
<script src="<?= e(asset('js/public.js')) ?>"></script>
</body>
</html>
