<?php
/**
 * Useful functions.
 *
 * @package TieredPricingForWooCommerce
 * @since 1.0.0
 */

use TieredPricingForWooCommerce\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the plugin instance.
 *
 * @since 1.0.0
 * @return Plugin
 */
function TPFW(): Plugin { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return Plugin::instance();
}
