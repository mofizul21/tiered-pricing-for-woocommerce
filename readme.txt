=== Wine Vendor WooCommerce ===
Contributors: mofizul
Tags: woocommerce, vendors, wine vendor
Requires at least: 5.2
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
WC requires at least: 3.0.0
WC tested up to: 10.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Provides a custom dashboard for the Wine Vendor role with access to specific WooCommerce menus and data, including Products, Orders, Customers, Sales Reports, and Analytics.

== Description ==

This plugin creates a dedicated "Wine Vendor" user role in your WooCommerce store. It provides a simplified and secure dashboard for your vendors, giving them access only to the tools and data they need to manage their products and sales.

When a customer adds a product from a wine vendor to their cart, they can only check out with products from that same vendor. This ensures a streamlined purchasing process and avoids conflicts between different vendors' products in a single order.

Additionally, all products sold by wine vendors are automatically assigned a "Zero Rate" tax class, simplifying tax management for both you and your vendors.

== Features ==

*   **"Wine Vendor" User Role:** Creates a dedicated "Wine Vendor" user role with specific permissions.
*   **Restricted Admin Access:** Provides a simplified dashboard for Wine Vendors, limiting access to only essential WooCommerce sections like Products, Orders, and Reports.
*   **Vendor-Specific Products:** Allows vendors to manage their own product listings.
*   **Isolated Product Categories:** Vendors can create and manage their own product categories and tags, which are kept separate from those of other vendors.
*   **Cart & Checkout Rules:** Prevents customers from purchasing products from different vendors in the same order.
*   **Custom Tax Handling:** Applies a "Zero Rate" tax class to all products sold by Wine Vendors.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/wine-vendor-woocommerce` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Assign the 'Wine Vendor' role to your vendors.

== Frequently Asked Questions ==

= Can a customer buy from multiple vendors at once? =

No. To ensure a smooth checkout process, customers can only purchase products from a single vendor at a time.

= How are taxes handled for wine vendor products? =

All products sold by users with the "Wine Vendor" role are automatically assigned the "Zero Rate" tax class.

= What can a Wine Vendor see and do? =

Wine Vendors have a simplified dashboard with access to manage their products, view their orders, and see sales reports. They cannot access other areas of the WordPress admin.

== Screenshots ==

1. The simplified dashboard for Wine Vendors.

== Changelog ==

= 1.0.0 - 2025-10-04 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
* Initial release.