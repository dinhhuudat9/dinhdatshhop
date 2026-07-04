<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quên mật khẩu') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/user-auth.css">
<link rel="stylesheet" href="' . BASE_URL('mod/css/') . 'auth.css">
' . renderCaptchaScripts('forgot_password') . '
';
$body['footer'] = '

';
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

                <div class="user-form-card">
                    <div class="user-form-title">
                        <h2><?= __('Bạn quên mật khẩu?'); ?></h2>
                        <p><?= __('Vui lòng nhập thông tin vào ô dưới đây để xác minh'); ?></p>
                    </div>
                    <form class="user-form">
                        <div class="form-group">
                            <input type="hidden" id="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="email" id="email" class="form-control"
                                placeholder="<?= __('Vui lòng nhập địa chỉ Email'); ?>">
                        </div>
                        <?php if (isCaptchaEnabledForModule('forgot_password')): ?>
                            <center class="mb-3" id="captcha-container">
                                <?= renderCaptchaWidget('captcha-container', 'forgot_password'); ?>
                            </center>
                        <?php endif; ?>
                        <div class="form-button"><button type="button"
                                id="btnForgotPassword"><?= __('Xác minh'); ?></button></div>
                    </form>
                </div>
                <div class="user-form-remind">
                    <p><?= __('Bạn đã có tài khoản?'); ?> <a href="<?= BASE_URL('client/login'); ?>"><?= __('Đăng Nhập'); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script type="text/javascript">
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

    $("#btnForgotPassword").on("click", function() {
        const $btn = $('#btnForgotPassword');
        const $email = $('#email').val();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?>').prop('disabled', true);

        const captchaResponse = getSafeCaptchaResponse();
        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'ForgotPassword',
                csrf_token: $("#csrf_token").val(),
                email: $email,
                captcha_response: captchaResponse,
                recaptcha: captchaResponse, // Backward compatibility
                'cf-turnstile-response': captchaResponse // For Cloudflare
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: '<?= __('Thành công!'); ?>',
                        text: response.msg,
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: '<?= __('Thất bại!'); ?>',
                        text: response.msg,
                        icon: 'error',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'OK'
                    });
                }
                $btn.html('<?= __('Xác minh'); ?>').prop('disabled', false);
            },
            error: function() {
                showMessage('<?= __('Không thể xử lý'); ?>', 'error');
                $btn.html('<?= __('Xác minh'); ?>').prop('disabled', false);
            }
        });
    });
</script>