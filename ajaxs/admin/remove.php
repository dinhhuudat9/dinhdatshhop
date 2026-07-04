<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/../../libs/services/ProductDeletionService.php');

if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => 'The Request Not Found'
    ]);
    die($data);
}
if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}

// Kiểm tra CSRF token cho tất cả request
checkCSRFAjax();


// Xóa logs cũ
if ($_POST['action'] == 'cleanupLogs') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    // Chặn demo site cho action xóa
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }

    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;

    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Đếm số lượng trước khi xóa
    $count = $CMSNT->num_rows_safe("SELECT id FROM `logs` WHERE `createdate` < ?", [$cutoff_date]);

    if ($count == 0) {
        die(json_encode([
            'status' => 'success',
            'msg' => __('Không có nhật ký nào cần xóa'),
            'deleted' => 0
        ]));
    }

    // Thực hiện xóa sử dụng phương thức remove() của DB class
    $result = $CMSNT->remove("logs", "`createdate` < ?", [$cutoff_date]);

    if ($result) {
        // Ghi log (sử dụng insert trực tiếp để tránh ghi log cho chính hành động này)
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Dọn dẹp nhật ký: Xóa %d bản ghi cũ hơn %d ngày'), $count, $days)
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Dọn dẹp nhật ký: Xóa %d bản ghi'), $count), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Đã xóa thành công %d bản ghi nhật ký cũ hơn %d ngày'), $count, $days),
            'deleted' => $count
        ]));
    } else {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Không thể xóa nhật ký. Vui lòng thử lại.')
        ]));
    }
}

if ($_POST['action'] == 'cleanupTransactions') {
    if (checkPermission($getUser['admin'], 'edit_transactions') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `dongtien` WHERE `thoigian` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có bản ghi nào cần xóa'), 'deleted' => 0]));
    }
    $result = $CMSNT->remove("dongtien", "`thoigian` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp biến động số dư: Xóa %d bản ghi cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d bản ghi biến động số dư cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

if ($_POST['action'] == 'cleanupBotTelegramLogs') {
    if (checkPermission($getUser['admin'], 'edit_bot_telegram_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `bot_telegram_logs` WHERE `created_at` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có bản ghi nào cần xóa'), 'deleted' => 0]));
    }
    $result = $CMSNT->remove("bot_telegram_logs", "`created_at` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp nhật ký Bot Telegram: Xóa %d bản ghi cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d bản ghi nhật ký Bot Telegram cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

if ($_POST['action'] == 'cleanupBlockIP') {
    if (checkPermission($getUser['admin'], 'edit_block_ip') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $count = $CMSNT->num_rows_safe("SELECT id FROM `block_ip` WHERE `create_gettime` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có IP nào cần xóa'), 'deleted' => 0]));
    }
    $result = $CMSNT->remove("block_ip", "`create_gettime` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp Block IP: Xóa %d IP cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d IP bị chặn cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

// ==================== SUPPORT TICKETS ====================
if ($_POST['action'] == 'cleanupTickets') {
    if (checkPermission($getUser['admin'], 'edit_ticket') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Chỉ xóa tickets đã đóng
    $count = $CMSNT->num_rows_safe("SELECT id FROM `support_tickets` WHERE `status` = 'closed' AND `created_at` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có ticket đã đóng nào cần xóa'), 'deleted' => 0]));
    }

    // Lấy danh sách ticket ID để xóa messages liên quan
    $tickets_to_delete = $CMSNT->get_list_safe("SELECT id FROM `support_tickets` WHERE `status` = 'closed' AND `created_at` < ?", [$cutoff_date]);
    $ticket_ids = array_column($tickets_to_delete, 'id');

    // Xóa messages của các tickets
    if (!empty($ticket_ids)) {
        $placeholders = str_repeat('?,', count($ticket_ids) - 1) . '?';
        $CMSNT->remove("support_messages", "`ticket_id` IN ($placeholders)", $ticket_ids);
    }

    // Xóa tickets
    $result = $CMSNT->remove("support_tickets", "`status` = 'closed' AND `created_at` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp Support Tickets: Xóa %d tickets đã đóng cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d tickets đã đóng cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

// ==================== PRODUCT ORDERS ====================
if ($_POST['action'] == 'cleanupProductOrders') {
    if (checkPermission($getUser['admin'], 'edit_orders_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Lấy danh sách đơn hàng cũ
    $oldOrders = $CMSNT->get_list_safe("SELECT id FROM `product_orders` WHERE `created_at` < ?", [$cutoff_date]);
    if (empty($oldOrders)) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có đơn hàng nào cần xóa'), 'deleted' => 0]));
    }

    $orderIds = array_column($oldOrders, 'id');

    // Sử dụng ProductDeletionService để xóa orders + stock liên quan
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteProductOrders($orderIds, true); // true = xóa stock hoàn toàn

    if ($result['success']) {
        $logAction = sprintf(__('Dọn dẹp Đơn hàng: Xóa %d đơn hàng cũ hơn %d ngày'), $result['deleted_orders'], $days);
        if ($result['deleted_stock'] > 0) {
            $logAction .= sprintf(__(', xóa %d tài khoản liên quan'), $result['deleted_stock']);
        }

        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => $logAction]);

        $msg = sprintf(__('Đã xóa thành công %d đơn hàng cũ hơn %d ngày'), $result['deleted_orders'], $days);
        if ($result['deleted_stock'] > 0) {
            $msg .= sprintf(__(', %d tài khoản liên quan'), $result['deleted_stock']);
        }

        die(json_encode(['status' => 'success', 'msg' => $msg, 'deleted' => $result['deleted_orders']]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.') . ($deletionService->getFirstError() ? ': ' . $deletionService->getFirstError() : '')]));
    }
}

// ==================== PRODUCT STOCK ====================
if ($_POST['action'] == 'cleanupProductStock') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    // Chỉ xóa kho hàng đã bán (status = 0)
    $count = $CMSNT->num_rows_safe("SELECT id FROM `product_stock` WHERE `status` = 0 AND `updated_at` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có kho hàng đã bán nào cần xóa'), 'deleted' => 0]));
    }

    // Xóa kho hàng đã bán
    $result = $CMSNT->remove("product_stock", "`status` = 0 AND `updated_at` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp Kho hàng: Xóa %d kho hàng đã bán cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d kho hàng đã bán cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

// ==================== TELEGRAM QUEUE ====================
if ($_POST['action'] == 'cleanupTelegramQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $count = $CMSNT->num_rows_safe("SELECT id FROM `telegram_queue` WHERE `created_at` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có bản ghi Telegram Queue nào cần xóa'), 'deleted' => 0]));
    }

    $result = $CMSNT->remove("telegram_queue", "`created_at` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp Telegram Queue: Xóa %d bản ghi cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d bản ghi Telegram Queue cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

// ==================== EMAIL QUEUE ====================
if ($_POST['action'] == 'cleanupEmailQueue') {
    if (checkPermission($getUser['admin'], 'edit_logs') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
    }
    $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
    if ($days < 1) $days = 1;
    if ($days > 365) $days = 365;
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $count = $CMSNT->num_rows_safe("SELECT id FROM `email_queue` WHERE `created_at` < ?", [$cutoff_date]);
    if ($count == 0) {
        die(json_encode(['status' => 'success', 'msg' => __('Không có bản ghi Email Queue nào cần xóa'), 'deleted' => 0]));
    }

    $result = $CMSNT->remove("email_queue", "`created_at` < ?", [$cutoff_date]);
    if ($result) {
        $CMSNT->insert("logs", ['user_id' => $getUser['id'], 'ip' => myip(), 'device' => getUserAgent(), 'createdate' => gettime(), 'action' => sprintf(__('Dọn dẹp Email Queue: Xóa %d bản ghi cũ hơn %d ngày'), $count, $days)]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d bản ghi Email Queue cũ hơn %d ngày'), $count, $days), 'deleted' => $count]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể xóa. Vui lòng thử lại.')]));
    }
}

if ($_POST['action'] == 'removeSession') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['session_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    $session_id = check_string($_POST['session_id']);
    // Kiểm tra session có tồn tại và thuộc về user hiện tại
    if (!$session = $CMSNT->get_row("SELECT * FROM `active_sessions` WHERE `id` = '$session_id' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Phiên đăng nhập không tồn tại')]));
    }
    if (!$user = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '" . $session['user_id'] . "' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('User không tồn tại')]));
    }
    // Xóa phiên
    if ($CMSNT->remove("active_sessions", " `id` = '$session_id' ")) {
        // Ghi log
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Xóa phiên đăng nhập của User %s (ID %s)'), $user['username'], $user['id'])
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Xóa phiên đăng nhập của User %s (ID %s)'), $user['username'], $user['id']), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode(['status' => 'success', 'msg' => __('Đăng xuất phiên thành công')]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Đăng xuất thất bại')]));
    }
}
if ($_POST['action'] == 'removeInvoiceBank') {
    if (checkPermission($getUser['admin'], 'edit_recharge_bank_invoice') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không tồn tại trong hệ thống')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `payment_bank_invoice` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Hóa đơn không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("payment_bank_invoice", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa thành công hóa đơn') . ' (' . $row['trans_id'] . ')'
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa thành công hóa đơn') . ' (' . $row['trans_id'] . ')'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'bulkDeleteInvoiceBank') {
    if (checkPermission($getUser['admin'], 'edit_recharge_bank_invoice') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một hóa đơn để xóa')
        ]));
    }

    $ids = array_filter(array_map('intval', $_POST['ids']));

    if (empty($ids)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    // Validate số lượng để tránh xóa quá nhiều
    if (count($ids) > 1000) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chỉ có thể xóa tối đa 1000 hóa đơn cùng lúc')
        ]));
    }

    $deleted_count = 0;
    $failed_count = 0;
    $trans_ids = [];

    foreach ($ids as $id) {
        // Kiểm tra hóa đơn có tồn tại
        $invoice = $CMSNT->get_row_safe("SELECT * FROM `payment_bank_invoice` WHERE `id` = ?", [$id]);

        if ($invoice) {
            $isRemove = $CMSNT->remove("payment_bank_invoice", "`id` = ?", [$id]);

            if ($isRemove) {
                $deleted_count++;
                $trans_ids[] = $invoice['trans_id'];
            } else {
                $failed_count++;
            }
        } else {
            $failed_count++;
        }
    }

    // Ghi log
    if ($deleted_count > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Xóa hàng loạt %d hóa đơn nạp tiền'), $deleted_count)
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Xóa hàng loạt %d hóa đơn nạp tiền'), $deleted_count), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
    }

    // Tạo thông báo kết quả
    $msg = sprintf(__('Đã xóa thành công %d hóa đơn'), $deleted_count);
    if ($failed_count > 0) {
        $msg .= sprintf(__(', %d hóa đơn xóa thất bại'), $failed_count);
    }

    die(json_encode([
        'status'    => 'success',
        'msg'       => $msg,
        'deleted'   => $deleted_count,
        'failed'    => $failed_count
    ]));
}


if ($_POST['action'] == 'remove_payment_manual') {
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không tồn tại trong hệ thống')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `payment_manual` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item does not exist in the system')
        ]));
    }
    $isRemove = $CMSNT->remove("payment_manual", " `id` = '$id' ");
    if ($isRemove) {
        // XÓA LOGO BANK
        unlink("../../" . $row['icon']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá trang nạp tiền thủ công') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá trang nạp tiền thủ công') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa thành công'
        ]);
        die($data);
    }
}



if ($_POST['action'] == 'removeTaskAutomation') {
    if (checkPermission($getUser['admin'], 'edit_automations') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `automations` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("automations", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Task (' . $row['name'] . ')'
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa dữ liệu thành công')
        ]);
        die($data);
    }
}




if ($_POST['action'] == 'removeBlockIP') {
    if (checkPermission($getUser['admin'], 'edit_block_ip') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `block_ip` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("block_ip", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Block IP (' . $row['ip'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Remove Block IP (' . $row['ip'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa IP thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeMultipleBlockIP') {
    if (checkPermission($getUser['admin'], 'edit_block_ip') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có IP nào được chọn')]));
    }

    $ids = $_POST['ids'];
    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $removeCount = 0;
    $errorCount = 0;
    $ipDetails = [];

    foreach ($ids as $id) {
        $id = check_string($id);
        if (empty($id)) continue;

        // Kiểm tra xem IP có tồn tại không
        if (!$row = $CMSNT->get_row("SELECT * FROM `block_ip` WHERE `id` = '$id'")) {
            $errorCount++;
            continue;
        }

        // Lưu thông tin IP để ghi log
        $ipDetails[] = $row['ip'];

        // Tiến hành xóa
        if ($CMSNT->remove("block_ip", " `id` = '$id'")) {
            $removeCount++;
        } else {
            $errorCount++;
        }
    }

    if ($removeCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa hàng loạt') . ' ' . $removeCount . ' Block IP (' .
                implode(', ', array_slice($ipDetails, 0, 5)) .
                (count($ipDetails) > 5 ? '...' : '') . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa hàng loạt') . ' ' . $removeCount . ' Block IP', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $removeCount . ' IP' .
                ($errorCount > 0 ? ', ' . $errorCount . ' IP bị lỗi' : '')
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Không có IP nào được xóa')]));
}


// Xóa tất cả gói dịch vụ (product_plans) + trường (fields) của một supplier
if ($_POST['action'] == 'removePlansOnly') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $supplier_id = validate_int($_POST['id'], 1);
    if ($supplier_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ?", [$supplier_id]);
    if (!$supplier) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Supplier không tồn tại trong hệ thống')
        ]));
    }

    // Sử dụng ProductDeletionService để xóa cascade
    $deletionService = new ProductDeletionService($CMSNT);

    // Lấy danh sách plan IDs của supplier
    $plans = $CMSNT->get_list_safe("SELECT `id` FROM `product_plans` WHERE `supplier_id` = ?", [$supplier_id]);
    $planIds = array_column($plans, 'id');

    if (empty($planIds)) {
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Không có gói sản phẩm nào để xóa')
        ]));
    }

    // Xóa tất cả plans + fields
    $result = $deletionService->deletePlans($planIds, true);

    if (!$result['success']) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Lỗi khi xóa: ') . $deletionService->getFirstError()
        ]));
    }

    // Ghi log
    $logAction = sprintf(
        __('Xóa gói sản phẩm API') . ' (%s - %d gói, %d trường)',
        $supplier['domain'],
        $result['deleted_plans'],
        $result['deleted_fields']
    );

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => $logAction
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', $logAction, $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status'    => 'success',
        'msg'       => sprintf(
            __('Đã xóa %d gói, %d trường của %s'),
            $result['deleted_plans'],
            $result['deleted_fields'],
            $supplier['domain']
        )
    ]));
}

// Xóa tất cả sản phẩm (products) + gói (plans) + trường (fields) của một supplier
if ($_POST['action'] == 'removeProductsOnly') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $supplier_id = validate_int($_POST['id'], 1);
    if ($supplier_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ?", [$supplier_id]);
    if (!$supplier) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Supplier không tồn tại trong hệ thống')
        ]));
    }

    // Sử dụng ProductDeletionService để xóa cascade
    $deletionService = new ProductDeletionService($CMSNT);

    // Lấy danh sách product IDs của supplier
    $products = $CMSNT->get_list_safe("SELECT `id` FROM `products` WHERE `supplier_id` = ?", [$supplier_id]);
    $productIds = array_column($products, 'id');

    if (empty($productIds)) {
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Không có sản phẩm nào để xóa')
        ]));
    }

    // Xóa tất cả products + plans + fields
    $result = $deletionService->deleteProducts($productIds, true);

    if (!$result['success']) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Lỗi khi xóa: ') . $deletionService->getFirstError()
        ]));
    }

    // Ghi log
    $logAction = sprintf(
        __('Xóa sản phẩm API') . ' (%s - %d sản phẩm, %d gói, %d trường)',
        $supplier['domain'],
        $result['deleted_products'],
        $result['deleted_plans'],
        $result['deleted_fields']
    );

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => $logAction
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', $logAction, $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status'    => 'success',
        'msg'       => sprintf(
            __('Đã xóa %d sản phẩm, %d gói, %d trường của %s'),
            $result['deleted_products'],
            $result['deleted_plans'],
            $result['deleted_fields'],
            $supplier['domain']
        )
    ]));
}

// Xóa tất cả chuyên mục (categories) của một supplier
if ($_POST['action'] == 'removeCategoriesOnly') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $supplier_id = validate_int($_POST['id'], 1);
    if ($supplier_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID không hợp lệ')
        ]));
    }

    $supplier = $CMSNT->get_row_safe("SELECT * FROM `suppliers` WHERE `id` = ?", [$supplier_id]);
    if (!$supplier) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Supplier không tồn tại trong hệ thống')
        ]));
    }

    // Đếm số chuyên mục sẽ xóa
    $count = $CMSNT->num_rows_safe("SELECT id FROM `categories` WHERE `supplier_id` = ?", [$supplier_id]);

    // Xóa category ID khỏi category_ids của sản phẩm (multi-category support)
    $categories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `supplier_id` = ?", [$supplier_id]);
    foreach ($categories as $cat) {
        // Tìm tất cả sản phẩm có category này trong category_ids
        $products = $CMSNT->get_list_safe(
            "SELECT * FROM `products` WHERE FIND_IN_SET(?, `category_ids`) > 0",
            [$cat['id']]
        );

        // Xóa category ID khỏi danh sách category_ids của mỗi sản phẩm
        foreach ($products as $prod) {
            $catIds = array_filter(explode(',', $prod['category_ids']));
            $catIds = array_diff($catIds, [$cat['id']]);
            $newCatIds = implode(',', $catIds);
            $CMSNT->update_safe("products", ['category_ids' => $newCatIds], "`id` = ?", [$prod['id']]);
        }

        // Xóa icon nếu có
        if (!empty($cat['icon'])) {
            $imagePath = "../../" . $cat['icon'];
            if (file_exists($imagePath) && is_file($imagePath)) {
                @unlink($imagePath);
            }
        }
    }

    // Xóa tất cả chuyên mục của supplier này
    $CMSNT->remove("categories", "`supplier_id` = ?", [$supplier_id]);

    // Ghi log
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Xóa chuyên mục API') . ' (' . $supplier['domain'] . ' - ' . $count . ' chuyên mục)'
    ]);

    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Xóa chuyên mục API') . ' (' . $supplier['domain'] . ' - ' . $count . ' chuyên mục)', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);

    die(json_encode([
        'status'    => 'success',
        'msg'       => sprintf(__('Đã xóa %d chuyên mục của %s'), $count, $supplier['domain'])
    ]));
}






if ($_POST['action'] == 'removeOrder') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$product_order = $CMSNT->get_row("SELECT * FROM `orders` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Đơn hàng không tồn tại trong hệ thống'
        ]));
    }
    $isRemove = $CMSNT->remove("orders", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Order (' . $product_order['trans_id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Order (' . $product_order['trans_id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa đơn hàng thành công!')
        ]);
        die($data);
    }
}
if ($_POST['action'] == 'removeRole') {
    if (checkPermission($getUser['admin'], 'edit_role') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `admin_role` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'Dữ liệu không tồn tại'
        ]));
    }
    $isRemove = $CMSNT->remove("admin_role", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Delete Role (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}',  'Delete Role (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa Role thành công !'
        ]);
        die($data);
    }
}


if ($_POST['action'] == 'removeImageProduct') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `products` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => 'ID sản phẩm không tồn tại trong hệ thống'
        ]));
    }
    $image = check_string($_POST['image']);
    unlink("../../" . dirImageProduct($image));
    // Xóa giá trị cụ thể khỏi biến $images
    $images = str_replace($image, '', $row['images']);
    // Loại bỏ dấu xuống dòng trống nếu có
    $images = preg_replace('/^\h*\v+/m', '', $images);
    $CMSNT->update('products', [
        'images'    => $images
    ], " `id` = '" . $row['id'] . "' ");
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => 'Delete Image Product (' . $row['name'] . ' ID ' . $row['id'] . ')'
    ]);
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', 'Delete Image Product (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die(json_encode([
        'status'    => 'success',
        'msg'       => __('Xóa sản phẩm thành công')
    ]));
}


if ($_POST['action'] == 'removeProduct') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'] ?? 0, 1);
    if (!$id) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID sản phẩm không hợp lệ')
        ]));
    }

    // Sử dụng ProductDeletionService
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteProduct($id, true, true);

    if ($result['success']) {
        $product = $result['product'];

        // Ghi log
        $logAction = __('Xoá sản phẩm') . ' (' . $product['name'] . ' ID ' . $product['id'] . ')';
        if ($result['deleted_plans'] > 0) {
            $logAction .= ' + ' . $result['deleted_plans'] . ' gói';
        }
        if ($result['deleted_fields'] > 0) {
            $logAction .= ', ' . $result['deleted_fields'] . ' trường';
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $logAction
        ]);

        // Gửi thông báo admin
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá sản phẩm') . ' (' . $product['name'] . ' ID ' . $product['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        $msg = __('Xóa sản phẩm thành công!');
        if ($result['deleted_plans'] > 0 || $result['deleted_fields'] > 0) {
            $msg .= sprintf(__(' Đã xóa %d gói, %d trường.'), $result['deleted_plans'], $result['deleted_fields']);
        }

        die(json_encode([
            'status'    => 'success',
            'msg'       => $msg
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Lỗi khi xóa sản phẩm: ') . $deletionService->getFirstError()
        ]));
    }
}



if ($_POST['action'] == 'removeCategory') {
    if (checkPermission($getUser['admin'], 'edit_category') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = (int) check_string($_POST['id']);

    // Kiểm tra chuyên mục tồn tại
    $row = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$id]);
    if (!$row) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID chuyên mục không tồn tại trong hệ thống')
        ]));
    }

    // Kiểm tra nếu là chuyên mục cha thì phải xóa hết chuyên mục con trước
    if ($row['parent_id'] == 0) {
        $childCount = $CMSNT->num_rows_safe(
            "SELECT id FROM `categories` WHERE `parent_id` = ?",
            [$row['id']]
        );
        if ($childCount > 0) {
            die(json_encode([
                'status'    => 'error',
                'msg'       => __('Bạn cần xóa hết chuyên mục con của chuyên mục này trước khi xóa chuyên mục cha')
            ]));
        }
    }

    // Sử dụng ProductDeletionService để xóa chuyên mục
    $deletionService = new ProductDeletionService($CMSNT);

    // deleteProducts = false: chỉ xóa category khỏi category_ids của sản phẩm, không xóa sản phẩm
    $result = $deletionService->deleteCategory($id, true, false, true);

    if ($result['success']) {
        // Log action
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá chuyên mục') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá chuyên mục') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa chuyên mục thành công')
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => $deletionService->getFirstError() ?: __('Xóa chuyên mục thất bại')
        ]));
    }
}

// Xóa hàng loạt sản phẩm
if ($_POST['action'] == 'bulkDeleteProducts') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một sản phẩm để xóa')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $validIds = [];
    $deletedNames = [];

    // Kiểm tra từng sản phẩm có tồn tại không
    foreach ($ids as $id) {
        if ($id > 0) {
            $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$id]);
            if ($product) {
                $validIds[] = $id;
                $deletedNames[] = $product['name'];
            }
        }
    }

    if (empty($validIds)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không tìm thấy sản phẩm nào để xóa')
        ]));
    }

    // Sử dụng ProductDeletionService để xóa hàng loạt
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteProducts($validIds, true);

    if ($result['success'] && $result['deleted_products'] > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Xóa hàng loạt %d sản phẩm: %s'), $result['deleted_products'], implode(', ', array_slice($deletedNames, 0, 5)) . (count($deletedNames) > 5 ? '...' : ''))
        ]);

        die(json_encode([
            'status'    => 'success',
            'msg'       => sprintf(__('Đã xóa thành công %d sản phẩm'), $result['deleted_products'])
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => $deletionService->getFirstError() ?: __('Không thể xóa sản phẩm nào')
        ]));
    }
}

// Xóa đơn hàng sản phẩm (single)
if ($_POST['action'] == 'deleteProductOrder') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = intval(check_string($_POST['id']));
    if ($id <= 0) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID đơn hàng không hợp lệ')
        ]));
    }

    // Kiểm tra đơn hàng tồn tại
    $order = $CMSNT->get_row_safe("SELECT * FROM `product_orders` WHERE `id` = ?", [$id]);
    if (!$order) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Đơn hàng không tồn tại')
        ]));
    }

    // Sử dụng ProductDeletionService
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteProductOrders([$id], true); // true = xóa stock hoàn toàn

    if ($result['success'] && $result['deleted_orders'] > 0) {
        $logAction = sprintf(__('Xóa đơn hàng sản phẩm #%s'), $order['trans_id']);
        if ($result['deleted_stock'] > 0) {
            $logAction .= sprintf(__(', xóa %d tài khoản liên quan'), $result['deleted_stock']);
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $logAction
        ]);

        $msg = sprintf(__('Đã xóa đơn hàng #%s thành công'), $order['trans_id']);
        if ($result['deleted_stock'] > 0) {
            $msg .= sprintf(__(', %d tài khoản liên quan'), $result['deleted_stock']);
        }

        die(json_encode([
            'status'    => 'success',
            'msg'       => $msg
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không thể xóa đơn hàng') . ($deletionService->getFirstError() ? ': ' . $deletionService->getFirstError() : '')
        ]));
    }
}

// Xóa hàng loạt đơn hàng sản phẩm

if ($_POST['action'] == 'bulkDeleteProductOrders') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một đơn hàng để xóa')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $validIds = array_filter($ids, function ($id) {
        return $id > 0;
    });

    if (empty($validIds)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Danh sách ID không hợp lệ')
        ]));
    }

    // Lấy trans_id để log
    $deletedTransIds = [];
    foreach ($validIds as $id) {
        $order = $CMSNT->get_row_safe("SELECT `trans_id` FROM `product_orders` WHERE `id` = ?", [$id]);
        if ($order) {
            $deletedTransIds[] = $order['trans_id'];
        }
    }

    // Sử dụng ProductDeletionService
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteProductOrders($validIds, true); // true = xóa stock hoàn toàn

    if ($result['success'] && $result['deleted_orders'] > 0) {
        $logAction = sprintf(__('Xóa hàng loạt %d đơn hàng sản phẩm: %s'), $result['deleted_orders'], implode(', ', array_slice($deletedTransIds, 0, 5)) . (count($deletedTransIds) > 5 ? '...' : ''));
        if ($result['deleted_stock'] > 0) {
            $logAction .= sprintf(__(', xóa %d tài khoản liên quan'), $result['deleted_stock']);
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $logAction
        ]);

        $msg = sprintf(__('Đã xóa thành công %d đơn hàng'), $result['deleted_orders']);
        if ($result['deleted_stock'] > 0) {
            $msg .= sprintf(__(', %d tài khoản liên quan'), $result['deleted_stock']);
        }

        die(json_encode([
            'status'    => 'success',
            'msg'       => $msg
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không thể xóa đơn hàng nào') . ($deletionService->getFirstError() ? ': ' . $deletionService->getFirstError() : '')
        ]));
    }
}

if ($_POST['action'] == 'removeProduct') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = (int) check_string($_POST['id']);

    // Kiểm tra sản phẩm tồn tại
    $row = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$id]);
    if (!$row) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID sản phẩm không tồn tại trong hệ thống')
        ]));
    }

    // Sử dụng ProductDeletionService để xóa sản phẩm
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteProduct($id, true, true);

    if ($result['success']) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá sản phẩm') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá sản phẩm') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa sản phẩm thành công')
        ]));
    }

    die(json_encode([
        'status'    => 'error',
        'msg'       => $deletionService->getFirstError() ?: __('Xóa sản phẩm thất bại')
    ]));
}

if ($_POST['action'] == 'removeProductField') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'] ?? 0, 1);
    if (!$id) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID trường không hợp lệ')
        ]));
    }

    // Lấy thông tin field trước để log
    $field = $CMSNT->get_row_safe("SELECT * FROM `product_fields` WHERE `id` = ?", [$id]);
    if (!$field) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Trường không tồn tại trong hệ thống')
        ]));
    }

    // Sử dụng ProductDeletionService
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteField($id);

    if ($result) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá trường sản phẩm') . ' (' . $field['label'] . ' ID ' . $field['id'] . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá trường sản phẩm') . ' (' . $field['label'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa trường thành công')
        ]));
    }
    die(json_encode([
        'status'    => 'error',
        'msg'       => __('Xóa trường thất bại: ') . $deletionService->getFirstError()
    ]));
}


if ($_POST['action'] == 'removeProductPlan') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'] ?? 0, 1);
    if (!$id) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID gói sản phẩm không hợp lệ')
        ]));
    }

    // Sử dụng ProductDeletionService
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deletePlan($id, true, true);

    if ($result['success']) {
        $plan = $result['plan'];

        // Ghi log
        $logAction = __('Xoá gói sản phẩm') . ' (' . $plan['name'] . ' ID ' . $plan['id'] . ')';
        if ($result['deleted_fields'] > 0) {
            $logAction .= ' + ' . $result['deleted_fields'] . ' trường';
        }
        if ($result['deleted_stock'] > 0) {
            $logAction .= ', ' . $result['deleted_stock'] . ' stock';
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $logAction
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá gói sản phẩm') . ' (' . $plan['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        $msg = __('Xóa gói sản phẩm thành công!');
        if ($result['deleted_fields'] > 0 || $result['deleted_stock'] > 0) {
            $msg .= sprintf(__(' Đã xóa %d trường, %d stock.'), $result['deleted_fields'], $result['deleted_stock']);
        }

        die(json_encode([
            'status'    => 'success',
            'msg'       => $msg
        ]));
    }
    die(json_encode([
        'status'    => 'error',
        'msg'       => __('Xóa gói sản phẩm thất bại: ') . $deletionService->getFirstError()
    ]));
}


// Xóa hàng loạt gói sản phẩm
if ($_POST['action'] == 'bulkDeleteProductPlans') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một gói sản phẩm')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $validIds = array_filter($ids, function ($id) {
        return $id > 0;
    });

    if (empty($validIds)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Danh sách ID không hợp lệ')
        ]));
    }

    // Lấy tên các gói trước khi xóa để log
    $deletedNames = [];
    foreach ($validIds as $id) {
        $plan = $CMSNT->get_row_safe("SELECT `name` FROM `product_plans` WHERE `id` = ?", [$id]);
        if ($plan) {
            $deletedNames[] = $plan['name'];
        }
    }

    // Sử dụng ProductDeletionService để xóa hàng loạt
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deletePlans($validIds, true);

    if ($result['success'] && $result['deleted_plans'] > 0) {
        $logAction = sprintf(__('Xóa hàng loạt %d gói sản phẩm: %s'), $result['deleted_plans'], implode(', ', array_slice($deletedNames, 0, 5)) . (count($deletedNames) > 5 ? '...' : ''));
        if ($result['deleted_stock'] > 0) {
            $logAction .= sprintf(__(', xóa %d stock chưa bán'), $result['deleted_stock']);
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $logAction
        ]);

        $msg = sprintf(__('Đã xóa thành công %d gói sản phẩm'), $result['deleted_plans']);
        if ($result['deleted_stock'] > 0) {
            $msg .= sprintf(__(', %d stock chưa bán'), $result['deleted_stock']);
        }

        die(json_encode([
            'status'    => 'success',
            'msg'       => $msg
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => $deletionService->getFirstError() ?: __('Không thể xóa gói sản phẩm nào')
        ]));
    }
}

if ($_POST['action'] == 'removeCategoryBlog') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `post_category` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID chuyên mục không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("post_category", " `id` = '$id' ");
    if ($isRemove) {
        unlink("../../" . $row['icon']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá chuyên mục bài viết') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá chuyên mục bài viết') . ' (' . $row['name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa chuyên mục thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removePost') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `posts` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bài viết không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("posts", " `id` = '$id' ");
    if ($isRemove) {
        unlink("../../" . $row['image']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá bài viết') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá bài viết') . ' (' . $row['title'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa bài viết thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeBank') {
    if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `banks` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item does not exist in the system')
        ]));
    }
    $isRemove = $CMSNT->remove("banks", " `id` = '$id' ");
    if ($isRemove) {
        // XÓA LOGO BANK
        unlink("../../" . $row['image']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá ngân hàng') . ' (' . $row['short_name'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá ngân hàng') . ' (' . $row['short_name'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa ngân hàng thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    $row = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$id' ");
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    if ($row['lang_default'] == 1) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('You cannot delete the system default language')
        ]);
        die($data);
    }
    $CMSNT->remove("translate", " `lang_id` = '" . $row['id'] . "' ");
    $isRemove = $CMSNT->remove("languages", " `id` = '$id' ");
    if ($isRemove) {
        unlink("../../" . $row['image']);

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá ngôn ngữ') . ' (' . $row['lang'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá ngôn ngữ') . ' (' . $row['lang'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Successful language removal')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeCurrency') {
    if (checkPermission($getUser['admin'], 'edit_currency') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item does not exist in the system')
        ]));
    }
    $isRemove = $CMSNT->remove("currencies", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá tiền tệ') . ' (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá tiền tệ') . ' (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa item thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeWithdraw') {
    if (checkPermission($getUser['admin'], 'edit_withdraw_affiliate') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không được để trống')]));
    }
    $id = check_string($_POST['id']);
    $row = $CMSNT->get_row("SELECT * FROM `aff_withdraw` WHERE `id` = '$id' ");
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('ID item không tồn tại trong hệ thống')
        ]);
        die($data);
    }
    $isRemove = $CMSNT->remove("aff_withdraw", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá yêu cầu rút tiền hoa hồng') . ' #' . $row['trans_id'] . ' - ' . format_currency($row['amount']) . ' - ' . $row['status']
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá yêu cầu rút tiền hoa hồng') . ' #' . $row['trans_id'] . ' - ' . format_currency($row['amount']) . ' - ' . $row['status'], $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xoá thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeUser') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không được để trống')]));
    }
    $id = check_string($_POST['id']);
    $row = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '$id' ");
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => 'ID user không tồn tại trong hệ thống'
        ]);
        die($data);
    }
    if ($getUser['admin'] != 99999 && $row['admin'] == 99999) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isRemove = $CMSNT->remove("users", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá tài khoản') . ' (' . $row['username'] . ' ID ' . $row['id'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá tài khoản') . ' (' . $row['username'] . ' ID ' . $row['id'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => 'Xóa người dùng thành công'
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'removeTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    $row = $CMSNT->get_row("SELECT * FROM `translate` WHERE `id` = '$id' ");
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('The ID to delete does not exist')
        ]);
        die($data);
    }
    $isRemove = $CMSNT->remove("translate", " `name` = '" . $row['name'] . "' ");
    //$isRemove = $CMSNT->remove("translate", " `id` = '$id' ");
    if ($isRemove) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá nội dung ngôn ngữ') . ' (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá nội dung ngôn ngữ') . ' (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Language removal successful')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'email_campaigns') {
    if (checkPermission($getUser['admin'], 'edit_email_campaigns') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $id = check_string($_POST['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `email_campaigns` WHERE `id` = '$id' ")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Item không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->remove("email_campaigns", " `id` = '$id' ");
    if ($isRemove) {
        $CMSNT->remove('email_sending', " `camp_id` = '" . $row['id'] . "' ");

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xoá chiến dịch Email Marketing') . ' (' . $row['name'] . ')'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xoá chiến dịch Email Marketing') . ' (' . $row['name'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa item thành công')
        ]);
        die($data);
    }
}



// Xóa hàng loạt chuyên mục con
if ($_POST['action'] == 'bulkRemoveCategorySub') {
    if (checkPermission($getUser['admin'], 'edit_category') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['productIds'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có chuyên mục con nào được chọn')]));
    }

    $productIds = json_decode($_POST['productIds'], true);
    if (!is_array($productIds) || empty($productIds)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $removeCount = 0;
    $errorCount = 0;

    foreach ($productIds as $id) {
        $id = intval($id);
        if ($id <= 0) continue;

        // Kiểm tra xem dịch vụ có tồn tại không
        if (!$categorySub = $CMSNT->get_row("SELECT * FROM `categories` WHERE `id` = '$id'")) {
            $errorCount++;
            continue;
        }

        // Tiến hành xóa
        if ($CMSNT->remove("categories", " `id` = '$id' ")) {
            if (!empty($categorySub['icon'])) {
                $imagePath = "../../" . $categorySub['icon'];
                if (file_exists($imagePath) && is_file($imagePath)) {
                    unlink($imagePath); // Xóa icon chuyên mục nếu có
                }
            }
            $removeCount++;
        } else {
            $errorCount++;
        }
    }

    if ($removeCount > 0) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa hàng loạt') . ' ' . $removeCount . ' ' . __('chuyên mục con')
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa hàng loạt') . ' ' . $removeCount . ' ' . __('chuyên mục con'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $removeCount . ' ' . __('chuyên mục con') . ($errorCount > 0 ? ', ' . $errorCount . ' ' . __('chuyên mục con lỗi') : "")
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => 'Không có chuyên mục con nào được xóa']));
}

// Xóa nhiều đơn hàng cùng lúc
if ($_POST['action'] == 'bulkRemoveOrders') {
    if (checkPermission($getUser['admin'], 'delete_order_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không có đơn hàng nào được chọn')]));
    }

    $ids = json_decode($_POST['ids'], true);
    if (!is_array($ids) || empty($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $removeCount = 0;
    $errorCount = 0;
    $orderDetails = [];

    foreach ($ids as $id) {
        $id = intval($id);
        if ($id <= 0) continue;

        // Kiểm tra xem đơn hàng có tồn tại không
        if (!$order = $CMSNT->get_row("SELECT * FROM `orders` WHERE `id` = '$id'")) {
            $errorCount++;
            continue;
        }

        // Lưu thông tin đơn hàng để ghi log
        $orderDetails[] = $order['trans_id'];

        // Tiến hành xóa
        if ($CMSNT->remove("orders", " `id` = '$id'")) {
            $removeCount++;
        } else {
            $errorCount++;
        }
    }

    if ($removeCount > 0) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa hàng loạt') . ' ' . $removeCount . ' ' . __('đơn hàng') .
                ' (' . implode(', ', array_slice($orderDetails, 0, 5)) .
                (count($orderDetails) > 5 ? '...' : '') . ')'
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa hàng loạt') . ' ' . $removeCount . ' ' . __('đơn hàng'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa thành công') . ' ' . $removeCount . ' ' . __('đơn hàng') .
                ($errorCount > 0 ? ', ' . $errorCount . ' ' . __('đơn hàng bị lỗi') : "")
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Không có đơn hàng nào được xóa')]));
}

// Bulk delete translates
if ($_POST['action'] == 'bulk_delete_translates') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $ids = $_POST['ids'];
    if (empty($ids) || !is_array($ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một mục để xóa')]));
    }

    $deleted_count = 0;
    foreach ($ids as $id) {
        $id = check_string($id);
        if ($CMSNT->num_rows("SELECT * FROM `translate` WHERE `id` = '$id'") > 0) {
            $CMSNT->remove("translate", " `id` = '$id' ");
            $deleted_count++;
        }
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => "Bulk Delete $deleted_count Translates."
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Đã xóa thành công') . ' ' . $deleted_count . ' ' . __('bản dịch')
    ]));
}

// Xóa file installer.php
if ($_POST['action'] == 'deleteInstallerFile') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Đường dẫn tới file installer.php
    $installer_path = __DIR__ . '/../../installer.php';

    // Kiểm tra file có tồn tại không
    if (!file_exists($installer_path)) {
        die(json_encode(['status' => 'error', 'msg' => __('File installer.php không tồn tại')]));
    }

    // Thử xóa file
    if (unlink($installer_path)) {
        // Ghi log hoạt động
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa file installer.php khỏi hệ thống')
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã xóa file installer.php thành công! Bảo mật website đã được tăng cường.')
        ]));
    } else {
        die(json_encode([
            'status' => 'error',
            'msg' => __('Không thể xóa file installer.php. Vui lòng kiểm tra quyền ghi file hoặc xóa thủ công.')
        ]));
    }
}


if ($_POST['action'] == 'removeProductStock') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
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

    // Kiểm tra kho hàng tồn tại
    $stock = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `id` = ?", [$id]);
    if (!$stock) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Kho hàng không tồn tại')
        ]));
    }

    $isDelete = $CMSNT->remove("product_stock", "`id` = ?", [$id]);

    if ($isDelete) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Product Stock ID ' . $id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Xóa kho hàng thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa kho hàng thất bại')]));
}

if ($_POST['action'] == 'removeProductStocks') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['ids']) || empty($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một kho hàng để xóa')
        ]));
    }

    $ids_json = $_POST['ids'];
    $ids_array = json_decode($ids_json, true);

    if (!is_array($ids_array) || empty($ids_array)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Danh sách ID không hợp lệ')
        ]));
    }

    // Validate và sanitize tất cả IDs
    $valid_ids = [];
    foreach ($ids_array as $id) {
        $valid_id = validate_int($id, 1);
        if ($valid_id !== false) {
            $valid_ids[] = $valid_id;
        }
    }

    if (empty($valid_ids)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không có ID hợp lệ')
        ]));
    }

    // Xóa từng kho hàng
    $success_count = 0;
    $error_count = 0;

    foreach ($valid_ids as $id) {
        // Kiểm tra kho hàng tồn tại
        $stock = $CMSNT->get_row_safe("SELECT * FROM `product_stock` WHERE `id` = ?", [$id]);
        if ($stock) {
            $isDelete = $CMSNT->remove("product_stock", "`id` = ?", [$id]);
            if ($isDelete) {
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }

    if ($success_count > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Product Stocks (' . $success_count . ' items) - IDs: ' . implode(', ', $valid_ids)
        ]);
        $msg = __('Xóa kho hàng thành công! Đã xóa ') . $success_count . __(' kho hàng') . ($error_count > 0 ? ' (' . $error_count . __(' lỗi)') : '');
        die(json_encode(['status' => 'success', 'msg' => $msg]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa kho hàng thất bại')]));
}

if ($_POST['action'] == 'removeCoupon') {
    if (checkPermission($getUser['admin'], 'edit_coupon') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $coupon = $CMSNT->get_row_safe("SELECT * FROM `coupons` WHERE `id` = ?", [$id]);
    if (!$coupon) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Mã giảm giá không tồn tại')
        ]));
    }

    // Xóa các bản ghi sử dụng mã giảm giá
    $CMSNT->remove("coupon_usages", "`coupon_id` = ?", [$id]);

    // Xóa mã giảm giá
    $isDelete = $CMSNT->remove("coupons", "`id` = ?", [$id]);

    if ($isDelete) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Coupon (ID ' . $id . ') - Code: ' . $coupon['code']
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Xóa mã giảm giá thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa mã giảm giá thất bại')]));
}

// ==================== FLASH SALE ACTIONS ====================
if ($_POST['action'] == 'removeFlashSale') {
    if (checkPermission($getUser['admin'], 'edit_flash_sale') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $flash_sale = $CMSNT->get_row_safe("SELECT * FROM `flash_sales` WHERE `id` = ?", [$id]);
    if (!$flash_sale) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Flash Sale không tồn tại')
        ]));
    }

    // Xóa các items liên quan (đã có CASCADE)
    $CMSNT->remove("flash_sale_items", "`flash_sale_id` = ?", [$id]);

    // Xóa các purchase records (đã có CASCADE)
    $CMSNT->remove("flash_sale_purchases", "`flash_sale_id` = ?", [$id]);

    // Xóa Flash Sale
    $isDelete = $CMSNT->remove("flash_sales", "`id` = ?", [$id]);

    if ($isDelete) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Flash Sale (ID ' . $id . ') - Name: ' . $flash_sale['name']
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Xóa Flash Sale thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa Flash Sale thất bại')]));
}

// ==================== BLOG CATEGORY ACTIONS ====================
if ($_POST['action'] == 'removeBlogCategory') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$id]);
    if (!$category) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chuyên mục không tồn tại')
        ]));
    }

    // Chuyển các bài viết thuộc chuyên mục này về "Chưa phân loại" (category_id = 0)
    $CMSNT->update("blogs", ['category_id' => 0], " `category_id` = ? ", [$id]);

    // Xóa ảnh nếu có
    if (!empty($category['image']) && file_exists($category['image'])) {
        @unlink($category['image']);
    }

    // Xóa chuyên mục
    $isDelete = $CMSNT->remove("blog_categories", "`id` = ?", [$id]);

    if ($isDelete) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Blog Category (ID ' . $id . ') - ' . $category['name']
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Xóa chuyên mục thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa chuyên mục thất bại')]));
}

// ==================== BLOG POST ACTIONS ====================
if ($_POST['action'] == 'removeBlog') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);

    if ($id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` = ?", [$id]);
    if (!$blog) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bài viết không tồn tại')
        ]));
    }

    // Xóa ảnh nếu có
    if (!empty($blog['thumbnail']) && file_exists($blog['thumbnail'])) {
        @unlink($blog['thumbnail']);
    }

    // Xóa bài viết
    $isDelete = $CMSNT->remove("blogs", "`id` = ?", [$id]);

    if ($isDelete) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Remove Blog Post (ID ' . $id . ') - ' . $blog['title']
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Xóa bài viết thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa bài viết thất bại')]));
}

if ($_POST['action'] == 'bulkDeleteBlogs') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một bài viết')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $deleteCount = 0;

    foreach ($ids as $id) {
        if ($id > 0) {
            $blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` = ?", [$id]);
            if ($blog) {
                // Xóa ảnh nếu có
                if (!empty($blog['thumbnail']) && file_exists($blog['thumbnail'])) {
                    @unlink($blog['thumbnail']);
                }

                if ($CMSNT->remove("blogs", "`id` = ?", [$id])) {
                    $deleteCount++;
                }
            }
        }
    }

    if ($deleteCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Xóa hàng loạt %d bài viết'), $deleteCount)
        ]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã xóa thành công %d bài viết'), $deleteCount)]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Xóa bài viết thất bại')]));
}

// ==================== SLIDER ACTIONS ====================
if ($_POST['action'] == 'removeSlider') {
    if (checkPermission($getUser['admin'], 'edit_sliders') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $slider_id = validate_int($_POST['id'], 1);

    if ($slider_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    // Lấy thông tin slider để xóa ảnh
    $slider = $CMSNT->get_row_safe("SELECT * FROM `sliders` WHERE `id` = ?", [$slider_id]);

    if (!$slider) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Slider không tồn tại')
        ]));
    }

    // Xóa ảnh cũ nếu tồn tại
    if (!empty($slider['image']) && file_exists($slider['image'])) {
        @unlink($slider['image']);
    }

    // Xóa slider
    $isDeleted = $CMSNT->remove("sliders", "`id` = ?", [$slider_id]);

    if ($isDeleted) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Xóa slider')
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa slider'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Xóa slider thành công!')
        ]));
    }

    die(json_encode([
        'status' => 'error',
        'msg' => __('Xóa slider thất bại!')
    ]));
}

// ==================== BANNER ACTIONS ====================
if ($_POST['action'] == 'removeBanner') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $banner_id = validate_int($_POST['id'], 1);

    if ($banner_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    // Lấy thông tin banner để xóa ảnh
    $banner = $CMSNT->get_row_safe("SELECT * FROM `banners` WHERE `id` = ?", [$banner_id]);

    if (!$banner) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Banner không tồn tại')
        ]));
    }

    // Xóa ảnh cũ nếu tồn tại
    if (!empty($banner['image']) && file_exists(__DIR__ . '/../../' . $banner['image'])) {
        @unlink(__DIR__ . '/../../' . $banner['image']);
    }

    // Xóa banner
    $isDeleted = $CMSNT->remove("banners", "`id` = ?", [$banner_id]);

    if ($isDeleted) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Xóa banner')
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa banner'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Xóa banner thành công!')
        ]));
    }

    die(json_encode([
        'status' => 'error',
        'msg' => __('Xóa banner thất bại!')
    ]));
}

/**
 * Xóa API Key
 */
if ($_POST['action'] == 'api_key') {
    if (checkPermission($getUser['admin'], 'edit_api_keys') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : 0;

    if (!$id) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }

    $apiKey = $CMSNT->get_row_safe("SELECT ak.*, u.username FROM `api_keys` ak LEFT JOIN `users` u ON ak.user_id = u.id WHERE ak.`id` = ?", [$id]);
    if (!$apiKey) {
        die(json_encode(['status' => 'error', 'msg' => __('API Key không tồn tại')]));
    }

    // Xóa logs liên quan
    $CMSNT->remove('api_logs', "`api_key` = ?", [$apiKey['api_key']]);

    // Xóa API Key
    $isDeleted = $CMSNT->remove('api_keys', "`id` = ?", [$id]);

    if ($isDeleted) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Xóa API Key') . " (User: {$apiKey['username']}, Name: {$apiKey['name']})"
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Xóa API Key thành công!')]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Xóa API Key thất bại')]));
}

// ==================== SUPPLIER ACTIONS ====================
if ($_POST['action'] == 'removeSupplier') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
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

    // Sử dụng ProductDeletionService
    $deletionService = new ProductDeletionService($CMSNT);
    $result = $deletionService->deleteSupplier($id, true, true);

    if ($result['success']) {
        $supplier = $result['supplier'];

        // Ghi log
        $logAction = __('Xóa Supplier API') . ' (' . $supplier['domain'] . ' ID ' . $id . ')';
        if ($result['deleted_categories'] > 0 || $result['deleted_products'] > 0 || $result['deleted_plans'] > 0) {
            $logAction .= sprintf(
                ' - %d chuyên mục, %d sản phẩm, %d gói',
                $result['deleted_categories'],
                $result['deleted_products'],
                $result['deleted_plans']
            );
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => $logAction
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Xóa Supplier API') . ' (' . $supplier['domain'] . ')', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        $msg = __('Xóa Supplier thành công!');
        if ($result['deleted_categories'] > 0 || $result['deleted_products'] > 0 || $result['deleted_plans'] > 0) {
            $msg .= sprintf(
                __(' Đã xóa %d chuyên mục, %d sản phẩm, %d gói.'),
                $result['deleted_categories'],
                $result['deleted_products'],
                $result['deleted_plans']
            );
        }

        die(json_encode(['status' => 'success', 'msg' => $msg]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Xóa Supplier thất bại: ') . $deletionService->getFirstError()]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => 'Dữ liệu không hợp lệ'
]));
