<?php
class ModelExtensionTotalProductBundlesTotal2 extends Model {
 
    private $languagePath = 'extension/total/productbundlestotal';
    	
	protected function bundlesWithQuantity() {
		$this->load->model('catalog/product');
		
		$CustomBundles = $this->config->get('productbundles_custom');
		$showingBundles = array();
		
		if (isset($CustomBundles)) {
			foreach ($CustomBundles as $key => $CurrentBundle) {
				foreach ($CurrentBundle['products'] as $result) {
					$product_info = $this->model_catalog_product->getProduct($result);
					
					if (($product_info && $product_info['quantity']<=0) || empty($product_info)) {
						unset($showingBundles[$key]);
						break;
					} else {
						$showingBundles[$key] = $CurrentBundle;
					}
				}
			}
		}
	
		return $showingBundles;	
	}

	public function getTotal($totals) {
		
		$total = &$totals['total'];
		$taxes = &$totals['taxes'];
		$total_data = &$totals['totals'];
		
		$cartProducts = $this->cart->getProducts();
		$cartProductsFlat = array();
		$cartProductsQuantities = array();
		$taxClasses = array();
		$matchingBundles = array();
		foreach ($cartProducts as $product) {
			$cartProductsFlat[] = $product['product_id'];
			if (empty($cartProductsQuantities[$product['product_id']])) {
				$cartProductsQuantities[$product['product_id']] = $product['quantity'];
			} else {
				$cartProductsQuantities[$product['product_id']] += $product['quantity'];
			}
			
			$taxClasses[$product['product_id']] = $product['tax_class_id'];
		}
		
		$bndlSettings = $this->bundlesWithQuantity();
		$bundles = isset($bndlSettings) ? $bndlSettings : array();
		usort($bundles, array($this, 'cmp'));
		
		$setting = $this->config->get('productbundles');
		$discountsApply = (isset($setting['MultipleBundles']) && ($setting['MultipleBundles']=='yes')) ? true : false;
		
		if (isset($bundles)) {
			foreach ($bundles as $bundle) {
				if (array_diff($bundle['products'], $cartProductsFlat) === array()) {
					$bundleQuantities = array();
					foreach($bundle['products'] as $product_id) {
						if (empty($bundleQuantities[$product_id])) {
							$bundleQuantities[$product_id] = 1;
						} else {
							$bundleQuantities[$product_id]++;
						}
					}
					
					for(;;) {
						foreach($bundleQuantities as $product_id=>$quantity) {
							if (!isset($cartProductsQuantities[$product_id]) || ($quantity > $cartProductsQuantities[$product_id])) {
								continue 3;
							}
						}
						
						foreach($bundleQuantities as $product_id=>$quantity) {
							$cartProductsQuantities[$product_id] -= $quantity;
						}
						
						if (!array_key_exists($bundle['id'], $matchingBundles)) {
							$matchingBundles[$bundle['id']] = array();
							$matchingBundles[$bundle['id']][] = $bundle;
						} else if ($discountsApply) {
							$matchingBundles[$bundle['id']][] = $bundle;
						}
					}
				}
			}
			
			$this->load->model('catalog/product');
			
			if (!empty($matchingBundles)) {
				$this->language->load($this->languagePath);
				
				$grandTotal = 0;
				foreach ($matchingBundles as $bundle) {
					$taxClassesUnique = array();
					foreach ($bundle as $bndl) {
						foreach ($bndl['products'] as $product) {
							$taxClassesUnique[] = $taxClasses[$product];
						}
					}	
					$taxClassesUnique = array_unique($taxClassesUnique);
					foreach($bundle as $instance) {
						if (isset($setting['DiscountTaxation']) && $setting['DiscountTaxation']=='yes') {
							foreach ($taxClassesUnique as $taxClassId) {
								$tax_rates = $this->tax->getRates((float)$instance['voucherprice'], $taxClassId);
								foreach ($tax_rates as $tax_rate) {
									if ($tax_rate['type'] == 'P') {
										$taxes[$tax_rate['tax_rate_id']] -= $tax_rate['amount'];
									}
								}
							}
						}
						$grandTotal += (float)$instance['voucherprice'];
					}
				}
				
				$total_data[] = array(
					'code'       => 'productbundlestotal2',
					'title'      => $this->language->get('entry_title'),
					'text'       => $this->currency->format(-$grandTotal, $this->config->get('config_currency')),
					'value'      => -$grandTotal,
					'sort_order' => $this->config->get('productbundlestotal2_sort_order')
				);
		
				$total -= (float)$grandTotal;
				if ($total < 0) {
					$total = 0;
				}
			}
		}
		
	}
	
	private function cmp($a, $b) {
		if ($a == $b) {
             return 0;
		}
		
		return (count($a['products']) > count($b['products'])) ? -1 : 1;
	}	
}
?>