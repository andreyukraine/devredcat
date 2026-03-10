<?php
/*
*  location: admin/model
*/

class ModelExtensionDAjaxFilterModuleAttribute extends Model {

    public $codename = 'd_ajax_filter';

    public $setting = array();

    private $separator = '';

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('setting/setting');
        $this->load->model('extension/'.$this->codename.'/cache');

        $setting = $this->model_setting_setting->getSetting($this->codename);

        if(!empty($setting[$this->codename.'_setting'])){
            $this->setting = $setting[$this->codename.'_setting'];
        }
        else{
            $this->config->load('d_ajax_filter');
            $setting = $this->config->get('d_ajax_filter_setting');

            $this->setting = $setting['general'];
        }

        if($this->setting['multiple_attributes_value'] && !empty($this->setting['separator'])){
            $this->separator = $this->setting['separator'];
        }
    }

    public function updateProduct($product_id){

        if(!empty($this->separator)){
            $attributes = $this->db->query("SELECT *, REPLACE(REPLACE(TRIM(`text`), '\r', ''), '\n', '') AS text FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '".(int)$product_id."'");
        }
        else{
            $attributes = $this->db->query("SELECT *, text AS text FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '".(int)$product_id."'");
        }
        $product_attributes = array();
        $new_values = array();
        if($attributes->num_rows){
            foreach ($attributes->rows as $row) {

                $attribute_values = array();

                if(!empty($this->separator)){
                    $attribute_values = explode($this->separator, $row['text']);
                }
                else{
                    $attribute_values[] = $row['text'];
                }

                foreach ($attribute_values as $text) {
                    if(!empty($text)){
                        $attribute_value_id = $this->getAttributeValue($text, $row['attribute_id'], $row['language_id']);

                        $product_attributes[] = "('".(int)$product_id."', '".(int)$row['attribute_id']."', '".(int)$attribute_value_id."')";

                        $attribute_info  = $this->db->query("SELECT * FROM `".DB_PREFIX."af_values` WHERE `type` = 'attribute' AND `group_id` = '".(int)$row['attribute_id']."' AND `value` = '".(int)$attribute_value_id."'");
                        if($attribute_info->num_rows > 0){
                            $new_values[] = $attribute_info->row['af_value_id'];
                        }
                        else{
                            $new_values[] = $this->{'model_extension_'.$this->codename.'_cache'}->addValue('attribute', $row['attribute_id'], $attribute_value_id);
                        }
                    }
                }
            }
        }

        if(count($product_attributes) > 0){
            $this->db->query("DELETE FROM  `" . DB_PREFIX . "af_product_attribute` WHERE `product_id`='".(int)$product_id."'");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "af_product_attribute` (`product_id`, `attribute_id`, `attribute_value_id`) VALUES ". implode( ',', array_unique( $product_attributes)));
        }
        
        return $new_values;
    }

    public function getAttributeValue($text, $attribute_id, $language_id){

        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."af_attribute_values` WHERE `text` = '".$this->db->escape($text)."' AND `attribute_id` = '".(int)$attribute_id."' AND `language_id` = '".$language_id."'");

        $attribute_value_info = false;
        if($query->num_rows > 0){
            $attribute_value_id = $query->row['attribute_value_id'];
        }
        else{
            $this->db->query(sprintf("INSERT INTO `".DB_PREFIX."af_attribute_values` SET `attribute_id` = '%s', `language_id` = '%s', `text` = '%s'", (int)$attribute_id, (int)$language_id, $this->db->escape($text)));
            $attribute_value_id = $this->db->getLastId();
        }

        return $attribute_value_id;
    }

    public function step($data){

        if(!empty($this->separator)){
            $sql = "SELECT `attribute_id`, `language_id`, REPLACE(REPLACE(TRIM(`text`), '\r', ''), '\n', '') AS text FROM `" . DB_PREFIX . "product_attribute` GROUP BY text, attribute_id, language_id";
        }
        else{
            $sql = "SELECT * FROM `" . DB_PREFIX . "product_attribute` GROUP BY text, attribute_id, language_id";
        }
        $sql .= " LIMIT ".($data['limit']*$data['last_step']).", ".$data['limit'];

        $query = $this->db->query($sql);

        if($query->rows){
            foreach ($query->rows as $row) {
                $attribute_values = array();

                if(!empty($this->separator)) {
                    $values = explode($this->separator, $row['text']);
                }
                else {
                    $values = array($row['text']);
                }

                foreach ($values as $attribute_text) {
                    if(!empty($attribute_text)){
                        $attribute_value_id = $this->getAttributeValue($attribute_text, $row['attribute_id'], $row['language_id']);

                        $results  = $this->db->query("SELECT * FROM `".DB_PREFIX."af_values` WHERE `type` = 'attribute' AND `group_id` = '".(int)$row['attribute_id']."' AND `value` = '".(int)$attribute_value_id."'");
                        if($results->num_rows == 0){
                            $this->{'model_extension_'.$this->codename.'_cache'}->addValue('attribute', $row['attribute_id'], $attribute_value_id);
                        }
                    }
                    
                }
                
            }
        }

        $count = $this->db->query("SELECT COUNT(*) AS `c` FROM(SELECT * FROM `" . DB_PREFIX . "product_attribute` GROUP BY text) AS t");;
        return $count->row['c'];
    }

    public function save($data){
        $query = $this->db->query("SELECT `attribute_id`, `language_id`, `sort_order`, 'image', 'text' 
            FROM `".DB_PREFIX."af_attribute_values` LIMIT ".($data['limit']*$data['last_step']).", ".$data['limit']);
        if($query->num_rows){
            foreach ($query->rows as $row) {
                $this->db->query("INSERT INTO `".DB_PREFIX."af_attribute_values_backup` SET 
                    `attribute_id` = '".(int)$row['attribute_id']."', 
                    `language_id` = '".(int)$row['language_id']."',
                    `sort_order` = '".(int)$row['sort_order']."',
                    `image` = '".$row['image']."',
                    `text` = '".$row['text']."'");
            }
        }
        $count = $this->db->query("SELECT COUNT(*) as c FROM `".DB_PREFIX."af_attribute_values`");;
        return $count->row['c'];
    }

    public function restore($data){
        $query = $this->db->query("SELECT `attribute_id`, `language_id`, `sort_order`, 'image', 'text' FROM `".DB_PREFIX."af_filter_backup` WHERE LIMIT ".($data['limit']*$data['last_step']).", ".$data['limit']);
        if($query->num_rows){
            foreach ($query->rows as $row) {
                    $this->db->query("UPDATE `".DB_PREFIX."af_filter` SET
                    `sort_order` = '".(int)$row['sort_order']."',
                    `image` = '".$row['image']."'
                    WHERE `attribute_id` = '".(int)$row['attribute_id']."' AND `language_id` = '".(int)$language_id."' AND  `text` = '".$text."'");
            }
        }
        $count = $this->db->query("SELECT COUNT(*) as c FROM `".DB_PREFIX."af_filter_backup`");;
        return $count->row['c'];
    }

    public function cleaning_before(){
        $this->db->query('TRUNCATE TABLE '.DB_PREFIX.'af_attribute_values');
    }

    public function cleaning(){
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_attribute_values_backup");
    }

    public function prepare(){
        $this->db->query('TRUNCATE TABLE '.DB_PREFIX.'af_product_attribute');
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_attribute_values_backup");
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_attribute_values_backup (
            `attribute_value_id` int(11) NOT NULL AUTO_INCREMENT,
            `attribute_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL,
            `sort_order` int(11) NOT NULL,
            `image` varchar(255) NOT NULL,
            `text` TEXT NOT NULL,
            PRIMARY KEY (`attribute_value_id`),
            UNIQUE KEY (`attribute_id`,`language_id`,`text`(200))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function installModule(){
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_product_attribute (
            `product_attribute_id` INT(11) NOT NULL AUTO_INCREMENT,
            `product_id` INT(11) NULL DEFAULT NULL,
            `attribute_id` INT(11) NULL DEFAULT NULL,
            `attribute_value_id` INT(11) NULL DEFAULT NULL,
            PRIMARY KEY (`product_attribute_id`),
            INDEX `product_id` (`product_id`),
            INDEX `attribute_id` (`attribute_id`),
            INDEX `attribute_value_id` (`attribute_value_id`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB");
        
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_attribute_values (
            `attribute_value_id` int(11) NOT NULL AUTO_INCREMENT,
            `attribute_id` int(11) NOT NULL,
            `language_id` int(11) NOT NULL,
            `sort_order` int(11) NOT NULL,
            `image` varchar(255) NOT NULL,
            `text` TEXT NOT NULL,
            PRIMARY KEY (`attribute_value_id`),
            UNIQUE KEY (`attribute_id`,`language_id`,`text`(200))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function uninstallModule(){
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_product_attribute");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_attribute_values");
    }

}