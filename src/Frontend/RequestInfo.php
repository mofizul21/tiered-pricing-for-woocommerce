<?php
namespace TieredPricingForWooCommerce\Frontend;

use WC_Product;

defined( 'ABSPATH' ) || exit;

class RequestInfo {

	/**
	 * Register hooks for the Request Info popup.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'render_modal' ] );
		add_action( 'wp_ajax_tpfw_request_info', [ $this, 'handle_submission' ] );
		add_action( 'wp_ajax_nopriv_tpfw_request_info', [ $this, 'handle_submission' ] );
	}

	/**
	 * Output the modal HTML and inline config in the footer on single product pages.
	 *
	 * tpfwRequestInfo is printed as an inline <script> here rather than via
	 * wp_localize_script() because CheckoutFields::enqueue_scripts() registers the
	 * shared script handle at the same hook priority — ordering is not guaranteed, so
	 * wp_localize_script() would silently fail if it fires before registration.
	 */
	public function render_modal(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$image_id   = $product->get_image_id();
		$image_html = $image_id
			? wp_get_attachment_image( $image_id, 'woocommerce_single', false, [ 'class' => 'tpfw-modal-product-img' ] )
			: wc_placeholder_img( 'woocommerce_single' );

		$config = wp_json_encode( [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => [
				'sending' => esc_html__( 'Sending…', 'tiered-pricing-for-woocommerce' ),
				'error'   => esc_html__( 'Something went wrong. Please try again.', 'tiered-pricing-for-woocommerce' ),
				'submit'  => esc_html__( 'Submit', 'tiered-pricing-for-woocommerce' ),
			],
		] );

		echo '<script>var tpfwRequestInfo = ' . $config . ';</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		?>
		<div id="tpfw-request-info-modal" class="tpfw-ri-modal" aria-hidden="true">
			<div class="tpfw-ri-overlay"></div>
			<div class="tpfw-ri-container" role="dialog" aria-modal="true" aria-labelledby="tpfw-ri-title">
				<div class="tpfw-ri-header">
					<h3 id="tpfw-ri-title"><?php esc_html_e( 'Request Information', 'tiered-pricing-for-woocommerce' ); ?></h3>
					<button type="button" class="tpfw-ri-close" aria-label="<?php esc_attr_e( 'Close', 'tiered-pricing-for-woocommerce' ); ?>">&times;</button>
				</div>

				<div class="tpfw-ri-body">
					<div class="tpfw-ri-product-row">
						<div class="tpfw-ri-product-image">
							<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="tpfw-ri-product-details">
							<h4 class="tpfw-ri-product-name"><?php echo esc_html( $product->get_name() ); ?></h4>
							<p class="tpfw-ri-intro"><?php esc_html_e( 'Fill out the form below to receive more information about this product.', 'tiered-pricing-for-woocommerce' ); ?></p>

							<form id="tpfw-request-info-form" novalidate>
								<?php wp_nonce_field( 'tpfw_request_info', 'tpfw_request_info_nonce', false ); ?>
								<input type="hidden" name="action" value="tpfw_request_info">
								<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
								<input type="hidden" name="product_name" value="<?php echo esc_attr( $product->get_name() ); ?>">

								<div class="tpfw-ri-row tpfw-ri-2col">
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_full_name"><?php esc_html_e( 'Full Name', 'tiered-pricing-for-woocommerce' ); ?> <span aria-hidden="true">*</span></label>
										<input type="text" id="tpfw_ri_full_name" name="full_name" required autocomplete="name">
									</div>
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_email"><?php esc_html_e( 'Your Email', 'tiered-pricing-for-woocommerce' ); ?> <span aria-hidden="true">*</span></label>
										<input type="email" id="tpfw_ri_email" name="email" required autocomplete="email">
									</div>
								</div>

								<div class="tpfw-ri-row">
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_organization"><?php esc_html_e( 'Organization', 'tiered-pricing-for-woocommerce' ); ?></label>
										<input type="text" id="tpfw_ri_organization" name="organization" autocomplete="organization">
									</div>
								</div>

								<div class="tpfw-ri-row tpfw-ri-2col">
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_phone"><?php esc_html_e( 'Phone', 'tiered-pricing-for-woocommerce' ); ?></label>
										<input type="tel" id="tpfw_ri_phone" name="phone" autocomplete="tel">
									</div>
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_zip"><?php esc_html_e( 'Zip', 'tiered-pricing-for-woocommerce' ); ?></label>
										<input type="text" id="tpfw_ri_zip" name="zip" autocomplete="postal-code">
									</div>
								</div>

								<div class="tpfw-ri-row">
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_qty"><?php esc_html_e( 'Qty', 'tiered-pricing-for-woocommerce' ); ?></label>
										<input type="number" id="tpfw_ri_qty" name="qty" min="1" step="1">
									</div>
								</div>

								<div class="tpfw-ri-row">
									<div class="tpfw-ri-field">
										<label for="tpfw_ri_additional"><?php esc_html_e( 'Additional Information', 'tiered-pricing-for-woocommerce' ); ?></label>
										<textarea id="tpfw_ri_additional" name="additional_info" rows="4"></textarea>
									</div>
								</div>

								<div id="tpfw-ri-message" role="status" aria-live="polite"></div>

								<div class="tpfw-ri-row">
									<button type="submit" class="button alt tpfw-ri-submit">
										<?php esc_html_e( 'Submit', 'tiered-pricing-for-woocommerce' ); ?>
									</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle the AJAX form submission and send an email to the admin.
	 */
	public function handle_submission(): void {
		$nonce = isset( $_POST['tpfw_request_info_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['tpfw_request_info_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tpfw_request_info' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Security check failed. Please refresh the page and try again.', 'tiered-pricing-for-woocommerce' ) ] );
		}

		$full_name       = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
		$email           = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$organization    = isset( $_POST['organization'] ) ? sanitize_text_field( wp_unslash( $_POST['organization'] ) ) : '';
		$phone           = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$zip             = isset( $_POST['zip'] ) ? sanitize_text_field( wp_unslash( $_POST['zip'] ) ) : '';
		$qty             = isset( $_POST['qty'] ) ? absint( $_POST['qty'] ) : 0;
		$additional_info = isset( $_POST['additional_info'] ) ? sanitize_textarea_field( wp_unslash( $_POST['additional_info'] ) ) : '';
		$product_name    = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
		$product_id      = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( empty( $full_name ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter your full name.', 'tiered-pricing-for-woocommerce' ) ] );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid email address.', 'tiered-pricing-for-woocommerce' ) ] );
		}

		$admin_email  = get_option( 'admin_email' );
		$site_name    = get_bloginfo( 'name' );
		$site_host    = (string) wp_parse_url( get_site_url(), PHP_URL_HOST );
		$product_url  = $product_id ? get_permalink( $product_id ) : '';
		$subject      = sprintf( '[%s] Product Info Request: %s', $site_name, $product_name );

		$lines   = [];
		$lines[] = 'Product: ' . $product_name;
		if ( $product_url ) {
			$lines[] = 'Product URL: ' . $product_url;
		}
		$lines[] = '';
		$lines[] = 'Full Name: ' . $full_name;
		$lines[] = 'Email: ' . $email;
		if ( $organization ) {
			$lines[] = 'Organization: ' . $organization;
		}
		if ( $phone ) {
			$lines[] = 'Phone: ' . $phone;
		}
		if ( $zip ) {
			$lines[] = 'Zip: ' . $zip;
		}
		if ( $qty ) {
			$lines[] = 'Qty: ' . $qty;
		}
		if ( $additional_info ) {
			$lines[] = '';
			$lines[] = 'Additional Information:';
			$lines[] = $additional_info;
		}

		$body = implode( "\n", $lines );

		// From must be on the same domain as the server so SPF passes on shared
		// hosting. Reply-To is set to the submitter so replying goes to them.
		$headers = [
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . $site_name . ' <noreply@' . $site_host . '>',
			'Reply-To: ' . $full_name . ' <' . $email . '>',
		];

		$sent = wp_mail( $admin_email, $subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( [ 'message' => esc_html__( 'Your request has been sent successfully!', 'tiered-pricing-for-woocommerce' ) ] );
		} else {
			wp_send_json_error( [ 'message' => esc_html__( 'Something went wrong sending your request. Please try again.', 'tiered-pricing-for-woocommerce' ) ] );
		}
	}
}
