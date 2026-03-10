<?php

/*
* location: admin/model
*/

class ModelExtensionModuleDAjaxFilter extends Model
{
  private $codename = "d_ajax_filter";

  private $common_setting;

  private static $tmp_table_status = 0;
  private static $tmp_table_filter_status = 0;

  public function __construct($registry)
  {
    parent::__construct($registry);

    $this->load->model('setting/setting');
    $common_setting = $this->model_setting_setting->getSetting($this->codename);

    if (empty($common_setting[$this->codename . '_setting'])) {
      $this->config->load('d_ajax_filter');
      $setting = $this->config->get('d_ajax_filter_setting');

      $common_setting = $setting['general'];
    } else {
      $common_setting = $common_setting[$this->codename . '_setting'];
    }

    $this->common_setting = $common_setting;
  }

  public function prepareTableFilter($data)
  {
    if (self::$tmp_table_filter_status) {
      return;
    }
    
    $this->db->query("DROP TEMPORARY TABLE IF EXISTS `" . DB_PREFIX . "af_temporary_filter` ");

    $category_id = isset($data['filter_category_id']) ? $data['filter_category_id'] : 0;
    $category_ids = is_array($category_id) ? $category_id : explode(',', $category_id);
    $category_ids = array_map('intval', $category_ids);
    $category_ids = array_filter($category_ids);

    $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `" . DB_PREFIX . "af_temporary_filter` (PRIMARY KEY (`product_id`)) ";
    $sql .= "SELECT DISTINCT p.product_id, p.manufacturer_id, p.af_values ";
    $sql .= "FROM " . DB_PREFIX . "product p ";
    $sql .= "INNER JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id ";

    if (!empty($category_ids)) {
      if (!empty($data['filter_sub_category'])) {
        $sql .= "INNER JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id = p2c.product_id ";
        $sql .= "INNER JOIN " . DB_PREFIX . "category_path cp ON cp.category_id = p2c.category_id ";
        $sql .= "AND cp.path_id IN (" . implode(',', $category_ids) . ") ";
      } else {
        $sql .= "INNER JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id = p2c.product_id ";
        $sql .= "AND p2c.category_id IN (" . implode(',', $category_ids) . ") ";
      }
    }

    $sql .= "WHERE p.status = 1 AND p.date_available <= NOW() AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' ";

    $this->db->query($sql);
    self::$tmp_table_filter_status = 1;
  }

  public function prepareTable($data)
  {
    // Очищуємо стару таблицю, щоб уникнути проблем зі застарілими даними сортування
    $this->db->query("DROP TEMPORARY TABLE IF EXISTS `" . DB_PREFIX . "af_temporary` ");
    self::$tmp_table_status = 0;

    $price_func = (isset($data['order']) && strtoupper($data['order']) == 'DESC') ? 'MAX' : 'MIN';

    // Отримуємо всі параметри фільтрації (option + attribute)
    $params = $this->getParamsToArray();
    $af_filter_sql = '';

    if (!empty($params)) {
      $af_filter_sql = $this->getParamsQuery($params, "p");
      if (!empty($af_filter_sql)) {
        $af_filter_sql = ' AND (' . $af_filter_sql . ')';
      }
    }

    $customerGroupId = (int)$this->getCustomerGroupId();

    // Початок SQL
    $sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `" . DB_PREFIX . "af_temporary` (PRIMARY KEY (`product_id`)) ";
    $sql .= "SELECT 
                p.product_id, 
                p.manufacturer_id, 
                p.af_values,
                final_prices.price,
                final_prices.quantity,
                final_prices.calc_final_price,
                final_prices.discount,
                final_prices.discount_percentage,
                final_prices.special
            FROM " . DB_PREFIX . "product p
            LEFT JOIN (
                SELECT 
                    pq_inner.product_id,
                    {$price_func}(pq_inner.price) as price,
                    (SELECT GREATEST(IFNULL(SUM(pq.quantity), 0), IFNULL(p_inner.quantity, 0)) FROM " . DB_PREFIX . "product_quantity pq WHERE pq.product_id = p_inner.product_id) as quantity,
                    {$price_func}(pd_inner.price) as discount,
                    MAX(pd_inner.percentage) as discount_percentage,
                    {$price_func}(ps_inner.price) as special,
                    CAST(IFNULL({$price_func}(
                        CASE 
                            WHEN ps_inner.price > 0 THEN ps_inner.price 
                            WHEN (pd_inner.price > 0 AND (pd_inner.options_value IS NULL OR pd_inner.options_value = '' OR pd_inner.options_value = pq_inner.options_value)) AND pq_inner.quantity > 0 THEN pd_inner.price 
                            WHEN (pd_inner.percentage > 0 AND (pd_inner.options_value IS NULL OR pd_inner.options_value = '' OR pd_inner.options_value = pq_inner.options_value)) AND pq_inner.quantity > 0 THEN (pq_inner.price * (1 - pd_inner.percentage / 100))
                            WHEN pq_inner.price > 0 AND pq_inner.quantity > 0 THEN pq_inner.price 
                            ELSE NULL 
                        END
                    ), p_inner.price) AS DECIMAL(15,4)) as calc_final_price
                FROM " . DB_PREFIX . "product p_inner
                LEFT JOIN " . DB_PREFIX . "product_quantity pq_inner ON pq_inner.product_id = p_inner.product_id
                LEFT JOIN " . DB_PREFIX . "product_discount pd_inner ON pd_inner.product_id = p_inner.product_id 
                    AND pd_inner.customer_group_id = '$customerGroupId'
                    AND pd_inner.quantity <= '1'
                LEFT JOIN " . DB_PREFIX . "product_special ps_inner ON ps_inner.product_id = p_inner.product_id 
                    AND ps_inner.customer_group_id = '$customerGroupId'
                    AND (ps_inner.date_start IS NULL OR ps_inner.date_start = '0000-00-00' OR ps_inner.date_start < NOW()) 
                    AND (ps_inner.date_end IS NULL OR ps_inner.date_end = '0000-00-00' OR ps_inner.date_end > NOW())
                GROUP BY p_inner.product_id
            ) as final_prices ON p.product_id = final_prices.product_id
    LEFT JOIN " . DB_PREFIX . "product_quantity pq ON (p.product_id = pq.product_id) ";
    
    // Приєднання категорій
    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);
      
      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= "INNER JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id = p2c.product_id ";
          $sql .= "INNER JOIN " . DB_PREFIX . "category_path cp ON cp.category_id = p2c.category_id ";
          $sql .= "AND cp.path_id IN (" . implode(',', $category_ids) . ") ";
        } else {
          $sql .= "INNER JOIN " . DB_PREFIX . "product_to_category p2c ON p.product_id = p2c.product_id ";
          $sql .= "AND p2c.category_id IN (" . implode(',', $category_ids) . ") ";
        }
      }
    }

    // Опис
    $sql .= "INNER JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id ";
    $sql .= "INNER JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "') ";

    // WHERE — статус і мова
    $sql .= " WHERE p.status = 1 AND p.date_available <= NOW() AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    if (!empty($data['filter_manufacturer_id'])) {
      $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "' ";
    }

    if (!empty($data['filter_special'])) {
      $sql .= " AND p.product_id IN (SELECT ps.product_id FROM " . DB_PREFIX . "product_special ps WHERE ps.customer_group_id = '" . (int)$this->getCustomerGroupId() . "' AND (ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))";
    }

    // Фильтр по имени и тегам
    if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
      $sql .= " AND (";

      $search_conditions = [];

      // Условия для поиска по имени
      if (!empty($data['filter_name'])) {
        $name_conditions = [];
        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

        foreach ($words as $word) {
          $name_conditions[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
        }

        $fullPhraseCondition = "pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        array_unshift($name_conditions, $fullPhraseCondition);

        $search_conditions[] = "(" . implode(" OR ", $name_conditions) . ")";

        // Условия для точного поиска по SKU или EAN
        $loweredName = $this->db->escape(utf8_strtolower($data['filter_name']));
        $search_conditions[] = "LCASE(pq.sku) = '" . $loweredName . "'";
        $search_conditions[] = "LCASE(pq.ean) = '" . $loweredName . "'";

        // Условия для поиска в описании
        if (!empty($data['filter_description'])) {
          $search_conditions[] = "pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
      }

      // Условия для поиска по SKU или EAN в таблице product_quantity через JOIN
      if (isset($data['filter_search']) && !empty($data['filter_search'])) {
        $search_conditions[] = "pd.name LIKE '%" . $this->db->escape($data['filter_search']) . "%'";
        $search_conditions[] = "pq.sku LIKE '%" . $this->db->escape($data['filter_search']) . "%'";
        $search_conditions[] = "pq.ean LIKE '%" . $this->db->escape($data['filter_search']) . "%'";
      }

      // Условия для поиска по тегу
      if (!empty($data['filter_tag'])) {
        $escapedTag = $this->db->escape(utf8_strtolower($data['filter_tag']));
        $search_conditions[] = "LOWER(pd.tag) LIKE '%$escapedTag%'";
      }

      $sql .= implode(" OR ", $search_conditions);
      $sql .= ")";
    }

    // Тут додаємо фільтр по af_values (FIND_IN_SET)
    $sql .= $af_filter_sql;

    $sql .= " GROUP BY p.product_id";

    // Запускаємо створення тимчасової таблиці
    $this->db->query($sql);
    self::$tmp_table_status = 1;

  }

  public function getParamsQuery(array $params, string $table_name = 'p'): string
  {
    $implode = array();

    foreach ($params as $type => $param) {
      if (file_exists(DIR_APPLICATION . 'model/extension/' . $this->codename . '/' . $type . '.php')) {

        $this->load->model('extension/' . $this->codename . '/' . $type);

        $result = $this->{'model_extension_' . $this->codename . '_' . $type}->getTotalQuery($param, $table_name);

        if (!empty($result)) {
          $implode[] = $result;
        }
      }
    }
    if (!empty($implode)) {
      $sql = implode(' AND ', $implode);
    } else {
      $sql = "";
    }

    return $sql;
  }


// Допоміжний метод для перевірки валідності опцій
  protected function hasValidOptions(array $options): bool
  {
    foreach ($options as $group_id => $values) {
      $values = (array)$values;
      foreach ($values as $value) {
        if ($value !== '' && $value !== null) {
          return true;
        }
      }
    }
    return false;
  }

  public function getTypes()
  {
    $dir = DIR_APPLICATION . 'controller/extension/' . $this->codename . '/*.php';

    $files = glob($dir);

    $type_data = array();

    foreach ($files as $file) {

      $type_data[] = basename($file, '.php');

    }

    return $type_data;
  }

  public function getParamsToArray()
  {

    $result = array();

    $types = $this->getTypes();

    if (isset($this->request->get['filter'])) {
      $params = $this->request->get['filter'];

//      $hash = md5(json_encode($params));
//      $result = $this->cache->get('af-url-params.' . $hash);
//      if (!$result) {
      foreach ($types as $type) {
        if (file_exists(DIR_APPLICATION . '/controller/extension/d_ajax_filter/' . $type . '.php')) {
          $output = $this->load->controller('extension/' . $this->codename . '/' . $type . '/url', $params);
          if (!empty($output)) {
            $result[$type] = $output;
          }
        }
      }
//        $this->cache->set('af-url-params.' . $hash, $result);
//      }

    } else {
      foreach ($types as $type) {
        if (isset($this->request->post[$type])) {
          $result[$type] = $this->request->post[$type];
        }
      }
    }

    return $result;
  }

  public function getUrlParams()
  {
    $result = array();

    $types = $this->getTypes();
    $params = $this->getParamsToArray();

    if (!empty($params)) {
      foreach ($params as $type => $param) {
        if (file_exists(DIR_APPLICATION . '/controller/extension/d_ajax_filter/' . $type . '.php')) {
          $output = $this->load->controller('extension/' . $this->codename . '/' . $type . '/rewrite', $param);
          if (!empty($output)) {
            $result = array_merge($result, $output);
          }
        }
      }
    }

    if (!empty($result)) {
      $result = 'ajaxfilter=' . implode('/', $result);
    } else {
      $result = '';
    }

    return $result;
  }

  public function getTranslit($text, $type, $group_id)
  {

    $sql = "SELECT * FROM `" . DB_PREFIX . "af_translit` WHERE `type` = '" . $type . "' AND `group_id` = '" . $group_id . "' AND `text`IN (" . implode(',', $text) . ") GROUP BY `value`";

    $hash = md5($sql);

    $result = $this->cache->get('af-translit.' . $hash);

    if (!$result) {
      $query = $this->db->query($sql);
      $result = $query->rows;
      $this->cache->set('af-translit.' . $hash, $result);
    }
    $translit_data = array();

    if (!empty($result)) {
      $translit_data = array_map(function ($val) {
        if (isset($val['value'])) {
          return $val['value'];
        }
      }, $result);
    }

    return $translit_data;
  }

  public function setTranslit($text, $type, $group_id, $value)
  {

    $text = $this->translit($text);

    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "af_translit` WHERE 
            `type` = '" . $type . "' AND 
            `group_id` = '" . (int)$group_id . "' AND 
            `value` = '" . (int)$value . "' AND 
            `language_id` = '" . (int)$this->config->get('config_language_id') . "' AND 
            `text` = '" . $this->db->escape($text) . "'");

    if (!$query->num_rows) {
      $this->db->query("DELETE FROM `" . DB_PREFIX . "af_translit` WHERE 
                `type` = '" . $type . "' AND 
                `group_id` = '" . (int)$group_id . "' AND 
                `value` = '" . (int)$value . "' AND 
                `language_id` = '" . (int)$this->config->get('config_language_id') . "'");
      $this->db->query("INSERT INTO `" . DB_PREFIX . "af_translit` SET 
                `type` = '" . $type . "', 
                `group_id` = '" . $group_id . "', 
                `value` = '" . $value . "', 
                `language_id` = '" . (int)$this->config->get('config_language_id') . "',
                `text` = '" . $this->db->escape($text) . "'");
    }

    return $text;
  }

  public function translit($text)
  {

    $translit_data = $this->config->get('d_ajax_filter_translit');

    if (empty($translit_data)) {
      $this->config->load('d_ajax_filter_translit');
      $translit_data = $this->config->get('d_ajax_filter_translit');
    }
    $text = strtr($text, $translit_data['translit_symbol']);
    $text = mb_strtolower(trim(preg_replace('/-+/', '-', preg_replace('/ +/', '-', $text)), '-'), 'utf-8');

    return $text;
  }

  public function getFitlerData()
  {
    $filter_data = array();

    if (!empty($this->request->get['curRoute'])) {
      $route = $this->request->get['curRoute'];
    } elseif (!empty($this->request->post['curRoute'])) {
      $route = $this->request->post['curRoute'];
    } elseif (isset($this->request->get['route'])) {
      $route = $this->request->get['route'];
    } else {
      $route = 'common/home';
    }

    if ($route == 'extension/module/d_ajax_filter/ajax' || $route == 'product/category'){
      if (isset($this->request->get['path'])) {
        $path = $this->request->get['path'];
      } elseif (isset($this->request->post['path'])) {
        $path = $this->request->post['path'];
      } else {
        $path = '';
      }

      if ($path) {
        $parts = explode('_', (string)$path);
        $category_id = (int)array_pop($parts);
      } else {
        $category_id = 0;
      }

      $filter_data = array(
        'filter_category_id' => $category_id,
      );
      if ($this->common_setting['display_sub_category']) {
        $filter_data['filter_sub_category'] = true;
      }

      if (isset($this->request->get['filter'])) {
        $filter_data['filter'] = $this->request->get['filter'];
      } elseif (isset($this->request->post['filter'])) {
        $filter_data['filter'] = $this->request->post['filter'];
      } else {
        $filter_data['filter'] = '';
      }
    }

    if ($route == 'product/special') {
      $filter_data = array(
        'filter_special' => true
      );
    }
    if ($route == 'product/manufacturer/info' || $route == 'manufacturer') {
      if (isset($this->request->get['manufacturer_id'])) {
        $manufacturer_id = $this->request->get['manufacturer_id'];
      } elseif (isset($this->request->post['manufacturer_id'])) {
        $manufacturer_id = $this->request->post['manufacturer_id'];
      } else {
        $manufacturer_id = '';
      }
      $filter_data = array(
        'filter_manufacturer_id' => $manufacturer_id
      );
    }

    if ($route == 'product/search' || $route == 'search') {
      if (isset($this->request->get['search'])) {
        $search = $this->request->get['search'];
      } elseif (isset($this->request->post['q'])) {
        $search = $this->request->post['q'];
      } else {
        $search = '';
      }
      
      if (isset($this->request->get['tag'])) {
        $tag = $this->request->get['tag'];
      } elseif (isset($this->request->post['q'])) {
        $tag = $this->request->post['q'];
      } else {
        $tag = $search;
      }

      if (isset($this->request->get['description'])) {
        $description = $this->request->get['description'];
      } else {
        $description = '';
      }
      $filter_data = array(
        'filter_name' => $search,
        'filter_tag' => $tag,
        'filter_description' => $description
      );
    }

    if (isset($this->request->get['category_id'])) {
      $filter_data['filter_category_id'] = $this->request->get['category_id'];
    } elseif (isset($this->request->post['category_id'])) {
      $filter_data['filter_category_id'] = $this->request->post['category_id'];
    }

    return $filter_data;
  }


  public function getURLQuery()
  {
    $implode = array();
    if (isset($this->request->get['route'])) {
      $implode[] = 'curRoute=' . $this->request->get['route'];
    }
    if (isset($this->request->get['path'])) {
      $implode[] = 'path=' . $this->request->get['path'];
    }
    if (isset($this->request->get['search'])) {
      $implode[] = 'search=' . $this->request->get['search'];
    }
    if (isset($this->request->get['tag'])) {
      $implode[] = 'tag=' . $this->request->get['tag'];
    }
    if (isset($this->request->get['category_id'])) {
      $implode[] = 'category_id=' . $this->request->get['category_id'];
    }
    if (isset($this->request->get['manufacturer_id'])) {
      $implode[] = 'manufacturer_id=' . $this->request->get['manufacturer_id'];
    }
    if (isset($this->request->get['description'])) {
      $implode[] = 'description=' . $this->request->get['description'];
    }
    if (isset($this->request->get['sub_category'])) {
      $implode[] = 'sub_category=' . $this->request->get['sub_category'];
    }
    if (isset($this->request->get['limit'])) {
      $implode[] = 'limit=' . $this->request->get['limit'];
    }
    if (isset($this->request->get['filter'])) {
      $implode[] = 'filter=' . $this->request->get['filter'];
    }
    if (isset($this->request->get['sort'])) {
      $implode[] = 'sort=' . $this->request->get['sort'];
    }
    if (isset($this->request->get['order'])) {
      $implode[] = 'order=' . $this->request->get['order'];
    }

    if (count($implode) > 0) {
      $result = implode('&', $implode);
    } else {
      $result = '';
    }
    return $result;
  }

  public function getUrl($route)
  {
    $query = array();
    if (isset($this->request->get['path'])) {
      $query[] = 'path=' . $this->request->get['path'];
    }
    if (isset($this->request->get['search'])) {
      $query[] = 'search=' . $this->request->get['search'];
    }
    if (isset($this->request->get['tag'])) {
      $query[] = 'tag=' . $this->request->get['tag'];
    }
    if (isset($this->request->get['category_id'])) {
      $query[] = 'category_id=' . $this->request->get['category_id'];
    }
    if (isset($this->request->get['manufacturer_id'])) {
      $query[] = 'manufacturer_id=' . $this->request->get['manufacturer_id'];
    }
    if (isset($this->request->get['description'])) {
      $query[] = 'description=' . $this->request->get['description'];
    }
    if (isset($this->request->get['sub_category'])) {
      $query[] = 'sub_category=' . $this->request->get['sub_category'];
    }
    if (isset($this->request->get['limit'])) {
      $query[] = 'limit=' . $this->request->get['limit'];
    }
    if (isset($this->request->get['filter'])) {
      $query[] = 'filter=' . $this->request->get['filter'];
    }
    if (isset($this->request->get['sort'])) {
      $query[] = 'sort=' . $this->request->get['sort'];
    }
    if (isset($this->request->get['order'])) {
      $query[] = 'order=' . $this->request->get['order'];
    }

    //++Andrey тут посмотреть формирования SEO url

    $params = $this->getUrlParams();

    if (is_array($params)) {
      $query_params = array();
      if (!empty($params)) {
        $query_params[] = $params;
      }
      $query_params = implode('&', $query_params);
    } else {
      if (!empty($params)) {
        $query_params = '&' . $params;
      } else {
        $query_params = $params;
      }

    }
    $query = implode('&', $query);

    if (!empty($query)) {
      $url = $this->url->link($route, $query . $query_params, 'SSL');
    } else {
      $url = $this->url->link($route, $query_params, 'SSL');
    }

    $url = str_replace('&amp;', '&', $url);
    return $url;
  }

  public function convertResultTotal($data)
  {
    $output = array();

    if (count($data)) {
      foreach ($data as $row) {
        if (!isset($output[$row['type']])) {
          $output[$row['type']] = array();
        }
        if (!isset($output[$row['type']][$row['id']])) {
          $output[$row['type']][$row['id']] = array();
        }
        $output[$row['type']][$row['id']][$row['val']] = $row['c'];

      }
    }
    return $output;
  }

  public function mergeTotal($in, $out)
  {
    foreach ($out as $type => $groups) {
      foreach ($groups as $group_id => $values) {
        foreach ($values as $value => $total) {
          if (!isset($in[$type])) {
            $in[$type] = array();
          }
          if (!isset($in[$type][$group_id])) {
            $in[$type][$group_id] = array();
          }
          $in[$type][$group_id][$value] = $total;
        }
      }
    }
    return $in;
  }

  public function getProductsQuery($price_status = false)
  {
    $sql = "SELECT aft.*, aft.calc_final_price AS af_price ";
    $sql .= " FROM `" . DB_PREFIX . "af_temporary` aft ";
    $sql .= "INNER JOIN `" . DB_PREFIX . "product` p2 ON aft.product_id = p2.product_id";
    $sql .= " GROUP BY aft.product_id";

    return $sql;
  }

  public function getQuantity($status)
  {
    $result = array();
    foreach ($status as $type) {
      if (file_exists(DIR_APPLICATION . '/controller/extension/d_ajax_filter/' . $type . '.php')) {
        $output = $this->load->controller('extension/' . $this->codename . '/' . $type . '/quantity');
        if (!empty($output)) {
          $result[$type] = $output;
        }
      }
    }
    return $result;
  }

  public function getTotalQuantityProducts($data)
  {
    $this->prepareTable($data);

    $total_query = $this->getProductsQuery(true);

    $sql = "SELECT p.product_id, aft.discount, aft.special, aft.af_price,
        (SELECT pq.price FROM " . DB_PREFIX . "product_quantity pq WHERE pq.product_id = p.product_id GROUP BY pq.product_id ORDER BY pq.price ASC LIMIT 1) AS price
        FROM (" . $total_query . ") aft
        INNER JOIN `" . DB_PREFIX . "product` p ON aft.product_id = p.product_id
        INNER JOIN `" . DB_PREFIX . "product_description` pd ON pd.product_id = p.product_id
        WHERE pd.language_id = '" . $this->config->get('config_language_id') . "'";

    // Додатковий фільтр за категорією
    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);
      
      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= " AND p.product_id IN (
                    SELECT p2c.product_id
                    FROM `" . DB_PREFIX . "product_to_category` p2c
                    INNER JOIN `" . DB_PREFIX . "category_path cp ON cp.category_id = p2c.category_id
                    WHERE cp.path_id IN (" . implode(',', $category_ids) . "))";
        } else {
          $sql .= " AND p.product_id IN (SELECT product_id FROM `" . DB_PREFIX . "product_to_category` WHERE category_id IN (" . implode(',', $category_ids) . "))";
        }
      }
    }

    if (!empty($data['filter_manufacturer_id'])) {
      $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
    }

    //++Andrey
    // тут получает списов вібраних свойств
    $params = $this->getParamsToArray();

    if (!empty($params)) {
      $result = $this->getParamsQuery($params, 'aft');
      if (!empty($result)) {
        $sql .= " AND " . $result;
      }
    }

    $sql .= " GROUP BY p.product_id";

    $sort_data = array(
      'pd.name',
      'p.quantity',
      'price',
      'rating',
      'p.sort_order',
      'p.date_added'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
        $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
      } elseif ($data['sort'] == 'price') {
        $sql .= " ORDER BY CASE WHEN special IS NOT NULL AND special > 0 THEN special WHEN discount IS NOT NULL AND discount > 0 THEN discount ELSE price END, price";
      } else {
        $sql .= " ORDER BY " . $data['sort'];
      }
    } else {
      $sql .= " ORDER BY p.sort_order";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
      $sql .= " DESC";
    } else {
      $sql .= " ASC";
    }

    $query = $this->db->query($sql);
    return $query->num_rows;
  }

  public function prepareAjaxFilter($data, $sql, $applyLimit = true)
  {
    $customerGroupId = (int)$this->getCustomerGroupId();

    // Створюємо тимчасову таблицю з урахуванням усіх фільтрів
    $this->prepareTable($data);

    // Отримуємо SQL-запит до тимчасової таблиці з обрахованою ціною
    $total_query = $this->getProductsQuery(true);

    if ($applyLimit) {
        $sql = "SELECT
                p.product_id,
                p.sort_order,
                aft.discount,
                aft.discount_percentage,
                aft.special,
                aft.af_price,
                aft.quantity
              FROM (" . $total_query . ") aft
              INNER JOIN `" . DB_PREFIX . "product` p ON aft.product_id = p.product_id
              INNER JOIN `" . DB_PREFIX . "product_description` pd ON pd.product_id = p.product_id
              WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
    } else {
        // Для підрахунку загальної кількості достатньо працювати тільки з тимчасовою таблицею
        $sql = $total_query;
    }

    // Додатковий фільтр за категорією
    if ($applyLimit && !empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);
      
      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= " AND p.product_id IN (
                    SELECT p2c.product_id
                    FROM `" . DB_PREFIX . "product_to_category` p2c
                    INNER JOIN `" . DB_PREFIX . "category_path cp ON cp.category_id = p2c.category_id
                    WHERE cp.path_id IN (" . implode(',', $category_ids) . "))";
        } else {
          $sql .= " AND p.product_id IN (
                    SELECT product_id
                    FROM `" . DB_PREFIX . "product_to_category`
                    WHERE category_id IN (" . implode(',', $category_ids) . "))";
        }
      }
    }

    if ($applyLimit && !empty($data['filter_manufacturer_id'])) {
      $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "' ";
    }

    $sort_data = [
      'pd.name', 'price', 'p.sort_order', 'p.date_added', 'p.model', 'p.quantity', 'rating'
    ];

    if ($applyLimit && isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      $sql .= " ORDER BY (aft.quantity > 0) DESC, ";
      
      // Якщо сортування за ціною — прибираємо пріоритет акцій, залишаємо тільки наявність
      if ($data['sort'] == 'price') {
        $sql .= "aft.af_price";
      } else {
        // Для інших видів сортування (назва, замовчування) залишаємо акції першими
        $sql .= "(IFNULL(aft.special, 0) > 0 OR IFNULL(aft.discount, 0) > 0 OR IFNULL(aft.discount_percentage, 0) > 0) DESC, ";
        
        if ($data['sort'] == 'pd.name') {
          $sql .= "LCASE(pd.name)";
        } elseif ($data['sort'] == 'p.sort_order') {
          $sql .= "p.sort_order ASC, LCASE(pd.name) ASC";
        } elseif ($data['sort'] == 'p.date_added') {
          $sql .= "p.date_added DESC";
        } else {
          $sql .= $data['sort'];
        }
      }

      if (!in_array($data['sort'], ['p.sort_order', 'p.date_added'])) {
        $sql .= (isset($data['order']) && $data['order'] == 'DESC') ? " DESC" : " ASC";
      }
      $sql .= ", p.product_id DESC";
    } elseif ($applyLimit) {
      $sql .= " ORDER BY (aft.quantity > 0) DESC, (IFNULL(aft.special, 0) > 0 OR IFNULL(aft.discount, 0) > 0 OR IFNULL(aft.discount_percentage, 0) > 0) DESC, p.sort_order ASC, aft.af_price ASC, p.product_id DESC";
    }

    //Ліміти
    if ($applyLimit && (isset($data['start']) || isset($data['limit']))) {
      $start = max(0, (int)$data['start']);
      $limit = max(1, (int)$data['limit']);
      $sql .= " LIMIT {$start},{$limit}";
    }

    return $sql;
  }


  public function getValue(string $type, int $value, $group_id = 0)
  {
    $query = $this->db->query("SELECT af_value_id FROM " . DB_PREFIX . "af_values WHERE `value` = '" . (int)$value . "' AND `type` = '" . $this->db->escape($type) . "' AND `group_id` = '" . (int)$group_id . "' LIMIT 1");

    return $query->num_rows ? $query->row['af_value_id'] : null;
  }

//  public function getRiotTags()
//  {
//    $result = array();
//    $files = glob(DIR_APPLICATION . 'view/theme/default/template/extension/d_ajax_filter/component/*.tag', GLOB_BRACE);
//    foreach ($files as $file) {
//      $result[] = 'catalog/view/theme/default/template/extension/d_ajax_filter/component/' . basename($file) . '?' . rand();
//    }
//
//    $files = glob(DIR_APPLICATION . 'view/theme/default/template/extension/d_ajax_filter/group/*.tag', GLOB_BRACE);
//    foreach ($files as $file) {
//      $result[] = 'catalog/view/theme/default/template/extension/d_ajax_filter/group/' . basename($file) . '?' . rand();
//    }
//    return $result;
//  }

  private function getCustomerGroupId()
  {
    return $this->customer->isLogged() ? $this->customer->getGroupId() : $this->config->get('config_customer_group_id');
  }

  public function sort_values($values, $type = 'default')
  {
    if ($type == 'default') {
      return $values;
    }

    switch ($type) {
      case 'string_asc':

        uasort($values, function ($a, $b) {
          return strcmp($a['name'], $b['name']);
        });

        break;
      case 'string_desc':
        uasort($values, function ($a, $b) {
          return (-1) * strcmp($a['name'], $b['name']);
        });
        break;
      case 'numeric_asc':
        uasort($values, function ($a, $b) {
          return strnatcmp($a['name'], $b['name']);
        });
        break;
      case 'numeric_desc':
        uasort($values, function ($a, $b) {
          return (-1) * strnatcmp($a['name'], $b['name']);
        });
        break;
    }
    return $values;
  }

  public function addMoreValuesItem($values, $type, $group_id)
  {
    $lang = (int)$this->config->get('config_language_id');
    foreach ($values as &$val) {
      $translit_val = $this->getTranslitItem($type, $group_id, $val['value'], $lang);
      if ($translit_val != null) {
        $val['slug'] = rawurlencode($translit_val['text']);
      } else {
        $val['slug'] = rawurlencode($this->setTranslit($val['name'], $type, $group_id, $val['value']));
      }
      unset($val);
    }
    return $values;
  }

  public function getTranslitItem($type, $group_id, $value, $language_id)
  {
    $sql = "SELECT * FROM `" . DB_PREFIX . "af_translit` WHERE `type` = '" . $type . "' AND `group_id` = '" . $group_id . "' AND `value` = '" . $value . "' AND `language_id` = '" . $language_id . "' GROUP BY `value`";
    $query = $this->db->query($sql);
    return $query->row;
  }

  /* gets the data from a URL */
  public function get_data($url)
  {
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }

  /*
  * Format the link to work with ajax requests
  */
  public function ajax($link)
  {
    return str_replace('&amp;', '&', $link);
  }
}
