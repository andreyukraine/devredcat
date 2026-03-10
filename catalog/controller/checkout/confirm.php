<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCheckoutConfirm extends Controller
{
  public function index()
  {

    // Перевірка сесії (якщо це AJAX-запит від модуля custom)
    if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && $this->request->server['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
      if (!isset($this->session->data['orders']) && !isset($this->session->data['orders_history'])) {
        $this->load->language('extension/module/custom/customer');
        $json = array();
        $json['error']['warning'] = $this->language->get('error_session');
        $json['redirect'] = $this->url->link('extension/module/custom', '', true);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
      }
    }

    $data = array();
    $this->load->language('common/confirm');

    $this->document->setTitle($this->language->get('heading_title_confirm'));
    $this->document->setRobots('noindex,follow');

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_done'),
      'href' => ''
    );

    $redirect = '';

    // Validate if payment method has been set.
    if (!isset($this->session->data['payment_method'])) {
      $redirect = $this->url->link('checkout/checkout', '', true);
    }

    // Validate if shipping method has been set.
    if (!isset($this->session->data['shipping_method'])) {
      $redirect = $this->url->link('checkout/checkout', '', true);
    }

    // Validate cart has products and has stock.
    if (!$this->cart->hasProducts()) {
      $redirect = $this->url->link('checkout/cart');
    }

    if (!$redirect) {

      $this->load->language('checkout/checkout');
      $this->load->model('account/customer');
      $this->load->model('checkout/order');
      $this->load->model('tool/upload');
      $this->load->model('extension/module/ocwarehouses');
      $this->load->model('localisation/language');

      //TODO тут треба зробити перевірку якщо декілька замовлень
      if (isset($this->session->data['orders'])) {
        $data['orders'] = []; // Инициализируем массив для хранения всех заказов
        $number_ord = 1;
        foreach ($this->session->data['orders'] as $store_id => $order) {

          //тут робимо підміну бо склад один а різні організації
          if (isset($this->session->data['client_address_id'])) {
            $this->load->model('account/address');
            $select_adress = $this->model_account_address->getAddress($this->session->data['client_address_id']);
            if ($select_adress) {
              if ($select_adress['customer_type'] > 0) {

                //якщо замовлення з різних організацій то  беремо склад з налаштування
                $store_id_ord = 0;

                if (!empty($this->config->get('config_warehouse_id'))) {
                  $store_id_ord = (int)$this->config->get('config_warehouse_id');
                }

                $orderData = $this->addOrder($order, $store_id_ord);
              } else {
                $orderData = $this->addOrder($order, $store_id);
              }
            } else {
              $orderData = $this->addOrder($order, $store_id);
            }
          } else {
            $orderData = $this->addOrder($order, $store_id);
          }


          $data['orders'][$store_id]["store"] = $orderData['warehouse'];
          $data['orders'][$store_id]["store_id"] = (int)$store_id;
          $data['orders'][$store_id]["number"] = $number_ord;

          $data['orders'][$store_id]["shipping"][] = array(
            'title' => $orderData['shipping_method'],
            'city' => $orderData['shipping_city'],
            'type' => $orderData['shipping_type'],
            'address' => $orderData['address']
          );

          $data['orders'][$store_id]["payment"][] = array(
            'title' => $orderData['payment_method'],
            'action_pay' => $this->load->controller('extension/payment/' . $orderData['payment_code'])
          );

          $data['orders'][$store_id]["products"] = $orderData['products'];

          $data['orders'][$store_id]["total"] = $orderData['total'];
          $data['orders'][$store_id]["invoice"] = $orderData['invoice_prefix'] . $orderData['order_id_db'];
          $data['orders'][$store_id]["url"] = $this->url->link('account/order/info', 'order_id=' . $orderData['order_id_db'], true);

          $number_ord++;
        }
        $this->session->data['orders_history'] = $data['orders'];
      }


      $this->cart->clear();

      unset($this->session->data['orders']);
      unset($this->session->data['shipping_method']);
      unset($this->session->data['shipping_methods']);
      unset($this->session->data['shipping_address']);
      unset($this->session->data['custom_city']);
      unset($this->session->data['dropshipping_tth']);
      unset($this->session->data['dropshipping_tth_filename']);
      unset($this->session->data['dropshipping_tth_files']);
      unset($this->session->data['payment_method']);
      unset($this->session->data['payment_methods']);
      unset($this->session->data['payment_address']);
      unset($this->session->data['guest']);
      unset($this->session->data['comment']);
      unset($this->session->data['order_id']);
      unset($this->session->data['coupon']);
      unset($this->session->data['reward']);
      unset($this->session->data['voucher']);
      unset($this->session->data['vouchers']);
      unset($this->session->data['totals']);
      unset($this->session->data['client_address_id']);
      unset($this->session->data['customer_override']);

    } else {
      $data['redirect'] = $redirect;
    }

    if (isset($this->session->data['orders_history'])) {
      $data['orders'] = $this->session->data['orders_history'];
    } else {
      $data['orders'] = array();
    }
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->load->model('tool/image');
    $img_bg = "confirm_bg.jpg";

    if (file_exists(DIR_IMAGE . $img_bg)) {
      list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . $img_bg);
      if ($width_orig > 0) {
        $height = (1000 / $width_orig) * $height_orig;
      } else {
        $height = 1000;
      }

      $data["image"] = $this->model_tool_image->resize($img_bg, 1000, $height);
    } else {
      list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . "no_image.png");
      if ($width_orig > 0) {
        $height = (1000 / $width_orig) * $height_orig;
      } else {
        $height = 1000;
      }

      $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, $height);
    }

    //ecommerce
    if (isset($this->session->data['orders_history'])) {
      $data['ecommerce_purchase'] = $this->load->controller('ecommerce/purchase', $this->session->data['orders_history']);
    } else {
      $data['ecommerce_purchase'] = '';
    }

    $this->response->setOutput($this->load->view('checkout/confirm', $data));


  }

  function addOrder($order, $store_id = 0)
  {

    $order_data = array();

    $langId = 1; // Default
    if (isset($this->session->data['language'])) {
      $lang_info = $this->model_localisation_language->getLanguageByCode($this->session->data['language']);
      if ($lang_info) {
        $langId = (int)$lang_info['language_id'];
      }
    }

    if ($this->customer->isLogged()) {
      $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
      $order_data['customer_id'] = $this->customer->getId();
      $order_data['customer_group_id'] = $customer_info['customer_group_id'];

      $order_data['firstname'] = isset($this->session->data['customer_override']['firstname']) ? $this->session->data['customer_override']['firstname'] : $customer_info['firstname'];
      $order_data['lastname'] = isset($this->session->data['customer_override']['lastname']) ? $this->session->data['customer_override']['lastname'] : $customer_info['lastname'];
      $order_data['email'] = isset($this->session->data['customer_override']['email']) ? $this->session->data['customer_override']['email'] : $customer_info['email'];
      $order_data['telephone'] = isset($this->session->data['customer_override']['telephone']) ? $this->session->data['customer_override']['telephone'] : $customer_info['telephone'];

      $order_data['custom_field'] = json_decode($customer_info['custom_field'], true);

      if (isset($this->session->data['customer_override'])) {
        foreach ($this->session->data['customer_override'] as $key => $value) {
          if (stripos($key, 'custom_field') !== false) {
             $id = (int)str_replace('custom_field', '', $key);
             $order_data['custom_field'][$id] = $value;
          }
        }
      }
    } elseif (isset($this->session->data['guest'])) {
      $order_data['customer_id'] = 0;
      $order_data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
      $order_data['firstname'] = $this->session->data['guest']['firstname'];
      $order_data['lastname'] = $this->session->data['guest']['lastname'];
      $order_data['email'] = $this->session->data['guest']['email'];
      $order_data['telephone'] = $this->session->data['guest']['telephone'];
      $order_data['custom_field'] = $this->session->data['guest']['custom_field'];
    }

    // Отримувач (recipient) для Нової Пошти, якщо вказано
    if (isset($this->session->data['shipping_method'][$store_id]['code']) && $this->session->data['shipping_method'][$store_id]['code'] == 'np') {
      if (!empty($this->session->data['shipping_method'][$store_id]['np_customer_name'])) {
        $order_data['firstname'] = $this->session->data['shipping_method'][$store_id]['np_customer_name'];
      }
      if (!empty($this->session->data['shipping_method'][$store_id]['np_customer_lastname'])) {
        $order_data['lastname'] = $this->session->data['shipping_method'][$store_id]['np_customer_lastname'];
      }
      if (!empty($this->session->data['shipping_method'][$store_id]['np_customer_phone'])) {
        $order_data['telephone'] = $this->session->data['shipping_method'][$store_id]['np_customer_phone'];
      }
    }

    $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($store_id);
    $order_data['warehouse'] = $warehouse;

    $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
    $order_data['store_id'] = $this->config->get('config_store_id');
    $order_data['store_name'] = $this->config->get('config_name');

    if ($order_data['store_id']) {
      $order_data['store_url'] = $this->config->get('config_url');
    } else {
      if ($this->request->server['HTTPS']) {
        $order_data['store_url'] = HTTPS_SERVER;
      } else {
        $order_data['store_url'] = HTTP_SERVER;
      }
    }

    $order_data['order_status_id'] = 1;

    //Доставка
    if (isset($this->session->data['shipping_method'][$store_id]['title'])) {
      $order_data['shipping_method'] = $this->session->data['shipping_method'][$store_id]['title'];
    } else {
      $order_data['shipping_method'] = '';
    }

    //код торгової точки
    $order_data['client_address_id'] = 0;
    $order_data['customer_cod_guid'] = '';
    if (isset($this->session->data['client_address_id'])) {

      $this->load->model('account/address');
      $select_adress = $this->model_account_address->getAddress($this->session->data['client_address_id']);
      if ($select_adress) {
        $order_data['client_address_id'] = $select_adress['address_id'];
        $order_data['customer_cod_guid'] = $select_adress['customer_cod_guid'];
      }
    }

    $order_data['shipping_city'] = isset($this->session->data['shipping_method'][$store_id]['city']) ? $this->session->data['shipping_method'][$store_id]['city'] : '';
    $order_data['shipping_city_id'] = isset($this->session->data['shipping_method'][$store_id]['city_ref']) ? $this->session->data['shipping_method'][$store_id]['city_ref'] : '';

    $order_data['shipping_type'] = '';
    $order_data['shipping_code'] = '';
    $order_data['shipping_post'] = '';
    $order_data['shipping_post_id'] = '';
    $order_data['shipping_address'] = '';
    $order_data['shipping_address_id'] = '';
    $order_data['shipping_house'] = '';
    $order_data['shipping_apartment'] = '';
    $order_data['shipping_level'] = '';
    $order_data['address'] = '';
    $order_data['address_id'] = '';

    if (isset($this->session->data['shipping_method'][$store_id]['code'])) {
      switch ($this->session->data['shipping_method'][$store_id]['code']) {
        case "np":
          switch ($this->session->data['shipping_method'][$store_id]['shipping_np_option']) {
            case "post":
              $order_data['shipping_code'] = "np_post";
              $order_data['shipping_type'] = 'На відділення';
              $order_data['shipping_post_id'] = $this->session->data['shipping_method'][$store_id]['post_ref'];
              $order_data['shipping_post'] = $this->session->data['shipping_method'][$store_id]['post'];
              $order_data['address'] = $order_data['shipping_type'] . ". " . $order_data['shipping_city'] . ". " .  $order_data['shipping_post'];
              break;
            case "address":
              $order_data['shipping_code'] = "np_address";
              $order_data['shipping_type'] = 'На адресу';
              $order_data['shipping_house'] = $this->session->data['shipping_method'][$store_id]['address_house'];
              $order_data['shipping_apartment'] = $this->session->data['shipping_method'][$store_id]['address_apartment'];
              $order_data['shipping_level'] = $this->session->data['shipping_method'][$store_id]['shipping_level'];
              $order_data['shipping_address'] = $this->session->data['shipping_method'][$store_id]['address'] . " дім." . $this->session->data['shipping_method'][$store_id]['address_house'] . " кв." . $this->session->data['shipping_method'][$store_id]['address_apartment'];
              $order_data['shipping_address_id'] = $this->session->data['shipping_method'][$store_id]['address_ref'];
              $order_data['address'] = $order_data['shipping_type'] . ". " . $order_data['shipping_city'] . ". " . $order_data['shipping_address'];
              break;
            case "postomat":
              $order_data['shipping_code'] = "np_postomat";
              $order_data['shipping_type'] = 'На поштомат';
              $order_data['shipping_poshtomat_id'] = $this->session->data['shipping_method'][$store_id]['postomat_ref'];
              $order_data['shipping_poshtomat'] = $this->session->data['shipping_method'][$store_id]['postomat'];
              $order_data['address'] = $order_data['shipping_type'] . ". " . $order_data['shipping_city'] . ". " .  $order_data['shipping_poshtomat'];
              break;
          }
          break;
        case "dropshipping":
          $order_data['shipping_code'] = "dropshipping";
          $order_data['shipping_type'] = 'Дропшипінг';
          $order_data['address'] = "За адресою ТТН перевізника";
          break;
        case "pickup":
          $order_data['shipping_code'] = "pickup";
          $order_data['shipping_type'] = 'Самовивіз';
          $order_data['address'] = "Зі складу компанії";
          break;
        case "ourdelivery":
          $order_data['shipping_code'] = "ourdelivery";
          $order_data['shipping_type'] = 'Кур’єр Detta';
          
          $ourdelivery_address = $this->session->data['shipping_method'][$store_id]['address'] ?? '';
          $ourdelivery_house = $this->session->data['shipping_method'][$store_id]['shipping_house'] ?? '';
          $ourdelivery_apartment = $this->session->data['shipping_method'][$store_id]['shipping_apartment'] ?? '';
          
          $full_address = $ourdelivery_address;
          if ($ourdelivery_house) {
            $full_address .= ', буд. ' . $ourdelivery_house;
          }
          if ($ourdelivery_apartment) {
            $full_address .= ', кв. ' . $ourdelivery_apartment;
          }

          $order_data['address'] = $order_data['shipping_type'] . ". " .$order_data['shipping_city'] . ". " . $full_address;
          $order_data['address_id'] = $this->session->data['client_address_id'];
          break;
      }
    }

    // Auto-save address if it doesn't exist
    if ($this->customer->isLogged()) {
      $this->load->model('account/address');
      
      $save_address_data = array();
      $save_type = '';
      $shipping_comment = '';
      
      if (isset($this->session->data['shipping_method'][$store_id]['code']) && $this->session->data['shipping_method'][$store_id]['code'] == 'np') {
        $np_option = $this->session->data['shipping_method'][$store_id]['shipping_np_option'];
        
        if ($np_option == 'post' || $np_option == 'postomat') {
          $shipping_comment = $this->session->data['shipping_method'][$store_id]['comment'] ?? '';
          $save_type = ($np_option == 'post') ? 'np_post' : 'np_poshtomat';
          $save_address_data = array(
            'city_id'           => $this->session->data['shipping_method'][$store_id]['city_ref'],
            'city_name'         => $this->session->data['shipping_method'][$store_id]['city'],
            'post_id'           => ($np_option == 'post') ? $this->session->data['shipping_method'][$store_id]['post_ref'] : $this->session->data['shipping_method'][$store_id]['postomat_ref'],
            'post_name'         => ($np_option == 'post') ? $this->session->data['shipping_method'][$store_id]['post'] : $this->session->data['shipping_method'][$store_id]['postomat'],
            'customer_name'     => !empty($this->session->data['shipping_method'][$store_id]['np_customer_name']) ? $this->session->data['shipping_method'][$store_id]['np_customer_name'] : $order_data['firstname'],
            'customer_lastname' => !empty($this->session->data['shipping_method'][$store_id]['np_customer_lastname']) ? $this->session->data['shipping_method'][$store_id]['np_customer_lastname'] : $order_data['lastname'],
            'customer_phone'    => !empty($this->session->data['shipping_method'][$store_id]['np_customer_phone']) ? $this->session->data['shipping_method'][$store_id]['np_customer_phone'] : $order_data['telephone'],
            'comment'           => $shipping_comment,
            'guid'              => ''
          );
        } elseif ($np_option == 'address') {
          $save_type = 'np_dveri';
          $shipping_comment = $this->session->data['shipping_method'][$store_id]['comment'] ?? '';
          $save_address_data = array(
            'city_id'           => $this->session->data['shipping_method'][$store_id]['city_ref'],
            'city_name'         => $this->session->data['shipping_method'][$store_id]['city'],
            'street_id'         => $this->session->data['shipping_method'][$store_id]['address_ref'],
            'street_name'       => $this->session->data['shipping_method'][$store_id]['address'],
            'house'             => $this->session->data['shipping_method'][$store_id]['address_house'],
            'apartment'         => $this->session->data['shipping_method'][$store_id]['address_apartment'],
            'level'             => isset($this->session->data['shipping_method'][$store_id]['shipping_level']) ? $this->session->data['shipping_method'][$store_id]['shipping_level'] : '',
            'customer_name'     => !empty($this->session->data['shipping_method'][$store_id]['np_customer_name']) ? $this->session->data['shipping_method'][$store_id]['np_customer_name'] : $order_data['firstname'],
            'customer_lastname' => !empty($this->session->data['shipping_method'][$store_id]['np_customer_lastname']) ? $this->session->data['shipping_method'][$store_id]['np_customer_lastname'] : $order_data['lastname'],
            'customer_phone'    => !empty($this->session->data['shipping_method'][$store_id]['np_customer_phone']) ? $this->session->data['shipping_method'][$store_id]['np_customer_phone'] : $order_data['telephone'],
            'comment'           => $shipping_comment,
            'guid'              => ''
          );
        }
        
        if ($save_type && $save_address_data) {
          $existing_address_id = $this->model_account_address->getAddressByNP(
            $this->customer->getId(), 
            $order_data['customer_cod_guid'], 
            $save_type, 
            $save_address_data
          );
          
          if (!$existing_address_id) {
            $save_firstname = (!empty($order_data['customer_cod_guid']) && isset($select_adress['firstname'])) ? $select_adress['firstname'] : $order_data['firstname'] . ' ' . $order_data['lastname'];

            $this->model_account_address->addAddressClient(
              $this->customer->getId(), 
              $order_data['customer_cod_guid'], 
              $save_type, 
              $save_firstname, 
              0, 
              0, 
              0, 
              0, 
              0, 
              $save_address_data
            );
          } else {
             // Update comment if exists
             $this->db->query("UPDATE " . DB_PREFIX . "address SET comment = '" . $this->db->escape($save_address_data['comment']) . "' WHERE address_id = '" . (int)$existing_address_id . "'");
          }
        }
      } elseif (isset($this->session->data['shipping_method'][$store_id]['code']) && $this->session->data['shipping_method'][$store_id]['code'] == 'ourdelivery') {
        $save_type = 'car';
        $shipping_comment = $this->session->data['shipping_method'][$store_id]['comment'] ?? '';
        $save_address_data = array(
          'city'              => $this->session->data['shipping_method'][$store_id]['city'] ?? '',
          'name'              => $this->session->data['shipping_method'][$store_id]['address'] ?? '',
          'house'             => $this->session->data['shipping_method'][$store_id]['shipping_house'] ?? '',
          'apartment'         => $this->session->data['shipping_method'][$store_id]['shipping_apartment'] ?? '',
          'comment'           => $shipping_comment,
          'guid'              => ''
        );
        
        // Перевіряємо чи така адреса вже є
        $sql = "SELECT address_id FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "' AND type = 'car' AND address_1 = '" . $this->db->escape($save_address_data['name']) . "' AND np_house = '" . $this->db->escape($save_address_data['house']) . "' AND np_apartment = '" . $this->db->escape($save_address_data['apartment']) . "'";
        $query = $this->db->query($sql);
        
        if (!$query->num_rows) {
           $save_firstname = (!empty($order_data['customer_cod_guid']) && isset($select_adress['firstname'])) ? $select_adress['firstname'] : $order_data['firstname'] . ' ' . $order_data['lastname'];

            $this->model_account_address->addAddressClient(
              $this->customer->getId(), 
              $order_data['customer_cod_guid'], 
              $save_type, 
              $save_firstname, 
              0, 
              0, 
              0, 
              0, 
              0, 
              $save_address_data
            );
        } else {
           // Оновлюємо коментар, якщо адреса вже є?
           $this->db->query("UPDATE " . DB_PREFIX . "address SET comment = '" . $this->db->escape($save_address_data['comment']) . "' WHERE address_id = '" . (int)$query->row['address_id'] . "'");
        }
      }
    }

    $order_data['shipping_comment'] = $shipping_comment;

    //Оплата
    $order_data['payment_method'] = '';
    $order_data['payment_code'] = '';
    if (isset($this->session->data['payment_method'][$store_id])) {
      $order_data['payment_code'] = $this->session->data['payment_method'][$store_id]['code'];
      $order_data['payment_method'] = isset($this->session->data['payment_method'][$store_id]['title'][$langId]['title']) ? $this->session->data['payment_method'][$store_id]['title'][$langId]['title'] : '';
    }

    //Товари
    $order_data['products'] = array();
    $order_data['total'] = 0;

    foreach ($order as $product) {
      $option_data = array();
      $opts = array();
      $opts_value = array();

      if (isset($product['option'])) {
        foreach ($product['option'] as $option) {
          $option_data[] = array(
            'name' => $option['name'],
            'value' => $option['value'],
            'type' => $option['type'],
            'product_option_id' => $option['product_option_id'],
            'product_option_value_id' => $option['product_option_value_id'],
            'option_id' => $option['option_id'],
            'option_value_id' => $option['option_value_id']
          );
          $opts[(int)$option['option_id']] = (int)$option['option_value_id'];
          $opts_value += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];
        }
      }

      $product_data = array(
        'item' => $product,
        'sort_order' => "ASC",
        'detal' => true,
        'mass_options' => $opts_value
      );
      $options = $this->load->controller('product/options', $product_data);

      $price = $options['price'];
      if ($options['special'] > 0) {
        $price = $options['special'];
      }

      $total = $price * $product['quantity'];

      $order_data['products'][] = array(
        'product_id' => $product['product_id'],
        'name' => $product['name'],
        'model' => $options['ean'],
        'option' => $option_data,
        'quantity' => $product['quantity'],
        'price' => $price,
        'total' => $total,
        'options' => $opts,
        'options_value' => $opts_value,
        'tax' => 0,
        'reward' => 0
      );

      $order_data['total'] += $total;
    }

    $order_data['total'] = $this->currency->format($order_data['total'], $this->session->data['currency']);

    //Маркетинг
    $order_data['affiliate_id'] = 0;
    $order_data['commission'] = 0;
    $order_data['marketing_id'] = 0;
    $order_data['tracking'] = '';

    $order_data['language_id'] = $this->config->get('config_language_id');
    $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
    $order_data['currency_code'] = $this->session->data['currency'];
    $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
    $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

    if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
      $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
      $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
    } else {
      $order_data['forwarded_ip'] = '';
    }

    if (isset($this->request->server['HTTP_USER_AGENT'])) {
      $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
    } else {
      $order_data['user_agent'] = '';
    }

    if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
      $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
    } else {
      $order_data['accept_language'] = '';
    }

    $order_data['comment'] = $this->session->data['comment'] ?? '';

    $order_data['shipping_ttn_file'] = '';

    if (isset($this->session->data['shipping_method'][$store_id]['code']) && $this->session->data['shipping_method'][$store_id]['code'] == 'dropshipping') {
      $upload_ids = array();
      $filenames = array();

      if (!empty($this->session->data['dropshipping_tth_files'][$store_id])) {
        $this->load->model('tool/upload');
        foreach ($this->session->data['dropshipping_tth_files'][$store_id] as $file) {
          $upload_info = $this->model_tool_upload->getUploadByCode($file['code']);
          if ($upload_info) {
            $upload_ids[] = $upload_info['upload_id'];
            $filenames[] = $file['filename'];
          }
        }
      } elseif (!empty($this->session->data['dropshipping_tth_files'][0])) {
        // Fallback if stored under index 0
        $this->load->model('tool/upload');
        foreach ($this->session->data['dropshipping_tth_files'][0] as $file) {
          $upload_info = $this->model_tool_upload->getUploadByCode($file['code']);
          if ($upload_info) {
            $upload_ids[] = $upload_info['upload_id'];
            $filenames[] = $file['filename'];
          }
        }
      } elseif (!empty($this->session->data['dropshipping_tth'])) {
        // Fallback for single file
        $this->load->model('tool/upload');
        $upload_info = $this->model_tool_upload->getUploadByCode($this->session->data['dropshipping_tth']);
        if ($upload_info) {
          $upload_ids[] = $upload_info['upload_id'];
          $filenames[] = $this->session->data['dropshipping_tth_filename'];
        }
      }

      if ($upload_ids) {
        $order_data['shipping_ttn_file'] = implode(',', $upload_ids);
      }
    }

    //Створюємо замовлення
    $order_id_db = $this->model_checkout_order->addOrder($order_data);
    $this->session->data['order_id'] = $order_id_db;
    $order_data['order_id_db'] = $order_id_db;

    return $order_data;
  }
}
