<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý chuyên mục blog').' | '.$CMSNT->site('title'),
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
$search = '';
$status_filter = -1;

// WHERE an toàn với prepared statements
$where_conditions = ["1 = 1"];
$where_params = [];

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
        $where_conditions[] = '(`name` LIKE ? OR `slug` LIKE ? OR `description` LIKE ?)';
        $searchPattern = '%' . $search . '%';
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
        $where_params[] = $searchPattern;
    } else {
        $search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Tổng số chuyên mục
$countSql = "SELECT COUNT(*) AS total_count FROM `blog_categories` WHERE $where_clause";
$totalRow = $CMSNT->get_row_safe($countSql, $where_params);
$total = (int)($totalRow['total_count'] ?? 0);
$total_pages = ceil($total / $limit);

// Lấy danh sách chuyên mục
$listSql = "SELECT * FROM `blog_categories` WHERE $where_clause ORDER BY `sort_order` ASC, `id` DESC LIMIT ?, ?";
$listParams = array_merge($where_params, [$from, $limit]);
$categories = $CMSNT->get_list_safe($listSql, $listParams);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-folder me-1"></i><?=__('Quản lý chuyên mục blog');?>
            </h1>
            <div class="ms-md-1 ms-0">
                <button type="button" class="btn btn-primary btn-sm" onclick="showAddCategoryModal()">
                    <i class="fa-solid fa-plus me-1"></i><?=__('Thêm chuyên mục');?>
                </button>
            </div>
        </div>

        <!-- Bộ lọc -->
        <div class="row mb-3">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form method="GET" action="<?= base_url(); ?>" class="row g-3">
                            <input type="hidden" name="module" value="<?=$CMSNT->site('path_admin');?>">
                            <input type="hidden" name="action" value="blog-category">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><?=__('Tìm kiếm');?></label>
                                    <input type="text" class="form-control" name="search" 
                                        value="<?=htmlspecialchars($search);?>" 
                                        placeholder="<?=__('Tên chuyên mục, slug...');?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?=__('Trạng thái');?></label>
                                    <select class="form-select" name="status">
                                        <option value="" <?=$status_filter == -1 ? 'selected' : '';?>><?=__('Tất cả');?></option>
                                        <option value="1" <?=$status_filter == 1 ? 'selected' : '';?>><?=__('Đang hoạt động');?></option>
                                        <option value="0" <?=$status_filter == 0 ? 'selected' : '';?>><?=__('Đã tắt');?></option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><?=__('Số lượng/trang');?></label>
                                    <select class="form-select" name="limit">
                                        <option value="10" <?=$limit == 10 ? 'selected' : '';?>>10</option>
                                        <option value="20" <?=$limit == 20 ? 'selected' : '';?>>20</option>
                                        <option value="50" <?=$limit == 50 ? 'selected' : '';?>>50</option>
                                        <option value="100" <?=$limit == 100 ? 'selected' : '';?>>100</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fa-solid fa-filter me-1"></i><?=__('Lọc');?>
                                    </button>
                                    <a href="<?=base_url_admin('blog-category');?>" class="btn btn-secondary">
                                        <i class="fa-solid fa-times me-1"></i><?=__('Bỏ lọc');?>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách chuyên mục -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body p-0">
                        <?php if(count($categories) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover border text-nowrap">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;"><i class="fa-solid fa-grip-vertical"></i></th>
                                        <th><?=__('Tên chuyên mục');?></th>
                                        <th><?=__('Slug');?></th>
                                        <th class="text-center"><?=__('Số bài viết');?></th>
                                        <th class="text-center"><?=__('Trạng thái');?></th>
                                        <th><?=__('Ngày tạo');?></th>
                                        <th><?=__('Thao tác');?></th>
                                    </tr>
                                </thead>
                                <tbody id="sortable-categories">
                                    <?php foreach ($categories as $category): 
                                        // Đếm số bài viết trong chuyên mục
                                        $blog_count = $CMSNT->get_row_safe("SELECT COUNT(*) AS total FROM `blogs` WHERE `category_id` = ?", [$category['id']]);
                                        $total_blogs = (int)($blog_count['total'] ?? 0);
                                    ?>
                                    <tr id="category-<?=$category['id'];?>" data-category-id="<?=$category['id'];?>" data-sort-order="<?=$category['sort_order'];?>">
                                        <td class="text-center handle-category" style="cursor: move;">
                                            <i class="fa-solid fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if(!empty($category['image'])): ?>
                                                <div class="flex-shrink-0">
                                                    <img src="<?=BASE_URL($category['image']);?>" 
                                                        alt="<?=htmlspecialchars(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'));?>"
                                                        class="rounded" 
                                                        style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #dee2e6;">
                                                </div>
                                                <?php endif; ?>
                                                <div class="flex-grow-1">
                                                    <strong><?=htmlspecialchars(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'));?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?=htmlspecialchars($category['slug']);?></code>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-transparent">
                                                <i class="fa-solid fa-newspaper me-1"></i><?=format_cash($total_blogs);?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" 
                                                    id="status<?=$category['id'];?>" 
                                                    <?=$category['status'] == 1 ? 'checked' : '';?>
                                                    onchange="updateStatus('<?=$category['id'];?>', this.checked ? 1 : 0)"
                                                    style="transform: scale(1.5);">
                                            </div>
                                        </td>
                                        <td>
                                            <small>
                                                <i class="fa-regular fa-clock me-1"></i><?=$category['created_at'];?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-list">
                                                <button onclick="editCategory('<?=$category['id'];?>')" 
                                                    class="btn btn-sm btn-info">
                                                    <i class="fa-solid fa-edit me-1"></i><?=__('Sửa');?>
                                                </button>
                                                <button onclick="removeCategory('<?=$category['id'];?>')" 
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
                            $pagination_url = base_url_admin('blog-category');
                            $pagination_url .= '&limit='.$limit;
                            if(!empty($search)) $pagination_url .= '&search='.urlencode($search);
                            if($status_filter != -1) $pagination_url .= '&status='.$status_filter;
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
                            <i class="fa-solid fa-exclamation-circle me-2"></i><?=__('Chưa có chuyên mục nào.');?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm/sửa chuyên mục -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">
                    <i class="fa-solid fa-folder me-2"></i><?=__('Thêm chuyên mục blog');?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" enctype="multipart/form-data">
                    <input type="hidden" id="category_id" name="category_id">
                    
                    <div class="mb-3">
                        <label class="form-label"><?=__('Tên chuyên mục:');?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="name" 
                            placeholder="<?=__('VD: Tin tức, Hướng dẫn...');?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?=__('Slug:');?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_slug" name="slug" 
                            placeholder="<?=__('Slug sẽ được tạo tự động');?>" required>
                        <small class="text-muted"><?=__('URL thân thiện, tự động tạo từ tên chuyên mục');?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?=__('Mô tả:');?></label>
                        <textarea class="form-control" id="category_description" name="description" rows="3"
                            placeholder="<?=__('Nhập mô tả ngắn về chuyên mục');?>"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?=__('Ảnh đại diện:');?></label>
                        <input type="file" class="form-control" id="category_image" name="image" accept="image/*">
                        <small class="text-muted"><?=__('Ảnh đại diện cho chuyên mục');?></small>
                        <div id="category_image_preview" class="mt-2" style="display: none;">
                            <img id="category_image_preview_img" src="" alt="" style="max-width: 150px; max-height: 150px; border-radius: 4px; border: 1px solid #dee2e6;">
                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="removeCategoryImagePreview()">
                                <i class="fa-solid fa-times me-1"></i><?=__('Xóa ảnh');?>
                            </button>
                        </div>
                        <div id="category_current_image" class="mt-2" style="display: none;">
                            <p class="text-muted mb-1"><?=__('Ảnh hiện tại:');?></p>
                            <img id="category_current_image_img" src="" alt="" style="max-width: 150px; max-height: 150px; border-radius: 4px; border: 1px solid #dee2e6;">
                        </div>
                    </div>

                    <!-- SEO Meta -->
                    <div class="alert alert-info">
                        <i class="fa-solid fa-search me-2"></i><strong><?=__('Tối ưu SEO');?></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?=__('Meta Title:');?></label>
                        <input type="text" class="form-control" id="category_meta_title" name="meta_title" 
                            placeholder="<?=__('Tiêu đề hiển thị trên Google (60-70 ký tự)');?>" maxlength="255">
                        <small class="text-muted"><?=__('Để trống sẽ dùng tên chuyên mục');?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?=__('Meta Description:');?></label>
                        <textarea class="form-control" id="category_meta_description" name="meta_description" rows="3"
                            placeholder="<?=__('Mô tả hiển thị trên Google (150-160 ký tự)');?>" maxlength="500"></textarea>
                        <small class="text-muted"><?=__('Để trống sẽ dùng mô tả chuyên mục');?></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?=__('Meta Keywords:');?></label>
                        <input type="text" class="form-control" id="category_meta_keywords" name="meta_keywords" 
                            placeholder="<?=__('VD: tin tức, hướng dẫn, blog...');?>">
                        <small class="text-muted"><?=__('Từ khóa cách nhau bằng dấu phẩy');?></small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="category_status_input" name="status" value="1" checked>
                            <label class="form-check-label" for="category_status_input">
                                <?=__('Kích hoạt chuyên mục');?>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times me-1"></i><?=__('Hủy');?>
                </button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">
                    <i class="fa-solid fa-save me-1"></i><?=__('Lưu');?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>

<script>
// Modal instance
var categoryModal;
document.addEventListener('DOMContentLoaded', function() {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    
    // Khởi tạo Sortable cho bảng categories
    var sortableCategoriesEl = document.getElementById('sortable-categories');
    if(sortableCategoriesEl) {
        new Sortable(sortableCategoriesEl, {
            handle: '.handle-category',
            animation: 150,
            onEnd: function(evt) {
                updateCategoriesOrder();
            }
        });
    }
    
    // Preview ảnh khi chọn
    var categoryImageInput = document.getElementById('category_image');
    if(categoryImageInput) {
        categoryImageInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if(file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('category_image_preview_img').src = e.target.result;
                    document.getElementById('category_image_preview').style.display = 'block';
                    document.getElementById('category_current_image').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

// Tự động tạo slug từ tên
function removeVietnameseTones(str) {
    return str.normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D');
}

document.addEventListener('DOMContentLoaded', function() {
    var nameInput = document.getElementById('category_name');
    var slugInput = document.getElementById('category_slug');
    
    if(nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if(!document.getElementById('category_id').value) {
                var slug = removeVietnameseTones(this.value.toLowerCase())
                    .replace(/ /g, '-')
                    .replace(/[^\w-]+/g, '');
                slugInput.value = slug;
            }
        });
    }
});

// Hiển thị modal thêm chuyên mục
function showAddCategoryModal() {
    document.getElementById('categoryModalTitle').innerHTML = '<i class="fa-solid fa-plus-circle me-2"></i><?=__("Thêm chuyên mục blog");?>';
    document.getElementById('categoryForm').reset();
    document.getElementById('category_id').value = '';
    document.getElementById('category_status_input').checked = true;
    document.getElementById('category_image_preview').style.display = 'none';
    document.getElementById('category_current_image').style.display = 'none';
    categoryModal.show();
}

// Sửa chuyên mục
function editCategory(categoryId) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/view.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'getBlogCategory',
            id: categoryId
        },
        success: function(result) {
            if (result.status == 'success') {
                document.getElementById('categoryModalTitle').innerHTML = '<i class="fa-solid fa-edit me-2"></i><?=__("Chỉnh sửa chuyên mục");?>';
                document.getElementById('category_id').value = result.data.id;
                document.getElementById('category_name').value = result.data.name;
                document.getElementById('category_slug').value = result.data.slug;
                document.getElementById('category_description').value = result.data.description || '';
                document.getElementById('category_meta_title').value = result.data.meta_title || '';
                document.getElementById('category_meta_description').value = result.data.meta_description || '';
                document.getElementById('category_meta_keywords').value = result.data.meta_keywords || '';
                document.getElementById('category_status_input').checked = result.data.status == 1;
                
                // Hiển thị ảnh hiện tại
                if(result.data.image && result.data.image !== '') {
                    document.getElementById('category_current_image_img').src = '<?=base_url();?>' + result.data.image;
                    document.getElementById('category_current_image').style.display = 'block';
                } else {
                    document.getElementById('category_current_image').style.display = 'none';
                }
                document.getElementById('category_image_preview').style.display = 'none';
                document.getElementById('category_image').value = '';
                
                categoryModal.show();
            } else {
                showMessage(result.msg, 'error');
            }
        },
        error: function() {
            showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
        }
    });
}

// Lưu chuyên mục
function saveCategory() {
    var imageFile = document.getElementById('category_image').files[0];
    var hasImage = imageFile !== undefined;
    
    var formData;
    if(hasImage) {
        formData = new FormData();
        formData.append('action', $('#category_id').val() ? 'updateBlogCategory' : 'addBlogCategory');
        formData.append('id', $('#category_id').val() || '');
        formData.append('name', $('#category_name').val());
        formData.append('slug', $('#category_slug').val());
        formData.append('description', $('#category_description').val());
        formData.append('meta_title', $('#category_meta_title').val());
        formData.append('meta_description', $('#category_meta_description').val());
        formData.append('meta_keywords', $('#category_meta_keywords').val());
        formData.append('status', $('#category_status_input').is(':checked') ? 1 : 0);
        formData.append('image', imageFile);
    } else {
        formData = {
            action: $('#category_id').val() ? 'updateBlogCategory' : 'addBlogCategory',
            id: $('#category_id').val(),
            name: $('#category_name').val(),
            slug: $('#category_slug').val(),
            description: $('#category_description').val(),
            meta_title: $('#category_meta_title').val(),
            meta_description: $('#category_meta_description').val(),
            meta_keywords: $('#category_meta_keywords').val(),
            status: $('#category_status_input').is(':checked') ? 1 : 0
        };
    }

    var nameValue = hasImage ? formData.get('name') : formData.name;
    var slugValue = hasImage ? formData.get('slug') : formData.slug;
    
    if(!nameValue || !slugValue) {
        showMessage('<?=__("Vui lòng điền đầy đủ thông tin bắt buộc");?>', 'error');
        return;
    }

    var actionValue = hasImage ? formData.get('action') : formData.action;
    var ajaxUrl = actionValue == 'addBlogCategory' ? "<?=BASE_URL("ajaxs/admin/create.php");?>" : "<?=BASE_URL("ajaxs/admin/update.php");?>";

    $.ajax({
        url: ajaxUrl,
        method: "POST",
        dataType: "JSON",
        data: formData,
        processData: hasImage ? false : true,
        contentType: hasImage ? false : 'application/x-www-form-urlencoded',
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, 'success');
                categoryModal.hide();
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

// Xóa preview ảnh
function removeCategoryImagePreview() {
    document.getElementById('category_image').value = '';
    document.getElementById('category_image_preview').style.display = 'none';
}

// Cập nhật trạng thái
function updateStatus(id, status) {
    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'updateBlogCategoryStatus',
            id: id,
            status: status
        },
        success: function(result) {
            if (result.status == 'success') {
                showMessage(result.msg, result.status);
            } else {
                showMessage(result.msg, result.status);
                $('#status' + id).prop('checked', !status);
            }
        },
        error: function() {
            showMessage('<?=__("Đã xảy ra lỗi");?>', 'error');
            $('#status' + id).prop('checked', !status);
        }
    });
}

// Xóa chuyên mục
function removeCategory(id) {
    cuteAlert({
        type: "question",
        title: "<?=__('Cảnh báo');?>",
        message: "<?=__('Bạn có chắc chắn muốn xóa chuyên mục này không? Các bài viết thuộc chuyên mục sẽ chuyển về Chưa phân loại.');?>",
        confirmText: "<?=__('Đồng ý');?>",
        cancelText: "<?=__('Hủy');?>"
    }).then((e) => {
        if (e) {
            $.ajax({
                url: "<?=BASE_URL('ajaxs/admin/remove.php');?>",
                type: 'POST',
                dataType: "JSON",
                data: {
                    action: 'removeBlogCategory',
                    id: id
                },
                success: function(result) {
                    if (result.status == 'success') {
                        showMessage(result.msg, 'success');
                        $('#category-' + id).fadeOut(300, function() {
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

// Cập nhật thứ tự chuyên mục
function updateCategoriesOrder() {
    var order = [];
    $('#sortable-categories tr').each(function(index) {
        var categoryId = $(this).data('category-id');
        if(categoryId) {
            order.push({
                id: categoryId,
                sort_order: index
            });
        }
    });

    $.ajax({
        url: "<?=BASE_URL("ajaxs/admin/update.php");?>",
        method: "POST",
        dataType: "JSON",
        data: {
            action: 'updateBlogCategoriesOrder',
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
</script>

