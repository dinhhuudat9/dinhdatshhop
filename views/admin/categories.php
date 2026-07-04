<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Danh sách chuyên mục') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<style>
    /* Cursor cho kéo thả - đơn giản */
    .handle-parent, .handle-child {
        cursor: move;
        color: #6c757d;
        font-size: 16px;
        padding: 5px;
    }
    
    .handle-parent:hover, .handle-child:hover {
        color: #0d6efd;
    }
</style>
';
$body['footer'] = '
<script>
// SortableJS được sử dụng thay thế cho jQuery UI Sortable
</script>
';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'view_category') != true) {
    $role_name = getRoleName('view_category');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

if (isset($_POST['submit'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }
    if (checkPermission($getUser['admin'], 'edit_category') != true) {
        $role_name = getRoleName('edit_category');
        die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `categories` WHERE `slug` = '" . create_slug(check_string($_POST['name'])) . "' ")) {
        die('<script type="text/javascript">if(!alert("' . __('Chuyên mục này đã tồn tại trong hệ thống.') . '")){window.history.back().location.reload();}</script>');
    }
    $url_icon = null;

    // Kiểm tra icon từ thư viện (elFinder)
    if (!empty($_POST['icon_path'])) {
        $icon_path = trim($_POST['icon_path']);
        // Chuyển đổi URL đầy đủ thành đường dẫn tương đối
        $base_url = rtrim(BASE_URL(), '/');
        if (strpos($icon_path, $base_url) === 0) {
            $url_icon = substr($icon_path, strlen($base_url) + 1);
        } else {
            $url_icon = $icon_path;
        }
    }
    // Nếu không có icon từ thư viện, kiểm tra upload file
    elseif (check_img('icon') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $uploads_dir = 'assets/storage/images/icon' . $rand . '.png';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_icon = $uploads_dir;
        }
    }
    $isInsert = $CMSNT->insert("categories", [
        'stt'     => check_string($_POST['stt']),
        'icon'          => $url_icon,
        'name'          => check_string($_POST['name']),
        'parent_id'     => check_string($_POST['parent_id']),
        'slug'          => check_string($_POST['slug']),
        'description'   => check_string($_POST['description']),
        'status'        => check_string($_POST['status']),
        'created_at'   => gettime()
    ]);
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Category (" . check_string($_POST['name']) . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Category (" . check_string($_POST['name']) . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Thêm thành công!') . '")){location.href = "";}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thêm thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
}
?>


<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-sitemap me-1"></i><?= __('Quản lý chuyên mục'); ?>
            </h1>
            <div class="ms-md-1 ms-0">
                <button id="btn-add-parent" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i><?= __('Thêm chuyên mục cha'); ?>
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Form thêm chuyên mục cha -->
            <div class="col-xl-12" id="card-add-parent" style="display: none;">
                <div class="card custom-card mb-4">
                    <div class="card-header d-flex justify-content-between border-bottom-0">
                        <div class="card-title">
                            <i class="fa-solid fa-folder-plus me-2"></i><?= __('Thêm chuyên mục cha mới'); ?>
                        </div>
                        <button type="button" class="btn-close" id="btn-close-add-parent"></button>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="stt"><?= __('Ưu tiên:'); ?></label>
                                        <input type="text" class="form-control" value="0" name="stt" required>
                                        <div class="form-text text-muted"><?= __('Ưu tiên càng cao, chuyên mục càng hiển thị trên cùng'); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Tên chuyên mục cha:'); ?> <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            placeholder="<?= __('Nhập tên chuyên mục'); ?>" required>
                                    </div>
                                    <input type="hidden" name="parent_id" value="0">
                                </div>
                                <div class="row mb-4">
                                    <label class="col-sm-4 col-form-label"
                                        for="example-hf-email"><?= __('Slug:'); ?>
                                        <span class="text-danger">*</span></label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="slug"
                                            placeholder="<?= __('Nhập slug chuyên mục'); ?>" required>
                                        <small class="text-muted"><?= __('Slug sẽ được tạo tự động từ tên chuyên mục'); ?></small>
                                    </div>
                                </div>
                                <script>
                                    function removeVietnameseTones(str) {
                                        return str.normalize('NFD') // Tách tổ hợp ký tự và dấu
                                            .replace(/[\u0300-\u036f]/g, '') // Loại bỏ dấu
                                            .replace(/đ/g, 'd') // Chuyển đổi chữ "đ" thành "d"
                                            .replace(/Đ/g, 'D'); // Chuyển đổi chữ "Đ" thành "D"
                                    }

                                    document.querySelector('input[name="name"]').addEventListener('input', function() {
                                        var categoryName = this.value;

                                        // Chuyển tên chuyên mục thành slug
                                        var slug = removeVietnameseTones(categoryName.toLowerCase())
                                            .replace(/ /g, '-') // Thay khoảng trắng bằng dấu gạch ngang
                                            .replace(/[^\w-]+/g, ''); // Loại bỏ các ký tự không hợp lệ

                                        // Đặt giá trị slug vào trường input slug
                                        document.querySelector('input[name="slug"]').value = slug;
                                    });
                                </script>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Icon:'); ?></label>

                                        <!-- Image Preview -->
                                        <div id="icon-preview-container" class="mb-2" style="display: none;">
                                            <img id="icon-preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                            <button type="button" class="btn btn-sm btn-danger ms-2" onclick="clearSelectedIcon()">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </div>

                                        <!-- Hidden input for library image path -->
                                        <input type="hidden" name="icon_path" id="category-icon-path" value="">

                                        <div class="d-flex gap-2 align-items-start">
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" name="icon" id="category-icon"
                                                    accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                                    onchange="previewUploadedIcon(this)">
                                            </div>
                                            <button type="button" class="btn btn-outline-primary" onclick="openIconFileManager()">
                                                <i class="fa-solid fa-folder-open me-1"></i><?= __('Thư viện'); ?>
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1"><?= __('Upload ảnh mới hoặc chọn từ thư viện'); ?></small>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa-solid fa-lightbulb text-warning me-1"></i>
                                            <?= __('Gợi ý: Tải icon miễn phí tại'); ?>
                                            <a href="https://www.flaticon.com/search" target="_blank" class="text-primary">Flaticon</a>,
                                            <a href="https://icons8.com" target="_blank" class="text-primary">Icons8</a>,
                                            <a href="https://www.iconfinder.com" target="_blank" class="text-primary">Iconfinder</a>
                                        </small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Trạng thái:'); ?> <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="status" required>
                                            <option value="show"><?= __('Hiển thị'); ?></option>
                                            <option value="hide"><?= __('Ẩn'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Description SEO:'); ?></label>
                                        <textarea class="form-control" rows="3" name="description"
                                            placeholder="<?= __('Mô tả ngắn về chuyên mục này'); ?>"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-1"></i> <?= __('Thêm chuyên mục'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Danh sách chuyên mục -->
            <div class="col-xl-12">
                <div class="card custom-card">

                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="collapse-all-btn">
                                <i class="fa-solid fa-angles-up me-1"></i><?= __('Đóng tất cả'); ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="expand-all-btn">
                                <i class="fa-solid fa-angles-down me-1"></i><?= __('Mở tất cả'); ?>
                            </button>
                        </div>
                        <?php
                        $parentCategories = $CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ORDER BY `stt` DESC");

                        // Đếm số lượng sản phẩm cho mỗi chuyên mục con
                        $category_products_count = [];
                        if (count($parentCategories) > 0) {
                            foreach ($parentCategories as $parent) {
                                $childCategories = $CMSNT->get_list("SELECT `id` FROM `categories` WHERE `parent_id` = '" . $parent['id'] . "'");
                                if (count($childCategories) > 0) {
                                    $child_ids = array_column($childCategories, 'id');
                                    // Đếm sản phẩm sử dụng category_ids (multi-category)
                                    foreach ($child_ids as $child_id) {
                                        $count = $CMSNT->num_rows_safe(
                                            "SELECT id FROM `products` WHERE FIND_IN_SET(?, `category_ids`)",
                                            [$child_id]
                                        );
                                        $category_products_count[$child_id] = $count;
                                    }
                                }
                            }
                        }

                        if (count($parentCategories) > 0):
                        ?>

                            <div id="category-container">
                                <ul id="sortable-parent-categories" class="list-unstyled mb-0">
                                    <?php foreach ($parentCategories as $index => $category): ?>
                                        <li class="sortable-parent-item" id="parent-item-<?= $category['id']; ?>"
                                            data-id="<?= $category['id']; ?>">
                                            <div class="card-header p-2 bg-light category-header">
                                                <div class="d-flex align-items-center justify-content-between w-100 category-header-content"
                                                    data-bs-toggle="collapse" data-bs-target="#category-<?= $category['id']; ?>"
                                                    aria-expanded="false" aria-controls="category-<?= $category['id']; ?>"
                                                    style="cursor: pointer;">
                                                    <div class="d-flex align-items-center">
                                                        <span class="handle-parent" onclick="event.stopPropagation();" style="width: 30px; text-align: center; display: inline-block;">
                                                            <i class="fa-solid fa-grip-vertical"></i>
                                                        </span>
                                                        <?php if ($category['icon'] != null && file_exists($category['icon'])): ?>
                                                            <img src="<?= base_url($category['icon']); ?>" class="me-2 rounded"
                                                                width="36px" height="36px">
                                                        <?php else: ?>
                                                            <div class="me-2 rounded d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                                                                style="width: 36px; height: 36px;">
                                                                <i class="fa-solid fa-folder fs-5 text-primary"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <h5 class="category-name1"><?= $category['name']; ?></h5>
                                                    </div>
                                                    <div class="d-flex align-items-center flex-wrap category-header-right">
                                                        <div class="category-badges me-2">
                                                            <span class="badge bg-primary rounded-pill category-badge">
                                                                <i class="fa-solid fa-folder me-1"></i><?= format_cash($CMSNT->num_rows("SELECT * FROM `categories` WHERE `parent_id` = '" . $category['id'] . "'")); ?>
                                                            </span>
                                                            <span class="badge bg-info rounded-pill category-badge">
                                                                <i class="fa-solid fa-sort-numeric-up me-1"></i><?= $category['stt']; ?>
                                                                <input type="hidden" id="stt<?= $category['id']; ?>" value="<?= $category['stt']; ?>">
                                                            </span>
                                                            <div class="form-check form-switch category-status-switch" onclick="event.stopPropagation();">
                                                                <input class="form-check-input category-status-input" type="checkbox"
                                                                    id="status<?= $category['id']; ?>" value="show"
                                                                    <?= $category['status'] == 'show' ? 'checked' : ''; ?>
                                                                    onchange="updateForm('<?= $category['id']; ?>')"
                                                                    title="<?= __('Bật/tắt chuyên mục'); ?>">
                                                            </div>
                                                        </div>
                                                        <button class="btn btn-sm btn-light category-collapse-btn" type="button"
                                                            onclick="event.stopPropagation();">
                                                            <i class="fa-solid fa-chevron-down collapse-icon"
                                                                data-category-id="<?= $category['id']; ?>"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="category-<?= $category['id']; ?>" class="collapse">
                                                <div class="card-body">
                                                    <div class="category-actions">
                                                        <a href="<?= base_url_admin('category-add&id=' . $category['id']); ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fa-solid fa-plus me-1"></i><?= __('Thêm chuyên mục con'); ?>
                                                        </a>
                                                        <a href="<?= base_url_admin('category-edit&id=' . $category['id']); ?>"
                                                            class="btn btn-sm btn-outline-info">
                                                            <i class="fa-solid fa-edit me-1"></i><?= __('Sửa'); ?>
                                                        </a>
                                                        <button onclick="RemoveRow('<?= $category['id']; ?>')"
                                                            class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-trash me-1"></i><?= __('Xóa'); ?>
                                                        </button>
                                                    </div>

                                                    <?php $childCategories = $CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $category['id'] . "' ORDER BY `stt` DESC"); ?>

                                                    <?php if (count($childCategories) > 0): ?>
                                                        <div class="table-responsive mt-3">
                                                            <table class="table table-striped table-hover border child-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th colspan="2"><?= __('Chuyên mục con'); ?></th>
                                                                        <th class="text-center" width="12%"><?= __('Số lượng sản phẩm'); ?></th>
                                                                        <th class="text-center" width="10%"><?= __('Trạng thái'); ?></th>
                                                                        <th width="10%"><?= __('Thao tác'); ?></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="sortable-child-categories" data-parent-id="<?= $category['id']; ?>">
                                                                    <?php foreach ($childCategories as $child): ?>
                                                                        <tr id="child-item-<?= $child['id']; ?>" class="child-category-row" data-id="<?= $child['id']; ?>">
                                                                            <td style="width: 30px; text-align: center;">
                                                                                <i class="fa-solid fa-grip-vertical handle-child"></i>
                                                                                <input type="hidden"
                                                                                    class="form-control form-control-sm"
                                                                                    style="display: none;"
                                                                                    id="stt<?= $child['id']; ?>"
                                                                                    value="<?= $child['stt']; ?>"
                                                                                    onchange="updateForm('<?= $child['id']; ?>')"
                                                                                    readonly>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <?php if ($child['icon'] != null && file_exists($child['icon'])): ?>
                                                                                        <img src="<?= base_url($child['icon']); ?>" width="32px"
                                                                                            height="32px" class="img-thumbnail me-2">
                                                                                    <?php else: ?>
                                                                                        <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 rounded me-2"
                                                                                            style="width: 32px; height: 32px; flex-shrink: 0;">
                                                                                            <i class="fa-solid fa-file-alt text-info"></i>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <div>
                                                                                        <span class="fw-bold text-truncate"
                                                                                            style="max-width: 600px; display: inline-block;"><?= $child['name']; ?></span>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <?php
                                                                                $products_count = $category_products_count[$child['id']] ?? 0;
                                                                                if ($products_count > 0) {
                                                                                    echo '<a href="' . base_url_admin('products&category_id=' . $child['id']) . '" class="badge bg-primary-transparent text-primary border border-primary border-opacity-25 px-3 py-2 text-decoration-none">';
                                                                                    echo '<i class="fa-solid fa-box me-1"></i>';
                                                                                    echo '<span class="fw-semibold">' . number_format($products_count) . '</span>';
                                                                                    echo '</a>';
                                                                                } else {
                                                                                    echo '<span class="text-muted fst-italic">0</span>';
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                            <td class="text-center">
                                                                                <div class="form-check form-switch d-flex justify-content-center">
                                                                                    <input class="form-check-input" type="checkbox"
                                                                                        id="status<?= $child['id']; ?>" value="show"
                                                                                        style="transform: scale(1.5);"
                                                                                        <?= $child['status'] == 'show' ? 'checked' : ''; ?>
                                                                                        onchange="updateForm('<?= $child['id']; ?>')">
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="btn-list">
                                                                                    <a href="<?= base_url_admin('category-edit&id=' . $child['id']); ?>"
                                                                                        class="btn btn-sm btn-info"
                                                                                        data-bs-toggle="tooltip" title="<?= __('Sửa'); ?>">
                                                                                        <i class="fa-solid fa-edit"></i>
                                                                                    </a>
                                                                                    <button onclick="RemoveRow('<?= $child['id']; ?>')"
                                                                                        class="btn btn-sm btn-danger"
                                                                                        data-bs-toggle="tooltip" title="<?= __('Xóa'); ?>">
                                                                                        <i class="fa-solid fa-trash"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-info mt-3">
                                                            <i class="fa-solid fa-info-circle me-2"></i><?= __('Chưa có chuyên mục con nào trong chuyên mục này.'); ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (count($childCategories) > 0): ?>
                                                        <div class="alert alert-light border mt-3 fs-sm">
                                                            <i class="fa-solid fa-info-circle me-2 text-primary"></i>
                                                            <?= __('Để sắp xếp chuyên mục con hoặc cập nhật nhanh chuyên mục con, bạn có thể truy cập vào'); ?> <a class="text-primary" href="<?= base_url_admin('category-sub&parent_id=' . $category['id']); ?>"><strong><?= __('đây'); ?></strong>.</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-exclamation-circle me-2"></i> <?= __('Chưa có chuyên mục nào trong hệ thống.'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info mb-2">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <?= __('Bạn có thể kéo thả các chuyên mục cha để sắp xếp thứ tự. Nhấp vào biểu tượng'); ?> <i
                                class="fa-solid fa-grip-vertical"></i> <?= __('và kéo thả để thay đổi vị trí.'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<!-- SortableJS được sử dụng thay thế cho jQuery UI Sortable -->

<script>
    function updateForm(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateTableCategory',
                id: id,
                stt: $('#stt' + id).val(),
                status: $('#status' + id + ':checked').val()
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, result.status);
                } else {
                    showMessage(result.msg, result.status);
                }
            },
            error: function() {
                alert(html(result));
                location.reload();
            }
        });
    }

    // Cải tiến hàm updateParentCategoryOrder với debounce hiệu quả hơn
    let updateCategoryTimer;

    function updateParentCategoryOrder(order) {
        clearTimeout(updateCategoryTimer);
        updateCategoryTimer = setTimeout(function() {
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'updateCategorySTT',
                    order: order
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                    } else {
                        showMessage(result.msg, result.status);
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    showMessage('<?= __('Đã xảy ra lỗi khi cập nhật thứ tự'); ?>', 'error');
                }
            });
        }, 500);
    }

    function postRemove(id) {
        $.ajax({
            url: "<?= BASE_URL('ajaxs/admin/remove.php'); ?>",
            type: 'POST',
            dataType: "JSON",
            data: {
                action: 'removeCategory',
                id: id
            },
            success: function(result) {
                if (result.status == 'success') {
                    showMessage(result.msg, 'success');
                } else {
                    showMessage(result.msg, 'error');
                }
            }
        });
    }

    function RemoveRow(id) {
        cuteAlert({
            type: "question",
            title: "<?= __('Cảnh báo'); ?>",
            message: "<?= __('Bạn có chắc chắn muốn xóa chuyên mục ID'); ?> " + id + " <?= __('này không?'); ?>",
            confirmText: "<?= __('Đồng ý'); ?>",
            cancelText: "<?= __('Hủy'); ?>"
        }).then((e) => {
            if (e) {
                postRemove(id);
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        })
    }

    // Hàm debounce để giảm số lần gọi hàm
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    // Xử lý hiển thị/ẩn form thêm chuyên mục cha
    document.addEventListener('DOMContentLoaded', function() {
        const btnAddParent = document.getElementById('btn-add-parent');
        const btnCloseAddParent = document.getElementById('btn-close-add-parent');
        const cardAddParent = document.getElementById('card-add-parent');

        btnAddParent.addEventListener('click', function() {
            cardAddParent.style.display = 'block';
            // Cuộn trang lên vị trí form
            cardAddParent.scrollIntoView({
                behavior: 'smooth'
            });
        });

        btnCloseAddParent.addEventListener('click', function() {
            cardAddParent.style.display = 'none';
        });

        // Khởi tạo tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Tối ưu hiệu năng kéo thả với SortableJS
    $(document).ready(function() {

        // Khởi tạo SortableJS cho chuyên mục cha - đơn giản
        var sortableParentElement = document.getElementById('sortable-parent-categories');
        if (sortableParentElement) {
            var sortableParent = new Sortable(sortableParentElement, {
                handle: '.handle-parent',
                animation: 150,
                onEnd: function(evt) {
                    // Cập nhật thứ tự chuyên mục cha
                    var parentOrder = [];
                    var items = sortableParentElement.querySelectorAll('li.sortable-parent-item');
                    var total = items.length;

                    items.forEach(function(item, index) {
                        var id = item.getAttribute('data-id');
                        if (id) {
                            var reversedPosition = total - index;
                            parentOrder.push({
                                id: id,
                                position: reversedPosition
                            });
                            var sttInput = document.getElementById('stt' + id);
                            if (sttInput) {
                                sttInput.value = reversedPosition;
                            }
                        }
                    });

                    // Gửi thứ tự mới lên server
                    updateParentCategoryOrder(parentOrder);
                }
            });
        }

        // Khởi tạo sortable cho chuyên mục con
        initChildCategoriesSortable();

        // Tối ưu sự kiện collapse
        const clickHandler = debounce(function() {
            const categoryId = $(this).attr('data-bs-target').replace('#category-', '');
            const icon = $(this).find('.collapse-icon[data-category-id="' + categoryId + '"]');

            setTimeout(function() {
                if ($('#category-' + categoryId).hasClass('show')) {
                    icon.addClass('rotate-icon');
                    localStorage.setItem('last_opened_category', categoryId);
                } else {
                    icon.removeClass('rotate-icon');
                    if (localStorage.getItem('last_opened_category') === categoryId) {
                        localStorage.removeItem('last_opened_category');
                    }
                }
            }, 300);
        }, 50);

        // Đăng ký sự kiện với debounce để tăng hiệu suất
        $('.category-header-content').off('click').on('click', clickHandler);

        // Khôi phục trạng thái tab cuối cùng được mở
        const lastOpenedCategory = localStorage.getItem('last_opened_category');
        if (lastOpenedCategory) {
            // Đóng tất cả các tab trước
            $('.collapse').removeClass('show');

            // Mở tab đã lưu 
            $('#category-' + lastOpenedCategory).addClass('show');

            // Cập nhật biểu tượng mũi tên
            $('.collapse-icon').removeClass('rotate-icon');
            $('.collapse-icon[data-category-id="' + lastOpenedCategory + '"]').addClass('rotate-icon');
        }

        // Xử lý nút đóng tất cả chuyên mục
        $('#collapse-all-btn').on('click', function() {
            $('.collapse').removeClass('show');
            $('.collapse-icon').removeClass('rotate-icon');
            localStorage.removeItem('last_opened_category');
        });

        // Xử lý nút mở tất cả chuyên mục
        $('#expand-all-btn').on('click', function() {
            $('.collapse').addClass('show');
            $('.collapse-icon').addClass('rotate-icon');
        });

        // Khởi tạo sortable cho chuyên mục con
        initChildCategoriesSortable();
    });

    // Hàm khởi tạo SortableJS cho các bảng chuyên mục con - đơn giản
    function initChildCategoriesSortable() {
        document.querySelectorAll('.sortable-child-categories').forEach(function(element) {
            const parentId = element.getAttribute('data-parent-id');
            if (!parentId) return;

            new Sortable(element, {
                handle: '.handle-child',
                animation: 150,
                onEnd: function(evt) {
                    // Thu thập dữ liệu vị trí mới cho các chuyên mục con
                    const childOrder = [];
                    const rows = element.querySelectorAll('tr[data-id]');
                    const total = rows.length;

                    rows.forEach(function(row, index) {
                        const id = row.getAttribute('data-id');
                        if (id) {
                            const reversedPosition = total - index;
                            childOrder.push({
                                id: id,
                                position: reversedPosition
                            });
                            // Cập nhật giá trị input
                            const sttInput = document.getElementById('stt' + id);
                            if (sttInput) {
                                sttInput.value = reversedPosition;
                            }
                        }
                    });

                    // Gửi dữ liệu vị trí mới lên server
                    updateChildCategoryOrder(childOrder, parentId);
                }
            });
        });
    }

    // Hàm cập nhật thứ tự chuyên mục con lên server
    let updateChildCategoryTimer;

    function updateChildCategoryOrder(order, parentId) {
        clearTimeout(updateChildCategoryTimer);
        updateChildCategoryTimer = setTimeout(function() {
            $.ajax({
                url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
                method: "POST",
                dataType: "JSON",
                data: {
                    action: 'updateCategorySubSTT',
                    order: JSON.stringify(order),
                    parent_id: parentId
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, result.status);
                    } else {
                        showMessage(result.msg || '<?= __("Lỗi không xác định"); ?>', result.status);
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    showMessage('<?= __("Đã xảy ra lỗi khi cập nhật thứ tự"); ?>', 'error');
                },
                complete: function() {
                    // $('#loading-overlay').removeClass('active');
                }
            });
        }, 500);
    }

    // ========== elFinder Integration for Category Icon ==========
    var iconFileManagerWindow = null;

    // Open file manager in popup window
    function openIconFileManager() {
        var url = '<?= BASE_URL("admin/elfinder?callback=iconElfinderCallback"); ?>';
        var width = 900;
        var height = 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;

        iconFileManagerWindow = window.open(url, 'elfinderIconManager',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top +
            ',menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes');

        if (iconFileManagerWindow) {
            iconFileManagerWindow.focus();
        }
    }

    // Callback when file is selected from elFinder
    function iconElfinderCallback(fileUrl) {
        document.getElementById('category-icon-path').value = fileUrl;
        document.getElementById('icon-preview').src = fileUrl;
        document.getElementById('icon-preview-container').style.display = 'block';

        // Clear file input since we're using library image
        document.getElementById('category-icon').value = '';
    }

    // Preview uploaded icon
    function previewUploadedIcon(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('icon-preview').src = e.target.result;
                document.getElementById('icon-preview-container').style.display = 'block';
                // Clear library path since we're uploading new image
                document.getElementById('category-icon-path').value = '';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Clear selected icon
    function clearSelectedIcon() {
        document.getElementById('category-icon-path').value = '';
        document.getElementById('category-icon').value = '';
        document.getElementById('icon-preview').src = '';
        document.getElementById('icon-preview-container').style.display = 'none';
    }
</script>