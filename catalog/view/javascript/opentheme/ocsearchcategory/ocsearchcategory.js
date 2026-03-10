/**
 * window.ocsearchcategory — відкриття/закриття вікна пошуку в хедері (по кліку на іконку).
 * Використовується разом з іконкою .header-search-icon у header.twig.
 */
window.ocsearchcategory = {
  open: function () {
    if (typeof window.ocmenuview_top !== 'undefined') {
      window.ocmenuview_top.hide();
    }
    var btnGroup = document.querySelector('#search-by-category .btn-group');
    var menu = document.querySelector('#search-by-category .dropdown-menu.search-content');
    if (btnGroup && menu) {
      btnGroup.classList.add('open');
      menu.style.display = 'block';
      var input = document.getElementById('text-search');
      if (input) {
        setTimeout(function () { input.focus(); }, 100);
      }
    }
  },

  hide: function () {
    var btnGroup = document.querySelector('#search-by-category .btn-group');
    var menu = document.querySelector('#search-by-category .dropdown-menu.search-content');
    if (btnGroup) btnGroup.classList.remove('open');
    if (menu) menu.style.display = '';
  },

  init: function () {
    var icon = document.querySelector('.header-search-icon');
    if (icon) {
      icon.addEventListener('click', function (e) {
        e.preventDefault();
        window.ocsearchcategory.open();
      });
    }
    document.addEventListener('click', function (e) {
      var closeBtn = e.target.closest('#search-by-category .button-close');
      if (closeBtn) {
        window.ocsearchcategory.hide();
      }
    });
  }
};

(function () {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      window.ocsearchcategory.init();
    });
  } else {
    window.ocsearchcategory.init();
  }
})();
