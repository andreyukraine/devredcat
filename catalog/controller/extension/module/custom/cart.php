<?php

class ControllerExtensionModuleCustomCart extends Controller
{
  public function index($setting)
  {

    // Блок отображается
    if (isset($setting['status']) && (bool)$setting['status'] === true) {

      $this->load->language('checkout/cart');
      $this->load->language('extension/module/custom/cart');

      $data['action'] = $this->url->link('extension/module/custom/cart/edit', '', true);

      if ($this->config->get('config_cart_weight')) {
        $data['weight'] = $this->weight->format($this->cart->getWeight(), $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
      } else {
        $data['weight'] = '';
      }

      $this->load->model('tool/image');
      $this->load->model('tool/upload');
      $this->load->model('catalog/product');
      $this->load->model('extension/module/ocwarehouses');

      $data['is_mobile'] = false;
      if ($this->mobile_detect->isMobile()) {
        $data['is_mobile'] = true;
      }

      $data['products'] = array();

      unset($this->session->data['orders']);

      $warehouse_id = 0;
      if (!empty($this->config->get('config_warehouse_id'))) {
        $warehouse_id = $this->config->get('config_warehouse_id');
      }
      $data['warehouse_id'] = $warehouse_id;

      $products = $this->cart->getProducts();
      $products_total = 0;
      $products_total_special = 0;

      foreach ($products as $product) {

        //++Andrey
        $option_data = array();
        $mass_options = array();

        $image = "no_image.png";

        if (!empty($product['option_data'])) {
          foreach ($product['option_data'] as $option) {

            if (!empty($option['image_opt'])) {
              $image = !empty($option['image_opt']) ? $option['image_opt'] : "no_image.png";;
            }

            if ($option['type'] != 'file') {
              $value = $option['value'];
            } else {
              $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

              if ($upload_info) {
                $value = $upload_info['name'];
              } else {
                $value = '';
              }
            }

            $mass_options += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];

            $option_data[] = array(
              'name' => $option['name'],
              'value' => (utf8_strlen($value) > 60 ? utf8_substr($value, 0, 60) . '..' : $value),
              'type' => $option['type'],
              'product_option_id' => (int)$option['product_option_id'],
              'product_option_value_id' => (int)$option['product_option_value_id'],
              'option_id' => $option['option_id'],
              'option_value_id' => $option['option_value_id'],
            );
          }
        } else {
          if (isset($product['image'])) {
            $image = !empty($product['image'] && file_exists(DIR_IMAGE . $product['image'])) ? $product['image'] : "no_image.png";;
          }
        }

        $image = $this->model_tool_image->resize($image, 400, 400);

        $price = $product['price'];
        $price_view = $this->currency->format($price, $this->session->data['currency']);
        $price_total_item_view = $this->currency->format($price * $product['quantity'], $this->session->data['currency']);
        $products_total += $price * $product['quantity'];

        $special = $product['special'] > 0 ? $product['special'] : false;
        $special_view = $product['special'] > 0 ? $this->currency->format($special, $this->session->data['currency']) : false;
        $special_total_item_view = $special > 0 ? $this->currency->format($special * $product['quantity'], $this->session->data['currency']) : false;
        $products_total_special += $special * $product['quantity'];

        $percent = (int)$product['percent'] > 0 ? (int)$product['percent'] : false;

        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product['product_id']);

        // Преобразуем каждую пару ключ-значение в формат "ключ-значение" и объединяем их в строку
        if (!empty($mass_options)) {
          $key_opts = implode('-', array_map(
            function ($key, $value) {
              return "$key-$value";
            },
            array_keys($mass_options),
            $mass_options
          ));
          $uniq_id = $product['product_id'] . "-" . $key_opts;
        } else {
          $uniq_id = $product['product_id'];
        }

        $is_buy = true;
        $total_qty_store = 0;
        $this->load->model('catalog/product');
        $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product['product_id'], $mass_options, true);
        if (isset($stocks[$product['warehouse_id']])) {
          $total_qty_store = $stocks[$product['warehouse_id']];
        }

        if ($total_qty_store - $product['quantity'] > 0) {
          $is_buy = true;
        } else {
          $is_buy = false;
        }

        $data['products'][] = array(
          'cart_id' => $product['cart_id'],
          'product_id' => $product['product_id'],
          'thumb' => $image,
          'name' => $product['name'],
          'sku' => $product_info["sku"],
          'manufacturer' => $product_info["manufacturer"],
          'option' => $option_data,
          'uniq_id' => $uniq_id,
          'is_buy' => $is_buy,
          'quantity' => $product['quantity'],
          'price' => $price,
          'price_view' => $price_view,
          'total' => $product['quantity'] * $price,
          'price_total_item_view' => $price_total_item_view,
          'special' => $special,
          'special_view' => $special_view,
          'special_total_item_view' => $special_total_item_view,
          'percent' => $percent,
          'products_total' => $products_total,
          'products_total_special' => $products_total_special,
          'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
        );

        $this->session->data['orders'][$product['warehouse_id']] = $data['products'];
      }

      foreach ($setting['column'] as $column) {
        $setting['columns'][$column] = $this->language->get('column_' . $column);
      }

      $data['setting'] = $setting;

      //ecommerce
      $data['ecommerce_begin_checkout'] = $this->load->controller('ecommerce/begin_checkout', $data);

      return $this->load->view('extension/module/custom/cart', $data);

    } else {
      return false;
    }

  }

  public function update_cart()
  {

    $this->load->language('checkout/cart');

    $is_buy = false;
    $json = array();
    $json["error"] = "";

    $settings = $this->getCustomSettings();
    $json = array_merge($json, $settings);
    $json['order_by_store'] = $settings['check_qty_cart_by_store'];

    $cart_id = 0;
    if (isset($this->request->post['cart_id'])) {
      $cart_id = (int)$this->request->post['cart_id'];
    }
    $warehouse_id = 0;
    if (!empty($this->config->get('config_warehouse_id'))){
      $warehouse_id = (int)$this->config->get('config_warehouse_id');
    }
    if (isset($this->request->post['warehouse_id'])) {
      $warehouse_id = (int)$this->request->post['warehouse_id'];
    }
    if (isset($this->request->post['event'])) {
      $event = $this->request->post['event'];
    }

    if (isset($this->request->post['quantity'])) {
      $qty = (int)$this->request->post['quantity'];
    } else {
      $qty = 1;
    }

    $option = array();
    $product_id = (int)$this->request->post['product_id'];
    $uniq_id = $product_id;

    $cart_prods = $this->cart->getItemCartNew($cart_id, $warehouse_id);
    $operation_warehouse_id = $warehouse_id;

    if (!empty($cart_prods)) {
      $option = json_decode($cart_prods['option'], true);
      $operation_warehouse_id = (int)$cart_prods['warehouse_id'];
    }

    if (!empty($option)) {
      $key_opts = implode('-', array_map(
        function ($key, $value) {
          return "$key-$value";
        },
        array_keys($option),
        $option
      ));
      $uniq_id = $product_id . "-" . $key_opts;
    }

    $stocks = $this->getProductStocks($product_id, $option, $warehouse_id);
    $total_qty_store = $stocks['store'];
    $total_qty_limit = $stocks['total'];

    $res_change = $this->performQuantityChange($cart_id, $operation_warehouse_id, $event, $qty, $total_qty_limit, $json);
    $cart_id = $res_change['cart_id'];
    $total_qty_in_cart = $res_change['quantity'];

    $json["uniq_id"] = $uniq_id;
    $json["cart_id"] = $cart_id;

    $html_price_total = '';
    $cart_line_param = $this->cart->getProductOptions($product_id, $option);
    if (!empty($cart_line_param)) {
      $price_total = $this->currency->format($cart_line_param[0]['price'] * $total_qty_in_cart, $this->session->data['currency']);
      $special_total = $this->currency->format($cart_line_param[0]['special'] * $total_qty_in_cart, $this->session->data['currency']);

      if ($cart_line_param[0]['special'] > 0) {
        $html_price_total .= '<div class="price-box box-special">';
        $html_price_total .= '<div class="old-price"><span class="price">' . $price_total . '</span></div>';
        $html_price_total .= '<div class="special-price"><span class="price">' . $special_total . '</span></div>';
        $html_price_total .= '</div>';
      } else {
        $html_price_total .= '<div class="price-box box-regular">';
        $html_price_total .= '<div class="regular-price">';
        $html_price_total .= '<span class="price">' . $price_total . '</span>';
        $html_price_total .= '</div>';
        $html_price_total .= '</div>';
      }
    }

    $res_total_qty_in_cart = 0;
    $cart_prods = $this->cart->getProductByStore($product_id, $option, $warehouse_id);
    foreach ($cart_prods as $item_cart) {
      $res_total_qty_in_cart = $res_total_qty_in_cart + (int)$item_cart['quantity'];
    }

    if ($total_qty_store - $res_total_qty_in_cart > 0) {
      $is_buy = true;
    } else {
      $is_buy = false;
    }

    $json['html_price_total'] = $html_price_total;
    $json['total_qty_in_cart'] = $total_qty_in_cart;

    $json['html'] = $this->getCartHtml($product_id, $cart_id, $operation_warehouse_id, $res_total_qty_in_cart, $is_buy);
    $json['product_html'] = $this->getProductHtml($product_id, $cart_id, $res_total_qty_in_cart, $is_buy, $uniq_id);

    $json['total'] = $this->cart->countProducts();

    $json['empty'] = "";
    if (empty($json['total'])) {
      $this->clearSessionCart();
      $json['empty'] = $this->getEmptyCartHtml();
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function update()
  {
    $this->load->model('catalog/product');
    $this->load->language('checkout/cart');
    $json = array();
    $json["error"] = "";
    $is_buy = true;

    $settings = $this->getCustomSettings();
    $json = array_merge($json, $settings);
    $json['order_by_store'] = $settings['check_qty_cart_by_store'];

    $cart_id = 0;
    if (isset($this->request->post['cart_id'])) {
      $cart_id = (int)$this->request->post['cart_id'];
    }

    $warehouse_id = 0;
    if (!empty($this->config->get('config_warehouse_id'))){
      $warehouse_id = (int)$this->config->get('config_warehouse_id');
    }
    if (isset($this->request->post['warehouse_id'])) {
      $warehouse_id = (int)$this->request->post['warehouse_id'];
    }

    if (isset($this->request->post['event'])) {
      $event = $this->request->post['event'];
    } else {
      $event = 'update';
    }

    if (isset($this->request->post['quantity'])) {
      $qty = (int)$this->request->post['quantity'];
    } else {
      $qty = 1;
    }

    $product_id = (int)$this->request->post['product_id'];
    $uniq_id = $product_id;

    $option = array();
    if (isset($this->request->post['option'])) {
      $opt_string = '';
      if (is_array($this->request->post['option'])) {
        if (isset($this->request->post['option'][$product_id])) {
          $opt_string = $this->request->post['option'][$product_id];
        }
      } else {
        $opt_string = $this->request->post['option'];
      }

      if (!empty($opt_string)) {
        $option_parce = explode('-', $opt_string);
        if (count($option_parce) == 2) {
          $option = [(int)$option_parce[0] => (int)$option_parce[1]];
        }
        $uniq_id = $product_id . "-" . $opt_string;
      }
    }

    $operation_warehouse_id = $warehouse_id;

    if ($cart_id > 0) {
      $cart_info = $this->cart->getItemCartNew($cart_id, $warehouse_id);
      if (!empty($cart_info)) {
        $option = json_decode($cart_info['option'], true);
        $operation_warehouse_id = (int)$cart_info['warehouse_id'];
        if (!empty($option)) {
          $key_opts = implode('-', array_map(
            function ($key, $value) {
              return "$key-$value";
            },
            array_keys($option),
            $option
          ));
          $uniq_id = $product_id . "-" . $key_opts;
        }
      } else {
        $cart_id = 0;
      }
    }

    if ($cart_id <= 0) {
      if ($warehouse_id > 0) {
        $warehouses_in_cart = $this->cart->getStoresByProduct($product_id, $option, $warehouse_id);
        if (!empty($warehouses_in_cart)) {
          foreach ($warehouses_in_cart as $item_cart) {
            $cart_id = $item_cart['cart_id'];
            $operation_warehouse_id = $item_cart['warehouse_id'];
          }
        }
      } else {
        $cart_prods = $this->cart->getProductByStore($product_id, $option);
        if (!empty($cart_prods)) {
          foreach ($cart_prods as $item_cart) {
            $cart_id = $item_cart['cart_id'];
            $operation_warehouse_id = $item_cart['warehouse_id'];
            break;
          }
        }
      }
    }

    $stocks = $this->getProductStocks($product_id, $option, $warehouse_id);
    $total_qty_store = $stocks['store'];
    $total_qty_limit = $stocks['total'];

    $total_qty_in_cart = 0;
    if ($cart_id <= 0) {
      if ($event == 'plus' || $event == 'update' || $event == 'add') {
        $total_qty_in_cart = $qty;
        if ($total_qty_in_cart > $total_qty_limit) {
          $total_qty_in_cart = $total_qty_limit;
          $json["error"] = "Кількість перевищює наявність";
        }
        if ($total_qty_in_cart > 0) {
          $recurring_id = 0;
          if (isset($this->request->post['recurring_id'])) {
            $recurring_id = (int)$this->request->post['recurring_id'];
          }
          $cart_id = $this->cart->add($product_id, $total_qty_in_cart, $option, $recurring_id, $warehouse_id);
        }
      }
    } else {
      $res_change = $this->performQuantityChange($cart_id, $operation_warehouse_id, $event, $qty, $total_qty_limit, $json);
      $cart_id = $res_change['cart_id'];
      $total_qty_in_cart = $res_change['quantity'];
    }

    $json["uniq_id"] = $uniq_id;
    $json["cart_id"] = $cart_id;

    $html_price_total = '';
    $cart_line_param = $this->cart->getProductOptions($product_id, $option);
    if (!empty($cart_line_param)) {
      $price_total = $this->currency->format($cart_line_param[0]['price'] * $total_qty_in_cart, $this->session->data['currency']);
      $special_total = $this->currency->format($cart_line_param[0]['special'] * $total_qty_in_cart, $this->session->data['currency']);

      if ($cart_line_param[0]['special'] > 0) {
        $html_price_total .= '<div class="price-box box-special">';
        $html_price_total .= '<div class="special-price"><span class="price">' . $price_total . '</span></div>';
        $html_price_total .= '<div class="old-price"><span class="price">' . $special_total . '</span></div>';
        $html_price_total .= '</div>';
      } else {
        $html_price_total .= '<div class="price-box box-regular">';
        $html_price_total .= '<div class="regular-price">';
        $html_price_total .= '<span class="price">' . $price_total . '</span>';
        $html_price_total .= '</div>';
        $html_price_total .= '</div>';
      }
    }

    $res_total_qty_in_cart = 0;
    $cart_prods = $this->cart->getProductByStore($product_id, $option, $warehouse_id);

    $json["prod"] = "";
    $json["opt"] = "";

    foreach ($cart_prods as $item_cart) {
      $res_total_qty_in_cart = $res_total_qty_in_cart + (int)$item_cart['quantity'];
      $json["prod"] = $item_cart['name'];
      if (!empty($item_cart['option_data'])) {
        $json["opt"] = $item_cart['option_data'][0]['name'] . ': ' . $item_cart['option_data'][0]['value'];
      }
    }

    if ($total_qty_store - $res_total_qty_in_cart > 0) {
      $is_buy = true;
    } else {
      $is_buy = false;
    }

    $json['html_price_total'] = $html_price_total;
    $json['total_qty_in_cart'] = $total_qty_in_cart;

    $json['html'] = $this->getProductHtml($product_id, $cart_id, $res_total_qty_in_cart, $is_buy, $uniq_id);

    $json['total'] = $this->cart->countProducts();

    //ecommerce
    $json['ecommerce_products'] = $this->load->controller('ecommerce/add_to_cart', $cart_prods);

    $json['empty'] = "";
    if (empty($json['total'])) {
      $this->clearSessionCart();
      $json['empty'] = $this->getEmptyCartHtml();
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function update_order()
  {

    $this->load->language('checkout/cart');
    $json = array();
    $json["error"] = "";
    $is_buy = true;

    $settings = $this->getCustomSettings();
    $json = array_merge($json, $settings);
    $json['order_by_store'] = $settings['check_qty_cart_by_store'];

    $cart_id = (int)$this->request->post['cart_id'];
    $order_id = (int)$this->request->post['order_id'];
    $product_id = (int)$this->request->post['product_id'];
    $uniq_id = $product_id;
    if (isset($this->request->post['quantity'])) {
      $quantity = (int)$this->request->post['quantity'];
    } else {
      $quantity = 1;
    }

    if (isset($this->request->post['old_value'])) {
      $old_value = (int)$this->request->post['old_value'];
    } else {
      $old_value = 0;
    }

    $option = array();
    $line_cart = null;
    if ($cart_id > 0) {
      $line_cart = $this->cart->getItemCart($cart_id);
      if (!empty($line_cart)) {
        if (isset($line_cart['option'])) {
          $option = json_decode($line_cart['option'], true);
        }
      } else {
        $cart_id = 0;
        if (isset($this->request->post['option'])) {
          $option = array_map(function ($value) {
            return is_numeric($value) ? (int)$value : $value;
          }, array_filter($this->request->post['option']));
        }
      }
    } else {
      if (isset($this->request->post['option'])) {
        $option = array_map(function ($value) {
          return is_numeric($value) ? (int)$value : $value;
        }, array_filter($this->request->post['option']));
      }
    }

    //stock warehouses
    $total_qty = 0;
    $this->load->model('catalog/product');
    $stocks = $this->model_catalog_product->getProductOptStockWarehouse($product_id, $order_id, $option);
    if (!empty($stocks)) {
      foreach ($stocks as $stock) {
        $total_qty += $stock;
      }
    }

    $event = $this->request->post['event'];

    switch ($event) {
      case "plus":
        if ($quantity + 1 <= $total_qty) {
          $quantity++;
          if ($line_cart) {
            $this->cart->update($line_cart['cart_id'], (int)$line_cart['quantity'] + 1, $order_id);
          }
        } else {
          $json["error"] = "Кількість перевищює наявність";
        }
        break;
      case "minus":
        if ($quantity > 1) {
          $quantity = $quantity - 1;
          if ($line_cart) {
            $this->cart->update($line_cart['cart_id'], (int)$line_cart['quantity'] - 1, $order_id);
          }
        } else {
          $this->cart->remove($cart_id, $order_id);
          $cart_id = 0;
          $quantity = 0;
        }
        break;
      case "update":
        if ($quantity > 0) {
          if ($quantity > $total_qty) {
            $quantity = $total_qty;
            $json["error"] = "Кількість перевищює наявність";
          }
          if ($line_cart) {
            $this->cart->update($line_cart['cart_id'], (int)$quantity, $order_id);
          }
        } else if ($cart_id > 0) {
          $this->cart->remove($cart_id, $order_id);
          $cart_id = 0;
          $quantity = 0;
        }
        break;
    }

    if ($total_qty - $quantity > 0) {
      $is_buy = true;
    } else {
      $is_buy = false;
    }

    $json["cart_id"] = $cart_id;

    if ($line_cart && isset($line_cart['option'])) {
      $option = json_decode($line_cart['option'], true);
      if (!empty($option)) {
        $key_opts = implode('-', array_map(
          function ($key, $value) {
            return "$key-$value";
          },
          array_keys($option),
          $option
        ));
        $uniq_id = $product_id . "-" . $key_opts;
      }
    }
    $json["uniq_id"] = $uniq_id;

    $html_price_total = '';
    $cart_line_param = $this->cart->getProductOptions($product_id, $option);
    if (!empty($cart_line_param)) {

      $price_total = $this->currency->format($cart_line_param[0]['price'] * $quantity, $this->session->data['currency']);
      $special_total = $this->currency->format($cart_line_param[0]['special'] * $quantity, $this->session->data['currency']);

      if ($cart_line_param[0]['special'] > 0) {
        $html_price_total .= '<div class="price-box box-special">';
        $html_price_total .= '<div class="special-price"><span class="price">' . $price_total . '</span></div>';
        $html_price_total .= '<div class="old-price"><span class="price">' . $special_total . '</span></div>';
        $html_price_total .= '</div>';
      } else {
        $html_price_total .= '<div class="price-box box-regular">';
        $html_price_total .= '<div class="regular-price">';
        $html_price_total .= '<span class="price">' . $price_total . '</span>';
        $html_price_total .= '</div>';
        $html_price_total .= '</div>';
      }
    }
    $json['html_price_total'] = $html_price_total;

    $json['order_id'] = $order_id;
    $json['html'] = $this->getOrderHtml($product_id, $cart_id, $order_id, $quantity, $is_buy);
    $json['total'] = $this->cart->countProducts();

    $json['empty'] = "";
    if (empty($json['total'])) {
      $this->clearSessionCart();
      $json['empty'] = $this->getEmptyCartHtml();
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function remove()
  {
    $this->load->model('catalog/product');
    $this->load->language('checkout/cart');

    $json = array();
    $is_buy = false;

    $product_id = $this->request->post['product_id'];
    $uniq_id = $product_id;

    $cart_id = $this->request->post['cart_id'];

    $line_cart = $this->cart->getItemCart($cart_id);

    $option = array();
    if (isset($line_cart['option'])) {
      $option = json_decode($line_cart['option'], true);
      // Преобразуем каждую пару ключ-значение в формат "ключ-значение" и объединяем их в строку
      if (!empty($option)) {
        $key_opts = implode('-', array_map(
          function ($key, $value) {
            return "$key-$value";
          },
          array_keys($option),
          $option
        ));
        $uniq_id = $product_id . "-" . $key_opts;
      }
    }

    // Remove
    if ($line_cart) {
      $this->cart->remove($cart_id, $line_cart['warehouse_id']);

      //отримуємо список товарів по складу в кошику
      $all_prd_by_store = $this->cart->getItemsCartByStore($line_cart['warehouse_id']);
      if ($all_prd_by_store == null) {
        //якщ список пусти треба видалити з сесії замовлення
        if (isset($this->session->data['orders'][$line_cart['warehouse_id']])) {
          unset($this->session->data['orders'][$line_cart['warehouse_id']]);
        }
      }
    }

    $cart_id = 0;
    $res_total_qty_in_cart = 0;
    $warehouse_id_for_remove = $line_cart ? $line_cart['warehouse_id'] : 0;
    $cart_prods = $this->cart->getProductByStore($product_id, $option, $warehouse_id_for_remove);
    foreach ($cart_prods as $item_cart) {
      $res_total_qty_in_cart = $res_total_qty_in_cart + (int)$item_cart['quantity'];
    }

    $total_qty_store = 0;
    $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_id, $option, true);
    if (isset($stocks[$warehouse_id_for_remove])) {
      $total_qty_store = $stocks[$warehouse_id_for_remove];
    }

    if ($total_qty_store - $res_total_qty_in_cart > 0) {
      $is_buy = true;
    } else {
      $is_buy = false;
    }

    $json['html'] = $this->getCartHtml($product_id, $cart_id, $warehouse_id_for_remove, $res_total_qty_in_cart, $is_buy);
    $json['product_html'] = $this->getProductHtml($product_id, $cart_id, $res_total_qty_in_cart, $is_buy, $uniq_id);

    //cart-btns-102239-89706-165109 in-cart

    $json['product_id'] = $product_id;
    $json["uniq_id"] = $uniq_id;

    $json['total'] = $this->cart->countProducts();

    $json['empty'] = "";
    if (empty($json['total'])) {
      $this->clearSessionCart();
      $json['empty'] = $this->getEmptyCartHtml();
    }

    $settings = $this->getCustomSettings();
    $json = array_merge($json, $settings);
    $json['order_by_store'] = $settings['check_qty_cart_by_store'];

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function remove_order()
  {
    $this->load->language('checkout/cart');

    $json = array();

    $product_id = $this->request->post['product_id'];
    $cart_id = $this->request->post['cart_id'];
    $order_id = $this->request->post['order_id'];
    $qty = (int)$this->request->post['qty'];

    $settings = $this->getCustomSettings();
    $json = array_merge($json, $settings);
    $json['order_by_store'] = $settings['check_qty_cart_by_store'];

    $line_cart = $this->cart->getItemCart($cart_id);

    // Remove
    if (!empty($line_cart)) {
      if ((int)$line_cart['quantity'] - $qty > 0) {
        $this->cart->update($line_cart['cart_id'], (int)$line_cart['quantity'] - $qty, (int)$line_cart['warehouse_id']);
      } else {
        $this->cart->remove($cart_id, (int)$line_cart['warehouse_id']);
      }
    } else {
      $cart_id = 0;
    }

    if (isset($this->session->data['shipping_method'][$order_id])) {
      unset($this->session->data['shipping_method'][$order_id]);
    }
    if (isset($this->session->data['payment_method'][$order_id])) {
      unset($this->session->data['payment_method'][$order_id]);
    }

    $json['product_id'] = $product_id;
    $json["cart_id"] = $cart_id;
    $json["order_id"] = $order_id;

    $json['total'] = $this->cart->countProducts();

    $json['empty'] = "";
    if (empty($json['total'])) {
      $this->clearSessionCart();
      $json['empty'] = $this->getEmptyCartHtml();
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clear()
  {

    $json = array();

    // Clear
    $this->cart->clear();

    $json['empty'] = true;

    $this->clearSessionCart();
    unset($this->session->data['reward']);

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function remind()
  {
    $json = array();

    $data['prods'] = array();

    $products = $this->cart->getProducts();

    foreach ($products as $product) {
      $data['prods'][] = array(
        "ean" => $product["model"]
      );
    }

    // Преобразуем массив в JSON
    $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://b2b.detta.com.ua/api/hs/site/products',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: Basic 0JDQtNC80LjQvdC40YHRgtGA0LDRgtC+0YA6MjkxMTcwNjg3NQ==',
        'Cookie: catalog_type=0; currency=UAH; language=uk-ua'
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $response_mass = json_decode($response, true);
    if (!empty($response_mass['prods'])) {
      $this->load->model('catalog/product');
      foreach ($response_mass['prods'] as $item) {
        foreach ($products as $product) {

          $this->model_catalog_product->updateProductQtyStore($product['model'], $item['qty_base'], $product['warehouse_id']);

          if ($product['model'] == $item['ean']) {
            if ($product['quantity'] > $item['qty_base']) {
              $json['remind_prods'][(int)$product['cart_id']] = $item['qty_base'];
            }
          }
        }
      }
    }

    if (isset($json['remind_prods'])){
      $current_date = date('d-m-Y H:i:s');
      $json['error'] = 'Станом на ' . $current_date . ' недостатній залишок деяких товарів на складі';
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function ask_store_cart()
  {
    $json['html'] = '';

    $settings = $this->getCustomSettings();
    $json = array_merge($json, $settings);
    $json['order_by_store'] = $settings['check_qty_cart_by_store'];

    if (isset($this->request->post['event'])) {
      $event = $this->request->post['event'];
    }

    if (isset($this->request->post['product_id'])) {
      if ($settings['check_qty_cart_by_store'] > 0) {
        $product_id = (int)$this->request->post['product_id'];

        $option = array();

        if (isset($this->request->post['option'])) {
          $opt_string = $this->request->post['option'][$product_id];
          $option_parce = explode('-', $opt_string);
          if (count($option_parce) == 2) {
            $option = [(int)$option_parce[0] => (int)$option_parce[1]];
          }

          if (!empty($option)) {
            $uniq_id = $product_id . "-" . $opt_string;
          }
        }

        switch ($event) {
          case "add":
          case "plus":

            //stock warehouses
            $this->load->model('catalog/product');
            $this->load->model('extension/module/ocwarehouses');

            $this->load->language('extension/module/ocwarehouses');
            $data["title_stock_warehouses"] = $this->language->get('title_stock_warehouses');

            $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_id, $option, true);
            if (!empty($stocks)) {

              $data = array();
              foreach ($stocks as $warehouse_id => $stock) {
                $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);

                $total_qty = 0;
                $total_qty_store = 0;
                $total_qty_free = 0;
                $total_qty_in_cart = 0;

                $warehouses_in_cart = $this->cart->getStoresByProduct($product_id, $option, $warehouse_id);
                if (!empty($warehouses_in_cart)) {
                  foreach ($warehouses_in_cart as $item_cart) {
                    $total_qty_in_cart = $item_cart['quantity'];
                    $total_qty_store = $item_cart['store_quantity'];
                    $total_qty_free = $total_qty_store - $total_qty_in_cart;
                  }
                } else {
                  $total_qty_free = (int)$stock;
                }

                if ($total_qty_free > 0) {
                  $data["warehouses"][] = array(
                    'warehouse_id' => $warehouse_id,
                    'name' => $warehouse['name'],
                    'quantity' => $total_qty_free
                  );
                }
              }
            }
            break;
          case "minus":
            $data["title_stock_warehouses"] = "Виберіть склад з якого видалити кількість:";
            $data["warehouses"] = $this->cart->getStoresByProduct($product_id, $option);
            break;
        }
        $json['html'] .= $this->load->view('extension/module/stockproduct_check', $data);

      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  private function getCustomSettings() {
    $settings = array(
      'order_org'               => 0,
      'split_order_store'       => 0,
      'check_qty_cart_by_store' => 0
    );

    if (isset($this->session->data['client_address_id'])) {
      $this->load->model('account/address');
      $select_adress = $this->model_account_address->getAddress($this->session->data['client_address_id']);
      if ($select_adress && $select_adress['customer_type'] > 0) {
        $settings['order_org'] = 1;
      }
    } else {
      if ($this->config->get('module_custom_split_order_store') != null) {
        if ((int)$this->config->get('module_custom_split_order_store') > 0) {
          $settings['split_order_store'] = 1;
        }
      }
      if ($this->config->get('module_custom_check_qty_cart_by_store') != null) {
        if ((int)$this->config->get('module_custom_check_qty_cart_by_store') > 0) {
          $settings['check_qty_cart_by_store'] = 1;
        }
      }
    }
    return $settings;
  }

  private function getCartHtml($product_id, $cart_id, $warehouse_id, $quantity, $is_buy) {
    $html = '';
    if ($quantity > 0) {
      $html .= '<div class="minus" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-store="' . $warehouse_id . '" onclick="custom_cart.handleClickInCart(this, \'minus\')">-</div>';
    } else {
      $html .= '<div class="minus not-available" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-store="' . $warehouse_id . '">-</div>';
    }
    $html .= '<input id="qty-' . $product_id . '-' . $cart_id . '" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-store="' . $warehouse_id . '" type="number" name="quantity" value="' . $quantity . '" class="quantity-input"/>';
    if ($is_buy) {
      $html .= '<div class="plus" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-store="' . $warehouse_id . '" onclick="custom_cart.handleClickInCart(this, \'plus\')"></div>';
    } else {
      $html .= '<div class="plus not-available" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-store="' . $warehouse_id . '"></div>';
    }
    return $html;
  }

  private function getProductHtml($product_id, $cart_id, $quantity, $is_buy, $uniq_id = '') {
    if (!$uniq_id) {
      $uniq_id = $product_id;
    }
    if ($quantity <= 0) {
      $html = '<div class="bl-add-in-cart cart-btns-' . $uniq_id . '" data-key="' . $product_id . '" data-cart="0"' . ($is_buy ? ' onclick="custom_cart.handleClickPlus(this)"' : '') . '>';
      if ($is_buy) {
        $html .= '<div class="minus-add"></div>';
        $html .= '<div class="plus-add-text">Додати до кошика</div>';
      }else{
        $html .= '<div class="notify" data-product="' . $product_id . '" data-opt="' . $uniq_id . '" onclick="notify.open(this)">Повідомити про наявність</div>';
      }
      if ($is_buy) {
        $html .= '<div class="plus-add"></div>';
      } else {
        $html .= '<div class="plus-add not-available"></div>';
      }
    } else {
      $html = '<div class="bl-add-in-cart cart-btns-' . $uniq_id . '" data-key="' . $product_id . '" data-cart="' . $cart_id . '">';
      $html .= '<div class="minus" data-key="' . $product_id . '" data-cart="' . $cart_id . '" onclick="custom_cart.handleClickMinus(this)">-</div>';
      $html .= '<input id="qty-' . $product_id . '-' . $cart_id . '" data-key="' . $product_id . '" data-cart="' . $cart_id . '" type="number" name="quantity" value="' . $quantity . '" class="quantity-input"/>';

      if ($is_buy) {
        $html .= '<div class="plus" data-key="' . $product_id . '" data-cart="' . $cart_id . '" onclick="custom_cart.handleClickPlus(this)"></div>';
      } else {
        $html .= '<div class="plus not-available" data-key="' . $product_id . '" data-cart="' . $cart_id . '"></div>';
      }
    }
    $html .= '</div>';
    return $html;
  }

  private function getOrderHtml($product_id, $cart_id, $order_id, $quantity, $is_buy) {
    $html = '';
    $html .= '<input type="hidden" class="old-value" id="old-value-' . $order_id . '-' . $product_id . '" name="old_value" value="">';
    if ($quantity > 0) {
      $html .= '<div class="minus-order btn" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-order="' . $order_id . '">-</div>';
    } else {
      $html .= '<div class="minus-order btn not-available" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-order="' . $order_id . '">-</div>';
    }

    $html .= '<input id="qty-' . $order_id . '-' . $product_id . '-' . $cart_id . '" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-order="' . $order_id . '" type="number" name="quantity" value="' . $quantity . '" class="quantity-input-order"/>';

    if ($is_buy) {
      $html .= '<div class="plus-order btn" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-order="' . $order_id . '"></div>';
    } else {
      $html .= '<div class="plus-order btn not-available" data-key="' . $product_id . '" data-cart="' . $cart_id . '" data-order="' . $order_id . '"></div>';
    }
    return $html;
  }

  private function getEmptyCartHtml() {
    return '<li class="text-center">
          <svg class="icon-empty-cart" id="icon-empty-shop-cart" xmlns="http://www.w3.org/2000/svg" width="195" height="151" fill="none" viewBox="0 0 195 151">
            <path fill="#9CA9BC" d="m170.452 109.713 1.98.273-1.49 10.798-1.982-.274 1.492-10.797ZM38 45h2v32h-2V45Zm0 39h2v9h-2v-9Zm107.8 42.7c.2.3.5.4.8.4.2 0 .4-.1.6-.2 1.5-1 3.6-1.1 5.2-.1.5.3 1.1.2 1.4-.3.3-.5.2-1.1-.3-1.4-2.2-1.4-5.2-1.4-7.4.1-.5.4-.6 1-.3 1.5ZM70 113h9v2h-9v-2ZM5 144h13v2H5v-2Z"></path>
            <path fill="#9CA9BC" d="m173 144 .3-.9 5-39.1h.7c2.2 0 4-1.4 4-3.2v-1.5c0-1.8-1.8-3.2-4-3.2h-34.7l9.1-22c.8-2-.1-4.4-2.2-5.2-1-.4-2.1-.4-3.1 0s-1.8 1.2-2.2 2.2L136.5 94c-1.2.4-2.1 1.1-2.9 2H132V37c0-3.3-2.7-6-5.9-6-6.3 0-12.6-.7-18.9-.9-4-14.7-14-25.1-25.7-25.1S59.8 15.4 55.8 30c-6.4.2-12.7.5-18.8 1-3.3 0-6 2.7-6 6v75.9L27.1 144H23v2h167v-2h-17Zm-1.7-1.2c-.1.9-.9 1.2-1.6 1.2h-33.8l-3.9-31.1V104h2.2c1.1 1 2.3 1.7 3.9 1.7s2.9-.6 3.9-1.7h34.3l-5 38.8Zm9.7-43.6v1.5c0 .6-.8 1.2-2 1.2h-35.1c.3-.7.5-1.5.5-2.3 0-.6-.1-1.1-.2-1.7H179c1.2.1 2 .8 2 1.3Zm-33.2-27.5c.2-.5.6-.9 1.1-1.1.5-.2 1-.2 1.5 0 1 .4 1.5 1.6 1.1 2.6l-9.1 22c-1.1-1-2.4-1.5-3.6-1.7l9-21.8Zm-9.5 24c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4Zm-5.7 2.3c-.2.5-.2 1.1-.2 1.7 0 .8.2 1.6.5 2.3h-.9v-4h.6ZM81.5 7c10.7 0 19.8 9.5 23.6 23-16.1-.5-31.9-.5-47.2 0 3.8-13.5 13-23 23.6-23ZM32.8 115H59v-2H33V37c0-2.2 1.8-4 4.1-4 6-.4 12.1-.7 18.3-.9-.5 2.3-.9 4.7-1.1 7.2-2.3.4-4.1 2.4-4.1 4.8 0 2.7 2.2 4.9 4.9 4.9s4.9-2.2 4.9-4.9c0-2.3-1.6-4.1-3.6-4.7.2-2.5.6-5 1.2-7.3 15.6-.5 31.7-.5 48.2.1.6 2.3.9 4.8 1.2 7.3-2.1.5-3.6 2.4-3.6 4.7 0 2.7 2.2 4.9 4.9 4.9s4.9-2.2 4.9-4.9c0-2.4-1.8-4.4-4.1-4.8-.2-2.4-.6-4.8-1.1-7.1 6.1.2 12.2.5 18.3.9 2.2 0 4 1.8 4 4v76H89v2h41.2l3.6 29H29.1l3.7-29.2Zm23.3-73.6c1 .4 1.8 1.5 1.8 2.6 0 1.6-1.3 2.9-2.9 2.9-1.6 0-2.9-1.3-2.9-2.9 0-1.3.8-2.3 1.9-2.7 0 .9-.1 1.8-.1 2.7h2c.1-.9.1-1.8.2-2.6ZM107 44h2c0-.9 0-1.8-.1-2.7 1.1.4 1.9 1.4 1.9 2.7 0 1.6-1.3 2.9-2.9 2.9-1.6 0-2.9-1.3-2.9-2.9 0-1.2.7-2.2 1.8-2.6.2.8.2 1.7.2 2.6Z"></path>
            <path fill="#9CA9BC" d="M145.6 120.2a2 2 0 1 0 .001-3.999 2 2 0 0 0-.001 3.999Zm8 0a2 2 0 1 0 .001-3.999 2 2 0 0 0-.001 3.999Zm-67-40.9c-2.8-1.8-6.7-1.8-9.4.2-.5.3-.6.9-.3 1.4.2.3.5.4.8.4.2 0 .4-.1.6-.2 2.1-1.5 5.1-1.5 7.2-.1.5.3 1.1.2 1.4-.3.3-.5.1-1.1-.3-1.4Zm-12.9-5.9a2.7 2.7 0 1 0 0-5.4 2.7 2.7 0 0 0 0 5.4Zm16 0a2.7 2.7 0 1 0 0-5.4 2.7 2.7 0 0 0 0 5.4Z"></path>
          </svg>
        </li>
        <li>
        <p class="text-24 bl-bold text-center">' . $this->language->get('text_empty') . '</p>
        <p class="cart-empty-info-text text-center">' . $this->language->get('text_empty_marketing') . '</p>
      </li>';
  }

  private function clearSessionCart() {
    unset($this->session->data['orders']);
    unset($this->session->data['shipping_methods']);
    unset($this->session->data['shipping_method']);
    unset($this->session->data['payment_methods']);
    unset($this->session->data['payment_method']);
  }

  private function getProductStocks($product_id, $option, $warehouse_id) {
    $this->load->model('catalog/product');
    $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_id, $option, true);

    $total_qty = 0;
    $total_qty_store = 0;
    if (!empty($stocks)) {
      foreach ($stocks as $warehouse_id_key => $stock) {
        $total_qty += (int)$stock;
        if ($warehouse_id > 0 && $warehouse_id_key == $warehouse_id) {
          $total_qty_store = (int)$stock;
        }
      }
    }
    if ($warehouse_id == 0) {
      $total_qty_store = $total_qty;
    }
    return array('total' => $total_qty, 'store' => $total_qty_store);
  }

  private function performQuantityChange($cart_id, $warehouse_id, $event, $qty, $limit, &$json) {
    $total_qty_in_cart = 0;
    $cart_item = $this->cart->getItemCart($cart_id);
    if ($cart_item) {
      $total_qty_in_cart = (int)$cart_item['quantity'];
    }

    switch ($event) {
      case "plus":
        if ($total_qty_in_cart + 1 <= $limit) {
          $total_qty_in_cart++;
        } else {
          $json["error"] = "Кількість перевищює наявність";
        }
        break;
      case "minus":
        if ($total_qty_in_cart > 0) {
          $total_qty_in_cart--;
        }
        break;
      case "update":
        if ($qty > 0) {
          if ($qty <= $limit) {
            $total_qty_in_cart = $qty;
          } else {
            $total_qty_in_cart = $limit;
            $json["error"] = "Кількість перевищює наявність";
          }
        } else {
          $total_qty_in_cart = 0;
        }
        break;
    }

    if ($total_qty_in_cart > 0) {
      if ($cart_id > 0) {
        $this->cart->update($cart_id, $total_qty_in_cart, $warehouse_id);
      }
    } elseif ($total_qty_in_cart == 0 && $cart_id > 0) {
      $this->cart->remove($cart_id, $warehouse_id);
      $cart_id = 0;
    }

    return array('cart_id' => $cart_id, 'quantity' => $total_qty_in_cart);
  }
}
