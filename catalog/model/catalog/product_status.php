<?php
class ModelCatalogProductStatus extends Model {

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

	public function getProductStatusDescriptions($product_status_id) {
		$product_status_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_status WHERE product_status_id = '" . (int)$product_status_id . "'");

		foreach ($query->rows as $result) {
			$product_status_data[$result['language_id']] = array('name' => $result['name']);
		}

		return $product_status_data;
	}

}
