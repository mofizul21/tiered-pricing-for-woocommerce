<?php
namespace TieredPricingForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

class ProductSettings {

	/**
	 * Register starter product setting hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_pricing', [ $this, 'render_product_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_fields' ] );
	}

	/**
	 * Render starter product fields.
	 */
	public function render_product_fields(): void {
		if ( 'yes' !== get_option( 'tpfw_enable_plugin', 'yes' ) ) {
			return;
		}

		echo '<div class="options_group">';

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_themes',
				'label'       => esc_html__( 'Themes', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Starter text field for product themes.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_imprint',
				'label'       => esc_html__( 'Imprint', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Starter text field for imprint information.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_additional',
				'label'       => esc_html__( 'Additional', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Starter text field for additional product details.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_options',
				'label'       => esc_html__( 'Options', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Starter text field for product options.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		woocommerce_wp_textarea_input(
			[
				'id'          => '_tpfw_delivery',
				'label'       => esc_html__( 'Delivery', 'tiered-pricing-for-woocommerce' ),
				'description' => esc_html__( 'Starter text field for delivery information.', 'tiered-pricing-for-woocommerce' ),
				'desc_tip'    => true,
			]
		);

		echo '</div>';
	}

	/**
	 * Save starter product fields.
	 *
	 * @param int $product_id Product ID.
	 */
	public function save_product_fields( int $product_id ): void {
		$themes     = isset( $_POST['_tpfw_themes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_themes'] ) ) : '';
		$imprint    = isset( $_POST['_tpfw_imprint'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_imprint'] ) ) : '';
		$additional = isset( $_POST['_tpfw_additional'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_additional'] ) ) : '';
		$options    = isset( $_POST['_tpfw_options'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_options'] ) ) : '';
		$delivery   = isset( $_POST['_tpfw_delivery'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_tpfw_delivery'] ) ) : '';

		update_post_meta( $product_id, '_tpfw_themes', $themes );
		update_post_meta( $product_id, '_tpfw_imprint', $imprint );
		update_post_meta( $product_id, '_tpfw_additional', $additional );
		update_post_meta( $product_id, '_tpfw_options', $options );
		update_post_meta( $product_id, '_tpfw_delivery', $delivery );
	}
}
