<?php
class ModelLocalisationCity extends Model {
	public function getCities($data = array()) {
		$sql = "SELECT nc.*, z.name AS zone FROM " . DB_PREFIX . "city nc LEFT JOIN " . DB_PREFIX . "zone z ON (nc.zone_id = z.zone_id)";

		$sort_data = array(
			'nc.name',
			'z.name',
			'nc.settlement_type'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY nc.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalCities() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "city");

		return $query->row['total'];
	}

	public function cronImportStreets($zone_ids = array()) {
		$api_key = $this->config->get('shipping_np_key');

		if (!$api_key) {
			return 'Nova Poshta API key is missing!';
		}

		$status_file = DIR_STORAGE . 'logs/np_streets_import.json';
		
		$sql = "SELECT city_id, np_city_id, name FROM " . DB_PREFIX . "city";
		if (!empty($zone_ids)) {
			$sql .= " WHERE zone_id IN (" . implode(',', array_map('intval', $zone_ids)) . ")";
		}
		
		$cities_query = $this->db->query($sql);
		$total_cities = $cities_query->num_rows;
		$total_imported = 0;

		foreach ($cities_query->rows as $index => $city) {
			// Update status
			file_put_contents($status_file, json_encode([
				'status' => 'processing',
				'total' => $total_cities,
				'current' => $index + 1,
				'current_city' => $city['name'],
				'imported' => $total_imported,
				'time' => time()
			]));
// ...

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
						'SettlementRef' => $city['np_city_id']
					]
				]),
				CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			]);
			$response = curl_exec($curl);
			curl_close($curl);

			$streets_data = json_decode($response, true);

			if (!empty($streets_data['success']) && !empty($streets_data['data'])) {
				foreach ($streets_data['data'] as $street) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "street SET np_street_id = '" . $this->db->escape($street['Ref']) . "', name = '" . $this->db->escape($street['Description']) . "', city_id = '" . (int)$city['city_id'] . "' ON DUPLICATE KEY UPDATE name = '" . $this->db->escape($street['Description']) . "', city_id = '" . (int)$city['city_id'] . "'");
					$total_imported++;
				}
			}
			// Small sleep to avoid hitting API rate limits if any
			usleep(100000); // 0.1 sec
		}

		file_put_contents($status_file, json_encode([
			'status' => 'completed',
			'total' => $total_cities,
			'current' => $total_cities,
			'imported' => $total_imported,
			'time' => time()
		]));

		return 'Success: Imported ' . $total_imported . ' streets for all cities!';
	}

	public function cronImportPosts($zone_ids = array()) {
		$api_key = $this->config->get('shipping_np_key');

		if (!$api_key) {
			return 'Nova Poshta API key is missing!';
		}

		$status_file = DIR_STORAGE . 'logs/np_posts_import.json';
		
		// Негайно створюємо файл статусу, щоб адмінка бачила початок процесу
		file_put_contents($status_file, json_encode([
			'status' => 'processing',
			'total' => 100,
			'current' => 1,
			'imported' => 0,
			'time' => time()
		]));

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => 'https://api.novaposhta.ua/v2.0/json/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				'apiKey' => $api_key,
				'modelName' => 'Address',
				'calledMethod' => 'getWarehouses',
				'methodProperties' => new stdClass()
			]),
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		]);
		$response = curl_exec($curl);
		curl_close($curl);

		$warehouses_data = json_decode($response, true);
		$total_imported = 0;

		if (!empty($warehouses_data['success']) && !empty($warehouses_data['data'])) {
			$total_items = count($warehouses_data['data']);
			
			// Pre-fetch cities to map np_city_id to city_id
			$cities = array();
			$sql = "SELECT city_id, np_city_id FROM " . DB_PREFIX . "city";
			if (!empty($zone_ids)) {
				$sql .= " WHERE zone_id IN (" . implode(',', array_map('intval', $zone_ids)) . ")";
			}
			$city_query = $this->db->query($sql);
			foreach ($city_query->rows as $city) {
				$cities[$city['np_city_id']] = $city['city_id'];
			}

			foreach ($warehouses_data['data'] as $index => $warehouse) {
				if ($index % 100 == 0) {
					file_put_contents($status_file, json_encode([
						'status' => 'processing',
						'total' => $total_items,
						'current' => $index,
						'imported' => $total_imported,
						'time' => time()
					]));
				}

				$city_id = isset($cities[$warehouse['SettlementRef']]) ? $cities[$warehouse['SettlementRef']] : 0;
				if ($city_id) {
					$type = (strpos($warehouse['TypeOfWarehouse'], 'f9316480') !== false) ? 'post_machine' : 'branch';
					$this->db->query("INSERT INTO " . DB_PREFIX . "post SET np_ref = '" . $this->db->escape($warehouse['Ref']) . "', name = '" . $this->db->escape($warehouse['Description']) . "', number = '" . (int)$warehouse['Number'] . "', city_id = '" . (int)$city_id . "', type = '" . $this->db->escape($type) . "', address = '" . $this->db->escape($warehouse['ShortAddress']) . "' ON DUPLICATE KEY UPDATE name = '" . $this->db->escape($warehouse['Description']) . "', number = '" . (int)$warehouse['Number'] . "', city_id = '" . (int)$city_id . "', type = '" . $this->db->escape($type) . "', address = '" . $this->db->escape($warehouse['ShortAddress']) . "'");
					$total_imported++;
				}
			}
		}

		file_put_contents($status_file, json_encode([
			'status' => 'completed',
			'total' => isset($total_items) ? $total_items : 0,
			'current' => isset($total_items) ? $total_items : 0,
			'imported' => $total_imported,
			'time' => time()
		]));

		return 'Success: Imported ' . $total_imported . ' warehouses/post machines for all cities!';
	}
}
