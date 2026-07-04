<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý đơn hàng') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_orders_product') != true) {
    $role_name = getRoleName('view_orders_product');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

// Phân trang an toàn
$limit = isset($_GET['limit']) ? (validate_int($_GET['limit'], 1, 1000) ?: 20) : 10;
$page = isset($_GET['page']) ? (validate_int($_GET['page'], 1, 1000000) ?: 1) : 1;
$from = ($page - 1) * $limit;

// Biến giữ giá trị hiển thị lại
$product_filter = 0;
$plan_filter = 0;
$user_filter = 0;
$status_filter = '';
$search = '';
$api_trans_id_search = '';
$supplier_filter = 0;
$date_from = '';
$date_to = '';
$coupon_code_filter = '';
$has_coupon_filter = '';
$has_commission_filter = '';
$expiry_status_filter = '';
$delivery_search = '';

// WHERE an toàn với prepared statements
$where_conditions = ["1 = 1"];
$where_params = [];

// Lọc theo sản phẩm
if (!empty($_GET['product_id'])) {
    $product_filter_input = validate_int($_GET['product_id'], 1);
    if ($product_filter_input !== false) {
        $product_filter = $product_filter_input;
        $where_conditions[] = 'po.`product_id` = ?';
        $where_params[] = $product_filter;
    } else {
        $product_filter = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    }
}

// Lọc theo gói
if (!empty($_GET['plan_id'])) {
    $plan_filter_input = validate_int($_GET['plan_id'], 1);
    if ($plan_filter_input !== false) {
        $plan_filter = $plan_filter_input;
        $where_conditions[] = 'po.`plan_id` = ?';
        $where_params[] = $plan_filter;
    } else {
        $plan_filter = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
    }
}

// Lọc theo user
if (!empty($_GET['user_id'])) {
    $user_filter_input = validate_int($_GET['user_id'], 1);
    if ($user_filter_input !== false) {
        $user_filter = $user_filter_input;
        $where_conditions[] = 'po.`user_id` = ?';
        $where_params[] = $user_filter;
    } else {
        $user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    }
}

// Lọc theo trạng thái đơn hàng
if (!empty($_GET['status'])) {
    $status_input = validate_string($_GET['status'], 20);
    if ($status_input !== false && in_array($status_input, ['pending', 'processing', 'completed', 'cancelled', 'cancelled_no_refund'])) {
        $status_filter = $status_input;
        $where_conditions[] = 'po.`status` = ?';
        $where_params[] = $status_filter;
    } else {
        $status_filter = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';
    }
}

// Tìm kiếm theo mã đơn hàng
if (!empty($_GET['search'])) {
    $search_input = validate_string($_GET['search'], 100, 1);
    if ($search_input !== false) {
        $search = $search_input;
        $where_conditions[] = 'po.`trans_id` LIKE ?';
        $where_params[] = '%' . $search . '%';
    } else {
        $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
    }
}

// Tìm kiếm theo mã đơn hàng API
if (!empty($_GET['api_trans_id'])) {
    $api_trans_id_input = validate_string($_GET['api_trans_id'], 100, 1);
    if ($api_trans_id_input !== false) {
        $api_trans_id_search = $api_trans_id_input;
        $where_conditions[] = 'po.`api_trans_id` LIKE ?';
        $where_params[] = '%' . $api_trans_id_search . '%';
    } else {
        $api_trans_id_search = isset($_GET['api_trans_id']) ? htmlspecialchars($_GET['api_trans_id'] ?? '') : '';
    }
}

// Tìm kiếm theo nội dung đã giao (tài khoản đã bán)
if (!empty($_GET['delivery_search'])) {
    $delivery_search_input = validate_string($_GET['delivery_search'], 200, 1);
    if ($delivery_search_input !== false) {
        $delivery_search = $delivery_search_input;
        // Tìm trong delivery_content của đơn hàng HOẶC stock_value trong product_stock (đã bán)
        $where_conditions[] = '(po.`delivery_content` LIKE ? OR EXISTS (SELECT 1 FROM `product_stock` pst WHERE pst.order_id = po.id AND pst.stock_value LIKE ?))';
        $where_params[] = '%' . $delivery_search . '%';
        $where_params[] = '%' . $delivery_search . '%';
    } else {
        $delivery_search = isset($_GET['delivery_search']) ? htmlspecialchars($_GET['delivery_search']) : '';
    }
}

// Lọc theo nhà cung cấp API
if (!empty($_GET['supplier_id'])) {
    $supplier_filter_input = validate_int($_GET['supplier_id'], 1);
    if ($supplier_filter_input !== false) {
        $supplier_filter = $supplier_filter_input;
        $where_conditions[] = 'pp.`supplier_id` = ?';
        $where_params[] = $supplier_filter;
    } else {
        $supplier_filter = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
    }
}

// Lọc theo thời gian từ
if (!empty($_GET['date_from'])) {
    $date_from_input = validate_string($_GET['date_from'], 20);
    if ($date_from_input !== false && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from_input)) {
        $date_from = $date_from_input;
        $where_conditions[] = 'DATE(po.`created_at`) >= ?';
        $where_params[] = $date_from;
    } else {
        $date_from = isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : '';
    }
}

// Lọc theo thời gian đến
if (!empty($_GET['date_to'])) {
    $date_to_input = validate_string($_GET['date_to'], 20);
    if ($date_to_input !== false && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to_input)) {
        $date_to = $date_to_input;
        $where_conditions[] = 'DATE(po.`created_at`) <= ?';
        $where_params[] = $date_to;
    } else {
        $date_to = isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : '';
    }
}

// Lọc theo mã giảm giá
if (!empty($_GET['coupon_code'])) {
    $coupon_code_input = validate_string($_GET['coupon_code'], 50, 1);
    if ($coupon_code_input !== false) {
        $coupon_code_filter = $coupon_code_input;
        $where_conditions[] = 'po.`coupon_code` = ?';
        $where_params[] = $coupon_code_filter;
    } else {
        $coupon_code_filter = isset($_GET['coupon_code']) ? htmlspecialchars($_GET['coupon_code']) : '';
    }
}

// Lọc theo đơn có/không có coupon
if (isset($_GET['has_coupon']) && $_GET['has_coupon'] !== '') {
    $has_coupon_input = validate_int($_GET['has_coupon'], 0, 1);
    if ($has_coupon_input !== false) {
        $has_coupon_filter = $has_coupon_input;
        if ($has_coupon_filter == 1) {
            // Có coupon
            $where_conditions[] = 'po.`coupon_code` IS NOT NULL AND po.`coupon_code` != "" AND po.`discount_amount` > 0';
        } else {
            // Không có coupon
            $where_conditions[] = '(po.`coupon_code` IS NULL OR po.`coupon_code` = "" OR po.`discount_amount` = 0)';
        }
    } else {
        $has_coupon_filter = isset($_GET['has_coupon']) ? intval($_GET['has_coupon']) : '';
    }
}

// Lọc theo đơn có/không có hoa hồng
if (isset($_GET['has_commission']) && $_GET['has_commission'] !== '') {
    $has_commission_input = validate_int($_GET['has_commission'], 0, 1);
    if ($has_commission_input !== false) {
        $has_commission_filter = $has_commission_input;
        if ($has_commission_filter == 1) {
            // Có hoa hồng
            $where_conditions[] = 'po.`commission_amount` > 0 AND po.`commission_user_id` IS NOT NULL';
        } else {
            // Không có hoa hồng
            $where_conditions[] = '(po.`commission_amount` = 0 OR po.`commission_amount` IS NULL OR po.`commission_user_id` IS NULL)';
        }
    } else {
        $has_commission_filter = isset($_GET['has_commission']) ? intval($_GET['has_commission']) : '';
    }
}

// Lọc theo đơn hàng API
$is_api_order_filter = '';
if (isset($_GET['is_api_order']) && $_GET['is_api_order'] !== '') {
    $is_api_order_input = validate_int($_GET['is_api_order'], 0, 1);
    if ($is_api_order_input !== false) {
        $is_api_order_filter = $is_api_order_input;
        if ($is_api_order_filter == 1) {
            // Đơn từ API
            $where_conditions[] = 'pp.`supplier_id` > 0';
        } else {
            // Đơn thường
            $where_conditions[] = '(pp.`supplier_id` IS NULL OR pp.`supplier_id` = 0)';
        }
    }
}

$where_sql = implode(' AND ', $where_conditions);

// Kiểm tra nếu có filter theo expiry_status
$has_expiry_filter = false;
$expiry_status_filter = '';
if (isset($_GET['expiry_status']) && $_GET['expiry_status'] !== '') {
    $expiry_status_input = validate_string($_GET['expiry_status'], 20);
    if ($expiry_status_input !== false && in_array($expiry_status_input, ['expired', 'expiring_soon', 'active', 'lifetime'])) {
        $expiry_status_filter = $expiry_status_input;
        $has_expiry_filter = true;

        // Nếu filter theo expiry, cần lấy TẤT CẢ đơn hàng completed có duration để filter
        $where_conditions[] = "po.`status` = 'completed'";
        $where_sql = implode(' AND ', $where_conditions);
    } else {
        $expiry_status_filter = isset($_GET['expiry_status']) ? htmlspecialchars($_GET['expiry_status']) : '';
    }
}

// Nếu có filter expiry, lấy tất cả đơn hàng để filter (không LIMIT)
if ($has_expiry_filter) {
    // Lấy TẤT CẢ đơn hàng completed
    $all_orders = $CMSNT->get_list_safe("
        SELECT po.*, 
               p.`name` as product_name, 
               pp.`name` as plan_name,
               pp.`is_instant` as plan_is_instant,
               pp.`duration_type`,
               pp.`duration_value`,
               pp.`supplier_id` as plan_supplier_id,
               pp.`api_id` as plan_api_id,
               u.`username` as user_username,
               u.`money` as user_money,
               cu.`username` as commission_username,
               s.`domain` as supplier_domain,
               s.`type` as supplier_type
        FROM `product_orders` po 
        LEFT JOIN `products` p ON po.`product_id` = p.`id` 
        LEFT JOIN `product_plans` pp ON po.`plan_id` = pp.`id`
        LEFT JOIN `users` u ON po.`user_id` = u.`id`
        LEFT JOIN `users` cu ON po.`commission_user_id` = cu.`id`
        LEFT JOIN `suppliers` s ON pp.`supplier_id` = s.`id`
        WHERE " . $where_sql . " 
        ORDER BY po.`id` DESC
    ", $where_params);

    // Filter theo expiry_status
    $filtered_orders = [];
    foreach ($all_orders as $order) {
        $include_order = false;

        if (!empty($order['duration_type'])) {
            if ($order['duration_type'] == 'lifetime') {
                // Đơn hàng vĩnh viễn
                if ($expiry_status_filter == 'lifetime') {
                    $include_order = true;
                }
            } else {
                // Ưu tiên custom_expiry_date nếu có
                if (!empty($order['custom_expiry_date'])) {
                    $expiry_date = strtotime($order['custom_expiry_date']);
                } else {
                    // Tính thời gian hết hạn từ completed_at (fallback về updated_at)
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
                }

                if ($expiry_date) {
                    $is_expired = time() > $expiry_date;
                    $days_remaining = ceil(($expiry_date - time()) / 86400);

                    if ($expiry_status_filter == 'expired' && $is_expired) {
                        $include_order = true;
                    } elseif ($expiry_status_filter == 'expiring_soon' && !$is_expired && $days_remaining <= 7 && $days_remaining > 0) {
                        $include_order = true;
                    } elseif ($expiry_status_filter == 'active' && !$is_expired && $days_remaining > 7) {
                        $include_order = true;
                    }
                }
            }
        }

        if ($include_order) {
            $filtered_orders[] = $order;
        }
    }

    // Cập nhật total và phân trang
    $total = count($filtered_orders);
    $total_pages = ceil($total / $limit);

    // Lấy đúng trang hiện tại từ filtered_orders
    $orders_list = array_slice($filtered_orders, $from, $limit);
} else {
    // Đếm tổng số lượng bình thường
    $total_query = "SELECT COUNT(*) as total FROM `product_orders` po LEFT JOIN `product_plans` pp ON po.`plan_id` = pp.`id` WHERE " . $where_sql;
    $total_result = $CMSNT->get_row_safe($total_query, $where_params);
    $total = $total_result ? (int)$total_result['total'] : 0;
    $total_pages = ceil($total / $limit);

    // Lấy danh sách đơn hàng với LIMIT bình thường
    $orders_list = $CMSNT->get_list_safe("
        SELECT po.*, 
               COALESCE(p.`name`, po.`product_name`) as product_name, 
               COALESCE(pp.`name`, po.`plan_name`) as plan_name,
               pp.`is_instant` as plan_is_instant,
               pp.`duration_type`,
               pp.`duration_value`,
               pp.`supplier_id` as plan_supplier_id,
               pp.`api_id` as plan_api_id,
               pp.`cost_price` as plan_cost_price,
               u.`username` as user_username,
               u.`money` as user_money,
               cu.`username` as commission_username,
               s.`domain` as supplier_domain,
               s.`type` as supplier_type
        FROM `product_orders` po 
        LEFT JOIN `products` p ON po.`product_id` = p.`id` 
        LEFT JOIN `product_plans` pp ON po.`plan_id` = pp.`id`
        LEFT JOIN `users` u ON po.`user_id` = u.`id`
        LEFT JOIN `users` cu ON po.`commission_user_id` = cu.`id`
        LEFT JOIN `suppliers` s ON pp.`supplier_id` = s.`id`
        WHERE " . $where_sql . " 
        ORDER BY po.`id` DESC 
        LIMIT ? OFFSET ?
    ", array_merge($where_params, [$limit, $from]));
}

// Đếm số lượng theo trạng thái
$stats = [
    'total' => $total,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN `status` = 'processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN `status` = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN `status` = 'cancelled_no_refund' THEN 1 ELSE 0 END) as cancelled_no_refund
FROM `product_orders`";
$stats_result = $CMSNT->get_row_safe($stats_query, []);
if ($stats_result) {
    $stats = [
        'total' => (int)$stats_result['total'],
        'pending' => (int)$stats_result['pending'],
        'processing' => (int)$stats_result['processing'],
        'completed' => (int)$stats_result['completed'],
        'cancelled' => (int)$stats_result['cancelled'],
        'cancelled_no_refund' => (int)$stats_result['cancelled_no_refund']
    ];
}

// Thống kê theo điều kiện filter (số đơn hàng, doanh thu, lợi nhuận)
$filter_stats = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'total_profit' => 0
];

// Query thống kê theo điều kiện filter
// Đếm tổng đơn theo filter, nhưng doanh thu/lợi nhuận chỉ tính đơn hoàn thành
$filter_stats_query = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN po.`status` = 'completed' THEN (CASE WHEN po.`final_amount` > 0 THEN po.`final_amount` ELSE po.`total_price` - COALESCE(po.`discount_amount`, 0) END) ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN po.`status` = 'completed' THEN (CASE WHEN po.`final_amount` > 0 THEN po.`final_amount` ELSE po.`total_price` - COALESCE(po.`discount_amount`, 0) END) - COALESCE(po.`cost_price`, 0) ELSE 0 END), 0) as total_profit
FROM `product_orders` po 
LEFT JOIN `products` p ON po.`product_id` = p.`id` 
LEFT JOIN `product_plans` pp ON po.`plan_id` = pp.`id`
LEFT JOIN `users` u ON po.`user_id` = u.`id`
LEFT JOIN `users` cu ON po.`commission_user_id` = cu.`id`
LEFT JOIN `suppliers` s ON pp.`supplier_id` = s.`id`
WHERE " . $where_sql;

$filter_stats_result = $CMSNT->get_row_safe($filter_stats_query, $where_params);
if ($filter_stats_result) {
    $filter_stats = [
        'total_orders' => (int)$filter_stats_result['total_orders'],
        'total_revenue' => floatval($filter_stats_result['total_revenue']),
        'total_profit' => floatval($filter_stats_result['total_profit'])
    ];
}

// Lấy danh sách sản phẩm và gói để filter
$products_list = $CMSNT->get_list_safe("SELECT `id`, `name` FROM `products` ORDER BY `name` ASC", []);
$plans_list = [];

// Nếu có plan_id trong URL nhưng chưa có product_filter, lấy product_id từ plan đó
if ($plan_filter > 0 && $product_filter == 0) {
    $plan_info = $CMSNT->get_row_safe("SELECT `product_id` FROM `product_plans` WHERE `id` = ?", [$plan_filter]);
    if ($plan_info) {
        $product_filter = (int)$plan_info['product_id'];
    }
}

// Load plans nếu có product_filter
if ($product_filter > 0) {
    $plans_list = $CMSNT->get_list_safe("SELECT `id`, `name` FROM `product_plans` WHERE `product_id` = ? AND `status` = 1 ORDER BY `id` DESC", [$product_filter]);
}

// Lấy danh sách nhà cung cấp API
$suppliers_list = $CMSNT->get_list_safe("SELECT `id`, `domain` FROM `suppliers` WHERE `status` = 1 ORDER BY `id` ASC", []);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="d-flex align-items-center gap-3">
                <h1 class="page-name fw-semibold fs-18 mb-0">
                    <i class="fa-solid fa-shopping-cart me-1"></i><?= __('Quản lý đơn hàng'); ?>
                </h1>
                <?php if (checkPermission($getUser['admin'], 'edit_orders_product')): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cleanupProductOrdersModal">
                        <i class="fa-solid fa-trash me-1"></i><?= __('Dọn dẹp'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <div class="ms-md-1 ms-0 d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#apiDocumentationModal">
                    <i class="fa-solid fa-book me-1"></i><?= __('Tài liệu API'); ?>
                </button>
                <a href="<?= base_url_admin('products'); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i><?= __('Quay lại'); ?>
                </a>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-primary-transparent rounded-circle">
                                    <i class="fa-solid fa-cart-shopping fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Tổng đơn hàng'); ?></p>
                                <h4 class="mb-0 fw-semibold"><?= number_format($stats['total']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-warning-transparent rounded-circle">
                                    <i class="fa-solid fa-hourglass-half fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Chờ xử lý'); ?></p>
                                <h4 class="mb-0 fw-semibold text-warning"><?= number_format($stats['pending']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-info-transparent rounded-circle">
                                    <i class="fa-solid fa-spinner fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Đang xử lý'); ?></p>
                                <h4 class="mb-0 fw-semibold text-info"><?= number_format($stats['processing']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-success-transparent rounded-circle">
                                    <i class="fa-solid fa-check-circle fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Hoàn thành'); ?></p>
                                <h4 class="mb-0 fw-semibold text-success"><?= number_format($stats['completed']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-danger-transparent rounded-circle">
                                    <i class="fa-solid fa-times-circle fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Đã hủy'); ?></p>
                                <h4 class="mb-0 fw-semibold text-danger"><?= number_format($stats['cancelled'] + $stats['cancelled_no_refund']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="toggleFilterForm()">
                <h6 class="mb-0">
                    <i class="fa-solid fa-filter me-2"></i><?= __('Bộ lọc tìm kiếm'); ?>
                </h6>
                <button type="button" class="btn btn-sm btn-light" id="toggleFilterBtn">
                    <i class="fa-solid fa-chevron-down" id="filterIcon"></i>
                </button>
            </div>
            <div class="card-body" id="filterFormBody" style="display: none;">
                <form method="GET" action="<?= base_url(); ?>">
                    <input type="hidden" name="module" value="<?= $CMSNT->site('path_admin'); ?>">
                    <input type="hidden" name="action" value="product-orders">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label"><?= __('Sản phẩm'); ?></label>
                            <select class="form-select" name="product_id" id="filter_product_id" onchange="loadPlans(this.value)">
                                <option value=""><?= __('Tất cả sản phẩm'); ?></option>
                                <?php foreach ($products_list as $prod): ?>
                                    <option value="<?= $prod['id']; ?>" <?= $product_filter == $prod['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars(html_entity_decode($prod['name'], ENT_QUOTES, 'UTF-8')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label"><?= __('Gói sản phẩm'); ?></label>
                            <select class="form-select" name="plan_id" id="filter_plan_id">
                                <option value=""><?= __('Tất cả gói'); ?></option>
                                <?php foreach ($plans_list as $plan): ?>
                                    <option value="<?= $plan['id']; ?>" <?= $plan_filter == $plan['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars(html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('User ID'); ?></label>
                            <input type="number" class="form-control" name="user_id"
                                value="<?= $user_filter > 0 ? $user_filter : ''; ?>"
                                placeholder="<?= __('ID người dùng'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Mã đơn hàng'); ?></label>
                            <input type="text" class="form-control" name="search"
                                value="<?= htmlspecialchars($search); ?>"
                                placeholder="<?= __('Mã đơn hàng...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Mã đơn API'); ?></label>
                            <input type="text" class="form-control" name="api_trans_id"
                                value="<?= htmlspecialchars($api_trans_id_search); ?>"
                                placeholder="<?= __('Mã đơn API...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Nội dung đã giao'); ?></label>
                            <input type="text" class="form-control" name="delivery_search"
                                value="<?= htmlspecialchars($delivery_search); ?>"
                                placeholder="<?= __('Tài khoản đã bán...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Nhà cung cấp API'); ?></label>
                            <select class="form-select" name="supplier_id">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <?php foreach ($suppliers_list as $sup): ?>
                                    <option value="<?= $sup['id']; ?>" <?= $supplier_filter == $sup['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($sup['domain']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Trạng thái'); ?></label>
                            <select class="form-select" name="status">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : ''; ?>><?= __('Chờ xử lý'); ?></option>
                                <option value="processing" <?= $status_filter == 'processing' ? 'selected' : ''; ?>><?= __('Đang xử lý'); ?></option>
                                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : ''; ?>><?= __('Hoàn thành'); ?></option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : ''; ?>><?= __('Đã hủy'); ?></option>
                                <option value="cancelled_no_refund" <?= $status_filter == 'cancelled_no_refund' ? 'selected' : ''; ?>><?= __('Hủy không hoàn tiền'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Mã giảm giá'); ?></label>
                            <input type="text" class="form-control" name="coupon_code"
                                value="<?= htmlspecialchars($coupon_code_filter); ?>"
                                placeholder="<?= __('Nhập mã giảm giá...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Có mã giảm giá'); ?></label>
                            <select class="form-select" name="has_coupon">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="1" <?= $has_coupon_filter === 1 ? 'selected' : ''; ?>><?= __('Có coupon'); ?></option>
                                <option value="0" <?= $has_coupon_filter === 0 ? 'selected' : ''; ?>><?= __('Không có coupon'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Có hoa hồng'); ?></label>
                            <select class="form-select" name="has_commission">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="1" <?= $has_commission_filter === 1 ? 'selected' : ''; ?>><?= __('Có hoa hồng'); ?></option>
                                <option value="0" <?= $has_commission_filter === 0 ? 'selected' : ''; ?>><?= __('Không có hoa hồng'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Trạng thái hết hạn'); ?></label>
                            <select class="form-select" name="expiry_status">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="expired" <?= $expiry_status_filter == 'expired' ? 'selected' : ''; ?>><?= __('Đã hết hạn'); ?></option>
                                <option value="expiring_soon" <?= $expiry_status_filter == 'expiring_soon' ? 'selected' : ''; ?>><?= __('Sắp hết hạn (≤7 ngày)'); ?></option>
                                <option value="active" <?= $expiry_status_filter == 'active' ? 'selected' : ''; ?>><?= __('Còn hạn (>7 ngày)'); ?></option>
                                <option value="lifetime" <?= $expiry_status_filter == 'lifetime' ? 'selected' : ''; ?>><?= __('Vĩnh viễn'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Đơn API'); ?></label>
                            <select class="form-select" name="is_api_order">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="1" <?= (isset($_GET['is_api_order']) && $_GET['is_api_order'] == '1') ? 'selected' : ''; ?>><?= __('Đơn từ API'); ?></option>
                                <option value="0" <?= (isset($_GET['is_api_order']) && $_GET['is_api_order'] == '0') ? 'selected' : ''; ?>><?= __('Đơn thường'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Từ ngày'); ?></label>
                            <input type="date" class="form-control" name="date_from"
                                value="<?= htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Đến ngày'); ?></label>
                            <input type="date" class="form-control" name="date_to"
                                value="<?= htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Số lượng/trang'); ?></label>
                            <select class="form-select" name="limit">
                                <option value="10" <?= $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?= $limit == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="200" <?= $limit == 200 ? 'selected' : ''; ?>>200</option>
                                <option value="500" <?= $limit == 500 ? 'selected' : ''; ?>>500</option>
                                <option value="1000" <?= $limit == 1000 ? 'selected' : ''; ?>>1000</option>
                                <option value="2000" <?= $limit == 2000 ? 'selected' : ''; ?>>2000</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa-solid fa-filter me-1"></i><?= __('Lọc'); ?>
                            </button>
                            <a href="<?= base_url_admin('product-orders') ?>" class="btn btn-secondary">
                                <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách đơn hàng -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if (count($orders_list) > 0): ?>
                            <!-- Thanh công cụ hàng loạt -->
                            <div id="bulkActionsToolbar" class="card-footer bg-light border-bottom d-none">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">
                                            <span id="selectedCount">0</span> <?= __('đơn hàng đã chọn'); ?>
                                        </span>
                                    </div>
                                    <div class="btn-list">
                                        <button type="button" id="btnBulkNote" class="btn btn-sm btn-info d-none" onclick="showBulkNoteModal()">
                                            <i class="fa-solid fa-note-sticky me-1"></i><?= __('Cập nhật ghi chú'); ?>
                                        </button>
                                        <button type="button" id="btnBulkExport" class="btn btn-sm btn-success d-none" onclick="showExportModal()">
                                            <i class="fa-solid fa-download me-1"></i><?= __('Tải về'); ?>
                                        </button>
                                        <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger d-none" onclick="bulkDeleteOrders()">
                                            <i class="fa-solid fa-trash me-1"></i><?= __('Xóa đã chọn'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover border text-nowrap">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 50px;">
                                                <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll(this)">
                                            </th>
                                            <th><?= __('Bên mua'); ?></th>
                                            <th><?= __('Đơn hàng'); ?></th>
                                            <th><?= __('Gói'); ?></th>
                                            <th><?= __('Thanh toán'); ?></th>
                                            <th class="text-center"><?= __('Trạng thái'); ?></th>
                                            <th><?= __('Ngày hết hạn'); ?></th>
                                            <th><?= __('Ghi chú nội bộ'); ?></th>
                                            <th><?= __('Ngày tạo'); ?></th>
                                            <th><?= __('Thao tác'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders_list as $order): ?>
                                            <tr id="order-<?= $order['id']; ?>" data-order-id="<?= $order['id']; ?>">
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input order-checkbox" value="<?= $order['id']; ?>" onchange="updateBulkButtons()">
                                                </td>
                                                <td>
                                                    <?php if ($order['user_id'] > 0): ?>
                                                        <?php $user = $CMSNT->get_row(" SELECT * FROM users WHERE id = " . $order['user_id']); ?>
                                                        <i class="fa-solid fa-user"></i> <?= $user['username']; ?> [ID
                                                        <?= $order['user_id']; ?>] <a class="text-primary"
                                                            href="<?= base_url_admin('user-edit&id=' . $order['user_id']); ?>"><i
                                                                class="fa-solid fa-edit"></i></a><br>
                                                        <i class="fa-solid fa-wallet"></i> <?= __('Số dư hiện tại:'); ?>
                                                        <strong
                                                            style="color:red;"><?= format_currency($user['money']); ?></strong><br>
                                                        <i class="fa-solid fa-money-bill-trend-up"></i> <?= __('Tổng nạp:'); ?>
                                                        <strong
                                                            style="color:green;"><?= format_currency($user['total_money']); ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted"><?= __('Hệ thống'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= __('Mã đơn hàng'); ?>: <strong><?= htmlspecialchars($order['trans_id']); ?></strong><br>
                                                    <?= __('Mã đơn hàng API (nếu có)'); ?>: <strong><?= htmlspecialchars($order['api_trans_id'] ?? ''); ?></strong><br>
                                                    <?php if (checkPermission($getUser['admin'], 'view_suppliers')): ?>
                                                        <?= __('Server API (nếu có)'); ?>: <i><?= htmlspecialchars($order['supplier_domain'] ?? ''); ?></i><br>
                                                    <?php endif; ?>

                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <?php
                                                        // Kiểm tra xem product và plan còn tồn tại không
                                                        $plan_exists = isset($order['plan_is_instant']); // plan_is_instant được JOIN từ product_plans, nếu có nghĩa là plan còn tồn tại
                                                        $plan_name_display = htmlspecialchars(html_entity_decode($order['plan_name'], ENT_QUOTES, 'UTF-8'));

                                                        if ($plan_exists && $order['plan_id'] > 0 && $order['product_id'] > 0):
                                                        ?>
                                                            <a href="<?= base_url_admin('product-plans&product_id=' . $order['product_id'] . '&plan_id=' . $order['plan_id']); ?>" class="text-primary">
                                                                <?= $plan_name_display; ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted" data-toggle="tooltip" data-placement="bottom" title="<?= __('Sản phẩm/gói đã bị xóa'); ?>">
                                                                <?= $plan_name_display; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php
                                                        $is_instant = isset($order['plan_is_instant']) && (int)$order['plan_is_instant'] == 1;
                                                        $has_coupon = !empty($order['coupon_code']) && isset($order['discount_amount']) && $order['discount_amount'] > 0;
                                                        $has_commission = isset($order['commission_amount']) && $order['commission_amount'] > 0;

                                                        // Kiểm tra Flash Sale
                                                        $flash_sale_purchase = $CMSNT->get_row_safe(
                                                            "SELECT fsp.*, fs.name as flash_sale_name 
                                                     FROM `flash_sale_purchases` fsp 
                                                     LEFT JOIN `flash_sales` fs ON fsp.flash_sale_id = fs.id 
                                                     WHERE fsp.order_id = ?",
                                                            [$order['id']]
                                                        );
                                                        $has_flash_sale = !empty($flash_sale_purchase);
                                                        ?>
                                                        <?php if ($is_instant): ?>
                                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" data-toggle="tooltip" data-placement="bottom" title="<?= __('Giao ngay'); ?>">
                                                                <i class="fa-solid fa-bolt"></i>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" data-toggle="tooltip" data-placement="bottom" title="<?= __('Đặt hàng'); ?>">
                                                                <i class="fa-solid fa-shopping-cart"></i>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($has_flash_sale): ?>
                                                            <span class="badge border" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3) !important;" data-toggle="tooltip" data-placement="bottom" title="<?= __('Flash Sale:'); ?> <?= htmlspecialchars($flash_sale_purchase['flash_sale_name'] ?? ''); ?> (x<?= $flash_sale_purchase['quantity']; ?>)">
                                                                <i class="fa-solid fa-bolt"></i>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($has_coupon): ?>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" data-toggle="tooltip" data-placement="bottom" title="<?= __('Có mã giảm giá:'); ?> <?= htmlspecialchars($order['coupon_code']); ?> (-<?= format_currency($order['discount_amount']); ?>)">
                                                                <i class="fa-solid fa-ticket"></i>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($has_commission): ?>
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" data-toggle="tooltip" data-placement="bottom" title="<?= __('Có hoa hồng:'); ?> <?= format_currency($order['commission_amount']); ?>">
                                                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $quantity = isset($order['quantity']) && $order['quantity'] > 0 ? $order['quantity'] : 1;

                                                    // Tính final_amount nếu chưa có trong database (backward compatibility)
                                                    $final_amount = isset($order['final_amount']) && $order['final_amount'] >= 0
                                                        ? $order['final_amount']
                                                        : (($order['sale_price'] > 0 && $order['sale_price'] < $order['total_price'])
                                                            ? ($order['sale_price'] - (isset($order['discount_amount']) ? $order['discount_amount'] : 0))
                                                            : ($order['total_price'] - (isset($order['discount_amount']) ? $order['discount_amount'] : 0)));
                                                    $final_amount = max(0, $final_amount);

                                                    $has_coupon = !empty($order['coupon_code']) && isset($order['discount_amount']) && $order['discount_amount'] > 0;

                                                    // Tính giá vốn và lợi nhuận
                                                    $cost_price = isset($order['cost_price']) ? floatval($order['cost_price']) : 0;
                                                    $profit = $final_amount - $cost_price;
                                                    ?>
                                                    <div>
                                                        <?= __('Số lượng:'); ?> <strong><?= number_format($quantity); ?></strong><br>
                                                        <?= __('Thanh toán:'); ?>
                                                        <strong class="text-primary"><?= format_currency($final_amount); ?></strong>
                                                        <?php if ($has_coupon): ?>
                                                            <i class="fa-solid fa-ticket text-info" title="<?= __('Mã giảm giá:'); ?> <?= htmlspecialchars($order['coupon_code']); ?>"></i>
                                                        <?php endif; ?>
                                                        <?php if ($final_amount < $order['total_price']): ?>
                                                            <small class="text-muted text-decoration-line-through"><?= format_currency($order['total_price']); ?></small>
                                                        <?php endif; ?>
                                                        <br>
                                                        <?php if ($cost_price > 0): ?>
                                                            <span><?= __('Giá vốn:'); ?> <?= format_currency($cost_price); ?></span>
                                                            - <span class="<?= $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                <?= __('Lãi:'); ?> <?= $profit >= 0 ? '+' : ''; ?><?= format_currency($profit); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <?= display_product_order_status($order['status']); ?>
                                                </td>
                                                <td style="min-width: 120px;">
                                                    <?php
                                                    // Tính thời gian hết hạn nếu đơn hàng đã hoàn thành và có thời hạn
                                                    $expiry_date = null;
                                                    $is_expired = false;
                                                    $days_remaining = 0;
                                                    $is_custom_expiry = false;

                                                    if (!empty($order['custom_expiry_date'])) {
                                                        // Sử dụng custom_expiry_date nếu có
                                                        $expiry_date = strtotime($order['custom_expiry_date']);
                                                        $is_custom_expiry = true;
                                                        $is_expired = time() > $expiry_date;
                                                        $days_remaining = ceil(($expiry_date - time()) / 86400);
                                                    } elseif ($order['status'] == 'completed' && !empty($order['duration_type']) && $order['duration_type'] != 'lifetime') {
                                                        // Lấy thời điểm hoàn thành (ưu tiên completed_at, fallback về updated_at)
                                                        $completed_time = !empty($order['completed_at'])
                                                            ? strtotime($order['completed_at'])
                                                            : strtotime($order['updated_at']);

                                                        // Tính thời gian hết hạn dựa trên duration_type và duration_value
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
                                                            $is_expired = time() > $expiry_date;
                                                            $days_remaining = ceil(($expiry_date - time()) / 86400);
                                                        }
                                                    }
                                                    ?>

                                                    <?php if ($expiry_date): ?>
                                                        <div>
                                                            <small class="<?= $is_expired ? 'text-danger' : 'text-muted'; ?>">
                                                                <?= date('d/m/Y', $expiry_date); ?>
                                                            </small>
                                                            <?php if ($is_expired): ?>
                                                                <br><span class="badge bg-danger-transparent" style="font-size: 10px;">
                                                                    <i class="fa-solid fa-exclamation-circle"></i> <?= __('Hết hạn'); ?>
                                                                </span>
                                                            <?php elseif ($days_remaining <= 7 && $days_remaining > 0): ?>
                                                                <br><span class="badge bg-warning-transparent" style="font-size: 10px;">
                                                                    <i class="fa-solid fa-exclamation-triangle"></i> <?= $days_remaining; ?> <?= __('ngày'); ?>
                                                                </span>
                                                            <?php elseif ($days_remaining > 0): ?>
                                                                <br><span class="badge bg-success-transparent" style="font-size: 10px;">
                                                                    <i class="fa-solid fa-check-circle"></i> <?= $days_remaining; ?> <?= __('ngày'); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php elseif ($order['status'] == 'completed' && !empty($order['duration_type']) && $order['duration_type'] == 'lifetime'): ?>
                                                        <span class="badge bg-success-transparent">
                                                            <i class="fa-solid fa-infinity"></i> <?= __('Vĩnh viễn'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="max-width: 250px;">
                                                    <div class="position-relative">
                                                        <textarea
                                                            class="form-control form-control-sm order-note-textarea"
                                                            id="note_<?= $order['id']; ?>"
                                                            data-order-id="<?= $order['id']; ?>"
                                                            data-original-value="<?= htmlspecialchars($order['note'] ?? ''); ?>"
                                                            rows="2"
                                                            style="resize: vertical; font-size: 12px; min-height: 50px;"
                                                            placeholder="<?= __('Nhập ghi chú...'); ?>"><?= htmlspecialchars($order['note'] ?? ''); ?></textarea>
                                                        <small class="text-muted position-absolute"
                                                            id="note_status_<?= $order['id']; ?>"
                                                            style="bottom: -18px; right: 2px; font-size: 10px; display: none;">
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small data-toggle="tooltip" data-placement="bottom" title="<?= timeAgo(strtotime($order['created_at'])); ?>"><?= date('d/m/Y H:i:s', strtotime($order['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-list">
                                                        <a href="<?= base_url_admin('product-order-edit&id=' . $order['id']); ?>" class="btn btn-sm btn-info">
                                                            <i class="fa-solid fa-eye me-1"></i><?= __('Xem'); ?>
                                                        </a>
                                                        <?php if ($order['status'] == 'pending'): ?>
                                                            <button onclick="cancelOrder(<?= $order['id']; ?>)" class="btn btn-sm btn-warning">
                                                                <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button onclick="deleteOrder(<?= $order['id']; ?>)" class="btn btn-sm btn-danger">
                                                            <i class="fa-solid fa-trash me-1"></i><?= __('Xóa'); ?>
                                                        </button>
                                                    </div>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <td colspan="10" class="text-start py-3">
                                                <div class="d-flex justify-content-start align-items-center gap-4 flex-wrap">
                                                    <span class="fw-semibold">
                                                        <i class="fa-solid fa-shopping-cart text-primary me-1"></i>
                                                        <?= __('Đơn hàng:'); ?>
                                                        <strong class="text-primary"><?= number_format($filter_stats['total_orders']); ?></strong>
                                                    </span>
                                                    <span class="text-muted">|</span>
                                                    <span class="fw-semibold">
                                                        <i class="fa-solid fa-money-bill-wave text-success me-1"></i>
                                                        <?= __('Doanh thu:'); ?>
                                                        <strong class="text-success"><?= format_currency($filter_stats['total_revenue']); ?></strong>
                                                    </span>
                                                    <span class="text-muted">|</span>
                                                    <span class="fw-semibold">
                                                        <i class="fa-solid fa-chart-line <?= $filter_stats['total_profit'] >= 0 ? 'text-info' : 'text-danger'; ?> me-1"></i>
                                                        <?= __('Lợi nhuận:'); ?>
                                                        <strong class="<?= $filter_stats['total_profit'] >= 0 ? 'text-info' : 'text-danger'; ?>">
                                                            <?= $filter_stats['total_profit'] >= 0 ? '+' : ''; ?><?= format_currency($filter_stats['total_profit']); ?>
                                                        </strong>
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Phân trang -->
                            <?php
                            // Tạo URL pagination với các tham số filter
                            $pagination_url = base_url_admin('product-orders');
                            $pagination_url .= '&limit=' . $limit;
                            if (!empty($search)) $pagination_url .= '&search=' . urlencode($search);
                            if ($product_filter > 0) $pagination_url .= '&product_id=' . $product_filter;
                            if ($plan_filter > 0) $pagination_url .= '&plan_id=' . $plan_filter;
                            if ($user_filter > 0) $pagination_url .= '&user_id=' . $user_filter;
                            if ($status_filter) $pagination_url .= '&status=' . $status_filter;
                            if (!empty($api_trans_id_search)) $pagination_url .= '&api_trans_id=' . urlencode($api_trans_id_search);
                            if ($supplier_filter > 0) $pagination_url .= '&supplier_id=' . $supplier_filter;
                            if (!empty($coupon_code_filter)) $pagination_url .= '&coupon_code=' . urlencode($coupon_code_filter);
                            if ($has_coupon_filter !== '') $pagination_url .= '&has_coupon=' . $has_coupon_filter;
                            if ($has_commission_filter !== '') $pagination_url .= '&has_commission=' . $has_commission_filter;
                            if ($expiry_status_filter) $pagination_url .= '&expiry_status=' . $expiry_status_filter;
                            if ($is_api_order_filter !== '') $pagination_url .= '&is_api_order=' . $is_api_order_filter;
                            if (!empty($delivery_search)) $pagination_url .= '&delivery_search=' . urlencode($delivery_search);
                            if (isset($_GET['date_from']) && !empty($_GET['date_from'])) $pagination_url .= '&date_from=' . urlencode($_GET['date_from']);
                            if (isset($_GET['date_to']) && !empty($_GET['date_to'])) $pagination_url .= '&date_to=' . urlencode($_GET['date_to']);
                            $pagination_url .= '&';

                            $urlDatatable = pagination($pagination_url, $from, $total, $limit);
                            ?>
                            <?php if ($total > $limit): ?>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-5">
                                            <p class="dataTables_info"><?= __('Showing'); ?> <?= $limit; ?> <?= __('of'); ?> <?= number_format($total); ?> <?= __('Results'); ?></p>
                                        </div>
                                        <div class="col-sm-12 col-md-7">
                                            <?= $urlDatatable; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-exclamation-circle me-2"></i><?= __('Chưa có đơn hàng nào.'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal cập nhật ghi chú hàng loạt -->
<div class="modal fade" id="bulkNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= __('Cập nhật ghi chú đơn hàng'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?= __('Nhập ghi chú mới cho các đơn hàng đã chọn:'); ?></p>
                <textarea class="form-control" id="bulkNoteTextarea" rows="5" placeholder="<?= __('Nhập ghi chú...'); ?>"></textarea>
                <small class="text-muted d-block mt-2">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    <?= __('Ghi chú này sẽ được áp dụng cho tất cả các đơn hàng đã chọn.'); ?>
                </small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Hủy'); ?></button>
                <button type="button" class="btn btn-primary" onclick="confirmBulkNote()"><?= __('Xác nhận'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Export đơn hàng -->
<div class="modal fade" id="exportOrdersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-download me-2"></i><?= __('Xuất đơn hàng'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium"><?= __('Loại file'); ?></label>
                    <select class="form-select" id="exportFileType">
                        <option value="txt">TXT (Tab-separated)</option>
                        <option value="csv">CSV (Comma-separated)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium"><?= __('Chọn và sắp xếp cột'); ?></label>
                    <small class="text-muted d-block mb-2">
                        <i class="fa-solid fa-grip-vertical me-1"></i><?= __('Kéo thả để sắp xếp thứ tự cột'); ?>
                    </small>
                    <ul class="list-group" id="exportColumnsList">
                        <li class="list-group-item d-flex align-items-center" data-column="trans_id">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="trans_id" checked>
                            <span><?= __('Mã đơn hàng'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="api_trans_id">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="api_trans_id">
                            <span><?= __('Mã đơn API'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="username">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="username" checked>
                            <span><?= __('Username'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="product_name">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="product_name" checked>
                            <span><?= __('Sản phẩm'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="plan_name">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="plan_name" checked>
                            <span><?= __('Gói'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="quantity">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="quantity" checked>
                            <span><?= __('Số lượng'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="total_price">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="total_price">
                            <span><?= __('Giá gốc'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="discount_amount">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="discount_amount">
                            <span><?= __('Giảm giá'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="final_amount">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="final_amount" checked>
                            <span><?= __('Thanh toán'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="status">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="status" checked>
                            <span><?= __('Trạng thái'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="created_at">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="created_at" checked>
                            <span><?= __('Ngày tạo'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="delivery_content">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="delivery_content">
                            <span><?= __('Nội dung giao'); ?></span>
                        </li>
                        <li class="list-group-item d-flex align-items-center" data-column="note">
                            <i class="fa-solid fa-grip-vertical me-3 text-muted cursor-move"></i>
                            <input type="checkbox" class="form-check-input me-2 export-col-checkbox" value="note">
                            <span><?= __('Ghi chú'); ?></span>
                        </li>
                    </ul>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllExportColumns(true)">
                        <i class="fa-solid fa-check-double me-1"></i><?= __('Chọn tất cả'); ?>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllExportColumns(false)">
                        <i class="fa-solid fa-times me-1"></i><?= __('Bỏ chọn tất cả'); ?>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Hủy'); ?></button>
                <button type="button" class="btn btn-success" id="confirmExportBtn" onclick="confirmExportOrders()">
                    <i class="fa-solid fa-download me-1"></i><?= __('Tải về'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #exportColumnsList .cursor-move {
        cursor: move;
    }

    #exportColumnsList .list-group-item {
        user-select: none;
    }

    #exportColumnsList .list-group-item.sortable-ghost {
        opacity: 0.4;
        background-color: #e3f2fd;
    }
</style>
<?php
require_once(__DIR__ . '/footer.php');
?>

<!-- Sortable.js for drag-drop column ordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
    // Load danh sách gói khi chọn sản phẩm
    function loadPlans(productId, callback) {
        var planSelect = document.getElementById('filter_plan_id');
        if (!planSelect) {
            if (callback) callback();
            return;
        }

        planSelect.innerHTML = '<option value=""><?= __("Đang tải..."); ?></option>';
        planSelect.disabled = true;

        if (!productId || productId == '') {
            planSelect.innerHTML = '<option value=""><?= __("Tất cả gói"); ?></option>';
            planSelect.disabled = false;
            if (callback) callback();
            return;
        }

        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/view.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'getProductPlans',
                product_id: productId
            },
            success: function(result) {
                planSelect.innerHTML = '<option value=""><?= __("Tất cả gói"); ?></option>';

                if (result.status == 'success' && result.data && result.data.length > 0) {
                    result.data.forEach(function(plan) {
                        var option = document.createElement('option');
                        option.value = plan.id;
                        option.textContent = plan.name;
                        planSelect.appendChild(option);
                    });
                } else {
                    var option = document.createElement('option');
                    option.value = '';
                    option.textContent = '<?= __("Không có gói nào"); ?>';
                    planSelect.appendChild(option);
                }
                planSelect.disabled = false;
                if (callback) callback();
            },
            error: function(xhr, status, error) {
                console.error('Lỗi khi tải danh sách gói:', error);
                planSelect.innerHTML = '<option value=""><?= __("Lỗi khi tải danh sách gói"); ?></option>';
                planSelect.disabled = false;
                if (callback) callback();
            }
        });
    }

    // Load plans khi trang load nếu đã có product_id được chọn hoặc plan_id trong URL
    $(document).ready(function() {
        var productId = document.getElementById('filter_product_id')?.value;
        var planId = new URLSearchParams(window.location.search).get('plan_id');

        // Nếu có plan_id nhưng chưa có product_id, cần load plans trước
        if (planId && (!productId || productId == '')) {
            // Lấy product_id từ plan_id bằng AJAX
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/view.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'getProductPlan',
                    id: planId
                },
                success: function(result) {
                    if (result.status == 'success' && result.data) {
                        var productIdFromPlan = result.data.product_id;
                        // Set product_id trong dropdown
                        $('#filter_product_id').val(productIdFromPlan);
                        // Load plans
                        loadPlans(productIdFromPlan, function() {
                            // Sau khi load xong, chọn plan_id
                            $('#filter_plan_id').val(planId);
                        });
                    }
                },
                error: function() {
                    console.error('Lỗi khi lấy thông tin gói');
                }
            });
        } else if (productId && productId != '') {
            // Nếu đã có product_id, load plans và chọn plan_id nếu có
            loadPlans(productId, function() {
                if (planId) {
                    $('#filter_plan_id').val(planId);
                }
            });
        }
    });

    // Cập nhật trạng thái đơn hàng
    function updateOrderStatus(orderId, currentStatus) {
        window.location.href = '<?= base_url_admin("product-order-edit"); ?>&id=' + orderId;
    }

    // Hủy đơn hàng
    function cancelOrder(orderId) {
        Swal.fire({
            title: "<?= __('Cảnh báo'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn hủy đơn hàng này không? Hệ thống sẽ tự động hoàn tiền cho khách hàng.'); ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Hủy'); ?>",
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
                    type: 'POST',
                    dataType: "JSON",
                    data: {
                        action: 'cancelProductOrder',
                        id: orderId
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                    }
                });
            }
        });
    }

    // Xóa đơn hàng
    function deleteOrder(orderId) {
        Swal.fire({
            title: "<?= __('Cảnh báo'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn xóa đơn hàng này không? Hành động này không thể hoàn tác.'); ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "<?= __('Xóa'); ?>",
            cancelButtonText: "<?= __('Hủy'); ?>",
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                    type: 'POST',
                    dataType: "JSON",
                    data: {
                        action: 'deleteProductOrder',
                        id: orderId
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                    }
                });
            }
        });
    }

    // Chọn tất cả / Bỏ chọn tất cả

    function toggleSelectAll(checkbox) {
        $('.order-checkbox').prop('checked', checkbox.checked);
        updateBulkButtons();
    }

    // Cập nhật hiển thị nút bulk action
    function updateBulkButtons() {
        var selectedCount = $('.order-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);

        if (selectedCount > 0) {
            $('#bulkActionsToolbar').removeClass('d-none');
            $('#btnBulkDelete, #btnBulkNote, #btnBulkExport').removeClass('d-none');
        } else {
            $('#bulkActionsToolbar').addClass('d-none');
            $('#btnBulkDelete, #btnBulkNote, #btnBulkExport').addClass('d-none');
        }

        // Cập nhật trạng thái checkbox "Chọn tất cả"
        var totalCheckboxes = $('.order-checkbox').length;
        $('#selectAll').prop('checked', selectedCount === totalCheckboxes && totalCheckboxes > 0);
    }

    // Lấy danh sách ID đã chọn
    function getSelectedOrderIds() {
        var selectedIds = [];
        $('.order-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    // Hiển thị modal cập nhật ghi chú
    function showBulkNoteModal() {
        var selectedIds = getSelectedOrderIds();
        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng"); ?>', 'error');
            return;
        }
        $('#bulkNoteTextarea').val('');
        var modal = new bootstrap.Modal(document.getElementById('bulkNoteModal'));
        modal.show();
    }

    // Xác nhận cập nhật ghi chú hàng loạt
    function confirmBulkNote() {
        var selectedIds = getSelectedOrderIds();
        var note = $('#bulkNoteTextarea').val();

        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng"); ?>', 'error');
            return;
        }

        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'bulkUpdateProductOrderNote',
                ids: selectedIds,
                note: note
            },
            beforeSend: function() {
                $('#btnBulkNote').prop('disabled', true);
            },
            success: function(result) {
                $('#btnBulkNote').prop('disabled', false);
                if (result.status == 'success') {
                    showMessage(result.msg, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('bulkNoteModal')).hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $('#btnBulkNote').prop('disabled', false);
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }

    // Xóa hàng loạt đơn hàng
    function bulkDeleteOrders() {
        var selectedIds = getSelectedOrderIds();

        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng để xóa"); ?>', 'error');
            return;
        }

        Swal.fire({
            title: "<?= __('Cảnh báo'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn xóa'); ?> " + selectedIds.length + " <?= __('đơn hàng đã chọn không? Hành động này không thể hoàn tác.'); ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Hủy'); ?>",
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                    type: 'POST',
                    dataType: "JSON",
                    data: {
                        action: 'bulkDeleteProductOrders',
                        ids: selectedIds
                    },
                    beforeSend: function() {
                        $('#btnBulkDelete').prop('disabled', true);
                        $('#btnBulkDelete').html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang xóa..."); ?>');
                    },
                    success: function(result) {
                        $('#btnBulkDelete').prop('disabled', false);
                        $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa đã chọn"); ?>');

                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        $('#btnBulkDelete').prop('disabled', false);
                        $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa đã chọn"); ?>');
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                    }
                });
            }
        });
    }

    // Lưu ghi chú đơn hàng
    var saveNoteTimeouts = {};

    function saveOrderNote(orderId, showSuccessMessage = false) {
        var noteTextarea = document.getElementById('note_' + orderId);
        var statusElement = document.getElementById('note_status_' + orderId);
        var note = noteTextarea.value.trim();
        var originalValue = $(noteTextarea).data('original-value') || '';

        // Không lưu nếu giá trị không thay đổi
        if (note === originalValue) {
            return;
        }

        // Hiển thị trạng thái "Đang lưu..."
        if (statusElement) {
            statusElement.style.display = 'block';
            statusElement.className = 'text-muted position-absolute';
            statusElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang lưu..."); ?>';
        }

        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/update.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'updateProductOrderNote',
                id: orderId,
                note: note
            },
            success: function(result) {
                if (result.status == 'success') {
                    // Cập nhật giá trị gốc
                    $(noteTextarea).data('original-value', note);

                    // Hiển thị trạng thái "Đã lưu"
                    if (statusElement) {
                        statusElement.className = 'text-success position-absolute';
                        statusElement.innerHTML = '<i class="fa-solid fa-check me-1"></i><?= __("Đã lưu"); ?>';

                        // Ẩn sau 2 giây
                        setTimeout(function() {
                            statusElement.style.display = 'none';
                        }, 2000);
                    }

                    // Hiệu ứng flash border
                    $(noteTextarea).addClass('border-success');
                    setTimeout(function() {
                        $(noteTextarea).removeClass('border-success');
                    }, 1000);

                    // Hiển thị thông báo nếu cần
                    if (showSuccessMessage) {
                        showMessage(result.msg, 'success');
                    }
                } else {
                    if (statusElement) {
                        statusElement.className = 'text-danger position-absolute';
                        statusElement.innerHTML = '<i class="fa-solid fa-times me-1"></i><?= __("Lỗi"); ?>';
                        setTimeout(function() {
                            statusElement.style.display = 'none';
                        }, 3000);
                    }
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                if (statusElement) {
                    statusElement.className = 'text-danger position-absolute';
                    statusElement.innerHTML = '<i class="fa-solid fa-times me-1"></i><?= __("Lỗi"); ?>';
                    setTimeout(function() {
                        statusElement.style.display = 'none';
                    }, 3000);
                }
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }

    // Toggle filter form
    function toggleFilterForm() {
        var filterBody = document.getElementById('filterFormBody');
        var filterIcon = document.getElementById('filterIcon');

        if (filterBody.style.display === 'none') {
            filterBody.style.display = 'block';
            filterIcon.className = 'fa-solid fa-chevron-up';
            localStorage.setItem('product_orders_filter_expanded', 'true');
        } else {
            filterBody.style.display = 'none';
            filterIcon.className = 'fa-solid fa-chevron-down';
            localStorage.setItem('product_orders_filter_expanded', 'false');
        }
    }

    // Auto-save khi blur hoặc sau 2 giây không typing
    $(document).ready(function() {
        // Khôi phục trạng thái filter form từ localStorage
        var isFilterExpanded = localStorage.getItem('product_orders_filter_expanded');
        <?php
        // Tự động mở nếu có filter đang active
        $has_active_filter = !empty($search) || !empty($api_trans_id_search) || !empty($delivery_search) || $product_filter > 0 || $plan_filter > 0 || $user_filter > 0
            || $supplier_filter > 0 || $status_filter || !empty($coupon_code_filter) || $has_coupon_filter !== ''
            || $has_commission_filter !== '' || $expiry_status_filter || $is_api_order_filter !== '' || !empty($date_from) || !empty($date_to);
        ?>
        <?php if ($has_active_filter): ?>
            // Có filter đang active, tự động mở
            document.getElementById('filterFormBody').style.display = 'block';
            document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
        <?php else: ?>
            // Không có filter, kiểm tra localStorage
            if (isFilterExpanded === 'true') {
                document.getElementById('filterFormBody').style.display = 'block';
                document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
            }
        <?php endif; ?>

        $('.order-note-textarea').on('input', function() {
            var orderId = $(this).data('order-id');

            // Clear timeout cũ
            if (saveNoteTimeouts[orderId]) {
                clearTimeout(saveNoteTimeouts[orderId]);
            }

            // Set timeout mới - tự động lưu sau 2 giây không typing
            saveNoteTimeouts[orderId] = setTimeout(function() {
                saveOrderNote(orderId, false);
            }, 2000);
        });

        // Lưu khi blur (rời khỏi textarea)
        $('.order-note-textarea').on('blur', function() {
            var orderId = $(this).data('order-id');

            // Clear timeout nếu có
            if (saveNoteTimeouts[orderId]) {
                clearTimeout(saveNoteTimeouts[orderId]);
            }

            // Lưu ngay lập tức
            saveOrderNote(orderId, false);
        });

        // Hỗ trợ phím tắt Ctrl+Enter để lưu ngay
        $('.order-note-textarea').on('keydown', function(e) {
            if (e.ctrlKey && e.keyCode === 13) {
                var orderId = $(this).data('order-id');

                // Clear timeout nếu có
                if (saveNoteTimeouts[orderId]) {
                    clearTimeout(saveNoteTimeouts[orderId]);
                }

                saveOrderNote(orderId, true);
            }
        });

        // Khởi tạo Sortable cho danh sách cột export
        if (typeof Sortable !== 'undefined' && document.getElementById('exportColumnsList')) {
            new Sortable(document.getElementById('exportColumnsList'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.fa-grip-vertical'
            });
        }
    });

    // Hiển thị modal export
    function showExportModal() {
        var selectedIds = getSelectedOrderIds();
        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng"); ?>', 'error');
            return;
        }
        var modalEl = document.getElementById('exportOrdersModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            // Fallback: dùng attribute để trigger
            $(modalEl).addClass('show').css('display', 'block');
            $('body').addClass('modal-open').append('<div class="modal-backdrop fade show"></div>');
        }
    }

    // Chọn/bỏ chọn tất cả cột
    function toggleAllExportColumns(checked) {
        $('.export-col-checkbox').prop('checked', checked);
    }

    // Xác nhận export đơn hàng
    function confirmExportOrders() {
        var selectedIds = getSelectedOrderIds();
        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một đơn hàng"); ?>', 'error');
            return;
        }

        // Lấy loại file
        var fileType = $('#exportFileType').val();

        // Lấy danh sách cột được chọn theo thứ tự
        var columns = [];
        $('#exportColumnsList li').each(function() {
            var $checkbox = $(this).find('.export-col-checkbox');
            if ($checkbox.prop('checked')) {
                columns.push($checkbox.val());
            }
        });

        if (columns.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một cột để xuất"); ?>', 'error');
            return;
        }

        // Gọi AJAX để export
        $('#confirmExportBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang tải..."); ?>');

        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'exportProductOrders',
                ids: selectedIds,
                file_type: fileType,
                columns: columns
            },
            success: function(result) {
                $('#confirmExportBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Tải về"); ?>');

                if (result.status == 'success') {
                    // Tạo file và download
                    var content = result.data.content;
                    var filename = result.data.filename;
                    var mimeType = fileType === 'csv' ? 'text/csv;charset=utf-8;' : 'text/plain;charset=utf-8;';

                    // Thêm BOM cho UTF-8
                    var bom = '\uFEFF';
                    var blob = new Blob([bom + content], {
                        type: mimeType
                    });
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);

                    showMessage(result.msg, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('exportOrdersModal')).hide();
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $('#confirmExportBtn').prop('disabled', false).html('<i class="fa-solid fa-download me-1"></i><?= __("Tải về"); ?>');
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }
</script>

<!-- Modal dọn dẹp đơn hàng -->
<div class="modal fade" id="cleanupProductOrdersModal" tabindex="-1" aria-labelledby="cleanupProductOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cleanupProductOrdersModalLabel">
                    <i class="fa-solid fa-trash text-danger me-2"></i><?= __('Dọn dẹp đơn hàng'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-0">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <?= __('Lưu ý: Thao tác này sẽ xóa vĩnh viễn tất cả đơn hàng cũ. Không thể hoàn tác.'); ?>
                </div>
                <div class="mb-3">
                    <label for="cleanupDaysProductOrders" class="form-label fw-medium"><?= __('Xóa đơn hàng cũ hơn'); ?></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="cleanupDaysProductOrders" value="30" min="1" max="365" placeholder="30">
                        <span class="input-group-text"><?= __('ngày'); ?></span>
                    </div>
                    <div class="form-text">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <?= __('Xóa tất cả đơn hàng cũ hơn số ngày chỉ định, không phân biệt trạng thái.'); ?>
                    </div>
                </div>
                <div id="cleanupPreviewProductOrders" class="d-none">
                    <div class="alert alert-info-transparent border mb-0">
                        <i class="fa-solid fa-file-list me-2"></i>
                        <span id="cleanupPreviewTextProductOrders"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                </button>
                <button type="button" class="btn btn-danger" id="confirmCleanupBtnProductOrders">
                    <i class="fa-solid fa-trash me-1"></i><?= __('Xóa đơn hàng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tài liệu API -->
<div class="modal fade" id="apiDocumentationModal" tabindex="-1" aria-labelledby="apiDocumentationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="apiDocumentationModalLabel">
                    <i class="fa-solid fa-book me-2"></i><?= __('Tài liệu API Đơn hàng'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Thông tin xác thực -->
                <div class="alert alert-info border-0 mb-4">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-key me-2"></i><?= __('Xác thực API'); ?></h6>
                    <p class="mb-2"><?= __('Tất cả các API đều yêu cầu xác thực bằng tham số <code>api_key</code> từ bảng users:'); ?></p>
                    <code class="d-block bg-dark text-light p-2 rounded">
                        api_key=YOUR_API_KEY
                    </code>
                    <small class="text-muted mt-2 d-block">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <?= __('API key lấy từ: Admin → Users → User Edit → cột api_key'); ?>
                    </small>
                </div>

                <hr class="my-4">

                <!-- API 1: Lấy danh sách đơn hàng đang chờ/đang xử lý -->
                <div class="mb-4">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="fa-solid fa-list me-2"></i><?= __('1. Lấy danh sách đơn hàng (Chờ xử lý / Đang xử lý)'); ?>
                    </h5>
                    <p class="text-muted mb-3"><?= __('Lấy danh sách đơn hàng theo trạng thái pending hoặc processing.'); ?></p>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Endpoint'); ?></strong>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success">GET/POST</span>
                                <code class="flex-grow-1"><?= BASE_URL('api/v1/admin/orders/list.php'); ?></code>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?= BASE_URL('api/v1/admin/orders/list.php'); ?>')">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Parameters'); ?></strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= __('Tham số'); ?></th>
                                        <th><?= __('Kiểu'); ?></th>
                                        <th><?= __('Bắt buộc'); ?></th>
                                        <th><?= __('Mô tả'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>api_key</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                        <td><?= __('API key từ cột api_key trong bảng users'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>status</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-secondary"><?= __('Không'); ?></span></td>
                                        <td><?= __('Trạng thái đơn hàng:'); ?> <code>pending</code>, <code>processing</code>, <code>completed</code>, <code>cancelled</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>page</code></td>
                                        <td>int</td>
                                        <td><span class="badge bg-secondary"><?= __('Không'); ?></span></td>
                                        <td><?= __('Số trang (mặc định: 1)'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>limit</code></td>
                                        <td>int</td>
                                        <td><span class="badge bg-secondary"><?= __('Không'); ?></span></td>
                                        <td><?= __('Số lượng mỗi trang (mặc định: 10, tối đa: 100)'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Ví dụ GET Request'); ?></strong>
                        </div>
                        <div class="card-body bg-dark">
                            <code class="text-success"><?= BASE_URL('api/v1/admin/orders/list.php'); ?>?api_key=YOUR_API_KEY&status=pending&page=1&limit=20</code>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Ví dụ cURL (POST)'); ?></strong>
                        </div>
                        <div class="card-body bg-dark">
                            <pre class="text-light mb-0" style="white-space: pre-wrap;"><code>curl -X POST "<?= BASE_URL('api/v1/orders/list.php'); ?>" \
  -d "api_key=YOUR_API_KEY" \
  -d "status=pending" \
  -d "page=1" \
  -d "limit=20"</code></pre>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <strong><?= __('Response mẫu'); ?></strong>
                        </div>
                        <div class="card-body bg-dark">
                            <pre class="text-light mb-0" style="white-space: pre-wrap;"><code>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"orders"</span>: [
      {
        <span class="json-key">"id"</span>: <span class="json-number">1234</span>,
        <span class="json-key">"trans_id"</span>: <span class="json-string">"ORD-1705678901-ABC123"</span>,
        <span class="json-key">"product"</span>: {
          <span class="json-key">"id"</span>: <span class="json-number">10</span>,
          <span class="json-key">"name"</span>: <span class="json-string">"Tên sản phẩm"</span>
        },
        <span class="json-key">"plan"</span>: {
          <span class="json-key">"id"</span>: <span class="json-number">5</span>,
          <span class="json-key">"name"</span>: <span class="json-string">"Tên gói"</span>
        },
        <span class="json-key">"quantity"</span>: <span class="json-number">2</span>,
        <span class="json-key">"total_price"</span>: <span class="json-number">1500000</span>,
        <span class="json-key">"final_amount"</span>: <span class="json-number">1350000</span>,
        <span class="json-key">"status"</span>: <span class="json-string">"pending"</span>,
        <span class="json-key">"created_at"</span>: <span class="json-string">"2026-01-19 10:30:00"</span>
      }
    ],
    <span class="json-key">"pagination"</span>: {
      <span class="json-key">"current_page"</span>: <span class="json-number">1</span>,
      <span class="json-key">"per_page"</span>: <span class="json-number">20</span>,
      <span class="json-key">"total"</span>: <span class="json-number">45</span>,
      <span class="json-key">"total_pages"</span>: <span class="json-number">3</span>
    }
  }
}</code></pre>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- API 2: Cập nhật trạng thái đơn hàng -->
                <div class="mb-4">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="fa-solid fa-pen-to-square me-2"></i><?= __('2. Cập nhật trạng thái đơn hàng'); ?>
                    </h5>
                    <p class="text-muted mb-3"><?= __('Cập nhật trạng thái và nội dung giao hàng cho đơn hàng.'); ?></p>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Endpoint'); ?></strong>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark">GET/POST</span>
                                <code class="flex-grow-1"><?= BASE_URL('api/v1/admin/orders/update-status.php'); ?></code>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('<?= BASE_URL('api/v1/admin/orders/update-status.php'); ?>')">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Parameters'); ?></strong>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= __('Tham số'); ?></th>
                                        <th><?= __('Kiểu'); ?></th>
                                        <th><?= __('Bắt buộc'); ?></th>
                                        <th><?= __('Mô tả'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>api_key</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                        <td><?= __('API key từ cột api_key trong bảng users'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>trans_id</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                        <td><?= __('Mã đơn hàng cần cập nhật'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>status</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                        <td><?= __('Trạng thái mới:'); ?> <code>processing</code>, <code>completed</code>, <code>cancelled</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>delivery_content</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-secondary"><?= __('Không'); ?></span></td>
                                        <td><?= __('Nội dung giao hàng (khi status = completed)'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>note</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-secondary"><?= __('Không'); ?></span></td>
                                        <td><?= __('Ghi chú nội bộ'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Ví dụ GET Request'); ?></strong>
                        </div>
                        <div class="card-body bg-dark">
                            <code class="text-success"><?= BASE_URL('api/v1/orders/update-status.php'); ?>?api_key=YOUR_API_KEY&trans_id=ORD-123456&status=completed&delivery_content=Account:pass123</code>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong><?= __('Ví dụ cURL (POST)'); ?></strong>
                        </div>
                        <div class="card-body bg-dark">
                            <pre class="text-light mb-0" style="white-space: pre-wrap;"><code>curl -X POST "<?= BASE_URL('api/v1/orders/update-status.php'); ?>" \
  -d "api_key=YOUR_API_KEY" \
  -d "trans_id=ORD-1705678901-ABC123" \
  -d "status=completed" \
  -d "delivery_content=Account: user@mail.com | Pass: abc123"</code></pre>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <strong><?= __('Response mẫu (Thành công)'); ?></strong>
                        </div>
                        <div class="card-body bg-dark">
                            <pre class="text-light mb-0" style="white-space: pre-wrap;"><code>{
  <span class="json-key">"success"</span>: <span class="json-boolean">true</span>,
  <span class="json-key">"message"</span>: <span class="json-string">"Cập nhật trạng thái đơn hàng thành công"</span>,
  <span class="json-key">"data"</span>: {
    <span class="json-key">"trans_id"</span>: <span class="json-string">"ORD-1705678901-ABC123"</span>,
    <span class="json-key">"old_status"</span>: <span class="json-string">"pending"</span>,
    <span class="json-key">"new_status"</span>: <span class="json-string">"completed"</span>,
    <span class="json-key">"updated_at"</span>: <span class="json-string">"2026-01-21 14:30:00"</span>
  }
}</code></pre>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Mã lỗi thường gặp -->
                <div class="mb-3">
                    <h5 class="fw-bold text-primary mb-3">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i><?= __('Mã lỗi thường gặp'); ?>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th><?= __('Mã lỗi'); ?></th>
                                    <th><?= __('HTTP Status'); ?></th>
                                    <th><?= __('Mô tả'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>API_KEY_REQUIRED</code></td>
                                    <td>401</td>
                                    <td><?= __('Thiếu tham số api_key'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>INVALID_API_KEY</code></td>
                                    <td>401</td>
                                    <td><?= __('API Key không hợp lệ hoặc tài khoản đã bị khóa'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>PERMISSION_DENIED</code></td>
                                    <td>403</td>
                                    <td><?= __('Không có quyền thực hiện thao tác này'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>ORDER_NOT_FOUND</code></td>
                                    <td>404</td>
                                    <td><?= __('Không tìm thấy đơn hàng'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>INVALID_STATUS</code></td>
                                    <td>400</td>
                                    <td><?= __('Trạng thái không hợp lệ'); ?></td>
                                </tr>
                                <tr>
                                    <td><code>ORDER_ALREADY_FINALIZED</code></td>
                                    <td>400</td>
                                    <td><?= __('Không thể cập nhật đơn hàng đã hoàn thành/hủy'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Lưu ý quan trọng -->
                <div class="alert alert-warning mb-0">
                    <h6 class="alert-heading fw-bold"><i class="fa-solid fa-exclamation-triangle me-2"></i><?= __('Lưu ý quan trọng'); ?></h6>
                    <ul class="mb-0">
                        <li><?= __('User phải có quyền <code>edit_orders_product</code> trong admin role'); ?></li>
                        <li><?= __('Với GET request, các giá trị đặc biệt phải được URL encode'); ?></li>
                        <li><?= __('API key lấy từ: Admin → Users → User Edit → cột api_key'); ?></li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i><?= __('Đóng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* JSON Syntax Highlighting for API Documentation Modal */
    #apiDocumentationModal .json-key {
        color: #9cdcfe;
    }

    #apiDocumentationModal .json-string {
        color: #ce9178;
    }

    #apiDocumentationModal .json-number {
        color: #b5cea8;
    }

    #apiDocumentationModal .json-boolean {
        color: #569cd6;
    }
</style>

<script>
    $(document).ready(function() {
        var cleanupModal = document.getElementById('cleanupProductOrdersModal');
        var $cleanupDays = $('#cleanupDaysProductOrders');
        var $cleanupPreview = $('#cleanupPreviewProductOrders');
        var $cleanupPreviewText = $('#cleanupPreviewTextProductOrders');
        var $confirmBtn = $('#confirmCleanupBtnProductOrders');
        var previewTimeout = null;

        function updatePreview() {
            var days = parseInt($cleanupDays.val()) || 0;
            if (days < 1) {
                $cleanupPreview.addClass('d-none');
                return;
            }
            $.ajax({
                url: '<?= BASE_URL('ajaxs/admin/view.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'previewCleanupProductOrders',
                    days: days
                },
                success: function(resp) {
                    if (resp.status === 'success') {
                        $cleanupPreviewText.text('<?= __('Sẽ xóa'); ?> ' + resp.count + ' <?= __('đơn hàng'); ?>');
                        $cleanupPreview.removeClass('d-none');
                    } else {
                        $cleanupPreview.addClass('d-none');
                    }
                }
            });
        }

        $cleanupDays.on('input', function() {
            clearTimeout(previewTimeout);
            previewTimeout = setTimeout(updatePreview, 500);
        });

        if (cleanupModal) {
            cleanupModal.addEventListener('shown.bs.modal', function() {
                $cleanupDays.val(30).focus();
                updatePreview();
            });
            cleanupModal.addEventListener('hidden.bs.modal', function() {
                $cleanupPreview.addClass('d-none');
                $confirmBtn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __('Xóa đơn hàng'); ?>');
            });
        }

        $confirmBtn.on('click', function() {
            var days = parseInt($cleanupDays.val()) || 0;
            if (days < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: '<?= __('Cảnh báo'); ?>',
                    text: '<?= __('Vui lòng nhập số ngày hợp lệ'); ?>'
                });
                return;
            }
            Swal.fire({
                icon: 'warning',
                title: '<?= __('Xác nhận xóa'); ?>',
                text: '<?= __('Bạn có chắc chắn muốn xóa tất cả đơn hàng cũ hơn'); ?> ' + days + ' <?= __('ngày?'); ?>',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<?= __('Xóa'); ?>',
                cancelButtonText: '<?= __('Hủy'); ?>'
            }).then(function(result) {
                if (result.isConfirmed) {
                    $confirmBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span><?= __('Đang xóa...'); ?>');
                    $.ajax({
                        url: '<?= BASE_URL('ajaxs/admin/remove.php'); ?>',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'cleanupProductOrders',
                            days: days
                        },
                        success: function(resp) {
                            if (resp.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '<?= __('Thành công'); ?>',
                                    text: resp.msg,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?= __('Lỗi'); ?>',
                                    text: resp.msg
                                });
                                $confirmBtn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __('Xóa đơn hàng'); ?>');
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: '<?= __('Lỗi'); ?>',
                                text: '<?= __('Không thể kết nối đến server'); ?>'
                            });
                            $confirmBtn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __('Xóa đơn hàng'); ?>');
                        }
                    });
                }
            });
        });
    });
</script>

<!-- Select2 JS Library -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Khởi tạo Select2 cho filter dropdown
    $(document).ready(function() {
        // Select2 cho sản phẩm
        $('#filter_product_id').select2({
            placeholder: '<?= __("Tìm kiếm sản phẩm..."); ?>',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return '<?= __("Không tìm thấy kết quả"); ?>';
                },
                searching: function() {
                    return '<?= __("Đang tìm..."); ?>';
                }
            }
        });

        // Select2 cho gói sản phẩm
        $('#filter_plan_id').select2({
            placeholder: '<?= __("Tìm kiếm gói..."); ?>',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return '<?= __("Không tìm thấy kết quả"); ?>';
                },
                searching: function() {
                    return '<?= __("Đang tìm..."); ?>';
                }
            }
        });
    });
</script>