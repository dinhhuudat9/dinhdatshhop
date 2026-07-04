<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$CMSNT = new DB();
require_once __DIR__.'/../libs/session.php';
 

if (isSecureCookie('user_login') != true) {
    redirect(base_url('client/logout'));
} else {
    $user_token = validate_alphanumeric($_COOKIE['user_login']);
    if ($user_token === false) {
        checkBlockIP('ADMIN');
        redirect(base_url('client/logout'));
    }
    $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `token` = ? AND `admin` > 0 ", [$user_token]);
    // chuyển hướng đăng nhập khi thông tin login không tồn tại
    if (!$getUser) {
        // Rate limit
        checkBlockIP('ADMIN');
        redirect(base_url('client/logout'));
    }

    // Kiểm tra phiên đăng nhập trong active_sessions
    $deviceToken = getOrCreateDeviceToken();
    $user_id = validate_int($getUser['id'], 1);
    if ($user_id === false) {
        checkBlockIP('ADMIN');
        redirect(base_url('client/logout'));
    }
    $activeSession = $CMSNT->get_row_safe("SELECT * FROM `active_sessions` 
        WHERE `user_id` = ? 
        AND `session_token` = ?
        AND `device_token` = ?", [$user_id, $user_token, $deviceToken]);
    
    if (!$activeSession) {
        // Rate limit
        checkBlockIP('ADMIN');
        redirect(base_url('client/logout'));
    }

    // Cập nhật thời gian hoạt động của phiên
    $session_id = validate_int($activeSession['id'], 1);
    if ($session_id !== false) {
        $CMSNT->update("active_sessions", [
            'last_activity' => gettime(),
            'ip_address' => myip(),
            'user_agent' => getUserAgent()
        ], " `id` = ? ", [$session_id]);
    }

    // Chuyển hướng khi bị khoá tài khoản
    if ($getUser['banned'] != 0) {
        require_once(__DIR__.'/../views/common/banned.php');
        exit();
    }
    if ($getUser['admin'] <= 0) {
        // Rate limit
        checkBlockIP('ADMIN');
        redirect(base_url('client/logout'));
    }
    // nếu phát hiện người dùng đang online thì ngăn chặn khôi phục mật khẩu
    if(!empty($getUser['token_forgot_password'])){
        $CMSNT->update('users', [
            'token_forgot_password' => NULL
        ], " `id` = ? ", [$user_id]);
    }
    /* cập nhật thời gian online */
    $CMSNT->update("users", [
        'time_session'  => time()
    ], " `id` = ? ", [$user_id]);
}

  