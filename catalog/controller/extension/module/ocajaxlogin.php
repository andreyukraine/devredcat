<?php

class ControllerExtensionModuleOcajaxlogin extends Controller
{

  public function index()
  {

    $this->load->language('extension/module/ocajaxlogin'); // loads the language file of Ajax Login

    $enable_status = $this->config->get('module_ocajaxlogin_status');
    if ($enable_status == '1') {
      $data['enable_status'] = true;
    } else {
      $data['enable_status'] = false;
    }

    $enable_redirect = $this->config->get('module_ocajaxlogin_redirect_status');
    if ($enable_redirect == '1') {
      $data['enable_redirect'] = true;
    } else {
      $data['enable_redirect'] = false;
    }

    $loader_img = $this->config->get('module_ocajaxlogin_loader_img');
    if ($loader_img) {
      $data['loader_img'] = $this->config->get('config_url') . 'image/' . $loader_img;
    }

    $data['heading_title'] = $this->language->get('heading_title'); // set the heading_title of the module
    $data['heading_title_reg'] = $this->language->get('heading_title_reg');
    $data['heading_title_type_client'] = $this->language->get('heading_title_type_client');
    $data['heading_title_fogotten'] = $this->language->get('heading_title_fogotten');
    $data['text_login'] = $this->language->get('text_login');
    $data['text_register'] = $this->language->get('text_register');
    $data['text_logout'] = $this->language->get('text_logout');

    $data['ajax_login_content'] = $this->load->controller('extension/module/ajaxlogin');
    $data['ajax_fogotten_content'] = $this->load->controller('extension/module/ajaxfogotten');

    $data['ajax_register_content'] = $this->load->controller('extension/module/ajaxregister');
    $data['ajax_typeclient_content'] = $this->load->controller('extension/module/ajaxtypeclient');


    $data['ajax_logoutsuccess_content'] = $this->load->controller('extension/module/ajaxlogin/logoutsuccess');

    $data['ajax_approved_content'] = $this->load->controller('extension/module/ajaxregister/approved');
    $data['heading_title_approved'] = $this->language->get('text_success_title');

    $data['logged'] = $this->customer->isLogged();
    if ($data['logged']) {
      $data['ajax_success_content'] = $this->load->controller('extension/module/ajaxregister/success');
      $data['heading_title'] = sprintf($this->language->get('text_title_user'), $this->customer->getFirstName());
    }

    $this->load->model('tool/image');
    $img_bg = "account_login_bg.jpg";

    if (file_exists(DIR_IMAGE . $img_bg)) {
      $data["image"] = $this->model_tool_image->resize($img_bg, 1000, 1000);
    } else {
      $data["image"] = $this->model_tool_image->resize("no_image.png", 1000, 1000);
    }

    return $this->load->view('extension/module/ocajaxlogin/ocajaxlogin', $data);
  }

  public function appendcontainer()
  {
    $this->response->setOutput($this->index());
  }
}
