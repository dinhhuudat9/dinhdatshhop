<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('View Sending Report'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '';
$body['footer'] = '';

require_once(__DIR__.'/../../models/is_admin.php');

// Validate và lấy thông tin chiến dịch
if (!isset($_GET['id'])) {
    redirect(base_url_admin('email-campaigns'));
}

$id = validate_int($_GET['id'], 1);
if ($id === false) {
    redirect(base_url_admin('email-campaigns'));
}

$campaign = $CMSNT->get_row_safe("SELECT * FROM `email_campaigns` WHERE `id` = ?", [$id]);
if (!$campaign) {
    redirect(base_url_admin('email-campaigns'));
}

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');

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
$where_conditions = ["`camp_id` = ?"];
$where_params = [$id];

$status = '';
$username = '';
$email = '';
$shortByDate = '';

// Filter theo status
if(!empty($_GET['status'])){
    $status = validate_int($_GET['status'], 1, 4);
    if($status !== false){
        $statusMap = [1 => 0, 2 => 1, 3 => 2]; // 1=Pending, 2=Success, 3=Failed
        if(isset($statusMap[$status])){
            $where_conditions[] = '`status` = ?';
            $where_params[] = $statusMap[$status];
        }
    }
}

// Filter theo username
if(!empty($_GET['username'])){
    $username = validate_string($_GET['username'], 100);
    if($username !== false){
        $userSearch = $CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `username` = ?", [$username]);
        if($userSearch){
            $where_conditions[] = '`user_id` = ?';
            $where_params[] = $userSearch['id'];
        } else {
            $where_conditions[] = '1 = 0'; // Không tìm thấy user
        }
    }
}

// Filter theo email
if(!empty($_GET['email'])){
    $email = validate_email($_GET['email']);
    if($email !== false){
        $userSearch = $CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `email` = ?", [$email]);
        if($userSearch){
            $where_conditions[] = '`user_id` = ?';
            $where_params[] = $userSearch['id'];
        } else {
            $where_conditions[] = '1 = 0';
        }
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
            $where_conditions[] = 'DATE(`update_gettime`) = ?';
            $where_params[] = $currentDate;
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(`update_gettime`) = ? AND WEEK(`update_gettime`, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(`update_gettime`) = ? AND YEAR(`update_gettime`) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Query với prepared statements
$sql_list = "SELECT es.*, u.username, u.email 
             FROM `email_sending` es
             LEFT JOIN `users` u ON es.user_id = u.id
             WHERE {$where_clause} 
             ORDER BY es.`id` ASC 
             LIMIT ?, ?";
$params_list = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_list);

$sql_count = "SELECT COUNT(*) as total FROM `email_sending` WHERE {$where_clause}";
$countResult = $CMSNT->get_row_safe($sql_count, $where_params);
$totalDatatable = $countResult ? $countResult['total'] : 0;

$urlDatatable = pagination(
    base_url_admin("email-sending-view&id={$id}&limit={$limit}&shortByDate={$shortByDate}&status={$status}&email=".urlencode($email)."&username=".urlencode($username)."&"), 
    $from, 
    $totalDatatable, 
    $limit
);

// Thống kê
$statsPending = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 0", [$id]);
$statsSuccess = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 1", [$id]);
$statsFailed = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 2", [$id]);
$statsTotal = $statsPending + $statsSuccess + $statsFailed;
$progressPercent = $statsTotal > 0 ? round(($statsSuccess / $statsTotal) * 100, 1) : 0;
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0">
                <i class="ri-bar-chart-line me-2"></i><?=__('Báo cáo gửi mail');?>
            </h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?=base_url_admin('email-campaigns');?>"><?=__('Email Campaigns');?></a></li>
                    <li class="breadcrumb-item active"><?=htmlspecialchars($campaign['name']);?></li>
                </ol>
            </nav>
        </div>
        
        <!-- Thông tin chiến dịch -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-1"><?=htmlspecialchars($campaign['name']);?></h5>
                        <p class="text-muted mb-0"><?=htmlspecialchars($campaign['subject']);?></p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-<?=$campaign['status'] == 0 ? 'primary' : ($campaign['status'] == 1 ? 'success' : 'danger');?> fs-12">
                            <?=$campaign['status'] == 0 ? __('Đang chạy') : ($campaign['status'] == 1 ? __('Hoàn thành') : __('Đã hủy'));?>
                        </span>
                        <small class="d-block text-muted mt-1">
                            <?=__('Tạo lúc');?>: <?=$campaign['create_gettime'];?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-1"><?=format_cash($statsTotal);?></h3>
                        <small class="text-muted"><?=__('Tổng người nhận');?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-1 text-success"><?=format_cash($statsSuccess);?></h3>
                        <small class="text-muted"><?=__('Đã gửi');?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-1 text-danger"><?=format_cash($statsFailed);?></h3>
                        <small class="text-muted"><?=__('Thất bại');?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-1 text-warning"><?=format_cash($statsPending);?></h3>
                        <small class="text-muted"><?=__('Đang chờ');?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thanh tiến trình -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span><?=__('Tiến trình gửi');?></span>
                    <span><?=$progressPercent;?>%</span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?=$progressPercent;?>%"></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('CHI TIẾT GỬI EMAIL');?>
                        </div>
                        <a href="<?=base_url_admin('email-campaigns');?>" class="btn btn-sm btn-danger">
                            <i class="ri-arrow-left-line me-1"></i> <?=__('Quay lại');?>
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Form tìm kiếm -->
                        <form action="<?=base_url();?>" method="GET" class="mb-4">
                            <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                            <input type="hidden" name="action" value="email-sending-view">
                            <input type="hidden" name="id" value="<?=$id;?>">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($username);?>" name="username" placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-md-3">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($email);?>" name="email" placeholder="<?=__('Email');?>">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" name="status">
                                        <option value=""><?=__('Trạng thái');?></option>
                                        <option <?=$status == 1 ? 'selected' : '';?> value="1"><?=__('Đang chờ');?></option>
                                        <option <?=$status == 2 ? 'selected' : '';?> value="2"><?=__('Thành công');?></option>
                                        <option <?=$status == 3 ? 'selected' : '';?> value="3"><?=__('Thất bại');?></option>
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
                                        <a class="btn btn-sm btn-outline-danger" href="<?=base_url_admin("email-sending-view&id={$id}");?>"><i class="fa fa-times"></i></a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="me-2"><?=__('Hiển thị');?>:</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block w-auto">
                                        <?php foreach([5,10,20,50,100,500] as $l): ?>
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
                                        <th width="50">#</th>
                                        <th><?=__('Người nhận');?></th>
                                        <th class="text-center" width="120"><?=__('Trạng thái');?></th>
                                        <th width="180"><?=__('Thời gian');?></th>
                                        <th><?=__('Phản hồi');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($listDatatable)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="ri-inbox-line fs-40 d-block mb-2"></i>
                                            <?=__('Không có dữ liệu');?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php $i = $from; foreach ($listDatatable as $row): $i++;?>
                                    <tr>
                                        <td class="text-center"><?=$i;?></td>
                                        <td>
                                            <div>
                                                <strong><i class="ri-user-line me-1"></i><?=htmlspecialchars($row['username'] ?? 'N/A');?></strong>
                                            </div>
                                            <small class="text-muted">
                                                <i class="ri-mail-line me-1"></i><?=htmlspecialchars($row['email'] ?? 'N/A');?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $statusClass = ['warning', 'success', 'danger'];
                                            $statusText = [__('Đang chờ'), __('Thành công'), __('Thất bại')];
                                            $statusIcon = ['ri-time-line', 'ri-check-line', 'ri-close-line'];
                                            $s = min(2, max(0, (int)$row['status']));
                                            ?>
                                            <span class="badge bg-<?=$statusClass[$s];?>">
                                                <i class="<?=$statusIcon[$s];?> me-1"></i><?=$statusText[$s];?>
                                            </span>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="ri-time-line me-1"></i>
                                                <?=$row['update_gettime'] ?: $row['create_gettime'];?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['response'])): ?>
                                            <small class="text-<?=$row['status'] == 1 ? 'success' : 'danger';?>">
                                                <?=htmlspecialchars(mb_substr($row['response'], 0, 100));?>
                                                <?=mb_strlen($row['response']) > 100 ? '...' : '';?>
                                            </small>
                                            <?php else: ?>
                                            <small class="text-muted">-</small>
                                            <?php endif; ?>
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
