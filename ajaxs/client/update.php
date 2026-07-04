<?php

use GuzzleHttp\Promise\Is;

define("IN_SITE", true);
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");
require_once(__DIR__."/../../config.php");
require_once(__DIR__.'/../../libs/database/users.php');

if(!isset($_POST['action'])){
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);   
}

// Kiểm tra CSRF token cho tất cả request (trừ changeLanguage, changeCurrency)
$csrfExcludedActions = ['changeLanguage', 'changeCurrency'];
if (!in_array($_POST['action'], $csrfExcludedActions)) {
    checkCSRFAjax();
}

if($_POST['action'] == 'changeLanguage'){
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `languages` WHERE `id` = ? ", [$id]);
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $isUpdate = setLanguage($id);
    if ($isUpdate) {
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Change language successfully')
        ]);
        die($data);
    }
}

if($_POST['action'] == 'changeCurrency'){
    if (empty($_POST['id'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $id = validate_int($_POST['id'], 1);
    if ($id === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $row = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? ", [$id]);
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => __('Data does not exist')]));
    }
    $isUpdate = setCurrency($id);
    if ($isUpdate) {
        $data = json_encode([
            'status'    => 'success',
            'msg'       => __('Successful currency change')
        ]);
        die($data);
    }
}

if($_POST['action'] == 'backToAdmin'){
    // Kiểm tra cookie admin_backup có tồn tại không
    if(!isset($_COOKIE['admin_backup'])){
        die(json_encode(['status' => 'error', 'msg' => __('Phiên admin không tồn tại hoặc đã hết hạn')]));
    }
    
    // Giải mã dữ liệu admin từ cookie
    $admin_backup_encrypted = $_COOKIE['admin_backup'];
    $admin_backup_data = base64_decode($admin_backup_encrypted);
    $admin_info = json_decode($admin_backup_data, true);
    
    if(!$admin_info || !isset($admin_info['admin_id']) || !isset($admin_info['admin_token'])){
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu admin không hợp lệ')]));
    }
    
    // Kiểm tra admin có tồn tại không
    $admin_id = validate_int($admin_info['admin_id'], 1);
    $admin_token = validate_string($admin_info['admin_token'], 255);
    if ($admin_id === false || $admin_token === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Dữ liệu admin không hợp lệ')]));
    }
    
    $adminUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ? AND `token` = ? AND `admin` > 0 AND `banned` = 0", [$admin_id, $admin_token]);
    if(!$adminUser){
        // Rate limit
        checkBlockIP('ADMIN', 5);
        die(json_encode(['status' => 'error', 'msg' => __('Tài khoản admin không tồn tại hoặc đã bị khóa')]));
    }
    
    // Kiểm tra thời gian hết hạn (1 giờ)
    if(time() - $admin_info['login_time'] > 3600){
        // Xóa cookie đã hết hạn
        setcookie('admin_backup', '', time() - 3600, "/");
        die(json_encode(['status' => 'error', 'msg' => __('Phiên admin đã hết hạn')]));
    }
    // Thay đổi cookie user_login với token admin
    setcookie('user_login', $admin_token, time() + $CMSNT->site('session_login'), "/", "", false, true);
    
    // Ghi log
    $CMSNT->insert("logs", [
        'user_id'       => $adminUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('[System] Quay lại tài khoản admin từ chế độ login user')
    ]);
    
    // Xóa cookie backup sau khi đã sử dụng
    setcookie('admin_backup', '', time() - 3600, "/");
    
    die(json_encode(['status' => 'success', 'msg' => __('Quay lại admin thành công'), 'url' => base_url_admin('users')]));
}


if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}














// CHỨC NĂNG KHÔNG DÙNG ĐƯỢC TẠI TRANG WEB DEMO
 
 


die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));