<?php
/**
 * Admin View: Quick Edit Product
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<fieldset class="inline-edit-col-left">
    <div id="cartpipe-fields" class="inline-edit-col">
        <h4><?php _e( 'Cartpipe', 'cartpipe' ); ?></h4>
        <?php do_action( 'cartpipe_product_quick_edit_start' ); ?>
        <label class="alignleft quickbooks_sync">
            <input type="checkbox" name="_quickbooks_sync" value="1">
            <span class="checkbox-title"><?php _e( 'Sync with QuickBooks', 'cartpipe' ); ?></span>
        </label>
        
        <?php do_action( 'cartpipe_product_quick_edit_end' ); ?>

        <input type="hidden" name="cartpipe_quick_edit" value="1" />
        <input type="hidden" name="cartpipe_quick_edit_nonce" value="<?php echo wp_create_nonce( 'cartpipe_quick_edit_nonce' ); ?>" />
    </div>
</fieldset>
