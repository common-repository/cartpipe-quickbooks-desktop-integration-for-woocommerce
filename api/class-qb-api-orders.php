<?php
/**
 * Cartpipe API QuickBooks Orders Class
 *
 * Handles requests to the /qb/orders endpoint
 *
 * @author      Cartpipe
 * @category    API
 * @package     Cartpipe-QuickBooks-Api/API
 * @since       1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class QB_API_Orders extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/qb';

	/**
	 * Register the routes for this class
	 *
	 * GET /orders/unposted
	 * GET /orders/posted
     * GET /orders/failed
	 *      * GET|PUT /orders/status/set
	 * 
	 *
	 * @since 1.0
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET /orders/count
		$routes[ $this->base . '/orders/unposted'] = array(
			array( array( $this, 'get_unposted_orders' ), WC_API_Server::READABLE ),
		);
		$routes[ $this->base . '/orders/posted'] = array(
			array( array( $this, 'get_posted_orders' ), WC_API_Server::READABLE ),
		);
		$routes[ $this->base . '/orders/failed'] = array(
			array( array( $this, 'get_failed_orders' ), WC_API_Server::READABLE ),
		);
		
		# GET|PUT /orders/<id>
		$routes[ $this->base . '/orders/status/set' ] = array(
			array( array( $this, 'update_orders_with_qb_data' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
		);
		return $routes;
	}

	/**
	 * Get unposted orders
	 *
	 * @since 1.0
	 * @param string $fields
	 * @param array $filter
	 * @param string $status
	 * @param int $page
	 * @return array
	 */
	public function get_unposted_orders( $fields = null, $filter = array(), $status = null, $page = 1 ) {
		 if ( ! empty( $status ) ){
			 $filter['status'] = $status;
		 }
		
		$filter['page'] = $page;
		$query = $this->query_unsynced_orders( $page );
       $orders = array();
		$orders_per_page 	= get_option('posts_per_page');
		$number_of_pages 	= $query->found_posts / $orders_per_page; 
		foreach( $query->posts as $order_id ) {
			if ( ! $this->is_readable( $order_id ) )
				continue;
			$orders[] = current( $this->get_order( $order_id, $fields ) );
		}
       
		$this->server->add_pagination_headers( $query );
		return array('orders' => $orders, 'total_orders' => (int) $query->found_posts , 'num_pages' => ceil( $number_of_pages ) ) ;
	}
	public function get_posted_orders( $fields = null, $filter = array(), $status = null, $page = 1 ) {

		if ( ! empty( $status ) ){
			$filter['status'] = $status;
		}
		$filter['page'] = $page;
		$query = $this->query_synced_orders( $filter );
		$orders = array();
		$orders_per_page 	= get_option('posts_per_page');
		$number_of_pages 	= $query->found_posts / $orders_per_page; 
		foreach( $query->posts as $order_id ) {
			if ( ! $this->is_readable( $order_id ) )
				continue;
			$orders[] = current( $this->get_order( $order_id, $fields ) );
		}
		$this->server->add_pagination_headers( $query );
		return  array('orders' => $query, 'total_orders' => (int) $query->found_posts , 'num_pages' => ceil( $number_of_pages ) ) ;
	}
	public function get_failed_orders( $fields = null, $filter = array(), $status = null, $page = 1 ) {
		if ( ! empty( $status ) ){
			$filter['status'] = $status;
		}
		$filter['page'] = $page;
		$query = $this->query_failed_orders( $filter );
		
		$orders = array();
		$orders_per_page 	= get_option('posts_per_page');
		$number_of_pages 	= $query->found_posts / $orders_per_page; 
		foreach( $query->posts as $order_id ) {
			if ( ! $this->is_readable( $order_id ) )
				continue;
			$orders[] = current( $this->get_order( $order_id, $fields ) );
		}
		$this->server->add_pagination_headers( $query );
		return  array('orders' => $orders, 'total_orders' => (int) $query->found_posts , 'num_pages' => ceil( $number_of_pages ) ) ;
	}
	private function query_unsynced_orders( $page ) {
		
		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => array( CPD()->settings->transfer_status ),
			'meta_query' => array(
				array(
					'key'		=>'_quickbooks_data',
					'value'		=> '',
					'compare' 	=> 'NOT EXISTS',
				),
			),
			'paged'=>$page
		);
		return new WP_Query( $query_args );
	}
	private function query_synced_orders( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => array( CPD()->settings->transfer_status ),
			'meta_query' => array(
				array(
					'key'		=>'_quickbooks_data',
					'compare' 	=> 'EXISTS',
				),
			)
		);
		return new WP_Query( $query_args );
	}
	private function query_failed_orders( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => array_keys( wc_get_order_statuses() ),
			'post_parent' => 0,
		);
	
		$query_args['tax_query'] = array(
			 	 array(
					'taxonomy' 	=> 'qb_order_status',
					'field'    	=> 'slug',
					'terms'    	=> 'failed',
				),
				
			);
	
		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}
	/**
	 * Get the order for the given ID
	 *
	 * @since 1.0
	 * @param int $id the order ID
	 * @param array $fields
	 * @return array
	 */
	public function get_order( $id, $fields = null ) {
		
		// ensure order ID is valid & user has permission to read
		$id = $this->validate_request( $id, 'shop_order', 'read' );
		
		if ( is_wp_error( $id ) )
			return $id;

		$order = new WC_Order( $id );
		$taxes              = array();
		
		$order_post = get_post( $id );
		$order_taxes 	= $order->get_taxes();
		$order_data = array(
			'id'                        => $order->id,
			'order_number'              => $order->get_order_number(),
			'created_at'                => $this->server->format_datetime( $order_post->post_date_gmt ),
			'updated_at'                => $this->server->format_datetime( $order_post->post_modified_gmt ),
			'completed_at'              => $this->server->format_datetime( $order->completed_date, true ),
			'status'                    => $order->status,
			'currency'                  => $order->order_currency,
			'total'                     => wc_format_decimal( $order->get_total(), 2 ),
			//'subtotal'                  => wc_format_decimal( $this->get_order_subtotal( $order ), 2 ),
			'total_line_items_quantity' => $order->get_item_count(),
			'total_tax'                 => wc_format_decimal( $order->get_total_tax(), 2 ),
			'total_shipping'            => wc_format_decimal( $order->get_total_shipping(), 2 ),
			'cart_tax'                  => wc_format_decimal( $order->get_cart_tax(), 2 ),
			'shipping_tax'              => wc_format_decimal( $order->get_shipping_tax(), 2 ),
			'total_discount'            => wc_format_decimal( $order->get_total_discount(), 2 ),
			'cart_discount'             => wc_format_decimal( $order->get_cart_discount(), 2 ),
			'order_discount'            => wc_format_decimal( $order->get_order_discount(), 2 ),
			'shipping_methods'          => $order->get_shipping_method(),
			'payment_details' => array(
				'method_id'    => $order->payment_method,
				'paid'         => isset( $order->paid_date ),
			),
			'billing_address' => array(
				'first_name' => $order->billing_first_name,
				'last_name'  => $order->billing_last_name,
				'company'    => $order->billing_company,
				'address_1'  => $order->billing_address_1,
				'address_2'  => $order->billing_address_2,
				'city'       => $order->billing_city,
				'state'      => $order->billing_state,
				'postcode'   => $order->billing_postcode,
				'country'    => $order->billing_country,
				'email'      => $order->billing_email,
				'phone'      => $order->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $order->shipping_first_name,
				'last_name'  => $order->shipping_last_name,
				'company'    => $order->shipping_company,
				'address_1'  => $order->shipping_address_1,
				'address_2'  => $order->shipping_address_2,
				'city'       => $order->shipping_city,
				'state'      => $order->shipping_state,
				'postcode'   => $order->shipping_postcode,
				'country'    => $order->shipping_country,
			),
			'note'                      => $order->customer_note,
			'customer_id'               => $order->customer_user,
			'line_items'                => array(),
			'shipping_lines'            => array(),
			'tax_lines'                 => array(),
			'fee_lines'                 => array(),
			'coupon_lines'              => array(),
		);

		// add line items
		foreach( $order->get_items() as $item_id => $item ) {

			$product 		= $order->get_product_from_item( $item );
			$rate_data 		= array(); 
			$line_tax_data 	= isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '';
			$tax_data      	= maybe_unserialize( $line_tax_data );
			$taxes_array 	= array();
			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
				$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
				if ( '' != $tax_item_total ) {
					$taxes_array[] = array(
										'code'	=> $tax_item['name'], 
										'label'	=> $tax_item['label'],
										'rate'	=>  $tax_item_subtotal /  $order->get_line_subtotal( $item )  * 100 . '%'
										);
				}
			endforeach;
			
			$order_data['line_items'][] = array(
				'id'         => $item_id,
				'subtotal'   => wc_format_decimal( $order->get_line_subtotal( $item ), 2 ),
				'total'      => wc_format_decimal( $order->get_line_total( $item ), 2 ),
				'total_tax'  => wc_format_decimal( $order->get_line_tax( $item ), 2 ),
				'price'      => wc_format_decimal( $order->get_item_total( $item ), 2 ),
				'quantity'   => (int) $item['qty'],
				'tax_class'  => ( ! empty( $item['tax_class'] ) ) ? $item['tax_class'] : null,
				'name'       => $item['name'],
				'product_id' => ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id,
				'sku'        => is_object( $product ) ? $product->get_sku() : null,
				'tax_rates'	=> $taxes_array
			);
		}

		// add shipping
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
			$shipping_line_tax_data 	= isset( $shipping_item['taxes'] ) ? $shipping_item['taxes'] : '';
			$shipping_tax_data      	= maybe_unserialize( $shipping_line_tax_data );
			$taxes_array 	= array();
			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $shipping_tax_data[$tax_item_id] ) ? $shipping_tax_data[ $tax_item_id ] : '';
				//$tax_item_subtotal = isset( $shipping_tax_data['subtotal'][ $tax_item_id ] ) ? $shipping_tax_data['subtotal'][ $tax_item_id ] : '';
				if ( '' != $tax_item_total ) {
					$taxes_array[] = array(
										'code'	=> $tax_item['name'], 
										'label'	=> $tax_item['label'],
										'rate'	=>  wc_format_decimal($tax_item_total / $shipping_item['cost']  * 100, 2) . '%'
										);
				}
			endforeach;
			$order_data['shipping_lines'][] = array(
				'method_id'    	=> $shipping_item['method_id'],
				'name'			=> $shipping_item['name'],
				'total'        	=> wc_format_decimal( $shipping_item['cost'], 2 ),
				'tax_rates'		=> $taxes_array
			);
		}

		// add taxes
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {

			$order_data['tax_lines'][] = array(
				'code'     => $tax_code,
				'title'    => $tax->label,
				'total'    => wc_format_decimal( $tax->amount, 2 ),
				'compound' => (bool) $tax->is_compound,
			);
		}

		// add fees
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
			$fee_line_tax_data 	= isset( $fee_item['line_tax_data'] ) ? $fee_item['line_tax_data'] : '';
			$fee_tax_data      	= maybe_unserialize( $fee_line_tax_data );
			$taxes_array 	= array();
			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $fee_tax_data['total'][ $tax_item_id ] ) ? $fee_tax_data['total'][ $tax_item_id ] : '';
				$tax_item_subtotal = isset( $fee_tax_data['subtotal'][ $tax_item_id ] ) ? $fee_tax_data['subtotal'][ $tax_item_id ] : '';
				if ( '' != $tax_item_total ) {
					$taxes_array[] = array(
										'code'	=> $tax_item['name'], 
										'label'	=> $tax_item['label'],
										'rate'	=>  $tax_item_subtotal /  $order->get_line_subtotal( $fee_item )  * 100 . '%'
										);
				}
			endforeach;
			$order_data['fee_lines'][] = array(
				'id'        => $fee_item_id,
				'title'     => $fee_item['name'],
				'tax_class' => ( ! empty( $fee_item['tax_class'] ) ) ? $fee_item['tax_class'] : null,
				'total'     => wc_format_decimal( $order->get_line_total( $fee_item ), 2 ),
				'total_tax' => wc_format_decimal( $order->get_line_tax( $fee_item ), 2 ),
				'tax_rates'	=> $taxes_array
			);
		}

		// add coupons
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {

			$order_data['coupon_lines'][] = array(
				'id'     => $coupon_item_id,
				'code'   => $coupon_item['name'],
				'amount' => wc_format_decimal( $coupon_item['discount_amount'], 2 ),
			);
		}

		return array( 'order' => apply_filters( 'qb_api_order_response', $order_data, $order, $fields, $this->server ) );
	}

	

	/**
	 * Update an order
	 *
	 *
	 * @since 1.0
	 * @param int $id the order ID
	 * @param array $data
	 * @return array
	 */
	public function update_order_with_qb_data( $id, $data ) {
		$fallout = array();
		$order = new WC_Order( $id );
		$id = $this->validate_request( $id, 'shop_order', 'edit' );
		if($data){
			if ( is_wp_error( $id ) )
					$fallout[] = $id;
			if ( ! empty( $data['status'] ) ) {
				$response = $this->update_order($id, $data['status']);
				
				if(!$response){
					$fallout[] = $id;
				}
			}
		}
		return $response;
	}
	public function update_orders_with_qb_data( $data ) {
		$data = maybe_unserialize( $data );
		$fallout = array();
		if(isset($data['order_id'])){
			$id = $this->validate_request( $data['order_id'], 'shop_order', 'edit' );
			if ( ! empty( $data['status'] ) ) {
				$response = $this->update_order($id, $data);
				if(!$response){
					$fallout[] = $id;
				}
			}
		}else{
			foreach($data as $order){	
				$id = $this->validate_request( $order['order_id'], 'shop_order', 'edit' );
				if ( ! empty( $order['status'] ) ) {
					$response = $this->update_order($id, $order);
					if(!$response){
						$fallout[] = $id;
					}
				}
			}
		}
		
	}

	

	

	/**
	 * Helper method to get order post objects
	 *
	 * @since 1.0
	 * @param array $args request arguments for filtering query
	 * @return WP_Query
	 */
	private function query_orders( $args ) {

		// set base query arguments
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => 'publish',
		);
			
		$query_args = $this->merge_query_args( $query_args, $args );
		
		return new WP_Query( $query_args );
	}

	public function update_order( $id, $data) {
		$WC_Order = new WC_Order($id);
		if(isset($data['status'])){
			$new_status = get_term_by( 'slug', sanitize_title( $data['status'] ), 'qb_order_status' );
			if ( $new_status ) {
	
				wp_set_object_terms( $id, array( $new_status->slug ), 'qb_order_status', false );
	
				if ( $id && $new_status->slug ) {
	
					// Status was changed
					
					$WC_Order->add_order_note( $note . sprintf( __( 'QuickBooks status changed to %s. QuickBooks Reference # is %s, for customer %s', 'woocommerce' ), __( $new_status->name, 'qb-api' ),  $data['ref_num'], $data['customer'] ) );
					
					// Record the completed date of the order
					if ( 'posted' == $new_status->slug ) {
						update_post_meta( $id, 'posted_date', current_time('mysql') );
					}
	
					
					// Update last modified
					wp_update_post( array( 'ID' => $id ) );
				}
				$return = true;
			}else{
				$return = false;
			}
		}
		update_post_meta( $id, '_quickbooks_data', $data );
		wc_delete_shop_order_transients( $id );
		return $return;
		
	}

}
