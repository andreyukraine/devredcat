import React, { useState, useEffect, useCallback, useRef, forwardRef, useImperativeHandle } from 'react';
import axios from 'axios';
import { parseSeoUrl, generateSeoUrl } from '../filterUtils';

const FilterComponent = forwardRef(({ filters, setFilters, groups, initialQuantities, initialTotal, apiUrls, translations, onTotalChange }, ref) => {

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [quantities, setQuantities] = useState(initialQuantities || {});
  const [totalProducts, setTotalProducts] = useState(initialTotal || 0);
  //const [expandedSections, setExpandedSections] = useState({});
  const [isReadyFromURL, setIsReadyFromURL] = useState(false);
  const [sort, setSort] = useState(new URLSearchParams(window.location.search).get('sort') || '');
  const [order, setOrder] = useState(new URLSearchParams(window.location.search).get('order') || '');
  const filtersChangedByUser = useRef(false);
  const isInitialMount = useRef(true);

  useImperativeHandle(ref, () => ({
    handleRemoveFilter: (type, data) => {
      handleRemoveFilter(type, data);
    },
    handleResetAll: () => {
      handleResetAll();
    },
    handleSort: (newSort, newOrder) => {
      setSort(newSort);
      setOrder(newOrder);
      filtersChangedByUser.current = true;
    }
  }));

  // Функція для ініціалізації розгорнутих секцій
  const initializeExpandedSections = (groups) => {
    const sections = {};

    if (groups?.manufacturer?._0?.values) {
      sections['manufacturer'] = true;
    }

    ['option', 'attribute'].forEach(type => {
      Object.values(groups?.[type] || {}).forEach(group => {
        sections[`${type}_${group.group_id}`] = true;
      });
    });

    return sections;
  };

  const [expandedSections, setExpandedSections] = useState(initializeExpandedSections(groups));

  // Парсинг URL при завантаженні
  const handleParseSeoUrl = useCallback((path = window.location.pathname) => {
    const parsed = parseSeoUrl(path, groups);
    
    // Якщо фільтрів в URL немає, ми все одно позначаємо готовність
    setFilters(parsed);
    filtersChangedByUser.current = false;
    setIsReadyFromURL(true);
  }, [groups, setFilters]);


  useEffect(() => {
    if (Object.keys(groups || {}).length > 0 && isInitialMount.current) {
      setExpandedSections(initializeExpandedSections(groups));
      handleParseSeoUrl();
      isInitialMount.current = false; // Встановлюємо після першого парсингу
    }
  }, [groups, handleParseSeoUrl]);

  // Генерація SEO URL
  const handleGenerateSeoUrl = useCallback(() => {
    return generateSeoUrl(filters, groups);
  }, [filters, groups]);

  const getStartFromPage = () => {
    const searchParams = new URLSearchParams(window.location.search);
    const page = parseInt(searchParams.get('page'), 10);
    return (Number.isNaN(page) || page < 1) ? 1 : page;
  };

  const activeRequests = useRef(0);
  const pendingRequests = useRef(0);
  const lastController = useRef(null);
  const debounceTimer = useRef(null);

  // Застосування фільтрів
  const applyFilters = useCallback(async (page = 1) => {
    // Очищуємо таймер дебаунсу якщо він є
    if (debounceTimer.current) {
      clearTimeout(debounceTimer.current);
      debounceTimer.current = null;
    }

    const container = document.getElementById('ajax-filter-container');
    if (!container) return;

    // Скасовуємо попередній запит (якщо ще триває)
    if (lastController.current) lastController.current.abort();
    const controller = new AbortController();
    lastController.current = controller;

    setLoading(true);
    setError(null);
    activeRequests.current += 1;
    // container.classList.add('custom_lock'); // Вже додано в useEffect для миттєвого фідбеку

    try {
      const currentSeoUrl = handleGenerateSeoUrl();
      const formData = new FormData();
      formData.append('filter', currentSeoUrl.split('/filter/')[1] || '');
      formData.append('quantity_status', 'true');
      formData.append('page', page);

      const urlSearchParams = new URLSearchParams(window.location.search);
      
      // Додаємо всі параметри з поточного URL до formData
      for (const [key, value] of urlSearchParams.entries()) {
        if (key !== 'filter' && key !== 'page' && key !== 'sort' && key !== 'order') {
          formData.append(key, value);
        }
      }

      // Визначаємо curRoute
      let curRoute = urlSearchParams.get('route');
      // Якщо в URL немає route, спробуємо знайти його в apiUrls.ajax
      if (!curRoute && apiUrls.ajax.includes('curRoute=')) {
        const ajaxParams = new URLSearchParams(apiUrls.ajax.split('?')[1]);
        curRoute = ajaxParams.get('curRoute');
      }
      formData.append('curRoute', curRoute || 'product/category');

      if (sort) {
        formData.append('sort', sort);
        urlSearchParams.set('sort', sort);
      } else {
        urlSearchParams.delete('sort');
      }
      
      if (order) {
        formData.append('order', order);
        urlSearchParams.set('order', order);
      } else {
        urlSearchParams.delete('order');
      }

      if (filtersChangedByUser.current) {
        urlSearchParams.delete('page');
      }

      const cleanQuery = urlSearchParams.toString();
      const finalUrl = cleanQuery ? `${currentSeoUrl}?${cleanQuery}` : currentSeoUrl;
      window.history.pushState(null, '', finalUrl);

      const response = await axios.post(apiUrls.ajax, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        signal: controller.signal // важливо для abort
      });

      if (!response.data?.success) throw new Error(response.data?.error || 'Server error');

      if (response.data.quantity) {
        setQuantities(response.data.quantity);
      }

      if (response.data.total_prods !== undefined) {
        setTotalProducts(response.data.total_prods);
        if (typeof onTotalChange === 'function') {
          onTotalChange(response.data.total_prods);
        }
      }

      if (container && response.data.products) {

        container.innerHTML = response.data.products;

        // застосовуємо режим відображення - ТЕПЕР ПЕРЕДАЄТЬСЯ ЧЕРЕЗ MutationObserver у ViewSwitcher
        /*
        if (window.category_view_apply_layout) {
          try { window.category_view_apply_layout(); } catch (e) {}
        }
        */

        // Прокурка після оновлення контенту
        setTimeout(() => {
          const container_header = document.getElementById('header-top-block');
          const targetElement = document.getElementById('react-root');
          if (targetElement) {
            const headerHeight = (container_header?.getBoundingClientRect().height || 0) + 20; 
            const elementPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
            
            // Скролимо лише якщо ми реально нижче ніж початок списку
            if (window.pageYOffset > elementPosition) {
                window.scrollTo({
                  top: elementPosition - headerHeight,
                  behavior: 'auto' // швидше ніж smooth
                });
            }
          }
        }, 50); 
      }
    } catch (err) {
      if (controller.signal.aborted) {
        // тихо ігноруємо — це був скасований запит
      } else {
        setError(err?.message || 'Filter error');
      }
    } finally {
      activeRequests.current -= 1;
      // Знімаємо lock лише коли НЕ залишилось ні активних, ні очікуючих запитів
      if (activeRequests.current <= 0 && pendingRequests.current <= 0) {
        activeRequests.current = 0;
        pendingRequests.current = 0;
        if (container) container.classList.remove('custom_lock');
      }
      setLoading(false);
    }
  }, [handleGenerateSeoUrl, apiUrls.ajax, sort, order]);

  // Обробники змін фільтрів
  const handleFilterChange = useCallback((type, groupId, valueId, checked) => {
    filtersChangedByUser.current = true;
    setFilters(prev => {
      if (type === 'manufacturer') {
        return {
          ...prev,
          manufacturer: checked
            ? [...prev.manufacturer, valueId]
            : prev.manufacturer.filter(id => id !== valueId)
        };
      }

      const filterKey = `${type}s`;
      const currentValues = prev[filterKey]?.[groupId] || [];

      return {
        ...prev,
        [filterKey]: {
          ...prev[filterKey],
          [groupId]: checked
            ? [...currentValues, valueId]
            : currentValues.filter(id => id !== valueId)
        }
      };
    });
  }, [setFilters]);

  const handleRemoveFilter = useCallback((type, value) => {
    filtersChangedByUser.current = true;

    setFilters(prev => {
      if (type === 'manufacturer') {
        return {
          ...prev,
          manufacturer: prev.manufacturer.filter(id => id !== value)
        };
      } else {
        const { groupId, valueId } = value;
        return {
          ...prev,
          [`${type}s`]: {
            ...prev[`${type}s`],
            [groupId]: (prev[`${type}s`]?.[groupId] || []).filter(id => id !== valueId)
          }
        };
      }
    });
  }, [setFilters]);

  const handleResetAll = useCallback(() => {
    filtersChangedByUser.current = true;
    setFilters({
      manufacturer: [],
      options: {},
      attributes: {}
    });
  }, [setFilters]);

  useEffect(() => {
    if (!isInitialMount.current && filtersChangedByUser.current) {
      // Показуємо лоадер відразу для зворотного зв'язку
      const container = document.getElementById('ajax-filter-container');
      if (container) container.classList.add('custom_lock');
      
      pendingRequests.current += 1;

      // Впроваджуємо Debounce (затримка 300мс — швидше ніж 500мс)
      if (debounceTimer.current) clearTimeout(debounceTimer.current);
      
      debounceTimer.current = setTimeout(() => {
        pendingRequests.current -= 1;
        // Якщо фільтри змінені користувачем, завжди скидаємо на першу сторінку
        const targetPage = filtersChangedByUser.current ? 1 : getStartFromPage();
        applyFilters(targetPage);
      }, 300);
    }
    
    return () => {
      if (debounceTimer.current) clearTimeout(debounceTimer.current);
    };
  }, [filters, sort, order, applyFilters]);

  // 🔁 Застосувати перегляд після першого завантаження, навіть без фільтра
  useEffect(() => {
    if (isReadyFromURL) {
      const container = document.getElementById('ajax-filter-container');
      if (container && container.children.length > 0 && window.category_view_apply_layout) {
        window.category_view_apply_layout(); //автоматично застосує поточний режим
      }
    }
  }, [isReadyFromURL]);

  // Відображення кількості товарів
  useEffect(() => {
    const countContainer = document.getElementById('root-react-wrapper');
    if (countContainer) {
      const ProductsCount = ({ total }) => (
        <div className="products-count">
          Знайдено товарів: <strong>{total}</strong>
        </div>
      );

      // Ми використовуємо ReactDOM.render через портал або прямий рендер
      // Але оскільки ми всередині React, краще передати цей стан вгору або через Portal
    }
  }, [totalProducts]);

  // Управління розгортанням секцій
  const toggleSection = useCallback((id, e) => {
    e.stopPropagation(); // Зупиняємо спливання події
    setExpandedSections(prev => ({ ...prev, [id]: !prev[id] }));
  }, []);

  return (
    <div className="ajax-filter">
      {/* Рендеринг фільтрів */}
      {groups?.manufacturer?._0?.values && Object.values(groups.manufacturer._0.values).length > 1 && (
        <div className="filter-section">
          <div className="f-name"><div className="f-title">{translations.text_brand || 'Бренди'}</div></div>
          <div style={{ display: expandedSections['manufacturer'] ? 'block' : 'none' }}>
            {Object.values(groups.manufacturer._0.values).map(brand => {
              const isChecked = filters.manufacturer.includes(brand.value);
              // Manufacturer usually has group_id 0
              const manufacturerQuantities = quantities.manufacturer || {};
              const group0Quantities = manufacturerQuantities[0] || manufacturerQuantities['_0'] || manufacturerQuantities;
              const quantity = group0Quantities[brand.value] ?? group0Quantities[`_${brand.value}`] ?? 0;
              
              const isDisabled = !isChecked && quantity === 0;

              return (
                <label key={brand.value} className={`${isDisabled ? 'disabled' : ''} ${isChecked ? 'checked' : ''}`}>
                  <div className={'ch-f-line'}>
                    <input
                      type="checkbox"
                      checked={isChecked}
                      disabled={isDisabled}
                      onChange={(e) => handleFilterChange('manufacturer', null, brand.value, e.target.checked)}
                    />
                    {brand.name}
                  </div>
                  {quantity !== undefined && <span className="count">({quantity})</span>}
                </label>
              );
            })}
          </div>
        </div>
      )}

      {/* Опції та атрибути (об'єднані та відсортовані) */}
      <div className="filters-container">
        {[
          ...Object.values(groups?.option || {}).map(g => ({ ...g, type: 'option' })),
          ...Object.values(groups?.attribute || {}).map(g => ({ ...g, type: 'attribute' }))
        ]
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
        .map(group => {
          const type = group.type;
          return (
            <div key={`${type}_${group.group_id}`} className="filter-section">
              <div className="f-name" onClick={(e) => {
                const isIconClicked = e.target.closest('.icon-mc-up, .icon-mc-down');
                if (!isIconClicked) {
                  toggleSection(`${type}_${group.group_id}`, e);
                }
              }}>
                <div className="f-title">{group.caption}</div>
                <div
                  className={expandedSections[`${type}_${group.group_id}`] ? "icon-mc-up" : "icon-mc-down"}
                  onClick={(e) => toggleSection(`${type}_${group.group_id}`, e)}
                ></div>
              </div>
              <div className="f-bl-values" style={{ display: expandedSections[`${type}_${group.group_id}`] ? 'block' : 'none' }}>
                {Object.values(group.values || {}).map(item => {
                  const isChecked = (filters[`${type}s`]?.[group.group_id] || []).includes(item.value);
                  
                  const typeQuantities = quantities[type] || {};
                  const groupQuantities = typeQuantities[group.group_id] || typeQuantities[`_${group.group_id}`] || {};
                  const quantity = groupQuantities[item.value] ?? groupQuantities[`_${item.value}`] ?? 0;
                  
                  const isDisabled = !isChecked && quantity === 0;

                  return (
                    <label key={item.value} className={`${isDisabled ? 'disabled' : ''} ${isChecked ? 'checked' : ''}`}>
                      <div className={'ch-f-line'}>
                      <input
                        type="checkbox"
                        checked={isChecked}
                        disabled={isDisabled}
                        onChange={(e) => handleFilterChange(type, group.group_id, item.value, e.target.checked)}
                      />
                      {item.name}
                      </div>
                      {quantity !== undefined && <span className="count">({quantity})</span>}
                    </label>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>

      {loading && <div className="loading">Завантаження...</div>}
      {error && <div className="error">{error}</div>}
    </div>
  );
});

export default FilterComponent;
