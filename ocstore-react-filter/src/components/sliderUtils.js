// sliderUtils.js
export function initializeSliders() {

  if (!window.custom_slider) {
    return;
  }

  if (typeof window.custom_slider.initAllSliders === 'function') {
    window.custom_slider.initAllSliders();
  } else if (typeof window.custom_slider.initializeSliderItem === 'function') {

    const sliders = document.querySelectorAll('.product-item .item .slider');

    if (sliders.length === 0) {
      return;
    }

    sliders.forEach(slider => {
      window.custom_slider.initializeSliderItem($(slider));
    });
  }
}


export function registerCategorySlider() {
  if (!window.custom_slider) {
    window.custom_slider = {};
  }

  if (!window.category_view) {
    window.category_view = {};
  }

  window.custom_slider.initAllSliders = function () {

      const sliders = document.querySelectorAll('.product-layout .slider');

      sliders.forEach(slider => {
        window.custom_slider.initializeSliderItem($(slider));
      });

  };

  window.custom_slider.initializeSliderItem = function ($slider) {
    if (!$slider || !$slider.length) {
      return;
    }

    if (typeof $.fn.slick !== 'function') {
      return;
    }

    // Сховати слайдер до ініціалізації
    $slider.css({
      opacity: '0',
      visibility: 'hidden'
    });

    // Якщо вже ініціалізовано — зняти
    if ($slider.hasClass('slick-initialized')) {
      $slider.slick('unslick');
    }

    const $activeSlide = $slider.find('.select[data-pos]');
    const initialSlideIndex = $activeSlide.length
      ? parseInt($activeSlide.attr('data-pos')) || 0
      : 0;

    // Додати обробник init до $slider
    $slider.on('init', function () {
      const $firstImg = $slider.find('img').first();
      
      const showSlider = () => {
        $slider.addClass('is-visible');
        $slider.css({
          opacity: '1',
          visibility: 'visible'
        });
      };

      if ($firstImg.length && !$firstImg[0].complete) {
        $firstImg.one('load error', showSlider);
        // Запасний таймаут, якщо подія не спрацює
        setTimeout(showSlider, 2000);
      } else {
        showSlider();
      }
    });

    // Ініціалізувати Slick
    $slider.slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      speed: 500,
      arrows: false,
      fade: false,
      dots: false,
      initialSlide: initialSlideIndex
    });
  };

}
