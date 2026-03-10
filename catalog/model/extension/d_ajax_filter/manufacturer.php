<?php

class ModelExtensionDAjaxFilterManufacturer extends Model {

    private $codename="d_ajax_filter";

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
    }

    public function getManufacturers($filter_data){

        $sql = "SELECT m.* FROM `".DB_PREFIX."af_temporary_filter` tp LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = tp.manufacturer_id WHERE tp.manufacturer_id != '0' GROUP BY tp.manufacturer_id ORDER BY m.sort_order ASC ";

//        $hash = md5(json_encode($filter_data));
//
//        $result = $this->cache->get('af-manufacturer.' . $hash);
//
//        if(!$result){
            $query = $this->db->query($sql);
            $result = $query->rows;
//            $this->cache->set('af-manufacturer.' .$hash , $result);
//        }

        $manufacturer_data = array();
        if(!empty($result)){
            foreach ($result as $row) {
                $manufacturer_data[$row['manufacturer_id']] = $row;
            }
        }


        return $manufacturer_data;
    }

    public function getManufacturer($manufacturer_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "manufacturer_to_store m2s ON (m.manufacturer_id = m2s.manufacturer_id) WHERE m.manufacturer_id = '" . (int)$manufacturer_id . "' AND m2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        return $query->row;
    }

//    public function getTotalManufacturer($data){
//
//        $data['params'] = $params = $this->{'model_extension_module_'.$this->codename}->getParamsToArray(true);
//
//        $total_query = $this->{'model_extension_module_'.$this->codename}->getProductsQuery();
//        $sql = "SELECT 'manufacturer' as type, 0 as id, m.manufacturer_id as val, COUNT(p.product_id) as c
//        FROM ".DB_PREFIX."manufacturer m
//        INNER JOIN (".$total_query.") p
//        ON m.manufacturer_id = p.manufacturer_id ";
//
//        if (!empty($params)) {
//            unset($params['manufacturer']);
//            $result = $this->{'model_extension_module_'.$this->codename}->getParamsQuery($params);
//            if(!empty($result)){
//                $sql.=" WHERE ".$result;
//            }
//        }
//
//        $sql .=" GROUP BY m.manufacturer_id";
//
//        $hash = md5(json_encode($data));
//
//        $result = $this->cache->get('af-total-manufacturer.' . $hash);
//
//        if(!$result){
//            $query = $this->db->query($sql);
//            $result = $query->rows;
//            $this->cache->set('af-total-manufacturer.' .$hash , $result);
//        }
//
//        return $this->{'model_extension_module_'.$this->codename}->convertResultTotal($result);
//    }


  public function getTotalManufacturer($data)
  {
    // Получаем параметры
    $data['params'] = $params = $this->{'model_extension_module_' . $this->codename}->getParamsToArray(true);

    // Запрос для получения количества производителей из временной таблицы
    $sql = "SELECT 'manufacturer' as type, 0 as id, p.manufacturer_id as val, COUNT(DISTINCT p.product_id) as c
            FROM " . DB_PREFIX . "af_temporary_filter p";

    // Фильтрация по производителю
    if (!empty($params)) {
      unset($params['manufacturer']);
      $result = $this->{'model_extension_module_' . $this->codename}->getParamsQuery($params);
      if (!empty($result)) {
        $sql .= " WHERE " . $result;
      }
    }

    // Группировка по производителю
    $sql .= " GROUP BY p.manufacturer_id";

    // Кэширование для ускорения
//    $hash = md5(json_encode($data));
//    $result = $this->cache->get('af-total-manufacturer.' . $hash);
//
//    if (!$result) {
      $query = $this->db->query($sql);
      $result = $query->rows;
//      $this->cache->set('af-total-manufacturer.' . $hash, $result);
//    }

    // Конвертация результата в нужный формат
    return $this->{'model_extension_module_' . $this->codename}->convertResultTotal($result);
  }


  public function getTotalQuery($manufacturers, $table_name = "p")
  {
    if (is_array($manufacturers)) {
      $manufacturers = implode(",", $manufacturers);
    }
    $ids = array_map('intval', explode(",", $manufacturers));
    $sql = $table_name . ".manufacturer_id IN (" . implode(',', $ids) . ")";
    return $sql;
  }
}
