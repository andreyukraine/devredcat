<?php

class ControllerExtensionModuleOcshare extends Controller{

  public function __construct($registry) {
    parent::__construct($registry);
  }

  public function index(){

    $this->load->language('extension/module/ocshare');

    $data = array();
    $data['url'] = $this->request->server['HTTP_REFERER'];

    $this->response->setOutput($this->load->view('extension/module/ocshare', $data));
  }

}
