<?php
namespace TieredPricingForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

class ProductSettings {

	/**
	 * Register product settings hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_pricing', [ $this, 'render_product_fields' ] );
		// Priority 20 — runs after WooCommerce saves _price/_regular_price at priority 10,
		// so our _price override (min tier price) always wins.
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_fields' ], 20 );
	}

	/**
	 * Render custom product pricing fields.
	 */
	public function render_product_fields(): void {
		if ( 'yes' !== get_option( 'tpfw_enable_plugin', 'yes' ) ) {
			return;
		}

		$tiers = get_post_meta( get_the_ID(), '_tpfw_pricing_table', true );
		$tiers = is_array( $tiers ) ? $tiers : [];

		if ( empty( $tiers ) ) {
			$tiers = [
				[
					'quantity' => '',
					'price'    => '',
				],
			];
		}

		echo '<div class="options_group tpfw-product-pricing-group">';

		woocommerce_wp_checkbox(
			[
				'id'          => '_tpfw_enable_pricing_table',
				'label'       => esc_html__( 'Enable pricing table', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Use the custom quantity pricing table and order builder on the single product page.', 'tiered-pricing-for-woocommerce' ),
			]
		);

		echo '<p class="form-field">';
		echo '<label>' . esc_html__( 'Pricing table', 'tiered-pricing-for-woocommerce' ) . '</label>';
		echo '<span class="wrap">';
		echo '<table class="widefat striped tpfw-pricing-table-editor">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Minimum quantity', 'tiered-pricing-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Your price (each)', 'tiered-pricing-for-woocommerce' ) . '</th>';
		echo '<th class="tpfw-pricing-table-actions">' . esc_html__( 'Actions', 'tiered-pricing-for-woocommerce' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $tiers as $index => $tier ) {
			$this->render_pricing_row( $index, $tier );
		}

		echo '</tbody></table>';
		echo '<button type="button" class="button tpfw-add-pricing-row">' . esc_html__( 'Add Row', 'tiered-pricing-for-woocommerce' ) . '</button>';
		echo '<input type="hidden" class="tpfw-pricing-row-count" value="' . esc_attr( count( $tiers ) ) . '">';
		echo '</span>';
		echo '<span class="description">' . esc_html__( 'Add one row for each quantity break. The first row is the minimum order quantity.', 'tiered-pricing-for-woocommerce' ) . '</span>';
		echo '</p>';

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_pricing_note',
				'label'       => esc_html__( 'Table note', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Optional text displayed below the pricing table on the frontend.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_themes',
				'label'       => esc_html__( 'Themes', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Additional product information tab content.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_imprint',
				'label'       => esc_html__( 'Imprint', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Additional product information tab content.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_additional',
				'label'       => esc_html__( 'Additional', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Additional product information tab content.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_options',
				'label'       => esc_html__( 'Options', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Additional product information tab content.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_delivery',
				'label'       => esc_html__( 'Delivery', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Additional product information tab content.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		echo '</div>';
	}

	/**
	 * Render one pricing row.
	 *
	 * @param int|string $index Row index.
	 * @param array      $tier  Row values.
	 */
	private function render_pricing_row( $index, array $tier ): void {
		$quantity = isset( $tier['quantity'] ) ? $tier['quantity'] : '';
		$price    = isset( $tier['price'] ) ? $tier['price'] : '';

		echo '<tr class="tpfw-pricing-row">';
		echo '<td><input type="number" min="1" step="1" name="tpfw_pricing_table[' . esc_attr( (string) $index ) . '][quantity]" value="' . esc_attr( (string) $quantity ) . '" class="short"></td>';
		echo '<td><input type="number" min="0" step="0.01" placeholder="$0.00" name="tpfw_pricing_table[' . esc_attr( (string) $index ) . '][price]" value="' . esc_attr( (string) $price ) . '" class="short wc_input_price"></td>';
		echo '<td class="tpfw-pricing-table-actions"><button type="button" class="button-link-delete tpfw-remove-pricing-row">' . esc_html__( 'Remove', 'tiered-pricing-for-woocommerce' ) . '</button></td>';
		echo '</tr>';
	}

	/**
	 * Save custom product pricing fields.
	 *
	 * @param int $product_id Product ID.
	 */
	public function save_product_fields( int $product_id ): void {
		$enabled      = isset( $_POST['_tpfw_enable_pricing_table'] ) ? 'yes' : 'no';
		$pricing_note = isset( $_POST['_tpfw_pricing_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_pricing_note'] ) ) : '';
		$themes       = isset( $_POST['_tpfw_themes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_themes'] ) ) : '';
		$imprint      = isset( $_POST['_tpfw_imprint'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_imprint'] ) ) : '';
		$additional   = isset( $_POST['_tpfw_additional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_additional'] ) ) : '';
		$options      = isset( $_POST['_tpfw_options'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_options'] ) ) : '';
		$delivery     = isset( $_POST['_tpfw_delivery'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_delivery'] ) ) : '';
		$raw_tiers    = isset( $_POST['tpfw_pricing_table'] ) ? wp_unslash( $_POST['tpfw_pricing_table'] ) : [];

		$tiers = $this->sanitize_pricing_table( $raw_tiers );

		update_post_meta( $product_id, '_tpfw_enable_pricing_table', $enabled );
		update_post_meta( $product_id, '_tpfw_pricing_note', $pricing_note );
		update_post_meta( $product_id, '_tpfw_themes', $themes );
		update_post_meta( $product_id, '_tpfw_imprint', $imprint );
		update_post_meta( $product_id, '_tpfw_additional', $additional );
		update_post_meta( $product_id, '_tpfw_options', $options );
		update_post_meta( $product_id, '_tpfw_delivery', $delivery );
		update_post_meta( $product_id, '_tpfw_pricing_table', $tiers );

		// Keep WooCommerce's _price in sync with the minimum tier price, and store
		// _tpfw_max_price for the maximum tier price. PriceFilter::fix_query() uses
		// both to perform an overlap check so the ShopLentor price-range slider
		// correctly matches products whose tier range intersects the filter range.
		if ( 'yes' === $enabled && ! empty( $tiers ) ) {
			$tier_prices  = array_map( 'floatval', array_column( $tiers, 'price' ) );
			update_post_meta( $product_id, '_price', (string) min( $tier_prices ) );
			update_post_meta( $product_id, '_tpfw_max_price', (string) max( $tier_prices ) );
		} else {
			delete_post_meta( $product_id, '_tpfw_max_price' );
		}
	}

	/**
	 * Sanitize posted pricing rows.
	 *
	 * @param mixed $raw_tiers Raw row data.
	 * @return array<int, array<string, float|int>>
	 */
	private function sanitize_pricing_table( $raw_tiers ): array {
		if ( ! is_array( $raw_tiers ) ) {
			return [];
		}

		$tiers = [];

		foreach ( $raw_tiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}

			$quantity = isset( $tier['quantity'] ) ? absint( $tier['quantity'] ) : 0;
			$price    = isset( $tier['price'] ) ? wc_format_decimal( $tier['price'] ) : '';

			if ( $quantity < 1 || '' === $price ) {
				continue;
			}

			$tiers[] = [
				'quantity' => $quantity,
				'price'    => (float) $price,
			];
		}

		usort(
			$tiers,
			static function( array $left, array $right ): int {
				return $left['quantity'] <=> $right['quantity'];
			}
		);

		return $tiers;
	}
}
