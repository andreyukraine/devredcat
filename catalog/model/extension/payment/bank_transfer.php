<?php
class ModelExtensionPaymentBankTransfer extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/bank_transfer');

		$method_data = array();

			$method_data = array(
				'code'       => 'bank_transfer',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_bank_transfer_sort_order')
			);

		return $method_data;
	}
}
