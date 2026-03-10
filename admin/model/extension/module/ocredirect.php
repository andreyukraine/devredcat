<?php
class ModelExtensionModuleOcredirect extends Model {

    public function clear() {
        $sql = "TRUNCATE `" . DB_PREFIX . "redirect`";
		$this->db->query($sql);
	}

    public function getTotalRules($filter_data = array()) {
        $sql = "SELECT COUNT(*) total FROM `" . DB_PREFIX . "redirect` p where 1 ";

        if (isset($filter_data['filter']['from_url']) && trim($filter_data['filter']['from_url']) != "")
            $sql .= " and from_url like '%" . $this->db->escape($filter_data['filter']['from_url']) . "%'";
		
        if (isset($filter_data['filter']['to_url']) && trim($filter_data['filter']['to_url']) != "")
            $sql .= " and to_url like '%" . $this->db->escape($filter_data['filter']['to_url']) . "%'";
		
        if (isset($filter_data['filter']['code']) && trim($filter_data['filter']['code']) != "")
            $sql .= " and code = " . (int)$filter_data['filter']['code'];
		
        if (isset($filter_data['filter']['status']) && trim($filter_data['filter']['status']) != -1)
            $sql .= " and status = " . (int)$filter_data['filter']['status'];
		$result = $this->db->query($sql);
		
        return $result->row['total'];
	}
	
	
    public function checkFromUrl($from_url) {
        $sql = "SELECT * from `" . DB_PREFIX . "redirect` p where from_url = '" . $this->db->escape($from_url) . "'";
		$result = $this->db->query($sql);
		return $result->row;
	}
	
    public function getRules($filter_data = array()) {
        $sql = "SHOW TABLES LIKE '" . DB_PREFIX . "redirect'";
		$result = $this->db->query($sql);
		if (!$result->num_rows) {
			$this->install();
		}

        $sql = "SELECT * from `" . DB_PREFIX . "redirect` p where 1 ";

        if (isset($filter_data['filter']['from_url']) && trim($filter_data['filter']['from_url']) != "")
            $sql .= " and from_url like '%" . $this->db->escape($filter_data['filter']['from_url']) . "%'";
		
        if (isset($filter_data['filter']['to_url']) && trim($filter_data['filter']['to_url']) != "")
            $sql .= " and to_url like '%" . $this->db->escape($filter_data['filter']['to_url']) . "%'";
		
        if (isset($filter_data['filter']['code']) && trim($filter_data['filter']['code']) != "")
            $sql .= " and code = " . (int)$filter_data['filter']['code'];
		
        if (isset($filter_data['filter']['status']) && trim($filter_data['filter']['status']) != -1)
            $sql .= " and status = " . (int)$filter_data['filter']['status'];
		
		$page = 1;
        if (isset($filter_data['page'])) {
			$page = $filter_data['page'];
		}
		$orders_by = array(
			'to_url',
			'from_url',
		);
		$order = 'to_url';
        if (isset($filter_data['order']) && in_array($filter_data['order'], $orders_by)) {
			$order = $filter_data['order'];
		}

		$sorts = array(
			'ASC',
			'DESC',
		);
		$sort = 'DESC';
        if (isset($filter_data['sort']) && in_array($filter_data['sort'], $sorts)) {
			$sort = $filter_data['sort'];
		}
		$sql .= " ORDER BY " . $order . " " . $sort; 
        if (!empty($filter_data['limit'])) {
			$limit = $filter_data['limit'];
		} else {
			$limit = $this->config->get('config_limit_admin');
		}
        $sql .= " LIMIT " . ((int)$limit*($page - 1)). ", " . (int)$limit;
        return $this->db->query($sql)->rows;
    }
	
    public function addRule($data) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "redirect` WHERE `from_url` = '" . ltrim($this->db->escape($data['from_url']),'/') . "'");
		$sql = "INSERT INTO `" . DB_PREFIX . "redirect` SET
					`from_url` = '" . ltrim($this->db->escape($data['from_url']),'/') . "',
					`to_url` = '" . $this->db->escape($data['to_url']) . "',
					`code` = " . (int)$data['code'] . ",
					`status` = " . (int)$data['status'] . ",
					`created_at` = 'NOW()'";
		$this->db->query($sql);
		$this->cache->delete('redirect');
    }
	
    public function editRule($redirect_id, $data) {
		$sql = "UPDATE `" . DB_PREFIX . "redirect` set
					`from_url` = '" . ltrim($this->db->escape($data['from_url']),'/') . "',
					`to_url` = '" . $this->db->escape($data['to_url']) . "',
					`code` = " . (int)$data['code'] . ",
					`status` = " . (int)$data['status'] . "
				WHERE `redirect_id` = " . (int)$redirect_id;
		$this->db->query($sql);
		$this->cache->delete('redirect');
    }

    public function getRule($redirect_id) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "redirect`
			WHERE `redirect_id` = " . (int)$redirect_id;
		$result = $this->db->query($sql);
		return $result->row;
    }

    public function deleteRule($redirect_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "redirect` where `redirect_id` = " . (int)$redirect_id);
		$this->cache->delete('redirect');
    }

  public function activeRule($redirect_id) {
    $sql = "UPDATE `" . DB_PREFIX . "redirect` set `status` = 1 WHERE `redirect_id` = " . (int)$redirect_id;
    $this->db->query($sql);
    $this->cache->delete('redirect');
  }

  public function deactiveRule($redirect_id) {
    $sql = "UPDATE `" . DB_PREFIX . "redirect` set `status` = 0 WHERE `redirect_id` = " . (int)$redirect_id;
    $this->db->query($sql);
    $this->cache->delete('redirect');
  }

    public function uninstall() {
		$sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect`";
		$this->db->query($sql);
		$this->cache->delete('redirect');
	}

    public function install() {
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect` (
					`redirect_id` INT(11) NOT NULL AUTO_INCREMENT,
					`from_url`    CHAR(255) DEFAULT NULL,
					`to_url`      CHAR(255) DEFAULT NULL,
					`code`        SMALLINT(3) DEFAULT NULL,
					`status`      TINYINT(1) DEFAULT '1',
					`cnt`         INT(11) NOT NULL DEFAULT 0,
					`last_date`   DATETIME,
					`created_at`  TIMESTAMP NULL DEFAULT NULL,
					PRIMARY KEY (`redirect_id`)
--					,UNIQUE KEY `from_url` (`from_url`)
				) ENGINE=myIsam DEFAULT CHARSET=utf8;";
		$this->db->query($sql);
		$this->cache->delete('redirect');
	}

}
