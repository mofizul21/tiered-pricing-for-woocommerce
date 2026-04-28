<?php
/**
 * Plugin Name:          Wine Vendor WooCommerce
 * Description:          Provides a custom dashboard for the Wine Vendor role with access to specific WooCommerce menus and data, including Products, Orders, Customers, Sales Reports, and Analytics.
 * Version:              1.0.0
 * Author:               Mofizul Islam
 * Author URI:           https://mofizul.com/
 * Text Domain:          wine-vendor-woocommerce
 * Domain Path:          /languages
 * License:              GPL-3.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * Tested up to:         6.8
 * Requires at least:    5.2
 * Requires PHP:         7.4
 * WC requires at least: 3.0.0
 * WC tested up to:      10.0
 * Requires Plugins:     woocommerce
 *
 * @package WineVendorWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin version
const WVWC_VERSION = '1.0.0';

// Define the plugin file
if ( ! defined( 'WVWC_PLUGIN_FILE' ) ) {
    define( 'WVWC_PLUGIN_FILE', __FILE__ );
}

// Define the plugin path
if ( ! defined( 'WVWC_PLUGIN_PATH' ) ) {
    define( 'WVWC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Include the autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Include global functions file
require_once __DIR__ . '/src/functions.php';

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return void
 */
function wvwc_run_plugin(): void {
    WVWC()->init([
        'settings_url'	=> admin_url( 'admin.php?page=wine-vendor-woocommerce' ),
		'docs_url'		=> admin_url( 'https://orfeostory.biz/docs/wine-vendor-woocommerce/' ),
    ]);
}
add_action( 'plugins_loaded', 'wvwc_run_plugin' );

/**
 * Plugin activation tasks
 *
 * @since 1.0.0
 * @return void
 */
function wvwc_plugin_activation_tasks(): void {
    // Add the "Wine Vendor" role
    add_role(
        'wine_vendor',
        __( 'Wine Vendor', 'wine-vendor-woocommerce' ),
        [
            'read'         => true,
            'edit_posts'   => true,
            'delete_posts' => true,
            'edit_products' => true,
            'edit_published_products' => true,
            'delete_products' => true,
            'delete_published_products' => true,
            'publish_products' => true,
            'list_users' => true,
            'edit_shop_orders' => true,
            'view_woocommerce_reports' => true,
        ]
    );
}
register_activation_hook( __FILE__, 'wvwc_plugin_activation_tasks' );
