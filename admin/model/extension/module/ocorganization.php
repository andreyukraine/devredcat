<?php

class ModelExtensionModuleOcorganization extends Model
{

  public function getOrganizationList() {
    $query = $this->db->query("SELECT *, w.organization_id as organization_id FROM " . DB_PREFIX . "organization w LEFT JOIN `" . DB_PREFIX . "organization_description` wd ON (w.organization_id = wd.organization_id) GROUP BY w.organization_id ORDER BY w.organization_id ASC");
    return $query->rows;
  }

  public function getOrganizationId($organization_id){
    if (!empty($organization_id)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "organization` w LEFT JOIN `" . DB_PREFIX . "organization_description` wd ON (w.organization_id = wd.organization_id) WHERE w.organization_id = '" . $organization_id . "' ORDER BY w.organization_id ASC");
      return $query->row;
    }
  }

  public function deleteOrganization($organization_id) {
    $this->db->query("DELETE FROM " . DB_PREFIX . "organization WHERE organization_id = '" . (int)$organization_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "organization_description WHERE organization_id = '" . (int)$organization_id . "'");
  }

  public function editOrganizationId($organization_id, $data){
    $this->db->query("UPDATE `" . DB_PREFIX . "organization` SET uid = '" . $data['uid'] . "', lat = '" . $data['lat'] . "', lon = '" . $data['lon'] . "', status = '" . $data['status'] . "', phone = '" . $data['phone'] . "', image = '" . $data['image'] . "' WHERE organization_id = '" . (int)$organization_id . "'");
    foreach ($data['lang'] as $language_id => $val) {
      $this->db->query("UPDATE " . DB_PREFIX . "organization_description SET working_hours = '" . $val['working_hours'] . "', name = '" . $val['name'] . "', address = '" . $val['address'] . "' WHERE organization_id = '" . $organization_id . "' AND language_id = '" . (int)$language_id . "'");
    }
  }

  public function addOrganization($data = array()){
    $this->db->query("INSERT INTO " . DB_PREFIX . "organization SET `uid` = '" . $data['uid'] . "', phone = '" . $data['phone'] . "', image = '" . $data['image'] . "', status = '" . $data['status'] . "', lat = '" . $data['lat'] . "', lon = '" . $data['lon'] . "',  date_modified = NOW(), date_added = NOW()");
    $organization_id = $this->db->getLastId();
    foreach ($data['lang'] as $language_id => $val) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "organization_description SET organization_id = '" . $organization_id . "', language_id = '" . (int)$language_id . "', working_hours = '" . $val['working_hours'] . "', name = '" . $val['name'] . "', address = '" . $val['address'] . "'");
    }
  }
}
