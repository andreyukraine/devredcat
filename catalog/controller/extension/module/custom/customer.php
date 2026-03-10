<?php
class ControllerExtensionModuleCustomcustomer extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/custom/customer');

		$data['heading_customer'] = $this->language->get('heading_customer');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_firstname'] = $this->language->get('entry_firstname');
		$data['entry_lastname'] = $this->language->get('entry_lastname');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_telephone'] = $this->language->get('entry_telephone');
		$data['entry_fax'] = $this->language->get('entry_fax');
		$data['entry_password'] = $this->language->get('entry_password');
		$data['entry_confirm'] = $this->language->get('entry_confirm');

		$data['mask_phone_number'] = "+38(999)999-99-99";

    $this->load->model('account/address');
    $data['customer_address'] = $this->model_account_address->getAddressCustomerCode();

		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_upload'] = $this->language->get('button_upload');

    if ($this->config->get('config_tax_customer') == 'shipping') {
      if (!isset($this->session->data['shipping_address'])) {
        $this->session->data['shipping_address'] = $this->customer->getAddressId();
      }
      if (!isset($this->session->data['client_address_id'])) {
        $this->session->data['client_address_id'] = $this->customer->getAddressId();
      }
      $data['select_address'] = $this->session->data['client_address_id'];
    }

		// Customer groups 
		$data['customer_groups'] = array();
		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');

			$customer_groups = $this->model_account_customer_group->getCustomerGroups();

			foreach ($customer_groups  as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}
		$data['customer_group_id'] = $this->config->get('config_customer_group_id');


    if ($this->customer->isLogged()) {
      $this->load->model('account/customer');
      $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
      if ($customer_info != null){
        if (isset($this->request->post['firstname'])) {
          $data['firstname'] = $this->request->post['firstname'];
        } elseif (!empty($customer_info)) {
          $data['firstname'] = $customer_info['firstname'];
        } else {
          $data['firstname'] = '';
        }

        if (isset($this->request->post['lastname'])) {
          $data['lastname'] = $this->request->post['lastname'];
        } elseif (!empty($customer_info)) {
          $data['lastname'] = $customer_info['lastname'];
        } else {
          $data['lastname'] = '';
        }
        if (isset($this->request->post['email'])) {
          $data['email'] = $this->request->post['email'];
        } elseif (!empty($customer_info)) {
          $data['email'] = $customer_info['email'];
        } else {
          $data['email'] = '';
        }

        if (isset($this->request->post['telephone'])) {
          $data['telephone'] = $this->request->post['telephone'];
        } elseif (!empty($customer_info)) {
          $data['telephone'] = $customer_info['telephone'];
        } else {
          $data['telephone'] = '';
        }
      }
    }else {

      if (isset($this->session->data['guest']['firstname'])) {
        $data['firstname'] = $this->session->data['guest']['firstname'];
      } else {
        $data['firstname'] = '';
      }

      if (isset($this->session->data['guest']['lastname'])) {
        $data['lastname'] = $this->session->data['guest']['lastname'];
      } else {
        $data['lastname'] = '';
      }

      if (isset($this->session->data['guest']['telephone'])) {
        $data['telephone'] = $this->session->data['guest']['telephone'];
      } else {
        $data['telephone'] = '';
      }

      if (isset($this->session->data['guest']['email'])) {
        $data['email'] = $this->session->data['guest']['email'];
      } else {
        $data['email'] = '';
      }

      if (isset($this->session->data['guest']['password'])) {
        $data['password'] = $this->session->data['guest']['password'];
      } else {
        $data['password'] = '';
      }

      if (isset($this->session->data['guest']['confirm'])) {
        $data['confirm'] = $this->session->data['guest']['confirm'];
      } else {
        $data['confirm'] = '';
      }
    }

		// Custom Fields
		$this->load->model('account/custom_field');

		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();
		foreach($data['custom_fields'] as $key => $field){

			if ($field['location'] != 'account') {
					unset($data['custom_fields'][$key]);
					continue;
				}

			$custom_field_id = $field['custom_field_id'];

			if ( isset($this->session->data['guest']['custom_field'][$custom_field_id]) ) {
				$data['custom_fields'][$key]['value'] = $this->session->data['guest']['custom_field'][$custom_field_id];
			}

		}

		// Setting
		$data['setting'] = $setting;
		
		return $this->load->view('extension/module/custom/customer', $data);

	}

	public function update(){
		$json = array();

		$this->load->model('account/custom_field');
		$this->load->model('setting/setting');

		// Customer Group
		if (isset($this->request->get['customer_group_id'])) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$setting = json_decode($this->model_setting_setting->getSettingValue('module_custom_customer'), true);
		foreach($setting['fields'] as $field){

			if ( ($field['name'] == 'password' || $field['name'] == 'confirm') ){

				if ($this->session->data['account'] == 'register') {
					$json[] = array(
						'name' => str_replace('_', '-', $field['name']),
						'required' => true
					);
				}
				continue;

			}

			if (isset($field['customer_group']) && in_array($customer_group_id, $field['customer_group'])){

				if (isset($field['required']) && in_array($customer_group_id, $field['required'])){
					$required = true;
				} else {
					$required = false;
				}

				$json[] = array(
					'name' => str_replace('_', '-', $field['name']),
					'required' => $required
				);

			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function save(){

		$json = array();
		$customer = array();

		$this->load->language('extension/module/custom/customer');
		$this->load->model('account/custom_field');

    // Перевірка сесії
    if (!$this->customer->isLogged() && !isset($this->session->data['guest']) && !isset($this->session->data['account'])) {
      $json['error']['warning'] = $this->language->get('error_session');
      $json['redirect'] = $this->url->link('extension/module/custom', '', true);
      $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($json));
      return;
    }

		$this->load->model('setting/setting');
		$setting = json_decode($this->model_setting_setting->getSettingValue('module_custom_customer'), true);

    if ($this->customer->isLogged()) {
      $customer_group_id = $this->customer->getGroupId();
    } elseif (!empty($this->request->post['customer_group_id'])) {
      $customer_group_id = $this->request->post['customer_group_id'];
    } else {
      $customer_group_id = $this->config->get('config_customer_group_id');
    }

    $customer['customer_group_id'] = $customer_group_id;

    // Пробегаемся по полям
    foreach($setting['fields'] as $field){

      $name = $field['name'];

      // На госте не проверяем пароль и подтверждение
      if (!$this->customer->isLogged() && isset($this->session->data['account'])) {
        if ($this->session->data['account'] == 'guest' && ($name == 'password' || $name == 'confirm')) {
          continue;
        }
      }

      $value = isset($this->request->post['customer_'.$name]) ? $this->request->post['customer_'.$name] : '';
      $validation = $field['validation'];

      // Поле применимо для этой группы клиентов
      if (isset($field['customer_group']) && array_search($customer_group_id, (array)$field['customer_group']) !== false) {

        // Если поле обязательно, то проверяем его 
        if (isset($field['required']) && array_search($customer_group_id, (array)$field['required']) !== false) {

          if ($this->validate($name, $value, $validation)) {
            if (stripos($name, 'custom_field') === false) {
              $json['error'][$name] = $this->language->get('error_'.$name);
            } else {
              $custom_field = $this->model_account_custom_field->getCustomField((int)substr($name, 12));
              $json['error'][$name] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
            }
          } else {
            $customer[$name] = $value;
          }

        } else {
          $customer[$name] = $value;
        }
      }
    }

		// Якщо не авторизован - валидируем и сохраняем/регистрируем
		if (!$this->customer->isLogged()) {

			unset($this->session->data['guest']);

			// Дополнительная проверка для паролей
			if (isset($this->session->data['account']) && $this->session->data['account'] == 'register') {
				if (!isset($customer['password'])) {
					$customer['password'] = '';
					$customer['confirm'] = '';
				} elseif (isset($customer['password']) && !isset($customer['confirm'])) {
					if (!empty($customer['password'])) {
						$customer['confirm'] = $customer['password'];
					}
				} elseif (isset($customer['password']) && isset($customer['confirm'])) {
					if ($customer['password'] !== $customer['confirm']) {
						$json['error']['confirm'] = $this->language->get('error_confirm');
					}
				}
			}

			if (!$json){
				$full_customer = $this->full($customer);
				if (isset($this->session->data['account']) && $this->session->data['account'] === 'register') {
					$json = $this->addCustomer($full_customer);
				} else {
					$this->session->data['guest'] = $full_customer;
				}
			}
		} else {
      // Якщо авторизований - перевіряємо чи вибрано адресу (Торгову точку), якщо вони є
      $this->load->model('account/address');
      $customer_addresses = $this->model_account_address->getAddressCustomerCode();
      
      if (!empty($customer_addresses)) {
        if (empty($this->request->post['address_id'])) {
          // Якщо доступна лише одна торгова точка, вибираємо її автоматично.
          if (count($customer_addresses) === 1) {
            $single_address = reset($customer_addresses);
            $this->session->data['client_address_id'] = (int)$single_address['address_id'];
          } else {
            $json['error']['address_id'] = $this->language->get('error_address_id');
          }
        } else {
          $this->session->data['client_address_id'] = (int)$this->request->post['address_id'];
        }
      }

      // Зберігаємо змінені дані в сесію, щоб confirm міг їх використати (за бажанням)
      if (!$json) {
        $this->session->data['customer_override'] = $customer;
      }
    }

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function full($customer){

		// Восстанавливаем custom-поля
		foreach($customer as $key => $field){

			if (stripos($key, 'custom_field') !== false) {
				$id = (int)str_replace('custom_field', '', $key);
				if (isset($this->session->data['account']) && $this->session->data['account'] === 'register') {
					$customer['custom_field']['account'][$id] = $field;
				} else {
					$customer['custom_field'][$id] = $field;
				}
				unset($customer[$key]);
			}

		}

		// Какие поля должны быть
		$default = array(
			'customer_group_id' => '',
			'firstname' => '',
			'lastname' => '',
			'email' => '',
			'telephone' => '',
			'password' => '',
			'confirm' => '',
			'fax' => '',
			'custom_field' => array()
		);

		$result = array_merge($default, $customer);

		return $result;

	}

	private function addCustomer($customer){

		$json = array();

		$this->load->model('account/customer');
		
		// Проверяем, нет ли с таким email
		if ($this->model_account_customer->getTotalCustomersByEmail($customer['email'])) {
			$this->load->language('account/register');
			$json['error']['email'] = $this->language->get('error_exists');
		}

		// Регистририуем
		if (!$json) {

			$this->load->model('extension/module/custom/custom');
			$customer_id = $this->model_account_customer->addCustomer($customer);

			// Clear any previous login attempts for unregistered accounts.
			$this->model_account_customer->deleteLoginAttempts($customer['email']);

			// Смиотрим, что там с этой группой можно делать
			$this->load->model('account/customer_group');
			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer['customer_group_id']);

			if ($customer_group_info && !$customer_group_info['approval']) {
				$this->customer->login($customer['email'], $customer['password']);
			} else {
				$json['redirect'] = $this->url->link('account/success');
			}

			// Add to activity log
			if ($this->config->get('config_customer_activity')) {

				$this->load->model('account/activity');
				$activity_data = array(
					'customer_id' => $customer_id,
					'name'        => $customer['firstname'] . ' ' . $customer['lastname']
				);
				$this->model_account_activity->addActivity('register', $activity_data);

			}

		}

		return $json;
	}

	private function validate($name, $value, $validation = ''){

		// Проверяем на пустоту
		if (empty($value)) {
			return true;

		// Особая проверка для email	
		} elseif ($name == 'email' && !preg_match("/.+@.+\..+/i", $value)) {
			return true;

		// Проверка на регулярное выражение		
		} elseif (!empty($validation) && !filter_var($value, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $validation )))){
			return true;
		}

		return false;

	}

}
