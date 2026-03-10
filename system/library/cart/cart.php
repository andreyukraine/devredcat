<?php

namespace Cart;
class Cart
{
  private $data = null;
  private $model_extension_module_discount;

  public function __construct($registry)
  {
    $this->config = $registry->get('config');
    $this->customer = $registry->get('customer');
    $this->session = $registry->get('session');
    $this->db = $registry->get('db');
    $this->tax = $registry->get('tax');
    $this->weight = $registry->get('weight');


    // Загружаем модель через реестр
    $registry->get('load')->model('extension/module/discount');

    // Получаем модель после её загрузки
    $this->model_extension_module_discount = $registry->get('model_extension_module_discount');

    // Remove all the expired carts with no customer ID
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE (api_id > '0' OR customer_id = '0') AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1000");

    if ($this->customer->getId()) {
      // We want to change the session ID on all the old items in the customers cart
      $this->db->query("UPDATE " . DB_PREFIX . "cart SET session_id = '" . $this->db->escape($this->session->getId()) . "' WHERE api_id = '0' AND customer_id = '" . (int)$this->customer->getId() . "'");

      // Once the customer is logged in we want to update the customers cart
      $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

      foreach ($cart_query->rows as $cart) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart['cart_id'] . "' AND `warehouse_id` = '" . (int)$cart['warehouse_id'] . "'");

        // The advantage of using $this->add is that it will check if the products already exist and increaser the quantity if necessary.
        $this->add($cart['product_id'], $cart['quantity'], json_decode($cart['option']), $cart['recurring_id'], $cart['warehouse_id']);
      }
    }
  }

  public function getProducts()
  {
    if ($this->data !== null) {
      return $this->data;
    }

    $product_data = array();

    $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

    foreach ($cart_query->rows as $cart) {
      $stock = true;

      $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store p2s LEFT JOIN " . DB_PREFIX . "product p ON (p2s.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p2s.product_id = '" . (int)$cart['product_id'] . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.date_available <= NOW() AND p.status = '1'");

      if ($product_query->num_rows && ($cart['quantity'] > 0)) {

        $option_points = 0;
        $option_weight = 0;

        $option_data = array();

        foreach (json_decode($cart['option']) as $product_option_id => $value) {
          $option_query = $this->db->query("SELECT pov.product_option_id, pov.option_id, od.name, o.type FROM `" . DB_PREFIX . "product_option_value` pov LEFT JOIN `" . DB_PREFIX . "option` o ON (o.option_id = pov.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (pov.option_id = od.option_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

          if ($option_query->num_rows) {
            if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
              $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, pov.image_opt FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

              if ($option_value_query->num_rows) {
                if ($option_value_query->row['points_prefix'] == '+') {
                  $option_points += $option_value_query->row['points'];
                } elseif ($option_value_query->row['points_prefix'] == '-') {
                  $option_points -= $option_value_query->row['points'];
                }

                if ($option_value_query->row['weight_prefix'] == '+') {
                  $option_weight += $option_value_query->row['weight'];
                } elseif ($option_value_query->row['weight_prefix'] == '-') {
                  $option_weight -= $option_value_query->row['weight'];
                }

                $option_data[] = array(
                  'product_option_id' => $product_option_id,
                  'product_option_value_id' => $value,
                  'option_id' => $option_query->row['option_id'],
                  'option_value_id' => $option_value_query->row['option_value_id'],
                  'name' => $option_query->row['name'],
                  'value' => $option_value_query->row['name'],
                  'weight' => $option_value_query->row['weight'],
                  'type' => $option_query->row['type'],
                  'image_opt' => $option_value_query->row['image_opt']
                );
              }
            } elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
              foreach ($value as $product_option_value_id) {
                $option_value_query = $this->db->query("SELECT pov.option_value_id, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name, pov.image_opt FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                if ($option_value_query->num_rows) {
                  if ($option_value_query->row['points_prefix'] == '+') {
                    $option_points += $option_value_query->row['points'];
                  } elseif ($option_value_query->row['points_prefix'] == '-') {
                    $option_points -= $option_value_query->row['points'];
                  }

                  if ($option_value_query->row['weight_prefix'] == '+') {
                    $option_weight += $option_value_query->row['weight'];
                  } elseif ($option_value_query->row['weight_prefix'] == '-') {
                    $option_weight -= $option_value_query->row['weight'];
                  }

                  $option_data[] = array(
                    'product_option_id' => $product_option_id,
                    'product_option_value_id' => $product_option_value_id,
                    'option_id' => $option_query->row['option_id'],
                    'option_value_id' => $option_value_query->row['option_value_id'],
                    'name' => $option_query->row['name'],
                    'value' => $option_value_query->row['name'],
                    'weight' => $option_value_query->row['weight'],
                    'type' => $option_query->row['type'],
                    'image_opt' => $option_value_query->row['image_opt']
                  );
                }
              }
            } elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
              $option_data[] = array(
                'product_option_id' => $product_option_id,
                'product_option_value_id' => '',
                'option_id' => $option_query->row['option_id'],
                'option_value_id' => '',
                'name' => $option_query->row['name'],
                'value' => $value,
                'weight' => $option_query->row['weight'],
                'type' => $option_query->row['type'],
                'image_opt' => ''
              );
            }
          }
        }

        // Product Discounts
        $prices_discount = $this->model_extension_module_discount->applyDisconts($cart['product_id'], json_decode($cart['option']));
        $sku = $prices_discount['sku'];
        $ean = $prices_discount['ean'];
        $price = $prices_discount['price'];
        $tax = $prices_discount['tax'];
        $percent = $prices_discount['percent'];
        $special = $prices_discount['special'];

        // Reward Points
        $product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

        if ($product_reward_query->num_rows) {
          $reward = $product_reward_query->row['points'];
        } else {
          $reward = 0;
        }

        // Downloads
        $download_data = array();

        $download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download p2d LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = '" . (int)$cart['product_id'] . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($download_query->rows as $download) {
          $download_data[] = array(
            'download_id' => $download['download_id'],
            'name' => $download['name'],
            'filename' => $download['filename'],
            'mask' => $download['mask']
          );
        }

        $recurring_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r LEFT JOIN " . DB_PREFIX . "product_recurring pr ON (r.recurring_id = pr.recurring_id) LEFT JOIN " . DB_PREFIX . "recurring_description rd ON (r.recurring_id = rd.recurring_id) WHERE r.recurring_id = '" . (int)$cart['recurring_id'] . "' AND pr.product_id = '" . (int)$cart['product_id'] . "' AND rd.language_id = " . (int)$this->config->get('config_language_id') . " AND r.status = 1 AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

        if ($recurring_query->num_rows) {
          $recurring = array(
            'recurring_id' => $cart['recurring_id'],
            'name' => $recurring_query->row['name'],
            'frequency' => $recurring_query->row['frequency'],
            'price' => $recurring_query->row['price'],
            'cycle' => $recurring_query->row['cycle'],
            'duration' => $recurring_query->row['duration'],
            'trial' => $recurring_query->row['trial_status'],
            'trial_frequency' => $recurring_query->row['trial_frequency'],
            'trial_price' => $recurring_query->row['trial_price'],
            'trial_cycle' => $recurring_query->row['trial_cycle'],
            'trial_duration' => $recurring_query->row['trial_duration']
          );
        } else {
          $recurring = false;
        }


        $price_total = $price * (int)$cart['quantity'];
        if ($special > 0){
          $price_total = $special * (int)$cart['quantity'];
        }

        $product_data[] = array(
          'cart_id' => $cart['cart_id'],
          'product_id' => $product_query->row['product_id'],
          'name' => $product_query->row['name'],
          'sku' => $sku,
          'model' => $ean,
          'shipping' => $product_query->row['shipping'],
          'image' => $product_query->row['image'],
          'option' => $cart['option'],
          'option_data' => $option_data,
          'download' => $download_data,
          'quantity' => (int)$cart['quantity'],
          'minimum' => $product_query->row['minimum'],
          'subtract' => $product_query->row['subtract'],
          'stock' => (int)$stock,
          'price' => $price,
          'special' => $special,
          'tax' => $tax,
          'percent' => $percent,
          'total' => $price_total,
          'reward' => $reward * (int)$cart['quantity'],
          'points' => ($product_query->row['points'] ? ($product_query->row['points'] + $option_points) * $cart['quantity'] : 0),
          'tax_class_id' => $product_query->row['tax_class_id'],
          'weight' => ($product_query->row['weight'] + $option_weight) * $cart['quantity'],
          'weight_class_id' => $product_query->row['weight_class_id'],
          'length' => $product_query->row['length'],
          'width' => $product_query->row['width'],
          'height' => $product_query->row['height'],
          'length_class_id' => $product_query->row['length_class_id'],
          'recurring' => $recurring,
          'warehouse_id' => (int)$cart['warehouse_id'],
          'organization_id' => (int)$product_query->row['organization_id']
        );
      } else {
        $this->remove($cart['cart_id']);
      }
    }

    $this->data = $product_data;

    return $product_data;
  }

  public function getProductOptions($product_id, $option)
  {
    $product_data = array();
    $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
    foreach ($cart_query->rows as $cart) {

      $prices_discount = $this->model_extension_module_discount->applyDisconts($cart['product_id'], json_decode($cart['option']));
      $price = $prices_discount['price'];
      $special = $prices_discount['special'];
      $percent = $prices_discount['percent'];
      $ean = $prices_discount['ean'];

      $product_data[] = array(
        'cart_id' => $cart['cart_id'],
        'quantity' => $cart['quantity'],
        'price' => $price,
        'special' => $special,
        'percent' => $percent,
        'ean' => $ean
      );
    }
    return $product_data;
  }

  public function getItemCart($cart_id)
  {
    $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "'");
    return $cart_query->row;
  }

  public function getItemsCartByStore($warehouse_id)
  {
    $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE warehouse_id = '" . (int)$warehouse_id . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
    return $cart_query->rows;
  }

  public function getItemCartNew($cart_id, $warehouse_id = 0)
  {
    $data = array();

    $sql = "SELECT c.*,
    (SELECT SUM(pq_total.quantity) 
     FROM " . DB_PREFIX . "product_quantity pq_total 
     WHERE pq_total.product_id = c.product_id 
     AND pq_total.options_value = c.option) as total_quantity, /* загальна кількість товару з усіх складів */
    (SELECT " . ($warehouse_id != 0 ? "pq.quantity" : "SUM(pq.quantity)") . "
     FROM " . DB_PREFIX . "product_quantity pq 
     WHERE pq.product_id = c.product_id 
     AND pq.options_value = c.option 
     " . ($warehouse_id != 0 ? " AND pq.warehouse_id = '" . (int)$warehouse_id . "'" : "") . " /* якщо склад вказаний - додаємо умову по складу */
     " . ($warehouse_id != 0 ? "LIMIT 1" : "") . ") as store_quantity /* для конкретного складу беремо одну запис, для всіх - сумуємо */
    FROM " . DB_PREFIX . "cart c 
    WHERE c.cart_id = '" . (int)$cart_id . "'
    " . ($warehouse_id != 0 ? " AND c.warehouse_id = '" . (int)$warehouse_id . "'" : ""); /* якщо склад вказаний - фільтруємо кошик по складу */

    $cart_query = $this->db->query($sql);

    if ($cart_query->rows > 0) {
      $data = array(
        'warehouse_id' => (int)$cart_query->row['warehouse_id'], /* ID складу */
        'quantity' => (int)$cart_query->row['quantity'], /* кількість в кошику */
        'total_quantity' => (int)$cart_query->row['total_quantity'], /* загальна кількість на всіх складах */
        'option' => $cart_query->row['option'], /* опції товару */
        'store_quantity' => (int)$cart_query->row['store_quantity'] /* кількість на обраному складі (або сума по всіх) */
      );
    }

    return $data;
  }

  public function getStoresByProduct($product_id, $option, $warehouse_id = 0)
  {
    $data = array();

    $option_json = $this->db->escape(json_encode($option));

    $sql = "SELECT 
        c.*,
        (SELECT SUM(pq_total.quantity) 
         FROM " . DB_PREFIX . "product_quantity pq_total 
         WHERE pq_total.product_id = c.product_id 
         AND pq_total.options_value = c.option) as total_quantity,
        
        (SELECT pq.quantity 
         FROM " . DB_PREFIX . "product_quantity pq 
         WHERE pq.product_id = c.product_id 
         AND pq.options_value = c.option 
         " . ($warehouse_id != 0 ? " AND pq.warehouse_id = '" . (int)$warehouse_id . "'" : "") . "
         LIMIT 1) as store_quantity
    FROM " . DB_PREFIX . "cart c 
    WHERE c.product_id = '" . (int)$product_id . "' 
    AND c.option = '" . $option_json . "' 
    AND c.customer_id = '" . (int)$this->customer->getId() . "'
    AND c.session_id = '" . $this->db->escape($this->session->getId()) . "'
    " . ($warehouse_id != 0 ? " AND c.warehouse_id = '" . (int)$warehouse_id . "'" : "");
    $cart_query = $this->db->query($sql);


    if ($cart_query->rows > 0) {
      foreach ($cart_query->rows as $cart_item) {
        if ($cart_item['warehouse_id'] > 0) {
          $store_query = $this->db->query("SELECT *  FROM `" . DB_PREFIX . "warehouse` w LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) WHERE w.warehouse_id = '" . (int)$cart_item['warehouse_id'] . "' AND wd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
          $store = "";
          if ($store_query->num_rows) {
            $store = $store_query->row['name'];
          }
          $data[] = array(
            'warehouse_id' => $cart_item['warehouse_id'],
            'name' => $store,
            'cart_id' => $cart_item['cart_id'],
            'option' => $cart_item['option'],
            'quantity' => (int)$cart_item['quantity'],
            'total_quantity' => (int)$cart_item['total_quantity'],
            'store_quantity' => (int)$cart_item['store_quantity']
          );
        }
      }
    }
    return $data;
  }

  public function getProductByStore($product_id, $option, $warehouse_id = 0)
  {
    $product_data = array();

    $option_json = $this->db->escape(json_encode($option));

    // Формируем JOIN условие в зависимости от warehouse_id
    $join_condition = ($warehouse_id == 0)
      ? "ON (c.product_id = pq.product_id AND c.option = pq.options_value)"
      : "ON (c.product_id = pq.product_id AND c.option = pq.options_value AND c.warehouse_id = pq.warehouse_id)";

    $sql_text = "SELECT c.*, SUM(pq.quantity) as total_quantity, pq.quantity as store_quantity  
        FROM " . DB_PREFIX . "cart c 
        LEFT JOIN `" . DB_PREFIX . "product_quantity` pq 
            " . $join_condition . " 
        WHERE c.product_id = '" . (int)$product_id . "' 
        AND c.option = '" . $option_json . "'" .
      ($warehouse_id != 0 ? " AND c.warehouse_id = '" . (int)$warehouse_id . "'" : "") . "
        AND c.customer_id = '" . (int)$this->customer->getId() . "'
        AND session_id = '" . $this->db->escape($this->session->getId()) . "'
        GROUP BY c.cart_id";
    $cart_query = $this->db->query($sql_text);

    if ($cart_query->rows) {
      foreach ($cart_query->rows as $cart) {
        $stock = true;

        $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$cart['product_id'] . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.date_available <= NOW() AND p.status = '1'");

        $option_price = 0;
        $option_points = 0;
        $option_weight = 0;

        $option_data = array();

        foreach (json_decode($cart['option']) as $product_option_id => $value) {
          $option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type 
                        FROM " . DB_PREFIX . "product_option po 
                        LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) 
                        LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) 
                        WHERE po.product_option_id = '" . (int)$product_option_id . "' 
                        AND po.product_id = '" . (int)$cart['product_id'] . "' 
                        AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

          if ($option_query->num_rows) {
            if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
              $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, pov.image_opt 
                                FROM " . DB_PREFIX . "product_option_value pov 
                                LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) 
                                LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) 
                                WHERE pov.product_option_value_id = '" . (int)$value . "' 
                                AND pov.product_option_id = '" . (int)$product_option_id . "' 
                                AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

              if ($option_value_query->num_rows) {
                if ($option_value_query->row['points_prefix'] == '+') {
                  $option_points += $option_value_query->row['points'];
                } elseif ($option_value_query->row['points_prefix'] == '-') {
                  $option_points -= $option_value_query->row['points'];
                }

                if ($option_value_query->row['weight_prefix'] == '+') {
                  $option_weight += $option_value_query->row['weight'];
                } elseif ($option_value_query->row['weight_prefix'] == '-') {
                  $option_weight -= $option_value_query->row['weight'];
                }

                $option_data[] = array(
                  'product_option_id' => $product_option_id,
                  'product_option_value_id' => $value,
                  'option_id' => $option_query->row['option_id'],
                  'option_value_id' => $option_value_query->row['option_value_id'],
                  'name' => $option_query->row['name'],
                  'value' => $option_value_query->row['name'],
                  'type' => $option_query->row['type'],
                  'image_opt' => $option_value_query->row['image_opt']
                );
              }
            } elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
              foreach ($value as $product_option_value_id) {
                $option_value_query = $this->db->query("SELECT pov.option_value_id, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name, pov.image_opt 
                                    FROM " . DB_PREFIX . "product_option_value pov 
                                    LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) 
                                    WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' 
                                    AND pov.product_option_id = '" . (int)$product_option_id . "' 
                                    AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                if ($option_value_query->num_rows) {
                  if ($option_value_query->row['points_prefix'] == '+') {
                    $option_points += $option_value_query->row['points'];
                  } elseif ($option_value_query->row['points_prefix'] == '-') {
                    $option_points -= $option_value_query->row['points'];
                  }

                  if ($option_value_query->row['weight_prefix'] == '+') {
                    $option_weight += $option_value_query->row['weight'];
                  } elseif ($option_value_query->row['weight_prefix'] == '-') {
                    $option_weight -= $option_value_query->row['weight'];
                  }

                  $option_data[] = array(
                    'product_option_id' => $product_option_id,
                    'product_option_value_id' => $product_option_value_id,
                    'option_id' => $option_query->row['option_id'],
                    'option_value_id' => $option_value_query->row['option_value_id'],
                    'name' => $option_query->row['name'],
                    'value' => $option_value_query->row['name'],
                    'type' => $option_query->row['type'],
                    'image_opt' => $option_value_query->row['image_opt']
                  );
                }
              }
            } elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
              $option_data[] = array(
                'product_option_id' => $product_option_id,
                'product_option_value_id' => '',
                'option_id' => $option_query->row['option_id'],
                'option_value_id' => '',
                'name' => $option_query->row['name'],
                'value' => $value,
                'type' => $option_query->row['type'],
                'image_opt' => '',
              );
            }
          }
        }

        // Product Discounts
        $prices_discount = $this->model_extension_module_discount->applyDisconts($cart['product_id'], json_decode($cart['option']));
        $price = $prices_discount['price'];
        $tax = $prices_discount['tax'];
        $percent = $prices_discount['percent'];
        $special = $prices_discount['special'];
        $ean = $prices_discount['ean'];

        // Reward Points
        $product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward 
                    WHERE product_id = '" . (int)$cart['product_id'] . "' 
                    AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

        $reward = $product_reward_query->num_rows ? $product_reward_query->row['points'] : 0;

        $product_data[] = array(
          'cart_id' => $cart['cart_id'],
          'product_id' => $product_query->row['product_id'],
          'name' => $product_query->row['name'],
          'model' => $product_query->row['model'],
          'shipping' => $product_query->row['shipping'],
          'image' => $product_query->row['image'],
          'option' => $cart['option'],
          'option_data' => $option_data,
          'quantity' => $cart['quantity'],
          'minimum' => $product_query->row['minimum'],
          'subtract' => $product_query->row['subtract'],
          'stock' => $stock,
          'price' => $price,
          'ean' => $ean,
          'special' => $special,
          'tax' => $tax,
          'percent' => $percent,
          'total' => $price * $cart['total_quantity'],
          'reward' => $reward * $cart['total_quantity'],
          'points' => ($product_query->row['points'] ? ($product_query->row['points'] + $option_points) * $cart['total_quantity'] : 0),
          'tax_class_id' => $product_query->row['tax_class_id'],
          'weight' => ($product_query->row['weight'] + $option_weight) * $cart['total_quantity'],
          'weight_class_id' => $product_query->row['weight_class_id'],
          'length' => $product_query->row['length'],
          'width' => $product_query->row['width'],
          'height' => $product_query->row['height'],
          'length_class_id' => $product_query->row['length_class_id'],
          'warehouse_id' => $cart['warehouse_id'],
          'total_quantity' => $cart['total_quantity'] == null ? 0 : $cart['total_quantity'],
          'store_quantity' => $cart['store_quantity'] == null ? 0 : $cart['store_quantity']
        );
      }
    }

    return $product_data;
  }

  public function getProduct($product_id)
  {
    $product_data = array();

    $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "'");

    if ($cart_query->rows > 0) {
      foreach ($cart_query->rows as $cart) {
        $stock = true;

        $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store p2s LEFT JOIN " . DB_PREFIX . "product p ON (p2s.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p2s.product_id = '" . (int)$cart['product_id'] . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.date_available <= NOW() AND p.status = '1'");

        if ($product_query->num_rows && ($cart['quantity'] > 0)) {
          $option_price = 0;
          $option_points = 0;
          $option_weight = 0;

          $option_data = array();

          foreach (json_decode($cart['option']) as $product_option_id => $value) {
            $option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$cart['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

            if ($option_query->num_rows) {
              if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
                $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, pov.image_opt FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                if ($option_value_query->num_rows) {
                  if ($option_value_query->row['points_prefix'] == '+') {
                    $option_points += $option_value_query->row['points'];
                  } elseif ($option_value_query->row['points_prefix'] == '-') {
                    $option_points -= $option_value_query->row['points'];
                  }

                  if ($option_value_query->row['weight_prefix'] == '+') {
                    $option_weight += $option_value_query->row['weight'];
                  } elseif ($option_value_query->row['weight_prefix'] == '-') {
                    $option_weight -= $option_value_query->row['weight'];
                  }

                  $option_data[] = array(
                    'product_option_id' => $product_option_id,
                    'product_option_value_id' => $value,
                    'option_id' => $option_query->row['option_id'],
                    'option_value_id' => $option_value_query->row['option_value_id'],
                    'name' => $option_query->row['name'],
                    'value' => $option_value_query->row['name'],
                    'type' => $option_query->row['type'],
                    'image_opt' => $option_value_query->row['image_opt']
                  );
                }
              } elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
                foreach ($value as $product_option_value_id) {
                  $option_value_query = $this->db->query("SELECT pov.option_value_id, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name, pov.image_opt FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                if ($option_value_query->num_rows) {
                  if ($option_value_query->row['points_prefix'] == '+') {
                    $option_points += $option_value_query->row['points'];
                  } elseif ($option_value_query->row['points_prefix'] == '-') {
                    $option_points -= $option_value_query->row['points'];
                  }

                  if ($option_value_query->row['weight_prefix'] == '+') {
                    $option_weight += $option_value_query->row['weight'];
                  } elseif ($option_value_query->row['weight_prefix'] == '-') {
                    $option_weight -= $option_value_query->row['weight'];
                  }

                  $option_data[] = array(
                    'product_option_id' => $product_option_id,
                    'product_option_value_id' => $product_option_value_id,
                      'option_id' => $option_query->row['option_id'],
                      'option_value_id' => $option_value_query->row['option_value_id'],
                      'name' => $option_query->row['name'],
                      'value' => $option_value_query->row['name'],
                      'type' => $option_query->row['type'],
                      'image_opt' => $option_value_query->row['image_opt']
                    );
                  }
                }
              } elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
                $option_data[] = array(
                  'product_option_id' => $product_option_id,
                  'product_option_value_id' => '',
                  'option_id' => $option_query->row['option_id'],
                  'option_value_id' => '',
                  'name' => $option_query->row['name'],
                  'value' => $value,
                  'type' => $option_query->row['type'],
                  'image_opt' => '',
                );
              }
            }
          }
          // Product Discounts
          $prices_discount = $this->model_extension_module_discount->applyDisconts($cart['product_id'], json_decode($cart['option']));
          $price = $prices_discount['price'];
          $tax = $prices_discount['tax'];
          $percent = $prices_discount['percent'];
          $special = $prices_discount['special'];
          $ean = $prices_discount['ean'];
          $sku = $prices_discount['sku'];

          // Reward Points
          $product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

          if ($product_reward_query->num_rows) {
            $reward = $product_reward_query->row['points'];
          } else {
            $reward = 0;
          }

          // Downloads
          $download_data = array();

          $download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download p2d LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = '" . (int)$cart['product_id'] . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

          foreach ($download_query->rows as $download) {
            $download_data[] = array(
              'download_id' => $download['download_id'],
              'name' => $download['name'],
              'filename' => $download['filename'],
              'mask' => $download['mask']
            );
          }

          $recurring_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r LEFT JOIN " . DB_PREFIX . "product_recurring pr ON (r.recurring_id = pr.recurring_id) LEFT JOIN " . DB_PREFIX . "recurring_description rd ON (r.recurring_id = rd.recurring_id) WHERE r.recurring_id = '" . (int)$cart['recurring_id'] . "' AND pr.product_id = '" . (int)$cart['product_id'] . "' AND rd.language_id = " . (int)$this->config->get('config_language_id') . " AND r.status = 1 AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

          if ($recurring_query->num_rows) {
            $recurring = array(
              'recurring_id' => $cart['recurring_id'],
              'name' => $recurring_query->row['name'],
              'frequency' => $recurring_query->row['frequency'],
              'price' => $recurring_query->row['price'],
              'cycle' => $recurring_query->row['cycle'],
              'duration' => $recurring_query->row['duration'],
              'trial' => $recurring_query->row['trial_status'],
              'trial_frequency' => $recurring_query->row['trial_frequency'],
              'trial_price' => $recurring_query->row['trial_price'],
              'trial_cycle' => $recurring_query->row['trial_cycle'],
              'trial_duration' => $recurring_query->row['trial_duration']
            );
          } else {
            $recurring = false;
          }

          $product_data[] = array(
            'cart_id' => $cart['cart_id'],
            'product_id' => $product_query->row['product_id'],
            'name' => $product_query->row['name'],
            'model' => $product_query->row['model'],
            'shipping' => $product_query->row['shipping'],
            'image' => $product_query->row['image'],
            'option' => $cart['option'],
            'option_data' => $option_data,
            'download' => $download_data,
            'quantity' => $cart['quantity'],
            'minimum' => $product_query->row['minimum'],
            'subtract' => $product_query->row['subtract'],
            'stock' => $stock,
            'price' => $price,
            'ean' => $ean,
            'sku' => $sku,
            'special' => $special,
            'tax' => $tax,
            'percent' => $percent,
            'total' => $price * $cart['quantity'],
            'reward' => $reward * $cart['quantity'],
            'points' => ($product_query->row['points'] ? ($product_query->row['points'] + $option_points) * $cart['quantity'] : 0),
            'tax_class_id' => $product_query->row['tax_class_id'],
            'weight' => ($product_query->row['weight'] + $option_weight) * $cart['quantity'],
            'weight_class_id' => $product_query->row['weight_class_id'],
            'length' => $product_query->row['length'],
            'width' => $product_query->row['width'],
            'height' => $product_query->row['height'],
            'length_class_id' => $product_query->row['length_class_id'],
            'recurring' => $recurring,
            'warehouse_id' => $cart['warehouse_id']
          );
        } else {
          $this->remove($cart['cart_id']);
        }
      }
    }

    return $product_data;
  }

  public function add($product_id, $quantity = 1, $option = array(), $recurring_id = 0, $warehouse_id = 0)
  {
    $this->data = null;

    $query = $this->db->query("SELECT COUNT(*) AS total, cart_id FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "' AND `warehouse_id` = '" . (int)$warehouse_id . "'");

    if (!$query->row['total']) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "cart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', warehouse_id = '" . (int)$warehouse_id . "', date_added = NOW()");
      $cart_id = $this->db->getLastId();
    } else {
      //$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = (quantity + " . (int)$quantity . ") WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
      $this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = " . (int)$quantity . ", warehouse_id = '" . (int)$warehouse_id . "' WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "' AND `warehouse_id` = '" . (int)$warehouse_id . "'");
      $cart_id = $query->row['cart_id'];
    }
    return $cart_id;
  }

  public function update($cart_id, $quantity, $warehouse_id = 0)
  {
    $this->data = null;
    $this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = '" . (int)$quantity . "', warehouse_id = '" . (int)$warehouse_id . "' WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND warehouse_id = '" . (int)$warehouse_id . "'");
  }

  public function editStoreItemCart($cart_id, $warehouse_id)
  {
    $this->data = null;
    $this->db->query("UPDATE " . DB_PREFIX . "cart SET warehouse_id = '" . (int)$warehouse_id . "' WHERE cart_id = '" . (int)$cart_id . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
  }

  public function remove($cart_id, $warehouse_id = 0)
  {
    $this->data = null;
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND warehouse_id = '" . (int)$warehouse_id . "'");
  }

  public function clear()
  {
    $this->data = null;
    $this->db->query("DELETE FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
  }

  public function getRecurringProducts()
  {
    $product_data = array();

    foreach ($this->getProducts() as $value) {
      if ($value['recurring']) {
        $product_data[] = $value;
      }
    }

    return $product_data;
  }

  public function getWeight()
  {
    $weight = 0;

    foreach ($this->getProducts() as $product) {
      if (isset($product['option_data'])){
        foreach ($product['option_data'] as $item) {
          $weight += $this->weight->convert($item['weight'] * $product['quantity'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
        }
      }
    }

    return $weight;
  }

  public function getSubTotal()
  {
    $total = 0;

    foreach ($this->getProducts() as $product) {
      $total += $product['total'];
    }

    return $total;
  }

  public function getTaxes()
  {
    $tax_data = array();

    foreach ($this->getProducts() as $product) {
      if ($product['tax_class_id']) {
        $tax_rates = $this->tax->getRates($product['price'], $product['tax_class_id']);

        foreach ($tax_rates as $tax_rate) {
          if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
            $tax_data[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
          } else {
            $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
          }
        }
      }
    }

    return $tax_data;
  }

  public function getTotal()
  {
    $total = 0;

    foreach ($this->getProducts() as $product) {
      $total += $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
    }

    return $total;
  }

  public function countProducts()
  {
    $product_total = 0;

    $products = $this->getProducts();

    foreach ($products as $product) {
      $product_total += $product['quantity'];
    }

    return $product_total;
  }

  public function hasProducts()
  {
    return count($this->getProducts());
  }

  public function hasRecurringProducts()
  {
    return count($this->getRecurringProducts());
  }

  public function hasStock()
  {
    foreach ($this->getProducts() as $product) {
      if (!$product['stock']) {
        return false;
      }
    }

    return true;
  }

  public function hasShipping()
  {
    foreach ($this->getProducts() as $product) {
      if ($product['shipping']) {
        return true;
      }
    }

    return false;
  }

  public function hasDownload()
  {
    foreach ($this->getProducts() as $product) {
      if ($product['download']) {
        return true;
      }
    }

    return false;
  }
}
