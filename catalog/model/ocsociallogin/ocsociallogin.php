<?php

class Modelocsocialloginocsociallogin extends Model
{

    public function getIconLayout($id)
    {
        $query = $this->db->query("SELECT * FROM  `" . DB_PREFIX . "ocsociallogin_layout` where id ='" . $id . "'");
        foreach ($query->rows as $result) {
            foreach ($result as $key => $value) {
                if ($key == 'color' || $key == 'choose_login') {
                    $setting_data[$key] = json_decode($value, true);
                } else {
                    $setting_data[$key] = $value;
                }
            }
        }

        return $setting_data;
    }

    public function checkUser($id, $type)
    {
        $query = $this->db->query("SELECT * FROM  `" . DB_PREFIX . "ocsociallogin_users` where social_side_user_id = '" . $this->db->escape($id) . "' AND type = '" . $this->db->escape($type) . "'");
        return $query->row;
    }

    public function addLoginUser($customer_id, $id, $type)
    {
        $this->db->query("INSERT INTO  `" . DB_PREFIX . "ocsociallogin_users` SET oc_customer_id = '" . (int) $customer_id . "' , type = '" . $this->db->escape($type) . "' , social_side_user_id = '" . $this->db->escape($id) . "'");
    }

    public function addLoginHistory($user_id, $type, $name)
    {
        $this->db->query("INSERT INTO  `" . DB_PREFIX . "ocsociallogin_login_history` SET user_id = '" . (int) $this->db->escape($user_id) . "' , type = '" . $this->db->escape($type) . "' , user_name = '" . $this->db->escape($name) . "'");
    }

}
