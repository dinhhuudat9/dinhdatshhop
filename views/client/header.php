<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>
<!doctype html>
<html class="h-100">
<script>
    // Load theme preference immediately to prevent flash
    (function() {
        var theme = localStorage.getItem('theme');
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    })();
</script>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= generateCSRFToken(); ?>">
    <link rel="canonical" href="<?= url(); ?>" />
    <title><?= isset($body['title']) ? $body['title'] : $CMSNT->site('title'); ?></title>
    <meta name="description" content="<?= isset($body['desc']) ? $body['desc'] : $CMSNT->site('description'); ?>" />
    <meta name="keywords" content="<?= isset($body['keyword']) ? $body['keyword'] : $CMSNT->site('keywords'); ?>">
    <meta name="copyright" content="<?= $CMSNT->site('author'); ?>" />
    <meta name="author" content="<?= $CMSNT->site('author'); ?>" />
    <meta property="og:url" content="<?= base_url(''); ?>">
    <meta property="og:site_name" content="<?= base_url(); ?>" />
    <meta property="og:title" content="<?= $body['title']; ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:image"
        content="<?= isset($body['image']) ? $body['image'] : BASE_URL($CMSNT->site('image')); ?>" />
    <meta property="og:image:secure"
        content="<?= isset($body['image']) ? $body['image'] : BASE_URL($CMSNT->site('image')); ?>" />
    <meta name="twitter:title" content="<?= $body['title']; ?>" />
    <meta name="twitter:image"
        content="<?= isset($body['image']) ? $body['image'] : BASE_URL($CMSNT->site('image')); ?>" />
    <meta name="twitter:image:alt" content="<?= $body['title']; ?>" />
    <link rel="icon" type="image/png" href="<?= BASE_URL($CMSNT->site('favicon')); ?>" />

    <!-- Preconnect để tăng tốc kết nối CDN -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://www.google.com" crossorigin>
    <link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://www.google.com">

    <!-- Critical CSS - Theme variables -->
    <style>
        :root {
            /* Primary Colors */
            --primary: <?= $CMSNT->site('theme_color'); ?>;
            --primary1: <?= $CMSNT->site('theme_color1'); ?>;

            /* Light Mode Colors */
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f7fafc;
            --bg-secondary: rgba(0, 0, 0, 0.03);
            --bg-tertiary: #f1f5f9;

            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-light: #94a3b8;

            --border-color: #e2e8f0;
            --border-light: rgba(0, 0, 0, 0.08);

            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);

            --input-bg: #ffffff;
            --input-border: #e2e8f0;
        }

        [data-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #334155;
            --bg-secondary: rgba(255, 255, 255, 0.05);
            --bg-tertiary: #334155;

            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --text-light: #64748b;

            --border-color: #334155;
            --border-light: rgba(255, 255, 255, 0.1);

            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);

            --input-bg: #1e293b;
            --input-border: #475569;
        }

        html {
            scroll-behavior: smooth;
        }

        .feature-content {
            padding-left: 0px;
            border-left: none;
        }
    </style>

    <!-- CSS Files - Load theo thứ tự ưu tiên -->
    <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>css/main.css">
    <link rel="stylesheet" href="<?= BASE_URL('mod/css/main.css?v=6'); ?>">

    <!-- Font CSS - Load với preload cho font quan trọng -->
    <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>fonts/flaticon/flaticon.css">
    <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>fonts/icofont/icofont.min.css">
    <link rel="stylesheet" href="<?= BASE_URL('public/client/'); ?>fonts/fontawesome/fontawesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL('public/fontawesome/'); ?>css/all.min.css">

    <!-- Plugin CSS - Load với media print trick để không block render -->
    <link rel="stylesheet" href="<?= BASE_URL('public/'); ?>sweetalert2/default.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="<?= BASE_URL('public/'); ?>cute-alert/style.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css" media="print" onload="this.media='all'">

    <!-- Noscript fallback cho CSS lazy load -->
    <noscript>
        <link rel="stylesheet" href="<?= BASE_URL('public/'); ?>sweetalert2/default.css">
        <link rel="stylesheet" href="<?= BASE_URL('public/'); ?>cute-alert/style.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    </noscript>

    <?php if ($CMSNT->site('google_analytics_status') == 1): ?>
        <!-- Google tag (gtag.js) - Đã có async -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $CMSNT->site('google_analytics_id'); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());
            gtag('config', '<?= $CMSNT->site('google_analytics_id'); ?>');
        </script>
    <?php endif ?>

    <?php if ($CMSNT->site('reCAPTCHA_status') == 1): ?>
        <!-- reCaptcha - Đã có async defer -->
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif ?>

    <!-- Header custom từ page -->
    <?= $body['header']; ?>

    <!-- Custom JS từ admin -->
    <?= $CMSNT->site('javascript_header'); ?>

    <!-- Core JS - jQuery cần load sớm vì nhiều script phụ thuộc -->
    <script src="<?= base_url('public/js/jquery-3.6.0.js'); ?>"></script>

    <!-- Plugin JS - Load với defer để không block render -->
    <script src="<?= BASE_URL('public/'); ?>sweetalert2/sweetalert2.js" defer></script>
    <script src="<?= BASE_URL('public/'); ?>cute-alert/cute-alert.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.min.js" defer></script>
    <script src="<?= base_url('mod/js/main.js'); ?>" defer></script>

    <?php if (!empty($CMSNT->site('font_family'))): ?>
        <style>
            body {
                <?= $CMSNT->site('font_family'); ?>
            }
        </style>
    <?php endif ?>
</head>

<script>
    // Khai báo biến global
    var baseUrl = '<?= base_url(); ?>';
    var BASE_URL = '<?= BASE_URL(); ?>';

    function showMessage(message, type) {
        const commonOptions = {
            effect: 'fade',
            speed: 300,
            customClass: null,
            customIcon: null,
            showIcon: true,
            showCloseButton: true,
            autoclose: true,
            autotimeout: 3000,
            gap: 20,
            distance: 20,
            type: 'outline',
            position: 'right top'
        };

        const options = {
            success: {
                status: 'success',
                title: '<?= __("Thành công!"); ?>',
                text: message,
            },
            error: {
                status: 'error',
                title: '<?= __("Thất bại!"); ?>',
                text: message,
            }
        };
        new Notify(Object.assign({}, commonOptions, options[type]));
    }

    // ===== CSRF PROTECTION =====
    function getCSRFToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }
</script>