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

/**
 * Write a debug line to wp-content/debug.log via error_log().
 * Remove this function (and all tpfw_log() calls) once the bug is diagnosed.
 *
 * @param string $message
 */
function tpfw_log( string $message ): void {
	error_log( '[TPFW] ' . $message );
}
