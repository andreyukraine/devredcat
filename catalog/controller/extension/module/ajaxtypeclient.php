<?php

class ControllerExtensionModuleAjaxTypeClient extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->language('extension/module/ajaxregister');

    $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
    $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
    $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
    $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

    $this->load->model('account/customer');

    $data['heading_title'] = "Виберіть тип облікового запису";

    $data['text_account_already'] = $this->language->get('text_account_already');

    $data['button_continue'] = $this->language->get('button_continue');
    $data['button_upload'] = $this->language->get('button_upload');

    $data['action'] = $this->url->link('extension/module/ajaxtypeclient', '', true);

    $data['customer_groups'] = array();

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
      $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
    } else {
      $data['captcha'] = '';
    }

    $loader_img = $this->config->get('module_ocajaxlogin_loader_img');
    if ($loader_img) {
      $data['loader_img'] = $this->config->get('config_url') . 'image/' . $loader_img;
    }

    return $this->load->view('extension/module/ocajaxlogin/ajaxtypeclient', $data);
  }

  public function register()
  {
    $this->load->model('account/customer');
    $this->load->language('extension/module/ajaxregister');
    $json = array();
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function tohtml()
  {
    $this->response->setOutput($this->index());
  }


}
