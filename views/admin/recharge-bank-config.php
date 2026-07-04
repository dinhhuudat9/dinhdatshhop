<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Cấu hình ngân hàng'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
<link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">

';
$body['footer'] = '
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}


if (isset($_POST['SaveSettings'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Chức năng này không thể sử dụng vì đây là trang web demo') . '")){window.history.back().location.reload();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if ($checkKey['status'] != true) {
        die('<script type="text/javascript">if(!alert("' . $checkKey['msg'] . '")){window.history.back().location.reload();}</script>');
    }
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => __('Cấu hình nạp tiền Ngân Hàng')
    ]);
    foreach ($_POST as $key => $value) {
        $CMSNT->update("settings", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    /** NOTE ACTION */
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình nạp tiền Ngân Hàng'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
}
?>
<?php
if (isset($_POST['ThemNganHang'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không thể sử dụng chức năng này vì đây là trang web demo') . '")){window.history.back().location.reload();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if ($checkKey['status'] != true) {
        die('<script type="text/javascript">if(!alert("' . $checkKey['msg'] . '")){window.history.back().location.reload();}</script>');
    }

    $url_image = '';
    if (check_img('image') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $uploads_dir = 'assets/storage/images/bank/' . $rand . '.png';
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_image = 'assets/storage/images/bank/' . $rand . '.png';
        }
    }
    $isInsert = $CMSNT->insert("banks", [
        'image'         => $url_image,
        'short_name'    => check_string($_POST['short_name']),
        'accountNumber' => check_string($_POST['accountNumber']),
        'token' => check_string(removeSpaces($_POST['token'])),
        'password' => check_string($_POST['password']),
        'accountName'   => check_string($_POST['accountName']),
        'is_openapi'    => isset($_POST['is_openapi']) ? 1 : 0
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Thêm ngân hàng') . " (" . $_POST['short_name'] . " - " . $_POST['accountNumber'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Thêm ngân hàng') . " (" . $_POST['short_name'] . " - " . $_POST['accountNumber'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Thêm thành công!') . '")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thêm thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
}



?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= __('Cấu hình ngân hàng'); ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?= __('Nạp tiền'); ?></a></li>
                        <li class="breadcrumb-item"><a
                                href="<?= base_url_admin('recharge-bank'); ?>"><?= __('Ngân hàng'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= __('Cấu hình'); ?></li>
                    </ol>
                </nav>
            </div>
        </div>
        <?php if (time() - $CMSNT->site('check_time_cron_bank') >= 120): ?>
            <div class="alert alert-danger alert-dismissible fade show custom-alert-icon shadow-sm" role="alert">
                <svg class="svg-danger" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24"
                    width="1.5rem" fill="#000000">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path
                        d="M15.73 3H8.27L3 8.27v7.46L8.27 21h7.46L21 15.73V8.27L15.73 3zM12 17.3c-.72 0-1.3-.58-1.3-1.3 0-.72.58-1.3 1.3-1.3.72 0 1.3.58 1.3 1.3 0 .72-.58 1.3-1.3 1.3zm1-4.3h-2V7h2v6z" />
                </svg>
                <?= __('Vui lòng thực hiện'); ?> <b><a target="_blank" class="text-primary"
                        href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b>
                <?= __('liên kết'); ?>: <a class="text-primary"
                    href="<?= base_url('cron/bank.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank"><?= base_url('cron/bank.php?key=' . $CMSNT->site('key_cron_job')); ?></a>
                <?= __('1 phút 1 lần để hệ thống xử lý nạp tiền tự động.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="text-right">
                    <a class="btn btn-danger label-btn mb-3" href="<?= base_url_admin('recharge-bank'); ?>">
                        <i class="ri-arrow-go-back-line label-btn-icon me-2"></i> <?= __('QUAY LẠI'); ?>
                    </a>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?= __('DANH SÁCH NGÂN HÀNG'); ?>
                        </div>
                        <div class="d-flex">
                            <button data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i>
                                <?= __('Thêm ngân hàng'); ?></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="datatable-basic" class="table text-nowrap table-striped table-hover table-bordered"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?= __('Ngân hàng'); ?></th>
                                    <th><?= __('Số tài khoản'); ?></th>
                                    <th><?= __('Chủ tài khoản'); ?></th>
                                    <th><?= __('Trạng thái'); ?></th>
                                    <th><?= __('Thao tác'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0;
                                foreach ($CMSNT->get_list("SELECT * FROM `banks`  ") as $bank) { ?>
                                    <tr>
                                        <td><?= $i++; ?></td>
                                        <td><?= $bank['short_name']; ?></td>
                                        <td><?= $bank['accountNumber']; ?></td>
                                        <td><?= $bank['accountName']; ?></td>
                                        <td><?= display_status_product($bank['status']); ?></td>
                                        <td><a aria-label=""
                                                href="<?= base_url_admin('recharge-bank-edit&id=' . $bank['id']); ?>"
                                                style="color:white;" class="btn btn-info btn-sm btn-icon-left m-b-10"
                                                type="button">
                                                <i class="fas fa-edit mr-1"></i><span class=""> <?= __('Edit'); ?></span>
                                            </a>
                                            <button style="color:white;" onclick="RemoveRow('<?= $bank['id']; ?>')"
                                                class="btn btn-danger btn-sm btn-icon-left m-b-10" type="button">
                                                <i class="fas fa-trash mr-1"></i><span class=""> <?= __('Delete'); ?></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card custom-card">

                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="row">
                                <!-- Cấu hình chung -->
                                <div class="col-xl-12">
                                    <div class="card custom-card shadow-none border">
                                        <div class="card-header">
                                            <div class="card-title text-uppercase">
                                                <i class="ri-settings-3-line me-2"></i><?= __('Cấu hình chung'); ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-xl-4">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Trạng thái hệ thống'); ?>
                                                            <span class="text-danger">*</span></label>
                                                        <select class="form-select" name="bank_status">
                                                            <option
                                                                <?= $CMSNT->site('bank_status') == 0 ? 'selected' : ''; ?>
                                                                value="0">
                                                                <i class="ri-close-circle-line"></i>
                                                                <?= __('TẮT (OFF)'); ?>
                                                            </option>
                                                            <option
                                                                <?= $CMSNT->site('bank_status') == 1 ? 'selected' : ''; ?>
                                                                value="1">
                                                                <i class="ri-check-circle-line"></i>
                                                                <?= __('BẬT (ON)'); ?>
                                                            </option>
                                                        </select>
                                                        <div class="form-text text-muted">
                                                            <?= __('Bật/tắt chức năng nạp tiền qua ngân hàng'); ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Thời gian hết hạn hóa đơn'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i
                                                                    class="ri-time-line"></i></span>
                                                            <input type="number" class="form-control"
                                                                value="<?= $CMSNT->site('bank_expired_invoice'); ?>"
                                                                name="bank_expired_invoice" placeholder="900" min="300">
                                                            <span class="input-group-text"><?= __('giây'); ?></span>
                                                        </div>
                                                        <div class="form-text text-muted">
                                                            <?= __('Thời gian hết hạn hóa đơn (tối thiểu 300 giây)'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Tỷ giá quy đổi tiền tệ cho QRCODE'); ?>
                                                            <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control"
                                                            value="<?= $CMSNT->site('bank_rate'); ?>" name="bank_rate"
                                                            placeholder="1" min="0">
                                                        <div class="form-text text-muted">
                                                            <?= __('Không thay đổi nếu website sử dụng tiền tệ mặc định là VND'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cấu hình giới hạn -->
                                <div class="col-xl-12">
                                    <div class="card custom-card shadow-none border">
                                        <div class="card-header">
                                            <div class="card-title text-uppercase">
                                                <i class="ri-coins-line me-2"></i><?= __('Giới hạn số tiền'); ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-xl-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Số tiền nạp tối thiểu'); ?>
                                                            <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text text-success"><i
                                                                    class="ri-money-dollar-circle-line"></i></span>
                                                            <input type="number" class="form-control"
                                                                value="<?= $CMSNT->site('bank_min'); ?>" name="bank_min"
                                                                placeholder="0" min="0">
                                                            <span class="input-group-text"><?= getCurrency(); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-6">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Số tiền nạp tối đa'); ?>
                                                            <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text text-warning"><i
                                                                    class="ri-money-dollar-circle-line"></i></span>
                                                            <input type="number" class="form-control"
                                                                value="<?= $CMSNT->site('bank_max'); ?>" name="bank_max"
                                                                placeholder="0" min="0">
                                                            <span class="input-group-text"><?= getCurrency(); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cấu hình Random -->
                                <div class="col-xl-12">
                                    <div class="card custom-card shadow-none border">
                                        <div class="card-header">
                                            <div class="card-title text-uppercase">
                                                <i class="ri-shuffle-line me-2"></i><?= __('Cấu hình Random'); ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-xl-4">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Loại nội dung Random'); ?></label>
                                                        <select class="form-select" name="random_content">
                                                            <option value="string"
                                                                <?= $CMSNT->site('random_content') == 'string' ? 'selected' : ''; ?>>
                                                                <?= __('Chuỗi ký tự (ABC...)'); ?>
                                                            </option>
                                                            <option value="string_number"
                                                                <?= $CMSNT->site('random_content') == 'string_number' ? 'selected' : ''; ?>>
                                                                <?= __('Chuỗi ký tự + số (ABC123...)'); ?>
                                                            </option>
                                                            <option value="number"
                                                                <?= $CMSNT->site('random_content') == 'number' ? 'selected' : ''; ?>>
                                                                <?= __('Chỉ số (123456...)'); ?>
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Số ký tự Random'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i
                                                                    class="ri-text"></i></span>
                                                            <input type="number" class="form-control"
                                                                value="<?= $CMSNT->site('bank_random_length'); ?>"
                                                                name="bank_random_length" placeholder="6" min="6"
                                                                max="20">
                                                        </div>
                                                        <div class="form-text text-muted">
                                                            <?= __('Tối thiểu 6 ký tự, tối đa 20 ký tự'); ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-4">
                                                    <div class="mb-3">
                                                        <label
                                                            class="form-label fw-semibold"><?= __('Prefix nội dung'); ?></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i
                                                                    class="ri-hashtag"></i></span>
                                                            <input type="text" class="form-control"
                                                                value="<?= $CMSNT->site('prefix_autobank'); ?>"
                                                                name="prefix_autobank"
                                                                placeholder="<?= __('Nhập prefix (không bắt buộc)'); ?>">
                                                        </div>
                                                        <div class="form-text text-muted">
                                                            <?= __('Prefix sẽ được thêm vào đầu nội dung nạp tiền'); ?>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Khuyến mãi nạp tiền -->
                                <div class="col-xl-12">
                                    <div class="card custom-card shadow-none border">
                                        <div class="card-header">
                                            <div class="card-title text-uppercase">
                                                <i class="ri-percent-line me-2"></i><?= __('Khuyến mãi nạp tiền'); ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= __('Khuyến mãi nạp tiền nếu có'); ?></label>
                                                <textarea class="form-control" rows="5"
                                                    placeholder="100000|5 (tức nạp 100,000đ khuyến mãi 5%)
200000|10 (tức nạp 200,000đ khuyến mãi 10%)"
                                                    name="bank_promotions"><?= $CMSNT->site('bank_promotions'); ?></textarea>
                                                <div class="form-text text-muted">
                                                    <?= __('Nhập mốc nạp khuyến mãi nạp tiền nếu có, mỗi dòng là một mốc nạp.'); ?>
                                                    <br><strong><?= __('Định dạng: SỐ_TIỀN|PHẦN_TRĂM_KHUYẾN_MÃI'); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cấu hình Webhook (Web2M) - Collapsible -->
                                <div class="col-xl-12">
                                    <div class="card custom-card shadow-none border">
                                        <div class="card-header" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#webhookCollapse" aria-expanded="false" aria-controls="webhookCollapse">
                                            <div class="card-title text-uppercase d-flex align-items-center justify-content-between w-100">
                                                <div>
                                                    <i class="ri-webhook-line me-2"></i><?= __('Webhook (Tùy chọn)'); ?>
                                                    <span class="badge bg-info-transparent ms-2"><?= __('Không bắt buộc'); ?></span>
                                                </div>
                                                <i class="ri-arrow-down-s-line fs-18 webhook-arrow"></i>
                                            </div>
                                        </div>
                                        <div class="collapse" id="webhookCollapse">
                                            <div class="card-body">
                                                <div class="alert alert-info-transparent mb-3">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ri-information-line fs-16 me-2 mt-1"></i>
                                                        <div>
                                                            <strong><?= __('Webhook là gì?'); ?></strong><br>
                                                            <?= __('Webhook cho phép nhận giao dịch ngân hàng realtime từ Web2M thay vì sử dụng Cron Job. Nếu bạn đang sử dụng dịch vụ Web2M có hỗ trợ Webhook, hãy cấu hình phần này.'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-xl-12">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold"><?= __('Webhook URL'); ?></label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="ri-link"></i></span>
                                                                <input type="text" class="form-control"
                                                                    value="<?= base_url('api/webhook_bank.php'); ?>"
                                                                    readonly id="webhook_url">
                                                                <button type="button" class="btn btn-outline-primary" onclick="copyWebhookUrl()">
                                                                    <i class="ri-file-copy-line"></i> <?= __('Copy'); ?>
                                                                </button>
                                                            </div>
                                                            <div class="form-text text-muted">
                                                                <?= __('Sao chép URL này và đăng ký tại Web2M để nhận giao dịch realtime'); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-12">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold"><?= __('Access Token'); ?></label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="ri-key-2-line"></i></span>
                                                                <input type="text" class="form-control"
                                                                    value="<?= $CMSNT->site('token_webhook_web2m'); ?>"
                                                                    name="token_webhook_web2m"
                                                                    placeholder="<?= __('Nhập Access Token từ Web2M (để trống nếu không sử dụng)'); ?>">
                                                            </div>
                                                            <div class="form-text text-muted">
                                                                <?= __('Lấy Access Token từ trang quản lý Web2M và dán vào đây. Để trống nếu không sử dụng Webhook.'); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lưu ý nạp tiền -->
                                <div class="col-xl-12">
                                    <div class="card custom-card shadow-none border">
                                        <div class="card-header">
                                            <div class="card-title text-uppercase">
                                                <i class="ri-information-line me-2"></i><?= __('Lưu ý cho khách hàng'); ?>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= __('Nội dung lưu ý'); ?></label>
                                                <textarea id="bank_notice" name="bank_notice" rows="8"
                                                    class="form-control"
                                                    placeholder="<?= __('Nhập nội dung lưu ý cho khách hàng khi nạp tiền...'); ?>"><?= $CMSNT->site('bank_notice'); ?></textarea>
                                                <div class="form-text text-muted">
                                                    <?= __('Nội dung này sẽ hiển thị cho khách hàng khi thực hiện nạp tiền'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a type="button" class="btn btn-light" href="">
                                    <i class="ri-refresh-line me-1"></i><?= __('Tải lại'); ?>
                                </a>
                                <button type="submit" name="SaveSettings" class="btn btn-primary">
                                    <i class="ri-save-line me-1"></i><?= __('Lưu cấu hình'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModalScrollable2" tabindex="-1" aria-labelledby="exampleModalScrollable2"
        data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="staticBackdropLabel2"><?= __('Thêm ngân hàng mới'); ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Ngân hàng'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" list="options" name="short_name"
                                    placeholder="<?= __('Nhập tên ngân hàng'); ?>" required>
                                <datalist id="options">
                                    <?php foreach ($config_listbank as $value): ?>
                                        <option value="<?= $value['shortName']; ?>"><?= $value['name']; ?></option>
                                    <?php endforeach ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Image'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" name="image" required>
                                <small><?= __('Khi VietQR không hoạt động, hệ thống sẽ hiện ảnh này thay cho mã QR'); ?></small>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Số tài khoản'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="accountNumber"
                                    placeholder="<?= __('Nhập số tài khoản'); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"><?= __('Chủ tài khoản'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="accountName"
                                    placeholder="<?= __('Nhập tên chủ tài khoản'); ?>" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label"
                                for="example-hf-email"><?= __('Password Internet Banking'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="password"
                                    placeholder="<?= __('Áp dụng khi cấu hình nạp tiền tự động.'); ?>">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <label class="col-sm-4 col-form-label" for="example-hf-email"><?= __('Token'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control" name="token"
                                    placeholder="<?= __('Áp dụng khi cấu hình nạp tiền tự động.'); ?>">
                            </div>
                        </div>
                        <!-- OpenAPI Checkbox - chỉ hiển thị khi ngân hàng hỗ trợ -->
                        <div class="row mb-4" id="openapi_row_add" style="display:none;">
                            <label class="col-sm-4 col-form-label"><?= __('Sử dụng OpenAPI'); ?></label>
                            <div class="col-sm-8">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_openapi" id="is_openapi_add" value="1">
                                    <label class="form-check-label" for="is_openapi_add">
                                        <?= __('Ngân hàng này có sử dụng OpenAPI để kiểm tra giao dịch'); ?>
                                    </label>
                                </div>
                                <small class="text-muted"><?= __('Chỉ bật nếu bạn đã tích hợp OpenAPI từ ngân hàng này.'); ?></small>
                            </div>
                        </div>
                        <small><?= __('Hướng dẫn tích hợp tự động nạp tiền bằng Ngân Hàng tại'); ?> <a target="_blank"
                                class="text-primary"
                                href="https://help.cmsnt.co/huong-dan/huong-dan-tich-hop-nap-tien-bang-ngan-hang-vn-tu-dong/"><?= __('đây'); ?></a></small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm"
                            data-bs-dismiss="modal"><?= __('Close'); ?></button>
                        <button type="submit" name="ThemNganHang" class="btn btn-primary btn-sm"><i
                                class="fa fa-fw fa-plus me-1"></i>
                            <?= __('Thêm'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>
<script>
    CKEDITOR.replace("bank_notice");
</script>


<script type="text/javascript">
    function RemoveRow(id) {
        cuteAlert({
            type: "question",
            title: "<?= __('Xác nhận xóa'); ?>",
            message: "<?= __('Bạn có chắc chắn muốn xóa ID'); ?> " + id + " ?",
            confirmText: "<?= __('Đồng ý'); ?>",
            cancelText: "<?= __('Đóng'); ?>"
        }).then((e) => {
            if (e) {
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'removeBank',
                        id: id
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, result.status);
                            location.reload();
                        } else {
                            showMessage(result.msg, result.status);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        })
    }
</script>

<script>
    $('#datatable-basic').DataTable({
        language: {
            searchPlaceholder: '<?= __('Tìm kiếm'); ?>...',
            sSearch: '',
        },
        "pageLength": 10,
        scrollX: true
    });

    // OpenAPI supported banks (case-insensitive)
    const openapiBanks = ['vietcombank', 'vcb', 'mb', 'mbbank', 'tpbank', 'techcombank', 'tcb', 'acb', 'bidv', 'seabank', 'vietinbank'];

    function checkOpenAPISupport(bankName, checkboxRowId) {
        const bankLower = bankName.toLowerCase().trim();
        const isSupported = openapiBanks.some(bank => bankLower === bank || bankLower.includes(bank));
        const row = document.getElementById(checkboxRowId);
        if (row) {
            row.style.display = isSupported ? 'flex' : 'none';
        }
    }

    // Listen for bank name changes in add modal
    document.querySelector('input[name="short_name"]').addEventListener('input', function() {
        checkOpenAPISupport(this.value, 'openapi_row_add');
    });

    // Copy Webhook URL to clipboard
    function copyWebhookUrl() {
        var webhookUrl = document.getElementById('webhook_url');
        webhookUrl.select();
        webhookUrl.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(webhookUrl.value).then(function() {
            showMessage('<?= __('Đã sao chép Webhook URL!'); ?>', 'success');
        }).catch(function() {
            document.execCommand('copy');
            showMessage('<?= __('Đã sao chép Webhook URL!'); ?>', 'success');
        });
    }

    // Rotate arrow on webhook collapse toggle
    var webhookCollapse = document.getElementById('webhookCollapse');
    if (webhookCollapse) {
        webhookCollapse.addEventListener('show.bs.collapse', function() {
            document.querySelector('.webhook-arrow').style.transform = 'rotate(180deg)';
        });
        webhookCollapse.addEventListener('hide.bs.collapse', function() {
            document.querySelector('.webhook-arrow').style.transform = 'rotate(0deg)';
        });
    }
</script>