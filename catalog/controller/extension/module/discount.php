<?php

class ControllerExtensionModuleDiscount extends Controller
{

  public function getPrice()
  {

    $this->load->language('extension/module/discont');
    $this->load->model('extension/module/discount');
    $this->load->model('catalog/product');
    $this->load->model('catalog/product_status');

    $json = array();

    $product_id = (int)$this->request->post['product_id'];
    $option = array();
    $status = array();
    $in_cart = false;
    $total_qty = 0;
    $qty = 0;
    $price = 0;
    $percent = 0;
    $special = 0;
    $sku = "";
    $ean = "";
    $cart_id = 0;
    $product_status_id = 0;

    $json["description"] = "";
    $json["composition"] = "";
    $json["uniq_id"] = $product_id;


    if (isset($this->request->post['option'])) {
      $opt_string = $this->request->post['option'][$product_id];
      $option_parce = explode('-', $opt_string);
      if (count($option_parce) == 2) {
        $option = [
          (int)$option_parce[0] => (int)$option_parce[1]
        ];
      }
    }

    $cart_prods = $this->cart->getProduct($product_id);
    foreach ($cart_prods as $item) {
      if ($item['option'] == json_encode($option)) {
        $in_cart = true;
        $qty = $qty + (int)$item['quantity'];
        $price = $item['price'];
        $special = $item['special'];
        $percent = $item['percent'];
        $sku = $item['sku'];
        $ean = $item['ean'];
        $cart_id = $item['cart_id'];
      }
    }

    $prices_discount = $this->model_extension_module_discount->applyDisconts($product_id, $option);
    $price = $prices_discount['price'];
    $special = $prices_discount['special'] > 0 ? $prices_discount['special'] : false;
    $percent = (int)$prices_discount['percent'] > 0 ? (int)$prices_discount['percent'] : false;
    $ean = $prices_discount["ean"];
    $sku = $prices_discount["sku"];

    $product_options = $this->model_catalog_product->getProductByOption($product_id, $option);
    if (!empty($product_options)) {
      $product_status_id = (int)$product_options['product_status_id'];

      $description_opt = $this->model_catalog_product->getProductOptionValueDescription($product_options['product_option_value_id']);
      $json["description"] = Helper::cleanProductDescription($description_opt);

      $composition_opt = $this->model_catalog_product->getProductOptionValueComposition($product_options['product_option_value_id']);
      $json["composition"] = Helper::cleanProductDescription($composition_opt);
    }
    if ($product_status_id > 0) {
      $status = $this->model_catalog_product_status->getProductStatus($product_status_id);
    }

    $print_price = $this->currency->format($price, $this->session->data['currency']);
    $print_special = $special > 0 ? $this->currency->format($special, $this->session->data['currency']) : false;

    //stock warehouses
    $stocks = $this->model_catalog_product->getProductOptStockWarehouses($product_id, $option, true);

    $this->load->language('extension/module/ocwarehouses');
    $data["title_stock_warehouses"] = $this->language->get('title_stock_warehouses');

    $data = array();
    $this->load->model('extension/module/ocwarehouses');
    foreach ($stocks as $warehouse_id => $stock) {
      $warehouse = $this->model_extension_module_ocwarehouses->getWarehouseId($warehouse_id);
      $data["warehouses"][] = array(
        'name' => $warehouse['name'] ?? "",
        'quantity' => $stock
      );
      $total_qty += $stock;
    }
    $data["total_qty"] = $total_qty;
    $json['stock'] = $this->load->view('extension/module/stockproduct', $data);

    $is_buy = ($total_qty - $qty > 0);

    $json['total_qty'] = (int)$total_qty;

    // Преобразуем каждую пару ключ-значение в формат "ключ-значение" и объединяем их в строку
    if (!empty($option)) {
      $key_opts = implode('-', array_map(
        function ($key, $value) {
          return "$key-$value";
        },
        array_keys($option),
        $option
      ));
      $uniq_id = $product_id . "-" . $key_opts;
    } else {
      $uniq_id = $product_id;
    }

    $json["uniq_id"] = $uniq_id;
    $json["is_buy"] = $is_buy;

    // Приклад дати у форматі 'Y-m-d'
    $dateEndObj = DateTime::createFromFormat('Y-m-d', $prices_discount['date_end']);
    $currentDate = new DateTime();

    $html_countdown = '<div class="text-discont-countdown">' . $this->language->get("text_discont_countdown") . '</div>';
    $html_countdown .= '<div id="countdown' . $product_id . '" class="box-timer"></div>';

    if ($dateEndObj > $currentDate) {
      // Перетворюємо дату на формат без часового поясу
      $json["date_end"] = $dateEndObj->format('Y-m-d\TH:i:s');
      $json["bl_countdown_html"] = $html_countdown;
    } else {
      $json["date_end"] = "";
    }

    $html = '';
    if ($special) {
      $html .= '<div class="box-special"><div class="old-price"><span class="price">' . $print_price . '</span></div><div class="special-price"><span class="price">' . $print_special . '</span></div></div>';
    } else {
      $html .= '<div class="box-regular"><div class="regular-price"><span class="price">' . $print_price . '</span></div></div>';
    }
    if ($total_qty > 0) {
      $html .= '<div class="stock-status"><span class="in-stock">' . $this->language->get('text_in_stock') . '</span></div>';
    } else {
      $html .= '<div class="stock-status"><span class="out-stock">' . $this->language->get('text_out_stock') . '</span></div>';
    }

    $html_btn_cart = '';

    if ($qty <= 0) {
      $html_btn_cart .= '<div class="bl-add-in-cart cart-btns-' . $uniq_id . '" data-key="' . $product_id . '" data-cart="0"' . ($is_buy ? ' onclick="custom_cart.handleClickPlus(this)"' : '') . '>';
      $html_btn_cart .= '<div class="minus-add"></div>';
      if ($is_buy) {
        $html_btn_cart .= '<div class="plus-add-text">Додати до кошика</div>';
      }else{
        $html_btn_cart .= '<div class="notify" data-product="' . $product_id . '" data-opt="' . $uniq_id . '" onclick="notify.open(this)">Повідомити про наявність</div>';
      }
      if ($is_buy) {
        $html_btn_cart .= '<div class="plus-add"></div>';
      } else {
        $html_btn_cart .= '<div class="not-available"></div>';
      }
    } else {
      $html_btn_cart .= '<div class="bl-add-in-cart cart-btns-' . $uniq_id . '" data-key="' . $product_id . '" data-cart="' . $cart_id . '">';
      $html_btn_cart .= '<div class="minus" data-key="' . $product_id . '" data-cart="' . $cart_id . '" onclick="custom_cart.handleClickMinus(this)">-</div>';
      $html_btn_cart .= '<input id="qty-' . $product_id . '-' . $cart_id . '" data-key="' . $product_id . '" data-cart="' . $cart_id . '" type="number" name="quantity" value="' . $qty . '" class="quantity-input">';

      if ($is_buy) {
        $html_btn_cart .= '<div class="plus" data-key="' . $product_id . '" data-cart="' . $cart_id . '" onclick="custom_cart.handleClickPlus(this)"></div>';
      } else {
        $html_btn_cart .= '<div class="plus not-available" data-key="' . $product_id . '" data-cart="' . $cart_id . '"></div>';
      }
    }

    $html_btn_cart .= '</div>';

    $json["percent"] = '';
    if ($percent) {
      $json["percent"] .= '<div class="bg-special"><span>-' . $percent . '%</span></div>';
    }

    if (!empty($status)) {
      $json["percent"] .= '<div class="box-label">';
      $json["percent"] .= '<div class="statuses-product statuses-{{ product.product_id }}">';
      $json["percent"] .= '<div class="status-product label_' . $status['code'] . '" style="background-color: ' . $status['color'] . '"><span>' . $status['name'] . '</span></div>';
      $json["percent"] .= '</div></div>';
    }

    $html_model = '';
    $html_model .= $ean;
    $json["model"] = $html_model;

    $html_sku = '';
    $html_sku .= $sku;
    $json["sku"] = $html_sku;

    $json["prices"] = $html;
    $json["btn_cart"] = $html_btn_cart;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

}
