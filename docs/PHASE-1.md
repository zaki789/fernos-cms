# گزارش فایل‌های مرحله اول Fernosa CMS

## فایل‌های پایه و نصب

- `.htaccess`
- `config.example.php`
- `install/index.php`
- `database/fernosa.sql`
- `database/seed.sql`
- `storage/.htaccess`
- `uploads/.htaccess`
- `README.md`
- `VERSION`

## هسته برنامه

- `app/bootstrap.php`
- `app/core/Database.php`
- `app/core/Session.php`
- `app/core/Csrf.php`
- `app/core/Auth.php`
- `app/helpers/functions.php`
- `app/services/VisitService.php`

## پنل مدیریت

- `admin/login.php`
- `admin/change-password.php`
- `admin/logout.php`
- `admin/index.php`
- `admin/includes/header.php`
- `admin/includes/sidebar.php`
- `admin/includes/footer.php`
- `assets/css/admin.css`
- `assets/js/admin.js`

## بخش عمومی و API

- `index.php`
- `menu.php`
- `item.php`
- `about.php`
- `contact.php`
- `api/menu.php`
- `api/item.php`
- `partials/public-header.php`
- `partials/public-footer.php`
- `assets/css/public.css`
- `assets/js/public.js`
- تصاویر SVG موجود در `assets/images/menu` و `assets/images/brand`

## نصب‌کننده

- `assets/css/installer.css`

## بررسی‌های انجام‌شده

- بررسی Syntax تمام فایل‌های PHP با `php -l`
- بررسی Syntax فایل‌های JavaScript با `node --check`
- بررسی تطبیق Hash رمز اولیه با `password_verify`
- بررسی وجود تمام تصاویر ارجاع‌شده در داده‌های آزمایشی
- بررسی تقسیم صحیح دستورات فایل‌های SQL توسط الگوریتم Installer
- بررسی Redirect خودکار پروژه نصب‌نشده به `/install/`

اجرای واقعی Queryها روی MySQL در محیط ساخت انجام نشده است، زیرا سرویس MySQL و افزونه `pdo_mysql` در این محیط در دسترس نبودند. Installer قبل از نصب، وجود `pdo_mysql` را بررسی می‌کند و Schema برای MySQL/MariaDB طراحی شده است.
