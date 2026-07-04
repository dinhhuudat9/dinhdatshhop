<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Quản lý kết nối API') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '';
$body['footer'] = '

';
require_once(__DIR__ . '/../../models/is_admin.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/sidebar.php');
if (checkPermission($getUser['admin'], 'manager_suppliers') != true) {
    die('<script type="text/javascript">if(!alert("Bạn không có quyền sử dụng tính năng này")){window.history.back();}</script>');
}
if (isset($_GET['limit'])) {
    $limit = intval(check_string($_GET['limit']));
} else {
    $limit = 10;
}
if (isset($_GET['page'])) {
    $page = check_string(intval($_GET['page']));
} else {
    $page = 1;
}
$from = ($page - 1) * $limit;
$where = " `id` > 0 ";
$shortByDate  = '';


if (isset($_GET['shortByDate'])) {
    $shortByDate = check_string($_GET['shortByDate']);
    $yesterday = date('Y-m-d', strtotime("-1 day"));
    $currentWeek = date("W");
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentDate = date("Y-m-d");
    if ($shortByDate == 1) {
        $where .= " AND `create_gettime` LIKE '%" . $currentDate . "%' ";
    }
    if ($shortByDate == 2) {
        $where .= " AND YEAR(create_gettime) = $currentYear AND WEEK(create_gettime, 1) = $currentWeek ";
    }
    if ($shortByDate == 3) {
        $where .= " AND MONTH(create_gettime) = '$currentMonth' AND YEAR(create_gettime) = '$currentYear' ";
    }
}

$listDatatable = $CMSNT->get_list(" SELECT * FROM `suppliers` WHERE $where ORDER BY `id` DESC LIMIT $from,$limit ");
$totalDatatable = $CMSNT->num_rows(" SELECT * FROM `suppliers` WHERE $where ORDER BY id DESC ");
$urlDatatable = pagination(base_url_admin("product-api&limit=$limit&shortByDate=$shortByDate&"), $from, $totalDatatable, $limit);

?>


<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><i class="fa-solid fa-code"></i> Kết nối API</h1>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            DANH SÁCH API ĐANG KẾT NỐI
                        </div>
                        <div class="d-flex">
                            <a type="button" href="<?= base_url_admin('product-api-add'); ?>"
                                class="btn btn-sm btn-primary btn-wave waves-light waves-effect waves-light"><i
                                    class="ri-add-line fw-semibold align-middle"></i> THÊM WEBSITE API</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url_admin(); ?>" class="align-items-center mb-3" name="formSearch"
                            method="GET">
                            <div class="row row-cols-lg-auto g-3 mb-3">
                                <input type="hidden" name="module" value="admin">
                                <input type="hidden" name="action" value="product-api">
                            </div>
                            <div class="top-filter">
                                <div class="filter-show">
                                    <label class="filter-label">Show :</label>
                                    <select name="limit" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option <?= $limit == 5 ? 'selected' : ''; ?> value="5">5</option>
                                        <option <?= $limit == 10 ? 'selected' : ''; ?> value="10">10</option>
                                        <option <?= $limit == 20 ? 'selected' : ''; ?> value="20">20</option>
                                        <option <?= $limit == 50 ? 'selected' : ''; ?> value="50">50</option>
                                        <option <?= $limit == 100 ? 'selected' : ''; ?> value="100">100</option>
                                        <option <?= $limit == 500 ? 'selected' : ''; ?> value="500">500</option>
                                        <option <?= $limit == 1000 ? 'selected' : ''; ?> value="1000">1000</option>
                                    </select>
                                </div>
                                <div class="filter-short">
                                    <label class="filter-label"><?= __('Short by Date:'); ?></label>
                                    <select name="shortByDate" onchange="this.form.submit()"
                                        class="form-select filter-select">
                                        <option value=""><?= __('Tất cả'); ?></option>
                                        <option <?= $shortByDate == 1 ? 'selected' : ''; ?> value="1">
                                            <?= __('Hôm nay'); ?>
                                        </option>
                                        <option <?= $shortByDate == 2 ? 'selected' : ''; ?> value="2">
                                            <?= __('Tuần này'); ?>
                                        </option>
                                        <option <?= $shortByDate == 3 ? 'selected' : ''; ?> value="3">
                                            <?= __('Tháng này'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive mb-3">
                            <table class="table text-nowrap table-striped table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th><?= __('Website'); ?></th>
                                        <th class="text-center"><?= __('Type'); ?></th>
                                        <th class="text-center"><?= __('Số dư'); ?></th>

                                        <th class="text-center"><?= __('Chi tiết'); ?></th>
                                        <th class="text-center"><?= __('Thống kê'); ?></th>
                                        <th class="text-center"><?= __('Trạng thái'); ?></th>
                                        <th class="text-center"><?= __('Thao tác'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listDatatable as $supplier): ?>

                                        <tr onchange="updateForm(`<?= $supplier['id']; ?>`)">
                                            <td>
                                                <?php if (!empty($supplier['domain'])): ?>
                                                    <i class="fa-solid fa-link"></i> Domain: <a class="text-primary"
                                                        href="<?= $supplier['domain']; ?>"
                                                        target="_blank"><?= $supplier['domain']; ?></a><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['username'])): ?>
                                                    <i class="fa-solid fa-user"></i> Username:
                                                    <strong><?= substr($supplier['username'], 0, 4); ?>...</strong><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['password'])): ?>
                                                    <i class="fa-solid fa-lock"></i> Password:
                                                    <strong><?= substr($supplier['password'], 0, 4); ?>...</strong><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['api_key'])): ?>
                                                    <i class="fa-solid fa-key"></i> API Key:
                                                    <strong><?= substr($supplier['api_key'], 0, 12); ?>...</strong><br>
                                                <?php endif; ?>

                                                <?php if (!empty($supplier['token'])): ?>
                                                    <i class="fa-solid fa-key"></i> Token:
                                                    <strong><?= substr($supplier['token'], 0, 12); ?>...</strong>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $supplier['type']; ?></span>
                                            </td>
                                            <td class="text-right">
                                                <?= check_string($supplier['price']); ?>
                                            </td>

                                            <td>
                                                <?php
                                                $updateNameBadge = $supplier['update_name'] == 'ON' ? 'success' : 'danger';
                                                $updatePriceBadge = $supplier['update_price'] == 'ON' ? 'success' : 'danger';
                                                $roundMoneyBadge = $supplier['roundMoney'] == 'ON' ? 'success' : 'danger';
                                                $syncCategoryBadge = $supplier['sync_category'] == 'ON' ? 'success' : 'danger';
                                                ?>
                                                Tăng giá: <span class="badge bg-outline-primary"><?= $supplier['discount']; ?>%</span><br>
                                                Cập nhật tên sản phẩm: <span class="badge bg-<?= $updateNameBadge; ?>"><?= $supplier['update_name']; ?></span><br>
                                                Cập nhật giá bán: <span class="badge bg-<?= $updatePriceBadge; ?>"><?= $supplier['update_price']; ?></span><br>
                                                Làm tròn giá bán: <span class="badge bg-<?= $roundMoneyBadge; ?>"><?= $supplier['roundMoney']; ?></span><br>
                                                Đồng bộ chuyên mục API: <span class="badge bg-<?= $syncCategoryBadge; ?>"><?= $supplier['sync_category']; ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                // Thống kê cho supplier
                                                $stat_categories = $CMSNT->num_rows_safe("SELECT id FROM `categories` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                $stat_products = $CMSNT->num_rows_safe("SELECT id FROM `products` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                $stat_plans = $CMSNT->num_rows_safe("SELECT id FROM `product_plans` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                $stat_orders = $CMSNT->get_row_safe("SELECT COUNT(*) as total FROM `product_orders` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                $stat_revenue = $CMSNT->get_row_safe("SELECT SUM(`total_price`) as total FROM `product_orders` WHERE `supplier_id` = ? AND `status` IN ('completed', 'success')", [$supplier['id']]);
                                                $stat_cost = $CMSNT->get_row_safe("SELECT SUM(`cost_price` * `quantity`) as total FROM `product_orders` WHERE `supplier_id` = ? AND `status` IN ('completed', 'success')", [$supplier['id']]);
                                                $total_orders = (int)($stat_orders['total'] ?? 0);
                                                $total_revenue = (float)($stat_revenue['total'] ?? 0);
                                                $total_cost = (float)($stat_cost['total'] ?? 0);
                                                $total_profit = $total_revenue - $total_cost;
                                                ?>
                                                <span class="badge bg-info-transparent mb-1"><?= __('Chuyên mục'); ?>: <?= format_cash($stat_categories); ?></span><br>
                                                <span class="badge bg-primary-transparent mb-1"><?= __('Sản phẩm'); ?>: <?= format_cash($stat_products); ?></span><br>
                                                <span class="badge bg-secondary-transparent mb-1"><?= __('Gói sản phẩm'); ?>: <?= format_cash($stat_plans); ?></span><br>
                                                <span class="badge bg-warning-transparent mb-1"><?= __('Đơn hàng'); ?>: <?= format_cash($total_orders); ?></span><br>
                                                <span class="badge bg-success-transparent mb-1"><?= __('Doanh thu'); ?>: <?= format_currency($total_revenue); ?></span><br>
                                                <span class="badge bg-<?= $total_profit >= 0 ? 'success' : 'danger'; ?>-transparent"><?= __('Lợi nhuận'); ?>: <?= format_currency($total_profit); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch form-check-lg">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="status<?= $supplier['id']; ?>" value="1"
                                                        <?= $supplier['status'] == 1 ? 'checked=""' : ''; ?>>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-gear"></i> <?= __('Thao tác'); ?>
                                                    </button>
                                                    <ul class="dropdown-menu">

                                                        <?php
                                                        // Đếm số gói dịch vụ của supplier
                                                        $planCount = $CMSNT->num_rows_safe("SELECT id FROM `product_plans` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                        ?>
                                                        <?php
                                                        // Đếm số sản phẩm của supplier
                                                        $productCount = $CMSNT->num_rows_safe("SELECT id FROM `products` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                        ?>
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('products&supplier_id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-cube text-info me-2"></i> <?= __('Sản phẩm'); ?> (<?= $productCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('product-plans-all&supplier_id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-box text-warning me-2"></i> <?= __('Gói sản phẩm'); ?> (<?= $planCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" href="<?= base_url_admin('product-orders&supplier_id=' . $supplier['id']); ?>">
                                                                <i class="fa-solid fa-cart-shopping text-success me-2"></i> <?= __('Đơn hàng'); ?>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="<?= base_url_admin('product-api-edit&id=' . $supplier['id']); ?>">
                                                                <i class="fas fa-edit text-info me-2"></i> <?= __('Chỉnh sửa'); ?>
                                                            </a>
                                                        </li>
                                                        <?php
                                                        // Đếm số chuyên mục của supplier
                                                        $categoryCount = $CMSNT->num_rows_safe("SELECT id FROM `categories` WHERE `supplier_id` = ?", [$supplier['id']]);
                                                        ?>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                                onclick="removePlansOnly('<?= $supplier['id']; ?>', '<?= $planCount; ?>')">
                                                                <i class="fas fa-cubes text-danger me-2"></i> <?= __('Xóa gói sản phẩm'); ?> (<?= $planCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                                onclick="removeProductsOnly('<?= $supplier['id']; ?>', '<?= $productCount; ?>')">
                                                                <i class="fas fa-cube text-danger me-2"></i> <?= __('Xóa sản phẩm'); ?> (<?= $productCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)"
                                                                onclick="removeCategoriesOnly('<?= $supplier['id']; ?>', '<?= $categoryCount; ?>')">
                                                                <i class="fas fa-folder text-danger me-2"></i> <?= __('Xóa chuyên mục'); ?> (<?= $categoryCount; ?>)
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="removeItem('<?= $supplier['id']; ?>')">
                                                                <i class="fas fa-trash text-danger me-2"></i> <?= __('Xóa API'); ?>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <p class="dataTables_info">Showing <?= $limit; ?> of
                                    <?= format_cash($totalDatatable); ?>
                                    Results</p>
                            </div>
                            <div class="col-sm-12 col-md-7 mb-3">
                                <?= $totalDatatable > $limit ? $urlDatatable : ''; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <style>
            .brand-carousel {
                width: 100%;
                overflow: hidden;
                animation: moveCards 25s linear infinite;
                white-space: nowrap;
            }

            .brand-carousel-container {
                width: 100%;
                overflow-x: auto;
            }

            .brand-carousel {
                white-space: nowrap;
                font-size: 0;
                width: max-content;
            }

            .brand-card {
                font-size: 16px;
                display: inline-block;
                vertical-align: top;
                margin-right: 20px;
                transition: all 0.3s ease;
            }

            .brand-carousel:hover {
                animation-play-state: paused;
            }

            @keyframes moveCards {
                0% {
                    transform: translateX(0%);
                }

                100% {
                    transform: translateX(-100%);
                }
            }

            .brand-card {
                position: relative;
                display: inline-block;
                margin: 10px;
                vertical-align: middle;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 20px;
                transition: all 0.3s ease;
            }

            .brand-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            .brand-card img {
                width: 100px;
                height: 100px;
                object-fit: contain;
                margin-bottom: 20px;
            }

            .connect-button,
            .website-button {
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                color: #fff;
                padding: 5px 10px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
            }

            .brand-card:hover .connect-button,
            .brand-card:hover .website-button {
                opacity: 1;
            }

            .website-button {
                bottom: 45px;
            }

            .api-section {
                border-left: 4px solid #3498db;
                transition: all 0.3s ease;
            }

            .api-section:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }
        </style>
        <div class="row justify-content-center py-4">
            <div class="col-12 text-center mb-3">
                <h4 class="fw-bold"><i class="fa-solid fa-boxes-packing text-primary me-2"></i>Nhà cung cấp API gợi ý</h4>
                <p class="text-muted">Kết nối nhanh với các nhà cung cấp API đáng tin cậy</p>
            </div>
            <div class="brand-carousel-container">
                <div class="brand-carousel animated-carousel">

                </div>
            </div>
            <div class="mt-3 text-center" id="notitcation_suppliers"></div>
        </div>
        <script>
            $(document).ready(function() {
                $('.brand-carousel').html('');
                $.ajax({
                    url: 'https://api.cmsnt.co/suppliers.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        // Xử lý dữ liệu trả về từ server
                        if (response && response.suppliers.length > 0) {
                            var html = '';
                            $.each(response.suppliers, function(index, brand) {
                                html += '<div class="brand-card">';
                                html += '<img src="' + brand.logo + '" alt="Logo" class="mb-2">';
                                html +=
                                    '<a href="<?= base_url_admin("product-api-add"); ?>&domain=' +
                                    brand.domain + '&type=' + brand.type +
                                    '" class="connect-button btn btn-sm btn-danger">Kết nối</a>';
                                html += '<a href="' + brand.domain +
                                    '?utm_source=ads_cmsnt" target="_blank" class="website-button btn btn-sm btn-primary">Xem</a>';
                                html += '</div>';
                            });
                            $('.brand-carousel').html(html);
                            $('#notitcation_suppliers').html(response.notication);
                            calculateAndSetAnimationDuration();
                        } else {
                            $('.brand-carousel').html('');
                        }
                    },
                    error: function() {
                        $('.brand-carousel').html('');
                    }
                });
            });
            // Function to calculate carousel width and set animation duration
            function calculateAndSetAnimationDuration() {
                var carousel = $('.animated-carousel');
                var carouselWidth = carousel[0].scrollWidth;
                var cardWidth = carousel.children().first().outerWidth(true); // Including margin
                var numberOfCards = carouselWidth / cardWidth;
                var animationDuration = numberOfCards * 2; // Adjust this multiplier as needed
                carousel.css('animation-duration', animationDuration + 's');
            }
        </script>

    </div>
</div>


<?php
require_once(__DIR__ . '/footer.php');
?>

<script>
    function updateForm(id) {
        $.ajax({
            url: "<?= BASE_URL("ajaxs/admin/update.php"); ?>",
            method: "POST",
            dataType: "JSON",
            data: {
                action: 'updateTableProductAPI',
                id: id,
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




    var lightboxVideo = GLightbox({
        selector: '.glightbox'
    });
</script>



<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteModalLabel">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    <?= __('Xác nhận xóa nhà cung cấp API'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-warning">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-exclamation-triangle text-warning me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa API này khỏi hệ thống'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả sản phẩm của API này'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả chuyên mục của API này'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa nhà cung cấp này'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa nhà cung cấp'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="confirmDeletePlansModal" tabindex="-1" aria-labelledby="confirmDeletePlansModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeletePlansModalLabel">
                    <i class="fa-solid fa-cubes me-2"></i>
                    <?= __('Xác nhận xóa gói sản phẩm'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả gói dịch vụ của nhà cung cấp này'); ?> (<span id="plansCount">0</span> <?= __('gói'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả trường tùy chỉnh của gói'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-info-circle text-info me-2"></i>
                            <?= __('Giữ nguyên thông tin nhà cung cấp'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                            <?= __('Đơn hàng đã mua sẽ không bị ảnh hưởng'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmPlansCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmPlansCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa toàn bộ gói dịch vụ'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeletePlansButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa gói sản phẩm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Modal xác nhận xóa sản phẩm -->
<div class="modal fade" id="confirmDeleteProductsModal" tabindex="-1" aria-labelledby="confirmDeleteProductsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteProductsModalLabel">
                    <i class="fa-solid fa-cube me-2"></i>
                    <?= __('Xác nhận xóa sản phẩm'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả sản phẩm của nhà cung cấp này'); ?> (<span id="productsCount">0</span> <?= __('sản phẩm'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả gói sản phẩm liên quan'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả trường tùy chỉnh của gói'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                            <?= __('Ảnh sản phẩm sẽ bị xóa khỏi hệ thống'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmProductsCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmProductsCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa toàn bộ sản phẩm'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteProductsButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa chuyên mục -->
<div class="modal fade" id="confirmDeleteCategoriesModal" tabindex="-1" aria-labelledby="confirmDeleteCategoriesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-transparent">
                <h5 class="modal-title text-danger" id="confirmDeleteCategoriesModalLabel">
                    <i class="fa-solid fa-folder me-2"></i>
                    <?= __('Xác nhận xóa chuyên mục'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation text-danger me-2 fs-4"></i>
                        <div>
                            <h6 class="alert-heading mb-1"><?= __('Cảnh báo quan trọng!'); ?></h6>
                            <p class="mb-0"><?= __('Hành động này sẽ không thể hoàn tác.'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <p class="mb-2"><?= __('Hệ thống sẽ thực hiện các hành động sau:'); ?></p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fa-solid fa-check text-danger me-2"></i>
                            <?= __('Xóa tất cả chuyên mục của nhà cung cấp này'); ?> (<span id="categoriesCount">0</span> <?= __('chuyên mục'); ?>)
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-info-circle text-info me-2"></i>
                            <?= __('Sản phẩm sẽ được chuyển về Chưa phân loại'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                            <?= __('Icon chuyên mục sẽ bị xóa khỏi hệ thống'); ?>
                        </li>
                    </ul>
                </div>

                <div class="form-check form-check-lg d-flex align-items-center bg-light p-3 rounded">
                    <input class="form-check-input" type="checkbox" value="" id="confirmCategoriesCheckbox">
                    <label class="form-check-label fw-semibold" for="confirmCategoriesCheckbox">
                        <?= __('Tôi hiểu rủi ro và đồng ý xóa toàn bộ chuyên mục'); ?>
                    </label>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary btn-wave" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i><?= __('Hủy bỏ'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-wave" id="confirmDeleteCategoriesButton" disabled>
                    <i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    function removePlansOnly(supplierId, plansCount) {
        $('#plansCount').text(plansCount);
        $('#confirmDeletePlansModal').modal('show');
        $('#confirmPlansCheckbox').prop('checked', false);
        $('#confirmDeletePlansButton').prop('disabled', true);

        $('#confirmDeletePlansButton').off('click').on('click', function() {
            if ($('#confirmPlansCheckbox').prop('checked')) {
                $('#confirmDeletePlansButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: supplierId,
                        action: 'removePlansOnly'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            $('#confirmDeletePlansModal').modal('hide');
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeletePlansButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa gói sản phẩm'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        showMessage("<?= __('Có lỗi xảy ra, vui lòng thử lại!'); ?>", "error");
                        $('#confirmDeletePlansButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa gói sản phẩm'); ?>').prop(
                            'disabled',
                            false);
                    }
                });
            }
        });

        $('#confirmPlansCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeletePlansButton').prop('disabled', false);
            } else {
                $('#confirmDeletePlansButton').prop('disabled', true);
            }
        });
    }

    function removeItem(id) {
        $('#confirmDeleteModal').modal('show');

        $('#confirmDeleteButton').off('click').on('click', function() {
            if ($('#confirmCheckbox').prop('checked')) {
                $('#confirmDeleteButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: id,
                        action: 'removeSupplier'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa nhà cung cấp'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        alert(html(result));
                        location.reload();
                    }
                });
            }
        });

        $('#confirmCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteButton').prop('disabled', false);
            } else {
                $('#confirmDeleteButton').prop('disabled', true);
            }
        });
    }

    function removeProductsOnly(supplierId, productCount) {
        $('#productsCount').text(productCount);
        $('#confirmDeleteProductsModal').modal('show');
        $('#confirmProductsCheckbox').prop('checked', false);
        $('#confirmDeleteProductsButton').prop('disabled', true);

        $('#confirmDeleteProductsButton').off('click').on('click', function() {
            if ($('#confirmProductsCheckbox').prop('checked')) {
                $('#confirmDeleteProductsButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: supplierId,
                        action: 'removeProductsOnly'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            $('#confirmDeleteProductsModal').modal('hide');
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteProductsButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        showMessage("<?= __('Có lỗi xảy ra, vui lòng thử lại!'); ?>", "error");
                        $('#confirmDeleteProductsButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa sản phẩm'); ?>').prop(
                            'disabled',
                            false);
                    }
                });
            }
        });

        $('#confirmProductsCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteProductsButton').prop('disabled', false);
            } else {
                $('#confirmDeleteProductsButton').prop('disabled', true);
            }
        });
    }

    function removeCategoriesOnly(supplierId, categoryCount) {
        $('#categoriesCount').text(categoryCount);
        $('#confirmDeleteCategoriesModal').modal('show');
        $('#confirmCategoriesCheckbox').prop('checked', false);
        $('#confirmDeleteCategoriesButton').prop('disabled', true);

        $('#confirmDeleteCategoriesButton').off('click').on('click', function() {
            if ($('#confirmCategoriesCheckbox').prop('checked')) {
                $('#confirmDeleteCategoriesButton').html('<i class="fa fa-spinner fa-spin me-1"></i><?= __('Đang xóa...'); ?>').prop(
                    'disabled',
                    true);
                $.ajax({
                    url: "<?= BASE_URL("ajaxs/admin/remove.php"); ?>",
                    method: "POST",
                    dataType: "JSON",
                    data: {
                        id: supplierId,
                        action: 'removeCategoriesOnly'
                    },
                    success: function(result) {
                        if (result.status == 'success') {
                            $('#confirmDeleteCategoriesModal').modal('hide');
                            Swal.fire({
                                title: "<?= __('Thành công!'); ?>",
                                text: result.msg,
                                icon: "success"
                            });
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showMessage(result.msg, result.status);
                            $('#confirmDeleteCategoriesButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục'); ?>').prop(
                                'disabled',
                                false);
                        }
                    },
                    error: function() {
                        showMessage("<?= __('Có lỗi xảy ra, vui lòng thử lại!'); ?>", "error");
                        $('#confirmDeleteCategoriesButton').html('<i class="fa fa-trash me-1"></i><?= __('Xóa chuyên mục'); ?>').prop(
                            'disabled',
                            false);
                    }
                });
            }
        });

        $('#confirmCategoriesCheckbox').off('change').on('change', function() {
            if ($(this).prop('checked')) {
                $('#confirmDeleteCategoriesButton').prop('disabled', false);
            } else {
                $('#confirmDeleteCategoriesButton').prop('disabled', true);
            }
        });
    }
</script>