<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . '/../../models/is_admin.php');



if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}

// Kiểm tra CSRF token cho tất cả request
checkCSRFAjax();

// Forward supplier actions to dedicated handler
$supplierActions = ['testSupplierConnection', 'testSupplierConnectionNew', 'checkSupplierBalance', 'getSupplierProductDetail'];
if (in_array($_POST['action'], $supplierActions)) {
    require_once(__DIR__ . '/suppliers.php');
    exit;
}

// ==================== PREVIEW CLEANUP HANDLERS ====================

// Preview số lượng logs sẽ bị xóa
if ($_POST['action'] == 'previewCleanupLogs') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `logs` WHERE `createdate` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng transactions sẽ bị xóa
if ($_POST['action'] == 'previewCleanupTransactions') {
    if (checkPermission($getUser['admin'], 'edit_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `dongtien` WHERE `thoigian` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng bot telegram logs sẽ bị xóa
if ($_POST['action'] == 'previewCleanupBotTelegramLogs') {
    if (checkPermission($getUser['admin'], 'edit_bot_telegram_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `bot_telegram_logs` WHERE `created_at` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng blocked IP sẽ bị xóa
if ($_POST['action'] == 'previewCleanupBlockIP') {
    if (checkPermission($getUser['admin'], 'edit_block_ip') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `block_ip` WHERE `create_gettime` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng tickets sẽ bị xóa (chỉ xóa tickets đã đóng)
if ($_POST['action'] == 'previewCleanupTickets') {
    if (checkPermission($getUser['admin'], 'edit_ticket') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `support_tickets` WHERE `status` = 'closed' AND `created_at` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng đơn hàng sẽ bị xóa
if ($_POST['action'] == 'previewCleanupProductOrders') {
    if (checkPermission($getUser['admin'], 'edit_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `product_orders` WHERE `created_at` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng kho hàng đã bán sẽ bị xóa
if ($_POST['action'] == 'previewCleanupProductStock') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    // Chỉ đếm các kho hàng đã bán (status = 0)
    $count = $CMSNT->num_rows_safe("SELECT id FROM `product_stock` WHERE `status` = 0 AND `updated_at` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng Telegram Queue sẽ bị xóa
if ($_POST['action'] == 'previewCleanupTelegramQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `telegram_queue` WHERE `created_at` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview số lượng Email Queue sẽ bị xóa
if ($_POST['action'] == 'previewCleanupEmailQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `email_queue` WHERE `created_at` < ?", [$cutoff_date]);
    die(json_encode(['status' => 'success', 'count' => intval($count), 'cutoff_date' => $cutoff_date]));
}

// Preview chi tiết sản phẩm sẽ bị xóa hàng loạt
if ($_POST['action'] == 'previewBulkDeleteProducts') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có sản phẩm nào được chọn')]));
    }

    // Sanitize IDs
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function ($id) {
        return $id > 0;
    });

    if (empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('ID sản phẩm không hợp lệ')]));
    }

    // Build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Get products info
    $products = $CMSNT->get_list_safe("SELECT `id`, `name`, `image` FROM `products` WHERE `id` IN ($placeholders)", $ids);

    // Get total plans count
    $total_plans = $CMSNT->num_rows_safe("SELECT `id` FROM `product_plans` WHERE `product_id` IN ($placeholders)", $ids);

    // Build products array with plans count
    $products_data = [];
    foreach ($products as $product) {
        $plans_count = $CMSNT->num_rows_safe("SELECT `id` FROM `product_plans` WHERE `product_id` = ?", [$product['id']]);
        $products_data[] = [
            'id' => $product['id'],
            'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
            'image' => !empty($product['image']) ? BASE_URL($product['image']) : null,
            'plans_count' => intval($plans_count)
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => [
            'products' => $products_data,
            'total_plans' => intval($total_plans)
        ]
    ]));
}

// Preview chi tiết gói sản phẩm sẽ bị xóa hàng loạt
if ($_POST['action'] == 'previewBulkDeletePlans') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có gói nào được chọn')]));
    }

    // Sanitize IDs
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, function ($id) {
        return $id > 0;
    });

    if (empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('ID gói không hợp lệ')]));
    }

    // Build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Get plans info with product name
    $plans = $CMSNT->get_list_safe("
        SELECT pp.`id`, pp.`name`, pp.`image`, pp.`product_id`, p.`name` as product_name, p.`image` as product_image
        FROM `product_plans` pp 
        LEFT JOIN `products` p ON pp.`product_id` = p.`id`
        WHERE pp.`id` IN ($placeholders)
    ", $ids);

    // Get total stock count
    $total_stock = $CMSNT->num_rows_safe("SELECT `id` FROM `product_stock` WHERE `plan_id` IN ($placeholders)", $ids);

    // Get total fields count
    $total_fields = $CMSNT->num_rows_safe("SELECT `id` FROM `product_fields` WHERE `plan_id` IN ($placeholders)", $ids);

    // Get total orders count
    $total_orders = $CMSNT->num_rows_safe("SELECT `id` FROM `product_orders` WHERE `plan_id` IN ($placeholders)", $ids);

    // Build plans array with stock count
    $plans_data = [];
    foreach ($plans as $plan) {
        $stock_count = $CMSNT->num_rows_safe("SELECT `id` FROM `product_stock` WHERE `plan_id` = ?", [$plan['id']]);
        $plan_image = !empty($plan['image']) ? $plan['image'] : (!empty($plan['product_image']) ? $plan['product_image'] : null);
        $plans_data[] = [
            'id' => $plan['id'],
            'name' => html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8'),
            'image' => $plan_image ? BASE_URL($plan_image) : null,
            'product_name' => $plan['product_name'] ? html_entity_decode($plan['product_name'], ENT_QUOTES, 'UTF-8') : null,
            'stock_count' => intval($stock_count)
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => [
            'plans' => $plans_data,
            'total_stock' => intval($total_stock),
            'total_fields' => intval($total_fields),
            'total_orders' => intval($total_orders)
        ]
    ]));
}

// Lấy số đơn hàng pending và ticket chờ phản hồi
if ($_POST['action'] == 'get_pending_counts') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Đếm số đơn hàng đang pending
    $pending_orders = $CMSNT->num_rows("SELECT id FROM `product_orders` WHERE `status` = 'pending'");

    // Đếm số ticket đang chờ phản hồi (status = open và chưa có phản hồi từ admin)
    // Ticket open: chưa được admin phản hồi hoặc có tin nhắn mới từ user
    $pending_tickets = $CMSNT->num_rows("SELECT t.id FROM `support_tickets` t 
        WHERE t.status = 'open' 
        AND (
            NOT EXISTS (SELECT 1 FROM `support_messages` tm WHERE tm.ticket_id = t.id AND tm.sender_type = 'admin')
            OR (SELECT MAX(tm2.id) FROM `support_messages` tm2 WHERE tm2.ticket_id = t.id AND tm2.sender_type = 'user') > 
               COALESCE((SELECT MAX(tm3.id) FROM `support_messages` tm3 WHERE tm3.ticket_id = t.id AND tm3.sender_type = 'admin'), 0)
        )
    ");

    // Đếm số reviews đang chờ duyệt
    $pending_reviews = $CMSNT->num_rows("SELECT id FROM `product_reviews` WHERE `status` = 'pending'");

    // Đếm số yêu cầu rút tiền affiliate đang chờ duyệt
    $pending_withdrawals = $CMSNT->num_rows("SELECT id FROM `aff_withdraw` WHERE `status` = 'pending'");

    die(json_encode([
        'status' => 'success',
        'pending_orders' => intval($pending_orders),
        'pending_tickets' => intval($pending_tickets),
        'pending_reviews' => intval($pending_reviews),
        'pending_withdrawals' => intval($pending_withdrawals)
    ]));
}

if ($_POST['action'] == 'show_thong_ke_dashboard') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $currentDate = date("Y-m-d");
    $currentYear = date('Y');
    $currentMonth = date('m');

    // Xác định ngày bắt đầu và kết thúc của tuần hiện tại (Thứ Hai đến Chủ Nhật)
    $startOfWeek = date("Y-m-d", strtotime("last Monday", strtotime($currentDate)));
    // Nếu hôm nay là Thứ Hai, không cần lùi lại
    if (date('N', strtotime($currentDate)) == 1) {
        $startOfWeek = $currentDate;
    }
    $endOfWeek = date("Y-m-d", strtotime("next Sunday", strtotime($currentDate)));
    // Nếu hôm nay là Chủ Nhật, không cần tiến lên
    if (date('N', strtotime($currentDate)) == 7) {
        $endOfWeek = $currentDate;
    }

    // Dữ liệu hôm nay - Lấy từ bảng product_orders
    $query1 = "SELECT 
                COUNT(id) AS total_orders_today, 
                SUM(final_amount) AS total_pay_today, 
                SUM(cost_price) AS total_cost_today 
              FROM `product_orders` 
              WHERE `status` = 'completed'
              AND DATE(created_at) = '$currentDate'";
    $result1 = $CMSNT->get_row($query1);

    $total_orders_today = $result1['total_orders_today'] ?? 0;
    $total_pay_today = $result1['total_pay_today'] ?? 0;
    $total_cost_today = $result1['total_cost_today'] ?? 0;
    $profit_today = $total_pay_today - $total_cost_today;

    $new_users_today = $CMSNT->get_row("SELECT COUNT(id) AS total_users_today FROM `users` WHERE DATE(create_date) = '$currentDate'")['total_users_today'];

    // Dữ liệu tuần này - Lấy từ bảng product_orders
    $query_week = "SELECT 
                    COUNT(id) AS total_orders_week, 
                    SUM(final_amount) AS total_pay_week, 
                    SUM(cost_price) AS total_cost_week 
                  FROM `product_orders` 
                  WHERE `status` = 'completed'
                  AND DATE(created_at) BETWEEN '$startOfWeek' AND '$endOfWeek'";
    $result_week = $CMSNT->get_row($query_week);

    $total_orders_week = $result_week['total_orders_week'] ?? 0;
    $total_pay_week = $result_week['total_pay_week'] ?? 0;
    $total_cost_week = $result_week['total_cost_week'] ?? 0;
    $profit_week = $total_pay_week - $total_cost_week;

    $new_users_week = $CMSNT->get_row("SELECT COUNT(id) AS total_users_week FROM `users` WHERE DATE(create_date) BETWEEN '$startOfWeek' AND '$endOfWeek'")['total_users_week'];

    // Dữ liệu tháng này - Lấy từ bảng product_orders
    $query2 = "SELECT 
                COUNT(id) AS total_orders_month, 
                SUM(final_amount) AS total_pay_month, 
                SUM(cost_price) AS total_cost_month 
              FROM `product_orders` 
              WHERE `status` = 'completed'
              AND YEAR(created_at) = $currentYear 
              AND MONTH(created_at) = $currentMonth";
    $result2 = $CMSNT->get_row($query2);

    $total_orders_month = $result2['total_orders_month'] ?? 0;
    $total_pay_month = $result2['total_pay_month'] ?? 0;
    $total_cost_month = $result2['total_cost_month'] ?? 0;
    $profit_month = $total_pay_month - $total_cost_month;

    $new_users_month = $CMSNT->get_row("SELECT COUNT(id) AS total_users_month FROM `users` WHERE YEAR(create_date) = $currentYear AND MONTH(create_date) = $currentMonth")['total_users_month'];

    // Dữ liệu toàn thời gian - Lấy từ bảng product_orders
    $query3 = "SELECT 
                COUNT(id) AS total_orders_all, 
                SUM(final_amount) AS total_pay_all, 
                SUM(cost_price) AS total_cost_all 
              FROM `product_orders` 
              WHERE `status` = 'completed'";
    $result3 = $CMSNT->get_row($query3);

    $total_orders_all = $result3['total_orders_all'] ?? 0;
    $total_pay_all = $result3['total_pay_all'] ?? 0;
    $total_cost_all = $result3['total_cost_all'] ?? 0;
    $profit_all = $total_pay_all - $total_cost_all;

    $total_users_all = $CMSNT->get_row("SELECT COUNT(id) AS total_users_all FROM `users`")['total_users_all'];

    $data = array(
        "total_orders_today" => format_cash($total_orders_today),
        "total_pay_today" => format_currency($total_pay_today),
        "total_cost_today" => format_currency($total_cost_today),
        "profit_today" => format_currency($profit_today),
        "new_users_today" => format_cash($new_users_today),

        // Thêm dữ liệu tuần này
        "total_orders_week" => format_cash($total_orders_week),
        "total_pay_week" => format_currency($total_pay_week),
        "total_cost_week" => format_currency($total_cost_week),
        "profit_week" => format_currency($profit_week),
        "new_users_week" => format_cash($new_users_week),

        "total_orders_month" => format_cash($total_orders_month),
        "total_pay_month" => format_currency($total_pay_month),
        "total_cost_month" => format_currency($total_cost_month),
        "profit_month" => format_currency($profit_month),
        "new_users_month" => format_cash($new_users_month),
        "total_orders_all" => format_cash($total_orders_all),
        "total_pay_all" => format_currency($total_pay_all),
        "total_cost_all" => format_currency($total_cost_all),
        "profit_all" => format_currency($profit_all),
        "total_users_all" => format_cash($total_users_all)
    );

    die(json_encode($data));
}

if ($_POST['action'] == 'view_chart_thong_ke_don_hang') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $time_range = check_string($_POST['time_range']);
    $labels = [];
    $revenues = [];
    $profits = [];

    if ($time_range == 'week') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $query = "SELECT SUM(final_amount) AS total_pay, SUM(cost_price) AS total_cost FROM `product_orders` WHERE `status` = 'completed' AND DATE(created_at) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'month') {
        // Thống kê theo tháng hiện tại
        $month = date('m');
        $year = date('Y');
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = "$year-$month-$day";
            $query = "SELECT SUM(final_amount) AS total_pay, SUM(cost_price) AS total_cost FROM `product_orders` WHERE `status` = 'completed' AND DATE(created_at) = '$date'";
            $result = $CMSNT->get_row($query);

            $labels[] = "$day/$month";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    } else if ($time_range == 'year') {
        // Thống kê theo năm hiện tại
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $month_name = date('m', mktime(0, 0, 0, $month, 1));
            $query = "SELECT SUM(final_amount) AS total_pay, SUM(cost_price) AS total_cost FROM `product_orders` 
                      WHERE `status` = 'completed' AND MONTH(created_at) = '$month' AND YEAR(created_at) = '$year'";
            $result = $CMSNT->get_row($query);

            $labels[] = "Tháng $month_name";
            $revenues[] = $result['total_pay'] ?? 0;
            $profits[] = ($result['total_pay'] ?? 0) - ($result['total_cost'] ?? 0);
        }
    }

    die(json_encode([
        'labels' => $labels,
        'revenues' => $revenues,
        'profits' => $profits
    ]));
}

if ($_POST['action'] == 'view_don_hang_gan_day') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_recent_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $orders = $CMSNT->get_list("SELECT * FROM `product_orders` ORDER BY id DESC limit 100");
    $html = '';
    foreach ($orders as $order) {
        $username = $CMSNT->get_row("SELECT username FROM `users` WHERE `id` = '" . $order['user_id'] . "'")['username'] ?? 'N/A';
        $status_badge = '';
        switch ($order['status']) {
            case 'completed':
                $status_badge = '<span class="badge bg-success">Hoàn thành</span>';
                break;
            case 'pending':
                $status_badge = '<span class="badge bg-warning">Chờ xử lý</span>';
                break;
            case 'processing':
                $status_badge = '<span class="badge bg-info">Đang xử lý</span>';
                break;
            case 'cancelled':
                $status_badge = '<span class="badge bg-danger">Đã hủy</span>';
                break;
            case 'cancelled_no_refund':
                $status_badge = '<span class="badge bg-dark">Hủy không hoàn</span>';
                break;
        }
        $html .= '<li>
            <div class="timeline-time text-end">
                <span class="date">' . timeAgo(strtotime($order['created_at'])) . '</span>
            </div>
            <div class="timeline-icon">
                <a href="javascript:void(0);"></a>
            </div>
            <div class="timeline-body">
                <div class="d-flex align-items-top timeline-main-content flex-wrap mt-0">
                    <div class="flex-fill">
                        <div class="d-flex align-items-center">
                            <div class="mt-sm-0 mt-2">
                                <p class="mb-0 text-muted"><a class="fw-bold" href="' . base_url_admin('user-edit&id=' . $order['user_id']) . '" style="color: green;">' . $username . '</a>
                                    mua <b style="color: red;">' . format_cash($order['quantity']) . '</b>
                                    <b>' . $order['product_name'] . ' - ' . $order['plan_name'] . '</b> với giá <b style="color:blue;">' . format_currency($order['final_amount']) . '</b> ' . $status_badge . '
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>';
    }
    die($html);
}

if ($_POST['action'] == 'view_nap_tien_gan_day') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_recent_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $deposits = $CMSNT->get_list("SELECT * FROM `deposit_log` WHERE `is_virtual` = 0 ORDER BY id DESC limit 100");
    $html = '';
    foreach ($deposits as $deposit) {
        $username = $CMSNT->get_row("SELECT username FROM `users` WHERE `id` = '" . $deposit['user_id'] . "'")['username'] ?? 'N/A';
        $html .= '<li>
        <div class="timeline-time text-end">
            <span class="date">' . timeAgo($deposit['create_time']) . '</span>
        </div>
        <div class="timeline-icon">
            <a href="javascript:void(0);"></a>
        </div>
        <div class="timeline-body">
            <div class="d-flex align-items-top timeline-main-content flex-wrap mt-0">
                <div class="flex-fill">
                    <div class="d-flex align-items-center">
                        <div class="mt-sm-0 mt-2">
                            <p class="mb-0 text-muted"><a class="fw-bold" href="' . base_url_admin('user-edit&id=' . $deposit['user_id']) . '" style="color: green;">' . $username . '</a>
                                thực hiện nạp <b style="color: blue;">' . format_currency($deposit['amount']) . '</b>
                                bằng <b style="color:red">' . $deposit['method'] . '</b> thực nhận <b style="color:blue;">' . format_currency($deposit['received']) . '</b>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </li>';
    }
    die($html);
}

if ($_POST['action'] == 'view_chart_thong_ke_nap_tien') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $time_range = check_string($_POST['time_range']);
    $labels = [];
    $amount = [];

    if ($time_range == 'week') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $payment_bank_invoice = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank_invoice WHERE `status` = 'completed' AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_paypal + $total_topup_xipay + $total_topup_tmweasyapi + $payment_bank_invoice;

            $labels[] = date("d/m", strtotime("-$i days"));
            $amount[] = $total_topup;
        }
    } else if ($time_range == 'month') {
        // Thống kê theo tháng hiện tại
        $month = date('m');
        $year = date('Y');
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($day = 1; $day <= $numOfDays; $day++) {
            $date = "$year-$month-$day";

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
            $payment_bank_invoice = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank_invoice WHERE `status` = 'completed' AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;

            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_paypal + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix + $payment_bank_invoice;

            $labels[] = "$day/$month";
            $amount[] = $total_topup;
        }
    } else if ($time_range == 'year') {
        // Thống kê theo năm hiện tại
        $year = date('Y');

        for ($month = 1; $month <= 12; $month++) {
            $month_name = date('m', mktime(0, 0, 0, $month, 1));

            $start_date = "$year-$month-01";
            $last_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $end_date = "$year-$month-$last_day";

            $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $payment_bank_invoice = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank_invoice WHERE `status` = 'completed' AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'")['total'] ?? 0;
            $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_paypal + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix + $payment_bank_invoice;

            $labels[] = "Tháng $month_name";
            $amount[] = $total_topup;
        }
    }

    die(json_encode([
        'labels' => $labels,
        'amount' => $amount
    ]));
}

if ($_POST['action'] == 'view_chart_thong_ke_nap_tien_thang') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $month = date('m');
    $year = date('Y');
    $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $labels = [];
    $data = [];

    for ($day = 1; $day <= $numOfDays; $day++) {
        $date = "$year-$month-$day";

        $total_topup_bank = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank WHERE DATE(create_gettime) = '$date'")['total'] ?? 0;
        $payment_bank_invoice = $CMSNT->get_row("SELECT SUM(amount) AS total FROM payment_bank_invoice WHERE `status` = 'completed' AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_card = $CMSNT->get_row("SELECT SUM(amount) AS total FROM cards WHERE `status` = 'completed' AND DATE(create_date) = '$date'")['total'] ?? 0;
        $total_topup_crypto = $CMSNT->get_row("SELECT SUM(received) AS total FROM payment_crypto WHERE `status` = 'completed' AND DATE(create_gettime) = '$date'")['total'] ?? 0;
        $total_topup_paypal = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_paypal WHERE DATE(create_date) = '$date'")['total'] ?? 0;

        $total_topup_xipay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_xipay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_korapay = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_korapay WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_tmweasyapi = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_tmweasyapi WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup_openpix = $CMSNT->get_row("SELECT SUM(price) AS total FROM payment_openpix WHERE `status` = 1 AND DATE(created_at) = '$date'")['total'] ?? 0;
        $total_topup = $total_topup_bank + $total_topup_card + $total_topup_crypto + $total_topup_paypal + $total_topup_xipay + $total_topup_korapay + $total_topup_tmweasyapi + $total_topup_openpix + $payment_bank_invoice;

        $labels[] = "$day/$month/$year";
        $data[] = $total_topup;
    }

    die(json_encode([
        'labels' => $labels,
        'data' => $data
    ]));
}






if ($_POST['action'] == 'phan_tich_utm_source_users') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    // Tạo HTML cho tab
    $html = '<ul class="nav nav-tabs mb-5 nav-justified nav-style-1 d-sm-flex d-block" id="myTab" role="tablist">';
    $html .= '<li class="nav-item">';
    $html .= '<a class="nav-link active" id="table-tab" data-toggle="tab" href="#table-content" role="tab" aria-controls="table-content" aria-selected="true">Table</a>';
    $html .= '</li>';
    $html .= '<li class="nav-item">';
    $html .= '<a class="nav-link" id="chart-tab" data-toggle="tab" href="#chart-content" role="tab" aria-controls="chart-content" aria-selected="false">Pie Chart</a>';
    $html .= '</li>';
    $html .= '</ul>';

    // Tạo HTML cho nội dung của tab
    $html .= '<div class="tab-content" id="myTabContent">';
    $html .= '<div class="tab-pane fade show active" id="table-content" role="tabpanel" aria-labelledby="table-tab">';
    $html .= '<div class="table-responsive table-wrapper" style="max-height: 500px;overflow-y: auto;">';
    $html .= '<table class="table text-nowrap table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th class="text-center">Xếp hạng</th>
                    <th class="text-center">utm_source</th>
                    <th class="text-center">Số thành viên đăng ký</th>
                </tr>
            </thead>
            <tbody>';
    $i = 1;
    $data_labels = [];
    $data_user_counts = [];
    foreach (
        $CMSNT->get_list("SELECT 
    utm_source, 
    COUNT(*) AS total_users
FROM users 
GROUP BY utm_source 
ORDER BY total_users DESC ") as $row
    ) {
        $data_labels[] = $row['utm_source'];
        $data_user_counts[] = $row['total_users'];
        $html .= "<tr>
    <td class='text-center' style='font-size:15px;'>" . $i++ . "</td>
    <td class='text-center'>" . $row['utm_source'] . "</td>
    <td class='text-center'><b>" . format_cash($row['total_users']) . "</b></td>
  </tr>";
    }
    $html .= "</tbody>
        </table>";
    $html .= "</div>";
    $html .= '</div>';

    $html .= '<div class="tab-pane fade" id="chart-content" role="tabpanel" aria-labelledby="chart-tab">';
    $html .= '<canvas id="myChart" width="500" height="300"></canvas>';
    $html .= '</div>';

    $html .= '</div>';

    // Thêm kịch bản JavaScript để chuyển đổi tab
    $html .= '<script>
            $(document).ready(function(){
                $("#table-tab").click(function(){
                    $("#chart-content").removeClass("show active");
                    $("#chart-tab").removeClass("active");
                    $("#table-content").addClass("show active");
                    $("#table-tab").addClass("active");
                });
                $("#chart-tab").click(function(){
                    $("#table-content").removeClass("show active");
                    $("#table-tab").removeClass("active");
                    $("#chart-content").addClass("show active");
                    $("#chart-tab").addClass("active");
                    // Thêm kịch bản JavaScript để vẽ biểu đồ Pie Chart
                    var ctx = document.getElementById("myChart").getContext("2d");
                    var myChart = new Chart(ctx, {
                        type: "pie",
                        data: {
                            labels: ' . json_encode($data_labels) . ',
                            datasets: [{
                                label: "Số lượng người dùng",
                                data: ' . json_encode($data_user_counts) . ',
                                backgroundColor: [
                                    "rgba(255, 99, 132, 0.6)",
                                    "rgba(54, 162, 235, 0.6)",
                                    "rgba(255, 206, 86, 0.6)",
                                    "rgba(75, 192, 192, 0.6)",
                                    "rgba(153, 102, 255, 0.6)",
                                    "rgba(255, 159, 64, 0.6)"
                                ],
                                borderColor: [
                                    "rgba(255, 99, 132, 1)",
                                    "rgba(54, 162, 235, 1)",
                                    "rgba(255, 206, 86, 1)",
                                    "rgba(75, 192, 192, 1)",
                                    "rgba(153, 102, 255, 1)",
                                    "rgba(255, 159, 64, 1)"
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            legend: {
                                position: "right",
                                labels: {
                                    fontColor: "black",
                                    fontSize: 12
                                }
                            }
                        }
                    });
                });
            });
        </script>';







    die($html);
}

if ($_POST['action'] == 'export_users_email') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Lấy tất cả email của users
    $users = $CMSNT->get_list("SELECT `id`, `username`, `email`, `fullname`, `create_date` FROM `users` ORDER BY id DESC");

    // Tạo header CSV
    $csv_data = "ID,Username,Email,Full Name,Create Date\n";

    // Thêm dữ liệu users
    foreach ($users as $user) {
        $csv_data .= $user['id'] . ',';
        $csv_data .= '"' . str_replace('"', '""', $user['username']) . '",';
        $csv_data .= '"' . str_replace('"', '""', $user['email']) . '",';
        $csv_data .= '"' . str_replace('"', '""', $user['fullname']) . '",';
        $csv_data .= '"' . str_replace('"', '""', $user['create_date']) . '"';
        $csv_data .= "\n";
    }

    die(json_encode([
        'status' => 'success',
        'csv_data' => $csv_data,
        'total_users' => count($users)
    ]));
}

// Export đơn hàng sản phẩm
if ($_POST['action'] == 'exportProductOrders') {
    if (checkPermission($getUser['admin'], 'view_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input
    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một đơn hàng')]));
    }

    if (empty($_POST['columns']) || !is_array($_POST['columns'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';
    $separator = $file_type === 'csv' ? ',' : "\t";

    // Sanitize IDs
    $ids = array_filter(array_map('intval', $_POST['ids']));
    if (empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('ID đơn hàng không hợp lệ')]));
    }

    // Allowed columns mapping
    $allowed_columns = [
        'trans_id' => ['field' => 'po.trans_id', 'label' => __('Mã đơn hàng')],
        'api_trans_id' => ['field' => 'po.api_trans_id', 'label' => __('Mã đơn API')],
        'username' => ['field' => 'u.username', 'label' => __('Username')],
        'product_name' => ['field' => 'po.product_name', 'label' => __('Sản phẩm')],
        'plan_name' => ['field' => 'po.plan_name', 'label' => __('Gói')],
        'quantity' => ['field' => 'po.quantity', 'label' => __('Số lượng')],
        'total_price' => ['field' => 'po.total_price', 'label' => __('Giá gốc')],
        'discount_amount' => ['field' => 'po.discount_amount', 'label' => __('Giảm giá')],
        'final_amount' => ['field' => 'po.final_amount', 'label' => __('Thanh toán')],
        'status' => ['field' => 'po.status', 'label' => __('Trạng thái')],
        'created_at' => ['field' => 'po.created_at', 'label' => __('Ngày tạo')],
        'delivery_content' => ['field' => 'po.delivery_content', 'label' => __('Nội dung giao')],
        'note' => ['field' => 'po.note', 'label' => __('Ghi chú')]
    ];

    // Filter and validate columns
    $selected_columns = [];
    foreach ($_POST['columns'] as $col) {
        if (isset($allowed_columns[$col])) {
            $selected_columns[$col] = $allowed_columns[$col];
        }
    }

    if (empty($selected_columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có cột hợp lệ để xuất')]));
    }

    // Build SELECT clause
    $select_fields = [];
    foreach ($selected_columns as $key => $col) {
        $select_fields[] = $col['field'] . ' AS `' . $key . '`';
    }

    // Build query with placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "SELECT " . implode(', ', $select_fields) . "
              FROM `product_orders` po
              LEFT JOIN `users` u ON po.user_id = u.id
              WHERE po.id IN ($placeholders)
              ORDER BY po.id DESC";

    $orders = $CMSNT->get_list_safe($query, $ids);

    if (empty($orders)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy đơn hàng')]));
    }

    // Build content
    $lines = [];

    // Header row
    $headers = [];
    foreach ($selected_columns as $col) {
        $label = $col['label'];
        if ($file_type === 'csv') {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $headers[] = $label;
    }
    $lines[] = implode($separator, $headers);

    // Status mapping
    $status_labels = [
        'pending' => __('Chờ xử lý'),
        'processing' => __('Đang xử lý'),
        'completed' => __('Hoàn thành'),
        'cancelled' => __('Đã hủy'),
        'cancelled_no_refund' => __('Hủy không hoàn tiền')
    ];

    // Data rows
    foreach ($orders as $order) {
        $row = [];
        foreach ($selected_columns as $key => $col) {
            $value = $order[$key] ?? '';

            // Format specific fields
            if ($key === 'status') {
                $value = $status_labels[$value] ?? $value;
            } elseif (in_array($key, ['total_price', 'discount_amount', 'final_amount'])) {
                $value = number_format((float)$value, 0, ',', '.');
            } elseif ($key === 'delivery_content') {
                // Remove newlines for export
                $value = str_replace(["\r\n", "\r", "\n"], ' | ', $value);
            }

            if ($file_type === 'csv') {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $row[] = $value;
        }
        $lines[] = implode($separator, $row);
    }

    $content = implode("\n", $lines);
    $filename = 'orders_export_' . date('Y-m-d_His') . '.' . $file_type;

    // Ghi log xuất đơn hàng
    $log_content = sprintf(
        'Xuất %d đơn hàng (IDs: %s) - File: %s - Các cột: %s',
        count($orders),
        implode(', ', $ids),
        $filename,
        implode(', ', array_keys($selected_columns))
    );
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $log_content
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => sprintf(__('Đã xuất %d đơn hàng'), count($orders)),
        'data' => [
            'content' => $content,
            'filename' => $filename
        ]
    ]));
}

// Export thành viên đã chọn
if ($_POST['action'] == 'exportSelectedUsers') {
    if (checkPermission($getUser['admin'], 'view_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input
    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một thành viên')]));
    }

    if (empty($_POST['columns']) || !is_array($_POST['columns'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một cột để xuất')]));
    }

    $file_type = isset($_POST['file_type']) && in_array($_POST['file_type'], ['txt', 'csv']) ? $_POST['file_type'] : 'txt';
    $separator = $file_type === 'csv' ? ',' : "\t";

    // Sanitize IDs
    $ids = array_filter(array_map('intval', $_POST['ids']));
    if (empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('ID thành viên không hợp lệ')]));
    }

    // Allowed columns mapping
    $allowed_columns = [
        'id' => ['field' => 'id', 'label' => __('ID')],
        'username' => ['field' => 'username', 'label' => __('Username')],
        'email' => ['field' => 'email', 'label' => __('Email')],
        'name' => ['field' => 'fullname', 'label' => __('Họ tên')],
        'phone' => ['field' => 'phone', 'label' => __('Số điện thoại')],
        'money' => ['field' => 'money', 'label' => __('Số dư')],
        'total_money' => ['field' => 'total_money', 'label' => __('Tổng nạp')],
        'discount' => ['field' => 'discount', 'label' => __('Chiết khấu')],
        'admin' => ['field' => 'admin', 'label' => __('Admin')],
        'banned' => ['field' => 'banned', 'label' => __('Trạng thái')],
        'utm_source' => ['field' => 'utm_source', 'label' => __('utm_source')],
        'create_date' => ['field' => 'create_date', 'label' => __('Ngày tạo')],
        'ip' => ['field' => 'ip', 'label' => __('Địa chỉ IP')]
    ];

    // Filter and validate columns
    $selected_columns = [];
    foreach ($_POST['columns'] as $col) {
        if (isset($allowed_columns[$col])) {
            $selected_columns[$col] = $allowed_columns[$col];
        }
    }

    if (empty($selected_columns)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có cột hợp lệ để xuất')]));
    }

    // Build SELECT clause
    $select_fields = [];
    foreach ($selected_columns as $key => $col) {
        $select_fields[] = '`' . $col['field'] . '` AS `' . $key . '`';
    }

    // Build query with placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $query = "SELECT " . implode(', ', $select_fields) . "
              FROM `users`
              WHERE id IN ($placeholders)
              ORDER BY id DESC";

    $users = $CMSNT->get_list_safe($query, $ids);

    if (empty($users)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy thành viên')]));
    }

    // Build content
    $lines = [];

    // Header row
    $headers = [];
    foreach ($selected_columns as $col) {
        $label = $col['label'];
        if ($file_type === 'csv') {
            $label = '"' . str_replace('"', '""', $label) . '"';
        }
        $headers[] = $label;
    }
    $lines[] = implode($separator, $headers);

    // Status mapping
    $status_labels = [
        '0' => __('Active'),
        '1' => __('Banned')
    ];

    // Admin mapping
    $admin_labels = [
        '0' => __('Không'),
        '1' => __('Có')
    ];

    // Data rows
    foreach ($users as $user) {
        $row = [];
        foreach ($selected_columns as $key => $col) {
            $value = $user[$key] ?? '';

            // Format specific fields
            if ($key === 'banned') {
                $value = $status_labels[$value] ?? $value;
            } elseif ($key === 'admin') {
                $value = ($value != '0') ? __('Có') : __('Không');
            } elseif (in_array($key, ['money', 'total_money'])) {
                $value = number_format((float)$value, 0, ',', '.');
            } elseif ($key === 'discount') {
                $value = number_format((float)$value, 0) . '%';
            }

            if ($file_type === 'csv') {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $row[] = $value;
        }
        $lines[] = implode($separator, $row);
    }

    $content = implode("\n", $lines);
    $filename = 'users_export_' . date('Y-m-d_His') . '.' . $file_type;

    // Ghi log xuất thành viên
    $log_content = sprintf(
        'Xuất %d thành viên (IDs: %s) - File: %s - Các cột: %s',
        count($users),
        implode(', ', $ids),
        $filename,
        implode(', ', array_keys($selected_columns))
    );
    $CMSNT->insert('logs', [
        'user_id' => $getUser['id'],
        'ip' => myip(),
        'device' => getUserAgent(),
        'createdate' => gettime(),
        'action' => $log_content
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => sprintf(__('Đã xuất %d thành viên'), count($users)),
        'data' => [
            'content' => $content,
            'filename' => $filename
        ]
    ]));
}


// Thống kê doanh thu nhà cung cấp theo khoảng thời gian
if ($_POST['action'] == 'view_chart_supplier_revenue') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $supplier_id = check_string($_POST['supplier_id']);
    $time_range = check_string($_POST['time_range']);

    // Kiểm tra tồn tại supplier_id
    if (!$CMSNT->get_row("SELECT * FROM `suppliers` WHERE `id` = '$supplier_id'")) {
        die(json_encode(['status' => 'error', 'msg' => __('Nhà cung cấp không tồn tại')]));
    }

    $labels = [];
    $revenues = [];

    if ($time_range == '7_days') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $query = "SELECT SUM(pay) AS total FROM `orders` WHERE `supplier_id` = '$supplier_id' AND DATE(created_at) = '$date' AND `status` IN ('Completed', 'In progress', 'Processing')";
            $result = $CMSNT->get_row($query);

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $result['total'] ?? 0;
        }
    } else if ($time_range == '30_days') {
        // Thống kê 30 ngày gần đây
        for ($i = 29; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $query = "SELECT SUM(pay) AS total FROM `orders` WHERE `supplier_id` = '$supplier_id' AND DATE(created_at) = '$date' AND `status` IN ('Completed', 'In progress', 'Processing')";
            $result = $CMSNT->get_row($query);

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $result['total'] ?? 0;
        }
    } else if ($time_range == '1_year') {
        // Thống kê 12 tháng gần đây
        for ($i = 11; $i >= 0; $i--) {
            $year = date("Y", strtotime("-$i months"));
            $month = date("m", strtotime("-$i months"));

            $query = "SELECT SUM(pay) AS total FROM `orders` 
                      WHERE `supplier_id` = '$supplier_id' 
                      AND MONTH(created_at) = '$month' AND YEAR(created_at) = '$year'
                      AND `status` IN ('Completed', 'In progress', 'Processing')";
            $result = $CMSNT->get_row($query);

            $labels[] = date("m/Y", strtotime("-$i months"));
            $revenues[] = $result['total'] ?? 0;
        }
    }

    die(json_encode([
        'status' => 'success',
        'labels' => $labels,
        'revenues' => $revenues
    ]));
}

// Thống kê doanh thu vs lợi nhuận nhà cung cấp theo khoảng thời gian
if ($_POST['action'] == 'view_chart_supplier_revenue_profit') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $supplier_id = check_string($_POST['supplier_id']);
    $time_range = check_string($_POST['time_range']);

    // Kiểm tra tồn tại supplier_id
    if (!$CMSNT->get_row("SELECT * FROM `suppliers` WHERE `id` = '$supplier_id'")) {
        die(json_encode(['status' => 'error', 'msg' => __('Nhà cung cấp không tồn tại')]));
    }

    $labels = [];
    $revenues = [];
    $profits = [];

    if ($time_range == '7_days') {
        // Thống kê 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $revenue_query = "SELECT SUM(pay) AS total FROM `orders` WHERE `supplier_id` = '$supplier_id' AND DATE(created_at) = '$date' AND `status` IN ('Completed', 'In progress', 'Processing')";
            $cost_query = "SELECT SUM(cost) AS total FROM `orders` WHERE `supplier_id` = '$supplier_id' AND DATE(created_at) = '$date' AND `status` IN ('Completed', 'In progress', 'Processing')";

            $revenue_result = $CMSNT->get_row($revenue_query);
            $cost_result = $CMSNT->get_row($cost_query);

            $daily_revenue = $revenue_result['total'] ?? 0;
            $daily_cost = $cost_result['total'] ?? 0;

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $daily_revenue;
            $profits[] = $daily_revenue - $daily_cost;
        }
    } else if ($time_range == '30_days') {
        // Thống kê 30 ngày gần đây
        for ($i = 29; $i >= 0; $i--) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $revenue_query = "SELECT SUM(pay) AS total FROM `orders` WHERE `supplier_id` = '$supplier_id' AND DATE(created_at) = '$date' AND `status` IN ('Completed', 'In progress', 'Processing')";
            $cost_query = "SELECT SUM(cost) AS total FROM `orders` WHERE `supplier_id` = '$supplier_id' AND DATE(created_at) = '$date' AND `status` IN ('Completed', 'In progress', 'Processing')";

            $revenue_result = $CMSNT->get_row($revenue_query);
            $cost_result = $CMSNT->get_row($cost_query);

            $daily_revenue = $revenue_result['total'] ?? 0;
            $daily_cost = $cost_result['total'] ?? 0;

            $labels[] = date("d/m", strtotime("-$i days"));
            $revenues[] = $daily_revenue;
            $profits[] = $daily_revenue - $daily_cost;
        }
    } else if ($time_range == '1_year') {
        // Thống kê 12 tháng gần đây
        for ($i = 11; $i >= 0; $i--) {
            $year = date("Y", strtotime("-$i months"));
            $month = date("m", strtotime("-$i months"));

            $revenue_query = "SELECT SUM(pay) AS total FROM `orders` 
                            WHERE `supplier_id` = '$supplier_id' 
                            AND MONTH(created_at) = '$month' AND YEAR(created_at) = '$year'
                            AND `status` IN ('Completed', 'In progress', 'Processing')";
            $cost_query = "SELECT SUM(cost) AS total FROM `orders` 
                         WHERE `supplier_id` = '$supplier_id' 
                         AND MONTH(created_at) = '$month' AND YEAR(created_at) = '$year'
                         AND `status` IN ('Completed', 'In progress', 'Processing')";

            $revenue_result = $CMSNT->get_row($revenue_query);
            $cost_result = $CMSNT->get_row($cost_query);

            $monthly_revenue = $revenue_result['total'] ?? 0;
            $monthly_cost = $cost_result['total'] ?? 0;

            $labels[] = date("m/Y", strtotime("-$i months"));
            $revenues[] = $monthly_revenue;
            $profits[] = $monthly_revenue - $monthly_cost;
        }
    }

    die(json_encode([
        'status' => 'success',
        'labels' => $labels,
        'revenues' => $revenues,
        'profits' => $profits
    ]));
}

// Load translate data with pagination and search
if ($_POST['action'] == 'load_translate_data') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $lang_id = check_string($_POST['lang_id']);
    $draw = intval(check_string($_POST['draw']));
    $start = intval(check_string($_POST['start']));
    $length = intval(check_string($_POST['length']));
    $search = check_string(check_string($_POST['search']['value']));
    $order_column = intval(check_string($_POST['order'][0]['column']));
    $order_dir = check_string(check_string($_POST['order'][0]['dir']));
    $filter = isset($_POST['filter']) ? check_string($_POST['filter']) : 'all';

    // Kiểm tra ngôn ngữ tồn tại
    if (!$lang_row = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$lang_id' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại')]));
    }

    // Cột để sắp xếp
    $columns = array('id', 'name', 'value', 'id');
    $order_column_name = isset($columns[$order_column]) ? $columns[$order_column] : 'id';

    // Xây dựng câu truy vấn
    $where = "WHERE `lang_id` = '$lang_id'";

    // Thêm filter cho nội dung chưa dịch
    if ($filter === 'untranslated') {
        $where .= " AND (`name` = `value` OR `value` = '' OR `value` IS NULL)";
    }

    if (!empty($search)) {
        $where .= " AND (`name` LIKE '%$search%' OR `value` LIKE '%$search%')";
    }

    // Tổng số bản ghi
    $total_records = $CMSNT->num_rows("SELECT * FROM `translate` WHERE `lang_id` = '$lang_id'");

    // Tổng số bản ghi sau khi lọc
    $total_filtered = $CMSNT->num_rows("SELECT * FROM `translate` $where");

    // Lấy dữ liệu với phân trang và sắp xếp
    $sql = "SELECT * FROM `translate` $where ORDER BY $order_column_name $order_dir LIMIT $start, $length";
    $translates = $CMSNT->get_list($sql);

    $data = array();

    foreach ($translates as $trans) {
        $row = array();
        $row[] = '<input type="checkbox" class="form-check-input row-checkbox" value="' . $trans['id'] . '" data-name="' . htmlspecialchars($trans['name']) . '" data-code="' . $lang_row['code'] . '">';
        $row[] = '<textarea class="form-control" disabled>' . htmlspecialchars($trans['name']) . '</textarea>';
        $row[] = '<textarea class="form-control" id="value' . $trans['id'] . '" onchange="updateForm(\'' . $trans['id'] . '\')">' . htmlspecialchars($trans['value']) . '</textarea>';
        $row[] = '<div class="btn-list">
                    <button type="button" class="btn btn-primary-gradient btn-wave btn-sm" onclick="autoTranslate(\'' . $trans['id'] . '\', \'' . addslashes($trans['name']) . '\', \'' . $lang_row['code'] . '\', this)">
                        <i class="ri-translate"></i> ' . __('Dịch tự động') . '
                    </button>
                    <button type="button" class="btn btn-danger-gradient btn-wave btn-sm" onclick="RemoveRow(\'' . $trans['id'] . '\', \'' . addslashes($trans['name']) . '\')">
                        <i class="ri-delete-bin-line"></i> ' . __('Delete') . '
                    </button>
                  </div>';
        $data[] = $row;
    }

    $response = array(
        "draw" => $draw,
        "recordsTotal" => $total_records,
        "recordsFiltered" => $total_filtered,
        "data" => $data
    );

    die(json_encode($response));
}

// Lấy bảng xếp hạng user theo giá trị đơn hàng trong ngày
if ($_POST['action'] == 'get_daily_leaderboard') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $currentDate = date("Y-m-d");

    // Lấy tất cả user có đơn hàng trong ngày từ bảng product_orders
    $query = "SELECT 
                u.id,
                u.username,
                u.fullname,
                u.email,
                SUM(po.total_price) as total_spent,
                COUNT(po.id) as total_orders
              FROM `users` u
              INNER JOIN `product_orders` po ON u.id = po.user_id
              WHERE po.status IN ('completed', 'pending', 'processing')
              AND DATE(po.created_at) = '$currentDate'
              GROUP BY u.id, u.username, u.fullname, u.email
              ORDER BY total_spent DESC";

    $leaderboard = $CMSNT->get_list($query);

    $data = [];
    $rank = 1;

    foreach ($leaderboard as $user) {
        $data[] = [
            'rank'  => $rank,
            'id'    => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'] ? $user['fullname'] : $user['username'],
            'email' => $user['email'],
            'total_spent' => format_currency($user['total_spent']),
            'total_orders' => format_cash($user['total_orders'])
        ];
        $rank++;
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'date' => date('d/m/Y')
    ]));
}

// Lấy top 50 sản phẩm bán chạy nhất trong ngày
if ($_POST['action'] == 'get_daily_top_services') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $currentDate = date("Y-m-d");

    // Lấy top 50 sản phẩm có tổng doanh thu cao nhất trong ngày từ bảng product_orders
    $query = "SELECT 
                po.product_id as service_id,
                p.name as service_name,
                SUM(po.total_price) as total_revenue,
                SUM(po.cost_price * po.quantity) as total_cost,
                COUNT(po.id) as total_orders,
                AVG(po.total_price) as avg_price
              FROM `product_orders` po
              LEFT JOIN `products` p ON po.product_id = p.id
              WHERE po.status IN ('completed', 'pending', 'processing')
              AND DATE(po.created_at) = '$currentDate'
              GROUP BY po.product_id, p.name
              ORDER BY total_revenue DESC
              LIMIT 50";

    $services = $CMSNT->get_list($query);

    $data = [];
    $rank = 1;

    foreach ($services as $service) {
        $profit = $service['total_revenue'] - $service['total_cost'];
        $data[] = [
            'rank' => $rank,
            'service_id' => $service['service_id'],
            'service_name' => $service['service_name'] ?? __('Sản phẩm đã xóa'),
            'total_revenue' => format_currency($service['total_revenue']),
            'total_cost' => format_currency($service['total_cost']),
            'profit' => format_currency($profit),
            'total_orders' => format_cash($service['total_orders']),
            'avg_price' => format_currency($service['avg_price'])
        ];
        $rank++;
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'date' => date('d/m/Y')
    ]));
}

// Lấy thống kê theo danh mục sản phẩm trong ngày
if ($_POST['action'] == 'get_daily_suppliers_stats') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_statistical') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $currentDate = date("Y-m-d");

    // Lấy thống kê theo danh mục sản phẩm trong ngày từ bảng product_orders
    // Sử dụng FIND_IN_SET vì products có thể thuộc nhiều category (category_ids)
    $query = "SELECT 
                c.id as supplier_id,
                c.name as supplier_name,
                'Danh mục' as type,
                0 as price,
                SUM(po.total_price) as total_revenue,
                SUM(po.cost_price * po.quantity) as total_cost,
                COUNT(po.id) as total_orders
              FROM `categories` c
              INNER JOIN `products` p ON FIND_IN_SET(c.id, p.category_ids) > 0
              INNER JOIN `product_orders` po ON p.id = po.product_id
              WHERE po.status IN ('completed', 'pending', 'processing')
              AND DATE(po.created_at) = '$currentDate'
              GROUP BY c.id, c.name
              ORDER BY total_revenue DESC";

    $suppliers = $CMSNT->get_list($query);

    $data = [];
    $rank = 1;

    foreach ($suppliers as $supplier) {
        $profit = $supplier['total_revenue'] - $supplier['total_cost'];
        $profit_margin = $supplier['total_revenue'] > 0 ? round(($profit / $supplier['total_revenue']) * 100, 2) : 0;

        $data[] = [
            'rank' => $rank,
            'supplier_id' => $supplier['supplier_id'],
            'supplier_name' => $supplier['supplier_name'],
            'type' => $supplier['type'],
            'price' => format_currency($supplier['price']),
            'total_revenue' => format_currency($supplier['total_revenue']),
            'total_cost' => format_currency($supplier['total_cost']),
            'profit' => format_currency($profit),
            'total_orders' => format_cash($supplier['total_orders']),
            'profit_margin' => $profit_margin
        ];
        $rank++;
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'date' => date('d/m/Y')
    ]));
}



// Export dữ liệu đơn hàng
if ($_POST['action'] == 'exportOrderData') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'view_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $format = check_string($_POST['format']);
    $orderIds = json_decode($_POST['orderIds'], true);

    if (empty($orderIds) || !is_array($orderIds)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có đơn hàng nào được chọn')]));
    }

    // Validate format
    if (!in_array($format, ['csv', 'txt'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Định dạng không hợp lệ')]));
    }

    // Lấy dữ liệu đơn hàng
    $orderIds = array_map('intval', $orderIds);
    $orderIdsStr = implode(',', $orderIds);

    $orders = $CMSNT->get_list("SELECT 
        `trans_id`, 
        `order_id`, 
        `service_name`, 
        `quantity`, 
        `link`, 
        `comment`, 
        `pay`,
        `created_at`
    FROM `orders` 
    WHERE `id` IN ($orderIdsStr) 
    ORDER BY `id` DESC");

    if (empty($orders)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy đơn hàng nào')]));
    }

    $data = '';
    $filename = 'orders_export_' . date('Y-m-d_H-i-s');
    $mimeType = '';

    if ($format == 'csv') {
        // Tạo CSV
        $filename .= '.csv';
        $mimeType = 'text/csv; charset=utf-8';

        // Header CSV
        $data = "\xEF\xBB\xBF"; // UTF-8 BOM để Excel hiển thị đúng tiếng Việt
        $data .= '"' . __('Tên dịch vụ') . '","' . __('Mã đơn hàng') . '","' . __('Mã đơn hàng API') . '","' . __('Số lượng') . '","' . __('Liên kết') . '","' . __('Bình luận') . '","' . __('Số tiền thanh toán') . '","' . __('Thời gian tạo') . '"' . "\n";

        // Dữ liệu CSV
        foreach ($orders as $order) {
            $data .= '"' . str_replace('"', '""', $order['service_name']) . '",';
            $data .= '"' . str_replace('"', '""', $order['trans_id']) . '",';
            $data .= '"' . str_replace('"', '""', $order['order_id'] ? $order['order_id'] : 'N/A') . '",';
            $data .= '"' . str_replace('"', '""', format_cash($order['quantity'])) . '",';
            $data .= '"' . str_replace('"', '""', $order['link'] ? $order['link'] : 'N/A') . '",';
            $data .= '"' . str_replace('"', '""', $order['comment'] ? $order['comment'] : 'N/A') . '",';
            $data .= '"' . str_replace('"', '""', format_currency($order['pay'])) . '",';
            $data .= '"' . str_replace('"', '""', $order['created_at']) . '"';
            $data .= "\n";
        }
    } else if ($format == 'txt') {
        // Tạo TXT
        $filename .= '.txt';
        $mimeType = 'text/plain; charset=utf-8';

        // Header TXT
        $data = "=== " . __('DANH SÁCH ĐƠN HÀNG XUẤT') . " ===\n";
        $data .= __('Thời gian xuất') . ": " . date('d/m/Y H:i:s') . "\n";
        $data .= __('Tổng số đơn hàng') . ": " . count($orders) . "\n";
        $data .= str_repeat("=", 80) . "\n\n";

        // Dữ liệu TXT
        $index = 1;
        foreach ($orders as $order) {
            $data .= "[$index] " . __('ĐƠN HÀNG') . " #" . $order['trans_id'] . "\n";
            $data .= "- " . __('Tên dịch vụ') . ": " . $order['service_name'] . "\n";
            $data .= "- " . __('Mã đơn hàng API') . ": " . ($order['order_id'] ? $order['order_id'] : 'N/A') . "\n";
            $data .= "- " . __('Số lượng') . ": " . format_cash($order['quantity']) . "\n";
            $data .= "- " . __('Liên kết') . ": " . ($order['link'] ? $order['link'] : 'N/A') . "\n";
            $data .= "- " . __('Bình luận') . ": " . ($order['comment'] ? $order['comment'] : 'N/A') . "\n";
            $data .= "- " . __('Số tiền thanh toán') . ": " . format_currency($order['pay']) . "\n";
            $data .= "- " . __('Thời gian tạo') . ": " . $order['created_at'] . "\n";
            $data .= str_repeat("-", 60) . "\n\n";
            $index++;
        }

        $data .= "=== " . __('KẾT THÚC DANH SÁCH') . " ===\n";
    }

    die(json_encode([
        'status' => 'success',
        'data' => $data,
        'filename' => $filename,
        'mimeType' => $mimeType,
        'total_orders' => count($orders)
    ]));
}

if ($_POST['action'] == 'getProductField') {
    if (checkPermission($getUser['admin'], 'view_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['id'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $id = check_string($_POST['id']);
    $field = $CMSNT->get_row("SELECT * FROM `product_fields` WHERE `id` = '" . $id . "'");

    if (!$field) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Trường không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $field
    ]));
}

if ($_POST['action'] == 'getProductPlan') {
    if (checkPermission($getUser['admin'], 'view_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ?", [$id]);

    if (!$plan) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Gói sản phẩm không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $plan
    ]));
}

if ($_POST['action'] == 'getPlanFields') {
    if (checkPermission($getUser['admin'], 'view_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['plan_id'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Plan ID không hợp lệ')
        ]));
    }

    $plan_id = check_string($_POST['plan_id']);
    $fields = $CMSNT->get_list("SELECT * FROM `product_fields` WHERE `plan_id` = '" . $plan_id . "' ORDER BY `sort_order` ASC, `id` ASC");

    die(json_encode([
        'status'    => 'success',
        'data'      => $fields
    ]));
}

if ($_POST['action'] == 'getProductStock') {
    if (checkPermission($getUser['admin'], 'view_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $stock = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `id` = ?", [$id]);

    if (!$stock) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Kho hàng không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $stock
    ]));
}

if ($_POST['action'] == 'getProductPlans') {
    if (checkPermission($getUser['admin'], 'view_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['product_id'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Product ID không hợp lệ')
        ]));
    }

    $product_id = validate_int($_POST['product_id'], 1);

    if ($product_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Product ID không hợp lệ')
        ]));
    }

    // Lấy tất cả các gói của sản phẩm (chỉ lấy gói đang active)
    $plans = $CMSNT->get_list_safe("SELECT `id`, `name` FROM `product_plans` WHERE `product_id` = ? AND `status` = 1 ORDER BY `name` ASC", [$product_id]);

    die(json_encode([
        'status'    => 'success',
        'data'      => $plans ? $plans : []
    ]));
}

// ==================== BLOG CATEGORY VIEW ====================
if ($_POST['action'] == 'getBlogCategory') {
    if (checkPermission($getUser['admin'], 'view_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$id]);

    if (!$category) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chuyên mục không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $category
    ]));
}

// ==================== BLOG POST VIEW ====================
if ($_POST['action'] == 'getBlog') {
    if (checkPermission($getUser['admin'], 'view_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` = ?", [$id]);

    if (!$blog) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bài viết không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $blog
    ]));
}

// Lấy thông tin banner
if ($_POST['action'] == 'getBanner') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['id'] ?? 0, 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $banner = $CMSNT->get_row_safe("SELECT * FROM `banners` WHERE `id` = ?", [$id]);

    if (!$banner) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Banner không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $banner
    ]));
}

// Lấy thông tin slider
if ($_POST['action'] == 'getSlider') {
    if (checkPermission($getUser['admin'], 'edit_sliders') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['id'] ?? 0, 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $slider = $CMSNT->get_row_safe("SELECT * FROM `sliders` WHERE `id` = ?", [$id]);

    if (!$slider) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Slider không tồn tại')
        ]));
    }

    die(json_encode([
        'status'    => 'success',
        'data'      => $slider
    ]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
