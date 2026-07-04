<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Rút tiền hoa hồng') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/affiliates.css">
' . renderCaptchaScripts('withdraw_affiliate') . '
';
$body['footer'] = '
<script>var BASE_URL = "' . base_url() . '";</script>
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

// Pagination cho lịch sử rút tiền
$limit = validate_int($_GET['limit'] ?? 10, 5, 100) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Filters
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

$shortByDate = '';
$transid = '';
$time = '';
$status = '';

// Filter by status
if (!empty($_GET['status'])) {
    $status = validate_string($_GET['status'], 20);
    if ($status !== false && in_array($status, ['pending', 'cancel', 'completed'])) {
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status;
    }
}

// Filter by trans_id
if (!empty($_GET['transid'])) {
    $transid = validate_alphanumeric($_GET['transid'], 50);
    if ($transid !== false) {
        $where_conditions[] = '`trans_id` = ?';
        $where_params[] = $transid;
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
$sql_list = "SELECT * FROM `aff_withdraw` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `aff_withdraw` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination_client(base_url("?action=affiliate-withdraw&limit=$limit&shortByDate=$shortByDate&time=$time&transid=$transid&status=$status&"), $from, $totalDatatable, $limit);

// Các thông số
$minWithdraw = $CMSNT->site('affiliate_min') ?: 100000;
$canWithdraw = $stats['available_balance'] >= $minWithdraw;

// Function hiển thị status badge
function display_withdraw_status($status)
{
    $statuses = [
        'pending' => ['label' => __('Đang chờ'), 'icon' => 'fa-clock', 'class' => 'pending'],
        'completed' => ['label' => __('Hoàn thành'), 'icon' => 'fa-check-circle', 'class' => 'completed'],
        'cancel' => ['label' => __('Đã hủy'), 'icon' => 'fa-times-circle', 'class' => 'cancel'],
    ];

    $config = $statuses[$status] ?? ['label' => $status, 'icon' => 'fa-circle', 'class' => 'pending'];
    return '<span class="affiliate-status-badge ' . $config['class'] . '"><i class="fa-solid ' . $config['icon'] . '"></i> ' . $config['label'] . '</span>';
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
                        <i class="fa-solid fa-money-bill-transfer"></i>
                        <?= __('Rút tiền hoa hồng'); ?>
                    </h1>
                    <p class="affiliate-page-subtitle"><?= __('Yêu cầu rút tiền hoa hồng về tài khoản ngân hàng của bạn'); ?></p>
                </div>

                <div class="affiliate-two-col">
                    <!-- Left Column - Withdraw Form -->
                    <div class="affiliate-col-left">
                        <!-- Balance Card -->
                        <div class="affiliate-balance-card">
                            <div class="affiliate-balance-content">
                                <div class="affiliate-balance-label">
                                    <i class="fa-solid fa-wallet"></i> <?= __('Số dư hoa hồng'); ?>
                                </div>
                                <div class="affiliate-balance-value"><?= format_currency($stats['available_balance']); ?></div>

                                <div class="affiliate-mini-stats">
                                    <div class="affiliate-mini-stat">
                                        <div class="affiliate-mini-stat-value"><?= format_currency($stats['total_earned']); ?></div>
                                        <div class="affiliate-mini-stat-label"><?= __('Tổng kiếm được'); ?></div>
                                    </div>
                                    <div class="affiliate-mini-stat">
                                        <div class="affiliate-mini-stat-value"><?= format_currency($stats['total_withdrawn']); ?></div>
                                        <div class="affiliate-mini-stat-label"><?= __('Đã rút'); ?></div>
                                    </div>
                                    <div class="affiliate-mini-stat">
                                        <div class="affiliate-mini-stat-value"><?= format_cash($stats['total_referrals']); ?></div>
                                        <div class="affiliate-mini-stat-label"><?= __('Thành viên'); ?></div>
                                    </div>
                                </div>

                                <div class="affiliate-balance-actions">
                                    <a href="<?= base_url('?action=affiliates'); ?>" class="affiliate-balance-btn">
                                        <i class="fa-solid fa-house"></i>
                                        <span><?= __('Dashboard'); ?></span>
                                    </a>
                                    <a href="<?= base_url('?action=affiliate-history'); ?>" class="affiliate-balance-btn">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        <span><?= __('Lịch sử'); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Withdraw Form Card -->
                        <div class="affiliate-withdraw-card">
                            <div class="affiliate-withdraw-header">
                                <h5 class="affiliate-withdraw-title">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <?= __('Yêu cầu rút tiền'); ?>
                                </h5>
                            </div>
                            <div class="affiliate-withdraw-body">
                                <input type="hidden" id="token" value="<?= $getUser['token']; ?>">
                                <input type="hidden" id="csrf_token" value="<?= generateCSRFToken(); ?>">

                                <div class="affiliate-form-group">
                                    <label class="affiliate-form-label">
                                        <i class="fa-solid fa-building-columns"></i>
                                        <?= __('Ngân hàng'); ?> <span class="required">*</span>
                                    </label>
                                    <select class="affiliate-form-select" id="bank" <?= !$canWithdraw ? 'disabled' : ''; ?>>
                                        <option value="">-- <?= __('Chọn ngân hàng'); ?> --</option>
                                        <?php
                                        $banks = explode(PHP_EOL, $CMSNT->site('affiliate_banks'));
                                        foreach ($banks as $bank):
                                            $bank = trim($bank);
                                            if (!empty($bank)):
                                        ?>
                                                <option value="<?= $bank; ?>"><?= $bank; ?></option>
                                        <?php endif;
                                        endforeach; ?>
                                    </select>
                                </div>

                                <div class="affiliate-form-group">
                                    <label class="affiliate-form-label">
                                        <i class="fa-solid fa-credit-card"></i>
                                        <?= __('Số tài khoản'); ?> <span class="required">*</span>
                                    </label>
                                    <input type="text" class="affiliate-form-input" id="stk"
                                        placeholder="<?= __('Nhập số tài khoản ngân hàng'); ?>"
                                        <?= !$canWithdraw ? 'disabled' : ''; ?>>
                                </div>

                                <div class="affiliate-form-group">
                                    <label class="affiliate-form-label">
                                        <i class="fa-solid fa-user"></i>
                                        <?= __('Tên chủ tài khoản'); ?> <span class="required">*</span>
                                    </label>
                                    <input type="text" class="affiliate-form-input" id="name"
                                        placeholder="<?= __('Nhập tên chủ tài khoản (viết hoa, không dấu)'); ?>"
                                        <?= !$canWithdraw ? 'disabled' : ''; ?>>
                                </div>

                                <div class="affiliate-form-group">
                                    <label class="affiliate-form-label">
                                        <i class="fa-solid fa-money-bill-wave"></i>
                                        <?= __('Số tiền rút'); ?> <span class="required">*</span>
                                    </label>
                                    <input type="number" class="affiliate-form-input" id="amount"
                                        placeholder="<?= __('Nhập số tiền cần rút'); ?>"
                                        min="<?= $minWithdraw; ?>" max="<?= $stats['available_balance']; ?>"
                                        <?= !$canWithdraw ? 'disabled' : ''; ?>>
                                    <div class="affiliate-form-hint">
                                        <?= __('Tối thiểu'); ?>: <strong><?= format_currency($minWithdraw); ?></strong> |
                                        <?= __('Khả dụng'); ?>: <strong><?= format_currency($stats['available_balance']); ?></strong>
                                    </div>
                                </div>

                                <?php if (isCaptchaEnabledForModule('withdraw_affiliate')): ?>
                                    <div class="affiliate-form-group">
                                        <div id="captcha-container" style="display: flex; justify-content: center;">
                                            <?= renderCaptchaWidget('captcha-container', 'withdraw_affiliate'); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="button" class="affiliate-submit-btn" id="btnWithdraw"
                                    data-min-withdraw="<?= $minWithdraw; ?>"
                                    data-captcha-enabled="<?= isCaptchaEnabledForModule('withdraw_affiliate') ? '1' : '0'; ?>"
                                    <?= !$canWithdraw ? 'disabled' : ''; ?>>
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <?= $canWithdraw ? __('Gửi yêu cầu rút tiền') : __('Chưa đủ số dư tối thiểu'); ?>
                                </button>

                                <div class="affiliate-withdraw-info">
                                    <div class="affiliate-withdraw-info-content">
                                        <i class="affiliate-withdraw-info-icon fa-solid fa-info-circle"></i>
                                        <div class="affiliate-withdraw-info-text">
                                            <strong><?= __('Lưu ý'); ?>:</strong><br>
                                            • <?= __('Số tiền rút tối thiểu'); ?>: <strong><?= format_currency($minWithdraw); ?></strong><br>
                                            • <?= __('Yêu cầu sẽ được xử lý trong 24-48 giờ làm việc'); ?><br>
                                            • <?= __('Vui lòng kiểm tra kỹ thông tin ngân hàng'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - History -->
                    <div class="affiliate-col-right">
                        <div class="affiliate-table-card">
                            <div class="affiliate-table-header">
                                <h5 class="affiliate-table-title">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <?= __('Lịch sử rút tiền'); ?>
                                    <span class="affiliate-table-badge"><?= $totalDatatable; ?></span>
                                </h5>
                            </div>
                            <div class="affiliate-table-body">
                                <!-- Filter Form -->
                                <form action="" method="GET" class="affiliate-filter-form">
                                    <input type="hidden" name="action" value="affiliate-withdraw">
                                    <div class="affiliate-filter-row">
                                        <div class="affiliate-filter-group">
                                            <input class="affiliate-filter-input" value="<?= htmlspecialchars($transid); ?>"
                                                name="transid" placeholder="<?= __('Mã giao dịch'); ?>">
                                        </div>
                                        <div class="affiliate-filter-group">
                                            <select class="affiliate-filter-select" name="status">
                                                <option value=""><?= __('Trạng thái'); ?></option>
                                                <option <?= $status == 'pending' ? 'selected' : ''; ?> value="pending"><?= __('Đang chờ'); ?></option>
                                                <option <?= $status == 'cancel' ? 'selected' : ''; ?> value="cancel"><?= __('Đã hủy'); ?></option>
                                                <option <?= $status == 'completed' ? 'selected' : ''; ?> value="completed"><?= __('Hoàn thành'); ?></option>
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
                                            <a href="<?= base_url('?action=affiliate-withdraw'); ?>" class="affiliate-filter-btn danger">
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
                                                <th><?= __('Mã GD'); ?></th>
                                                <th><?= __('Thời gian'); ?></th>
                                                <th class="text-end"><?= __('Số tiền'); ?></th>
                                                <th><?= __('Ngân hàng'); ?></th>
                                                <th class="text-center"><?= __('Trạng thái'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($listDatatable) > 0): ?>
                                                <?php foreach ($listDatatable as $row): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="affiliate-trans-badge">#<?= $row['trans_id']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="affiliate-amount-muted"><?= $row['create_gettime']; ?></span>
                                                        </td>
                                                        <td class="text-end">
                                                            <strong style="color: var(--primary);"><?= format_currency($row['amount']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <div style="font-size: 0.85rem;">
                                                                <?= $row['bank']; ?><br>
                                                                <span class="affiliate-amount-muted"><?= $row['stk']; ?></span>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <?= display_withdraw_status($row['status']); ?>
                                                            <?php if (!empty($row['reason'])): ?>
                                                                <br><small class="text-danger"><?= $row['reason']; ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5">
                                                        <div class="affiliate-table-empty">
                                                            <div class="affiliate-table-empty-icon">
                                                                <i class="fa-solid fa-inbox"></i>
                                                            </div>
                                                            <h4><?= __('Chưa có yêu cầu rút tiền nào'); ?></h4>
                                                            <p><?= __('Lịch sử rút tiền sẽ hiển thị tại đây'); ?></p>
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
                                        </div>
                                        <div class="affiliate-pagination pagination pagination-sm mb-0">
                                            <?= $urlDatatable; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.col-lg-9 -->
        </div><!-- /.row -->
    </div>
</section>

<?php require_once(__DIR__ . '/footer.php'); ?>

<script>
    // Language strings for JS
    window.LANG_ERROR = '<?= __('Lỗi'); ?>';
    window.LANG_SUCCESS = '<?= __('Thành công'); ?>';
    window.LANG_SELECT_BANK = '<?= __('Vui lòng chọn ngân hàng'); ?>';
    window.LANG_ENTER_STK = '<?= __('Vui lòng nhập số tài khoản'); ?>';
    window.LANG_ENTER_NAME = '<?= __('Vui lòng nhập tên chủ tài khoản'); ?>';
    window.LANG_MIN_WITHDRAW = '<?= __('Số tiền rút tối thiểu là'); ?>';
    window.LANG_PROCESSING = '<?= __('Đang xử lý...'); ?>';
    window.LANG_ERROR_OCCURRED = '<?= __('Đã xảy ra lỗi, vui lòng thử lại'); ?>';
    window.LANG_CAPTCHA_REQUIRED = '<?= __('Vui lòng xác nhận Captcha'); ?>';

    Dashmix.helpersOnLoad(['js-flatpickr']);
</script>