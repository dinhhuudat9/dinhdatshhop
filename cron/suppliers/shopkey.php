<?php
/**
 * CRON - SHOPKEY Supplier
 * Đồng bộ sản phẩm từ API SHOPKEY
 * 
 * Hỗ trợ 2 chế độ:
 * - child = OFF: Chỉ đồng bộ product_plans
 * - child = ON: Đồng bộ đầy đủ (categories, products, plans)
 * 
 * URL: /cron/suppliers/shopkey.php?key=YOUR_CRON_KEY
 */

define('SUPPLIER_TYPE', 'SHOPKEY');
require_once(__DIR__ . '/_base.php');
