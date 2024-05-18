<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


abstract class My_Cart_Session extends WC_Session {

	protected $_cart_hash;

	public function get_customer_id() {
		return $this->_customer_id;
	}

	public function set_customer_id( $customer_id ) {
		$this->_customer_id = $customer_id;
	}

	public function get_data() {
		return $this->_data;
	}

	public function get_cart_hash() {
		return $this->_cart_hash;
	}
}
