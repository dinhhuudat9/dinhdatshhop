-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th2 06, 2026 lúc 12:59 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.4.17
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `shopkey`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `active_sessions`
--

CREATE TABLE `active_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `session_token` varchar(255) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `active_sessions`
--

INSERT INTO `active_sessions` (`id`, `user_id`, `session_token`, `device_token`, `ip_address`, `user_agent`, `last_activity`, `created_at`) VALUES
(1, 1, 'guanvhLpnouu8b59062387e4606e3fc8826e3cf960cc4c708f9cba6cc6578bb91975aa948c63522da468e9111207ad813298cad5ccc44d3a38224afdfa53dcfc0bc577f09592', 'bb1cc43356cd98756c56a7d50a032287cce6145fea5093ddb9dfc4d4a01596a5', '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:22:27', '2026-02-05 22:04:41'),
(2, 2, 'yb2bdhgn2utu7085336eef9892337a8efe7b53a3f3845288194ce53767b03e11c3cacd3ff0caa086e0d9523156b5d2d21de555a901361bc04ad3c40722a5674a08380c7c7eaa', '5f5ec97ba21446a65b80e8d26e239da586add9db11aa73c8821a2797d87a7842', '113.174.135.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:09:27', '2026-02-05 23:06:46'),
(3, 3, 'iclumtl99eac6d5f0db6613290c2a7eef34d71dec87473937aeefede0eefce69cdd8eec6bf18832a05e86abdaaa39c759fc8091add7c1e6867c040c992e962f97b57eee', '6849252cd1d94f93d7108f565ee5aaeeeff9d51b1d35d45cd5007bbd23ad234e', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 06:53:11', '2026-02-05 23:37:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin_role`
--

CREATE TABLE `admin_role` (
  `id` int(11) NOT NULL,
  `name` mediumtext DEFAULT NULL,
  `role` longtext DEFAULT NULL CHECK (json_valid(`role`)),
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admin_role`
--

INSERT INTO `admin_role` (`id`, `name`, `role`, `create_gettime`, `update_gettime`) VALUES
(1, 'Super Admin', '[\"view_license\",\"view_statistical\",\"view_recent_transactions\",\"view_logs\",\"edit_logs\",\"view_transactions\",\"edit_transactions\",\"view_bot_telegram_logs\",\"edit_bot_telegram_logs\",\"view_block_ip\",\"edit_block_ip\",\"view_automations\",\"edit_automations\",\"view_media_library\",\"edit_media_library\",\"view_addons\",\"edit_addons\",\"view_user\",\"edit_user\",\"login_user\",\"view_role\",\"edit_role\",\"view_ticket\",\"edit_ticket\",\"config_ticket\",\"view_recharge\",\"edit_recharge\",\"view_recharge_bank_invoice\",\"edit_recharge_bank_invoice\",\"view_affiliate\",\"view_withdraw_affiliate\",\"edit_withdraw_affiliate\",\"edit_affiliate\",\"view_email_campaigns\",\"edit_email_campaigns\",\"view_blog\",\"edit_blog\",\"view_category\",\"edit_category\",\"view_product\",\"edit_product\",\"view_product_plan\",\"edit_product_plan\",\"view_product_stock\",\"edit_product_stock\",\"view_orders_product\",\"edit_orders_product\",\"refund_orders_product\",\"view_order_product\",\"delete_order_product\",\"view_product_reviews\",\"edit_product_reviews\",\"delete_product_reviews\",\"manager_suppliers\",\"view_suppliers\",\"request_api\",\"view_coupon\",\"edit_coupon\",\"view_flash_sale\",\"edit_flash_sale\",\"view_api_keys\",\"edit_api_keys\",\"view_api_logs\",\"view_lang\",\"edit_lang\",\"view_currency\",\"edit_currency\",\"edit_theme\",\"edit_setting\",\"edit_general\",\"edit_shopkey\",\"edit_connection\",\"edit_notification\",\"edit_telegram_template\",\"edit_mail_template\",\"edit_security\",\"edit_widget\",\"edit_cron_jobs\",\"edit_banners\",\"edit_sliders\"]', '2023-11-16 20:28:54', '2026-02-06 06:49:58'),
(2, 'Sales', '[\"view_logs\",\"view_transactions\",\"view_user\",\"view_affiliate\",\"view_withdraw_affiliate\",\"view_coupon\"]', '2023-11-16 20:41:10', '2023-11-16 20:53:56');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `affiliate_clicks`
--

CREATE TABLE `affiliate_clicks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID người sở hữu link affiliate',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referer` text DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `is_unique` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: unique, 0: repeat',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Theo dõi click affiliate link';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `affiliate_commissions`
--

CREATE TABLE `affiliate_commissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID người nhận hoa hồng (referrer)',
  `referral_id` int(11) NOT NULL COMMENT 'ID người tạo ra hoa hồng (referred user)',
  `type` enum('recharge','order','signup') NOT NULL DEFAULT 'recharge' COMMENT 'Loại hoa hồng',
  `source_id` int(11) DEFAULT NULL COMMENT 'ID nguồn (order_id hoặc transaction_id)',
  `source_trans_id` varchar(50) DEFAULT NULL COMMENT 'Mã giao dịch nguồn',
  `source_amount` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền nguồn (nạp tiền/đơn hàng)',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Tỷ lệ hoa hồng (%)',
  `commission_amount` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền hoa hồng nhận được',
  `status` enum('pending','approved','cancelled') NOT NULL DEFAULT 'approved' COMMENT 'Trạng thái',
  `note` text DEFAULT NULL COMMENT 'Ghi chú',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Chi tiết hoa hồng affiliate';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `affiliate_stats`
--

CREATE TABLE `affiliate_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID người dùng',
  `total_clicks` int(11) NOT NULL DEFAULT 0,
  `unique_clicks` int(11) NOT NULL DEFAULT 0,
  `total_referrals` int(11) NOT NULL DEFAULT 0 COMMENT 'Tổng số người giới thiệu',
  `total_orders` int(11) NOT NULL DEFAULT 0 COMMENT 'Tổng đơn hàng từ referral',
  `total_order_amount` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền đơn hàng từ referral',
  `total_recharge_amount` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền nạp từ referral',
  `total_commission_earned` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng hoa hồng đã kiếm được',
  `total_commission_withdrawn` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng hoa hồng đã rút',
  `available_balance` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Số dư khả dụng',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Thống kê affiliate (cache)';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `aff_log`
--

CREATE TABLE `aff_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `type` enum('recharge','order','withdraw','refund','manual','signup') DEFAULT 'recharge' COMMENT 'Loại giao dịch',
  `referral_id` int(11) DEFAULT NULL COMMENT 'ID người tạo hoa hồng',
  `reason` mediumtext DEFAULT NULL,
  `sotientruoc` float NOT NULL DEFAULT 0,
  `sotienthaydoi` float NOT NULL DEFAULT 0,
  `sotienhientai` float NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `aff_withdraw`
--

CREATE TABLE `aff_withdraw` (
  `id` int(11) NOT NULL,
  `trans_id` mediumtext DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `bank` mediumtext DEFAULT NULL,
  `stk` mediumtext DEFAULT NULL,
  `name` mediumtext DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `status` varchar(25) NOT NULL DEFAULT 'pending',
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `reason` mediumtext DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL COMMENT 'ID admin xử lý',
  `processed_at` datetime DEFAULT NULL COMMENT 'Thời gian xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'ID người dùng sở hữu API key',
  `key_name` varchar(100) NOT NULL DEFAULT 'API Key' COMMENT 'Tên đặt cho API key',
  `name` varchar(100) NOT NULL DEFAULT 'API Key' COMMENT 'Tên đặt cho API key',
  `api_key` varchar(64) NOT NULL,
  `api_secret` varchar(128) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách quyền: orders.create, orders.view, products.view, balance.view, all' CHECK (json_valid(`permissions`)),
  `ip_whitelist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Danh sách IP được phép (null = tất cả)' CHECK (json_valid(`ip_whitelist`)),
  `rate_limit_per_minute` int(11) UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Giới hạn request/phút',
  `rate_limit_per_day` int(11) UNSIGNED NOT NULL DEFAULT 10000 COMMENT 'Giới hạn request/ngày',
  `rate_limit` int(11) UNSIGNED NOT NULL DEFAULT 60 COMMENT 'Giới hạn request/phút',
  `daily_limit` int(11) UNSIGNED NOT NULL DEFAULT 10000 COMMENT 'Giới hạn request/ngày',
  `expires_at` datetime DEFAULT NULL COMMENT 'Thời gian hết hạn (null = không hết hạn)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0: Disabled, 1: Active',
  `last_used_at` datetime DEFAULT NULL COMMENT 'Lần sử dụng cuối',
  `last_ip` varchar(45) DEFAULT NULL COMMENT 'IP lần sử dụng cuối',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu API Keys';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `api_logs`
--

CREATE TABLE `api_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `api_key_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID của API key',
  `user_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID người dùng',
  `api_key` varchar(64) NOT NULL,
  `endpoint` varchar(100) NOT NULL COMMENT 'Endpoint được gọi',
  `method` varchar(10) NOT NULL DEFAULT 'POST' COMMENT 'HTTP method',
  `ip` varchar(45) NOT NULL COMMENT 'IP address',
  `user_agent` varchar(500) DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Request parameters (đã lọc sensitive data)' CHECK (json_valid(`request_data`)),
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Response data (summary)' CHECK (json_valid(`response_data`)),
  `status` varchar(20) NOT NULL DEFAULT 'success' COMMENT 'success, failed, blocked',
  `message` varchar(255) DEFAULT NULL COMMENT 'Thông báo/lỗi',
  `execution_time` decimal(10,4) DEFAULT NULL COMMENT 'Thời gian xử lý (seconds)',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng log API requests';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `automations`
--

CREATE TABLE `automations` (
  `id` int(11) NOT NULL,
  `name` mediumtext DEFAULT NULL,
  `type` varchar(55) DEFAULT NULL,
  `product_id` longtext DEFAULT NULL,
  `schedule` int(11) NOT NULL DEFAULT 0,
  `other` mediumtext DEFAULT NULL,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banks`
--

CREATE TABLE `banks` (
  `id` int(11) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `image` mediumtext DEFAULT NULL,
  `accountName` mediumtext DEFAULT NULL,
  `accountNumber` mediumtext DEFAULT NULL,
  `password` mediumtext DEFAULT NULL,
  `token` mediumtext DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `is_openapi` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Sử dụng OpenAPI'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `position` varchar(50) NOT NULL DEFAULT 'below_sliders',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banners`
--

INSERT INTO `banners` (`id`, `title`, `image`, `link`, `position`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, '', 'assets/storage/images/banner_KGYQOP.png', '', 'below_sliders', 5, 1, '2025-12-21 19:12:47', '2026-02-05 22:24:28'),
(2, '', 'assets/storage/images/banner_B9AUW3.png', '', 'below_sliders', 4, 1, '2025-12-21 19:30:56', '2026-02-05 22:24:28'),
(3, '', 'assets/storage/images/banner_WORP1J.png', '', 'below_sliders', 3, 1, '2025-12-21 19:31:02', '2026-02-05 22:24:28'),
(4, '', 'assets/storage/images/banner_J3XGI7.png', '', 'below_sliders', 2, 1, '2025-12-21 19:31:33', '2026-02-05 22:24:28'),
(5, '', 'assets/storage/images/banner_I04GB1.png', '', 'below_sliders', 1, 1, '2025-12-21 19:31:40', '2026-02-05 22:24:28'),
(6, '', 'assets/storage/images/banner_JC6M5K.png', '', 'below_sliders', 0, 1, '2025-12-21 19:31:47', '2026-02-05 22:24:28'),
(7, '', 'assets/storage/images/banner_N6S410.png', '', 'sidebar_left', 0, 1, '2025-12-22 23:17:11', '2026-02-05 22:24:28'),
(8, '', 'assets/storage/images/banner_IWGO36.png', '', 'sidebar_right', 0, 1, '2025-12-22 23:17:29', '2026-02-05 22:24:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `block_ip`
--

CREATE TABLE `block_ip` (
  `id` int(11) NOT NULL,
  `ip` mediumtext DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `banned` int(11) NOT NULL DEFAULT 0,
  `reason` mediumtext DEFAULT NULL,
  `create_gettime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT 0,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','published','scheduled') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `blogs`
--

INSERT INTO `blogs` (`id`, `category_id`, `author_id`, `title`, `slug`, `excerpt`, `content`, `thumbnail`, `meta_title`, `meta_description`, `meta_keywords`, `views`, `is_featured`, `sort_order`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 6255, 'Làm Dropship thời điểm 2025 - 2026 còn kiếm cơm được không?', 'lam-dropship-thoi-diem-2025---2026-con-kiem-com-duoc-khong', '', '<p>Hi ae, lặn gần cả năm rồi nay trồi l&ecirc;n chia sẻ cho ae v&agrave;i thứ,<br />\r\n<br />\r\nĐ&acirc;y l&agrave; quan điểm của m&igrave;nh sau khi đổ tiền đổ c&ocirc;ng sức v&agrave;o l&agrave;m, c&oacute; thể những quan điểm n&agrave;y ph&ugrave; hợp hoặc chưa ph&ugrave; hợp, ae nhẹ nh&agrave;ng với nhau đừng gạch đ&aacute; lại rước bực v&agrave;o người.<br />\r\n<br />\r\nNăm nay n&oacute;i chung m&igrave;nh cũng kh&ocirc;ng c&oacute; qu&aacute; nhiều th&agrave;nh tựu, kiếm kh&ocirc;ng được nhiều như năm ngo&aacute;i. Nhưng m&igrave;nh cũng đ&uacute;c kết được nhiều kinh nghiệm để chia sẻ cho ae để sau n&agrave;y nếu ae th&igrave; n&ecirc;n tr&aacute;nh từ đầu.<br />\r\n<br />\r\nM&igrave;nh đ&atilde; b&aacute;n h&agrave;ng tr&ecirc;n nhiều nền tảng: Shopify, Etsy, Dropship qua c&aacute;c plf do ae VN l&agrave;m chủ. Mặt h&agrave;ng m&igrave;nh b&aacute;n rất đa dạng, từ h&agrave;ng gia dụng, đồ chơi cho đến home decor, gardening,... c&aacute;i g&igrave; m&igrave;nh thấy b&aacute;n được l&agrave; m&igrave;nh b&aacute;n. Tất nhi&ecirc;n l&agrave; m&igrave;nh b&aacute;n vừa sạch vừa black&nbsp;<img alt=\":)\" data-shortname=\":)\" data-smilie=\"1\" height=\"64\" loading=\"lazy\" src=\"https://cdn.jsdelivr.net/joypixels/assets/8.0/png/unicode/64/1f642.png\" title=\"Smile    :)\" width=\"64\" />))<br />\r\n<br />\r\n<b>1. Dropship 2025 như thế n&agrave;o?</b><br />\r\n<br />\r\nCũng chẳng biết n&oacute;i g&igrave; nhiều, m&igrave;nh v&agrave; nhiều ae c&ugrave;ng nghề đều thấy r&otilde; l&agrave; năm nay tiền về kh&ocirc;ng được ngon như c&aacute;c năm trước, người mua h&agrave;ng cũng dần thấy được sự kh&ocirc;ng chất lượng, kh&ocirc;ng đ&uacute;ng với m&ocirc; tả n&ecirc;n cũng đ&atilde; giảm mua nhiều so với c&aacute;c năm trước. Tuy nhi&ecirc;n với lợi thế về ng&acirc;n s&aacute;ch quảng c&aacute;o th&igrave; cũng c&oacute; rất nhiều team ăn đậm năm nay. Thời gian vừa qua th&igrave; ph&iacute;a facebook cũng c&oacute; kh&aacute; nhiều tut đổi nh&agrave; đổi xe cho c&aacute;c ae ads thủ.<br />\r\n<br />\r\nCũng c&oacute; một v&agrave;i quan điểm n&oacute;i rằng v&igrave; năm nay kinh tế đi xuống, lạm ph&aacute;t, thất nghiệp,... n&ecirc;n sức mua giảm. Cũng c&oacute; phần đ&uacute;ng, tuy nhi&ecirc;n c&aacute; nh&acirc;n m&igrave;nh nghĩ rằng sản phẩm chưa đủ chất lượng v&agrave; customer service chưa l&agrave;m tốt th&igrave; kh&ocirc;ng thể b&aacute;n được h&agrave;ng, c&oacute; một v&agrave;i sản phẩm m&igrave;nh b&aacute;n thuộc về ng&aacute;ch decor home b&aacute;n gi&aacute; rất cao ($180 - $225) vẫn b&aacute;n rất đều v&igrave; m&igrave;nh đ&aacute;p ứng được chất lượng như m&igrave;nh đ&atilde; m&ocirc; tả, support họ tận răng, v&agrave; ưu đ&atilde;i cho lần mua tiếp theo của họ...<br />\r\n<br />\r\n<b>2. Thế 2026 c&oacute; n&ecirc;n l&agrave;m dropship nữa kh&ocirc;ng?</b><br />\r\n<br />\r\nĐể b&oacute;c t&aacute;ch ra từng yếu tố để cấu th&agrave;nh n&ecirc;n m&ocirc; h&igrave;nh dropship th&igrave; cần c&oacute;:<br />\r\n- Store: B&aacute;n h&agrave;ng th&igrave; chắc chắn phải c&oacute; store rồi, kh&ocirc;ng c&oacute; th&igrave; kh&aacute;ch biết chỗ n&agrave;o m&agrave; mua.<br />\r\n<br />\r\n- Mặt h&agrave;ng: B&aacute;n c&aacute;i g&igrave;, ai l&agrave; người cần sản phẩm đ&oacute;, mua về d&ugrave;ng v&agrave;o việc g&igrave;, sản phẩm đ&oacute; bao l&acirc;u th&igrave; người sử dụng cần thay mới, v&agrave; c&aacute;i quan trọng nữa l&agrave; c&oacute; bao nhi&ecirc;u người cần sản phẩm đ&oacute; tại thời điểm m&agrave; ae b&aacute;n h&agrave;ng? Trả lời được hết mấy c&acirc;u n&agrave;y th&igrave; coi như ae đ&atilde; h&igrave;nh dung được m&igrave;nh sẽ b&aacute;n c&aacute;i g&igrave; rồi.<br />\r\n<br />\r\n- Cổng thanh to&aacute;n: Nếu mua h&agrave;ng ở shop của ae th&igrave; người mua trả cho ae kiểu g&igrave;, v&igrave; b&aacute;n nước ngo&agrave;i th&igrave; l&agrave;m g&igrave; c&oacute; gửi qr bank VN cho họ qu&eacute;t m&atilde; được đ&acirc;u =)))). C&aacute;i n&agrave;y th&igrave; hơi phiền t&iacute; nhưng phải cố gắng l&agrave;m cho hẳn hoi kh&ocirc;ng sau n&agrave;y lại &ocirc;m nỗi buồn đấy.<br />\r\n<br />\r\n- Supplier: Sau khi t&igrave;m được sản phẩm m&agrave; ae muốn b&aacute;n th&igrave; xưởng/nh&agrave; m&aacute;y n&agrave;o sẽ l&agrave;m c&aacute;i sản phẩm đ&oacute; ra cho ae. C&aacute;i n&agrave;y th&igrave; cũng rất nhiều th&ocirc;ng tin, ae cứ g&otilde; l&ecirc;n google l&agrave; n&oacute; ra đủ lu&ocirc;n, m&igrave;nh cũng kh&ocirc;ng giải th&iacute;ch th&ecirc;m nữa.<br />\r\n<br />\r\n- Shipper: C&oacute; th&agrave;nh phẩm rồi b&acirc;y giờ đơn vị n&agrave;o sẽ giao c&aacute;i sản phẩm đ&oacute; đến tay người ti&ecirc;u d&ugrave;ng? N&agrave;y th&igrave; cũng phải l&agrave;m kĩ v&igrave; đ&ocirc;i khi sẽ c&oacute; những l&ocirc; bị hold h&agrave;ng chờ kiểm h&oacute;a, nếu đơn vị vận chuyển kh&ocirc;ng c&oacute; giấy tờ r&otilde; r&agrave;ng hoặc kh&ocirc;ng c&oacute; &quot;người nh&agrave;&quot; th&igrave; khả năng bị tịch thu + ti&ecirc;u hủy l&agrave; rất cao.<br />\r\n<br />\r\n- Ads: C&aacute;i n&agrave;y quyết định phần lớn th&agrave;nh c&ocirc;ng của ae, muốn ra tiền th&igrave; phải c&oacute; người mua, m&agrave; muốn c&oacute; người mua th&igrave; cần phải chạy ads để hiển thị sản phẩm đến với người mua th&igrave; họ mới mua chứ họ kh&ocirc;ng tự đi kiếm tới m&igrave;nh đ&acirc;u.<br />\r\n<br />\r\n<b>Giờ th&igrave; quay lại với c&acirc;u hỏi ban đầu, năm 2026 c&oacute; n&ecirc;n l&agrave;m dropship nữa kh&ocirc;ng?<br />\r\nTheo quan điểm m&igrave;nh th&igrave; l&agrave;m vẫn được, kể cả 2027, 2028,... vẫn l&agrave;m được th&ocirc;i, nhưng ae phải lưu &yacute; điều n&agrave;y. Seller dropship th&igrave; c&agrave;ng ng&agrave;y c&agrave;ng đ&ocirc;ng n&ecirc;n giờ cuộc chơi chỉ d&agrave;nh cho những ae c&oacute; lợi thế.</b><br />\r\n<br />\r\nĐể ph&acirc;n t&iacute;ch cho ae dễ hiểu lợi thế l&agrave; như thế n&agrave;o, ae đọc lại phần yếu tố cấu th&agrave;nh n&ecirc;n m&ocirc; h&igrave;nh dropship v&agrave; đọc tiếp phần m&igrave;nh viết sau đ&acirc;y:<br />\r\n<br />\r\nV&iacute; dụ m&igrave;nh v&agrave; ae c&ugrave;ng l&agrave; seller, c&ugrave;ng b&aacute;n chung một mặt h&agrave;ng, c&ugrave;ng nhắm đến một thị trường th&igrave; m&igrave;nh sẽ c&oacute; lợi thế hơn ở chỗ:<br />\r\n- Store của m&igrave;nh đẹp hơn ae, m&igrave;nh c&oacute; nhiều sản phẩm hơn ae, shop m&igrave;nh load nhanh vl, bấm ph&aacute;t l&agrave; ra, đụng l&agrave; nhảy. Cấu tr&uacute;c website m&igrave;nh l&agrave;m logic hơn ae, gọn hơn ae, kh&aacute;ch v&agrave;o thấy thoải m&aacute;i vl. C&ograve;n shop của ae load chậm, kh&aacute;ch đang cao hứng muốn mua h&agrave;ng rồi v&agrave;o bấm c&aacute;i web m&atilde;i đ load xong sản phẩm, chờ m&atilde;i ch&aacute;n vl out cmnl. vậy th&igrave; m&igrave;nh lời hơn ae 1 kh&aacute;ch h&agrave;ng rồi.<br />\r\n<br />\r\n- Cổng thanh to&aacute;n m&igrave;nh nhiều hơn ae, kh&aacute;ch muốn thanh to&aacute;n bằng bank m&igrave;nh c&oacute; bank, kh&aacute;ch muốn paypal m&igrave;nh c&oacute; paypal, kh&aacute;ch muốn stripe m&igrave;nh c&oacute; stripe,... t&oacute;m lại l&agrave; clg kh&aacute;ch muốn m&igrave;nh cũng c&oacute;, m&igrave;nh chỉ sợ kh&aacute;ch kh&ocirc;ng muốn mua h&agrave;ng th&ocirc;i =)))<br />\r\n<br />\r\n- Supplier + Shipper: V&iacute; dụ như m&igrave;nh v&agrave; ae đều dropship US nhưng c&aacute;i sản phẩm của m&igrave;nh được sản xuất tại US v&agrave; đơn vị tại US ship lu&ocirc;n. Vậy th&igrave; từ thời điểm kh&aacute;ch thanh to&aacute;n đến khi kh&aacute;ch nhận được h&agrave;ng trong khoảng 1-3 ng&agrave;y th&ocirc;i. Trong khi supplier của ae ở m&atilde;i t&agrave;u khựa v&agrave; ship từ t&agrave;u khựa qua us mất &iacute;t nhất 7-10 ng&agrave;y th&igrave; ae nghĩ l&agrave; kh&aacute;ch sẽ mua của m&igrave;nh hay của ae. Đơn giản l&agrave; thế thui.<br />\r\n<br />\r\n- Ads: C&aacute;i n&agrave;y th&igrave; dễ rồi, giờ m&igrave;nh chạy 10 đồng quảng c&aacute;o th&igrave; m&igrave;nh chỉ phải trả 2 đồng th&ocirc;i, trong khi ae chạy 10 đồng th&igrave; phải trả đủ 10 đồng.<br />\r\n<br />\r\nĐ&oacute;, lợi thế l&agrave; như vậy, ae tự ngẫm nh&eacute;. Ae tối ưu c&agrave;ng nhiều th&igrave; c&agrave;ng lợi thế hơn c&aacute;c ae kh&aacute;c v&agrave; cũng sẽ sống tốt d&ugrave; cho thị trường c&oacute; down cỡ n&agrave;o.<br />\r\n<br />\r\n<b>Đọc thấy mệt vl, hết ước mơ l&agrave;m dropship rồi, c&oacute; nhiều thứ cần phải triển khai qu&aacute;. B&acirc;y giờ c&oacute; đường n&agrave;o l&agrave;m &iacute;t m&agrave; vẫn c&oacute; ăn hoặc ăn nhiều kh&ocirc;ng?<br />\r\nC&acirc;u trả lời l&agrave; c&oacute;, ae xem th&ecirc;m tại đ&acirc;y:&nbsp;</b><a href=\"https://www.youtube.com/shorts/nPKBZtmxaL4\" rel=\"nofollow ugc noopener\" target=\"_blank\"><b>C&aacute;ch l&agrave;m dropship kh&ocirc;ng cần tốn c&ocirc;ng m&agrave; vẫn c&oacute; lợi nhuận</b></a><br />\r\n<br />\r\n<b>Ae n&agrave;o xem xong th&igrave; quay lại đ&acirc;y m&igrave;nh c&oacute; đ&ocirc;i lời nhắn nhủ:</b><br />\r\n1. Kiếm tiền kh&ocirc;ng dễ, kh&ocirc;ng c&oacute; m&ocirc; h&igrave;nh n&agrave;o m&agrave; đụng c&aacute;i ra cả đống tiền được, tất cả phải nỗ lực, cố gắng để c&oacute;. Đừng để c&aacute;c thầy m&otilde;m fomo m&ocirc; h&igrave;nh si&ecirc;u lợi nhuận, 0 đồng. Học ra nghề kiếm tiền được ngay.<br />\r\n<br />\r\nAe chắc cũng nghe c&oacute; nhiều người kiếm v&agrave;i triệu đ&ocirc; 1 năm nhờ dropship n&agrave;y nọ, c&aacute; nh&acirc;n m&igrave;nh đ&atilde; chứng kiến l&agrave; c&oacute; thật, kh&ocirc;ng bốc ph&eacute;t đ&acirc;u, nhưng đằng sau người ta cũng rất khổ mới tới được đ&acirc;y, c&oacute; người trước khi l&agrave;m được họ cũng đ&atilde; đổ nợ v&igrave; c&aacute;i dropship n&agrave;y bao nhi&ecirc;u lần rồi, thức trắng đ&ecirc;m bao nhi&ecirc;u lần rồi, chủ nợ tới dọa nạt bao lần rồi, mới đ&uacute;c kết ra kinh nghiệm v&agrave; th&agrave;nh c&ocirc;ng được.<br />\r\n<br />\r\n2. N&ecirc;n c&oacute; vốn trước khi l&agrave;m, phần lớn mấy ae nhắn tin hỏi m&igrave;nh đều được c&aacute;c thầy thấm nhuần tư tưởng dropship 0 đồng. Kh&ocirc;ng c&oacute; đ&acirc;u nh&eacute;, ae n&ecirc;n t&iacute;ch vốn m&agrave; l&agrave;m. C&aacute;i thứ nhất l&agrave; vốn d&ugrave;ng để chi trả chi ph&iacute; x&acirc;y dựng store, chi ph&iacute; tạo cổng thanh to&aacute;n, chi ph&iacute; quảng c&aacute;o,... C&aacute;i thứ 2 l&agrave; khi ae tự bỏ tiền vốn ra th&igrave; ae sẽ nghi&ecirc;m t&uacute;c v&agrave; c&oacute; tr&aacute;ch nhiệm với đồng tiền của m&igrave;nh hơn.<br />\r\n<br />\r\n3. Đi từng bước một. C&oacute; thể ae l&agrave;m ra tiền th&igrave; c&oacute; v&agrave;i trăm đ&ocirc; 1 th&aacute;ng, sau đ&oacute; v&agrave;i ngh&igrave;n đ&ocirc; 1 th&aacute;ng, rồi v&agrave;i chục ngh&igrave;n đ&ocirc; 1 th&aacute;ng, nữa th&igrave; v&agrave;i trăm ngh&igrave;n đ&ocirc; một th&aacute;ng. Chứ kh&oacute; m&agrave; đi 1 ph&aacute;t từ 0 l&ecirc;n 10000$ trong thời gian ngắn được.<br />\r\n<br />\r\n4. Cố gắng t&igrave;m cho m&igrave;nh 1 c&aacute;i lợi thế, rồi kết giao để t&igrave;m kiếm th&ecirc;m.<br />\r\n<br />\r\n5. B&agrave;i n&agrave;y m&igrave;nh viết bằng những cảm nhận v&agrave; đ&uacute;c kết của m&igrave;nh qua qu&aacute; tr&igrave;nh nằm gai nếm mật với c&aacute;i m&ocirc; h&igrave;nh n&agrave;y. ae n&agrave;o bị bệnh ti&ecirc;u cực th&igrave; đọc xong sẽ c&agrave;ng ti&ecirc;u cực, ae n&agrave;o t&iacute;ch cực th&igrave; sẽ c&agrave;ng c&oacute; động lực. N&ecirc;n ae th&ocirc;ng cảm nếu như m&igrave;nh l&agrave;m ae thấy nhụt ch&iacute;.<br />\r\n<br />\r\n6. C&aacute;i n&agrave;y th&igrave; d&agrave;nh cho những ae n&agrave;o đ&atilde; kiếm được tiền từ dropship rồi: Cố gắng sở hữu cho m&igrave;nh 1 sản phẩm m&agrave; m&igrave;nh c&oacute; thể chủ động sản xuất v&agrave; kiểm so&aacute;t được chất lượng.<br />\r\n<br />\r\n<br />\r\n<b>Chốt lại: Tất cả những thứ m&igrave;nh n&oacute;i ở tr&ecirc;n đều v&ocirc; nghĩa nếu như ae kh&ocirc;ng chịu bắt tay v&agrave;o l&agrave;m việc, chỉ ngồi t&iacute;nh to&aacute;n bằng giấy b&uacute;t.</b><br />\r\n<br />\r\nTh&ocirc;i nay viết đến đ&acirc;y th&ocirc;i, sau c&oacute; thời gian m&igrave;nh sẽ share về c&aacute;c loại h&igrave;nh, ưu nhược điểm của c&aacute;c nền tảng cho ae tham khảo th&ecirc;m. Đợt sale cho chrismast đang s&aacute;t lưng rồi, m&igrave;nh đi kiếm b&aacute;nh chưng ăn tết đ&acirc;y. Ch&uacute;c ae trong forum gặp nhiều may mắn, c&aacute;t t&agrave;i c&aacute;t lộc, ăn n&ecirc;n l&agrave;m ra. &lt;3</p>', 'assets/storage/images/blog_HUM8YZ.webp', 'Làm Dropship thời điểm 2025 - 2026 còn kiếm cơm được không?', '', '', 9, 0, 0, 'published', '2025-12-20 22:36:57', '2025-12-20 22:36:57', '2025-12-20 22:42:32'),
(2, 2, 6255, 'Câu chuyện Talmud 2.000 năm tuổi và câu hỏi lớn về AI và quyền lựa chọn của loài người', 'cau-chuyen-talmud-2000-nam-tuoi-va-cau-hoi-lon-ve-ai-va-quyen-lua-chon-cua-loai-nguoi', '', '<p>Gần 2.000 năm trước khi con người biết đến kh&aacute;i niệm tr&iacute; tuệ nh&acirc;n tạo, đ&atilde; c&oacute; một cuộc tranh luận mang t&iacute;nh nền tảng về quyền quyết định, đạo đức v&agrave; &yacute; nghĩa của việc l&agrave;m người. Điều bất ngờ l&agrave; cuộc tranh luận ấy, được ghi lại trong&nbsp;<a href=\"https://tinhte.vn/tag/talmud\">Talmud</a>, văn bản trung t&acirc;m của luật&nbsp;<a href=\"https://tinhte.vn/tag/do-thai\">Do Th&aacute;i</a>, lại phản chiếu một c&aacute;ch kh&aacute; ch&iacute;nh x&aacute;c những g&igrave; lo&agrave;i người đang vật lộn h&ocirc;m nay, khi c&aacute;c c&ocirc;ng ty c&ocirc;ng nghệ n&oacute;i về việc x&acirc;y dựng &ldquo;si&ecirc;u tr&iacute; tuệ&rdquo; c&oacute; khả năng quyết định hay gi&uacute;p con người giải quyết mọi vấn đề.<br />\r\n<br />\r\nThoạt nh&igrave;n, c&acirc;u hỏi lớn của AI dường như thi&ecirc;n về mặt kỹ thuật: l&agrave;m sao để AI th&ocirc;ng minh hơn, mạnh hơn, an to&agrave;n hơn, v&agrave; đồng nhất với gi&aacute; trị con người. Nhưng khi đi s&acirc;u hơn, ch&uacute;ng ta nhận ra cốt l&otilde;i của vấn đề kh&ocirc;ng nằm ở thuật to&aacute;n hay dữ liệu, m&agrave; nằm ở một c&acirc;u hỏi triết học rất cũ: nếu c&oacute; một thực thể biết r&otilde; điều g&igrave; l&agrave; &ldquo;đ&uacute;ng&rdquo;, &ldquo;tốt&rdquo;, &ldquo;n&ecirc;n l&agrave;m&rdquo; hơn ch&uacute;ng ta, th&igrave; liệu n&oacute; c&oacute; n&ecirc;n thay ch&uacute;ng ta quyết định hay kh&ocirc;ng?<br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid0\"><b>C&acirc;u chuyện ẩn dụ từ Talmud</b></h2>\r\n\r\n<p><br />\r\nTheo Talmud, Rabbi Eliezer v&agrave; Rabbi Yoshua l&agrave; những học giả t&ocirc;n gi&aacute;o tại Do Th&aacute;i. Trong khi Eliezer nổi tiếng về sự uy&ecirc;n b&aacute;c v&agrave; bảo thủ v&agrave; trung th&agrave;nh với truyền thống th&igrave; Yoshua lại t&ocirc;n trọng nguy&ecirc;n tắc cộng đồng. Hai &ocirc;ng từng tranh luận gay gắt về một vấn đề luật nghi lễ t&ocirc;n gi&aacute;o: một kiểu l&ograve; đất, gọi l&agrave; &ldquo;l&ograve; của Akhnai&rdquo;, c&oacute; t&iacute;nh nghi thức l&agrave; sạch hay kh&ocirc;ng sạch, tức tinh khiết hay &ocirc; uế theo luật Do Th&aacute;i.<br />\r\n<br />\r\n<img alt=\"[​IMG]\" data-url=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923981_talmud.jpeg\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923981_talmud.jpeg\" /><br />\r\n<i>Talmud, văn bản trung t&acirc;m của luật Do Th&aacute;i</i></p>\r\n\r\n<p><br />\r\n<br />\r\nRabbi Eliezer tin chắc m&igrave;nh đ&uacute;ng, v&agrave; để chứng minh điều đ&oacute;, &ocirc;ng li&ecirc;n tục viện đến những ph&eacute;p m&agrave;u: c&acirc;y bật gốc m&agrave; chạy, d&ograve;ng suối chảy ngược, tường nh&agrave; học viện nghi&ecirc;ng sụp. Khi tất cả vẫn kh&ocirc;ng thuyết phục được c&aacute;c học giả kh&aacute;c, &ocirc;ng đi đến nước cờ cuối c&ugrave;ng: k&ecirc;u gọi một tiếng n&oacute;i từ trời cao x&aacute;c nhận m&igrave;nh đ&uacute;ng.<br />\r\n<br />\r\nV&agrave; điều kỳ lạ l&agrave; tiếng n&oacute;i từ trời thực sự vang xuống, tuy&ecirc;n bố Rabbi Eliezer đ&uacute;ng. Nhưng thay v&igrave; c&uacute;i đầu chấp nhận, Rabbi Yoshua đứng l&ecirc;n v&agrave; n&oacute;i một c&acirc;u trở th&agrave;nh kinh điển: &ldquo;Torah kh&ocirc;ng ở tr&ecirc;n trời.&rdquo; &Yacute; của &ocirc;ng rất r&otilde;: luật lệ, đạo đức, c&aacute;ch sống kh&ocirc;ng phải thứ được quyết định bởi một quyền lực si&ecirc;u việt n&agrave;o đ&oacute;, d&ugrave; quyền lực ấy đ&uacute;ng đến đ&acirc;u. Ch&uacute;ng phải được quyết định bởi con người, th&ocirc;ng qua tranh luận, đồng thuận v&agrave; tr&aacute;ch nhiệm chung.<br />\r\n<br />\r\n<img alt=\"debate.jpeg\" data-height=\"726\" data-width=\"1001\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923983_debate.jpeg\" /><br />\r\n<i>Từ xưa người ta đ&atilde; tranh luận về quyền lựa chọn, tự quyết định của lo&agrave;i người</i><br />\r\n<br />\r\nCuối c&ugrave;ng, đa số c&aacute;c học giả b&aacute;c bỏ Rabbi Eliezer. Thậm ch&iacute; trong một đoạn kết rất đẹp, khi người ta hỏi Thượng đế phản ứng ra sao trước việc con người kh&ocirc;ng nghe lời Ng&agrave;i, c&acirc;u trả lời l&agrave;: Ng&agrave;i mỉm cười v&agrave; n&oacute;i, &ldquo;Con ta đ&atilde; thắng ta rồi&quot; Th&ocirc;ng điệp ẩn dụ sau c&acirc;u chuyện ấy l&agrave; một th&ocirc;ng điệp mạnh mẽ: việc giữ lại quyền quyết định cho con người kh&ocirc;ng phải l&agrave; sai lầm m&agrave; l&agrave; điều cốt yếu để con người ch&iacute;nh l&agrave; con người.<br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid1\"><b>Thế giới hiện tại với tr&iacute; tuệ nh&acirc;n tạo</b></h2>\r\n\r\n<p><br />\r\nTua nhanh đến 2000 năm sau, lo&agrave;i người giờ đ&acirc;y kh&ocirc;ng c&ograve;n rảnh để tranh luận về tiếng n&oacute;i từ trời, m&agrave; họ n&oacute;i về &ldquo;AI god&rdquo; một si&ecirc;u tr&iacute; tuệ vượt xa con người, c&oacute; thể giải quyết mọi vấn đề, từ vật l&yacute;, kinh tế cho đến ch&iacute;nh trị v&agrave; chiến tranh.&nbsp;<a href=\"https://tinhte.vn/tag/sam-altman-2\">Sam Altman</a>&nbsp;c&oacute; từng chia sẻ với những &yacute; tưởng như &ldquo;magic intelligence in the sky&rdquo; trong ngữ cảnh ph&acirc;n phối tr&iacute; tuệ qua cloud, hay &ldquo;nearly limitless intelligence&rdquo; m&agrave; lo&agrave;i người c&oacute; được để giải quyết c&aacute;c vấn đề họ đang gặp phải với một nguồn tr&iacute; tuệ v&ocirc; hạn. Những điều m&agrave; Altman hay c&aacute;c c&ocirc;ng ty c&ocirc;ng nghệ hướng tới kh&ocirc;ng chỉ về một chatbot th&ocirc;ng minh hơn, m&agrave; về một thực thể c&oacute; khả năng hỗ trợ v&agrave; thậm ch&iacute; đưa ra những quyết định quan trọng thay cho lo&agrave;i người.<br />\r\n<br />\r\n<img alt=\"agi.jpg\" data-height=\"1024\" data-width=\"1536\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923986_agi.jpg\" /><br />\r\n<i>V&agrave;&nbsp;<a href=\"https://tinhte.vn/tag/agi\">AGI</a>&nbsp;l&agrave; một trong những mục ti&ecirc;u lớn m&agrave; c&aacute;c c&ocirc;ng ty c&ocirc;ng nghệ hướng tới để gi&uacute;p con người giải quyết vấn đề, hay n&oacute;i c&aacute;ch kh&aacute;c l&agrave; c&oacute; thể đưa ra quyết định gi&uacute;p con người</i></p>\r\n\r\n<p>Quảng c&aacute;o</p>\r\n\r\n<p><iframe align=\"top\" frameborder=\"0\" height=\"90\" hspace=\"0\" id=\"adnzone_515899_0_450340\" marginheight=\"0\" name=\"adnzone_515899_0_450340\" scrolling=\"No\" src=\"javascript:if(typeof(adnzone515899)!=\'undefined\'){adnzone515899.renderIframe();}else{parent.adnzone515899.renderIframe();}\" vspace=\"0\" width=\"728\"></iframe></p>\r\n\r\n<p><br />\r\nTừ đ&acirc;y, vấn đề &ldquo;đồng quan điểm&rdquo; trở th&agrave;nh vấn đề cốt l&otilde;i: l&agrave;m sao để AI lu&ocirc;n l&agrave;m điều con người muốn? Nhưng c&acirc;u hỏi n&agrave;y che khuất một vấn đề s&acirc;u hơn: ngay cả khi ta&nbsp;<i>c&oacute; thể</i>&nbsp;tạo ra một AI ho&agrave;n to&agrave;n &ldquo;tốt&rdquo;, &ldquo;đạo đức&rdquo;, &ldquo;vị tha&rdquo;, th&igrave; việc để n&oacute; quyết định thay ch&uacute;ng ta c&oacute; phải l&agrave; một &yacute; tưởng tốt kh&ocirc;ng?<br />\r\n<br />\r\nGiống như tiếng n&oacute;i từ trời trong Talmud, một AI si&ecirc;u th&ocirc;ng minh c&oacute; thể lu&ocirc;n &ldquo;đ&uacute;ng&rdquo; về mặt logic, dự đo&aacute;n, v&agrave; thậm ch&iacute; đạo đức. Nhưng nếu mọi quyết định quan trọng đều được giao cho n&oacute;, th&igrave; vai tr&ograve; của con người c&ograve;n lại l&agrave; g&igrave;?<br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid2\"><b>Những triết l&yacute; đối lập nhau</b></h2>\r\n\r\n<p>Ở đ&acirc;y, c&aacute;c nh&agrave; tư tưởng AI hiện đại chia th&agrave;nh những hướng rất kh&aacute;c nhau. Eliezer Yudkowsky, người thường được xem l&agrave; &ldquo;AI doomer&rdquo;, lại tin rằng việc căn chỉnh một si&ecirc;u tr&iacute; tuệ l&agrave;&nbsp;<i><b>c&oacute; thể&nbsp;</b></i>về mặt nguy&ecirc;n tắc. Với &ocirc;ng, đ&oacute; l&agrave; một b&agrave;i to&aacute;n kỹ thuật cực kh&oacute;, nhưng cuối c&ugrave;ng vẫn l&agrave; kỹ thuật. Nếu giải được, &ocirc;ng sẵn s&agrave;ng để si&ecirc;u tr&iacute; tuệ đ&oacute; vận h&agrave;nh x&atilde; hội, thậm ch&iacute; đưa ra những quyết định sinh tử, dựa tr&ecirc;n c&aacute;i gọi l&agrave; &ldquo;coherent extrapolated volition&rdquo;, tức l&agrave; &yacute; ch&iacute; tổng hợp của nh&acirc;n loại nếu tất cả ch&uacute;ng ta đều hiểu biết hơn, nhất qu&aacute;n hơn.<br />\r\n<br />\r\n<br />\r\nỞ ph&iacute;a đối diện, nhiều triết gia v&agrave; nh&agrave; nghi&ecirc;n cứu cảnh b&aacute;o rằng ch&iacute;nh điều đ&oacute; mới l&agrave; nguy hiểm. Ruth Chang chỉ ra rằng rất nhiều lựa chọn đạo đức quan trọng l&agrave; những quyết định kh&oacute; khăn: kh&ocirc;ng c&oacute; đ&aacute;p &aacute;n đ&uacute;ng nhất. Việc chọn l&agrave;m mẹ hay đi tu, chọn tự do hay an to&agrave;n, chọn hy sinh hay thỏa hiệp, đ&oacute; l&agrave; những lựa chọn kh&ocirc;ng thể đo bằng c&ugrave;ng một thước đo. Gi&aacute; trị của ch&uacute;ng kh&ocirc;ng nằm ở kết quả &ldquo;đ&uacute;ng&rdquo;, m&agrave; nằm ở việc con người đặt bản th&acirc;n m&igrave;nh v&agrave;o lựa chọn đ&oacute;.&#39;<br />\r\n<br />\r\n<img alt=\"ai-god.jpeg\" data-height=\"2000\" data-width=\"3000\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923984_ai-god.jpeg\" /><br />\r\n<i>V&agrave; AGI, hay si&ecirc;u tr&iacute; tuệ l&agrave; một vấn đề g&acirc;y tranh c&atilde;i về mặt triết học khi n&oacute; c&oacute; thể tước đi quyền cơ bản nhất của con người: tự trải nghiệm tự quyết</i></p>\r\n\r\n<p><br />\r\nJoe Edelman từ Meaning Alignment Institute đồng &yacute; rằng một AI tốt n&ecirc;n biết n&oacute;i &ldquo;t&ocirc;i kh&ocirc;ng biết&rdquo;. Nhưng &ocirc;ng cũng thừa nhận: nếu AI im lặng trong mọi quyết định quan trọng, th&igrave; n&oacute; c&ograve;n gi&uacute;p được g&igrave;? V&agrave; nếu n&oacute; kh&ocirc;ng im lặng, th&igrave; n&oacute; đang tước đi điều g&igrave;?<br />\r\n<br />\r\nYoshua Bengio, một trong những nh&agrave; khoa học AI c&oacute; ảnh hưởng nhất thế giới, đứng rất gần với Rabbi Yoshua xưa kia. &Ocirc;ng nhấn mạnh rằng gi&aacute; trị con người kh&ocirc;ng chỉ đến từ l&yacute; tr&iacute;, m&agrave; từ cảm x&uacute;c, sự đồng cảm v&agrave; trải nghiệm sống. D&ugrave; c&oacute; một tr&iacute; tuệ &ldquo;giống thần&rdquo; đi nữa, th&igrave; n&oacute; cũng kh&ocirc;ng thể v&agrave; kh&ocirc;ng n&ecirc;n quyết định thay ch&uacute;ng ta điều g&igrave; l&agrave; đ&aacute;ng sống.<br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid3\"><b>Rủi ro lớn hơn khi AI c&oacute; khả năng quyết định thay con người</b></h2>\r\n\r\n<p><br />\r\nNgay cả khi bỏ qua nguy cơ AI bị lệch chuẩn v&agrave; g&acirc;y thảm họa, vẫn c&ograve;n một rủi ro kh&aacute;c &iacute;t được n&oacute;i tới hơn: rủi ro về sự tồn tại. Nếu mọi quyết định quan trọng đều được tối ưu h&oacute;a, nếu mọi m&acirc;u thuẫn gi&aacute; trị đều được &ldquo;giải quyết&rdquo; bởi một tr&iacute; tuệ vượt trội, th&igrave; khả năng ph&aacute;n đo&aacute;n, cảm nhận v&agrave; lựa chọn của con người sẽ dần teo đi.<br />\r\n<br />\r\nJohn Hick gọi đ&oacute; l&agrave; &ldquo;epistemic distance&rdquo;, hiểu n&ocirc;m na l&agrave; khoảng c&aacute;ch cần thiết để con người ph&aacute;t triển đạo đức. Nếu Thượng đế lu&ocirc;n can thiệp, con người sẽ kh&ocirc;ng bao giờ vấp ng&atilde; để trưởng th&agrave;nh. Một AI lu&ocirc;n biết trước c&acirc;u trả lời cũng c&oacute; thể khiến con người đ&aacute;nh mất ch&iacute;nh m&igrave;nh.<br />\r\n<br />\r\nC&acirc;u chuyện trong Talmud kh&ocirc;ng dạy rằng con người lu&ocirc;n đ&uacute;ng, m&agrave; dạy rằng con người cần được quyền sai. Việc tranh luận, bất đồng, lựa chọn v&agrave; chịu tr&aacute;ch nhiệm ch&iacute;nh l&agrave; c&aacute;ch lo&agrave;i người tạo ra &yacute; nghĩa cho cuộc sống.<br />\r\n<br />\r\nAI c&oacute; thể l&agrave; c&ocirc;ng cụ mạnh mẽ, thậm ch&iacute; l&agrave; người cố vấn xuất sắc. Nhưng khoảnh khắc ch&uacute;ng ta để n&oacute; trở th&agrave;nh &ldquo;tiếng n&oacute;i từ trời&rdquo;, khoảnh khắc ch&uacute;ng ta ngừng lựa chọn v&agrave; chỉ c&ograve;n l&agrave;m theo, th&igrave; d&ugrave; AI c&oacute; tốt đến đ&acirc;u, ch&uacute;ng ta cũng đ&atilde; đ&aacute;nh mất điều khiến m&igrave;nh l&agrave; con người: quyền tự quyết.</p>', 'assets/storage/images/blog_9Q1MBX.webp', 'Câu chuyện Talmud 2.000 năm tuổi và câu hỏi lớn về AI và quyền lựa chọn của loài người', '', '', 16, 0, 0, 'published', '2025-12-20 22:38:14', '2025-12-20 22:38:14', '2025-12-20 22:38:27'),
(3, 1, 6255, 'Đừng lãng phí máy tính bảng cũ, hãy biến nó thành màn hình phụ cho PC ngay!', 'dung-lang-phi-may-tinh-bang-cu-hay-bien-no-thanh-man-hinh-phu-cho-pc-ngay', '', '<p>Kh&ocirc;ng gian hiển thị hạn hẹp lu&ocirc;n l&agrave; r&agrave;o cản khiến việc đa nhiệm tr&ecirc;n m&aacute;y t&iacute;nh b&agrave;n PC trở n&ecirc;n kh&oacute; khăn. Thay v&igrave; phải chi h&agrave;ng triệu đồng cho một chiếc&nbsp;<a href=\"https://tinhte.vn/tag/man-hinh-di-dong\">m&agrave;n h&igrave;nh di động</a>&nbsp;chuy&ecirc;n dụng, tại sao kh&ocirc;ng tận dụng ngay chiếc&nbsp;<a href=\"https://tinhte.vn/tag/may-tinh-bang\">m&aacute;y t&iacute;nh bảng</a>&nbsp;sẵn c&oacute;? B&agrave;i viết n&agrave;y sẽ hướng dẫn bạn c&aacute;ch d&ugrave;ng&nbsp;<a href=\"https://tinhte.vn/tag/spacedesk-2\">Spacedesk</a>&nbsp;để biến Tablet th&agrave;nh m&agrave;n h&igrave;nh thứ hai mượt m&agrave; như m&agrave;n h&igrave;nh di động.<br />\r\n<br />\r\nVới phần mềm Spacedesk từ nh&agrave; ph&aacute;t triển Datronicsoft, bạn c&oacute; thể biến n&oacute; th&agrave;nh một chiếc m&agrave;n h&igrave;nh phụ ho&agrave;n hảo cho bộ PC của m&igrave;nh m&agrave; kh&ocirc;ng tốn một đồng chi ph&iacute; n&agrave;o.<br />\r\n<br />\r\nKh&aacute;c với Laptop, m&aacute;y t&iacute;nh để b&agrave;n PC thường rất &iacute;t m&aacute;y kết nối mạng qua Wifi. Spacedesk ch&iacute;nh l&agrave; ứng dụng tuyệt vời gi&uacute;p bạn mở rộng kh&ocirc;ng gian hiển thị một c&aacute;ch chuy&ecirc;n nghiệp.<br />\r\n<br />\r\n<img alt=\"spacedesk (3).jpg\" data-height=\"839\" data-width=\"1636\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923892_spacedesk_3.jpg\" /></p>\r\n\r\n<h2 id=\"menuid0\"><b>Hướng dẫn c&agrave;i đặt v&agrave; sử dụng Spacedesk</b></h2>\r\n\r\n<p><b>Bước 1:</b></p>\r\n\r\n<ul>\r\n	<li>Tr&ecirc;n PC: Bạn truy cập trang chủ Spacedesk để tải bản Driver d&agrave;nh cho&nbsp;<a href=\"https://tinhte.vn/tag/windows-8\">Windows</a>. Sau khi c&agrave;i, ứng dụng sẽ chạy ẩn dưới thanh Taskbar.</li>\r\n	<li>Tr&ecirc;n m&aacute;y t&iacute;nh bảng: L&ecirc;n CH Play (<i><a href=\"https://tinhte.vn/tag/android\">Android</a></i>) t&igrave;m v&agrave; tải ứng dụng&nbsp;<b>Spacedesk</b>&nbsp;(<i>Datronicsoft</i>).</li>\r\n</ul>\r\n\r\n<p><br />\r\n<b>Bước 2:&nbsp;</b>Đảm bảo m&aacute;y t&iacute;nh để b&agrave;n (<i>PC</i>) v&agrave; m&aacute;y t&iacute;nh bảng của bạn đang kết nối chung một bộ ph&aacute;t Wi-Fi hoặc chung một mạng LAN trong nh&agrave;.<br />\r\n<br />\r\n<b>Bước 3:&nbsp;</b>Mở ứng dụng Spacedesk tr&ecirc;n m&aacute;y t&iacute;nh bảng. Ứng dụng sẽ tự động qu&eacute;t v&agrave; hiển thị địa chỉ IP của PC đang chạy driver Spacedesk. Bạn chỉ cần nhấn v&agrave;o d&ograve;ng &quot;Connection: IP...&quot; đ&oacute;. Ngay lập tức, m&agrave;n h&igrave;nh PC sẽ được truyền sang m&aacute;y t&iacute;nh bảng.<br />\r\n<br />\r\n<b>Bước 4:</b>&nbsp;T&ugrave;y chỉnh thiết lập chất lượng h&igrave;nh ảnh ph&ugrave; hợp tr&ecirc;n m&aacute;y t&iacute;nh bảng, bạn c&oacute; thể điều chỉnh mức độ r&otilde; n&eacute;t hay Slace m&agrave;n h&igrave;nh t&ugrave;y với nhu cầu của bạn.<br />\r\n<br />\r\n<img alt=\"spacedesk (5).jpg\" data-height=\"1366\" data-width=\"2048\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923897_spacedesk_5.jpg\" /><br />\r\n<i>Ứng dụng Spacedesk tr&ecirc;n m&aacute;y t&iacute;nh bảng Lenovo</i><br />\r\n<br />\r\n<img alt=\"spacedesk-pc (1).jpg\" data-height=\"1366\" data-width=\"2048\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923901_spacedesk-pc_1.jpg\" /><br />\r\n<i>Bảng th&ocirc;ng b&aacute;o 2 m&aacute;y chuẩn bị li&ecirc;n kết với nhau tr&ecirc;n m&aacute;y t&iacute;nh bảng</i><br />\r\n<br />\r\n<img alt=\"spacedesk (4).jpg\" data-height=\"1366\" data-width=\"2048\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923898_spacedesk_4.jpg\" /><br />\r\n<i>T&ugrave;y chỉnh chất lượng h&igrave;nh ảnh tr&ecirc;n m&aacute;y t&iacute;nh bảng</i><br />\r\n<br />\r\n<img alt=\"spacedesk (6).jpg\" data-height=\"1366\" data-width=\"2048\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923902_spacedesk_6.jpg\" /></p>\r\n\r\n<p>Quảng c&aacute;o</p>\r\n\r\n<p><iframe align=\"top\" frameborder=\"0\" height=\"90\" hspace=\"0\" id=\"adnzone_515899_0_450340\" marginheight=\"0\" name=\"adnzone_515899_0_450340\" scrolling=\"No\" src=\"javascript:if(typeof(adnzone515899)!=\'undefined\'){adnzone515899.renderIframe();}else{parent.adnzone515899.renderIframe();}\" vspace=\"0\" width=\"728\"></iframe></p>\r\n\r\n<p><br />\r\n<i>Thử nghiệm k&eacute;o thả tr&ecirc;n m&aacute;y t&iacute;nh để b&agrave;n PC qua m&aacute;y t&iacute;nh bảng Lenovo</i><br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid1\"><b>V&igrave; sao n&ecirc;n d&ugrave;ng m&aacute;y t&iacute;nh bảng như 1 m&agrave;n h&igrave;nh di động</b></h2>\r\n\r\n<p>Một chiếc tablet 10 đến12 inch đặt dưới gầm m&agrave;n h&igrave;nh ch&iacute;nh hoặc b&ecirc;n cạnh l&agrave; giải ph&aacute;p đa m&agrave;n h&igrave;nh cực kỳ gọn g&agrave;ng m&agrave; c&ograve;n tối ưu v&agrave; tiết kiệm kh&ocirc;ng gian b&agrave;n l&agrave;m việc.<br />\r\nVới ứng dụng kết nối tiện lợi cả Wi-Fi lẫn d&acirc;y mạng LAN (chung lớp mạng). Đặc biệt, với PC d&ugrave;ng mạng d&acirc;y, kết nối qua Spacedesk cực kỳ ổn định so với Wifi của Laptop.<br />\r\n&nbsp;</p>\r\n\r\n<ul>\r\n	<li>Độ trễ (Delay) : M&igrave;nh đ&aacute;nh gi&aacute; độ trễ của t&aacute;c vụ tạm ổn, phụ thuộc 1 phần v&agrave;o nh&agrave; mạng v&agrave; Wifi tr&ecirc;n m&aacute;y t&iacute;nh bảng, nhưng vẫn đủ d&ugrave;ng cho c&aacute;c t&aacute;c vụ văn ph&ograve;ng, theo d&otilde;i chứng kho&aacute;n hay đọc t&agrave;i liệu.</li>\r\n	<li>Độ n&eacute;t : Về độ r&otilde; n&eacute;t v&agrave; m&agrave;u sắc, m&igrave;nh đ&aacute;nh gi&aacute; cũng tạm ổn v&igrave; c&ograve;n phụ thuộc v&agrave;o chiếc m&aacute;y t&iacute;nh bảng của bạn c&oacute; tấm m&agrave;n xịn cỡ n&agrave;o. Ngo&agrave;i ra bạn ho&agrave;n to&agrave;n c&oacute; thể điều chỉnh th&ocirc;ng qua ứng dụng tr&ecirc;n m&aacute;y t&iacute;nh bảng</li>\r\n	<li>T&iacute;nh thực dụng : R&otilde; r&agrave;ng việc c&oacute; th&ecirc;m 1 chiếc m&agrave;n h&igrave;nh mở rộng kh&ocirc;ng gian ngay b&ecirc;n cạnh cũng đủ gi&uacute;p m&igrave;nh mở th&ecirc;m 1 Task việc v&agrave; l&agrave;m việc hiệu quả hơn 1 chiếc m&agrave;n h&igrave;nh.</li>\r\n</ul>\r\n\r\n<p><br />\r\n<img alt=\"spacedesk-pc (2).jpg\" data-height=\"1366\" data-width=\"2048\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923900_spacedesk-pc_2.jpg\" /><br />\r\n<i>Hiển thị song song tr&ecirc;n m&aacute;y t&iacute;nh để b&agrave;n PC</i><br />\r\n<br />\r\nƯu điểm so với Second Screen của Samsung th&igrave; Spacedesk dễ chịu hơn khi cho ph&eacute;p hầu như mọi loại m&aacute;y t&iacute;nh bảng (Lenovo, Xiaomi, Oppo&hellip;v.v&hellip;) đều c&oacute; thể kết nối với PC hay Laptop của Windows, kh&ocirc;ng nhất thiết phải c&ugrave;ng hệ sinh th&aacute;i như của Samsung hay y&ecirc;u cầu bắt buộc 2 m&aacute;y li&ecirc;n kết bắt buộc phải c&oacute; Wifi.<br />\r\n<img alt=\"samsung-galaxy-tab-s (5).jpg\" data-height=\"1366\" data-width=\"2048\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/12/8923899_samsung-galaxy-tab-s_5.jpg\" /><br />\r\n<i>Second Screen tr&ecirc;n m&aacute;y t&iacute;nh bảng Samsung phải c&oacute; kết nối qua Wifi với Laptop</i></p>\r\n\r\n<p><br />\r\n<br />\r\nViệc t&aacute;i sử dụng m&aacute;y t&iacute;nh bảng l&agrave;m m&agrave;n h&igrave;nh phụ l&agrave; c&aacute;ch để tối ưu h&oacute;a t&agrave;i nguy&ecirc;n sẵn c&oacute;, đồng thời l&agrave; cũng l&agrave; 1 c&aacute;ch để bảo vệ m&ocirc;i trường. Đ&acirc;y l&agrave; giải ph&aacute;p thực tế gi&uacute;p tăng diện t&iacute;ch hiển thị cho PC, hỗ trợ theo d&otilde;i th&ocirc;ng tin nhanh ch&oacute;ng m&agrave; kh&ocirc;ng cần đầu tư th&ecirc;m phần cứng đắt đỏ.<br />\r\n<br />\r\nM&igrave;nh l&agrave;&nbsp;<b>Kim L</b>, m&igrave;nh y&ecirc;u th&iacute;ch c&ocirc;ng nghệ, m&igrave;nh chia sẻ những g&igrave; m&igrave;nh biết với mọi người.<br />\r\nCh&acirc;n th&agrave;nh cảm ơn bạn đ&atilde; xem.</p>', 'assets/storage/images/blog_4MUAQE.webp', 'Đừng lãng phí máy tính bảng cũ, hãy biến nó thành màn hình phụ cho PC ngay!', '', '', 41, 0, 0, 'published', '2025-12-20 22:43:19', '2025-12-20 22:43:19', '2025-12-20 22:43:19'),
(4, 1, 6255, 'Trải nghiệm tính năng Click to Do trên Windows 11 26120.3863: &quot;Cirle to Search&quot; phiên bản máy tính', 'trai-nghiem-tinh-nang-click-to-do-tren-windows-11-261203863-cirle-to-search-phien-ban-may-tinh', '', '<p>C&oacute; thể n&oacute;i khi&nbsp;<a href=\"https://tinhte.vn/tag/recall\">Recall</a>&nbsp;v&agrave;&nbsp;<a href=\"https://tinhte.vn/tag/click-to-do\">Click to Do</a>&nbsp;ch&iacute;nh thức ra mắt, c&aacute;c mẫu m&aacute;y&nbsp;<a href=\"https://tinhte.vn/tag/copilot-pc-3\">Copilot+ PC</a>&nbsp;mới thực sự tạo ra kh&aacute;c biệt so với phần c&ograve;n lại v&igrave; những g&igrave; m&agrave; Recall v&agrave; Click to Do mang lại cho m&igrave;nh sau khoảng 1 ng&agrave;y sử dụng l&agrave; rất tuyệt vời.<br />\r\n<br />\r\nĐ&aacute;ng lẽ&nbsp;<a href=\"https://tinhte.vn/tag/microsoft\">Microsoft</a>&nbsp;phải ra mắt Recall v&agrave; Click to Do l&acirc;u rồi v&agrave; người d&ugrave;ng mua m&aacute;y t&iacute;nh Copilot+ PC kh&ocirc;ng đ&aacute;ng phải chờ đợi l&acirc;u như vậy. Nhưng d&ugrave; sao, b&acirc;y giờ ở những bản build Insider, người d&ugrave;ng đ&atilde; c&oacute; những trải nghiệm gần như trọn vẹn v&agrave; m&igrave;nh sẽ chia sẻ những ấn tượng đầu ti&ecirc;n trong b&agrave;i n&agrave;y. Recall hẹn anh em trong một b&agrave;i sau nh&eacute; v&igrave; cần phải sử dụng nhiều để Recall thực sự c&oacute; hiệu quả.<br />\r\n<br />\r\nTheo th&ocirc;ng tin gần nhất được Microsoft c&ocirc;ng bố, t&iacute;nh năng Click to Do đ&atilde; được cập nhật cho c&aacute;c mẫu m&aacute;y Copilot+ PC đang chạy bản build Insider bất k&igrave;, ở k&ecirc;nh bất k&igrave; n&agrave;o cũng được v&agrave; nếu bạn muốn trải nghiệm m&agrave; hạn chế lỗi xảy ra nhất c&oacute; thể th&igrave; c&oacute; thể chọn k&ecirc;nh Beta.</p>\r\n\r\n<p><a href=\"https://tinhte.vn/thread/tong-hop-cac-tinh-nang-moi-se-duoc-cap-nhat-cho-nguoi-dung-copilot-pc-ngay-trong-thang-nay.4016592/\"><img height=\"300\" src=\"https://imgproxy7.tinhte.vn/iR9LByG1lynmgPVPn3PcHlLmxtkIUvFyHcHKYisU-ko/rs:fill:480:300:0/plain/https://photo2.tinhte.vn/data/attachment-files/2025/05/8725971_copilot-vision.jpg\" width=\"480\" /></a></p>\r\n\r\n<h2 title=\"Tổng hợp các tính năng mới sẽ được cập nhật cho người dùng Copilot+ PC ngay trong tháng này\"><a href=\"https://tinhte.vn/thread/tong-hop-cac-tinh-nang-moi-se-duoc-cap-nhat-cho-nguoi-dung-copilot-pc-ngay-trong-thang-nay.4016592/\">Tổng hợp c&aacute;c t&iacute;nh năng mới sẽ được cập nhật cho người d&ugrave;ng Copilot+ PC ngay trong th&aacute;ng n&agrave;y</a></h2>\r\n\r\n<p><a href=\"https://tinhte.vn/thread/tong-hop-cac-tinh-nang-moi-se-duoc-cap-nhat-cho-nguoi-dung-copilot-pc-ngay-trong-thang-nay.4016592/\">Đa phần c&aacute;c t&iacute;nh năng n&agrave;y đều đ&atilde; được Microsoft giới thiệu từ năm ngo&aacute;i, nhưng v&igrave; nhiều l&iacute; do m&agrave; h&atilde;ng đ&atilde; l&ugrave;i ng&agrave;y ph&aacute;t h&agrave;nh (v&iacute; dụ: Recall) cho đến tận ng&agrave;y h&ocirc;m nay. Nhưng thời gian chờ đợi đ&oacute; sắp kết th&uacute;c với một loạt t&iacute;nh năng d&agrave;nh ri&ecirc;ng cho...</a></p>\r\n\r\n<p><a href=\"https://tinhte.vn/thread/tong-hop-cac-tinh-nang-moi-se-duoc-cap-nhat-cho-nguoi-dung-copilot-pc-ngay-trong-thang-nay.4016592/\">&nbsp;tinhte.vn</a></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h2 id=\"menuid0\"><b>Click to Do l&agrave; g&igrave;?</b></h2>\r\n\r\n<p><img alt=\"[​IMG]\" data-url=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704173_Screenshot_2025-04-13_092603_Medium.png\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704173_Screenshot_2025-04-13_092603_Medium.png\" /><br />\r\n&nbsp;</p>\r\n\r\n<p><br />\r\nN&oacute;i một c&aacute;ch đơn giản, Click to Do l&agrave; &quot;Circle to Search&quot; phi&ecirc;n bản m&aacute;y t&iacute;nh, cho ph&eacute;p n&oacute; &quot;đọc&quot; được nội dung tr&ecirc;n m&agrave;n h&igrave;nh v&agrave; tương t&aacute;c lại với những nội dung đ&oacute; tuỳ v&agrave;o ho&agrave;n cảnh.<br />\r\n<br />\r\nSo với Circle to Search vốn chỉ c&oacute; t&iacute;nh năng t&igrave;m kiếm, Click to Do cho người d&ugrave;ng nhiều c&aacute;ch sử dụng hơn nữa, t&iacute;ch hợp cả một m&ocirc; h&igrave;nh ng&ocirc;n ngữ nhỏ (SLM) l&agrave; Phi Silica của Microsoft để thực hiện t&oacute;m tắt văn bản, viết lại đoạn văn bản đ&atilde; chọn v&agrave; t&iacute;nh năng OCR để tự động nhận diện văn bản, email, số điện thoại. Click to Do cũng t&iacute;ch hợp Copilot để bạn c&oacute; thể hỏi Copilot (qua t&iacute;nh năng Ask Copilot) .<br />\r\n<br />\r\nNgo&agrave;i văn bản, Click to Do c&oacute; thể hoạt động với h&igrave;nh ảnh, bao gồm:<br />\r\n&nbsp;</p>\r\n\r\n<ul>\r\n	<li><b>Sao ch&eacute;p</b>: Lưu v&agrave;o clipboard.</li>\r\n	<li><b>Lưu th&agrave;nh</b>: Chọn vị tr&iacute;.</li>\r\n	<li><b>Chia sẻ</b>: T&ugrave;y chọn chia sẻ file.</li>\r\n	<li><b>Mở bằng</b>: V&iacute; dụ Photos, Paint.</li>\r\n	<li><b>T&igrave;m kiếm h&igrave;nh ảnh</b>: Bing qua tr&igrave;nh duyệt mặc định.</li>\r\n	<li><b>L&agrave;m mờ nền</b>: Ứng dụng Photos.</li>\r\n	<li><b>X&oacute;a vật thể</b>: Photos.</li>\r\n	<li><b>X&oacute;a nền</b>: Paint.</li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h2 id=\"menuid1\"><b>C&aacute;ch Click to Do hoạt động</b></h2>\r\n\r\n<p><br />\r\nĐ&acirc;y l&agrave; t&iacute;nh năng được hoạt động ho&agrave;n to&agrave;n tại m&aacute;y của người d&ugrave;ng, tức l&agrave; d&ugrave; bạn kh&ocirc;ng kết nối internet, bạn vẫn c&oacute; thể thực hiện được một số t&aacute;c vụ, ngoại trừ t&aacute;c vụ t&igrave;m kiếm tr&ecirc;n website th&igrave; bắt buộc phải c&oacute; internet m&agrave; th&ocirc;i. N&oacute; chỉ nhận diện văn bản v&agrave; h&igrave;nh ảnh, kh&ocirc;ng xử l&yacute; nội dung, v&agrave; kh&ocirc;ng ph&acirc;n t&iacute;ch ứng dụng thu nhỏ.<br />\r\n<img alt=\"Screenshot 2025-04-13 102608 (Medium).png\" data-height=\"768\" data-width=\"1229\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704177_Screenshot_2025-04-13_102608_Medium.png\" /><br />\r\nKhi hoạt động, một con trỏ nhiều m&agrave;u sắc sẽ xuất hiện, thay đổi h&igrave;nh dạng t&ugrave;y loại nội dung, với c&aacute;c h&agrave;nh động kh&aacute;c nhau theo nội dung ph&aacute;t hiện.<br />\r\n<br />\r\nC&aacute;c file khi hoạt động sẽ tạm lưu tại&nbsp;<b>C:\\Users\\{username}\\AppData\\Local\\Temp</b>&nbsp;khi chuyển sang ứng dụng (v&iacute; dụ Paint) hoặc gửi phản hồi, n&oacute; sẽ x&oacute;a sau khi ho&agrave;n tất.<br />\r\n<img alt=\"Screenshot 2025-04-13 102737 (Medium).png\" data-height=\"768\" data-width=\"1229\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704178_Screenshot_2025-04-13_102737_Medium.png\" /><br />\r\nClick to Do c&oacute; thể hoạt động ở mọi nơi, mọi l&uacute;c, kh&ocirc;ng bị giới hạn ở tr&igrave;nh duyệt m&agrave; c&oacute; thể ở c&aacute;c ứng dụng, trong File Explorer, trong Word, Excel, Power Point, Outlook&hellip;.n&oacute;i chung n&oacute; hoạt động tr&ecirc;n to&agrave;n bộ m&aacute;y t&iacute;nh của người d&ugrave;ng.</p>\r\n\r\n<p>Quảng c&aacute;o</p>\r\n\r\n<p><iframe align=\"top\" frameborder=\"0\" height=\"90\" hspace=\"0\" id=\"adnzone_515899_0_450340\" marginheight=\"0\" name=\"adnzone_515899_0_450340\" scrolling=\"No\" src=\"javascript:if(typeof(adnzone515899)!=\'undefined\'){adnzone515899.renderIframe();}else{parent.adnzone515899.renderIframe();}\" vspace=\"0\" width=\"728\"></iframe></p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h2 id=\"menuid2\"><b>Y&ecirc;u cầu hệ thống để sử dụng Click to Do</b></h2>\r\n\r\n<p><br />\r\nTrước ti&ecirc;n&nbsp;<a href=\"https://tinhte.vn/thread/microsoft-copilot-pc-tieu-chuan-phan-cung-moi-cua-ai-pc-de-thuc-su-co-cai-goi-la-ai-everywhere.3789160/\"><b>m&aacute;y t&iacute;nh của bạn phải l&agrave; Copiltot+ PC</b></a>, tức l&agrave; những mẫu m&aacute;y t&iacute;nh c&oacute; logo Copilot+ PC d&aacute;n tr&ecirc;n m&aacute;y, hoặc m&aacute;y phải c&oacute; NPU từ 40 TOPs trở l&ecirc;n, &iacute;t nhất 16GB RAM, chip &iacute;t nhất 8 nh&acirc;n v&agrave; SSD tối thiểu l&agrave; 256GB.<br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid3\"><b>C&aacute;ch d&ugrave;ng Click to Do</b></h2>\r\n\r\n<p><br />\r\nBạn c&oacute; thể nhấn ph&iacute;m tắt&nbsp;<b><a href=\"https://tinhte.vn/tag/windows-8\">Windows</a>&nbsp;+ Q</b>&nbsp;để k&iacute;ch hoạt Click to Do hoặc sử dụng kết hợp với Snipping Tool qua ph&iacute;m tắt&nbsp;<b>Windows + Shift + S.</b><br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid4\"><b>C&aacute;c t&iacute;nh năng của Click to Do</b></h2>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<h3 id=\"menuid5\"><b>Đối với văn bản</b></h3>\r\n\r\n<p><img alt=\"Screenshot 2025-04-13 093338 (Medium).png\" data-height=\"768\" data-width=\"1229\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704179_Screenshot_2025-04-13_093338_Medium.png\" /><br />\r\n<br />\r\n<br />\r\nKhi k&iacute;ch hoạt Click to Do, OCR sẽ hoạt động v&agrave; tự động chọn c&aacute;c đoạn văn bản đang hiển thị tr&ecirc;n m&agrave;n h&igrave;nh, bạn c&oacute; thể chọn lại đoạn văn bản mong muốn v&agrave; nhấp chuột phải, bạn sẽ c&oacute; c&aacute;c h&agrave;nh động tương ứng.<br />\r\n<img alt=\"Screenshot 2025-04-13 105640.png\" data-height=\"261\" data-width=\"1226\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704196_Screenshot_2025-04-13_105640.png\" /></p>\r\n\r\n<p><br />\r\nC&aacute;c t&iacute;nh năng cơ bản nhất l&agrave; copy, mở trong Notepad (hoặc ứng dụng kh&aacute;c tuỳ chọn) cũng như t&igrave;m kiếm tr&ecirc;n web. Hơn nữa, tr&ecirc;n c&ugrave;ng của m&agrave;n h&igrave;nh sẽ c&oacute; một khung nhập liệu, bạn c&oacute; thể t&igrave;m kiếm tr&ecirc;n khung đ&oacute;, c&oacute; thể g&otilde; văn bản hoặc d&ugrave;ng giọng n&oacute;i.<br />\r\n<img alt=\"0babd17ea03da247218c4aa35c8f451f68b43c78 (Medium).png\" data-height=\"767\" data-width=\"1366\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704205_0babd17ea03da247218c4aa35c8f451f68b43c78_Medium.png\" /><br />\r\nN&acirc;ng cao hơn bạn sẽ c&oacute; thể hỏi Copilot về đoạn văn đ&atilde; chọn, nhờ&nbsp;<a href=\"https://tinhte.vn/tag/ai\">AI</a>&nbsp;t&oacute;m tắt đoạn văn bản đ&oacute;, viết lại đoạn văn bản đ&oacute; để dễ hiểu hơn. Hiện tại với t&iacute;nh năng n&agrave;y th&igrave; m&igrave;nh chưa sử dụng được, cũng kh&ocirc;ng r&otilde; v&igrave; sao nhưng m&igrave;nh nghĩ c&aacute;c bản Preview thường sẽ hay gặp lỗi trong qu&aacute; tr&igrave;nh sử dụng.<br />\r\n<img alt=\"Screenshot 2025-04-13 094628 (Medium).png\" data-height=\"768\" data-width=\"1229\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704181_Screenshot_2025-04-13_094628_Medium.png\" /><br />\r\nNhưng bạn c&oacute; thể thấy, khi Click to Do (hoặc Recall) hoạt động với văn bản v&agrave; h&igrave;nh ảnh, NPU sẽ được sử dụng, l&uacute;c n&agrave;y n&oacute; đang sử dụng m&ocirc; h&igrave;nh Phi Silica để ph&acirc;n t&iacute;ch v&agrave; thực hiện c&aacute;c t&aacute;c vụ được người d&ugrave;ng chỉ định.<br />\r\n<br />\r\nCho đến khi Click to Do hay Recall ra mắt, NPU tr&ecirc;n Copilot+ PC thực sự kh&ocirc;ng l&agrave;m được g&igrave; nhiều ngoại trừ một số&nbsp;<a href=\"https://tinhte.vn/thread/dung-ai-de-tach-loi-voi-nhac-bang-audacity-va-openvino-plugin.3767465/\">rất rất &iacute;t c&aacute;c ứng dụng, phần mềm tận dụng được NPU</a>&nbsp;hay bộ Windows Studio Effects c&oacute; sẵn mặc định tr&ecirc;n Windows 11. M&agrave; đ&oacute; l&agrave; với NPU tr&ecirc;n c&aacute;c vi xử l&yacute; của Intel từ Meteor Lake nh&eacute;, c&ograve;n m&igrave;nh đang d&ugrave;ng AMD Ryzen AI 7 350 tr&ecirc;n chiếc Zenbook 14 (NPU XDNA 2) th&igrave; n&oacute; lại c&agrave;ng hiếm được c&aacute;c phần mềm hỗ trợ hơn nữa.<br />\r\n&nbsp;</p>\r\n\r\n<h3 id=\"menuid6\"><b>Đối với h&igrave;nh ảnh</b></h3>\r\n\r\n<p><img alt=\"Screenshot 2025-04-13 093506 (Medium).png\" data-height=\"768\" data-width=\"1229\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704185_Screenshot_2025-04-13_093506_Medium.png\" /><br />\r\n<br />\r\n<br />\r\nVề cơ bản th&igrave; đối với h&igrave;nh ảnh, Click to Do cũng hoạt động như với văn bản, nhưng sẽ c&oacute; th&ecirc;m mục t&igrave;m kiếm với h&igrave;nh ảnh, n&oacute; sẽ sử dụng Bing để t&igrave;m.<br />\r\n<br />\r\nỞ khoản n&agrave;y, m&igrave;nh thử t&igrave;m &iacute;t nhất hai tấm h&igrave;nh tr&ecirc;n Tinh tế th&igrave; Bing kh&ocirc;ng trả về được kết quả, c&ograve;n với Circle to Search th&igrave; n&oacute; trả kết quả rất ch&iacute;nh x&aacute;c. Circle to Search c&ograve;n cho ph&eacute;p người d&ugrave;ng điều chỉnh phạm vi t&igrave;m kiếm (mở rộng hoặc thu hẹp) c&ograve;n với Click to Do th&igrave; chưa l&agrave;m được.<br />\r\nDĩ nhi&ecirc;n t&iacute;nh năng t&igrave;m kiếm website đối với h&igrave;nh ảnh cần phải c&oacute; kết nối internet v&agrave; chỉ l&uacute;c đ&oacute; th&igrave; dữ liệu của bạn (tấm h&igrave;nh đ&oacute;) mới được chia sẻ l&ecirc;n cloud để phục vụ việc trả kết quả m&agrave; th&ocirc;i. Về việc kết quả kh&ocirc;ng hiển thị như mong muốn th&igrave; m&igrave;nh cũng đo&aacute;n rằng l&agrave; v&igrave; lỗi ở bản Preview m&agrave; th&ocirc;i.<br />\r\n<img alt=\"Screenshot 2025-04-13 103634 (Medium).png\" data-height=\"768\" data-width=\"1227\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704186_Screenshot_2025-04-13_103634_Medium.png\" /><br />\r\n<i>Thử xo&aacute; background với Click to Do. (Sử dụng ứng dụng Paint)</i><br />\r\n<img alt=\"Screenshot 2025-04-13 103713 (Medium).png\" data-height=\"768\" data-width=\"1322\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704188_Screenshot_2025-04-13_103713_Medium.png\" /><br />\r\n<i>Thử l&agrave;m mở background với Click to Do. (Sử dụng ứng dụng Photos)</i><br />\r\n<img alt=\"Screenshot 2025-04-13 103915 (Medium).png\" data-height=\"768\" data-width=\"1322\" src=\"https://photo2.tinhte.vn/data/attachment-files/2025/04/8704189_Screenshot_2025-04-13_103915_Medium.png\" /><br />\r\n<i>Thử xo&aacute; vật thể với Click to Do. (Sử dụng ứng dụng Photos)</i><br />\r\n&nbsp;</p>\r\n\r\n<h2 id=\"menuid7\"><b>Tạm kết</b></h2>\r\n\r\n<p><br />\r\nM&igrave;nh tin rằng khi Click to Do ra mắt ch&iacute;nh thức v&agrave; c&aacute;c t&iacute;nh năng được ho&agrave;n chỉnh, n&oacute; sẽ mở ra một khả năng hoạt động cực k&igrave; linh động v&agrave; hữu hiệu cho người d&ugrave;ng. M&igrave;nh lấy đơn cử một v&iacute; dụ khi người d&ugrave;ng cần tinh chỉnh một c&agrave;i đặt g&igrave; đ&oacute; tr&ecirc;n m&aacute;y t&iacute;nh m&agrave; kh&ocirc;ng r&otilde; n&oacute; c&oacute; hiệu quả g&igrave;, bạn c&oacute; thể mở Click to Do v&agrave; hỏi Copilot, hoặc t&oacute;m tắt lời giải th&iacute;ch của Microsoft về thiết lập đ&oacute; trực tiếp tr&ecirc;n m&agrave;n h&igrave;nh m&agrave; kh&ocirc;ng cần phải t&igrave;m đ&acirc;u xa.<br />\r\n<br />\r\nM&igrave;nh sẽ quay trở lại chia sẻ với anh em một lần nữa về Click to Do khi n&oacute; hoạt động tốt v&agrave; ch&iacute;nh thức ra mắt to&agrave;n bộ người d&ugrave;ng.</p>', 'assets/storage/images/blog_OY6SBE.webp', 'Trải nghiệm tính năng Click to Do trên Windows 11 26120.3863: &quot;Cirle to Search&quot; phiên bản máy tính', '', '', 22, 0, 0, 'published', '2025-12-21 12:47:48', '2025-12-21 12:47:48', '2025-12-21 12:47:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`, `image`, `meta_title`, `meta_description`, `meta_keywords`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Hướng dẫn', 'huong-dan', '', NULL, 'Hướng dẫn', '', '', 0, 1, '2025-12-20 22:36:14', '2025-12-20 22:36:14'),
(2, 'Tin tức', 'tin-tuc', '', NULL, 'Tin tức', '', '', 0, 1, '2025-12-20 22:37:35', '2025-12-20 22:37:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bot_telegram_logs`
--

CREATE TABLE `bot_telegram_logs` (
  `id` int(11) NOT NULL,
  `chat_id` mediumtext DEFAULT NULL,
  `message` mediumtext DEFAULT NULL,
  `token` mediumtext DEFAULT NULL,
  `response` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `trans_id` varchar(255) DEFAULT NULL,
  `telco` varchar(255) DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `price` int(11) NOT NULL DEFAULT 0,
  `serial` mediumtext DEFAULT NULL,
  `pin` mediumtext DEFAULT NULL,
  `status` varchar(55) NOT NULL DEFAULT 'pending',
  `create_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `reason` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `supplier_id` int(11) NOT NULL DEFAULT 0,
  `stt` int(11) NOT NULL DEFAULT 0,
  `icon` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `title` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `keywords` mediumtext DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `status` varchar(55) NOT NULL DEFAULT 'show',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `supplier_id`, `stt`, `icon`, `name`, `title`, `description`, `keywords`, `slug`, `content`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, 0, 3, 'assets/storage/images/category/EIT3.png', 'Tiện Ích', NULL, '', NULL, 'tien-ich', NULL, 'show', '2025-11-27 20:59:28', '2026-02-05 23:12:56'),
(2, 0, 0, 2, 'assets/storage/images/category/ZXK4.png', 'Gift Cards', NULL, '', NULL, 'gift-cards', NULL, 'show', '2025-11-27 21:01:26', '2026-02-05 23:12:56'),
(3, 1, 0, 9, 'assets/storage/images/category/DOQ4.png', 'Giải Trí', NULL, '', NULL, 'giai-tri', NULL, 'show', '2025-11-27 21:03:51', '2026-01-28 20:22:04'),
(4, 1, 0, 6, 'assets/storage/images/category/QT1M.png', 'Nghe nhạc', NULL, '', NULL, 'nghe-nhac', NULL, 'show', '2025-11-27 21:05:25', '2026-01-28 20:22:05'),
(5, 1, 0, 4, 'assets/storage/images/category/9IRK.png', 'Học Tập', NULL, '', NULL, 'hoc-tap', NULL, 'show', '2025-11-27 21:09:22', '2026-01-28 20:22:10'),
(7, 0, 0, 1, 'assets/storage/images/category/4R3G.png', 'Trò chơi', NULL, '', NULL, 'tro-choi', NULL, 'show', '2025-11-29 20:12:21', '2026-02-05 23:12:56'),
(8, 7, 0, 0, 'assets/storage/images/category/UE8P.png', 'Top-Up Game', NULL, '', NULL, 'top-up-game', NULL, 'show', '2025-11-29 22:25:54', '2026-01-25 16:15:22'),
(9, 1, 0, 5, 'assets/storage/images/category/6BES.png', 'Thiết Kế - Đồ họa', NULL, '', NULL, 'thiet-ke--do-hoa', NULL, 'show', '2025-11-30 23:47:48', '2026-01-28 20:22:10'),
(10, 7, 0, 0, 'assets/storage/images/category/12AJ.png', 'Game Steam', NULL, '', NULL, 'game-steam', NULL, 'show', '2025-11-30 23:51:43', '2026-01-25 16:15:22'),
(11, 1, 0, 3, 'assets/storage/images/category/PD9V.png', 'VPN', NULL, '', NULL, 'vpn', NULL, 'show', '2025-12-21 22:46:17', '2026-01-28 20:22:05'),
(12, 1, 0, 2, 'assets/storage/images/category/GTA6.png', 'ESIM', NULL, '', NULL, 'esim', NULL, 'show', '2025-12-21 22:48:18', '2026-01-28 20:22:05'),
(13, 1, 0, 1, 'assets/storage/images/category/V6N2.png', 'Sức khỏe', NULL, '', NULL, 'suc-khoe', NULL, 'show', '2025-12-21 23:59:41', '2026-01-28 20:22:05'),
(14, 1, 0, 7, 'assets/storage/images/category/P4D1.png', 'Làm việc', NULL, '', NULL, 'lam-viec', NULL, 'show', '2025-12-22 16:48:13', '2026-01-28 20:22:05'),
(15, 1, 0, 8, 'assets/storage/images/category/MR2E.png', 'Tài khoản AI', NULL, '', NULL, 'tai-khoan-ai', NULL, 'show', '2026-01-19 13:25:22', '2026-01-28 20:22:04'),
(17, 1, 3, 0, 'assets/storage/images/category_9ec7dd58.webp', 'Trestt', NULL, '', NULL, 'trestt', NULL, 'show', '2026-01-31 16:59:50', '2026-01-31 16:59:50'),
(18, 0, 0, 5, 'assets/storage/images/icon4YWP.png', 'Tài Khoản Facebook', NULL, '', NULL, 'tai-khoan-facebook', NULL, 'show', '2026-02-05 23:12:52', '2026-02-05 23:13:22'),
(19, 0, 0, 4, 'assets/storage/images/iconVJUB.png', 'Tài Khoản TikTok', NULL, '', NULL, 'tai-khoan-tiktok', NULL, 'show', '2026-02-05 23:13:16', '2026-02-05 23:13:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(20,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `usage_limit` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `user_limit` int(11) NOT NULL DEFAULT 0,
  `product_ids` text DEFAULT NULL,
  `plan_ids` text DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupon_usages`
--

CREATE TABLE `coupon_usages` (
  `id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `order_trans_id` varchar(100) DEFAULT NULL,
  `discount_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `order_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `used_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `currencies`
--

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `name` mediumtext DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `rate` float NOT NULL DEFAULT 0,
  `symbol_left` mediumtext DEFAULT NULL,
  `symbol_right` mediumtext DEFAULT NULL,
  `seperator` mediumtext DEFAULT NULL,
  `display` int(11) NOT NULL DEFAULT 1,
  `default_currency` int(11) NOT NULL DEFAULT 0,
  `decimal_currency` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `currencies`
--

INSERT INTO `currencies` (`id`, `name`, `code`, `rate`, `symbol_left`, `symbol_right`, `seperator`, `display`, `default_currency`, `decimal_currency`) VALUES
(3, 'Đồng', 'VND', 1, NULL, 'đ', 'dot', 1, 1, 0),
(4, 'Dollar', 'USD', 27000, '$', NULL, 'dot', 1, 0, 2),
(5, 'Chinese Yuan', 'CNY', 3500, '¥', NULL, 'comma', 1, 0, 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `deposit_log`
--

CREATE TABLE `deposit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `method` varchar(255) DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `received` float NOT NULL DEFAULT 0,
  `create_time` int(11) DEFAULT 0,
  `is_virtual` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dongtien`
--

CREATE TABLE `dongtien` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `sotientruoc` decimal(20,2) NOT NULL DEFAULT 0.00,
  `sotienthaydoi` decimal(20,2) NOT NULL DEFAULT 0.00,
  `sotiensau` decimal(20,2) NOT NULL DEFAULT 0.00,
  `thoigian` datetime NOT NULL,
  `noidung` mediumtext DEFAULT NULL,
  `transid` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `email_campaigns`
--

CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL,
  `name` mediumtext DEFAULT NULL,
  `subject` mediumtext DEFAULT NULL,
  `cc` mediumtext DEFAULT NULL,
  `bcc` mediumtext DEFAULT NULL,
  `content` longblob DEFAULT NULL,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `email_queue`
--

CREATE TABLE `email_queue` (
  `id` int(11) UNSIGNED NOT NULL,
  `to_email` varchar(255) NOT NULL COMMENT 'Email người nhận',
  `to_name` varchar(100) DEFAULT NULL COMMENT 'Tên người nhận',
  `subject` varchar(998) NOT NULL COMMENT 'Tiêu đề email',
  `body` longtext NOT NULL COMMENT 'Nội dung email (HTML)',
  `priority` tinyint(1) NOT NULL DEFAULT 3 COMMENT 'Độ ưu tiên: 1=cao, 5=thấp',
  `status` enum('pending','processing','sent','failed') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái',
  `attempts` tinyint(2) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Số lần thử gửi',
  `max_attempts` tinyint(2) UNSIGNED NOT NULL DEFAULT 3 COMMENT 'Số lần thử tối đa',
  `error_message` text DEFAULT NULL COMMENT 'Lỗi nếu gửi thất bại',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dữ liệu bổ sung (JSON)' CHECK (json_valid(`metadata`)),
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo',
  `scheduled_at` datetime NOT NULL COMMENT 'Thời gian dự kiến gửi',
  `last_attempt_at` datetime DEFAULT NULL COMMENT 'Lần thử gần nhất',
  `sent_at` datetime DEFAULT NULL COMMENT 'Thời gian gửi thành công'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Email Queue for async sending';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `email_sending`
--

CREATE TABLE `email_sending` (
  `id` int(11) NOT NULL,
  `camp_id` int(11) DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `response` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_attempts`
--

CREATE TABLE `failed_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL,
  `type` varchar(55) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `failed_attempts`
--

INSERT INTO `failed_attempts` (`id`, `ip_address`, `attempts`, `create_gettime`, `type`) VALUES
(1, '127.0.0.1', 1, '2026-02-05 23:34:55', 'LOGIN'),
(2, '127.0.0.1', 1, '2026-02-05 23:37:41', 'LOGIN');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sales`
--

CREATE TABLE `flash_sales` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Tên chương trình Flash Sale',
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage' COMMENT 'Loại giảm giá',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá trị giảm',
  `max_discount_amount` decimal(15,2) DEFAULT NULL COMMENT 'Giảm tối đa (với %)',
  `start_time` datetime NOT NULL COMMENT 'Thời gian bắt đầu',
  `end_time` datetime NOT NULL COMMENT 'Thời gian kết thúc',
  `quantity_limit` int(11) DEFAULT 0 COMMENT 'Giới hạn tổng số lượng (0=không giới hạn)',
  `quantity_sold` int(11) DEFAULT 0 COMMENT 'Số lượng đã bán',
  `per_user_limit` int(11) DEFAULT 0 COMMENT 'Giới hạn mỗi user (0=không giới hạn)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=active, 0=inactive',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `flash_sales`
--

INSERT INTO `flash_sales` (`id`, `name`, `description`, `discount_type`, `discount_value`, `max_discount_amount`, `start_time`, `end_time`, `quantity_limit`, `quantity_sold`, `per_user_limit`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Flash Sale Ăn Tết', '', 'percentage', 34.00, 0.00, '2026-02-06 01:00:00', '2026-02-13 07:52:00', 0, 0, 0, 1, '2026-02-06 06:53:02', '2026-02-06 06:53:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sale_items`
--

CREATE TABLE `flash_sale_items` (
  `id` int(11) NOT NULL,
  `flash_sale_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL COMMENT 'ID sản phẩm (null nếu áp dụng cho tất cả)',
  `plan_id` int(11) DEFAULT NULL COMMENT 'ID gói (null nếu áp dụng cho tất cả gói của sản phẩm)',
  `flash_price` decimal(15,2) DEFAULT NULL COMMENT 'Giá Flash Sale cố định (nếu có)',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `flash_sale_items`
--

INSERT INTO `flash_sale_items` (`id`, `flash_sale_id`, `product_id`, `plan_id`, `flash_price`, `created_at`) VALUES
(1, 1, NULL, 10, NULL, '2026-02-06 06:53:02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `flash_sale_purchases`
--

CREATE TABLE `flash_sale_purchases` (
  `id` int(11) NOT NULL,
  `flash_sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `stt` int(11) NOT NULL DEFAULT 0,
  `lang` varchar(255) DEFAULT NULL,
  `code` varchar(55) DEFAULT NULL,
  `icon` mediumtext DEFAULT NULL,
  `lang_default` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `languages`
--

INSERT INTO `languages` (`id`, `stt`, `lang`, `code`, `icon`, `lang_default`, `status`) VALUES
(1, 0, 'Vietnamese', 'vi', 'assets/storage/flags/flag_Vietnamese.png', 1, 1),
(2, 0, 'English', 'en', 'assets/storage/flags/flag_English.png', 0, 1),
(19, 0, 'Thailand', 'th', 'assets/storage/flags/flag_Thailand.png', 0, 1),
(20, 0, 'Chinese', 'zh', 'assets/storage/flags/flag_Chinese.png', 0, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `createdate` datetime NOT NULL,
  `action` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `ip`, `device`, `createdate`, `action`) VALUES
(1, 0, '103.200.23.88', '', '2026-02-05 22:03:47', 'Cập nhật hệ thống từ phiên bản 1.0.6 lên phiên bản 1.1.5'),
(2, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:04:41', 'Tài khoản đầu tiên khi đăng ký được set Admin cao nhất'),
(3, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:09:51', 'Thay đổi ảnh giao diện website'),
(4, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:09:57', 'Thay đổi ảnh giao diện website'),
(5, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:10:04', 'Thay đổi ảnh giao diện website'),
(6, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:10:10', 'Thay đổi ảnh giao diện website'),
(7, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:10:48', 'Thay đổi cài đặt SHOPKEY'),
(8, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:11:14', 'Thay đổi cài đặt thông báo'),
(9, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:11:46', 'Thay đổi cài đặt chung'),
(10, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:13:21', 'Cập nhật banner'),
(11, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:24:03', 'Thay đổi cài đặt bảo mật'),
(12, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:24:16', 'Thay đổi cài đặt Widget'),
(13, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 22:24:28', 'Cập nhật vị trí và thứ tự banner'),
(14, 2, '113.174.135.236', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:06:46', 'Create an account'),
(15, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:12:52', 'Add Category (Tài Khoản Facebook).'),
(16, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:13:16', 'Add Category (Tài Khoản TikTok).'),
(17, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:19:54', 'Set mặc định tiền tệ (Dollar ID 4)'),
(18, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:19:58', 'Set mặc định tiền tệ (Đồng ID 3)'),
(19, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:20:09', 'Chỉnh sửa tiền tệ (Dollar).'),
(20, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:20:09', 'Chỉnh sửa tiền tệ (Dollar).'),
(21, 1, '123.21.11.204', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:21:39', 'Thay đổi ảnh giao diện website'),
(22, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 23:37:59', 'Tài khoản đầu tiên khi đăng ký được set Admin cao nhất'),
(23, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 00:12:50', 'Thay đổi ảnh giao diện website'),
(24, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 06:49:58', 'Edit Role (Super Admin).'),
(25, 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-06 06:53:02', 'Add Flash Sale (Flash Sale Ăn Tết).');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `log_ref`
--

CREATE TABLE `log_ref` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `reason` mediumtext DEFAULT NULL,
  `sotientruoc` float NOT NULL DEFAULT 0,
  `sotienthaydoi` float NOT NULL DEFAULT 0,
  `sotienhientai` float NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_expiry_notifications`
--

CREATE TABLE `order_expiry_notifications` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `notification_type` enum('expiring_soon','expired') NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_log`
--

CREATE TABLE `order_log` (
  `id` int(11) NOT NULL,
  `buyer` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `pay` float NOT NULL DEFAULT 0,
  `amount` int(11) NOT NULL DEFAULT 0,
  `create_time` int(11) NOT NULL,
  `is_virtual` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_bakong`
--

CREATE TABLE `payment_bakong` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(64) NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền thanh toán',
  `status` tinyint(4) DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `checkout_url` varchar(255) NOT NULL,
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_bank`
--

CREATE TABLE `payment_bank` (
  `id` int(11) NOT NULL,
  `method` varchar(55) DEFAULT NULL,
  `tid` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `amount` int(11) DEFAULT 0,
  `received` int(11) DEFAULT 0,
  `create_gettime` datetime DEFAULT NULL,
  `create_time` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT 0,
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_bank_invoice`
--

CREATE TABLE `payment_bank_invoice` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `bank_id` int(11) NOT NULL DEFAULT 0,
  `short_name` varchar(55) DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `received` float NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `create_time` int(11) NOT NULL DEFAULT 0,
  `status` enum('waiting','expired','completed') NOT NULL DEFAULT 'waiting',
  `note` text DEFAULT NULL,
  `api_type` varchar(55) DEFAULT NULL,
  `api_tid` varchar(255) DEFAULT NULL,
  `api_desc` varchar(2555) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_crypto`
--

CREATE TABLE `payment_crypto` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(55) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `request_id` varchar(55) DEFAULT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `received` float NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `status` varchar(55) NOT NULL DEFAULT 'waiting',
  `msg` mediumtext DEFAULT NULL,
  `url_payment` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_flutterwave`
--

CREATE TABLE `payment_flutterwave` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `tx_ref` varchar(55) DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `price` float NOT NULL DEFAULT 0,
  `currency` mediumtext DEFAULT NULL,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `status` varchar(55) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_korapay`
--

CREATE TABLE `payment_korapay` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(64) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền thực nhận',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền thanh toán',
  `status` tinyint(4) DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'ID user trong hệ thống (nếu có)',
  `checkout_url` varchar(255) NOT NULL,
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_manual`
--

CREATE TABLE `payment_manual` (
  `id` int(11) NOT NULL,
  `icon` mediumtext DEFAULT NULL,
  `title` mediumtext DEFAULT NULL,
  `slug` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `display` int(11) NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_momo`
--

CREATE TABLE `payment_momo` (
  `id` int(11) NOT NULL,
  `method` varchar(55) DEFAULT NULL,
  `tid` varchar(55) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `amount` int(11) DEFAULT 0,
  `received` int(11) DEFAULT 0,
  `create_gettime` datetime DEFAULT NULL,
  `create_time` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT 0,
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_openpix`
--

CREATE TABLE `payment_openpix` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(64) NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền thanh toán',
  `status` tinyint(4) DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `checkout_url` varchar(255) NOT NULL,
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_paypal`
--

CREATE TABLE `payment_paypal` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trans_id` varchar(255) DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `price` int(11) NOT NULL DEFAULT 0,
  `create_date` datetime NOT NULL,
  `create_time` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_pm`
--

CREATE TABLE `payment_pm` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `payment_id` varchar(255) DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `price` int(11) NOT NULL DEFAULT 0,
  `create_date` datetime NOT NULL,
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_date` datetime NOT NULL,
  `update_time` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_squadco`
--

CREATE TABLE `payment_squadco` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `transaction_ref` varchar(55) DEFAULT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_thesieure`
--

CREATE TABLE `payment_thesieure` (
  `id` int(11) NOT NULL,
  `method` varchar(55) DEFAULT NULL,
  `tid` varchar(55) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `received` int(11) NOT NULL DEFAULT 0,
  `create_gettime` datetime NOT NULL,
  `create_time` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_tmweasyapi`
--

CREATE TABLE `payment_tmweasyapi` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(64) NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực nhận',
  `amount` int(11) NOT NULL DEFAULT 0 COMMENT 'Số tiền thanh toán',
  `status` tinyint(4) DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `checkout_url` varchar(255) NOT NULL,
  `notication` int(11) NOT NULL DEFAULT 0,
  `id_pay` varchar(55) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_toyyibpay`
--

CREATE TABLE `payment_toyyibpay` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `trans_id` varchar(50) DEFAULT NULL,
  `billName` mediumtext DEFAULT NULL,
  `amount` float NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `BillCode` varchar(50) DEFAULT NULL,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `reason` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_xipay`
--

CREATE TABLE `payment_xipay` (
  `id` int(11) NOT NULL,
  `out_trade_no` varchar(64) NOT NULL,
  `transaction_id` varchar(64) DEFAULT NULL COMMENT 'Mã giao dịch do Xipay trả về',
  `type` varchar(20) DEFAULT NULL COMMENT 'Phương thức thanh toán (alipay, wxpay...)',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền thực nhận',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền thanh toán',
  `param` varchar(255) DEFAULT NULL COMMENT 'Tham số mở rộng',
  `product_name` varchar(255) DEFAULT NULL COMMENT 'Tên sản phẩm/dịch vụ',
  `status` tinyint(4) DEFAULT 0 COMMENT 'Trạng thái giao dịch: 0=pending,1=success,2=fail...',
  `notify_data` mediumtext DEFAULT NULL COMMENT 'Lưu dữ liệu notify (nếu cần)',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'ID user trong hệ thống (nếu có)',
  `notication` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `category_ids` text DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0 COMMENT 'Điểm đánh giá trung bình (1-5)',
  `rating_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng đánh giá',
  `sold` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng đã bán'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `supplier_id`, `sort_order`, `category_ids`, `name`, `slug`, `description`, `image`, `status`, `created_at`, `updated_at`, `rating`, `rating_count`, `sold`) VALUES
(1, 0, 8, '4', 'Youtube Premium & YouTube Music', 'youtube-premium--youtube-music', '<p>N&acirc;ng cấp YouTube Premium &amp; YouTube Music tại Ritokey.com &ndash; Loại bỏ mọi r&agrave;o cản quảng c&aacute;o, nghe nhạc, xem phim, học tập, giải tr&iacute; đỉnh cao bất tận!</p>', 'assets/storage/images/product_HUI8.jpg', 1, '2025-11-27 22:08:10', '2026-01-28 19:43:13', 0.0, 0, 517),
(2, 0, 7, '5', 'Duolingo Super', 'duolingo-super', '', 'assets/storage/images/product_0VC9.png', 1, '2025-11-28 12:59:56', '2026-01-28 19:43:13', 0.0, 0, 1548),
(3, 0, 6, '9', 'Sản phẩm Canva Pro', 'san-pham-canva-pro', '', 'assets/storage/images/product_CG5V.jpg', 1, '2025-11-28 14:10:59', '2026-01-28 19:43:13', 0.0, 0, 521),
(6, 0, 3, '3', 'Disney Plus: Nâng Cấp Tài Khoản Plus', 'disney-plus-nang-cap-tai-khoan-plus', '', 'assets/storage/images/product_ZARX.jpg', 1, '2025-11-29 22:24:48', '2026-01-28 19:43:13', 0.0, 0, 0),
(7, 0, 2, '3', 'Nitro Discord', 'nitro-discord', '', 'assets/storage/images/product_ZR37.png', 1, '2025-11-29 22:27:27', '2026-01-28 19:43:13', 0.0, 0, 0),
(8, 0, 1, '9', 'CapCut Pro', 'capcut-pro', '', 'assets/storage/images/product_GTUZ.png', 1, '2025-11-30 23:48:16', '2026-01-28 19:43:13', 0.0, 0, 581),
(9, 0, 0, '8', 'Nạp Tokens Honor Of Kings Global Chỉ Cần ID', 'nap-tokens-honor-of-kings-global-chi-can-id', '', 'assets/storage/images/product_NBZ0.png', 1, '2025-11-30 23:52:25', '2026-01-28 19:43:13', 0.0, 0, 600),
(10, 0, 0, '4', 'Sản phẩm Spotify Premium', 'san-pham-spotify-premium', '', 'assets/storage/images/product_3PES.jpg', 1, '2025-12-22 13:34:02', '2026-01-28 19:43:13', 0.0, 0, 0),
(11, 0, 0, '3', 'Sản phẩm iQIYI Premium', 'san-pham-iqiyi-premium', '', 'assets/storage/images/product_GCXU.jpg', 1, '2025-12-22 13:34:30', '2026-01-28 19:43:13', 0.0, 0, 0),
(12, 0, 0, '5', 'Sản phẩm Grammarly Premium', 'san-pham-grammarly-premium', '', 'assets/storage/images/product_6QYM.jpg', 1, '2025-12-22 13:48:03', '2026-01-28 19:43:13', 0.0, 0, 0),
(13, 0, 0, '5', 'Sản phẩm Quizlet', 'san-pham-quizlet', '', 'assets/storage/images/product_W1LR.jpg', 1, '2025-12-22 13:50:19', '2026-01-28 19:43:13', 0.0, 0, 0),
(14, 0, 0, '5', 'Sản phẩm Datacamp Premium', 'san-pham-datacamp-premium', '', 'assets/storage/images/product_6BN4.jpg', 1, '2025-12-22 13:50:37', '2026-01-28 19:43:13', 0.0, 0, 0),
(15, 0, 0, '5', 'Sản phẩm QuillBot Premium', 'san-pham-quillbot-premium', '', 'assets/storage/images/product_KCHL.jpg', 1, '2025-12-22 13:51:04', '2026-01-28 19:43:13', 0.0, 0, 0),
(16, 0, 0, '14', 'Sản phẩm Microsoft Office', 'san-pham-microsoft-office', '', 'assets/storage/images/product_W2RT.jpg', 1, '2025-12-22 16:46:33', '2026-01-28 19:43:13', 0.0, 0, 0),
(17, 0, 0, '14', 'Sản phẩm Notion', 'san-pham-notion', '', 'assets/storage/images/product_ZUIT.jpg', 1, '2025-12-22 16:47:38', '2026-01-28 19:43:13', 0.0, 0, 0),
(18, 0, 0, '14', 'Sản phẩm CamScanner Premium', 'san-pham-camscanner-premium', '', 'assets/storage/images/product_WGJL.jpg', 1, '2025-12-22 16:49:00', '2026-01-28 19:43:13', 0.0, 0, 0),
(19, 0, 0, '14', 'Sản phẩm LastPass Premium', 'san-pham-lastpass-premium', '', 'assets/storage/images/product_QOU2.jpg', 1, '2025-12-22 16:49:18', '2026-01-28 19:43:13', 0.0, 0, 0),
(20, 0, 0, '9', 'Sản phẩm Freepik', 'san-pham-freepik', '', 'assets/storage/images/product_36JA.jpg', 1, '2025-12-22 23:52:32', '2026-01-28 19:43:13', 0.0, 0, 0),
(30, 0, 0, '5,14,15', 'Sản phẩm ChatGPT Plus', 'san-pham-chatgpt-plus', '', 'assets/storage/images/library/AI/OpenAI/uM4YbwNWj6VeXHwmb7Vzt3yBw84.jpg', 1, '2026-01-28 20:24:26', '2026-01-31 18:47:28', 4.0, 2, 43),
(31, 3, 0, '5,14,9', 'Sản phẩm Figma', 'san-pham-figma', '<p data-pm-slice=\"0 0 []\">Chi tiết sản phẩm</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Figma Edu &ndash; Thiết Kế UI/UX Chuy&ecirc;n Nghiệp, Hợp T&aacute;c Hiệu Quả</p>\r\n\r\n<p>Figma Edu mang đến trải nghiệm thiết kế với đầy đủ t&iacute;nh năng của Figma Pro, hỗ trợ l&agrave;m việc nh&oacute;m, chia sẻ, prototyping v&agrave; ph&aacute;t triển sản phẩm UI/UX to&agrave;n diện. Đ&acirc;y l&agrave; lựa chọn tối ưu cho sinh vi&ecirc;n, nh&agrave; thiết kế, developer v&agrave; nh&oacute;m startup muốn tiết kiệm chi ph&iacute; nhưng vẫn c&oacute; m&ocirc;i trường l&agrave;m việc chuy&ecirc;n nghiệp.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Lợi &iacute;ch khi n&acirc;ng cấp Figma Edu</p>\r\n\r\n<ul>\r\n	<li>Kh&ocirc;ng giới hạn File &amp; Folder &ndash; quản l&yacute; v&agrave; lưu trữ dự &aacute;n thoải m&aacute;i.</li>\r\n	<li>Prototyping n&acirc;ng cao &ndash; dễ d&agrave;ng tạo flow, test user journey trực quan.</li>\r\n	<li>Thư viện Team &ndash; quản l&yacute; Component, Variable, Mode, tăng tốc độ thiết kế.</li>\r\n	<li>C&ocirc;ng cụ b&agrave;n giao File n&acirc;ng cao &ndash; ch&uacute; th&iacute;ch, kiểm tra thuộc t&iacute;nh, hỗ trợ developer tốt hơn.</li>\r\n	<li>3.000 AI Credit/th&aacute;ng &ndash; &aacute;p dụng cho gợi &yacute;, chỉnh sửa th&ocirc;ng minh bằng AI.</li>\r\n</ul>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Truy cập to&agrave;n bộ hệ sinh th&aacute;i Figma Tools:</p>\r\n\r\n<ol>\r\n	<li>Figma Design</li>\r\n	<li>FigJam</li>\r\n	<li>Figma Slide</li>\r\n	<li>Dev Mode</li>\r\n	<li>Figma Site</li>\r\n	<li>Figma Make</li>\r\n</ol>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Ch&iacute;nh s&aacute;ch bảo h&agrave;nh</p>\r\n\r\n<p>Thời gian bảo h&agrave;nh: 12 th&aacute;ng</p>\r\n\r\n<p>C&aacute;ch thức bảo h&agrave;nh:</p>\r\n\r\n<p>Dưới 30 ng&agrave;y: Ho&agrave;n 100% gi&aacute; trị đơn h&agrave;ng.</p>\r\n\r\n<p>Sau 30 ng&agrave;y: Ho&agrave;n tiền theo thời gian chưa sử dụng (VD: d&ugrave;ng 3/12 th&aacute;ng &rarr; ho&agrave;n 75%).</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>Lưu &yacute;: Ritokey kh&ocirc;ng bảo h&agrave;nh dữ liệu lưu trữ tr&ecirc;n t&agrave;i khoản Figma của bạn.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>C&acirc;u hỏi thường gặp</p>\r\n\r\n<p>1. T&ocirc;i c&oacute; thể d&ugrave;ng t&agrave;i khoản c&aacute; nh&acirc;n kh&ocirc;ng?</p>\r\n\r\n<p>C&oacute;. Shop sẽ n&acirc;ng cấp trực tiếp tr&ecirc;n t&agrave;i khoản Figma của bạn (kh&ocirc;ng cấp t&agrave;i khoản mới).</p>\r\n\r\n<p>2. Nếu t&agrave;i khoản t&ocirc;i c&ograve;n hạn Figma Pro th&igrave; c&oacute; mua được kh&ocirc;ng?</p>\r\n\r\n<p>Kh&ocirc;ng. Bạn cần hủy g&oacute;i hiện tại hoặc đợi hết hạn rồi mới mua g&oacute;i Edu từ Shop.</p>\r\n\r\n<p>3. G&oacute;i Edu c&oacute; hỗ trợ Dev Mode kh&ocirc;ng?</p>\r\n\r\n<p>C&oacute;. Dev Mode hoạt động đầy đủ tr&ecirc;n t&agrave;i khoản đ&atilde; đăng k&yacute; g&oacute;i trả ph&iacute;.</p>\r\n\r\n<p>4. T&ocirc;i c&oacute; xem được thời hạn của g&oacute;i kh&ocirc;ng?</p>\r\n\r\n<p>Kh&ocirc;ng. Do đ&acirc;y l&agrave; g&oacute;i Edu, hệ thống kh&ocirc;ng hiển thị thời hạn. Shop sẽ t&iacute;nh thời gian bảo h&agrave;nh dựa tr&ecirc;n ng&agrave;y mua h&agrave;ng.</p>', 'assets/storage/images/product_c465cf16.jpg', 1, '2026-01-31 16:59:50', '2026-01-31 17:00:17', 0.0, 0, 4),
(32, 3, 0, '5', 'Key Adobe Creative Cloud All Apps', 'key-adobe-creative-cloud-all-apps', '<h3><strong>Phần mềm thiết kế Full App 100GB &ndash; Sở hữu trọn bộ ứng dụng Adobe</strong></h3>\r\n\r\n<p><strong>Mua ngay code phần mềm Adobe Full App 100GB để truy cập trọn bộ c&aacute;c c&ocirc;ng cụ thiết kế chuy&ecirc;n nghiệp, gi&uacute;p n&acirc;ng cao năng suất v&agrave; s&aacute;ng tạo cho c&aacute;c dự &aacute;n đồ họa, chỉnh sửa video, thiết kế UX/UI, v&agrave; ph&aacute;t triển web.</strong>&nbsp;Với g&oacute;i ADB Full App, bạn sẽ được sử dụng đầy đủ c&aacute;c phần mềm nổi bật của Adobe, gi&uacute;p bạn dễ d&agrave;ng thực hiện mọi c&ocirc;ng việc từ chỉnh sửa ảnh, thiết kế đồ họa, đến ph&aacute;t triển website v&agrave; video.</p>\r\n\r\n<h3><strong>C&aacute;c phần mềm trong g&oacute;i Adobe Full App 100GB</strong></h3>\r\n\r\n<ol>\r\n	<li><strong>Photoshop (PTS)</strong>: Phần mềm chỉnh sửa ảnh chuy&ecirc;n nghiệp h&agrave;ng đầu thế giới. Photoshop cung cấp c&aacute;c c&ocirc;ng cụ mạnh mẽ cho việc biến đổi h&igrave;nh ảnh, thiết kế đồ họa, v&agrave; vẽ kỹ thuật số. Nếu bạn l&agrave; một nhiếp ảnh gia, designer hay họa sĩ kỹ thuật số, Photoshop sẽ l&agrave; c&ocirc;ng cụ kh&ocirc;ng thể thiếu.</li>\r\n	<li><strong>Illustrator (AI)</strong>: Phần mềm thiết kế đồ họa vector, gi&uacute;p tạo ra c&aacute;c biểu tượng, logo, bản vẽ, kiểu chữ, v&agrave; h&igrave;nh minh họa phức tạp với độ ch&iacute;nh x&aacute;c cực cao. Illustrator l&agrave; c&ocirc;ng cụ l&yacute; tưởng cho những ai l&agrave;m việc trong ng&agrave;nh thiết kế đồ họa, branding v&agrave; s&aacute;ng tạo biểu tượng.</li>\r\n	<li><strong>InDesign (ID)</strong>: C&ocirc;ng cụ thiết kế v&agrave; xuất bản chuy&ecirc;n nghiệp, InDesign l&agrave; lựa chọn l&yacute; tưởng cho việc tạo ra c&aacute;c ấn phẩm in ấn v&agrave; kỹ thuật số, từ s&aacute;ch, tạp ch&iacute;, đến t&agrave;i liệu quảng c&aacute;o. Đ&acirc;y l&agrave; c&ocirc;ng cụ kh&ocirc;ng thể thiếu trong c&ocirc;ng việc thiết kế in ấn.</li>\r\n	<li><strong>XD</strong>: Phần mềm thiết kế trải nghiệm người d&ugrave;ng (UX) v&agrave; giao diện người d&ugrave;ng (UI), gi&uacute;p tạo prototype v&agrave; mockup cho c&aacute;c ứng dụng web v&agrave; di động. XD l&agrave; c&ocirc;ng cụ tuyệt vời cho c&aacute;c nh&agrave; thiết kế web v&agrave; ứng dụng để tạo ra giao diện người d&ugrave;ng mượt m&agrave;, dễ sử dụng.</li>\r\n	<li><strong>Premiere Pro (PR)</strong>: Phần mềm chỉnh sửa video chuy&ecirc;n nghiệp, được sử dụng để bi&ecirc;n tập video, phim v&agrave; c&aacute;c sản phẩm đa phương tiện kh&aacute;c. Với c&aacute;c c&ocirc;ng cụ mạnh mẽ như chỉnh sửa thời gian thực, hiệu ứng video, v&agrave; hỗ trợ nhiều định dạng, Premiere Pro l&agrave; c&ocirc;ng cụ l&yacute; tưởng cho c&aacute;c nh&agrave; sản xuất video.</li>\r\n	<li><strong>Lightroom (LR)</strong>: Phần mềm quản l&yacute; v&agrave; chỉnh sửa ảnh số chuy&ecirc;n nghiệp, tập trung v&agrave;o việc chỉnh m&agrave;u, điều chỉnh &aacute;nh s&aacute;ng v&agrave; tổ chức ảnh. Lightroom l&agrave; sự lựa chọn ho&agrave;n hảo cho c&aacute;c nhiếp ảnh gia khi cần quản l&yacute; số lượng lớn ảnh v&agrave; tạo ra những bức ảnh đẹp mắt.</li>\r\n	<li><strong>Acrobat</strong>: C&ocirc;ng cụ tạo, chỉnh sửa v&agrave; quản l&yacute; t&agrave;i liệu PDF. Adobe Acrobat cung cấp t&iacute;nh năng như k&yacute; điện tử, bảo mật t&agrave;i liệu, v&agrave; cộng t&aacute;c trực tuyến, gi&uacute;p bạn dễ d&agrave;ng tạo v&agrave; chỉnh sửa t&agrave;i liệu PDF cho c&ocirc;ng việc hoặc c&aacute;c dự &aacute;n c&aacute; nh&acirc;n.</li>\r\n	<li><strong>Dreamweaver (DW)</strong>: Phần mềm thiết kế v&agrave; ph&aacute;t triển web, gi&uacute;p tạo ra c&aacute;c trang web v&agrave; ứng dụng web tương th&iacute;ch với nhiều nền tảng v&agrave; thiết bị. Dreamweaver l&agrave; c&ocirc;ng cụ l&yacute; tưởng cho những người ph&aacute;t triển web muốn tạo ra c&aacute;c trang web v&agrave; ứng dụng đẹp mắt, dễ sử dụng.</li>\r\n</ol>\r\n\r\n<p><img alt=\"Tài khoản Adobe Creative Cloud All Apps bản quyền\" src=\"https://taphoammobucket.s3.ap-southeast-1.amazonaws.com/wp-content/uploads/2023/09/18124805/Tai-khoan-Adobe-Creative-Cloud-All-Apps-ban-quyen-co-key.jpg\" /></p>\r\n\r\n<h3>&nbsp;</h3>\r\n\r\n<h3><strong>C&acirc;u hỏi thường gặp (FAQ)</strong></h3>\r\n\r\n<ol>\r\n	<li><strong>Code n&agrave;y c&oacute; thể nhập cho t&agrave;i khoản Adobe Việt Nam kh&ocirc;ng?</strong></li>\r\n	<li><strong>Code n&agrave;y hỗ trợ to&agrave;n cầu v&agrave; c&oacute; thể nhập v&agrave;o t&agrave;i khoản Adobe của bất kỳ quốc gia n&agrave;o.</strong></li>\r\n	<li><strong>Code c&oacute; thể cộng dồn kh&ocirc;ng?</strong></li>\r\n	<li><strong>Code c&oacute; thể cộng dồn tối đa 2 m&atilde;, gi&uacute;p bạn sử dụng sản phẩm trong 2 th&aacute;ng. Sau đ&oacute;, bạn c&oacute; thể mua th&ecirc;m code để gia hạn.</strong></li>\r\n	<li><strong>Nếu t&ocirc;i mua 2 code để n&acirc;ng cấp cho 1 t&agrave;i khoản, thời gian bảo h&agrave;nh sẽ l&agrave; bao l&acirc;u?</strong></li>\r\n	<li><strong>Thời gian bảo h&agrave;nh sẽ t&iacute;nh từ ng&agrave;y thanh to&aacute;n v&agrave; k&eacute;o d&agrave;i theo số code bạn mua. V&iacute; dụ, nếu bạn mua 2 code 1 th&aacute;ng, bảo h&agrave;nh sẽ l&agrave; 2 th&aacute;ng.</strong></li>\r\n	<li><strong>Một t&agrave;i khoản c&oacute; thể nhập bao nhi&ecirc;u code?</strong></li>\r\n	<li><strong>Mỗi t&agrave;i khoản c&oacute; thể nhập tối đa 3 code, nhưng chỉ c&oacute; thể sử dụng tối đa 2 code c&ugrave;ng l&uacute;c.</strong></li>\r\n	<li><strong>Sau khi mua code, t&ocirc;i phải l&agrave;m g&igrave; để sử dụng c&aacute;c phần mềm?</strong></li>\r\n	<li><strong>Bạn chỉ cần l&agrave;m theo hướng dẫn k&iacute;ch hoạt code tr&ecirc;n t&agrave;i khoản Adobe của m&igrave;nh. Xem chi tiết hướng dẫn tại [đ&acirc;y].</strong></li>\r\n	<li><strong>T&ocirc;i c&oacute; thể sử dụng t&agrave;i khoản Adobe tr&ecirc;n bao nhi&ecirc;u thiết bị?</strong></li>\r\n	<li><strong>Bạn c&oacute; thể đăng nhập t&agrave;i khoản Adobe tr&ecirc;n nhiều thiết bị nhưng chỉ c&oacute; thể sử dụng tối đa 2 thiết bị c&ugrave;ng l&uacute;c.</strong></li>\r\n	<li><strong>Code c&oacute; thể đổi sang t&agrave;i khoản kh&aacute;c kh&ocirc;ng?</strong></li>\r\n	<li><strong>Kh&ocirc;ng, mỗi code chỉ hỗ trợ k&iacute;ch hoạt cho 1 t&agrave;i khoản duy nhất.</strong></li>\r\n	<li><strong>L&agrave;m sao để kiểm tra thời gian sử dụng sau khi nhập code?</strong></li>\r\n	<li><strong>Sau khi nhập code, Adobe sẽ gửi th&ocirc;ng b&aacute;o qua email về thời gian sử dụng. Bạn cũng c&oacute; thể kiểm tra trực tiếp tr&ecirc;n trang chủ Adobe.</strong></li>\r\n	<li><strong>Code đ&atilde; nhập nhưng kh&ocirc;ng thể sử dụng tr&ecirc;n ứng dụng Adobe, phải l&agrave;m sao?</strong></li>\r\n	<li><strong>H&atilde;y đăng xuất khỏi t&agrave;i khoản Adobe v&agrave; đăng nhập lại để sử dụng code. Đảm bảo bạn chọn profile personal trong ứng dụng Creative Cloud.</strong></li>\r\n</ol>\r\n\r\n<h3><strong>L&yacute; do chọn Adobe Full App 100GB</strong></h3>\r\n\r\n<p>Với&nbsp;<strong>Phần mềm thiết kế Adobe Full App 100GB</strong>, bạn sẽ c&oacute; tất cả c&aacute;c c&ocirc;ng cụ cần thiết cho c&ocirc;ng việc thiết kế chuy&ecirc;n nghiệp, từ chỉnh sửa ảnh, thiết kế đồ họa, đến chỉnh sửa video v&agrave; ph&aacute;t triển web. G&oacute;i phần mềm n&agrave;y kh&ocirc;ng chỉ mang lại gi&aacute; trị l&acirc;u d&agrave;i m&agrave; c&ograve;n cung cấp c&aacute;c c&ocirc;ng cụ mạnh mẽ, hỗ trợ tối đa sự s&aacute;ng tạo v&agrave; hiệu quả c&ocirc;ng việc của bạn.</p>\r\n\r\n<p><strong>Đặt mua ngay để sở hữu trọn bộ c&ocirc;ng cụ thiết kế mạnh mẽ v&agrave; kh&ocirc;ng giới hạn s&aacute;ng tạo!</strong></p>', 'assets/storage/images/product_1a760e4c.jpg', 1, '2026-01-31 16:59:50', '2026-01-31 17:04:40', 0.0, 0, 6),
(33, 3, 0, '3', 'Netflix Premium Xem phim chất lượng 4k và Full HD', 'netflix-premium-xem-phim-chat-luong-4k-va-full-hd', '', 'assets/storage/images/product_d3de3df4.jpg', 1, '2026-01-31 16:59:50', '2026-01-31 18:47:28', 5.0, 1, 2),
(34, 0, 0, '14,15', 'Super Grok AI', 'super-grok-ai', '', 'assets/storage/images/library/AI/SuperGrok/ZmX1TV8kRkiHSZquoi7egYs0tm4.jpg', 1, '2026-01-31 18:29:54', '2026-01-31 18:29:54', 0.0, 0, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_favorites`
--

CREATE TABLE `product_favorites` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `product_id` int(11) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_fields`
--

CREATE TABLE `product_fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `field_key` varchar(100) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` enum('text','email','password','textarea') NOT NULL DEFAULT 'text',
  `is_required` tinyint(1) DEFAULT 1,
  `placeholder` varchar(255) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_fields`
--

INSERT INTO `product_fields` (`id`, `plan_id`, `field_key`, `label`, `type`, `is_required`, `placeholder`, `sort_order`) VALUES
(1, 2, 'tai_khoan_duolingo', 'Tài khoản Duolingo', 'text', 1, 'Nhập tài khoản Duolingo cần nâng cấp', 0),
(2, 2, 'mat_khau_duolingo', 'Mật khẩu Duolingo', 'password', 1, 'Nhập mật khẩu Duolingo cần nâng cấp', 1),
(3, 4, 'tai_khoan_duolingo', 'Tài khoản Duolingo', 'text', 1, 'Tài khoản Duolingo', 1),
(4, 4, 'mat_khau_duolingo', 'Mật khẩu Duolingo', 'password', 1, 'Mật khẩu Duolingo', 2),
(5, 6, 'email_canva', 'Email Canva', 'email', 1, 'Nhập Email Canva cần nâng cấp', 1),
(6, 11, 'uid_honor_of_kings_global', 'UID HONOR OF KINGS GLOBAL', 'text', 1, 'Nhập UID HONOR OF KINGS GLOBAL', 1),
(15, 323, 'email_dang_nhap', 'Email đăng nhập', 'email', 1, '', 0),
(16, 323, 'mat_khau', 'Mật khẩu', 'password', 1, '', 1),
(17, 326, 'email', 'Email', 'email', 1, '', 0),
(18, 326, 'mat_khau', 'Mặt khẩu', 'password', 1, '', 1),
(19, 329, 'email_dang_nhap', 'Email đăng nhập', 'email', 1, '', 1),
(20, 329, 'mat_khau', 'Mật khẩu', 'password', 1, '', 2),
(21, 304, 'test', 'test', 'text', 1, '', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_orders`
--

CREATE TABLE `product_orders` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(50) NOT NULL COMMENT 'Mã đơn hàng duy nhất',
  `user_id` int(11) NOT NULL COMMENT 'ID người đặt hàng',
  `product_id` int(11) NOT NULL COMMENT 'ID sản phẩm',
  `plan_id` int(11) NOT NULL COMMENT 'ID gói sản phẩm',
  `product_name` varchar(255) DEFAULT NULL COMMENT 'Tên sản phẩm tại thời điểm mua',
  `plan_name` varchar(255) DEFAULT NULL COMMENT 'Tên gói tại thời điểm mua',
  `total_price` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Tổng tiền đơn hàng',
  `coupon_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Số tiền khách hàng thanh toán',
  `commission_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Tỷ lệ hoa hồng (%)',
  `referrer_id` int(11) DEFAULT NULL COMMENT 'ID người nhận hoa hồng',
  `commission_user_id` int(11) DEFAULT NULL,
  `sale_price` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá khuyến mãi (nếu có)',
  `cost_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','completed','cancelled','cancelled_no_refund') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái đơn hàng',
  `payment_status` enum('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `order_source` varchar(20) DEFAULT 'web' COMMENT 'Nguồn đơn hàng: web, api, admin',
  `api_key` varchar(64) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `api_trans_id` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `stock_id` int(11) DEFAULT NULL COMMENT 'ID kho hàng đã bán (nếu là gói giao ngay)',
  `fields_data` text DEFAULT NULL COMMENT 'Dữ liệu các trường tùy chỉnh (JSON)',
  `note` text DEFAULT NULL COMMENT 'Ghi chú nội bộ',
  `delivery_content` text DEFAULT NULL,
  `reason` text DEFAULT NULL COMMENT 'Lý do hủy/hoàn tiền',
  `buyer_ip` varchar(45) DEFAULT NULL COMMENT 'IP người mua',
  `buyer_useragent` varchar(500) DEFAULT NULL COMMENT 'User Agent người mua',
  `is_protected` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Trạng thái bảo vệ đơn hàng tại thời điểm mua',
  `created_at` datetime NOT NULL COMMENT 'Ngày tạo đơn',
  `updated_at` datetime NOT NULL COMMENT 'Ngày cập nhật cuối',
  `completed_at` datetime DEFAULT NULL COMMENT 'Thời điểm đơn hàng được hoàn thành lần đầu',
  `custom_expiry_date` datetime DEFAULT NULL COMMENT 'Ngày hết hạn tùy chỉnh, ghi đè logic tính tự động'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý đơn hàng sản phẩm';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_plans`
--

CREATE TABLE `product_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `image` text DEFAULT NULL,
  `duration_type` enum('day','month','year','lifetime') NOT NULL DEFAULT 'month',
  `duration_value` int(10) UNSIGNED DEFAULT NULL,
  `cost_price` float NOT NULL DEFAULT 0,
  `price` float NOT NULL,
  `sale_price` float NOT NULL DEFAULT 0,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `is_instant` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `api_id` int(11) NOT NULL DEFAULT 0 COMMENT 'ID sản phẩm của API',
  `api_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng kho hàng lấy từ API',
  `api_sync_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_plans`
--

INSERT INTO `product_plans` (`id`, `product_id`, `supplier_id`, `name`, `description`, `image`, `duration_type`, `duration_value`, `cost_price`, `price`, `sale_price`, `sort_order`, `status`, `is_instant`, `created_at`, `updated_at`, `api_id`, `api_stock`, `api_sync_time`) VALUES
(1, 1, 0, 'Nâng Cấp Youtube Premium & YouTube Music 3 Tháng', NULL, NULL, 'month', 3, 0, 179000, 0, 0, 1, 0, '2025-11-28 00:23:25', '2026-01-25 16:25:37', 0, 0, NULL),
(2, 2, 0, 'Duolingo Super - Duolingo Slot Super 1 Năm - Nâng Cấp Chính chủ', '', NULL, 'year', 1, 150000, 150000, 150000, 1, 1, 0, '2025-11-28 13:00:35', '2026-01-25 16:25:37', 2, 0, '2026-01-25 16:18:10'),
(3, 2, 0, 'Duolingo Super - Duolingo Super Admin Family 1 Năm - Nâng Cấp Chính chủ', '', NULL, 'year', 1, 749000, 749000, 0, 2, 1, 0, '2025-11-28 13:25:25', '2026-01-25 16:25:37', 3, 0, '2026-01-25 16:18:10'),
(4, 2, 0, 'Duolingo Super - Tài Khoản Duolingo Super 1 tháng', '', NULL, 'month', 1, 24000, 24000, 0, 3, 1, 0, '2025-11-28 13:26:26', '2026-01-25 16:25:37', 4, 0, '2026-01-25 16:18:10'),
(6, 3, 0, 'Sản phẩm Canva Pro - Canva Edu Member Slot 1 Năm - Nâng Cấp Email chính chủ', '&lt;p&gt;T&amp;iacute;nh năng vẫn ổn chỉ thiếu 1 v&amp;agrave;i t&amp;iacute;nh năng như add font, AI, brandkit&lt;/p&gt;', NULL, 'year', 1, 25000, 25000, 0, 1, 1, 0, '2025-11-28 23:39:54', '2026-01-25 16:25:37', 6, 0, '2026-01-25 16:18:10'),
(9, 8, 0, 'CapCut Pro - CapCut Pro  7 Ngày - Tài Khoản Riêng', '', NULL, 'day', 7, 15000, 15000, 0, 1, 1, 1, '2025-11-30 23:49:00', '2026-01-25 16:25:37', 9, 0, '2026-01-25 16:18:10'),
(10, 8, 0, 'CapCut Pro - CapCut Pro 1 Thiết Bị 6 Tháng - Tài Khoản Riêng Add Team', '', NULL, 'month', 6, 399000, 399000, 0, 2, 1, 0, '2025-11-30 23:49:27', '2026-01-25 16:25:37', 10, 0, '2026-01-25 16:18:10'),
(11, 9, 0, 'Nạp Tokens Honor Of Kings Global Chỉ Cần ID - 16 Tokens Nạp Honor Of Kings Global Chỉ Cần ID', '&lt;p&gt;Kh&amp;aacute;ch h&amp;agrave;ng cung cấp UID, c&amp;aacute;ch lấy ID:&lt;/p&gt;\n\n&lt;ol&gt;\n	&lt;li&gt;Đăng nhập v&amp;agrave;o Game&lt;/li&gt;\n	&lt;li&gt;Bấm v&amp;agrave;o Avatar ở m&amp;agrave;n h&amp;igrave;nh ch&amp;iacute;nh.&lt;/li&gt;\n	&lt;li&gt;Bấm V&amp;agrave;o Setting ở g&amp;oacute;c tr&amp;ecirc;n b&amp;ecirc;n phải, Chọn View UID&lt;/li&gt;\n&lt;/ol&gt;', 'assets/storage/images/plan_TDZM.png', 'lifetime', 1, 6000, 6000, 0, 0, 1, 0, '2025-11-30 23:53:46', '2026-01-25 16:25:37', 11, 0, '2026-01-25 16:18:10'),
(17, 9, 0, 'Nạp Tokens Honor Of Kings Global Chỉ Cần ID - Weekly Card Không Gộp Ngày Nạp Honor Of Kings Global Chỉ Cần ID', '', NULL, 'lifetime', 1, 29000, 29000, 0, 1, 1, 0, '2025-12-01 00:14:49', '2026-01-25 16:25:37', 17, 0, '2026-01-25 16:18:10'),
(296, 0, 0, 'Snapdragon 835 1 ngày', 'Best value, smooth experience | Chip: Snapdragon 835 (S835) | RAM: 6GB | Bộ nhớ: 64GB', NULL, 'lifetime', NULL, 18900, 18900, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 28568, 999, '2026-01-16 12:13:19'),
(297, 0, 0, 'FACEBOOK VIỆT CỔ 1000-5000 BẠN BÈ TẠO 2005-2022', 'FB THẬT BẠN BÈ CHỌN LỌC NHIỀU BÀI ĐĂNG', NULL, 'lifetime', NULL, 390000, 390000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 7, 0, '2026-01-16 12:13:19'),
(298, 0, 0, 'Facebook eu cổ người dùng thật bật hẹn hò nhắn tin market us số lượng lớn mỗi ngày', 'Fb eu cổ người dùng thật nói không với hàng nuôi có sell sll', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 8, 0, '2026-01-16 12:13:19'),
(299, 0, 0, 'TÀI KHOẢN FB CỔ 2007 &amp;gt; 2019 CÓ NÚT THUÊ TÍCH XANH', '✅ TK FB cổ random 2012 &amp;gt; 2019 ✅Checkpoint Mail ✅ Bao đổi tên ✅ Bao đổi ngày tháng năm sinh ✅ Bao có nút thuê tích xanh', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 1, 1, '2025-12-31 20:06:27', '2026-01-29 01:06:09', 9, 0, '2026-01-16 12:13:19'),
(300, 0, 0, 'FaceBook Việt Hẹn Hò ( Datting), 2021, siêu cứng', 'Tài khoản facebook có tính năng hẹn hò, hàng 2021', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 1, 1, '2025-12-31 20:06:27', '2026-01-29 01:06:10', 10, 0, '2026-01-16 12:13:19'),
(301, 0, 0, 'Clone Facebook Việt 100-400 Bạn Bè', '* Sử dụng: Nuôi, Spam , Seeding Autofarmer-Adbreak-Tlc ...', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 11, 0, '2026-01-16 12:13:19'),
(303, 0, 0, 'OUTLOOK US REG NEW LIVE', 'Đã Bật IMAP + POP3\r\nLive trong 24H\r\nReg bằng IP US', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 1774, 0, '2026-01-16 12:13:19'),
(304, 0, 0, 'GMAIL Test', 'Đã Bật IMAP + POP3\r\n Live trong 24H\r\n Reg bằng IP US', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 1920, 0, '2026-01-16 12:13:19'),
(305, 0, 0, 'instagram test', 'test', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 4380, 0, '2026-01-16 12:13:19'),
(306, 0, 0, 'Nick Unrank Sever LA1', 'Nick Unrank Sever LA1', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 11247, 0, '2026-01-16 12:13:19'),
(307, 0, 0, 'Nick Unrank Sever Đài Loan', 'Nick Unrank Sever Đài Loan', NULL, 'lifetime', NULL, 10000, 10000, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 11248, 0, '2026-01-16 12:13:19'),
(308, 0, 0, 'test22', '', NULL, 'lifetime', NULL, 22, 22, 0, 0, 0, 1, '2025-12-31 20:06:27', '2026-01-25 16:25:37', 11249, 0, '2026-01-16 12:13:19'),
(309, 8, 0, 'CapCut Pro - FB Clone Việt 100-200 Bạn bè 🙋👨‍👩‍👧️🎈🍆🌽🥜', 'FB Clone Việt CÓ NHIỀU BÀI ĐĂNG\r\nCó 100-200 Bạn bè\r\nCó 2FA, verify Hotmail\r\nHàng zin chưa qua ADS\r\nGAME - BAO TRÂU', NULL, 'lifetime', NULL, 25, 25, 0, 0, 1, 1, '2025-12-31 21:11:44', '2026-01-25 16:25:37', 309, 0, '2026-01-25 16:18:10'),
(310, 0, 0, '2022 OUTLOOK | LONG LIVE | REFRESH TOKEN', '1. The accounts were registered using high-quality proxies.2. All accounts were verified before issuance (100% live accounts)3. Refresh token included4. Clean accounts!5. Accounts registered in 20226. Format accounts: login:password:mail:mailpassword:refresh token:client id', NULL, 'lifetime', NULL, 10500, 10500, 0, 0, 0, 1, '2026-01-11 20:37:35', '2026-01-25 16:25:37', 28726, 27976, '2026-01-16 12:13:19'),
(311, 0, 0, 'Gmail edu租赁 1-12小时，edu.pl', '', NULL, 'lifetime', NULL, 1000, 1000, 0, 0, 0, 1, '2026-01-16 12:13:19', '2026-01-25 16:25:37', 28727, 10521, '2026-01-16 12:13:19'),
(312, 0, 0, '✅ KEY HMA 3.500 VNĐ ( HẠN DÙNG 15 - 27 NGÀY )  ( DÙNG : 5 THIẾT BỊ ) ( SẢN PHẨM : KEY )(Cập nhật : 14/01/2026)', '✅ KEY HMA 3.500 VNĐ ( HẠN DÙNG 15 - 27 NGÀY )  ( DÙNG : 5 THIẾT BỊ ) ( SẢN PHẨM : KEY )(Cập nhật : 14/01/2026)', NULL, 'lifetime', NULL, 3500, 3500, 0, 0, 0, 1, '2026-01-16 12:13:19', '2026-01-25 16:25:37', 28780, 370, '2026-01-16 12:13:19'),
(321, 30, 0, 'ChatGPT Plus 20$ 1 tháng - dùng chung', '<h3>Lưu &yacute;:</h3>\n\n<p>- Đ&acirc;y l&agrave; t&agrave;i khoản d&ugrave;ng chung, kh&aacute;ch h&agrave;ng sẽ được sử dụng chung t&agrave;i khoản với người kh&aacute;c. Kh&aacute;ch h&agrave;ng sử dụng tối&nbsp;đa tr&ecirc;n&nbsp;<strong>1 thiết bị</strong>&nbsp;c&ugrave;ng l&uacute;c với dạng&nbsp;t&agrave;i khoản d&ugrave;ng chung n&agrave;y.</p>\n\n<p>- T&agrave;i khoản hiện&nbsp;<strong>KH&Ocirc;NG&nbsp;</strong>sử dụng được c&aacute;c t&iacute;nh năng&nbsp;<strong>Ảnh,&nbsp;Upload File,&nbsp;Advanced Voice</strong>. Nếu qu&yacute; kh&aacute;ch muốn d&ugrave;ng tham khảo g&oacute;i n&acirc;ng cấp c&aacute; nh&acirc;n.&nbsp;<strong>Sản phẩm n&agrave;y kh&ocirc;ng đảm bảo&nbsp;số lượt sử dụng t&iacute;nh năng&nbsp;Agent Mode.</strong></p>\n\n<p>- Model GPT-5 mới nhất c&oacute; giới hạn sử dụng, c&oacute; thể sẽ bị hạn chế v&agrave; kh&ocirc;ng truy cập được.</p>\n\n<p>- Kh&aacute;ch h&agrave;ng vui l&ograve;ng&nbsp;<strong>KH&Ocirc;NG&nbsp;</strong>thay đổi th&ocirc;ng tin t&agrave;i khoản,&nbsp;<strong>KH&Ocirc;NG</strong>&nbsp;thay đổi g&oacute;i cước hoặc&nbsp;share t&agrave;i khoản cho người kh&aacute;c sử dụng chung.&nbsp;<strong>Divine Shop c&oacute; quyền từ chối bảo h&agrave;nh với c&aacute;c kh&aacute;ch h&agrave;ng vi phạm ch&iacute;nh s&aacute;ch.</strong></p>\n\n<p>- C&oacute; thể sử dụng t&iacute;nh năng &quot;Đoạn chat tạm thời&quot; để kh&ocirc;ng lưu lại lịch sử chat với người kh&aacute;c.&nbsp;</p>\n\n<p>- Một số h&agrave;nh vi vi phạm ch&iacute;nh s&aacute;ch của GPT c&oacute; thể bao gồm việc sử dụng n&oacute; để sản xuất nội dung vi phạm bản quyền, k&iacute;ch động, ph&acirc;n biệt chủng tộc, quấy rối hoặc x&uacute;c phạm người kh&aacute;c, hoặc sử dụng n&oacute; để thực hiện c&aacute;c hoạt động lừa đảo hoặc gian lận.</p>\n\n<p>- T&agrave;i khoản kh&ocirc;ng hỗ trợ login trang web từ b&ecirc;n thứ 3 (third-party login) v&agrave; API.</p>', 'assets/storage/images/library/AI/OpenAI/ebt8phfQ0HUnJJ7R7dyWUm6vU.jpg', 'month', 1, 150000, 510000, 150000, 1, 1, 0, '2026-01-31 12:49:03', '2026-01-31 12:49:03', 0, 0, NULL),
(322, 30, 0, 'ChatGPT Plus 20$ 1 tháng', '<h3>Lưu &yacute;:</h3>\n\n<p>- G&oacute;i n&acirc;ng cấp ChatGPT Plus 1 th&aacute;ng cho t&agrave;i khoản OpenAI - ChatGPT. K&iacute;ch hoạt&nbsp;<strong>Model GPT-5.&nbsp;Sản phẩm c&oacute;&nbsp;40 lượt d&ugrave;ng t&iacute;nh năng&nbsp;Agent Mode 1 th&aacute;ng.&nbsp;ChatGPT Plus&nbsp;</strong>cung cấp quyền truy cập&nbsp;v&agrave;o Sora, lưu &yacute; khi sử dụng SORA c&oacute; thể tham khảo&nbsp;<strong>tại đ&acirc;y.</strong></p>\n\n<p><strong>-&nbsp;</strong>Qu&yacute; kh&aacute;ch vui l&ograve;ng lấy m&atilde; 2FA&nbsp;theo&nbsp;<strong>hướng dẫn n&agrave;y.</strong></p>\n\n<p>- Kh&aacute;ch h&agrave;ng cung cấp th&ocirc;ng tin t&agrave;i khoản v&agrave; t&agrave;i khoản kh&ocirc;ng c&ograve;n g&oacute;i cước Plus hoặc Pro, Divine Shop sẽ đăng nhập tiến h&agrave;nh n&acirc;ng cấp trong 6h l&agrave;m việc (08:30&nbsp;- 23:00)</p>\n\n<p>- Qu&yacute; kh&aacute;ch vui l&ograve;ng&nbsp;<strong>KH&Ocirc;NG</strong>&nbsp;cung cấp th&ocirc;ng tin&nbsp;<strong>Google/Microsoft/Apple/SĐT</strong>. Kh&aacute;ch h&agrave;ng cần cung cấp t&agrave;i khoản ChatGPT được tạo bằng email v&agrave; mật khẩu để Shop đăng nhập thẳng v&agrave;o xử l&yacute; (kh&ocirc;ng nhận t&agrave;i khoản đăng nhập qua&nbsp;<strong>Google/Microsoft/Apple/SĐT</strong>)</p>\n\n<p>- Hiện tại ChatGPT OpenAI ngo&agrave;i l&atilde;nh thổ Việt Nam sẽ c&oacute; c&aacute;c nước kh&aacute;c được hỗ trợ sử dụng, nếu kh&aacute;ch h&agrave;ng sử dụng t&agrave;i khoản ở c&aacute;c quốc gia kh&ocirc;ng được ph&iacute;a nh&agrave; ph&aacute;t h&agrave;nh hỗ trợ th&igrave; t&agrave;i khoản sẽ bị kh&oacute;a, với trường hợp kh&aacute;ch h&agrave;ng sử dụng t&agrave;i khoản ở quốc gia kh&ocirc;ng được hỗ trợ&nbsp;khiển t&agrave;i khoản bị kh&oacute;a shop sẽ kh&ocirc;ng hỗ trợ bảo h&agrave;nh với g&oacute;i n&acirc;ng cấp. Kh&aacute;ch c&oacute; thể tham khảo c&aacute;c nước được hỗ trợ từ nh&agrave; ph&aacute;t h&agrave;nh tại&nbsp;<strong>Đ&Acirc;Y</strong></p>', 'assets/storage/images/library/AI/OpenAI/UxUsBLeoznLk7lT97U9tJMrUY.jpg', 'month', 1, 560000, 560000, 0, 2, 1, 0, '2026-01-31 12:51:20', '2026-01-31 12:51:20', 0, 0, NULL),
(323, 31, 3, 'Figma Edu 1 năm - Nâng chính chủ', '&lt;h3&gt;Lưu &amp;yacute;:&amp;nbsp;&lt;/h3&gt;\n\n&lt;p&gt;- Qu&amp;yacute; kh&amp;aacute;ch vui l&amp;ograve;ng điền th&amp;ocirc;ng tin t&amp;agrave;i khoản Figma trước khi thanh to&amp;aacute;n. Divine sẽ đăng nhập v&amp;agrave;o t&amp;agrave;i khoản Figma v&amp;agrave; gia hạn trực tiếp tr&amp;ecirc;n t&amp;agrave;i khoản.&amp;nbsp;Đơn h&amp;agrave;ng sẽ được xử l&amp;yacute; trong v&amp;ograve;ng 1h (trong khung giờ l&amp;agrave;m việc 8h30 - 23h).&lt;/p&gt;\n\n&lt;p&gt;- Qu&amp;yacute; kh&amp;aacute;ch vui l&amp;ograve;ng&amp;nbsp;&lt;strong&gt;KH&amp;Ocirc;NG&amp;nbsp;&lt;/strong&gt;cung cấp th&amp;ocirc;ng tin&amp;nbsp;t&amp;agrave;i khoản Google/Facebook. Chỉ cung cấp t&amp;agrave;i khoản đăng nhập trực tiếp qua email v&amp;agrave; pass của Figma.&lt;/p&gt;\n\n&lt;p&gt;- Hạn sử dụng của sản phẩm&amp;nbsp;&lt;strong&gt;KH&amp;Ocirc;NG&amp;nbsp;&lt;/strong&gt;cộng dồn&amp;nbsp;khi mua số lượng nhiều sản phẩm.&lt;/p&gt;\n\n&lt;p&gt;- Q&amp;uacute;y kh&amp;aacute;ch vui l&amp;ograve;ng&amp;nbsp;&lt;strong&gt;KH&amp;Ocirc;NG&amp;nbsp;&lt;/strong&gt;thay đổi email t&amp;agrave;i khoản Figma sau khi n&amp;acirc;ng cấp, Divine kh&amp;ocirc;ng thể bảo h&amp;agrave;nh nếu qu&amp;yacute; kh&amp;aacute;ch thay đổi th&amp;ocirc;ng tin.&lt;/p&gt;\n\n&lt;p&gt;- Sau khi n&amp;acirc;ng cấp xong qu&amp;yacute; kh&amp;aacute;ch&amp;nbsp;&lt;strong&gt;c&amp;oacute; thể thay&amp;nbsp;đổi&lt;/strong&gt;&amp;nbsp;Password&lt;strong&gt;.&lt;/strong&gt;&lt;/p&gt;', NULL, 'year', 1, 349000, 523500, 0, 0, 1, 0, '2026-01-31 16:59:50', '2026-01-31 16:59:50', 18, 999, '2026-01-31 16:59:50'),
(324, 31, 3, 'Figma Edu 1 năm - Tài khoản', '', NULL, 'year', 1, 200000, 300000, 0, 0, 1, 1, '2026-01-31 16:59:50', '2026-01-31 16:59:50', 19, 354, '2026-01-31 16:59:50'),
(325, 32, 3, 'Tài khoản 1 tháng', '', NULL, 'month', 1, 250000, 375000, 0, 0, 1, 1, '2026-01-31 16:59:50', '2026-01-31 16:59:50', 7, 1157, '2026-01-31 16:59:50'),
(326, 32, 3, 'Nâng chính chủ 1 Tháng', '', NULL, 'month', 3, 1100000, 1650000, 0, 0, 1, 0, '2026-01-31 16:59:50', '2026-01-31 16:59:50', 8, 999, '2026-01-31 16:59:50'),
(327, 33, 3, 'Netflix 4K Premium 1 User Riêng 1 Tháng - Tài khoản', '', NULL, 'month', 1, 75000, 112500, 0, 0, 1, 1, '2026-01-31 16:59:50', '2026-01-31 16:59:50', 5, 52, '2026-01-31 16:59:50'),
(328, 30, 0, 'ChatGPT Plus 20$ - Tài khoản dùng riêng', '<h3>Lưu &yacute;:</h3>\n\n<p>- Đ&acirc;y l&agrave; t&agrave;i khoản d&ugrave;ng ri&ecirc;ng, kh&aacute;ch h&agrave;ng sẽ được sử dụng một minh một t&agrave;i khoản.&nbsp;<strong>ChatGPT Plus&nbsp;</strong>cung cấp quyền truy cập&nbsp;v&agrave;o Sora, lưu &yacute; khi sử dụng SORA c&oacute; thể tham khảo&nbsp;<strong>tại đ&acirc;y.</strong></p>\n\n<p>- T&agrave;i khoản sẽ được Divine Shop thu hồi sau khi hết hạn 1 th&aacute;ng.</p>\n\n<p>- Kh&aacute;ch h&agrave;ng vui l&ograve;ng&nbsp;<strong>KH&Ocirc;NG&nbsp;</strong>thay đổi th&ocirc;ng tin thanh to&aacute;n của t&agrave;i khoản.&nbsp;<strong>Divine Shop c&oacute; quyền từ chối bảo h&agrave;nh với c&aacute;c kh&aacute;ch h&agrave;ng vi phạm ch&iacute;nh s&aacute;ch.</strong></p>\n\n<p>- Một số h&agrave;nh vi vi phạm ch&iacute;nh s&aacute;ch của GPT c&oacute; thể bao gồm việc sử dụng n&oacute; để sản xuất nội dung vi phạm bản quyền, k&iacute;ch động, ph&acirc;n biệt chủng tộc, quấy rối hoặc x&uacute;c phạm người kh&aacute;c, hoặc sử dụng n&oacute; để thực hiện c&aacute;c hoạt động lừa đảo hoặc gian lận.</p>\n\n<p>-&nbsp;Hiện tại ChatGPT OpenAI ngo&agrave;i l&atilde;nh thổ Việt Nam sẽ c&oacute; c&aacute;c nước kh&aacute;c được hỗ trợ sử dụng, nếu kh&aacute;ch h&agrave;ng sử dụng t&agrave;i khoản ở c&aacute;c quốc gia kh&ocirc;ng được ph&iacute;a nh&agrave; ph&aacute;t h&agrave;nh hỗ trợ th&igrave; t&agrave;i khoản sẽ bị kh&oacute;a, với trường hợp kh&aacute;ch h&agrave;ng sử dụng t&agrave;i khoản ở quốc gia kh&ocirc;ng được hỗ trợ&nbsp;khiển t&agrave;i khoản bị kh&oacute;a shop sẽ kh&ocirc;ng hỗ trợ bảo h&agrave;nh với g&oacute;i n&acirc;ng cấp. Kh&aacute;ch c&oacute; thể tham khảo c&aacute;c nước được hỗ trợ từ nh&agrave; ph&aacute;t h&agrave;nh tại&nbsp;<strong>Đ&Acirc;Y</strong></p>', 'assets/storage/images/library/AI/OpenAI/Rfe7JVCrrSeZSZ3HHbDhNYGDG0.jpg', 'month', 1, 0, 50000, 0, 3, 1, 1, '2026-01-31 17:02:23', '2026-01-31 17:02:23', 0, 0, NULL),
(329, 34, 0, 'Gói nâng cấp chính chủ', '<h3>Lưu &yacute;:</h3>\n\n<p>- Kh&aacute;ch h&agrave;ng cung cấp th&ocirc;ng tin t&agrave;i khoản v&agrave; t&agrave;i khoản kh&ocirc;ng c&ograve;n g&oacute;i cước SuperGrok, Divine Shop sẽ đăng nhập tiến h&agrave;nh n&acirc;ng cấp trong 6h l&agrave;m việc (08:30&nbsp;- 23:00)</p>\n\n<p>- Qu&yacute; kh&aacute;ch vui l&ograve;ng&nbsp;<strong>KH&Ocirc;NG</strong>&nbsp;cung cấp th&ocirc;ng tin&nbsp;<strong>Google/X/Apple</strong>. Kh&aacute;ch h&agrave;ng cần cung cấp t&agrave;i khoản Grok AI được tạo bằng email v&agrave; mật khẩu để Shop đăng nhập thẳng v&agrave;o xử l&yacute; (kh&ocirc;ng nhận t&agrave;i khoản đăng nhập qua&nbsp;<strong>Google/X/Apple</strong>)</p>', 'assets/storage/images/library/AI/SuperGrok/YL6WnWObXh2tJIwPa3k6aDo0qw.jpg', 'month', 1, 0, 490000, 0, 1, 1, 0, '2026-01-31 18:30:26', '2026-01-31 18:30:26', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL COMMENT 'ID đánh giá',
  `order_id` int(11) NOT NULL COMMENT 'ID đơn hàng (từ product_orders)',
  `user_id` int(11) NOT NULL COMMENT 'ID người đánh giá',
  `product_id` int(11) NOT NULL COMMENT 'ID sản phẩm',
  `plan_id` int(11) NOT NULL COMMENT 'ID gói sản phẩm đã mua',
  `rating` tinyint(1) NOT NULL DEFAULT 5 COMMENT 'Số sao (1-5)',
  `title` varchar(255) DEFAULT NULL COMMENT 'Tiêu đề đánh giá',
  `content` text NOT NULL COMMENT 'Nội dung đánh giá',
  `images` text DEFAULT NULL COMMENT 'Hình ảnh đánh giá (JSON array)',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái: pending=chờ duyệt, approved=đã duyệt, rejected=từ chối',
  `admin_reply` text DEFAULT NULL COMMENT 'Phản hồi của Admin',
  `admin_reply_by` int(11) DEFAULT NULL COMMENT 'ID Admin phản hồi',
  `admin_reply_at` datetime DEFAULT NULL COMMENT 'Thời gian Admin phản hồi',
  `reject_reason` varchar(500) DEFAULT NULL COMMENT 'Lý do từ chối (nếu rejected)',
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Đánh dấu đã mua hàng xác nhận',
  `helpful_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượt thấy hữu ích',
  `created_at` datetime NOT NULL COMMENT 'Thời gian tạo đánh giá',
  `updated_at` datetime NOT NULL COMMENT 'Thời gian cập nhật'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng đánh giá sản phẩm';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL COMMENT 'ID gói sản phẩm (product_plans)',
  `order_id` int(11) DEFAULT NULL,
  `stock_value` text NOT NULL COMMENT 'Giá trị kho hàng (tài khoản, mã kích hoạt, serial...)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0: Đã bán, 1: Còn hàng',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review_helpful_votes`
--

CREATE TABLE `review_helpful_votes` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` mediumtext DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `note`) VALUES
(1, 'status_demo', '0', '1 = Bật tính năng Website Demo'),
(2, 'type_password', 'bcrypt', 'Kiểu mã hóa mật khẩu'),
(3, 'title', 'LumiLTC FAKE', NULL),
(4, 'description', 'Hệ thống bán tài khoản digital, key game tự động', NULL),
(5, 'keywords', '', NULL),
(6, 'author', 'MMO369.COM', NULL),
(7, 'timezone', 'Asia/Ho_Chi_Minh', NULL),
(8, 'email', 'xxx@xxx.xxxx', NULL),
(9, 'status', '1', '0 = Website Bảo trì'),
(10, 'status_update', '1', '1 = Bật cập nhật phiên bản tự động'),
(12, 'session_login', '10000000', NULL),
(13, 'javascript_header', '<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\r\n<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\r\n<link href=\"https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap\" rel=\"stylesheet\">', NULL),
(14, 'javascript_footer', '', NULL),
(16, 'logo_light', 'assets/storage/images/logo_light_OZ4.png', NULL),
(17, 'logo_dark', 'assets/storage/images/logo_dark_EI0.png', NULL),
(18, 'favicon', 'assets/storage/images/favicon_S4B.png', NULL),
(19, 'image', 'assets/storage/images/image_7YK.png', NULL),
(20, 'bg_login', 'assets/storage/images/bg_loginBYI.png', NULL),
(21, 'bg_register', 'assets/storage/images/bg_registerMOU.png', NULL),
(26, 'telegram_token', '', NULL),
(27, 'telegram_chat_id', '', NULL),
(35, 'bank_status', '1', NULL),
(36, 'bank_notice', '<ul>\r\n	<li>Vui l&ograve;ng chuyển khoản đ&uacute;ng số tiền v&agrave; nội dung để được cộng tiền tự động</li>\r\n	<li>Thời gian xử l&yacute; giao dịch c&oacute; thể mất từ 1-5 ph&uacute;t sau khi chuyển khoản th&agrave;nh c&ocirc;ng</li>\r\n	<li>Nếu sau 5 ph&uacute;t vẫn chưa nhận được tiền, vui l&ograve;ng li&ecirc;n hệ hỗ trợ qua Telegram @ntthanhz hoặc Zalo 0947838128</li>\r\n</ul>\r\n', NULL),
(43, 'notice_home', '<p>Hệ thống mmo uy t&iacute;n h&agrave;ng đầu vn</p>\r\n', NULL),
(44, 'font_family', '  font-family: \"Be Vietnam Pro\", sans-serif;\r\n  font-weight: 400;\r\n  font-style: normal;', NULL),
(59, 'popup_status', '1', NULL),
(60, 'popup_noti', '<p>Hệ thống mmo uy t&iacute;n h&agrave;ng đầu vn</p>\r\n', NULL),
(64, 'license_key', 'fd4a485c57bf000d908f70956121d87b', NULL),
(70, 'smtp_host', 'smtp.gmail.com', NULL),
(71, 'smtp_encryption', 'tls', NULL),
(72, 'smtp_port', '587', NULL),
(73, 'smtp_email', '', NULL),
(74, 'smtp_password', '', NULL),
(76, 'default_product_image', 'assets/storage/images/default_product_image3VL.png', NULL),
(77, 'status_captcha', '0', NULL),
(78, 'crypto_note', '<p>Rate 1 USD =&nbsp;26.000đ</p>\r\n', NULL),
(79, 'crypto_address', '', NULL),
(80, 'crypto_token', '', NULL),
(81, 'crypto_min', '1', NULL),
(82, 'crypto_max', '100000', NULL),
(83, 'crypto_status', '1', NULL),
(84, 'crypto_rate', '26000', NULL),
(85, 'reCAPTCHA_site_key', '', NULL),
(86, 'reCAPTCHA_secret_key', '', NULL),
(87, 'reCAPTCHA_status', '0', NULL),
(88, 'telegram_status', '0', NULL),
(89, 'smtp_status', '0', NULL),
(93, 'affiliate_ck', '5', NULL),
(94, 'affiliate_status', '1', NULL),
(95, 'affiliate_min', '10000', NULL),
(96, 'affiliate_banks', 'Vietcombank\r\nMBBank\r\nTechcombank', NULL),
(97, 'affiliate_note', '<p>Chia sẻ&nbsp;li&ecirc;n kết n&agrave;y l&ecirc;n mạng x&atilde; hội hoặc bạn b&egrave; của bạn.</p>\n', NULL),
(98, 'affiliate_chat_id_telegram', '1048444403', NULL),
(99, 'check_time_cron_cron2', '0', NULL),
(100, 'bank_min', '1000', NULL),
(101, 'bank_max', '100000000', NULL),
(102, 'paypal_clientId', '', NULL),
(103, 'paypal_clientSecret', '', NULL),
(104, 'paypal_status', '1', NULL),
(105, 'paypal_rate', '25000', NULL),
(108, 'paypal_note', '', NULL),
(109, 'noti_recharge', '📢 **THÔNG BÁO GIAO DỊCH NẠP TIỀN MỚI**\r\n\r\n🕒 **Thời gian:** {time}  \r\n👤 **Người dùng:** {username}  \r\n💳 **Phương thức thanh toán:** {method}  \r\n💰 **Số tiền nạp:** {amount}  \r\n🔗 **Mã giao dịch:** {trans_id}  \r\n🌐 **Tên miền:** {domain}  \r\n', NULL),
(110, 'noti_action', '', NULL),
(111, 'theme_color', '#405189', NULL),
(112, 'hotline', '09xx.xxx.xxx', NULL),
(113, 'type_notification', 'telegram', NULL),
(114, 'perfectmoney_status', '1', NULL),
(115, 'perfectmoney_account', '', NULL),
(116, 'perfectmoney_pass', '', NULL),
(117, 'perfectmoney_rate', '23000', NULL),
(118, 'perfectmoney_units', '', NULL),
(119, 'perfectmoney_notice', '', NULL),
(120, 'fanpage', 'https://www.facebook.com/xxxx', NULL),
(121, 'address', '1Hd- 50, 010 Avenue, NY 90001 United States', NULL),
(122, 'toyyibpay_status', '1', NULL),
(123, 'toyyibpay_userSecretKey', '', NULL),
(124, 'toyyibpay_categoryCode', '', NULL),
(125, 'toyyibpay_min', '1', NULL),
(126, 'toyyibpay_billChargeToCustomer', '0', NULL),
(127, 'toyyibpay_rate', '5258', NULL),
(128, 'toyyibpay_notice', '', NULL),
(129, 'noti_affiliate_withdraw', '🎉 **Yêu cầu rút hoa hồng mới** 🎉\r\n\r\n🔹 **Tài khoản:** {username}  \r\n🔹 **Số tiền rút:** {amount} VND  \r\n🔹 **Ngân hàng:** {bank}  \r\n🔹 **Số tài khoản:** {account_number}  \r\n🔹 **Tên tài khoản:** {account_name}  \r\n🔹 **Thời gian yêu cầu:** {time}  \r\n🔹 **Địa chỉ IP:** {ip}  \r\n\r\nVui lòng xử lý yêu cầu này trong thời gian sớm nhất! 🚀\r\n\r\n🌐 **Tên miền:** {domain}', NULL),
(130, 'check_time_cron_sending_email', '1768463251', NULL),
(131, 'squadco_status', '1', NULL),
(132, 'squadco_Secret_Key', '', NULL),
(133, 'squadco_Public_Key', '', NULL),
(134, 'squadco_rate', '51', NULL),
(135, 'squadco_currency_code', 'NGN', NULL),
(136, 'squadco_notice', '', NULL),
(137, 'theme_color1', '#0ab39c', NULL),
(141, 'banner_singer', 'assets/storage/images/banner_singer08A.png', NULL),
(142, 'image_empty_state', 'assets/storage/images/image_empty_stateNPV.png', NULL),
(143, 'copyright_footer', ' | Software By <a href=\"https://www.cmsnt.co/\">CMSNT.CO</a>', NULL),
(145, 'crypto_trial', '5', NULL),
(147, 'check_time_cron_bank', '1751732905', NULL),
(148, 'google_analytics_status', '0', NULL),
(149, 'google_analytics_id', '', NULL),
(150, 'card_status', '1', NULL),
(151, 'card_partner_id', '', NULL),
(152, 'card_partner_key', '', NULL),
(153, 'card_ck', '20', NULL),
(154, 'card_notice', '', NULL),
(155, 'api_status', '1', NULL),
(158, 'language_type', 'manual', NULL),
(159, 'gtranslate_script', '<div class=\"gtranslate_wrapper\"></div>\r\n<script>window.gtranslateSettings = {\"default_language\":\"vi\",\"native_language_names\":true,\"languages\":[\"vi\",\"de\",\"ru\",\"ko\",\"th\",\"km\",\"zh-CN\",\"es\",\"ar\",\"en\"],\"wrapper_selector\":\".gtranslate_wrapper\"}</script>\r\n<script src=\"https://cdn.gtranslate.net/widgets/latest/float.js\" defer></script>', NULL),
(161, 'page_contact', '', NULL),
(162, 'page_policy', '', NULL),
(163, 'page_faq', '', NULL),
(164, 'page_block_ip', NULL, NULL),
(165, 'email_temp_content_warning_login', '\r\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #007ea8 0%, #764ba2 100%); padding: 30px; text-align: center;\">\r\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">🔐</div>\r\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">Đăng nhập mới được phát hiện</h2>\r\n    </div>\r\n    \r\n    <!-- Body -->\r\n    <div style=\"padding: 30px;\">\r\n        \r\n            <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Xin chào <strong>{username}</strong>,\r\n            </p>\r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Chúng tôi phát hiện một đăng nhập mới vào tài khoản của bạn.\r\n            </p>\r\n            \r\n            <!-- Login Info -->\r\n            <div style=\"background: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #ffc107;\">\r\n                <p style=\"color: #856404; font-size: 14px; margin: 0 0 10px 0;\"><strong>🕐 Thời gian:</strong> {time}</p>\r\n                <p style=\"color: #856404; font-size: 14px; margin: 0 0 10px 0;\"><strong>🌐 Địa chỉ IP:</strong> {ip}</p>\r\n                <p style=\"color: #856404; font-size: 14px; margin: 0;\"><strong>📱 Thiết bị:</strong> {device}</p>\r\n            </div>\r\n            \r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 20px 0;\">\r\n                Nếu đây là bạn, bạn có thể bỏ qua email này. Nếu không phải, hãy đổi mật khẩu ngay lập tức để bảo vệ tài khoản.\r\n            </p>\r\n            \r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\r\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Email này được gửi tự động để bảo vệ tài khoản của bạn.</p>\r\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 SHOPKEY. All rights reserved.</p>\r\n    </div>\r\n</div>', NULL),
(166, 'email_temp_subject_warning_login', 'Cảnh báo đăng nhập tài khoản - {title}', NULL),
(167, 'email_temp_content_otp_mail', '\r\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #007ea8 0%, #764ba2 100%); padding: 30px; text-align: center;\">\r\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">🔑</div>\r\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">Mã xác thực OTP</h2>\r\n    </div>\r\n    \r\n    <!-- Body -->\r\n    <div style=\"padding: 30px;\">\r\n        \r\n            <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Xin chào <strong>{username}</strong>,\r\n            </p>\r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Đây là mã OTP để xác thực đăng nhập của bạn:\r\n            </p>\r\n            \r\n            <!-- OTP Code -->\r\n            <div style=\"text-align: center; margin: 30px 0;\">\r\n                <div style=\"display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 40px; border-radius: 10px;\">\r\n                    <span style=\"font-size: 32px; font-weight: 700; color: #ffffff; letter-spacing: 8px;\">{otp}</span>\r\n                </div>\r\n            </div>\r\n            \r\n            <div style=\"background: #e8f4fd; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;\">\r\n                <p style=\"color: #1565c0; font-size: 14px; margin: 0;\">⏱️ Mã có hiệu lực trong <strong>5 phút</strong></p>\r\n            </div>\r\n            \r\n            <p style=\"color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;\">\r\n                <strong>IP:</strong> {ip} • <strong>Thiết bị:</strong> {device}\r\n            </p>\r\n            \r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\r\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email.</p>\r\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 SHOPKEY. All rights reserved.</p>\r\n    </div>\r\n</div>', NULL),
(168, 'email_temp_subject_otp_mail', 'OTP xác minh đăng nhập website - {title}', NULL),
(169, 'email_temp_content_forgot_password', '\r\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #007ea8 0%, #764ba2 100%); padding: 30px; text-align: center;\">\r\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">🔓</div>\r\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">Khôi phục mật khẩu</h2>\r\n    </div>\r\n    \r\n    <!-- Body -->\r\n    <div style=\"padding: 30px;\">\r\n        \r\n            <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Xin chào <strong>{username}</strong>,\r\n            </p>\r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Click vào nút bên dưới để tiếp tục:\r\n            </p>\r\n            \r\n            <!-- CTA Button -->\r\n            <div style=\"text-align: center; margin: 30px 0;\">\r\n                <a href=\"{link}\" style=\"display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 15px;\">Đặt lại mật khẩu</a>\r\n            </div>\r\n            \r\n            <div style=\"background: #fff3e0; border-radius: 8px; padding: 15px; margin: 20px 0;\">\r\n                <p style=\"color: #e65100; font-size: 13px; margin: 0;\">⚠️ Link sẽ hết hạn sau <strong>30 phút</strong>. Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>\r\n            </div>\r\n            \r\n            <p style=\"color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;\">\r\n                <strong>Thời gian:</strong> {time}<br>\r\n                <strong>IP:</strong> {ip} • <strong>Thiết bị:</strong> {device}\r\n            </p>\r\n            \r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\r\n        \r\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 SHOPKEY. All rights reserved.</p>\r\n    </div>\r\n</div>', NULL),
(170, 'email_temp_subject_forgot_password', 'Xác nhận khôi phục mật khẩu website - {title}', NULL),
(172, 'time_cron_checklive_clone', '1740738217', NULL),
(173, 'time_cron_checklive_hotmail', '1711615443', NULL),
(178, 'email_temp_subject_buy_order', 'Chi tiết đơn hàng {product} - {title}', NULL),
(181, 'avatar', 'assets/storage/images/avatarVE6.png', NULL),
(182, 'check_time_cron_momo', '1711213245', NULL),
(183, 'momo_number', 'XXX', NULL),
(184, 'momo_name', 'WEB DEMO VUI LÒNG KHÔNG NẠP', NULL),
(185, 'momo_token', '', NULL),
(186, 'momo_notice', '', NULL),
(187, 'momo_status', '1', NULL),
(188, 'script_footer_admin', '', NULL),
(203, 'check_time_cron_cron', '1768573681', NULL),
(204, 'blog_status', '1', NULL),
(207, 'check_time_cron_task', '1741847046', NULL),
(209, 'max_register_ip', '1000', NULL),
(212, 'debug_auto_bank', '0', NULL),
(214, 'debug_api_suppliers', '0', NULL),
(216, 'token_webhook_web2m', '', NULL),
(223, 'widget_zalo1_status', '0', NULL),
(224, 'widget_zalo1_sdt', '0385429660', NULL),
(225, 'widget_phone1_status', '0', NULL),
(226, 'widget_phone1_sdt', '0849999088', NULL),
(227, 'flutterwave_status', '1', NULL),
(228, 'flutterwave_rate', '16', NULL),
(229, 'flutterwave_currency_code', 'NGN', NULL),
(230, 'flutterwave_publicKey', NULL, NULL),
(231, 'flutterwave_secretKey', NULL, NULL),
(232, 'flutterwave_notice', '', NULL),
(233, 'limit_block_ip_login', '20', NULL),
(234, 'limit_block_client_login', '10', NULL),
(235, 'limit_block_ip_api', '20', NULL),
(236, 'limit_block_ip_admin_access', '5', NULL),
(241, 'notice_orders', '', NULL),
(242, 'widget_fbzalo2_status', '0', NULL),
(243, 'widget_fbzalo2_zalo', 'https://zalo.me/0385429660', NULL),
(244, 'widget_fbzalo2_fb', '', NULL),
(248, 'status_only_ip_login_admin', '0', NULL),
(250, 'check_time_cron_thesieure', '0', NULL),
(251, 'thesieure_status', '0', NULL),
(252, 'thesieure_number', '', NULL),
(253, 'thesieure_email', '', NULL),
(254, 'thesieure_token', '', NULL),
(255, 'thesieure_notice', '', NULL),
(256, 'thesieure_name', '', NULL),
(257, 'crypto_type_api', 'fpayment.net', NULL),
(258, 'crypto_merchant_id', '', NULL),
(259, 'crypto_api_key', '', NULL),
(265, 'domains', '127.0.0.1', NULL),
(266, 'isLoginRequiredToViewProduct', '0', NULL),
(267, 'telegram_assistant_status', '0', NULL),
(268, 'telegram_assistant_token', '', NULL),
(269, 'telegram_assistant_list_username', '', NULL),
(270, 'telegram_assistant_secret_token', '95dd86482e6ff43eb90998ef09426518c1e24210dc04f651c96d0a7ae1222402', NULL),
(271, 'telegram_assistant_LicenseKey', '', NULL),
(272, 'status_only_device_client', '0', NULL),
(273, 'status_only_device_admin', '0', NULL),
(275, 'list_network_topup_card', 'VIETTEL|Viettel\r\nVINAPHONE|Vinaphone\r\nMOBIFONE|Mobifone\r\nVNMOBI|Vietnamobile\r\nZING|Zing\r\nVCOIN|Vcoin\r\nGARENA|Garena (chỉ nhận thẻ trên 10k)\r\n', NULL),
(276, 'gateway_xipay_status', '0', NULL),
(277, 'xipay_notice', '', NULL),
(278, 'xipay_min', '1', NULL),
(279, 'xipay_max', '1000000', NULL),
(280, 'gateway_xipay_md5key', '', NULL),
(281, 'gateway_xipay_pid', '', NULL),
(282, 'gateway_xipay_rate', '3508', NULL),
(283, 'gateway_xipay_license', '', NULL),
(284, 'korapay_status', '0', NULL),
(285, 'korapay_secretKey', '', NULL),
(286, 'korapay_min', '1', NULL),
(287, 'korapay_max', '1000000', NULL),
(288, 'korapay_notice', '', NULL),
(289, 'korapay_currency_code', 'NGN', NULL),
(290, 'korapay_rate', '17', NULL),
(291, 'korapay_proxy', '', NULL),
(292, 'korapay_license', '', NULL),
(293, 'tmweasyapi_status', '0', NULL),
(294, 'tmweasyapi_username', '', NULL),
(295, 'tmweasyapi_password', '', NULL),
(296, 'tmweasyapi_con_id', '', NULL),
(297, 'tmweasyapi_license', '', NULL),
(298, 'tmweasyapi_rate', '756', NULL),
(299, 'tmweasyapi_notice', '', NULL),
(300, 'tmweasyapi_min', '1', NULL),
(301, 'tmweasyapi_max', '1000000', NULL),
(302, 'chatgpt_api_key', '', NULL),
(303, 'chatgpt_model', 'gpt-4o-mini', NULL),
(304, 'openpix_status', '0', NULL),
(305, 'openpix_api_key', NULL, NULL),
(306, 'openpix_license', '', NULL),
(307, 'openpix_rate', '4357', NULL),
(308, 'openpix_notice', '', NULL),
(309, 'openpix_min', '1', NULL),
(310, 'openpix_max', '1000000', NULL),
(311, 'openpix_HMAC_key', NULL, NULL),
(312, 'openpix_HMAC_key_completed', '', NULL),
(313, 'limit_block_ip_reset_password', '10', NULL),
(314, 'limit_block_ip_otp', '10', NULL),
(315, 'limit_block_ip_2fa', '10', NULL),
(316, 'task_24h', '1768542704', NULL),
(317, 'limit_block_ip_spam', '10', NULL),
(318, 'limit_block_ip_payment', '10', NULL),
(319, 'bakong_status', '0', NULL),
(320, 'bakong_profile_id', '', NULL),
(321, 'bakong_profile_key', '', NULL),
(322, 'bakong_license', '', NULL),
(323, 'bakong_rate', '25000', NULL),
(324, 'bakong_notice', '', NULL),
(325, 'bakong_min', '1', NULL),
(326, 'bakong_max', '1000000', NULL),
(327, 'icon_hotline', '<i class=\"fa-solid fa-phone\"></i>', NULL),
(328, 'icon_address', '<i class=\"fa-solid fa-location-dot\"></i>', NULL),
(329, 'icon_email', '<i class=\"fa-solid fa-envelope\"></i>', NULL),
(331, 'bank_expired_invoice', '3600', NULL),
(338, 'data-sidebar', 'dark', NULL),
(347, 'data-sidebar-color', '#0f172a', NULL),
(352, 'time_cron_suppliers_SMMPANEL2', '1761894200', NULL),
(353, 'noti_buy_service_manual', '🛒 **Đơn hàng thủ công mới** từ {domain}!\r\n\r\n**Thông tin đơn hàng:**\r\n- **Người dùng:** {username}\r\n- **Mã giao dịch:** {trans_id}\r\n- **Dịch vụ:** {service}\r\n- **Số lượng:** {quantity}\r\n- **Giá thanh toán:** {pay} 💵\r\n- **IP:** {ip}\r\n- **Thời gian đặt:** {time} ⏰\r\n\r\n**Chi tiết khác:**\r\n- **Link:** {link}\r\n- **Ghi chú:** {comment}\r\n\r\n📌 Vui lòng xử lý đơn hàng kịp thời!', NULL),
(354, 'noti_buy_service_api', '', NULL),
(356, 'random_content', 'number', NULL),
(357, 'status_scheduled_orders', '1', NULL),
(359, 'time_cron_suppliers_SMMPANEL2_refil', '1757938189', NULL),
(360, 'support_tickets_status', '1', NULL),
(361, 'status_noti_ticket_to_mail', '1', NULL),
(362, 'email_temp_subject_warning_ticket', 'Ticket mới từ {username}', NULL),
(363, 'email_temp_content_warning_ticket', '<div style=\"background-color:#fafafa; color:#333333; font-family:\'Roboto\',sans-serif; line-height:1.6; padding:20px\">\r\n<div style=\"background-color:#ffffff; border-radius:8px; box-shadow:0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom:0; margin-left:auto; margin-right:auto; margin-top:0; max-width:700px; padding:30px\">\r\n<p>K&iacute;nh gửi Quản trị vi&ecirc;n,</p>\r\n\r\n<p>Bạn c&oacute; một ticket mới được tạo với c&aacute;c th&ocirc;ng tin chi tiết như sau:</p>\r\n\r\n<ul>\r\n	<li><strong>Ti&ecirc;u đề:</strong> {title}</li>\r\n	<li><strong>Người gửi:</strong> {username}</li>\r\n	<li><strong>IP Address:</strong> {ip}</li>\r\n	<li><strong>Thiết bị:</strong> {device}</li>\r\n	<li><strong>Thời gian tạo:</strong> {time}</li>\r\n	<li><strong>Chủ đề:</strong> {subject}</li>\r\n	<li><strong>Danh mục:</strong> {category}</li>\r\n	<li><strong>M&atilde; đơn h&agrave;ng:</strong> {order_id}</li>\r\n	<li><strong>Nội dung:</strong> {content}</li>\r\n</ul>\r\n\r\n<p>Vui l&ograve;ng kiểm tra v&agrave; xử l&yacute; ticket n&agrave;y kịp thời để đảm bảo dịch vụ được duy tr&igrave; li&ecirc;n tục v&agrave; an to&agrave;n.</p>\r\n\r\n<p>Tr&acirc;n trọng,</p>\r\n\r\n<p>Hệ thống Quản l&yacute; Ticket</p>\r\n</div>\r\n</div>\r\n', NULL),
(364, 'email_temp_subject_reply_ticket', 'Trả lời Ticket từ {username}', NULL),
(365, 'email_temp_content_reply_ticket', '<div style=\"background-color:#f5f5f5; font-family:Arial,sans-serif; padding:20px\">\r\n<div style=\"background-color:#ffffff; border-radius:8px; box-shadow:0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom:auto; margin-left:auto; margin-right:auto; margin-top:auto; max-width:600px; padding:20px\">\r\n<h2>Th&ocirc;ng b&aacute;o phản hồi mới từ người d&ugrave;ng</h2>\r\n\r\n<p>Xin ch&agrave;o Quản trị vi&ecirc;n,</p>\r\n\r\n<p>Bạn c&oacute; một phản hồi mới từ người d&ugrave;ng li&ecirc;n quan đến ticket:</p>\r\n\r\n<ul style=\"list-style-type:none\">\r\n	<li><strong>Ti&ecirc;u đề:</strong> {title}</li>\r\n	<li><strong>T&ecirc;n người d&ugrave;ng:</strong> {username}</li>\r\n	<li><strong>IP:</strong> {ip}</li>\r\n	<li><strong>Thiết bị:</strong> {device}</li>\r\n	<li><strong>Thời gian:</strong> {time}</li>\r\n	<li><strong>Chủ đề:</strong> {subject}</li>\r\n	<li><strong>Danh mục:</strong> {category}</li>\r\n	<li><strong>M&atilde; đơn h&agrave;ng:</strong> {order_id}</li>\r\n</ul>\r\n\r\n<p><strong>Nội dung phản hồi:</strong></p>\r\n\r\n<p>{content}</p>\r\n\r\n<p>Tr&acirc;n trọng,</p>\r\n\r\n<p>Đội ngũ hỗ trợ của {domain}</p>\r\n</div>\r\n</div>\r\n', NULL),
(366, 'support_tickets_telegram_chat_id', '', NULL),
(367, 'support_tickets_telegram_message', '📩 Yêu cầu hỗ trợ mới từ {username}', NULL),
(369, 'status_google_login', '0', NULL),
(370, 'google_login_client_id', '', NULL),
(371, 'google_login_client_secret', '', NULL),
(373, 'support_tickets_order_history', '1', NULL),
(374, 'telegram_bot_username', '', NULL),
(375, 'telegram_url', 'https://api.telegram.org/', NULL),
(376, 'telegram_webhook_secret', 'e92e2e75764b28fd05c831f9d1582526', NULL),
(377, 'telegram_noti_login_user', '🎉 Chào mừng bạn, {username}! 🎉\r\n\r\nBạn đã đăng nhập thành công vào hệ thống **{domain}**. \r\n\r\n🕒 **Thời gian:** {time}  \r\n💻 **Thiết bị:** {device}  \r\n🌐 **Địa chỉ IP:** {ip}  \r\n\r\nChúng tôi luôn bảo mật thông tin của bạn và cảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi! Nếu bạn không thực hiện đăng nhập này, hãy liên hệ ngay với chúng tôi. \r\n\r\nChúc bạn có một trải nghiệm tuyệt vời! 😊', NULL),
(378, 'tax_vat', '0', NULL),
(379, 'page_privacy', '', NULL),
(380, 'bank_random_length', '12', NULL),
(382, 'prefix_autobank', '', NULL),
(383, 'path_admin', 'vgprd', NULL),
(384, 'status_show_button_admin_panel', '1', NULL),
(385, 'noti_buy_service_to_user', '🎉 Chúc mừng {username}!\r\n\r\nBạn đã mua thành công dịch vụ **{service}** với thông tin như sau:\r\n\r\n- **Tên miền:** {domain}\r\n- **ID giao dịch:** {trans_id}\r\n- **Số lượng:** {quantity}\r\n- **Giá:** {pay} VNĐ\r\n- **Số dư cũ:** {old_balance} VNĐ\r\n- **Số dư mới:** {new_balance} VNĐ\r\n- **IP giao dịch:** {ip}\r\n- **Thời gian giao dịch:** {time}\r\n\r\n🔗 Bạn có thể theo dõi đơn hàng của mình tại: {link}\r\n\r\nNếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi! Chúng tôi luôn sẵn sàng hỗ trợ bạn. 🤝\r\n\r\nCảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi!', NULL),
(387, 'key_cron_job', '0cb9b14a545ba251', NULL),
(396, 'copyright_footer_left', '© All Copyrights Reserved by <a href=\"https://cmsnt.co\">MMO369.COM</a>', NULL),
(397, 'noti_user_admin_reply_ticket', 'Admin đã reply ticket của bạn. Subject: {subject} - Content: {content} - Status: {status} - Category: {category} - Quantity: {quantity} - IP: {ip} - Time: {time} - Device: {device}', NULL),
(398, 'support_tickets_telegram_message_reply', 'User {username} đã reply ticket của bạn. Subject: {subject} - Content: {content} - Status: {status} - Category: {category} - Quantity: {quantity} - IP: {ip} - Time: {time} - Device: {device} - Order ID: {order_id}', NULL),
(400, 'type_avatar', 'default', NULL),
(402, 'html_banned', '<p>Vui lòng liên hệ với admin để được hỗ trợ</p>', NULL),
(404, 'ai_memory_enabled', '1', NULL),
(405, 'google_ads_status', '0', NULL),
(406, 'google_ads_id', '', NULL),
(407, 'random_transid_order_type', 'number', NULL),
(408, 'random_transid_order_length', '12', NULL),
(409, 'prefix_transid_order', 'MMO369', NULL),
(410, 'debug_mode', '0', NULL),
(411, 'bank_rate', '1', NULL),
(412, 'limit_block_ip_not_whitelist_api', '10', NULL),
(413, 'homepage_img', 'assets/img/homepage-item1.webp', NULL),
(414, 'captcha_status', '0', NULL),
(415, 'captcha_type', 'reCAPTCHA', NULL),
(416, 'captcha_site_key', '', NULL),
(417, 'captcha_secret_key', '', NULL),
(418, 'captcha_modules', 'register,login,forgot_password,withdraw_affiliate,add_invoice_recharge,add_ticket,verify_2fa,verify_otp', NULL),
(419, 'is_show_telegram_reminder', '1', NULL),
(420, 'isValidatePasswordStrength', '0', NULL),
(421, 'isShowServiceMaintenance', '0', NULL),
(422, 'tmweasyapi_watermark_text', '', NULL),
(423, 'tmweasyapi_watermark_color', '#ff0000', NULL),
(424, 'tmweasyapi_watermark_opacity', '0.28', NULL),
(425, 'tmweasyapi_watermark_font_size', '0.08', NULL),
(426, 'order_scheduling_status', '1', NULL),
(427, 'isOrderScheduling', '1', NULL),
(431, 'isCreateCommentByAI', '1', NULL),
(432, 'isAcceptOrderEvenIfAPIError', '0', NULL),
(438, 'notice_top_left', '', NULL),
(439, 'footer_card', '', NULL),
(440, 'isConfirmPolicyRegister', '1', NULL),
(441, 'status_giao_dich_gan_day', '1', NULL),
(442, 'content_gd_mua_gan_day', '<b style=\"color: green;\">...{username}</b> mua <b style=\"color: red;\">{amount}</b> <b>{product_name}</b> với giá <b style=\"color:blue;\">{price}</b>', NULL),
(443, 'content_gd_nap_tien_gan_day', '<b style=\"color: green;\">...{username}</b> thực hiện nạp <b style=\"color:blue;\">{amount}</b> bằng <b style=\"color:red;\">{method}</b> thực nhận <b style=\"color:blue;\">{received}</b>', NULL),
(444, 'crypto_promotions', '', NULL),
(445, 'status_review_product', '1', NULL),
(446, 'affiliate_order_ck', '5', 'Phần trăm hoa hồng từ đơn hàng sản phẩm'),
(447, 'affiliate_signup_bonus', '0', 'Thưởng khi có người đăng ký mới (VND)'),
(448, 'affiliate_order_status', '1', 'Bật/tắt hoa hồng từ đơn hàng (1/0)'),
(449, 'affiliate_recharge_status', '0', 'Bật/tắt hoa hồng từ nạp tiền (1/0)'),
(450, 'affiliate_cookie_days', '30', 'Thời gian lưu cookie affiliate (ngày)'),
(451, 'affiliate_min_commission', '1000', 'Số tiền tối thiểu để tạo hoa hồng (VND)'),
(452, 'noti_affiliate_commission', '🎉 <b>HOA HỒNG MỚI</b>\n\n👤 Website: {domain}\n📌 Loại: {type}\n💰 Số tiền: {amount}\n👥 Từ: {referral_username}\n⏰ Thời gian: {time}', 'Template thông báo hoa hồng affiliate'),
(453, 'isShowSold', '1', 'Hiển thị số lượng đã bán'),
(454, 'email_temp_subject_order_success', '[{title}] Xác nhận đơn hàng thành công #{order_count} đơn', NULL),
(455, 'email_temp_content_order_success', '\r\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #007ea8 0%, #764ba2 100%); padding: 30px; text-align: center;\">\r\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">🎉</div>\r\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">Đơn hàng thành công!</h2>\r\n    </div>\r\n    \r\n    <!-- Body -->\r\n    <div style=\"padding: 30px;\">\r\n        \r\n            <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Xin chào <strong>{username}</strong>,\r\n            </p>\r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Cảm ơn bạn đã mua hàng! Đơn hàng của bạn đã được xử lý thành công.\r\n            </p>\r\n            \r\n            <!-- Order Info Card -->\r\n            <div style=\"background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;\">\r\n                <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>📦 Số lượng đơn:</strong> {order_count}</p>\r\n                <p style=\"color: #333; font-size: 14px; margin: 0;\"><strong>💰 Tổng thanh toán:</strong> <span style=\"color: #e53935; font-weight: 600;\">{total_amount}</span></p>\r\n            </div>\r\n            \r\n            <!-- Order Details -->\r\n            <div style=\"margin: 20px 0;\">\r\n                {order_details}\r\n            </div>\r\n            \r\n            <!-- CTA Button -->\r\n            <div style=\"text-align: center; margin: 30px 0;\">\r\n                <a href=\"{order_link}\" style=\"display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 14px;\">Xem đơn hàng</a>\r\n            </div>\r\n            \r\n            <p style=\"color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;\">\r\n                <strong>Thời gian:</strong> {time}<br>\r\n                <strong>IP:</strong> {ip}\r\n            </p>\r\n            \r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\r\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Cảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi!</p>\r\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 SHOPKEY. All rights reserved.</p>\r\n    </div>\r\n</div>', NULL),
(456, 'check_time_cron_email_queue', '1769537168', 'Thời gian chạy cron email queue'),
(457, 'api_user_enabled', '1', 'Cho phép user sử dụng API'),
(458, 'api_max_keys_per_user', '3', 'Số API key tối đa mỗi user được tạo'),
(459, 'api_user_rate_limit_minute', '60', 'Giới hạn request/phút cho user'),
(460, 'api_user_rate_limit_day', '86400', 'Giới hạn request/ngày cho user'),
(462, 'time_cron_suppliers_shopclone7', '1768540399', NULL),
(463, 'time_cron_suppliers_shopkey', '0', NULL),
(464, 'bank_promotions', '100000|5\r\n1000000|10\r\n10000000|15', 'Mốc khuyến mãi nạp tiền ngân hàng'),
(465, 'card_promotions', '', 'Khuyến mãi nạp tiền Thẻ Cào'),
(466, 'bakong_promotions', '', 'Khuyến mãi nạp tiền Bakong Wallet Cambodia'),
(467, 'xipay_promotions', '', 'Khuyến mãi nạp tiền XiPay'),
(468, 'tmweasyapi_promotions', '', 'Khuyến mãi nạp tiền Tmweasyapi Thailand'),
(469, 'thesieure_promotions', '', 'Khuyến mãi nạp tiền Thesieure'),
(470, 'paypal_promotions', '10|5\r\n100|10', 'Khuyến mãi nạp tiền PayPal'),
(471, 'openpix_promotions', '', 'Khuyến mãi nạp tiền OpenPix Brazil'),
(472, 'korapay_promotions', '', 'Khuyến mãi nạp tiền Korapay'),
(473, 'time_cron_suppliers_SHOPCLONE6', '0', NULL),
(474, 'noti_api_out_of_money', '🚨 **CẢNH BÁO QUAN TRỌNG** 🚨\r\n\r\n📢 **Nhà cung cấp API**: *{supplier_name}*  \r\n🛑 **Sản phẩm**: *{product_name}*  \r\n🔍 **ID sản phẩm**: *{product_id}*  \r\n📅 **Thời gian thông báo**: *{time}*  \r\n💻 **Địa chỉ IP**: *{ip}*  \r\n🆘 **Mã HTTP**: *{http_code}*  \r\n\r\n⚠️ **Tình trạng**: Tài khoản API đã **hết tiền**!  \r\n👉 **Số tiền cần nạp**: *{amount}*  \r\n\r\n\r\n⏰ **Hãy xử lý ngay để tránh gián đoạn dịch vụ!**  \r\n\r\nCảm ơn bạn đã quản lý hệ thống!', 'Thông báo API hết tiền'),
(475, 'noti_api_connection_error', '🚨 *CẢNH BÁO LỖI KẾT NỐI* 🚨\r\n\r\nCó lỗi xảy ra khi kết nối đến API của nhà cung cấp *{supplier_name}*.\r\n\r\n🔹 *Mã lỗi HTTP:* `{http_code}`\r\n🔹 *Tài khoản:* `{username}`\r\n🔹 *Tên sản phẩm:* `{product_name}`\r\n🔹 *ID sản phẩm:* `{product_id}`\r\n🔹 *Kế hoạch:* `{plan_id}`\r\n🔹 *Số tiền:* `{amount}`\r\n🔹 *Địa chỉ IP:* `{ip}`\r\n🔹 *Thời gian:* `{time}`\r\n\r\n🔍 Vui lòng kiểm tra trạng thái của nhà cung cấp *{domain}* để khắc phục sự cố.', 'Thông báo lỗi kết nối API'),
(476, 'check_time_cron_telegram_queue', '1768327800', 'Thời gian chạy cron telegram queue'),
(477, 'noti_order_success_admin', '📦 **Đơn hàng mới từ khách hàng**: {username}  \r\n🌐 **Website**: {domain}  \r\n🕒 **Thời gian đặt hàng**: {time}  \r\n🔢 **Số đơn hàng**: {order_count}  \r\n💰 **Tổng số tiền**: {total_amount} VNĐ  \r\n🎟️ **Mã giảm giá**: {coupon_code} (Giảm: {discount_amount} VNĐ)  \r\n🆔 **ID đơn hàng**: {order_ids}  ', 'Thông báo Telegram đơn hàng mới cho Admin'),
(478, 'noti_order_success_user', '🎉 **Chúc mừng bạn, {username}!** 🎉\r\n\r\nCảm ơn bạn đã mua sắm tại {domain}. Đơn hàng của bạn đã được xác nhận thành công! 🛒\r\n\r\n**Thông tin đơn hàng:**\r\n- **Số lượng sản phẩm:** {order_count}\r\n- **Tổng số tiền:** {total_amount} VNĐ\r\n- **Giảm giá:** {discount_amount} VNĐ (Mã giảm giá: {coupon_code})\r\n- **Mã đơn hàng:** {order_ids}\r\n- **Thời gian đặt hàng:** {time}\r\n\r\n🔄 **Số dư tài khoản còn lại:** {new_balance} VNĐ\r\n\r\n🌐 **Địa chỉ IP:** {ip}\r\n\r\nChúng tôi rất vui khi được phục vụ bạn! Nếu bạn có bất kỳ câu hỏi nào, đừng ngần ngại liên hệ với chúng tôi. \r\n\r\nChúc bạn có những trải nghiệm tuyệt vời cùng sản phẩm! ❤️\r\n\r\n--- \r\nCảm ơn bạn đã chọn {domain}!', 'Thông báo Telegram mua hàng thành công cho User'),
(479, 'noti_new_review', '📢 **Thông báo đánh giá sản phẩm mới** \r\n\r\n🔹 **Tên sản phẩm:** {product_name}  \r\n🔹 **Người đánh giá:** {username}  \r\n🔹 **Số sao:** {stars} ⭐  \r\n🔹 **Tiêu đề đánh giá:** {title}  \r\n🔹 **Nội dung đánh giá:** 📝 {content}  \r\n🔹 **Thời gian đánh giá:** {time}  \r\n\r\nVui lòng xem xét và duyệt đánh giá này!', 'Thông báo Telegram đánh giá mới'),
(480, 'enable_order_expiry_email', '1', 'Bật/tắt email thông báo đơn hàng hết hạn'),
(481, 'email_temp_subject_order_expiring', 'Đơn hàng của bạn sắp hết hạn - {product_name}', 'Subject email sắp hết hạn'),
(482, 'email_temp_subject_order_expired', 'Đơn hàng của bạn đã hết hạn - {product_name}', 'Subject email đã hết hạn'),
(483, 'email_temp_content_order_expiry', '\r\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #007ea8 0%, #764ba2 100%); padding: 30px; text-align: center;\">\r\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">⏰</div>\r\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">{expiry_message}</h2>\r\n    </div>\r\n    \r\n    <!-- Body -->\r\n    <div style=\"padding: 30px;\">\r\n        \r\n            <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Xin chào <strong>{username}</strong>,\r\n            </p>\r\n            \r\n            <!-- Product Info -->\r\n            <div style=\"background: #fce4ec; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #e91e63;\">\r\n                <p style=\"color: #c2185b; font-size: 15px; margin: 0 0 10px 0;\"><strong>📦 Sản phẩm:</strong> {product_name}</p>\r\n                <p style=\"color: #c2185b; font-size: 14px; margin: 0 0 10px 0;\"><strong>📋 Gói:</strong> {plan_name}</p>\r\n                <p style=\"color: #c2185b; font-size: 14px; margin: 0 0 10px 0;\"><strong>🔖 Mã đơn:</strong> {trans_id}</p>\r\n                <p style=\"color: #c2185b; font-size: 14px; margin: 0;\"><strong>📅 Ngày hết hạn:</strong> {expiry_date}</p>\r\n            </div>\r\n            \r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 20px 0;\">\r\n                Để tiếp tục sử dụng dịch vụ mà không bị gián đoạn, vui lòng gia hạn đơn hàng của bạn.\r\n            </p>\r\n            \r\n            <!-- CTA Button -->\r\n            <div style=\"text-align: center; margin: 30px 0;\">\r\n                <a href=\"{domain}\" style=\"display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #e91e63 0%, #9c27b0 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 14px;\">Gia hạn ngay</a>\r\n            </div>\r\n            \r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\r\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>\r\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 SHOPKEY. All rights reserved.</p>\r\n    </div>\r\n</div>', 'Nội dung email thông báo hết hạn'),
(484, 'home_page', 'home', NULL),
(485, 'email_temp_content_flash_sale_favorite', '\r\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #007ea8 0%, #764ba2 100%); padding: 30px; text-align: center;\">\r\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">⚡</div>\r\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">🔥 Flash Sale - Sản phẩm yêu thích!</h2>\r\n    </div>\r\n    \r\n    <!-- Body -->\r\n    <div style=\"padding: 30px;\">\r\n        \r\n            <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Xin chào <strong>{username}</strong>,\r\n            </p>\r\n            <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\r\n                Tin vui! Sản phẩm bạn yêu thích đang có <strong style=\"color: #e53935;\">FLASH SALE</strong>! 🎉\r\n            </p>\r\n            \r\n            <!-- Flash Sale Info -->\r\n            <div style=\"background: linear-gradient(135deg, #ff5722 0%, #e91e63 100%); border-radius: 10px; padding: 25px; margin: 20px 0; text-align: center;\">\r\n                <p style=\"color: #fff; font-size: 18px; font-weight: 600; margin: 0 0 10px 0;\">{flash_sale_name}</p>\r\n                <p style=\"color: #fff; font-size: 20px; font-weight: 700; margin: 0 0 15px 0;\">{product_name}</p>\r\n                <div style=\"background: rgba(255,255,255,0.2); border-radius: 8px; padding: 15px; display: inline-block;\">\r\n                    <span style=\"color: #fff; font-size: 24px; font-weight: 700;\">{discount_info}</span>\r\n                </div>\r\n            </div>\r\n            \r\n            <!-- Time Info -->\r\n            <div style=\"background: #fff8e1; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;\">\r\n                <p style=\"color: #f57c00; font-size: 14px; margin: 0;\">\r\n                    ⏰ <strong>Bắt đầu:</strong> {start_time} | <strong>Kết thúc:</strong> {end_time}\r\n                </p>\r\n            </div>\r\n            \r\n            <!-- CTA Button -->\r\n            <div style=\"text-align: center; margin: 30px 0;\">\r\n                <a href=\"{product_link}\" style=\"display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #ff5722 0%, #e91e63 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 700; font-size: 16px; text-transform: uppercase;\">Mua ngay 🛒</a>\r\n            </div>\r\n            \r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\r\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Số lượng có hạn, nhanh tay kẻo lỡ!</p>\r\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 SHOPKEY. All rights reserved.</p>\r\n    </div>\r\n</div>', 'Nội dung email thông báo Flash Sale yêu thích'),
(486, 'email_temp_subject_flash_sale_favorite', '🔥 {username} ơi! Sản phẩm bạn yêu thích đang có khuyến mãi đặc biệt', 'Tiêu đề email thông báo Flash Sale yêu thích'),
(487, 'is_show_slider', '1', 'Bật/Tắt hiển thị Slider trên trang chủ'),
(488, 'is_show_banner', '1', 'Bật/Tắt hiển thị Banner trên trang chủ'),
(489, 'time_cron_suppliers_shopkey', '1769332238', NULL),
(531, 'policy_register', '', ''),
(533, 'noti_pending_order_admin', '⏳ *ĐƠN HÀNG ORDER MỚI*\n\n👤 *Khách hàng:* {username}\n📦 *Số đơn ORDER:* {order_count}\n💰 *Tổng tiền:* {total_amount}\n📋 *Mã đơn:* {order_ids}\n\n📝 *Chi tiết:*\n{order_details}\n\n⚠️ Vui lòng xử lý đơn hàng!\n🌐 {domain}\n🕐 {time} | 📍 {ip}', 'Thông báo Telegram đơn hàng ORDER cần xử lý cho Admin'),
(534, 'pending_order_telegram_chat_id', '', 'Chat ID Telegram riêng cho đơn hàng ORDER'),
(535, 'email_temp_subject_order_completed', '[{title}] Đơn hàng #{trans_id} đã hoàn thành', 'Tiêu đề email thông báo đơn hàng hoàn thành'),
(536, 'email_temp_content_order_completed', '\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\n    <!-- Header -->\n    <div style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;\">\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">🎉</div>\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">✅ Đơn hàng đã hoàn thành!</h2>\n    </div>\n    \n    <!-- Body -->\n    <div style=\"padding: 30px;\">\n        <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\n            Xin chào <strong>{username}</strong>,\n        </p>\n        <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\n            Đơn hàng <strong>#{trans_id}</strong> của bạn đã được xử lý thành công! 🎉\n        </p>\n        \n        <!-- Order Info Card -->\n        <div style=\"background: linear-gradient(135deg, rgba(102,126,234,0.15) 0%, rgba(118,75,162,0.15) 100%); border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;\">\n            <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>📦 Sản phẩm:</strong> {product_name}</p>\n            <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>📋 Gói:</strong> {plan_name}</p>\n            <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>🔢 Số lượng:</strong> {quantity}</p>\n            <p style=\"color: #333; font-size: 14px; margin: 0;\"><strong>💰 Tổng tiền:</strong> <span style=\"color: #e53935; font-weight: 600;\">{total_amount}</span></p>\n        </div>\n        \n        <!-- Delivery Content -->\n        <div style=\"background: #e8f5e9; border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #4caf50;\">\n            <p style=\"color: #2e7d32; font-size: 14px; font-weight: 600; margin: 0 0 10px 0;\">📄 Thông tin tài khoản:</p>\n            <div style=\"background: #fff; border-radius: 6px; padding: 15px; font-family: monospace; font-size: 13px; color: #333; white-space: pre-wrap; word-break: break-all;\">{delivery_content}</div>\n        </div>\n        \n        <!-- CTA Button -->\n        <div style=\"text-align: center; margin: 30px 0;\">\n            <a href=\"{order_link}\" style=\"display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; font-size: 14px;\">Xem chi tiết đơn hàng</a>\n        </div>\n        \n        <p style=\"color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;\">\n            <strong>Thời gian:</strong> {time}\n        </p>\n    </div>\n    \n    <!-- Footer -->\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© {title}. All rights reserved.</p>\n    </div>\n</div>\n', 'Nội dung email thông báo đơn hàng hoàn thành'),
(537, 'email_temp_subject_ticket_created_user', '[#{ticket_id}] Yêu cầu hỗ trợ của bạn đã được tiếp nhận - {title}', 'Tiêu đề email thông báo tạo ticket cho User'),
(538, 'email_temp_content_ticket_created_user', '\n<div style=\"font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);\">\n    <!-- Header -->\n    <div style=\"background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;\">\n        <div style=\"font-size: 40px; margin-bottom: 10px;\">🎫</div>\n        <h2 style=\"color: #ffffff; font-size: 22px; font-weight: 600; margin: 0;\">Ticket của bạn đã được tiếp nhận</h2>\n    </div>\n    \n    <!-- Body -->\n    <div style=\"padding: 30px;\">\n        <p style=\"color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;\">\n            Xin chào <strong>{username}</strong>,\n        </p>\n        <p style=\"color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0;\">\n            Cảm ơn bạn đã liên hệ với chúng tôi. Yêu cầu hỗ trợ của bạn đã được tiếp nhận và đang được xử lý.\n        </p>\n        \n        <!-- Ticket Info Card -->\n        <div style=\"background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea;\">\n            <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>🎫 Mã ticket:</strong> #{ticket_id}</p>\n            <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>📝 Tiêu đề:</strong> {subject}</p>\n            <p style=\"color: #333; font-size: 14px; margin: 0 0 10px 0;\"><strong>📁 Danh mục:</strong> {category}</p>\n            <p style=\"color: #333; font-size: 14px; margin: 0;\"><strong>📦 Mã đơn hàng:</strong> {order_id}</p>\n        </div>\n        \n        <div style=\"background: #e8f4fd; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;\">\n            <p style=\"color: #1565c0; font-size: 14px; margin: 0;\">⏰ Chúng tôi sẽ phản hồi trong vòng <strong>24 giờ làm việc</strong></p>\n        </div>\n        \n        <p style=\"color: #888; font-size: 13px; margin: 20px 0 0 0; padding-top: 20px; border-top: 1px solid #eee;\">\n            <strong>Thời gian:</strong> {time}\n        </p>\n    </div>\n    \n    <!-- Footer -->\n    <div style=\"background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eee;\">\n        <p style=\"color: #666; font-size: 13px; margin: 0 0 10px 0;\">Cảm ơn bạn đã tin tưởng sử dụng dịch vụ của chúng tôi!</p>\n        <p style=\"color: #888; font-size: 12px; margin: 0;\">© 2026 {title}. All rights reserved.</p>\n    </div>\n</div>', 'Nội dung email thông báo tạo ticket cho User'),
(539, 'is_show_recently_viewed', '1', 'Tùy chọn ON/OFF Widget Hiển thị Sản phẩm đã xem');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `image` varchar(255) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sliders`
--

INSERT INTO `sliders` (`id`, `title`, `image`, `link`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, '', 'assets/storage/images/slider_9D7VBY.png', '', 0, 1, '2025-12-21 14:52:28', '2025-12-23 12:19:48'),
(2, '', 'assets/storage/images/slider_2LXB50.png', '', 1, 1, '2025-12-21 15:02:51', '2025-12-21 15:02:51'),
(3, '', 'assets/storage/images/slider_OBEPUT.png', '', 2, 1, '2025-12-21 18:21:52', '2025-12-21 18:21:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` mediumtext DEFAULT NULL,
  `domain` mediumtext DEFAULT NULL,
  `username` mediumtext DEFAULT NULL,
  `password` mediumtext DEFAULT NULL,
  `api_key` mediumtext DEFAULT NULL,
  `token` mediumtext DEFAULT NULL,
  `coupon` mediumtext DEFAULT NULL,
  `price` mediumtext DEFAULT NULL,
  `discount` float NOT NULL DEFAULT 0,
  `rate` float NOT NULL DEFAULT 0,
  `update_name` mediumtext DEFAULT NULL,
  `sync_category` varchar(55) NOT NULL DEFAULT 'OFF',
  `sync_image` varchar(10) DEFAULT 'ON',
  `update_price` mediumtext DEFAULT NULL,
  `roundMoney` varchar(55) NOT NULL DEFAULT 'ON',
  `status` int(11) NOT NULL DEFAULT 1,
  `create_gettime` datetime NOT NULL,
  `update_gettime` datetime NOT NULL,
  `check_string_api` varchar(55) NOT NULL DEFAULT 'ON',
  `proxy` varchar(55) DEFAULT NULL,
  `isAutoShow` int(11) NOT NULL DEFAULT 0,
  `child` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `support_messages`
--

CREATE TABLE `support_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticket_id` int(11) NOT NULL DEFAULT 0,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `support_quick_replies`
--

CREATE TABLE `support_quick_replies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `command` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `updated_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `order_id` varchar(255) DEFAULT NULL,
  `category` varchar(55) DEFAULT NULL,
  `subject` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `status` varchar(55) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `telegram_logs`
--

CREATE TABLE `telegram_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `command` varchar(100) DEFAULT NULL,
  `params` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `telegram_queue`
--

CREATE TABLE `telegram_queue` (
  `id` int(11) UNSIGNED NOT NULL,
  `chat_id` varchar(50) NOT NULL,
  `token` varchar(255) DEFAULT NULL,
  `message` longtext NOT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT 3,
  `status` enum('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` tinyint(2) UNSIGNED NOT NULL DEFAULT 3,
  `error_message` text DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `last_attempt_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `translate`
--

CREATE TABLE `translate` (
  `id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL DEFAULT 0,
  `name` longtext DEFAULT NULL,
  `value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `admin` int(11) NOT NULL DEFAULT 0,
  `ctv` int(11) NOT NULL DEFAULT 0,
  `banned` int(11) NOT NULL DEFAULT 0,
  `reason_banned` mediumtext DEFAULT NULL,
  `create_date` datetime NOT NULL,
  `update_date` datetime NOT NULL,
  `time_session` int(11) DEFAULT 0,
  `time_request` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `token_2fa` varchar(255) DEFAULT NULL,
  `token_forgot_password` varchar(255) DEFAULT NULL,
  `time_forgot_password` int(11) NOT NULL DEFAULT 0,
  `money` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_money` decimal(20,2) NOT NULL DEFAULT 0.00,
  `debit` decimal(20,2) NOT NULL DEFAULT 0.00,
  `gender` varchar(255) NOT NULL DEFAULT 'Male',
  `device` mediumtext DEFAULT NULL,
  `avatar` mediumtext DEFAULT NULL,
  `status_2fa` int(11) NOT NULL DEFAULT 0,
  `SecretKey_2fa` varchar(255) DEFAULT NULL,
  `limit_2fa` int(11) NOT NULL DEFAULT 0,
  `discount` float NOT NULL DEFAULT 0,
  `trial` int(11) NOT NULL DEFAULT 0,
  `ref_id` int(11) NOT NULL DEFAULT 0,
  `ref_code` varchar(10) DEFAULT NULL,
  `ref_ck` float NOT NULL DEFAULT 0,
  `ref_click` int(11) NOT NULL DEFAULT 0,
  `ref_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ref_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ref_total_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `telegram_chat_id` mediumtext DEFAULT NULL,
  `telegram_username` varchar(55) DEFAULT NULL,
  `telegram_notification` int(11) NOT NULL DEFAULT 1,
  `api_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `status_otp_mail` int(11) NOT NULL DEFAULT 0,
  `otp_mail` varchar(55) DEFAULT NULL,
  `token_otp_mail` varchar(255) DEFAULT NULL,
  `limit_otp_mail` int(11) NOT NULL DEFAULT 0,
  `status_noti_login_to_mail` int(11) NOT NULL DEFAULT 0,
  `status_view_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Đúng trình duyệt và ip mua hàng mới được xem đơn hàng',
  `utm_source` varchar(55) NOT NULL DEFAULT 'web',
  `device_token` varchar(255) DEFAULT NULL,
  `google_id` varchar(191) DEFAULT NULL,
  `google_linked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `fullname`, `phone`, `admin`, `ctv`, `banned`, `reason_banned`, `create_date`, `update_date`, `time_session`, `time_request`, `ip`, `token`, `remember_token`, `token_2fa`, `token_forgot_password`, `time_forgot_password`, `money`, `total_money`, `debit`, `gender`, `device`, `avatar`, `status_2fa`, `SecretKey_2fa`, `limit_2fa`, `discount`, `trial`, `ref_id`, `ref_code`, `ref_ck`, `ref_click`, `ref_amount`, `ref_price`, `ref_total_price`, `telegram_chat_id`, `telegram_username`, `telegram_notification`, `api_key`, `login_attempts`, `status_otp_mail`, `otp_mail`, `token_otp_mail`, `limit_otp_mail`, `status_noti_login_to_mail`, `status_view_order`, `utm_source`, `device_token`, `google_id`, `google_linked_at`) VALUES
(3, 'lumiltc', '$2y$12$pfecUqPWBYPNp8.3aS43mOY7XFcv5.Md2RgXtM6mwbJXCjvy68b0S', 'mymyvsub@gmail.com', NULL, NULL, 99999, 0, 0, NULL, '2026-02-05 23:37:59', '2026-02-05 23:37:59', 1770335591, 0, '127.0.0.1', 'iclumtl99eac6d5f0db6613290c2a7eef34d71dec87473937aeefede0eefce69cdd8eec6bf18832a05e86abdaaa39c759fc8091add7c1e6867c040c992e962f97b57eee', '4b1e872e2f8cb8ed64a5ab6629d4dbbe58a60efc179b98522202253384ef3db1fabcf0192bed4bb31a9d51376512bab48bcf071113a6a2ee465f99f106880593', NULL, NULL, 0, 0.00, 0.00, 0.00, 'Male', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', NULL, 0, 'AE6FP3ERB5HZPDMX', 0, 0, 0, 0, 'VHX36394', 0, 0, 0.00, 0.00, 0.00, NULL, NULL, 1, '6775066e0d75701fba861f82d80860c0348de787058a2d235e3ac8d290cd8ac2', 0, 0, NULL, NULL, 0, 0, 0, 'web', '6849252cd1d94f93d7108f565ee5aaeeeff9d51b1d35d45cd5007bbd23ad234e', NULL, NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_session_token` (`session_token`(64)),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Chỉ mục cho bảng `admin_role`
--
ALTER TABLE `admin_role`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `affiliate_clicks`
--
ALTER TABLE `affiliate_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Chỉ mục cho bảng `affiliate_commissions`
--
ALTER TABLE `affiliate_commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_referral_id` (`referral_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_source` (`type`,`source_id`);

--
-- Chỉ mục cho bảng `affiliate_stats`
--
ALTER TABLE `affiliate_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `aff_log`
--
ALTER TABLE `aff_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `aff_withdraw`
--
ALTER TABLE `aff_withdraw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Chỉ mục cho bảng `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_api_key` (`api_key`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_api_key_created` (`api_key`,`created_at`),
  ADD KEY `idx_api_key_id` (`api_key_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `automations`
--
ALTER TABLE `automations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `position` (`position`),
  ADD KEY `status` (`status`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Chỉ mục cho bảng `block_ip`
--
ALTER TABLE `block_ip`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`(45));

--
-- Chỉ mục cho bảng `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `status` (`status`),
  ADD KEY `is_featured` (`is_featured`),
  ADD KEY `sort_order` (`sort_order`),
  ADD KEY `published_at` (`published_at`);

--
-- Chỉ mục cho bảng `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `status` (`status`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Chỉ mục cho bảng `bot_telegram_logs`
--
ALTER TABLE `bot_telegram_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trans_id` (`trans_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_create_date` (`create_date`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent_status_stt` (`parent_id`,`status`,`stt`),
  ADD KEY `idx_status_stt` (`status`,`stt`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `status` (`status`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `end_date` (`end_date`);

--
-- Chỉ mục cho bảng `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_id` (`coupon_id`),
  ADD KEY `coupon_code` (`coupon_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_trans_id` (`order_trans_id`);

--
-- Chỉ mục cho bảng `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `deposit_log`
--
ALTER TABLE `deposit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_create_time` (`create_time`);

--
-- Chỉ mục cho bảng `dongtien`
--
ALTER TABLE `dongtien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transid` (`transid`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_thoigian` (`user_id`,`thoigian`),
  ADD KEY `idx_transid` (`transid`),
  ADD KEY `idx_thoigian` (`thoigian`);

--
-- Chỉ mục cho bảng `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_to_email` (`to_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_scheduled` (`status`,`scheduled_at`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_to_email` (`to_email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_cleanup` (`status`,`created_at`);

--
-- Chỉ mục cho bảng `email_sending`
--
ALTER TABLE `email_sending`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `failed_attempts`
--
ALTER TABLE `failed_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_type` (`ip_address`,`type`);

--
-- Chỉ mục cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_time` (`status`,`start_time`,`end_time`);

--
-- Chỉ mục cho bảng `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_flash_sale` (`flash_sale_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_plan` (`plan_id`);

--
-- Chỉ mục cho bảng `flash_sale_purchases`
--
ALTER TABLE `flash_sale_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_flash_user` (`flash_sale_id`,`user_id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Chỉ mục cho bảng `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_createdate` (`user_id`,`createdate`),
  ADD KEY `idx_createdate` (`createdate`),
  ADD KEY `idx_ip` (`ip`);

--
-- Chỉ mục cho bảng `log_ref`
--
ALTER TABLE `log_ref`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `order_expiry_notifications`
--
ALTER TABLE `order_expiry_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification` (`order_id`,`notification_type`);

--
-- Chỉ mục cho bảng `order_log`
--
ALTER TABLE `order_log`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `payment_bakong`
--
ALTER TABLE `payment_bakong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_trans_id` (`trans_id`);

--
-- Chỉ mục cho bảng `payment_bank`
--
ALTER TABLE `payment_bank`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `tid` (`tid`);

--
-- Chỉ mục cho bảng `payment_bank_invoice`
--
ALTER TABLE `payment_bank_invoice`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trans_id` (`trans_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `payment_crypto`
--
ALTER TABLE `payment_crypto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `payment_flutterwave`
--
ALTER TABLE `payment_flutterwave`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_tx_ref` (`tx_ref`);

--
-- Chỉ mục cho bảng `payment_korapay`
--
ALTER TABLE `payment_korapay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_trans_id` (`trans_id`);

--
-- Chỉ mục cho bảng `payment_manual`
--
ALTER TABLE `payment_manual`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `payment_momo`
--
ALTER TABLE `payment_momo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tid` (`tid`);

--
-- Chỉ mục cho bảng `payment_openpix`
--
ALTER TABLE `payment_openpix`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_trans_id` (`trans_id`);

--
-- Chỉ mục cho bảng `payment_paypal`
--
ALTER TABLE `payment_paypal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_trans_id` (`trans_id`);

--
-- Chỉ mục cho bảng `payment_pm`
--
ALTER TABLE `payment_pm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Chỉ mục cho bảng `payment_squadco`
--
ALTER TABLE `payment_squadco`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_transaction_ref` (`transaction_ref`);

--
-- Chỉ mục cho bảng `payment_thesieure`
--
ALTER TABLE `payment_thesieure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tid` (`tid`);

--
-- Chỉ mục cho bảng `payment_tmweasyapi`
--
ALTER TABLE `payment_tmweasyapi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Chỉ mục cho bảng `payment_toyyibpay`
--
ALTER TABLE `payment_toyyibpay`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trans_id` (`trans_id`),
  ADD UNIQUE KEY `BillCode` (`BillCode`);

--
-- Chỉ mục cho bảng `payment_xipay`
--
ALTER TABLE `payment_xipay`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_category_status_sort` (`status`,`sort_order`),
  ADD KEY `idx_status` (`status`);

--
-- Chỉ mục cho bảng `product_favorites`
--
ALTER TABLE `product_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Chỉ mục cho bảng `product_fields`
--
ALTER TABLE `product_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`) USING BTREE;

--
-- Chỉ mục cho bảng `product_orders`
--
ALTER TABLE `product_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trans_id` (`trans_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_coupon_code` (`coupon_code`),
  ADD KEY `idx_final_amount` (`final_amount`),
  ADD KEY `idx_commission_user_id` (`commission_user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_referrer_id` (`referrer_id`),
  ADD KEY `idx_buyer_ip` (`buyer_ip`),
  ADD KEY `idx_order_source` (`order_source`),
  ADD KEY `idx_api_key` (`api_key`);

--
-- Chỉ mục cho bảng `product_plans`
--
ALTER TABLE `product_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_status_sort` (`product_id`,`status`,`sort_order`),
  ADD KEY `idx_supplier_id` (`supplier_id`),
  ADD KEY `idx_api_id` (`api_id`);

--
-- Chỉ mục cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_order_id` (`order_id`) COMMENT 'Mỗi đơn hàng chỉ được 1 đánh giá',
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_plan_id` (`plan_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_product_status` (`product_id`,`status`) COMMENT 'Để query nhanh đánh giá đã duyệt của sản phẩm';

--
-- Chỉ mục cho bảng `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Chỉ mục cho bảng `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`user_id`),
  ADD KEY `idx_review_id` (`review_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Chỉ mục cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `support_messages`
--
ALTER TABLE `support_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Chỉ mục cho bảng `support_quick_replies`
--
ALTER TABLE `support_quick_replies`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `telegram_logs`
--
ALTER TABLE `telegram_logs`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `telegram_queue`
--
ALTER TABLE `telegram_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_scheduled` (`status`,`scheduled_at`),
  ADD KEY `idx_priority` (`priority`);

--
-- Chỉ mục cho bảng `translate`
--
ALTER TABLE `translate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lang_id` (`lang_id`),
  ADD KEY `idx_lang_name` (`lang_id`,`name`(191));

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniq_google_id` (`google_id`),
  ADD UNIQUE KEY `idx_ref_code` (`ref_code`),
  ADD KEY `idx_ref_id` (`ref_id`),
  ADD KEY `idx_telegram_chat_id` (`telegram_chat_id`(64)),
  ADD KEY `idx_banned` (`banned`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `active_sessions`
--
ALTER TABLE `active_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `admin_role`
--
ALTER TABLE `admin_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `affiliate_clicks`
--
ALTER TABLE `affiliate_clicks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `affiliate_commissions`
--
ALTER TABLE `affiliate_commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `affiliate_stats`
--
ALTER TABLE `affiliate_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `aff_log`
--
ALTER TABLE `aff_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `aff_withdraw`
--
ALTER TABLE `aff_withdraw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `automations`
--
ALTER TABLE `automations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `banks`
--
ALTER TABLE `banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `block_ip`
--
ALTER TABLE `block_ip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `bot_telegram_logs`
--
ALTER TABLE `bot_telegram_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `coupon_usages`
--
ALTER TABLE `coupon_usages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `deposit_log`
--
ALTER TABLE `deposit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `dongtien`
--
ALTER TABLE `dongtien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `email_campaigns`
--
ALTER TABLE `email_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `email_queue`
--
ALTER TABLE `email_queue`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `email_sending`
--
ALTER TABLE `email_sending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `failed_attempts`
--
ALTER TABLE `failed_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `flash_sales`
--
ALTER TABLE `flash_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `flash_sale_purchases`
--
ALTER TABLE `flash_sale_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `log_ref`
--
ALTER TABLE `log_ref`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_expiry_notifications`
--
ALTER TABLE `order_expiry_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `order_log`
--
ALTER TABLE `order_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_bakong`
--
ALTER TABLE `payment_bakong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_bank`
--
ALTER TABLE `payment_bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_bank_invoice`
--
ALTER TABLE `payment_bank_invoice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_crypto`
--
ALTER TABLE `payment_crypto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_flutterwave`
--
ALTER TABLE `payment_flutterwave`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_korapay`
--
ALTER TABLE `payment_korapay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_manual`
--
ALTER TABLE `payment_manual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_momo`
--
ALTER TABLE `payment_momo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_openpix`
--
ALTER TABLE `payment_openpix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_paypal`
--
ALTER TABLE `payment_paypal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_pm`
--
ALTER TABLE `payment_pm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_squadco`
--
ALTER TABLE `payment_squadco`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_thesieure`
--
ALTER TABLE `payment_thesieure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_tmweasyapi`
--
ALTER TABLE `payment_tmweasyapi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_toyyibpay`
--
ALTER TABLE `payment_toyyibpay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `payment_xipay`
--
ALTER TABLE `payment_xipay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT cho bảng `product_favorites`
--
ALTER TABLE `product_favorites`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_fields`
--
ALTER TABLE `product_fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `product_orders`
--
ALTER TABLE `product_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_plans`
--
ALTER TABLE `product_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=330;

--
-- AUTO_INCREMENT cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID đánh giá';

--
-- AUTO_INCREMENT cho bảng `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=540;

--
-- AUTO_INCREMENT cho bảng `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `support_messages`
--
ALTER TABLE `support_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `support_quick_replies`
--
ALTER TABLE `support_quick_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `telegram_logs`
--
ALTER TABLE `telegram_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `telegram_queue`
--
ALTER TABLE `telegram_queue`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `translate`
--
ALTER TABLE `translate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD CONSTRAINT `active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `aff_withdraw`
--
ALTER TABLE `aff_withdraw`
  ADD CONSTRAINT `aff_withdraw_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `dongtien`
--
ALTER TABLE `dongtien`
  ADD CONSTRAINT `dongtien_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `email_sending`
--
ALTER TABLE `email_sending`
  ADD CONSTRAINT `email_sending_ibfk_1` FOREIGN KEY (`camp_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `email_sending_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `flash_sale_items`
--
ALTER TABLE `flash_sale_items`
  ADD CONSTRAINT `fk_flash_sale_items_flash_sale` FOREIGN KEY (`flash_sale_id`) REFERENCES `flash_sales` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `flash_sale_purchases`
--
ALTER TABLE `flash_sale_purchases`
  ADD CONSTRAINT `fk_flash_sale_purchases_flash_sale` FOREIGN KEY (`flash_sale_id`) REFERENCES `flash_sales` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `payment_bank_invoice`
--
ALTER TABLE `payment_bank_invoice`
  ADD CONSTRAINT `payment_bank_invoice_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `payment_crypto`
--
ALTER TABLE `payment_crypto`
  ADD CONSTRAINT `payment_crypto_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `product_fields`
--
ALTER TABLE `product_fields`
  ADD CONSTRAINT `product_fields_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `product_plans` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `review_helpful_votes`
--
ALTER TABLE `review_helpful_votes`
  ADD CONSTRAINT `fk_review_votes_review` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_votes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `translate`
--
ALTER TABLE `translate`
  ADD CONSTRAINT `translate_ibfk_1` FOREIGN KEY (`lang_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
SET FOREIGN_KEY_CHECKS=1;
