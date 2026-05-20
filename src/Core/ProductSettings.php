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

		$this->render_custom_dropdowns_field( get_the_ID() );

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
	 * Render custom dropdown groups field.
	 */
	private function render_custom_dropdowns_field( int $product_id ): void {
		$dropdowns = get_post_meta( $product_id, '_tpfw_custom_dropdowns', true );
		$dropdowns = is_array( $dropdowns ) ? $dropdowns : [];

		echo '<div class="form-field tpfw-custom-dropdowns-field">';
		echo '<label>' . esc_html__( 'Custom Order Options', 'tiered-pricing-for-woocommerce' ) . '</label>';
		echo '<div class="tpfw-custom-dropdowns-inner">';
		echo '<div class="tpfw-custom-dropdowns-wrap">';

		foreach ( $dropdowns as $di => $dropdown ) {
			$this->render_dropdown_group( $di, $dropdown );
		}

		echo '</div>';
		echo '<button type="button" class="button tpfw-add-dropdown-group" style="margin:6px 0 0;">' . esc_html__( 'Add Dropdown Group', 'tiered-pricing-for-woocommerce' ) . '</button>';
		echo '<p class="description" style="margin-top:6px;">' . esc_html__( 'Add custom dropdowns (e.g. Size, Color options). Each option can carry an optional price add-on multiplied by quantity.', 'tiered-pricing-for-woocommerce' ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render one custom dropdown group.
	 */
	private function render_dropdown_group( int $di, array $dropdown ): void {
		$label    = isset( $dropdown['label'] ) ? $dropdown['label'] : '';
		$required = ! empty( $dropdown['required'] );
		$options  = isset( $dropdown['options'] ) && is_array( $dropdown['options'] ) ? $dropdown['options'] : [ [ 'label' => '', 'price' => '' ] ];

		echo '<div class="tpfw-dropdown-group" data-di="' . esc_attr( (string) $di ) . '">';
		echo '<div class="tpfw-dropdown-group-header">';
		echo '<input type="text" name="tpfw_custom_dropdowns[' . esc_attr( (string) $di ) . '][label]" value="' . esc_attr( $label ) . '" placeholder="' . esc_attr__( 'Label (e.g. Size)', 'tiered-pricing-for-woocommerce' ) . '" class="regular-text tpfw-dropdown-label">';
		echo '<label class="tpfw-dropdown-required-label"><input type="checkbox" name="tpfw_custom_dropdowns[' . esc_attr( (string) $di ) . '][required]" value="1"' . checked( $required, true, false ) . '> ' . esc_html__( 'Required', 'tiered-pricing-for-woocommerce' ) . '</label>';
		echo '<button type="button" class="button-link-delete tpfw-remove-dropdown-group">' . esc_html__( 'Remove Group', 'tiered-pricing-for-woocommerce' ) . '</button>';
		echo '</div>';

		echo '<table class="widefat striped tpfw-dropdown-options-table">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Option Label', 'tiered-pricing-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Price Add-on (+$)', 'tiered-pricing-for-woocommerce' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'tiered-pricing-for-woocommerce' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $options as $oi => $option ) {
			$this->render_dropdown_option_row( $di, $oi, $option );
		}

		echo '</tbody></table>';
		echo '<button type="button" class="button tpfw-add-dropdown-option" style="margin:4px 0 0;">' . esc_html__( 'Add Option', 'tiered-pricing-for-woocommerce' ) . '</button>';
		echo '</div>';
	}

	/**
	 * Render one dropdown option row.
	 */
	private function render_dropdown_option_row( int $di, int $oi, array $option ): void {
		$label = isset( $option['label'] ) ? $option['label'] : '';
		$price = isset( $option['price'] ) ? $option['price'] : '';

		echo '<tr class="tpfw-dropdown-option-row">';
		echo '<td><input type="text" name="tpfw_custom_dropdowns[' . esc_attr( (string) $di ) . '][options][' . esc_attr( (string) $oi ) . '][label]" value="' . esc_attr( (string) $label ) . '" class="regular-text"></td>';
		echo '<td><input type="number" min="0" step="0.01" placeholder="0.00" name="tpfw_custom_dropdowns[' . esc_attr( (string) $di ) . '][options][' . esc_attr( (string) $oi ) . '][price]" value="' . esc_attr( (string) $price ) . '" class="short wc_input_price"></td>';
		echo '<td><button type="button" class="button-link-delete tpfw-remove-dropdown-option">' . esc_html__( 'Remove', 'tiered-pricing-for-woocommerce' ) . '</button></td>';
		echo '</tr>';
	}

	/**
	 * Sanitize custom dropdowns from POST.
	 */
	private function sanitize_custom_dropdowns( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$result = [];

		foreach ( $raw as $dropdown ) {
			if ( ! is_array( $dropdown ) ) {
				continue;
			}

			$label    = isset( $dropdown['label'] ) ? sanitize_text_field( $dropdown['label'] ) : '';
			$required = ! empty( $dropdown['required'] );

			if ( '' === $label ) {
				continue;
			}

			$options = [];

			if ( isset( $dropdown['options'] ) && is_array( $dropdown['options'] ) ) {
				foreach ( $dropdown['options'] as $option ) {
					if ( ! is_array( $option ) ) {
						continue;
					}

					$opt_label = isset( $option['label'] ) ? sanitize_text_field( $option['label'] ) : '';
					$opt_price = ( isset( $option['price'] ) && '' !== $option['price'] ) ? (float) wc_format_decimal( $option['price'] ) : 0.0;

					if ( '' === $opt_label ) {
						continue;
					}

					$options[] = [
						'label' => $opt_label,
						'price' => $opt_price,
					];
				}
			}

			if ( empty( $options ) ) {
				continue;
			}

			$result[] = [
				'label'    => $label,
				'required' => $required,
				'options'  => $options,
			];
		}

		return $result;
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
		$raw_tiers       = isset( $_POST['tpfw_pricing_table'] ) ? wp_unslash( $_POST['tpfw_pricing_table'] ) : [];
		$raw_dropdowns   = isset( $_POST['tpfw_custom_dropdowns'] ) ? wp_unslash( $_POST['tpfw_custom_dropdowns'] ) : [];

		$tiers     = $this->sanitize_pricing_table( $raw_tiers );
		$dropdowns = $this->sanitize_custom_dropdowns( $raw_dropdowns );

		update_post_meta( $product_id, '_tpfw_enable_pricing_table', $enabled );
		update_post_meta( $product_id, '_tpfw_pricing_note', $pricing_note );
		update_post_meta( $product_id, '_tpfw_themes', $themes );
		update_post_meta( $product_id, '_tpfw_imprint', $imprint );
		update_post_meta( $product_id, '_tpfw_additional', $additional );
		update_post_meta( $product_id, '_tpfw_options', $options );
		update_post_meta( $product_id, '_tpfw_delivery', $delivery );
		update_post_meta( $product_id, '_tpfw_pricing_table', $tiers );
		update_post_meta( $product_id, '_tpfw_custom_dropdowns', $dropdowns );

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
