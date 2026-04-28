<?php
namespace TieredPricingForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

class WooCommerceStarter {

	/**
	 * Register starter WooCommerce integration hooks.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . plugin_basename( TPFW_PLUGIN_FILE ), [ $this, 'add_action_links' ] );
		add_action( 'woocommerce_init', [ $this, 'register_feature_notice' ] );
	}

	/**
	 * Add quick links on the plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=tiered-pricing-for-woocommerce' ) ),
			esc_html__( 'Settings', 'tiered-pricing-for-woocommerce' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Register a minimal placeholder note for future feature work.
	 */
	public function register_feature_notice(): void {
		do_action( 'tpfw_loaded' );
	}
}
