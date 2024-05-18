<?php
/**
 * Plugin Name: My Cart REST
 * Description: Make your WooCommerce store headless with My Cart Rest, a REST API designed for decoupling.
 * Author:      Navneet Golwalkar
 * Version:     1.0.0
 * Text Domain: my-cart-rest
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'My_CART_FILE' ) ) {
	define( 'My_CART_FILE', __FILE__ );
}

if ( ! class_exists( 'My_Cart', false ) ) {
	include_once untrailingslashit( plugin_dir_path( My_CART_FILE ) ) . '/includes/class-mycart.php';
}

if ( ! function_exists( 'My_Cart' ) ) {

	function My_Cart() {
		return My_Cart::init();
	}

	My_Cart();
}
