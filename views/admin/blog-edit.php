<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Chỉnh sửa bài viết') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
require_once(__DIR__ . '/../../models/is_license.php');
if (checkPermission($getUser['admin'], 'edit_blog') != true) {
    $role_name = getRoleName('edit_blog');
    die('<script type="text/javascript">if(!alert("' . sprintf(__('Bạn không có quyền %s'), $role_name) . '")){window.history.back();}</script>');
}

$id = isset($_GET['id']) ? validate_int($_GET['id'], 1) : 0;
if (!$id) {
    die('<script type="text/javascript">if(!alert("' . __('ID không hợp lệ') . '")){window.history.back();}</script>');
}

$blog = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `id` = ?", [$id]);
if (!$blog) {
    die('<script type="text/javascript">if(!alert("' . __('Bài viết không tồn tại') . '")){window.history.back();}</script>');
}

if (isset($_POST['submit'])) {
    // Kiểm tra CSRF token
    checkCSRF();

    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("' . __('Không được dùng chức năng này vì đây là trang web demo.') . '")){window.history.back().location.reload();}</script>');
    }

    $title = validate_string($_POST['title'], 255, 1);
    $slug = validate_string($_POST['slug'], 255, 1);
    $category_id = validate_int($_POST['category_id'], 0);
    $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $status = validate_string($_POST['status'], 20);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // SEO Meta
    $meta_title = validate_string($_POST['meta_title'] ?? '', 255);
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';

    // Published date
    $published_at = $blog['published_at'];
    if ($status == 'published' || $status == 'scheduled') {
        $published_at_input = validate_string($_POST['published_at'] ?? '', 20);
        if ($published_at_input && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $published_at_input)) {
            $published_at = $published_at_input . ':00';
        } elseif (empty($blog['published_at'])) {
            $published_at = gettime();
        }
    }

    if ($title === false || empty($title)) {
        die('<script type="text/javascript">if(!alert("' . __('Vui lòng nhập tiêu đề bài viết.') . '")){window.history.back();}</script>');
    }
    if ($slug === false || empty($slug)) {
        $slug = create_slug($title);
    }
    if ($status === false || !in_array($status, ['draft', 'published', 'scheduled'])) {
        die('<script type="text/javascript">if(!alert("' . __('Trạng thái không hợp lệ.') . '")){window.history.back();}</script>');
    }

    // Kiểm tra slug trùng (trừ chính nó)
    $check_slug = $CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `slug` = ? AND `id` != ?", [$slug, $id]);
    if ($check_slug) {
        die('<script type="text/javascript">if(!alert("' . __('Slug này đã tồn tại. Vui lòng chọn slug khác.') . '")){window.history.back();}</script>');
    }

    // Xử lý upload ảnh
    $update_data = [
        'category_id'       => $category_id ?: 0,
        'title'             => $title,
        'slug'              => $slug,
        'excerpt'           => $excerpt,
        'content'           => $content,
        'meta_title'        => $meta_title ?: $title,
        'meta_description'  => $meta_description ?: $excerpt,
        'meta_keywords'     => $meta_keywords,
        'is_featured'       => $is_featured,
        'status'            => $status,
        'published_at'      => $published_at,
        'updated_at'        => gettime()
    ];

    if (check_img('thumbnail') == true) {
        // Xóa ảnh cũ nếu có
        if (!empty($blog['thumbnail']) && file_exists($blog['thumbnail'])) {
            @unlink($blog['thumbnail']);
        }
        // Upload ảnh mới
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/blog_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['thumbnail']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $uploads_dir);
        if ($addlogo) {
            $update_data['thumbnail'] = $uploads_dir;
        }
    }

    $isUpdate = $CMSNT->update("blogs", $update_data, " `id` = ? ", [$id]);

    if ($isUpdate) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Edit Blog Post ID " . $id . " (" . $title . ")."
        ]);

        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', "Edit Blog Post ID " . $id . " (" . $title . ").", $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die('<script type="text/javascript">if(!alert("' . __('Cập nhật bài viết thành công!') . '")){location.href = "' . base_url_admin('blogs') . '";}  </script>');
    } else {
        die('<script type="text/javascript">if(!alert("' . __('Không có thay đổi nào!') . '")){window.history.back();}</script>');
    }
}
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Tiêu đề trang -->
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-name fw-semibold fs-18 mb-0">
                <i class="fa-solid fa-edit me-1"></i><?= __('Chỉnh sửa bài viết'); ?>
            </h1>
            <div class="ms-md-1 ms-0">
                <a href="<?= base_url_admin('blogs'); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i><?= __('Quay lại'); ?>
                </a>
            </div>
        </div>

        <!-- Form chỉnh sửa bài viết -->
        <form action="" method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="row">
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?= __('Tiêu đề bài viết:'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" id="blog-title"
                                    value="<?= htmlspecialchars(html_entity_decode($blog['title'], ENT_QUOTES, 'UTF-8')); ?>"
                                    placeholder="<?= __('Nhập tiêu đề bài viết'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= __('Slug (URL thân thiện):'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="slug" id="blog-slug"
                                    value="<?= htmlspecialchars($blog['slug']); ?>"
                                    placeholder="<?= __('Slug sẽ được tạo tự động'); ?>" required>
                                <small class="text-muted"><?= __('URL thân thiện SEO'); ?></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= __('Mô tả ngắn (Excerpt):'); ?></label>
                                <textarea class="form-control" name="excerpt" id="blog-excerpt" rows="3"
                                    placeholder="<?= __('Nhập mô tả ngắn về bài viết'); ?>"><?= htmlspecialchars(html_entity_decode($blog['excerpt'], ENT_QUOTES, 'UTF-8')); ?></textarea>
                                <small class="text-muted"><?= __('Mô tả này sẽ hiển thị trong danh sách bài viết và meta description'); ?></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= __('Nội dung:'); ?></label>
                                <textarea class="form-control" name="content" id="blog_content" rows="10"
                                    placeholder="<?= __('Nhập nội dung bài viết'); ?>"><?= htmlspecialchars($blog['content'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Meta -->
                    <div class="card custom-card mt-3">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-search me-2"></i><?= __('Tối ưu SEO'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?= __('Meta Title:'); ?></label>
                                <input type="text" class="form-control" name="meta_title" id="blog-meta-title"
                                    value="<?= htmlspecialchars($blog['meta_title']); ?>"
                                    placeholder="<?= __('Tiêu đề SEO (để trống sẽ dùng tiêu đề bài viết)'); ?>" maxlength="255">
                                <small class="text-muted"><?= __('60-70 ký tự để hiển thị tốt trên Google'); ?></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= __('Meta Description:'); ?></label>
                                <textarea class="form-control" name="meta_description" id="blog-meta-description" rows="3"
                                    placeholder="<?= __('Mô tả SEO (để trống sẽ dùng excerpt)'); ?>" maxlength="500"><?= htmlspecialchars($blog['meta_description']); ?></textarea>
                                <small class="text-muted"><?= __('150-160 ký tự để hiển thị tốt trên Google'); ?></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?= __('Meta Keywords:'); ?></label>
                                <input type="text" class="form-control" name="meta_keywords" id="blog-meta-keywords"
                                    value="<?= htmlspecialchars($blog['meta_keywords']); ?>"
                                    placeholder="<?= __('VD: shopkey, mua key, license...'); ?>">
                                <small class="text-muted"><?= __('Từ khóa cách nhau bằng dấu phẩy'); ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê -->
                    <div class="card custom-card mt-3">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-chart-line me-2"></i><?= __('Thống kê'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><?= __('Lượt xem:'); ?></span>
                                <strong class="text-primary"><?= format_cash($blog['views']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted"><?= __('Ngày tạo:'); ?></span>
                                <small><?= date('d/m/Y H:i', strtotime($blog['created_at'])); ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted"><?= __('Cập nhật cuối:'); ?></span>
                                <small><?= date('d/m/Y H:i', strtotime($blog['updated_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-xl-4">
                    <!-- Xuất bản -->
                    <div class="card custom-card">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-upload me-2"></i><?= __('Xuất bản'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><?= __('Trạng thái:'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="blog-status" required onchange="togglePublishedDate()">
                                    <option value="draft" <?= $blog['status'] == 'draft' ? 'selected' : ''; ?>><?= __('Bản nháp'); ?></option>
                                    <option value="published" <?= $blog['status'] == 'published' ? 'selected' : ''; ?>><?= __('Xuất bản'); ?></option>
                                    <option value="scheduled" <?= $blog['status'] == 'scheduled' ? 'selected' : ''; ?>><?= __('Lên lịch xuất bản'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3" id="published-date-group" style="<?= ($blog['status'] == 'published' || $blog['status'] == 'scheduled') ? 'display: block;' : 'display: none;'; ?>">
                                <label class="form-label"><?= __('Thời gian xuất bản:'); ?></label>
                                <input type="datetime-local" class="form-control" name="published_at" id="blog-published-at"
                                    value="<?= $blog['published_at'] ? date('Y-m-d\TH:i', strtotime($blog['published_at'])) : ''; ?>">
                                <small class="text-muted"><?= __('Để trống sẽ dùng thời gian hiện tại'); ?></small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="blog-is-featured" <?= $blog['is_featured'] == 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="blog-is-featured">
                                        <i class="fa-solid fa-star text-warning me-1"></i><?= __('Bài viết nổi bật'); ?>
                                    </label>
                                </div>
                                <small class="text-muted"><?= __('Bài viết nổi bật sẽ hiển thị ưu tiên trên trang chủ'); ?></small>
                            </div>

                            <div class="d-grid gap-2 pt-3 border-top">
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save me-1"></i><?= __('Cập nhật bài viết'); ?>
                                </button>
                                <a href="<?= base_url_admin('blogs'); ?>" class="btn btn-secondary">
                                    <i class="fa-solid fa-times me-1"></i><?= __('Hủy'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Chuyên mục -->
                    <div class="card custom-card mt-3">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-folder me-2"></i><?= __('Chuyên mục'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-0">
                                <select class="form-select" name="category_id">
                                    <option value="0"><?= __('-- Chưa phân loại --'); ?></option>
                                    <?php
                                    $categories = $CMSNT->get_list_safe("SELECT * FROM `blog_categories` WHERE `status` = 1 ORDER BY `name` ASC");
                                    foreach ($categories as $cat):
                                    ?>
                                        <option value="<?= $cat['id']; ?>" <?= $blog['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Ảnh đại diện -->
                    <div class="card custom-card mt-3">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fa-solid fa-image me-2"></i><?= __('Ảnh đại diện'); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($blog['thumbnail'])): ?>
                                <div class="mb-3">
                                    <p class="text-muted mb-2"><?= __('Ảnh hiện tại:'); ?></p>
                                    <img src="<?= BASE_URL($blog['thumbnail']); ?>" alt="" class="img-fluid rounded" style="max-height: 200px;">
                                </div>
                            <?php endif; ?>
                            <div class="mb-0">
                                <input type="file" class="form-control" name="thumbnail" id="blog-thumbnail" accept="image/*">
                                <small class="text-muted d-block mt-2"><?= __('Để trống nếu không muốn thay đổi ảnh'); ?></small>
                                <div id="thumbnail-preview" class="mt-3" style="display: none;">
                                    <img id="thumbnail-preview-img" src="" alt="" class="img-fluid rounded" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    // Tự động tạo slug từ tiêu đề (chỉ khi tạo mới)
    function removeVietnameseTones(str) {
        return str.normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D');
    }

    // Toggle published date field
    function togglePublishedDate() {
        var status = document.getElementById('blog-status').value;
        var dateGroup = document.getElementById('published-date-group');

        if (status == 'published' || status == 'scheduled') {
            dateGroup.style.display = 'block';
        } else {
            dateGroup.style.display = 'none';
        }
    }

    // Preview thumbnail
    document.getElementById('blog-thumbnail').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('thumbnail-preview-img').src = e.target.result;
                document.getElementById('thumbnail-preview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Khởi tạo CKEditor cho content
    var blogContentEditor;
    if (typeof CKEDITOR !== 'undefined') {
        blogContentEditor = CKEDITOR.replace("blog_content", {
            toolbar: [{
                    name: 'document',
                    items: ['Source', '-', 'Save', 'NewPage', 'Preview', 'Print']
                },
                {
                    name: 'clipboard',
                    items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo']
                },
                {
                    name: 'editing',
                    items: ['Find', 'Replace', '-', 'SelectAll']
                },
                '/',
                {
                    name: 'basicstyles',
                    items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat']
                },
                {
                    name: 'paragraph',
                    items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
                },
                {
                    name: 'links',
                    items: ['Link', 'Unlink', 'Anchor']
                },
                {
                    name: 'insert',
                    items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'PageBreak', 'Iframe']
                },
                '/',
                {
                    name: 'styles',
                    items: ['Styles', 'Format', 'Font', 'FontSize']
                },
                {
                    name: 'colors',
                    items: ['TextColor', 'BGColor']
                },
                {
                    name: 'tools',
                    items: ['Maximize', 'ShowBlocks']
                }
            ],
            extraPlugins: 'image',
            language: 'vi',
            height: 500,
            resize_enabled: true,
            allowedContent: true,
            removeDialogTabs: 'image:advanced;image:Link',
            filebrowserUploadUrl: '<?= BASE_URL("ajaxs/admin/upload-image.php"); ?>',
            filebrowserUploadMethod: 'form'
        });
    }
</script>