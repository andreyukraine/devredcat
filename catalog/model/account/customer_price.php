<?php
class ModelAccountCustomerPrice extends Model {
	public function getCustomerPrice($customer_price_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_price WHERE customer_price_id = '" . (int)$customer_price_id . "'");
		return $query->row;
	}

  public function getCustomerPriceByUid($guid) {
    $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer_price WHERE guid = '" . $this->db->escape($guid) . "'");
    return $query->row;
  }

}
