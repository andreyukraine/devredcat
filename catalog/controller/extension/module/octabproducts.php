<?php

class ControllerExtensionModuleOcTabProducts extends Controller
{
  public function index($setting)
  {
    $this->load->language('extension/module/octabproducts');
    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocproduct');
    $this->load->model('tool/image');
    $this->load->model('localisation/language');
    $this->load->model('catalog/category');
    $data['code'] = $this->session->data['language'];
    if (!$setting['limit']) {
      $setting['limit'] = 4;
    }

    $width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width');
    $height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height');
    if ($this->mobile_detect->isMobile()) {
      $width = 150;
      $height = 150;
    }

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

    if (isset($setting['module_image2'])) {
      $data['module_image2'] = $this->model_tool_image->resize($setting['module_image2'], $width, $height);
    } else {
      $data['module_image2'] = false;
    }
    if (isset($setting['module_image_link2'])) {
      $data['module_image_link2'] = $setting['module_image_link2'];
    } else {
      $data['module_image_link2'] = false;
    }
    $data['octabs'] = array();

    foreach ($setting['octab'] as $octab) {

      $thumbnail_image = false;
      $results = array();
      $product_data = array();

      $filter_data = array(
        'filter_category_id' => (int)$octab['cate_id'],
        'filter_sub_category' => true,
        'sort' => 'p.date_added',
        'order' => 'DESC',
        'start' => 0,
        'limit' => $setting['limit']
      );
      $results = $this->model_catalog_product->getTabProducts($filter_data);

      $store_id = $this->config->get('config_store_id');
      $data['use_quickview'] = (int)$this->config->get('module_octhemeoption_quickview')[$store_id];
      $data['use_catalog'] = (int)$this->config->get('module_octhemeoption_catalog')[$store_id];


      if ($results) {
        foreach ($results as $result) {

          //++Andrey
          $mass_options = array();
          $prod_options = array();
          $is_buy = false;
          $on_stock = false;
          $images = array();

          $is_pawpaw = 1;
          $data['is_pawpaw'] = $is_pawpaw;

          $sort_order = "ASC";
          if (isset($this->request->get['order'])) {
            $sort_order = $this->request->get['order'];
          }

          if ($is_pawpaw) {
            $options = $this->model_catalog_product->getProductOptionPawPaw($result['product_id'], $sort_order);
            if (!empty($options)) {
              foreach ($options as $option) {
                if ($option['product_option_id'] > 0) {
                  if ($option['selected']) {
                    if ($option['quantity'] > 0) {
                      $is_buy = true;
                      $on_stock = true;
                    }
                    $mass_options += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];
                  }

                  if (isset($option['image_opt'])) {
                    $img_opt = !empty($option['image_opt'] && file_exists(DIR_IMAGE . $option['image_opt'])) ? $option['image_opt'] : "no_image.png";
                  } else {
                    $img_opt = "no_image.png";
                  }
                  $images[] = array(
                    'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                    'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                    'product_option_value_id' => (int)$option['product_option_id'] . "-" . (int)$option['product_option_value_id'],
                    'width' => $width,
                    'height' => $height,
                    'select' => $option['selected']
                  );

                  $prod_options[] = array(
                    'product_option_value_id' => (int)$option['product_option_value_id'],
                    'option_value_id' => (int)$option['option_value_id'],
                    'product_option_id' => $option['product_option_id'],
                    'name' => $option['name'],
                    'image' => $this->model_tool_image->resize($img_opt, 50, 50),
                    'qty' => $option['quantity'],
                    'selected' => $option['selected']
                  );
                } else {
                  if ($option['quantity'] > 0) {
                    $is_buy = true;
                    $on_stock = true;
                  }
                  if (isset($result['image'])) {
                    $img_opt = !empty($result['image'] && file_exists(DIR_IMAGE . $result['image'])) ? $result['image'] : "no_image.png";
                  } else {
                    $img_opt = "no_image.png";
                  }
                  $images[] = array(
                    'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                    'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                    'product_option_value_id' => (int)$option['product_option_id'] . "-" . (int)$option['product_option_value_id'],
                    'width' => $width,
                    'height' => $height,
                    'select' => $option['selected']
                  );
                }
              }
            } else {
              if (isset($result['image'])) {
                $img_opt = !empty($result['image'] && file_exists(DIR_IMAGE . $result['image'])) ? $result['image'] : "no_image.png";
              } else {
                $img_opt = "no_image.png";
              }

              if ($result['quantity'] > 0) {
                $is_buy = true;
                $on_stock = true;
              }

              $images[] = array(
                'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                'product_option_value_id' => (int)$result['product_id'],
                'width' => $width,
                'height' => $height,
                'select' => true
              );
            }
          } else {
            $options = $this->model_catalog_product->getProductOption($result['product_id']);
            if (!empty($options)) {
              foreach ($options as $option) {
                $product_option_value_data = array();
                foreach ($option['product_option_value'] as $option_value) {

                  if ($option['selected'] && $option_value['selected']) {
                    $mass_options += [(int)$option['product_option_id'] => (int)$option_value['product_option_value_id']];
                  }

                  if (isset($option_value['image_opt'])) {
                    $img_opt = !empty($option_value['image_opt'] && file_exists(DIR_IMAGE . $option_value['image_opt'])) ? $option_value['image_opt'] : "no_image.png";
                  } else {
                    $img_opt = "no_image.png";
                  }

                  $images[] = array(
                    'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                    'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                    'product_option_value_id' => (int)$option['product_option_id'] . "-" . (int)$option_value['product_option_value_id'],
                    'width' => $width,
                    'height' => $height,
                    'select' => $option['selected']
                  );

                  $product_option_value_data[] = array(
                    'product_option_value_id' => (int)$option_value['product_option_value_id'],
                    'option_value_id' => (int)$option_value['option_value_id'],
                    'name' => $option_value['name'],
                    'image' => $this->model_tool_image->resize($img_opt, 50, 50),
                    'price_prefix' => $option_value['price_prefix'],
                    'selected' => $option_value['selected'],
                    'qty' => $this->model_catalog_product->getProductOptStock($result['product_id'], [(int)$option['product_option_id'] => (int)$option_value['product_option_value_id']])
                  );
                }

                $prod_options[] = array(
                  'product_option_id' => $option['product_option_id'],
                  'product_option_value' => $product_option_value_data,
                  'option_id' => $option['option_id'],
                  'name' => $option['name'],
                  'type' => $option['type'],
                  'value' => $option['value'],
                  'required' => $option['required'],
                  'selected' => $option['selected']
                );
              }
            } else {
              if (isset($result['image'])) {
                $img_opt = !empty($result['image'] && file_exists(DIR_IMAGE . $result['image'])) ? $result['image'] : "no_image.png";
              } else {
                $img_opt = "no_image.png";
              }
              $images[] = array(
                'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                'product_option_value_id' => (int)$result['product_id'],
                'width' => $width,
                'height' => $height,
                'select' => true
              );
            }
          }

          $this->load->model('extension/module/discount');
          $prices_discount = $this->model_extension_module_discount->applyDisconts($result['product_id'], $mass_options);
          $price = $prices_discount['price'];
          $special = $prices_discount['special'] > 0 ? $prices_discount['special'] : false;
          $tax = $prices_discount['tax'] > 0 ? $prices_discount['tax'] : false;
          $percent = (int)$prices_discount['percent'] > 0 ? (int)$prices_discount['percent'] : false;

          $this->load->model('account/wishlist');
          $in_wishlist = $this->model_account_wishlist->getProductInWishlist($result['product_id']);

          //logic in qty cart
          $total_qty = 0;
          $total_qty_in_cart = 0;
          $in_cart = false;
          $cart_id = 0;

          $cart_prods = $this->cart->getProductByStore($result['product_id'], $mass_options);
          foreach ($cart_prods as $item_cart) {
            $total_qty = $total_qty_in_cart + $item_cart['total_quantity'];
            $total_qty_in_cart = $total_qty_in_cart + (int)$item_cart['quantity'];
            $cart_id = (int)$item_cart['cart_id'];
          }

          $total_qty_free = $total_qty - $total_qty_in_cart;
          $data['quantity'] = $total_qty_in_cart;

          if ($total_qty_free > 0){
            $is_buy = true;
          }

          // Преобразуем каждую пару ключ-значение в формат "ключ-значение" и объединяем их в строку
          if (!empty($mass_options)) {
            $key_opts = implode('-', array_map(
              function ($key, $value) {
                return "$key-$value";
              },
              array_keys($mass_options),
              $mass_options
            ));
            $uniq_id = $result['product_id'] . "-" . $key_opts;
          } else {
            $uniq_id = $result['product_id'];
          }

          if ($this->config->get('config_review_status')) {
            $rating = $result['rating'];
          } else {
            $rating = false;
          }

          $date_end = false;
          if ($setting['countdown']) {
            $date_end = $this->model_extension_module_ocproduct->getSpecialCountdown($result['product_id']);
            if ($date_end === '0000-00-00') {
              $date_end = false;
            }
          }

          if (!empty($mass_options)){
            $product_options = $this->model_catalog_product->getProductByOption($result['product_id'], $mass_options);
            $product_status_id = (int)$product_options['product_status_id'];
          }else{
            $product_status_id = (int)$result['product_status_id'];
          }
          if ($product_status_id > 0) {
            $statuses[$product_status_id] = $this->model_catalog_product_status->getProductStatus($product_status_id);
          }

          $product_data[] = array(
            'product_id' => $result['product_id'],
            'images' => $images,
            'options' => $prod_options,
            'name' => (utf8_strlen($result['name']) > 50 ? utf8_substr($result['name'], 0, 50) . '..' : $result['name']),
            'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $setting['des_limit']) . '..',
            'price' => $this->currency->format($price, $this->session->data['currency']),
            'rate_special' => $percent,
            'special' => $special > 0 ? $this->currency->format($special, $this->session->data['currency']) : 0,
            'rating' => $rating,
            'uniq_id' => $uniq_id,
            'cart_id' => $cart_id,
            'in_cart' => $in_cart,
            'is_buy' => $is_buy,
            'quantity' => $data['quantity'],
            'href' => $this->url->link('product/product', 'product_id=' . $result['product_id']),
            'date_end' => $date_end,
            'on_stock' => $on_stock,
            'in_wishlist' => $in_wishlist,
          );
        }
      }

      if (isset($octab['tab_name'][$data['code']]['title'])) {
        $title = $octab['tab_name'][$data['code']]['title'];
      } else {
        $title = 'Tab title';
      }

      $data['octabs'][] = array(
        'products' => $product_data,
        'thumbnail_image' => $thumbnail_image,
        'title' => $title
      );

    }
    //echo '<pre>'; print_r($data['octabs']); die;
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
      'subtitle_lang' => $setting['subtitle_lang'],
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
    // echo '<pre>'; print_r($data['octabs']); die;
    return $this->load->view('extension/module/octabproducts', $data);
  }

  public function getFirstProduts($products)
  {
    $trdProduct = array();
    $count = 0;
    foreach ($products as $product) {
      if ($count < 1) {
        $product_id = $product['product_id'];
        $trdProduct[] = $product;
      }
      $count++;
    }

    return $trdProduct;
  }

  public function getOtherExcpFirstProducts($products)
  {
    $excpTrdProducts = array();

    $count = 0;
    foreach ($products as $product) {
      if ($count > 1) {
        $excpTrdProducts[] = $product;
      }
      $count++;
    }

    return $excpTrdProducts;
  }
}
