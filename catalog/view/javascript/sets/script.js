(function ($) {
    $.fn.pop = function () {
        var top = this.get(-1);
        this.splice(this.length - 1, 1);
        return top;
    };

    $.fn.shift = function () {
        var bottom = this.get(0);
        this.splice(0, 1);
        return bottom;
    };
})(jQuery);

var product_id;
var iset;
var gproducts = [];
var set_modal = false;
var current_set;


$(document).ready(function () {
    $('.sets-slick').slick({infinite: true, autoplay: true, autoplaySpeed: 3000, speed: 300, slidesToShow: 1, adaptiveHeight: true, arrows: true});
    $(".sets").click(function ()
    {
        $('.sets-slick').slick('slickPause');
    });

    $(".add-set-btn").click(function () {

        $('.sets-slick').slick('slickPause');
        product_id = $(this).parents('.set').find("input[name='product_id']").val();
        iset = $(this).parents('.set').find("input[name='iset']").val();

        gproducts = $(this).parents('.set').find('.set-product');
        products = $(this).parents('.set').find('.set-product');
        current_set = $(this).parents('.set');

        if (products.length > 1)
            recuesiveCheckSetOptions(products);
    });

    $('.apply-options').on('click', function () {
        var btn_id = $(this).parents('.modal').attr('id');
        $('button[data-target="#' + btn_id + '"]').parents('.set').find('.add-set-btn').first().trigger("click");

    });

    $(".set-options select,.set-options input[type='radio'],.set-options input[type='checkbox']").change(function ()
    {

        var options = $(this).parents('.set-options').find("select option:selected,input[type='radio']:checked,input[type='checkbox']:checked");
        var total = 0;

        $(options).each(function () {
            var pre = $(this).data('prefix');
            var price = parseFloat($(this).data('price'));

            if (pre.length != 0 && isNaN(price) == false)
            {

                switch (pre) {
                    case '-':
                        total -= price;
                        break;
                    case '+':
                        total += price;
                        break;
                    case '=':
                        total = price;
                        return false;
                        break;
                    default:
                        break;
                }
            }
        });
        var btn_id = $(this).parents('.set-options').attr("id");
        var product = $("button[data-target='#" + btn_id + "'").parents('.set-product');
        var set = $(product).parents('.set');
        $(product).find("input[name='option_price']").val(total);


        update_total(set);
    });

});
function roundNumber(number) {
    decimals = parseInt(getDecimal());
    var dec = Math.pow(10, decimals)


    if (decimals)
    {
        number = "" + Math.round(parseFloat(number) * dec + .0000000000001);
        return number.slice(0, -1 * decimals) + "." + number.slice(-1 * decimals);
    } else
    return Math.round(number)+' ';
}
function animateCounter(selector, oldp, newp)
{
    if (oldp !== newp)
        $(selector).prop('Counter', oldp).animate({
            Counter: newp
        }, {
            duration: 500,
            easing: 'swing',
            step: function (now) {
                $(this).text(Math.ceil(now));
            },
            complete: function () {
                $(selector).html(roundNumber(newp));
            }
        });
}
function update_total(set)
{
    var cprice;
    var option_price;
    var qty;
    var actual_price;
    var total = 0;
    var start;

    if (set.length > 1)
        set = $(set).last();

    $(set).find('.set-product').each(function (index) {
        cprice = parseFloat($(this).find("input[name='cprice']").val());
        option_price = parseFloat($(this).find("input[name='option_price']").val());
        qty = parseInt($(this).find("input[name='quantity']").val());


        actual_price = (cprice + option_price);
        total += (actual_price * qty);

        start = parseFloat(($(this).find('.new_price .num').html()).replace(/\s/g, ''));
        animateCounter($(this).find(".new_price .num"), start, actual_price);
    });

    var economy = $(set).find("input[name='economy']").val();


    if (economy.length)
    {
        if (economy.slice(-1) == "%")
        {
            var prec = parseFloat(economy.slice(0, -1));
            economy = ((total / 100) * prec);
        } else
        {
            economy = parseFloat(economy);
        }

        total -= economy;
        start = parseFloat(($(set).find('.total .economy_val .num').html()).replace(/\s/g, ''));
        animateCounter($(set).find('.total .economy_val .num'), start, economy);
    }


    start = parseFloat(($(set).find('.total .new_summ .num').html()).replace(/\s/g, ''));
    animateCounter($(set).find('.total .new_summ .num'), start, total);
}


function addSetToCart()
{

    var products = {};

    $.each(gproducts, function (key, value) {
        var data = getOptions(value).serialize();
        products['product' + key] = data;
    });
    var btn = $(current_set).find(".add-set-btn");


    $.ajax({
        url: 'index.php?route=extension/module/sets/addProductToCart',
        type: 'post',
        data: products,
        dataType: 'json',

        success: function (json) {
            if (json['success'])
            {
                $('.set-options').find('input[type=\'text\'], input[type=\'date\'], input[type=\'time\'], input[type=\'datetime\'],  select, textarea').val('');
                $('.set-options').find('input[type=\'radio\'], input[type=\'checkbox\']').removeAttr('checked');

                addSetToCartSuccess(json);
                
                $(btn).button('success');
                setTimeout(function () {

                    clearOptionPrice();
                    update_total(current_set);
                    $(btn).button('reset');
                }, 2000);
            }
        }
    });
}
function addSetToCartSuccess(json)
{
    setTimeout(function () {
        $('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
    }, 100);

    $('#cart > ul').load('index.php?route=common/cart/info ul li');
}

function clearOptionPrice()
{
    $("input[name='option_price']").val(0);
}

function addSetToTotal()
{
    $.ajax({
        url: 'index.php?route=extension/module/sets/addSetToTotal',
        type: 'post',
        data: {product_id: product_id, iset: iset},
        dataType: 'json',
        success: function () {

            addSetToCart(iset);

        }
    });
}
function getOptions(product)
{
    var modal_selector = $($(product).find('.open-options').data('target'));

    var options = $(product).find('input[type="hidden"]');
    if (modal_selector.length)
    {
        var options_modal = $(modal_selector).find('input[type=\'text\'], input[type=\'hidden\'], input[type=\'date\'], input[type=\'time\'], input[type=\'datetime\'], input[type=\'radio\']:checked, input[type=\'checkbox\']:checked, select,  textarea');
        var options = $.merge(options_modal, options);

    }
    return options;

}
function recuesiveCheckSetOptions(products)
{

    var product = products.shift();
    var options = getOptions(product);
    var modal_selector = $($(product).find('.open-options').data('target'));


    $.ajax({
        url: 'index.php?route=extension/module/sets/checkProductOption',
        type: 'post',
        data: options,
        dataType: 'json',
        success: function (json) {
            $(modal_selector).find('.text-danger').parent().removeClass('has-error');
            $(modal_selector).find('.text-danger').remove();

            if (json['error']) {
                if (json['error']['option']) {

                    $(".modal").modal('hide');
                    if (modal_selector.length)
                        setTimeout(function () {
                            $(modal_selector).modal('show')
                        }, 500);


                    for (i in json['error']['option']) {

                        var element = $(modal_selector).find('#set-input-option' + i.replace('_', '-'));

                        if (element.parent().hasClass('input-group')) {
                            element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                        } else {
                            element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                        }
                    }
                }
                if (modal_selector.length)
                    $(modal_selector).find('.text-danger').parent().addClass('has-error');

            } else if (json['success']) {

                if (products.length > 0)
                    recuesiveCheckSetOptions(products);
                else
                {
                    $(".modal").modal('hide');
                    addSetToTotal();
                }
            }


        },
        error: function (xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }

    }
    );

}