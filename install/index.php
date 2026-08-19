<?php
declare(strict_types=1);

$installerHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $installerHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

$basePath = dirname(__DIR__);
$configPath = $basePath . '/app/config/config.php';
$lockPath = $basePath . '/storage/install.lock';
$isInstalled = is_file($configPath) || is_file($lockPath);
$errors = [];
$success = false;

function installer_csrf_token(): string
{
    if (empty($_SESSION['installer_csrf'])) {
        $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION['installer_csrf'];
}

function installer_e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function detect_app_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/install/index.php');
    $base = preg_replace('~/install/index\.php$~', '', $script);

    return rtrim($scheme . '://' . $host . $base, '/');
}

/**
 * فایل SQL را بدون شکستن متن‌های داخل کوتیشن به دستورهای مستقل تقسیم می‌کند.
 *
 * @return list<string>
 */
function split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null;
    $escaped = false;
    $lineComment = false;
    $blockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($lineComment) {
            if ($char === "\n") {
                $lineComment = false;
                $buffer .= $char;
            }
            continue;
        }

        if ($blockComment) {
            if ($char === '*' && $next === '/') {
                $blockComment = false;
                $i++;
            }
            continue;
        }

        if ($quote === null) {
            if (($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2])))
                || $char === '#') {
                $lineComment = true;
                if ($char === '-') {
                    $i++;
                }
                continue;
            }

            if ($char === '/' && $next === '*') {
                $blockComment = true;
                $i++;
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
            continue;
        }

        $buffer .= $char;

        if ($escaped) {
            $escaped = false;
            continue;
        }

        if ($char === '\\' && $quote !== '`') {
            $escaped = true;
            continue;
        }

        if ($char === $quote) {
            if ($next === $quote && $quote !== '`') {
                $buffer .= $next;
                $i++;
                continue;
            }

            $quote = null;
        }
    }

    $remaining = trim($buffer);
    if ($remaining !== '') {
        $statements[] = $remaining;
    }

    return $statements;
}

function execute_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);

    if ($sql === false) {
        throw new RuntimeException('خواندن فایل SQL ممکن نیست: ' . basename($path));
    }

    foreach (split_sql_statements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

function write_config_file(string $path, array $config): void
{
    $content = "<?php\n";
    $content .= "declare(strict_types=1);\n\n";
    $content .= 'return ' . var_export($config, true) . ";\n";

    $temporary = $path . '.tmp';

    if (file_put_contents($temporary, $content, LOCK_EX) === false) {
        throw new RuntimeException('نوشتن فایل تنظیمات ممکن نیست. دسترسی پوشه app/config را بررسی کنید.');
    }

    @chmod($temporary, 0640);

    if (!rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('انتقال فایل تنظیمات نهایی انجام نشد.');
    }
}

$defaults = [
    'app_url' => detect_app_url(),
    'timezone' => 'Asia/Tehran',
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'fernosa_cms',
    'db_user' => 'root',
    'db_password' => '',
];

$values = $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isInstalled) {
    foreach (array_keys($defaults) as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? $defaults[$key]));
    }

    if (!hash_equals(installer_csrf_token(), (string)($_POST['_token'] ?? ''))) {
        $errors[] = 'نشست نصب منقضی شده است. صفحه را تازه‌سازی کنید.';
    }

    if (!extension_loaded('pdo_mysql')) {
        $errors[] = 'افزونه pdo_mysql روی PHP فعال نیست.';
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $values['db_name'])) {
        $errors[] = 'نام دیتابیس فقط می‌تواند شامل حروف انگلیسی، عدد و خط زیر باشد.';
    }

    if (!filter_var($values['app_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'نشانی سایت معتبر نیست.';
    }

    if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
        $errors[] = 'منطقه زمانی انتخاب‌شده معتبر نیست.';
    }

    if (!is_writable(dirname($configPath))) {
        $errors[] = 'پوشه app/config قابل نوشتن نیست.';
    }

    if (!is_writable(dirname($lockPath))) {
        $errors[] = 'پوشه storage قابل نوشتن نیست.';
    }

    if ($errors === []) {
        try {
            $databaseName = $values['db_name'];
            $databaseDsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $values['db_host'],
                (int)$values['db_port'],
                $databaseName
            );
            $pdoOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                // روی cPanel دیتابیس معمولاً از قبل ساخته شده و کاربر اجازه CREATE DATABASE ندارد.
                $pdo = new PDO(
                    $databaseDsn,
                    $values['db_user'],
                    $values['db_password'],
                    $pdoOptions
                );
            } catch (PDOException $databaseException) {
                $serverDsn = sprintf(
                    'mysql:host=%s;port=%d;charset=utf8mb4',
                    $values['db_host'],
                    (int)$values['db_port']
                );
                $serverPdo = new PDO(
                    $serverDsn,
                    $values['db_user'],
                    $values['db_password'],
                    $pdoOptions
                );
                $serverPdo->exec(
                    "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                );
                $pdo = new PDO(
                    $databaseDsn,
                    $values['db_user'],
                    $values['db_password'],
                    $pdoOptions
                );
            }

            execute_sql_file($pdo, $basePath . '/database/fernosa.sql');
            execute_sql_file($pdo, $basePath . '/database/seed.sql');

            $configuredHost = strtolower((string)(parse_url($values['app_url'], PHP_URL_HOST) ?? ''));
            $localHost = $configuredHost === 'localhost'
                || $configuredHost === '127.0.0.1'
                || $configuredHost === '::1'
                || str_ends_with($configuredHost, '.test')
                || str_ends_with($configuredHost, '.local');

            $config = [
                'app' => [
                    'name' => 'Fernosa',
                    'url' => rtrim($values['app_url'], '/'),
                    'auto_url' => $localHost,
                    'timezone' => $values['timezone'],
                    'debug' => $localHost,
                ],
                'database' => [
                    'host' => $values['db_host'],
                    'port' => (int)$values['db_port'],
                    'name' => $databaseName,
                    'username' => $values['db_user'],
                    'password' => $values['db_password'],
                    'charset' => 'utf8mb4',
                ],
                'security' => [
                    'session_name' => 'fernosa_session',
                    'session_lifetime' => 7200,
                    'remember_lifetime' => 2592000,
                    'login_max_attempts' => 5,
                    'login_lock_minutes' => 15,
                ],
            ];

            write_config_file($configPath, $config);
            file_put_contents(
                $lockPath,
                json_encode(
                    [
                        'installed_at' => date(DATE_ATOM),
                        'version' => '1.0.1-stage1-fix',
                    ],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ),
                LOCK_EX
            );
            @chmod($lockPath, 0640);

            session_regenerate_id(true);
            $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
            $success = true;
            $isInstalled = true;
        } catch (Throwable $exception) {
            $errors[] = 'نصب کامل نشد: ' . $exception->getMessage();
        }
    }
}

$checks = [
    ['PHP 8.2 یا بالاتر', version_compare(PHP_VERSION, '8.2.0', '>=')],
    ['افزونه PDO', extension_loaded('pdo')],
    ['افزونه pdo_mysql', extension_loaded('pdo_mysql')],
    ['افزونه mbstring', extension_loaded('mbstring')],
    ['پوشه app/config قابل نوشتن', is_writable(dirname($configPath))],
    ['پوشه storage قابل نوشتن', is_writable(dirname($lockPath))],
];
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نصب Fernosa CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/installer.css">
</head>
<body>
<main class="installer-shell">
    <section class="installer-brand">
        <span class="brand-mark">F</span>
        <div>
            <p class="eyebrow">FERNOSA DIGITAL MENU</p>
            <h1>نصب هوشمند فرنوسا</h1>
            <p>ساخت دیتابیس، داده‌های آزمایشی و تنظیمات اتصال در یک مرحله.</p>
        </div>
    </section>

    <section class="installer-card">
        <?php if ($success): ?>
            <div class="result success">
                <span class="result-icon">✓</span>
                <h2>نصب با موفقیت انجام شد</h2>
                <p>سامانه آماده استفاده است. در اولین ورود باید رمز عبور اولیه را تغییر دهید.</p>
                <div class="credentials">
                    <span>نام کاربری: <b>admin</b></span>
                    <span>رمز اولیه: <b>Fernosa@2026</b></span>
                </div>
                <div class="actions">
                    <a class="button primary" href="../admin/login.php">ورود به پنل مدیریت</a>
                    <a class="button ghost" href="../menu.php">مشاهده منوی عمومی</a>
                </div>
                <p class="security-note">برای امنیت بیشتر، پوشه <code>install</code> را پس از اطمینان از نصب حذف یا تغییر نام دهید.</p>
            </div>
        <?php elseif ($isInstalled): ?>
            <div class="result installed">
                <span class="result-icon">i</span>
                <h2>فرنوسا قبلاً نصب شده است</h2>
                <p>فایل تنظیمات و قفل نصب موجود هستند؛ نصب مجدد برای جلوگیری از بازنویسی اطلاعات متوقف شده است.</p>
                <div class="actions">
                    <a class="button primary" href="../admin/login.php">ورود به پنل</a>
                    <a class="button ghost" href="../menu.php">مشاهده منو</a>
                </div>
            </div>
        <?php else: ?>
            <div class="requirements">
                <div>
                    <p class="eyebrow">بررسی سرور</p>
                    <h2>پیش‌نیازهای نصب</h2>
                </div>
                <div class="check-grid">
                    <?php foreach ($checks as [$label, $passed]): ?>
                        <div class="check-item <?= $passed ? 'passed' : 'failed' ?>">
                            <span><?= $passed ? '✓' : '×' ?></span>
                            <?= installer_e($label) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($errors !== []): ?>
                <div class="alert error">
                    <strong>خطاهای نصب:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= installer_e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="installer-form" autocomplete="off">
                <input type="hidden" name="_token" value="<?= installer_e(installer_csrf_token()) ?>">

                <div class="form-section">
                    <div class="section-heading">
                        <span>۱</span>
                        <div>
                            <h3>تنظیمات سایت</h3>
                            <p>نشانی کامل محل نصب را وارد کنید.</p>
                        </div>
                    </div>
                    <div class="field-grid">
                        <label class="field full">
                            <span>نشانی سایت</span>
                            <input type="url" name="app_url" value="<?= installer_e($values['app_url']) ?>" required dir="ltr">
                        </label>
                        <label class="field">
                            <span>منطقه زمانی</span>
                            <select name="timezone">
                                <option value="Asia/Tehran" <?= $values['timezone'] === 'Asia/Tehran' ? 'selected' : '' ?>>Asia/Tehran</option>
                                <option value="Europe/Berlin" <?= $values['timezone'] === 'Europe/Berlin' ? 'selected' : '' ?>>Europe/Berlin</option>
                                <option value="UTC" <?= $values['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-heading">
                        <span>۲</span>
                        <div>
                            <h3>اتصال MySQL</h3>
                            <p>در XAMPP و Laragon معمولاً نام کاربری root و رمز خالی است.</p>
                        </div>
                    </div>
                    <div class="field-grid">
                        <label class="field">
                            <span>میزبان دیتابیس</span>
                            <input type="text" name="db_host" value="<?= installer_e($values['db_host']) ?>" required dir="ltr">
                        </label>
                        <label class="field">
                            <span>پورت</span>
                            <input type="number" name="db_port" value="<?= installer_e($values['db_port']) ?>" min="1" max="65535" required dir="ltr">
                        </label>
                        <label class="field">
                            <span>نام دیتابیس</span>
                            <input type="text" name="db_name" value="<?= installer_e($values['db_name']) ?>" required pattern="[A-Za-z0-9_]+" dir="ltr">
                        </label>
                        <label class="field">
                            <span>نام کاربری MySQL</span>
                            <input type="text" name="db_user" value="<?= installer_e($values['db_user']) ?>" required dir="ltr">
                        </label>
                        <label class="field full">
                            <span>رمز MySQL</span>
                            <input type="password" name="db_password" value="<?= installer_e($values['db_password']) ?>" dir="ltr">
                        </label>
                    </div>
                </div>

                <button class="install-button" type="submit">
                    <span>نصب و راه‌اندازی Fernosa</span>
                    <small>ساخت جدول‌ها، اطلاعات نمونه و حساب مدیر</small>
                </button>
            </form>
        <?php endif; ?>
    </section>

    <footer>Fernosa CMS — مرحله اول توسعه</footer>
</main>
</body>
</html>
