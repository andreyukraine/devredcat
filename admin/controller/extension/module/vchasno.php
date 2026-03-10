<?php
class ControllerExtensionModuleVchasno extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/vchasno');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_vchasno', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['token'])) {
			$data['error_token'] = $this->error['token'];
		} else {
			$data['error_token'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/vchasno', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/vchasno', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_vchasno_status'])) {
			$data['module_vchasno_status'] = $this->request->post['module_vchasno_status'];
		} else {
			$data['module_vchasno_status'] = $this->config->get('module_vchasno_status');
		}

		if (isset($this->request->post['module_vchasno_token'])) {
			$data['module_vchasno_token'] = $this->request->post['module_vchasno_token'];
		} else {
			$data['module_vchasno_token'] = $this->config->get('module_vchasno_token');
		}

		if (isset($this->request->post['module_vchasno_cashier'])) {
			$data['module_vchasno_cashier'] = $this->request->post['module_vchasno_cashier'];
		} else {
			$data['module_vchasno_cashier'] = $this->config->get('module_vchasno_cashier');
		}

		if (isset($this->request->post['module_vchasno_tax_group'])) {
			$data['module_vchasno_tax_group'] = $this->request->post['module_vchasno_tax_group'];
		} else {
			$data['module_vchasno_tax_group'] = $this->config->get('module_vchasno_tax_group');
		}

		if (isset($this->request->post['module_vchasno_rules'])) {
			$data['module_vchasno_rules'] = $this->request->post['module_vchasno_rules'];
		} else {
			$data['module_vchasno_rules'] = $this->config->get('module_vchasno_rules');
		}

		if (isset($this->request->post['module_vchasno_send_phone'])) {
			$data['module_vchasno_send_phone'] = $this->request->post['module_vchasno_send_phone'];
		} else {
			$data['module_vchasno_send_phone'] = $this->config->get('module_vchasno_send_phone');
		}

		if (isset($this->request->post['module_vchasno_send_receipt'])) {
			$data['module_vchasno_send_receipt'] = $this->request->post['module_vchasno_send_receipt'];
		} else {
			$data['module_vchasno_send_receipt'] = $this->config->get('module_vchasno_send_receipt');
		}

		if (isset($this->request->post['module_vchasno_send_type'])) {
			$data['module_vchasno_send_type'] = $this->request->post['module_vchasno_send_type'];
		} else {
			$data['module_vchasno_send_type'] = $this->config->get('module_vchasno_send_type');
		}

		// Get active payment methods
		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('payment');

		$data['payment_methods'] = array();

		foreach ($extensions as $extension) {
			if ($this->config->get('payment_' . $extension . '_status')) {
				$this->load->language('extension/payment/' . $extension, 'extension');

				$data['payment_methods'][] = array(
					'code' => $extension,
					'name' => $this->language->get('extension')->get('heading_title')
				);
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/vchasno', $data));
	}

	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "vchasno_receipt` (
			`order_id` int(11) NOT NULL,
			`vchasno_receipt_id` varchar(255) NOT NULL,
			`vchasno_receipt_url` varchar(255) NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`order_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		$this->load->model('setting/event');
		$this->model_setting_event->addEvent('module_vchasno', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/vchasno/onOrderHistoryAfter');
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "vchasno_receipt`;");

		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('module_vchasno');
	}

	public function createReceipt() {
		$this->load->language('extension/module/vchasno');
		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/module/vchasno')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$order_id = isset($this->request->get['order_id']) ? $this->request->get['order_id'] : 0;
			
			$this->load->model('sale/order');
			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				// Check if already exists
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "vchasno_receipt` WHERE order_id = '" . (int)$order_id . "'");
				if ($query->num_rows) {
					$json['error'] = 'Чек для цього замовлення вже створено!';
				} else {
					$token = $this->config->get('module_vchasno_token');
					$cashier = $this->config->get('module_vchasno_cashier');
					$tax_group = $this->config->get('module_vchasno_tax_group');
					
					$rules = $this->config->get('module_vchasno_rules');
					$payment_code = $order_info['payment_code'];
					$payment_form = isset($rules[$payment_code]['form']) ? $rules[$payment_code]['form'] : 'Безготівка';

					$products = $this->model_sale_order->getOrderProducts($order_id);
					$totals = $this->model_sale_order->getOrderTotals($order_id);

					$rows = array();
					foreach ($products as $product) {
						$rows[] = array(
							'type'      => 0,
							'name'      => $product['name'],
							'cnt'       => (float)$product['quantity'] * 1000,
							'price'     => round($product['price'], 2) * 100,
							'cost'      => round($product['total'], 2) * 100,
							'tax_group' => (int)$tax_group
						);
					}

					foreach ($totals as $total) {
						if ($total['code'] == 'shipping' && (float)$total['value'] > 0) {
							$rows[] = array(
								'type'      => 1,
								'name'      => $total['title'],
								'cnt'       => 1000,
								'price'     => round($total['value'], 2) * 100,
								'cost'      => round($total['value'], 2) * 100,
								'tax_group' => (int)$tax_group
							);
						}
					}

					$calculated_total = 0;
					foreach ($rows as $row) {
						$calculated_total += $row['cost'];
					}

					$pay_type = 1; 
					if ($payment_form == 'Готівка') $pay_type = 0;

					$receipt = array(
						'cashier' => $cashier,
						'receipt' => array(
							'type' => 0,
							'rows' => $rows,
							'pays' => array(
								array('type' => $pay_type, 'sum' => $calculated_total)
							)
						)
					);

					if ($this->config->get('module_vchasno_send_phone')) {
						$receipt['receipt']['customer_phone'] = preg_replace('/[^0-9]/', '', $order_info['telephone']);
					}

					if ($this->config->get('module_vchasno_send_receipt') && $this->config->get('module_vchasno_send_type')) {
						$receipt['receipt']['customer_phone'] = preg_replace('/[^0-9]/', '', $order_info['telephone']);
						$receipt['receipt']['send_type'] = $this->config->get('module_vchasno_send_type');
					}

					$curl = curl_init('https://kassa.vchasno.ua/api/v1/receipts');
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($receipt));
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Authorization: ' . $token
					));

					$response = curl_exec($curl);
					$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
					curl_close($curl);

					if ($http_code == 200 || $http_code == 201) {
						$result = json_decode($response, true);
						$this->db->query("INSERT INTO `" . DB_PREFIX . "vchasno_receipt` SET 
							order_id = '" . (int)$order_id . "', 
							vchasno_receipt_id = '" . $this->db->escape($result['receipt_id']) . "', 
							vchasno_receipt_url = '" . $this->db->escape($result['visual_url']) . "', 
							date_added = NOW()");
						
						$this->model_sale_order->addOrderHistory($order_id, array(
							'order_status_id' => $order_info['order_status_id'],
							'comment'         => 'Вчасно.Каса: Чек створено вручну. URL: ' . $result['visual_url'],
							'notify'          => false
						));

						$json['success'] = 'Чек успішно створено!';
						$json['receipt_url'] = $result['visual_url'];
					} else {
						$json['error'] = 'Vchasno Error: ' . $response;
					}
				}
			} else {
				$json['error'] = 'Замовлення не знайдено!';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/vchasno')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['module_vchasno_token']) {
			$this->error['token'] = $this->language->get('error_token');
		}

		return !$this->error;
	}
}
