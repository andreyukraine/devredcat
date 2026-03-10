<?php

class ModelExtensionModuleOcwarehouses extends Model
{

  public function getWarehouseList() {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "warehouse w LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) GROUP BY w.warehouse_id ORDER BY w.warehouse_id ASC");
    return $query->rows;
  }

  public function getWarehouse($warehouse_id){
    if (!empty($warehouse_id)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "warehouse` w LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) WHERE w.warehouse_id = '" . $warehouse_id . "' ORDER BY w.warehouse_id ASC");
      return $query->row;
    }
  }

  public function getWarehouseListInIds($keys) {
    // Преобразуем массив ключей в строку для SQL-запроса
    $ids = implode(',', array_map('intval', $keys));  // Преобразуем значения в целые числа для безопасности

    // Выполняем запрос с использованием оператора IN
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "warehouse w 
        LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) 
        WHERE w.warehouse_id IN ($ids) 
        GROUP BY w.warehouse_id 
        ORDER BY w.warehouse_id ASC");

    return $query->rows;
  }

  public function getWarehouseId($warehouse_id){
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "warehouse` w LEFT JOIN `" . DB_PREFIX . "warehouse_description` wd ON (w.warehouse_id = wd.warehouse_id) WHERE w.warehouse_id = '" . (int)$warehouse_id . "'");
      return $query->row;
  }
}
