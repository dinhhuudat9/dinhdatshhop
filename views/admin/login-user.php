<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('Login User'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];

require_once(__DIR__.'/../../models/is_admin.php');

// Kiểm tra quyền login_user
if(checkPermission($getUser['admin'], 'login_user') != true){
    die('<script type="text/javascript">if(!alert("'._('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}

// Kiểm tra ID user được truyền vào
if(empty($_GET['id'])){
    die('<script type="text/javascript">if(!alert("'._('Thiếu thông tin user').'")){window.history.back();}</script>');
}

$user_id = intval(check_string($_GET['id']));

// Lấy thông tin user cần đăng nhập
$targetUser = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '$user_id'");
if(!$targetUser){
    die('<script type="text/javascript">if(!alert("'._('User không tồn tại').'")){window.history.back();}</script>');
}

// Kiểm tra user có bị ban không
if($targetUser['banned'] == 1){
    die('<script type="text/javascript">if(!alert("'._('User này đã bị khóa').'")){window.history.back();}</script>');
}

// Lưu thông tin admin hiện tại vào cookie để có thể quay lại
$admin_backup_data = json_encode([
    'admin_id' => $getUser['id'],
    'admin_token' => $getUser['token'],
    'login_time' => time()
]);

// Mã hóa dữ liệu admin để bảo mật
$admin_backup_encrypted = base64_encode($admin_backup_data);

// Lưu vào cookie với thời hạn 1 giờ
setcookie('admin_backup', $admin_backup_encrypted, time() + 3600, "/", "", false, true);

// Tạo session mới cho user
createSession($targetUser['id'], $targetUser['token']);

// Ghi log hoạt động
$CMSNT->insert("logs", [
    'user_id'       => $getUser['id'],
    'ip'            => myip(),
    'device'        => getUserAgent(),
    'createdate'    => gettime(),
    'action'        => sprintf(__('[Admin] Đăng nhập vào tài khoản user: %s (ID: %s)'), $targetUser['username'], $targetUser['id'])
]);


// Chuyển hướng về trang chủ client
redirect(base_url());
?> 