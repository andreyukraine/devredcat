import React from 'react';
import ReactDOM from 'react-dom/client';
import FilterComponent from './components/FilterComponent';
import ViewSwitcher from './components/ViewSwitcher';
import WrapperComponent from "./components/WrapperComponent";
import { registerCategorySlider } from './components/sliderUtils';

// Реестр компонентов (только фильтр)
const COMPONENTS = {
  'Filter': FilterComponent,
  'ViewSwitcher': ViewSwitcher,
  'ProductsCount': ({ total }) => (
    <div className="products-count">
      Знайдено: <strong>{total}</strong>
    </div>
  )
};

registerCategorySlider();

// Для обратной совместимости
export function init(filterProps, translations = {}, containerId = 'filter-container') {
  const container = document.getElementById(containerId);
  if (!container) {
    console.error(`Container #${containerId} not found`);
    return false;
  }

  const root = ReactDOM.createRoot(container);
  root.render(
    <React.StrictMode>
        <WrapperComponent filterProps={filterProps} translations={translations} />
    </React.StrictMode>
  );
}

export function renderComponent(componentName, props, containerId) {
  const container = document.getElementById(containerId);
  if (!container) {
    console.error(`Container #${containerId} not found`);
    return false;
  }

  const Component = COMPONENTS[componentName];
  if (!Component) {
    console.error(`Component ${componentName} not registered`);
    return false;
  }

  try {
    const root = ReactDOM.createRoot(container);
    root.render(
      <React.StrictMode>
        <Component {...props} />
      </React.StrictMode>
    );
    return true;
  } catch (error) {
    console.error('Error rendering component:', error);
    return false;
  }
}


// Глобальный объект
if (typeof window !== 'undefined') {
  window.ReactApp = {
    init
  };
  window.AjaxFilter = { init }; // Для обратной совместимости
}
