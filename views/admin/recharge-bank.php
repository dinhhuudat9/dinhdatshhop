<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Recharge Bank'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<style>
/* Làm to checkbox lên */
.invoice-checkbox, #selectAll {
    width: 20px !important;
    height: 20px !important;
    cursor: pointer;
}
</style>
';
$body['footer'] = '


';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/nav.php');
if(checkPermission($getUser['admin'], 'view_recharge_bank_invoice') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back();}</script>');
}

if(isset($_GET['limit'])){
    $limit = intval(check_string($_GET['limit']));
}else{
    $limit = 10;
}
if(isset($_GET['page'])){
    $page = check_string(intval($_GET['page']));
}else{
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$shortByDate = '';
$trans_id = '';
$created_at = '';
$user_id = '';
$username = '';
$method = '';


if(!empty($_GET['method'])){
    $method = check_string($_GET['method']);
    $where .= ' AND `method` LIKE "%'.$method.'%" ';
}
if (!empty($_GET['username'])) {
    $username = check_string($_GET['username']);
    if($idUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `username` = '$username' ")){
        $where .= ' AND `user_id` =  "'.$idUser['id'].'" ';
    }else{
        $where .= ' AND `user_id` =  "" ';
    }
}
if(!empty($_GET['user_id'])){
    $user_id = check_string($_GET['user_id']);
    $where .= ' AND `user_id` = '.$user_id.' ';
}
if(!empty($_GET['trans_id'])){
    $trans_id = check_string($_GET['trans_id']);
    $where .= ' AND `trans_id` = "'.$trans_id.'" ';
}

if(!empty($_GET['created_at'])){
    $created_at = check_string($_GET['created_at']);
    $create_date_1 = str_replace('-', '/', $created_at);
    $create_date_1 = explode(' to ', $create_date_1);
    if($create_date_1[0] != $create_date_1[1]){
        $create_date_1 = [$create_date_1[0].' 00:00:00', $create_date_1[1].' 23:59:59'];
        $where .= " AND `created_at` >= '".$create_date_1[0]."' AND `created_at` <= '".$create_date_1[1]."' ";
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
        $where .= " AND `created_at` LIKE '%".$currentDate."%' ";
    }
    if($shortByDate == 2){
        $where .= " AND YEAR(created_at) = $currentYear AND WEEK(created_at, 1) = $currentWeek ";
    }
    if($shortByDate == 3){
        $where .= " AND MONTH(created_at) = '$currentMonth' AND YEAR(created_at) = '$currentYear' ";
    }
    if($shortByDate == 4){
        $where .= " AND DATE(created_at) = '$yesterday' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `payment_bank_invoice` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `payment_bank_invoice` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("recharge-bank&limit=$limit&shortByDate=$shortByDate&created_at=$created_at&trans_id=$trans_id&method=$method&"), $from, $totalDatatable, $limit);


 
$yesterday = date('Y-m-d', strtotime("-1 day")); // hôm qua
$currentWeek = date("W");
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");


$total_yesterday = intval($CMSNT->get_row("SELECT SUM(amount) FROM payment_bank_invoice WHERE  `created_at` LIKE '%".$yesterday."%' AND `status` = 'completed' ")['SUM(amount)']);
$total_today = $CMSNT->get_row("SELECT SUM(amount) FROM payment_bank_invoice WHERE  `created_at` LIKE '%".$currentDate."%' AND `status` = 'completed' ")['SUM(amount)'];
$total_all_time = $CMSNT->get_row("SELECT SUM(amount) FROM payment_bank_invoice WHERE `status` = 'completed' ")['SUM(amount)'];

?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?=__('Ngân hàng');?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?=__('Nạp tiền');?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=__('Ngân hàng');?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if(time() - $CMSNT->site('check_time_cron_bank') >= 120):?>
        <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
            <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path
                    d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
            </svg>
            <?=__('Vui lòng thực hiện');?> <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> <?=__('liên kết');?>: <a class="text-primary" href="<?=base_url('cron/bank.php?key='.$CMSNT->site('key_cron_job'));?>"
                target="_blank"><?=base_url('cron/bank.php?key='.$CMSNT->site('key_cron_job'));?></a> <?=__('1 phút 1 lần hoặc nhanh hơn để hệ thống xử lý nạp tiền tự động.');?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                    class="bi bi-x"></i></button>
        </div>
        <?php endif?>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <a class="btn btn-primary label-btn mb-3" href="<?=base_url_admin('recharge-bank-config');?>">
                        <i class="ri-settings-4-line label-btn-icon me-2"></i> <?=__('CẤU HÌNH');?>
                    </a>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="row">
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($total_all_time);?></p>
                                        <p class="mb-0 text-muted"><?=__('Toàn thời gian');?></p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-danger rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM payment_bank_invoice WHERE  MONTH(created_at) = '$currentMonth' AND YEAR(created_at) = '$currentYear' AND `status` = 'completed' ")['SUM(amount)']);?>
                                        </p>
                                        <p class="mb-0 text-muted"><?=__('Tháng').' '.date('m');?></p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-info rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($CMSNT->get_row("SELECT SUM(amount) FROM payment_bank_invoice WHERE  YEAR(created_at) = $currentYear AND WEEK(created_at, 1) = $currentWeek AND `status` = 'completed' ")['SUM(amount)']);?>
                                        </p>
                                        <p class="mb-0 text-muted"><?=__('Trong tuần');?></p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-warning rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="card custom-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-fill">
                                        <p class="mb-1 fs-5 fw-semibold text-default">
                                            <?=format_currency($total_today);?></p>
                                        <p class="mb-0 text-muted"><?=__('Hôm nay');?>
                                            <?php 
if ($total_yesterday != 0) {
    $revenueGrowth = ($total_today - $total_yesterday) / $total_yesterday * 100;
    if ($revenueGrowth > 0) {
        // tăng
      echo '<span class="fs-12 text-success ms-2"><i class="ti ti-trending-up me-1 d-inline-block"></i>' . round($revenueGrowth, 2) . '% </span>';
    } else if ($revenueGrowth < 0) {
        // giảm
      echo '<span class="fs-12 text-danger ms-2"><i class="ti ti-trending-down me-1 d-inline-block"></i>' . round(abs($revenueGrowth), 2) . '% </span>';
    }
  }
                            ?>

                                        </p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="avatar text-bg-primary rounded-circle fs-20"><i
                                                class='bx bxs-wallet-alt'></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title"><?=__('THỐNG KÊ NẠP TIỀN THÁNG').' '.date('m');?></div>
                    </div>
                    <div class="card-body">
                        <canvas id="chartjs-line" class="chartjs-chart"></canvas>
                        <script>
                        (function() {
                            /* line chart  */
                            Chart.defaults.borderColor = "rgba(142, 156, 173,0.1)", Chart.defaults.color =
                                "#8c9097";
                            const labels = [
                                <?php
              $month = date('m');
              $year = date('Y');
              $numOfDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

              for ($day = 1; $day <= $numOfDays; $day++) {
                  echo "\"$day/$month/$year\",";
              }
              ?>
                            ];
                            const data = {
                                labels: labels,
                                datasets: [{
                                    label: '<?=__('Nạp tiền tự động');?>',
                                    backgroundColor: 'rgb(132, 90, 223)',
                                    borderColor: 'rgb(132, 90, 223)',
                                    data: [
                                        <?php
                  $data = [];
                  for ($day = 1; $day <= $numOfDays; $day++) {
                      $date = "$year-$month-$day";
                      $row = $CMSNT->get_row("SELECT SUM(amount) FROM payment_bank_invoice WHERE DATE(created_at) = '$date' AND `status` = 'completed' ");
                      $data[$day - 1] = $row['SUM(amount)'];
                  }
                  for ($i = 0; $i < $numOfDays; $i++) {
                      echo "$data[$i],";
                  }
                  ?>
                                    ],
                                }]
                            };
                            const config = {
                                type: 'bar',
                                data: data,
                                options: {}
                            };
                            const myChart = new Chart(
                                document.getElementById('chartjs-line'),
                                config
                            );



                        })();
                        </script>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?=__('LỊCH SỬ HÓA ĐƠN NẠP TIỀN');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url(); ?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                                <input type="hidden" name="action" value="recharge-bank">
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$user_id;?>" name="user_id"
                                        placeholder="<?=__('Tìm ID thành viên');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$username;?>" name="username"
                                        placeholder="<?=__('Tìm Username');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$trans_id;?>" name="trans_id"
                                        placeholder="<?=__('Mã giao dịch');?>">
                                </div>
                                <div class="col-md-3 col-6">
                                    <input class="form-control form-control-sm" value="<?=$method;?>" name="method"
                                        placeholder="<?=__('Ngân hàng');?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="created_at" class="form-control form-control-sm"
                                        id="daterange" value="<?=$created_at;?>" placeholder="Chọn thời gian">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-primary"><i class="fa fa-search"></i>
                                        <?=__('Tìm kiếm');?>
                                    </button>
                                    <a class="btn btn-sm btn-danger" href="<?=base_url_admin('recharge-bank');?>"><i
                                            class="fa fa-trash"></i>
                                        <?=__('Xóa bộ lọc');?>
                                    </a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?=$limit == 5 ? 'selected' : '';?> value="5">5</option>
                                        <option <?=$limit == 10 ? 'selected' : '';?> value="10">10</option>
                                        <option <?=$limit == 20 ? 'selected' : '';?> value="20">20</option>
                                        <option <?=$limit == 50 ? 'selected' : '';?> value="50">50</option>
                                        <option <?=$limit == 100 ? 'selected' : '';?> value="100">100</option>
                                        <option <?=$limit == 500 ? 'selected' : '';?> value="500">500</option>
                                        <option <?=$limit == 1000 ? 'selected' : '';?> value="1000">1000
                                        </option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?=__('Short by Date:');?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option <?=$shortByDate == 1 ? 'selected' : '';?> value="1"><?=__('Hôm nay');?>
                                        </option>
                                        <option <?=$shortByDate == 4 ? 'selected' : '';?> value="4"><?=__('Hôm qua');?>
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
                        <!-- Thanh công cụ hàng loạt -->
                        <div id="bulkActionsToolbar" class="card-footer bg-light border-bottom d-none">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">
                                        <span id="selectedCount">0</span> <?=__('hóa đơn đã chọn');?>
                                    </span>
                                </div>
                                <div class="btn-list">
                                    <button type="button" id="btnBulkStatus" class="btn btn-sm btn-primary d-none" onclick="showBulkStatusModal()">
                                        <i class="fa-solid fa-edit me-1"></i><?=__('Thay đổi trạng thái');?>
                                    </button>
                                    <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger d-none" onclick="bulkDeleteInvoices()">
                                        <i class="fa-solid fa-trash me-1"></i><?=__('Xóa đã chọn');?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th><?=__('Thao tác');?></th>
                                        <th><?=__('Username');?></th>
                                        <th class="text-center"><?=__('Mã giao dịch');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th class="text-right"><?=__('Số tiền nạp');?></th>
                                        <th class="text-right"><?=__('Thực nhận');?></th>
                                        <th class="text-center"><?=__('Ngân hàng');?></th>
                                        <th class="text-center"><?=__('Thời gian');?></th>
                                        <th class="text-center"><?=__('Cập nhật');?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i=0; foreach ($listDatatable as $row): ?>
                                    <tr id="invoice-<?=$row['id'];?>" data-invoice-id="<?=$row['id'];?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input invoice-checkbox" value="<?=$row['id'];?>" onchange="updateBulkButtons()">
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="<?=__('Chỉnh sửa');?>" href="<?=base_url_admin('recharge-bank-invoice&id='.$row['id']);?>"><i class="fa fa-edit"></i> Edit</a>
                                            <a type="button" onclick="removeRow('<?=$row['id'];?>')"
                                                class="btn btn-sm btn-danger shadow-danger btn-wave"
                                                data-bs-toggle="tooltip" title="<?=__('Xóa');?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <td><a class="text-primary"
                                                href="<?=base_url_admin('user-edit&id='.$row['user_id']);?>"><?=getRowRealtime("users", $row['user_id'], "username");?>
                                                [ID <?=$row['user_id'];?>]</a>
                                        </td>
                                        <td class="text-center">
                                            <a class="text-primary" target="_blank" href="<?=base_url('payment/'.$row['trans_id']);?>"><?=$row['trans_id'];?></a>
                                        </td>
                                        <td class="text-center">
                                           <?=display_invoice($row['status']);?>
                                        </td>
                                        <td class="text-right"><b
                                                style="color: green;"><?=format_currency($row['amount']);?></b>
                                        </td>
                                        <td class="text-right"><b
                                                style="color: red;"><?=format_currency($row['received']);?></b>
                                        </td>
                                        <td class="text-center"><b><?=$row['short_name'];?></b></td>
                                        <td class="text-center"><span data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['created_at']));?>"><?=$row['created_at'];?></span>
                                        </td>
                                        <td class="text-center"><span data-toggle="tooltip" data-placement="bottom"
                                                title="<?=timeAgo(strtotime($row['updated_at']));?>"><?=$row['updated_at'];?></span>
                                        </td>

                                    </tr>
                                    <?php endforeach?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="float-right">
                                                <?=__('Đã thanh toán:');?>
                                                <strong
                                                    style="color:red;"><?=format_currency($CMSNT->get_row(" SELECT SUM(`amount`) FROM `payment_bank_invoice` WHERE $where AND `status` = 'completed' ")['SUM(`amount`)']);?></strong>
                                                |

                                                <?=__('Thực nhận:');?>
                                                <strong
                                                    style="color:blue;"><?=format_currency($CMSNT->get_row(" SELECT SUM(`received`) FROM `payment_bank_invoice` WHERE $where AND `status` = 'completed' ")['SUM(`received`)']);?></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?=$limit;?> of
                                    <?=format_cash($totalDatatable);?>
                                    Results</p>
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

    <!-- Modal thay đổi trạng thái hàng loạt -->
    <div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?=__('Thay đổi trạng thái hóa đơn');?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted"><?=__('Chọn trạng thái mới cho các hóa đơn đã chọn:');?></p>
                    <select class="form-select" id="bulkStatusSelect">
                        <option value=""><?=__('-- Chọn trạng thái --');?></option>
                        <option value="pending"><?=__('Chờ xác nhận');?></option>
                        <option value="completed"><?=__('Hoàn tất');?></option>
                        <option value="expired"><?=__('Hết hạn');?></option>
                    </select>
                    <small class="text-muted d-block mt-2">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <?=__('Trạng thái này sẽ được áp dụng cho tất cả các hóa đơn đã chọn.');?>
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=__('Hủy');?></button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkStatus()"><?=__('Xác nhận');?></button>
                </div>
            </div>
        </div>
    </div>

    <?php
require_once(__DIR__.'/footer.php');
?>

<script>
// Chọn tất cả / Bỏ chọn tất cả
function toggleSelectAll(checkbox) {
    $('.invoice-checkbox').prop('checked', checkbox.checked);
    updateBulkButtons();
}

// Cập nhật hiển thị nút bulk action
function updateBulkButtons() {
    var selectedCount = $('.invoice-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkActionsToolbar').removeClass('d-none');
        $('#btnBulkDelete, #btnBulkStatus').removeClass('d-none');
    } else {
        $('#bulkActionsToolbar').addClass('d-none');
        $('#btnBulkDelete, #btnBulkStatus').addClass('d-none');
    }
    
    // Cập nhật trạng thái checkbox "Chọn tất cả"
    var totalCheckboxes = $('.invoice-checkbox').length;
    $('#selectAll').prop('checked', selectedCount === totalCheckboxes && totalCheckboxes > 0);
}

// Lấy danh sách ID đã chọn
function getSelectedInvoiceIds() {
    var selectedIds = [];
    $('.invoice-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

// Hiển thị modal thay đổi trạng thái
function showBulkStatusModal() {
    var selectedIds = getSelectedInvoiceIds();
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một hóa đơn");?>', 'error');
        return;
    }
    $('#bulkStatusSelect').val('');
    var modal = new bootstrap.Modal(document.getElementById('bulkStatusModal'));
    modal.show();
}

// Xác nhận thay đổi trạng thái hàng loạt
function confirmBulkStatus() {
    var selectedIds = getSelectedInvoiceIds();
    var status = $('#bulkStatusSelect').val();
    
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một hóa đơn");?>', 'error');
        return;
    }
    
    if (!status) {
        showMessage('<?=__("Vui lòng chọn trạng thái");?>', 'error');
        return;
    }
    
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/update.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'bulkUpdateInvoiceBankStatus',
            ids: selectedIds,
            status: status
        },
        beforeSend: function() {
            $('#btnBulkStatus').prop('disabled', true);
        },
        success: function(result) {
            $('#btnBulkStatus').prop('disabled', false);
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
                bootstrap.Modal.getInstance(document.getElementById('bulkStatusModal')).hide();
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            $('#btnBulkStatus').prop('disabled', false);
            showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
        }
    });
}

// Xóa hàng loạt hóa đơn
function bulkDeleteInvoices() {
    var selectedIds = getSelectedInvoiceIds();
    
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một hóa đơn để xóa");?>', 'error');
        return;
    }
    
    Swal.fire({
        title: "<?=__('Cảnh báo');?>",
        text: "<?=__('Bạn có chắc chắn muốn xóa');?> " + selectedIds.length + " <?=__('hóa đơn đã chọn không? Hành động này không thể hoàn tác.');?>",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Hủy');?>",
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'bulkDeleteInvoiceBank',
                    ids: selectedIds
                },
                beforeSend: function() {
                    $('#btnBulkDelete').prop('disabled', true);
                    $('#btnBulkDelete').html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?=__("Đang xóa...");?>');
                },
                success: function(result) {
                    $('#btnBulkDelete').prop('disabled', false);
                    $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?=__("Xóa đã chọn");?>');
                    
                    if (result.status == 'success') {
                        showMessage(result.msg, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(result.msg, 'error');
                    }
                },
                error: function() {
                    $('#btnBulkDelete').prop('disabled', false);
                    $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?=__("Xóa đã chọn");?>');
                    showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
                }
            });
        }
    });
}

function removeRow(id) {
    Swal.fire({
        title: "<?=__('Xác nhận xóa hóa đơn');?>",
        text: "<?=__('Bạn có chắc chắn muốn xóa hóa đơn này không ?');?>",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Không');?>"
    }).then((result) => {
        if (result.isConfirmed) {
            postremoveRow(id);
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}

function postremoveRow(id) {
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'removeInvoiceBank',
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
</script>