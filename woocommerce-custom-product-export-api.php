<?php
/**
 * Plugin Name:       WooCommerce Custom Product Export API
 * Plugin URI:        https://woocommerce.com
 * Description:       Adds a custom REST API endpoint to trigger a WooCommerce product CSV export.
 * Version:           0.1
 * Author:            Riaan Knoetze
 * Author URI:        https://woocommerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-custom-product-export-api
 * Domain Path:       /languages
 *
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 7.4
 *
 * WC requires at least: 7.4
 * WC tested up to: 8.8
 *
 * @package WooCommerce_Custom_Product_Export_API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Hook into the REST API init action to register the custom endpoint.
add_action( 'rest_api_init', 'register_custom_product_export_endpoint' );
load_plugin_textdomain( 'woocommerce-custom-product-export-api', false, basename( dirname( __FILE__ ) ) . '/languages' );

/**
 * Registers the custom product export endpoint.
 */
function register_custom_product_export_endpoint() {
	register_rest_route(
		'wc/v3',
		'/export-products',
		array(
			'methods'             => 'GET',
			'callback'            => 'handle_product_export',
			'permission_callback' => 'can_export_products',
		)
	);
}

/**
 * Checks if the current user can export products.
 *
 * @param WP_REST_Request $request The request object.
 * @return bool True if the user has the capability to export products, false otherwise.
 */
function can_export_products( $request ) {
	// Perform capability checks to ensure the user has permission to export products.
	// For example, check if the user has the 'export' capability or is an administrator.
	return current_user_can( 'export' );
}

/**
 * Handles the product export request.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_Error|void
 */
function handle_product_export( $request ) {
	// Check for user permissions.
	if ( ! current_user_can( 'export' ) ) {
		return new WP_Error( 'export_error', esc_html__( 'You do not have permission to export products.', 'woocommerce-custom-product-export-api' ), array( 'status' => 403 ) );
	}

	// Perform a product query to get all products.
	$query    = new WC_Product_Query(
		array(
			'limit'  => -1,
			'status' => 'publish',
			'return' => 'objects',
		)
	);
	$products = $query->get_products();

	// Prepare CSV data.
	$headers = array(
		'Product ID',
		'Product Name',
		'Product SKU',
		'Regular Price',
		'Sale Price',
		// Add any other product fields here.
	);

	// Find all unique meta keys across all products.
	$meta_keys = array();
	foreach ( $products as $product ) {
		$product_meta_data = get_post_meta( $product->get_id() );
		foreach ( $product_meta_data as $key => $value ) {
			if ( ! in_array( $key, $meta_keys, true ) ) { // Use strict comparison.
				$meta_keys[] = $key;
			}
		}
	}

	// Merge headers with meta keys.
	$headers = array_merge( $headers, $meta_keys );

	// Start CSV output.
	$csv_data = '"' . implode( '","', $headers ) . '"' . PHP_EOL;

	foreach ( $products as $product ) {
		// Collect product data.
		$row = array(
			$product->get_id(),
			$product->get_name(),
			$product->get_sku(),
			$product->get_regular_price(),
			$product->get_sale_price(),
			// Add any other product fields here.
		);

		// Collect product meta data.
		foreach ( $meta_keys as $meta_key ) {
			$meta_value = get_post_meta( $product->get_id(), $meta_key, true );
			$row[]      = is_array( $meta_value ) ? implode( '|', $meta_value ) : $meta_value;
		}

		// Sanitize data to prevent CSV injection.
		array_walk(
			$row,
			function( &$value, $key ) {
				$value = str_replace( array( "\n", "\r", '"' ), '', $value );
			}
		);

		// Add a new row to the CSV data.
		$csv_data .= '"' . implode( '","', $row ) . '"' . PHP_EOL;
	}

	// Create a temporary file in a secure location for the CSV data.
	$uploads_dir      = wp_upload_dir();
	$export_file      = tempnam( $uploads_dir['path'], 'wc-export-' );
	$export_file_path = $export_file . '.csv';

	// Save the CSV data to the temporary file.
	file_put_contents( $export_file_path, $csv_data );

	// Serve the file to the client.
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . esc_html__( 'wc-products-export', 'woocommerce-custom-product-export-api' ) . '.csv"' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $export_file_path ) );
	readfile( $export_file_path );

	// Clean up the file after we're done.
	@unlink( $export_file_path );

	// End the script execution to prevent WordPress from sending a JSON response.
	exit;
}
