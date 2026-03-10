<?php

class ControllerEcommerceAddToCart extends Controller
{
  public function index($cart_prods)
  {
    $this->load->model('catalog/category');

    $json['ecommerce_products'] = [];

    foreach ($cart_prods as $item_cart) {

      $opt_name = "";

      $product_options = $this->model_catalog_product->getProductOptionPawPaw($item_cart["product_id"]);

      if (!empty($product_options)){
        foreach ($product_options as $opt_item){
          if ($opt_item["selected"] > 0){
            $opt_name = $opt_item["name"];
            break;
          }
        }
      }

      $product_info = $this->model_catalog_product->getProduct($item_cart["product_id"]);
      $product_categories = $this->model_catalog_product->getCategories($item_cart["product_id"]);

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
      }

      $json['ecommerce_products'][] = [
        'product_name' => $item_cart['name'] ?? "", // используйте реальные данные
        'product_id' => $item_cart["product_id"],
        'product_price' => $item_cart['price'] ?? "",
        'product_brand' => $product_info['manufacturer'] ?? "",
        'product_category' => $mass_cat[0]['name'] ?? "",
        'product_variant' => $opt_name ?? "",
        'product_quantity' => $item_cart['quantity'] ?? ""
      ];
    }

    return $json['ecommerce_products'];
  }
}
