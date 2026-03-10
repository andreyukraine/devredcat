<?php

class ModelExtensionModuleOcorganization extends Model
{
  public function getOrganizationId($organization_id){
    if (!empty($organization_id)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "organization` w LEFT JOIN `" . DB_PREFIX . "organization_description` wd ON (w.organization_id = wd.organization_id) WHERE w.organization_id = '" . $organization_id . "' ORDER BY w.organization_id ASC");
      return $query->row;
    }
  }
}
