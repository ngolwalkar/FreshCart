<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class My_Cart_REST_API {
	protected $controllers = array();
	public function __construct() {
		// If WooCommerce does not exists then do nothing!
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->maybe_load_cart();
		$this->rest_api_includes();

		// Hook into WordPress ready to init the REST API as needed.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}
	
	protected function get_controller() {
		return array(
			'wc-rest-cart' => 'WC_REST_Cart_Controller',
		);
	}
	
	protected function get_rest_namespaces() {
		return array(
			'my-cart/v1' => $this->get_controller(),
		);
	}
	
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				if ( class_exists( $controller_class ) ) {
					$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
					$this->controllers[ $namespace ][ $controller_name ]->register_routes();
				}
			}
		}
	}

	
	public function rest_api_includes() {
		require_once __DIR__ . '/api/class-wc-rest-cart-controller.php';
	} 
	
	private function maybe_load_cart() {

		require_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		require_once WC_ABSPATH . 'includes/wc-notice-functions.php';

		// Initialize session.
		$this->initialize_session();

		// Initialize cart.
		$this->initialize_cart();


		if ( is_null( WC()->cart ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}
	}
	
	public function initialize_session() {
		$session_class = 'My_Cart_Session_Handler';
		
		if(!class_exists($session_class)){
			include_once My_CART_ABSPATH . 'includes/abstracts/abstract-my-cart-session.php';
			require_once My_CART_ABSPATH . 'includes/class-my-cart-session-handler.php';
		}

		if ( is_null( WC()->session ) || ! WC()->session instanceof $session_class ) {
			if ( false === strpos( $session_class, '\\' ) ) {
				$session_class = '\\' . $session_class;
			}
			WC()->session = new $session_class();
			WC()->session->init();
		}
	}
	
	public function initialize_cart() {
		if ( is_null( WC()->customer ) || ! WC()->customer instanceof WC_Customer ) {
			$customer_id = strval( get_current_user_id() );

			WC()->customer = new WC_Customer( $customer_id, true );

			add_action( 'shutdown', array( WC()->customer, 'save' ), 10 );
		}

		if ( is_null( WC()->cart ) || ! WC()->cart instanceof WC_Cart ) {
			WC()->cart = new WC_Cart();
		}
	}
	
}

return new My_Cart_REST_API();