<?php

class ControllerEcommerceBeginCheckout extends Controller
{
  public function index($data)
  {
    $this->load->model('catalog/category');

    foreach ($data['products'] as &$product) {
      $product_categories = $this->model_catalog_product->getCategories($product["product_id"]);

      if (!empty($product_categories)){

        $mass_cat = array();
        foreach ($product_categories as $cat_item) {
          $cat_info = $this->model_catalog_category->getCategory($cat_item["category_id"]);
          if (isset($this->request->cookie['catalog_type'])) {
            if ((int)$this->request->cookie['catalog_type'] > 0) {
              if ((int)$cat_info['type'] > 0) {
                $mass_cat[] = $cat_info;
              }else{
                $mass_cat[] = $cat_info;
              }
            }else{
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
        
        if (isset($mass_cat[0]['name'])) {
          $product['category'] = $mass_cat[0]['name'];
        } else {
          $product['category'] = '';
        }
      }
    }

    return $this->load->view('ecommerce/begin_checkout', $data);
  }
}
