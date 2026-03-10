<?php

class ControllerExtensionModuleCustomButton extends Controller
{
  public function index($setting = array())
  {

    $this->load->language('extension/module/custom/button');

    if ($this->config->get('config_checkout_id')) {
      $this->load->model('catalog/information');

      $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));

      if ($information_info) {
        $data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id'), true), $information_info['title'], $information_info['title']);
      } else {
        $data['text_agree'] = '';
      }
    } else {
      $data['text_agree'] = '';
    }

    if (isset($this->session->data['agree'])) {
      $data['agree'] = $this->session->data['agree'];
    } else {
      $data['agree'] = '';
    }

    $this->load->model('setting/extension');

    $totals = array();
    $taxes = $this->cart->getTaxes();
    $total = 0;

    // Because __call can not keep var references so we put them into an array.
    $total_data = array(
      'totals' => &$totals,
      'taxes' => &$taxes,
      'total' => &$total
    );

    // Display prices
    $sort_order = array();
    $results = $this->model_setting_extension->getExtensions('total');
    foreach ($results as $key => $value) {
      $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
    }
    array_multisort($sort_order, SORT_ASC, $results);

    foreach ($results as $result) {
      if ($this->config->get('total_' . $result['code'] . '_status')) {
        $this->load->model('extension/total/' . $result['code']);
        // We have to put the totals in an array so that they pass by reference.
        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
      }
    }

    $sort_order = array();

    foreach ($totals as $key => $value) {
      $sort_order[$key] = $value['sort_order'];
    }

    $data['is_create_order'] = true;
    $data['text_min_order'] = "";

    array_multisort($sort_order, SORT_ASC, $totals);

    return $this->load->view('extension/module/custom/button', $data);
  }
}
