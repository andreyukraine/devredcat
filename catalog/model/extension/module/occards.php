<?php

class ModelExtensionModuleOccards extends Model
{

  public function getCardCustomer($customer_id){
    $card = null;
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cards` WHERE customer_id = '" . $customer_id . "'");
    if ($query->num_rows) {
      $card = $query->row;
    }
    return $card;
  }

  public function setCardPercent($card_id, $percent){
    $this->db->query("UPDATE " . DB_PREFIX . "cards SET percent = '" . (int)$percent . "'  WHERE card_id = '" . (int)$card_id . "'");
  }

  public function clearCardCustomer($customer_id)
  {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "cards WHERE customer_id = '" . (int)$customer_id . "'");
    if ($query->rows){
      $this->db->query("UPDATE " . DB_PREFIX . "cards SET customer_id = '', percent = ''  WHERE card_id = '" . (int)$query->row['card_id'] . "'");
    }
  }

  public function setCardCustomer($phone, $customer_id){
    if (!empty($phone)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cards` WHERE phone = '" . $phone . "' ORDER BY card_id ASC");
      if ($query->rows){
        $this->db->query("UPDATE " . DB_PREFIX . "cards SET customer_id = '" . (int)$customer_id . "'  WHERE phone = '" . $phone . "'");
      }
    }
  }

  public function editCardPhone($phone, $data){
    if (!empty($phone)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cards` WHERE phone = '" . $phone . "' ORDER BY card_id ASC");
      if ($query->rows){
        $this->db->query("UPDATE " . DB_PREFIX . "cards SET customer_id = '" . (int)$data['customer_id'] . "'  WHERE phone = '" . $phone . "'");
      }
    }
  }

  public function addCard($data = array()){
    if (!empty($data)){
      $this->db->query("INSERT INTO " . DB_PREFIX . "cards SET guid = '" . $data['guid'] . "', `card_number` = '" . $data['card_number'] . "', card_code = '" . $data['card_code'] . "', status = '" . (int)$data['status'] . "', phone = '" . $data['phone'] . "', sum = '" . $data['sum'] . "', date_modified = NOW(), date_added = NOW()");
    }
  }

  public function calculateEAN13CheckDigit($phone) {
    $type = 13;
    $even = 0;
    $odd = 0;

    if (strlen($phone) > 0) {

      $phoneText = "9" . $phone;

      for ($i = 0; $i < strlen($phoneText); $i++) {
        if ($i % 2 === 0) {
          $odd += intval($phoneText[$i]);
        } else {
          $even += intval($phoneText[$i]);
        }
      }

      if ($type === 13) {
        $even *= 3;
      } else {
        $odd *= 3;
      }

      $control = 10 - ($even + $odd) % 10;
      $controlChar = ($control === 10) ? "0" : strval($control);
      return $phoneText . $controlChar;
    }
    return $phone;
  }


}
