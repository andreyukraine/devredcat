<?php

class ControllerExtensionModuleOcproductviews extends Controller
{
  public function index($setting)
  {

    static $module = 0;

    $this->load->language('extension/module/ocproductviews');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');

    $data['heading_title'] = (isset($setting['heading'][(int)$this->config->get('config_language_id')]) && !empty($setting['heading'][(int)$this->config->get('config_language_id')])) ? $setting['heading'][(int)$this->config->get('config_language_id')] : $this->language->get('heading_title');

    $data['position'] = isset($setting['position']) ? $setting['position'] : '';
    $data['show_type'] = isset($setting['show_type']) ? $setting['show_type'] : '';
    $data['module_id'] = $setting['module_id'];

    $data['products'] = $products = [];

    if (isset($this->request->cookie['ocproductviews'])) {
      $products = explode(',', $this->request->cookie['ocproductviews']);
    } elseif (isset($this->session->data['ocproductviews'])) {
      $products = $this->session->data['ocproductviews'];
    }

    if (isset($this->request->cookie['viewed'])) {
      $products = array_merge($products, explode(',', $this->request->cookie['viewed']));
    } elseif (isset($this->session->data['viewed'])) {
      $products = array_merge($products, $this->session->data['viewed']);
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

    $products = array_slice($products, 0, 12);

    if (!empty($products)) {
      $oct_product_stickers = [];

      foreach ($products as $product_id) {
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {

          //++Andrey
          $mass_options = array();
          $prod_options = array();
          $is_buy = false;
          $on_stock = false;

          $images = array();
          $statuses = array();

          $is_pawpaw = 1;
          $data['is_pawpaw'] = $is_pawpaw;

          $sort_order = "ASC";
          if (isset($this->request->get['order'])){
            $sort_order = $this->request->get['order'];
          }

          if ($is_pawpaw) {
            $options = $this->model_catalog_product->getProductOptionPawPaw($product_info['product_id'], $sort_order);
            if (!empty($options)) {
              foreach ($options as $option) {
                if ($option['product_option_id'] > 0) {
                  if ($option['selected']) {
                    if ($option['quantity'] > 0){
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
                }else{
                  if ($option['quantity'] > 0){
                    $is_buy = true;
                    $on_stock = true;
                  }
                  if (isset($product_info['image'])) {
                    $img_opt = !empty($product_info['image'] && file_exists(DIR_IMAGE . $product_info['image'])) ? $product_info['image'] : "no_image.png";
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
              if (isset($product_info['image'])) {
                $img_opt = !empty($product_info['image'] && file_exists(DIR_IMAGE . $product_info['image'])) ? $product_info['image'] : "no_image.png";
              } else {
                $img_opt = "no_image.png";
              }

              if ($product_info['quantity'] > 0){
                $is_buy = true;
                $on_stock = true;
              }

              $images[] = array(
                'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                'product_option_value_id' => (int)$product_info['product_id'],
                'width' => $width,
                'height' => $height,
                'select' => true
              );
            }
          } else {
            $options = $this->model_catalog_product->getProductOption($product_info['product_id']);
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
                    'image' => isset($option_value['image']) ? $this->model_tool_image->resize($option_value['image'], 50, 50) : "",
                    'price_prefix' => $option_value['price_prefix'],
                    'selected' => $option_value['selected'],
                    'qty' => $this->model_catalog_product->getProductOptStock($product_info['product_id'], [(int)$option['product_option_id'] => (int)$option_value['product_option_value_id']])
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
              if (isset($product_info['image'])) {
                $img_opt = !empty($product_info['image'] && file_exists(DIR_IMAGE . $product_info['image'])) ? $product_info['image'] : "no_image.png";
              } else {
                $img_opt = "no_image.png";
              }

              $images[] = array(
                'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
                'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
                'product_option_value_id' => (int)$product_info['product_id'],
                'width' => $width,
                'height' => $height,
                'select' => true
              );
            }
          }

          $this->load->model('extension/module/discount');
          $prices_discount = $this->model_extension_module_discount->applyDisconts($product_info['product_id'], $mass_options);
          $price = $prices_discount['price'];
          $special = $prices_discount['special'] > 0 ? $prices_discount['special'] : false;
          $tax = $prices_discount['tax'] > 0 ? $prices_discount['tax'] : false;
          $percent = (int)$prices_discount['percent'] > 0 ? (int)$prices_discount['percent'] : false;

          $this->load->model('account/wishlist');
          $in_wishlist = $this->model_account_wishlist->getProductInWishlist($product_info['product_id']);

          //logic in qty cart
          $total_qty = 0;
          $total_qty_in_cart = 0;
          $in_cart = false;
          $cart_id = 0;

          $cart_prods = $this->cart->getProductByStore($product_info['product_id'], $mass_options);
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
            $uniq_id = $product_info['product_id'] . "-" . $key_opts;
          } else {
            $uniq_id = $product_info['product_id'];
          }

          if ($this->config->get('config_review_status')) {
            $rating = (int)$product_info['rating'];
          } else {
            $rating = false;
          }

          if (!empty($mass_options)){
            $product_options = $this->model_catalog_product->getProductByOption($product_info['product_id'], $mass_options);
            $product_status_id = (int)$product_options['product_status_id'];
          }else{
            $product_status_id = (int)$product_info['product_status_id'];
          }
          if ($product_status_id > 0) {
            $statuses[$product_status_id] = $this->model_catalog_product_status->getProductStatus($product_status_id);
          }

          $data['products'][] = [
            'product_id' => $product_id,
            'images' => $images,
            'options' => $prod_options,
            'name' => (utf8_strlen($product_info['name']) > 60 ? utf8_substr($product_info['name'], 0, 60) . '..' : $product_info['name']),
            'oct_model' => $this->config->get('theme_oct_remarket_data_model') ? $product_info['model'] : '',
            'description' => utf8_substr(trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
            'price' => $this->currency->format($price, $this->session->data['currency']),
            'special' => $special > 0 ? $this->currency->format($special, $this->session->data['currency']) : false,
            'rate_special' => $percent,
            'quantity' => $data['quantity'],
            'rating' => $rating,
            'uniq_id' =>$uniq_id,
            'cart_id' => $cart_id,
            'in_cart' => $in_cart,
            'is_buy'  => $is_buy,
            'on_stock' => $on_stock,
            'reviews' => $product_info['reviews'],
            'statuses' => $statuses,
            'href' => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
          ];
        }
      }
    }

    $data['module'] = $module++;

    if ($data['products']) {
      if (isset($setting['mobile']) && $setting['mobile']) {
        return $data['products'];
      } else {
        $data['module_name'] = "pv";

        return $this->load->view('extension/module/ocproductviews', $data);
      }
    }
  }
}
