<?php

class ControllerExtensionModuleCustomTotal extends Controller
{
  public function index($setting = array())
  {
    if (isset($setting['status']) && (bool)$setting['status'] === true) {
      $this->load->language('extension/module/custom/total');
      $data['totals'] = array();

      $result = $this->getTotals($setting);

      if (!empty($result['totals'])) {
        foreach ($result['totals'] as $total_row) {
          
          $is_side_cart = !empty($setting['is_side_cart']);
          $code = $total_row['code'] ?? '';

          // Якщо це боковий кошик, ховаємо доставку
          if ($is_side_cart && $code == 'shipping' || $code == 'product_discount') {
            continue;
          }

          $value = isset($total_row['value']) ? (float)$total_row['value'] : 0.0;
          $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
          
          if ($code == 'weight') {
            $text = $this->weight->format($value, $this->config->get('config_weight_class_id'), $this->language->get('decimal_point'), $this->language->get('thousand_point'));
          } else {
            $text = $this->currency->format($value, $currency);
          }
          
          if (empty($text) && $value == 0) {
             $text = '0₴';
          } elseif (empty($text)) {
            $text = number_format($value, 2, '.', '') . '₴';
          }

          $title = $total_row['title'] ?? '';
          if (empty($title) && $code == 'shipping') {
            $title = 'Вартість доставки';
          } elseif (empty($title) && $code == 'total') {
            $title = 'До сплати';
          }

          if ($code == 'shipping') {
            if (isset($total_row["type"]) && isset($total_row["cost_delivery"])){
              if ($total_row["type"] == "np" && $total_row["cost_delivery"] > 0){
                $text = "0₴";
              }
            }
          }

          $data['totals'][] = array(
            'title' => $title,
            'text'  => $text
          );
        }
      }

      return $this->load->view('extension/module/custom/total', $data);
    }
    return false;
  }

  public function getTotals($setting = array())
  {
    $this->load->model('setting/extension');
    $totals = array();
    $taxes = $this->cart->getTaxes();
    $total = 0;

    $total_data = array(
      'totals' => &$totals,
      'taxes' => &$taxes,
      'total' => &$total
    );

    $results = $this->model_setting_extension->getExtensions('total');
    $sort_order = array();
    foreach ($results as $key => $value) {
      $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
    }
    array_multisort($sort_order, SORT_ASC, $results);

    foreach ($results as $result) {
      if ($this->config->get('total_' . $result['code'] . '_status')) {
        $this->load->model('extension/total/' . $result['code']);
        $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
      }
    }

    return array('total' => $total, 'totals' => $totals);
  }

  public function ajax_total()
  {
    $this->load->model('setting/setting');
    $setting = json_decode($this->model_setting_setting->getSettingValue('module_custom_total'), true);
    $setting['is_side_cart'] = true; 
    $this->response->setOutput($this->index($setting));
  }
}
