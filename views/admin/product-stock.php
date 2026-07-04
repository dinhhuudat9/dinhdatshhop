<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý kho hàng') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
    $role_name = getRoleName('edit_product_stock');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

// Lấy plan_id từ URL
$plan_id = isset($_GET['plan_id']) ? validate_int($_GET['plan_id'], 1) : 0;
if (!$plan_id) {
    die('<script type="text/javascript">if(!alert("' . __('Plan ID không hợp lệ') . '")){window.history.back();}</script>');
}

// Lấy thông tin gói sản phẩm
$plan = $CMSNT->get_row_safe("SELECT pp.*, p.name as product_name FROM `product_plans` pp LEFT JOIN `products` p ON pp.product_id = p.id WHERE pp.id = ?", [$plan_id]);
if (!$plan) {
    die('<script type="text/javascript">if(!alert("' . __('Gói sản phẩm không tồn tại') . '")){window.history.back();}</script>');
}

// Kiểm tra gói có phải giao ngay không
if (!isset($plan['is_instant']) || (int)$plan['is_instant'] != 1) {
    die('<script type="text/javascript">if(!alert("' . __('Chỉ gói sản phẩm giao ngay mới có thể quản lý kho hàng') . '")){window.history.back();}</script>');
}

// Phân trang an toàn
$limit = isset($_GET['limit']) ? (validate_int($_GET['limit'], 1, 1000) ?: 20) : 20;
$page = isset($_GET['page']) ? (validate_int($_GET['page'], 1, 1000000) ?: 1) : 1;
$from = ($page - 1) * $limit;

// Biến giữ giá trị hiển thị lại
$status_filter = -1;
$search = '';

// WHERE an toàn với prepared statements
$where_conditions = ["`plan_id` = ?"];
$where_params = [$plan_id];

// Lọc theo trạng thái
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status_filter_input = validate_int($_GET['status'], 0, 1);
    if ($status_filter_input !== false) {
        $status_filter = $status_filter_input;
        $where_conditions[] = '`status` = ?';
        $where_params[] = $status_filter;
    } else {
        $status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
    }
}

// Tìm kiếm
if (!empty($_GET['search'])) {
    $search_input = validate_string($_GET['search'], 255, 1);
    if ($search_input !== false) {
        $search = $search_input;
        $where_conditions[] = '`stock_value` LIKE ?';
        $where_params[] = '%' . $search . '%';
    } else {
        $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
    }
}

$where_sql = implode(' AND ', $where_conditions);

// Đếm tổng số lượng
$total_query = "SELECT COUNT(*) as total FROM `product_stock` WHERE " . $where_sql;
$total_result = $CMSNT->get_row_safe($total_query, $where_params);
$total = $total_result ? (int)$total_result['total'] : 0;
$total_pages = ceil($total / $limit);

// Lấy danh sách kho hàng
$stock_list = $CMSNT->get_list_safe("SELECT * FROM `product_stock` WHERE " . $where_sql . " ORDER BY `id` DESC LIMIT ? OFFSET ?", array_merge($where_params, [$limit, $from]));

// Đếm số lượng theo trạng thái
$total_available = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_stock` WHERE `plan_id` = ? AND `status` = 1", [$plan_id]);
$total_sold = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_stock` WHERE `plan_id` = ? AND `status` = 0", [$plan_id]);
$total_available_count = $total_available ? (int)$total_available['total'] : 0;
$total_sold_count = $total_sold ? (int)$total_sold['total'] : 0;
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h1 class="page-name fw-semibold fs-18 mb-0">
                    <i class="fa-solid fa-warehouse me-1"></i><?= __('Quản lý kho hàng'); ?>
                    <strong class="text-danger"><?= htmlspecialchars(html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8')); ?></strong>
                </h1>
            </div>
            <div class="ms-md-1 ms-0">
                <button type="button" class="btn btn-success btn-sm me-2" onclick="importStock()">
                    <i class="fa-solid fa-file-import me-1"></i><?= __('Nhập hàng loạt'); ?>
                </button>
                <button type="button" class="btn btn-primary btn-sm me-2" onclick="importStockCSV()">
                    <i class="fa-solid fa-file-csv me-1"></i><?= __('Nhập từ CSV'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-sm me-2" onclick="showApiGuide()">
                    <i class="fa-solid fa-code me-1"></i><?= __('API'); ?>
                </button>
                <a href="<?= base_url_admin('product-plans&product_id=' . $plan['product_id']); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i><?= __('Quay lại'); ?>
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
                                <div class="avatar avatar-md bg-success-transparent rounded-circle">
                                    <i class="fa-solid fa-boxes-stacked fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Tổng số lượng'); ?></p>
                                <h4 class="mb-0 fw-semibold"><?= number_format($total); ?></h4>
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
                                <div class="avatar avatar-md bg-info-transparent rounded-circle">
                                    <i class="fa-solid fa-check-circle fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Còn hàng'); ?></p>
                                <h4 class="mb-0 fw-semibold text-info"><?= number_format($total_available_count); ?></h4>
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
                                <div class="avatar avatar-md bg-danger-transparent rounded-circle">
                                    <i class="fa-solid fa-times-circle fs-20"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0 text-muted"><?= __('Đã bán'); ?></p>
                                <h4 class="mb-0 fw-semibold text-danger"><?= number_format($total_sold_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="card custom-card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= base_url(); ?>" class="row g-3">
                    <input type="hidden" name="module" value="<?= $CMSNT->site('path_admin'); ?>">
                    <input type="hidden" name="action" value="product-stock">
                    <input type="hidden" name="plan_id" value="<?= $plan_id; ?>">
                    <div class="col-md-4">
                        <label class="form-label"><?= __('Tìm kiếm'); ?></label>
                        <input type="text" class="form-control" name="search"
                            value="<?= htmlspecialchars($search); ?>"
                            placeholder="<?= __('Nhập giá trị kho hàng...'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= __('Trạng thái'); ?></label>
                        <select class="form-select" name="status">
                            <option value="" <?= $status_filter == -1 ? 'selected' : ''; ?>><?= __('Tất cả'); ?></option>
                            <option value="1" <?= $status_filter == 1 ? 'selected' : ''; ?>><?= __('Còn hàng'); ?></option>
                            <option value="0" <?= $status_filter == 0 ? 'selected' : ''; ?>><?= __('Đã bán'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?= __('Số lượng/trang'); ?></label>
                        <select class="form-select" name="limit">
                            <option value="20" <?= $limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?= $limit == 100 ? 'selected' : ''; ?>>100</option>
                            <option value="200" <?= $limit == 200 ? 'selected' : ''; ?>>200</option>
                            <option value="500" <?= $limit == 500 ? 'selected' : ''; ?>>500</option>
                            <option value="1000" <?= $limit == 1000 ? 'selected' : ''; ?>>1000</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa-solid fa-filter me-1"></i><?= __('Lọc'); ?>
                        </button>
                        <a href="<?= base_url_admin('product-stock') ?>&plan_id=<?= $plan_id; ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách kho hàng -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if (count($stock_list) > 0): ?>
                            <!-- Thanh công cụ hàng loạt -->
                            <div id="bulkActionsToolbar" class="card-footer bg-light border-bottom d-none">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted">
                                            <span id="selectedCount">0</span> <?= __('kho hàng đã chọn'); ?>
                                        </span>
                                    </div>
                                    <div class="btn-list">
                                        <button type="button" id="btnBulkDelete" class="btn btn-sm btn-danger d-none" onclick="bulkDeleteStocks()">
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
                                            <th><?= __('ID'); ?></th>
                                            <th><?= __('Giá trị kho hàng'); ?></th>
                                            <th class="text-center"><?= __('Trạng thái'); ?></th>
                                            <th><?= __('Ngày tạo'); ?></th>
                                            <th><?= __('Ngày cập nhật'); ?></th>
                                            <th><?= __('Thao tác'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stock_list as $stock): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input stock-checkbox" value="<?= $stock['id']; ?>" onchange="updateBulkButtons()" style="transform: scale(1.3); cursor: pointer;">
                                                </td>
                                                <td>
                                                    <code><?= $stock['id']; ?></code>
                                                </td>
                                                <td>
                                                    <textarea class="form-control form-control-sm" rows="2" readonly style="resize: none; background-color: #f8f9fa;"><?= htmlspecialchars($stock['stock_value']); ?></textarea>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($stock['status'] == 1): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fa-solid fa-check-circle me-1"></i><?= __('Còn hàng'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fa-solid fa-times-circle me-1"></i><?= __('Đã bán'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i:s', strtotime($stock['created_at'])); ?></td>
                                                <td><?= date('d/m/Y H:i:s', strtotime($stock['updated_at'])); ?></td>
                                                <td>
                                                    <div class="btn-list">
                                                        <?php if ($stock['status'] == 1): ?>
                                                            <button onclick="editStock(<?= $stock['id']; ?>)" class="btn btn-sm btn-info">
                                                                <i class="fa-solid fa-edit me-1"></i><?= __('Sửa'); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button onclick="deleteStock(<?= $stock['id']; ?>)" class="btn btn-sm btn-danger">
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
                            $pagination_url = base_url_admin('product-stock');
                            $pagination_url .= '&plan_id=' . $plan_id;
                            $pagination_url .= '&limit=' . $limit;
                            if (!empty($search)) $pagination_url .= '&search=' . urlencode($search);
                            if ($status_filter != -1) $pagination_url .= '&status=' . $status_filter;
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
                                <i class="fa-solid fa-exclamation-circle me-2"></i><?= __('Chưa có kho hàng nào.'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal sửa kho hàng -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalTitle">
                    <i class="fa-solid fa-edit me-2"></i><?= __('Chỉnh sửa kho hàng'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockForm">
                    <input type="hidden" id="stock_id" name="stock_id">
                    <input type="hidden" id="stock_plan_id" name="plan_id" value="<?= $plan_id; ?>">

                    <div class="mb-3">
                        <label class="form-label"><?= __('Giá trị kho hàng:'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="stock_value" name="stock_value" rows="5"
                            placeholder="<?= __('VD: Tài khoản | mã kích hoạt | serial...'); ?>" required></textarea>
                        <small class="text-muted"><?= __('Nhập giá trị kho hàng'); ?></small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="stock_status_input" name="status" value="1" checked>
                            <label class="form-check-label" for="stock_status_input">
                                <?= __('Trạng thái: Còn hàng'); ?>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveStock()">
                    <i class="fa-solid fa-save me-1"></i><?= __('Lưu'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal nhập hàng loạt -->
<div class="modal fade" id="importStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-import me-2"></i><?= __('Nhập hàng loạt'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="importStockLoading" class="import-loading-overlay d-none">
                    <div class="import-loading-content">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mb-2"><?= __('Đang nhập dữ liệu...'); ?></h5>
                        <p class="text-muted mb-0" id="importStockProgress"><?= __('Vui lòng chờ trong giây lát'); ?></p>
                    </div>
                </div>

                <form id="importStockForm">
                    <input type="hidden" name="plan_id" value="<?= $plan_id; ?>">

                    <div class="mb-3">
                        <label class="form-label"><?= __('Dán danh sách kho hàng:'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="import_stock_data" name="stock_data" rows="10"
                            placeholder="<?= __('Mỗi dòng là một sản phẩm. VD:&#10;Tài khoản 1&#10;Tài khoản 2&#10;Mã kích hoạt ABC123'); ?>"
                            oninput="updateStockCount()" required></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <small class="text-muted"><?= __('Mỗi dòng sẽ được thêm vào kho hàng'); ?></small>
                            <span id="stock_count_text" class="badge bg-info">0 <?= __('tài khoản'); ?></span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelImport">
                    <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveImport" onclick="saveImportStock()">
                    <i class="fa-solid fa-save me-1"></i><?= __('Nhập hàng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal nhập từ CSV -->
<div class="modal fade" id="importStockCSVModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-file-csv me-2"></i><?= __('Nhập kho hàng từ CSV'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body position-relative">
                <!-- Loading Overlay -->
                <div id="importCSVLoading" class="import-loading-overlay d-none">
                    <div class="import-loading-content">
                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h5 class="mb-2"><?= __('Đang nhập dữ liệu...'); ?></h5>
                        <p class="text-muted mb-0" id="importCSVProgress"><?= __('Vui lòng chờ trong giây lát'); ?></p>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <strong><?= __('Hướng dẫn:'); ?></strong>
                    <ul class="mb-0 mt-2">
                        <li><?= __('File CSV phải có 1 cột: stock_value (giá trị kho hàng)'); ?></li>
                        <li><?= __('Dòng đầu tiên là header (tên cột)'); ?></li>
                        <li><?= __('Mã hóa: UTF-8'); ?></li>
                        <li><?= __('Phân cách: dấu phẩy (,)'); ?></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-success" onclick="downloadSampleCSV()">
                        <i class="fa-solid fa-download me-1"></i><?= __('Tải file CSV mẫu'); ?>
                    </button>
                </div>

                <form id="importStockCSVForm" enctype="multipart/form-data">
                    <input type="hidden" name="plan_id" value="<?= $plan_id; ?>">

                    <div class="mb-3">
                        <label class="form-label"><?= __('Chọn file CSV:'); ?> <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <small class="text-muted"><?= __('Chỉ chấp nhận file .csv'); ?></small>
                    </div>

                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title mb-2"><?= __('Xem trước dữ liệu:'); ?></h6>
                                <div id="csv_preview" class="text-muted">
                                    <i class="fa-solid fa-info-circle me-1"></i><?= __('Chọn file để xem trước...'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveCSV" onclick="saveImportStockCSV()" disabled>
                    <i class="fa-solid fa-save me-1"></i><?= __('Nhập hàng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal API Guide -->
<div class="modal fade" id="apiGuideModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-code me-2"></i><?= __('Hướng dẫn sử dụng API Import Stock'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <strong><?= __('Endpoint:'); ?></strong>
                    <code class="ms-2 user-select-all"><?= BASE_URL('api/v1/stock/import.php'); ?></code>
                </div>

                <h6 class="fw-semibold mb-3"><?= __('Parameters:'); ?></h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th><?= __('Tham số'); ?></th>
                                <th><?= __('Bắt buộc'); ?></th>
                                <th><?= __('Mô tả'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>api_key</code></td>
                                <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                <td><?= __('API key từ cột api_key trong bảng users'); ?></td>
                            </tr>
                            <tr>
                                <td><code>plan_id</code></td>
                                <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                <td><?= __('ID của gói sản phẩm (phải là gói giao ngay)'); ?></td>
                            </tr>
                            <tr>
                                <td><code>stock_data</code></td>
                                <td><span class="badge bg-danger"><?= __('Có'); ?></span></td>
                                <td><?= __('Dữ liệu kho hàng, mỗi dòng là 1 item'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-semibold mb-3"><?= __('Ví dụ GET Request:'); ?></h6>
                <div class="bg-dark text-light p-3 rounded mb-4" style="font-family: monospace; font-size: 13px;">
                    <code class="text-success"><?= BASE_URL('api/v1/stock/import.php'); ?>?api_key=YOUR_API_KEY&plan_id=<?= $plan_id; ?>&stock_data=acc1:pass1%0Aacc2:pass2</code>
                </div>

                <h6 class="fw-semibold mb-3"><?= __('Ví dụ POST Request (cURL):'); ?></h6>
                <div class="bg-dark text-light p-3 rounded mb-4" style="font-family: monospace; font-size: 13px;">
                    <pre class="mb-0 text-light">curl -X POST "<?= BASE_URL('api/v1/stock/import.php'); ?>" \
  -d "api_key=YOUR_API_KEY" \
  -d "plan_id=<?= $plan_id; ?>" \
  -d "stock_data=account1:password1
account2:password2
account3:password3"</pre>
                </div>

                <h6 class="fw-semibold mb-3"><?= __('Response thành công:'); ?></h6>
                <div class="bg-dark text-light p-3 rounded mb-4" style="font-family: monospace; font-size: 13px;">
                    <pre class="mb-0 text-light">{
  "success": true,
  "data": {
    "plan_id": <?= $plan_id; ?>,
    "plan_name": "<?= htmlspecialchars($plan['name']); ?>",
    "imported_count": 3,
    "stock_available": 150
  },
  "message": "Nhập kho hàng thành công"
}</pre>
                </div>

                <div class="alert alert-warning mb-0">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <strong><?= __('Lưu ý:'); ?></strong>
                    <ul class="mb-0 mt-2">
                        <li><?= __('User phải có quyền <code>edit_product_stock</code> trong admin role'); ?></li>
                        <li><?= __('Với GET request, <code>stock_data</code> phải được URL encode (<code>%0A</code> = xuống dòng)'); ?></li>
                        <li><?= __('API key lấy từ: Admin → Users → User Edit → cột api_key'); ?></li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i><?= __('Đóng'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<style>
    /* Import Loading Overlay */
    .import-loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: inherit;
    }

    .import-loading-content {
        text-align: center;
        padding: 30px;
    }

    .import-loading-content h5 {
        color: #1e293b;
        font-weight: 600;
    }

    .import-loading-content p {
        font-size: 14px;
    }

    /* Animation for spinner */
    .import-loading-overlay .spinner-border {
        animation: spinner-border .75s linear infinite, pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    /* Dark mode support */
    [data-theme="dark"] .import-loading-overlay {
        background: rgba(30, 41, 59, 0.95);
    }

    [data-theme="dark"] .import-loading-content h5 {
        color: #f8fafc;
    }

    [data-theme="dark"] .import-loading-content p {
        color: #94a3b8 !important;
    }
</style>

<script>
    // Modal instance
    var stockModal;
    var importStockModal;
    var importStockCSVModal;
    var apiGuideModal;
    var csvData = [];

    document.addEventListener('DOMContentLoaded', function() {
        stockModal = new bootstrap.Modal(document.getElementById('stockModal'));
        importStockModal = new bootstrap.Modal(document.getElementById('importStockModal'));
        importStockCSVModal = new bootstrap.Modal(document.getElementById('importStockCSVModal'));
        apiGuideModal = new bootstrap.Modal(document.getElementById('apiGuideModal'));

        // Xử lý khi chọn file CSV
        document.getElementById('csv_file').addEventListener('change', function(e) {
            handleCSVFile(e.target.files[0]);
        });
    });

    // Sửa kho hàng
    function editStock(stockId) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/view.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'getProductStock',
                id: stockId
            },
            success: function(result) {
                if (result.status == 'success') {
                    document.getElementById('stock_id').value = result.data.id;
                    document.getElementById('stock_value').value = result.data.stock_value;
                    document.getElementById('stock_status_input').checked = result.data.status == 1;
                    stockModal.show();
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }

    // Lưu kho hàng (chỉ dùng cho sửa)
    function saveStock() {
        var formData = {
            action: 'updateProductStock',
            id: $('#stock_id').val(),
            plan_id: $('#stock_plan_id').val(),
            stock_value: $('#stock_value').val().trim(),
            status: $('#stock_status_input').is(':checked') ? 1 : 0
        };

        if (!formData.stock_value) {
            showMessage('<?= __("Vui lòng nhập giá trị kho hàng"); ?>', 'error');
            return;
        }

        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: formData,
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, 'success');
                    stockModal.hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }

    // Xóa kho hàng
    function deleteStock(stockId) {
        Swal.fire({
            title: "<?= __('Cảnh báo'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn xóa kho hàng này không?'); ?>",
            icon: 'warning',
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
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                    type: 'POST',
                    dataType: "JSON",
                    data: {
                        action: 'removeProductStock',
                        id: stockId
                    },
                    success: function(result) {
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
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                    }
                });
            }
        });
    }

    // Hiển thị modal API Guide
    function showApiGuide() {
        apiGuideModal.show();
    }

    // Hiển thị modal nhập hàng loạt
    function importStock() {
        document.getElementById('importStockForm').reset();
        updateStockCount();
        importStockModal.show();
    }

    // Cập nhật số lượng tài khoản
    function updateStockCount() {
        var stockData = document.getElementById('import_stock_data').value;
        var lines = stockData.split(/\r?\n/).filter(function(line) {
            return line.trim() !== '';
        });
        var count = lines.length;

        var countBadge = document.getElementById('stock_count_text');
        if (count > 0) {
            countBadge.textContent = count + ' <?= __("tài khoản"); ?>';
            countBadge.className = 'badge bg-success';
        } else {
            countBadge.textContent = '0 <?= __("tài khoản"); ?>';
            countBadge.className = 'badge bg-info';
        }
    }

    // Lưu nhập hàng loạt
    function saveImportStock() {
        var stockData = $('#import_stock_data').val().trim();

        if (!stockData) {
            showMessage('<?= __("Vui lòng nhập dữ liệu kho hàng"); ?>', 'error');
            return;
        }

        // Count items to import
        var lines = stockData.split(/\r?\n/).filter(function(line) {
            return line.trim() !== '';
        });
        var totalItems = lines.length;

        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/create.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'importProductStock',
                plan_id: <?= $plan_id; ?>,
                stock_data: stockData
            },
            beforeSend: function() {
                // Show loading overlay
                $('#importStockLoading').removeClass('d-none');
                $('#importStockProgress').text('<?= __("Đang nhập"); ?> ' + totalItems + ' <?= __("tài khoản"); ?>...');
                // Disable buttons
                $('#btnSaveImport').prop('disabled', true);
                $('#btnSaveImport').html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang nhập..."); ?>');
                $('#btnCancelImport').prop('disabled', true);
                // Disable textarea
                $('#import_stock_data').prop('disabled', true);
            },
            success: function(result) {
                // Hide loading overlay
                $('#importStockLoading').addClass('d-none');

                if (result.status == 'success') {
                    $('#importStockProgress').text('<?= __("Hoàn thành!"); ?>');
                    showMessage(result.msg, 'success');
                    importStockModal.hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    // Reset buttons on error
                    $('#btnSaveImport').prop('disabled', false);
                    $('#btnSaveImport').html('<i class="fa-solid fa-save me-1"></i><?= __("Nhập hàng"); ?>');
                    $('#btnCancelImport').prop('disabled', false);
                    $('#import_stock_data').prop('disabled', false);
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                // Hide loading overlay and reset
                $('#importStockLoading').addClass('d-none');
                $('#btnSaveImport').prop('disabled', false);
                $('#btnSaveImport').html('<i class="fa-solid fa-save me-1"></i><?= __("Nhập hàng"); ?>');
                $('#btnCancelImport').prop('disabled', false);
                $('#import_stock_data').prop('disabled', false);
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }

    // Chọn tất cả / Bỏ chọn tất cả
    function toggleSelectAll(checkbox) {
        $('.stock-checkbox').prop('checked', checkbox.checked);
        updateBulkButtons();
    }

    // Cập nhật hiển thị nút bulk action
    function updateBulkButtons() {
        var selectedCount = $('.stock-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);

        if (selectedCount > 0) {
            $('#bulkActionsToolbar').removeClass('d-none');
            $('#btnBulkDelete').removeClass('d-none');
        } else {
            $('#bulkActionsToolbar').addClass('d-none');
            $('#btnBulkDelete').addClass('d-none');
        }

        // Cập nhật trạng thái checkbox "Chọn tất cả"
        var totalCheckboxes = $('.stock-checkbox').length;
        $('#selectAll').prop('checked', selectedCount === totalCheckboxes && totalCheckboxes > 0);
    }

    // Lấy danh sách ID đã chọn
    function getSelectedStockIds() {
        var selectedIds = [];
        $('.stock-checkbox:checked').each(function() {
            selectedIds.push(parseInt($(this).val()));
        });
        return selectedIds;
    }

    // Xóa các kho hàng đã chọn
    function bulkDeleteStocks() {
        var selectedIds = getSelectedStockIds();

        if (selectedIds.length === 0) {
            showMessage('<?= __("Vui lòng chọn ít nhất một kho hàng để xóa"); ?>', 'error');
            return;
        }

        Swal.fire({
            title: "<?= __('Cảnh báo'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn xóa'); ?> " + selectedIds.length + " <?= __('kho hàng đã chọn không? Hành động này không thể hoàn tác.'); ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "<?= __('Đồng ý'); ?>",
            cancelButtonText: "<?= __('Hủy'); ?>",
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false,
            showCloseButton: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
                    type: 'POST',
                    dataType: "JSON",
                    data: {
                        action: 'removeProductStocks',
                        ids: JSON.stringify(selectedIds)
                    },
                    beforeSend: function() {
                        $('#btnBulkDelete').prop('disabled', true);
                        $('#btnBulkDelete').html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang xóa..."); ?>');
                    },
                    success: function(result) {
                        $('#btnBulkDelete').prop('disabled', false);
                        $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa đã chọn"); ?>');

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
                        $('#btnBulkDelete').html('<i class="fa-solid fa-trash me-1"></i><?= __("Xóa đã chọn"); ?>');
                        showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
                    }
                });
            }
        });
    }

    // Hiển thị modal nhập CSV
    function importStockCSV() {
        document.getElementById('importStockCSVForm').reset();
        document.getElementById('csv_preview').innerHTML = '<i class="fa-solid fa-info-circle me-1"></i><?= __("Chọn file để xem trước..."); ?>';
        document.getElementById('btnSaveCSV').disabled = true;
        csvData = [];
        importStockCSVModal.show();
    }

    // Tải file CSV mẫu
    function downloadSampleCSV() {
        var csvContent = "stock_value\n";
        csvContent += "username1:password1\n";
        csvContent += "username2:password2\n";
        csvContent += "ABC123XYZ456\n";
        csvContent += "Tài khoản 1 | Mật khẩu 1\n";
        csvContent += "Mã kích hoạt: KEY-001\n";

        // Tạo blob với BOM để Excel nhận dạng UTF-8
        var BOM = "\uFEFF";
        var blob = new Blob([BOM + csvContent], {
            type: 'text/csv;charset=utf-8;'
        });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);

        link.setAttribute("href", url);
        link.setAttribute("download", "stock_sample_<?= $plan_id; ?>.csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showMessage('<?= __("Đã tải file CSV mẫu"); ?>', 'success');
    }

    // Xử lý file CSV
    function handleCSVFile(file) {
        if (!file) {
            return;
        }

        if (!file.name.endsWith('.csv')) {
            showMessage('<?= __("Vui lòng chọn file CSV"); ?>', 'error');
            document.getElementById('csv_file').value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var content = e.target.result;
            parseCSV(content);
        };
        reader.readAsText(file, 'UTF-8');
    }

    // Parse CSV
    function parseCSV(content) {
        var lines = content.split(/\r?\n/);
        var headers = [];
        csvData = [];

        if (lines.length < 2) {
            showMessage('<?= __("File CSV không có dữ liệu"); ?>', 'error');
            document.getElementById('csv_preview').innerHTML = '<span class="text-danger"><i class="fa-solid fa-exclamation-circle me-1"></i><?= __("File CSV không có dữ liệu"); ?></span>';
            document.getElementById('btnSaveCSV').disabled = true;
            return;
        }

        // Parse header
        headers = parseCSVLine(lines[0]);

        // Kiểm tra header
        if (!headers.includes('stock_value')) {
            showMessage('<?= __("File CSV phải có cột stock_value"); ?>', 'error');
            document.getElementById('csv_preview').innerHTML = '<span class="text-danger"><i class="fa-solid fa-exclamation-circle me-1"></i><?= __("File CSV phải có cột stock_value"); ?></span>';
            document.getElementById('btnSaveCSV').disabled = true;
            return;
        }

        var stockValueIndex = headers.indexOf('stock_value');

        // Parse data
        for (var i = 1; i < lines.length; i++) {
            if (lines[i].trim() === '') continue;

            var values = parseCSVLine(lines[i]);
            if (values.length > stockValueIndex && values[stockValueIndex].trim() !== '') {
                csvData.push(values[stockValueIndex].trim());
            }
        }

        // Hiển thị preview
        if (csvData.length === 0) {
            document.getElementById('csv_preview').innerHTML = '<span class="text-warning"><i class="fa-solid fa-exclamation-circle me-1"></i><?= __("Không tìm thấy dữ liệu hợp lệ"); ?></span>';
            document.getElementById('btnSaveCSV').disabled = true;
            return;
        }

        var previewHTML = '<div class="alert alert-success mb-0">';
        previewHTML += '<i class="fa-solid fa-check-circle me-1"></i>';
        previewHTML += '<strong><?= __("Tìm thấy"); ?>: ' + csvData.length + ' <?= __("kho hàng"); ?></strong><br>';
        previewHTML += '<small><?= __("Xem trước 5 dòng đầu tiên:"); ?></small><br>';
        previewHTML += '<ul class="mb-0 mt-2">';
        for (var i = 0; i < Math.min(5, csvData.length); i++) {
            previewHTML += '<li><code>' + escapeHtml(csvData[i].substring(0, 100)) + (csvData[i].length > 100 ? '...' : '') + '</code></li>';
        }
        previewHTML += '</ul>';
        previewHTML += '</div>';

        document.getElementById('csv_preview').innerHTML = previewHTML;
        document.getElementById('btnSaveCSV').disabled = false;
    }

    // Parse một dòng CSV (xử lý dấu phẩy trong quotes)
    function parseCSVLine(line) {
        var result = [];
        var current = '';
        var inQuotes = false;

        for (var i = 0; i < line.length; i++) {
            var char = line[i];

            if (char === '"') {
                inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                result.push(current);
                current = '';
            } else {
                current += char;
            }
        }
        result.push(current);

        return result;
    }

    // Escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    // Lưu CSV import
    function saveImportStockCSV() {
        if (csvData.length === 0) {
            showMessage('<?= __("Không có dữ liệu để nhập"); ?>', 'error');
            return;
        }

        // Gộp dữ liệu thành chuỗi, mỗi dòng là một stock
        var stockDataText = csvData.join('\n');
        var totalItems = csvData.length;

        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/create.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'importProductStock',
                plan_id: <?= $plan_id; ?>,
                stock_data: stockDataText
            },
            beforeSend: function() {
                // Show loading overlay
                $('#importCSVLoading').removeClass('d-none');
                $('#importCSVProgress').text('<?= __("Đang nhập"); ?> ' + totalItems + ' <?= __("tài khoản"); ?>...');
                $('#btnSaveCSV').prop('disabled', true);
                $('#btnSaveCSV').html('<i class="fa-solid fa-spinner fa-spin me-1"></i><?= __("Đang nhập..."); ?>');
            },
            success: function(result) {
                // Hide loading overlay
                $('#importCSVLoading').addClass('d-none');

                if (result.status == 'success') {
                    $('#importCSVProgress').text('<?= __("Hoàn thành!"); ?>');
                    showMessage(result.msg, 'success');
                    importStockCSVModal.hide();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $('#btnSaveCSV').prop('disabled', false);
                    $('#btnSaveCSV').html('<i class="fa-solid fa-save me-1"></i><?= __("Nhập hàng"); ?>');
                    showMessage(result.msg, 'error');
                }
            },
            error: function() {
                $('#importCSVLoading').addClass('d-none');
                $('#btnSaveCSV').prop('disabled', false);
                $('#btnSaveCSV').html('<i class="fa-solid fa-save me-1"></i><?= __("Nhập hàng"); ?>');
                showMessage('<?= __("Đã xảy ra lỗi"); ?>', 'error');
            }
        });
    }
</script>