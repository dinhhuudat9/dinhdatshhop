<?php

define("IN_SITE", true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../config.php');
$CMSNT = new DB();

if (!isset($_GET['key'])) {
    die(__('Vui lòng nhập Key Cron Job vào đường dẫn'));
}
if (isset($_GET['key']) && $_GET['key'] != $CMSNT->site('key_cron_job')) {
    die(__('Key không hợp lệ'));
}


/* START CHỐNG SPAM */
if (time() > $CMSNT->site('check_time_cron_task')) {
    if (time() - $CMSNT->site('check_time_cron_task') < 3) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'check_time_cron_task' ");


foreach ($CMSNT->get_list(" SELECT * FROM `automations` ") as $task) {

    // XÓA ĐƠN HÀNG SẢN PHẨM (bảng product_orders) + stock liên quan
    if ($task['type'] == 'delete_order') {
        require_once(__DIR__ . '/../libs/services/ProductDeletionService.php');

        // Tính cutoff time
        $cutoff_time = time() - intval($task['schedule']);
        $cutoff_date = date('Y-m-d H:i:s', $cutoff_time);

        // Lấy danh sách đơn hàng cũ
        $oldOrders = $CMSNT->get_list_safe("SELECT id FROM `product_orders` WHERE UNIX_TIMESTAMP(created_at) <= ?", [$cutoff_time]);

        if (!empty($oldOrders)) {
            $orderIds = array_column($oldOrders, 'id');
            $deletionService = new ProductDeletionService($CMSNT);
            $result = $deletionService->deleteProductOrders($orderIds, true); // true = xóa stock hoàn toàn

            // Log nếu có xóa
            if ($result['deleted_orders'] > 0) {
                $days = intval($task['schedule']) / 86400; // Convert seconds to days
                $CMSNT->insert("logs", [
                    'user_id' => 0,
                    'ip' => myip(),
                    'device' => 'Cron Task',
                    'createdate' => gettime(),
                    'action' => sprintf(__('[Cron] Xóa %d đơn hàng sản phẩm cũ hơn %d ngày, %d tài khoản liên quan'), $result['deleted_orders'], round($days), $result['deleted_stock'])
                ]);
            }
        }
    }

    // XÓA LỊCH SỬ NẠP TIỀN
    if ($task['type'] == 'delete_recharge_history') {
        $CMSNT->remove('payment_bank_invoice', " " . time() . " - UNIX_TIMESTAMP(created_at) >= " . $task['schedule'] . " ");
        $CMSNT->remove('payment_crypto', " " . time() . " - UNIX_TIMESTAMP(create_gettime) >= " . $task['schedule'] . " ");
    }

    // XÓA USER KHÔNG PHÁT SINH GIAO DỊCH
    if ($task['type'] == 'delete_users_no_recharge') {
        // TÌM CÁC USER KHÔNG CÓ PHÁT SINH GIAO DỊCH VÀ ĐỦ THỜI GIAN
        foreach ($CMSNT->get_list(" SELECT * FROM `users` WHERE " . time() . " - UNIX_TIMESTAMP(create_date) >= " . $task['schedule'] . " AND `admin` = 0 AND `money` = 0 AND `total_money` = 0 ") as $user) {
            // KIỂM TRA USER CÓ PHÁT SINH GIAO DỊCH KHÔNG
            $checkRecharge = $CMSNT->num_rows(" SELECT * FROM `dongtien` WHERE `user_id` = '" . $user['id'] . "' ");
            if (isset($checkRecharge) && $checkRecharge == 0) {
                // XÓA USER KHÔNG CÓ CÓ PHÁT SINH GIAO DỊCH
                $isRemove = $CMSNT->remove('users', " `id` = '" . $user['id'] . "' ");
            }
        }
    }

    // XÓA NHẬT KÝ BOT TELEGRAM
    if ($task['type'] == 'delete_telegram_log') {
        $CMSNT->remove('bot_telegram_logs', " " . time() . " - UNIX_TIMESTAMP(created_at) >= " . $task['schedule'] . " ");
    }
}
