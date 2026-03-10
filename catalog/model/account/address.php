<?php

class ModelAccountAddress extends Model
{
  public function addAddress($customer_id, $data)
  {
    $this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "', customer_cod_guid = '" . $this->db->escape(isset($data['customer_cod_guid']) ? $data['customer_cod_guid'] : '') . "', comment = '" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "'");

    $address_id = $this->db->getLastId();

    if (!empty($data['default'])) {
      $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
    }

    return $address_id;
  }

  public function editAddress($address_id, $data)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "address SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "', customer_cod_guid = '" . $this->db->escape(isset($data['customer_cod_guid']) ? $data['customer_cod_guid'] : '') . "', comment = '" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "' WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

    if (!empty($data['default'])) {
      $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
    }
  }

  public function getAddressByNP($customer_id, $customer_cod_guid, $type, $data)
  {
    $sql = "SELECT address_id FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "' AND customer_cod_guid = '" . $this->db->escape($customer_cod_guid) . "' AND type = '" . $this->db->escape($type) . "'";

    if ($type == 'np_post' || $type == 'np_poshtomat') {
      $sql .= " AND np_city_id = '" . $this->db->escape($data['city_id']) . "' AND np_post_id = '" . $this->db->escape($data['post_id']) . "'";
    } elseif ($type == 'np_dveri') {
      $sql .= " AND np_city_id = '" . $this->db->escape($data['city_id']) . "' AND np_street_id = '" . $this->db->escape($data['street_id']) . "' AND np_house = '" . $this->db->escape($data['house']) . "' AND np_apartment = '" . $this->db->escape($data['apartment']) . "'";
    } else {
      return false;
    }

    $query = $this->db->query($sql);

    return $query->row ? $query->row['address_id'] : false;
  }

  public function deleteAddress($address_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
    $default_query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "customer WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
    if ($default_query->num_rows) {
      $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = 0 WHERE customer_id = '" . (int)$this->customer->getId() . "'");
    }
  }

  public function deleteAddressByCustomerId($customer_id)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");
    $this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = 0 WHERE customer_id = '" . (int)$customer_id . "'");
  }

  public function getAddress($address_id)
  {
    return $this->getAddressForOrder($address_id, $this->customer->getId());
  }

  public function getAddressForOrder($address_id, $customer_id)
  {
    $address_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$customer_id . "'");

    if ($address_query->num_rows) {
      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

      if ($country_query->num_rows) {
        $country = $country_query->row['name'];
        $iso_code_2 = $country_query->row['iso_code_2'];
        $iso_code_3 = $country_query->row['iso_code_3'];
        $address_format = $country_query->row['address_format'];
      } else {
        $country = '';
        $iso_code_2 = '';
        $iso_code_3 = '';
        $address_format = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");

      if ($zone_query->num_rows) {
        $zone = $zone_query->row['name'];
        $zone_code = $zone_query->row['code'];
      } else {
        $zone = '';
        $zone_code = '';
      }

      $address_data = array(
        'address_id' => $address_query->row['address_id'],
        'firstname' => $address_query->row['firstname'],
        'lastname' => $address_query->row['lastname'],
        'company' => $address_query->row['company'],
        'address_1' => $address_query->row['address_1'],
        'address_2' => $address_query->row['address_2'],
        'postcode' => $address_query->row['postcode'],
        'city' => $address_query->row['city'],
        'type' => $address_query->row['type'],
        'guid' => $address_query->row['guid'],
        'customer_cod_guid' => $address_query->row['customer_cod_guid'],
        'customer_price_id' => $address_query->row['customer_price_id'],
        'customer_type' => $address_query->row['customer_type'],
        'ourdelivery' => $address_query->row['ourdelivery'],
        'np_city_id' => $address_query->row['np_city_id'],
        'np_city_name' => $address_query->row['np_city_name'],
        'np_street_id' => $address_query->row['np_street_id'],
        'np_street_name' => $address_query->row['np_street_name'],
        'np_post_id' => $address_query->row['np_post_id'],
        'np_post_name' => $address_query->row['np_post_name'],
        'np_house' => $address_query->row['np_house'],
        'np_level' => $address_query->row['np_level'],
        'np_apartment' => $address_query->row['np_apartment'],
        'np_customer_name' => $address_query->row['np_customer_name'],
        'np_customer_lastname' => $address_query->row['np_customer_lastname'],
        'np_customer_phone' => $address_query->row['np_customer_phone'],
        'comment' => $address_query->row['comment'],
        'zone_id' => $address_query->row['zone_id'],
        'zone' => $zone,
        'zone_code' => $zone_code,
        'country_id' => $address_query->row['country_id'],
        'country' => $country,
        'iso_code_2' => $iso_code_2,
        'iso_code_3' => $iso_code_3,
        'address_format' => $address_format,
        'delay_pay' => $address_query->row['delay_pay'],
        'delay_reserv' => $address_query->row['delay_reserv'],
        'custom_field' => json_decode($address_query->row['custom_field'], true)
      );

      return $address_data;
    } else {
      return false;
    }
  }

  public function getAddressCustomerCode(){
    $address_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "' AND customer_cod_guid != ''  GROUP BY customer_cod_guid");
    foreach ($query->rows as $result) {
      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");

      if ($country_query->num_rows) {
        $country = $country_query->row['name'];
        $iso_code_2 = $country_query->row['iso_code_2'];
        $iso_code_3 = $country_query->row['iso_code_3'];
        $address_format = $country_query->row['address_format'];
      } else {
        $country = '';
        $iso_code_2 = '';
        $iso_code_3 = '';
        $address_format = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$result['zone_id'] . "'");

      if ($zone_query->num_rows) {
        $zone = $zone_query->row['name'];
        $zone_code = $zone_query->row['code'];
      } else {
        $zone = '';
        $zone_code = '';
      }

      $address_data[$result['address_id']] = array(
        'address_id' => $result['address_id'],
        'firstname' => $result['firstname'],
        'lastname' => $result['lastname'],
        'company' => $result['company'],
        'address_1' => $result['address_1'],
        'address_2' => $result['address_2'],
        'postcode' => $result['postcode'],
        'city' => $result['city'],
        'type' => $result['type'],
        'guid' => $result['guid'],
        'customer_cod_guid' => $result['customer_cod_guid'],
        'customer_type' => (int)$result['customer_type'],
        'np_city_id' => $result['np_city_id'],
        'np_city_name' => $result['np_city_name'],
        'np_street_id' => $result['np_street_id'],
        'np_street_name' => $result['np_street_name'],
        'np_post_id' => $result['np_post_id'],
        'np_post_name' => $result['np_post_name'],
        'np_house' => $result['np_house'],
        'np_level' => $result['np_level'],
        'np_apartment' => $result['np_apartment'],
        'np_customer_name' => $result['np_customer_name'],
        'np_customer_lastname' => $result['np_customer_lastname'],
        'np_customer_phone' => $result['np_customer_phone'],
        'comment' => $result['comment'],
        'zone_id' => $result['zone_id'],
        'zone' => $zone,
        'zone_code' => $zone_code,
        'country_id' => $result['country_id'],
        'country' => $country,
        'iso_code_2' => $iso_code_2,
        'iso_code_3' => $iso_code_3,
        'address_format' => $address_format,
        'custom_field' => json_decode($result['custom_field'], true)

      );
    }

    return $address_data;
  }

  public function getAddressCustomerByType($customer_cod_guid, $type){
    $address_data = array();

    $sql = "SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "' AND type = '" . $this->db->escape($type) . "'";

    if (!empty($customer_cod_guid)){
      $sql .= " AND customer_cod_guid = '" . $this->db->escape($customer_cod_guid) . "'";
    }

    $query = $this->db->query($sql);
    foreach ($query->rows as $result) {
      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");

      if ($country_query->num_rows) {
        $country = $country_query->row['name'];
        $iso_code_2 = $country_query->row['iso_code_2'];
        $iso_code_3 = $country_query->row['iso_code_3'];
        $address_format = $country_query->row['address_format'];
      } else {
        $country = '';
        $iso_code_2 = '';
        $iso_code_3 = '';
        $address_format = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$result['zone_id'] . "'");

      if ($zone_query->num_rows) {
        $zone = $zone_query->row['name'];
        $zone_code = $zone_query->row['code'];
      } else {
        $zone = '';
        $zone_code = '';
      }

      $address_data[$result['address_id']] = array(
        'address_id' => $result['address_id'],
        'firstname' => $result['firstname'],
        'lastname' => $result['lastname'],
        'company' => $result['company'],
        'address_1' => $result['address_1'],
        'address_2' => $result['address_2'],
        'postcode' => $result['postcode'],
        'city' => $result['city'],
        'type' => $result['type'],
        'guid' => $result['guid'],
        'customer_cod_guid' => $result['customer_cod_guid'],
        'np_city_id' => $result['np_city_id'],
        'np_city_name' => $result['np_city_name'],
        'np_street_id' => $result['np_street_id'],
        'np_street_name' => $result['np_street_name'],
        'np_post_id' => $result['np_post_id'],
        'np_post_name' => $result['np_post_name'],
        'np_house' => $result['np_house'],
        'np_level' => $result['np_level'],
        'np_apartment' => $result['np_apartment'],
        'np_customer_name' => $result['np_customer_name'],
        'np_customer_lastname' => $result['np_customer_lastname'],
        'np_customer_phone' => $result['np_customer_phone'],
        'comment' => $result['comment'],
        'zone_id' => $result['zone_id'],
        'zone' => $zone,
        'zone_code' => $zone_code,
        'country_id' => $result['country_id'],
        'country' => $country,
        'iso_code_2' => $iso_code_2,
        'iso_code_3' => $iso_code_3,
        'address_format' => $address_format,
        'custom_field' => json_decode($result['custom_field'], true)

      );
    }

    return $address_data;
  }

  public function getAddresses()
  {
    $address_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

    foreach ($query->rows as $result) {
      $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");

      if ($country_query->num_rows) {
        $country = $country_query->row['name'];
        $iso_code_2 = $country_query->row['iso_code_2'];
        $iso_code_3 = $country_query->row['iso_code_3'];
        $address_format = $country_query->row['address_format'];
      } else {
        $country = '';
        $iso_code_2 = '';
        $iso_code_3 = '';
        $address_format = '';
      }

      $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$result['zone_id'] . "'");

      if ($zone_query->num_rows) {
        $zone = $zone_query->row['name'];
        $zone_code = $zone_query->row['code'];
      } else {
        $zone = '';
        $zone_code = '';
      }

      $address_data[$result['address_id']] = array(
        'address_id' => $result['address_id'],
        'firstname' => $result['firstname'],
        'lastname' => $result['lastname'],
        'company' => $result['company'],
        'address_1' => $result['address_1'],
        'address_2' => $result['address_2'],
        'postcode' => $result['postcode'],
        'city' => $result['city'],
        'type' => $result['type'],
        'guid' => $result['guid'],
        'customer_cod_guid' => $result['customer_cod_guid'],
        'np_city_id' => $result['np_city_id'],
        'np_city_name' => $result['np_city_name'],
        'np_street_id' => $result['np_street_id'],
        'np_street_name' => $result['np_street_name'],
        'np_post_id' => $result['np_post_id'],
        'np_post_name' => $result['np_post_name'],
        'np_house' => $result['np_house'],
        'np_level' => $result['np_level'],
        'np_apartment' => $result['np_apartment'],
        'np_customer_name' => $result['np_customer_name'],
        'np_customer_lastname' => $result['np_customer_lastname'],
        'np_customer_phone' => $result['np_customer_phone'],
        'comment' => $result['comment'],
        'zone_id' => $result['zone_id'],
        'zone' => $zone,
        'zone_code' => $zone_code,
        'country_id' => $result['country_id'],
        'country' => $country,
        'iso_code_2' => $iso_code_2,
        'iso_code_3' => $iso_code_3,
        'address_format' => $address_format,
        'custom_field' => json_decode($result['custom_field'], true)

      );
    }

    return $address_data;
  }

  public function getTotalAddresses()
  {
    $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

    return $query->row['total'];
  }

  public function getAddressCustomerCodsGuid($customer_id) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "' AND customer_cod_guid != ''");
    return $query->rows;
  }

  public function getAddressClientByUid($uid, $customer_id, $customer_code_1c)
  {
    $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE guid = '" . $this->db->escape($uid) . "' AND customer_id = '" . (int)$customer_id . "' AND customer_cod_guid = '" . $this->db->escape($customer_code_1c) . "'");
    return $query->row;
  }

  public function updateCustomerAdressDefault($customer_id, $adress_id)
  {
    $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET address_id = '" . (int)$adress_id . "' WHERE `customer_id` = '" . (int)$customer_id . "'");
  }

  public function addAddressClient($customer_id, $customer_code_1c, $type, $name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $data = array())
  {

    switch ($type) {
      case "car":
        $this->db->query("INSERT INTO `" . DB_PREFIX . "address` SET 
        guid = '" . $this->db->escape($data['guid']) . "', 
        customer_id ='" . (int)$customer_id . "', 
        customer_cod_guid ='" . $customer_code_1c . "', 
        customer_price_id = '" . (int)$customer_price_id . "',
        customer_type ='" . (int)$customer_type . "', 
        firstname ='" . $this->db->escape($name_erp) . "',
        city ='" . $this->db->escape($data['city']) . "', 
        address_1 ='" . $this->db->escape($data['name']) . "', 
        np_house ='" . $this->db->escape($data['house']) . "', 
        np_apartment ='" . $this->db->escape($data['apartment']) . "', 
        comment ='" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        delay_pay = '" . (int)$delay_pay . "',
        delay_reserv = '" . (int)$delay_reserv . "',
        type='" . $this->db->escape($type) . "'");
        break;
      case "np_post":
      case "np_poshtomat":
        $this->db->query("INSERT INTO `" . DB_PREFIX . "address` SET 
        guid = '" . $this->db->escape($data['guid']) . "', 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_id ='" . (int)$customer_id . "', 
        customer_cod_guid ='" . $customer_code_1c . "', 
        customer_price_id = '" . (int)$customer_price_id . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['settlement_ref']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_post_id ='" . $this->db->escape($data['post_id']) . "',
        np_post_name ='" . $this->db->escape($data['post_name']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "', 
        comment ='" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "', 
        delay_pay = '" . (int)$delay_pay . "',
        delay_reserv = '" . (int)$delay_reserv . "',
        type='" . $this->db->escape($type) . "'");
        break;
      case "np_dveri":
        $this->db->query("INSERT INTO `" . DB_PREFIX . "address` SET 
        guid = '" . $this->db->escape($data['guid']) . "', 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_id ='" . (int)$customer_id . "', 
        customer_cod_guid ='" . $customer_code_1c . "', 
        customer_price_id = '" . (int)$customer_price_id . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['settlement_ref']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_street_id ='" . $this->db->escape($data['settlement_street_ref']) . "',
        np_street_name ='" . $this->db->escape($data['street_name']) . "',
        np_house ='" . $this->db->escape($data['house']) . "',
        np_level ='" . $this->db->escape($data['level']) . "',
        np_apartment ='" . $this->db->escape($data['apartment']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "', 
        comment ='" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "', 
        delay_pay = '" . (int)$delay_pay . "',
        delay_reserv = '" . (int)$delay_reserv . "',
        type='" . $this->db->escape($type) . "'");
        break;
    }
    $id = $this->db->getLastId();
    return $id;
  }

  public function updateAddressClient($address_db, $customer_id, $customer_code_1c, $type, $name_erp, $customer_type, $customer_ourdelivery, $customer_price_id, $delay_pay, $delay_reserv, $data = array())
  {
    switch ($type) {
      case "car":
        $this->db->query("UPDATE `" . DB_PREFIX . "address` SET 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_cod_guid ='" . $this->db->escape($customer_code_1c) . "', 
        customer_price_id = '" . (int)$customer_price_id . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        city = '" . $this->db->escape($data['city']) . "', 
        address_1 = '" . $this->db->escape($data['name']) . "',
        np_house = '" . $this->db->escape($data['house']) . "',
        np_apartment = '" . $this->db->escape($data['apartment']) . "',
        comment = '" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "',
        delay_pay = '" . (int)$delay_pay . "',
        delay_reserv = '" . (int)$delay_reserv . "'
        WHERE `address_id` = '" . (int)$address_db['address_id'] . "'");
        break;
      case "np_post":
      case "np_poshtomat":
        $this->db->query("UPDATE `" . DB_PREFIX . "address` SET 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_cod_guid ='" . $this->db->escape($customer_code_1c) . "', 
        customer_price_id = '" . (int)$customer_price_id . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['settlement_ref']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_post_id ='" . $this->db->escape($data['post_id']) . "',
        np_post_name ='" . $this->db->escape($data['post_name']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "', 
        comment ='" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "',
        delay_pay = '" . (int)$delay_pay . "',
        delay_reserv = '" . (int)$delay_reserv . "'
        WHERE `address_id` = '" . (int)$address_db['address_id'] . "'");
        break;
      case "np_dveri":
        $this->db->query("UPDATE `" . DB_PREFIX . "address` SET 
        firstname ='" . $this->db->escape($name_erp) . "',
        customer_cod_guid ='" . $this->db->escape($customer_code_1c) . "', 
        customer_price_id = '" . (int)$customer_price_id . "',
        customer_type ='" . (int)$customer_type . "', 
        ourdelivery = '" . (int)$customer_ourdelivery . "',
        np_city_id ='" . $this->db->escape($data['settlement_ref']) . "',
        np_city_name ='" . $this->db->escape($data['city_name']) . "',
        np_street_id ='" . $this->db->escape($data['settlement_street_ref']) . "',
        np_street_name ='" . $this->db->escape($data['street_name']) . "',
        np_house ='" . $this->db->escape($data['house']) . "',
        np_level ='" . $this->db->escape($data['level']) . "',
        np_apartment ='" . $this->db->escape($data['apartment']) . "',
        np_customer_name ='" . $this->db->escape($data['customer_name']) . "',
        np_customer_lastname ='" . $this->db->escape($data['customer_lastname']) . "', 
        np_customer_phone ='" . $this->db->escape($data['customer_phone']) . "', 
        comment ='" . $this->db->escape(isset($data['comment']) ? $data['comment'] : '') . "',
        delay_pay = '" . (int)$delay_pay . "',
        delay_reserv = '" . (int)$delay_reserv . "' 
        WHERE `address_id` = '" . (int)$address_db['address_id'] . "'");
        break;
    }
  }

}
