<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountAddress extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/address', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/address');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$this->load->model('account/address');

		$this->getList();
	}

	public function delete() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/address', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/address');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$this->load->model('account/address');

		if (isset($this->request->get['address_id'])) {
			$address_info = $this->model_account_address->getAddress($this->request->get['address_id']);

			if ($address_info && empty($address_info['guid']) && $this->validateDelete()) {
				$this->model_account_address->deleteAddress($this->request->get['address_id']);

				// Default Shipping Address
				if (isset($this->session->data['shipping_address']['address_id']) && ($this->request->get['address_id'] == $this->session->data['shipping_address']['address_id'])) {
					unset($this->session->data['shipping_address']);
					unset($this->session->data['client_address_id']);
					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
				}

				// Default Payment Address
				if (isset($this->session->data['payment_address']['address_id']) && ($this->request->get['address_id'] == $this->session->data['payment_address']['address_id'])) {
					unset($this->session->data['payment_address']);
					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				}

				$this->session->data['success'] = $this->language->get('text_delete');
			} elseif ($address_info && !empty($address_info['guid'])) {
				$this->session->data['error_warning'] = "Заборонено видаляти адреси, синхронізовані з обліковою системою.";
			}

			$this->response->redirect($this->url->link('account/address', '', true));
		}

		$this->getList();
	}

	protected function getList() {
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/address', '', true)
		);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} elseif (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['addresses'] = array();

		$results = $this->model_account_address->getAddresses();

    $mass_addresses = array();

    foreach ($results as $result) {

      if ($result['type'] == "car" && $result['guid'] != ""){
        continue;
      }


      $type_desc = "";
      switch ($result['type']) {
        case "car":
          $type_desc = "Кур’єр Detta";
          break;
        case "np_post":
          $type_desc = "Нова пошта на відділення";
          break;
        case "np_poshtomat":
          $type_desc = "Нова пошта на поштомат";
          break;
        case "np_dveri":
          $type_desc = "Нова пошта на адресу";
          break;
        default:
          $type_desc = $result['type'];
          break;
      }

      $point_name = !empty($result['customer_cod_guid']) ? $result['firstname'] : '';
      $group_name = $type_desc;
      if ($point_name) {
        $group_name = $point_name . " - " . $type_desc;
      }

      $group_key = $result['customer_cod_guid'] . '_' . $result['type'];

      if (!isset($mass_addresses[$group_key])) {
        $mass_addresses[$group_key] = array(
          'name'      => $group_name,
          'addresses' => array()
        );
      }

      $mass_addresses[$group_key]['addresses'][] = array(
        'address_id'           => $result['address_id'],
        'guid'                 => $result['guid'],
        'type'                 => $result['type'],
        'type_desc'            => $type_desc,
        'city'                 => $result['city'],
        'address_1'            => $result['address_1'],
        'np_city_name'         => $result['np_city_name'],
        'np_street_name'       => $result['np_street_name'],
        'np_post_name'         => $result['np_post_name'],
        'np_house'             => $result['np_house'],
        'np_level'             => $result['np_level'],
        'np_apartment'         => $result['np_apartment'],
        'np_customer_name'     => $result['np_customer_name'],
        'np_customer_lastname' => $result['np_customer_lastname'],
        'np_customer_phone'    => $result['np_customer_phone'],
        'customer_type'        => $result['customer_type'],
        'customer_cod_guid'    => $result['customer_cod_guid'],
        'can_edit'             => empty($result['guid']),
        'delete'               => $this->url->link('account/address/delete', 'address_id=' . $result['address_id'], true)
      );
    }

    $data['addresses'] = array_values($mass_addresses);

    $data['menu'] = $this->load->controller('account/menu');

		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/address_list', $data));
	}

	protected function validateDelete() {
		if ($this->model_account_address->getTotalAddresses() == 1) {
			$this->error['warning'] = $this->language->get('error_delete');
		}

		if ($this->customer->getAddressId() == $this->request->get['address_id']) {
			$this->error['warning'] = $this->language->get('error_default');
		}

		return !$this->error;
	}

  public function getModal() {
    $this->load->language('account/address');
    $this->load->model('account/address');

    if (isset($this->request->get['address_id'])) {
      $address_info = $this->model_account_address->getAddress($this->request->get['address_id']);
    } else {
      $address_info = array();
    }

    $data['address_id'] = isset($this->request->get['address_id']) ? $this->request->get['address_id'] : 0;
    $data['customer_cod_guid'] = isset($this->request->get['customer_cod_guid']) ? trim($this->request->get['customer_cod_guid']) : '';

    $occallback_data = $this->config->get('occallback_data');
    $data['mask'] = (isset($occallback_data['mask']) && !empty($occallback_data['mask'])) ? $occallback_data['mask'] : '';

    if ($address_info) {
      $data['type'] = $address_info['type'];
      $data['customer_cod_guid'] = $address_info['customer_cod_guid'];
      $data['np_city_id'] = $address_info['np_city_id'];
      $data['np_city_name'] = $address_info['np_city_name'];

      // Get delivery_ref if not present
      $data['np_city_delivery_id'] = '';
      if (!empty($data['np_city_id'])) {
          $this->load->model('setting/setting');
          $api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');
          if (empty($api_key)) $api_key = $this->config->get('shipping_np_key');

          $curl = curl_init();
          curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode([
                'apiKey' => $api_key,
                'modelName' => 'Address',
                'calledMethod' => 'searchSettlements',
                'methodProperties' => [
                    'CityName' => $data['np_city_name'],
                    'Limit' => '1'
                ]
            ]),
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
          ));
          $response = curl_exec($curl);
          curl_close($curl);
          $res_data = json_decode($response, true);
          if (!empty($res_data['data'][0]['Addresses'][0]['DeliveryCity'])) {
              $data['np_city_delivery_id'] = $res_data['data'][0]['Addresses'][0]['DeliveryCity'];
          }
      }

      $data['np_street_id'] = $address_info['np_street_id'];
      $data['np_street_name'] = $address_info['np_street_name'];
      $data['np_post_id'] = $address_info['np_post_id'];
      $data['np_post_name'] = $address_info['np_post_name'];
      $data['np_house'] = $address_info['np_house'];
      $data['np_level'] = $address_info['np_level'];
      $data['np_apartment'] = $address_info['np_apartment'];
      $data['np_customer_name'] = $address_info['np_customer_name'];
      $data['np_customer_lastname'] = $address_info['np_customer_lastname'];
      $data['np_customer_phone'] = $address_info['np_customer_phone'];
      $data['address_1'] = $address_info['address_1'];
      $data['city'] = $address_info['city'];
      $data['firstname'] = $address_info['firstname'];
    } else {
      $data['type'] = '';
      $data['np_city_id'] = '';
      $data['np_city_name'] = '';
      $data['np_street_id'] = '';
      $data['np_street_name'] = '';
      $data['np_post_id'] = '';
      $data['np_post_name'] = '';
      $data['np_house'] = '';
      $data['np_level'] = '';
      $data['np_apartment'] = '';
      $data['np_customer_name'] = '';
      $data['np_customer_lastname'] = '';
      $data['np_customer_phone'] = '';
      $data['address_1'] = '';
      $data['city'] = '';
      $data['firstname'] = '';
      $data['np_city_delivery_id'] = '';
    }

    if (empty($data['firstname']) && $data['customer_cod_guid'] !== '') {
       // Try to get firstname from other addresses with same guid
       $results = $this->model_account_address->getAddresses();
       foreach ($results as $res) {
           if (trim($res['customer_cod_guid']) == $data['customer_cod_guid'] && !empty($res['firstname'])) {
               $data['firstname'] = $res['firstname'];
               break;
           }
       }
    }

    if (empty($data['firstname'])) {
       $data['firstname'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
    }

    $this->response->setOutput($this->load->view('account/address_modal', $data));
  }

  public function saveAddress() {
    $this->load->language('account/address');
    $this->load->model('account/address');

    $json = array();

    if (!$this->customer->isLogged()) {
      $json['redirect'] = $this->url->link('account/login', '', true);
    }

    if (!$json) {
      if (utf8_strlen(trim($this->request->post['firstname'])) < 1) {
        $json['error']['firstname'] = 'Вкажіть назву точки або ПІБ';
      }

      $type = $this->request->post['type'];
      $city_name = trim($this->request->post['np_city_name']);

      if ($type == 'np_post' || $type == 'np_dveri' || $type == 'np_poshtomat') {
        if (utf8_strlen($city_name) < 1) {
          $json['error']['np_city_name'] = 'Виберіть місто';
        }
        if (utf8_strlen(trim($this->request->post['np_customer_name'])) < 1) {
          $json['error']['np_customer_name'] = 'Вкажіть ім\'я';
        }
        if (utf8_strlen(trim($this->request->post['np_customer_lastname'])) < 1) {
          $json['error']['np_customer_lastname'] = 'Вкажіть прізвище';
        }
        if (utf8_strlen(trim($this->request->post['np_customer_phone'])) < 1) {
          $json['error']['np_customer_phone'] = 'Вкажіть телефон';
        }

        if ($type == 'np_post') {
          if (utf8_strlen(trim($this->request->post['np_post_name'])) < 1) {
            $json['error']['np_post_name'] = 'Виберіть відділення';
          }
        }
        if ($type == 'np_poshtomat') {
          if (utf8_strlen(trim($this->request->post['np_post_name'])) < 1) {
            $json['error']['np_post_name'] = 'Виберіть номер або адресу поштомату';
          }
        }
        if ($type == 'np_dveri') {
          if (utf8_strlen(trim($this->request->post['np_street_name'])) < 1) {
            $json['error']['np_street_name'] = 'Виберіть вулицю';
          }
          if (utf8_strlen(trim($this->request->post['np_house'])) < 1) {
            $json['error']['np_house'] = 'Вкажіть будинок';
          }
        }
      } elseif ($type == 'car') {
        if (utf8_strlen($city_name) < 2) {
          $json['error']['np_city_name'] = 'Вкажіть місто';
        }
        if (utf8_strlen(trim($this->request->post['address_1'])) < 3) {
          $json['error']['address_1'] = 'Вкажіть вулицю';
        }
        if (utf8_strlen(trim($this->request->post['np_house'])) < 1) {
          $json['error']['np_house'] = 'Вкажіть будинок';
        }
      } else {
        $json['error']['type'] = 'Виберіть тип доставки';
      }
    }

    if (!$json) {
      $address_data = array(
        'city_id' => isset($this->request->post['np_city_id']) ? $this->request->post['np_city_id'] : '',
        'city_name' => $city_name,
        'city' => $city_name,
        'post_id' => isset($this->request->post['np_post_id']) ? $this->request->post['np_post_id'] : '',
        'post_name' => isset($this->request->post['np_post_name']) ? $this->request->post['np_post_name'] : '',
        'street_id' => isset($this->request->post['np_street_id']) ? $this->request->post['np_street_id'] : '',
        'street_name' => isset($this->request->post['np_street_name']) ? $this->request->post['np_street_name'] : '',
        'house' => isset($this->request->post['np_house']) ? $this->request->post['np_house'] : '',
        'level' => isset($this->request->post['np_level']) ? $this->request->post['np_level'] : '',
        'apartment' => isset($this->request->post['np_apartment']) ? $this->request->post['np_apartment'] : '',
        'customer_name' => isset($this->request->post['np_customer_name']) ? $this->request->post['np_customer_name'] : '',
        'customer_lastname' => isset($this->request->post['np_customer_lastname']) ? $this->request->post['np_customer_lastname'] : '',
        'customer_phone' => isset($this->request->post['np_customer_phone']) ? $this->request->post['np_customer_phone'] : '',
        'name' => isset($this->request->post['address_1']) ? $this->request->post['address_1'] : '',
        'guid' => '',
      );

      $customer_id = $this->customer->getId();
      $customer_code_1c = $this->request->post['customer_cod_guid'];
      $type = $this->request->post['type'];
      $name_erp = $this->request->post['firstname'];
      $customer_type = 0;
      $customer_ourdelivery = ($type == 'car') ? 1 : 0;

      if (isset($this->request->post['address_id']) && $this->request->post['address_id'] > 0) {
        $address_db = $this->model_account_address->getAddress($this->request->post['address_id']);
        if ($address_db) {
           if (!empty($address_db['guid'])) {
             $json['error']['warning'] = 'Заборонено редагувати адреси, синхронізовані з обліковою системою.';
           } else {
             $type = $address_db['type'];
             $customer_ourdelivery = ($type == 'car') ? 1 : 0;
             $address_data['guid'] = $address_db['guid'];
             $this->model_account_address->updateAddressClient($address_db, $customer_id, $customer_code_1c, $type, $name_erp, $customer_type, $customer_ourdelivery, 0, 0, 0, $address_data);
             $json['success'] = 'Адресу успішно оновлено';
           }
        } else {
           $json['error']['warning'] = 'Адресу не знайдено';
        }
      } else {
        $this->model_account_address->addAddressClient($customer_id, $customer_code_1c, $type, $name_erp, $customer_type, $customer_ourdelivery, 0, 0, 0, $address_data);
        $json['success'] = 'Адресу успішно додано';
      }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
