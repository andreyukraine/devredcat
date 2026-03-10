<?php
class ModelExtensionTotalNp extends Model {
	public function getTotal($total) {
		if ($this->cart->hasShipping() && isset($this->session->data['shipping_method'])) {

      $this->load->language('extension/total/shipping');

      $cost = 0;
      if (isset($this->session->data['shipping_method'])){
        foreach ($this->session->data['shipping_method'] as $method){
          if (isset($method['cost'])) {
            $cost += $method['cost'];
          }
        }
      }
      if ($cost > 0) {
        $total['total'] += $cost;

        $total['totals'][] = array(
          'code' => 'shipping',
          'shipping' => "",
          'title' => $this->language->get('shipping_title'),
          'value' => $cost,
          'sort_order' => $this->config->get('total_shipping_sort_order')
        );
      }
		}
	}
}
