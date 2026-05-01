<?php
namespace TieredPricingForWooCommerce\Frontend;

defined( 'ABSPATH' ) || exit;

class PriceFilter {

	public function __construct() {
		add_action( 'init', [ $this, 'sync_prices' ] );
	}

	/**
	 * Populate _price (min tier) and _tpfw_max_price (max tier) for every
	 * tiered product that is missing them.
	 *
	 * ShopLentor's price slider filters by:
	 *   _price >= filter_min AND _price <= filter_max
	 *
	 * _price must equal the minimum tier price for that query to work.
	 * WooCommerce's own save flow sets _price to the regular/sale price, which
	 * is irrelevant for tiered-only products. ProductSettings::save_product_fields()
	 * overrides _price on every admin save, but products saved before that code
	 * existed need a one-time migration.
	 *
	 * Uses direct SQL instead of wc_get_products() because WC_Product_Query
	 * does not reliably honour bare meta_key/meta_value arguments in all versions.
	 */
	public function sync_prices(): void {
		if ( get_option( 'tpfw_prices_synced_v3' ) ) {
			return;
		}

		global $wpdb;

		// Find every product that has a pricing table saved.
		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = %s",
				'_tpfw_pricing_table'
			)
		);

		foreach ( $product_ids as $product_id ) {
			$product_id = (int) $product_id;

			// Only sync products where tiered pricing is enabled.
			$enabled = get_post_meta( $product_id, '_tpfw_enable_pricing_table', true );
			if ( 'yes' !== $enabled ) {
				continue;
			}

			$tiers = get_post_meta( $product_id, '_tpfw_pricing_table', true );
			if ( ! is_array( $tiers ) || empty( $tiers ) ) {
				continue;
			}

			$tier_prices = array_filter(
				array_map( 'floatval', array_column( $tiers, 'price' ) )
			);

			if ( empty( $tier_prices ) ) {
				continue;
			}

			update_post_meta( $product_id, '_price', (string) min( $tier_prices ) );
			update_post_meta( $product_id, '_tpfw_max_price', (string) max( $tier_prices ) );
		}

		// Bust WooCommerce's product query transient cache so the next filter
		// request runs a fresh query instead of returning stale cached results.
		WC_Cache_Helper::get_transient_version( 'product_query', true );

		update_option( 'tpfw_prices_synced_v3', 'yes' );
	}
}
