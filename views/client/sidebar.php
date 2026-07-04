<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
}?>

<!-- Sidebar Navigation -->
<div class="client-sidebar">
    <!-- User Mini Profile -->
    <div class="sidebar-user-card">
        <div class="sidebar-user-avatar">
            <img src="<?=getGravatarUrl($getUser['email']);?>" alt="<?=htmlspecialchars($getUser['username']);?>">
            <span class="user-status-dot"></span>
        </div>
        <div class="sidebar-user-info">
            <h6 class="sidebar-user-name"><?=htmlspecialchars($getUser['username']);?></h6>
            <span class="sidebar-user-balance"><?=format_currency($getUser['money']);?></span>
        </div>
    </div>

    <!-- Menu Items -->
    <div class="sidebar-menu">
        <div class="sidebar-menu-label"><?=__('Tài khoản');?></div>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['profile']);?>" href="<?=base_url('client/profile');?>">
            <i class="fa-solid fa-user"></i>
            <span><?=__('Thông tin cá nhân');?></span>
        </a>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['security']);?>" href="<?=base_url('client/security');?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span><?=__('Bảo mật');?></span>
        </a>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['change-password']);?>" href="<?=base_url('client/change-password');?>">
            <i class="fa-solid fa-lock"></i>
            <span><?=__('Thay đổi mật khẩu');?></span>
        </a>

        <div class="sidebar-menu-label"><?=__('Giao dịch');?></div>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['product-orders', 'product-order']);?>" href="<?=base_url('product-orders');?>">
            <i class="fa-solid fa-receipt"></i>
            <span><?=__('Đơn hàng của tôi');?></span>
        </a>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['transactions']);?>" href="<?=base_url('?action=transactions');?>">
            <i class="fa-solid fa-wallet"></i>
            <span><?=__('Biến động số dư');?></span>
        </a>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['logs']);?>" href="<?=base_url('?action=logs');?>">
            <i class="fa-solid fa-history"></i>
            <span><?=__('Nhật ký hoạt động');?></span>
        </a>

        <div class="sidebar-menu-label"><?=__('Tiện ích');?></div>
        
        <a class="sidebar-menu-item <?=active_sidebar_client(['favorites']);?>" href="<?=base_url('client/favorites');?>">
            <i class="fa-solid fa-heart"></i>
            <span><?=__('Sản phẩm yêu thích');?></span>
        </a>

        <?php if($CMSNT->site('support_tickets_status') == 1):?>
        <a class="sidebar-menu-item <?=active_sidebar_client(['support-tickets', 'ticket-detail']);?>" href="<?=base_url('client/support-tickets');?>">
            <i class="fa-solid fa-headset"></i>
            <span><?=__('Yêu cầu hỗ trợ');?></span>
            <?php
            $pending_tickets = $CMSNT->get_row_safe("SELECT COUNT(id) as total FROM `support_tickets` WHERE `user_id` = ? AND `status` IN ('open', 'pending')", [$getUser['id']]);
            $ticket_count = $pending_tickets ? (int)$pending_tickets['total'] : 0;
            if($ticket_count > 0):
            ?>
            <span class="menu-badge"><?=$ticket_count;?></span>
            <?php endif; ?>
        </a>
        <?php endif?>

        <?php if($CMSNT->site('affiliate_status') == 1):?>
        <a class="sidebar-menu-item <?=active_sidebar_client(['affiliates', 'affiliate-history', 'affiliate-withdraw']);?>" href="<?=base_url('?action=affiliates');?>">
            <i class="fa-solid fa-handshake"></i>
            <span><?=__('Giới thiệu bạn bè');?></span>
        </a>
        <?php endif?>
        
        <?php if($CMSNT->site('api_user_enabled') == 1):?>
        <a class="sidebar-menu-item <?=active_sidebar_client(['api-keys']);?>" href="<?=base_url('client/api-keys');?>">
            <i class="fa-solid fa-code"></i>
            <span><?=__('API Keys');?></span>
        </a>
        <?php endif?>
    </div>

    <!-- Logout Button -->
    <div class="sidebar-footer">
        <a class="sidebar-logout-btn" onclick="logout()" href="javascript:void(0)">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span><?=__('Đăng xuất');?></span>
        </a>
    </div>
</div>

<script type="text/javascript">
function logout() {
    Swal.fire({
        title: '<?=__('Bạn có chắc không?');?>',
        text: "<?=__('Bạn sẽ bị đăng xuất khỏi tài khoản khi nhấn Đồng Ý');?>",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<?=__('Đồng ý');?>',
        cancelButtonText: '<?=__('Huỷ bỏ');?>'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location = "<?=base_url('client/logout');?>";
        }
    })
}
</script>