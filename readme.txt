=== WooCommerce Custom Product Export API ===
Contributors: riaanknoetze
Tags: woocommerce, products, export, csv, api
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Adds a custom REST API endpoint to trigger a WooCommerce product CSV export.

== Description ==

The WooCommerce Custom Product Export API plugin adds a new REST API endpoint to WooCommerce. This endpoint allows authorized users to export product data to a CSV file. The plugin is designed to be simple to use and requires minimal configuration.

The export includes product ID, name, SKU, regular price, sale price, and all unique product meta fields. This plugin is ideal for store owners who need to perform regular product data analyses or migrations.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-custom-product-export-api` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Access the newly created API endpoint `wp-json/wc/v3/export-products` to trigger a .csv file download (Please remember authentication). For example: https://www.yourdomain.net/wp-json/wc/v3/export-products?consumer_key=123&consumer_secret=abc

== Frequently Asked Questions ==

= What user role is required to perform an export? =

Users must have the 'export' capability, typically available to administrators and shop managers.

= How is the CSV file accessed? =

After triggering the export via the REST API endpoint, the CSV file is automatically downloaded to your local system.

= Can I export custom meta fields for products? =

Yes, the plugin automatically includes all unique meta fields for products in the export.

== Changelog ==

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
Initial release.