<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Trang thanh toán thủ công'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

 
';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $CMSNT = new DB();
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `payment_manual` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url_admin('recharge-payment'));
    }
} else {
    redirect(base_url_admin('recharge-payment'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_recharge') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['save'])) {
    // Kiểm tra CSRF token
    checkCSRF();
    
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    if (check_img('icon') == true) {
        unlink($row['icon']);
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $uploads_dir = 'assets/storage/images/icon_gateway'.$rand.'.png';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('payment_manual', [
                'icon'  => 'assets/storage/images/icon_gateway'.$rand.'.png'
            ], " `id` = '$id' ");
        }
    }
    $rawContent = isset($_POST['content']) ? $_POST['content'] : '';
    $cleanContent = sanitize_html_content($rawContent);
    if (trim($cleanContent) === '' && trim($rawContent) !== '') {
        // Fallback để tránh mất nội dung nếu sanitizer gặp lỗi parsing
        $cleanContent = $rawContent;
    }
    $isUpdate = $CMSNT->update("payment_manual", [
        'title'             => check_string($_POST['title']),
        'description'       => check_string($_POST['description']),
        'slug'              => check_string($_POST['slug']),
        'content'           => base64_encode($cleanContent),
        'display'           => check_string($_POST['display']),
        'update_gettime'    => gettime()
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật trang thanh toán thủ công')." (".check_string($_POST['title']).")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Cập nhật trang thanh toán thủ công')." (".check_string($_POST['title']).").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("Lưu thành công !")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("Lưu thất bại !")){window.history.back().location.reload();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?=__('Chỉnh sửa trang');?> <?=$row['title'];?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?=__('Nạp tiền');?></a></li>
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('recharge-manual');?>"><?=__('Manual Payment');?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?=__('Chỉnh sửa trang');?>
                            <?=$row['title'];?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('CHỈNH SỬA TRANG');?>
                        </div>
                        <div class="d-flex">

                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Title');?>:
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input name="title" type="text" value="<?=$row['title'];?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Description');?>:</label>
                                <div class="col-sm-8">
                                    <textarea class="form-control" name="description"><?=$row['description'];?></textarea>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Slug');?> :
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input name="slug" type="text" value="<?=$row['slug'];?>" class="form-control" required>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Icon');?>:</label>
                                <div class="col-sm-8">
                                    <input type="file" class="custom-file-input mb-3" name="icon">
                                    <img width="200px" src="<?=BASE_URL($row['icon']);?>" />
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label"
                                    for="example-hf-email"><?=__('Nội dung chi tiết');?>:</label>
                                <div class="col-sm-12">
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span class="align-self-center small text-muted me-2"><?=__('Chèn nhanh');?>:</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-insert-token" data-token="{username}"><i class="ri-user-line me-1"></i>{username}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-insert-token" data-token="{id}"><i class="ri-hashtag me-1"></i>{id}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-insert-token" data-token="{hotline}"><i class="ri-phone-line me-1"></i>{hotline}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-insert-token" data-token="{email}"><i class="ri-mail-line me-1"></i>{email}</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-insert-token" data-token="{fanpage}"><i class="ri-external-link-line me-1"></i>{fanpage}</button>
                                        <button type="button" class="btn btn-sm btn-dark ms-auto" id="btn-ai-manual" title="<?=__('Tạo nội dung bằng AI');?>">
                                            <i class="ri-magic-line me-1"></i><?=__('Generated by AI');?>
                                        </button>
                                    </div>
                                    <textarea class="content" id="content" name="content"><?=base64_decode($row['content']);?></textarea>
                                    <div class="mt-2">
                                        <ul class="mb-0">
                                            <li><strong>{username}</strong> => <?=__('Username của khách hàng.');?></li>
                                            <li><strong>{id}</strong> => <?=__('ID của khách hàng.');?></li>
                                            <li><strong>{hotline}</strong> => <?=__('Hotline đã nhập trong cài đặt.');?></li>
                                            <li><strong>{email}</strong> => <?=__('Email đã nhập trong cài đặt.');?></li>
                                            <li><strong>{fanpage}</strong> => <?=__('Fanpage đã nhập trong cài đặt.');?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Trạng thái:');?>
                                    <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <select class="form-control" name="display" required>
                                        <option <?=$row['display'] == 1 ? 'selected' : '';?> value="1">ON</option>
                                        <option <?=$row['display'] == 0 ? 'selected' : '';?> value="0">OFF</option>
                                    </select>
                                </div>
                            </div>


                            <a type="button" class="btn btn-hero btn-danger"
                                href="<?=base_url_admin('recharge-manual');?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Back');?></a>
                            <button type="submit" name="save" class="btn btn-hero btn-success"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Save');?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<?php
require_once(__DIR__.'/footer.php');
?>


<script>
CKEDITOR.replace("content");
</script>

<script>
// Chèn token vào CKEditor tại vị trí con trỏ
document.querySelectorAll('.btn-insert-token').forEach(function(btn){
    btn.addEventListener('click', function(){
        var token = this.getAttribute('data-token') || '';
        if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.content) {
            CKEDITOR.instances.content.insertText(token);
            CKEDITOR.instances.content.focus();
        }
    });
});

// Generated by AI cho Manual Payment
document.getElementById('btn-ai-manual')?.addEventListener('click', function(){
    var btn = this;
    var originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i><?=__('Đang tạo...');?>';

    $.ajax({
        url: '<?=base_url('ajaxs/admin/ai.php');?>',
        method: 'POST',
        dataType: 'JSON',
        data: {
            action: 'generateManualPaymentContent',
            gateway_title: (function(){
                var input = document.querySelector('input[name="title"]');
                return input ? input.value : '';
            })()
        },
        success: function(res){
            if(res.success){
                if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.content) {
                    CKEDITOR.instances.content.setData(res.content);
                    CKEDITOR.instances.content.focus();
                }
                showMessage('<?=__('Đã tạo nội dung bằng AI');?>', 'success');
            }else{
                showMessage(res.message || '<?=__('Không thể tạo nội dung');?>', 'error');
            }
        },
        error: function(){
            showMessage('<?=__('Lỗi kết nối');?>', 'error');
        },
        complete: function(){
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
});
</script>
                                    