<?php
class ModelDesignBanner extends Model {
	public function addBanner($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "banner SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "'");

		$banner_id = $this->db->getLastId();

		if (isset($data['banner_image'])) {
			foreach ($data['banner_image'] as $language_id => $value) {
				foreach ($value as $banner_image) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "banner_image SET banner_id = '" . (int)$banner_id . "', language_id = '" . (int)$language_id . "', title = '" .  $this->db->escape($banner_image['title']) . "', link = '" .  $this->db->escape($banner_image['link']) . "', video = '" .  $this->db->escape($banner_image['video']) . "', image = '" .  $this->db->escape($banner_image['image']) . "', image_mob = '" .  $this->db->escape($banner_image['image_mob']) . "', sort_order = '" .  (int)$banner_image['sort_order'] . "'");
				}
			}
		}

    //++Andrey
    $this->db->query("DELETE FROM " . DB_PREFIX . "banner_category WHERE banner_id = '" . (int)$banner_id . "'");

    if (isset($data['category'])) {
      foreach ($data['category'] as $category_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "banner_category SET banner_id = '" . (int)$banner_id . "', category_id = '" . (int)$category_id . "'");
      }
    }

    $this->db->query("DELETE FROM " . DB_PREFIX . "banner_manufacture WHERE banner_id = '" . (int)$banner_id . "'");

    if (isset($data['manufactures'])) {
      foreach ($data['manufactures'] as $manufacturer_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "banner_manufacture SET banner_id = '" . (int)$banner_id . "', manufacturer_id = '" . (int)$manufacturer_id . "'");
      }
    }

		return $banner_id;
	}

	public function editBanner($banner_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "banner SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "' WHERE banner_id = '" . (int)$banner_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "'");

		if (isset($data['banner_image'])) {
			foreach ($data['banner_image'] as $language_id => $value) {
				foreach ($value as $banner_image) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "banner_image SET banner_id = '" . (int)$banner_id . "', language_id = '" . (int)$language_id . "', title = '" .  $this->db->escape($banner_image['title']) . "', link = '" .  $this->db->escape($banner_image['link']) . "', video = '" .  $this->db->escape($banner_image['video']) . "', image = '" .  $this->db->escape($banner_image['image']) . "', image_mob = '" .  $this->db->escape($banner_image['image_mob']) . "', sort_order = '" . (int)$banner_image['sort_order'] . "'");
				}
			}
		}

    //++Andrey
    $this->db->query("DELETE FROM " . DB_PREFIX . "banner_category WHERE banner_id = '" . (int)$banner_id . "'");

    if (isset($data['category'])) {
      foreach ($data['category'] as $category_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "banner_category SET banner_id = '" . (int)$banner_id . "', category_id = '" . (int)$category_id . "'");
      }
    }

    $this->db->query("DELETE FROM " . DB_PREFIX . "banner_manufacture WHERE banner_id = '" . (int)$banner_id . "'");

    if (isset($data['manufactures'])) {
      foreach ($data['manufactures'] as $manufacturer_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "banner_manufacture SET banner_id = '" . (int)$banner_id . "', manufacturer_id = '" . (int)$manufacturer_id . "'");
      }
    }
	}

	public function deleteBanner($banner_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "'");
	}

	public function getBanner($banner_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "banner WHERE banner_id = '" . (int)$banner_id . "'");

		return $query->row;
	}

  public function getCategories($banner_id)
  {
    $categories_data = [];

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_category WHERE banner_id = '" . (int)$banner_id . "'");

    foreach ($query->rows as $result) {
      $categories_data[] = $result['category_id'];
    }

    return $categories_data;
  }

  public function getManufactures($banner_id)
  {
    $manufactures_data = [];

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_manufacture WHERE banner_id = '" . (int)$banner_id . "'");

    foreach ($query->rows as $result) {
      $manufactures_data[] = $result['manufacturer_id'];
    }

    return $manufactures_data;
  }

	public function getBanners($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "banner";

		$sort_data = array(
			'name',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getBannerImages($banner_id) {
		$banner_image_data = array();

		$banner_image_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner_image WHERE banner_id = '" . (int)$banner_id . "' ORDER BY sort_order ASC");

		foreach ($banner_image_query->rows as $banner_image) {
			$banner_image_data[$banner_image['language_id']][] = array(
				'title'      => $banner_image['title'],
				'link'       => $banner_image['link'],
				'image'      => $banner_image['image'],
        'image_mob'      => $banner_image['image_mob'],
				'sort_order' => $banner_image['sort_order'],
        'video'      => $banner_image['video']
			);
		}

		return $banner_image_data;
	}

	public function getTotalBanners() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "banner");

		return $query->row['total'];
	}
}
