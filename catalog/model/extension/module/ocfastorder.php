<?php
class ModelExtensionModuleOcfastorder extends Model {

	public function addOrder($order_id, $data) {
		if (isset($data['products'])) {
			foreach ($data['products'] as $product) {
				$query = $this->db->query("INSERT INTO " . DB_PREFIX . "newfastorder_product SET
					order_id = '" . (int)$order_id . "',
					product_id = '" . (int)$product['product_id'] . "',
					product_name = '" . $this->db->escape($product['name'])  . "',
					product_image = '" . $this->db->escape($product['product_image']) . "',
					model = '" . $this->db->escape($product['model']) . "',
					quantity = '" . (int)$product['quantity'] . "',
					total = '" . (float)$product['total'] . "',
					currency_code 		= '" . $this->db->escape($product['currency_code']) . "',
					currency_value 		= '" . $this->db->escape($product['currency_value']) . "',
					price = '" . (float)$product['price'] . "'
				");

				$order_product_id = $this->db->getLastId();

				if(!empty($product['option'])){
					foreach($product['option'] as $product_option){
						$this->db->query("INSERT INTO " . DB_PREFIX . "newfastorder_product_option SET
							order_id 				= '". (int)$order_id."',
							order_product_id 		= '". (int)$product['product_id'] ."',
							product_option_id 		= '". (int)$product_option['product_option_id'] . "',
							product_option_value_id = '". (int)$product_option['product_option_value_id'] . "',
							type 					= '". $this->db->escape($product_option['type']) ."',
							name 					= '". $this->db->escape($product_option['name']) ."',
							`value` 				= '". $this->db->escape($product_option['value']) . "'
						");
					}
				}
			}
		}

		$query = $this->db->query("INSERT INTO " . DB_PREFIX . "newfastorder SET
			name 					= '" . $this->db->escape($data['name_fastorder'])  . "',
			email_buyer 		= '" . $this->db->escape($data['email_buyer'])  . "',
			newfastorder_url 	= '" . $this->db->escape($data['url_site'])  . "',
			comment_buyer 		= '" . $this->db->escape($data['comment_buyer'])  . "',
			telephone 			= '" . $this->db->escape($data['phone']) . "',
			total = '" . $this->db->escape($this->currency->format($data['total_fast'], $this->session->data['currency'])). "',
			order_id 			= '" . (int)$order_id . "',
			date_added 			= NOW(),
			date_modified 		= NOW(),
			status_id 			= '0',
			comment 				= ''");
	}
}
?>
