<?php

class ModelExtensionModuleOccards extends Model
{

  public function getCardList($start = 0, $limit = 10) {
    $sql = "SELECT * FROM " . DB_PREFIX . "cards LIMIT " . (int)$start . "," . (int)$limit;
    $query = $this->db->query($sql);

    return $query->rows;
  }

  public function getTotalCards() {
    $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cards";
    $query = $this->db->query($sql);

    return $query->row['total'];
  }

  public function getCardUid($uid){
    if (!empty($uid)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cards` WHERE uid = '" . $uid . "' ORDER BY card_id ASC");
      return $query->row;
    }
  }

  public function editCardUid($uid, $data){
    if (!empty($uid)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cards` WHERE uid = '" . $uid . "' ORDER BY card_id ASC");
      if ($query->rows){
        $this->db->query("UPDATE " . DB_PREFIX . "cards SET card_number = '" . $data['card_number'] . "', card_code = '" . $data['card_code'] . "', phone = '" . $data['phone'] . "', sum = '" . $data['sum'] . "', status = '" . $data['status'] . "' WHERE uid = '" . $uid . "'");
      }
    }
  }

  public function addCard($data = array()){
    if (count($data) > 0){
      $this->db->query("INSERT INTO " . DB_PREFIX . "cards SET uid = '" . $data['uid'] . "', `card_number` = '" . $data['card_number'] . "', card_code = '" . $data['card_code'] . "', status = '" . (int)$data['status'] . "', phone = '" . $data['phone'] . "', sum = '" . $data['sum'] . "', date_modified = NOW(), date_added = NOW()");
    }
  }

  public function install()
  {
    $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "cards` (
			    `card_id` INT(11) NOT NULL AUTO_INCREMENT,
			    `uid` VARCHAR(255),
			    `card_number` VARCHAR(255),
			    `card_code` VARCHAR(255),
			    `phone` VARCHAR(255),
			    `customer_id` INT(11),
			    `percent` INT(11),
			    `sum` DECIMAL(12,2) NOT NULL,
	        `sort_order` INT(11) NOT NULL DEFAULT '0',
	        `status` TINYINT(1) NOT NULL DEFAULT '0',
	        `date_added` DATETIME NOT NULL,
	        `date_modified` DATETIME NOT NULL,
	        PRIMARY KEY (`card_id`)
		) DEFAULT COLLATE=utf8_general_ci;");

  }


  public function uninstall()
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "cards`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_occards'");
  }
}
