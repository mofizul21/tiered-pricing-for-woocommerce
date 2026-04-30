<?php
namespace TieredPricingForWooCommerce\Frontend;

use WC_Product;

defined( 'ABSPATH' ) || exit;

class ShopArchive {

	/**
	 * Register hooks for the shop, archive, and quick view contexts.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_price_html', [ $this, 'maybe_replace_price_html' ], 10, 2 );
		add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'remove_loop_add_to_cart' ], 10, 2 );
		add_action( 'wp_loaded', [ $this, 'block_url_add_to_cart' ], 5 );
		add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'quickview_buffer_start' ], 5 );
		add_action( 'woocommerce_after_add_to_cart_form', [ $this, 'quickview_buffer_end' ], 999 );
	}

	/**
	 * Replace price HTML for custom-priced products.
	 *
	 * Returns empty string on the single product page (the shortcode shows the full
	 * pricing table there). On all other contexts — shop, archive, quick view AJAX —
	 * returns a formatted min–max price range.
	 *
	 * is_admin() returns true during admin-ajax.php requests, so AJAX must be
	 * explicitly allowed through; otherwise the quick view price is never replaced.
	 *
	 * @param string     $price_html Existing price HTML.
	 * @param WC_Product $product    Product object.
	 * @return string
	 */
	public function maybe_replace_price_html( string $price_html, WC_Product $product ): string {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $price_html;
		}

		if ( ! $this->has_custom_pricing( $product->get_id() ) ) {
			return $price_html;
		}

		if ( is_product() && get_queried_object_id() === $product->get_id() ) {
			return '';
		}

		return $this->get_price_range_html( $product->get_id() );
	}

	/**
	 * Remove the loop add-to-cart button for custom-priced products.
	 *
	 * @param string     $link    Button HTML.
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	public function remove_loop_add_to_cart( string $link, WC_Product $product ): string {
		if ( $this->has_custom_pricing( $product->get_id() ) ) {
			return '';
		}

		return $link;
	}

	/**
	 * Redirect ?add-to-cart= GET requests for custom-priced products to the product page.
	 *
	 * Runs at priority 5, before WooCommerce's own handler at priority 20.
	 */
	public function block_url_add_to_cart(): void {
		if ( empty( $_GET['add-to-cart'] ) ) {
			return;
		}

		$product_id = absint( $_GET['add-to-cart'] );

		if ( $product_id && $this->has_custom_pricing( $product_id ) ) {
			wp_safe_redirect( get_permalink( $product_id ) );
			exit;
		}
	}

	/**
	 * Start output buffering before the add-to-cart form in the quick view popup.
	 *
	 * The buffered output (WC quantity input + add-to-cart button) is discarded
	 * in quickview_buffer_end() and replaced with a plain "Purchase" link.
	 */
	public function quickview_buffer_start(): void {
		global $product;

		if ( $product instanceof WC_Product && $this->has_custom_pricing( $product->get_id() ) && $this->is_quickview_ajax() ) {
			ob_start();
		}
	}

	/**
	 * Discard the buffered add-to-cart form and output a "Purchase" link instead.
	 */
	public function quickview_buffer_end(): void {
		global $product;

		if ( ! ( $product instanceof WC_Product ) || ! $this->has_custom_pricing( $product->get_id() ) || ! $this->is_quickview_ajax() ) {
			return;
		}

		ob_end_clean();

		echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" class="button alt tpfw-purchase-btn">';
		echo esc_html__( 'Purchase', 'tiered-pricing-for-woocommerce' );
		echo '</a>';
	}

	/**
	 * Detect whether the current request is the WooLentor quick view AJAX call.
	 */
	private function is_quickview_ajax(): bool {
		return defined( 'DOING_AJAX' ) && DOING_AJAX
			&& isset( $_REQUEST['action'] )
			&& 'woolentor_quickview' === $_REQUEST['action'];
	}

	/**
	 * Build a min–max price range HTML string from the product's pricing tiers.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	private function get_price_range_html( int $product_id ): string {
		$tiers = $this->get_pricing_table( $product_id );

		if ( empty( $tiers ) ) {
			return '';
		}

		$prices = array_column( $tiers, 'price' );
		$min    = (float) min( $prices );
		$max    = (float) max( $prices );

		if ( $min === $max ) {
			return '<span class="price">' . wc_price( $min ) . '</span>';
		}

		return '<span class="price">' . wc_price( $min ) . ' &ndash; ' . wc_price( $max ) . '</span>';
	}

	/**
	 * Check whether a product uses the custom tiered pricing flow.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function has_custom_pricing( int $product_id ): bool {
		return 'yes' === get_post_meta( $product_id, '_tpfw_enable_pricing_table', true )
			&& ! empty( $this->get_pricing_table( $product_id ) );
	}

	/**
	 * Get the sanitized pricing tier rows for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, float|int>>
	 */
	private function get_pricing_table( int $product_id ): array {
		$tiers = get_post_meta( $product_id, '_tpfw_pricing_table', true );

		return is_array( $tiers ) ? $tiers : [];
	}
}
