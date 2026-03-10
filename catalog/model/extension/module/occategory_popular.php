<?php

class ModelExtensionModuleOccategoryPopular extends Model
{

  public function getCalegoryList(){
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_popular` WHERE status = 1");
    return $query->rows;
  }

}
