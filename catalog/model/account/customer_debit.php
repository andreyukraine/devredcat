<?php
class ModelAccountCustomerDebit extends Model {


  public function getDebits($customer_id, $customer_cod_guid) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_debit WHERE customer_id = '" . (int)$customer_id . "' AND customer_cod_guid = '" . $this->db->escape($customer_cod_guid). "'");
    return $query->rows;
  }

  public function updateDebit($customer_id, $customer_cod_guid, $data)
  {
    $this->db->query("UPDATE `" . DB_PREFIX . "customer_debit` SET 
    `number` = '" . $this->db->escape($data['number']) . "', 
    `date` = '" . $this->db->escape($data['date']) . "', 
    `sum_minus` = '" . (double)($data['sum_minus']) . "', 
    `sum_plus` = '" . (double)($data['sum_plus']) . "', 
    `sum_debit` = '" . (double)($data['sum_debit']) . "', 
    `day_minus` = '" . (int)$data['day_minus'] . "', 
    `day_plus` = '" . (int)$data["day_plus"] . "' 
    WHERE `customer_id` = '" . (int)$customer_id . "' AND customer_cod_guid = '" . $this->db->escape($customer_cod_guid). "'");
  }

  public function addDebit($customer_id, $customer_cod_guid, $data)
  {
    $this->db->query("INSERT INTO `" . DB_PREFIX . "customer_debit` 
    SET customer_id = '" . (int)$customer_id . "', 
    `customer_cod_guid` = '" . $this->db->escape($customer_cod_guid) . "', 
    `number` = '" . $this->db->escape($data['number']) . "', 
    `date` = '" . $this->db->escape($data['date']) . "', 
    `sum_minus` = '" . (double)($data['sum_minus']) . "', 
    `sum_plus` = '" . (double)($data['sum_plus']) . "', 
    `sum_debit` = '" . (double)($data['sum_debit']) . "', 
    `contract_bonus` = '" . (double)$data['contract_bonus'] . "', 
    `day_minus` = '" . (int)$data['day_minus'] . "', 
    `day_plus` = '" . (int)(isset($data['day_plus']) ? $data['day_plus'] : 0) . "'");
    return $this->db->getLastId();
  }

  public function deleteDebits($customer_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "customer_debit WHERE customer_id = '" . (int)$customer_id . "'");
  }
}
