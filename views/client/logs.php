<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Nhật ký hoạt động') . ' | ' . $CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
';
$body['footer'] = '';
require_once(__DIR__ . '/../../models/is_user.php');
require_once(__DIR__ . '/header.php');
require_once(__DIR__ . '/nav.php');

// Get filter values from URL (for form display only)
$content_filter = isset($_GET['content']) ? validate_string($_GET['content'], 255, 2) : '';
$ip_filter = isset($_GET['ip']) ? validate_ip($_GET['ip']) : '';
$shortByDate = isset($_GET['shortByDate']) ? validate_int($_GET['shortByDate'], 1, 3) : '';
$time_filter = isset($_GET['time']) ? validate_string($_GET['time'], 50) : '';
?>

<section class="py-5 inner-section">
    <div class="container">
        <div class="row content-reverse">
            <div class="col-lg-3 mt-3 mt-lg-0">
                <?php require_once(__DIR__ . '/sidebar.php'); ?>
            </div>
            <div class="col-lg-9">
                <!-- Header -->
                <div class="data-card-header">
                    <div class="data-header-info">
                        <div class="data-header-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <h1 class="data-card-title"><?= __('Nhật ký hoạt động'); ?></h1>
                            <p class="data-subtitle"><?= __('Lịch sử các hoạt động của tài khoản'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="data-filter-section">
                    <form id="logsFilterForm">
                        <div class="data-filter-grid">
                            <div class="data-filter-group">
                                <label><?= __('Thao tác'); ?></label>
                                <input type="text" class="form-control" id="filterContent" name="content" value="<?= htmlspecialchars($content_filter); ?>" placeholder="<?= __('Nhập thao tác...'); ?>">
                            </div>
                            <div class="data-filter-group">
                                <label><?= __('Địa chỉ IP'); ?></label>
                                <input type="text" class="form-control" id="filterIp" name="ip" value="<?= htmlspecialchars($ip_filter); ?>" placeholder="<?= __('Nhập IP...'); ?>">
                            </div>
                            <div class="data-filter-group">
                                <label><?= __('Thời gian'); ?></label>
                                <select id="filterShortByDate" name="shortByDate" class="form-select">
                                    <option value=""><?= __('Tất cả'); ?></option>
                                    <option value="1" <?= $shortByDate == 1 ? 'selected' : ''; ?>><?= __('Hôm nay'); ?></option>
                                    <option value="2" <?= $shortByDate == 2 ? 'selected' : ''; ?>><?= __('Tuần này'); ?></option>
                                    <option value="3" <?= $shortByDate == 3 ? 'selected' : ''; ?>><?= __('Tháng này'); ?></option>
                                </select>
                            </div>
                            <div class="data-filter-group">
                                <label><?= __('Khoảng ngày'); ?></label>
                                <input type="text" class="form-control" id="flatpickr-range" name="time" value="<?= htmlspecialchars($time_filter); ?>" placeholder="<?= __('Chọn ngày'); ?>" readonly>
                            </div>
                            <div class="data-filter-actions">
                                <button type="submit" class="btn-filter-search">
                                    <i class="fa-solid fa-search"></i>
                                    <?= __('Tìm'); ?>
                                </button>
                                <a href="javascript:void(0)" class="btn-filter-reset" id="resetFilter">
                                    <i class="fa-solid fa-times"></i>
                                    <?= __('Xóa'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="data-table-wrapper">
                    <!-- Empty State -->
                    <div class="data-empty-state" id="logsEmpty" style="display: none;">
                        <div class="data-empty-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <h4><?= __('Không có dữ liệu'); ?></h4>
                        <p><?= __('Chưa có nhật ký hoạt động nào trong khoảng thời gian này.'); ?></p>
                    </div>

                    <table class="data-table" id="logsTable">
                        <thead>
                            <tr>
                                <th style="width: 140px;"><?= __('Thời gian'); ?></th>
                                <th><?= __('Thao tác'); ?></th>
                                <th style="width: 120px;"><?= __('Địa chỉ IP'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <!-- Content loaded via AJAX -->
                        </tbody>
                        <!-- Loading Skeleton -->
                        <tbody class="data-loading-skeleton" id="logsLoading">
                            <?php for ($i = 0; $i < 10; $i++): ?>
                                <tr class="skeleton-row">
                                    <td>
                                        <div class="skeleton-cell skeleton-md"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-cell skeleton-xl"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-cell skeleton-md"></div>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>

                    <!-- Load More -->
                    <div class="data-load-more" id="loadMoreWrapper" style="display: none;">
                        <button type="button" class="btn-load-more" id="btnLoadMore">
                            <i class="fa-solid fa-arrow-down"></i>
                            <?= __('Tải thêm'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hidden Inputs for AJAX -->
<input type="hidden" id="csrfToken" value="<?= generateCSRFToken(); ?>">

<?php require_once(__DIR__ . '/footer.php'); ?>

<script>
    var LOGS_AJAX_URL = '<?= base_url('ajaxs/client/logs.php'); ?>';
    var LOGS_USER_TOKEN = '<?= $getUser['token']; ?>';
</script>
<script src="<?= BASE_URL('mod/js/logs.js'); ?>"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined' && document.getElementById('flatpickr-range')) {
            flatpickr('#flatpickr-range', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                locale: {
                    firstDayOfWeek: 1,
                    rangeSeparator: ' to '
                }
            });
        }
    });
</script>