<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Lịch sử hoa hồng Affiliate'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '';

require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');

if (checkPermission($getUser['admin'], 'view_affiliate') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back().location.reload();}</script>');
}

// Pagination
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Filters
$where_conditions = ["`id` > 0"];
$where_params = [];

$user_id = '';
$reason = '';
$create_gettime = '';
$username = '';
$shortByDate = '';
$type = '';

// Filter by username
if (!empty($_GET['username'])) {
    $username = validate_string($_GET['username'], 100);
    if ($username !== false) {
        $idUser = $CMSNT->get_row_safe("SELECT id FROM `users` WHERE `username` = ?", [$username]);
        if ($idUser) {
            $where_conditions[] = '`user_id` = ?';
            $where_params[] = $idUser['id'];
        } else {
            $where_conditions[] = '`user_id` = ?';
            $where_params[] = 0;
        }
    }
}

// Filter by user_id
if (!empty($_GET['user_id'])) {
    $user_id = validate_int($_GET['user_id'], 1);
    if ($user_id !== false) {
        $where_conditions[] = '`user_id` = ?';
        $where_params[] = $user_id;
    }
}

// Filter by type
if (!empty($_GET['type'])) {
    $type = validate_string($_GET['type'], 20);
    if ($type !== false && in_array($type, ['recharge', 'order', 'withdraw', 'refund', 'manual', 'signup'])) {
        $where_conditions[] = '`type` = ?';
        $where_params[] = $type;
    }
}

// Filter by reason
if (!empty($_GET['reason'])) {
    $reason = validate_string($_GET['reason'], 255);
    if ($reason !== false) {
        $where_conditions[] = '`reason` LIKE ?';
        $where_params[] = '%' . $reason . '%';
    }
}

// Filter by date range
if (!empty($_GET['create_gettime'])) {
    $create_gettime = validate_string($_GET['create_gettime'], 50);
    if ($create_gettime !== false) {
        $date_parts = str_replace('-', '/', $create_gettime);
        $date_parts = explode(' to ', $date_parts);
        if (count($date_parts) == 2 && $date_parts[0] != $date_parts[1]) {
            $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
            $where_params[] = $date_parts[0] . ' 00:00:00';
            $where_params[] = $date_parts[1] . ' 23:59:59';
        }
    }
}

// Filter by shortByDate
if (isset($_GET['shortByDate']) && $_GET['shortByDate'] !== '') {
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if ($shortByDate !== false) {
        $currentDate = date("Y-m-d");
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');

        if ($shortByDate == 1) {
            $where_conditions[] = '`create_gettime` LIKE ?';
            $where_params[] = '%' . $currentDate . '%';
        }
        if ($shortByDate == 2) {
            $where_conditions[] = 'YEAR(create_gettime) = ? AND WEEK(create_gettime, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if ($shortByDate == 3) {
            $where_conditions[] = 'MONTH(create_gettime) = ? AND YEAR(create_gettime) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// Build query
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT * FROM `aff_log` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `aff_log` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination(base_url_admin("affiliate-history&limit=$limit&shortByDate=$shortByDate&user_id=$user_id&reason=$reason&create_gettime=$create_gettime&username=$username&type=$type&"), $from, $totalDatatable, $limit);

// Statistics
$currentMonth = date('m');
$currentYear = date('Y');
$currentDate = date("Y-m-d");

// Tổng hoa hồng đã trả (chỉ tính recharge, order, signup)
$totalPaid = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(sotienthaydoi), 0) as total FROM `aff_log` WHERE `sotienthaydoi` > 0"
)['total'] ?? 0;

// Hoa hồng từ nạp tiền
$rechargeCommission = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(sotienthaydoi), 0) as total FROM `aff_log` WHERE `type` = 'recharge' AND `sotienthaydoi` > 0"
)['total'] ?? 0;

// Hoa hồng từ đơn hàng
$orderCommission = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(sotienthaydoi), 0) as total FROM `aff_log` WHERE `type` = 'order' AND `sotienthaydoi` > 0"
)['total'] ?? 0;

// Số giao dịch hôm nay
$todayTransactions = $CMSNT->get_row_safe(
    "SELECT COUNT(*) as total FROM `aff_log` WHERE `create_gettime` LIKE ?",
    ['%' . $currentDate . '%']
)['total'] ?? 0;

// Function hiển thị loại hoa hồng
function display_commission_type($type)
{
    $types = [
        'recharge' => '<span class="badge bg-success-gradient"><i class="ti ti-cash me-1"></i>Nạp tiền</span>',
        'order' => '<span class="badge bg-info-gradient"><i class="ti ti-shopping-cart me-1"></i>Đơn hàng</span>',
        'withdraw' => '<span class="badge bg-warning-gradient"><i class="ti ti-wallet me-1"></i>Rút tiền</span>',
        'refund' => '<span class="badge bg-danger-gradient"><i class="ti ti-receipt-refund me-1"></i>Hoàn tiền</span>',
        'manual' => '<span class="badge bg-secondary-gradient"><i class="ti ti-adjustments me-1"></i>Thủ công</span>',
        'signup' => '<span class="badge bg-primary-gradient"><i class="ti ti-user-plus me-1"></i>Đăng ký</span>'
    ];
    return $types[$type] ?? '<span class="badge bg-light text-dark">' . $type . '</span>';
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= __('Lịch sử hoa hồng'); ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?= __('Affiliate Program'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= __('Lịch sử hoa hồng'); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?= __('Tổng hoa hồng đã trả'); ?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?= format_currency($totalPaid); ?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-primary-transparent rounded">
                                <i class="ti ti-coins fs-24 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?= __('Từ nạp tiền'); ?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?= format_currency($rechargeCommission); ?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-success-transparent rounded">
                                <i class="ti ti-cash fs-24 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?= __('Từ đơn hàng'); ?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?= format_currency($orderCommission); ?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-info-transparent rounded">
                                <i class="ti ti-shopping-cart fs-24 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?= __('Giao dịch hôm nay'); ?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?= format_cash($todayTransactions); ?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-warning-transparent rounded">
                                <i class="ti ti-chart-line fs-24 text-warning"></i>
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
                            <i class="ti ti-history me-2"></i><?= __('NHẬT KÝ HOA HỒNG'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search Form -->
                        <form action="<?= base_url(); ?>" class="align-items-center mb-3" name="formSearch" method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="<?= $CMSNT->site('path_admin'); ?>">
                                <input type="hidden" name="action" value="affiliate-history">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= htmlspecialchars($user_id); ?>" name="user_id" placeholder="<?= __('ID User'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= htmlspecialchars($username); ?>" name="username" placeholder="<?= __('Username'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-control form-control-sm" name="type">
                                        <option value=""><?= __('Loại hoa hồng'); ?></option>
                                        <option <?= $type == 'recharge' ? 'selected' : ''; ?> value="recharge"><?= __('Nạp tiền'); ?></option>
                                        <option <?= $type == 'order' ? 'selected' : ''; ?> value="order"><?= __('Đơn hàng'); ?></option>
                                        <option <?= $type == 'withdraw' ? 'selected' : ''; ?> value="withdraw"><?= __('Rút tiền'); ?></option>
                                        <option <?= $type == 'refund' ? 'selected' : ''; ?> value="refund"><?= __('Hoàn tiền'); ?></option>
                                        <option <?= $type == 'signup' ? 'selected' : ''; ?> value="signup"><?= __('Đăng ký'); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control form-control-sm" value="<?= htmlspecialchars($reason); ?>" name="reason" placeholder="<?= __('Lý do'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" name="create_gettime" class="form-control form-control-sm" id="daterange" value="<?= htmlspecialchars($create_gettime); ?>" placeholder="<?= __('Chọn thời gian'); ?>">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-hero btn-sm btn-primary"><i class="fa fa-search"></i> <?= __('Tìm kiếm'); ?></button>
                                    <a class="btn btn-hero btn-sm btn-danger" href="<?= base_url_admin('affiliate-history'); ?>"><i class="fa fa-trash"></i> <?= __('Xóa bộ lọc'); ?></a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label"><?= __('Hiển thị'); ?> :</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?= __('Sắp xếp theo ngày'); ?> :</label>
                                    <select name="shortByDate" onchange="this.form.submit()" class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?></option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </form>

                        <!-- Data Table -->
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= __('Người nhận'); ?></th>
                                        <th class="text-center"><?= __('Loại'); ?></th>
                                        <th class="text-center"><?= __('Số dư trước'); ?></th>
                                        <th class="text-center"><?= __('Thay đổi'); ?></th>
                                        <th class="text-center"><?= __('Số dư sau'); ?></th>
                                        <th class="text-center"><?= __('Thời gian'); ?></th>
                                        <th><?= __('Lý do'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($listDatatable) > 0): ?>
                                        <?php foreach ($listDatatable as $row): ?>
                                            <tr>
                                                <td>
                                                    <a class="text-primary fw-semibold" href="<?= base_url_admin('user-edit&id=' . $row['user_id']); ?>">
                                                        <?= getRowRealtime("users", $row['user_id'], "username"); ?>
                                                    </a>
                                                    <small class="text-muted d-block">[ID: <?= $row['user_id']; ?>]</small>
                                                </td>
                                                <td class="text-center">
                                                    <?= display_commission_type($row['type'] ?? 'recharge'); ?>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-muted"><?= format_currency($row['sotientruoc']); ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($row['sotienthaydoi'] > 0 && !in_array($row['type'], ['withdraw'])): ?>
                                                        <span class="badge bg-success-gradient">+<?= format_currency($row['sotienthaydoi']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-gradient">-<?= format_currency($row['sotienthaydoi']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-semibold text-primary"><?= format_currency($row['sotienhientai']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark"><?= $row['create_gettime']; ?></span>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($row['reason'] ?? ''); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="ti ti-inbox fs-1 text-muted d-block mb-2"></i>
                                                <?= __('Không có dữ liệu'); ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info"><?= __('Hiển thị'); ?> <?= $limit; ?> <?= __('trên tổng'); ?> <?= format_cash($totalDatatable); ?> <?= __('kết quả'); ?></p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>