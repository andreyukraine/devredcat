<?php
class ModelExtensionDAjaxFilterCategory extends Model {

    private $codename="d_ajax_filter";

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
    }

    public function getCategories($filter_data){

        $sql = "SELECT p2c.category_id, cd.name, c.image
        FROM `".DB_PREFIX."af_temporary` aft
        INNER JOIN `".DB_PREFIX."product_to_category` p2c
        ON aft.product_id = p2c.product_id
        INNER JOIN `".DB_PREFIX."category` c
        ON c.category_id = p2c.category_id
        INNER JOIN `".DB_PREFIX."category_description` cd
        ON cd.category_id = p2c.category_id
        WHERE cd.language_id = '".(int)$this->config->get('config_language_id')."'
        group by p2c.category_id
        ORDER BY c.sort_order ASC";
        
//        $hash = md5(json_encode(array($filter_data, (int)$this->config->get('config_language_id'))));
//        $result = $this->cache->get('af-category.' . $hash);
//
//        if(!$result){
            $query = $this->db->query($sql);
            $result = $query->rows;
//            $this->cache->set('af-category.' .$hash , $result);
//        }

        $category_data = array();
        if(!empty($result)){
            foreach ($result as $row) {
                $category_data[$row['category_id']] = $row;
            }
        }
        return $category_data;
    }

    public function getCategory($category_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");
        return $query->row;
    }

    public function getTotalCategory($data){
        $data['params'] = $params = $this->{'model_extension_module_'.$this->codename}->getParamsToArray();

        $total_query = $this->{'model_extension_module_'.$this->codename}->getProductsQuery();
        $sql = "SELECT 'category' as type, 0 as id, c.category_id as val, COUNT(p.product_id) as c
        FROM ".DB_PREFIX."category c
        INNER JOIN ".DB_PREFIX."product_to_category pc
        ON c.category_id = pc.category_id
        INNER JOIN (".$total_query.") p
        ON pc.product_id = p.product_id ";
        if (!empty($params)) {
            unset($params['category']);
            $result = $this->{'model_extension_module_'.$this->codename}->getParamsQuery($params);
            if(!empty($result)){
                $sql.=" WHERE ".$result;
            }
        }
        $sql .=" GROUP BY c.category_id";

//        $hash = md5(json_encode($data));
//        $result = $this->cache->get('af-total-category.' . $hash);
//        if(!$result){
            $query = $this->db->query($sql);
            $result = $query->rows;
//            $this->cache->set('af-total-category.' .$hash , $result);
//        }
        return $this->{'model_extension_module_'.$this->codename}->convertResultTotal($result);
    }

    public function getTotalQuery($categories, $table_name){
        $sql = $table_name.".product_id IN (SELECT product_id FROM ".DB_PREFIX."product_to_category WHERE category_id IN (".implode(',',$categories[0]).")) ";
        return $sql;
    }
}
