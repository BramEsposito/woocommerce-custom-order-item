<?php
/*
Plugin Name: Custom Woocommerce Order Item
Plugin URI: http://bramesposito.com
Description: Custom Woocommerce Products
Version: 1.0
Author: Bram Esposito
Author URI: http://bramesposito.com
*/


// define the woocommerce_order_item_add_line_buttons callback 
function action_woocommerce_order_item_add_line_buttons( $order ) { 

    print '<button type="button" class="button add-order-custom-item">';
    esc_html_e( 'Add Custom Order Item', 'custom-products' );
    print'</button>';
    ?>
  <script type="text/template" id="tmpl-wc-modal-add-custom-item">
    <div class="wc-backbone-modal">
      <div class="wc-backbone-modal-content">
        <section class="wc-backbone-modal-main" role="main">
          <header class="wc-backbone-modal-header">
            <h1><?php _e("Add Custom Item", 'custom-products'); ?></h1>
            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
              <span class="screen-reader-text">Close modal panel</span>
            </button>
          </header>
          <article>
            <form action="" method="post">
              <table class="form-table">

        <tbody><tr><th scope="row"><label for="custom_item_description"><?php _e("Order line description", 'custom-products'); ?>:</label></th>
          <td>
            <textarea name="description" id="custom_item_description" cols="60" rows="3"></textarea>
            <p class="description" id="tagline-description"><?php _e("Describe this custom order item", 'custom-products'); ?>.</p></td>
          </tr>
          <tr><th scope="row"><label for="custom-item-cost"><?php _e("Cost", 'custom-products'); ?>:</label></th>
            <td><input id="custom-item-cost" name="value" type="text">
              <p class="description" id="tagline-description"><?php _e("Set the price for this item (use decimal notation (.) )", 'custom-products'); ?>.</p></td>
          </tr>
        </tbody>
      </table>
            </form>
          </article>
          <footer>
            <div class="inner">
              <button id="btn-ok" class="button button-primary button-large"><?php _e("Add"); ?></button>
            </div>
          </footer>
        </section>
      </div>
    </div>
	  <div class="wc-backbone-modal-backdrop modal-close"></div>
  </script>
  <?php
}; 
         
// add the action 
add_action( 'woocommerce_order_item_add_line_buttons', 'action_woocommerce_order_item_add_line_buttons', 10, 1 );


function custom_products_add_scripts() {
  wp_enqueue_script("custom_products",plugins_url( 'custom-products.js', __FILE__ ));
}

add_action( 'admin_enqueue_scripts', "custom_products_add_scripts" );

add_action( 'wp_ajax_custom_products_add', 'custom_products_add' );

function custom_products_add() {
  check_ajax_referer( 'order-item', 'security' );

  if ( ! current_user_can( 'edit_shop_orders' ) ) {
    wp_die( -1 );
  }

  try {
    $order_id     = absint( $_POST['order_id'] );
    $order        = wc_get_order( $order_id );
    $items_to_add = wp_parse_id_list( is_array( $_POST['item_to_add'] ) ? $_POST['item_to_add'] : array( $_POST['item_to_add'] ) );
    $items        = ( ! empty( $_POST['items'] ) ) ? $_POST['items'] : '';
    $description  = ( ! empty( $_POST['description'] ) ) ? $_POST['description'] : '';
    $amount       = ( ! empty( $_POST['amount'] ) ) ? $_POST['amount'] : 0;

    if ( ! $order ) {
      throw new Exception( __( 'Invalid order', 'woocommerce' ) );
    }

    // If we passed through items it means we need to save first before adding a new one.
    if ( ! empty( $items ) ) {
      $save_items = array();
      parse_str( $items, $save_items );
      // Save order items.
      wc_save_order_items( $order->get_id(), $save_items );
    }

    $args = array('name' => $description,'total'=>$amount);

    $item = new WC_Order_Item_Product();
    $item->set_props( $args );
    $item->set_backorder_meta();
    $item->set_order_id( $order->get_id() );
    $item->save();

//    do_action( 'woocommerce_ajax_added_order_items', $item_id, $item, $order );

    $data = get_post_meta( $order_id );

    ob_start();
    include( plugin_dir_path( __DIR__ ).'woocommerce/includes/admin/meta-boxes/views/html-order-items.php' );

    wp_send_json_success( array(
      'html' => ob_get_clean(),
    ) );
  } catch ( Exception $e ) {
    wp_send_json_error( array( 'error' => $e->getMessage() ) );
  }
}