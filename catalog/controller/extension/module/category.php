<?php

class ControllerExtensionModuleCategory extends Controller
{
  public function index($setting)
  {
    $this->load->language('extension/module/category');
    $this->load->model('catalog/category');
    $this->load->model('catalog/product');

    if (isset($this->request->get['path'])) {
      $parts = explode('_', (string)$this->request->get['path']);
    } else {
      $parts = array();
    }

    if (!empty($parts)) {
      if (isset($parts[0])) {
        $data['category_id'] = $parts[0];
      } else {
        $data['category_id'] = 0;
      }

      if (isset($parts[1])) {
        $data['child_id'] = $parts[1];
      } else {
        $data['child_id'] = 0;
      }

      // Добавьте условие для третьего уровня
      if (isset($parts[2])) {
        $data['child_3lv_id'] = $parts[2];
      } else {
        $data['child_3lv_id'] = 0;
      }

      $data['categories'] = array();

      $category_type = 0;
      if (isset($this->request->cookie['catalog_type'])) {
        $category_type = (int)$this->request->cookie['catalog_type'];
      }

      $categories = $this->model_catalog_category->getCategories(0, $category_type);

      foreach ($categories as $category) {
        $children_data = array();

        // Убедитесь, что загружаются только нужные категории
        if ($category['category_id'] != $data['category_id']) {
          continue;
        }

        // Получение подкатегорий (второй уровень)
        $children = $this->model_catalog_category->getCategories($category['category_id'], $category_type);

        if (empty($children)){
          continue;
        }

        foreach ($children as $child) {
          $grandchildren_data = array(); // Массив для третьего уровня

          // Получение подкатегорий для текущей подкатегории (третий уровень)
          $grandchildren = $this->model_catalog_category->getCategories($child['category_id'], $category_type);

          foreach ($grandchildren as $grandchild) {
            $filter_data3 = array(
              'filter_category_id' => $grandchild['category_id'],
              'filter_sub_category' => true
            );
            $count3 = $this->model_catalog_product->getTotalProductsInCategory($filter_data3);
            if ($count3 > 0) {
              $grandchildren_data[] = array(
                'category_id' => $grandchild['category_id'],
                'name' => $grandchild['name'] . ($this->config->get('config_product_count') ? '<span class="articles-count">' . $count3 . '</span>' : ''),
                'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grandchild['category_id'])
              );
            }
          }

          // Добавление второго уровня категорий, включая третий уровень
          $filter_data2 = array(
            'filter_category_id' => $child['category_id'],
            'filter_sub_category' => true
          );
          $count2 = $this->model_catalog_product->getTotalProductsInCategory($filter_data2);
          if ($count2 > 0) {
            $children_data[] = array(
              'category_id' => $child['category_id'],
              'name' => $child['name'] . ($this->config->get('config_product_count') ? '<span class="articles-count">' . $count2 . '</span>' : ''),
              'children' => $grandchildren_data, // Добавляем третий уровень категорий
              'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
            );
          }
        }

        $filter_data = array(
          'filter_category_id' => $category['category_id'],
          'filter_sub_category' => true
        );
        $name = mb_convert_case(mb_strtolower($category['name']), MB_CASE_TITLE, "UTF-8");
        // Добавление основного (первого) уровня категорий
        $data['categories'][] = array(
          'category_id' => $category['category_id'],
          'name' => $name,
          'children' => $children_data, // Второй уровень с третьим уровнем внутри
          'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
        );
      }

    } else {
      if (isset($this->request->get['manufacturer_id'])){
        $data['all_active'] = true;
        $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($this->request->get['manufacturer_id']);
        if ($manufacturer_info) {
          $filter_data = array(
            'filter_manufacturer_id' => $manufacturer_info['manufacturer_id'],
          );
          $results = $this->model_catalog_product->getProducts($filter_data);
          if (!empty($results)) {
            $categories = $this->getManufactureCategories(array_keys($results));
            if (!empty($categories)) {
              foreach ($categories as $category) {
                $children_data = array();

                if (isset($category['children'])) {
                  // Получение подкатегорий (второй уровень)
                  foreach ($category['children'] as $child) {
                    $grandchildren_data = array(); // Массив для третьего уровня

                    if (isset($child['children'])) {
                      // Получение подкатегорий для текущей подкатегории (третий уровень)
                      foreach ($child['children'] as $grandchild) {
                        $filter_data3 = array(
                          'filter_category_id' => $grandchild['category_id'],
                          'filter_manufacturer_id' => $manufacturer_info['manufacturer_id'],
                          'filter_sub_category' => false
                        );
                        $count3 = $this->model_catalog_product->getTotalProductsInCategory($filter_data3);
                        if ($count3 > 0) {
                          $grandchildren_data[] = array(
                            'category_id' => $grandchild['category_id'],
                            'name' => $grandchild['name'] . ($this->config->get('config_product_count') ? '<span class="articles-count">' . $count3 . '</span>' : ''),
                            'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grandchild['category_id'])
                          );
                        }
                      }
                    }

                    // Добавление второго уровня категорий, включая третий уровень
                    $filter_data2 = array(
                      'filter_category_id' => $child['category_id'],
                      'filter_manufacturer_id' => $manufacturer_info['manufacturer_id'],
                      'filter_sub_category' => true
                    );
                    $count2 = $this->model_catalog_product->getTotalProductsInCategory($filter_data2);
                    if ($count2 > 0) {
                      $children_data[] = array(
                        'category_id' => $child['category_id'],
                        'name' => $child['name'] . ($this->config->get('config_product_count') ? '<span class="articles-count">' . $count2 . '</span>' : ''),
                        'children' => $grandchildren_data, // Добавляем третий уровень категорий
                        'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
                      );
                    }
                  }
                }

                $filter_data = array(
                  'filter_category_id' => $category['category_id'],
                  'filter_manufacturer_id' => $manufacturer_info['manufacturer_id'],
                  'filter_sub_category' => true
                );

                $count1 = $this->model_catalog_product->getTotalProductsInCategory($filter_data);
                if ($count1 > 0) {
                  $name = mb_convert_case(mb_strtolower($category['name']), MB_CASE_TITLE, "UTF-8");
                  // Добавление основного (первого) уровня категорий
                  $data['categories'][] = array(
                    'category_id' => $category['category_id'],
                    'name' => $name,
                    'children' => $children_data, // Второй уровень с третьим уровнем внутри
                    'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
                  );
                }
              }
            }
          }
        }
      }else{
        $data = array();
        $data['all_active'] = true;
        if (isset($this->request->get['route'])){
          switch ($this->request->get['route']){
            case "product/special":
              $filter_data = array(
                'sort'  => 'pd.name',
                'order' => 'ASC'
              );

              $results = $this->model_catalog_product->getProductSpecials($filter_data);
              $categories = $this->getSpecialCategories(array_keys($results));
              break;
            case "product/search":
              $q = "";
              if (isset($this->request->get['q'])){
                $q = $this->request->get['q'];
              }
              $filter_data = array(
                'filter_name' => $q,
                'filter_sub_category' => true
              );

              if (isset($this->request->get['category_id'])) {
                $filter_data['category_id'] = $this->request->get['category_id'];
              }

              $results = $this->model_catalog_product->getProducts($filter_data);
              $categories = $this->getSearchCategories(array_keys($results));
              break;
          }
          if (!empty($categories)) {
            foreach ($categories as $category) {
              $children_data = array();

              if (isset($category['children'])) {
                // Получение подкатегорий (второй уровень)
                foreach ($category['children'] as $child) {
                  $grandchildren_data = array(); // Массив для третьего уровня

                  if (isset($child['children'])) {
                    // Получение подкатегорий для текущей подкатегории (третий уровень)
                    foreach ($child['children'] as $grandchild) {
                      $filter_data3 = array(
                        'filter_category_id' => $grandchild['category_id'],
                        'filter_sub_category' => false
                      );
                      $count3 = $this->model_catalog_product->getTotalProductsInCategory($filter_data3);
                      if ($count3 > 0) {
                        $grandchildren_data[] = array(
                          'category_id' => $grandchild['category_id'],
                          'name' => $grandchild['name'] . ($this->config->get('config_product_count') ? '<span class="articles-count">' . $count3 . '</span>' : ''),
                          'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $grandchild['category_id'])
                        );
                      }
                    }
                  }

                  // Добавление второго уровня категорий, включая третий уровень
                  $filter_data2 = array(
                    'filter_category_id' => $child['category_id'],
                    'filter_sub_category' => false
                  );
                  $count2 = $this->model_catalog_product->getTotalProductsInCategory($filter_data2);
                  if ($count2 > 0) {
                    $children_data[] = array(
                      'category_id' => $child['category_id'],
                      'name' => $child['name'] . ($this->config->get('config_product_count') ? '<span class="articles-count">' . $count2 . '</span>' : ''),
                      'children' => $grandchildren_data, // Добавляем третий уровень категорий
                      'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
                    );
                  }
                }
              }

              $filter_data = array(
                'filter_category_id' => $category['category_id'],
                'filter_sub_category' => true
              );

              $count1 = $this->model_catalog_product->getTotalProductsInCategory($filter_data);
              if ($count1 > 0) {
                $name = mb_convert_case(mb_strtolower($category['name']), MB_CASE_TITLE, "UTF-8");
                // Добавление основного (первого) уровня категорий
                $data['categories'][] = array(
                  'category_id' => $category['category_id'],
                  'name' => $name,
                  'children' => $children_data, // Второй уровень с третьим уровнем внутри
                  'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
                );
              }
            }
          }
        }
      }
    }

    return $this->load->view('extension/module/category', $data);
  }

  public function getManufactureCategories($data = array()) {
    if(!empty($data)){

      foreach ($data as $result) {
        if(isset($result['product_id'])){
          $products[] = (int)$result['product_id'];
        } else {
          $products[] = (int)$result;
        }
      }

      $sql = " SELECT cd.name, c.parent_id, c.category_id,cp.level, COUNT(DISTINCT `p`.`product_id`) AS total FROM `" . DB_PREFIX . "category_path` `cp`";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cp`.`path_id` = `cd`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "category` `c` ON (`cp`.`path_id` = `c`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`cp`.`category_id` = `p2c`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`p2c`.`product_id` = `p`.`product_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`)";
      $sql .= " WHERE `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";
      $sql .= " AND `p`.`status` = '1' AND `c`.`status` = '1' AND `p`.`date_available` <= NOW() AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "'";
      $sql .= " AND `p`.`product_id` IN (" . implode(',', $products) . ")";
      $sql .= " GROUP BY cp.path_id";
      $sql .= " ORDER BY `cp`.`level` ASC";

      $query = $this->db->query($sql);
      $query_rows = $query && $query->rows ? $query->rows : array();

      $categories = [];
      $this->collectСategories($query_rows, $categories);
      return $categories;
    }
  }

  public function getSpecialCategories($data = array()) {
    if(!empty($data)){

      foreach ($data as $result) {
        if(isset($result['product_id'])){
          $products[] = (int)$result['product_id'];
        } else {
          $products[] = (int)$result;
        }
      }

      $sql = " SELECT cd.name, c.parent_id, c.category_id,cp.level, COUNT(DISTINCT `p`.`product_id`) AS total FROM `" . DB_PREFIX . "category_path` `cp`";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cp`.`path_id` = `cd`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "category` `c` ON (`cp`.`path_id` = `c`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`cp`.`category_id` = `p2c`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`p2c`.`product_id` = `p`.`product_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`)";
      $sql .= " WHERE `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";
      $sql .= " AND `p`.`status` = '1' AND `c`.`status` = '1' AND `p`.`date_available` <= NOW() AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "'";
      $sql .= " AND `p`.`product_id` IN (" . implode(',', $products) . ")";
      $sql .= " GROUP BY cp.path_id";
      $sql .= " ORDER BY `cp`.`level` ASC";

      $query = $this->db->query($sql);
      $query_rows = $query && $query->rows ? $query->rows : array();

      $categories = [];
      $this->collectСategories($query_rows, $categories);
      return $categories;
    }
  }

  public function getSearchCategories($data = array()) {
    if(!empty($data)){

      foreach ($data as $result) {
        if(isset($result['product_id'])){
          $products[] = (int)$result['product_id'];
        } else {
          $products[] = (int)$result;
        }
      }

      $sql = " SELECT cd.name, c.parent_id, c.category_id,cp.level, COUNT(DISTINCT `p`.`product_id`) AS total FROM `" . DB_PREFIX . "category_path` `cp`";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cp`.`path_id` = `cd`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "category` `c` ON (`cp`.`path_id` = `c`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`cp`.`category_id` = `p2c`.`category_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`p2c`.`product_id` = `p`.`product_id`)";
      $sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`)";
      $sql .= " WHERE `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "'";
      $sql .= " AND `p`.`status` = '1' AND `c`.`status` = '1' AND `p`.`date_available` <= NOW() AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "'";
      $sql .= " AND `p`.`product_id` IN (" . implode(',', $products) . ")";
      $sql .= " GROUP BY cp.path_id";
      $sql .= " ORDER BY `cp`.`level` ASC";

      $query = $this->db->query($sql);
      $query_rows = $query && $query->rows ? $query->rows : array();

      $categories = [];
      $this->collectСategories($query_rows, $categories);
      return $categories;
    }
  }

  private function collectСategories(array $allItems, array &$children, $parentId = 0) {
    foreach ($allItems as $item) {
      if ($item['parent_id'] == $parentId) {
        $item['children'] = [];
        $this->collectСategories($allItems, $item['children'], $item['category_id']);
        $children[] = $item;
      }
    }
  }
}
