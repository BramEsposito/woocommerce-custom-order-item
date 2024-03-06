<?php
/*
* Plugin Name: آیتم سفارشی ووکامرس
* Plugin URI: https://github.com/alihesarian/woocommerce-custom-order-item
* Description: افزودن اقلام سفارشی به سفارشات ووکامرس
* Version: 1.1
* Author: محمد علی حصاریان
* Author URI: http://hesarian.ir
*/


class WoocommerceCustomOrderItemMaster {
  public function __construct(){
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
      // add the action 
      add_action( 'woocommerce_order_item_add_line_buttons', [ $this, 'Modal' ], 10, 1 );
      add_action( 'admin_enqueue_scripts', [ $this , 'Scripts' ] );
      add_action( 'wp_ajax_custom_products_add', [ $this , 'Store' ] );
    }
  }

  public function Modal( $order ) {
    print '<button type="button" class="button add-order-custom-item">افزودن آیتم سفارشی</button>';
    ?>
    <script type="text/template" id="tmpl-wc-modal-add-custom-item">
      <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content">
          <section class="wc-backbone-modal-main" role="main">
            <header class="wc-backbone-modal-header">
              <h1>افزودن آیتم سفارشی</h1>
              <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                <span class="screen-reader-text">خروج</span>
              </button>
            </header>
            <article>
              <form action="" method="post">
                <table class="form-table">

                  <tbody><tr><th scope="row"><label for="custom_item_description">نام:</label></th>
                    <td>
                      <textarea name="description" id="custom_item_description" cols="60" rows="3"></textarea>
                      <br>
                      <p class="description" id="tagline-description">نام و توضیحات آیتم سفارشی</p></td>
                    </tr>
                    <tr>
                      <th scope="row"><label for="custom-item-count">تعداد:</label></th>
                      <td><input id="custom-item-count" name="count" type="number" value="1"></td>
                    </tr>
                    <tr>
                      <th scope="row"><label for="custom-item-cost">قیمت:</label></th>
                      <td><input id="custom-item-cost" name="value" type="text"></td>
                    </tr>
                  </tbody>
                </table>
              </form>
            </article>
            <footer>
              <div class="inner">
                <button id="btn-ok" class="button button-primary button-large">افزودن</button>
              </div>
            </footer>
          </section>
        </div>
      </div>
      <div class="wc-backbone-modal-backdrop modal-close"></div>
    </script>
    <?php
  }
    
  public function Scripts() {
    wp_enqueue_script("custom_products",plugins_url( 'custom-products.js', __FILE__ ));
  }

  public function Store() {
    check_ajax_referer( 'order-item', 'security' );

    if ( ! current_user_can( 'edit_shop_orders' ) ) {
      wp_die( -1 );
    }

    try {

      $order_id     = absint( $_POST['order_id'] );
      $order        = wc_get_order( $order_id );
      $items_to_add = wp_parse_id_list( is_array( $_POST['item_to_add'] ) ? $_POST['item_to_add'] : [ $_POST['item_to_add'] ] );
      $items        = ( ! empty( $_POST['items'] ) ) ? $_POST['items'] : '';
      $description  = ( ! empty( $_POST['description'] ) ) ? $_POST['description'] : '';
      $amount       = ( ! empty( $_POST['amount'] ) ) ? $_POST['amount'] : 0;
      $quantity       = ( ! empty( $_POST['count'] ) ) ? intval($_POST['count']) : 1;

      if ( ! $order ) {
        throw new Exception( __( 'Invalid order', 'woocommerce' ) );
      }

      // If we passed through items it means we need to save first before adding a new one.
      if ( ! empty( $items ) ) {
        $save_items = [];
        parse_str( $items, $save_items );
        // Save order items.
        wc_save_order_items( $order->get_id(), $save_items );
      }

      $args = [
        'name'  =>  $description,
        'total' =>  $amount * $quantity
      ];

      $item = new WC_Order_Item_Product();
      $item->set_quantity( $quantity );
      $item->set_props( $args );
      $item->set_backorder_meta();
      $item->set_order_id( $order->get_id() );
      $item->save();

    //    do_action( 'woocommerce_ajax_added_order_items', $item_id, $item, $order );

      $data = get_post_meta( $order_id );

      ob_start();
      include( plugin_dir_path( __DIR__ ).'woocommerce/includes/admin/meta-boxes/views/html-order-items.php' );

      wp_send_json_success( [
        'html' => ob_get_clean(),
      ]);
    } catch ( Exception $e ) {
      wp_send_json_error([
        'error' => $e->getMessage()
      ]);
    }
  }
}
$WoocommerceCustomOrderItemMaster = new WoocommerceCustomOrderItemMaster;