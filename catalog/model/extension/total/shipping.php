<?php
class ModelExtensionTotalShipping extends Model {
	public function getTotal($total) {
		if ($this->cart->hasShipping()) {

      $this->load->language('extension/total/shipping');

      $cost = 0;
      $has_method = false;
      $type = "";
      $cost_delivery = 0;
      
      if (!empty($this->session->data['shipping_method'])){
        $sm = $this->session->data['shipping_method'];



        // Перевіряємо, чи це багатовимірний масив (по складах) чи плоский
        $methods = array();
        if (isset($sm['code'])) {
          $methods[] = $sm;
        } else {
          foreach ($sm as $item) {
            if (is_array($item) && isset($item['code'])) {
              $methods[] = $item;
            }
          }
        }

        foreach ($methods as $method){
          if (!empty($method['error'])) continue;
          
          $has_method = true;
          if (isset($method['cost'])) {
            $free_threshold = (float)($method['free'] ?? 0);
            
            // Розраховуємо суму товарів
            $real_subtotal = 0;
            foreach ($this->cart->getProducts() as $product) {
              $real_subtotal += (float)$product['total'];
            }

            $type = $method['code'];

            if ($free_threshold > 0 && $real_subtotal >= $free_threshold) {
              // 0
            }elseif ($free_threshold > 0 && $real_subtotal < $free_threshold){
              $cost_delivery = 1;
              $cost += (float)$method['cost'];
            } else {
                $cost += (float)$method['cost'];
            }
          }
        }
      }

      if ($has_method) {
        $total['total'] += $cost;
        $total['totals'][] = array(
          'code'       => 'shipping',
          'type'       => $type,
          'cost_delivery' => $cost_delivery,
          'title'      => $this->language->get('shipping_title'),
          'value'      => (float)$cost,
          'sort_order' => (int)$this->config->get('total_shipping_sort_order')
        );
      }
		}
	}
}
