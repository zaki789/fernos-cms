<?php
declare(strict_types=1);

$metaTitle = $metaTitle ?? setting('site_title', 'فرنوسا | کافه و جلاتو');
$metaDescription = $metaDescription ?? setting('tagline', 'طعم اصیل، لحظه‌های ماندگار');
$currentPage = $currentPage ?? 'home';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="theme-color" content="<?= e(theme_setting('primary_color', '#013A17')) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e(url(basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php')))) ?>">
    <link rel="canonical" href="<?= e(url(basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php')))) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style><?= public_theme_variables() ?></style>
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
</head>
<body class="public-body page-<?= e($currentPage) ?>">
<header class="site-header" data-site-header>
    <div class="site-container header-inner">
        <a class="public-brand" href="<?= e(url()) ?>">
            <span class="public-brand-mark">F</span>
            <div>
                <b><?= e(setting('logo_text', 'Fernosa')) ?></b>
                <small>Café & Gelato</small>
            </div>
        </a>

        <nav class="desktop-nav" aria-label="ناوبری اصلی">
            <a class="<?= $currentPage === 'home' ? 'active' : '' ?>" href="<?= e(url()) ?>">خانه</a>
            <a class="<?= $currentPage === 'menu' ? 'active' : '' ?>" href="<?= e(url('menu.php')) ?>">منو</a>
            <a href="<?= e(url('about.php')) ?>">درباره فرنوسا</a>
            <a href="<?= e(url('contact.php')) ?>">تماس</a>
        </nav>

        <div class="header-actions">
            <a class="header-contact" href="tel:<?= e(preg_replace('/\D+/', '', (string)setting('phone', ''))) ?>">
                <i class="fa-solid fa-phone"></i>
                <span>تماس</span>
            </a>
            <button class="mobile-nav-toggle" type="button" data-mobile-nav-toggle aria-label="بازکردن منو">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>

    <nav class="mobile-nav" data-mobile-nav>
        <a href="<?= e(url()) ?>">خانه</a>
        <a href="<?= e(url('menu.php')) ?>">منوی کامل</a>
        <a href="<?= e(url('about.php')) ?>">درباره ما</a>
        <a href="<?= e(url('contact.php')) ?>">تماس</a>
    </nav>
</header>
<main>
