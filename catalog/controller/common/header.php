<?php

class ControllerCommonHeader extends Controller
{
  private $error = array();

  public function index()
  {
    // Analytics
    $this->load->model('setting/extension');
    $this->load->model('catalog/product');
    $this->load->model('catalog/akcii');
    $this->load->model('tool/image');

    $data['analytics'] = array();

    $analytics = $this->model_setting_extension->getExtensions('analytics');

    foreach ($analytics as $analytic) {
      if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
        $data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
      }
    }

    if ($this->request->server['HTTPS']) {
      $server = $this->config->get('config_ssl');
    } else {
      $server = $this->config->get('config_url');
    }

    if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
      $this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
    }

    $data['title'] = $this->document->getTitle();
    $data['meta_h1'] = $this->document->getMetaH1();
    $data['base'] = $server;
    $data['description'] = $this->document->getDescription();
    $data['keywords'] = $this->document->getKeywords();
    $data['links'] = $this->document->getLinks();
    $data['styles'] = $this->document->getStyles();
    $data['scripts'] = $this->document->getScripts('header');
    $data['lang'] = $this->language->get('code');
    $data['direction'] = $this->language->get('direction');

    $this->load->language('common/header');

    $data['name'] = $this->config->get('config_name');

    if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
      $data['logo'] = $server . 'image/' . $this->config->get('config_logo');
    } else {
      $data['logo'] = '';
    }

    $data['account_img'] = $server . '/image/account.svg';

    $data['powered'] = "";
    // Wishlist
    if ($this->customer->isLogged()) {
      $this->load->model('account/wishlist');

      $data['text_wishlist'] = $this->model_account_wishlist->getTotalWishlist();
    } else {
      $data['text_wishlist'] = isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
    }

    $data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));

    $data['text_menu'] = $this->language->get('text_menu');
    $data['text_menu_sales'] = $this->language->get('text_menu_sales');
    $data['text_menu_shop'] = $this->language->get('text_menu_shop');

    $data['home'] = $this->url->link('common/home');
    $data['wishlist'] = $this->url->link('account/wishlist', '', true);
    $data['logged'] = $this->customer->isLogged();
    $data['account'] = $this->url->link('account/account', '', true);
    $data['register'] = $this->url->link('account/register', '', true);
    $data['login'] = $this->url->link('account/login', '', true);
    $data['order'] = $this->url->link('account/order', '', true);
    $data['transaction'] = $this->url->link('account/transaction', '', true);
    $data['download'] = $this->url->link('account/download', '', true);
    $data['logout'] = $this->url->link('account/logout', '', true);
    $data['shopping_cart'] = $this->url->link('checkout/cart');
    $data['checkout'] = $this->url->link('checkout/checkout', '', true);
    $data['contact'] = $this->url->link('information/contact');
    $data['telephone'] = $this->config->get('config_telephone');
    $data['config_open'] = $this->config->get('config_open');

    $data['language'] = $this->load->controller('common/language');
    $data['currency'] = $this->load->controller('common/currency');
    $data['ocsearchcategory'] = $this->load->controller('extension/module/ocsearchcategory');

    //++Andrey
    $data['use_ajax_login'] = true;
    $data['cart_top'] = $this->load->controller('common/cart/top');
    $data['wishlist_top'] = $this->load->controller('account/wishlist/top');

    if ($this->customer->isLogged()) {
      $customer_group_id = $this->customer->getGroupId();
    } else {
      $customer_group_id = $this->config->get('config_customer_group_id');
    }
    $filter_data = array(
      'customer_group_id' => $customer_group_id
    );
    $discont_total = $this->model_catalog_product->getProductsDiscont($filter_data);
    $data['discont_total'] = $discont_total['total_products'];
    //--Andrey

    //setOpenGraph --------------------------

    if ($this->request->get['route'] == "product/product") {
      $image = $this->document->getOgImage();
    } else {
      $image = file_exists(DIR_IMAGE . $this->config->get('config_logo')) ? $this->config->get('config_logo') : 'no_image.png';
    }

    $image_path = DIR_IMAGE . $image;

    if (is_file($image_path)) {
      $image_info = getimagesize($image_path);
      // Далі робота з $image_info
      $image_width = $image_info[0];
      $image_height = $image_info[1];
    } else {
      $image_width = 150;
      $image_height = 200;
    }

    $im_webp = $this->model_tool_image->resize($image, $image_width, $image_height);

    $mime_type = isset($image_info['mime']) ? $image_info['mime'] : 'image/svg+xml';

    $this->document->setOpenGraph('og:title', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", $data['meta_h1'])))))));
    $this->document->setOpenGraph('og:description', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", $data['description'])))))));
    $this->document->setOpenGraph('og:site_name', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'))))))));
    $this->document->setOpenGraph('og:url', $this->url->link('common/home', '', true));
    $this->document->setOpenGraph('og:image', str_replace(" ", "%20", $im_webp));

    if (isset($mime_type) && $mime_type) {
      $this->document->setOpenGraph('og:image:type', $mime_type);
    }

    if (isset($image_width) && $image_width) {
      $this->document->setOpenGraph('og:image:width', $image_width);
    }

    if (isset($image_height) && $image_height) {
      $this->document->setOpenGraph('og:image:height', $image_height);
    }

    $this->document->setOpenGraph('og:image:alt', htmlspecialchars(strip_tags(str_replace("\r", " ", str_replace("\n", " ", str_replace("\\", "/", str_replace("\"", "", $data['title'])))))));
    $this->document->setOpenGraph('og:type', 'website');

    $data['open_graph'] = $this->document->getOpenGraph();

    $data['is_mobile'] = $this->mobile_detect->isMobile();

    return $this->load->view('common/header', $data);
  }

  public function phone_more()
  {

    $data = array();

    $this->load->language('information/contact');

    $data['map'] = $this->load->controller('information/map');

    $data['entry_name'] = $this->language->get('entry_name');
    $data['entry_email'] = $this->language->get('entry_email');
    $data['entry_enquiry'] = $this->language->get('entry_enquiry');
    $data['text_contact'] = $this->language->get('text_contact');
    $data['send_done'] = false;

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate_send_form()) {
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

      $data['send_done'] = true;
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

    $data['action'] = $this->url->link('common/header/info', '', true);

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


    $data['config_address'] = $this->config->get('config_address');

    $data['telephone'] = $this->config->get('config_telephone');
    $data['telephone_1'] = $this->config->get('config_telephone_1');
    $data['telephone_2'] = $this->config->get('config_telephone_2');
    $data['telephone_3'] = $this->config->get('config_telephone_3');
    $data['open'] = nl2br(html_entity_decode($this->config->get('config_open')));

    $data['email_store'] = $this->config->get('config_email');

    return $this->load->view('common/menu_top_phone', $data);
  }

  public function info()
  {
    $this->response->setOutput($this->phone_more());
  }

  protected function validate_send_form()
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

}
