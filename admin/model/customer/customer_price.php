<?php
class ModelCustomerCustomerPrice extends Model {
	public function addCustomerPrice($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_price SET name = '" . $this->db->escape($data['name']) . "', guid = '" . $this->db->escape($data['guid']) . "'");

		return $this->db->getLastId();
	}

	public function editCustomerPrice($customer_price_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "customer_price SET name = '" . $this->db->escape($data['name']) . "', guid = '" . $this->db->escape($data['guid']) . "' WHERE customer_price_id = '" . (int)$customer_price_id . "'");
	}

	public function deleteCustomerPrice($customer_price_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_price WHERE customer_price_id = '" . (int)$customer_price_id . "'");
	}

	public function getCustomerPrice($customer_price_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_price WHERE customer_price_id = '" . (int)$customer_price_id . "'");

		return $query->row;
	}

	public function getCustomerPrices($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "customer_price";

		$sort_data = array(
			'name',
			'guid'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	public function getTotalCustomerPrices() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_price");

		return $query->row['total'];
	}
}
