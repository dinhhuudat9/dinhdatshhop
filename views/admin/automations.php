<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Automations'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

';
$body['footer'] = '
 
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_automations') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['AddTask'])) {
    // Kiểm tra CSRF token
    checkCSRF();
    
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Chức năng này không thể sử dụng vì đây là trang web demo').'")){window.history.back().location.reload();}</script>');
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

    // Không cần validation thêm cho các task mới

    $isInsert = $CMSNT->insert("automations", [
        'name'              => !empty($_POST['name']) ? check_string($_POST['name']) : NULL,
        'type'              => $type,
        'product_id'        => $product_id,
        'schedule'          => $schedule,
        'other'             => !empty($_POST['other']) ? check_string($_POST['other']) : NULL,
        'create_gettime'    => gettime(),
        'update_gettime'    => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Add Task Automation'
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', 'Add Task Automation', $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);    
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("'.__('Thêm thành công!').'")){location.href = "'.base_url_admin('automations').'";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("'.__('Thêm thất bại!').'")){window.history.back().location.reload();}</script>');
    }
}

if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}
else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$create_gettime = '';
$shortByDate  = '';


if(!empty($_GET['create_gettime'])){
    $create_gettime = check_string($_GET['create_gettime']);
    $createdate = $create_gettime;
    $create_gettime_1 = str_replace('-', '/', $create_gettime);
    $create_gettime_1 = explode(' to ', $create_gettime_1);
    if($create_gettime_1[0] != $create_gettime_1[1]){
        $create_gettime_1 = [$create_gettime_1[0].' 00:00:00', $create_gettime_1[1].' 23:59:59'];
        $where .= " AND `create_gettime` >= '".$create_gettime_1[0]."' AND `create_gettime` <= '".$create_gettime_1[1]."' ";
    }
}
if(isset($_GET['shortByDate'])){
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if($shortByDate == 1){
        $where .= " AND `create_gettime` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}
$listDatatable = $CMSNT->get_list(" SELECT * FROM `automations` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `automations` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("automations&limit=$limit&shortByDate=$shortByDate&create_gettime=$create_gettime&"), $from, $totalDatatable, $limit);


?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="bx bxs-calendar"></i> <?=__('Automations');?></h1>
        </div>
        <?php if(time() - $CMSNT->site('check_time_cron_task') >= 300):?>
        <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
            </svg>
            <?=__('Vui lòng thực hiện');?> <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> <?=__('liên kết');?>: <a class="text-primary" href="<?=base_url('cron/task.php?key='.$CMSNT->site('key_cron_job'));?>"
                target="_blank"><?=base_url('cron/task.php?key='.$CMSNT->site('key_cron_job'));?></a> <?=__('1 - 5 phút 1 lần để sử dụng được chức năng này.');?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <?php endif?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('DANH SÁCH CÔNG VIỆC TỰ ĐỘNG');?>
                        </div>
                        <button type="button" data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                            class="btn btn-sm btn-primary shadow-primary"><i
                                class="ri-add-line fw-semibold align-middle"></i> <?=__('THÊM TASK');?></button>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url(); ?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                                <input type="hidden" name="action" value="automations">
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm"
                                        id="daterange" value="<?=$create_gettime;?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Tìm kiếm');?>
                                    </button>
                                    <a class="btn btn-hero btn-sm btn-danger"
                                        href="<?=base_url_admin('automations');?>"><i class="fa fa-trash"></i>
                                        <?=__('Xóa bộ lọc');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label"><?=__('Hiển thị');?> :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                        <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Sắp xếp theo ngày');?> :</label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?>
                                        </option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3">
                                            <?=__('Tháng này');?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive table-wrapper mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input" name="check_all"
                                                    id="check_all_checkbox" value="option1">
                                            </div>
                                        </th>
                                        <th class="text-center"><?=__('Tên công việc');?></th>
                                        <th class="text-center"><?=__('Loại công việc');?></th>
                                        <th class="text-center"><?=__('Chi tiết công việc');?></th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check form-check-md d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input checkbox"
                                                    data-id="<?=$row['id'];?>" name="checkbox"
                                                    value="<?=$row['id'];?>" />
                                            </div>
                                        </td>
                                        <td class="text-center"><?=$row['name'];?></td>
                                        <td class="text-center">
                                            <?php if($row['type'] == 'delete_order'){
                                                echo '<span style="font-size: 13px;" class="badge bg-danger">'.__('Xóa đơn hàng đã bán').'</span>';
                                            }elseif($row['type'] == 'delete_recharge_history'){
                                                echo '<span style="font-size: 13px;" class="badge bg-warning">'.__('Xóa lịch sử nạp tiền').'</span>';
                                            }elseif($row['type'] == 'delete_users_no_recharge'){
                                                echo '<span style="font-size: 13px;" class="badge bg-primary">'.__('Xóa User không nạp tiền').'</span>';
                                            }elseif($row['type'] == 'delete_telegram_log'){
                                                echo '<span style="font-size: 13px;" class="badge bg-success">'.__('Xóa nhật ký Bot Telegram').'</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($row['type'] == 'delete_order'):?>
                                            <?php echo sprintf(__('Hệ thống sẽ thực hiện xóa đơn hàng đã bán sau %s.'), '<b style="color:red;">'.timeAgo2($row['schedule']).'</b>');?>
                                            <?php elseif($row['type'] == 'delete_recharge_history'):?>
                                            <?php echo sprintf(__('Hệ thống sẽ thực hiện xóa lịch sử nạp tiền sau %s.'), '<b style="color:red;">'.timeAgo2($row['schedule']).'</b>');?>
                                            <?php elseif($row['type'] == 'delete_users_no_recharge'):?>
                                            <?php echo sprintf(__('Hệ thống sẽ thực hiện xóa User không nạp tiền sau %s.'), '<b style="color:red;">'.timeAgo2($row['schedule']).'</b>');?>
                                            <?php elseif($row['type'] == 'delete_telegram_log'):?>
                                            <?php echo sprintf(__('Hệ thống sẽ thực hiện xóa nhật ký Bot Telegram cũ hơn %s.'), '<b style="color:red;">'.timeAgo2($row['schedule']).'</b>');?>
                                            <?php endif?>

                                        </td>
                                        <td class="text-center">
                                            <a type="button"
                                                href="<?=base_url_admin('automation-edit&id='.$row['id']);?>"
                                                class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                                                title="<?=__('Edit');?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a type="button" onclick="remove('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="<?=__('Delete');?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <td colspan="8">
                                        <div class="btn-list">
                                            <button type="button" id="btn_delete_row"
                                                class="btn btn-outline-danger shadow-danger btn-wave btn-sm"><i
                                                    class="fa-solid fa-trash"></i> <?=__('XÓA TASK ĐÃ CHỌN');?></button>
                                        </div>
                                    </td>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?=__('Showing');?> <?=$limit;?> <?=__('of');?> <?=format_cash($totalDatatable);?>
                                    <?=__('Results');?></p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
                        <p><?=__('Chức năng Automation giúp tự động hóa các công việc định kỳ của hệ thống');?>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
    data-bs-keyboard="false" aria-hidden="true">
    <!-- Scrollable modal -->
    <div class="modal-dialog modal-dialog-centered modal-lg dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel2"><i class="fa-solid fa-plus"></i> <?=__('THÊM CÔNG VIỆC CẦN TỰ ĐỘNG');?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <div class="modal-body" onchange="loadform()">
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Tên công việc');?></label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <textarea class="form-control" name="name"
                                    placeholder="<?=__('Nhập tên mô tả task nếu có');?>"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Loại công việc');?> (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <select class="form-control" name="type" id="type" required>
                                    <option value=""> -- <?=__('Chọn loại công việc');?> --</option>
                                    <option value="delete_order"><?=__('Xóa đơn hàng đã bán');?></option>
                                    <option value="delete_recharge_history"><?=__('Xóa lịch sử nạp tiền');?></option>
                                    <option value="delete_users_no_recharge"><?=__('Xóa User không nạp tiền');?></option>
                                    <option value="delete_telegram_log"><?=__('Xóa nhật ký Bot Telegram');?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-sm-4 col-form-label" for="example-hf-email"><?=__('Thời gian');?> (<span
                                class="text-danger">*</span>)</label>
                        <div class="col-sm-8">
                            <div class="input-group mb-3">
                                <input class="form-control" name="schedule" id="schedule" onkeyup="loadform()"
                                    value="604800" placeholder="<?=__('Nhập giây, ví dụ 1 ngày = 86400');?>" required>
                                <span class="input-group-text">
                                    <?=__('Giây');?>
                                </span>
                            </div>
                            <div class="btn-group" role="group" aria-label="Time buttons">
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(1)"><?=__('1 ngày');?></button>
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(3)"><?=__('3 ngày');?></button>
                                <button type="button" class="btn btn-outline-primary btn-wave btn-sm" onclick="setTime(7)" active><?=__('7 ngày');?></button>
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
                    </script>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light " data-bs-dismiss="modal"><?=__('Close');?></button>
                    <button type="submit" name="AddTask" class="btn btn-primary shadow-primary btn-wave"><i
                            class="fa fa-fw fa-plus me-1"></i>
                        <?=__('Submit');?></button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php
require_once(__DIR__.'/footer.php');
?>


<script>
$(function() {
    $('#check_all_checkbox').on('click', function() {
        $('.checkbox').prop('checked', this.checked);
    });
    $('.checkbox').on('click', function() {
        $('#check_all_checkbox').prop('checked', $('.checkbox:checked')
            .length === $('.checkbox').length);
    });
});
</script>


<script>
$("#btn_delete_row").click(function() {
    var checkboxes = document.querySelectorAll('input[name="checkbox"]:checked');
    if (checkboxes.length === 0) {
        return showMessage('<?=__('Lỗi: Vui lòng chọn ít nhất một dữ liệu.');?>', 'error');
    }
    Swal.fire({
        title: "<?=__('Bạn có chắc không?');?>",
        text: "<?=__('Hệ thống sẽ xóa');?> " + checkboxes.length +
            " <?=__('Task bạn đã chọn khi nhấn Đồng Ý');?>",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Đóng');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            delete_records();
        }
    });
});

function delete_records() {
    var checkbox = document.getElementsByName('checkbox');

    function postUpdatesSequentially(index) {
        if (index < checkbox.length) {
            if (checkbox[index].checked === true) {
                postRemove(checkbox[index].value);
            }
            setTimeout(function() {
                postUpdatesSequentially(index + 1);
            }, 100);
        } else {
            Swal.fire({
                title: "<?=__('Thành công!');?>",
                text: "<?=__('Xóa dữ liệu thành công');?>",
                icon: "success"
            });
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    }
    postUpdatesSequentially(0);
}
</script>

<script>
function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeTaskAutomation',
            id: id
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, result.status);
            } else {
                showMessage(result.msg, result.status);
            }
        }
    });
}

function remove(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác nhận xóa Task');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa Task này không ?');?>",
        confirmText: "<?=__('Đồng ý');?>",
        cancelText: "<?=__('Không');?>"
    }).then((e) => {
        if (e) {
            postRemove(id);
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    })
}
</script>