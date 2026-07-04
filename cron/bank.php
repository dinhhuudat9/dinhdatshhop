<?php

define("IN_SITE", true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/database/users.php');
require_once(__DIR__ . '/../libs/database/affiliate.php');
$CMSNT = new DB();
$user = new users();
$AffiliateHandler = new AffiliateHandler();


if (!isset($_GET['key'])) {
    die(__('Vui lòng nhập Key Cron Job vào đường dẫn'));
}
if (isset($_GET['key']) && $_GET['key'] != $CMSNT->site('key_cron_job')) {
    die(__('Key không hợp lệ'));
}

if (time() > $CMSNT->site('check_time_cron_bank')) {
    if (time() - $CMSNT->site('check_time_cron_bank') < 5) {
        die('[ÉT O ÉT ]Thao tác quá nhanh, vui lòng đợi');
    }
}
$CMSNT->update("settings", ['value' => time()], " `name` = 'check_time_cron_bank' ");

if (DEBUG) {
    echo "<pre>";
    echo "=== BẮT ĐẦU XỬ LÝ CRON BANK ===\n";
    echo "Thời gian: " . date('Y-m-d H:i:s') . "\n";
    echo "Tổng số ngân hàng đang hoạt động: " . $CMSNT->num_rows(" SELECT * FROM `banks` WHERE `status` = 1 AND `token` != '' ") . "\n\n";
}

$config_list_api_web2m = [
    'Vietcombank' => [
        'api' => 'https://api.web2m.com/historyapivcbv3/Password/AccountNumber/Token'
    ],
    'VCB' => [
        'api' => 'https://api.web2m.com/historyapivcbv3/Password/AccountNumber/Token'
    ],
    'MB' => [
        'api' => 'https://api.web2m.com/historyapimbv3/Password/AccountNumber/Token'
    ],
    'MBBank' => [
        'api' => 'https://api.web2m.com/historyapimbv3/Password/AccountNumber/Token'
    ],
    'TPBank' => [
        'api' => 'https://api.web2m.com/historyapitpbv3/Token'
    ],
    'Techcombank' => [
        'api' => 'https://api.web2m.com/historyapitcbv3/Password/AccountNumber/Token'
    ],
    'TCB' => [
        'api' => 'https://api.web2m.com/historyapitcbv3/Password/AccountNumber/Token'
    ],
    'ACB' => [
        'api' => 'https://api.web2m.com/historyapiacbv3/Password/AccountNumber/Token'
    ],
    'BIDV' => [
        'api' => 'https://api.web2m.com/historyapibidvv3/Password/AccountNumber/Token'
    ],
    'SeABank' => [
        'api' => 'https://api.web2m.com/historyapiseabankv3/Token'
    ],
    'VietinBank' => [
        'api' => 'https://api.web2m.com/historyapivtbv3/Password/AccountNumber/Token'
    ],
];

// Lấy danh sách tất cả các ngân hàng đang hoạt động
foreach ($CMSNT->get_list(" SELECT * FROM `banks` WHERE `status` = 1 AND `token` != '' ") as $bank) {

    if (DEBUG) {
        echo "--- XỬ LÝ NGÂN HÀNG: " . $bank['short_name'] . " ---\n";
        echo "Account Number: " . $bank['accountNumber'] . "\n";
    }

    // Nếu short_name không có trong danh sách API thì bỏ qua (không phân biệt hoa thường)
    $config_keys_lower = array_change_key_case($config_list_api_web2m, CASE_LOWER);
    if (!isset($config_keys_lower[strtolower($bank['short_name'])])) {
        if (DEBUG) {
            echo "[DEBUG] Bỏ qua ngân hàng " . $bank['short_name'] . " - Không có trong danh sách API hỗ trợ\n";
        }
        continue;
    }

    $api_url = $config_keys_lower[strtolower($bank['short_name'])]['api']; // Lấy URL API từ config

    // Nếu ngân hàng sử dụng OpenAPI, thay đổi URL từ historyapi thành historyapiopen
    if (isset($bank['is_openapi']) && $bank['is_openapi'] == 1) {
        $api_url = str_replace('historyapi', 'historyapiopen', $api_url);
        if (DEBUG) {
            echo "[DEBUG] Ngân hàng sử dụng OpenAPI - URL đã được thay đổi\n";
        }
    }

    if (DEBUG) {
        echo "[DEBUG] URL API gốc: " . $api_url . "\n";
    }

    // Thay thế các placeholder trong URL API
    if (strpos($api_url, 'Password') !== false) {
        $api_url = str_replace('Password', $bank['password'], $api_url);
    }
    if (strpos($api_url, 'AccountNumber') !== false) {
        $api_url = str_replace('AccountNumber', $bank['accountNumber'], $api_url);
    }
    if (strpos($api_url, 'Token') !== false) {
        $api_url = str_replace('Token', $bank['token'], $api_url);
    }

    if (DEBUG) {
        echo "[DEBUG] URL API sau khi thay thế: " . $api_url . "\n";
    }

    // Gọi API
    if (DEBUG) {
        echo "[DEBUG] Đang gọi API...\n";
    }

    $result = curl_get($api_url);

    // Nếu debug bật thì hiển thị kết quả từ API
    if ($CMSNT->site('debug_auto_bank') == 1) {
        echo $result;
    }

    $result = json_decode($result, true);

    if (DEBUG) {
        echo "[DEBUG] Kết quả JSON decode:\n";
        print_r($result);
        echo "\n";
    }

    // Kiểm tra status
    if (!isset($result['status']) || $result['status'] != true) {
        if (DEBUG) {
            echo "[DEBUG] API trả về status = false. Message: " . ($result['message'] ?? 'Không có message') . "\n";
            echo "[DEBUG] Dữ liệu trả về: ";
            print_r($result);
            echo "\n";
        }
        continue;
    }
    // Kiểm tra transactions
    if (empty($result['transactions'])) {
        if (DEBUG) {
            echo "[DEBUG] API không trả về giao dịch nào!\n";
        }
        continue;
    }

    if (DEBUG) {
        echo "[DEBUG] Số giao dịch nhận được: " . count($result['transactions']) . "\n";
    }

    foreach ($result['transactions'] as $data) {
        // Validate dữ liệu API trước khi sử dụng
        $tid_raw        = isset($data['transactionID']) ? $data['transactionID'] : '';
        $tid            = validate_alphanumeric($tid_raw, 255);
        $description    = validate_string(isset($data['description']) ? $data['description'] : '', 1000);
        $amount         = validate_float(isset($data['amount']) ? $data['amount'] : 0, 0.0);
        $type           = validate_string(isset($data['type']) ? $data['type'] : '', 10);


        if (DEBUG) {
            echo "[DEBUG] Xử lý giao dịch: TID=" . $tid . ", Amount=" . $amount . ", Type=" . $type . ", Desc=" . $description . "\n";
        }

        // Nếu là giao dịch rút tiền thì bỏ qua
        if ($type == 'OUT') {
            if (DEBUG) {
                echo "[DEBUG] Bỏ qua giao dịch rút tiền (OUT)\n";
            }
            continue;
        }

        // Tìm hoá đơn đã thanh toán trước đó hay chưa, nếu có thì bỏ qua
        if ($CMSNT->num_rows_safe(" SELECT * FROM `payment_bank_invoice` WHERE `api_tid` = ? AND `api_desc` = ? AND `short_name` = ? ", [$tid, $description, $bank['short_name']]) > 0) {
            if (DEBUG) {
                echo "[DEBUG] Giao dịch đã được xử lý trước đó, bỏ qua\n";
            }
            continue;
        }

        if (DEBUG) {
            echo "[DEBUG] Tìm hóa đơn chờ thanh toán với số tiền: " . $amount . "\n";
        }

        // Xử lý những bill đủ điều kiện
        $waiting_invoices = whereInvoiceWaiting($bank['short_name'], $amount);

        if (DEBUG) {
            echo "[DEBUG] Số hóa đơn chờ thanh toán tìm được: " . count($waiting_invoices) . "\n";
        }

        if (empty($waiting_invoices)) {
            if (DEBUG) {
                echo "[DEBUG] Không tìm thấy hóa đơn chờ thanh toán phù hợp với số tiền: $amount\n";
            }
        }

        foreach ($waiting_invoices as $invoice) {
            if (DEBUG) {
                echo "[DEBUG] Kiểm tra hóa đơn: " . $invoice['trans_id'] . " (Số tiền: " . $invoice['amount'] . ")\n";
            }

            // Tìm kiếm nội dung chuyển tiền trong hóa đơn xem có trans_id không
            $exploded = explode(mb_strtoupper($invoice['trans_id']), mb_strtoupper(str_replace(' ', '', $description)));
            if (isset($exploded[1])) {
                if (DEBUG) {
                    echo "[DEBUG] Tìm thấy trans_id trong nội dung chuyển tiền!\n";
                }

                // Cập nhật trạng thái và thông tin hóa đơn
                if (DEBUG) {
                    echo "[DEBUG] Chuẩn bị cập nhật hóa đơn ID: " . $invoice['id'] . "\n";
                    echo "[DEBUG] SQL điều kiện: id = " . $invoice['id'] . " AND status = 'waiting'\n";
                }

                // Thử cập nhật với retry mechanism
                $isUpdate = false;
                $retryCount = 0;
                $maxRetries = 3;

                while ($retryCount < $maxRetries && !$isUpdate) {
                    if ($retryCount > 0) {
                        if (DEBUG) {
                            echo "[DEBUG] Retry lần " . $retryCount . " sau 2 giây...\n";
                        }
                        sleep(2); // Đợi 2 giây trước khi retry
                    }

                    $isUpdate = $CMSNT->update("payment_bank_invoice", [
                        'status'        => 'completed',
                        'api_tid'       => $tid,
                        'api_desc'      => $description,
                        'api_type'      => 'WEB2M',
                        'updated_at'    => gettime()
                    ], " `id` = ? AND `status` = 'waiting' ", [$invoice['id']]);

                    if (!$isUpdate && DEBUG) {
                        echo "[DEBUG] Lần thử " . ($retryCount + 1) . " thất bại\n";
                    }

                    $retryCount++;
                }

                if (DEBUG) {
                    if ($isUpdate) {
                        echo "[DEBUG] Cập nhật hóa đơn: THÀNH CÔNG - Đã cập nhật " . $isUpdate . " bản ghi";
                        if ($retryCount > 1) {
                            echo " (sau " . ($retryCount - 1) . " lần retry)";
                        }
                        echo "\n";
                    } else {
                        echo "[DEBUG] Cập nhật hóa đơn: THẤT BẠI (đã thử " . $retryCount . " lần)\n";
                        echo "[DEBUG] Chi tiết lỗi:\n";
                        echo "[DEBUG] - Invoice ID: " . $invoice['id'] . "\n";
                        echo "[DEBUG] - Status hiện tại: " . $invoice['status'] . "\n";
                        echo "[DEBUG] - TID: " . $tid . "\n";
                        echo "[DEBUG] - Description: " . $description . "\n";

                        // Kiểm tra xem hóa đơn có tồn tại không
                        $check_invoice = $CMSNT->get_row_safe("SELECT * FROM `payment_bank_invoice` WHERE `id` = ?", [$invoice['id']]);
                        if ($check_invoice) {
                            echo "[DEBUG] - Hóa đơn tồn tại với status: " . $check_invoice['status'] . "\n";
                            if ($check_invoice['status'] != 'waiting') {
                                echo "[DEBUG] - LỖI: Hóa đơn không ở trạng thái 'waiting' nên không thể cập nhật\n";
                            }
                        } else {
                            echo "[DEBUG] - LỖI: Không tìm thấy hóa đơn với ID: " . $invoice['id'] . "\n";
                        }

                        // Kiểm tra xem có duplicate api_tid không
                        $check_duplicate = $CMSNT->get_row_safe("SELECT * FROM `payment_bank_invoice` WHERE `api_tid` = ? AND `id` != ?", [$tid, $invoice['id']]);
                        if ($check_duplicate) {
                            echo "[DEBUG] - LỖI: TID đã tồn tại trong hóa đơn khác (ID: " . $check_duplicate['id'] . ")\n";
                        }
                    }
                }

                if ($isUpdate) {
                    // Xử lý cộng tiền cho tài khoản User đủ điều kiện
                    $isCong = $user->AddCredits($invoice['user_id'], $invoice['received'], __('Thanh toán hoá đơn nạp tiền') . ' #' . $invoice['trans_id'], 'INVOICE_' . $invoice['trans_id']);

                    if (DEBUG) {
                        if ($isCong) {
                            echo "[DEBUG] Cộng tiền cho user: THÀNH CÔNG\n";
                            echo "[DEBUG] - User ID: " . $invoice['user_id'] . "\n";
                            echo "[DEBUG] - Số tiền cộng: " . format_currency($invoice['received']) . "\n";
                            echo "[DEBUG] - Lý do: Thanh toán hoá đơn nạp tiền #" . $invoice['trans_id'] . "\n";
                        } else {
                            echo "[DEBUG] Cộng tiền cho user: THẤT BẠI\n";
                            echo "[DEBUG] Chi tiết lỗi cộng tiền:\n";
                            echo "[DEBUG] - User ID: " . $invoice['user_id'] . "\n";
                            echo "[DEBUG] - Số tiền cần cộng: " . format_currency($invoice['received']) . "\n";

                            // Kiểm tra user có tồn tại không
                            $check_user = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `id` = ?", [$invoice['user_id']]);
                            if ($check_user) {
                                echo "[DEBUG] - User tồn tại: " . $check_user['username'] . "\n";
                                echo "[DEBUG] - Số dư hiện tại: " . format_currency($check_user['money']) . "\n";
                            } else {
                                echo "[DEBUG] - LỖI: Không tìm thấy user với ID: " . $invoice['user_id'] . "\n";
                            }
                        }
                    }

                    if ($isCong) {
                        // LẤY THÔNG TIN USER
                        $getUser = $CMSNT->get_row_safe(" SELECT * FROM `users` WHERE `id` = ? ", [$invoice['user_id']]);

                        if (DEBUG) {
                            echo "[DEBUG] User ID: " . $invoice['user_id'] . ", Username: " . $getUser['username'] . "\n";
                        }

                        // CỘNG HOA HỒNG AFFILIATE
                        $affiliateResult = $AffiliateHandler->processRechargeCommission($getUser['id'], $invoice['received'], 'INVOICE_' . $invoice['trans_id']);

                        if (DEBUG) {
                            if ($affiliateResult) {
                                echo "[DEBUG] Cộng hoa hồng affiliate thành công cho user_id: " . $getUser['id'] . "\n";
                            } else {
                                echo "[DEBUG] Không cộng hoa hồng affiliate (không đủ điều kiện hoặc lỗi)\n";
                            }
                        }
                        // XỬ LÝ TIỀN NỢ NẾU CÓ
                        debit_processing($getUser['id']);
                        // TẠO LOG GIAO DỊCH GẦN ĐÂY
                        $CMSNT->insert('deposit_log', [
                            'user_id'       => $invoice['user_id'],
                            'method'        => $bank['short_name'],
                            'amount'        => $invoice['amount'],
                            'received'      => $invoice['received'],
                            'create_time'   => time(),
                            'is_virtual'    => 0
                        ]);
                        // GỬI THÔNG BÁO CHO ADMIN
                        $my_text = $CMSNT->site('noti_recharge');
                        $my_text = str_replace('{domain}', check_string($_SERVER['SERVER_NAME']), $my_text);
                        $my_text = str_replace('{title}', $CMSNT->site('title'), $my_text);
                        $my_text = str_replace('{trans_id}', $invoice['trans_id'], $my_text);
                        $my_text = str_replace('{username}', getRowRealtime('users', $invoice['user_id'], 'username'), $my_text);
                        $my_text = str_replace('{method}', $bank['short_name'], $my_text);
                        $my_text = str_replace('{amount}', format_currency($invoice['amount']), $my_text);
                        $my_text = str_replace('{price}', format_currency($invoice['received']), $my_text);
                        $my_text = str_replace('{time}', gettime(), $my_text);
                        sendMessAdmin($my_text);
                        echo '[<b style="color:green">-</b>] Xử lý thành công 1 hoá đơn.' . PHP_EOL;
                        break;
                    } else {
                        if (DEBUG) {
                            echo "[DEBUG] LỖI: Không thể cộng tiền cho user\n";
                        }
                        break;
                    }
                } else {
                    if (DEBUG) {
                        echo "[DEBUG] LỖI: Không thể cập nhật trạng thái hóa đơn\n";
                    }
                    break;
                }
            } else {
                if (DEBUG) {
                    echo "[DEBUG] Không tìm thấy trans_id '" . $invoice['trans_id'] . "' trong description: $description\n";
                }
            }
        }
    }

    if (DEBUG) {
        echo "--- KẾT THÚC XỬ LÝ NGÂN HÀNG: " . $bank['short_name'] . " ---\n\n";
    }
}

if (DEBUG) {
    echo "=== CẬP NHẬT HÓA ĐƠN HẾT HẠN ===\n";
}

// CẬP NHẬT TRẠNG THÁI HÓA ĐƠN HẾT THỜI GIAN
$expired_count = $CMSNT->update('payment_bank_invoice', [
    'status'    => 'expired'
], " `status` = 'waiting' AND " . time() . " - `create_time` > " . $CMSNT->site('bank_expired_invoice') . " ");

if (DEBUG) {
    echo "[DEBUG] Số hóa đơn hết hạn đã cập nhật: " . $expired_count . "\n";
    echo "=== KẾT THÚC XỬ LÝ CRON BANK ===\n";
    echo "</pre>";
}

curl_get2(base_url('cron/cron.php?key=' . $CMSNT->site('key_cron_job')));
