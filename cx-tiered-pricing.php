<?php
/**
 * Plugin Name: CX Tiered Pricing
 * Description: Tiered pricing for WooCommerce products.
 * Version: 1.0
 * Author: Creatricx
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CX_TIERED_PRICING_FILE', __FILE__);
define('CX_TIERED_PRICING_PATH', plugin_dir_path(__FILE__));
define('CX_TIERED_PRICING_URL', plugin_dir_url(__FILE__));

require_once CX_TIERED_PRICING_PATH . 'includes/class-cx-tiered-pricing-db.php';
require_once CX_TIERED_PRICING_PATH . 'includes/class-cx-tiered-pricing-prices.php';
require_once CX_TIERED_PRICING_PATH . 'includes/class-cx-tiered-pricing-admin.php';
require_once CX_TIERED_PRICING_PATH . 'includes/class-cx-tiered-pricing-cleanup.php';

register_activation_hook(__FILE__, ['CX_Tiered_Pricing_DB', 'install']);

add_action('plugins_loaded', function () {
    CX_Tiered_Pricing_Admin::init();
    CX_Tiered_Pricing_Prices::init();
    CX_Tiered_Pricing_Cleanup::init();
});