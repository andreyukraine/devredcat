<?php
class ModelDesignBanner extends Model {
	public function getBanner($banner_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner b LEFT JOIN " . DB_PREFIX . "banner_image bi ON (b.banner_id = bi.banner_id) WHERE b.banner_id = '" . (int)$banner_id . "' AND b.status = '1' AND bi.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bi.sort_order ASC");
		return $query->rows;
	}

  public function getSlideshowManufacture($manufacturer_id) {
    $language_id = (int)$this->config->get('config_language_id');
    $query = $this->db->query("
			SELECT
				si.title,
				si.link,
				si.image,
				si.image_mob,
				si.video,
				si.sort_order
			FROM `" . DB_PREFIX . "banner_manufacture` s
			LEFT JOIN `" . DB_PREFIX . "banner_image` si
				ON (si.banner_id = s.banner_id AND si.language_id = '" . $language_id . "')
			LEFT JOIN `" . DB_PREFIX . "banner` scid
				ON (scid.banner_id = s.banner_id)
			WHERE scid.status = '1'
				AND s.manufacturer_id = '" . (int)$manufacturer_id . "'
			ORDER BY
				si.sort_order ASC");
    return $query->rows;
  }

  public function getSlideshowCategory($category_id) {
    $language_id = (int)$this->config->get('config_language_id');
    $sql = "
			SELECT
				si.title,
				si.link,
				si.image,
				si.image_mob,
				si.video,
				si.sort_order
			FROM `" . DB_PREFIX . "banner_category` s
			LEFT JOIN `" . DB_PREFIX . "banner_image` si
				ON (si.banner_id = s.banner_id AND si.language_id = '" . $language_id . "')
			LEFT JOIN `" . DB_PREFIX . "banner` scid
				ON (scid.banner_id = s.banner_id)
			WHERE scid.status = '1'
				AND s.category_id = '" . (int)$category_id . "'
			ORDER BY
				si.sort_order ASC";
    $query = $this->db->query($sql);
    return $query->rows;
  }
}
