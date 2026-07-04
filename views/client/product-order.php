<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once(__DIR__ . '/../../models/is_user.php');

// Lấy trans_id từ URL
$trans_id = isset($_GET['trans_id']) ? validate_string($_GET['trans_id'], 50) : '';
if (empty($trans_id)) {
    header('Location: ' . base_url('product-orders'));
    exit();
}

// Lấy thông tin đơn hàng
// Ưu tiên lấy tên từ cột đã lưu trong product_orders, fallback sang JOIN nếu trống
$order = $CMSNT->get_row_safe("
    SELECT po.*, 
           COALESCE(NULLIF(po.`product_name`, ''), p.`name`) as product_name,
           COALESCE(NULLIF(po.`plan_name`, ''), pp.`name`) as plan_name,
           p.`image` as product_image,
           p.`slug` as product_slug,
           pp.`duration_type`,
           pp.`duration_value`,
           pp.`is_instant` as plan_is_instant,
           pp.`supplier_id` as plan_supplier_id
    FROM `product_orders` po 
    LEFT JOIN `products` p ON po.`product_id` = p.`id` 
    LEFT JOIN `product_plans` pp ON po.`plan_id` = pp.`id`
    WHERE po.`trans_id` = ? AND po.`user_id` = ?
", [$trans_id, $getUser['id']]);

if (!$order) {
    header('Location: ' . base_url('product-orders'));
    exit();
}

// Fallback cuối cùng nếu cả cột lưu và JOIN đều trống
if (empty($order['product_name'])) {
    $order['product_name'] = __('Sản phẩm') . ' #' . $order['product_id'];
}
if (empty($order['plan_name'])) {
    $order['plan_name'] = __('Gói') . ' #' . $order['plan_id'];
}

// Kiểm tra bảo vệ đơn hàng: chỉ cho phép xem từ IP và trình duyệt đã mua
// Check is_protected của đơn hàng (lưu tại thời điểm tạo đơn), fallback về status_view_order cho đơn cũ
$is_order_protected = isset($order['is_protected']) ? ($order['is_protected'] == 1) : ($getUser['status_view_order'] == 1);
if ($is_order_protected && !empty($order['buyer_ip']) && !empty($order['buyer_useragent'])) {
    $current_ip = myip();
    $current_ua = getUserAgent();

    // So sánh IP và User Agent
    $ip_match = ($current_ip === $order['buyer_ip']);
    $ua_match = (strpos($current_ua, substr($order['buyer_useragent'], 0, 100)) !== false ||
        strpos($order['buyer_useragent'], substr($current_ua, 0, 100)) !== false);

    if (!$ip_match || !$ua_match) {
        // Ghi log cảnh báo
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => $current_ip,
            'device'        => substr($current_ua, 0, 255),
            'createdate'    => gettime(),
            'action'        => sprintf("[Cảnh báo] Cố gắng xem đơn hàng #%s từ IP/trình duyệt khác. IP gốc: %s", $order['trans_id'], $order['buyer_ip'])
        ]);

        // Redirect về trang danh sách với thông báo
        $_SESSION['order_security_warning'] = __('Không thể xem đơn hàng này. Bạn đã bật tính năng bảo vệ đơn hàng và đang truy cập từ IP hoặc trình duyệt khác với lúc mua hàng.');
        header('Location: ' . base_url('product-orders'));
        exit();
    }
}

$body = [
    'title' => __('Chi tiết đơn hàng') . ' #' . $order['trans_id'] . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/main.css">
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/product-orders.css">
' . ($CMSNT->site('support_tickets_status') == 1 ? '<link rel="stylesheet" href="' . BASE_URL('mod/css/ticket.css') . '">' : '') . '
';
$body['footer'] = '<script src="' . BASE_URL('mod/') . 'js/product-order.js"></script>';

// Parse fields_data
$fields_data = [];
if (!empty($order['fields_data'])) {
    $fields_data = json_decode($order['fields_data'], true);
    if (!is_array($fields_data)) $fields_data = [];
}

// Lấy danh sách trường của plan để hiển thị label
$plan_fields = [];
if ($order['plan_id'] > 0) {
    $plan_fields_raw = $CMSNT->get_list_safe("
        SELECT `field_key`, `label` FROM `product_fields` 
        WHERE `plan_id` = ? 
        ORDER BY `sort_order` ASC
    ", [$order['plan_id']]);
    foreach ($plan_fields_raw as $pf) {
        $plan_fields[$pf['field_key']] = $pf['label'];
    }
}

// Lấy stock nếu là gói giao ngay và đã hoàn thành
$stock_list = [];
if (isset($order['plan_is_instant']) && (int)$order['plan_is_instant'] == 1 && $order['status'] == 'completed') {
    $stock_list = $CMSNT->get_list_safe("
        SELECT `stock_value` FROM `product_stock` WHERE `order_id` = ?
    ", [$order['id']]);
}

// Tính final amount
$has_sale = $order['sale_price'] > 0 && $order['sale_price'] < $order['total_price'];
$has_discount = !empty($order['coupon_code']) && isset($order['discount_amount']) && $order['discount_amount'] > 0;
$final_amount = isset($order['final_amount']) && $order['final_amount'] >= 0
    ? $order['final_amount']
    : ($has_sale ? $order['sale_price'] : $order['total_price']);

// Tính thời gian hết hạn
$expiry_info = null;

// Ưu tiên custom_expiry_date nếu có
if (!empty($order['custom_expiry_date'])) {
    $expiry_date = strtotime($order['custom_expiry_date']);
    $expiry_info = [
        'date' => $expiry_date,
        'is_expired' => time() > $expiry_date,
        'days_remaining' => ceil(($expiry_date - time()) / 86400),
        'is_custom' => true
    ];
} elseif ($order['status'] == 'completed' && !empty($order['duration_type']) && $order['duration_type'] != 'lifetime') {
    // Dùng completed_at (fallback về updated_at cho đơn cũ)
    $completed_time = !empty($order['completed_at'])
        ? strtotime($order['completed_at'])
        : strtotime($order['updated_at']);
    $expiry_date = null;

    switch ($order['duration_type']) {
        case 'day':
            $expiry_date = strtotime('+' . $order['duration_value'] . ' days', $completed_time);
            break;
        case 'month':
            $expiry_date = strtotime('+' . $order['duration_value'] . ' months', $completed_time);
            break;
        case 'year':
            $expiry_date = strtotime('+' . $order['duration_value'] . ' years', $completed_time);
            break;
    }

    if ($expiry_date) {
        $expiry_info = [
            'date' => $expiry_date,
            'is_expired' => time() > $expiry_date,
            'days_remaining' => ceil(($expiry_date - time()) / 86400),
            'is_custom' => false
        ];
    }
}

// Status labels
function get_order_status_info($status)
{
    $statuses = [
        'pending' => ['label' => __('Chờ xử lý'), 'class' => 'status-pending', 'icon' => 'fa-clock', 'color' => '#f59e0b'],
        'processing' => ['label' => __('Đang xử lý'), 'class' => 'status-processing', 'icon' => 'fa-spinner', 'color' => '#3b82f6'],
        'completed' => ['label' => __('Hoàn thành'), 'class' => 'status-completed', 'icon' => 'fa-check-circle', 'color' => '#10b981'],
        'cancelled' => ['label' => __('Đã hủy'), 'class' => 'status-cancelled', 'icon' => 'fa-times-circle', 'color' => '#ef4444'],
        'cancelled_no_refund' => ['label' => __('Đã hủy'), 'class' => 'status-cancelled', 'icon' => 'fa-times-circle', 'color' => '#ef4444']
    ];
    return $statuses[$status] ?? ['label' => $status, 'class' => 'status-pending', 'icon' => 'fa-question-circle', 'color' => '#64748b'];
}

$status_info = get_order_status_info($order['status']);

// Lấy thông tin gói hiện tại để mua lại (giá có thể đã thay đổi)
$current_plan = $CMSNT->get_row_safe("
    SELECT pp.*, p.`name` as product_name, p.`image` as product_image, p.`slug` as product_slug, p.`status` as product_status
    FROM `product_plans` pp
    LEFT JOIN `products` p ON pp.`product_id` = p.`id`
    WHERE pp.`id` = ? AND pp.`status` = 1 AND p.`status` = 1
", [$order['plan_id']]);

// Có thể mua lại nếu sản phẩm và gói vẫn còn hoạt động
$can_reorder = !empty($current_plan);
$reorder_price = 0;
if ($can_reorder) {
    $reorder_price = ($current_plan['sale_price'] > 0 && $current_plan['sale_price'] < $current_plan['price'])
        ? $current_plan['sale_price']
        : $current_plan['price'];
}

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
?>

<section class="py-5 inner-section profile-part product-orders-page order-detail-page">
    <div class="container">
        <div class="row content-reverse">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-3">
                <?php require_once(__DIR__ . '/sidebar.php'); ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 orders-main mb-3">
                <!-- Breadcrumb -->
                <div class="order-breadcrumb">
                    <a href="<?= base_url('product-orders'); ?>">
                        <i class="fa-solid fa-arrow-left"></i>
                        <?= __('Quay lại danh sách'); ?>
                    </a>
                </div>

                <!-- Order Header -->
                <div class="order-detail-header">
                    <div class="order-header-row1">
                        <span class="order-id-text"><?= __('Chi tiết đơn hàng'); ?> #<?= htmlspecialchars($order['trans_id']); ?></span>
                        <div class="order-header-actions">
                            <?php if ($can_reorder): ?>
                                <button type="button" class="btn-reorder" id="btnReorder" title="<?= __('Mua lại đơn hàng này'); ?>">
                                    <i class="fa-solid fa-rotate-right"></i>
                                    <?= __('Mua lại'); ?>
                                </button>
                            <?php endif; ?>
                            <span class="order-status <?= $status_info['class']; ?>">
                                <i class="fa-solid <?= $status_info['icon']; ?>"></i>
                                <?= $status_info['label']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="order-header-row2">
                        <i class="fa-regular fa-calendar"></i>
                        <?= date('d/m/Y H:i:s', strtotime($order['created_at'])); ?>
                    </div>
                </div>

                <!-- Order Content -->
                <div class="order-detail-content">
                    <!-- Product Info -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i class="fa-solid fa-box"></i>
                            <?= __('Thông tin sản phẩm'); ?>
                        </div>
                        <div class="detail-card-body">
                            <div class="product-detail-row">
                                <div class="product-image-large">
                                    <?php if (!empty($order['product_image'])): ?>
                                        <img src="<?= BASE_URL($order['product_image']); ?>" alt="<?= htmlspecialchars($order['product_name']); ?>">
                                    <?php else: ?>
                                        <div class="no-image"><i class="fa-solid fa-box"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-detail-info">
                                    <h3 class="product-name">
                                        <?php if (!empty($order['product_slug'])): ?>
                                            <a href="<?= base_url('product/' . $order['product_slug']); ?>">
                                                <?= htmlspecialchars(html_entity_decode($order['product_name'], ENT_QUOTES, 'UTF-8')); ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars(html_entity_decode($order['product_name'], ENT_QUOTES, 'UTF-8')); ?>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="product-meta">
                                        <span class="plan-badge">
                                            <i class="fa-solid fa-tag"></i>
                                            <?= htmlspecialchars(html_entity_decode($order['plan_name'], ENT_QUOTES, 'UTF-8')); ?>
                                        </span>
                                        <?php if (isset($order['plan_is_instant']) && (int)$order['plan_is_instant'] == 1): ?>
                                            <span class="instant-badge">
                                                <i class="fa-solid fa-bolt"></i>
                                                <?= __('Giao ngay'); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($order['quantity']) && $order['quantity'] > 1): ?>
                                            <span class="quantity-badge">
                                                <i class="fa-solid fa-layer-group"></i>
                                                x<?= $order['quantity']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php
                                    // Kiểm tra xem đây có phải sản phẩm từ API không
                                    // Sản phẩm API (supplier_id > 0) sẽ không hiển thị thông tin thời hạn
                                    // vì thời hạn được quản lý bởi API nguồn, không phải hệ thống này
                                    $is_api_product = !empty($order['plan_supplier_id']) && $order['plan_supplier_id'] > 0;

                                    // Chỉ hiển thị duration info cho sản phẩm hệ thống (không phải API)
                                    if (!empty($order['duration_type']) && !$is_api_product):
                                    ?>
                                        <div class="duration-info">
                                            <?php if ($order['duration_type'] == 'lifetime'): ?>
                                                <span class="duration-badge lifetime">
                                                    <i class="fa-solid fa-infinity"></i>
                                                    <?= __('Thời hạn vĩnh viễn'); ?>
                                                </span>
                                            <?php else:
                                                $duration_labels = ['day' => __('ngày'), 'month' => __('tháng'), 'year' => __('năm')];
                                            ?>
                                                <span class="duration-badge">
                                                    <i class="fa-solid fa-clock"></i>
                                                    <?= __('Thời hạn:'); ?> <?= $order['duration_value']; ?> <?= $duration_labels[$order['duration_type']] ?? ''; ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($expiry_info): ?>
                                                <span class="expiry-info <?= $expiry_info['is_expired'] ? 'expired' : ($expiry_info['days_remaining'] <= 7 ? 'warning' : ''); ?>">
                                                    <?php if ($expiry_info['is_expired']): ?>
                                                        <i class="fa-solid fa-exclamation-circle"></i>
                                                        <?= __('Đã hết hạn'); ?>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-hourglass-half"></i>
                                                        <?= sprintf(__('Còn %d ngày'), $expiry_info['days_remaining']); ?>
                                                        (<?= date('d/m/Y', $expiry_info['date']); ?>)
                                                    <?php endif; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i class="fa-solid fa-credit-card"></i>
                            <?= __('Thông tin thanh toán'); ?>
                        </div>
                        <div class="detail-card-body">
                            <div class="payment-rows">
                                <div class="payment-row">
                                    <span class="payment-label"><?= __('Giá gốc'); ?></span>
                                    <span class="payment-value"><?= format_currency($order['total_price']); ?></span>
                                </div>
                                <?php if ($has_sale): ?>
                                    <div class="payment-row discount">
                                        <span class="payment-label"><?= __('Giảm giá sản phẩm'); ?></span>
                                        <span class="payment-value">-<?= format_currency($order['total_price'] - $order['sale_price']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($has_discount): ?>
                                    <div class="payment-row discount">
                                        <span class="payment-label">
                                            <?= __('Mã giảm giá'); ?>
                                            <span class="coupon-code"><?= htmlspecialchars($order['coupon_code']); ?></span>
                                        </span>
                                        <span class="payment-value">-<?= format_currency($order['discount_amount']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="payment-row total">
                                    <span class="payment-label"><?= __('Tổng thanh toán'); ?></span>
                                    <span class="payment-value"><?= format_currency($final_amount); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Fields -->
                    <?php if (!empty($fields_data)): ?>
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <i class="fa-solid fa-list-check"></i>
                                <?= __('Thông tin đơn hàng'); ?>
                            </div>
                            <div class="detail-card-body">
                                <div class="fields-grid">
                                    <?php foreach ($fields_data as $key => $value): ?>
                                        <?php if (!empty($value)): ?>
                                            <div class="field-item">
                                                <span class="field-label"><?= htmlspecialchars($plan_fields[$key] ?? $key); ?></span>
                                                <span class="field-value"><?= htmlspecialchars($value); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Delivery Content / Stock -->
                    <?php if (!empty($stock_list) && $order['status'] == 'completed'): ?>
                        <div class="detail-card delivery-card">
                            <div class="detail-card-header">
                                <i class="fa-solid fa-gift"></i>
                                <?= __('Thông tin tài khoản'); ?>
                                <span class="stock-count-badge"><?= count($stock_list); ?> <?= __('tài khoản'); ?></span>
                            </div>
                            <div class="detail-card-body">
                                <div class="stock-copy-all mb-3" data-content="<?= htmlspecialchars(implode("\n", array_column($stock_list, 'stock_value'))); ?>" data-json="<?= htmlspecialchars(json_encode(array_column($stock_list, 'stock_value'))); ?>" data-filename="order-<?= htmlspecialchars($order['trans_id']); ?>">
                                    <button type="button" class="btn-copy-all" onclick="copyAllContent(this)">
                                        <i class="fa-solid fa-copy"></i>
                                        <?= __('Sao chép tất cả'); ?>
                                    </button>
                                    <button type="button" class="btn-export-txt" onclick="exportAllToTxt(this)">
                                        <i class="fa-solid fa-file-lines"></i>
                                        <?= __('Xuất TXT'); ?>
                                    </button>
                                    <button type="button" class="btn-export-csv" onclick="exportAllToCsv(this)">
                                        <i class="fa-solid fa-file-csv"></i>
                                        <?= __('Xuất CSV'); ?>
                                    </button>
                                </div>
                                <div class="stock-list">
                                    <?php foreach ($stock_list as $index => $stock): ?>
                                        <div class="stock-item">
                                            <div class="stock-item-header">
                                                <span class="stock-item-number">#<?= $index + 1; ?></span>
                                                <div class="stock-item-actions">
                                                    <button type="button" class="btn-copy-single" onclick="copySingleItem(this)" data-content="<?= htmlspecialchars($stock['stock_value']); ?>" title="<?= __('Sao chép'); ?>">
                                                        <i class="fa-solid fa-copy"></i>
                                                    </button>
                                                    <?php if ($CMSNT->site('support_tickets_status') == 1): ?>
                                                        <button type="button" class="btn-report-error" onclick="openReportErrorModal(this)" data-content="<?= htmlspecialchars($stock['stock_value']); ?>" data-index="<?= $index + 1; ?>" title="<?= __('Báo lỗi'); ?>">
                                                            <i class="fa-solid fa-bug"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="stock-item-content"><?= nl2br(htmlspecialchars($stock['stock_value'])); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!empty($order['delivery_content']) && $order['status'] == 'completed'):
                        // Split delivery_content thành từng dòng
                        $delivery_lines = array_values(array_filter(
                            array_map('trim', preg_split('/\r\n|\r|\n/', $order['delivery_content'])),
                            function ($line) {
                                return $line !== '';
                            }
                        ));
                    ?>
                        <div class="detail-card delivery-card">
                            <div class="detail-card-header">
                                <i class="fa-solid fa-truck-fast"></i>
                                <?= __('Nội dung giao hàng'); ?>
                                <span class="stock-count-badge"><?= count($delivery_lines); ?> <?= __('tài khoản'); ?></span>
                            </div>
                            <div class="detail-card-body">
                                <div class="stock-copy-all mb-3" data-content="<?= htmlspecialchars(implode("\n", $delivery_lines)); ?>" data-json="<?= htmlspecialchars(json_encode($delivery_lines)); ?>" data-filename="order-<?= htmlspecialchars($order['trans_id']); ?>">
                                    <button type="button" class="btn-copy-all" onclick="copyAllContent(this)">
                                        <i class="fa-solid fa-copy"></i>
                                        <?= __('Sao chép tất cả'); ?>
                                    </button>
                                    <button type="button" class="btn-export-txt" onclick="exportAllToTxt(this)">
                                        <i class="fa-solid fa-file-lines"></i>
                                        <?= __('Xuất TXT'); ?>
                                    </button>
                                    <button type="button" class="btn-export-csv" onclick="exportAllToCsv(this)">
                                        <i class="fa-solid fa-file-csv"></i>
                                        <?= __('Xuất CSV'); ?>
                                    </button>
                                </div>
                                <div class="stock-list">
                                    <?php foreach ($delivery_lines as $index => $line): ?>
                                        <div class="stock-item">
                                            <div class="stock-item-header">
                                                <span class="stock-item-number">#<?= $index + 1; ?></span>
                                                <div class="stock-item-actions">
                                                    <button type="button" class="btn-copy-single" onclick="copySingleItem(this)" data-content="<?= htmlspecialchars($line); ?>" title="<?= __('Sao chép'); ?>">
                                                        <i class="fa-solid fa-copy"></i>
                                                    </button>
                                                    <?php if ($CMSNT->site('support_tickets_status') == 1): ?>
                                                        <button type="button" class="btn-report-error" onclick="openReportErrorModal(this)" data-content="<?= htmlspecialchars($line); ?>" data-index="<?= $index + 1; ?>" title="<?= __('Báo lỗi'); ?>">
                                                            <i class="fa-solid fa-bug"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="stock-item-content"><?= htmlspecialchars($line); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Cancel Reason -->
                    <?php if (($order['status'] == 'cancelled' || $order['status'] == 'cancelled_no_refund') && !empty($order['reason'])): ?>
                        <div class="detail-card cancel-card">
                            <div class="detail-card-header">
                                <i class="fa-solid fa-info-circle"></i>
                                <?= __('Lý do hủy đơn hàng'); ?>
                            </div>
                            <div class="detail-card-body">
                                <p class="cancel-reason"><?= htmlspecialchars($order['reason']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Note -->
                    <?php if (!empty($order['note'])): ?>
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <i class="fa-solid fa-sticky-note"></i>
                                <?= __('Ghi chú'); ?>
                            </div>
                            <div class="detail-card-body">
                                <p class="order-note"><?= nl2br(htmlspecialchars($order['note'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($CMSNT->site('support_tickets_status') == 1): ?>
    <!-- Modal Báo lỗi Account -->
    <div class="ticket-modal-overlay" id="reportErrorModal">
        <div class="ticket-modal-container" style="max-width: 500px;">
            <div class="ticket-modal-content">
                <div class="ticket-modal-header">
                    <h5 class="ticket-modal-title">
                        <i class="fa-solid fa-bug"></i>
                        <?= __('Báo lỗi tài khoản'); ?>
                    </h5>
                    <button type="button" class="ticket-modal-close" id="closeReportErrorModal">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="ticket-modal-body">
                    <div class="report-error-info">
                        <div class="report-error-order">
                            <strong><?= __('Đơn hàng:'); ?></strong>
                            <span>#<?= htmlspecialchars($order['trans_id']); ?></span>
                        </div>
                        <div class="report-error-product">
                            <strong><?= __('Sản phẩm:'); ?></strong>
                            <span><?= htmlspecialchars(html_entity_decode($order['product_name'], ENT_QUOTES, 'UTF-8')); ?></span>
                        </div>
                        <div class="report-error-account">
                            <strong><?= __('Tài khoản lỗi:'); ?></strong>
                            <div class="error-account-content" id="errorAccountContent"></div>
                        </div>
                    </div>
                    <div class="ticket-form-group">
                        <label class="ticket-form-label"><?= __('Mô tả lỗi'); ?> <span class="required">*</span></label>
                        <textarea class="ticket-form-control" id="errorDescription" rows="4" placeholder="<?= __('Mô tả chi tiết lỗi bạn gặp phải với tài khoản này...'); ?>" required></textarea>
                    </div>
                </div>
                <div class="ticket-modal-footer">
                    <button type="button" class="btn-modal-cancel" id="cancelReportErrorModal"><?= __('Đóng'); ?></button>
                    <button type="button" class="btn-submit-ticket" id="submitReportError">
                        <span class="btn-spinner d-none">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i><?= __('Đang gửi...'); ?>
                        </span>
                        <span class="btn-text">
                            <i class="fa-solid fa-paper-plane me-1"></i><?= __('Gửi báo lỗi'); ?>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($can_reorder): ?>
            // Initialize reorder feature
            if (typeof initReorderFeature === 'function') {
                initReorderFeature({
                    reorderData: {
                        product_id: <?= $order['product_id']; ?>,
                        plan_id: <?= $order['plan_id']; ?>,
                        quantity: <?= $order['quantity']; ?>,
                        product_name: <?= json_encode(html_entity_decode($current_plan['product_name'], ENT_QUOTES, 'UTF-8')); ?>,
                        plan_name: <?= json_encode(html_entity_decode($current_plan['name'], ENT_QUOTES, 'UTF-8')); ?>,
                        product_image: <?= json_encode($current_plan['product_image']); ?>,
                        product_slug: <?= json_encode($current_plan['product_slug']); ?>,
                        final_price: <?= $reorder_price; ?>,
                        fields: <?= json_encode($fields_data); ?>
                    },
                    messages: {
                        addedToCart: '<?= __('Đã thêm vào giỏ hàng!'); ?>',
                        goToCart: '<?= __('Đã thêm sản phẩm vào giỏ hàng. Bạn có muốn đến giỏ hàng ngay?'); ?>'
                    },
                    cartUrl: '<?= base_url('cart'); ?>'
                });
            }
        <?php endif; ?>

        // Initialize report error feature
        <?php if ($CMSNT->site('support_tickets_status') == 1): ?>
            if (typeof ReportError !== 'undefined') {
                ReportError.init({
                    ajaxUrl: '<?= base_url('ajaxs/client/ticket.php'); ?>',
                    transId: '<?= htmlspecialchars($order['trans_id']); ?>',
                    productName: <?= json_encode(html_entity_decode($order['product_name'], ENT_QUOTES, 'UTF-8')); ?>,
                    planName: <?= json_encode(html_entity_decode($order['plan_name'], ENT_QUOTES, 'UTF-8')); ?>,
                    userToken: '<?= $getUser['token']; ?>',
                    csrfToken: '<?= generateCSRFToken(); ?>',
                    lang: {
                        error: '<?= __('Lỗi'); ?>',
                        success: '<?= __('Thành công!'); ?>',
                        minLength: '<?= __('Vui lòng mô tả chi tiết lỗi (ít nhất 10 ký tự)'); ?>',
                        successMsg: '<?= __('Đã gửi báo lỗi thành công. Chúng tôi sẽ kiểm tra và phản hồi sớm nhất.'); ?>',
                        serverError: '<?= __('Không thể kết nối đến server'); ?>',
                        genericError: '<?= __('Có lỗi xảy ra'); ?>',
                        close: '<?= __('Đóng'); ?>',
                        reportTitle: '<?= __('Báo lỗi đơn hàng'); ?>',
                        headerReport: '<?= __('** BÁO LỖI ĐƠN HÀNG **'); ?>',
                        orderId: '<?= __('Mã đơn hàng:'); ?>',
                        product: '<?= __('Sản phẩm:'); ?>',
                        plan: '<?= __('Gói:'); ?>',
                        accountError: '<?= __('** TÀI KHOẢN LỖI (STT #'); ?>',
                        descriptionLabel: '<?= __('** MÔ TẢ LỖI **'); ?>'
                    }
                });
            }
        <?php endif; ?>

        // Auto-polling kiểm tra trạng thái đơn hàng
        <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                (function() {
                    var checkInterval = 5000; // 5 giây
                    var maxAttempts = 120; // Tối đa 10 phút (120 * 5s)
                    var attempts = 0;
                    var pollingTimer = null;

                    function checkOrderStatus() {
                        attempts++;
                        if (attempts > maxAttempts) {
                            console.log('Đã hết thời gian polling, dừng kiểm tra tự động.');
                            clearInterval(pollingTimer);
                            return;
                        }

                        $.ajax({
                            url: '<?= base_url("ajaxs/client/product-orders.php"); ?>',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'checkOrderStatus',
                                token: '<?= $getUser["token"]; ?>',
                                trans_id: '<?= htmlspecialchars($order["trans_id"]); ?>'
                            },
                            success: function(response) {
                                if (response.status === 'success' && response.should_reload) {
                                    clearInterval(pollingTimer);
                                    // Hiển thị thông báo và reload
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            title: '<?= __("Đơn hàng đã được cập nhật!"); ?>',
                                            text: '<?= __("Trang sẽ tự động tải lại để hiển thị kết quả."); ?>',
                                            icon: 'success',
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(function() {
                                            location.reload();
                                        });
                                    } else {
                                        location.reload();
                                    }
                                }
                            },
                            error: function() {
                                console.log('Lỗi khi kiểm tra trạng thái đơn hàng');
                            }
                        });
                    }

                    // Bắt đầu polling
                    pollingTimer = setInterval(checkOrderStatus, checkInterval);
                    // Kiểm tra ngay lần đầu sau 2 giây
                    setTimeout(checkOrderStatus, 2000);
                })();
        <?php endif; ?>
    });
</script>

<?php
require_once(__DIR__ . '/footer.php');
?>