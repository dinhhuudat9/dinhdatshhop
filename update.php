<?php
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/libs/helper.php');
require_once(__DIR__ . '/config.php');
$CMSNT = new DB();


$whitelist = array(
    '127.0.0.1',
    '::1'
);

$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);


if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
    die('Localhost không thể sử dụng chức năng này');
}
if ($CMSNT->site('status_update') == 1) {
    // Lấy phiên bản cũ
    $old_version = $config['version'];
    // Lấy phiên bản mới từ API
    $new_version = curl_get_contents('http://api.cmsnt.co/version.php?version=SHOPKEY', 3);

    // API sập hoặc timeout → bỏ qua
    if (empty($new_version) || $new_version === false) {
        die('Không thể kiểm tra phiên bản mới. Vui lòng thử lại sau.');
    }

    if ($old_version != $new_version) {
        //CONFIG THÔNG SỐ
        define('filename', 'update_' . random('ABC123456789', 6) . '.zip');
        $license_key = $CMSNT->site('license_key');
        define('serverfile', 'https://api.cmsnt.co/update_path_code.php?license=' . urlencode($license_key) . '&type=SHOPKEY&domain=' . urlencode($_SERVER['SERVER_NAME']));
        // TIẾN HÀNH TẢI BẢN CẬP NHẬT TỪ SERVER VỀ
        file_put_contents(filename, curl_get_contents(serverfile, 30));
        // TIẾN HÀNH GIẢI NÉN BẢN CẬP NHẬT VÀ GHI ĐÈ VÀO HỆ THỐNG
        $file = filename;
        $path = pathinfo(realpath($file), PATHINFO_DIRNAME);
        $zip = new ZipArchive();
        $res = $zip->open($file);
        if ($res === true) {
            $zip->extractTo($path);
            $zip->close();
            // XÓA FILE ZIP CẬP NHẬT TRÁNH TỤI KHÔNG MUA ĐÒI XÀI FREE
            unlink(filename);
            // TIẾN HÀNH INSTALL DATABASE MỚI
            $query = file_get_contents(BASE_URL('install.php'), false, stream_context_create($arrContextOptions));
            // XÓA FILE INSTALL DATABASE
            if ($query) {
                unlink('install.php');
            }

            // GHI LOG VÀO DATABASE
            $log_action = "Cập nhật hệ thống từ phiên bản {$old_version} lên phiên bản {$new_version}";
            $CMSNT->insert("logs", [
                'user_id'   => 0,
                'ip'        => myip(),
                'device'    => getUserAgent(),
                'createdate' => gettime(),
                'action'    => $log_action
            ]);

            die('Cập nhật thành công!');
        } else {
            die('Cập nhật thất bại!');
        }
    }
    die('Không có phiên bản mới nhất');
} else {
    die('Chức năng cập nhật tự động đang được tắt');
}
