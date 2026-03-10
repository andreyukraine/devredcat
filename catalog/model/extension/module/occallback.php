<?php

class ModelExtensionModuleOccallback extends Model {
	public function addRequest($data) {
		$this->db->query("INSERT INTO `". DB_PREFIX ."occallback` SET info = '". $this->db->escape($data['info']) ."', date_added = NOW()");
	}
}
