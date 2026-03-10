<?php  
class ControllerExtensionModuleProductbundles extends Controller {
	private $moduleName;
    private $callModel;
    private $modulePath;
    private $moduleModel;
    private $data = array();
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        // Config Loader
        $this->config->load('isenselabs/productbundles');
        
        /* Fill Main Variables - Begin */
        $this->moduleName       = $this->config->get('productbundles_name');
        $this->callModel        = $this->config->get('productbundles_model');
        $this->modulePath       = $this->config->get('productbundles_path');
        /* Fill Main Variables - End */
        
        /* Module-specific declarations - Begin */
        $this->load->language($this->modulePath);
       
        // Multi-Store
        $this->load->model('setting/store');
        // Product
        $this->load->model('catalog/product');
        // Settings
        $this->load->model('setting/setting');
        // Images
        $this->load->model('tool/image');

        // Variables
        $this->data['moduleName'] 		= $this->moduleName;
        $this->data['modulePath']       = $this->modulePath;
        /* Module-specific declarations - End */
    }
    	
	public function index($setting) {
		$this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
		$this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');	
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');		
		
		$this->data['moduleData']                   = $this->config->get($this->moduleName);
		
      	$this->data['heading_title']                = $this->language->get('heading_title');
      	$this->data['button_cart']					= $this->language->get('button_cart');
		$this->data['ProductBundles_BundlePrice'] 	= $this->language->get('ProductBundles_BundlePrice');
		$this->data['ProductBundles_YouSave'] 		= $this->language->get('ProductBundles_YouSave');
		$this->data['ProductBundles_AddBundleToCart'] = $this->language->get('ProductBundles_AddBundleToCart');
		$this->data['CloseButton']					= true;
		$this->data['language_id']					= $this->config->get('config_language_id');
		$this->data['cart_url']						= $this->url->link('checkout/cart');
		$this->data['CustomBundles']                = $this->bundlesWithQuantity();
		$this->data['ShowTheModule']                = false;
		$bundles								    = array();
		$picture_width							    = isset($this->data['moduleData']['WidgetWidth']) ? $this->data['moduleData']['WidgetWidth'] : '100';
		$picture_height							    = isset($this->data['moduleData']['WidgetHeight']) ? $this->data['moduleData']['WidgetHeight'] : '100';		
		
		// Bundles in product pages
		if ((isset($this->request->get['product_id'])) && isset($this->data['CustomBundles'])) {
			$productID = $this->request->get['product_id'];
			foreach ($this->data['CustomBundles'] as $CustomBundles) {
				if (isset($CustomBundles['productsShow'])) {
					if (in_array($productID, $CustomBundles['productsShow'])) {
						$bundles[] = $CustomBundles['id'];
						$this->data['ShowTheModule'] = true;
					}
				}
			}
		}
		
		// Bundles in category pages
		if ((isset($this->request->get['path'])) && isset($this->data['CustomBundles']) && (!isset($this->request->get['product_id']))) {
			$category = (explode("_", $this->request->get['path']));
			if (isset($category[1])) {
				$categoryID = end($category);
            } else {
				$categoryID = $this->request->get['path'];
            }
            
			foreach ($this->data['CustomBundles'] as $CustomBundles) {
				if (isset($CustomBundles['categoriesShow'])) {
					if (in_array($categoryID, $CustomBundles['categoriesShow'])) {
					    $bundles[] = $CustomBundles['id'];
					    $this->data['ShowTheModule'] = true;
					}
				}
			}
		}
		
		// Bundles in any other page
		if (($this->data['ShowTheModule'] == false) && isset($this->data['moduleData']['ShowRandomBundles']) && ($this->data['moduleData']['ShowRandomBundles'] == 'yes')) {
			if (sizeof($this->data['CustomBundles'])>0) {
				foreach ($this->data['CustomBundles'] as $CustomBundles) {
					$bundles[] = $CustomBundles['id'];
					$this->data['ShowTheModule'] = true;
				}
			}
		}
		
		$_LIMIT = (isset($this->data['moduleData']['WidgetLimit'])) ? $this->data['moduleData']['WidgetLimit'] : '2';
		
		// Show bundle
		if ($this->data['ShowTheModule'] == true) {			
			$n = 0;
			$this->data['Bundles'] = array();

			if (!empty($this->data['moduleData']['DisplayType']) && $this->data['moduleData']['DisplayType'] == 'random') {
				shuffle($bundles);
			}
			
			for ($n=0; $n < sizeof($bundles); $n++) {
				if ($n == $_LIMIT) break;
				
                $this->data['Bundles'][$n]['BundleNumber']      = $this->data['CustomBundles'][$bundles[$n]]['id'];
				$CurrentBundle							        = $this->data['CustomBundles'][$bundles[$n]];
				$i										        = 0;
				$TotalPrice								        = 0;
				$TotalPriceNoTaxes 						        = 0;
				$this->data['Bundles'][$n]['BundleProducts']    = "";
				$this->data['Bundles'][$n]['productOptions']    = false;
				$products_count                                 = (array_count_values($CurrentBundle['products']));
				$added_products                                 = array();
                
				foreach ($CurrentBundle['products'] as $result) {						
					$product_info = $this->model_catalog_product->getProduct($result);
	
					if ($i!=0) {
						$this->data['Bundles'][$n]['BundleProducts'] .= "_";
					}
					$this->data['Bundles'][$n]['BundleProducts'] .= $result;
					
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
					} else {
						$image = false;
					}
					  
					if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
					} else {
						$price = false;
					}
							
					if ((float)$product_info['special']) {
						$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
						$TotalPrice+=$this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
						$TotalPriceNoTaxes += $product_info['special'];						
					} else {
						$TotalPrice+=$this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
						$TotalPriceNoTaxes += $product_info['price'];
						$special = false;
					}

					$product_options = $this->model_catalog_product->getProductOptions($product_info['product_id']);
					if (!empty($product_options)) $this->data['Bundles'][$n]['productOptions'] = true;
						
					if (!in_array($result, $added_products))  {
						$this->data['Bundles'][$n]['products'][] = array(
							'product_id' => $product_info['product_id'],
							'quantity'	 => $products_count[$product_info['product_id']],
							'thumb'   	 => $image,
							'name'    	 => $product_info['name'],
							'price'   	 => $price,
							'special' 	 => $special,
							'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
						);
						$added_products[] = $result;
					}
					
					$this->data['Bundles'][$n]['url'] = $this->url->link($this->modulePath.'/view', 'bundle_id=' . $CurrentBundle['id']);
					
					$i++;
				}
									
				if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation']=='yes') {
					foreach ($CurrentBundle['products'] as $result) {
						$product_info = $this->model_catalog_product->getProduct($result);
						if ((float)$product_info['special']) {
							$ratio = $TotalPriceNoTaxes / $product_info['special'];
						} else {
							$ratio = $TotalPriceNoTaxes / $product_info['price'];
						}
						
						$tax_rates = $this->tax->getRates((float)$CurrentBundle['voucherprice'] / $ratio, $product_info['tax_class_id']);
						foreach ($tax_rates as $tax_rate) {
							if ($tax_rate['type'] == 'P') {
								$TotalPrice -= $tax_rate['amount'];
							}
						}
					}
				}
								
				$VoucherPrice 								    = $CurrentBundle['voucherprice'];
				$FinalPrice 								    = $TotalPrice-$VoucherPrice;
				$this->data['Bundles'][$n]['VoucherData'] 		= $VoucherPrice;
				$this->data['Bundles'][$n]['TotalPrice']        = $this->currency->format($TotalPrice, $this->config->get('config_currency'));
				$this->data['Bundles'][$n]['VoucherPrice'] 		= $this->currency->format($VoucherPrice, $this->config->get('config_currency'));
				$this->data['Bundles'][$n]['FinalPrice']        = $this->currency->format($FinalPrice, $this->config->get('config_currency'));
				
                if (isset($CurrentBundle['name'][$this->config->get('config_language_id')]) && (!empty($CurrentBundle['name'][$this->config->get('config_language_id')]))) {
					$this->data['Bundles'][$n]['BundleName']    = $CurrentBundle['name'][$this->config->get('config_language_id')];
				} else {
					$this->data['Bundles'][$n]['BundleName']    = $this->language->get('view_bundle');
				}
			}	
		}

		if(version_compare(VERSION, '2.2.0.0', "<")) {
		    if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/'.$this->moduleName.'.tpl')) {
				$this->document->addStyle('catalog/view/theme/'.$this->getConfigTemplate() . '/stylesheet/'.$this->moduleName.'.css');
				return $this->load->view($this->getConfigTemplate() . '/template/'.$this->modulePath.'/'.$this->moduleName.'.tpl', $this->data);
			} else {
				$this->document->addStyle('catalog/view/theme/default/stylesheet/'.$this->moduleName.'.css');
				return $this->load->view('default/template/'.$this->modulePath.'/'.$this->moduleName.'.tpl', $this->data);
			}
		} else {
            $this->document->addStyle('catalog/view/theme/default/stylesheet/'.$this->moduleName.'.css');
            return $this->load->view($this->modulePath.'/'.$this->moduleName.'.tpl', $this->data);
        }
	}
	
    public function listing() {
		$this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
		$this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');		
		
		$this->data['moduleData']                       = $this->config->get('productbundles');
		
		$this->document->setTitle($this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')]);
		$this->document->setDescription($this->data['moduleData']['MetaDescription'][$this->config->get('config_language_id')]);
		$this->document->setKeywords($this->data['moduleData']['MetaKeywords'][$this->config->get('config_language_id')]);

		$this->data['heading_title'] = (!empty($this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')]) ? $this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')] : $this->language->get('listing_heading_title'));
		
        $this->data['breadcrumbs']      = array();
		$this->data['breadcrumbs'][]    = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home'),			
            'separator' => false
        );
        $this->data['breadcrumbs'][]    = array(
            'text'      => $this->language->get('text_breadcrumb'),
            'href'      => $this->url->link($this->modulePath.'/listing'),
            'separator' => $this->language->get('text_separator')
        );
        
		$this->data['ProductBundles_BundlePrice']       = $this->language->get('ProductBundles_BundlePrice');
		$this->data['ProductBundles_YouSave']           = $this->language->get('ProductBundles_YouSave');
		$this->data['ProductBundles_AddBundleToCart']   = $this->language->get('ProductBundles_AddBundleToCart');
		$this->data['cart_url']                         = $this->url->link('checkout/cart');
		$bundleSettings                                 = $this->bundlesWithQuantity();
		$this->data['CustomBundles']                    = isset($bundleSettings) ? $bundleSettings : array();
		$this->data['CloseButton']                      = true;
		$picture_width								    = isset($this->data['moduleData']['ListingPictureWidth']) ? $this->data['moduleData']['ListingPictureWidth'] : '100';
		$picture_height								    = isset($this->data['moduleData']['ListingPictureHeight']) ? $this->data['moduleData']['ListingPictureHeight'] : '100';
		$Bundles									    = array();
		$n											    = 0;
		$limit										    = isset($this->data['moduleData']['ListingLimit']) ? $this->data['moduleData']['ListingLimit'] : '10';

		foreach ($this->data['CustomBundles'] as $CustomBundle) {
			$Bundles[$n] = $CustomBundle;
			$n++;
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else { 
			$page = 1;
		}
		
		$start = ($page - 1) * $limit;
		$this->data['Bundles'] = array();
		for ($n=$start; $n < ($limit+$start); $n++) {
			if (!isset($Bundles[$n]['id'])) break;
            
			$this->data['Bundles'][$n]['BundleNumber']	    = $Bundles[$n]['id'];
			$CurrentBundle							        = $Bundles[$n];
			$i										        = 0;
			$TotalPrice								        = 0;
			$TotalPriceNoTaxes 						        = 0;
			$this->data['Bundles'][$n]['BundleProducts'] 	= "";
			$this->data['Bundles'][$n]['productOptions']    = false;
			$products_count                                 = (array_count_values($CurrentBundle['products']));
			$added_products                                 = array();
            
			foreach ($CurrentBundle['products'] as $result) {
				$product_info = $this->model_catalog_product->getProduct($result);

				if ($i!=0) {
					$this->data['Bundles'][$n]['BundleProducts'] .= "_";
				}
				$this->data['Bundles'][$n]['BundleProducts'] .= $result;
				
				if ($product_info['image']) {
					$image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
				} else {
					$image = false;
				}
				  
				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
				} else {
					$price = false;
				}
						
				if ((float)$product_info['special']) {
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
					$TotalPrice+=$this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
					$TotalPriceNoTaxes += $product_info['special'];						
				} else {
					$TotalPrice+=$this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
					$TotalPriceNoTaxes += $product_info['price'];
					$special = false;
				}
					
				$product_options = $this->model_catalog_product->getProductOptions($product_info['product_id']);
				if (!empty($product_options)) $this->data['Bundles'][$n]['productOptions'] = true;
					
				if (!in_array($result, $added_products))  {
					$this->data['Bundles'][$n]['products'][] = array(
						'product_id' => $product_info['product_id'],
						'quantity'	 => $products_count[$product_info['product_id']],
						'thumb'   	 => $image,
						'name'    	 => $product_info['name'],
						'price'   	 => $price,
						'special' 	 => $special,
						'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
					);
					$added_products[] = $result;
				}
				
				$this->data['Bundles'][$n]['url'] = $this->url->link($this->modulePath.'/view', 'bundle_id=' . $Bundles[$n]['id']);
				
				$i++;
			}
			
			if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation']=='yes') {
				foreach ($CurrentBundle['products'] as $result) {
					$product_info = $this->model_catalog_product->getProduct($result);
					if ((float)$product_info['special']) {
						$ratio = $TotalPriceNoTaxes / $product_info['special'];
					} else {
						$ratio = $TotalPriceNoTaxes / $product_info['price'];
					}
					
					$tax_rates = $this->tax->getRates((float)$CurrentBundle['voucherprice'] / $ratio, $product_info['tax_class_id']);
					foreach ($tax_rates as $tax_rate) {
						if ($tax_rate['type'] == 'P') {
							$TotalPrice -= $tax_rate['amount'];
						}
					}
				}
			}	
					
			$VoucherPrice                                   = $CurrentBundle['voucherprice'];
			$FinalPrice                                     = $TotalPrice-$VoucherPrice;
			$this->data['Bundles'][$n]['VoucherData'] 		= $VoucherPrice;
			$this->data['Bundles'][$n]['TotalPrice']        = $this->currency->format($TotalPrice, $this->config->get('config_currency'));
			$this->data['Bundles'][$n]['VoucherPrice'] 		= $this->currency->format($VoucherPrice, $this->config->get('config_currency'));
			$this->data['Bundles'][$n]['FinalPrice']        = $this->currency->format($FinalPrice, $this->config->get('config_currency'));
			
            if (isset($CurrentBundle['name'][$this->config->get('config_language_id')]) && (!empty($CurrentBundle['name'][$this->config->get('config_language_id')]))) {
				$this->data['Bundles'][$n]['BundleName']    = $CurrentBundle['name'][$this->config->get('config_language_id')];
			} else {
				$this->data['Bundles'][$n]['BundleName']    = $this->language->get('view_bundle');
			}
		}
			
		$url = '';
		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}	
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}
		
		$total								                = count($this->data['CustomBundles']);
		$pagination 						                = new Pagination();
		$pagination->total 					                = $total;
		$pagination->page 					                = $page;
		$pagination->limit 					                = $limit;
		$pagination->url 					                = $this->url->link($this->modulePath.'/listing', $url.'&page={page}');
		$this->data['pagination']					        = $pagination->render();
		$this->data['results']					            = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));
			
		if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_listing.tpl')) {
			$this->document->addStyle('catalog/view/theme/'. $this->getConfigTemplate() . '/stylesheet/productbundles.css');
		} else {
			$this->document->addStyle('catalog/view/theme/default/stylesheet/productbundles.css');
		}
		
		$this->data['column_left']				            = $this->load->controller('common/column_left');
		$this->data['column_right']				            = $this->load->controller('common/column_right');
		$this->data['content_top']				            = $this->load->controller('common/content_top');
		$this->data['content_bottom']				        = $this->load->controller('common/content_bottom');
		$this->data['footer']						        = $this->load->controller('common/footer');
		$this->data['header']						        = $this->load->controller('common/header');

		if(version_compare(VERSION, '2.2.0.0', "<")) {
		    if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_listing.tpl')) {
				$this->response->setOutput($this->load->view($this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_listing.tpl', $this->data));
			} else {
				$this->response->setOutput($this->load->view('default/template/'.$this->modulePath.'/productbundles_listing.tpl', $this->data));
			}
		} else {
		      $this->response->setOutput($this->load->view($this->modulePath.'/productbundles_listing', $this->data));
		 }

	}
    
    public function view() {
		$this->document->addScript('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.pack.js');
		$this->document->addStyle('catalog/view/javascript/'.$this->moduleName.'/fancybox/jquery.fancybox.css');
		
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');		
		
		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		
		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_breadcrumb'),
			'href' => $this->url->link($this->modulePath.'/listing')
		);
		
		$this->data['moduleData']						= $this->config->get($this->moduleName);
		
      	$this->data['heading_title']					= $this->language->get('heading_title');
      	$this->data['button_cart']					    = $this->language->get('button_cart');
		$this->data['ProductBundles_BundlePrice'] 	    = $this->language->get('ProductBundles_BundlePrice');
		$this->data['ProductBundles_YouSave'] 		    = $this->language->get('ProductBundles_YouSave');
		$this->data['ProductBundles_AddBundleToCart']   = $this->language->get('ProductBundles_AddBundleToCart');
		$this->data['CustomBundles']					= $this->bundlesWithQuantity();
		$picture_width							        = isset($this->data['moduleData']['ViewWidth']) ? $this->data['moduleData']['ViewWidth'] : '100';
		$picture_height							        = isset($this->data['moduleData']['ViewHeight']) ? $this->data['moduleData']['ViewHeight'] : '100';		
		$this->data['CloseButton']					    = true;
		$this->data['language_id']					    = $this->config->get('config_language_id');
		$this->data['cart_url']						    = $this->url->link('checkout/cart');
		$bundleNumber                                   = (isset($this->request->get['bundle_id']) && !empty($this->request->get['bundle_id'])) ? $this->request->get['bundle_id'] : 0;
		$bundleData                                     = array(); 
		
		if (isset($this->data['CustomBundles'][$bundleNumber]) && !empty($this->data['CustomBundles'][$bundleNumber])) {
			$bundleData = $this->data['CustomBundles'][$bundleNumber];
		}
		
		if (!empty($bundleData)) {
			if (isset($bundleData['name'][$this->config->get('config_language_id')]) && (!empty($bundleData['name'][$this->config->get('config_language_id')]))) {
				$this->data['heading_title'] = $bundleData['name'][$this->config->get('config_language_id')];
			} else {
				$this->data['heading_title'] = $this->language->get('view_bundle');
			}
			
			$this->document->setTitle($this->data['moduleData']['PageTitle'][$this->config->get('config_language_id')]);

			$this->data['breadcrumbs'][] = array(
				'text' => $this->data['heading_title'],
				'href' => $this->url->link($this->modulePath.'/view', 'bundle_id='.$bundleNumber)
			);

			$this->data['Bundle']							= array();
			$this->data['Bundle']['BundleNumber']			= $bundleNumber;
			$TotalPrice								        = 0;
			$TotalPriceNoTaxes 						        = 0;
			$i										        = 0;
			$this->data['Bundle']['BundleProducts'] 		= "";
			$this->data['Bundle']['productOptions'] 		= false;
			$products_count                                 = (array_count_values($bundleData['products']));
			$added_products                                 = array();
            
			foreach ($bundleData['products'] as $result) {
				$product_info = $this->model_catalog_product->getProduct($result);

				if ($i!=0) {
					$this->data['Bundle']['BundleProducts'] .= "_";
				}
				$this->data['Bundle']['BundleProducts'] .= $result;
				
				if ($product_info['image']) {
					$image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
				} else {
					$image = false;
				}
				  
				if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
				} else {
					$price = false;
				}
						
				if ((float)$product_info['special']) {
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
					$TotalPrice+=$this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));	
					$TotalPriceNoTaxes += $product_info['special'];						
				} else {
					$TotalPrice+=$this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
					$TotalPriceNoTaxes += $product_info['price'];
					$special = false;
				}
					
				$product_options = $this->model_catalog_product->getProductOptions($product_info['product_id']);
				if (!empty($product_options)) $this->data['Bundle']['productOptions'] = true;
					
				if (!in_array($result, $added_products))  {
					$this->data['Bundle']['products'][] = array(
						'product_id' => $product_info['product_id'],
						'quantity'	 => $products_count[$product_info['product_id']],
						'thumb'   	 => $image,
						'name'    	 => $product_info['name'],
						'price'   	 => $price,
						'special' 	 => $special,
						'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id'])
					);
					$added_products[] = $result;
				}
				$this->data['Bundle']['url'] = $this->url->link($this->modulePath.'/view', 'bundle_id=' . $bundleNumber);
				$i++;
			}
			
			if (isset($this->data['moduleData']['DiscountTaxation']) && $this->data['moduleData']['DiscountTaxation']=='yes') {
				foreach ($bundleData['products'] as $result) {
					$product_info = $this->model_catalog_product->getProduct($result);
					if ((float)$product_info['special']) {
						$ratio = $TotalPriceNoTaxes / $product_info['special'];
					} else {
						$ratio = $TotalPriceNoTaxes / $product_info['price'];
					}
					
					$tax_rates = $this->tax->getRates((float)$bundleData['voucherprice'] / $ratio, $product_info['tax_class_id']);
					foreach ($tax_rates as $tax_rate) {
						if ($tax_rate['type'] == 'P') {
							$TotalPrice -= $tax_rate['amount'];
						}
					}
				}
			}	
						
			$VoucherPrice                               = $bundleData['voucherprice'];
			$FinalPrice                                 = $TotalPrice-$VoucherPrice;
			$this->data['Bundle']['VoucherData'] 		= $VoucherPrice;
			$this->data['Bundle']['TotalPrice']         = $this->currency->format($TotalPrice, $this->config->get('config_currency'));
			$this->data['Bundle']['VoucherPrice'] 		= $this->currency->format($VoucherPrice, $this->config->get('config_currency'));
			$this->data['Bundle']['FinalPrice']         = $this->currency->format($FinalPrice, $this->config->get('config_currency'));
			
            if (isset($bundleData['name'][$this->config->get('config_language_id')]) && (!empty($CurrentBundle['name'][$this->config->get('config_language_id')]))) {
				$this->data['Bundle']['BundleName']     = $CurrentBundle['name'][$this->config->get('config_language_id')];
			} else {
				$this->data['Bundle']['BundleName']     = '';
			}
	
			if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_listing.tpl')) {
				$this->document->addStyle('catalog/view/theme/'.$this->getConfigTemplate() . '/stylesheet/productbundles.css');
			} else {
				$this->document->addStyle('catalog/view/theme/default/stylesheet/productbundles.css');
			}
	
			$this->data['column_left']				    = $this->load->controller('common/column_left');
			$this->data['column_right']				    = $this->load->controller('common/column_right');
			$this->data['content_top']				    = $this->load->controller('common/content_top');
			$this->data['content_bottom']				= $this->load->controller('common/content_bottom');
			$this->data['footer']						= $this->load->controller('common/footer');
			$this->data['header']						= $this->load->controller('common/header');

			if(version_compare(VERSION, '2.2.0.0', "<")) {
			    if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_view.tpl')) {
					$this->response->setOutput($this->load->view($this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_view.tpl', $this->data));
				} else {
					$this->response->setOutput($this->load->view('default/template/'.$this->modulePath.'/productbundles_view.tpl', $this->data));
				}
			} else {
			      $this->response->setOutput($this->load->view($this->modulePath.'/productbundles_view', $this->data));
			}
		} else {
			$this->data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link($this->modulePath.'/view', 'bundle_id=' . $bundleNumber)
			);

			$this->document->setTitle($this->language->get('text_error'));
        
			$this->data['heading_title']        = $this->language->get('text_error');
			$this->data['text_error']           = $this->language->get('text_error');
			$this->data['button_continue']      = $this->language->get('button_continue');
			$this->data['continue']             = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$this->data['column_left']          = $this->load->controller('common/column_left');
			$this->data['column_right']         = $this->load->controller('common/column_right');
			$this->data['content_top']          = $this->load->controller('common/content_top');
			$this->data['content_bottom']       = $this->load->controller('common/content_bottom');
			$this->data['footer']               = $this->load->controller('common/footer');
			$this->data['header']               = $this->load->controller('common/header');

			if(version_compare(VERSION, '2.2.0.0', "<")) {
			    if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/error/not_found.tpl')) {
					$this->response->setOutput($this->load->view($this->getConfigTemplate() . '/template/error/not_found.tpl', $this->data));
				} else {
					$this->response->setOutput($this->load->view('default/template/error/not_found.tpl', $this->data));
				}
			} else {
                $this->response->setOutput($this->load->view('error/not_found.tpl', $this->data));
			}
		}
	}
    
    public function bundleproductoptions() {		
		$this->data['moduleData']               = $this->config->get('productbundles');
		$this->data['text_select']              = $this->language->get('text_select');
		$this->data['text_option']              = $this->language->get('text_option');
		$this->data['text_option_heading']      = $this->language->get('text_option_heading');
		$this->data['heading_title']            = $this->language->get('heading_title');
      	$this->data['button_cart']              = $this->language->get('button_cart');
		$this->data['button_upload']            = $this->language->get('button_upload');
		$this->data['AdditionalFees']           = $this->language->get('AdditionalFees');
		$this->data['Continue']                 = $this->language->get('Continue');
		$this->data['CustomBundles']            = $this->bundlesWithQuantity(); //$this->config->get('productbundles_custom');
		$this->data['ShowPage']                 = false;
		$this->data['cart_url']                 = $this->url->link('checkout/cart');

		if (isset($this->request->get['bundle'])) {
			$this->data['BundleNumber']         = $this->request->get['bundle'];
			$this->data['ShowPage']             = true;
			$CurrentBundle						= $this->data['CustomBundles'][$this->data['BundleNumber']];
			$i									= 0;
			$TotalPrice 						= 0;
			$this->data['BundleProducts']       = "";
			$picture_width						= isset($this->data['moduleData']['WidgetWidth']) ? $this->data['moduleData']['WidgetWidth'] : '128';
			$picture_height						= isset($this->data['moduleData']['WidgetHeight']) ? $this->data['moduleData']['WidgetHeight'] : '128';
			
			foreach ($CurrentBundle['products'] as $result) {
				$product_info = $this->model_catalog_product->getProduct($result);

				if ($i!=0) {
					$this->data['BundleProducts'] .= "_";
				}
				$this->data['BundleProducts'] .= $result;
				
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], $picture_width, $picture_height);
					} else {
						$image = false;
					}
					  
					if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
					} else {
						$price = false;
					}
							
					if ((float)$product_info['special']) {
						$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
						$TotalPrice+=$this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax'));					
					} else {
						$TotalPrice+=$this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
						$special = false;
					}
					
					if ($this->config->get('config_review_status')) {
						$rating = (int)$product_info['rating'];
					} else {
						$rating = false;
					}
					
					$product_options = $this->model_catalog_product->getProductOptions($product_info['product_id']);
					$this->data['options'] = array();

					foreach ($this->model_catalog_product->getProductOptions($product_info['product_id']) as $option) { 
						if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') { 
							$option_value_data = array();
							foreach ($option['product_option_value'] as $option_value) {
								if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
									if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
										$option_price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->config->get('config_currency'));
									} else {
										$option_price = 0;
									}
														
									$option_value_data[] = array(
										'product_option_value_id' => $option_value['product_option_value_id'],
										'option_value_id'         => $option_value['option_value_id'],
										'name'                    => $option_value['name'],
										'image'                   => $this->model_tool_image->resize($option_value['image'], 50, 50),
										'price'                   => $option_price,
										'price_prefix'            => $option_value['price_prefix']
									);
								}
							}
		
							$this->data['options'][] = array(
								'product_option_id' => $option['product_option_id'],
								'option_id'         => $option['option_id'],
								'name'              => $option['name'],
								'type'              => $option['type'],
								'option_value'      => $option_value_data,
								'required'          => $option['required']
							);					
						} elseif ($option['type'] == 'text' || $option['type'] == 'textarea' || $option['type'] == 'file' || $option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
							$this->data['options'][] = array(
								'product_option_id' => $option['product_option_id'],
								'option_id'         => $option['option_id'],
								'name'              => $option['name'],
								'type'              => $option['type'],
								'option_value'      => $option['value'],
								'required'          => $option['required']
							);						
						}
					}

					$this->data['products'][] = array(
						'product_id' => $product_info['product_id'],
						'thumb'   	 => $image,
						'name'    	 => $product_info['name'],
						'price'   	 => $price,
						'special' 	 => $special,
						'rating'     => $rating,
						'reviews'    => sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']),
						'href'    	 => $this->url->link('product/product', 'product_id=' . $product_info['product_id']),
						'options'    => $this->data['options']
					);
					$i++;
			}
            
			$VoucherPrice 				        = $CurrentBundle['voucherprice'];
			$FinalPrice 				        = $TotalPrice-$VoucherPrice;
			$this->data['VoucherData']		    = $VoucherPrice;
			$this->data['TotalPrice']			= $this->currency->format($TotalPrice, $this->config->get('config_currency'));
			$this->data['VoucherPrice']		    = $this->currency->format($VoucherPrice, $this->config->get('config_currency'));
			$this->data['FinalPrice']			= $this->currency->format($FinalPrice, $this->config->get('config_currency'));
		
		}

		if(version_compare(VERSION, '2.2.0.0', "<")) {
		    if (file_exists(DIR_TEMPLATE . $this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_options.tpl')) {
				echo $this->load->view($this->getConfigTemplate() . '/template/'.$this->modulePath.'/productbundles_options.tpl', $this->data);
				exit;
			} else {
				echo $this->load->view('default/template/'.$this->modulePath.'/productbundles_options.tpl', $this->data);
				exit;
			}
		} else {
            echo $this->load->view($this->modulePath.'/productbundles_options.tpl', $this->data);
        }

	}
    
	public function bundletocartoptions() {
		if ((isset($this->request->post)) && (($this->request->post['products']) && isset($this->request->post['discount']))) {
		
            $products = explode("_", $this->request->post['products']); // Explode products
            
            if (isset($this->request->post['option'])) {  // Product Options
                $option = $this->request->post['option'];
            } else {
                $option = array();	
            }
        
            $json = array();
    
            foreach ($products as $key=>$p) { // Check for empty but required product options
                $product_options = $this->model_catalog_product->getProductOptions($p);
                    foreach ($product_options as $product_option) {
                        if ($product_option['required'] && empty($option[$key][$product_option['product_option_id']])) {
                            if (empty($json['error']['option'][$product_option['product_option_id']])) {
                                $json['error']['option'][$product_option['product_option_id']] = array();
                            }
                            $json['error']['option'][$product_option['product_option_id']][] = array(
                                'message' => sprintf($this->language->get('error_required'), $product_option['name']),
                                'key' 	 => $key 
                            );
                    }
                }
            }
            
            $config = $this->config->get('productbundles_custom');
	
			if (($config[$this->request->post['bundle']]['products'] == $products) && (isset($this->request->post['discount'])) && (isset($this->request->post['bundle']))) {
				
				if (!$json) {
					foreach ($products as $key=>$p) {
						$p_option = $p_option = !empty($option[$key]) ? $option[$key] : array();
						$this->cart->add($p, 1, $p_option, "");
					}
					$json['bundle_code'] = $this->request->post['bundle'];
					$json['success'] = "1";
				}
			} else {
				//echo "ERROR 1!";	
			}
		} else {
			//echo "ERROR 2!";	
		}
		
		$this->response->setOutput(json_encode($json));
	}
	
	public function bundletocart() {
		$json = array();

		if ((isset($this->request->post)) && (($this->request->post['products']) && isset($this->request->post['discount']))) {
			$products = explode("_", $this->request->post['products']);

			$config = $this->config->get('productbundles_custom');
			if ( ($config[$this->request->post['bundle']]['products'] == $products) && (isset($this->request->post['discount'])) && (isset($this->request->post['bundle'])) ) {
				
				if (!$json) {
					foreach ($products as $p) {
						$this->cart->add($p, 1);
					}
					$json['bundle_code'] = $this->request->post['bundle'];
					$json['success'] = 1;
				}	
			} else {
				$json['error'] = 'error';
			}
		} else {
			$json['error'] = 'error';	
		}
		
		$this->response->setOutput(json_encode($json));
	}
    
    /* Helper functions - Begin */
    
    protected function bundlesWithQuantity() {	
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
    
	protected function getConfigTemplate(){
		if(version_compare(VERSION, '2.2.0.0', '<')) {
			return $this->config->get('config_template');
		} else {
			return  $this->config->get($this->config->get('config_theme') . '_directory');
		}
	}
    
    /* Helper functions - End */
    
}
?>