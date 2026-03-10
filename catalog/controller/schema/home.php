<?php

class ControllerSchemaHome extends Controller
{
  public function index($data)
  {
    $data['telephone'] = $this->config->get('config_telephone');
    $data['url'] = $this->url->link('common/home');
    $data['name'] = $this->config->get('config_name');

    return $this->load->view('schema/home', $data);
  }
}
