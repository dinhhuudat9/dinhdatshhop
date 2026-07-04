<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
require_once 'libs/session.php';
// Xóa phiên trong active_sessions
if(isset($_COOKIE['user_login'])) {
    $deviceToken = getOrCreateDeviceToken();
    $CMSNT->remove("active_sessions", 
        " `session_token` = '".check_string($_COOKIE['user_login'])."' 
        AND `device_token` = '".$deviceToken."' ");
}

// Xóa cookie
setcookie('login', '', time() - 3600, '/');
setcookie('admin_login', '', time() - 3600, '/');
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_login', '', time() - 3600, '/');
setcookie('user_agent', '', time() - 3600, '/');

// Xóa session
session_unset(); // Xóa tất cả các biến session
session_destroy(); // Hủy session
redirect(base_url('client/login'));
