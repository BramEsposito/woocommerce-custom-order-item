jQuery( function ( $ ) {

  function handleBram2() {


    var value = window.prompt( woocommerce_admin_meta_boxes.i18n_add_fee );

    if ( value != null ) {
      console.log("ok dan");
      // wc_meta_boxes_order_items.block();

      var data = $.extend( {}, {}, {
        action  : 'custom_products_add',
        dataType: 'json',
        whatever: 15,
        order_id: woocommerce_admin_meta_boxes.post_id,
        security: woocommerce_admin_meta_boxes.order_item_nonce,
        amount  : value
      } );

      $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
        if ( response.success ) {
          $( '#woocommerce-order-items' ).find( '.inside' ).empty();
          $( '#woocommerce-order-items' ).find( '.inside' ).append( response.data.html );
          // wc_meta_boxes_order.init_tiptip();
          // wc_meta_boxes_order_items.unblock();
          // wc_meta_boxes_order_items.stupidtable.init();
        } else {
          window.alert( response.data.error );
        }
        // wc_meta_boxes_order_items.unblock();
      });
    }
    return false;
  }

  var handleBram = {
    init: function () {
      $( '#woocommerce-order-items' ).on( 'click', 'button.add-order-bram', this.add_bram);
      $( document.body )
          .on( 'wc_backbone_modal_loaded', this.backbone.init )
          .on( 'wc_backbone_modal_response', this.backbone.response );
    },
    add_bram: function() {
      $( this ).WCBackboneModal({
        template: 'wc-modal-add-bram'
      });
    },
    backbone: {
      init: function (e, target) {
        if ('wc-modal-add-bram' === target) {
          $('#bram_description').focus();
        }
      },

      response: function (e, target, data) {
        console.log(target);

        if ('wc-modal-add-bram' === target) {
          console.log(data);
          handleBram.backbone.add_bram(data);
        }
        if ('wc-modal-add-tax' === target) {
          var rate_id = data.add_order_tax;
          var manual_rate_id = '';

          if (data.manual_tax_rate_id) {
            manual_rate_id = data.manual_tax_rate_id;
          }

          wc_meta_boxes_order_items.backbone.add_tax(rate_id, manual_rate_id);
        }
        if ('wc-modal-add-products' === target) {
          wc_meta_boxes_order_items.backbone.add_item(data.add_order_items);
        }
      },
      add_bram: function (item) {
        if(item.description != "" && item.value != "") {
          // wc_meta_boxes_order_items.block();

          var data = $.extend( {}, {}, {
            action  : 'custom_products_add',
            dataType: 'json',
            description: item.description,
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
            amount  : item.value
          } );

          $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
            if ( response.success ) {
              $( '#woocommerce-order-items' ).find( '.inside' ).empty();
              $( '#woocommerce-order-items' ).find( '.inside' ).append( response.data.html );
              // wc_meta_boxes_order.init_tiptip();
              // wc_meta_boxes_order_items.unblock();
              // wc_meta_boxes_order_items.stupidtable.init();
            } else {
              window.alert( response.data.error );
            }
          });
        }
      },

      add_item: function (add_item_ids) {
        if (add_item_ids) {
          wc_meta_boxes_order_items.block();

          var data = {
            action: 'woocommerce_add_order_item',
            item_to_add: add_item_ids,
            dataType: 'json',
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
            data: $('#wc-backbone-modal-dialog form').serialize()
          };

          // Check if items have changed, if so pass them through so we can save them before adding a new item.
          if ('true' === $('button.cancel-action').attr('data-reload')) {
            data.items = $('table.woocommerce_order_items :input[name], .wc-order-totals-items :input[name]').serialize();
          }

          $.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {
            if (response.success) {
              $('#woocommerce-order-items').find('.inside').empty();
              $('#woocommerce-order-items').find('.inside').append(response.data.html);
              wc_meta_boxes_order.init_tiptip();
              wc_meta_boxes_order_items.stupidtable.init();
            }
            else {
              window.alert(response.data.error);
            }
            wc_meta_boxes_order_items.unblock();
          });
        }
      }
    }
  }

  handleBram.init();
});