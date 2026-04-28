<?php
namespace TieredPricingForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

class ProductColors {

	/**
	 * Register product color taxonomy hooks.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
	}

	/**
	 * Register the Colors taxonomy for products.
	 */
	public function register_taxonomy(): void {
		$labels = [
			'name'              => esc_html__( 'Colors', 'tiered-pricing-for-woocommerce' ),
			'singular_name'     => esc_html__( 'Color', 'tiered-pricing-for-woocommerce' ),
			'search_items'      => esc_html__( 'Search Colors', 'tiered-pricing-for-woocommerce' ),
			'all_items'         => esc_html__( 'All Colors', 'tiered-pricing-for-woocommerce' ),
			'edit_item'         => esc_html__( 'Edit Color', 'tiered-pricing-for-woocommerce' ),
			'update_item'       => esc_html__( 'Update Color', 'tiered-pricing-for-woocommerce' ),
			'add_new_item'      => esc_html__( 'Add New Color', 'tiered-pricing-for-woocommerce' ),
			'new_item_name'     => esc_html__( 'New Color Name', 'tiered-pricing-for-woocommerce' ),
			'menu_name'         => esc_html__( 'Colors', 'tiered-pricing-for-woocommerce' ),
		];

		register_taxonomy(
			'product_color',
			[ 'product' ],
			[
				'labels'            => $labels,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_quick_edit' => false,
				'hierarchical'      => false,
				'rewrite'           => false,
				'show_in_rest'      => true,
			]
		);
	}
}
