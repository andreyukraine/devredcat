<?php

class ModelExtensionModuleDAjaxFilterSeo extends Model
{
    private $codename = 'd_ajax_filter_seo';

    public function getCurrentURL()
    {
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $url = "https://";
        } else {
            $url = 'http://';
        }
        
        $url .= $this->request->server['SERVER_NAME'] . $this->request->server['REQUEST_URI'];

        $url = str_replace('&', '&amp;', str_replace('&amp;', '&', $url));
        
        return $url;
    }

    public function addQuery()
    {
        if (isset($this->request->get['path'])) {
            $path = $this->request->get['path'];
        }

        $this->load->model('extension/module/d_ajax_filter');


        $full_params = $this->model_extension_module_d_ajax_filter->getUrlParams();

        if (!empty($full_params)) {
            $explode = explode('=', $full_params);
            $params = $explode[1];
        }

        if (isset($path) && isset($params)) {
            $this->db->query("INSERT INTO ".DB_PREFIX."af_query SET 
                path='".$this->db->escape($path)."', 
                params='".$this->db->escape($params)."', 
                popularity='0'");
            $query_id = $this->db->getLastId();

            $this->load->model('localisation/language');

            $languages = $this->model_localisation_language->getLanguages();

            $stores = $this->getStores();

            foreach ($languages as $language) {
                $this->db->query("INSERT INTO ".DB_PREFIX."af_query_description SET 
                    query_id='".$query_id."', 
                    description='',  
                    h1='',   
                    meta_title='',  
                    meta_description='', 
                    meta_keyword='', 
                    language_id='".$language['language_id']."'");
                foreach($stores as $store){
                    $this->db->query("INSERT INTO ".DB_PREFIX."d_meta_data SET 
                    route='af_query_id=".$query_id."', 
                    store_id='".(int)$store['store_id']."',  
                    language_id='".(int)$language['language_id']."'");
                }
            }
            return $query_id;
        }
        
        return false;
    }

    public function getURLForLanguage($link, $language_code)
    {
        $link = str_replace($this->url->link('common/home', '', true), '', $link);
        
        $url_info = parse_url(str_replace('&amp;', '&', $link));

        $data = array();

        if (isset($url_info['query'])) {
            parse_str($url_info['query'], $data);
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE code = '" . $language_code . "'");

        $language_id = $query->row['language_id'];
        
        if (isset($data['_route_'])) {
            $parts = explode('/', $data['_route_']);

            // remove any empty arrays from trailing
            if (utf8_strlen(end($parts)) == 0) {
                array_pop($parts);
            }

            foreach ($parts as $part) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "url_alias WHERE keyword = '" . $this->db->escape($part) . "'");

                foreach ($query->rows as $result) {
                    parse_str($result['query'], $url_info);

                    if (!empty($url_info)) {
                        if (isset($url_info['path']) && isset($url_info['ajaxfilter']) && isset($url_info['language_id'])) {
                            if ($url_info['language_id'] == $this->config->get('config_language_id')) {
                                $data['route']='product/category';
                                $data['path'] = $url_info['path'];
                                $data['ajaxfilter'] = $url_info['ajaxfilter'];
                            }
                        }
                    }
                }
            }
        }
        
        $params = array();

        if (isset($data['ajaxfilter']) && isset($data['path'])) {
            $params[] = 'path=' . $data['path'];
            $params[] = 'ajaxfilter=' . $data['ajaxfilter'];
        }
        if (isset($data['route'])) {
            foreach ($data as $param => $value) {
                if ($param != '_route_' && $param != 'route' && $param != 'path' && $param!='ajaxfilter') {
                    $params[] = $param . '=' . $value;
                }
            }
            
            $config_language_id = $this->config->get('config_language_id');
            $this->config->set('config_language_id', $language_id);

            $link = $this->url->link($data['route'], implode('&', $params), true);

            $this->config->set('config_language_id', $config_language_id);
        }

        return $link;
    }
    
    public function updatePopularity()
    {
        if (isset($this->request->get['path'])) {
            $path = $this->request->get['path'];
        }

        $this->load->model('extension/module/d_ajax_filter');


        $full_params = $this->model_extension_module_d_ajax_filter->getUrlParams();

        if (!empty($full_params)) {
            $explode = explode('=', $full_params);
            $params = $explode[1];
        }

        if (isset($path) && isset($params)) {
            $this->db->query("UPDATE `".DB_PREFIX."af_query` SET `popularity`=`popularity`+1 WHERE `path`='".$this->db->escape($path)."' AND `params`='".$this->db->escape($params)."'");
        }
    }

    public function getCurrentQuery()
    {
        if (isset($this->request->get['path'])) {
            $path = $this->request->get['path'];
        }

        $this->load->model('extension/module/d_ajax_filter');

        $full_params = $this->model_extension_module_d_ajax_filter->getUrlParams();
        if (!empty($full_params)) {
            $explode = explode('=', $full_params);
            $params = $explode[1];
        }

        if (isset($path) && isset($params)) {
            return $this->findQuery($path, $params);
        } else {
            return array();
        }
    }



    public function findQuery($path, $params)
    {
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."af_query` WHERE `path` = '".$this->db->escape($path)."' AND `params` = '".$this->db->escape($params)."'");
            
        return $query->row;
    }

    public function getQueryDescription($query_id)
    {
        $sql = "SELECT * FROM `".DB_PREFIX."af_query_description` WHERE `query_id` = '".(int)$query_id."' AND `language_id` = '".(int)$this->config->get('config_language_id')."'";
        
        $hash = md5($sql);

        $result = $this->cache->get('af-seo-query-description.' . $hash);

        if (!$result) {
            $query = $this->db->query($sql);
            $result = $query->row;
            $this->cache->set('af-seo-query-description.' .$hash, $result);
        }

        return $result;
    }

    public function getQuery($query_id)
    {
        $query = $this->db->query("SELECT * FROM `".DB_PREFIX."af_query` WHERE `query_id` = '".(int)$query_id."'");
        return $query->row;
    }

    public function getLanguages()
    {
        $this->load->model('localisation/language');
        
        $languages = $this->model_localisation_language->getLanguages();
        
        foreach ($languages as $key => $language) {
            if (VERSION >= '2.2.0.0') {
                $languages[$key]['flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
            } else {
                $languages[$key]['flag'] = 'view/image/flags/' . $language['image'];
            }
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
    * Format the link to work with ajax requests
    */
    public function ajax($link)
    {
        return str_replace('&amp;', '&', $link);
    }
}
