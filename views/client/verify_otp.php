<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Xác minh OTP') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/user-auth.css">
<link rel="stylesheet" href="' . BASE_URL('mod/css/') . 'auth.css">
' . renderCaptchaScripts('verify_otp') . '
';
$body['footer'] = '
<!-- particles js -->
<script src="' . BASE_URL('public/client/assets/') . 'libs/particles.js/particles.js"></script>
<!-- particles app js -->
<script src="' . BASE_URL('public/client/assets/') . 'js/pages/particles.app.js"></script>
<!-- password-addon init -->
<script src="' . BASE_URL('public/client/assets/') . 'js/pages/password-addon.init.js"></script>
';

if (isset($_GET['token'])) {
    $token = validate_alphanumeric($_GET['token'], 255);
    if ($token === false) {
        redirect(base_url('client/login'));
    }
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_otp_mail` = ? AND `token_otp_mail` IS NOT NULL", [$token]);
    if (!$getUser || empty($getUser['token_otp_mail'])) {
        redirect(base_url('client/login'));
    }
} else {
    redirect(base_url('client/login'));
}


require_once(__DIR__ . '/header.php');

require_once(__DIR__ . '/sidebar.php');



?>


<div class="main-content">
    <div class="page-content">
        <div class="auth-page-wrapper pt-5">
            <!-- auth page bg -->
            <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
                <div class="bg-overlay"></div>

                <div class="shape">
                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
                        viewBox="0 0 1440 120">
                        <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                    </svg>
                </div>
            </div>

            <!-- auth page content -->
            <div class="auth-page-content">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="text-center mt-sm-5 mb-4 text-white-50">
                                <div>
                                    <a href="<?= base_url(); ?>" class="d-inline-block auth-logo">
                                        <img src="<?= BASE_URL($CMSNT->site('logo_dark')); ?>" alt="" height="100">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end row -->

                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6 col-xl-5">
                            <div class="card mt-4">
                                <div class="card-body p-4">
                                    <div class="text-center mt-2">
                                        <h5 class="text-primary"><?= __('Xác Minh OTP'); ?></h5>
                                        <p class="text-muted"><?= __('Vui lòng kiểm tra hộp thư đến hoặc thư spam trong Email của bạn để lấy OTP đăng nhập'); ?></p>

                                        <lord-icon src="https://cdn.lordicon.com/rhvddzym.json" trigger="loop"
                                            colors="primary:#0ab39c" class="avatar-xl"></lord-icon>

                                    </div>

                                    <div class="alert border-0 alert-info text-center mb-2 mx-2" role="alert">
                                        <?= __('Nhập mã OTP từ email của bạn!'); ?>
                                    </div>
                                    <div class="p-2">
                                        <form>
                                            <input type="hidden" id="token_otp_mail" value="<?= $getUser['token_otp_mail']; ?>">
                                            <div class="mb-4">
                                                <label class="form-label"><?= __('Mã xác minh OTP'); ?></label>
                                                <input type="text" class="form-control" id="code"
                                                    placeholder="<?= __('Vui lòng nhập OTP'); ?>">
                                            </div>

                                            <?php if (isCaptchaEnabledForModule('verify_otp')): ?>
                                                <center class="mb-3" id="captcha-container">
                                                    <?= renderCaptchaWidget('captcha-container', 'verify_otp'); ?>
                                                </center>
                                            <?php endif; ?>

                                            <div class="text-center mt-4">
                                                <button class="btn btn-success w-100" type="button"
                                                    id="btnsubmit"><?= __('Xác minh'); ?></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <p class="mb-0"><?= __('Bạn chưa có tài khoản?'); ?> <a
                                        href="<?= BASE_URL('client/register'); ?>"
                                        class="fw-semibold text-primary text-decoration-underline"><?= __('Đăng Ký Ngay'); ?></a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- end row -->
                </div>
                <!-- end container -->
            </div>
            <!-- end auth page content -->
        </div>
    </div>
</div>


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

    $("#btnsubmit").on("click", function() {
        $('#btnsubmit').html('<i class="fa fa-spinner fa-spin"></i> <?= __('Đang xử lý...'); ?>').prop('disabled',
            true);

        const captchaResponse = getSafeCaptchaResponse();
        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'VerifyOTP',
                token_otp_mail: $("#token_otp_mail").val(),
                code: $("#code").val(),
                captcha_response: captchaResponse,
                recaptcha: captchaResponse, // Backward compatibility
                'cf-turnstile-response': captchaResponse // For Cloudflare
            },
            success: function(respone) {
                if (respone.status == 'success') {
                    var redirectUrl = respone.redirect_url || '<?= BASE_URL(''); ?>';
                    Swal.fire({
                        title: '<?= __('Successful!'); ?>',
                        text: respone.msg,
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.href = redirectUrl;
                        }
                    });
                    setTimeout(function() {
                        location.href = redirectUrl;
                    }, 100);
                } else if (respone.status == 'verify') {
                    Swal.fire('<?= __('Warning!'); ?>', respone.msg, 'warning');
                    setTimeout("location.href = '" + respone.url + "';", 2000);
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }
                $('#btnsubmit').html('<?= __('Submit'); ?>').prop('disabled', false);
            },
            error: function() {
                showMessage('<?= __('Vui lòng liên hệ Developer'); ?>', 'error');
                $('#btnsubmit').html('<?= __('Submit'); ?>').prop('disabled', false);
            }

        });
    });
</script>