<?php
/**
 * Plugin Name: Cartpipe QuickBooks Desktop API for WooCommerce
 * Plugin URI: https://www.cartpipe.com/services/cartpipe-for-quickbooks-desktop/
 * Description: An API for connecting QuickBooks Desktop and WooCommerce using Cartpipe
 * Version: 1.1.6
 * Author: Cartpipe.com
 * Author URI: http://cartpipe.com
 * Requires at least: 3.8
 * Tested up to: 3.8
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define("CPD_VERSION", '1.1.6');
class CPD_Client {
	/*
	 * Instance
	 */
	protected static $_instance 	= null;
	protected $cp_consumer_key 		= null;
	protected $api_url 				= null;	
	/*
	 * CartPipe Consumber Secret
	 */
	protected $cp_consumer_secret 	= null;
		
	/*
	 * CartPipe License
	 */
	protected $cp_license 			= null;
	/*
	 * API Client
	 */
	public $client 					= null;
    /**
     * Cloning is forbidden.
     *
     * @since 1.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'cartpipe' ), '1.0.1' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'cartpipe' ), '1.0.1' );
    }
	/**
	 * Setup class
	 *
	 * @access public
	 * @since 2.0
	 * @return WC_API
	 */
	public function __construct() {
		$this->includes();
		$this->init();
		add_action( 'init', array( $this, 'register_qb_post_status' ), 0);
		add_filter( 'woocommerce_api_classes', array( $this, 'register_resources' ), 99 );
		add_action( 'admin_notices', array($this, 'cp_admin_notices' ) );
        add_action( 'admin_notices', array($this, 'cp_download_app_notice' ) );
        
		add_action( 'admin_enqueue_scripts', array( $this,'cp_enqueue' )) ;
		add_action('admin_menu', array($this,'menu_items'));
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
        add_filter( 'manage_product_posts_columns', array( $this, 'product_columns' ), 99 );
        add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_columns' ), 2 );
     }
    /**
     * Get the plugin url.
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }
	function init(){
			$options 	= maybe_unserialize( get_option('cpd', array()) );
			
			$this->settings 	= new stdClass;
			$defaults	= array(
				'consumer_key' 		=> NULL,
				'consumer_secret'	=> NULL,
				'license'			=> NULL,
				'enable_debugger'   => 'no',
			);
           
			$options = array_merge($defaults, $options);
			if(sizeof($options) > 0 ){
				foreach($options as $key=>$value){
					if(!empty($key) && !empty($value)){
						$this->settings->$key = $value;
					}
				}
			}
			if(isset($this->settings->consumer_key)){
				$this->cp_consumer_key 		= $this->settings->consumer_key;	
			}
			if(isset($this->settings->consumer_secret)){
				$this->cp_consumer_secret 	= $this->settings->consumer_secret;	
			}
			if(isset($this->settings->license)){
				$this->cp_license 	= $this->settings->license; //'ck_5b96dffbb2b8daf926ac5bc026884d00'; // Add your own Consumer Key here	
			}
			$this->api_url 					= home_url();
			if(isset($this->cp_license) && isset($this->cp_consumer_key) && isset($this->cp_consumer_secret)) {		
				$this->client 					= new CPD_API_Client( $this->cp_consumer_key, $this->cp_consumer_secret, $this->api_url );
			}
			
		}	
	function cp_admin_notices(){
			$notices = get_option( 'cp_admin_notices' );
			if($notices){
				foreach ($notices as $notice) {
			      echo "<div class='updated'><p>$notice</p></div>";
			    }
			}
			delete_option( 'cp_admin_notices' );
		}
    function cp_download_app_notice(){
        $screen = get_current_screen();
        if($screen->id == "toplevel_page_cartpipe"){
            $class = 'notice notice-warning is-dismissible';
            $message = __( 'Do you need the Cartpipe for QuickBooks Desktop app? Click <a target="_blank" href="https://www.cartpipe.com/services/cartpipe-for-quickbooks-desktop/">here</a>.', 'cartpipe' );
            
            printf( '<div class="%1$s"><div class="cartpipe-icon qb-green"></div><p>%2$s</p></div>', $class, $message ); 
        }
        
    }
	function product_columns( $existing_columns ){
	    $existing_columns['cartpipe'] = '<span><span class="cp-synced parent-tips" data-tip="' . esc_attr__( 'Connected to QuickBooks', 'woocommerce' ) . '">' . __( 'Synced', 'woocommerce' ) . '</span></span>';
	    return $existing_columns;
	}
    public function render_product_columns( $column ) {
        global $post, $the_product;

        if ( empty( $the_product ) || $the_product->id != $post->ID ) {
            $the_product = wc_get_product( $post );
        }
        
        switch ( $column ) {
            case 'cartpipe' :
                if($this->_sync_enabled( $post )){
                    if ( $this->_is_matched( $post ) ) {
                        echo '<span class="cp-quickbooks tips" data-tip="' . esc_attr($this->_quickbooks_html( $post )) . '"><i class="fa fa-check-square qb-green"></i></span>';
                    } else {
                        echo '<span class="cp-quickbooks not-synced tips" data-tip="' .esc_attr($this->_quickbooks_html( $post )) . '"><i class="fa fa-exclamation qb-yellow"></i></span>';
                    }
                }else{
                       echo '<span class="cp-quickbooks tips" data-tip="' . esc_attr($this->_quickbooks_html( $post )) . '"><i class="fa fa-ban qb-red"></i></span>';
                    
                }
                break;
            default :
                break;
        }
    }
	function menu_items(){
		 $main_page = add_menu_page( __( 'Cartpipe', 'cartpipe' ), __( 'Cartpipe', 'cartpipe' ), 'manage_woocommerce', 'cartpipe', null, null, '50' );
    }
	function settings_menu(){
		$settings_page  = add_submenu_page( 'cartpipe', __( 'Settings', 'cartpipe' ),  __( 'Settings', 'cartpipe' ) , 'manage_woocommerce', 'cartpipe', array( $this, 'settings_page' ) );
    }
	public function settings_page() {
		CPD_Admin_Settings::output();
	}
    function _is_matched( $post ){
        global $post, $the_product;
        if($the_product->is_type('variable')){
            $variations = $the_product->get_available_variations();
            $has        = false;
            if(has_term( 'synced', 'qb_product_status',  $post )){
                $has = true;
            }
            foreach($variations as $variation){
                if( has_term( 'synced', 'qb_product_status',  $variation['variation_id'] ) ){
                    $has = true;
                }
            }
            return $has;
        }else{
             if( has_term( 'synced', 'qb_product_status', $post )){
                 return true;
             }else{
                 return false;
             }
        }
    }
    function _sync_enabled( $post ){
        
        $sync_status = get_post_meta( $post->ID, '_quickbooks_sync', true );
        if( $sync_status == 'yes'){
            return true;
        }else{
            return false;
        }
    }
    function _quickbooks_html( $post ){
        global $the_product;
        if($this->_sync_enabled( $post )){
            $data = $this->_has_quickbooks_data( $post );
           
            if( $data ){
                $return = "";
                foreach($data as $sku=>$value){
                             
                    $return .= sprintf('
                            <p class="small quickbooks">%s<br/>%s<br/>%s</p>
                            ',$sku, $value['stock'], $value['price']);    
                }
                return $return;
            }else{
                return sprintf('<p class="small quickbooks">If you want this product to sync with QuickBooks, 
                                ensure that the sku matches the Item Name in QuickBooks</p>');
            }
        }else{
            return sprintf('<p class="small quickbooks">If you want this product to sync with QuickBooks, 
                                please enable the QuickBooks Sync option.</p>');
        }
    }
    function _has_quickbooks_data( $post ){
       global $the_product;
       $data = array();
       if($the_product->is_type('variable')){
            $variations = $the_product->get_available_variations();
           
            if(sizeof($variations) > 0){
                foreach($variations as $variation){
                    $sku          = get_post_meta( $variation['variation_id'], '_sku', true );
                    $stock_update = get_post_meta( $variation['variation_id'], '_quickbooks_stock', true );
                    $price_update = get_post_meta( $variation['variation_id'], '_quickbooks_pricing', true );
                   
                    if( $sku and ( $stock_update || $price_update ) ){
                        $data[ $sku ] = array('stock'=>$stock_update, 'price'=> $price_update );
                    };
                };
            } 
            
            return $data;          
       }else{
           $stock_update   = get_post_meta( $post->ID, '_quickbooks_stock', true );
           $price_update   = get_post_meta( $post->ID, '_quickbooks_pricing', true );
           $sku             = $the_product->get_sku();
           
           if( $stock_update != '' || $price_update !=''){
               if( $sku ){
                   $data [ $sku ] = array(
                                        'stock'=>$stock_update, 
                                        'price'=>$price_update 
                                    );
                                    
                    return $data;
               }    
           }else{
               return false;
           }
       }
    }
	function cp_enqueue($hook){
			
		global $post;
		wp_enqueue_script( 'jquery' );
		wp_register_style( 'cp-font', plugins_url('/assets/css/cp-font.css', __FILE__), false, CPD_VERSION );
        wp_register_style( 'cp-font-awesome', plugins_url('/assets/css/font-awesome.min.css', __FILE__), false, CPD_VERSION );
        wp_register_style( 'cp-admin-css', plugins_url('/assets/css/cp.css', __FILE__), false, CPD_VERSION );
		wp_enqueue_style( 'cp-font' );
		wp_enqueue_style( 'cp-font-awesome' );
        wp_enqueue_style( 'cp-admin-css' );
        }
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		
		return self::$_instance;
		
	}
	function includes(){
		include_once(plugin_dir_path( __FILE__ ). 'cartpipe-functions.php');
		include_once(plugin_dir_path( __FILE__ ). 'includes/class-cpd-api-client.php');
		include_once(plugin_dir_path( __FILE__ ). 'includes/cpd-post-types.php');
		include_once(plugin_dir_path( __FILE__ ). 'includes/admin-settings.php' );
		include_once(plugin_dir_path( __FILE__ ).'/includes/admin-meta-boxes.php');
        include_once(plugin_dir_path( __FILE__ ).'/includes/class-qb-admin.php');
		include_once(plugin_dir_path( __FILE__ ).'/includes/meta-boxes/class-qb-products-meta-box.php');
		include_once(plugin_dir_path( __FILE__ ).'/includes/meta-boxes/class-qb-orders-meta-box.php');
	}
	function register_qb_post_status(){
		 register_taxonomy( 
		  	'qb_order_status',
	        'shop_order',
		        array(
		            'hierarchical' 			=> false,
		            'update_count_callback' => '_update_post_term_count',
		            'show_ui' 				=> false,
		            'show_in_nav_menus' 	=> false,
		            'query_var' 			=> is_admin(),
		            'rewrite' 				=> false,
		            'public'                => false
		        )
	    	);
		if(!term_exists( 'posted', 'qb_order_status' ) ):
			wp_insert_term( 'posted', 'qb_order_status' );
		endif;
		if(!term_exists( 'failed', 'qb_order_status' ) ):
			wp_insert_term( 'failed', 'qb_order_status' );
		endif;
		//add_rewrite_rule( '^qb-api/?$', 'index.php?wc-api-version=2&wc-api-route=/', 'top' );
		//add_rewrite_rule( '^qb-api/{1}(.*)?', 'index.php?wc-api-version=2&wc-api-route=$matches[1]', 'top' );

		// WC API for payment gateway IPNs, etc
		//add_rewrite_endpoint( 'qb-api', EP_ALL );
	}
	function register_resources($resources){
		include(plugin_dir_path( __FILE__ ).'/api/class-qb-api-index.php');
		include(plugin_dir_path( __FILE__ ).'/api/class-qb-api-orders.php');
		include(plugin_dir_path( __FILE__ ).'/api/class-qb-api-products.php');
		
		$new_resources[] = 'QB_API_Index';
		$new_resources[] = 'QB_API_Orders';
		$new_resources[] = 'QB_API_Products';
		
		return $new_resources;
	}
}
function CPD() {

	return CPD_Client::instance();
}

// Global for backwards compatibility.
$GLOBALS['CPD_Client'] = CPD();