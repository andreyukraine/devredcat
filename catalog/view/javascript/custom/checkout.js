window.custom_block = {

  /**
   * Початковий рендеринг блоку оформлення замовлення
   */
  render: function (block) {

    var $target = $('#custom-' + block);
    if (!$target.length) {
      return;
    }

    $.ajax({
      url: 'index.php?route=extension/module/custom/render',
      type: 'get',
      data: {'block': block},
      dataType: 'html',
      beforeSend: function () {
        $target.addClass('custom_lock');
      },
      success: function (html) {
        setTimeout(function () {
          $target.html(html).removeClass('custom_lock');
        }, 100);
      }
    });
  },

  /**
   * Рендеринг конкретного блоку замовлення (доставка, оплата тощо)
   */
  render_order: function (block, order_id) {

    var target = $('#custom-' + block + '-' + order_id);
    if (!target.length) target = $('#' + block + '-' + order_id);

    if (!target.length) {
      return $.Deferred().resolve();
    }

    return $.ajax({
      url: 'index.php?route=extension/module/custom/render',
      type: 'get',
      data: {'block': block, 'order_id': order_id},
      dataType: 'html',
      beforeSend: function () {
        target.addClass('custom_lock');
      },
      success: function (html) {
        setTimeout(function () {
          // Шукаємо спочатку за префіксом custom-, потім за прямим ID
          var target = $('#custom-' + block + '-' + order_id);
          if (!target.length) target = $('#' + block + '-' + order_id);
          
          target.html(html).removeClass('custom_lock');
        }, 100);
      }
    });
  },

  /**
   * Зберігає ручне введення адреси для внутрішньої доставки
   */
  editAddress: function(address, shipping, order_id) {
    // Clear errors immediately for better UX
    var $input = $('#shipping-' + order_id + ' [name="shipping_method[' + order_id + '][address_input]"]');
    $input.closest('.has-error').removeClass('has-error').find('.text-danger, .custom-text-danger').remove();

    $.ajax({
      url: 'index.php?route=extension/module/custom/shipping/update&shipping_code=' + shipping + '&order_id=' + order_id,
      type: 'post',
      data: {
        address: address,
        order_id: order_id,
        shipping_code: "ourdelivery"
      },
      dataType: 'json',
      success: function(json) {
        // No need to clear errors here if we do it immediately, 
        // but we can keep it as a fallback
        if (!json.error) {
           $('.alert, .text-danger, .custom-text-danger:not(.shipping-method-error)').remove();
           $('.has-error').removeClass('has-error');
        }
      }
    });
  },

  check_pickup_warehouse: function (order_id) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/shipping/pickup&order_id=' + order_id,
      dataType: 'json',
      success: function (json) {

        $('html body').append('<div id="modal-pickup" class="modal fade" role="dialog" data-backdrop="static" data-keyboard="false">' + json['delivery_options'] + '</div>');

        $('#modal-pickup').modal('show');

        $(document).on('click', '.contact-store-line', function () {
          var warehouseElement = $(this).find('#warehouse-id');
          selected = warehouseElement.data('id');
          var warehouseName = warehouseElement.text();

          // console.log('Selected data-id: ' + selected);
          // console.log('Selected warehouse name: ' + warehouseName);

          var html = '<div class="selected-pickup">';
          html += '<p>Вибраний склад: ' + warehouseName + '</p>';
          html += '<input id="selected-pickup-id" name="pickup-id" value="' + selected + '" type="hidden">';
          html += '<span class="edit_pickup" onclick="custom_block.check_pickup_warehouse(' + order_id + ')">Змінити</span></div>';

          $('#shipping-options-pickup-0').html(html);
          custom_block.checkoutShipping(order_id);

          $('#modal-pickup').modal('hide');
          $('#shipping-options-pickup-0').html(html);

        });
      }
    });
  },


  update_option: function (order_id, type) {
    return $.ajax({
      url: 'index.php?route=extension/module/custom/shipping/update_option&shipping_option=' + type + '&order_id=' + order_id,
      type: 'post',
      dataType: 'json',
      success: function (json) {
        //console.log(order_id);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },

  shipping_option: function (type, order_id) {
    return this.update_option(order_id, type);
  },

  shipping: function (code, order_id) {
    $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
    $('.has-error').removeClass('has-error');
    $.ajax({
      url: 'index.php?route=extension/module/custom/shipping/update',
      type: 'post',
      data: {
        order_id: order_id,
        shipping_code : code
      },
      dataType: 'json',
      success: function (json) {

        $('.alert, .text-danger, .custom-text-danger:not(.shipping-method-error)').remove();
        $('.has-error').removeClass('has-error');

        $('[id^=shipping-field]').hide().removeClass('required');

        if (code === 'pickup' && order_id <= 0) {
          if (json['delivery_options'][code][order_id] === "") {
            custom_block.check_pickup_warehouse(order_id);
          }
        }

        $('#shipping-' + order_id + ' [id^="shipping-options-"]').html('');
        $('#shipping-options-' + code + '-' + order_id).html(json['delivery_options'][code][order_id]);

        setTimeout(function () {
          custom_block.render_order('payment', order_id);
          custom_block.render('total');
          custom_block.render('button');

        }, 200);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },

  /**
   * Повне оновлення даних доставки та оновлення інтерфейсу
   */
  updateShipping: function (order_id) {
    $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
    $('.has-error').removeClass('has-error');
    var shipping_code = $('#shipping-' + order_id + ' input[name="shipping_method[' + order_id + '][code]"]:checked').val();

    return new Promise((resolve, reject) => {
      $.ajax({
        url: 'index.php?route=extension/module/custom/shipping/update',
        type: 'post',
        data: $('#shipping-' + order_id + ' input[type=\'text\'], #shipping-' + order_id + ' input[type=\'date\'], #shipping-' + order_id + ' input[type=\'datetime-local\'], #shipping-' + order_id + ' input[type=\'time\'], #shipping-' + order_id + ' input[type=\'checkbox\']:checked, #shipping-' + order_id + ' input[type=\'radio\']:checked, #shipping-' + order_id + ' input[type=\'hidden\'], #shipping-' + order_id + ' textarea, #shipping-' + order_id + ' select').serialize() + '&shipping_code=' + shipping_code,
        dataType: 'json',
        success: function (json) {
          $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
          $('.shipping-method-error').hide().empty();
          $('.has-error').removeClass('has-error');
          $('#shipping-' + order_id).find('.text-danger, .custom-text-danger').not('.shipping-method-error').remove();

          if (json['redirect']) {
            location = json['redirect'];
          } else if (json['error']) {
            if (json['error']['warning']) {
              $('body').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json['error']['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button> </div>');
            }
            
            // Display field errors
            for (var orderId in json['error']) {
              if (typeof json['error'][orderId] === 'object') {
                for (var field in json['error'][orderId]) {
                  var element = $('#shipping-' + orderId + ' [name="shipping_method[' + orderId + '][' + field + ']"]');
                  if (element.length) {
                    if (field === 'dropshipping_tth') {
                      var errorBlock = element.closest('.radio-shipping-method').find('.shipping-method-error');
                      if (errorBlock.length) {
                        errorBlock.html(json['error'][orderId][field]).show();
                      }
                    } else {
                      element.before('<div class="custom-text-danger">' + json['error'][orderId][field] + '</div>');
                    }
                  }
                }
              }
            }

            $('.text-danger, .custom-text-danger').parent().addClass('has-error');
            reject('shipping');
          }

          if (json['delivery_options']) {
            for (var code in json['delivery_options']) {
              for (var orderId in json['delivery_options'][code]) {
                if (json['delivery_options'][code][orderId] !== "") {
                  $('#shipping-options-' + code + '-' + orderId).html(json['delivery_options'][code][orderId]);
                }
              }
            }
          }

          resolve();
        },
        error: function (xhr, ajaxOptions, thrownError) {
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    })
  },

  checkoutShipping: function (order_id) {
    $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
    $('.shipping-method-error').hide().empty();
    $('.has-error').removeClass('has-error');

    var selector = (order_id !== undefined && order_id !== null && order_id !== 'undefined') ? '#shipping-' + order_id : '[id^="shipping-"]';

    return new Promise((resolve, reject) => {
      $.ajax({
        url: 'index.php?route=extension/module/custom/shipping/save' + (order_id !== undefined && order_id !== null && order_id !== 'undefined' ? '&order=' + order_id : ''),
        type: 'post',
        data: $(selector).find('input[type=\'text\'], input[type=\'date\'], input[type=\'datetime-local\'], input[type=\'time\'], input[type=\'checkbox\']:checked, input[type=\'radio\']:checked, input[type=\'hidden\'], textarea, select').serialize(),
        dataType: 'json',
        beforeSend: function () {
          // $('#button-custom-order').button('loading');
        },
        success: function (json) {

          $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
          $('.shipping-method-error').hide().empty();
          $('.has-error').removeClass('has-error');

          if (json['redirect']) {
            location = json['redirect'];
          } else if (json['error']) {

            if (json['error']['warning']) {
              $('body').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json['error']['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button> </div>');
            }

            for (var orderId in json['error']) {
              for (var field in json['error'][orderId]) {
                // Знаходимо елемент для конкретного поля помилки в замовленні
                var element = $('#shipping-' + orderId + ' [name="shipping_method[' + orderId + '][' + field + ']"]');

                // Додаємо повідомлення про помилку перед елементом, якщо елемент знайдено
                if (element.length) {
                  if (field === 'dropshipping_tth') {
                    var errorBlock = element.closest('.radio-shipping-method').find('.shipping-method-error');
                    if (errorBlock.length) {
                      errorBlock.html(json['error'][orderId][field]).show();
                    }
                  } else {
                    element.before('<div class="custom-text-danger">' + json['error'][orderId][field] + '</div>');
                  }
                } else {
                  console.warn("Елемент з іменем 'shipping_method[" + orderId + "][" + field + "]' не знайдено.");
                }
              }
            }

            $('.text-danger, .custom-text-danger').parent().addClass('has-error');

            reject('shipping');
          }

          resolve();

        },
        complete: function () {
          // $('#button-custom-order').button('reset');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });
  },


  payment: function (code, order_id) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/payment/update',
      type: 'post',
      data: {
        order_id: order_id,
        payment_code : code
      },
      dataType: 'json',
      success: function (json) {

        $('[name=payment_method]').parents('.radio').hide();

        for (i = 0; i < json.length; i++) {
          let method = json[i].name.replace('-', '_');
          $('[name=payment_method][value^=' + method + ']').parents('.radio').show();
        }

        $('[name=payment_method]:visible').first().prop('checked', true);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },

  checkoutRemaning: function (order_id) {
    return new Promise((resolve, reject) => {

      // Створюємо об'єкт дати
      var currentDate = new Date();

      // Форматуємо дату та час
      var dateTimeString = currentDate.toLocaleString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });

      $.ajax({
        url: 'index.php?route=extension/module/custom/cart/remind' + (order_id !== undefined && order_id !== null && order_id !== 'undefined' ? '&order=' + order_id : ''),
        type: 'post',
        dataType: 'json',
        beforeSend: function () {
          // Видаляємо попередні блоки з інформацією
          $('.free-stock-info').remove();
          $('.button-order-stock-info').remove();
        },
        success: function (json) {

          if (json['remind_prods']) {

            var firstStockInfo = null; // Змінна для збереження першого елементу

            for (var cart_id in json['remind_prods']) {

              var row_cart = $('.cart-fix-list-products #cart-line-' + cart_id + ' .info-item');

              // Створюємо блок з спеціальним класом
              var stockInfo = $('<div>', {
                class: 'free-stock-info text-danger',
                text: 'Вільний залишок ' + json['remind_prods'][cart_id] + ' шт. ' +  dateTimeString
              });

              // Запам'ятовуємо перший створений елемент, який був доданий в DOM
              if (!firstStockInfo && row_cart.length > 0) {
                firstStockInfo = stockInfo;
              }

              row_cart.append(stockInfo);
            }

            // Прокручуємо до першого блоку, якщо він є в DOM
            if (firstStockInfo && firstStockInfo.offset()) {
              // Плавна прокрутка до елементу
              $('html, body').animate({
                scrollTop: firstStockInfo.offset().top - 250 // Відступ зверху 250px
              }, 500); // Тривалість анімації 500 мс
            }

            // Створюємо блок з спеціальним класом
            $('body').before('<div class="alert alert-danger alert-dismissible"><i class="fa fa-check-circle"></i>' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            // Удаляем через 2 секунды
            setTimeout(function () {
              $('.alert-danger').remove();
            }, 2000);

            reject('remind');
          }else {
            resolve();
          }

        },
        error: function (xhr, ajaxOptions, thrownError) {
          reject('remind');
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });
  },

  checkoutPayment: function (order_id) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: 'index.php?route=extension/module/custom/payment/save' + (order_id !== undefined && order_id !== null && order_id !== 'undefined' ? '&order=' + order_id : ''),
        type: 'post',
        data: $('#custom-payment input[name=\'payment_method\']:checked, .checkout-agree input[name=\'agree\']:checked').serialize(),
        dataType: 'json',
        beforeSend: function () {
          // $('#button-custom-order').button('loading');
        },
        success: function (json) {

          $('#custom-payment .alert, #custom-control .alert').remove();

          if (json['redirect']) {
            location = json['redirect'];
          } else if (json['error']) {

            if (json['error']['payment_method']) {
              $('#custom-payment').prepend('<div class="alert alert-warning">' + json['error']['payment_method'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              reject('payment');
            }

            if (json['error']['agree']) {
              $('#custom-control').prepend('<div class="alert alert-warning">' + json['error']['agree'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              reject('control');
            }

          }

          resolve();

        },
        complete: function () {
          // $('#button-custom-order').button('reset');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });
  },


  customer: function (value) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/customer/update&customer_group_id=' + value,
      dataType: 'json',
      success: function (json) {
        $('[id^=customer-field]').hide();
        $('[id^=customer-field]').removeClass('required');

        for (i = 0; i < json.length; i++) {
          field = json[i];

          $('#customer-field-' + field.name).show();

          if (field['required']) {
            $('#customer-field-' + field.name).addClass('required');
          }
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },

  checkoutLogin: function () {

    return new Promise((resolve, reject) => {

      $.ajax({
        url: 'index.php?route=extension/module/custom/login/save',
        type: 'post',
        data: $('#custom-login input[type=\'radio\']:checked').serialize(),
        dataType: 'json',
        beforeSend: function () {
          // $('#button-custom-order').button('loading');
        },
        success: function (json) {
          resolve();
        },
        complete: function () {
          // $('#button-custom-order').button('reset');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });

    });

  },

  checkoutCustomer: function () {
    $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
    $('.has-error').removeClass('has-error');

    return new Promise((resolve, reject) => {

      $.ajax({
        url: 'index.php?route=extension/module/custom/customer/save',
        type: 'post',
        data: $('#custom-customer input[type=\'text\'], #custom-customer input[type=\'date\'], #custom-customer input[type=\'datetime-local\'], #custom-customer input[type=\'time\'], #custom-customer input[type=\'checkbox\']:checked, #custom-customer input[type=\'radio\']:checked, #custom-customer input[type=\'hidden\'], #custom-customer input[type=\'password\'], #custom-customer textarea, #custom-customer select').serialize(),
        dataType: 'json',
        beforeSend: function () {
          // $('#button-custom-order').button('loading');
        },
        success: function (json) {

          $('.alert, .text-danger, .custom-text-danger').not('.shipping-method-error').remove();
          $('.shipping-method-error').hide().empty();
          $('.has-error').removeClass('has-error');

          if (json['redirect']) {
            location = json['redirect'];
          } else if (json['error']) {

            if (json['error']['warning']) {
              $('#custom-customer').prepend('<div class="alert alert-warning">' + json['error']['warning'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            for (i in json['error']) {
              var element = $('#customer-field-' + i.replace('_', '-'));
              $(element).append('<div class="custom-text-danger">' + json['error'][i] + '</div>');
            }

            $('.text-danger, .custom-text-danger').parent().addClass('has-error');

            reject('customer');

          }

          resolve();

        },
        complete: function () {
          // $('#button-custom-order').button('reset');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });

    });

  },


  checkoutComment: function () {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: 'index.php?route=extension/module/custom/comment/save',
        type: 'post',
        data: $('#comment-ord textarea').serialize(),
        dataType: 'json',
        beforeSend: function () {
          // $('#button-custom-order').button('loading');
        },
        success: function (json) {

          $('.alert').remove();

          if (json['error']) {
            if (json['error']['warning']) {
              $('#custom-comment').prepend('<div class="alert alert-warning">' + json['error']['warning'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            reject('comment');
          }

          resolve();

        },
        complete: function () {
          // $('#button-custom-order').button('reset');
        },
        error: function (xhr, ajaxOptions, thrownError) {
          //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });
  },

  checkoutConfirm: function () {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: 'index.php?route=checkout/confirm',
        dataType: 'html',
        beforeSend: function () {
          // Починаємо процес, наприклад, показуємо завантаження
          //$('#button-create-order').prop('disabled', true).val('Завантаження...');
        },
        success: function (html) {
          // Перевірка на JSON (помилка сесії)
          try {
            var json = JSON.parse(html);
            if (json['error']) {
              if (json['error']['warning']) {
                $('body').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json['error']['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button> </div>');
                
                // Прокрутка до помилки
                $('html, body').animate({ scrollTop: 0 }, 'slow');
              }
              if (json['redirect']) {
                setTimeout(function() {
                  location = json['redirect'];
                }, 2000);
              }
              $('#checkout-checkout').removeClass('custom_lock');
              reject('confirm');
              return;
            }
          } catch (e) {
            // Не JSON, продовжуємо як зазвичай
          }

          window.location.replace('/index.php?route=checkout/confirm');
          resolve('confirm'); // завершуємо Promise
        },
        complete: function () {
          $('#button-create-order').prop('disabled', false).val('Оформити замовлення'); // Повертаємо кнопку в початковий стан
        },
        error: function (xhr, ajaxOptions, thrownError) {
          alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          reject('confirm');
        }
      });
    });
  },

  failureCallback: function (id, order_id) {
    $('#checkout-checkout').removeClass('custom_lock');
    //$('html, body').animate({scrollTop: $('#custom-' + id + '-' + order_id).offset().top}, 'slow');
  },

  debounceSaveShipping: function(order_id) {
    clearTimeout(window.shippingInputTimer);
    window.shippingInputTimer = setTimeout(function() {
      if (typeof custom_block !== 'undefined' && typeof custom_block.checkoutShipping === 'function') {
        custom_block.checkoutShipping(order_id);
      }
    }, 800);
  }
}
