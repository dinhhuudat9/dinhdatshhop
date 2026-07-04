<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa Task'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="'.BASE_URL('public/AdminLTE3/').'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__.'/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `automations` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url_admin('automations'));
    }
} else {
    redirect(base_url_admin('automations'));
}
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'edit_automations') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveTask'])) {
    // Kiểm tra CSRF token
    checkCSRF();
    
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('This function cannot be used because this is a demo site').'")){window.history.back().location.reload();}</script>');
    }
    if(checkPermission($getUser['admin'], 'edit_automations') != true){
        die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
    }
    if(empty($_POST['type'])){
        die('<script type="text/javascript">if(!alert("'.__('Vui lòng chọn loại công việc').'")){window.history.back().location.reload();}</script>');
    }
    $type = check_string($_POST['type']);
    if(empty($_POST['product_id'])){
        $product_id = NULL;
    }else{
        $product_id = json_encode($_POST['product_id']);
    }
    
    if(empty($_POST['schedule'])){
        die('<script type="text/javascript">if(!alert("'.__('Vui lòng nhập thời gian').'")){window.history.back().location.reload();}</script>');
    }
    $schedule = check_string($_POST['schedule']);
    
    $isUpdate = $CMSNT->update("automations", [
        'name'              => !empty($_POST['name']) ? check_string($_POST['name']) : NULL,
        'type'              => $type,
        'product_id'        => $product_id,
        'schedule'          => $schedule,
        'other'             => !empty($_POST['other']) ? check_string($_POST['other']) : NULL,
        'update_gettime'    => gettime()
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Task Automation (".$row['name'].")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Task Automation (".$row['name'].").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("'.__('Lưu thành công!').'")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("'.__('Lưu thất bại!').'")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-tags"></i> <?=__('Chỉnh sửa công việc');?> '<b
                    style="color:red;"><?=$row['name'];?></b>'</h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?=base_url_admin('automations');?>"><?=__('Automations');?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=__('Edit Task');?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('CHỈNH SỬA CÔNG VIỆC');?>
                        </div>
                    </div>
                    <div class="card-body" onchange="loadform()">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Tên công việc');?></label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <textarea class="form-control" name="name" placeholder="<?=__('Nhập tên mô tả task nếu có');?>"><?=$row['name'];?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Loại công việc');?> (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <select class="form-control" name="type" id="type">
                                            <option value=""> -- <?=__('Chọn loại công việc');?> --</option>
                                            <option value="delete_order" <?=$row['type'] == 'delete_order' ? 'selected' : '';?>><?=__('Xóa đơn hàng đã bán');?></option>
                                            <option value="delete_recharge_history" <?=$row['type'] == 'delete_recharge_history' ? 'selected' : '';?>><?=__('Xóa lịch sử nạp tiền');?></option>
                                            <option value="delete_users_no_recharge" <?=$row['type'] == 'delete_users_no_recharge' ? 'selected' : '';?>><?=__('Xóa User không nạp tiền');?></option>
                                            <option value="delete_telegram_log" <?=$row['type'] == 'delete_telegram_log' ? 'selected' : '';?>><?=__('Xóa nhật ký Bot Telegram');?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Thời gian');?> (<span
                                        class="text-danger">*</span>)</label>
                                <div class="col-sm-8">
                                    <div class="input-group mb-3">
                                        <input class="form-control" name="schedule" value="<?=$row['schedule'];?>" id="schedule" onkeyup="loadform()"
                                            placeholder="<?=__('Nhập giây, ví dụ 1 ngày = 86400');?>" required>
                                        <span class="input-group-text">
                                            <?=__('Giây');?>
                                        </span>
                                    </div>
                                    <div class="btn-group" role="group" aria-label="Time buttons">
                                        <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(1)"><?=__('1 ngày');?></button>
                                        <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(3)"><?=__('3 ngày');?></button>
                                        <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(7)"><?=__('7 ngày');?></button>
                                        <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(30)"><?=__('30 ngày');?></button>
                                        <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(60)"><?=__('3 tháng');?></button>
                                    </div>
                                </div>
                            </div>
                            <script>
                            function setTime(days) {
                                const seconds = days * 86400; // 1 ngày = 86400 giây
                                document.getElementById('schedule').value = seconds;
                                loadform(); // Gọi hàm loadform nếu cần cập nhật gì thêm
                            }
                            </script>


                            <p id="mota"><?=__('Vui lòng chọn loại công việc');?></p>

                            <script>
                            function formatTime(seconds) {
                                var days = Math.floor(seconds / (60 * 60 * 24));
                                var hours = Math.floor((seconds % (60 * 60 * 24)) / (60 * 60));
                                var minutes = Math.floor((seconds % (60 * 60)) / 60);
                                var remainingSeconds = seconds % 60;

                                var result = '';
                                if (days > 0) {
                                    result += days + ' <?=__('ngày');?> ';
                                }
                                if (hours > 0) {
                                    result += hours + ' <?=__('giờ');?> ';
                                }
                                if (minutes > 0) {
                                    result += minutes + ' <?=__('phút');?> ';
                                }
                                if (remainingSeconds > 0) {
                                    result += remainingSeconds + ' <?=__('giây');?>';
                                }

                                return result.trim();
                            }

                            function loadform() {
                                var type = $('#type').val();
                                var schedule = $('#schedule').val();
                                var formattedTime = formatTime(schedule);

                                if (type == 'delete_order') {
                                    $('#mota').html(
                                        '<?=__('Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa đơn hàng đã bán cũ hơn');?> <b style="color:red;">' +
                                        formattedTime + '</b>.');
                                } else if (type == 'delete_recharge_history') {
                                    $('#mota').html(
                                        '<?=__('Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa lịch sử nạp tiền cũ hơn');?> <b style="color:red;">' +
                                        formattedTime + '</b>.');
                                } else if (type == 'delete_users_no_recharge') {
                                    $('#mota').html(
                                        '<?=__('Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa User không nạp tiền cũ hơn');?> <b style="color:red;">' +
                                        formattedTime + '</b>.');
                                } else if (type == 'delete_telegram_log') {
                                    $('#mota').html(
                                        '<?=__('Nếu bạn tạo Task này => Hệ thống sẽ thực hiện xóa nhật ký Bot Telegram cũ hơn');?> <b style="color:red;">' +
                                        formattedTime + '</b>.');
                                } else {
                                    $('#mota').html('<?=__('Vui lòng chọn loại công việc');?>');
                                }
                            }
                            // Sự kiện DOMContentLoaded
                            document.addEventListener("DOMContentLoaded", function(event) {
                                // Gọi hàm loadform khi trang đã tải xong
                                loadform();
                            });
                            </script>
                            <a type="button" class="btn btn-danger shadow-danger btn-wave"
                                href="<?=base_url_admin('automations');?>"><i class="fa fa-fw fa-undo me-1"></i>
                                <?=__('Tải lại');?></a>
                            <button type="submit" name="SaveTask" class="btn btn-primary shadow-primary btn-wave"><i
                                    class="fa fa-fw fa-save me-1"></i> <?=__('Lưu');?></button>
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