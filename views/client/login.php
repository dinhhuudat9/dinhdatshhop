<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Xử lý redirect URL - đơn giản hóa
$redirect_url = '';
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect_param = urldecode($_GET['redirect']);
    // Chỉ cho phép URL trong cùng domain hoặc relative path
    $site_url = rtrim(BASE_URL(''), '/');
    // Kiểm tra đơn giản - bắt đầu bằng site URL hoặc /
    if (stripos($redirect_param, $site_url) === 0 || $redirect_param[0] === '/') {
        $redirect_url = $redirect_param;
    }
}
// JSON encode để dùng trong JS
$redirect_url_js = $redirect_url !== '' ? json_encode($redirect_url) : 'null';

$body = [
    'title' => __('Đăng nhập') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/user-auth.css">
<link rel="stylesheet" href="' . BASE_URL('public/client/') . 'css/wallet.css">
<link rel="stylesheet" href="' . BASE_URL('mod/css/') . 'auth.css">
' . renderCaptchaScripts('login') . '
';
$body['footer'] = '

';
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');



?>

<section class="py-5 inner-section profile-part">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">

                <div class="user-form-card">
                    <div class="user-form-title">
                        <h2><?= __('Đăng Nhập'); ?></h2>
                        <p><?= __('Vui lòng nhập thông tin đăng nhập'); ?></p>
                    </div>
                    <div class="user-form-group">

                        <form class="user-form">
                            <div class="form-group">
                                <input type="hidden" id="csrf_token" value="<?= generate_csrf_token(); ?>">
                                <input type="text" id="page-login-username" class="form-control" value="<?= $CMSNT->site('status_demo') == 1 ? 'admin' : ''; ?>"
                                    placeholder="<?= __('Vui lòng nhập username'); ?>" autocomplete="username">
                            </div>
                            <div class="form-group">
                                <input type="password" id="page-login-password" class="form-control" value="<?= $CMSNT->site('status_demo') == 1 ? '123456' : ''; ?>"
                                    placeholder="<?= __('Vui lòng nhập mật khẩu'); ?>" autocomplete="current-password">
                            </div>
                            <?php if (isCaptchaEnabledForModule('login')): ?>
                                <center class="mb-3" id="captcha-container">
                                    <?= renderCaptchaWidget('captcha-container', 'login'); ?>
                                </center>
                            <?php endif; ?>
                            <div class="form-button">
                                <button type="button" id="btnLoginPage"><?= __('Đăng Nhập'); ?></button>
                                <p><a href="<?= base_url('client/forgot-password'); ?>"><?= __('Quên mật khẩu?'); ?></a></p>
                            </div>

                            <?php if ($CMSNT->site('status_google_login') == 1): ?>
                                <?php
                                $client = new Google_Client();
                                $client->setClientId($CMSNT->site('google_login_client_id')); // Client ID của bạn
                                $client->setClientSecret($CMSNT->site('google_login_client_secret')); // Client Secret của bạn
                                $client->setRedirectUri(base_url('api/callback_google_login.php')); // URL callback
                                $client->addScope("email");
                                $client->addScope("profile");
                                if (session_status() !== PHP_SESSION_ACTIVE) {
                                    session_start();
                                }
                                try {
                                    $googleOauthState = bin2hex(random_bytes(32));
                                } catch (Exception $e) {
                                    $googleOauthState = md5(uniqid((string)mt_rand(), true));
                                }
                                $_SESSION['google_oauth_state'] = $googleOauthState;
                                $_SESSION['google_oauth_state_expires'] = time() + 300; // 5 phút
                                $_SESSION['google_oauth_intent'] = 'login';
                                $client->setState($googleOauthState);
                                $login_url = $client->createAuthUrl();
                                ?>
                                <div class="social-login-divider">
                                    <span><?= __('Hoặc đăng nhập với'); ?></span>
                                </div>

                                <div class="social-login-buttons">
                                    <a href="<?= $login_url; ?>" class="btn-google-signin">
                                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4" />
                                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.837.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.44 15.983 5.485 18 9.003 18z" fill="#34A853" />
                                            <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05" />
                                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.485 0 2.44 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335" />
                                        </svg>
                                        <span><?= __('Đăng nhập với Google'); ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="user-form-remind">
                    <p><?= __('Bạn chưa có tài khoản?'); ?> <a href="<?= base_url('client/register'); ?>"><?= __('Đăng Ký Ngay'); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</section>


<?php
require_once(__DIR__ . '/footer.php');
?>

<script type="text/javascript">
    $("#btnLoginPage").on("click", function() {
        $('#btnLoginPage').html('<i class="fa fa-spinner fa-spin"></i> <?= __('Đang xử lý...'); ?>').prop('disabled',
            true);
        <?php if (isCaptchaEnabledForModule('login')): ?>
            var __captchaVal = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
            if (!__captchaVal) {
                Swal.fire('<?= __('Failure!'); ?>', '<?= __('Vui lòng xác nhận Captcha'); ?>', 'error');
                $('#btnLoginPage').html('<?= __('Đăng Nhập'); ?>').prop('disabled', false);
                return;
            }
        <?php endif; ?>
        var ajaxData = {
            action: 'Login',
            csrf_token: $("#csrf_token").val(),
            username: $("#page-login-username").val(),
            password: $("#page-login-password").val(),
            redirect_url: <?= $redirect_url_js; ?> || ''
        };
        <?php if (isCaptchaEnabledForModule('login')): ?>
            ajaxData.captcha_response = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
            ajaxData.recaptcha = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : $("#g-recaptcha-response").val();
            ajaxData['cf-turnstile-response'] = (typeof getCaptchaResponse === 'function') ? getCaptchaResponse() : '';
        <?php endif; ?>

        $.ajax({
            url: "<?= base_url('ajaxs/client/auth.php'); ?>",
            method: "POST",
            dataType: "JSON",
            data: ajaxData,
            success: function(respone) {
                if (respone.status == 'success') {
                    // Ưu tiên redirect_url từ response, sau đó từ URL param
                    var defaultUrl = <?= !empty($redirect_url) ? $redirect_url_js : json_encode(BASE_URL('')); ?>;
                    var redirectUrl = respone.redirect_url || defaultUrl;
                    Swal.fire({
                        title: '<?= __('Successful!'); ?>',
                        text: respone.msg,
                        icon: 'success',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK',
                        timer: 1500,
                        timerProgressBar: true
                    }).then((result) => {
                        window.location.href = redirectUrl;
                    });
                } else if (respone.status == 'verify') {
                    Swal.fire('<?= __('Warning!'); ?>', respone.msg, 'warning');
                    setTimeout("location.href = '" + respone.url + "';", 2000);
                } else if (respone.status == 'verify_otp_mail') {
                    Swal.fire('<?= __('Warning!'); ?>', respone.msg, 'warning');
                    setTimeout("location.href = '" + respone.url + "';", 2000);
                } else if (respone.status == 'verify_2fa') {
                    Swal.fire('<?= __('Warning!'); ?>', respone.msg, 'warning');
                    setTimeout("location.href = '" + respone.url + "';", 2000);
                } else {
                    Swal.fire('<?= __('Failure!'); ?>', respone.msg, 'error');
                }

                <?php if ($CMSNT->site('google_analytics_status') == 1): ?>
                    gtag('event', 'login', {
                        method: 'Website Form'
                    });
                <?php endif ?>
                $('#btnLoginPage').html('<?= __('Đăng Nhập'); ?>').prop('disabled', false);
            },
            error: function() {
                showMessage('<?= __('Vui lòng liên hệ Developer'); ?>', 'error');
                $('#btnLoginPage').html('<?= __('Đăng Nhập'); ?>').prop('disabled', false);
            }

        });
    });
</script>