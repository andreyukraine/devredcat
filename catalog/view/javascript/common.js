function toggleAddressComment(el) {
  var $el = $(el);
  var $wrapper = $el.closest('.address-comment-wrapper');
  if ($wrapper.length) {
    $wrapper.find('.address-comment-textarea').toggle();
  } else {
    // fallback
    $el.next('.address-comment-textarea').toggle();
  }
}

function getCookie(name) {
  var matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
  return matches ? decodeURIComponent(matches[1]) : 'undefined';
}

function toggleWishlist(productId, is_in_wishlist) {
  if (parseInt(is_in_wishlist) > 0) {
    wishlist.remove(productId, false);
  } else {
    wishlist.add(productId);
  }
}

function getURLVar(key) {
  var value = [];

  var query = String(document.location).split('?');

  if (query[1]) {
    var part = query[1].split('&');

    for (i = 0; i < part.length; i++) {
      var data = part[i].split('=');

      if (data[0] && data[1]) {
        value[data[0]] = data[1];
      }
    }

    if (value[key]) {
      return value[key];
    } else {
      return '';
    }
  }
}


function isEmpty(el) {
  return !$.trim(el.html())
}

$(document).ajaxStop(function () {
  function isEmpty(el) {
    return !$.trim(el.html())
  }

  if (!isEmpty($('#product'))) {
    $('#product .option-container').addClass('has-option');
  }
});

window.notify = {
  open: function (btn) {
    var $btn = $(btn);
    var prod_id = $btn.data('product');
    var opt = $btn.data('opt');

    console.log("log - prod:" + prod_id + ", opt:" + opt);

    $.ajax({
      url: 'index.php?route=extension/module/ocnotifyproduct',
      type: 'post',
      data: '&product_id=' + prod_id + '&opt=' + opt,
      dataType: 'json',
      beforeSend: function () {
      },
      success: function (json) {
        if (json['error'] !== ""){
          $('body').before('<div class="alert alert-danger alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }else {
          $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['msg'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }
        setTimeout(function () {
          $('.alert-dismissible').remove();
        }, 3000);
      }
    });
  }
}


window.ocmenuview = {

  close: function () {
    ocmenuview.hide();
  },

  open: function () {
    ocmenuview_top.hide();
    ocmenuview.view();
  },

  view: function () {
    $('#menuview-bg-block').show();
    $('#menuview-content').addClass('active');
    document.body.classList.add('body-no-scroll');
    $('#ajax-lock-block').show();

    // Додаємо обробник кліку для ajax-lock-block
    $('#ajax-lock-block').off('click').on('click', function() {
      ocmenuview.close();
    });
  },

  hide: function () {
    $('#menuview-bg-block').hide();
    $('#menuview-content').removeClass('active');
    document.body.classList.remove('body-no-scroll');
    $('#ajax-lock-block').hide();
  },

};

window.ocmenuview_top = {
  _currentType: null,
  _loading: false,

  _getUrl: function (type) {
    switch (type) {
      case 'phone':
        return 'index.php?route=common/header/info';
      case 'main':
        return 'index.php?route=common/menu/menuTopMain';
      default:
        return null;
    }
  },

  close: function () {
    this.hide();
  },

  open: function () {
    this.view();
  },

  view: function () {
    var $c = $('#menuview-top-content');
    var $header = $('header');
    var headerHeight = $header.outerHeight();
    var headerOffset = $header.offset().top;

    // позиціонуємо під хедером
    $c.css({ top: (headerOffset + headerHeight) + 'px' });

    document.body.classList.add('body-no-scroll');
    $header.css({ 'z-index': 10001 }); // Трохи вище ніж lock-block (10000)

    $('#ajax-lock-block').show().off('click').on('click', () => this.close());
    $('#menuview-top-content').show().off('click').on('click', () => this.close());


    if (!$c.hasClass('active')) {
      $c.addClass('active');
    }
  },

  hide: function () {
    var $c = $('#menuview-top-content');
    $c.removeClass('active');
    
    setTimeout(function() {
      if (!$c.hasClass('active')) {
        $c.empty();
      }
    }, 300);

    document.body.classList.remove('body-no-scroll');
    $('header').css('z-index', '');
    $('#ajax-lock-block').hide();
    this._currentType = null;
  },

  toggle: function (type) {
    var $c = $('#menuview-top-content');
    var isOpen = $c.hasClass('active');
    var same = this._currentType === type;
    var url = this._getUrl(type);

    if (!url) return;

    if (isOpen && same) {
      this.hide();
      return;
    }

    if (this._loading) return;
    this._loading = true;
    this._currentType = type;

    // Додаємо напівпрозорість під час завантаження нового контенту, щоб уникнути різкого миготіння
    if (isOpen) {
      $c.css('opacity', '0.6');
    }

    $c.load(url, () => {
      $c.css('opacity', '');
      this.view();
      $('#ajax-lock-block').show();
      this._loading = false;
    });
  }
};

window.custom_callback = {
  view: function () {
    $('.callback-container').fadeIn(200).addClass("open");
  },
  open: function () {
    $('body').append('<div class="callback-container"></div>');
    $('.callback-container').html("").load('index.php?route=extension/module/occallback/index', function () {
      custom_callback.view();
    });
  },
  close: function () {
    custom_callback.hide();
  },
  hide: function () {
    $('.callback-container').fadeOut(200).removeClass("open").remove();
  }
}

window.custom_popup = {
  view: function () {
    $('.popup-container').fadeIn(200).addClass("open");
  },
  open: function (html_body) {
    $('body').append('<div class="popup-container"></div>');
    $('.popup-container').html("").html(html_body);
    custom_popup.view();
  },
  close: function () {
    custom_popup.hide();
    // Создаем новую дату (текущий момент)
    const expirationDate = new Date();

    // Добавляем 1 год к текущей дате
    expirationDate.setFullYear(expirationDate.getFullYear() + 1);

    // Устанавливаем cookie с именем 'ocpopup' и сроком действия 1 год
    document.cookie = `ocpopup=1; path=/; expires=${expirationDate.toUTCString()}`;
  },
  hide: function () {
    $('.popup-container').fadeOut(200).removeClass("open").remove();
  }
}

window.custom_share = {
  'view': function () {
    $('#share-bg-block').show();
    document.body.classList.add('body-no-scroll');
    $('#ajax-lock-block').show();
    $('#share-content').addClass('active');

    // Додаємо обробник кліку для ajax-lock-block
    $('#ajax-lock-block').off('click').on('click', function() {
      custom_share.close();
    });
  },
  'open': function () {
    $('body').append('<div class="share-container"></div>');
    $('.share-container').html("").load('index.php?route=extension/module/ocshare/index', function () {
      custom_share.view();
    });
  },
  'close': function () {
    custom_share.hide();
    document.body.classList.remove('body-no-scroll');
  },
  'hide': function () {
    $('#share-content').removeClass('active');
    $('#share-bg-block').hide();
    $('#ajax-lock-block').hide();
    $('.share-container').remove();

  }
}

window.ocquickview = {
  view: function () {
    $('body').append('<div id="quickview-container"></div>');
    $('#lightbox-container').show();
    document.body.classList.add('body-no-scroll');
    $('#quickview-modal').fadeIn(200);
  },

  close: function () {
    $('#lightbox-container').hide();
    $('#quickview-modal').fadeOut(200);
    $('#quickview-container').remove();
    document.body.classList.remove('body-no-scroll');
  },

  open: function (url) {

    if (url.search('route=product/product') != -1) {
      url = url.replace('route=product/product', 'route=product/ocquickview');
    } else {
      url = 'index.php?route=product/ocquickview/seoview&ourl=' + url;
    }

    $.ajax({
      url: url,
      type: 'get',
      beforeSend: function () {
      },
      success: function (json) {
        if (json['success'] === true) {
          ocquickview.view();
          $('#quickview-container').html('').append(json['html']);
          $('body').trigger('contentUpdated');
        }
      },
      complete: function () {
      }
    });
  }
};

window.custom_review = {
  'view': function () {
    $('body').append('<div class="review-container"></div>');
    $('.review-container').addClass("open");
    document.body.classList.add('body-no-scroll');
  },
  'hide': function () {
    $('.review-container').removeClass("open");
    $('#review-content').fadeOut(200);
    document.body.classList.remove('body-no-scroll');
    $('.review-container').remove();
  },
  'add': function (prod_id) {
    $.ajax({
      url: 'index.php?route=product/product/add_review',
      type: 'post',
      data: '&product_id=' + prod_id,
      dataType: 'json',
      beforeSend: function () {
      },
      success: function (json) {
        custom_review.view();
        $('.review-container').html(json);
      }
    });
  },
  'send': function () {
    $.ajax({
      url: 'index.php?route=product/product/write',
      type: 'post',
      data: $('#form-review').serialize(),
      dataType: 'json',
      beforeSend: function () {
        // Очищаємо всі попередні помилки
        $('.for-error').html('');
        $('.form-control').removeClass('error_style');
      },
      success: function (json) {

        if (json['error']) {

          //console.log(json['error']);

          // Виводимо помилки для кожного поля
          $.each(json['error'], function(field, errorText) {
            const $input = $('[name="' + field + '"]');
            $input.addClass('error_style');

            // Шукаємо відповідний блок для помилки
            const $errorField = $input.closest('.form-group').find('.error-field');
            if ($errorField.length) {
              $errorField.html(errorText);
            } else {
              // Якщо блок для помилки не знайдено (наприклад, для captcha)
              $('.error-' + field).html(errorText);
              $('.error-' + field).addClass('text-danger');
            }
          });
        }

        if (json['success']) {
          $('#form-review').html("").html(json['success']);
          $('input[name=\'name\']').val('');
          $('textarea[name=\'text\']').val('');
          $('input[name=\'rating\']:checked').prop('checked', false);
        }
      }
    });
  },
}

var view_filter_right = false;

const custom_customer_menu = {
  'view': function () {
    $('#filter-bg-block').show();
    $('.bl-account-menu-fixed').addClass('active');

    $('#ajax-lock-block').show();
  },
  'close': function () {
    custom_customer_menu.hide();
  },
  'open': function () {
    custom_customer_menu.view();
  },
  'hide': function () {
    $('#filter-bg-block').hide();
    $('.filter-load-img').hide();
    $('.bl-account-menu-fixed').removeClass('active');
    $('#ajax-lock-block').hide();
  },
}

const custom_filter = {

  'view': function () {
    $('#filter-bg-block').show();
    $('#filter-content').addClass('active');
    document.body.classList.add('body-no-scroll');
    $('#ajax-lock-block').show();
    view_filter_right = true;
    
    $('#ajax-lock-block').off('click').on('click', function () {
      custom_filter.close();
    });
  },
  'close': function () {
    custom_filter.hide();
  },
  'open': function () {
    custom_filter.view();
  },
  'hide': function () {
    view_filter_right = false;
    $('#filter-bg-block').hide();
    $('.filter-load-img').hide();
    $('#filter-content').removeClass('active');
    document.body.classList.remove('body-no-scroll');
    $('#ajax-lock-block').hide();
  },
}

function initializeCountdown(endDate, elementId) {
  var $countdownElement = $('#' + elementId);
  // Запасний варіант — використання локального часу
  $countdownElement.countdown({
    until: new Date(endDate.replace(' ', 'T')),
    labels: ['Роки', 'Місяці', 'Тижні', 'Дні', 'Години', 'Хвилини', 'Секунди'],
    labels1: ['Рік', 'Місяць', 'Тиждень', 'День', 'Година', 'Хвилина', 'Секунда'],
    format: 'DHMS'
  });

  // Отримуємо час сервера перед запуском таймера
  // fetch('index.php?route=common/countdown/server_time2')
  //   .then(response => response.json())
  //   .then(data => {
  //     const serverTime = new Date(data.server_time);
  //     const targetDate = new Date(endDate.replace(' ', 'T'));
  //
  //     // Корекція часу, якщо потрібно
  //     const timeDiff = new Date() - serverTime; // Різниця між локальним часом і серверним
  //
  //     if ($countdownElement.data('countdown')) {
  //       $countdownElement.countdown('destroy');
  //     }
  //
  //     $countdownElement.empty().countdown({
  //       until: targetDate,
  //       labels: ['Роки', 'Місяці', 'Тижні', 'Дні', 'Години', 'Хвилини', 'Секунди'],
  //       labels1: ['Рік', 'Місяць', 'Тиждень', 'День', 'Година', 'Хвилина', 'Секунда'],
  //       format: 'DHMS'
  //     });
  //   })
  //   .catch(error => {
  //     console.error("Помилка отримання часу сервера:", error);
  //     //Запасний варіант — використання локального часу
  //     $countdownElement.countdown({
  //       until: new Date(endDate.replace(' ', 'T')),
  //       labels: ['Роки', 'Місяці', 'Тижні', 'Дні', 'Години', 'Хвилини', 'Секунди'],
  //       labels1: ['Рік', 'Місяць', 'Тиждень', 'День', 'Година', 'Хвилина', 'Секунда'],
  //       format: 'DHMS'
  //     });
  //   });
}

$(document).ready(function () {

  $('.product-slider').hover(
    function() {
      $(this).closest('.product-grid').css({
        'overflow': 'visible',
        'z-index': '10',
        'box-shadow': '0 2px 4px #ccc, 0 8px 16px #ccc'
      });
    },
    function() {
      $(this).closest('.product-grid').css({
        'overflow': '',
        'z-index': '',
        'box-shadow': ''
      });
    }
  );

  $('.toggle-switch-menu input').on('change', function () {
    var $checkbox = $(this);
    var paramName = $checkbox.data('param');
    var checked = $checkbox.is(':checked') ? 1 : 0;

    $.ajax({
      url: 'index.php?route=common/menu/change_menu',
      method: 'POST',
      data: {
        param: paramName,
        switch_menu: checked
      },
      beforeSend: function () {
        $(' #bl-menu-type').addClass('custom_lock');
      },
      success: function (response) {
        if (response.status){
          $('#type-text').html(response.text);
          $('#bl-toogle').removeClass();
          $('#bl-toogle').addClass("toggle-container " + response.type_class);
          $('#type-text-check').html(response.type_check);
          $('#bl-menu-type').html(response.html);
        }
        $(' #bl-menu-type').removeClass('custom_lock');
      },
      error: function (xhr, status, error) {
        $(' #bl-menu-type').removeClass('custom_lock');
        console.error('Error:', status, error);
      }
    });
  });

  $('.sorter .dropdown-item').on('click', function (e) {
    e.preventDefault();
    var selectedText = $(this).text();
    var href = $(this).attr('href');
    
    // Отримуємо sort та order з data-атрибута (найнадійніший спосіб для SEO-урлів)
    var sortValue = $(this).data('sort-value');
    var sort = '';
    var order = '';
    
    if (sortValue) {
      var parts = sortValue.split('-');
      sort = parts[0];
      order = parts[1];
    } else {
      // Fallback на URLSearchParams якщо data-атрибут чомусь порожній
      var url = new URL(href, window.location.origin);
      sort = url.searchParams.get('sort');
      order = url.searchParams.get('order');
    }

    if (window.FilterApp && typeof window.FilterApp.handleSort === 'function') {
      // Оновлюємо UI сортування
      $('.sorter .dropdown-item').removeClass('active');
      $(this).addClass('active');
      $('.sorter .sort_val').text(selectedText);
      
      // Викликаємо React-метод
      window.FilterApp.handleSort(sort, order);
    } else {
      window.location.href = href;
    }
  });

  var cartId = 0;

  $('body').on('click', '.options-box', function () {
    var product_id = $(this).data('options-id');
    var contextSelector = '#product-' + product_id;

    // Виконуємо потрібні функції для конкретного продукту
    eventCheckBox(contextSelector, product_id);
    eventRadio(contextSelector, product_id);
    eventSelect(contextSelector, product_id);
    clickLi(contextSelector, product_id);
    eventLi(contextSelector, product_id);
  });

  function getPrice(contextSelector, product_id) {

    //var opts = $('.bl-detail-info #product-'+product_id+' input[type=\'text\'], .bl-detail-info #product-'+product_id+' input[type=\'hidden\'], .bl-detail-info #product-'+product_id+' input[id="qty-' + cart_id + '"],  .bl-detail-info #product-'+product_id+' input[type=\'radio\']:checked, .bl-detail-info #product-'+product_id+' input[type=\'checkbox\']:checked, .bl-detail-info #product-'+product_id+' select, .bl-detail-info #product-'+product_id+' textarea');

    var container = contextSelector;

    //Определяем, где искать элементы, в модальном окне или на основной странице
    var opts = $(container + ' input[type=\'text\'], ' +
      container + ' input[type=\'hidden\'], ' +
      container + ' input[id="qty-' + cartId + '"], ' +
      container + ' input[type=\'radio\']:checked, ' +
      container + ' input[type=\'checkbox\']:checked, ' +
      container + ' select, ' +
      container + ' textarea');

    // Преобразуем все числовые значения в строки
    var serializedOpts = opts.serializeArray().map(function (item) {
      if (!isNaN(item.value) && item.value !== '') {
        // Преобразуем числовое значение в строку
        item.value = String(item.value);
      }
      return item;
    });
    // Преобразуем массив обратно в строку для передачи через AJAX
    var serializedData = $.param(serializedOpts);

    $.ajax({
      url: 'index.php?route=extension/module/discount/getPrice',
      type: 'post',
      data: '&product_id=' + product_id + '&' + serializedData,
      dataType: 'json',
      beforeSend: function () {
        $(container + ' #prod-bl-qty-' + product_id).addClass('custom_lock');
        $(container + ' .stocks-' + product_id).addClass('custom_lock');
      },
      success: function (json) {

        $(container + ' .price-box-' + product_id).html("");
        $(container + ' .price-box-' + product_id).html(json.prices);

        $(container + ' .label-sale-' + product_id).html("");
        $(container + ' .label-sale-' + product_id).html(json.percent);

        $(container + ' .model-' + product_id).html("");
        $(container + ' .model-' + product_id).html(json.model);

        $(container + ' .sku-' + product_id).html("");
        $(container + ' .sku-' + product_id).html(json.sku);

        $(container + ' #prod-bl-qty-' + product_id).html("");
        $(container + ' #prod-bl-qty-' + product_id).html(json.btn_cart);

        console.log(json);

        //$(container + ' #prod-bl-qty-' + product_id + ' .cart-btns-' + json.uniq_id).html("").html(json.btn_cart);

        $(container + ' .stocks-' + product_id).html("");
        $(container + ' .stocks-' + product_id).html(json.stock);

        $(container + ' .product-thumb').removeClass("not-stock");

        //console.log(json.total_qty);

        if (json.total_qty === 0) {
          $(container + ' .product-thumb').addClass("not-stock");
        }


        if (json.date_end) {
          $(container + ' .bl-countdown').show();
          //$(contextSelector + ' .bl-countdown').html(json.bl_countdown_html);

          try {
            // Перезапуск лічильника з новою датою
            initializeCountdown(json.date_end, "countdown" + product_id);
          } catch (error) {
            console.error("Помилка в countdown:", error);
          }
        } else {
          $(container + ' .bl-countdown').hide();
        }

        $(container + ' #prod-bl-qty-' + product_id).removeClass('custom_lock');
        $(container + ' .stocks-' + product_id).removeClass('custom_lock');

        if (json.description !== "") {
          $(container + ' .description-product').html("").html(json.description);
        }

        if (json.composition !== "") {
          $(container + ' .composition-product').html("").html(json.composition);
        }
      }
    });
  }

  function eventCheckBox(contextSelector, product_id) {
    var inputs = document.querySelectorAll(contextSelector + ' .option-block input[type="checkbox"]');
    // Добавляем обработчик события 'change' к каждому элементу
    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        getPrice(contextSelector, product_id);
        $(contextSelector + ' .slider-nav-' + product_id + ' .product-image-options-' + input.value).trigger('click');
      });
    });
  }

  function eventRadio(contextSelector, product_id) {
    $(contextSelector + ' .option-block input[type="radio"]').off('click').on('click', function (event) {
      var currentInput = event.currentTarget;
      var productOption = currentInput.dataset.productOption;
      var name = currentInput.name;
      var value = currentInput.value;
      cartId = currentInput.dataset.cart;

      // 1. Сначала обновляем все соответствующие радио-кнопки на странице
      $('input[name="' + name + '"][value="' + value + '"][data-product="' + product_id + '"]').each(function () {
        // Пропускаем текущий элемент, так как его состояние уже правильное
        if (this !== currentInput) {
          $(this).prop('checked', true).attr('checked', 'checked');
          $(this).closest('label').find('.radio-control').addClass('check');
        }
      });

      // 2. Сбрасываем все другие радио-кнопки в той же группе
      $('input[name="' + name + '"][data-product="' + product_id + '"]').not('[value="' + value + '"]').each(function () {
        $(this).prop('checked', false).removeAttr('checked');
        $(this).closest('label').find('.radio-control').removeClass('check');
      });

      // 3. Обеспечиваем, что текущий элемент отмечен (на случай, если он был в другой группе)
      $(currentInput).prop('checked', true).attr('checked', 'checked');
      $(currentInput).closest('label').find('.radio-control').addClass('check');

      // Оновити ціну і слайдер
      getPrice(contextSelector, product_id);

      var slideIndex = $(contextSelector + ' .product-image-options-' + value).data('pos');
      if (slideIndex !== undefined) {
        $(contextSelector + ' .slider-for-' + product_id).slick('slickGoTo', slideIndex);
      }
    });
  }

  function eventLi(contextSelector, product_id) {
    var lis = document.querySelectorAll(contextSelector + ' .option-block li');
    // Добавляем обработчик события 'change' к каждому элементу
    lis.forEach(function (li) {
      li.addEventListener('click', function () {
        getPrice(contextSelector, product_id);
        $(contextSelector + ' .slider-nav-' + product_id + ' .product-image-options-' + li.value).trigger('click');
      });
    });
  }

  function clickLi(contextSelector, product_id) {
    // Находим все элементы li внутри списка с классом .ul-swatches-colors
    const swatchesOptions = document.querySelectorAll(contextSelector + ' .ul-swatches-colors .swatches-options');

    swatchesOptions.forEach(function (swatchesOption) {
      swatchesOption.addEventListener('click', function () {
        // Удаляем класс checked у всех элементов li
        swatchesOptions.forEach(option => option.classList.remove('checked'));

        // Добавляем класс checked только нажатому элементу li
        this.classList.add('checked');

        // Дополнительно: если нужно синхронизировать с формой или выполнить другие действия
        // Можно использовать data-атрибуты, например:
        const selectedOptionValueId = this.dataset.productOptionValueId;

        // Находим элемент select, связанный с текущим swatch
        const selectElement = document.querySelector(contextSelector + ' .form-control.option-swatches');

        if (selectElement) {
          // Устанавливаем выбранное значение в элементе select
          selectElement.value = selectedOptionValueId;

          // Удаляем атрибут checked у всех опций и добавляем к выбранной
          const options = selectElement.querySelectorAll('option');
          options.forEach(option => option.removeAttribute('checked'));

        }
      });
    });
  }

  function eventSelect(contextSelector, product_id) {
    // Получаем все элементы <select> в блоках .option-block
    var selects = document.querySelectorAll(contextSelector + ' .option-block select');
    // Добавляем обработчик события 'change' к каждому элементу
    selects.forEach(function (select) {
      select.addEventListener('change', function () {
        getPrice(contextSelector, product_id);
        $(contextSelector + ' .slider-nav-' + product_id + ' .product-image-options-' + select.value).trigger('click');
      });
    });
  }

  // Глобальный объект для функций хедера
  window.headerFunctions = {
    currentP: 0,
    stickyOffset: 0,
    headerHeight: 0,

    // Функция для обновления высоты хедера
    updateHeaderHeight: function () {
      this.headerHeight = $('header').outerHeight();
      this.stickyOffset = $('header').offset().top;
      // Обновляем позицию toolbar-products
      this.updateToolbarPosition();
    },

    // Функция для фиксации хедера
    updateHeaderFix: function() {
      var scrollP = $(window).scrollTop();

      // Проверяем, что stickyOffset и headerHeight вычислены
      if (this.stickyOffset === undefined || this.headerHeight === undefined) {
        this.updateHeaderHeight(); // Пересчитываем, если что-то пошло не так
      }
      this.currentP = scrollP;
    },

    // Функция для обновления позиции toolbar
    updateToolbarPosition: function() {
      var topVal = this.headerHeight + 'px';
      $('.toolbar-products').css('top', topVal);
      
      var $topBuy = $('#top-buy-product');
      if ($topBuy.length && !$topBuy.hasClass('is-mobile')) {
        $topBuy.css('top', topVal);
      }
    },

    // Инициализация
    init: function () {
      this.updateHeaderHeight();
      this.updateHeaderFix();
    }
  };

// Инициализация при загрузке документа
  $(document).ready(function () {
    window.headerFunctions.init();
  });

// Обновление при скролле
  $(window).scroll(function () {
    window.headerFunctions.updateHeaderHeight();
    window.headerFunctions.updateHeaderFix();
  });

// Обновление при изменении размера окна
  $(window).resize(function () {
    window.headerFunctions.updateHeaderHeight();
    window.headerFunctions.updateHeaderFix();
  });

  if (!isEmpty($('#product'))) {
    $('#product .option-container').addClass('has-option');
  }
  if (!isEmpty($('#product2'))) {
    $('#product2 .option-container').addClass('has-option');
  }

  // Currency
  $('#form-currency .currency-select').on('click', function (e) {
    e.preventDefault();

    $('#form-currency input[name=\'code\']').val($(this).attr('name'));

    $('#form-currency').submit();
  });

  // // Language
  // $('#form-language .language-select').on('click', function (e) {
  //   e.preventDefault();
  //
  //   $('#form-language input[name=\'code\']').val($(this).attr('name'));
  //
  //   $('#form-language').submit();
  // });

  $(document).on('click', '#form-language .language-select', function(e) {
    e.preventDefault();
    $('#form-language input[name=\'code\']').val($(this).attr('name'));
    $('#form-language').submit();
  });

  /* Search */
  $('#search input[name=\'search\']').parent().find('button').on('click', function () {
    var url = $('base').attr('href') + 'index.php?route=product/search';

    var value = $('header #search input[name=\'search\']').val();

    if (value) {
      url += '&search=' + encodeURIComponent(value);
    }

    location = url;
  });

  $('#search input[name=\'search\']').on('keydown', function (e) {
    if (e.keyCode == 13) {
      $('header #search input[name=\'search\']').parent().find('button').trigger('click');
    }
  });

  // Menu
  $('#menu .dropdown-menu').each(function () {
    var menu = $('#menu').offset();
    var dropdown = $(this).parent().offset();

    var i = (dropdown.left + $(this).outerWidth()) - (menu.left + $('#menu').outerWidth());

    if (i > 0) {
      $(this).css('margin-left', '-' + (i + 10) + 'px');
    }
  });

  // grid-style
  $('.grid-style .item').mouseover(function () {
    $(this).closest('.grid-style').addClass('active');
  });
  $('.grid-style .item').mouseout(function () {
    $(this).closest('.grid-style').removeClass('active');
  });

  // grid-style options hover image change
  $(document).on('mouseenter', '.options-box .option-item', function () {
    var $optionItem = $(this);
    var $optionsBox = $optionItem.closest('.options-box');
    var product_id = $optionsBox.data('options-id');
    var contextSelector = '#product-' + product_id;
    var $input = $optionItem.find('input[type="radio"]');
    var value = $input.val();

    if (value) {
      var slideIndex = $(contextSelector + ' .product-image-options-' + value).data('pos');
      if (slideIndex !== undefined) {
        $(contextSelector + ' .slider-for-' + product_id).slick('slickGoTo', slideIndex);
      }
    }
  });

  $(document).on('mouseleave', '.options-box', function () {
    var $optionsBox = $(this);
    var product_id = $optionsBox.data('options-id');
    var contextSelector = '#product-' + product_id;
    var $checkedInput = $optionsBox.find('input[type="radio"]:checked');
    var value = $checkedInput.val();

    if (value) {
      var slideIndex = $(contextSelector + ' .product-image-options-' + value).data('pos');
      if (slideIndex !== undefined) {
        $(contextSelector + ' .slider-for-' + product_id).slick('slickGoTo', slideIndex);
      }
    }
  });

  // Checkout
  $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function (e) {
    if (e.keyCode == 13) {
      $('#collapse-checkout-option #button-login').trigger('click');
    }
  });
});

var voucher = {
  'add': function () {

  },
  'remove': function (key) {
    $.ajax({
      url: 'index.php?route=checkout/cart/remove',
      type: 'post',
      data: 'key=' + key,
      dataType: 'json',
      beforeSend: function () {
        $('#cart-total').button('loading');
      },
      complete: function () {
        $('#cart-total').button('reset');
      },
      success: function (json) {
        // Need to set timeout otherwise it wont update the total
        setTimeout(function () {
          $('#cart').html('<span id="cart-total">' + json['total'] + '</span>');
        }, 100);

        if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
          location = 'index.php?route=checkout/cart';
        } else {
          $('#cart > ul').load('index.php?route=common/cart/info ul li');
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  }
}

window.custom_slider = {

  initializeSliderItem: function ($slider) {
    if (!$slider.length) return;

    // Знаходимо активний слайд
    var $activeSlide = $slider.find('.select[data-pos]');
    var initialSlideIndex = $activeSlide.length ? parseInt($activeSlide.attr('data-pos')) || 0 : 0;

    // Гарантируем, что слайдер видим перед инициализацией
    $slider.css({
      'opacity': '1',
      'visibility': 'visible'
    });

    // Знімаємо попередню ініціалізацію
    if ($slider.hasClass('slick-initialized')) {
      $slider.slick('unslick');
    }

    // Ініціалізація з додатковою перевіркою
    $slider.slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      speed: 500,
      arrows: false,
      fade: false,
      dots: false,
      initialSlide: initialSlideIndex
    }).on('init', function (event, slick) {
      //setTimeout(() => {
      //slick.slickGoTo(initialSlideIndex, true);
      //slick.refresh();

      // Форсируем пересчет позиций
      //$slider.find('.slick-track').css('transform', 'translate3d(0, 0, 0)');

      //}, 100);
    });
  }
}

window.wishlist = {
  'add': function (product_id) {
    $.ajax({
      url: 'index.php?route=account/wishlist/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function (json) {
        $('.alert-dismissible').remove();

        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          $('#wishlist').html('<span id="wishlist-total">' + json['total'] + '</span>');
          $('.btn-wishlist-' + product_id).attr('onclick', "wishlist.remove('" + product_id + "');")
          $('.btn-wishlist-' + product_id).addClass("is-check");
        } else {
          $('body').before('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }

        // Удаляем через 2 секунды
        setTimeout(function () {
          $('.alert-success').remove();
          $('.alert-danger').remove();
        }, 2000);

      },
      error: function (xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },
  'remove': function (product_id, reload) {
    $.ajax({
      url: 'index.php?route=account/wishlist/remove',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function (json) {
        $('.alert-dismissible').remove();

        if (json['redirect']) {
          location = json['redirect'];
        }

        if (json['success']) {
          $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          $('#wishlist').html('<span id="wishlist-total">' + json['total'] + '</span>');
          $('.btn-wishlist-' + product_id).attr('onclick', "wishlist.add('" + product_id + "');")
          $('.btn-wishlist-' + product_id).removeClass("is-check");
        } else {
          $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }

        if (reload) {
          location = 'index.php?route=account/wishlist';
        }

        // Удаляем через 2 секунды
        setTimeout(function () {
          $('.alert-success').remove();
          $('.alert-danger').remove();
        }, 2000);
      },
      error: function (xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  }
}

var compare = {
  'add': function (product_id) {
    $.ajax({
      url: 'index.php?route=product/compare/add',
      type: 'post',
      data: 'product_id=' + product_id,
      dataType: 'json',
      success: function (json) {
        $('.alert-dismissible').remove();

        if (json['success']) {
          $('body').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');

          $('#compare-total').html(json['total']);

          //$('html, body').animate({ scrollTop: 0 }, 'slow');
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  },
  'remove': function () {

  }
}

/* Agree to Terms */
$(document).delegate('.agree', 'click', function (e) {
  e.preventDefault();

  $('#modal-agree').remove();

  var element = this;

  $.ajax({
    url: $(element).attr('href'),
    type: 'get',
    dataType: 'html',
    success: function (data) {
      html = '<div id="modal-agree" class="modal">';
      html += '  <div class="modal-dialog">';
      html += '    <div class="modal-content">';
      html += '      <div class="modal-header">';
      html += '        <h3 class="modal-title text-24 bl-bold">' + $(element).text() + '</h3>';
      html += '        <div class="close" data-dismiss="modal" aria-hidden="true"><svg width="25px" height="25px" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path fill="#000" d="M764.288 214.592 512 466.88 259.712 214.592a31.936 31.936 0 0 0-45.12 45.12L466.752 512 214.528 764.224a31.936 31.936 0 1 0 45.12 45.184L512 557.184l252.288 252.288a31.936 31.936 0 0 0 45.12-45.12L557.12 512.064l252.288-252.352a31.936 31.936 0 1 0-45.12-45.184z"></path></svg></div>';
      html += '      </div>';
      html += '      <div class="modal-body">' + data + '</div>';
      html += '    </div>';
      html += '  </div>';
      html += '</div>';

      $('body').append(html);

      $('#modal-agree').modal('show');
    }
  });
});

$(document).delegate('.akcia-info', 'click', function (e) {
  e.preventDefault();

  var $link = $(this);

  // Якщо клік вже обробляється або модалка відкрита - ігноруємо
  if ($link.hasClass('loading')) {
    return;
  }

  // Блокуємо посилання
  $link.addClass('loading').css('pointer-events', 'none');

  $('#modal-agree').remove();

  $.ajax({
    url: $link.attr('href'),
    type: 'GET',
    dataType: 'html',
    success: function (data) {
      var $response = $(data);

      var modalTitle = $response.find('h1.akcia-name').text().trim();
      var blockContent = $response.find('#akcia-information').html();
      var contentToShow = blockContent || data;

      html = '<div id="modal-agree" class="modal">';
      html += '  <div class="modal-dialog">';
      html += '    <div class="modal-content">';
      html += '      <div class="modal-header">';
      html += '        <div class="close" data-dismiss="modal" aria-hidden="true"><svg width="20px" height="30px" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path fill="#000" d="M764.288 214.592 512 466.88 259.712 214.592a31.936 31.936 0 0 0-45.12 45.12L466.752 512 214.528 764.224a31.936 31.936 0 1 0 45.12 45.184L512 557.184l252.288 252.288a31.936 31.936 0 0 0 45.12-45.12L557.12 512.064l252.288-252.352a31.936 31.936 0 1 0-45.12-45.184z"></path></svg></div>';
      html += '        <h3 class="modal-title text-24 bl-bold">' + modalTitle + '</h3>';
      html += '      </div>';
      html += '      <div class="modal-body">' + contentToShow + '</div>';
      html += '    </div>';
      html += '  </div>';
      html += '</div>';
      $('body').append(html);

      // Відновлюємо посилання після закриття модалки
      $('#modal-agree').on('hidden.bs.modal', function () {
        $link.removeClass('loading').css('pointer-events', 'auto');
      });

      $('#modal-agree').modal('show');
    },
    error: function () {
      // Відновлюємо посилання у разі помилки
      $link.removeClass('loading').css('pointer-events', 'auto');
    }
  });
});

$(document).delegate('.policy', 'click', function (e) {
  e.preventDefault();

  var $link = $(this);

  // Якщо клік вже обробляється або модалка відкрита - ігноруємо
  if ($link.hasClass('loading')) {
    return;
  }

  // Блокуємо посилання
  $link.addClass('loading').css('pointer-events', 'none');

  $('#modal-agree').remove();

  $.ajax({
    url: $link.attr('href'),
    type: 'GET',
    dataType: 'html',
    success: function (data) {
      var $response = $(data);
      var modalTitle = $response.find('h1.category-name').text().trim();
      var blockContent = $response.find('#information-information').html();
      var contentToShow = blockContent || data;

      html = '<div id="modal-agree" class="modal">';
      html += '  <div class="modal-dialog">';
      html += '    <div class="modal-content">';
      html += '      <div class="modal-header">';
      html += '        <h3 class="modal-title text-24 bl-bold">' + modalTitle + '</h3>';
      html += '        <div class="close" data-dismiss="modal" aria-hidden="true"><svg width="20px" height="30px" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path fill="#000" d="M764.288 214.592 512 466.88 259.712 214.592a31.936 31.936 0 0 0-45.12 45.12L466.752 512 214.528 764.224a31.936 31.936 0 1 0 45.12 45.184L512 557.184l252.288 252.288a31.936 31.936 0 0 0 45.12-45.12L557.12 512.064l252.288-252.352a31.936 31.936 0 1 0-45.12-45.184z"></path></svg></div>';
      html += '      </div>';
      html += '      <div class="modal-body">' + contentToShow + '</div>';
      html += '    </div>';
      html += '  </div>';
      html += '</div>';
      $('body').append(html);

      // Відновлюємо посилання після закриття модалки
      $('#modal-agree').on('hidden.bs.modal', function () {
        $link.removeClass('loading').css('pointer-events', 'auto');
      });

      $('#modal-agree').modal('show');
    },
    error: function () {
      // Відновлюємо посилання у разі помилки
      $link.removeClass('loading').css('pointer-events', 'auto');
    }
  });
});

// Autocomplete */
(function ($) {
  $.fn.autocomplete = function (option) {
    return this.each(function () {
      this.timer = null;
      this.items = new Array();

      $.extend(this, option);

      $(this).attr('autocomplete', 'off');

      // Focus
      $(this).on('focus', function () {
        this.request();
      });

      // Blur
      $(this).on('blur', function () {
        setTimeout(function (object) {
          object.hide();
        }, 200, this);
      });

      // Keydown
      $(this).on('keydown', function (event) {
        switch (event.keyCode) {
          case 27: // escape
            this.hide();
            break;
          default:
            this.request();
            break;
        }
      });

      // Click
      this.click = function (event) {
        event.preventDefault();

        value = $(event.target).parent().attr('data-value');

        if (value && this.items[value]) {
          this.select(this.items[value]);
        }
      }

      // Show
      this.show = function () {
        var pos = $(this).position();

        $(this).siblings('ul.dropdown-menu').css({
          top: pos.top + $(this).outerHeight(),
          left: pos.left
        });

        $(this).siblings('ul.dropdown-menu').show();
      }

      // Hide
      this.hide = function () {
        $(this).siblings('ul.dropdown-menu').hide();
      }

      // Request
      this.request = function () {
        clearTimeout(this.timer);

        this.timer = setTimeout(function (object) {
          object.source($(object).val(), $.proxy(object.response, object));
        }, 200, this);
      }

      // Response
      this.response = function (json) {
        html = '';

        if (json.length) {
          for (i = 0; i < json.length; i++) {
            this.items[json[i]['value']] = json[i];
          }

          for (i = 0; i < json.length; i++) {
            if (!json[i]['category']) {
              html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
            }
          }

          // Get all the ones with a categories
          var category = new Array();

          for (i = 0; i < json.length; i++) {
            if (json[i]['category']) {
              if (!category[json[i]['category']]) {
                category[json[i]['category']] = new Array();
                category[json[i]['category']]['name'] = json[i]['category'];
                category[json[i]['category']]['item'] = new Array();
              }

              category[json[i]['category']]['item'].push(json[i]);
            }
          }

          for (i in category) {
            html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

            for (j = 0; j < category[i]['item'].length; j++) {
              html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
            }
          }
        }

        if (html) {
          this.show();
        } else {
          this.hide();
        }

        $(this).siblings('ul.dropdown-menu').html(html);
      }

      $(this).after('<ul class="dropdown-menu"></ul>');
      $(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

    });
  }
})(window.jQuery);
