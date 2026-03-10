<?php

class ControllerExtensionModuleCustom extends Controller
{
  public function index()
  {

    $this->load->model('setting/setting');
    $setting = $this->model_setting_setting->getSetting('module_custom');

    $data['logged'] = $this->customer->isLogged();

    if ($data['logged'] && !isset($this->session->data['client_address_id'])) {
      $this->session->data['client_address_id'] = $this->customer->getAddressId();
      $this->session->data['shipping_address'] = $this->customer->getAddressId();
    }

    $data['show_login_popup'] = false;
    $data['url_link_extension_module_custom'] = $this->url->link('extension/module/custom', '', true);
    if ((int)$setting['module_custom_login']['status'] == 0) {
      if (!$data['logged'] && $this->cart->countProducts() > 0) {
        $this->session->data['redirect'] = $data['url_link_extension_module_custom'];
        $data['show_login_popup'] = true;
      }
    }

    //Скидання історії при вході в модуль оформлення
    unset($this->session->data['orders']);
    unset($this->session->data['orders_history']);

    $this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    $this->response->addHeader('Pragma: no-cache');
    $this->response->addHeader('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    $this->document->addStyle('catalog/view/javascript/custom/stylesheet.css');

    $this->load->language('extension/module/custom/module');


    $data['button_continue'] = $this->language->get('button_continue');
    $data['text_loading'] = $this->language->get('text_loading');
    $data['checkout_title'] = $this->language->get('checkout_title');

    $data['breadcrumbs'] = array();
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );
    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('checkout_title'),
      'href' => $this->url->link('extension/module/custom', '', true)
    );

    $this->document->setTitle($this->language->get('checkout_title'));

    $data['buy_org'] = false;

    if ($this->config->get('module_custom_status')) {

      if ($this->cart->countProducts() > 0) {

        $this->load->model('extension/module/custom/custom');

        $errors = $this->model_extension_module_custom_custom->validate();

        // Подгружаем настройки
        if ($setting['module_custom_status'] && !empty($errors)) {
          $data['errors'] = $errors;
        }

        if ($setting['module_custom_status']) {

          $data['login'] = $this->getChildController('login', $setting['module_custom_login']);
          $data['customer'] = $this->getChildController('customer', $setting['module_custom_customer']);

          if (isset($this->session->data['client_address_id'])) {
            $this->load->model('account/address');
            $select_adress = $this->model_account_address->getAddress($this->session->data['client_address_id']);

            if ($select_adress) {
              if ((int)$select_adress['customer_type'] > 0) {
                $data['buy_org'] = true;
                $data['orders_org'] = $this->getChildController('orders_org', $setting);
                $data['shipping'] = $this->getChildController('shipping', $setting['module_custom_shipping']);
                $data['payment'] = $this->getChildController('payment', $setting['module_custom_payment']);
                $data['comment'] = $this->getChildController('comment', $setting['module_custom_comment']);
              }else{
                $data['cart'] = $this->getChildController('cart', $setting['module_custom_cart']);
                $data['shipping'] = $this->getChildController('shipping', $setting['module_custom_shipping']);
                $data['payment'] = $this->getChildController('payment', $setting['module_custom_payment']);
                $data['comment'] = $this->getChildController('comment', $setting['module_custom_comment']);
              }
            }else{
              $data['cart'] = $this->getChildController('cart', $setting['module_custom_cart']);
              $data['shipping'] = $this->getChildController('shipping', $setting['module_custom_shipping']);
              $data['payment'] = $this->getChildController('payment', $setting['module_custom_payment']);
              $data['comment'] = $this->getChildController('comment', $setting['module_custom_comment']);
            }
          } else {
            $data['cart'] = $this->getChildController('cart', $setting['module_custom_cart']);
            $data['shipping'] = $this->getChildController('shipping', $setting['module_custom_shipping']);
            $data['payment'] = $this->getChildController('payment', $setting['module_custom_payment']);
            $data['comment'] = $this->getChildController('comment', $setting['module_custom_comment']);
          }

          $data['split_order_store'] = $setting['module_custom_split_order_store'];
          $data['order_by_store'] = $setting['module_custom_check_qty_cart_by_store'];

          $data['module'] = $this->getChildController('module', $setting['module_custom_module']);
          $data['total'] = $this->getChildController('total', $setting['module_custom_total']);
        }

      } else {
        unset($this->session->data['orders']);
        unset($this->session->data['shipping_method']);
        unset($this->session->data['payment_method']);
        $data['empty'] = $this->language->get('entry_empty');
      }

    } elseif ($this->config->get('module_custom_status') /*&& !$time['status']*/) {

    } else {
      $data['errors'][] = $this->language->get('error_module_off');
    }

    $data['button'] = $this->load->controller('extension/module/custom/button');

    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');

    if (isset($this->session->data['fastorder'])) {
      $this->session->data['fastorder'] = null;
    }

    $this->response->setOutput($this->load->view('extension/module/custom', $data));
  }

  public function getChildController($name, $setting)
  {
    return $this->load->controller('extension/module/custom/' . $name, $setting);
  }

  public function render()
  {
    if (isset($this->request->get['block'])) {
      $this->load->model('setting/setting');
      $setting = json_decode($this->model_setting_setting->getSettingValue('module_custom_' . $this->request->get['block']), true);

      if (isset($this->request->get['order_id'])) {
        $setting['order_id'] = $this->request->get['order_id'];
      }

      $this->response->setOutput($this->load->controller('extension/module/custom/' . $this->request->get['block'], $setting));
    }
  }

}
