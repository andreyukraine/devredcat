<?php
class ControllerextensionModuleOCredirect extends Controller {
	private $codes = array(
		301 => 'Moved Permanently',
		302 => 'Moved Temporarily',
		410 => 'Not Found (GONE)',
		404 => 'Not Found',
		403 => 'Forbidden',
		307 => 'Temporary Redirect'
	);

	private $path_module ='extension/module/ocredirect';
	private $path_extension = 'marketplace/extension/&type=module';
	private $module_name ='ocredirect';
	private $my_model ='model_extension_module_ocredirect';
	
	private $token = 'user_token';
	
	public  function index() {
		$data = $this->load->language($this->path_module);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('ocredirect', $this->request->post);
			
			if (isset($this->request->post['redirect_status']) && $this->request->post['redirect_status']) {
				$this->installEvent();
				$this->install_eventTemplate($this->request->post);

				if (isset($this->request->post['redirect_check404'])) {
					$this->installEventOne($this->getEventsSef('410'));
				} else {
					$this->uninstallEventOne($this->getEventsSef('410'));
				}

				if (isset($this->request->post['redirect_check_redirect'])) {
					$this->installEventOne($this->getEventsSef('301'));
				} else {
					$this->uninstallEventOne($this->getEventsSef('301'));
				}

			} else {
				$this->uninstallEvent();
				$this->uninstall_eventTemplate();
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->makeUrl($this->path_module));
		}
		
		$this->load->model($this->path_module);
		
		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['action'] = $this->makeUrl($this->path_module);
		$data['cancel'] = $this->makeUrl($this->path_extension);
		$data['settings'] = $this->makeUrl($this->path_module);

		$data['export'] = $this->makeUrl('extension/module/ocredirect/export');
		$data['import'] = $this->makeUrl('extension/module/ocredirect/import');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_clear'] = $this->language->get('button_clear');

		$data[$this->token] = $this->session->data[$this->token];
		$l_codes = $this->language->get('codes');
		$data['codes']=[];
		foreach ($this->codes as $code=>$text) {
			$data['codes'][$code] = !empty($l_codes[$code])?$l_codes[$code]:$text;
		}

		$page = 1;
		if (isset($this->request->get['page']))
			$page = $this->request->get['page'];
		
		$order = 'ASC';
		if (isset($this->request->get['order']))
			$order = $this->request->get['order'];

		$sort = 'from_url';
		if (isset($this->request->get['sort']))
			$sort = $this->request->get['sort'];

		$filter = array();

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		}
		
		$url = '';
		
		$url = http_build_query(array("filter" => $filter));

		if (isset($this->request->get['page']))
			$url = '&page=' . $this->request->get['page'];
		
		if (isset($this->request->get['sort']))
			$url = '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order']))
			$url = '&order=' . $this->request->get['order'];
		
		
		$data['filter'] = $filter;

		$data['delete'] = $this->makeUrl($this->path_module . '/delete', $url);
		$data['add']	= $this->makeUrl($this->path_module . '/edit', $url);
		$data['clear']	= $this->makeUrl($this->path_module . '/clear', $url);

    $data['active'] = $this->makeUrl($this->path_module . '/active', $url);
    $data['deactive'] = $this->makeUrl($this->path_module . '/deactive', $url);
		
		$data['get_filter'] = $this->makeUrlScript($this->path_module);

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->makeUrl('common/dashboard')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->makeUrl($this->path_extension)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->makeUrl($this->path_module)
		);

		$filter_data = array (
			'page' => $page,
			'sort' => $sort,
			'order' => $order,
			'filter' => $filter
		);
		$rules = $this->model_extension_module_ocredirect->getRules($filter_data);
		$data['rules'] = array();
		foreach ($rules as $rule) {
			$data['rules'][] = array(
				'redirect_id'  => $rule['redirect_id'],
				'from_url'  => $rule['from_url'],
				'to_url'	=> $rule['to_url'],
				'status'	=> $rule['status'],
				'code'	  => $rule['code'],
				'cnt'	   => $rule['cnt'],
				'last_date' => $rule['last_date'],
				'edit'	  => $this->makeUrl($this->path_module . '/edit', "&redirect_id=" . $rule['redirect_id'] . $url),
				'delete'	=> $this->makeUrl($this->path_module . '/delete', "&redirect_id=" . $rule['redirect_id'] . $url),
				'check'	 => $this->makeUrl($this->path_module . '/check', "&redirect_id=" . $rule['redirect_id'] . $url),
			);
		}
		$data['delimiters'] = array(
			"," => $this->language->get('text_import_delimiter_coma'),
			";" => $this->language->get('text_import_delimiter_semicolon'),
			"\t" => $this->language->get('text_import_delimiter_tab'),
		);
		
		$url = '';
		$url = http_build_query(array('filter' => $filter));
		if (isset($this->request->get['sort']))
			$url = '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order']))
			$url = '&order=' . $this->request->get['order'];

		$rule_total = $this->model_extension_module_ocredirect->getTotalRules($filter_data);
		$pagination = new Pagination();
		$pagination->total = $rule_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->makeUrl($this->path_module, $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['result'] = sprintf($this->language->get('text_pagination'), ($rule_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($rule_total - $this->config->get('config_limit_admin'))) ? $rule_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $rule_total, ceil($rule_total / $this->config->get('config_limit_admin')));
		
		if (isset($this->request->post['redirect_status'])) {
			$data['redirect_status'] = $this->request->post['redirect_status'];
		} else {
			$data['redirect_status'] = $this->config->get('redirect_status');
		}

		if (isset($this->request->post['redirect_templates'])) {
			$data['redirect_templates'] = $this->request->post['redirect_templates'];
		} else {
			$data['redirect_templates'] = $this->config->get('redirect_templates');
		}
		$dirs = scandir(DIR_CATALOG . 'view/theme');
		$dirs = array_diff($dirs, ['..', '.']);

		
		$dirs = array_filter($dirs,function($dir) {return is_dir(DIR_CATALOG . 'view/theme/' . $dir);});
		$templates = [];
		foreach ($dirs as $dir) {
			if (is_dir(DIR_CATALOG . 'view/theme/' . $dir . '/template/error')) {
				$f404 = scandir(DIR_CATALOG . 'view/theme/' . $dir . '/template/error');
				$f404 = array_diff($f404, ['..', '.']);
				foreach ($f404 as $f) {
					if (is_file(DIR_CATALOG . 'view/theme/' . $dir . '/template/error/' . $f)) {
						$templates[] = 'error/' . $f;
					}
				
				}
			}
		}

		$data['templates'] = implode(',',array_unique($templates));
		if (isset($this->request->post['redirect_check404'])) {
			$data['redirect_check404'] = $this->request->post['redirect_check404'];
		} else {
			$data['redirect_check404'] = $this->config->get('redirect_check404');
		}
		if (isset($this->request->post['redirect_check_redirect'])) {
			$data['redirect_check_redirect'] = $this->request->post['redirect_check_redirect'];
		} else {
			$data['redirect_check_redirect'] = $this->config->get('redirect_check_redirect');
		}

		$this->footer('list', $data);
	}
	
	public function clear() {
		$this->load->language($this->path_module);
		if ($this->validate()) {
			$this->load->model($this->path_module);
			$this->model_extension_module_ocredirect->clear();
		}
		$this->response->redirect($this->makeUrl($this->path_module));
	}

	public function check() {
		$this->load->language($this->path_module);
		$this->load->model($this->path_module);
		if (isset($this->request->get['redirect_id'])) {
			$rule_info = $this->model_extension_module_ocredirect->getRule($this->request->get['redirect_id']);
			if ($rule_info) {
				$curl = curl_init();
				if ($this->request->server['HTTPS']) {
					$catalog = HTTPS_CATALOG;
				} else {
					$catalog = HTTP_CATALOG;
				}
				$url = $catalog . ltrim(trim($rule_info['from_url'],'#'), '/');
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_FILETIME, true);
				curl_setopt($curl, CURLOPT_NOBODY, true);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HEADER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				$header = curl_exec($curl);
				$info = curl_getinfo($curl);
				
				echo "<pre>";
				
				print_r($header);
				print_r($info);
				echo "</pre>";
				
				curl_close($curl);
				
			} else {
				$data['text'] = $this->language->get('error_rule_not_found');
			}
		} else {
			$data['text'] = $this->language->get('error_rule_empty');
		}
	}

	public function edit() {
		$data = $this->load->language($this->path_module);
		$this->load->model($this->path_module);

		$this->document->setTitle($this->language->get('heading_title'));

		$url = '';

		$filter = array();

		if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		}

		$url = http_build_query(array("filter" => $filter));
		if (isset($this->request->get['page']))
			$url = '&page=' . $this->request->get['page'];
		if (isset($this->request->get['sort']))
			$url = '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order']))
			$url = '&order=' . $this->request->get['order'];

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
			if (isset($this->request->get['redirect_id'])) {
				$this->model_extension_module_ocredirect->editRule($this->request->get['redirect_id'], $this->request->post);
			} else {
				$this->model_extension_module_ocredirect->addRule($this->request->post);
			}
			$this->response->redirect($this->makeUrl($this->path_module, $url, true));
		}

		if (isset($this->request->get['redirect_id'])) {
			$rule_info = $this->model_extension_module_ocredirect->getRule($this->request->get['redirect_id']);
			if (isset($this->request->post['from_url'])) {
				$data['from_url'] = $this->request->post['from_url'];
			} else {
				$data['from_url'] = $rule_info['from_url'];
			}
			if (isset($this->request->post['to_url'])) {
				$data['to_url'] = $this->request->post['to_url'];
			} else {
				$data['to_url'] = $rule_info['to_url'];
			}
			if (isset($this->request->post['code'])) {
				$data['code'] = $this->request->post['code'];
			} else {
				$data['code'] = $rule_info['code'];
			}
			if (isset($this->request->post['status'])) {
				$data['status'] = $this->request->post['status'];
			} else {
				$data['status'] = $rule_info['status'];
			}
		} else {
			if (isset($this->request->post['from_url'])) {
				$data['from_url'] = $this->request->post['from_url'];
			} else {
				$data['from_url'] = '';
			}
			if (isset($this->request->post['to_url'])) {
				$data['to_url'] = $this->request->post['to_url'];
			} else {
				$data['to_url'] = '';
			}
			if (isset($this->request->post['code'])) {
				$data['code'] = $this->request->post['code'];
			} else {
				$data['code'] = 301;
			}
			if (isset($this->request->post['status'])) {
				$data['status'] = $this->request->post['status'];
			} else {
				$data['status'] = 1;
			}
		}
		if (isset($this->request->get['redirect_id'])){
			$data['heading_title'] = $this->language->get('heading_edit');
		} else {
			$data['heading_title'] = $this->language->get('heading_add');
		}

		$data['action'] = $this->makeUrl($this->path_module . '/edit', (isset($this->request->get['redirect_id']) ? '&redirect_id=' . $this->request->get['redirect_id'] : ''), true);
		$data['cancel'] = $this->makeUrl($this->path_module);
		$data['settings'] = $this->makeUrl($this->path_module);

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data[$this->token] = $this->session->data[$this->token];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->makeUrl('common/dashboard')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_module'),
			'href' => $this->makeUrl($this->path_extension)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->makeUrl($this->path_module)
		];
		$data['errors'] = $this->error;

		$l_codes = $this->language->get('codes');

		foreach ($this->codes as $code=>$text) {
			$data['codes'][$code] = !empty($l_codes[$code])?$l_codes[$code]:$text;
		}

		$this->footer('form', $data);
	}
	
	public function delete() {
		$this->load->language($this->path_module);
		if ($this->validate()) {
			$this->load->model($this->path_module);
			if (isset($this->request->get['redirect_id'])) {
				$this->model_extension_module_ocredirect->deleteRule($this->request->get['redirect_id']);
			} elseif (isset($this->request->post['selected'])) {
				foreach ($this->request->post['selected'] as $redirect_id) {
					$this->model_extension_module_ocredirect->deleteRule($redirect_id);
				}
			}
		}
		$this->response->redirect($this->makeUrl($this->path_module, (isset($this->request->get['filter']) ? '&' . http_build_query(array("filter" => $this->request->get['filter'])) : ""), true));
	}

  public function active() {
    $this->load->language($this->path_module);
    if ($this->validate()) {
      $this->load->model($this->path_module);
      if (isset($this->request->get['redirect_id'])) {
        $this->model_extension_module_ocredirect->activeRule($this->request->get['redirect_id']);
      } elseif (isset($this->request->post['selected'])) {
        foreach ($this->request->post['selected'] as $redirect_id) {
          $this->model_extension_module_ocredirect->activeRule($redirect_id);
        }
      }
    }
    $this->response->redirect($this->makeUrl($this->path_module, (isset($this->request->get['filter']) ? '&' . http_build_query(array("filter" => $this->request->get['filter'])) : ""), true));
  }

  public function deactive() {
    $this->load->language($this->path_module);
    if ($this->validate()) {
      $this->load->model($this->path_module);
      if (isset($this->request->get['redirect_id'])) {
        $this->model_extension_module_ocredirect->deactiveRule($this->request->get['redirect_id']);
      } elseif (isset($this->request->post['selected'])) {
        foreach ($this->request->post['selected'] as $redirect_id) {
          $this->model_extension_module_ocredirect->deactiveRule($redirect_id);
        }
      }
    }
    $this->response->redirect($this->makeUrl($this->path_module, (isset($this->request->get['filter']) ? '&' . http_build_query(array("filter" => $this->request->get['filter'])) : ""), true));
  }

	private function validate() {
		$errors = array();
		if (!$this->user->hasPermission('modify', $this->path_module))
			$errors['persimission'] = $this->language->get('error_permission');
		$this->error = $errors;
		return !$this->error;
	}

	private function validateForm() {
		$errors = array();
		if (!$this->user->hasPermission('modify', $this->path_module))
			$errors['persimission'] = $this->language->get('error_permission');

		if (!isset($this->request->post['from_url']) || isset($this->request->post['from_url']) && mb_strlen(trim($this->request->post['from_url'])) == 0) {
			$errors['from_url'] = $this->language->get('error_from_url');
		}

		if (isset($this->request->post['from_url']) && mb_strlen(trim($this->request->post['from_url'])) >0) {
			if (preg_match('#^(http|https):\/\/#', $this->request->post['from_url'])) {
				$errors['from_url'] = $this->language->get('error_protocol_from_url');
			}
			$row = $this->{$this->my_model}->checkFromUrl($this->request->post['from_url']);
			if ($row) {
				if (isset($this->request->get['redirect_id']) &&  $this->request->get['redirect_id']!= $row['redirect_id']) {
					$errors['from_url'] = $this->language->get('error_from_url_exists');
				}
			}
			
		}

		if (!isset($this->request->post['code']) || (isset($this->request->post['code']) && !in_array($this->request->post['code'], array_keys($this->codes)))) {
			$errors['code'] = $this->language->get('error_code');
		}

		if (!isset($this->request->post['to_url']) || mb_strlen(trim($this->request->post['to_url'])) == 0) {
			if (isset($this->request->post['code']) && in_array($this->request->post['code'], array(301,302))) {
				$errors['to_url'] = $this->language->get('error_to_url');
			}
		}
			
		if (!isset($this->request->post['status']) || !in_array($this->request->post['status'], array('0', '1')))
			$errors['status'] = $this->language->get('error_status');

		$this->error = $errors;
		return !$this->error;
	}

	protected  function installEvent() {
		$events = $this->getEvents();
		$this->load->model('setting/event');
		foreach ($events as $code=>$value) {
			$this->model_setting_event->deleteEventByCode($code);
			$this->model_setting_event->addEvent($code, $value['trigger'], $value['action'], 1);
		}		
	}

	protected  function uninstallEvent() {
		$event_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE code LIKE 'ocredirect%'");
		if ($event_query->num_rows) {
			$this->load->model('setting/event');

			foreach ($event_query->rows as $row) {
				$this->model_setting_event->deleteEventByCode($row['code']);
			}
		}
	}
	protected function installEventOne($event) {
		$this->load->model('setting/event');

		foreach ($event as $code=>$value) {
			$this->model_setting_event->deleteEventByCode($code);
			$this->model_setting_event->addEvent($code, $value['trigger'], $value['action'], 1);
		}

	}
	protected function uninstallEventOne($event) {
		$this->load->model('setting/event');

		foreach ($event as $code=>$value) {
			$this->model_setting_event->deleteEventByCode($code);
		}

	}
	
	public  function install() {
		$this->load->model($this->path_module);
		$this->model_extension_module_ocredirect->install();
	}

	public  function uninstall() {
		if ($this->validate()) {
			$this->load->model($this->path_module);
			$this->model_extension_module_ocredirect->uninstall();
		}
		$this->uninstallEvent();
	}

		
	protected function uninstall_eventTemplate() {
		$event_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE code LIKE 'ocredirectTemplate%'");
		if ($event_query->num_rows) {
			$this->load->model('setting/event');

			foreach ($event_query->rows as $row) {
				$this->model_setting_event->deleteEventByCode($row['code']);
			}
		}
	}

	protected function install_eventTemplate($data) {
		$this->load->model('setting/event');
		$this->uninstall_eventTemplate();

		$events['ocredirectTemplate'] = array(
				'trigger' => 'catalog/view/error/not_found/before/',
				'action'  => 'startup/redirect/redirect301',
		);


		if (isset($data['ocredirect_templates'])) {
			$templates = explode(',',$this->request->post['ocredirect_templates']);
			foreach ($templates as $key=>$template) {
				$template = trim($template);
				if ($template) {
					$events['ocredirectTemplate' . $key] =  array(
							'trigger' => 'catalog/view/' . $template . '/before/',
							'action'  => 'startup/redirect/redirect301',
					);
				}
			}
		}
		
		foreach ($events as $code=>$value) {
				$this->model_setting_event->addEvent($code, $value['trigger'], $value['action'], 1);
		}
	}
		
	public function deleteProduct(&$view, &$data) {

		$results = [];
		$product_id = $data[0];
		
		$catalog = HTTPS_CATALOG;
		$result = $this->getUrl($catalog, 'product/product&product_id=' . $product_id);
		if ($result) {
			$this->load->model($this->path_module);
			$results = json_decode(base64_decode($result),true);
			if ($results) {
				foreach ($results as $store) {
					foreach ($store as $result) {
						$from_url = $result;
						$this->model_extension_module_ocredirect->addRule([
							'from_url' 		=> $from_url,
							'to_url' 		=> '/',
							'code'			=> '410',
							'status'		=> 1,
						]);
					}
				}
			}
		}
	}


	public function deleteCategory(&$view, &$data) {
		$results = array();
		$category_id = $data[0];
		$from_url = $this->getUrl('product/category&path=' . $category_id);
		$this->load->model($this->path_module);
		$this->model_extension_module_ocredirect->addRule(array(
			'from_url' 		=> $from_url,
			'to_url' 		=> '/',
			'code'			=> '301',
			'status'		=> 1,
		));
	}


	public function editProduct(&$view, &$data) {
		$results = [];
		$product_id = $data[0];
		$product_info = $data[1];
		if (isset($product_info['product_seo_url'])) {
			$sql = "SELECT * FROM " . DB_PREFIX . "seo_url WHERE query='product_id=" . (int)$product_id . "'";
			$res = $this->db->query($sql);
			if ($res->num_rows){
				//$real_keyword = [];
				$this->load->model($this->path_module);
				foreach ($res->rows as $row){
					$real_keyword[$row['store_id']][$row['language_id']] = $row['keyword'];
				}
				
				foreach ($product_info['product_seo_url'] as $store_id=>$keywords) {
					foreach ($keywords as $language_id=>$keyword) {
						if ($real_keyword[$store_id][$language_id] != $keyword) {
							if (!empty($real_keyword[$store_id][$language_id])) {
								if ($keyword) {
									$this->model_extension_module_ocredirect->addRule([
										'from_url' 		=> "(.*)" . $real_keyword[$store_id][$language_id] . "([/]|$)",
										'to_url' 		=> '${1}' . $keyword . '${2}',
										'code'			=> '301',
										'status'		=> 1,
									]);
								}
							}
						}
					}
				}
			}
		}
	}

	protected function getUrl($catalog, $query) {
		$curl = curl_init();
		
		$sting_query= json_encode($query);
		$url = $catalog . 'index.php?route=startup/redirect/getUrl';
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FILETIME, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'query=' . base64_encode($sting_query));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		$result = curl_exec($curl);

		$info = curl_getinfo($curl);
		
		curl_close($curl);
		if (isset($info['http_code']) && $info['http_code'] == 200) {
/*		echo "<pre>";
			var_dump($result);
			var_dump($info);
		echo "</pre>";
*/
		return $result;
		} else {
			return false;
		}
	}

		
	protected function getEventsSef($code) {
		$events['410'] = array(
			'ocredirectDeleteProduct' => array(
				'trigger' => 'admin/model/catalog/product/deleteProduct/before',
				'action'  => $this->path_module . '/deleteProduct',
			),
			
			'ocredirectDeleteCategory' => array(
				'trigger' => 'admin/model/catalog/product/deleteCategory/before',
				'action'  => $this->path_module . '/deleteCategory',
			),

		);
		
		$events['301'] = array(
			'ocredirectEditProducr' => array(
				'trigger' => 'admin/model/catalog/product/editProduct/before',
				'action'  => $this->path_module . '/editProduct',
			),

		);
		if (array_key_exists($code, $events)) {
			return $events[$code];
		} else {
			return array();
		}
	}

	protected function getEvents() {
		$events = array(
			'ocredirect' => array(
				'trigger' => 'catalog/view/error/not_found/before',
				'action'  => 'startup/redirect/redirect301',
			),
			'ocredirect_not_found' => array(
				'trigger' => 'catalog/controller/error/not_found/before',
				'action'  => 'startup/redirect/redirect301_not_found',
			),
		);
		return $events;
	}
	
	public  function export() {
		$this->load->language($this->path_module);
		$this->load->model($this->path_module);
		$total = $this->model_extension_module_ocredirect->getTotalRules();
		$filter_data = array(
			'limit' => $total,
			'page' => 1,
		);
		
		$redirects = $this->model_extension_module_ocredirect->getRules($filter_data);
		
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=ocredirect-".date('d-m-Y').".csv");
		header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
		header("Pragma: no-cache"); // HTTP 1.0
		header("Expires: 0"); // Proxies
		$out = fopen('php://output', 'w');
		$export_head = array(
			"From URL",
			"To URL",
			"HTTP server code",
			"Status"
		);
		fputcsv($out, $export_head);
		
		if ($redirects)
			foreach ($redirects as $rule) {
				$export = array(
					$rule['from_url'],
					$rule['to_url'],
					$rule['code'],
					$rule['status'],
				);
			fputcsv($out, $export);
			}

		fclose($out);		
	}
	
	public  function import() {
		$data = $this->load->language($this->path_module);
		if (!$this->validate()) {
			$this->response->redirect($this->makeUrl($this->path_module, (isset($this->request->get['filter']) ? '&' . http_build_query(array("filter" => $this->request->get['filter'])) : ""), true));
		}

		$this->load->model($this->path_module);
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$result = false;
		$errors = array();
		if (!isset($this->request->files['filename']) || $this->request->files['filename']['error'] != 0)
			$errors[] = $this->language->get('error_uploadfile');
		else {
			$results = array (
				'all'	   => array(
					'text' => $this->language->get('text_success_result_all'),
					'cnt' => 0
					),
				'update'	=> array(
					'text' => $this->language->get('text_success_result_update'),
					'cnt' => 0
					),
				'insert'	=> array(
					'text' => $this->language->get('text_success_result_insert'),
					'cnt' => 0
					),
				'error'	 => array(
					'text' => $this->language->get('text_success_result_error'),
					'cnt' => 0
					),
			);
			$delimiters = array(
				';',
				"\t",
				','
			);
			if (in_array($this->request->post['delimiter'],$delimiters)) {
				$delimiter = $this->request->post['delimiter'];
			} else {
				$delimiter = ',';
			}
			$line = 1;
			
			$fp = fopen($this->request->files['filename']['tmp_name'], "r");
			if ($fp !== false) {
				while (($export = fgetcsv($fp, 1000, $delimiter)) !== false) {
					if (count($export)  < 2) {
						$errors[] = sprintf($this->language->get('error_data'), $line);
						$results['error']['cnt']++;
					} else {
						if (isset($export[0]) && stristr($export[0],'from_url')) continue;
						if (isset($export[0])) $from_url = $export[0];
						if (isset($export[1])) {
							$to_url = $export[1];
						} else {
							$to_url = '';
						}
						if (isset($export[2]) && isset($this->codes[$export[2]])) {
							$code = $export[2];
						} else {
							$code = 301;
						}
						if (isset($export[3]) && in_array($export[3], array(0,1))) {
							$status = $export[3];
						} else {
							$status = 0;
						}
						$check = $this->model_extension_module_ocredirect->checkFromUrl($from_url);
						if (!$check) {
							$this->model_extension_module_ocredirect->addRule(array(
								'from_url' 		=> $from_url,
								'to_url' 		=> $to_url,
								'code'			=> $code,
								'status'		=> $status,
							));
							$results['insert']['cnt']++;
						} else {
							$this->model_extension_module_ocredirect->editRule($check['redirect_id'], array(
								'from_url' 		=> $from_url,
								'to_url' 		=> $to_url,
								'code'			=> $code,
								'status'		=> $status,
							));
							$results['update']['cnt']++;
						}
					}
				$line++;
				$results['all']['cnt']++;
				}
			}
		}
		// view 
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['cancel'] = $this->makeUrl($this->path_extension);
		$data['link_return'] = $this->makeUrl($this->path_module);
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data[$this->token] = $this->session->data[$this->token];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->makeUrl('common/dashboard')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->makeUrl($this->path_extension)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->makeUrl($this->path_module)
		);


		$data['errors'] = $errors;
		$data['results'] = $results;

		$this->footer('import', $data);
	}

	private function footer($template, $data) {
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
		
        $data[$this->token] = $this->session->data[$this->token];
        $data['path_module'] = $this->path;
        $this->response->setOutput($this->load->view($this->path_module . '/' . $this->module_name . '_' . $template, $data));
	}

	private function makeUrl($route, $arg=''){
		if ($arg) {
			$arg = '&' . ltrim($arg,'&');
		}
		return $this->url->link ($route, $this->token . '=' . $this->session->data[$this->token] . $arg, true);
	}

	private function makeUrlScript($route, $arg=''){
		return str_replace('&amp;','&',$this->makeUrl($route, $arg));
	}

}
