<?php

class ControllerProductOcquickview extends Controller
{
  public function index()
  {
    $json = array();

    if (isset($product_info['product_id'])) {
      $product_id = (int)$product_info['product_id'];
    } else {
      $product_id = 0;
    }

    $data = $this->loadProduct($product_id);

    if (!$json) {
      if ($data) {

        $json['html'] = $this->load->view('product/ocquickview/product', $data);
        $json['success'] = true;
      } else {
        $json['success'] = false;
        $json['html'] = "There is no product";
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function seoview()
  {
    $json = array();

    $this->load->model('catalog/ocquickview');

    if (!$json) {
      if (isset($this->request->get['ourl'])) {
        $seo_url = $this->request->get['ourl'];

        $product_id = $this->model_catalog_ocquickview->getProductBySeoUrl($seo_url);

        $data = $this->loadProduct($product_id);

        if ($data) {
          $json['html'] = $this->load->view('product/ocquickview', $data);
          $json['success'] = true;
        } else {
          $json['success'] = false;
          $json['html'] = "There is no product";
        }
      } else {
        $json['success'] = false;
        $json['html'] = "There is no product";
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function loadProduct($product_id)
  {
    $this->load->language('product/product');
    $this->load->model('catalog/product');
    $this->load->model('catalog/product_status');

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    $product_info = $this->model_catalog_product->getProduct($product_id);

    if ($product_info) {

      $this->document->setTitle($product_info['meta_title']);
      $this->document->setDescription($product_info['meta_description']);
      $this->document->setKeywords($product_info['meta_keyword']);
      $this->document->addLink($this->url->link('product/product', 'product_id=' . $product_info['product_id']), 'canonical');

      $data['heading_title'] = $product_info['name'];

      $data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
      $data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));

      $this->load->model('catalog/review');

      $data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

      $data['product_id'] = (int)$product_info['product_id'];
      $data['manufacturer'] = $product_info['manufacturer'];
      $data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);

      $data['reward'] = $product_info['reward'];
      $data['points'] = $product_info['points'];

      $sort_order = "ASC";
      if (isset($this->request->get['order'])){
        $sort_order = $this->request->get['order'];
      }

      $product_data = array(
        'item' => $product_info,
        'sort_order' => $sort_order
      );
      $options = $this->load->controller('product/options', $product_data);

      $data['options']      = $options['prod_options'];
      $data['images']       = $options['images'];
      $data['statuses']     = $options['statuses'];
      $data['sku']          = $options['sku'];
      $data['model']        = $options['ean'];
      $data['price']        = $this->currency->format($options['price'], $this->session->data['currency']);
      $data['special']      = $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : 0;
      $data['rate_special'] = $options['percent'];
      $data['quantity']     = $options['quantity'];
      $data["uniq_id"]      = $options['uniq_id'];
      $data['cart_id']      = $options['cart_id'];
      $data['in_cart']      = $options['in_cart'];
      $data['is_buy']       = $options['is_buy'];
      $data['on_stock']     = $options['on_stock'];
      $data['in_wishlist']  = $options['in_wishlist'] ? 1 : 0;

      $data['review_status'] = $this->config->get('config_review_status');

      if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
        $data['review_guest'] = true;
      } else {
        $data['review_guest'] = false;
      }

      if ($this->customer->isLogged()) {
        $data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
      } else {
        $data['customer_name'] = '';
      }

      $data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
      $data['rating'] = (int)$product_info['rating'];

      // Captcha
      if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
        $data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
      } else {
        $data['captcha'] = '';
      }

      //++Andrey
      $data["warehouses"] = array();
      $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_id, $options['mass_options'], true);
      if (!empty($stocks)){
        $this->load->language('extension/module/ocwarehouses');
        $data["title_stock_warehouses"] = $this->language->get('title_stock_warehouses');

        $this->load->model('extension/module/ocwarehouses');
        foreach ($stocks as $warehouse_id => $stock){
          $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);
          $data["warehouses"][] = array(
            'name'     => $warehouse['name'],
            'quantity' => $stock
          );
        }
      }

      $data['share'] = $this->url->link('product/product', 'product_id=' . (int)$product_info['product_id']);

      $data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($product_info['product_id']);
    } else {
      $data = false;
    }

    return $data;
  }
}
