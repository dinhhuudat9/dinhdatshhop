<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Xác minh 2FA') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/user-auth.css">
<link rel="stylesheet" href="' . BASE_URL('mod/css/') . 'auth.css">
' . renderCaptchaScripts('verify_2fa') . '
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
    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token_2fa` = ? AND `token_2fa` IS NOT NULL", [$token]);
    if (!$getUser || empty($getUser['token_2fa'])) {
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
                                        <h5 class="text-primary"><?= __('Xác Minh 2FA'); ?></h5>
                                        <p class="text-muted"><?= __('Nhập mã xác minh mà bạn dùng để bật 2FA vào ô dưới đây để xác minh đăng nhập hợp lệ'); ?></p>

                                        <lord-icon src="https://cdn.lordicon.com/kfzfxczd.json" trigger="loop"
                                            colors="primary:#0ab39c" class="avatar-xl"></lord-icon>

                                    </div>

                                    <div class="alert border-0 alert-info text-center mb-2 mx-2" role="alert">
                                        <?= __('Nhập mã 6 số từ ứng dụng xác thực của bạn!'); ?>
                                    </div>
                                    <div class="p-2">
                                        <form>
                                            <input type="hidden" id="token_2fa" value="<?= $getUser['token_2fa']; ?>">
                                            <div class="mb-4">
                                                <label class="form-label"><?= __('Mã xác minh 2FA'); ?></label>
                                                <input type="text" class="form-control" id="code"
                                                    placeholder="<?= __('Vui lòng nhập mã xác minh'); ?>" maxlength="6">
                                            </div>

                                            <?php if (isCaptchaEnabledForModule('verify_2fa')): ?>
                                                <center class="mb-3" id="captcha-container">
                                                    <?= renderCaptchaWidget('captcha-container', 'verify_2fa'); ?>
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
        const $btn = $('#btnsubmit');
        $btn.html('<i class="fa fa-spinner fa-spin"></i> <?= __('Processing...'); ?>').prop('disabled', true);

        const captchaResponse = getSafeCaptchaResponse();
        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'Verify2FA',
                token_2fa: $("#token_2fa").val(),
                code: $("#code").val(),
                captcha_response: captchaResponse,
                recaptcha: captchaResponse, // Backward compatibility
                'cf-turnstile-response': captchaResponse // For Cloudflare
            },
            success: function(response) {
                if (response.status === 'success') {
                    var redirectUrl = response.redirect_url || '<?= BASE_URL(''); ?>';
                    Swal.fire({
                        title: '<?= __('Thành công!'); ?>',
                        text: response.msg,
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
                } else {
                    Swal.fire('<?= __('Thất bại!'); ?>', response.msg, 'error');
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