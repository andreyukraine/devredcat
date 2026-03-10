<?php
class ModelLocalisationPost extends Model {
	public function getPosts($data = array()) {
		$sql = "SELECT p.*, c.name AS city, z.name AS zone FROM " . DB_PREFIX . "post p LEFT JOIN " . DB_PREFIX . "city c ON (p.city_id = c.city_id) LEFT JOIN " . DB_PREFIX . "zone z ON (c.zone_id = z.zone_id)";

		$sort_data = array(
			'p.name',
			'c.name',
			'p.type'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY p.name";
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

	public function getTotalPosts() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "post");

		return $query->row['total'];
	}
}
