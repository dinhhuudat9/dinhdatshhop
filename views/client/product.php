<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Load user info (nếu cần)
if ($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
} else {
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}


// Lấy slug từ URL
$product_slug = isset($_GET['slug']) ? check_string($_GET['slug']) : '';

if (empty($product_slug)) {
    header('Location: ' . base_url('products'));
    exit();
}

// Lấy thông tin sản phẩm theo slug hoặc ID
if (is_numeric($product_slug)) {
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1", [$product_slug]);
} else {
    $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `slug` = ? AND `status` = 1", [$product_slug]);
}

if (!$product) {
    header('Location: ' . base_url('products'));
    exit();
}

// Track via localStorage will be done in footer script

// Lấy thông tin các chuyên mục (hỗ trợ multi-category)
$product_categories = [];
if (!empty($product['category_ids'])) {
    $cat_ids = array_map('intval', explode(',', $product['category_ids']));
    foreach ($cat_ids as $cid) {
        $cat = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$cid]);
        if ($cat) $product_categories[] = $cat;
    }
}
// Primary category (first one) for breadcrumb and related products
$get_category_product = !empty($product_categories) ? $product_categories[0] : null;


// Lấy danh sách gói sản phẩm (plans)
$product_plans = $CMSNT->get_list_safe(
    "SELECT * FROM `product_plans` WHERE `product_id` = ? AND `status` = 1 ORDER BY `sort_order` ASC, `id` ASC",
    [$product['id']]
);

// Lấy thông tin kho hàng cho mỗi gói giao ngay
$plan_stock_counts = [];
if (count($product_plans) > 0) {
    // Lấy stock từ product_stock cho gói local
    $plan_ids = array_column($product_plans, 'id');
    $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));
    $stock_query = "SELECT `plan_id`, COUNT(*) as stock_count FROM `product_stock` WHERE `plan_id` IN ($placeholders) AND `status` = 1 GROUP BY `plan_id`";
    $stock_result = $CMSNT->get_list_safe($stock_query, $plan_ids);

    foreach ($stock_result as $row) {
        $plan_stock_counts[$row['plan_id']] = (int)$row['stock_count'];
    }

    // Với gói API: sử dụng api_stock thay vì product_stock
    foreach ($product_plans as $plan) {
        if (!empty($plan['supplier_id']) && !empty($plan['api_id'])) {
            // Đây là gói API, sử dụng api_stock
            // api_stock chứa số lượng kho hàng thực tế từ API nguồn
            $plan_stock_counts[$plan['id']] = isset($plan['api_stock']) ? (int)$plan['api_stock'] : 0;
        }
    }
}

// Lấy gói mặc định (gói đầu tiên)
$default_plan = !empty($product_plans) ? $product_plans[0] : null;

// Lấy trường tùy chỉnh của gói mặc định
$default_plan_fields = [];
if ($default_plan) {
    $default_plan_fields = $CMSNT->get_list_safe(
        "SELECT * FROM `product_fields` WHERE `plan_id` = ? ORDER BY `sort_order` ASC",
        [$default_plan['id']]
    );
}

// Tính giá min/max để hiển thị range
$min_price = null;
$max_price = null;
foreach ($product_plans as $plan) {
    $final_price = ($plan['sale_price'] > 0 && $plan['sale_price'] < $plan['price']) ? $plan['sale_price'] : $plan['price'];
    if ($min_price === null || $final_price < $min_price) {
        $min_price = $final_price;
    }
    if ($max_price === null || $final_price > $max_price) {
        $max_price = $final_price;
    }
}

// Kiểm tra có gói giao ngay không
$has_instant_plan = false;
foreach ($product_plans as $plan) {
    if (isset($plan['is_instant']) && $plan['is_instant'] == 1) {
        $has_instant_plan = true;
        break;
    }
}

// ========== FLASH SALE ==========
require_once(__DIR__ . '/../../libs/database/flashsale.php');
$FlashSaleHandler = new FlashSaleHandler();

// Lấy Flash Sales active cho tất cả plans
$plan_flash_sales = [];
$active_flash_sale_end_time = null;
if (count($product_plans) > 0) {
    $plan_ids = array_column($product_plans, 'id');
    $plan_flash_sales = $FlashSaleHandler->getActiveFlashSalesForPlans($plan_ids, [$product['id']]);

    // Tìm thời gian kết thúc Flash Sale gần nhất
    foreach ($plan_flash_sales as $fs) {
        if ($active_flash_sale_end_time === null || strtotime($fs['end_time']) < strtotime($active_flash_sale_end_time)) {
            $active_flash_sale_end_time = $fs['end_time'];
        }
    }
}


// Lấy thông tin rating (nếu có trong database, nếu không dùng giá trị mặc định)
$product_rating = isset($product['rating']) && $product['rating'] > 0 ? (float)$product['rating'] : 0;
$product_rating_count = isset($product['rating_count']) && $product['rating_count'] > 0 ? (int)$product['rating_count'] : 0;

// Lấy danh sách đánh giá đã được duyệt (chỉ load 5 đầu tiên)
$reviews_limit = 5;
$approved_reviews = $CMSNT->get_list_safe(
    "SELECT r.*, u.username, u.fullname as user_name, u.avatar,
            p.name as plan_name
     FROM `product_reviews` r
     LEFT JOIN `users` u ON r.user_id = u.id
     LEFT JOIN `product_plans` p ON r.plan_id = p.id
     WHERE r.product_id = ? AND r.status = 'approved'
     ORDER BY r.created_at DESC
     LIMIT ?",
    [$product['id'], $reviews_limit]
);

// Đếm tổng số đánh giá để hiển thị nút Xem thêm
$total_reviews = $CMSNT->get_row_safe(
    "SELECT COUNT(*) as total FROM `product_reviews` WHERE product_id = ? AND status = 'approved'",
    [$product['id']]
)['total'] ?? 0;

// Thống kê đánh giá theo số sao
$rating_stats = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($approved_reviews as $review) {
    $rating_stats[(int)$review['rating']]++;
}

// Lấy danh sách review IDs mà user đã vote
$user_voted_review_ids = [];
if (isset($getUser) && $getUser) {
    $voted_reviews = $CMSNT->get_list_safe(
        "SELECT `review_id` FROM `review_helpful_votes` WHERE `user_id` = ?",
        [$getUser['id']]
    );
    foreach ($voted_reviews as $vr) {
        $user_voted_review_ids[] = (int)$vr['review_id'];
    }
}

// Kiểm tra user có đơn hàng nào đã hoàn thành mà chưa đánh giá không
$user_pending_review_orders = [];
if (isset($getUser) && $getUser) {
    $user_pending_review_orders = $CMSNT->get_list_safe(
        "SELECT po.id, po.trans_id, po.plan_id, po.quantity, po.created_at, pp.name as plan_name
         FROM `product_orders` po
         LEFT JOIN `product_plans` pp ON po.plan_id = pp.id
         WHERE po.user_id = ? 
           AND po.product_id = ? 
           AND po.status = 'completed'
           AND po.id NOT IN (SELECT order_id FROM `product_reviews` WHERE user_id = ?)
         ORDER BY po.created_at DESC",
        [$getUser['id'], $product['id'], $getUser['id']]
    );
}

// Kiểm tra user đã yêu thích sản phẩm chưa (nếu đã đăng nhập)
$is_favorited = false;
if (isset($getUser) && $getUser) {
    $favorite_check = $CMSNT->get_row_safe(
        "SELECT * FROM `product_favorites` WHERE `user_id` = ? AND `product_id` = ?",
        [$getUser['id'], $product['id']]
    );
    $is_favorited = !empty($favorite_check);
}

$page_title = html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8');

$body = [
    'title' => $page_title . ' | ' . $CMSNT->site('title'),
    'desc'  => mb_substr(strip_tags($product['description']), 0, 160),
    'keyword' => $CMSNT->site('keywords')
];

$body['header'] = '
<link rel="stylesheet" href="' . base_url('mod/css/product.css') . '">
<link rel="stylesheet" href="' . base_url('mod/css/product-detail.css?v=2') . '">
';

// Get current currency info for JS
$current_currency_id = getCurrency();
$current_currency = null;
foreach (get_currencies_cached() as $c) {
    if ($c['id'] == $current_currency_id) {
        $current_currency = $c;
        break;
    }
}
if (!$current_currency) {
    foreach (get_currencies_cached() as $c) {
        if ($c['default_currency'] == 1) {
            $current_currency = $c;
            break;
        }
    }
}

$body['footer'] = '
<script>
    var BASE_URL = "' . base_url() . '";
    var PRODUCT_ID = ' . $product['id'] . ';
    var PRODUCT_SLUG = "' . addslashes($product['slug']) . '";
    var DEFAULT_PLAN_ID = ' . ($default_plan ? $default_plan['id'] : 'null') . ';
    function base_url(path) { return BASE_URL + (path || ""); }
    
    // Currency config
    var CURRENCY_CONFIG = {
        symbol_left: "' . htmlspecialchars($current_currency['symbol_left'] ?? '', ENT_QUOTES) . '",
        symbol_right: "' . htmlspecialchars($current_currency['symbol_right'] ?? '', ENT_QUOTES) . '",
        rate: ' . (float)($current_currency['rate'] ?? 1) . ',
        decimal: ' . (int)($current_currency['decimal_currency'] ?? 0) . ',
        seperator: "' . htmlspecialchars($current_currency['seperator'] ?? 'dot', ENT_QUOTES) . '"
    };
    
    // Translation strings
    var TRANSLATIONS = {
        sold: "' . addslashes(__('Đã bán')) . '",
        deliveryInstant: "' . addslashes(__('Giao ngay')) . '",
        deliveryOrder: "' . addslashes(__('Order')) . '",
        inStock: "' . addslashes(__('Còn hàng')) . '",
        outOfStock: "' . addslashes(__('Hết hàng')) . '",
        selectPlan: "' . addslashes(__('Vui lòng chọn gói sản phẩm')) . '",
        loginRequired: "' . addslashes(__('Vui lòng đăng nhập để mua hàng')) . '",
        addToCartSuccess: "' . addslashes(__('Đã thêm vào giỏ hàng')) . '",
        orderSuccess: "' . addslashes(__('Đặt hàng thành công')) . '",
        errorOccurred: "' . addslashes(__('Đã xảy ra lỗi, vui lòng thử lại')) . '",
        quantity: "' . addslashes(__('Số lượng')) . '",
        confirmOrder: "' . addslashes(__('Xác nhận đặt hàng')) . '",
        processing: "' . addslashes(__('Đang xử lý...')) . '",
        favorite: "' . addslashes(__('Yêu thích')) . '",
        unfavorite: "' . addslashes(__('Bỏ yêu thích')) . '",
        addToCart: "' . addslashes(__('Thêm vào giỏ hàng')) . '",
        addedToCart: "' . addslashes(__('Đã thêm vào giỏ hàng')) . '",
        cartUpdated: "' . addslashes(__('Đã cập nhật giỏ hàng')) . '",
        enterCouponCode: "' . addslashes(__('Vui lòng nhập mã giảm giá')) . '",
        couponApplied: "' . addslashes(__('Áp dụng mã giảm giá thành công')) . '",
        couponRemoved: "' . addslashes(__('Đã xóa mã giảm giá')) . '",
        applyingCoupon: "' . addslashes(__('Đang áp dụng...')) . '",
        // Review translations
        reviewSubmitting: "' . addslashes(__('Đang gửi đánh giá...')) . '",
        reviewSubmitSuccess: "' . addslashes(__('Gửi đánh giá thành công! Đánh giá của bạn đang chờ duyệt.')) . '",
        reviewSelectOrder: "' . addslashes(__('Vui lòng chọn đơn hàng để đánh giá')) . '",
        reviewSelectRating: "' . addslashes(__('Vui lòng chọn số sao đánh giá')) . '",
        reviewEnterContent: "' . addslashes(__('Vui lòng nhập nội dung đánh giá')) . '",
        reviewContentTooShort: "' . addslashes(__('Nội dung đánh giá quá ngắn (tối thiểu 5 ký tự)')) . '",
        reviewLoginRequired: "' . addslashes(__('Vui lòng đăng nhập để đánh giá')) . '",
        reviewHelpful: "' . addslashes(__('Hữu ích')) . '",
        reviewReply: "' . addslashes(__('Phản hồi từ Shop')) . '",
        reviewVerifiedPurchase: "' . addslashes(__('Đã mua hàng')) . '",
        helpful: "' . addslashes(__('Hữu ích')) . '",
        unvote: "' . addslashes(__('Bỏ vote')) . '"
    };
    
    // Plan data for JS
    var PRODUCT_PLANS = ' . json_encode(array_map(function ($plan) use ($plan_stock_counts, $plan_flash_sales, $FlashSaleHandler) {
    $is_api_plan = !empty($plan['supplier_id']) && !empty($plan['api_id']);
    // Sản phẩm API cần is_instant = 1 để JS check stock
    $is_instant = $is_api_plan ? 1 : (int)($plan['is_instant'] ?? 0);

    // Kiểm tra Flash Sale
    $flash_price = 0;
    if (isset($plan_flash_sales[$plan['id']])) {
        $flash_price = $FlashSaleHandler->calculateFlashSalePrice($plan, $plan_flash_sales[$plan['id']]);
    }

    return [
        'id' => (int)$plan['id'],
        'name' => html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8'),
        'description' => isset($plan['description']) ? html_entity_decode($plan['description'], ENT_QUOTES, 'UTF-8') : '',
        'price' => (float)$plan['price'],
        'sale_price' => (float)($plan['sale_price'] ?? 0),
        'flash_price' => (float)$flash_price,
        'is_instant' => $is_instant,
        'stock_count' => $plan_stock_counts[$plan['id']] ?? 0,
        'image' => $plan['image'] ?? ''
    ];
}, $product_plans)) . ';
    
    // Pending review orders (đơn hàng chưa đánh giá)
    var PENDING_REVIEW_ORDERS = ' . json_encode(array_map(function ($order) {
    return [
        'id' => (int)$order['id'],
        'trans_id' => $order['trans_id'],
        'plan_id' => (int)$order['plan_id'],
        'plan_name' => html_entity_decode($order['plan_name'] ?? '', ENT_QUOTES, 'UTF-8'),
        'quantity' => (int)$order['quantity'],
        'created_at' => $order['created_at']
    ];
}, $user_pending_review_orders)) . ';
    
    // Scroll to reviews section
    function scrollToReviews(event) {
        event.preventDefault();
        var reviewsSection = document.getElementById("productReviewsSection");
        if (reviewsSection) {
            reviewsSection.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }
</script>
<script src="' . base_url('mod/js/product-detail.js?v=1') . '"></script>
';

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
?>

<!-- Product Hero Section (Breadcrumb + Main Info) -->
<div class="page-header-modern product-hero-header">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern">
            <a href="<?= base_url(); ?>"><i class="fa-solid fa-home"></i> <?= __('Trang chủ'); ?></a>
            <span class="separator">›</span>
            <a href="<?= base_url('products'); ?>"><?= __('Sản phẩm'); ?></a>
            <?php if ($get_category_product): ?>
                <span class="separator">›</span>
                <a href="<?= base_url('category/' . $get_category_product['slug']); ?>">
                    <?= htmlspecialchars(html_entity_decode($get_category_product['name'], ENT_QUOTES, 'UTF-8')); ?>
                </a>
            <?php endif; ?>
            <span class="separator">›</span>
            <span class="current"><?= htmlspecialchars($page_title); ?></span>
        </nav>

        <!-- Product Main Section -->
        <div class="product-hero-main">
            <!-- Product Image -->
            <div class="product-hero-image">
                <div class="product-image-wrapper">
                    <?php if ($product['image'] && file_exists($product['image'])): ?>
                        <img src="<?= base_url($product['image']); ?>" alt="<?= htmlspecialchars($page_title); ?>" id="productMainImage">
                    <?php else: ?>
                        <div class="product-image-placeholder">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-hero-info">
                <?php
                // Nút Chia sẻ kiếm tiền - chỉ hiển thị khi bật affiliate và user đã đăng nhập
                $show_affiliate_share = $CMSNT->site('affiliate_status') == 1 && $CMSNT->site('affiliate_order_status') == 1 && isset($getUser) && $getUser && !empty($getUser['ref_code']);
                $affiliate_order_rate = $CMSNT->site('affiliate_order_ck') ?: 0;
                $affiliate_product_link = isset($getUser) && $getUser ? base_url('product/' . $product['slug'] . '?aff=' . $getUser['ref_code']) : '';
                ?>
                <div class="product-hero-header">
                    <h1 class="product-hero-title"><?= htmlspecialchars($page_title); ?></h1>
                    <div class="product-header-actions">
                        <?php if ($show_affiliate_share && $affiliate_order_rate > 0): ?>
                            <button type="button" class="product-affiliate-share-btn" id="btnAffiliateShare" title="<?= __('Chia sẻ kiếm tiền'); ?>">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                <span class="affiliate-rate-badge-small"><?= $affiliate_order_rate; ?>%</span>
                            </button>
                        <?php endif; ?>
                        <?php if ($CMSNT->site('api_user_enabled') == 1): ?>
                            <button type="button" class="product-api-doc-btn" id="btnApiDoc" title="<?= __('Tài liệu API'); ?>">
                                <i class="fa-solid fa-code"></i>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="product-favorite-btn <?= $is_favorited ? 'active' : ''; ?>"
                            id="productFavoriteBtn"
                            data-product-id="<?= $product['id']; ?>"
                            title="<?= $is_favorited ? __('Bỏ yêu thích') : __('Yêu thích'); ?>">
                            <i class="fa-solid fa-heart"></i>
                        </button>
                    </div>
                </div>

                <!-- Rating & Review -->
                <?php if ($CMSNT->site('status_review_product') == 1): ?>
                    <div class="product-rating-section product-rating-light">
                        <div class="product-rating-stars">
                            <?php
                            $full_stars = floor($product_rating);
                            $has_half_star = ($product_rating - $full_stars) >= 0.5;
                            for ($i = 1; $i <= 5; $i++):
                                if ($i <= $full_stars): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif ($i == $full_stars + 1 && $has_half_star): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                            <?php endif;
                            endfor; ?>
                        </div>
                        <span class="product-rating-value"><?= number_format($product_rating, 1); ?></span>
                        <a href="#productReviewsSection" class="product-rating-count" onclick="scrollToReviews(event)">(<?= number_format($product_rating_count); ?> <?= __('đánh giá'); ?>)</a>
                    </div>
                <?php endif; ?>

                <div class="product-hero-meta">
                    <?php if (!empty($product_categories)): ?>
                        <?php foreach ($product_categories as $pcat): ?>
                            <a href="<?= base_url('category/' . $pcat['slug']); ?>" class="product-meta-item">
                                <?php if (!empty($pcat['icon']) && file_exists($pcat['icon'])): ?>
                                    <img src="<?= base_url($pcat['icon']); ?>" alt="" class="meta-icon-img">
                                <?php else: ?>
                                    <i class="fa-solid fa-folder"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars(html_entity_decode($pcat['name'], ENT_QUOTES, 'UTF-8')); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($CMSNT->site('isShowSold') == 1 && isset($product['sold']) && $product['sold'] > 0): ?>
                        <span class="product-meta-item sold-count">
                            <i class="fa-solid fa-fire-flame-curved"></i>
                            <?= __('Đã bán'); ?> <?= number_format($product['sold']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($product['description'])): ?>
                    <div class="product-hero-short-desc">
                        <?= mb_substr(strip_tags($product['description']), 0, 200); ?>...
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="product-detail-page">
    <div class="container">

        <?php if ($show_affiliate_share && $affiliate_order_rate > 0): ?>
            <!-- Affiliate Share Modal -->
            <div class="affiliate-share-modal" id="affiliateShareModal">
                <div class="affiliate-share-modal-backdrop" onclick="closeAffiliateModal()"></div>
                <div class="affiliate-share-modal-content">
                    <div class="affiliate-share-modal-header">
                        <div class="affiliate-share-modal-icon">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </div>
                        <h3><?= __('Chia sẻ kiếm tiền'); ?></h3>
                        <button type="button" class="affiliate-share-modal-close" onclick="closeAffiliateModal()">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="affiliate-share-modal-body">
                        <!-- Info Section -->
                        <div class="affiliate-info-section">
                            <div class="affiliate-info-icon">
                                <i class="fa-solid fa-gift"></i>
                            </div>
                            <div class="affiliate-info-text">
                                <p><?= __('Nhận ngay'); ?> <strong class="text-success"><?= $affiliate_order_rate; ?>%</strong> <?= __('hoa hồng khi bạn bè mua hàng qua link của bạn!'); ?></p>
                            </div>
                        </div>

                        <!-- Commission Preview -->
                        <div class="affiliate-commission-preview">
                            <h4><i class="fa-solid fa-calculator"></i> <?= __('Hoa hồng dự kiến'); ?></h4>
                            <div class="affiliate-commission-list">
                                <?php foreach ($product_plans as $plan):
                                    $plan_final_price = ($plan['sale_price'] > 0 && $plan['sale_price'] < $plan['price']) ? $plan['sale_price'] : $plan['price'];
                                    $estimated_commission = $plan_final_price * $affiliate_order_rate / 100;
                                ?>
                                    <div class="affiliate-commission-item">
                                        <span class="commission-plan-name"><?= htmlspecialchars($plan['name']); ?></span>
                                        <div class="commission-values">
                                            <span class="commission-price"><?= format_currency($plan_final_price); ?></span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                            <span class="commission-amount">+<?= format_currency($estimated_commission); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Affiliate Link -->
                        <div class="affiliate-link-section">
                            <label><i class="fa-solid fa-link"></i> <?= __('Link giới thiệu của bạn'); ?></label>
                            <div class="affiliate-link-box">
                                <input type="text" readonly id="affiliateProductLink" value="<?= $affiliate_product_link; ?>">
                                <button type="button" class="btn-copy-affiliate" onclick="copyAffiliateLink()" title="<?= __('Sao chép'); ?>">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                            <span class="affiliate-link-hint"><?= __('Chia sẻ link này để nhận hoa hồng'); ?></span>
                        </div>

                        <!-- Social Share Buttons -->
                        <div class="affiliate-social-share">
                            <label><?= __('Chia sẻ nhanh'); ?></label>
                            <div class="affiliate-social-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($affiliate_product_link); ?>" target="_blank" class="social-btn facebook" title="Facebook">
                                    <i class="fa-brands fa-facebook-f"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($affiliate_product_link); ?>&text=<?= urlencode($page_title); ?>" target="_blank" class="social-btn twitter" title="Twitter">
                                    <i class="fa-brands fa-x-twitter"></i>
                                </a>
                                <a href="https://t.me/share/url?url=<?= urlencode($affiliate_product_link); ?>&text=<?= urlencode($page_title); ?>" target="_blank" class="social-btn telegram" title="Telegram">
                                    <i class="fa-brands fa-telegram"></i>
                                </a>
                                <a href="https://wa.me/?text=<?= urlencode($page_title . ' - ' . $affiliate_product_link); ?>" target="_blank" class="social-btn whatsapp" title="WhatsApp">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                                <a href="https://www.messenger.com/t/?link=<?= urlencode($affiliate_product_link); ?>" target="_blank" class="social-btn messenger" title="Messenger">
                                    <i class="fa-brands fa-facebook-messenger"></i>
                                </a>
                                <a href="javascript:void(0)" onclick="shareViaZalo()" class="social-btn zalo" title="Zalo">
                                    <span class="zalo-icon">Z</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="affiliate-share-modal-footer">
                        <a href="<?= base_url('?action=affiliates'); ?>" class="btn-view-affiliate">
                            <i class="fa-solid fa-chart-line"></i>
                            <?= __('Xem thống kê hoa hồng'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($CMSNT->site('api_user_enabled') == 1):
            $apiBaseUrl = rtrim(base_url(), '/') . '/api/v1';
            $demoApiKey = 'sk_live_your_api_key_here';
            $demoApiSecret = 'sk_secret_your_api_secret_here';
        ?>
            <!-- API Documentation Modal -->
            <div class="api-doc-modal" id="apiDocModal">
                <div class="api-doc-modal-backdrop" onclick="closeApiDocModal()"></div>
                <div class="api-doc-modal-content">
                    <div class="api-doc-modal-header">
                        <div class="api-doc-modal-icon">
                            <i class="fa-solid fa-code"></i>
                        </div>
                        <h3><?= __('Tài liệu API sản phẩm'); ?></h3>
                        <button type="button" class="api-doc-modal-close" onclick="closeApiDocModal()">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="api-doc-modal-body">
                        <!-- Tabs -->
                        <div class="api-doc-tabs">
                            <button type="button" class="api-doc-tab active" data-tab="product-info"><?= __('Chi tiết sản phẩm'); ?></button>
                            <button type="button" class="api-doc-tab" data-tab="create-order"><?= __('Mua sản phẩm'); ?></button>
                            <button type="button" class="api-doc-tab" data-tab="order-status"><?= __('Xem đơn hàng'); ?></button>
                        </div>

                        <!-- Tab Contents -->
                        <div class="api-doc-tab-contents">
                            <!-- Product Info Tab -->
                            <div class="api-doc-tab-content active" id="tab-product-info">
                                <div class="api-endpoint-info">
                                    <span class="api-method get">GET</span>
                                    <code><?= $apiBaseUrl; ?>/products/list?search=<?= urlencode($product['slug']); ?></code>
                                </div>

                                <h5><i class="fa-solid fa-arrow-right"></i> <?= __('Response chứa'); ?>:</h5>
                                <ul class="api-response-list">
                                    <li><code>id</code> - ID sản phẩm</li>
                                    <li><code>name</code> - Tên sản phẩm</li>
                                    <li><code>plans[]</code> - Danh sách gói: <code>id</code>, <code>name</code>, <code>price</code>, <code>sale_price</code>, <code>fields[]</code></li>
                                </ul>

                                <h5><i class="fa-solid fa-info-circle"></i> <?= __('Thông tin sản phẩm này'); ?>:</h5>
                                <div class="api-product-info-box">
                                    <div class="api-info-row">
                                        <span class="api-info-label">Product ID:</span>
                                        <code><?= $product['id']; ?></code>
                                    </div>
                                    <div class="api-info-row">
                                        <span class="api-info-label">Slug:</span>
                                        <code><?= $product['slug']; ?></code>
                                    </div>
                                    <?php if (!empty($product_plans)): ?>
                                        <div class="api-info-row">
                                            <span class="api-info-label">Plans:</span>
                                            <div class="api-plans-list">
                                                <?php foreach ($product_plans as $plan): ?>
                                                    <div class="api-plan-item">
                                                        <code>ID: <?= $plan['id']; ?></code> - <?= htmlspecialchars($plan['name']); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h5><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?>:</h5>
                                <div class="api-code-block">
                                    <pre>curl "<?= $apiBaseUrl; ?>/products/list" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                                </div>
                            </div>

                            <!-- Create Order Tab -->
                            <div class="api-doc-tab-content" id="tab-create-order">
                                <div class="api-endpoint-info">
                                    <span class="api-method post">POST</span>
                                    <code><?= $apiBaseUrl; ?>/orders/create</code>
                                </div>

                                <h5><i class="fa-solid fa-arrow-right"></i> <?= __('Request Body'); ?>:</h5>
                                <div class="api-code-block">
                                    <pre>{
  "items": [
    {
      "plan_id": <?= $default_plan ? $default_plan['id'] : 'PLAN_ID'; ?>,
      "quantity": 1,
      "fields": {
        "field_key": "field_value"
      }
    }
  ],
  "coupon_code": ""
}</pre>
                                </div>

                                <h5><i class="fa-solid fa-list"></i> <?= __('Các gói có thể mua'); ?>:</h5>
                                <table class="api-plans-table">
                                    <thead>
                                        <tr>
                                            <th>Plan ID</th>
                                            <th><?= __('Tên gói'); ?></th>
                                            <th><?= __('Giá'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($product_plans as $plan):
                                            $final = ($plan['sale_price'] > 0 && $plan['sale_price'] < $plan['price']) ? $plan['sale_price'] : $plan['price'];
                                        ?>
                                            <tr>
                                                <td><code><?= $plan['id']; ?></code></td>
                                                <td><?= htmlspecialchars($plan['name']); ?></td>
                                                <td><?= format_currency($final); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <h5><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?>:</h5>
                                <div class="api-code-block">
                                    <pre>curl -X POST "<?= $apiBaseUrl; ?>/orders/create" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>" \
  -d '{"items":[{"plan_id":<?= $default_plan ? $default_plan['id'] : 'PLAN_ID'; ?>,"quantity":1}]}'</pre>
                                </div>
                            </div>

                            <!-- Order Status Tab -->
                            <div class="api-doc-tab-content" id="tab-order-status">
                                <div class="api-endpoint-info">
                                    <span class="api-method get">GET</span>
                                    <code><?= $apiBaseUrl; ?>/orders/status?trans_id={trans_id}</code>
                                </div>

                                <h5><i class="fa-solid fa-arrow-right"></i> <?= __('Parameters'); ?>:</h5>
                                <ul class="api-response-list">
                                    <li><code>trans_id</code> - <?= __('Mã giao dịch từ kết quả tạo đơn'); ?></li>
                                </ul>

                                <h5><i class="fa-solid fa-arrow-left"></i> <?= __('Response chứa'); ?>:</h5>
                                <ul class="api-response-list">
                                    <li><code>order</code> - <?= __('Thông tin đơn hàng'); ?></li>
                                    <li><code>delivery.items</code> - <?= __('Nội dung giao hàng'); ?></li>
                                    <li><code>status</code> - pending, processing, completed, cancelled</li>
                                </ul>

                                <h5><i class="fa-solid fa-code"></i> <?= __('Code mẫu'); ?>:</h5>
                                <div class="api-code-block">
                                    <pre>curl "<?= $apiBaseUrl; ?>/orders/status?trans_id=ORD123456" \
  -H "X-API-Key: <?= $demoApiKey; ?>" \
  -H "X-API-Secret: <?= $demoApiSecret; ?>"</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="api-doc-modal-footer">
                        <a href="<?= base_url('document-api'); ?>" class="btn-view-full-doc" target="_blank">
                            <i class="fa-solid fa-book"></i>
                            <?= __('Xem tài liệu đầy đủ'); ?>
                        </a>
                        <a href="<?= base_url('client/api-keys'); ?>" class="btn-manage-api-keys">
                            <i class="fa-solid fa-key"></i>
                            <?= __('Quản lý API Keys'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <script>
                // API Doc Modal Functions
                function openApiDocModal() {
                    document.getElementById('apiDocModal').classList.add('show');
                    document.body.style.overflow = 'hidden';
                }

                function closeApiDocModal() {
                    document.getElementById('apiDocModal').classList.remove('show');
                    document.body.style.overflow = '';
                }
                document.getElementById('btnApiDoc')?.addEventListener('click', openApiDocModal);

                // Tab switching
                document.querySelectorAll('.api-doc-tab').forEach(tab => {
                    tab.addEventListener('click', function() {
                        document.querySelectorAll('.api-doc-tab').forEach(t => t.classList.remove('active'));
                        document.querySelectorAll('.api-doc-tab-content').forEach(c => c.classList.remove('active'));
                        this.classList.add('active');
                        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
                    });
                });
            </script>

            <style>
                /* === API Doc Modal - Style matching Affiliate Modal === */
                .api-doc-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 9999;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                }

                .api-doc-modal.show {
                    display: flex;
                }

                .api-doc-modal-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.6);
                    animation: fadeIn 0.15s ease-out;
                }

                .api-doc-modal-content {
                    position: relative;
                    width: 100%;
                    max-width: 580px;
                    max-height: 90vh;
                    background: #ffffff;
                    border-radius: 12px;
                    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
                    overflow: hidden;
                    animation: slideUp 0.2s ease-out;
                }

                /* Modal Header - Purple theme */
                .api-doc-modal-header {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 1.25rem 1.5rem;
                    background: #667eea;
                    color: #ffffff;
                }

                .api-doc-modal-icon {
                    width: 40px;
                    height: 40px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.25rem;
                }

                .api-doc-modal-header h3 {
                    flex: 1;
                    margin: 0;
                    font-size: 1.125rem;
                    font-weight: 600;
                    color: #ffffff;
                }

                .api-doc-modal-close {
                    width: 32px;
                    height: 32px;
                    background: rgba(255, 255, 255, 0.15);
                    border: none;
                    border-radius: 8px;
                    color: #ffffff;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background-color 0.12s ease-out;
                }

                .api-doc-modal-close:hover {
                    background: rgba(255, 255, 255, 0.25);
                }

                /* Modal Body */
                .api-doc-modal-body {
                    padding: 1.5rem;
                    overflow-y: auto;
                    max-height: calc(90vh - 180px);
                }

                /* Tabs */
                .api-doc-tabs {
                    display: flex;
                    gap: 0.5rem;
                    margin-bottom: 1.25rem;
                    flex-wrap: wrap;
                }

                .api-doc-tab {
                    padding: 0.5rem 1rem;
                    border: 1.5px solid #e5e7eb;
                    border-radius: 20px;
                    background: #ffffff;
                    cursor: pointer;
                    font-size: 0.8rem;
                    font-weight: 500;
                    color: #6b7280;
                    transition: all 0.15s ease-out;
                }

                .api-doc-tab:hover {
                    border-color: #667eea;
                    color: #667eea;
                }

                .api-doc-tab.active {
                    background: #667eea;
                    color: #fff;
                    border-color: #667eea;
                }

                .api-doc-tab-content {
                    display: none;
                }

                .api-doc-tab-content.active {
                    display: block;
                }

                /* Endpoint Box */
                .api-endpoint-info {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 0.875rem 1rem;
                    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
                    border-radius: 10px;
                    margin-bottom: 1rem;
                    border: 1px solid rgba(102, 126, 234, 0.2);
                }

                .api-method {
                    padding: 0.25rem 0.625rem;
                    border-radius: 6px;
                    font-size: 0.65rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    flex-shrink: 0;
                }

                .api-method.get {
                    background: #10b981;
                    color: #fff;
                }

                .api-method.post {
                    background: #3b82f6;
                    color: #fff;
                }

                .api-endpoint-info code {
                    font-size: 0.75rem;
                    word-break: break-all;
                    color: #374151;
                }

                /* Section Titles */
                .api-doc-modal-body h5 {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-size: 0.85rem;
                    font-weight: 600;
                    margin: 1rem 0 0.625rem;
                    color: #374151;
                }

                .api-doc-modal-body h5 i {
                    color: #667eea;
                }

                /* Response List */
                .api-response-list {
                    list-style: none;
                    padding: 0;
                    margin: 0 0 1rem;
                }

                .api-response-list li {
                    padding: 0.375rem 0;
                    font-size: 0.8rem;
                    display: flex;
                    gap: 0.5rem;
                    align-items: flex-start;
                    color: #4b5563;
                }

                .api-response-list li::before {
                    content: "•";
                    color: #667eea;
                    font-weight: bold;
                }

                /* Info Box */
                .api-product-info-box {
                    background: #f9fafb;
                    border-radius: 10px;
                    padding: 0.875rem 1rem;
                    margin-bottom: 1rem;
                    border: 1px solid #e5e7eb;
                }

                .api-info-row {
                    display: flex;
                    gap: 0.625rem;
                    padding: 0.375rem 0;
                    font-size: 0.8rem;
                }

                .api-info-label {
                    font-weight: 600;
                    min-width: 70px;
                    color: #6b7280;
                }

                .api-plans-list {
                    display: flex;
                    flex-direction: column;
                    gap: 0.25rem;
                }

                .api-plan-item {
                    font-size: 0.75rem;
                    color: #4b5563;
                }

                /* Code Block */
                .api-code-block {
                    background: #1f2937;
                    border-radius: 8px;
                    padding: 1rem;
                    overflow-x: auto;
                }

                .api-code-block pre {
                    margin: 0;
                    color: #e5e7eb;
                    font-size: 0.7rem;
                    line-height: 1.5;
                    white-space: pre-wrap;
                    word-break: break-all;
                }

                /* Plans Table */
                .api-plans-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 1rem;
                    font-size: 0.8rem;
                }

                .api-plans-table th,
                .api-plans-table td {
                    padding: 0.625rem 0.75rem;
                    text-align: left;
                    border-bottom: 1px solid #e5e7eb;
                }

                .api-plans-table th {
                    background: #f9fafb;
                    font-weight: 600;
                    color: #6b7280;
                }

                .api-plans-table td {
                    color: #374151;
                }

                /* Modal Footer */
                .api-doc-modal-footer {
                    display: flex;
                    gap: 0.75rem;
                    padding: 1rem 1.5rem;
                    background: #f9fafb;
                    border-top: 1px solid #e5e7eb;
                }

                .btn-view-full-doc,
                .btn-manage-api-keys {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    padding: 0.75rem 1rem;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 500;
                    font-size: 0.8rem;
                    transition: all 0.15s ease-out;
                }

                .btn-view-full-doc {
                    background: #667eea;
                    color: #fff;
                }

                .btn-view-full-doc:hover {
                    background: #5469d4;
                    color: #fff;
                }

                .btn-manage-api-keys {
                    background: #ffffff;
                    color: #374151;
                    border: 1.5px solid #e5e7eb;
                }

                .btn-manage-api-keys:hover {
                    border-color: #667eea;
                    color: #667eea;
                }

                /* Dark mode */
                [data-theme="dark"] .api-doc-modal-content {
                    background: #1f2937;
                }

                [data-theme="dark"] .api-doc-modal-body {
                    color: #e5e7eb;
                }

                [data-theme="dark"] .api-doc-tab {
                    background: #374151;
                    border-color: #4b5563;
                    color: #9ca3af;
                }

                [data-theme="dark"] .api-doc-tab:hover {
                    border-color: #667eea;
                    color: #667eea;
                }

                [data-theme="dark"] .api-endpoint-info {
                    background: rgba(102, 126, 234, 0.15);
                    border-color: rgba(102, 126, 234, 0.3);
                }

                [data-theme="dark"] .api-endpoint-info code {
                    color: #e5e7eb;
                }

                [data-theme="dark"] .api-doc-modal-body h5 {
                    color: #e5e7eb;
                }

                [data-theme="dark"] .api-response-list li {
                    color: #d1d5db;
                }

                [data-theme="dark"] .api-product-info-box {
                    background: #374151;
                    border-color: #4b5563;
                }

                [data-theme="dark"] .api-info-label {
                    color: #9ca3af;
                }

                [data-theme="dark"] .api-plan-item {
                    color: #d1d5db;
                }

                [data-theme="dark"] .api-plans-table th {
                    background: #374151;
                    color: #9ca3af;
                }

                [data-theme="dark"] .api-plans-table td {
                    color: #e5e7eb;
                }

                [data-theme="dark"] .api-plans-table th,
                [data-theme="dark"] .api-plans-table td {
                    border-color: #4b5563;
                }

                [data-theme="dark"] .api-doc-modal-footer {
                    background: #111827;
                    border-color: #374151;
                }

                [data-theme="dark"] .btn-manage-api-keys {
                    background: #374151;
                    border-color: #4b5563;
                    color: #e5e7eb;
                }

                /* Responsive */
                @media (max-width: 576px) {
                    .api-doc-modal-content {
                        max-height: 85vh;
                        margin-top: auto;
                        border-radius: 12px 12px 0 0;
                    }

                    .api-doc-modal-header {
                        padding: 1rem;
                    }

                    .api-doc-modal-body {
                        padding: 1rem;
                    }

                    .api-doc-modal-footer {
                        flex-direction: column;
                        padding: 1rem;
                    }
                }
            </style>
        <?php endif; ?>

        <!-- Product Plans & Order Section -->
        <div class="product-detail-content">
            <!-- Plans Selection -->
            <div class="product-plans-section">
                <?php if (count($product_plans) > 0): ?>

                    <?php if ($active_flash_sale_end_time): ?>
                        <!-- Flash Sale Countdown -->
                        <div class="flash-sale-countdown" id="flashSaleCountdown" data-end-time="<?= strtotime($active_flash_sale_end_time); ?>">
                            <div class="flash-sale-countdown-label">
                                <i class="fa-solid fa-bolt"></i>
                                <span><?= __('Flash Sale kết thúc sau'); ?></span>
                            </div>
                            <div class="flash-sale-countdown-timer">
                                <div class="countdown-block">
                                    <span class="countdown-value" id="countdownDays">00</span>
                                    <span class="countdown-label"><?= __('Ngày'); ?></span>
                                </div>
                                <span class="countdown-separator">:</span>
                                <div class="countdown-block">
                                    <span class="countdown-value" id="countdownHours">00</span>
                                    <span class="countdown-label"><?= __('Giờ'); ?></span>
                                </div>
                                <span class="countdown-separator">:</span>
                                <div class="countdown-block">
                                    <span class="countdown-value" id="countdownMinutes">00</span>
                                    <span class="countdown-label"><?= __('Phút'); ?></span>
                                </div>
                                <span class="countdown-separator">:</span>
                                <div class="countdown-block">
                                    <span class="countdown-value" id="countdownSeconds">00</span>
                                    <span class="countdown-label"><?= __('Giây'); ?></span>
                                </div>
                            </div>
                        </div>
                        <script>
                            (function() {
                                var endTime = <?= strtotime($active_flash_sale_end_time); ?> * 1000;

                                function updateCountdown() {
                                    var now = Date.now();
                                    var diff = endTime - now;

                                    if (diff <= 0) {
                                        document.getElementById('flashSaleCountdown').style.display = 'none';
                                        location.reload();
                                        return;
                                    }

                                    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                    var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                    var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                                    document.getElementById('countdownDays').textContent = days.toString().padStart(2, '0');
                                    document.getElementById('countdownHours').textContent = hours.toString().padStart(2, '0');
                                    document.getElementById('countdownMinutes').textContent = minutes.toString().padStart(2, '0');
                                    document.getElementById('countdownSeconds').textContent = seconds.toString().padStart(2, '0');
                                }

                                updateCountdown();
                                setInterval(updateCountdown, 1000);
                            })();
                        </script>
                    <?php endif; ?>

                    <div class="product-plans-grid" id="productPlansGrid">
                        <?php foreach ($product_plans as $index => $plan):
                            $plan_image = !empty($plan['image']) ? $plan['image'] : $product['image'];

                            // Kiểm tra Flash Sale cho plan này
                            $plan_flash_sale = isset($plan_flash_sales[$plan['id']]) ? $plan_flash_sales[$plan['id']] : null;
                            $has_flash_sale = !empty($plan_flash_sale);

                            // Tính giá hiển thị
                            $original_price = $plan['price'];
                            $sale_price = ($plan['sale_price'] > 0 && $plan['sale_price'] < $plan['price']) ? $plan['sale_price'] : null;

                            if ($has_flash_sale) {
                                // Áp dụng giá Flash Sale
                                $final_price = $FlashSaleHandler->calculateFlashSalePrice($plan, $plan_flash_sale);
                                $has_sale = true;
                                $discount_percent = round((($original_price - $final_price) / $original_price) * 100);
                                $flash_sale_end_time = $plan_flash_sale['end_time'];
                            } else {
                                $final_price = $sale_price ?? $original_price;
                                $has_sale = ($sale_price !== null);
                                $discount_percent = $has_sale ? round((($original_price - $sale_price) / $original_price) * 100) : 0;
                                $flash_sale_end_time = null;
                            }

                            $is_api_plan = !empty($plan['supplier_id']) && !empty($plan['api_id']);
                            // Sản phẩm API cũng cần check stock nên is_instant = 1
                            $is_instant = $is_api_plan || (isset($plan['is_instant']) && $plan['is_instant'] == 1);
                            $stock_count = $plan_stock_counts[$plan['id']] ?? 0;
                        ?>
                            <div class="product-plan-card <?= $index === 0 ? 'active' : ''; ?> <?= $has_flash_sale ? 'has-flash-sale' : ''; ?>"
                                data-plan-id="<?= $plan['id']; ?>"
                                data-plan-price="<?= $plan['price']; ?>"
                                data-plan-sale-price="<?= $plan['sale_price'] ?? 0; ?>"
                                data-plan-flash-price="<?= $has_flash_sale ? $final_price : 0; ?>"
                                data-plan-instant="<?= $is_instant ? '1' : '0'; ?>"
                                data-plan-stock="<?= $stock_count; ?>"
                                data-flash-sale-end="<?= $flash_sale_end_time ? strtotime($flash_sale_end_time) : ''; ?>">



                                <div class="plan-card-image">
                                    <?php if ($plan_image && file_exists($plan_image)): ?>
                                        <img src="<?= base_url($plan_image); ?>" alt="<?= htmlspecialchars(html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8')); ?>">
                                    <?php else: ?>
                                        <div class="plan-image-placeholder">
                                            <i class="fa-solid fa-box"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="plan-card-content">
                                    <h4 class="plan-card-title"><?= htmlspecialchars(html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8')); ?></h4>

                                    <div class="plan-card-badges">
                                        <span class="product-delivery <?= $is_instant ? 'delivery-instant' : 'delivery-order'; ?>">
                                            <i class="fa-solid <?= $is_instant ? 'fa-bolt' : 'fa-shopping-cart'; ?>"></i>
                                            <?= $is_instant ? __('Giao ngay') : __('Order'); ?>
                                        </span>
                                        <?php if ($has_flash_sale): ?>
                                            <span class="flash-sale-badge-inline">
                                                <i class="fa-solid fa-bolt"></i>
                                                Flash Sale
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="plan-card-price">
                                    <div class="plan-price-main">
                                        <span class="plan-current-price <?= $has_flash_sale ? 'flash-price' : ''; ?>"><?= format_currency($final_price); ?></span>
                                    </div>
                                    <?php if ($has_sale): ?>
                                        <div class="plan-price-extra">
                                            <span class="plan-original-price"><?= format_currency($original_price); ?></span>
                                            <span class="plan-discount-badge <?= $has_flash_sale ? 'flash-discount' : ''; ?>">-<?= $discount_percent; ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="plan-card-check">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-plans-message">
                        <i class="fa-solid fa-box-open"></i>
                        <p><?= __('Sản phẩm này hiện chưa có gói nào'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Selected Plan Description - Modern Design -->
                <div class="selected-plan-description" id="selectedPlanDescription" style="<?= !empty($default_plan) && !empty($default_plan['description']) ? '' : 'display:none;'; ?>">
                    <div class="plan-desc-header collapse-toggle" data-target="selectedPlanDescriptionContent">
                        <div class="plan-desc-header-left">
                            <div class="plan-desc-icon-wrapper">
                                <i class="fa-solid fa-cube"></i>
                            </div>
                            <div class="plan-desc-title-group">
                                <span class="plan-desc-label"><?= __('Chi tiết gói'); ?></span>
                                <span class="selected-plan-name"><?= !empty($default_plan) ? htmlspecialchars(html_entity_decode($default_plan['name'], ENT_QUOTES, 'UTF-8')) : ''; ?></span>
                            </div>
                        </div>
                        <div class="plan-desc-toggle-btn">
                            <i class="fa-solid fa-chevron-down collapse-icon"></i>
                        </div>
                    </div>
                    <div class="plan-desc-content collapse-content" id="selectedPlanDescriptionContent">
                        <div class="plan-desc-inner">
                            <?php if (!empty($default_plan) && !empty($default_plan['description'])): ?>
                                <?= $default_plan['description']; ?>
                            <?php else: ?>
                                <p class="text-muted"><?= __('Chọn một gói để xem mô tả'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Description - Modern Design -->
                <!-- Product Description - Modern Design -->
                <div class="product-description-section">
                    <div class="desc-section-header collapse-toggle collapsed" data-target="productDescriptionContent">
                        <div class="desc-header-left">
                            <div class="desc-icon-wrapper">
                                <i class="fa-solid fa-scroll"></i>
                            </div>
                            <div class="desc-title-group">
                                <span class="desc-label"><?= __('Giới thiệu'); ?></span>
                                <h3 class="desc-title"><?= __('Mô tả sản phẩm'); ?></h3>
                            </div>
                        </div>
                        <div class="desc-toggle-btn">
                            <span class="toggle-text"><?= __('Xem chi tiết'); ?></span>
                            <i class="fa-solid fa-chevron-down collapse-icon"></i>
                        </div>
                    </div>
                    <div class="product-description-content collapse-content collapsed" id="productDescriptionContent">
                        <div class="desc-content-wrapper">
                            <div class="description-text" id="productDescriptionText">
                                <?= $product['description']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Reviews Section (separate for mobile reorder) -->
            <?php if ($CMSNT->site('status_review_product') == 1): ?>
                <div class="product-reviews-section" id="productReviewsSection">
                    <div class="section-header">
                        <div class="header-left">
                            <i class="fa-solid fa-star"></i>
                            <h3><?= __('Đánh giá sản phẩm'); ?></h3>
                        </div>
                        <span class="review-count-badge"><?= $product_rating_count; ?> <?= __('đánh giá'); ?></span>
                    </div>

                    <!-- Rating Overview -->
                    <div class="reviews-overview">
                        <div class="reviews-summary">
                            <div class="rating-big">
                                <span class="rating-number"><?= number_format($product_rating, 1); ?></span>
                                <div class="rating-stars-big">
                                    <?php
                                    $full_stars = floor($product_rating);
                                    $has_half_star = ($product_rating - $full_stars) >= 0.5;
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $full_stars): ?>
                                            <i class="fa-solid fa-star"></i>
                                        <?php elseif ($i == $full_stars + 1 && $has_half_star): ?>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        <?php else: ?>
                                            <i class="fa-regular fa-star"></i>
                                    <?php endif;
                                    endfor; ?>
                                </div>
                                <span class="rating-label"><?= $product_rating_count; ?> <?= __('đánh giá'); ?></span>
                                <?php if (isset($getUser) && $getUser && count($user_pending_review_orders) > 0): ?>
                                    <button type="button" class="btn-write-review-inline" id="writeReviewToggle">
                                        <i class="fa-solid fa-pen"></i>
                                        <?= __('Viết đánh giá'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="rating-bars">
                                <?php for ($i = 5; $i >= 1; $i--):
                                    $count = $rating_stats[$i];
                                    $percent = $product_rating_count > 0 ? round(($count / $product_rating_count) * 100) : 0;
                                ?>
                                    <div class="rating-bar-row">
                                        <span class="rating-bar-label"><?= $i; ?> <i class="fa-solid fa-star"></i></span>
                                        <div class="rating-bar-track">
                                            <div class="rating-bar-fill" style="width: <?= $percent; ?>%"></div>
                                        </div>
                                        <span class="rating-bar-count"><?= $count; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Write Review Modal -->
                    <?php if (isset($getUser) && $getUser && count($user_pending_review_orders) > 0): ?>
                        <div class="review-modal" id="writeReviewModal">
                            <div class="review-modal-backdrop" onclick="closeReviewModal()"></div>
                            <div class="review-modal-content">
                                <div class="review-modal-header">
                                    <h3><i class="fa-solid fa-pen-to-square"></i> <?= __('Viết đánh giá'); ?></h3>
                                    <button type="button" class="review-modal-close" onclick="closeReviewModal()">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                                <div class="review-modal-body">
                                    <form class="write-review-form" id="writeReviewForm">
                                        <input type="hidden" id="reviewUserToken" value="<?= $getUser['token']; ?>">

                                        <!-- Select Order -->
                                        <div class="review-form-group">
                                            <label><?= __('Chọn đơn hàng'); ?> <span class="required">*</span></label>
                                            <select class="review-select" id="reviewOrderSelect" required>
                                                <option value=""><?= __('-- Chọn đơn hàng đã mua --'); ?></option>
                                                <?php foreach ($user_pending_review_orders as $order): ?>
                                                    <option value="<?= $order['id']; ?>" data-plan-id="<?= $order['plan_id']; ?>">
                                                        #<?= $order['trans_id']; ?> - <?= htmlspecialchars(html_entity_decode($order['plan_name'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                                        (x<?= $order['quantity']; ?>) - <?= date('d/m/Y', strtotime($order['created_at'])); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Rating -->
                                        <div class="review-form-group">
                                            <label><?= __('Đánh giá'); ?> <span class="required">*</span></label>
                                            <div class="rating-input" id="ratingInput">
                                                <i class="fa-regular fa-star" data-rating="1"></i>
                                                <i class="fa-regular fa-star" data-rating="2"></i>
                                                <i class="fa-regular fa-star" data-rating="3"></i>
                                                <i class="fa-regular fa-star" data-rating="4"></i>
                                                <i class="fa-regular fa-star" data-rating="5"></i>
                                                <span class="rating-text" id="ratingText"><?= __('Chọn số sao'); ?></span>
                                            </div>
                                            <input type="hidden" id="reviewRating" value="">
                                        </div>

                                        <!-- Content -->
                                        <div class="review-form-group">
                                            <label><?= __('Nội dung đánh giá'); ?> <span class="required">*</span></label>
                                            <textarea class="review-textarea" id="reviewContent"
                                                placeholder="<?= __('Chia sẻ trải nghiệm của bạn về sản phẩm này...'); ?>"
                                                rows="4" required minlength="5" maxlength="2000"></textarea>
                                            <div class="review-char-count">
                                                <span id="reviewCharCount">0</span>/2000
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <button type="submit" class="btn-submit-review" id="btnSubmitReview">
                                            <i class="fa-solid fa-paper-plane"></i>
                                            <?= __('Gửi đánh giá'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!isset($getUser) || !$getUser): ?>
                        <div class="write-review-login-prompt">
                            <i class="fa-solid fa-sign-in-alt"></i>
                            <span><?= __('Vui lòng'); ?> <a href="<?= base_url('client/login?redirect=' . urlencode(base_url('product/' . $product['slug']))); ?>"><?= __('đăng nhập'); ?></a> <?= __('và mua hàng để đánh giá sản phẩm'); ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div class="reviews-list" id="reviewsList">
                        <?php if (count($approved_reviews) > 0): ?>
                            <?php foreach ($approved_reviews as $index => $review): ?>
                                <div class="review-item" data-review-id="<?= $review['id']; ?>">
                                    <div class="review-header">
                                        <div class="review-user">
                                            <div class="review-avatar">
                                                <?php if (!empty($review['avatar']) && file_exists($review['avatar'])): ?>
                                                    <img src="<?= base_url($review['avatar']); ?>" alt="">
                                                <?php else: ?>
                                                    <i class="fa-solid fa-user"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="review-user-info">
                                                <span class="review-username"><?php
                                                                                $display_name = $review['user_name'] ?: $review['username'];
                                                                                // Ẩn bớt username: giữ 2 ký tự đầu + *** + 1 ký tự cuối
                                                                                $len = mb_strlen($display_name);
                                                                                if ($len > 4) {
                                                                                    $masked_name = mb_substr($display_name, 0, 2) . '***' . mb_substr($display_name, -1);
                                                                                } elseif ($len > 2) {
                                                                                    $masked_name = mb_substr($display_name, 0, 1) . '***';
                                                                                } else {
                                                                                    $masked_name = $display_name[0] . '***';
                                                                                }
                                                                                echo htmlspecialchars($masked_name);
                                                                                ?></span>
                                                <?php if ($review['is_verified_purchase']): ?>
                                                    <span class="verified-badge">
                                                        <i class="fa-solid fa-check-circle"></i>
                                                        <?= __('Đã mua hàng'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="review-meta">
                                            <div class="review-rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fa-<?= $i <= $review['rating'] ? 'solid' : 'regular'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="review-date"><?= date('d/m/Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                    </div>

                                    <?php if (!empty($review['plan_name'])): ?>
                                        <div class="review-plan">
                                            <i class="fa-solid fa-box"></i>
                                            <?= htmlspecialchars(html_entity_decode($review['plan_name'], ENT_QUOTES, 'UTF-8')); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($review['title'])): ?>
                                        <h4 class="review-title"><?= htmlspecialchars($review['title']); ?></h4>
                                    <?php endif; ?>

                                    <div class="review-content">
                                        <?= nl2br(htmlspecialchars($review['content'])); ?>
                                    </div>

                                    <?php if (!empty($review['images'])):
                                        $images = json_decode($review['images'], true);
                                        if (is_array($images) && count($images) > 0):
                                    ?>
                                            <div class="review-images">
                                                <?php foreach ($images as $img): ?>
                                                    <div class="review-image-item">
                                                        <img src="<?= base_url($img); ?>" alt="">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                    <?php endif;
                                    endif; ?>

                                    <?php if (!empty($review['admin_reply'])): ?>
                                        <div class="review-admin-reply">
                                            <div class="admin-reply-header">
                                                <i class="fa-solid fa-store"></i>
                                                <span class="admin-reply-label"><?= __('Phản hồi từ Shop'); ?></span>
                                                <?php if (!empty($review['admin_reply_at'])): ?>
                                                    <span class="admin-reply-date"><?= date('d/m/Y', strtotime($review['admin_reply_at'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="admin-reply-content">
                                                <?= nl2br(htmlspecialchars($review['admin_reply'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="review-actions">
                                        <?php $is_voted = in_array((int)$review['id'], $user_voted_review_ids); ?>
                                        <button type="button" class="btn-review-helpful<?= $is_voted ? ' voted' : ''; ?>" data-review-id="<?= $review['id']; ?>">
                                            <?php if ($is_voted): ?>
                                                <i class="fa-solid fa-thumbs-down"></i>
                                                <span><?= __('Bỏ vote'); ?></span>
                                            <?php else: ?>
                                                <i class="fa-regular fa-thumbs-up"></i>
                                                <span><?= __('Hữu ích'); ?></span>
                                            <?php endif; ?>
                                            <?php if ($review['helpful_count'] > 0): ?>
                                                <span class="helpful-count">(<?= $review['helpful_count']; ?>)</span>
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($total_reviews > $reviews_limit): ?>
                                <div class="reviews-load-more" id="reviewsLoadMore" data-product-id="<?= $product['id']; ?>" data-offset="<?= $reviews_limit; ?>" data-total="<?= $total_reviews; ?>">
                                    <button type="button" class="btn-load-more-reviews" id="btnLoadMoreReviews">
                                        <i class="fa-solid fa-chevron-down"></i>
                                        <?= __('Xem thêm'); ?> <span id="hiddenReviewsCount">(<?= $total_reviews - $reviews_limit; ?>)</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-reviews-message">
                                <i class="fa-solid fa-comment-slash"></i>
                                <p><?= __('Chưa có đánh giá nào cho sản phẩm này'); ?></p>
                                <span><?= __('Hãy là người đầu tiên đánh giá sản phẩm!'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Order Form -->
            <div class="product-order-section">
                <form class="order-form-card" autocomplete="off" novalidate>
                    <!-- User Token (Hidden) -->
                    <input type="hidden" class="form-control" value="<?= isset($getUser) ? $getUser['token'] : ''; ?>" id="userToken">

                    <!-- Quantity -->
                    <div class="order-form-group order-form-group-quantity">
                        <label><?= __('Số lượng'); ?></label>
                        <div class="quantity-selector">
                            <button type="button" class="qty-btn qty-minus" id="qtyMinus">
                                <i class="fa-solid fa-minus"></i>
                            </button>
                            <input type="number" class="qty-input" id="orderQuantity" value="1" min="1" max="1000000">
                            <button type="button" class="qty-btn qty-plus" id="qtyPlus">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Order Fields -->
                    <div class="order-form-group" id="orderFieldsSection">
                        <label><?= __('Thông tin Order'); ?></label>
                        <div class="order-fields-container" id="orderFieldsContainer">
                            <?php if ($default_plan && count($default_plan_fields) > 0): ?>
                                <?php foreach ($default_plan_fields as $field):
                                    // Xác định autocomplete value dựa trên field type
                                    $autocomplete_value = 'off';
                                    if ($field['type'] === 'password') {
                                        $autocomplete_value = 'new-password';
                                    } else {
                                        $autocomplete_value = 'one-time-code';
                                    }
                                ?>
                                    <div class="order-field">
                                        <?php if ($field['type'] === 'textarea'): ?>
                                            <textarea
                                                name="field_<?= $field['field_key']; ?>"
                                                id="field_<?= $field['field_key']; ?>"
                                                class="order-input"
                                                placeholder="<?= htmlspecialchars(html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8')); ?> <?= $field['is_required'] ? '*' : ''; ?>"
                                                <?= $field['is_required'] ? 'required' : ''; ?>
                                                rows="3"
                                                autocomplete="<?= $autocomplete_value; ?>"
                                                data-lpignore="true"
                                                data-form-type="other"></textarea>
                                        <?php else: ?>
                                            <input
                                                type="<?= $field['type']; ?>"
                                                name="field_<?= $field['field_key']; ?>"
                                                id="field_<?= $field['field_key']; ?>"
                                                class="order-input"
                                                placeholder="<?= htmlspecialchars(html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8')); ?> <?= $field['is_required'] ? '*' : ''; ?>"
                                                <?= $field['is_required'] ? 'required' : ''; ?>
                                                autocomplete="<?= $autocomplete_value; ?>"
                                                data-lpignore="true"
                                                data-form-type="other">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-fields-message"><?= __('Không có trường thông tin nào cần điền'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Coupon Section -->
                    <div class="order-coupon-section">
                        <div class="coupon-toggle" id="couponToggle">
                            <i class="fa-solid fa-ticket"></i>
                            <span><?= __('Bạn có mã giảm giá?'); ?></span>
                            <i class="fa-solid fa-chevron-down coupon-toggle-arrow"></i>
                        </div>
                        <div class="coupon-form-wrapper" id="couponFormWrapper">
                            <div class="coupon-input-group">
                                <input type="text"
                                    class="coupon-input"
                                    id="couponCode"
                                    placeholder="<?= __('Nhập mã giảm giá'); ?>"
                                    maxlength="50"
                                    autocomplete="off">
                                <button type="button" class="btn-apply-coupon" id="btnApplyCoupon">
                                    <?= __('Áp dụng'); ?>
                                </button>
                            </div>
                            <div class="coupon-message" id="couponMessage"></div>
                            <div class="applied-coupon" id="appliedCoupon" style="display:none;">
                                <div class="applied-coupon-info">
                                    <i class="fa-solid fa-check-circle"></i>
                                    <span class="applied-coupon-code" id="appliedCouponCode"></span>
                                    <span class="applied-coupon-desc" id="appliedCouponDesc"></span>
                                </div>
                                <button type="button" class="btn-remove-coupon" id="btnRemoveCoupon" title="<?= __('Xóa mã giảm giá'); ?>">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="order-price-summary">
                        <div class="price-row">
                            <span><?= __('Tạm tính'); ?></span>
                            <span class="price-value" id="originalPrice">
                                <?php if ($default_plan): ?>
                                    <?= format_currency($default_plan['price']); ?>
                                <?php else: ?>
                                    <?= format_currency(0); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="price-row member-discount-row" id="memberDiscountRow" style="display:none;">
                            <span class="discount-label member-discount-label">
                                <i class="fa-solid fa-crown"></i> <?= __('Chiết khấu'); ?> <span class="discount-percent">(<span id="memberDiscountPercent">0</span>%)</span>
                            </span>
                            <span class="price-value discount" id="memberDiscountAmount">-<?= format_currency(0); ?></span>
                        </div>
                        <div class="price-row flash-sale-row" id="flashSaleRow" style="display:none;">
                            <span class="discount-label flash-sale-label">
                                <i class="fa-solid fa-bolt"></i> <?= __('Flash Sale'); ?>
                            </span>
                            <span class="price-value discount" id="flashSaleAmount">-<?= format_currency(0); ?></span>
                        </div>
                        <div class="price-row sale-row" id="saleRow" style="display:none;">
                            <span class="discount-label sale-label">
                                <i class="fa-solid fa-tag"></i> <?= __('Giảm giá sản phẩm'); ?>
                            </span>
                            <span class="price-value discount" id="saleAmount">-<?= format_currency(0); ?></span>
                        </div>
                        <div class="price-row coupon-discount-row" id="couponDiscountRow" style="display:none;">
                            <span class="discount-label coupon-label">
                                <i class="fa-solid fa-ticket"></i> <?= __('Mã giảm giá'); ?>
                            </span>
                            <span class="price-value coupon-discount" id="couponDiscountAmount">-<?= format_currency(0); ?></span>
                        </div>
                        <div class="price-row total-row">
                            <span><?= __('Tổng cộng'); ?></span>
                            <span class="price-value total" id="totalPrice">
                                <?php if ($default_plan):
                                    $final = ($default_plan['sale_price'] > 0 && $default_plan['sale_price'] < $default_plan['price'])
                                        ? $default_plan['sale_price']
                                        : $default_plan['price'];
                                ?>
                                    <?= format_currency($final); ?>
                                <?php else: ?>
                                    <?= format_currency(0); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Stock Info (for instant delivery or API product) -->
                    <?php
                    $default_is_api = $default_plan && !empty($default_plan['supplier_id']) && !empty($default_plan['api_id']);
                    $default_is_instant = $default_is_api || ($default_plan && isset($default_plan['is_instant']) && $default_plan['is_instant'] == 1);
                    ?>
                    <div class="order-stock-info" id="stockInfo" style="<?= $default_is_instant ? '' : 'display:none;'; ?>">
                        <i class="fa-solid fa-box-open"></i>
                        <span>
                            <?= __('Kho hàng:'); ?>
                            <strong id="stockCount"><?= $default_plan ? ($plan_stock_counts[$default_plan['id']] ?? 0) : 0; ?></strong>
                            <?= __('sản phẩm'); ?>
                        </span>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="order-buttons-row">
                        <?php if (isset($getUser) && $getUser): ?>
                            <button type="button" class="btn-order" id="btnSubmitOrder" <?= count($product_plans) === 0 ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-shopping-cart"></i>
                                <?= __('Đặt hàng ngay'); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?= base_url('client/login?redirect=' . urlencode(base_url('product/' . $product['slug']))); ?>" class="btn-order btn-login-required">
                                <i class="fa-solid fa-sign-in-alt"></i>
                                <?= __('Đăng nhập để mua'); ?>
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn-add-cart" id="btnAddToCart" title="<?= __('Thêm vào giỏ hàng'); ?>" <?= count($product_plans) === 0 ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-cart-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Track recently viewed product in localStorage
    (function() {
        const productId = <?= (int)$product['id']; ?>;
        let viewed = [];
        try {
            viewed = JSON.parse(localStorage.getItem('recently_viewed') || '[]');
        } catch (e) {
            viewed = [];
        }
        // Remove if already exists
        viewed = viewed.filter(id => id !== productId);
        // Add to beginning
        viewed.unshift(productId);
        // Keep only last 10
        viewed = viewed.slice(0, 10);
        // Save
        localStorage.setItem('recently_viewed', JSON.stringify(viewed));
    })();
</script>

<?php require_once(__DIR__ . '/footer.php'); ?>