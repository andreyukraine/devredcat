<?php

class ControllerExtensionModuleOcsociallogin extends Controller
{
  var $success;
  var $user;
  var $provider;

  public function index($setting)
  {

    $this->language->load('ocsociallogin/ocsociallogin');
    $this->load->model('setting/setting');
    $this->load->model('account/customer');
    $this->load->model('design/layout');
    $this->load->model('ocsociallogin/ocsociallogin');

    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_login_via'] = $this->language->get('text_login_via');
    $data['error_facebook_login'] = $this->language->get('error_facebook_login');
    $data['error_google_login'] = $this->language->get('error_google_login');
    $data['error_linkedin_login'] = $this->language->get('error_linkedin_login');
    $data['error_live_login'] = $this->language->get('error_live_login');
    $data['error_twitter_login'] = $this->language->get('error_twitter_login');
    $data['error_yahoo_login'] = $this->language->get('error_yahoo_login');
    $data['error_amazon_login'] = $this->language->get('error_amazon_login');
    $data['error_instagram_login'] = $this->language->get('error_instagram_login');
    $data['error_paypal_login'] = $this->language->get('error_paypal_login');
    $data['login_via_facebook'] = $this->language->get('login_via_facebook');
    $data['login_via_google'] = $this->language->get('login_via_google');
    $data['login_via_linked'] = $this->language->get('login_via_linked');
    $data['login_via_live'] = $this->language->get('login_via_live');
    $data['login_via_twitter'] = $this->language->get('login_via_twitter');
    $data['login_via_yahoo'] = $this->language->get('login_via_yahoo');
    $data['login_via_amazon'] = $this->language->get('login_via_amazon');
    $data['login_via_instagram'] = $this->language->get('login_via_instagram');
    $data['login_via_paypal'] = $this->language->get('login_via_paypal');
    $data['login_with_google'] = $this->language->get('login_with_google');
    $data['login_with_linked'] = $this->language->get('login_with_linked');
    $data['login_with_live'] = $this->language->get('login_with_live');
    $data['login_with_twitter'] = $this->language->get('login_with_twitter');
    $data['login_with_yahoo'] = $this->language->get('login_with_yahoo');
    $data['login_with_amazon'] = $this->language->get('login_with_amazon');
    $data['login_with_instagram'] = $this->language->get('login_with_instagram');
    $data['login_with_paypal'] = $this->language->get('login_with_paypal');
    $data['login_with_facebook'] = $this->language->get('login_with_facebook');
    $current_langauge_id = $this->config->get('config_language_id');

    $result = $this->model_setting_setting->getSetting('ocsociallogin', $this->config->get('config_store_id'));

    $account_setting = $this->model_setting_setting->getSetting('ocsociallogin_setting', $this->config->get('config_store_id'));

    $data['buttons_info'] = array();


    $data['buttons_info'][] = array(
      'title' => $setting['title'][$current_langauge_id],
      'button_size' => 'small'
    );
    $data['status'] = $setting['status'];
    $data['title_status'] = $setting['title_status'];
    $data['title'] = $setting['title'][$current_langauge_id];
    $data['button_size'] = 'small';
    $data['button_layout'] = $setting['button_layout'];
    $data['button_layout_setting'] = $this->model_ocsociallogin_ocsociallogin->getIconLayout($data['button_layout']);

    if (isset($result['ocsociallogin_status'])) {
      $data['ocsociallogin_status'] = $result['ocsociallogin_status'];
    } else {
      $data['ocsociallogin_status'] = '0';
    }

    if (isset($result['ocsociallogin_include_font_awesome'])) {
      $data['ocsociallogin_include_font_awesome'] = $result['ocsociallogin_include_font_awesome'];
    } else {
      $data['ocsociallogin_include_font_awesome'] = '0';
    }

    $data['ocsociallogin_setting_facebook_status'] = '0';

    if (isset($account_setting['ocsociallogin_setting_google_status'])) {
      $data['ocsociallogin_setting_google_status'] = $account_setting['ocsociallogin_setting_google_status'];
    } else {
      $data['ocsociallogin_setting_google_status'] = '0';
    }

    $data['ocsociallogin_setting_linkedin_status'] = '0';

    $data['ocsociallogin_setting_live_status'] = '0';

    $data['ocsociallogin_setting_twitter_status'] = '0';

    $data['ocsociallogin_setting_yahoo_status'] = '0';

    $data['ocsociallogin_setting_instagram_status'] = '0';
    $data['ocsociallogin_setting_amazon_status'] = '0';
    $data['ocsociallogin_setting_paypal_status'] = '0';


    $this->template = 'ocsociallogin/login';

    if ($this->customer->isLogged()) {
      if (version_compare(VERSION, '2.2.0.0', '<')) {
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/ocsociallogin/blank.tpl')) {
          $this->template = $this->config->get('config_template') . '/template/ocsociallogin/blank.tpl';
        } else {
          $this->template = 'default/template/ocsociallogin/blank.tpl';
        }
      } else {
        $this->template = 'ocsociallogin/blank';
      }
    }

    $data['action'] = 'index.php?route=extension/module/ocsociallogin/loginProcess'; //Fix for Seo URL
    if (isset($this->session->data['error']) && $this->session->data['error'] != "") {
      $this->response->redirect($this->url->link("extension/module/ocsociallogin/error"));
    } else {
      return $this->load->view($this->template, $data);
    }
  }

  public function error()
  {

    if ($this->customer->isLogged()) {
      $this->response->redirect($this->url->link('common/home', '', 'SSL'));
      unset($this->session->data['error']);
    }

    $this->language->load('ocsociallogin/ocsociallogin');

    $data['heading_title'] = $this->language->get('heading_title_error');

    $this->document->setTitle($this->language->get('heading_title_error'));

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
      'separator' => false
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title_error'),
      'href' => $this->url->link('common/home'),
      'separator' => $this->language->get('text_separator')
    );

    $data['module_configuration_error'] = $this->language->get('module_configuration_error');

    if (isset($this->session->data['error']) && $this->session->data['error'] != "") {
      $data['error_warning'] = $this->session->data['error'];
    }
    unset($this->session->data['error']);

    if (version_compare(VERSION, '2.2.0.0', '<')) {
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/ocsociallogin/login_error.tpl')) {
        $this->template = $this->config->get('config_template') . '/template/ocsociallogin/login_error.tpl';
      } else {
        $this->template = 'default/template/ocsociallogin/login_error.tpl';
      }
    } else {
      $this->template = 'ocsociallogin/login_error';
    }

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view($this->template, $data));
  }

  public function loginProcess() {
    $this->load->model('setting/setting');
    $this->language->load('ocsociallogin/ocsociallogin');

    $json = array();

    if ($this->request->get['loginvia']) {
      include_once(DIR_SYSTEM . 'library/ocsociallogin/login.php');

      if ($this->request->get['loginvia'] == "google") {
        $this->provider = 'Google';
        $this->session->data['snslogin_provider'] = 'Google';

        // Замість перенаправлення повертаємо URL для модального вікна
        $redirectUrl = $this->url->link("ocsociallogin/redirect");
        $json['redirect_url'] = $redirectUrl;
      }
    }

    if (isset($this->session->data['error']) && $this->session->data['error'] != "") {
      $json['error'] = $this->session->data['error'];
    }

    // Повертаємо JSON замість перенаправлення
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

}
