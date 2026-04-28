<?php
namespace TieredPricingForWooCommerce\Frontend;

defined( 'ABSPATH' ) || exit;

class ProductInfo {

	/**
	 * Product meta mapped to WooCommerce tabs.
	 *
	 * @var array<string, string>
	 */
	private $fields = [
		'themes'     => '_tpfw_themes',
		'imprint'    => '_tpfw_imprint',
		'additional' => '_tpfw_additional',
		'options'    => '_tpfw_options',
		'delivery'   => '_tpfw_delivery',
	];

	public function __construct() {
		add_filter( 'woocommerce_product_tabs', [ $this, 'register_product_tabs' ], 30 );
	}

	/**
	 * Add custom product info tabs for non-empty meta values.
	 *
	 * @param array $tabs Existing WooCommerce tabs.
	 * @return array
	 */
	public function register_product_tabs( array $tabs ): array {
		if ( ! is_product() ) {
			return $tabs;
		}

		$priority = 40;

		foreach ( $this->fields as $slug => $meta_key ) {
			$value = get_post_meta( get_the_ID(), $meta_key, true );

			if ( '' === trim( (string) $value ) ) {
				continue;
			}

			$tabs[ 'tpfw_' . $slug ] = [
				'title'    => $this->get_tab_title( $slug ),
				'priority' => $priority,
				'callback' => function() use ( $meta_key ): void {
					$this->render_tab_content( $meta_key );
				},
			];

			$priority += 10;
		}

		return $tabs;
	}

	/**
	 * Resolve tab titles.
	 *
	 * @param string $slug Tab slug.
	 * @return string
	 */
	private function get_tab_title( string $slug ): string {
		$titles = [
			'themes'     => __( 'Themes', 'tiered-pricing-for-woocommerce' ),
			'imprint'    => __( 'Imprint', 'tiered-pricing-for-woocommerce' ),
			'additional' => __( 'Additional', 'tiered-pricing-for-woocommerce' ),
			'options'    => __( 'Options', 'tiered-pricing-for-woocommerce' ),
			'delivery'   => __( 'Delivery', 'tiered-pricing-for-woocommerce' ),
		];

		return $titles[ $slug ] ?? ucfirst( $slug );
	}

	/**
	 * Render tab content with paragraph formatting preserved.
	 *
	 * @param string $meta_key Product meta key.
	 * @return void
	 */
	private function render_tab_content( string $meta_key ): void {
		$value = get_post_meta( get_the_ID(), $meta_key, true );

		if ( '' === trim( (string) $value ) ) {
			return;
		}

		echo wp_kses_post( wpautop( esc_html( (string) $value ) ) );
	}
}
