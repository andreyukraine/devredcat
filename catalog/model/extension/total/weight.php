<?php
class ModelExtensionTotalWeight extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/weight');

		$weight = $this->cart->getWeight();

		if ($weight > 0) {
			$total['totals'][] = array(
				'code'       => 'weight',
				'title'      => $this->language->get('text_weight'),
				'value'      => $weight,
				'sort_order' => $this->config->get('total_weight_sort_order')
			);
		}
	}
}
