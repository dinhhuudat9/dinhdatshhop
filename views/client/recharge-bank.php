<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

$body = [
    'title' => __('Nạp tiền') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
' . renderCaptchaScripts('add_invoice_recharge') . '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
<link rel="stylesheet" href="' . BASE_URL('mod/css/') . 'recharge.css">
';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

$trans_id = '';
$invoice = null;
if (isset($_GET['trans_id'])) {
    $trans_id = validate_alphanumeric($_GET['trans_id'], 100);
    if ($trans_id !== false) {
        $invoice = $CMSNT->get_row_safe("SELECT * FROM `payment_bank_invoice` WHERE `trans_id` = ? AND `user_id` = ?", [$trans_id, $getUser['id']]);
    }
}


// Validate limit parameter
$limit = validate_int($_GET['limit'] ?? 10, 5, 1000) ?: 10;

// Validate page parameter
$page = validate_int($_GET['page'] ?? 1, 1, 10000) ?: 1;
$from = ($page - 1) * $limit;

// Build WHERE conditions with prepared statements
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];
$shortByDate = '';
$transid = '';
$time = '';
$status = '';

// Validate time range parameter
if (!empty($_GET['time'])) {
    $time = validate_string($_GET['time'], 50);
    if ($time !== false) {
        $create_date_1 = str_replace('-', '/', $time);
        $create_date_1 = explode(' to ', $create_date_1);
        if (count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]) {
            $start_date = $create_date_1[0] . ' 00:00:00';
            $end_date = $create_date_1[1] . ' 23:59:59';
            // Validate date format before using
            if (validate_date($create_date_1[0], 'Y/m/d') && validate_date($create_date_1[1], 'Y/m/d')) {
                $where_conditions[] = '`created_at` >= ? AND `created_at` <= ?';
                $where_params[] = $start_date;
                $where_params[] = $end_date;
            }
        }
    }
}

// Validate transid parameter
if (!empty($_GET['transid'])) {
    $transid = validate_alphanumeric($_GET['transid'], 100);
    if ($transid !== false) {
        $where_conditions[] = '`trans_id` LIKE ?';
        $where_params[] = '%' . $transid . '%';
    }
}

// Validate status parameter
if (!empty($_GET['status'])) {
    $status = validate_string($_GET['status'], 20);
    if ($status !== false) {
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status;
    }
}
// Validate shortByDate parameter
if (isset($_GET['shortByDate'])) {
    $shortByDate = validate_int($_GET['shortByDate'], 1, 3);
    if ($shortByDate !== false) {
        $currentWeek = date("W");
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date("Y-m-d");

        if ($shortByDate == 1) {
            $where_conditions[] = "`created_at` LIKE ?";
            $where_params[] = '%' . $currentDate . '%';
        }
        if ($shortByDate == 2) {
            $where_conditions[] = "YEAR(created_at) = ? AND WEEK(created_at, 1) = ?";
            $where_params[] = $currentYear;
            $where_params[] = $currentWeek;
        }
        if ($shortByDate == 3) {
            $where_conditions[] = "MONTH(created_at) = ? AND YEAR(created_at) = ?";
            $where_params[] = $currentMonth;
            $where_params[] = $currentYear;
        }
    }
}

// Build final WHERE clause
$where_clause = implode(' AND ', $where_conditions);



// Execute queries with prepared statements
$sql = "SELECT * FROM `payment_bank_invoice` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);
$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

$count_sql = "SELECT * FROM `payment_bank_invoice` WHERE $where_clause ORDER BY id DESC";
$totalDatatable = $CMSNT->num_rows_safe($count_sql, $where_params);
$urlDatatable = pagination_client(base_url("?action=recharge-bank&limit=$limit&shortByDate=$shortByDate&time=$time&transid=$transid&status=$status&trans_id=$trans_id&"), $from, $totalDatatable, $limit);



?>


<!-- Page Header with Breadcrumb -->
<div class="page-header-modern page-header-compact">
    <div class="container">
        <nav class="breadcrumb-modern">
            <a href="<?= base_url(); ?>"><i class="fa-solid fa-home"></i> <?= __('Trang chủ'); ?></a>
            <span class="separator">›</span>
            <span class="current"><?= __('Nạp tiền qua ngân hàng'); ?></span>
        </nav>
        <h1 class="page-title-modern">
            <i class="fa-solid fa-building-columns"></i>
            <?= __('Nạp tiền qua ngân hàng'); ?>
        </h1>
        <p class="page-subtitle-modern"><?= __('Chuyển khoản ngân hàng để nạp tiền vào tài khoản'); ?></p>
    </div>
</div>

<section class="py-4 inner-section profile-part">
    <div class="container">
        <div class="row">
            <?php if (isset($_GET['trans_id']) && $invoice): ?>
                <?php $bank = $CMSNT->get_row_safe("SELECT * FROM `banks` WHERE `id` = ?", [$invoice['bank_id']]); ?>
                <div class="col-lg-12">

                    <div class="mb-5">
                        <div class="payment-info-container">


                            <div class="row">
                                <!-- Thông tin chuyển khoản -->
                                <div class="col-lg-7">
                                    <div class="card-modern">
                                        <div class="card-modern-header">
                                            <h5><i class="fa-solid fa-building-columns me-2"></i><?= __('Thông tin chuyển khoản'); ?></h5>
                                        </div>
                                        <div class="card-modern-body">
                                            <table class="bank-info-table">
                                                <tbody>
                                                    <tr>
                                                        <th><?= __('Số tiền'); ?>:</th>
                                                        <td>
                                                            <span class="badge bg-success" style="font-size: 16px; padding: 8px 15px;"><?= format_currency($invoice['amount']); ?></span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= __('Ngân hàng'); ?>:</th>
                                                        <td>
                                                            <strong><?= $bank['short_name']; ?></strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= __('Số tài khoản'); ?>:</th>
                                                        <td>
                                                            <code><?= $bank['accountNumber']; ?></code>
                                                            <button type="button" class="copy-btn" onclick="copyToClipboard('<?= $bank['accountNumber']; ?>')" title="<?= __('Sao chép'); ?>">
                                                                <i class="fa-solid fa-copy"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= __('Chủ tài khoản'); ?>:</th>
                                                        <td>
                                                            <code><?= $bank['accountName']; ?></code>
                                                            <button type="button" class="copy-btn" onclick="copyToClipboard('<?= $bank['accountName']; ?>')" title="<?= __('Sao chép'); ?>">
                                                                <i class="fa-solid fa-copy"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><?= __('Nội dung chuyển tiền'); ?>:</th>
                                                        <td>
                                                            <code><?= $invoice['trans_id']; ?></code>
                                                            <button type="button" class="copy-btn" onclick="copyToClipboard('<?= $invoice['trans_id']; ?>')" title="<?= __('Sao chép'); ?>">
                                                                <i class="fa-solid fa-copy"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p class="text-muted small mt-3 mb-0">
                                                <i class="fa-solid fa-circle-info me-1"></i>
                                                <?= __('Nội dung chuyển khoản chỉ áp dụng cho 1 lần chuyển khoản, nếu bạn cần nạp thêm vui lòng tạo hóa đơn mới bằng cách nhấn vào nút bên dưới.'); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Nút tạo hóa đơn mới -->
                                    <div class="d-grid mb-3">
                                        <a href="<?= BASE_URL('client/recharge-bank'); ?>" class="shop-widget-btn">
                                            <i class="fa-solid fa-plus me-1"></i> <?= __('Tạo hóa đơn mới'); ?>
                                        </a>
                                    </div>
                                </div>

                                <!-- Mã QR -->
                                <div class="col-lg-5">
                                    <div class="card-modern">
                                        <div class="card-modern-header">
                                            <h5><i class="fa-solid fa-qrcode me-2"></i><?= __('Quét mã QR để thanh toán'); ?></h5>
                                        </div>
                                        <div class="card-modern-body">
                                            <div class="qr-container">
                                                <?php
                                                if (in_array('VietQR', array_column($config_listbank, 'type')) && in_array($bank['short_name'], array_column($config_listbank, 'shortName'))) {
                                                    $qr = 'https://api.vietqr.io/' . $bank['short_name'] . '/' . $bank['accountNumber'] . '/' . $invoice['amount'] * $CMSNT->site('bank_rate') . '/' . $invoice['trans_id'] . '/vietqr_net_2.jpg?accountName=' . $bank['accountName'];
                                                } elseif (in_array('PromptPay', array_column($config_listbank, 'type')) && in_array($bank['short_name'], array_column($config_listbank, 'shortName'))) {
                                                    $qr = 'https://promptpay.io/' . $bank['accountNumber'] . '/' . $invoice['amount'] * $CMSNT->site('bank_rate');
                                                } else {
                                                    $qr = base_url($bank['image']);
                                                }
                                                ?>
                                                <img src="<?= $qr; ?>" alt="QR Code" class="img-fluid" id="qrCodeImage"
                                                    oncontextmenu="return false;"
                                                    onselectstart="return false;"
                                                    ondragstart="return false;"
                                                    style="user-select: none; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none;">
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-sm btn-dark" onclick="downloadQRCode()" style="padding: 6px 16px; font-size: 13px; border-radius: 6px;">
                                                        <i class="fa-solid fa-download me-1"></i><?= __('Tải QR về máy'); ?>
                                                    </button>
                                                </div>
                                                <p class="text-muted small mt-3 mb-3">
                                                    <?= __('Quét mã QR bằng ứng dụng ngân hàng để thanh toán nhanh chóng'); ?>
                                                </p>

                                                <!-- Bộ đếm ngược -->
                                                <div class="countdown-container">
                                                    <div class="countdown-item">
                                                        <div class="countdown-value" id="countdown-minutes">00</div>
                                                        <div class="countdown-label"><?= __('Phút'); ?></div>
                                                    </div>
                                                    <div class="countdown-separator">:</div>
                                                    <div class="countdown-item">
                                                        <div class="countdown-value" id="countdown-seconds">00</div>
                                                        <div class="countdown-label"><?= __('Giây'); ?></div>
                                                    </div>
                                                </div>
                                                <p class="text-muted small mt-2 mb-0">
                                                    <?= __('Thời gian còn lại để thanh toán'); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lưu ý -->
                            <div class="alert alert-warning mt-4">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                <strong><?= __('Lưu ý'); ?>:</strong>
                                <?= $CMSNT->site('bank_notice'); ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="col-md-7">
                    <div class="card-modern">
                        <div class="card-modern-header">
                            <h5><i class="fa-solid fa-university me-2"></i><?= __('Nạp tiền qua ngân hàng'); ?></h5>
                        </div>
                        <div class="card-modern-body">
                            <form id="recharge-form" class="recharge-form needs-validation" onsubmit="return createInvoice();" novalidate>
                                <!-- Số tiền nạp -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-wallet"></i>
                                        <?= __('Số tiền nạp'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="amount" name="amount"
                                        placeholder="<?= __('Nhập số tiền cần nạp'); ?>" required>
                                    <div class="invalid-feedback"><?= __('Vui lòng nhập số tiền cần nạp'); ?></div>
                                    <div class="form-info">
                                        <small>
                                            <i class="fa-solid fa-circle-info me-1"></i>
                                            <?= __('Số tiền tối thiểu:'); ?> <span class="text-danger"><?= format_currency($CMSNT->site('bank_min')); ?></span>
                                        </small>
                                    </div>
                                </div>

                                <!-- Chọn ngân hàng -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fa-solid fa-building-columns"></i>
                                        <?= __('Chọn ngân hàng'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="bank-select" name="bank" required>
                                        <?php foreach ($CMSNT->get_list_safe(" SELECT * FROM `banks` WHERE `status` = ?", [1]) as $bank): ?>
                                            <option value="<?= $bank['id']; ?>"><?= $bank['short_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback"><?= __('Vui lòng chọn ngân hàng'); ?></div>
                                </div>

                                <!-- Số tiền thực nhận -->
                                <div class="form-group">
                                    <div class="received-amount-box-enhanced">
                                        <div class="label">
                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                            <?= __('Số tiền thực nhận ước tính'); ?>
                                        </div>
                                        <div class="value" id="received_amount">0đ</div>
                                    </div>
                                </div>

                                <?php if (isCaptchaEnabled() && isCaptchaEnabledForModule('add_invoice_recharge')): ?>
                                    <!-- Captcha -->
                                    <div class="form-group">
                                        <center id="captcha-container">
                                            <?= renderCaptchaWidget('captcha-container', 'add_invoice_recharge'); ?>
                                        </center>
                                    </div>
                                <?php endif; ?>

                                <!-- Nút submit -->
                                <div class="form-group mb-0">
                                    <button type="submit" class="create-invoice-btn-enhanced" id="create-invoice-btn">
                                        <span class="btn-text">
                                            <i class="fa-solid fa-shield"></i>
                                            <?= __('Tạo hóa đơn nạp tiền'); ?>
                                        </span>
                                        <span class="btn-spinner d-none">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <?= __('Đang xử lý...'); ?>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <?php
                    $bankPromotions = parseCryptoPromotionsConfig($CMSNT->site('bank_promotions'));
                    ?>
                    <?php if (!empty($bankPromotions)): ?>
                        <div class="card-modern mb-3">
                            <div class="card-modern-header">
                                <h5><i class="fa-solid fa-percent me-2"></i><?= __('Khuyến mãi'); ?></h5>
                            </div>
                            <div class="card-modern-body p-2">
                                <table class="table fs-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col"><?= __('Số tiền nạp'); ?></th>
                                            <th scope="col"><?= __('Khuyến mãi'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bankPromotions as $promotion):
                                            $discountFormatted = rtrim(rtrim(number_format($promotion['discount'], 2, '.', ''), '0'), '.');
                                        ?>
                                            <tr>
                                                <td><b style="color: blue;">≥ <?= format_currency($promotion['min']); ?></b></td>
                                                <td><b style="color: green;">+<?= $discountFormatted; ?>%</b></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="card-modern">
                        <div class="card-modern-header">
                            <h5><i class="fa-solid fa-triangle-exclamation me-2"></i><?= __('Lưu ý'); ?></h5>
                        </div>
                        <div class="card-modern-body">
                            <?= $CMSNT->site('bank_notice'); ?>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            <div class="col-lg-12">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fa-solid fa-clock-rotate-left me-2"></i><?= __('Lịch sử nạp tiền'); ?></h5>
                    </div>
                    <div class="card-modern-body">
                        <form action="<?= base_url(); ?>" method="GET" class="mb-3">
                            <input type="hidden" name="action" value="recharge-bank">
                            <input type="hidden" name="trans_id" value="<?= $trans_id; ?>">
                            <div class="row">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control mb-2" name="transid" value="<?= $transid; ?>"
                                        placeholder="<?= __('Mã giao dịch'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-select mb-2" name="status">
                                        <option value=""><?= __('-- Trạng thái --'); ?></option>
                                        <option value="pending" <?= $status == 'pending' ? 'selected' : ''; ?>>
                                            <?= __('Chờ xác nhận'); ?></option>
                                        <option value="completed" <?= $status == 'completed' ? 'selected' : ''; ?>>
                                            <?= __('Hoàn tất'); ?></option>
                                        <option value="expired" <?= $status == 'expired' ? 'selected' : ''; ?>>
                                            <?= __('Hết hạn'); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" class="js-flatpickr form-control mb-2" id="flatpickr-range" name="time"
                                        placeholder="<?= __('Chọn thời gian cần tìm'); ?>" value="<?= $time; ?>" data-mode="range" readonly>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <button class="shop-widget-btn mb-2"><i class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <a href="<?= base_url('?action=recharge-bank'); ?>" class="shop-widget-btn mb-2"><i class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
                                </div>
                            </div>

                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()" class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                    <select name="shortByDate" onchange="this.form.submit()" class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?></option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?></option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-scroll">
                            <table class="table fs-sm mb-0">
                                <thead>
                                    <tr>
                                        <th><?= __('Mã giao dịch'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th><?= __('Ngân hàng'); ?></th>
                                        <th class="text-center"><?= __('Số tiền cần thanh toán'); ?></th>
                                        <th class="text-center"><?= __('Số tiền nhận được'); ?></th>
                                        <th class="text-center"><?= __('Thời gian tạo hóa đơn'); ?></th>
                                        <th class="text-center"><?= __('Cập nhật'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listDatatable)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <div class="text-center p-3">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json"
                                                        trigger="loop" colors="primary:#121331,secondary:#08a88a"
                                                        style="width:75px;height:75px">
                                                    </lord-icon>
                                                    <h5 class="mt-2"><?= __('Không tìm thấy kết quả'); ?></h5>
                                                    <p class="text-muted mb-0">
                                                        <?= __('Không có nhật ký hoạt động nào được tìm thấy'); ?>
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($listDatatable as $log): ?>
                                            <tr>
                                                <td><a href="<?= base_url('payment/' . $log['trans_id']); ?>"><?= $log['trans_id']; ?></a></td>
                                                <td class="text-center"><?= display_invoice($log['status']); ?></td>
                                                <td><?= $log['short_name']; ?></td>
                                                <td class="text-right"><b style="color: green;"><?= format_currency($log['amount']); ?></b></td>
                                                <td class="text-right"><b style="color: red;"><?= format_currency($log['received']); ?></b></td>
                                                <td class="text-center"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                                <td class="text-center"><?= date('d/m/Y H:i:s', strtotime($log['updated_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="float-right">
                                                <?php
                                                // Tính tổng đã thanh toán
                                                $completed_params = array_merge($where_params, ['completed']);
                                                $completed_where = $where_clause . ' AND `status` = ?';
                                                $completed_total = $CMSNT->get_row_safe("SELECT SUM(`received`) as total FROM `payment_bank_invoice` WHERE $completed_where", $completed_params);

                                                // Tính tổng chưa thanh toán  
                                                $waiting_params = array_merge($where_params, ['waiting']);
                                                $waiting_where = $where_clause . ' AND `status` = ?';
                                                $waiting_total = $CMSNT->get_row_safe("SELECT SUM(`received`) as total FROM `payment_bank_invoice` WHERE $waiting_where", $waiting_params);
                                                ?>
                                                <?= __('Đã thanh toán:'); ?>
                                                <strong style="color:red;"><?= format_currency($completed_total['total'] ?? 0); ?></strong>
                                                | <?= __('Chưa thanh toán:'); ?>
                                                <strong style="color:blue;"><?= format_currency($waiting_total['total'] ?? 0); ?></strong>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="bottom-paginate">
                            <p class="page-info">Showing <?= $limit; ?> of <?= $totalDatatable; ?> Results</p>
                            <div class="pagination">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>


<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    // Hàm wrapper an toàn cho captcha
    function getSafeCaptchaResponse() {
        try {
            if (typeof getCaptchaResponse === 'function') {
                return getCaptchaResponse() || '';
            }
            return '';
        } catch (e) {
            console.warn('Error getting captcha response:', e);
            return '';
        }
    }

    function copyToClipboard(text) {
        // Tạo một phần tử textarea tạm thời
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);

        // Chọn và sao chép nội dung
        textarea.select();
        document.execCommand('copy');

        // Xóa phần tử tạm thời
        document.body.removeChild(textarea);

        showMessage('<?= __('Nội dung đã được sao chép vào clipboard'); ?>', 'success');
    }

    function createInvoice() {
        console.log('createInvoice called');
        var form = document.getElementById('recharge-form');
        if (!form.checkValidity()) {
            console.log('Form validation failed');
            form.reportValidity();
            return false;
        }
        console.log('Form validation passed');

        var amount = $('#amount').val();
        var bank = $('#bank-select').val();

        // Kiểm tra số tiền
        if (!amount || parseFloat(amount) <= 0) {
            Swal.fire({
                icon: 'error',
                title: '<?= __('Lỗi'); ?>',
                text: '<?= __('Vui lòng nhập số tiền hợp lệ'); ?>'
            });
            return false;
        }

        // Kiểm tra số tiền tối thiểu
        var minAmount = <?= $CMSNT->site('bank_min'); ?>;
        if (parseFloat(amount) < minAmount) {
            Swal.fire({
                icon: 'error',
                title: '<?= __('Lỗi'); ?>',
                text: '<?= __('Số tiền tối thiểu là'); ?> ' + minAmount.toLocaleString() + 'đ'
            });
            return false;
        }

        // Kiểm tra captcha nếu cần thiết
        <?php if (isCaptchaEnabled()): ?>
            <?php if (isCaptchaEnabledForModule('add_invoice_recharge')): ?>
                const captchaValue = getSafeCaptchaResponse();
                if (!captchaValue) {
                    Swal.fire({
                        icon: 'error',
                        title: '<?= __('Thất bại'); ?>',
                        text: '<?= __('Vui lòng xác nhận Captcha'); ?>'
                    });
                    return false;
                }
            <?php endif; ?>
        <?php endif; ?>
        var $btn = $('#create-invoice-btn');

        // Thay đổi trạng thái nút - Hiển thị loading
        $btn.find('.btn-text').addClass('d-none');
        $btn.find('.btn-spinner').removeClass('d-none');
        $btn.prop('disabled', true);
        $btn.css('cursor', 'not-allowed');

        // Chuẩn bị dữ liệu để gửi
        var postData = {
            action: 'createInvoice',
            token: '<?= $getUser['token']; ?>',
            amount: amount,
            bank_id: bank
        };

        // Thêm captcha response nếu có
        <?php if (isCaptchaEnabled()): ?>
            var captchaResponse = getSafeCaptchaResponse();
            if (captchaResponse) {
                postData.captcha_response = captchaResponse;
                postData.recaptcha = captchaResponse;
                postData['cf-turnstile-response'] = captchaResponse;
            }
        <?php endif; ?>

        $.ajax({
            url: '<?= BASE_URL('ajaxs/client/recharge.php'); ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    window.location.href = response.payment_url;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '<?= __('Lỗi'); ?>',
                        text: response.msg
                    });

                    // Khôi phục trạng thái nút
                    $btn.find('.btn-spinner').addClass('d-none');
                    $btn.find('.btn-text').removeClass('d-none');
                    $btn.prop('disabled', false);
                    $btn.css('cursor', 'pointer');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '<?= __('Lỗi'); ?>',
                    text: '<?= __('Đã xảy ra lỗi, vui lòng thử lại'); ?>'
                });

                // Khôi phục trạng thái nút
                $btn.find('.btn-spinner').addClass('d-none');
                $btn.find('.btn-text').removeClass('d-none');
                $btn.prop('disabled', false);
                $btn.css('cursor', 'pointer');
            }
        });

        return false;
    }
</script>

<script>
    // Thêm hàm đếm ngược
    function startCountdown(createdTime) {
        const minutesElement = document.getElementById('countdown-minutes');
        const secondsElement = document.getElementById('countdown-seconds');
        const expiryTime = createdTime + <?= $CMSNT->site('bank_expired_invoice'); ?>;
        let hasShownExpiredAlert = false;
        let intervalId;

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expiryTime - now;

            if (timeLeft <= 0) {
                minutesElement.innerHTML = '00';
                secondsElement.innerHTML = '00';

                if (!hasShownExpiredAlert) {
                    hasShownExpiredAlert = true;
                    clearInterval(intervalId);
                }
                return;
            }

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;

            minutesElement.innerHTML = minutes.toString().padStart(2, '0');
            secondsElement.innerHTML = seconds.toString().padStart(2, '0');
        }

        updateCountdown();
        intervalId = setInterval(updateCountdown, 1000);
    }

    // Khởi tạo đếm ngược khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($invoice)): ?>
            const createdTime = <?= strtotime($invoice['created_at']); ?>;
            startCountdown(createdTime);
        <?php endif; ?>
    });
</script>

<script>
    // Hàm kiểm tra trạng thái hóa đơn
    function checkInvoiceStatus() {
        <?php if (isset($invoice)): ?>
            $.ajax({
                url: '<?= BASE_URL('ajaxs/client/recharge.php'); ?>',
                type: 'POST',
                data: {
                    action: 'getInvoice',
                    token: '<?= $getUser['token']; ?>',
                    trans_id: '<?= $invoice['trans_id']; ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.invoice.status == 'completed') {
                            $('.payment-info-container').html(`
                        <div class="completed-invoice-container text-center py-5" style="background: #fff; border-radius: 10px; padding: 40px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                            <div class="success-animation">
                                <div class="success-checkmark">
                                    <div class="check-icon">
                                        <span class="icon-line line-tip"></span>
                                        <span class="icon-line line-long"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 mb-4">
                                <div class="badge bg-success" style="font-size: 16px; padding: 10px 20px;">
                                    <i class="fa-solid fa-circle-check me-1"></i> <?= __('Giao dịch thành công'); ?>
                                </div>
                            </div>
                            <h3 class="text-success fw-bold mb-3"><?= __('Tài khoản của bạn đã được cộng'); ?> ` + response.invoice.received + `</h3>
                            <p class="text-muted fs-5 mb-4"><?= __('Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi'); ?></p>
                            
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-3">
                                <a href="<?= BASE_URL('client/recharge-bank'); ?>" class="btn btn-dark">
                                    <i class="fa-solid fa-plus me-1"></i> <?= __('Tạo hóa đơn mới'); ?>
                                </a>
                                <a href="<?= BASE_URL(); ?>" class="btn btn-danger">
                                    <i class="fa-solid fa-cart-shopping me-1"></i> <?= __('Mua hàng ngay'); ?>
                                </a>
                            </div>
                        </div>
                    `);
                            clearInterval(checkInvoiceInterval);
                        } else if (response.invoice.status == 'expired') {
                            $('.payment-info-container').html(`
                        <div class="expired-invoice-container text-center py-5" style="background: #fff; border-radius: 10px; padding: 40px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                            <div class="expired-icon mb-4">
                                <div style="width: 100px; height: 100px; background: #fee2e2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-clock text-danger" style="font-size: 3rem;"></i>
                                </div>
                            </div>
                            <h4 class="text-danger mb-3"><?= __('Hóa đơn đã hết hạn'); ?></h4>
                            <p class="text-muted mb-4"><?= __('Hóa đơn này đã hết hạn, vui lòng tạo hóa đơn mới để tiếp tục thanh toán.'); ?></p>
                           
                            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                                <a href="<?= BASE_URL('client/recharge-bank'); ?>" class="btn btn-dark">
                                    <i class="fa-solid fa-plus me-1"></i> <?= __('Tạo hóa đơn mới'); ?>
                                </a>
                            </div>
                        </div>
                    `);
                            clearInterval(checkInvoiceInterval);
                        }
                    }
                },
                error: function() {
                    console.log('Lỗi khi kiểm tra trạng thái hóa đơn');
                }
            });
        <?php endif; ?>
    }

    // Biến để lưu ID của interval
    let checkInvoiceInterval;

    // Thiết lập interval để gọi hàm kiểm tra mỗi 10 giây
    $(document).ready(function() {
        <?php if (isset($invoice)): ?>
            checkInvoiceStatus();
            checkInvoiceInterval = setInterval(checkInvoiceStatus, 10000);
        <?php endif; ?>
    });
</script>

<?php if (isset($invoice)): ?>
    <script>
        function downloadQRCode() {
            const qrImageUrl = '<?= $qr; ?>';
            const fileName = 'QR_<?= $invoice['trans_id']; ?>.jpg';
            fetch(qrImageUrl)
                .then(response => response.blob())
                .then(blob => {
                    const blobUrl = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = blobUrl;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(blobUrl);
                    document.body.removeChild(a);
                    showMessage('<?= __('Ảnh QR đã được tải về máy của bạn'); ?>', 'success');
                })
                .catch(error => {
                    showMessage('<?= __('Không thể tải xuống ảnh QR. Vui lòng thử lại sau.'); ?>', 'error');
                });
        }
    </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    flatpickr("#flatpickr-range", {
        mode: "range",
        dateFormat: "Y-m-d",
        enableTime: false,
        altInput: true,
        altFormat: "d/m/Y",
        defaultDate: "<?= $time ?>",
        locale: {
            firstDayOfWeek: 1,
            weekdays: {
                shorthand: [
                    "<?= __('CN'); ?>",
                    "<?= __('T2'); ?>",
                    "<?= __('T3'); ?>",
                    "<?= __('T4'); ?>",
                    "<?= __('T5'); ?>",
                    "<?= __('T6'); ?>",
                    "<?= __('T7'); ?>"
                ],
                longhand: [
                    "<?= __('Chủ Nhật'); ?>",
                    "<?= __('Thứ 2'); ?>",
                    "<?= __('Thứ 3'); ?>",
                    "<?= __('Thứ 4'); ?>",
                    "<?= __('Thứ 5'); ?>",
                    "<?= __('Thứ 6'); ?>",
                    "<?= __('Thứ 7'); ?>"
                ]
            },
            months: {
                shorthand: [
                    "<?= __('Th1'); ?>",
                    "<?= __('Th2'); ?>",
                    "<?= __('Th3'); ?>",
                    "<?= __('Th4'); ?>",
                    "<?= __('Th5'); ?>",
                    "<?= __('Th6'); ?>",
                    "<?= __('Th7'); ?>",
                    "<?= __('Th8'); ?>",
                    "<?= __('Th9'); ?>",
                    "<?= __('Th10'); ?>",
                    "<?= __('Th11'); ?>",
                    "<?= __('Th12'); ?>"
                ],
                longhand: [
                    "<?= __('Tháng 1'); ?>",
                    "<?= __('Tháng 2'); ?>",
                    "<?= __('Tháng 3'); ?>",
                    "<?= __('Tháng 4'); ?>",
                    "<?= __('Tháng 5'); ?>",
                    "<?= __('Tháng 6'); ?>",
                    "<?= __('Tháng 7'); ?>",
                    "<?= __('Tháng 8'); ?>",
                    "<?= __('Tháng 9'); ?>",
                    "<?= __('Tháng 10'); ?>",
                    "<?= __('Tháng 11'); ?>",
                    "<?= __('Tháng 12'); ?>"
                ]
            }
        }
    });
</script>

<script>
    // Xử lý tính toán số tiền thực nhận
    $(document).ready(function() {
        $("#amount").on("input", function() {
            try {
                var amount = parseFloat($(this).val()) || 0;

                if (amount > 0) {
                    $("#received_amount").html('<small><i class="spinner-border spinner-border-sm"></i> <?= __('Đang tính...'); ?></small>');

                    $.ajax({
                        url: "<?= base_url('ajaxs/client/recharge.php'); ?>",
                        method: "POST",
                        dataType: "JSON",
                        data: {
                            action: 'getReceivedBank',
                            amount: amount
                        },
                        success: function(response) {
                            if (response.status == 'success') {
                                $(".received-amount-box-enhanced").css({
                                    'background': 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)',
                                    'border-color': '#10b981',
                                    'box-shadow': '0 2px 8px rgba(16, 185, 129, 0.3)'
                                });
                                $("#received_amount").html(response.received);
                            } else {
                                $(".received-amount-box-enhanced").css({
                                    'background': 'linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%)',
                                    'border-color': '#ffc107',
                                    'box-shadow': '0 2px 8px rgba(255, 193, 7, 0.2)'
                                });
                                $("#received_amount").html("0đ");
                            }
                        },
                        error: function() {
                            $(".received-amount-box-enhanced").css({
                                'background': 'linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%)',
                                'border-color': '#ffc107',
                                'box-shadow': '0 2px 8px rgba(255, 193, 7, 0.2)'
                            });
                            $("#received_amount").html("0đ");
                        }
                    });
                } else {
                    $(".received-amount-box-enhanced").css({
                        'background': 'linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%)',
                        'border-color': '#ffc107',
                        'box-shadow': '0 2px 8px rgba(255, 193, 7, 0.2)'
                    });
                    $("#received_amount").html("0đ");
                }
            } catch (error) {
                $("#received_amount").html("0đ");
            }
        });
    });
</script>