<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Kiểm tra tính năng API cho user có được bật không
if ($CMSNT->site('api_user_enabled') != 1) {
    redirect(base_url());
}

$body = [
    'title' => __('Tài liệu API') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '<link rel="stylesheet" href="' . base_url('mod/css/api.css?v=1.0') . '">';
$body['footer'] = '<script src="' . base_url('mod/js/api.js?v=1.0') . '"></script>';

if ($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
} else {
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

// Lấy API key của user nếu đã đăng nhập
$userApiKey = '';
$userApiSecret = '';
if (isset($getUser) && $getUser) {
    $apiKeyData = $CMSNT->get_row_safe(
        "SELECT * FROM `api_keys` WHERE `user_id` = ? AND `status` = 1 ORDER BY `id` DESC LIMIT 1",
        [$getUser['id']]
    );
    if ($apiKeyData) {
        $userApiKey = $apiKeyData['api_key'];
        $userApiSecret = $apiKeyData['api_secret'];
    }
}

$apiBaseUrl = rtrim(base_url(), '/') . '/api/v1';

// Demo placeholders cho code examples (sk_live_ + 32 chars = 40 chars, sk_secret_ + 64 chars = 74 chars)
$demoApiKey = 'sk_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
$demoApiSecret = 'sk_secret_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2';
?>

<!-- Page Header -->
<div class="api-page-header">
    <div class="container">
        <div class="api-page-header-content">
            <h1 class="api-page-title">
                <i class="fa-solid fa-code"></i>
                <?= __('API Documentation'); ?>
            </h1>
            <p class="api-page-desc">
                <?= __('Tích hợp hệ thống mua hàng tự động vào ứng dụng của bạn. Hỗ trợ đầy đủ các tính năng: tạo đơn hàng, kiểm tra trạng thái, xem danh sách sản phẩm và nhiều hơn nữa.'); ?>
            </p>
            <div class="api-version-badge">
                <i class="fa-solid fa-rocket"></i>
                API Version 1.0 - RESTful
            </div>
        </div>
    </div>
</div>

<div class="container api-page-container">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="api-sidebar">
                <div class="api-sidebar-section">
                    <div class="api-sidebar-title"><?= __('Bắt đầu'); ?></div>
                    <a href="#overview" class="api-nav-link active">
                        <i class="fa-solid fa-house"></i>
                        <?= __('Tổng quan'); ?>
                    </a>
                    <a href="#authentication" class="api-nav-link">
                        <i class="fa-solid fa-key"></i>
                        <?= __('Xác thực'); ?>
                    </a>
                </div>

                <div class="api-sidebar-section">
                    <div class="api-sidebar-title"><?= __('Endpoints'); ?></div>
                    <a href="#create-order" class="api-nav-link">
                        <i class="fa-solid fa-cart-plus"></i>
                        <?= __('Tạo đơn hàng'); ?>
                        <span class="api-nav-method post">POST</span>
                    </a>
                    <a href="#order-status" class="api-nav-link">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <?= __('Trạng thái đơn'); ?>
                        <span class="api-nav-method get">GET</span>
                    </a>
                    <a href="#order-list" class="api-nav-link">
                        <i class="fa-solid fa-list"></i>
                        <?= __('Danh sách đơn'); ?>
                        <span class="api-nav-method get">GET</span>
                    </a>
                    <a href="#products" class="api-nav-link">
                        <i class="fa-solid fa-box"></i>
                        <?= __('Sản phẩm'); ?>
                        <span class="api-nav-method get">GET</span>
                    </a>
                    <a href="#categories" class="api-nav-link">
                        <i class="fa-solid fa-sitemap"></i>
                        <?= __('Danh mục'); ?>
                        <span class="api-nav-method get">GET</span>
                    </a>
                    <a href="#balance" class="api-nav-link">
                        <i class="fa-solid fa-wallet"></i>
                        <?= __('Số dư'); ?>
                        <span class="api-nav-method get">GET</span>
                    </a>
                    <a href="#account" class="api-nav-link">
                        <i class="fa-solid fa-user"></i>
                        <?= __('Thông tin tài khoản'); ?>
                        <span class="api-nav-method get">GET</span>
                    </a>
                </div>

                <div class="api-sidebar-section">
                    <div class="api-sidebar-title"><?= __('Tham khảo'); ?></div>
                    <a href="#errors" class="api-nav-link">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?= __('Mã lỗi'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="api-content">

                <!-- Overview Section -->
                <section class="api-section-card" id="overview">
                    <div class="api-section-header">
                        <div class="api-section-icon purple">
                            <i class="fa-solid fa-rocket"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Tổng quan API'); ?></h2>
                            <p><?= __('Thông tin cơ bản về Shop API'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-info-box">
                            <div class="api-info-row">
                                <span class="api-info-label"><i class="fa-solid fa-globe"></i> Base URL</span>
                                <span class="api-info-value">
                                    <code><?= $apiBaseUrl; ?></code>
                                    <button class="btn btn-sm btn-outline-primary" onclick="copyText('<?= $apiBaseUrl; ?>')">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="api-info-row">
                                <span class="api-info-label"><i class="fa-solid fa-key"></i> API Key</span>
                                <span class="api-info-value">
                                    <a href="<?= base_url('client/api-keys'); ?>" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-key me-1"></i><?= __('Quản lý API Keys'); ?>
                                    </a>
                                </span>
                            </div>
                            <div class="api-info-row">
                                <span class="api-info-label"><i class="fa-solid fa-file-code"></i> Content-Type</span>
                                <span class="api-info-value"><code>application/json</code></span>
                            </div>
                            <div class="api-info-row">
                                <span class="api-info-label"><i class="fa-solid fa-reply"></i> Response</span>
                                <span class="api-info-value"><code>JSON</code></span>
                            </div>
                        </div>

                        <div class="api-alert info">
                            <i class="fa-solid fa-circle-info api-alert-icon"></i>
                            <div>
                                <strong><?= __('Quản lý API Keys'); ?></strong>
                                <?= __('Truy cập'); ?> <a href="<?= base_url('client/api-keys'); ?>"><?= __('trang API Keys'); ?></a> <?= __('để tạo, quản lý và xem lịch sử sử dụng API của bạn.'); ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Authentication Section -->
                <section class="api-section-card" id="authentication">
                    <div class="api-section-header">
                        <div class="api-section-icon blue">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Xác thực API'); ?></h2>
                            <p><?= __('Cách xác thực các request đến API'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <p><?= __('Tất cả các request đến API cần có các headers sau:'); ?></p>

                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Header</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">X-API-Key</span></td>
                                    <td><?= __('API Key của bạn (40 ký tự, bắt đầu bằng sk_live_)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">X-API-Secret</span></td>
                                    <td><?= __('API Secret của bạn (74 ký tự, bắt đầu bằng sk_secret_)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">Content-Type</span></td>
                                    <td><code>application/json</code> <?= __('(cho POST request)'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Ví dụ Headers'); ?></h5>

                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-code"></i> HTTP Headers</span>
                                <button class="api-code-copy" onclick="copyCode('auth-header-example')">
                                    <i class="fa-solid fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="api-code-content">
                                <pre id="auth-header-example">X-API-Key: <?= $demoApiKey; ?>

X-API-Secret: <?= $demoApiSecret; ?>

Content-Type: application/json</pre>
                            </div>
                        </div>

                        <div class="api-alert warning">
                            <i class="fa-solid fa-triangle-exclamation api-alert-icon"></i>
                            <div>
                                <strong><?= __('Lưu ý bảo mật'); ?></strong>
                                <?= __('Giữ API Key và API Secret an toàn. Không chia sẻ hoặc commit vào source code. Sử dụng biến môi trường để lưu trữ.'); ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Create Order Endpoint -->
                <section class="api-section-card" id="create-order">
                    <div class="api-section-header">
                        <div class="api-section-icon green">
                            <i class="fa-solid fa-cart-plus"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Tạo đơn hàng'); ?></h2>
                            <p><?= __('Tạo đơn hàng mới và thanh toán tự động'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge post">POST</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/orders/create</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/orders/create')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-right"></i> <?= __('Request Body'); ?></h5>
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="param-name">items</span>
                                        <span class="param-required yes"><?= __('Bắt buộc'); ?></span>
                                    </td>
                                    <td><span class="param-type">array</span></td>
                                    <td><?= __('Danh sách sản phẩm cần mua'); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="param-name">items[].plan_id</span>
                                        <span class="param-required yes"><?= __('Bắt buộc'); ?></span>
                                    </td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('ID gói sản phẩm (lấy từ /products/list)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">items[].quantity</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Số lượng mua (mặc định: 1)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">items[].fields</span></td>
                                    <td><span class="param-type">object</span></td>
                                    <td><?= __('Các field bổ sung (nếu plan yêu cầu)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">coupon_code</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Mã giảm giá (nếu có)'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-tabs">
                            <button class="api-code-tab active" onclick="showCodeExample('php', 'create-order')"><i class="fa-brands fa-php"></i> PHP</button>
                            <button class="api-code-tab" onclick="showCodeExample('python', 'create-order')"><i class="fa-brands fa-python"></i> Python</button>
                            <button class="api-code-tab" onclick="showCodeExample('curl', 'create-order')"><i class="fa-solid fa-terminal"></i> cURL</button>
                        </div>

                        <div class="api-code-examples">
                            <div class="code-example" id="code-php" data-code="php">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-brands fa-php"></i> PHP</span>
                                        <button class="api-code-copy" onclick="copyCode('create-order-php')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="create-order-php">&lt;?php
$apiKey = '<?= $demoApiKey; ?>';
$apiSecret = '<?= $demoApiSecret; ?>';

$data = [
    'items' => [
        ['plan_id' => 18, 'quantity' => 1, 'fields' => ['email_dang_nhap' => 'user@example.com']]
    ],
    'coupon_code' => ''
];

$ch = curl_init('<?= $apiBaseUrl; ?>/orders/create');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
        'X-API-Secret: ' . $apiSecret
    ]
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);

print_r($response);
?&gt;</pre>
                                    </div>
                                </div>
                            </div>

                            <div class="code-example" id="code-python" data-code="python" style="display:none;">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-brands fa-python"></i> Python</span>
                                        <button class="api-code-copy" onclick="copyCode('create-order-python')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="create-order-python">import requests

api_key = '<?= $demoApiKey; ?>'
api_secret = '<?= $demoApiSecret; ?>'

data = {
    'items': [{'plan_id': 18, 'quantity': 1, 'fields': {'email_dang_nhap': 'user@example.com'}}],
    'coupon_code': ''
}

response = requests.post(
    '<?= $apiBaseUrl; ?>/orders/create',
    json=data,
    headers={
        'Content-Type': 'application/json',
        'X-API-Key': api_key,
        'X-API-Secret': api_secret
    }
)

print(response.json())</pre>
                                    </div>
                                </div>
                            </div>

                            <div class="code-example" id="code-curl" data-code="curl" style="display:none;">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                        <button class="api-code-copy" onclick="copyCode('create-order-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="create-order-curl">curl -X POST "<?= $apiBaseUrl; ?>/orders/create" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>" \
  -d '{"items":[{"plan_id":18,"quantity":1}]}'</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-left"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle text-success"></i> Success Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"Đơn hàng đã được tạo thành công"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"orders"</span>: [{
      <span class="json-key">"trans_id"</span>: <span class="json-string">"ORD1703750400ABC"</span>,
      <span class="json-key">"product_name"</span>: <span class="json-string">"Sản phẩm Premium"</span>,
      <span class="json-key">"status"</span>: <span class="json-string">"completed"</span>,
      <span class="json-key">"total"</span>: <span class="json-number">100000</span>
    }],
    <span class="json-key">"summary"</span>: {
      <span class="json-key">"total_amount"</span>: <span class="json-number">100000</span>,
      <span class="json-key">"new_balance"</span>: <span class="json-number">400000</span>
    }
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Order Status Endpoint -->
                <section class="api-section-card" id="order-status">
                    <div class="api-section-header">
                        <div class="api-section-icon blue">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Trạng thái đơn hàng'); ?></h2>
                            <p><?= __('Kiểm tra trạng thái và nội dung giao hàng'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge get">GET</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/orders/status?trans_id={trans_id}</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/orders/status')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-right"></i> <?= __('Query Parameters'); ?></h5>
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <span class="param-name">trans_id</span>
                                        <span class="param-required yes"><?= __('Bắt buộc'); ?></span>
                                    </td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Mã giao dịch từ kết quả tạo đơn'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-tabs">
                            <button class="api-code-tab active" onclick="showCodeExample('php', 'order-status')"><i class="fa-brands fa-php"></i> PHP</button>
                            <button class="api-code-tab" onclick="showCodeExample('curl', 'order-status')"><i class="fa-solid fa-terminal"></i> cURL</button>
                        </div>

                        <div class="api-code-examples">
                            <div class="code-example" id="code-php" data-code="php">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-brands fa-php"></i> PHP</span>
                                        <button class="api-code-copy" onclick="copyCode('order-status-php')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="order-status-php">&lt;?php
$transId = 'ORD1703750400ABC';
$url = '<?= $apiBaseUrl; ?>/orders/status?trans_id=' . $transId;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-API-Key: <?= $demoApiKey; ?>',
        'X-API-Secret: <?= $demoApiSecret; ?>'
    ]
]);

$response = json_decode(curl_exec($ch), true);
curl_close($ch);
print_r($response);
?&gt;</pre>
                                    </div>
                                </div>
                            </div>

                            <div class="code-example" id="code-curl" data-code="curl" style="display:none;">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                        <button class="api-code-copy" onclick="copyCode('order-status-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="order-status-curl">curl "<?= $apiBaseUrl; ?>/orders/status?trans_id=ORD1703750400ABC" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-left"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle text-success"></i> Success Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"timestamp"</span>: <span class="json-number">1770020629</span>,
  <span class="json-key">"request_id"</span>: <span class="json-string">"req_69805f15a5ed77.94324321"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"order"</span>: {
      <span class="json-key">"id"</span>: <span class="json-number">36</span>,
      <span class="json-key">"trans_id"</span>: <span class="json-string">"PO8153690"</span>,
      <span class="json-key">"user_id"</span>: <span class="json-number">1</span>,
      <span class="json-key">"product"</span>: {
        <span class="json-key">"id"</span>: <span class="json-number">21</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Sản phẩm Figma"</span>,
        <span class="json-key">"slug"</span>: <span class="json-string">"san-pham-figma"</span>,
        <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/product.jpg"</span>
      },
      <span class="json-key">"plan"</span>: {
        <span class="json-key">"id"</span>: <span class="json-number">19</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Figma Edu 1 năm - Tài khoản"</span>,
        <span class="json-key">"is_instant"</span>: <span class="json-boolean">true</span>
      },
      <span class="json-key">"quantity"</span>: <span class="json-number">2</span>,
      <span class="json-key">"total_price"</span>: <span class="json-number">1600000</span>,
      <span class="json-key">"sale_price"</span>: <span class="json-number">400000</span>,
      <span class="json-key">"discount_amount"</span>: <span class="json-number">0</span>,
      <span class="json-key">"coupon_code"</span>: <span class="json-null">null</span>,
      <span class="json-key">"final_amount"</span>: <span class="json-number">400000</span>,
      <span class="json-key">"fields_data"</span>: [],
      <span class="json-key">"status"</span>: <span class="json-string">"completed"</span>,
      <span class="json-key">"payment_status"</span>: <span class="json-string">"paid"</span>,
      <span class="json-key">"order_source"</span>: <span class="json-string">"api"</span>,
      <span class="json-key">"created_at"</span>: <span class="json-string">"2026-02-02 15:23:14"</span>,
      <span class="json-key">"updated_at"</span>: <span class="json-string">"2026-02-02 15:23:14"</span>
    },
    <span class="json-key">"delivery"</span>: {
      <span class="json-key">"items"</span>: [<span class="json-string">"Account: user@mail.com | Pass: abc123"</span>, <span class="json-string">"Account: user2@mail.com | Pass: xyz789"</span>],
      <span class="json-key">"delivered_count"</span>: <span class="json-number">2</span>,
      <span class="json-key">"expected_count"</span>: <span class="json-number">2</span>
    },
    <span class="json-key">"execution_time"</span>: <span class="json-number">0.0238</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Order List Endpoint -->
                <section class="api-section-card" id="order-list">
                    <div class="api-section-header">
                        <div class="api-section-icon purple">
                            <i class="fa-solid fa-list"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Danh sách đơn hàng'); ?></h2>
                            <p><?= __('Lấy danh sách đơn hàng với phân trang'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge get">GET</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/orders/list</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/orders/list')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-right"></i> <?= __('Query Parameters'); ?></h5>
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">page</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Số trang (mặc định: 1)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">limit</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Số lượng/trang (mặc định: 20, max: 100)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">status</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Lọc: pending, processing, completed, cancelled'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                <button class="api-code-copy" onclick="copyCode('order-list-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                            <div class="api-code-content">
                                <pre id="order-list-curl">curl "<?= $apiBaseUrl; ?>/orders/list?page=1&limit=20&status=completed" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-reply"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle"></i> Success Response</span>
                                <button class="api-code-copy" onclick="copyCode('order-list-response')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                            <div class="api-code-content">
                                <pre id="order-list-response">{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"orders"</span>: [{
      <span class="json-key">"id"</span>: <span class="json-number">1234</span>,
      <span class="json-key">"trans_id"</span>: <span class="json-string">"ORD-1705678901-ABC123"</span>,
      <span class="json-key">"product"</span>: {
        <span class="json-key">"id"</span>: <span class="json-number">10</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Windows 11 Pro Key"</span>,
        <span class="json-key">"slug"</span>: <span class="json-string">"windows-11-pro-key"</span>
      },
      <span class="json-key">"plan"</span>: {
        <span class="json-key">"id"</span>: <span class="json-number">5</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Gói Retail - Vĩnh viễn"</span>
      },
      <span class="json-key">"quantity"</span>: <span class="json-number">2</span>,
      <span class="json-key">"total_price"</span>: <span class="json-number">1500000</span>,
      <span class="json-key">"final_amount"</span>: <span class="json-number">1350000</span>,
      <span class="json-key">"status"</span>: <span class="json-string">"completed"</span>,
      <span class="json-key">"payment_status"</span>: <span class="json-string">"paid"</span>,
      <span class="json-key">"created_at"</span>: <span class="json-string">"2026-01-19 10:30:00"</span>
    }],
    <span class="json-key">"pagination"</span>: {
      <span class="json-key">"current_page"</span>: <span class="json-number">1</span>,
      <span class="json-key">"per_page"</span>: <span class="json-number">20</span>,
      <span class="json-key">"total"</span>: <span class="json-number">45</span>,
      <span class="json-key">"total_pages"</span>: <span class="json-number">3</span>,
      <span class="json-key">"has_more"</span>: <span class="json-boolean">true</span>
    },
    <span class="json-key">"execution_time"</span>: <span class="json-number">0.0234</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Products Endpoint -->
                <section class="api-section-card" id="products">
                    <div class="api-section-header">
                        <div class="api-section-icon orange">
                            <i class="fa-solid fa-box"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Danh sách sản phẩm'); ?></h2>
                            <p><?= __('Lấy danh sách sản phẩm và các gói'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge get">GET</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/products/list</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/products/list')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-right"></i> <?= __('Query Parameters'); ?></h5>
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">page</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Số trang (mặc định: 1)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">limit</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Số lượng/trang (mặc định: 10, tối đa: 100)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">category_id</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Lọc theo danh mục'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">search</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Tìm kiếm theo tên'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">sort</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Sắp xếp: <code>newest</code>, <code>oldest</code>, <code>price_asc</code>, <code>price_desc</code>, <code>bestseller</code>'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="api-alert info">
                            <i class="fa-solid fa-circle-info api-alert-icon"></i>
                            <div>
                                <strong><?= __('Phân trang'); ?></strong>
                                <?= __('API trả về tối đa 100 sản phẩm/trang. Sử dụng tham số <code>page</code> và kiểm tra <code>pagination.has_more</code> trong response để lấy tất cả sản phẩm.'); ?>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-tabs">
                            <button class="api-code-tab active" onclick="showCodeExample('php', 'products')"><i class="fa-brands fa-php"></i> PHP</button>
                            <button class="api-code-tab" onclick="showCodeExample('curl', 'products')"><i class="fa-solid fa-terminal"></i> cURL</button>
                        </div>

                        <div class="api-code-examples">
                            <div class="code-example" id="code-php" data-code="php">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-brands fa-php"></i> PHP - <?= __('Lấy tất cả sản phẩm (phân trang)'); ?></span>
                                        <button class="api-code-copy" onclick="copyCode('products-php')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="products-php">&lt;?php
$apiKey = '<?= $demoApiKey; ?>';
$apiSecret = '<?= $demoApiSecret; ?>';
$baseUrl = '<?= $apiBaseUrl; ?>';

$allProducts = [];
$page = 1;
$limit = 100; // Tối đa 100 sản phẩm/trang

do {
    $url = $baseUrl . '/products/list?page=' . $page . '&amp;limit=' . $limit;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER =&gt; true,
        CURLOPT_HTTPHEADER =&gt; [
            'X-API-Key: ' . $apiKey,
            'X-API-Secret: ' . $apiSecret
        ]
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!$response['success']) break;

    $products = $response['data']['products'] ?? [];
    $allProducts = array_merge($allProducts, $products);

    $hasMore = $response['data']['pagination']['has_more'] ?? false;
    $page++;
} while ($hasMore);

echo 'Tổng sản phẩm: ' . count($allProducts);
?&gt;</pre>
                                    </div>
                                </div>
                            </div>

                            <div class="code-example" id="code-curl" data-code="curl" style="display:none;">
                                <div class="api-code-block">
                                    <div class="api-code-header">
                                        <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                        <button class="api-code-copy" onclick="copyCode('products-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                                    </div>
                                    <div class="api-code-content">
                                        <pre id="products-curl"># Lấy trang 1 (10 sản phẩm mặc định)
curl "<?= $apiBaseUrl; ?>/products/list" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"

# Lấy trang 2, mỗi trang 50 sản phẩm
curl "<?= $apiBaseUrl; ?>/products/list?page=2&amp;limit=50" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"

# Lọc theo danh mục + sắp xếp theo giá tăng dần
curl "<?= $apiBaseUrl; ?>/products/list?category_id=5&amp;sort=price_asc&amp;limit=100" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-reply"></i> <?= __('Response Fields (Plan)'); ?></h5>
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">id</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('ID của gói sản phẩm'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">name</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Tên gói sản phẩm'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">price</span></td>
                                    <td><span class="param-type">float</span></td>
                                    <td><?= __('Giá gốc'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">sale_price</span></td>
                                    <td><span class="param-type">float</span></td>
                                    <td><?= __('Giá khuyến mãi (nếu có)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">final_price</span></td>
                                    <td><span class="param-type">float</span></td>
                                    <td><?= __('Giá cuối cùng (ưu tiên sale_price nếu có)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">is_instant</span></td>
                                    <td><span class="param-type">boolean</span></td>
                                    <td><?= __('Giao hàng tự động'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">duration_type</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><?= __('Loại thời hạn: <code>lifetime</code> (vĩnh viễn), <code>days</code> (ngày), <code>months</code> (tháng), <code>years</code> (năm)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">duration_value</span></td>
                                    <td><span class="param-type">integer|null</span></td>
                                    <td><?= __('Giá trị thời hạn (VD: 30 ngày, 1 tháng). Null nếu duration_type = lifetime'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">stock_count</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Số lượng tồn kho (nếu is_instant = true)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">in_stock</span></td>
                                    <td><span class="param-type">boolean</span></td>
                                    <td><?= __('Còn hàng hay không'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">fields</span></td>
                                    <td><span class="param-type">array</span></td>
                                    <td><?= __('Danh sách field cần nhập khi đặt hàng'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-left"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle text-success"></i> Success Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"timestamp"</span>: <span class="json-number">1769604661</span>,
  <span class="json-key">"request_id"</span>: <span class="json-string">"req_697a063503ed57.32834148"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"products"</span>: [{
      <span class="json-key">"id"</span>: <span class="json-number">29</span>,
      <span class="json-key">"name"</span>: <span class="json-string">"Key Adobe Creative Cloud All Apps"</span>,
      <span class="json-key">"slug"</span>: <span class="json-string">"key-adobe-creative-cloud-all-apps"</span>,
      <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/product.jpg"</span>,
      <span class="json-key">"description"</span>: <span class="json-string">"&lt;p&gt;Mô tả sản phẩm...&lt;/p&gt;"</span>,
      <span class="json-key">"category"</span>: {
        <span class="json-key">"id"</span>: <span class="json-number">5</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Học Tập"</span>,
        <span class="json-key">"slug"</span>: <span class="json-string">"hoc-tap"</span>,
        <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/category.jpg"</span>
      },
      <span class="json-key">"categories"</span>: [
        {<span class="json-key">"id"</span>: <span class="json-number">5</span>, <span class="json-key">"name"</span>: <span class="json-string">"Học Tập"</span>, <span class="json-key">"slug"</span>: <span class="json-string">"hoc-tap"</span>, <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/category.jpg"</span>},
        {<span class="json-key">"id"</span>: <span class="json-number">14</span>, <span class="json-key">"name"</span>: <span class="json-string">"Làm việc"</span>, <span class="json-key">"slug"</span>: <span class="json-string">"lam-viec"</span>, <span class="json-key">"image"</span>: <span class="json-null">null</span>}
      ],
      <span class="json-key">"min_price"</span>: <span class="json-number">375000</span>,
      <span class="json-key">"max_price"</span>: <span class="json-number">1650000</span>,
      <span class="json-key">"sold"</span>: <span class="json-number">24</span>,
      <span class="json-key">"rating"</span>: <span class="json-number">4.5</span>,
      <span class="json-key">"plans"</span>: [{
        <span class="json-key">"id"</span>: <span class="json-number">319</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Tài khoản 1 tháng"</span>,
        <span class="json-key">"price"</span>: <span class="json-number">375000</span>,
        <span class="json-key">"sale_price"</span>: <span class="json-number">0</span>,
        <span class="json-key">"final_price"</span>: <span class="json-number">375000</span>,
        <span class="json-key">"is_instant"</span>: <span class="json-boolean">true</span>,
        <span class="json-key">"duration_type"</span>: <span class="json-string">"month"</span>,
        <span class="json-key">"duration_value"</span>: <span class="json-number">1</span>,
        <span class="json-key">"stock_count"</span>: <span class="json-number">50</span>,
        <span class="json-key">"in_stock"</span>: <span class="json-boolean">true</span>,
        <span class="json-key">"description"</span>: <span class="json-string">""</span>,
        <span class="json-key">"fields"</span>: []
      }, {
        <span class="json-key">"id"</span>: <span class="json-number">320</span>,
        <span class="json-key">"name"</span>: <span class="json-string">"Nâng chính chủ 1 Tháng"</span>,
        <span class="json-key">"price"</span>: <span class="json-number">1650000</span>,
        <span class="json-key">"sale_price"</span>: <span class="json-number">0</span>,
        <span class="json-key">"final_price"</span>: <span class="json-number">1650000</span>,
        <span class="json-key">"is_instant"</span>: <span class="json-boolean">false</span>,
        <span class="json-key">"duration_type"</span>: <span class="json-string">"month"</span>,
        <span class="json-key">"duration_value"</span>: <span class="json-number">3</span>,
        <span class="json-key">"stock_count"</span>: <span class="json-number">0</span>,
        <span class="json-key">"in_stock"</span>: <span class="json-boolean">true</span>,
        <span class="json-key">"description"</span>: <span class="json-string">""</span>,
        <span class="json-key">"fields"</span>: [
          {<span class="json-key">"key"</span>: <span class="json-string">"email"</span>, <span class="json-key">"label"</span>: <span class="json-string">"Email"</span>, <span class="json-key">"type"</span>: <span class="json-string">"email"</span>, <span class="json-key">"required"</span>: <span class="json-boolean">true</span>},
          {<span class="json-key">"key"</span>: <span class="json-string">"mat_khau"</span>, <span class="json-key">"label"</span>: <span class="json-string">"Mật khẩu"</span>, <span class="json-key">"type"</span>: <span class="json-string">"password"</span>, <span class="json-key">"required"</span>: <span class="json-boolean">true</span>}
        ]
      }]
    }],
    <span class="json-key">"pagination"</span>: {
      <span class="json-key">"current_page"</span>: <span class="json-number">1</span>,
      <span class="json-key">"per_page"</span>: <span class="json-number">10</span>,
      <span class="json-key">"total"</span>: <span class="json-number">21</span>,
      <span class="json-key">"total_pages"</span>: <span class="json-number">3</span>,
      <span class="json-key">"has_more"</span>: <span class="json-boolean">true</span>
    },
    <span class="json-key">"execution_time"</span>: <span class="json-number">0.0828</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Categories Endpoint -->
                <section class="api-section-card" id="categories">
                    <div class="api-section-header">
                        <div class="api-section-icon teal">
                            <i class="fa-solid fa-sitemap"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Danh sách danh mục'); ?></h2>
                            <p><?= __('Lấy danh sách danh mục sản phẩm'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge get">GET</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/categories/list</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/categories/list')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-right"></i> <?= __('Query Parameters'); ?></h5>
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">parent_id</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td><?= __('Lọc theo danh mục cha'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">include_children</span></td>
                                    <td><span class="param-type">boolean</span></td>
                                    <td><?= __('Bao gồm danh mục con (0 hoặc 1)'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">include_products</span></td>
                                    <td><span class="param-type">boolean</span></td>
                                    <td><?= __('Bao gồm số lượng sản phẩm (0 hoặc 1)'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                <button class="api-code-copy" onclick="copyCode('categories-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                            <div class="api-code-content">
                                <pre id="categories-curl">curl "<?= $apiBaseUrl; ?>/categories/list?include_children=1" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-left"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle text-success"></i> Success Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"timestamp"</span>: <span class="json-number">1770022554</span>,
  <span class="json-key">"request_id"</span>: <span class="json-string">"req_6980669a4793d9.70486853"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"categories"</span>: [{
      <span class="json-key">"id"</span>: <span class="json-number">3</span>,
      <span class="json-key">"name"</span>: <span class="json-string">"Giải Trí"</span>,
      <span class="json-key">"slug"</span>: <span class="json-string">"giai-tri"</span>,
      <span class="json-key">"description"</span>: <span class="json-string">""</span>,
      <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/category/DOQ4.png"</span>,
      <span class="json-key">"parent_id"</span>: <span class="json-number">1</span>,
      <span class="json-key">"sort_order"</span>: <span class="json-number">9</span>
    }, {
      <span class="json-key">"id"</span>: <span class="json-number">5</span>,
      <span class="json-key">"name"</span>: <span class="json-string">"Học Tập"</span>,
      <span class="json-key">"slug"</span>: <span class="json-string">"hoc-tap"</span>,
      <span class="json-key">"description"</span>: <span class="json-string">""</span>,
      <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/category/9IRK.png"</span>,
      <span class="json-key">"parent_id"</span>: <span class="json-number">1</span>,
      <span class="json-key">"sort_order"</span>: <span class="json-number">4</span>
    }, {
      <span class="json-key">"id"</span>: <span class="json-number">1</span>,
      <span class="json-key">"name"</span>: <span class="json-string">"Tiện Ích"</span>,
      <span class="json-key">"slug"</span>: <span class="json-string">"tien-ich"</span>,
      <span class="json-key">"description"</span>: <span class="json-string">""</span>,
      <span class="json-key">"image"</span>: <span class="json-string">"https://example.com/category/EIT3.png"</span>,
      <span class="json-key">"parent_id"</span>: <span class="json-number">0</span>,
      <span class="json-key">"sort_order"</span>: <span class="json-number">0</span>
    }],
    <span class="json-key">"total"</span>: <span class="json-number">15</span>,
    <span class="json-key">"execution_time"</span>: <span class="json-number">0.0341</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Balance Endpoint -->
                <section class="api-section-card" id="balance">
                    <div class="api-section-header">
                        <div class="api-section-icon green">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Kiểm tra số dư'); ?></h2>
                            <p><?= __('Xem số dư tài khoản hiện tại'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge get">GET</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/account/balance</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/account/balance')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                <button class="api-code-copy" onclick="copyCode('balance-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                            <div class="api-code-content">
                                <pre id="balance-curl">curl "<?= $apiBaseUrl; ?>/account/balance" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-left"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle text-success"></i> Success Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"timestamp"</span>: <span class="json-number">1770022345</span>,
  <span class="json-key">"request_id"</span>: <span class="json-string">"req_698065c9694a56.14017855"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"user"</span>: {
      <span class="json-key">"id"</span>: <span class="json-number">1</span>,
      <span class="json-key">"username"</span>: <span class="json-string">"admin"</span>,
      <span class="json-key">"email"</span>: <span class="json-string">"admin@cmsnt.co"</span>
    },
    <span class="json-key">"balance"</span>: {
      <span class="json-key">"current"</span>: <span class="json-number">93537500</span>,
      <span class="json-key">"total_deposited"</span>: <span class="json-number">100000000</span>,
      <span class="json-key">"currency"</span>: <span class="json-string">"VND"</span>,
      <span class="json-key">"formatted"</span>: <span class="json-string">"93.537.500đ"</span>
    },
    <span class="json-key">"execution_time"</span>: <span class="json-number">0.0258</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Account Info Endpoint -->
                <section class="api-section-card" id="account">
                    <div class="api-section-header">
                        <div class="api-section-icon blue">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Thông tin tài khoản'); ?></h2>
                            <p><?= __('Lấy thông tin tài khoản người dùng'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <div class="api-endpoint-box">
                            <span class="api-method-badge get">GET</span>
                            <span class="api-endpoint-url"><?= base_url('api/v1'); ?>/account/info</span>
                            <button class="api-endpoint-copy" onclick="copyText('<?= $apiBaseUrl; ?>/account/info')">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-terminal"></i> cURL</span>
                                <button class="api-code-copy" onclick="copyCode('account-curl')"><i class="fa-solid fa-copy"></i> Copy</button>
                            </div>
                            <div class="api-code-content">
                                <pre id="account-curl">curl "<?= $apiBaseUrl; ?>/account/info" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                            </div>
                        </div>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-arrow-left"></i> <?= __('Response mẫu'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-check-circle text-success"></i> Success Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"timestamp"</span>: <span class="json-number">1770022432</span>,
  <span class="json-key">"request_id"</span>: <span class="json-string">"req_69806620805ae3.29218122"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"user"</span>: {
      <span class="json-key">"id"</span>: <span class="json-number">1</span>,
      <span class="json-key">"username"</span>: <span class="json-string">"admin"</span>,
      <span class="json-key">"email"</span>: <span class="json-string">"admin@cmsnt.co"</span>,
      <span class="json-key">"phone"</span>: <span class="json-string">""</span>,
      <span class="json-key">"balance"</span>: <span class="json-number">93537500</span>,
      <span class="json-key">"total_deposited"</span>: <span class="json-number">100000000</span>,
      <span class="json-key">"created_at"</span>: <span class="json-null">null</span>
    },
    <span class="json-key">"api_key"</span>: {
      <span class="json-key">"name"</span>: <span class="json-string">"API Key"</span>,
      <span class="json-key">"permissions"</span>: [
        <span class="json-string">"orders.create"</span>,
        <span class="json-string">"orders.list"</span>,
        <span class="json-string">"orders.status"</span>,
        <span class="json-string">"products.list"</span>,
        <span class="json-string">"account.balance"</span>,
        <span class="json-string">"account.info"</span>
      ],
      <span class="json-key">"rate_limit"</span>: <span class="json-number">60</span>,
      <span class="json-key">"daily_limit"</span>: <span class="json-number">10000</span>,
      <span class="json-key">"expires_at"</span>: <span class="json-null">null</span>,
      <span class="json-key">"created_at"</span>: <span class="json-string">"2026-02-02 14:53:35"</span>
    },
    <span class="json-key">"orders_summary"</span>: {
      <span class="json-key">"total"</span>: <span class="json-number">12</span>,
      <span class="json-key">"pending"</span>: <span class="json-number">3</span>,
      <span class="json-key">"completed"</span>: <span class="json-number">8</span>
    },
    <span class="json-key">"api_usage_today"</span>: {
      <span class="json-key">"period"</span>: <span class="json-string">"day"</span>,
      <span class="json-key">"start_time"</span>: <span class="json-string">"2026-02-02 00:00:00"</span>,
      <span class="json-key">"total_requests"</span>: <span class="json-number">5</span>,
      <span class="json-key">"success_requests"</span>: <span class="json-number">5</span>,
      <span class="json-key">"failed_requests"</span>: <span class="json-number">0</span>,
      <span class="json-key">"success_rate"</span>: <span class="json-number">100</span>
    },
    <span class="json-key">"execution_time"</span>: <span class="json-number">0.0449</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Error Codes Section -->
                <section class="api-section-card" id="errors">
                    <div class="api-section-header">
                        <div class="api-section-icon red">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="api-section-info">
                            <h2><?= __('Mã lỗi'); ?></h2>
                            <p><?= __('Danh sách các mã lỗi có thể gặp'); ?></p>
                        </div>
                    </div>
                    <div class="api-section-body">
                        <table class="api-params-table">
                            <thead>
                                <tr>
                                    <th><?= __('Mã lỗi'); ?></th>
                                    <th>HTTP</th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="api-error-code">INVALID_API_KEY</span></td>
                                    <td>401</td>
                                    <td><?= __('API Key không hợp lệ'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">INVALID_API_SECRET</span></td>
                                    <td>401</td>
                                    <td><?= __('API Secret không chính xác'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">DISABLED_API_KEY</span></td>
                                    <td>401</td>
                                    <td><?= __('API Key đã bị vô hiệu hóa'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">RATE_LIMIT_EXCEEDED</span></td>
                                    <td>429</td>
                                    <td><?= __('Vượt quá giới hạn request'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">IP_NOT_ALLOWED</span></td>
                                    <td>403</td>
                                    <td><?= __('IP không được phép truy cập'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">PERMISSION_DENIED</span></td>
                                    <td>403</td>
                                    <td><?= __('Không có quyền thực hiện'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">INSUFFICIENT_BALANCE</span></td>
                                    <td>400</td>
                                    <td><?= __('Số dư không đủ'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">PRODUCT_NOT_FOUND</span></td>
                                    <td>404</td>
                                    <td><?= __('Sản phẩm không tồn tại'); ?></td>
                                </tr>
                                <tr>
                                    <td><span class="api-error-code">OUT_OF_STOCK</span></td>
                                    <td>400</td>
                                    <td><?= __('Sản phẩm đã hết hàng'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h5 class="api-subsection-title"><i class="fa-solid fa-code"></i> <?= __('Error Response Format'); ?></h5>
                        <div class="api-code-block">
                            <div class="api-code-header">
                                <span class="api-code-lang"><i class="fa-solid fa-times-circle text-danger"></i> Error Response</span>
                            </div>
                            <div class="api-code-content">
                                <pre>{
  <span class="json-key">"success"</span>: <span class="json-boolean">false</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"Số dư không đủ để thanh toán"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"error_code"</span>: <span class="json-string">"INSUFFICIENT_BALANCE"</span>
  }
}</pre>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/footer.php'); ?>