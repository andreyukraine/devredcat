<?php
class ControllerExtensionModuleDiscountsPackDiscountAccumulative extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/accumulative_discount');

		$this->document->setTitle($this->language->get('heading_title'));
		/* Bootstrap Select CDN */
		$this->document->addStyle("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css");
		$this->document->addScript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js");
		
		$this->load->model('extension/module/discount');
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_discount'] = $this->language->get('text_discount');
		$data['text_fixed'] = $this->language->get('text_fixed');
		$data['text_percentage'] = $this->language->get('text_percentage');
		
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		
		$data['text_select_all'] = $this->language->get('text_select_all');
		$data['text_unselect_all'] = $this->language->get('text_unselect_all');
		
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_ordertotal'] = $this->language->get('entry_ordertotal');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_discount'] = $this->language->get('entry_discount');
		$data['entry_priority'] = $this->language->get('entry_priority');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_type'] = $this->language->get('entry_type');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_remove'] = $this->language->get('button_remove');
		
		$data['error_permission'] = $this->language->get('error_permission');	
		
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->success)) {
			$data['success'] = $this->success;
		} else {
			$data['success'] = '';
		}
		
		$url = '';
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_discounts'),
			'href' => $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/discounts_pack/discount_accumulative', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
		);
		
		$data['permission'] = $this->user->hasPermission('modify', 'extension/module/discounts_pack/discount_accumulative') ? 1 : 0;
		
		$data['cancel'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'], true);
		
		$data['user_token'] = $this->session->data['user_token'];
		
		$data['back_link'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		
		$this->model_extension_module_discount->checkTableExist('accumulative');
		
		if (isset($this->request->post['total_accumulative_discount_status'])) {
			$data['accumulative_discount_status'] = $this->request->post['total_accumulative_discount_status'];
		} else {
			$data['accumulative_discount_status'] = $this->config->get('total_accumulative_discount_status');
		}

		if (isset($this->request->post['total_accumulative_discount_sort_order'])) {
			$data['accumulative_discount_sort_order'] = $this->request->post['total_accumulative_discount_sort_order'];
		} else {
			$data['accumulative_discount_sort_order'] = $this->config->get('total_accumulative_discount_sort_order');
		}

		
		$discounts = $this->model_extension_module_discount->getAccumulativeDisconts('accumulative');

		if (isset($this->request->post['accumulative_discount'])) {
			$accumulative_discounts = $this->request->post['accumulative_discount'];
		} elseif (isset($discounts)) {
      $accumulative_discounts = $discounts;
		} else {
      $accumulative_discounts = array();
		}

		$data['accumulative_discounts'] = array();

		foreach ($accumulative_discounts as $discount) {
			$data['accumulative_discounts'][] = array(
				'amount_from' => $discount['amount_from'],
				'amount_to'	=> $discount['amount_to'],
				'percent'		  => $discount['percent']
			);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/discount_accumulative', $data));
	}
	
	public function saveDiscount() {
		
		$json = array();
		$this->load->language('extension/module/accumulative_discount');
		
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			
			$this->load->model('setting/setting');
			$this->load->model('extension/module/discount');
			
			parse_str(htmlspecialchars_decode($this->request->post['setting']), $settings);
			
			$this->model_setting_setting->editSetting('total_accumulative_discount', $settings);
			
			if(!empty($this->request->post['accumulative_discount'])) {
			
				parse_str(htmlspecialchars_decode($this->request->post['accumulative_discount']), $discount_data);

				$this->model_extension_module_discount->setAccumulativeDisconts($discount_data['accumulative_discount'], 'accumulative');
						
			}
			
			$json['success'] = $this->language->get('text_success');

		} else {
			$json['error'] = $this->language->get('error_warning');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
		
	}

  public function install()
  {
    $sql = "CREATE TABLE IF NOT EXISTS oc_accumulative_discount (
  `accumulative_discount_id` int(11) NOT NULL AUTO_INCREMENT,
  `amount_from` int(11),
  `amount_to` int(11),
  `percent` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`accumulative_discount_id`)
    )";
    $this->db->query($sql);
  }
	
	public function uninstall() {
		$key = 'accumulative';
		$this->db->query("DROP TABLE " . DB_PREFIX . $key . "_discount");
		$this->db->query("DELETE FROM ". DB_PREFIX ."extension WHERE `code` = '" . $key . "_discount';");
	}
	
}
