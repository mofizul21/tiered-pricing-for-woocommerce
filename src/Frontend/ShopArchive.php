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
		add_filter( 'woolentor_filterable_shortcode_products_query', [ $this, 'filter_search_to_title_and_category' ], 20, 2 );
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
	 * Replace the `s` query arg in ShopLentor's filterable query with an explicit
	 * post__in list so results are limited to title and category matches only.
	 *
	 * Fires at priority 20, after WLPF\Frontend\Query\Shortcode (priority 10) has
	 * already added `s` to the query args.  Removing `s` also changes the md5 key
	 * used by WC_Shortcode_Products for its transient cache, so stale cached results
	 * that include description matches are never returned.
	 *
	 * @param array $query_args  Accumulated WP_Query args.
	 * @param array $filter_args ShopLentor filter data.
	 * @return array
	 */
	public function filter_search_to_title_and_category( array $query_args, array $filter_args ): array {
		if ( empty( $query_args['s'] ) ) {
			return $query_args;
		}

		$search = (string) $query_args['s'];
		unset( $query_args['s'] );

		$ids = $this->get_products_by_title_and_category( $search );

		if ( empty( $ids ) ) {
			$query_args['post__in'] = [ 0 ];
		} else {
			$existing = isset( $query_args['post__in'] ) ? array_filter( (array) $query_args['post__in'] ) : [];
			if ( empty( $existing ) ) {
				$query_args['post__in'] = $ids;
			} else {
				$intersect = array_values( array_intersect( $ids, $existing ) );
				$query_args['post__in'] = $intersect ?: [ 0 ];
			}
		}

		return $query_args;
	}

	/**
	 * Return IDs of published products whose title contains every search word
	 * (AND) or whose product_cat name matches the full phrase (OR).
	 *
	 * @param string $search Raw search string.
	 * @return int[]
	 */
	private function get_products_by_title_and_category( string $search ): array {
		global $wpdb;

		$terms = $this->split_search_terms( $search );

		$title_clauses = array_map(
			fn( string $term ) => $wpdb->prepare(
				'p.post_title LIKE %s',
				'%' . $wpdb->esc_like( $term ) . '%'
			),
			$terms
		);

		$cat_clause = $wpdb->prepare(
			"p.ID IN (
				SELECT tr.object_id
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = 'product_cat'
				AND t.name LIKE %s
			)",
			'%' . $wpdb->esc_like( $search ) . '%'
		);

		$title_sql = implode( ' AND ', $title_clauses );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col(
			"SELECT p.ID
			 FROM {$wpdb->posts} p
			 WHERE p.post_type = 'product'
			   AND p.post_status = 'publish'
			   AND ( ({$title_sql}) OR ({$cat_clause}) )"
		);

		return array_map( 'intval', $ids ?: [] );
	}

	/**
	 * Split a raw search string into individual words, ignoring quoted phrases.
	 *
	 * @param string $raw Raw search string.
	 * @return string[]
	 */
	private function split_search_terms( string $raw ): array {
		$plain = preg_replace( '/"[^"]*"/', '', $raw );
		$terms = preg_split( '/\s+/', trim( (string) $plain ), -1, PREG_SPLIT_NO_EMPTY );

		return $terms ?: [ $raw ];
	}

	/**
	 * Detect whether the current request is a supported quick view AJAX call.
	 *
	 * Supported sources:
	 * - WooLentor / ShopLentor: action=woolentor_quickview
	 * - Essential Addons for Elementor (Woo Product Gallery widget): action=eael_product_quickview_popup
	 */
	private function is_quickview_ajax(): bool {
		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! isset( $_REQUEST['action'] ) ) {
			return false;
		}

		return in_array( $_REQUEST['action'], [ 'woolentor_quickview', 'eael_product_quickview_popup' ], true );
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
