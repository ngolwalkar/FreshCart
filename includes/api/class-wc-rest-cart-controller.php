<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_REST_Cart_Controller {

	protected $namespace = 'fl-cart/v1';


	protected $rest_base = 'cart';

	public function register_routes() {
		// View Cart - fl-cart/v1/cart (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_complete_cart_details' ),
			'args'     => array(
				'thumb' => array(
					'default' => null
				),
			),
		));

		// Count Items in Cart - fl-cart/v1/cart/count-items (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base  . '/count-items', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_cart_contents_count' ),
			'args'     => array(
				'return' => array(
					'default' => 'numeric'
				),
			),
		));

		// Get Cart Totals - fl-cart/v1/cart/totals (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base  . '/totals', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_totals' ),
		));

		// Clear Cart - fl-cart/v1/cart/clear (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base  . '/clear', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'clear_cart' ),
		));

		// Add Item - fl-cart/v1/cart/add (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/add', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'add_to_cart' ),
			'args'     => array(
				'product_id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				),
				'quantity' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				),
				'variation_id' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				),
				'variation' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_array( $param );
					}
				),
				'cart_item_data' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return is_array( $param );
					}
				),
				'subscription_scheme' => array( // New argument for subscription details
					'validate_callback' => function($param, $request, $key) {
						return is_string($param);
					}
				)
			)
		) );

		// Calculate Cart Total - fl-cart/v1/cart/calculate (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base  . '/calculate', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'calculate_totals' ),
		));

		// Update, Remove or Restore Item - fl-cart/v1/cart/cart-item (GET, POST, DELETE)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/cart-item', array(
			'args' => array(
				'cart_item_key' => array(
					'description' => __( 'The cart item key is what identifies the item in the cart.', 'cart-rest-api-for-woocommerce' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'restore_item' ),
			),
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'update_item' ),
				'args'     => array(
					'quantity' => array(
						'default' => 1,
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						}
					),
				),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'remove_item' ),
			),
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/apply-coupon', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'apply_coupon' ),
			'args'     => array(
				'coupon_code' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				)
			)
		));

		// Remove Coupon - fl-cart/v1/cart/remove-coupon (POST)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/remove-coupon', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'remove_coupon' ),
			'args'     => array(
				'coupon_code' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				)
			)
		));
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipping-methods', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_shipping_methods' ),
		));
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/set-shipping-method', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'set_shipping_method' ),
			'args'     => array(
				'shipping_method' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'package_id' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_numeric( $param );
					}
				)
			)
		));
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/create-order', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'add_order_from_cart' ),
			'args'     => array(
				'payment_method' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'payment_method_title' => array(
					'required' => false,
					'validate_callback' => function( $param, $request, $key ) {
						return is_string( $param );
					}
				),
				'set_paid' => array(
					'required' => false,
					'validate_callback' => function( $param, $request, $key ) {
						return is_bool( $param );
					}
				),
				'meta_data' => array(
					'required' => false,
					'validate_callback' => function( $param, $request, $key ) {
						return is_array( $param );
					}
				),
			)
		));
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/set-customer-details', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'set_customer_details' ),
			'args'     => array(
				'billing' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_array( $param );
					}
				),
				'shipping' => array(
					'required' => true,
					'validate_callback' => function( $param, $request, $key ) {
						return is_array( $param );
					}
				),
				'personal' => array(
					'required' => false,
					'validate_callback' => function( $param, $request, $key ) {
						return is_array( $param );
					}
				),
			)
		));
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/payment-gateways', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array( $this, 'get_payment_gateways' ),
        ));
		
		register_rest_route($this->namespace, '/' . $this->rest_base . '/products/(?P<product_id>\d+)/subscription-options', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_subscription_options'),
			'args'     => array(
				'product_id' => array(
					'required' => true,
					'validate_callback' => function($param, $request, $key) {
						return is_numeric($param);
					}
				)
			)
		));
		
	} // register_routes()
	
	public function get_subscription_options($request) {
		$product_id = absint($request['product_id']);
		$product = wc_get_product($product_id);

		if (!$product || !$product->is_type(array('simple', 'variable'))) {
			return new WP_Error('invalid_product', __('Invalid product.', 'cart-rest-api-for-woocommerce'), array('status' => 404));
		}

		$subscription_options = array();

		// Check if the product has subscription options
		if (class_exists('WCS_ATT_Product_Schemes')) {
			$product_schemes = WCS_ATT_Product_Schemes::get_subscription_schemes($product);
			foreach ($product_schemes as $scheme) {
				$subscription_options[] = array(
					'id'            => $scheme->get_key(),
					'price'         => wc_price(WC_Subscriptions_Product::get_price($product, $scheme->get_key())),
					'billing_period'=> $scheme->get_period(),
					'interval'      => $scheme->get_interval(),
					'trial_length'  => $scheme->get_trial_length(),
					'trial_period'  => $scheme->get_trial_period(),
				);
			}
		}

		return new WP_REST_Response($subscription_options, 200);
	}
	
	
	public function get_payment_gateways( $request ) {
        // Get available payment gateways
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $gateways = array();

        foreach ( $available_gateways as $gateway ) {
            $gateways[] = array(
                'id' => $gateway->id,
                'title' => $gateway->get_title(),
                'description' => $gateway->get_description(),
                'enabled' => $gateway->enabled,
            );
        }

        return new WP_REST_Response( $gateways, 200 );
    }
	
	
	public function set_customer_details( $request ) {
		
		
		$billing_details = $request['billing'];
		$shipping_details = $request['shipping'];
		$personal_details = isset( $request['personal'] ) ? $request['personal'] : array();
		
		$customer_id = get_current_user_id();
		if (!$customer_id) {
			$customer_id = 0; // Guest user
			WC()->customer = new WC_Customer($customer_id);
			WC()->customer->set_email($personal_details['email']);
		} else {
			$current_user = wp_get_current_user();
			WC()->customer = new WC_Customer($customer_id);
			WC()->customer->set_email($current_user->user_email);
		}

		// Set personal details
		if ( ! empty( $personal_details ) ) {
			WC()->customer->set_first_name( sanitize_text_field( $personal_details['first_name'] ) );
			WC()->customer->set_last_name( sanitize_text_field( $personal_details['last_name'] ) );
		}

		// Set billing details
		foreach ( $billing_details as $key => $value ) {
			$method = "set_billing_$key";
			if ( method_exists( WC()->customer, $method ) ) {
				WC()->customer->$method( sanitize_text_field( $value ) );
			}
		}

		// Set shipping details
		foreach ( $shipping_details as $key => $value ) {
			$method = "set_shipping_$key";
			if ( method_exists( WC()->customer, $method ) ) {
				WC()->customer->$method( sanitize_text_field( $value ) );
			}
		}

		// Save the customer data
		WC()->customer->save();

		return new WP_REST_Response( array(
			'message' => __( 'Customer details set successfully.', 'cart-rest-api-for-woocommerce' ),
		), 200 );
	}
	
	
	public function add_order_from_cart( $request = array() ) {
		try {
			
			WC()->cart->calculate_totals();
			// Retrieve billing and shipping details from the cart
			$billing_details = WC()->customer->get_billing();
			$shipping_details = WC()->customer->get_shipping();

			$payment_method = sanitize_text_field( $request['payment_method'] );
			$payment_method_title = isset( $request['payment_method_title'] ) ? sanitize_text_field( $request['payment_method_title'] ) : '';
			$set_paid = isset( $request['set_paid'] ) ? (bool) $request['set_paid'] : false;
			$meta_data = isset( $request['meta_data'] ) ? $request['meta_data'] : array();

			// Create the order and set details
			$order = wc_create_order();
			$customer_id = get_current_user_id();
			if (!$customer_id) {
				$customer_id = 0; // Guest user
			}
			var_dump($customer_id);
			$order->set_customer_id($customer_id);
			$order->set_address( $billing_details, 'billing' );
			$order->set_address( $shipping_details, 'shipping' );
			$order->set_payment_method( $payment_method );
			$order->set_payment_method_title( $payment_method_title );

			// Add meta data and cart items
			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $meta_key => $meta_value ) {
					$order->update_meta_data( $meta_key, $meta_value );
				}
			}
			
			// Add items from the cart
			foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
				$product = $values['data'];
				$item = new WC_Order_Item_Product();
				$item->set_product($product);
				$item->set_quantity($values['quantity']);
				$item->set_subtotal($values['line_subtotal']);
				$item->set_total($values['line_total']);

				// Add subscription scheme if applicable
				if (isset($values['wcsatt_data']['active_subscription_scheme'])) {
					$scheme_key = $values['wcsatt_data']['active_subscription_scheme'];
					$scheme = WCS_ATT_Product_Schemes::get_subscription_scheme($product, $scheme_key);

					if ($scheme) {
						$item->add_meta_data('_subscription_scheme', $scheme_key);
						$item->add_meta_data('_subscription_period', $scheme->get_period());
						$item->add_meta_data('_subscription_interval', $scheme->get_interval());
						$item->add_meta_data('_subscription_trial_length', $scheme->get_trial_length());
						$item->add_meta_data('_subscription_trial_period', $scheme->get_trial_period());
					}
				}

				// Add item to order
				$order->add_item($item);
			}

			// Calculate totals
			$order->calculate_totals();

			// Set status and save order
			if ($set_paid) {
				$order->set_status('completed');
			} else {
				$order->set_status('pending');
			}

			$order->save();

			// Trigger WooCommerce Subscriptions hooks to process subscriptions
			do_action('woocommerce_checkout_order_processed', $order->get_id(), $request->get_params(), $order);

			// Clear the cart
			WC()->cart->empty_cart();

			return new WP_REST_Response(array(
				'order_id' => $order->get_id(),
				'message' => __('Order created successfully.', 'cart-rest-api-for-woocommerce'),
			), 200);
					
		} catch ( CoCart_Data_Exception $e ) {
			return CoCart_Response::get_error_response( $e->getErrorCode(), $e->getMessage(), $e->getCode(), $e->getAdditionalData() );
		}
	} // END add_to_cart()
	
	
	public function set_shipping_method( $request ) {
		$shipping_method = sanitize_text_field( $request['shipping_method'] );
		$package_id = absint( $request['package_id'] );

		WC()->session->set('chosen_shipping_methods', array());

		// Optionally, you might want to recalculate shipping and totals
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
		
		$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods', []);

		$shipping_added = false;
		
		$packages = WC()->shipping()->get_packages();
		
		if ( ! isset( $packages[ $package_id ] ) ) {
			return new WP_Error( 'wc_cart_rest_invalid_package_id', __( 'Invalid package ID.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 400 ) );
		}
		
		foreach ($packages as $i => $package) {
				// Loop through available methods for the package
				foreach ($package['rates'] as $rate_id => $rate) {
					if ($rate_id === $shipping_method) {
						$chosen_shipping_methods[$i] = $rate_id;
						WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
						WC()->cart->calculate_totals();
						$shipping_added = true;
					}
				}
			}

		if($shipping_added){
			return new WP_REST_Response( array(
				'message' => __( 'Shipping method set successfully.', 'cart-rest-api-for-woocommerce' ),
				'chosen_methods' => $chosen_shipping_methods
			), 200 );
		}else{
			return new WP_Error( 'wc_cart_rest_invalid_shipping_method', __( 'Invalid shipping method.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 400 ) );
		}
	}
	
	public function get_shipping_methods( $request ) {
		
		// Set the shipping destination details
		$country  = ! empty( $request['country'] ) ? sanitize_text_field( $request['country'] ) : WC()->customer->get_shipping_country();
		$state    = ! empty( $request['state'] ) ? sanitize_text_field( $request['state'] ) : WC()->customer->get_shipping_state();
		$postcode = ! empty( $request['postcode'] ) ? sanitize_text_field( $request['postcode'] ) : WC()->customer->get_shipping_postcode();
		$city     = ! empty( $request['city'] ) ? sanitize_text_field( $request['city'] ) : WC()->customer->get_shipping_city();

		WC()->customer->set_shipping_country( $country );
		WC()->customer->set_shipping_state( $state );
		WC()->customer->set_shipping_postcode( $postcode );
		WC()->customer->set_shipping_city( $city );

		// Calculate shipping packages
		WC()->cart->calculate_shipping();

		$packages = WC()->cart->get_shipping_packages();
		$available_methods = array();

		foreach ( $packages as $package_id => $package ) {
			$available_methods[ $package_id ] = array(
				'package_details' => $package,
				'methods' => array()
			);

			$shipping_rates = WC()->shipping->calculate_shipping_for_package( $package );

			if ( isset( $shipping_rates['rates'] ) && ! empty( $shipping_rates['rates'] ) ) {
				foreach ( $shipping_rates['rates'] as $rate_id => $rate ) {
					$available_methods[ $package_id ]['methods'][ $rate_id ] = array(
						'id' => $rate->get_id(),
						'label' => $rate->get_label(),
						'cost' => $rate->get_cost(),
						'taxes' => $rate->get_taxes(),
					);
				}
			}
		}

		return new WP_REST_Response( $available_methods, 200 );
	}
	
	public function apply_coupon( $request ) {
		$coupon_code = sanitize_text_field( $request['coupon_code'] );

		if ( ! WC()->cart->has_discount( $coupon_code ) ) {
			WC()->cart->add_discount( $coupon_code );
			
			if ( WC()->cart->has_discount( $coupon_code ) ) {
				return new WP_REST_Response( array( 'message' => __( 'Coupon applied successfully.', 'cart-rest-api-for-woocommerce' ) ), 200 );
			} else {
				return new WP_Error( 'wc_cart_rest_apply_coupon_failed', __( 'Failed to apply coupon. Please check the coupon code.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_Error( 'wc_cart_rest_coupon_already_applied', __( 'Coupon already applied.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 400 ) );
		}
	}

	public function remove_coupon( $request ) {
		$coupon_code = sanitize_text_field( $request['coupon_code'] );

		if ( WC()->cart->has_discount( $coupon_code ) ) {
			WC()->cart->remove_coupon( $coupon_code );
			
			if ( ! WC()->cart->has_discount( $coupon_code ) ) {
				return new WP_REST_Response( array( 'message' => __( 'Coupon removed successfully.', 'cart-rest-api-for-woocommerce' ) ), 200 );
			} else {
				return new WP_Error( 'wc_cart_rest_remove_coupon_failed', __( 'Failed to remove coupon. Please try again.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_Error( 'wc_cart_rest_coupon_not_found', __( 'Coupon not found in cart.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 404 ) );
		}
	}

	public function get_cart( $data = array() ) {
		$cart = WC()->cart->get_cart();

		if ( $this->get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( array(), 200 );
		}

		$show_thumb = ! empty( $data['thumb'] ) ? $data['thumb'] : false;

		foreach ( $cart as $item_key => $cart_item ) {
			$_product = apply_filters( 'wc_cart_rest_api_cart_item_product', $cart_item['data'], $cart_item, $item_key );

			// Adds the product name as a new variable.
			$cart[$item_key]['product_name'] = $_product->get_name();

			// If main product thumbnail is requested then add it to each item in cart.
			if ( $show_thumb ) {
				$thumbnail_id = apply_filters( 'wc_cart_rest_api_cart_item_thumbnail', $_product->get_image_id(), $cart_item, $item_key );

				$thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_thumbnail' );

				// Add main product image as a new variable.
				$cart[$item_key]['product_image'] = esc_url( $thumbnail_src[0] );
			}
		}

		return new WP_REST_Response( $cart, 200 );
	} // END get_cart()


	public function get_cart_contents_count( $data = array() ) {
		$count = WC()->cart->get_cart_contents_count();

		$return = ! empty( $data['return'] ) ? $data['return'] : '';

		if ( $return != 'numeric' && $count <= 0 ) {
			return new WP_REST_Response( __( 'There are no items in the cart!', 'cart-rest-api-for-woocommerce' ), 200 );
		}

		return $count;
	} // END get_cart_contents_count()

	public function clear_cart() {
		WC()->cart->empty_cart();
		WC()->session->set('cart', array()); // Empty the session cart data

		if ( WC()->cart->is_empty() ) {
			return new WP_REST_Response( __( 'Cart is cleared.', 'cart-rest-api-for-woocommerce' ), 200 );
		} else {
			return new WP_Error( 'wc_cart_rest_clear_cart_failed', __( 'Clearing the cart failed!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END clear_cart()

	protected function validate_product_id( $product_id ) {
		if ( $product_id <= 0 ) {
			return new WP_Error( 'wc_cart_rest_product_id_required', __( 'Product ID number is required!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}

		if ( ! is_numeric( $product_id ) ) {
			return new WP_Error( 'wc_cart_rest_product_id_not_numeric', __( 'Product ID must be numeric!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END validate_product_id()


	protected function validate_quantity( $quantity ) {
		if ( ! is_numeric( $quantity ) ) {
			return new WP_Error( 'wc_cart_rest_quantity_not_numeric', __( 'Quantity must be numeric!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END validate_quantity()


	protected function validate_product( $product_id = null, $quantity = 1 ) {
		$this->validate_product_id( $product_id );

		$this->validate_quantity( $quantity );
	} // END validate_product()


	protected function has_enough_stock( $current_data = array(), $quantity = 1 ) {
		$product_id      = ! isset( $current_data['product_id'] ) ? 0 : absint( $current_data['product_id'] );
		$variation_id    = ! isset( $current_data['variation_id'] ) ? 0 : absint( $current_data['variation_id'] );
		$current_product = wc_get_product( $variation_id ? $variation_id : $product_id );

		$quantity = absint( $quantity );

		if ( ! $current_product->has_enough_stock( $quantity ) ) {
			return new WP_Error( 'wc_cart_rest_not_enough_in_stock', sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'cart-rest-api-for-woocommerce' ), $current_product->get_name(), wc_format_stock_quantity_for_display( $current_product->get_stock_quantity(), $current_product ) ), array( 'status' => 500 ) );
		}

		return true;
	} // END has_enough_stock()


	public function add_to_cart($request) {
		$product_id = !isset($request['product_id']) ? 0 : absint($request['product_id']);
		$quantity = !isset($request['quantity']) ? 1 : absint($request['quantity']);
		$variation_id = !isset($request['variation_id']) ? 0 : absint($request['variation_id']);
		$variation = !isset($request['variation']) ? array() : $request['variation'];
		$cart_item_data = !isset($request['cart_item_data']) ? array() : $request['cart_item_data'];
		$subscription_scheme = !isset($request['subscription_scheme']) ? '' : sanitize_text_field($request['subscription_scheme']);

		// Validate the product
		$this->validate_product($product_id, $quantity);

		$product_data = wc_get_product($variation_id ? $variation_id : $product_id);

		if (!$product_data || 'trash' === $product_data->get_status()) {
			return new WP_Error('wc_cart_rest_product_does_not_exist', __('Warning: This product does not exist!', 'cart-rest-api-for-woocommerce'), array('status' => 500));
		}

		// Force quantity to 1 if sold individually and check for existing item in cart
		if ($product_data->is_sold_individually()) {
			$quantity = 1;

			foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
				$_product = $values['data'];
				if ($_product->get_id() === $product_id) {
					return new WP_Error('wc_cart_rest_product_sold_individually', sprintf(__('You cannot add another "%s" to your cart.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array('status' => 500));
				}
			}
		}

		// Product is purchasable check
		if (!$product_data->is_purchasable()) {
			return new WP_Error('wc_cart_rest_cannot_be_purchased', __('Sorry, this product cannot be purchased.', 'cart-rest-api-for-woocommerce'), array('status' => 500));
		}

		// Stock check
		if (!$product_data->is_in_stock()) {
			return new WP_Error('wc_cart_rest_product_out_of_stock', sprintf(__('You cannot add "%s" to the cart because the product is out of stock.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array('status' => 500));
		}

		// Stock check - this time accounting for what's already in-cart.
		if ($product_data->managing_stock()) {
			$products_qty_in_cart = WC()->cart->get_cart_item_quantities();
			if (isset($products_qty_in_cart[$product_data->get_stock_managed_by_id()]) && ! $product_data->has_enough_stock($products_qty_in_cart[$product_data->get_stock_managed_by_id()] + $quantity)) {
				return new WP_Error(
					'wc_cart_rest_not_enough_stock_remaining',
					sprintf(
						__('You cannot add that amount to the cart — we have %1$s in stock and you already have %2$s in your cart.', 'cart-rest-api-for-woocommerce'),
						wc_format_stock_quantity_for_display($product_data->get_stock_quantity(), $product_data),
						wc_format_stock_quantity_for_display($products_qty_in_cart[$product_data->get_stock_managed_by_id()], $product_data)
					),
					array('status' => 500)
				);
			}
		}

		// Add subscription details to cart item data if provided
		if (!empty($subscription_scheme)) {
			$cart_item_data['wcsatt_data'] = array(
				'active_subscription_scheme' => $subscription_scheme
			);
		}

		// Add item to cart
		$item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);
		
		WC_Subscriptions_Cart::calculate_subscription_totals(WC()->cart->get_total( 'total' ), WC()->cart);

		if ($item_key) {
			$data = $this->get_complete_cart_details();

			do_action('wc_cart_rest_add_to_cart', $item_key, $data);

			if (is_array($data)) {
				return new WP_REST_Response($data, 200);
			}
		} else {
			return new WP_Error('wc_cart_rest_cannot_add_to_cart', sprintf(__('You cannot add "%s" to your cart.', 'cart-rest-api-for-woocommerce'), $product_data->get_name()), array('status' => 500));
		}
	}

	public function remove_item( $data = array() ) {
		$cart_item_key = ! isset( $data['cart_item_key'] ) ? '0' : wc_clean( $data['cart_item_key'] );

		if ( $cart_item_key != '0' ) {
			if ( WC()->cart->remove_cart_item( $cart_item_key ) ) {
				return new WP_REST_Response( __( 'Item has been removed from cart.', 'cart-rest-api-for-woocommerce' ), 200 );
			} else {
				return new WP_ERROR( 'wc_cart_rest_can_not_remove_item', __( 'Unable to remove item from cart.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_ERROR( 'wc_cart_rest_cart_item_key_required', __( 'Cart item key is required!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END remove_item()

	public function restore_item( $data = array() ) {
		$cart_item_key = ! isset( $data['cart_item_key'] ) ? '0' : wc_clean( $data['cart_item_key'] );

		if ( $cart_item_key != '0' ) {
			if ( WC()->cart->restore_cart_item( $cart_item_key ) ) {
				return new WP_REST_Response( __( 'Item has been restored to the cart.', 'cart-rest-api-for-woocommerce' ), 200 );
			} else {
				return new WP_ERROR( 'wc_cart_rest_can_not_restore_item', __( 'Unable to restore item to the cart.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_ERROR( 'wc_cart_rest_cart_item_key_required', __( 'Cart item key is required!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END restore_item()

	public function update_item( $data = array() ) {
		$cart_item_key = ! isset( $data['cart_item_key'] ) ? '0' : wc_clean( $data['cart_item_key'] );
		$quantity      = ! isset( $data['quantity'] ) ? 1 : absint( $data['quantity'] );

		// Allows removing of items if quantity is zero should for example the item was with a product bundle.
		if ( $quantity === 0 ) {
			return $this->remove_item( $data );
		}

		$this->validate_quantity( $quantity );

		if ( $cart_item_key != '0' ) {
			$current_data = WC()->cart->get_cart_item( $cart_item_key ); // Fetches the cart item data before it is updated.

			$this->has_enough_stock( $current_data, $quantity ); // Checks if the item has enough stock before updating.

			if ( WC()->cart->set_quantity( $cart_item_key, $quantity ) ) {

				$new_data = WC()->cart->get_cart_item( $cart_item_key );

				$product_id   = ! isset( $new_data['product_id'] ) ? 0 : absint( $new_data['product_id'] );
				$variation_id = ! isset( $new_data['variation_id'] ) ? 0 : absint( $new_data['variation_id'] );

				$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

				if ( $quantity != $new_data['quantity'] ) {
					do_action( 'wc_cart_rest_item_quantity_changed', $cart_item_key, $new_data );
				}

				// Return response based on product quantity increment.
				if ( $quantity > $current_data['quantity'] ) {
					return new WP_REST_Response( sprintf( __( 'The quantity for "%1$s" has increased to "%2$s".', 'cart-rest-api-for-woocommerce' ), $product_data->get_name(), $new_data['quantity'] ), 200 );
				} else if ( $quantity < $current_data['quantity'] ) {
					return new WP_REST_Response( sprintf( __( 'The quantity for "%1$s" has decreased to "%2$s".', 'cart-rest-api-for-woocommerce' ), $product_data->get_name(), $new_data['quantity'] ), 200 );
				} else {
					return new WP_REST_Response( sprintf( __( 'The quantity for "%s" has not changed.', 'cart-rest-api-for-woocommerce' ), $product_data->get_name() ), 200 );
				}
			} else {
				return new WP_ERROR( 'wc_cart_rest_can_not_update_item', __( 'Unable to update item quantity in cart.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
			}
		} else {
			return new WP_ERROR( 'wc_cart_rest_cart_item_key_required', __( 'Cart item key is required!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END update_item()

	public function calculate_totals() {
		if ( $this->get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
			return new WP_REST_Response( __( 'No items in cart to calculate totals.', 'cart-rest-api-for-woocommerce' ), 200 );
		}

		WC()->cart->calculate_totals();

		return new WP_REST_Response( __( 'Cart totals have been calculated.', 'cart-rest-api-for-woocommerce' ), 200 );
	} // END calculate_totals()

	public function get_totals() {
		$totals = WC()->cart->get_totals();

		return $totals;
	} // END get_totals()
	
	public function get_complete_cart_details() {
		// Get cart items
		$cart_items = WC()->cart->get_cart();

		// Get cart totals
		$cart_totals = WC()->cart->get_totals();

		// Get applied coupons
		$applied_coupons = WC()->cart->get_applied_coupons();

		// Get shipping details
		$shipping_packages = WC()->cart->get_shipping_packages();
		$shipping_total = WC()->cart->get_shipping_total();

		// Assemble the complete cart details
		$complete_cart_details = array(
			'items' => array(),
			'totals' => $cart_totals,
			'coupons' => $applied_coupons,
			'shipping' => array(
				'packages' => $shipping_packages,
				'total' => $shipping_total,
			),
		);

		// Process each cart item
		foreach ($cart_items as $cart_item_key => $cart_item) {
			$product = $cart_item['data'];
			$complete_cart_details['items'][] = array(
				'product_id' => $cart_item['product_id'],
				'variation_id' => $cart_item['variation_id'],
				'quantity' => $cart_item['quantity'],
				'line_subtotal' => $cart_item['line_subtotal'],
				'line_subtotal_tax' => $cart_item['line_subtotal_tax'],
				'line_total' => $cart_item['line_total'],
				'line_tax' => $cart_item['line_tax'],
				'product_name' => $product->get_name(),
				'product_price' => $product->get_price(),
			);
		}

		return $complete_cart_details;
	}

} // END class
