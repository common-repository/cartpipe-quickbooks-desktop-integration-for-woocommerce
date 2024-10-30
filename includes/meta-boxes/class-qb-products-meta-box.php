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
class CP_QB_Product_Meta_Box extends CP_Meta_Boxes{

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $post;
		?>
		<div class="panel-wrap">
			<div id="quickbooks_product_data" class="cp_data" style="display:block;">
				<div class="qb_content">
				    <?php 
				           wp_nonce_field( 'cp_meta_nonce', 'cp_meta_nonce' );
					       $properties = array(
    							'_quickbooks_sync'           => array(
    							 	    'can_edit'	=> true,
    									'type'		=> 'checkbox'
    							),
    							'_quickbooks_last_updated'   => array(
    									'can_edit'	=>  false,
    									'type'		=> 'label'
    								),
    							'_quickbooks_pricing'=> array(
    									'can_edit'	=> false,
    									'type'		=> 'label'
    								),
    							'_quickbooks_stock' => array( 
                                        'can_edit'  => false,
                                        'type'      => 'label'
                                    )
    							);
						  foreach($properties as $prop=>$prop_data){
							    $data = get_post_meta( $post->ID, $prop, true);
                                if($prop == '_quickbooks_sync'){
                                    if(!$data){
                                       $data = CPD()->settings->default_sync == "yes" ? "yes" :false ;
                                    }
                                    								
                                }
                                if( $data ){
								?>
							<p class="form-field <?php echo $prop;?>_field">
								<label for="qb_product_<?php echo $prop;?>"><?php printf('<strong>%s</strong>: ', ucwords(str_replace('_', ' ', $prop ) ) );?></label>
								<br />
								<?php switch ($prop_data['type']) {
									case 'input':?>
										<input type="text" 
											class="short <?php echo $prop_data['can_edit'] ? 'can_edit' : '';?>" 
											disabled="disabled" 
											name="<?php echo $prop;?>" 
											id="<?php echo $prop;?>" 
											value="<?php echo  cptexturize( wp_kses_post( $data ) );?>"
										></input>
										<?php break;
									case 'label':?>
										  <em><?php echo $data;?></em>
								        <?php break;
									case 'checkbox':
									?>
										<input type="checkbox" 
											class="<?php echo $prop_data['can_edit'] ? 'can_edit' : '';?>" 
											name="<?php echo $prop;?>" 
											id="<?php echo $prop;?>"
											<?php if(isset($data) && $data == 'yes' ):?>
											checked="checked"
											<?php endif; ?>
										></input>
										<?php break;
								}?>
							</p>
						<?php }
						}
                        ?>
					</div>
					
			</div>
		</div>
		<?php
	}
	
	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;
        if(isset($_REQUEST['_quickbooks_sync'])){
            update_post_meta($post_id, '_quickbooks_sync', 'yes');
        }else{
            update_post_meta($post_id, '_quickbooks_sync', 'no');
        } 
    }
}
