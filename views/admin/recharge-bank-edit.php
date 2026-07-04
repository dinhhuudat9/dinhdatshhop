<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa ngân hàng'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '
 

 
';
$body['footer'] = '
 
';
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $CMSNT = new DB();
    $id = check_string($_GET['id']);
    $row = $CMSNT->get_row("SELECT * FROM `banks` WHERE `id` = '$id' ");
    if (!$row) {
        redirect(base_url_admin('bank-list'));
    }
} else {
    redirect(base_url_admin('bank-list'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
if (checkPermission($getUser['admin'], 'edit_recharge') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['LuuNganHang'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Chức năng này không thể sử dụng vì đây là trang web demo') . '")){window.history.back().location.reload();}</script>');
    }
    $checkKey = checkLicenseKey($CMSNT->site('license_key'));
    if ($checkKey['status'] != true) {
        die('<script type="text/javascript">if(!alert("' . $checkKey['msg'] . '")){window.history.back().location.reload();}</script>');
    }
    if (check_img('image') == true) {
        unlink($row['image']);
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 3);
        $uploads_dir = 'assets/storage/images/bank/' . $rand . '.png';
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update('banks', [
                'image'  => 'assets/storage/images/bank/' . $rand . '.png'
            ], " `id` = '$id' ");
        }
    }
    $isUpdate = $CMSNT->update("banks", [
        'short_name' => check_string($_POST['short_name']),
        'accountNumber' => check_string($_POST['accountNumber']),
        'status' => check_string($_POST['status']),
        'token' => check_string(removeSpaces($_POST['token'])),
        'password' => check_string($_POST['password']),
        'accountName' => check_string($_POST['accountName']),
        'is_openapi' => isset($_POST['is_openapi']) ? 1 : 0
    ], " `id` = '$id' ");
    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Cập nhật thông tin ngân hàng') . " (" . $_POST['short_name'] . " - " . $_POST['accountNumber'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Cập nhật thông tin ngân hàng') . " (" . $_POST['short_name'] . " - " . $_POST['accountNumber'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Lưu thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= __('Chỉnh sửa ngân hàng') . ' ' . $row['short_name']; ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?= __('Nạp tiền'); ?></a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url_admin('recharge-bank-config'); ?>"><?= __('Ngân hàng'); ?></a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page"><?= __('Chỉnh sửa ngân hàng'); ?>
                            <?= $row['short_name']; ?></li>
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
                <?= __('Vui lòng thực hiện'); ?> <b><a target="_blank" class="text-primary" href="https://help.cmsnt.co/huong-dan/huong-dan-xu-ly-khi-website-bao-loi-cron/">CRON JOB</a></b> <?= __('liên kết'); ?>: <a class="text-primary" href="<?= base_url('cron/bank.php?key=' . $CMSNT->site('key_cron_job')); ?>"
                    target="_blank"><?= base_url('cron/bank.php?key=' . $CMSNT->site('key_cron_job')); ?></a> <?= __('1 phút 1 lần để hệ thống xử lý nạp tiền tự động.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i
                        class="bi bi-x"></i></button>
            </div>
        <?php endif ?>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?= __('CHỈNH SỬA NGÂN HÀNG'); ?>
                        </div>
                        <div class="d-flex">
                            <button data-bs-toggle="modal" data-bs-target="#exampleModalScrollable2"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> <?= __('Thêm ngân hàng'); ?></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?= __('Tên ngân hàng'); ?> <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" value="<?= $row['short_name']; ?>" list="options"
                                    name="short_name" placeholder="<?= __('Nhập tên ngân hàng'); ?>" required autocomplete="off">
                                <datalist id="options">
                                    <?php foreach ($config_listbank as $value): ?>
                                        <option value="<?= $value['shortName']; ?>"><?= $value['name']; ?></option>
                                    <?php endforeach ?>
                                </datalist>
                            </div>
                            <div class="row">
                                <div class="col-8">
                                    <div class="mb-4">
                                        <label for="exampleInputFile"><?= __('Hình ảnh'); ?></label>
                                        <input type="file" class="form-control" name="image">
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group">
                                        <img width="200px" src="<?= BASE_URL($row['image']); ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?= __('Số tài khoản'); ?></label>
                                <input type="text" class="form-control" name="accountNumber"
                                    value="<?= $row['accountNumber']; ?>" placeholder="<?= __('Nhập số tài khoản'); ?>" required autocomplete="off">
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?= __('Tên chủ tài khoản'); ?></label>
                                <input type="text" class="form-control" name="accountName"
                                    value="<?= $row['accountName']; ?>" placeholder="<?= __('Nhập tên chủ tài khoản'); ?>" required autocomplete="off">
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?= __('Trạng thái'); ?></label>
                                <select class="form-control" name="status">
                                    <option <?= $row['status'] == 1 ? 'selected' : ''; ?> value="1"><?= __('ON'); ?></option>
                                    <option <?= $row['status'] == 0 ? 'selected' : ''; ?> value="0"><?= __('OFF'); ?></option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?= __('Mật khẩu Internet Banking'); ?></label>
                                <input type="text" class="form-control" name="password" value="<?= $row['password']; ?>"
                                    placeholder="<?= __('Áp dụng khi cấu hình nạp tiền tự động.'); ?>" autocomplete="off">
                            </div>
                            <div class="mb-4">
                                <label for="exampleInputEmail1"><?= __('Token'); ?></label>
                                <input type="text" class="form-control" name="token" value="<?= $row['token']; ?>"
                                    placeholder="<?= __('Áp dụng khi cấu hình nạp tiền tự động.'); ?>" autocomplete="off">
                            </div>
                            <!-- OpenAPI Checkbox - chỉ hiển thị khi ngân hàng hỗ trợ và status = 1 -->
                            <?php
                            $openapi_banks = ['vietcombank', 'vcb', 'mb', 'mbbank', 'tpbank', 'techcombank', 'tcb', 'acb', 'bidv', 'seabank', 'vietinbank'];
                            $bank_name_lower = strtolower($row['short_name']);
                            $is_openapi_supported = false;
                            foreach ($openapi_banks as $bank) {
                                if ($bank_name_lower === $bank || strpos($bank_name_lower, $bank) !== false) {
                                    $is_openapi_supported = true;
                                    break;
                                }
                            }
                            ?>
                            <div class="mb-4" id="openapi_row_edit" style="display: <?= ($row['status'] == 1 && $is_openapi_supported) ? 'block' : 'none'; ?>;">
                                <label><?= __('Sử dụng OpenAPI'); ?></label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_openapi" id="is_openapi_edit" value="1" <?= isset($row['is_openapi']) && $row['is_openapi'] == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_openapi_edit">
                                        <?= __('Ngân hàng này có sử dụng OpenAPI để kiểm tra giao dịch'); ?>
                                    </label>
                                </div>
                                <small class="text-muted"><?= __('Chỉ bật nếu bạn đã tích hợp OpenAPI từ ngân hàng này.'); ?></small>
                            </div>


                            <a type="button" class="btn btn-hero btn-danger"
                                href="<?= base_url_admin('recharge-bank-config'); ?>"><i
                                    class="fa fa-fw fa-undo me-1"></i>
                                <?= __('Quay lại'); ?></a>
                            <button type="submit" name="LuuNganHang" class="btn btn-hero btn-success"><i
                                    class="fa fa-fw fa-save me-1"></i> <?= __('Lưu'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    // OpenAPI supported banks (case-insensitive)
    const openapiBanks = ['vietcombank', 'vcb', 'mb', 'mbbank', 'tpbank', 'techcombank', 'tcb', 'acb', 'bidv', 'seabank', 'vietinbank'];

    function checkOpenAPISupportEdit() {
        const bankName = document.querySelector('input[name="short_name"]').value.toLowerCase().trim();
        const status = document.querySelector('select[name="status"]').value;
        const isSupported = openapiBanks.some(bank => bankName === bank || bankName.includes(bank));
        const row = document.getElementById('openapi_row_edit');
        if (row) {
            row.style.display = (status === '1' && isSupported) ? 'block' : 'none';
        }
    }

    // Listen for changes
    document.querySelector('input[name="short_name"]').addEventListener('input', checkOpenAPISupportEdit);
    document.querySelector('select[name="status"]').addEventListener('change', checkOpenAPISupportEdit);
</script>