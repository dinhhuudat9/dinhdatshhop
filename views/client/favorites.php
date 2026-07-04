<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

require_once(__DIR__ . '/../../models/is_user.php');

// Lấy danh sách sản phẩm yêu thích
$favorites = $CMSNT->get_list_safe(
    "SELECT p.*, pf.created_at as favorited_at,
            (SELECT COUNT(*) FROM `product_plans` WHERE `product_id` = p.id AND `status` = 1 AND `is_instant` = 1) as instant_plan_count
     FROM `product_favorites` pf 
     INNER JOIN `products` p ON pf.product_id = p.id 
     WHERE pf.user_id = ? AND p.status = 1 
     ORDER BY pf.created_at DESC",
    [$getUser['id']]
);

$favorites_count = count($favorites);

// Count by type
$instant_count = 0;
$order_count = 0;
foreach ($favorites as $fav) {
    if ($fav['instant_plan_count'] > 0) {
        $instant_count++;
    } else {
        $order_count++;
    }
}

$body = [
    'title' => __('Sản phẩm yêu thích') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/main.css">
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/product.css">
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/favorites.css?v=' . time() . '">
';
$body['footer'] = '
<script>
var BASE_URL = "' . BASE_URL('') . '";
var FAVORITES_TRANSLATIONS = {
    removeSuccess: "' . addslashes(__('Đã xóa khỏi danh sách yêu thích')) . '",
    errorOccurred: "' . addslashes(__('Đã xảy ra lỗi, vui lòng thử lại')) . '",
    confirmRemove: "' . addslashes(__('Bạn có chắc muốn xóa sản phẩm này khỏi danh sách yêu thích?')) . '",
    product: "' . addslashes(__('sản phẩm')) . '",
    noFavorites: "' . addslashes(__('Chưa có sản phẩm yêu thích')) . '",
    noFavoritesText: "' . addslashes(__('Hãy thêm sản phẩm vào danh sách yêu thích để xem lại sau')) . '",
    explore: "' . addslashes(__('Khám phá sản phẩm')) . '"
};
</script>
<script src="' . BASE_URL('mod/') . 'js/favorites.js?v=' . time() . '"></script>
';

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');
?>

<input type="hidden" id="userToken" value="<?= $getUser['token']; ?>">

<section class="py-5 inner-section favorites-page">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-3">
                <?php require_once(__DIR__ . '/sidebar.php'); ?>
            </div>
            <div class="col-lg-9 mb-5">
                <!-- Page Header -->
                <div class="favorites-page-header">
                    <div class="favorites-page-header-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <div class="favorites-page-header-content">
                        <h1 class="favorites-page-header-title"><?= __('Sản phẩm yêu thích'); ?></h1>
                        <p class="favorites-page-header-count"><span id="favoritesCount"><?= $favorites_count; ?></span> <?= __('sản phẩm'); ?></p>
                    </div>
                    <?php if ($favorites_count > 0): ?>
                        <div class="favorites-page-header-actions">
                            <a href="<?= base_url('products'); ?>" class="btn-add-new">
                                <i class="fa-solid fa-plus"></i>
                                <?= __('Thêm mới'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($favorites_count > 0): ?>
                    <!-- Filter Tabs -->
                    <div class="favorites-filters">
                        <button type="button" class="favorites-filter-btn active" data-filter="all">
                            <i class="fa-solid fa-layer-group"></i>
                            <?= __('Tất cả'); ?>
                            <span class="count"><?= $favorites_count; ?></span>
                        </button>
                        <button type="button" class="favorites-filter-btn" data-filter="instant">
                            <i class="fa-solid fa-bolt"></i>
                            <?= __('Giao ngay'); ?>
                            <span class="count"><?= $instant_count; ?></span>
                        </button>
                        <button type="button" class="favorites-filter-btn" data-filter="order">
                            <i class="fa-solid fa-shopping-cart"></i>
                            <?= __('Order'); ?>
                            <span class="count"><?= $order_count; ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Favorites List -->
                <div class="favorites-list">
                    <?php if ($favorites_count > 0): ?>
                        <?php foreach ($favorites as $product):
                            $is_instant = $product['instant_plan_count'] > 0;
                            $product_type = $is_instant ? 'instant' : 'order';

                            // Lấy giá từ gói (giá thấp nhất)
                            $price_result = $CMSNT->get_row_safe(
                                "SELECT MIN(CASE WHEN sale_price > 0 AND sale_price < price THEN sale_price ELSE price END) as min_price,
                                        MIN(price) as original_price
                                 FROM `product_plans` WHERE `product_id` = ? AND `status` = 1",
                                [$product['id']]
                            );
                            $current_price = $price_result ? $price_result['min_price'] : 0;
                            $original_price = $price_result ? $price_result['original_price'] : 0;
                            $has_sale = $current_price < $original_price;
                            $sale_percent = $has_sale && $original_price > 0 ? round((1 - $current_price / $original_price) * 100) : 0;
                        ?>
                            <div class="favorite-item" data-type="<?= $product_type; ?>">
                                <div class="favorite-item-image">
                                    <?php if ($product['image'] && file_exists($product['image'])): ?>
                                        <a href="<?= base_url('product/' . $product['slug']); ?>">
                                            <img src="<?= base_url($product['image']); ?>" alt="<?= htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?>" loading="lazy">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('product/' . $product['slug']); ?>">
                                            <div class="favorite-item-placeholder">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($sale_percent > 0): ?>
                                        <span class="favorite-item-sale">-<?= $sale_percent; ?>%</span>
                                    <?php endif; ?>
                                </div>

                                <div class="favorite-item-content">
                                    <div class="favorite-item-header">
                                        <span class="favorite-item-badge <?= $is_instant ? 'instant' : 'order'; ?>">
                                            <i class="fa-solid <?= $is_instant ? 'fa-bolt' : 'fa-shopping-cart'; ?>"></i>
                                            <?= $is_instant ? __('Giao ngay') : __('Order'); ?>
                                        </span>
                                        <span class="favorite-item-date">
                                            <i class="fa-regular fa-clock"></i>
                                            <?= date('d/m/Y', strtotime($product['favorited_at'])); ?>
                                        </span>
                                    </div>

                                    <h3 class="favorite-item-title">
                                        <a href="<?= base_url('product/' . $product['slug']); ?>">
                                            <?= htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?>
                                        </a>
                                    </h3>

                                    <div class="favorite-item-meta">
                                        <span><i class="fa-solid fa-eye"></i> <?= number_format($product['views'] ?? 0); ?> <?= __('lượt xem'); ?></span>
                                        <?php if (isset($product['sold']) && $product['sold'] > 0): ?>
                                            <span class="favorite-meta-dot">•</span>
                                            <span><i class="fa-solid fa-shopping-bag"></i> <?= __('Đã bán'); ?> <?= number_format($product['sold']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="favorite-item-price">
                                    <span class="favorite-item-price-current"><?= format_currency($current_price); ?></span>
                                    <?php if ($has_sale): ?>
                                        <span class="favorite-item-price-original"><?= format_currency($original_price); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="favorite-item-actions">
                                    <a href="<?= base_url('product/' . $product['slug']); ?>" class="favorite-item-btn-view">
                                        <i class="fa-solid fa-eye"></i>
                                        <?= __('Xem'); ?>
                                    </a>
                                    <button type="button" class="favorite-item-btn-remove" data-product-id="<?= $product['id']; ?>" title="<?= __('Xóa khỏi yêu thích'); ?>">
                                        <i class="fa-solid fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="favorites-empty">
                            <div class="favorites-empty-icon">
                                <i class="fa-solid fa-heart-crack"></i>
                            </div>
                            <h3 class="favorites-empty-title"><?= __('Chưa có sản phẩm yêu thích'); ?></h3>
                            <p class="favorites-empty-text"><?= __('Hãy thêm sản phẩm vào danh sách yêu thích để xem lại sau'); ?></p>
                            <a href="<?= base_url('products'); ?>" class="favorites-empty-btn">
                                <i class="fa-solid fa-shopping-bag"></i>
                                <?= __('Khám phá sản phẩm'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div><!-- /.favorites-list -->
            </div>
        </div>
    </div>
</section>

<?php require_once(__DIR__ . '/footer.php'); ?>