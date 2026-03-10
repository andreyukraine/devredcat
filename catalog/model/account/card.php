<?php
class ModelAccountCard extends Model {

	public function editCustomer($customer_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET telephone = '" . $this->db->escape($data['telephone']) . "' WHERE customer_id = '" . (int)$customer_id . "'");
	}

  public function getCustomer($customer_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");
    return $query->row;
  }

  public function getCardByCustomerId($customer_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cards WHERE customer_id = '" . (int)$customer_id . "'");
    return $query->row;
  }

  public function getCardByCode($card_code) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cards WHERE card_code = '" . (int)$card_code . "'");
    return $query->row;
  }

  public function updateCard($card, $data)
  {
    $sum = 0;
    if (isset($data['sum'])) {
      $sum = (int)$data['sum'];
    }
    $current_date = date('Y-m-d H:i:s');
    $this->db->query("UPDATE `" . DB_PREFIX . "cards` SET date_modified = '" . $current_date . "', `phone` = '" . $this->db->escape($data['phone']) . "', `card_code` = '" . $this->db->escape($data['code']) . "', `sum` = '" . $sum . "', card_name = '" . $this->db->escape($data['name']) . "', `card_code_qr` = '" . $this->db->escape($data["code_qr"]) . "' WHERE `card_id` = '" . (int)$card["card_id"] . "'");
  }

  public function addCard($data)
  {
    $sum = 0;
    if (isset($data['sum'])) {
      $sum = (int)$data['sum'];
    }
    $current_date = date('Y-m-d H:i:s');
    $this->db->query("INSERT INTO `" . DB_PREFIX . "cards` SET guid = '" . $this->db->escape($data['guid']) . "', customer_id = '" . (int)$data['customer_id'] . "', card_code = '" . $this->db->escape($data['customer_cod_guid']) . "', phone = '" . $this->db->escape($data['phone']) . "', card_name = '" . $this->db->escape($data['name']) . "', `sum` = '" . $sum . "', `card_code_qr` = '" . $this->db->escape($data["code_qr"]) . "', date_added = '" . $current_date . "', date_modified = '" . $current_date . "'");
    return $this->db->getLastId();
  }

  public function getCardByUid($uid)
  {
    $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "cards WHERE guid = '" . $uid . "'");
    return $query->row;
  }
}
