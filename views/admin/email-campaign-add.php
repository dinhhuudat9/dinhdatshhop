<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Add Campaign'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '';
$body['footer'] = '';

require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');

if(checkPermission($getUser['admin'], 'edit_email_campaigns') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}

if (isset($_POST['submit'])) {
    // Kiểm tra CSRF token
    checkCSRF();
    
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Không được dùng chức năng này vì đây là trang web demo.').'")){window.history.back().location.reload();}</script>');
    }
    
    if(checkPermission($getUser['admin'], 'edit_email_campaigns') != true){
        die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
    }
    
    // Validate và sanitize input
    $name = validate_string($_POST['name'], 255, 1);
    $subject = validate_string($_POST['subject'], 500, 1);
    $cc = !empty($_POST['cc']) ? validate_email($_POST['cc']) : null;
    $bcc = !empty($_POST['bcc']) ? validate_email($_POST['bcc']) : null;
    $content = $_POST['content']; // HTML content, sẽ được xử lý khi gửi
    
    if ($name === false || $subject === false) {
        die('<script type="text/javascript">if(!alert("'.__('Dữ liệu không hợp lệ').'")){window.history.back();}</script>');
    }
    
    // Tạo chiến dịch
    $campaignId = $CMSNT->insert('email_campaigns', [
        'name'              => $name,
        'subject'           => $subject,
        'cc'                => $cc ?: null,
        'bcc'               => $bcc ?: null,
        'content'           => $content,
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime(),
        'status'            => 0
    ]);
    
    if ($campaignId) {
        // Thêm người nhận
        if (empty($_POST['listUser'])) {
            // Gửi cho tất cả user có email
            $users = $CMSNT->get_list_safe(
                "SELECT `id` FROM `users` WHERE `banned` = 0 AND `email` IS NOT NULL AND `email` != ''",
                []
            );
            foreach ($users as $user) {
                $CMSNT->insert('email_sending', [
                    'camp_id'           => $campaignId,
                    'user_id'           => $user['id'],
                    'status'            => 0,
                    'create_gettime'    => gettime(),
                    'update_gettime'    => gettime()
                ]);
            }
        } else {
            // Gửi cho danh sách user đã chọn
            $listUser = validate_array($_POST['listUser'], 'validate_int', 1000);
            if ($listUser !== false) {
                foreach ($listUser as $userId) {
                    // Kiểm tra user tồn tại và có email
                    $userExists = $CMSNT->get_row_safe(
                        "SELECT `id` FROM `users` WHERE `id` = ? AND `email` IS NOT NULL AND `email` != ''",
                        [$userId]
                    );
                    if ($userExists) {
                        $CMSNT->insert('email_sending', [
                            'camp_id'           => $campaignId,
                            'user_id'           => $userId,
                            'status'            => 0,
                            'create_gettime'    => gettime(),
                            'update_gettime'    => gettime()
                        ]);
                    }
                }
            }
        }
        
        // Log hành động
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Tạo chiến dịch Email Marketing')." ({$name})"
        ]);
        
        // Gửi thông báo admin
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Tạo chiến dịch Email Marketing')." ({$name})", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        
        die('<script type="text/javascript">if(!alert("'.__('Thành công!').'")){location.href = "'.base_url_admin('email-campaigns').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("'.__('Thất bại!').'")){window.history.back().location.reload();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-inbox"></i> <?=__('Tạo chiến dịch Email Marketing');?></h1>
        </div>
        
        <?php if($CMSNT->site('smtp_status') != 1):?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>
            <?=__('SMTP chưa được kích hoạt.');?> 
            <a href="<?=base_url_admin('settings&tab=smtp');?>" class="alert-link"><?=__('Cấu hình ngay');?></a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif?>
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('TẠO CHIẾN DỊCH EMAIL MARKETING');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <?php echo csrfField(); ?>
                            
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?=__('Tên chiến dịch');?> <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="text" placeholder="<?=__('Nhập tên cho chiến dịch');?>" name="name" required maxlength="255">
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?=__('Người nhận');?></label>
                                <div class="col-sm-9">
                                    <select class="form-control" name="listUser[]" id="listUser" multiple>
                                        <option value=""><?=__('Mặc định sẽ gửi cho tất cả thành viên');?></option>
                                        <?php 
                                        $users = $CMSNT->get_list_safe(
                                            "SELECT `id`, `username`, `email` FROM `users` WHERE `banned` = 0 AND `email` IS NOT NULL AND `email` != '' ORDER BY `id` DESC LIMIT 1000",
                                            []
                                        );
                                        foreach ($users as $user): ?>
                                        <option value="<?=$user['id'];?>">
                                            ID: <?=$user['id'];?> | <?=htmlspecialchars($user['username']);?> | <?=htmlspecialchars($user['email']);?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?=__('Để trống sẽ gửi cho tất cả thành viên có email');?></small>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?=__('Tiêu đề Mail');?> <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="text" name="subject" required maxlength="500" placeholder="<?=__('Tiêu đề email sẽ được hiển thị');?>">
                                    <small class="text-muted"><?=__('Có thể dùng: {username}, {email}, {domain}, {title}');?></small>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?=__('CC');?></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="email" name="cc" placeholder="cc@example.com">
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?=__('BCC');?></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="email" name="bcc" placeholder="bcc@example.com">
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <label class="col-sm-12 col-form-label"><?=__('Nội dung Email');?> <span class="text-danger">*</span></label>
                                <div class="col-sm-12">
                                    <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                                    <small class="text-muted"><?=__('Có thể dùng: {username}, {email}, {domain}, {title}, {time}, {year}');?></small>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a class="btn btn-danger" href="<?=base_url_admin('email-campaigns');?>">
                                    <i class="fa fa-fw fa-undo me-1"></i> <?=__('Quay lại');?>
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fa fa-fw fa-plus me-1"></i> <?=__('Tạo chiến dịch');?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script>
CKEDITOR.replace("content");

const multipleCancelButton = new Choices('#listUser', {
    allowHTML: true,
    removeItemButton: true,
    placeholder: true,
    placeholderValue: '<?=__('Chọn người nhận hoặc để trống...');?>'
});
</script>

</script>