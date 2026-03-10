<?php

class ControllerEcommercePurchase extends Controller
{
  public function index($orders)
  {
    $this->load->model('catalog/category');
    $this->load->model('catalog/product');

    foreach ($orders as $warehouse_id => $order) {
      foreach ($order['products'] as &$product) {

        $product_info = $this->model_catalog_product->getProduct($product["product_id"]);
        $product_categories = $this->model_catalog_product->getCategories($product["product_id"]);

        if (!empty($product_categories)) {

          $mass_cat = array();
          foreach ($product_categories as $cat_item) {
            $cat_info = $this->model_catalog_category->getCategory($cat_item["category_id"]);
            if (isset($this->request->cookie['catalog_type'])) {
              if ((int)$this->request->cookie['catalog_type'] > 0) {
                if ((int)$cat_info['type'] > 0) {
                  $mass_cat[] = $cat_info;
                } else {
                  $mass_cat[] = $cat_info;
                }
              } else {
                if ((int)$cat_info['type'] <= 0) {
                  $mass_cat[] = $cat_info;
                }
              }
            } else {
              if ((int)$cat_info['type'] <= 0) {
                $mass_cat[] = $cat_info;
              }
            }
          }
          $product['category'] = $mass_cat[0]['name'];
          $product['manufacturer'] = $product_info['manufacturer'];
        }

        $data['orders'][$warehouse_id]['site'] = "detta.com.ua";
        $data['orders'][$warehouse_id]['order_id'] = $order['number'];
        $data['orders'][$warehouse_id]['total_value'] = substr($order['total'], 0, -3);;
        $data['orders'][$warehouse_id]['tax_value'] = 0;

        $data['orders'][$warehouse_id]['shipping_value'] = 0;
        $data['orders'][$warehouse_id]['currency'] = "";
        $data['orders'][$warehouse_id]['coupon'] = "";

        $data['orders'][$warehouse_id]['products'] = $order['products'];

      }
    }

    if (isset($this->session->data['ga4_purchase_sent'])) {
      $data['send_ga4'] = true;
    } else {
      $data['send_ga4'] = true;
      $this->session->data['ga4_purchase_sent'] = true;
    }

    return $this->load->view('ecommerce/purchase', $data);
  }
}
