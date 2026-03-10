<?php

class ControllerCommonCart extends Controller
{
  public function index()
  {
    $this->load->language('common/cart');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('tool/image');
    $this->load->model('tool/upload');
    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocwarehouses');

    $data['products'] = array();
    $data['store'] = false;

    $data['is_mobile'] = $this->mobile_detect->isMobile();

    $width = 400;
    $height = 400;

    if ($data['is_mobile']) {
      $width = 200;
      $height = 200;
    }

    $products = $this->cart->getProducts();
    if (!empty($products)) {
      unset($this->session->data['orders']);

      if ($this->config->get('module_custom_check_qty_cart_by_store') != null) {

        $data['store'] = true;

        // Группируем товары по warehouse_id
        $ordersByStore = [];

        foreach ($products as $item) {
          $warehouse_id = $item['warehouse_id'];

          if (!isset($ordersByStore[$warehouse_id])) {
            $ordersByStore[$warehouse_id] = [];
          }

          $image = "no_image.png";
          if (!empty($item['image']) && file_exists(DIR_IMAGE.$item['image'])){
            $image = $item['image'];
          }
          if (!empty($item['option_data'])) {
            foreach ($item['option_data'] as $option) {
              if (!empty($option['image_opt']) && file_exists(DIR_IMAGE.$option['image_opt'])) {
                $image = $option['image_opt'];
              }
            }
          }

          $price = $item['price'];
          $special = $item['special'] > 0 ? $item['special'] : false;
          $percent = (int)$item['percent'] > 0 ? (int)$item['percent'] : false;

          // Обработка опций
          $mass_options = [];
          if (isset($item['option_data'])) {
            foreach ($item['option_data'] as $option) {
              $mass_options[(int)$option['product_option_id']] = (int)$option['product_option_value_id'];
            }
          }

          $ordersByStore[$warehouse_id][] = [
            'cart_id' => $item['cart_id'],
            'warehouse_id' => $item['warehouse_id'],
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'price' => $price,
            'special' => $special,
            'percent' => $percent,
            'option' => $item['option_data'],
            'mass_options' => $mass_options,
            'model' => $item['model'],
            'image' => $image,
            'href' => $this->url->link('product/product', 'product_id=' . $item['product_id'])
          ];
        }

        // Формируем данные для отображения
        foreach ($ordersByStore as $warehouse_id => $items) {
          $warehouse_info = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);

          $warehouse_prods = array();
          foreach ($items as $prod) {
            $product_info = $this->model_catalog_product->getProduct($prod['product_id']);

            $image = $this->model_tool_image->resize($prod['image'], $width, $height);

            if ($prod['special'] > 0) {
              $total = $prod['special'] * $prod['quantity'];
            } else {
              $total = $prod['price'] * $prod['quantity'];
            }

            $price_view = $this->currency->format($prod['price'], $this->session->data['currency']);
            $price_total_item_view = $this->currency->format($prod['price'] * $prod['quantity'], $this->session->data['currency']);
            $special_view = $prod['special'] > 0 ? $this->currency->format($prod['special'], $this->session->data['currency']) : false;
            $special_total_item_view = $prod['special'] > 0 ? $this->currency->format($total, $this->session->data['currency']) : false;

            // Формируем уникальный ID для товара с опциями
            if (!empty($prod['mass_options'])) {
              ksort($prod['mass_options']);
              $key_opts = implode('-', array_map(
                function ($key, $value) {
                  return "$key-$value";
                },
                array_keys($prod['mass_options']),
                $prod['mass_options']
              ));
              $uniq_id = $prod['product_id'] . "-" . $key_opts;
            } else {
              $uniq_id = $prod['product_id'];
            }

            $total_qty = 0;
            $stocks = $this->model_catalog_product->getProductOptStockWarehouse($prod['product_id'], $warehouse_id, $prod['mass_options']);
            if (!empty($stocks)) {
              foreach ($stocks as $stock) {
                $total_qty += (int)$stock;
              }
            }

            $qty_in_cart = 0;
            $cart_prods = $this->cart->getProductByStore($prod['product_id'], $prod['mass_options'], $warehouse_id);
            foreach ($cart_prods as $item_cart) {
              $qty_in_cart += (int)$item_cart['quantity'];
            }

            $is_buy = ($total_qty - $qty_in_cart > 0);

            $warehouse_prods[] = [
              'cart_id' => $prod['cart_id'],
              'warehouse_id' => $prod['warehouse_id'],
              'model' => $prod['model'],
              'name' => $product_info['name'],
              'option' => $prod['option'],
              'product_id' => $prod['product_id'],
              'image' => $image,
              'quantity' => (int)$prod['quantity'],
              'price' => $prod['price'],
              'price_view' => $price_view,
              'uniq_id' => $uniq_id,
              'is_buy' => $is_buy,
              'total' => $total,
              'price_total_item_view' => $price_total_item_view,
              'special' => $prod['special'],
              'special_view' => $special_view,
              'special_total_item_view' => $special_total_item_view,
              'percent' => $prod['percent'],
              'href' => $prod['href']
            ];
          }

          $data['stores'][$warehouse_id]['products'] = $warehouse_prods;
          $data['stores'][$warehouse_id]['warehouse'] = $warehouse_info;
          $this->session->data['orders'][$warehouse_id] = $warehouse_prods;

        }

      } else {
        foreach ($products as $product) {

          //++Andrey
          $option_data = array();
          $mass_options = array();

          $image = "no_image.png";

          if (!empty($product['option_data'])) {
            foreach ($product['option_data'] as $option) {

              if (!empty($option['image_opt']) && file_exists(DIR_IMAGE.$option['image_opt'])) {
                $image = $option['image_opt'];
              }

              if ($option['type'] != 'file') {
                $value = $option['value'];
              } else {
                $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                if ($upload_info) {
                  $value = $upload_info['name'];
                } else {
                  $value = '';
                }
              }

              $mass_options += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];

              $option_data[] = array(
                'name' => $option['name'],
                'value' => (utf8_strlen($value) > 60 ? utf8_substr($value, 0, 60) . '..' : $value)
              );
            }
          } else {
            if (!empty($product['image']) && file_exists(DIR_IMAGE.$product['image'])){
              $image = $product['image'];
            }
          }

          $image = $this->model_tool_image->resize($image, $width, $height);

          $price = $product['price'];
          $price_view = $this->currency->format($price, $this->session->data['currency']);
          $price_total_item_view = $this->currency->format($price * $product['quantity'], $this->session->data['currency']);

          $special = $product['special'] > 0 ? $product['special'] : false;
          $special_view = $product['special'] > 0 ? $this->currency->format($special, $this->session->data['currency']) : false;
          $special_total_item_view = $special > 0 ? $this->currency->format($special * $product['quantity'], $this->session->data['currency']) : false;

          $percent = (int)$product['percent'] > 0 ? (int)$product['percent'] : false;

          $product_info = $this->model_catalog_product->getProduct($product['product_id']);

          // Преобразуем каждую пару ключ-значение в формат "ключ-значение" и объединяем их в строку
          if (!empty($mass_options)) {
            ksort($mass_options);
            $key_opts = implode('-', array_map(
              function ($key, $value) {
                return "$key-$value";
              },
              array_keys($mass_options),
              $mass_options
            ));
            $uniq_id = $product['product_id'] . "-" . $key_opts;
          } else {
            $uniq_id = $product['product_id'];
          }

          $total_qty = 0;
          $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product['product_id'], $mass_options, true);
          if (!empty($stocks)) {
            foreach ($stocks as $stock) {
              $total_qty = $total_qty + (int)$stock;
            }
          }

          $qty_in_cart = 0;
          $cart_prods = $this->cart->getProductByStore($product['product_id'], $mass_options);
          if (!empty($cart_prods)) {
            foreach ($cart_prods as $item_cart) {
              $qty_in_cart += (int)$item_cart['quantity'];
            }
          }

          $is_buy = ($total_qty - $qty_in_cart > 0);

          $data['warehouse_id'] = (int)$this->config->get('config_warehouse_id');

          $data['products'][] = array(
            'cart_id' => $product['cart_id'],
            'product_id' => $product['product_id'],
            'image' => $image,
            'name' => $product['name'],
            'sku' => $product_info["sku"],
            'option' => $option_data,
            'uniq_id' => $uniq_id,
            'is_buy' => $is_buy,
            'warehouse_id' => $product['warehouse_id'],
            'quantity' => $product['quantity'],
            'price' => $price_view,
            'price_total_item_view' => $price_total_item_view,
            'special' => $special_view,
            'special_total_item_view' => $special_total_item_view,
            'percent' => $percent,
            'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
          );

        }
      }
    }

    // Gift Voucher
    $data['vouchers'] = array();

    if (!empty($this->session->data['vouchers'])) {
      foreach ($this->session->data['vouchers'] as $key => $voucher) {
        $data['vouchers'][] = array(
          'key' => $key,
          'description' => $voucher['description'],
          'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency'])
        );
      }
    }

    $data['totals'] = array();
    $result = $this->getTotals();
    
    $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
    $total_value = $result['total'];

    foreach ($result['totals'] as $total_row) {
      $code = $total_row['code'] ?? '';
      
      // Ховаємо доставку в боковому кошику
      if ($code == 'shipping' || $code == 'np' || $code == 'total' || $code == 'product_discount') continue;

      $title = $total_row['title'] ?? '';
      $value = isset($total_row['value']) ? (float)$total_row['value'] : 0.0;

      $text = $this->currency->format($value, $currency);
      
      if (empty($text) && $value == 0) {
        $text = '0₴';
      } elseif (empty($text)) {
        $text = number_format($value, 2, '.', '') . '₴';
      }

      $data['totals'][] = array(
        'title' => $title,
        'text'  => $text
      );
    }

    // Завжди додаємо "До сплати" останнім рядком
    $text_total = $this->currency->format($total_value, $currency);
    if (empty($text_total)) {
      $text_total = number_format($total_value, 2, '.', '') . '₴';
    }

    $data['totals'][] = array(
      'title' => 'До сплати',
      'text'  => $text_total
    );

    $data['module_custom_fastorder'] = $this->config->get('module_custom_fastorder');

    //режим каталогу
    if ($this->config->get('config_is_catalog')) {
      $data['config_is_catalog'] = $this->config->get('config_is_catalog');
    }

    $data['cart'] = $this->url->link('checkout/cart');
    $data['checkout'] = $this->url->link('checkout/checkout', '', true);

    $data['logged'] = $this->customer->isLogged();

    return $this->load->view('common/cart_fixed', $data);
  }

  public function getTotals(){

    $this->load->model('setting/extension');

    $totals = array();
    $taxes = $this->cart->getTaxes();
    $total = 0;

    // Because __call can not keep var references so we put them into an array.
    $total_data = array(
      'totals' => &$totals,
      'taxes'  => &$taxes,
      'total'  => &$total
    );

    // Display prices
    $sort_order = array();

    $results = $this->model_setting_extension->getExtensions('total');

    foreach ($results as $key => $value) {
      $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
    }

    array_multisort($sort_order, SORT_ASC, $results);

    foreach ($results as $result) {
      if ($this->config->get('total_' . $result['code'] . '_status')) {
        $this->load->model('extension/total/' . $result['code']);

        // We have to put the totals in an array so that they pass by reference.
        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
      }
    }

    $sort_order = array();

    foreach ($totals as $key => $value) {
      $sort_order[$key] = $value['sort_order'];
    }

    array_multisort($sort_order, SORT_ASC, $totals);


    return array(
      'total' => $total,
      'totals' => $totals
    );

  }

  public function top()
  {
    $this->load->language('common/cart');
    // Totals
    $this->load->model('setting/extension');
    $data['text_items'] = $this->cart->countProducts();
    return $this->load->view('common/cart_top', $data);
  }

  public function info()
  {
    $this->response->setOutput($this->index());
  }

}
