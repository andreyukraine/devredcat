<?php

class ModelExtensionTotalProductDiscount extends Model
{
  public function getTotal($total)
  {
    if ($this->config->get('module_discounts_pack_status') && $this->cart->hasProducts() && $this->config->get('total_product_discount_status')) {
      $this->load->language('extension/total/product_discount');
      $this->load->model('extension/module/discount');

      $discount_total = 0;
      $discount = 0;


      $total_discount = $this->model_extension_module_discount->getLoyaltyDiscount();
      if ($total_discount) {
        $discount = (int)$total_discount['discount'];
      }

      if ($discount <= 0) {
        foreach ($this->cart->getProducts() as $product) {
          if ($product['special'] > 0) {
            $discount = ($product['price'] - $product['special']) * $product['quantity'];
            $discount_total += (double)$discount;
          }
        }

        if ($discount_total > 0) {
          $total['totals'][] = array(
            'code' => 'product_discount',
            'title' => $this->language->get('text_discount'),
            'value' => -$discount_total,
            'sort_order' => $this->config->get('total_product_discount_sort_order')
          );
          //$total['total'] -= $discount_total;
        }
      }else{
        if ($discount_total > 0) {
          $total['totals'][] = array(
            'code' => 'product_discount',
            'title' => $this->language->get('text_discount'),
            'value' => 0,
            'sort_order' => $this->config->get('total_product_discount_sort_order')
          );
          //$total['total'] = 0;
        }
      }
    }
  }
}
