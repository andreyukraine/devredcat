<?php

class ModelExtensionModuleOcpopups extends Model {

	public function getActive() : array
	{
		$curr_date = date('Y-m-d');
		$q = "SELECT * FROM `" . DB_PREFIX . "popups` WHERE date_start <= '" . $curr_date ."' AND date_end >= '" . $curr_date ."' ORDER BY id DESC";
		$res = $this->db->query($q);
		return $res->row;
	}

}
