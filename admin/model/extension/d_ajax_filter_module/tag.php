<?php
/*
*  location: admin/model
*/

class ModelExtensionDAjaxFilterModuleTag extends Model {

    public $codename = 'd_ajax_filter';

    public function updateProduct($product_id){
        $this->load->model('extension/'.$this->codename.'/cache');
        $tags = $this->db->query("SELECT product_id, language_id, tag FROM `".DB_PREFIX."product_description` WHERE product_id = '".(int)$product_id."' AND `tag` != ''");
        $new_tags = array();
        $product_tags = array();
        if($tags->num_rows){
            foreach ($tags->rows as $row) {
                $results = explode(',',$row['tag']);
                foreach ($results as $tag) {

                    $tag_id = $this->getTag($tag, $row['language_id']);

                    $new_tags[] = $tag_id;
                    $product_tags[] = "('".(int)$product_id."', '".(int)$tag_id."')";
                }
            }
        }
        
        if(count($product_tags) > 0){
            $this->db->query("DELETE FROM  `" . DB_PREFIX . "af_product_tag` WHERE `product_id`='".(int)$product_id."'");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "af_product_tag` (`product_id`, `tag_id`) VALUES ". implode( ',', array_unique( $product_tags )));
        }

        return $new_tags;
    }

    public function step($data){
        $this->load->model('extension/'.$this->codename.'/cache');
        $query = $this->db->query("SELECT tag, language_id FROM `" . DB_PREFIX . "product_description` WHERE `tag` != '' LIMIT ".($data['limit']*$data['last_step']).", ".$data['limit']);
        if($query->rows){
            foreach ($query->rows as $row) {
                $values = explode(',', $row['tag']);

                foreach ($values as $value) {
                    $this->getTag($value, $row['language_id']);
                }
            }
        }

        $count = $this->db->query("SELECT count(tag) as c FROM `" . DB_PREFIX . "product_description` WHERE `tag` != ''");
        return $count->row['c'];
    }

    public function getTag($text, $language_id){
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."af_tag` WHERE `value` = '".$this->db->escape($text)."' AND `language_id` = '".$language_id."'");

        $tag_id = 0;

        if($query->num_rows > 0){
            $tag_id = $query->row['tag_id'];
        }
        else{
            $this->db->query(sprintf("INSERT INTO `".DB_PREFIX."af_tag` SET `language_id` = '%s', `value` = '%s'", (int)$language_id, $this->db->escape($text)));
            $tag_id = $this->db->getLastId();
        }

        return $tag_id;
    }

    public function prepare(){
        $this->db->query('TRUNCATE TABLE '.DB_PREFIX.'af_product_tag');
    }

    public function installModule(){
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_tag (
            `tag_id` int(11) NOT NULL AUTO_INCREMENT,
            `language_id` int(11) NOT NULL,
            `value` varchar(96) NOT NULL,
            PRIMARY KEY (`tag_id`),
            UNIQUE KEY (`language_id`,`value`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "af_product_tag` (
            `product_tag_id` INT(11) NOT NULL AUTO_INCREMENT,
            `product_id` INT(11) NULL DEFAULT NULL,
            `tag_id` INT(11) NULL DEFAULT NULL,
            PRIMARY KEY (`product_tag_id`),
            INDEX `product_id` (`product_id`),
            INDEX `tag_id` (`tag_id`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB");
    }

    public function uninstallModule(){
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_product_tag");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_tag");
    }
}