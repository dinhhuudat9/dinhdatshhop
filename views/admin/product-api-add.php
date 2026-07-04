<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Thêm API cần kết nối'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="' . BASE_URL('public/AdminLTE3/') . 'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__ . '/../../libs/suppliers/SupplierApiFactory.php');
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');

if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
// Lấy tất cả API configs để render động
$allApiConfigs = SupplierApiFactory::getAllApiConfigs();


if (isset($_POST['submit'])) {
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }
    if (empty($_POST['type'])) {
        die('<script type="text/javascript">if(!alert("' . __('Vui lòng chọn loại API cần kết nối') . '")){window.history.back().location.reload();}</script>');
    }
    $type = check_string($_POST['type']);
    if (empty($_POST['domain'])) {
        die('<script type="text/javascript">if(!alert("' . __('Vui lòng nhập domain cần kết nối') . '")){window.history.back().location.reload();}</script>');
    }
    $domain = check_string($_POST['domain']);
    $price = '';
    $token = !empty($_POST['token']) ? check_string($_POST['token']) : NULL;

    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if ($checkKey['status'] != true) {
        die('<script type="text/javascript">if(!alert("' . $checkKey['msg'] . '")){window.history.back().location.reload();}</script>');
    }

    // Tạo supplier data tạm để test kết nối
    $temp_supplier = [
        'type' => $type,
        'domain' => $domain,
        'username' => !empty($_POST['username']) ? check_string($_POST['username']) : '',
        'password' => !empty($_POST['password']) ? check_string($_POST['password']) : '',
        'api_key' => !empty($_POST['api_key']) ? check_string($_POST['api_key']) : '',
        'proxy' => !empty($_POST['proxy']) ? check_string($_POST['proxy']) : '',
        'coupon' => !empty($_POST['coupon']) ? check_string($_POST['coupon']) : ''
    ];

    // Validate form data động theo API type
    $validation = SupplierApiFactory::validateFormData($type, $_POST);
    if (!$validation['valid']) {
        die('<script type="text/javascript">if(!alert("' . addslashes($validation['error']) . '")){window.history.back().location.reload();}</script>');
    }

    // Kiểm tra API được hỗ trợ và test kết nối
    $api = SupplierApiFactory::create($temp_supplier);
    if (!$api) {
        die('<script type="text/javascript">if(!alert("' . __('Loại API không được hỗ trợ') . '")){window.history.back().location.reload();}</script>');
    }

    // Test kết nối trước để lấy lỗi cụ thể
    $testResult = $api->testConnection();
    $testSuccess = is_array($testResult) ? ($testResult['success'] ?? false) : (bool)$testResult;
    if (!$testSuccess) {
        $errorMsg = is_array($testResult) && isset($testResult['message']) ? addslashes($testResult['message']) : __('Không thể kết nối đến API');
        die('<script type="text/javascript">if(!alert("' . __('Kết nối API thất bại:') . ' ' . $errorMsg . '")){window.history.back().location.reload();}</script>');
    }

    $balance = $api->getBalance();
    if ($balance === null) {
        die('<script type="text/javascript">if(!alert("' . __('Không thể lấy số dư từ API. Vui lòng kiểm tra lại thông tin xác thực.') . '")){window.history.back().location.reload();}</script>');
    }
    $price = format_currency($balance);
    $isInsert = $CMSNT->insert('suppliers', [
        'user_id'           => $getUser['id'],
        'type'              => $type,
        'domain'            => $domain,
        'username'          => !empty($_POST['username']) ? check_string($_POST['username']) : NULL,
        'password'          => !empty($_POST['password']) ? check_string($_POST['password']) : NULL,
        'api_key'           => !empty($_POST['api_key']) ? check_string($_POST['api_key']) : NULL,
        'token'             => $token,
        'coupon'            => !empty($_POST['coupon']) ? check_string($_POST['coupon']) : NULL,
        'price'             => check_string($price),
        'discount'          => check_string($_POST['discount']),
        'update_name'       => check_string($_POST['update_name']),
        'sync_category'     => !empty($_POST['sync_category']) ? check_string($_POST['sync_category']) : 'OFF',
        'sync_image'        => !empty($_POST['sync_image']) ? check_string($_POST['sync_image']) : 'ON',
        'child'             => isset($_POST['child']) ? intval($_POST['child']) : 0,
        'isAutoShow'        => isset($_POST['isAutoShow']) ? intval($_POST['isAutoShow']) : 0,
        'rate'              => !empty($_POST['rate']) ? check_string($_POST['rate']) : 1,
        'update_price'      => check_string($_POST['update_price']),
        'roundMoney'        => check_string($_POST['roundMoney']),
        'status'            => 1,
        'check_string_api'  => check_string($_POST['check_string_api']),
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add API Supplier (" . check_string($_POST['domain']) . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add API Supplier (" . check_string($_POST['domain']) . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Thêm thành công !') . '")){location.href = "' . base_url_admin('product-api') . '";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thêm thất bại !') . '")){window.history.back().location.reload();}</script>');
    }
}

$domain = '';
if (!empty($_GET['domain'])) {
    $domain = check_string($_GET['domain']);
}
$type = '';
if (!empty($_GET['type'])) {
    $type = check_string($_GET['type']);
}
?>




<div class="main-content app-content">

    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button"
                    class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?= base_url_admin('product-api'); ?>"><i class="fa-solid fa-arrow-left"></i></a> <?= __('Thêm API cần kết nối'); ?>
            </h1>
        </div>


        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-xl-7">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                <?= __('NHẬP THÔNG TIN API CẦN KẾT NỐI'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-5 gy-3">
                                <div class="col-12 mb-2">
                                    <div class="api-section p-3 rounded bg-light mb-3">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-plug-circle-plus text-primary"></i> <?= __('Thông tin kết nối API'); ?></h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="api-select">
                                                    <i class="fa-solid fa-server text-info"></i> <?= __('Loại API:'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select form-select-lg shadow-sm" id="api-select" name="type" required>
                                                    <option value=""><?= __('-- Chọn loại API --'); ?></option>
                                                    <?php foreach ($allApiConfigs as $apiType => $apiConfig): ?>
                                                        <option <?= $type == $apiType ? 'selected' : ''; ?> value="<?= $apiType; ?>" class="bg-success-subtle">
                                                            <?= htmlspecialchars($apiConfig['display_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text"><i class="fas fa-info-circle"></i> <?= __('API CMSNT được hỗ trợ miễn phí, API khác tính phí 200.000đ/lần'); ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="domain">
                                                    <i class="fa-solid fa-globe text-primary"></i> <?= __('Domain'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group input-group-lg">
                                                    <span class="input-group-text bg-light"><i class="fas fa-link"></i></span>
                                                    <input type="text" class="form-control shadow-sm" id="domain" value="<?= $domain; ?>"
                                                        placeholder="VD: https://domain.com/" name="domain" autocomplete="off"
                                                        data-lpignore="true" required>
                                                </div>
                                                <div class="form-text"><i class="fas fa-info-circle"></i> <?= __('Nhập đầy đủ URL kèm https:// hoặc http://'); ?></div>
                                            </div>
                                        </div>

                                        <!-- Thông tin đăng nhập -->
                                        <div class="credentials-container mt-3">
                                            <div class="row">
                                                <div class="col-md-6 mb-3" id="username" style="display: none;">
                                                    <label class="form-label fw-bold" for="username-input">
                                                        <i class="fa-solid fa-user text-warning"></i> <?= __('Username:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-user"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="username-input" name="username"
                                                            placeholder="<?= __('Nhập tên đăng nhập website API'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="password" style="display: none;">
                                                    <label class="form-label fw-bold" for="password-input">
                                                        <i class="fa-solid fa-key text-warning"></i> <?= __('Password:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                                                        <input type="password" class="form-control shadow-sm" id="password-input" name="password"
                                                            placeholder="<?= __('Nhập mật khẩu đăng nhập website API'); ?>">
                                                        <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="api_key" style="display: none;">
                                                    <label class="form-label fw-bold" for="api-key-input">
                                                        <i class="fa-solid fa-key text-danger"></i> <?= __('API Key:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="api-key-input" name="api_key"
                                                            placeholder="<?= __('Nhập Api Key trong website API'); ?>">
                                                    </div>
                                                    <div class="form-text" id="api-key-hint"></div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="api_secret" style="display: none;">
                                                    <label class="form-label fw-bold" for="api-secret-input">
                                                        <i class="fa-solid fa-shield-halved text-primary"></i> <?= __('API Secret:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-lock"></i></span>
                                                        <input type="password" class="form-control shadow-sm" id="api-secret-input" name="password"
                                                            autocomplete="new-password"
                                                            placeholder="<?= __('Nhập API Secret (X-API-Secret)'); ?>">
                                                        <button class="btn btn-outline-secondary" type="button" id="toggle-api-secret">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text"><i class="fas fa-info-circle"></i> <?= __('API Secret bắt đầu bằng sk_secret_'); ?></div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="token" style="display: none;">
                                                    <label class="form-label fw-bold" for="token-input">
                                                        <i class="fa-solid fa-shield-halved text-success"></i> <?= __('Token:'); ?>
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-shield-alt"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="token-input" name="token"
                                                            placeholder="<?= __('Nhập Token trong website API'); ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3" id="coupon" style="display: none;">
                                                    <label class="form-label fw-bold" for="coupon-input">
                                                        <i class="fa-solid fa-tag text-info"></i> <?= __('Coupon:'); ?>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-percentage"></i></span>
                                                        <input type="text" class="form-control shadow-sm" id="coupon-input" name="coupon"
                                                            placeholder="<?= __('Nhập mã giảm giá nếu có'); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cài đặt đồng bộ -->
                                <div class="col-12 mb-2">
                                    <div class="api-section p-3 rounded bg-light mb-3">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-sliders text-success"></i> <?= __('Cài đặt đồng bộ dữ liệu'); ?></h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3" id="sync_category" style="display: none;">
                                                <label class="form-label fw-bold" for="sync-category-select">
                                                    <i class="fa-solid fa-folder-tree text-primary"></i> <?= __('Đồng bộ chuyên mục từ API'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="sync-category-select" name="sync_category" required>
                                                    <option value="OFF"><?= __('OFF - Không đồng bộ'); ?></option>
                                                    <option value="ON"><?= __('ON - Đồng bộ tự động'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Hệ thống sẽ tự động đồng bộ và thêm chuyên mục từ API.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="child_sync" style="display: none;">
                                                <label class="form-label fw-bold" for="child-select">
                                                    <i class="fa-solid fa-sitemap text-warning"></i> <?= __('Đồng bộ cấu trúc như web con'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="child-select" name="child" required>
                                                    <option value="0"><?= __('OFF - Cấu trúc thông thường (Con → Sản phẩm)'); ?></option>
                                                    <option value="1"><?= __('ON - Cấu trúc web con (Cha → Con → Sản phẩm)'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Chọn ON nếu API có cấu trúc Chuyên mục cha → Chuyên mục con → Sản phẩm.'); ?>
                                                </div>
                                                <div class="alert alert-warning mt-2" id="child-warning" style="display: none;">
                                                    <i class="fas fa-exclamation-triangle"></i> <strong><?= __('Lưu ý:'); ?></strong> <?= __('Khi bật chế độ này, hệ thống sẽ tự động đồng bộ theo cấu trúc 3 cấp (Cha → Con → Sản phẩm) và tự động cập nhật chuyên mục, mô tả ngắn, mô tả, ưu tiên khi API thay đổi.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="auto_show" style="display: none;">
                                                <label class="form-label fw-bold" for="auto-show-select">
                                                    <i class="fa-solid fa-toggle-on text-success"></i> <?= __('Tự động bật trạng thái sản phẩm'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="auto-show-select" name="isAutoShow" required>
                                                    <option value="0"><?= __('OFF - Giữ trạng thái ẩn khi đồng bộ'); ?></option>
                                                    <option value="1"><?= __('ON - Tự động hiển thị sản phẩm'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Khi ON, sản phẩm sẽ tự động được bật trạng thái hiển thị sau khi đồng bộ.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="update-price-select">
                                                    <i class="fa-solid fa-sack-dollar text-success"></i> <?= __('Cập nhật giá bán tự động'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="update-price-select" name="update_price" required>
                                                    <option value="ON"><?= __('ON - Cập nhật tự động'); ?></option>
                                                    <option value="OFF"><?= __('OFF - Giữ nguyên giá'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Khi giá sản phẩm thay đổi ở API, hệ thống sẽ tự động cập nhật.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="round-money-select">
                                                    <i class="fa-solid fa-circle-dollar-to-slot text-primary"></i> <?= __('Làm tròn giá bán'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="round-money-select" name="roundMoney" required>
                                                    <option value="OFF"><?= __('OFF - Giữ nguyên số'); ?></option>
                                                    <option value="ON"><?= __('ON - Làm tròn số'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('VD:'); ?> <?= format_currency(10550); ?> <?= __('sẽ làm tròn thành'); ?> <?= format_currency(10600); ?> <?= __('hoặc'); ?> <?= format_currency(10530); ?> <?= __('sẽ làm tròn thành'); ?> <?= format_currency(10500); ?>.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="discount-input">
                                                    <i class="fa-solid fa-percent text-danger"></i> <?= __('Tăng giá so với giá gốc'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control shadow-sm" id="discount-input" value="0" min="0"
                                                        placeholder="<?= __('Nhập % tăng giá'); ?>" name="discount" required>
                                                    <span class="input-group-text bg-light">%</span>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Nhập 10 để tăng giá bán thêm 10% so với giá gốc, nhập 0 để giữ nguyên.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3" id="rate_field" style="display: none;">
                                                <label class="form-label fw-bold" for="rate-input">
                                                    <i class="fa-solid fa-percent text-danger"></i> <?= __('Tỷ giá tiền tệ quốc tế (nếu có)'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control shadow-sm"
                                                        id="rate-input" value="1" min="0"
                                                        placeholder="<?= __('Nhập tỷ giá'); ?>" name="rate" required>
                                                    <span class="input-group-text bg-light"><?= currencyDefault(); ?></span>
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Nếu giá dịch vụ của API giống giá tiền tệ của bạn, hãy nhập 1. Nếu website bạn sử dụng USD nhưng giá dịch vụ API là VND, hãy nhập tỷ giá của 1 VND ~0,000038. Nếu giá của bạn là VND nhưng giá của API là USD, hãy nhập tỷ giá của 1 USD ~26.000'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="update-name-select">
                                                    <i class="fa-solid fa-font text-info"></i> <?= __('Cập nhật tên & mô tả tự động'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="update-name-select" name="update_name" required>
                                                    <option value="ON"><?= __('ON - Cập nhật tự động'); ?></option>
                                                    <option value="OFF"><?= __('OFF - Giữ nguyên nội dung'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Tự động cập nhật tên và mô tả sản phẩm từ API.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="sync-image-select">
                                                    <i class="fa-solid fa-images text-purple"></i> <?= __('Đồng bộ Ảnh từ API'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="sync-image-select" name="sync_image" required>
                                                    <option value="ON"><?= __('ON - Đồng bộ ảnh tự động'); ?></option>
                                                    <option value="OFF"><?= __('OFF - Không đồng bộ ảnh'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Tự động đồng bộ ảnh sản phẩm từ API khi cập nhật.'); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold" for="check-string-api-select">
                                                    <i class="fa-solid fa-code text-warning"></i> <?= __('Lọc HTML trong nội dung API'); ?>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select" id="check-string-api-select" name="check_string_api" required>
                                                    <option value="ON"><?= __('ON - Kích hoạt bảo vệ'); ?></option>
                                                    <option value="OFF"><?= __('OFF - Tắt bảo vệ'); ?></option>
                                                </select>
                                                <div class="form-text">
                                                    <i class="fas fa-shield-alt text-danger"></i> <?= __('Bảo vệ website bằng cách lọc mã HTML độc hại từ API.'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mb-2">
                                    <div class="api-section p-3 rounded bg-light mb-3">
                                        <h5 class="border-bottom pb-2 mb-3"><i class="fa-solid fa-sliders text-success"></i> <?= __('Cài đặt khác'); ?></h5>
                                        <div class="row">

                                            <div class="col-md-6 mb-3" id="proxy" style="display: none;">
                                                <label class="form-label fw-bold" for="proxy-input">
                                                    <i class="fa-solid fa-globe text-danger"></i> <?= __('Proxy v4 hoặc v6 (nếu có):'); ?>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control shadow-sm" id="proxy-input"
                                                        placeholder="ip:port:username:password" name="proxy" autocomplete="off">
                                                </div>
                                                <div class="form-text">
                                                    <i class="fas fa-info-circle"></i> <?= __('Chỉ dùng Proxy nếu quý khách đã nhờ phía API whitelist IP nhưng vẫn không hiện số dư sau khi kết nối.'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" name="submit" class="btn btn-primary btn-lg shadow-lg btn-wave">
                                    <i class="fa-solid fa-plug-circle-bolt me-1"></i> <?= __('Kết nối API ngay'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="card custom-card position-sticky" style="top: 85px;">
                        <div class="card-header bg-primary">
                            <div class="card-title">
                                <i class="fa-solid fa-circle-info me-1"></i> <?= __('LƯU Ý'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-primary" role="alert">
                                <i class="fa-solid fa-lightbulb me-1"></i> <strong><?= __('Mục đích:'); ?></strong> <?= __('Chức năng này cho phép quý khách bán lại sản phẩm của website khác trên chính website của quý khách.'); ?>
                            </div>

                            <div class="alert alert-warning mb-3" role="alert">
                                <h6 class="alert-heading"><i class="fa-solid fa-triangle-exclamation me-1"></i> <?= __('Lưu ý quan trọng!'); ?></h6>
                                <p><?= __('Trường hợp quý khách cấu hình đúng nhưng không hiện số dư API có thể do máy chủ không thể kết nối với API đích.'); ?></p>
                                <a href="https://help.cmsnt.co/huong-dan/ket-noi-api-nhap-dung-thong-tin-nhung-khong-ra-so-du-thi-lam-sao/" class="btn btn-sm btn-warning mt-2" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i> <?= __('Xem hướng dẫn xử lý'); ?>
                                </a>
                            </div>

                            <div class="d-flex align-items-center p-3 rounded bg-light mb-3">
                                <div class="me-3 text-primary fs-3"><i class="fa-solid fa-handshake"></i></div>
                                <div>
                                    <h6 class="mb-1"><?= __('API cùng hệ sinh thái CMSNT'); ?></h6>
                                    <p class="mb-0 text-success fw-bold"><?= __('Miễn phí'); ?></p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center p-3 rounded bg-light mb-3">
                                <div class="me-3 text-warning fs-3"><i class="fa-solid fa-circle-dollar-to-slot"></i></div>
                                <div>
                                    <h6 class="mb-1"><?= __('API ngoài hệ sinh thái'); ?></h6>
                                    <p class="mb-0"><?= __('Phí tích hợp:'); ?> <span class="text-danger fw-bold"><?= __('liên hệ báo giá chi tiết'); ?></span></p>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <a href="https://www.cmsnt.co/p/contact.html" class="btn btn-outline-primary" target="_blank">
                                    <i class="fa-solid fa-headset me-1"></i> <?= __('Liên hệ hỗ trợ kết nối API'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>


        <style>
            .brand-carousel {
                width: 100%;
                overflow: hidden;
                animation: moveCards 25s linear infinite;
                white-space: nowrap;
            }

            .brand-carousel-container {
                width: 100%;
                overflow-x: auto;
            }

            .brand-carousel {
                white-space: nowrap;
                font-size: 0;
                width: max-content;
            }

            .brand-card {
                font-size: 16px;
                display: inline-block;
                vertical-align: top;
                margin-right: 20px;
                transition: all 0.3s ease;
            }

            .brand-carousel:hover {
                animation-play-state: paused;
            }

            @keyframes moveCards {
                0% {
                    transform: translateX(0%);
                }

                100% {
                    transform: translateX(-100%);
                }
            }

            .brand-card {
                position: relative;
                display: inline-block;
                margin: 10px;
                vertical-align: middle;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 20px;
                transition: all 0.3s ease;
            }

            .brand-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            .brand-card img {
                width: 100px;
                height: 100px;
                object-fit: contain;
                margin-bottom: 20px;
            }

            .connect-button,
            .website-button {
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                color: #fff;
                padding: 5px 10px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
            }

            .brand-card:hover .connect-button,
            .brand-card:hover .website-button {
                opacity: 1;
            }

            .website-button {
                bottom: 45px;
            }

            .api-section {
                border-left: 4px solid #3498db;
                transition: all 0.3s ease;
            }

            .api-section:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
        </style>
        <div class="row justify-content-center py-4">
            <div class="col-12 text-center mb-3">
                <h4 class="fw-bold"><i class="fa-solid fa-boxes-packing text-primary me-2"></i><?= __('Nhà cung cấp API gợi ý'); ?></h4>
                <p class="text-muted"><?= __('Kết nối nhanh với các nhà cung cấp API đáng tin cậy'); ?></p>
            </div>
            <div class="brand-carousel-container">
                <div class="brand-carousel animated-carousel">

                </div>
            </div>
            <div class="mt-3 text-center" id="notitcation_suppliers"></div>
        </div>
        <script>
            $(document).ready(function() {
                $('.brand-carousel').html('');
                $.ajax({
                    url: 'https://api.cmsnt.co/suppliers.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // Xử lý dữ liệu trả về từ server
                        if (response && response.suppliers.length > 0) {
                            var html = '';
                            $.each(response.suppliers, function(index, brand) {
                                html += '<div class="brand-card">';
                                html += '<img src="' + brand.logo + '" alt="Logo" class="mb-2">';
                                html +=
                                    '<a href="<?= base_url_admin("product-api-add"); ?>&domain=' +
                                    brand.domain + '&type=' + brand.type +
                                    '" class="connect-button btn btn-sm btn-danger">Kết nối</a>';
                                html += '<a href="' + brand.domain +
                                    '?utm_source=ads_cmsnt" target="_blank" class="website-button btn btn-sm btn-primary">Xem</a>';
                                html += '</div>';
                            });
                            $('.brand-carousel').html(html);
                            $('#notitcation_suppliers').html(response.notication);
                            calculateAndSetAnimationDuration();
                        } else {
                            $('.brand-carousel').html('');
                        }
                    },
                    error: function() {
                        $('.brand-carousel').html('');
                    }
                });
            });
            // Function to calculate carousel width and set animation duration
            function calculateAndSetAnimationDuration() {
                var carousel = $('.animated-carousel');
                var carouselWidth = carousel[0].scrollWidth;
                var cardWidth = carousel.children().first().outerWidth(true); // Including margin
                var numberOfCards = carouselWidth / cardWidth;
                var animationDuration = numberOfCards * 2; // Adjust this multiplier as needed
                carousel.css('animation-duration', animationDuration + 's');
            }
        </script>


    </div>
</div>



<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ngăn chặn autofill bằng cách thêm một trường ẩn và đảm bảo không tự động điền
        const form = document.querySelector('form');
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'text';
        hiddenInput.style.display = 'none';
        hiddenInput.name = 'prevent_autofill';
        hiddenInput.setAttribute('autocomplete', 'off');
        form.prepend(hiddenInput);

        // Thêm thuộc tính autocomplete="new-password" vào tất cả các trường input
        const allInputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        allInputs.forEach(input => {
            input.setAttribute('autocomplete', 'new-password');
        });

        // Đoạn code xử lý toggle fields
        const typeSelect = document.querySelector("select[name='type']");
        const usernameField = document.getElementById("username");
        const passwordField = document.getElementById("password");
        const apiKeyField = document.getElementById("api_key");
        const apiSecretField = document.getElementById("api_secret");
        const tokenField = document.getElementById("token");
        const couponField = document.getElementById("coupon");
        const sync_category = document.getElementById("sync_category");
        const child_sync = document.getElementById("child_sync");
        const auto_show = document.getElementById("auto_show");
        const proxyField = document.getElementById("proxy");
        const rateField = document.getElementById("rate_field");
        const apiKeyHint = document.getElementById("api-key-hint");

        // Thêm xử lý hiển thị/ẩn mật khẩu
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password-input');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Thêm xử lý hiển thị/ẩn API Secret
        const toggleApiSecret = document.getElementById('toggle-api-secret');
        if (toggleApiSecret) {
            toggleApiSecret.addEventListener('click', function() {
                const apiSecretInput = document.getElementById('api-secret-input');
                const icon = this.querySelector('i');

                if (apiSecretInput.type === 'password') {
                    apiSecretInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    apiSecretInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        }

        // Config động từ PHP
        const apiConfigs = <?= json_encode($allApiConfigs); ?>;

        function toggleFields() {
            const selectedType = typeSelect.value;

            // Ẩn tất cả các trường
            usernameField.style.display = "none";
            passwordField.style.display = "none";
            apiKeyField.style.display = "none";
            apiSecretField.style.display = "none";
            tokenField.style.display = "none";
            couponField.style.display = "none";
            sync_category.style.display = "none";
            child_sync.style.display = "none";
            auto_show.style.display = "none";
            proxyField.style.display = "none";
            rateField.style.display = "none";
            if (apiKeyHint) apiKeyHint.innerHTML = "";

            // Lấy config của API type được chọn
            const config = apiConfigs[selectedType];
            if (!config) return;

            const fieldsConfig = config.fields_config;
            const fields = fieldsConfig.fields || [];

            // Hiển thị các trường theo config
            if (fields.includes('username')) usernameField.style.display = "block";
            if (fields.includes('password')) passwordField.style.display = "block";
            if (fields.includes('api_key')) apiKeyField.style.display = "block";
            if (fields.includes('api_secret')) apiSecretField.style.display = "block";
            if (fields.includes('token')) tokenField.style.display = "block";
            if (fields.includes('coupon')) couponField.style.display = "block";

            // Hiển thị các options
            // Chỉ hiển thị sync_category và child_sync cho SHOPKEY
            if (fieldsConfig.show_sync_category && selectedType === "SHOPKEY") sync_category.style.display = "block";
            if (fieldsConfig.show_child_sync && selectedType === "SHOPKEY") child_sync.style.display = "block";
            if (fieldsConfig.show_auto_show) auto_show.style.display = "block";
            if (fieldsConfig.show_proxy) proxyField.style.display = "block";
            if (fieldsConfig.show_rate) rateField.style.display = "block";

            // Hiển thị hints
            if (apiKeyHint && fieldsConfig.api_key_hint) {
                apiKeyHint.innerHTML = '<i class="fas fa-info-circle"></i> ' + fieldsConfig.api_key_hint;
            }

            // Xử lý child_sync phụ thuộc sync_category (chỉ cho SHOPKEY)
            if (fieldsConfig.show_child_sync && selectedType === "SHOPKEY") {
                const syncCategorySelect = document.getElementById('sync-category-select');
                const childSelect = document.getElementById('child-select');
                if (syncCategorySelect && childSelect) {
                    if (syncCategorySelect.value === "OFF") {
                        childSelect.disabled = true;
                        childSelect.value = "0";
                    } else {
                        childSelect.disabled = false;
                    }
                }
            }
        }
        toggleFields();
        typeSelect.addEventListener("change", toggleFields);

        // Xử lý disabled/enabled child_sync khi sync_category thay đổi
        const syncCategorySelect = document.getElementById('sync-category-select');
        if (syncCategorySelect) {
            syncCategorySelect.addEventListener('change', function() {
                const selectedType = typeSelect.value;
                const childSelect = document.getElementById('child-select');
                const childWarning = document.getElementById('child-warning');

                // Chỉ áp dụng cho SHOPKEY
                if (selectedType === "SHOPKEY" && childSelect) {
                    if (this.value === "ON") {
                        childSelect.disabled = false;
                        childSelect.parentElement.classList.remove('opacity-50');
                    } else {
                        childSelect.disabled = true;
                        childSelect.value = "0";
                        childSelect.parentElement.classList.add('opacity-50');
                        if (childWarning) {
                            childWarning.style.display = "none";
                        }
                    }
                }
            });
        }

        // Xử lý hiển thị cảnh báo khi chọn child = ON
        const childSelect = document.getElementById('child-select');
        const childWarning = document.getElementById('child-warning');

        if (childSelect) {
            childSelect.addEventListener('change', function() {
                if (this.value == '1') {
                    childWarning.style.display = 'block';
                    childWarning.classList.add('animate__animated', 'animate__fadeIn');
                    setTimeout(() => {
                        childWarning.classList.remove('animate__animated', 'animate__fadeIn');
                    }, 1000);
                } else {
                    childWarning.style.display = 'none';
                }
            });
        }

        // Cải thiện UX với hiệu ứng làm nổi bật section
        const apiSelect = document.getElementById('api-select');
        apiSelect.addEventListener('change', function() {
            if (this.value) {
                document.querySelector('.credentials-container').classList.add('animate__animated', 'animate__fadeIn');
                setTimeout(() => {
                    document.querySelector('.credentials-container').classList.remove('animate__animated', 'animate__fadeIn');
                }, 1000);
            }
        });
    });
</script>