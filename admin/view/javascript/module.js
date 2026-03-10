$(function () {
    $('form').on('submit', function () {
        return isEmpty();
    });

    $('form').delegate('.fa-minus-circle', 'click', function () {
        $(this).parent().remove();
    });
});

function isEmpty() {
    var notEmpty = true;
    if ($('.form-content').length > 1) {
        $("form :input").each(function () {
            var element = $(this);
            if ($.trim(element.val()) === "" && (element.attr("name") === "name[]" ||
                    element.attr("name") === "undisplayed_name[]")) {

                element.parent().addClass('has-error');
                element.next().text(element.prev().text() + ' is required');
                notEmpty = false;
            }
        });

        $('form #featured-product').each(function () {
            var element = $(this);
            if (element.parent().find('input').attr('name') === "product_name[]" && $.trim(element.html()) === "" &&
                $.trim(element.parent().parent().parent().find('#featured-category').html()) === ""
            ) {
                notEmpty = false;
                element.parent().find('input').parent().addClass('has-error');
                element.parent().parent().parent().find('#featured-category')
                    .parent().find('input').parent().addClass('has-error');

            }
        });
    }
    return notEmpty;


}
