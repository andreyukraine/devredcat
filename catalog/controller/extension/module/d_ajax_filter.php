<?php

class ControllerExtensionModuleDAjaxFilter extends Controller
{
  protected $codename = 'd_ajax_filter';
  protected $route_url = 'extension/module/d_ajax_filter';

  private $route = 'extension/module/d_ajax_filter';

  private $extension = '';

  private $error = array();

  private $theme = 'default';

  private $common_setting = array();

  public function __construct($registry)
  {
    parent::__construct($registry);

    if (!empty($this->request->get['ajax'])) {
      return;
    }

    $this->load->model($this->route);
    $this->load->language($this->route);


    if ($this->config->get('config_theme') == 'default') {
      $this->theme = $this->config->get('theme_default_directory');
    } else {
      $this->theme = $this->config->get('config_theme');
    }

    if (!$this->theme) {
      $this->theme = $this->config->get('config_template');
    }
    $this->load->model('setting/setting');
    $common_setting = $this->model_setting_setting->getSetting($this->codename);

    if (empty($common_setting[$this->codename . '_setting'])) {
      $this->config->load('d_ajax_filter');
      $setting = $this->config->get('d_ajax_filter_setting');

      $common_setting = $setting['general'];
    } else {
      $common_setting = $common_setting[$this->codename . '_setting'];
    }

    $this->common_setting = $common_setting;

    $this->extension = json_decode(file_get_contents(DIR_SYSTEM . 'library/d_shopunity/extension/' . $this->codename . '.json'), true);
  }


  public function index($setting)
  {

    if (!empty($this->request->get['ajax'])) {
      return;
    }

    $data['is_mobile'] = $this->mobile_detect->isMobile();

    $json = array();

    $data['setting'] = $setting;

    $json['selected'] = $this->{'model_extension_module_' . $this->codename}->getParamsToArray();

    if (!empty($setting['title'][$this->config->get('config_language_id')])) {
      $data['setting']['heading_title'] = $setting['title'][$this->config->get('config_language_id')];
    } else {
      $data['setting']['heading_title'] = $this->language->get('heading_title');
    }
    $json['translate']['text_none'] = $this->language->get('text_none');
    $json['translate']['text_search'] = $this->language->get('text_search_placeholder');

    $json['translate']['text_price'] = $this->language->get('text_price');

    $json['translate']['button_filter'] = $this->language->get('button_filter');
    $json['translate']['button_reset'] = $this->language->get('button_reset');

    $json['translate']['text_show_more'] = $this->language->get('text_show_more');
    $json['translate']['text_shrink'] = $this->language->get('text_shrink');

    $json['translate']['text_symbol_left'] = $this->currency->getSymbolLeft($this->session->data['currency']);
    $json['translate']['text_symbol_right'] = $this->currency->getSymbolRight($this->session->data['currency']);
    $json['translate']['text_not_found'] = $this->language->get('text_not_found');

    $filter_data = $this->{'model_extension_module_' . $this->codename}->getFitlerData();

    $base_attribs = array_filter($setting['base_attribs'], function ($base_attrib) {
      return $base_attrib['status'];
    });

    if (empty($base_attribs)) {
      return;
    }

    $data['groups'] = array();

    $this->{'model_extension_module_' . $this->codename}->prepareTableFilter($filter_data);
    uasort($base_attribs, array($this, "compare"));

    // Завантаження користувацьких налаштувань фільтра
    $this->load->model('setting/setting');
    $filter_settings = $this->model_setting_setting->getSetting('filter_settings');
    $show_settings = isset($filter_settings['filter_settings_show']) ? $filter_settings['filter_settings_show'] : [];
    $sort_settings = isset($filter_settings['filter_settings_sort_order']) ? $filter_settings['filter_settings_sort_order'] : [];

    foreach ($base_attribs as $key => $value) {
      if (file_exists(DIR_APPLICATION . '/controller/extension/d_ajax_filter/' . $key . '.php')) {
        $result = $this->load->controller('extension/' . $this->codename . '/' . $key, ($value + array('module_setting' => $setting)), $filter_data);
        if (!empty($result)) {
          // Застосовуємо фільтрацію та сортування для атрибутів та опцій
          if ($key == 'attribute' || $key == 'option') {
            $prefix = ($key == 'attribute') ? 'a' : 'o';
            $filtered_result = [];
            foreach ($result as $group_key => $group_data) {
              $id = $prefix . $group_data['group_id'];
              if (isset($show_settings[$id]) && $show_settings[$id]) {
                $group_data['sort_order'] = isset($sort_settings[$id]) ? (int)$sort_settings[$id] : 0;
                $filtered_result[$group_key] = $group_data;
              }
            }
            // Сортуємо групи всередині типу (наприклад, всі вибрані атрибути між собою)
            uasort($filtered_result, function($a, $b) {
              return $a['sort_order'] - $b['sort_order'];
            });
            $result = $filtered_result;
          }

          if (!empty($result)) {
            $data['groups'][$key] = $result;
          }
        }
      }
    }

    $url = $this->{'model_extension_module_' . $this->codename}->getURLQuery();

    $json['url'] = array(
      'ajax' => 'index.php?route=' . $this->route_url . '/ajax&' . $url
    );

    // Підготовка даних для React-компонента
    $data['react_data'] = [
      'groups' => $data['groups'], // Вже масив, не JSON
      'apiUrls' => [
        'ajax' => $json['url']['ajax']
      ],
      'translations' => $json['translate'],
      'initialQuantities' => $this->{'model_extension_module_' . $this->codename}->getQuantity($this->model_extension_module_d_ajax_filter->getTypes()),
      'initialTotal' => $this->getProductCount($filter_data)
    ];


    return $this->load->view('extension/d_ajax_filter/d_ajax_filter', $data);

  }

  private function compare($a, $b)
  {
    if (isset($a['sort_order']) && isset($b['sort_order'])) {
      if ($a['sort_order'] == $b['sort_order']) {
        return 0;
      }
      return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
    } else {
      return 0;
    }
  }

  public function getQuantity()
  {
    if (isset($this->request->get['curRoute'])) {
      $route = $this->request->get['curRoute'];
    }

    if (isset($this->request->post['status'])) {
      $status = $this->request->post['status'];
    }

    $json = array();
    if (isset($route) && !empty($status)) {
      if (isset($this->request->get['path'])) {
        $parts = explode('_', (string)$this->request->get['path']);
        $categoryId = array_pop($parts);
      } else {
        $categoryId = false;
      }
      if (isset($this->request->get['q'])) {
        $search = $this->request->get['q'];
      } else {
        $search = '';
      }
      if (isset($this->request->get['path'])) {
        $path = $this->request->get['path'];
      } else {
        $path = '';
      }
      if (isset($this->request->get['tag'])) {
        $tag = $this->request->get['tag'];
      } else {
        $tag = $search;
      }
      if (isset($this->request->get['manufacturer_id'])) {
        $manufacturer_id = $this->request->get['manufacturer_id'];
      } else {
        $manufacturer_id = 0;
      }
      if (isset($this->request->get['description'])) {
        $description = $this->request->get['description'];
      } else {
        $description = '';
      }
      if ($this->common_setting['display_sub_category']) {
        $sub_category = true;
      } else {
        $sub_category = false;
      }
      if (isset($this->request->get['quantity_status'])) {
        $quantity_status = $this->request->get['quantity_status'];
      } else {
        $quantity_status = false;
      }

      if ($route == 'product/special') {
        $special = true;
      } else {
        $special = false;
      }

      $data = array(
        'filter_category_id' => $categoryId,
        'filter_name' => $search,
        'filter_tag' => $tag,
        'filter_description' => $description,
        'filter_sub_category' => $sub_category,
        'filter_manufacturer_id' => $manufacturer_id
      );

      $this->request->get['ajax'] = 'ajax';

      $json['success'] = 'success';

      $this->{'model_extension_module_' . $this->codename}->prepareTable($data, true);

      $json['quantity'] = $this->{'model_extension_module_' . $this->codename}->getQuantity($status);

      $json['total_prods'] = $this->{'model_extension_module_' . $this->codename}->getTotalQuantityProducts($data);

    } else {
      $json['error'] = 'error';
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function ajax() {
    $this->response->addHeader('Content-Type: application/json');

    $postData = $this->request->post;
    if (empty($postData)) {
      $postData = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $json = ['success' => false, 'products' => '', 'error' => ''];

    if (!empty($postData)) {
      try {
        $this->request->get['ajax'] = true;

        if (isset($postData['filter'])) {
          $this->request->get['filter'] = urldecode($postData['filter']);
        }

        if (isset($postData['page'])) {
          $this->request->get['page'] = (int)$postData['page'];
        }

        if (isset($postData['sort'])) {
          $this->request->get['sort'] = $postData['sort'];
        }

        if (isset($postData['order'])) {
          $this->request->get['order'] = $postData['order'];
        }

        // Передаємо інші параметри в GET для коректної роботи getFitlerData та контролерів
        if (isset($postData['path'])) $this->request->get['path'] = $postData['path'];
        if (isset($postData['manufacturer_id'])) $this->request->get['manufacturer_id'] = $postData['manufacturer_id'];
        if (isset($postData['q'])) {
            $this->request->get['q'] = $postData['q'];
            $this->request->get['search'] = $postData['q'];
        }
        if (isset($postData['category_id'])) $this->request->get['category_id'] = $postData['category_id'];

        $filter_data = $this->model_extension_module_d_ajax_filter->getFitlerData();
        
        // Визначаємо роут для завантаження товарів
        $route = $postData['curRoute'] ?? 'product/category';
        if ($route == 'manufacturer') $route = 'product/manufacturer/info';
        if ($route == 'category') $route = 'product/category';
        if ($route == 'special') $route = 'product/special';
        if ($route == 'search') $route = 'product/search';
        if ($route == 'discont') $route = 'product/discont';

        $this->model_extension_module_d_ajax_filter->prepareTableFilter($filter_data);

        $output = $this->load->controller($route);

        if (!empty(trim($output))) {
          $json = [
            'success' => true,
            'products' => $output,
            'total_prods' => $this->getProductCount($filter_data),
            'quantity' => $this->{'model_extension_module_' . $this->codename}->getQuantity($this->model_extension_module_d_ajax_filter->getTypes())
          ];
        }

      } catch (Exception $e) {
        $json['error'] = 'Помилка сервера: ' . $e->getMessage();
      }
    }

    $this->response->setOutput(json_encode($json));
  }

  protected function getProductCount($filterData) {
    $this->{'model_extension_module_' . $this->codename}->prepareTable($filterData, true);
    return $this->{'model_extension_module_' . $this->codename}->getTotalQuantityProducts($filterData);
  }

  /**
   * Очистка вывода от лишних секций
   */
  protected function cleanOutput($output)
  {
    $this->load->language($this->route);

    if (empty($output)) {
      return $this->language->get('text_not_found');
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->formatOutput = false;
    $dom->preserveWhiteSpace = false;

    $html = mb_convert_encoding($output, 'HTML-ENTITIES', 'UTF-8');

    $dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>');
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $result = '';

    // Пошук за ID ajax-filter-container
    $container = $xpath->query('//*[@id="ajax-filter-container"]')->item(0);

    if ($container) {
      foreach ($container->childNodes as $node) {
        $result .= $dom->saveHTML($node);
      }
    } else {
      // Пошук першого div, що містить "product-list" у class
      $divs = $xpath->query('//div[contains(@class, "product-list")]');
      if ($divs->length) {
        foreach ($divs->item(0)->childNodes as $node) {
          $result .= $dom->saveHTML($node);
        }
      }
    }

    return trim($result) !== '' ? $result : $output;
  }


}
