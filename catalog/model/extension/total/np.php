<?php
class ModelExtensionTotalNp extends Model {
	public function getTotal($total) {
		if ($this->cart->hasShipping() && isset($this->session->data['shipping_method'])) {

      $this->load->language('extension/total/shipping');

      $cost = 0;
      $has_method = false;
      if (!empty($this->session->data['shipping_method'])){
        $sm = $this->session->data['shipping_method'];
        
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
          $has_method = true;
          if (isset($method['cost'])) {
            $free_threshold = (float)($method['free'] ?? 0);

            // Розраховуємо суму товарів з урахуванням знижок (акційних цін)
            $real_subtotal = 0;
            foreach ($this->cart->getProducts() as $product) {
              if (isset($product['special']) && $product['special'] > 0) {
                $real_subtotal += (float)$product['special'] * $product['quantity'];
              } else {
                $real_subtotal += (float)$product['total'];
              }
            }

            // Віднімаємо знижку лояльності, якщо вона є
//            $this->load->model('extension/module/discount');
//            $loyalty = $this->model_extension_module_discount->getLoyaltyDiscount();
//            if (!empty($loyalty) && isset($loyalty['discount']) && (float)$loyalty['discount'] > 0) {
//              $real_subtotal -= ($real_subtotal * (float)$loyalty['discount'] / 100);
//            }

            if ($free_threshold > 0 && $real_subtotal >= $free_threshold) {
              // 0
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
          'title'      => $this->language->get('shipping_title'),
          'value'      => (float)$cost,
          'sort_order' => (int)$this->config->get('total_shipping_sort_order')
        );
      }
		}
	}
}
