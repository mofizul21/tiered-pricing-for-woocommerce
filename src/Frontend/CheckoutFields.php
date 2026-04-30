<?php
namespace TieredPricingForWooCommerce\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Adds custom imprint/order-detail fields to the WooCommerce checkout page,
 * saves them to order meta, and surfaces them in the admin order screen and emails.
 */
class CheckoutFields {

	/**
	 * Register all hooks for checkout fields, logo upload, admin display, and emails.
	 */
	public function __construct() {
		add_action( 'woocommerce_after_order_notes',                      [ $this, 'render_fields' ] );
		add_action( 'woocommerce_checkout_process',                       [ $this, 'validate_fields' ] );
		add_action( 'woocommerce_checkout_create_order',                  [ $this, 'save_fields' ], 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_admin_fields' ] );
		add_action( 'woocommerce_email_order_meta',                       [ $this, 'display_email_fields' ], 10, 3 );
		add_action( 'wp_enqueue_scripts',                                 [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_tpfw_upload_logo',                           [ $this, 'handle_logo_upload' ] );
		add_action( 'wp_ajax_nopriv_tpfw_upload_logo',                    [ $this, 'handle_logo_upload' ] );
	}

	// -------------------------------------------------------------------------
	// Script / style enqueue
	// -------------------------------------------------------------------------

	/**
	 * Register and enqueue the shared frontend script and stylesheet.
	 *
	 * This is intentionally the canonical registration point for
	 * tpfw-frontend-script on the frontend. Admin.php only runs in wp-admin
	 * context, so this method owns registration for all frontend pages.
	 * On the checkout page it also localises tpfwCheckout for the logo uploader.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Register and enqueue the shared frontend script on all pages.
		// Admin::enqueue_frontend_assets() only runs in wp-admin context,
		// so we own the registration here for all frontend pages.
		if ( ! wp_script_is( 'tpfw-frontend-script', 'registered' ) ) {
			wp_register_script(
				'tpfw-frontend-script',
				plugins_url( 'assets/js/frontend.js', TPFW_PLUGIN_FILE ),
				[ 'jquery' ],
				TPFW_VERSION,
				true
			);
		}
		wp_enqueue_script( 'tpfw-frontend-script' );

		if ( ! wp_style_is( 'tpfw-frontend-style', 'registered' ) ) {
			wp_register_style(
				'tpfw-frontend-style',
				plugins_url( 'assets/css/frontend.css', TPFW_PLUGIN_FILE ),
				[],
				TPFW_VERSION
			);
		}
		wp_enqueue_style( 'tpfw-frontend-style' );

		if ( ! is_checkout() ) {
			return;
		}

		wp_localize_script(
			'tpfw-frontend-script',
			'tpfwCheckout',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tpfw_upload_logo' ),
				'i18n'    => [
					'uploading' => esc_html__( 'Uploading…', 'tiered-pricing-for-woocommerce' ),
					'uploaded'  => esc_html__( 'Uploaded', 'tiered-pricing-for-woocommerce' ),
					'remove'    => esc_html__( 'Remove', 'tiered-pricing-for-woocommerce' ),
					'error'     => esc_html__( 'Upload failed. Please try again.', 'tiered-pricing-for-woocommerce' ),
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Render fields on the checkout page
	// -------------------------------------------------------------------------

	/**
	 * Output the Imprint & Delivery Details section after the order notes field.
	 *
	 * Renders seven fields: Date in Hand (required date picker), Imprint Information
	 * (textarea), Text Imprint Colors, Text Imprint Size/Font/Details, Logo Imprint
	 * Description, Logo Imprint Colors, and a file upload for logo art (AJAX-handled,
	 * attachment ID stored in a hidden input).
	 *
	 * @param \WC_Checkout $checkout WooCommerce checkout instance.
	 * @return void
	 */
	public function render_fields( \WC_Checkout $checkout ): void {
		echo '<div id="tpfw-checkout-fields" class="tpfw-checkout-section">';
		echo '<h3>' . esc_html__( 'Imprint & Delivery Details', 'tiered-pricing-for-woocommerce' ) . '</h3>';
		echo '<p class="tpfw-imprint-notice">' . esc_html__(
			'We have the ability to place a text imprint and/or logo on your promotional products. If you want text on this item, please specify the exact text as well as the fonts, colors, sizes, and layout. If you would like to use a logo, you should describe the logo and attach the logo file. If you only have a paper copy of the logo, specify that below and we\'ll contact you to make arrangements to receive the art. Most items have imprint size restrictions. We will contact you if we need to make adjustments to your text or artwork. A one-color imprint is usually included in the price of the item. If there will be additional charges (extra colors, setup for art, etc.), we will notify you of those charges.',
			'tiered-pricing-for-woocommerce'
		) . '</p>';

		// Date in hand (required) — min set to today so past dates are disabled.
		woocommerce_form_field(
			'tpfw_date_in_hand',
			[
				'type'              => 'date',
				'label'             => __( 'Date in Hand', 'tiered-pricing-for-woocommerce' ),
				'required'          => true,
				'class'             => [ 'form-row-wide' ],
				'custom_attributes' => [
					'min' => wp_date( 'Y-m-d' ),
				],
			],
			$checkout->get_value( 'tpfw_date_in_hand' )
		);

		// Imprint Information
		woocommerce_form_field(
			'tpfw_imprint_info',
			[
				'type'  => 'textarea',
				'label' => __( 'Imprint Information', 'tiered-pricing-for-woocommerce' ),
				'class' => [ 'form-row-wide' ],
			],
			$checkout->get_value( 'tpfw_imprint_info' )
		);

		// Text Imprint Colors
		woocommerce_form_field(
			'tpfw_text_imprint_colors',
			[
				'type'        => 'text',
				'label'       => __( 'Text Imprint Colors', 'tiered-pricing-for-woocommerce' ),
				'placeholder' => __( 'e.g. Red, White', 'tiered-pricing-for-woocommerce' ),
				'class'       => [ 'form-row-wide' ],
			],
			$checkout->get_value( 'tpfw_text_imprint_colors' )
		);

		// Text Imprint size, font and other information
		woocommerce_form_field(
			'tpfw_text_imprint_details',
			[
				'type'        => 'text',
				'label'       => __( 'Text Imprint Size, Font & Other Information', 'tiered-pricing-for-woocommerce' ),
				'placeholder' => __( 'e.g. 12pt Arial Bold', 'tiered-pricing-for-woocommerce' ),
				'class'       => [ 'form-row-wide' ],
			],
			$checkout->get_value( 'tpfw_text_imprint_details' )
		);

		// Logo Imprint Description
		woocommerce_form_field(
			'tpfw_logo_description',
			[
				'type'  => 'text',
				'label' => __( 'Logo Imprint Description', 'tiered-pricing-for-woocommerce' ),
				'class' => [ 'form-row-wide' ],
			],
			$checkout->get_value( 'tpfw_logo_description' )
		);

		// Logo Imprint Color(s)
		woocommerce_form_field(
			'tpfw_logo_colors',
			[
				'type'        => 'text',
				'label'       => __( 'Logo Imprint Color(s)', 'tiered-pricing-for-woocommerce' ),
				'placeholder' => __( 'e.g. PMS 485 Red, Black', 'tiered-pricing-for-woocommerce' ),
				'class'       => [ 'form-row-wide' ],
			],
			$checkout->get_value( 'tpfw_logo_colors' )
		);

		// Logo art file upload (handled via AJAX — attachment ID stored in hidden field)
		?>
		<p class="form-row form-row-wide tpfw-logo-upload-wrap">
			<label for="tpfw_logo_file">
				<?php esc_html_e( 'Attach Logo Art File', 'tiered-pricing-for-woocommerce' ); ?>
				<span class="optional">(<?php esc_html_e( 'optional', 'tiered-pricing-for-woocommerce' ); ?>)</span>
			</label>
			<input type="file" id="tpfw_logo_file" name="tpfw_logo_file"
				accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.ai,.eps,.svg">
			<span class="tpfw-upload-status"></span>
			<input type="hidden" id="tpfw_logo_attachment_id" name="tpfw_logo_attachment_id">
		</p>
		<?php

		echo '</div>';
	}

	// -------------------------------------------------------------------------
	// Validation
	// -------------------------------------------------------------------------

	/**
	 * Validate required checkout fields before the order is created.
	 *
	 * Adds a WooCommerce error notice if Date in Hand is missing, which prevents
	 * the order from being placed until the customer fills it in.
	 *
	 * @return void
	 */
	public function validate_fields(): void {
		if ( empty( $_POST['tpfw_date_in_hand'] ) ) {
			wc_add_notice(
				esc_html__( 'Please select a Date in Hand.', 'tiered-pricing-for-woocommerce' ),
				'error'
			);
		}
	}

	// -------------------------------------------------------------------------
	// Save to order meta
	// -------------------------------------------------------------------------

	/**
	 * Persist checkout field values to order meta.
	 *
	 * Text fields are sanitized individually before storage. The imprint info
	 * textarea uses sanitize_textarea_field to preserve line breaks, and the
	 * logo attachment ID is cast to int. All meta keys are prefixed with `_tpfw_`.
	 *
	 * @param \WC_Order $order WooCommerce order being created.
	 * @param array     $data  Posted checkout data (unused; reads from $_POST directly).
	 * @return void
	 */
	public function save_fields( \WC_Order $order, array $data ): void {
		$text_fields = [
			'tpfw_date_in_hand'        => 'sanitize_text_field',
			'tpfw_text_imprint_colors' => 'sanitize_text_field',
			'tpfw_text_imprint_details'=> 'sanitize_text_field',
			'tpfw_logo_description'    => 'sanitize_text_field',
			'tpfw_logo_colors'         => 'sanitize_text_field',
		];

		foreach ( $text_fields as $key => $sanitize ) {
			if ( ! empty( $_POST[ $key ] ) ) {
				$order->update_meta_data( '_' . $key, call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		if ( ! empty( $_POST['tpfw_imprint_info'] ) ) {
			$order->update_meta_data( '_tpfw_imprint_info', sanitize_textarea_field( wp_unslash( $_POST['tpfw_imprint_info'] ) ) );
		}

		if ( ! empty( $_POST['tpfw_logo_attachment_id'] ) ) {
			$order->update_meta_data( '_tpfw_logo_attachment_id', absint( $_POST['tpfw_logo_attachment_id'] ) );
		}
	}

	// -------------------------------------------------------------------------
	// AJAX logo upload
	// -------------------------------------------------------------------------

	/**
	 * Handle the AJAX logo art file upload and return the new attachment ID.
	 *
	 * Accepts authenticated and unauthenticated requests (guests at checkout).
	 * Validates the nonce, checks that a file was received, restricts to an
	 * explicit allowlist of MIME types, delegates to media_handle_upload(), and
	 * returns JSON with the attachment ID and filename on success.
	 *
	 * Allowed types: jpg/jpeg, png, gif, webp, pdf, doc, docx, ai, eps, svg.
	 *
	 * @return void Sends JSON response and exits.
	 */
	public function handle_logo_upload(): void {
		check_ajax_referer( 'tpfw_upload_logo', 'nonce' );

		if ( empty( $_FILES['tpfw_logo_file']['name'] ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No file received.', 'tiered-pricing-for-woocommerce' ) ] );
		}

		$allowed = [
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'svg'  => 'image/svg+xml',
		];

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'tpfw_logo_file', 0, [], [
			'mimes'                    => $allowed,
			'test_form'                => false,
		] );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ] );
		}

		wp_send_json_success( [
			'attachment_id' => $attachment_id,
			'filename'      => basename( get_attached_file( $attachment_id ) ),
		] );
	}

	// -------------------------------------------------------------------------
	// Admin order view
	// -------------------------------------------------------------------------

	/**
	 * Render imprint and delivery details in the admin order edit screen.
	 *
	 * Outputs an HTML table below the billing address block. Returns early if
	 * all fields are empty so no section appears for orders placed before this
	 * plugin was active.
	 *
	 * @param \WC_Order $order Current order being viewed.
	 * @return void
	 */
	public function display_admin_fields( \WC_Order $order ): void {
		$fields = $this->get_saved_fields( $order );

		if ( empty( array_filter( $fields ) ) ) {
			return;
		}

		echo '<div class="tpfw-order-meta">';
		echo '<h4>' . esc_html__( 'Imprint & Delivery Details', 'tiered-pricing-for-woocommerce' ) . '</h4>';
		echo '<table cellspacing="0" cellpadding="4" style="width:100%">';

		foreach ( $fields as $label => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			echo '<tr><th style="text-align:left;padding-right:12px;white-space:nowrap">'
				. esc_html( $label ) . '</th><td>' . wp_kses_post( $value ) . '</td></tr>';
		}

		echo '</table></div>';
	}

	// -------------------------------------------------------------------------
	// Email output
	// -------------------------------------------------------------------------

	/**
	 * Append imprint and delivery details to WooCommerce order emails.
	 *
	 * Called for both admin and customer emails. Outputs a plain-text block when
	 * $plain_text is true, otherwise an HTML table. Returns early if all fields
	 * are empty so no section appears in emails for older orders.
	 *
	 * @param \WC_Order $order          Order whose details are being emailed.
	 * @param bool      $sent_to_admin  True when the email goes to the site admin.
	 * @param bool      $plain_text     True when a plain-text email is being rendered.
	 * @return void
	 */
	public function display_email_fields( \WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
		$fields = $this->get_saved_fields( $order );

		if ( empty( array_filter( $fields ) ) ) {
			return;
		}

		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Imprint & Delivery Details', 'tiered-pricing-for-woocommerce' ) . "\n";
			echo str_repeat( '-', 40 ) . "\n";
			foreach ( $fields as $label => $value ) {
				if ( '' !== $value && null !== $value ) {
					echo esc_html( $label ) . ': ' . wp_strip_all_tags( $value ) . "\n";
				}
			}
		} else {
			echo '<h2>' . esc_html__( 'Imprint & Delivery Details', 'tiered-pricing-for-woocommerce' ) . '</h2>';
			echo '<table cellspacing="0" cellpadding="6" style="width:100%;border-collapse:collapse">';
			foreach ( $fields as $label => $value ) {
				if ( '' === $value || null === $value ) {
					continue;
				}
				echo '<tr><th style="text-align:left;border:1px solid #e0e0e0;padding:8px">'
					. esc_html( $label )
					. '</th><td style="border:1px solid #e0e0e0;padding:8px">'
					. wp_kses_post( $value )
					. '</td></tr>';
			}
			echo '</table>';
		}
	}

	// -------------------------------------------------------------------------
	// Helper: read all saved fields from an order
	// -------------------------------------------------------------------------

	/**
	 * Build a label-to-value map of all saved imprint/delivery fields for an order.
	 *
	 * For the logo attachment, resolves the ID to a clickable filename link (or
	 * plain filename if the URL is unavailable). All values are already escaped
	 * for safe output by callers.
	 *
	 * @param \WC_Order $order Order to read meta from.
	 * @return array<string, string> Associative array of human-readable label => escaped value.
	 */
	private function get_saved_fields( \WC_Order $order ): array {
		$attachment_id = (int) $order->get_meta( '_tpfw_logo_attachment_id' );
		$logo_value    = '';

		if ( $attachment_id ) {
			$url         = wp_get_attachment_url( $attachment_id );
			$filename    = basename( get_attached_file( $attachment_id ) );
			$logo_value  = $url
				? '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $filename ) . '</a>'
				: esc_html( $filename );
		}

		return [
			__( 'Date in Hand', 'tiered-pricing-for-woocommerce' )                        => esc_html( (string) $order->get_meta( '_tpfw_date_in_hand' ) ),
			__( 'Imprint Information', 'tiered-pricing-for-woocommerce' )                  => nl2br( esc_html( (string) $order->get_meta( '_tpfw_imprint_info' ) ) ),
			__( 'Text Imprint Colors', 'tiered-pricing-for-woocommerce' )                  => esc_html( (string) $order->get_meta( '_tpfw_text_imprint_colors' ) ),
			__( 'Text Imprint Size, Font & Other Info', 'tiered-pricing-for-woocommerce' ) => esc_html( (string) $order->get_meta( '_tpfw_text_imprint_details' ) ),
			__( 'Logo Imprint Description', 'tiered-pricing-for-woocommerce' )             => esc_html( (string) $order->get_meta( '_tpfw_logo_description' ) ),
			__( 'Logo Imprint Color(s)', 'tiered-pricing-for-woocommerce' )                => esc_html( (string) $order->get_meta( '_tpfw_logo_colors' ) ),
			__( 'Logo Art File', 'tiered-pricing-for-woocommerce' )                        => $logo_value,
		];
	}
}
