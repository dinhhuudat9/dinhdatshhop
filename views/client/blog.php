<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Lấy slug từ URL
$slug = isset($_GET['slug']) ? validate_string($_GET['slug'], 255, 1) : '';

if ($slug === false || empty($slug)) {
    die('<script type="text/javascript">if(!alert("' . __('Bài viết không tồn tại') . '")){window.location.href = "' . base_url('?module=client&action=blogs') . '";}</script>');
}

// Lấy thông tin bài viết
$blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `slug` = ? AND `status` = 'published'", [$slug]);

if (!$blog) {
    die('<script type="text/javascript">if(!alert("' . __('Bài viết không tồn tại hoặc chưa được xuất bản') . '")){window.location.href = "' . base_url('?module=client&action=blogs') . '";}</script>');
}

// Tăng lượt xem
$CMSNT->update("blogs", [
    'views' => $blog['views'] + 1
], " `id` = ? ", [$blog['id']]);

// Lấy thông tin chuyên mục
$blog_category = null;
if ($blog['category_id'] > 0) {
    $blog_category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$blog['category_id']]);
}

// Lấy thông tin tác giả
$author = $CMSNT->get_row_safe("SELECT `username`, `fullname` FROM `users` WHERE `id` = ?", [$blog['author_id']]);

// Lấy bài viết liên quan (cùng chuyên mục)
$related_blogs = [];
if ($blog['category_id'] > 0) {
    $related_blogs = $CMSNT->get_list_safe("SELECT * FROM `blogs` WHERE `category_id` = ? AND `id` != ? AND `status` = 'published' ORDER BY `published_at` DESC LIMIT 3", [$blog['category_id'], $blog['id']]);
}

// Nếu không có bài liên quan cùng chuyên mục, lấy bài mới nhất
if (count($related_blogs) < 3) {
    $additional = 3 - count($related_blogs);
    $additional_blogs = $CMSNT->get_list_safe("SELECT * FROM `blogs` WHERE `id` != ? AND `status` = 'published' ORDER BY `published_at` DESC LIMIT ?", [$blog['id'], $additional]);
    $related_blogs = array_merge($related_blogs, $additional_blogs);
}

// Lấy bài viết trước và sau
$prev_blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` < ? AND `status` = 'published' ORDER BY `id` DESC LIMIT 1", [$blog['id']]);
$next_blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` > ? AND `status` = 'published' ORDER BY `id` ASC LIMIT 1", [$blog['id']]);

// Lấy bài viết phổ biến cho sidebar
$popular_blogs = $CMSNT->get_list_safe("SELECT * FROM `blogs` WHERE `status` = 'published' AND `id` != ? ORDER BY `views` DESC LIMIT 5", [$blog['id']]);

// Lấy tất cả chuyên mục
$all_categories = $CMSNT->get_list_safe("SELECT * FROM `blog_categories` WHERE `status` = 1 ORDER BY `sort_order` ASC, `name` ASC");

// SEO Meta
$body = [
    'title' => $blog['meta_title'] ?: ($blog['title'] . ' | ' . $CMSNT->site('title')),
    'desc'   => $blog['meta_description'] ?: $blog['excerpt'],
    'keyword' => $blog['meta_keywords'] ?: $CMSNT->site('keywords'),
    'image' => !empty($blog['thumbnail']) ? BASE_URL($blog['thumbnail']) : BASE_URL($CMSNT->site('image'))
];
$body['header'] = '<link rel="stylesheet" href="' . BASE_URL('mod/css/blogs.css?v=') . time() . '">';
$body['footer'] = '<script src="' . BASE_URL('mod/js/blogs.js?v=') . time() . '"></script>';

if ($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
} else {
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}

require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

$author_name = ($author && !empty($author['fullname'])) ? $author['fullname'] : 'Admin';
?>

<!-- Page Header -->
<div class="news-page-header">
    <div class="container">
        <div class="news-page-header-content">
            <nav class="news-breadcrumb">
                <a href="<?= base_url(); ?>"><i class="fa-solid fa-home"></i> <?= __('Trang chủ'); ?></a>
                <span class="separator">›</span>
                <a href="<?= base_url('?module=client&action=blogs'); ?>"><?= __('Blog'); ?></a>
                <?php if ($blog_category): ?>
                    <span class="separator">›</span>
                    <a href="<?= base_url('?module=client&action=blogs&category_id=' . $blog_category['id']); ?>">
                        <?= htmlspecialchars(html_entity_decode($blog_category['name'], ENT_QUOTES, 'UTF-8')); ?>
                    </a>
                <?php endif; ?>
            </nav>
            <h1 class="news-page-title">
                <?php if ($blog_category): ?>
                    <i class="fa-solid fa-folder"></i>
                <?php else: ?>
                    <i class="fa-solid fa-newspaper"></i>
                <?php endif; ?>
                <?= htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8')); ?>
            </h1>
            <div class="news-page-meta">
                <span class="news-page-meta-item">
                    <i class="fa-regular fa-user"></i>
                    <?= htmlspecialchars($author_name); ?>
                </span>
                <span class="news-page-meta-item">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d/m/Y, H:i', strtotime($blog['published_at'])); ?>
                </span>
                <span class="news-page-meta-item">
                    <i class="fa-regular fa-eye"></i>
                    <?= format_cash($blog['views']); ?> <?= __('lượt xem'); ?>
                </span>
                <?php if ($blog['is_featured'] == 1): ?>
                    <span class="news-page-meta-item featured">
                        <i class="fa-solid fa-star"></i>
                        <?= __('Nổi bật'); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="news-page">

    <!-- Article Content -->
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-9">
                <article class="article-container">
                    <!-- Thumbnail -->
                    <?php if (!empty($blog['thumbnail'])): ?>
                        <img src="<?= BASE_URL($blog['thumbnail']); ?>"
                            alt="<?= htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8')); ?>"
                            class="article-thumbnail">
                    <?php endif; ?>

                    <div class="article-content-wrapper">
                        <!-- Excerpt -->
                        <?php if (!empty($blog['excerpt'])): ?>
                            <div class="article-excerpt">
                                <div class="article-excerpt-label">
                                    <i class="fa-solid fa-quote-left"></i>
                                    <?= __('Tóm tắt'); ?>
                                </div>
                                <p><?= htmlspecialchars(html_entity_decode($blog['excerpt'], ENT_QUOTES, 'UTF-8')); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Content -->
                        <div class="article-content">
                            <?= $blog['content']; ?>
                        </div>

                        <!-- Footer -->
                        <footer class="article-footer">
                            <!-- Tags -->
                            <?php if (!empty($blog['meta_keywords'])):
                                $keywords = array_filter(array_map('trim', explode(',', $blog['meta_keywords'])));
                            ?>
                                <?php if (count($keywords) > 0): ?>
                                    <div class="article-tags">
                                        <div class="article-tags-label">
                                            <i class="fa-solid fa-tags"></i>
                                            <?= __('Tags'); ?>
                                        </div>
                                        <div class="article-tags-list">
                                            <?php foreach ($keywords as $keyword): ?>
                                                <a href="<?= base_url('?module=client&action=blogs&search=' . urlencode($keyword)); ?>" class="article-tag">
                                                    #<?= htmlspecialchars($keyword); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Share -->
                            <div class="article-share">
                                <div class="article-share-label">
                                    <i class="fa-solid fa-share-nodes"></i>
                                    <?= __('Chia sẻ bài viết'); ?>
                                </div>
                                <?php $current_url = base_url('blog/' . $blog['slug']); ?>
                                <div class="article-share-buttons">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($current_url); ?>"
                                        target="_blank" class="article-share-btn facebook">
                                        <i class="fa-brands fa-facebook-f"></i>Facebook
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($current_url); ?>&text=<?= urlencode($blog['title']); ?>"
                                        target="_blank" class="article-share-btn twitter">
                                        <i class="fa-brands fa-twitter"></i>Twitter
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($current_url); ?>"
                                        target="_blank" class="article-share-btn linkedin">
                                        <i class="fa-brands fa-linkedin-in"></i>LinkedIn
                                    </a>
                                    <button onclick="copyBlogUrl('<?= addslashes($current_url); ?>')" class="article-share-btn copy">
                                        <i class="fa-solid fa-link"></i><?= __('Sao chép'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Navigation -->
                            <?php if ($prev_blog || $next_blog): ?>
                                <nav class="article-nav">
                                    <?php if ($prev_blog): ?>
                                        <a href="<?= base_url('blog/' . $prev_blog['slug']); ?>" class="article-nav-item prev">
                                            <div class="article-nav-label">
                                                <i class="fa-solid fa-arrow-left"></i>
                                                <?= __('Bài trước'); ?>
                                            </div>
                                            <h5 class="article-nav-title">
                                                <?= htmlspecialchars(html_entity_decode($prev_blog['title'], ENT_QUOTES, 'UTF-8')); ?>
                                            </h5>
                                        </a>
                                    <?php else: ?>
                                        <div></div>
                                    <?php endif; ?>

                                    <?php if ($next_blog): ?>
                                        <a href="<?= base_url('blog/' . $next_blog['slug']); ?>" class="article-nav-item next">
                                            <div class="article-nav-label">
                                                <?= __('Bài tiếp'); ?>
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </div>
                                            <h5 class="article-nav-title">
                                                <?= htmlspecialchars(html_entity_decode($next_blog['title'], ENT_QUOTES, 'UTF-8')); ?>
                                            </h5>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>
                        </footer>
                    </div>
                </article>

                <!-- Related Posts -->
                <?php if (count($related_blogs) > 0): ?>
                    <section class="related-posts">
                        <div class="news-section-header">
                            <h2 class="news-section-title"><?= __('Bài viết liên quan'); ?></h2>
                        </div>
                        <div class="related-posts-grid">
                            <?php foreach ($related_blogs as $related):
                                $related_cat = null;
                                if ($related['category_id'] > 0) {
                                    $related_cat = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$related['category_id']]);
                                }
                            ?>
                                <article class="news-card">
                                    <a href="<?= base_url('blog/' . $related['slug']); ?>" class="news-card-link">
                                        <div class="news-card-image">
                                            <?php if (!empty($related['thumbnail'])): ?>
                                                <img src="<?= BASE_URL($related['thumbnail']); ?>"
                                                    class="news-card-thumbnail"
                                                    alt="<?= htmlspecialchars(html_entity_decode($related['title'], ENT_QUOTES, 'UTF-8')); ?>">
                                            <?php else: ?>
                                                <div class="news-card-placeholder">
                                                    <i class="fa-solid fa-newspaper"></i>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($related_cat): ?>
                                                <span class="news-card-category">
                                                    <?= htmlspecialchars(html_entity_decode($related_cat['name'], ENT_QUOTES, 'UTF-8')); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="news-card-body">
                                            <h3 class="news-card-title">
                                                <?= htmlspecialchars(html_entity_decode($related['title'], ENT_QUOTES, 'UTF-8')); ?>
                                            </h3>
                                            <div class="news-card-meta" style="margin-top: auto;">
                                                <span>
                                                    <i class="fa-regular fa-clock"></i>
                                                    <?= date('d/m/Y', strtotime($related['published_at'])); ?>
                                                </span>
                                                <span>
                                                    <i class="fa-solid fa-eye"></i>
                                                    <?= format_cash($related['views']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <aside class="news-sidebar">
                    <!-- Popular Posts Widget -->
                    <?php if (count($popular_blogs) > 0): ?>
                        <div class="news-widget">
                            <div class="news-widget-header">
                                <h4 class="news-widget-title">
                                    <i class="fa-solid fa-fire me-2"></i><?= __('Đọc nhiều nhất'); ?>
                                </h4>
                            </div>
                            <div class="news-widget-body">
                                <div class="news-popular-list">
                                    <?php foreach ($popular_blogs as $index => $pop): ?>
                                        <a href="<?= base_url('blog/' . $pop['slug']); ?>" class="news-popular-item">
                                            <span class="news-popular-number"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                                            <div class="news-popular-content">
                                                <h5 class="news-popular-title">
                                                    <?= htmlspecialchars(html_entity_decode($pop['title'], ENT_QUOTES, 'UTF-8')); ?>
                                                </h5>
                                                <div class="news-popular-meta">
                                                    <span>
                                                        <i class="fa-solid fa-eye me-1"></i>
                                                        <?= format_cash($pop['views']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Categories Widget -->
                    <?php if (count($all_categories) > 0): ?>
                        <div class="news-widget">
                            <div class="news-widget-header">
                                <h4 class="news-widget-title">
                                    <i class="fa-solid fa-folder me-2"></i><?= __('Chuyên mục'); ?>
                                </h4>
                            </div>
                            <div class="news-widget-body">
                                <div class="news-categories-list">
                                    <?php foreach ($all_categories as $cat):
                                        $cat_count = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `blogs` WHERE `category_id` = ? AND `status` = 'published'", [$cat['id']]);
                                    ?>
                                        <a href="<?= base_url('?module=client&action=blogs&category_id=' . $cat['id']); ?>"
                                            class="news-category-link <?= $blog['category_id'] == $cat['id'] ? 'active' : ''; ?>">
                                            <span class="news-category-name"><?= htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8')); ?></span>
                                            <span class="news-category-count"><?= number_format($cat_count['total'] ?? 0); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Back to Blog -->
                    <div class="news-widget">
                        <div class="news-widget-body" style="text-align: center;">
                            <a href="<?= base_url('?module=client&action=blogs'); ?>" class="news-empty-btn" style="width: 100%;">
                                <i class="fa-solid fa-arrow-left"></i><?= __('Xem tất cả bài viết'); ?>
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>