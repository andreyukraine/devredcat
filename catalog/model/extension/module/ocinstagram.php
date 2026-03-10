<?php

class ModelExtensionModuleOcinstagram extends Model
{

  public function getPostList($start = 0, $limit = 10) {
    $sql = "SELECT * FROM " . DB_PREFIX . "instagram LIMIT " . (int)$start . "," . (int)$limit;
    $query = $this->db->query($sql);
    return $query->rows;
  }

  public function getTotalPosts() {
    $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "instagram";
    $query = $this->db->query($sql);
    return $query->row['total'];
  }
}
