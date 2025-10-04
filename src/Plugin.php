<?php
namespace WineVendorWooCommerce;

defined( 'ABSPATH' ) || exit;

use WineVendorWooCommerce\Admin\Admin;
use WineVendorWooCommerce\Admin\Menus;
use WineVendorWooCommerce\Core\WineVendorProducts;
use WineVendorWooCommerce\Core\WineVendorWoo;
use WineVendorWooCommerce\Frontend\CartCustomizer;

/**
 * Plugin class
 *
 * @since 1.0.0
 */
final class Plugin {

    /**
     * The single instance of the class.
     *
     * @var Plugin|null
     *
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * The plugin's configuration data.
     *
     * @var array
     *
     * @since 1.0.0
     */
    private $data = [];

    /**
     * Get the single instance of the class.
     *
     * @param array $data Optional. Configuration data to initialize with.
     *
     * @since 1.0.0
     * @return Plugin
     */
    public static function instance( array $data = [] ): Plugin {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self( $data );
        }
        return self::$instance;
    }

    /**
     * Run the plugin's initialization hooks.
     *
     * @param array $data
     *
     * @since 1.0.0
     * @return void
     */
    public function init( array $data ): void {
        $this->data = $data;

        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            return;
        }

        add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
        add_filter( 'woocommerce_email_classes', [ $this, 'add_email_class' ] );
        $this->load_textdomain();

        // Load classes for admin only
        if(is_admin()){
            new Menus();
            new Admin();
			new WineVendorProducts();
			new WineVendorWoo();
        }
        new CartCustomizer();
    }

    /**
     * Magic method to get data from the $data array.
     * This allows accessing $this->data['key'] as $this->key.
     *
     * @param string $key The key to retrieve.
     *
     * @since 1.0.0
     * @return mixed|null The value or null if not found.
     */
    public function __get( string $key ) {
        return $this->data[ $key ] ?? null;
    }

    /**
     * It parses the settings_url to get the menu slug.
     *
     * @since 1.0.0
     * @return string|null
     */
    public function get_menu_slug(): ?string {
        $url = $this->settings_url;
        if ( ! $url ) {
            return null;
        }

        parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query_params );

        return $query_params['page'] ?? null;
    }

    /**
     * Make compatible with WooCommerce
     *
     * @since 1.0.0
     * @return void
     */
    public function declare_hpos_compatibility(): void {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WVWC_PLUGIN_FILE, true );
        }
    }

    /**
     * Load text-domain
     *
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain( 'wine-vendor-woocommerce', false, dirname( plugin_basename( WVWC_PLUGIN_FILE ) ) . '/languages/' );
    }

    /**
     * Add email class
     *
     * @since 1.0.0
     * @param array $email_classes
     * @return array
     */
    public function add_email_class( array $email_classes ): array {
        return $email_classes;
    }
}
