<?php

class ModelExtensionShippingNp extends Model
{
  function getQuote($address)
  {
    $this->load->language('extension/shipping/np');

    $quote_data = array();
    $method_data = array();

    $quote_data['np'] = array(
      'code' => 'np.np',
      'title' => $this->language->get('text_description'),
      'cost' => 0.00,
      'text' => $this->currency->format(0.00, $this->session->data['currency']),
    );

    $method_data = array(
      'code' => 'np',
      'title' => $this->language->get('text_title'),
      'quote' => $quote_data,
      'sort_order' => $this->config->get('shipping_np_sort_order'),
      'error' => false
    );

    return $method_data;
  }
}
