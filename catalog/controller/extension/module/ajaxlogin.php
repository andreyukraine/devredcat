<?php

class ControllerExtensionModuleAjaxlogin extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->model('account/customer');

    $this->load->language('checkout/cart');
    $this->load->language('extension/module/ajaxlogin');
    $this->load->language('account/card');

    $data['heading_title'] = $this->language->get('heading_title');

    $data['text_new_customer'] = $this->language->get('text_new_customer');
    $data['text_register'] = $this->language->get('text_register');
    $data['text_register_account'] = $this->language->get('text_register_account');
    $data['text_returning_customer'] = $this->language->get('text_returning_customer');
    $data['text_i_am_returning_customer'] = $this->language->get('text_i_am_returning_customer');
    $data['text_forgotten'] = $this->language->get('text_forgotten');

    $data['text_sum'] = $this->language->get('text_sum');
    $data['text_phone'] = $this->language->get('text_phone');
    $data['text_code'] = $this->language->get('text_code');
    $data['heading_title_card'] = $this->language->get('heading_title_card');

    $data['entry_email'] = $this->language->get('entry_email');
    $data['entry_password'] = $this->language->get('entry_password');
    $data['text_logout'] = $this->language->get('text_logout');

    $data['account'] = $this->url->link('account/account', '', true);

    $data['button_continue'] = $this->language->get('button_continue');
    $data['button_login'] = $this->language->get('button_login');
    $data['button_register_link'] = $this->language->get('button_register_link');

    if (isset($this->session->data['error'])) {
      $data['error_warning'] = $this->session->data['error'];

      unset($this->session->data['error']);
    } elseif (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    $data['logged'] = $this->customer->isLogged();

//    $this->load->model('setting/module');
//    $ocsociallogin_setting = json_decode($this->model_setting_module->getModulesByCode("ocsociallogin_module")['setting'], true);
//    $data['social_login'] = $this->load->controller('extension/module/ocsociallogin', $ocsociallogin_setting);


    $data['action'] = $this->url->link('extension/module/ajaxlogin/login', '', true);
    $data['register'] = $this->url->link('account/register', '', true);
    $data['forgotten'] = $this->url->link('account/forgotten', '', true);

    // Added strpos check to pass McAfee PCI compliance test (http://forum.opencart.com/viewtopic.php?f=10&t=12043&p=151494#p151295)
    if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
      $data['redirect'] = $this->request->post['redirect'];
    } elseif (isset($this->session->data['redirect'])) {
      $data['redirect'] = $this->session->data['redirect'];

      unset($this->session->data['redirect']);
    } else {
      $data['redirect'] = '';
    }

    if (empty($data['redirect']) && isset($this->request->server['HTTP_REFERER'])) {
        if (strpos($this->request->server['HTTP_REFERER'], 'route=account/logout') !== false || $this->request->server['HTTP_REFERER'] == $this->url->link('account/logout', '', true)) {
            $data['redirect'] = $this->url->link('account/account', '', true);
        }
    }

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    if (isset($this->request->post['email'])) {
      $data['email'] = $this->request->post['email'];
    } else {
      $data['email'] = '';
    }

    if (isset($this->request->post['password'])) {
      $data['password'] = $this->request->post['password'];
    } else {
      $data['password'] = '';
    }

    $loader_img = $this->config->get('module_ocajaxlogin_loader_img');
    if ($loader_img) {
      $data['loader_img'] = $this->config->get('config_url') . 'image/' . $loader_img;
    }

    $data['telephone'] = $this->customer->getTelephone();
    $data['card_number'] = "";
    $data['text_discont_card'] = "";
    $data['card_code_qr'] = "";

    $data['card_code'] = false;
    $data['sum'] = 0;
    $data['percent'] = 0;

    if ($this->config->get('config_tax_customer') == 'shipping') {
      if (!isset($this->session->data['shipping_address'])) {
        $this->session->data['shipping_address'] = $this->customer->getAddressId();
      }
      if (!isset($this->session->data['client_address_id'])) {
        $this->session->data['client_address_id'] = $this->customer->getAddressId();
      }

      $data['select_address'] = $this->session->data['client_address_id'];
    }

    $this->load->model('account/customer_group');
    $this->load->model('account/address');
    $address_default = $this->model_account_address->getAddress($data['select_address']);

    if ($address_default) {

      $data['card_code_qr'] = $address_default['guid'];

      $data['card_code'] = $this->customer->getId();
      $data['text_discont_card'] = $address_default['firstname'];

      $data['price_groups'] = $this->model_account_customer->getPriceGroups($data['select_address'], $this->customer->getId());
    }else{
      $data['card_code'] = $this->customer->getId();
    }
    $data['customer_group_name'] = $this->model_account_customer_group->getCustomerGroup($this->customer->getGroupId());

    return $this->load->view('extension/module/ocajaxlogin/ajaxlogin', $data);
  }

  public function logout()
  {
    $this->load->model('account/customer');
    $this->load->language('checkout/cart');
    $this->load->language('account/wishlist');

    $json = array();

    if ($this->customer->isLogged()) {
      $this->customer->logout();

      unset($this->session->data['shipping_address']);
      unset($this->session->data['client_address_id']);
      unset($this->session->data['shipping_method']);
      unset($this->session->data['shipping_methods']);
      unset($this->session->data['payment_address']);
      unset($this->session->data['payment_method']);
      unset($this->session->data['payment_methods']);
      unset($this->session->data['comment']);
      unset($this->session->data['order_id']);
      unset($this->session->data['coupon']);
      unset($this->session->data['reward']);
      unset($this->session->data['voucher']);
      unset($this->session->data['vouchers']);

      /* Update Cart Total */
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
      }
      /* End update cart */

      if (!$json) {
        $json['wishlist_total'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
        $json['cart_total'] = $this->cart->countProducts();
        $json['redirect'] = $this->url->link('common/home');
      }

    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function logoutsuccess()
  {
    $this->load->language('account/logout');

    $data['heading_title'] = $this->language->get('heading_title');

    $data['text_message'] = $this->language->get('text_message');

    $data['button_continue'] = $this->language->get('button_continue');

    return $this->load->view('extension/module/ocajaxlogin/success', $data);
  }

  public function login()
  {
    $this->load->model('account/customer');
    $this->load->language('checkout/cart');
    $this->load->language('account/wishlist');
    $this->load->language('extension/module/ajaxlogin');

    $enable_redirect = $this->config->get('module_ocajaxlogin_redirect_status');

    $json = array();

    // Login override for admin users
    if (!empty($this->request->get['token'])) {
      $this->customer->logout();
      $this->cart->clear();

      unset($this->session->data['order_id']);
      unset($this->session->data['payment_address']);
      unset($this->session->data['payment_method']);
      unset($this->session->data['payment_methods']);
      unset($this->session->data['shipping_address']);
      unset($this->session->data['client_address_id']);
      unset($this->session->data['shipping_method']);
      unset($this->session->data['shipping_methods']);
      unset($this->session->data['comment']);
      unset($this->session->data['coupon']);
      unset($this->session->data['reward']);
      unset($this->session->data['voucher']);
      unset($this->session->data['vouchers']);

      $customer_info = $this->model_account_customer->getCustomerByToken($this->request->get['token']);

      if ($customer_info && $this->customer->login($customer_info['email'], '', true)) {
        // Default Addresses
        $this->load->model('account/address');

        if ($this->config->get('config_tax_customer') == 'payment') {
          $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        }

        if ($this->config->get('config_tax_customer') == 'shipping') {
          $this->session->data['shipping_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
        }

        $this->response->redirect($this->url->link('account/account', '', true));
      }
    }

    $this->document->setTitle($this->language->get('heading_title'));

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      // Unset guest
      if (isset($this->session->data['guest'])) {
        unset($this->session->data['guest']);
      }

      // Wishlist
      if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
        $this->load->model('account/wishlist');

        foreach ($this->session->data['wishlist'] as $key => $product_id) {
          $this->model_account_wishlist->addWishlist($product_id);

          unset($this->session->data['wishlist'][$key]);
        }
      }

      //++Andrey
      $customer_id = (int)$this->customer->getId();
      if ($customer_id > 0) {

          // Default Addresses
          $this->load->model('account/address');

          if ($this->config->get('config_tax_customer') == 'payment') {
            $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
          }

          if ($this->config->get('config_tax_customer') == 'shipping') {
            $default_address = $this->model_account_address->getAddress($this->customer->getAddressId());
            if ($default_address != null){
              $this->session->data['shipping_address'] = (int)$default_address['address_id'];
              $this->session->data['client_address_id'] = (int)$default_address['address_id'];
            }
          }

      }

      $json['success'] = true;
      $json['wishlist_total'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
      $json['cart_total'] = $this->cart->countProducts();
      $json['success_message'] = $this->language->get('success_message');

      if (isset($this->request->post['redirect']) && $this->request->post['redirect']) {
        $json['redirect'] = $this->request->post['redirect'];
      } elseif (isset($this->session->data['redirect'])) {
        $json['redirect'] = $this->session->data['redirect'];
      }

      if (isset($json['redirect']) && $json['redirect'] == $this->url->link('account/logout', '', true)) {
          $json['redirect'] = $this->url->link('account/account', '', true);
      }

      if (empty($json['redirect']) && isset($this->request->server['HTTP_REFERER'])) {
          if (strpos($this->request->server['HTTP_REFERER'], 'route=account/logout') !== false || $this->request->server['HTTP_REFERER'] == $this->url->link('account/logout', '', true)) {
              $json['redirect'] = $this->url->link('account/account', '', true);
          }
      }

    } else {
      if (!$json) {
        $json['success'] = false;
      }
    }

    if (isset($this->error['warning'])) {
      $json['error_warning'] = $this->error['warning'];
    } else {
      $json['error_warning'] = '';
    }

    if (isset($this->error['email'])) {
      $json['error_email'] = $this->error['email'];
    } else {
      $json['error_email'] = '';
    }

    if (isset($this->error['password'])) {
      $json['error_password'] = $this->error['password'];
    } else {
      $json['error_password'] = '';
    }

    if (!$json) {
      $json['redirect'] = $this->url->link('account/account', '', true);
      if ($enable_redirect == '1') {
        $json['enable_redirect'] = true;
      } else {
        $json['enable_redirect'] = false;
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  protected function validate()
  {
    /// Check how many login attempts have been made.
    $login_info = $this->model_account_customer->getLoginAttempts($this->request->post['email']);

//    if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
//      $this->error['warning'] = $this->language->get('error_attempts');
//    }

    if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL) || empty($this->request->post['email'])) {
      $this->error['email'] = $this->language->get('error_email');
    }

    if (empty($this->request->post['password'])){
      $this->error['password'] = $this->language->get('error_password');
    }

    // Check if customer has been approved.
    $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

    if ($customer_info && !$customer_info['status']) {
      $this->error['warning'] = $this->language->get('error_approved');
    }

    if (!$this->error) {
      if (!$this->customer->login($this->request->post['email'], $this->request->post['password'])) {
        $this->error['warning'] = $this->language->get('error_email_or_pass');

        $this->model_account_customer->addLoginAttempt($this->request->post['email']);
      } else {

        $this->model_account_customer->deleteLoginAttempts($this->request->post['email']);
      }
    }

    return !$this->error;
  }

  public function tohtml()
  {
    $this->response->setOutput($this->index());
  }

  public function toheaderhtml()
  {
    $this->response->setOutput($this->load->controller('common/header'));
  }
}
