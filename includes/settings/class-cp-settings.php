<?php
/**
 * QBO Product Settings
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CP_Settings' ) ) :

/**
 * WC_Settings_Products
 */
class CP_Settings extends CPD_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    			= 'settings';
		$this->label 			= __( 'Cartpipe Settings', 'cartpipe' );
		add_filter( 'cpd_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'cpd_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'cpd_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		CPD_Admin_Settings::output_fields( $settings );
		if(isset($this->usage)){
			echo $this->usage;
		}
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		CPD_Admin_Settings::save_fields( $settings );
	    CPD()->init();
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		//if ( $current_section == 'inventory' ) {
			
			
			
			 $settings = apply_filters( 'cpd_sales_settings', array(
// 
				array( 'title' => __( 'Cartpipe Settings', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'cartpipe_settings' ),
				
				array(
					'title'             => __( 'Order Transfer Status','cartpipe' ),
					'desc'              => __( 'Please select the order status when orders are ready to be sent to QuickBooks.','cartpipe' ),
					'id'                => 'cpd[transfer_status]',
					'type'              => 'select',
					'options'			=> wc_get_order_statuses(),//array('sales-receipt'	=>	'Sales Receipt','invoice'		=>	'Invoice'),
					'css'               => '',
					'default'           => '',
					'autoload'          => false
				),
				array(
                    'title'             => __( 'Product Import Status','cartpipe' ),
                    'desc'              => __( 'Please select the product status for imported items from QuickBooks.','cartpipe' ),
                    'id'                => 'cpd[import_status]',
                    'type'              => 'select',
                    'options'           => array('draft'=>'Draft', 'publish'=>'Publish'),//array('sales-receipt'  =>  'Sales Receipt','invoice'       =>  'Invoice'),
                    'css'               => '',
                    'default'           => 'draft',
                    'autoload'          => false
                ),
				array(
					'title'             => __( 'Default Product Sync Status','cartpipe' ),
					'desc'              => __( 'Please check the box to set the default sync status for products to "synced". Leave the box unchecked to set the default sync status to un-synced. You can override this value on each product.','cartpipe' ),
					'id'                => 'cpd[default_sync]',
					'type'              => 'checkbox',
					'dependency' 		=> '',
					'css'               => '',
					'default'           => 'yes',
					//'autoload'          => false
				),
				array(
                    'title'             => __( 'Number of Products in API request','cartpipe' ),
                    'desc'              => __( 'To speed up the Cartpipe App\'s product syncing, you can adjust the number of products included in each API request. By default, Cartpipe uses your "posts per page" setting. If you have a lot of products, the larger this number is, the more webserver resources will be consumed, so if you see a negative impact on server performance, please decrease this number.','cartpipe' ),
                    'id'                => 'cpd[posts_per_page]',
                    'type'              => 'text',
                    'dependency'        => '',
                    'css'               => '',
                    'default'           => get_option('posts_per_page'),
                    //'autoload'          => false
                ),
				array(
					'title'             => __( 'Enable Debugger','cartpipe' ),
					'desc'              => __( 'Check this box to enable the Cartpipe API Debugger on your website.','cartpipe' ),
					'id'                => 'cpd[enable_debugger]',
					'type'              => 'checkbox',
					'css'               => '',
					'default'           => 'no',
					'autoload'          => false
				),
				array( 'type' => 'sectionend', 'id' => 'cartpipe_settings'),

			));
		//}

		return apply_filters( 'cpd_get_settings_' . $this->id, $settings, $current_section );
	}
	
}

endif;

return new CP_Settings();
