<?php

class ControllerExtensionModuleOcpolicy extends Controller
{

  public function index()
  {
    //if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $this->config->get('config_maintenance') == 0) {

      $this->load->language('extension/module/ocpolicy');

      $data = [];

      $data['ocpolicy_accept'] = $this->language->get('ocpolicy_accept');
      $data['ocpolicy_more'] = $this->language->get('ocpolicy_more');

      $data['text_ocpolicy'] = false;
      $data['max_day'] = 365;
      $ocpolicy_value = 'ocpolicy';
      $data['ocpolicy_value'] = $ocpolicy_value;
      $data['ocpolicy_day_now'] = date("Y-m-d H:i:s");

      $ocpolicy_status = $this->config->get('ocpolicy_status');
      $ocpolicy_data = $this->config->get('ocpolicy_data');

      if (isset($ocpolicy_data['indormation_id'])) {
        $data['url'] = '<a class="policy" href="'. $this->url->link('information/information', 'information_id=' . (int)$ocpolicy_data['indormation_id']) . '">Детальніше</a>';
      }

      if (isset($ocpolicy_data['value']) && $ocpolicy_data['value'] && !empty($ocpolicy_data['value'])) {
        $ocpolicy_value = $ocpolicy_data['value'];
        $data['ocpolicy_value'] = $ocpolicy_value;
      }

      if ($ocpolicy_status && (!isset($this->request->cookie[$ocpolicy_value]) || !$this->request->cookie[$ocpolicy_value])) {
        if (isset($ocpolicy_data['module_text'][(int)$this->config->get('config_language_id')]) && !empty($ocpolicy_data['module_text'][(int)$this->config->get('config_language_id')])) {
          $data['text_ocpolicy'] = html_entity_decode($ocpolicy_data['module_text'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8');

          if (isset($ocpolicy_data['max_day']) && (int)$ocpolicy_data['max_day'] > 0) {
            $data['max_day'] = (int)$ocpolicy_data['max_day'];
          }
        }
      }

      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($data));
//    } else {
//      $this->response->redirect($this->url->link('error/not_found', '', true));
//    }
  }
}
