<?php
/*
*  location: admin/model
*/

class ModelExtensionDAjaxFilterAttribute extends Model {

    private $codename = 'd_ajax_filter';

    public function getAttributes($data = array()) {
        $sql = "SELECT *, (SELECT agd.name FROM " . DB_PREFIX . "attribute_group_description agd WHERE agd.attribute_group_id = a.attribute_group_id AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS attribute_group FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND LCASE(ad.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
        }

        if (!empty($data['filter_attribute_group_id'])) {
            $sql .= " AND a.attribute_group_id = '" . $this->db->escape($data['filter_attribute_group_id']) . "'";
        }

        $sort_data = array(
            'ad.name',
            'attribute_group',
            'a.sort_order'
            );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY attribute_group, ad.name";
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

    public function getAttributeGroups($language_id){

        $sql = "SELECT a.attribute_group_id, agd.name 
        FROM `".DB_PREFIX."af_attribute_values` av
        LEFT JOIN `".DB_PREFIX."attribute` a
        ON a.attribute_id = av.attribute_id
        LEFT JOIN `".DB_PREFIX."attribute_group_description` agd
        ON a.attribute_group_id = agd.attribute_group_id AND av.language_id = agd.language_id
        WHERE av.language_id='".(int)$language_id."'
        GROUP BY a.attribute_group_id";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getAttributeValues($attribute_id, $language_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "af_attribute_values`
            WHERE `attribute_id` = '" . $attribute_id . "' AND `language_id` = '".(int)$language_id."' ORDER BY `sort_order`");
        return $query->rows;
    }

    public function getAttributesByAttributeGroup($attribute_group_id, $language_id){

        $sql = "SELECT a.attribute_id, ad.name 
        FROM `".DB_PREFIX."af_attribute_values` av
        LEFT JOIN `".DB_PREFIX."attribute` a
        ON a.attribute_id = av.attribute_id
        LEFT JOIN `".DB_PREFIX."attribute_description` ad
        ON a.attribute_id = ad.attribute_id AND av.language_id = ad.language_id
        WHERE av.language_id='".(int)$language_id."' AND a.attribute_group_id = '".(int)$attribute_group_id."'
        GROUP BY a.attribute_id";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function editAttributeValues($attribute_values)
    {
        foreach ($attribute_values as $attribute_value_id => $attribute_value) {
            $this->db->query('UPDATE '.DB_PREFIX."af_attribute_values SET sort_order = '" . (int)$attribute_value['sort_order'] ."' WHERE  attribute_value_id='".(int)$attribute_value_id."'");
        }
    }

    public function editAttributeImages($attribute_values)
    {
        foreach ($attribute_values as $attribute_value_id => $attribute_value) {
            $this->db->query('UPDATE '.DB_PREFIX."af_attribute_values SET image = '" . $attribute_value['image'] ."' WHERE  attribute_value_id='".$attribute_value_id."'");
        }
    }
}