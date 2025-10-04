<?php
namespace WineVendorWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

class WineVendorProducts {

	/**
	 * Constructor. Initializes the class and hooks into various WordPress actions and filters
	 * to manage product-related functionalities for wine vendors.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		// Add capabilities to Wine Vendor role
		add_action( 'init', [ $this, 'add_caps_to_role' ] );

		// Restrict admin menus
		add_action( 'admin_menu', [ $this, 'hide_admin_menus' ], 999 );

		// Track term author when a vendor creates a category/tag
		add_action( 'created_term', [ $this, 'set_term_author' ], 10, 3 );

		// Filter term list so vendor sees only their own terms
		add_filter( 'get_terms_args', [ $this, 'filter_terms_for_vendor' ], 10, 2 );

		// Use map_meta_cap to restrict edit/delete permissions
		add_filter( 'map_meta_cap', [ $this, 'restrict_term_caps' ], 10, 4 );
		add_filter( 'wp_count_posts', [ $this, 'filter_product_counts' ], 10, 3 );
		add_action( 'save_post', [ $this, 'add_wine_vendor_meta_on_product_save' ], 10, 2 );
	}

	/**
	 * Save meta-data to a product when it is created by a Wine Vendor.
	 *
	 * @param int     $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_wine_vendor_meta_on_product_save( int $post_id, \WP_Post $post ): void {
		if ( 'product' !== $post->post_type || ! $this->is_wine_vendor() ) {
			return;
		}
		update_post_meta( $post_id, '_is_wine_vendor_product', true );
	}

	/**
	 * Check if current user is a Wine Vendor.
	 */
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
	 * Add product & category capabilities to Wine Vendor role.
	 */
	/**
	 * Add product & category capabilities to Wine Vendor role.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_caps_to_role(): void {
		$role = get_role( 'wine_vendor' );
		if ( ! $role ) return;

		$caps = [
			'read',
			'upload_files',

			// Products
			'edit_products',
			'edit_published_products',
			'publish_products',
			'delete_products',
			'delete_published_products',

			// Categories / Tags
			'manage_product_terms',
			'assign_product_terms',
			'manage_woocommerce',  // Added to allow access to WooCommerce menu

			// Orders
			'edit_shop_orders',
			'read_shop_order',
			'delete_shop_orders',
			'edit_published_shop_orders',

			// Customers
			'manage_woocommerce_customers',

			// Reports
			'view_woocommerce_reports',

			// remove edit/delete globally; handled in map_meta_cap
		];

		foreach ( $caps as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Restrict admin menu for Wine Vendor. Keep only Products + Categories + Tags.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function hide_admin_menus(): void {
		if ( ! $this->is_wine_vendor() ) return;

		// Remove unwanted menus
		remove_menu_page( 'index.php' );
		remove_menu_page( 'jetpack' );
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );

		remove_menu_page( 'edit.php?post_type=elementor_library' );
		remove_menu_page( 'edit.php?post_type=nuss_gallery' );

		// Optional: remove attributes submenu
		remove_submenu_page( 'edit.php?post_type=product', 'product_attributes' );
	}

	/**
	 * Save current user as the author of the term.
	 *
	 * @param int    $term_id  The term ID.
	 * @param int    $tt_id    The term taxonomy ID.
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function set_term_author( int $term_id, int $tt_id, string $taxonomy ): void {
		if ( in_array( $taxonomy, ['product_cat', 'product_tag'] ) ) {
			update_term_meta( $term_id, '_term_author', get_current_user_id() );
		}
	}

	/**
	 * Filter term lists so vendor sees only their own terms.
	 *
	 * @param array $args       Query arguments for terms.
	 * @param array $taxonomies An array of taxonomies.
	 *
	 * @since 1.0.0
	 * @return array Modified query arguments.
	 */
	public function filter_terms_for_vendor( array $args, array $taxonomies ): array {
		if ( ! is_admin() || ! $this->is_wine_vendor() ) {
			return $args;
		}

		if ( ! array_intersect( $taxonomies, ['product_cat', 'product_tag'] ) ) {
			return $args;
		}

		$user_id = get_current_user_id();

		$meta_query = [
			'key'     => '_term_author',
			'value'   => $user_id,
			'compare' => '=',
		];

		if ( isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
			$args['meta_query'][] = $meta_query;
		} else {
			$args['meta_query'] = [$meta_query];
		}

		return $args;
	}

	/**
	 * Use map_meta_cap to restrict edit/delete term capabilities to the author.
	 *
	 * @param array  $caps    The user's capabilities.
	 * @param string $cap     The capability being checked.
	 * @param int    $user_id The user ID.
	 * @param array  $args    Additional arguments for the capability check.
	 *
	 * @since 1.0.0
	 * @return array Modified capabilities.
	 */
	public function restrict_term_caps( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, ['edit_term', 'delete_term'], true ) ) {
			return $caps;
		}

		$term_id = $args[0] ?? 0;
		$term = get_term( $term_id );
		if ( ! $term || ! in_array( $term->taxonomy, ['product_cat', 'product_tag'], true ) ) {
			return $caps;
		}

		$author_id = get_term_meta( $term_id, '_term_author', true );

		// If user is a Wine Vendor and not the author, deny permission
		$user = get_userdata( $user_id );
		if ( $user && in_array( 'wine_vendor', (array) $user->roles, true ) && (int) $author_id !== (int) $user_id ) {
			$caps[] = 'do_not_allow';
		}

		return $caps;
	}

	/**
	 * Filter product counts for Wine Vendor.
	 *
	 * @param object $counts An object of post counts by status.
	 * @param string $type   The post type.
	 * @param string $perm   The permission type.
	 *
	 * @since 1.0.0
	 * @return object Modified post counts.
	 */
	public function filter_product_counts( object $counts, string $type, string $perm ): object {
		if ( ! is_admin() || ! $this->is_wine_vendor() || 'product' !== $type ) {
			return $counts;
		}

		$user_id = get_current_user_id();

		global $wpdb;

		$query = "
			SELECT post_status, COUNT( * ) AS num_posts
			FROM {$wpdb->posts}
			WHERE post_type = %s
			AND post_author = %d
			GROUP BY post_status
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $type, $user_id ), ARRAY_A );

		$new_counts = [];
		foreach ( $results as $row ) {
			$new_counts[ $row['post_status'] ] = $row['num_posts'];
		}

		// The $counts object has all statuses, so we need to zero out the ones the user doesn't have.
		foreach ( (array) $counts as $status => $count ) {
			if ( ! isset( $new_counts[ $status ] ) ) {
				$counts->{$status} = 0;
			} else {
				$counts->{$status} = $new_counts[ $status ];
			}
		}

		return $counts;
	}
}
