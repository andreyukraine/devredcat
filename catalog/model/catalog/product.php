<?php

class ModelCatalogProduct extends Model
{

  public function updateProductQtyStore($ean, $qty, $warehouse_id)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "product_quantity SET quantity = '" . $qty . "' WHERE ean = '" . $ean . "' AND warehouse_id = '" . (int)$warehouse_id . "'");
  }

  public function updateViewed($product_id)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");

    if ($product_id) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "module` WHERE `code` LIKE 'ocproductviews'");

      if ($query->num_rows) {
        $product_ids = [];

        if (isset($this->request->cookie['ocproductviews'])) {
          $product_ids = explode(',', $this->request->cookie['ocproductviews']);
        } elseif (isset($this->session->data['ocproductviews'])) {
          $product_ids = $this->session->data['ocproductviews'];
        }

        if (isset($this->request->cookie['viewed'])) {
          $product_ids = array_merge($product_ids, explode(',', $this->request->cookie['viewed']));
        } elseif (isset($this->session->data['viewed'])) {
          $product_ids = array_merge($product_ids, $this->session->data['viewed']);
        }

        $product_ids = array_diff($product_ids, [(int)$product_id]);

        array_unshift($product_ids, (int)$product_id);

        $pr_ids = array_slice($product_ids, 0, 20);

        setcookie('ocproductviews', implode(',', $pr_ids), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
      }
    }
  }

  public function getProductByEan($ean)
  {
    $query = $this->db->query("SELECT pq.*, pd.name FROM " . DB_PREFIX . "product_quantity pq LEFT JOIN " . DB_PREFIX . "product_description pd ON (pq.product_id = pd.product_id) WHERE pq.ean = '" . $this->db->escape($ean) . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1");

    return $query->row;
  }


  public function getProduct($product_id)
  {
    $query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, m.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

    if ($query->num_rows) {
      return array(
        'product_id' => $query->row['product_id'],
        'name' => $query->row['name'],
        'description' => $query->row['description'],
        'composition' => $query->row['composition'],
        'normi' => $query->row['normi'],
        'meta_title' => $query->row['meta_title'],
        'meta_description' => $query->row['meta_description'],
        'meta_keyword' => $query->row['meta_keyword'],
        'tag' => $query->row['tag'],
        'sku' => $query->row['sku'],
        'ean' => $query->row['ean'],
        'jan' => $query->row['jan'],
        'mpn' => $query->row['mpn'],
        'calc' => $query->row['calc'],
        'price_group_id' => $query->row['price_group_id'],
        'location' => $query->row['location'],
        'quantity' => $query->row['quantity'],
        'stock_status' => $query->row['stock_status'],
        'image' => $query->row['image'],
        'manufacturer_id' => $query->row['manufacturer_id'],
        'manufacturer' => $query->row['manufacturer'],
        'price' => ($query->row['price'] ? $query->row['price'] : $query->row['discount']),
        'special' => $query->row['special'],
        'reward' => $query->row['reward'],
        'points' => $query->row['points'],
        'tax_class_id' => $query->row['tax_class_id'],
        'date_available' => $query->row['date_available'],
        'weight' => $query->row['weight'],
        'weight_class_id' => $query->row['weight_class_id'],
        'length' => $query->row['length'],
        'width' => $query->row['width'],
        'height' => $query->row['height'],
        'length_class_id' => $query->row['length_class_id'],
        'subtract' => $query->row['subtract'],
        'rating' => round(is_numeric($query->row['rating']) ? $query->row['rating'] : 0),
        'reviews' => $query->row['reviews'] ? $query->row['reviews'] : 0,
        'minimum' => $query->row['minimum'],
        'sort_order' => $query->row['sort_order'],
        'status' => $query->row['status'],
        'date_added' => $query->row['date_added'],
        'date_modified' => $query->row['date_modified'],
        'viewed' => $query->row['viewed'],
        'product_status_id' => $query->row['product_status_id']
      );
    } else {
      return false;
    }
  }

  public function getProductsByIds($product_ids)
  {
    $product_data = array();

    if (empty($product_ids)) {
      return $product_data;
    }

    $ids = array_map('intval', $product_ids);

    $sql = "SELECT p.*, pd.name AS name, pd.description, pd.composition, pd.normi, pd.tag, pd.meta_title, pd.meta_description, pd.meta_keyword, p.image, m.name AS manufacturer, 
            (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, 
            (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, 
            (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, 
            (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, 
            (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, 
            (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, 
            (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, 
            (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews
            FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) 
            WHERE p.product_id IN (" . implode(',', $ids) . ") 
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND p.status = '1' 
            AND p.date_available <= NOW() 
            AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
            ORDER BY FIELD(p.product_id, " . implode(',', $ids) . ")";

    $query = $this->db->query($sql);

    foreach ($query->rows as $row) {
      $product_data[$row['product_id']] = array(
        'product_id' => $row['product_id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'composition' => $row['composition'],
        'normi' => $row['normi'],
        'meta_title' => $row['meta_title'],
        'meta_description' => $row['meta_description'],
        'meta_keyword' => $row['meta_keyword'],
        'tag' => $row['tag'],
        'sku' => $row['sku'],
        'ean' => $row['ean'],
        'jan' => $row['jan'],
        'mpn' => $row['mpn'],
        'calc' => $row['calc'],
        'price_group_id' => $row['price_group_id'],
        'location' => $row['location'],
        'quantity' => $row['quantity'],
        'stock_status' => $row['stock_status'],
        'image' => $row['image'],
        'manufacturer_id' => $row['manufacturer_id'],
        'manufacturer' => $row['manufacturer'],
        'price' => ($row['price'] ? $row['price'] : $row['discount']),
        'special' => $row['special'],
        'reward' => $row['reward'],
        'points' => $row['points'],
        'tax_class_id' => $row['tax_class_id'],
        'date_available' => $row['date_available'],
        'weight' => $row['weight'],
        'weight_class_id' => $row['weight_class_id'],
        'length' => $row['length'],
        'width' => $row['width'],
        'height' => $row['height'],
        'length_class_id' => $row['length_class_id'],
        'subtract' => $row['subtract'],
        'rating' => round(is_numeric($row['rating']) ? $row['rating'] : 0),
        'reviews' => $row['reviews'] ? $row['reviews'] : 0,
        'minimum' => $row['minimum'],
        'sort_order' => $row['sort_order'],
        'status' => $row['status'],
        'date_added' => $row['date_added'],
        'date_modified' => $row['date_modified'],
        'viewed' => $row['viewed'],
        'product_status_id' => $row['product_status_id']
      );
    }

    return $product_data;
  }

  public function getTabProducts($data = array())
  {
    $price_func = (isset($data['order']) && strtoupper($data['order']) == 'DESC') ? 'MAX' : 'MIN';

    $sql = "SELECT 
            p.product_id, 
            IFNULL({$price_func}(pq.price), p.price) AS price, 
            GREATEST(IFNULL(SUM(pq.quantity), 0), IFNULL(p.quantity, 0)) AS quantity2, 
            AVG(r1.rating) AS rating, 
            {$price_func}(pd2.price) AS discount, 
            {$price_func}(ps.price) AS special,
            {$price_func}(
                CASE 
                    WHEN ps.price > 0 THEN ps.price 
                    WHEN pd2.price > 0 THEN pd2.price 
                    WHEN pq.price > 0 THEN pq.price 
                    ELSE p.price 
                END
            ) AS final_price
        FROM " . DB_PREFIX . "product p";

    // Присоединяем таблицы в зависимости от наличия фильтров категории
    $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
          LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
          LEFT JOIN " . DB_PREFIX . "product_quantity pq ON (pq.product_id = p.product_id)
          LEFT JOIN " . DB_PREFIX . "review r1 ON (r1.product_id = p.product_id AND r1.status = '1')
          LEFT JOIN " . DB_PREFIX . "product_discount pd2 ON (pd2.product_id = p.product_id 
               AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
               AND (pd2.date_start IS NULL OR pd2.date_start < NOW()) 
               AND (pd2.date_end IS NULL OR pd2.date_end > NOW()))
          LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id = p.product_id)
          LEFT JOIN " . DB_PREFIX . "category_path cp ON (cp.category_id = p2c.category_id)
          LEFT JOIN " . DB_PREFIX . "product_special ps ON (ps.product_id = p.product_id 
               AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
               AND (ps.date_start IS NULL OR ps.date_start < NOW()) 
               AND (ps.date_end IS NULL OR ps.date_end > NOW()))";

    // Условия WHERE для фильтров
    $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
           AND p.status = '1' 
           AND p.date_available <= NOW() 
           AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);

      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= " AND cp.path_id IN (" . implode(',', $category_ids) . ")";
        } else {
          $sql .= " AND p2c.category_id IN (" . implode(',', $category_ids) . ")";
        }
      }

      if (!empty($data['filter_filter'])) {
        $filters = array_map('intval', explode(',', $data['filter_filter']));
        $sql .= " AND pf.filter_id IN (" . implode(',', $filters) . ")";
      }
    }

    if (!empty($data['filter_manufacturer_id'])) {
      $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
    }

    // Группировка и сортировка
    $sql .= " GROUP BY p.product_id";

    $sort_data = [
      'pd.name', 'price', 'p.sort_order', 'p.date_added'
    ];
    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      $sql .= " ORDER BY (quantity2 > 0) DESC, ";

      if ($data['sort'] == 'price') {
        $sql .= "final_price";
      } else {
        $sql .= "(IFNULL(special, 0) > 0 OR IFNULL(discount, 0) > 0 OR IFNULL(discount_percentage, 0) > 0) DESC, ";

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
    } else {
      $sql .= " ORDER BY (quantity2 > 0) DESC, (IFNULL(special, 0) > 0 OR IFNULL(discount, 0) > 0 OR IFNULL(discount_percentage, 0) > 0) DESC, p.sort_order ASC, final_price ASC, p.product_id DESC";
    }

    // Ограничение количества результатов
    if (isset($data['start']) || isset($data['limit'])) {
      $start = max(0, (int)$data['start']);
      $limit = max(1, (int)$data['limit']);
      $sql .= " LIMIT " . $start . "," . $limit;
    }

    $product_data_tab = [];
    $query2 = $this->db->query($sql);

    foreach ($query2->rows as $result) {
      $product_data_tab[$result['product_id']] = $this->getProduct($result['product_id']);
    }

    return $product_data_tab;
  }

  public function getProducts($data = array())
  {
    $price_func = (isset($data['order']) && strtoupper($data['order']) == 'DESC') ? 'MAX' : 'MIN';

    $sql = "SELECT 
            p.product_id, 
            final_prices.price,
            final_prices.quantity2,
            AVG(r1.rating) AS rating,
            final_prices.discount,
            final_prices.discount_percentage,
            final_prices.special,
            final_prices.final_price
        FROM " . DB_PREFIX . "product p
        LEFT JOIN (
            SELECT 
                pq_inner.product_id,
                {$price_func}(pq_inner.price) as price,
                (SELECT GREATEST(IFNULL(SUM(pq.quantity), 0), IFNULL(p_inner.quantity, 0)) FROM " . DB_PREFIX . "product_quantity pq WHERE pq.product_id = p_inner.product_id) as quantity2,
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
                ), p_inner.price) AS DECIMAL(15,4)) as final_price
            FROM " . DB_PREFIX . "product p_inner
            LEFT JOIN " . DB_PREFIX . "product_quantity pq_inner ON pq_inner.product_id = p_inner.product_id
            LEFT JOIN " . DB_PREFIX . "product_discount pd_inner ON pd_inner.product_id = p_inner.product_id 
                AND pd_inner.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'
                AND pd_inner.quantity <= '1'
            LEFT JOIN " . DB_PREFIX . "product_special ps_inner ON ps_inner.product_id = p_inner.product_id 
                AND ps_inner.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'
                AND (ps_inner.date_start IS NULL OR ps_inner.date_start = '0000-00-00' OR ps_inner.date_start < NOW()) 
                AND (ps_inner.date_end IS NULL OR ps_inner.date_end = '0000-00-00' OR ps_inner.date_end > NOW())
            GROUP BY p_inner.product_id
        ) as final_prices ON p.product_id = final_prices.product_id
        LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
        LEFT JOIN " . DB_PREFIX . "product_quantity pq ON (p.product_id = pq.product_id)
        LEFT JOIN " . DB_PREFIX . "review r1 ON r1.product_id = p.product_id AND r1.status = '1'
        LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON p.product_id = p2s.product_id 
            AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);

      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= " LEFT JOIN " . DB_PREFIX . "category_path cp ON cp.path_id IN (" . implode(',', $category_ids) . ")
                    LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id AND p2c.product_id = p.product_id)";
        } else {
          $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id = p.product_id AND p2c.category_id IN (" . implode(',', $category_ids) . "))";
        }
      }
    }

    // Умови WHERE для фільтрації за статусом та датою доступності
    $sql .= " WHERE p.status = '1' 
           AND p.date_available <= NOW() ";

    // Умови для фільтрації за категорією та фільтрами
    if (!empty($data['filter_category_id'])) {
      if (!empty($data['filter_sub_category'])) {
        if (!empty($category_ids)) {
          $sql .= " AND cp.path_id IN (" . implode(',', $category_ids) . ")";
        }
      } else {
        if (!empty($category_ids)) {
          $sql .= " AND p2c.category_id IN (" . implode(',', $category_ids) . ")";
        }
      }
    }

    if (!empty($data['filter_manufacturer_id'])) {
      $sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
    }

    // Фильтр по имени и тегам
    if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
      $sql .= " AND (";

      // Условия для поиска по имени
      if (!empty($data['filter_name'])) {
        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));
        $conditions = [];

//        foreach ($words as $word) {
//          $conditions[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
//        }

        $fullPhraseCondition = "pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        array_unshift($conditions, $fullPhraseCondition);

        $sql .= " (" . implode(" OR ", $conditions) . ")";

        // Условие для поиска в описании
        if (!empty($data['filter_description'])) {
          $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // Условие для поиска в описании
        if (!empty($data['filter_search'])) {
          $sql .= " OR pq.ean LIKE '%" . $this->db->escape($data['filter_search']) . "%'";
        }
      }

      // Проверка и добавление условия `OR` между именем и тегом, если оба заполнены
      if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
        $sql .= " OR ";
      }

      // Условия для поиска по тегу
      if (!empty($data['filter_tag'])) {
        $tagWords = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));
        $sql .= " (" . implode(" AND ", array_map(function ($word) {
            return "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
          }, $tagWords)) . ")";
      }

      // Условия для точного поиска по SKU или EAN
      if (!empty($data['filter_name'])) {
        $loweredName = $this->db->escape(utf8_strtolower($data['filter_name']));
        $sql .= " OR LCASE(pq.sku) = '" . $loweredName . "'";
        $sql .= " OR LCASE(pq.ean) = '" . $loweredName . "'";
      }

      $sql .= ")";
    }

    // Групування за ID продукту
    $sql .= " GROUP BY p.product_id";

    // Умови сортування
    $sort_data = [
      'pd.name', 'price', 'p.sort_order', 'p.date_added'
    ];
    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      $sql .= " ORDER BY (quantity2 > 0) DESC, ";

      if ($data['sort'] == 'price') {
        $sql .= "final_price";
      } else {
        $sql .= "(IFNULL(special, 0) > 0 OR IFNULL(discount, 0) > 0 OR IFNULL(discount_percentage, 0) > 0) DESC, ";

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
    } else {
      $sql .= " ORDER BY (quantity2 > 0) DESC, (IFNULL(special, 0) > 0 OR IFNULL(discount, 0) > 0 OR IFNULL(discount_percentage, 0) > 0) DESC, p.sort_order ASC, final_price ASC, p.product_id DESC";
    }

    // Обмеження кількості результатів
    if (isset($data['start']) || isset($data['limit'])) {
      $start = max(0, (int)$data['start']);
      $limit = max(1, (int)$data['limit']);
      $sql .= " LIMIT " . $start . "," . $limit;
    }


    $result_mass = [];
    $this->load->model('extension/module/d_ajax_filter');

    if (isset($data['filter'])) {
      // 1. Отримуємо товари з лімітом
      $sql_limited = $this->model_extension_module_d_ajax_filter->prepareAjaxFilter($data, $sql, true);
      $product_query = $this->db->query($sql_limited);

      // 2. Отримуємо загальну кількість за допомогою підзапиту COUNT(*)
      $sql_total = $this->model_extension_module_d_ajax_filter->prepareAjaxFilter($data, $sql, false);
      $count_query = $this->db->query("SELECT COUNT(*) AS total FROM (" . $sql_total . ") AS tmp");
      $result_mass['total_products'] = $count_query->row['total'];
    } else {
      $result_mass['total_products'] = $this->getTotalProducts($data);
      $product_query = $this->db->query($sql);
    }

    $product_ids = array();
    foreach ($product_query->rows as $result) {
      $product_ids[] = $result['product_id'];
    }

    $products_details = $this->getProductsByIds($product_ids);

    foreach ($product_ids as $product_id) {
      if (isset($products_details[$product_id])) {
        $result_mass['products'][] = $products_details[$product_id];
      }
    }

    return $result_mass;
  }

  public function getProductSpecials($data = array())
  {
    $sql = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_discount ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		WHERE p.status = '1' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' GROUP BY ps.product_id";

    $sort_data = array(
      'pd.name',
      'p.model',
      'ps.price',
      'rating',
      'p.sort_order'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
        $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
      } else {
        $sql .= " ORDER BY " . $data['sort'];
      }
    } else {
      $sql .= " ORDER BY p.sort_order";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
      $sql .= " DESC, LCASE(pd.name) DESC";
    } else {
      $sql .= " ASC, LCASE(pd.name) ASC";
    }

    if (isset($data['start']) || isset($data['limit'])) {
      if ($data['start'] < 0) {
        $data['start'] = 0;
      }

      if ($data['limit'] < 1) {
        $data['limit'] = 20;
      }

      $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
    }

    $product_data = array();

    $query = $this->db->query($sql);

    foreach ($query->rows as $result) {
      $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
    }

    return $product_data;
  }

  public function getProductsDiscont($data = array())
  {
    $sql = "SELECT DISTINCT ps.product_id, 
                (SELECT SUM(quantity) FROM " . DB_PREFIX . "product_quantity pq WHERE pq.product_id = ps.product_id AND pq.options = ps.options AND pq.options_value = ps.options_value GROUP BY pq.product_id) AS quantity, 
                (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating 
                FROM " . DB_PREFIX . "product_discount ps 
                LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)";

    // Приєднання для фільтрації по категорії
    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);

      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= " LEFT JOIN " . DB_PREFIX . "category_path cp ON cp.path_id IN (" . implode(',', $category_ids) . ")";
          $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON cp.category_id = p2c.category_id AND p2c.product_id = p.product_id";
        } else {
          $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON p2c.product_id = p.product_id AND p2c.category_id IN (" . implode(',', $category_ids) . ")";
        }
      }
    }

    if (isset($data['customer_group_id'])) {
      $sql .= " WHERE p.status = '1' AND ps.customer_group_id = '" . (int)$data['customer_group_id'] . "'";
    }

    // Добавляем условие для фильтрации по категории
    if (isset($data['filter_category_id'])) {
      if (!empty($data['filter_category_id'])) {
        $sql .= " AND p2c.category_id IS NOT NULL";
      }
    }

    $sql .= " GROUP BY ps.product_id";

    $sort_data = array(
      'pd.name',
      'p.model',
      'ps.price',
      'rating',
      'p.sort_order'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
      if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
        $sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
      } else {
        $sql .= " ORDER BY " . $data['sort'];
      }
    } else {
      $sql .= " ORDER BY p.sort_order";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
      $sql .= " DESC, LCASE(pd.name) DESC";
    } else {
      $sql .= " ASC, LCASE(pd.name) ASC";
    }

    if (isset($data['start']) || isset($data['limit'])) {
      if ($data['start'] < 0) {
        $data['start'] = 0;
      }

      if ($data['limit'] < 1) {
        $data['limit'] = 20;
      }

      $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
    }

    $query = $this->db->query($sql);

    $result_mass = [];

    $sql_total = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_discount ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)";

    // Добавляем фильтрацию по категории и для общего запроса
    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);

      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql_total .= " LEFT JOIN " . DB_PREFIX . "category_path cp ON cp.path_id IN (" . implode(',', $category_ids) . ")";
          $sql_total .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON cp.category_id = p2c.category_id AND p2c.product_id = p.product_id";
        } else {
          $sql_total .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON p2c.product_id = p.product_id AND p2c.category_id IN (" . implode(',', $category_ids) . ")";
        }
      }
    }

    $sql_total .= " WHERE p.status = '1' AND ps.customer_group_id = '" . (int)$data['customer_group_id'] . "'";

    if (!empty($data['filter_category_id'])) {
      $sql_total .= " AND p2c.category_id IS NOT NULL";
    }

    $sql_total .= " GROUP BY ps.product_id";

    $query_total = $this->db->query($sql_total);
    $result_mass['total_products'] = $query_total->num_rows;

    foreach ($query->rows as $result) {
      if ((int)$result['quantity'] > 0) {
        $result_mass['products'][$result['product_id']] = $this->getProduct($result['product_id']);
      }
    }

    return $result_mass;
  }

  public function getLatestProducts($limit)
  {
    $product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

    if (!$product_data) {
      $query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);

      foreach ($query->rows as $result) {
        $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
      }

      $this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
    }


    return $product_data;
  }

  public function getPopularProducts($limit)
  {
    $product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

    if (!$product_data) {
      $query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);

      foreach ($query->rows as $result) {
        $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
      }

      $this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
    }

    return $product_data;
  }

  public function getBestSellerProducts($limit)
  {
    $product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

    if (!$product_data) {
      $product_data = array();

      $query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

      foreach ($query->rows as $result) {
        $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
      }

      $this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
    }

    return $product_data;
  }

  public function getProductAttributes($product_id)
  {
    $product_attribute_group_data = array();

    $product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

    foreach ($product_attribute_group_query->rows as $product_attribute_group) {
      $product_attribute_data = array();

      $product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, ad.slug, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

      $this->load->model('extension/module/d_ajax_filter');

      //TODO додати з d_ajax_filter translate для значення опції
//      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "attribute` a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE a.attribute_id = '" . (int)$attribute_id . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");
//      return $query->row;

      foreach ($product_attribute_query->rows as $product_attribute) {
        $product_attribute_data[] = array(
          'attribute_id' => $product_attribute['attribute_id'],
          'name' => $product_attribute['name'],
          'slug' => $product_attribute['slug'],
          'text' => $product_attribute['text']
        );
      }

      $product_attribute_group_data[] = array(
        'attribute_group_id' => $product_attribute_group['attribute_group_id'],
        'name' => $product_attribute_group['name'],
        'attribute' => $product_attribute_data
      );
    }

    return $product_attribute_group_data;
  }

  public function getProductStatus($product_status_id)
  {
    $status = array();
    if ($product_status_id != 0 || $product_status_id > 0) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
      if (!empty($query->row)) {
        $status = $query->row;
      }
    }
    return $status;
  }

  public function getProductOptionPawPaw($product_id, $sort_order = "ASC")
  {
    $sort_order = (strtoupper($sort_order) == 'DESC') ? 'DESC' : 'ASC';

    $sql = "
    SELECT 
        p.product_status_id,
        pq.options_value, 
        pq.options, 
        pq.ean,
        pq.sku,
        SUM(pq.quantity) as quantity, 
        pq.price
    FROM `" . DB_PREFIX . "product_quantity` as pq
    LEFT JOIN `" . DB_PREFIX . "product` as p ON (p.product_id = pq.product_id)
    WHERE pq.product_id = '" . (int)$product_id . "'
    GROUP BY pq.options_value, pq.options
    ORDER BY quantity DESC, pq.price " . $sort_order;

    $products_query = $this->db->query($sql);

    $options = array();
    $added_options = array();
    $selected_value_id = null; // Будет хранить ID выбранной опции

    // Сначала собираем все опции
    foreach ($products_query->rows as $d => $product_option) {

      $f = json_decode($product_option['options_value'], true);
      if (!empty($f)) {
        foreach ($f as $product_option_id => $product_option_value_id) {
          if (!in_array($product_option_value_id, $added_options)) {
            $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "product_quantity pq ON (pq.product_id = pov.product_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY pov.product_option_value_id ORDER BY pq.price " . $sort_order);

            foreach ($product_option_value_query->rows as $product_option_value) {

              $mass_options = array();
              $mass_options += [(int)$product_option_value['product_option_id'] => (int)$product_option_value['product_option_value_id']];
              $prices_discount = $this->model_extension_module_discount->applyDisconts((int)$product_id, $mass_options);

              $options[] = array(
                'product_option_value_id' => $product_option_value_id,
                'option_value_id' => $product_option_value['option_value_id'],
                'product_option_id' => $product_option_id,
                'name' => $product_option_value['name'],
                'image_opt' => $product_option_value['image_opt'],
                'quantity' => $product_option['quantity'],
                'price' => $prices_discount['price'],
                'special' => $prices_discount['special'],
                'selected' => 0, // Пока все не выбраны
                'discount_percent' => $prices_discount['percent'], // Добавляем скидку
                'product_status_id' => $product_option_value['product_status_id'],
                'description' => $this->getProductOptionValueDescription($product_option_value_id),
                'composition' => $this->getProductOptionValueComposition($product_option_value_id),
              );
              $added_options[] = $product_option_value_id;
            }
          }
        }
      } else {

        $mass_options = array();
        $prices_discount = $this->model_extension_module_discount->applyDisconts((int)$product_id, $mass_options);

        $options[] = array(
          'product_option_value_id' => 0,
          'option_value_id' => 0,
          'product_option_id' => 0,
          'name' => "без характеристики",
          'image_opt' => '',
          'quantity' => $product_option['quantity'],
          'price' => $prices_discount['price'],
          'special' => $prices_discount['special'],
          'selected' => 0,
          'discount_percent' => $prices_discount['percent'],
          'product_status_id' => $product_option['product_status_id']
        );
      }
    }

    //СОРТУВАННЯ
    // Витягуємо дані для сортування
    $weights = [];
    $isNumeric = [];
    $alphabetic = [];
    $quantities = array_column($options, 'quantity');
    $hasQuantity = []; // Чи є кількість (quantity > 0)

    foreach ($options as $product) {
      // Витягуємо числове значення з назви
      $cleanedValue = preg_replace('/[^0-9,.]/', '', $product['name']);
      $numericValue = (float)str_replace(',', '.', $cleanedValue);

      // Чи є числове значення в назві (напр. "0,4 кг" → 0.4)
      $hasNumericValue = ($cleanedValue !== '' && is_numeric(str_replace(',', '.', $cleanedValue)));

      $weights[] = $hasNumericValue ? $numericValue : INF;
      $isNumeric[] = $hasNumericValue;
      $alphabetic[] = $product['name'];

      // Чи є кількість (враховуємо тільки quantity > 0)
      $hasQuantity[] = !empty($product['quantity']) && $product['quantity'] > 0;
      
      // Фінальна ціна для сортування
      if ($product['special'] > 0) {
          $f_price = (float)$product['special'];
      } elseif (!empty($product['discount_percent']) && $product['discount_percent'] > 0) {
          $f_price = (float)$product['price'] * (1 - $product['discount_percent'] / 100);
      } else {
          $f_price = (float)$product['price'];
      }
      $f_prices[] = $f_price;
    }

    $dir = (strtoupper($sort_order) == 'DESC') ? SORT_DESC : SORT_ASC;

    // Сортуємо:
    // 1. Спочатку товари з наявною кількістю
    // 2. По ціні (згідно з напрямком сортування)
    // 3. По вазі/назві
    array_multisort(
      $hasQuantity, SORT_DESC,
      $f_prices, $dir,
      $weights, $dir,
      $alphabetic, $dir,
      $options
    );

    //ВИБРАНА ОПЦІЯ ПО КРИТЕРІЯМ
    $selected_index = null;

    // Шукаємо першу опцію в наявності (вона вже відсортована за ціною вище)
    foreach ($options as $index => $option) {
      if ($option['quantity'] > 0) {
        $selected_index = $index;
        break;
      }
    }

    // Якщо нічого в наявності немає, беремо просто першу
    if ($selected_index === null && !empty($options)) {
      $selected_index = 0;
    }

    // Устанавливаем selected
    if ($selected_index !== null) {
      $options[$selected_index]['selected'] = 1;
    }

    return $options;
  }

  public function getProductByOption($product_id, $option)
  {
    $product_option_value_data = array();
    foreach ($option as $product_option_id => $product_option_value_id) {
      $q = "SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN `" . DB_PREFIX . "option` o ON (pov.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order";
      $product_option_value_query = $this->db->query($q);
      foreach ($product_option_value_query->rows as $d => $product_option_value) {

        $product_option_value_data = array(
          'product_option_value_id' => $product_option_value['product_option_value_id'],
          'option_value_id' => $product_option_value['option_value_id'],
          'name' => $product_option_value['name'],
          'image_opt' => $product_option_value['image_opt'],
          'subtract' => $product_option_value['subtract'],
          'price' => $product_option_value['price'],
          'price_prefix' => $product_option_value['price_prefix'],
          'weight' => $product_option_value['weight'],
          'weight_prefix' => $product_option_value['weight_prefix'],
          'selected' => $d == 0 ? 1 : 0,
          'product_status_id' => $product_option_value['product_status_id']
        );
      }
    }

    return $product_option_value_data;
  }

  public function getProductOptions($product_id)
  {
    $product_option_data = array();

    $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");

    foreach ($product_option_query->rows as $k => $product_option) {
      $product_option_value_data = array();

      $product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order");

      foreach ($product_option_value_query->rows as $d => $product_option_value) {

        $product_option_value_data[] = array(
          'product_option_value_id' => $product_option_value['product_option_value_id'],
          'option_value_id' => $product_option_value['option_value_id'],
          'name' => $product_option_value['name'],
          'image_opt' => $product_option_value['image_opt'],
          'subtract' => $product_option_value['subtract'],
          'price' => $product_option_value['price'],
          'price_prefix' => $product_option_value['price_prefix'],
          'weight' => $product_option_value['weight'],
          'weight_prefix' => $product_option_value['weight_prefix'],
          'selected' => $d == 0 ? 1 : 0,
          'product_status_id' => $product_option_value['product_status_id']
        );
      }

      $product_option_data[] = array(
        'product_option_id' => $product_option['product_option_id'],
        'product_option_value' => $product_option_value_data,
        'option_id' => $product_option['option_id'],
        'name' => $product_option['name'],
        'type' => $product_option['type'],
        'value' => $product_option['value'],
        'required' => $product_option['required'],
        'selected' => $k == 0 ? 1 : 0
      );
    }

    return $product_option_data;
  }

  public function getProductDiscounts($product_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity > 1 AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity ASC, priority ASC, price ASC");

    return $query->rows;
  }

  public function getProductImages($product_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

    return $query->rows;
  }

  public function getProductOptStock($product_id, $mass_opts)
  {
    $stock = 0;
    if (!empty($mass_opts)) {
      $query = $this->db->query("SELECT SUM(quantity) AS sum_quantity FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' AND options_value = '" . json_encode($mass_opts) . "' ORDER BY price ASC");
    } else {
      $query = $this->db->query("SELECT SUM(quantity) AS sum_quantity FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' ORDER BY price ASC");
    }
    if (count($query->rows) > 0) {
      if ($query->row['sum_quantity'] != null) {
        $stock = $query->row['sum_quantity'];
      }
    }
    return $stock;
  }


  public function getProductOptStockWarehouses($product_id, $mass_opts, $is_buy = true)
  {
    $mass = array();

    $conditions = array();

    if ($is_buy) {
      $conditions[] = "quantity > 0";
    }

    $sql = "SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "'";

    if (!empty($mass_opts)) {
      $conditions[] = "options_value = '" . json_encode($mass_opts) . "'";
    }

    if (!empty($conditions)) {
      $sql .= " AND " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY quantity ASC";

    $query = $this->db->query($sql);

    if (count($query->rows) > 0) {
      foreach ($query->rows as $result) {
        $mass[$result['warehouse_id']] = $result['quantity'];
      }
    }

    arsort($mass);

    return $mass;
  }

  public function getProductOptStockWarehouse($product_id, $warehouse_id, $mass_opts)
  {
    $mass = array();
    if (!empty($mass_opts)) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' AND warehouse_id = '" . (int)$warehouse_id . "' AND options_value = '" . json_encode($mass_opts) . "' ORDER BY quantity ASC");
    } else {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' AND warehouse_id = '" . (int)$warehouse_id . "' ORDER BY quantity ASC");
    }
    if (count($query->rows) > 0) {
      foreach ($query->rows as $result) {
        $mass[$result['warehouse_id']] = $result['quantity'];
      }
    }

    arsort($mass);

    return $mass;
  }

  public function getProductOptPrice($product_id, $mass_opts)
  {

    if (!empty($mass_opts)) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' AND options_value = '" . json_encode($mass_opts) . "' ORDER BY price ASC");
    } else {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' ORDER BY price ASC");
    }

    if ($query->num_rows) {
      return array(
        'price' => $query->row['price'],
        'price_base' => $query->row['price_base'],
        'sku' => $query->row['sku'],
        'ean' => $query->row['ean'],
        'quantity' => $query->row['quantity'],
      );
    }
  }

  public function getProductMainCategoryId($product_id)
  {
    $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND main_category = '1' LIMIT 1");

    return ($query->num_rows ? (int)$query->row['category_id'] : 0);
  }

  public function getProductOptValues($product_id, $mass_opts)
  {
    $price = 0;
    if (!empty($mass_opts)) {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' AND options_value = '" . json_encode($mass_opts) . "' ORDER BY price ASC");
    } else {
      $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' ORDER BY price ASC");
    }
    return $query->row;
  }

  public function getProductImagesDop($product_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");
    return $query->rows;
  }

  public function getProductRelated($product_id)
  {
    $product_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

    foreach ($query->rows as $result) {
      $product_data[$result['related_id']] = $this->getProduct($result['related_id']);
    }

    return $product_data;
  }

  public function getProductLayoutId($product_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

    if ($query->num_rows) {
      return (int)$query->row['layout_id'];
    } else {
      return 0;
    }
  }

  public function getCategories($product_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
    return $query->rows;
  }

  public function getCategoriesIn($mass_productt_id = array())
  {
    $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (" . implode(',', $mass_productt_id) . ") AND main_category = 1");
    return $query->rows;
  }

  public function getTotalProductsInCategory($data = array())
  {
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS total";
    $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2cat ON (cp.category_id = p2cat.category_id)";
    $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2cat.product_id = p.product_id)";
    $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2store ON (p.product_id = p2store.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2store.store_id = '" . (int)$this->config->get('config_store_id') . "'";
    $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
    $category_ids = array_map('intval', $category_ids);
    $category_ids = array_filter($category_ids);

    if (!empty($category_ids)) {
      $sql .= " AND p2cat.category_id IN (" . implode(',', $category_ids) . ")";
    }

    $query = $this->db->query($sql);

    return $query->row['total'];
  }

  public function getTotalProductsInBrand($data = array())
  {
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS total";
    $sql .= " FROM " . DB_PREFIX . "manufacturer m";
    $sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (m.manufacturer_id = p.manufacturer_id)";

    if (!empty($data['filter_manufacturer_id'])) {
      $sql .= " AND m.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
    }

    $query = $this->db->query($sql);

    return $query->row['total'];
  }

  public function getTotalProducts($data = array())
  {
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS total FROM " . DB_PREFIX . "product p";
    $sql .= " LEFT JOIN " . DB_PREFIX . "product_quantity pq ON pq.product_id = p.product_id";
    $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON pd.product_id = p.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
    $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON p.product_id = p2s.product_id AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

    // Приєднання для фільтрації по категорії
    if (!empty($data['filter_category_id'])) {
      $category_ids = is_array($data['filter_category_id']) ? $data['filter_category_id'] : explode(',', $data['filter_category_id']);
      $category_ids = array_map('intval', $category_ids);
      $category_ids = array_filter($category_ids);

      if (!empty($category_ids)) {
        if (!empty($data['filter_sub_category'])) {
          $sql .= " LEFT JOIN " . DB_PREFIX . "category_path cp ON cp.path_id IN (" . implode(',', $category_ids) . ")
                    LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON cp.category_id = p2c.category_id 
                    AND p2c.product_id = p.product_id";
        } else {
          $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON p2c.product_id = p.product_id 
                    AND p2c.category_id IN (" . implode(',', $category_ids) . ")";
        }
      }

      // Приєднання для фільтрів
      if (!empty($data['filter_filter'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON pf.product_id = p.product_id";
      }
    }

    // Умови WHERE для фільтрації за статусом та датою доступності
    $sql .= " WHERE p.status = '1' AND p.date_available <= NOW() ";

    // Умови для фільтрації за категорією та фільтрами
    if (!empty($data['filter_category_id'])) {
      if (!empty($data['filter_sub_category'])) {
        if (!empty($category_ids)) {
          $sql .= " AND cp.path_id IN (" . implode(',', $category_ids) . ")";
        }
      } else {
        if (!empty($category_ids)) {
          $sql .= " AND p2c.category_id IN (" . implode(',', $category_ids) . ")";
        }
      }

      if (!empty($data['filter_filter'])) {
        $filters = array_map('intval', explode(',', $data['filter_filter']));
        $sql .= " AND pf.filter_id IN (" . implode(',', $filters) . ")";
      }
    }

    // Фільтрація за виробником
    if (!empty($data['filter_manufacturer_id'])) {
      $ids = array_map('intval', explode(',', $data['filter_manufacturer_id']));
      $sql .= " AND p.manufacturer_id IN (" . implode(',', $ids) . ") ";
    }

    // Фильтр по имени и тегам
    if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
      $sql .= " AND (";

      // Условия для поиска по имени
      if (!empty($data['filter_name'])) {
        $words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));
        $conditions = [];

        foreach ($words as $word) {
          $conditions[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
        }

        $fullPhraseCondition = "pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        array_unshift($conditions, $fullPhraseCondition);

        $sql .= " (" . implode(" OR ", $conditions) . ")";

        // Условие для поиска в описании
        if (!empty($data['filter_description'])) {
          $sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        // Условие для поиска в описании
        if (!empty($data['filter_search'])) {
          $sql .= " OR pq.ean LIKE '%" . $this->db->escape($data['filter_search']) . "%'";
        }
      }

      // Проверка и добавление условия `OR` между именем и тегом, если оба заполнены
      if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
        $sql .= " OR ";
      }

      // Условия для поиска по тегу
      if (!empty($data['filter_tag'])) {
        $tagWords = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));
        $sql .= " (" . implode(" AND ", array_map(function ($word) {
            return "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
          }, $tagWords)) . ")";
      }

      // Условия для точного поиска по SKU или EAN
      if (!empty($data['filter_name'])) {
        $loweredName = $this->db->escape(utf8_strtolower($data['filter_name']));
        $sql .= " OR LCASE(pq.sku) = '" . $loweredName . "'";
        $sql .= " OR LCASE(pq.ean) = '" . $loweredName . "'";
      }

      $sql .= ")";
    }

    $query_total = $this->db->query($sql);

    return $query_total->row['total'];
  }


  public function getProfile($product_id, $recurring_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r JOIN " . DB_PREFIX . "product_recurring pr ON (pr.recurring_id = r.recurring_id AND pr.product_id = '" . (int)$product_id . "') WHERE pr.recurring_id = '" . (int)$recurring_id . "' AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

    return $query->row;
  }

  public function getProfiles($product_id)
  {
    $query = $this->db->query("SELECT rd.* FROM " . DB_PREFIX . "product_recurring pr JOIN " . DB_PREFIX . "recurring_description rd ON (rd.language_id = " . (int)$this->config->get('config_language_id') . " AND rd.recurring_id = pr.recurring_id) JOIN " . DB_PREFIX . "recurring r ON r.recurring_id = rd.recurring_id WHERE pr.product_id = " . (int)$product_id . " AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY sort_order ASC");

    return $query->rows;
  }

  public function getTotalProductSpecials()
  {
    $query_db = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))");

    if (isset($query_db->row['total'])) {
      return $query_db->row['total'];
    } else {
      return 0;
    }
  }

  public function getProductOptionValueDescription($product_option_value_id)
  {
    $text = "";
    $sql = "SELECT * FROM " . DB_PREFIX . "product_option_value_description WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'";
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      $text = $query->row['text'];
    }
    return $text;
  }

  public function getProductOptionValueComposition($product_option_value_id)
  {
    $text = "";
    $sql = "SELECT * FROM " . DB_PREFIX . "product_option_value_composition WHERE product_option_value_id = '" . (int)$product_option_value_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'";
    $query = $this->db->query($sql);
    if ($query->num_rows) {
      $text = $query->row['text'];
    }
    return $text;
  }

  public function getProductsByProductStatus($data = array())
  {
    $product_data = array();
    $a = "SELECT pov.product_id, 
    (SELECT SUM(quantity) FROM " . DB_PREFIX . "product_quantity pq 
     WHERE pq.product_id = pov.product_id 
     AND pq.options = CONCAT('{\"', pov.option_id, '\":', pov.option_value_id, '}')
     AND pq.options_value = CONCAT('{\"', pov.product_option_id, '\":', pov.product_option_value_id, '}')
     GROUP BY pq.product_id) AS quantity
FROM " . DB_PREFIX . "product_option_value pov 
LEFT JOIN " . DB_PREFIX . "product p ON (p.product_id = pov.product_id) 
LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
WHERE (pov.product_status_id = '" . (int)$data['product_status_id'] . "' 
      OR p.product_status_id = '" . (int)$data['product_status_id'] . "') 
AND p.date_available <= NOW() 
AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
    $query = $this->db->query($a);
    if ($query->num_rows) {
      foreach ($query->rows as $result) {
        if ((int)$result['quantity'] > 0) {
          $product_data[$result['product_id']] = $this->getProduct($result['product_id']);
        }
      }
    }
    return $product_data;
  }

  public function getTotalProductDiscont()
  {
    $query_db = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_discount ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))");

    if (isset($query_db->row['total'])) {
      return $query_db->row['total'];
    } else {
      return 0;
    }
  }

}
