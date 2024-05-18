<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'My_Cart_Session' ) ) {
	return;
}

class My_Cart_Session_Handler extends My_Cart_Session {

	protected $_cookie;
	protected $_cart_expiring;
	protected $_cart_expiration;
	protected $_cart_source;
	protected $_has_cookie = false;
	protected $_table;
	public $counter = 0;

	public function __construct() {
		$this->_cookie = 'wp_my_cart_session_' . COOKIEHASH;
		$this->_table = $GLOBALS['wpdb']->prefix . 'woocommerce_sessions';
	}
	
	public function init() {
		$current_user_id = strval( get_current_user_id() );

		$this->init_session_cookie( $current_user_id );
		add_action( 'woocommerce_set_cart_cookies', array( $this, 'set_customer_cart_cookie' ), 20 );
		add_action( 'shutdown', array( $this, 'save_cart' ), 20 );
	}
	
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
			return false;
		}

		$cookie_value = explode( '||', $cookie_value );

		$customer_id     = $cookie_value[0];
		$cart_expiration = $cookie_value[1];
		$cart_expiring   = $cookie_value[2];
		$cookie_hash     = '';

		if ( empty( $customer_id ) ) {
			return false;
		}

		// Validate hash.
		$to_hash = $customer_id . '|' . $cart_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $cart_expiration, $cart_expiring, $cookie_hash );
	}
	
	public function get_session( $cart_key, $default_value = false ) {
		return $this->get_cart_data();
	} 
	
	public function init_session_cookie( $current_user_id = 0 ) {
		
		global $wpdb;
		
		$cookie = false;

		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false; 

		if ( !empty( $cookie_value ) && is_string( $cookie_value ) && false !== $cookie_value ) {
			$cookie_value = explode( '||', $cookie_value );

			$customer_id     = $cookie_value[0];
			$cart_expiration = $cookie_value[1];
			$cart_expiring   = $cookie_value[2];
			$cookie_hash     = $cookie_value[3];

			if ( !empty( $customer_id ) ) {
				$to_hash = $customer_id . '|' . $cart_expiration;
				$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

				if ( !empty( $cookie_hash ) && hash_equals( $hash, $cookie_hash ) ) {
					$cookie = array( $customer_id, $cart_expiration, $cart_expiring, $cookie_hash );
				}
			}
		}

		if ( $cookie ) {
			$this->_customer_id     = $cookie[0];
			$this->_cart_expiration = $cookie[1];
			$this->_cart_expiring   = $cookie[2];
			$this->_has_cookie      = true;
		}


		if ( isset( $_REQUEST['cart_key'] ) ) {
			$this->_customer_id = (string) trim( sanitize_key( wp_unslash( $_REQUEST['cart_key'] ) ) );
		}

		if ( is_numeric( $current_user_id ) && $current_user_id > 0 ) {
				$this->_customer_id = $current_user_id;
		}

		if ( $cookie || $this->_customer_id ) {
			$this->_data = $this->get_cart_data();

			if ( is_numeric( $current_user_id ) && $current_user_id > 0 && $current_user_id !== $this->_customer_id ) {
				$this->set_customer_cart_cookie( false );

				$guest_cart_id      = $this->_customer_id;
				$this->_customer_id = $current_user_id;

				$this->save_cart( $guest_cart_id );

				$this->set_customer_cart_cookie( true );
			}

			if ( time() > $this->_cart_expiring || empty( $this->_cart_expiring ) ) {
				$this->set_cart_expiration();
				$this->update_cart_timestamp( $this->_customer_id, $this->_cart_expiration );
			}
		} else {
			$this->set_cart_expiration();
			$this->_customer_id = $this->generate_customer_id();
			$this->set_customer_cart_cookie( true );
		}
	}
	
	public function generate_customer_id() {
		$customer_id = '';

		$current_user_id = strval( get_current_user_id() );
		if ( is_numeric( $current_user_id ) && $current_user_id > 0 ) {
			$customer_id = $current_user_id;
		}

		if ( empty( $customer_id ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';

			$hasher      = new PasswordHash( 8, false );
			$customer_id =  md5( $hasher->get_random_bytes( 32 ) );
		}

		return $customer_id;
	}
	
	
	public function get_cart_data(){
		global $wpdb;
		$data = false;
		if( $this->has_session() ){
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $this->_customer_id ) );
			if ( !is_null( $value ) ) {
				$data = maybe_unserialize( $value );
			}
		}
		return $data;
	}
	
	public function set_cart_expiration() {
		$this->_cart_expiring   = time() + intval( DAY_IN_SECONDS * 6 ); // 6 Days.
		$this->_cart_expiration = time() + intval( DAY_IN_SECONDS * 7 ); // 7 Days.
	}
	
	public function update_cart_timestamp( $cart_key, $timestamp ) {
		global $wpdb;

		$wpdb->update(
			$this->_table,
			array( 'session_expiry' => $timestamp ),
			array( 'session_key' => $cart_key ),
			array( '%d' ),
			array( '%s' )
		);
	} 
	
	public function has_session() {
		if ( isset( $_COOKIE[ $this->_cookie ] ) ) {
			return true;
		}

		// Current user ID. If value is above zero then user is logged in.
		$current_user_id = strval( get_current_user_id() );
		if ( is_numeric( $current_user_id ) && $current_user_id > 0 ) {
			return true;
		}

		if ( ! empty( $this->_customer_id ) ) {
			return true;
		}

		return false;
	}
	
	public function set_customer_cart_cookie( $set = true ) {
		if ( $set ) {
			$to_hash           = $this->_customer_id . '|' . $this->_cart_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_customer_id . '||' . $this->_cart_expiration . '||' . $this->_cart_expiring . '||' . $cookie_hash;
			$this->_has_cookie = true;

			// If no cookie exists then create a new.
			if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {
				setcookie( $this->_cookie, $cookie_value, $this->_cart_expiration, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $this->use_secure_cookie(), $this->use_httponly() );
			}
		} else {
			// If cookies exists, destroy it.
			if ( isset( $_COOKIE[ $this->_cookie ] ) ) {
				setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $this->use_secure_cookie(), $this->use_httponly() );
				unset( $_COOKIE[ $this->_cookie ] );
			}
		}
	}
	
	protected function use_secure_cookie() {
		return apply_filters( 'cocart_cart_use_secure_cookie', wc_site_is_https() && is_ssl() );
	} // END use_secure_cookie()

	protected function use_httponly() {
		$httponly = true;
		return $httponly;
	} // END use_httponly()

	
	public function save_cart( $old_cart_key = 0 ) {
		$this->counter++;
		if ( $this->has_session() ) {
			global $wpdb;
			
			$data = $this->_data;
			
			$cart = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE save_key = %s", $this->_customer_id ) );
			
			if ( ! empty( $data ) && empty( $cart ) ) {
				if ( ! isset( $data['cart'] ) || empty( maybe_unserialize( $data['cart'] ) ) ) {
					$data = false;
				}
			}

			$this->data = $data;

			if ( ! $this->_data || empty( $this->_data ) || is_null( $this->_data ) ) {
				return true;
			}
			
			//$this->set_cart_hash();

			// Save or update cart data.
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $this->_table (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)
 					ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)",
					$this->_customer_id,
					maybe_serialize( $this->_data ),
					$this->_session_expiration
				)
			);
			if ( get_current_user_id() !== $old_cart_key && ! is_object( get_user_by( 'id', $old_cart_key ) ) ) {
				$this->delete_cart( $old_cart_key );
			}
		}
	}
	
	public function delete_cart( $cart_key ) {
		global $wpdb;
		$wpdb->delete( $this->_table, array( 'cart_key' => $cart_key ), array( '%s' ) );
	}

}