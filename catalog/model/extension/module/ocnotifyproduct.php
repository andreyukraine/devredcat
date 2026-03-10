<?php

class ModelExtensionModuleOcnotifyproduct extends Model {
	public function addRequest($customer_id, $data) {
		$this->db->query("INSERT INTO `". DB_PREFIX ."notifyproduct` SET 
		`customer_id` = '". (int)$customer_id ."',
		`product_id` = '". (int)$data['product_id'] ."',
		`options` = '". $this->db->escape($data['options']) ."', 
		`date` = NOW()
		");
	}

  public function getRequest($customer_id, $data) {
    $sql = "SELECT * FROM " . DB_PREFIX . "notifyproduct WHERE 
    `customer_id` = '". (int)$customer_id ."' AND
		`product_id` = '". (int)$data['product_id'] ."' AND
		`options` = '". $this->db->escape($data['options']) ."' AND
		`notify` = 0";
    $query = $this->db->query($sql);
    return $query->row;
  }
}
