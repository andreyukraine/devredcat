<?php
/*
*  location: admin/model
*/

class ModelExtensionDAjaxFilterModuleEan extends Model {

    public $codename = 'd_ajax_filter';

    public function updateProduct($product_id){
        $this->load->model('extension/'.$this->codename.'/cache');
        $eans = $this->db->query("SELECT *, REPLACE(REPLACE(TRIM(`ean`), '\r', ''), '\n', '') AS ean FROM `".DB_PREFIX."product` WHERE product_id = '".(int)$product_id."' AND ean != ''");
        $new_values = array();
        $product_eans = array();
        if($eans->num_rows){
            foreach ($eans->rows as $product) {
                $ean_id = $this->getEan($product['ean']);
                $product_eans[] = "('".(int)$product_id."', '".(int)$ean_id."')";
                $ean_info  = $this->db->query("SELECT * FROM `".DB_PREFIX."af_values` WHERE `type` = 'ean' AND `value` = '".(int)$ean_id."'");
                if($ean_info->num_rows > 0){
                    $new_values[] = $ean_info->row['af_value_id'];
                }
                else{
                    $new_values[] = $this->{'model_extension_'.$this->codename.'_cache'}->addValue('ean', 0, $ean_id);
                }
            }
        }

        if(count($product_eans) > 0){
            $this->db->query("DELETE FROM  `" . DB_PREFIX . "af_product_ean` WHERE `product_id`='".(int)$product_id."'");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "af_product_ean` (`product_id`, `ean_id`) VALUES ". implode( ',', array_unique( $product_eans)));
        }

        return $new_values;
    }

    public function getEan($text){
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."af_ean` WHERE `value` = '".$this->db->escape($text)."'");

        $ean_id = 0;

        if($query->num_rows > 0){
            $ean_id = $query->row['ean_id'];
        }
        else{
            $this->db->query(sprintf("INSERT INTO `".DB_PREFIX."af_ean` SET `value` = '%s'", $this->db->escape($text)));
            $ean_id = $this->db->getLastId();
        }

        return $ean_id;
    }

    public function step($data){
        $this->load->model('extension/'.$this->codename.'/cache');
        $query = $this->db->query("SELECT REPLACE(REPLACE(TRIM(`ean`), '\r', ''), '\n', '') AS ean FROM `" . DB_PREFIX . "product` WHERE ean != '' LIMIT ".($data['limit']*$data['last_step']).", ".$data['limit']);
        if($query->rows){
            foreach ($query->rows as $row) {
                $ean_id = $this->getEan($row['ean']);

                $this->{'model_extension_'.$this->codename.'_cache'}->addValue('ean', 0, $ean_id);
            }
        }

        $count = $this->db->query("SELECT COUNT(*) AS `c` FROM `" . DB_PREFIX . "product` WHERE ean != ''");
        return $count->row['c'];
    }

    public function prepare(){
        $this->db->query('TRUNCATE TABLE '.DB_PREFIX.'af_ean');
    }

    public function installModule(){
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_ean (
            `ean_id` int(11) NOT NULL AUTO_INCREMENT,
            `value` varchar(96) NOT NULL,
            PRIMARY KEY (`ean_id`),
            UNIQUE KEY (`value`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "af_product_ean` (
            `product_ean_id` INT(11) NOT NULL AUTO_INCREMENT,
            `product_id` INT(11) NULL DEFAULT NULL,
            `ean_id` INT(11) NULL DEFAULT NULL,
            PRIMARY KEY (`product_ean_id`),
            INDEX `product_id` (`product_id`),
            INDEX `ean_id` (`ean_id`)
            ) COLLATE='utf8_general_ci' ENGINE=InnoDB");
    }
    public function uninstallModule(){
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_product_ean");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_ean");
    }
}