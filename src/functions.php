<?php
/**
 * Useful functions.
 *
 * @package WineVendorWooCommerce
 * @since 1.0.0
 */


use WineVendorWooCommerce\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the plugin instance.
 *
 * @since 1.0.0
 * @return Plugin
 */
function WVWC(): Plugin
{ // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
    return Plugin::instance();
}
