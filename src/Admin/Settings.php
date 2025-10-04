<?php
namespace WineVendorWooCommerce\Admin;
use WineVendorWooCommerce\Core\Base\Settings as BaseSettings;
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
        // Only sanitize our own options.
        if ( empty( $option['id'] ) || strpos( $option['id'], 'wvwc_' ) !== 0 ) {
            return $value;
        }

        $type = isset( $option['type'] ) ? $option['type'] : 'text';

        // Sanitize color fields.
        if ( $type === 'text' && ! empty( $option['class'] ) && $option['class'] === 'color-picker' ) {
            return sanitize_hex_color( $raw_value );
        }

        switch ( $type ) {
            case 'number':
                return intval( $raw_value );

            case 'multiselect':
                return is_array( $raw_value ) ? array_map( 'sanitize_text_field', $raw_value ) : [];

            case 'checkbox':
                return ( isset( $raw_value ) && $raw_value === '1' ) ? 'yes' : 'no';

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
			'general'  				=> esc_html__( 'General', 'wine-vendor-woocommerce' ),
			'advanced' 				=> esc_html__( 'Advanced', 'wine-vendor-woocommerce' ),
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
						'title' => esc_html__( 'General Settings', 'wine-vendor-woocommerce' ),
						'type'  => 'title',
						'id'    => 'wvwc_general_settings',
					),
					array(
						'title'   => esc_html__( 'Enable Wine Vendor', 'wine-vendor-woocommerce' ),
						'desc'    => esc_html__( 'Check this to enable to disable the wine vendor facilities.', 'wine-vendor-woocommerce' ),
						'id'      => 'wvwc_disable_auto_approval',
						'type'    => 'checkbox',
						'default' => 'no',
					),
					[
						'type' => 'sectionend',
						'id'   => 'wvwc_general_settings',
					],
				];
				break;

			case 'advanced':
				$settings = [
					[
						'title' 	=> esc_html__( 'Advanced Settings', 'wine-vendor-woocommerce' ),
						'type'  	=> 'title',
						'id'    	=> 'wvwc_advanced_settings',
					],
					[
						'title'   	=> esc_html__( 'Remove All Data on Uninstall', 'wine-vendor-woocommerce' ),
						'desc'    	=> esc_html__( 'If enabled, all plugin data will be removed when you uninstall the plugin.', 'wine-vendor-woocommerce' ),
						'id'      	=> 'wvwc_delete_data',
						'type'    	=> 'checkbox',
						'default' 	=> 'no',
					],
					[
						'type' 		=> 'sectionend',
						'id'   		=> 'wvwc_advanced_settings',
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
		$docs_url = WVWC()->docs_url;
		if ( $docs_url ) {
			$extra_tabs['documentation'] = [
				'label' => esc_html__( 'Documentation', 'wine-vendor-woocommerce' ),
				'url'   => esc_url( $docs_url ),
			];
		}
		return $extra_tabs;
	}
}
