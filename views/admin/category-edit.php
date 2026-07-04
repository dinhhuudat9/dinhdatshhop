<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa chuyên mục') . ' | ' . $CMSNT->site('title')
];
$body['header'] = '

';
$body['footer'] = '
<!-- bs-custom-file-input -->
<script src="' . BASE_URL('public/AdminLTE3/') . 'plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<!-- Page specific script -->
<script>
$(function () {
  bsCustomFileInput.init();
});
</script> 
';
require_once(__DIR__ . '/../../models/is_admin.php');
if (isset($_GET['id'])) {
    $id = check_string($_GET['id']);
    if (!$row = $CMSNT->get_row("SELECT * FROM `categories` WHERE `id` = '$id' ")) {
        redirect(base_url_admin('categories'));
    }
} else {
    redirect(base_url_admin('categories'));
}
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/nav.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_category') != true) {
    $role_name = getRoleName('edit_category');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}
?>
<?php
if (isset($_POST['SaveCategory'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }
    if ($CMSNT->get_row("SELECT * FROM `categories` WHERE `name` = '" . check_string($_POST['name']) . "' AND `id` != " . $row['id'] . " ")) {
        die('<script type="text/javascript">if(!alert("' . __('Tên chuyên mục đã tồn tại trong hệ thống.') . '")){window.history.back().location.reload();}</script>');
    }
    // Kiểm tra icon từ thư viện (elFinder)
    if (!empty($_POST['icon_path'])) {
        $icon_path = trim($_POST['icon_path']);
        // Chuyển đổi URL đầy đủ thành đường dẫn tương đối
        $base_url = rtrim(BASE_URL(), '/');
        if (strpos($icon_path, $base_url) === 0) {
            $new_icon = substr($icon_path, strlen($base_url) + 1);
        } else {
            $new_icon = $icon_path;
        }
        // Xóa icon cũ nếu không phải từ thư viện
        if (!empty($row['icon']) && strpos($row['icon'], 'library/') === false && file_exists($row['icon'])) {
            @unlink($row['icon']);
        }
        $CMSNT->update("categories", [
            'icon' => $new_icon
        ], " `id` = '" . $row['id'] . "' ");
    }
    // Nếu không có icon từ thư viện, kiểm tra upload file
    elseif (check_img('icon') == true) {
        // Xóa icon cũ nếu không phải từ thư viện
        if (!empty($row['icon']) && strpos($row['icon'], 'library/') === false && file_exists($row['icon'])) {
            @unlink($row['icon']);
        }
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/category/' . $rand . '.' . $ext;
        $tmp_name = $_FILES['icon']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $CMSNT->update("categories", [
                'icon' => $uploads_dir
            ], " `id` = '" . $row['id'] . "' ");
        }
    }
    $isInsert = $CMSNT->update("categories", [
        'stt'               => check_string($_POST['stt']),
        'parent_id'         => check_string($_POST['parent_id']),
        'name'              => check_string($_POST['name']),
        'slug'              => check_string($_POST['slug']),
        'description'       => check_string($_POST['description']),
        'status'            => check_string($_POST['status'])
    ], " `id` = '" . $row['id'] . "' ");
    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Category (" . $row['name'] . " ID " . $row['id'] . ")."
        ]);
        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Category (" . $row['name'] . " ID " . $row['id'] . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);
        die('<script type="text/javascript">if(!alert("' . __('Lưu thành công!') . '")){window.history.back().location.reload();}</script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Lưu thất bại!') . '")){window.history.back().location.reload();}</script>');
    }
}
?>



<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><a type="button" class="btn btn-dark btn-raised-shadow btn-wave btn-sm me-1"
                    href="<?= base_url_admin('categories'); ?>"><i class="fa-solid fa-arrow-left"></i></a> <?= __('Chỉnh sửa chuyên mục'); ?> <?= $row['name']; ?></h1>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <?= __('CHỈNH SỬA CHUYÊN MỤC SẢN PHẨM'); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="row mb-4">
                                <div class="col-sm-12">
                                    <div class="mb-4">
                                        <label class="form-label" for="stt"><?= __('Ưu tiên:'); ?></label>
                                        <input type="text" class="form-control" value="<?= $row['stt']; ?>" name="stt" required>
                                        <small class="text-muted"><?= __('Ưu tiên càng cao, chuyên mục càng hiển thị trên cùng'); ?></small>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="name"><?= __('Tên chuyên mục:'); ?></label>
                                        <input type="text" class="form-control" value="<?= $row['name']; ?>" name="name"
                                            placeholder="<?= __('Nhập tên chuyên mục'); ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="slug"><?= __('Slug:'); ?></label>
                                        <input type="text" class="form-control" value="<?= $row['slug']; ?>" name="slug"
                                            placeholder="<?= __('Nhập slug chuyên mục'); ?>" required>
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
                                    <div class="row mb-4" <?= $row['parent_id'] == 0 ? 'style="display:none;"' : ''; ?>>
                                        <label class="col-sm-4 col-form-label"
                                            for="example-hf-email"><?= __('Chuyên mục cha:'); ?>
                                            <span class="text-danger">*</span></label>
                                        <div class="col-sm-8">
                                            <select class="form-control mb-2" name="parent_id" required>
                                                <option value="0"><?= __('-- Chuyên mục cha --'); ?></option>
                                                <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = 0 ") as $option): ?>
                                                    <option <?= $option['id'] == $row['parent_id'] ? 'selected' : ''; ?>
                                                        value="<?= $option['id']; ?>"><?= $option['name']; ?></option>
                                                    <?php foreach ($CMSNT->get_list("SELECT * FROM `categories` WHERE `parent_id` = '" . $option['id'] . "' ") as $option1): ?>
                                                        <option disabled value="<?= $option1['id']; ?>">__<?= $option1['name']; ?>
                                                        </option>
                                                    <?php endforeach ?>
                                                <?php endforeach ?>
                                            </select>
                                            <i><?= __('Chỉ định một chuyên mục cha để tạo đa cấp. Chẳng hạn, chuyên mục Facebook sẽ là chuyên mục cha của Clone Facebook và VIA Facebook.'); ?></i>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="code"><?= __('Icon:'); ?></label>

                                        <!-- Image Preview -->
                                        <div id="icon-preview-container" class="mb-2" <?= !empty($row['icon']) ? '' : 'style="display: none;"' ?>>
                                            <img id="icon-preview" src="<?= !empty($row['icon']) ? base_url($row['icon']) : '' ?>" alt="Preview" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
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
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_left"><?= __('Description SEO:'); ?></label>
                                        <textarea class="form-control" rows="3"
                                            name="description"><?= $row['description']; ?></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="symbol_right"><?= __('Status:'); ?></label>
                                        <select class="form-control" name="status" required>
                                            <option <?= $row['status'] == 'show' ? 'selected' : ''; ?> value="show">ON</option>
                                            <option <?= $row['status'] == 'hide' ? 'selected' : ''; ?> value="hide">OFF</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <a type="button" class="btn btn-danger" href="<?= base_url_admin('categories'); ?>"><i
                                    class="fa fa-fw fa-undo me-1"></i> <?= __('Back'); ?></a>
                            <button type="submit" name="SaveCategory" class="btn btn-primary"><i
                                    class="fa fa-fw fa-save me-1"></i> <?= __('Save'); ?></button>
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