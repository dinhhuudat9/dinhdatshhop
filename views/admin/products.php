<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý sản phẩm') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_product') != true) {
    $role_name = getRoleName('view_product');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

// Phân trang an toàn
$limit = isset($_GET['limit']) ? (validate_int($_GET['limit'], 1, 1000) ?: 20) : 20;
$page = isset($_GET['page']) ? (validate_int($_GET['page'], 1, 1000000) ?: 1) : 1;
$from = ($page - 1) * $limit;

// Biến giữ giá trị hiển thị lại
$category_filter = 0;
$supplier_filter = '';
$search = '';
$status_filter = '';
$date_from = '';
$date_to = '';

// WHERE an toàn với prepared statements
$where_conditions = ["1 = 1"];
$where_params = [];

// Lọc theo danh mục (sử dụng FIND_IN_SET với category_ids)
if (!empty($_GET['category_id'])) {
    $category_filter_input = validate_int($_GET['category_id'], 1);
    if ($category_filter_input !== false) {
        $category_filter = $category_filter_input;
        $where_conditions[] = 'FIND_IN_SET(?, `category_ids`) > 0';
        $where_params[] = $category_filter;
    } else {
        // Giữ giá trị để hiển thị lại trong form (nhưng không dùng trong query)
        $category_filter = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    }
}

// Lọc theo nguồn API (supplier)
if (isset($_GET['supplier_id']) && $_GET['supplier_id'] !== '') {
    if ($_GET['supplier_id'] === '0') {
        // Lọc sản phẩm không có supplier (thủ công)
        $supplier_filter = '0';
        $where_conditions[] = '(`supplier_id` IS NULL OR `supplier_id` = 0)';
    } else {
        $supplier_filter_input = validate_int($_GET['supplier_id'], 1);
        if ($supplier_filter_input !== false) {
            $supplier_filter = $supplier_filter_input;
            $where_conditions[] = '`supplier_id` = ?';
            $where_params[] = $supplier_filter;
        }
    }
}

// Lọc theo trạng thái
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status_input = validate_string($_GET['status'], 20);
    if ($status_input !== false && in_array($status_input, ['active', 'inactive'])) {
        $status_filter = $status_input;
        if ($status_input == 'active') {
            $where_conditions[] = '`status` = ?';
            $where_params[] = 1;
        } else {
            $where_conditions[] = '`status` = ?';
            $where_params[] = 0;
        }
    } else {
        $status_filter = isset($_GET['status']) ? htmlspecialchars($_GET['status']) : '';
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

// Tìm kiếm (LIKE)
if (!empty($_GET['search'])) {
    $search_input = validate_string($_GET['search'], 255, 1);
    if ($search_input !== false) {
        $search = $search_input;
        $where_conditions[] = '(`name` LIKE ? OR `slug` LIKE ? OR `description` LIKE ?)';
        $searchPattern = '%' . $search . '%';
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
    } else {
        // Giữ giá trị để hiển thị lại trong form (nhưng không dùng trong query)
        $search = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Tổng số sản phẩm
$countSql = "SELECT COUNT(*) AS total_count FROM `products` WHERE $where_clause";
$totalRow = $CMSNT->get_row_safe($countSql, $where_params);
$total = (int)($totalRow['total_count'] ?? 0);
$total_pages = ceil($total / $limit);

// Lấy danh sách sản phẩm
$listSql = "SELECT * FROM `products` WHERE $where_clause ORDER BY `sort_order` ASC, `id` DESC LIMIT ?, ?";
$listParams = array_merge($where_params, [$from, $limit]);
$products = $CMSNT->get_list_safe($listSql, $listParams);

// Lấy danh sách suppliers cho filter
$suppliers_list = $CMSNT->get_list_safe("SELECT `id`, `domain`, `type` FROM `suppliers` WHERE `status` = 1 ORDER BY `domain` ASC", []);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-box me-1"></i><?= __('Quản lý sản phẩm'); ?>
            </h1>
            <div class="ms-md-1 ms-0">
                <a href="<?= base_url_admin('product-add'); ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i><?= __('Thêm sản phẩm'); ?>
                </a>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="card custom-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center" style="cursor: pointer;" onclick="toggleFilterForm()">
                <h6 class="mb-0">
                    <i class="fa-solid fa-filter me-2"></i><?= __('Bộ lọc tìm kiếm'); ?>
                </h6>
                <button type="button" class="btn btn-sm btn-light" id="toggleFilterBtn">
                    <i class="fa-solid fa-chevron-down" id="filterIcon"></i>
                </button>
            </div>
            <div class="card-body" id="filterFormBody" style="display: none;">
                <form method="GET" action="<?= base_url(); ?>" class="row g-3">
                    <input type="hidden" name="module" value="<?= $CMSNT->site('path_admin'); ?>">
                    <input type="hidden" name="action" value="products">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Chuyên mục'); ?></label>
                            <select class="form-select" name="category_id" id="filter_category_id">
                                <option value="0"><?= __('Tất cả chuyên mục'); ?></option>
                                <?php
                                $categories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` != 0 ORDER BY `name` ASC");
                                foreach ($categories as $category):
                                    $parent = $CMSNT->get_row_safe("SELECT `name` FROM `categories` WHERE `id` = ?", [$category['parent_id']]);
                                ?>
                                    <option value="<?= $category['id']; ?>" <?= $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?= $parent['name']; ?> - <?= $category['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Nguồn API'); ?></label>
                            <select class="form-select" name="supplier_id" id="filter_supplier_id">
                                <option value=""><?= __('Tất cả nguồn'); ?></option>
                                <option value="0" <?= $supplier_filter === '0' ? 'selected' : ''; ?>><?= __('Thủ công (Không API)'); ?></option>
                                <?php foreach ($suppliers_list as $sup): ?>
                                    <option value="<?= $sup['id']; ?>" <?= $supplier_filter == $sup['id'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($sup['domain']); ?> (<?= $sup['type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Tìm kiếm'); ?></label>
                            <input type="text" class="form-control" name="search"
                                value="<?= htmlspecialchars($search); ?>"
                                placeholder="<?= __('Tên sản phẩm, slug, mô tả...'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Trạng thái'); ?></label>
                            <select class="form-select" name="status">
                                <option value=""><?= __('Tất cả'); ?></option>
                                <option value="active" <?= $status_filter == 'active' ? 'selected' : ''; ?>><?= __('Đang hoạt động'); ?></option>
                                <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : ''; ?>><?= __('Đã tắt'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Từ ngày'); ?></label>
                            <input type="date" class="form-control" name="date_from"
                                value="<?= htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><?= __('Đến ngày'); ?></label>
                            <input type="date" class="form-control" name="date_to"
                                value="<?= htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label"><?= __('Số lượng/trang'); ?></label>
                            <select class="form-select" name="limit">
                                <option value="10" <?= $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?= $limit == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="50" <?= $limit == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?= $limit == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                        <div class="col-md-12 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa-solid fa-filter me-1"></i><?= __('Lọc'); ?>
                            </button>
                            <a href="<?= base_url_admin('products'); ?>" class="btn btn-secondary">
                                <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if (count($products) > 0): ?>
                            <!-- Thanh công cụ hàng loạt -->
                            <div id="bulkActionsToolbar" class="card-footer bg-light border-bottom d-none">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">
                                            <span id="selectedCount">0</span> <?= __('sản phẩm đã chọn'); ?>
                                        </span>
                                    </div>
                                    <div class="btn-list">
                                        <button type="button" id="btnBulkQuickUpdate" class="btn btn-sm btn-primary d-none" onclick="showBulkQuickUpdateModal()">
                                            <i class="fa-solid fa-bolt me-1"></i><?= __('Cập nhật nhanh'); ?>
                                        </button>
                                        <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger d-none" onclick="bulkDeleteProducts()">
                                            <i class="fa-solid fa-trash me-1"></i><?= __('Xóa đã chọn'); ?>
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
                                            <th><?= __('Tên sản phẩm'); ?></th>
                                            <th><?= __('Chuyên mục'); ?></th>
                                            <th><?= __('Nguồn API'); ?></th>
                                            <th class="text-center"><?= __('Số lượng gói'); ?></th>
                                            <th class="text-center"><?= __('Trạng thái'); ?></th>
                                            <th><?= __('Ngày tạo'); ?></th>
                                            <th><?= __('Thao tác'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortable-products">
                                        <?php foreach ($products as $product):
                                            // Lấy tất cả categories từ category_ids
                                            $categoryIds = array_filter(explode(',', $product['category_ids'] ?? ''));
                                            $productCategories = [];
                                            foreach ($categoryIds as $catId) {
                                                $cat = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ?", [$catId]);
                                                if ($cat) {
                                                    $parent = $CMSNT->get_row_safe("SELECT `name` FROM `categories` WHERE `id` = ?", [$cat['parent_id']]);
                                                    $productCategories[] = [
                                                        'category' => $cat,
                                                        'parent' => $parent
                                                    ];
                                                }
                                            }
                                            // Đếm số lượng gói sản phẩm
                                            $plan_count = $CMSNT->get_row_safe("SELECT COUNT(*) AS total FROM `product_plans` WHERE `product_id` = ?", [$product['id']]);
                                            $total_plans = (int)($plan_count['total'] ?? 0);
                                            // Lấy thông tin supplier nếu có
                                            $supplier_info = null;
                                            if (!empty($product['supplier_id']) && $product['supplier_id'] > 0) {
                                                $supplier_info = $CMSNT->get_row_safe("SELECT `id`, `domain`, `type` FROM `suppliers` WHERE `id` = ?", [$product['supplier_id']]);
                                            }
                                        ?>
                                            <tr id="product-<?= $product['id']; ?>" data-product-id="<?= $product['id']; ?>" data-sort-order="<?= isset($product['sort_order']) ? $product['sort_order'] : 0; ?>">
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input product-checkbox" value="<?= $product['id']; ?>" onchange="updateBulkButtons()" style="transform: scale(1.3); cursor: pointer;">
                                                </td>
                                                <td class="text-center handle-product" style="cursor: move;">
                                                    <i class="fa-solid fa-grip-vertical text-muted"></i>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <div class="flex-shrink-0">
                                                                <img src="<?= BASE_URL($product['image']); ?>"
                                                                    alt="<?= htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?>"
                                                                    class="rounded"
                                                                    style="width: 60px; height: 60px; object-fit: cover; border: 1px solid #dee2e6;">
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex-shrink-0">
                                                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                                    style="width: 60px; height: 60px; border: 1px solid #dee2e6;">
                                                                    <i class="fa-solid fa-image text-muted"></i>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-bold text-truncate" style="max-width: 400px;" title="<?= htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?>">
                                                                    <?= htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?>
                                                                </span>
                                                                <small class="text-muted">
                                                                    <i class="fa-solid fa-link me-1"></i><?= $product['slug']; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($productCategories)): ?>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <?php foreach ($productCategories as $catData): ?>
                                                                <span class="badge bg-info">
                                                                    <?= $catData['parent'] ? $catData['parent']['name'] . ' - ' : ''; ?><?= $catData['category']['name']; ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= __('Chưa phân loại'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($supplier_info): ?>
                                                        <span class="badge bg-success-transparent" title="<?= htmlspecialchars($supplier_info['domain']); ?>">
                                                            <i class="fa-solid fa-plug me-1"></i><?= htmlspecialchars($supplier_info['domain']); ?>
                                                        </span>
                                                        <span class="badge bg-info-transparent ms-1">
                                                            <?= $supplier_info['type']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-transparent">
                                                            <i class="fa-solid fa-hand me-1"></i><?= __('Thủ công'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary-transparent fs-6">
                                                        <i class="fa-solid fa-box-open me-1"></i><?= format_cash($total_plans); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="status<?= $product['id']; ?>"
                                                            <?= $product['status'] == 1 ? 'checked' : ''; ?>
                                                            onchange="updateStatus('<?= $product['id']; ?>', this.checked ? 1 : 0)"
                                                            style="transform: scale(1.5);">
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="fa-regular fa-clock me-1"></i><?= $product['created_at']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-list">
                                                        <a href="<?= base_url_admin('product-plans&product_id=' . $product['id']); ?>"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fa-solid fa-box-open me-1"></i><?= __('Quản lý gói'); ?>
                                                        </a>
                                                        <a href="<?= base_url_admin('product-edit&id=' . $product['id']); ?>"
                                                            class="btn btn-sm btn-info">
                                                            <i class="fa-solid fa-edit me-1"></i><?= __('Sửa'); ?>
                                                        </a>
                                                        <button onclick="removeProduct('<?= $product['id']; ?>')"
                                                            class="btn btn-sm btn-danger">
                                                            <i class="fa-solid fa-trash me-1"></i><?= __('Xóa'); ?>
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
                            // Tạo URL pagination với các tham số filter
                            $pagination_url = base_url_admin('products');
                            $pagination_url .= '&limit=' . $limit;
                            if ($category_filter > 0) $pagination_url .= '&category_id=' . $category_filter;
                            if ($supplier_filter !== '') $pagination_url .= '&supplier_id=' . $supplier_filter;
                            if (!empty($search)) $pagination_url .= '&search=' . urlencode($search);
                            if ($status_filter) $pagination_url .= '&status=' . $status_filter;
                            if ($date_from) $pagination_url .= '&date_from=' . urlencode($date_from);
                            if ($date_to) $pagination_url .= '&date_to=' . urlencode($date_to);
                            $pagination_url .= '&';

                            $urlDatatable = pagination($pagination_url, $from, $total, $limit);
                            ?>
                            <?php if ($total > $limit): ?>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-5">
                                            <p class="dataTables_info"><?= __('Showing'); ?> <?= $limit; ?> <?= __('of'); ?> <?= number_format($total); ?> <?= __('Results'); ?></p>
                                        </div>
                                        <div class="col-sm-12 col-md-7">
                                            <?= $urlDatatable; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-exclamation-circle me-2"></i><?= __('Chưa có sản phẩm nào.'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cập nhật nhanh sản phẩm hàng loạt -->
<div class="modal fade" id="bulkQuickUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?= __('Cập nhật nhanh'); ?> <span class="badge bg-primary" id="bulkUpdateSelectedCount">0</span> <?= __('sản phẩm đã chọn'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-4">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <strong><?= __('Hướng dẫn:'); ?></strong> <?= __('Chỉ nhập vào các trường bạn muốn thay đổi. Để trống nếu muốn giữ nguyên giá trị hiện tại.'); ?>
                </div>

                <form id="bulkQuickUpdateForm">
                    <div class="row">
                        <!-- Chuyên mục -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= __('Chuyên mục'); ?></label>
                                <select class="form-select" id="bulk_category_id" name="category_id">
                                    <option value=""><?= __('Giữ nguyên chuyên mục hiện tại'); ?></option>
                                    <?php
                                    $all_categories = $CMSNT->get_list_safe("SELECT * FROM `categories` WHERE `parent_id` != 0 ORDER BY `name` ASC");
                                    foreach ($all_categories as $cat):
                                        $parent = $CMSNT->get_row_safe("SELECT `name` FROM `categories` WHERE `id` = ?", [$cat['parent_id']]);
                                    ?>
                                        <option value="<?= $cat['id']; ?>">
                                            <?= $parent['name']; ?> - <?= $cat['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Trạng thái -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><?= __('Trạng thái'); ?></label>
                                <select class="form-select" id="bulk_status" name="status">
                                    <option value=""><?= __('Giữ nguyên trạng thái hiện tại'); ?></option>
                                    <option value="1"><?= __('Kích hoạt'); ?></option>
                                    <option value="0"><?= __('Tắt'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Số lượng đã bán -->
                        <div class="col-md-12">
                            <div class="card mb-3">
                                <div class="card-header bg-warning text-dark py-2">
                                    <i class="fa-solid fa-chart-line me-1"></i><?= __('Số lượng đã bán'); ?>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Đặt số lượng cụ thể -->
                                        <div class="col-md-6">
                                            <label class="form-label"><?= __('Đặt số lượng cụ thể'); ?></label>
                                            <input type="number" class="form-control" id="bulk_sold_count" name="sold_count" min="0" placeholder="<?= __('Nhập số lượng đã bán mới'); ?>">
                                            <small class="text-muted"><?= __('Đặt số lượng đã bán cố định cho tất cả sản phẩm đã chọn'); ?></small>
                                        </div>

                                        <!-- Điều chỉnh số lượng -->
                                        <div class="col-md-6">
                                            <label class="form-label"><?= __('Hoặc điều chỉnh số lượng'); ?></label>
                                            <div class="input-group">
                                                <select class="form-select" id="bulk_sold_adjust_type" style="max-width: 100px;">
                                                    <option value="add"><?= __('Cộng'); ?></option>
                                                    <option value="subtract"><?= __('Trừ'); ?></option>
                                                </select>
                                                <input type="number" class="form-control" id="bulk_sold_adjust_value" min="0" placeholder="<?= __('Số lượng'); ?>">
                                            </div>
                                            <small class="text-muted"><?= __('Cộng/trừ vào số lượng đã bán hiện tại'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Đóng'); ?></button>
                <button type="button" class="btn btn-primary" id="btnSubmitBulkQuickUpdate" onclick="submitBulkQuickUpdate()">
                    <i class="fa-solid fa-save me-1"></i><?= __('Cập Nhật'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    // Khởi tạo tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Khởi tạo Sortable cho bảng products
    document.addEventListener('DOMContentLoaded', function() {
        var sortableProductsEl = document.getElementById('sortable-products');
        if (sortableProductsEl) {
            new Sortable(sortableProductsEl, {
                handle: '.handle-product',
                animation: 150,
                onEnd: function(evt) {
                    updateProductsOrder();
                }
            });
        }

        // Khôi phục trạng thái filter form từ localStorage
        var isFilterExpanded = localStorage.getItem('products_filter_expanded');
        <?php
        // Tự động mở nếu có filter đang active
        $has_active_filter = $category_filter > 0 || $supplier_filter !== '' || !empty($search)
            || $status_filter !== '' || !empty($date_from) || !empty($date_to);
        ?>
        <?php if ($has_active_filter): ?>
            // Có filter đang active, tự động mở
            document.getElementById('filterFormBody').style.display = 'block';
            document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
        <?php else: ?>
            // Không có filter, kiểm tra localStorage
            if (isFilterExpanded === 'true') {
                document.getElementById('filterFormBody').style.display = 'block';
                document.getElementById('filterIcon').className = 'fa-solid fa-chevron-up';
            }
        <?php endif; ?>
    });

    // Toggle filter form
    function toggleFilterForm() {
        var filterBody = document.getElementById('filterFormBody');
        var filterIcon = document.getElementById('filterIcon');

        if (filterBody.style.display === 'none') {
            filterBody.style.display = 'block';
            filterIcon.className = 'fa-solid fa-chevron-up';
            localStorage.setItem('products_filter_expanded', 'true');
        } else {
            filterBody.style.display = 'none';
            filterIcon.className = 'fa-solid fa-chevron-down';
            localStorage.setItem('products_filter_expanded', 'false');
        }
    }

    // Cập nhật thứ tự sản phẩm
    function updateProductsOrder() {
        var order = [];
        $('#sortable-products tr').each(function(index) {
            var productId = $(this).data('product-id');
            if (productId) {
                order.push({
                    id: productId,
                    sort_order: index
                });
            }
        });

        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateProductsOrder',
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
                showMessage('<?= __("Đã xảy ra lỗi khi cập nhật thứ tự"); ?>', 'error');
            }
        });
    }

    // Cập nhật trạng thái sản phẩm
    function updateStatus(id, status) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateProductStatus',
                id: id,
                status: status
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
                } else {
                    showMessage(result.msg, result.status);
                    // Khôi phục lại checkbox nếu lỗi
                    $('#status' + id).prop('checked', !status);
                }
            },
            error: function() {
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                $('#status' + id).prop('checked', !status);
            }
        });
    }

    // Xóa sản phẩm đơn lẻ
    function removeProduct(id) {
        // Reset modal state
        $('#confirmSingleDeleteCheckbox').prop('checked', false);
        $('#confirmSingleDeleteButton').prop('disabled', true).data('product-id', id);
        $('#singleDeleteProductInfo').html('<div class="text-center"><i class="fa-solid fa-spinner fa-spin"></i> <?= __("Đang tải..."); ?></div>');
        $('#singleDeletePlansCount').text('...');

        // Show modal
        $('#confirmSingleDeleteModal').modal('show');

        // Fetch product details
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'previewBulkDeleteProducts',
                ids: [id]
            },
            success: function(result) {
                if (result.status == 'success' && result.data.products.length > 0) {
                    var product = result.data.products[0];
                    $('#singleDeletePlansCount').text(product.plans_count);

                    // Build product info HTML
                    var html = '<div class="d-flex align-items-center p-2 bg-light rounded">';
                    if (product.image) {
                        html += '<img src="' + product.image + '" class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">';
                    } else {
                        html += '<div class="bg-secondary-transparent rounded me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fa-solid fa-image text-muted fs-4"></i></div>';
                    }
                    html += '<div>';
                    html += '<div class="fw-bold">' + product.name + '</div>';
                    html += '<small class="text-muted">ID: ' + product.id + ' • ' + product.plans_count + ' <?= __("gói sản phẩm"); ?></small>';
                    html += '</div>';
                    html += '</div>';
                    $('#singleDeleteProductInfo').html(html);
                } else {
                    $('#singleDeleteProductInfo').html('<div class="text-danger"><?= __("Không tìm thấy sản phẩm"); ?></div>');
                }
            },
            error: function() {
                $('#singleDeleteProductInfo').html('<div class="text-danger"><?= __("Không thể tải dữ liệu"); ?></div>');
            }
        });

        // Handle checkbox change
        $('#confirmSingleDeleteCheckbox').off('change').on('change', function() {
            $('#confirmSingleDeleteButton').prop('disabled', !$(this).prop('checked'));
        });

        // Handle confirm button click
        $('#confirmSingleDeleteButton').off('click').on('click', function() {
            if (!$('#confirmSingleDeleteCheckbox').prop('checked')) return;

            var productId = $(this).data('product-id');
            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang xóa..."); ?>');

            $.ajax({
                url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'removeProduct',
                    id: productId
                },
                success: function(result) {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa sản phẩm"); ?>');

                    if (result.status == 'success') {
                        $('#confirmSingleDeleteModal').modal('hide');
                        showMessage(result.msg, 'success');
                        $('#product-' + productId).fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        showMessage(result.msg, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa sản phẩm"); ?>');
                    showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                }
            });
        });
    }

    // Chọn tất cả / Bỏ chọn tất cả
    function toggleSelectAll(checkbox) {
        $('.product-checkbox').prop('checked', checkbox.checked);
        updateBulkButtons();
    }

    // Cập nhật hiển thị nút bulk action
    function updateBulkButtons() {
        var selectedCount = $('.product-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);

        if (selectedCount > 0) {
            $('#bulkActionsToolbar').removeClass('d-none');
            $('#btnBulkDelete, #btnBulkQuickUpdate').removeClass('d-none');
        } else {
            $('#bulkActionsToolbar').addClass('d-none');
            $('#btnBulkDelete, #btnBulkQuickUpdate').addClass('d-none');
        }

        // Cập nhật trạng thái checkbox "Chọn tất cả"
        var totalCheckboxes = $('.product-checkbox').length;
        $('#selectAll').prop('checked', selectedCount === totalCheckboxes && totalCheckboxes > 0);
    }

    // Lấy danh sách ID đã chọn
    function getSelectedProductIds() {
        var selectedIds = [];
        $('.product-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }

    // ==================== BULK QUICK UPDATE ====================

    var bulkQuickUpdateModal;

    document.addEventListener('DOMContentLoaded', function() {
        bulkQuickUpdateModal = new bootstrap.Modal(document.getElementById('bulkQuickUpdateModal'));
    });

    // Hiển thị modal cập nhật nhanh
    function showBulkQuickUpdateModal() {
        var selectedIds = getSelectedProductIds();

        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một sản phẩm"); ?>', 'error');
            return;
        }

        // Reset form
        $('#bulkQuickUpdateForm')[0].reset();

        // Hiển thị số lượng
        $('#bulkUpdateSelectedCount').text(selectedIds.length);

        // Show modal
        bulkQuickUpdateModal.show();
    }

    // Submit cập nhật nhanh hàng loạt
    function submitBulkQuickUpdate() {
        var selectedIds = getSelectedProductIds();

        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một sản phẩm"); ?>', 'error');
            return;
        }

        // Chuẩn bị data - chỉ gửi các field có giá trị
        var updateData = {
            action: 'bulkQuickUpdateProducts',
            ids: selectedIds,
            csrf_token: getCSRFToken(),
            fields: {}
        };

        // Chuyên mục
        var categoryId = $('#bulk_category_id').val();
        if (categoryId !== '') {
            updateData.fields.category_id = categoryId;
        }

        // Trạng thái
        var status = $('#bulk_status').val();
        if (status !== '') {
            updateData.fields.status = status;
        }

        // Số lượng đã bán (đặt cụ thể)
        var soldCount = $('#bulk_sold_count').val();
        if (soldCount !== '' && soldCount !== null) {
            updateData.fields.sold_count = parseInt(soldCount);
        }

        // Điều chỉnh số lượng đã bán
        var soldAdjustValue = $('#bulk_sold_adjust_value').val();
        if (soldAdjustValue && parseInt(soldAdjustValue) > 0) {
            updateData.fields.sold_adjust_type = $('#bulk_sold_adjust_type').val();
            updateData.fields.sold_adjust_value = parseInt(soldAdjustValue);
        }
        // Kiểm tra có field nào để cập nhật không
        if (Object.keys(updateData.fields).length === 0) {
            showMessage('<?= __("Vui lòng nhập ít nhất một trường để cập nhật"); ?>', 'error');
            return;
        }

        // Xác nhận
        Swal.fire({
            title: "<?= __('Xác nhận cập nhật'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn cập nhật'); ?> " + selectedIds.length + " <?= __('sản phẩm đã chọn?'); ?>",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Hủy'); ?>",
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                var $btn = $('#btnSubmitBulkQuickUpdate');
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang cập nhật..."); ?>');

                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: updateData,
                    success: function(result) {
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i><?= __("Cập Nhật"); ?>');

                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            bulkQuickUpdateModal.hide();
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, 'error');
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i><?= __("Cập Nhật"); ?>');
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                    }
                });
            }
        });
    }

    // Xóa hàng loạt sản phẩm
    function bulkDeleteProducts() {
        var selectedIds = getSelectedProductIds();

        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một sản phẩm để xóa"); ?>', 'error');
            return;
        }

        // Reset modal state
        $('#confirmBulkDeleteCheckbox').prop('checked', false);
        $('#confirmBulkDeleteButton').prop('disabled', true);
        $('#bulkDeleteProductsList').html('<div class="text-center"><i class="fa-solid fa-spinner fa-spin"></i> <?= __("Đang tải..."); ?></div>');
        $('#bulkDeletePlansCount').text('...');
        $('#bulkDeleteProductsCount').text(selectedIds.length);

        // Show modal
        $('#confirmBulkDeleteModal').modal('show');

        // Fetch preview data
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/view.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'previewBulkDeleteProducts',
                ids: selectedIds
            },
            success: function(result) {
                if (result.status == 'success') {
                    $('#bulkDeletePlansCount').text(result.data.total_plans);

                    // Build product list HTML
                    var listHtml = '';
                    if (result.data.products && result.data.products.length > 0) {
                        listHtml = '<div class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">';
                        result.data.products.forEach(function(product) {
                            listHtml += '<div class="list-group-item d-flex align-items-center py-2">';
                            if (product.image) {
                                listHtml += '<img src="' + product.image + '" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">';
                            } else {
                                listHtml += '<div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fa-solid fa-image text-muted"></i></div>';
                            }
                            listHtml += '<div class="flex-grow-1">';
                            listHtml += '<div class="fw-semibold text-truncate" style="max-width: 300px;">' + product.name + '</div>';
                            listHtml += '<small class="text-muted">' + product.plans_count + ' <?= __("gói sản phẩm"); ?></small>';
                            listHtml += '</div>';
                            listHtml += '</div>';
                        });
                        listHtml += '</div>';
                    } else {
                        listHtml = '<div class="text-muted text-center py-2"><?= __("Không có sản phẩm"); ?></div>';
                    }
                    $('#bulkDeleteProductsList').html(listHtml);
                } else {
                    $('#bulkDeleteProductsList').html('<div class="text-danger">' + result.msg + '</div>');
                }
            },
            error: function() {
                $('#bulkDeleteProductsList').html('<div class="text-danger"><?= __("Không thể tải dữ liệu"); ?></div>');
            }
        });

        // Handle checkbox change
        $('#confirmBulkDeleteCheckbox').off('change').on('change', function() {
            $('#confirmBulkDeleteButton').prop('disabled', !$(this).prop('checked'));
        });

        // Handle confirm button click
        $('#confirmBulkDeleteButton').off('click').on('click', function() {
            if (!$('#confirmBulkDeleteCheckbox').prop('checked')) return;

            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang xóa..."); ?>');

            $.ajax({
                url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'bulkDeleteProducts',
                    ids: selectedIds
                },
                success: function(result) {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa sản phẩm"); ?>');

                    if (result.status == 'success') {
                        $('#confirmBulkDeleteModal').modal('hide');
                        showMessage(result.msg, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(result.msg, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa sản phẩm"); ?>');
                    showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                }
            });
        });
    }
</script>

<!-- Modal Xác nhận xóa hàng loạt sản phẩm -->
<div class="modal fade" id="confirmBulkDeleteModal" tabindex="-1" aria-labelledby="confirmBulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmBulkDeleteModalLabel">
                    <i class="fa-solid fa-trash me-2"></i>
                    <?= __('Xác nhận xóa sản phẩm'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa'); ?> <span class="badge bg-danger" id="bulkDeleteProductsCount">0</span> <?= __('sản phẩm đã chọn'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả gói sản phẩm liên quan'); ?> (<span id="bulkDeletePlansCount">0</span> <?= __('gói'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả trường tùy chỉnh của gói'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                            <?= __('Ảnh sản phẩm sẽ bị xóa khỏi hệ thống'); ?>
                        </li>
                    </ul>
                </div>

                <div class="mb-3">
                    <h6 class="mb-2"><i class="fa-solid fa-list me-1"></i> <?= __('Danh sách sản phẩm sẽ xóa:'); ?></h6>
                    <div id="bulkDeleteProductsList" class="border rounded p-2">
                        <!-- Product list will be loaded here -->
                    </div>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmBulkDeleteCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmBulkDeleteCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa toàn bộ sản phẩm đã chọn'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmBulkDeleteButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa sản phẩm đơn lẻ -->
<div class="modal fade" id="confirmSingleDeleteModal" tabindex="-1" aria-labelledby="confirmSingleDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmSingleDeleteModalLabel">
                    <i class="fa-solid fa-trash me-2"></i>
                    <?= __('Xác nhận xóa sản phẩm'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="mb-2"><i class="fa-solid fa-cube me-1"></i> <?= __('Sản phẩm sẽ xóa:'); ?></h6>
                    <div id="singleDeleteProductInfo" class="border rounded p-2">
                        <!-- Product info will be loaded here -->
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa sản phẩm này khỏi hệ thống'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả gói sản phẩm liên quan'); ?> (<span id="singleDeletePlansCount">0</span> <?= __('gói'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả trường tùy chỉnh của gói'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                            <?= __('Ảnh sản phẩm sẽ bị xóa khỏi hệ thống'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmSingleDeleteCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmSingleDeleteCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa sản phẩm này'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmSingleDeleteButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Select2 JS Library -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Khởi tạo Select2 cho filter dropdown
    $(document).ready(function() {
        // Select2 cho chuyên mục
        $('#filter_category_id').select2({
            placeholder: '<?= __("Tìm kiếm chuyên mục..."); ?>',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return '<?= __("Không tìm thấy kết quả"); ?>';
                },
                searching: function() {
                    return '<?= __("Đang tìm..."); ?>';
                }
            }
        });

        // Select2 cho nguồn API
        $('#filter_supplier_id').select2({
            placeholder: '<?= __("Tìm kiếm nguồn API..."); ?>',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return '<?= __("Không tìm thấy kết quả"); ?>';
                },
                searching: function() {
                    return '<?= __("Đang tìm..."); ?>';
                }
            }
        });
    });
</script>