<?php

class ControllerCommonMenuBottom extends Controller
{
  public function index()
  {
    $this->load->model('catalog/information');

    $data['menu_bottom'] = array();

    $informations = $this->model_catalog_information->getInformationsBottom();

    foreach ($informations as &$information) {
      $data['menu_bottom'][] = array(
        'title' => $information['title'],
        'url'   => $this->url->link('information/information', 'information_id=' .  $information['information_id'])
      );
    }

    unset($information);

    if ($this->config->get('configblog_blog_menu')) {
      if (!empty($this->config->get('configblog_name'))) {
        $title = $this->config->get('configblog_name');
      } else {
        $title = $this->language->get('text_blog');
      }
      $data['menu_bottom'][] = array(
        'title' => $title,
        'url'   => "blog"
      );
    }
    $data['menu_bottom'][] = array(
      'title' => $this->language->get('text_contact'),
      'url'   => "contact"
    );

    return $this->load->view('common/menu_bottom', $data);
  }

}
