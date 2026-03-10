<?php
/*
 *	location: admin/model
 */

class ModelExtensionModuleDAjaxFilterSeo extends Model
{
    private $codename = 'd_ajax_filter';

    public function CreateDatabase()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_query (
            `query_id` INT(11) NOT NULL AUTO_INCREMENT,
            `path` VARCHAR(256) NOT NULL,
            `params` VARCHAR(256) NOT NULL,
            `popularity` INT(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`query_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "af_query_description (
            `query_id` INT(11) NOT NULL,
            `language_id` INT(11) NOT NULL,
            `description` TEXT NOT NULL COLLATE 'utf8_general_ci',
            `h1` VARCHAR(96) NOT NULL COLLATE 'utf8_general_ci',
            `meta_title` VARCHAR(96) NOT NULL COLLATE 'utf8_general_ci',
            `meta_description` VARCHAR(96) NOT NULL COLLATE 'utf8_general_ci',
            `meta_keyword` VARCHAR(96) NOT NULL COLLATE 'utf8_general_ci',
            PRIMARY KEY (`query_id`,`language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "d_meta_data (
                route VARCHAR(255) NOT NULL, 
                store_id INT(11) NOT NULL, 
                language_id INT(11) NOT NULL, 
                name VARCHAR(64) NOT NULL, 
                title VARCHAR(64) NOT NULL, 
                description TEXT NOT NULL, 
                short_description TEXT NOT NULL, 
                meta_title VARCHAR(255) NOT NULL, 
                meta_description VARCHAR(255) NOT NULL, 
                meta_keyword VARCHAR(255) NOT NULL, 
                tag TEXT NOT NULL, 
                custom_title_1 VARCHAR(255) NOT NULL, 
                custom_title_2 VARCHAR(255) NOT NULL, 
                custom_image_title VARCHAR(255) NOT NULL, 
                custom_image_alt VARCHAR(255) NOT NULL, 
                meta_robots VARCHAR(32) NOT NULL DEFAULT 'index,follow', 
                PRIMARY KEY (route, store_id, language_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

        if (VERSION >= '3.0.0.0') {
            $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'af_query_id=%'");
        } else {
            $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "d_url_keyword (
                route VARCHAR(255) NOT NULL, 
                store_id INT(11) NOT NULL, 
                language_id INT(11) NOT NULL, 
                keyword VARCHAR(255) NOT NULL, 
                PRIMARY KEY (route, store_id, language_id), 
                KEY keyword (keyword)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
                
            $this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'af_query_id=%'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query LIKE 'af_query_id=%'");
        }
    }

    public function DropDatabase()
    {
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_query");
        $this->db->query("DROP TABLE IF EXISTS ". DB_PREFIX . "af_query_decription");
        $this->db->query("DELETE FROM " . DB_PREFIX . "d_meta_data WHERE route LIKE 'af_query_id=%'");
        
        $this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'af_query_id=%'");

        if (VERSION >= '3.0.0.0') {
            $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'af_query_id=%'");
        } else {
            $this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'af_query_id=%'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query LIKE 'af_query_id=%'");
        }
    }

    public function saveSEOExtensions($seo_extensions)
    {
        $this->load->model('setting/setting');
        
        $setting['d_seo_extension_install'] = $seo_extensions;
        
        $this->model_setting_setting->editSetting('d_seo_extension', $setting);
    }
    
    /*
    *   Return list of SEO extensions.
    */
    public function getSEOExtensions()
    {
        $this->load->model('setting/setting');

        $installed_extensions = array();
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension ORDER BY code");
        
        foreach ($query->rows as $result) {
            $installed_extensions[] = $result['code'];
        }

        $installed_seo_extensions = $this->model_setting_setting->getSetting('d_seo_extension');

        $installed_seo_extensions = isset($installed_seo_extensions['d_seo_extension_install']) ? $installed_seo_extensions['d_seo_extension_install'] : array();

        $seo_extensions = array();

        $files = glob(DIR_APPLICATION . 'controller/extension/d_seo_module/*.php');

        if ($files) {
            foreach ($files as $file) {
				$seo_extension = basename($file, '.php');
				
				if (in_array($seo_extension, $installed_extensions) && in_array($seo_extension, $installed_seo_extensions)) {
					$seo_extensions[] = $seo_extension;
				}
            }
        }
        
        return $seo_extensions;
    }

    public function ajax($link)
    {
        return str_replace('&amp;', '&', $link);
    }

    public function getGroupId()
    {
        if (VERSION == '2.0.0.0') {
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '" . $this->user->getId() . "'");
            $user_group_id = (int)$user_query->row['user_group_id'];
        } else {
            $user_group_id = $this->user->getGroupId();
        }

        return $user_group_id;
    }

    public function getSEOFilterExtensions()
    {
        $this->load->model('setting/setting');
        
        $installed_extensions = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension ORDER BY code");

        foreach ($query->rows as $result) {
            $installed_extensions[] = $result['code'];
        }

        $installed_seo_extensions = $this->model_setting_setting->getSetting('d_seo_extension');
        $installed_seo_extensions = isset($installed_seo_extensions['d_seo_extension_install']) ? $installed_seo_extensions['d_seo_extension_install'] : array();

        $seo_extensions = array();

        $files = glob(DIR_APPLICATION . 'controller/extension/d_seo_module_ajax_filter/*.php');

        if ($files) {
            foreach ($files as $file) {
                $seo_extension = basename($file, '.php');
        
                if (in_array($seo_extension, $installed_extensions) && in_array($seo_extension, $installed_seo_extensions)) {
                    $seo_extensions[] = $seo_extension;
                }
            }
        }

        return $seo_extensions;
    }

    public function getLanguages()
    {
        $this->load->model('localisation/language');
        
        $languages = $this->model_localisation_language->getLanguages();
        
        foreach ($languages as $key => $language) {
            $languages[$key]['flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
        }
        
        return $languages;
    }
    /*
    *	Return list of stores.
    */
    public function getStores()
    {
        $this->load->model('setting/store');
        
        $result = array();
        
        $result[] = array(
            'store_id' => 0,
            'name' => $this->config->get('config_name')
        );
        
        $stores = $this->model_setting_store->getStores();
        
        if ($stores) {
            foreach ($stores as $store) {
                $result[] = array(
                    'store_id' => $store['store_id'],
                    'name' => $store['name']
                );
            }
        }
        
        return $result;
    }
        /*
    *	Return store.
    */
    public function getStore($store_id)
    {
        $this->load->model('setting/store');
        
        $result = array();
        
        if ($store_id == 0) {
            $result = array(
                'store_id' => 0,
                'name' => $this->config->get('config_name'),
                'url' => HTTP_CATALOG,
                'ssl' => HTTPS_CATALOG
            );
        } else {
            $store = $this->model_setting_store->getStore($store_id);
            
            $result = array(
                'store_id' => $store['store_id'],
                'name' => $store['name'],
                'url' => $store['url'],
                'ssl' => $store['ssl']
            );
        }
                
        return $result;
    }
}
