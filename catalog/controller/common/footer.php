<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

    $data['name'] = $this->config->get('config_name');

		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('information/tracking');
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/akcii');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

    $data['powered'] = date('Y', time());

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

    //++Andrey
    $data['categories'] = array();
    $this->load->model('catalog/category');
    $categories = $this->model_catalog_category->getCategories(0);
    foreach ($categories as $category) {
      // Level 1
      $data['categories'][] = array(
        'id' => $category['category_id'],
        'name' => mb_convert_case(mb_strtolower($category['name']), MB_CASE_TITLE, "UTF-8"),
        'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
      );
    }

    $data['manufactures'] = array();

    $filter = array();
    $filter['in_footer'] = true;
    $this->load->model('catalog/manufacturer');
    $manufactures = $this->model_catalog_manufacturer->getManufacturers($filter);

    foreach ($manufactures as $manufacture) {
      if ((int)$manufacture['in_footer'] == 1) {
        $data['manufactures'][] = array(
          'id' => $manufacture['manufacturer_id'],
          'name' => mb_convert_case(mb_strtolower($manufacture['name']), MB_CASE_TITLE, "UTF-8"),
          'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacture['manufacturer_id'])
        );
      }
    }

    $data['menu_bottom'] = $this->load->controller('common/menu_bottom');


    if ($this->request->server['HTTPS']) {
      $server = $this->config->get('config_ssl');
    } else {
      $server = $this->config->get('config_url');
    }

    if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
      $data['logo'] = $server . 'image/' . $this->config->get('config_logo');
    } else {
      $data['logo'] = '';
    }

    $data['config_name'] = $this->config->get('config_name');
    $data['text_copiring'] = $this->language->get('text_copiring') . " " . rtrim($this->config->get('site_url'), '/');

    $data['config_soc_telegram'] = $this->config->get('config_soc_telegram');
    $data['config_soc_facebook'] = $this->config->get('config_soc_facebook');
    $data['config_soc_instagram'] = $this->config->get('config_soc_instagram');
    $data['config_soc_youtube'] = $this->config->get('config_soc_youtube');

    $data['telephone'] = $this->config->get('config_telephone');
    $data['telephone_1'] = $this->config->get('config_telephone_1');
    $data['telephone_2'] = $this->config->get('config_telephone_2');
    $data['telephone_3'] = $this->config->get('config_telephone_3');
    $data['open'] = nl2br(html_entity_decode($this->config->get('config_open')));

    $data['config_last_update'] = $this->config->get('config_last_update');

    $data['email'] = $this->config->get('config_email');

    //--Andrey

    $ocpolicy_status = $this->config->get('ocpolicy_status');
    $ocpolicy_data = $this->config->get('ocpolicy_data');
    
    $data['ocpolicy_value'] = (isset($ocpolicy_data['value']) && $ocpolicy_data['value']) ? $ocpolicy_data['value'] : 'ocpolicy';
    $data['ocpolicy_status'] = $ocpolicy_status;

		$data['scripts'] = $this->document->getScripts('footer');
		$data['styles'] = $this->document->getStyles('footer');

    $data['menu'] = $this->load->controller('common/menu');
		
		return $this->load->view('common/footer', $data);
	}
}
