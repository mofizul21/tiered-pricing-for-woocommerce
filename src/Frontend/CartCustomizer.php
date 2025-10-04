<?php
namespace WineVendorWooCommerce\Frontend;

class CartCustomizer
{
	public function __construct() {
		add_action( 'woocommerce_add_to_cart', [ $this, 'maybe_clear_cart' ], 10, 2 );
		add_filter( 'woocommerce_product_get_tax_class', [ $this, 'maybe_set_tax_class' ], 10, 2 );
	}

	/**
	 * Clear the cart of other products when a wine vendor product is added to the cart.
	 *
	 * @param string $cart_item_key The cart item key.
	 * @param int    $product_id    The product ID.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function maybe_clear_cart( string $cart_item_key, int $product_id ): void {
		$is_wine_vendor_product = get_post_meta( $product_id, '_is_wine_vendor_product', true );

		if ( $is_wine_vendor_product ) {
			// This is a wine vendor product. Remove all other products.
			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				if ( $key !== $cart_item_key && ! get_post_meta( $cart_item['product_id'], '_is_wine_vendor_product', true ) ) {
					WC()->cart->remove_cart_item( $key );
				}
			}
		} else {
			// This is not a wine vendor product. Check if the cart has wine vendor products.
			foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
				if ( $key !== $cart_item_key && get_post_meta( $cart_item['product_id'], '_is_wine_vendor_product', true ) ) {
					// Cart has a wine vendor product. Remove the non-wine-vendor product that was just added.
					WC()->cart->remove_cart_item( $cart_item_key );
					wc_add_notice( __( "Wine vendor and non-wine-vendor products can't be purchased together. The non-wine-vendor product has been removed from your cart.", 'wine-vendor-woocommerce' ), 'notice' );
					break;
				}
			}
		}
	}

	/**
	 * Set tax class for wine vendor products.
	 *
	 * @param string $tax_class The tax class.
	 * @param \WC_Product $product The product.
	 *
	 * @return string
	 */
	public function maybe_set_tax_class(string $tax_class, $product ): string
	{
		if ( get_post_meta( $product->get_id(), '_is_wine_vendor_product', true ) ) {
			return 'Zero Rate';
		}

		return $tax_class;
	}
}
