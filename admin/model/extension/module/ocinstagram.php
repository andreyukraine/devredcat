<?php

class ModelExtensionModuleOcinstagram extends Model
{

  public function getPostList($start = 0, $limit = 10) {
    $sql = "SELECT * FROM " . DB_PREFIX . "instagram LIMIT " . (int)$start . "," . (int)$limit;
    $query = $this->db->query($sql);
    return $query->rows;
  }

  public function editPost($post_id, $data){
    if (!empty($post_id)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "instagram` WHERE post_id = '" . $post_id . "' ORDER BY post_id ASC");
      if ($query->rows){
        $this->db->query("UPDATE " . DB_PREFIX . "instagram SET title = '" . $this->db->escape($data['title']) . "', caption = '" . $this->db->escape($data['caption']) . "', image = '" . $this->db->escape($data['image']) . "', link = '" . $this->db->escape($data['link']) . "', created_time = '" . $this->db->escape($data['created_time']) . "', date_modified = NOW()  WHERE post_id = '" . $post_id . "'");
      }
    }
  }

  public function addPost($data = array()){
    if (!empty($data) > 0){
      $this->db->query("INSERT INTO " . DB_PREFIX . "instagram SET title = '" . $this->db->escape($data['title']) . "', `caption` = '" . $this->db->escape($data['caption']) . "', image = '" . $this->db->escape($data['image']) . "', link = '" . $this->db->escape($data['link']) . "', created_time = '" . $this->db->escape($data['created_time']) . "',  date_modified = NOW(), date_added = NOW()");
    }
  }

  public function getTotalPosts() {
    $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "instagram";
    $query = $this->db->query($sql);
    return $query->row['total'];
  }

  public function clearPosts(){
    $this->db->query("DELETE FROM `" . DB_PREFIX . "instagram`");
  }

  public function install()
  {
    $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "instagram` (
			    `post_id` INT(11) NOT NULL AUTO_INCREMENT,
			    `id` INT(25),
			    `title` VARCHAR(255),
			    `caption` VARCHAR(255),
			    `image` LONGTEXT,
	        `link` VARCHAR(255),
	        `created_time`VARCHAR(255),
	        `date_added` DATETIME NOT NULL,
	        `date_modified` DATETIME NOT NULL,
	        PRIMARY KEY (`post_id`)
		) DEFAULT COLLATE=utf8_general_ci;";
    $query = $this->db->query($sql);
  }


  public function uninstall()
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "instagram`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_ocinstagram'");
  }
}
