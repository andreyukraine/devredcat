<?php
class ControllerLocalisationZone extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('localisation/zone');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/zone');

		$this->getList();
	}

	public function add() {
		$this->load->language('localisation/zone');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/zone');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_localisation_zone->addZone($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('localisation/zone');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/zone');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_localisation_zone->editZone($this->request->get['zone_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('localisation/zone');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('localisation/zone');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $zone_id) {
				$this->model_localisation_zone->deleteZone($zone_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

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

			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'c.name';
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
			'href' => $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('localisation/zone/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['import'] = $this->url->link('localisation/zone/import', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['import_cities'] = $this->url->link('localisation/zone/import_cities', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['import_streets'] = $this->url->link('localisation/zone/import_streets', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['import_posts'] = $this->url->link('localisation/zone/import_posts', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('localisation/zone/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['button_import_cities'] = $this->language->get('button_import_cities');
		$data['button_import_streets'] = $this->language->get('button_import_streets');
		$data['button_import_posts'] = $this->language->get('button_import_posts');

		$data['zones'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$zone_total = $this->model_localisation_zone->getTotalZones();

		$results = $this->model_localisation_zone->getZones($filter_data);

		foreach ($results as $result) {
			$data['zones'][] = array(
				'zone_id' => $result['zone_id'],
				'country' => $result['country'],
				'name'    => $result['name'] . (($result['zone_id'] == $this->config->get('config_zone_id')) ? $this->language->get('text_default') : null),
				'code'    => $result['code'],
				'edit'    => $this->url->link('localisation/zone/edit', 'user_token=' . $this->session->data['user_token'] . '&zone_id=' . $result['zone_id'] . $url, true),
				'import_cities' => $this->url->link('localisation/zone/import_cities', 'user_token=' . $this->session->data['user_token'] . '&zone_id=' . $result['zone_id'] . $url, true),
				'import_streets' => $this->url->link('localisation/zone/import_streets', 'user_token=' . $this->session->data['user_token'] . '&zone_id=' . $result['zone_id'] . $url, true),
				'import_posts' => $this->url->link('localisation/zone/import_posts', 'user_token=' . $this->session->data['user_token'] . '&zone_id=' . $result['zone_id'] . $url, true)
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

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
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

		$data['sort_country'] = $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . '&sort=c.name' . $url, true);
		$data['sort_name'] = $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . '&sort=z.name' . $url, true);
		$data['sort_code'] = $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . '&sort=z.code' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $zone_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($zone_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($zone_total - $this->config->get('config_limit_admin'))) ? $zone_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $zone_total, ceil($zone_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/zone_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['zone_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
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
			'href' => $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['zone_id'])) {
			$data['action'] = $this->url->link('localisation/zone/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('localisation/zone/edit', 'user_token=' . $this->session->data['user_token'] . '&zone_id=' . $this->request->get['zone_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['zone_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$zone_info = $this->model_localisation_zone->getZone($this->request->get['zone_id']);
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($zone_info)) {
			$data['status'] = $zone_info['status'];
		} else {
			$data['status'] = '1';
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($zone_info)) {
			$data['name'] = $zone_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['code'])) {
			$data['code'] = $this->request->post['code'];
		} elseif (!empty($zone_info)) {
			$data['code'] = $zone_info['code'];
		} else {
			$data['code'] = '';
		}

		if (isset($this->request->post['country_id'])) {
			$data['country_id'] = $this->request->post['country_id'];
		} elseif (!empty($zone_info)) {
			$data['country_id'] = $zone_info['country_id'];
		} else {
			$data['country_id'] = '';
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('localisation/zone_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'localisation/zone')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'localisation/zone')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$this->load->model('setting/store');
		$this->load->model('customer/customer');
		$this->load->model('localisation/geo_zone');

		foreach ($this->request->post['selected'] as $zone_id) {
			if ($this->config->get('config_zone_id') == $zone_id) {
				$this->error['warning'] = $this->language->get('error_default');
			}

			$store_total = $this->model_setting_store->getTotalStoresByZoneId($zone_id);

			if ($store_total) {
				$this->error['warning'] = sprintf($this->language->get('error_store'), $store_total);
			}

			$address_total = $this->model_customer_customer->getTotalAddressesByZoneId($zone_id);

			if ($address_total) {
				$this->error['warning'] = sprintf($this->language->get('error_address'), $address_total);
			}

			$zone_to_geo_zone_total = $this->model_localisation_geo_zone->getTotalZoneToGeoZoneByZoneId($zone_id);

			if ($zone_to_geo_zone_total) {
				$this->error['warning'] = sprintf($this->language->get('error_zone_to_geo_zone'), $zone_to_geo_zone_total);
			}
		}

		return !$this->error;
	}

	public function import() {
		$this->load->language('localisation/zone');

		$this->load->model('localisation/zone');
		$this->load->model('setting/setting');

		$api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');

		if (!$api_key) {
			$this->session->data['error_warning'] = 'Nova Poshta API key is missing!';
			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (!$this->user->hasPermission('modify', 'localisation/zone')) {
			$this->session->data['error_warning'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
		}

		$country_ids = array();

		if (isset($this->request->get['country_id'])) {
			$country_ids[] = (int)$this->request->get['country_id'];
		} elseif (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $country_id) {
				$country_ids[] = (int)$country_id;
			}
		}

		if (!$country_ids) {
			$country_ids[] = 220; // Default to Ukraine
		}

		$total_updated = 0;
		$total_added = 0;

		foreach ($country_ids as $country_id) {
			// 1. Fetch Areas
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode([
					'apiKey' => $api_key,
					'modelName' => 'Address',
					'calledMethod' => 'getAreas',
					'methodProperties' => new stdClass()
				]),
				CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			]);
			$response = curl_exec($curl);
			curl_close($curl);

			$areas_data = json_decode($response, true);

			if (!empty($areas_data['success']) && !empty($areas_data['data'])) {
				foreach ($areas_data['data'] as $area) {
					$name = $area['Description'];
					$ref = $area['Ref'];
					
					$check_query = $this->db->query("SELECT zone_id FROM " . DB_PREFIX . "zone WHERE np_area_ref = '" . $this->db->escape($ref) . "' LIMIT 1");
					
					if ($check_query->num_rows) {
						$this->db->query("UPDATE " . DB_PREFIX . "zone SET name = '" . $this->db->escape($name) . "' WHERE zone_id = '" . (int)$check_query->row['zone_id'] . "'");
						$total_updated++;
					} else {
						$name_query = $this->db->query("SELECT zone_id FROM " . DB_PREFIX . "zone WHERE country_id = " . (int)$country_id . " AND name = '" . $this->db->escape($name) . "' LIMIT 1");
						
						if ($name_query->num_rows) {
							$this->db->query("UPDATE " . DB_PREFIX . "zone SET np_area_ref = '" . $this->db->escape($ref) . "' WHERE zone_id = '" . (int)$name_query->row['zone_id'] . "'");
							$total_updated++;
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "zone SET country_id = " . (int)$country_id . ", name = '" . $this->db->escape($name) . "', code = '', status = 1, np_area_ref = '" . $this->db->escape($ref) . "'");
							$total_added++;
						}
					}
				}
			}
		}

		$this->session->data['success'] = sprintf('Success: Updated %d areas and added %d new areas!', $total_updated, $total_added);

		$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import_cities() {
		$this->load->language('localisation/zone');

		$this->load->model('setting/setting');

		$api_key = $this->model_setting_setting->getSettingValue('shipping_np_key');

		if (!$api_key) {
			$this->session->data['error_warning'] = 'Nova Poshta API key is missing!';
			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
		}

		$zone_ids = array();

		if (isset($this->request->get['zone_id'])) {
			$zone_ids[] = (int)$this->request->get['zone_id'];
		} elseif (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $zone_id) {
				$zone_ids[] = (int)$zone_id;
			}
		}

		if (!$zone_ids) {
			$this->session->data['error_warning'] = 'No zones selected!';
			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
		}

		// Get names of selected zones for matching
		$zones = array();
		foreach ($zone_ids as $zone_id) {
			$zone_query = $this->db->query("SELECT zone_id, name FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "'");
			if ($zone_query->num_rows) {
				$zones[$zone_id] = str_replace(' область', '', $zone_query->row['name']);
			}
		}

		$page = 1;
		$limit = 500;
		$total_imported = 0;

		while (true) {
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode([
					'apiKey' => $api_key,
					'modelName' => 'Address',
					'calledMethod' => 'getSettlements',
					'methodProperties' => [
						'Page' => $page,
						'Limit' => $limit
					]
				]),
				CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			]);
			$response = curl_exec($curl);
			curl_close($curl);

			$settlements_data = json_decode($response, true);

			if (empty($settlements_data['success']) || empty($settlements_data['data'])) {
				break;
			}

			foreach ($settlements_data['data'] as $settlement) {
				foreach ($zones as $zid => $zname) {
					if (strpos($settlement['AreaDescription'], $zname) !== false) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "city SET np_city_id = '" . $this->db->escape($settlement['Ref']) . "', name = '" . $this->db->escape($settlement['Description']) . "', zone_id = '" . (int)$zid . "', np_area_ref = '" . $this->db->escape($settlement['Area']) . "', settlement_type = '" . $this->db->escape($settlement['SettlementTypeDescription']) . "' ON DUPLICATE KEY UPDATE name = '" . $this->db->escape($settlement['Description']) . "', zone_id = '" . (int)$zid . "', np_area_ref = '" . $this->db->escape($settlement['Area']) . "', settlement_type = '" . $this->db->escape($settlement['SettlementTypeDescription']) . "'");
						$total_imported++;
						break; // Found matching zone, move to next settlement
					}
				}
			}

			if (count($settlements_data['data']) < $limit) {
				break;
			}

			$page++;
			if ($page > 100) break; 
		}

		$this->session->data['success'] = 'Success: Imported ' . $total_imported . ' settlements for selected zones!';
		$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import_streets() {
		$this->load->language('localisation/zone');

		$zone_ids = array();
		if (isset($this->request->get['zone_id'])) {
			$zone_ids[] = (int)$this->request->get['zone_id'];
		} elseif (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $zone_id) {
				$zone_ids[] = (int)$zone_id;
			}
		}

		if (!$zone_ids) {
			$this->session->data['error_warning'] = 'No zones selected!';
			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
		}

		$script = DIR . 'crons/cron_streets.php';

		if (file_exists($script)) {
			// Очищаємо старий статус
			$status_file = DIR_STORAGE . 'logs/np_streets_import.json';
			if (file_exists($status_file)) {
				unlink($status_file);
			}

			$php_path = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
			$zone_list = implode(',', $zone_ids);

			$command = "nohup " . $php_path . " " . $script . " " . $zone_list . " > /dev/null 2>&1 &";
			exec($command);
			
			$this->session->data['success'] = 'Успішно: Імпорт вулиць для вибраних областей запущено у фоновому режимі.';
		} else {
			$this->session->data['error_warning'] = 'Помилка: Файл ' . $script . ' не знайдено!';
		}

		$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import_posts() {
		$this->load->language('localisation/zone');

		$zone_ids = array();
		if (isset($this->request->get['zone_id'])) {
			$zone_ids[] = (int)$this->request->get['zone_id'];
		} elseif (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $zone_id) {
				$zone_ids[] = (int)$zone_id;
			}
		}

		if (!$zone_ids) {
			$this->session->data['error_warning'] = 'No zones selected!';
			$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
		}

		$script = DIR . 'crons/cron_posts.php';

		if (file_exists($script)) {
			// Очищаємо старий статус
			$status_file = DIR_STORAGE . 'logs/np_posts_import.json';
			if (file_exists($status_file)) {
				unlink($status_file);
			}

			$php_path = (defined('PHP_BINARY') && PHP_BINARY) ? PHP_BINARY : 'php';
			$zone_list = implode(',', $zone_ids);

			// Додаємо повний шлях до php і запускаємо через nohup для надійності
			$command = "nohup " . $php_path . " " . $script . " " . $zone_list . " > /dev/null 2>&1 &";
			exec($command);
			
			$this->session->data['success'] = 'Успішно: Імпорт відділень для вибраних областей запущено у фоновому режимі.';
		} else {
			$this->session->data['error_warning'] = 'Помилка: Файл ' . $script . ' не знайдено!';
		}

		$this->response->redirect($this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function get_import_status() {
		$json = array();

		$streets_status = DIR_STORAGE . 'logs/np_streets_import.json';
		$posts_status = DIR_STORAGE . 'logs/np_posts_import.json';

		if (file_exists($streets_status)) {
			$data = json_decode(file_get_contents($streets_status), true);
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
