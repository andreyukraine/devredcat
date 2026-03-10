<?php

class ControllerExtensionModuleOcProduct extends Controller
{
  public function index($setting)
  {
    $this->load->language('extension/module/ocproducts');
    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocproduct');
    $this->load->model('tool/image');
    $data['products'] = array();
    $this->load->model('localisation/language');
    $data['code'] = $this->session->data['language'];
    $store_id = $this->config->get('config_store_id');
    if (!$setting['limit']) {
      $setting['limit'] = 4;
    }

    $data['text_option'] = $this->language->get("text_option");;
    $data['text_add_to_wishlist'] = $this->language->get("text_add_to_wishlist");;
    $data['text_remove_from_wishlist'] = $this->language->get("text_remove_from_wishlist");;

    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

    $data['heading_title'] = $setting['name'];
    $data['bg_color'] = "#fffff";

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    if (isset($setting['module_image'])) {
      $data['module_image'] = $this->model_tool_image->resize($setting['module_image'], $width, $height);
    } else {
      $data['module_image'] = false;
    }
    if (isset($setting['module_image_link'])) {
      $data['module_image_link'] = $setting['module_image_link'];
    } else {
      $data['module_image_link'] = false;
    }
    $results = array();
    if ($setting['option'] == 0) {
      if (!empty($setting['product'])) {
        $results = array();
        $products = array_slice($setting['product'], 0, (int)$setting['limit']);
        foreach ($products as $product_id) {
          $results[] = $this->model_catalog_product->getProduct($product_id);
        }
      }
    } else if ($setting['option'] == 1) {
      if ($setting['productfrom'] == 1) {
        $data['filter_category_id'] = $setting['cate_id'];
        $results = $this->model_catalog_product->getProducts($data);
      } else if ($setting['productfrom'] == 0) {
        if (!empty($setting['productcate'])) {
          $products = array_slice($setting['productcate'], 0, (int)$setting['limit']);
          foreach ($products as $product_id) {
            $results[] = $this->model_catalog_product->getProduct($product_id);
          }
        }
      } else {
        if ($setting['input_specific_product'] == 0) {
          $data['products'] = array();
          $filter_data = array(
            'filter_category_id' => $setting['cate_id'],
            'sort' => 'p.date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => $setting['limit'],
          );
          $results = $this->model_catalog_product->getProducts($filter_data);
        } else if ($setting['input_specific_product'] == 1) {
          $filter_data = array(
            'sort' => 'pd.name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => $setting['limit']
          );
          $results = $this->model_extension_module_ocproduct->getProductSpecialsCategory($setting['cate_id'],$filter_data);
        } else if ($setting['input_specific_product'] == 2) {
          $data['products'] = array();
          $results = $this->model_extension_module_ocproduct->getBestSellerProductsCategory($setting['cate_id'], $setting['limit']);
        } else {
          $data['products'] = array();
          $results = $this->model_extension_module_ocproduct->getMostViewedProductsCategory($setting['cate_id'], $setting['limit']);
        }
      }
    } else {
      if (!empty($setting['product_status_id'])) {
        $this->load->model('catalog/product_status');
        $data['products'] = array();
        $filter_data = array(
          'sort' => 'p.date_added',
          'order' => 'DESC',
          'start' => 0,
          'limit' => $setting['limit'],
          'product_status_id' => $setting['product_status_id']
        );
        $results = $this->model_catalog_product->getProductsByProductStatus($filter_data);
      } else {
        if ($setting['autoproduct'] == 0) {
          $data['products'] = array();
          $filter_data = array(
            'sort' => 'p.date_added',
            'order' => 'DESC',
            'start' => 0,
            'limit' => $setting['limit']
          );
          $results = $this->model_catalog_product->getProducts($filter_data);
        } else if ($setting['autoproduct'] == 1) {
          $filter_data = array(
            'sort' => 'pd.name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => $setting['limit']
          );
          $results = $this->model_catalog_product->getProductSpecials($filter_data);
        } else if ($setting['autoproduct'] == 2) {
          $data['products'] = array();
          $results = $this->model_catalog_product->getBestSellerProducts($setting['limit']);
        } else if ($setting['autoproduct'] == 3) {
          $data['products'] = array();
          $results = $this->model_catalog_product->getPopularProducts($setting['limit']);
        } else  if ($setting['autoproduct'] == 4) {
          $data['products'] = array();
          $results = $this->model_extension_module_ocproduct->getDealProducts($setting['limit']);
        } else  if ($setting['autoproduct'] == 5) {
          $data['products'] = array();

          if ($this->customer->isLogged()) {
            $customer_group_id = $this->customer->getGroupId();
          } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
          }

          $filter_data = array(
            'sort' => 'pd.name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => $setting['limit'],
            'customer_group_id' => $customer_group_id
          );
          $results_prods = $this->model_catalog_product->getProductsDiscont($filter_data);
          if (!empty($results_prods['products'])){
            $results = $results_prods['products'];
          }
        }
      }
    }

    /* End */
    if ($results) {
      foreach ($results as $result) {

        $sort_order = "ASC";
        if (isset($this->request->get['order'])) {
          $sort_order = $this->request->get['order'];
        }

        if (!empty($setting['product_status_id'])) {
          $product_data = array(
            'item' => $result,
            'sort_order' => $sort_order,
            'product_status_id' => $setting['product_status_id']
          );
        }else{
          $product_data = array(
            'item' => $result,
            'sort_order' => $sort_order
          );
        }
        $options = $this->load->controller('product/options', $product_data);

        $data['products'][] = array(
          'product_id' => $result['product_id'],
          'images' => $options['images'],
          'options' => $options['prod_options'],
          'name' => (utf8_strlen($result['name']) > 70 ? utf8_substr($result['name'], 0, 70) . '..' : $result['name']),
          'price' => $this->currency->format($options['price'], $this->session->data['currency']),
          'special' => $options['special'] > 0 ? $this->currency->format($options['special'], $this->session->data['currency']) : false,
          'rate_special' => $options['percent'],
          'quantity' => $options['quantity'],
          'rating' => (int)$result['rating'],
          'uniq_id' => $options['uniq_id'],
          'cart_id' => $options['cart_id'],
          'in_cart' => $options['in_cart'],
          'is_buy' => $options['is_buy'],
          'on_stock' => $options['on_stock'],
          'in_wishlist' => $options['in_wishlist'] ? 1 : 0,
          'statuses' => $options['statuses'],
          'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'], true)
        );
      }
    }

    $number_random = rand(1, 1000);
    $data['config_module'] = array(
      'name' => $setting['name'],
      'class' => $setting['class'],
      'type' => (int)$setting['type'],
      'slider' => (int)$setting['slider'],
      'auto' => (int)$setting['auto'],
      'loop' => (int)$setting['loop'],
      'margin' => (int)$setting['margin'],
      'nrow' => (int)$setting['nrow'],
      'items' => (int)$setting['items'],
      'time' => (int)$setting['time'],
      'speed' => (int)$setting['speed'],
      'row' => (int)$setting['row'],
      'navigation' => (int)$setting['navigation'],
      'pagination' => (int)$setting['pagination'],
      'showcart' => (int)$setting['showcart'],
      'showdescription' => (int)$setting['showdescription'],
      'showwishlist' => (int)$setting['showwishlist'],
      'showcompare' => (int)$setting['showcompare'],
      'showquickview' => (int)$setting['showquickview'],
      'laptop' => (int)$setting['laptop'],
      'tablet' => (int)$setting['tablet'],
      'stablet' => (int)$setting['stablet'],
      'mobile' => (int)$setting['mobile'],
      'smobile' => (int)$setting['smobile'],
      'title_lang' => $setting['title_lang'],
      'sub_title_lang' => $setting['sub_title_lang'],
      'description' => (int)$setting['description'],
      'countdown' => (int)$setting['countdown'],
      'rotator' => (int)$setting['rotator'],
      'newlabel' => (int)$setting['newlabel'],
      'salelabel' => (int)$setting['salelabel'],
      'module_id' => $number_random
    );
    if (isset($setting['module_description'][$this->config->get('config_language_id')])) {
      $data['module_description'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['description'], ENT_QUOTES, 'UTF-8');
      if ($data['module_description'] == '<p><br><p>') $data['module_description'] = '';
    }
    //echo '<pre>'; print_r($data['config_module']); die;
    return $this->load->view('extension/module/ocproduct', $data);
  }
}
