// Оновлює H1 і document.title: якщо pageNum > 1 — додати/замінити " | сторінка N"
function updateH1AndTitleForPage(pageNum) {
  if (!pageNum || pageNum < 1) pageNum = 1;
  var baseRe = /\s*\|\s*сторінка\s+\d+\s*$/;
  var suffix = pageNum > 1 ? ' | сторінка ' + pageNum : '';

  var h1 = document.querySelector('.breadcrumbs h1') ||
    document.querySelector('h1');
  if (h1) {
    var h1Text = (h1.textContent || '').trim();
    var newText = baseRe.test(h1Text) ? h1Text.replace(baseRe, '').trim() : h1Text;
    h1.textContent = newText + (pageNum > 1 ? ' | сторінка ' + pageNum : '');
  }

  var title = document.title;
  if (/\|\s*сторінка\s+\d+/.test(title)) {
    document.title = (pageNum > 1)
      ? title.replace(/\|\s*сторінка\s+\d+/, '| сторінка ' + pageNum)
      : title.replace(/\s*\|\s*сторінка\s+\d+\s*$/, '').trim();
  } else if (pageNum > 1) {
    document.title = title + ' | сторінка ' + pageNum;
  }
}

// Ініціалізація при завантаженні сторінки
document.addEventListener('DOMContentLoaded', function () {
  if ($('.ns-smv .pagination li.active').next('li').length > 0) {
    createShowMoreButton();
  }

  // Одразу ініціалізуємо слайдери для товарів на сторінці
  initSlidersForNewProducts($('.category-product-list'));

  // Якщо відкрили сторінку 2, 3, … — одразу додати до H1 " | сторінка N"
  var urlParams = new URLSearchParams(window.location.search);
  var page = parseInt(urlParams.get('page') || '1', 10);
  if (page > 1) {
    setTimeout(function () { updateH1AndTitleForPage(page); }, 100);
  }
});

// Функція створення кнопки "Показати ще"
function createShowMoreButton() {
  if ($('#showmore').length === 0) {
    $('.pagination').before(`
      <div id="showmore" class="box-showmore">
        <button onclick="showmore()" class="showmore-btn button" type="button">
          <svg class="chm-icon-showmore">
            <use xlink:href="/image/showmore.svg#icon-showmore"></use>
          </svg>
          <span class="chm-btn-text">${chSetting.text_showmore}</span>
        </button>
      </div>
    `);
  }
}

// Основна функція "Показати ще"
function showmore() {
  const $next = $('.pagination li.active').next('li');
  if ($next.length === 0) return;

  $('#showmore .showmore-btn').addClass('active-load');

  // Поточна сторінка з URL ?page=
  const urlParams = new URLSearchParams(window.location.search);
  const currentPage = parseInt(urlParams.get('page') || '1', 10);
  const nextPage = isNaN(currentPage) ? 2 : currentPage + 1;

  // Підготовка formData
  const formData = new FormData();

  // Витягуємо filter з pathname
  const path = window.location.pathname;
  const filterIndex = path.indexOf('/filter/');
  if (filterIndex !== -1) {
    const filterSegment = decodeURIComponent(path.substring(filterIndex + 8));
    formData.append('filter', filterSegment);
  }

  // Додаємо категорію, якщо є
  const categoryId = document.querySelector('[data-category-id]')?.getAttribute('data-category-id') || '';

  // 1. Перевіряємо чи це сторінка бренда (має manufacturer_id в URL)
  const manufacturerId = new URLSearchParams(window.location.search).get('manufacturer_id')
    || document.querySelector('[data-manufacturer-id]')?.getAttribute('data-manufacturer-id');

  // special
  const special = document.querySelector('[data-special-id]')?.getAttribute('data-special-id');

  // discont
  const discont = document.querySelector('[data-discont-id]')?.getAttribute('data-discont-id');

  const search = document.querySelector('[data-search-id]')?.getAttribute('data-search-id');

  if (manufacturerId) {
    formData.append('manufacturer_id', manufacturerId);
    formData.append('curRoute', 'manufacturer'); // Важливо для контролера
  } else if (categoryId) {
    formData.append('path', categoryId);
    formData.append('curRoute', 'category');
  }else if (special) {
    formData.append('curRoute', 'special');
  }else if (discont) {
    formData.append('curRoute', 'discont');
  }else if (search){
    const searchInput = document.getElementById('input-search');
    formData.append('q', searchInput ? searchInput.value : '');
    formData.append('curRoute', 'search');
  }

  formData.append('quantity_status', 'true');
  formData.append('page', nextPage);

  // Оновлюємо URL без дублювання page=
  let currentUrl = window.location.pathname + window.location.search;
  let cleanUrl = currentUrl
    .replace(/\/page-\d+\/?/, '')
    .replace(/\?page=\d+&?/, '?')
    .replace(/&page=\d+/, '')
    .replace(/[\/&?]+$/, '');

  const separator = cleanUrl.includes('?') ? '&' : '?';
  const newPageUrl = `${cleanUrl}${separator}page=${nextPage}`;
  window.history.pushState(null, '', newPageUrl);

  // AJAX запит
  $.ajax({
    url: 'index.php?route=extension/module/d_ajax_filter/ajax',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (json) {
      if (typeof json === 'string') {
        try { json = JSON.parse(json); } catch (e) { json = { success: false }; }
      }
      if (json && json.success) {
        const $data = $('<div>').html(json.products);
        const $newProducts = $data.find('.category-product-list > div');
        const $container = $('.category-product-list');

        $container.append($newProducts);
        initSlidersForNewProducts($newProducts);
        updateViewDisplay();
        updatePagination($data);

        // Після оновлення DOM — додати до H1 " | сторінка 2" … " | сторінка n"
        (function (pageNum) {
          setTimeout(function () { updateH1AndTitleForPage(pageNum); }, 100);
        })(nextPage);
      }

      $('#showmore .showmore-btn').removeClass('active-load');

      if (!$('.pagination li.active').next('li').length) {
        $('#showmore').hide();
      }
    },
    error: function () {
      $('#showmore .showmore-btn').removeClass('active-load');
      alert('Помилка завантаження наступної сторінки');
    }
  });
}

// Ініціалізація слайдерів для нових товарів
function initSlidersForNewProducts($newProducts) {
  $newProducts.find('.slider').each(function () {
    const $slider = $(this);
    initSingleSlider($slider);
  });
}

// Оновлення відображення товарів
function updateViewDisplay() {
  if (window.category_view_apply_layout) {
    window.category_view_apply_layout(); // автоматично візьме display та grid_cols з localStorage
  }
}

// Оновлення пагінації
function updatePagination($data) {
  const $newResults = $data.find('.col-sm-12.text-right');
  if ($newResults.length > 0) {
    $('.col-sm-12.text-right').html($newResults.html());
  }

  const $newPages = $data.find('.pages');
  if ($newPages.length > 0) {
    $('.pages').html($newPages.html());
  } else {
    // Резервний варіант, якщо .pages немає, оновлюємо тільки список сторінок
    $('.pagination').html($data.find('.pagination > *'));
  }
}

// Ініціалізація при завантаженні сторінки (дублюємо для надійності, якщо DOMContentLoaded вже пройшов)
$(document).ready(function () {
  initSlidersForNewProducts($('.category-product-list'));
});

// Функція ініціалізації одного слайдера
function initSingleSlider($slider) {
  if (!$slider.length || $slider.hasClass('slick-initialized') || $slider.hasClass('slick-initializing')) return;

  $slider.addClass('slick-initializing');

  const $activeSlide = $slider.find('.select[data-pos]');
  const initialSlideIndex = $activeSlide.length ? parseInt($activeSlide.attr('data-pos')) || 0 : 0;

  $slider.css({
    'opacity': '1',
    'visibility': 'visible'
  });

  setTimeout(() => {
    if ($slider.hasClass('slick-initialized')) {
      $slider.removeClass('slick-initializing');
      return;
    }

    try {
      $slider.slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        speed: 500,
        arrows: false,
        fade: false,
        dots: false,
        initialSlide: initialSlideIndex,
        adaptiveHeight: true,
        accessibility: false
      });
    } catch (e) {
      console.error('Slick initialization error:', e, $slider);
    } finally {
      $slider.removeClass('slick-initializing');
    }
  }, 100);
}
