<?php
/**
 * Uninstall Tiered Pricing for WooCommerce.
 *
 * @package TieredPricingForWooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( 'yes' !== get_option( 'tpfw_delete_data', 'no' ) ) {
	return;
}

delete_option( 'tpfw_enable_plugin' );
delete_option( 'tpfw_enable_frontend_display' );
delete_option( 'tpfw_delete_data' );

$products = get_posts(
	[
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]
);

foreach ( $products as $product_id ) {
	delete_post_meta( $product_id, '_tpfw_enabled' );
	delete_post_meta( $product_id, '_tpfw_tier_rules' );
}
