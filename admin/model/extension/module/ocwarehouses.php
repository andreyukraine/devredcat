<?php

class ModelExtensionModuleOcwarehouses extends Model
{

  public function getWarehouseList() {
    $query = $this->db->query("SELECT *, w.warehouse_id as warehouse_id FROM " . DB_PREFIX . "warehouse w LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) GROUP BY w.warehouse_id ORDER BY w.warehouse_id ASC");
    return $query->rows;
  }

  public function getWarehouseId($warehouse_id){
    if (!empty($warehouse_id)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "warehouse` w LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) WHERE w.warehouse_id = '" . $warehouse_id . "' ORDER BY w.warehouse_id ASC");
      return $query->row;
    }
  }

  public function deleteWarehouse($warehouse_id) {
    $this->db->query("DELETE FROM " . DB_PREFIX . "warehouse WHERE warehouse_id = '" . (int)$warehouse_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "warehouse_description WHERE warehouse_id = '" . (int)$warehouse_id . "'");
  }

  public function editWarehouseId($warehouse_id, $data){
    $this->db->query("UPDATE `" . DB_PREFIX . "warehouse` SET uid = '" . $data['uid'] . "', lat = '" . $data['lat'] . "', lon = '" . $data['lon'] . "', status = '" . $data['status'] . "', phone = '" . $data['phone'] . "', image = '" . $data['image'] . "' WHERE warehouse_id = '" . (int)$warehouse_id . "'");
    foreach ($data['lang'] as $language_id => $val) {
      $this->db->query("UPDATE " . DB_PREFIX . "warehouse_description SET working_hours = '" . $val['working_hours'] . "', name = '" . $val['name'] . "', address = '" . $val['address'] . "' WHERE warehouse_id = '" . $warehouse_id . "' AND language_id = '" . (int)$language_id . "'");
    }
  }

  public function addWarehouse($data = array()){
    $this->db->query("INSERT INTO " . DB_PREFIX . "warehouse SET `uid` = '" . $data['uid'] . "', phone = '" . $data['phone'] . "', image = '" . $data['image'] . "', status = '" . $data['status'] . "', lat = '" . $data['lat'] . "', lon = '" . $data['lon'] . "',  date_modified = NOW(), date_added = NOW()");
    $warehouse_id = $this->db->getLastId();
    foreach ($data['lang'] as $language_id => $val) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "warehouse_description SET warehouse_id = '" . $warehouse_id . "', language_id = '" . (int)$language_id . "', working_hours = '" . $val['working_hours'] . "', name = '" . $val['name'] . "', address = '" . $val['address'] . "'");
    }
  }

  public function install()
  {
    $sql = array();
    $sql[] = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "warehouse` (
			    `warehouse_id` INT(11) NOT NULL AUTO_INCREMENT,
			    `uid` VARCHAR(255),
			    `phone` VARCHAR(255),
			    `image` VARCHAR(255),
	        `sort_order` INT(11) NOT NULL DEFAULT '0',
	        `status` TINYINT(1) NOT NULL DEFAULT '0',
	        `date_added` DATETIME NOT NULL,
	        `date_modified` DATETIME NOT NULL,
	        PRIMARY KEY (`warehouse_id`)
		) DEFAULT COLLATE=utf8_general_ci;";
    $sql[] = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."warehouse_description` (
					  `warehouse_description_id` int(11) NOT NULL,
					  `language_id` int(11) NOT NULL,
					  `warehouse_id` int(11) NOT NULL,
					  `name` VARCHAR(255),
					  `address` text,
					  `working_hours` text,
					  PRIMARY KEY (`warehouse_id`,`language_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

    foreach( $sql as $q ){
      $this->db->query($q);
    }
  }


  public function uninstall()
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse`");
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse_description`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_ocwarehouses'");
  }
}
