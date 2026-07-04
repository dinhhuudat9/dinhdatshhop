<?php
define("IN_SITE", true);
require_once(__DIR__ . '/libs/db.php');
require_once(__DIR__ . '/libs/lang.php');
require_once(__DIR__ . '/libs/helper.php');
require_once(__DIR__ . '/config.php');
$CMSNT = new DB();



insert_options('check_time_cron_email_queue', 0, 'Thời gian chạy cron email queue');
insert_options('noti_api_out_of_money', '', 'Thông báo API hết tiền');
insert_options('noti_api_connection_error', '', 'Thông báo lỗi kết nối API');

// Telegram Queue System
insert_options('check_time_cron_telegram_queue', 0, 'Thời gian chạy cron telegram queue');
insert_options('noti_order_success_admin', '', 'Thông báo Telegram đơn hàng mới cho Admin');
insert_options('noti_pending_order_admin', '⏳ *ĐƠN HÀNG ORDER MỚI*

👤 *Khách hàng:* {username}
📦 *Số đơn ORDER:* {order_count}
💰 *Tổng tiền:* {total_amount}
📋 *Mã đơn:* {order_ids}

📝 *Chi tiết:*
{order_details}

⚠️ Vui lòng xử lý đơn hàng!
🌐 {domain}
🕐 {time} | 📍 {ip}', 'Thông báo Telegram đơn hàng ORDER cần xử lý cho Admin');
insert_options('pending_order_telegram_chat_id', '', 'Chat ID Telegram riêng cho đơn hàng ORDER');
insert_options('noti_order_success_user', '', 'Thông báo Telegram mua hàng thành công cho User');
insert_options('noti_new_review', '', 'Thông báo Telegram đánh giá mới');

insert_options('email_temp_content_flash_sale_favorite', '', 'Nội dung email thông báo Flash Sale yêu thích');
insert_options('email_temp_subject_flash_sale_favorite', '', 'Tiêu đề email thông báo Flash Sale yêu thích');

// Order Completed Email - Gửi email khi đơn hàng thủ công hoàn thành
insert_options('email_temp_subject_order_completed', '[{title}] Đơn hàng #{trans_id} đã hoàn thành', 'Tiêu đề email thông báo đơn hàng hoàn thành');
insert_options('email_temp_content_order_completed', '
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
        <div style="font-size: 40px; margin-bottom: 10px;">🎉</div>
        <h2 style="color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;">✅ Đơn hàng đã hoàn thành!</h2>
    </div>
    
    <!-- Body -->
    <div style="padding: 30px;">
        <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
            Xin chào <strong>{username}</strong>,
        </p>
        <p style="color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;">
            Đơn hàng <strong>#{trans_id}</strong> của bạn đã được xử lý thành công! 🎉
        </p>
        
        <!-- Order Info Card -->
        <div style="background: linear-gradient(135deg, rgba(102,126,234,0.15) 0%, rgba(118,75,162,0.15) 100%); border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;">
            <p style="color: #333; font-size: 14px; margin: 0 0 10px 0;"><strong>📦 Sản phẩm:</strong> {product_name}</p>
            <p style="color: #333; font-size: 14px; margin: 0 0 10px 0;"><strong>📋 Gói:</strong> {plan_name}</p>
            <p style="color: #333; font-size: 14px; margin: 0 0 10px 0;"><strong>🔢 Số lượng:</strong> {quantity}</p>
            <p style="color: #333; font-size: 14px; margin: 0;"><strong>💰 Tổng tiền:</strong> <span style="color: #e53935; font-weight: 600;">{total_amount}</span></p>
        </div>
        
        <!-- Delivery Content -->
        <div style="background: #e8f5e9; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #4caf50;">
            <p style="color: #2e7d32; font-size: 14px; font-weight: 600; margin: 0 0 10px 0;">📄 Thông tin tài khoản:</p>
            <div style="background: #fff; border-radius: 6px; padding: 15px; font-family: monospace; font-size: 13px; color: #333; white-space: pre-wrap; word-break: break-all;">{delivery_content}</div>
        </div>
        
        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="{order_link}" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 14px;">Xem chi tiết đơn hàng</a>
        </div>
        
        <p style="color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;">
            <strong>Thời gian:</strong> {time}
        </p>
    </div>
    
    <!-- Footer -->
    <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;">
        <p style="color: #666; font-size: 13px; margin: 0 0 10px 0;">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>
        <p style="color: #888; font-size: 12px; margin: 0;">© {title}. All rights reserved.</p>
    </div>
</div>
', 'Nội dung email thông báo đơn hàng hoàn thành');

// Ticket Created User Email
insert_options('email_temp_subject_ticket_created_user', '[#{ticket_id}] Yêu cầu hỗ trợ của bạn đã được tiếp nhận - {title}', 'Tiêu đề email thông báo tạo ticket cho User');
insert_options('email_temp_content_ticket_created_user', '
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
        <div style="font-size: 40px; margin-bottom: 10px;">🎫</div>
        <h2 style="color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;">Ticket của bạn đã được tiếp nhận</h2>
    </div>
    
    <!-- Body -->
    <div style="padding: 30px;">
        <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
            Xin chào <strong>{username}</strong>,
        </p>
        <p style="color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;">
            Cảm ơn bạn đã liên hệ với chúng tôi. Yêu cầu hỗ trợ của bạn đã được tiếp nhận và đang được xử lý.
        </p>
        
        <!-- Ticket Info Card -->
        <div style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;">
            <p style="color: #333; font-size: 14px; margin: 0 0 10px 0;"><strong>🎫 Mã ticket:</strong> #{ticket_id}</p>
            <p style="color: #333; font-size: 14px; margin: 0 0 10px 0;"><strong>📝 Tiêu đề:</strong> {subject}</p>
            <p style="color: #333; font-size: 14px; margin: 0 0 10px 0;"><strong>📁 Danh mục:</strong> {category}</p>
            <p style="color: #333; font-size: 14px; margin: 0;"><strong>📦 Mã đơn hàng:</strong> {order_id}</p>
        </div>
        
        <div style="background: #e8f4fd; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
            <p style="color: #1565c0; font-size: 14px; margin: 0;">⏰ Chúng tôi sẽ phản hồi trong vòng <strong>24 giờ làm việc</strong></p>
        </div>
        
        <p style="color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;">
            <strong>Thời gian:</strong> {time}
        </p>
    </div>
    
    <!-- Footer -->
    <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;">
        <p style="color: #666; font-size: 13px; margin: 0 0 10px 0;">Cảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi!</p>
        <p style="color: #888; font-size: 12px; margin: 0;">© ' . date('Y') . ' {title}. All rights reserved.</p>
    </div>
</div>', 'Nội dung email thông báo tạo ticket cho User');


insert_options('policy_register', '');

// ==================== SYNC IMAGE FROM API ====================
// Thêm cột sync_image để bật/tắt đồng bộ ảnh từ API
$syncImageColumnExists = $CMSNT->get_row("SHOW COLUMNS FROM `suppliers` LIKE 'sync_image'");
if (!$syncImageColumnExists) {
    $CMSNT->query("ALTER TABLE `suppliers` ADD COLUMN `sync_image` VARCHAR(10) DEFAULT 'ON' AFTER `sync_category`");
}

insert_options('is_show_recently_viewed', 1, 'Tùy chọn ON/OFF Widget Hiển thị Sản phẩm đã xem');
