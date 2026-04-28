<?php
namespace TieredPricingForWooCommerce\Admin;

use TieredPricingForWooCommerce\Core\Base\Settings as BaseSettings;

defined( 'ABSPATH' ) || exit;
/**
 * The main settings class for the plugin.
 */
class Settings extends BaseSettings {
	/**
	 * Settings constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_admin_settings_sanitize_option', [ $this, 'sanitize_option' ], 10, 3 );
	}
	/**
	 * Sanitize the option values.
	 *
	 * @param mixed      $value     The sanitized value (default from WooCommerce).
	 * @param array      $option    The option array.
	 * @param mixed      $raw_value The raw submitted value.
	 *
	 * @return mixed The sanitized value.
	 *@since 1.0.0
	 */
	public function sanitize_option( $value, array $option, $raw_value ) {
		if ( empty( $option['id'] ) || 0 !== strpos( $option['id'], 'tpfw_' ) ) {
			return $value;
		}

		$type = $option['type'] ?? 'text';

		switch ( $type ) {
			case 'checkbox':
				return ( isset( $raw_value ) && '1' === $raw_value ) ? 'yes' : 'no';
			case 'textarea':
				return sanitize_textarea_field( $raw_value );
			case 'number':
				return absint( $raw_value );
			default:
				return sanitize_text_field( $raw_value );
		}
	}


    /**
	 * Demonstrate tabs for the plugin settings
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_tabs(): array {
		return [
			'general'  => esc_html__( 'General', 'tiered-pricing-for-woocommerce' ),
			'advanced' => esc_html__( 'Advanced', 'tiered-pricing-for-woocommerce' ),
		];
	}

	/**
	 * Display tab content
	 *
	 * @param string $tab
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings( string $tab ): array {
		$settings = [];

		switch ( $tab ) {
			case 'general':
				$settings = [
					array(
						'title' => esc_html__( 'General Settings', 'tiered-pricing-for-woocommerce' ),
						'type'  => 'title',
						'desc'  => esc_html__( 'Starter options for future tiered-pricing development.', 'tiered-pricing-for-woocommerce' ),
						'id'    => 'tpfw_general_settings',
					),
					array(
						'title'   => esc_html__( 'Enable Starter Hooks', 'tiered-pricing-for-woocommerce' ),
						'desc'    => esc_html__( 'Loads the product settings fields and WooCommerce starter integration.', 'tiered-pricing-for-woocommerce' ),
						'id'      => 'tpfw_enable_plugin',
						'type'    => 'checkbox',
						'default' => 'yes',
					),
					array(
						'title'   => esc_html__( 'Enable Frontend Table', 'tiered-pricing-for-woocommerce' ),
						'desc'    => esc_html__( 'Displays a starter tier table on eligible single product pages.', 'tiered-pricing-for-woocommerce' ),
						'id'      => 'tpfw_enable_frontend_display',
						'type'    => 'checkbox',
						'default' => 'yes',
					),
					[
						'type' => 'sectionend',
						'id'   => 'tpfw_general_settings',
					],
				];
				break;

			case 'advanced':
				$settings = [
					[
						'title' => esc_html__( 'Advanced Settings', 'tiered-pricing-for-woocommerce' ),
						'type'  => 'title',
						'id'    => 'tpfw_advanced_settings',
					],
					[
						'title'   => esc_html__( 'Remove All Data on Uninstall', 'tiered-pricing-for-woocommerce' ),
						'desc'    => esc_html__( 'If enabled, starter plugin options and product meta will be removed during uninstall.', 'tiered-pricing-for-woocommerce' ),
						'id'      => 'tpfw_delete_data',
						'type'    => 'checkbox',
						'default' => 'no',
					],
					[
						'type' => 'sectionend',
						'id'   => 'tpfw_advanced_settings',
					],
				];
				break;
		}
		return $settings;
	}

	/**
	 * Renders the entire settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 * */
	public function output(): void
	{
		parent::output();
	}

	/**
	 * Overrides the parent method to add a documentation link.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_extra_tabs(): array {
		$extra_tabs = [];
		$docs_url = TPFW()->docs_url;
		if ( $docs_url ) {
			$extra_tabs['documentation'] = [
				'label' => esc_html__( 'Documentation', 'tiered-pricing-for-woocommerce' ),
				'url'   => esc_url( $docs_url ),
			];
		}
		return $extra_tabs;
	}
}
