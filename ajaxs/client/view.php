<?php

define("IN_SITE", true);
require_once(__DIR__ . "/../../libs/db.php");
require_once(__DIR__ . "/../../libs/lang.php");
require_once(__DIR__ . "/../../libs/helper.php");
require_once(__DIR__ . "/../../config.php");

// Hỗ trợ action từ cả GET và POST
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    $data = json_encode([
        'status'    => 'error',
        'msg'       => __('The Request Not Found')
    ]);
    die($data);
}



if ($action == 'CalculateCryptoReceived') {
    // Validate amount
    if (empty($_POST['amount'])) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    $amount = validate_float($_POST['amount'], 0);
    if ($amount === false || $amount <= 0) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    // Lấy tỷ giá
    $crypto_rate = floatval($CMSNT->site('crypto_rate'));
    if ($crypto_rate <= 0) {
        die(json_encode(['status' => 'error', 'received' => '0']));
    }

    // Tính toán số tiền thực nhận (bao gồm khuyến mãi)
    // Bước 1: Tính số tiền USDT sau khi cộng khuyến mãi
    $received = calculateCryptoReceivedAmount($amount, $CMSNT->site('crypto_promotions'));
    // Bước 2: Nhân với tỷ giá để ra số tiền VND
    $received = $received * $crypto_rate;

    // Format số tiền bằng hàm format_currency
    $received_formatted = format_currency($received);

    die(json_encode([
        'status' => 'success',
        'received' => $received_formatted,
        'rate' => format_currency($crypto_rate)
    ]));
}

/**
 * Lấy danh sách sản phẩm
 * Hỗ trợ: tất cả sản phẩm, theo parent_id, theo category_id
 * Hỗ trợ phân trang và lazy loading
 */
if ($action == 'getProductsByCategory') {
    // Phân trang
    $limit = isset($_POST['limit']) ? (validate_int($_POST['limit'], 1, 100) ?: 20) : 20;
    $page = isset($_POST['page']) ? (validate_int($_POST['page'], 1, 1000000) ?: 1) : 1;
    $offset = ($page - 1) * $limit;

    // Lấy user_id nếu đã đăng nhập để kiểm tra trạng thái yêu thích
    $user_id = 0;
    if (isSecureCookie('user_login') == true) {
        $user_token = validate_alphanumeric($_COOKIE['user_login']);
        if ($user_token) {
            $getUser = $CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `token` = ?", [$user_token]);
            if ($getUser) {
                $user_id = $getUser['id'];
            }
        }
    }

    // Xác định loại filter
    $filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : 'all';
    $category_id = isset($_POST['category_id']) ? validate_int($_POST['category_id'], 1) : false;
    $parent_id = isset($_POST['parent_id']) ? validate_int($_POST['parent_id'], 1) : false;

    $where_clause = "p.`status` = 1";
    $params = [];
    $category_info = [
        'id' => 0,
        'name' => __('Tất cả sản phẩm'),
        'slug' => ''
    ];

    if ($filter_type === 'category' && $category_id) {
        // Lọc theo chuyên mục con cụ thể
        $category = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `status` = 'show'", [$category_id]);
        if (!$category) {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Chuyên mục không tồn tại hoặc đã bị ẩn')
            ]));
        }
        $where_clause .= " AND FIND_IN_SET(?, p.`category_ids`)";
        $params[] = $category_id;
        $category_info = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug']
        ];
    } elseif ($filter_type === 'parent' && $parent_id) {
        // Lọc theo chuyên mục cha (lấy tất cả sản phẩm thuộc các category con)
        $parent = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `parent_id` = 0 AND `status` = 'show'", [$parent_id]);
        if (!$parent) {
            die(json_encode([
                'status' => 'error',
                'msg' => __('Chuyên mục cha không tồn tại hoặc đã bị ẩn')
            ]));
        }
        // Lấy danh sách category con
        $child_categories = $CMSNT->get_list_safe("SELECT `id` FROM `categories` WHERE `parent_id` = ? AND `status` = 'show'", [$parent_id]);
        if (count($child_categories) > 0) {
            $child_ids = array_column($child_categories, 'id');
            // Build dynamic FIND_IN_SET conditions for multi-category
            $find_conditions = [];
            foreach ($child_ids as $cid) {
                $find_conditions[] = "FIND_IN_SET(?, p.`category_ids`)";
                $params[] = $cid;
            }
            $where_clause .= " AND (" . implode(' OR ', $find_conditions) . ")";
        } else {
            // Không có category con nào
            $where_clause .= " AND 1 = 0";
        }
        $category_info = [
            'id' => $parent['id'],
            'name' => $parent['name'],
            'slug' => $parent['slug']
        ];
    }
    // Nếu filter_type === 'all' thì không thêm điều kiện, lấy tất cả sản phẩm

    // Đếm tổng số sản phẩm
    $count_sql = "SELECT COUNT(*) as total FROM `products` p WHERE $where_clause";
    $count_query = $CMSNT->get_row_safe($count_sql, $params);
    $total = (int)($count_query['total'] ?? 0);
    $total_pages = $total > 0 ? ceil($total / $limit) : 0;

    // Lấy danh sách sản phẩm với giá min/max từ các gói
    $products_sql = "SELECT p.*, 
            (SELECT MIN(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as min_final_price,
            (SELECT MAX(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as max_final_price,
            (SELECT MIN(pp.price) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as min_original_price,
            (SELECT MAX(pp.price) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as max_original_price,
            (SELECT COUNT(*) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as plan_count,
            (SELECT COUNT(*) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1 AND pp.sale_price > 0 AND pp.sale_price < pp.price) as sale_plan_count,
            (SELECT MAX(ROUND(((pp.price - pp.sale_price) / pp.price) * 100)) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1 AND pp.sale_price > 0 AND pp.sale_price < pp.price) as max_discount_percent,
            (SELECT COUNT(*) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1 AND pp.is_instant = 1) as instant_plan_count
         FROM `products` p 
         WHERE $where_clause 
         ORDER BY p.`sort_order` ASC, p.`id` DESC 
         LIMIT ?, ?";
    $products_params = array_merge($params, [$offset, $limit]);
    $products = $CMSNT->get_list_safe($products_sql, $products_params);

    // Format dữ liệu sản phẩm
    $formatted_products = [];
    foreach ($products as $product) {
        $price_display = '';
        $price_range = '';
        $has_sale = false;
        $original_price = '';
        $discount_percent = 0;

        $min_final = $product['min_final_price'] ? (float)$product['min_final_price'] : 0;
        $max_final = $product['max_final_price'] ? (float)$product['max_final_price'] : 0;
        $min_original = $product['min_original_price'] ? (float)$product['min_original_price'] : 0;
        $max_original = $product['max_original_price'] ? (float)$product['max_original_price'] : 0;
        $sale_count = (int)$product['sale_plan_count'];

        if ($min_final > 0) {
            // Hiển thị giá dạng "XX ~ YY" nếu có nhiều mức giá
            if ($min_final != $max_final && $product['plan_count'] > 1) {
                $price_display = format_currency($min_final);
                $price_range = format_currency($min_final) . ' ~ ' . format_currency($max_final);
            } else {
                $price_display = format_currency($min_final);
                $price_range = '';
            }

            // Kiểm tra có giảm giá không
            if ($sale_count > 0) {
                $has_sale = true;
                $discount_percent = (int)$product['max_discount_percent'];
                // Hiển thị giá gốc cao nhất nếu có giảm giá
                if ($max_original > $max_final) {
                    $original_price = format_currency($max_original);
                }
            }
        } else {
            $price_display = __('Liên hệ');
        }

        // Kiểm tra có gói giao ngay không
        $is_instant = (int)($product['instant_plan_count'] ?? 0) > 0;

        // Kiểm tra sản phẩm đã được yêu thích chưa
        $is_favorited = false;
        if ($user_id > 0) {
            $is_favorited = $CMSNT->num_rows_safe(
                "SELECT 1 FROM `product_favorites` WHERE `user_id` = ? AND `product_id` = ?",
                [$user_id, $product['id']]
            ) > 0;
        }

        $formatted_products[] = [
            'id' => $product['id'],
            'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
            'slug' => $product['slug'],
            'image' => !empty($product['image']) ? BASE_URL($product['image']) : '',
            'description' => mb_substr(strip_tags(html_entity_decode($product['description'] ?? '', ENT_QUOTES, 'UTF-8')), 0, 100),
            'price_display' => $price_display,
            'price_range' => $price_range,
            'original_price' => $original_price,
            'has_sale' => $has_sale,
            'discount_percent' => $discount_percent,
            'plan_count' => (int)$product['plan_count'],
            'is_instant' => $is_instant, // true nếu có gói giao ngay, false nếu order
            'is_favorited' => $is_favorited, // trạng thái yêu thích
            'rating' => isset($product['rating']) ? (float)$product['rating'] : 0,
            'rating_count' => isset($product['rating_count']) ? (int)$product['rating_count'] : 0,
            'sold' => isset($product['sold']) ? (int)$product['sold'] : 0,
            'url' => base_url('product/' . $product['slug'])
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => [
            'products' => $formatted_products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_products' => $total,
                'per_page' => $limit,
                'has_more' => $page < $total_pages
            ],
            'category' => $category_info
        ]
    ]));
}

/**
 * Lấy danh sách sản phẩm với bộ lọc nâng cao
 * Hỗ trợ: lọc theo giá, sắp xếp
 */
if ($action == 'getProductsFiltered') {
    $limit = isset($_POST['limit']) ? (validate_int($_POST['limit'], 1, 100) ?: 12) : 12;
    $page = isset($_POST['page']) ? (validate_int($_POST['page'], 1, 1000000) ?: 1) : 1;
    $offset = ($page - 1) * $limit;

    // Lấy user_id nếu đã đăng nhập để kiểm tra trạng thái yêu thích
    $user_id = 0;
    if (isSecureCookie('user_login') == true) {
        $user_token = validate_alphanumeric($_COOKIE['user_login']);
        if ($user_token) {
            $getUser = $CMSNT->get_row_safe("SELECT `id` FROM `users` WHERE `token` = ?", [$user_token]);
            if ($getUser) {
                $user_id = $getUser['id'];
            }
        }
    }

    $filter_type = isset($_POST['filter_type']) ? $_POST['filter_type'] : 'all';
    $category_id = isset($_POST['category_id']) ? validate_int($_POST['category_id'], 1) : false;
    $parent_id = isset($_POST['parent_id']) ? validate_int($_POST['parent_id'], 1) : false;
    $price_min = isset($_POST['price_min']) ? preg_replace('/[^0-9]/', '', $_POST['price_min']) : '';
    $price_max = isset($_POST['price_max']) ? preg_replace('/[^0-9]/', '', $_POST['price_max']) : '';
    $sort = isset($_POST['sort']) ? check_string($_POST['sort']) : 'default';
    $keyword = isset($_POST['keyword']) ? trim(check_string($_POST['keyword'])) : '';

    $where_clause = "p.`status` = 1";
    $params = [];
    $category_info = [
        'id' => 0,
        'name' => __('Tất cả sản phẩm'),
        'slug' => ''
    ];

    // Tìm kiếm theo keyword
    if (!empty($keyword)) {
        $where_clause .= " AND (p.`name` LIKE ? OR p.`description` LIKE ?)";
        $params[] = '%' . $keyword . '%';
        $params[] = '%' . $keyword . '%';
        $category_info['name'] = __('Kết quả tìm kiếm: ') . $keyword;
    }

    // Filter by category
    if ($filter_type === 'category' && $category_id) {
        $category = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `status` = 'show'", [$category_id]);
        if (!$category) {
            die(json_encode(['status' => 'error', 'msg' => __('Chuyên mục không tồn tại hoặc đã bị ẩn')]));
        }
        $where_clause .= " AND FIND_IN_SET(?, p.`category_ids`)";
        $params[] = $category_id;
        $category_info = ['id' => $category['id'], 'name' => $category['name'], 'slug' => $category['slug']];
    } elseif ($filter_type === 'parent' && $parent_id) {
        $parent = $CMSNT->get_row_safe("SELECT * FROM `categories` WHERE `id` = ? AND `parent_id` = 0 AND `status` = 'show'", [$parent_id]);
        if (!$parent) {
            die(json_encode(['status' => 'error', 'msg' => __('Chuyên mục cha không tồn tại hoặc đã bị ẩn')]));
        }
        $child_categories = $CMSNT->get_list_safe("SELECT `id` FROM `categories` WHERE `parent_id` = ? AND `status` = 'show'", [$parent_id]);
        if (count($child_categories) > 0) {
            $child_ids = array_column($child_categories, 'id');
            // Build dynamic FIND_IN_SET conditions for multi-category
            $find_conditions = [];
            foreach ($child_ids as $cid) {
                $find_conditions[] = "FIND_IN_SET(?, p.`category_ids`)";
                $params[] = $cid;
            }
            $where_clause .= " AND (" . implode(' OR ', $find_conditions) . ")";
        } else {
            $where_clause .= " AND 1 = 0";
        }
        $category_info = ['id' => $parent['id'], 'name' => $parent['name'], 'slug' => $parent['slug']];
    }

    // Filter by price range - using subquery for min final price
    $price_subquery = "(SELECT MIN(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1)";

    if ($price_min !== '' && is_numeric($price_min)) {
        $where_clause .= " AND $price_subquery >= ?";
        $params[] = (int)$price_min;
    }
    if ($price_max !== '' && is_numeric($price_max)) {
        $where_clause .= " AND $price_subquery <= ?";
        $params[] = (int)$price_max;
    }

    // Determine sort order
    $order_by = "p.`sort_order` ASC, p.`id` DESC";
    switch ($sort) {
        case 'price_asc':
            $order_by = "$price_subquery ASC, p.`sort_order` ASC";
            break;
        case 'price_desc':
            $order_by = "$price_subquery DESC, p.`sort_order` ASC";
            break;
        case 'newest':
            $order_by = "p.`id` DESC";
            break;
        case 'name_asc':
            $order_by = "p.`name` ASC";
            break;
        case 'name_desc':
            $order_by = "p.`name` DESC";
            break;
    }

    // Count total
    $count_sql = "SELECT COUNT(*) as total FROM `products` p WHERE $where_clause";
    $count_query = $CMSNT->get_row_safe($count_sql, $params);
    $total = (int)($count_query['total'] ?? 0);
    $total_pages = $total > 0 ? ceil($total / $limit) : 0;

    // Get products
    $products_sql = "SELECT p.*, 
            (SELECT MIN(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as min_final_price,
            (SELECT MAX(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as max_final_price,
            (SELECT MIN(pp.price) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as min_original_price,
            (SELECT MAX(pp.price) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as max_original_price,
            (SELECT COUNT(*) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as plan_count,
            (SELECT COUNT(*) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1 AND pp.sale_price > 0 AND pp.sale_price < pp.price) as sale_plan_count,
            (SELECT MAX(ROUND(((pp.price - pp.sale_price) / pp.price) * 100)) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1 AND pp.sale_price > 0 AND pp.sale_price < pp.price) as max_discount_percent,
            (SELECT COUNT(*) FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1 AND pp.is_instant = 1) as instant_plan_count
         FROM `products` p 
         WHERE $where_clause 
         ORDER BY $order_by 
         LIMIT ?, ?";
    $products_params = array_merge($params, [$offset, $limit]);
    $products = $CMSNT->get_list_safe($products_sql, $products_params);

    // Format products
    $formatted_products = [];
    foreach ($products as $product) {
        $price_display = '';
        $price_range = '';
        $has_sale = false;
        $original_price = '';
        $discount_percent = 0;

        $min_final = $product['min_final_price'] ? (float)$product['min_final_price'] : 0;
        $max_final = $product['max_final_price'] ? (float)$product['max_final_price'] : 0;
        $min_original = $product['min_original_price'] ? (float)$product['min_original_price'] : 0;
        $max_original = $product['max_original_price'] ? (float)$product['max_original_price'] : 0;
        $sale_count = (int)$product['sale_plan_count'];

        if ($min_final > 0) {
            if ($min_final != $max_final && $product['plan_count'] > 1) {
                $price_display = format_currency($min_final);
                $price_range = format_currency($min_final) . ' ~ ' . format_currency($max_final);
            } else {
                $price_display = format_currency($min_final);
                $price_range = '';
            }

            if ($sale_count > 0) {
                $has_sale = true;
                $discount_percent = (int)$product['max_discount_percent'];
                if ($max_original > $max_final) {
                    $original_price = format_currency($max_original);
                }
            }
        } else {
            $price_display = __('Liên hệ');
        }

        $is_instant = (int)($product['instant_plan_count'] ?? 0) > 0;

        // Kiểm tra sản phẩm đã được yêu thích chưa
        $is_favorited = false;
        if ($user_id > 0) {
            $is_favorited = $CMSNT->num_rows_safe(
                "SELECT 1 FROM `product_favorites` WHERE `user_id` = ? AND `product_id` = ?",
                [$user_id, $product['id']]
            ) > 0;
        }

        $formatted_products[] = [
            'id' => $product['id'],
            'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
            'slug' => $product['slug'],
            'image' => !empty($product['image']) ? BASE_URL($product['image']) : '',
            'description' => mb_substr(strip_tags(html_entity_decode($product['description'] ?? '', ENT_QUOTES, 'UTF-8')), 0, 100),
            'price_display' => $price_display,
            'price_range' => $price_range,
            'original_price' => $original_price,
            'has_sale' => $has_sale,
            'discount_percent' => $discount_percent,
            'plan_count' => (int)$product['plan_count'],
            'is_instant' => $is_instant,
            'is_favorited' => $is_favorited, // trạng thái yêu thích
            'rating' => isset($product['rating']) ? (float)$product['rating'] : 0,
            'rating_count' => isset($product['rating_count']) ? (int)$product['rating_count'] : 0,
            'sold' => isset($product['sold']) ? (int)$product['sold'] : 0,
            'url' => base_url('product/' . $product['slug'])
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => [
            'products' => $formatted_products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_products' => $total,
                'per_page' => $limit,
                'has_more' => $page < $total_pages
            ],
            'category' => $category_info
        ]
    ]));
}

/**
 * Autocomplete tìm kiếm sản phẩm
 */
if ($action == 'searchAutocomplete') {
    $keyword = isset($_POST['keyword']) ? trim(check_string($_POST['keyword'])) : '';
    $limit = 8; // Số gợi ý tối đa

    if (empty($keyword) || mb_strlen($keyword) < 2) {
        die(json_encode(['status' => 'success', 'data' => []]));
    }

    // Tìm sản phẩm theo tên
    $products = $CMSNT->get_list_safe(
        "SELECT p.id, p.name, p.slug, p.image,
            (SELECT MIN(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) 
             FROM `product_plans` pp WHERE pp.product_id = p.id AND pp.status = 1) as min_price
         FROM `products` p 
         WHERE p.`status` = 1 AND p.`name` LIKE ?
         ORDER BY p.`sort_order` ASC, p.`id` DESC
         LIMIT ?",
        ['%' . $keyword . '%', $limit]
    );

    $results = [];
    foreach ($products as $product) {
        $results[] = [
            'id' => $product['id'],
            'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
            'slug' => $product['slug'],
            'image' => !empty($product['image']) ? BASE_URL($product['image']) : '',
            'price' => $product['min_price'] ? format_currency($product['min_price']) : __('Liên hệ'),
            'url' => base_url('product/' . $product['slug'])
        ];
    }

    die(json_encode(['status' => 'success', 'data' => $results]));
}

/**
 * Lấy danh sách trường của gói sản phẩm
 */
if ($action == 'getPlanFields') {
    $plan_id = isset($_POST['plan_id']) ? validate_int($_POST['plan_id'], 1) : 0;

    if (!$plan_id) {
        die(json_encode(['status' => 'error', 'msg' => __('Plan ID không hợp lệ')]));
    }

    // Kiểm tra plan có tồn tại và đang active không
    $plan = $CMSNT->get_row_safe("SELECT * FROM `product_plans` WHERE `id` = ? AND `status` = 1", [$plan_id]);
    if (!$plan) {
        die(json_encode(['status' => 'error', 'msg' => __('Gói sản phẩm không tồn tại')]));
    }

    // Lấy danh sách trường của gói
    $fields = $CMSNT->get_list_safe(
        "SELECT `id`, `field_key`, `label`, `type`, `placeholder`, `is_required`, `sort_order` 
         FROM `product_fields` 
         WHERE `plan_id` = ? 
         ORDER BY `sort_order` ASC, `id` ASC",
        [$plan_id]
    );

    $results = [];
    foreach ($fields as $field) {
        $results[] = [
            'id' => (int)$field['id'],
            'field_key' => $field['field_key'],
            'label' => html_entity_decode($field['label'], ENT_QUOTES, 'UTF-8'),
            'type' => $field['type'],
            'placeholder' => html_entity_decode($field['placeholder'] ?? '', ENT_QUOTES, 'UTF-8'),
            'is_required' => (int)$field['is_required']
        ];
    }

    die(json_encode(['status' => 'success', 'data' => $results]));
}

/**
 * Lấy giá real-time cho một hoặc nhiều plan (bao gồm Flash Sale)
 * @param plan_ids: mảng plan IDs hoặc single plan_id
 * @param quantity: số lượng (mặc định = 1)
 */
if ($action == 'getPlanPricing') {
    // Get plan IDs - có thể là single ID hoặc array
    $plan_ids = [];
    if (isset($_POST['plan_ids']) && is_array($_POST['plan_ids'])) {
        foreach ($_POST['plan_ids'] as $pid) {
            $id = validate_int($pid, 1);
            if ($id) $plan_ids[] = $id;
        }
    } elseif (isset($_POST['plan_id'])) {
        $id = validate_int($_POST['plan_id'], 1);
        if ($id) $plan_ids[] = $id;
    }

    if (empty($plan_ids)) {
        die(json_encode(['status' => 'error', 'msg' => __('Plan ID không hợp lệ')]));
    }

    $quantity = isset($_POST['quantity']) ? validate_int($_POST['quantity'], 1, 1000) : 1;
    if (!$quantity) $quantity = 1;

    // Lấy user_id và discount nếu đã đăng nhập
    $user_id = 0;
    $user_discount_percent = 0;
    if (isSecureCookie('user_login') == true) {
        $user_token = validate_alphanumeric($_COOKIE['user_login']);
        if ($user_token) {
            $getUser = $CMSNT->get_row_safe("SELECT `id`, `discount` FROM `users` WHERE `token` = ?", [$user_token]);
            if ($getUser) {
                $user_id = $getUser['id'];
                $user_discount_percent = (float)($getUser['discount'] ?? 0);
            }
        }
    }

    // Lấy thông tin plans
    $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));
    $plans = $CMSNT->get_list_safe(
        "SELECT pp.*, p.id as product_id, p.name as product_name 
         FROM `product_plans` pp 
         LEFT JOIN `products` p ON pp.product_id = p.id
         WHERE pp.`id` IN ($placeholders) AND pp.`status` = 1",
        $plan_ids
    );

    if (empty($plans)) {
        die(json_encode(['status' => 'error', 'msg' => __('Không tìm thấy gói sản phẩm')]));
    }

    // Load FlashSaleHandler
    require_once(__DIR__ . '/../../libs/database/flashsale.php');
    $FlashSaleHandler = new FlashSaleHandler();

    // Lấy Flash Sales active cho tất cả plans
    $product_ids = array_unique(array_column($plans, 'product_id'));
    $flash_sales = $FlashSaleHandler->getActiveFlashSalesForPlans($plan_ids, $product_ids);

    // Lấy stock counts
    $stock_query = "SELECT `plan_id`, COUNT(*) as stock_count FROM `product_stock` WHERE `plan_id` IN ($placeholders) AND `status` = 1 GROUP BY `plan_id`";
    $stock_result = $CMSNT->get_list_safe($stock_query, $plan_ids);
    $stock_counts = [];
    foreach ($stock_result as $row) {
        $stock_counts[$row['plan_id']] = (int)$row['stock_count'];
    }

    // Build response
    $pricing_data = [];
    foreach ($plans as $plan) {
        $plan_id = (int)$plan['id'];
        $original_price = (float)$plan['price'];
        $sale_price = (float)($plan['sale_price'] ?? 0);
        $has_sale = ($sale_price > 0 && $sale_price < $original_price);

        // Tính user discount amount (từ giá gốc)
        $user_discount_amount = 0;
        $price_after_user_discount = $original_price;
        if ($user_discount_percent > 0) {
            $user_discount_amount = $original_price * ($user_discount_percent / 100);
            $price_after_user_discount = $original_price - $user_discount_amount;
        }

        // Kiểm tra Flash Sale
        $flash_sale = isset($flash_sales[$plan_id]) ? $flash_sales[$plan_id] : null;
        $has_flash_sale = !empty($flash_sale);
        $flash_price = 0;
        $flash_sale_info = null;

        if ($has_flash_sale) {
            $flash_price = $FlashSaleHandler->calculateFlashSalePrice($plan, $flash_sale);

            // Kiểm tra user có thể mua không
            $can_purchase = ['can_buy' => true, 'message' => '', 'remaining' => PHP_INT_MAX];
            if ($user_id > 0) {
                $can_purchase = $FlashSaleHandler->canUserPurchase($user_id, $flash_sale['id'], $quantity);
            }

            $flash_sale_info = [
                'id' => (int)$flash_sale['id'],
                'name' => $flash_sale['name'],
                'end_time' => $flash_sale['end_time'],
                'end_timestamp' => strtotime($flash_sale['end_time']),
                'quantity_limit' => (int)$flash_sale['quantity_limit'],
                'quantity_sold' => (int)$flash_sale['quantity_sold'],
                'quantity_remaining' => $flash_sale['quantity_limit'] > 0 ? max(0, $flash_sale['quantity_limit'] - $flash_sale['quantity_sold']) : null,
                'per_user_limit' => (int)$flash_sale['per_user_limit'],
                'can_purchase' => $can_purchase['can_buy'],
                'purchase_message' => $can_purchase['message'],
                'user_remaining' => $can_purchase['remaining']
            ];
        }

        // Tính giá cuối cùng (User Discount áp dụng trước, sau đó Flash Sale/Sale)
        // Theo logic cart.js: user discount được tính từ original_price trước
        // Flash Sale/Sale được tính từ price_after_user_discount
        $final_price = $price_after_user_discount; // Mặc định là giá sau user discount
        $flash_discount_amount = 0;
        $sale_discount_amount = 0;

        if ($has_flash_sale && $flash_price > 0 && $flash_price < $price_after_user_discount) {
            // Flash Sale: tính từ giá SAU user discount
            $final_price = $flash_price;
            $flash_discount_amount = $price_after_user_discount - $flash_price;
            // KHÔNG reset user_discount vì cả hai được hiển thị
        } elseif ($has_sale && $sale_price > 0 && $sale_price < $price_after_user_discount) {
            // Sale thường: tính từ giá SAU user discount
            $final_price = $sale_price;
            $sale_discount_amount = $price_after_user_discount - $sale_price;
        }

        // Tính discount percent tổng
        $discount_percent = 0;
        if ($final_price < $original_price) {
            $discount_percent = round((($original_price - $final_price) / $original_price) * 100);
        }

        // Stock cho gói này
        $is_api_plan = !empty($plan['supplier_id']) && !empty($plan['api_id']);
        $stock_count = $is_api_plan
            ? (isset($plan['api_stock']) ? (int)$plan['api_stock'] : 0)
            : ($stock_counts[$plan_id] ?? 0);

        $pricing_data[$plan_id] = [
            'plan_id' => $plan_id,
            'plan_name' => html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8'),
            'product_id' => (int)$plan['product_id'],
            'product_name' => html_entity_decode($plan['product_name'] ?? '', ENT_QUOTES, 'UTF-8'),

            // Giá gốc
            'original_price' => $original_price,
            'original_price_formatted' => format_currency($original_price),

            // User discount (ưu đãi thành viên)
            'user_discount_percent' => $user_discount_percent,
            'user_discount_amount' => $user_discount_amount,
            'user_discount_amount_formatted' => $user_discount_amount > 0 ? format_currency($user_discount_amount) : null,
            'price_after_user_discount' => $price_after_user_discount,

            // Giá sale thường
            'sale_price' => $sale_price,
            'sale_price_formatted' => $sale_price > 0 ? format_currency($sale_price) : null,
            'has_sale' => $has_sale,
            'sale_discount_amount' => $sale_discount_amount,
            'sale_discount_amount_formatted' => $sale_discount_amount > 0 ? format_currency($sale_discount_amount) : null,

            // Flash Sale
            'has_flash_sale' => $has_flash_sale,
            'flash_price' => $flash_price,
            'flash_price_formatted' => $flash_price > 0 ? format_currency($flash_price) : null,
            'flash_sale' => $flash_sale_info,
            'flash_discount_amount' => $flash_discount_amount,
            'flash_discount_amount_formatted' => $flash_discount_amount > 0 ? format_currency($flash_discount_amount) : null,

            // Giá cuối cùng
            'final_price' => $final_price,
            'final_price_formatted' => format_currency($final_price),
            'discount_percent' => $discount_percent,

            // Tính theo quantity
            'quantity' => $quantity,
            'subtotal' => $original_price * $quantity,
            'subtotal_formatted' => format_currency($original_price * $quantity),
            'discount_amount' => ($original_price - $final_price) * $quantity,
            'discount_amount_formatted' => format_currency(($original_price - $final_price) * $quantity),
            'total' => $final_price * $quantity,
            'total_formatted' => format_currency($final_price * $quantity),

            // Stock
            'is_instant' => (int)($plan['is_instant'] ?? 0) || $is_api_plan,
            'stock_count' => $stock_count,
            'in_stock' => ($plan['is_instant'] ?? 0) == 0 || $stock_count > 0
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => $pricing_data,
        'timestamp' => time()
    ]));
}

/**
 * Lấy thông tin chi tiết sản phẩm
 */
if ($action == 'getProductDetail') {
    $product_slug = isset($_POST['slug']) ? check_string($_POST['slug']) : '';

    if (empty($product_slug)) {
        die(json_encode(['status' => 'error', 'msg' => __('Slug sản phẩm không hợp lệ')]));
    }

    // Lấy thông tin sản phẩm
    if (is_numeric($product_slug)) {
        $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `id` = ? AND `status` = 1", [$product_slug]);
    } else {
        $product = $CMSNT->get_row_safe("SELECT * FROM `products` WHERE `slug` = ? AND `status` = 1", [$product_slug]);
    }

    if (!$product) {
        die(json_encode(['status' => 'error', 'msg' => __('Sản phẩm không tồn tại')]));
    }

    // Lấy danh sách gói
    $plans = $CMSNT->get_list_safe(
        "SELECT * FROM `product_plans` WHERE `product_id` = ? AND `status` = 1 ORDER BY `sort_order` ASC, `id` ASC",
        [$product['id']]
    );

    // Lấy số lượng kho cho mỗi gói
    $plan_stocks = [];
    if (count($plans) > 0) {
        $plan_ids = array_column($plans, 'id');
        $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));
        $stock_query = "SELECT `plan_id`, COUNT(*) as stock_count FROM `product_stock` WHERE `plan_id` IN ($placeholders) AND `status` = 1 GROUP BY `plan_id`";
        $stock_result = $CMSNT->get_list_safe($stock_query, $plan_ids);

        foreach ($stock_result as $row) {
            $plan_stocks[$row['plan_id']] = (int)$row['stock_count'];
        }
    }

    // Format plans
    $formatted_plans = [];
    foreach ($plans as $plan) {
        $formatted_plans[] = [
            'id' => (int)$plan['id'],
            'name' => html_entity_decode($plan['name'], ENT_QUOTES, 'UTF-8'),
            'price' => (float)$plan['price'],
            'sale_price' => (float)($plan['sale_price'] ?? 0),
            'is_instant' => (int)($plan['is_instant'] ?? 0),
            'stock_count' => $plan_stocks[$plan['id']] ?? 0,
            'image' => $plan['image'] ?? ''
        ];
    }

    die(json_encode([
        'status' => 'success',
        'data' => [
            'product' => [
                'id' => (int)$product['id'],
                'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
                'slug' => $product['slug'],
                'description' => $product['description'],
                'image' => $product['image'] ?? ''
            ],
            'plans' => $formatted_plans
        ]
    ]));
}

/**
 * Áp dụng mã giảm giá vào đơn hàng
 * Validate coupon và tính toán discount
 */
if ($action == 'applyCouponToOrder') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die(json_encode(['status' => 'error', 'msg' => __('Token không hợp lệ')]));
    }

    // Get parameters
    $coupon_code = isset($_POST['coupon_code']) ? trim(check_string($_POST['coupon_code'])) : '';
    $product_id = isset($_POST['product_id']) ? validate_int($_POST['product_id'], 1) : 0;
    $plan_id = isset($_POST['plan_id']) ? validate_int($_POST['plan_id'], 1) : 0;
    $quantity = isset($_POST['quantity']) ? validate_int($_POST['quantity'], 1, 100) : 1;
    $order_total = isset($_POST['order_total']) ? validate_float($_POST['order_total'], 0) : 0;

    // Validate inputs
    if (empty($coupon_code)) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng nhập mã giảm giá')]));
    }

    if (!$product_id || !$plan_id) {
        die(json_encode(['status' => 'error', 'msg' => __('Thông tin sản phẩm không hợp lệ')]));
    }

    if ($order_total <= 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Giá trị đơn hàng không hợp lệ')]));
    }

    // Check if user is logged in
    $user_id = 0;
    if (isSecureCookie('user_login') == true) {
        $user_token = validate_alphanumeric($_COOKIE['user_login']);
        if ($user_token) {
            $getUser = $CMSNT->get_row_safe("SELECT * FROM `users` WHERE `token` = ?", [$user_token]);
            if ($getUser) {
                $user_id = $getUser['id'];
            }
        }
    }

    if ($user_id == 0) {
        die(json_encode(['status' => 'error', 'msg' => __('Vui lòng đăng nhập để sử dụng mã giảm giá')]));
    }

    // Include CouponHandler
    require_once(__DIR__ . '/../../libs/database/coupon.php');
    $CouponHandler = new CouponHandler();

    // Apply coupon
    $result = $CouponHandler->applyCoupon(
        $coupon_code,
        $user_id,
        $order_total,
        $product_id,
        $plan_id
    );

    if (!$result['success']) {
        die(json_encode(['status' => 'error', 'msg' => $result['message']]));
    }

    // Return success response
    die(json_encode([
        'status' => 'success',
        'msg' => __('Áp dụng mã giảm giá thành công'),
        'coupon' => [
            'id' => $result['coupon']['id'],
            'code' => $result['coupon']['code'],
            'type' => $result['coupon']['type'],
            'value' => (float)$result['coupon']['value'],
            'max_discount_amount' => (float)$result['coupon']['max_discount_amount']
        ],
        'discount_amount' => $result['discount_amount'],
        'final_amount' => $result['final_amount'],
        'original_amount' => $order_total
    ]));
}

/**
 * Lấy sản phẩm đã xem gần đây (từ localStorage)
 * Input: JSON body với ids array
 */
if ($action == 'recently_viewed_products') {
    // Đọc JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

    // Validate và giới hạn
    $validated_ids = [];
    foreach ($ids as $id) {
        $valid_id = validate_int($id, 1);
        if ($valid_id && !in_array($valid_id, $validated_ids)) {
            $validated_ids[] = $valid_id;
        }
        if (count($validated_ids) >= 10) break;
    }

    if (empty($validated_ids)) {
        die(json_encode(['success' => false, 'products' => []]));
    }

    // Lấy thông tin sản phẩm
    $placeholders = implode(',', array_fill(0, count($validated_ids), '?'));
    $products = $CMSNT->get_list_safe(
        "SELECT p.*, 
                (SELECT MIN(CASE WHEN pp.sale_price > 0 AND pp.sale_price < pp.price THEN pp.sale_price ELSE pp.price END) 
                 FROM product_plans pp WHERE pp.product_id = p.id AND pp.status = 1) as min_price,
                (SELECT MIN(pp.price) FROM product_plans pp WHERE pp.product_id = p.id AND pp.status = 1) as original_price,
                (SELECT AVG(pr.rating) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.status = 1) as avg_rating,
                (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.status = 1) as rating_count,
                (SELECT COUNT(*) FROM product_orders po WHERE po.product_id = p.id AND po.status = 'completed') as sold_count,
                (SELECT COUNT(*) FROM product_stock ps 
                 JOIN product_plans pp ON ps.plan_id = pp.id 
                 WHERE pp.product_id = p.id AND ps.status = 1) as instant_stock
         FROM products p 
         WHERE p.id IN ($placeholders) AND p.status = 1",
        $validated_ids
    );

    // Index by ID để sắp xếp theo thứ tự gốc
    $products_by_id = [];
    foreach ($products as $product) {
        $products_by_id[$product['id']] = $product;
    }

    // Build kết quả theo thứ tự gốc
    $result = [];
    foreach ($validated_ids as $id) {
        if (isset($products_by_id[$id])) {
            $p = $products_by_id[$id];
            $result[] = [
                'id' => (int)$p['id'],
                'name' => html_entity_decode($p['name'], ENT_QUOTES, 'UTF-8'),
                'slug' => $p['slug'],
                'image' => !empty($p['image']) ? BASE_URL($p['image']) : '',
                'url' => base_url('product/' . $p['slug']),
                'price' => (float)($p['min_price'] ?? 0),
                'price_formatted' => format_currency($p['min_price'] ?? 0),
                'original_price' => (float)($p['original_price'] ?? 0),
                'original_price_formatted' => format_currency($p['original_price'] ?? 0),
                'is_instant' => ((int)($p['instant_stock'] ?? 0)) > 0,
                'rating' => round((float)($p['avg_rating'] ?? 0), 1),
                'rating_count' => (int)($p['rating_count'] ?? 0),
                'sold' => (int)($p['sold_count'] ?? 0)
            ];
        }
    }

    die(json_encode(['success' => true, 'products' => $result]));
}

die(json_encode([
    'status'    => 'error',
    'msg'       => __('Invalid data')
]));
