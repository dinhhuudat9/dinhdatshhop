<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Thêm sản phẩm') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_product') != true) {
    $role_name = getRoleName('edit_product');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

if (isset($_POST['submit'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }

    $name = trim(strip_tags($_POST['name']));
    $slug = validate_string($_POST['slug']);
    // Xử lý multi-category
    $category_ids = [];
    if (isset($_POST['category_ids']) && is_array($_POST['category_ids'])) {
        foreach ($_POST['category_ids'] as $cat_id) {
            $cid = validate_int($cat_id, 1);
            if ($cid) $category_ids[] = $cid;
        }
    }
    $category_ids_str = implode(',', $category_ids);
    $description = trim($_POST['description']);
    $status = isset($_POST['status']) ? 1 : 0;
    $sold = isset($_POST['sold']) ? max(0, (int)$_POST['sold']) : 0;

    if (empty($category_ids)) {
        die('<script type="text/javascript">if(!alert("' . __('Vui lòng chọn ít nhất một chuyên mục.') . '")){window.history.back();}</script>');
    }

    if (empty($name)) {
        die('<script type="text/javascript">if(!alert("' . __('Vui lòng nhập tên sản phẩm.') . '")){window.history.back();}</script>');
    }
    if (empty($slug)) {
        $slug = create_slug($name);
    }
    if ($CMSNT->get_row_safe("SELECT * FROM `products` WHERE `slug` = ?", [$slug])) {
        die('<script type="text/javascript">if(!alert("' . __('Slug này đã tồn tại trong hệ thống.') . '")){window.history.back();}</script>');
    }

    // Xử lý ảnh - ưu tiên từ thư viện, nếu không có thì upload mới
    $url_image = null;

    // Kiểm tra ảnh từ thư viện (elFinder)
    if (!empty($_POST['image_path'])) {
        $image_path = trim($_POST['image_path']);
        // Chuyển đổi URL đầy đủ thành đường dẫn tương đối
        $base_url = rtrim(BASE_URL(), '/');
        if (strpos($image_path, $base_url) === 0) {
            $url_image = substr($image_path, strlen($base_url) + 1);
        } else {
            $url_image = $image_path;
        }
    }
    // Nếu không có ảnh từ thư viện, kiểm tra upload file
    elseif (check_img('image') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/product_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $url_image = $uploads_dir;
        }
    }

    $isInsert = $CMSNT->insert("products", [
        'category_ids'  => $category_ids_str,
        'name'          => $name,
        'slug'          => $slug,
        'description'   => $description,
        'image'         => $url_image,
        'status'        => $status,
        'sold'          => $sold,
        'created_at'    => gettime(),
        'updated_at'    => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Product (" . $name . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Add Product (" . $name . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Thêm sản phẩm thành công!') . '")){location.href = "' . base_url_admin('products') . '";}  </script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Thêm sản phẩm thất bại!') . '")){window.history.back();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div>
                <h1 class="page-name fw-semibold fs-18 mb-0">
                    <i class="fa-solid fa-plus-circle me-1"></i><?= __('Thêm sản phẩm mới'); ?>
                </h1>
            </div>
            <div class="ms-md-1 ms-0">
                <a href="<?= base_url_admin('products'); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i><?= __('Quay lại'); ?>
                </a>
            </div>
        </div>

        <!-- Form thêm sản phẩm -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Tên sản phẩm:'); ?> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" id="product-name"
                                            placeholder="<?= __('Nhập tên sản phẩm'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Slug:'); ?> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="slug" id="product-slug"
                                            placeholder="<?= __('Slug sẽ được tạo tự động'); ?>" required>
                                        <small class="text-muted"><?= __('Slug sẽ được tạo tự động từ tên sản phẩm'); ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Chuyên mục:'); ?> <span class="text-danger">*</span></label>
                                        <small class="text-muted d-block mb-2"><?= __('Chọn một hoặc nhiều chuyên mục cho sản phẩm. Chuyên mục đầu tiên được chọn sẽ là chuyên mục chính.'); ?></small>
                                        <div class="category-checkbox-list" style="max-height: 300px; overflow-y: auto; border: 1px solid var(--default-border); border-radius: 0.375rem; padding: 10px;">
                                            <?php
                                            $parent_categories = $CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 AND `status` = 'show' ORDER BY `name` ASC");
                                            foreach ($parent_categories as $parent_cat):
                                                $child_categories = $CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $parent_cat['id'] . "' AND `status` = 'show' ORDER BY `name` ASC");
                                                if (count($child_categories) > 0):
                                            ?>
                                                    <div class="category-parent-group mb-2">
                                                        <strong class="text-primary"><i class="fa-solid fa-folder me-1"></i><?= $parent_cat['name']; ?></strong>
                                                        <div class="ms-3 mt-1">
                                                            <?php foreach ($child_categories as $cat): ?>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="category_ids[]" value="<?= $cat['id']; ?>" id="cat_<?= $cat['id']; ?>">
                                                                    <label class="form-check-label" for="cat_<?= $cat['id']; ?>"><?= $cat['name']; ?></label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                            <?php endif;
                                            endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Ảnh sản phẩm:'); ?></label>

                                        <!-- Image Preview -->
                                        <div id="image-preview-container" class="mb-2" style="display: none;">
                                            <img id="image-preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                            <button type="button" class="btn btn-sm btn-danger ms-2" onclick="clearSelectedImage()">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </div>

                                        <!-- Hidden input for library image path -->
                                        <input type="hidden" name="image_path" id="product-image-path" value="">

                                        <div class="d-flex gap-2 align-items-start">
                                            <!-- Upload file option -->
                                            <div class="flex-grow-1">
                                                <input type="file" class="form-control" name="image" id="product-image"
                                                    accept="image/png,image/jpeg,image/jpg,image/gif,image/svg,image/webp"
                                                    onchange="previewUploadedImage(this)">
                                            </div>

                                            <!-- Browse Library button -->
                                            <button type="button" class="btn btn-outline-primary" onclick="openFileManager()">
                                                <i class="fa-solid fa-folder-open me-1"></i><?= __('Thư viện'); ?>
                                            </button>
                                        </div>

                                        <small class="text-muted d-block mt-1"><?= __('Upload ảnh mới hoặc chọn từ thư viện'); ?></small>
                                        <small class="text-info d-block"><i class="fa-solid fa-circle-info me-1"></i><?= __('Khuyến nghị: 1000x500px (tỷ lệ 2:1), định dạng WEBP hoặc JPG'); ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Mô tả:'); ?></label>
                                        <textarea class="form-control" name="description" id="product_description" rows="4"
                                            placeholder="<?= __('Nhập mô tả sản phẩm'); ?>"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="status"
                                                id="productStatus" checked>
                                            <label class="form-check-label" for="productStatus">
                                                <?= __('Kích hoạt sản phẩm'); ?>
                                            </label>
                                        </div>
                                        <small class="text-muted"><?= __('Chỉ sản phẩm được kích hoạt mới hiển thị'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?= __('Số lượng đã bán:'); ?></label>
                                        <input type="number" class="form-control" name="sold" id="product-sold"
                                            value="0" min="0"
                                            placeholder="<?= __('Nhập số lượng đã bán'); ?>">
                                        <small class="text-muted"><?= __('Có thể nhập số lượng ảo ban đầu'); ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <a href="<?= base_url_admin('products'); ?>" class="btn btn-secondary me-2">
                                    <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                                </a>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-1"></i><?= __('Thêm sản phẩm'); ?>
                                </button>
                            </div>
                        </form>
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
    // Tự động tạo slug từ tên sản phẩm
    function removeVietnameseTones(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D');
    }

    document.getElementById('product-name').addEventListener('input', function() {
        var productName = this.value;
        var slug = removeVietnameseTones(productName.toLowerCase())
            .replace(/ /g, '-')
            .replace(/[^\w-]+/g, '');
        document.getElementById('product-slug').value = slug;
    });

    // Khởi tạo CKEditor cho description
    var productDescriptionEditor;
    if (typeof CKEDITOR !== 'undefined') {
        productDescriptionEditor = CKEDITOR.replace("product_description", {
            toolbar: [{
                    name: 'styles',
                    items: ['Format', 'Font', 'FontSize']
                },
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'Strike']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
                },
                {
                    name: 'links',
                    items: ['Link', 'Unlink']
                },
                {
                    name: 'insert',
                    items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar']
                },
                {
                    name: 'colors',
                    items: ['TextColor', 'BGColor']
                },
                {
                    name: 'tools',
                    items: ['Maximize', 'ShowBlocks', 'Source']
                }
            ],
            extraPlugins: 'image',
            language: 'vi',
            height: 300,
            resize_enabled: true,
            allowedContent: true,
            removeDialogTabs: 'image:advanced;image:Link'
        });
    }

    // ========== elFinder Integration ==========
    var fileManagerWindow = null;

    // Open file manager in popup window
    function openFileManager() {
        var url = '<?= BASE_URL("admin/elfinder?callback=elfinderCallback"); ?>';
        var width = 900;
        var height = 600;
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;

        fileManagerWindow = window.open(url, 'elfinderFileManager',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top +
            ',menubar=no,toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes');

        if (fileManagerWindow) {
            fileManagerWindow.focus();
        }
    }

    // Callback when file is selected from elFinder
    function elfinderCallback(fileUrl) {
        document.getElementById('product-image-path').value = fileUrl;
        document.getElementById('image-preview').src = fileUrl;
        document.getElementById('image-preview-container').style.display = 'block';

        // Clear file input since we're using library image
        document.getElementById('product-image').value = '';
    }

    // Preview uploaded image
    function previewUploadedImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('image-preview').src = e.target.result;
                document.getElementById('image-preview-container').style.display = 'block';
                // Clear library path since we're uploading new image
                document.getElementById('product-image-path').value = '';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Clear selected image
    function clearSelectedImage() {
        document.getElementById('product-image-path').value = '';
        document.getElementById('product-image').value = '';
        document.getElementById('image-preview').src = '';
        document.getElementById('image-preview-container').style.display = 'none';
    }
</script>