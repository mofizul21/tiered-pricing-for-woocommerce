<?php
namespace TieredPricingForWooCommerce\Frontend;

use WC_Product;

defined( 'ABSPATH' ) || exit;

class ProductPricingTable {

	/**
	 * Register frontend product pricing hooks.
	 */
	public function __construct() {
		add_action( 'wp', [ $this, 'maybe_replace_single_product_ui' ] );
		add_shortcode( 'tpfw_pricing_table', [ $this, 'render_shortcode' ] );
		add_action( 'wp_loaded', [ $this, 'handle_custom_add_to_cart' ] );
		add_filter( 'woocommerce_get_price_html', [ $this, 'maybe_hide_single_price' ], 10, 2 );
		add_filter( 'woocommerce_get_item_data', [ $this, 'render_cart_item_meta' ], 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_cart_item_prices' ] );
	}

	/**
	 * Remove the standard WooCommerce single-product purchase UI.
	 */
	public function maybe_replace_single_product_ui(): void {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! $product instanceof WC_Product || ! $this->has_custom_pricing( $product->get_id() ) ) {
			return;
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}

	/**
	 * Hide the regular price HTML on the custom-priced single product page.
	 *
	 * @param string     $price_html Existing price HTML.
	 * @param WC_Product $product    Product object.
	 * @return string
	 */
	public function maybe_hide_single_price( string $price_html, WC_Product $product ): string {
		if ( is_admin() || ! is_product() ) {
			return $price_html;
		}

		if ( get_queried_object_id() === $product->get_id() && $this->has_custom_pricing( $product->get_id() ) ) {
			return '';
		}

		return $price_html;
	}

	/**
	 * Render the pricing table and custom order builder.
	 */
	public function render_shortcode(): string {
		if ( ! is_product() ) {
			return '';
		}

		global $product;

		if ( ! $product instanceof WC_Product || ! $this->has_custom_pricing( $product->get_id() ) ) {
			return '';
		}

		$tiers      = $this->get_pricing_table( $product->get_id() );
		$colors     = $this->get_product_colors( $product->get_id() );
		$note       = get_post_meta( $product->get_id(), '_tpfw_pricing_note', true );
		$min_qty    = $tiers[0]['quantity'];
		$tiers_json = wp_json_encode( $tiers );

		ob_start();
		?>
		<div class="tpfw-pricing-interface" data-min-quantity="<?php echo esc_attr( (string) $min_qty ); ?>" data-pricing-table="<?php echo esc_attr( $tiers_json ? $tiers_json : '[]' ); ?>" data-currency-symbol="<?php echo esc_attr( html_entity_decode( get_woocommerce_currency_symbol() ) ); ?>">
			<div class="tpfw-pricing-table-wrapper">
				<table class="shop_table tpfw-tier-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Quantity', 'tiered-pricing-for-woocommerce' ); ?></th>
							<?php foreach ( $tiers as $tier ) : ?>
								<th><?php echo esc_html( number_format_i18n( (int) $tier['quantity'] ) ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th><?php esc_html_e( 'Your Price (each)', 'tiered-pricing-for-woocommerce' ); ?></th>
							<?php foreach ( $tiers as $tier ) : ?>
								<td><?php echo wp_kses_post( wc_price( $tier['price'] ) ); ?></td>
							<?php endforeach; ?>
						</tr>
					</tbody>
				</table>
			</div>

			<?php if ( ! empty( $note ) ) : ?>
				<div class="tpfw-pricing-note"><?php echo wp_kses_post( wpautop( esc_html( (string) $note ) ) ); ?></div>
			<?php endif; ?>

			<?php if ( empty( $colors ) ) : ?>
				<p class="tpfw-order-help"><?php esc_html_e( 'Assign at least one Color term to this product to enable ordering from the pricing table.', 'tiered-pricing-for-woocommerce' ); ?></p>
			<?php else : ?>
				<form class="tpfw-order-form" method="post" action="">
					<?php wp_nonce_field( 'tpfw_add_to_cart', 'tpfw_nonce' ); ?>
					<input type="hidden" name="tpfw_add_to_cart" value="1">
					<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">

					<table class="tpfw-order-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Qty*', 'tiered-pricing-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Color', 'tiered-pricing-for-woocommerce' ); ?></th>
							</tr>
						</thead>
						<tbody class="tpfw-order-rows">
							<?php $this->render_order_row( 0, $colors, $min_qty ); ?>
						</tbody>
					</table>

					<p class="tpfw-order-help">
						<?php
						printf(
							/* translators: %s: minimum quantity */
							esc_html__( 'Minimum total quantity for this product is %s across all rows.', 'tiered-pricing-for-woocommerce' ),
							esc_html( number_format_i18n( $min_qty ) )
						);
						?>
					</p>

					<button type="submit" class="single_add_to_cart_button button alt"><?php esc_html_e( 'Add to cart', 'tiered-pricing-for-woocommerce' ); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<script>
		(function () {
			function tpfwEnsureRow( form ) {
				var rows = Array.prototype.slice.call( form.querySelectorAll( '.tpfw-order-row' ) );
				if ( ! rows.length ) return;
				var lastQty = ( rows[ rows.length - 1 ].querySelector( '.tpfw-qty-input' ).value || '' ).trim();
				var hasBlank = rows.some( function ( row ) {
					return ! ( row.querySelector( '.tpfw-qty-input' ).value || '' ).trim() &&
					       ! ( row.querySelector( '.tpfw-color-select' ).value || '' ).trim();
				} );
				if ( lastQty && ! hasBlank ) {
					var tbody = form.querySelector( '.tpfw-order-rows' );
					var allRows = tbody.querySelectorAll( '.tpfw-order-row' );
					var last   = allRows[ allRows.length - 1 ];
					var idx    = allRows.length;
					var clone  = last.cloneNode( true );
					clone.querySelector( '.tpfw-qty-input' ).value = '';
					clone.querySelector( '.tpfw-qty-input' ).name  = 'tpfw_order_rows[' + idx + '][quantity]';
					clone.querySelector( '.tpfw-color-select' ).value = '';
					clone.querySelector( '.tpfw-color-select' ).name  = 'tpfw_order_rows[' + idx + '][color]';
					tbody.appendChild( clone );
				}
			}
			window.tpfwQtyChanged   = function ( el ) { var f = el.closest( '.tpfw-order-form' ); if ( f ) tpfwEnsureRow( f ); };
			window.tpfwColorChanged = function ( el ) { var f = el.closest( '.tpfw-order-form' ); if ( f ) tpfwEnsureRow( f ); };

			var forms = document.querySelectorAll( '.tpfw-order-form:not([data-tpfw-submit])' );
			for ( var fi = 0; fi < forms.length; fi++ ) {
				forms[ fi ].setAttribute( 'data-tpfw-submit', '1' );
				forms[ fi ].addEventListener( 'submit', function ( e ) {
					var form         = e.currentTarget;
					var iface        = form.closest( '.tpfw-pricing-interface' );
					var minQty       = iface ? ( parseInt( iface.getAttribute( 'data-min-quantity' ), 10 ) || 0 ) : 0;
					var rows         = Array.prototype.slice.call( form.querySelectorAll( '.tpfw-order-row' ) );
					var total        = 0;
					var hasQty       = false;
					var missingColor = false;

					// Validate only — do NOT touch the DOM yet.
					for ( var i = 0; i < rows.length; i++ ) {
						var qty   = ( rows[ i ].querySelector( '.tpfw-qty-input' ).value || '' ).trim();
						var color = ( rows[ i ].querySelector( '.tpfw-color-select' ).value || '' ).trim();
						if ( ! qty && ! color ) { continue; }
						var n = parseInt( qty, 10 );
						if ( ! isNaN( n ) && n > 0 ) {
							hasQty = true;
							total += n;
							if ( ! color ) { missingColor = true; }
						}
					}

					if ( ! hasQty ) {
						e.preventDefault();
						window.alert( 'Please fill out the Qty.' );
						return;
					}
					if ( missingColor ) {
						e.preventDefault();
						window.alert( 'Please select a color for each row.' );
						return;
					}
					if ( total < minQty ) {
						e.preventDefault();
						window.alert( 'The total quantity must be at least ' + minQty + '.' );
						return;
					}

					// All checks passed — strip blank rows before the form submits.
					for ( var j = 0; j < rows.length; j++ ) {
						var q = ( rows[ j ].querySelector( '.tpfw-qty-input' ).value || '' ).trim();
						var c = ( rows[ j ].querySelector( '.tpfw-color-select' ).value || '' ).trim();
						if ( ! q && ! c ) {
							rows[ j ].parentNode && rows[ j ].parentNode.removeChild( rows[ j ] );
						}
					}
				} );
			}
		})();
		</script>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render one frontend order row.
	 *
	 * @param int|string $index   Row index.
	 * @param array      $colors  Allowed colors.
	 * @param int        $min_qty Minimum quantity.
	 */
	private function render_order_row( $index, array $colors, int $min_qty ): void {
		?>
		<tr class="tpfw-order-row">
			<td class="tpfw-order-field">
				<input type="number" name="tpfw_order_rows[<?php echo esc_attr( (string) $index ); ?>][quantity]" min="1" step="1" class="tpfw-qty-input" inputmode="numeric" oninput="tpfwQtyChanged(this)" onchange="tpfwQtyChanged(this)">
			</td>

			<td class="tpfw-order-field">
				<select name="tpfw_order_rows[<?php echo esc_attr( (string) $index ); ?>][color]" class="tpfw-color-select" onchange="tpfwColorChanged(this)">
					<option value=""><?php esc_html_e( 'Select color', 'tiered-pricing-for-woocommerce' ); ?></option>
					<?php foreach ( $colors as $color ) : ?>
						<option value="<?php echo esc_attr( (string) $color['id'] ); ?>"><?php echo esc_html( $color['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Process custom add-to-cart rows.
	 */
	public function handle_custom_add_to_cart(): void {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['tpfw_add_to_cart'] ) ) {
			return;
		}

		$nonce = isset( $_POST['tpfw_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tpfw_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tpfw_add_to_cart' ) ) {
			wc_add_notice( esc_html__( 'Unable to add this product to the cart. Please refresh and try again.', 'tiered-pricing-for-woocommerce' ), 'error' );
			return;
		}

		$product_id = isset( $_POST['add-to-cart'] ) ? absint( wp_unslash( $_POST['add-to-cart'] ) ) : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product instanceof WC_Product || ! $this->has_custom_pricing( $product_id ) ) {
			wc_add_notice( esc_html__( 'This product is not available for custom pricing.', 'tiered-pricing-for-woocommerce' ), 'error' );
			return;
		}

		$tiers     = $this->get_pricing_table( $product_id );
		$min_qty   = $tiers[0]['quantity'];
		$colors    = $this->get_product_colors( $product_id );
		$color_map = [];

		foreach ( $colors as $color ) {
			$color_map[ $color['id'] ] = $color['name'];
		}

		$rows = isset( $_POST['tpfw_order_rows'] ) ? wp_unslash( $_POST['tpfw_order_rows'] ) : [];

		if ( ! is_array( $rows ) ) {
			$rows = [];
		}

		$valid_rows     = [];
		$total_quantity = 0;

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$quantity = isset( $row['quantity'] ) ? absint( $row['quantity'] ) : 0;
			$color_id = isset( $row['color'] ) ? absint( $row['color'] ) : 0;

			if ( 0 === $quantity && 0 === $color_id ) {
				continue;
			}

			if ( $quantity < 1 ) {
				wc_add_notice( esc_html__( 'Each order row must have a quantity greater than zero.', 'tiered-pricing-for-woocommerce' ), 'error' );
				return;
			}

			if ( empty( $color_map[ $color_id ] ) ) {
				wc_add_notice( esc_html__( 'Please choose a valid color for each order row.', 'tiered-pricing-for-woocommerce' ), 'error' );
				return;
			}

			$valid_rows[] = [
				'quantity'   => $quantity,
				'color_id'   => $color_id,
				'color_name' => $color_map[ $color_id ],
			];
			$total_quantity += $quantity;
		}

		if ( empty( $valid_rows ) ) {
			wc_add_notice( esc_html__( 'Please add at least one quantity and color combination.', 'tiered-pricing-for-woocommerce' ), 'error' );
			return;
		}

		if ( $total_quantity < $min_qty ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: total quantity, 2: minimum quantity */
					esc_html__( 'Minimum total quantity is %2$s. Your current total is %1$s.', 'tiered-pricing-for-woocommerce' ),
					esc_html( number_format_i18n( $total_quantity ) ),
					esc_html( number_format_i18n( $min_qty ) )
				),
				'error'
			);
			return;
		}

		$unit_price = $this->get_price_for_quantity( $total_quantity, $tiers );

		if ( null === $unit_price ) {
			wc_add_notice( esc_html__( 'Unable to match the total quantity to a pricing tier.', 'tiered-pricing-for-woocommerce' ), 'error' );
			return;
		}

		foreach ( $valid_rows as $index => $row ) {
			$added = WC()->cart->add_to_cart(
				$product_id,
				$row['quantity'],
				0,
				[],
				[
					'tpfw_color_id'   => $row['color_id'],
					'tpfw_color_name' => $row['color_name'],
					'tpfw_unit_price' => $unit_price,
					'tpfw_total_qty'  => $total_quantity,
					'tpfw_row_key'    => md5( $product_id . '|' . $row['color_id'] . '|' . $row['quantity'] . '|' . $index . '|' . wp_rand() ),
				]
			);

			if ( ! $added ) {
				wc_add_notice( esc_html__( 'A row could not be added to the cart.', 'tiered-pricing-for-woocommerce' ), 'error' );
				return;
			}
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * Force custom unit prices in the cart.
	 *
	 * @param \WC_Cart $cart WooCommerce cart instance.
	 */
	public function apply_cart_item_prices( $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! $cart || ! method_exists( $cart, 'get_cart' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['tpfw_unit_price'] ) || empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
				continue;
			}

			$cart_item['data']->set_price( (float) $cart_item['tpfw_unit_price'] );
		}
	}

	/**
	 * Show color and unit price in the cart item meta.
	 *
	 * @param array $item_data Existing item data.
	 * @param array $cart_item Cart item data.
	 * @return array
	 */
	public function render_cart_item_meta( array $item_data, array $cart_item ): array {
		if ( ! empty( $cart_item['tpfw_color_name'] ) ) {
			$item_data[] = [
				'key'   => esc_html__( 'Color', 'tiered-pricing-for-woocommerce' ),
				'value' => wc_clean( $cart_item['tpfw_color_name'] ),
			];
		}

		return $item_data;
	}

	/**
	 * Check whether a product should use the custom pricing flow.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function has_custom_pricing( int $product_id ): bool {
		return 'yes' === get_post_meta( $product_id, '_tpfw_enable_pricing_table', true ) && ! empty( $this->get_pricing_table( $product_id ) );
	}

	/**
	 * Get sanitized tier rows.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, float|int>>
	 */
	private function get_pricing_table( int $product_id ): array {
		$tiers = get_post_meta( $product_id, '_tpfw_pricing_table', true );

		return is_array( $tiers ) ? $tiers : [];
	}

	/**
	 * Get matching price for a quantity using the highest eligible tier.
	 *
	 * @param int   $quantity Quantity entered.
	 * @param array $tiers    Tier rows.
	 * @return float|null
	 */
	private function get_price_for_quantity( int $quantity, array $tiers ): ?float {
		$matched = null;

		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['quantity'], $tier['price'] ) ) {
				continue;
			}

			if ( $quantity >= (int) $tier['quantity'] ) {
				$matched = (float) $tier['price'];
			}
		}

		return $matched;
	}

	/**
	 * Get assigned product colors.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array<string, int|string>>
	 */
	private function get_product_colors( int $product_id ): array {
		$terms = get_the_terms( $product_id, 'product_color' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		$colors = [];

		foreach ( $terms as $term ) {
			$colors[] = [
				'id'   => (int) $term->term_id,
				'name' => $term->name,
			];
		}

		return $colors;
	}
}
