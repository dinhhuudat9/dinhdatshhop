<?php

define("IN_SITE", true);
require_once(__DIR__."/../../libs/db.php");
require_once(__DIR__."/../../libs/lang.php");
require_once(__DIR__."/../../libs/helper.php");
require_once(__DIR__."/../../config.php");

// Lấy categories từ cache
$all_categories = get_categories_not_parent_cached();

// Tạo HTML cho menu dropdown (nav.php)
$menu_html = '';
$parent_categories = get_categories_parent_cached();

if (!empty($parent_categories)) {
    $menu_html .= '<div class="row">';
    
    foreach($parent_categories as $category) {
        $child_categories = get_categories_by_parent_cached($category['id']);
        
        // Chỉ hiển thị category có child
        if (empty($child_categories)) continue;
        
        $menu_html .= '<div class="megamenu-column">';
        $menu_html .= '<div class="megamenu-wrap">';
        
        // Title với icon
        $menu_html .= '<h5 class="megamenu-title">';
        if (!empty($category['icon']) && file_exists($category['icon'])) {
            $menu_html .= '<img src="' . base_url($category['icon']) . '" alt="' . htmlspecialchars(__($category['name'])) . '">';
        } else {
            $menu_html .= '<i class="fa-solid fa-folder"></i>';
        }
        $menu_html .= '<a href="' . base_url('category/'.$category['slug']) . '">' . __($category['name']) . '</a>';
        $menu_html .= '</h5>';
        
        $menu_html .= '<ul class="megamenu-list">';
        
        foreach($child_categories as $category1) {
            $menu_html .= '<li><a href="' . base_url('category/'.$category1['slug']) . '">';
            if (!empty($category1['icon']) && file_exists($category1['icon'])) {
                $menu_html .= '<img src="' . base_url($category1['icon']) . '" alt="' . htmlspecialchars(__($category1['name'])) . '">';
            } else {
                $menu_html .= '<i class="fa-solid fa-tag"></i>';
            }
            $menu_html .= '<span>' . __($category1['name']) . '</span></a></li>';
        }
        
        $menu_html .= '</ul>';
        $menu_html .= '</div>';
        $menu_html .= '</div>';
    }
    
    $menu_html .= '</div>';
}

// Tạo HTML cho category buttons (home.php)
$home_buttons_html = '';
foreach($all_categories as $category) {
    $home_buttons_html .= '<li><a class="btn-category-home"';
    $home_buttons_html .= ' href="javascript:void(0);"';
    $home_buttons_html .= ' onclick="loadProductsByCategory(\'' . htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') . '\')"';
    $home_buttons_html .= ' data-category-id="' . htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8') . '"';
    $home_buttons_html .= ' data-category-slug="' . htmlspecialchars($category['slug'], ENT_QUOTES, 'UTF-8') . '">';
    $home_buttons_html .= '<img src="' . base_url($category['icon']) . '" width="25px" class="me-2">';
    $home_buttons_html .= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');
    $home_buttons_html .= '</a></li>';
}

// Trả về JSON với cả 2 loại HTML
header('Content-Type: application/json');
echo json_encode([
    'menu_html' => $menu_html,
    'home_buttons_html' => $home_buttons_html
]);
?>
