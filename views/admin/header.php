<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<!doctype html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="dark" data-toggled="close">

<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=no'>
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= generateCSRFToken(); ?>">
    <title><?= isset($body['title']) ? $body['title'] : $CMSNT->site('title'); ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL($CMSNT->site('favicon')); ?>" />
    <!-- Choices JS -->
    <script src="<?= base_url('public/theme/'); ?>assets/libs/choices.js"></script>

    <!-- Main Theme Js -->
    <script src="<?= base_url('public/theme/'); ?>assets/js/main.js"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="<?= base_url('public/theme/'); ?>assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Style Css -->
    <link href="<?= base_url('public/theme/'); ?>assets/css/styles.min.css" rel="stylesheet">

    <!-- Icons Css -->
    <link href="<?= base_url('public/theme/'); ?>assets/css/icons.css" rel="stylesheet">

    <!-- Node Waves Css -->
    <link href="<?= base_url('public/theme/'); ?>assets/libs/node-waves/waves.min.css" rel="stylesheet">

    <!-- Simplebar Css -->
    <link href="<?= base_url('public/theme/'); ?>assets/libs/simplebar/simplebar.min.css" rel="stylesheet">

    <!-- Color Picker Css -->
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/@simonwep/pickr/themes/nano.min.css">

    <!-- Prism CSS -->
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/prismjs/themes/prism-coy.min.css">

    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/@simonwep/pickr/themes/classic.min.css">
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/@simonwep/pickr/themes/monolith.min.css">
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/@simonwep/pickr/themes/nano.min.css">


    <!-- Choices Css -->
    <link rel="stylesheet"
        href="<?= base_url('public/theme/'); ?>assets/libs/choices.js/public/assets/styles/choices.min.css">

    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/glightbox/css/glightbox.min.css">

    <!-- Simple Notify CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.css" />
    <!-- Simple Notify JS -->
    <script src="https://cdn.jsdelivr.net/npm/simple-notify@1.0.4/dist/simple-notify.min.js"></script>

    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/sweetalert2/sweetalert2.min.css">
    <!-- Sweetalerts JS -->
    <script src="<?= base_url('public/theme/'); ?>assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= base_url('public/theme/'); ?>assets/js/sweet-alerts.js"></script>

    <!-- SwiperJS Css -->
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/libs/swiper/swiper-bundle.min.css">

    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/css/styles.css">
    <!-- Cute Alert -->
    <link class="main-stylesheet" href="<?= BASE_URL('public/'); ?>cute-alert/style.css" rel="stylesheet" type="text/css">
    <script src="<?= BASE_URL('public/'); ?>cute-alert/cute-alert.js"></script>

    <script src="<?= base_url('public/js/'); ?>jquery-3.6.0.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL('public/fontawesome/'); ?>css/all.min.css">


    <script src="<?= BASE_URL('public/ckeditor/ckeditor.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

    <!-- jQuery UI - Dành cho chức năng kéo thả sắp xếp -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <!-- Tùy chỉnh CSS -->
    <link rel="stylesheet" href="<?= base_url('public/theme/'); ?>assets/css/mod.css?v=1.0.0">

    <?= $body['header']; ?>
</head>
<style>
    body {
        font-family: "Roboto", sans-serif;
    }
</style>


<script>
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
                title: 'Thành công!',
                text: message,
            },
            error: {
                status: 'error',
                title: 'Thất bại!',
                text: message,
            }
        };
        new Notify(Object.assign({}, commonOptions, options[type]));
    }

    // ===== CSRF PROTECTION =====
    // Đọc CSRF token từ meta tag (bảo mật hơn so với biến global)
    function getCSRFToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Biến global để truy cập CSRF token dễ dàng
    var CSRF_TOKEN = getCSRFToken();

    // Tự động thêm CSRF token vào tất cả AJAX POST requests
    // TRƯỚC khi bất kỳ script nào (như DataTable) gọi AJAX
    $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
        // Chỉ thêm CSRF token cho POST requests
        if (options.type && options.type.toUpperCase() === 'POST') {
            var csrfToken = getCSRFToken();

            // Nếu originalOptions.data là object (chưa bị serialize)
            if (originalOptions.data && typeof originalOptions.data === 'object' && !(originalOptions.data instanceof FormData)) {
                // Thêm csrf_token vào data object
                originalOptions.data.csrf_token = csrfToken;
                // Re-serialize data
                options.data = $.param(originalOptions.data);
            }
            // Nếu options.data là string (đã serialize)
            else if (typeof options.data === 'string') {
                if (options.data.indexOf('csrf_token=') === -1) {
                    options.data += (options.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(csrfToken);
                }
            }
            // Nếu là FormData
            else if (originalOptions.data instanceof FormData) {
                originalOptions.data.append('csrf_token', csrfToken);
            }
            // Nếu không có data
            else if (!options.data) {
                options.data = 'csrf_token=' + encodeURIComponent(csrfToken);
            }
        }
    });
</script>