<!-- Developer By CMSNT.CO | FB.COM/CMSNT.CO | ZALO.ME/0947838128 | MMO Solution -->
<?php
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/libs/helper.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/libs/database/users.php');
$CMSNT = new DB();

if ($CMSNT->site('status') != 1 && isSecureCookie('admin_login') != true) {
    require_once(__DIR__ . '/views/common/maintenance.php');
    exit();
}

// Định nghĩa hằng số cho thư mục views
define('VIEWS_PATH', __DIR__ . '/views');

// Kiểm tra module và action hợp lệ
$module = !empty($_GET['module']) ? check_path($_GET['module']) : 'client';
$module = $module == $CMSNT->site('path_admin') ? 'admin' : 'client';
$home   = $module == 'client' ? $CMSNT->site('home_page') : 'home';
$action = !empty($_GET['action']) ? check_path($_GET['action']) : $home;

// Các Action được phép
$allowed_actions = [
    // Client
    'home',
    'aff',
    'affiliate-history',
    'affiliate-withdraw',
    'affiliates',
    'api-keys',
    'blog',
    'blogs',
    'cart',
    'change-password',
    'contact',
    'document-api',
    'faq',
    'favorites',
    'forgot-password',
    'login',
    'logout',
    'logs',
    'order',
    'orders',
    'policy',
    'privacy',
    'product',
    'product-order',
    'product-orders',
    'products',
    'profile',
    'recharge-bakong',
    'recharge-bank',
    'recharge-card',
    'recharge-crypto',
    'recharge-korapay',
    'recharge-manual',
    'recharge-openpix',
    'recharge-paypal',
    'recharge-tmweasyapi',
    'recharge-xipay',
    'register',
    'reset-password',
    'scheduled-orders',
    'services',
    'set-language',
    'support-tickets',
    'ticket-detail',
    'transactions',
    'verify_2fa',
    'verify_otp',
    'security',

    // Admin
    'addons',
    'affiliate-config',
    'affiliate-history',
    'affiliate-withdraw',
    'automation-edit',
    'automations',
    'block-ip',
    'blog-add',
    'blog-category',
    'blog-category-edit',
    'blog-edit',
    'blogs',
    'bot-telegram-logs',
    'categories',
    'category-add',
    'category-edit',
    'currency-edit',
    'currency-list',
    'email-campaign-add',
    'email-campaign-edit',
    'email-campaigns',
    'email-sending-view',
    'failed-attempts-logs',
    'home',
    'language-edit',
    'language-list',
    'login-user',
    'logs',
    'product-add',
    'product-edit',
    'product-plans',
    'product-plans-all',
    'product-stock',
    'product-stock-all',
    'product-orders',
    'product-order-edit',
    'product-reviews',
    'products',
    'recharge-bakong',
    'coupons',
    'coupon-add',
    'coupon-edit',
    'flash-sales',
    'flash-sale-add',
    'flash-sale-edit',
    'recharge-bank',
    'recharge-bank-config',
    'recharge-bank-edit',
    'recharge-bank-invoice',
    'recharge-card',
    'recharge-crypto',
    'recharge-korapay',
    'recharge-manual',
    'recharge-manual-edit',
    'recharge-openpix',
    'recharge-paypal',
    'recharge-tmweasyapi',
    'recharge-xipay',
    'role-edit',
    'roles',
    'services',
    'settings',
    'product-api-add',
    'product-api-edit',
    'product-api',
    'theme',
    'ticket-detail',
    'tickets',
    'transactions',
    'translate-list',
    'user-edit',
    'users',
    'messages',
    'api-keys',
    'api-logs',
    'api-key-detail',
    'email-queue',
    'telegram-queue',
    'media-library',
    'elfinder',

    // Common
    '404',
    'banned',
    'block-ip',
    'maintenance'
];
// Nếu action không nằm trong danh sách cho phép thì trả về 404
if (!in_array($action, $allowed_actions, true)) {
    require_once(VIEWS_PATH . '/common/404.php');
    exit();
}

if ($module == 'admin') {
    require_once __DIR__ . '/models/is_admin.php';
}

if (isset($_GET['utm_source'])) {
    $utm_source = check_string($_GET['utm_source']);
    setcookie('utm_source', $utm_source, time() + (86400 * 30), "/"); // Cookie sẽ tồn tại trong 30 ngày
}
// XỬ LÝ AFFILIATE LINK
if (isset($_GET['aff'])) {
    require_once(__DIR__ . '/libs/database/affiliate.php');
    $AffiliateHandler = new AffiliateHandler();

    $aff_code = check_string($_GET['aff']);
    $cookie_days = intval($CMSNT->site('affiliate_cookie_days')) ?: 30;
    $cookie_expiry = time() + (86400 * $cookie_days);

    // Kiểm tra nếu aff là số (legacy support cho link cũ) hoặc là ref_code
    if (is_numeric($aff_code)) {
        // Legacy: Hỗ trợ link cũ với ID
        $aff = validate_int($aff_code, 1) ?: 0;
        if ($aff > 0) {
            $user_ref = $AffiliateHandler->getUserById($aff);
            if ($user_ref) {
                // Lưu ref_code vào cookie
                setcookie('aff', $user_ref['ref_code'], $cookie_expiry, "/");

                // Ghi nhận click
                $AffiliateHandler->recordClick(
                    $user_ref['id'],
                    myip(),
                    getUserAgent(),
                    $_SERVER['HTTP_REFERER'] ?? null
                );
            }
        }
    } else {
        // Mới: Xử lý với ref_code
        $ref_code = validate_string($aff_code, 10);
        if ($ref_code !== false) {
            $user_ref = $AffiliateHandler->getUserByRefCode($ref_code);
            if ($user_ref) {
                // Lưu ref_code vào cookie
                setcookie('aff', $user_ref['ref_code'], $cookie_expiry, "/");

                // Ghi nhận click
                $AffiliateHandler->recordClick(
                    $user_ref['id'],
                    myip(),
                    getUserAgent(),
                    $_SERVER['HTTP_REFERER'] ?? null
                );
            }
        }
    }
}

// Xây dựng đường dẫn an toàn
$path = VIEWS_PATH . '/' . $module . '/' . $action . '.php';

// Kiểm tra file tồn tại và nằm trong thư mục views
if (file_exists($path) && strpos(realpath($path), realpath(VIEWS_PATH)) === 0) {
    require_once($path);
    exit();
} else {
    require_once(VIEWS_PATH . '/common/404.php');
    exit();
}
?>