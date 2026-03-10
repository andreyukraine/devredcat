<?php
class ModelExtensionModuleAdminRename extends Model {

	public function getSetting($code, $store_id = 0) {
	    $this->load->model('setting/setting');
		return $this->model_setting_setting->getSetting($code,$store_id);
	}

  	public function editSetting($code, $data, $store_id = 0) {
	    $this->load->model('setting/setting');
		$this->model_setting_setting->editSetting($code,$data,$store_id);
	}

  	public function install() {
		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET status=1 WHERE `name` LIKE'%admin_rename%'");
		$modifications = $this->load->controller('extension/modification/refresh');
  	}

  	public function uninstall() {
		$this->db->query("UPDATE `" . DB_PREFIX . "modification` SET status=0 WHERE `name` LIKE'%admin_rename%'");
		$modifications = $this->load->controller('extension/modification/refresh');
  	}

  }
?>
