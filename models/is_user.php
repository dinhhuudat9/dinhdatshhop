<?php

if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$CMSNT = new DB();
require_once 'libs/session.php';
 

if (isSecureCookie('user_login') != true) {
    redirect(base_url('client/logout'));
} else {
    $user_token = validate_alphanumeric($_COOKIE['user_login']);
    if ($user_token === false) {
        checkBlockIP('LOGIN');
        redirect(base_url('client/logout'));
    }
    $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `token` = ? ", [$user_token]);
    // chuyển hướng đăng nhập khi thông tin login không tồn tại
    if (!$getUser) {
        // Rate limit
        checkBlockIP('LOGIN');
        redirect(base_url('client/logout'));
    }

    // Kiểm tra phiên đăng nhập trong active_sessions
    $deviceToken = getOrCreateDeviceToken();
    $user_id = validate_int($getUser['id'], 1);
    if ($user_id === false) {
        checkBlockIP('LOGIN');
        redirect(base_url('client/logout'));
    }
    $activeSession = $CMSNT->get_row_safe("SELECT * FROM `active_sessions` 
        WHERE `user_id` = ? 
        AND `session_token` = ?
        AND `device_token` = ?", [$user_id, $user_token, $deviceToken]);
    
    if (!$activeSession) {
        // Rate limit
        checkBlockIP('LOGIN');
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
    // Khoá tài khoản trường hợp âm tiền, tránh bug
    if ($getUser['money'] < -500) {
        $User = new users();
        $User->Banned($getUser['id'], 'Tài khoản âm tiền, ghi vấn bug');
        require_once(__DIR__.'/../views/common/banned.php');
        exit();
    }
    // Nếu phát hiện người dùng đang online thì ngăn chặn khôi phục mật khẩu
    if(!empty($getUser['token_forgot_password'])){
        $CMSNT->update('users', [
            'token_forgot_password' => NULL
        ], " `id` = ? ", [$user_id]);
    }
    /* Cập nhật thời gian online */
    $CMSNT->update("users", [
        'time_session'  => time()
    ], " `id` = ? ", [$user_id]);

    // Set cấp bậc khi đủ điều kiện
    updateUserRank($getUser['id'], $getUser['total_money']);
}


  