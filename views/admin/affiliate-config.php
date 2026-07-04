<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}
$body = [
    'title' => __('Cấu hình Tiếp Thị Liên Kết').' | '.$CMSNT->site('title'),
    'desc'   => $CMSNT->site('description'),
    'keyword' => $CMSNT->site('keywords')
];
$body['header'] = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js"></script>
';
$body['footer'] = '
<!-- ckeditor -->
<script src="'.BASE_URL('public/ckeditor/ckeditor.js').'"></script>
';
require_once(__DIR__.'/../../models/is_admin.php');
require_once(__DIR__.'/header.php');
require_once(__DIR__.'/sidebar.php');
require_once(__DIR__.'/../../models/is_license.php');

if(checkPermission($getUser['admin'], 'edit_affiliate') != true){
    die('<script type="text/javascript">if(!alert("'.__('Bạn không có quyền sử dụng tính năng này').'")){window.history.back().location.reload();}</script>');
}

// Xử lý lưu cấu hình
if (isset($_POST['SaveSettings'])) {
    // Kiểm tra CSRF token
    checkCSRF();
    
    if ($CMSNT->site('status_demo') != 0) {
        die('<script type="text/javascript">if(!alert("'.__('Chức năng này không thể sử dụng vì đây là trang web demo').'")){window.history.back().location.reload();}</script>');
    }
    
    // Log action
    $CMSNT->insert("logs", [
        'user_id'       => $getUser['id'],
        'ip'            => myip(),
        'device'        => getUserAgent(),
        'createdate'    => gettime(),
        'action'        => 'Cấu hình Affiliate Program'
    ]);
    
    // Danh sách các settings được phép cập nhật
    $allowed_settings = [
        'affiliate_status',
        'affiliate_ck',
        'affiliate_order_ck',
        'affiliate_order_status',
        'affiliate_recharge_status',
        'affiliate_min',
        'affiliate_min_commission',
        'affiliate_cookie_days',
        'affiliate_signup_bonus',
        'affiliate_banks',
        'affiliate_chat_id_telegram',
        'affiliate_note'
    ];
    
    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowed_settings)) {
            $CMSNT->update("settings", [
                'value' => $value
            ], " `name` = ? ", [$key]);
        }
    }
    
    // Gửi thông báo admin
    $my_text = $CMSNT->site('noti_action');
    $my_text = str_replace('{domain}', $_SERVER['SERVER_NAME'], $my_text);
    $my_text = str_replace('{username}', $getUser['username'], $my_text);
    $my_text = str_replace('{action}', __('Cấu hình Affiliate Program'), $my_text);
    $my_text = str_replace('{ip}', myip(), $my_text);    
    $my_text = str_replace('{time}', gettime(), $my_text);
    sendMessAdmin($my_text);
    
    die('<script type="text/javascript">if(!alert("'.__('Lưu thành công!').'")){window.history.back().location.reload();}</script>');
}

// Lấy thống kê
$currentMonth = date('m');
$currentYear = date('Y');
$currentWeek = date("W");
$currentDate = date("Y-m-d");

// Tổng hoa hồng đã trả
$totalCommission = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(sotienthaydoi), 0) as total FROM `aff_log` WHERE `sotienthaydoi` > 0"
)['total'] ?? 0;

// Tổng đã rút
$totalWithdrawn = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(amount), 0) as total FROM `aff_withdraw` WHERE `status` = 'completed'"
)['total'] ?? 0;

// Tổng affiliates
$totalAffiliates = $CMSNT->get_row_safe(
    "SELECT COUNT(DISTINCT user_id) as total FROM `aff_log`"
)['total'] ?? 0;

// Số dư chưa rút
$pendingBalance = $CMSNT->get_row_safe(
    "SELECT COALESCE(SUM(ref_price), 0) as total FROM `users` WHERE `ref_price` > 0"
)['total'] ?? 0;

// Yêu cầu rút đang chờ
$pendingWithdrawals = $CMSNT->num_rows_safe(
    "SELECT id FROM `aff_withdraw` WHERE `status` = 'pending'"
);
?>

<div class="main-content app-content">
    <div class="container-fluid">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <h1 class="page-title fw-semibold fs-18 mb-0"><?=__('Cấu hình Affiliate');?></h1>
            <div class="ms-md-1 ms-0">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="#"><?=__('Affiliate Program');?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?=__('Cấu hình');?></li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?=__('Tổng hoa hồng đã trả');?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?=format_currency($totalCommission);?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-primary-transparent rounded">
                                <i class="ti ti-coins fs-24 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?=__('Đã rút thành công');?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?=format_currency($totalWithdrawn);?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-success-transparent rounded">
                                <i class="ti ti-wallet fs-24 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?=__('Số dư chưa rút');?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?=format_currency($pendingBalance);?></h4>
                            </div>
                            <div class="avatar avatar-lg bg-warning-transparent rounded">
                                <i class="ti ti-clock fs-24 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card custom-card overflow-hidden">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"><?=__('Tổng Affiliates');?></p>
                                <h4 class="fw-semibold mt-2 mb-0"><?=format_cash($totalAffiliates);?> <small class="text-muted fs-12"><?=__('thành viên');?></small></h4>
                            </div>
                            <div class="avatar avatar-lg bg-info-transparent rounded">
                                <i class="ti ti-users fs-24 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if($pendingWithdrawals > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show custom-alert-icon shadow-sm mb-4" role="alert">
            <svg class="svg-warning" xmlns="http://www.w3.org/2000/svg" height="1.5rem" viewBox="0 0 24 24" width="1.5rem" fill="#000000">
                <path d="M0 0h24v24H0z" fill="none" />
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
            </svg>
            <strong><?=__('Thông báo');?>:</strong> <?=__('Có');?> <strong><?=$pendingWithdrawals;?></strong> <?=__('yêu cầu rút tiền đang chờ xử lý');?>. 
            <a href="<?=base_url_admin('affiliate-withdraw&status=pending');?>" class="alert-link"><?=__('Xem ngay');?></a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x"></i></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header justify-content-between">
                        <div class="card-title">
                            <i class="ti ti-settings me-2"></i><?=__('CẤU HÌNH AFFILIATE PROGRAM');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            
                            <!-- Cấu hình chung -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-primary mb-3">
                                        <i class="ti ti-settings-2 me-2"></i><?=__('Cấu hình chung');?>
                                    </h6>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Trạng thái Affiliate');?> <span class="text-danger">*</span></label>
                                        <select class="form-control" name="affiliate_status" required>
                                            <option <?=$CMSNT->site('affiliate_status') == 1 ? 'selected' : '';?> value="1"><?=__('Bật');?></option>
                                            <option <?=$CMSNT->site('affiliate_status') == 0 ? 'selected' : '';?> value="0"><?=__('Tắt');?></option>
                                        </select>
                                        <small class="text-muted"><?=__('Bật/tắt toàn bộ hệ thống affiliate');?></small>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Thời gian lưu Cookie');?></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="<?=$CMSNT->site('affiliate_cookie_days') ?: 30;?>" name="affiliate_cookie_days" min="1" max="365">
                                            <span class="input-group-text"><?=__('ngày');?></span>
                                        </div>
                                        <small class="text-muted"><?=__('Thời gian cookie affiliate được lưu trên trình duyệt');?></small>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Số tiền hoa hồng tối thiểu');?></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="<?=$CMSNT->site('affiliate_min_commission') ?: 1000;?>" name="affiliate_min_commission" min="0">
                                            <span class="input-group-text"><?=__('VNĐ');?></span>
                                        </div>
                                        <small class="text-muted"><?=__('Hoa hồng nhỏ hơn số này sẽ không được ghi nhận');?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hoa hồng nạp tiền -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-success mb-3">
                                        <i class="ti ti-cash me-2"></i><?=__('Hoa hồng từ nạp tiền');?>
                                    </h6>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Trạng thái');?></label>
                                        <select class="form-control" name="affiliate_recharge_status">
                                            <option <?=$CMSNT->site('affiliate_recharge_status') == 1 ? 'selected' : '';?> value="1"><?=__('Bật');?></option>
                                            <option <?=$CMSNT->site('affiliate_recharge_status') == 0 ? 'selected' : '';?> value="0"><?=__('Tắt');?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Tỷ lệ hoa hồng');?> <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" value="<?=$CMSNT->site('affiliate_ck') ?: 0;?>" name="affiliate_ck" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small class="text-muted"><?=__('Khi thành viên được giới thiệu nạp tiền');?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hoa hồng đơn hàng -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-info mb-3">
                                        <i class="ti ti-shopping-cart me-2"></i><?=__('Hoa hồng từ đơn hàng sản phẩm');?>
                                    </h6>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Trạng thái');?></label>
                                        <select class="form-control" name="affiliate_order_status">
                                            <option <?=$CMSNT->site('affiliate_order_status') == 1 ? 'selected' : '';?> value="1"><?=__('Bật');?></option>
                                            <option <?=$CMSNT->site('affiliate_order_status') == 0 ? 'selected' : '';?> value="0"><?=__('Tắt');?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Tỷ lệ hoa hồng');?></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" value="<?=$CMSNT->site('affiliate_order_ck') ?: 0;?>" name="affiliate_order_ck" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <small class="text-muted"><?=__('Khi thành viên được giới thiệu mua sản phẩm');?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Rút tiền -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-warning mb-3">
                                        <i class="ti ti-wallet me-2"></i><?=__('Cấu hình rút tiền');?>
                                    </h6>
                                </div>
                                
                                <div class="col-lg-6 col-xl-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Số tiền rút tối thiểu');?></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" value="<?=$CMSNT->site('affiliate_min') ?: 100000;?>" name="affiliate_min" min="0">
                                            <span class="input-group-text"><?=__('VNĐ');?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 col-xl-8">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Danh sách ngân hàng hỗ trợ');?></label>
                                        <textarea class="form-control" rows="4" placeholder="<?=__('Mỗi dòng 1 ngân hàng');?>" name="affiliate_banks"><?=$CMSNT->site('affiliate_banks');?></textarea>
                                        <small class="text-muted"><?=__('Mỗi dòng 1 ngân hàng. Ví dụ: Vietcombank, Techcombank...');?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Thông báo -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-danger mb-3">
                                        <i class="ti ti-bell me-2"></i><?=__('Cấu hình thông báo');?>
                                    </h6>
                                </div>
                                
                                <div class="col-lg-12 col-xl-6">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Chat ID Telegram nhận thông báo rút tiền');?></label>
                                        <input type="text" class="form-control" value="<?=$CMSNT->site('affiliate_chat_id_telegram');?>" name="affiliate_chat_id_telegram" placeholder="<?=__('Nhập Chat ID Telegram');?>">
                                        <small class="text-muted"><?=__('Để nhận thông báo khi có yêu cầu rút tiền mới');?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Lưu ý -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="fw-semibold text-secondary mb-3">
                                        <i class="ti ti-info-circle me-2"></i><?=__('Nội dung hướng dẫn');?>
                                    </h6>
                                </div>
                                
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label"><?=__('Lưu ý/Hướng dẫn cho thành viên');?></label>
                                        <textarea id="affiliate_note" name="affiliate_note"><?=$CMSNT->site('affiliate_note');?></textarea>
                                        <small class="text-muted"><?=__('Nội dung này sẽ hiển thị ở trang affiliate của khách hàng');?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a type="button" class="btn btn-outline-danger" href="">
                                    <i class="fa fa-fw fa-undo me-1"></i><?=__('Tải lại');?>
                                </a>
                                <button type="submit" name="SaveSettings" class="btn btn-primary">
                                    <i class="fa fa-fw fa-save me-1"></i> <?=__('Lưu cấu hình');?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hướng dẫn -->
        <div class="row mt-4">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ti ti-help me-2"></i><?=__('Hướng dẫn sử dụng');?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded mb-3">
                                    <h6 class="fw-semibold"><i class="ti ti-number-1 me-2 text-primary"></i><?=__('Hoa hồng nạp tiền');?></h6>
                                    <p class="mb-0 text-muted small"><?=__('Khi thành viên được giới thiệu nạp tiền vào tài khoản, người giới thiệu sẽ nhận được % hoa hồng từ số tiền nạp.');?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded mb-3">
                                    <h6 class="fw-semibold"><i class="ti ti-number-2 me-2 text-success"></i><?=__('Hoa hồng đơn hàng');?></h6>
                                    <p class="mb-0 text-muted small"><?=__('Khi thành viên được giới thiệu mua sản phẩm/dịch vụ, người giới thiệu sẽ nhận được % hoa hồng từ giá trị đơn hàng.');?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded mb-3">
                                    <h6 class="fw-semibold"><i class="ti ti-number-3 me-2 text-info"></i><?=__('Rút tiền hoa hồng');?></h6>
                                    <p class="mb-0 text-muted small"><?=__('Thành viên có thể rút số dư hoa hồng khi đạt mức tối thiểu. Admin sẽ xử lý yêu cầu rút tiền.');?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__.'/footer.php');
?>

<script>
CKEDITOR.replace("affiliate_note");
</script>
