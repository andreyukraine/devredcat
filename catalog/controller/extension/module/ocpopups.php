<?php

class ControllerExtensionModuleOcpopups extends Controller {
    private $prefix;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->prefix = (version_compare(VERSION, '3.0', '>=')) ? 'module_' : '';
        $this->load->model('extension/module/ocpopups');
        $this->load->language('extension/module/ocpopups');
    }

    public function index() {

      $json = array();

      $data = array();
      $message = $this->model_extension_module_ocpopups->getActive();

      $data['text'] =  html_entity_decode($message['text']);

      $json['html'] = $this->load->view('extension/module/ocpopup', $data);

      $json['max_day'] = 365;
      $json['ocpopup'] = 'ocpopup';
      $json['ocpopup_day_now'] = date("Y-m-d H:i:s");

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));

    }
}
