<?php
/**
 * QBO Product Settings
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CPD_Settings_Credentials' ) ) :

/**
 * CPD_Settings_Credentials
 */
class CPD_Settings_Credentials extends CPD_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
	    
        if(CPD()->settings->enable_debugger == 'yes'){
    		$this->id    = 'credentials';
    		$this->label = __( 'Cartpipe Debugger', 'cartpipe' );
    
    		add_filter( 'cpd_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
    		add_action( 'cpd_settings_' . $this->id, array( $this, 'output' ) );
    		add_action( 'cpd_settings_save_' . $this->id, array( $this, 'save' ) );
    		add_action( 'cpd_sections_' . $this->id, array( $this, 'output_sections' ) );
        }
	}

	public function get_sections() {

		$sections = array(
			'creds'   			=> __( 'Credentials', 'cartpipe' ),
			'payment_methods'   => __( 'Payment Methods', 'cartpipe' ),
			'tax_classes'		=> __( 'Tax Classes', 'cartpipe' ),
			'tax_rates' 		=> __( 'Tax Rates', 'cartpipe' ),
			//'cartpipe_info'     => __( 'Cartpipe License Info', 'cartpipe' ),
			'unposted_orders'	=> __( 'Unposted Orders', 'cartpipe' ),
			'posted_orders' 	=> __( 'Posted Orders', 'cartpipe' ),
			//'failed_orders'		=> __( 'Failed Orders', 'cartpipe' ),
			'all_products'		=> __( 'All Products', 'cartpipe' ),
			//'sync_products'		=> __( 'Synced Products', 'cartpipe' ),
			//'unsynced_products'	=> __( 'Unsynced Products', 'cartpipe' ),
		);
		
		return apply_filters( 'cpd_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;
			
		$settings = $this->get_settings( $current_section );

 		CPD_Admin_Settings::output_fields( $settings );
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
	    if(CPD()->settings->enable_debugger == 'yes' ){
		switch ($current_section) {
			case 'payment_methods':
				$data = CPD()->client->get_payment_methods();
                
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'Payment Methods', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'payment_methods' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are the payment methods being returned to Cartpipe for Desktop via your website API', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'payment_methods',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'payment_methods'),
				));
				break;
			case 'tax_classes':
				$data = CPD()->client->get_sales_tax_classes();
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'Tax Classes', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'tax_classes' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are the sales tax classes being returned to Cartpipe for Desktop via your website API', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'tax_classes',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'tax_classes'),
				));
				break;
			case 'tax_rates':
				$data = CPD()->client->get_sales_tax_rates();
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'Tax Rates', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'tax_rates' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are the sales tax rates being returned to Cartpipe for Desktop via your website API.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'tax_rates',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'tax_rates'),
				));
				break;
			case 'cartpipe_info':
				$data = CPD()->client->get_license_info(CPD()->settings->license);
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'CartPipe License Info', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'license' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'This is your current Cartpipe.com license and subscription infor for the license key you entered.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'license',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'license'),
				));
				break;
			case 'unposted_orders':
				$data = CPD()->client->get_unposted_orders();
				
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'Unposted Orders', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'unposted_orders' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are the orders that have not transferred to QuickBooks.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'unposted',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'unposted_orders'),
				));
				break;
			case 'posted_orders':
				$data = CPD()->client->get_posted_orders();
				
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'Posted Orders', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'posted_orders' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are the orders that have transferred to QuickBooks.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'posted',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'posted_orders'),
				));
				break;
			case 'failed_orders':
				$data = CPD()->client->get_failed_orders();
				$settings = apply_filters( 'Failed Orders', array(
					array( 'title' => __( 'CartPipe Credentials', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'failed_orders' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are the orders that failed to transfer to QuickBooks.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'failed',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'failed_orders'),
				));
				break;
			case 'sync_products':
				$data = CPD()->client->get_synced_products();
				
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'Synced Products', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'synced_products' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These products have synced with QuickBooks.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'synced',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'synced_products'),
				));
				break;
			case 'all_products':
				$data = CPD()->client->get_all_products();
				
				$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'All Products', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'all_products' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These are all the products.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'synced',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'all_products'),
				));
				break;
			case 'unsynced_products':
				$data = CPD()->client->get_unsynced_products();
				$settings = apply_filters( 'Unsynced Products', array(
					array( 'title' => __( 'Unsynced Products', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'unsynced_products' ),
					array(
						'title'             => __( '', 'cartpipe' ),
						'desc'              => __( 'These products have not synced with QuickBooks.', 'cartpipe' ),
						'id'                => '',
						'type'              => 'data',
						'sub-type'          => 'unsynced',
						'css'               => '',
						'default'           => '',
						'autoload'          => false,
						'data'				=> $data
					),
					array( 'type' => 'sectionend', 'id' => 'unsynced_products'),
				));
				break;
			case 'creds':
			default:
					$settings = apply_filters( 'cpd_inventory_settings', array(
					array( 'title' => __( 'CartPipe Credentials', 'cartpipe' ), 'type' => 'title', 'desc' => '', 'id' => 'credentials_options' ),
					array(
						'title'             => __( 'Consumer Key', 'cartpipe' ),
						'desc'              => __( 'Enter the consumer key you used in the Cartpipe for Desktop app. Make sure the Key has Read/Write Permissions', 'cartpipe' ),
						'id'                => 'cpd[consumer_key]',
						'type'              => 'text',
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'             => __( 'Consumer Secret', 'cartpipe' ),
						'desc'              => __( 'Enter the consumer secret you used in the Cartpipe for Desktop app. Make sure the Secret has Read/Write Permissions', 'cartpipe' ),
						'id'                => 'cpd[consumer_secret]',
						'type'              => 'text',
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array(
						'title'             => __( 'CartPipe License Key', 'cartpipe' ),
						'desc'              => __( 'Enter your license key for the CartPipe / QuickBooks Online Integration.', 'cartpipe' ),
						'id'                => 'cpd[license]',
						'type'              => 'text',
						'css'               => '',
						'default'           => '',
						'autoload'          => false
					),
					array( 'type' => 'sectionend', 'id' => 'credentials_options'),
				));
				
				break;
			}
		}
		

		return apply_filters( 'cpd_get_settings_' . $this->id, $settings, $current_section );
	}
}

endif;

return new CPD_Settings_Credentials();
