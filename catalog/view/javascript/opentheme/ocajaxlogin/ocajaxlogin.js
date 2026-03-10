$(window).load(function () {
  ocajaxlogin.changeEvent();
});

$(document).ready(function () {
  ocajaxlogin.changeEvent();
});
$(document).ajaxComplete(function () {
  ocajaxlogin.changeEvent();
});
window.ocajaxlogin = {

  _loadingView: false,
  _viewCallbacks: [],
  view: function (callback) {
    if ($('.ajaxlogin-container').length && $('.ajax-body-login').length) {
      if (typeof callback === 'function') callback();
      return;
    }
    
    if (typeof callback === 'function') {
      this._viewCallbacks.push(callback);
    }

    if (this._loadingView) return;
    this._loadingView = true;

    if (!$('.ajaxlogin-container').length) {
      $('body').append('<div class="ajaxlogin-container"></div>');
    }

    var self = this;
    $('.ajaxlogin-container').load('index.php?route=extension/module/ocajaxlogin/appendcontainer', function () {
      self._loadingView = false;
      while (self._viewCallbacks.length > 0) {
        var cb = self._viewCallbacks.shift();
        cb();
      }
    });
  },

  loginGoogle: function (urlLogin) {
    $.ajax({
      url: urlLogin,
      type: 'post',
      dataType: 'json',
      beforeSend: function () {
        // Показати лоадер
      },
      success: function (json) {
        if (json.redirect_url) {
          // Відкрити модальне вікно з URL авторизації
          ocajaxlogin.openAuthPopup(json.redirect_url);
        } else if (json.error) {
          // Показати помилку
          alert(json.error);
        }
      },
      error: function(xhr, status, error) {
        console.error('Помилка:', error);
      }
    });
  },

  // Функція для відкриття popup авторизації
  openAuthPopup: function (authUrl) {
    // Відкрити нове popup-вікно
    var width = 600, height = 700;
    var left = (screen.width / 2) - (width / 2);
    var top = (screen.height / 2) - (height / 2);

    var authWindow = window.open(
      authUrl,
      "GoogleAuth",
      `width=${width},height=${height},top=${top},left=${left},status=no,toolbar=no,menubar=no,location=no,resizable=yes,scrollbars=yes`
    );

    // Таймер щоб слідкувати, коли popup закриється
    var checkPopupClosed = setInterval(function () {
      if (authWindow.closed) {
        clearInterval(checkPopupClosed);
        // Тут можна викликати refresh профілю чи перевірку логіну
      }
    }, 500);
  },

  loginAction: function (email, password) {
    //ocajaxlogin.view();
    $.ajax({
      url: 'index.php?route=extension/module/ajaxlogin/login',
      type: 'post',
      data: $('#ajax-login-form').serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('.action').addClass('custom_lock');
        $('.error-email').removeClass('text-danger').html('').hide();
        $('.error-password').removeClass('text-danger').html('').hide();
        $('.error-warning').html('').hide();
      },
      success: function (json) {
        if (json['success'] == true) {
          if (json['redirect']) {
            var redirect_url = json['redirect'].replace(/&amp;/g, '&');
            if (redirect_url == window.location.href || redirect_url == window.location.pathname + window.location.search) {
              if (window.location.href.indexOf('route=account/logout') !== -1) {
                location = 'index.php?route=account/account';
              } else {
                location.reload(true);
              }
            } else {
              location = redirect_url;
            }
            return;
          }
          if (json['enable_redirect']) {
            location = json['redirect'];
            return;
          } else {
            $('#wishlist').html('<span id="wishlist-total">' + json['wishlist_total'] + '</span>');
            $('#cart-total').html(json['cart_total']);
            $('body').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success_message'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          }
          ocajaxlogin.closeForm();
          $('.ajax-load-img').hide();
          $('.login-form-content .alert-danger').remove();

          if (window.location.href.indexOf('route=account/logout') !== -1) {
              location = 'index.php?route=account/account';
          } else {
              location.reload(true);
          }
        } else {

          if (json['error_email'] !== '') {
            $('.error-email').addClass('text-danger').html(json['error_email']).show();
          }
          if (json['error_password'] !== '') {
            $('.error-password').addClass('text-danger').html(json['error_password']).show();
          }

          if (json['error_warning'] !== '') {
            $('.error-warning').html(json['error_warning']).show();
          }

          $('.text-danger').each(function () {
            var element = $(this).parent().parent();
            if (element.hasClass('form-group')) {
              element.addClass('has-error');
            }
          });

          $('.action').removeClass('custom_lock');
          $('.ajax-load-img').hide();
          $('.login-form-content .alert-danger').remove();
        }
      }
    });
  },

  registerAction: function () {

    $('.for-error').removeClass('text-danger').hide();
    $('.form-group').removeClass('has-error');

    var customer_type = $('#customer-type').val();

    $.ajax({
      url: 'index.php?route=extension/module/ajaxregister/register',
      type: 'post',
      data: $('#ajax-register-form').serialize() + '&customer_type=' + encodeURIComponent(customer_type),
      dataType: 'json',
      beforeSend: function () {
        $('.register-form-content').show();
        $('.error-warning').html('');
      },
      success: function (json) {
        $('.ajax-load-img').hide();
        if (json['success']) {

          $('body').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success_message'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

          if (json['approved']) {
            $('.account-approved .ajax-content').html(json['success_html']);
            ocajaxlogin.appendApproved(false);
          } else {
            $('.account-success .ajax-content').html(json['success_html']);
            ocajaxlogin.appendSuccess(false);

            if (json['enable_redirect']) {
              setTimeout(function() {
                location = json['redirect'];
              }, 7000);
            } else {
              $('#wishlist').html('<span id="wishlist-total">' + json['wishlist_total'] + '</span>');
              $('#cart-total').html(json['cart_total']);
              
              setTimeout(function() {
                if (window.location.href.indexOf('route=account/logout') !== -1) {
              location = 'index.php?route=account/account';
          } else {
              location.reload(true);
          }
              }, 7000);
            }
          }
        } else {
          if (json['error_warning'] != '') {
            $('.error-warning').html(json['error_warning']);
            $('.error-warning').show();
          }
          if (json['error_firstname'] != '') {
            $('.error-firstname').addClass('text-danger').html(json['error_firstname']).show();
          }
          if (json['error-customer_group'] != '') {
            $('.error-customer_group').addClass('text-danger').html(json['error_customer_group']).show();
          }
          if (json['error_lastname'] != '') {
            $('.error-lastname').addClass('text-danger').html(json['error_lastname']).show();
          }
          if (json['error_email'] != '') {
            $('.error-email').addClass('text-danger').html(json['error_email']).show();
          }
          if (json['error_telephone'] != '') {
            $('.error-telephone').addClass('text-danger').html(json['error_telephone']).show();
          }
          if (json['error_zone'] != '') {
            $('.error-zone').addClass('text-danger').html(json['error_zone']).show();
          }

          if (json['error_custom_field']) {
            for (i in json['error_custom_field']) {
              $('.error-custom-field' + i).addClass('text-danger').html(json['error_custom_field'][i]).show();
            }
          }
          if (json['error_password'] != '') {
            $('.error-password').addClass('text-danger').html(json['error_password']).show();
          }
          if (json['error_confirm'] != '') {
            $('.error-confirm').addClass('text-danger').html(json['error_confirm']).show();
          }
          if (json['error_agree'] != '') {
            $('.error-agree').addClass('text-danger').html(json['error_agree']).show();
          }
          if (json['error_captcha'] != '') {
            $('.error-captcha').addClass('text-danger').html(json['error_captcha']).show();
          }

          $('.text-danger').each(function () {
            var element = $(this).parent().parent();
            if (element.hasClass('form-group')) {
              element.addClass('has-error');
            }
          });
        }
      }
    });
  },

  logoutAction: function () {
    $.ajax({
      url: 'index.php?route=extension/module/ajaxlogin/logout',
      dataType: 'json',
      beforeSend: function () {
        $('#ajax-lock-block').show();
        // Додаємо обробник кліку для ajax-lock-block
        $('#ajax-lock-block').off('click').on('click', function () {
          ocajaxlogin.closeForm();
        });
        $('#ajax-loader').show();
      },
      success: function (json) {
        $('#ajax-loader').hide();
        $('#ajax-lock-block').hide();

        location = json['redirect'];
        location.reload(true);

        ocajaxlogin.appendLogoutSuccess();
      }
    });
  },

  fogottenAction: function () {
    $.ajax({
      url: 'index.php?route=extension/module/ajaxfogotten/fogotten',
      type: 'post',
      data: $('#ajax-fogotten-form').serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('.ajax-load-img').show();
      }, success: function (json) {
        if (json['success'] == true) {
          ocajaxlogin.appendLoginForm();
          $('.account-fogotten .bl-inp-block').html(json['text_success']);
        } else {

          if (json['error_email'] != '') {
            $('.error-email').addClass('text-danger').html(json['error_email']).show();
          }
          if (json['error_captcha'] != '') {
            $('.error-captcha').addClass('text-danger').html(json['error_captcha']).show();
          }

          $('.text-danger').each(function () {
            var element = $(this).parent().parent();
            if (element.hasClass('form-group')) {
              element.addClass('has-error');
            }
          });

        }
        $('.ajax-load-img').hide();
      }
    });
  },

  _isShowing: false,
  appendLoginForm: function (redirect) {

    if (this._isShowing) return;
    this._isShowing = true;

    if (!$('.ajax-body-login').length) {
      var self = this;
      ocajaxlogin.view(function () {
        self._isShowing = false;
        ocajaxlogin.appendLoginForm(redirect);
      });
      return;
    }

    if ($('.ajax-body-login').hasClass('active')) {
        if (!$('.account-login').is(':visible')) {
            $('.account-type-client').hide();
            $('.account-register').hide();
            $('.account-fogotten').hide();
            $('.account-login').show();
        }
        this._isShowing = false;
        return;
    }

    custom_cart.close();

    if (redirect) {
      if ($('#ajax-login-form input[name="redirect"]').length) {
        $('#ajax-login-form input[name="redirect"]').val(redirect);
      } else {
        $('#ajax-login-form').append('<input type="hidden" name="redirect" value="' + redirect + '" />');
      }
    }

    ocmenuview_top.hide();
    $('#customer-type').val("");
    ocajaxlogin.resetLoginForm();
    ocajaxlogin.resetRegisterForm();
    
    var $body = $('.ajax-body-login');
    $body.show();
    
    // Використовуємо подвійний таймаут для гарантованої активації анімації в усіх браузерах
    setTimeout(function() {
        $body.addClass('active');
        ocajaxlogin._isShowing = false;
        // Додаткова перевірка через 100мс, якщо клас не додався або не спрацював
        setTimeout(function() {
            if (!$body.hasClass('active')) {
                $body.addClass('active');
            }
        }, 100);
    }, 50);

    $('.account-type-client').hide();
    $('.account-register').hide();
    $('.account-fogotten').hide();
    $('.account-login').show();
    document.body.classList.add('body-no-scroll');

    $('#ajax-lock-block').show();

    // Додаємо обробник кліку для ajax-lock-block
    $('#ajax-lock-block').off('click').on('click', function () {
      ocajaxlogin.closeForm();
    });
  },

  appendRegisterForm: function () {
    if (!$('.ajax-body-login').length) {
      ocajaxlogin.view(function () {
        ocajaxlogin.appendRegisterForm();
      });
      return;
    }
    if ($('.account-register').is(':visible') && $('.ajax-body-login').hasClass('active')) {
        return;
    }
    ocmenuview_top.hide();
    var type = 1;
    $.ajax({
      url: 'index.php?route=extension/module/ajaxregister/tohtml',
      type: 'post',
      data: $('#ajax-register-form').serialize() + '&customer_type=' + encodeURIComponent(type),
      dataType: 'json',
      beforeSend: function () {
        $('.ajax-load-img').show();
      },
      success: function (json) {
        $('.ajax-load-img').hide();

        $('#customer-type').val(type);

        ocajaxlogin.resetLoginForm();
        ocajaxlogin.resetRegisterForm();
        $('.ajax-body-login').addClass('active');
        $('.account-login').hide();
        $('.account-type-client').hide();
        $('.account-fogotten').hide();
        $('.account-register .ajax-content').html(json.html);
        $('.account-register').show();

        $('#ajax-lock-block').show();

        // Додаємо обробник кліку для ajax-lock-block
        $('#ajax-lock-block').off('click').on('click', function () {
          ocajaxlogin.closeForm();
        });
      }
    });
    document.body.classList.add('body-no-scroll');
  },

  appendRegisterFormWholesaler: function () {
    var type = 2;
    $.ajax({
      url: 'index.php?route=extension/module/ajaxregister/tohtml',
      type: 'post',
      data: $('#ajax-register-form').serialize() + '&customer_type=' + encodeURIComponent(type),
      dataType: 'json',
      beforeSend: function () {
        $('.ajax-load-img').show();
      },
      success: function (json) {
        $('.ajax-load-img').hide();

        $('#customer-type').val(type);

        ocajaxlogin.resetLoginForm();
        ocajaxlogin.resetRegisterForm();
        $('.ajax-body-login').addClass('active');
        $('.account-login').hide();
        $('.account-type-client').hide();
        $('.account-fogotten').hide();
        $('.account-register .ajax-content').html(json.html);
        $('.account-register').show();

        $('#ajax-lock-block').show();

        // Додаємо обробник кліку для ajax-lock-block
        $('#ajax-lock-block').off('click').on('click', function () {
          ocajaxlogin.closeForm();
        });
      }
    });
    document.body.classList.add('body-no-scroll');
  },

  appendRegisterFormBreeder: function () {
    var type = 3;
    $.ajax({
      url: 'index.php?route=extension/module/ajaxregister/tohtml',
      type: 'post',
      data: $('#ajax-register-form').serialize() + '&customer_type=' + encodeURIComponent(type),
      dataType: 'json',
      beforeSend: function () {
        $('.ajax-load-img').show();
      },
      success: function (json) {
        $('.ajax-load-img').hide();

        $('#customer-type').val(type);

        ocajaxlogin.resetLoginForm();
        ocajaxlogin.resetRegisterForm();
        $('.ajax-body-login').addClass('active');
        $('.account-login').hide();
        $('.account-type-client').hide();
        $('.account-fogotten').hide();
        $('.account-register .ajax-content').html(json.html);
        $('.account-register').show();

        $('#ajax-lock-block').show();

        // Додаємо обробник кліку для ajax-lock-block
        $('#ajax-lock-block').off('click').on('click', function () {
          ocajaxlogin.closeForm();
        });
      }
    });
    document.body.classList.add('body-no-scroll');
  },

  appendTypeClientForm: function () {
    if (!$('.ajax-body-login').length) {
      ocajaxlogin.view(function () {
        ocajaxlogin.appendTypeClientForm();
      });
      return;
    }
    if ($('.account-type-client').is(':visible') && $('.ajax-body-login').hasClass('active')) {
        return;
    }
    ocmenuview_top.hide();
    $('#customer-type').val("");
    ocajaxlogin.resetLoginForm();
    ocajaxlogin.resetRegisterForm();
    
    var $body = $('.ajax-body-login');
    $body.show();
    setTimeout(function() {
        $body.addClass('active');
    }, 10);

    $('.account-login').hide();
    $('.account-register').hide();
    $('.account-fogotten').hide();
    $('.account-type-client').show();
    document.body.classList.add('body-no-scroll');

    $('#ajax-lock-block').show();

    // Додаємо обробник кліку для ajax-lock-block
    $('#ajax-lock-block').off('click').on('click', function () {
      ocajaxlogin.closeForm();
    });

  },

  appendFogottenForm: function () {
    if (!$('.ajax-body-login').length) {
      ocajaxlogin.view(function () {
        ocajaxlogin.appendFogottenForm();
      });
      return;
    }
    if ($('.account-fogotten').is(':visible') && $('.ajax-body-login').hasClass('active')) {
        return;
    }
    $('#customer-type').val("");
    ocajaxlogin.resetLoginForm();
    ocajaxlogin.resetRegisterForm();
    $('.ajax-body-login').addClass('active');
    $('.account-fogotten').show();
    $('.account-login').hide();
    $('.account-register').hide();
    $('.account-type-client').hide();
    document.body.classList.add('body-no-scroll');
  },

  appendSuccess: function ($return) {
    $('#customer-type').val("");
    $('.ajax-body-login').addClass('active');
    $('.account-register').hide();
    $('.account-fogotten').hide();
    $('.account-type-client').hide();
    $('.account-success').show();
    $('.account-approved').hide();

    if ($return) {
      // Перезагрузить страницу после успешной авторизации
      //location.reload();
    }
  },

  appendApproved: function ($return) {
    $('#customer-type').val("");
    ocajaxlogin.resetRegisterForm();
    $('.ajax-body-login').addClass('active');
    $('.account-register').hide();
    $('.account-fogotten').hide();
    $('.account-type-client').hide();
    $('.account-success').hide();
    $('.account-approved').show();

    if ($return) {
      // Перезагрузить страницу после успешной авторизации
      //location.reload();
    }
  },

  appendLogoutSuccess: function () {
    $('#customer-type').val("");
    $('.ajax-body-login').addClass('active');
    $('.account-type-client').hide();
    $('.account-register').hide();
    $('.account-fogotten').hide();
    $('.account-approved').hide();
    $('.account-login').show();

    $('#ajax-lock-block').show();

    // Додаємо обробник кліку для ajax-lock-block
    $('#ajax-lock-block').off('click').on('click', function () {
      ocajaxlogin.closeForm();
    });
  },

  resetLoginForm: function () {
    $('.login-form-content .alert-danger').remove();
  },

  resetRegisterForm: function () {
    $('.for-error').removeClass('text-danger').hide();
    $('.form-group').removeClass('has-error');

    var $form = $('#ajax-register-form');
    if ($form.length) {
      // Reset native form state first (works for most inputs/selects)
      if ($form[0] && typeof $form[0].reset === 'function') {
        $form[0].reset();
      }

      // Ensure values are fully cleared (browser may keep values in some cases)
      $form.find('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="number"], input[type="search"], input[type="url"], input[type="date"], textarea').val('');
      $form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
      $form.find('select').each(function () {
        this.selectedIndex = 0;
      });
    }
  },

  closeForm: function () {
    this._isShowing = false;
    document.body.classList.remove('body-no-scroll');
    $('#customer-type').val("");
    $('#ajax-lock-block').hide();

    $('#ajax-loader').hide();
    $('.account-login').hide();
    $('.account-register').hide();
    $('.account-fogotten').hide();
    $('.account-success').hide();
    $('.account-approved').hide();
    $('.logout-success').hide();
    $('.ajax-body-login').removeClass('active');
    ocajaxlogin.resetLoginForm();
    ocajaxlogin.resetRegisterForm();
  },

  changeEvent: function () {
    $('#a-typeclient-link').attr('href', 'javascript:void(0);').attr('onclick', 'ocajaxlogin.appendTypeClientForm()');
    $('#a-register-link').attr('href', 'javascript:void(0);').attr('onclick', 'ocajaxlogin.appendRegisterForm()');
    $('#a-login-link').attr('href', 'javascript:void(0);').attr('onclick', 'ocajaxlogin.appendLoginForm()');
    $('#a-fogotten-link').attr('href', 'javascript:void(0);').attr('onclick', 'ocajaxlogin.appendFogottenForm()');
    $('#a-logout-link').attr('href', 'javascript:void(0);').attr('onclick', 'ocajaxlogin.logoutAction()');
  }
};
