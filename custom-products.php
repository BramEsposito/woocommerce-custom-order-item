<?php
/*
Plugin Name: Custom Woocommerce Products
Plugin URI: http://bramesposito.com
Description: Custom Woocommerce Products
Version: 1.0
Author: Bram Esposito
Author URI: http://bramesposito.com
*/


// define the woocommerce_order_item_add_line_buttons callback 
function action_woocommerce_order_item_add_line_buttons( $order ) { 

    print '<button type="button" class="button add-order-bram">';
    esc_html_e( 'Add bram', 'woocommerce' );
    print'</button>';
    ?>
  <script type="text/template" id="tmpl-wc-modal-add-bram">
    <div class="wc-backbone-modal">
      <div class="wc-backbone-modal-content">
        <section class="wc-backbone-modal-main" role="main">
          <header class="wc-backbone-modal-header">
            <h1>Add bram</h1>
            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
              <span class="screen-reader-text">Close modal panel</span>
            </button>
          </header>
          <article>
            <form action="" method="post">
              <label>Order line description</label>
              <textarea name="description" id="bram_description" cols="60" rows="5"></textarea>

              <label>Cost<input name="value" type="text"></label>

            </form>
          </article>
          <footer>
            <div class="inner">
              <button id="btn-ok" class="button button-primary button-large">Add</button>
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
//  wp_add_inline_script('wc-admin-order-meta-boxes',"$( '#woocommerce-order-items' ).on( 'click', 'button.add-order-bram', this.add_fee )");
  wp_enqueue_script("custom_products",plugins_url( 'custom-products.js', __FILE__ ));
//  error_log(plugins_url( 'custom_products.js', __FILE__ ));
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