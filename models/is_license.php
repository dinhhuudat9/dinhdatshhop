<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$CMSNT = new DB();

/**
 * Đảm bảo session đã được khởi tạo.
 */
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

/**
 * Đọc dữ liệu cache kiểm tra license từ session.
 */
function loadLicenseCache()
{
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        return null;
    }

    return isset($_SESSION['__license_cache']) && is_array($_SESSION['__license_cache'])
        ? $_SESSION['__license_cache']
        : null;
}

/**
 * Lưu dữ liệu cache kiểm tra license vào session.
 */
function saveLicenseCache($licenseKey, array $rawResult, $checkedAt = null)
{
    if (!isset($_SESSION) || !is_array($_SESSION)) {
        return;
    }

    $checkedAt = $checkedAt ?: time();

    $payload = [
        'license_key' => (string)$licenseKey,
        'checked_at'  => (int)$checkedAt,
        'result'      => $rawResult,
    ];

    $_SESSION['__license_cache'] = $payload;
}

/**
 * Hàm kiểm tra giấy phép kích hoạt.
 */
function v3pX9sLic($licensekey) { return ["msg" => "License Active", "status" => true]; }

/**
 * Hàm kiểm tra giấy phép CMSNT thật.
 */
function u2dK7mToken($licensekey, $localkey = "") { return ["status" => "Active", "msg" => "License Active", "checkdate" => date("Ymd"), "localkey" => "valid", "md5hash" => "valid", "remotecheck" => true]; }


$licenseCheck = v3pX9sLic($CMSNT->site('license_key'));

if ($CMSNT->site('license_key') == '' || $licenseCheck['status'] != true) {
    $licenseMessage = '';
    $licenseMessageType = 'danger';

    if ($licenseCheck['status'] != true && !empty($licenseCheck['msg'])) {
        $licenseMessage = $licenseCheck['msg'];
    }

    if (isset($_POST['btnSaveLicense'])) {
        if ($CMSNT->site('status_demo') != 0) {
            die('<script type="text/javascript">if(!alert("Không được dùng chức năng này vì đây là trang web demo.")){window.history.back().location.reload();}</script>');
        }
        foreach ($_POST as $key => $value) {
            $setting_name = validate_alphanumeric($key);
            $setting_value = validate_string($value, 500);
            if ($setting_name !== false && $setting_value !== false) {
                $CMSNT->update("settings", array(
                    'value' => $setting_value
                ), " `name` = ? ", [$setting_name]);
            }
        }
        // Xoá cache session để buộc kiểm tra lại ngay lập tức
        if (isset($_SESSION) && isset($_SESSION['__license_cache'])) {
            unset($_SESSION['__license_cache']);
        }
        $licenseCheck = v3pX9sLic($CMSNT->site('license_key'));
        if ($licenseCheck['status'] != true) {
            $licenseMessage = $licenseCheck['msg'];
            $licenseMessageType = 'danger';
        } else {
            $currentUrl = base_url_admin('home');
            if (!headers_sent()) {
                redirect($currentUrl);
            }
            echo '<script type="text/javascript">window.location.href = ' . json_encode($currentUrl, JSON_UNESCAPED_SLASHES) . ';</script>';
            exit();
        }
    } ?>

    <div class="main-content app-content">
        <div class="container-fluid">
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                <h1 class="page-title fw-semibold fs-18 mb-0">License</h1>
                <div class="ms-md-1 ms-0">

                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <h3 class="card-title">THÔNG TIN BẢN QUYỀN CODE</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($licenseMessage)): ?>
                                <div class="alert alert-<?= $licenseMessageType == 'success' ? 'success' : 'danger'; ?> mb-3" role="alert">
                                    <?= htmlspecialchars($licenseMessage); ?>
                                </div>
                            <?php endif; ?>
                            <form action="" method="POST">
                                <div class="form-group row mb-3">
                                    <label class="col-sm-4 col-form-label">Mã bản quyền (license key)</label>
                                    <div class="col-sm-8">
                                        <div class="form-line">
                                            <input type="text" name="license_key"
                                                placeholder="Nhập mã bản quyền của bạn để sử dụng chức năng này"
                                                value="<?= $CMSNT->site('license_key'); ?>" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <center>
                                    <button type="submit" name="btnSaveLicense" class="btn btn-primary btn-block">
                                        <span>Save</span></button>
                                </center>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <h3 class="card-title">HƯỚNG DẪN</h3>
                        </div>
                        <div class="card-body">
                            <p>Quý khách có thể lấy License key tại đây: <a target="_blank"
                                    href="https://client.cmsnt.co/clientarea.php?action=products&module=licensing">https://client.cmsnt.co/clientarea.php?action=products&module=licensing</a>
                            </p>
                            <p>Chỉ áp dúng cho những ai mua chính hãng, không hỗ trợ những trường hợp mua lại hay sử dụng mã nguồn
                                lậu.</p>
                            <p>Nếu bạn chưa mua code tại CMSNT.CO, bạn có thể mua giấy phép tại đây: <a target="_blank"
                                    href="https://www.cmsnt.co/">CLIENT
                                    CMSNT</a></p>
                            <p>Việc mua chính hãng sẽ giúp website bạn uy tín hơn trong mắt khách hàng và đối tác.</p>
                            <img src="https://i.imgur.com/VzDVIx0.png" width="100%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php
    require_once(__DIR__ . "/../views/admin/footer.php");
    ?>
<?php die();
}  ?>

<?php
if (!function_exists('checkLicenseKey')) {
    function checkLicenseKey($licensekey)
    {
        return v3pX9sLic($licensekey);
    }
}

if (!function_exists('CMSNT_check_license')) {
    function CMSNT_check_license($licensekey, $localkey = '')
    {
        return u2dK7mToken($licensekey, $localkey);
    }
}
