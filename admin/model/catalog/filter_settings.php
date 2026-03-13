<?php
class ModelCatalogFilterSettings extends Model {
    public function getFilterGroups() {
        $filter_groups = array();

        // Get Attributes
        $query = $this->db->query("SELECT a.attribute_id, ad.name, agd.name as group_name 
            FROM " . DB_PREFIX . "attribute a 
            LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) 
            LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (a.attribute_group_id = agd.attribute_group_id) 
            WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            ORDER BY agd.name, ad.name");

        foreach ($query->rows as $result) {
            $filter_groups[] = array(
                'id'         => 'a' . $result['attribute_id'],
                'type'       => 'attribute',
                'name'       => $result['name'],
                'group'      => $result['group_name']
            );
        }

        // Get Options
        $query = $this->db->query("SELECT o.option_id, od.name 
            FROM `" . DB_PREFIX . "option` o 
            LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) 
            WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "' 
            ORDER BY od.name");

        foreach ($query->rows as $result) {
            $filter_groups[] = array(
                'id'         => 'o' . $result['option_id'],
                'type'       => 'option',
                'name'       => $result['name'],
                'group'      => 'Options'
            );
        }

        return $filter_groups;
    }

    public function saveSettings($data) {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('filter_settings', $data);
    }

    public function getSettings() {
        $this->load->model('setting/setting');
        return $this->model_setting_setting->getSetting('filter_settings');
    }
}
