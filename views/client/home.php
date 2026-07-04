<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

if ($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
} else {
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}


$body = [
    'title' => $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/home.css?v=6">
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/product.css?v=1">
<link rel="stylesheet" href="' . BASE_URL('mod/') . 'css/flashsale.css?v=6">
';
$body['footer'] = '
<script>
    var BASE_URL = "' . base_url() . '";
    var USER_TOKEN = "' . (isset($getUser) ? addslashes($getUser['token']) : '') . '";
    function base_url(path) { return BASE_URL + (path || ""); }
    // Translation strings
    var TRANSLATIONS = {
        sold: "' . addslashes(__('Đã bán')) . '",
        deliveryInstant: "' . addslashes(__('Giao ngay')) . '",
        deliveryOrder: "' . addslashes(__('Order')) . '",
        text_featured_products: "' . addslashes(__('Sản phẩm nổi bật')) . '"
    };
    var IS_SHOW_SOLD = ' . ($CMSNT->site('isShowSold') == 1 ? 'true' : 'false') . ';
    // Recently Viewed Widget translations
    var LANG_RECENTLY_VIEWED_PREFIX = "' . addslashes(__('Bạn đã xem')) . '";
    var LANG_RECENTLY_VIEWED_SUFFIX = "' . addslashes(__('sản phẩm gần đây')) . '";
    var LANG_SOLD = "' . addslashes(__('Đã bán')) . '";
    var LANG_INSTANT_DELIVERY = "' . addslashes(__('Giao ngay')) . '";
    var LANG_ORDER = "' . addslashes(__('Order')) . '";
</script>
<script src="' . BASE_URL('mod/') . 'js/home.js?v=2"></script>
<script src="' . BASE_URL('mod/') . 'js/product.js?v=3"></script>
';



require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

// Lấy banner cố định bên trái và phải
$banners_sidebar_left = $CMSNT->get_list_safe("SELECT * FROM `banners` WHERE `status` = 1 AND `position` = ? ORDER BY `sort_order` ASC, `id` DESC", ['sidebar_left']);
$banners_sidebar_right = $CMSNT->get_list_safe("SELECT * FROM `banners` WHERE `status` = 1 AND `position` = ? ORDER BY `sort_order` ASC, `id` DESC", ['sidebar_right']);

?>

<!-- Fixed Sidebar Banners -->
<?php if (count($banners_sidebar_left) > 0 || count($banners_sidebar_right) > 0): ?>
    <div class="home-sidebar-banners">
        <?php if (count($banners_sidebar_left) > 0): ?>
            <div class="home-sidebar-banner home-sidebar-banner-left" id="sidebarBannerLeft">
                <button type="button" class="home-sidebar-banner-close" onclick="closeSidebarBanner('left')" aria-label="<?= __('Đóng banner'); ?>">
                    <i class="fa-solid fa-times"></i>
                </button>
                <?php foreach ($banners_sidebar_left as $banner): ?>
                    <a href="<?= !empty($banner['link']) ? htmlspecialchars($banner['link'], ENT_QUOTES, 'UTF-8') : 'javascript:void(0);'; ?>"
                        class="home-sidebar-banner-link" <?= !empty($banner['link']) ? 'target="_blank"' : ''; ?>>
                        <img src="<?= BASE_URL($banner['image']); ?>"
                            alt="<?= htmlspecialchars($banner['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="home-sidebar-banner-img">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($banners_sidebar_right) > 0): ?>
            <div class="home-sidebar-banner home-sidebar-banner-right" id="sidebarBannerRight">
                <button type="button" class="home-sidebar-banner-close" onclick="closeSidebarBanner('right')" aria-label="<?= __('Đóng banner'); ?>">
                    <i class="fa-solid fa-times"></i>
                </button>
                <?php foreach ($banners_sidebar_right as $banner): ?>
                    <a href="<?= !empty($banner['link']) ? htmlspecialchars($banner['link'], ENT_QUOTES, 'UTF-8') : 'javascript:void(0);'; ?>"
                        class="home-sidebar-banner-link" <?= !empty($banner['link']) ? 'target="_blank"' : ''; ?>>
                        <img src="<?= BASE_URL($banner['image']); ?>"
                            alt="<?= htmlspecialchars($banner['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            class="home-sidebar-banner-img">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="section feature-part">
    <div class="container">
        <?php require_once(__DIR__ . '/widgets/sliders.php'); ?>

        <?php require_once(__DIR__ . '/widgets/flashsale-products.php'); ?>

        <?php if ($CMSNT->site('notice_home') != ''): ?>
            <!-- Announcement Bar -->
            <div class="home-announcement-bar" id="announcementBar">
                <div class="announcement-content">
                    <i class="fa-solid fa-bullhorn announcement-icon"></i>
                    <div class="announcement-text">
                        <?= $CMSNT->site('notice_home'); ?>
                    </div>
                </div>
                <button class="announcement-close" onclick="closeAnnouncement()" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Categories Section - Modern Design -->
        <?php
        // Lấy categories cha (parent_id == 0)
        $parent_categories = get_categories_parent_cached();
        // Lấy tất cả categories con (parent_id != 0)
        $all_child_categories = get_categories_not_parent_cached();
        if (count($all_child_categories) > 0):
        ?>
            <div class="home-categories-section mb-5">
                <!-- Parent Categories Filter Buttons -->
                <?php if (count($parent_categories) > 0): ?>
                    <div class="parent-categories-filter mb-2">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-parent-category active" data-parent-id="all">
                                <i class="fa-solid fa-layer-group"></i> <?= __('Tất cả'); ?>
                            </button>
                            <?php foreach ($parent_categories as $parent): ?>
                                <button class="btn btn-sm btn-parent-category" data-parent-id="<?= $parent['id']; ?>">
                                    <?php if ($parent['icon'] != null && file_exists($parent['icon'])): ?>
                                        <img src="<?= base_url($parent['icon']); ?>" alt="<?= __($parent['name']); ?>" style="width: 14px; height: 14px; object-fit: contain; margin-right: 3px;">
                                    <?php else: ?>
                                        <i class="fa-solid fa-folder"></i>
                                    <?php endif; ?>
                                    <?= __($parent['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Child Categories Grid with Carousel -->
                <div class="categories-card-wrapper">
                    <?php $showCarouselArrows = count($all_child_categories) > 10; ?>
                    <div class="categories-carousel-container<?= $showCarouselArrows ? ' has-arrows' : ''; ?>">
                        <?php if ($showCarouselArrows): ?>
                            <button type="button" class="categories-carousel-nav prev" id="categoriesNavPrev" aria-label="<?= __('Trước'); ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>

                        <div class="categories-grid" id="categoriesGrid">
                            <?php foreach ($all_child_categories as $category): ?>
                                <div class="category-card" data-parent-id="<?= $category['parent_id']; ?>" data-category-id="<?= $category['id']; ?>" data-category-name="<?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="category-card-icon">
                                        <?php if ($category['icon'] != null && file_exists($category['icon'])): ?>
                                            <img src="<?= base_url($category['icon']); ?>" alt="<?= __($category['name']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <div class="category-icon-placeholder">
                                                <i class="fa-solid fa-folder-open"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="category-card-label">
                                        <?= __($category['name']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($showCarouselArrows): ?>
                            <button type="button" class="categories-carousel-nav next" id="categoriesNavNext" aria-label="<?= __('Tiếp'); ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <!-- Empty State Message -->
                    <div class="categories-empty-state" style="display: none;">
                        <div class="empty-state-icon">
                            <i class="fa-solid fa-folder-open"></i>
                        </div>
                        <div class="empty-state-text">
                            <h4><?= __('Chưa có chuyên mục con'); ?></h4>
                            <p><?= __('Chuyên mục này hiện chưa có sản phẩm nào.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <!-- Products Section - Modern Design -->
        <div class="home-products-section mb-5" id="productsSection">
            <div class="products-section-header">
                <div class="products-section-header-left">
                    <h3 class="products-section-title" id="productsSectionTitle"><i class="fa-solid fa-fire" style="color: #ff6b35; margin-right: 8px;"></i><?= __('Sản phẩm nổi bật'); ?></h3>
                    <p class="products-section-subtitle"><?= __('Khám phá bộ sưu tập sản phẩm chất lượng cao được chọn lọc dành riêng cho bạn'); ?></p>
                </div>
                <a href="<?= base_url('products'); ?>" id="viewAllProductsLink" class="btn-explore">
                    <?= __('Xem tất cả'); ?>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productsGrid">
                <!-- Products will be loaded here via AJAX -->
            </div>

            <!-- Products Loading Skeleton -->
            <div class="products-loading" id="productsLoading" style="display: none;">
                <div class="products-grid">
                    <?php for ($i = 0; $i < 8; $i++): ?>
                        <div class="product-card-skeleton">
                            <div class="skeleton-image"></div>
                            <div class="skeleton-content">
                                <div class="skeleton-title"></div>
                                <div class="skeleton-price"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Products Empty State -->
            <div class="products-empty-state" id="productsEmptyState" style="display: none;">
                <div class="empty-state-icon">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <div class="empty-state-text">
                    <h4><?= __('Chưa có sản phẩm'); ?></h4>
                    <p><?= __('Chuyên mục này hiện chưa có sản phẩm nào. Hãy quay lại sau nhé!'); ?></p>
                </div>
            </div>

            <!-- Load More Button -->
            <div class="products-load-more" id="productsLoadMore" style="display: none;">
                <button type="button" class="btn-load-more" id="btnLoadMore">
                    <span id="loadMoreSpinner" style="display: none;"><i class="fa-solid fa-spinner fa-spin me-2"></i></span>
                    <span id="loadMoreText"><?= __('Tải thêm sản phẩm'); ?></span>
                </button>
            </div>
        </div>


        <?php require_once(__DIR__ . '/widgets/recently-viewed-products.php'); ?>

    </div>
</section>







<?php if ($CMSNT->site('popup_status') == 1): ?>
    <div class="modal fade" id="modal_notification" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLabel1"><i class="fa-solid fa-bell"></i> <?= __('Thông Báo'); ?>
                    </h6>
                    <button type="button" class="modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <?= $CMSNT->site('popup_noti'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger"
                        id="dontShowAgainBtn"><?= __('Không hiển thị lại trong 2 giờ'); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>


<?php
require_once(__DIR__ . '/footer.php');
?>