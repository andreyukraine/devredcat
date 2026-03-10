<?php

class ControllerMailTemplateData extends Controller
{
  public function index()
  {
    $data = array();

    $this->load->language('mail/template');

    $server = "https://detta.com.ua/";
    $data['store_url'] = $server;

    $data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

    $data['text_welcome'] = sprintf($this->language->get('text_welcome'), $data['store']);

    //логотип
    if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
      $data['logo_url'] = $server . 'image/' . $this->config->get('config_logo');
    } else {
      $data['logo_url'] = '/image/no_image.png';
    }

    if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
      $data['bg_url'] = $server . '/image/catalog/mail/blank_mail.svg';
    }

    $data['text_login'] = $this->language->get('text_login');
    $data['text_thanks'] = $this->language->get('text_thanks');

    $data['current_year'] = date('Y');
    $data['button_color'] = '#003f40'; // можна змінити колір кнопки
    $data['store_color'] = '#333'; // колір назви магазину

    $data['login'] = $server . "login";

    // Дані компанії
    $data['company_name'] = 'Приватне підприємство "Детта"';
    $data['company_edrpou'] = '32071677';
    $data['company_phone'] = '+38 067 549 14 55';
    $data['company_email'] = 'detta@detta.com.ua';
    $data['company_motto'] = 'де дружба розвиток надійність';

    return $data;
  }
}
