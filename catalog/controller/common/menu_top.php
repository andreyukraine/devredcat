<?php

class ControllerCommonMenuTop extends Controller
{

  public function index()
  {
    $this->load->model('catalog/information');

    $data['menu_top'] = array();

    $informations = $this->model_catalog_information->getInformationsTop();
    foreach ($informations as &$information) {
      $data['menu_top'][] = array(
        'title' => $information['title'],
        'url'   => $this->url->link('information/information', 'information_id=' .  $information['information_id'])
      );
    }
    unset($information);

    //delivery
    $this->load->model('catalog/information');
    if ($this->customer->isLogged()) {
      if ((int)$this->customer->getGroupId() == 17){
        $information_info = $this->model_catalog_information->getInformation(13);
      } elseif ((int)$this->customer->getGroupId() != 17 && (int)$this->customer->getGroupId() != 21){
        $information_info = $this->model_catalog_information->getInformation(12);
      }else{
        $information_info = $this->model_catalog_information->getInformation(6);
      }
    }else{
      $information_info = $this->model_catalog_information->getInformation(6);
    }
    $data['menu_top'][] = array(
      'title' => $information_info['title'],
      'url'   => $this->url->link('information/information', 'information_id=' .  $information_info['information_id'])
    );


    if ($this->config->get('configblog_blog_menu')) {
      if (!empty($this->config->get('configblog_name'))) {
        $title = $this->config->get('configblog_name');
      } else {
        $title = $this->language->get('text_blog');
      }
      $data['menu_top'][] = array(
        'title' => $title,
        'url'   => "blog"
      );
    }
    $data['menu_top'][] = array(
      'title' => $this->language->get('text_contact'),
      'url'   => "contact"
    );

    return $this->load->view('common/menu_top', $data);
  }

}
