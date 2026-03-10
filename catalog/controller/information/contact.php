<?php

class ControllerInformationContact extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->language('information/contact');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $mail = new Mail($this->config->get('config_mail_engine'));
      $mail->parameter = $this->config->get('config_mail_parameter');
      $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
      $mail->smtp_username = $this->config->get('config_mail_smtp_username');
      $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
      $mail->smtp_port = $this->config->get('config_mail_smtp_port');
      $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

      $mail->setTo($this->config->get('config_email'));
      $mail->setFrom($this->request->post['email']);
      $mail->setReplyTo($this->request->post['email']);
      $mail->setSender(html_entity_decode($this->request->post['name'], ENT_QUOTES, 'UTF-8'));
      $mail->setSubject(html_entity_decode(sprintf($this->language->get('email_subject'), $this->request->post['name'] . ' (' . $this->request->post['email'] . ')'), ENT_QUOTES, 'UTF-8'));
      $mail->setText($this->request->post['enquiry']);
      $mail->send();

      $this->response->redirect($this->url->link('information/contact/success'));
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('information/contact')
    );

    if (isset($this->error['name'])) {
      $data['error_name'] = $this->error['name'];
    } else {
      $data['error_name'] = '';
    }

    if (isset($this->error['email'])) {
      $data['error_email'] = $this->error['email'];
    } else {
      $data['error_email'] = '';
    }

    if (isset($this->error['enquiry'])) {
      $data['error_enquiry'] = $this->error['enquiry'];
    } else {
      $data['error_enquiry'] = '';
    }

    $data['button_submit'] = $this->language->get('button_submit');

    $data['action'] = $this->url->link('information/contact', '', true);

    $this->load->model('tool/image');
    $this->load->model('setting/setting');

    if (isset($this->session->data['language'])) {
      $langId = $this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];
    }

    $data['heading_title'] = $this->language->get('heading_title');
    $meta_h1 = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_contact_h1_title');
    if (!empty($meta_h1[$langId]['value'])){
      $data['heading_title'] = $meta_h1[$langId]['value'];
    }

    $this->document->setTitle($data['heading_title']);
    $meta_title = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_contact_meta_title');
    if (!empty($meta_title[$langId]['value'])) {
      $this->document->setTitle($meta_title[$langId]['value']);
    }

    $meta_desc = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_contact_meta_description');
    if (!empty($meta_desc[$langId]['value']))
    $this->document->setDescription($meta_desc[$langId]['value']);

    //++Andrey
    $data['map'] = $this->load->controller('information/map');

    $this->load->model('extension/module/ocwarehouses');
    $results = $this->model_extension_module_ocwarehouses->getWarehouseList();
    foreach ($results as $result) {

      if (!empty($result['image']) ){
        $image = $this->model_tool_image->resize($result['image'], 150, 150);
      }else{
        $image = $this->model_tool_image->resize("no_image.png", 150, 150);
      }

      $data['warehouses'][] = array(
        'warehouse_id'    => $result['warehouse_id'],
        'uid'             => $result['uid'],
        'name'            => $result['name'],
        'phone'           => $result['phone'],
        'address'         => nl2br($result['address']),
        'working_hours'   => nl2br(html_entity_decode($result['working_hours'])),
        'image'           => $image,
        'status'          => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'))
      );
    }

    $data['locations'] = array();

    $this->load->model('localisation/location');

    foreach ((array)$this->config->get('config_location') as $location_id) {
      $location_info = $this->model_localisation_location->getLocation($location_id);

      if ($location_info) {
        if ($location_info['image']) {
          $image = $this->model_tool_image->resize($location_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_location_height'));
        } else {
          $image = false;
        }

        $data['locations'][] = array(
          'location_id' => $location_info['location_id'],
          'name' => $location_info['name'],
          'address' => nl2br($location_info['address']),
          'geocode' => $location_info['geocode'],
          'telephone' => $location_info['telephone'],
          'fax' => $location_info['fax'],
          'image' => $image,
          'open' => nl2br(html_entity_decode($location_info['open'])),
          'comment' => $location_info['comment']
        );
      }
    }

    if (isset($this->request->post['name'])) {
      $data['name'] = $this->request->post['name'];
    } else {
      $data['name'] = $this->customer->getFirstName();
    }

    if (isset($this->request->post['email'])) {
      $data['email'] = $this->request->post['email'];
    } else {
      $data['email'] = $this->customer->getEmail();
    }

    if (isset($this->request->post['enquiry'])) {
      $data['enquiry'] = $this->request->post['enquiry'];
    } else {
      $data['enquiry'] = '';
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
      $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
    } else {
      $data['captcha'] = '';
    }

    $data['config_address'] = $this->config->get('config_address');

    $data['telephone'] = $this->config->get('config_telephone');
    $data['telephone_1'] = $this->config->get('config_telephone_1');
    $data['telephone_2'] = $this->config->get('config_telephone_2');
    $data['telephone_3'] = $this->config->get('config_telephone_3');
    $data['open'] = nl2br(html_entity_decode($this->config->get('config_open')));

    $data['email_store'] = $this->config->get('config_email');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('information/contact', $data));
  }

  protected function validate()
  {
    if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
      $this->error['name'] = $this->language->get('error_name');
    }

    if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
      $this->error['email'] = $this->language->get('error_email');
    }

    if ((utf8_strlen($this->request->post['enquiry']) < 10) || (utf8_strlen($this->request->post['enquiry']) > 3000)) {
      $this->error['enquiry'] = $this->language->get('error_enquiry');
    }

    // Captcha
    if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('contact', (array)$this->config->get('config_captcha_page'))) {
      $captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

      if ($captcha) {
        $this->error['captcha'] = $captcha;
      }
    }

    return !$this->error;
  }

  public function success()
  {
    $this->load->language('information/contact');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('information/contact')
    );

    $data['text_message'] = $this->language->get('text_message');

    $data['continue'] = $this->url->link('common/home');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('account/success', $data));
  }


}
