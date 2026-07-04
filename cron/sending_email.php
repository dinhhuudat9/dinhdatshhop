<?php

/**
 * Email Campaigns Sender - Cron Job
 * 
 * Xử lý gửi email marketing campaigns sử dụng SMTPMailer class.
 * 
 * URL: https://yoursite.com/cron/sending_email.php?key=YOUR_KEY_CRON_JOB
 * 
 * @author CMSNT.CO
 * @version 2.0.0
 */

define("IN_SITE", true);
require_once(__DIR__ . '/../libs/db.php');
require_once(__DIR__ . '/../libs/helper.php');
require_once(__DIR__ . '/../libs/lang.php');
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../libs/SMTPMailer.php');

$CMSNT = new DB();

// Kiểm tra key
if (!isset($_GET['key'])) {
    die(__('Vui lòng nhập Key Cron Job vào đường dẫn'));
}
if (isset($_GET['key']) && $_GET['key'] != $CMSNT->site('key_cron_job')) {
    die(__('Key không hợp lệ'));
}

// Chống spam - tối thiểu 3 giây giữa mỗi lần chạy
$lastRun = (int) $CMSNT->site('check_time_cron_sending_email');
if ($lastRun > 0 && (time() - $lastRun) < 3) {
    die('Thao tác quá nhanh, vui lòng đợi ' . (3 - (time() - $lastRun)) . ' giây!');
}

// Cập nhật thời gian chạy
$CMSNT->update("settings", [
    'value' => time()
], " `name` = 'check_time_cron_sending_email' ");

// Khởi tạo SMTPMailer
$mailer = new SMTPMailer();

// Kiểm tra SMTP đã bật chưa
if (!$mailer->isEnabled()) {
    die(__('Vui lòng cấu hình và kích hoạt SMTP'));
}

$processedCount = 0;
$successCount = 0;
$failedCount = 0;

// Lấy các chiến dịch đang chạy (status = 0: Processing)
$campaigns = $CMSNT->get_list_safe(
    "SELECT * FROM `email_campaigns` WHERE `status` = ?",
    [0]
);

foreach ($campaigns as $camp) {
    // Lấy 20 email pending cho mỗi chiến dịch
    $pendingEmails = $CMSNT->get_list_safe(
        "SELECT es.*, u.email, u.username 
         FROM `email_sending` es
         LEFT JOIN `users` u ON es.user_id = u.id
         WHERE es.camp_id = ? AND es.status = 0 
         ORDER BY es.id ASC 
         LIMIT 20",
        [$camp['id']]
    );

    foreach ($pendingEmails as $row) {
        $processedCount++;

        // Kiểm tra email người nhận
        if (empty($row['email'])) {
            $CMSNT->update('email_sending', [
                'status' => 2, // Failed
                'update_gettime' => gettime(),
                'response' => __('Không tìm thấy Email người nhận')
            ], "`id` = ?", [$row['id']]);
            $failedCount++;
            continue;
        }

        // Parse template variables
        $content = $camp['content'];
        $subject = $camp['subject'];

        // Replace các biến trong nội dung
        $variables = [
            '{username}' => $row['username'] ?? '',
            '{email}' => $row['email'] ?? '',
            '{domain}' => $_SERVER['SERVER_NAME'] ?? '',
            '{title}' => $CMSNT->site('title') ?? '',
            '{time}' => gettime(),
            '{year}' => date('Y')
        ];

        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
            $subject = str_replace($key, $value, $subject);
        }

        // NOTE: wrapInTemplate CHỈ được dùng ở đây (Email Campaigns).
        // Các email khác (order, flash sale, v.v.) sử dụng Mail Template từ admin settings.
        // Xem thêm: SMTPMailer::wrapInTemplate()
        $htmlBody = $mailer->wrapInTemplate($subject, $content);

        // Reset mailer cho email mới
        $mailer->reset();

        // Gửi email
        $sent = $mailer->quickSend(
            $row['email'],
            $row['username'] ?? '',
            $subject,
            $htmlBody
        );

        if ($sent) {
            // Thành công
            $CMSNT->update('email_sending', [
                'status' => 1, // Success
                'update_gettime' => gettime(),
                'response' => 'OK'
            ], "`id` = ?", [$row['id']]);
            $successCount++;
        } else {
            // Thất bại
            $CMSNT->update('email_sending', [
                'status' => 2, // Failed
                'update_gettime' => gettime(),
                'response' => $mailer->getLastError() ?: __('Gửi email thất bại')
            ], "`id` = ?", [$row['id']]);
            $failedCount++;
        }

        // Delay nhỏ để tránh quá tải SMTP
        usleep(100000); // 0.1 giây
    }

    // Kiểm tra xem chiến dịch đã hoàn thành chưa
    $pendingCount = $CMSNT->num_rows_safe(
        "SELECT id FROM `email_sending` WHERE `camp_id` = ? AND `status` = 0",
        [$camp['id']]
    );

    if ($pendingCount == 0) {
        // Đánh dấu chiến dịch hoàn thành
        $CMSNT->update('email_campaigns', [
            'status' => 1, // Completed
            'update_gettime' => gettime()
        ], "`id` = ?", [$camp['id']]);
    }
}

echo "Processed: {$processedCount}, Success: {$successCount}, Failed: {$failedCount}";
