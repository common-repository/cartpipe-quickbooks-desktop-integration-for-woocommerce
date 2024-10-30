<?php
/**
 * Admin View: Bulk Edit Products
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>

<fieldset class="inline-edit-col-right">
    <div id="cartpipe-fields-bulk" class="inline-edit-col">
        <h4><?php _e( 'Cartpipe', 'cartpipe' ); ?></h4>
        <?php do_action( 'cartpipe_product_bulk_edit_start' ); ?>
        <div class="inline-edit-group">
            <label class="alignleft quickbooks_sync">
                <input type="checkbox" name="_quickbooks_sync" value="1">
                <span class="checkbox-title"><?php _e( 'Sync with QuickBooks', 'cartpipe' ); ?></span>
            </label>
        </div>
        <?php do_action( 'cartpipe_product_bulk_edit_end' ); ?>
        <input type="hidden" name="cartpipe_bulk_edit" value="1" />
        <input type="hidden" name="cartpipe_bulk_edit_nonce" value="<?php echo wp_create_nonce( 'cartpipe_bulk_edit_nonce' ); ?>" />
    </div>
</fieldset>
