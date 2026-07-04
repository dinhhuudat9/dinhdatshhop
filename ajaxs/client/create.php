<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . '/../../libs/database/users.php');
require_once(__DIR__ . "/../../libs/sendEmail.php");

if ($CMSNT->site('status') != 1) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('Hệ thống đang bảo trì!')
    ]);
    die($data);
}
if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}

// Kiểm tra CSRF token cho tất cả request
checkCSRFAjax();

if ($CMSNT->site('status_demo') != 0) {
    die(json_encode(['status' => 'error', 'msg' => __('Chức năng này không thể sử dụng trên website demo')]));
}


if ($_POST['action'] == 'WithdrawCommission') {
    if ($CMSNT->site('status_demo') != 0) {
        die(json_encode(['status' => 'error', 'msg' => __('This function cannot be used because this is a demo site')]));
    }
    if ($CMSNT->site('affiliate_status') != 1) {
        die(json_encode(['status' => 'error', 'msg' => __('Chức năng này đang được bảo trì')]));
    }
    if (empty($_POST['token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    if (!$getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0 ", [validate_string($_POST['token'], 255)])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập')]));
    }
    // Xác thực Captcha
    $captchaResponse = check_string($_POST['captcha_response'] ?? $_POST['recaptcha'] ?? $_POST['cf-turnstile-response'] ?? '');
    $captchaResult = verifyCaptchaResponse($captchaResponse, myip(), 'withdraw_affiliate');
    if (!$captchaResult['success']) {
        die(json_encode(['status' => 'error', 'msg' => $captchaResult['error_message']]));
    }
    // Validate bank input
    if (empty($_POST['bank'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng chọn ngân hàng cần rút')]));
    }
    $bank = validate_string($_POST['bank'], 100);
    if ($bank === false || strlen(trim($bank)) < 2) {
        die(json_encode(['status' => 'error', 'msg' => __('Tên ngân hàng không hợp lệ')]));
    }
    if (empty($_POST['stk'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số tài khoản cần rút')]));
    }
    $stk = validate_string($_POST['stk'], 100);
    if ($stk === false || strlen(trim($stk)) < 2) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tài khoản không hợp lệ')]));
    }
    if (empty($_POST['name'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập tên chủ tài khoản')]));
    }
    $name = validate_string($_POST['name'], 100);
    if ($name === false || strlen(trim($name)) < 2) {
        die(json_encode(['status' => 'error', 'msg' => __('Tên chủ tài khoản không hợp lệ')]));
    }
    if (empty($_POST['amount'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập số tiền cần rút')]));
    }
    if ($_POST['amount'] < $CMSNT->site('affiliate_min')) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền rút tối thiểu phải là') . ' ' . format_currency($CMSNT->site('affiliate_min'))]));
    }
    $amount = validate_float($_POST['amount'], 0.01);
    if ($amount === false) {
        die(json_encode(['status' => 'error', 'msg' => __('Số tiền không hợp lệ')]));
    }

    // BẮT ĐẦU TRANSACTION để tránh race condition
    $CMSNT->query("START TRANSACTION");

    try {
        // Lock row user bằng SELECT ... FOR UPDATE để đảm bảo không có request khác can thiệp
        $userLocked = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ? FOR UPDATE", [$getUser['id']]);

        if (!$userLocked) {
            $CMSNT->query("ROLLBACK");
            die(json_encode(['status' => 'error', 'msg' => __('Không thể lấy thông tin tài khoản')]));
        }

        // Kiểm tra lại số dư SAU KHI ĐÃ LOCK - đây là điểm quan trọng để tránh race condition
        if ($userLocked['ref_price'] < $amount) {
            $CMSNT->query("ROLLBACK");
            die(json_encode(['status' => 'error', 'msg' => __('Số dư hoa hồng khả dụng của bạn không đủ')]));
        }

        // Generate unique trans_id
        $trans_id = random('123456789QWERTYUIOPASDFGHJKLZXCVBNM', 6);
        while ($CMSNT->num_rows_safe("SELECT id FROM `aff_withdraw` WHERE `trans_id` = ?", [$trans_id]) > 0) {
            $trans_id = random('123456789QWERTYUIOPASDFGHJKLZXCVBNM', 6);
        }

        // Insert log trước khi trừ tiền
        $isInsertLog = $CMSNT->insert("aff_log", array(
            'user_id' => $userLocked['id'],
            'type' => 'withdraw', // Loại: rút tiền hoa hồng
            'sotientruoc' => $userLocked['ref_price'],
            'sotienthaydoi' => $amount,
            'sotienhientai' => $userLocked['ref_price'] - $amount,
            'create_gettime' => gettime(),
            'reason' => __('Rút số dư hoa hồng') . ' #' . $trans_id
        ));

        if (!$isInsertLog) {
            $CMSNT->query("ROLLBACK");
            die(json_encode(['status' => 'error', 'msg' => 'ERROR 1 - ' . __('System error')]));
        }

        // Trừ tiền trong transaction
        $isRemove = $CMSNT->tru("users", "ref_price", $amount, " `id` = '" . $userLocked['id'] . "' ");

        if (!$isRemove) {
            $CMSNT->query("ROLLBACK");
            die(json_encode(['status' => 'error', 'msg' => 'ERROR 2 - ' . __('System error')]));
        }

        // Kiểm tra nếu số dư bị âm quá nhiều (có thể do gian lận)
        $currentBalance = $userLocked['ref_price'] - $amount;
        if ($currentBalance < -5) {
            $CMSNT->query("ROLLBACK");
            $User = new users();
            $User->Banned($userLocked['id'], __('Gian lận khi rút số dư hoa hồng'));
            die(json_encode(['status' => 'error', 'msg' => __('Tài khoản của bạn đã bị khóa vì gian lận')]));
        }

        // Insert yêu cầu rút tiền
        $isInsert = $CMSNT->insert('aff_withdraw', [
            'trans_id'  => $trans_id,
            'user_id'   => $userLocked['id'],
            'bank'      => $bank,
            'stk'       => $stk,
            'name'      => $name,
            'amount'    => $amount,
            'status'    => 'pending',
            'create_gettime'    => gettime(),
            'update_gettime'    => gettime(),
            'reason'    => NULL
        ]);

        if (!$isInsert) {
            $CMSNT->query("ROLLBACK");
            die(json_encode(['status' => 'error', 'msg' => 'ERROR 3 - ' . __('System error')]));
        }

        // COMMIT transaction nếu mọi thứ thành công
        $CMSNT->query("COMMIT");

        // Gửi thông báo Telegram sau khi đã commit thành công
        $my_text = $CMSNT->site('noti_affiliate_withdraw');
        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
        $my_text = str_replace('{username}', $userLocked['username'], $my_text);
        $my_text = str_replace('{bank}', $bank, $my_text);
        $my_text = str_replace('{account_number}', $stk, $my_text);
        $my_text = str_replace('{account_name}', $name, $my_text);
        $my_text = str_replace('{amount}', format_currency($amount), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessTelegram($my_text, '', $CMSNT->site('affiliate_chat_id_telegram'));

        die(json_encode(['status' => 'success', 'msg' => __('Yêu cầu rút tiền được tạo thành công, vui lòng đợi ADMIN xử lý')]));
    } catch (Exception $e) {
        // Rollback nếu có bất kỳ lỗi nào xảy ra
        $CMSNT->query("ROLLBACK");
        die(json_encode(['status' => 'error', 'msg' => 'ERROR 4 - ' . __('System error')]));
    }
}



/**
 * Toggle yêu thích sản phẩm
 */
if ($_POST['action'] == 'toggleProductFavorite') {
    // Kiểm tra user đã đăng nhập
    $token = isset($_POST['token']) ? validate_string($_POST['token'], 255) : false;
    if (!$token) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng đăng nhập để sử dụng tính năng này')
        ]));
    }

    $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ? AND `banned` = 0", [$token]);
    if (!$getUser) {
        checkBlockIP('SCAN_TOKEN', 1);
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Phiên đăng nhập không hợp lệ')
        ]));
    }

    // Validate product_id
    $product_id = isset($_POST['product_id']) ? validate_int($_POST['product_id'], 1) : 0;
    if (!$product_id) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không hợp lệ')]));
    }

    // Kiểm tra sản phẩm có tồn tại không
    $product = $CMSNT->get_row_safe("SELECT `id`, `name` FROM `products` WHERE `id` = ? AND `status` = 1", [$product_id]);
    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại')]));
    }

    // Kiểm tra trạng thái yêu thích hiện tại
    $existing = $CMSNT->get_row_safe(
        "SELECT * FROM `product_favorites` WHERE `user_id` = ? AND `product_id` = ?",
        [$getUser['id'], $product_id]
    );

    $is_favorite = isset($_POST['is_favorite']) ? (int)$_POST['is_favorite'] : null;

    if ($existing) {
        // Nếu đã yêu thích, xóa khỏi danh sách
        $CMSNT->remove('product_favorites', "`user_id` = ? AND `product_id` = ?", [$getUser['id'], $product_id]);
        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã bỏ yêu thích sản phẩm'),
            'is_favorited' => false
        ]));
    } else {
        // Nếu chưa yêu thích, thêm vào danh sách
        $CMSNT->insert('product_favorites', [
            'user_id' => $getUser['id'],
            'product_id' => $product_id,
            'created_at' => gettime()
        ]);
        die(json_encode([
            'status' => 'success',
            'msg' => __('Đã thêm vào danh sách yêu thích'),
            'is_favorited' => true
        ]));
    }
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Request does not exist')
]));
