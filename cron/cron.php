<?php

define("IN_SITE", true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/SMTPMailer.php');
$CMSNT = new DB();

if (!isset($_GET['key'])) {
    die(__('Vui lòng nhập Key Cron Job vào đường dẫn'));
}
if (isset($_GET['key']) && $_GET['key'] != $CMSNT->site('key_cron_job')) {
    die(__('Key không hợp lệ'));
}

/* START CHỐNG SPAM */
if (time() > $CMSNT->site('check_time_cron_cron')) {
    if (time() - $CMSNT->site('check_time_cron_cron') < 3) {
        die('Thao tác quá nhanh, vui lòng thử lại sau!');
    }
}
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'check_time_cron_cron' ");


// Thay đổi trạng thái hóa đơn Bank hết hạn
$CMSNT->update("payment_bank_invoice", [
    'status' => 'expired'
], " `status` = 'waiting' AND `created_at` <= NOW() - INTERVAL " . intval($CMSNT->site('bank_expired_invoice')) . " SECOND ");

// Thay đổi trạng thái hóa đơn Crypto hết hạn
$CMSNT->update("payment_crypto", [
    'status' => 'expired'
], " `status` = 'waiting' AND `create_gettime` <= NOW() - INTERVAL 86400 SECOND ");


// Task chỉ xử lý mỗi 24 giờ
if (time() > $CMSNT->site('task_24h')) {
    if (time() - $CMSNT->site('task_24h') > 86400) {
        $CMSNT->update("settings", [
            'value' => time()
        ], " `name` = 'task_24h' ");

        // Dọn dẹp failed_attempts
        $isRemove = $CMSNT->remove('failed_attempts', " `create_gettime` <= NOW() - INTERVAL 1 DAY ");
        if ($isRemove) {
            $CMSNT->insert("logs", [
                'user_id'     => 0, // 0 = log hệ thống
                'action'      => __('Hệ thống thực hiện dọn dẹp failed_attempts sau mỗi 24 giờ'),
                'createdate'  => gettime(),
                'ip'          => myip(),
                'device'      => getUserAgent()
            ]);
        }

        // Xóa file CMSNT.CO thừa
        if (is_dir(__DIR__ . '/../CMSNT.CO')) {
            deleteFolder(__DIR__ . '/../CMSNT.CO');

            $CMSNT->insert("logs", [
                'user_id'     => 0, // 0 = log hệ thống
                'action'      => __('Hệ thống thực hiện xóa file rác'),
                'createdate'  => gettime(),
                'ip'          => myip(),
                'device'      => getUserAgent()
            ]);
        }
    }
}

// ============================================================================
// KIỂM TRA ĐƠN HÀNG HẾT HẠN VÀ GỬI EMAIL THÔNG BÁO
// ============================================================================
if ($CMSNT->site('enable_order_expiry_email') == 1 && $CMSNT->site('smtp_status') == 1) {
    // Lấy đơn hàng completed có thời hạn, đã hết hạn hoặc sắp hết hạn (trong 7 ngày)
    // và CHƯA được gửi thông báo tương ứng
    $expiring_orders = $CMSNT->get_list_safe("
        SELECT po.*, 
               p.`name` as product_name, 
               pp.`name` as plan_name,
               pp.`duration_type`,
               pp.`duration_value`,
               u.`email` as user_email,
               u.`username` as user_username,
               CASE 
                   WHEN po.`custom_expiry_date` IS NOT NULL THEN po.`custom_expiry_date`
                   WHEN pp.`duration_type` = 'day' THEN DATE_ADD(COALESCE(po.`completed_at`, po.`updated_at`), INTERVAL pp.`duration_value` DAY)
                   WHEN pp.`duration_type` = 'month' THEN DATE_ADD(COALESCE(po.`completed_at`, po.`updated_at`), INTERVAL pp.`duration_value` MONTH)
                   WHEN pp.`duration_type` = 'year' THEN DATE_ADD(COALESCE(po.`completed_at`, po.`updated_at`), INTERVAL pp.`duration_value` YEAR)
                   ELSE NULL
               END as calculated_expiry_date,
               oen_expired.id as has_expired_notification,
               oen_expiring.id as has_expiring_notification
        FROM `product_orders` po 
        LEFT JOIN `products` p ON po.`product_id` = p.`id` 
        LEFT JOIN `product_plans` pp ON po.`plan_id` = pp.`id`
        LEFT JOIN `users` u ON po.`user_id` = u.`id`
        LEFT JOIN `order_expiry_notifications` oen_expired 
            ON po.`id` = oen_expired.`order_id` AND oen_expired.`notification_type` = 'expired'
        LEFT JOIN `order_expiry_notifications` oen_expiring 
            ON po.`id` = oen_expiring.`order_id` AND oen_expiring.`notification_type` = 'expiring_soon'
        WHERE po.`status` = 'completed' 
        AND pp.`duration_type` IS NOT NULL 
        AND pp.`duration_type` != 'lifetime'
        AND pp.`duration_type` != ''
        AND u.`email` IS NOT NULL 
        AND u.`email` != ''
        HAVING calculated_expiry_date IS NOT NULL 
        AND (
            (calculated_expiry_date < NOW() AND has_expired_notification IS NULL)
            OR (calculated_expiry_date >= NOW() AND calculated_expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND has_expiring_notification IS NULL)
        )
        ORDER BY calculated_expiry_date ASC
    ", []);

    $mailer = new SMTPMailer();
    $queued_count = 0;

    foreach ($expiring_orders as $order) {
        // Ưu tiên sử dụng custom_expiry_date nếu có
        if (!empty($order['custom_expiry_date'])) {
            $expiry_date = strtotime($order['custom_expiry_date']);
        } else {
            // Tính thời gian hết hạn từ completed_at (fallback về updated_at)
            $completed_time = !empty($order['completed_at'])
                ? strtotime($order['completed_at'])
                : strtotime($order['updated_at']);
            $expiry_date = null;

            switch ($order['duration_type']) {
                case 'day':
                    $expiry_date = strtotime('+' . $order['duration_value'] . ' days', $completed_time);
                    break;
                case 'month':
                    $expiry_date = strtotime('+' . $order['duration_value'] . ' months', $completed_time);
                    break;
                case 'year':
                    $expiry_date = strtotime('+' . $order['duration_value'] . ' years', $completed_time);
                    break;
            }
        }

        if (!$expiry_date) continue;

        $is_expired = time() > $expiry_date;
        $days_remaining = ceil(($expiry_date - time()) / 86400);

        // Xác định loại thông báo
        $notification_type = null;
        if ($is_expired) {
            $notification_type = 'expired';
        } elseif ($days_remaining <= 7 && $days_remaining > 0) {
            $notification_type = 'expiring_soon';
        }

        if (!$notification_type) continue;

        // Tạo nội dung email từ template
        $subject_key = $notification_type == 'expired'
            ? 'email_temp_subject_order_expired'
            : 'email_temp_subject_order_expiring';
        $subject_template = $CMSNT->site($subject_key) ?: ($notification_type == 'expired'
            ? 'Đơn hàng của bạn đã hết hạn - {product_name}'
            : 'Đơn hàng của bạn sắp hết hạn - {product_name}');

        $content_template = $CMSNT->site('email_temp_content_order_expiry') ?: '<p>Xin chào <strong>{username}</strong>,</p>
<p>{expiry_message}</p>
<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
<p style="margin: 5px 0;"><strong>Sản phẩm:</strong> {product_name}</p>
<p style="margin: 5px 0;"><strong>Gói:</strong> {plan_name}</p>
<p style="margin: 5px 0;"><strong>Mã đơn hàng:</strong> {trans_id}</p>
<p style="margin: 5px 0;"><strong>Ngày hết hạn:</strong> {expiry_date}</p>
</div>
<p>Vui lòng liên hệ với chúng tôi nếu bạn muốn gia hạn đơn hàng.</p>
<p>Trân trọng,<br>{title}</p>';

        // Tạo expiry_message tự động
        $expiry_message = $notification_type == 'expired'
            ? 'Đơn hàng của bạn đã <strong style="color: #dc3545;">hết hạn</strong>.'
            : 'Đơn hàng của bạn sẽ <strong style="color: #ffc107;">hết hạn sau ' . $days_remaining . ' ngày</strong>.';

        // Variables để thay thế
        $variables = [
            '{domain}' => $_SERVER['SERVER_NAME'] ?? '',
            '{title}' => $CMSNT->site('title') ?? '',
            '{username}' => htmlspecialchars($order['user_username']),
            '{product_name}' => htmlspecialchars($order['product_name']),
            '{plan_name}' => htmlspecialchars($order['plan_name']),
            '{trans_id}' => htmlspecialchars($order['trans_id']),
            '{expiry_date}' => date('d/m/Y H:i', $expiry_date),
            '{days_remaining}' => $days_remaining,
            '{expiry_message}' => $expiry_message,
            '{time}' => date('d/m/Y H:i:s')
        ];

        $subject = str_replace(array_keys($variables), array_values($variables), $subject_template);
        $content = str_replace(array_keys($variables), array_values($variables), $content_template);

        // Bỏ qua nếu subject hoặc content trống
        if (empty(trim($subject)) || empty(trim($content))) {
            continue;
        }

        // Queue email (không wrap template vì admin đã tạo nội dung đầy đủ)
        $queued = $mailer->queueEmail(
            $order['user_email'],
            $order['user_username'],
            $subject,
            $content,
            2, // Priority medium
            ['type' => 'order_expiry', 'order_id' => $order['id'], 'notification_type' => $notification_type]
        );

        if ($queued) {
            // Lưu vào bảng tracking
            $CMSNT->insert('order_expiry_notifications', [
                'order_id' => $order['id'],
                'notification_type' => $notification_type,
                'sent_at' => date('Y-m-d H:i:s')
            ]);
            $queued_count++;
        }
    }

    if ($queued_count > 0) {
        echo "Queued {$queued_count} expiry notification emails.<br>";
    }
}





$CMSNT->remove('deposit_log', " " . time() . " - `create_time` >= 604800 ");
$CMSNT->remove('order_log', " " . time() . " - `create_time` >= 604800 ");


// Tạo sitemap chuẩn SEO với đầy đủ thông tin
$urls = array();

// Thêm trang chủ với độ ưu tiên cao nhất
$urls[] = array(
    'loc' => base_url(),
    'lastmod' => date('Y-m-d\TH:i:s+07:00'),
    'changefreq' => 'daily',
    'priority' => '1.0'
);

// Thêm trang danh sách sản phẩm
$urls[] = array(
    'loc' => base_url('?action=products'),
    'lastmod' => date('Y-m-d\TH:i:s+07:00'),
    'changefreq' => 'daily',
    'priority' => '0.9'
);

// Thêm URL cho các danh mục cha (parent_id = 0)
foreach ($CMSNT->get_list("SELECT `id`, `slug`, `created_at` FROM `categories` WHERE `status` = 'show' AND `parent_id` = 0 ORDER BY `stt` DESC") as $parent) {
    $urls[] = array(
        'loc' => base_url('?action=products&parent=' . $parent['slug']),
        'lastmod' => date('Y-m-d\TH:i:s+07:00', strtotime($parent['created_at'])),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    );
}

// Thêm URL cho các danh mục con (parent_id > 0)
foreach ($CMSNT->get_list("SELECT `id`, `slug`, `created_at` FROM `categories` WHERE `status` = 'show' AND `parent_id` > 0 ORDER BY `stt` DESC") as $category) {
    $urls[] = array(
        'loc' => base_url('?action=products&category=' . $category['slug']),
        'lastmod' => date('Y-m-d\TH:i:s+07:00', strtotime($category['created_at'])),
        'changefreq' => 'weekly',
        'priority' => '0.7'
    );
}

// Thêm URL cho các sản phẩm
foreach ($CMSNT->get_list("SELECT `id`, `slug`, `created_at` FROM `products` WHERE `status` = 'show' ORDER BY `id` DESC") as $product) {
    $urls[] = array(
        'loc' => base_url('?action=product&slug=' . $product['slug']),
        'lastmod' => date('Y-m-d\TH:i:s+07:00', strtotime($product['created_at'])),
        'changefreq' => 'monthly',
        'priority' => '0.6'
    );
}

// Giới hạn số lượng URL tối đa 50,000 (chuẩn sitemap)
if (count($urls) > 50000) {
    $urls = array_slice($urls, 0, 50000);
}

// Tạo tệp XML chuẩn SEO
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

// Tạo phần tử gốc <urlset> với đầy đủ namespace
$urlset = $xml->createElement('urlset');
$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

// Thêm các URL với đầy đủ thông tin SEO
foreach ($urls as $urlData) {
    $urlElement = $xml->createElement('url');

    // Thêm loc (URL)
    $locElement = $xml->createElement('loc', htmlspecialchars($urlData['loc']));
    $urlElement->appendChild($locElement);

    // Thêm lastmod (thời gian cập nhật cuối)
    $lastmodElement = $xml->createElement('lastmod', $urlData['lastmod']);
    $urlElement->appendChild($lastmodElement);

    // Thêm changefreq (tần suất thay đổi)
    $changefreqElement = $xml->createElement('changefreq', $urlData['changefreq']);
    $urlElement->appendChild($changefreqElement);

    // Thêm priority (độ ưu tiên)
    $priorityElement = $xml->createElement('priority', $urlData['priority']);
    $urlElement->appendChild($priorityElement);

    $urlset->appendChild($urlElement);
}

$xml->appendChild($urlset);

// Lưu sitemap vào tệp
$xml->save('../sitemap.xml');
