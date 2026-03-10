<?php
/*
*  location: admin/model
*/

class ModelExtensionDAjaxFilterModuleOption extends Model {

    public $codename = 'd_ajax_filter';

    public function updateProduct($product_id){
        $this->load->model('extension/'.$this->codename.'/cache');
        $options = $this->db->query("SELECT * FROM `".DB_PREFIX."product_option_value` WHERE product_id = '".(int)$product_id."'");
        $new_values = array();
        if($options->num_rows){
            foreach ($options->rows as $option) {
                $option_info  = $this->db->query("SELECT * FROM `".DB_PREFIX."af_values` WHERE `type` = 'option' AND `group_id` = '".(int)$option['option_id']."' AND `value` = '".(int)$option['option_value_id']."'");
                if($option_info->num_rows > 0){
                    $new_values[] = $option_info->row['af_value_id'];
                }
                else{
                    $new_values[] = $this->{'model_extension_'.$this->codename.'_cache'}->addValue('option', $option['option_id'], $option['option_value_id']);
                }
            }
        }
        return $new_values;
    }

    public function step($data){
        $this->load->model('extension/'.$this->codename.'/cache');
        $query = $this->db->query("SELECT ov.option_id, ov.option_value_id FROM ".DB_PREFIX."option_value ov LIMIT ".($data['limit']*$data['last_step']).", ".$data['limit']);
        if($query->rows){
            foreach ($query->rows as $row) {
                $option_info  = $this->db->query("SELECT * FROM `".DB_PREFIX."af_values` WHERE `type` = 'option' AND `group_id` = '".(int)$row['option_id']."' AND `value` = '".(int)$row['option_value_id']."'");
                if($option_info->num_rows == 0){
                    $this->{'model_extension_'.$this->codename.'_cache'}->addValue('option', $row['option_id'], $row['option_value_id']);
                }
            }
        }

        $count = $this->db->query("SELECT COUNT(*) as c FROM `".DB_PREFIX."option_value`");
        return $count->row['c'];
    }
}