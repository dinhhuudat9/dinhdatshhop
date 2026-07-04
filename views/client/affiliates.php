<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Affiliate Program') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/affiliates.css">
';
$body['footer'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
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

// Lấy thống kê
$stats = $AffiliateHandler->getUserStats($getUser['id']);

// Đảm bảo user có ref_code
if (empty($stats['ref_code'])) {
    $stats['ref_code'] = $AffiliateHandler->generateRefCode($getUser['id']);
}

// Pagination cho danh sách referrals
$limit = validate_int($_GET['limit'] ?? 10, 5, 100) ?: 10;
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Filters
$shortByDate = '';
$time = '';

$where_conditions = ["`ref_id` = ?"];
$where_params = [$getUser['id']];

// Filter by date range
if (!empty($_GET['time'])) {
    $time = validate_string($_GET['time'], 50);
    if ($time !== false) {
        $date_parts = str_replace('-', '/', $time);
        $date_parts = explode(' to ', $date_parts);
        if (count($date_parts) == 2 && $date_parts[0] != $date_parts[1]) {
            $where_conditions[] = '`create_date` >= ? AND `create_date` <= ?';
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
            $where_conditions[] = '`create_date` LIKE ?';
            $where_params[] = '%' . $currentDate . '%';
        }
        if ($shortByDate == 2) {
            $where_conditions[] = 'YEAR(create_date) = ? AND WEEK(create_date, 1) = ?';
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if ($shortByDate == 3) {
            $where_conditions[] = 'MONTH(create_date) = ? AND YEAR(create_date) = ?';
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// Build query
$where_clause = implode(' AND ', $where_conditions);
$sql_list = "SELECT id, username, create_date, total_money, ref_amount FROM `users` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql_list, $params_with_limit);

$sql_count = "SELECT COUNT(*) as total FROM `users` WHERE $where_clause";
$totalDatatable = $CMSNT->get_row_safe($sql_count, $where_params)['total'];

$urlDatatable = pagination_client(base_url("?action=affiliates&limit=$limit&shortByDate=$shortByDate&time=$time&"), $from, $totalDatatable, $limit);

// Tính tỷ lệ hoa hồng
$rechargeRate = $CMSNT->site('affiliate_ck') ?: 0;
if ($stats['custom_rate'] > 0) {
    $rechargeRate = $stats['custom_rate'];
}
$orderRate = $CMSNT->site('affiliate_order_ck') ?: 0;

// Tính % tiến độ đến mức rút tối thiểu
$minWithdraw = $CMSNT->site('affiliate_min') ?: 100000;
$progressPercent = min(100, ($stats['available_balance'] / $minWithdraw) * 100);
$amountNeeded = max(0, $minWithdraw - $stats['available_balance']);

// Link affiliate
$affiliateLink = base_url('?aff=' . $stats['ref_code']);
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
                        <i class="fa-solid fa-handshake"></i>
                        <?= __('Affiliate Program'); ?>
                    </h1>
                    <p class="affiliate-page-subtitle"><?= __('Giới thiệu bạn bè và nhận hoa hồng hấp dẫn'); ?></p>
                </div>

                <!-- Balance Hero Card -->
                <div class="affiliate-balance-card">
                    <div class="affiliate-balance-content">
                        <!-- Header: Label + Buttons -->
                        <div class="affiliate-balance-header">
                            <span class="affiliate-balance-label">
                                <i class="fa-solid fa-wallet"></i> <?= __('Số dư khả dụng'); ?>
                            </span>
                            <div class="affiliate-balance-btns">
                                <a href="<?= base_url('?action=affiliate-withdraw'); ?>" class="affiliate-action-btn primary">
                                    <i class="fa-solid fa-money-bill-transfer"></i> <?= __('Rút tiền'); ?>
                                </a>
                                <a href="<?= base_url('?action=affiliate-history'); ?>" class="affiliate-action-btn outline">
                                    <i class="fa-solid fa-history"></i> <?= __('Lịch sử'); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Balance Value -->
                        <div class="affiliate-balance-value"><?= format_currency($stats['available_balance']); ?></div>

                        <!-- Rate Badges -->
                        <div class="affiliate-rate-badges">
                            <?php if ($CMSNT->site('affiliate_recharge_status') == 1): ?>
                                <span class="affiliate-rate-badge">
                                    <i class="fa-solid fa-percent"></i> <?= __('Nạp tiền'); ?>: <?= $rechargeRate; ?>%
                                </span>
                            <?php endif ?>
                            <?php if ($CMSNT->site('affiliate_order_status') == 1): ?>
                                <span class="affiliate-rate-badge">
                                    <i class="fa-solid fa-shopping-cart"></i> <?= __('Đơn hàng'); ?>: <?= $orderRate; ?>%
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Mini Stats -->
                        <div class="affiliate-mini-stats">
                            <div class="affiliate-mini-stat">
                                <div class="affiliate-mini-stat-value"><?= format_cash($stats['total_referrals']); ?></div>
                                <div class="affiliate-mini-stat-label"><?= __('Thành viên'); ?></div>
                            </div>
                            <div class="affiliate-mini-stat">
                                <div class="affiliate-mini-stat-value"><?= format_cash($stats['total_clicks']); ?></div>
                                <div class="affiliate-mini-stat-label"><?= __('Lượt click'); ?></div>
                            </div>
                            <div class="affiliate-mini-stat">
                                <div class="affiliate-mini-stat-value"><?= format_currency($stats['total_earned']); ?></div>
                                <div class="affiliate-mini-stat-label"><?= __('Tổng kiếm được'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="affiliate-two-col">
                    <!-- Left Column -->
                    <div class="affiliate-col-left">
                        <!-- Affiliate Link Card -->
                        <div class="affiliate-link-card">
                            <h5 class="affiliate-link-title">
                                <i class="fa-solid fa-link"></i>
                                <?= __('Liên kết giới thiệu của bạn'); ?>
                            </h5>

                            <div class="affiliate-link-input-wrapper">
                                <input type="text" readonly id="affiliateUrl" class="affiliate-link-input" value="<?= $affiliateLink; ?>">
                                <button type="button" class="affiliate-copy-btn" data-clipboard-target="#affiliateUrl">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>

                            <div class="affiliate-social-share">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($affiliateLink); ?>" target="_blank" class="affiliate-social-btn facebook" title="Facebook">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($affiliateLink); ?>&text=<?= urlencode(__('Tham gia ngay!')); ?>" target="_blank" class="affiliate-social-btn twitter" title="Twitter">
                                    <i class="fa-brands fa-x-twitter"></i>
                                </a>
                                <a href="https://t.me/share/url?url=<?= urlencode($affiliateLink); ?>" target="_blank" class="affiliate-social-btn telegram" title="Telegram">
                                    <i class="fa-brands fa-telegram"></i>
                                </a>
                                <a href="https://wa.me/?text=<?= urlencode($affiliateLink); ?>" target="_blank" class="affiliate-social-btn whatsapp" title="WhatsApp">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>


                        <!-- Progress Card -->
                        <div class="affiliate-progress-card">
                            <div class="affiliate-progress-header">
                                <span><?= __('Tiến độ rút tiền'); ?></span>
                                <span><?= round($progressPercent); ?>%</span>
                            </div>
                            <div class="affiliate-progress-bar">
                                <div class="affiliate-progress-fill" style="width: <?= $progressPercent; ?>%;"></div>
                            </div>
                            <div class="affiliate-progress-labels">
                                <span><?= format_currency(0); ?></span>
                                <span><?= format_currency($minWithdraw); ?></span>
                            </div>
                            <?php if ($amountNeeded > 0): ?>
                                <div class="affiliate-progress-note">
                                    <i class="fa-solid fa-info-circle"></i>
                                    <?= __('Cần thêm'); ?> <strong style="margin: 0 4px;"><?= format_currency($amountNeeded); ?></strong> <?= __('để rút tiền'); ?>
                                </div>
                            <?php else: ?>
                                <div class="affiliate-progress-note">
                                    <i class="fa-solid fa-check-circle"></i>
                                    <?= __('Bạn đã đủ điều kiện rút tiền!'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="affiliate-col-right">
                        <?php if (!empty($CMSNT->site('affiliate_note'))): ?>
                            <div class="affiliate-note-box">
                                <h6 class="affiliate-note-title">
                                    <i class="fa-solid fa-info-circle"></i>
                                    <?= __('Lưu ý'); ?>
                                </h6>
                                <div class="affiliate-note-content"><?= $CMSNT->site('affiliate_note'); ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Commission Breakdown Card -->
                        <div class="affiliate-breakdown-card">
                            <h5 class="affiliate-breakdown-title">
                                <i class="fa-solid fa-chart-pie"></i>
                                <?= __('Chi tiết hoa hồng'); ?>
                            </h5>

                            <div class="affiliate-breakdown-row">
                                <span class="affiliate-breakdown-label"><?= __('Từ nạp tiền'); ?></span>
                                <span class="affiliate-breakdown-value success"><?= format_currency($stats['recharge_commission']); ?></span>
                            </div>
                            <div class="affiliate-breakdown-row">
                                <span class="affiliate-breakdown-label"><?= __('Từ đơn hàng'); ?></span>
                                <span class="affiliate-breakdown-value info"><?= format_currency($stats['order_commission']); ?></span>
                            </div>
                            <div class="affiliate-breakdown-row">
                                <span class="affiliate-breakdown-label"><?= __('Đã rút'); ?></span>
                                <span class="affiliate-breakdown-value warning"><?= format_currency($stats['total_withdrawn']); ?></span>
                            </div>
                            <div class="affiliate-breakdown-row">
                                <span class="affiliate-breakdown-label" style="font-weight: 600; color: #1e293b;"><?= __('Số dư hiện tại'); ?></span>
                                <span class="affiliate-breakdown-value primary"><?= format_currency($stats['available_balance']); ?></span>
                            </div>

                            <div class="affiliate-breakdown-actions">
                                <a href="<?= base_url('?action=affiliate-history'); ?>" class="affiliate-breakdown-btn outline">
                                    <i class="fa-solid fa-history"></i> <?= __('Lịch sử'); ?>
                                </a>
                                <a href="<?= base_url('?action=affiliate-withdraw'); ?>" class="affiliate-breakdown-btn filled">
                                    <i class="fa-solid fa-wallet"></i> <?= __('Rút tiền'); ?>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Referrals Table - Full Width -->
                <div class="affiliate-table-card" style="margin-top: 1.5rem;">
                    <div class="affiliate-table-header">
                        <h5 class="affiliate-table-title">
                            <i class="fa-solid fa-users"></i>
                            <?= __('Thành viên đã giới thiệu'); ?>
                            <span class="affiliate-table-badge"><?= format_cash($totalDatatable); ?></span>
                        </h5>
                    </div>
                    <div class="affiliate-table-body">
                        <!-- Filter Form -->
                        <form action="" method="GET" class="affiliate-filter-form">
                            <input type="hidden" name="action" value="affiliates">
                            <div class="affiliate-filter-row">
                                <div class="affiliate-filter-group flex-2">
                                    <input type="text" class="js-flatpickr affiliate-filter-input"
                                        id="example-flatpickr-range" name="time"
                                        placeholder="<?= __('Chọn thời gian'); ?>"
                                        value="<?= htmlspecialchars($time); ?>" data-mode="range">
                                </div>
                                <div class="affiliate-filter-group">
                                    <select name="shortByDate" class="affiliate-filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?></option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?></option>
                                    </select>
                                </div>
                                <div class="affiliate-filter-buttons">
                                    <button type="submit" class="affiliate-filter-btn primary">
                                        <i class="fa-solid fa-search"></i>
                                    </button>
                                    <a href="<?= base_url('?action=affiliates'); ?>" class="affiliate-filter-btn danger">
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
                                        <th><?= __('Thành viên'); ?></th>
                                        <th class="text-center"><?= __('Ngày đăng ký'); ?></th>
                                        <th class="text-end"><?= __('Hoa hồng'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($listDatatable) > 0): ?>
                                        <?php foreach ($listDatatable as $row): ?>
                                            <tr>
                                                <td>
                                                    <div class="affiliate-user-cell">
                                                        <div class="affiliate-user-avatar">
                                                            <?= strtoupper(substr($row['username'], 0, 1)); ?>
                                                        </div>
                                                        <span class="affiliate-user-name"><?= maskUsername($row['username']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="affiliate-amount-muted"><?= $row['create_date']; ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="affiliate-commission-badge"><?= format_currency($row['ref_amount']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3">
                                                <div class="affiliate-table-empty">
                                                    <div class="affiliate-table-empty-icon">
                                                        <i class="fa-solid fa-users-slash"></i>
                                                    </div>
                                                    <h4><?= __('Chưa có thành viên nào'); ?></h4>
                                                    <p><?= __('Chia sẻ link giới thiệu để mời bạn bè'); ?></p>
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
            </div><!-- /.col-lg-9 -->
        </div><!-- /.row -->
    </div>
</section>

<?php require_once(__DIR__ . '/footer.php'); ?>

<script>
    window.LANG_COPIED = '<?= __('Đã sao chép link giới thiệu!'); ?>';
    Dashmix.helpersOnLoad(['js-flatpickr']);
</script>