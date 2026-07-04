// Validate form
document.addEventListener('DOMContentLoaded', function() {
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Khởi tạo Payment Gateways Modal
    if (document.getElementById('paymentGatewaysModal')) {
        window.paymentGatewaysModal = new bootstrap.Modal(document.getElementById('paymentGatewaysModal'));
    }
});

// Payment Gateways Modal Functions
window.showPaymentGatewaysModal = function() {
    // Hiển thị modal
    window.paymentGatewaysModal.show();
    
    // Reset trạng thái
    document.getElementById('paymentGatewaysLoader').classList.remove('d-none');
    document.getElementById('paymentGatewaysList').classList.add('d-none');
    document.getElementById('paymentGatewaysError').classList.add('d-none');
    
    // Gọi AJAX để lấy danh sách cổng thanh toán
    $.ajax({
        url: window.AJAX_URLS.domain + 'ajaxs/client/recharge.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getActivePaymentGateways'
        },
        success: function(response) {
            if (response.status === 'success' && response.data.length > 0) {
                displayPaymentGateways(response.data);
            } else {
                showNoGatewaysMessage();
            }
        },
        error: function() {
            showErrorMessage();
        }
    });
};

// Hiển thị danh sách cổng thanh toán
function displayPaymentGateways(gateways) {
    const listContainer = document.getElementById('paymentGatewaysList');
    let html = '';
    
    gateways.forEach(function(gateway) {
        html += `
            <div class="pg-gateway-card" onclick="selectPaymentGateway('${gateway.url}')">
                <div class="pg-gateway-content">
                    <div class="pg-gateway-icon bg-${gateway.color}">
                        <i class="${gateway.icon}"></i>
                    </div>
                    <div class="pg-gateway-info">
                        <h6 class="pg-gateway-name">${gateway.name}</h6>
                        <p class="pg-gateway-desc">${gateway.description}</p>
                    </div>
                    <div class="pg-gateway-arrow">
                        <i class="ri-arrow-right-s-line"></i>
                    </div>
                </div>
            </div>
        `;
    });
    
    listContainer.innerHTML = html;
    
    // Ẩn loader và hiển thị danh sách
    document.getElementById('paymentGatewaysLoader').classList.add('d-none');
    document.getElementById('paymentGatewaysList').classList.remove('d-none');
}

// Chọn cổng thanh toán
window.selectPaymentGateway = function(url) {
    // Đóng modal
    window.paymentGatewaysModal.hide();
    
    // Chuyển đến trang cổng thanh toán
    setTimeout(function() {
        window.location.href = url;
    }, 0);
};

// Hiển thị thông báo không có cổng thanh toán nào
function showNoGatewaysMessage() {
    const listContainer = document.getElementById('paymentGatewaysList');
    listContainer.innerHTML = `
        <div class="pg-empty">
            <div class="pg-empty-content">
                <div class="pg-empty-icon">
                    <i class="ri-wallet-line"></i>
                </div>
                <h6 class="pg-empty-title">${window.TRANSLATIONS.no_payment_gateways}</h6>
                <p class="pg-empty-text">${window.TRANSLATIONS.no_payment_gateways_desc}</p>
            </div>
        </div>
    `;
    
    document.getElementById('paymentGatewaysLoader').classList.add('d-none');
    document.getElementById('paymentGatewaysList').classList.remove('d-none');
}

// Hiển thị thông báo lỗi
function showErrorMessage() {
    document.getElementById('paymentGatewaysLoader').classList.add('d-none');
    document.getElementById('paymentGatewaysError').classList.remove('d-none');
}

// ===== CSRF PROTECTION =====
// Tự động thêm CSRF token vào tất cả AJAX POST requests
$(document).ready(function() {
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            // Chỉ thêm CSRF token cho POST requests
            if (settings.type && settings.type.toUpperCase() === 'POST') {
                // Lấy CSRF token từ meta tag
                var csrfToken = getCSRFToken();
                
                // Nếu data là string (form serialized)
                if (typeof settings.data === 'string') {
                    settings.data += (settings.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(csrfToken);
                }
                // Nếu data là FormData
                else if (settings.data instanceof FormData) {
                    settings.data.append('csrf_token', csrfToken);
                }
                // Nếu data là object
                else if (typeof settings.data === 'object' && settings.data !== null) {
                    settings.data.csrf_token = csrfToken;
                }
                // Nếu không có data
                else if (!settings.data) {
                    settings.data = 'csrf_token=' + encodeURIComponent(csrfToken);
                }
            }
        }
    });
});

// ========================================
// Header Dropdown (Language & Currency)
// ========================================
(function() {
    'use strict';
    
    let langBtn, langMenu, currencyBtn, currencyMenu;
    let scrollTimer, resizeTimer;
    
    // Function to position dropdown menu
    function positionDropdown(btn, menu) {
        if (!btn || !menu) return;
        
        const rect = btn.getBoundingClientRect();
        menu.style.top = (rect.bottom + window.scrollY + 4) + 'px';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
    }
    
    // Reposition all open dropdowns
    function repositionDropdowns() {
        if (langMenu?.classList.contains('show')) {
            positionDropdown(langBtn, langMenu);
        }
        if (currencyMenu?.classList.contains('show')) {
            positionDropdown(currencyBtn, currencyMenu);
        }
    }
    
    // Initialize dropdowns
    function initDropdowns() {
        langBtn = document.getElementById('langDropdownBtn');
        langMenu = document.getElementById('langDropdownMenu');
        currencyBtn = document.getElementById('currencyDropdownBtn');
        currencyMenu = document.getElementById('currencyDropdownMenu');
        
        if (!langBtn || !langMenu || !currencyBtn || !currencyMenu) return;
        
        // Language dropdown
        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            positionDropdown(langBtn, langMenu);
            langMenu.classList.toggle('show');
            currencyMenu?.classList.remove('show');
        });
        
        // Currency dropdown
        currencyBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            positionDropdown(currencyBtn, currencyMenu);
            currencyMenu.classList.toggle('show');
            langMenu?.classList.remove('show');
        });
        
        // Reposition on scroll (debounced)
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(repositionDropdowns, 50);
        }, { passive: true });
        
        // Reposition on resize (debounced)
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(repositionDropdowns, 100);
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!langBtn?.contains(e.target) && !langMenu?.contains(e.target)) {
                langMenu?.classList.remove('show');
            }
            if (!currencyBtn?.contains(e.target) && !currencyMenu?.contains(e.target)) {
                currencyMenu?.classList.remove('show');
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropdowns);
    } else {
        initDropdowns();
    }
})();

// ========================================
// Language & Currency Change Functions
// ========================================
window.setLanguage = function(id) {
    const select = document.getElementById('changeLanguage');
    if (select) {
        select.value = id;
        changeLanguage();
    }
};

window.setCurrency = function(id) {
    const select = document.getElementById('changeCurrency');
    if (select) {
        select.value = id;
        changeCurrency();
    }
};

window.changeLanguage = function() {
    const select = document.getElementById('changeLanguage');
    if (!select) return;
    
    const id = select.value;
    if (!id) return;
    
    $.ajax({
        url: (typeof baseUrl !== 'undefined' ? baseUrl : '') + 'ajaxs/client/update.php',
        method: 'POST',
        dataType: 'JSON',
        data: {
            action: 'changeLanguage',
            id: id
        },
        success: function(response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                if (typeof cuteAlert !== 'undefined') {
                    cuteAlert({
                        type: 'error',
                        title: 'Error',
                        message: response.msg || 'Đã xảy ra lỗi',
                        buttonText: 'Okay'
                    });
                } else {
                    alert(response.msg || 'Đã xảy ra lỗi');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Change language error:', error);
            if (typeof cuteAlert !== 'undefined') {
                cuteAlert({
                    type: 'error',
                    title: 'Error',
                    message: 'Không thể thay đổi ngôn ngữ',
                    buttonText: 'Okay'
                });
            } else {
                alert('Không thể thay đổi ngôn ngữ');
            }
        }
    });
};

window.changeCurrency = function() {
    const select = document.getElementById('changeCurrency');
    if (!select) return;
    
    const id = select.value;
    if (!id) return;
    
    $.ajax({
        url: (typeof baseUrl !== 'undefined' ? baseUrl : '') + 'ajaxs/client/update.php',
        method: 'POST',
        dataType: 'JSON',
        data: {
            action: 'changeCurrency',
            id: id
        },
        success: function(response) {
            if (response.status === 'success') {
                location.reload();
            } else {
                if (typeof cuteAlert !== 'undefined') {
                    cuteAlert({
                        type: 'error',
                        title: 'Error',
                        message: response.msg || 'Đã xảy ra lỗi',
                        buttonText: 'Okay'
                    });
                } else {
                    alert(response.msg || 'Đã xảy ra lỗi');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Change currency error:', error);
            if (typeof cuteAlert !== 'undefined') {
                cuteAlert({
                    type: 'error',
                    title: 'Error',
                    message: 'Không thể thay đổi tiền tệ',
                    buttonText: 'Okay'
                });
            } else {
                alert('Không thể thay đổi tiền tệ');
            }
        }
    });
};

// ========================================
// Load Menu Categories
// ========================================
window.loadMenuCategories = function() {
    if (typeof baseUrl === 'undefined') {
        console.error('baseUrl is not defined');
        return;
    }
    
    $.ajax({
        url: baseUrl + 'ajaxs/client/load_menu.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Load menu dropdown (nav.php)
            const menuContainer = $('#menu-categories-container');
            if (menuContainer.length) {
                menuContainer.html(response.menu_html || '');
                menuContainer.addClass('menu-loaded');
            }
            
            // Load category buttons (home.php) - nếu container tồn tại
            const homeContainer = $('#home-categories-container');
            if (homeContainer.length) {
                // Xóa skeleton loading
                $('.home-categories-skeleton').remove();
                // Giữ lại nút "Tất cả sản phẩm", chỉ append categories mới
                if (response.home_buttons_html) {
                    homeContainer.append(response.home_buttons_html);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading menu categories:', error);
            // Giữ skeleton loading nếu có lỗi
        }
    });
};

// Auto load menu categories on page load
$(document).ready(function() {
    if ($('#menu-categories-container').length || $('#home-categories-container').length) {
        loadMenuCategories();
    }
});

// ========================================
// Search Autocomplete
// ========================================
(function() {
    'use strict';
    
    // Initialize when DOM is ready
    function initSearchAutocomplete() {
        const searchInput = document.getElementById('searchInput');
        const autocompleteContainer = document.getElementById('searchAutocomplete');
        
        if (!searchInput || !autocompleteContainer) {
            console.warn('Search Autocomplete: Elements not found');
            return;
        }
    
    let debounceTimer = null;
    let currentKeyword = '';
    const DEBOUNCE_DELAY = 300;
    const MIN_CHARS = 2;
    
    // Get URLs from data attributes or use baseUrl
    let AJAX_URL = searchInput.dataset.ajaxUrl;
    let PRODUCTS_URL = searchInput.dataset.productsUrl;
    
    // Fallback to baseUrl if data attributes not set
    if (!AJAX_URL && typeof baseUrl !== 'undefined') {
        AJAX_URL = baseUrl + 'ajaxs/client/view.php';
    }
    if (!PRODUCTS_URL && typeof baseUrl !== 'undefined') {
        PRODUCTS_URL = baseUrl + 'products';
    }
    
    // Check if URLs are available
    if (!AJAX_URL || !PRODUCTS_URL) {
        console.error('Search Autocomplete: URLs not defined. baseUrl:', typeof baseUrl !== 'undefined' ? baseUrl : 'undefined');
        return;
    }
    
    const TRANSLATIONS = {
        noResults: searchInput.dataset.noResults || 'Không tìm thấy sản phẩm nào',
        viewAll: searchInput.dataset.viewAll || 'Xem tất cả kết quả'
    };
    
    // Escape HTML để tránh XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Hiển thị loading
    function showLoading() {
        autocompleteContainer.innerHTML = `
            <div class="search-autocomplete-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
        `;
        autocompleteContainer.classList.add('show');
    }
    
    // Hiển thị kết quả
    function showResults(results, keyword) {
        if (results.length === 0) {
            autocompleteContainer.innerHTML = `
                <div class="search-autocomplete-empty">${escapeHtml(TRANSLATIONS.noResults)}</div>
                <div class="search-autocomplete-footer">
                    <a href="${PRODUCTS_URL}?keyword=${encodeURIComponent(keyword)}">${escapeHtml(TRANSLATIONS.viewAll)} →</a>
                </div>
            `;
        } else {
            let html = '';
            results.forEach(product => {
                const imageHtml = product.image 
                    ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}">`
                    : `<i class="fa-solid fa-image"></i>`;
                    
                html += `
                    <a href="${escapeHtml(product.url)}" class="search-autocomplete-item">
                        <div class="search-autocomplete-image">${imageHtml}</div>
                        <div class="search-autocomplete-info">
                            <p class="search-autocomplete-name">${escapeHtml(product.name)}</p>
                            <p class="search-autocomplete-price">${escapeHtml(product.price)}</p>
                        </div>
                    </a>
                `;
            });
            
            html += `
                <div class="search-autocomplete-footer">
                    <a href="${PRODUCTS_URL}?keyword=${encodeURIComponent(keyword)}">${escapeHtml(TRANSLATIONS.viewAll)} →</a>
                </div>
            `;
            
            autocompleteContainer.innerHTML = html;
        }
        autocompleteContainer.classList.add('show');
    }
    
    // Ẩn autocomplete
    function hideAutocomplete() {
        autocompleteContainer.classList.remove('show');
    }
    
    // Gọi API tìm kiếm
    function searchProducts(keyword) {
        if (keyword.length < MIN_CHARS) {
            hideAutocomplete();
            return;
        }
        
        showLoading();
        
        const formData = new FormData();
        formData.append('action', 'searchAutocomplete');
        formData.append('keyword', keyword);
        
        // Add CSRF token if available
        const csrfToken = getCSRFToken();
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success' && currentKeyword === keyword) {
                showResults(data.data || [], keyword);
            } else if (currentKeyword === keyword) {
                hideAutocomplete();
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            if (currentKeyword === keyword) {
                hideAutocomplete();
            }
        });
    }
    
    // Event: Input change
    searchInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        currentKeyword = keyword;
        
        clearTimeout(debounceTimer);
        
        if (keyword.length < MIN_CHARS) {
            hideAutocomplete();
            return;
        }
        
        debounceTimer = setTimeout(() => {
            searchProducts(keyword);
        }, DEBOUNCE_DELAY);
    });
    
    // Event: Focus
    searchInput.addEventListener('focus', function() {
        const keyword = this.value.trim();
        if (keyword.length >= MIN_CHARS) {
            searchProducts(keyword);
        }
    });
    
    // Event: Click outside để đóng
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteContainer.contains(e.target)) {
            hideAutocomplete();
        }
    });
    
    // Event: Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        const items = autocompleteContainer.querySelectorAll('.search-autocomplete-item');
        const activeItem = autocompleteContainer.querySelector('.search-autocomplete-item.active');
        let currentIndex = -1;
        
        if (activeItem) {
            items.forEach((item, index) => {
                if (item === activeItem) currentIndex = index;
            });
        }
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                if (activeItem) activeItem.classList.remove('active');
                const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                items[nextIndex].classList.add('active');
                items[nextIndex].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                if (activeItem) activeItem.classList.remove('active');
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex].classList.add('active');
                items[prevIndex].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            if (activeItem) {
                e.preventDefault();
                window.location.href = activeItem.href;
            }
        } else if (e.key === 'Escape') {
            hideAutocomplete();
        }
    });
    } // End initSearchAutocomplete
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSearchAutocomplete);
    } else {
        // DOM already loaded, but wait a bit for baseUrl to be defined
        setTimeout(initSearchAutocomplete, 100);
    }
})();

/**
 * Cart Count Module
 * Cập nhật số lượng giỏ hàng trong navigation
 */
(function() {
    'use strict';

    /**
     * Initialize
     */
    function init() {
        updateCartCount();
        
        // Listen for cart updates from other modules
        window.addEventListener('cartUpdated', function() {
            updateCartCount();
        });
    }

    /**
     * Get cart from LocalStorage
     */
    function getCart() {
        try {
            const cart = localStorage.getItem('shopkey_cart');
            return cart ? JSON.parse(cart) : [];
        } catch (e) {
            console.error('Error reading cart:', e);
            return [];
        }
    }

    /**
     * Update cart count in nav
     */
    function updateCartCount() {
        const cart = getCart();
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        // Update element by ID
        const numCart = document.getElementById('numCart');
        if (numCart) {
            numCart.textContent = totalItems;
            numCart.style.display = totalItems > 0 ? '' : 'none';
        }
        
        // Update all elements with class numCart (mobile cart badge, etc.)
        const numCartElements = document.querySelectorAll('.numCart');
        numCartElements.forEach(function(el) {
            el.textContent = totalItems;
            el.style.display = totalItems > 0 ? '' : 'none';
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose public API
    window.CartModule = {
        getCart: getCart,
        refresh: updateCartCount
    };

})();

// ========================================
// Recharge Sidebar Functions
// ========================================
window.openRechargeSidebar = function() {
    // Sử dụng jQuery giống như code gốc để đảm bảo nhất quán
    if (typeof $ !== 'undefined') {
        $("body").css("overflow", "hidden");
        $(".cart-sidebar.recharge-sidebar").addClass("active");
        $(".backdrop").fadeIn();
        
        // Bind close button
        $(".recharge-close-btn").off("click").on("click", function() {
            closeRechargeSidebar();
        });
        
        // Bind backdrop click
        $(".backdrop").off("click").on("click", function() {
            $(this).fadeOut();
            $("body").css("overflow", "inherit");
            $(".nav-sidebar").removeClass("active");
            $(".cart-sidebar").removeClass("active");
            $(".category-sidebar").removeClass("active");
        });
    } else {
        // Fallback nếu không có jQuery
        const rechargeSidebar = document.querySelector('.recharge-sidebar');
        const backdrop = document.querySelector('.backdrop');
        
        if (rechargeSidebar) {
            rechargeSidebar.classList.add('active');
            if (backdrop) {
                backdrop.style.display = 'block';
            }
            document.body.style.overflow = 'hidden';
        }
    }
};

window.closeRechargeSidebar = function() {
    if (typeof $ !== 'undefined') {
        $("body").css("overflow", "inherit");
        $(".cart-sidebar.recharge-sidebar").removeClass("active");
        $(".backdrop").fadeOut();
    } else {
        const rechargeSidebar = document.querySelector('.recharge-sidebar');
        const backdrop = document.querySelector('.backdrop');
        
        if (rechargeSidebar) {
            rechargeSidebar.classList.remove('active');
            if (backdrop) {
                backdrop.style.display = 'none';
            }
            document.body.style.overflow = '';
        }
    }
};

// Initialize Recharge Sidebar - ESC key handler
(function() {
    'use strict';
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const rechargeSidebar = document.querySelector('.recharge-sidebar');
            if (rechargeSidebar && rechargeSidebar.classList.contains('active')) {
                closeRechargeSidebar();
            }
        }
    });
})();

// ============================================
// Theme Toggle (Dark/Light Mode)
// ============================================
(function() {
    'use strict';
    
    var themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            var currentTheme = document.documentElement.getAttribute('data-theme');
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });
    }
})();