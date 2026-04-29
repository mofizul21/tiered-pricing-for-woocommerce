<?php
/**
 * Plugin Name:          Tiered Pricing for WooCommerce
 * Description:          Offer tiered, quantity-based pricing on WooCommerce products. Automatically adjusts the per-unit price as customers increase quantity — for example: $5.00 each for 10 units, $4.00 each for 30 units. Supports unlimited pricing tiers per product.
 * Version:              1.0.0
 * Author:               Mofizul Islam
 * Author URI:           https://mofizul.com/
 * Text Domain:          tiered-pricing-for-woocommerce
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
 * @package TieredPricingForWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TPFW_VERSION = '1.0.0';
const TPFW_TEXT_DOMAIN = 'tiered-pricing-for-woocommerce';

if ( ! defined( 'TPFW_PLUGIN_FILE' ) ) {
	define( 'TPFW_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'TPFW_PLUGIN_PATH' ) ) {
	define( 'TPFW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'TPFW_PLUGIN_URL' ) ) {
	define( 'TPFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once __DIR__ . '/src/Core/Base/Settings.php';
require_once __DIR__ . '/src/Admin/Settings.php';
require_once __DIR__ . '/src/Admin/Menus.php';
require_once __DIR__ . '/src/Admin/Admin.php';
require_once __DIR__ . '/src/Core/ProductColors.php';
require_once __DIR__ . '/src/Core/ProductSettings.php';
require_once __DIR__ . '/src/Core/WooCommerceStarter.php';
require_once __DIR__ . '/src/Frontend/ProductInfo.php';
require_once __DIR__ . '/src/Frontend/ProductPricingTable.php';
require_once __DIR__ . '/src/Frontend/CheckoutFields.php';
require_once __DIR__ . '/src/Plugin.php';
require_once __DIR__ . '/src/functions.php';

/**
 * Initialize the plugin.
 */
function tpfw_run_plugin(): void {
	TPFW()->init(
		[
			'settings_url' => admin_url( 'admin.php?page=tiered-pricing-for-woocommerce' ),
			'docs_url'     => 'https://mofizul.com/',
		]
	);
}
add_action( 'plugins_loaded', 'tpfw_run_plugin' );

/**
 * Plugin activation tasks.
 */
function tpfw_plugin_activation_tasks(): void {
	add_option( 'tpfw_enable_plugin', 'yes' );
	add_option( 'tpfw_enable_frontend_display', 'yes' );
	add_option( 'tpfw_delete_data', 'no' );
}
register_activation_hook( __FILE__, 'tpfw_plugin_activation_tasks' );
