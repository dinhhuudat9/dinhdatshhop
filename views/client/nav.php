<?php if (!defined('IN_SITE')) {
    die('The Request Not Found');
} ?>

<body>
    <div class="backdrop"></div><a class="backtop" href="#"><i class="fa-sharp fa-solid fa-chevron-up"></i></a>
    <div class="header-top">
        <div class="container">
            <div class="header-top-inner">
                <!-- Left: Quick Links -->
                <div class="header-top-left">
                    <ul class="header-top-links">
                        <li><a href="<?= base_url('client/policy'); ?>"><?= __('Chính sách'); ?></a></li>
                        <li><a href="<?= base_url('client/faq'); ?>"><?= __('FAQ'); ?></a></li>
                        <li><a href="<?= base_url('client/contact'); ?>"><?= __('Liên Hệ'); ?></a></li>
                    </ul>
                </div>

                <!-- Right: Language & Currency -->
                <div class="header-top-right">
                    <div class="header-top-actions">
                        <?php if ($CMSNT->site('language_type') == 'manual'): ?>
                            <div class="header-dropdown">
                                <button class="header-dropdown-btn" type="button" id="langDropdownBtn">
                                    <i class="fa-solid fa-globe"></i>
                                    <span id="currentLang"><?= getLanguage(); ?></span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="header-dropdown-menu" id="langDropdownMenu">
                                    <?php foreach (get_languages_cached() as $lang): ?>
                                        <a href="javascript:void(0)" class="header-dropdown-item <?= getLanguage() == $lang['lang'] ? 'active' : ''; ?>"
                                            onclick="setLanguage(<?= $lang['id']; ?>)"><?= $lang['lang']; ?></a>
                                    <?php endforeach; ?>
                                </div>
                                <select class="select" id="changeLanguage" onchange="changeLanguage()" style="display:none;">
                                    <?php foreach (get_languages_cached() as $lang): ?>
                                        <option value="<?= $lang['id']; ?>" <?= getLanguage() == $lang['lang'] ? 'selected' : ''; ?>><?= $lang['lang']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php elseif ($CMSNT->site('language_type') == 'gtranslate'): ?>
                            <?= $CMSNT->site('gtranslate_script'); ?>
                        <?php endif ?>

                        <div class="header-dropdown">
                            <button class="header-dropdown-btn" type="button" id="currencyDropdownBtn">
                                <i class="fa-solid fa-coins"></i>
                                <span id="currentCurrency"><?php
                                                            $currencies = get_currencies_cached();
                                                            foreach ($currencies as $c) {
                                                                if ($c['id'] == getCurrency()) echo $c['code'];
                                                            }
                                                            ?></span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <div class="header-dropdown-menu" id="currencyDropdownMenu">
                                <?php foreach (get_currencies_cached() as $currency): ?>
                                    <a href="javascript:void(0)" class="header-dropdown-item <?= getCurrency() == $currency['id'] ? 'active' : ''; ?>"
                                        onclick="setCurrency(<?= $currency['id']; ?>)"><?= $currency['code']; ?></a>
                                <?php endforeach; ?>
                            </div>
                            <select class="select" id="changeCurrency" onchange="changeCurrency()" style="display:none;">
                                <?php foreach (get_currencies_cached() as $currency): ?>
                                    <option value="<?= $currency['id']; ?>" <?= getCurrency() == $currency['id'] ? 'selected' : ''; ?>><?= $currency['code']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button class="theme-toggle-btn" id="themeToggleBtn" type="button" title="<?= __('Chế độ tối/sáng'); ?>">
                            <i class="fa-solid fa-sun theme-icon-light"></i>
                            <i class="fa-solid fa-moon theme-icon-dark"></i>
                        </button>

                        <!-- Mobile Cart Button -->
                        <a href="<?= base_url('cart'); ?>" class="header-cart-btn d-lg-none" title="<?= __('Giỏ hàng'); ?>">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <sup class="header-cart-badge numCart" style="display:none;">0</sup>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <header class="header-part">
        <div class="container">
            <div class="header-content">
                <div class="header-media-group">
                    <button class="header-user"><i class="fa-solid fa-bars"></i></button>
                    <a href="<?= base_url(); ?>">
                        <img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>" alt="logo" class="logo-light">
                        <?php if ($CMSNT->site('logo_dark')): ?>
                            <img src="<?= BASE_URL($CMSNT->site('logo_dark')); ?>" alt="logo" class="logo-dark">
                        <?php endif; ?>
                    </a>
                    <button class="header-src"><i class="fas fa-search"></i></button>
                </div>
                <a href="<?= base_url(); ?>" class="header-logo">
                    <img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>" alt="logo" class="logo-light">
                    <?php if ($CMSNT->site('logo_dark')): ?>
                        <img src="<?= BASE_URL($CMSNT->site('logo_dark')); ?>" alt="logo" class="logo-dark">
                    <?php endif; ?>
                </a>
                <div class="header-search-wrapper">
                    <form class="header-form" method="GET" action="<?= base_url('products'); ?>" autocomplete="off">
                        <input type="text"
                            name="keyword"
                            id="searchInput"
                            value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>"
                            placeholder="<?= __('Tìm kiếm sản phẩm...'); ?>"
                            autocomplete="off"
                            data-ajax-url="<?= base_url('ajaxs/client/view.php'); ?>"
                            data-products-url="<?= base_url('products'); ?>"
                            data-no-results="<?= __('Không tìm thấy sản phẩm nào'); ?>"
                            data-view-all="<?= __('Xem tất cả kết quả'); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    <div class="search-autocomplete" id="searchAutocomplete"></div>
                </div>
                <div class="header-widget-group">
                    <a href="<?= base_url('cart'); ?>" class="header-widget" title="<?= __('Giỏ hàng'); ?>">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <sup id="numCart" style="display:none;">0</sup>
                    </a>
                    <?php
                    // Đếm số sản phẩm yêu thích của user
                    $favorites_count = 0;
                    if (isset($getUser) && $getUser) {
                        $favorites_count = (int)$CMSNT->num_rows_safe(
                            "SELECT 1 FROM `product_favorites` WHERE `user_id` = ?",
                            [$getUser['id']]
                        );
                    }
                    ?>
                    <a href="<?= base_url('client/favorites'); ?>" class="header-widget"
                        title="<?= __('Sản phẩm yêu thích'); ?>">
                        <i class="fas fa-heart"></i>
                        <sup id="numFavorites" style="<?= $favorites_count > 0 ? '' : 'display:none;'; ?>"><?= $favorites_count; ?></sup>
                    </a>
                    <?php
                    // Đếm số ticket có tin nhắn mới (admin đã trả lời)
                    $unread_tickets = 0;
                    if (isset($getUser) && $getUser) {
                        $unread_tickets = (int)$CMSNT->num_rows_safe(
                            "SELECT 1 FROM `support_tickets` WHERE `user_id` = ? AND `status` = 'answered'",
                            [$getUser['id']]
                        );
                    }
                    ?>
                    <a href="<?= base_url('client/support-tickets'); ?>" class="header-widget" title="<?= __('Hỗ trợ'); ?>">
                        <i class="fa-solid fa-message"></i>
                        <sup id="numTickets" style="<?= $unread_tickets > 0 ? '' : 'display:none;'; ?>"><?= $unread_tickets; ?></sup>
                    </a>
                    <?php if (isset($getUser)): ?>
                        <a href="<?= base_url('client/profile'); ?>" class="header-widget" title="Profile">
                            <img src="<?= getGravatarUrl($getUser['email']); ?>" alt="user"><span>
                                <p class="text-uppercase"><?= $getUser['username']; ?></p>
                                <p style="color:blue;"><?= format_currency($getUser['money']); ?></p>
                            </span>
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('client/login'); ?>" class="header-widget" title="Login">
                            <img src="<?= BASE_URL($CMSNT->site('avatar')); ?>" alt="user"><span>Login</span>
                        </a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </header>
    <nav class="navbar-part">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="sknav">
                        <!-- Main Navigation -->
                        <nav class="sknav-main">
                            <ul class="sknav-menu">
                                <!-- Menu Sản phẩm với Megamenu -->
                                <li class="sknav-item has-megamenu">
                                    <a class="sknav-link" href="<?= base_url('products'); ?>">
                                        <i class="fas fa-th-large"></i>
                                        <span><?= __('Sản phẩm'); ?></span>
                                        <i class="fas fa-chevron-down sknav-arrow"></i>
                                    </a>
                                    <div class="sknav-megamenu">
                                        <div class="sknav-megamenu-inner">
                                            <div id="menu-categories-container">
                                                <!-- Skeleton loading -->
                                                <div class="menu-skeleton">
                                                    <div class="neo-megamenu-grid">
                                                        <?php for ($i = 0; $i < 3; $i++): ?>
                                                            <div class="neo-megamenu-col">
                                                                <div class="skeleton-menu-title"></div>
                                                                <ul class="neo-megamenu-list">
                                                                    <li>
                                                                        <div class="skeleton-menu-item"></div>
                                                                    </li>
                                                                    <li>
                                                                        <div class="skeleton-menu-item"></div>
                                                                    </li>
                                                                    <li>
                                                                        <div class="skeleton-menu-item"></div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <!-- Menu Nạp tiền -->
                                <li class="sknav-item has-dropdown">
                                    <a class="sknav-link sknav-link-hot" href="#">
                                        <i class="fas fa-wallet"></i>
                                        <span><?= __('Nạp tiền'); ?></span>
                                        <span class="sknav-badge">HOT</span>
                                        <i class="fas fa-chevron-down sknav-arrow"></i>
                                    </a>
                                    <div class="sknav-dropdown">
                                        <div class="sknav-dropdown-grid">
                                            <?php if ($CMSNT->site('bank_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-bank'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('assets/img/icon-bank.svg'); ?>" alt="Bank">
                                                    <span><?= __('Ngân hàng'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('card_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-card'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('assets/img/icon-cards.png'); ?>" alt="Card">
                                                    <span><?= __('Thẻ cào'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('crypto_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-crypto'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('assets/img/icon-usdt.svg'); ?>" alt="Crypto">
                                                    <span><?= __('Crypto'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('paypal_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-paypal'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('assets/img/icon-paypal.svg'); ?>" alt="Paypal">
                                                    <span><?= __('PayPal'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('gateway_xipay_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-xipay'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('mod/img/logo-xipay.webp'); ?>" alt="Xipay">
                                                    <span><?= __('AliPay'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('korapay_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-korapay'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('mod/img/logo-korapay.webp'); ?>" alt="Korapay">
                                                    <span><?= __('Korapay'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('tmweasyapi_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-tmweasyapi'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('mod/img/icon-tmweasyapi.webp'); ?>" alt="TMW">
                                                    <span><?= __('TMW'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('openpix_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-openpix'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('mod/img/icon-openpix.webp'); ?>" alt="OpenPix">
                                                    <span><?= __('PIX'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php if ($CMSNT->site('bakong_status') == 1): ?>
                                                <a href="<?= base_url('?action=recharge-bakong'); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url('mod/img/icon-bakong.webp'); ?>" alt="Bakong">
                                                    <span><?= __('Bakong'); ?></span>
                                                </a>
                                            <?php endif ?>
                                            <?php foreach (get_payment_manual_cached() as $payment_manual): ?>
                                                <a href="<?= base_url('recharge-manual/' . $payment_manual['slug']); ?>" class="sknav-pay-item">
                                                    <img src="<?= base_url($payment_manual['icon']); ?>" alt="">
                                                    <span><?= __($payment_manual['title']); ?></span>
                                                </a>
                                            <?php endforeach ?>
                                        </div>
                                    </div>
                                </li>

                                <!-- Menu Đơn hàng -->
                                <li class="sknav-item">
                                    <a class="sknav-link" href="<?= base_url('product-orders'); ?>">
                                        <i class="fas fa-shopping-bag"></i>
                                        <span><?= __('Đơn hàng'); ?></span>
                                    </a>
                                </li>

                                <!-- Menu Blogs -->
                                <?php if ($CMSNT->site('blog_status') == 1): ?>
                                    <li class="sknav-item">
                                        <a class="sknav-link" href="<?= base_url('blogs'); ?>">
                                            <i class="fas fa-newspaper"></i>
                                            <span><?= __('Blogs'); ?></span>
                                        </a>
                                    </li>
                                <?php endif ?>

                                <!-- Menu Admin -->
                                <?php if (isset($getUser) && $getUser['admin'] != 0): ?>
                                    <li class="sknav-item">
                                        <a class="sknav-link sknav-link-admin" href="<?= base_url_admin(); ?>">
                                            <i class="fas fa-shield-alt"></i>
                                            <span><?= __('Admin'); ?></span>
                                        </a>
                                    </li>
                                <?php endif ?>
                            </ul>
                        </nav>

                        <!-- Contact Info -->
                        <div class="sknav-contact">
                            <a href="tel:<?= $CMSNT->site('hotline'); ?>" class="sknav-contact-item">
                                <i class="fas fa-headset"></i>
                                <div>
                                    <small><?= __('HOTLINE 24/7'); ?></small>
                                    <strong><?= $CMSNT->site('hotline'); ?></strong>
                                </div>
                            </a>
                            <a href="mailto:<?= $CMSNT->site('email'); ?>" class="sknav-contact-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <small><?= __('EMAIL'); ?></small>
                                    <strong><?= $CMSNT->site('email'); ?></strong>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <aside class="category-sidebar">
        <div class="category-header">
            <h4 class="category-title"><i class="fas fa-align-left"></i><span><?= __('Sản phẩm'); ?></span></h4><button
                class="category-close"><i class="icofont-close"></i></button>
        </div>
        <!--menu mobile-->
        <ul class="category-list">
            <?php foreach (get_categories_parent_cached() as $nav_category): ?>
                <li class="category-item">
                    <a class="category-link dropdown-link" href="#">
                        <img src="<?= base_url($nav_category['icon']); ?>" style="margin-right: 10px;" width="30px">
                        <?= __($nav_category['name']); ?> </a>
                    <ul class="dropdown-list">
                        <?php foreach (get_categories_by_parent_cached($nav_category['id']) as $nav_subcategory): ?>
                            <li><a href="<?= base_url('category/' . $nav_subcategory['slug']); ?>"><img src="<?= base_url($nav_subcategory['icon']); ?>" style="margin-right: 8px;" width="20px"><?= __($nav_subcategory['name']); ?></a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </li>
            <?php endforeach ?>
        </ul>
    </aside>
    <aside class="cart-sidebar recharge-sidebar">
        <div class="cart-header recharge-sidebar-header">
            <div class="recharge-header-content">
                <div class="recharge-header-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div class="recharge-header-text">
                    <h3><?= __('Nạp tiền'); ?></h3>
                    <p><?= __('Chọn phương thức thanh toán'); ?></p>
                </div>
            </div>
            <button class="cart-close recharge-close-btn"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="recharge-sidebar-body">
            <ul class="category-list recharge-methods-list">
                <?php if ($CMSNT->site('bank_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-bank'); ?>">
                            <div class="recharge-method-icon bank">
                                <img src="<?= base_url('assets/img/icon-bank.svg'); ?>" alt="Bank">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Ngân hàng'); ?></span>
                                <span class="recharge-method-desc"><?= __('Chuyển khoản nội địa'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('card_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-card'); ?>">
                            <div class="recharge-method-icon card">
                                <img src="<?= base_url('assets/img/icon-cards.png'); ?>" alt="Card">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Thẻ cào'); ?></span>
                                <span class="recharge-method-desc"><?= __('Viettel, Mobi, Vina...'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('crypto_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-crypto'); ?>">
                            <div class="recharge-method-icon crypto">
                                <img src="<?= base_url('assets/img/icon-usdt.svg'); ?>" alt="Crypto">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Crypto'); ?></span>
                                <span class="recharge-method-desc"><?= __('USDT, BTC, ETH...'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('paypal_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-paypal'); ?>">
                            <div class="recharge-method-icon paypal">
                                <img src="<?= base_url('assets/img/icon-paypal.svg'); ?>" alt="Paypal">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Paypal'); ?></span>
                                <span class="recharge-method-desc"><?= __('Thanh toán quốc tế'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('gateway_xipay_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-xipay'); ?>">
                            <div class="recharge-method-icon xipay">
                                <img src="<?= base_url('mod/img/logo-xipay.webp'); ?>" alt="XiPay">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('AliPay & WeChat Pay'); ?></span>
                                <span class="recharge-method-desc"><?= __('Ví điện tử Trung Quốc'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('korapay_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-korapay'); ?>">
                            <div class="recharge-method-icon korapay">
                                <img src="<?= base_url('mod/img/logo-korapay.webp'); ?>" alt="Korapay">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Korapay Africa'); ?></span>
                                <span class="recharge-method-desc"><?= __('Thanh toán châu Phi'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('tmweasyapi_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-tmweasyapi'); ?>">
                            <div class="recharge-method-icon tmweasyapi">
                                <img src="<?= base_url('mod/img/icon-tmweasyapi.webp'); ?>" alt="TMW">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Tmweasyapi Thailand'); ?></span>
                                <span class="recharge-method-desc"><?= __('Thanh toán Thái Lan'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('openpix_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-openpix'); ?>">
                            <div class="recharge-method-icon openpix">
                                <img src="<?= base_url('mod/img/icon-openpix.webp'); ?>" alt="OpenPix">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('OpenPix'); ?></span>
                                <span class="recharge-method-desc"><?= __('PIX Brazil'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php if ($CMSNT->site('bakong_status') == 1): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('?action=recharge-bakong'); ?>">
                            <div class="recharge-method-icon bakong">
                                <img src="<?= base_url('mod/img/icon-bakong.webp'); ?>" alt="Bakong">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __('Bakong Wallet Cambodia'); ?></span>
                                <span class="recharge-method-desc"><?= __('Ví điện tử Campuchia'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endif ?>
                <?php foreach (get_payment_manual_cached() as $payment_manual): ?>
                    <li class="category-item recharge-method-item">
                        <a class="category-link recharge-method-link" href="<?= base_url('recharge-manual/' . $payment_manual['slug']); ?>">
                            <div class="recharge-method-icon manual">
                                <img src="<?= base_url($payment_manual['icon']); ?>" alt="<?= htmlspecialchars($payment_manual['title']); ?>">
                            </div>
                            <div class="recharge-method-info">
                                <span class="recharge-method-name"><?= __($payment_manual['title']); ?></span>
                                <span class="recharge-method-desc"><?= __('Nạp thủ công'); ?></span>
                            </div>
                            <div class="recharge-method-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
        <div class="recharge-sidebar-footer">
            <div class="recharge-footer-info">
                <i class="fa-solid fa-shield-halved"></i>
                <span><?= __('Giao dịch an toàn & bảo mật'); ?></span>
            </div>
        </div>
    </aside>
    <aside class="nav-sidebar">
        <div class="nav-header">
            <a href="<?= base_url(); ?>">
                <img src="<?= BASE_URL($CMSNT->site('logo_light')); ?>" alt="logo" class="logo-light">
                <?php if ($CMSNT->site('logo_dark')): ?>
                    <img src="<?= BASE_URL($CMSNT->site('logo_dark')); ?>" alt="logo" class="logo-dark">
                <?php endif; ?>
            </a>
            <button class="nav-close"><i class="icofont-close"></i></button>
        </div>
        <div class="nav-content">
            <div class="nav-btn">
                <?php if (isset($getUser)): ?>
                    <a href="<?= base_url('client/profile'); ?>" class="btn btn-inline">
                        <i class="fa fa-user"></i> <span><?= $getUser['username']; ?></span></a>
                <?php else: ?>
                    <a href="<?= base_url('client/login'); ?>" class="btn btn-inline">
                        <i class="fa fa-unlock-alt"></i> <span><?= __('Đăng Nhập'); ?></span></a>
                <?php endif ?>

            </div>
            <div class="nav-select-group">
                <p><?= __('Số dư của tôi:'); ?> <strong
                        class="text-wallet"><?= isset($getUser) ? format_currency($getUser['money']) : 0; ?></strong></p>
            </div>
            <ul class="nav-list">
                <li><a class="nav-link" href="<?= base_url(); ?>"><i
                            class="icofont-home"></i><?= __('Trang chủ'); ?></a></li>
                <li><a class="nav-link dropdown-link" href="#"><i
                            class="fa-solid fa-cart-shopping"></i><?= __('Sản phẩm'); ?></a>
                    <ul class="dropdown-list">
                        <?php foreach (get_categories_not_parent_cached() as $nav_category): ?>
                            <li><a href="<?= base_url('category/' . $nav_category['slug']); ?>"><img width="25px"
                                        class="me-2 active" src="<?= base_url($nav_category['icon']); ?>">
                                    <?= __($nav_category['name']); ?></a></li>
                        <?php endforeach ?>
                    </ul>
                </li>
                <li><a class="nav-link dropdown-link" href="#"><i
                            class="fa-solid fa-building-columns"></i><?= __('Nạp tiền'); ?></a>
                    <ul class="dropdown-list">
                        <?php if ($CMSNT->site('bank_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-bank'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-bank.svg'); ?>">
                                    <?= __('Ngân hàng'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('card_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-card'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-cards.png'); ?>">
                                    <?= __('Thẻ cào'); ?></a>
                            </li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('crypto_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-crypto'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-usdt.svg'); ?>"> <?= __('Crypto'); ?></a>
                            </li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('paypal_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-paypal'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('assets/img/icon-paypal.svg'); ?>">
                                    <?= __('Paypal'); ?></a></li>
                        <?php endif ?>


                        <?php if ($CMSNT->site('gateway_xipay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-xipay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/logo-xipay.webp'); ?>">
                                    <?= __('AliPay & WeChat Pay'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('korapay_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-korapay'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/logo-korapay.webp'); ?>">
                                    <?= __('Korapay Africa'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('tmweasyapi_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-tmweasyapi'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-tmweasyapi.webp'); ?>">
                                    <?= __('Tmweasyapi Thailand'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('openpix_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-openpix'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-openpix.webp'); ?>">
                                    <?= __('OpenPix'); ?></a></li>
                        <?php endif ?>
                        <?php if ($CMSNT->site('bakong_status') == 1): ?>
                            <li><a href="<?= base_url('?action=recharge-bakong'); ?>"><img width="20px" class="me-2"
                                        src="<?= base_url('mod/img/icon-bakong.webp'); ?>">
                                    <?= __('Bakong Wallet Cambodia'); ?></a></li>
                        <?php endif ?>
                        <?php foreach (get_payment_manual_cached() as $payment_manual): ?>
                            <li><a href="<?= base_url('recharge-manual/' . $payment_manual['slug']); ?>"><img width="20px"
                                        class="me-2" src="<?= base_url($payment_manual['icon']); ?>">
                                    <?= __($payment_manual['title']); ?></a></li>
                        <?php endforeach ?>
                    </ul>
                </li>
                <li><a class="nav-link dropdown-link" href="#"><i
                            class="fa-solid fa-clock-rotate-left"></i><?= __('Lịch sử'); ?></a>
                    <ul class="dropdown-list">
                        <li><a href="<?= base_url('product-orders/'); ?>"><?= __('Lịch sử đơn hàng'); ?></a>
                        </li>
                        <li><a href="<?= base_url('client/logs'); ?>"><?= __('Nhật ký hoạt động'); ?></a></li>
                        <li><a href="<?= base_url('client/transactions'); ?>"><?= __('Biến động số dư'); ?></a>
                        </li>
                    </ul>
                </li>
                <?php if ($CMSNT->site('blog_status') == 1): ?>
                    <li><a class="nav-link" href="<?= base_url('blogs'); ?>"><i
                                class="fa-solid fa-newspaper"></i><?= __('Blogs'); ?></a></li>
                <?php endif ?>
                <?php if ($CMSNT->site('api_status') == 1): ?>
                    <li><a class="nav-link" href="<?= base_url('document-api'); ?>"><i
                                class="fa-regular fa-file-code"></i><?= __('Tài liệu API'); ?></a></li>
                <?php endif ?>
                <?php if (isset($getUser) && $getUser['admin'] != 0): ?>
                    <li><a class="nav-link" href="<?= base_url_admin(); ?>"><i
                                class="fa-solid fa-gear"></i><?= __('Admin Panel'); ?></a></li>
                <?php endif ?>
                <li><a class="nav-link" href="<?= base_url('client/logout'); ?>"><i
                            class="icofont-logout"></i><?= __('Đăng xuất'); ?></a></li>
            </ul>
            <div class="nav-info-group">
                <div class="nav-info"><?= $CMSNT->site('icon_hotline'); ?>
                    <p><span><?= $CMSNT->site('hotline'); ?></span></p>
                </div>
                <div class="nav-info"><?= $CMSNT->site('icon_email'); ?>
                    <p><span><?= $CMSNT->site('email'); ?></span></p>
                </div>
            </div>
        </div>
    </aside>
    <div class="mobile-menu">
        <a href="<?= base_url(); ?>" title="<?= __('Trang chủ'); ?>"
            class="<?= active_sidebar_client(['home', '']); ?>"><i
                class="fas fa-home"></i><span><?= __('Trang chủ'); ?></span></a>
        <button class="cate-btn" title="<?= __('Sản phẩm'); ?>"><i
                class="fas fa-list"></i><span><?= __('Sản phẩm'); ?></span></button>
        <button
            class="cart-btn <?= active_sidebar_client(['recharge-bank', 'recharge-crypto', 'recharge-card', 'recharge-paypal', 'recharge-perfectmoney', 'recharge-manual']); ?>"
            title="<?= __('Nạp tiền'); ?>"><i
                class="fa-solid fa-building-columns"></i><span><?= __('Nạp tiền'); ?></span></button>
        <a href="<?= base_url('product-orders'); ?>"
            class="<?= active_sidebar_client(['product-orders', 'product-order']); ?>" title="<?= __('Đơn hàng'); ?>"><i
                class="fa-solid fa-cart-shopping"></i><span><?= __('Đơn hàng'); ?></span></a>
        <a href="<?= base_url('client/profile'); ?>" title="Profile" class="<?= active_sidebar_client(['profile']); ?>"><i
                class="fa-solid fa-user"></i><span><?= __('Thông tin'); ?></span></a>
    </div>