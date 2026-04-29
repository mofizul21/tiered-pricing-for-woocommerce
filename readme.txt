=== Tiered Pricing for WooCommerce ===
Contributors: mofizul
Tags: woocommerce, tiered pricing, quantity pricing, wholesale pricing
Requires at least: 5.2
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
WC requires at least: 3.0.0
WC tested up to: 10.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Starter plugin scaffold for building tiered pricing features on WooCommerce products.

== Description ==

This plugin has been cleaned and converted into a starter WooCommerce extension for tiered pricing development.

It includes:

* Display of tiered pricing fields through shortcodes on the frontend [tpfw_pricing_table]
* A proper WooCommerce plugin bootstrap.
* A WooCommerce submenu settings page.
* Starter product-level fields for enabling tiered pricing and entering pricing tiers.
* A basic frontend pricing table renderer for single product pages.
* Clean placeholders for future pricing calculation logic.

== Features ==

* Product-level starter fields for tiered pricing data.
* WooCommerce admin settings page.
* Frontend tier table output.
* HPOS compatibility declaration.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/tiered-pricing-for-woocommerce` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Make sure WooCommerce is installed and active.

== Frequently Asked Questions ==

= Does this plugin calculate tiered prices already? =

Not yet. This is now a starter development plugin with product fields and frontend output only.

= Where do I enter tier rules? =

Open a WooCommerce product, enable tiered pricing in the pricing section, and add one rule per line using `quantity:price`.

= What is included now? =

The plugin includes only starter architecture, settings, product fields, and a display table so future commands can add pricing logic cleanly.

== Screenshots ==

1. Tier settings in the WooCommerce product editor.

== Changelog ==

= 1.0.0 =
* Converted legacy inherited code into a clean WooCommerce starter scaffold for tiered pricing.

== Upgrade Notice ==

= 1.0.0 =
* Initial release.
