<?php
class ModelUserApi extends Model {
	public function getUserByCodeErp($code_erp) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user` WHERE `code_erp` = '" . $this->db->escape($code_erp) . "' AND `status` = '1'");
		return $query->row;
	}

	public function serUserFcmtoken($user_id, $fcm_token) {
    $this->db->query("UPDATE " . DB_PREFIX . "user SET fcm_token = '" . $this->db->escape($fcm_token) . "' WHERE user_id = '" . (int)$user_id . "'");

  }
}
