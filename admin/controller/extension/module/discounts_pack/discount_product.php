<?php

class ControllerExtensionModuleDiscountsPackDiscountProduct extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->language('extension/module/product_discount');

    $this->document->setTitle("Products discont");
    /* Bootstrap Select CDN */
    $this->document->addStyle("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css");
    $this->document->addScript("https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js");

    $this->load->model('extension/module/discount');

    $data['heading_title'] = $this->language->get('heading_title');

    $data['text_enabled'] = $this->language->get('text_enabled');
    $data['text_disabled'] = $this->language->get('text_disabled');
    $data['text_discount'] = $this->language->get('text_discount');

    $data['text_enabled'] = $this->language->get('text_enabled');
    $data['text_disabled'] = $this->language->get('text_disabled');

    $data['entry_status'] = $this->language->get('entry_status');
    $data['entry_date_start'] = $this->language->get('entry_date_start');
    $data['entry_date_end'] = $this->language->get('entry_date_end');
    $data['entry_product'] = $this->language->get('entry_product');
    $data['entry_percentage'] = $this->language->get('entry_percentage');
    $data['entry_priority'] = $this->language->get('entry_priority');
    $data['entry_qty'] = $this->language->get('entry_qty');
    $data['entry_sort_order'] = $this->language->get('entry_sort_order');
    $data['entry_customer_group'] = $this->language->get('entry_customer_group');
    $this->load->language('module/discounts_pack');
    $data['entry_override_special_price'] = $this->language->get('entry_override_special_price');
    $data['entry_override_discount_price'] = $this->language->get('entry_override_discount_price');

    $data['help_override_special_price'] = $this->language->get('help_override_special_price');
    $data['help_override_discount_price'] = $this->language->get('help_override_discount_price');


    $data['button_save'] = $this->language->get('button_save');
    $data['button_cancel'] = $this->language->get('button_cancel');
    $data['button_add'] = $this->language->get('button_add');
    $data['button_remove'] = $this->language->get('button_remove');
    $data['button_yes'] = $this->language->get('button_yes');
    $data['button_no'] = $this->language->get('button_no');

    $data['error_permission'] = $this->language->get('error_permission');

    $data['options'] = array('default', 'exclusive', 'override');

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
      'href' => $this->url->link('extension/module/discounts_pack/discount_product', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL')
    );

    $data['permission'] = $this->user->hasPermission('modify', 'extension/module/discounts_pack/discount_product') ? 1 : 0;

    $data['cancel'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'], true);

    $data['user_token'] = $this->session->data['user_token'];

    $data['back_link'] = $this->url->link('extension/module/discounts_pack', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

    if (isset($this->request->post['total_product_discount_status'])) {
      $data['product_discount_status'] = $this->request->post['total_product_discount_status'];
    } else {
      $data['product_discount_status'] = $this->config->get('total_product_discount_status');
    }

    if (isset($this->request->post['total_product_discount_sort_order'])) {
      $data['product_discount_sort_order'] = $this->request->post['total_product_discount_sort_order'];
    } else {
      $data['product_discount_sort_order'] = $this->config->get('total_product_discount_sort_order');
    }

    $this->load->model('catalog/product');


    $data['products'] = $this->model_catalog_product->getProducts();

    if (version_compare(VERSION, '2.1', '>=')) {
      $this->load->model('customer/customer_group');
      $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
    } else {
      $this->load->model('sale/customer_group');
      $data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
    }

    $discounts = $this->model_extension_module_discount->getAllDiscounts('product');

    if (isset($this->request->post['product_discount'])) {
      $product_discounts = $this->request->post['product_discount'];
    } elseif (isset($discounts)) {
      $product_discounts = $discounts;
    } else {
      $product_discounts = array();
    }

    $data['product_discounts'] = array();

    foreach ($product_discounts as $product_discount) {

      //++Andrey
      $dd = $this->model_catalog_product->getProductOptions($product_discount['product_id']);
      $opts = array();
      foreach ($dd as $option) {
        $v = array();
        foreach ($option['product_option_value'] as $val) {
          $v[] = array(
            'option_value_id' => $val['option_value_id'],
            'name' => $val['name']
          );
        }

        $opts[] = array(
          'option_id' => $option['option_id'],
          'name' => $option['name'],
          'value' => $option['value'],
          'values' => $v
        );
      }
      $data['opts_add_js'] = $opts;

      //++Andrey
      $discont_opts = json_decode($product_discount['options'], true);
      $dd = $this->model_catalog_product->getProductOptions($product_discount['product_id']);
      $opts = array();
      if (!empty($dd)) {
        foreach ($dd as $option) {
          $v = array();
          foreach ($option['product_option_value'] as $val) {
            $v[] = array(
              'option_value_id' => $val['option_value_id'],
              'name' => $val['name'],
              'selected' => (!empty($discont_opts) ? (array_search($val['option_value_id'], $discont_opts) ? true : false) : false)
            );
          }

          $opts[] = array(
            'option_id' => $option['option_id'],
            'name' => $option['name'],
            'value' => $option['value'],
            'values' => $v
          );
        }
      }

      $data['product_discounts'][] = array(
        'customer_group_id' => $product_discount['customer_group_id'],
        'product_id' => $product_discount['product_id'],
        'options' => $opts,
        'quantity' => $product_discount['quantity'],
        'priority' => $product_discount['priority'],
        'price' => $product_discount['price'],
        'percentage' => $product_discount['percentage'],
        'date_start' => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
        'date_end' => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
      );
    }


    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('extension/module/discount_product', $data));
  }

  public function getItemOptions()
  {
    $this->load->model('catalog/product');

    $json = array();

    $json = array();

    $dd = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
    $opts = array();
    $html = '<td class="text-center bl-ops' . $this->request->post['discount_row'] . '"><table class="table-sm table-striped table-opts" style="width: 100%">';
    foreach ($dd as $option) {

      $html .= '  <tr><td><span>' . $option['name'] . '</span></td>';
      $html .= '  <td><select name="product_discount[' .$this->request->post['discount_row']. '][options][{{ option.option_id }}]" class="form-control row' . $this->request->post['discount_row'] . ' selectpicker" data-live-search="true">';


      $v = array();
      foreach ($option['product_option_value'] as $val) {
        $html .= '  <option value="'.$val['option_value_id'].'">'.$val['name'].'</option>';
      }

      $html .= '  </select></td></tr>';
    }
    $html .= '  </table></td>';
    $json['options'] = $html;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function getProductOptionValues()
  {
    $this->load->model('catalog/product');

    $json = array();
    $html_option_values = "";

    $dd = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
    foreach ($dd as $option) {
      if ($option['option_id'] == $this->request->post['option_id']) {
        foreach ($option['product_option_value'] as $val) {
          $html_option_values .= '<option value="' . $val['option_value_id'] . '">' . $val['name'] . '</option>';
        }
      }
    }

    $json['option_values'] = $html_option_values;

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function saveDiscount()
  {

    $json = array();

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
      $this->load->language('extension/module/product_discount');
      $this->load->model('setting/setting');
      $this->load->model('extension/module/discount');

      $this->load->model('catalog/option');

      parse_str(htmlspecialchars_decode($this->request->post['setting']), $settings);

      $this->model_setting_setting->editSetting('total_product_discount', $settings);

      if (!empty($this->request->post['product_discount'])) {

        //parse_str(htmlspecialchars_decode($this->request->post['product_discount']), $discount_data);

        $decoded_string = html_entity_decode($this->request->post['product_discount']);
        $decoded_string = str_replace('&amp;', '&', $decoded_string);
        parse_str($decoded_string, $discount_data);


        $this->load->model('catalog/option');
        foreach ($discount_data['product_discount'] as &$item) {
          $opts_value = array();

            foreach ($item['options'] as $option_id => $value) {
              $opts = $this->model_catalog_option->getProductOptionValueId($item['product_id'], $option_id, $value);
              if ($opts != null) {
                $opts_value += [(int)$opts['product_option_id'] => (int)$opts['product_option_value_id']];
              }
            }

          $item['options'] = json_encode($item['options']);
          $item['options_value'] = json_encode($opts_value);

        }
        unset($item);

        $this->model_extension_module_discount->setDiscount($discount_data['product_discount'], 'product');
      } else {
        $this->model_extension_module_discount->setDiscount(NULL, 'product');
      }

      $json['success'] = $this->language->get('text_success');

    } else {
      $json['error'] = $this->language->get('error_warning');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));

  }

  public function activate()
  {
    $this->load->language('extension/module/product_discount');
    $json = array();

    if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
      $this->db->query("UPDATE `" . DB_PREFIX . "product_discount` SET `status` = '1' WHERE `product_discount_id` = '" . (int)$this->request->post['row'] . "' ");
    }

    $json['success'] = $this->language->get('text_activated');

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function deactivate()
  {
    $this->load->language('extension/module/product_discount');
    $json = array();

    if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['row'])) {
      $this->db->query("UPDATE `" . DB_PREFIX . "product_discount` SET `status` = '0' WHERE `product_discount_id` = '" . (int)$this->request->post['row'] . "' ");
    }

    $json['success'] = $this->language->get('text_deactivated');

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function install()
  {
    $sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_discount` (
		`product_discount_id` int(11) NOT NULL AUTO_INCREMENT, 
		`product_id` int(11) NOT NULL, 
		`customer_group_id` int(11) NOT NULL, 
		`priority` int(5) NOT NULL DEFAULT '1'";
    $sql .= "`percentage` decimal(15,4) NOT NULL DEFAULT '0.0000', 
		`quantity` int(1) NOT NULL DEFAULT '0',
		`status` INT  NOT NULL DEFAULT '1', 
		`date_start` date NOT NULL DEFAULT '0000-00-00', 
		`date_end` date NOT NULL DEFAULT '0000-00-00', 
		PRIMARY KEY (`product_discount_id`), 
		KEY `product_id` (`product_id`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

    $this->db->query($sql);

    $this->db->query("INSERT INTO `" . DB_PREFIX . "extension` (`extension_id`, `type`, `code`) VALUES (NULL, 'total', 'product_discount'); ");
    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_product_discount', 'total_product_discount_sort_order', '2', '0'); ");
    $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` (`setting_id`, `store_id`, `code`, `key`, `value`, `serialized`) VALUES (NULL, '0', 'total_product_discount', 'total_product_discount_status', '1', '0');");
  }

  public function uninstall()
  {
    $key = 'product';

    $this->db->query("DROP TABLE `" . DB_PREFIX . $key . "_discount`");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "extension` WHERE `code` = '" . $key . "_discount';");
    $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'total_" . $key . "_discount';");

  }

}
