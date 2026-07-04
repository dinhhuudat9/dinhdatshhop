<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . '/../../models/is_admin.php');

if ($CMSNT->site('status_demo') != 0) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('This function cannot be used because this is a demo site')
    ]);
    die($data);
}
if (!isset($_POST['action'])) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => 'The Request Not Found'
    ]);
    die($data);
}

// Kiểm tra CSRF token cho tất cả request
checkCSRFAjax();

// Forward supplier actions to dedicated handler
$supplierActions = ['createSupplier', 'importSupplierProduct', 'bulkImportSupplierProducts'];
if (in_array($_POST['action'], $supplierActions)) {
    require_once(__DIR__ . '/suppliers.php');
    exit;
}

if ($_POST['action'] == 'generated_description_by_ai') {
    // Kiểm tra quyền sử dụng tính năng
    if (checkPermission($getUser['admin'], 'edit_product') != true) {
        die(json_encode(['success' => false, 'message' => 'Bạn không có quyền sử dụng tính năng này']));
    }

    // Lấy dữ liệu từ POST
    $keyword    = isset($_POST['keyword']) ? check_string($_POST['keyword']) : '';
    $short_desc = isset($_POST['short_desc']) ? check_string($_POST['short_desc']) : '';

    // Kiểm tra tham số (loại bỏ biến $license vì không được định nghĩa)
    if (empty($keyword) || empty($short_desc)) {
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu dữ liệu tên sản phẩm và mô tả ngắn'
        ]);
        exit;
    }

    if ($CMSNT->site('chatgpt_api_key') == '') {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng cấu hình Api Key ChatGPT tại Admin -> Cài Đặt -> Kết nối'
        ]);
        exit;
    }
    // Tạo prompt cho ChatGPT sử dụng cả keyword và short_desc
    $prompt = "Hãy tạo một bài giới thiệu chi tiết về loại tài khoản hoặc sản phẩm có tên \"$keyword\". " .
        "Mô tả ngắn: \"$short_desc\". " .
        "Nội dung cần bao gồm:\n" .
        "- Các tính năng và ưu điểm nổi bật,\n" .
        "- Lợi ích khi sử dụng tài khoản/sản phẩm \"$keyword\",\n" .
        "- Một số hướng dẫn hoặc thông tin bổ ích dành cho người dùng.\n\n" .
        "Yêu cầu:\n" .
        "- Sử dụng các thẻ như <h1>, <h2>, <p>, <ul>, <li> phù hợp trong CKEDITOR để định dạng nội dung giống như bài viết chuẩn SEO.\n" .
        "    - Font chữ (font-family) và màu sắc (color) cho tiêu đề và đoạn văn,\n" .
        "    - Khoảng cách padding và margin hợp lý,\n" .
        "    - Hiệu ứng nhẹ cho tiêu đề, ví dụ như underline hoặc shadow.\n\n";

    // Thiết lập các thông số cho API ChatGPT
    $api_key     = $CMSNT->site('chatgpt_api_key');
    $model       = $CMSNT->site('chatgpt_model'); // Hoặc "gpt-4" nếu bạn có quyền truy cập
    $max_tokens  = 1000;
    $temperature = 0.7;

    $url     = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ];
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens'   => $max_tokens,
        'temperature'  => $temperature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    // Tắt xác minh SSL nếu môi trường của bạn cần
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode([
            'success' => false,
            'message' => "Curl Error: " . curl_error($ch)
        ]);
        exit;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        echo json_encode([
            'success' => false,
            'message' => "HTTP Error: $http_code => $response"
        ]);
        exit;
    }
    curl_close($ch);

    $response_data = json_decode($response, true);
    if (!$response_data) {
        echo json_encode([
            'success' => false,
            'message' => "AI đang gián đoạn, đang cố gắng thử lại sau: " . $response
        ]);
        exit;
    }

    // Lấy kết quả từ API nếu có
    if (isset($response_data['choices'][0]['message']['content'])) {
        $generatedContent = $response_data['choices'][0]['message']['content'];
    } else {
        $generatedContent = 'No response generated';
    }

    // Trả về kết quả dưới dạng JSON
    echo json_encode([
        'success'     => true,
        'description' => $generatedContent
    ]);
    exit;
}

if ($_POST['action'] == 'addProductPlan') {
    if (checkPermission($getUser['admin'], 'edit_product_plan') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $product_id = validate_int($_POST['product_id'], 1);
    $name = validate_string($_POST['name'], 255, 1);
    $duration_type = validate_string($_POST['duration_type'], 20);
    $duration_value = isset($_POST['duration_value']) ? validate_int($_POST['duration_value'], 1) : null;
    $cost_price = isset($_POST['cost_price']) ? validate_float($_POST['cost_price'], 0) : 0;
    $price = validate_float($_POST['price'], 0);
    $sale_price = isset($_POST['sale_price']) ? validate_float($_POST['sale_price'], 0) : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $status = isset($_POST['status']) ? validate_int($_POST['status'], 0, 1) : 0;

    if ($product_id === false || $name === false || $price === false) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    if ($duration_type === false || !in_array($duration_type, ['day', 'month', 'year', 'lifetime'])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Loại thời hạn không hợp lệ')
        ]));
    }

    // Kiểm tra sản phẩm tồn tại
    if (!$CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ?", [$product_id])) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Sản phẩm không tồn tại')
        ]));
    }

    // Lấy sort_order cao nhất
    $max_order = $CMSNT->get_row_safe("SELECT MAX(`sort_order`) as max_order FROM `product_plans` WHERE `product_id` = ?", [$product_id]);
    $sort_order = $max_order ? intval($max_order['max_order']) + 1 : 0;

    $is_instant = isset($_POST['is_instant']) ? validate_int($_POST['is_instant'], 0, 1) : 0;

    // Xử lý upload ảnh icon hoặc chọn từ thư viện
    $url_image = null;
    $image_path = isset($_POST['image_path']) ? trim($_POST['image_path']) : '';

    // Kiểm tra nếu có chọn ảnh từ thư viện (elFinder)
    if (!empty($image_path)) {
        // Chuyển đổi URL đầy đủ thành đường dẫn tương đối
        if (strpos($image_path, base_url()) === 0) {
            $url_image = str_replace(base_url(), '', $image_path);
            $url_image = ltrim($url_image, '/');
        } else {
            $url_image = $image_path;
        }
    } elseif (isset($_FILES['image']) && check_img('image') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 4);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploadDir = 'assets/storage/images/';
        $absoluteUploadDir = __DIR__ . '/../../' . $uploadDir;

        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($absoluteUploadDir)) {
            mkdir($absoluteUploadDir, 0755, true);
        }

        $uploads_dir = $uploadDir . 'plan_' . $rand . '.' . $ext;
        $absolute_uploads_dir = $absoluteUploadDir . 'plan_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['image']['tmp_name'];
        $addlogo = move_uploaded_file($tmp_name, $absolute_uploads_dir);
        if ($addlogo) {
            $url_image = $uploads_dir;
        }
    }

    $isInsert = $CMSNT->insert("product_plans", [
        'product_id'    => $product_id,
        'name'          => $name,
        'duration_type' => $duration_type,
        'duration_value' => $duration_value,
        'cost_price'    => $cost_price,
        'price'         => $price,
        'sale_price'   => $sale_price,
        'description'   => $description,
        'is_instant'    => $is_instant,
        'image'         => $url_image,
        'status'        => $status,
        'sort_order'    => $sort_order,
        'created_at'    => gettime(),
        'updated_at'    => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Add Product Plan (' . $name . ') for Product ID ' . $product_id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Thêm gói thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thêm gói thất bại')]));
}

if ($_POST['action'] == 'addProductStock') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $plan_id = validate_int($_POST['plan_id'], 1);
    $stock_value = isset($_POST['stock_value']) ? trim(strip_tags($_POST['stock_value'])) : '';
    $status = isset($_POST['status']) ? validate_int($_POST['status'], 0, 1) : 1;

    if ($plan_id === false || empty($stock_value)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng điền đầy đủ thông tin bắt buộc')
        ]));
    }

    // Kiểm tra gói tồn tại và là gói giao ngay
    $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ?", [$plan_id]);
    if (!$plan) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Gói sản phẩm không tồn tại')
        ]));
    }

    if (!isset($plan['is_instant']) || (int)$plan['is_instant'] != 1) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chỉ gói sản phẩm giao ngay mới có thể quản lý kho hàng')
        ]));
    }

    $isInsert = $CMSNT->insert("product_stock", [
        'plan_id'       => $plan_id,
        'stock_value'   => $stock_value,
        'status'        => $status,
        'created_at'    => gettime(),
        'updated_at'    => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => 'Add Product Stock for Plan ID ' . $plan_id
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Thêm kho hàng thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thêm kho hàng thất bại')]));
}

if ($_POST['action'] == 'importProductStock') {
    if (checkPermission($getUser['admin'], 'edit_product_stock') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $plan_id = validate_int($_POST['plan_id'], 1);
    $stock_data = isset($_POST['stock_data']) ? trim($_POST['stock_data']) : '';

    if ($plan_id === false || empty($stock_data)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng nhập dữ liệu kho hàng')
        ]));
    }

    // Kiểm tra gói tồn tại và là gói giao ngay
    $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ?", [$plan_id]);
    if (!$plan) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Gói sản phẩm không tồn tại')
        ]));
    }

    if (!isset($plan['is_instant']) || (int)$plan['is_instant'] != 1) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Chỉ gói sản phẩm giao ngay mới có thể quản lý kho hàng')
        ]));
    }

    // Tách dữ liệu theo từng dòng
    $lines = explode("\n", $stock_data);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines); // Loại bỏ dòng trống

    if (empty($lines)) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Không có dữ liệu hợp lệ')
        ]));
    }

    $success_count = 0;
    $error_count = 0;

    foreach ($lines as $line) {
        if (empty($line)) continue;

        $stock_value = strip_tags($line);
        if (empty($stock_value)) continue;

        $isInsert = $CMSNT->insert("product_stock", [
            'plan_id'       => $plan_id,
            'stock_value'   => $stock_value,
            'status'        => 1, // Mặc định là còn hàng
            'created_at'    => gettime(),
            'updated_at'    => gettime()
        ]);

        if ($isInsert) {
            $success_count++;
        } else {
            $error_count++;
        }
    }

    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => 'Import Product Stock (' . $success_count . ' items) for Plan ID ' . $plan_id
    ]);

    if ($success_count > 0) {
        die(json_encode([
            'status' => 'success',
            'msg' => __('Nhập hàng thành công! Đã thêm ') . $success_count . __(' sản phẩm vào kho hàng') . ($error_count > 0 ? ' (' . $error_count . __(' lỗi)') : '')
        ]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Nhập hàng thất bại')]));
}

// ==================== BLOG CATEGORY ====================
if ($_POST['action'] == 'addBlogCategory') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $name = validate_string($_POST['name'], 255, 1);
    $slug = validate_string($_POST['slug'], 255, 1);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $meta_title = validate_string($_POST['meta_title'] ?? '', 255);
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';
    $status = validate_int($_POST['status'], 0, 1);

    if ($name === false || empty($name)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập tên chuyên mục')]));
    }
    if ($slug === false || empty($slug)) {
        $slug = create_slug($name);
    }
    if ($status === false) {
        $status = 1;
    }

    // Kiểm tra slug trùng
    if ($CMSNT->get_row_safe("SELECT * FROM `blog_categories` WHERE `slug` = ?", [$slug])) {
        die(json_encode(['status' => 'error', 'msg' => __('Slug này đã tồn tại')]));
    }

    // Xử lý upload ảnh
    $image_url = null;
    if (check_img('image') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/blog_cat_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['image']['tmp_name'];
        if (move_uploaded_file($tmp_name, $uploads_dir)) {
            $image_url = $uploads_dir;
        }
    }

    $isInsert = $CMSNT->insert("blog_categories", [
        'name'              => $name,
        'slug'              => $slug,
        'description'       => $description,
        'image'             => $image_url,
        'meta_title'        => $meta_title ?: $name,
        'meta_description'  => $meta_description ?: $description,
        'meta_keywords'     => $meta_keywords,
        'status'            => $status,
        'created_at'        => gettime(),
        'updated_at'        => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Blog Category (" . $name . ")."
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Thêm chuyên mục thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thêm chuyên mục thất bại')]));
}

// ==================== BLOG POST ====================
if ($_POST['action'] == 'addBlog') {
    if (checkPermission($getUser['admin'], 'edit_blog') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $title = validate_string($_POST['title'], 255, 1);
    $slug = validate_string($_POST['slug'], 255, 1);
    $category_id = validate_int($_POST['category_id'], 0);
    $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $status = validate_string($_POST['status'], 20);
    $is_featured = validate_int($_POST['is_featured'], 0, 1);

    // SEO Meta
    $meta_title = validate_string($_POST['meta_title'] ?? '', 255);
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $meta_keywords = isset($_POST['meta_keywords']) ? trim($_POST['meta_keywords']) : '';

    // Published date
    $published_at = null;
    if ($status == 'published' || $status == 'scheduled') {
        $published_at_input = validate_string($_POST['published_at'] ?? '', 20);
        if ($published_at_input && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $published_at_input)) {
            $published_at = $published_at_input;
        } else {
            $published_at = gettime();
        }
    }

    if ($title === false || empty($title)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập tiêu đề bài viết')]));
    }
    if ($slug === false || empty($slug)) {
        $slug = create_slug($title);
    }
    if ($status === false || !in_array($status, ['draft', 'published', 'scheduled'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Trạng thái không hợp lệ')]));
    }
    if ($is_featured === false) {
        $is_featured = 0;
    }

    // Kiểm tra slug trùng
    if ($CMSNT->get_row_safe("SELECT * FROM `blogs` WHERE `slug` = ?", [$slug])) {
        die(json_encode(['status' => 'error', 'msg' => __('Slug này đã tồn tại')]));
    }

    // Xử lý upload ảnh thumbnail
    $thumbnail_url = null;
    if (check_img('thumbnail') == true) {
        $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
        $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $uploads_dir = 'assets/storage/images/blog_' . $rand . '.' . $ext;
        $tmp_name = $_FILES['thumbnail']['tmp_name'];
        if (move_uploaded_file($tmp_name, $uploads_dir)) {
            $thumbnail_url = $uploads_dir;
        }
    }

    $isInsert = $CMSNT->insert("blogs", [
        'category_id'       => $category_id ?: 0,
        'author_id'         => $getUser['id'],
        'title'             => $title,
        'slug'              => $slug,
        'excerpt'           => $excerpt,
        'content'           => $content,
        'thumbnail'         => $thumbnail_url,
        'meta_title'        => $meta_title ?: $title,
        'meta_description'  => $meta_description ?: $excerpt,
        'meta_keywords'     => $meta_keywords,
        'is_featured'       => $is_featured,
        'status'            => $status,
        'published_at'      => $published_at,
        'created_at'        => gettime(),
        'updated_at'        => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'       => $getUser['id'],
            'ip'            => myip(),
            'device'        => getUserAgent(),
            'createdate'    => gettime(),
            'action'        => "Add Blog Post (" . $title . ")."
        ]);
        die(json_encode(['status' => 'success', 'msg' => __('Thêm bài viết thành công!')]));
    }
    die(json_encode(['status' => 'error', 'msg' => __('Thêm bài viết thất bại')]));
}

// ==================== SLIDER ACTIONS ====================
if ($_POST['action'] == 'addSlider') {
    if (checkPermission($getUser['admin'], 'edit_sliders') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $title = validate_string($_POST['title'] ?? '', 255);
    $link = validate_string($_POST['link'] ?? '', 500);
    $sort_order = validate_int($_POST['sort_order'] ?? 0, 0, 9999);

    if ($title === false) {
        $title = '';
    }
    if ($link === false) {
        $link = null;
    }
    if ($sort_order === false) {
        $sort_order = 0;
    }

    // Kiểm tra upload ảnh
    if (!isset($_FILES['slider_image']) || check_img('slider_image') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ảnh hợp lệ')
        ]));
    }

    // Upload ảnh
    $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
    $ext = pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION);
    $uploads_dir = 'assets/storage/images/slider_' . $rand . '.' . $ext;
    $tmp_name = $_FILES['slider_image']['tmp_name'];
    $addimage = move_uploaded_file($tmp_name, $uploads_dir);

    if (!$addimage) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Upload ảnh thất bại!')
        ]));
    }

    $isInsert = $CMSNT->insert("sliders", [
        'title'      => $title,
        'image'      => $uploads_dir,
        'link'       => $link,
        'sort_order' => $sort_order,
        'status'     => 1,
        'created_at' => gettime(),
        'updated_at' => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Thêm slider mới')
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Thêm slider mới'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Thêm slider thành công!')
        ]));
    }

    die(json_encode([
        'status' => 'error',
        'msg' => __('Thêm slider thất bại!')
    ]));
}

// ==================== BANNER ACTIONS ====================
if ($_POST['action'] == 'addBanner') {
    if (checkPermission($getUser['admin'], 'edit_banners') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Bạn không có quyền sử dụng tính năng này')
        ]));
    }

    $title = validate_string($_POST['title'] ?? '', 255);
    $link = validate_string($_POST['link'] ?? '', 500);
    $position = validate_string($_POST['position'] ?? '', 50);
    $sort_order = validate_int($_POST['sort_order'] ?? 0, 0, 9999);

    if ($title === false) {
        $title = '';
    }
    if ($link === false) {
        $link = null;
    }
    if ($position === false || !in_array($position, ['below_sliders', 'sidebar_left', 'sidebar_right', 'footer', 'top', 'content'])) {
        $position = 'below_sliders';
    }
    if ($sort_order === false) {
        $sort_order = 0;
    }

    // Kiểm tra upload ảnh
    if (!isset($_FILES['banner_image']) || check_img('banner_image') != true) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Vui lòng chọn ảnh hợp lệ')
        ]));
    }

    // Upload ảnh
    $rand = random('0123456789QWERTYUIOPASDGHJKLZXCVBNM', 6);
    $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
    $uploads_dir = 'assets/storage/images/banner_' . $rand . '.' . $ext;
    $tmp_name = $_FILES['banner_image']['tmp_name'];
    $addimage = move_uploaded_file($tmp_name, __DIR__ . '/../../' . $uploads_dir);

    if (!$addimage) {
        die(json_encode([
            'status'    => 'error',
            'msg'       => __('Upload ảnh thất bại!')
        ]));
    }

    $isInsert = $CMSNT->insert("banners", [
        'title'      => $title,
        'image'      => $uploads_dir,
        'link'       => $link,
        'position'   => $position,
        'sort_order' => $sort_order,
        'status'     => 1,
        'created_at' => gettime(),
        'updated_at' => gettime()
    ]);

    if ($isInsert) {
        $CMSNT->insert("logs", [
            'user_id'    => $getUser['id'],
            'ip'         => myip(),
            'device'     => getUserAgent(),
            'createdate' => gettime(),
            'action'     => __('Thêm banner mới')
        ]);

        /** NOTE ACTION */
        $my_text = $CMSNT->site('noti_action');
        $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
        $my_text = str_replace('{username}', $getUser['username'], $my_text);
        $my_text = str_replace('{action}', __('Thêm banner mới'), $my_text);
        $my_text = str_replace('{ip}', myip(), $my_text);
        $my_text = str_replace('{time}', gettime(), $my_text);
        sendMessAdmin($my_text);

        die(json_encode([
            'status' => 'success',
            'msg' => __('Thêm banner thành công!')
        ]));
    }

    die(json_encode([
        'status' => 'error',
        'msg' => __('Thêm banner thất bại!')
    ]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
