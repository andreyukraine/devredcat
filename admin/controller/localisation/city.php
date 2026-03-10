<?php
class ControllerLocalisationCity extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('localisation/city');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/city');

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'nc.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['column_np_id'] = $this->language->get('column_np_id');
		$data['column_action'] = $this->language->get('column_action');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_zone'] = $this->language->get('column_zone');
		$data['column_type'] = $this->language->get('column_type');

		$data['import'] = $this->url->link('localisation/zone/import', 'user_token=' . $this->session->data['user_token'], true);
		$data['import_all_streets'] = $this->url->link('localisation/city/import_all_streets', 'user_token=' . $this->session->data['user_token'], true);
		$data['import_all_warehouses'] = $this->url->link('localisation/city/import_all_warehouses', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['button_import'] = $this->language->get('button_import');
		$data['button_import_streets'] = $this->language->get('button_import_streets');
		$data['button_import_warehouses'] = $this->language->get('button_import_warehouses');
		$data['button_import_all_streets'] = $this->language->get('button_import_all_streets');
		$data['button_import_all_warehouses'] = $this->language->get('button_import_all_warehouses');

		$data['cities'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$city_total = $this->model_localisation_city->getTotalCities();

		$results = $this->model_localisation_city->getCities($filter_data);

		foreach ($results as $result) {
			$data['cities'][] = array(
				'city_id'     => $result['city_id'],
				'np_city_id'  => $result['np_city_id'],
				'name'        => $result['name'],
				'zone'        => $result['zone'],
				'type'        => $result['settlement_type'],
				'import_streets' => $this->url->link('localisation/city/import_streets', 'user_token=' . $this->session->data['user_token'] . '&city_id=' . $result['city_id'] . $url, true),
				'import_warehouses' => $this->url->link('localisation/city/import_warehouses', 'user_token=' . $this->session->data['user_token'] . '&city_id=' . $result['city_id'] . $url, true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'] . '&sort=nc.name' . $url, true);
		$data['sort_zone'] = $this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'] . '&sort=z.name' . $url, true);
		$data['sort_type'] = $this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'] . '&sort=nc.settlement_type' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $city_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($city_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($city_total - $this->config->get('config_limit_admin'))) ? $city_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $city_total, ceil($city_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/city_list', $data));
	}

	public function import_streets() {
		$this->load->language('localisation/city');
		$this->load->model('localisation/city');

		if (isset($this->request->get['city_id'])) {
			$city_id = (int)$this->request->get['city_id'];
			
			$city_query = $this->db->query("SELECT np_city_id FROM " . DB_PREFIX . "city WHERE city_id = '" . (int)$city_id . "'");
			
			if ($city_query->num_rows) {
				$this->load->model('setting/setting');
				$api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');

				if (!$api_key) {
					$this->session->data['error_warning'] = 'Nova Poshta API key is missing!';
					$this->response->redirect($this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'], true));
				}

				$np_city_id = $city_query->row['np_city_id'];

				$curl = curl_init();
				curl_setopt_array($curl, [
					CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => json_encode([
						'apiKey' => $api_key,
						'modelName' => 'Address',
						'calledMethod' => 'getSettlementStreets',
						'methodProperties' => [
							'SettlementRef' => $np_city_id
						]
					]),
					CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
				]);
				$response = curl_exec($curl);
				curl_close($curl);

				$streets_data = json_decode($response, true);

				if (!empty($streets_data['success']) && !empty($streets_data['data'])) {
					foreach ($streets_data['data'] as $street) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "street SET np_street_id = '" . $this->db->escape($street['Ref']) . "', name = '" . $this->db->escape($street['Description']) . "', city_id = '" . (int)$city_id . "' ON DUPLICATE KEY UPDATE name = '" . $this->db->escape($street['Description']) . "', city_id = '" . (int)$city_id . "'");
					}
					$this->session->data['success'] = 'Success: Imported ' . count($streets_data['data']) . ' streets!';
				} else {
					$this->session->data['error_warning'] = 'No streets found for this city.';
				}
			}
		}

		$this->response->redirect($this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import_warehouses() {
		$this->load->language('localisation/city');
		$this->load->model('localisation/city');

		if (isset($this->request->get['city_id'])) {
			$city_id = (int)$this->request->get['city_id'];
			
			$city_query = $this->db->query("SELECT np_city_id FROM " . DB_PREFIX . "city WHERE city_id = '" . (int)$city_id . "'");
			
			if ($city_query->num_rows) {
				$this->load->model('setting/setting');
				$api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');

				if (!$api_key) {
					$this->session->data['error_warning'] = 'Nova Poshta API key is missing!';
					$this->response->redirect($this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'], true));
				}

				$np_city_id = $city_query->row['np_city_id'];

				$curl = curl_init();
				curl_setopt_array($curl, [
					CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => json_encode([
						'apiKey' => $api_key,
						'modelName' => 'Address',
						'calledMethod' => 'getWarehouses',
						'methodProperties' => [
							'SettlementRef' => $np_city_id
						]
					]),
					CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
				]);
				$response = curl_exec($curl);
				curl_close($curl);

				$warehouses_data = json_decode($response, true);

				if (!empty($warehouses_data['success']) && !empty($warehouses_data['data'])) {
					foreach ($warehouses_data['data'] as $warehouse) {
						$type = (strpos($warehouse['TypeOfWarehouse'], 'f9316480') !== false) ? 'post_machine' : 'branch';
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "post SET np_ref = '" . $this->db->escape($warehouse['Ref']) . "', name = '" . $this->db->escape($warehouse['Description']) . "', number = '" . (int)$warehouse['Number'] . "', city_id = '" . (int)$city_id . "', type = '" . $this->db->escape($type) . "', address = '" . $this->db->escape($warehouse['ShortAddress']) . "' ON DUPLICATE KEY UPDATE name = '" . $this->db->escape($warehouse['Description']) . "', number = '" . (int)$warehouse['Number'] . "', city_id = '" . (int)$city_id . "', type = '" . $this->db->escape($type) . "', address = '" . $this->db->escape($warehouse['ShortAddress']) . "'");
					}
					$this->session->data['success'] = 'Success: Imported ' . count($warehouses_data['data']) . ' warehouses/post machines!';
				} else {
					$this->session->data['error_warning'] = 'No warehouses found for this city.';
				}
			}
		}

		$this->response->redirect($this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import_all_streets() {
		$this->load->language('localisation/city');

		$script = DIR . 'crons/cron_streets.php';

		if (file_exists($script)) {
			// Очищаємо старий статус
			$status_file = DIR_STORAGE . 'logs/np_streets_import.json';
			if (file_exists($status_file)) {
				unlink($status_file);
			}

			// Визначаємо шлях до PHP
			$php_path = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
			
			// Запускаємо крон-скрипт у фоновому режимі з повним шляхом
			exec($php_path . " " . $script . " > /dev/null 2>&1 &");
			$this->session->data['success'] = 'Успішно: Імпорт усіх вулиць запущено у фоновому режимі. Це може зайняти досить тривалий час (міст: ' . $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "city")->row['total'] . ').';
		} else {
			$this->session->data['error_warning'] = 'Помилка: Файл ' . $script . ' не знайдено!';
		}

		$this->response->redirect($this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import_all_warehouses() {
		$this->load->language('localisation/city');

		$script = DIR . 'crons/cron_posts.php';

		if (file_exists($script)) {
			// Очищаємо старий статус
			$status_file = DIR_STORAGE . 'logs/np_posts_import.json';
			if (file_exists($status_file)) {
				unlink($status_file);
			}

			// Визначаємо шлях до PHP
			$php_path = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';

			// Запускаємо крон-скрипт у фоновому режимі з повним шляхом
			exec($php_path . " " . $script . " > /dev/null 2>&1 &");
			$this->session->data['success'] = 'Успішно: Імпорт усіх відділень та поштоматів запущено у фоновому режимі.';
		} else {
			$this->session->data['error_warning'] = 'Помилка: Файл ' . $script . ' не знайдено!';
		}

		$this->response->redirect($this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function get_import_status() {
		$json = array();

		$streets_status = DIR_STORAGE . 'logs/np_streets_import.json';
		$posts_status = DIR_STORAGE . 'logs/np_posts_import.json';

		if (file_exists($streets_status)) {
			$data = json_decode(file_get_contents($streets_status), true);
			// Only show if modified in the last 15 minutes
			if (isset($data['time']) && (time() - $data['time'] < 900)) {
				$json['streets'] = $data;
			}
		}

		if (file_exists($posts_status)) {
			$data = json_decode(file_get_contents($posts_status), true);
			if (isset($data['time']) && (time() - $data['time'] < 900)) {
				$json['posts'] = $data;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
