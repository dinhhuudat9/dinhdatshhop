<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Lịch sử hoa hồng') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/affiliates.css">
';
$body['footer'] = '
<script src="' . BASE_URL('mod/') . 'js/affiliates.js"></script>
';

// Kiểm tra affiliate có bật không
if ($CMSNT->site('affiliate_status') != 1) {
    redirect(base_url());
}

require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/../../libs/database/affiliate.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

// Khởi tạo AffiliateHandler
$AffiliateHandler = new AffiliateHandler();
$stats = $AffiliateHandler->getUserStats($getUser['id']);

// Pagination
$limit = validate_int($_GET['limit'] ?? 10, 5, 100) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Filters
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$time = '';
$type = '';

// Filter by type
if (!empty($_GET['type'])) {
    $type = validate_string($_GET['type'], 20);
    if ($type !== false && in_array($type, ['recharge', 'order', 'withdraw', 'refund', 'signup'])) {
        $where_conditions[] = '`type` = ?';
        $where_params[] = $type;
    }
}

// Filter by date range
if (!empty($_GET['time'])) {
    $time = validate_string($_GET['time'], 50);
    if ($time !== false) {
        $date_parts = str_replace('-', '/', $time);
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

$urlDatatable = pagination_client(base_url("?action=affiliate-history&limit=$limit&shortByDate=$shortByDate&time=$time&type=$type&"), $from, $totalDatatable, $limit);

// Function hiển thị loại
function display_type_badge($type)
{
    $types = [
        'recharge' => ['label' => __('Nạp tiền'), 'icon' => 'fa-cash-register', 'class' => 'recharge'],
        'order' => ['label' => __('Đơn hàng'), 'icon' => 'fa-shopping-cart', 'class' => 'order'],
        'withdraw' => ['label' => __('Rút tiền'), 'icon' => 'fa-wallet', 'class' => 'withdraw'],
        'refund' => ['label' => __('Hoàn tiền'), 'icon' => 'fa-undo', 'class' => 'refund'],
        'signup' => ['label' => __('Đăng ký'), 'icon' => 'fa-user-plus', 'class' => 'signup'],
    ];

    $config = $types[$type] ?? ['label' => $type, 'icon' => 'fa-circle', 'class' => ''];
    return '<span class="affiliate-type-badge ' . $config['class'] . '"><i class="fa-solid ' . $config['icon'] . '"></i> ' . $config['label'] . '</span>';
}
?>

<section class="affiliate-page inner-section">
    <div class="container">
        <div class="row content-reverse">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <?php require_once(__DIR__ . '/sidebar.php'); ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Page Header -->
                <div class="affiliate-page-header">
                    <h1 class="affiliate-page-title">
                        <i class="fa-solid fa-chart-line"></i>
                        <?= __('Lịch sử hoa hồng'); ?>
                    </h1>
                    <p class="affiliate-page-subtitle"><?= __('Theo dõi chi tiết các giao dịch hoa hồng của bạn'); ?></p>
                </div>

                <!-- Stats Grid -->
                <div class="affiliate-stats-grid">
                    <div class="affiliate-stat-card success">
                        <div class="affiliate-stat-icon">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                        <div class="affiliate-stat-value"><?= format_currency($stats['recharge_commission']); ?></div>
                        <div class="affiliate-stat-label"><?= __('Từ nạp tiền'); ?></div>
                    </div>
                    <div class="affiliate-stat-card info">
                        <div class="affiliate-stat-icon">
                            <i class="fa-solid fa-shopping-cart"></i>
                        </div>
                        <div class="affiliate-stat-value"><?= format_currency($stats['order_commission']); ?></div>
                        <div class="affiliate-stat-label"><?= __('Từ đơn hàng'); ?></div>
                    </div>
                    <div class="affiliate-stat-card warning">
                        <div class="affiliate-stat-icon">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div class="affiliate-stat-value"><?= format_currency($stats['total_withdrawn']); ?></div>
                        <div class="affiliate-stat-label"><?= __('Đã rút'); ?></div>
                    </div>
                    <div class="affiliate-stat-card primary">
                        <div class="affiliate-stat-icon">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                        <div class="affiliate-stat-value"><?= format_currency($stats['available_balance']); ?></div>
                        <div class="affiliate-stat-label"><?= __('Số dư hiện tại'); ?></div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="affiliate-balance-card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
                    <div class="affiliate-balance-content">
                        <div class="affiliate-quick-links" style="justify-content: center;">
                            <a href="<?= base_url('?action=affiliates'); ?>" class="affiliate-quick-link">
                                <i class="fa-solid fa-home"></i> <?= __('Dashboard'); ?>
                            </a>
                            <a href="<?= base_url('?action=affiliate-withdraw'); ?>" class="affiliate-quick-link">
                                <i class="fa-solid fa-wallet"></i> <?= __('Rút tiền'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- History Table Card -->
                <div class="affiliate-table-card">
                    <div class="affiliate-table-header">
                        <h5 class="affiliate-table-title">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <?= __('Chi tiết giao dịch'); ?>
                            <span class="affiliate-table-badge"><?= $totalDatatable; ?></span>
                        </h5>
                    </div>
                    <div class="affiliate-table-body">
                        <!-- Filter Form -->
                        <form action="" method="GET" class="affiliate-filter-form">
                            <input type="hidden" name="action" value="affiliate-history">
                            <div class="affiliate-filter-row">
                                <div class="affiliate-filter-group">
                                    <select class="affiliate-filter-select" name="type">
                                        <option value=""><?= __('Loại giao dịch'); ?></option>
                                        <option <?= $type == 'recharge' ? 'selected' : ''; ?> value="recharge"><?= __('Nạp tiền'); ?></option>
                                        <option <?= $type == 'order' ? 'selected' : ''; ?> value="order"><?= __('Đơn hàng'); ?></option>
                                        <option <?= $type == 'withdraw' ? 'selected' : ''; ?> value="withdraw"><?= __('Rút tiền'); ?></option>
                                        <option <?= $type == 'refund' ? 'selected' : ''; ?> value="refund"><?= __('Hoàn tiền'); ?></option>
                                    </select>
                                </div>
                                <div class="affiliate-filter-group">
                                    <select name="shortByDate" class="affiliate-filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?></option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?></option>
                                    </select>
                                </div>
                                <div class="affiliate-filter-group flex-2">
                                    <input type="text" class="js-flatpickr affiliate-filter-input"
                                        id="example-flatpickr-range" name="time"
                                        placeholder="<?= __('Chọn thời gian'); ?>"
                                        value="<?= htmlspecialchars($time); ?>" data-mode="range">
                                </div>
                                <div class="affiliate-filter-buttons">
                                    <button type="submit" class="affiliate-filter-btn primary">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                    <a href="<?= base_url('?action=affiliate-history'); ?>" class="affiliate-filter-btn danger">
                                        <i class="fa-solid fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- Table -->
                        <div class="affiliate-table-wrapper">
                            <table class="affiliate-table">
                                <thead>
                                    <tr>
                                        <th><?= __('Loại'); ?></th>
                                        <th class="text-end"><?= __('Trước'); ?></th>
                                        <th class="text-end"><?= __('Thay đổi'); ?></th>
                                        <th class="text-end"><?= __('Sau'); ?></th>
                                        <th><?= __('Thời gian'); ?></th>
                                        <th><?= __('Ghi chú'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($listDatatable) > 0): ?>
                                        <?php foreach ($listDatatable as $row): ?>
                                            <tr>
                                                <td><?= display_type_badge($row['type'] ?? 'recharge'); ?></td>
                                                <td class="text-end">
                                                    <span class="affiliate-amount-muted"><?= format_currency($row['sotientruoc']); ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <?php
                                                    // So sánh số tiền sau và trước để xác định cộng hay trừ
                                                    $isPositive = $row['sotienhientai'] >= $row['sotientruoc'];
                                                    if ($isPositive):
                                                    ?>
                                                        <span class="affiliate-amount positive">+<?= format_currency($row['sotienthaydoi']); ?></span>
                                                    <?php else: ?>
                                                        <span class="affiliate-amount negative">-<?= format_currency($row['sotienthaydoi']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <strong><?= format_currency($row['sotienhientai']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="affiliate-amount-muted"><?= $row['create_gettime']; ?></span>
                                                </td>
                                                <td>
                                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($row['reason']); ?>">
                                                        <?= htmlspecialchars($row['reason']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="affiliate-table-empty">
                                                    <div class="affiliate-table-empty-icon">
                                                        <i class="fa-solid fa-inbox"></i>
                                                    </div>
                                                    <h4><?= __('Chưa có giao dịch nào'); ?></h4>
                                                    <p><?= __('Lịch sử giao dịch hoa hồng sẽ hiển thị tại đây'); ?></p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalDatatable > $limit): ?>
                            <div class="affiliate-pagination-wrapper">
                                <div class="affiliate-pagination-info">
                                    <span><?= __('Hiển thị'); ?> <?= $limit; ?> / <?= $totalDatatable; ?> <?= __('kết quả'); ?></span>
                                    <select name="limit" onchange="window.location.href='<?= base_url('?action=affiliate-history&shortByDate=' . $shortByDate . '&type=' . $type . '&limit='); ?>'+this.value" class="affiliate-pagination-select">
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                    </select>
                                </div>
                                <div class="affiliate-pagination pagination pagination-sm mb-0">
                                    <?= $urlDatatable; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div><!-- /.col-lg-9 -->
        </div><!-- /.row -->
    </div>
</section>

<?php require_once(__DIR__ . '/footer.php'); ?>

<script>
    Dashmix.helpersOnLoad(['js-flatpickr']);
</script>