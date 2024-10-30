<?php
/**
 * Cartpipe API Index Class
 *
 * Handles requests to the /qb endpoint
 *
 * @author      Cartpipe
 * @category    API
 * @package     Cartpipe-QuickBooks-Api/API
 * @since       1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class QB_API_Index extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/qb';
	
	/**
	 * Register the routes for this class
	 *
	 *
	 * @since 1.0
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {
		
		# GET /orders/count
		$routes[ $this->base . '/payment_methods'] = array(
			array( array( $this, 'get_payment_methods' ), WC_API_Server::READABLE ),
		);
        $routes[ $this->base . '/product_categories'] = array(
            array( array( $this, 'get_product_categories' ), WC_API_Server::READABLE ),
        );
		$routes[ $this->base . '/sales_tax_classes'] = array(
			array( array( $this, 'get_sales_tax_classes' ), WC_API_Server::READABLE ),
		);
		$routes[ $this->base . '/sales_tax_rates'] = array(
			array( array( $this, 'get_sales_tax_rates' ), WC_API_Server::READABLE ),
		);
		$routes[ $this->base . '/license/(?P<id>\w+)/get'] = array(
			array( array( $this, 'get_license_info' ), WC_API_Server::READABLE ),
		);
		return $routes;
	}

	
	public function get_payment_methods() {
		global $wpdb, $woocommerce;
		$data = $woocommerce->payment_gateways->get_available_payment_gateways();
		
		$return = array();
		foreach($data as $value){
			$return[] = (array) array(
				'id' 	=> $value->id,
				'title'	=> $value->title
			);
		};
		
		return  $return;
	}
	public function get_license_info( $id ){
		$api_params = array( 
			'edd_action' 	=> 'check_license', 
			'license' 		=> CPD()->settings->license, 
			'item_name' 	=> urlencode( 'CartPipe Online' ),
			'url'       	=> home_url()
		);
		
		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, 'http://cartpipe.com' ), array( 'timeout' => 15, 'sslverify' => false ) );
	
	
		if ( is_wp_error( $response ) )
			return false;
	
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		return $license_data;
	}
    public function get_product_categories(){
        $w_categories   = get_terms('product_cat', array( 'hide_empty' => 0 ));
        $cats           = array();
        foreach ($w_categories  as $key => $value) {
            $cats[] = array(
                "id" => $value->term_id,
                "title"=> $value->name
             );
        }
        return $cats;
    }
	public function get_sales_tax_classes() {
		$tax_classes			= array_filter(array_map('trim', explode("\n", get_option('woocommerce_tax_classes'))));
		$return               = array(); 
        $return[]=array(
		      "id"=>"standard",
		      "title"=>"Standard"
          );
		foreach($tax_classes as $item){
			$return[]=array(
			     "id"    =>  str_replace(" ","_",strtolower($item)),
			     "title"    =>  $item,
          );       
		}
		
		return  $return ;
	}
	public function get_sales_tax_rates() {
		global $wpdb, $woocommerce;
		$tax_rates 				= array();
		$return 				= array();
		$tax_rates = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
			ORDER BY tax_rate_order
			" , $current_class=array() ) );
		foreach($tax_rates as $rate){
			
			$return[] = array(
				'code'		=> strtoupper($rate->tax_rate_country . '-' .$rate->tax_rate_state . '-' .$rate->tax_rate_name . '-' .$rate->tax_rate_id),   
				'id' 		=> $rate->tax_rate_id,
				'title' 	=> $rate->tax_rate_name,
				'tax_rate'	=> $rate->tax_rate,
				);
		}
		return  $return ;
	}
}
