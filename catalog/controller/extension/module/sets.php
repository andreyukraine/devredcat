<?php

class ControllerExtensionModuleSets extends Controller {

    public function index() {

    }

    public function addSetToTotal() {

        if (!(isset($this->request->post['product_id']) && isset($this->request->post['iset'])))
            return;

        $product_id = $this->request->post['product_id'];
        $i = $this->request->post['iset'];

        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if (!$product_info)
            return;

        if (empty($product_info['sets']))
            return;

        $sets = json_decode($product_info['sets'], true);
        if (!isset($this->session->data['sets']))
            $this->session->data['sets'] = array();

        $this->session->data['sets'][] = $sets[$i];

        $json = array();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addProductToCart() {
        $json = array();
        $this->load->language('extension/module/set');
        
        if (isset($this->request->post) && is_array($this->request->post)) {

            foreach ($this->request->post as $product) {
                parse_str(html_entity_decode($product), $pr);

                $id = $pr['product_id'];
                $q = $pr['quantity'];

                $option = array();
                if (isset($pr['option']) && $pr['option'] !== 'no')
                    $option = $pr['option'];

                $this->cart->add($id, $q, $option);
            }
            
            $json = $this->getTotal23();
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function checkProductOption() {
        $this->load->language('extension/module/set');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int) $this->request->post['product_id'];
        } else {
            $product_id = 0;
        }

        $this->load->model('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            if (isset($this->request->post['option']) && $this->request->post['option'] == 'no')
                $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('checkout/cart'));
            else {
                if (isset($this->request->post['quantity']) && ((int) $this->request->post['quantity'] >= $product_info['minimum'])) {
                    $quantity = (int) $this->request->post['quantity'];
                } else {
                    $quantity = $product_info['minimum'] ? $product_info['minimum'] : 1;
                }

                if (isset($this->request->post['option'])) {
                    $option = array_filter($this->request->post['option']);
                } else {
                    $option = array();
                }

                $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

                foreach ($product_options as $product_option) {
                    if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                        $json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
                    }
                }

                if (isset($this->request->post['recurring_id'])) {
                    $recurring_id = $this->request->post['recurring_id'];
                } else {
                    $recurring_id = 0;
                }

                $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

                if ($recurrings) {
                    $recurring_ids = array();

                    foreach ($recurrings as $recurring) {
                        $recurring_ids[] = $recurring['recurring_id'];
                    }

                    if (!in_array($recurring_id, $recurring_ids)) {
                        $json['error']['recurring'] = $this->language->get('error_recurring_required');
                    }
                }

                if (!$json) {

                    $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('checkout/cart'));
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getMyPrOptions($id) {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $my_pr_options = array();
        $product_info = $this->model_catalog_product->getProduct($id);

        foreach ($this->model_catalog_product->getProductOptions($id) as $option) {
            $product_option_value_data = array();

            foreach ($option['product_option_value'] as $option_value) {
                if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
                    if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float) $option_value['price']) {

                       $cprice = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax')),$this->session->data['currency'],'',false);
                       $price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                   } else {
                    $price = 0;
                    $cprice = 0;
                }

                $product_option_value_data[$option_value['product_option_value_id']] = array(
                    'product_option_value_id' => $option_value['product_option_value_id'],
                    'option_value_id' => $option_value['option_value_id'],
                    'name' => $option_value['name'],
                    'image' => $this->model_tool_image->resize($option_value['image'], 50, 50),
                    'price' => $price,
                    'cprice' => $cprice,
                    'price_prefix' => $option_value['price_prefix']
                );
            }
        }

        $my_pr_options[$option['product_option_id']] = array(
            'product_option_value' => $product_option_value_data,
            'option_id' => $option['option_id'],
            'product_option_id' => $option['product_option_id'],
            'name' => $option['name'],
            'type' => $option['type'],
            'value' => $option['value'],
            'required' => $option['required']
        );
    }
    return $my_pr_options;
}

public function getSet($product_id) {

    $this->load->model('catalog/product');

    $product_info = $this->model_catalog_product->getProduct($product_id);
    $sets = false;

    if ($product_info)
        if (!empty($product_info['sets'])) {

            $sets = json_decode($product_info['sets'], true);

            foreach ($sets as $s_key => &$set) {

                $total = 0;
                $js_array = array();
                $set['product_id'] = $product_id;

                foreach ($set['products'] as $pkey => &$product) {
                    $id = $product['product_id'];
                        //проверка на кол-во и статус
                    $pr_info = $this->model_catalog_product->getProduct($id);
                    if (!$pr_info || (int) $pr_info['status'] == 0 || (int) $pr_info['quantity'] == 0 || (int) $product['quantity'] > (int) $pr_info['quantity']) {

                        unset($sets[$s_key]);
                        continue 2;
                    }

                    $my_pr_options = $this->getMyPrOptions($id);
                    $product['all_options'] = $my_pr_options;
                    $product['product_name'] = $pr_info['name'];
                    $product['href'] = $this->url->link('product/product', 'product_id=' . $id);

                    if ($pr_info['image']) {


                        $product['thumb'] = $this->model_tool_image->resize($pr_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
                    } else
                    $product['thumb'] = false;

                    if ((float) $pr_info['special'])
                        $price = $pr_info['special'];
                    else
                        $price = $pr_info['price'];


                    if (isset($product['use_option'])) {
                        if ($product['use_option'] == 'fixed' && isset($product['option'])) {
                            $options = $product['option'];
                            $options_array = $this->getSelectedOptions(array('options' => $options, 'pr_options' => $my_pr_options));
                            $data2['options'] = $options_array;
                            $data2['modal_id'] = $product_id . $s_key . $pkey;
                            $data2['modal_title'] = $product['product_name'];

                            if (floatval(VERSION) >= 2.2) {
                                $product['html_options_button'] = $this->load->view('extension/module/sets_fixed_options_button', $data2);
                                $product['html_options'] = $this->load->view('extension/module/sets_fixed_options', $data2);
                            } else {
                                $product['html_options_button'] = $this->load->view('default/template/extension/module/sets_fixed_options_button.tpl', $data2);
                                $product['html_options'] = $this->load->view('default/template/extension/module/sets_fixed_options.tpl', $data2);
                            }

                            $price += $this->getPriceOpts(array('options' => $options, 'pr_options' => $my_pr_options, 'product_info' => $pr_info));
                        } else if ($product['use_option'] == 'popup') {

                            $data3['options'] = $my_pr_options;
                            $data3['modal_id'] = $product_id . $s_key . $pkey;
                            $data3['modal_title'] = $product['product_name'];
                            $data3['text_select'] = $this->language->get('text_select');

                            if (floatval(VERSION) >= 2.2) {

                                $product['html_options_button'] = $this->load->view('extension/module/sets_popup_options_button', $data3);
                                $product['html_options'] = $this->load->view('extension/module/sets_popup_options', $data3);
                            } else {
                                $product['html_options_button'] = $this->load->view('default/template/extension/module/sets_popup_options_button.tpl', $data3);
                                $product['html_options'] = $this->load->view('default/template/extension/module/sets_popup_options.tpl', $data3);
                            }
                        }
                    }
                    $product['cprice'] = $this->currency->format($this->tax->calculate($price, $pr_info['tax_class_id'], $this->config->get('config_tax')),$this->session->data['currency'],'',false);

                    $product['price'] = $this->currency->format($this->tax->calculate($price, $pr_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    $product['price'] = preg_replace('/(-?[0-9]+[\s0-9.]*)/', '<span class="num">$1</span>', $product['price']);

                    $total += $this->tax->calculate($price, $pr_info['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'];
                }


                $discount = 0;
                if (isset($set['discount']))
                    if (substr($set['discount'], -1) == '%') {
                        $prec = (int) rtrim($set['discount'], '%');
                        $discount = $total / 100 * $prec;
                        $set['discount_prec'] = $set['discount'];
                    } else {
                        $discount = $set['discount'];
                           // if ($discount)
                            //    $set['discount_prec'] = intval($discount / ($total / 100)) . '%';
                    }

                    $set['int_economy'] = $discount;
                    if (floatval(VERSION) >= 2.2) {
                        $set['old_total'] = $this->currency->format($total, $this->session->data['currency']);
                        $set['economy'] = $this->currency->format($discount, $this->session->data['currency']);
                        $set['new_total'] = $this->currency->format($total - $discount, $this->session->data['currency']);
                    } else {
                        $set['old_total'] = $this->currency->format($total);
                        $set['economy'] = $this->currency->format($discount);
                        $set['new_total'] = $this->currency->format($total - $discount);
                    }
                    $set['economy'] = preg_replace('/(-?[0-9]+[\s0-9.]*)/', '<span class="num">$1</span>', $set['economy']);
                    $set['new_total'] = preg_replace('/(-?[0-9]+[\s0-9.]*)/', '<span class="num">$1</span>', $set['new_total']);



                    // $this->debug(json_encode($js_array));
                }
            }

            return $sets;
        }

        public function getSets() {

            if (!$this->config->get('module_sets_status'))
                return;

            $this->load->language('extension/module/set');

            if (isset($this->request->get['product_id']))
                $product_id = $this->request->get['product_id'];
            else
                $product_id = 0;

            $data['text_sets'] = $this->language->get('text_sets');
            $data['text_buy_sets'] = $this->language->get('text_buy_sets');
            $data['text_economy'] = $this->language->get('text_economy');
            $data['decimal_place'] = $this->currency->getDecimalPlace($this->session->data['currency']);
            $data['sets'] = $this->getSet($product_id);

            if ($data['sets']) {

                $this->document->addScript('catalog/view/javascript/sets/script.js');

                $this->document->addScript('catalog/view/javascript/slick/slick.min.js');
                $this->document->addStyle('catalog/view/javascript/slick/slick.css');
                $this->document->addStyle('catalog/view/javascript/slick/slick-theme.css');

                $this->document->addStyle('catalog/view/javascript/sets/style.css');
            }

            $data['show_disc_prec'] = $this->config->get('module_sets_show_disc_prec');
            $data['show_old_price'] = $this->config->get('module_sets_show_old_price');
            $data['show_qty'] = $this->config->get('module_sets_show_qty');

            $data['selector'] = $this->config->get('module_sets_selector');
            $data['position'] = $this->config->get('module_sets_position');

            if (floatval(VERSION) >= 2.2)
                return $this->load->view('extension/module/sets', $data);
            else
                return $this->load->view('default/template/extension/module/sets.tpl', $data);
        }

        public function debug($d) {
            echo "<pre>";
            var_dump($d);
            echo "</pre>";
            exit();
        }

        public function getSelectedOptions($arg) {
            $this->load->model('tool/image');
            $options = $arg['options'];
            $pr_options = $arg['pr_options'];

            $options_array = array();
        //$this->debug($options);
            foreach ($pr_options as $id_option => $option) {

                $value = '';
                $type = $option['type'];
                $name = $option['name'];


                if(isset($options[$id_option]))
                {
                    if ($option['type'] == 'select' ||
                        $option['type'] == 'radio' ||
                        $option['type'] == 'image' ||
                        $option['type'] == 'checkbox') {
                        if ($option['type'] == 'checkbox' && is_array($options[$id_option])) {
                            foreach ($options[$id_option] as $product_option_value_id) {
                                if(isset($option['product_option_value'][$product_option_value_id]))
                                    $value[$product_option_value_id] = $option['product_option_value'][$product_option_value_id]['name'];
                                else
                                    $value[$product_option_value_id] = '';
                            }
                        } else {
                            if ($option['type'] == 'image') {
                                if(isset($option['product_option_value'][$options[$id_option]]))
                                {
                                    $image = $option['product_option_value'][$options[$id_option]]['image'];
                                    $value[$options[$id_option]] = $option['product_option_value'][$options[$id_option]]['name'] . " <img src='$image'>";
                                }
                                else
                                    $value[$options[$id_option]] = '';
                            } else 
                            {
                                if(isset($option['product_option_value'][$options[$id_option]]))
                                    $value[$options[$id_option]] = $option['product_option_value'][$options[$id_option]]['name'];
                                else
                                    $value[$options[$id_option]] = '';
                            }
                        }
                    } else
                    $value = isset($options[$id_option]) ? $options[$id_option] : '';
                }


                $options_array[$id_option]['type'] = $type;
                $options_array[$id_option]['value'] = $value;
                $options_array[$id_option]['name'] = $name;
            }
        //$this->debug($options_array);

            return $options_array;
        }

        public function getPriceOpts($arg) {

            $options = $arg['options'];
            $my_pr_options = $arg['pr_options'];
            $product_info = $arg['product_info'];
            $total = 0;



            foreach ($options as $key => $option) {
                if ($my_pr_options[$key]['type'] == 'select' ||
                    $my_pr_options[$key]['type'] == 'radio' ||
                    $my_pr_options[$key]['type'] == 'image' ||
                    $my_pr_options[$key]['type'] == 'checkbox') {
                    if ($my_pr_options[$key]['type'] == 'checkbox' && is_array($option)) {
                        foreach ($option as $product_option_value_id) {
                            if(isset($my_pr_options[$key]['product_option_value'][$product_option_value_id]))
                            {
                                $price = $my_pr_options[$key]['product_option_value'][$product_option_value_id]['cprice'];
                                $pre = $my_pr_options[$key]['product_option_value'][$product_option_value_id]['price_prefix'];
                                $total = $this->help_opt($pre, $total, $price);
                                if ($pre == '=')
                                    break 2;
                            }
                        }
                    } else {
                        if(isset( $my_pr_options[$key]['product_option_value'][$option]))
                        {
                            $price = $my_pr_options[$key]['product_option_value'][$option]['cprice'];
                            $pre = $my_pr_options[$key]['product_option_value'][$option]['price_prefix'];
                            $total = $this->help_opt($pre, $total, $price);
                            if ($pre == '=')
                                break;
                        }
                    }
                }
            }

            return $total;
        }

        public function getTotal23() {
            $this->load->language('checkout/cart');
            $this->load->model('setting/extension');

            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;
            $json=array();


        // Because __call can not keep var references so we put them into an array. 			
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );

        // Display prices
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $sort_order = array();

                $results = $this->model_setting_extension->getExtensions('total');

                foreach ($results as $key => $value) {
                    $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
                }

                array_multisort($sort_order, SORT_ASC, $results);

                foreach ($results as $result) {
                    if ($this->config->get('total_' . $result['code'] . '_status')) {
                        $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                    }
                }

                $sort_order = array();

                foreach ($totals as $key => $value) {
                    $sort_order[$key] = $value['sort_order'];
                }

                array_multisort($sort_order, SORT_ASC, $totals);
            }
            $json['success'] = true;
            $json['total']=sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));

            return $json;
        }

        public function help_opt($pre, $total, $price) {
            if ($pre == '-')
                $total -= $price;
            else if ($pre == '+')
                $total += $price;
            else if ($pre == '=')
                $total = $price;
            else if ($pre == '*')
                $total *= $price;
            else if ($pre == '/')
                $total /= $price;
            else if ($pre == 'u')
                $total = $total + (( $total * $price ) / 100);
            else if ($pre == 'd')
                $total = $total - (( $total * $price ) / 100);

            return $total;
        }

    }
