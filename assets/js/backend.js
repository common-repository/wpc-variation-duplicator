(function($) {
  'use strict';

  $(function() {
    // ready
    init();
    ready();
  });

  $(document).
      on('woocommerce_variations_added woocommerce_variations_loaded',
          function() {
            init();
            ready();
          });

  $(document).on('woocommerce_variations_input_changed', function(e) {
    ready();
  });

  $(document).on('click touch', '.wpcvd-btn', function(e) {
    e.preventDefault();

    if (!$(this).hasClass('wpcvd-btn-needs-update')) {
      $(this).
          closest('.wc-metaboxes-wrapper').
          find('.wc-metabox > .wc-metabox-content').
          hide();
      $(this).
          closest('.woocommerce_variations').
          find('.woocommerce_variation.open').
          removeClass('open').
          addClass('closed');

      var $old_variation = $(this).closest('.woocommerce_variation');
      var variation_id = $(this).data('id');
      var copies = 1;

      if (wpcvd_vars.copies === 'custom') {
        var copies_prompt = window.prompt(wpcvd_vars.copies_text, 1);
        copies = Number(copies_prompt);

        if (copies_prompt === null || isNaN(copies)) {
          return false;
        }
      }

      $(document.body).
          trigger('wpcvd_before_duplicate', [variation_id, $old_variation]);

      $('#woocommerce-product-data').block({
        message: null, overlayCSS: {
          background: '#fff', opacity: 0.6,
        },
      });

      var data = {
        action: 'wpcvd_duplicate',
        nonce: wpcvd_vars.nonce,
        copies: copies,
        variation_id: variation_id,
        post_id: $('#post_ID').val(),
        loop: $('.woocommerce_variation').length,
      };

      $.post(ajaxurl, data, function(response) {
        var $variation = $(response);

        $variation.addClass('variation-needs-update variation-duplicated');
        $variation.insertAfter($old_variation);
        $('<span class="wpcvd-duplicated-notice">' +
            wpcvd_vars.duplicated_notice.replace('%s', '#' + variation_id) +
            '</span>').appendTo($variation.find('h3'));

        $('button.cancel-variation-changes, button.save-variation-changes').
            prop('disabled', false);
        $('#variable_product_options').
            trigger('woocommerce_variations_added', 1);

        // update order
        re_indexes();

        $('#woocommerce-product-data').unblock();
        $(document.body).
            trigger('wpcvd_duplicated', [variation_id, $variation]);
      });
    }

    return false;
  });

  function init() {
    $('.wpcvd-btn').each(function() {
      var $this = $(this);
      var $variation = $this.closest('.woocommerce_variation');

      $this.insertAfter($variation.find('.edit_variation'));
    });
  }

  function ready() {
    $('.woocommerce_variation').each(function() {
      if ($(this).hasClass('variation-needs-update')) {
        $(this).
            find('.wpcvd-btn').
            addClass('wpcvd-btn-needs-update').
            attr('aria-label', wpcvd_vars.save_before);
      } else {
        $(this).
            find('.wpcvd-btn').removeClass('wpcvd-btn-needs-update').
            attr('aria-label', wpcvd_vars.ready_duplicate);
      }
    });
  }

  function re_indexes() {
    var wrapper = $('#variable_product_options').
            find('.woocommerce_variations'),
        current_page = parseInt(wrapper.attr('data-page'), 10),
        offset = parseInt((current_page - 1) *
            woocommerce_admin_meta_boxes_variations.variations_per_page, 10);

    $('.woocommerce_variations .woocommerce_variation').
        each(function(index, el) {
          $('.variation_menu_order', el).
              val(parseInt($(el).
                      index('.woocommerce_variations .woocommerce_variation'), 10) +
                  1 + offset).
              trigger('change');
        });
  }
})(jQuery);
