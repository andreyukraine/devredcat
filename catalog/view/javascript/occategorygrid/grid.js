// Замінюємо window.load на document.ready + додатковий таймаут
$(document).ready(function() {
  setTimeout(function() {
    initViewType();
  }, 100);
});

function initViewType() {
  if (localStorage.getItem('type') == null) {
    var type = $('#category-view-type').val();
    var cols = $('#category-grid-cols').val();
    var element = type == 'list' ? 'btn-list' : 'btn-grid-' + cols;

    category_view.initView(type, cols, element);
  } else {
    var type = localStorage.getItem('type');
    var cols = localStorage.getItem('cols');
    var element = localStorage.getItem('element');
    category_view.initView(type, cols, element);
  }
}

window.category_view = {
  initView: function(type, cols, element) {
    this.changeView(type, cols, element);
  },

  changeView: function(type, cols, element) {
    // Зміна відображення товарів
    if (type == "grid") {
      var column = parseInt(cols);
      var gridClass = column == 4 ?
        'col-lg-4 col-md-4 col-sm-4 col-xs-4' :
        'col-lg-3 col-md-3 col-sm-3 col-xs-6';

      $('#content .product-item').attr('class',
        'product-layout product-grid grid-style ' + gridClass + ' product-item');
    } else {
      $('#content .product-item').attr('class',
        'product-layout product-list col-xs-12 product-item');
    }

    // Оновлення активної кнопки
    $('.btn-custom-view').removeClass('active');
    $('.' + element).addClass('active');

    // Збереження в localStorage
    localStorage.setItem('type', type);
    localStorage.setItem('cols', cols);
    localStorage.setItem('element', element);

    // this.initAllSliders(); // Викликаємо ініціалізацію слайдерів окремо
  },

  // initAllSliders: function() {
  //   // Додаємо затримку для гарантії оновлення DOM
  //     $(".product-item .item .slider").each(function() {
  //       category_view.initializeSliderItem($(this));
  //     });
  // },

  initializeSliderItem: function($slider) {
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
      }).on('init', function(event, slick) {
        //setTimeout(() => {
          //slick.slickGoTo(initialSlideIndex, true);
          //slick.refresh();

          // Форсируем пересчет позиций
          //$slider.find('.slick-track').css('transform', 'translate3d(0, 0, 0)');

        //}, 100);
      });
  }
};
