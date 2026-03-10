<?php
class ModelExtensionPaymentLiqPay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/liqpay');

		$method_data = array();

			$method_data = array(
				'code'       => 'liqpay',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_liqpay_sort_order')
			);

		return $method_data;
	}
}
