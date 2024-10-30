<?php
/**
 * WooCommerce Admin.
 *
 * @class 		WC_Admin
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin class.
 */
class CPD_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
	   add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit' ), 10, 2 );
       add_action( 'quick_edit_custom_box',  array( $this, 'quick_edit' ), 10, 2 );
       add_action( 'save_post', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );
       add_action( 'woocommerce_admin_order_actions_end', array($this, 'add_transfer_status'),10,2);
        
        
    }
    function add_transfer_status( $the_order ){
        $data = get_post_meta( $the_order->id, '_quickbooks_data', true);
        if( $data ){
            if( isset( $data['status'] ) ){
                if($data['status'] == 'posted'){
                    printf( '<a class="button tips quickbooks" data-tip="%s">%s</a>', esc_attr( sprintf( '%s #%s', isset( $data['type'] ) ? $data['type'] : 'Reference Number', $data['ref_num']) ), esc_attr( $data['status']) );
                }
            }
        }
    }
    /**
     * Custom quick edit - form.
     *
     * @param mixed $column_name
     * @param mixed $post_type
     */
    public function quick_edit( $column_name, $post_type ) {
        global $post;
        if ( 'price' != $column_name || 'product' != $post_type ) {
            return;
        }

        include( CPD()->plugin_path() . '/includes/views/cp-admin-quick-edit-html.php' );
    }
    public function bulk_edit( $column_name, $post_type ) {

        if ( 'price' != $column_name || 'product' != $post_type ) {
            return;
        }
        include( CPD()->plugin_path() . '/includes/views/cp-admin-bulk-edit-html.php' );
    }
    /**
     * Quick and bulk edit saving.
     *
     * @param int $post_id
     * @param WP_Post $post
     * @return int
     */
    public function bulk_and_quick_edit_save_post( $post_id, $post ) {

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        
        // Don't save revisions and autosaves
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return $post_id;
        }

        // Check post type is product
        if ( 'product' != $post->post_type ) {
            return $post_id;
        }
        
        // Check user permission
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        // Check nonces
        if ( ! isset( $_REQUEST['cartpipe_quick_edit_nonce'] ) && ! isset( $_REQUEST['cartpipe_bulk_edit_nonce'] ) ) {
             return $post_id;
        }
        if ( isset( $_REQUEST['cartpipe_quick_edit_nonce'] ) && ! wp_verify_nonce( $_REQUEST['cartpipe_quick_edit_nonce'], 'cartpipe_quick_edit_nonce' ) ) {
           
            return $post_id;
        }
        if ( isset( $_REQUEST['cartpipe_bulk_edit_nonce'] ) && ! wp_verify_nonce( $_REQUEST['cartpipe_bulk_edit_nonce'], 'cartpipe_bulk_edit_nonce' ) ) {
            
            return $post_id;
        }
         if ( ! empty( $_REQUEST['cartpipe_quick_edit'] ) ) {
            $this->quick_edit_save( $post_id, $post );
        } else {
            $this->bulk_edit_save( $post_id, $post );
        }
       
        return $post_id;
    }

    /**
     * Quick edit.
     *
     * @param integer $post_id
     * @param WC_Product $product
     */
    private function quick_edit_save( $post_id, $post ) {
        global $wpdb;
       //error_log(print_r($post_id, true), 3, plugin_dir_path(__FILE__) . "/log.log");
        if ( isset( $_REQUEST['_quickbooks_sync'] ) ) {
           update_post_meta( $post_id, '_quickbooks_sync', 'yes' );
        } else {
           update_post_meta( $post_id, '_quickbooks_sync', 'no' );
        }
        do_action( 'cartpipe_product_quick_edit_save', $post );
    }

    /**
     * Bulk edit.
     * @param integer $post_id
     * @param WC_Product $product
     */
    public function bulk_edit_save( $post_id, $post ) {

        if ( isset( $_REQUEST['_quickbooks_sync'] ) ) {
           update_post_meta( $post_id, '_quickbooks_sync', 'yes' );
        } else {
           update_post_meta( $post_id, '_quickbooks_sync', 'no' );
        }
        do_action( 'cartpipe_product_bulk_edit_save', $post );
    }

}

return new CPD_Admin();
