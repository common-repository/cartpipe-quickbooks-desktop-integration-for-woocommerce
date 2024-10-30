<?php
/**
 * CP QBO Order Data
 *
 * Functions for displaying the qbo order data meta box.
 *
 * @author 		CartPipe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Data Class
 */
class CP_QB_Order_Meta_Box {

		

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $post;
    ?>
	   <p id="quickbooks_order_data" class="form-field form-field-wide wc-quickbooks-data">
		<?php 
		      $data = maybe_unserialize( get_post_meta( $post->ID, '_quickbooks_data', true ) ); ?>
    		  <h4>
    		      <?php echo sprintf('%s', __('QuickBooks Details', 'cartpipe') );?>
    		  </h4>
    		  <?php if( !$data ){?>
    		      <label>
    		          <?php echo sprintf('%s', __('This order hasn\'t transferred to QuickBooks yet.', 'cartpipe') );?>
    		       </label>   
    		  <?php }else{ ?>
    		      <dl>
    		      <?php
    		          foreach( $data as $key => $value ){
    		              switch ($key) {
							  case 'customer':?>
								    <dt class="quickbooks"><?php _e('Customer Name');?></dt>
                                    <dd class="quickbooks"><?php echo $value;?></dd>
								  <?php break;
							  
                              case 'ref_num':?>
								    <dt class="quickbooks"><?php _e('Reference Number');?></dt>
                                    <dd class="quickbooks"><?php echo $value;?></dd>
								  <?php break;
                              case 'type':?>
                                    <dt class="quickbooks"><?php _e('Type');?></dt>
                                    <dd class="quickbooks"><?php echo $value;?></dd>
                                  <?php break;
						  }
                      }
    		      ?>
    		      </dl>
    		  <?php }?>
	   </p>
		<?php
	}
	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		
	}
}
