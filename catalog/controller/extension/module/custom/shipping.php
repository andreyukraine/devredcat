<?php

class ControllerExtensionModuleCustomShipping extends Controller
{

  public function index($setting)
   {
     // Clear fast order session if present
     if (isset($this->session->data['fastorder'])) {
       $this->session->data['fastorder'] = null;
     }

     // Check if shipping block should be displayed
     if (isset($setting['status']) && (bool)$setting['status'] === true && $this->cart->hasShipping()) {

       $this->load->language('extension/module/custom/shipping');
       $this->load->model('setting/setting');
       $this->load->model('extension/module/ocwarehouses');

       $data['setting'] = $setting;
       $data['np_key'] = $this->config->get('module_custom_np_key');

       // Labels and translations
       $data['heading_shipping'] = $this->language->get('heading_shipping');
       $data['text_select'] = $this->language->get('text_select');
       $data['text_none'] = $this->language->get('text_none');
       $data['text_loading'] = $this->language->get('text_loading');
       $data['entry_city'] = $this->language->get('entry_city');
       $data['input_city'] = $this->language->get('input_city');

       $this->load->language('extension/shipping/dropshipping');
       $data['text_upload_tth'] = $this->language->get('text_upload_tth');
       $data['button_upload'] = $this->language->get('button_upload');
       $data['text_upload_success'] = $this->language->get('text_upload_success');
       $data['dropshipping_tth'] = $this->session->data['dropshipping_tth'] ?? '';
       $data['dropshipping_tth_filename'] = $this->session->data['dropshipping_tth_filename'] ?? '';

       // Determine order_id (handling multiple orders/warehouses)
       $order_id = 0;
       if (isset($setting['order_id'])) {
         $order_id = $setting['order_id'];
       }

       if ($order_id == 0){
         if (!empty($this->config->get('config_warehouse_id'))) {
           $order_id = (int)$this->config->get('config_warehouse_id');
         }
       }

       $data['order_id'] = $order_id;

       // Initialize Nova Poshta city fields from session or defaults
       $np_fields = [
         'city_ref' => ['city_ref', ''],
         'city_delivery_ref' => ['city_delivery_ref', ''],
         'city' => ['city', ''],
       ];

       foreach ($np_fields as $dataKey => [$sessionKey, $default]) {
         if (isset($this->session->data['shipping_method'][$order_id][$sessionKey])) {
           $data[$dataKey] = $this->session->data['shipping_method'][$order_id][$sessionKey];
         } elseif (isset($this->session->data['custom_city'][$sessionKey])) {
           $data[$dataKey] = $this->session->data['custom_city'][$sessionKey];
         } else {
           $data[$dataKey] = $default;
         }
       }

       $data['has_address'] = isset($this->session->data['client_address_id']);

       // Get available shipping methods and store in session
       $data['shipping_methods'] = $this->getMethods($order_id);
       $this->session->data['shipping_methods'] = $data['shipping_methods'];

       $data['cart_total'] = $this->getRealSubtotal();

       // Check if 'ourdelivery' is available
       $data['has_ourdelivery'] = false;
       foreach ($data['shipping_methods'] as $method) {
         if ($method['code'] == 'ourdelivery') {
           $data['has_ourdelivery'] = true;
           break;
         }
       }

       if (empty($this->session->data['shipping_methods'])) {
         $data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
       } else {
         $data['error_warning'] = '';
       }

       // Manage selected shipping method code
       if (isset($this->session->data['shipping_method'][$order_id]['code'])) {
         $data['code'] = $this->session->data['shipping_method'][$order_id]['code'];

         // Ensure currently selected method is still available and has no errors
         $method_exists = false;
         foreach ($data['shipping_methods'] as $method) {
           if ($method['code'] == $data['code'] && empty($method['error'])) {
             $method_exists = true;
             break;
           }
         }

          // If not available or has errors, fallback to first valid method
          if (!$method_exists) {
            $firstValidMethod = null;
            foreach ($data['shipping_methods'] as $method) {
              if (empty($method['error'])) {
                $firstValidMethod = $method;
                break;
              }
            }

            if ($firstValidMethod) {
              $data['code'] = $firstValidMethod['code'];
              $this->session->data['shipping_method'][$order_id] = $firstValidMethod;
            } else {
              // If no methods without errors, clear selection
              unset($this->session->data['shipping_method'][$order_id]);
              $data['code'] = '';
            }
          }
        } else {
          // If not available or has errors, fallback to first valid method
          $firstValidMethod = null;
          foreach ($data['shipping_methods'] as $method) {
            if (empty($method['error'])) {
              $firstValidMethod = $method;
              break;
            }
          }

          if ($firstValidMethod) {
            $data['code'] = $firstValidMethod['code'];
            $this->session->data['shipping_method'][$order_id] = $firstValidMethod;
          } else {
            // Якщо жоден метод не валідний, все одно зберігаємо перший, щоб він був вибраний у UI
            $firstMethod = reset($data['shipping_methods']);
            if ($firstMethod) {
              $data['code'] = $firstMethod['code'];
              $this->session->data['shipping_method'][$order_id] = $firstMethod;
            } else {
              unset($this->session->data['shipping_method'][$order_id]);
              $data['code'] = '';
            }
          }
        }

        // Auto-select if only one method exists and it is valid
        if (count($data['shipping_methods']) == 1 && !isset($this->session->data['shipping_method'])){
          $firstMethod = reset($data['shipping_methods']);
          if (empty($firstMethod['error'])) {
            $data['code'] = $firstMethod['code'];
            $this->session->data['shipping_method'][$order_id] = $firstMethod;
          }
        }

        // Initialize delivery options container
        foreach ($this->session->data['shipping_methods'] as $method) {
          $data['delivery_options'][$method['code']][$order_id] = '';
        }

        // Render specific options for selected method
        $html = '';
        switch ($data['code']) {
          case "dropshipping":
            $this->load->language('extension/shipping/dropshipping');
            $data['text_upload_tth'] = $this->language->get('text_upload_tth');
            $data['button_upload'] = $this->language->get('button_upload');
            $data['text_upload_success'] = $this->language->get('text_upload_success');

            $data['dropshipping_tth'] = $this->session->data['dropshipping_tth'] ?? '';
            $data['dropshipping_tth_filename'] = $this->session->data['dropshipping_tth_filename'] ?? '';
            $data['dropshipping_tth_files'] = $this->session->data['dropshipping_tth_files'][$order_id] ?? ($this->session->data['dropshipping_tth_files'][0] ?? array());

            $html .= $this->load->view('checkout/shipping_dropshipping', $data);
            break;

          case "np":
            // Nova Poshta specific fields
            $fields = [
              'code' => ['code', ''],
              'type' => ['shipping_np_option', 'post'],
              'post_ref' => ['post_ref', ''],
              'post' => ['post', ''],
              'postomat_ref' => ['postomat_ref', ''],
              'postomat' => ['postomat', ''],
              'address_ref' => ['address_ref', ''],
              'address' => ['address', ''],
              'address_house' => ['address_house', ''],
              'address_apartment' => ['address_apartment', ''],
              'np_customer_name' => ['np_customer_name', ''],
              'np_customer_lastname' => ['np_customer_lastname', ''],
              'np_customer_phone' => ['np_customer_phone', ''],
              'comment' => ['comment', '']
            ];

            foreach ($fields as $dataKey => [$sessionKey, $default]) {
              $data[$dataKey] = $this->session->data['shipping_method'][$order_id][$sessionKey] ?? $default;
            }

            // Customer addresses for selection
            $data['has_saved_post'] = false;
            $data['has_saved_postomat'] = false;
            $data['has_saved_address'] = false;

            if ($this->customer->isLogged()) {
              $this->load->model('account/address');
              $addresses = $this->model_account_address->getAddresses();
              
              $customer_cod_guid = '';
              if (isset($this->session->data['client_address_id'])) {
                $trading_point = $this->model_account_address->getAddress($this->session->data['client_address_id']);
                if ($trading_point) {
                  $customer_cod_guid = $trading_point['customer_cod_guid'];
                }
              }

              $current_city_ref = $data['city_ref'];
              $current_city_name = $data['city'];

              foreach ($addresses as $address) {
                // Фільтруємо за торговою точкою
                if (isset($address['customer_cod_guid']) && $address['customer_cod_guid'] !== $customer_cod_guid) {
                  continue;
                }

                // Фільтруємо за містом
                $city_match = false;
                if ($current_city_ref && isset($address['np_city_id']) && $address['np_city_id'] === $current_city_ref) {
                  $city_match = true;
                } elseif ($current_city_name && !empty($address['np_city_name'])) {
                  $address_city = mb_strtolower(trim($address['np_city_name']), 'UTF-8');
                  $search_city = mb_strtolower(trim($current_city_name), 'UTF-8');
                  
                  if ($address_city === $search_city || mb_stripos($search_city, $address_city) !== false || mb_stripos($address_city, $search_city) !== false) {
                    $city_match = true;
                  }
                }

                if (!$city_match && ($current_city_ref || $current_city_name)) {
                  continue;
                }

                if ($address['type'] == 'np_post') $data['has_saved_post'] = true;
                if ($address['type'] == 'np_poshtomat') $data['has_saved_postomat'] = true;
                if ($address['type'] == 'np_dveri') $data['has_saved_address'] = true;
              }
            }

            // Додаємо дані про ціни для кожного типу доставки Нової Пошти для поточної групи клієнта
            $customer_group_id = (int)$this->customer->getGroupId();
            $module_custom = $this->model_setting_setting->getSetting('module_custom');
            foreach ($module_custom['module_custom_shipping']['methods'] as $delivery) {
              if ($delivery['code'] == 'np') {
                $data['np_prices'] = [
                  'post' => [
                    'free' => $delivery['free'][$customer_group_id] ?? 0,
                    'cost' => $delivery['cost'][$customer_group_id] ?? 0,
                    'minimum' => $delivery['minimum'][$customer_group_id] ?? 0,
                  ],
                  'postomat' => [
                    'free' => $delivery['free_postomat'][$customer_group_id] ?? ($delivery['free'][$customer_group_id] ?? 0),
                    'cost' => $delivery['cost_postomat'][$customer_group_id] ?? ($delivery['cost'][$customer_group_id] ?? 0),
                    'minimum' => $delivery['minimum_postomat'][$customer_group_id] ?? ($delivery['minimum'][$customer_group_id] ?? 0),
                  ],
                  'address' => [
                    'free' => $delivery['free_address'][$customer_group_id] ?? ($delivery['free'][$customer_group_id] ?? 0),
                    'cost' => $delivery['cost_address'][$customer_group_id] ?? ($delivery['cost'][$customer_group_id] ?? 0),
                    'minimum' => $delivery['minimum_address'][$customer_group_id] ?? ($delivery['minimum'][$customer_group_id] ?? 0),
                  ]
                ];
                break;
              }
            }
            $data['cart_total'] = $this->getRealSubtotal();

            $occallback_data = $this->config->get('occallback_data');
            $data['mask'] = (isset($occallback_data['mask']) && !empty($occallback_data['mask'])) ? $occallback_data['mask'] : '';

            $html .= $this->load->view('checkout/shipping_np', $data);
            break;

          case "pickup":
            // Pickup point logic
            if (isset($this->session->data['shipping_method'][$order_id]['pickup-id'])) {
              $warehouse_id = $this->session->data['shipping_method'][$order_id]['pickup-id'];
              $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);

              $html .= '<div class="selected-pickup">';
              if ($warehouse != null) {
                $html .= '<p>Вибраний склад: ' . $warehouse['name'] . '</p>';
              }
              $html .= '<input id="selected-pickup-id" name="pickup-id" value="' . $warehouse_id . '" type="hidden">';
              $html .= '<span class="edit_pickup" onclick="custom_block.check_pickup_warehouse()">Змінити</span></div>';
            }
            break;

          case 'ourdelivery':
            // Internal delivery (Our Delivery) logic
            $html .= $this->getOurDeliveryHtml($order_id);
            break;
        }

        $data['delivery_options'][$data['code']][$order_id] = $html;

        return $this->load->view('extension/module/custom/shipping', $data);

      } else {
        // Block is disabled
        $this->session->data['shipping_method'] = array();
        $this->session->data['shipping_address'] = $this->full(array());
        $this->session->data['payment_address'] = $this->full(array());
        return false;
      }
   }

  public function pickup()
  {
    $json = array();
    $this->load->language('extension/module/custom/shipping');

    $data['title_pickup_warehouse'] = $this->language->get('title_pickup_warehouse');
    $data['title_working_hours_warehouse'] = $this->language->get('title_working_hours_warehouse');

    $order_id = 0;
    if (isset($this->request->get['order_id'])) {
      $order_id = $this->request->get['order_id'];
      unset($this->session->data['shipping_method'][$order_id]);
    }

    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocwarehouses');

    $data['warehouses'] = array();

    $stores = array();
    $cart_prod = $this->cart->getProducts();

    foreach ($cart_prod as $prod) {
      if (empty($prod['option'])) {
        $s = $this->model_catalog_product->getProductOptStockWarehouses($prod['product_id'], []);
      } else {
        foreach ($prod['option'] as $opt) {
          $s = $this->model_catalog_product->getProductOptStockWarehouses($prod['product_id'], [(int)$opt['product_option_id'] => (int)$opt['product_option_value_id']]);
        }
      }
      foreach ($s as $store => $qty) {
        // Проверяем, существует ли ключ $store, если нет, инициализируем его значением 0
        if (!isset($stores[$store])) {
          $stores[$store] = 0;
        }
        // Теперь безопасно добавляем количество
        $stores[$store] += (int)$qty;
      }
    }

    $keys = array_keys($stores);

    $warehouses = $this->model_extension_module_ocwarehouses->getWarehouseListInIds($keys);
    foreach ($warehouses as &$warehouse) {

      if (!empty($warehouse['image'] && file_exists(DIR_IMAGE . $warehouse['image']))) {
        $warehouse['image'] = "/image/" . $warehouse['image'];
      } else {
        $warehouse['image'] = "/image/" . "no_image.png";
      }

      $warehouse['working_hours'] = html_entity_decode($warehouse['working_hours'], ENT_QUOTES, 'UTF-8');
    }

    $data['warehouses'] = $warehouses;

    $json['delivery_options'] = $this->load->view('checkout/shipping_pickup', $data);
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  private function getRealWeight($order_id)
  {
    $weight = 0;
    foreach ($this->cart->getProducts() as $product) {
      if ((int)$product['warehouse_id'] === (int)$order_id && $product['shipping']) {
        $weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id'));
      }
    }
    return $weight;
  }

  private function getRealSubtotal()
  {
    $real_subtotal = 0;
    foreach ($this->cart->getProducts() as $product) {
      $real_subtotal += (float)$product['total'];
    }

    return $real_subtotal;
  }

  /**
   * Update shipping data from POST request
   */
  public function update()
  {
    $json = array();
    $data = array();

    $this->load->model('setting/setting');
    $this->load->language('extension/module/custom/shipping');

    $data['text_loading'] = $this->language->get('text_loading');

    // Extract order_id
    $order_id = 0;
    if (isset($this->request->post['order_id'])) {
      $order_id = (int)$this->request->post['order_id'];
    }

    if ($order_id == 0){
      if (!empty($this->config->get('config_warehouse_id'))) {
        $order_id = (int)$this->config->get('config_warehouse_id');
      }
    }

    $data['order_id'] = $order_id;

    // Determine shipping method code
    $shipping_code = "";
    if (isset($this->request->post['shipping_code']) && $this->request->post['shipping_code'] !== 'undefined') {
      $shipping_code = $this->request->post['shipping_code'];
    } elseif (isset($this->request->post['shipping_method'][$order_id]['code']) && $this->request->post['shipping_method'][$order_id]['code'] !== 'undefined') {
      $shipping_code = $this->request->post['shipping_method'][$order_id]['code'];
    }

    if (empty($shipping_code) || $shipping_code === 'undefined') {
      $shipping_code = $this->session->data['shipping_method'][$order_id]['code'] ?? 'np';
    }

    $data['code'] = $shipping_code;

    // Save 'ourdelivery' specific selections
    if (isset($this->request->post['shipping_method'][$order_id]['car'])) {
      $old_car = $this->session->data['shipping_method'][$order_id]['car'] ?? '';
      $new_car = $this->request->post['shipping_method'][$order_id]['car'];
      
      if ($old_car !== $new_car) {
        $this->session->data['shipping_method'][$order_id]['car'] = $new_car;
        // If we switched car/address, we should clear the manual address field 
        // to prevent old address from persisting when switching to 'other'
        if ($new_car === 'other' || !empty($new_car)) {
          $this->session->data['shipping_method'][$order_id]['address'] = '';
        }
      }
    }

    // Handle manual address input for 'ourdelivery'
    if (isset($this->request->post['address'])) {
      $this->session->data['shipping_method'][$order_id]['address'] = trim($this->request->post['address']);
      if ($shipping_code === 'ourdelivery') {
        $this->session->data['shipping_method'][$order_id]['car'] = 'other';
      }
    }

    if (isset($this->request->post['shipping_method'][$order_id]['address_input']) && isset($this->session->data['shipping_method'][$order_id]['car']) && $this->session->data['shipping_method'][$order_id]['car'] === 'other') {
      $this->session->data['shipping_method'][$order_id]['address'] = trim($this->request->post['shipping_method'][$order_id]['address_input']);
    }

    // Save house and apartment for 'ourdelivery'
    if (isset($this->request->post['shipping_method'][$order_id]['shipping_house'])) {
      $this->session->data['shipping_method'][$order_id]['shipping_house'] = trim($this->request->post['shipping_method'][$order_id]['shipping_house']);
    }
    if (isset($this->request->post['shipping_method'][$order_id]['shipping_apartment'])) {
      $this->session->data['shipping_method'][$order_id]['shipping_apartment'] = trim($this->request->post['shipping_method'][$order_id]['shipping_apartment']);
    }

    // Persistent city fields for Nova Poshta
    $np_city_fields = [
      'city_ref' => '',
      'city_delivery_ref' => '',
      'city' => ''
    ];

    // Save city data to persistent session before getting methods
    foreach ($np_city_fields as $field => $default) {
      if (isset($this->request->post['shipping_method'][$order_id][$field])) {
        $this->session->data['shipping_method'][$order_id][$field] = $this->request->post['shipping_method'][$order_id][$field];
        $this->session->data['custom_city'][$field] = $this->request->post['shipping_method'][$order_id][$field];
      } elseif (!isset($this->session->data['shipping_method'][$order_id][$field])) {
        if (isset($this->session->data['custom_city'][$field])) {
          $this->session->data['shipping_method'][$order_id][$field] = $this->session->data['custom_city'][$field];
        } else {
          $this->session->data['shipping_method'][$order_id][$field] = $default;
        }
      }
      $data[$field] = $this->session->data['shipping_method'][$order_id][$field];
    }

    // Оновлюємо тип доставки NP перед викликом getMethods, щоб правильно розрахувати вартість
    if ($shipping_code === 'np') {
      if (!isset($this->session->data['shipping_method'][$order_id])) {
        $this->session->data['shipping_method'][$order_id] = array();
      }
      
      if (isset($this->request->post['shipping_method'][$order_id]['shipping_np_option'])) {
        $this->session->data['shipping_method'][$order_id]['shipping_np_option'] = $this->request->post['shipping_method'][$order_id]['shipping_np_option'];
      } elseif (!isset($this->session->data['shipping_method'][$order_id]['shipping_np_option'])) {
        $this->session->data['shipping_method'][$order_id]['shipping_np_option'] = 'post';
      }
    }

    // Get current available methods based on updated city info
    $methods = $this->getMethods($order_id);

    // Merge session with current selected method data
    if (isset($methods[$shipping_code])) {
      
      $this->session->data['shipping_method'][$order_id] = array_merge(
        $this->session->data['shipping_method'][$order_id] ?? [],
        $methods[$shipping_code]
      );
      // Ensure code is updated
      $this->session->data['shipping_method'][$order_id]['code'] = $shipping_code;

    }

    // Clear cached methods for future requests
    unset($this->session->data['shipping_methods']);

    $shipping_method = $this->session->data['shipping_method'][$order_id];

    if (isset($this->session->data['shipping_method']['desc'])) {
      $data['desc'] = html_entity_decode($shipping_method['desc']);
    }

    // Process specific method UI updates
    $html = '';
    switch ($shipping_code) {
      case "dropshipping":
        $this->load->language('extension/shipping/dropshipping');
        $data['text_upload_tth'] = $this->language->get('text_upload_tth');
        $data['button_upload'] = $this->language->get('button_upload');
        $data['text_upload_success'] = $this->language->get('text_upload_success');
        $data['text_loading'] = $this->language->get('text_loading');

        $data['dropshipping_tth'] = $this->session->data['dropshipping_tth'] ?? '';
        $data['dropshipping_tth_filename'] = $this->session->data['dropshipping_tth_filename'] ?? '';
        $data['dropshipping_tth_files'] = $this->session->data['dropshipping_tth_files'][$order_id] ?? ($this->session->data['dropshipping_tth_files'][0] ?? array());
        $data['order_id'] = $order_id;

        $html .= $this->load->view('checkout/shipping_dropshipping', $data);
        break;

      case "np":
        $this->session->data['shipping_method'][$order_id]['address'] = null;

        $fields = [
          'code' => 'np',
          'shipping_np_option' => 'post',
          'post_ref' => '',
          'post' => '',
          'postomat_ref' => '',
          'postomat' => '',
          'address_ref' => '',
          'address' => '',
          'address_house' => '',
          'address_apartment' => '',
          'np_customer_name' => '',
          'np_customer_lastname' => '',
          'np_customer_phone' => '',
          'comment' => ''
        ];

        foreach ($fields as $field => $default) {
          $val = $this->request->post['shipping_method'][$order_id][$field] ?? $default;
          if (is_string($val)) $val = trim($val);
          $this->session->data['shipping_method'][$order_id][$field] = $val;
          $data[$field] = $this->session->data['shipping_method'][$order_id][$field];
        }

        // Додаємо дані про ціни для кожного типу доставки Нової Пошти для поточної групи клієнта
        $customer_group_id = (int)$this->customer->getGroupId();
        $module_custom = $this->model_setting_setting->getSetting('module_custom');
        foreach ($module_custom['module_custom_shipping']['methods'] as $delivery) {
          if ($delivery['code'] == 'np') {
            $data['np_prices'] = [
              'post' => [
                'free' => $delivery['free'][$customer_group_id] ?? 0,
                'cost' => $delivery['cost'][$customer_group_id] ?? 0,
              ],
              'postomat' => [
                'free' => $delivery['free_postomat'][$customer_group_id] ?? ($delivery['free'][$customer_group_id] ?? 0),
                'cost' => $delivery['cost_postomat'][$customer_group_id] ?? ($delivery['cost'][$customer_group_id] ?? 0),
              ],
              'address' => [
                'free' => $delivery['free_address'][$customer_group_id] ?? ($delivery['free'][$customer_group_id] ?? 0),
                'cost' => $delivery['cost_address'][$customer_group_id] ?? ($delivery['cost'][$customer_group_id] ?? 0),
              ]
            ];
            break;
          }
        }
        $data['cart_total'] = $this->getRealSubtotal();

        $occallback_data = $this->config->get('occallback_data');
        $data['mask'] = (isset($occallback_data['mask']) && !empty($occallback_data['mask'])) ? $occallback_data['mask'] : '';

        // Customer addresses for selection
        $data['has_saved_post'] = false;
        $data['has_saved_postomat'] = false;
        $data['has_saved_address'] = false;

        if ($this->customer->isLogged()) {
          $this->load->model('account/address');
          $addresses = $this->model_account_address->getAddresses();
          
          $customer_cod_guid = '';
          if (isset($this->session->data['client_address_id'])) {
            $trading_point = $this->model_account_address->getAddress($this->session->data['client_address_id']);
            if ($trading_point) {
              $customer_cod_guid = $trading_point['customer_cod_guid'];
            }
          }

          $current_city_ref = $this->session->data['shipping_method'][$order_id]['city_ref'] ?? '';
          $current_city_name = $this->session->data['shipping_method'][$order_id]['city'] ?? '';

          foreach ($addresses as $address) {
            // Фільтруємо за торговою точкою
            if ($customer_cod_guid && isset($address['customer_cod_guid']) && $address['customer_cod_guid'] !== $customer_cod_guid) {
              continue;
            }

            // Фільтруємо за містом
            $city_match = false;
            if ($current_city_ref && isset($address['np_city_id']) && $address['np_city_id'] === $current_city_ref) {
              $city_match = true;
            } elseif ($current_city_name && !empty($address['np_city_name'])) {
              $address_city = mb_strtolower(trim($address['np_city_name']), 'UTF-8');
              $search_city = mb_strtolower(trim($current_city_name), 'UTF-8');
              
              if ($address_city === $search_city || mb_stripos($search_city, $address_city) !== false || mb_stripos($address_city, $search_city) !== false) {
                $city_match = true;
              }
            }

            if (!$city_match && ($current_city_ref || $current_city_name)) {
              continue;
            }

            if ($address['type'] == 'np_post') $data['has_saved_post'] = true;
            if ($address['type'] == 'np_poshtomat') $data['has_saved_postomat'] = true;
            if ($address['type'] == 'np_dveri') $data['has_saved_address'] = true;
          }
        }

        $data['type'] = $data['shipping_np_option'];

        // Weight validation for NP
        $total_weight = $this->getRealWeight($order_id);
        $option = $data['shipping_np_option'];
        $desc = '';
        if ($option == 'post') {
          $desc = $data['post'] ?? '';
        } elseif ($option == 'postomat') {
          $desc = $data['postomat'] ?? '';
        }
        
        if ($desc && preg_match('/до\s+(\d+)\s+кг/ui', $desc, $matches)) {
          $weight_limit = (int)$matches[1];
          if ($total_weight > $weight_limit) {
            $error_msg = sprintf("Вага замовлення (%s кг) перевищує обмеження цього %s (%s кг)", number_format($total_weight, 2), ($option == 'post' ? 'відділення' : 'поштомату'), $weight_limit);
            $json['error'][$order_id][$option] = $error_msg;
          }
        }

        $html .= $this->load->view('checkout/shipping_np', $data);
        break;

      case "pickup":
        $this->session->data['shipping_method'][$order_id]['address'] = null;
        if ($order_id <= 0) {
          if (isset($this->session->data['shipping_method'][$order_id]['pickup-id'])) {
            $this->load->model('extension/module/ocwarehouses');
            $warehouse_id = $this->request->post['pickup-id'];
            $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);

            $html .= '<div class="selected-pickup">';
            $html .= '<p>Вибраний склад: ' . $warehouse['name'] . '</p>';
            $html .= '<input id="selected-pickup-id" name="pickup-id" value="' . $warehouse_id . '" type="hidden">';
            $html .= '<span class="edit_pickup" onclick="custom_block.check_pickup_warehouse()">Змінити</span></div>';
          }
        }
        break;

      case 'ourdelivery':
        $this->request->post['shipping_code'] = $shipping_code;
        $html .= $this->getOurDeliveryHtml($order_id);
        break;
    }
    $json['delivery_options'][$shipping_code][$order_id] = $html;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function save()
  {

    $json = array();

    $this->load->language('extension/module/custom/shipping');
    $this->load->model('setting/setting');
    $this->load->model('account/custom_field');

    // Update session from POST data before validation
    if (isset($this->request->post['shipping_method'])) {
      foreach ($this->request->post['shipping_method'] as $order_id => $posted_data) {
        if (!isset($this->session->data['shipping_method'][$order_id])) {
          $this->session->data['shipping_method'][$order_id] = array();
        }

        // Check if code changed and refresh method info if needed
        if (isset($posted_data['code'])) {
          if (!isset($this->session->data['shipping_method'][$order_id]['code']) || $this->session->data['shipping_method'][$order_id]['code'] !== $posted_data['code']) {
            $methods = $this->getMethods($order_id);
            if (isset($methods[$posted_data['code']])) {
              $this->session->data['shipping_method'][$order_id] = array_merge($this->session->data['shipping_method'][$order_id], $methods[$posted_data['code']]);
            }
          }
        }

        // Merge all posted data into session
        if (isset($posted_data['car'])) {
          $old_car = $this->session->data['shipping_method'][$order_id]['car'] ?? '';
          $new_car = $posted_data['car'];
          if ($old_car !== $new_car) {
            $this->session->data['shipping_method'][$order_id]['car'] = $new_car;
            if ($new_car === 'other' || !empty($new_car)) {
              $this->session->data['shipping_method'][$order_id]['address'] = '';
            }
          }
        }
        $this->session->data['shipping_method'][$order_id] = array_merge($this->session->data['shipping_method'][$order_id], $posted_data);

        // Persistent city fields sync
        foreach (['city_ref', 'city_delivery_ref', 'city'] as $field) {
          if (isset($posted_data[$field])) {
            $this->session->data['custom_city'][$field] = $posted_data[$field];
          }
        }

        // Special handling for ourdelivery manual address input
        if (isset($posted_data['car']) && $posted_data['car'] === 'other' && isset($posted_data['address_input'])) {
          $this->session->data['shipping_method'][$order_id]['address'] = trim($posted_data['address_input']);
        }

        // Save house and apartment for 'ourdelivery'
        if (isset($posted_data['shipping_house'])) {
          $this->session->data['shipping_method'][$order_id]['shipping_house'] = trim($posted_data['shipping_house']);
        }
        if (isset($posted_data['shipping_apartment'])) {
          $this->session->data['shipping_method'][$order_id]['shipping_apartment'] = trim($posted_data['shipping_apartment']);
        }

        if (isset($posted_data['comment'])) {
          $this->session->data['shipping_method'][$order_id]['comment'] = trim($posted_data['comment']);
        }

        // Sync dropshipping TTH numbers from post to files session
        if (isset($posted_data['dropshipping_tth_numbers'])) {
          if (isset($this->session->data['dropshipping_tth_files'][$order_id])) {
            foreach ($this->session->data['dropshipping_tth_files'][$order_id] as &$file) {
              if (isset($posted_data['dropshipping_tth_numbers'][$file['code']])) {
                $file['tth_number'] = $posted_data['dropshipping_tth_numbers'][$file['code']];
              }
            }
          }
          if (isset($this->session->data['dropshipping_tth_files'][0])) {
            foreach ($this->session->data['dropshipping_tth_files'][0] as &$file) {
              if (isset($posted_data['dropshipping_tth_numbers'][$file['code']])) {
                $file['tth_number'] = $posted_data['dropshipping_tth_numbers'][$file['code']];
              }
            }
          }
        }
      }
    }

    // Method
    if (isset($this->session->data['shipping_method'])) {

      // Отримуємо список активних складів з кошика, щоб не валідувати застарілі дані в сесії
      $active_warehouse_ids = array();
      $cart_products = $this->cart->getProducts();
      foreach ($cart_products as $product) {
        if (isset($product['warehouse_id'])) {
          $active_warehouse_ids[] = (int)$product['warehouse_id'];
        }
      }

      // Якщо кошик не пустий, але warehouse_id не задано, за замовчуванням використовуємо 0 або config
      if (!empty($cart_products) && empty($active_warehouse_ids)) {
        $active_warehouse_ids[] = (int)$this->config->get('config_warehouse_id');
      }
      $active_warehouse_ids = array_unique($active_warehouse_ids);

      foreach ($this->session->data['shipping_method'] as $order_id => $method) {

        // Пропускаємо валідацію, якщо цього складу вже немає в кошику
        if (!empty($active_warehouse_ids) && !in_array((int)$order_id, $active_warehouse_ids)) {
          continue;
        }

        switch ($method['code']) {
          case "dropshipping":
            if (empty($this->session->data['dropshipping_tth_files'][$order_id]) && empty($this->session->data['dropshipping_tth_files'][0])) {
              $this->load->language('extension/shipping/dropshipping');
              $json['error'][$order_id]['dropshipping_tth'] = $this->language->get('error_tth_required');
            } else {
              $files = $this->session->data['dropshipping_tth_files'][$order_id] ?? ($this->session->data['dropshipping_tth_files'][0] ?? array());
              foreach ($files as $file) {
                if (empty($file['tth_number'])) {
                  $json['error'][$order_id]['dropshipping_tth'] = "Будь ласка, вкажіть номер ТТН для всіх завантажених файлів";
                  break;
                }
              }
            }
            break;

          case 'ourdelivery':
            $this->request->post['shipping_code'] = $method['code'];
            
            $car = $this->session->data['shipping_method'][$order_id]['car'] ?? '';
            $address = $this->session->data['shipping_method'][$order_id]['address'] ?? '';

            if (empty($car)) {
              $json['error'][$order_id]['car'] = "Не вказано адресу";
            } elseif ($car === 'other' && empty($address)) {
              $json['error'][$order_id]['address_input'] = "Не вказано адресу";
            }
            break;
          case "np":
            if ($this->request->server['REQUEST_METHOD'] == 'POST') {

              // Якщо місто винесено окремо, воно може бути в custom_city, але бути відсутнім у конкретному замовленні
              if (empty($this->session->data['shipping_method'][$order_id]['city_ref']) && !empty($this->session->data['custom_city']['city_ref'])) {
                $this->session->data['shipping_method'][$order_id]['city_ref'] = $this->session->data['custom_city']['city_ref'];
                $this->session->data['shipping_method'][$order_id]['city'] = $this->session->data['custom_city']['city'] ?? '';
                $this->session->data['shipping_method'][$order_id]['city_delivery_ref'] = $this->session->data['custom_city']['city_delivery_ref'] ?? '';
              }

              if (empty($this->session->data['shipping_method'][$order_id]['city_ref'])) {
                $json['error'][$order_id]['city'] = "Не вказано місто";
              } else {
                switch ($this->session->data['shipping_method'][$order_id]['shipping_np_option']) {
                  case "post":
                    if (empty($this->session->data['shipping_method'][$order_id]['post_ref'])) {
                      $json['error'][$order_id]['post'] = "Не вказано відділення";
                    } else {
                      $total_weight = $this->getRealWeight($order_id);
                      $desc = $this->session->data['shipping_method'][$order_id]['post'] ?? '';
                      if ($desc && preg_match('/до\s+(\d+)\s+кг/ui', $desc, $matches)) {
                        $weight_limit = (int)$matches[1];
                        if ($total_weight > $weight_limit) {
                          $json['error'][$order_id]['post'] = sprintf("Вага замовлення (%s кг) перевищує обмеження цього відділення (%s кг)", number_format($total_weight, 2), $weight_limit);
                        }
                      }
                    }
                    break;
                  case "postomat":
                    if (empty($this->session->data['shipping_method'][$order_id]['postomat_ref'])) {
                      $json['error'][$order_id]['postomat'] = "Не вказано поштомат";
                    } else {
                      $total_weight = $this->getRealWeight($order_id);
                      $desc = $this->session->data['shipping_method'][$order_id]['postomat'] ?? '';
                      if ($desc && preg_match('/до\s+(\d+)\s+кг/ui', $desc, $matches)) {
                        $weight_limit = (int)$matches[1];
                        if ($total_weight > $weight_limit) {
                          $json['error'][$order_id]['postomat'] = sprintf("Вага замовлення (%s кг) перевищує обмеження цього поштомату (%s кг)", number_format($total_weight, 2), $weight_limit);
                        }
                      }
                    }
                    break;
                  case "address":
                    if (empty($this->session->data['shipping_method'][$order_id]['address_ref'])) {
                      $json['error'][$order_id]['address'] = "Не вказано адреса";
                    }
                    if (empty($this->session->data['shipping_method'][$order_id]['address_house'])) {
                      $json['error'][$order_id]['address_house'] = "Не вказано будинок";
                    }
/*
                    if (empty($this->session->data['shipping_method'][$order_id]['address_apartment'])) {
                      $json['error'][$order_id]['address_apartment'] = "Не вказано квартиру";
                    }
*/
                    break;
                }
              }
            }
            break;
          case 'pickup':
            if (isset($this->request->post['pickup-id'])) {
              $this->load->model('extension/module/ocwarehouses');
              $warehouse_id = $this->request->post['pickup-id'];
              $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);
              $this->session->data['shipping_method'][$order_id]['address'] = $warehouse["name"];
            }
            break;
        }
      }
    } else {
      $json['error']['warning'] = "Помилка заповнення доставки";
    }

    // Address
    if (!$json) {
      // Клиент зарегистрированный
      if ($this->customer->isLogged()) {
        // Выбран существующий адрес
        if (isset($this->request->post['shipping_address']) && $this->request->post['shipping_address'] == 'existing') {
          // Новый адрес
        } else {
        }
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function getSavedAddresses()
  {
    $json = array();
    
    if (!$this->customer->isLogged()) {
      $json['error'] = 'Ви повинні авторизуватися';
    } else {
      $type = $this->request->post['type'] ?? '';
      $order_id = $this->request->post['order_id'] ?? 0;
      $city_ref = $this->request->post['city_ref'] ?? '';
      $city_name = $this->request->post['city_name'] ?? '';
      
      $this->load->model('account/address');
      
      $customer_cod_guid = '';
      if (isset($this->session->data['client_address_id'])) {
        $trading_point = $this->model_account_address->getAddress($this->session->data['client_address_id']);
        if ($trading_point) {
          $customer_cod_guid = $trading_point['customer_cod_guid'];
        }
      }

      $addresses = $this->model_account_address->getAddresses();
      
      $filtered = array();
      foreach ($addresses as &$address) {
        if ($address['type'] === $type) {

          $address['comment'] = isset($address['comment']) ? trim($address['comment']) : '';

          // Фільтруємо за містом
          if ($type !== 'car' && ($city_ref || $city_name)) {
            $city_match = false;
            
            if ($city_ref && isset($address['np_city_id']) && $address['np_city_id'] === $city_ref) {
              $city_match = true;
            } elseif ($city_name && !empty($address['np_city_name'])) {
              $address_city = mb_strtolower(trim($address['np_city_name']), 'UTF-8');
              $search_city = mb_strtolower(trim($city_name), 'UTF-8');
              
              if ($address_city === $search_city || mb_stripos($search_city, $address_city) !== false || mb_stripos($address_city, $search_city) !== false) {
                $city_match = true;
              }
            }

            if (!$city_match) {
              continue;
            }
          }
          
          if ($customer_cod_guid && isset($address['customer_cod_guid']) && $address['customer_cod_guid'] !== $customer_cod_guid) {
            continue;
          }

          if ($type === 'car' && !empty($address['guid'])) {
            continue;
          }

          $filtered[] = $address;
        }
      }
      
      $data['addresses'] = $filtered;
      $data['type'] = $type;
      $data['order_id'] = $order_id;
      
      $json['html'] = $this->load->view('checkout/shipping_np_addresses_modal', $data);
    }
    
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function update_option()
  {
    $json = array();

    $this->load->model('setting/setting');
    $this->load->model('setting/extension');

    $order_id = 0;
    if (isset($this->request->get['order_id'])) {
      $order_id = (int)$this->request->get['order_id'];
    }

    $option = isset($this->request->get['shipping_option']) ? $this->request->get['shipping_option'] : "";
    $this->session->data['shipping_method'][$order_id]['shipping_np_option'] = $option;

    // Рекалькулюємо вартість для обраної опції та оновлюємо сесію
    $methods = $this->getMethods($order_id);
    if (isset($methods['np'])) {
      $this->session->data['shipping_method'][$order_id]['cost'] = $methods['np']['cost'];
      $this->session->data['shipping_method'][$order_id]['free'] = $methods['np']['free'];
      if (!empty($methods['np']['error'])) {
        $this->session->data['shipping_method'][$order_id]['error'] = $methods['np']['error'];
      } else {
        unset($this->session->data['shipping_method'][$order_id]['error']);
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function clearCity()
  {
    unset($this->session->data['shipping_method']);
    unset($this->session->data['custom_city']);
    $this->response->setOutput(json_encode(['success' => true]));
  }

  private function getAddress($fields, $post, $shipping_code)
  {

    $address = array();
    $json = array();

    // Пробегаемся по полям
    foreach ($fields as $field) {

      $name = $field['name'];
      $value = $post[$name];
      $validation = $field['validation'];

      if (isset($field['method']) && array_search($shipping_code, (array)$field['method']) !== false) {

        // Если поле обязательно, то проверяем его
        if (isset($field['required']) && array_search($shipping_code, (array)$field['required']) !== false) {

          // Если есть ошибка, то запомниаем её
          if ($this->validate($name, $value, $validation)) {
            if (stripos($name, 'custom_field') === false) {
              $json['error'][$name] = $this->language->get('error_' . $name);
            } else {
              $custom_field = $this->model_account_custom_field->getCustomField((int)substr($name, 12));
              $json['error'][$name] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
            }
          } else {
            $address[$name] = $value;
          }

        } else {
          $address[$name] = $value;
        }

      }

    }

    return array(
      'json' => $json,
      'address' => $address
    );

  }

  private function full($address)
  {

    // Восстанавливаем custom-поля
    foreach ($address as $key => $field) {

      // is_array - чтобы не ушатать возможное уже готовое значение
      if (stripos($key, 'custom_field') !== false && !is_array($address[$key])) {
        $id = (int)str_replace('custom_field', '', $key);
        $address['custom_field']['address'][$id] = $field;
        unset($address[$key]);
      }

    }

    if ($this->customer->isLogged()) {
      $firstname = $this->customer->getFirstName();
      $lastname = $this->customer->getLastName();
    } else {
      $firstname = isset($this->session->data['guest']['firstname']) ? $this->session->data['guest']['firstname'] : '';
      $lastname = isset($this->session->data['guest']['lastname']) ? $this->session->data['guest']['lastname'] : '';
    }

    // Какие поля должны быть
    $default = array(
      'firstname' => $firstname,
      'lastname' => $lastname,
      'email' => $this->config->get('config_email'),
      'telephone' => '',
      'company' => '',
      'address_1' => '',
      'address_2' => '',
      'city' => '',
      'postcode' => '',
      'zone' => '',
      'zone_id' => '',
      'country' => '',
      'default' => true,
      'country_id' => '',
      'address_format' => '',
    );

    return array_merge($default, $address);
  }

  private function validate($name, $value, $validation = '')
  {

    // Проверяем на пустоту
    if (empty($value)) {
      return true;

      // Проверка на регулярное выражение
    } elseif (!empty($validation) && !filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => trim($validation))))) {
      return true;
    }

    return false;

  }

  /**
   * Внутрішній метод для фільтрації та отримання доступних методів доставки
   * Містить логіку видимості "Нашої доставки" на основі міста/регіону
   */
  private function getOurDeliveryHtml($order_id)
  {
    $html = '';
    $this->load->model('account/address');

    if (isset($this->session->data['client_address_id'])) {
      $select_adress = $this->model_account_address->getAddress($this->session->data['client_address_id']);
    }
    $customer_cod_guid = (!empty($select_adress)) ? $select_adress['customer_cod_guid'] : '';
    $customer_address_car = $this->model_account_address->getAddressCustomerByType($customer_cod_guid, 'car');

    $shipping_house = $this->session->data['shipping_method'][$order_id]['shipping_house'] ?? '';
    $shipping_apartment = $this->session->data['shipping_method'][$order_id]['shipping_apartment'] ?? '';
    $comment = $this->session->data['shipping_method'][$order_id]['comment'] ?? '';

    $can_select_saved = false;
    if (!empty($customer_address_car)) {
      foreach ($customer_address_car as $item_car) {
        if (empty($item_car['guid'])) {
          $can_select_saved = true;
          break;
        }
      }
    }



    $html .= '<div class="bl-np-type">';

    $manual_address = $this->session->data['shipping_method'][$order_id]['address'] ?? '';
    $html .= '<input type="hidden" name="shipping_method[' . $order_id . '][car]" value="other">';
    $html .= '<div class="form-group">';
    $html .= '<div class="inp-ourdelivery">';

    if ($can_select_saved) {
      $html .= '<div class="bl-save-address">';
      $html .= '<div class="save-addresses" onclick="showSavedCarAddresses(' . $order_id . ')">Вибрати зі збережених</div>';
      $html .= '</div>';
    }

    $html .= '<lable class="control-label" for="addressOurDeliveryInput-' . $order_id . '">Введіть адресу</lable><input type="text" name="shipping_method[' . $order_id . '][address_input]" id="addressOurDeliveryInput-' . $order_id . '" value="' . $manual_address . '" oninput="inputStreetOurDelivery(' . $order_id . ')" class="form-control" placeholder="Введіть адресу" autocomplete="off" ' . ($manual_address ? 'readonly="readonly"' : '') . '>';
    $html .= '<img id="clear-street-ourdelivery-' . $order_id . '" width="20px" src="/image/close.svg" class="clear-street-ourdelivery" style="' . ($manual_address ? '' : 'display: none;') . '" onclick="clearStreetOurDelivery(' . $order_id . ')">';

    $html .= '</div>';
    $html .= '<div id="streets-ourdelivery-' . $order_id . '" class="streets-ourdelivery" style="display: none; position: absolute; z-index: 999; width: 100%; background: #fff; border: 1px solid #ccc;"></div>';
    $html .= '</div>';

    $html .= '<div class="address_param">';
    $html .= '<div class="bl-address-param form-group"><lable class="control-label" for="shipping_house-' . $order_id . '">Номер будинку</lable><input type="text" name="shipping_method[' . $order_id . '][shipping_house]" id="shipping_house-' . $order_id . '" value="' . $shipping_house . '" class="form-control" placeholder="Номер будинку" oninput="custom_block.debounceSaveShipping(' . $order_id . ')"></div>';
    $html .= '<div class="bl-address-param form-group"><lable class="control-label" for="shipping_apartment-' . $order_id . '">Номер квартири</lable><input type="text" name="shipping_method[' . $order_id . '][shipping_apartment]" id="shipping_apartment-' . $order_id . '" value="' . $shipping_apartment . '" class="form-control" placeholder="Номер квартири" oninput="custom_block.debounceSaveShipping(' . $order_id . ')"></div>';
    $html .= '</div>';
    
    // Додаємо поле для коментаря до адреси
    $html .= '<div class="address-comment-wrapper bl-bold">';
    $html .= '<a href="javascript:void(0);" onclick="toggleAddressComment(this);">Додати коментар до адреси</a>';
    $html .= '<div class="address-comment-textarea form-group" style="display: ' . ($comment ? 'block' : 'none') . ';">';
    $html .= '<lable class="control-label">Додатковий коментар по адресі доставки</lable>';
    $html .= '<textarea name="shipping_method[' . $order_id . '][comment]" class="form-control" rows="1" placeholder="Додатковий коментар по адресі доставки" oninput="custom_block.debounceSaveShipping(' . $order_id . ')">' . $comment . '</textarea>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';

    $html .= '<script>
    if (typeof inputStreetOurDelivery !== "function") {
      window.inputStreetOurDelivery = function(order_id) {
        var $input = $("#addressOurDeliveryInput-" + order_id);
        var $result = $("#streets-ourdelivery-" + order_id);
        var cityRef = $("#city-ref-" + order_id).val();

        clearTimeout(window.ourdeliveryTimer);
        var query = $input.val().trim();

        if (query.length > 0) {
          $("#clear-street-ourdelivery-" + order_id).show();
        } else {
          $("#clear-street-ourdelivery-" + order_id).hide();
        }

        if (query.length === 0) {
          $result.hide();
          return;
        }

        window.ourdeliveryTimer = setTimeout(function() {
          $.ajax({
            url: "index.php?route=extension/shipping/np",
            type: "post",
            data: "&event=address&ref=" + cityRef + "&q=" + query,
            dataType: "json",
            beforeSend: function() {
              $result.show().html(\'<div class="loading" style="padding: 5px 10px;"><i class="fa fa-spinner fa-spin"></i> Пошук...</div>\');
            },
            success: function(json) {
              $result.show().html("");
              $.each(json, function(index, item) {
                if (item["ref"] !== "-") {
                  $(\'<div></div>\', {
                    "class": "row_item",
                    "text": item["desc"],
                    "css": {"padding": "5px 10px", "cursor": "pointer"}
                  }).appendTo($result).on("click", function() {
                    $input.val(item["desc"]).attr("readonly", true);
                    $result.hide();
                    $("#clear-street-ourdelivery-" + order_id).show();
                    custom_block.editAddress(item["desc"], "ourdelivery", order_id);
                  });
                }
              });
            }
          });
        }, 300);
      }
    }

    if (typeof clearStreetOurDelivery !== "function") {
      window.clearStreetOurDelivery = function(order_id) {
        var $input = $("#addressOurDeliveryInput-" + order_id);
        $input.val("").attr("readonly", false);
        $("#clear-street-ourdelivery-" + order_id).hide();
        $("#streets-ourdelivery-" + order_id).hide();
        custom_block.editAddress("", "ourdelivery", order_id);
      }
    }

    if (typeof showSavedCarAddresses !== "function") {
      window.showSavedCarAddresses = function(order_id) {
        $.ajax({
          url: "index.php?route=extension/module/custom/shipping/getSavedAddresses",
          type: "post",
          data: "type=car&order_id=" + order_id,
          dataType: "json",
          success: function(json) {
            if (json["html"]) {
              $("#modal-np-addresses").remove();
              $("body").append(json["html"]);
              $("#modal-np-addresses").modal("show");
            }
          }
        });
      }
    }

    if (typeof selectCarAddress !== "function") {
      window.selectCarAddress = function(order_id, address) {
        $("#modal-np-addresses").modal("hide");
        var $input = $("#addressOurDeliveryInput-" + order_id);
        $input.val(address.address_1).attr("readonly", true);
        $("#clear-street-ourdelivery-" + order_id).show();
        $("#shipping_house-" + order_id).val(address.np_house);
        $("#shipping_apartment-" + order_id).val(address.np_apartment);
        
        // Fill comment
        if (address.comment) {
          $(\'[name="shipping_method[\' + order_id + \'][comment]"]\').val(address.comment);
          $(\'[name="shipping_method[\' + order_id + \'][comment]"]\').closest(".address-comment-textarea").show();
          $(\'[name="shipping_method[\' + order_id + \'][comment]"]\').parent().show();
        } else {
          $(\'[name="shipping_method[\' + order_id + \'][comment]"]\').val("");
          $(\'[name="shipping_method[\' + order_id + \'][comment]"]\').closest(".address-comment-textarea").hide();
          $(\'[name="shipping_method[\' + order_id + \'][comment]"]\').parent().hide();
        }
        
        if (typeof custom_block !== "undefined" && typeof custom_block.updateShipping === "function") {
           custom_block.updateShipping(order_id);
        }
      }
    }
    </script>';

    return $html;
  }

  private function getMethods($order_id = 0)
  {
    // Методи доставки
    $method_data = array();

    $this->load->model('setting/extension');
    $results = $this->model_setting_extension->getExtensions('shipping');
    $module_custom = $this->model_setting_setting->getSetting('module_custom');

    // Ідентифікація мови
    $this->load->model('localisation/language');
    $langId = $this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];

    // Визначення місця розташування
    if (!empty($this->session->data['shipping_address']['country_id'])) {
      $country_id = $this->session->data['shipping_address']['country_id'];
    } else {
      $country_id = $this->config->get('config_country_id');
    }

    if (!empty($this->session->data['shipping_address']['zone_id'])) {
      $zone_id = $this->session->data['shipping_address']['zone_id'];
    } else {
      $zone_id = $this->config->get('config_zone_id');
    }

    $this->load->model('account/address');

    $select_adress = null;
    if (isset($this->session->data['client_address_id'])) {
      $select_adress = $this->model_account_address->getAddress($this->session->data['client_address_id']);
    }

    $selected_city = '';
    if (!empty($this->session->data['shipping_method'][$order_id]['city'])) {
      $selected_city = $this->session->data['shipping_method'][$order_id]['city'];
    } elseif (!empty($this->session->data['custom_city']['city'])) {
      $selected_city = $this->session->data['custom_city']['city'];
    } elseif (!empty($this->request->post['shipping_method'][$order_id]['city'])) {
      $selected_city = $this->request->post['shipping_method'][$order_id]['city'];
    }

    $is_kyiv_region = false;
    if ($selected_city) {
      $is_kyiv_region = (mb_stripos($selected_city, 'Київська') !== false || mb_stripos($selected_city, 'Киевская') !== false || mb_stripos($selected_city, 'м. Київ') !== false || mb_stripos($selected_city, 'г. Киев') !== false);
    } elseif ($select_adress) {
      $is_kyiv_region = (mb_stripos($select_adress['zone'], 'Київ') !== false || mb_stripos($select_adress['zone'], 'Киев') !== false);
    }

    foreach ($results as $result) {
      $f = "";
      $cost = 0;
      $free = 0;
      $minimum = 0;

      $title = '';
      $allowed_by_group = true;
      $sort = null;

      // Отримуємо налаштування кастомного методу (вартість, поріг безкоштовної доставки, опис)
      $customer_group_id = (int)$this->customer->getGroupId();

      foreach ($module_custom['module_custom_shipping']['methods'] as $delivery) {
        if ($delivery['code'] == $result['code']) {

          if (isset($delivery['customer_group']) && !empty($delivery['customer_group']) && !in_array($customer_group_id, $delivery['customer_group'])) {
            $allowed_by_group = false;
          }

          $f = html_entity_decode($delivery['descs'][$langId]['desc']);

          if (isset($delivery['title'][$langId]['title']) && $delivery['title'][$langId]['title'] !== '') {
            $title = $delivery['title'][$langId]['title'];
          }

          if (isset($delivery['sort'])) {
            $sort = $delivery['sort'];
          }

          if (isset($delivery['cost']) && is_array($delivery['cost'])) {
            $cost = $delivery['cost'][$customer_group_id] ?? 0;
          } else {
            $cost = $delivery['cost'] ?? 0;
          }

          if (isset($delivery['free']) && is_array($delivery['free'])) {
            $free = $delivery['free'][$customer_group_id] ?? 0;
          } else {
            $free = $delivery['free'] ?? 0;
          }

          if (isset($delivery['minimum']) && is_array($delivery['minimum'])) {
            $minimum = $delivery['minimum'][$customer_group_id] ?? 0;
          } else {
            $minimum = $delivery['minimum'] ?? 0;
          }

          // Спеціальна логіка для Нової Пошти (різні ціни для відділення, адреси, поштомату)
          if ($result['code'] == 'np') {
            $np_option = $this->session->data['shipping_method'][$order_id]['shipping_np_option'] ?? 'post';
            
            if ($np_option == 'address') {
              if (isset($delivery['cost_address'][$customer_group_id]) && $delivery['cost_address'][$customer_group_id] !== '') {
                $cost = $delivery['cost_address'][$customer_group_id];
              }
              if (isset($delivery['free_address'][$customer_group_id]) && $delivery['free_address'][$customer_group_id] !== '') {
                $free = $delivery['free_address'][$customer_group_id];
              }
              if (isset($delivery['minimum_address'][$customer_group_id]) && $delivery['minimum_address'][$customer_group_id] !== '') {
                $minimum = $delivery['minimum_address'][$customer_group_id];
              }
            } elseif ($np_option == 'postomat') {
              if (isset($delivery['cost_postomat'][$customer_group_id]) && $delivery['cost_postomat'][$customer_group_id] !== '') {
                $cost = $delivery['cost_postomat'][$customer_group_id];
              }
              if (isset($delivery['free_postomat'][$customer_group_id]) && $delivery['free_postomat'][$customer_group_id] !== '') {
                $free = $delivery['free_postomat'][$customer_group_id];
              }
              if (isset($delivery['minimum_postomat'][$customer_group_id]) && $delivery['minimum_postomat'][$customer_group_id] !== '') {
                $minimum = $delivery['minimum_postomat'][$customer_group_id];
              }
            }
          }

          break;
        }
      }

      if (!$allowed_by_group) {
        continue;
      }

      $this->load->model('extension/shipping/' . $result['code']);
      $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote(array(
        'country_id' => $country_id,
        'zone_id' => $zone_id
      ));

      // Спеціальна логіка для "Нашої доставки" та "Самовивозу"
      if ($result['code'] == "ourdelivery" || $result['code'] == "pickup") {

          $customer_cod_guid = "";
          if ($select_adress != null){
            $customer_cod_guid = $select_adress['customer_cod_guid'];
          }

          // Отримуємо спеціальні адреси доставки для цього клієнта
          $customer_address_car = $this->model_account_address->getAddressCustomerByType($customer_cod_guid, 'car');

          // Рішення про видимість
          $show_method = false;
          if ($selected_city) {
            // Якщо місто явно вибрано, показуємо ТІЛЬКИ якщо це Київський регіон
            if ($is_kyiv_region) {
              $show_method = true;
            }
          } else {
            // Якщо місто НЕ вибрано, показуємо, якщо торгова точка в Києві АБО існують спеціальні адреси
            if ($is_kyiv_region || !empty($customer_address_car) || ($select_adress != null && (int)$select_adress['ourdelivery'] > 0)) {
              $show_method = true;
            }
          }

          $error = $quote['error'];
          if ($show_method) {
            $real_subtotal = $this->getRealSubtotal();

            if ($real_subtotal < (float)$minimum) {
              $remaining = (float)$minimum - $real_subtotal;
              $error = "Для вибору доставки залишилось додати товарів ще на " . $this->currency->format($remaining, $this->session->data['currency']);
            }

            if ($result['code'] == "ourdelivery") {
              if ((float)$minimum > 0 && $real_subtotal < (float)$free) {
                $remaining = (float)$free - $real_subtotal;
                $error = "Для вибору доставки залишилось додати товарів ще на " . $this->currency->format($remaining, $this->session->data['currency']);
              }
            }
            
            if ($error && isset($this->session->data['shipping_method'][$order_id]['code']) && $this->session->data['shipping_method'][$order_id]['code'] == $result['code']) {
                if ($result['code'] == 'ourdelivery') {
                  unset($this->session->data['shipping_method'][$order_id]['car']);
                  unset($this->session->data['shipping_method'][$order_id]['address']);
                }
            }
          }

          if ($show_method) {
            $method_data[$result['code']] = array(
              'code' => $result['code'],
              'title' => $title ?: $quote['title'],
              'quote' => $quote['quote'],
              'desc' => nl2br($f),
              'sort_order' => ($sort !== null) ? $sort : $quote['sort_order'],
              'error' => $error,
              'cost' => $cost,
              'free' => $free,
              'minimum' => $minimum
            );
          }

      } else {
        // Стандартні методи (наприклад, Нова Пошта)
        $error = $quote['error'];
        $real_subtotal = $this->getRealSubtotal();

        // Якщо місто не вибрано, для Нової Пошти не показуємо вартість і ставимо помилку
        if (!$selected_city && $result['code'] == 'np') {
          $error = "Будь ласка, виберіть місто";
          $cost = 0;
        }

        if ($real_subtotal < (float)$minimum) {
          $remaining = (float)$minimum - $real_subtotal;
          $error = "Для вибору доставки залишилось додати товарів ще на " . $this->currency->format($remaining, $this->session->data['currency']);
        }

        $method_data[$result['code']] = array(
          'code' => $result['code'],
          'title' => $title ?: $quote['title'],
          'quote' => $quote['quote'],
          'desc' => nl2br($f),
          'sort_order' => ($sort !== null) ? $sort : $quote['sort_order'],
          'error' => $error,
          'cost' => $cost,
          'free' => $free,
          'minimum' => $minimum
        );
      }
    }

    // Сортуємо методи за sort_order
    $sort_order = array();
    foreach ($method_data as $key => $value) {
      $sort_order[$key] = $value['sort_order'];
    }
    array_multisort($sort_order, SORT_ASC, $method_data);

    return $method_data;
  }

  private function addAddress($address)
  {
    $this->load->model('account/address');
    $address_id = $this->model_account_address->addAddress($this->customer->getId(), $address);
    return $this->model_account_address->getAddress($address_id);
  }

  private function getAddressById($address_id)
  {
    $this->load->model('account/address');
    return $this->model_account_address->getAddress($address_id);
  }

}
