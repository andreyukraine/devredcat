<?php

class ModelSettingSetting extends Model
{
  public function getSetting($code, $store_id = 0)
  {
    $setting_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

    foreach ($query->rows as $result) {
      if (!$result['serialized']) {
        $setting_data[$result['key']] = $result['value'];
      } else {
        $setting_data[$result['key']] = json_decode($result['value'], true);
      }
    }
    return $setting_data;
  }

  public function editSetting($code, $data, $store_id = 0)
  {
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

    //++Andrey
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting_descriptions WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

    foreach ($data as $key => $value) {
      if (substr($key, 0, strlen($code)) == $code) {
        if (!is_array($value)) {
          $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
        } else {
          switch ($key) {
            case "config_gen_seo_brand_gpt":
            case "config_gen_seo_brand":
            case "config_gen_seo_prod_gpt":
            case "config_gen_seo_prod":
            case "config_gen_seo_cat_gpt":
            case "config_gen_seo_cat":
              foreach ($value as $language_id => $item) {
                foreach ($item as $keyV => $val) {
                  $updQ = "INSERT INTO " . DB_PREFIX . "setting_descriptions SET store_id = '" . (int)$store_id . "', `language_id` = '" . (int)$language_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $key . "_" . $keyV . "', `value` = '" . $this->db->escape($val) . "'";
                  $this->db->query($updQ);
                }
              }
              break;

            case "config_discont_meta_description":
            case "config_discont_meta_title":
            case "config_discont_h1_title":
            case "config_contact_meta_description":
            case "config_contact_meta_title":
            case "config_contact_h1_title":
            case "config_h1_title":
            case "configblog_html_h1":
            case "configblog_meta_title":
            case "configblog_meta_description":
            case "configblog_meta_keyword":
            case "config_home_description":
            case "config_meta_keyword":
            case "config_meta_description":
            case "config_meta_title":
              foreach ($value as $language_id => $item) {
                if ($language_id == 3) {
                  $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $key . "', `value` = '" . $this->db->escape($item['value']) . "'");
                }
                $this->db->query("INSERT INTO " . DB_PREFIX . "setting_descriptions SET `store_id` = '" . (int)$store_id . "', `language_id` = '" . (int)$language_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $key . "', `value` = '" . $this->db->escape($item['value']) . "'");
              }
              break;

            default:
              $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");

              break;
          }

          //--Andrey
        }
      } else {
        $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
      }
    }
    //--Andrey
  }

  public function deleteSetting($code, $store_id = 0)
  {
    $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");
  }

  public function getSettingValue($key, $store_id = 0)
  {
    $query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

    if ($query->num_rows) {
      return $query->row['value'];
    } else {
      return null;
    }
  }

  public function editSettingValue($code = '', $key = '', $value = '', $store_id = 0)
  {
    if (!is_array($value)) {
      $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($value) . "', serialized = '0'  WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
    } else {
      $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1' WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
    }
  }

  public function getValuesLangByKey($store_id, $key, $code = "config")
  {
    $mass_lal = array();
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting_descriptions WHERE `code`='" . $code ."' AND `key`='" . $key . "' AND `store_id` = '" . (int)$store_id . "'");
    foreach ($query->rows as $result) {
      $mass_lal[$result['language_id']] = array(
        'value' => $result['value'],
      );
    }
    return $mass_lal;
  }

}
