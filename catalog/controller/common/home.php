<?php
class ControllerCommonHome extends Controller {
	public function index() {

    $this->load->model('setting/setting');

    if (isset($this->session->data['language'])) {
      $langId = $this->model_localisation_language->getLanguageByCode($this->session->data['language'])['language_id'];
    }

    $data['meta_h1'] = $this->language->get('heading_title');
    $meta_h1 = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_h1_title');
    if (!empty($meta_h1[$langId]['value'])){
      $data['meta_h1'] = $meta_h1[$langId]['value'];
    }

    $meta_title = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_meta_title');
    if (!empty($meta_title[$langId]['value'])){
      $data['meta_title'] = $meta_title[$langId]['value'];
    }
		$this->document->setTitle($data['meta_title']);

    $meta_desc = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_meta_description');
    if (!empty($meta_desc[$langId]['value'])){
      $data['meta_desc'] = $meta_desc[$langId]['value'];
    }
    $this->document->setDescription($data['meta_desc']);

		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$canonical = $this->url->link('common/home');
			if ($this->config->get('config_seo_pro') && !$this->config->get('config_seopro_addslash')) {
				$canonical = rtrim($canonical, '/');
			}
			$this->document->addLink($canonical, 'canonical');
		}

    //++Andrey
    $data['home_desc'] = '';
    $home_desc = $this->model_setting_setting->getValuesLangByKey($this->config->get('config_store_id'), 'config_home_description');
    if (!empty($home_desc[$langId]['value'])) {
      $data['home_desc'] = html_entity_decode($home_desc[$langId]['value']);
    }
    //--Andrey

    $data['block1'] = $this->load->controller('common/block1');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

    $data['schema_home'] = $this->load->controller('schema/home', $data);

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
