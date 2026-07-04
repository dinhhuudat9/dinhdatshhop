<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa thành viên'),
    'desc'   => 'CMSNT Panel',
    'keyword' => 'cmsnt, CMSNT, cmsnt.co,'
];
$body['header'] = '

';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
if (checkPermission($getUser['admin'], 'edit_user') != true) {
    die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền sử dụng tính năng này') . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_GET['id'])) {
    $CMSNT = new DB();
    $id = validate_int($_GET['id'], 1);
    if ($id === false) {
        die(__('ID user không hợp lệ'));
    }
    $user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$id]);
    if (!$user) {
        die(__('ID user không tồn tại trong hệ thống'));
    }
    if ($getUser['admin'] != 99999 && $user['admin'] == 99999) {
        die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền chỉnh sửa Admin cấp cao hơn bạn') . '")){window.history.back();}</script>');
    }
    // submit form edit
    if (isset($_POST['email'])) {
        // Kiểm tra CSRF token
        checkCSRF();

        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
        }
        if (check_string($_POST['discount']) > 100) {
            die('<script type="text/javascript">if(!alert("' . __('Chiết khấu giảm giá không được lớn hơn 100') . '")){window.history.back().location.reload();}</script>');
        }
        if (check_string($_POST['admin']) != $user['admin']) {
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Thay đổi quyền Admin cho thành viên %s[%s] từ %s -> %s.'), $user['username'], $user['id'], $user['admin'], check_string($_POST['admin']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => sprintf(__("Bạn được Admin %s thay đổi quyền Admin."), $getUser['username'])
            ]);
        }
        if ($_POST['discount'] != $user['discount']) {
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Thay đổi chiết khấu thành viên %s từ %s%% sang %s%%.'), $user['username'], $user['discount'], check_string($_POST['discount']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => 'Bạn được Admin ' . $getUser['username'] . ' thay đổi chiết khấu.'
            ]);
        }
        if ($_POST['username'] != $user['username']) {
            if ($CMSNT->get_row_safe("SELECT * FROM `users` WHERE `username` = ? AND `id` != ?", [validate_string($_POST['username'], 100), $user['id']])) {
                die('<script type="text/javascript">if(!alert("' . __('Tên đăng nhập này đã có người sử dụng') . '")){window.history.back().location.reload();}</script>');
            }
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Change Username %s[%s] | %s -> %s.'), $user['username'], $user['id'], $user['username'], check_string($_POST['username']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => __('Bạn được Admin thay đổi Username.')
            ]);
        }
        if ($_POST['email'] != $user['email']) {
            if ($CMSNT->get_row_safe("SELECT * FROM `users` WHERE `email` = ? AND `id` != ?", [validate_email($_POST['email']), $user['id']])) {
                die('<script type="text/javascript">if(!alert("' . __('Địa chỉ Email này đã có người sử dụng') . '")){window.history.back().location.reload();}</script>');
            }
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Change Email %s[%s] | %s -> %s.'), $user['username'], $user['id'], $user['email'], check_string($_POST['email']))
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => __('Bạn được Admin thay đổi Email.')
            ]);
        }
        if ($_POST['admin'] == 99999 && $user['admin'] != 99999) {
            die('<script type="text/javascript">if(!alert("' . __('Bạn không có quyền chỉnh sửa Admin cấp cao hơn bạn') . '")){window.history.back().location.reload();}</script>');
        }
        $DBUser = new users();
        $isUpdate = $DBUser->update_by_id([
            'username'  => check_string($_POST['username']),
            'email'     => check_string($_POST['email']),
            'status_2fa'    => check_string($_POST['status_2fa']),
            'token'     => check_string($_POST['token']),
            'api_key'   => check_string($_POST['api_key']),
            'phone'     => check_string($_POST['phone']),
            'gender'    => check_string($_POST['gender']),
            'admin'     => check_string($_POST['admin']),
            'discount'  => check_string($_POST['discount']),
            'banned'    => check_string($_POST['banned']),
            'ref_id'    => check_string($_POST['ref_id'])
        ], $user['id']);
        if ($isUpdate) {
            if (!empty($_POST['password'])) {
                $DBUser->update_by_id([
                    'password' => TypePassword(check_string($_POST['password']))
                ], $user['id']);
            }
            $CMSNT->insert("logs", [
                'user_id'       => $getUser['id'],
                'createdate'    => gettime(),
                'device'        => getUserAgent(),
                'ip'            => myip(),
                'action'        => sprintf(__('[Admin] Cập nhật thông tin thành viên %s[%s].'), $user['username'], $user['id'])
            ]);
            $CMSNT->insert("logs", [
                'user_id'       => $user['id'],
                'createdate'    => gettime(),
                'action'        => __('You are changed information by Admin.')
            ]);
            /** NOTE ACTION */
            $my_text = $CMSNT->site('noti_action');
            $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
            $my_text = str_replace('{username}', $getUser['username'], $my_text);
            $my_text = str_replace('{action}', sprintf(__('Cập nhật thông tin thành viên %s[%s].'), $user['username'], $user['id']), $my_text);
            $my_text = str_replace('{ip}', myip(), $my_text);
            $my_text = str_replace('{time}', gettime(), $my_text);
            sendMessAdmin($my_text);
            die('<script type="text/javascript">if(!alert("' . __('Cập nhật thông tin thành công!') . '")){window.history.back().location.reload();}</script>');
        }
    }
    if (isset($_POST['cong_tien'])) {
        // Kiểm tra CSRF token
        checkCSRF();

        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
        }
        if ($_POST['amount'] <= 0) {
            die('<script type="text/javascript">if(!alert("' . __('Amount không hợp lệ !') . '")){window.history.back().location.reload();}</script>');
        }

        $amount = validate_float($_POST['amount'], 0.01);
        if ($amount === false) {
            die('<script type="text/javascript">if(!alert("' . __('Số tiền không hợp lệ!') . '")){window.history.back().location.reload();}</script>');
        }
        $DBUser = new users();


        // CỘNG TIỀN VÀO VÍ CHÍNH
        if ($_POST['wallet'] == 1) {
            $reason = '[' . __('CỘNG TIỀN VÀO VÍ CHÍNH') . '] ' . check_string($_POST['reason']);
            $DBUser->AddCredits($id, $amount, $reason, 'admin_add_' . uniqid());
        }
        // HOÀN TIỀN VÀO VÍ CHÍNH
        if ($_POST['wallet'] == 2) {
            $reason = '[' . __('HOÀN TIỀN') . '] ' . check_string($_POST['reason']);
            $DBUser->RefundCredits($id, $amount, $reason, 'admin_refund_' . uniqid());
        }
        // CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI
        if ($_POST['wallet'] == 3) {
            $reason = '[' . __('CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI') . '] ' . check_string($_POST['reason']);
            $isDebit = $CMSNT->cong('users', 'debit', $amount, "`id` = ?", [$id]);
            if ($isDebit) {
                $DBUser->AddCredits($id, $amount, $reason, 'admin_add_' . uniqid());
            }
        }
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'createdate'    => gettime(),
            'device'        => getUserAgent(),
            'ip'            => myip(),
            'action'        => sprintf(__('[Admin] Cộng %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason)
        ]);


        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('[Admin] Cộng %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        admin_msg_success(__('Cộng số dư thành công!'), "", 1000);
    }

    if (isset($_POST['tru_tien'])) {
        // Kiểm tra CSRF token
        checkCSRF();

        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
        }
        if ($_POST['amount'] <= 0) {
            die('<script type="text/javascript">if(!alert("' . __('Amount không hợp lệ !') . '")){window.history.back().location.reload();}</script>');
        }

        $amount = validate_float($_POST['amount'], 0.01);
        if ($amount === false) {
            die('<script type="text/javascript">if(!alert("' . __('Số tiền không hợp lệ!') . '")){window.history.back().location.reload();}</script>');
        }

        if ($_POST['wallet'] == 2) {
            $CMSNT->tru('users', 'debit', $amount, "`id` = ?", [$id]);
            $reason = '[VÍ GHI NỢ] ' . check_string($_POST['reason']);
        } else {
            if (getRowRealtime('users', $id, 'money') < $amount) {
                die('<script type="text/javascript">if(!alert("' . __('Số dư bạn trừ vượt quá số dư khả dụng của thành viên') . '")){window.history.back().location.reload();}</script>');
            }
            $reason = '[VÍ CHÍNH] ' . check_string($_POST['reason']);
            /* Xử lý trừ tiền */
            $DBUser = new users();
            $DBUser->RemoveCredits($id, $amount, $reason, 'admin_remove_' . uniqid());
        }

        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'createdate'    => gettime(),
            'device'        => getUserAgent(),
            'ip'            => myip(),
            'action'        => sprintf(__('[Admin] Trừ %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason)
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', sprintf(__('[Admin] Trừ %s cho User %s[%s] lý do (%s).'), format_currency($amount), $user['username'], $user['id'], $reason), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        admin_msg_success(__("Trừ số dư thành công!"), "", 1000);
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-user-edit"></i>
                <?= __('Chỉnh sửa thành viên'); ?></h1>
        </div>

        <!-- User Profile Header Card -->
        <div class="card custom-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8 mb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-xxl bg-primary-transparent rounded-circle me-4">
                                <img class="avatar-initial rounded-circle bg-primary text-white fs-20 fw-bold"
                                    src="<?= getGravatarUrl($user['email']); ?>">
                            </div>
                            <div>
                                <div class="d-flex align-items-center mb-2">
                                    <h4 class="mb-0 fw-bold text-dark me-3"><?= $user['username']; ?></h4>
                                    <span
                                        class="badge bg-<?= $user['banned'] == 0 ? 'success' : 'danger'; ?> rounded-pill px-3 py-2">
                                        <i
                                            class="ri-<?= $user['banned'] == 0 ? 'check' : 'close'; ?>-circle-line me-1"></i>
                                        <?= $user['banned'] == 0 ? __('Hoạt động') : __('Bị khóa'); ?>
                                    </span>
                                </div>
                                <div class="text-muted">
                                    <i class="ri-shield-user-line me-2"></i>
                                    <span><?= __('ID'); ?>: <strong>#<?= $user['id']; ?></strong></span>
                                </div>
                                <div class="text-muted mb-2">
                                    <i class="ri-calendar-line me-2"></i>
                                    <span><?= __('Tham gia'); ?>:
                                        <strong><?= date('d/m/Y', strtotime($user['create_date'])); ?></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-lg-end">
                            <!-- Primary Actions -->
                            <div class="btn-group me-2 mb-2" role="group">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#modal-addCredit"
                                    class="btn btn-sm btn-success btn-wave">
                                    <i class="ri-add-circle-line me-1"></i><?= __('Cộng tiền'); ?>
                                </button>
                                <button type="button" data-bs-toggle="modal" data-bs-target="#modal-removeCredit"
                                    class="btn btn-sm btn-danger btn-wave">
                                    <i class="ri-subtract-line me-1"></i><?= __('Trừ tiền'); ?>
                                </button>
                            </div>

                            <!-- Secondary Actions -->
                            <div class="btn-group mb-2" role="group">
                                <button type="button" data-bs-toggle="modal" data-bs-target="#modal-createTicket"
                                    class="btn btn-sm btn-info btn-wave">
                                    <i class="ri-customer-service-2-line me-1"></i><?= __('Tạo ticket'); ?>
                                </button>
                                <div class="btn-group" role="group">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary btn-wave dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-2-line me-1"></i><?= __('Thêm'); ?>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item"
                                                href="<?= base_url_admin('logs&user_id=' . $user['id']); ?>"
                                                target="_blank">
                                                <i
                                                    class="ri-history-line me-2 text-primary"></i><?= __('Nhật ký hoạt động'); ?>
                                            </a>
                                        <li>
                                            <a class="dropdown-item"
                                                href="<?= base_url_admin('transactions&user_id=' . $user['id']); ?>"
                                                target="_blank">
                                                <i
                                                    class="ri-exchange-funds-line me-2 text-success"></i><?= __('Biến động số dư'); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item"
                                                href="<?= base_url_admin('orders&user_id=' . $user['id']); ?>"
                                                target="_blank">
                                                <i
                                                    class="ri-shopping-cart-line me-2 text-danger"></i><?= __('Đơn hàng'); ?>
                                            </a>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= base_url_admin('users'); ?>">
                                                <i class="ri-arrow-left-line me-2"></i><?= __('Quay lại'); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- User Statistics Card -->
            <div class="col-12">
                <div class="card custom-card border">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-primary-transparent me-3">
                                        <i class="ri-wallet-3-line fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Ví chính'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-primary"><?= format_currency($user['money']); ?>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-success-transparent me-3">
                                        <i class="ri-money-dollar-circle-line fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Tổng tiền nạp'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-success">
                                            <?= format_currency($user['total_money']); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-warning-transparent me-3">
                                        <i class="ri-shopping-cart-line fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Đã sử dụng'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-warning">
                                            <?= format_currency($user['total_money'] - $user['money']); ?></h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg bg-danger-transparent me-3">
                                        <i class="fa-solid fa-credit-card fs-18"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 text-muted fs-12"><?= __('Số tiền nợ'); ?></p>
                                        <h6 class="mb-0 fw-semibold text-danger"><?= format_currency($user['debit']); ?>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Form -->
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" method="POST">
                            <?= csrfField(); ?>
                            <!-- Navigation Tabs -->
                            <ul class="nav nav-tabs nav-tabs-header mb-4" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#basic-info"
                                        aria-selected="true">
                                        <i class="ri-user-line me-2"></i><?= __('Thông tin cơ bản'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#security-info"
                                        aria-selected="false">
                                        <i class="ri-shield-user-line me-2"></i><?= __('Bảo mật'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#permissions"
                                        aria-selected="false">
                                        <i class="ri-admin-line me-2"></i><?= __('Quyền hạn'); ?>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" href="#system-info"
                                        aria-selected="false">
                                        <i class="ri-computer-line me-2"></i><?= __('Thông tin hệ thống'); ?>
                                    </a>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content">
                                <!-- Basic Information Tab -->
                                <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Username'); ?> <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-user-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['username']; ?>" name="username" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Email <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-mail-line text-muted"></i>
                                                    </span>
                                                    <input type="email" class="form-control"
                                                        value="<?= $user['email']; ?>" name="email" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Phone'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-phone-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control" value="<?= $user['phone']; ?>"
                                                        name="phone">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Giới tính'); ?></label>
                                                <select class="form-control" name="gender">
                                                    <option <?= $user['gender'] == 'Male' ? 'selected' : ''; ?>
                                                        value="Male">
                                                        <i class="ri-men-line"></i> <?= __('Nam'); ?> (Male)
                                                    </option>
                                                    <option <?= $user['gender'] == 'Female' ? 'selected' : ''; ?>
                                                        value="Female">
                                                        <i class="ri-women-line"></i> <?= __('Nữ'); ?> (Female)
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Người giới thiệu'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-user-shared-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control" name="ref_id"
                                                        value="<?= $user['ref_id']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Chiết khấu giảm giá'); ?>
                                                    (%)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-percent-line text-muted"></i>
                                                    </span>
                                                    <input type="number" class="form-control"
                                                        value="<?= $user['discount']; ?>" name="discount" min="0"
                                                        max="100">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Information Tab -->
                                <div class="tab-pane fade" id="security-info" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Token <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-key-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control" id="token_input"
                                                        value="<?= $user['token']; ?>" name="token" required>
                                                    <button type="button" id="show_token"
                                                        class="btn btn-outline-secondary"
                                                        onclick="toggleTokenVisibility()">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                    <button type="button" id="regenerate_token"
                                                        class="btn btn-outline-warning"
                                                        onclick="regenerateToken('<?= $user['username']; ?>')"
                                                        title="<?= __('Tạo token mới'); ?>">
                                                        <i class="ri-refresh-line"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text text-warning">
                                                    <i
                                                        class="ri-alert-line me-1"></i><?= __('Bảo mật thông tin này vì kẻ xấu có thể thực hiện đăng nhập tài khoản bằng Token'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">API Key <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-key-2-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control"
                                                        value="<?= $user['api_key']; ?>" name="api_key" id="api_key_input"
                                                        required>
                                                    <button type="button" id="show_api_key"
                                                        class="btn btn-outline-secondary"
                                                        onclick="toggleApiKeyVisibility()">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text text-warning">
                                                    <i
                                                        class="ri-alert-line me-1"></i><?= __('Bảo mật thông tin này vì kẻ xấu có thể mua hàng thông qua API KEY'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Mật khẩu mới'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-lock-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control"
                                                        placeholder="<?= __('Nhập mật khẩu mới'); ?>" name="password">
                                                </div>
                                                <div class="form-text">
                                                    <i
                                                        class="ri-information-line me-1"></i><?= __('Bỏ trống nếu không muốn thay đổi mật khẩu'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Secret Key Google 2FA</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-shield-keyhole-line text-muted"></i>
                                                    </span>
                                                    <input type="password" class="form-control" id="key_2fa_input"
                                                        value="<?= $user['SecretKey_2fa']; ?>" disabled>
                                                    <button type="button" id="show_key_2fa"
                                                        class="btn btn-outline-secondary"
                                                        onclick="toggleKey2FAVisibility()">
                                                        <i class="ri-eye-line"></i>
                                                    </button>
                                                </div>
                                                <div class="form-text text-danger">
                                                    <i
                                                        class="ri-error-warning-line me-1"></i><?= __('Lộ thông tin này có thể khiến kẻ xấu bỏ qua bước xác minh 2FA.'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Xác minh Google 2FA'); ?></label>
                                                <select class="form-select" name="status_2fa">
                                                    <option <?= $user['status_2fa'] == 1 ? 'selected' : ''; ?> value="1">
                                                        <i class="ri-checkbox-circle-line"></i> <?= __('Bật'); ?>
                                                    </option>
                                                    <option <?= $user['status_2fa'] == 0 ? 'selected' : ''; ?> value="0">
                                                        <i class="ri-close-circle-line"></i> <?= __('Tắt'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Xác minh OTP'); ?></label>
                                                <select class="form-select" name="status_otp_mail">
                                                    <option <?= $user['status_otp_mail'] == 1 ? 'selected' : ''; ?> value="1">
                                                        <i class="ri-checkbox-circle-line"></i> <?= __('Bật'); ?>
                                                    </option>
                                                    <option <?= $user['status_otp_mail'] == 0 ? 'selected' : ''; ?> value="0">
                                                        <i class="ri-close-circle-line"></i> <?= __('Tắt'); ?>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Permissions Tab -->
                                <div class="tab-pane fade" id="permissions" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Admin Role'); ?> <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select" name="admin">
                                                    <option value="0" <?= $user['admin'] == 0 ? 'selected' : ''; ?>>
                                                        <i class="ri-user-line"></i> User (Khách hàng)
                                                    </option>
                                                    <?php foreach ($CMSNT->get_list(" SELECT * FROM `admin_role` ") as $role): ?>
                                                        <option value="<?= $role['id']; ?>"
                                                            <?= $user['admin'] == $role['id'] ? 'selected' : ''; ?>>
                                                            <i class="ri-admin-line"></i> <?= $role['name']; ?> (Admin Role)
                                                        </option>
                                                    <?php endforeach ?>
                                                    <?php if ($user['admin'] == 99999): ?>
                                                        <option value="99999"
                                                            <?= $user['admin'] == 99999 ? 'selected' : ''; ?>>
                                                            <i class="ri-vip-crown-line"></i> Administrator (Admin Root)
                                                        </option>
                                                    <?php endif ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Trạng thái tài khoản</label>
                                                <select class="form-select" name="banned">
                                                    <option <?= $user['banned'] == 0 ? 'selected' : ''; ?> value="0">
                                                        <i class="ri-checkbox-circle-line"></i> <span
                                                            class="text-success">Hoạt động (Active)</span>
                                                    </option>
                                                    <option <?= $user['banned'] == 1 ? 'selected' : ''; ?> value="1">
                                                        <i class="ri-forbid-line"></i> <span class="text-danger">Bị cấm
                                                            (Banned)</span>
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- System Information Tab -->
                                <div class="tab-pane fade" id="system-info" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Địa chỉ IP đăng nhập'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-wifi-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control" value="<?= $user['ip']; ?>"
                                                        disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Thiết bị đăng nhập'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-computer-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['device']; ?>" disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold"><?= __('Ngày đăng ký'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-calendar-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['create_date']; ?>" disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label
                                                    class="form-label fw-semibold"><?= __('Đăng nhập gần nhất'); ?></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-time-line text-muted"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        value="<?= $user['update_date']; ?>" disabled>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <a class="btn btn-outline-secondary btn-wave" href="<?= base_url_admin('users'); ?>">
                                    <i class="ri-arrow-left-line me-2"></i><?= __('Quay lại'); ?>
                                </a>
                                <button type="submit" class="btn btn-primary btn-wave">
                                    <i class="ri-save-line me-2"></i><?= __('Lưu thay đổi'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Logs Card -->
            <div class="col-xl-6 mb-4">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title text-uppercase">
                            <i class="ri-history-line me-2"></i><?= __('Nhật ký hoạt động gần nhất'); ?>
                        </div>
                        <a href="<?= base_url_admin('logs&user_id=' . $user['id']); ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="ri-external-link-line me-1"></i><?= __('Xem tất cả'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th class="fw-semibold"><?= __('Hành động'); ?></th>
                                        <th class="fw-semibold"><?= __('Thời gian'); ?></th>
                                        <th class="fw-semibold">IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentLogs = $CMSNT->get_list_safe("SELECT * FROM `logs` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 20", [$user['id']]);
                                    if (empty($recentLogs)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="ri-folder-open-line fs-24 d-block mb-2"></i>
                                                <?= __('Chưa có hoạt động nào'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentLogs as $log): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-start">
                                                        <div class="avatar avatar-xs bg-primary-transparent me-2 mt-1">
                                                            <i class="ri-file-text-line fs-10"></i>
                                                        </div>
                                                        <div class="flex-1">
                                                            <small class="text-muted"><?= $log['action']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark fs-10" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= timeAgo(strtotime($log['createdate'])); ?>">
                                                        <?= date('d/m H:i', strtotime($log['createdate'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger-transparent fs-10"><?= $log['ip']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Balance Transactions Card -->
            <div class="col-xl-6 mb-4">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title text-uppercase">
                            <i class="ri-exchange-line me-2"></i><?= __('Biến động số dư gần nhất'); ?>
                        </div>
                        <a href="<?= base_url_admin('transactions&user_id=' . $user['id']); ?>"
                            class="btn btn-sm btn-outline-primary">
                            <i class="ri-external-link-line me-1"></i><?= __('Xem tất cả'); ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th class="fw-semibold"><?= __('Thay đổi'); ?></th>
                                        <th class="fw-semibold"><?= __('Số dư sau'); ?></th>
                                        <th class="fw-semibold"><?= __('Thời gian'); ?></th>
                                        <th class="fw-semibold"><?= __('Lý do'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recentTransactions = $CMSNT->get_list_safe("SELECT * FROM `dongtien` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 20", [$user['id']]);
                                    if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="ri-coins-line fs-24 d-block mb-2"></i>
                                                <?= __('Chưa có giao dịch nào'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <?php if (($transaction['sotiensau'] - $transaction['sotientruoc']) > 0): ?>
                                                        <span class="badge bg-success-transparent">
                                                            <i
                                                                class="ri-add-line me-1"></i><?= format_currency($transaction['sotienthaydoi']); ?>
                                                        </span>
                                                    <?php elseif (($transaction['sotientruoc'] - $transaction['sotiensau']) > 0): ?>
                                                        <span class="badge bg-danger-transparent">
                                                            <i
                                                                class="ri-subtract-line me-1"></i><?= format_currency($transaction['sotienthaydoi']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-transparent">
                                                            <i
                                                                class="ri-equal-line me-1"></i><?= format_currency($transaction['sotienthaydoi']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-primary-transparent"><?= format_currency($transaction['sotiensau']); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark fs-10" data-toggle="tooltip"
                                                        data-placement="bottom"
                                                        title="<?= timeAgo(strtotime($transaction['thoigian'])); ?>">
                                                        <?= date('d/m H:i', strtotime($transaction['thoigian'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted" title="<?= $transaction['noidung']; ?>">
                                                        <?= $transaction['noidung']; ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 mb-4">
                <div class="card custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="card-title text-uppercase">
                            <i class="ri-history-line me-2"></i><?= __('Phiên đăng nhập'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php
                            $sessions = $CMSNT->get_list_safe("SELECT * FROM `active_sessions` WHERE `user_id` = ? ORDER BY `last_activity` DESC", [$user['id']]);
                            foreach ($sessions as $session):
                                // Xác định icon dựa vào user agent
                                $icon = 'ri-computer-line'; // mặc định là máy tính
                                if (
                                    strpos(strtolower($session['user_agent']), 'mobile') !== false ||
                                    strpos(strtolower($session['user_agent']), 'android') !== false ||
                                    strpos(strtolower($session['user_agent']), 'iphone') !== false
                                ) {
                                    $icon = 'ri-smartphone-line';
                                } else if (
                                    strpos(strtolower($session['user_agent']), 'ipad') !== false ||
                                    strpos(strtolower($session['user_agent']), 'tablet') !== false
                                ) {
                                    $icon = 'ri-tablet-line';
                                }
                            ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0 avatar-sm">
                                        <div class="avatar-title bg-light text-primary rounded-3 fs-18 material-shadow">
                                            <i class="<?= $icon; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6><?= get_device_by_user_agent($session['user_agent']); ?></h6>
                                        <p class="text-muted mb-0">
                                            <?= __('Địa chỉ IP'); ?>: <?= $session['ip_address']; ?> -
                                            <?= __('Hoạt động'); ?>
                                            <?= timeAgo(strtotime($session['last_activity'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <a href="javascript:void(0);" onclick="logoutSession('<?= $session['id']; ?>');"
                                            class="text-danger">
                                            <?= __('Đăng xuất'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Enhanced JavaScript for improved UI -->
<script>
    // Toggle password visibility functions with improved styling
    function toggleTokenVisibility() {
        var input = document.getElementById('token_input');
        var button = document.getElementById('show_token');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    // Tạo token mới với prefix username xáo trộn
    function regenerateToken(username) {
        Swal.fire({
            title: '<?= __('Xác nhận'); ?>',
            text: '<?= __('Bạn có chắc muốn tạo token mới? Người dùng sẽ bị đăng xuất khỏi tất cả thiết bị.'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f7b84b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<?= __('Tạo mới'); ?>',
            cancelButtonText: '<?= __('Hủy'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tạo token mới client-side (giống logic PHP generateUserToken)
                var cleanUsername = username.replace(/[^a-zA-Z0-9]/g, '');
                if (!cleanUsername) cleanUsername = 'user';

                // Xáo trộn username
                var usernameArray = cleanUsername.split('');
                for (var i = usernameArray.length - 1; i > 0; i--) {
                    var j = Math.floor(Math.random() * (i + 1));
                    var temp = usernameArray[i];
                    usernameArray[i] = usernameArray[j];
                    usernameArray[j] = temp;
                }
                var shuffledPrefix = usernameArray.join('');

                // Tạo random hex string (128 ký tự = 64 bytes)
                var maxLength = 255;
                var prefixLength = shuffledPrefix.length;
                var availableLength = maxLength - prefixLength;
                var hexLength = Math.min(128, availableLength);

                var randomHex = '';
                var hexChars = '0123456789abcdef';
                for (var i = 0; i < hexLength; i++) {
                    randomHex += hexChars.charAt(Math.floor(Math.random() * 16));
                }

                var newToken = shuffledPrefix + randomHex;

                // Cập nhật input
                document.getElementById('token_input').value = newToken;
                document.getElementById('token_input').type = 'text';

                // Cập nhật icon button show/hide
                var showBtn = document.getElementById('show_token');
                showBtn.querySelector('i').className = 'ri-eye-off-line';
                showBtn.classList.remove('btn-outline-secondary');
                showBtn.classList.add('btn-outline-primary');

                Swal.fire({
                    title: '<?= __('Đã tạo token mới!'); ?>',
                    text: '<?= __('Nhấn "Lưu thay đổi" để áp dụng.'); ?>',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }

    function toggleApiKeyVisibility() {
        var input = document.getElementById('api_key_input');
        var button = document.getElementById('show_api_key');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleKey2FAVisibility() {
        var input = document.getElementById('key_2fa_input');
        var button = document.getElementById('show_key_2fa');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    // Tab navigation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade animation to tabs
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tabLink) {
            tabLink.addEventListener('shown.bs.tab', function() {
                const target = document.querySelector(this.getAttribute('href'));
                target.style.opacity = '0';
                setTimeout(function() {
                    target.style.transition = 'opacity 0.3s ease-in-out';
                    target.style.opacity = '1';
                }, 50);
            });
        });
    });
</script>

<?php
require_once(__DIR__ . '/footer.php');
?>

<div class="modal fade" id="modal-addCredit" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <?= csrfField(); ?>
                <div class="modal-header bg-primary-transparent">
                    <h6 class="modal-title fw-semibold" id="staticBackdropLabel2">
                        <i class="ri-add-circle-line me-2"></i><?= __('Cộng số dư'); ?>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="notice_add_money" class="alert alert-success alert-dismissible fade show"
                        style="display: none;" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-checkbox-circle-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('CỘNG TIỀN VÀO VÍ CHÍNH'); ?></strong><br>
                                <small><?= __('Số tiền sẽ được cộng vào ví chính và cộng vào tổng nạp của thành viên.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="notice_refund" class="alert alert-info alert-dismissible fade show" style="display: none;"
                        role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-information-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('HOÀN TIỀN VÀO VÍ CHÍNH'); ?></strong><br>
                                <small><?= __('Số tiền sẽ được hoàn vào ví chính nhưng KHÔNG cộng vào tổng nạp của thành viên.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="notice_credit_debt" class="alert alert-warning alert-dismissible fade show"
                        style="display: none;" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-error-warning-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI'); ?></strong><br>
                                <small><?= __('Số tiền sẽ được cộng vào ví chính nhưng KHÔNG cộng vào tổng nạp, đồng thời ghi nợ lại số tiền này. Số tiền nợ sẽ được trừ tự động khi thành viên nạp tiền.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Loại ví'); ?></label>
                            <select class="form-select" name="wallet" id="walletSelect">
                                <option value="1"><?= __('CỘNG TIỀN VÀO VÍ CHÍNH'); ?></option>
                                <option value="2"><?= __('HOÀN TIỀN VÀO VÍ CHÍNH'); ?></option>
                                <option value="3"><?= __('CỘNG TIỀN VÀO VÍ CHÍNH VÀ GHI NỢ LẠI'); ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Số tiền'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                                <input type="number" step="any" min="0" class="form-control" name="amount"
                                    id="amountInput" placeholder="<?= __('Nhập số tiền cần cộng'); ?>" required>
                                <span class="input-group-text"><?= getCurrencyNameDefault(); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Lý do'); ?></label>
                            <textarea class="form-control" name="reason" rows="3"
                                placeholder="<?= __('Nhập lý do cộng tiền (tùy chọn)'); ?>"></textarea>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 text-center">
                        <i class="ri-information-line me-1"></i>
                        <?= __('Sẽ thực hiện cộng'); ?> <span id="amountDisplay" class="fw-bold text-primary">0</span> VNĐ
                        <?= __('vào'); ?> <span id="walletDisplay"
                            class="fw-bold text-success"><?= __('VÍ CHÍNH'); ?></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-hero btn-danger" data-bs-dismiss="modal"><i
                            class="fa fa-fw fa-times me-1"></i> <?= __('Close'); ?></button>
                    <button type="submit" name="cong_tien" class="btn btn-hero btn-success"><i
                            class="fa fa-fw fa-plus me-1"></i> <?= __('Submit'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Enhanced Modal JavaScript for Cộng số dư
    document.addEventListener("DOMContentLoaded", function() {
        // Modal Cộng tiền elements
        var selectWallet = document.getElementById('walletSelect');
        var amountInput = document.getElementById('amountInput');
        var amountDisplay = document.getElementById('amountDisplay');
        var walletDisplay = document.getElementById('walletDisplay');
        var noticeAddMoney = document.getElementById('notice_add_money');
        var noticeRefund = document.getElementById('notice_refund');
        var noticeCreditDebt = document.getElementById('notice_credit_debt');

        // Initialize displays if elements exist
        if (selectWallet && amountInput && amountDisplay && walletDisplay) {
            updateAmountDisplay();
            updateWalletDisplay();
            updateNoticeDisplay();

            // Event listeners
            amountInput.addEventListener('input', updateAmountDisplay);
            selectWallet.addEventListener('change', function() {
                updateWalletDisplay();
                updateNoticeDisplay();
            });

            function updateAmountDisplay() {
                var inputValue = amountInput.value;
                if (!inputValue || isNaN(inputValue)) {
                    amountDisplay.textContent = '0';
                    return;
                }
                var formattedAmount = formatNumber(inputValue);
                amountDisplay.textContent = formattedAmount;
            }

            function updateWalletDisplay() {
                walletDisplay.textContent = selectWallet.options[selectWallet.selectedIndex].text;
            }

            function updateNoticeDisplay() {
                if (!noticeAddMoney || !noticeRefund || !noticeCreditDebt) return;

                // Ẩn tất cả thông báo
                noticeAddMoney.style.display = 'none';
                noticeRefund.style.display = 'none';
                noticeCreditDebt.style.display = 'none';

                // Hiển thị thông báo tương ứng
                if (selectWallet.value === "1") {
                    noticeAddMoney.style.display = 'block';
                } else if (selectWallet.value === "2") {
                    noticeRefund.style.display = 'block';
                } else if (selectWallet.value === "3") {
                    noticeCreditDebt.style.display = 'block';
                }
            }

            function formatNumber(value) {
                return parseFloat(value).toLocaleString('vi-VN');
            }
        }

        // Modal Trừ tiền elements
        var selectWallet2 = document.getElementById('walletSelect2');
        var amountInput2 = document.getElementById('amountInput2');
        var amountDisplay2 = document.getElementById('amountDisplay2');
        var walletDisplay2 = document.getElementById('walletDisplay2');

        // Initialize displays for modal trừ tiền
        if (selectWallet2 && amountInput2 && amountDisplay2 && walletDisplay2) {
            updateAmountDisplay2();
            updateWalletDisplay2();

            // Event listeners for modal trừ tiền
            amountInput2.addEventListener('input', updateAmountDisplay2);
            selectWallet2.addEventListener('change', updateWalletDisplay2);

            function updateAmountDisplay2() {
                var inputValue = amountInput2.value;
                if (!inputValue || isNaN(inputValue)) {
                    amountDisplay2.textContent = '0';
                    return;
                }
                var formattedAmount = formatNumber(inputValue);
                amountDisplay2.textContent = formattedAmount;
            }

            function updateWalletDisplay2() {
                walletDisplay2.textContent = selectWallet2.options[selectWallet2.selectedIndex].text;
            }

            function formatNumber(value) {
                return parseFloat(value).toLocaleString('vi-VN');
            }
        }
    });
</script>

<div class="modal fade" id="modal-removeCredit" tabindex="-1" aria-labelledby="modal-block-popout" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="" method="POST" enctype="multipart/form-data">
                <?= csrfField(); ?>
                <div class="modal-header bg-danger-transparent">
                    <h6 class="modal-title fw-semibold" id="staticBackdropLabel2">
                        <i class="ri-subtract-line me-2"></i><?= __('Trừ số dư'); ?>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ri-error-warning-line me-2 fs-16"></i>
                            <div>
                                <strong><?= __('Chú ý khi trừ tiền'); ?></strong><br>
                                <small><?= __('Hãy kiểm tra kỹ số dư và lý do trước khi thực hiện. Thao tác này không thể hoàn tác.'); ?></small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Loại ví'); ?></label>
                            <select class="form-select" name="wallet" id="walletSelect2">
                                <option value="1"><?= __('VÍ CHÍNH'); ?></option>
                                <option value="2"><?= __('VÍ GHI NỢ'); ?></option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Số tiền'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                                <input type="number" step="any" min="0" class="form-control" name="amount"
                                    id="amountInput2" placeholder="<?= __('Nhập số tiền cần trừ'); ?>" required>
                                <span class="input-group-text"><?= getCurrencyNameDefault(); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Lý do'); ?></label>
                            <textarea class="form-control" name="reason" id="reasonInput" rows="3"
                                placeholder="<?= __('Nhập lý do trừ tiền'); ?>" required></textarea>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 text-center">
                        <i class="ri-information-line me-1"></i>
                        <?= __('Sẽ thực hiện trừ'); ?> <span id="amountDisplay2" class="fw-bold text-danger">0</span>
                        <?= getCurrencyNameDefault(); ?>
                        <?= __('từ'); ?> <span id="walletDisplay2"
                            class="fw-bold text-warning"><?= __('VÍ CHÍNH'); ?></span>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i><?= __('Đóng'); ?>
                    </button>
                    <button type="submit" name="tru_tien" class="btn btn-danger btn-wave">
                        <i class="ri-subtract-line me-1"></i><?= __('Trừ tiền'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tạo Ticket -->
<div class="modal fade" id="modal-createTicket" tabindex="-1" aria-labelledby="modal-createTicket-label" role="dialog"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info-transparent">
                <h6 class="modal-title fw-semibold" id="modal-createTicket-label">
                    <i class="ri-ticket-line me-2"></i><?= __('Tạo ticket cho user'); ?>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ri-information-line me-2 fs-16"></i>
                        <div>
                            <strong><?= __('Tạo ticket cho user'); ?></strong><br>
                            <small><?= __('Ticket sẽ được tạo dưới tên user này. User sẽ nhận được thông báo qua Telegram (nếu có kích hoạt).'); ?></small>
                        </div>
                    </div>
                </div>

                <form id="form-createTicket">
                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                    <input type="hidden" name="action" value="createTicketForUser">
                    <input type="hidden" name="token" value="<?= $getUser['token']; ?>">

                    <div class="row g-3">


                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('Chủ đề hỗ trợ'); ?> <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" name="category" id="ticketCategory" required>
                                <option value=""><?= __('Chọn chủ đề hỗ trợ'); ?></option>
                                <?php foreach ($config_category_support_tickets as $key => $value): ?>
                                    <option value="<?= $key; ?>"><?= $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="orderIdGroup" style="display: none;">
                            <label class="form-label fw-semibold"><?= __('Mã đơn hàng'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-shopping-cart-line"></i>
                                </span>
                                <input type="text" class="form-control" name="order_id" id="orderIdInput"
                                    placeholder="<?= __('Nhập mã đơn hàng (tùy chọn)'); ?>">
                            </div>
                            <small class="text-muted"><?= __('Chỉ áp dụng với chủ đề Đơn hàng'); ?></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Tiêu đề ticket'); ?> <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="ri-edit-line"></i>
                                </span>
                                <input type="text" class="form-control" name="subject" id="ticketSubject"
                                    placeholder="<?= __('Nhập tiêu đề ticket'); ?>" maxlength="200" required>
                            </div>
                            <small class="text-muted"><span id="subjectCount">0</span>/200 <?= __('ký tự'); ?></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold"><?= __('Nội dung ticket'); ?> <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" id="ticketContent" rows="5"
                                placeholder="<?= __('Nhập nội dung chi tiết ticket...'); ?>" maxlength="5000"
                                required></textarea>
                            <small class="text-muted"><span id="contentCount">0</span>/5000 <?= __('ký tự'); ?></small>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i><?= __('Đóng'); ?>
                </button>
                <button type="button" class="btn btn-info btn-wave" id="btn-createTicket">
                    <i class="ri-ticket-line me-1"></i><?= __('Tạo ticket'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript for improved UI -->
<script>
    // Toggle password visibility functions with improved styling
    function toggleTokenVisibility() {
        var input = document.getElementById('token_input');
        var button = document.getElementById('show_token');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleApiKeyVisibility() {
        var input = document.getElementById('api_key_input');
        var button = document.getElementById('show_api_key');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    function toggleKey2FAVisibility() {
        var input = document.getElementById('key_2fa_input');
        var button = document.getElementById('show_key_2fa');
        var icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ri-eye-off-line';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-outline-primary');
        } else {
            input.type = 'password';
            icon.className = 'ri-eye-line';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-outline-secondary');
        }
    }

    // Tab navigation enhancement
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade animation to tabs
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabLinks.forEach(function(tabLink) {
            tabLink.addEventListener('shown.bs.tab', function() {
                const target = document.querySelector(this.getAttribute('href'));
                target.style.opacity = '0';
                setTimeout(function() {
                    target.style.transition = 'opacity 0.3s ease-in-out';
                    target.style.opacity = '1';
                }, 50);
            });
        });
    });
</script>

<script>
    function logoutSession(session_id) {
        Swal.fire({
            title: '<?= __('Xác nhận'); ?>',
            text: '<?= __('Bạn có chắc chắn muốn đăng xuất phiên này không?'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= __('Xác nhận'); ?>',
            cancelButtonText: '<?= __('Hủy'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/admin/remove.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'removeSession',
                        session_id: session_id,
                        token: '<?= $getUser['token']; ?>'
                    },
                    success: function(res) {
                        if (res.status == 'success') {
                            Swal.fire({
                                title: '<?= __('Thành công'); ?>',
                                text: res.msg,
                                icon: 'success',
                                confirmButtonText: '<?= __('Đóng'); ?>'
                            }).then((result) => {
                                if (result.isConfirmed || result.isDismissed) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: '<?= __('Lỗi'); ?>',
                                text: res.msg,
                                icon: 'error',
                                confirmButtonText: '<?= __('Đóng'); ?>'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: '<?= __('Lỗi'); ?>',
                            text: '<?= __('Có lỗi xảy ra!'); ?>',
                            icon: 'error',
                            confirmButtonText: '<?= __('Đóng'); ?>'
                        });
                    }
                });
            }
        });
    }
</script>

<script>
    // JavaScript cho Modal Tạo Ticket
    document.addEventListener('DOMContentLoaded', function() {
        // Elements
        const ticketCategory = document.getElementById('ticketCategory');
        const orderIdGroup = document.getElementById('orderIdGroup');
        const orderIdInput = document.getElementById('orderIdInput');
        const ticketSubject = document.getElementById('ticketSubject');
        const ticketContent = document.getElementById('ticketContent');
        const subjectCount = document.getElementById('subjectCount');
        const contentCount = document.getElementById('contentCount');
        const btnCreateTicket = document.getElementById('btn-createTicket');
        const formCreateTicket = document.getElementById('form-createTicket');
        const modalCreateTicket = document.getElementById('modal-createTicket');

        // Show/hide order ID field based on category
        if (ticketCategory) {
            ticketCategory.addEventListener('change', function() {
                if (this.value === 'order') {
                    orderIdGroup.style.display = 'block';
                    orderIdInput.required = false; // Optional field
                } else {
                    orderIdGroup.style.display = 'none';
                    orderIdInput.required = false;
                    orderIdInput.value = '';
                }
            });
        }

        // Character counters
        if (ticketSubject && subjectCount) {
            ticketSubject.addEventListener('input', function() {
                subjectCount.textContent = this.value.length;
                if (this.value.length > 180) {
                    subjectCount.parentElement.classList.add('text-warning');
                } else {
                    subjectCount.parentElement.classList.remove('text-warning');
                }
            });
        }

        if (ticketContent && contentCount) {
            ticketContent.addEventListener('input', function() {
                contentCount.textContent = this.value.length;
                if (this.value.length > 4500) {
                    contentCount.parentElement.classList.add('text-warning');
                } else {
                    contentCount.parentElement.classList.remove('text-warning');
                }
            });
        }

        // Reset form when modal is closed
        if (modalCreateTicket) {
            modalCreateTicket.addEventListener('hidden.bs.modal', function() {
                if (formCreateTicket) {
                    formCreateTicket.reset();
                    orderIdGroup.style.display = 'none';
                    subjectCount.textContent = '0';
                    contentCount.textContent = '0';
                    subjectCount.parentElement.classList.remove('text-warning');
                    contentCount.parentElement.classList.remove('text-warning');
                }
            });
        }

        // Handle form submission
        if (btnCreateTicket) {
            btnCreateTicket.addEventListener('click', function() {
                // Validate form
                if (!formCreateTicket.checkValidity()) {
                    formCreateTicket.reportValidity();
                    return;
                }

                // Disable button and show loading
                const originalText = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<i class="ri-loader-4-line me-1 spin"></i><?= __('Đang tạo...'); ?>';

                // Get form data
                const formData = new FormData(formCreateTicket);

                // Send AJAX request
                fetch('<?= base_url('ajaxs/admin/ticket.php'); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: '<?= __('Thành công'); ?>',
                                text: data.msg,
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonText: '<?= __('Xem ticket'); ?>',
                                cancelButtonText: '<?= __('Đóng'); ?>',
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#6c757d'
                            }).then((result) => {
                                // Close modal and reset form
                                const modal = bootstrap.Modal.getInstance(modalCreateTicket);
                                modal.hide();
                                formCreateTicket.reset();
                                orderIdGroup.style.display = 'none';
                                subjectCount.textContent = '0';
                                contentCount.textContent = '0';

                                if (result.isConfirmed && data.redirect_url) {
                                    // Chuyển hướng đến trang chi tiết ticket
                                    window.location.href = data.redirect_url;
                                }
                            });
                        } else {
                            Swal.fire({
                                title: '<?= __('Lỗi'); ?>',
                                text: data.msg,
                                icon: 'error',
                                confirmButtonText: '<?= __('Đóng'); ?>'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: '<?= __('Lỗi'); ?>',
                            text: '<?= __('Có lỗi xảy ra, vui lòng thử lại'); ?>',
                            icon: 'error',
                            confirmButtonText: '<?= __('Đóng'); ?>'
                        });
                    })
                    .finally(() => {
                        // Re-enable button
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
            });
        }
    });

    // Add CSS for spinning animation
    const style = document.createElement('style');
    style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
    document.head.appendChild(style);
</script>