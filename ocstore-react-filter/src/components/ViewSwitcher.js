import React, { useState, useEffect, useCallback, useRef } from 'react';
import { initializeSliders } from './sliderUtils';

const ViewSwitcher = ({ buttonList = 'List', categoryView = 'grid', gridColumns = 4 }) => {
  const [viewType, setViewType] = useState(localStorage.getItem('display') || categoryView);
  const [gridCols, setGridCols] = useState(() => {
    const val = parseInt(localStorage.getItem('grid_cols'));
    return isNaN(val) ? gridColumns : val;
  });

  const timeoutRef = useRef(null);

  const applyViewChanges = useCallback((type, cols, attempt = 0) => {
    if (timeoutRef.current) clearTimeout(timeoutRef.current);

    const container = document.getElementById('ajax-filter-container');
    if (!container) {
      if (attempt < 20) {
        timeoutRef.current = setTimeout(() => applyViewChanges(type, cols, attempt + 1), 50);
      }
      return;
    }

    const products = container.querySelectorAll('.product-layout');
    // Не чекаємо якщо товарів просто немає (може порожня категорія)
    if (!products.length && attempt < 5) {
      timeoutRef.current = setTimeout(() => applyViewChanges(type, cols, attempt + 1), 50);
      return;
    }

    const columns = document.querySelectorAll('#column-left, #column-right').length;
    // Пріоритет: 
    // 1. cols (передано явно при кліку)
    // 2. gridCols (взято з localStorage при ініціалізації)
    // 3. Значення за замовчуванням
    const currentCols = cols || gridCols;
    const colSize = columns === 2 ? 6 : (currentCols || 4);

    const expectedClass = type === 'list' ? 'product-list' : 'product-grid';
    const firstProduct = products[0];
    const expectedColClass = `col-lg-${12 / colSize}`;

    if (firstProduct && firstProduct.classList.contains(expectedClass) && 
        firstProduct.classList.contains(expectedColClass)) {
            // Схоже все вже ок, просто оновимо кнопки
            updateButtons(type, currentCols);
            initializeSliders(); // Все одно треба ініціалізувати слайдери для нових товарів
            return;
    }

    products.forEach(product => {
      product.classList.remove('product-grid', 'product-list', 'grid-style', 'list-style', 'col-lg-3', 'col-lg-4', 'col-md-3', 'col-md-4', 'col-sm-4', 'col-xs-6', 'col-xs-12');
      
      if (type === 'list') {
        product.classList.add('product-list', 'list-style', 'col-xs-12');
      } else {
        product.classList.add(
          'product-grid',
          'grid-style',
          `col-lg-${12 / colSize}`,
          `col-md-${12 / colSize}`,
          'col-sm-4',
          'col-xs-12'
        );
      }
    });

    container.classList.remove('layout-preloading');
    updateButtons(type, currentCols);
    initializeSliders();

  }, [gridCols]);

  const updateButtons = (type, cols) => {
    document.querySelectorAll('.modes .btn').forEach(btn => btn.classList.remove('active'));
    if (type === 'grid') {
      const btn = document.querySelector(`.btn-grid-${cols}`);
      if (btn) btn.classList.add('active');
    } else {
      const btn = document.querySelector('.btn-list');
      if (btn) btn.classList.add('active');
    }
  };

  useEffect(() => {
    // Глобальний доступ для AJAX-функцій
    window.category_view_apply_layout = (type, cols) => {
      applyViewChanges(type || viewType, cols || gridCols);
    };

    // Вперше застосувати поточний вид
    applyViewChanges(viewType, gridCols);

    // Спостерігати за новими товарами
    const observer = new MutationObserver(() => {
      applyViewChanges(viewType, gridCols);
    });

    const container = document.getElementById('ajax-filter-container');
    if (container) {
      observer.observe(container, { childList: true });
    }

    return () => {
      if (timeoutRef.current) clearTimeout(timeoutRef.current);
      observer.disconnect();
    };
  }, [applyViewChanges, viewType, gridCols]);

  const handleViewChange = (type, cols) => {
    setViewType(type);
    if (type === 'grid' && cols) {
      setGridCols(cols);
      localStorage.setItem('grid_cols', cols);
    }
    localStorage.setItem('display', type);
  };

  return (
    <div className="modes">
      <button
        type="button"
        onClick={() => handleViewChange('grid', 3)}
        className={`btn btn-default btn-custom-view btn-grid btn-grid-3${viewType === 'grid' && gridCols === 3 ? ' active' : ''}`}
        title="3"
      >
        3
      </button>
      <button
        type="button"
        onClick={() => handleViewChange('grid', 4)}
        className={`btn btn-default btn-custom-view btn-grid btn-grid-4${viewType === 'grid' && gridCols === 4 ? ' active' : ''}`}
        title="4"
      >
        4
      </button>
      <button
        type="button"
        onClick={() => handleViewChange('list')}
        className={`btn btn-default btn-custom-view btn-list${viewType === 'list' ? ' active' : ''}`}
        title={buttonList}
      >
        {buttonList}
      </button>
      <input type="hidden" id="category-view-type" value={viewType} />
      <input type="hidden" id="category-grid-cols" value={gridCols} />
    </div>
  );
};

export default ViewSwitcher;
