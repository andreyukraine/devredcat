import React, { useState, useRef } from 'react';
import FilterComponent from './FilterComponent';
import ActiveFilters from './ActiveFilters';
import Portal from './PortalWrapper';

const WrapperComponent = ({ filterProps, translations }) => {
  const filterRef = useRef(null);
  const [totalProducts, setTotalProducts] = useState(filterProps.initialTotal || 0);

  // Експортуємо методи фільтра глобально
  React.useEffect(() => {
    window.FilterApp = {
      handleSort: (sort, order) => {
        if (filterRef.current) {
          filterRef.current.handleSort(sort, order);
        }
      },
      handleResetAll: () => {
        if (filterRef.current) {
          filterRef.current.handleResetAll();
        }
      }
    };
  }, []);

  const onTotalChange = (newTotal) => {
    setTotalProducts(newTotal);
  };

  const [filters, setFilters] = useState({
    manufacturer: [],
    options: {},
    attributes: {}
  });

  // Функція для перевірки наявності активних фільтрів
  const hasActiveFilters = () => {
    return filters.manufacturer.length > 0 || Object.keys(filters.options).length > 0 || Object.keys(filters.attributes).length > 0;
  };

  const handleRemoveFilter = (type, data) => {
    if (filterRef.current && typeof filterRef.current.handleRemoveFilter === 'function') {
      filterRef.current.handleRemoveFilter(type, data);
    } else {
      // Fallback якщо реф ще не готовий (хоча це малоімовірно)
      setFilters(prev => {
        if (type === 'manufacturer') {
          return {
            ...prev,
            manufacturer: prev.manufacturer.filter(id => id !== data)
          };
        }

        if (type === 'option') {
          const { groupId, valueId } = data;
          const updated = { ...prev.options };
          updated[groupId] = (updated[groupId] || []).filter(id => id !== valueId);
          if (updated[groupId].length === 0) delete updated[groupId];
          return {
            ...prev,
            options: updated
          };
        }

        if (type === 'attribute') {
          const { groupId, valueId } = data;
          const updated = { ...prev.attributes };
          updated[groupId] = (updated[groupId] || []).filter(id => id !== valueId);
          if (updated[groupId].length === 0) delete updated[groupId];
          return {
            ...prev,
            attributes: updated
          };
        }

        return prev;
      });
    }
  };

  const handleResetAll = () => {
    if (filterRef.current && typeof filterRef.current.handleResetAll === 'function') {
      filterRef.current.handleResetAll();
    } else {
      setFilters({
        manufacturer: [],
        options: {},
        attributes: {}
      });
    }
  };

  return (
    <>
      <Portal containerId="filter-container">
        <FilterComponent
          ref={filterRef}
          {...filterProps}
          translations={translations}
          filters={filters}
          setFilters={setFilters}
          onTotalChange={onTotalChange}
        />
      </Portal>

      <Portal containerId="active-filters-container">
        <ActiveFilters
          filters={filters}
          groups={filterProps.groups}
          onRemove={handleRemoveFilter}
          onResetAll={handleResetAll}
        />
      </Portal>

      <Portal containerId="root-react-wrapper">
        <div className="products-count">
          Знайдено товарів: <strong>{totalProducts}</strong>
        </div>
      </Portal>
    </>
  );
};

export default WrapperComponent;
