<?php

class ModelExtensionModuleOcakcii extends Model
{

  public function getAkciaList($start = 0, $limit = 10) {
    $sql = "SELECT * FROM " . DB_PREFIX . "akcii a LEFT JOIN `" . DB_PREFIX . "akcii_description` ad ON (a.akcia_id = ad.akcia_id) LIMIT " . (int)$start . "," . (int)$limit;
    $query = $this->db->query($sql);

    return $query->rows;
  }

  public function getTotalAkcii() {
    $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "akcii";
    $query = $this->db->query($sql);

    return $query->row['total'];
  }

  public function getAkciaSeoUrls($akcia_id) {
    $akcia_seo_url_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'akcia_id=" . (int)$akcia_id . "'");

    foreach ($query->rows as $result) {
      $akcia_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
    }

    return $akcia_seo_url_data;
  }

  public function deleteAkcia($akcia_id) {
    $this->db->query("DELETE FROM " . DB_PREFIX . "akcii WHERE akcia_id = '" . (int)$akcia_id . "'");
    $this->db->query("DELETE FROM " . DB_PREFIX . "akcii_description WHERE akcia_id = '" . (int)$akcia_id . "'");
  }

  public function getAkciaUid($guid){
    if (!empty($uid)) {
      $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "akcii` WHERE guid = '" . $this->db->escape($guid) . "' ORDER BY akcia_id ASC");
      return $query->row;
    }
  }

  public function getAkciaId($akcia_id){
    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "akcii` a LEFT JOIN `" . DB_PREFIX . "akcii_description` ad ON (a.akcia_id = ad.akcia_id) WHERE a.akcia_id = '" . (int)$akcia_id . "'");
    return $query->row;
  }

  public function editAkcia($akcia_id, $data){
    $this->db->query("UPDATE `" . DB_PREFIX . "akcii` SET guid = '" . $this->db->escape($data['guid']) . "', image = '" . $this->db->escape($data['image']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_start = '" . $data['date_start'] . "', date_end = '" . $data['date_end'] . "',  date_modified = NOW() WHERE akcia_id = '" . (int)$akcia_id . "'");
    foreach ($data['lang'] as $language_id => $val) {
      $this->db->query("UPDATE " . DB_PREFIX . "akcii_description SET `desc` = '" . $this->db->escape($val['desc']) . "', name = '" . $this->db->escape($val['name']) . "', url = '" . $this->db->escape($val['url']) . "', meta_title = '" . $this->db->escape($val['meta_title']) . "', meta_description = '" . $this->db->escape($val['meta_description']) . "', meta_keyword = '" . $this->db->escape($val['meta_keyword']) . "', meta_h1 = '" . $this->db->escape($val['meta_h1']) . "' WHERE akcia_id = '" . (int)$akcia_id . "' AND language_id = '" . (int)$language_id . "'");
    }

    // STORE
    $this->db->query("DELETE FROM " . DB_PREFIX . "akcii_to_store WHERE akcia_id = '" . (int)$akcia_id . "'");
    if (isset($data['akcia_store'])) {
      foreach ($data['akcia_store'] as $store_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "akcii_to_store SET akcia_id = '" . (int)$akcia_id . "', store_id = '" . (int)$store_id . "'");
      }
    }

    // SEO URL
    $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'akcia_id=" . (int)$akcia_id . "'");

    if (isset($data['akcii_seo_url'])) {
      foreach ($data['akcii_seo_url']as $store_id => $language) {
        foreach ($language as $language_id => $keyword) {
          if (empty($keyword)) {
            $query = "akcia_id=" . (int)$akcia_id;
            $this->load->model('design/seo_url');
            $translit_name = $this->model_design_seo_url->translit($data['lang'][$language_id]['desc']);
            $keyword = $this->model_design_seo_url->uniqueSlug($store_id,$language_id, $translit_name, $query);
          }
          if (!empty($keyword)) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'akcia_id=" . (int)$akcia_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
          }
        }
      }
    }
  }

  public function addAkcia($data = array()){
    $this->db->query("INSERT INTO " . DB_PREFIX . "akcii SET `guid` = '" . $this->db->escape($data['guid']) . "', image = '" . $this->db->escape($data['image']) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_start = '" . $this->db->escape($data['date_start']) . "', date_end = '" . $this->db->escape($data['date_end']) . "',  date_modified = NOW(), date_added = NOW()");
    $akcia_id = $this->db->getLastId();
    foreach ($data['lang'] as $language_id => $val) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "akcii_description SET akcia_id = '" . (int)$akcia_id . "', language_id = '" . (int)$language_id . "', `desc` = '" . $this->db->escape($val['desc']) . "', name = '" . $this->db->escape($val['name']) . "', url = '" . $this->db->escape($val['url']) . "', meta_title = '" . $this->db->escape($val['meta_title']) . "', meta_description = '" . $this->db->escape($val['meta_description']) . "', meta_keyword = '" . $this->db->escape($val['meta_keyword']) . "', meta_h1 = '" . $this->db->escape($val['meta_h1']) . "'");
    }

    // STORE
    if (isset($data['akcia_store'])) {
      foreach ($data['akcia_store'] as $store_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "akcii_to_store SET akcia_id = '" . (int)$akcia_id . "', store_id = '" . (int)$store_id . "'");
      }
    }

    // SEO URL
    if (isset($data['akcii_seo_url'])) {
      foreach ($data['akcii_seo_url'] as $store_id => $language) {
        foreach ($language as $language_id => $keyword) {

          if (empty($keyword)) {
            $query = "akcia_id=" . (int)$akcia_id;
            $this->load->model('design/seo_url');
            $translit_name = $this->model_design_seo_url->translit($data['lang'][$language_id]['desc']);
            $keyword = $this->model_design_seo_url->uniqueSlug($store_id,$language_id, $translit_name, $query);
          }

          if (!empty($keyword)) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'akcia_id=" . (int)$akcia_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
          }
        }
      }
    }
  }

  public function getAkciaDescriptions($akcia_id) {
    $article_description_data = array();

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "akcii_description WHERE akcia_id = '" . (int)$akcia_id . "'");

    foreach ($query->rows as $result) {
      $article_description_data[$result['language_id']] = array(
        'name'             => $result['name'],
        'url'              => $result['url'],
        'desc'             => $result['desc'],
        'meta_title'       => $result['meta_title'],
        'meta_h1'	         => $result['meta_h1'],
        'meta_description' => $result['meta_description'],
        'meta_keyword'     => $result['meta_keyword']
      );
    }

    return $article_description_data;
  }

  public function getAkciaStores($akcia_id) {
    $akcii_store_data = array();
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "akcii_to_store WHERE akcia_id = '" . (int)$akcia_id . "'");
    foreach ($query->rows as $result) {
      $akcii_store_data[] = $result['store_id'];
    }
    return $akcii_store_data;
  }

  public function install()
  {
    $sql = array();
    $sql[] = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "akcii` (
			    `akcia_id` INT(11) NOT NULL AUTO_INCREMENT,
			    `guid` VARCHAR(255),
			    `image` VARCHAR(255),
	        `sort_order` INT(11) NOT NULL DEFAULT '0',
	        `status` TINYINT(1) NOT NULL DEFAULT '0',
	        `date_start` DATETIME NOT NULL,
	        `date_end` DATETIME NOT NULL,
	        `date_added` DATETIME NOT NULL,
	        `date_modified` DATETIME NOT NULL,
	        PRIMARY KEY (`akcia_id`)
		) DEFAULT COLLATE=utf8_general_ci;";
    $sql[] = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."akcii_description` (
					  `akcia_description_id` int(11) NOT NULL,
					  `language_id` int(11) NOT NULL,
					  `akcia_id` int(11) NOT NULL,
					  `name` VARCHAR(255),
					  `desc` text,
					  `url` text,
					  `meta_title` VARCHAR(255),
					  `meta_description` VARCHAR(255),
					  `meta_keyword` VARCHAR(255),
					  `meta_h1` VARCHAR(255),
					  PRIMARY KEY (`akcia_id`,`language_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

    $sql[] = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "akcii_to_store` (
			    `akcia_id` INT(11) NOT NULL,
          `store_id` INT(11) NOT NULL) DEFAULT COLLATE=utf8_general_ci;";

    foreach($sql as $q){
      $this->db->query($q);
    }
  }


  public function uninstall()
  {
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "akcii`");
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "akcii_description`");
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "akcii_to_store`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'ocakcii'");
  }
}
