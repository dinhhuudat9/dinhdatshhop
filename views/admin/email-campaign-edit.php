<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Edit Campaign'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '';
$body['footer'] = '';

require_once(__DIR__ . '/../../models/is_admin.php');

// Validate và lấy thông tin chiến dịch
if (!isset($_GET['id'])) {
    redirect(base_url_admin('email-campaigns'));
}

$id = validate_int($_GET['id'], 1);
if ($id === false) {
    redirect(base_url_admin('email-campaigns'));
}

$row = $CMSNT->get_row_safe("SELECT * FROM `email_campaigns` WHERE `id` = ?", [$id]);
if (!$row) {
    redirect(base_url_admin('email-campaigns'));
}

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');

if (checkPermission($getUser['admin'], 'edit_email_campaigns') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}

if (isset($_POST['submit'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }

    if (checkPermission($getUser['admin'], 'edit_email_campaigns') != true) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
    }

    // Validate và sanitize input
    $name = validate_string($_POST['name'], 255, 1);
    $subject = validate_string($_POST['subject'], 500, 1);
    $cc = !empty($_POST['cc']) ? validate_email($_POST['cc']) : null;
    $bcc = !empty($_POST['bcc']) ? validate_email($_POST['bcc']) : null;
    $content = $_POST['content']; // HTML content

    if ($name === false || $subject === false) {
        die('<script type="text/javascript">if(!alert("' . __('Dữ liệu không hợp lệ') . '")){window.history.back();}</script>');
    }

    // Cập nhật chiến dịch
    $isUpdate = $CMSNT->update('email_campaigns', [
        'name'              => $name,
        'subject'           => $subject,
        'cc'                => $cc ?: null,
        'bcc'               => $bcc ?: null,
        'content'           => $content,
        'update_gettime'    => gettime()
    ], "`id` = ?", [$row['id']]);

    if ($isUpdate) {
        // Log hành động
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => __('Chỉnh sửa chiến dịch Email Marketing') . " ({$name})"
        ]);

        // Gửi thông báo admin
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Chỉnh sửa chiến dịch Email Marketing') . " ({$name})", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die('<script type="text/javascript">if(!alert("' . __('Thành công!') . '")){location.href = "";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
}

// Lấy thống kê
$totalRecipients = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ?", [$row['id']]);
$sentCount = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 1", [$row['id']]);
$failedCount = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 2", [$row['id']]);
$pendingCount = $CMSNT->num_rows_safe("SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 0", [$row['id']]);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?= __('Chỉnh sửa chiến dịch'); ?>: <?= htmlspecialchars($row['name']); ?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url_admin('email-campaigns'); ?>"><?= __('Email Campaigns'); ?></a></li>
                        <li class="breadcrumb-item active"><?= __('Chỉnh sửa'); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1"><?= format_cash($totalRecipients); ?></h3>
                        <small><?= __('Tổng người nhận'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1 text-success"><?= format_cash($sentCount); ?></h3>
                        <small><?= __('Đã gửi'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1 text-danger"><?= format_cash($failedCount); ?></h3>
                        <small><?= __('Thất bại'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning-transparent">
                    <div class="card-body text-center">
                        <h3 class="mb-1 text-warning"><?= format_cash($pendingCount); ?></h3>
                        <small><?= __('Đang chờ'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?= __('CHỈNH SỬA CHIẾN DỊCH'); ?>
                        </div>
                        <div>
                            <span class="badge bg-<?= $row['status'] == 0 ? 'primary' : ($row['status'] == 1 ? 'success' : 'danger'); ?>">
                                <?= $row['status'] == 0 ? __('Đang chạy') : ($row['status'] == 1 ? __('Hoàn thành') : __('Đã hủy')); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <?php echo csrfField(); ?>

                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?= __('Tên chiến dịch'); ?> <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input class="form-control" value="<?= htmlspecialchars($row['name']); ?>" type="text" placeholder="<?= __('Nhập tên cho chiến dịch'); ?>" name="name" required maxlength="255">
                                </div>
                            </div>

                            <hr>

                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?= __('Tiêu đề Mail'); ?> <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input class="form-control" value="<?= htmlspecialchars($row['subject']); ?>" type="text" name="subject" required maxlength="500">
                                    <small class="text-muted"><?= __('Có thể dùng: {username}, {email}, {domain}, {title}'); ?></small>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?= __('CC'); ?></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="email" value="<?= htmlspecialchars($row['cc'] ?? ''); ?>" name="cc">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label class="col-sm-3 col-form-label"><?= __('BCC'); ?></label>
                                <div class="col-sm-9">
                                    <input class="form-control" type="email" value="<?= htmlspecialchars($row['bcc'] ?? ''); ?>" name="bcc">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <label class="col-sm-12 col-form-label"><?= __('Nội dung Email'); ?> <span class="text-danger">*</span></label>
                                <div class="col-sm-12">
                                    <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($row['content'] ?? ''); ?></textarea>
                                    <small class="text-muted"><?= __('Có thể dùng: {username}, {email}, {domain}, {title}, {time}, {year}'); ?></small>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a class="btn btn-danger" href="<?= base_url_admin('email-campaigns'); ?>">
                                    <i class="fa fa-fw fa-undo me-1"></i> <?= __('Quay lại'); ?>
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fa fa-fw fa-save me-1"></i> <?= __('Lưu thay đổi'); ?>
                                </button>
                                <a class="btn btn-info" href="<?= base_url_admin('email-sending-view&id=' . $row['id']); ?>">
                                    <i class="fa fa-fw fa-eye me-1"></i> <?= __('Xem báo cáo'); ?>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/footer.php'); ?>

<script>
    CKEDITOR.replace("content");
</script>

</script>