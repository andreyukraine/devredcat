<?php

class ModelExtensionModulePopups extends Model {


	public function install()
	{
		$query = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "popups` (
		  `id` INT(11) NOT NULL AUTO_INCREMENT,
		  `title` varchar(255) DEFAULT NULL,
		  `text` text DEFAULT NULL,
		  `date_start` date DEFAULT NULL,
		  `date_end` date DEFAULT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"; 

		$this->db->query($query);

	}

	public function getAll() : array
	{
		$query = "SELECT p.* FROM `" . DB_PREFIX . "popups` p ORDER BY p.id DESC";
		$r = $this->db->query($query);
		return $r->rows;
	}

	public function getOne(int $id = 0) : array
	{
		$query = "SELECT * FROM `" . DB_PREFIX . "popups` WHERE id = ".$id;
		$r = $this->db->query($query);
		return $r->row;
	}

	public function add()
	{
		$result = 0;
		$title = $this->db->escape(trim($this->request->post['title']));
		$text = $this->db->escape(trim($this->request->post['text']));
		$date_start = $this->db->escape(trim($this->request->post['date_start']));
		$date_end = $this->db->escape(trim($this->request->post['date_end']));
		$q = "INSERT INTO `" . DB_PREFIX . "popups` SET `title` = \"$title\", `text` = \"$text\", `date_start` = \"$date_start\", `date_end` = \"$date_end\"";
		$this->db->query($q);
	}
	public function save() : int
	{
		$result = 0;
		$id = $this->db->escape((int)trim($this->request->post['id']));
		$title = $this->db->escape(trim($this->request->post['title']));
		$text = $this->db->escape(trim($this->request->post['text']));
		$date_start = $this->db->escape(trim($this->request->post['date_start']));
		$date_end = $this->db->escape(trim($this->request->post['date_end']));
		$q = "UPDATE `" . DB_PREFIX . "popups` SET `title` = \"$title\", `text` = \"$text\", `date_start` = \"$date_start\", `date_end` = \"$date_end\" WHERE `id` = $id";
        if($this->db->query($q))
        {
        	$result = 1;
        }
        return $result;
	}

	public function delete(int $id)
	{
		$q = "DELETE FROM `" . DB_PREFIX . "popups` WHERE id = $id";
		$this->db->query($q);
	}
}
