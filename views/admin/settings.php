<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Setup body và header/footer
$body = [
    'title' => 'Settings',
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<!-- ckeditor -->
<script src="' . BASE_URL('public/ckeditor/ckeditor.js') . '"></script>
<!-- Thêm CSS của CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">

<!-- Thêm JavaScript của CodeMirror -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/monokai.min.css">
<!-- Mode HTML mixed (hỗ trợ HTML, CSS và JS) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/htmlmixed/htmlmixed.min.js"></script>
<!-- Mode cho CSS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/css/css.min.js"></script>
<!-- Mode cho JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/javascript/javascript.min.js"></script>
<!-- Mode cho XML (cần cho HTML) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/xml/xml.min.js"></script>

 
';
$body['footer'] = '
<!-- Select2 Cdn -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Internal Select-2.js -->
<script src="' . base_url('public/theme/') . 'assets/js/select2.js"></script>

 
';

// Require các file cần thiết
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');

// Xác định tab hiện tại
$current_tab = isset($_GET['tab']) ? check_string($_GET['tab']) : 'general';
$base_settings_url = base_url_admin('settings');

// Mapping các tab với file và permission tương ứng
$tab_config = [
    'general'           => ['file' => 'general.php',           'permission' => 'edit_general',           'icon' => 'bx bx-cog',                  'label' => __('Cài đặt chung')],
    'theme'             => ['file' => 'theme.php',             'permission' => 'edit_theme',             'icon' => 'bx bx-image',                'label' => __('Hình ảnh')],
    'color'             => ['file' => 'color.php',             'permission' => 'edit_color',             'icon' => 'fas fa-palette',             'label' => __('Màu sắc')],
    'shopkey'           => ['file' => 'shopkey.php',           'permission' => 'edit_shopkey',           'icon' => 'bx bx-cog',                  'label' => __('Cài đặt SHOPKEY')],
    'connection'        => ['file' => 'connection.php',        'permission' => 'edit_connection',        'icon' => 'bx bx-plug',                 'label' => __('Kết nối')],
    'notification'      => ['file' => 'notification.php',      'permission' => 'edit_notification',      'icon' => 'bx bx-bell',                 'label' => __('Thông báo')],
    'telegram-template' => ['file' => 'telegram-template.php', 'permission' => 'edit_telegram_template', 'icon' => 'fa-brands fa-telegram',      'label' => __('Telegram Template')],
    'mail-template'     => ['file' => 'mail-template.php',     'permission' => 'edit_mail_template',     'icon' => 'fa-solid fa-envelope',       'label' => __('Mail Template')],
    'security'          => ['file' => 'security.php',          'permission' => 'edit_security',          'icon' => 'fa-solid fa-shield-halved',  'label' => __('Bảo mật')],
    'widget'            => ['file' => 'widget.php',            'permission' => 'edit_widget',            'icon' => 'fa-brands fa-themeco',       'label' => __('Widget')],
    'cron-jobs'         => ['file' => 'cron-jobs.php',         'permission' => 'edit_cron_jobs',         'icon' => 'fa-solid fa-clock',          'label' => __('Cron Jobs')],
    'banners'           => ['file' => 'banners.php',           'permission' => 'edit_banners',           'icon' => 'fa-solid fa-rectangle-ad',   'label' => __('Banner')],
    'sliders'           => ['file' => 'sliders.php',           'permission' => 'edit_sliders',           'icon' => 'fa-solid fa-images',         'label' => __('Slider')],
];

// Lọc các tab mà user có quyền truy cập
$allowed_tabs = [];
foreach ($tab_config as $tab_key => $config) {
    if (checkPermission($getUser['admin'], $config['permission']) == true) {
        $allowed_tabs[$tab_key] = $config;
    }
}

// Nếu không có quyền nào, từ chối truy cập
if (empty($allowed_tabs)) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}

// Kiểm tra tab hợp lệ và có quyền, nếu không thì chuyển về tab đầu tiên có quyền
if (!array_key_exists($current_tab, $allowed_tabs)) {
    $current_tab = array_key_first($allowed_tabs);
}

// Biến permission cho tab hiện tại - các file con sử dụng để kiểm tra quyền khi xử lý POST
$current_tab_permission = $allowed_tabs[$current_tab]['permission'];
?>

<style>
    /* Ẩn pseudo-element :before của card title */
    .card.custom-card .card-header .card-title:before {
        display: none !important;
    }

    /* Hoặc có thể dùng cách này */
    .card.custom-card .card-header .card-title:before {
        content: none !important;
    }
</style>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-gear"></i> Cài đặt</h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Sidebar Navigation -->
                            <div class="col-xl-2">
                                <nav class="nav nav-tabs flex-column nav-style-5 mb-3" role="tablist">
                                    <?php foreach ($allowed_tabs as $tab_key => $config): ?>
                                        <a class="nav-link <?= $current_tab == $tab_key ? 'active' : ''; ?>" href="<?= $base_settings_url; ?>&tab=<?= $tab_key; ?>">
                                            <i class="<?= $config['icon']; ?> me-2 align-middle d-inline-block"></i><?= $config['label']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </nav>
                            </div>

                            <!-- Tab Content -->
                            <div class="col-xl-10">
                                <div class="tab-content">
                                    <?php
                                    // Include file tab tương ứng
                                    $tab_file = __DIR__ . '/settings/' . $allowed_tabs[$current_tab]['file'];
                                    if (file_exists($tab_file)) {
                                        require_once($tab_file);
                                    } else {
                                        // Fallback về tab đầu tiên có quyền nếu file không tồn tại
                                        $first_tab = array_key_first($allowed_tabs);
                                        require_once(__DIR__ . '/settings/' . $allowed_tabs[$first_tab]['file']);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    // Function để copy URL vào clipboard
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        const button = element.parentElement.querySelector('button');

        // Copy text to clipboard
        navigator.clipboard.writeText(element.value).then(function() {
            // Thay đổi icon và màu button tạm thời
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="ri-check-line"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');

            // Hiển thị thông báo
            showMessage('<?= __('Đã sao chép!'); ?>', 'success')

            // Khôi phục button sau 2 giây
            setTimeout(function() {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }).catch(function(err) {
            // Fallback cho trình duyệt cũ
            element.select();
            element.setSelectionRange(0, 99999);
            document.execCommand('copy');

            // Hiển thị thông báo
            showMessage('<?= __('Đã sao chép!'); ?>', 'success')
        });
    }
</script>