<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý rút tiền Affiliate'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '';

require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
require_once(__DIR__.'/../../models/is_license.php');

if(checkPermission($getUser['admin'], 'view_withdraw_affiliate') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back().location.reload();}</script>');
}

// Pagination
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Filters
$where_conditions = ["`id` > 0"];
$where_params = [];

$shortByDate = '';
$user_id = '';
$reason = '';
$create_gettime = '';
$username = '';
$status = '';
$stk = '';
$trans_id = '';

// Filter by trans_id
if(!empty($_GET['trans_id'])){
    $trans_id = validate_string($_GET['trans_id'], 50);
    if($trans_id !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $trans_id;
    }
}

// Filter by account number
if(!empty($_GET['stk'])){
    $stk = validate_string($_GET['stk'], 50);
    if($stk !== false) {
        $where_conditions[] = '`stk` = ?';
        $where_params[] = $stk;
    }
}

// Filter by status
if(!empty($_GET['status'])){
    $status = validate_string($_GET['status'], 20);
    if($status !== false && in_array($status, ['pending', 'cancel', 'completed'])) {
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status;
    }
}

// Filter by username
if (!empty($_GET['username'])) {
    $username = validate_string($_GET['username'], 100);
    if($username !== false) {
        $idUser = $CMSNT->get_row_safe("SELECT id FROM `users` WHERE `username` = ?", [$username]);
        if($idUser){
            $where_conditions[] = '`user_id` = ?';
            $where_params[] = $idUser['id'];
        } else {
            $where_conditions[] = '`user_id` = ?';
            $where_params[] = 0;
        }
    }
}

// Filter by user_id
if(!empty($_GET['user_id'])){
    $user_id = validate_int($_GET['user_id'], 1);
    if($user_id !== false) {
        $where_conditions[] = '`user_id` = ?';
        $where_params[] = $user_id;
    }
}

// Filter by reason
if(!empty($_GET['reason'])){
    $reason = validate_string($_GET['reason'], 255);
    if($reason !== false) {
        $where_conditions[] = '`reason` LIKE ?';
        $where_params[] = '%'.$reason.'%';
    }
}

// Filter by date range
if(!empty($_GET['create_gettime'])){
    $create_gettime = validate_string($_GET['create_gettime'], 50);
    if($create_gettime !== false) {
        $date_parts = str_replace('-', '/', $create_gettime);
        $date_parts = explode(' to ', $date_parts);
        if(count($date_parts) == 2 && $date_parts[0] != $date_parts[1]){
            $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
            $where_params[] = $date_parts[0].' 00:00:00';
            $where_params[] = $date_parts[1].' 23:59:59';
        }
    }
}

// Filter by shortByDate
if(isset($_GET['shortByDate']) && $_GET['shortByDate'] !== ''){
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if($shortByDate !== false) {
        $currentDate = date("Y-m-d");
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        if($shortByDate == 1){
            $where_conditions[] = '`create_gettime` LIKE ?';
            $where_params[] = '%'.$currentDate.'%';
        }
        if($shortByDate == 2){
            $where_conditions[] = 'YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if($shortByDate == 3){
            $where_conditions[] = 'MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// Build query
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT * FROM `aff_withdraw` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `aff_withdraw` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination(base_url_admin("affiliate-withdraw&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&reason=$reason&create_gettime=$create_gettime&username=$username&stk=$stk&status=$status&trans_id=$trans_id&"), $from, $totalDatatable, $limit);

// Statistics
$currentDate = date("Y-m-d");
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');

$totalWithdrawn = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(amount), 0) as total FROM `aff_withdraw` WHERE `status` = 'completed'"
)['total'] ?? 0;

$monthWithdrawn = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(amount), 0) as total FROM `aff_withdraw` WHERE `status` = 'completed' AND MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?",
    [$currentMonth, $currentYear]
)['total'] ?? 0;

$weekWithdrawn = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(amount), 0) as total FROM `aff_withdraw` WHERE `status` = 'completed' AND YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?",
    [$currentYear, $currentWeek]
)['total'] ?? 0;

$todayWithdrawn = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(amount), 0) as total FROM `aff_withdraw` WHERE `status` = 'completed' AND `create_gettime` LIKE ?",
    ['%'.$currentDate.'%']
)['total'] ?? 0;

$pendingCount = $CMSNT->get_row_safe(
    "SELECT COUNT(*) as total FROM `aff_withdraw` WHERE `status` = 'pending'"
)['total'] ?? 0;

$pendingAmount = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(amount), 0) as total FROM `aff_withdraw` WHERE `status` = 'pending'"
)['total'] ?? 0;
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?=__('Quản lý rút tiền Affiliate');?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?=__('Affiliate Program');?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=__('Rút tiền');?></li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($totalWithdrawn);?></p>
                                <p class="mb-0 text-muted small"><?=__('Tổng đã rút');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-primary rounded-circle fs-20"><i class='bx bxs-wallet-alt'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($monthWithdrawn);?></p>
                                <p class="mb-0 text-muted small"><?=__('Tháng');?> <?=date('m');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-info rounded-circle fs-20"><i class='bx bxs-calendar'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($weekWithdrawn);?></p>
                                <p class="mb-0 text-muted small"><?=__('Tuần này');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-success rounded-circle fs-20"><i class='bx bxs-calendar-week'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-default"><?=format_currency($todayWithdrawn);?></p>
                                <p class="mb-0 text-muted small"><?=__('Hôm nay');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-secondary rounded-circle fs-20"><i class='bx bxs-time'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-warning"><?=$pendingCount;?> <?=__('đơn');?></p>
                                <p class="mb-0 text-muted small"><?=__('Đang chờ');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-warning rounded-circle fs-20"><i class='bx bxs-hourglass'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <p class="mb-1 fs-5 fw-semibold text-danger"><?=format_currency($pendingAmount);?></p>
                                <p class="mb-0 text-muted small"><?=__('Tiền chờ xử lý');?></p>
                            </div>
                            <div class="ms-2">
                                <span class="avatar text-bg-danger rounded-circle fs-20"><i class='bx bxs-error'></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Table -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <i class="ti ti-list me-2"></i><?=__('DANH SÁCH YÊU CẦU RÚT TIỀN');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form action="<?=base_url();?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                                <input type="hidden" name="action" value="affiliate-withdraw">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($user_id);?>" name="user_id" placeholder="<?=__('ID User');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($username);?>" name="username" placeholder="<?=__('Username');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($trans_id);?>" name="trans_id" placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?=htmlspecialchars($stk);?>" name="stk" placeholder="<?=__('Số tài khoản');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control form-control-sm" name="status">
                                        <option value=""><?=__('Trạng thái');?></option>
                                        <option <?=$status == 'pending' ? 'selected' : '';?> value="pending"><?=__('Đang chờ');?></option>
                                        <option <?=$status == 'cancel' ? 'selected' : '';?> value="cancel"><?=__('Đã hủy');?></option>
                                        <option <?=$status == 'completed' ? 'selected' : '';?> value="completed"><?=__('Hoàn thành');?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm" id="daterange" value="<?=htmlspecialchars($create_gettime);?>" placeholder="<?=__('Chọn thời gian');?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i> <?=__('Tìm kiếm');?></button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?=base_url_admin('affiliate-withdraw');?>"><i class="fa fa-trash"></i> <?=__('Xóa bộ lọc');?></a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label"><?=__('Hiển thị');?> :</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                        <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Sắp xếp theo ngày');?> :</label>
                                    <select name="shortByDate" onchange="this.form.submit()" class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?></option>
                                        <option <?=$shortByDate == 2 ? 'selected' : '';?> value="2"><?=__('Tuần này');?></option>
                                        <option <?=$shortByDate == 3 ? 'selected' : '';?> value="3"><?=__('Tháng này');?></option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Data Table -->
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead class="table">
                                    <tr>
                                        <th scope="col" style="width: 50px;"></th>
                                        <th><?=__('Mã giao dịch');?></th>
                                        <th><?=__('Thành viên');?></th>
                                        <th><?=__('Số tiền rút');?></th>
                                        <th><?=__('Tài khoản nhận tiền');?></th>
                                        <th><?=__('Trạng thái');?></th>
                                        <th><?=__('Thời gian');?></th>
                                        <th><?=__('Lý do');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($listDatatable) > 0): ?>
                                    <?php foreach ($listDatatable as $row): ?>
                                    <tr class="<?=$row['status'] == 'pending' ? 'table-warning' : '';?>">
                                        <td>
                                            <button type="button" onclick="modalEdit(`<?=$getUser['token'];?>`, `<?=$row['id'];?>`)" class="btn btn-icon btn-sm btn-light" data-bs-toggle="tooltip" title="<?=__('Xử lý');?>">
                                                <i class="fa fa-fw fa-edit"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark">#<?=$row['trans_id'];?></span>
                                        </td>
                                        <td>
                                            <a class="text-primary fw-semibold" href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>">
                                                <?=getRowRealtime("users", $row['user_id'], "username");?>
                                            </a>
                                            <small class="text-muted d-block">[ID: <?=$row['user_id'];?>]</small>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-primary-gradient fs-14"><?=format_currency($row['amount']);?></span>
                                        </td>
                                        <td>
                                            <strong><?=$row['bank'];?></strong><br>
                                            <span class="text-muted"><?=$row['stk'];?></span><br>
                                            <small><?=$row['name'];?></small>
                                        </td>
                                        <td class="text-center"><?=display_withdraw($row['status']);?></td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?=$row['create_gettime'];?></span>
                                            <?php if(!empty($row['update_gettime']) && $row['update_gettime'] != $row['create_gettime']): ?>
                                            <br><small class="text-muted"><?=__('Cập nhật');?>: <?=$row['update_gettime'];?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['reason'])): ?>
                                            <span class="text-danger"><?=$row['reason'];?></span>
                                            <?php else: ?>
                                            <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="ti ti-inbox fs-1 text-muted d-block mb-2"></i>
                                            <?=__('Không có dữ liệu');?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?=__('Hiển thị');?> <?=$limit;?> <?=__('trên tổng');?> <?=format_cash($totalDatatable);?> <?=__('kết quả');?></p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?=$totalDatatable > $limit ? $urlDatatable : '';?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>

<!-- Modal -->
<div class="modal fade" id="ModalDialog" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl dialog-scrollable">
        <div class="modal-content">
            <div id="modalEdit"></div>
        </div>
    </div>
</div>

<script>
function modalEdit(token, id) {
    $("#modalEdit").html('<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    $.get("<?=BASE_URL('ajaxs/admin/modal/withdraw-edit.php?id=');?>" + id + '&token=' + token, function(data) {
        $("#modalEdit").html(data);
    });
    $('#ModalDialog').modal('show');
}

function postRemove(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeWithdraw',
            id: id
        },
        success: function(result) {
            showMessage(result.msg, result.status);
        }
    });
}

function RemoveRow(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Cảnh báo');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa item ID');?> " + id + " <?=__('không');?>?",
        confirmText: "<?=__('Đồng ý');?>",
        cancelText: "<?=__('Huỷ');?>"
    }).then((e) => {
        if (e) {
            postRemove(id);
            location.reload();
        }
    });
}
</script>
