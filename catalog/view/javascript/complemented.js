$(document).ready(function () {

    $('.btn-vertical-slider').on('click', function () {

        if ($(this).attr('data-slide') === 'next') {
            $(this).parent().parent().parent().carousel('next');
        }
        if ($(this).attr('data-slide') === 'prev') {
            $(this).parent().parent().parent().carousel('prev')
        }

    });
    $('.carousel-control').on('click', function () {

        var $active = $('#myCarousel1 .horizontal-carousel .active.horizontal');
        var slide = 'right';
        var $next = $active.next();
        if ($(this).attr('data-slide') === 'next') {
            slide = 'right';
            $next = $active.next();
        }
        if ($(this).attr('data-slide') === 'prev') {
            slide = 'left';
            $next = $active.prev();
        }
        $active.addClass(slide);
        $next.addClass(slide);
        setTimeout(function () {
            $active.removeClass(slide);
            $next.removeClass(slide);
        }, 0);

        $active.removeClass('active')
            .find('> .dropdown-menu > .active')
            .removeClass('active')
            .end()
            .find('[data-toggle="tab"]')
            .attr('aria-expanded', false)

        $next.addClass('active')
            .find('[data-toggle="tab"]')
            .attr('aria-expanded', true)
        if (!$next.next().hasClass('horizontal'))
            $('.right.carousel-control').hide();
        else
            $('.right.carousel-control').show();
        if (!$next.prev().hasClass('horizontal'))
            $('.left.carousel-control').hide();
        else
            $('.left.carousel-control').show();
    });

    var $active = $('#myCarousel1 .horizontal-carousel .active.horizontal');
    if (!$active.next().hasClass('horizontal'))
        $('.right.carousel-control').hide();
    else
        $('.right.carousel-control').show();
    if (!$active.prev().hasClass('horizontal'))
        $('.left.carousel-control').hide();
    else
        $('.left.carousel-control').show();

    $('.carousel').carousel({
        interval: false
    });
    $('.get-product ').on('click', function () {
        $(this).find('input[type=checkbox]').prop('checked', function () {
            return !this.checked;
        });

    });
    $('#myCarousel1').bind('slid.bs.carousel', function (e) {
        // console.log(e.target);
        if (e.target.id === "myCarousel1")
            $('.horizontal').each(function () {
                $(this).find('.vertical-slider').find('.item:first').not('.active').addClass('active');
            });
    });


    setResult();

    $('input:checkbox').on('change', function () {
        this.setAttribute('checked', this.checked);
        setResult();
    });

    $('.carousel').bind('slid.bs.carousel', function (e) {
        setResult();
    });
    $('input[name=\'quantity\']').on('change keypress keydown keyup', function () {
        setResult();
    });

    $('#buy-button').click(function () {
        var mainValidation = validateMainproduct();
        if (!mainValidation) {
            $('#button-cart').trigger("click");
            $('html, body').animate({scrollTop: $("#product").offset().top}, 'slow');
        }
        if (validationBeforesubmit() && mainValidation) {

            $('#button-cart').trigger("click");

            $('.product-layout.active .get-product  input:checkbox:checked').each(function () {

                var current = $(this).parent().parent().parent().find('input[type=\'text\'], input[type=\'hidden\'], input[type=\'radio\']:checked, input[type=\'checkbox\']:checked, select, textarea');
                $.ajax({
                    url: 'index.php?route=checkout/cart/add',
                    type: 'post',
                    data: current,
                    dataType: 'json',
                    beforeSend: function () {
                        $('#button-cart').button('loading');
                    },
                    complete: function () {
                        $('#button-cart').button('reset');
                    },
                    success: function (json) {
                        $('.alert, .text-danger').remove();
                        $('.form-group').removeClass('has-error');
                        if (json['error']) {
                            if (json['error']['option']) {
                                for (i in json['error']['option']) {
                                    var element = $('#input-option' + i.replace('_', '-'));

                                    if (element.parent().hasClass('input-group')) {
                                        element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                                    } else {
                                        element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                                    }
                                }
                            }

                            if (json['error']['recurring']) {
                                $('select[name=\'recurring_id\']').after('<div class="text-danger">' + json['error']['recurring'] + '</div>');
                            }

                            // Highlight any found errors
                            $('.text-danger').parent().addClass('has-error');
                        }

                        if (json['success']) {
                            $('.breadcrumb').after('<div class="alert alert-success">' + json['success'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

                            $('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');

                            $('html, body').animate({scrollTop: 0}, 'slow');

                            $('#cart > ul').load('index.php?route=common/cart/info ul li');
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });

            });

        }
    });

});

function setResult() {
    var checked = $('.product-layout.active .get-product input:checkbox');
    var count = parseInt($('#product input[name=\'quantity\']').val());


    var cur = '';
    var regExp = /[+-]?\d+(\.\d+)?/g;

    var sum = parseFloat($('#price-to-complemented').text().replace(/ /g, '').match(regExp).map(function (value) {
        return parseFloat(value);
    })[0]) * count;
    $.each(checked, function () {
        var text = ($.trim($(this).parent().parent().parent().find('.price').text())).replace(/ /g, '');

        if ($(this).is(':checked')) {

            sum += text.match(regExp).map(function (value) {
                return parseFloat(value);
            })[0];
            cur = ($.trim($(this).parent().parent().parent().find('.price').text())).split(" ")[1];
            count++;
        }
    });
    sum = sum.toFixed(2);
    var expression = count.toString().split('').pop();
    var children = $('#top-span').find('span').clone();
    if (parseInt(expression) === 1) {
        $('#top-span').html("Купить " + count + ' товар <br> за  ' + sum);
    }
    else if (parseInt(expression) >= 5 || parseInt(expression) === 0)
        $('#top-span').html("Купить " + count + ' товаров <br> за  ' + sum);
    else $('#top-span').html("Купить " + count + ' товара <br> за  ' + sum);
    $("#top-span").append(children);


    // $('#top-span').html(count + '  ' + sum);
}

function validationBeforesubmit() {
    var allIsOk = true;
    $('.product-layout.active .get-product  input:checkbox:checked').each(function () {
        var $inputs = $(this).parent().parent().parent().find('.required').find(':input').not(':input[type=button], :input[type=checkbox], :input[type=radio]');

        $inputs.each(function () {

            if (!$(this).val()) {
                if ($(this).is(":visible"))
                    $(this).css('border-color', 'red');
                else $(this).prev().css('border-color', 'red');
                $(this).closest('.product-thumb').css(
                    {
                        "border-color": "red",
                        "border-width": "1px",
                        "border-style": "solid"
                    }
                );
                allIsOk = false;
            }
        });


        $inputs = $(this).parent().parent().parent().find('.required');


        $inputs.each(function () {

            var $checkboxes = $(this).find(':input[type=checkbox]');
            var $radios = $(this).find(':input[type=radio]');

            var $checkedCheckboxes = $(this).find(':input[type=checkbox]:checked');
            var $checkedRadios = $(this).find(':input[type=radio]:checked');

            if (($checkboxes.length > 0 && $checkedCheckboxes.length === 0) || ($radios.length > 0 && $checkedRadios.length === 0)) {
                $(this).css({
                    "border-color": "red",
                    "border-width": "1px",
                    "border-style": "solid"
                });
                $(this).closest('.product-thumb').css(
                    {
                        "border-color": "red",
                        "border-width": "1px",
                        "border-style": "solid"
                    }
                );
                allIsOk = false;
            }


        });

    });


    return allIsOk;
}

function validateMainproduct() {
    var allIsOk = true;
    $('#product .required').each(function () {
        var $inputs = $(this).find(':input').not(':input[type=button], :input[type=checkbox], :input[type=radio]');

        $inputs.each(function () {

            if (!$(this).val()) {
                allIsOk = false;
            }
        });


        $inputs = $(this);


        $inputs.each(function () {

            var $checkboxes = $(this).find(':input[type=checkbox]');
            var $radios = $(this).find(':input[type=radio]');

            var $checkedCheckboxes = $(this).find(':input[type=checkbox]:checked');
            var $checkedRadios = $(this).find(':input[type=radio]:checked');

            if (($checkboxes.length > 0 && $checkedCheckboxes.length === 0) || ($radios.length > 0 && $checkedRadios.length === 0)) {
                allIsOk = false;
            }


        });
    });
    return allIsOk;
}
