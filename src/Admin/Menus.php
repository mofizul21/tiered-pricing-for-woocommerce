<?php

namespace WineVendorWooCommerce\Admin;

defined('ABSPATH') || exit;

/**
 * Handles the creation of the admin menu.
 */
class Menus {

	/**
	 * The instance of the Settings class.
	 *
	 * @var Settings
	 */
	private $settings_page;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_page = new Settings();
		//add_action( 'admin_menu', [ $this, 'add_submenu_page' ] );
	}

	/**
	 * Adds the submenu page under the "WooCommerce" menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_submenu_page(): void {
		$menu_slug = WVWC()->get_menu_slug();
		if ( ! $menu_slug ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			esc_html__( 'Wine Vendor Settings', 'wine-vendor-woocommerce' ),
			esc_html__( 'Wine Vendor', 'wine-vendor-woocommerce' ),
			'manage_woocommerce',
			$menu_slug,
			[ $this->settings_page, 'output' ]
		);
	}
}
