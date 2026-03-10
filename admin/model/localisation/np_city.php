<?php
class ModelLocalisationNpCity extends Model {
	public function getCities($data = array()) {
		$sql = "SELECT nc.*, z.name AS zone FROM " . DB_PREFIX . "np_city nc LEFT JOIN " . DB_PREFIX . "zone z ON (nc.zone_id = z.zone_id)";

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
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "np_city");

		return $query->row['total'];
	}
}
