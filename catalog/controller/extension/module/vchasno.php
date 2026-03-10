<?php
class ControllerExtensionModuleVchasno extends Controller {
	public function onOrderHistoryAfter($route, $args) {
		if (!$this->config->get('module_vchasno_status')) {
			return;
		}

		$order_id = $args[0];
		$order_status_id = $args[1];

		// Check if receipt already exists
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "vchasno_receipt` WHERE order_id = '" . (int)$order_id . "'");
		if ($query->num_rows) {
			return;
		}

		// Load order info
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return;
		}

		// Check if this payment method should trigger automatic receipt
		$rules = $this->config->get('module_vchasno_rules');
		$payment_code = $order_info['payment_code'];

		if (empty($rules[$payment_code]['status'])) {
			return;
		}

		// Only generate on "Complete" status? 
		// Actually, let's check what status is considered "Complete"
		if ($order_status_id != $this->config->get('config_complete_status_id')[0] && $order_status_id != 5) { // 5 is usually 'Complete'
			// return; 
			// Wait, the user might want it on any status. 
			// Let's assume for now it's when the status matches config_complete_status_id.
			$complete_statuses = (array)$this->config->get('config_complete_status_id');
			if (!in_array($order_status_id, $complete_statuses)) {
				return;
			}
		}

		$this->sendReceipt($order_info, $rules[$payment_code]['form']);
	}

	public function sendReceipt($order_info, $payment_form) {
		$token = $this->config->get('module_vchasno_token');
		if (!$token) return;

		$this->load->model('account/order');
		$products = $this->model_account_order->getOrderProducts($order_info['order_id']);
		$totals = $this->model_account_order->getOrderTotals($order_info['order_id']);

		$rows = array();
		$total_sum = 0;

		// Prepare rows
		foreach ($products as $product) {
			$price = round($product['price'], 2);
			$cnt = (float)$product['quantity'];
			$cost = round($product['total'], 2);
			
			$rows[] = array(
				'type'      => 0, // 0 - product, 1 - service
				'name'      => $product['name'],
				'cnt'       => $cnt * 1000,
				'price'     => $price * 100,
				'cost'      => $cost * 100,
				'tax_group' => (int)$this->config->get('module_vchasno_tax_group')
			);
		}

		// Handle shipping and other totals
		foreach ($totals as $total) {
			if ($total['code'] == 'shipping' && (float)$total['value'] > 0) {
				$cost = round($total['value'], 2);
				$rows[] = array(
					'type'      => 1, // service
					'name'      => $total['title'],
					'cnt'       => 1000,
					'price'     => $cost * 100,
					'cost'      => $cost * 100,
					'tax_group' => (int)$this->config->get('module_vchasno_tax_group')
				);
			}
		}

		// Calculate total from rows to be sure
		$calculated_total = 0;
		foreach ($rows as $row) {
			$calculated_total += $row['cost'];
		}

		// Vchasno payment types: 0 - cash, 1 - card, 2 - bank, etc.
		// mapping based on common Vchasno API: 
		// 0 - Готівка
		// 1 - Картка
		// 2 - Передплата
		// 3 - Післяплата (накладений платіж)
		// 4 - Бонуси
		// 5 - Сертифікат
		
		$pay_type = 1; // Default to card
		if ($payment_form == 'Готівка') {
			$pay_type = 0;
		} elseif ($payment_form == 'Безготівка') {
			$pay_type = 1; // Often mapped to card/online in small shops
		}

		$receipt = array(
			'cashier' => $this->config->get('module_vchasno_cashier'),
			'receipt' => array(
				'type' => 0, // 0 - Sell, 1 - Return, 2 - Service output, 3 - Service input
				'rows' => $rows,
				'pays' => array(
					array(
						'type' => $pay_type,
						'sum'  => $calculated_total
					)
				)
			)
		);

		// Customer phone
		if ($this->config->get('module_vchasno_send_phone') && !empty($order_info['telephone'])) {
			$receipt['receipt']['customer_phone'] = preg_replace('/[^0-9]/', '', $order_info['telephone']);
		}

		// Send receipt to customer
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
			if (!empty($result['receipt_id'])) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "vchasno_receipt` SET 
					order_id = '" . (int)$order_info['order_id'] . "', 
					vchasno_receipt_id = '" . $this->db->escape($result['receipt_id']) . "', 
					vchasno_receipt_url = '" . $this->db->escape($result['visual_url']) . "', 
					date_added = NOW()");
				
				// Add order history comment
				$this->model_checkout_order->addOrderHistory($order_info['order_id'], $order_info['order_status_id'], 'Вчасно.Каса: Чек створено. URL: ' . $result['visual_url'], false);
			}
		} else {
			$this->log->write('Vchasno Error: ' . $response . ' (HTTP ' . $http_code . ')');
		}
	}
}
