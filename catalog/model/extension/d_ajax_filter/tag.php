<?php
class ModelExtensionDAjaxFilterTag extends Model {

    private $codename="d_ajax_filter";

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('extension/module/'.$this->codename);
    }

    public function getTags($filter_data){

        $sql = "SELECT t.tag_id, t.value as name FROM`" . DB_PREFIX . "af_product_tag` pt INNER JOIN `" . DB_PREFIX . "af_temporary` aft ON aft.product_id = pt.product_id INNER JOIN `" . DB_PREFIX . "af_tag` t ON t.tag_id = pt.tag_id WHERE t.language_id = '1' GROUP BY pt.tag_id ORDER BY LCASE(t.value) ASC";

//        $hash = md5(json_encode($filter_data));
//        $result = $this->cache->get('af-tag.' . $hash);
//
//        if(!$result){
            $query = $this->db->query($sql);
            $result = $query->rows;
//            $this->cache->set('af-tag.' .$hash , $result);
//        }

        $tag_data = array();
        if(!empty($result)){
            foreach ($result as $row) {
                $tag_data[$row['tag_id']] = $row;
            }
        }

        return $tag_data;
    }

    public function getTag($tag_id){
        $query = $this->db->query("SELECT `tag_id`, `value` FROM `".DB_PREFIX."af_tag` WHERE `tag_id` = '".(int)$tag_id."'");
        return $query->row;
    }

    public function getTotalTag($data){
        $data['params'] = $params = $this->{'model_extension_module_'.$this->codename}->getParamsToArray();

        $total_query = $this->{'model_extension_module_'.$this->codename}->getProductsQuery();
        $sql = "SELECT 'tag' as type, 0 as id, t.tag_id as val, COUNT(p.product_id) as c
        FROM ".DB_PREFIX."af_tag t
        INNER JOIN ".DB_PREFIX."af_product_tag pt
        ON t.tag_id = pt.tag_id
        INNER JOIN (".$total_query.") p
        ON pt.product_id = p.product_id ";
        if (!empty($params)) {
            unset($params['tag']);
            $result = $this->{'model_extension_module_'.$this->codename}->getParamsQuery($params);
            if(!empty($result)){
                $sql.=" WHERE ".$result;
            }
        }
        $sql .=" GROUP BY t.tag_id";

//        $hash = md5(json_encode($data));
//        $result = $this->cache->get('af-total-tag.' . $hash);
//        if(!$result){
            $query = $this->db->query($sql);
            $result = $query->rows;
//            $this->cache->set('af-total-tag.' .$hash , $result);
//        }
        return $this->{'model_extension_module_'.$this->codename}->convertResultTotal($result);
    }

    public function getTotalQuery($tags, $table_name){
        $implode = array();
        $value_array = array();
        foreach ($tags[0] as  $tag_id) {
            $implode[]="FIND_IN_SET(". $tag_id. ",".$table_name.".af_tags)";
        }
        $sql = "";
        if(!empty($implode)){
            $sql = "(".implode(' OR ',$implode).")";
        }
        return $sql;
    }
}
