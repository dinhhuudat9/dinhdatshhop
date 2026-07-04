<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Profile') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/profile.css">
';
$body['footer'] = '';
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
                <!-- Wallet Showcase -->
                <div class="wallet-modern-showcase">
                    <div class="wallet-modern-label">
                        <i class="fa-solid fa-wallet"></i>
                        <?= __('Số dư hiện tại'); ?>
                    </div>
                    <div class="wallet-modern-amount">
                        <?= format_currency($getUser['money']); ?>
                    </div>
                    <div class="wallet-stats-grid">
                        <div class="wallet-stat-item">
                            <div class="wallet-stat-label">
                                <i class="fa-solid fa-chart-line"></i>
                                <?= __('Tổng tiền nạp'); ?>
                            </div>
                            <div class="wallet-stat-value">
                                <?= format_currency($getUser['total_money']); ?>
                            </div>
                        </div>
                        <div class="wallet-stat-item">
                            <div class="wallet-stat-label">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                <?= __('Đã sử dụng'); ?>
                            </div>
                            <div class="wallet-stat-value">
                                <?= format_currency($getUser['total_money'] - $getUser['money']); ?>
                            </div>
                        </div>
                        <div class="wallet-stat-item">
                            <div class="wallet-stat-label">
                                <i class="fa-solid fa-percent"></i>
                                <?= __('Giảm giá'); ?>
                            </div>
                            <div class="wallet-stat-value">
                                <?= $getUser['discount']; ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Information Card -->
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5>
                            <i class="fa-solid fa-user-circle"></i>
                            <?= __('Thông tin tài khoản'); ?>
                        </h5>
                    </div>
                    <div class="card-modern-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-primary" onclick="openProfileModal()">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <?= __('Chỉnh sửa'); ?>
                            </button>
                        </div>

                        <div class="profile-info-grid">
                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-user"></i>
                                    <?= __('Tên đăng nhập'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['username']; ?></div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-envelope"></i>
                                    <?= __('Địa chỉ Email'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['email']; ?></div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-phone"></i>
                                    <?= __('Số điện thoại'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['phone'] ?: __('Chưa cập nhật'); ?></div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-id-card"></i>
                                    <?= __('Họ và Tên'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['fullname'] ?: __('Chưa cập nhật'); ?></div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-brands fa-telegram"></i>
                                    <?= __('Telegram'); ?>
                                </div>
                                <div class="profile-info-value d-flex align-items-center gap-2">
                                    <?php if (!empty($getUser['telegram_chat_id'])): ?>
                                        <span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i><?= __('Đã liên kết'); ?></span>
                                        <?php if (!empty($getUser['telegram_username'])): ?>
                                            <span class="text-muted">@<?= htmlspecialchars($getUser['telegram_username']); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= __('Chưa liên kết'); ?></span>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary ms-auto" onclick="openTelegramModal()">
                                        <i class="fa-brands fa-telegram me-1"></i>
                                        <?= !empty($getUser['telegram_chat_id']) ? __('Xem chi tiết') : __('Liên kết'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-mobile-screen-button"></i>
                                    <?= __('Thiết bị'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['device']; ?></div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-calendar-plus"></i>
                                    <?= __('Đăng ký vào lúc'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['create_date']; ?></div>
                            </div>

                            <div class="profile-info-item">
                                <div class="profile-info-label">
                                    <i class="fa-solid fa-clock"></i>
                                    <?= __('Đăng nhập gần nhất'); ?>
                                </div>
                                <div class="profile-info-value"><?= $getUser['update_date']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Telegram Link Card -->
            </div>
        </div>
    </div>
</section>

<!-- Edit Profile Modal -->
<div class="custom-modal-overlay" id="profileModalOverlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h5>
                <i class="fa-solid fa-user-pen"></i>
                <?= __('Chỉnh sửa thông tin'); ?>
            </h5>
            <button type="button" class="custom-modal-close" onclick="closeProfileModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="custom-modal-body">
            <input type="hidden" value="<?= isset($getUser) ? $getUser['token'] : ''; ?>" id="token">

            <div class="custom-form-group">
                <label class="custom-form-label">
                    <i class="fa-solid fa-phone"></i>
                    <?= __('Số điện thoại'); ?>
                </label>
                <input type="text" class="custom-form-input" value="<?= $getUser['phone']; ?>" id="phone"
                    maxlength="20" placeholder="<?= __('Nhập số điện thoại'); ?>">
            </div>

            <div class="custom-form-group">
                <label class="custom-form-label">
                    <i class="fa-solid fa-id-card"></i>
                    <?= __('Họ và Tên'); ?>
                </label>
                <input type="text" class="custom-form-input" value="<?= $getUser['fullname']; ?>" id="fullname"
                    maxlength="100" placeholder="<?= __('Nhập họ và tên'); ?>">
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="custom-btn custom-btn-secondary" onclick="closeProfileModal()">
                <i class="fa-solid fa-xmark"></i>
                <?= __('Đóng'); ?>
            </button>
            <button type="button" class="custom-btn custom-btn-primary" id="btnSaveProfile">
                <i class="fa-solid fa-floppy-disk"></i>
                <?= __('Lưu thay đổi'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Telegram Link Modal -->
<div class="custom-modal-overlay" id="telegramModalOverlay">
    <div class="custom-modal" style="max-width: 500px;">
        <div class="custom-modal-header" style="background: linear-gradient(135deg, #0088cc 0%, #00aced 100%);">
            <h5>
                <i class="fa-brands fa-telegram"></i>
                <?= __('Liên kết Telegram'); ?>
            </h5>
            <button type="button" class="custom-modal-close" onclick="closeTelegramModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="custom-modal-body">
            <?php if (!empty($getUser['telegram_chat_id'])): ?>
                <!-- Đã liên kết -->
                <div class="telegram-linked-status">
                    <div class="d-flex align-items-center mb-3">
                        <div class="telegram-avatar">
                            <i class="fa-brands fa-telegram"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1 text-success">
                                <i class="fa-solid fa-check-circle me-1"></i>
                                <?= __('Đã liên kết thành công'); ?>
                            </h6>
                            <?php if (!empty($getUser['telegram_username'])): ?>
                                <span class="text-muted">@<?= htmlspecialchars($getUser['telegram_username']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Chat ID: <?= htmlspecialchars($getUser['telegram_chat_id']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        <i class="fa-solid fa-bell me-1"></i>
                        <?= __('Bạn sẽ nhận được thông báo đơn hàng, đăng nhập và các cảnh báo bảo mật qua Telegram.'); ?>
                    </p>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnUnlinkTelegram">
                        <i class="fa-solid fa-link-slash me-1"></i>
                        <?= __('Hủy liên kết'); ?>
                    </button>
                </div>
            <?php else: ?>
                <!-- Chưa liên kết -->
                <div class="telegram-link-guide">
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fa-solid fa-circle-info fa-2x"></i>
                            </div>
                            <div>
                                <strong><?= __('Lợi ích khi liên kết Telegram:'); ?></strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <li><?= __('Nhận thông báo đơn hàng ngay lập tức'); ?></li>
                                    <li><?= __('Cảnh báo đăng nhập từ thiết bị mới'); ?></li>
                                    <li><?= __('Thông báo nạp tiền thành công'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3"><i class="fa-solid fa-list-ol me-2"></i><?= __('Hướng dẫn liên kết:'); ?></h6>

                    <div class="telegram-steps">
                        <div class="telegram-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <strong><?= __('Mở Bot Telegram'); ?></strong>
                                <p class="text-muted small mb-2"><?= __('Nhấn nút bên dưới để mở Bot'); ?></p>
                                <a href="#" class="btn btn-primary btn-sm" id="btnOpenTelegramBot" target="_blank">
                                    <i class="fa-brands fa-telegram me-1"></i>
                                    <?= __('Mở Bot Telegram'); ?>
                                </a>
                            </div>
                        </div>

                        <div class="telegram-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <strong><?= __('Gửi lệnh liên kết'); ?></strong>
                                <p class="text-muted small mb-2"><?= __('Sao chép và gửi lệnh sau cho Bot:'); ?></p>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="telegramLinkCode"
                                        value="/link <?= htmlspecialchars($getUser['api_key']); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" id="btnCopyLinkCode">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                                <small class="text-danger mt-1 d-block">
                                    <i class="fa-solid fa-shield-halved me-1"></i>
                                    <?= __('Không chia sẻ mã này với người khác!'); ?>
                                </small>
                            </div>
                        </div>

                        <div class="telegram-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <strong><?= __('Hoàn tất'); ?></strong>
                                <p class="text-muted small mb-0"><?= __('Sau khi Bot xác nhận thành công, tải lại trang này.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="custom-btn custom-btn-secondary" onclick="closeTelegramModal()">
                <i class="fa-solid fa-xmark"></i>
                <?= __('Đóng'); ?>
            </button>
            <?php if (empty($getUser['telegram_chat_id'])): ?>
                <button type="button" class="custom-btn custom-btn-primary" onclick="location.reload()">
                    <i class="fa-solid fa-rotate"></i>
                    <?= __('Tải lại trang'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    // ===== Custom Modal Functions (Pure JS) =====
    function openProfileModal() {
        var overlay = document.getElementById('profileModalOverlay');
        overlay.style.display = 'flex';
        overlay.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeProfileModal() {
        var overlay = document.getElementById('profileModalOverlay');
        overlay.classList.remove('active');
        overlay.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    function openTelegramModal() {
        var overlay = document.getElementById('telegramModalOverlay');
        overlay.style.display = 'flex';
        overlay.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeTelegramModal() {
        var overlay = document.getElementById('telegramModalOverlay');
        overlay.classList.remove('active');
        overlay.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    // Close modals when clicking outside or pressing Escape
    document.addEventListener('DOMContentLoaded', function() {
        var profileOverlay = document.getElementById('profileModalOverlay');
        var telegramOverlay = document.getElementById('telegramModalOverlay');

        profileOverlay.addEventListener('click', function(e) {
            if (e.target === profileOverlay) closeProfileModal();
        });

        telegramOverlay.addEventListener('click', function(e) {
            if (e.target === telegramOverlay) closeTelegramModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (profileOverlay.classList.contains('active')) closeProfileModal();
                if (telegramOverlay.classList.contains('active')) closeTelegramModal();
            }
        });
    });

    // Lấy thông tin Bot Telegram
    $(document).ready(function() {
        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'getTelegramBotInfo',
                token: "<?= $getUser['token']; ?>"
            },
            success: function(response) {
                if (response.status == 'success') {
                    $('#btnOpenTelegramBot').attr('href', 'https://t.me/' + response.bot_username);
                } else {
                    $('#btnOpenTelegramBot').hide();
                    $('.telegram-link-guide').prepend(
                        '<div class="alert alert-warning">' + response.msg + '</div>'
                    );
                }
            }
        });
    });

    // Copy link code
    $('#btnCopyLinkCode').on('click', function() {
        var code = $('#telegramLinkCode').val();
        navigator.clipboard.writeText(code).then(function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?= __('Đã sao chép!'); ?>',
                showConfirmButton: false,
                timer: 1500
            });
        });
    });

    // Hủy liên kết Telegram
    $('#btnUnlinkTelegram').on('click', function() {
        Swal.fire({
            title: '<?= __('Hủy liên kết Telegram?'); ?>',
            text: '<?= __('Bạn sẽ không nhận được thông báo qua Telegram nữa.'); ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<?= __('Hủy liên kết'); ?>',
            cancelButtonText: '<?= __('Đóng'); ?>'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('ajaxs/client/auth.php'); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        action: 'unlinkTelegram',
                        token: "<?= $getUser['token']; ?>"
                    },
                    success: function(response) {
                        if (response.status == 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '<?= __('Thành công!'); ?>',
                                text: response.msg
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('<?= __('Lỗi'); ?>', response.msg, 'error');
                        }
                    }
                });
            }
        });
    });

    // Save Profile
    $("#btnSaveProfile").on("click", function() {
        var phone = $("#phone").val();
        var fullname = $("#fullname").val();

        if (phone && !/^[0-9+\-\s()]+$/.test(phone)) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Số điện thoại không hợp lệ'); ?>', 'error');
            return;
        }

        if (fullname && fullname.length > 100) {
            Swal.fire('<?= __('Error'); ?>', '<?= __('Họ và tên không được vượt quá 100 ký tự'); ?>', 'error');
            return;
        }

        $('#btnSaveProfile').html('<span><i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?></span>')
            .prop('disabled', true);

        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'ChangeProfile',
                token: $("#token").val(),
                phone: phone,
                fullname: fullname
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    Swal.fire({
                        title: '<?= __('Successful!'); ?>',
                        text: respone.msg,
                        icon: 'success',
                        confirmButtonText: '<?= __('Đóng'); ?>'
                    }).then((result) => {
                        if (result.isConfirmed || result.isDismissed) {
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }
                $('#btnSaveProfile').html(
                    '<span><i class="fa-solid fa-floppy-disk"></i> <?= __('Lưu thay đổi'); ?></span>'
                ).prop('disabled', false);
            },
            error: function() {
                Swal.fire('<?= __('Error'); ?>', '<?= __('Không thể xử lý'); ?>', 'error');
                $('#btnSaveProfile').html(
                    '<span><i class="fa-solid fa-floppy-disk"></i> <?= __('Lưu thay đổi'); ?></span>'
                ).prop('disabled', false);
            }
        });
    });
</script>




<?php
require_once(__DIR__ . '/footer.php');
?>