<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountAccount extends Controller
{
  public function index()
  {

    if (!$this->customer->isLogged()) {
      $this->session->data['redirect'] = $this->url->link('account/account', '', true);

      $this->response->redirect($this->url->link('account/login', '', true));
    }

    $this->load->language('account/account');
    $this->load->language('account/card');

    $this->load->model('account/customer');
    $this->load->model('account/address');
    $this->load->model('account/customer_group');
    $this->load->model('account/card');
    $this->load->model('account/customer_debit');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['heading_title'] = $this->language->get('heading_title');

    $this->document->setRobots('noindex,follow');

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home')
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_account'),
      'href' => $this->url->link('account/account', '', true)
    );

    if (isset($this->session->data['success'])) {
      $data['success'] = $this->session->data['success'];

      unset($this->session->data['success']);
    } else {
      $data['success'] = '';
    }

    $data['last_sync'] = isset($this->session->data['last_sync_time']) ? $this->session->data['last_sync_time'] : 0;
    
    // Якщо потрібно скинути таймер для тесту, розкоментуйте рядок нижче ОДИН РАЗ і оновіть сторінку:
    // unset($this->session->data['last_sync_time']);

    // --- НАЛАШТУВАННЯ ЧАСУ ОНОВЛЕННЯ ---
    // Вкажіть час у секундах (наприклад: 120 = 2 хв, 300 = 5 хв, 1800 = 30 хв)
    $data['sync_interval'] = 120; 
    // ----------------------------------

    $affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());

    if (!$affiliate_info) {
      $data['affiliate'] = $this->url->link('account/affiliate/add', '', true);
    } else {
      $data['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
    }

    if ($affiliate_info) {
      $data['tracking'] = $this->url->link('account/tracking', '', true);
    } else {
      $data['tracking'] = '';
    }

    $data['heading_title_card'] = $this->language->get('heading_title_card');
    $data['text_register_bot'] = $this->language->get('text_register_bot');

    $data['telephone'] = $this->customer->getTelephone();
    $data['card_number'] = "";
    $data['text_discont_card'] = "";
    $data['card_code_qr'] = "";

    $data['sum'] = 0;
    $data['percent'] = 0;

    $data['price_groups'] = array();

    // Default Addresses
    if ($this->config->get('config_tax_customer') == 'payment') {
      $this->session->data['payment_address'] = $this->model_account_address->getAddress($this->customer->getAddressId());
    }

    $data['customer_address'] = $this->model_account_address->getAddressCustomerCode();

    if ($this->config->get('config_tax_customer') == 'shipping') {
      $client_address_id = isset($this->session->data['client_address_id']) ? (int)$this->session->data['client_address_id'] : 0;

      // Перевіряємо чи існуюча адреса в сесії все ще валідна для цього клієнта
      if (!$client_address_id || !isset($data['customer_address'][$client_address_id])) {
        // Якщо адреса не встановлена або застаріла (після оновлення), беремо адресу за замовчуванням
        $default_address_id = (int)$this->customer->getAddressId();

        if ($default_address_id && isset($data['customer_address'][$default_address_id])) {
          $this->session->data['client_address_id'] = $default_address_id;
        } elseif (!empty($data['customer_address'])) {
          // Якщо дефолтної немає, беремо першу доступну зі списку
          $first_address = reset($data['customer_address']);
          $this->session->data['client_address_id'] = $first_address['address_id'];
        } else {
          $this->session->data['client_address_id'] = 0;
        }
        $this->session->data['shipping_address'] = $this->session->data['client_address_id'];
      }
    }

    $data['select_address'] = $this->session->data['client_address_id'];

    $address_default = $this->model_account_address->getAddress($data['select_address']);

    $debitTotal = 0;
    $debitBonus = 0;

    $data['delay_pay'] = !empty($address_default["delay_pay"]) ? $address_default["delay_pay"] : 0;
    $data['delay_reserv'] = !empty($address_default["delay_reserv"]) ? $address_default["delay_reserv"] : 0;

    $data['orders'] = array();

    if ($address_default) {

      if (!empty($address_default['customer_cod_guid'])){
        $card = $this->model_account_card->getCardByCode($address_default['customer_cod_guid']);
        if (!empty($card)){
          $data['sum'] = $card['sum'];
        }

        $data['debits'] = $this->model_account_customer_debit->getDebits($this->customer->getId(), $address_default['customer_cod_guid']);
        foreach ($data['debits'] as $deb){
          $debitTotal += $deb['sum_debit'];
          $debitBonus += $deb['contract_bonus'];

          if (!empty($deb['number'])){
            $data['orders'][] = $deb;
          }
        }
      }

      $data['card_code_qr'] = $address_default['guid'];

      $data['card_code'] = $this->customer->getId();
      $data['text_discont_card'] = $address_default['firstname'];

      $data['price_groups'] = $this->model_account_customer->getPriceGroups($data['select_address'], $this->customer->getId());
    } else {
      $data['card_code'] = $this->customer->getId();

      $card = $this->model_account_card->getCardByCustomerId($this->customer->getId());
      if (!empty($card)){
        $data['sum'] = $card['sum'];
      }
    }

    $data['debit_total'] = $this->currency->format(-($debitTotal + $debitBonus), $this->session->data['currency']);
    $data['bonus_total'] = $this->currency->format(-$debitBonus, $this->session->data['currency']);

    $data['text_generate_info'] = $this->language->get('text_generate_info');
    $data['text_after_generate_info'] = $this->language->get('text_after_generate_info');

    $data['menu'] = $this->load->controller('account/menu');

    $data['customer_group_name'] = $this->model_account_customer_group->getCustomerGroup($this->customer->getGroupId());

    $customer_info = $this->model_account_customer->getCustomerByEmail($this->customer->getEmail());
    $data['customer_status'] = $customer_info['status'];
    $data['customer_status_text'] = "";

    if ($customer_info && !$customer_info['status']) {
      $data['customer_status_text'] = $this->language->get('error_approved');
    }

    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $data['text_discont_empty'] = $this->language->get('text_discont_empty');

    $this->load->model('extension/module/discount');
    $data['discount_select'] = (int)$this->model_extension_module_discount->getLoyaltyDiscount()['discount'];
    $data['loyalty_list'] = $this->model_extension_module_discount->getLoyaltyDiscounts();


    if (isset($this->request->get['ajax'])) {
      return $this->load->view('account/account_ajax', $data);
    } else {
      $this->response->setOutput($this->load->view('account/account', $data));
    }

  }

  public function update_info_account()
  {
    $json = array();
    $json['status'] = false;

    if ($this->customer->isLogged()) {
      $current_time = time();
      
      // --- НАЛАШТУВАННЯ ЧАСУ ОНОВЛЕННЯ ---
      // Вкажіть той самий час, що і вище (у секундах)
      $sync_interval = 60;
      // ----------------------------------

      if (isset($this->session->data['last_sync_time']) && ($current_time - $this->session->data['last_sync_time']) < $sync_interval) {
        $interval_minutes = ceil($sync_interval / 60);
        $json['status'] = false;
        $json['error'] = sprintf('Оновлення можливе не частіше ніж раз на %d хв. Спробуйте пізніше.', $interval_minutes);
        $json['next_sync'] = $this->session->data['last_sync_time'] + $sync_interval;
      } else {
        $json['status'] = true;
        $this->session->data['last_sync_time'] = $current_time;

        $this->load->model('setting/setting');
        $setting_module = $this->model_setting_setting->getSetting("module_ocimport");

        $this->importAddressesClient($setting_module);
        $this->importPriceGroupsClient($setting_module);
        $this->importOrdersAndCardClient($setting_module);
      }
    }

    if (!isset($this->request->post['ajax'])) {
      $json['error'] = 'Invalid request';
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    } else {
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
    }

  }

  private function importAddressesClient($setting_module)
  {

    $curl = curl_init();

    $login = $setting_module['login'];
    $password = $setting_module['password'];

    curl_setopt_array($curl, array(
      CURLOPT_URL => $setting_module['url'] . '/get-adresses-client/' . $this->customer->getId(),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . base64_encode("$login:$password")
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {

      $this->load->model('account/address');
      $this->load->model('account/customer_price');
      $this->load->model('account/customer_debit');

      $response_mass = json_decode($response, true);

      //видаляємо всі які є в базі сайту
      $this->model_account_address->deleteAddressByCustomerId($this->customer->getId());

      //видаляємо всі документи дебіторки які є в базі сайту
      $this->model_account_customer_debit->deleteDebits($this->customer->getId());

      if (!empty($response_mass['address'])) {
        foreach ($response_mass['address'] as $line) {

          $customer_code_erp = $line['client_code'];
          $customer_name_erp = $line['client_name'];
          $customer_ourdelivery = $line['ourdelivery'];

          $delay_pay = $line['delay_pay'];
          $delay_reserv = $line['delay_reserv'];

          $customer_price_id = 0;
          $customer_price_db = $this->model_account_customer_price->getCustomerPriceByUid($line['price_guid']);
          if (!empty($customer_price_db)){
            $customer_price_id = $customer_price_db['customer_price_id'];
          }

          $customer_type = 0;
          if (isset($line['customer_type'])) {
            $customer_type = $line['customer_type'];
          }

          $default_address = false;

          if (isset($line['address_car'])) {
            if (!empty($line['address_car'])) {
              foreach ($line['address_car'] as $item_car) {

                $address_db = $this->model_account_address->getAddressClientByUid($item_car['guid'], $this->customer->getId(), $customer_code_erp);
                if (!empty($address_db)) {
                  $address_id = $address_db['address_id'];
                  $this->model_account_address->updateAddressClient($address_db, $this->customer->getId(), $customer_code_erp, "car", $customer_name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $item_car);
                } else {
                  $address_id = $this->model_account_address->addAddressClient($this->customer->getId(), $customer_code_erp, "car", $customer_name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $item_car);
                }

                if ($address_id > 0) {
                  $default_address = true;
                  $this->model_account_address->updateCustomerAdressDefault($this->customer->getId(), $address_id);
                }
              }
            }
          }

          if (isset($line['address_np'])) {
            if (!empty($line['address_np'])) {
              foreach ($line['address_np'] as $item_np) {

                $address_id = 0;

                if (isset($item_np['dveri'])) {
                  foreach ($item_np['dveri'] as $item_dvery) {
                    $address_db = $this->model_account_address->getAddressClientByUid($item_dvery['guid'], $this->customer->getId(), $customer_code_erp);
                    if (!empty($address_db)) {
                      $address_id = $address_db['address_id'];
                      $this->model_account_address->updateAddressClient($address_db, $this->customer->getId(), $customer_code_erp, "np_dveri", $customer_name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $item_dvery);
                    } else {
                      $address_id = $this->model_account_address->addAddressClient($this->customer->getId(), $customer_code_erp, "np_dveri", $customer_name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $item_dvery);
                    }
                  }
                }
                if (isset($item_np['posts'])) {
                  foreach ($item_np['posts'] as $item_post) {
                    $address_db = $this->model_account_address->getAddressClientByUid($item_post['guid'], $this->customer->getId(), $customer_code_erp);
                    if (!empty($address_db)) {
                      $address_id = $address_db['address_id'];
                      $this->model_account_address->updateAddressClient($address_db, $this->customer->getId(), $customer_code_erp, "np_post", $customer_name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $item_post);
                    } else {
                      $address_id = $this->model_account_address->addAddressClient($this->customer->getId(), $customer_code_erp, "np_post", $customer_name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $item_post);
                    }
                  }
                }
                if (!$default_address) {
                  if ($address_id > 0) {
                    $default_address = true;
                    $this->model_account_address->updateCustomerAdressDefault($this->customer->getId(), $address_id);
                  }
                }
              }
            }
          }

          //записуємо дебіторку
          if (isset($line['docs'])){
            if (!empty($line['docs'])) {
              foreach ($line['docs'] as $doc) {
                  $this->model_account_customer_debit->addDebit($this->customer->getId(), $customer_code_erp, $doc);
              }
            }
          }
        }
      }
    }
  }

  public function importOrdersAndCardClient($setting_module)
  {
    $login = $setting_module['login'];
    $password = $setting_module['password'];

    $this->load->model('account/address');
    $this->load->model('account/customer');
    $this->load->model('account/order');
    $this->load->model('account/card');
    $this->load->model('catalog/product');
    $this->load->model('checkout/order');

    $mass_ords = array();
    $orders_site = $this->model_account_order->getOrdersForErp($this->customer->getId());
    if (!empty($orders_site)){
      foreach ($orders_site as $order)
        if (!empty($order['invoce_erp_guid'])) {
          $mass_ords[] = $order['invoce_erp_guid'];
        }
    }

    // Формуємо масив для orders
    $orders_array = array_values($mass_ords); // Якщо потрібні тільки значення

    $data_json = json_encode(array(
      'client_id' => $this->customer->getId(),
      'orders' => $orders_array
    ));

    $curl = curl_init();

    curl_setopt_array($curl, array(
      //CURLOPT_URL => $setting_module['url'] . '/cards',
      CURLOPT_URL =>  'https://b2b.detta.com.ua/demo_api/hs/site/cards',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $data_json,
      CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . base64_encode("$login:$password")
      ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    if (!empty($response)) {
      $response_mass = json_decode($response, true);
      if (!empty($response_mass)){
        if (isset($response_mass['cards'])) {
          foreach ($response_mass['cards'] as $card) {
            $card_db = $this->model_account_card->getCardByUid($card['guid']);
            if ($card_db) {
              $this->model_account_card->updateCard($card_db, $card);
            } else {
              $this->model_account_card->addCard($card);
            }
          }
        }
        if (isset($response_mass['orders'])) {
          foreach ($response_mass['orders'] as $order) {
            $orders_site = $this->model_account_order->getOrderForErp($this->customer->getId(), $order['invoce_erp_guid']);
            if (!empty($orders_site)){
              $this->model_account_order->deleteOrderProducts($orders_site["order_id"]);

              switch ($order["status"]){
                case 2:
                  $this->model_checkout_order->addOrderHistory($orders_site["order_id"], 3, "", 1, true);
                  break;
                case 3:
                  $this->model_checkout_order->addOrderHistory($orders_site["order_id"], 9, "", 1, true);
                  break;
                case 4:
                  $this->model_checkout_order->addOrderHistory($orders_site["order_id"], 5, "", 1, true);
                  break;
              }

              foreach ($order["products"] as $order_prod) {
                $prod_db = $this->model_catalog_product->getProductByEan($order_prod["barcode"]);
                if (!empty($prod_db)){

                  $percent = $order_prod["manual_discont"] + $order_prod["auto_discont"];

                  $data = array(
                    "product_id" => $prod_db["product_id"],
                    "name" => $prod_db["name"],
                    "model" => $prod_db["sku"],
                    "quantity" => $order_prod["count_1c"],
                    "quantity_order" => $order_prod["count"],
                    "price" => $order_prod["price"],
                    "price_discont" => $order_prod["price_discont"],
                    "percent" => $percent,
                    "total" => $order_prod["total"],
                    "options" => $prod_db["options"],
                    "options_value" => $prod_db["options_value"],
                  );
                  $this->model_account_order->insertOrderProducts($orders_site["order_id"], $data);
                }
              }
            }
          }
        }
      }
    }

  }

  public function importPriceGroupsClient($setting_module)
  {

    $login = $setting_module['login'];
    $password = $setting_module['password'];

    $this->load->model('account/address');
    $this->load->model('account/customer');

    $this->model_account_customer->deleteCustomerAddressPriceGroupsByCustomer($this->customer->getId());

    //отримуємо всі адреса
    $customer_addresses = $this->model_account_address->getAddressCustomerCodsGuid($this->customer->getId());
    if (!empty($customer_addresses)) {
      foreach ($customer_addresses as $address) {
        if (!empty($address['customer_cod_guid'])) {
          $curl = curl_init();

          curl_setopt_array($curl, array(
            CURLOPT_URL => $setting_module['url'] . '/client-disconts/' . $address['customer_cod_guid'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              'Authorization: Basic ' . base64_encode("$login:$password")
            ),
          ));

          $response = curl_exec($curl);

          curl_close($curl);

          if (!empty($response)) {
            $response_mass = json_decode($response, true);

            if (!empty($response_mass['price_groups'])) {
              foreach ($response_mass['price_groups'] as $item) {
                $price_group_db = $this->model_account_customer->getPriceGroupByUid($item['price_group_guid']);
                if (!empty($price_group_db)) {
                  $price_group_id = $price_group_db['price_group_id'];
                } else {
                  $price_group_id = $this->model_account_customer->addPriceGroupe($item['price_group_guid'], $item['name']);
                }

                if ((int)$item['percent'] > 0) {
                  $this->model_account_customer->addCustomerAddressPriceGroup($item, $this->customer->getId(), $address['address_id'], $price_group_id);
                }
              }
            }
          }
        }
      }
    }
  }

  public function generate_pricelist()
  {

    if (!$this->customer->isLogged()) {
      // Додайте ці два рядки на початок методу
      ob_start(); // Початок буферизації виводу
      error_reporting(0); // Тимчасово вимкніть помилки

      $json = array();
      $json['status'] = false;

      // Перевірка, чи це AJAX запит
      if (!isset($this->request->post['ajax'])) {
        $json['error'] = 'Invalid request';
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
      }

      $this->load->model('catalog/category');
      $this->load->model('catalog/product');
      // Додайте цей рядок, якщо використовуєте модель discount
      $this->load->model('extension/module/discount');

      $customer_id = $this->customer->getId();
      $selected_address = isset($this->request->post['selected_address']) ? $this->request->post['selected_address'] : null;

      // Основні файли PHPExcel
      require_once DIR_SYSTEM . 'library/PHPExcel.php';
      require_once DIR_SYSTEM . 'library/PHPExcel/Writer/Excel5.php';
      require_once DIR_SYSTEM . 'library/PHPExcel/IOFactory.php';

      try {
        // Створюємо новий Excel документ
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Прайс-лист');

        // Заповнюємо заголовки колонок
        $sheet->setCellValue('A1', 'Категорія');
        $sheet->setCellValue('B1', 'Арткул');
        $sheet->setCellValue('C1', 'Штрихкод');
        $sheet->setCellValue('D1', 'Назва/Характеристика');

        $sheet->setCellValue('E1', 'Ціна');
        $sheet->setCellValue('F1', '% знижкою');
        $sheet->setCellValue('G1', 'Ціна зі знижкою');
        $sheet->setCellValue('H1', 'Рекомендована ціна');

        // Стилі для заголовків
        $headerStyle = array(
          'font' => array('bold' => true, 'color' => array('rgb' => '000000'))
        );

        $categotyStyle = array(
          'font' => array('bold' => true, 'color' => array('rgb' => '000000'))
        );

        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        $row = 2;
        $data['categories'] = array();
        $data['category_type'] = 0;

        $categories = $this->model_catalog_category->getCategories(0, $data['category_type']);

        foreach ($categories as $category) {

          $sheet->setCellValue('A' . $row, $category['name']);
          $sheet->getStyle('A' . $row)->applyFromArray($categotyStyle);
          $row++;

          // Level 2
          $children_data = array();
          $children = $this->model_catalog_category->getCategories($category['category_id'], $data['category_type']);
          foreach ($children as $child) {

            $sheet->setCellValue('B' . $row, $child['name']);
            $sheet->getStyle('B' . $row)->applyFromArray($categotyStyle);
            $row++;

            // Level 3
            $children_data3 = array();
            $children3 = $this->model_catalog_category->getCategories($child['category_id'], $data['category_type']);

            foreach ($children3 as $child3) {
              $filter_data3 = array(
                'filter_category_id' => (int)$child3['category_id'],
                'filter_sub_category' => false
              );

              $sheet->setCellValue('C' . $row, $child3['name']);
              $sheet->getStyle('C' . $row)->applyFromArray($categotyStyle);
              $row++;

              $results3 = $this->model_catalog_product->getProducts($filter_data3);
              if (isset($results3['products'])) {
                foreach ($results3['products'] as $prod) {

                  $mass_option = array();
                  $options = $this->model_catalog_product->getProductOptionPawPaw($prod['product_id']);
                  if (!empty($options)) {

                    $sheet->setCellValue('D' . $row, $prod['name']);
                    $row++;

                    foreach ($options as $option) {

                      $mass_option += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];
                      $prices_discount3 = $this->model_extension_module_discount->applyDisconts($prod['product_id'], $mass_option);

                      $sheet->setCellValue('B' . $row, $prices_discount3["sku"]);
                      $sheet->setCellValue('C' . $row, $prices_discount3["ean"]);
                      $sheet->setCellValue('D' . $row, mb_strtolower($option['name'], 'UTF-8'));

                      $sheet->setCellValue('E' . $row, $prices_discount3['price']);

                      if ($prices_discount3['percent'] > 0) {
                        $sheet->setCellValue('F' . $row, $prices_discount3['price'] - ($prices_discount3['price'] * ($prices_discount3['percent'] / 100)));
                        $sheet->setCellValue('G' . $row, $prices_discount3['percent']);
                      }

                      $sheet->setCellValue('H' . $row, $prices_discount3['price_rrc']);

                      $mass_option = array();
                      $row++;
                    }
                  } else {

                    $prices_discount3 = $this->model_extension_module_discount->applyDisconts($prod['product_id'], $mass_option);

                    $sheet->setCellValue('B' . $row, $prod['sku']);
                    $sheet->setCellValue('C' . $row, $prod['ean']);
                    $sheet->setCellValue('D' . $row, mb_strtolower($option['name'], 'UTF-8'));

                    $sheet->setCellValue('E' . $row, $prices_discount3['price']);

                    if ($prices_discount3['percent'] > 0) {
                      $sheet->setCellValue('F' . $row, $prices_discount3['price'] - ($prices_discount3['price'] * ($prices_discount3['percent'] / 100)));
                      $sheet->setCellValue('G' . $row, $prices_discount3['percent']);
                    }
                    $sheet->setCellValue('H' . $row, $prices_discount3['price_rrc']);
                    $row++;
                  }

                }
              }

              $children_data3[] = array(
                'id' => $child3['category_id'],
                'name' => $child3['name'],
                'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child3['category_id'])
              );

            }

            $filter_data2 = array(
              'filter_category_id' => (int)$child['category_id'],
              'filter_sub_category' => false
            );

            if (empty($children_data3)) {
              $results2 = $this->model_catalog_product->getProducts($filter_data2);
              if (isset($results2['products'])) {
                foreach ($results2['products'] as $prod) {

                  $mass_option = array();
                  $options = $this->model_catalog_product->getProductOptionPawPaw($prod['product_id']);
                  if (!empty($options)) {

                    $sheet->setCellValue('D' . $row, $prod['name']);
                    $row++;

                    foreach ($options as $option) {

                      $mass_option += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];
                      $prices_discount2 = $this->model_extension_module_discount->applyDisconts($prod['product_id'], $mass_option);

                      $sheet->setCellValue('B' . $row, $prices_discount2["sku"]);
                      $sheet->setCellValue('C' . $row, $prices_discount2["ean"]);
                      $sheet->setCellValue('D' . $row, mb_strtolower($option['name'], 'UTF-8'));

                      $sheet->setCellValue('E' . $row, $prices_discount2['price']);

                      if ($prices_discount2['percent'] > 0) {
                        $sheet->setCellValue('F' . $row, $prices_discount2['price'] - ($prices_discount2['price'] * ($prices_discount2['percent'] / 100)));
                        $sheet->setCellValue('G' . $row, $prices_discount2['percent']);
                      }

                      $sheet->setCellValue('H' . $row, $prices_discount2['price_rrc']);

                      $mass_option = array();
                      $row++;
                    }
                  } else {

                    $prices_discount2 = $this->model_extension_module_discount->applyDisconts($prod['product_id'], $mass_option);

                    $sheet->setCellValue('B' . $row, $prod['sku']);
                    $sheet->setCellValue('C' . $row, $prod['ean']);
                    $sheet->setCellValue('D' . $row, mb_strtolower($prod['name'], 'UTF-8'));

                    $sheet->setCellValue('E' . $row, $prices_discount2['price']);

                    if ($prices_discount2['percent'] > 0) {
                      $sheet->setCellValue('F' . $row, $prices_discount2['price'] - ($prices_discount2['price'] * ($prices_discount2['percent'] / 100)));
                      $sheet->setCellValue('G' . $row, $prices_discount2['percent']);
                    }

                    $sheet->setCellValue('H' . $row, $prices_discount2['price_rrc']);

                    $row++;
                  }
                }
              }
            }

            $children_data[] = array(
              'id' => $child['category_id'],
              'name' => $child['name'],
              'children' => $children_data3,
              'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
            );
          }

          // Level 1
          $filter_data1 = array(
            'filter_category_id' => (int)$category['category_id'],
            'filter_sub_category' => false
          );

          if (empty($children_data)) {
            $results1 = $this->model_catalog_product->getProducts($filter_data1);
            if (isset($results1['products'])) {
              foreach ($results1['products'] as $prod) {
                $mass_option = array();
                $options = $this->model_catalog_product->getProductOptionPawPaw($prod['product_id']);
                if (!empty($options)) {

                  $sheet->setCellValue('D' . $row, $prod['name']);
                  $row++;

                  foreach ($options as $option) {

                    $mass_option += [(int)$option['product_option_id'] => (int)$option['product_option_value_id']];
                    $prices_discount = $this->model_extension_module_discount->applyDisconts($prod['product_id'], $mass_option);

                    $sheet->setCellValue('B' . $row, $prices_discount["sku"]);
                    $sheet->setCellValue('C' . $row, $prices_discount["ean"]);
                    $sheet->setCellValue('D' . $row, mb_strtolower($option['name'], 'UTF-8'));

                    $sheet->setCellValue('E' . $row, $prices_discount['price']);

                    if ($prices_discount['percent'] > 0) {
                      $sheet->setCellValue('F' . $row, $prices_discount['price'] - ($prices_discount['price'] * ($prices_discount['percent'] / 100)));
                      $sheet->setCellValue('G' . $row, $prices_discount['percent']);
                    }

                    $sheet->setCellValue('H' . $row, $prices_discount['price_rrc']);

                    $mass_option = array();

                    $row++;
                  }
                } else {

                  $prices_discount = $this->model_extension_module_discount->applyDisconts($prod['product_id'], $mass_option);

                  $sheet->setCellValue('B' . $row, $prod['sku']);
                  $sheet->setCellValue('C' . $row, $prod['ean']);
                  $sheet->setCellValue('D' . $row, mb_strtolower($prod['name'], 'UTF-8'));

                  $sheet->setCellValue('E' . $row, $prices_discount['price']);

                  if ($prices_discount['percent'] > 0) {
                    $sheet->setCellValue('F' . $row, $prices_discount['price'] - ($prices_discount['price'] * ($prices_discount['percent'] / 100)));
                    $sheet->setCellValue('G' . $row, $prices_discount['percent']);
                  }

                  $sheet->setCellValue('H' . $row, $prices_discount['price_rrc']);

                  $row++;
                }
              }
            }
          }

          $data['categories'][] = array(
            'id' => $category['category_id'],
            'name' => $category['name'],
            'children' => $children_data,
            'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
          );
        }

        // Автоматичне ширини колонок
        foreach (range('A', 'H') as $column) {
          $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Зберігаємо файл у тимчасову директорію
        $filename = 'detta_' . date('Y-m-d_H-i-s') . '.xls';
        $temp_dir = sys_get_temp_dir();
        $file_path = $temp_dir . '/' . $filename;

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($file_path);

        // Перевіряємо, чи файл успішно створено
        if (file_exists($file_path)) {
          $json['status'] = true;
          $json['download_url'] = 'index.php?route=account/account/download_pricelist&file=' . urlencode($filename);
          $json['filename'] = $filename;
          $json['message'] = 'Прайс-лист успішно сформовано';
        } else {
          $json['error'] = 'Помилка при створенні файлу';
        }

      } catch (Exception $e) {
        $json['error'] = 'Помилка: ' . $e->getMessage();
      }

      // В кінці методу додайте:
      ob_end_clean(); // Очистити буфер і вимкнути буферизацію
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function download_pricelist()
  {
    $filename = isset($this->request->get['file']) ? $this->request->get['file'] : '';
    $temp_dir = sys_get_temp_dir();
    $file_path = $temp_dir . '/' . $filename;

    if (file_exists($file_path) && is_file($file_path)) {
      header('Content-Type: application/vnd.ms-excel');
      header('Content-Disposition: attachment;filename="' . $filename . '"');
      header('Cache-Control: max-age=0');
      header('Content-Length: ' . filesize($file_path));

      readfile($file_path);

      // Видаляємо тимчасовий файл після завантаження
      unlink($file_path);
      exit;
    } else {
      echo 'Файл не знайдено або вже завантажено';
    }
  }

  public function change_address()
  {

    $json = array();
    $json['status'] = false;
    $json['html'] = '';
    $json['redirect'] = '';
    $json['customer_type'] = "";

    if (!$this->customer->isLogged()) {
      $json['redirect'] = $this->url->link('account/login', '', true);
    } else {
      if (isset($this->request->post['address_id'])) {
        $json['status'] = true;
        $this->request->get['ajax'] = 1;
        $this->session->data['client_address_id'] = (int)$this->request->post['address_id'];

        $this->load->model('account/address');

        $customer_address = $this->model_account_address->getAddressCustomerCode();
        if (!empty($customer_address)) {
          foreach ($customer_address as $address) {
            if ($address['address_id'] == $this->request->post['address_id']) {
              if ((int)$address['customer_type'] > 0) {
                $json['customer_type'] = '<span class="customer-type">статус юридичної особи</span>';
              }
            }
          }
        }

        $json['html'] = $this->index();
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function country()
  {
    $json = array();

    $this->load->model('localisation/country');

    $country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

    if ($country_info) {
      $this->load->model('localisation/zone');

      $json = array(
        'country_id' => $country_info['country_id'],
        'name' => $country_info['name'],
        'iso_code_2' => $country_info['iso_code_2'],
        'iso_code_3' => $country_info['iso_code_3'],
        'address_format' => $country_info['address_format'],
        'postcode_required' => $country_info['postcode_required'],
        'zone' => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
        'status' => $country_info['status']
      );
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
