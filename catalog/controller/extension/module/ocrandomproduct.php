<?php

class ControllerExtensionModuleOcRandomProduct extends Controller
{
  public function index($setting)
  {
    $this->load->language('extension/module/ocproducts');
    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocrandomproduct');
    $this->load->model('tool/image');
    $this->load->model('localisation/language');
    $data['code'] = $this->session->data['language'];
    $store_id = $this->config->get('config_store_id');

    $data['use_quickview'] = 1;
    $data['use_catalog'] = 1;

//    $data['products'] = array();
//    $results = $this->model_catalog_product->getBestSellerProducts($setting['limit']);
//
//    $data['products'] = array();
//    $results = $this->model_catalog_product->getPopularProducts($setting['limit']);
//
//    $data['products'] = array();
//    $results = $this->model_extension_module_ocproduct->getDealProducts($setting['limit']);
//
//
//    /* Get new product */
//    $this->load->model('catalog/product');
//    $filter_data = array(
//      'sort' => 'p.date_added',
//      'order' => 'DESC',
//      'start' => 0,
//      'limit' => 10
//    );
//    $new_results = $this->model_catalog_product->getProducts($filter_data);

    $filter_data = array(
      'sort' => 'pd.name',
      'order' => 'ASC',
      'start' => 0,
      'limit' => 1
    );

    $results = $this->model_extension_module_ocrandomproduct->getProductSpecialsCategory($setting['cate_id'], $filter_data);

    if ($results) {
      foreach ($results as $result) {
        if ($result['image']) {
          $image = $this->model_tool_image->resize($result['image'], 150, 200);
        } else {
          $image = $this->model_tool_image->resize('placeholder.png', 150, 200);
        }

        $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

        if ($this->config->get('config_review_status')) {
          $rating = $result['rating'];
        } else {
          $rating = false;
        }

//        $is_new = false;
//        if ($new_results) {
//          foreach ($new_results as $new_r) {
//            if ($result['product_id'] == $new_r['product_id']) {
//              $is_new = true;
//            }
//          }
//        }

        if ($result['quantity'] <= 0) {
          $stock = $result['stock_status'];
        } elseif ($this->config->get('config_stock_display')) {
          $stock = $result['quantity'];
        } else {
          $stock = $this->language->get('text_instock');
        }
        $model = $result['model'];

        $product_images = array();
        $img_results = $this->model_catalog_product->getProductImages($result['product_id']);
        foreach ($img_results as $img) {
          $product_images[] = array(
            'main' => $this->model_tool_image->resize($img['image'], 150, 200),
            'small' => $this->model_tool_image->resize($img['image'], 80, 80)
          );
        }

        $data['promo'][] = array(
          'product_id' => $result['product_id'],
          'thumb' => $image,
          'images' => $product_images,
          'model' => $model,
          'name' => $result['name'],
          'price' => $price,
          'special' => $result['special'],
          'rate_special' => false,
          'tax' => false,
          'rating' => $rating,
          'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'], true),
          'is_new' => false,
          'stock' => $stock,
          'quantity' => (int)$result['quantity'],
          'total_qty' => (int)$result['quantity'],
          'manufacturer' => $result['manufacturer'],
          'manufacturers' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $result['manufacturer_id'])
        );
      }
    }

    //echo '<pre>'; print_r($data['config_module']); die;
    //$this->load->view('extension/module/ocrandomproduct', $data)
    return $data;
  }
}
