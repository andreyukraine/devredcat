$(window).load(function () {
  custom_cart.bindPopupStoreSelector();
});

$(document).ready(function () {

  $(document).on('focusin', '.quantity-input', function () {
    $(this).data('oldValue', $(this).val());
  });

  $(document).on('change', '.quantity-input', function () {
    var prod_id = $(this).data('key');
    var cart_id = $(this).data('cart');
    var warehouse_id = $(this).data('store');

    // Получаем текущее значение input
    var quantity = parseInt($(this).val(), 10);

    if (!isNaN(quantity)) {
      // Устанавливаем новое значение в инпуте
      $('#qty-' + prod_id + '-' + cart_id).val(quantity);

      if (view_cart_right && warehouse_id !== undefined) {
        custom_cart.updateCart(prod_id, cart_id, warehouse_id, "update");
      } else {
        // Включаем текущий элемент в выборку
        var opts = $('#product-' + prod_id + ' input[type="text"], #product-' + prod_id + ' input[type="hidden"], #product-' + prod_id + ' input[type="radio"]:checked, #product-' + prod_id + ' input[type="checkbox"]:checked, #product-' + prod_id + ' select, #product-' + prod_id + ' textarea').add($(this));

        // Передаем обновленные данные
        custom_cart.update(prod_id, cart_id, "update", opts);
      }
    }
  });

  $(document).on('focusin', '.quantity-input-order', function () {
    // Сохраняем старое значение в data-атрибуте и обновляем скрытое поле oldValue
    var old_value = $(this).val();
    $(this).data('old_value', old_value);

    // Обновляем скрытое поле oldValue
    var prod_id = $(this).data('key');
    var order_id = $(this).data('order');
    $('#old-value-' + order_id + '-' + prod_id).val(old_value);
  });

  $(document).on('change', '.quantity-input-order', function () {
    var prod_id = $(this).data('key');
    var cart_id = $(this).data('cart');
    var order_id = $(this).data('order');

    // Получаем текущее значение input и старое значение из скрытого поля
    var quantity = parseInt($(this).val(), 10);

    //console.log(quantity);

    if (!isNaN(quantity)) {
      // Устанавливаем новое значение в инпуте
      $('#qty-' + order_id + '-' + prod_id + '-' + cart_id).val(quantity);

      // Правильный выбор элементов
      var quantityInput = $('#qty-' + order_id + '-' + prod_id + '-' + cart_id);
      var oldValueInput = $('#old-value-' + order_id + '-' + prod_id + '-' + cart_id);

      // Объединяем в один jQuery объект
      var opts = quantityInput.add(oldValueInput);

      custom_cart.update_order(prod_id, cart_id, order_id, "update", opts);
    }
  });

  $(document).on('click', '.plus-order.btn', function () {
    if ($(this).hasClass('not-available')) return;
    var prod_id = $(this).data('key');
    var cart_id = $(this).data('cart');
    var order_id = $(this).data('order');

    var opts = $('#product-' + order_id + '-' + prod_id + ' input[name="quantity"]');
    custom_cart.update_order(prod_id, cart_id, order_id, "plus", opts);
  });

  $(document).on('click', '.minus-order.btn', function () {
    if ($(this).hasClass('not-available')) return;
    var prod_id = $(this).data('key');
    var cart_id = $(this).data('cart');
    var order_id = $(this).data('order');

    var opts = $('#product-' + order_id + '-' + prod_id + ' input[name="quantity"]');
    custom_cart.update_order(prod_id, cart_id, order_id, "minus", opts);
  });

  // Синхронізація радіо-кнопок опцій (Grid vs List)
  $(document).on('change', 'input[type="radio"][data-product]', function () {
    var prod_id = $(this).data('product');
    var value = $(this).val();
    var option_id = value.split('-').pop();

    // 1. Знаходимо всі радіо-кнопки цього продукту з таким самим значенням
    var $allRadiosWithValue = $('input[type="radio"][data-product="' + prod_id + '"][value="' + value + '"]');
    $allRadiosWithValue.prop('checked', true);

    // 2. Оновлюємо візуальні класи (radio-control) для всіх знайдених
    $allRadiosWithValue.closest('.option-item').find('.radio-control').addClass('check');

    // 3. Прибираємо виділення з усіх інших опцій цього ж продукту (оскільки у Grid та List різні імена)
    $('input[type="radio"][data-product="' + prod_id + '"]').not($allRadiosWithValue).each(function () {
      $(this).prop('checked', false);
      $(this).closest('.option-item').find('.radio-control').removeClass('check');
    });

    // Оновлюємо основну кнопку в Grid, щоб вона відповідала вибраній опції
    var $mainBtn = $('.main-cart-btn-' + prod_id);
    if ($mainBtn.length) {
      // Оновлюємо клас cart-btns-XXX для синхронізації
      var currentClasses = $mainBtn.attr('class').split(' ');
      for (var i = 0; i < currentClasses.length; i++) {
        if (currentClasses[i].startsWith('cart-btns-')) {
          $mainBtn.removeClass(currentClasses[i]);
        }
      }
      $mainBtn.addClass('cart-btns-' + prod_id + '-' + option_id);

      // Також оновлюємо дані кнопки (data-cart тощо) за потреби
      // Але зазвичай краще просто оновити HTML через AJAX або залишити як є,
      // бо при натисканні AJAX все одно підтягне актуальні дані.
    }
  });

});

var view_cart_right = false;


window.custom_cart = {

  handleClickInCart: function (btn, event) {

    // Якщо це не реальний клік або кнопка неактивна, виходимо
    if (!btn || !$(btn).is(':visible') || $(btn).hasClass('not-available')) {
      return;
    }

    var $btn = $(btn);
    var prod_id = $btn.data('key');
    var cart_id = $btn.data('cart');
    var warehouse_id = $btn.data('store');

    custom_cart.updateCart(prod_id, cart_id, warehouse_id, event);
  },

  handleClickPlus: function (btn) {

    // Якщо це не реальний клік або кнопка неактивна, виходимо
    if (!btn || !$(btn).is(':visible') || $(btn).hasClass('not-available')) {
      return;
    }

    var $btn = $(btn);
    var prod_id = $btn.data('key');
    var cart_id = $btn.data('cart');

    // 1. Якщо ми всередині .option-item, обираємо відповідний radio перед збором опцій
    var $optionItem = $btn.closest('.option-item');
    if ($optionItem.length) {
      var $radio = $optionItem.find('input[type="radio"][data-product="' + prod_id + '"]');
      if ($radio.length) {
        var value = $radio.val();
        // Знаходимо ВУІ радіо-кнопки для цього продукту з таким самим значенням (для синхронізації grid/list)
        var $allRadiosWithValue = $('input[type="radio"][data-product="' + prod_id + '"][value="' + value + '"]');
        
        $allRadiosWithValue.prop('checked', true);
        
        // Оновлюємо візуальні класи для всіх знайдених елементів
        $allRadiosWithValue.closest('.option-item').find('.radio-control').addClass('check');
        
        // Прибираємо виділення з усіх інших радіо-кнопок цього продукту (через різні імена)
        $('input[type="radio"][data-product="' + prod_id + '"]').not($allRadiosWithValue).each(function () {
          $(this).prop('checked', false);
          $(this).closest('.option-item').find('.radio-control').removeClass('check');
        });
      }
    }

    // 2. Збираємо опції з data-product (radio, checkbox, select, textarea)
    var opts = $('input[data-product="' + prod_id + '"], select[data-product="' + prod_id + '"], textarea[data-product="' + prod_id + '"]')
      .filter(function () {
        if ((this.type === 'radio' || this.type === 'checkbox') && !this.checked) {
          return false;
        }
        return true;
      });

    // 🔧 Передаємо
    custom_cart.update(prod_id, cart_id, "plus", opts);

  },

  handleClickMinus: function (btn) {

    // Якщо це не реальний клік або кнопка неактивна, виходимо
    if (!btn || !$(btn).is(':visible') || $(btn).hasClass('not-available')) {
      return;
    }

    var $btn = $(btn);
    var prod_id = $btn.data('key');
    var cart_id = $btn.data('cart');

    // 1. Якщо ми всередині .option-item, обираємо відповідний radio перед збором опцій
    var $optionItem = $btn.closest('.option-item');
    if ($optionItem.length) {
      var $radio = $optionItem.find('input[type="radio"][data-product="' + prod_id + '"]');
      if ($radio.length && !$radio.prop('checked')) {
        $radio.prop('checked', true);
        $optionItem.find('.radio-control').addClass('check');
        $optionItem.siblings().find('.radio-control').removeClass('check');
      }
    }

    // 2. Збираємо опції з data-product (radio, checkbox, select, textarea)
    var opts = $('input[data-product="' + prod_id + '"], select[data-product="' + prod_id + '"], textarea[data-product="' + prod_id + '"]')
      .filter(function () {
        if ((this.type === 'radio' || this.type === 'checkbox') && !this.checked) {
          return false;
        }
        return true;
      });

    opts.each(function (i, el) {
      //console.log(i + ':', el.name, el.type, el.value, el.checked);
    });

    custom_cart.update(prod_id, cart_id, "minus", opts);
  },

  view: function () {
    if (typeof ocmenuview_top !== 'undefined') {
      ocmenuview_top.hide();
    }
    $('#cartview-bg-block').show();
    $('#cartview-content').addClass('active');
    view_cart_right = true;
    document.body.classList.add('body-no-scroll');
    $('#ajax-lock-block').show();

    // Додаємо обробник кліку для ajax-lock-block
    $('#ajax-lock-block').off('click').on('click', function () {
      custom_cart.close();
    });

  },

  close: function () {
    custom_cart.hide();
  },
  open: function () {
    // 1. Створюємо контейнер, якщо його немає
    if ($('.cartview-container').length == 0) {
      $('body').append('<div class="cartview-container"></div>');
    }

    // 2. Вставляємо скелет кошика, який НЕ буде перемальовуватися повністю
    // Це запобігає "бліканню" шапки
    var skeletonHtml =
      '<div id="cartview-content">' +
      '  <div class="cart-fix-top">' +
      '    <div class="cart-fix-close">' +
      '      <img width="25px" height="25px" src="/image/close.svg" onclick="custom_cart.close()" alt="close">' +
      '    </div>' +
      '    <div class="cart-fix-title"><span class="text-21 bl-bold">Кошик</span></div>' +
      '    <div id="cart-header-actions"></div>' +
      '  </div>' +
      '  <div class="cart-fix-block" style="display: flex; align-items: center; justify-content: center; min-height: 200px;">' +
      '    <img src="image/catalog/load.gif" alt="loading..." />' +
      '  </div>' +
      '  <div id="cart-footer-placeholder"></div>' +
      '</div>' +
      '<div id="cartview-bg-block" style="display: none;" onclick="custom_cart.close()"></div>';

    $('.cartview-container').html(skeletonHtml);

    // 3. Відразу показуємо панель (скелет)
    custom_cart.view();

    // 4. Завантажуємо дані та оновлюємо тільки потрібні частини
    $.ajax({
      url: 'index.php?route=common/cart/info',
      type: 'get',
      dataType: 'html',
      success: function (html) {
        var $data = $(html);

        // Отримуємо новий контент
        var newProducts = $data.find('.cart-fix-block').html();
        var newTotal = $data.find('.cart-fix-total').html();
        var clearBtn = $data.find('.clear-cart').length ? $data.find('.clear-cart')[0].outerHTML : '';

        // 5. Оновлюємо блок товарів (прибираємо стилі лоадера)
        $('#cartview-content .cart-fix-block').removeAttr('style').html(newProducts);

        // 6. Оновлюємо кнопку "Очистити" в шапці (не чіпаючи саму шапку)
        $('#cart-header-actions').html(clearBtn);

        // 7. Оновлюємо підсумки (футер кошика)
        if (newTotal) {
          if ($('#cartview-content .cart-fix-total').length == 0) {
            $('#cartview-content').append('<div class="cart-fix-total">' + newTotal + '</div>');
          } else {
            $('#cartview-content .cart-fix-total').html(newTotal);
          }
        }
      }
    });
  },
  hide: function () {
    view_cart_right = false;
    $('#cartview-bg-block').hide();
    $('.cartview-load-img').hide();
    $('#cartview-content').removeClass('active');

    // Видаляємо контейнер після завершення анімації
    setTimeout(function () {
      if (!view_cart_right) {
        $('.cartview-container').remove();
      }
    }, 300);

    document.body.classList.remove('body-no-scroll');
    $('#ajax-lock-block').hide();
  },

  checkStoreAvailability: function (prod_id, cart_id, event, opts, callback) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/ask_store_cart',
      type: 'post',
      data: 'product_id=' + prod_id + '&event=' + event + '&' + opts.serialize(),
      dataType: 'json',
      success: function (json) {
        if (json['order_by_store'] > 0) {
          let modal = $('<div id="modal-popup" class="modal fade" role="dialog">' + json['html'] + '</div>');
          modal.data({
            'prod_id': prod_id,
            'event': event,
            'cart_id': cart_id,
            'opts': opts
          });
          $('html body').append(modal);
          $('#modal-popup').modal('show');

          $(document).on('hide.bs.modal', '#modal-popup.modal.fade', function () {
            $('#modal-popup').remove();
          });
        } else {
          callback(); // Если не требуется выбор склада, продолжаем выполнение
        }
      }
    });
  },

  updateCart: function (prod_id, cart_id, warehouse_id, event) {
    var qty = $('#qty-' + prod_id + '-' + cart_id).val();
    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/update_cart',
      type: 'post',
      data: 'product_id=' + prod_id + '&event=' + event + '&cart_id=' + cart_id + '&warehouse_id=' + warehouse_id + '&quantity=' + qty,
      dataType: 'json',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
        $('#cartview-content').addClass('custom_lock');
        $('#product-' + prod_id + ' .product-cart-block').addClass('custom_lock');
      },
      success: function (json) {

        if (json['empty'] !== "") {
          $('#cart-header-actions').html('');
          $('.cart-fix-block').html('').html(json['empty']);
          $('.cart-fix-total').html('');
        }

        $('#cartview-content').removeClass('custom_lock');
        $('#product-' + prod_id + ' .product-cart-block').removeClass('custom_lock');

        if (typeof custom_block !== 'undefined') {

          if (json['total'] > 0) {
            if (json['order_org'] === 0) {
              custom_block.render('cart');
            } else {
              if (json['order_org'] > 0) {
                custom_block.render('orders_org');
              } else {
                if (json['order_by_store'] === 0 && json['split_order_store'] === 0) {
                  custom_block.render('cart');
                } else {
                  custom_block.render('orders');
                }
              }
            }

            // Оновлюємо основну сторінку
            custom_block.render('total');
            custom_block.render_order('shipping', 0);
            custom_block.render('button');

          } else {
            $('#cart-header-actions').html('');
            $('#checkout-checkout').html('').html(json['empty']);
          }


          if (json['cart_id'] === 0) {
            $('.cart-fix-list-products').find('#product-cart-' + cart_id).remove();
          } else {
            $('.cart-fix-list-products').find('#product-cart-' + cart_id + ' .cart-price-total').html(json['html_price_total']);
            
            // Оновлюємо кнопки безпосередньо в кошику
            $('.cart-fix-list-products').find('#product-cart-' + cart_id + ' .cart-right-btns').html(json['html']);

            // Отримуємо новий HTML та додаємо потрібні класи для синхронізації (для каталогу)
            var $newHtml = $(json['product_html']);
            
            // Оновлюємо всі кнопки цієї опції на сторінці (в списку та в сітці)
            $('.cart-btns-' + json['uniq_id']).replaceWith($newHtml);

            // СИНХРОНІЗАЦІЯ З ГОЛОВНОЮ КНОПКОЮ (Grid)
            var $activeRadio = $('input[name="option[' + prod_id + ']"]:checked');
            if ($activeRadio.length && $activeRadio.val().includes(json['uniq_id'].split('-').pop())) {
                // Якщо оновлювана опція є активною, додаємо головній кнопці Grid її клас
                var $gridHtml = $(json['product_html']).addClass('main-cart-btn-' + prod_id);
                $('.main-cart-btn-' + prod_id).replaceWith($gridHtml);
            }

            $('#product-' + prod_id + ' .error-message').remove();

            if (json['error'] !== "") {
              let $error_target = $('#product-' + prod_id + ' .cart-btns-' + json['uniq_id']);
              if ($error_target.length === 0) {
                $error_target = $('#product-' + prod_id + ' .cart-right-btns');
              }
              $error_target.before('<span class="error-message">' + json['error'] + '</span>');
            }
          }

          // Оновлюємо боковий кошик (без доставки)
          if (view_cart_right) {
            let temp_block = $('<div style="display: none;"></div>');
            temp_block.load('index.php?route=extension/module/custom/total/ajax_total', function () {
              $('#cartview-content .cart-total-bl').html(temp_block.html());
            });
          }

          setTimeout(function () {
            $('#product-' + prod_id + ' .error-message').remove();
          }, 2000);

          $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
        }
      }
    });
  },

  ga4AddToCart: function (products) {
    // Используем глобальную функцию sendGA4Event (если она существует)
    if (typeof window.sendGA4Event === 'function') {
      // Преобразуем объект в массив (если нужно)
      const productsArray = Object.values(products || {});

      if (productsArray.length === 0) {
        return;
      }

      const items = productsArray.map(product => ({
        item_name: product.product_name || "",
        item_id: product.product_id || "",
        price: parseFloat(product.product_price) || 0,
        item_brand: product.product_brand || "not set",
        item_category: product.product_category || "not set",
        item_variant: product.product_variant || "not set",
        quantity: parseInt(product.product_quantity) || 1
      }));

      const ecommerceData = {
        currency: "UAH",
        value: items.reduce((total, item) => total + (item.price * item.quantity), 0),
        items: items
      };

      // Используем глобальную функцию
      window.sendGA4Event("add_to_cart", ecommerceData);

    } else {
      // Если sendGA4Event еще не загружена, используем очередь
      window.gaQueue = window.gaQueue || [];

      const productsArray = Object.values(products || {});

      if (productsArray.length === 0) {
        return;
      }

      const items = productsArray.map(product => ({
        item_name: product.product_name || "",
        item_id: product.product_id || "",
        price: parseFloat(product.product_price) || 0,
        item_brand: product.product_brand || "not set",
        item_category: product.product_category || "not set",
        item_variant: product.product_variant || "not set",
        quantity: parseInt(product.product_quantity) || 1
      }));

      window.gaQueue.push({
        eventName: "add_to_cart",
        ecommerceData: {
          currency: "UAH",
          value: items.reduce((total, item) => total + (item.price * item.quantity), 0),
          items: items
        }
      });
    }
  },

  update: function (prod_id, cart_id, event, opts) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/update',
      type: 'post',
      data: 'product_id=' + prod_id + '&event=' + event + '&cart_id=' + cart_id + '&' + opts.serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
        $('#product-' + prod_id + ' .product-cart-block').addClass('custom_lock');
      },
      success: function (json) {

        $('#product-' + prod_id + ' .product-cart-block').removeClass('custom_lock');

        if (typeof custom_block !== 'undefined' && $('#checkout-checkout').length) {
          if (json['order_by_store'] === 0 && json['split_order_store'] === 0) {
            custom_block.render('cart');
          }

          // Оновлюємо основну сторінку
          custom_block.render('total');
          custom_block.render_order('shipping', 0);
          custom_block.render('button');
          custom_block.render('orders');

          if (json['empty'] !== "") {
            $('#checkout-checkout').html('').html(json['empty']);
          }
        }

        // Оновлюємо боковий кошик окремо
        if (view_cart_right) {
          let temp_block = $('<div style="display: none;"></div>');
          temp_block.load('index.php?route=extension/module/custom/total/ajax_total', function () {
            $('#cartview-content .cart-total-bl').html(temp_block.html());
          });
        }

        if (json['total'] > 0) {
          custom_cart.ga4AddToCart(json['ecommerce_products']);
        }

        var $newHtml = $(json['html']);
        
        // Оновлюємо всі кнопки цієї опції на сторінці (в списку та в сітці)
        $('.cart-btns-' + json['uniq_id']).replaceWith($newHtml);

        // СИНХРОНІЗАЦІЯ З ГОЛОВНОЮ КНОПКОЮ (Grid)
        var $activeRadio = $('input[name="option[' + prod_id + ']"]:checked');
        if ($activeRadio.length && $activeRadio.val().includes(json['uniq_id'].split('-').pop())) {
             // Якщо оновлювана опція є активною, додаємо головній кнопці Grid її клас
             var $gridHtml = $(json['html']).addClass('main-cart-btn-' + prod_id);
             $('.main-cart-btn-' + prod_id).replaceWith($gridHtml);
        }

        $('#product-' + prod_id + ' .error-message').remove();
        if (json['error'] !== "") {
          let $error_target = $('#product-' + prod_id + ' .cart-btns-' + json['uniq_id']);
          if ($error_target.length === 0) {
            $error_target = $('#product-' + prod_id + ' .cart-right-btns');
          }
          $error_target.before('<span class="error-message">' + json['error'] + '</span>');

          setTimeout(function () {
            $('#product-' + prod_id + ' .error-message').remove();
          }, 3000);

        } else {
          if (event === "plus") {
            $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i>Додано до кошика ' + json['prod'] + ' ' + json['opt'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          } else if (event === "minus") {
            $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i>Видалено з кошика ' + json['prod'] + ' ' + json['opt'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          } else {
            $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i>Змінено кількість ' + json['prod'] + ' ' + json['opt'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          }
          // Удаляем через 2 секунды
          setTimeout(function () {
            $('.alert-success').remove();
          }, 1500);
        }

        $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
      }
    });
  },

  remove: function (prod_id, cart_id) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/remove',
      type: 'post',
      data: 'product_id=' + prod_id + '&cart_id=' + cart_id + '&event=remove',
      dataType: 'json',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
        if (view_cart_right) {
          $('#cartview-content').addClass('custom_lock');
        } else {
          $('#product-' + prod_id + ' .product-cart-block').addClass('custom_lock');
        }
      },
      success: function (json) {

        if (typeof custom_block !== 'undefined' && $('#checkout-checkout').length) {
          if (json['empty'] !== "") {
            $('#cart-header-actions').html('');
            $('#checkout-checkout').html('').html(json['empty']);
          } else {
            if (json['order_org'] === 0) {
              custom_block.render('cart');
            } else {

              if (json['order_org'] > 0) {
                custom_block.render('orders_org');
              } else {
                if (json['order_by_store'] === 0 && json['split_order_store'] === 0) {
                  custom_block.render('cart');
                } else {
                  custom_block.render('orders');
                }
              }
            }

            custom_block.render('total');
            custom_block.render_order('shipping', 0);
            custom_block.render('button');
          }
        }

        $('#product-' + prod_id + ' .product-cart-block').removeClass('custom_lock');

        if (view_cart_right) {
          //console.log("view cart right");
          $('.cart-fix-list-products').find('#product-cart-' + cart_id).remove();
          $('#cartview-content').removeClass('custom_lock');

          if (json['empty'] !== "") {
            $('#cart-header-actions').html('');
            $('.cart-fix-block').html('').html(json['empty']);
            $('.cart-fix-total').html('');
          } else {
            // Находим все элементы с классом has-scroll
            const hasScrollElements = document.querySelectorAll('.has-scroll');

            // Перебираем каждый элемент
            hasScrollElements.forEach(function (hasScrollElement) {
              // Проверяем, есть ли внутри элемента блоки с классом cart-line
              const cartLineElements = hasScrollElement.querySelectorAll('.cart-line');

              // Если cart-line элементов нет, удаляем has-scroll элемент
              if (cartLineElements.length === 0) {
                hasScrollElement.remove();
                //console.log('Удален has-scroll элемент, так как он не содержит cart-line');
              }
            });
          }

          setTimeout(function () {
            var $newHtml = $(json['product_html']);
            
            // Оновлюємо всі кнопки цієї опції на сторінці (в списку та в сітці)
            $('.cart-btns-' + json['uniq_id']).replaceWith($newHtml);

            // СИНХРОНІЗАЦІЯ З ГОЛОВНОЮ КНОПКОЮ (Grid)
            var $activeRadio = $('input[name="option[' + prod_id + ']"]:checked');
            if ($activeRadio.length && $activeRadio.val().includes(json['uniq_id'].split('-').pop())) {
                // Якщо оновлювана опція є активною, додаємо головній кнопці Grid її клас
                var $gridHtml = $(json['product_html']).addClass('main-cart-btn-' + prod_id);
                $('.main-cart-btn-' + prod_id).replaceWith($gridHtml);
            }
          }, 300);
        } else {
          setTimeout(function () {
            var $newHtml = $(json['product_html']);
            
            // Оновлюємо всі кнопки цієї опції на сторінці (в списку та в сітці)
            $('.cart-btns-' + json['uniq_id']).replaceWith($newHtml);

            // СИНХРОНІЗАЦІЯ З ГОЛОВНОЮ КНОПКОЮ (Grid)
            var $activeRadio = $('input[name="option[' + prod_id + ']"]:checked');
            if ($activeRadio.length && $activeRadio.val().includes(json['uniq_id'].split('-').pop())) {
                // Якщо оновлювана опція є активною, додаємо головній кнопці Grid її клас
                var $gridHtml = $(json['product_html']).addClass('main-cart-btn-' + prod_id);
                $('.main-cart-btn-' + prod_id).replaceWith($gridHtml);
            }
          }, 300);
        }

        $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
      }
    });
  }
  ,

  clear: function () {
    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/clear',
      type: 'post',
      dataType: 'json',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
        $('#cartview-content').addClass('custom_lock');
      },
      success: function (json) {
        if (json['empty']) {
          $('#checkout-checkout').html('');
          setTimeout(function () {
            location.reload();
          }, 2000);

        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        $('#cartview-content').removeClass('custom_lock');
        //console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  }
  ,

  fastorder: function () {
    $.ajax({
      type: 'get',
      url: 'index.php?route=extension/module/ocfastordercart',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
        custom_cart.hide();
      },
      success: function (data) {
        $('html body').append('<div id="modal-quickorder" class="modal fade" role="dialog">' + data + '</div>');
        $('#modal-quickorder').modal('show');

        $(document).on('hide.bs.modal', '#modal-quickorder.modal.fade', function () {
          $('#modal-quickorder').remove();
        });
      }
    });
  },

  quickorder_confirm_checkout: function () {

    $('#quickorder_url').val(window.location.href);
    var success = 'false';
    $.ajax({
      url: 'index.php?route=extension/module/ocfastordercart/addFastOrder',
      type: 'post',
      data: $('#fastorder_data').serialize() + '&action=send',
      dataType: 'json',
      beforeSend: function () {
        $('#modal-quickorder .modal-body').prepend('<div class="masked_bg"></div><div class="loading_masked"></div>');
      },
      success: function (json) {
        $('.alert').remove();
        $('#modal-quickorder .form-control').removeClass('error_input');
        $('.alert.ch-alert-danger').remove();
        var error_qo = '';
        if (json['error']) {
          if (json['error']['name_fastorder']) {
            $('#modal-quickorder #contact-name').addClass('error_input');
            error_qo += '<div class="ch-error-text">' + json['error']['name_fastorder'] + '</div>';
          }
          if (json['error']['phone']) {
            $('#modal-quickorder #contact-phone').addClass('error_input');
            error_qo += '<div class="ch-error-text">' + json['error']['phone'] + '</div>';
          }
          if (json['error']['comment_buyer']) {
            $('#modal-quickorder #contact-comment').addClass('error_input');
            error_qo += '<div class="ch-error-text">' + json['error']['comment_buyer'] + '</div>';
          }
          if (json['error']['email_error']) {
            $('#modal-quickorder #contact-email').addClass('error_input');
            error_qo += '<div class="ch-error-text">' + json['error']['email_error'] + '</div>';
          }
          if (json['error']['error_agree']) {
            error_qo += '<div class="ch-error-text">' + json['error']['error_agree'] + '</div>';
          }
          $('body').append('<div class="alert ch-alert-danger mh-100"><img class="success-icon" alt="success-icon" src="catalog/view/theme/chameleon/image/warning-icon.svg"><div class="text-modal-block">' + error_qo + '</div><button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }

        if (json['success']) {
          $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');

          $('#modal-quickorder').modal('hide');

          html = '<div id="modal-addquickorder" class="modal fade">';
          html += '  <div class="modal-dialog">';
          html += '    <div class="modal-content ch-modal-success">';
          html += '      <div class="modal-body"><img class="success-icon" alt="success-icon" src="catalog/view/theme/chameleon/image/success-icon.svg"> <div class="text-modal-block">' + json['success'] + '</div><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button></div>';
          html += '    </div>';
          html += '  </div>';
          html += '</div>';

          $('body').append(html);
          setTimeout(function () {
            $('#modal-addquickorder').modal('show');
          }, 500);

          $(document).on('hide.bs.modal', '#modal-addquickorder.modal.fade', function () {
            $('#modal-addquickorder').remove();
          });
        }
      }

    });
  },

  update_order: function (prod_id, cart_id, order_id, event, opts) {
    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/update_order',
      type: 'post',
      data: 'product_id=' + prod_id + '&event=' + event + '&cart_id=' + cart_id + '&order_id=' + order_id + '&' + opts.serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
      },
      success: function (json) {

        if (json['empty'] !== "") {
          $('#checkout-checkout').html('').html(json['empty']);
        } else {
          if (json['order_org'] === 0) {
            custom_block.render('cart');
            //console.log("order_org - 0");
          } else {

            if (json['order_org'] > 0) {

              //console.log("order_org > 0");

              custom_block.render('orders_org');

            } else {
              if (json['order_by_store'] === 0 && json['split_order_store'] === 0) {
                custom_block.render('cart');

                //console.log("cart");
              } else {
                custom_block.render('orders');

                //console.log("cart def");
              }
            }
          }

          custom_block.render('total');
          custom_block.render_order('shipping', order_id);
          custom_block.render('button');

          // Добавляем сообщение перед блоком с кнопками
          $('#product-' + order_id + '-' + prod_id + ' .error-message').remove();
          if (json['error'] !== "") {
            let $error_target = $('#product-' + order_id + '-' + prod_id + ' .cart-btns-' + json['uniq_id']);
            if ($error_target.length === 0) {
              $error_target = $('#product-' + order_id + '-' + prod_id + ' .cart-right-btns');
            }
            $error_target.before('<span class="error-message">' + json['error'] + '</span>');
          }

          $('#product-' + order_id + '-' + prod_id + ' .cart-btns-' + json['uniq_id']).html("");
          $('#product-' + order_id + '-' + prod_id + ' .cart-btns-' + json['uniq_id']).html(json['html']);

          $('#product-' + order_id + '-' + prod_id + ' .cart-price-total').html();
          $('#product-' + order_id + '-' + prod_id + ' .cart-price-total').html(json['html_price_total']);

          setTimeout(function () {
            $('#product-' + order_id + '-' + prod_id + ' .error-message').remove();
          }, 3000);
        }

        $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
      }
    });
  }
  ,

  remove_order: function (prod_id, cart_id, order_id) {

    var qty = $('#product-' + order_id + '-' + prod_id + ' input[name="quantity"]').val();

    $.ajax({
      url: 'index.php?route=extension/module/custom/cart/remove_order',
      type: 'post',
      data: 'product_id=' + prod_id + '&cart_id=' + cart_id + '&order_id=' + order_id + '&qty=' + qty + '&event=remove',
      dataType: 'json',
      beforeSend: function () {
        $('.alert').remove();
        $('[role="tooltip"]').remove();
      },
      success: function (json) {

        document.getElementById('product-' + json['order_id'] + '-' + json['product_id']).remove();

        custom_cart.removeOrderIfEmpty(json['order_id']);

        custom_block.render('orders');
        custom_block.render('total');
        custom_block.render_order('shipping', json['order_id']);
        custom_block.render('button');

        if (json['empty'] !== "") {
          $('#checkout-checkout').html('').html(json['empty']);
        }

        $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
      }
    });
  }
  ,

  removeOrderIfEmpty: function (warehouseId) {
    // Получаем элемент с id "order-{{ warehouse_id }}"
    const orderElement = document.getElementById(`order-${warehouseId}`);

    if (orderElement) {
      // Проверяем, есть ли дочерние элементы с id, начинающимся на "product-{{ warehouse_id }}"
      const productElements = orderElement.querySelectorAll(`[id^="product-${warehouseId}-"]`);

      // Если таких элементов нет, удаляем блок "order-{{ warehouse_id }}"
      if (productElements.length === 0) {
        orderElement.remove();
      }
    }
  }
  ,

  bindPopupStoreSelector: function () {
    $(document).on('change', '#popup-check-store input[type="radio"]', function () {
      let warehouse_id = $(this).val();
      console.log('Выбранный склад:', warehouse_id);

      const modal = $('#modal-popup');
      const prod_id = modal.data('prod_id');
      const event = modal.data('event');
      const cart_id = modal.data('cart_id');
      const opts = modal.data('opts');

      $.ajax({
        url: 'index.php?route=extension/module/custom/cart/update',
        type: 'post',
        data: 'warehouse_id=' + warehouse_id + '&product_id=' + prod_id + '&event=' + event + '&cart_id=' + cart_id + '&' + opts.serialize(),
        dataType: 'json',
        beforeSend: function () {
          $('.alert').remove();
          $('[role="tooltip"]').remove();
          if (view_cart_right) {
            $('#cartview-content').addClass('custom_lock');
          } else {
            $('#product-' + prod_id + ' .product-cart-block').addClass('custom_lock');
          }
        },
        success: function (json) {
          if (typeof custom_block !== 'undefined') {
            if (json['order_by_store'] === 0 && json['split_order_store'] === 0) {
              custom_block.render('cart');
            }

            setTimeout(function () {
              custom_block.render('total');
              custom_block.render_order('shipping', 0);
              custom_block.render('button');
              custom_block.render('orders');
            }, 100);

            if (json['empty'] !== "") {
              $('#checkout-checkout').html('').html(json['empty']);
            }
          }

          $('#product-' + prod_id + ' .product-cart-block').removeClass('custom_lock');

          if (view_cart_right) {
            if (json['cart_id'] === 0) {
              $('.cart-fix-list-products').find('#product-cart-' + cart_id).remove();
            } else {
              $('.cart-fix-list-products').find('#product-cart-' + cart_id + ' .cart-price-total').html(json['html_price_total']);
            }

            let tempContainer = $('<div style="display: none;"></div>');
            tempContainer.load('index.php?route=extension/module/custom/total/ajax_total', function () {
              $('#cartview-content .cart-total-bl').html(tempContainer.html());
            });

            $('#cartview-content').removeClass('custom_lock');

            if (json['empty'] !== "") {
              $('.cart-fix-block').html('').html(json['empty']);
              $('.cart-fix-total').html('');
            }

            $('#product-' + prod_id + ' .cart-btns-' + json['uniq_id']).replaceWith(json['html']);
          } else {
            $('#product-' + prod_id + ' .cart-btns-' + json['uniq_id']).replaceWith(json['html']);
          }

          $('#product-' + prod_id + ' .error-message').remove();
          if (json['error'] !== "") {
            let $error_target = $('#product-' + prod_id + ' .cart-btns-' + json['uniq_id']);
            if ($error_target.length === 0) {
              $error_target = $('#product-' + prod_id + ' .cart-right-btns');
            }
            $error_target.before('<span class="error-message">' + json['error'] + '</span>');
          }

          setTimeout(function () {
            $('#product-' + prod_id + ' .error-message').remove();
          }, 3000);

          $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
        }
      });

      // Закрываем модальное окно
      $('#modal-popup').modal('hide');
    });
  }
}
