<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../libs/db.php");
require_once(__DIR__ . "/../libs/lang.php");
require_once(__DIR__ . "/../libs/helper.php");
require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../libs/database/users.php");
require_once(__DIR__ . "/../libs/database/affiliate.php");
$CMSNT = new DB();
$AffiliateHandler = new AffiliateHandler();

if (isset($_POST['order_id']) && isset($_POST['billcode'])) {

    // Validate callback parameters
    $order_id = validate_alphanumeric($_POST['order_id'], 100);
    $billcode = validate_alphanumeric($_POST['billcode'], 100);
    $status = validate_int($_POST['status'], 0, 10);

    // Check validation results
    if ($order_id === false || $billcode === false || $status === false) {
        die('invalid_parameters');
    }


    if ($row = $CMSNT->get_row_safe("SELECT * FROM `payment_toyyibpay` WHERE `trans_id` = ? AND `status` = 0 AND `BillCode` = ?", [$order_id, $billcode])) {

        if ($status == 1) {
            $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$row['user_id']]);
            $isUpdate = $CMSNT->update('payment_toyyibpay', [
                'status'    => 1,
                'update_gettime'   => gettime()
            ], " `id` = ?", [$row['id']]);
            if ($isUpdate) {
                $amount = $amount / 100;
                $received = $row['amount'] * $CMSNT->site('toyyibpay_rate');
                $User->AddCredits($row['user_id'], $received, 'Automatic top-up via Malaysian bank #' . $billcode);

                // CỘNG HOA HỒNG AFFILIATE
                $AffiliateHandler->processRechargeCommission($getUser['id'], $received, 'TOPUP_toyyibpay_' . $billcode);
                // XỬ LÝ TIỀN NỢ NẾU CÓ
                debit_processing($getUser['id']);

                // TẠO LOG GIAO DỊCH GẦN ĐÂY
                $CMSNT->insert('deposit_log', [
                    'user_id'       => $row['user_id'],
                    'method'        => 'Toyyibpay',
                    'amount'        => $received,
                    'received'      => $received,
                    'create_time'   => time(),
                    'is_virtual'    => 0
                ]);
                /** SEND NOTI CHO ADMIN */
                $my_text = $CMSNT->site('noti_recharge');
                $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
                $my_text = str_replace('{username}', getRowRealtime('users', $row['user_id'], 'username'), $my_text);
                $my_text = str_replace('{method}', 'Toyyibpay', $my_text);
                $my_text = str_replace('{amount}', $received, $my_text);
                $my_text = str_replace('{price}', $received, $my_text);
                $my_text = str_replace('{time}', gettime(), $my_text);
                sendMessAdmin($my_text);
            }
        } else if ($status == 3) {
            $CMSNT->update('payment_toyyibpay', [
                'status'    => 2,
                'update_gettime'   => gettime()
            ], " `id` = ?", [$row['id']]);
        }
    }
}
