<?php

class ControllerProductCategoryTree extends Controller
{
  public function index($params)
  {
    if (is_array($params) && isset($params['mass_prod_id'])) {
      $mass_prod_id = $params['mass_prod_id'];
    } else {
      $mass_prod_id = $params;
    }

    if (empty($mass_prod_id)) {
      return '';
    }

    if (!is_array($mass_prod_id)) {
      $mass_prod_id = array($mass_prod_id);
    }

    $this->load->model('catalog/category');
    $this->load->model('catalog/product');
    $cats = $this->model_catalog_product->getCategoriesIn($mass_prod_id);

    $tree = array();
    $data['debug_message'] = 'IDs: ' . count($mass_prod_id) . ', Cats: ' . count($cats);

    // Отримуємо унікальні category_id з масиву $cats
    $category_ids = array_unique(array_column($cats, 'category_id'));

    // Запит до БД для отримання повної інформації про категорії
    $categories_info = array();
    foreach ($category_ids as $category_id) {
      $category_info = $this->model_catalog_category->getCategory($category_id);
      if ($category_info) {
        $categories_info[$category_id] = $category_info;

        // Отримуємо шлях категорії (всі батьківські категорії)
        $path = $this->model_catalog_category->getCategoryPath($category_id);
        $categories_info[$category_id]['path'] = $path;

        // Рахуємо кількість товарів (лише з $mass_prod_id)
        $categories_info[$category_id]['product_count'] = 0;
        foreach ($cats as $cat) {
          if ($cat['category_id'] == $category_id) {
            $categories_info[$category_id]['product_count']++;
          }
        }
      }
    }

    foreach ($categories_info as $category_id => $category) {
      $type = $category['type'] ?? 0;

      if (!isset($tree[$type])) {
        $tree[$type] = array();
      }

      $path = explode('_', $category['path']);
      $current_level = &$tree[$type];

      foreach ($path as $parent_id) {
        if (!isset($current_level[$parent_id])) {
          $cat = $this->model_catalog_category->getCategory($parent_id);
          if (!$cat) continue;

          $href = $this->url->link('product/category', 'path=' . $cat['category_id']);

          $current_level[$parent_id] = array(
            'category_info' => $cat,
            'href' => $href,
            'product_count' => ($parent_id == $category_id) ? $category['product_count'] : 0,
            'children' => array()
          );
        } else if ($parent_id == $category_id) {
          $current_level[$parent_id]['product_count'] += $category['product_count'];
        }

        if ((string)$parent_id === (string)$category_id) {
          break;
        }

        $current_level = &$current_level[$parent_id]['children'];
      }
    }

    // Тепер підсумовуємо кількість товарів для батьківських категорій
    foreach ($tree as &$type_tree) {
      $this->sumChildProductCounts($type_tree);
    }

    $data['category_tree'] = $tree;

    return $this->load->view('product/category_tree', $data);
  }

  function sumChildProductCounts(&$tree) {
    $total = 0;
    foreach ($tree as $category_id => &$node) {
      if (!empty($node['children'])) {
        $node['product_count'] += $this->sumChildProductCounts($node['children']);
      }
      $total += $node['product_count'];
    }
    return $total;
  }
}
