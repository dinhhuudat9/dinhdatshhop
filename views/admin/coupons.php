<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý mã giảm giá') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_coupon') != true) {
    $role_name = getRoleName('view_coupon');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

// Phân trang an toàn
$limit = isset($_GET['limit']) ? (validate_int($_GET['limit'], 1, 1000) ?: 20) : 20;
$page = isset($_GET['page']) ? (validate_int($_GET['page'], 1, 1000000) ?: 1) : 1;
$from = ($page - 1) * $limit;

// Biến giữ giá trị hiển thị lại
$search = '';
$status_filter = '';
$type_filter = '';
$date_from = '';
$date_to = '';

// WHERE an toàn với prepared statements
$where_conditions = ["1 = 1"];
$where_params = [];

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

// Lọc theo loại mã giảm giá
if (isset($_GET['type']) && $_GET['type'] !== '') {
    $type_input = validate_string($_GET['type'], 20);
    if ($type_input !== false && in_array($type_input, ['percentage', 'fixed'])) {
        $type_filter = $type_input;
        $where_conditions[] = '`type` = ?';
        $where_params[] = $type_filter;
    } else {
        $type_filter = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
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
        $where_conditions[] = '(`code` LIKE ? OR `description` LIKE ?)';
        $searchPattern = '%' . $search . '%';
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
    } else {
        $search = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Tổng số mã giảm giá
$countSql = "SELECT COUNT(*) AS total_count FROM `coupons` WHERE $where_clause";
$totalRow = $CMSNT->get_row_safe($countSql, $where_params);
$total = (int)($totalRow['total_count'] ?? 0);
$total_pages = ceil($total / $limit);

// Lấy danh sách mã giảm giá
$listSql = "SELECT * FROM `coupons` WHERE $where_clause ORDER BY `id` DESC LIMIT ?, ?";
$listParams = array_merge($where_params, [$from, $limit]);
$coupons = $CMSNT->get_list_safe($listSql, $listParams);

// Lấy số lượng đơn hàng đã sử dụng từng mã giảm giá
$order_counts = [];
if (!empty($coupons)) {
    $coupon_codes = array_column($coupons, 'code');
    $placeholders = implode(',', array_fill(0, count($coupon_codes), '?'));
    $order_count_sql = "SELECT `coupon_code`, COUNT(*) as order_count FROM `product_orders` WHERE `coupon_code` IN ($placeholders) AND `coupon_code` IS NOT NULL AND `coupon_code` != '' GROUP BY `coupon_code`";
    $order_count_results = $CMSNT->get_list_safe($order_count_sql, $coupon_codes);

    foreach ($order_count_results as $result) {
        $order_counts[$result['coupon_code']] = (int)$result['order_count'];
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-ticket me-1"></i><?= __('Quản lý mã giảm giá'); ?>
            </h1>
            <div class="ms-md-1 ms-0">
                <a href="<?= base_url_admin('coupon-add'); ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i><?= __('Thêm mã giảm giá'); ?>
                </a>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="row mb-3">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form method="GET" action="<?= base_url(); ?>" class="row g-3">
                            <input type="hidden" name="module" value="<?= $CMSNT->site('path_admin'); ?>">
                            <input type="hidden" name="action" value="coupons">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label"><?= __('Tìm kiếm'); ?></label>
                                    <input type="text" class="form-control" name="search"
                                        value="<?= htmlspecialchars($search); ?>"
                                        placeholder="<?= __('Mã giảm giá, mô tả...'); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?= __('Loại'); ?></label>
                                    <select class="form-select" name="type">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option value="percentage" <?= $type_filter == 'percentage' ? 'selected' : ''; ?>><?= __('Phần trăm'); ?></option>
                                        <option value="fixed" <?= $type_filter == 'fixed' ? 'selected' : ''; ?>><?= __('Số tiền cố định'); ?></option>
                                    </select>
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
                                    <a href="<?= base_url_admin('coupons'); ?>" class="btn btn-secondary">
                                        <i class="fa-solid fa-times me-1"></i><?= __('Bỏ lọc'); ?>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách mã giảm giá -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if (count($coupons) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover border text-nowrap">
                                    <thead>
                                        <tr>
                                            <th><?= __('Mã giảm giá'); ?></th>
                                            <th><?= __('Loại'); ?></th>
                                            <th><?= __('Giá trị'); ?></th>
                                            <th><?= __('Đơn tối thiểu'); ?></th>
                                            <th><?= __('Giảm tối đa'); ?></th>
                                            <th class="text-center"><?= __('Đã sử dụng'); ?></th>
                                            <th><?= __('Thời hạn'); ?></th>
                                            <th class="text-center"><?= __('Trạng thái'); ?></th>
                                            <th><?= __('Ngày tạo'); ?></th>
                                            <th><?= __('Thao tác'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coupons as $coupon):
                                            // Kiểm tra mã còn hiệu lực không
                                            $is_valid = true;
                                            $validity_text = '';
                                            if ($coupon['start_date'] && strtotime($coupon['start_date']) > time()) {
                                                $is_valid = false;
                                                $validity_text = __('Chưa bắt đầu');
                                            } elseif ($coupon['end_date'] && strtotime($coupon['end_date']) < time()) {
                                                $is_valid = false;
                                                $validity_text = __('Đã hết hạn');
                                            } elseif ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
                                                $is_valid = false;
                                                $validity_text = __('Đã hết lượt sử dụng');
                                            } else {
                                                if ($coupon['start_date'] && $coupon['end_date']) {
                                                    $validity_text = date('d/m/Y', strtotime($coupon['start_date'])) . ' - ' . date('d/m/Y', strtotime($coupon['end_date']));
                                                } elseif ($coupon['start_date']) {
                                                    $validity_text = __('Từ') . ' ' . date('d/m/Y', strtotime($coupon['start_date']));
                                                } elseif ($coupon['end_date']) {
                                                    $validity_text = __('Đến') . ' ' . date('d/m/Y', strtotime($coupon['end_date']));
                                                } else {
                                                    $validity_text = __('Vĩnh viễn');
                                                }
                                            }
                                        ?>
                                            <tr id="coupon-<?= $coupon['id']; ?>">
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <strong class="text-primary"><?= htmlspecialchars($coupon['code']); ?></strong>
                                                        <?php if (!empty($coupon['description'])): ?>
                                                            <small class="text-muted"><?= htmlspecialchars($coupon['description'] ?? ''); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($coupon['type'] == 'percentage'): ?>
                                                        <span class="badge bg-info">
                                                            <i class="fa-solid fa-percent me-1"></i><?= __('Phần trăm'); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">
                                                            <i class="fa-solid fa-money-bill me-1"></i><?= __('Số tiền cố định'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($coupon['type'] == 'percentage'): ?>
                                                        <strong><?= number_format($coupon['value'], 0); ?>%</strong>
                                                    <?php else: ?>
                                                        <strong><?= format_currency($coupon['value']); ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($coupon['min_order_amount'] > 0): ?>
                                                        <span><?= format_currency($coupon['min_order_amount']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($coupon['max_discount_amount'] > 0): ?>
                                                        <span><?= format_currency($coupon['max_discount_amount']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex flex-column align-items-center gap-1">
                                                        <span class="badge bg-primary-transparent">
                                                            <?= $coupon['used_count']; ?>
                                                            <?php if ($coupon['usage_limit'] > 0): ?>
                                                                / <?= $coupon['usage_limit']; ?>
                                                            <?php endif; ?>
                                                        </span>

                                                    </div>
                                                </td>
                                                <td>
                                                    <small class="<?= $is_valid ? 'text-success' : 'text-danger'; ?>">
                                                        <?= $validity_text; ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="status<?= $coupon['id']; ?>"
                                                            <?= $coupon['status'] == 1 ? 'checked' : ''; ?>
                                                            onchange="updateStatus('<?= $coupon['id']; ?>', this.checked ? 1 : 0)"
                                                            style="transform: scale(1.5);">
                                                    </div>
                                                </td>
                                                <td>
                                                    <small>
                                                        <i class="fa-regular fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($coupon['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-list">
                                                        <?php
                                                        $order_count = isset($order_counts[$coupon['code']]) ? $order_counts[$coupon['code']] : 0;
                                                        if ($order_count > 0):
                                                        ?>
                                                            <a href="<?= base_url_admin('product-orders&coupon_code=' . urlencode($coupon['code'])); ?>"
                                                                class="btn btn-sm btn-success"
                                                                title="<?= __('Xem đơn hàng sử dụng mã này'); ?>">
                                                                <i class="fa-solid fa-shopping-cart me-1"></i><?= __('Đơn hàng'); ?>
                                                                <?php if ($order_count > 0): ?>
                                                                    <span class="badge bg-white text-success ms-1"><?= $order_count; ?></span>
                                                                <?php endif; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="<?= base_url_admin('coupon-edit&id=' . $coupon['id']); ?>"
                                                            class="btn btn-sm btn-info">
                                                            <i class="fa-solid fa-edit me-1"></i><?= __('Sửa'); ?>
                                                        </a>
                                                        <button onclick="removeCoupon('<?= $coupon['id']; ?>')"
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
                            $pagination_url = base_url_admin('coupons');
                            $pagination_url .= '&limit=' . $limit;
                            if (!empty($search)) $pagination_url .= '&search=' . urlencode($search);
                            if ($status_filter) $pagination_url .= '&status=' . $status_filter;
                            if ($type_filter) $pagination_url .= '&type=' . $type_filter;
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
                                <i class="fa-solid fa-exclamation-circle me-2"></i><?= __('Chưa có mã giảm giá nào.'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    // Cập nhật trạng thái mã giảm giá
    function updateStatus(id, status) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateCouponStatus',
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

    // Xóa mã giảm giá
    function removeCoupon(id) {
        Swal.fire({
            title: "<?= __('Cảnh báo'); ?>",
            text: "<?= __('Bạn có chắc chắn muốn xóa mã giảm giá này không?'); ?>",
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
                        action: 'removeCoupon',
                        id: id
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            showMessage(result.msg, 'success');
                            $('#coupon-' + id).fadeOut(300, function() {
                                $(this).remove();
                            });
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
</script>