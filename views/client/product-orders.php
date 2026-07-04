<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once(__DIR__ . '/../../models/is_user.php');

$body = [
    'title' => __('Đơn hàng của tôi') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
 
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/product-orders.css?v=2">
';
$body['footer'] = '
<script>var BASE_URL = "' . base_url() . '";</script>
<script src="' . BASE_URL('mod/') . 'js/product-orders.js?v=2"></script>
';

// Lọc theo status (dùng cho URL)
$status_filter = isset($_GET['status']) ? validate_string($_GET['status'], 30) : '';

// Tìm kiếm (dùng cho URL)
$search_query = isset($_GET['q']) ? trim(validate_string($_GET['q'], 100)) : '';

// Đếm số đơn hàng theo status (cho filter tabs)
$order_counts = $CMSNT->get_list_safe("
    SELECT `status`, COUNT(*) as count 
    FROM `product_orders` 
    WHERE `user_id` = ? 
    GROUP BY `status`
", [$getUser['id']]);

$counts = [
    'all' => 0,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($order_counts as $oc) {
    $counts[$oc['status']] = $oc['count'];
    $counts['all'] += $oc['count'];
}

// Hàm hiển thị status badge
function display_order_status_client($status)
{
    $statuses = [
        'pending' => ['label' => __('Chờ xử lý'), 'class' => 'status-pending', 'icon' => 'fa-clock'],
        'processing' => ['label' => __('Đang xử lý'), 'class' => 'status-processing', 'icon' => 'fa-spinner'],
        'completed' => ['label' => __('Hoàn thành'), 'class' => 'status-completed', 'icon' => 'fa-check-circle'],
        'cancelled' => ['label' => __('Đã hủy'), 'class' => 'status-cancelled', 'icon' => 'fa-times-circle'],
        'cancelled_no_refund' => ['label' => __('Đã hủy'), 'class' => 'status-cancelled', 'icon' => 'fa-times-circle']
    ];

    $s = $statuses[$status] ?? ['label' => $status, 'class' => 'status-pending', 'icon' => 'fa-question-circle'];
    return '<span class="order-status ' . $s['class'] . '"><i class="fa-solid ' . $s['icon'] . '"></i> ' . $s['label'] . '</span>';
}

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

// Hiển thị cảnh báo bảo vệ đơn hàng nếu có
$order_security_warning = '';
if (isset($_SESSION['order_security_warning'])) {
    $order_security_warning = $_SESSION['order_security_warning'];
    unset($_SESSION['order_security_warning']);
}
?>

<?php if (!empty($order_security_warning)): ?>
    <div class="container mt-3">
        <div class="order-security-alert" id="orderSecurityAlert">
            <div class="order-security-alert-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="order-security-alert-content">
                <div class="order-security-alert-title"><?= __('Bảo vệ đơn hàng'); ?></div>
                <div class="order-security-alert-message"><?= htmlspecialchars($order_security_warning); ?></div>
            </div>
            <button type="button" class="order-security-alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<section class="py-5 inner-section profile-part product-orders-page">
    <div class="container">
        <div class="row content-reverse">
            <!-- Sidebar -->
            <div class="col-lg-3 mt-3 mt-lg-0">
                <?php require_once(__DIR__ . '/sidebar.php'); ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 orders-main">
                <!-- Header -->
                <div class="orders-header">
                    <div class="orders-header-info">
                        <div class="orders-header-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h1 class="orders-title"><?= __('Đơn hàng của tôi'); ?></h1>
                            <p class="orders-subtitle"><?= sprintf(__('Tổng cộng %d đơn hàng'), $counts['all']); ?></p>
                        </div>
                    </div>

                    <!-- Search Form -->
                    <form class="orders-search-form" method="GET" action="<?= base_url('product-orders'); ?>">
                        <?php if (!empty($status_filter)): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <div class="search-input-wrapper">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" name="q" placeholder="<?= __('Tìm mã đơn hàng, tên sản phẩm...'); ?>" value="<?= htmlspecialchars($search_query); ?>">
                            <a href="javascript:void(0)" class="search-clear" title="<?= __('Xóa'); ?>" style="<?= empty($search_query) ? 'display:none' : ''; ?>">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        </div>
                        <button type="submit" class="btn-search"><?= __('Tìm kiếm'); ?></button>
                    </form>
                </div>

                <!-- Filter Tabs -->
                <div class="orders-filter-tabs">
                    <button type="button" data-url="<?= base_url('product-orders' . (!empty($search_query) ? '?q=' . urlencode($search_query) : '')); ?>" class="filter-tab <?= empty($status_filter) ? 'active' : ''; ?>">
                        <span class="tab-label"><?= __('Tất cả'); ?></span>
                        <span class="tab-count"><?= $counts['all']; ?></span>
                    </button>
                    <button type="button" data-url="<?= base_url('product-orders?status=pending' . (!empty($search_query) ? '&q=' . urlencode($search_query) : '')); ?>" class="filter-tab <?= $status_filter == 'pending' ? 'active' : ''; ?>">
                        <span class="tab-label"><?= __('Chờ xử lý'); ?></span>
                        <span class="tab-count"><?= $counts['pending']; ?></span>
                    </button>
                    <button type="button" data-url="<?= base_url('product-orders?status=processing' . (!empty($search_query) ? '&q=' . urlencode($search_query) : '')); ?>" class="filter-tab <?= $status_filter == 'processing' ? 'active' : ''; ?>">
                        <span class="tab-label"><?= __('Đang xử lý'); ?></span>
                        <span class="tab-count"><?= $counts['processing']; ?></span>
                    </button>
                    <button type="button" data-url="<?= base_url('product-orders?status=completed' . (!empty($search_query) ? '&q=' . urlencode($search_query) : '')); ?>" class="filter-tab <?= $status_filter == 'completed' ? 'active' : ''; ?>">
                        <span class="tab-label"><?= __('Hoàn thành'); ?></span>
                        <span class="tab-count"><?= $counts['completed']; ?></span>
                    </button>
                    <button type="button" data-url="<?= base_url('product-orders?status=cancelled' . (!empty($search_query) ? '&q=' . urlencode($search_query) : '')); ?>" class="filter-tab <?= $status_filter == 'cancelled' ? 'active' : ''; ?>">
                        <span class="tab-label"><?= __('Đã hủy'); ?></span>
                        <span class="tab-count"><?= $counts['cancelled']; ?></span>
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="orders-table-wrapper">
                    <!-- Empty State -->
                    <div class="orders-empty" id="ordersEmptyState" style="display: none;">
                        <div class="empty-icon">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                        <h3><?= __('Chưa có đơn hàng nào'); ?></h3>
                        <p><?= __('Bạn chưa có đơn hàng nào. Hãy khám phá sản phẩm của chúng tôi!'); ?></p>
                        <a href="<?= base_url('products'); ?>" class="btn-shop-now">
                            <i class="fa-solid fa-shopping-bag"></i>
                            <?= __('Mua sắm ngay'); ?>
                        </a>
                    </div>

                    <!-- Desktop Table View -->
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th class="text-center"><?= __('Chi tiết'); ?></th>
                                <th><?= __('Sản phẩm'); ?></th>
                                <th class="text-right"><?= __('Giá'); ?></th>
                                <th class="text-center"><?= __('Trạng thái'); ?></th>
                                <th><?= __('Mã đơn hàng'); ?></th>
                                <th><?= __('Thời gian'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <!-- Content loaded via AJAX -->
                        </tbody>
                        <tbody class="orders-loading-skeleton" id="ordersLoadingDesktop">
                            <?php for ($i = 0; $i < 10; $i++): ?>
                                <tr class="skeleton-row">
                                    <td class="text-center">
                                        <div class="skeleton-cell skeleton-action"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-cell skeleton-product"></div>
                                    </td>
                                    <td class="text-right">
                                        <div class="skeleton-cell skeleton-price"></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="skeleton-cell skeleton-status"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-cell skeleton-id"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-cell skeleton-time"></div>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>

                    <!-- Mobile Card View -->
                    <div class="orders-mobile-list" id="ordersMobileList">
                        <!-- Content loaded via AJAX -->
                    </div>

                    <!-- Mobile Loading Spinner -->
                    <div class="orders-mobile-loading" id="ordersLoadingMobile">
                        <div class="mobile-spinner-wrapper">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <span><?= __('Đang tải đơn hàng...'); ?></span>
                        </div>
                    </div>

                    <!-- Load More -->
                    <div class="load-more-wrapper" id="loadMoreWrapper" style="display: none;">
                        <button type="button" class="btn-load-more" id="btnLoadMore">
                            <i class="fa-solid fa-plus"></i>
                            <?= __('Xem thêm'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hidden Inputs -->
<input type="hidden" id="userToken" value="<?= isset($getUser) ? $getUser['token'] : ''; ?>">
<input type="hidden" id="csrfToken" value="<?= generateCSRFToken(); ?>">

<?php
require_once(__DIR__ . '/footer.php');
?>