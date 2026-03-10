<?php
/*
*  location: admin/model
*/

class ModelExtensionDAjaxFilterOption extends Model {

    private $codename = 'd_ajax_filter';
    public function getOptions($data = array()) {
        $sql = "SELECT * FROM `" . DB_PREFIX . "option` o LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND LCASE(od.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
        }

        $sort_data = array(
            'od.name',
            'o.type',
            'o.sort_order'
            );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY od.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getOptionValues($option_id) {
        $option_value_data = array();

        $option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "option_value ov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . (int)$option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order, ovd.name");

        foreach ($option_value_query->rows as $option_value) {
            $option_value_data[] = array(
                'option_value_id' => $option_value['option_value_id'],
                'name'            => $option_value['name'],
                'image'           => $option_value['image'],
                'sort_order'      => $option_value['sort_order']
                );
        }

        return $option_value_data;
    }
}