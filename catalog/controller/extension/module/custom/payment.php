<?php

class ControllerExtensionModuleCustomPayment extends Controller
{
  public function index($setting = array())
  {

    $data = array();

    if (empty($setting)) {
      $setting = $this->model_setting_setting->getSetting('module_custom');
    }

    $this->load->language('extension/module/custom/payment');

    // Група клієнтів
    if ($this->customer->isLogged()) {
      $data['customer_group_id'] = $this->customer->getGroupId();
    } else {
      $data['customer_group_id'] = $this->config->get('config_customer_group_id');
    }

    $order_id = 0;
    if (!empty($setting['order_id'])) {
      $order_id = $setting['order_id'];
    }

    if ($order_id == 0){
      if (!empty($this->config->get('config_warehouse_id'))) {
        $order_id = (int)$this->config->get('config_warehouse_id');
      }
    }

    $data['order_id'] = $order_id;

    $data['city_ref'] = $this->session->data['shipping_method'][$order_id]['city_ref'] 
                      ?? $this->session->data['custom_city']['city_ref'] 
                      ?? '';

    // Синхронізуємо city_ref в сесію доставки, або очищаємо, якщо його немає
    if (isset($this->session->data['custom_city']['city_ref']) && !empty($this->session->data['custom_city']['city_ref'])) {
      $data['city_ref'] = $this->session->data['custom_city']['city_ref'];
      $this->session->data['shipping_method'][$order_id]['city_ref'] = $data['city_ref'];
    } else {
      $data['city_ref'] = '';
      if (isset($this->session->data['shipping_method'][$order_id])) {
        $this->session->data['shipping_method'][$order_id]['city_ref'] = '';
      }
    }

    $data['has_address'] = isset($this->session->data['client_address_id']);

    // Мова
    $this->load->model('localisation/language');
    $langId = (int)$this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];
    $data['lang'] = $langId;

    // Методи оплати
    $method_data =  $this->getPaymentMethodList($order_id);

    $data['has_ourdelivery'] = false;
    $shipping_methods = $this->session->data['shipping_methods'] ?? [];
    foreach ($shipping_methods as $method) {
      if (isset($method['code']) && $method['code'] == 'ourdelivery') {
        $data['has_ourdelivery'] = true;
        break;
      }
    }

    if (!isset($this->session->data['payment_method']) || !is_array($this->session->data['payment_method'])) {
      $this->session->data['payment_method'] = array();
    }

    $current_payment_code = $this->session->data['payment_method'][$order_id]['code'] ?? '';

    if (empty($current_payment_code) || !isset($method_data[$current_payment_code])) {
      foreach ($method_data as $code => $item) {
        if ($item['default']) {
          $this->session->data['payment_method'][$order_id] = $item;
          $this->session->data['payment_method'][$order_id]['code'] = $code;
          break;
        }
      }
    }

    unset($this->session->data['payment_methods']);
    $this->session->data['payment_methods'] = $method_data;
    $data['payment_methods'] = $method_data;

    $data['heading_payment'] = $this->language->get('heading_payment');

    if (empty($this->session->data['payment_methods'])) {
      $data['error_warning'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
    } else {
      $data['error_warning'] = '';
    }

    $data['scripts'] = $this->document->getScripts();

    return $this->load->view('extension/module/custom/payment', $data);
  }

  public function update()
  {
    $json = array();

    $order_id = 0;
    if (isset($this->request->post['order_id'])) {
      $order_id = (int)$this->request->post['order_id'];
    } elseif (isset($this->request->get['order_id'])) {
      $order_id = (int)$this->request->get['order_id'];
    }

    $payment_code = isset($this->request->post['payment_code']) ? $this->request->post['payment_code'] : (isset($this->request->get['payment_code']) ? $this->request->get['payment_code'] : "");

    if ($payment_code && isset($this->session->data['payment_methods'][$payment_code])) {
      $this->session->data['payment_method'][$order_id] = $this->session->data['payment_methods'][$payment_code];
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function save()
  {

    $json = array();

    $this->load->language('extension/module/custom/payment');

    if ($this->config->get('config_checkout_id')) {
      $this->load->model('catalog/information');

      $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

      if ($information_info && !isset($this->request->post['agree'])) {
        $json['error']['agree'] = sprintf($this->language->get('error_agree'), $information_info['title']);
      }
    }

    $json['session'] = "";

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  private function getPaymentMethodList($order_id = 0)
  {

    $method_data = array();

    $check_delivery_setting = null;
    $this->load->model('setting/setting');
    $delivery_setting = $this->model_setting_setting->getSetting('module_custom');

    if (isset($delivery_setting['module_custom_shipping']['methods'])) {
      foreach ($delivery_setting['module_custom_shipping']['methods'] as $key => $delivery) {
        if (isset($this->session->data['shipping_method'][$order_id])) {
          if ($delivery['code'] == $this->session->data['shipping_method'][$order_id]['code']) {
            $check_delivery_setting = $delivery['payments'] ?? array();
            break;
          }
        } else {
          if ($key == 0) {
            $check_delivery_setting = $delivery['payments'] ?? array();
            break;
          }
        }
      }
    }

    $default = false;
    $payment_methods = $delivery_setting['module_custom_payment']['methods'] ?? array();
    $customer_group_id = (int)$this->customer->getGroupId();

    if (is_array($check_delivery_setting)) {
      foreach ($check_delivery_setting as $method_code) {
        foreach ($payment_methods as $payment_method) {
          if ($payment_method['code'] == $method_code){
            if (isset($payment_method['customer_group']) && !empty($payment_method['customer_group']) && !in_array($customer_group_id, $payment_method['customer_group'])) {
              continue;
            }
            $payment_method['default'] = false;
            $this->load->model('extension/payment/' . $method_code);
            $method_data[$payment_method['code']] = $payment_method;
          }
        }
      }
    } else {
      foreach ($payment_methods as $payment_method) {
        if (isset($payment_method['customer_group']) && !empty($payment_method['customer_group']) && !in_array($customer_group_id, $payment_method['customer_group'])) {
          continue;
        }
        $payment_method['default'] = false;
        $this->load->model('extension/payment/' . $payment_method['code']);
        $method_data[$payment_method['code']] = $payment_method;
      }
    }

    $sort_order = array();
    foreach ($method_data as $key => $value) {
      $sort_order[$key] = $value['sort_order'] ?? 0;
    }
    array_multisort($sort_order, SORT_ASC, $method_data);

    // Set default to the first one
    if (!empty($method_data)) {
      reset($method_data);
      $first_key = key($method_data);
      $method_data[$first_key]['default'] = true;
    }

    return $method_data;

  }

}
