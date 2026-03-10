<?php

// catalog/model/extension/module/ocredirect.php
class ModelExtensionModuleOcredirect extends Model {
  public function getSettings() {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'ocredirect'");

    $settings = array();
    foreach ($query->rows as $result) {
      if (!$result['serialized']) {
        $settings[$result['key']] = $result['value'];
      } else {
        $settings[$result['key']] = json_decode($result['value'], true);
      }
    }

    return $settings;
  }
}
