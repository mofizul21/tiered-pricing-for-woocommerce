<?php
namespace TieredPricingForWooCommerce\Core\Base;

defined( 'ABSPATH' ) || exit;

/**
 * A base class for creating standalone admin settings pages that look like WooCommerce.
 */
abstract class Settings {

    /**
     * Get the tabs for the settings page.
     *
     * @since 1.0.0
     * @return array
     */
    abstract public function get_tabs(): array;

    /**
     * Get the settings for a specific tab.
     * @param string $tab The current tab slug.
     * @return array
     */
    abstract public function get_settings( string $tab ): array;

    /**
     * Get the current tab from the URL.
     *
     * @since 1.0.0
     * @return string
     */
    protected function get_current_tab(): string {
        $tabs = $this->get_tabs();
        $default_tab = key( $tabs );
        return isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : $default_tab;
    }

    /**
     * Handles the saving of settings.
     *
     * @since 1.0.0
     * @return void
     * */
    public function save(): void {
        $nonce = isset( $_POST['tpfw_settings_nonce'] ) ? sanitize_key( wp_unslash( $_POST['tpfw_settings_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'tpfw_save_settings' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to save these settings.', 'tiered-pricing-for-woocommerce' ) );
        }

        $current_tab = $this->get_current_tab();
        $settings = $this->get_settings( $current_tab );

        \WC_Admin_Settings::save_fields( $settings );

        add_settings_error(
            'tpfw_settings',
            'settings_updated',
            esc_html__( 'Your settings have been saved.', 'tiered-pricing-for-woocommerce' ),
            'success'
        );
    }

    /**
     * A placeholder for child classes to add extra, non-standard tabs.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_extra_tabs(): array {
        return [];
    }

    /**
     * Renders the navigation tabs. This can now be extended by child classes.
     *
     * @since 1.0.0
     * @return void
     */
    public function output_tabs(): void
    {
        $tabs = $this->get_tabs();
        $extra_tabs = $this->get_extra_tabs();
        $current_tab = $this->get_current_tab();

        $base_url = TPFW()->settings_url;
        ?>
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            <?php foreach ( $tabs as $slug => $label ) : ?>
                <?php
                $tab_url = add_query_arg( 'tab', $slug, $base_url );
                ?>
                <a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab <?php if ( $current_tab === $slug ) echo 'nav-tab-active'; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>

            <?php
            foreach ( $extra_tabs as $tab_data ) {
                printf(
                    '<a href="%s" class="nav-tab" target="_blank">%s</a>',
                    esc_url( $tab_data['url'] ),
                    esc_html( $tab_data['label'] )
                );
            }
            ?>
        </nav>
        <?php
    }

	public function hide_unrelated_notices() {
		ob_start();
		do_action( 'admin_notices' );
		ob_end_clean();

		settings_errors( 'tpfw_settings' );
	}

    /**
     * Renders the entire settings page.
     *
     * @since 1.0.0
     * @return void
     * */
    public function output(): void
    {
        if ( ! empty( $_POST['save'] ) ) {
            $this->save();
        }
        ?>
        <div class="wrap woocommerce">
            <?php $this->output_tabs(); ?>

            <?php settings_errors( 'tpfw_settings' ); ?>

            <h1 class="screen-reader-text"><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="column_1">
                <form method="post" action="">
                    <?php
                    \WC_Admin_Settings::output_fields( $this->get_settings( $this->get_current_tab() ) );
                    wp_nonce_field( 'tpfw_save_settings', 'tpfw_settings_nonce' );
                    ?>
                    <p class="submit">
                        <button type="submit" class="button-primary woocommerce-save-button" name="save" value="<?php esc_attr_e( 'Save changes', 'tiered-pricing-for-woocommerce' ); ?>">
                            <?php esc_html_e( 'Save changes', 'tiered-pricing-for-woocommerce' ); ?>
                        </button>
                    </p>
                </form>
            </div>

            <div class="column_2">
                <h4><?php esc_html_e( 'Starter Notes', 'tiered-pricing-for-woocommerce' ); ?></h4>
				<ul>
					<li>
						<?php esc_html_e( 'This plugin is now a clean WooCommerce starter scaffold for tiered pricing features.', 'tiered-pricing-for-woocommerce' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'Use the product edit screen to enable the starter tier settings on individual products.', 'tiered-pricing-for-woocommerce' ); ?>
					</li>
					<li>
						<?php esc_html_e( 'The pricing calculation logic has intentionally been removed so new requirements can be added cleanly later.', 'tiered-pricing-for-woocommerce' ); ?>
					</li>
				</ul>
            </div>
        </div>
        <?php
    }
}
