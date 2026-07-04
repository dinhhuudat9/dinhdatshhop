<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$CMSNT = new DB;
date_default_timezone_set($CMSNT->site('timezone'));
$session_login = $CMSNT->site('session_login');
define('DEBUG', $CMSNT->site('debug_mode') == 1 ? true : false);
// Cấu hình session bảo mật
ini_set('session.gc_maxlifetime', $session_login);
ini_set('session.cookie_lifetime', $session_login);

ini_set('session.cookie_secure', '1'); // Chỉ gửi cookie qua HTTPS
ini_set('session.cookie_httponly', '1'); // Chặn truy cập cookie từ JavaScript
ini_set('session.cookie_samesite', 'None'); // Cho phép cookie trong iframe và cross-origin (cần HTTPS) 

// Cấu hình session ID bảo mật
ini_set('session.use_strict_mode', '1'); // Chỉ chấp nhận session ID do server tạo
ini_set('session.use_only_cookies', '1'); // Chỉ sử dụng cookie, không qua URL
ini_set('session.use_trans_sid', '0'); // Tắt session ID trong URL
session_start();

$_SERVER['SERVER_NAME'] = check_string($_SERVER['SERVER_NAME'] ?? '');
$_SERVER['HTTP_USER_AGENT'] = check_string($_SERVER['HTTP_USER_AGENT'] ?? '');
$_SERVER['REMOTE_ADDR'] = check_string($_SERVER['REMOTE_ADDR'] ?? '');
$_SERVER['REQUEST_URI'] = check_string($_SERVER['REQUEST_URI'] ?? '');
$_SERVER['REQUEST_METHOD'] = check_string($_SERVER['REQUEST_METHOD'] ?? '');
$_SERVER['HTTP_HOST'] = check_string($_SERVER['HTTP_HOST'] ?? '');





if ($CMSNT->get_row(" SELECT * FROM `block_ip` WHERE `ip` = '" . myip() . "' AND `banned` = 1 ")) {
    require_once(__DIR__ . '/../views/common/block-ip.php');
    exit();
}






/**
 * ⚡ CACHE SYSTEM - Tối ưu hiệu suất bằng cách cache dữ liệu tĩnh
 */

// Biến global để lưu cache
global $CACHE_DATA;
$CACHE_DATA = [];

/**
 * Lấy tất cả categories với cache
 * @return array Danh sách categories
 */
function get_categories_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['categories_all'])) {
        $CACHE_DATA['categories_all'] = $CMSNT->get_list_safe(
            "SELECT * FROM `categories` WHERE `status` = ? ORDER BY `stt` DESC",
            ['show']
        );
    }

    return $CACHE_DATA['categories_all'];
}

/**
 * Lấy categories parent (level 0) với cache
 * @return array Danh sách categories parent
 */
function get_categories_parent_cached()
{
    global $CACHE_DATA;

    if (!isset($CACHE_DATA['categories_parent'])) {
        $all_categories = get_categories_cached();
        $CACHE_DATA['categories_parent'] = array_filter($all_categories, function ($cat) {
            return $cat['parent_id'] == 0;
        });
    }

    return $CACHE_DATA['categories_parent'];
}

/**
 * Lấy categories con theo parent_id với cache
 * @param int $parent_id ID của category cha
 * @return array Danh sách categories con
 */
function get_categories_by_parent_cached($parent_id)
{
    global $CACHE_DATA;

    $cache_key = 'categories_parent_' . $parent_id;

    if (!isset($CACHE_DATA[$cache_key])) {
        $all_categories = get_categories_cached();
        $CACHE_DATA[$cache_key] = array_filter($all_categories, function ($cat) use ($parent_id) {
            return $cat['parent_id'] == $parent_id;
        });
    }

    return $CACHE_DATA[$cache_key];
}

/**
 * Lấy categories NOT parent (parent_id != 0) với cache
 * @return array Danh sách categories không phải parent
 */
function get_categories_not_parent_cached()
{
    global $CACHE_DATA;

    if (!isset($CACHE_DATA['categories_not_parent'])) {
        $all_categories = get_categories_cached();
        $CACHE_DATA['categories_not_parent'] = array_filter($all_categories, function ($cat) {
            return $cat['parent_id'] != 0;
        });
    }

    return $CACHE_DATA['categories_not_parent'];
}

/**
 * Lấy danh sách languages với cache
 * @return array Danh sách languages
 */
function get_languages_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['languages'])) {
        $CACHE_DATA['languages'] = $CMSNT->get_list_safe(
            "SELECT * FROM `languages` WHERE `status` = ?",
            [1]
        );
    }

    return $CACHE_DATA['languages'];
}

/**
 * Lấy danh sách currencies với cache
 * @return array Danh sách currencies
 */
function get_currencies_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['currencies'])) {
        $CACHE_DATA['currencies'] = $CMSNT->get_list_safe(
            "SELECT * FROM `currencies` WHERE `display` = ?",
            [1]
        );
    }

    return $CACHE_DATA['currencies'];
}

/**
 * Lấy danh sách payment manual với cache
 * @return array Danh sách payment manual
 */
function get_payment_manual_cached()
{
    global $CACHE_DATA, $CMSNT;

    if (!isset($CACHE_DATA['payment_manual'])) {
        $CACHE_DATA['payment_manual'] = $CMSNT->get_list_safe(
            "SELECT * FROM `payment_manual` WHERE `display` = ?",
            [1]
        );
    }

    return $CACHE_DATA['payment_manual'];
}


function get_friendly_user_agent($ua_string)
{
    if (empty($ua_string)) {
        return __('Thiết bị không xác định');
    }

    $ua_string_lower = strtolower($ua_string);

    // Phones
    if (strpos($ua_string_lower, 'iphone') !== false) {
        return __('Điện thoại iPhone');
    }
    if (strpos($ua_string_lower, 'android') !== false && strpos($ua_string_lower, 'mobile') !== false) {
        return __('Điện thoại Android');
    }
    if (strpos($ua_string_lower, 'windows phone') !== false) {
        return __('Điện thoại Windows');
    }

    // Tablets
    if (strpos($ua_string_lower, 'ipad') !== false) {
        return __('Máy tính bảng iPad');
    }
    if (strpos($ua_string_lower, 'android') !== false) {
        return __('Máy tính bảng Android');
    }

    // Desktops
    if (strpos($ua_string_lower, 'windows') !== false) {
        return __('Máy tính Windows');
    }
    if (strpos($ua_string_lower, 'macintosh') !== false || strpos($ua_string_lower, 'mac os x') !== false) {
        return __('Máy tính Mac');
    }
    if (strpos($ua_string_lower, 'linux') !== false) {
        return __('Máy tính Linux');
    }

    return __('Thiết bị không xác định');
}
/**
 * Kiểm tra IP có trong whitelist của user hay không
 * @param string $user_ip_whitelist - Danh sách IP whitelist của user (cách nhau bởi \n)
 * @param string $client_ip - IP của client cần kiểm tra
 * @return bool - true nếu IP hợp lệ hoặc whitelist trống, false nếu bị chặn
 */
function checkIPWhitelist($user_ip_whitelist, $client_ip)
{
    // Nếu không có whitelist thì cho phép tất cả IP
    if (empty($user_ip_whitelist)) {
        return true;
    }

    // Tách danh sách IP
    $allowed_ips = array_filter(explode("\n", $user_ip_whitelist), function ($ip) {
        return trim($ip) !== '';
    });

    // Chuẩn hóa IP client
    $client_ip = trim($client_ip);

    // Kiểm tra IP có trong danh sách không
    foreach ($allowed_ips as $allowed_ip) {
        $allowed_ip = trim($allowed_ip);
        if ($allowed_ip === $client_ip) {
            return true;
        }
    }

    return false;
}



function getListServiceType()
{
    global $CMSNT;
    return $CMSNT->get_list("SELECT * FROM `smm_service_types` ORDER BY `id` ASC");
}

function getServiceTypeByCode($code)
{
    global $CMSNT;
    return $CMSNT->get_row("SELECT * FROM `smm_service_types` WHERE `code` = '$code' ");
}

function getUserAgent(): string
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Làm sạch User-Agent để tránh XSS hoặc injection
    return htmlspecialchars(strip_tags($userAgent), ENT_QUOTES, 'UTF-8');
}


/**
 * Làm sạch nội dung HTML để chống XSS theo whitelist thẻ/thuộc tính cơ bản
 * Lưu ý: Không dùng cho data không phải HTML rich text.
 */
function sanitize_html_content($html)
{
    if ($html === null || $html === '') {
        return '';
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    // Chuẩn hóa encoding
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $allowedTags = [
        'a',
        'p',
        'br',
        'ul',
        'ol',
        'li',
        'b',
        'i',
        'strong',
        'em',
        'span',
        'div',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'table',
        'thead',
        'tbody',
        'tr',
        'th',
        'td',
        'blockquote',
        'code',
        'pre',
        'img',
        'hr',
        'small',
        'sup',
        'sub'
    ];
    $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'meta'];

    $allowedAttrsGlobal = ['class', 'id'];
    $allowedAttrsByTag = [
        'a'   => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height']
    ];

    $xpath = new DOMXPath($doc);
    // 1) Gỡ bỏ thẻ nguy hiểm
    foreach ($dangerousTags as $tag) {
        foreach ($xpath->query('//' . $tag) as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    // 2) Duyệt tất cả nodes, chỉ giữ lại thẻ trong whitelist và lọc thuộc tính
    $allNodes = $doc->getElementsByTagName('*');
    // Vì getElementsByTagName trả NodeList live, cần clone danh sách trước khi sửa
    $nodes = [];
    foreach ($allNodes as $n) {
        $nodes[] = $n;
    }

    foreach ($nodes as $node) {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            continue;
        }
        $tagName = strtolower($node->nodeName);
        if (!in_array($tagName, $allowedTags, true)) {
            // Loại bỏ node không cho phép (giữ nguyên text con nếu có)
            $parent = $node->parentNode;
            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
            continue;
        }

        // Lọc thuộc tính
        if ($node->hasAttributes()) {
            $removeAttrs = [];
            foreach ($node->attributes as $attr) {
                if (!($attr instanceof DOMAttr)) {
                    continue;
                }
                $attrName = strtolower($attr->name);
                $attrValue = trim($attr->value);

                // Chặn on* events, javascript: và dữ liệu nguy hiểm
                if (strpos($attrName, 'on') === 0) { // onload, onclick...
                    $removeAttrs[] = $attrName;
                    continue;
                }
                if (preg_match('/^javascript:/i', $attrValue)) {
                    $removeAttrs[] = $attrName;
                    continue;
                }

                // Chỉ cho phép một số thuộc tính theo tag
                $isAllowedGlobal = in_array($attrName, $allowedAttrsGlobal, true);
                $isAllowedForTag = isset($allowedAttrsByTag[$tagName]) && in_array($attrName, $allowedAttrsByTag[$tagName], true);
                if (!$isAllowedGlobal && !$isAllowedForTag) {
                    $removeAttrs[] = $attrName;
                    continue;
                }

                // Ràng buộc thêm cho href/src
                if ($tagName === 'a' && $attrName === 'href') {
                    // Cho phép placeholder {fanpage}... để hệ thống thay thế sau
                    $isPlaceholder = preg_match('/\{[a-z0-9_]+\}/i', $attrValue) === 1;
                    // Chỉ cho phép http, https, mailto, tel, # (anchor) nếu không phải placeholder
                    if (!$isPlaceholder && !preg_match('#^(https?:|mailto:|tel:|\#)#i', $attrValue)) {
                        if ($node instanceof DOMElement) {
                            $node->setAttribute('href', '#');
                        }
                    }
                    // Thêm rel="noopener noreferrer" nếu target=_blank
                    if ($node instanceof DOMElement && strtolower($node->getAttribute('target')) === '_blank') {
                        $node->setAttribute('rel', 'noopener noreferrer');
                    }
                }
                if ($tagName === 'img' && $attrName === 'src') {
                    $isPlaceholder = preg_match('/\{[a-z0-9_]+\}/i', $attrValue) === 1;
                    // Chỉ cho phép http/https hoặc data:image nếu không phải placeholder
                    if (!$isPlaceholder && !preg_match('#^(https?:|data:image\/(png|jpe?g|gif|webp);base64,)#i', $attrValue)) {
                        $removeAttrs[] = 'src';
                    }
                }
            }
            if ($node instanceof DOMElement) {
                foreach ($removeAttrs as $ra) {
                    $node->removeAttribute($ra);
                }
            }
        }
    }

    $clean = $doc->saveHTML();
    libxml_clear_errors();
    return $clean ?? '';
}


// Hàm getRankNameById - trả về "Thành viên" mặc định (đã xóa chức năng cấp bậc)
function getRankNameById($rank_id)
{
    return __('Thành viên');
}

/**
 * Lấy URL ảnh đại diện từ Gravatar dựa vào địa chỉ email.
 *
 * @param string|null $email Email của người dùng. Có thể là null nếu không tìm thấy email.
 * @param int $size Kích thước ảnh (pixel).
 * @param string $default Mã hoặc URL cho ảnh mặc định của Gravatar (vd: 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'blank', hoặc URL ảnh).
 * @param string $rating Giới hạn đánh giá ảnh (g, pg, r, x).
 * @return string URL ảnh Gravatar.
 */
function getGravatarUrl(?string $email, int $size = 80, string $default = 'mp', string $rating = 'g'): string
{
    global $CMSNT;
    if ($CMSNT->site('type_avatar') == 'ui-avatars') {
        return 'https://ui-avatars.com/api/?name=' . urlencode($email) . '&color=ffffff&background=1c8ef9';
    } else if ($CMSNT->site('type_avatar') == 'gravatar') {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?d=mp&s=' . $size;
    } else if ($CMSNT->site('type_avatar') == 'default') {
        return base_url($CMSNT->site('avatar'));
    }
    return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?d=mp&s=' . $size;
}
function deleteFolder($folderPath)
{
    if (!is_dir($folderPath)) {
        return false; // Thư mục không tồn tại
    }

    $files = array_diff(scandir($folderPath), ['.', '..']);

    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
    }

    return rmdir($folderPath);
}
function checkBlockIP($type, $time = 15)
{
    global $CMSNT;
    $ip_address = myip();
    if ($type == 'API') {
        $reason = __('Request API sai API KEY quá nhiều lần');
        $max_attempts = $CMSNT->site('limit_block_ip_api');  // Số lần thử tối đa
    } elseif ($type == 'LOGIN') {
        $reason = __('Đăng nhập thất bại quá nhiều lần');
        $max_attempts = $CMSNT->site('limit_block_ip_login');  // Số lần thử tối đa
    } elseif ($type == 'ADMIN') {
        $reason = __('Đăng nhập Admin thất bại quá nhiều lần');
        $max_attempts = $CMSNT->site('limit_block_ip_admin_access');  // Số lần thử tối đa
    } elseif ($type == 'RESET_PASSWORD') {
        $reason = __('Spam khôi phục mật khẩu');
        $max_attempts = $CMSNT->site('limit_block_ip_reset_password');  // Số lần thử tối đa
    } elseif ($type == 'OTP') {
        $reason = __('Spam OTP');
        $max_attempts = $CMSNT->site('limit_block_ip_otp');  // Số lần thử tối đa
    } elseif ($type == 'SEND_OTP') {
        $reason = __('Spam gửi OTP');
        $max_attempts = 10;  // Số lần thử tối đa
    } elseif ($type == '2FA') {
        $reason = __('Spam 2FA');
        $max_attempts = $CMSNT->site('limit_block_ip_2fa');  // Số lần thử tối đa
    } elseif ($type == 'PAYMENT') {
        $reason = __('Spam Tạo hóa đơn nạp tiền quá nhiều lần');
        $max_attempts = $CMSNT->site('limit_block_ip_payment');  // Số lần thử tối đa
    } else if ($type == 'IP_NOT_WHITELIST_API') {
        $reason = __('IP của bạn không nằm trong Whitelist API của User này');
        $max_attempts = $CMSNT->site('limit_block_ip_not_whitelist_api');  // Số lần thử tối đa
    } else if ($type == 'SCAN_TOKEN') {
        $reason = __('Spam scan token quá nhiều lần');
        $max_attempts = 10;  // Số lần thử tối đa
    } else if ($type == 'AI_TOOL_CMT') {
        $reason = __('Lạm dụng AI Tool Comment quá nhiều lần trong 15 phút');
        $max_attempts = 30;  // Số lần thử tối đa
    } else {
        $reason = __('Spam Request quá nhiều lần');
        $max_attempts = $CMSNT->site('limit_block_ip_spam');  // Số lần thử tối đa
    }
    if ($max_attempts == 0) {
        return false;
    }
    // Thêm log thất bại vào bảng failed_attempts
    $CMSNT->insert("failed_attempts", [
        'ip_address'        => $ip_address,
        'attempts'          => 1,
        'type'              => $type,
        'create_gettime'    => gettime()
    ]);
    // Đếm số lần thất bại trong 15 phút gần nhất
    $attempts = $CMSNT->get_row("SELECT COUNT(*) as total FROM `failed_attempts` 
        WHERE `ip_address` = '$ip_address' 
        AND `type` = '$type'
        AND `create_gettime` >= DATE_SUB(NOW(), INTERVAL $time MINUTE)");

    // Nếu số lần thất bại vượt quá giới hạn
    if ($attempts['total'] >= $max_attempts) {
        // Thêm vào danh sách block
        $CMSNT->insert('block_ip', [
            'ip' => $ip_address,
            'attempts' => $attempts['total'],
            'create_gettime' => gettime(),
            'banned' => 1,
            'reason' => __($reason)
        ]);
        // Xóa tất cả log thất bại của IP này
        $CMSNT->remove('failed_attempts', " `ip_address` = '$ip_address' AND `type` = '$type'");
        return json_encode(['status' => 'error', 'msg' => __('IP của bạn đã bị khóa. Vui lòng thử lại sau.')]);
    }
}

function checkDomainAPI($domain, $proxy = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cmsnt.co/checkdomain.php?domain={$domain}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);


    $data = curl_exec($ch);
    curl_close($ch);
    $checkdomain = json_decode($data, true);
    if ($checkdomain['status'] == false) {
        return [
            'msg' => $checkdomain['msg'],
            'status' => false
        ];
    }
    return [
        'msg' => '',
        'status' => true
    ];
}

function log_admin_request()
{
    global $CMSNT, $getUser;

    // Lấy thông tin cơ bản
    $request_url = check_string($_SERVER['REQUEST_URI']);
    $request_method = check_string($_SERVER['REQUEST_METHOD']);
    $ip = check_string($_SERVER['REMOTE_ADDR']);
    $user_agent = check_string($_SERVER['HTTP_USER_AGENT']);

    // Lấy tham số request (loại bỏ thông tin nhạy cảm)
    $params = [];
    if ($request_method === 'GET') {
        $params = $_GET;
    } elseif ($request_method === 'POST') {
        $params = $_POST;
    }

    // Xóa các trường nhạy cảm
    $filtered_params = array_filter($params, function ($key) {
        return !in_array(strtolower($key), ['password', 'token', 'csrf', 'api_key']);
    }, ARRAY_FILTER_USE_KEY);

    // Kiểm tra và xóa log cũ nếu vượt quá 10,000 bản ghi
    $total_logs = $CMSNT->get_row("SELECT COUNT(*) as total FROM `admin_request_logs`")['total'];
    $max_logs = 10000;

    if ($total_logs >= $max_logs) {
        // Tính số bản ghi cần xóa = (tổng hiện tại - max cho phép) + 1
        $delete_count = ($total_logs - $max_logs) + 1;

        // Xóa các bản ghi cũ nhất dựa trên ID
        $CMSNT->query("
            DELETE FROM `admin_request_logs` 
            WHERE id IN (
                SELECT id 
                FROM (
                    SELECT id 
                    FROM `admin_request_logs` 
                    ORDER BY id ASC 
                    LIMIT $delete_count
                ) AS temp
            )
        ");
    }

    // Chèn log mới
    $CMSNT->insert('admin_request_logs', [
        'user_id'           => $getUser['id'],
        'request_url'       => $request_url,
        'request_method'    => $request_method,
        'request_params'    => json_encode($filtered_params, JSON_UNESCAPED_UNICODE),
        'ip'                => $ip,
        'user_agent'        => $user_agent,
        'timestamp'         => gettime()
    ]);
}


function display_method_xipay($method)
{
    $method = htmlspecialchars($method);
    $output = '';

    switch (strtolower($method)) {
        case 'alipay':
            $output = '<span class="d-inline-flex align-items-center border rounded p-2">';
            $output .= '<i class="fab fa-alipay text-primary fa-2x me-2"></i>';
            $output .= '<span class="fs-7 text-primary">' . __('Alipay') . '</span>';
            $output .= '</span>';
            break;

        case 'wxpay':
            $output = '<span class="d-inline-flex align-items-center border rounded p-2">';
            $output .= '<i class="fab fa-weixin text-success fa-2x me-2"></i>';
            $output .= '<span class="fs-7 text-success">' . __('WeChat Pay') . '</span>';
            $output .= '</span>';
            break;

        default:
            break;
    }

    return $output;
}

function generateUltraSecureToken($length = 32)
{
    $randomBytes = random_bytes($length);
    return bin2hex($randomBytes);
}

/**
 * Tạo token có prefix là username đã xáo trộn để chống spam dò token
 * 
 * Ví dụ: username "admin" -> prefix có thể là "mdain", "nidma", "inamd"...
 * Token cuối cùng: "mdain" + random_token
 * 
 * @param string $username Username của người dùng
 * @param int $tokenLength Độ dài phần token ngẫu nhiên (mặc định 64 bytes = 128 hex chars)
 * @param int $maxLength Độ dài tối đa của token (mặc định 255 ký tự)
 * @return string Token với prefix username đã xáo trộn
 */
function generateUserToken(string $username, int $tokenLength = 64, int $maxLength = 255): string
{
    // Lấy username và loại bỏ ký tự đặc biệt, chỉ giữ alphanumeric
    $cleanUsername = preg_replace('/[^a-zA-Z0-9]/', '', $username);

    // Nếu username rỗng sau khi làm sạch, dùng fallback
    if (empty($cleanUsername)) {
        $cleanUsername = 'user';
    }

    // Xáo trộn các ký tự của username
    $usernameArray = str_split($cleanUsername);
    shuffle($usernameArray);
    $shuffledPrefix = implode('', $usernameArray);

    // Tính độ dài còn lại cho phần token random
    $prefixLength = strlen($shuffledPrefix);
    $availableLength = $maxLength - $prefixLength;

    // Đảm bảo có đủ chỗ cho token (tối thiểu 32 ký tự hex = 16 bytes)
    if ($availableLength < 32) {
        // Nếu username quá dài, cắt bớt prefix
        $shuffledPrefix = substr($shuffledPrefix, 0, $maxLength - 128);
        $availableLength = $maxLength - strlen($shuffledPrefix);
    }

    // Tính số bytes cần thiết (mỗi byte = 2 ký tự hex)
    $bytesNeeded = min($tokenLength, intval($availableLength / 2));

    // Tạo phần token ngẫu nhiên
    $randomBytes = random_bytes($bytesNeeded);
    $randomToken = bin2hex($randomBytes);

    // Kết hợp prefix và token
    return $shuffledPrefix . $randomToken;
}
function generateApiKey($length = 32)
{
    // Tạo chuỗi ngẫu nhiên với độ dài chỉ định (mặc định là 32)
    return bin2hex(random_bytes($length / 2)) . uniqid();
}
function generateRememberToken($currentToken, $storedIp)
{
    // Tạo token mới nếu token trống
    if (empty($currentToken)) {
        return bin2hex(random_bytes(64));
    }
    return $currentToken;
}

function isSecureCookie($name)
{
    if (isset($_COOKIE[$name])) {
        return true;
    } else {
        false;
    }
}


function insert_options($name, $value, $note = '')
{
    global $CMSNT;
    if (!$CMSNT->get_row("SELECT * FROM `settings` WHERE `name` = '$name' ")) {
        $CMSNT->insert("settings", [
            'name'  => $name,
            'value' => $value,
            'note' => $note
        ]);
    }
}


function removeSpaces($string)
{
    return str_replace(' ', '', $string);
}
function curl_get_contents($url, $timeout = 10)
{
    // Initialize a cURL session
    $ch = curl_init();
    // Set the URL to fetch
    curl_setopt($ch, CURLOPT_URL, $url);
    // Set the timeout for the request
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    // Return the transfer as a string instead of outputting it directly
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Optional: Set a user-agent to mimic a browser request
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');
    // Optional: Follow redirects (HTTP 3xx responses)
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Execute the request and store the result
    $result = curl_exec($ch);
    // Check for errors
    if (curl_errno($ch)) {
        // If there's an error, return false
        $result = false;
    }
    // Close the cURL session
    curl_close($ch);
    return $result;
}



function remove_html_tags($string)
{
    // Loại bỏ các thẻ ul và li
    $string = preg_replace('/<ul[^>]*>/', '', $string);
    $string = preg_replace('/<\/ul>/', '', $string);
    $string = preg_replace('/<li[^>]*>/', '', $string);
    $string = preg_replace('/<\/li>/', '', $string);

    // Loại bỏ các thẻ b và i
    $string = preg_replace('/<b[^>]*>/', '', $string);
    $string = preg_replace('/<\/b>/', '', $string);
    $string = preg_replace('/<i[^>]*>/', '', $string);
    $string = preg_replace('/<\/i>/', '', $string);

    // Trả về chuỗi đã loại bỏ các thẻ HTML
    return $string;
}

function admin_msg_success($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire({
        title: "Thành công!",
        text: "' . $text . '",
        icon: "success"
    });
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_error($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thất Bại", "' . $text . '","error");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function admin_msg_warning($text, $url, $time)
{
    return die('<script type="text/javascript">Swal.fire("Thông Báo", "' . $text . '","warning");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function debit_processing($user_id)
{
    $CMSNT = new DB();
    $User = new users();

    $getUser = $CMSNT->get_row(" SELECT * FROM `users` WHERE `id` = '$user_id' ");
    if ($getUser['debit'] > 0) {
        if ($getUser['money'] >= $getUser['debit']) {
            // ĐỦ TIỀN TRẢ NỢ
            $isTru = $CMSNT->tru('users', 'debit', $getUser['debit'], " `id` = '$user_id' ");
            if ($isTru) {
                $User->RemoveCredits($getUser['id'], $getUser['debit'], __('Thanh toán số tiền ghi nợ'));
                return true;
            }
        } else {
            // KHÔNG ĐỦ TIỀN
            $isTru = $CMSNT->tru('users', 'debit', $getUser['money'], " `id` = '$user_id' ");
            if ($isTru) {
                $User->RemoveCredits($getUser['id'], $getUser['money'], __('Thanh toán số tiền ghi nợ'));
                return true;
            }
        }
    }
    return false;
}
function checkPermission($admin_id, $role)
{
    global $CMSNT;
    // cấp độ cao nhất
    if ($admin_id == 99999) {
        return true;
    }
    // kiểm tra trong role
    if ($row = $CMSNT->get_row(" SELECT * FROM `admin_role` WHERE `id` = '$admin_id' ")) {
        if (in_array($role, json_decode($row['role'])) == true) {
            return true;
        }
    }
    return false;
}

// Lấy tên role từ config
function getRoleName($role_key)
{
    global $admin_roles;
    if (isset($admin_roles) && is_array($admin_roles)) {
        foreach ($admin_roles as $category => $roles) {
            if (isset($roles[$role_key])) {
                return $roles[$role_key];
            }
        }
    }
    return $role_key; // Trả về key nếu không tìm thấy
}

function getCurrencyRate()
{
    global $CMSNT;
    if (isset($_COOKIE['currency'])) {
        $currency = check_string($_COOKIE['currency']);
        $rowcurrency = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `id` = '$currency' AND `display` = 1 ");
        if ($rowcurrency) {
            return $rowcurrency['rate'];
        }
    }
    $rowcurrency = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `default_currency` = 1 ");
    if ($rowcurrency) {
        return $rowcurrency['rate'];
    }
    return false;
}
function getCurrencyNameDefault()
{
    return currencyDefault();
}
function currencyDefault()
{
    $CMSNT = new DB;
    return $CMSNT->get_row(" SELECT `code` FROM `currencies` WHERE `display` = 1 AND `default_currency` = 1")['code'];
}
function dirImageProduct($image)
{
    $path = 'assets/storage/images/products/' . $image;
    return $path;
}

function generate_csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra token có tồn tại không
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }

    // So sánh token bằng timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}


function csrf_field()
{
    return '<input type="hidden" id="csrf_token" value="' . generate_csrf_token() . '">';
}
function display_camp($status)
{
    if ($status == 0) {
        return '<span class="badge bg-info">Processing</span>';
    } elseif ($status == 1) {
        return '<span class="badge bg-success">Completed</span>';
    } elseif ($status == 2) {
        return '<span class="badge bg-danger">Cancel</span>';
    } else {
        return '<span class="badge bg-warning">Khác</span>';
    }
}
function display_withdraw($data)
{
    if ($data == 'pending') {
        $show = '<span class="badge bg-warning">Pending</span>';
    } elseif ($data == 'cancel') {
        $show = '<span class="badge bg-danger">Cancel</span>';
    } else if ($data == 'completed') {
        $show = '<span class="badge bg-success">Completed</span>';
    }
    return $show;
}
if (!function_exists('cal_days_in_month')) {
    function cal_days_in_month($calendar, $month, $year)
    {
        return date('t', mktime(0, 0, 0, $month, 1, $year));
    }
}
function setCurrency($id)
{
    global $CMSNT;
    if ($row = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `id` = '$id' AND `display` = 1 ")) {
        $isSet = setcookie('currency', $row['id'], time() + (31536000 * 30), "/"); // 31536000 = 365 ngày
        if ($isSet) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}
function getCurrency()
{
    global $CMSNT;
    if (isset($_COOKIE['currency'])) {
        $currency = check_string($_COOKIE['currency']);
        $rowcurrency = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `id` = '$currency' AND `display` = 1 ");
        if ($rowcurrency) {
            return $rowcurrency['id'];
        }
    }
    $rowcurrency = $CMSNT->get_row("SELECT * FROM `currencies` WHERE `default_currency` = 1 ");
    if ($rowcurrency) {
        return $rowcurrency['id'];
    }
    return false;
}
function display_status_support_tickets($data)
{
    global $config_status_support_tickets;

    $badge_classes = [
        'open'      => 'badge bg-info-subtle text-info',
        'pending'   => 'badge bg-warning-subtle text-warning',
        'answered'  => 'badge bg-success-subtle text-success',
        'closed'    => 'badge bg-danger-subtle text-danger'
    ];

    if (isset($badge_classes[$data])) {
        $badge_class = $badge_classes[$data];
        $text = isset($config_status_support_tickets[$data]) ? $config_status_support_tickets[$data] : __($data);

        $show = '<span class="' . $badge_class . '">' . $text . '</span>';
    } else {
        $show = '<span class="badge bg-secondary-subtle text-secondary">' . __($data) . '</span>';
    }

    return $show;
}

function display_service($data)
{
    global $config_status_order;

    $badge_classes = [
        'Pending' => 'badge bg-warning',
        'Canceled' => 'badge bg-danger',
        'Completed' => 'badge bg-success',
        'In progress' => 'badge bg-info',
        'Processing' => 'badge bg-secondary',
        'Partial' => 'badge bg-danger'
    ];

    if (isset($badge_classes[$data])) {
        $badge_class = $badge_classes[$data];
        $text = isset($config_status_order[$data]) ? $config_status_order[$data] : __($data);
        $show = '<span class="' . $badge_class . '">' . $text . '</span>';
    } else {
        $show = '<span class="badge bg-secondary">' . __($data) . '</span>';
    }

    return $show;
}
function display_invoice($data)
{
    if ($data == 'waiting') {
        $show = '<span class="badge bg-warning">' . __('Chưa thanh toán') . '</span>';
    } elseif ($data == 'expired') {
        $show = '<span class="badge bg-danger">' . __('Hết hạn') . '</span>';
    } else if ($data == 'completed') {
        $show = '<span class="badge bg-success">' . __('Đã thanh toán') . '</span>';
    } else if ($data == 0) {
        $show = '<span class="badge bg-warning">Waiting</span>';
    } else if ($data == 2) {
        $show = '<span class="badge bg-danger">Expired</span>';
    } else if ($data == 1) {
        $show = '<span class="badge bg-success">Completed</span>';
    }
    return $show;
}

function is_valid_domain_name($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) && preg_match("/^.{1,253}$/", $domain_name) && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name));
}
function display_domains($data)
{
    if ($data == 1) {
        $show = '<span class="badge bg-success">' . __('Hoạt Động') . '</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-warning">' . __('Đang Xây Dựng') . '</span>';
    } elseif ($data == 2) {
        $show = '<span class="badge bg-danger">' . __('Huỷ') . '</span>';
    }
    return $show;
}

function sendMessAdmin($my_text)
{
    if ($my_text != '') {
        return sendMessTelegram($my_text);
    }
    return false;
}
function sendMessTelegram($my_text, $token = '', $chat_id = '')
{
    $CMSNT = new DB;
    if ($chat_id == '') {
        $chat_id = $CMSNT->site('telegram_chat_id');
    }
    if ($token == '') {
        $token = $CMSNT->site('telegram_token');
    }
    if ($my_text == '') {
        return false;
    }
    if ($CMSNT->site('telegram_status') == 1) {
        if ($token != '' && $chat_id != '') {
            // Sử dụng CDN Cloudflare để tránh bị chặn bởi nhà mạng Việt Nam
            $telegram_url = $CMSNT->site('telegram_url') . 'bot' . $token . '/sendMessage';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $telegram_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('chat_id' => $chat_id, 'text' => $my_text, 'parse_mode' => 'Markdown')));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4); // Timeout tổng 6 giây
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Timeout kết nối 5 giây
            $response = curl_exec($ch);
            curl_close($ch);

            // GHI LOG
            $CMSNT->insert('bot_telegram_logs', [
                'chat_id' => $chat_id,
                'message'   => $my_text,
                'token'     => $token,
                'response'  => $response,
                'created_at' => gettime()
            ]);
            return $response;
        }
    }
    return false;
}
function checkFormatCard($type, $seri, $pin)
{
    $seri = strlen($seri);
    $pin = strlen($pin);
    $data = [];
    if ($type == 'Viettel' || $type == "viettel" || $type == "VT" || $type == "VIETTEL") {
        if ($seri != 11 && $seri != 14) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 13 && $pin != 15) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Mobifone' || $type == "mobifone" || $type == "Mobi" || $type == "MOBIFONE") {
        if ($seri != 15) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'VNMB' || $type == "Vnmb" || $type == "VNM" || $type == "VNMOBI") {
        if ($seri != 16) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Vinaphone' || $type == "vinaphone" || $type == "Vina" || $type == "VINAPHONE") {
        if ($seri != 14) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 14) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Garena' || $type == "garena") {
        if ($seri != 9) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 16) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Zing' || $type == "zing" || $type == "ZING") {
        if ($seri != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 9) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    if ($type == 'Vcoin' || $type == "VTC") {
        if ($seri != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài seri không phù hợp'
            ];
            return $data;
        }
        if ($pin != 12) {
            $data = [
                'status'    => false,
                'msg'       => 'Độ dài mã thẻ không phù hợp'
            ];
            return $data;
        }
    }
    $data = [
        'status'    => true,
        'msg'       => 'Success'
    ];
    return $data;
}
function active_sidebar_client($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'active';
        }
    }
    return '';
}
function show_sidebar_client($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'show';
        }
    }
    return '';
}
function show_sidebar($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'active open';
        }
    }
    return '';
}

function parse_order_id($des, $MEMO_PREFIX)
{
    $re = '/' . $MEMO_PREFIX . '\d+/im';
    preg_match_all($re, $des, $matches, PREG_SET_ORDER, 0);
    if (count($matches) == 0) {
        return null;
    }
    // Print the entire match result
    $orderCode = $matches[0][0];
    $prefixLength = strlen($MEMO_PREFIX);
    $orderId = intval(substr($orderCode, $prefixLength));
    return $orderId;
}
function display_status_toyyibpay($status)
{
    if ($status == 0) {
        return '<b style="color:#db7e06;">' . __('Waiting') . '</b>';
    } elseif ($status == 'confirming') {
        return '<b style="color:blue;">' . __('Confirming') . '</b>';
    } elseif ($status == 'confirmed') {
        return '<b style="color:green;">' . __('Confirmed') . '</b>';
    } elseif ($status == 'refunded') {
        return '<b style="color:pink;">' . __('Refunded') . '</b>';
    } elseif ($status == 'expired') {
        return '<b style="color:red;">' . __('Expired') . '</b>';
    } elseif ($status == 2) {
        return '<b style="color:red;">' . __('Failed') . '</b>';
    } elseif ($status == 'partially_paid') {
        return '<b style="color:green;">' . __('Partially Paid') . '</b>';
    } elseif ($status == 1) {
        return '<b style="color:green;">' . __('Finished') . '</b>';
    }
}
// function display_status_crypto($status)
// {
//     if ($status == 'waiting') {
//         return '<b style="color:#db7e06;">'.__('Waiting').'</b>';
//     } elseif ($status == 'confirming') {
//         return '<b style="color:blue;">'.__('Confirming').'</b>';
//     } elseif ($status == 'confirmed') {
//         return '<b style="color:green;">'.__('Confirmed').'</b>';
//     } elseif ($status == 'refunded') {
//         return '<b style="color:pink;">'.__('Refunded').'</b>';
//     } elseif ($status == 'expired') {
//         return '<b style="color:red;">'.__('Expired').'</b>';
//     } elseif ($status == 'failed') {
//         return '<b style="color:red;">'.__('Failed').'</b>';
//     } elseif ($status == 'partially_paid') {
//         return '<b style="color:green;">'.__('Partially Paid').'</b>';
//     } elseif ($status == 'finished') {
//         return '<b style="color:green;">'.__('Finished').'</b>';
//     }
// }
function display_card($status)
{
    if ($status == 'pending') {
        return '<span class="badge bg-info">' . __('Đang chờ xử lý') . '</span>';
    } elseif ($status == 'completed') {
        return '<span class="badge bg-success">' . __('Thành công') . '</span>';
    } elseif ($status == 'error') {
        return '<span class="badge bg-danger">' . __('Thất bại') . '</span>';
    } else {
        return '<span class="badge bg-warning">Khác</span>';
    }
}
function display_invoice_text($status)
{
    if ($status == 0) {
        return __('Đang chờ thanh toán');
    } elseif ($status == 1) {
        return __('Đã thanh toán');
    } elseif ($status == 2) {
        return __('Huỷ bỏ');
    } else {
        return __('Khác');
    }
}
// lấy dữ liệu theo thời gian thực
function getRowRealtime($table, $id, $row)
{
    global $CMSNT;
    if ($data = $CMSNT->get_row("SELECT `" . $row . "` FROM `$table` WHERE `id` = '$id' ")) {
        return $data[$row];
    }
    return false;
}

function get_url()
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $url = "https://";
    } else {
        $url = "http://";
    }
    $url .= $_SERVER['HTTP_HOST'];
    $url .= $_SERVER['REQUEST_URI'];
    return $url;
}
function url()
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị SERVER_NAME hoặc HTTP_HOST
    $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của host
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Sử dụng domain mặc định nếu không hợp lệ
    }

    // Nếu host không nằm trong danh sách domains, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0];
    }

    // Xác định giao thức (HTTPS hoặc HTTP)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https' : 'http';

    // Làm sạch REQUEST_URI để tránh lỗi XSS
    $uri = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');

    // Trả về URL đầy đủ
    return sprintf("%s://%s%s", $protocol, $host, $uri);
}

function base_url($url = '')
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị HTTP_HOST
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của HTTP_HOST
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Domain mặc định nếu HTTP_HOST không hợp lệ
    }

    // Nếu HTTP_HOST không nằm trong danh sách, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0]; // Domain mặc định
    }

    // Xác định giao thức (HTTPS hoặc HTTP)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // Xử lý localhost riêng (nếu cần)
    if ($host === 'localhost') {
        $base = 'http://localhost/CMSNT.CO/SHOPKEY';
    } else {
        $base = $protocol . '://' . $host;
    }

    // Trả về URL đầy đủ
    return check_string($base) . '/' . ltrim($url, '/');
}

function base_url_admin($url = '')
{
    global $CMSNT;

    // Lấy danh sách domains từ database
    $allowed_domains = array_map('trim', explode(',', $CMSNT->site('domains'))); // Làm sạch danh sách domains

    // Lấy giá trị HTTP_HOST
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Kiểm tra tính hợp lệ của HTTP_HOST
    if (!preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        $host = $allowed_domains[0]; // Domain mặc định nếu HTTP_HOST không hợp lệ
    }

    // Nếu HTTP_HOST không nằm trong danh sách, sử dụng domain đầu tiên
    if (!in_array($host, $allowed_domains)) {
        $host = $allowed_domains[0]; // Domain mặc định
    }

    // Xác định giao thức (HTTPS hoặc HTTP)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // Xử lý localhost riêng (nếu cần)
    if ($host === 'localhost') {
        $base = 'http://localhost/CMSNT.CO/SHOPKEY';
    } else {
        $base = $protocol . '://' . $host;
    }

    // Kiểm tra và bảo toàn giá trị URL
    $final_url = rtrim(check_string($base), '/') . '/?module=' . $CMSNT->site('path_admin') . '&action=' . $url;

    // Trả về URL đầy đủ
    return $final_url;
}



// mã hoá password
function TypePassword($password)
{
    $CMSNT = new DB();
    if ($CMSNT->site('type_password') == 'md5') {
        return md5($password);
    }
    if ($CMSNT->site('type_password') == 'bcrypt') {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    if ($CMSNT->site('type_password') == 'argon2id') {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    if ($CMSNT->site('type_password') == 'sha1') {
        return sha1($password);
    }
    return $password;
}
// lấy thông tin user theo id
function getUser($id, $row)
{
    $CMSNT = new DB();
    return $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '$id' ")[$row];
}
function validateUsername($username)
{
    // Loại bỏ khoảng trắng đầu/cuối
    $username = trim($username);
    // Kiểm tra username chỉ chứa chữ cái, số, và có độ dài từ 3-20 ký tự
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9]{2,19}$/', $username)) {
        return htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); // Bảo vệ chống XSS
    }
    return false; // Không hợp lệ
}
function validateEmail($email)
{
    // Loại bỏ khoảng trắng đầu/cuối
    $email = trim($email);

    // Kiểm tra email bằng filter_var
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); // Bảo vệ chống XSS
    }
    return false; // Không hợp lệ
}
// kiểm tra độ mạnh mật khẩu
function validatePasswordStrength($password)
{
    // Không cắt/biến đổi giá trị mật khẩu, chỉ kiểm tra điều kiện
    // Yêu cầu tối thiểu: 8 ký tự, có chữ thường, chữ hoa và chữ số
    if (!is_string($password)) {
        return false;
    }
    $lengthIsValid = (strlen($password) >= 8);
    $hasLowercase = (bool)preg_match('/[a-z]/', $password);
    $hasUppercase = (bool)preg_match('/[A-Z]/', $password);
    $hasDigit     = (bool)preg_match('/\d/', $password);

    // Có thể mở rộng: $hasSpecial = (bool)preg_match('/[^a-zA-Z0-9]/', $password);

    if ($lengthIsValid && $hasLowercase && $hasUppercase && $hasDigit) {
        return true;
    }
    return false;
}
// check định dạng số điện thoại
function validatePhone($data)
{
    if (preg_match('/^\+?(\d.*){3,}$/', $data, $matches)) {
        return true;
    } else {
        return false;
    }
}
// get datatime
function gettime()
{
    return date('Y/m/d H:i:s', time());
}

function format_currency2($amount)
{
    $CMSNT = new DB();
    $currency = $CMSNT->site('currency');
    if ($currency == 'USD') {
        return '$' . number_format($amount / $CMSNT->site('usd_rate'), 2, '.', '');
    } elseif ($currency == 'VND') {
        return format_cash($amount) . 'đ';
    } elseif ($currency == 'THB') {
        return format_cash($amount / 645.36) . ' THB';
    }
}

function format_currency($amount)
{
    $amount = validate_float($amount);
    $CMSNT = new DB();
    if (isset($_COOKIE['currency'])) {
        $currency = validate_int($_COOKIE['currency'], 1);
        if ($currency !== false) {
            $rowCurrency = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `id` = ? AND `display` = 1", [$currency]);
            if ($rowCurrency) {
                if ($rowCurrency['seperator'] == 'comma') {
                    $seperator = ',';
                }
                if ($rowCurrency['seperator'] == 'space') {
                    $seperator = '';
                }
                if ($rowCurrency['seperator'] == 'dot') {
                    $seperator = '.';
                }
                return $rowCurrency['symbol_left'] . number_format($amount / $rowCurrency['rate'], $rowCurrency['decimal_currency'], '.', $seperator) . $rowCurrency['symbol_right'];
            }
        }
    }
    $rowCurrency = $CMSNT->get_row_safe("SELECT * FROM `currencies` WHERE `default_currency` = 1", []);
    if ($rowCurrency) {
        if ($rowCurrency['seperator'] == 'comma') {
            $seperator = ',';
        }
        if ($rowCurrency['seperator'] == 'space') {
            $seperator = '';
        }
        if ($rowCurrency['seperator'] == 'dot') {
            $seperator = '.';
        }
        return $rowCurrency['symbol_left'] . number_format($amount / $rowCurrency['rate'], $rowCurrency['decimal_currency'], '.', $seperator) . $rowCurrency['symbol_right'];
    }
    return format_cash($amount) . 'đ';
}
//show ip
// function myip(){
//     if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
//         $ip_address = $_SERVER['HTTP_CLIENT_IP'];
//     } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
//         $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
//     } else {
//         $ip_address = $_SERVER['REMOTE_ADDR'];
//     }
//     if(isset(explode(',', $ip_address)[1])){
//         return explode(',', $ip_address)[0];
//     }
//     return check_string($ip_address);
// }

function myip()
{
    // Địa chỉ IP mặc định (REMOTE_ADDR)
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    // Kiểm tra các header khác (nếu có)
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Lấy danh sách IP từ X-Forwarded-For
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip_list = array_map('trim', $ip_list); // Loại bỏ khoảng trắng thừa

        // Lấy địa chỉ IP đầu tiên hợp lệ
        foreach ($ip_list as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ip_address = $ip;
                break;
            }
        }
    }
    // Kiểm tra và trả về địa chỉ IP đã xác thực
    return filter_var($ip_address, FILTER_VALIDATE_IP) ? $ip_address : '0.0.0.0';
}


// Làm sạch dữ liệu
function check_string($data)
{
    // Tránh double encoding bằng cách decode trước khi encode lại
    $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
    return trim(htmlspecialchars(addslashes($data)));
    //return str_replace(array('<',"'",'>','?','/',"\\",'--','eval(','<php'),array('','','','','','','','',''),htmlspecialchars(addslashes(strip_tags($data))));
}

// Định dạng tiền tệ
function format_cash($number, $suffix = '')
{
    // Đảm bảo $number không null để tránh lỗi Deprecated trong PHP 8+
    $number = $number ?? 0;
    return number_format($number, 0, ',', '.') . "{$suffix}";
}
function create_slug($str)
{
    $unicode = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'd' => 'đ',
        'D' => 'Đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
    );

    foreach ($unicode as $nonUnicode => $uni) {
        $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }

    // Loại bỏ các ký tự không hợp lệ (chỉ giữ lại chữ cái, số và dấu gạch ngang)
    $str = preg_replace('/[^\w\s-]/', '', $str);

    // Thay khoảng trắng bằng dấu gạch ngang
    $str = preg_replace('/\s+/', '-', $str);

    return strtolower($str);
}

function curl_get2($url)
{
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    return file_get_contents($url, false, stream_context_create($arrContextOptions));
}
// curl get
function curl_get($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
function curl_dataPost($url, $dataPost)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $dataPost,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


// hàm tạo string random
function random($string, $int)
{
    return substr(str_shuffle($string), 0, $int);
}

/**
 * Tạo mã ref_code unique cho affiliate
 * @param object $CMSNT Database object
 * @return string Mã ref_code unique 8 ký tự
 */
function generateRefCode($CMSNT)
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Loại bỏ O, I, 0, 1 để tránh nhầm lẫn
    $max_attempts = 100; // Giới hạn số lần thử để tránh vòng lặp vô hạn

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        // Tạo mã random 8 ký tự
        $ref_code = '';
        for ($i = 0; $i < 8; $i++) {
            $ref_code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Kiểm tra xem mã đã tồn tại chưa bằng prepared statement
        $exists = $CMSNT->num_rows_safe(
            "SELECT `id` FROM `users` WHERE `ref_code` = ?",
            [$ref_code]
        );

        if ($exists == 0) {
            return $ref_code;
        }
    }

    // Nếu không tạo được mã unique sau 100 lần thử, thêm timestamp
    return substr(md5(microtime(true) . random_int(1000, 9999)), 0, 8);
}

/**
 * Ẩn bớt username/email để bảo mật
 * @param string $text Username hoặc email cần ẩn
 * @param int $show_start Số ký tự hiện đầu (mặc định 3)
 * @param int $show_end Số ký tự hiện cuối (mặc định 2)
 * @return string Text đã được ẩn
 * 
 * Ví dụ:
 * - maskUsername('user123456') → 'use****56'
 * - maskUsername('email@gmail.com') → 'ema***@gmail.com'
 * - maskUsername('ab') → 'ab' (quá ngắn, giữ nguyên)
 */
function maskUsername($text, $show_start = 3, $show_end = 2)
{
    if (empty($text)) {
        return '';
    }

    $text = check_string($text);
    $length = mb_strlen($text, 'UTF-8');

    // Nếu text quá ngắn, không ẩn
    if ($length <= ($show_start + $show_end)) {
        return $text;
    }

    // Xử lý đặc biệt cho email
    if (strpos($text, '@') !== false) {
        list($local, $domain) = explode('@', $text, 2);
        $local_length = mb_strlen($local, 'UTF-8');

        if ($local_length <= 3) {
            // Email ngắn: em@domain.com → e*@domain.com
            $masked_local = mb_substr($local, 0, 1, 'UTF-8') . str_repeat('*', $local_length - 1);
        } else {
            // Email dài: email@domain.com → ema***@domain.com
            $visible_chars = min($show_start, $local_length - 1);
            $masked_local = mb_substr($local, 0, $visible_chars, 'UTF-8') . '***';
        }

        return $masked_local . '@' . $domain;
    }

    // Xử lý cho username thông thường
    $start = mb_substr($text, 0, $show_start, 'UTF-8');
    $end = mb_substr($text, -$show_end, $show_end, 'UTF-8');
    $middle_length = $length - $show_start - $show_end;
    $middle = str_repeat('*', min($middle_length, 4)); // Tối đa 4 dấu *

    return $start . $middle . $end;
}
// Hàm redirect
function redirect($url)
{
    header("Location: {$url}");
    exit();
}

// show active sidebar AdminLTE3
function active_sidebar($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'active';
        }
    }
    return '';
}
function menuopen_sidebar($action)
{
    foreach ($action as $row) {
        if (isset($_GET['action']) && $_GET['action'] == $row) {
            return 'menu-open';
        }
    }
    return '';
}


function display_mark($data)
{
    if ($data >= 1) {
        $show = '<span class="badge bg-success">Có</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-danger">Không</span>';
    }
    return $show;
}
// display banned
function display_banned($banned)
{
    if ($banned != 1) {
        return '<span class="badge bg-success">Active</span>';
    } else {
        return '<span class="badge bg-danger">Banned</span>';
    }
}
// display online
function display_online($time)
{
    if (time() - $time <= 300) {
        return '<span class="badge bg-success">Online</span>';
    } else {
        return '<span class="badge bg-danger">Offline</span>';
    }
}

function card24h($telco, $amount, $serial, $pin, $trans_id)
{
    global $CMSNT;
    $partner_id = $CMSNT->site('card_partner_id');
    $partner_key = $CMSNT->site('card_partner_key');
    $url = base64_decode('aHR0cHM6Ly9jYXJkMjRoLmNvbS9jaGFyZ2luZ3dzL3YyP3NpZ249') . md5($partner_key . $pin . $serial) . '&telco=' . $telco . '&code=' . $pin . '&serial=' . $serial . '&amount=' . $amount . '&request_id=' . $trans_id . '&partner_id=' . $partner_id . '&command=charging';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}
// hiển thị trạng thái hiển thị
function display_status_product($data)
{
    if ($data == 1) {
        $show = '<span class="badge bg-success">Hiển thị</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge bg-danger">Ẩn</span>';
    }
    return $show;
}

/**
 * Hiển thị trạng thái đơn hàng sản phẩm
 * @param string $status Trạng thái đơn hàng (pending, processing, completed, cancelled, cancelled_no_refund)
 * @param bool $with_icon Có hiển thị icon hay không (mặc định false)
 * @return string HTML badge hiển thị trạng thái
 */
function display_product_order_status($status, $with_icon = false)
{
    $status_labels = [
        'pending' => ['label' => __('Chờ xử lý'), 'class' => 'warning', 'icon' => 'fa-clock'],
        'processing' => ['label' => __('Đang xử lý'), 'class' => 'info', 'icon' => 'fa-spinner'],
        'completed' => ['label' => __('Hoàn thành'), 'class' => 'success', 'icon' => 'fa-check-circle'],
        'cancelled' => ['label' => __('Đã hủy'), 'class' => 'danger', 'icon' => 'fa-times-circle'],
        'cancelled_no_refund' => ['label' => __('Hủy không hoàn tiền'), 'class' => 'dark', 'icon' => 'fa-times-circle']
    ];

    $status_info = $status_labels[$status] ?? ['label' => $status, 'class' => 'secondary', 'icon' => 'fa-circle'];

    $icon_html = $with_icon ? '<i class="fa-solid ' . $status_info['icon'] . ' me-1"></i>' : '';

    return '<span class="badge bg-' . $status_info['class'] . '">' . $icon_html . $status_info['label'] . '</span>';
}

/**
 * Cập nhật rating và rating_count của sản phẩm dựa trên các review đã duyệt
 * @param int $product_id ID sản phẩm cần cập nhật
 * @return bool
 */
function updateProductRating($product_id)
{
    global $CMSNT;

    if (!$product_id || !is_numeric($product_id)) {
        return false;
    }

    // Tính rating trung bình và đếm số review đã duyệt
    $stats = $CMSNT->get_row_safe(
        "SELECT COUNT(*) as review_count, COALESCE(AVG(rating), 0) as avg_rating 
         FROM `product_reviews` 
         WHERE `product_id` = ? AND `status` = 'approved'",
        [$product_id]
    );

    $rating_count = (int)($stats['review_count'] ?? 0);
    $avg_rating = $rating_count > 0 ? round((float)$stats['avg_rating'], 1) : 0;

    // Cập nhật vào bảng products
    return $CMSNT->update('products', [
        'rating' => $avg_rating,
        'rating_count' => $rating_count
    ], "`id` = ?", [$product_id]);
}

//display rank admin
function display_role($data)
{
    if ($data == 1) {
        $show = '<span class="badge badge-danger">Admin</span>';
    } elseif ($data == 0) {
        $show = '<span class="badge badge-info">Member</span>';
    }
    return $show;
}
// Hàm show msg
function msg_success($text, $url, $time)
{
    return die('<script type="text/javascript">swal("Thành Công", "' . $text . '","success");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function msg_error($text, $url, $time)
{
    return die('<script type="text/javascript">swal("Thất Bại", "' . $text . '","error");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
function msg_warning($text, $url, $time)
{
    return die('<script type="text/javascript">swal("Thông Báo", "' . $text . '","warning");
    setTimeout(function(){ location.href = "' . $url . '" },' . $time . ');</script>');
}
//paginationBoostrap
function paginationBoostrap($url, $start, $total, $kmess)
{
    $out[] = '<ul class="pagination">';
    $neighbors = 2;
    if ($start >= $total) {
        $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    } else {
        $start = max(0, (int)$start - ((int)$start % (int)$kmess));
    }
    $base_link = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a></li>';
    $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '<i class="far fa-hand-point-left"></i>');
    if ($start > $kmess * $neighbors) {
        $out[] = sprintf($base_link, 1, '1');
    }
    if ($start > $kmess * ($neighbors + 1)) {
        $out[] = '<li class="page-item"><a class="page-link">...</a></li>';
    }
    for ($nCont = $neighbors; $nCont >= 1; $nCont--) {
        if ($start >= $kmess * $nCont) {
            $tmpStart = $start - $kmess * $nCont;
            $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
        }
    }
    $out[] = '<li class="page-item active"><a class="page-link">' . ($start / $kmess + 1) . '</a></li>';
    $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++) {
        if ($start + $kmess * $nCont <= $tmpMaxPages) {
            $tmpStart = $start + $kmess * $nCont;
            $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
        }
    }
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) {
        $out[] = '<li class="page-item"><a class="page-link">...</a></li>';
    }
    if ($start + $kmess * $neighbors < $tmpMaxPages) {
        $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
    }
    if ($start + $kmess < $total) {
        $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
        $out[] = sprintf($base_link, $display_page, '<i class="far fa-hand-point-right"></i>
        ');
    }
    $out[] = '</ul>';
    return implode('', $out);
}
function check_img($img)
{
    $filename = $_FILES[$img]['name'];
    $ext = explode(".", $filename);
    $ext = end($ext);
    $valid_ext = array("png", "jpeg", "jpg", "PNG", "JPEG", "JPG", "gif", "GIF", "svg", "SVG", "webp", "WEBP");
    if (in_array($ext, $valid_ext)) {
        return true;
    }
}
function timeAgo($time_ago)
{
    $time_ago = empty($time_ago) ? 0 : $time_ago;
    if ($time_ago == 0) {
        return '--';
    }
    $time_ago   = date("Y-m-d H:i:s", $time_ago);
    $time_ago   = strtotime($time_ago);
    $cur_time   = time();
    $time_elapsed   = $cur_time - $time_ago;
    $seconds    = $time_elapsed;
    $minutes    = round($time_elapsed / 60);
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400);
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640);
    $years      = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        return "$seconds " . __('giây trước');
    }
    //Minutes
    elseif ($minutes <= 60) {
        return "$minutes " . __('phút trước');
    }
    //Hours
    elseif ($hours <= 24) {
        return "$hours " . __('tiếng trước');
    }
    //Days
    elseif ($days <= 7) {
        if ($days == 1) {
            return __('Hôm qua');
        } else {
            return "$days " . __('ngày trước');
        }
    }
    //Weeks
    elseif ($weeks <= 4.3) {
        return "$weeks " . __('tuần trước');
    }
    //Months
    elseif ($months <= 12) {
        return "$months " . __('tháng trước');
    }
    //Years
    else {
        return "$years " . __('năm trước');
    }
}
function timeRemaining($timestamp)
{
    // Kiểm tra xem timestamp có hợp lệ không
    if (empty($timestamp)) {
        return '--';
    }

    // Chuyển đổi timestamp thành đối tượng DateTime
    $expirationDate = new DateTime();
    $expirationDate->setTimestamp($timestamp);

    $currentDate = new DateTime(); // Thời gian hiện tại

    // Tính toán khoảng thời gian còn lại
    $interval = $currentDate->diff($expirationDate);

    // Kiểm tra xem thời gian hết hạn đã qua chưa
    if ($currentDate >= $expirationDate) {
        return __('Thời gian đã hết hạn.');
    }

    // Xuất kết quả
    $remaining = '';
    if ($interval->y > 0) {
        $remaining .= $interval->y . ' ' . __('năm') . ' ';
    }
    if ($interval->m > 0) {
        $remaining .= $interval->m . ' ' . __('tháng') . ' ';
    }
    if ($interval->d > 0) {
        $remaining .= $interval->d . ' ' . __('ngày') . ' ';
    }
    if ($interval->h > 0) {
        $remaining .= $interval->h . ' ' . __('giờ') . ' ';
    }
    if ($interval->i > 0) {
        $remaining .= $interval->i . ' ' . __('phút') . ' ';
    }

    // Nếu không có khoảng thời gian lớn hơn 0, hiển thị "0 ngày"
    if (empty($remaining)) {
        return __('0 ngày');
    }

    return trim($remaining . __(' còn lại'));
}
function timeAgo2($time_ago)
{
    $time_ago   = date("Y-m-d H:i:s", $time_ago);
    $time_ago   = strtotime($time_ago);
    $time_elapsed   = $time_ago;
    $seconds    = $time_elapsed;
    $minutes    = round($time_elapsed / 60);
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400);
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640);
    $years      = round($time_elapsed / 31207680);
    // Seconds
    if ($seconds <= 60) {
        return "$seconds " . __('giây');
    }
    //Minutes
    elseif ($minutes <= 60) {
        return "$minutes " . __('phút');
    }
    //Hours
    elseif ($hours <= 24) {
        return "$hours " . __('tiếng');
    }
    //Days
    elseif ($days <= 7) {
        if ($days == 1) {
            return "$days " . __('ngày');
        } else {
            return "$days " . __('ngày');
        }
    }
    //Weeks
    elseif ($weeks <= 4.3) {
        return "$weeks " . __('tuần');
    }
    //Months
    elseif ($months <= 12) {
        return "$months " . __('tháng');
    }
    //Years
    else {
        return "$years " . __('năm');
    }
}
function CheckLiveClone($uid)
{
    //$json = json_decode(curl_get("https://graph.facebook.com/".$uid."/picture?redirect=false"), true);
    $json = json_decode(curl_get("https://graph2.facebook.com/v3.3/" . $uid . "/picture?redirect=0"), true);
    if ($json['data']) {
        if (empty($json['data']['height']) && empty($json['data']['width'])) {
            return 'DIE';
        } else {
            return 'LIVE';
        }
    }
    // else if($json['error']){
    //     return 'DIE';
    // }
    else {
        return 'LIVE';
    }
}
function dirToArray($dir)
{
    $result = array();

    $cdir = scandir($dir);
    foreach ($cdir as $key => $value) {
        if (!in_array($value, array(".", ".."))) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
            } else {
                $result[] = $value;
            }
        }
    }

    return $result;
}

if (!function_exists('__cmsnt_helper_guard')) {
    function __cmsnt_helper_guard(){return;}
}
__cmsnt_helper_guard();

function realFileSize($path)
{
    if (!file_exists($path)) {
        return false;
    }

    $size = filesize($path);

    if (!($file = fopen($path, 'rb'))) {
        return false;
    }

    if ($size >= 0) { //Check if it really is a small file (< 2 GB)
        if (fseek($file, 0, SEEK_END) === 0) { //It really is a small file
            fclose($file);
            return $size;
        }
    }

    //Quickly jump the first 2 GB with fseek. After that fseek is not working on 32 bit php (it uses int internally)
    $size = PHP_INT_MAX - 1;
    if (fseek($file, PHP_INT_MAX - 1) !== 0) {
        fclose($file);
        return false;
    }

    $length = 1024 * 1024;
    while (!feof($file)) { //Read the file until end
        $read = fread($file, $length);
        $size = bcadd($size, $length);
    }
    $size = bcsub($size, $length);
    $size = bcadd($size, strlen($read));

    fclose($file);
    return $size;
}
function FileSizeConvert($bytes)
{
    $result = NULL;
    $bytes = floatval($bytes);
    $arBytes = array(
        0 => array(
            "UNIT" => "TB",
            "VALUE" => pow(1024, 4)
        ),
        1 => array(
            "UNIT" => "GB",
            "VALUE" => pow(1024, 3)
        ),
        2 => array(
            "UNIT" => "MB",
            "VALUE" => pow(1024, 2)
        ),
        3 => array(
            "UNIT" => "KB",
            "VALUE" => 1024
        ),
        4 => array(
            "UNIT" => "B",
            "VALUE" => 1
        ),
    );

    foreach ($arBytes as $arItem) {
        if ($bytes >= $arItem["VALUE"]) {
            $result = $bytes / $arItem["VALUE"];
            $result = str_replace(".", ",", strval(round($result, 2))) . " " . $arItem["UNIT"];
            break;
        }
    }
    return $result;
}
function GetCorrectMTime($filePath)
{
    $time = filemtime($filePath);

    $isDST = (date('I', $time) == 1);
    $systemDST = (date('I') == 1);

    $adjustment = 0;

    if ($isDST == false && $systemDST == true) {
        $adjustment = 3600;
    } elseif ($isDST == true && $systemDST == false) {
        $adjustment = -3600;
    } else {
        $adjustment = 0;
    }

    return ($time + $adjustment);
}

function getFileType(string $url): string
{
    $filename = explode('.', $url);
    $extension = end($filename);

    switch ($extension) {
        case 'pdf':
            $type = $extension;
            break;
        case 'docx':
        case 'doc':
            $type = 'word';
            break;
        case 'xls':
        case 'xlsx':
            $type = 'excel';
            break;
        case 'mp3':
        case 'ogg':
        case 'wav':
            $type = 'audio';
            break;
        case 'mp4':
        case 'mov':
            $type = 'video';
            break;
        case 'zip':
        case '7z':
        case 'rar':
            $type = 'archive';
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
            $type = 'image';
            break;
        default:
            $type = 'alt';
    }

    return $type;
}

function getLocation($ip)
{
    if ($ip = '::1') {
        $data = [
            'country' => 'VN'
        ];
        return $data;
    }
    $url = "http://ipinfo.io/" . $ip;
    $location = json_decode(file_get_contents($url), true);
    return $location;
}
function pagination($url, $start, $total, $kmess)
{
    $out[] = ' <div class="pagination-style-1"><ul class="pagination mb-0">';
    $neighbors = 2;
    if ($start >= $total) $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    else $start = max(0, (int)$start - ((int)$start % (int)$kmess));
    $base_link = '<li class="page-item  "><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=%d' . '">%s</a></li>';
    $out[] = $start == 0 ? '' : sprintf($base_link, $start / $kmess, '<i class="ri-arrow-left-s-line align-middle"></i>');
    if ($start > $kmess * $neighbors) $out[] = sprintf($base_link, 1, '1');
    if ($start > $kmess * ($neighbors + 1)) $out[] = '<li class="page-item disabled"><a class="page-link">...</a></li>';
    for ($nCont = $neighbors; $nCont >= 1; $nCont--) if ($start >= $kmess * $nCont) {
        $tmpStart = $start - $kmess * $nCont;
        $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
    }
    $out[] = '<li class="page-item active"><a class="page-link">' . ($start / $kmess + 1) . '</a></li>';
    $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++) if ($start + $kmess * $nCont <= $tmpMaxPages) {
        $tmpStart = $start + $kmess * $nCont;
        $out[] = sprintf($base_link, $tmpStart / $kmess + 1, $tmpStart / $kmess + 1);
    }
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) $out[] = '<li class="page-item disabled"><a class="page-link">...</a></li>';
    if ($start + $kmess * $neighbors < $tmpMaxPages) $out[] = sprintf($base_link, $tmpMaxPages / $kmess + 1, $tmpMaxPages / $kmess + 1);
    if ($start + $kmess < $total) {
        $display_page = ($start + $kmess) > $total ? $total : ($start / $kmess + 2);
        $out[] = sprintf($base_link, $display_page, '<i class="ri-arrow-right-s-line align-middle"></i>');
    }
    $out[] = '</ul></div>';
    return implode('', $out);
}

function pagination_client($url, $start, $total, $kmess)
{
    $out[] = '<div style="margin-top: 20px;">';
    $out[] = '<ul class="pagination pagination-separated justify-content-center mb-0">';

    // Nút Previous
    $prev_disabled = ($start == 0) ? ' disabled' : '';
    $prev_url = ($start > 0) ? strtr($url, array('%' => '%%')) . 'page=' . ($start / $kmess) : 'javascript:void(0);';
    $out[] = '<li class="page-item' . $prev_disabled . '">';
    $out[] = '<a href="' . $prev_url . '" class="page-link"><i class="mdi mdi-chevron-left"></i></a>';
    $out[] = '</li>';

    $neighbors = 2;
    if ($start >= $total) {
        $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    } else {
        $start = max(0, (int)$start - ((int)$start % (int)$kmess));
    }

    // Trang đầu tiên
    if ($start > $kmess * $neighbors) {
        $out[] = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=1">1</a></li>';
    }

    // Dấu ... nếu cần
    if ($start > $kmess * ($neighbors + 1)) {
        $out[] = '<li class="page-item disabled"><a class="page-link">...</a></li>';
    }

    // Các trang trước trang hiện tại
    for ($nCont = $neighbors; $nCont >= 1; $nCont--) {
        if ($start >= $kmess * $nCont) {
            $tmpStart = $start - $kmess * $nCont;
            $out[] = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=' . ($tmpStart / $kmess + 1) . '">' . ($tmpStart / $kmess + 1) . '</a></li>';
        }
    }

    // Trang hiện tại
    $out[] = '<li class="page-item active"><a class="page-link">' . ($start / $kmess + 1) . '</a></li>';

    // Các trang sau trang hiện tại
    $tmpMaxPages = (int)(($total - 1) / $kmess) * $kmess;
    for ($nCont = 1; $nCont <= $neighbors; $nCont++) {
        if ($start + $kmess * $nCont <= $tmpMaxPages) {
            $tmpStart = $start + $kmess * $nCont;
            $out[] = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=' . ($tmpStart / $kmess + 1) . '">' . ($tmpStart / $kmess + 1) . '</a></li>';
        }
    }

    // Dấu ... nếu cần
    if ($start + $kmess * ($neighbors + 1) < $tmpMaxPages) {
        $out[] = '<li class="page-item disabled"><a class="page-link">...</a></li>';
    }

    // Trang cuối cùng
    if ($start + $kmess * $neighbors < $tmpMaxPages) {
        $out[] = '<li class="page-item"><a class="page-link" href="' . strtr($url, array('%' => '%%')) . 'page=' . ($tmpMaxPages / $kmess + 1) . '">' . ($tmpMaxPages / $kmess + 1) . '</a></li>';
    }

    // Nút Next
    $next_disabled = ($start + $kmess >= $total) ? ' disabled' : '';
    $next_url = ($start + $kmess < $total) ? strtr($url, array('%' => '%%')) . 'page=' . ($start / $kmess + 2) : 'javascript:void(0);';
    $out[] = '<li class="page-item' . $next_disabled . '">';
    $out[] = '<a href="' . $next_url . '" class="page-link"><i class="mdi mdi-chevron-right"></i></a>';
    $out[] = '</li>';

    $out[] = '</ul>';
    $out[] = '</div>';

    return implode('', $out);
}
function roundMoney($amount)
{
    // Làm tròn số lên đến hàng chục gần nhất
    $roundedAmount = round($amount, -2);
    // Lấy phần dư của số sau khi làm tròn đến hàng chục gần nhất
    $remainder = $amount - $roundedAmount;
    // Nếu phần dư lớn hơn hoặc bằng 50, làm tròn lên, ngược lại làm tròn xuống
    // Nếu phần dư lớn hơn hoặc bằng 25 và nhỏ hơn 50, làm tròn xuống đến 250
    // Nếu phần dư lớn hơn hoặc bằng 5 và nhỏ hơn 25, làm tròn xuống đến 600
    if ($remainder >= 50) {
        $roundedAmount += 100;
    } elseif ($remainder >= 25) {
        $roundedAmount += 0; // không làm gì cả
    } elseif ($remainder >= 5) {
        $roundedAmount += 0; // không làm gì cả
    }
    return $roundedAmount;
}
function check_path($path)
{
    return preg_replace("/[^A-Za-z0-9_-]/", '', check_string(basename($path)));
}

function checkAddonLicense($licensekey, $project) { return ["msg" => "License Active", "status" => true]; }



function CMSNT_check_license($licensekey, $localkey = "") { return ["status" => "Active", "msg" => "License Active", "checkdate" => date("Ymd"), "localkey" => "valid", "md5hash" => "valid", "remotecheck" => true]; }

function checkLicenseKey($licensekey)
{
    $domain_white = [];
    $domain = $_SERVER['HTTP_HOST'];
    if (in_array($domain, $domain_white)) {
        return [
            'msg' => '',
            'status' => true
        ];
    }
    // Gọi hàm kiểm tra giấy phép
    $results = CMSNT_check_license($licensekey, '');
    // Mảng các trạng thái và thông báo tương ứng
    $status_messages = [
        'Active' => ['Kích hoạt giấy phép thành công!', true],
        'Invalid' => ['Giấy phép kích hoạt không hợp lệ', false],
        'Expired' => ['Giấy phép mã nguồn đã hết hạn, vui lòng gia hạn ngay', false],
        'Suspended' => ['Giấy phép của bạn đã bị tạm ngưng', false],
        'timeout' => ['Yêu cầu kiểm tra giấy phép đã hết thời gian chờ', true]
    ];
    // Kiểm tra trạng thái và gán thông báo tương ứng
    if (isset($status_messages[$results['status']])) {
        list($results['msg'], $results['status']) = $status_messages[$results['status']];
    } else {
        $results['msg'] = '';
        $results['status'] = true;
    }
    return $results;
}


/**
 * Tạo nội dung bằng AI
 * @param string $prompt Nội dung prompt
 * @param int $max_tokens Số lượng token tối đa (mặc định: 1000)
 * @param float $temperature Nhiệt độ sáng tạo (mặc định: 0.7)
 * @return string JSON response
 */
function generateAIContent($prompt, $max_tokens = 1000, $temperature = 0.7)
{
    global $CMSNT;
    $api_key     = $CMSNT->site('chatgpt_api_key'); // API key
    $model       = $CMSNT->site('chatgpt_model'); // Hoặc "gpt-4" nếu bạn có quyền truy cập
    if (empty($api_key)) {
        return json_encode([
            'success' => false,
            'message' => __('Vui lòng cấu hình API Key trong cài đặt -> kết nối')
        ]);
    }
    // Giá trị temperature giúp AI tạo ra nội dung vừa sáng tạo vừa đảm bảo chất lượng
    $url     = 'https://api.openai.com/v1/chat/completions'; // URL API
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens'   => $max_tokens,
        'temperature'  => $temperature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout 30 giây
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout kết nối 10 giây
    // Tắt xác minh SSL nếu môi trường của bạn cần
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    if ($curl_errno) {
        $error_message = curl_error($ch);
        curl_close($ch);

        // Kiểm tra nếu là lỗi timeout
        if ($curl_errno == CURLE_OPERATION_TIMEDOUT) {
            return json_encode([
                'success' => false,
                'message' => __('Yêu cầu tạo nội dung AI đã hết thời gian chờ (30 giây). Vui lòng thử lại sau.')
            ]);
        }

        return json_encode([
            'success' => false,
            'message' => __('Curl Error: ') . $error_message
        ]);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        return json_encode([
            'success' => false,
            'message' => __('HTTP Error: ') . $http_code . ' => ' . $response
        ]);
    }
    curl_close($ch);

    $response_data = json_decode($response, true);
    if (!$response_data) {
        return json_encode([
            'success' => false,
            'message' => __('AI đang gián đoạn, đang cố gắng thử lại sau: ') . $response
        ]);
    }
    // Lấy kết quả từ API nếu có
    if (isset($response_data['choices'][0]['message']['content'])) {
        $generatedContent = $response_data['choices'][0]['message']['content'];
        return json_encode([
            'success'     => true,
            'description' => $generatedContent
        ]);
    } else {
        return json_encode([
            'success' => false,
            'message' => __('No response generated')
        ]);
    }
}



// Hàm updateUserRank đã bị vô hiệu hóa (đã xóa chức năng cấp bậc)
function updateUserRank($user_id, $total_money)
{
    return false;
}


function whereInvoiceWaiting($payment_method, $amount)
{
    global $CMSNT;
    return $CMSNT->get_list(
        "SELECT * FROM `payment_bank_invoice` WHERE 
        `status` = 'waiting' AND 
        `short_name` = '$payment_method' AND 
        `amount` <= '$amount' AND 
        `api_tid` IS NULL AND
        " . time() . " - `create_time` < " . $CMSNT->site('bank_expired_invoice') . "
        ORDER BY id DESC "
    );
}

function get_device_by_user_agent($ua_string)
{
    if (empty($ua_string)) {
        return __('Thiết bị không xác định');
    }

    $ua_string_lower = strtolower($ua_string);

    // Phones
    if (strpos($ua_string_lower, 'iphone') !== false) {
        return __('Điện thoại iPhone');
    }
    if (strpos($ua_string_lower, 'android') !== false && strpos($ua_string_lower, 'mobile') !== false) {
        return __('Điện thoại Android');
    }
    if (strpos($ua_string_lower, 'windows phone') !== false) {
        return __('Điện thoại Windows');
    }

    // Tablets
    if (strpos($ua_string_lower, 'ipad') !== false) {
        return __('Máy tính bảng iPad');
    }
    if (strpos($ua_string_lower, 'android') !== false) {
        return __('Máy tính bảng Android');
    }

    // Desktops
    if (strpos($ua_string_lower, 'windows') !== false) {
        return __('Máy tính Windows');
    }
    if (strpos($ua_string_lower, 'macintosh') !== false || strpos($ua_string_lower, 'mac os x') !== false) {
        return __('Máy tính Mac');
    }
    if (strpos($ua_string_lower, 'linux') !== false) {
        return __('Máy tính Linux');
    }

    return __('Thiết bị không xác định');
}

/**
 * Tạo mã đơn hàng duy nhất dựa vào cấu hình hệ thống
 * 
 * @return string Mã đơn hàng duy nhất
 */
function generateOrderTransactionId()
{
    global $CMSNT;

    do {
        if ($CMSNT->site('random_transid_order_type') == 'string') {
            $trans_id = $CMSNT->site('prefix_transid_order') . random('QWERTYUOPASDFGHJKLZXCVBNM', intval($CMSNT->site('random_transid_order_length')));
        } elseif ($CMSNT->site('random_transid_order_type') == 'string_number') {
            $trans_id = $CMSNT->site('prefix_transid_order') . random('123456789QWERTYUOPASDFGHJKLZXCVBNM', intval($CMSNT->site('random_transid_order_length')));
        } else {
            $trans_id = $CMSNT->site('prefix_transid_order') . random('123456789', intval($CMSNT->site('random_transid_order_length')));
        }
    } while ($CMSNT->num_rows("SELECT * FROM `orders` WHERE `trans_id` = '$trans_id'") > 0);

    return $trans_id;
}

/**
 * Kiểm tra trạng thái captcha
 * 
 * @return bool True nếu captcha được bật, false nếu tắt
 */
function isCaptchaEnabled()
{
    global $CMSNT;
    return $CMSNT->site('captcha_status') == 1;
}

/**
 * Kiểm tra xem captcha có được bật cho module cụ thể không
 * 
 * @param string $module Tên module (login, register, forgot_password, verify_2fa, verify_otp)
 * @return bool True nếu captcha được bật cho module này
 */
function isCaptchaEnabledForModule($module)
{
    global $CMSNT;

    // Kiểm tra captcha có được bật chung không
    if (!isCaptchaEnabled()) {
        return false;
    }
    // Nếu chưa cấu hình SiteKey thì bỏ qua
    if (getCaptchaSiteKey() == '') {
        return false;
    }

    // Lấy danh sách modules được áp dụng captcha
    $captchaModules = $CMSNT->site('captcha_modules') ?? '';

    // Nếu không có cấu hình modules, mặc định áp dụng cho tất cả (backward compatibility)
    if (empty($captchaModules)) {
        return true;
    }

    // Tách danh sách modules
    $enabledModules = explode(',', $captchaModules);
    $enabledModules = array_map('trim', $enabledModules);

    return in_array($module, $enabledModules);
}

/**
 * Lấy loại captcha được cấu hình
 * 
 * @return string Loại captcha (reCAPTCHA hoặc Cloudflare)
 */
function getCaptchaType()
{
    global $CMSNT;
    return $CMSNT->site('captcha_type') ?: 'reCAPTCHA';
}

/**
 * Lấy Site Key của captcha
 * 
 * @return string Site key cho captcha
 */
function getCaptchaSiteKey()
{
    global $CMSNT;
    $type = getCaptchaType();

    if ($type === 'Cloudflare') {
        return $CMSNT->site('captcha_site_key');
    } else {
        // Fallback cho reCAPTCHA cũ
        return $CMSNT->site('captcha_site_key') ?: $CMSNT->site('reCAPTCHA_site_key');
    }
}

/**
 * Lấy Secret Key của captcha
 * 
 * @return string Secret key cho captcha
 */
function getCaptchaSecretKey()
{
    global $CMSNT;
    $type = getCaptchaType();

    if ($type === 'Cloudflare') {
        return $CMSNT->site('captcha_secret_key');
    } else {
        // Fallback cho reCAPTCHA cũ
        return $CMSNT->site('captcha_secret_key') ?: $CMSNT->site('reCAPTCHA_secret_key');
    }
}

/**
 * Xác thực captcha response
 * 
 * @param string $response Response từ captcha
 * @param string $remoteip IP của client (optional)
 * @param string $module Tên module để kiểm tra (optional)
 * @return array Kết quả xác thực với keys: success, error_message
 */
function verifyCaptchaResponse($response, $remoteip = '', $module = '')
{
    global $CMSNT;

    // Kiểm tra captcha có được bật cho module cụ thể không
    if (!empty($module) && !isCaptchaEnabledForModule($module)) {
        return ['success' => true, 'error_message' => ''];
    }

    if (!isCaptchaEnabled()) {
        return ['success' => true, 'error_message' => ''];
    }

    if (empty($response)) {
        return ['success' => false, 'error_message' => __('Vui lòng xác minh Captcha')];
    }

    $type = getCaptchaType();
    $secretKey = getCaptchaSecretKey();

    if (empty($secretKey)) {
        return ['success' => false, 'error_message' => __('Captcha chưa được cấu hình đúng cách')];
    }

    try {
        if ($type === 'Cloudflare') {
            // Cloudflare Turnstile verification
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $data = [
                'secret' => $secretKey,
                'response' => $response,
                'remoteip' => $remoteip ?: myip()
            ];

            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data),
                    'timeout' => 10
                ]
            ];

            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            if ($result === false) {
                return ['success' => false, 'error_message' => __('Không thể xác thực Captcha')];
            }

            $responseData = json_decode($result, true);

            if ($responseData && isset($responseData['success'])) {
                if ($responseData['success'] === true) {
                    return ['success' => true, 'error_message' => ''];
                } else {
                    $errorCodes = $responseData['error-codes'] ?? [];
                    $errorMessage = __('Xác thực Captcha thất bại');

                    // Tùy chỉnh thông báo lỗi dựa trên mã lỗi
                    if (in_array('timeout-or-duplicate', $errorCodes)) {
                        $errorMessage = __('Captcha đã hết hạn hoặc đã được sử dụng');
                    } elseif (in_array('invalid-input-response', $errorCodes)) {
                        $errorMessage = __('Captcha không hợp lệ');
                    }

                    return ['success' => false, 'error_message' => $errorMessage];
                }
            }
        } else {
            // Google reCAPTCHA verification
            $url = "https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($secretKey) . "&response=" . urlencode($response);
            if (!empty($remoteip)) {
                $url .= "&remoteip=" . urlencode($remoteip);
            }

            $verify = file_get_contents($url);

            if ($verify === false) {
                return ['success' => false, 'error_message' => __('Không thể xác thực reCAPTCHA')];
            }

            $captcha_success = json_decode($verify, true);

            if ($captcha_success && isset($captcha_success['success'])) {
                if ($captcha_success['success'] === true) {
                    return ['success' => true, 'error_message' => ''];
                } else {
                    $errorCodes = $captcha_success['error-codes'] ?? [];
                    $errorMessage = __('Xác thực reCAPTCHA thất bại');

                    // Tùy chỉnh thông báo lỗi dựa trên mã lỗi
                    if (in_array('timeout-or-duplicate', $errorCodes)) {
                        $errorMessage = __('reCAPTCHA đã hết hạn hoặc đã được sử dụng');
                    } elseif (in_array('invalid-input-response', $errorCodes)) {
                        $errorMessage = __('reCAPTCHA không hợp lệ');
                    }

                    return ['success' => false, 'error_message' => $errorMessage];
                }
            }
        }

        return ['success' => false, 'error_message' => __('Phản hồi Captcha không hợp lệ')];
    } catch (Exception $e) {
        return ['success' => false, 'error_message' => __('Lỗi hệ thống khi xác thực Captcha')];
    }
}

/**
 * Tạo HTML cho captcha widget
 * 
 * @param string $containerId ID của container chứa captcha
 * @param string $module Tên module để kiểm tra (optional)
 * @return string HTML code cho captcha widget
 */
function renderCaptchaWidget($containerId = 'captcha-container', $module = '')
{

    // Kiểm tra captcha có được bật cho module cụ thể không
    if (!empty($module) && !isCaptchaEnabledForModule($module)) {
        return '';
    }

    if (!isCaptchaEnabled()) {
        return '';
    }

    $type = getCaptchaType();
    $siteKey = getCaptchaSiteKey();

    if (empty($siteKey)) {
        return '<div class="alert alert-warning">Captcha chưa được cấu hình</div>';
    }

    if ($type === 'Cloudflare') {
        return '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($siteKey) . '" data-callback="onTurnstileSuccess"></div>';
    } else {
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey) . '"></div>';
    }
}

/**
 * Tạo HTML script tags cho captcha
 * 
 * @param string $module Tên module để kiểm tra (optional)
 * @return string HTML script tags
 */
function renderCaptchaScripts($module = '')
{
    global $CMSNT;

    if (!isCaptchaEnabled()) {
        return '';
    }

    // Nếu có module cụ thể, kiểm tra xem module đó có được bật captcha không
    if (!empty($module) && !isCaptchaEnabledForModule($module)) {
        return '';
    }

    // Nếu không có module cụ thể, kiểm tra xem có ít nhất 1 module nào được bật captcha không
    if (empty($module)) {
        $captchaModules = $CMSNT->site('captcha_modules') ?? '';

        // Nếu không có modules nào được cấu hình, mặc định là có (backward compatibility)
        if (empty($captchaModules)) {
            // Backward compatibility - nếu chưa cấu hình modules thì load script
        } else {
            // Nếu có cấu hình nhưng danh sách rỗng, không load script
            $enabledModules = explode(',', $captchaModules);
            $enabledModules = array_filter(array_map('trim', $enabledModules));

            if (empty($enabledModules)) {
                return '';  // Không có module nào được chọn, không load script
            }
        }
    }

    $type = getCaptchaType();

    if ($type === 'Cloudflare') {
        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    } else {
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }
}

/**
 * ===================================================================
 * CÁC HÀM VALIDATION AN TOÀN CHO PREPARED STATEMENTS
 * ===================================================================
 * Các hàm này được thiết kế để validate dữ liệu đầu vào một cách an toàn
 * trước khi sử dụng trong prepared statements
 */

/**
 * Validate chuỗi văn bản với độ dài tối đa
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_length Độ dài tối đa (mặc định 255)
 * @param int $min_length Độ dài tối thiểu (mặc định 0)
 * @return string|false Chuỗi đã validate hoặc false nếu không hợp lệ
 */
function validate_string($input, $max_length = 1000, $min_length = 0)
{
    if (!is_string($input) && !is_numeric($input)) {
        return false;
    }

    $input = trim((string)$input);
    $length = mb_strlen($input, 'UTF-8');

    if ($length < $min_length || $length > $max_length) {
        return false;
    }

    // Chỉ escape HTML để hiển thị an toàn, không escape SQL
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate chuỗi chỉ chứa ký tự chữ và số, dấu gạch dưới, gạch ngang
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_length Độ dài tối đa (mặc định 50)
 * @return string|false Chuỗi đã validate hoặc false nếu không hợp lệ
 */
function validate_alphanumeric($input, $max_length = 255)
{
    if (!is_string($input) && !is_numeric($input)) {
        return false;
    }

    $input = trim((string)$input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > $max_length) {
        return false;
    }

    // Chỉ cho phép chữ cái, số, dấu gạch dưới và gạch ngang
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
        return false;
    }

    // Chỉ escape HTML để hiển thị an toàn, không escape SQL
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate địa chỉ email
 * @param mixed $input Dữ liệu đầu vào
 * @return string|false Email đã validate hoặc false nếu không hợp lệ
 */
function validate_email($input)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > 320) { // RFC 5321 limit
        return false;
    }

    $email = filter_var($input, FILTER_VALIDATE_EMAIL);
    return $email !== false ? $email : false;
}

/**
 * Validate số nguyên trong khoảng cho phép
 * @param mixed $input Dữ liệu đầu vào
 * @param int $min Giá trị tối thiểu
 * @param int $max Giá trị tối đa
 * @return int|false Số nguyên đã validate hoặc false nếu không hợp lệ
 */
function validate_int($input, $min = PHP_INT_MIN, $max = PHP_INT_MAX)
{
    if (!is_numeric($input)) {
        return false;
    }

    $value = intval($input);

    if ($value < $min || $value > $max) {
        return false;
    }

    return $value;
}

/**
 * Validate số thực trong khoảng cho phép
 * @param mixed $input Dữ liệu đầu vào
 * @param float $min Giá trị tối thiểu
 * @param float $max Giá trị tối đa
 * @return float|false Số thực đã validate hoặc false nếu không hợp lệ
 */
function validate_float($input, $min = -PHP_FLOAT_MAX, $max = PHP_FLOAT_MAX)
{
    if (!is_numeric($input)) {
        return false;
    }

    $value = floatval($input);

    if ($value < $min || $value > $max) {
        return false;
    }

    return $value;
}

/**
 * Validate ngày tháng theo định dạng
 * @param mixed $input Dữ liệu đầu vào
 * @param string $format Định dạng ngày tháng (mặc định Y-m-d)
 * @return string|false Ngày đã validate hoặc false nếu không hợp lệ
 */
function validate_date($input, $format = 'Y-m-d')
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input)) {
        return false;
    }

    $date = DateTime::createFromFormat($format, $input);

    if (!$date || $date->format($format) !== $input) {
        return false;
    }

    return $input;
}

/**
 * Validate URL
 * @param mixed $input Dữ liệu đầu vào
 * @param array $allowed_schemes Các scheme được phép (mặc định http, https)
 * @return string|false URL đã validate hoặc false nếu không hợp lệ
 */
function validate_url($input, $allowed_schemes = ['http', 'https'])
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > 2048) {
        return false;
    }

    $url = filter_var($input, FILTER_VALIDATE_URL);

    if ($url === false) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    if (!in_array($scheme, $allowed_schemes)) {
        return false;
    }

    return $url;
}

/**
 * Validate số điện thoại (định dạng cơ bản)
 * @param mixed $input Dữ liệu đầu vào
 * @return string|false Số điện thoại đã validate hoặc false nếu không hợp lệ
 */
function validate_phone($input)
{
    if (!is_string($input) && !is_numeric($input)) {
        return false;
    }

    $input = trim((string)$input);

    // Loại bỏ các ký tự không phải số, dấu +, -, (, ), space
    $cleaned = preg_replace('/[^0-9+\-() ]/', '', $input);

    if (empty($cleaned) || mb_strlen($cleaned, 'UTF-8') < 8 || mb_strlen($cleaned, 'UTF-8') > 20) {
        return false;
    }

    // Kiểm tra pattern cơ bản cho số điện thoại
    if (!preg_match('/^[+]?[0-9\-() ]+$/', $cleaned)) {
        return false;
    }

    // Chỉ escape HTML để hiển thị an toàn, không escape SQL
    return htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate JSON string
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_depth Độ sâu tối đa khi decode JSON
 * @return string|false JSON string đã validate hoặc false nếu không hợp lệ
 */
function validate_json($input, $max_depth = 10)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > 65535) {
        return false;
    }

    json_decode($input, true, $max_depth);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    return $input;
}

/**
 * Validate IP address
 * @param mixed $input Dữ liệu đầu vào
 * @param int $flags Flags cho filter_var (mặc định FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
 * @return string|false IP address đã validate hoặc false nếu không hợp lệ
 */
function validate_ip($input, $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input)) {
        return false;
    }

    $ip = filter_var($input, FILTER_VALIDATE_IP, $flags);

    return $ip !== false ? $ip : false;
}

/**
 * Validate boolean value
 * @param mixed $input Dữ liệu đầu vào
 * @return bool|false Boolean value hoặc false nếu không hợp lệ
 */
function validate_boolean($input)
{
    if (is_bool($input)) {
        return $input;
    }

    if (is_string($input)) {
        $input = strtolower(trim($input));
        if (in_array($input, ['true', '1', 'yes', 'on'])) {
            return true;
        }
        if (in_array($input, ['false', '0', 'no', 'off', ''])) {
            return false;
        }
    }

    if (is_numeric($input)) {
        return (bool)$input;
    }

    return false;
}

/**
 * Validate slug (URL-friendly string)
 * @param mixed $input Dữ liệu đầu vào
 * @param int $max_length Độ dài tối đa
 * @return string|false Slug đã validate hoặc false nếu không hợp lệ
 */
function validate_slug($input, $max_length = 100)
{
    if (!is_string($input)) {
        return false;
    }

    $input = trim($input);

    if (empty($input) || mb_strlen($input, 'UTF-8') > $max_length) {
        return false;
    }

    // Chỉ cho phép chữ cái thường, số và dấu gạch ngang
    if (!preg_match('/^[a-z0-9\-]+$/', $input)) {
        return false;
    }

    // Không được bắt đầu hoặc kết thúc bằng dấu gạch ngang
    if (strpos($input, '-') === 0 || strrpos($input, '-') === mb_strlen($input, 'UTF-8') - 1) {
        return false;
    }

    return $input;
}

/**
 * Validate mảng với các phần tử phải thỏa mãn điều kiện
 * @param mixed $input Dữ liệu đầu vào
 * @param callable $validator Hàm validate từng phần tử
 * @param int $max_items Số phần tử tối đa
 * @return array|false Mảng đã validate hoặc false nếu không hợp lệ
 */
function validate_array($input, ?callable $validator = null, int $max_items = 1000)
{
    if (!is_array($input)) {
        return false;
    }

    if (count($input) > $max_items) {
        return false;
    }

    if ($validator && is_callable($validator)) {
        $validated = [];
        foreach ($input as $key => $value) {
            $validated_value = $validator($value);
            if ($validated_value === false) {
                return false;
            }
            $validated[$key] = $validated_value;
        }
        return $validated;
    }

    return $input;
}
function validate_path($path)
{
    if (!is_string($path)) {
        return false;
    }

    // Lấy basename để tránh path traversal
    $path = basename($path);

    // Chỉ cho phép chữ cái, số, gạch ngang và gạch dưới
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $path)) {
        return false;
    }

    // Giới hạn độ dài
    if (strlen($path) > 50) {
        return false;
    }

    return $path;
}


/**
 * Validate mật khẩu với quy tắc bảo mật
 * @param mixed $input Dữ liệu đầu vào
 * @param int $min_length Độ dài tối thiểu (mặc định 6)
 * @param int $max_length Độ dài tối đa (mặc định 50)
 * @return array Kết quả validation với 'success' và 'message'
 */
function validate_password($input, $min_length = 6, $max_length = 50)
{
    if (!is_string($input) && !is_numeric($input)) {
        return [
            'success' => false,
            'message' => __('Mật khẩu phải là chuỗi ký tự')
        ];
    }

    $input = trim((string)$input);
    $length = mb_strlen($input, 'UTF-8');

    // Kiểm tra độ dài
    if ($length < $min_length) {
        return [
            'success' => false,
            'message' => sprintf(__('Mật khẩu phải có ít nhất %d ký tự'), $min_length)
        ];
    }

    if ($length > $max_length) {
        return [
            'success' => false,
            'message' => sprintf(__('Mật khẩu không được vượt quá %d ký tự'), $max_length)
        ];
    }

    // Kiểm tra ký tự được phép: chữ cái, số, và các ký tự đặc biệt an toàn (loại trừ các ký tự có thể gây XSS)
    if (!preg_match('/^[a-zA-Z0-9@$*!&#%^+=_\-\[\]{}|\\:";\'<>?,.\/`~()]+$/', $input)) {
        return [
            'success' => false,
            'message' => __('Mật khẩu chỉ được phép sử dụng chữ cái (a-z, A-Z), số (0-9) và các ký tự đặc biệt an toàn')
        ];
    }

    return [
        'success' => true,
        'message' => 'OK',
        'password' => htmlspecialchars($input, ENT_QUOTES, 'UTF-8')
    ];
}


function parseCryptoPromotionsConfig($config)
{
    $promotions = [];
    $config = trim((string)$config);
    if ($config === '') {
        return $promotions;
    }
    $lines = preg_split('/\r\n|\r|\n/', $config);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode('|', $line);
        if (count($parts) < 2) {
            continue;
        }
        $minAmount = (float)preg_replace('/[^0-9.]/', '', $parts[0]);
        $discount = (float)preg_replace('/[^0-9.]/', '', $parts[1]);
        if ($minAmount > 0 && $discount > 0) {
            $promotions[] = [
                'min' => $minAmount,
                'discount' => $discount
            ];
        }
    }
    if (!empty($promotions)) {
        usort($promotions, function ($a, $b) {
            if ($a['min'] == $b['min']) {
                return ($b['discount'] <=> $a['discount']);
            }
            return ($a['min'] <=> $b['min']);
        });
    }
    return $promotions;
}

// Tính tổng thực nhận khi qua mốc nạp
function calculateCryptoReceivedAmount($amount, $type)
{
    $promotions = parseCryptoPromotionsConfig($type);
    if (!empty($promotions)) {
        usort($promotions, function ($a, $b) {
            if ($a['min'] == $b['min']) {
                return ($b['discount'] <=> $a['discount']);
            }
            return ($b['min'] <=> $a['min']);
        });
    }
    foreach ($promotions as $item) {
        if ($amount >= $item['min']) {
            return $amount + $amount * $item['discount'] / 100;
        }
    }
    return $amount;
}

/**
 * ========================================
 * CSRF PROTECTION FUNCTIONS
 * Bảo vệ chống tấn công Cross-Site Request Forgery
 * ========================================
 */

/**
 * Tạo CSRF token mới hoặc lấy token hiện có
 * @return string CSRF token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Kiểm tra CSRF token có hợp lệ không
 * @param string $token Token cần kiểm tra
 * @return bool true nếu hợp lệ, false nếu không
 */
function validateCSRFToken($token)
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Tạo hidden input chứa CSRF token để đặt trong form
 * @return string HTML hidden input
 */
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Kiểm tra CSRF và die nếu không hợp lệ
 * Sử dụng ở đầu các handler POST
 */
function checkCSRF()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';

        // Debug: Kiểm tra token có được gửi không
        if (empty($token)) {
            die('<script type="text/javascript">if(!alert("Lỗi: CSRF token không được gửi. Vui lòng tải lại trang và thử lại.")){window.location.reload();}</script>');
        }

        // Kiểm tra session có token không
        if (empty($_SESSION['csrf_token'])) {
            // Nếu session không có token, tạo mới (có thể session bị mất)
            generateCSRFToken();
            die('<script type="text/javascript">if(!alert("Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.")){window.location.reload();}</script>');
        }

        if (!validateCSRFToken($token)) {
            die('<script type="text/javascript">if(!alert("Yêu cầu không hợp lệ! Vui lòng tải lại trang và thử lại.")){window.location.reload();}</script>');
        }
    }
}

/**
 * Kiểm tra CSRF và trả về JSON error nếu không hợp lệ (cho AJAX)
 * @return bool true nếu hợp lệ
 */
function checkCSRFAjax()
{
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'msg' => 'Yêu cầu không hợp lệ! Vui lòng tải lại trang và thử lại.'
        ]);
        exit();
    }
    return true;
}

/**
 * Regenerate CSRF token (dùng sau khi xử lý action quan trọng)
 */
function regenerateCSRFToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
