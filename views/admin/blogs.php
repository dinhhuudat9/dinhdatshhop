<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý bài viết').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';
$body['footer'] = '';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');
if(checkPermission($getUser['admin'], 'view_blog') != true){
    $role_name = getRoleName('view_blog');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

// Phân trang an toàn
$limit = isset($_GET['limit']) ? (validate_int($_GET['limit'], 1, 1000) ?: 20) : 20;
$page = isset($_GET['page']) ? (validate_int($_GET['page'], 1, 1000000) ?: 1) : 1;
$from = ($page - 1) * $limit;

// Biến giữ giá trị hiển thị lại
$category_filter = 0;
$search = '';
$status_filter = '';
$date_from = '';
$date_to = '';
$is_featured_filter = -1;

// WHERE an toàn với prepared statements
$where_conditions = ["1 = 1"];
$where_params = [];

// Lọc theo danh mục
if (!empty($_GET['category_id'])) {
    $category_filter_input = validate_int($_GET['category_id'], 1);
    if ($category_filter_input !== false) {
        $category_filter = $category_filter_input;
        $where_conditions[] = '`category_id` = ?';
        $where_params[] = $category_filter;
    } else {
        $category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    }
}

// Lọc theo trạng thái
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status_input = validate_string($_GET['status'], 20);
    if ($status_input !== false && in_array($status_input, ['draft', 'published', 'scheduled'])) {
        $status_filter = $status_input;
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status_filter;
    } else {
        $status_filter = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';
    }
}

// Lọc theo bài viết nổi bật
if (isset($_GET['is_featured']) && $_GET['is_featured'] !== '') {
    $is_featured_input = validate_int($_GET['is_featured'], 0, 1);
    if ($is_featured_input !== false) {
        $is_featured_filter = $is_featured_input;
        $where_conditions[] = '`is_featured` = ?';
        $where_params[] = $is_featured_filter;
    } else {
        $is_featured_filter = isset($_GET['is_featured']) ? intval($_GET['is_featured']) : -1;
    }
}

// Lọc theo thời gian từ
if (!empty($_GET['date_from'])) {
    $date_from_input = validate_string($_GET['date_from'], 20);
    if ($date_from_input !== false && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from_input)) {
        $date_from = $date_from_input;
        $where_conditions[] = 'DATE(`created_at`) >= ?';
        $where_params[] = $date_from;
    } else {
        $date_from = isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : '';
    }
}

// Lọc theo thời gian đến
if (!empty($_GET['date_to'])) {
    $date_to_input = validate_string($_GET['date_to'], 20);
    if ($date_to_input !== false && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to_input)) {
        $date_to = $date_to_input;
        $where_conditions[] = 'DATE(`created_at`) <= ?';
        $where_params[] = $date_to;
    } else {
        $date_to = isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : '';
    }
}

// Tìm kiếm
if (!empty($_GET['search'])) {
    $search_input = validate_string($_GET['search'], 255, 1);
    if ($search_input !== false) {
        $search = $search_input;
        $where_conditions[] = '(`title` LIKE ? OR `slug` LIKE ? OR `excerpt` LIKE ?)';
        $searchPattern = '%' . $search . '%';
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
    } else {
        $search = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Tổng số bài viết
$countSql = "SELECT COUNT(*) AS total_count FROM `blogs` WHERE $where_clause";
$totalRow = $CMSNT->get_row_safe($countSql, $where_params);
$total = (int)($totalRow['total_count'] ?? 0);
$total_pages = ceil($total / $limit);

// Lấy danh sách bài viết với thông tin tác giả
$listSql = "SELECT b.*, u.`username` as author_name FROM `blogs` b LEFT JOIN `users` u ON b.`author_id` = u.`id` WHERE $where_clause ORDER BY b.`sort_order` ASC, b.`id` DESC LIMIT ?, ?";
$listParams = array_merge($where_params, [$from, $limit]);
$blogs = $CMSNT->get_list_safe($listSql, $listParams);

// Thống kê
$stats_draft = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `blogs` WHERE `status` = 'draft'", []);
$stats_published = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `blogs` WHERE `status` = 'published'", []);
$stats_featured = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `blogs` WHERE `is_featured` = 1", []);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-newspaper me-1"></i><?=__('Quản lý bài viết');?>
            </h1>
            <div class="ms-md-1 ms-0">
                <a href="<?=base_url_admin('blog-add');?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i><?=__('Viết bài mới');?>
                </a>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-primary-transparent rounded-circle">
                                    <i class="fa-solid fa-newspaper fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?=__('Tổng bài viết');?></p>
                                <h4 class="mb-0 fw-semibold"><?=number_format($total);?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-success-transparent rounded-circle">
                                    <i class="fa-solid fa-check-circle fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?=__('Đã xuất bản');?></p>
                                <h4 class="mb-0 fw-semibold text-success"><?=number_format($stats_published['total'] ?? 0);?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar avatar-md bg-warning-transparent rounded-circle">
                                    <i class="fa-solid fa-star fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?=__('Bài nổi bật');?></p>
                                <h4 class="mb-0 fw-semibold text-warning"><?=number_format($stats_featured['total'] ?? 0);?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="row mb-3">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form method="GET" action="<?= base_url(); ?>" class="row g-3">
                            <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                            <input type="hidden" name="action" value="blogs">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label"><?=__('Chuyên mục');?></label>
                                    <select class="form-select" name="category_id">
                                        <option value="0"><?=__('Tất cả chuyên mục');?></option>
                                        <?php
                                        $blog_categories = $CMSNT->get_list_safe("SELECT * FROM `blog_categories` ORDER BY `name` ASC");
                                        foreach($blog_categories as $cat):
                                        ?>
                                        <option value="<?=$cat['id'];?>" <?=$category_filter == $cat['id'] ? 'selected' : '';?>>
                                            <?=htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8'));?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?=__('Tìm kiếm');?></label>
                                    <input type="text" class="form-control" name="search" 
                                        value="<?=htmlspecialchars($search);?>" 
                                        placeholder="<?=__('Tiêu đề, slug, mô tả...');?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?=__('Trạng thái');?></label>
                                    <select class="form-select" name="status">
                                        <option value=""><?=__('Tất cả');?></option>
                                        <option value="draft" <?=$status_filter == 'draft' ? 'selected' : '';?>><?=__('Bản nháp');?></option>
                                        <option value="published" <?=$status_filter == 'published' ? 'selected' : '';?>><?=__('Đã xuất bản');?></option>
                                        <option value="scheduled" <?=$status_filter == 'scheduled' ? 'selected' : '';?>><?=__('Đã lên lịch');?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?=__('Nổi bật');?></label>
                                    <select class="form-select" name="is_featured">
                                        <option value="" <?=$is_featured_filter == -1 ? 'selected' : '';?>><?=__('Tất cả');?></option>
                                        <option value="1" <?=$is_featured_filter == 1 ? 'selected' : '';?>><?=__('Có');?></option>
                                        <option value="0" <?=$is_featured_filter == 0 ? 'selected' : '';?>><?=__('Không');?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?=__('Từ ngày');?></label>
                                    <input type="date" class="form-control" name="date_from" 
                                        value="<?=htmlspecialchars($date_from);?>">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label"><?=__('Đến ngày');?></label>
                                    <input type="date" class="form-control" name="date_to" 
                                        value="<?=htmlspecialchars($date_to);?>">
                                </div>
                                <div class="col-md-12 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fa-solid fa-filter me-1"></i><?=__('Lọc');?>
                                    </button>
                                    <a href="<?=base_url_admin('blogs');?>" class="btn btn-secondary">
                                        <i class="fa-solid fa-times me-1"></i><?=__('Bỏ lọc');?>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách bài viết -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if(count($blogs) > 0): ?>
                        <!-- Thanh công cụ hàng loạt -->
                        <div id="bulkActionsToolbar" class="card-footer bg-light border-bottom d-none">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">
                                        <span id="selectedCount">0</span> <?=__('bài viết đã chọn');?>
                                    </span>
                                </div>
                                <div class="btn-list">
                                    <button type="button" id="btnBulkStatus" class="btn btn-sm btn-warning d-none" onclick="showBulkStatusModal()">
                                        <i class="fa-solid fa-toggle-on me-1"></i><?=__('Đổi trạng thái');?>
                                    </button>
                                    <button type="button" id="btnBulkCategory" class="btn btn-sm btn-info d-none" onclick="showBulkCategoryModal()">
                                        <i class="fa-solid fa-folder me-1"></i><?=__('Đổi chuyên mục');?>
                                    </button>
                                    <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger d-none" onclick="bulkDeleteBlogs()">
                                        <i class="fa-solid fa-trash me-1"></i><?=__('Xóa đã chọn');?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border text-nowrap">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll(this)" style="transform: scale(1.3); cursor: pointer;">
                                        </th>
                                        <th class="text-center" style="width: 50px;"><i class="fa-solid fa-grip-vertical"></i></th>
                                        <th><?=__('Tiêu đề');?></th>
                                        <th><?=__('Chuyên mục');?></th>
                                        <th><?=__('Tác giả');?></th>
                                        <th class="text-center"><?=__('Lượt xem');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th><?=__('Ngày xuất bản');?></th>
                                        <th><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody id="sortable-blogs">
                                    <?php foreach ($blogs as $blog): 
                                        $category = null;
                                        if($blog['category_id'] > 0) {
                                            $category = $CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `id` = ?", [$blog['category_id']]);
                                        }
                                    ?>
                                    <tr id="blog-<?=$blog['id'];?>" data-blog-id="<?=$blog['id'];?>" data-sort-order="<?=$blog['sort_order'];?>">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input blog-checkbox" value="<?=$blog['id'];?>" onchange="updateBulkButtons()" style="transform: scale(1.3); cursor: pointer;">
                                        </td>
                                        <td class="text-center handle-blog" style="cursor: move;">
                                            <i class="fa-solid fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if(!empty($blog['thumbnail'])): ?>
                                                <div class="flex-shrink-0">
                                                    <img src="<?=BASE_URL($blog['thumbnail']);?>" 
                                                        alt="<?=htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8'));?>"
                                                        class="rounded" 
                                                        style="width: 80px; height: 60px; object-fit: cover; border: 1px solid #dee2e6;">
                                                </div>
                                                <?php endif; ?>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold text-truncate" style="max-width: 400px;" title="<?=htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8'));?>">
                                                            <?=htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8'));?>
                                                        </span>
                                                        <?php if($blog['is_featured'] == 1): ?>
                                                        <span class="badge bg-warning-transparent mt-1" style="width: fit-content;">
                                                            <i class="fa-solid fa-star me-1"></i><?=__('Nổi bật');?>
                                                        </span>
                                                        <?php endif; ?>
                                                        <small class="text-muted mt-1">
                                                            <i class="fa-solid fa-link me-1"></i><?=$blog['slug'];?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($category): ?>
                                            <span class="badge bg-info">
                                                <?=htmlspecialchars(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'));?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary"><?=__('Chưa phân loại');?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?=htmlspecialchars($blog['author_name'] ?? 'Admin');?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-transparent">
                                                <i class="fa-solid fa-eye me-1"></i><?=format_cash($blog['views']);?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            $status_badges = [
                                                'draft' => '<span class="badge bg-secondary"><i class="fa-solid fa-file-pen me-1"></i>'.__('Bản nháp').'</span>',
                                                'published' => '<span class="badge bg-success"><i class="fa-solid fa-check-circle me-1"></i>'.__('Đã xuất bản').'</span>',
                                                'scheduled' => '<span class="badge bg-info"><i class="fa-solid fa-clock me-1"></i>'.__('Đã lên lịch').'</span>'
                                            ];
                                            echo $status_badges[$blog['status']] ?? '<span class="badge bg-secondary">'.$blog['status'].'</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if($blog['published_at']): ?>
                                                <i class="fa-regular fa-clock me-1"></i><?=date('d/m/Y H:i', strtotime($blog['published_at']));?>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <a href="<?=base_url_admin('blog-edit&id='.$blog['id']);?>" 
                                                    class="btn btn-sm btn-info">
                                                    <i class="fa-solid fa-edit me-1"></i><?=__('Sửa');?>
                                                </a>
                                                <button onclick="removeBlog('<?=$blog['id'];?>')" 
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fa-solid fa-trash me-1"></i><?=__('Xóa');?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang -->
                        <?php
                            $pagination_url = base_url_admin('blogs');
                            $pagination_url .= '&limit='.$limit;
                            if($category_filter > 0) $pagination_url .= '&category_id='.$category_filter;
                            if(!empty($search)) $pagination_url .= '&search='.urlencode($search);
                            if($status_filter) $pagination_url .= '&status='.$status_filter;
                            if($is_featured_filter != -1) $pagination_url .= '&is_featured='.$is_featured_filter;
                            if($date_from) $pagination_url .= '&date_from='.urlencode($date_from);
                            if($date_to) $pagination_url .= '&date_to='.urlencode($date_to);
                            $pagination_url .= '&';
                            
                            $urlDatatable = pagination($pagination_url, $from, $total, $limit);
                        ?>
                        <?php if($total > $limit): ?>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-sm-12 col-md-5">
                                    <p class="dataTables_info"><?=__('Showing');?> <?=$limit;?> <?=__('of');?> <?=number_format($total);?> <?=__('Results');?></p>
                                </div>
                                <div class="col-sm-12 col-md-7">
                                    <?=$urlDatatable;?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fa-solid fa-exclamation-circle me-2"></i><?=__('Chưa có bài viết nào.');?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal đổi trạng thái hàng loạt -->
<div class="modal fade" id="bulkStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=__('Đổi trạng thái bài viết');?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?=__('Chọn trạng thái mới cho các bài viết đã chọn:');?></p>
                <select class="form-select" id="bulkStatusSelect">
                    <option value="draft"><?=__('Bản nháp');?></option>
                    <option value="published"><?=__('Đã xuất bản');?></option>
                    <option value="scheduled"><?=__('Đã lên lịch');?></option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=__('Hủy');?></button>
                <button type="button" class="btn btn-primary" onclick="confirmBulkStatus()"><?=__('Xác nhận');?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal đổi chuyên mục hàng loạt -->
<div class="modal fade" id="bulkCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?=__('Đổi chuyên mục bài viết');?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?=__('Chọn chuyên mục mới cho các bài viết đã chọn:');?></p>
                <select class="form-select" id="bulkCategorySelect">
                    <option value="0"><?=__('-- Chọn chuyên mục --');?></option>
                    <?php foreach($blog_categories as $cat): ?>
                    <option value="<?=$cat['id'];?>">
                        <?=htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8'));?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?=__('Hủy');?></button>
                <button type="button" class="btn btn-primary" onclick="confirmBulkCategory()"><?=__('Xác nhận');?></button>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>

<script>
// Khởi tạo Sortable
document.addEventListener('DOMContentLoaded', function() {
    var sortableBlogsEl = document.getElementById('sortable-blogs');
    if(sortableBlogsEl) {
        new Sortable(sortableBlogsEl, {
            handle: '.handle-blog',
            animation: 150,
            onEnd: function(evt) {
                updateBlogsOrder();
            }
        });
    }
});

// Cập nhật thứ tự bài viết
function updateBlogsOrder() {
    var order = [];
    $('#sortable-blogs tr').each(function(index) {
        var blogId = $(this).data('blog-id');
        if(blogId) {
            order.push({
                id: blogId,
                sort_order: index
            });
        }
    });

    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'updateBlogsOrder',
            order: JSON.stringify(order)
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            showMessage('<?=__("Đã xảy ra lỗi khi cập nhật thứ tự");?>', 'error');
        }
    });
}

// Xóa bài viết
function removeBlog(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Cảnh báo');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa bài viết này không?');?>",
        confirmText: "<?=__('Đồng ý');?>",
        cancelText: "<?=__('Hủy');?>"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'removeBlog',
                    id: id
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, 'success');
                        $('#blog-' + id).fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        showMessage(result.msg, 'error');
                    }
                },
                error: function() {
                    showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
                }
            });
        }
    });
}

// Chọn tất cả / Bỏ chọn tất cả
function toggleSelectAll(checkbox) {
    $('.blog-checkbox').prop('checked', checkbox.checked);
    updateBulkButtons();
}

// Cập nhật hiển thị nút bulk action
function updateBulkButtons() {
    var selectedCount = $('.blog-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkActionsToolbar').removeClass('d-none');
        $('#btnBulkDelete, #btnBulkStatus, #btnBulkCategory').removeClass('d-none');
    } else {
        $('#bulkActionsToolbar').addClass('d-none');
        $('#btnBulkDelete, #btnBulkStatus, #btnBulkCategory').addClass('d-none');
    }
    
    var totalCheckboxes = $('.blog-checkbox').length;
    $('#selectAll').prop('checked', selectedCount === totalCheckboxes && totalCheckboxes > 0);
}

// Lấy danh sách ID đã chọn
function getSelectedBlogIds() {
    var selectedIds = [];
    $('.blog-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

// Hiển thị modal đổi trạng thái
function showBulkStatusModal() {
    var selectedIds = getSelectedBlogIds();
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một bài viết");?>', 'error');
        return;
    }
    var modal = new bootstrap.Modal(document.getElementById('bulkStatusModal'));
    modal.show();
}

// Xác nhận đổi trạng thái hàng loạt
function confirmBulkStatus() {
    var selectedIds = getSelectedBlogIds();
    var status = $('#bulkStatusSelect').val();
    
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một bài viết");?>', 'error');
        return;
    }
    
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/update.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'bulkUpdateBlogStatus',
            ids: selectedIds,
            status: status
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
                bootstrap.Modal.getInstance(document.getElementById('bulkStatusModal')).hide();
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
        }
    });
}

// Hiển thị modal đổi chuyên mục
function showBulkCategoryModal() {
    var selectedIds = getSelectedBlogIds();
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một bài viết");?>', 'error');
        return;
    }
    var modal = new bootstrap.Modal(document.getElementById('bulkCategoryModal'));
    modal.show();
}

// Xác nhận đổi chuyên mục hàng loạt
function confirmBulkCategory() {
    var selectedIds = getSelectedBlogIds();
    var categoryId = $('#bulkCategorySelect').val();
    
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một bài viết");?>', 'error');
        return;
    }
    
    $.ajax({
        url: "<?=BASE_URL('ajaxs/admin/update.php');?>",
        type: 'POST',
        dataType: "JSON",
        data: {
            action: 'bulkUpdateBlogCategory',
            ids: selectedIds,
            category_id: categoryId
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
                bootstrap.Modal.getInstance(document.getElementById('bulkCategoryModal')).hide();
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
        }
    });
}

// Xóa hàng loạt bài viết
function bulkDeleteBlogs() {
    var selectedIds = getSelectedBlogIds();
    
    if (selectedIds.length === 0) {
        showMessage('<?=__("Vui lòng chọn ít nhất một bài viết để xóa");?>', 'error');
        return;
    }
    
    Swal.fire({
        title: "<?=__('Cảnh báo');?>",
        text: "<?=__('Bạn có chắc chắn muốn xóa');?> " + selectedIds.length + " <?=__('bài viết đã chọn không? Hành động này không thể hoàn tác.');?>",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: "<?=__('Đồng ý');?>",
        cancelButtonText: "<?=__('Hủy');?>",
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false,
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'bulkDeleteBlogs',
                    ids: selectedIds
                },
                beforeSend: function() {
                    $('#btnBulkDelete').prop('disabled', true);
                    $('#btnBulkDelete').html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?=__("Đang xóa...");?>');
                },
                success: function(result) {
                    $('#btnBulkDelete').prop('disabled', false);
                    $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?=__("Xóa đã chọn");?>');
                    
                    if (result.status == 'success') {
                        showMessage(result.msg, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(result.msg, 'error');
                    }
                },
                error: function() {
                    $('#btnBulkDelete').prop('disabled', false);
                    $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?=__("Xóa đã chọn");?>');
                    showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
                }
            });
        }
    });
}
</script>

