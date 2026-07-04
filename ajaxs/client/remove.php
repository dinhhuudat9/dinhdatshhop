<?php

define("IN_SITE", true);
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");
require_once(__DIR__."/../../config.php");
if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
if(!isset($_POST['action'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => 'The Request Not Found'
    ]);
    die($data);   
}

// Kiểm tra CSRF token cho tất cả request
checkCSRFAjax();

if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}
if($_POST['action'] == 'removeOrder'){
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 ", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    if(!isset($_POST['id'])){
        die(json_encode(['status' => 'error', 'msg' => __('The ID to delete does not exist')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    if (!$row = $CMSNT->get_row_safe("SELECT * FROM `product_order` WHERE `id` = ? AND `buyer` = ?", [$id, $getUser['id']])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Đơn hàng không tồn tại trong hệ thống')
        ]));
    }
    $isRemove = $CMSNT->update("product_order", [
        'trash' => 1
    ], " `id` = ? ", [$row['id']]);
    if ($isRemove) {
    
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Delete order').' ('.$row['trans_id'].')'
        ]);
        die(json_encode([
            'status'    => 'success',
            'msg'       => __('Xóa đơn hàng thành công!')
        ]));
    }
}

if($_POST['action'] == 'cancelScheduledOrder'){
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    $token = validate_string($_POST['token'], 255);
    if ($token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu không hợp lệ')]));
    }
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 ", [$token])) {
        die(json_encode(['status' => 'error', 'msg' => __('Please login')]));
    }
    if(!isset($_POST['id'])){
        die(json_encode(['status' => 'error', 'msg' => __('The ID to delete does not exist')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('ID không hợp lệ')]));
    }
    
    // Kiểm tra đơn hàng có tồn tại và thuộc về user không
    if (!$order = $CMSNT->get_row_safe("SELECT * FROM `scheduled_orders` WHERE `id` = ? AND `user_id` = ?", [$id, $getUser['id']])) {
        die(json_encode(['status' => 'error', 'msg' => __('Đơn hàng không tồn tại hoặc không thuộc về bạn')]));
    }
    
    // Kiểm tra trạng thái đơn hàng có thể hủy không
    if ($order['status'] != 'pending') {
        die(json_encode(['status' => 'error', 'msg' => __('Chỉ có thể hủy đơn hàng đang chờ đến thời gian chạy')]));
    }
    
    // Cập nhật trạng thái đơn hàng thành cancelled
    $isUpdate = $CMSNT->update("scheduled_orders", [
        'status'        => 'cancelled',
        'reason'        => __('Hủy đơn đặt lịch bởi người dùng')
    ], " `id` = ? ", [$id]);
    
    if ($isUpdate) {
        // Ghi log hoạt động
    
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Hủy đơn hàng đặt lịch').' #'.$id
        ]);
        
        die(json_encode([
            'status' => 'success', 
            'msg' => __('Hủy đơn hàng thành công!')
        ]));
    } else {
        die(json_encode([
            'status' => 'error', 
            'msg' => __('Có lỗi xảy ra khi hủy đơn hàng, vui lòng thử lại')
        ]));
    }
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));

