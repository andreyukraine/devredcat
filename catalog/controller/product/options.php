<?php

class ControllerProductOptions extends Controller
{
  public function index($product_data)
  {
    $this->load->language('product/product');

    $this->load->model('catalog/product');
    $this->load->model('catalog/product_status');
    $this->load->model('tool/image');
    $this->load->model('account/customer');
    $this->load->model('account/wishlist');
    $this->load->model('extension/module/discount');

    $data['mass_options'] = isset($product_data['mass_options']) ? $product_data['mass_options'] : array();
    $data['prod_options'] = array();
    $data['is_buy'] = false;
    $data['on_stock'] = false;
    $data['images'] = array();
    $data['statuses'] = array();

    $data['is_mobile'] = $this->mobile_detect->isMobile();

    $width = 400;
    $height = 400;
    if ($data['is_mobile']) {
      $width = 300;
      $height = 300;
    }

    if (isset($product_data['detal'])){
      $width = 600;
      $height = 600;
      if ($data['is_mobile']) {
        $width = 300;
        $height = 300;
      }
    }

    $popup_width = 1000;
    $popup_height = 1000;

    $options = $this->model_catalog_product->getProductOptionPawPaw($product_data['item']['product_id'], $product_data['sort_order']);
    if (!empty($options)) {
      foreach ($options as $option) {
        if ($option['product_option_id'] > 0) {

          if (!empty($data['mass_options'])) {
            if (isset($data['mass_options'][$option['product_option_id']]) && (int)$data['mass_options'][$option['product_option_id']] == (int)$option['product_option_value_id']) {
              $option['selected'] = 1;
            } else {
              $option['selected'] = 0;
            }
          }

          if (isset($option['image_opt'])) {
            $img_opt = (!empty($option['image_opt']) && file_exists(DIR_IMAGE . $option['image_opt'])) ? $option['image_opt'] : "no_image.png";
          } else {
            $img_opt = "no_image.png";
          }
            if ($option['selected']) {
              $data['mass_options'] += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];

              $data['description'] = Helper::cleanProductDescription($option['description']);
              $data['composition'] = Helper::cleanProductDescription($option['composition']);

              if (isset($product_data['detal'])){
                $this->document->setOgImage($img_opt);
              }
          }

          $data['images'][] = array(
            'popup' => $this->model_tool_image->resize($img_opt, $popup_width, $popup_height),
            'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
            'product_option_value_id' => (int)$option['product_option_id'] . "-" . (int)$option['product_option_value_id'],
            'width' => $width,
            'height' => $height,
            'select' => $option['selected']
          );

          $opt_total_qty_in_cart = 0;
          $opt_cart_id = 0;
          $cart_opt_prods = $this->cart->getProductByStore($product_data['item']['product_id'], [(int)$option['product_option_id'] => (int)$option['product_option_value_id']]);
          foreach ($cart_opt_prods as $opt_cart) {
            $opt_total_qty_in_cart = $opt_total_qty_in_cart + (int)$opt_cart['quantity'];
            $opt_cart_id = (int)$opt_cart['cart_id'];
          }


          $opt_total_qty_free = $option['quantity'] - $opt_total_qty_in_cart;
          if ($opt_total_qty_free > 0) {
            $opt_is_buy = true;
          } else {
            $opt_is_buy = false;
          }

          if ($option['quantity'] > 0) {
            $opt_on_stock = true;
          } else {
            $opt_on_stock = false;
          }

          $prices_discount = $this->model_extension_module_discount->applyDisconts($product_data['item']['product_id'], [(int)$option['product_option_id'] => (int)$option['product_option_value_id']]);
          $opt_sku = $prices_discount["sku"];
          $opt_ean = $prices_discount["ean"];
          $opt_price = $prices_discount['price'];
          $opt_special = $prices_discount['special'] > 0 ? $prices_discount['special'] : false;
          $opt_percent = (int)$prices_discount['percent'] > 0 ? (int)$prices_discount['percent'] : false;

          $data['prod_options'][] = array(
            'product_option_value_id' => (int)$option['product_option_value_id'],
            'option_value_id' => (int)$option['option_value_id'],
            'product_option_id' => $option['product_option_id'],
            'name' => mb_strtolower($option['name'], 'UTF-8'),
            'image' => $this->model_tool_image->resize($img_opt, 50, 50),
            'qty' => $option['quantity'],
            'percent' => $option['discount_percent'],
            'selected' => $option['selected'],
            'uniq_id' => $product_data['item']['product_id'] ."-". (int)$option['product_option_id'] ."-". (int)$option['product_option_value_id'],
            'cart_id' => $opt_cart_id,
            'opt_total_qty_in_cart' => $opt_total_qty_in_cart,
            'is_buy' => $opt_is_buy,
            'on_stock' => $opt_on_stock,
            'sku' => $opt_sku,
            'ean' => $opt_ean,
            'price' => $this->currency->format($opt_price, $this->session->data['currency']),
            'special' => $opt_special > 0 ? $this->currency->format($opt_special, $this->session->data['currency']) : 0
          );
        } else {
          if (isset($product_data['item']['image'])) {
            $img_opt = (!empty($product_data['item']['image']) && file_exists(DIR_IMAGE . $product_data['item']['image'])) ? $product_data['item']['image'] : "no_image.png";
          } else {
            $img_opt = "no_image.png";
          }

          if (isset($product_data['detal'])){
            $this->document->setOgImage($img_opt);
          }

          $data['images'][] = array(
            'popup' => $this->model_tool_image->resize($img_opt, $popup_width, $popup_height),
            'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
            'product_option_value_id' => (int)$option['product_option_id'] . "-" . (int)$option['product_option_value_id'],
            'width' => $width,
            'height' => $height,
            'percent' => $option['discount_percent'],
            'select' => $option['selected']
          );
          $data['description'] = Helper::cleanProductDescription($product_data['item']['description']);
        }
      }
    } else {

      if (isset($product_data['item']['image'])) {
        $img_opt = (!empty($product_data['item']['image']) && file_exists(DIR_IMAGE . $product_data['item']['image'])) ? $product_data['item']['image'] : "no_image.png";
      } else {
        $img_opt = "no_image.png";
      }

      if (isset($product_data['detal'])){
        $this->document->setOgImage($img_opt);
      }

      $data['images'][] = array(
        'popup' => $this->model_tool_image->resize($img_opt, $width, $height),
        'thumb' => $this->model_tool_image->resize($img_opt, $width, $height),
        'product_option_value_id' => (int)$product_data['item']['product_id'],
        'width' => $width,
        'height' => $height,
        'percent' => 0,
        'select' => 0
      );
    }

    if (empty($data['description'])) {
      $data['description'] = Helper::cleanProductDescription($product_data['item']['description']);
    }

    $prices_discount = $this->model_extension_module_discount->applyDisconts($product_data['item']['product_id'], $data['mass_options']);
    $data['sku'] = $prices_discount["sku"];
    $data['ean'] = $prices_discount["ean"];
    $data['price'] = $prices_discount['price'];
    $data['special'] = $prices_discount['special'] > 0 ? $prices_discount['special'] : false;
    $data['percent'] = (int)$prices_discount['percent'] > 0 ? (int)$prices_discount['percent'] : false;

    // Приклад дати у форматі 'Y-m-d'
    $dateEndObj = DateTime::createFromFormat('Y-m-d', $prices_discount['date_end']);
    $currentDate = new DateTime();
    if ($dateEndObj > $currentDate) {
      $data['date_end'] = $dateEndObj->format('Y-m-d H:i:s');
    } else {
      $data['date_end'] = false;
    }

    $data['in_wishlist'] = $this->model_account_wishlist->getProductInWishlist($product_data['item']['product_id']);

    //logic in qty cart
    $data['total_qty'] = 0;
    $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_data['item']['product_id'], $data['mass_options'], true);
    if (!empty($stocks)) {
      foreach ($stocks as $stock) {
        $data['total_qty'] += $stock;
      }
    }

    if ($data['total_qty'] > 0) {
      $data['on_stock'] = true;
    } else {
      $data['on_stock'] = false;
    }

    $data['total_qty_in_cart'] = 0;
    $data['in_cart'] = false;
    $data['cart_id'] = 0;

    $cart_prods = $this->cart->getProductByStore($product_data['item']['product_id'], $data['mass_options']);
    foreach ($cart_prods as $item_cart) {
      $data['total_qty_in_cart'] = $data['total_qty_in_cart'] + (int)$item_cart['quantity'];
      $data['cart_id'] = (int)$item_cart['cart_id'];
    }

    $total_qty_free = $data['total_qty'] - $data['total_qty_in_cart'];
    $data['quantity'] = $data['total_qty_in_cart'];

    if ($total_qty_free > 0) {
      $data['is_buy'] = true;
    } else {
      $data['is_buy'] = false;
    }

    // Преобразуем каждую пару ключ-значение в формат "ключ-значение" и объединяем их в строку
    if (!empty($data['mass_options'])) {
      $key_opts = implode('-', array_map(
        function ($key, $value) {
          return "$key-$value";
        },
        array_keys($data['mass_options']),
        $data['mass_options']
      ));
      $data['uniq_id'] = $product_data['item']['product_id'] . "-" . $key_opts;
    } else {
      $data['uniq_id'] = $product_data['item']['product_id'];
    }

    //статус товару
    $product_status_id = (int)$product_data['item']['product_status_id'];

    if (!empty($data['mass_options'])){
      $product_options = $this->model_catalog_product->getProductByOption($product_data['item']['product_id'], $data['mass_options']);
      $product_status_id = (int)$product_options['product_status_id'];
    }

    if ($product_status_id > 0) {
      $data['statuses'][$product_status_id] = $this->model_catalog_product_status->getProductStatus($product_status_id);
    }

    return $data;
  }

}
