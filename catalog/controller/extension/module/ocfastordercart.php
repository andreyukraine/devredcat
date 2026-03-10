<?php

class ControllerExtensionModuleOcfastordercart extends Controller
{
  private $error = array();

  public function index()
  {
    if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

      $this->language->load('extension/module/ocfastordercart');

      $data['lang_id'] = $this->config->get('config_language_id');

      $data['button_send'] = $this->language->get('button_send');
      $data['any_text_at_the_bottom_color'] = $this->config->get('config_any_text_at_the_bottom_color');
      $data['mask_phone_number'] = "+38(999)999-99-99";

      $data['text_total_qucik_ckeckout'] = $this->cart->countProducts();

      if ($this->customer->isLogged()) {
        $data['phone'] = $this->customer->getTelephone();
        $data['name']  = $this->customer->getFirstname() . " " . $this->customer->getLastname();
        $data['email'] = $this->customer->getEmail();
      }else{
        $data['phone'] = '';
        $data['name']  = '';
        $data['email'] = '';
      }

      $this->load->model('account/customer');
      $affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());

      if (!$affiliate_info && $this->config->get('config_checkout_id')) {
        $this->load->model('catalog/information');

        $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

        if ($information_info) {
          $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), true), $information_info['title']);
        } else {
          $data['text_agree'] = '';
        }
      } else {
        $data['text_agree'] = '';
      }

      // Captcha
      if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status')) {
        $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
      } else {
        $data['captcha'] = '';
      }



      $this->load->model('tool/image');
      $this->load->model('tool/upload');
      $this->load->model('catalog/product');
      $this->load->model('extension/module/ocwarehouses');

      $data['totals'] = array();
      $data['products'] = array();
      $data['store'] = false;

      $data['is_mobile'] = $this->mobile_detect->isMobile();

      $width = 400;
      $height = 400;

      if ($data['is_mobile']) {
        $width = 200;
        $height = 200;
      }

      $products = $this->cart->getProducts();
      if (!empty($products)) {
        unset($this->session->data['orders']);

        if ($this->config->get('module_custom_check_qty_cart_by_store') != null) {

          $data['store'] = true;

          // Группируем товары по store_id
          $ordersByStore = [];

          foreach ($products as $item) {
            $store_id = $item['store_id'];

            if (!isset($ordersByStore[$store_id])) {
              $ordersByStore[$store_id] = [];
            }

            $image = "no_image.png";
            if (!empty($item['image']) && file_exists(DIR_IMAGE.$item['image'])){
              $image = $item['image'];
            }
            if (!empty($item['option'])) {
              foreach ($item['option'] as $option) {
                if (!empty($option['image_opt']) && file_exists(DIR_IMAGE.$option['image_opt'])) {
                  $image = $option['image_opt'];
                }
              }
            }

            $price = $item['price'];
            $special = $item['special'] > 0 ? $item['special'] : false;
            $percent = (int)$item['percent'] > 0 ? (int)$item['percent'] : false;

            // Обработка опций
            $mass_options = [];
            if (isset($item['option'])) {
              foreach ($item['option'] as $option) {
                $mass_options[(int)$option['product_option_id']] = (int)$option['product_option_value_id'];
              }
            }

            $ordersByStore[$store_id][] = [
              'cart_id' => $item['cart_id'],
              'store_id' => $item['store_id'],
              'product_id' => $item['product_id'],
              'quantity' => $item['quantity'],
              'price' => $price,
              'special' => $special,
              'percent' => $percent,
              'option' => $item['option'],
              'mass_options' => $mass_options,
              'model' => $item['model'],
              'image' => $image,
              'href' => $this->url->link('product/product', 'product_id=' . $item['product_id'])
            ];
          }

          // Формируем данные для отображения
          foreach ($ordersByStore as $store_id => $items) {
            $warehouse_info = $this->model_extension_module_ocwarehouses->getWarehouseId($store_id);

            $warehouse_prods = array();
            foreach ($items as $prod) {
              $product_info = $this->model_catalog_product->getProduct($prod['product_id']);

              $image = $this->model_tool_image->resize($prod['image'], $width, $height);

              if ($prod['special'] > 0) {
                $total = $prod['special'] * $prod['quantity'];
              } else {
                $total = $prod['price'] * $prod['quantity'];
              }

              $price_view = $this->currency->format($prod['price'], $this->session->data['currency']);
              $price_total_item_view = $this->currency->format($prod['price'] * $prod['quantity'], $this->session->data['currency']);
              $special_view = $prod['special'] > 0 ? $this->currency->format($prod['special'], $this->session->data['currency']) : false;
              $special_total_item_view = $prod['special'] > 0 ? $this->currency->format($total, $this->session->data['currency']) : false;

              // Формируем уникальный ID для товара с опциями
              if (!empty($prod['mass_options'])) {
                $key_opts = implode('-', array_map(
                  function ($key, $value) {
                    return "$key-$value";
                  },
                  array_keys($prod['mass_options']),
                  $prod['mass_options']
                ));
                $uniq_id = $prod['product_id'] . "-" . $key_opts;
              } else {
                $uniq_id = $prod['product_id'];
              }

              $warehouse_prods[] = [
                'cart_id' => $prod['cart_id'],
                'store_id' => $prod['store_id'],
                'model' => $prod['model'],
                'name' => $product_info['name'],
                'option' => $prod['option'],
                'product_id' => $prod['product_id'],
                'image' => $image,
                'quantity' => (int)$prod['quantity'],
                'price' => $prod['price'],
                'price_view' => $price_view,
                'uniq_id' => $uniq_id,
                'is_buy' => true, // Все товары доступны, так как не проверяем остатки
                'total' => $total,
                'price_total_item_view' => $price_total_item_view,
                'special' => $prod['special'],
                'special_view' => $special_view,
                'special_total_item_view' => $special_total_item_view,
                'percent' => $prod['percent'],
                'href' => $prod['href']
              ];
            }

            $data['stores'][$store_id]['products'] = $warehouse_prods;
            $data['stores'][$store_id]['warehouse'] = $warehouse_info;
            $this->session->data['orders'][$store_id] = $warehouse_prods;

          }

        } else {
          foreach ($products as $product) {

            //++Andrey
            $option_data = array();
            $mass_options = array();

            $image = "no_image.png";

            if (!empty($product['option'])) {
              foreach ($product['option'] as $option) {

                if (!empty($option['image_opt']) && file_exists(DIR_IMAGE.$option['image_opt'])) {
                  $image = $option['image_opt'];
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
                  'value' => (utf8_strlen($value) > 60 ? utf8_substr($value, 0, 60) . '..' : $value)
                );
              }
            } else {
              if (!empty($product['image']) && file_exists(DIR_IMAGE.$product['image'])){
                $image = $product['image'];
              }
            }

            $image = $this->model_tool_image->resize($image, $width, $height);

            $price = $product['price'];
            $price_view = $this->currency->format($price, $this->session->data['currency']);
            $price_total_item_view = $this->currency->format($price * $product['quantity'], $this->session->data['currency']);

            $special = $product['special'] > 0 ? $product['special'] : false;
            $special_view = $product['special'] > 0 ? $this->currency->format($special, $this->session->data['currency']) : false;
            $special_total_item_view = $special > 0 ? $this->currency->format($special * $product['quantity'], $this->session->data['currency']) : false;

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

            $is_buy = false;
            $total_qty = 0;
            $total_qty_free = 0;

            $cart_prods = $this->cart->getProductByStore($product['product_id'], $mass_options);
            if (!empty($cart_prods)) {
              foreach ($cart_prods as $item_cart) {
                $total_qty = (int)$item_cart['total_quantity'];
                $total_qty_in_cart = (int)$item_cart['quantity'];
                $total_qty_free = $total_qty - $total_qty_in_cart;
                break;
              }
            } else {
              $total_qty_in_cart = $product['quantity'];
              $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product['product_id'], $mass_options, true);
              if (!empty($stocks)) {
                foreach ($stocks as $stock) {
                  $total_qty = $total_qty + (int)$stock;
                }
                $total_qty_free = $total_qty - $total_qty_in_cart;
              }
            }

            if ($total_qty_free > 0) {
              $is_buy = true;
            }

            $data['products'][] = array(
              'cart_id' => $product['cart_id'],
              'product_id' => $product['product_id'],
              'image' => $image,
              'name' => $product['name'],
              'sku' => $product_info["sku"],
              'option' => $option_data,
              'uniq_id' => $uniq_id,
              'is_buy' => $is_buy,
              'store_id' => $product['store_id'],
              'quantity' => $product['quantity'],
              'price' => $price_view,
              'price_total_item_view' => $price_total_item_view,
              'special' => $special_view,
              'special_total_item_view' => $special_total_item_view,
              'percent' => $percent,
              'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );

          }
        }

        $this->session->data['fastorder'] = true;

        $result = $this->getTotals();

        foreach ($result['totals'] as $total) {

          if ($total['code'] == "shipping"){
            $data['totals'][] = array(
              'title' => $this->language->get('text_delivery'),
              'text'  => ""
            );
          }else {
            $data['totals'][] = array(
              'title' => $total['title'],
              'text' => $total['value'] !== 0 ? $this->currency->format($total['value'], $this->session->data['currency']) : $this->language->get('text_free')
            );
          }
        }
      }



      $this->response->setOutput($this->load->view('extension/module/ocfastordercart', $data));
    } else {
      $this->response->redirect($this->url->link('error/not_found', '', true));
    }
  }

  public function getTotals(){

    $this->load->model('setting/extension');

    $totals = array();
    $taxes = $this->cart->getTaxes();
    $total = 0;

    // Because __call can not keep var references so we put them into an array.
    $total_data = array(
      'totals' => &$totals,
      'taxes'  => &$taxes,
      'total'  => &$total
    );

    // Display prices
    $sort_order = array();

    $results = $this->model_setting_extension->getExtensions('total');

    foreach ($results as $key => $value) {
      $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
    }

    array_multisort($sort_order, SORT_ASC, $results);

    foreach ($results as $result) {

      if ($result['code'] != "sub_total" && $result['code'] != "product_discount"){
        continue;
      }
      if ($this->config->get('total_' . $result['code'] . '_status')) {
        $this->load->model('extension/total/' . $result['code']);

        // We have to put the totals in an array so that they pass by reference.
        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
      }
    }

    $sort_order = array();

    foreach ($totals as $key => $value) {
      $sort_order[$key] = $value['sort_order'];
    }

    array_multisort($sort_order, SORT_ASC, $totals);


    return array(
      'total' => $total,
      'totals' => $totals
    );

  }


  public function addFastOrder()
  {

    $this->load->model('tool/image');
    $this->load->model('catalog/product');
    $this->load->model('account/customer');

    if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && (isset($this->request->post['action'])) && $this->request->server['REQUEST_METHOD'] == 'POST') {

      $json = array();
      if ($this->validate()) {
        $order_data = array();
        $lang_id = $this->config->get('config_language_id');

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

        if ($this->customer->isLogged()) {
          $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
          $order_data['customer_id'] = $this->customer->getId();
          $order_data['customer_group_id'] = $customer_info['customer_group_id'];
        } else {
          $order_data['customer_id'] = 0;
          $order_data['customer_group_id'] = $this->customer->getGroupId();
        }

        if (isset($this->request->post['name_fastorder'])) {
          $order_data['name_fastorder'] = $this->request->post['name_fastorder'];
        } else {
          $order_data['name_fastorder'] = '';
        }

        $order_data['firstname'] = $order_data['shipping_firstname'] = $order_data['payment_firstname'] = $order_data['name_fastorder'];
        $order_data['lastname'] = '';

        if (isset($this->request->post['email_buyer'])) {
          $order_data['email_buyer'] = $this->request->post['email_buyer'];
          $order_data['email'] = (isset($this->request->post['email_buyer']) && !empty($this->request->post['email_buyer'])) ? $this->request->post['email_buyer'] : 'empty' . time() . '@localhost.net';
        } else {
          $order_data['email_buyer'] = '';
          $order_data['email'] = 'empty' . time() . '@localhost.net';
        }

        if (isset($this->request->post['phone'])) {
          $order_data['phone'] = $this->request->post['phone'];
          $order_data['telephone'] = $this->request->post['phone'];
        } else {
          $order_data['phone'] = '';
          $order_data['telephone'] = '';
        }

        if (isset($this->request->post['comment_buyer'])) {
          $order_data['comment_buyer'] = $this->request->post['comment_buyer'];
          $order_data['comment'] = $this->request->post['comment_buyer'];
        } else {
          $order_data['comment_buyer'] = '';
          $order_data['comment'] = '';
        }

        $order_data['custom_field'] = array();
        $order_data['fax'] = '';
        $order_data['payment_lastname'] = '';
        $order_data['payment_company'] = '';
        $order_data['payment_address_1'] = '';
        $order_data['payment_address_2'] = '';
        $order_data['payment_city'] = '';
        $order_data['payment_postcode'] = '';
        $order_data['payment_country'] = '';
        $order_data['payment_country_id'] = '';
        $order_data['payment_zone'] = '';
        $order_data['payment_zone_id'] = '';
        $order_data['payment_address_format'] = '';
        $order_data['payment_custom_field'] = array();
        $order_data['payment_method'] = '';
        $order_data['payment_code'] = '';

        $order_data['shipping_lastname'] = '';
        $order_data['shipping_company'] = '';
        $order_data['shipping_address_1'] = '';
        $order_data['shipping_address_2'] = '';
        $order_data['shipping_city'] = '';
        $order_data['shipping_postcode'] = '';
        $order_data['shipping_country'] = '';
        $order_data['shipping_country_id'] = '';
        $order_data['shipping_zone'] = '';
        $order_data['shipping_zone_id'] = '';
        $order_data['shipping_address_format'] = '';
        $order_data['shipping_custom_field'] = array();
        $order_data['shipping_method'] = '';
        $order_data['shipping_code'] = '';

        $order_data['affiliate_id'] = 0;
        $order_data['commission'] = 0;
        $order_data['marketing_id'] = 0;
        $order_data['tracking'] = '';

        $order_data['language_id'] = $lang_id;
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

        if (isset($this->request->post['url_site'])) {
          $order_data['url_site'] = $this->request->post['url_site'];
        } else {
          $order_data['url_site'] = '';
        }

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
          'totals' => &$totals,
          'taxes' => &$taxes,
          'total' => &$total
        );

        $this->load->model('setting/extension');

        $sort_order = array();

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
          $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
          if ($this->config->get('total_' . $result['code'] . '_status')) {
            $this->load->model('extension/total/' . $result['code']);

            // We have to put the totals in an array so that they pass by reference.
            $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
          }
        }

        $sort_order = array();

        foreach ($totals as $key => $value) {
          $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $totals);


        $order_data['totals'] = $totals;

        $order_data['total'] = $total_data['total'];

        $this->load->model('tool/image');

        $order_data['products'] = array();

        foreach ($this->cart->getProducts() as $product) {
          if ($product['image']) {
            $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
          } else {
            $image = '';
          }
          $option_data = array();

          foreach ($product['option'] as $option) {
            $option_data[] = array(
              'product_option_id' => $option['product_option_id'],
              'product_option_value_id' => $option['product_option_value_id'],
              'option_id' => $option['option_id'],
              'option_value_id' => $option['option_value_id'],
              'name' => $option['name'],
              'value' => $option['value'],
              'type' => $option['type']
            );
          }

          $order_data['products'][] = array(
            'product_id' => $product['product_id'],
            'product_image' => $product['image'],
            'name' => $product['name'],
            'model' => $product['model'],
            'option' => $option_data,
            'download' => $product['download'],
            'quantity' => $product['quantity'],
            'subtract' => $product['subtract'],
            'price' => $product['price'],
            'total' => $product['total'],
            'price_fast' => $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')),
            'total_fast' => $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'],
            'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
            'reward' => $product['reward'],
            'currency_code' => $order_data['currency_code'],
            'currency_value' => $order_data['currency_value'],
          );
        }


        $order_data['total_fast'] = $total_data['total'];


        $this->load->model('extension/module/ocfastorder');
        $this->load->model('checkout/order');

        $order_id = $this->model_checkout_order->addOrder($order_data);
        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('config_order_status_id'));
        $results = $this->model_extension_module_chameleon_newfastorder->addOrder($order_id, $order_data);

        $config_on_off_send_buyer_mail = $this->config->get('config_on_off_send_buyer_mail');
        if ($config_on_off_send_buyer_mail == '1') {
          if ($order_data['email_buyer'] != '') {
            $this->sendMailBuyer($order_data);
          }
        }
        $config_on_off_send_me_mail = $this->config->get('config_on_off_send_me_mail');
        if ($config_on_off_send_me_mail == '1') {
          $this->sendMailMe($order_data);
        }

        $config_complete_quickorder = $this->config->get('config_complete_quickorder');
        $ok = $config_complete_quickorder[$lang_id]['config_complete_quickorder'];
        if ($ok != '') {
          $json['success'] = $ok;
        } else {
          $json['success'] = $this->language->get('ok');
        }
        $this->cache->delete('product.bestseller');
        $this->cart->clear();

        if (isset($this->session->data['fastorder'])) {
          $this->session->data['fastorder'] = null;
        }

      } else {
        $json['error'] = $this->error;
      }
      return $this->response->setOutput(json_encode($json));
    } else {
      $this->response->redirect($this->url->link('error/not_found', '', true));
    }

  }

  private function validate()
  {
    $this->language->load('extension/module/ocfastorder');
    $config_fields_firstname_requared = $this->config->get('config_fields_firstname_requared');
    $config_on_off_fields_firstname = $this->config->get('config_on_off_fields_firstname');
    if (($config_fields_firstname_requared == '1') && $config_on_off_fields_firstname == '1') {
      if ((strlen(utf8_decode($this->request->post['name_fastorder'])) < 1) || (strlen(utf8_decode($this->request->post['name_fastorder'])) > 32)) {
        $this->error['name_fastorder'] = $this->language->get('mister');
      }
    }
    $config_fields_phone_requared = $this->config->get('config_fields_phone_requared');
    $config_on_off_fields_phone = $this->config->get('config_on_off_fields_phone');
    if (($config_fields_phone_requared == '1') && $config_on_off_fields_phone == '1') {
      if ((strlen(utf8_decode($this->request->post['phone'])) < 3) || (strlen(utf8_decode($this->request->post['phone'])) > 32)) {
        $this->error['phone'] = $this->language->get('error_phone');
      }
    }
    $config_fields_comment_requared = $this->config->get('config_fields_comment_requared');
    $config_on_off_fields_comment = $this->config->get('config_on_off_fields_comment');
    if (($config_fields_comment_requared == '1') && $config_on_off_fields_comment == '1') {
      if ((strlen(utf8_decode($this->request->post['comment_buyer'])) < 1) || (strlen(utf8_decode($this->request->post['comment_buyer'])) > 400)) {
        $this->error['comment_buyer'] = $this->language->get('comment_buyer_error');
      }
    }
    $config_fields_email_requared = $this->config->get('config_fields_email_requared');
    $config_on_off_fields_email = $this->config->get('config_on_off_fields_email');
    if (($config_fields_email_requared == '1') && $config_on_off_fields_email == '1') {
      if (!preg_match("/^([a-z0-9_\.-]+)@([a-z0-9_\.-]+)\.([a-z\.]{2,6})$/", $this->request->post['email_buyer'])) {
        $this->error['email_error'] = $this->language->get('email_buyer_error');
      }
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
      $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

      if ($captcha) {
        $this->error['captcha'] = $captcha;
      }
    }

    $this->load->model('catalog/product');
    // Agree to terms
    if ($this->config->get('config_quickorder_id')) {
      $this->load->model('catalog/information');
      $this->load->language('chameleon/theme');
      $information_info = $this->model_catalog_information->getInformation($this->config->get('config_quickorder_id'));

      if ($information_info && !isset($this->request->post['agree'])) {
        $this->error['error_agree'] = sprintf($this->language->get('error_agree'), $information_info['title']);
      }
    }
    if (!$this->error) {
      return true;
    } else {
      return false;
    }
  }

  private function getCustomFields($order_info, $varabliesd)
  {
    $instros = explode('~', $varabliesd);
    $instroz = "";
    foreach ($instros as $instro) {
      if ($instro == 'totals' || isset($order_info[$instro])) {
        if ($instro == 'totals') {
          $instro_other = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], true);
        }
        if (isset($order_info[$instro])) {
          $instro_other = $order_info[$instro];
        }
      } else {
        $instro_other = nl2br(htmlspecialchars_decode($instro));
      }
      $instroz .= $instro_other;
    }
    return $instroz;
  }

  private function sendMailBuyer($data)
  {

    $this->language->load('extension/module/ocfastorder');
    $data['text_photo'] = $this->language->get('text_photo');
    $data['text_product'] = $this->language->get('text_new_product');
    $data['text_model'] = $this->language->get('text_new_model');
    $data['text_quantity'] = $this->language->get('text_new_quantity');
    $data['text_price'] = $this->language->get('text_new_price');
    $data['text_total'] = $this->language->get('text_new_total');

    foreach ($data['totals'] as $result) {
      $data['totals_t'][] = array(
        'title' => $result['title'],
        'text' => $this->currency->format($result['value'], $this->session->data['currency']),
      );
    }
    $data['totals'] = $data['totals_t'];

    $text = '';
    $quickorder_subject = $this->config->get('quickorder_subject');
    $quickorder_description = $this->config->get('quickorder_description');
    $subject_buyer = $this->getCustomFields($data, $quickorder_subject [$data['language_id']]['text']);
    if ((strlen(utf8_decode($subject_buyer)) > 5)) {
      $subject = $subject_buyer;
    } else {
      $subject = $this->language->get('subject');
    }
    $html = $this->getCustomFields($data, $quickorder_description[$data['language_id']]['text']) . "\n";
    $config_buyer_html_products = $this->config->get('config_buyer_html_products');
    if ($config_buyer_html_products == '1') {
      $html .= $this->load->view('mail/quickorder', $data);
    }
    $mail = new Mail($this->config->get('config_mail_engine'));
    $mail->parameter = $this->config->get('config_mail_parameter');
    $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
    $mail->smtp_username = $this->config->get('config_mail_smtp_username');
    $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
    $mail->smtp_port = $this->config->get('config_mail_smtp_port');
    $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

    $mail->setTo($data['email_buyer']);
    $mail->setFrom($this->config->get('config_email'));
    $mail->setSender(html_entity_decode($data['store_name'], ENT_QUOTES, 'UTF-8'));
    $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
    $mail->setHtml(html_entity_decode($html, ENT_QUOTES, 'UTF-8'));
    $mail->setText($text);
    $mail->send();
  }

  private function sendMailMe($data)
  {

    $this->language->load('extension/module/ocfastorder');
    $data['text_photo'] = $this->language->get('text_photo');
    $data['text_product'] = $this->language->get('text_new_product');
    $data['text_model'] = $this->language->get('text_new_model');
    $data['text_quantity'] = $this->language->get('text_new_quantity');
    $data['text_price'] = $this->language->get('text_new_price');
    $data['text_total'] = $this->language->get('text_new_total');

    foreach ($data['totals'] as $result) {
      $data['totals_t'][] = array(
        'title' => $result['title'],
        'text' => $this->currency->format($result['value'], $this->session->data['currency']),
      );
    }
    $data['totals'] = $data['totals_t'];

    $text = '';
    $quickorder_subject_me = $this->config->get('quickorder_subject_me');
    $quickorder_description_me = $this->config->get('quickorder_description_me');
    $subject_me = $this->getCustomFields($data, $quickorder_subject_me[$data['language_id']]['text']);
    if ((strlen(utf8_decode($subject_me)) > 5)) {
      $subject = $subject_me;
    } else {
      $subject = $this->language->get('subject');
    }
    $html = $this->getCustomFields($data, $quickorder_description_me[$data['language_id']]['text']) . "\n";
    $on_off_html_product_me = $this->config->get('config_me_html_products');
    if ($on_off_html_product_me == '1') {
      $html .= $this->load->view('mail/quickorder', $data);

    }

    $mail = new Mail($this->config->get('config_mail_engine'));
    $mail->parameter = $this->config->get('config_mail_parameter');
    $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
    $mail->smtp_username = $this->config->get('config_mail_smtp_username');
    $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
    $mail->smtp_port = $this->config->get('config_mail_smtp_port');
    $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

    $mail->setTo($this->config->get('config_you_email_quickorder'));
    $mail->setFrom($this->config->get('config_email'));
    $mail->setSender(html_entity_decode($data['store_name'], ENT_QUOTES, 'UTF-8'));
    $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
    $mail->setHtml(html_entity_decode($html, ENT_QUOTES, 'UTF-8'));
    $mail->setText($text);
    $mail->send();
  }

  public function editCartQuick()
  {
    if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $this->load->language('checkout/cart');
      $this->load->model('tool/image');

      $json = array();

      // Update
      if (isset($this->request->post['quantity']) && isset($this->request->post['key'])) {

        $this->cart->update($this->request->post['key'], $this->request->post['quantity']);

        // Unset all shipping and payment methods
        unset($this->session->data['shipping_method']);
        unset($this->session->data['shipping_methods']);
        unset($this->session->data['payment_method']);
        unset($this->session->data['payment_methods']);

        // Totals
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
          'totals' => &$totals,
          'taxes' => &$taxes,
          'total' => &$total
        );

        // Display prices
        if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
          $sort_order = array();

          $results = $this->model_setting_extension->getExtensions('total');

          foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
          }

          array_multisort($sort_order, SORT_ASC, $results);

          foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
              $this->load->model('extension/total/' . $result['code']);

              // We have to put the totals in an array so that they pass by reference.
              $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
          }

          $sort_order = array();

          foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
          }

          array_multisort($sort_order, SORT_ASC, $totals);
        }
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    } else {
      $this->response->redirect($this->url->link('error/not_found', '', true));
    }
  }
}

?>
