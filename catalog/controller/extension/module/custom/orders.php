<?php
class ControllerExtensionModuleCustomOrders extends Controller
{
  public function index($setting = array())
  {
    if (empty($setting)) {
      $setting = $this->model_setting_setting->getSetting('module_custom');
    }

    $this->load->language('checkout/cart');
    $this->load->language('extension/module/custom/cart');
    $this->load->model('tool/image');
    $this->load->model('catalog/product');
    $this->load->model('extension/module/ocwarehouses');

    $products = $this->cart->getProducts();
    $orders = $this->groupOrdersOptimally($products); // Оптимальная группировка
    $data['orders'] = $this->prepareOrdersData($orders, $setting);
    $data['setting'] = $setting;

    return $this->load->view('extension/module/custom/orders', $data);
  }

  /**
   * Группирует товары так, чтобы максимум товаров было из одного склада
   */
  protected function groupOrdersOptimally($products)
  {
    $orders = [];

    foreach ($products as $item) {
      $productId = $item['product_id'];
      $massOptions = $this->getMassOptions($item['option'] ?? []);
      $requiredQty = $item['quantity'];

      // Шукаємо склади з достатньою кількістю
      $stocks = $this->model_catalog_product->getProductOptStockWarehouses(
        $productId,
        $massOptions
      );

      $distributed = false;

      foreach ($stocks as $warehouseId => $availableQty) {
        if ($availableQty >= $requiredQty) {
          if (!isset($orders[$warehouseId])) {
            $orders[$warehouseId] = [];
          }
          $orders[$warehouseId][] = $this->prepareOrderItem($item);
          $distributed = true;
          break;
        }
      }

      // Якщо не знайшли підходящий склад
      if (!$distributed) {
        if (!isset($orders['none'])) {
          $orders['none'] = [];
        }
        $orders['none'][] = $this->prepareOrderItem($item);
      }
    }

    return $orders;
  }

  protected function getAvailableWarehousesForProduct($productId, $massOptions, $warehouseStocks)
  {
    $available = [];

    foreach ($warehouseStocks as $warehouseId => $products) {
      foreach ($products as $pid => $data) {
        if ($pid == $productId && $data['mass_options'] == $massOptions && $data['quantity'] > 0) {
          $available[$warehouseId] = $data['quantity'];
        }
      }
    }

    return $available;
  }

  protected function addToOrder(&$orders, $warehouseId, $item, $quantity)
  {
    if (!isset($orders[$warehouseId])) {
      $orders[$warehouseId] = [];
    }

    $itemCopy = $item;
    $itemCopy['quantity'] = $quantity;

    $orders[$warehouseId][] = $this->prepareOrderItem($itemCopy);
  }

  protected function getDistributedQuantity($orders, $productId, $massOptions)
  {
    $total = 0;

    foreach ($orders as $warehouseItems) {
      foreach ($warehouseItems as $item) {
        if ($item['product_id'] == $productId && $item['mass_options'] == $massOptions) {
          $total += $item['quantity'];
        }
      }
    }

    return $total;
  }

  protected function prepareOutOfStockProducts($products)
  {
    $items = [];

    foreach ($products as $item) {
      $items[] = $this->prepareOrderItem($item);
    }

    return $items;
  }

  protected function groupAvailableProducts($products, $warehousePriority)
  {
    $orders = [];
    $remainingProducts = $products;

    // Сортуємо склади за пріоритетом
    arsort($warehousePriority);

    foreach ($warehousePriority as $warehouseId => $score) {
      if (empty($remainingProducts)) break;

      $warehouseItems = [];
      $newRemainingProducts = [];

      foreach ($remainingProducts as $item) {
        $productId = $item['product_id'];
        $requiredQty = $item['quantity'];
        $massOptions = $this->getMassOptions($item['option'] ?? []);

        $stocks = $this->model_catalog_product->getProductOptStockWarehouse(
          $productId,
          $warehouseId,
          $massOptions
        );
        $availableQty = array_sum($stocks);

        if ($availableQty > 0) {
          $addQty = min($availableQty, $requiredQty);

          // Створюємо копію товару з актуальною кількістю
          $warehouseItem = $this->prepareOrderItem($item);
          $warehouseItem['quantity'] = $addQty;
          $warehouseItems[] = $warehouseItem;

          // Якщо залишилась не розподілена кількість
          if ($addQty < $requiredQty) {
            $remainingItem = $item;
            $remainingItem['quantity'] = $requiredQty - $addQty;
            $newRemainingProducts[] = $remainingItem;
          }
        } else {
          $newRemainingProducts[] = $item;
        }
      }

      if (!empty($warehouseItems)) {
        $orders[$warehouseId] = $warehouseItems;
      }

      $remainingProducts = $newRemainingProducts;
    }

    // Додаємо залишки, які не вмістились на основні склади
    if (!empty($remainingProducts)) {
      foreach ($remainingProducts as $item) {
        $warehouseId = $this->findBestWarehouseForItem($item, $warehousePriority);

        if ($warehouseId) {
          if (!isset($orders[$warehouseId])) {
            $orders[$warehouseId] = [];
          }
          $orders[$warehouseId][] = $this->prepareOrderItem($item);
        } else {
          // Додаємо до відсутніх товарів
          if (!isset($orders['none'])) {
            $orders['none'] = [];
          }
          $orders['none'][] = $this->prepareOrderItem($item);
        }
      }
    }

    return $orders;
  }

  protected function prepareOrderItem($item)
  {
    $productId = $item['product_id'];
    $massOptions = $this->getMassOptions($item['option'] ?? []);

    return [
      'cart_id'      => $item['cart_id'],
      'warehouse_id'    => $item['warehouse_id'],
      'product_id'   => $productId,
      'quantity'     => $item['quantity'], // Важливо: зберігаємо оригінальну кількість
      'price'        => $item['price'],
      'special'      => $item['special'] > 0 ? $item['special'] : false,
      'percent'      => (int)$item['percent'] > 0 ? (int)$item['percent'] : false,
      'option'       => $item['option'] ?? [],
      'mass_options' => $massOptions,
      'model'        => $item['model'],
      'image'        => $item['image'],
      'href'         => $this->url->link('product/product', 'product_id=' . $productId)
    ];
  }
  protected function findBestWarehouseForRemainingItem($item, $warehousePriority, $existingOrders)
  {
    $productId = $item['product_id'];
    $massOptions = $this->getMassOptions($item['option'] ?? []);

    // Спочатку перевіряємо склади, де вже є частина цього замовлення
    foreach ($existingOrders as $warehouseId => $items) {
      if ($warehouseId === 'none') continue;

      foreach ($items as $orderItem) {
        if ($orderItem['product_id'] == $productId &&
          $orderItem['mass_options'] == $massOptions) {
          // Перевіряємо, чи є ще вільний залишок на цьому складі
          $stocks = $this->model_catalog_product->getProductOptStockWarehouse(
            $productId,
            $warehouseId,
            $massOptions
          );
          $availableQty = array_sum($stocks);
          $usedQty = $this->getUsedQuantity($existingOrders[$warehouseId], $productId, $massOptions);

          if ($availableQty > $usedQty) {
            return $warehouseId;
          }
        }
      }
    }

    // Якщо не знайшли на існуючих складах, шукаємо будь-який інший склад
    $stocks = $this->model_catalog_product->getProductOptStockWarehouses(
      $productId,
      $massOptions
    );

    if (empty($stocks)) return false;

    // Сортуємо склади за кількістю доступного товару (за спаданням)
    arsort($stocks);

    // Повертаємо перший склад з найбільшою кількістю
    return array_key_first($stocks);
  }


  protected function getUsedQuantity($orderItems, $productId, $massOptions)
  {
    $usedQty = 0;

    foreach ($orderItems as $item) {
      if ($item['product_id'] == $productId && $item['mass_options'] == $massOptions) {
        $usedQty += $item['quantity'];
      }
    }

    return $usedQty;
  }

  protected function findBestWarehouseForItem($item, $warehousePriority)
  {
    $productId = $item['product_id'];
    $massOptions = $this->getMassOptions($item['option'] ?? []);

    $stocks = $this->model_catalog_product->getProductOptStockWarehouses(
      $productId,
      $massOptions
    );

    if (empty($stocks)) return false;

    // Сортуємо склади за кількістю доступного товару (за спаданням)
    arsort($stocks);

    // Повертаємо перший склад з найбільшою кількістю
    return array_key_first($stocks);
  }


  /**
   * Определяет приоритет складов (где больше всего товаров из корзины)
   */
  protected function getWarehousesPriority($products)
  {
    $warehouseScores = [];

    foreach ($products as $item) {
      $productId = $item['product_id'];
      $massOptions = $this->getMassOptions($item['option'] ?? []);

      $stocks = $this->model_catalog_product->getProductOptStockWarehouses(
        $productId,
        $massOptions
      );

      foreach ($stocks as $warehouseId => $qty) {
        if (!isset($warehouseScores[$warehouseId])) {
          $warehouseScores[$warehouseId] = 0;
        }
        $warehouseScores[$warehouseId] += min($qty, $item['quantity']);
      }
    }

    arsort($warehouseScores); // Сначала склады с максимальным покрытием
    return $warehouseScores;
  }

  /**
   * Подготавливает данные для отображения заказов
   */
  protected function prepareOrdersData($orders, $setting)
  {
    $data = [];

    unset($this->session->data['orders']);
    $this->session->data['orders'] = [];

    foreach ($orders as $warehouseId => $items) {
      $warehouseInfo = $warehouseId === 'none'
        ? ['name' => $this->language->get('text_no_stock_warehouse')]
        : $this->model_extension_module_ocwarehouses->getWarehouseId($warehouseId);

      $totalOrder = 0;
      $sessionProducts = [];
      foreach ($items as $item) {
        $productInfo = $this->model_catalog_product->getProduct($item['product_id']);
        $image = $this->getProductImage($item);
        $total = $item['special'] ? $item['special'] * $item['quantity'] : $item['price'] * $item['quantity'];
        $totalOrder += $total;
        $sessionProducts[] = $this->prepareProductData($item, $productInfo, $image, $total, $warehouseId);
      }

      if ($warehouseInfo != null){
        $this->session->data['orders'][$warehouseId] = $sessionProducts;
        $warehouse_name = $warehouseInfo['name'];
        $data[$warehouseId] = [
          'products'     => $sessionProducts,
          'warehouse'    => $warehouse_name,
          'total_order'  => $this->currency->format($totalOrder, $this->session->data['currency']),
          'shipping'     => $warehouseId === 'none' ? false : $this->getShippingData($warehouseId, $items, $setting),
          'payment'      => $warehouseId === 'none' ? false : $this->getPaymentData($warehouseId, $setting),
          'is_available' => $warehouseId !== 'none'
        ];
      }

    }

    return $data;
  }

  /**
   * Формирует массив опций в формате [option_id => value_id]
   */
  protected function getMassOptions($options)
  {
    $massOptions = [];
    foreach ($options as $option) {
      $massOptions[(int)$option['product_option_id']] = (int)$option['product_option_value_id'];
    }
    return $massOptions;
  }

  /**
   * Возвращает изображение товара (учитывая опции)
   */
  protected function getProductImage($item)
  {
    $image = $item['image'];
    if (!empty($item['option'])) {
      foreach ($item['option'] as $option) {
        if (!empty($option['image_opt'])) {
          $image = $option['image_opt'];
          break;
        }
      }
    }
    return $this->model_tool_image->resize($image, 150, 150);
  }

  /**
   * Подготавливает данные товара для отображения
   */
  protected function prepareProductData($item, $productInfo, $image, $total, $warehouseId)
  {
    $uniqId = $item['product_id'];
    if (!empty($item['mass_options'])) {
      $keyOpts = implode('-', array_map(
        fn($k, $v) => "$k-$v",
        array_keys($item['mass_options']),
        $item['mass_options']
      ));
      $uniqId .= "-" . $keyOpts;
    }

    // Отримуємо актуальні залишки для цього складу
    $stocks = $this->model_catalog_product->getProductOptStockWarehouse(
      $item['product_id'],
      $warehouseId,
      $item['mass_options']
    );
    $totalStock = array_sum($stocks);

    // Перевіряємо доступність тільки для реальних складів (не 'none')
    $isBuyable = ($warehouseId === 'none') ? false : ($item['quantity'] <= $totalStock);

    // Якщо це реальний склад, але товару немає - перевіряємо інші склади
    if (!$isBuyable && $warehouseId !== 'none') {
      $otherStocks = $this->model_catalog_product->getProductOptStockWarehouses(
        $item['product_id'],
        $item['mass_options']
      );
      $isAvailableElsewhere = (array_sum($otherStocks) >= $item['quantity']);
    }

    $stockStatus = $isBuyable
      ? $this->language->get('text_in_stock')
      : ($isAvailableElsewhere ?? false
        ? $this->language->get('text_available_elsewhere')
        : $this->language->get('text_out_of_stock'));

    return [
      'cart_id'                => $item['cart_id'],
      'model'                 => $item['model'],
      'name'                  => $productInfo['name'],
      'option'                => $item['option'],
      'product_id'            => $item['product_id'],
      'image'                 => $image,
      'quantity'              => (int)$item['quantity'],
      'price'                 => $item['price'],
      'price_view'            => $this->currency->format($item['price'], $this->session->data['currency']),
      'uniq_id'               => $uniqId,
      'is_buy'               => $isBuyable,
      'stock_status'          => $stockStatus,
      'total'                => $total,
      'price_total_item_view' => $this->currency->format($item['price'] * $item['quantity'], $this->session->data['currency']),
      'special'              => $item['special'],
      'special_view'         => $item['special'] ? $this->currency->format($item['special'], $this->session->data['currency']) : false,
      'special_total_item_view' => $item['special'] ? $this->currency->format($total, $this->session->data['currency']) : false,
      'percent'              => $item['percent'],
      'href'                 => $item['href'],
      'warehouse_id'         => $warehouseId,
      'available_qty'        => $totalStock
    ];
  }
  /**
   * Возвращает данные доставки
   */
  protected function getShippingData($warehouseId, $items, $setting)
  {
    $shippingSetting = $setting['module_custom_shipping'] ?? [];
    $shippingSetting['module_custom_split_order_store'] = $setting['module_custom_split_order_store'] ?? '';
    $shippingSetting['order_id'] = $warehouseId;
    $shippingSetting['products'] = $items;

    return $this->getChildController('shipping', $shippingSetting);
  }

  /**
   * Возвращает данные оплаты
   */
  protected function getPaymentData($warehouseId, $setting)
  {
    $paymentSetting = $setting['module_custom_payment'] ?? [];
    $paymentSetting['order_id'] = $warehouseId;

    return $this->getChildController('payment', $paymentSetting);
  }

  public function getChildController($name, $setting)
  {
    return $this->load->controller('extension/module/custom/' . $name, $setting);
  }
}
