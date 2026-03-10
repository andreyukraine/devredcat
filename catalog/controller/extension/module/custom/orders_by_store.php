<?php

class ControllerExtensionModuleCustomOrdersByStore extends Controller
{
  public function index($setting = array())
  {
    if (empty($setting)) {
      $setting = $this->model_setting_setting->getSetting('module_custom');
    }

    // Блок отображается
    $this->load->language('checkout/cart');
    $this->load->language('extension/module/custom/cart');

    $this->load->model('tool/image');
    $this->load->model('tool/upload');
    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocwarehouses');

    $products = $this->cart->getProducts();

    // Группируем товары по warehouse_id
    $ordersByStore = [];

    if (!empty($products)) {
      foreach ($products as $item) {
        $warehouse_id = $item['warehouse_id'];

        if (!isset($ordersByStore[$warehouse_id])) {
          $ordersByStore[$warehouse_id] = [];
        }

        $price = $item['price'];
        $special = $item['special'] > 0 ? $item['special'] : false;
        $percent = (int)$item['percent'] > 0 ? (int)$item['percent'] : false;

        // Обработка опций
        $mass_options = [];
        if (isset($item['option'])) {
          foreach ($item['option'] as $option) {
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
          'option' => $item['option'],
          'mass_options' => $mass_options,
          'model' => $item['model'],
          'image' => $item['image'],
          'href' => $this->url->link('product/product', 'product_id=' . $item['product_id'])
        ];
      }

      unset($this->session->data['orders']);
      $data['orders'] = [];

      // Формируем данные для отображения
      foreach ($ordersByStore as $warehouse_id => $items) {
        $warehouse_info = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);
        $total_order = 0;
        $session_products = [];

        foreach ($items as $prod) {
          $product_info = $this->model_catalog_product->getProduct($prod['product_id']);

          $image = $prod['image'];
          if (!empty($prod['option'])) {
            foreach ($prod['option'] as $option) {
              $image = !empty($option['image_opt']) ? $option['image_opt'] : "no_image.png";
            }
          }

          if ($prod['special'] > 0) {
            $total = $prod['special'] * $prod['quantity'];
          } else {
            $total = $prod['price'] * $prod['quantity'];
          }
          $total_order += $total;

          $price_view = $this->currency->format($prod['price'], $this->session->data['currency']);
          $price_total_item_view = $this->currency->format($prod['price'] * $prod['quantity'], $this->session->data['currency']);
          $special_view = $prod['special'] > 0 ? $this->currency->format($prod['special'], $this->session->data['currency']) : false;
          $special_total_item_view = $prod['special'] > 0 ? $this->currency->format($total, $this->session->data['currency']) : false;

          // Формируем уникальный ID для товара с опциями
          if (!empty($prod['mass_options'])) {
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

          $session_products[] = [
            'cart_id' => $prod['cart_id'],
            'warehouse_id' => $prod['warehouse_id'],
            'model' => $prod['model'],
            'name' => $product_info['name'],
            'option' => $prod['option'],
            'product_id' => $prod['product_id'],
            'image' => $this->model_tool_image->resize($image, 150, 150),
            'quantity' => (int)$prod['quantity'],
            'price' => $prod['price'],
            'price_view' => $price_view,
            'uniq_id' => $uniq_id,
            'is_buy' => true, // Все товары доступны, так как не проверяем остатки
            'total' => $total,
            'price_total_item_view' => $price_total_item_view,
            'special' => $prod['special'],
            'special_view' => $special_view,
            'special_total_item_view' => $special_total_item_view,
            'percent' => $prod['percent'],
            'href' =>  $prod['href']
          ];
        }

        $this->session->data['orders'][$warehouse_id] = $session_products;

        $data['orders'][$warehouse_id]['products'] = $session_products;
        if ($warehouse_info != null) {
          $data['orders'][$warehouse_id]['warehouse'] = $warehouse_info['name'];
        }else{
          $data['orders'][$warehouse_id]['warehouse'] = "Не вказаний склад";
        }
        $data['orders'][$warehouse_id]['total_order'] = $this->currency->format($total_order, $this->session->data['currency']);

        // Обработка доставки и оплаты
        $setting['module_custom_shipping']['module_custom_split_order_store'] = $setting['module_custom_split_order_store'];
        $setting['module_custom_shipping']['order_id'] = $warehouse_id;
        $setting['module_custom_shipping']['products'] = $items;

        $data['orders'][$warehouse_id]['shipping'] = $this->getChildController('shipping', $setting['module_custom_shipping']);

        $setting['module_custom_payment']['order_id'] = $warehouse_id;
        $data['orders'][$warehouse_id]['payment'] = $this->getChildController('payment', $setting['module_custom_payment']);
      }
    }

    $data['setting'] = $setting;

    return $this->load->view('extension/module/custom/orders_by_store', $data);
  }

  public function getChildController($name, $setting)
  {
    return $this->load->controller('extension/module/custom/' . $name, $setting);
  }
}
