<?php

class ControllerExtensionModuleAjaxfogotten extends Controller
{
  private $error = array();

  public function index()
  {

    $this->load->language('account/forgotten');
    $this->document->setTitle($this->language->get('heading_title'));
    $data['action'] = $this->url->link('extension/module/ajaxfogotten/index', '', true);

    $loader_img = $this->config->get('module_ocajaxlogin_loader_img');
    if ($loader_img) {
      $data['loader_img'] = $this->config->get('config_url') . 'image/' . $loader_img;
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
      $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
    } else {
      $data['captcha'] = '';
    }

    return $this->load->view('extension/module/ocajaxlogin/ajaxforgotten', $data);
  }

  public function fogotten(){

    $json = array();

    $json['success'] = false;

    $this->load->language('account/forgotten');
    $this->load->model('account/customer');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_account_customer->editCode($this->request->post['email'], token(40));

      $this->session->data['success'] = $this->language->get('text_success');
      $json['text_success'] = $this->language->get('text_success');
      $json['success'] = true;
    }

    if (isset($this->request->post['email'])) {
      $json['error_email'] = $this->error['email'];
    } else {
      $json['error_email'] = '';
    }

    if (isset($this->error['warning'])) {
      $json['error_warning'] = $this->error['warning'];
    } else {
      $json['error_warning'] = '';
    }

    if (isset($this->error['captcha'])) {
      $json['error_captcha'] = $this->error['captcha'];
    } else {
      $json['error_captcha'] = '';
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  protected function validate() {
    if (!isset($this->request->post['email'])) {
      $this->error['email'] = $this->language->get('error_email');
    } elseif (!$this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
      $this->error['email'] = $this->language->get('error_email');
    }

    // Check if customer has been approved.
    $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
    if ($customer_info && !$customer_info['status']) {
      $this->error['email'] = $this->language->get('error_approved');
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
      $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

      if ($captcha) {
        $this->error['captcha'] = $captcha;
      }
    }

    return !$this->error;
  }

}
