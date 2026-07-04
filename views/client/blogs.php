<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

// Phân trang an toàn
$limit = isset($_GET['limit']) ? (validate_int($_GET['limit'], 1, 100) ?: 12) : 12;
$page = isset($_GET['page']) ? (validate_int($_GET['page'], 1, 1000000) ?: 1) : 1;
$from = ($page - 1) * $limit;

// Sắp xếp
$sort = isset($_GET['sort']) ? validate_string($_GET['sort'], 20) : 'latest';
$sort_options = [
    'latest' => '`published_at` DESC, `id` DESC',
    'oldest' => '`published_at` ASC, `id` ASC',
    'views' => '`views` DESC, `id` DESC',
    'title' => '`title` ASC'
];
$order_by = isset($sort_options[$sort]) ? $sort_options[$sort] : $sort_options['latest'];

// Biến giữ giá trị hiển thị lại
$category_filter = 0;
$search = '';

// WHERE an toàn với prepared statements
$where_conditions = ["`status` = 'published'"];
$where_params = [];

// Lọc theo danh mục
if (!empty($_GET['category_id'])) {
    $category_filter_input = validate_int($_GET['category_id'], 1);
    if ($category_filter_input !== false) {
        $category_filter = $category_filter_input;
        $where_conditions[] = '`category_id` = ?';
        $where_params[] = $category_filter;
    }
}

// Tìm kiếm
if (!empty($_GET['search'])) {
    $search_input = validate_string($_GET['search'], 255, 1);
    if ($search_input !== false) {
        $search = $search_input;
        $where_conditions[] = '(`title` LIKE ? OR `excerpt` LIKE ? OR `content` LIKE ?)';
        $searchPattern = '%' . $search . '%';
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Tổng số bài viết
$countSql = "SELECT COUNT(*) AS total_count FROM `blogs` WHERE $where_clause";
$totalRow = $CMSNT->get_row_safe($countSql, $where_params);
$total = (int)($totalRow['total_count'] ?? 0);
$total_pages = ceil($total / $limit);

// Lấy danh sách bài viết với thông tin tác giả
$listSql = "SELECT b.*, u.`username` as author_name, u.`fullname` as author_fullname FROM `blogs` b LEFT JOIN `users` u ON b.`author_id` = u.`id` WHERE $where_clause ORDER BY $order_by LIMIT ?, ?";
$listParams = array_merge($where_params, [$from, $limit]);
$blogs = $CMSNT->get_list_safe($listSql, $listParams);

// Lấy danh sách chuyên mục
$blog_categories = $CMSNT->get_list_safe("SELECT * FROM `blog_categories` WHERE `status` = 1 ORDER BY `sort_order` ASC, `name` ASC");

// Lấy bài viết nổi bật cho Hero (chỉ hiển thị khi không có filter)
$hero_blogs = [];
if(empty($search) && $category_filter == 0 && $page == 1) {
    $hero_blogs = $CMSNT->get_list_safe("SELECT b.*, u.`username` as author_name, u.`fullname` as author_fullname FROM `blogs` b LEFT JOIN `users` u ON b.`author_id` = u.`id` WHERE b.`status` = 'published' AND b.`is_featured` = 1 ORDER BY b.`published_at` DESC LIMIT 3", []);
}

// Lấy bài viết phổ biến cho sidebar
$popular_blogs = $CMSNT->get_list_safe("SELECT * FROM `blogs` WHERE `status` = 'published' ORDER BY `views` DESC LIMIT 5", []);

// Thống kê
$stats_total = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `blogs` WHERE `status` = 'published'", []);

// SEO Meta cho trang danh sách blog
$category_seo = null;
if($category_filter > 0) {
    $category_seo = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$category_filter]);
}

$body = [
    'title' => $category_seo ? ($category_seo['meta_title'] ?: $category_seo['name']).' | '.$CMSNT->site('title') : __('Blog').' | '.$CMSNT->site('title'),
    'desc'   => $category_seo ? ($category_seo['meta_description'] ?: $category_seo['description']) : __('Khám phá các bài viết, tin tức và hướng dẫn hữu ích'),
    'keyword' => $category_seo ? $category_seo['meta_keywords'] : $CMSNT->site('keywords')
];
$body['header'] = '<link rel="stylesheet" href="'.BASE_URL('mod/css/blogs.css?v=').time().'">';
$body['footer'] = '<script src="'.BASE_URL('mod/js/blogs.js?v=').time().'"></script>';

if($CMSNT->site('isLoginRequiredToViewProduct') == 1) {
    require_once(__DIR__ . '/../../models/is_user.php');
}else{
    if (isSecureCookie('user_login') == true) {
        require_once(__DIR__ . '/../../models/is_user.php');
    }
}

require_once(__DIR__.'/header.php');
require_once(__DIR__.'/nav.php');
?>

<!-- Page Header -->
<div class="news-page-header">
    <div class="container">
        <div class="news-page-header-content">
            <nav class="news-breadcrumb">
                <a href="<?=base_url();?>"><i class="fa-solid fa-home"></i> <?=__('Trang chủ');?></a>
                <span class="separator">›</span>
                <span class="current"><?=__('Blog');?></span>
            </nav>
            <h1 class="news-page-title">
                <i class="fa-solid fa-newspaper"></i>
                <?php if($category_seo): ?>
                    <?=htmlspecialchars(html_entity_decode($category_seo['name'], ENT_QUOTES, 'UTF-8'));?>
                <?php else: ?>
                    <?=__('Blog & Tin tức');?>
                <?php endif; ?>
            </h1>
            <p class="news-page-desc">
                <?php if($category_seo && !empty($category_seo['description'])): ?>
                    <?=htmlspecialchars(html_entity_decode($category_seo['description'], ENT_QUOTES, 'UTF-8'));?>
                <?php else: ?>
                    <?=__('Khám phá các bài viết, tin tức và hướng dẫn hữu ích từ chúng tôi');?>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="news-page">
    <!-- Hero Section - Featured Posts -->
    <?php if(count($hero_blogs) >= 3 && empty($search) && $category_filter == 0 && $page == 1): ?>
    <section class="news-hero">
        <div class="container">
            <div class="news-hero-grid">
                <!-- Main Featured Post -->
                <?php 
                $main_blog = $hero_blogs[0];
                $main_category = null;
                if($main_blog['category_id'] > 0) {
                    $main_category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$main_blog['category_id']]);
                }
                ?>
                <div class="news-hero-main">
                    <a href="<?=base_url('blog/'.$main_blog['slug']);?>" class="news-hero-main-link">
                        <?php if(!empty($main_blog['thumbnail'])): ?>
                        <img src="<?=BASE_URL($main_blog['thumbnail']);?>" 
                             alt="<?=htmlspecialchars(html_entity_decode($main_blog['title'], ENT_QUOTES, 'UTF-8'));?>" 
                             class="news-hero-main-image">
                        <?php endif; ?>
                        <div class="news-hero-main-content">
                            <?php if($main_category): ?>
                            <span class="news-hero-main-category" onclick="event.preventDefault(); event.stopPropagation(); window.location.href='<?=base_url('?module=client&action=blogs&category_id='.$main_category['id']);?>';">
                                <?=htmlspecialchars(html_entity_decode($main_category['name'], ENT_QUOTES, 'UTF-8'));?>
                            </span>
                            <?php endif; ?>
                            <h2 class="news-hero-main-title">
                                <?=htmlspecialchars(html_entity_decode($main_blog['title'], ENT_QUOTES, 'UTF-8'));?>
                            </h2>
                            <div class="news-hero-main-meta">
                                <span>
                                    <i class="fa-regular fa-user"></i>
                                    <?=htmlspecialchars($main_blog['author_fullname'] ?: $main_blog['author_name'] ?: 'Admin');?>
                                </span>
                                <span>
                                    <i class="fa-regular fa-calendar"></i>
                                    <?=date('d/m/Y', strtotime($main_blog['published_at']));?>
                                </span>
                                <span>
                                    <i class="fa-regular fa-eye"></i>
                                    <?=format_cash($main_blog['views']);?> <?=__('lượt xem');?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Side Featured Posts -->
                <div class="news-hero-side">
                    <?php for($i = 1; $i < 3; $i++): 
                        $side_blog = $hero_blogs[$i];
                        $side_category = null;
                        if($side_blog['category_id'] > 0) {
                            $side_category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$side_blog['category_id']]);
                        }
                    ?>
                    <div class="news-hero-side-item">
                        <a href="<?=base_url('blog/'.$side_blog['slug']);?>" class="news-hero-side-link">
                            <?php if(!empty($side_blog['thumbnail'])): ?>
                            <img src="<?=BASE_URL($side_blog['thumbnail']);?>" 
                                 alt="<?=htmlspecialchars(html_entity_decode($side_blog['title'], ENT_QUOTES, 'UTF-8'));?>" 
                                 class="news-hero-side-image">
                            <?php endif; ?>
                            <div class="news-hero-side-content">
                                <?php if($side_category): ?>
                                <span class="news-hero-side-category">
                                    <?=htmlspecialchars(html_entity_decode($side_category['name'], ENT_QUOTES, 'UTF-8'));?>
                                </span>
                                <?php endif; ?>
                                <h3 class="news-hero-side-title">
                                    <?=htmlspecialchars(html_entity_decode($side_blog['title'], ENT_QUOTES, 'UTF-8'));?>
                                </h3>
                                <div class="news-hero-side-meta">
                                    <span>
                                        <i class="fa-regular fa-calendar"></i>
                                        <?=date('d/m/Y', strtotime($side_blog['published_at']));?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Posts Column -->
            <div class="col-lg-9">
                <!-- Active Filters -->
                <?php if(!empty($search) || $category_filter > 0): ?>
                <div class="news-filters">
                    <div class="news-filters-list">
                        <span class="news-filter-label">
                            <i class="fa-solid fa-filter me-1"></i><?=__('Đang lọc');?>:
                        </span>
                        <?php if($category_filter > 0 && $category_seo): ?>
                        <span class="news-filter-tag">
                            <i class="fa-solid fa-folder me-1"></i>
                            <?=htmlspecialchars(html_entity_decode($category_seo['name'], ENT_QUOTES, 'UTF-8'));?>
                        </span>
                        <?php endif; ?>
                        <?php if(!empty($search)): ?>
                        <span class="news-filter-tag">
                            <i class="fa-solid fa-search me-1"></i>
                            "<?=htmlspecialchars($search);?>"
                        </span>
                        <?php endif; ?>
                    </div>
                    <a href="<?=base_url('?module=client&action=blogs');?>" class="news-clear-btn">
                        <i class="fa-solid fa-times"></i><?=__('Xóa bộ lọc');?>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Section Header -->
                <div class="news-section-header">
                    <h1 class="news-section-title">
                        <?php if($category_seo): ?>
                            <?=htmlspecialchars(html_entity_decode($category_seo['name'], ENT_QUOTES, 'UTF-8'));?>
                        <?php elseif(!empty($search)): ?>
                            <?=__('Kết quả tìm kiếm');?>
                        <?php else: ?>
                            <?=__('Tin mới nhất');?>
                        <?php endif; ?>
                    </h1>
                    <?php if($total > 0): ?>
                    <span class="news-section-link">
                        <?=number_format($total);?> <?=__('bài viết');?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Posts Grid -->
                <?php if(count($blogs) > 0): ?>
                <div class="news-grid" id="blogListContainer">
                    <?php foreach ($blogs as $index => $blog): 
                        $category = null;
                        if($blog['category_id'] > 0) {
                            $category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$blog['category_id']]);
                        }
                    ?>
                    <article class="news-card">
                        <a href="<?=base_url('blog/'.$blog['slug']);?>" class="news-card-link">
                            <div class="news-card-image">
                                <?php if(!empty($blog['thumbnail'])): ?>
                                <img src="<?=BASE_URL($blog['thumbnail']);?>" 
                                     class="news-card-thumbnail" 
                                     alt="<?=htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8'));?>">
                                <?php else: ?>
                                <div class="news-card-placeholder">
                                    <i class="fa-solid fa-newspaper"></i>
                                </div>
                                <?php endif; ?>
                                <?php if($category): ?>
                                <span class="news-card-category">
                                    <?=htmlspecialchars(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'));?>
                                </span>
                                <?php endif; ?>
                                <?php if($blog['is_featured'] == 1): ?>
                                <span class="news-card-featured">
                                    <i class="fa-solid fa-star me-1"></i><?=__('Hot');?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="news-card-body">
                                <h3 class="news-card-title">
                                    <?=htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8'));?>
                                </h3>
                                <p class="news-card-excerpt">
                                    <?=htmlspecialchars(html_entity_decode($blog['excerpt'], ENT_QUOTES, 'UTF-8'));?>
                                </p>
                                <div class="news-card-footer">
                                    <?php if(!empty($blog['author_fullname']) || !empty($blog['author_name'])): ?>
                                    <div class="news-card-author">
                                        <div class="news-card-avatar">
                                            <?=strtoupper(substr($blog['author_fullname'] ?: $blog['author_name'], 0, 1));?>
                                        </div>
                                        <span class="news-card-author-name">
                                            <?=htmlspecialchars($blog['author_fullname'] ?: $blog['author_name']);?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="news-card-meta">
                                        <span>
                                            <i class="fa-regular fa-clock"></i>
                                            <?=date('d/m', strtotime($blog['published_at']));?>
                                        </span>
                                        <span>
                                            <i class="fa-solid fa-eye"></i>
                                            <?=format_cash($blog['views']);?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php
                    $pagination_url = base_url('?module=client&action=blogs');
                    $pagination_url .= '&limit='.$limit;
                    if($category_filter > 0) $pagination_url .= '&category_id='.$category_filter;
                    if(!empty($search)) $pagination_url .= '&search='.urlencode($search);
                    if($sort != 'latest') $pagination_url .= '&sort='.$sort;
                    $pagination_url .= '&';
                    
                    $urlDatatable = pagination($pagination_url, $from, $total, $limit);
                ?>
                <?php if($total > $limit): ?>
                <div class="news-pagination">
                    <span class="news-pagination-info">
                        <?=__('Hiển thị');?> <?=number_format($from + 1);?> - <?=number_format(min($from + $limit, $total));?> 
                        <?=__('của');?> <?=number_format($total);?> <?=__('bài viết');?>
                    </span>
                    <div>
                        <?=$urlDatatable;?>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Empty State -->
                <div class="news-empty">
                    <div class="news-empty-icon">
                        <i class="fa-solid fa-newspaper"></i>
                    </div>
                    <h4 class="news-empty-title"><?=__('Không tìm thấy bài viết');?></h4>
                    <p class="news-empty-desc">
                        <?php if(!empty($search)): ?>
                            <?=__('Không có kết quả cho');?> "<strong><?=htmlspecialchars($search);?></strong>"
                        <?php elseif($category_filter > 0): ?>
                            <?=__('Chuyên mục này chưa có bài viết');?>
                        <?php else: ?>
                            <?=__('Hiện chưa có bài viết nào');?>
                        <?php endif; ?>
                    </p>
                    <?php if(!empty($search) || $category_filter > 0): ?>
                    <a href="<?=base_url('?module=client&action=blogs');?>" class="news-empty-btn">
                        <i class="fa-solid fa-arrow-left"></i><?=__('Xem tất cả');?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <aside class="news-sidebar">
                    <!-- Search Widget -->
                    <div class="news-widget">
                        <div class="news-widget-header">
                            <h4 class="news-widget-title">
                                <i class="fa-solid fa-search me-2"></i><?=__('Tìm kiếm');?>
                            </h4>
                        </div>
                        <div class="news-widget-body">
                            <form method="GET" action="" id="blogSearchForm" class="news-search-form">
                                <input type="hidden" name="module" value="client">
                                <input type="hidden" name="action" value="blogs">
                                <?php if($category_filter > 0): ?>
                                <input type="hidden" name="category_id" value="<?=$category_filter;?>">
                                <?php endif; ?>
                                <input type="text" 
                                       class="news-search-input" 
                                       name="search" 
                                       value="<?=htmlspecialchars($search);?>" 
                                       placeholder="<?=__('Tìm bài viết...');?>">
                                <button type="submit" class="news-search-btn">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Categories Widget -->
                    <?php if(count($blog_categories) > 0): ?>
                    <div class="news-widget">
                        <div class="news-widget-header">
                            <h4 class="news-widget-title">
                                <i class="fa-solid fa-folder me-2"></i><?=__('Chuyên mục');?>
                            </h4>
                        </div>
                        <div class="news-widget-body">
                            <div class="news-categories-list">
                                <a href="<?=base_url('?module=client&action=blogs');?>" 
                                   class="news-category-link <?=$category_filter == 0 ? 'active' : '';?>">
                                    <span class="news-category-name"><?=__('Tất cả bài viết');?></span>
                                    <span class="news-category-count"><?=number_format($stats_total['total'] ?? 0);?></span>
                                </a>
                                <?php foreach($blog_categories as $cat): 
                                    $cat_count = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `blogs` WHERE `category_id` = ? AND `status` = 'published'", [$cat['id']]);
                                ?>
                                <a href="<?=base_url('?module=client&action=blogs&category_id='.$cat['id']);?>" 
                                   class="news-category-link <?=$category_filter == $cat['id'] ? 'active' : '';?>">
                                    <span class="news-category-name"><?=htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8'));?></span>
                                    <span class="news-category-count"><?=number_format($cat_count['total'] ?? 0);?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Popular Posts Widget -->
                    <?php if(count($popular_blogs) > 0): ?>
                    <div class="news-widget">
                        <div class="news-widget-header">
                            <h4 class="news-widget-title">
                                <i class="fa-solid fa-fire me-2"></i><?=__('Đọc nhiều nhất');?>
                            </h4>
                        </div>
                        <div class="news-widget-body">
                            <div class="news-popular-list">
                                <?php foreach($popular_blogs as $index => $pop): ?>
                                <a href="<?=base_url('blog/'.$pop['slug']);?>" class="news-popular-item">
                                    <span class="news-popular-number"><?=str_pad($index + 1, 2, '0', STR_PAD_LEFT);?></span>
                                    <div class="news-popular-content">
                                        <h5 class="news-popular-title">
                                            <?=htmlspecialchars(html_entity_decode($pop['title'], ENT_QUOTES, 'UTF-8'));?>
                                        </h5>
                                        <div class="news-popular-meta">
                                            <span>
                                                <i class="fa-solid fa-eye me-1"></i>
                                                <?=format_cash($pop['views']);?>
                                            </span>
                                            <span>
                                                <i class="fa-regular fa-clock me-1"></i>
                                                <?=date('d/m/Y', strtotime($pop['published_at']));?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sort Widget -->
                    <div class="news-widget">
                        <div class="news-widget-header">
                            <h4 class="news-widget-title">
                                <i class="fa-solid fa-sort me-2"></i><?=__('Sắp xếp');?>
                            </h4>
                        </div>
                        <div class="news-widget-body">
                            <form method="GET" action="" id="blogSortForm">
                                <input type="hidden" name="module" value="client">
                                <input type="hidden" name="action" value="blogs">
                                <?php if($category_filter > 0): ?>
                                <input type="hidden" name="category_id" value="<?=$category_filter;?>">
                                <?php endif; ?>
                                <?php if(!empty($search)): ?>
                                <input type="hidden" name="search" value="<?=htmlspecialchars($search);?>">
                                <?php endif; ?>
                                <select class="form-select" name="sort" onchange="this.form.submit()">
                                    <option value="latest" <?=$sort == 'latest' ? 'selected' : '';?>><?=__('Mới nhất');?></option>
                                    <option value="oldest" <?=$sort == 'oldest' ? 'selected' : '';?>><?=__('Cũ nhất');?></option>
                                    <option value="views" <?=$sort == 'views' ? 'selected' : '';?>><?=__('Xem nhiều nhất');?></option>
                                    <option value="title" <?=$sort == 'title' ? 'selected' : '';?>><?=__('Theo tên A-Z');?></option>
                                </select>
                            </form>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>
