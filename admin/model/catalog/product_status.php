<?php
class ModelCatalogProductStatus extends Model {
	public function addProductStatus($data) {
		foreach ($data['product_status'] as $language_id => $value) {
			if (isset($product_status_id)) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_status SET product_status_id = '" . (int)$product_status_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', code = '" . $this->db->escape($data['product_status_code']) . "', color = '" . $this->db->escape($data['product_status_color']) . "'");
			} else {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_status SET language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', code = '" . $this->db->escape($data['product_status_code']) . "', color = '" . $this->db->escape($data['product_status_color']) . "'");

				$product_status_id = $this->db->getLastId();
			}
		}
		$this->cache->delete('product_status');
		return $product_status_id;
	}

	public function editProductStatus($product_status_id, $data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "'");
		foreach ($data['product_status'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_status SET product_status_id = '" . (int)$product_status_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', code = '" . $this->db->escape($data['product_status_code']) . "', color = '" . $this->db->escape($data['product_status_color']) . "'");
		}
		$this->cache->delete('product_status');
	}

	public function deleteProductStatus($product_status_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "'");
		$this->cache->delete('product_status');
	}

	public function getProductStatus($product_status_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
		return $query->row;
	}

  public function getProductStatusCode($product_status_id) {
    $query = $this->db->query("SELECT code FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "' GROUP BY code");
    return $query->row['code'];
  }

  public function getProductStatusColor($product_status_id) {
    $query = $this->db->query("SELECT color FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "' GROUP BY color");
    return $query->row['color'];
  }

	public function getProductStatuses($data = array()) {
		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "product_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'";

			$sql .= " ORDER BY name";

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
		} else {
			$product_status_data = $this->cache->get('product_status.' . (int)$this->config->get('config_language_id'));

			if (!$product_status_data) {
				$query = $this->db->query("SELECT product_status_id, name FROM " . DB_PREFIX . "product_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");

				$product_status_data = $query->rows;

				$this->cache->set('product_status.' . (int)$this->config->get('config_language_id'), $product_status_data);
			}

			return $product_status_data;
		}
	}

	public function getProductStatusDescriptions($product_status_id) {
		$product_status_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "'");

		foreach ($query->rows as $result) {
			$product_status_data[$result['language_id']] = array('name' => $result['name']);
		}

		return $product_status_data;
	}

	public function getTotalProductStatuses() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row['total'];
	}
}
