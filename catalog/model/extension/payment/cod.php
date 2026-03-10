<?php

class ModelExtensionPaymentCOD extends Model
{
  public function getMethod($address, $total)
  {
    $this->load->language('extension/payment/cod');
    $method_data = array();
    $method_data = array(
      'code' => 'cod',
      'title' => $this->language->get('text_title'),
      'terms' => '',
      'sort_order' => $this->config->get('payment_cod_sort_order')
    );
    return $method_data;
  }
}
