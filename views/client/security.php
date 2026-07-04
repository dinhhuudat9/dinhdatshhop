<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Bảo mật tài khoản') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/profile.css">
';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-3">
                <?php require_once(__DIR__ . '/sidebar.php'); ?>
            </div>
            <div class="col-lg-9">
                <!-- Security Settings Card -->
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5>
                            <i class="fa-solid fa-shield-halved"></i>
                            <?= __('Cài đặt bảo mật'); ?>
                        </h5>
                    </div>
                    <div class="card-modern-body">
                        <input type="hidden" id="token" value="<?= $getUser['token']; ?>">

                        <!-- OTP Mail Setting -->
                        <div class="security-setting-item">
                            <div class="security-setting-info">
                                <div class="security-setting-title">
                                    <i class="fa-solid fa-envelope-circle-check"></i>
                                    <?= __('Xác minh OTP qua Email'); ?>
                                </div>
                                <p class="security-setting-desc">
                                    <?= __('Yêu cầu mã xác minh được gửi qua email khi đăng nhập'); ?>
                                </p>
                            </div>
                            <div class="security-setting-toggle">
                                <label class="switch">
                                    <input type="checkbox" value="1" id="status_otp_mail"
                                        <?= $getUser['status_otp_mail'] == 1 ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Login Notification Setting -->
                        <div class="security-setting-item">
                            <div class="security-setting-info">
                                <div class="security-setting-title">
                                    <i class="fa-solid fa-bell"></i>
                                    <?= __('Thông báo đăng nhập'); ?>
                                </div>
                                <p class="security-setting-desc">
                                    <?= __('Nhận email thông báo mỗi khi có đăng nhập thành công'); ?>
                                </p>
                            </div>
                            <div class="security-setting-toggle">
                                <label class="switch">
                                    <input type="checkbox" value="1" id="status_noti_login_to_mail"
                                        <?= $getUser['status_noti_login_to_mail'] == 1 ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Order View Setting -->
                        <div class="security-setting-item">
                            <div class="security-setting-info">
                                <div class="security-setting-title">
                                    <i class="fa-solid fa-lock"></i>
                                    <?= __('Bảo vệ đơn hàng'); ?>
                                </div>
                                <p class="security-setting-desc">
                                    <?= __('Chỉ xem được đơn hàng từ trình duyệt và IP đã mua'); ?>
                                </p>
                            </div>
                            <div class="security-setting-toggle">
                                <label class="switch">
                                    <input type="checkbox" value="1" id="status_view_order"
                                        <?= $getUser['status_view_order'] == 1 ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Update Button -->
                        <div class="text-center mt-4">
                            <button class="security-update-btn" id="btnChangeSecurity" type="button">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span><?= __('Lưu thay đổi'); ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Google 2FA Card -->
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5>
                            <i class="fa-brands fa-google"></i>
                            <?= __('Google Authenticator'); ?>
                        </h5>
                    </div>
                    <div class="card-modern-body">
                        <div class="twofa-section">
                            <!-- 2FA Toggle -->
                            <div class="security-setting-item" style="margin-bottom: 0; background: #fff;">
                                <div class="security-setting-info">
                                    <div class="security-setting-title">
                                        <i class="fa-solid fa-qrcode"></i>
                                        <?= __('Xác thực 2 lớp (2FA)'); ?>
                                    </div>
                                    <p class="security-setting-desc">
                                        <?= __('Sử dụng Google Authenticator để bảo vệ tài khoản'); ?>
                                    </p>
                                </div>
                                <div class="security-setting-toggle">
                                    <label class="switch">
                                        <input type="checkbox" value="1" id="status_2fa"
                                            <?= $getUser['status_2fa'] == 1 ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <!-- QR Code & Verification -->
                            <div id="qr_2fa" style="display:none;">
                                <div class="twofa-qr-container">
                                    <?php

                                    use PragmaRX\Google2FAQRCode\Google2FA;

                                    $google2fa = new Google2FA();
                                    $qrCodeUrl = $google2fa->getQRCodeInline($CMSNT->site('title'), $getUser['email'], $getUser['SecretKey_2fa']);
                                    ?>
                                    <img src="<?= $qrCodeUrl; ?>" alt="QR Code 2FA" class="twofa-qr-image" style="max-width: 200px; height: auto;">

                                    <div class="twofa-input-group">
                                        <input placeholder="<?= __('Mã 6 chữ số'); ?>"
                                            class="input-style"
                                            id="secret"
                                            type="text"
                                            maxlength="6"
                                            pattern="[0-9]{6}"
                                            required>
                                        <button class="btn-save" id="btnSave2FA">
                                            <i class="fa-solid fa-check"></i>
                                            <span><?= __('Xác nhận'); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 2FA Notes -->
                            <div class="twofa-note">
                                <ul>
                                    <li><?= __('Tải ứng dụng Google Authenticator trên điện thoại'); ?></li>
                                    <li><?= __('Quét mã QR để liên kết tài khoản'); ?></li>
                                    <li><?= __('Mã QR sẽ thay đổi khi bạn tắt xác minh'); ?></li>
                                    <li><?= __('Không thể bật đồng thời OTP Mail và Google Authenticator'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Sessions Card -->
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5>
                            <i class="fa-solid fa-display"></i>
                            <?= __('Phiên hoạt động'); ?>
                        </h5>
                    </div>
                    <div class="card-modern-body">
                        <div class="sessions-header">
                            <h5><?= __('Các thiết bị đang đăng nhập'); ?></h5>
                            <a href="javascript:void(0);" class="logout-all-btn" onclick="logoutAllSessions()">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <?= __('Đăng xuất tất cả'); ?>
                            </a>
                        </div>

                        <div class="sessions-list">
                            <?php
                            $sessions = $CMSNT->get_list_safe("SELECT * FROM `active_sessions` WHERE `user_id` = ? ORDER BY `last_activity` DESC", [$getUser['id']]);
                            if (empty($sessions)):
                            ?>
                                <div class="empty-state">
                                    <i class="fa-solid fa-laptop"></i>
                                    <p><?= __('Chưa có phiên đăng nhập nào'); ?></p>
                                </div>
                                <?php else:
                                foreach ($sessions as $session):
                                    // Xác định icon và màu dựa vào user agent
                                    $icon = 'fa-solid fa-desktop';
                                    $iconColor = '#4a90e2';
                                    $bgGradient = 'linear-gradient(135deg, #4a90e215, #4a90e225)';

                                    if (
                                        strpos(strtolower($session['user_agent']), 'mobile') !== false ||
                                        strpos(strtolower($session['user_agent']), 'android') !== false ||
                                        strpos(strtolower($session['user_agent']), 'iphone') !== false
                                    ) {
                                        $icon = 'fa-solid fa-mobile-screen-button';
                                        $iconColor = '#7b68ee';
                                        $bgGradient = 'linear-gradient(135deg, #7b68ee15, #7b68ee25)';
                                    } else if (
                                        strpos(strtolower($session['user_agent']), 'ipad') !== false ||
                                        strpos(strtolower($session['user_agent']), 'tablet') !== false
                                    ) {
                                        $icon = 'fa-solid fa-tablet-screen-button';
                                        $iconColor = '#50c878';
                                        $bgGradient = 'linear-gradient(135deg, #50c87815, #50c87825)';
                                    }

                                    $isCurrentSession = ($session['device_token'] == getOrCreateDeviceToken());
                                ?>
                                    <div class="session-item">
                                        <div class="session-icon" style="background: <?= $bgGradient; ?>;">
                                            <i class="<?= $icon; ?>" style="color: <?= $iconColor; ?>;"></i>
                                        </div>
                                        <div class="session-info">
                                            <h6><?= get_device_by_user_agent($session['user_agent']); ?></h6>
                                            <div class="session-details">
                                                <span class="session-detail-item">
                                                    <i class="fa-solid fa-network-wired"></i>
                                                    <?= $session['ip_address']; ?>
                                                </span>
                                                <span class="session-detail-item">
                                                    <i class="fa-solid fa-clock"></i>
                                                    <?= __('Hoạt động'); ?> <?= timeAgo(strtotime($session['last_activity'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="session-action">
                                            <?php if ($isCurrentSession): ?>
                                                <span class="badge-current">
                                                    <i class="fa-solid fa-check-circle"></i>
                                                    <?= __('Phiên hiện tại'); ?>
                                                </span>
                                            <?php else: ?>
                                                <a href="javascript:void(0);"
                                                    class="btn-logout-session"
                                                    onclick="logoutSession('<?= $session['id']; ?>');">
                                                    <i class="fa-solid fa-right-from-bracket"></i>
                                                    <?= __('Đăng xuất'); ?>
                                                </a>
                                            <?php endif ?>
                                        </div>
                                    </div>
                            <?php endforeach;
                            endif ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        $("#status_2fa").change(function() {
            var qrElement = $("#qr_2fa");
            var toggled = qrElement.data('toggled');

            if (!toggled) {
                qrElement.show();
                qrElement.data('toggled', true);
            } else {
                qrElement.hide();
                qrElement.data('toggled', false);
            }
        });
    });
</script>
<script type="text/javascript">
    $("#btnSave2FA").on("click", function() {
        // Validate 2FA secret code
        var secret = $("#secret").val();
        if (!secret || secret.length !== 6 || !/^[0-9]{6}$/.test(secret)) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Mã xác minh phải có đúng 6 chữ số'); ?>', 'error');
            return;
        }

        $('#btnSave2FA').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'Save2FA',
                token: $("#token").val(),
                status_2fa: $("#status_2fa").is(":checked") ? 1 : 0,
                secret: secret
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire('<?= __('Successful!'); ?>', respone.msg, 'success');
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }
                $('#btnSave2FA').html(
                    '<span><i class="fa-solid fa-floppy-disk"></i> <?= __('Lưu'); ?></span>'
                ).prop('disabled',
                    false);
            },
            error: function() {
                showMessage('<?= __('Không thể xử lý'); ?>', 'error');
                $('#btnSave2FA').html(
                    '<span><i class="fa-solid fa-floppy-disk"></i> <?= __('Lưu'); ?></span>'
                ).prop('disabled',
                    false);
            }

        });
    });
</script>

<script type="text/javascript">
    $("#btnChangeSecurity").on("click", function() {
        $('#btnChangeSecurity').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled',
                true);
        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'changeSecurity',
                token: $("#token").val(),
                status_noti_login_to_mail: $("#status_noti_login_to_mail").is(":checked") ? 1 : 0,
                status_otp_mail: $("#status_otp_mail").is(":checked") ? 1 : 0,
                status_view_order: $("#status_view_order").is(":checked") ? 1 : 0
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire('<?= __('Successful!'); ?>', respone.msg, 'success');
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }
                $('#btnChangeSecurity').html(
                    '<?= __('Cập nhật'); ?>'
                ).prop('disabled',
                    false);
            },
            error: function() {
                showMessage('<?= __('Không thể xử lý'); ?>', 'error');
                $('#btnChangeSecurity').html(
                    '<?= __('Cập nhật'); ?>'
                ).prop('disabled',
                    false);
            }

        });
    });
</script>

<script type="text/javascript">
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
                    url: "<?= base_url('ajaxs/client/auth.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'logoutSession',
                        session_id: session_id,
                        token: $("#token").val()
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

    function logoutAllSessions() {
        Swal.fire({
            title: '<?= __('Xác nhận đăng xuất'); ?>',
            text: '<?= __('Bạn có chắc chắn muốn đăng xuất tất cả phiên đăng nhập không? Điều này sẽ đăng xuất khỏi tất cả thiết bị.'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<?= __('Đăng xuất tất cả'); ?>',
            cancelButtonText: '<?= __('Hủy'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/client/auth.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'logoutAllSessions',
                        token: $("#token").val()
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


<?php
require_once(__DIR__ . '/footer.php');
?>