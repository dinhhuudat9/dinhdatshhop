<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Email Campaigns').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';

require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');

if(checkPermission($getUser['admin'], 'view_email_campaigns') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}

// Xử lý phân trang và filter
$limit = isset($_GET['limit']) ? validate_int($_GET['limit'], 5, 1000) : 10;
$limit = $limit ?: 10;

$page = isset($_GET['page']) ? validate_int($_GET['page'], 1, 99999) : 1;
$page = $page ?: 1;

$from = ($page - 1) * $limit;

// Build WHERE clause với prepared statements
$where_conditions = ["`id` > 0"];
$where_params = [];

$status = '';
$subject = '';
$name = '';
$shortByDate = '';

// Filter theo status
if(!empty($_GET['status'])){
    $status = validate_int($_GET['status'], 1, 4);
    if($status !== false){
        $statusMap = [1 => 0, 2 => 1, 3 => 2]; // 1=Processing, 2=Completed, 3=Cancelled
        if(isset($statusMap[$status])){
            $where_conditions[] = '`status` = ?';
            $where_params[] = $statusMap[$status];
        }
    }
}

// Filter theo subject
if(!empty($_GET['subject'])){
    $subject = validate_string($_GET['subject'], 200);
    if($subject !== false){
        $where_conditions[] = '`subject` LIKE ?';
        $where_params[] = '%'.$subject.'%';
    }
}

// Filter theo name
if(!empty($_GET['name'])){
    $name = validate_string($_GET['name'], 200);
    if($name !== false){
        $where_conditions[] = '`name` LIKE ?';
        $where_params[] = '%'.$name.'%';
    }
}

// Filter theo thời gian
if(isset($_GET['shortByDate']) && $_GET['shortByDate'] !== ''){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false){
        $currentDate = date("Y-m-d");
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        if($shortByDate == 1){
            $where_conditions[] = 'DATE(`create_gettime`) = ?';
            $where_params[] = $currentDate;
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(`create_gettime`) = ? AND WEEK(`create_gettime`, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(`create_gettime`) = ? AND YEAR(`create_gettime`) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Query với prepared statements
$sql_list = "SELECT * FROM `email_campaigns` WHERE {$where_clause} ORDER BY `id` DESC LIMIT ?, ?";
$params_list = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_list);

$sql_count = "SELECT COUNT(*) as total FROM `email_campaigns` WHERE {$where_clause}";
$countResult = $CMSNT->get_row_safe($sql_count, $where_params);
$totalDatatable = $countResult ? $countResult['total'] : 0;

$urlDatatable = pagination(
    base_url_admin("email-campaigns&limit={$limit}&shortByDate={$shortByDate}&subject=".urlencode($subject)."&name=".urlencode($name)."&status={$status}&"), 
    $from, 
    $totalDatatable, 
    $limit
);

// Thống kê tổng quan
$statsTotal = $CMSNT->num_rows_safe("SELECT id FROM `email_campaigns`", []);
$statsProcessing = $CMSNT->num_rows_safe("SELECT id FROM `email_campaigns` WHERE `status` = 0", []);
$statsCompleted = $CMSNT->num_rows_safe("SELECT id FROM `email_campaigns` WHERE `status` = 1", []);
$statsCancelled = $CMSNT->num_rows_safe("SELECT id FROM `email_campaigns` WHERE `status` = 2", []);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-envelope"></i> <?=__('Email Campaigns');?></h1>
        </div>
        
        <?php if(time() - $CMSNT->site('check_time_cron_sending_email') >= 120):?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ri-error-warning-line me-2"></i>
            <?=__('Cron Job chưa hoạt động.');?> 
            <a href="<?=base_url_admin('settings&tab=cron-jobs');?>" class="alert-link"><?=__('Xem hướng dẫn');?></a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif?>
        
        <?php if($CMSNT->site('smtp_status') != 1):?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>
            <?=__('SMTP chưa được kích hoạt.');?> 
            <a href="<?=base_url_admin('settings&tab=smtp');?>" class="alert-link"><?=__('Cấu hình ngay');?></a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif?>
        
        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-primary-transparent me-3">
                            <i class="ri-mail-line fs-20"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?=format_cash($statsTotal);?></h5>
                            <small class="text-muted"><?=__('Tổng chiến dịch');?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-info-transparent me-3">
                            <i class="ri-loader-4-line fs-20"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?=format_cash($statsProcessing);?></h5>
                            <small class="text-muted"><?=__('Đang chạy');?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-success-transparent me-3">
                            <i class="ri-check-double-line fs-20"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?=format_cash($statsCompleted);?></h5>
                            <small class="text-muted"><?=__('Hoàn thành');?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div class="avatar avatar-lg bg-danger-transparent me-3">
                            <i class="ri-close-circle-line fs-20"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?=format_cash($statsCancelled);?></h5>
                            <small class="text-muted"><?=__('Đã hủy');?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('DANH SÁCH CHIẾN DỊCH EMAIL MARKETING');?>
                        </div>
                        <div class="d-flex">
                            <a href="<?=base_url_admin('email-campaign-add');?>" class="btn btn-sm btn-primary">
                                <i class="ri-add-line me-1"></i> <?=__('Tạo chiến dịch mới');?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Form tìm kiếm -->
                        <form action="<?=base_url();?>" method="GET" class="mb-4">
                            <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                            <input type="hidden" name="action" value="email-campaigns">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($name);?>" name="name" placeholder="<?=__('Tên chiến dịch');?>">
                                </div>
                                <div class="col-md-3">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($subject);?>" name="subject" placeholder="<?=__('Tiêu đề mail');?>">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" name="status">
                                        <option value=""><?=__('Trạng thái');?></option>
                                        <option <?=$status == 1 ? 'selected' : '';?> value="1"><?=__('Đang chạy');?></option>
                                        <option <?=$status == 2 ? 'selected' : '';?> value="2"><?=__('Hoàn thành');?></option>
                                        <option <?=$status == 3 ? 'selected' : '';?> value="3"><?=__('Đã hủy');?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" name="shortByDate">
                                        <option value=""><?=__('Thời gian');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?></option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?></option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i></button>
                                        <a class="btn btn-sm btn-outline-danger" href="<?=base_url_admin('email-campaigns');?>"><i class="fa fa-times"></i></a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="me-2"><?=__('Hiển thị');?>:</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto">
                                        <?php foreach([5,10,20,50,100] as $l): ?>
                                        <option <?=$limit == $l ? 'selected' : '';?> value="<?=$l;?>"><?=$l;?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small class="text-muted"><?=__('Tổng');?>: <?=format_cash($totalDatatable);?> <?=__('kết quả');?></small>
                            </div>
                        </form>
                        
                        <!-- Bảng danh sách -->
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th><?=__('Tên chiến dịch');?></th>
                                        <th><?=__('Tiêu đề');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th class="text-center"><?=__('Tiến trình');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                        <th class="text-center"><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($listDatatable)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="ri-inbox-line fs-40 d-block mb-2"></i>
                                            <?=__('Chưa có chiến dịch nào');?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($listDatatable as $row): 
                                        // Lấy thống kê cho mỗi chiến dịch
                                        $total_success = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 1", [$row['id']]);
                                        $total = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ?", [$row['id']]);
                                        $phantram = $total > 0 ? round(($total_success / $total) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?=htmlspecialchars($row['name']);?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?=htmlspecialchars(mb_substr($row['subject'], 0, 50));?><?=mb_strlen($row['subject']) > 50 ? '...' : '';?></small>
                                        </td>
                                        <td class="text-center">
                                            <?=display_camp($row['status']);?>
                                        </td>
                                        <td>
                                            <div class="progress progress-sm mb-1" style="height: 8px;">
                                                <div class="progress-bar bg-<?=$phantram >= 100 ? 'success' : ($phantram > 0 ? 'info' : 'secondary');?>" 
                                                     style="width: <?=$phantram;?>%"></div>
                                            </div>
                                            <small class="text-muted">
                                                <?=format_cash($total_success);?>/<?=format_cash($total);?> (<?=$phantram;?>%)
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <small><?=$row['create_gettime'];?></small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="<?=base_url_admin('email-sending-view&id='.$row['id']);?>" class="btn btn-sm btn-outline-info" title="<?=__('Xem báo cáo');?>">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                <a href="<?=base_url_admin('email-campaign-edit&id='.$row['id']);?>" class="btn btn-sm btn-outline-primary" title="<?=__('Chỉnh sửa');?>">
                                                    <i class="ri-edit-line"></i>
                                                </a>
                                                <?php if($row['status'] == 0): ?>
                                                <button onclick="CancelRow(<?=$row['id'];?>)" class="btn btn-sm btn-outline-warning" title="<?=__('Hủy');?>">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button onclick="RemoveRow(<?=$row['id'];?>)" class="btn btn-sm btn-outline-danger" title="<?=__('Xóa');?>">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Phân trang -->
                        <?php if($totalDatatable > $limit): ?>
                        <div class="d-flex justify-content-end mt-3">
                            <?=$urlDatatable;?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__.'/footer.php'); ?>

<script type="text/javascript">
function CancelRow(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác nhận hủy chiến dịch');?>",
        message: "<?=__('Bạn có chắc chắn muốn hủy chiến dịch này không?');?>",
        confirmText: "<?=__('Xác nhận');?>",
        cancelText: "<?=__('Hủy bỏ');?>"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=base_url('ajaxs/admin/update.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'cancel_email_campaigns',
                    id: id,
                    csrf_token: '<?=generate_csrf_token();?>'
                },
                success: function(result) {
                    showMessage(result.msg, result.status);
                    if (result.status == 'success') {
                        setTimeout(() => location.reload(), 1000);
                    }
                }
            });
        }
    });
}

function RemoveRow(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Xác nhận xóa chiến dịch');?>",
        message: "<?=__('Chiến dịch và toàn bộ lịch sử gửi sẽ bị xóa. Tiếp tục?');?>",
        confirmText: "<?=__('Xóa');?>",
        cancelText: "<?=__('Hủy');?>"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=base_url('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'email_campaigns',
                    id: id,
                    csrf_token: '<?=generate_csrf_token();?>'
                },
                success: function(result) {
                    showMessage(result.msg, result.status);
                    if (result.status == 'success') {
                        setTimeout(() => location.reload(), 1000);
                    }
                }
            });
        }
    });
}
</script>
