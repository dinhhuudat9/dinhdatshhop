<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Nạp tiền bằng Crypto') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
<link rel="stylesheet" href="' . BASE_URL('mod/css/') . 'recharge.css">
';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_user.php');
if ($CMSNT->site('crypto_status') != 1) {
    redirect(base_url('client/recharge'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');



if (isset($_GET['limit'])) {
    $limit = intval(check_string($_GET['limit']));
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = check_string(intval($_GET['page']));
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$shortByDate = '';
$trans_id = '';
$time = '';
$amount = '';
$status = '';

// Xây dựng WHERE clause an toàn với prepared statements
$where_conditions = ["`user_id` = ?"];
$where_params = [$getUser['id']];

if (!empty($_GET['status'])) {
    $status = trim($_GET['status']);
    // Chỉ cho phép các giá trị hợp lệ
    if (in_array($status, ['waiting', 'expired', 'completed'])) {
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status;
    }
}
if (!empty($_GET['trans_id'])) {
    $trans_id = trim($_GET['trans_id']);
    // Chỉ cho phép alphanumeric và một số ký tự đặc biệt
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $trans_id)) {
        $where_conditions[] = '`trans_id` LIKE ?';
        $where_params[] = '%' . $trans_id . '%';
    }
}
if (!empty($_GET['amount'])) {
    $amount = intval($_GET['amount']);
    if ($amount > 0) {
        $where_conditions[] = '`amount` = ?';
        $where_params[] = $amount;
    }
}
if (!empty($_GET['time'])) {
    $time = trim($_GET['time']);
    $create_date_1 = str_replace('-', '/', $time);
    $create_date_1 = explode(' to ', $create_date_1);
    if (count($create_date_1) == 2 && $create_date_1[0] != $create_date_1[1]) {
        $start_date = $create_date_1[0] . ' 00:00:00';
        $end_date = $create_date_1[1] . ' 23:59:59';
        // Validate dates
        if (DateTime::createFromFormat('Y/m/d H:i:s', $start_date) && DateTime::createFromFormat('Y/m/d H:i:s', $end_date)) {
            $where_conditions[] = '`create_gettime` >= ? AND `create_gettime` <= ?';
            $where_params[] = $start_date;
            $where_params[] = $end_date;
        }
    }
}
if (isset($_GET['shortByDate'])) {
    $shortByDate = intval($_GET['shortByDate']);
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

// Xây dựng câu SQL an toàn
$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT * FROM `payment_crypto` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$params_with_limit = array_merge($where_params, [$from, $limit]);

$listDatatable = $CMSNT->get_list_safe($sql, $params_with_limit);

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM `payment_crypto` WHERE $where_clause";
$totalResult = $CMSNT->get_row_safe($count_sql, $where_params);
$totalDatatable = $totalResult ? $totalResult['total'] : 0;
$urlDatatable = pagination_client(base_url("?action=recharge-crypto&limit=$limit&shortByDate=$shortByDate&time=$time&trans_id=$trans_id&amount=$amount&status=$status&"), $from, $totalDatatable, $limit);
?>


<!-- Page Header with Breadcrumb -->
<div class="page-header-modern page-header-compact">
    <div class="container">
        <nav class="breadcrumb-modern">
            <a href="<?= base_url(); ?>"><i class="fa-solid fa-home"></i> <?= __('Trang chủ'); ?></a>
            <span class="separator">›</span>
            <span class="current"><?= __('Nạp tiền bằng Crypto'); ?></span>
        </nav>
        <h1 class="page-title-modern">
            <i class="fa-solid fa-coins"></i>
            <?= __('Nạp tiền bằng Crypto'); ?>
        </h1>
        <p class="page-subtitle-modern"><?= __('Nạp tiền nhanh chóng bằng USDT'); ?></p>
    </div>
</div>

<section class="py-4 inner-section profile-part">
    <div class="container">
        <div class="row">
            <?php
            $cryptoPromotions = parseCryptoPromotionsConfig($CMSNT->site('crypto_promotions'));
            ?>
            <?php if (!empty($cryptoPromotions)): ?>
                <div class="col-lg-12">
                    <div class="card-modern mb-3">
                        <div class="card-modern-header">
                            <h5><i class="fa-solid fa-percent me-2"></i><?= __('Khuyến mãi'); ?></h5>
                        </div>
                        <div class="card-modern-body">
                            <table class="table fs-sm mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col"><?= __('Số tiền nạp lớn hơn hoặc bằng'); ?></th>
                                        <th scope="col"><?= __('Khuyến mãi thêm'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cryptoPromotions as $promotion):
                                        $minFormatted = number_format($promotion['min'], 2, '.', ',');
                                        if (strpos($minFormatted, '.') !== false) {
                                            $minFormatted = rtrim(rtrim($minFormatted, '0'), '.');
                                        }
                                        $discountFormatted = rtrim(rtrim(number_format($promotion['discount'], 2, '.', ''), '0'), '.');
                                    ?>
                                        <tr>
                                            <td><b style="color: blue;"><?= $minFormatted; ?></b> <strong>USDT</strong></td>
                                            <td><b style="color: red;"><?= $discountFormatted; ?></b>%</td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif ?>
            <div class="col-md-7">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fa-solid fa-coins me-2"></i><?= __('Nạp tiền bằng Crypto'); ?></h5>
                    </div>
                    <div class="card-modern-body">
                        <!-- Logo USDT -->
                        <div class="crypto-logo-box">
                            <img src="<?= base_url('assets/img/usdttrc20.png'); ?>" alt="USDT TRC20" />
                        </div>

                        <input type="hidden" id="token" value="<?= $getUser['token']; ?>">
                        <input type="hidden" id="crypto_rate" value="<?= $CMSNT->site('crypto_rate'); ?>">

                        <!-- Input số tiền USDT -->
                        <div class="crypto-input-group">
                            <label class="crypto-input-label">
                                <i class="fa-solid fa-wallet"></i>
                                <?= __('Nhập số tiền USDT cần nạp'); ?>
                            </label>
                            <input
                                type="text"
                                class="crypto-input-field"
                                id="amount"
                                placeholder="<?= __('Ví dụ: 100'); ?>"
                                autocomplete="off">

                            <!-- Box hiển thị tỷ giá -->
                            <div class="crypto-rate-box">
                                <i class="fa-solid fa-exchange-alt"></i>
                                <div>
                                    <span class="crypto-rate-text"><?= __('Tỷ giá hôm nay'); ?>: </span>
                                    <span class="crypto-rate-value">1 USDT = <?= format_currency($CMSNT->site('crypto_rate')); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Hiển thị số tiền thực nhận -->
                        <div class="crypto-input-group">
                            <div class="crypto-received-box">
                                <div class="crypto-received-label">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                    <?= __('Số tiền bạn sẽ nhận được ước tính'); ?>
                                </div>
                                <div class="crypto-received-amount" id="received_amount">
                                    0đ
                                </div>
                            </div>

                            <!-- Info box -->
                            <div class="crypto-info-box">
                                <p class="crypto-info-text">
                                    <i class="fa-solid fa-info-circle"></i>
                                    <?= __('Số tiền sẽ được cộng vào tài khoản sau khi thanh toán thành công'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Button submit -->
                        <button type="button" class="crypto-submit-btn" id="CreateInvoiceCrypto">
                            <i class="fa-solid fa-paper-plane"></i>
                            <?= __('Tạo đơn nạp tiền'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fa-solid fa-triangle-exclamation me-2"></i><?= __('Lưu ý'); ?></h5>
                    </div>
                    <div class="card-modern-body">
                        <?= $CMSNT->site('crypto_note'); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fa-solid fa-clock-rotate-left me-2"></i><?= __('Lịch sử nạp Crypto'); ?></h5>
                    </div>
                    <div class="card-modern-body">
                        <form action="" method="GET">
                            <input type="hidden" name="action" value="recharge-crypto">
                            <div class="row">
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control col-sm-2 mb-2" value="<?= $trans_id; ?>" name="trans_id"
                                        placeholder="<?= __('Mã giao dịch'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input class="form-control col-sm-2 mb-2" value="<?= $amount; ?>" name="amount"
                                        placeholder="<?= __('Số lượng'); ?>">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <select class="form-select mb-2" name="status">
                                        <option value=""><?= __('Trạng thái'); ?></option>
                                        <option <?= $status == 'waiting' ? 'selected' : ''; ?> value="waiting">
                                            <?= __('Waiting'); ?></option>
                                        <option <?= $status == 'expired' ? 'selected' : ''; ?> value="expired">
                                            <?= __('Expired'); ?></option>
                                        <option <?= $status == 'completed' ? 'selected' : ''; ?> value="completed">
                                            <?= __('Completed'); ?></option>
                                    </select>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <input type="text" class="js-flatpickr form-control mb-2" id="example-flatpickr-range"
                                        name="time" placeholder="<?= __('Chọn thời gian cần tìm'); ?>" value="<?= $time; ?>"
                                        data-mode="range">
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <button class="shop-widget-btn mb-2"><i
                                            class="fas fa-search"></i><span><?= __('Tìm kiếm'); ?></span></button>
                                </div>
                                <div class="col-lg col-md-4 col-6">
                                    <a href="<?= base_url('?action=recharge-crypto'); ?>" class="shop-widget-btn mb-2"><i
                                            class="far fa-trash-alt"></i><span><?= __('Bỏ lọc'); ?></span></a>
                                </div>
                            </div>
                            <div class="top-filter">
                                <div class="filter-show"><label class="filter-label">Show :</label>
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
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1"><?= __('Hôm nay'); ?>
                                        </option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2"><?= __('Tuần này'); ?>
                                        </option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3"><?= __('Tháng này'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-scroll">
                            <table class="table fs-sm mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center"><?= __('Mã giao dịch'); ?></th>
                                        <th class="text-center"><?= __('Số lượng'); ?></th>
                                        <th class="text-center"><?= __('Thực nhận'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th><?= __('Thời gian tạo'); ?></th>
                                        <th><?= __('Cập nhật'); ?></th>
                                        <th class="text-center"><?= __('Thao tác'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $row2) { ?>
                                        <tr>
                                            <td class="text-center"><small><a target="_blank"
                                                        href="<?= $row2['url_payment']; ?>"><?= $row2['trans_id']; ?></a></small>
                                            </td>
                                            <td style="text-align: right;"><b><?= $row2['amount']; ?></b>
                                                <b style="color:green;">USDT</b>
                                            </td>
                                            <td style="text-align: right;"><b
                                                    style="color: red;"><?= format_currency($row2['received']); ?></b>
                                            </td>
                                            <td class="text-center"><?= display_invoice($row2['status']); ?></td>
                                            <td><?= $row2['create_gettime']; ?></td>
                                            <td><?= $row2['update_gettime']; ?></td>
                                            <td class="text-center fs-base">
                                                <a type="button" target="_blank" href="<?= $row2['url_payment']; ?>">
                                                    <?= __('Xem thêm'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7">
                                            <div class="float-right">
                                                <?php
                                                // Tính tổng đã thanh toán
                                                $completed_params = array_merge($where_params, ['completed']);
                                                $completed_where = $where_clause . ' AND `status` = ?';
                                                $completed_total = $CMSNT->get_row_safe("SELECT SUM(`received`) as total FROM `payment_crypto` WHERE $completed_where", $completed_params);

                                                // Tính tổng chưa thanh toán  
                                                $waiting_params = array_merge($where_params, ['waiting']);
                                                $waiting_where = $where_clause . ' AND `status` = ?';
                                                $waiting_total = $CMSNT->get_row_safe("SELECT SUM(`received`) as total FROM `payment_crypto` WHERE $waiting_where", $waiting_params);
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
    </div>
</section>



<?php
require_once(__DIR__ . '/footer.php');
?>


<script type="text/javascript">
    // Tính toán số tiền thực nhận khi nhập USDT qua AJAX
    var calculateTimeout;
    $("#amount").on("input", function() {
        var amount = $(this).val();

        // Clear timeout trước đó để tránh gọi AJAX liên tục
        clearTimeout(calculateTimeout);

        if (!amount || parseFloat(amount) <= 0) {
            $("#received_amount").html('0 VND');
            return;
        }

        // Hiển thị loading
        $("#received_amount").html('<i class="fa fa-spinner fa-spin"></i> <?= __('Đang tính...'); ?>');

        // Đợi 500ms sau khi user ngừng nhập mới gọi AJAX
        calculateTimeout = setTimeout(function() {
            $.ajax({
                url: "<?= base_url('ajaxs/client/view.php'); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'CalculateCryptoReceived',
                    amount: amount
                },
                success: function(response) {
                    if (response.status == 'success') {
                        $("#received_amount").html(response.received);
                    } else {
                        $("#received_amount").html('0 VND');
                    }
                },
                error: function() {
                    $("#received_amount").html('0 VND');
                }
            });
        }, 500);
    });

    $("#CreateInvoiceCrypto").on("click", function() {
        $('#CreateInvoiceCrypto').html('<i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?>').prop(
            'disabled',
            true);
        $.ajax({
            url: "<?= base_url('ajaxs/client/recharge.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: '<?= $CMSNT->site('crypto_type_api') == 'fpayment.net' ? 'RechargeCryptoNew' : 'RechargeCrypto'; ?>',
                token: $("#token").val(),
                amount: $("#amount").val()
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire({
                        title: '<?= __('Successful !'); ?>',
                        text: respone.msg,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '<i class="fa fa-external-link"></i> <?= __("Đến trang thanh toán"); ?>',
                        cancelButtonText: 'Đóng'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(respone.url, '_blank');
                        }
                    });
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }
                $('#CreateInvoiceCrypto').html('<i class="fa-solid fa-paper-plane"></i> <?= __('Tạo đơn nạp tiền'); ?>').prop('disabled', false);
            },
            error: function() {
                Swal.fire('<?= __('Failure!'); ?>', 'Không thể xử lý', 'error');
                $('#CreateInvoiceCrypto').html('<i class="fa-solid fa-paper-plane"></i> <?= __('Tạo đơn nạp tiền'); ?>').prop('disabled', false);
            }

        });
    });
</script>