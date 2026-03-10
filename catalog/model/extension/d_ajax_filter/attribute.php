<?php

class ModelExtensionDAjaxFilterAttribute extends Model
{

  private $codename = "d_ajax_filter";

  public function __construct($registry)
  {
    parent::__construct($registry);
    $this->load->model('extension/module/' . $this->codename);
  }

  public function getAttributes($filter_data)
  {

    $sql = "SELECT a.attribute_id, ad.name, ad.slug, pa.product_id 
    FROM `" . DB_PREFIX . "product_attribute` pa 
    INNER JOIN `" . DB_PREFIX . "af_temporary_filter` aft ON aft.product_id = pa.product_id 
    LEFT JOIN `" . DB_PREFIX . "attribute` a ON pa.attribute_id = a.attribute_id 
    LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON a.attribute_id = ad.attribute_id 
    WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "' ";

    if (!empty($filter_data)) {
      if (isset($filter_data['filter_category_id'])) {
        $category_ids = is_array($filter_data['filter_category_id']) ? $filter_data['filter_category_id'] : explode(',', $filter_data['filter_category_id']);
        $category_ids = array_map('intval', $category_ids);
        $category_ids = array_filter($category_ids);

        if (!empty($category_ids)) {
          $sql .= "AND pa.product_id IN (SELECT product_id
                  FROM `" . DB_PREFIX . "product_to_category`
                  WHERE category_id IN (" . implode(',', $category_ids) . ")) ";
        }
      }
    }
    $sql .= "GROUP BY pa.attribute_id ORDER BY a.sort_order ASC";

//        $hash = md5(json_encode(array($filter_data, (int)$this->config->get('config_language_id'))));
//
//        $result = $this->cache->get('af-attribute.' . $hash);
//
//        if(!$result){
    $query = $this->db->query($sql);
    $result = $query->rows;
//            $this->cache->set('af-attribute.' .$hash , $result);
//        }

    $attribute_data = array();
    if (!empty($result)) {
      foreach ($result as $row) {
        $attribute_data[$row['attribute_id']] = $row;
      }
    }

    return $attribute_data;
  }

  public function getAttributeValues($attribute_id, $filter_data = [])
  {
    $language_id = (int)$this->config->get('config_language_id');

    $sql = "SELECT 
          av.attribute_value_id,
          av.text,
          av.sort_order,
          COUNT(DISTINCT pa.product_id) AS total
        FROM `" . DB_PREFIX . "af_product_attribute` pa
        INNER JOIN `" . DB_PREFIX . "af_temporary_filter` aft
          ON aft.product_id = pa.product_id
        INNER JOIN `" . DB_PREFIX . "af_attribute_values` av
          ON av.attribute_value_id = pa.attribute_value_id
          AND av.attribute_id = pa.attribute_id
          AND av.language_id = '" . $language_id . "'
        WHERE pa.attribute_id = '" . (int)$attribute_id . "'
        GROUP BY av.attribute_value_id
        ORDER BY av.sort_order ASC";

    $query = $this->db->query($sql);

    $results = [];
    foreach ($query->rows as $row) {
      // Не додаємо значення з нульовим total (неактивні фільтри)
      if ((int)$row['total'] > 0) {
        $results[$row['attribute_value_id']] = [
          'attribute_value_id' => (int)$row['attribute_value_id'],
          'text' => mb_strtolower($row['text'], 'UTF-8'),
          'sort_order' => (int)$row['sort_order'],
          'total' => (int)$row['total'],
        ];
      }
    }

    return $results;
  }



  public function getAttributeValue($attribute_value_id)
  {
    $query = $this->db->query("SELECT af.attribute_value_id, af.text as name FROM `" . DB_PREFIX . "af_attribute_values` af WHERE af.language_id = '" . (int)$this->config->get('config_language_id') . "' AND af.attribute_value_id = '" . (int)$attribute_value_id . "'");
    return $query->row;
  }

  public function getAttribute($attribute_id)
  {
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "attribute` a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE a.attribute_id = '" . (int)$attribute_id . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");
    return $query->row;
  }

  public
  function getAttributeCount($data)
  {
    $params = $this->{'model_extension_module_' . $this->codename}->getParamsToArray(true);

    $in = $this->getTotalAttribute($data);

    if (!empty($params['attribute'])) {
      foreach ($params['attribute'] as $attribute_id => $attribute_values) {
        $group_count = $this->getTotalAttribute($data, $attribute_id);
        if (isset($group_count['attribute'][$attribute_id])) {
          $in = $this->{'model_extension_module_' . $this->codename}->mergeTotal($in, array('attribute' => array($attribute_id => $group_count['attribute'][$attribute_id])));
        }
      }
    }
    return $in;
  }

  public
  function getTotalAttribute($data, $group_id = null)
  {
    $params = $this->{'model_extension_module_' . $this->codename}->getParamsToArray();

    $sql = "SELECT 'attribute' as type, av.attribute_id as id, av.attribute_value_id as val, COUNT(pa.product_id) as c
        FROM " . DB_PREFIX . "af_attribute_values av 
        INNER JOIN " . DB_PREFIX . "af_product_attribute pa
        ON pa.attribute_value_id = av.attribute_value_id
        INNER JOIN " . DB_PREFIX . "af_temporary_filter p
        ON pa.product_id = p.product_id
        WHERE av.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    if (!empty($params)) {

      if (!is_null($group_id)) {
        unset($params['attribute'][$group_id]);
      }

      $data['params'] = $params;

      $result = $this->{'model_extension_module_' . $this->codename}->getParamsQuery($params);
      if (!empty($result)) {
        $sql .= " AND " . $result;
      }
    }

    $sql .= " GROUP BY av.attribute_value_id ORDER BY av.sort_order";

//        $hash = md5(json_encode(array($data, (int)$this->config->get('config_language_id'))));
//        $result = $this->cache->get('af-total-attribute.' . $hash);
//        if(!$result){
    $query = $this->db->query($sql);
    $result = $query->rows;
//            $this->cache->set('af-total-attribute.' .$hash , $result);
//        }
    return $this->{'model_extension_module_' . $this->codename}->convertResultTotal($result);
  }

  public
  function getSetting($attribute_id, $common_setting, $module_setting)
  {

    if (isset($module_setting['attributes'][$attribute_id]) && $module_setting['attributes'][$attribute_id]['status'] != 'default') {
      return $module_setting['attributes'][$attribute_id];
    }

    if ($module_setting['attribute_default']['status'] != 'default') {
      return $module_setting['attribute_default'];
    }
    if (isset($common_setting['attributes'][$attribute_id]) && $common_setting['attributes'][$attribute_id]['status'] != 'default') {
      return $common_setting['attributes'][$attribute_id];
    }
    return $common_setting['default'];
  }

//    public function getTotalQuery($attributes, $table_name){
//        $implode = array();
//        foreach ($attributes as  $attribute_id => $attribute_values) {
//            $value_array = array();
//            foreach ($attribute_values as $attribute_value_id) {
//                $value_array[]="FIND_IN_SET((".$this->{'model_extension_module_'.$this->codename}->getValue('attribute', $attribute_id, $attribute_value_id)."),".$table_name.".af_values)";
//            }
//            $implode[] = "(".implode(' OR ',$value_array).")";
//        }
//        $sql = "";
//        if(!empty($implode)){
//            $sql = implode(' AND ', $implode);
//        }
//        return $sql;
//    }

  public function getTotalQuery($attributes, $table_name)
  {
    $implode = array();
    foreach ($attributes as $attribute_id => $attribute_values) {
      $value_array = array();
      foreach ($attribute_values as $attribute_value_id) {
        if (is_numeric($attribute_value_id)) {
          $value_array[] = "FIND_IN_SET(" . (int)$attribute_value_id . ", " . $table_name . ".af_values)";
        }
      }
      if (!empty($value_array)) {
        $implode[] = "(" . implode(' OR ', $value_array) . ")";
      }
    }
    $sql = "";
    if (!empty($implode)) {
      $sql = implode(' AND ', $implode);
    }
    return $sql;
  }
}
