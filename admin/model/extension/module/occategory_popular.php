<?php

class ModelExtensionModuleOccategoryPopular extends Model
{

  public function getCategoryList($start = 0, $limit = 10)
  {
    $sql = "SELECT * FROM " . DB_PREFIX . "category_popular LIMIT " . (int)$start . "," . (int)$limit;
    $query = $this->db->query($sql);
    return $query->rows;
  }

  public function getTotalCategory()
  {
    $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category_popular";
    $query = $this->db->query($sql);

    return $query->row['total'];
  }

  public function deleteCategory($category_popular_id) {
    $this->db->query("DELETE FROM " . DB_PREFIX . "category_popular WHERE category_popular_id = '" . (int)$category_popular_id . "'");
  }

  public function getCategory($category_popular_id)
  {
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_popular` WHERE category_popular_id = '" . (int)$category_popular_id . "'");
    return $query->row;
  }

  public function editCategory($category_popular_id, $data)
  {
    $this->db->query("UPDATE " . DB_PREFIX . "category_popular SET category_id = '" . (int)$data['category_id'] . "', status = '" . (int)$data['status'] . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE category_popular_id = '" . (int)$category_popular_id . "'");
  }

  public function addCategory($data = array())
  {
    if (count($data) > 0) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "category_popular SET category_id = '" . (int)$data['category_id'] . "', status = '" . (int)$data['status'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW(), date_added = NOW()");
    }
  }

  public function install()
  {
    $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "category_popular` (
			    `category_popular_id` INT(11) NOT NULL AUTO_INCREMENT,
			    `category_id` VARCHAR(255),
	        `sort_order` INT(11) NOT NULL DEFAULT '0',
	        `status` TINYINT(1) NOT NULL DEFAULT '0',
	        `date_added` DATETIME NOT NULL,
	        `date_modified` DATETIME NOT NULL,
	        PRIMARY KEY (`category_popular_id`)
		) DEFAULT COLLATE=utf8_general_ci;");

  }


  public function uninstall()
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_popular`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_occategory_popular'");
  }
}
