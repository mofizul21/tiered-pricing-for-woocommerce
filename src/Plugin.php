<?php
namespace TieredPricingForWooCommerce;

defined( 'ABSPATH' ) || exit;

use TieredPricingForWooCommerce\Admin\Admin;
use TieredPricingForWooCommerce\Admin\Menus;
use TieredPricingForWooCommerce\Core\ProductColors;
use TieredPricingForWooCommerce\Core\ProductSettings;
use TieredPricingForWooCommerce\Core\WooCommerceStarter;
use TieredPricingForWooCommerce\Frontend\ProductInfo;
use TieredPricingForWooCommerce\Frontend\ProductPricingTable;

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

        add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
        $this->load_textdomain();

        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', [ $this, 'render_missing_woocommerce_notice' ] );
            return;
        }

        if ( is_admin() ) {
            new Menus();
            new Admin();
        }

		new ProductColors();
		new ProductSettings();
		new WooCommerceStarter();
        new ProductInfo();
        new ProductPricingTable();
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
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', TPFW_PLUGIN_FILE, true );
        }
    }

    /**
     * Load text-domain
     *
     * @since 1.0.0
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain( TPFW_TEXT_DOMAIN, false, dirname( plugin_basename( TPFW_PLUGIN_FILE ) ) . '/languages/' );
    }

    /**
     * Check whether WooCommerce is active.
     */
    private function is_woocommerce_active(): bool {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Render an admin notice when WooCommerce is missing.
     */
    public function render_missing_woocommerce_notice(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Tiered Pricing for WooCommerce requires WooCommerce to be installed and active.', 'tiered-pricing-for-woocommerce' ); ?></p>
        </div>
        <?php
    }
}
