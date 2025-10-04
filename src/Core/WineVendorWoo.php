<?php
namespace WineVendorWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

class WineVendorWoo {

	/**
	 * Constructor. Hooks into various WordPress actions and filters
	 * to customize the WooCommerce experience for wine vendors.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'show_woocommerce_menus' ], 999 );
		add_action( 'pre_get_posts', [ $this, 'filter_orders' ] );
		add_filter( 'wp_count_posts', [ $this, 'filter_order_counts' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'set_orders_menu_icon' ], 9999 );
	}

	/**
	 * Filters the order counts for Wine Vendor.
	 *
	 * @param object $counts An object of post counts by status.
	 * @param string $type The post type.
	 * @param string $perm The permission type.
	 *
	 * @since 1.0.0
	 * @return object Modified post counts.
	 */
	public function filter_order_counts( object $counts, string $type, string $perm ): object
	{
		if ( ! is_admin() || ! $this->is_wine_vendor() || 'shop_order' !== $type ) {
			return $counts;
		}

		$vendor_id = get_current_user_id();

		// Get all product IDs for the current vendor
		$vendor_products = wc_get_products( [
			'author' => $vendor_id,
			'limit'  => -1,
			'return' => 'ids',
		] );

		if ( empty( $vendor_products ) ) {
			// No products, so no orders
			foreach ( (array) $counts as $status => $count ) {
				$counts->{$status} = 0;
			}
			return $counts;
		}

		// Find orders containing the vendor's products
		global $wpdb;
		$order_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT DISTINCT order_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
			WHERE oim.meta_key = '_product_id' AND oim.meta_value IN (" . implode( ',', $vendor_products ) . ")
		" ) );

		if ( empty( $order_ids ) ) {
			foreach ( (array) $counts as $status => $count ) {
				$counts->{$status} = 0;
			}
			return $counts;
		}

		// Get counts for these specific order IDs
		$query = "
			SELECT post_status, COUNT( * ) AS num_posts
			FROM {$wpdb->posts}
			WHERE post_type = %s
			AND ID IN (" . implode( ',', array_map( 'absint', $order_ids ) ) . ")
			GROUP BY post_status
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );

		$new_counts = [];
		foreach ( $results as $row ) {
			$new_counts[ $row['post_status'] ] = $row['num_posts'];
		}

		// Update the original counts object
		foreach ( (array) $counts as $status => $count ) {
			if ( ! isset( $new_counts[ $status ] ) ) {
				$counts->{$status} = 0;
			} else {
				$counts->{$status} = $new_counts[ $status ];
			}
		}

		return $counts;
	}

	/**
	 * Sets the dashicon for the 'Orders' menu item.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_orders_menu_icon(): void {
		if ( ! $this->is_wine_vendor() ) return;

		global $menu;

		foreach ( $menu as $key => $item ) {
			if ( 'edit.php?post_type=shop_order' === $item[2] ) { // Check for the Orders menu slug
				$menu[ $key ][6] = 'dashicons-cart'; // Set the dashicon
				break;
			}
		}
	}

	/**
	 * Check if current user is a Wine Vendor.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function is_wine_vendor(): bool {
		$user = wp_get_current_user();
		return in_array( 'wine_vendor', (array) $user->roles, true );
	}

	/**
	 * Displays the main WooCommerce menu and its specific submenus (Orders, Customers, Reports)
	 * for wine vendors, while hiding others.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function show_woocommerce_menus(): void {
		if ( ! $this->is_wine_vendor() ) return;

		// Remove the main WooCommerce menu, but not its submenus
		remove_menu_page( 'woocommerce' );

		// Add Customers as a top-level menu, pointing to our custom page
		add_menu_page(
			__( 'Customers', 'wine-vendor-woocommerce' ),
			__( 'Customers', 'wine-vendor-woocommerce' ),
			'manage_woocommerce_customers',
			'wine-vendor-customers',
			[ $this, 'display_wine_vendor_customers_page' ],
			'dashicons-groups',
			'55.6'
		);
	}

	/**
	 * Filters the WooCommerce orders list to show only orders that contain products
	 * created by the current wine vendor.
	 *
	 * @param \WP_Query $query The WordPress query object.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function filter_orders( \WP_Query $query ): void {
		if ( ! is_admin() || ! $this->is_wine_vendor() || ! $query->is_main_query() || 'shop_order' !== $query->get( 'post_type' ) ) {
			return;
		}

		$vendor_id = get_current_user_id();

		// Get all product IDs for the current vendor
		$vendor_products = wc_get_products( [
			'author' => $vendor_id,
			'limit'  => -1,
			'return' => 'ids',
		] );

		if ( empty( $vendor_products ) ) {
			// No products, so no orders
			$query->set( 'post__in', [0] );
			return;
		}

		// Find orders containing the vendor's products
		global $wpdb;
		$order_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT DISTINCT order_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
			WHERE oim.meta_key = '_product_id' AND oim.meta_value IN (" . implode( ',', array_map( 'absint', $vendor_products ) ) . ")
		" ) );

		if ( empty( $order_ids ) ) {
			$query->set( 'post__in', [0] );
			return;
		}

		$query->set( 'post__in', $order_ids );
	}

	/**
	 * Displays the custom customers page for wine vendors.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_wine_vendor_customers_page(): void {
		if ( ! current_user_can( 'manage_woocommerce_customers' ) || ! $this->is_wine_vendor() ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wine-vendor-woocommerce' ) );
		}

		global $wpdb;
		$vendor_id = get_current_user_id();

		// Get all product IDs for the current vendor
		$vendor_products = wc_get_products( [
			'author' => $vendor_id,
			'limit'  => -1,
			'return' => 'ids',
		] );

		$customer_ids = [];

		if ( ! empty( $vendor_products ) ) {
			// Find all order IDs that contain the current vendor's products
			$order_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT order_id
				FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
				INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
				WHERE oim.meta_key = '_product_id' AND oim.meta_value IN (" . implode( ',', array_map( 'absint', $vendor_products ) ) . ")
			" ) );

			if ( ! empty( $order_ids ) ) {
				// Find all customer IDs from these orders
				$customer_ids = $wpdb->get_col( $wpdb->prepare( "
					SELECT DISTINCT postmeta.meta_value
					FROM {$wpdb->posts} AS posts
					INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
					WHERE posts.post_type = 'shop_order'
					AND posts.ID IN (" . implode( ',', array_map( 'absint', $order_ids ) ) . ")
					AND postmeta.meta_key = '_customer_user'
				" ) );
			}
		}

		$customers = [];
		if ( ! empty( $customer_ids ) ) {
			$customers = get_users( [ 'include' => array_map( 'absint', $customer_ids ) ] );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'My Customers', 'wine-vendor-woocommerce' ); ?></h1>

			<?php if ( empty( $customers ) ) : ?>
				<p><?php esc_html_e( 'No customers found for your products.', 'wine-vendor-woocommerce' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Customer ID', 'wine-vendor-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Username', 'wine-vendor-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Email', 'wine-vendor-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'First Name', 'wine-vendor-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Last Name', 'wine-vendor-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $customers as $customer ) : ?>
							<tr>
								<td><?php echo esc_html( $customer->ID ); ?></td>
								<td><?php echo esc_html( $customer->user_login ); ?></td>
								<td><?php echo esc_html( $customer->user_email ); ?></td>
								<td><?php echo esc_html( $customer->first_name ); ?></td>
								<td><?php echo esc_html( $customer->last_name ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
