<?php
namespace WineVendorWooCommerce\Admin;

// Don't call the file directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles general admin-area modifications.
 */
class Admin {

	/**
	* The main plugin instance.
	*/
	private $plugin;

	/**
	 * Constructor. Hooks into WordPress.
	 */
	public function __construct() {
		$this->plugin = WVWC();

		add_filter( 'woocommerce_screen_ids', [ $this, 'add_screen_ids' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ], PHP_INT_MAX );
		add_filter( 'update_footer', [ $this, 'update_footer' ], PHP_INT_MAX );
	}

	/**
	 * Defines the screen IDs for our plugin's admin pages.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_screen_ids(): array {
		$menu_slug = $this->plugin->get_menu_slug();
		if ( ! $menu_slug ) {
			return [];
		}
		return [ 'woocommerce_page_' . $menu_slug ];
	}

	/**
	 * Adds our screen IDs to the list of WooCommerce screens.
	 * This ensures WooCommerce loads its default styles and scripts on our page.
	 *
	 * @param array $ids An array of existing screen IDs.
	 *
	 * @since 1.0.0
	 * @return array The merged array of screen IDs.
	 */
	public function add_screen_ids( array $ids ): array {
		return array_merge( $ids, $this->get_screen_ids() );
	}

	/**
	 * Modifies the admin footer text on our settings page.
	 *
	 * @param string $text The original footer text.
	 *
	 * @since 1.0.0
	 * @return string The modified footer text.
	 */
	public function admin_footer_text( string $text ): string {
		if ( in_array( get_current_screen()->id, $this->get_screen_ids(), true ) ) {
			$plugin_data = get_plugin_data( WVWC_PLUGIN_FILE );
			$plugin_name = $plugin_data['Name'];

			$text = sprintf(
			/* translators: %s: Plugin name */
				wp_kses( __( 'Thank you for using %s!', 'wine-vendor-woocommerce' ), [ 'strong' => [] ] ),
				'<strong>' . esc_html( $plugin_name ) . '</strong>'
			);
		}
		return $text;
	}

	/**
	 * Modifies the footer text to show the plugin version.
	 *
	 * @param string $text The original footer text.
	 *
	 * @since 1.0.0
	 * @return string The modified footer text.
	 */
	public function update_footer( string $text ): string {

		if ( in_array( get_current_screen()->id, $this->get_screen_ids(), true ) ) {
			$plugin_data = get_plugin_data( WVWC_PLUGIN_FILE );
			$version = $plugin_data['Version'];

			/* translators: %s: Plugin version number */
			$text = sprintf( esc_html__( 'Version %s', 'wine-vendor-woocommerce' ), $version );
		}
		return $text;
	}

	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		// Only load the assets on our specific admin page
		if ( in_array( get_current_screen()->id, $this->get_screen_ids(), true ) ) {
			wp_enqueue_style( 'wvwc-admin-style', plugins_url( 'assets/css/admin.css', WVWC_PLUGIN_FILE ), [], WVWC_VERSION );
			wp_enqueue_script( 'wvwc-admin-script', plugins_url( 'assets/js/admin.js', WVWC_PLUGIN_FILE ), ['jquery'],
				WVWC_VERSION, true );
		}
	}
}
