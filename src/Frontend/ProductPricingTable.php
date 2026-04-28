<?php
namespace TieredPricingForWooCommerce\Frontend;

defined( 'ABSPATH' ) || exit;

class ProductPricingTable {

	public function __construct() {
		add_action( 'woocommerce_single_product_summary', [ $this, 'render_tier_table' ], 25 );
	}

	/**
	 * Render a starter pricing table on single product pages.
	 */
	public function render_tier_table(): void {
		if ( ! is_product() || 'yes' !== get_option( 'tpfw_enable_frontend_display', 'yes' ) ) {
			return;
		}

		global $product;

		if ( ! $product || 'yes' !== get_post_meta( $product->get_id(), '_tpfw_enabled', true ) ) {
			return;
		}

		$rules = get_post_meta( $product->get_id(), '_tpfw_tier_rules', true );

		if ( empty( $rules ) ) {
			return;
		}

		$tiers = $this->parse_rules( $rules );

		if ( empty( $tiers ) ) {
			return;
		}
		?>
		<div class="tpfw-tier-table-wrapper">
			<h3><?php esc_html_e( 'Tiered Pricing', 'tiered-pricing-for-woocommerce' ); ?></h3>
			<table class="shop_table tpfw-tier-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Minimum Quantity', 'tiered-pricing-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Unit Price', 'tiered-pricing-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tiers as $tier ) : ?>
						<tr>
							<td><?php echo esc_html( $tier['quantity'] ); ?></td>
							<td><?php echo wp_kses_post( wc_price( $tier['price'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Parse textarea rules into tier rows.
	 *
	 * @param string $rules Raw rules from product meta.
	 * @return array<int, array<string, float|int>>
	 */
	private function parse_rules( string $rules ): array {
		$tiers = [];
		$lines = preg_split( '/\r\n|\r|\n/', $rules );

		if ( ! is_array( $lines ) ) {
			return $tiers;
		}

		foreach ( $lines as $line ) {
			$parts = array_map( 'trim', explode( ':', $line ) );

			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$quantity = absint( $parts[0] );
			$price    = wc_format_decimal( $parts[1] );

			if ( $quantity < 1 || '' === $price ) {
				continue;
			}

			$tiers[] = [
				'quantity' => $quantity,
				'price'    => (float) $price,
			];
		}

		return $tiers;
	}
}
