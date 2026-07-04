<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . '/../../libs/database/users.php');
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
$supplierActions = ['updateSupplier', 'updateSupplierStatus', 'syncSupplierProducts', 'syncSupplierCategories', 'unlinkSupplierProduct'];
if (in_array($_POST['action'], $supplierActions)) {
    require_once(__DIR__ . '/suppliers.php');
    exit;
}

if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}


if ($_POST['action'] == 'set_webhook') {
    if (checkPermission($getUser['admin'], 'edit_setting') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $webhook_secret = check_string($_POST['telegram_webhook_secret']);
    if (empty($webhook_secret)) {
        die(json_encode(['status' => 'error', 'msg' => __('Secret token không được để trống')]));
    }
    // Kiểm tra độ dài secret token (phải là 64 ký tự hex)
    if (strlen($webhook_secret) !== 64 || !ctype_xdigit($webhook_secret)) {
        die(json_encode(['status' => 'error', 'msg' => __('Secret token không hợp lệ (phải là 64 ký tự hex)')]));
    }
    // Lấy thông tin bot
    $bot_token = $CMSNT->site('telegram_token');
    $telegram_url = $CMSNT->site('telegram_url');

    if (empty($bot_token)) {
        die(json_encode(['status' => 'error', 'msg' => __('Chưa cấu hình Telegram Bot Token')]));
    }

    // Cập nhật secret token vào database trước
    $isUpdate = $CMSNT->update("settings", [
        'value' => $webhook_secret
    ], " `name` = 'telegram_webhook_secret' ");

    if ($isUpdate) {
        // Tạo webhook URL
        $webhook_url = base_url('api/webhook_telegram.php');
        // Gọi API Telegram để set webhook
        $url = $telegram_url . "bot{$bot_token}/setWebhook";
        $post_data = [
            'url' => $webhook_url,
            'secret_token' => $webhook_secret,
            'max_connections' => 10,
            'drop_pending_updates' => true
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = json_decode($response, true);

        if ($http_code !== 200) {
            die(json_encode([
                'status' => 'error',
                'msg' => check_string($result['description']) . ": HTTP $http_code"
            ]));
        }


        if (!$result) {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Telegram API trả về dữ liệu không hợp lệ')
            ]));
        }

        if ($result['ok']) {
            // Log hoạt động
            $CMSNT->insert("logs", [
                'user_id' => $getUser['id'],
                'ip' => myip(),
                'device' => getUserAgent(),
                'createdate' => gettime(),
                'action' => __('Set Telegram Webhook với Secret Token mới')
            ]);

            die(json_encode([
                'status' => 'success',
                'msg' => __('Webhook đã được thiết lập thành công với bảo mật!'),
                'webhook_url' => $webhook_url,
                'secret_preview' => substr($webhook_secret, 0, 8) . '...'
            ]));
        } else {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Lỗi từ Telegram') . ': ' . ($result['description'] ?? 'Unknown error')
            ]));
        }
    }
    die(json_encode(['status' => 'error', 'msg' => __('Set webhook thất bại')]));
}

if ($_POST['action'] == 'update_ticket_settings') {
    if (checkPermission($getUser['admin'], 'config_ticket') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $status = isset($_POST['support_tickets_status']) && $_POST['support_tickets_status'] == '1' ? 1 : 0;
    $orderHistory = isset($_POST['support_tickets_order_history']) && $_POST['support_tickets_order_history'] == '1' ? 1 : 0;
    $telegramChatId = isset($_POST['support_tickets_telegram_chat_id']) ? check_string($_POST['support_tickets_telegram_chat_id']) : '';

    $settingsPayload = [
        'support_tickets_status' => $status,
        'support_tickets_order_history' => $orderHistory,
        'support_tickets_telegram_chat_id' => $telegramChatId
    ];

    foreach ($settingsPayload as $key => $value) {
        $CMSNT->update('settings', [
            'value' => $value
        ], " `name` = '$key' ");
    }

    $CMSNT->insert('logs', [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình Ticket')
    ]);

    $notifyTemplate = $CMSNT->site('noti_action');
    $notifyTemplate = str_replace('{domain}', $_SERVER['SERVER_NAME'], $notifyTemplate);
    $notifyTemplate = str_replace('{username}', $getUser['username'], $notifyTemplate);
    $notifyTemplate = str_replace('{action}', __('Cấu hình Ticket'), $notifyTemplate);
    $notifyTemplate = str_replace('{ip}', myip(), $notifyTemplate);
    $notifyTemplate = str_replace('{time}', gettime(), $notifyTemplate);
    sendMessAdmin($notifyTemplate);

    die(json_encode(['status' => 'success', 'msg' => __('Đã lưu cấu hình ticket thành công.')]));
}

if ($_POST['action'] == 'reset_total_money_users') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $isUpdate = $CMSNT->update('users', [
        'total_money'  => 0
    ], " `total_money` > 0 ");
    if (isset($isUpdate)) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Reset tổng nạp toàn bộ thành viên')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Reset tổng nạp toàn bộ user thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Reset thất bại')]));
}
if ($_POST['action'] == 'update_status_user') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (!$user = $CMSNT->get_row(" SELECT * FROM `users` WHERE `id` = '" . check_string($_POST['id']) . "' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Thành viên không tồn tại trong hệ thống')]));
    }
    $isUpdate = $CMSNT->update("users", [
        'banned'    => !empty($_POST['status']) ? check_string($_POST['status']) : 0
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái thành viên (Tên: %s - ID: %s)'), $user['username'], $user['id'])
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}





if ($_POST['action'] == 'update_category_category') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isUpdate = $CMSNT->update("categories", [
        'parent_id'    => !empty($_POST['category_id']) ? check_string($_POST['category_id']) : 0
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật chuyên mục cha cho chuyên mục (ID %s)'), check_string($_POST['id']))
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật chuyên mục cha thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'update_category_product') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isUpdate = $CMSNT->update("products", [
        'category_id'    => !empty($_POST['category_id']) ? check_string($_POST['category_id']) : 0
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật chuyên mục cho sản phẩm (ID %s)'), check_string($_POST['id']))
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật chuyên mục thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}
if ($_POST['action'] == 'updateTableProductAPI') {
    if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isUpdate = $CMSNT->update("suppliers", [
        'status'    => !empty($_POST['status']) ? check_string($_POST['status']) : 0
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Supplier (ID ' . check_string($_POST['id']) . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'updateTableCategory') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    $isUpdate = $CMSNT->update("categories", [
        'stt'       => !empty($_POST['stt']) ? check_string($_POST['stt']) : 0,
        'status'    => !empty($_POST['status']) ? check_string($_POST['status']) : 'hide'
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Table Category (ID ' . check_string($_POST['id']) . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'updateProductStatus') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }
    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }
    $id = check_string($_POST['id']);
    $status = intval($_POST['status']);

    $isUpdate = $CMSNT->update("products", [
        'status'    => $status,
        'updated_at' => gettime()
    ], " `id` = '" . $id . "' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Status (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trạng thái thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'addProductField') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $product_id = check_string($_POST['product_id']);
    $field_key = check_string($_POST['field_key']);
    $label = check_string($_POST['label']);
    $type = check_string($_POST['type']);

    $is_required = isset($_POST['is_required']) ? intval($_POST['is_required']) : 0;

    if (empty($product_id) || empty($field_key) || empty($label)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    // Kiểm tra sản phẩm tồn tại
    if (!$CMSNT->get_row("SELECT * FROM `products` WHERE `id` = '" . $product_id . "'")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Sản phẩm không tồn tại')
        ]));
    }

    // Kiểm tra field_key trùng
    if ($CMSNT->get_row("SELECT * FROM `product_fields` WHERE `product_id` = '" . $product_id . "' AND `field_key` = '" . $field_key . "'")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Field Key đã tồn tại trong sản phẩm này')
        ]));
    }

    // Lấy sort_order cao nhất
    $max_order = $CMSNT->get_row("SELECT MAX(`sort_order`) as max_order FROM `product_fields` WHERE `product_id` = '" . $product_id . "'");
    $sort_order = $max_order ? intval($max_order['max_order']) + 1 : 0;

    $isInsert = $CMSNT->insert("product_fields", [
        'product_id'    => $product_id,
        'field_key'     => $field_key,
        'label'         => $label,
        'type'          => $type,

        'is_required'   => $is_required,
        'sort_order'    => $sort_order
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Add Product Field (' . $label . ') for Product ID ' . $product_id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Thêm trường thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thêm trường thất bại')]));
}

if ($_POST['action'] == 'updateProductField') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = check_string($_POST['id']);
    $field_key = check_string($_POST['field_key']);
    $label = check_string($_POST['label']);
    $type = check_string($_POST['type']);

    $is_required = isset($_POST['is_required']) ? intval($_POST['is_required']) : 0;

    if (empty($id) || empty($field_key) || empty($label)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    // Kiểm tra trường tồn tại
    $field = $CMSNT->get_row("SELECT * FROM `product_fields` WHERE `id` = '" . $id . "'");
    if (!$field) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Trường không tồn tại')
        ]));
    }

    // Kiểm tra field_key trùng (trừ chính nó)
    if ($CMSNT->get_row("SELECT * FROM `product_fields` WHERE `product_id` = '" . $field['product_id'] . "' AND `field_key` = '" . $field_key . "' AND `id` != '" . $id . "'")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Field Key đã tồn tại trong sản phẩm này')
        ]));
    }

    $isUpdate = $CMSNT->update("product_fields", [
        'field_key'     => $field_key,
        'label'         => $label,
        'type'          => $type,

        'is_required'   => $is_required
    ], " `id` = '" . $id . "' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Field (' . $label . ') ID ' . $id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trường thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateProductFieldsOrder') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['order'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        if (isset($item['id']) && isset($item['sort_order'])) {
            $id = intval($item['id']);
            $sort_order = intval($item['sort_order']);
            if ($CMSNT->update("product_fields", ['sort_order' => $sort_order], " `id` = '" . $id . "' ")) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Fields Order'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

// ==================== PLAN FIELDS ====================

if ($_POST['action'] == 'addPlanField') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $plan_id = check_string($_POST['plan_id']);
    $field_key = check_string($_POST['field_key']);
    $label = trim(strip_tags($_POST['label']));
    $type = check_string($_POST['type']);

    $is_required = isset($_POST['is_required']) ? intval($_POST['is_required']) : 0;

    if (empty($plan_id) || empty($field_key) || empty($label)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    // Kiểm tra gói tồn tại
    if (!$CMSNT->get_row("SELECT * FROM `product_plans` WHERE `id` = '" . $plan_id . "'")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Gói sản phẩm không tồn tại')
        ]));
    }

    // Kiểm tra field_key trùng trong cùng gói
    if ($CMSNT->get_row("SELECT * FROM `product_fields` WHERE `plan_id` = '" . $plan_id . "' AND `field_key` = '" . $field_key . "'")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Field Key đã tồn tại trong gói này')
        ]));
    }

    // Lấy sort_order cao nhất
    $max_order = $CMSNT->get_row("SELECT MAX(`sort_order`) as max_order FROM `product_fields` WHERE `plan_id` = '" . $plan_id . "'");
    $sort_order = $max_order ? intval($max_order['max_order']) + 1 : 0;

    $isInsert = $CMSNT->insert("product_fields", [
        'plan_id'       => $plan_id,
        'field_key'     => $field_key,
        'label'         => $label,
        'type'          => $type,

        'is_required'   => $is_required,
        'sort_order'    => $sort_order
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Add Plan Field (' . $label . ') for Plan ID ' . $plan_id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Thêm trường thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thêm trường thất bại')]));
}

if ($_POST['action'] == 'updatePlanField') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = check_string($_POST['id']);
    $field_key = check_string($_POST['field_key']);
    $label = trim(strip_tags($_POST['label']));
    $type = check_string($_POST['type']);

    $is_required = isset($_POST['is_required']) ? intval($_POST['is_required']) : 0;

    if (empty($id) || empty($field_key) || empty($label)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    // Kiểm tra trường tồn tại
    $field = $CMSNT->get_row("SELECT * FROM `product_fields` WHERE `id` = '" . $id . "'");
    if (!$field) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Trường không tồn tại')
        ]));
    }

    // Kiểm tra field_key trùng (trừ chính nó) trong cùng gói
    if ($CMSNT->get_row("SELECT * FROM `product_fields` WHERE `plan_id` = '" . $field['plan_id'] . "' AND `field_key` = '" . $field_key . "' AND `id` != '" . $id . "'")) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Field Key đã tồn tại trong gói này')
        ]));
    }

    $isUpdate = $CMSNT->update("product_fields", [
        'field_key'     => $field_key,
        'label'         => $label,
        'type'          => $type,

        'is_required'   => $is_required
    ], " `id` = '" . $id . "' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Plan Field (' . $label . ') ID ' . $id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trường thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updatePlanFieldsOrder') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['order'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        if (isset($item['id']) && isset($item['sort_order'])) {
            $id = intval($item['id']);
            $sort_order = intval($item['sort_order']);
            if ($CMSNT->update("product_fields", ['sort_order' => $sort_order], " `id` = '" . $id . "' ")) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Plan Fields Order'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

// ==================== PRODUCT PLANS ====================

if ($_POST['action'] == 'updateProductPlan') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);
    $product_id = isset($_POST['product_id']) ? validate_int($_POST['product_id'], 0) : 0;
    $name = validate_string($_POST['name'], 255, 1);
    $duration_type = validate_string($_POST['duration_type'], 20);
    $duration_value = isset($_POST['duration_value']) ? validate_int($_POST['duration_value'], 1) : null;
    $cost_price = isset($_POST['cost_price']) ? validate_float($_POST['cost_price'], 0) : 0;
    $price = validate_float($_POST['price'], 0);
    $sale_price = isset($_POST['sale_price']) ? validate_float($_POST['sale_price'], 0) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $is_instant = isset($_POST['is_instant']) ? validate_int($_POST['is_instant'], 0, 1) : 0;
    $status = isset($_POST['status']) ? validate_int($_POST['status'], 0, 1) : 0;

    if ($id === false || $name === false || $price === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    if ($duration_type === false || !in_array($duration_type, ['day', 'month', 'year', 'lifetime'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Loại thời hạn không hợp lệ')
        ]));
    }

    // Kiểm tra gói tồn tại
    $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ?", [$id]);
    if (!$plan) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Gói không tồn tại')
        ]));
    }

    // Xử lý upload ảnh icon hoặc chọn từ thư viện
    $url_image = $plan['image']; // Giữ ảnh cũ mặc định
    $image_path = isset($_POST['image_path']) ? trim($_POST['image_path']) : '';

    // Kiểm tra nếu có chọn ảnh từ thư viện (elFinder)
    if (!empty($image_path)) {
        // Chuyển đổi URL đầy đủ thành đường dẫn tương đối
        if (strpos($image_path, base_url()) === 0) {
            $url_image = str_replace(base_url(), '', $image_path);
            $url_image = ltrim($url_image, '/');
        } else {
            $url_image = $image_path;
        }

        // Xóa ảnh cũ nếu không phải ảnh từ thư viện (để tránh xóa ảnh dùng chung)
        if ($plan['image'] && !empty($plan['image']) && strpos($plan['image'], 'library/') === false) {
            $old_image_path = __DIR__ . '/../../' . $plan['image'];
            if (file_exists($old_image_path)) {
                @unlink($old_image_path);
            }
        }
    } elseif (isset($_FILES['image']) && check_img('image') == true) {
        // Upload ảnh mới
        // Xóa ảnh cũ nếu có và không phải từ thư viện
        if ($plan['image'] && !empty($plan['image']) && strpos($plan['image'], 'library/') === false) {
            $old_image_path = __DIR__ . '/../../' . $plan['image'];
            if (file_exists($old_image_path)) {
                @unlink($old_image_path);
            }
        }

        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploadDir = 'assets/storage/images/';
        $absoluteUploadDir = __DIR__ . '/../../' . $uploadDir;

        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($absoluteUploadDir)) {
            mkdir($absoluteUploadDir, 0755, true);
        }

        $uploads_dir = $uploadDir . 'plan_' . $rand . '.' . $ext;
        $absolute_uploads_dir = $absoluteUploadDir . 'plan_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $absolute_uploads_dir);
        if ($addlogo) {
            $url_image = $uploads_dir;
        }
    }

    $isUpdate = $CMSNT->update("product_plans", [
        'product_id'    => $product_id !== false ? $product_id : 0,
        'name'          => $name,
        'duration_type' => $duration_type,
        'duration_value' => $duration_value,
        'cost_price'    => $cost_price,
        'price'         => $price,
        'sale_price'   => $sale_price,
        'description'   => $description,
        'is_instant'    => $is_instant,
        'image'         => $url_image,
        'status'        => $status,
        'updated_at'    => gettime()
    ], " `id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Plan (' . $name . ') ID ' . $id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật gói thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateProductPlanStatus') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $id = check_string($_POST['id']);
    $status = intval($_POST['status']);

    $isUpdate = $CMSNT->update("product_plans", [
        'status'    => $status,
        'updated_at' => gettime()
    ], " `id` = '" . $id . "' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Plan Status (ID ' . $id . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trạng thái thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'updateProductPlansOrder') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['order'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        if (isset($item['id']) && isset($item['sort_order'])) {
            $id = intval($item['id']);
            $sort_order = intval($item['sort_order']);
            if ($CMSNT->update("product_plans", ['sort_order' => $sort_order], " `id` = '" . $id . "' ")) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Plans Order'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateProductsOrder') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['order'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        if (isset($item['id']) && isset($item['sort_order'])) {
            $id = validate_int($item['id'], 1);
            $sort_order = validate_int($item['sort_order'], 0);
            if ($id !== false && $sort_order !== false) {
                if ($CMSNT->update("products", ['sort_order' => $sort_order], " `id` = ?", [$id])) {
                    $updateCount++;
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Products Order'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateSlidersOrder') {
    if (checkPermission($getUser['admin'], 'edit_sliders') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['order'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        if (isset($item['id']) && isset($item['sort_order'])) {
            $id = validate_int($item['id'], 1);
            $sort_order = validate_int($item['sort_order'], 0);
            if ($id !== false && $sort_order !== false) {
                if ($CMSNT->update("sliders", ['sort_order' => $sort_order], " `id` = ?", [$id])) {
                    $updateCount++;
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật thứ tự slider')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateBannersOrder') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['order'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        if (isset($item['id']) && isset($item['sort_order'])) {
            $id = validate_int($item['id'], 1);
            $sort_order = validate_int($item['sort_order'], 0);
            if ($id !== false && $sort_order !== false) {
                if ($CMSNT->update("banners", ['sort_order' => $sort_order], " `id` = ?", [$id])) {
                    $updateCount++;
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật thứ tự banner')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateBannersOrderAndPosition') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (!isset($_POST['updates'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updates = json_decode($_POST['updates'], true);
    if (!is_array($updates)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    $updateCount = 0;
    foreach ($updates as $item) {
        if (isset($item['id']) && isset($item['position']) && isset($item['sort_order'])) {
            $id = validate_int($item['id'], 1);
            $position = validate_string($item['position'], 50);
            $sort_order = validate_int($item['sort_order'], 0);

            if ($id !== false && $sort_order !== false && $position !== false && in_array($position, ['below_sliders', 'sidebar_left', 'sidebar_right', 'footer', 'top', 'content'])) {
                if ($CMSNT->update("banners", [
                    'position' => $position,
                    'sort_order' => $sort_order,
                    'updated_at' => gettime()
                ], " `id` = ?", [$id])) {
                    $updateCount++;
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật vị trí và thứ tự banner')
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật banner thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

// Cập nhật nhanh hàng loạt gói sản phẩm
if ($_POST['action'] == 'bulkQuickUpdatePlans') {
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

    if (empty($_POST['fields']) || !is_array($_POST['fields'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng nhập ít nhất một trường để cập nhật')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $fields = $_POST['fields'];
    $updateFields = [];
    $updateCount = 0;

    // Kiểm tra các field điều chỉnh đặc biệt
    $hasPriceAdjust = isset($fields['price_adjust_percent']) && floatval($fields['price_adjust_percent']) > 0;
    $hasDiscountPercent = isset($fields['discount_percent']) && floatval($fields['discount_percent']) > 0;

    // Nếu có điều chỉnh đặc biệt, cần xử lý từng gói riêng
    if ($hasPriceAdjust || $hasDiscountPercent) {
        foreach ($ids as $id) {
            if ($id <= 0) continue;

            // Lấy thông tin gói hiện tại
            $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ?", [$id]);
            if (!$plan) continue;

            $updateData = ['updated_at' => gettime()];

            // === THÔNG TIN CƠ BẢN ===
            if (isset($fields['product_id']) && $fields['product_id'] !== '') {
                $updateData['product_id'] = intval($fields['product_id']);
                if (!in_array('product_id', $updateFields)) $updateFields[] = 'product_id';
            }

            if (isset($fields['status']) && $fields['status'] !== '') {
                $updateData['status'] = intval($fields['status']);
                if (!in_array('status', $updateFields)) $updateFields[] = 'status';
            }

            if (isset($fields['duration_type']) && $fields['duration_type'] !== '') {
                $updateData['duration_type'] = $fields['duration_type'];
                if (!in_array('duration_type', $updateFields)) $updateFields[] = 'duration_type';

                if ($fields['duration_type'] === 'lifetime') {
                    $updateData['duration_value'] = null;
                } elseif (isset($fields['duration_value']) && $fields['duration_value'] !== '') {
                    $updateData['duration_value'] = intval($fields['duration_value']);
                }
            }

            // === GIÁ ===
            // Giá vốn
            if (isset($fields['cost_price']) && $fields['cost_price'] !== '') {
                $updateData['cost_price'] = floatval($fields['cost_price']);
                if (!in_array('cost_price', $updateFields)) $updateFields[] = 'cost_price';
            }

            // Giá bán lẻ cố định
            if (isset($fields['price']) && $fields['price'] !== '') {
                $updateData['price'] = floatval($fields['price']);
                if (!in_array('price', $updateFields)) $updateFields[] = 'price';
            }
            // Hoặc điều chỉnh giá theo % dựa trên giá vốn
            elseif ($hasPriceAdjust) {
                $costPrice = isset($updateData['cost_price']) ? $updateData['cost_price'] : floatval($plan['cost_price']);
                $percent = floatval($fields['price_adjust_percent']);
                $adjustType = $fields['price_adjust_type'] ?? 'increase';

                if ($adjustType === 'increase') {
                    $updateData['price'] = $costPrice + ($costPrice * $percent / 100);
                } else {
                    $updateData['price'] = $costPrice - ($costPrice * $percent / 100);
                }
                $updateData['price'] = max(0, $updateData['price']); // Không âm
                if (!in_array('price (% adjust)', $updateFields)) $updateFields[] = 'price (% adjust)';
            }

            // Giảm giá % (tính sale_price từ price)
            if ($hasDiscountPercent) {
                $currentPrice = isset($updateData['price']) ? $updateData['price'] : floatval($plan['price']);
                $discountPercent = floatval($fields['discount_percent']);
                $updateData['sale_price'] = $currentPrice - ($currentPrice * $discountPercent / 100);
                $updateData['sale_price'] = max(0, $updateData['sale_price']);
                if (!in_array('sale_price (discount)', $updateFields)) $updateFields[] = 'sale_price (discount)';
            }

            // === MÔ TẢ ===
            if (isset($fields['description']) && $fields['description'] !== '') {
                $updateData['description'] = $fields['description'];
                if (!in_array('description', $updateFields)) $updateFields[] = 'description';
            }

            // Cập nhật
            if (count($updateData) > 1) { // Có field nào đó ngoài updated_at
                if ($CMSNT->update("product_plans", $updateData, " `id` = ?", [$id])) {
                    $updateCount++;
                }
            }
        }
    } else {
        // Không có điều chỉnh đặc biệt, cập nhật hàng loạt như bình thường
        $updateData = ['updated_at' => gettime()];

        if (isset($fields['product_id']) && $fields['product_id'] !== '') {
            $updateData['product_id'] = intval($fields['product_id']);
            $updateFields[] = 'product_id';
        }

        if (isset($fields['status']) && $fields['status'] !== '') {
            $updateData['status'] = intval($fields['status']);
            $updateFields[] = 'status';
        }

        if (isset($fields['duration_type']) && $fields['duration_type'] !== '') {
            $updateData['duration_type'] = $fields['duration_type'];
            $updateFields[] = 'duration_type';

            if ($fields['duration_type'] === 'lifetime') {
                $updateData['duration_value'] = null;
            } elseif (isset($fields['duration_value']) && $fields['duration_value'] !== '') {
                $updateData['duration_value'] = intval($fields['duration_value']);
            }
        }

        if (isset($fields['cost_price']) && $fields['cost_price'] !== '') {
            $updateData['cost_price'] = floatval($fields['cost_price']);
            $updateFields[] = 'cost_price';
        }

        if (isset($fields['price']) && $fields['price'] !== '') {
            $updateData['price'] = floatval($fields['price']);
            $updateFields[] = 'price';
        }

        if (isset($fields['description']) && $fields['description'] !== '') {
            $updateData['description'] = $fields['description'];
            $updateFields[] = 'description';
        }

        if (count($updateData) <= 1) {
            die(json_encode([
                'status'    => 'error',
                'msg'       => __('Không có trường nào hợp lệ để cập nhật')
            ]));
        }

        foreach ($ids as $id) {
            if ($id > 0) {
                if ($CMSNT->update("product_plans", $updateData, " `id` = ?", [$id])) {
                    $updateCount++;
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật nhanh hàng loạt %d gói sản phẩm (các trường: %s)'), $updateCount, implode(', ', $updateFields))
        ]);
        die(json_encode([
            'status'    => 'success',
            'msg'       => sprintf(__('Đã cập nhật thành công %d gói sản phẩm'), $updateCount)
        ]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có gói sản phẩm nào được cập nhật')]));
}

// Cập nhật ghi chú hàng loạt đơn hàng sản phẩm
if ($_POST['action'] == 'updateProductOrderNote') {
    if (checkPermission($getUser['admin'], 'edit_orders_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : 0;
    if (!$id) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID đơn hàng không hợp lệ')
        ]));
    }

    $note = isset($_POST['note']) ? validate_string($_POST['note'], 1000) : '';
    if ($note === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Ghi chú không hợp lệ')
        ]));
    }

    // Kiểm tra đơn hàng có tồn tại không
    $order = $CMSNT->get_row_safe("SELECT * FROM `product_orders` WHERE `id` = ?", [$id]);
    if (!$order) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Đơn hàng không tồn tại')
        ]));
    }

    // Cập nhật ghi chú
    $isUpdate = $CMSNT->update("product_orders", [
        'note' => $note,
        'updated_at' => gettime()
    ], " `id` = ?", [$id]);

    if ($isUpdate) {
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Cập nhật ghi chú thành công')
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không thể cập nhật ghi chú')
        ]));
    }
}

if ($_POST['action'] == 'cancelProductOrder') {
    if (checkPermission($getUser['admin'], 'edit_orders_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : 0;
    if (!$id) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('ID đơn hàng không hợp lệ')
        ]));
    }

    // Lấy thông tin đơn hàng
    $order = $CMSNT->get_row_safe("SELECT * FROM `product_orders` WHERE `id` = ?", [$id]);
    if (!$order) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Đơn hàng không tồn tại')
        ]));
    }

    // Kiểm tra đơn hàng có thể hủy không (chỉ hủy được đơn hàng pending)
    if ($order['status'] != 'pending') {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chỉ có thể hủy đơn hàng ở trạng thái chờ xử lý')
        ]));
    }

    // Kiểm tra quyền hoàn tiền
    if (checkPermission($getUser['admin'], 'refund_orders_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền hoàn tiền đơn hàng')
        ]));
    }

    // Hoàn tiền cho khách hàng (nếu đơn hàng có giá trị)
    if ($order['total_price'] > 0 && $order['user_id'] > 0) {
        require_once(__DIR__ . '/../../libs/database/users.php');
        $User = new users();

        // Số tiền hoàn = sale_price (nếu có) hoặc total_price
        $refund_amount = $order['sale_price'] > 0 && $order['sale_price'] < $order['total_price']
            ? $order['sale_price']
            : $order['total_price'];

        $isRefund = $User->RefundCredits(
            $order['user_id'],
            $refund_amount,
            '[Admin] ' . sprintf(__("Hoàn tiền đơn hàng sản phẩm #%s (Hủy đơn hàng)"), $order['trans_id']),
            'PRODUCT_ORDER_' . $order['trans_id']
        );

        if (!$isRefund) {
            die(json_encode([
                'status'    => 'error',
                'msg'       => __('Hoàn tiền thất bại! Vui lòng kiểm tra lại.')
            ]));
        }
    }

    // Thu hồi hoa hồng nếu có
    if (isset($order['commission_amount']) && $order['commission_amount'] > 0 && isset($order['commission_user_id']) && $order['commission_user_id'] > 0) {
        require_once(__DIR__ . '/../../libs/database/users.php');
        $User = new users();

        // Trừ tiền hoa hồng từ người nhận hoa hồng
        $isDeduct = $User->RemoveCredits(
            $order['commission_user_id'],
            $order['commission_amount'],
            '[Admin] ' . sprintf(__("Thu hồi hoa hồng đơn hàng sản phẩm #%s (Hủy đơn hàng)"), $order['trans_id']),
            'REVOKE_COMMISSION_' . $order['trans_id']
        );

        // Ghi chú: Nếu số dư người nhận hoa hồng không đủ, vẫn cho phép hủy đơn hàng
        // nhưng ghi log cảnh báo
        if (!$isDeduct) {
            $CMSNT->insert("logs", [
                'user_id'       => 0,
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => "[Cảnh báo] Không thể thu hồi hoa hồng " . format_currency($order['commission_amount']) . " từ User ID " . $order['commission_user_id'] . " cho đơn hàng " . $order['trans_id'] . " (Có thể do số dư không đủ)"
            ]);
        }
    }

    // Cập nhật trạng thái đơn hàng
    $isUpdate = $CMSNT->update("product_orders", [
        'status' => 'cancelled',
        'reason' => '[Admin] Hủy đơn hàng từ trang quản lý',
        'updated_at' => gettime()
    ], " `id` = ?", [$id]);

    if ($isUpdate) {
        // Ghi log
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Hủy đơn hàng sản phẩm ID " . $id . " (" . $order['trans_id'] . ")."
        ]);

        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Hủy đơn hàng thành công và đã hoàn tiền cho khách hàng')
        ]));
    } else {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không thể cập nhật trạng thái đơn hàng')
        ]));
    }
}

if ($_POST['action'] == 'bulkUpdateProductOrderNote') {
    if (checkPermission($getUser['admin'], 'edit_orders_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một đơn hàng')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $note = isset($_POST['note']) ? validate_string($_POST['note'], 1000) : '';

    if ($note === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Ghi chú không hợp lệ')
        ]));
    }

    $updateCount = 0;
    $updatedTransIds = [];

    foreach ($ids as $id) {
        if ($id > 0) {
            $order = $CMSNT->get_row_safe("SELECT * FROM `product_orders` WHERE `id` = ?", [$id]);
            if ($order) {
                if ($CMSNT->update("product_orders", [
                    'note' => $note,
                    'updated_at' => gettime()
                ], " `id` = ?", [$id])) {
                    $updatedTransIds[] = $order['trans_id'];
                    $updateCount++;
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật ghi chú hàng loạt %d đơn hàng sản phẩm: %s'), $updateCount, implode(', ', array_slice($updatedTransIds, 0, 5)) . (count($updatedTransIds) > 5 ? '...' : ''))
        ]);
        die(json_encode([
            'status'    => 'success',
            'msg'       => sprintf(__('Đã cập nhật ghi chú thành công cho %d đơn hàng'), $updateCount)
        ]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có đơn hàng nào được cập nhật')]));
}

// Cập nhật trạng thái hàng loạt hóa đơn ngân hàng
if ($_POST['action'] == 'bulkUpdateInvoiceBankStatus') {
    if (checkPermission($getUser['admin'], 'edit_recharge_bank_invoice') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một hóa đơn')
        ]));
    }

    if (empty($_POST['status'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn trạng thái')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $new_status = validate_string($_POST['status'], 20);

    // Validate trạng thái hợp lệ
    $valid_statuses = ['pending', 'completed', 'expired'];
    if ($new_status === false || !in_array($new_status, $valid_statuses)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Trạng thái không hợp lệ')
        ]));
    }

    // Validate số lượng để tránh cập nhật quá nhiều
    if (count($ids) > 1000) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chỉ có thể cập nhật tối đa 1000 hóa đơn cùng lúc')
        ]));
    }

    $updateCount = 0;
    $updatedTransIds = [];
    $total_amount = 0;
    $skipped_completed = 0; // Đếm số hóa đơn đã completed bị bỏ qua

    foreach ($ids as $id) {
        if ($id > 0) {
            $invoice = $CMSNT->get_row_safe("SELECT * FROM `payment_bank_invoice` WHERE `id` = ?", [$id]);
            if ($invoice) {
                $old_status = $invoice['status'];

                // Không cho phép thay đổi trạng thái nếu hóa đơn đã completed
                if ($old_status == 'completed') {
                    $skipped_completed++;
                    continue; // Bỏ qua hóa đơn này
                }

                // Chỉ cập nhật nếu trạng thái khác nhau
                if ($old_status != $new_status) {
                    // Nếu chuyển từ trạng thái khác sang completed, cộng tiền cho user
                    if ($new_status == 'completed') {
                        $user = new users();
                        $addCredit = $user->AddCredits(
                            $invoice['user_id'],
                            $invoice['received'],
                            '[Admin - Bulk Update] ' . __('Thanh toán hoá đơn nạp tiền') . ' #' . $invoice['trans_id'],
                            'bank_invoice_' . $invoice['trans_id']
                        );

                        // Ghi log nạp tiền
                        if ($addCredit) {
                            $CMSNT->insert('deposit_log', [
                                'user_id'       => $invoice['user_id'],
                                'method'        => $invoice['short_name'],
                                'amount'        => $invoice['amount'],
                                'received'      => $invoice['received'],
                                'create_time'   => time(),
                                'is_virtual'    => 0
                            ]);

                            $total_amount += floatval($invoice['received']);
                        }
                    }

                    // Cập nhật trạng thái hóa đơn
                    $isUpdate = $CMSNT->update("payment_bank_invoice", [
                        'status' => $new_status,
                        'updated_at' => gettime()
                    ], " `id` = '" . $id . "' ");

                    if ($isUpdate) {
                        $updatedTransIds[] = $invoice['trans_id'];
                        $updateCount++;
                    }
                }
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái hàng loạt %d hóa đơn nạp tiền sang "%s": %s'), $updateCount, $new_status, implode(', ', array_slice($updatedTransIds, 0, 5)) . (count($updatedTransIds) > 5 ? '...' : ''))
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('Cập nhật trạng thái hàng loạt %d hóa đơn nạp tiền sang "%s"'), $updateCount, $new_status), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        // Tạo thông báo kết quả
        $msg = sprintf(__('Đã cập nhật trạng thái thành công cho %d hóa đơn'), $updateCount);
        if ($skipped_completed > 0) {
            $msg .= sprintf(__('. %d hóa đơn đã hoàn thành không thể thay đổi trạng thái'), $skipped_completed);
        }

        die(json_encode([
            'status'    => 'success',
            'msg'       => $msg,
            'updated'   => $updateCount,
            'skipped'   => $skipped_completed
        ]));
    }

    // Nếu không có hóa đơn nào được cập nhật
    if ($skipped_completed > 0) {
        die(json_encode([
            'status' => 'error',
            'msg' => sprintf(__('Không thể cập nhật. %d hóa đơn đã hoàn thành không thể thay đổi trạng thái'), $skipped_completed)
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Không có hóa đơn nào được cập nhật')]));
}

// Cập nhật nhanh hàng loạt sản phẩm
if ($_POST['action'] == 'bulkQuickUpdateProducts') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một sản phẩm')
        ]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $fields = isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : [];

    if (empty($fields)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ít nhất một trường để cập nhật')
        ]));
    }

    // Kiểm tra các field điều chỉnh đặc biệt
    $hasSoldAdjust = isset($fields['sold_adjust_value']) && intval($fields['sold_adjust_value']) > 0;

    // Nếu có điều chỉnh sold_count, cần xử lý từng sản phẩm riêng
    if ($hasSoldAdjust || isset($fields['sold_count'])) {
        $updateCount = 0;
        $updateFields = [];

        foreach ($ids as $id) {
            if ($id <= 0) continue;

            // Lấy thông tin sản phẩm hiện tại
            $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$id]);
            if (!$product) continue;

            $updateData = [];

            // === CHUYÊN MỤC ===
            if (isset($fields['category_id']) && $fields['category_id'] !== '') {
                $updateData['category_id'] = intval($fields['category_id']);
                if (!in_array('category_id', $updateFields)) $updateFields[] = 'category_id';
            }

            // === TRẠNG THÁI ===
            if (isset($fields['status']) && $fields['status'] !== '') {
                $updateData['status'] = intval($fields['status']);
                if (!in_array('status', $updateFields)) $updateFields[] = 'status';
            }

            // === SỐ LƯỢNG ĐÃ BÁN ===
            // Đặt số lượng cụ thể
            if (isset($fields['sold_count']) && $fields['sold_count'] !== '') {
                $updateData['sold_count'] = intval($fields['sold_count']);
                if (!in_array('sold_count', $updateFields)) $updateFields[] = 'sold_count';
            }
            // Hoặc điều chỉnh số lượng
            elseif ($hasSoldAdjust) {
                $currentSold = intval($product['sold_count'] ?? 0);
                $adjustValue = intval($fields['sold_adjust_value']);
                $adjustType = $fields['sold_adjust_type'] ?? 'add';

                if ($adjustType === 'add') {
                    $updateData['sold_count'] = $currentSold + $adjustValue;
                } else {
                    $updateData['sold_count'] = max(0, $currentSold - $adjustValue);
                }
                if (!in_array('sold_count', $updateFields)) $updateFields[] = 'sold_count';
            }

            // Cập nhật sản phẩm
            if (!empty($updateData)) {
                $updateData['updated_at'] = gettime();
                if ($CMSNT->update('products', $updateData, " `id` = ? ", [$id])) {
                    $updateCount++;
                }
            }
        }

        if ($updateCount > 0) {
            $fieldsText = implode(', ', $updateFields);
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => sprintf(__('Cập nhật nhanh %d sản phẩm (các trường: %s)'), $updateCount, $fieldsText)
            ]);
            die(json_encode([
                'status'    => 'success',
                'msg'       => sprintf(__('Đã cập nhật thành công %d sản phẩm'), $updateCount)
            ]));
        }
        die(json_encode(['status' => 'error', 'msg' => __('Không có sản phẩm nào được cập nhật')]));
    }

    // Cập nhật hàng loạt không cần xử lý riêng từng sản phẩm
    $updateData = [];
    $updateFields = [];

    // Chuyên mục
    if (isset($fields['category_id']) && $fields['category_id'] !== '') {
        $updateData['category_id'] = intval($fields['category_id']);
        $updateFields[] = 'category_id';
    }

    // Trạng thái
    if (isset($fields['status']) && $fields['status'] !== '') {
        $updateData['status'] = intval($fields['status']);
        $updateFields[] = 'status';
    }

    if (empty($updateData)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không có trường nào để cập nhật')
        ]));
    }

    $updateData['updated_at'] = gettime();
    $updateCount = 0;

    foreach ($ids as $id) {
        if ($id > 0) {
            if ($CMSNT->update('products', $updateData, " `id` = ? ", [$id])) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $fieldsText = implode(', ', $updateFields);
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật nhanh %d sản phẩm (các trường: %s)'), $updateCount, $fieldsText)
        ]);
        die(json_encode([
            'status'    => 'success',
            'msg'       => sprintf(__('Đã cập nhật thành công %d sản phẩm'), $updateCount)
        ]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có sản phẩm nào được cập nhật')]));
}

if ($_POST['action'] == 'update_status_category') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isUpdate = $CMSNT->update("categories", [
        'status'    => !empty($_POST['status']) ? check_string($_POST['status']) : 0
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái chuyên mục (ID %s)'), check_string($_POST['id']))
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}




if ($_POST['action'] == 'update_status_table_category') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $id = intval(check_string($_POST['id']));
    if (!$category = $CMSNT->get_row(" SELECT * FROM `categories` WHERE `id` = '$id' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Chuyên mục không tồn tại trong hệ thống')]));
    }
    $status = !empty($_POST['status']) ? 'show' : 'hide';
    $isUpdate = $CMSNT->update("categories", [
        'status' => $status
    ], " `id` = '$id' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái chuyên mục (ID %s)'), $id)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}



if ($_POST['action'] == 'cancel_email_campaigns') {
    if (checkPermission($getUser['admin'], 'edit_email_campaigns') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isUpdate = $CMSNT->update("email_campaigns", [
        'status'  => 2
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

if ($_POST['action'] == 'setDefaultLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không tồn tại')]));
    }
    $id = check_string($_POST['id']);
    $row = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$id' ");
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không tồn tại')
        ]);
        die($data);
    }
    $CMSNT->update("languages", [
        'lang_default' => 0
    ], " `id` > 0 ");
    $isUpdate = $CMSNT->update("languages", [
        'lang_default' => 1
    ], " `id` = '$id' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Thiết lập ngôn ngữ mặc định (%s ID %s)'), $row['lang'], $row['id'])
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Thay đổi ngôn ngữ mặc định thành công')
        ]);
        die($data);
    }
}

if ($_POST['action'] == 'changeTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $isUpdate = $CMSNT->update("translate", [
        'value'  => check_string($_POST['value'])
    ], " `id` = '" . check_string($_POST['id']) . "' ");
    if ($isUpdate) {
        die(json_encode(['status' => 'success', 'msg' => __('Update successful!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Update failed!')]));
}

if ($_POST['action'] == 'setDefaultCurrency') {
    if (checkPermission($getUser['admin'], 'edit_currency') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    $id = check_string($_POST['id']);
    $row = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `id` = '$id' ");
    if (!$row) {
        $data = json_encode([
            'status'    => 'error',
            'msg'       => __('ID tiền tệ không tồn tại trong hệ thống')
        ]);
        die($data);
    }
    $CMSNT->update("currencies", [
        'default_currency' => 0
    ], " `id` > 0 ");
    $isUpdate = $CMSNT->update("currencies", [
        'default_currency' => 1
    ], " `id` = '$id' ");
    if ($isUpdate) {

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Set mặc định tiền tệ (%s ID %s)'), $row['name'], $row['id'])
        ]);
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Thay đổi trạng thái tiền tệ thành công')
        ]);
        die($data);
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
    }
}

if ($_POST['action'] == 'logoutALL') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    // Xóa tất cả phiên đăng nhập
    $CMSNT->remove("active_sessions", " `id` > 0 ");

    foreach ($CMSNT->get_list(" SELECT * FROM `users` WHERE `id` > 0 ") as $row) {
        $CMSNT->update('users', [
            'token'     => generateUltraSecureToken(32)
        ], " `id` = '" . $row['id'] . "' ");
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Log out all members on the system')
    ]);
    $data = json_encode([
        'status'    => 'success',
        'msg'       => __('Đăng xuất tất cả tài khoản thành công!')
    ]);
    die($data);
}

if ($_POST['action'] == 'changeAPIKey') {
    if (checkPermission($getUser['admin'], 'edit_user') != true) {
        die(json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền sử dụng tính năng này']));
    }
    foreach ($CMSNT->get_list(" SELECT * FROM `users`  ") as $row) {
        $CMSNT->update('users', [
            'api_key'     => generateUltraSecureToken(16)
        ], " `id` = '" . $row['id'] . "' ");
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Change API Key for all members')
    ]);

    $data = json_encode([
        'status'    => 'success',
        'msg'       => __('Thay đổi API KEY thành công!')
    ]);
    die($data);
}


// Thêm hàm xử lý cập nhật thứ tự chuyên mục khi kéo thả
if ($_POST['action'] == 'updateCategorySTT') {
    if (checkPermission($getUser['admin'], 'edit_category') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $order = $_POST['order'];

        foreach ($order as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            $position = isset($item['position']) ? intval($item['position']) : 0;

            if ($id > 0) {
                $CMSNT->update("categories", [
                    'stt' => $position
                ], " `id` = $id ");
            }
        }

        die(json_encode([
            'status' => 'success',
            'msg' => __('Cập nhật thứ tự thành công!')
        ]));
    }
}

if ($_POST['action'] == 'updateChildCategorySTT') {
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $order = $_POST['order'];

        foreach ($order as $item) {
            $id = isset($item['id']) ? intval($item['id']) : 0;
            $position = isset($item['position']) ? intval($item['position']) : 0;
            $parent_id = isset($item['parent_id']) ? intval($item['parent_id']) : 0;

            if ($id > 0 && $parent_id > 0) {
                // Cập nhật thứ tự cho danh mục con, đảm bảo nó thuộc danh mục cha đúng
                $check = $CMSNT->get_row("SELECT * FROM `categories` WHERE `id` = $id AND `parent_id` = $parent_id");
                if ($check) {
                    $CMSNT->update("categories", [
                        'stt' => $position
                    ], " `id` = $id ");
                }
            }
        }

        die(json_encode([
            'status' => 'success',
            'msg' => __('Cập nhật thứ tự chuyên mục con thành công!')
        ]));
    }
}
if ($_POST['action'] == 'updateCategorySubSTT') {
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }
    if (empty($_POST['order'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }
    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    foreach ($order as $item) {
        $id = intval($item['id']);
        $position = intval($item['position']);
        if ($id > 0) {
            $CMSNT->update("categories", [
                'stt' => $position
            ], " `id` = '$id' ");
        }
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cập nhật thứ tự sắp xếp chuyên mục con')
    ]);

    die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công')]));
}








if ($_POST['action'] == 'syncTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['lang_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy thông tin ngôn ngữ')]));
    }

    $lang_id = check_string($_POST['lang_id']);
    $row = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$lang_id' ");
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại trong hệ thống')]));
    }

    // Đọc file lang.php để lấy dữ liệu mặc định
    $langDefault = [];
    if (file_exists(__DIR__ . '/../../lang.php')) {
        include(__DIR__ . '/../../lang.php');
    }

    if (!empty($langDefault)) {
        $insertCount = 0;
        foreach ($langDefault as $key => $value) {
            $isExist = $CMSNT->get_row("SELECT * FROM `translate` WHERE `lang_id` = '$lang_id' AND `name` = '$key' ");
            if ($isExist) {
                continue;
            }
            $isInsert = $CMSNT->insert("translate", [
                'lang_id'   => $lang_id,
                'value'     => $value,
                'name'      => $key
            ]);
            if ($isInsert) {
                $insertCount++;
            }
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Đồng bộ bản dịch từ lang.php cho ngôn ngữ %s (%d items)'), $row['lang'], $insertCount)
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Đồng bộ bản dịch thành công! %d nội dung đã được đồng bộ.'), $insertCount),
            'count' => $insertCount
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy dữ liệu trong file lang.php')]));
    }
}

if ($_POST['action'] == 'updateTranslate') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['lang_id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy thông tin ngôn ngữ')]));
    }

    $lang_id = check_string($_POST['lang_id']);
    $row = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$lang_id' ");
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại trong hệ thống')]));
    }

    if ($row['lang_default'] == 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể thực hiện vì đây là ngôn ngữ mặc định của hệ thống')]));
    }

    // Xóa tất cả bản dịch của ngôn ngữ hiện tại
    $isDelete = $CMSNT->remove("translate", " `lang_id` = '$lang_id' ");

    if ($isDelete) {
        // Lấy ngôn ngữ mặc định
        $defaultLang = $CMSNT->get_row("SELECT * FROM `languages` WHERE `lang_default` = 1 ");
        if (!$defaultLang) {
            die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy ngôn ngữ mặc định')]));
        }

        // Sao chép bản dịch từ ngôn ngữ mặc định
        $defaultTranslations = $CMSNT->get_list("SELECT * FROM `translate` WHERE `lang_id` = '" . $defaultLang['id'] . "' ");
        if (!empty($defaultTranslations)) {
            $insertCount = 0;
            foreach ($defaultTranslations as $tran) {
                $isInsert = $CMSNT->insert("translate", [
                    'lang_id'   => $lang_id,
                    'value'     => $tran['value'],
                    'name'      => $tran['name']
                ]);
                if ($isInsert) {
                    $insertCount++;
                }
            }

            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'ip'            => myip(),
                'device'        => getUserAgent(),
                'createdate'    => gettime(),
                'action'        => sprintf(__('Tạo lại bản dịch cho ngôn ngữ %s từ ngôn ngữ mặc định %s (%d items)'), $row['lang'], $defaultLang['lang'], $insertCount)
            ]);

            die(json_encode([
                'status' => 'success',
                'msg' => sprintf(__('Tạo lại bản dịch thành công! %d nội dung đã được sao chép từ ngôn ngữ mặc định.'), $insertCount),
                'count' => $insertCount
            ]));
        } else {
            die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy bản dịch trong ngôn ngữ mặc định')]));
        }
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Có lỗi xảy ra khi xóa dữ liệu cũ')]));
    }
}

// Single item auto translate (for parallel processing)
if ($_POST['action'] == 'single_auto_translate') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = check_string($_POST['id']);
    $defaultText = check_string($_POST['text']);
    $target_lang = check_string($_POST['target_lang']); // vi => en

    if (empty($id) || empty($defaultText) || empty($target_lang)) {
        die(json_encode(['status' => 'error', 'msg' => __('Thiếu thông tin cần thiết để dịch')]));
    }

    // Gọi API dịch
    $apiUrl = 'https://api.cmsnt.co/translation-api.php';
    $url = $apiUrl . '?license_key=' . $CMSNT->site('license_key') . '&q=' . urlencode($defaultText) . '&target=' . urlencode($target_lang);

    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['data']['translations'][0]['translatedText'])) {
            $translatedText = $data['data']['translations'][0]['translatedText'];

            // Cập nhật vào database
            $isUpdate = $CMSNT->update("translate", [
                'value' => $translatedText
            ], " `id` = '$id' ");

            if ($isUpdate) {
                die(json_encode([
                    'status' => 'success',
                    'msg' => __('Dịch thành công'),
                    'id' => $id,
                    'translated_text' => $translatedText
                ]));
            } else {
                die(json_encode(['status' => 'error', 'msg' => __('Không thể cập nhật database')]));
            }
        } else {
            die(json_encode(['status' => 'error', 'msg' => __('API dịch trả về kết quả không hợp lệ')]));
        }
    } else {
        die(json_encode(['status' => 'error', 'msg' => __('Không thể kết nối đến API dịch')]));
    }
}

// Bulk auto translate (deprecated - kept for backward compatibility)
if ($_POST['action'] == 'bulk_auto_translate') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $translate_data = $_POST['translate_data'];
    $target_lang = check_string($_POST['target_lang']);

    if (empty($translate_data) || !is_array($translate_data)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một mục để dịch')]));
    }

    if (empty($target_lang)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng cập nhật ISO CODE ngôn ngữ trước khi thực hiện dịch tự động!')]));
    }

    // Kiểm tra giới hạn max_input_vars
    $maxInputVars = ini_get('max_input_vars');
    $maxAllowedItems = floor($maxInputVars / 6); // Mỗi item có khoảng 6 variables

    if (count($translate_data) > $maxAllowedItems) {
        die(json_encode([
            'status' => 'error',
            'msg' => sprintf(__('Vượt quá giới hạn max_input_vars (%d). Chỉ có thể dịch tối đa %d items cùng lúc. Vui lòng tăng max_input_vars trong php.ini hoặc dịch theo từng lô nhỏ hơn.'), $maxInputVars, $maxAllowedItems)
        ]));
    }

    $translated_count = 0;
    $failed_count = 0;

    foreach ($translate_data as $item) {
        $id = check_string($item['id']);
        $defaultText = check_string($item['name']);

        // Gọi API dịch
        $apiUrl = 'https://api.cmsnt.co/translation-api.php';
        $url = $apiUrl . '?license_key=' . $CMSNT->site('license_key') . '&q=' . urlencode($defaultText) . '&target=' . urlencode($target_lang);

        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['data']['translations'][0]['translatedText'])) {
                $translatedText = $data['data']['translations'][0]['translatedText'];

                // Cập nhật vào database
                $CMSNT->update("translate", [
                    'value' => $translatedText
                ], " `id` = '$id' ");

                $translated_count++;
            } else {
                $failed_count++;
            }
        } else {
            $failed_count++;
        }

        // Thêm delay để tránh spam API
        // usleep(500000); // 0.5 giây
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => "Bulk Auto Translate $translated_count items."
    ]);

    $msg = __('Đã dịch thành công') . ' ' . $translated_count . ' ' . __('bản dịch');
    if ($failed_count > 0) {
        $msg .= ', ' . $failed_count . ' ' . __('bản dịch thất bại');
    }

    die(json_encode([
        'status' => 'success',
        'msg' => $msg
    ]));
}

// Log bulk translate activity
if ($_POST['action'] == 'log_bulk_translate') {
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (!$getUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `token` = '" . check_string($_POST['token']) . "' AND `banned` = 0 AND `admin` != 0 ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng tính năng này')]));
    }
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $completed = intval(check_string($_POST['completed']));
    $failed = intval(check_string($_POST['failed']));

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => sprintf(__('Dịch song song %d bản dịch thành công, %d bản dịch thất bại'), $completed, $failed)
    ]);

    die(json_encode(['status' => 'success', 'msg' => __('Log đã được ghi')]));
}

// Cập nhật thông tin ngôn ngữ
if ($_POST['action'] == 'updateLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = intval(check_string($_POST['id']));
    if (!$language = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$id' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại trong hệ thống')]));
    }

    $stt = isset($_POST['stt']) ? intval(check_string($_POST['stt'])) : $language['stt'];
    $status = isset($_POST['status']) ? check_string($_POST['status']) : 0;

    $isUpdate = $CMSNT->update("languages", [
        'stt' => $stt,
        'status' => $status
    ], " `id` = '$id' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật ngôn ngữ %s (ID %s)'), $language['lang'], $id)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

// Cập nhật thứ tự ngôn ngữ
if ($_POST['action'] == 'updateLanguageOrder') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['order'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    $updateCount = 0;
    foreach ($order as $item) {
        $id = intval($item['id']);
        $position = intval($item['position']);
        if ($id > 0) {
            $isUpdate = $CMSNT->update("languages", [
                'stt' => $position
            ], " `id` = '$id' ");
            if ($isUpdate) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật thứ tự sắp xếp %d ngôn ngữ'), $updateCount)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thứ tự thất bại')]));
}

// Đặt ngôn ngữ mặc định
if ($_POST['action'] == 'setDefaultLanguage') {
    if (checkPermission($getUser['admin'], 'edit_lang') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = intval(check_string($_POST['id']));
    if (!$language = $CMSNT->get_row("SELECT * FROM `languages` WHERE `id` = '$id' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Ngôn ngữ không tồn tại trong hệ thống')]));
    }

    // Xóa mặc định của tất cả ngôn ngữ khác
    $CMSNT->update("languages", [
        'lang_default' => 'hide'
    ], " `id` != '$id' ");

    // Đặt ngôn ngữ hiện tại làm mặc định
    $isUpdate = $CMSNT->update("languages", [
        'lang_default' => 'show',
        'status' => 'show' // Đảm bảo ngôn ngữ mặc định luôn hiển thị
    ], " `id` = '$id' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Đặt ngôn ngữ %s làm mặc định'), $language['lang'])
        ]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã đặt %s làm ngôn ngữ mặc định!'), $language['lang'])]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Đặt ngôn ngữ mặc định thất bại')]));
}





// Cập nhật trạng thái chuyên mục
if ($_POST['action'] == 'updateCategoryStatus') {
    if (checkPermission($getUser['admin'], 'edit_category') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = intval(check_string($_POST['id']));
    $status = check_string($_POST['status']);

    if (!$category = $CMSNT->get_row("SELECT * FROM `categories` WHERE `id` = '$id' ")) {
        die(json_encode(['status' => 'error', 'msg' => __('Chuyên mục không tồn tại trong hệ thống')]));
    }

    if (!in_array($status, ['show', 'hide'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Trạng thái không hợp lệ')]));
    }

    $isUpdate = $CMSNT->update("categories", [
        'status' => $status
    ], " `id` = '$id' ");

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái chuyên mục (ID %s)'), $id)
        ]);

        $statusText = $status == 'show' ? __('hiển thị') : __('ẩn');
        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Đã cập nhật trạng thái chuyên mục thành %s'), $statusText)
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật trạng thái thất bại')]));
}



if ($_POST['action'] == 'updateProductStock') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);
    $stock_value = isset($_POST['stock_value']) ? trim(strip_tags($_POST['stock_value'])) : '';
    $status = isset($_POST['status']) ? validate_int($_POST['status'], 0, 1) : 1;

    if ($id === false || empty($stock_value)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
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

    $isUpdate = $CMSNT->update("product_stock", [
        'stock_value'   => $stock_value,
        'status'        => $status,
        'updated_at'    => gettime()
    ], "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Product Stock ID ' . $id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật kho hàng thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật kho hàng thất bại')]));
}

// Cập nhật trạng thái hàng loạt kho hàng
if ($_POST['action'] == 'bulkUpdateProductStockStatus') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : [];
    $status = validate_int($_POST['status'], 0, 1);

    if (empty($ids) || !is_array($ids) || $status === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    // Lọc IDs hợp lệ
    $validIds = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

    if (empty($validIds)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không có kho hàng hợp lệ để cập nhật')
        ]));
    }

    $updatedCount = 0;
    foreach ($validIds as $id) {
        $isUpdate = $CMSNT->update_safe("product_stock", [
            'status'        => $status,
            'updated_at'    => gettime()
        ], "`id` = ?", [$id]);

        if ($isUpdate) {
            $updatedCount++;
        }
    }

    if ($updatedCount > 0) {
        $statusText = $status == 1 ? __('Còn hàng') : __('Đã bán');
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái hàng loạt %d kho hàng thành: %s'), $updatedCount, $statusText)
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Đã cập nhật %d kho hàng thành: %s'), $updatedCount, $statusText)
        ]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không thể cập nhật kho hàng nào')]));
}

// Chuyển gói hàng loạt kho hàng
if ($_POST['action'] == 'bulkUpdateProductStockPlan') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : [];
    $plan_id = validate_int($_POST['plan_id'], 1);

    if (empty($ids) || !is_array($ids) || $plan_id === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Dữ liệu không hợp lệ')
        ]));
    }

    // Kiểm tra gói sản phẩm tồn tại và là gói instant
    $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ? AND `is_instant` = 1", [$plan_id]);
    if (!$plan) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Gói sản phẩm không tồn tại hoặc không phải gói tự động')
        ]));
    }

    // Lọc IDs hợp lệ
    $validIds = array_filter(array_map('intval', $ids), fn($id) => $id > 0);

    if (empty($validIds)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không có kho hàng hợp lệ để cập nhật')
        ]));
    }

    $updatedCount = 0;
    foreach ($validIds as $id) {
        $isUpdate = $CMSNT->update_safe("product_stock", [
            'plan_id'       => $plan_id,
            'updated_at'    => gettime()
        ], "`id` = ?", [$id]);

        if ($isUpdate) {
            $updatedCount++;
        }
    }

    if ($updatedCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Chuyển gói hàng loạt %d kho hàng sang: %s'), $updatedCount, $plan['name'])
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => sprintf(__('Đã chuyển %d kho hàng sang gói: %s'), $updatedCount, $plan['name'])
        ]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không thể chuyển gói kho hàng nào')]));
}

if ($_POST['action'] == 'updateCouponStatus') {
    if (checkPermission($getUser['admin'], 'edit_coupon') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);
    $status = validate_int($_POST['status'], 0, 1);

    if ($id === false || $status === false) {
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

    $isUpdate = $CMSNT->update("coupons", [
        'status'        => $status,
        'updated_at'    => gettime()
    ], "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Coupon Status (ID ' . $id . ') - Code: ' . $coupon['code']
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trạng thái mã giảm giá thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật trạng thái mã giảm giá thất bại')]));
}

// ==================== FLASH SALE ACTIONS ====================
if ($_POST['action'] == 'updateFlashSaleStatus') {
    if (checkPermission($getUser['admin'], 'edit_flash_sale') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $id = validate_int($_POST['id'], 1);
    $status = validate_int($_POST['status'], 0, 1);

    if ($id === false || $status === false) {
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

    $isUpdate = $CMSNT->update("flash_sales", [
        'status'        => $status,
        'updated_at'    => gettime()
    ], "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Flash Sale Status (ID ' . $id . ') - Name: ' . $flash_sale['name']
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trạng thái Flash Sale thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật trạng thái Flash Sale thất bại')]));
}

// ==================== BLOG CATEGORY ACTIONS ====================
if ($_POST['action'] == 'updateBlogCategory') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['id'], 1);
    $name = validate_string($_POST['name'], 255, 1);
    $slug = validate_string($_POST['slug'], 255, 1);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $meta_title = validate_string($_POST['meta_title'] ?? '', 255);
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';
    $status = validate_int($_POST['status'], 0, 1);

    if ($id === false || $name === false || $slug === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$id]);
    if (!$category) {
        die(json_encode(['status' => 'error', 'msg' => __('Chuyên mục không tồn tại')]));
    }

    // Kiểm tra slug trùng (trừ chính nó)
    $check_slug = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `slug` = ? AND `id` != ?", [$slug, $id]);
    if ($check_slug) {
        die(json_encode(['status' => 'error', 'msg' => __('Slug này đã tồn tại')]));
    }

    $update_data = [
        'name'              => $name,
        'slug'              => $slug,
        'description'       => $description,
        'meta_title'        => $meta_title ?: $name,
        'meta_description'  => $meta_description ?: $description,
        'meta_keywords'     => $meta_keywords,
        'status'            => $status,
        'updated_at'        => gettime()
    ];

    // Xử lý upload ảnh
    if (check_img('image') == true) {
        if (!empty($category['image']) && file_exists($category['image'])) {
            @unlink($category['image']);
        }
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/blog_cat_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['image']['tmp_name'];
        if (move_uploaded_file($tmp_name, $uploads_dir)) {
            $update_data['image'] = $uploads_dir;
        }
    }

    $isUpdate = $CMSNT->update("blog_categories", $update_data, " `id` = ? ", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Blog Category ID ' . $id . ' (' . $name . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật chuyên mục thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateBlogCategoryStatus') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['id'], 1);
    $status = validate_int($_POST['status'], 0, 1);

    if ($id === false || $status === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $isUpdate = $CMSNT->update("blog_categories", ['status' => $status], " `id` = ? ", [$id]);

    if ($isUpdate) {
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật trạng thái thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật trạng thái thất bại')]));
}

if ($_POST['action'] == 'updateBlogCategoriesOrder') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['order'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    foreach ($order as $item) {
        $id = intval($item['id']);
        $sort_order = intval($item['sort_order']);
        if ($id > 0) {
            $CMSNT->update("blog_categories", ['sort_order' => $sort_order], " `id` = ? ", [$id]);
        }
    }

    die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công')]));
}

// ==================== BLOG POST ACTIONS ====================
if ($_POST['action'] == 'updateBlog') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['id'], 1);
    $title = validate_string($_POST['title'], 255, 1);
    $slug = validate_string($_POST['slug'], 255, 1);
    $category_id = validate_int($_POST['category_id'], 0);
    $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $status = validate_string($_POST['status'], 20);
    $is_featured = validate_int($_POST['is_featured'], 0, 1);

    // SEO Meta
    $meta_title = validate_string($_POST['meta_title'] ?? '', 255);
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';

    // Published date
    $published_at = null;
    if ($status == 'published' || $status == 'scheduled') {
        $published_at_input = validate_string($_POST['published_at'] ?? '', 20);
        if ($published_at_input && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $published_at_input)) {
            $published_at = $published_at_input;
        }
    }

    if ($id === false || $title === false || $slug === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    if ($status === false || !in_array($status, ['draft', 'published', 'scheduled'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Trạng thái không hợp lệ')]));
    }
    if ($is_featured === false) {
        $is_featured = 0;
    }

    $blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` = ?", [$id]);
    if (!$blog) {
        die(json_encode(['status' => 'error', 'msg' => __('Bài viết không tồn tại')]));
    }

    // Kiểm tra slug trùng (trừ chính nó)
    $check_slug = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `slug` = ? AND `id` != ?", [$slug, $id]);
    if ($check_slug) {
        die(json_encode(['status' => 'error', 'msg' => __('Slug này đã tồn tại')]));
    }

    $update_data = [
        'category_id'       => $category_id ?: 0,
        'title'             => $title,
        'slug'              => $slug,
        'excerpt'           => $excerpt,
        'content'           => $content,
        'meta_title'        => $meta_title ?: $title,
        'meta_description'  => $meta_description ?: $excerpt,
        'meta_keywords'     => $meta_keywords,
        'is_featured'       => $is_featured,
        'status'            => $status,
        'updated_at'        => gettime()
    ];

    if ($published_at) {
        $update_data['published_at'] = $published_at;
    } elseif (empty($blog['published_at']) && ($status == 'published' || $status == 'scheduled')) {
        $update_data['published_at'] = gettime();
    }

    // Xử lý upload ảnh
    if (check_img('thumbnail') == true) {
        if (!empty($blog['thumbnail']) && file_exists($blog['thumbnail'])) {
            @unlink($blog['thumbnail']);
        }
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/blog_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['thumbnail']['tmp_name'];
        if (move_uploaded_file($tmp_name, $uploads_dir)) {
            $update_data['thumbnail'] = $uploads_dir;
        }
    }

    $isUpdate = $CMSNT->update("blogs", $update_data, " `id` = ? ", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Update Blog Post ID ' . $id . ' (' . $title . ')'
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật bài viết thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có thay đổi nào')]));
}

if ($_POST['action'] == 'updateBlogsOrder') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['order'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    $order = json_decode($_POST['order'], true);
    if (!is_array($order)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu sắp xếp không hợp lệ')]));
    }

    foreach ($order as $item) {
        $id = intval($item['id']);
        $sort_order = intval($item['sort_order']);
        if ($id > 0) {
            $CMSNT->update("blogs", ['sort_order' => $sort_order], " `id` = ? ", [$id]);
        }
    }

    die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thứ tự thành công')]));
}

if ($_POST['action'] == 'bulkUpdateBlogStatus') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một bài viết')]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $status = validate_string($_POST['status'], 20);

    if ($status === false || !in_array($status, ['draft', 'published', 'scheduled'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Trạng thái không hợp lệ')]));
    }

    $updateCount = 0;
    foreach ($ids as $id) {
        if ($id > 0) {
            $update_data = ['status' => $status, 'updated_at' => gettime()];
            if (($status == 'published' || $status == 'scheduled')) {
                $blog = $CMSNT->get_row_safe("SELECT `published_at` FROM `blogs` WHERE `id` = ?", [$id]);
                if ($blog && empty($blog['published_at'])) {
                    $update_data['published_at'] = gettime();
                }
            }
            if ($CMSNT->update("blogs", $update_data, " `id` = ? ", [$id])) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật trạng thái hàng loạt %d bài viết'), $updateCount)
        ]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã cập nhật trạng thái thành công cho %d bài viết'), $updateCount)]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có bài viết nào được cập nhật')]));
}

if ($_POST['action'] == 'bulkUpdateBlogCategory') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    if (empty($_POST['ids']) || !is_array($_POST['ids'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ít nhất một bài viết')]));
    }

    $ids = array_map('intval', $_POST['ids']);
    $category_id = validate_int($_POST['category_id'], 0);

    if ($category_id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Chuyên mục không hợp lệ')]));
    }

    $updateCount = 0;
    foreach ($ids as $id) {
        if ($id > 0) {
            if ($CMSNT->update("blogs", ['category_id' => $category_id, 'updated_at' => gettime()], " `id` = ? ", [$id])) {
                $updateCount++;
            }
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật chuyên mục hàng loạt %d bài viết'), $updateCount)
        ]);
        die(json_encode(['status' => 'success', 'msg' => sprintf(__('Đã cập nhật chuyên mục thành công cho %d bài viết'), $updateCount)]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có bài viết nào được cập nhật')]));
}

// Cập nhật thứ tự và vị trí banner (kéo thả)
if ($_POST['action'] == 'updateBannersOrderAndPosition') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $updates = json_decode($_POST['updates'] ?? '[]', true);

    if (!is_array($updates) || empty($updates)) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    $valid_positions = ['below_sliders', 'sidebar_left', 'sidebar_right', 'footer', 'top', 'content'];
    $updateCount = 0;

    foreach ($updates as $update) {
        $id = validate_int($update['id'] ?? 0, 1);
        $position = validate_string($update['position'] ?? '', 50);
        $sort_order = validate_int($update['sort_order'] ?? 0, 0, 9999);

        if ($id === false || $position === false || !in_array($position, $valid_positions)) {
            continue;
        }
        if ($sort_order === false) {
            $sort_order = 0;
        }

        if ($CMSNT->update("banners", [
            'position' => $position,
            'sort_order' => $sort_order,
            'updated_at' => gettime()
        ], " `id` = ? ", [$id])) {
            $updateCount++;
        }
    }

    if ($updateCount > 0) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => sprintf(__('Cập nhật vị trí/thứ tự %d banner'), $updateCount)
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Đã cập nhật banner thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Không có banner nào được cập nhật')]));
}

// Cập nhật thông tin banner (từ modal edit)
if ($_POST['action'] == 'editBanner') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['banner_id'] ?? 0, 1);

    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID Banner không hợp lệ')]));
    }

    $banner = $CMSNT->get_row_safe("SELECT * FROM `banners` WHERE `id` = ?", [$id]);
    if (!$banner) {
        die(json_encode(['status' => 'error', 'msg' => __('Banner không tồn tại')]));
    }

    $title = validate_string($_POST['title'] ?? '', 255);
    $link = validate_string($_POST['link'] ?? '', 500);
    $position = validate_string($_POST['position'] ?? '', 50);
    $sort_order = validate_int($_POST['sort_order'] ?? 0, 0, 9999);
    $status = validate_int($_POST['status'] ?? 1, 0, 1);

    $valid_positions = ['below_sliders', 'sidebar_left', 'sidebar_right', 'footer', 'top', 'content'];

    if ($title === false) $title = '';
    if ($link === false) $link = null;
    if ($position === false || !in_array($position, $valid_positions)) $position = $banner['position'];
    if ($sort_order === false) $sort_order = 0;
    if ($status === false) $status = 1;

    $update_data = [
        'title'      => $title,
        'link'       => $link,
        'position'   => $position,
        'sort_order' => $sort_order,
        'status'     => $status,
        'updated_at' => gettime()
    ];

    // Xử lý upload ảnh mới (nếu có)
    if (isset($_FILES['banner_image']) && check_img('banner_image') == true) {
        // Xóa ảnh cũ nếu tồn tại
        if (!empty($banner['image']) && file_exists(__DIR__ . '/../../' . $banner['image'])) {
            @unlink(__DIR__ . '/../../' . $banner['image']);
        }

        // Upload ảnh mới
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/banner_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['banner_image']['tmp_name'];
        $addimage = move_uploaded_file($tmp_name, __DIR__ . '/../../' . $uploads_dir);

        if ($addimage) {
            $update_data['image'] = $uploads_dir;
        }
    }

    $isUpdate = $CMSNT->update("banners", $update_data, "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Cập nhật banner')
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật banner thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật banner thất bại!')]));
}

// Cập nhật thông tin slider (từ modal edit)
if ($_POST['action'] == 'editSlider') {
    if (checkPermission($getUser['admin'], 'edit_sliders') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = validate_int($_POST['slider_id'] ?? 0, 1);

    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID Slider không hợp lệ')]));
    }

    $slider = $CMSNT->get_row_safe("SELECT * FROM `sliders` WHERE `id` = ?", [$id]);
    if (!$slider) {
        die(json_encode(['status' => 'error', 'msg' => __('Slider không tồn tại')]));
    }

    $title = validate_string($_POST['title'] ?? '', 255);
    $link = validate_string($_POST['link'] ?? '', 500);
    $sort_order = validate_int($_POST['sort_order'] ?? 0, 0, 9999);
    $status = validate_int($_POST['status'] ?? 1, 0, 1);

    if ($title === false) $title = '';
    if ($link === false) $link = null;
    if ($sort_order === false) $sort_order = 0;
    if ($status === false) $status = 1;

    $update_data = [
        'title'      => $title,
        'link'       => $link,
        'sort_order' => $sort_order,
        'status'     => $status,
        'updated_at' => gettime()
    ];

    // Xử lý upload ảnh mới (nếu có)
    if (isset($_FILES['slider_image']) && check_img('slider_image') == true) {
        // Xóa ảnh cũ nếu tồn tại
        if (!empty($slider['image']) && file_exists(__DIR__ . '/../../' . $slider['image'])) {
            @unlink(__DIR__ . '/../../' . $slider['image']);
        }

        // Upload ảnh mới
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/slider_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['slider_image']['tmp_name'];
        $addimage = move_uploaded_file($tmp_name, __DIR__ . '/../../' . $uploads_dir);

        if ($addimage) {
            $update_data['image'] = $uploads_dir;
        }
    }

    $isUpdate = $CMSNT->update("sliders", $update_data, "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Cập nhật slider')
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật slider thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật slider thất bại!')]));
}

/**
 * Tạo API Key mới
 */
if ($_POST['action'] == 'create_api_key') {
    if (checkPermission($getUser['admin'], 'edit_api_keys') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    require_once(__DIR__ . '/../../libs/services/ApiKeyService.php');

    // Validate input
    $username = isset($_POST['username']) ? validate_string($_POST['username'], 100) : '';
    $name = isset($_POST['name']) ? validate_string($_POST['name'], 100) : 'API Key';
    $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : ['orders.create', 'orders.view', 'products.view', 'balance.view'];
    $rate_limit = isset($_POST['rate_limit']) ? validate_int($_POST['rate_limit'], 1, 1000) : 60;
    $daily_limit = isset($_POST['daily_limit']) ? validate_int($_POST['daily_limit'], 100, 1000000) : 10000;

    if (empty($username)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập username')]));
    }

    // Tìm user
    $user = $CMSNT->get_row_safe("SELECT `id`, `username` FROM `users` WHERE `username` = ?", [$username]);
    if (!$user) {
        die(json_encode(['status' => 'error', 'msg' => __('User không tồn tại')]));
    }

    // Validate permissions
    $allowed_permissions = ['orders.create', 'orders.view', 'orders.create_for_others', 'products.view', 'balance.view', 'all'];
    $permissions = array_filter($permissions, function ($p) use ($allowed_permissions) {
        return in_array($p, $allowed_permissions);
    });

    if (empty($permissions)) {
        $permissions = ['orders.view', 'products.view'];
    }

    // Tạo API Key
    $ApiKeyService = new ApiKeyService();
    $result = $ApiKeyService->createApiKey($user['id'], $name ?: 'API Key', [
        'permissions' => $permissions,
        'rate_limit' => $rate_limit ?: 60,
        'daily_limit' => $daily_limit ?: 10000
    ]);

    if ($result === false) {
        die(json_encode(['status' => 'error', 'msg' => $ApiKeyService->getFirstError()]));
    }

    // Log
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Tạo API Key cho user') . " ({$user['username']})"
    ]);

    die(json_encode([
        'status' => 'success',
        'msg' => __('Tạo API Key thành công!'),
        'api_key' => $result['api_key'],
        'api_secret' => $result['api_secret']
    ]));
}

/**
 * Toggle trạng thái API Key
 */
if ($_POST['action'] == 'toggle_api_key') {
    if (checkPermission($getUser['admin'], 'edit_api_keys') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : 0;
    $status = isset($_POST['status']) ? validate_int($_POST['status'], 0, 1) : 0;

    if (!$id) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }

    $apiKey = $CMSNT->get_row_safe("SELECT * FROM `api_keys` WHERE `id` = ?", [$id]);
    if (!$apiKey) {
        die(json_encode(['status' => 'error', 'msg' => __('API Key không tồn tại')]));
    }

    $isUpdate = $CMSNT->update('api_keys', [
        'status' => $status,
        'updated_at' => gettime()
    ], "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => ($status == 1 ? __('Kích hoạt') : __('Vô hiệu hóa')) . " API Key (ID: {$id})"
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật thành công!')]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

/**
 * Tạo lại API Secret
 */
if ($_POST['action'] == 'regenerate_api_secret') {
    if (checkPermission($getUser['admin'], 'edit_api_keys') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : 0;

    if (!$id) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }

    $apiKey = $CMSNT->get_row_safe("SELECT * FROM `api_keys` WHERE `id` = ?", [$id]);
    if (!$apiKey) {
        die(json_encode(['status' => 'error', 'msg' => __('API Key không tồn tại')]));
    }

    // Generate secret mới (64 ký tự hex)
    $new_secret = bin2hex(random_bytes(32));

    $isUpdate = $CMSNT->update('api_keys', [
        'api_secret' => $new_secret,
        'updated_at' => gettime()
    ], "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Tạo lại API Secret') . " (ID: {$id})"
        ]);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã tạo API Secret mới!'),
            'api_secret' => $new_secret
        ]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

/**
 * Cập nhật Rate Limit cho API Key
 */
if ($_POST['action'] == 'update_api_rate_limit') {
    if (checkPermission($getUser['admin'], 'edit_api_keys') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    $id = isset($_POST['id']) ? validate_int($_POST['id'], 1) : 0;
    $rate_limit_per_minute = isset($_POST['rate_limit_per_minute']) ? validate_int($_POST['rate_limit_per_minute'], 1, 1000) : 60;
    $rate_limit_per_day = isset($_POST['rate_limit_per_day']) ? validate_int($_POST['rate_limit_per_day'], 1, 100000) : 10000;

    if (!$id) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }

    if ($rate_limit_per_minute === false || $rate_limit_per_day === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu rate limit không hợp lệ')]));
    }

    $apiKey = $CMSNT->get_row_safe("SELECT * FROM `api_keys` WHERE `id` = ?", [$id]);
    if (!$apiKey) {
        die(json_encode(['status' => 'error', 'msg' => __('API Key không tồn tại')]));
    }

    $isUpdate = $CMSNT->update('api_keys', [
        'rate_limit_per_minute' => $rate_limit_per_minute,
        'rate_limit_per_day' => $rate_limit_per_day,
        'updated_at' => gettime()
    ], "`id` = ?", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật Rate Limit API Key') . " (ID: {$id}) - {$rate_limit_per_minute}/min, {$rate_limit_per_day}/day"
        ]);

        die(json_encode(['status' => 'success', 'msg' => __('Cập nhật Rate Limit thành công!')]));
    }

    die(json_encode(['status' => 'error', 'msg' => __('Cập nhật thất bại')]));
}

/**
 * Cập nhật cấu hình API cho user
 */
if ($_POST['action'] == 'update_api_config') {
    if (checkPermission($getUser['admin'], 'edit_api_keys') != true) {
        die(json_encode(['status' => 'error', 'msg' => __('Bạn không có quyền sử dụng tính năng này')]));
    }

    // Validate input
    $api_user_enabled = isset($_POST['api_user_enabled']) ? validate_int($_POST['api_user_enabled'], 0, 1) : 0;
    $api_max_keys_per_user = isset($_POST['api_max_keys_per_user']) ? validate_int($_POST['api_max_keys_per_user'], 1, 100) : 5;
    $api_user_rate_limit_minute = isset($_POST['api_user_rate_limit_minute']) ? validate_int($_POST['api_user_rate_limit_minute'], 1, 1000) : 60;
    $api_user_rate_limit_day = isset($_POST['api_user_rate_limit_day']) ? validate_int($_POST['api_user_rate_limit_day'], 100, 1000000) : 10000;

    if ($api_max_keys_per_user === false || $api_user_rate_limit_minute === false || $api_user_rate_limit_day === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }

    // Cập nhật settings
    $settings = [
        'api_user_enabled' => $api_user_enabled,
        'api_max_keys_per_user' => $api_max_keys_per_user,
        'api_user_rate_limit_minute' => $api_user_rate_limit_minute,
        'api_user_rate_limit_day' => $api_user_rate_limit_day
    ];

    foreach ($settings as $key => $value) {
        // Kiểm tra setting đã tồn tại chưa
        $existing = $CMSNT->get_row_safe("SELECT `id` FROM `settings` WHERE `name` = ?", [$key]);

        if ($existing) {
            $CMSNT->update('settings', ['value' => $value], "`name` = ?", [$key]);
        } else {
            $CMSNT->insert('settings', ['name' => $key, 'value' => $value]);
        }
    }

    // Log hành động
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cập nhật cấu hình API cho user')
    ]);

    die(json_encode(['status' => 'success', 'msg' => __('Cập nhật cấu hình thành công!')]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
